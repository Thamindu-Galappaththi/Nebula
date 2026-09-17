<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Intake;
use App\Models\Course;
use App\Models\Module;
use App\Models\PaymentPlan;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class IntakeCreationController extends Controller
{
    public function create(Request $request)
    {
        $data = $this->intakePageData($request);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('courses_&_modules.partials.intake_rows', $data)->render(),
                'pagination' => view('courses_&_modules.partials.intake_pagination', $data)->render(),
            ]);
        }

        return view('courses_&_modules.intake_creation', $data);
    }

    public function export(Request $request)
    {
        $filters = $this->intakeFilters($request);
        $intakes = $this->filteredIntakesQuery($filters)
            ->withCount('registrations')
            ->orderByDesc('start_date')
            ->orderBy('batch')
            ->get();
        $filename = 'intakes_export_' . now()->timezone('Asia/Colombo')->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($intakes) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Course Name', 'Batch', 'Location', 'Mode', 'Type', 'Start Date', 'End Date', 'Enrollment End', 'Capacity', 'Status']);

            foreach ($intakes as $intake) {
                fputcsv($handle, [
                    $intake->course_name,
                    $intake->batch,
                    $intake->location,
                    $intake->intake_mode,
                    $intake->intake_type === 'Fulltime' ? 'Full Time' : ($intake->intake_type === 'Parttime' ? 'Part Time' : $intake->intake_type),
                    $this->formatDate($intake->start_date),
                    $this->formatDate($intake->end_date),
                    $this->formatDate($intake->enrollment_end_date) ?: '-',
                    ($intake->registrations_count ?? 0) . ' / ' . $intake->batch_size,
                    $this->intakeStatusLabel($intake),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request)
    {
        try {
            $course = Course::find($request->course_id);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found.',
                ], 404);
            }

            $courseType = $course->course_type;
            $isCertificate = $courseType === 'certificate';

            $validatedData = $request->validate([
                'location' => ['required', Rule::in(['Welisara', 'Moratuwa', 'Peradeniya'])],
                'course_id' => 'required|exists:courses,course_id',
                'course_type' => ['required', Rule::in(['degree', 'diploma', 'certificate'])],
                'batch' => 'required|string|max:255',
                'batch_size' => 'required|integer|min:1',
                'intake_mode' => ['required', Rule::in(['Physical', 'Online', 'Hybrid'])],
                'intake_type' => ['required', Rule::in(['Fulltime', 'Parttime'])],
                'registration_fee' => 'required|numeric|min:0',
                'franchise_payment' => $isCertificate ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
                'franchise_payment_currency' => $isCertificate ? 'nullable|string|in:LKR,USD,GBP,EUR' : 'required|string|in:LKR,USD,GBP,EUR',
                'course_fee' => 'required|numeric|min:0',
                'sscl_tax' => $isCertificate ? 'nullable|numeric|min:0|max:100' : 'required|numeric|min:0|max:100',
                'bank_charges' => 'nullable|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'enrollment_end_date' => 'nullable|date|before_or_equal:start_date',
                'course_registration_id_pattern' => 'required|string|regex:/^.*\d+$/',
                'module_ids' => $isCertificate ? 'required|array|min:1' : 'nullable|array',
                'module_ids.*' => $isCertificate ? 'required|exists:modules,module_id' : 'nullable|exists:modules,module_id',
            ]);

            if (empty($validatedData['enrollment_end_date'])) {
                $validatedData['enrollment_end_date'] = $validatedData['start_date'];
            }

            $validatedData['course_name'] = $course->course_name;

            if ($isCertificate) {
                $validatedData['franchise_payment'] = $validatedData['franchise_payment'] ?? 0;
                $validatedData['franchise_payment_currency'] = $validatedData['franchise_payment_currency'] ?? 'LKR';
                $validatedData['sscl_tax'] = $validatedData['sscl_tax'] ?? 0;
                $validatedData['bank_charges'] = $validatedData['bank_charges'] ?? 0;
            }

            unset($validatedData['course_type'], $validatedData['module_ids']);
            $intake = Intake::create($validatedData);

            if ($isCertificate && $request->filled('module_ids')) {
                $intake->modules()->attach($request->input('module_ids'));
            }

            $this->syncPaymentPlansFromIntake($intake);

            return response()->json([
                'success' => true,
                'message' => 'Intake created successfully.',
                'intake' => $intake,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing intake data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the intake.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $intake = Intake::with(['course', 'modules'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'intake' => $intake,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching intake for edit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Intake not found.',
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $intake = Intake::findOrFail($id);

            $course = Course::find($request->course_id);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found.',
                ], 404);
            }

            $courseType = $course->course_type;
            $isCertificate = $courseType === 'certificate';

            $validatedData = $request->validate([
                'location' => ['required', Rule::in(['Welisara', 'Moratuwa', 'Peradeniya'])],
                'course_id' => 'required|exists:courses,course_id',
                'batch' => 'required|string|max:255',
                'batch_size' => 'required|integer|min:1',
                'intake_mode' => ['required', Rule::in(['Physical', 'Online', 'Hybrid'])],
                'intake_type' => ['required', Rule::in(['Fulltime', 'Parttime'])],
                'registration_fee' => 'required|numeric|min:0',
                'franchise_payment' => $isCertificate ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
                'franchise_payment_currency' => $isCertificate ? 'nullable|string|in:LKR,USD,GBP,EUR' : 'required|string|in:LKR,USD,GBP,EUR',
                'course_fee' => 'required|numeric|min:0',
                'sscl_tax' => $isCertificate ? 'nullable|numeric|min:0|max:100' : 'required|numeric|min:0|max:100',
                'bank_charges' => 'nullable|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'enrollment_end_date' => 'nullable|date|before_or_equal:start_date',
                'course_registration_id_pattern' => 'required|string|regex:/^.*\d+$/',
                'module_ids' => $isCertificate ? 'required|array|min:1' : 'nullable|array',
                'module_ids.*' => $isCertificate ? 'required|exists:modules,module_id' : 'nullable|exists:modules,module_id',
            ]);

            $validatedData['course_name'] = $course->course_name;

            if ($isCertificate) {
                $validatedData['franchise_payment'] = $validatedData['franchise_payment'] ?? 0;
                $validatedData['franchise_payment_currency'] = $validatedData['franchise_payment_currency'] ?? 'LKR';
                $validatedData['sscl_tax'] = $validatedData['sscl_tax'] ?? 0;
                $validatedData['bank_charges'] = $validatedData['bank_charges'] ?? 0;
            }

            $moduleIds = $validatedData['module_ids'] ?? [];
            unset($validatedData['module_ids']);

            $intake->update($validatedData);

            if ($isCertificate) {
                $intake->modules()->sync($moduleIds);
            } else {
                $intake->modules()->detach();
            }

            $this->syncPaymentPlansFromIntake($intake);

            return response()->json([
                'success' => true,
                'message' => 'Intake updated successfully.',
                'intake' => $intake,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating intake data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the intake.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $intake = Intake::find($id);
        if (!$intake) {
            return response()->json(['success' => false, 'message' => 'Intake not found.'], 404);
        }

        if ($intake->registrations()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This intake cannot be deleted because students are registered on it.',
            ], 422);
        }

        try {
            $intake->modules()->detach();
            $intake->delete();

            return response()->json([
                'success' => true,
                'message' => 'Intake deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting intake: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the intake.',
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $deleted = 0;
        $failed = 0;

        foreach ($data['ids'] as $id) {
            try {
                $intake = Intake::find($id);
                if (!$intake || $intake->registrations()->exists()) {
                    $failed++;
                    continue;
                }
                $intake->modules()->detach();
                $intake->delete();
                $deleted++;
            } catch (\Exception $e) {
                Log::error('Error deleting intake ' . $id . ': ' . $e->getMessage());
                $failed++;
            }
        }

        return response()->json([
            'success' => $deleted > 0,
            'message' => $failed === 0
                ? "Successfully deleted {$deleted} intake(s)."
                : "Deleted {$deleted} intake(s). {$failed} could not be deleted.",
            'deleted' => $deleted,
            'failed' => $failed,
        ]);
    }

    public function getPaymentPlanDetails(Request $request)
    {
        $request->validate([
            'course_type' => 'required|string',
            'location' => 'required|string',
        ]);

        $course = null;
        if ($request->filled('course_id')) {
            $course = Course::find($request->course_id);
        }

        if (!$course && $request->filled('course_name')) {
            $course = Course::where('course_name', $request->course_name)->first();
        }

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Course not found.'], 404);
        }

        $plan = PaymentPlan::where('course_id', $course->course_id)
            ->where('location', $request->location)
            ->where('course_type', $request->course_type)
            ->latest()
            ->first();

        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'No payment plan found for this course/location/type.'], 404);
        }

        return response()->json([
            'success' => true,
            'registration_fee' => $plan->registration_fee,
            'course_fee' => $plan->local_fee,
            'franchise_payment' => $plan->international_fee,
            'franchise_payment_currency' => $plan->international_currency,
            'sscl_tax' => $plan->sscl_tax,
            'bank_charges' => $plan->bank_charges,
        ]);
    }

    private function syncPaymentPlansFromIntake(Intake $intake)
    {
        $plans = PaymentPlan::where('intake_id', $intake->intake_id)->get();
        if ($plans->isEmpty()) {
            return;
        }

        foreach ($plans as $plan) {
            $plan->update([
                'registration_fee' => $intake->registration_fee,
                'local_fee' => $intake->course_fee,
                'international_fee' => $intake->franchise_payment,
                'international_currency' => $intake->franchise_payment_currency,
                'sscl_tax' => $intake->sscl_tax,
                'bank_charges' => $intake->bank_charges,
            ]);
        }
    }

    private function intakePageData(Request $request): array
    {
        $filters = $this->intakeFilters($request);
        $perPage = (int) ($filters['per_page'] ?? 10);

        $intakes = $this->filteredIntakesQuery($filters)
            ->withCount('registrations')
            ->orderByDesc('start_date')
            ->orderBy('batch')
            ->paginate($perPage)
            ->withQueryString();

        $courses = Course::select('course_id', 'course_name', 'course_type', 'location')
            ->orderByRaw("CASE course_type WHEN 'degree' THEN 1 WHEN 'diploma' THEN 2 WHEN 'certificate' THEN 3 ELSE 4 END")
            ->orderBy('course_name')
            ->get();

        $modules = Module::where('module_category', 'certificate')
            ->orderBy('module_name')
            ->get();

        return [
            'intakes' => $intakes,
            'filters' => $filters,
            'perPage' => $perPage,
            'courses' => $courses,
            'modules' => $modules,
            'allCoursesForJson' => $courses->map(fn ($course) => [
                'course_id' => $course->course_id,
                'course_name' => $course->course_name,
                'course_type' => $course->course_type,
                'location' => $course->location,
            ])->values(),
        ];
    }

    private function intakeFilters(Request $request): array
    {
        return $request->validate([
            'search' => 'nullable|string|max:255',
            'location' => 'nullable|in:Welisara,Moratuwa,Peradeniya',
            'intake_mode' => 'nullable|in:Physical,Online,Hybrid',
            'status' => 'nullable|in:upcoming,ongoing,finished',
            'per_page' => 'nullable|integer|in:10,25,50',
        ]);
    }

    private function filteredIntakesQuery(array $filters)
    {
        $today = now()->timezone('Asia/Colombo')->toDateString();

        return Intake::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('course_name', 'like', '%' . $search . '%')
                        ->orWhere('batch', 'like', '%' . $search . '%')
                        ->orWhere('location', 'like', '%' . $search . '%')
                        ->orWhere('intake_mode', 'like', '%' . $search . '%')
                        ->orWhere('intake_type', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['location'] ?? null, fn ($query, $location) => $query->where('location', $location))
            ->when($filters['intake_mode'] ?? null, fn ($query, $mode) => $query->where('intake_mode', $mode))
            ->when(($filters['status'] ?? null) === 'upcoming', fn ($query) => $query->whereDate('start_date', '>', $today))
            ->when(($filters['status'] ?? null) === 'ongoing', function ($query) use ($today) {
                $query->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today);
            })
            ->when(($filters['status'] ?? null) === 'finished', fn ($query) => $query->whereDate('end_date', '<', $today));
    }

    private function intakeStatusLabel(Intake $intake): string
    {
        if ($intake->isPast()) {
            return 'Finished';
        }
        if ($intake->isCurrent()) {
            return 'Ongoing';
        }
        return 'Upcoming';
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return '';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return (string) $value;
        }
    }
}
