<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Student;
use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\Intake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Courses;
use App\Models\Semester;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Support\ClearanceRequestFilters;

class ProjectClearanceController extends Controller
{
    use ClearanceRequestFilters;

    public function showProjectClearanceFormManagement(Request $request)
    {
        if (Auth::check() && Auth::user()->status) {
            return view('clearance.project_clearance', $this->clearancePageData($request, ClearanceRequest::TYPE_PROJECT));
        }
        else {
        // Log unauthorized access attempt
            Log::warning('Unauthorized access attempt to project clearance page.');

        // Redirect to login or show an error message
        return redirect()->route('login')->with('error', 'You are not authorized to access this page.');
        }
    }

    public function store(Request $request)
    {
        try {
        $validated = $request->validate([
            'student_id' => 'required|string|max:255',
            'student_name' => 'required|string|max:255',
            'course' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
          //  'name_of_the_book' => 'nullable|string|max:255',
           // 'fine_amount' => 'nullable|numeric',
            'clearance_date' => 'nullable|date',
            //'date_received' => 'nullable|date',
            'is_cleared' => 'required|boolean',
        ]);


        Project::create($validated);

        return redirect()->back()->with('success', 'Student details saved successfully!');
    } catch (\Exception $e) {
        // Handle any unexpected errors and redirect back with an error message
        return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function getStudentDetails(Request $request)
    {
        $studentId = $request->get('student_id');

        // Fetch the student by student_id
        $student = Student::where('student_id', $studentId)->first();

        if ($student) {
            return response()->json([
                'success' => true,
                'name' => $student->name_with_initials, // Adjust this field based on the database
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Student not found.',
        ]);
    }

    public function updateClearance(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|string|max:255', // Ensure the student_id is provided
            'course' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'clearance_date' => 'required|date', // Ensure the date_received is provided and valid
            'is_cleared' => 'required|boolean',
        ]);


        // Find the record for the student
        $record = Project::where('student_id', $validated['student_id'])
        ->where('course', $validated['course'])
        ->where('semester', $validated['semester'])
        ->first();

        if (!$record) {
            return redirect()->back()->with('error', 'Record not found for the specified student and date.');
        }



        // Update the date_received field
        $record->update([
            'clearance_date' => $validated['clearance_date'],
            'is_cleared' => $validated['is_cleared']
    ]);

        return redirect()->back()->with('success', 'Clearance date and and clearance status updated successfully!');
    }


    public function search(Request $request)
    {
        $studentIdProject = $request->get('student_id');
        $projectRecords = Project::where('student_id', $studentIdProject)->get();
        return view('project_clearance', compact('projectRecords', 'studentIdProject'));
    }

    /**
     * Approve a clearance request
     */
    public function approveClearance(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:clearance_requests,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $clearanceRequest = ClearanceRequest::findOrFail($request->request_id);

            if ($clearanceRequest->clearance_type !== ClearanceRequest::TYPE_PROJECT) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid clearance type for this controller.'
                ], 400);
            }

            $clearanceRequest->approve(auth()->id(), $request->remarks);

            return response()->json([
                'success' => true,
                'message' => 'Clearance request approved successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve clearance request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a clearance request
     */
    public function rejectClearance(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:clearance_requests,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $clearanceRequest = ClearanceRequest::findOrFail($request->request_id);

            if ($clearanceRequest->clearance_type !== ClearanceRequest::TYPE_PROJECT) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid clearance type for this controller.'
                ], 400);
            }

            $clearanceRequest->reject(auth()->id(), $request->remarks);

            return response()->json([
                'success' => true,
                'message' => 'Clearance request rejected successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject clearance request: ' . $e->getMessage()
            ], 500);
        }
    }






}
