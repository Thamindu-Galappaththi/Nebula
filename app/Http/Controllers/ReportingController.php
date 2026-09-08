<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Intake;
use App\Models\Module;
use App\Models\ModuleManagement;
use App\Models\PaymentDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportingController extends Controller
{
    /**
     * Show the reporting dashboard
     */
    public function showReportingDashboard()
    {
        if (!Auth::check() || !Auth::user()->status) {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        if (view()->exists('reporting.dashboard')) {
            return view('reporting.dashboard');
        }

        if (view()->exists('dashboards.dgmdashboard')) {
            return view('dashboards.dgmdashboard');
        }

        return redirect()->route('dashboard');
    }

    /**
     * Show the reporting dashboard (alias for showReportingDashboard)
     */
    public function showDashboard()
    {
        return $this->showReportingDashboard();
    }

    /**
     * Generate student enrollment report
     */
    public function generateStudentEnrollmentReport(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'location' => 'nullable|string|in:Welisara,Moratuwa,Peradeniya',
                'course_id' => 'nullable|exists:courses,course_id',
                'format' => 'nullable|string|in:json,pdf,excel,csv'
            ]);

            $query = Student::query();

            // Apply filters
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }
            if ($request->filled('location')) {
                $query->where('institute_location', $request->location);
            }
            if ($request->filled('course_id')) {
                $query->whereHas('courseRegistrations', function ($q) use ($request) {
                    $q->where('course_id', $request->course_id);
                });
            }

            $students = $query->with(['courseRegistrations.course', 'courseRegistrations.intake'])
                            ->get();

            // Check if file export requested
            $format = strtolower($request->input('format', 'json'));
            if (in_array($format, ['csv', 'excel', 'pdf'])) {
                return $this->exportEnrollmentCsv($students, "enrollment_report_" . now()->format('Ymd_His') . ".csv");
            }

            // Group by location
            $locationStats = $students->groupBy(function ($s) {
                return $s->institute_location ?: 'Unknown';
            })->map(function ($group, $loc) {
                return [
                    'count' => $group->count(),
                    'male' => $group->filter(fn($s) => strtolower($s->gender ?? '') === 'male')->count(),
                    'female' => $group->filter(fn($s) => strtolower($s->gender ?? '') === 'female')->count(),
                    'students' => $group->map(function ($student) {
                        return [
                            'student_id' => $student->student_id,
                            'name' => $student->full_name,
                            'email' => $student->email,
                            'location' => $student->institute_location,
                            'registration_date' => optional($student->created_at)->format('Y-m-d'),
                            'courses' => $student->courseRegistrations->map(function ($reg) {
                                return [
                                    'course_name' => $reg->course->course_name ?? 'N/A',
                                    'intake_name' => $reg->intake->batch ?? ($reg->intake->intake_name ?? 'N/A'),
                                    'registration_date' => optional($reg->created_at)->format('Y-m-d')
                                ];
                            })
                        ];
                    })
                ];
            });

            $report = [
                'total_students' => $students->count(),
                'male_students' => $students->filter(fn($s) => strtolower($s->gender ?? '') === 'male')->count(),
                'female_students' => $students->filter(fn($s) => strtolower($s->gender ?? '') === 'female')->count(),
                'location_stats' => $locationStats,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'filters_applied' => $request->only(['start_date', 'end_date', 'location', 'course_id'])
            ];

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            Log::error('Student enrollment report generation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate course performance report
     */
    public function generateCoursePerformanceReport(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'course_id' => 'nullable|exists:courses,course_id',
                'location' => 'nullable|string|in:Welisara,Moratuwa,Peradeniya',
                'semester' => 'nullable|string|max:50',
                'format' => 'nullable|string|in:json,pdf,excel,csv'
            ]);

            $query = CourseRegistration::with(['student', 'course', 'intake']);

            // Apply filters
            if ($request->filled('course_id')) {
                $query->where('course_id', $request->course_id);
            }
            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }

            $registrations = $query->get();
            $semesterFilter = $request->input('semester');

            // Calculate performance metrics
            $courseStats = $registrations->groupBy('course_id')
                                       ->map(function ($group) use ($semesterFilter) {
                                           $course = $group->first()->course;
                                           $totalStudents = $group->count();
                                           $studentIds = $group->pluck('student_id')->filter();

                                           // Get attendance data (status is boolean: 1=Present, 0=Absent)
                                           $attendanceQuery = Attendance::whereIn('student_id', $studentIds)
                                                                      ->where('course_id', $course->course_id);
                                           if ($semesterFilter) {
                                               $attendanceQuery->where('semester', $semesterFilter);
                                           }
                                           $attendanceData = $attendanceQuery->get();

                                           $presentCount = $attendanceData->filter(fn($a) => (bool)$a->status)->count();
                                           $avgAttendance = $attendanceData->count() > 0
                                               ? round(($presentCount / $attendanceData->count()) * 100, 2)
                                               : 0;

                                           // Get exam results using marks column
                                           $examQuery = ExamResult::whereIn('student_id', $studentIds)
                                                                ->where('course_id', $course->course_id);
                                           if ($semesterFilter) {
                                               $examQuery->where('semester', $semesterFilter);
                                           }
                                           $examData = $examQuery->get();

                                           $avgScore = $examData->count() > 0
                                               ? round((float)$examData->avg(fn($e) => $e->marks ?? $e->score ?? 0), 2)
                                               : 0;

                                           return [
                                               'course_id' => $course->course_id,
                                               'course_name' => $course->course_name,
                                               'total_students' => $totalStudents,
                                               'average_attendance' => $avgAttendance,
                                               'average_exam_score' => $avgScore,
                                               'completion_rate' => $this->calculateCompletionRate($group),
                                               'students' => $group->map(function ($reg) {
                                                   return [
                                                       'student_id' => $reg->student->student_id ?? $reg->student_id,
                                                       'student_name' => $reg->student->full_name ?? 'N/A',
                                                       'intake_name' => $reg->intake->batch ?? ($reg->intake->intake_name ?? 'N/A'),
                                                       'registration_date' => optional($reg->created_at)->format('Y-m-d'),
                                                       'status' => $reg->status ?? 'Active'
                                                   ];
                                               })
                                           ];
                                       });

            $report = [
                'total_courses' => $courseStats->count(),
                'total_students' => $registrations->count(),
                'course_performance' => $courseStats,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'filters_applied' => $request->only(['course_id', 'location', 'semester'])
            ];

            $format = strtolower($request->input('format', 'json'));
            if (in_array($format, ['csv', 'excel', 'pdf'])) {
                return $this->exportPerformanceCsv($courseStats, "course_performance_" . now()->format('Ymd_His') . ".csv");
            }

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            Log::error('Course performance report generation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate attendance report
     */
    public function generateAttendanceReport(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'course_id' => 'nullable|exists:courses,course_id',
                'location' => 'nullable|string|in:Welisara,Moratuwa,Peradeniya',
                'semester' => 'nullable|string|max:50',
                'format' => 'nullable|string|in:json,pdf,excel,csv'
            ]);

            $query = Attendance::with(['student', 'course']);

            // Apply filters
            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
            }
            if ($request->filled('course_id')) {
                $query->where('course_id', $request->course_id);
            }
            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }
            if ($request->filled('semester')) {
                $query->where('semester', $request->semester);
            }

            $attendance = $query->get();

            // Calculate attendance statistics based on live boolean status (1/true=Present, 0/false=Absent)
            $totalSessions = $attendance->count();
            $presentSessions = $attendance->filter(fn($a) => (bool)$a->status)->count();
            $absentSessions = $attendance->filter(fn($a) => !(bool)$a->status)->count();
            $lateSessions = 0;

            $attendanceRate = $totalSessions > 0 ? round(($presentSessions / $totalSessions) * 100, 2) : 0;

            // Group by course
            $courseStats = $attendance->groupBy('course_id')
                                    ->map(function ($group) {
                                        $course = $group->first()->course;
                                        $total = $group->count();
                                        $present = $group->filter(fn($a) => (bool)$a->status)->count();
                                        $absent = $group->filter(fn($a) => !(bool)$a->status)->count();

                                        return [
                                            'course_id' => $course->course_id ?? 'N/A',
                                            'course_name' => $course->course_name ?? 'N/A',
                                            'total_sessions' => $total,
                                            'present' => $present,
                                            'absent' => $absent,
                                            'late' => 0,
                                            'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0
                                        ];
                                    });

            // Group by student
            $studentStats = $attendance->groupBy('student_id')
                                     ->map(function ($group) {
                                         $student = $group->first()->student;
                                         $total = $group->count();
                                         $present = $group->filter(fn($a) => (bool)$a->status)->count();
                                         $absent = $group->filter(fn($a) => !(bool)$a->status)->count();

                                         return [
                                             'student_id' => $student->student_id ?? 'N/A',
                                             'student_name' => $student->full_name ?? 'N/A',
                                             'total_sessions' => $total,
                                             'present' => $present,
                                             'absent' => $absent,
                                             'late' => 0,
                                             'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0
                                         ];
                                     });

            $report = [
                'total_sessions' => $totalSessions,
                'present_sessions' => $presentSessions,
                'absent_sessions' => $absentSessions,
                'late_sessions' => $lateSessions,
                'overall_attendance_rate' => $attendanceRate,
                'course_statistics' => $courseStats,
                'student_statistics' => $studentStats,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'filters_applied' => $request->only(['start_date', 'end_date', 'course_id', 'location', 'semester'])
            ];

            $format = strtolower($request->input('format', 'json'));
            if (in_array($format, ['csv', 'excel', 'pdf'])) {
                return $this->exportAttendanceCsv($attendance, "attendance_report_" . now()->format('Ymd_His') . ".csv");
            }

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            Log::error('Attendance report generation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate financial report
     */
    public function generateFinancialReport(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'location' => 'nullable|string|in:Welisara,Moratuwa,Peradeniya',
                'course_id' => 'nullable|exists:courses,course_id',
                'format' => 'nullable|string|in:json,pdf,excel,csv'
            ]);

            // Query CourseRegistration with related payments
            $query = CourseRegistration::with(['student', 'course', 'intake', 'payments']);

            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }
            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }
            if ($request->filled('course_id')) {
                $query->where('course_id', $request->course_id);
            }

            $registrations = $query->get();

            // Calculate revenue per registration from PaymentDetail records or fallback to registration_fee
            $registrationRevenues = $registrations->map(function ($reg) {
                $paymentAmount = (float) $reg->payments->sum('amount');
                $registrationFee = (float) ($reg->registration_fee ?? 0);
                $revenue = $paymentAmount > 0 ? $paymentAmount : $registrationFee;

                return [
                    'registration' => $reg,
                    'revenue' => $revenue,
                ];
            });

            // Financial metrics
            $totalRevenue = $registrationRevenues->sum('revenue');
            $totalStudents = $registrations->count();
            $averagePayment = $totalStudents > 0 ? round($totalRevenue / $totalStudents, 2) : 0;

            // Group by course
            $courseRevenue = $registrationRevenues->groupBy(function ($item) {
                return $item['registration']->course_id;
            })->map(function ($group) {
                $course = $group->first()['registration']->course;
                $revenue = $group->sum('revenue');
                $students = $group->count();

                return [
                    'course_id' => $course->course_id ?? 'N/A',
                    'course_name' => $course->course_name ?? 'N/A',
                    'total_revenue' => $revenue,
                    'student_count' => $students,
                    'average_payment' => $students > 0 ? round($revenue / $students, 2) : 0
                ];
            });

            // Group by location
            $locationRevenue = $registrationRevenues->groupBy(function ($item) {
                return $item['registration']->location ?? 'Unknown';
            })->map(function ($group, $loc) {
                $revenue = $group->sum('revenue');
                $students = $group->count();

                return [
                    'location' => $loc,
                    'total_revenue' => $revenue,
                    'student_count' => $students,
                    'average_payment' => $students > 0 ? round($revenue / $students, 2) : 0
                ];
            });

            // Monthly revenue breakdown
            $monthlyRevenue = $registrationRevenues->groupBy(function ($item) {
                return optional($item['registration']->created_at)->format('Y-m') ?? 'Unknown';
            })->map(function ($group, $month) {
                return [
                    'month' => $month,
                    'revenue' => $group->sum('revenue'),
                    'students' => $group->count()
                ];
            });

            $report = [
                'total_revenue' => $totalRevenue,
                'total_students' => $totalStudents,
                'average_payment' => $averagePayment,
                'course_revenue' => $courseRevenue,
                'location_revenue' => $locationRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'filters_applied' => $request->only(['start_date', 'end_date', 'location', 'course_id'])
            ];

            $format = strtolower($request->input('format', 'json'));
            if (in_array($format, ['csv', 'excel', 'pdf'])) {
                return $this->exportFinancialCsv($registrationRevenues, "financial_report_" . now()->format('Ymd_His') . ".csv");
            }

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            Log::error('Financial report generation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate module assignment report
     */
    public function generateModuleAssignmentReport(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'course_id' => 'nullable|exists:courses,course_id',
                'location' => 'nullable|string|in:Welisara,Moratuwa,Peradeniya',
                'semester' => 'nullable|string|in:1,2,3,4,5,6',
                'format' => 'nullable|string|in:json,pdf,excel,csv'
            ]);

            $query = ModuleManagement::with(['student', 'course', 'module', 'intake']);

            // Apply filters
            if ($request->filled('course_id')) {
                $query->where('course_id', $request->course_id);
            }
            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }
            if ($request->filled('semester')) {
                $query->where('semester', $request->semester);
            }

            $assignments = $query->get();

            // Group by module
            $moduleStats = $assignments->groupBy('module_id')
                                     ->map(function ($group) {
                                         $module = $group->first()->module;
                                         $course = $group->first()->course;

                                         return [
                                             'module_id' => $module->module_id ?? $group->first()->module_id,
                                             'module_name' => $module->module_name ?? 'N/A',
                                             'course_name' => $course->course_name ?? 'N/A',
                                             'student_count' => $group->count(),
                                             'students' => $group->map(function ($assignment) {
                                                 return [
                                                     'student_id' => $assignment->student->student_id ?? $assignment->student_id,
                                                     'student_name' => $assignment->student->full_name ?? 'N/A',
                                                     'semester' => $assignment->semester
                                                 ];
                                             })
                                         ];
                                     });

            // Group by semester
            $semesterStats = $assignments->groupBy('semester')
                                       ->map(function ($group) {
                                           return [
                                               'semester' => $group->first()->semester,
                                               'total_assignments' => $group->count(),
                                               'unique_students' => $group->unique('student_id')->count(),
                                               'unique_modules' => $group->unique('module_id')->count()
                                           ];
                                       });

            $report = [
                'total_assignments' => $assignments->count(),
                'unique_students' => $assignments->unique('student_id')->count(),
                'unique_modules' => $assignments->unique('module_id')->count(),
                'module_statistics' => $moduleStats,
                'semester_statistics' => $semesterStats,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'filters_applied' => $request->only(['course_id', 'location', 'semester'])
            ];

            $format = strtolower($request->input('format', 'json'));
            if (in_array($format, ['csv', 'excel', 'pdf'])) {
                return $this->exportModuleCsv($assignments, "module_report_" . now()->format('Ymd_His') . ".csv");
            }

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            Log::error('Module assignment report generation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report to actual CSV file with proper MIME type
     */
    public function exportReport(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'report_type' => 'required|string|in:enrollment,performance,attendance,financial,module',
                'format' => 'nullable|string|in:pdf,excel,csv',
                'filters' => 'nullable|array'
            ]);

            $reportType = $request->input('report_type');
            $format = $request->input('format', 'csv');
            $filters = $request->input('filters', []);
            $filters['format'] = $format;

            $subRequest = new Request($filters);

            switch ($reportType) {
                case 'enrollment':
                    return $this->generateStudentEnrollmentReport($subRequest);
                case 'performance':
                    return $this->generateCoursePerformanceReport($subRequest);
                case 'attendance':
                    return $this->generateAttendanceReport($subRequest);
                case 'financial':
                    return $this->generateFinancialReport($subRequest);
                case 'module':
                    return $this->generateModuleAssignmentReport($subRequest);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid report type.'
                    ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Report export failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * CSV Stream Helpers
     */
    private function exportEnrollmentCsv($students, string $filename)
    {
        $callback = function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student ID', 'Full Name', 'Email', 'Gender', 'Location', 'Registered Date', 'Course', 'Intake']);
            foreach ($students as $student) {
                $courses = $student->courseRegistrations->map(fn($r) => $r->course->course_name ?? 'N/A')->join('; ');
                $intakes = $student->courseRegistrations->map(fn($r) => $r->intake->batch ?? ($r->intake->intake_name ?? 'N/A'))->join('; ');
                fputcsv($out, [
                    $student->student_id,
                    $student->full_name,
                    $student->email,
                    $student->gender,
                    $student->institute_location,
                    optional($student->created_at)->format('Y-m-d'),
                    $courses ?: 'N/A',
                    $intakes ?: 'N/A'
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportPerformanceCsv($courseStats, string $filename)
    {
        $callback = function () use ($courseStats) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Course ID', 'Course Name', 'Total Students', 'Avg Attendance (%)', 'Avg Exam Score', 'Completion Rate (%)']);
            foreach ($courseStats as $stat) {
                fputcsv($out, [
                    $stat['course_id'],
                    $stat['course_name'],
                    $stat['total_students'],
                    $stat['average_attendance'],
                    $stat['average_exam_score'],
                    $stat['completion_rate']
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportAttendanceCsv($attendance, string $filename)
    {
        $callback = function () use ($attendance) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Student ID', 'Student Name', 'Course', 'Location', 'Semester', 'Status']);
            foreach ($attendance as $record) {
                fputcsv($out, [
                    optional($record->date)->format('Y-m-d') ?: ($record->date ?? 'N/A'),
                    $record->student->student_id ?? $record->student_id,
                    $record->student->full_name ?? 'N/A',
                    $record->course->course_name ?? 'N/A',
                    $record->location ?? 'N/A',
                    $record->semester ?? 'N/A',
                    (bool)$record->status ? 'Present' : 'Absent'
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportFinancialCsv($registrationRevenues, string $filename)
    {
        $callback = function () use ($registrationRevenues) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Registration ID', 'Student ID', 'Student Name', 'Course', 'Intake', 'Location', 'Registered Date', 'Revenue (LKR)']);
            foreach ($registrationRevenues as $item) {
                $reg = $item['registration'];
                fputcsv($out, [
                    $reg->course_registration_id ?? $reg->id,
                    $reg->student->student_id ?? $reg->student_id,
                    $reg->student->full_name ?? 'N/A',
                    $reg->course->course_name ?? 'N/A',
                    $reg->intake->batch ?? ($reg->intake->intake_name ?? 'N/A'),
                    $reg->location ?? 'N/A',
                    optional($reg->created_at)->format('Y-m-d'),
                    number_format($item['revenue'], 2, '.', '')
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportModuleCsv($assignments, string $filename)
    {
        $callback = function () use ($assignments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Assignment ID', 'Student ID', 'Student Name', 'Course', 'Module ID', 'Module Name', 'Semester', 'Location']);
            foreach ($assignments as $assignment) {
                fputcsv($out, [
                    $assignment->id,
                    $assignment->student->student_id ?? $assignment->student_id,
                    $assignment->student->full_name ?? 'N/A',
                    $assignment->course->course_name ?? 'N/A',
                    $assignment->module->module_id ?? $assignment->module_id,
                    $assignment->module->module_name ?? 'N/A',
                    $assignment->semester,
                    $assignment->location ?? 'N/A'
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Calculate completion rate for a group of registrations
     */
    private function calculateCompletionRate($registrations)
    {
        $total = $registrations->count();
        $completed = $registrations->filter(fn($r) => in_array(strtolower($r->status ?? ''), ['completed', 'graduated']))->count();

        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }
}
