<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\Intake;
use App\Support\ClearanceRequestFilters;
use Illuminate\Http\Request;

class LibraryClearanceController extends Controller
{
    use ClearanceRequestFilters;

    public function index(Request $request)
    {
        return $this->clearancePageResponse($request, ClearanceRequest::TYPE_LIBRARY, 'clearance.library_clearance');
    }

    public function details($id)
    {
        // Here you would typically fetch the student's library clearance details from your database
        // For now, we'll return a simple view with mock data
        $student = (object)[
            'id' => $id,
            'student_number' => '2023000' . $id,
            'name' => 'Student ' . $id,
            'status' => 'Pending',
            'books_borrowed' => [],
            'fines_due' => 0
        ];

        return view('library.clearance.details', [
            'student' => $student,
            'pageTitle' => 'Library Clearance Details'
        ]);
    }

    /**
     * Approve a clearance request
     */
    public function approveClearance(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:clearance_requests,id',
            'remarks' => 'nullable|string|max:500',
            'clearance_slip' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120', // 5MB max
        ]);

        try {
            $clearanceRequest = ClearanceRequest::findOrFail($request->request_id);
            
            if ($clearanceRequest->clearance_type !== ClearanceRequest::TYPE_LIBRARY) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid clearance type for this controller.'
                ], 400);
            }

            // Handle file upload if provided
            $filePath = null;
            if ($request->hasFile('clearance_slip')) {
                $file = $request->file('clearance_slip');
                $fileName = 'library_clearance_' . $clearanceRequest->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('clearance_slips', $fileName, 'public');
            }

            $clearanceRequest->approve(auth()->id(), $request->remarks, $filePath);

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
            'clearance_slip' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120', // 5MB max
        ]);

        try {
            $clearanceRequest = ClearanceRequest::findOrFail($request->request_id);
            
            if ($clearanceRequest->clearance_type !== ClearanceRequest::TYPE_LIBRARY) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid clearance type for this controller.'
                ], 400);
            }

            // Handle file upload if provided
            $filePath = null;
            if ($request->hasFile('clearance_slip')) {
                $file = $request->file('clearance_slip');
                $fileName = 'library_clearance_' . $clearanceRequest->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('clearance_slips', $fileName, 'public');
            }

            $clearanceRequest->reject(auth()->id(), $request->remarks, $filePath);

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
