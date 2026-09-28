<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\Intake;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HostelManagerDashboardController extends Controller
{
    private function normalizeLocation(?string $location): string
    {
        $location = $location ?? 'Welisara';
        $location = str_replace([
            'Nebula Institute of Technology – ',
            'Nebula Institute of Technology - '
        ], '', $location);

        return trim($location);
    }

    private function applyLocationScope($query)
    {
        // Match clearance pages: filter by clearance type/status only, no location restriction.
        return $query;
    }

    public function showDashboard(Request $request)
    {
        $pendingList = $this->pendingList($request);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('dashboards.partials.hostel_pending_list', compact('pendingList'))->render(),
            ]);
        }

        $pendingCount = $this->hostelQuery()
            ->where('status', ClearanceRequest::STATUS_PENDING)
            ->count();

        $approvedCount = $this->hostelQuery()
            ->where('status', ClearanceRequest::STATUS_APPROVED)
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();

        $rejectedCount = $this->hostelQuery()
            ->where('status', ClearanceRequest::STATUS_REJECTED)
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();

        $recent = $this->hostelQuery()
            ->with(['student', 'course', 'intake'])
            ->whereIn('status', [
                ClearanceRequest::STATUS_APPROVED,
                ClearanceRequest::STATUS_REJECTED,
            ])
            ->orderByRaw('COALESCE(approved_at, updated_at) DESC')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('dashboards.hostel_manager', compact(
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'pendingList',
            'recent'
        ));
    }

    private function pendingList(Request $request)
    {
        return $this->hostelQuery()
            ->with(['student', 'course', 'intake'])
            ->where('status', ClearanceRequest::STATUS_PENDING)
            ->orderByRaw('COALESCE(requested_at, created_at) ASC')
            ->orderBy('id', 'asc')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();
    }

    public function getOverviewMetrics(Request $request)
    {
        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;
        $range = $request->range ?? 'month';
        
        // Get date range based on filter
        list($startDate, $endDate) = $this->getDateRange($range, $year, $month, $request);
        
        // Build query for filtered period
        $filteredQuery = $this->applyLocationScope(
            ClearanceRequest::where('clearance_type', 'hostel')
        );
        
        // Apply date filter based on range
        if ($range === 'custom' && $request->startDate && $request->endDate) {
            $filteredQuery->whereBetween('requested_at', [
                Carbon::parse($request->startDate)->startOfDay(),
                Carbon::parse($request->endDate)->endOfDay()
            ]);
        } elseif ($range === 'month') {
            $filteredQuery->whereYear('requested_at', $year)
                         ->whereMonth('requested_at', $month);
        } elseif ($range === 'week') {
            $filteredQuery->whereBetween('requested_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        } elseif ($range === 'today') {
            $filteredQuery->whereDate('requested_at', now());
        }
        // For 'all' range, no date filter - show all time data
        
        // Get counts for filtered period
        $filteredPending = (clone $filteredQuery)->where('status', 'pending')->count();
        $filteredApproved = (clone $filteredQuery)->where('status', 'approved')->count();
        $filteredRejected = (clone $filteredQuery)->where('status', 'rejected')->count();
        
        // Get previous period for comparison
        $previousPeriodData = $this->getPreviousPeriodData($range, $year, $month, $request);
        
        // Calculate percentage changes
        $pendingChange = $this->calculatePercentageChange($filteredPending, $previousPeriodData['pending']);
        $approvedChange = $this->calculatePercentageChange($filteredApproved, $previousPeriodData['approved']);
        $rejectedChange = $this->calculatePercentageChange($filteredRejected, $previousPeriodData['rejected']);
        
        // Get total pending (all time) for totalPending
        $totalPending = $this->applyLocationScope(
            ClearanceRequest::where('clearance_type', 'hostel')
        )
            ->where('status', 'pending')->count();
        
        // Trends data for chart - based on filtered period
        $trends = $this->getRequestTrends($startDate, $endDate);
        
        // Status distribution for filtered period
        $distribution = [
            'pending' => $filteredPending,
            'approved' => $filteredApproved,
            'rejected' => $filteredRejected
        ];

        return response()->json([
            'totalPending' => $filteredPending,
            'allTimePending' => $totalPending,
            'monthly' => [ // Frontend expects 'monthly' key
                'pending' => $filteredPending,
                'approved' => $filteredApproved,
                'rejected' => $filteredRejected
            ],
            'filtered' => [ // Also provide filtered for compatibility
                'pending' => $filteredPending,
                'approved' => $filteredApproved,
                'rejected' => $filteredRejected
            ],
            'change' => [
                'pending' => $pendingChange >= 0 ? "+{$pendingChange}%" : "{$pendingChange}%",
                'approved' => $approvedChange >= 0 ? "+{$approvedChange}%" : "{$approvedChange}%",
                'rejected' => $rejectedChange >= 0 ? "+{$rejectedChange}%" : "{$rejectedChange}%"
            ],
            'trends' => $trends,
            'distribution' => $distribution
        ]);
    }
    
    public function getAnalytics(Request $request)
    {
        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;
        $range = $request->range ?? 'month';
        
        // Get date range based on filter
        list($startDate, $endDate) = $this->getDateRange($range, $year, $month, $request);
        
        // Build query for filtered period
        $query = $this->applyLocationScope(
            ClearanceRequest::where('clearance_type', 'hostel')
        )
            ->whereBetween('requested_at', [$startDate, $endDate]);
        
        // Average processing time (in hours) - only for approved requests
        $avgProcessingTime = (clone $query)
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, requested_at, approved_at)) as avg_time')
            ->value('avg_time') ?? 0;
            
        // Clearance rate (approval percentage)
        $totalRequests = (clone $query)->count();
        $approvedRequests = (clone $query)->where('status', 'approved')->count();
        
        $clearanceRate = $totalRequests > 0 
            ? round(($approvedRequests / $totalRequests) * 100, 1)
            : 0;
            
        // Active requests (last 7 days) - this is always last 7 days regardless of filter
        $activeRequests = $this->applyLocationScope(
            ClearanceRequest::where('clearance_type', 'hostel')
        )
            ->where('status', 'pending')
            ->where('requested_at', '>=', now()->subDays(7))
            ->count();
            
        // Average completion time (in days) - only for approved requests
        $avgCompletionTime = (clone $query)
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, requested_at, approved_at)) as avg_days')
            ->value('avg_days') ?? 0;

        return response()->json([
            'avg_processing_time' => round($avgProcessingTime, 1),
            'clearance_rate' => $clearanceRate,
            'active_requests' => $activeRequests,
            'avg_completion_time' => round($avgCompletionTime, 1)
        ]);
    }

    public function getActionList(Request $request)
    {
        $perPage = 15;
        $query = $this->applyLocationScope(
            ClearanceRequest::where('clearance_type', 'hostel')
        )
            ->with(['student', 'course', 'intake']);
            
        // Apply filters
        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }
        
        if ($request->intake_id) {
            $query->where('intake_id', $request->intake_id);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        // Bulk filter (for bulk actions panel)
        if ($request->bulk_filter && $request->bulk_filter !== 'all') {
            $query->where('status', $request->bulk_filter);
        }
        
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('student', function($q2) use ($search) {
                    $q2->where('full_name', 'like', "%{$search}%")
                      ->orWhere('student_id', 'like', "%{$search}%");
                })
                ->orWhereHas('course', function($q2) use ($search) {
                    $q2->where('course_name', 'like', "%{$search}%");
                })
                ->orWhereHas('intake', function($q2) use ($search) {
                    $q2->where('batch', 'like', "%{$search}%");
                });
            });
        }
        
        // Apply date range
        if ($request->date_range && $request->date_range !== 'all') {
            $dateRange = $this->getDateRangeForFilter($request->date_range, null, null, $request);
            $query->whereBetween('requested_at', $dateRange);
        }
        
        // Apply sorting
        switch ($request->sort) {
            case 'oldest':
                $query->orderBy('requested_at', 'asc');
                break;
            case 'name_asc':
                $query->join('students', 'clearance_requests.student_id', '=', 'students.id')
                      ->orderBy('students.full_name', 'asc')
                      ->select('clearance_requests.*');
                break;
            case 'name_desc':
                $query->join('students', 'clearance_requests.student_id', '=', 'students.id')
                      ->orderBy('students.full_name', 'desc')
                      ->select('clearance_requests.*');
                break;
            default: // 'newest'
                $query->orderBy('requested_at', 'desc');
        }
        
        $requests = $query->paginate($perPage);
        
        // Add days pending for each request
        $requests->getCollection()->transform(function ($request) {
            $request->days_pending = $request->status === 'pending' 
                ? Carbon::parse($request->requested_at)->diffInDays(now())
                : null;
                
            $request->processing_time = $request->approved_at 
                ? Carbon::parse($request->requested_at)->diffInHours(Carbon::parse($request->approved_at)) . 'h'
                : null;
                
            return $request;
        });

        return response()->json($requests);
    }

    public function getRecentHostelClearances()
    {
        $requests = $this->hostelQuery()
            ->with(['student', 'course'])
            ->whereIn('status', [
                ClearanceRequest::STATUS_APPROVED,
                ClearanceRequest::STATUS_REJECTED,
            ])
            ->orderByRaw('COALESCE(approved_at, updated_at) DESC')
            ->orderByDesc('id')
            ->take(10)
            ->get()
            ->map(function ($request) {
                $request->processing_time = $request->approved_at
                    ? Carbon::parse($request->requested_at)->diffInHours(Carbon::parse($request->approved_at)) . 'h'
                    : 'N/A';

                return $request;
            });

        return response()->json($requests);
    }

    public function filterRequests(Request $request)
    {
        $query = $this->applyLocationScope(
            ClearanceRequest::where('clearance_type', 'hostel')
        )
            ->with(['student', 'course', 'intake']);

        if ($request->course_id) $query->where('course_id', $request->course_id);
        if ($request->intake_id) $query->where('intake_id', $request->intake_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->year) $query->whereYear('requested_at', $request->year);
        if ($request->month) $query->whereMonth('requested_at', $request->month);

        if ($request->sort === 'newest') {
            $query->orderBy('requested_at', 'desc');
        } else {
            $query->orderBy('requested_at', 'asc');
        }

        $requests = $query->get()->map(function ($request) {
            $request->days_pending = $request->status === 'pending' 
                ? Carbon::parse($request->requested_at)->diffInDays(now())
                : null;
            return $request;
        });

        return response()->json($requests);
    }

    public function searchRequests(Request $request)
    {
        $requests = $this->applyLocationScope(
            ClearanceRequest::where('clearance_type', 'hostel')
        )
            ->with(['student', 'course', 'intake'])
            ->whereHas('student', function($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('student_id', 'like', "%{$request->search}%");
            })
            ->get()
            ->map(function ($request) {
                $request->days_pending = $request->status === 'pending' 
                    ? Carbon::parse($request->requested_at)->diffInDays(now())
                    : null;
                return $request;
            });

        return response()->json($requests);
    }

    public function listByStatus(Request $request)
    {
        $query = $this->applyLocationScope(
            ClearanceRequest::where('clearance_type', 'hostel')
        )
            ->where('status', $request->status)
            ->with(['student', 'course', 'intake']);
            
        // Apply date range filter if provided
        if ($request->year || $request->month || $request->range) {
            $year = $request->year ?? now()->year;
            $month = $request->month ?? now()->month;
            $range = $request->range ?? 'month';
            
            list($startDate, $endDate) = $this->getDateRange($range, $year, $month, $request);
            $query->whereBetween('requested_at', [$startDate, $endDate]);
        }
            
        $requests = $query->orderBy('requested_at', 'desc')
            ->paginate(10)
            ->through(function ($request) {
                $request->formatted_date = Carbon::parse($request->requested_at)->format('Y-m-d H:i');
                return $request;
            });

        return response()->json($requests);
    }
    
    public function updateRequestStatus(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:clearance_requests,id',
            'status' => 'required|in:pending,approved,rejected',
            'remarks' => 'nullable|string|max:500'
        ]);
        
        $clearanceRequest = ClearanceRequest::findOrFail($request->request_id);
        
        // Ensure it's a hostel clearance
        if ($clearanceRequest->clearance_type !== 'hostel') {
            return response()->json(['error' => 'Invalid clearance type'], 400);
        }
        
        $oldStatus = $clearanceRequest->status;
        $clearanceRequest->status = $request->status;
        $clearanceRequest->remarks = $request->remarks;
        
        if ($request->status === 'approved') {
            $clearanceRequest->approved_by = Auth::id();
            $clearanceRequest->approved_at = now();
        } else {
            $clearanceRequest->approved_by = null;
            $clearanceRequest->approved_at = null;
        }
        
        $clearanceRequest->save();
        
        // Log the status change
        activity()
            ->performedOn($clearanceRequest)
            ->causedBy(Auth::user())
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'remarks' => $request->remarks
            ])
            ->log('status_updated');
        
        return response()->json([
            'success' => true,
            'message' => 'Request status updated successfully'
        ]);
    }
    
    public function bulkUpdateRequests(Request $request)
    {
        $request->validate([
            'request_ids' => 'required|array',
            'request_ids.*' => 'exists:clearance_requests,id',
            'status' => 'required|in:pending,approved,rejected',
            'remarks' => 'nullable|string|max:500'
        ]);
        
        $updatedCount = 0;
        $failedCount = 0;
        
        foreach ($request->request_ids as $requestId) {
            try {
                $clearanceRequest = ClearanceRequest::find($requestId);
                
                // Ensure it's a hostel clearance
                if ($clearanceRequest->clearance_type !== 'hostel') {
                    $failedCount++;
                    continue;
                }
                
                $oldStatus = $clearanceRequest->status;
                $clearanceRequest->status = $request->status;
                $clearanceRequest->remarks = $request->remarks;
                
                if ($request->status === 'approved') {
                    $clearanceRequest->approved_by = Auth::id();
                    $clearanceRequest->approved_at = now();
                } else {
                    $clearanceRequest->approved_by = null;
                    $clearanceRequest->approved_at = null;
                }
                
                $clearanceRequest->save();
                
                // Log the status change
                activity()
                    ->performedOn($clearanceRequest)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'old_status' => $oldStatus,
                        'new_status' => $request->status,
                        'remarks' => $request->remarks,
                        'bulk_action' => true
                    ])
                    ->log('status_updated');
                
                $updatedCount++;
            } catch (\Exception $e) {
                $failedCount++;
            }
        }
        
        return response()->json([
            'success' => true,
            'updated' => $updatedCount,
            'failed' => $failedCount,
            'message' => "Updated {$updatedCount} request(s)" . ($failedCount > 0 ? ", {$failedCount} failed" : "")
        ]);
    }
    
    public function exportData(Request $request)
    {
        $query = $this->applyLocationScope(
            ClearanceRequest::where('clearance_type', 'hostel')
        )
            ->with(['student', 'course', 'intake']);
        
        // Apply filters
        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }
        
        if ($request->intake_id) {
            $query->where('intake_id', $request->intake_id);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%");
            });
        }
        
        // Apply date range
        if ($request->date_range && $request->date_range !== 'all') {
            $dateRange = $this->getDateRangeForFilter($request->date_range, null, null, $request);
            $query->whereBetween('requested_at', $dateRange);
        }
        
        $requests = $query->orderBy('requested_at', 'desc')->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=hostel_clearances_' . date('Y-m-d') . '.csv',
        ];
        
        $callback = function() use ($requests) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, [
                'Student ID',
                'Student Name',
                'Course',
                'Intake',
                'Status',
                'Requested At',
                'Updated At',
                'Processing Time (hours)',
                'Approved By',
                'Remarks'
            ]);
            
            // Data
            foreach ($requests as $request) {
                $processingTime = $request->approved_at 
                    ? Carbon::parse($request->requested_at)->diffInHours(Carbon::parse($request->approved_at))
                    : '';
                    
                fputcsv($file, [
                    $request->student->student_id ?? '',
                    $request->student->full_name ?? '',
                    $request->course->course_name ?? '',
                    $request->intake->intake_name ?? '',
                    ucfirst($request->status),
                    $request->requested_at->format('Y-m-d H:i:s'),
                    $request->updated_at->format('Y-m-d H:i:s'),
                    $processingTime,
                    $request->approvedBy->name ?? '',
                    $request->remarks ?? ''
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    private function hostelQuery()
    {
        return $this->applyLocationScope(
            ClearanceRequest::query()->where('clearance_type', ClearanceRequest::TYPE_HOSTEL)
        );
    }

    private function getDateRange($range, $year, $month, $request = null)
    {
        switch ($range) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay()];
            case 'week':
                return [now()->startOfWeek(), now()->endOfWeek()];
            case 'month':
                return [
                    Carbon::create($year, $month, 1)->startOfMonth(),
                    Carbon::create($year, $month, 1)->endOfMonth()
                ];
            case 'custom':
                if ($request && $request->startDate && $request->endDate) {
                    return [
                        Carbon::parse($request->startDate)->startOfDay(),
                        Carbon::parse($request->endDate)->endOfDay()
                    ];
                }
                return [now()->startOfMonth(), now()->endOfMonth()];
            default: // 'all' or any other
                return [
                    Carbon::create(2020, 1, 1)->startOfDay(), // From beginning
                    now()->endOfDay()
                ];
        }
    }
    
    private function getDateRangeForFilter($range, $year = null, $month = null, $request = null)
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        
        return $this->getDateRange($range, $year, $month, $request);
    }
    
    private function getPreviousPeriodData($range, $year, $month, $request)
    {
        $query = $this->applyLocationScope(
            ClearanceRequest::where('clearance_type', 'hostel')
        );
        
        switch ($range) {
            case 'month':
                $previousMonth = Carbon::create($year, $month, 1)->subMonth();
                $query->whereYear('requested_at', $previousMonth->year)
                     ->whereMonth('requested_at', $previousMonth->month);
                break;
                
            case 'week':
                $previousWeek = now()->subWeek();
                $query->whereBetween('requested_at', [
                    $previousWeek->startOfWeek(),
                    $previousWeek->endOfWeek()
                ]);
                break;
                
            case 'today':
                $query->whereDate('requested_at', now()->subDay());
                break;
                
            case 'custom':
                if ($request->startDate && $request->endDate) {
                    $start = Carbon::parse($request->startDate);
                    $end = Carbon::parse($request->endDate);
                    $daysDiff = $end->diffInDays($start);
                    
                    $query->whereBetween('requested_at', [
                        $start->subDays($daysDiff + 1),
                        $end->subDays($daysDiff + 1)
                    ]);
                }
                break;
                
            default: // 'all' or yearly comparison
                $previousYear = $year - 1;
                $query->whereYear('requested_at', $previousYear);
                break;
        }
        
        return [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count()
        ];
    }
    
    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        $change = (($current - $previous) / $previous) * 100;
        return round($change, 1);
    }
    
    private function getRequestTrends($startDate, $endDate)
    {
        $daysDiff = $startDate->diffInDays($endDate);
        
        if ($daysDiff <= 31) {
            // Daily trends
            $trends = $this->applyLocationScope(
                ClearanceRequest::where('clearance_type', 'hostel')
            )
                ->whereBetween('requested_at', [$startDate, $endDate])
                ->selectRaw('DATE(requested_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
                
            $labels = [];
            $data = [];
            
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');
                $count = $trends->firstWhere('date', $dateStr)->count ?? 0;
                
                $labels[] = $currentDate->format('M d');
                $data[] = $count;
                
                $currentDate->addDay();
            }
        } else {
            // Monthly trends
            $trends = $this->applyLocationScope(
                ClearanceRequest::where('clearance_type', 'hostel')
            )
                ->whereBetween('requested_at', [$startDate, $endDate])
                ->selectRaw('YEAR(requested_at) as year, MONTH(requested_at) as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
                
            $labels = [];
            $data = [];
            
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                $year = $currentDate->year;
                $month = $currentDate->month;
                
                $count = $trends->first(function ($item) use ($year, $month) {
                    return $item->year == $year && $item->month == $month;
                })->count ?? 0;
                
                $labels[] = $currentDate->format('M Y');
                $data[] = $count;
                
                $currentDate->addMonth();
            }
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
}