<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'action' => 'nullable|in:created,updated,deleted,login,logout,login_failed,submitted',
            'user_role' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|in:10,25,50',
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $logs = AuditLog::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('summary', 'like', '%' . $search . '%')
                        ->orWhere('user_name', 'like', '%' . $search . '%')
                        ->orWhere('user_email', 'like', '%' . $search . '%')
                        ->orWhere('item_label', 'like', '%' . $search . '%')
                        ->orWhere('path', 'like', '%' . $search . '%')
                        ->orWhere('ip_address', 'like', '%' . $search . '%')
                        ->orWhere('location', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($filters['user_role'] ?? null, fn ($query, $role) => $query->where('user_role', $role))
            ->when($filters['date_from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $roles = AuditLog::query()
            ->whereNotNull('user_role')
            ->where('user_role', '!=', '')
            ->distinct()
            ->orderBy('user_role')
            ->pluck('user_role');

        $data = [
            'logs' => $logs,
            'filters' => $filters,
            'perPage' => $perPage,
            'roles' => $roles,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('audit.partials.rows', $data)->render(),
                'pagination' => view('audit.partials.pagination', $data)->render(),
            ]);
        }

        return view('audit.index', $data);
    }

    public function destroy($id)
    {
        $log = AuditLog::find($id);
        if (!$log) {
            return response()->json(['success' => false, 'message' => 'Audit entry not found.'], 404);
        }

        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Audit entry deleted.',
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $deleted = AuditLog::whereIn('id', $data['ids'])->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0
                ? "Deleted {$deleted} audit " . ($deleted === 1 ? 'entry' : 'entries') . '.'
                : 'No matching audit entries were deleted.',
            'deleted' => $deleted,
        ]);
    }
}
