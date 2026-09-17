<?php

namespace App\Http\Controllers;

use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\Intake;
use App\Models\Student;
use App\Models\StudentClearance;
use App\Support\ClearanceRequestFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentClearanceFormManagementController extends Controller
{
    use ClearanceRequestFilters;

    // Method to show the student clearance form management view
    public function showStudentClearanceFormManagement(Request $request)
    {
        return $this->clearancePageResponse($request, ClearanceRequest::TYPE_LIBRARY, 'clearance.library_clearance');
    }

    public function store(Request $request)
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'student_id' => 'required|string|max:255',
                'student_name' => 'required|string|max:255',
                'name_of_the_book' => 'nullable|string|max:255',
                'fine_amount' => 'nullable|numeric',
                'date_taken' => 'required|date',
                'is_cleared' => 'required|boolean',
            ]);

            // Create a new clearance record
            StudentClearance::create($validated);

            // Redirect back with a success message
            return redirect()->back()->with('success', 'Student details saved successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function getStudentDetails(Request $request)
    {
        $studentId = $request->get('student_id');

        // Fetch the student by student_id
        $student = Student::where('student_id', $studentId)->first();

        if ($student) {
            return redirect()->route('student.profile', ['studentId' => $student->student_id]);
        }

        return redirect()->back()->with('error', 'Student not found.');
    }

    public function updateReceivedDate(Request $request)
    {
        try {
            $validated = $request->validate([
                'student_id' => 'required|string|max:255',
                'name_of_the_book' => 'required|string|max:255',
                'date_received' => 'required|date',
                'is_cleared' => 'required|boolean',
            ]);

            $record = StudentClearance::where('student_id', $validated['student_id'])->first();

            if (!$record) {
                return redirect()->back()->with('error', 'Record not found for the specified student and book.');
            }

            $record->update([
                'date_received' => $validated['date_received'],
                'is_cleared' => $validated['is_cleared'],
            ]);

            return redirect()->back()->with('success', 'Received date and clearance status updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function search(Request $request)
    {
        return $this->clearancePageResponse($request, ClearanceRequest::TYPE_LIBRARY, 'clearance.library_clearance');
    }

    public function getCourseData($courseID)
    {
        $data = Course::find($courseID);

        if (!$data) {
            return response()->json(['years' => null, 'semesters' => null]);
        }

        $jsonData = json_decode($data->duration, true);
        $years = is_array($jsonData) && isset($jsonData['years']) ? $jsonData['years'] : $data->duration;
        $semesters = $data->no_of_semesters ?? null;

        return response()->json([
            'years' => $years,
            'semesters' => $semesters,
        ]);
    }
}
