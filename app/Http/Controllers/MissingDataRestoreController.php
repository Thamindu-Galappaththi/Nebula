<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Support\MissingDataRestoreService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MissingDataRestoreController extends Controller
{
    public function __construct(private MissingDataRestoreService $restore)
    {
    }

    public function index()
    {
        return view('student_management.missing_data_restore');
    }

    public function semesters(Request $request)
    {
        $intakeId = (int) $request->query('intake_id');
        $semesters = Semester::query()
            ->where('intake_id', $intakeId)
            ->orderBy('start_date')
            ->get(['id', 'name', 'start_date', 'end_date', 'status']);

        return response()->json(['semesters' => $semesters]);
    }

    public function template(Request $request): StreamedResponse
    {
        $type = $request->query('type', MissingDataRestoreService::TYPE_STUDENTS);
        [$filename, $headers, $sample] = match ($type) {
            MissingDataRestoreService::TYPE_MODULES => [
                'restore_modules_template.xlsx',
                ['module_code', 'module_name', 'module_type', 'module_category', 'module_cordinator', 'credits'],
                ['6FTC1162', 'Satellite And Terrestrial Communication Systems', 'core', 'degree', 'Darshana Bandara', '15'],
            ],
            MissingDataRestoreService::TYPE_SEMESTERS => [
                'restore_semesters_template.xlsx',
                ['intake', 'semester', 'start', 'end'],
                ['2024-JUl-B08-DS', 'A', '2025-07-21', '2025-10-17'],
            ],
            MissingDataRestoreService::TYPE_SEMESTER_MODULES => [
                'restore_semester_modules_template.xlsx',
                ['module_code', 'specialization'],
                ['6FTC1158', 'ECME'],
            ],
            default => [
                'restore_students_template.xlsx',
                ['title', 'name_with_initials', 'full_name', 'gender', 'id_type', 'id_value', 'email', 'mobile_phone', 'birthday', 'address', 'district', 'marital_status', 'specialization'],
                ['Mr', 'H.Hanan', 'Hasbullah Hanan', 'Male', 'National id', '200313413524', 'hananhasbullah123@gmail.com', '0770000000', '2003-05-13', '', 'Colombo', 'Unmarried', ''],
            ],
        };

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers, $sample], null, 'A1');

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:students,modules,semesters,semester_modules',
            'file' => 'required|file|max:10240',
            'intake_id' => 'required_if:type,students|nullable|integer',
            'semester_id' => 'required_if:type,semester_modules|nullable|integer',
        ]);

        $extension = strtolower((string) $request->file('file')->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Upload an Excel or CSV template (.xlsx, .xls, .csv).',
            ], 422);
        }

        try {
            $preview = $this->restore->preview(
                $request->file('file'),
                $validated['type'],
                [
                    'intake_id' => $validated['intake_id'] ?? null,
                    'semester_id' => $validated['semester_id'] ?? null,
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $token = bin2hex(random_bytes(16));
        $request->session()->put('missing_data_restore', [
            'token' => $token,
            'type' => $validated['type'],
            'intake_id' => $validated['intake_id'] ?? null,
            'semester_id' => $validated['semester_id'] ?? null,
            'preview' => $preview,
        ]);

        $counts = collect($preview['rows'])->countBy('action');

        return response()->json([
            'success' => true,
            'token' => $token,
            'rows' => $preview['rows'],
            'counts' => [
                'insert' => ($counts['insert_student'] ?? 0) + ($counts['insert_registration'] ?? 0) + ($counts['insert_module'] ?? 0) + ($counts['insert_semester'] ?? 0) + ($counts['insert_link'] ?? 0),
                'skip' => $counts['skip'] ?? 0,
                'error' => $counts['error'] ?? 0,
            ],
        ]);
    }

    public function commit(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $stored = $request->session()->get('missing_data_restore');
        if (!$stored || !hash_equals((string) $stored['token'], (string) $request->input('token'))) {
            return response()->json([
                'success' => false,
                'message' => 'Preview expired. Upload the file again.',
            ], 422);
        }

        try {
            $result = $this->restore->commit($stored['preview'], [
                'intake_id' => $stored['intake_id'],
                'semester_id' => $stored['semester_id'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Insert was rolled back: '.$e->getMessage(),
            ], 422);
        }

        $request->session()->forget('missing_data_restore');

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Inserted %d missing row(s). Skipped %d existing row(s).',
                $result['inserted'],
                $result['skipped']
            ),
            'inserted' => $result['inserted'],
            'skipped' => $result['skipped'],
            'failed' => $result['failed'],
        ]);
    }
}
