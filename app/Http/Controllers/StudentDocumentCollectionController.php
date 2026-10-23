<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentDocumentFile;
use App\Models\StudentDocumentRequest;
use App\Notifications\StudentDocumentsSubmittedNotification;
use App\Services\StudentDocumentCollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class StudentDocumentCollectionController extends Controller
{
    public function __construct(private StudentDocumentCollectionService $documents)
    {
    }

    public function index(Request $request)
    {
        $status = $this->normalizedStatus($request->query('status'));
        $requests = $this->filteredRequests($status);
        $counts = $this->statusCounts();

        if ($request->ajax()) {
            return view('student_management.partials.document_collection_list', [
                'requests' => $requests,
                'status' => $status,
                'counts' => $counts,
            ]);
        }

        return view('student_management.document_collection', [
            'requests' => $requests,
            'status' => $status,
            'counts' => $counts,
            'tabs' => $this->statusTabs(),
            'retentionDays' => (int) config('student_documents.retention_days', 7),
            'linkTtlDays' => (int) config('student_documents.link_ttl_days', 14),
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        return response()->json([
            'success' => true,
            'students' => $this->documents->searchStudents($request->input('q')),
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,student_id'],
            'force_new' => ['sometimes', 'boolean'],
        ]);

        $student = Student::findOrFail($request->input('student_id'));
        $result = $this->documents->generateLink($student, Auth::user(), $request->boolean('force_new'));
        $ok = !($result['blocked'] ?? false) && !($result['needs_replace'] ?? false);

        return response()->json([
            'success' => $ok,
            'created' => $result['created'],
            'reused' => $result['reused'] ?? false,
            'replaced' => $result['replaced'] ?? false,
            'blocked' => $result['blocked'] ?? false,
            'needs_replace' => $result['needs_replace'] ?? false,
            'message' => $result['message'],
            'url' => $result['url'],
            'share' => $result['share'],
            'can_email' => filled($student->email),
            'request_id' => $result['request']?->id,
            'expires_at' => $result['request']?->link_expires_at
                ? $result['request']->link_expires_at->copy()->timezone('Asia/Colombo')->format('Y-m-d H:i')
                : null,
            'student' => [
                'student_id' => $student->student_id,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'phone' => $student->whatsapp_phone ?: $student->mobile_phone,
            ],
        ], $ok ? 200 : 409);
    }

    public function email(StudentDocumentRequest $documentRequest)
    {
        $documentRequest->load('student');

        try {
            $this->documents->sendLinkEmail($documentRequest);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'The email could not be sent. Copy the link and share it another way.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email sent to ' . $documentRequest->student->email . '.',
        ]);
    }

    public function show(StudentDocumentRequest $documentRequest)
    {
        $documentRequest->load(['student', 'creator', 'reviewer', 'files']);
        $submissionUrl = $this->documents->urlForRequest($documentRequest);
        $student = $documentRequest->student;

        return view('student_management.document_collection_show', [
            'documentRequest' => $documentRequest,
            'types' => config('student_documents.document_types', []),
            'submissionUrl' => $submissionUrl,
            'share' => $submissionUrl && $student ? $this->documents->shareLinks($student, $submissionUrl) : null,
        ]);
    }

    public function file(Request $request, StudentDocumentFile $documentFile)
    {
        $documentFile->load('request');
        $owner = $documentFile->request;

        if (!$owner || !$owner->documentsAreAvailable() || !$documentFile->existsOnDisk()) {
            abort(404, 'This document is no longer available.');
        }

        $inline = $request->boolean('inline');
        $filename = $documentFile->original_filename ?: 'document';
        $headers = [
            'Content-Type' => $documentFile->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $absolutePath = Storage::disk(config('student_documents.disk'))->path($documentFile->stored_path);

        if ($inline) {
            return response()->file($absolutePath, $headers);
        }

        return response()->download($absolutePath, $filename, $headers);
    }

    public function review(StudentDocumentRequest $documentRequest)
    {
        try {
            $this->documents->markReviewed($documentRequest, Auth::user());
        } catch (RuntimeException $e) {
            return redirect()
                ->route('student.documents.show', $documentRequest)
                ->with('error', $e->getMessage());
        }

        $this->markRelatedNotificationsRead($documentRequest);

        return redirect()
            ->route('student.documents.show', $documentRequest)
            ->with('success', 'Submission marked as reviewed.');
    }

    public function notifications()
    {
        $user = Auth::user();
        $items = $user->notifications()
            ->where('type', StudentDocumentsSubmittedNotification::class)
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'message' => $data['message'] ?? 'A student submitted documents.',
                    'url' => $data['url'] ?? route('student.documents.index'),
                    'read' => $notification->read_at !== null,
                    'created_at' => optional($notification->created_at)?->timezone('Asia/Colombo')->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'unread' => $user->unreadNotifications()
                ->where('type', StudentDocumentsSubmittedNotification::class)
                ->count(),
            'notifications' => $items,
        ]);
    }

    public function markNotificationRead(string $notification)
    {
        $item = Auth::user()->notifications()->where('id', $notification)->firstOrFail();
        $item->markAsRead();

        return response()->json(['success' => true]);
    }

    private function markRelatedNotificationsRead(StudentDocumentRequest $documentRequest): void
    {
        Auth::user()->unreadNotifications
            ->where('type', StudentDocumentsSubmittedNotification::class)
            ->filter(fn ($notification) => (int) ($notification->data['request_id'] ?? 0) === (int) $documentRequest->id)
            ->each->markAsRead();
    }

    private function statusTabs(): array
    {
        return [
            'submitted' => 'Submitted',
            'pending' => 'Awaiting upload',
            'reviewed' => 'Reviewed',
            'expired' => 'Expired',
            'purged' => 'Deleted',
            'all' => 'All',
        ];
    }

    private function normalizedStatus(?string $status): string
    {
        $allowed = array_keys($this->statusTabs());

        return in_array($status, $allowed, true) ? $status : 'submitted';
    }

    private function filteredRequests(string $status)
    {
        $query = StudentDocumentRequest::query()
            ->with(['student', 'creator', 'reviewer'])
            ->latest('id');

        if ($status === 'reviewed') {
            $query->where('status', StudentDocumentRequest::STATUS_SUBMITTED)->whereNotNull('reviewed_at');
        } elseif ($status === 'submitted') {
            $query->where('status', StudentDocumentRequest::STATUS_SUBMITTED);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query->paginate(10)->withQueryString();
    }

    private function statusCounts(): array
    {
        return [
            'all' => StudentDocumentRequest::count(),
            'pending' => StudentDocumentRequest::where('status', StudentDocumentRequest::STATUS_PENDING)->count(),
            'submitted' => StudentDocumentRequest::where('status', StudentDocumentRequest::STATUS_SUBMITTED)->count(),
            'reviewed' => StudentDocumentRequest::where('status', StudentDocumentRequest::STATUS_SUBMITTED)->whereNotNull('reviewed_at')->count(),
            'expired' => StudentDocumentRequest::where('status', StudentDocumentRequest::STATUS_EXPIRED)->count(),
            'purged' => StudentDocumentRequest::where('status', StudentDocumentRequest::STATUS_PURGED)->count(),
        ];
    }
}
