<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\Intake;
use App\Support\ClearanceRequestFilters;

class PaymentClearanceController extends Controller
{
    use ClearanceRequestFilters;

    public function index(Request $request)
    {
        return $this->clearancePageResponse($request, ClearanceRequest::TYPE_PAYMENT, 'clearance.payment_clearance');
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
            
            if ($clearanceRequest->clearance_type !== ClearanceRequest::TYPE_PAYMENT) {
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
            
            if ($clearanceRequest->clearance_type !== ClearanceRequest::TYPE_PAYMENT) {
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
