<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Module;
use App\Models\ModuleManagement;
use App\Models\Intake;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DataExportImportController extends Controller
{
    /**
     * Show the data export/import dashboard
     */
    public function showDashboard()
    {
        if (!Auth::check() || !Auth::user()->status) {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        return view('data-export-import.dashboard');
    }

    /**
     * Export students data
     */
    public function exportStudents(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'format' => 'required|string|in:csv,excel,json',
                'filters' => 'nullable|array'
            ]);

            $query = Student::query();

            // Apply filters
            if ($request->filled('filters.location')) {
                $query->where('institute_location', $request->input('filters.location'));
            }
            if ($request->filled('filters.gender')) {
                $query->where('gender', $request->input('filters.gender'));
            }
            if ($request->filled('filters.start_date')) {
                $query->whereDate('created_at', '>=', $request->input('filters.start_date'));
            }
            if ($request->filled('filters.end_date')) {
                $query->whereDate('created_at', '<=', $request->input('filters.end_date'));
            }

            $students = $query->get();

            $format = $request->input('format');
            $filename = "students_export_" . now()->format('Y-m-d_H-i-s') . ".{$format}";

            switch ($format) {
                case 'csv':
                    return $this->exportToCSV($students, $filename, 'students');
                case 'excel':
                    return $this->exportToExcel($students, $filename, 'students');
                case 'json':
                    return $this->exportToJSON($students, $filename, 'students');
                default:
                    return response()->json(['success' => false, 'message' => 'Invalid format.'], 400);
            }

        } catch (\Exception $e) {
            Log::error('Student export failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed.'
            ], 500);
        }
    }

    /**
     * Export courses data
     */
    public function exportCourses(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'format' => 'required|string|in:csv,excel,json'
            ]);

            $courses = Course::all();
            $format = $request->input('format');
            $filename = "courses_export_" . now()->format('Y-m-d_H-i-s') . ".{$format}";

            switch ($format) {
                case 'csv':
                    return $this->exportToCSV($courses, $filename, 'courses');
                case 'excel':
                    return $this->exportToExcel($courses, $filename, 'courses');
                case 'json':
                    return $this->exportToJSON($courses, $filename, 'courses');
                default:
                    return response()->json(['success' => false, 'message' => 'Invalid format.'], 400);
            }

        } catch (\Exception $e) {
            Log::error('Course export failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed.'
            ], 500);
        }
    }

    /**
     * Export attendance data
     */
    public function exportAttendance(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'format' => 'required|string|in:csv,excel,json',
                'filters' => 'nullable|array'
            ]);

            $query = Attendance::with(['student', 'course']);

            // Apply filters
            if ($request->filled('filters.start_date')) {
                $query->whereDate('date', '>=', $request->input('filters.start_date'));
            }
            if ($request->filled('filters.end_date')) {
                $query->whereDate('date', '<=', $request->input('filters.end_date'));
            }
            if ($request->filled('filters.course_id')) {
                $query->where('course_id', $request->input('filters.course_id'));
            }
            if ($request->filled('filters.status')) {
                $query->where('status', $request->input('filters.status'));
            }

            $attendance = $query->get();
            $format = $request->input('format');
            $filename = "attendance_export_" . now()->format('Y-m-d_H-i-s') . ".{$format}";

            switch ($format) {
                case 'csv':
                    return $this->exportToCSV($attendance, $filename, 'attendance');
                case 'excel':
                    return $this->exportToExcel($attendance, $filename, 'attendance');
                case 'json':
                    return $this->exportToJSON($attendance, $filename, 'attendance');
                default:
                    return response()->json(['success' => false, 'message' => 'Invalid format.'], 400);
            }

        } catch (\Exception $e) {
            Log::error('Attendance export failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed.'
            ], 500);
        }
    }

    /**
     * Export exam results data
     */
    public function exportExamResults(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'format' => 'required|string|in:csv,excel,json',
                'filters' => 'nullable|array'
            ]);

            $query = ExamResult::with(['student', 'course']);

            // Apply filters
            if ($request->filled('filters.course_id')) {
                $query->where('course_id', $request->input('filters.course_id'));
            }
            if ($request->filled('filters.exam_type')) {
                $query->where('exam_type', $request->input('filters.exam_type'));
            }
            if ($request->filled('filters.start_date')) {
                $query->whereDate('exam_date', '>=', $request->input('filters.start_date'));
            }
            if ($request->filled('filters.end_date')) {
                $query->whereDate('exam_date', '<=', $request->input('filters.end_date'));
            }

            $examResults = $query->get();
            $format = $request->input('format');
            $filename = "exam_results_export_" . now()->format('Y-m-d_H-i-s') . ".{$format}";

            switch ($format) {
                case 'csv':
                    return $this->exportToCSV($examResults, $filename, 'exam_results');
                case 'excel':
                    return $this->exportToExcel($examResults, $filename, 'exam_results');
                case 'json':
                    return $this->exportToJSON($examResults, $filename, 'exam_results');
                default:
                    return response()->json(['success' => false, 'message' => 'Invalid format.'], 400);
            }

        } catch (\Exception $e) {
            Log::error('Exam results export failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed.'
            ], 500);
        }
    }

    /**
     * Import students data
     */
    public function importStudents(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls',
                'format' => 'required|string|in:csv,excel'
            ]);

            $file = $request->file('file');
            $format = $request->input('format');

            $importedCount = 0;
            $errors = [];

            switch ($format) {
                case 'csv':
                    $result = $this->importFromCSV($file, 'students');
                    break;
                case 'excel':
                    $result = $this->importFromExcel($file, 'students');
                    break;
                default:
                    return response()->json(['success' => false, 'message' => 'Invalid format.'], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully.',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Student import failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import exam results data
     */
    public function importExamResults(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt'
            ]);

            $file = $request->file('file');
            $path = $file->getRealPath();

            // Read file with proper encoding handling
            $content = file_get_contents($path);
            if (!mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', 'auto');
            }

            // Split into lines and parse each line
            $lines = str_getcsv($content, "\n");
            $data = [];
            foreach ($lines as $line) {
                if (trim($line)) {
                    $data[] = str_getcsv($line);
                }
            }

            if (empty($data)) {
                return response()->json(['success' => false, 'message' => 'File is empty'], 400);
            }

            $headers = array_shift($data); // Remove header row

            // Determine format and import accordingly
            if ($this->isNewExamResultFormat($headers)) {
                $result = $this->importExamResultWithNamesSimple($data, $headers);
            } else {
                $result = ['imported' => 0, 'failed' => ['Old format not supported in this version']];
            }

            $message = $result['imported'] . " exam results imported successfully.";
            if (!empty($result['failed'])) {
                $message .= " " . count($result['failed']) . " records failed to import.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported_count' => $result['imported'],
                'failed_count' => count($result['failed']),
                'failed_details' => $result['failed']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing file: ' . $e->getMessage()
            ], 500);
        }
    }

    private function importExamResultWithNamesSimple($data, $headers)
    {
        \Log::info('importExamResultWithNamesSimple started', ['data_count' => count($data), 'headers' => $headers]);

        $importedCount = 0;
        $failedRows = [];

        foreach ($data as $rowIndex => $row) {
            try {
                \Log::info("Processing row {$rowIndex}", ['row_data' => $row]);

                // Map CSV row to associative array using headers
                $rowData = array_combine($headers, $row);
                \Log::info("Row data mapped", ['row_data' => $rowData]);

                // Extract data with fallback for missing columns
                $studentName = trim($rowData['Student Name'] ?? '');
                $courseName = trim($rowData['Course Name'] ?? '');
                $moduleName = trim($rowData['Module Name'] ?? '');
                $intakeValue = trim($rowData['Intake'] ?? '');
                $location = trim($rowData['Location'] ?? '');
                $semester = trim($rowData['Semester'] ?? '');
                $marks = trim($rowData['Marks'] ?? '');
                $grade = trim($rowData['Grade'] ?? '');
                $remarks = trim($rowData['Remarks'] ?? '');

                \Log::info("Extracted data", [
                    'student_name' => $studentName,
                    'course_name' => $courseName,
                    'module_name' => $moduleName,
                    'marks' => $marks,
                    'remarks' => $remarks
                ]);

                // Skip empty rows
                if (empty($studentName) || empty($courseName) || empty($moduleName)) {
                    \Log::warning("Skipping row {$rowIndex} - missing required data");
                    $failedRows[] = "Row " . ($rowIndex + 1) . ": Missing required data";
                    continue;
                }

                // Find student by name (flexible matching)
                $student = \App\Models\Student::where(function($query) use ($studentName) {
                    $query->where('full_name', 'LIKE', "%{$studentName}%")
                          ->orWhere('name_with_initials', 'LIKE', "%{$studentName}%");
                })->first();

                if (!$student) {
                    \Log::warning("Student not found", ['student_name' => $studentName]);
                    $failedRows[] = "Row " . ($rowIndex + 1) . ": Student '{$studentName}' not found";
                    continue;
                }

                \Log::info("Found student", ['student_id' => $student->student_id, 'student_name' => $student->full_name]);

                // Find course by name
                $course = \App\Models\Course::where('course_name', 'LIKE', "%{$courseName}%")->first();

                if (!$course) {
                    \Log::warning("Course not found", ['course_name' => $courseName]);
                    $failedRows[] = "Row " . ($rowIndex + 1) . ": Course '{$courseName}' not found";
                    continue;
                }

                \Log::info("Found course", ['course_id' => $course->course_id, 'course_name' => $course->course_name]);

                // Find module by name
                $module = \App\Models\Module::where(function($query) use ($moduleName) {
                    $query->where('module_name', 'LIKE', "%{$moduleName}%")
                          ->orWhere('module_code', 'LIKE', "%{$moduleName}%");
                })->first();

                if (!$module) {
                    \Log::warning("Module not found", ['module_name' => $moduleName]);
                    $failedRows[] = "Row " . ($rowIndex + 1) . ": Module '{$moduleName}' not found";
                    continue;
                }

                \Log::info("Found module", ['module_id' => $module->module_id, 'module_name' => $module->module_name]);

                // Find intake (flexible)
                $intake = null;
                if (!empty($intakeValue)) {
                    $intake = \App\Models\Intake::where('batch', 'LIKE', "%{$intakeValue}%")->first();
                }

                // Find semester (match by name, course, and intake)
                $semesterModel = null;
                if (!empty($semester) && is_numeric($semester) && $course && $intake) {
                    $semesterModel = \App\Models\Semester::where('name', $semester)
                        ->where('course_id', $course->course_id)
                        ->where('intake_id', $intake->intake_id)
                        ->first();
                }

                // Validate that at least marks or grade is provided
                $hasMarks = !empty($marks) && is_numeric($marks);
                $hasGrade = !empty($grade);

                if (!$hasMarks && !$hasGrade) {
                    \Log::warning("Neither marks nor grade provided", ['marks' => $marks, 'grade' => $grade]);
                    $failedRows[] = "Row " . ($rowIndex + 1) . ": Either marks or grade must be provided";
                    continue;
                }

                // Validate marks if provided
                if ($hasMarks && ($marks < 0 || $marks > 100)) {
                    \Log::warning("Invalid marks", ['marks' => $marks]);
                    $failedRows[] = "Row " . ($rowIndex + 1) . ": Invalid marks '{$marks}' (must be 0-100)";
                    continue;
                }

                // Calculate grade from marks if grade is empty but marks is provided
                if ($hasMarks && !$hasGrade) {
                    $grade = $this->calculateGradeSimple($marks);
                }

                // Prepare data for database - use null for empty values
                $finalMarks = $hasMarks ? (float) $marks : null;
                $finalGrade = $hasGrade ? trim($grade) : null;

                \Log::info("Creating exam result", [
                    'student_id' => $student->student_id,
                    'course_id' => $course->course_id,
                    'module_id' => $module->module_id,
                    'marks' => $finalMarks,
                    'grade' => $finalGrade,
                    'location' => $location,
                    'remarks' => $remarks,
                    'remarks_length' => strlen($remarks),
                    'remarks_empty' => empty($remarks),
                    'remarks_is_null' => is_null($remarks)
                ]);

                // Create or update exam result
                $examResult = \App\Models\ExamResult::updateOrCreate(
                    [
                        'student_id' => $student->student_id,
                        'course_id' => $course->course_id,
                        'module_id' => $module->module_id,
                        'semester' => $semester,
                        'intake_id' => $intake ? $intake->intake_id : null
                    ],
                    [
                        'marks' => $finalMarks,
                        'grade' => $finalGrade,
                        'location' => $location,
                        'remarks' => $remarks
                    ]
                );

                \Log::info("Exam result updateOrCreate completed", [
                    'result_id' => $examResult->id,
                    'saved_remarks' => $examResult->remarks,
                    'was_recently_created' => $examResult->wasRecentlyCreated
                ]);

                \Log::info("Exam result created successfully for row {$rowIndex}");
                $importedCount++;

            } catch (\Exception $e) {
                \Log::error("Error processing row {$rowIndex}", ['error' => $e->getMessage()]);
                $failedRows[] = "Row " . ($rowIndex + 1) . ": " . $e->getMessage();
            }
        }

        \Log::info('Import completed', [
            'imported_count' => $importedCount,
            'failed_count' => count($failedRows)
        ]);

        return [
            'imported' => $importedCount,
            'failed' => $failedRows
        ];
    }

    private function calculateGradeSimple($marks)
    {
        if ($marks >= 80) return 'A';
        if ($marks >= 70) return 'B';
        if ($marks >= 60) return 'C';
        if ($marks >= 50) return 'D';
        return 'F';
    }
    public function getImportTemplate(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $request->validate([
                'type' => 'required|string|in:students,courses,attendance,exam_results',
                'format' => 'required|string|in:csv,excel'
            ]);

            $type = $request->input('type');
            $format = $request->input('format');
            $filename = "{$type}_template.{$format}";

            return $this->generateTemplate($type, $filename, $format);

        } catch (\Exception $e) {
            Log::error('Template generation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Template generation failed.'
            ], 500);
        }
    }

    /**
     * Export to CSV
     */
    private function exportToCSV($data, $filename, $type)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data, $type) {
            $file = fopen('php://output', 'w');

            // Write headers based on type
            switch ($type) {
                case 'students':
                    fputcsv($file, [
                        'Student ID', 'Full Name', 'Email', 'Phone', 'NIC', 'Gender',
                        'Date of Birth', 'Address', 'Institute Location', 'Created At'
                    ]);
                    break;
                case 'courses':
                    fputcsv($file, [
                        'Course ID', 'Course Name', 'Duration', 'Fee', 'Description', 'Created At'
                    ]);
                    break;
                case 'attendance':
                    fputcsv($file, [
                        'Attendance ID', 'Student ID', 'Student Name', 'Course ID', 'Course Name',
                        'Date', 'Status', 'Remarks', 'Created At'
                    ]);
                    break;
                case 'exam_results':
                    fputcsv($file, [
                        'Result ID', 'Student ID', 'Student Name', 'Course ID', 'Course Name',
                        'Exam Type', 'Score', 'Max Score', 'Exam Date', 'Remarks', 'Created At'
                    ]);
                    break;
            }

            // Write data
            foreach ($data as $row) {
                switch ($type) {
                    case 'students':
                        fputcsv($file, [
                            $row->student_id,
                            $row->full_name,
                            $row->email,
                            $row->phone,
                            $row->nic,
                            $row->gender,
                            $row->date_of_birth,
                            $row->address,
                            $row->institute_location,
                            $row->created_at
                        ]);
                        break;
                    case 'courses':
                        fputcsv($file, [
                            $row->course_id,
                            $row->course_name,
                            $row->duration,
                            $row->fee,
                            $row->description,
                            $row->created_at
                        ]);
                        break;
                    case 'attendance':
                        fputcsv($file, [
                            $row->attendance_id,
                            $row->student_id,
                            $row->student->full_name ?? 'N/A',
                            $row->course_id,
                            $row->course->course_name ?? 'N/A',
                            $row->date,
                            $row->status,
                            $row->remarks,
                            $row->created_at
                        ]);
                        break;
                    case 'exam_results':
                        fputcsv($file, [
                            $row->result_id,
                            $row->student_id,
                            $row->student->full_name ?? 'N/A',
                            $row->course_id,
                            $row->course->course_name ?? 'N/A',
                            $row->exam_type,
                            $row->score,
                            $row->max_score,
                            $row->exam_date,
                            $row->remarks,
                            $row->created_at
                        ]);
                        break;
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to Excel
     */
    private function exportToExcel($data, $filename, $type)
    {
        // For now, return CSV format as Excel
        // In a real implementation, you would use a library like PhpSpreadsheet
        return $this->exportToCSV($data, str_replace('.xlsx', '.csv', $filename), $type);
    }

    /**
     * Export to JSON
     */
    private function exportToJSON($data, $filename, $type)
    {
        $jsonData = $data->toArray();

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->json($jsonData, 200, $headers);
    }

    /**
     * Import from CSV
     */
    private function importFromCSV($file, $type)
    {
        $importedCount = 0;
        $errors = [];
        $rowNumber = 1;

        if (($handle = fopen($file->getPathname(), "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            $rowNumber++;

            while (($data = fgetcsv($handle)) !== FALSE) {
                try {
                    switch ($type) {
                        case 'students':
                            $this->importStudent($data);
                            break;
                        case 'exam_results':
                            $this->importExamResult($data);
                            break;
                        // Add other types as needed
                    }
                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => $e->getMessage(),
                        'data' => $data
                    ];
                }
                $rowNumber++;
            }
            fclose($handle);
        }

        return [
            'imported_count' => $importedCount,
            'errors' => $errors,
            'total_rows' => $rowNumber - 1
        ];
    }

    /**
     * Import from Excel
     */
    private function importFromExcel($file, $type)
    {
        // For now, treat as CSV
        // In a real implementation, you would use a library like PhpSpreadsheet
        return $this->importFromCSV($file, $type);
    }

    /**
     * Import student data
     */
    private function importStudent($data)
    {
        // Validate required fields
        if (empty($data[1]) || empty($data[2]) || empty($data[3])) {
            throw new \Exception('Required fields missing: Full Name, Email, Phone');
        }

        // Check if student already exists
        $existingStudent = Student::where('email', $data[2])->first();
        if ($existingStudent) {
            throw new \Exception('Student with this email already exists');
        }

        // Create student
        Student::create([
            'full_name' => $data[1],
            'email' => $data[2],
            'phone' => $data[3],
            'nic' => $data[4] ?? null,
            'gender' => $data[5] ?? 'Male',
            'date_of_birth' => $data[6] ?? null,
            'address' => $data[7] ?? null,
            'institute_location' => $data[8] ?? 'Moratuwa',
            'status' => true
        ]);
    }

    /**
     * Import exam result data
     */
    private function importExamResult($data)
    {
        // Log the data being imported for debugging
        Log::info('ImportExamResult called with data:', ['data' => $data]);

        // Check if this is the new format with names instead of IDs
        $isNewFormat = $this->isNewExamResultFormat($data);

        Log::info('Import format detected:', ['is_new_format' => $isNewFormat, 'first_column' => $data[0]]);

        if ($isNewFormat) {
            return $this->importExamResultWithNames($data);
        } else {
            return $this->importExamResultWithIds($data);
        }
    }

    /**
     * Check if the exam result data is in the new format (with names)
     */
    private function isNewExamResultFormat($data)
    {
        // Check if the first column contains a student name instead of numeric ID
        // If it's numeric, it's the old format with IDs
        // If it's not numeric, it's the new format with names
        $isNumeric = is_numeric($data[0]);
        Log::info('Format check:', ['first_column' => $data[0], 'is_numeric' => $isNumeric]);
        return !$isNumeric;
    }

    /**
     * Import exam result data with names (new format)
     * CSV format: Student Name, Course Name, Module Name, Intake, Location, Semester, Marks, Grade, Remarks
     */
    private function importExamResultWithNames($data)
    {
        Log::info('ImportExamResultWithNames called with:', ['data' => $data]);

        // Validate required fields
        if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data[3])) {
            throw new \Exception('Required fields missing: Student Name, Course Name, Module Name, Intake');
        }

        // Find student by name (try exact match first, then partial)
        $student = Student::where('full_name', $data[0])->first();
        if (!$student) {
            // Try partial match in case of slight name differences
            $student = Student::where('full_name', 'LIKE', '%' . trim($data[0]) . '%')->first();
            if (!$student) {
                Log::error('Student not found:', ['name' => $data[0]]);
                throw new \Exception("Student with name '{$data[0]}' not found");
            }
        }
        Log::info('Student found:', ['student_id' => $student->student_id, 'name' => $student->full_name]);

        // Find course by name
        $course = Course::where('course_name', $data[1])->first();
        if (!$course) {
            Log::error('Course not found:', ['name' => $data[1]]);
            throw new \Exception("Course with name '{$data[1]}' not found");
        }
        Log::info('Course found:', ['course_id' => $course->course_id, 'name' => $course->course_name]);

        // Find module by name
        $module = Module::where('module_name', $data[2])->first();
        if (!$module) {
            Log::error('Module not found:', ['name' => $data[2]]);
            throw new \Exception("Module with name '{$data[2]}' not found");
        }
        Log::info('Module found:', ['module_id' => $module->module_id, 'name' => $module->module_name]);

        // Find intake by batch
        $intake = Intake::where('batch', $data[3])->first();
        if (!$intake) {
            // Try to find by partial match on batch field
            $intake = Intake::where('batch', 'LIKE', '%' . $data[3] . '%')->first();
            if (!$intake) {
                Log::error('Intake not found:', ['batch' => $data[3]]);
                throw new \Exception("Intake '{$data[3]}' not found");
            }
        }
        Log::info('Intake found:', ['intake_id' => $intake->intake_id, 'batch' => $intake->batch]);

        // Validate location
        $location = $data[4] ?? 'Moratuwa';
        if (!in_array($location, ['Welisara', 'Moratuwa', 'Peradeniya'])) {
            throw new \Exception('Invalid location. Must be one of: Welisara, Moratuwa, Peradeniya');
        }

        // Get semester name
        $semester = $data[5] ?? 'Semester 1';

        // Check if exam result already exists
        $existingResult = ExamResult::where('student_id', $student->student_id)
            ->where('course_id', $course->course_id)
            ->where('module_id', $module->module_id)
            ->where('intake_id', $intake->intake_id)
            ->where('location', $location)
            ->where('semester', $semester)
            ->first();

        // Validate marks (if provided)
        $marks = null;
        if (!empty($data[6])) {
            $marks = (float) $data[6];
            if ($marks < 0 || $marks > 100) {
                throw new \Exception('Marks must be between 0 and 100');
            }
        }

        // Auto-calculate grade if marks provided but grade not provided
        $grade = $data[7] ?? null;
        if ($marks !== null && empty($grade)) {
            $grade = ExamResult::calculateGradeFromMarks($marks);
        }

        $examResultData = [
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'module_id' => $module->module_id,
            'intake_id' => $intake->intake_id,
            'location' => $location,
            'semester' => $semester,
            'marks' => $marks,
            'grade' => $grade,
            'remarks' => $data[8] ?? null
        ];

        Log::info('Creating/updating exam result with data:', $examResultData);

        if ($existingResult) {
            // Update existing result
            $existingResult->update([
                'marks' => $marks,
                'grade' => $grade,
                'remarks' => $data[8] ?? null
            ]);
            Log::info('Updated existing exam result:', ['id' => $existingResult->id]);
            return $existingResult;
        } else {
            // Create new exam result
            $newResult = ExamResult::create($examResultData);
            Log::info('Created new exam result:', ['id' => $newResult->id]);
            return $newResult;
        }
    }

    /**
     * Import exam result data with IDs (old format)
     * CSV format: Student ID, Course ID, Module ID, Intake ID, Location, Semester, Marks, Grade, Remarks
     */
    private function importExamResultWithIds($data)
    {
        // Validate required fields
        if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data[3])) {
            throw new \Exception('Required fields missing: Student ID, Course ID, Module ID, Intake ID');
        }

        // Validate that student exists
        $student = Student::where('student_id', $data[0])->first();
        if (!$student) {
            throw new \Exception("Student with ID {$data[0]} not found");
        }

        // Validate that course exists
        $course = Course::where('course_id', $data[1])->first();
        if (!$course) {
            throw new \Exception("Course with ID {$data[1]} not found");
        }

        // Validate that module exists
        $module = Module::where('module_id', $data[2])->first();
        if (!$module) {
            throw new \Exception("Module with ID {$data[2]} not found");
        }

        // Validate that intake exists
        $intake = Intake::where('intake_id', $data[3])->first();
        if (!$intake) {
            throw new \Exception("Intake with ID {$data[3]} not found");
        }

        // Check if exam result already exists for this student, course, module, and intake
        $existingResult = ExamResult::where('student_id', $data[0])
            ->where('course_id', $data[1])
            ->where('module_id', $data[2])
            ->where('intake_id', $data[3])
            ->first();

        if ($existingResult) {
            throw new \Exception("Exam result already exists for Student ID {$data[0]}, Course ID {$data[1]}, Module ID {$data[2]}, Intake ID {$data[3]}");
        }

        // Validate marks (if provided)
        $marks = null;
        if (!empty($data[6])) {
            $marks = (float) $data[6];
            if ($marks < 0 || $marks > 100) {
                throw new \Exception('Marks must be between 0 and 100');
            }
        }

        // Auto-calculate grade if marks provided but grade not provided
        $grade = $data[7] ?? null;
        if ($marks !== null && empty($grade)) {
            $grade = ExamResult::calculateGradeFromMarks($marks);
        }

        // Validate location
        $location = $data[4] ?? 'Moratuwa';
        if (!in_array($location, ['Welisara', 'Moratuwa', 'Peradeniya'])) {
            throw new \Exception('Invalid location. Must be one of: Welisara, Moratuwa, Peradeniya');
        }

        // Validate semester
        $semester = $data[5] ?? 1;
        if (!is_numeric($semester) || $semester < 1 || $semester > 8) {
            throw new \Exception('Semester must be a number between 1 and 8');
        }

        // Create exam result
        return ExamResult::create([
            'student_id' => $data[0],
            'course_id' => $data[1],
            'module_id' => $data[2],
            'intake_id' => $data[3],
            'location' => $location,
            'semester' => (int) $semester,
            'marks' => $marks,
            'grade' => $grade,
            'remarks' => $data[8] ?? null
        ]);
    }

    /**
     * Generate template
     */
    private function generateTemplate($type, $filename, $format)
    {
        $headers = [
            'Content-Type' => $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($type) {
            $file = fopen('php://output', 'w');

            switch ($type) {
                case 'students':
                    fputcsv($file, [
                        'Student ID', 'Full Name', 'Email', 'Phone', 'NIC', 'Gender',
                        'Date of Birth', 'Address', 'Institute Location'
                    ]);
                    // Add sample row
                    fputcsv($file, [
                        '', 'John Doe', 'john@example.com', '+94712345678', '123456789V',
                        'Male', '1990-01-01', '123 Main St, Colombo', 'Moratuwa'
                    ]);
                    break;
                case 'courses':
                    fputcsv($file, [
                        'Course ID', 'Course Name', 'Duration', 'Fee', 'Description'
                    ]);
                    fputcsv($file, [
                        '', 'Computer Science', '3 years', '50000', 'Bachelor of Computer Science'
                    ]);
                    break;
                case 'attendance':
                    fputcsv($file, [
                        'Student ID', 'Course ID', 'Date', 'Status', 'Remarks'
                    ]);
                    fputcsv($file, [
                        '1', '1', '2024-01-15', 'Present', 'Good participation'
                    ]);
                    break;
                case 'exam_results':
                    fputcsv($file, [
                        'Student ID', 'Course ID', 'Module ID', 'Intake ID', 'Location', 'Semester', 'Marks', 'Grade', 'Remarks'
                    ]);
                    fputcsv($file, [
                        '1', '1', '1', '1', 'Moratuwa', '1', '85', 'B', 'Good performance'
                    ]);
                    break;
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get export statistics
     */
    public function getExportStats()
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $stats = [
                'students' => [
                    'total' => Student::count(),
                    'active' => Student::where('status', true)->count(),
                    'by_location' => Student::select('institute_location', DB::raw('count(*) as count'))
                        ->groupBy('institute_location')
                        ->get()
                ],
                'courses' => [
                    'total' => Course::count(),
                    'active' => Course::count() // Assuming all courses are active
                ],
                'attendance' => [
                    'total_records' => Attendance::count(),
                    'present_count' => Attendance::where('status', 'Present')->count(),
                    'absent_count' => Attendance::where('status', 'Absent')->count(),
                    'late_count' => Attendance::where('status', 'Late')->count()
                ],
                'exam_results' => [
                    'total_records' => ExamResult::count(),
                    'average_score' => ExamResult::avg('score'),
                    'highest_score' => ExamResult::max('score'),
                    'lowest_score' => ExamResult::min('score')
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Export stats failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get export statistics.'
            ], 500);
        }
    }
}
