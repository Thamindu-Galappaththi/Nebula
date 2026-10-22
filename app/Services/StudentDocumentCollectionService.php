<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentDocumentFile;
use App\Models\StudentDocumentRequest;
use App\Models\User;
use App\Notifications\StudentDocumentsSubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class StudentDocumentCollectionService
{
    public function searchStudents(string $query): Collection
    {
        $term = trim($query);
        if ($term === '' || mb_strlen($term) < 2) {
            return collect();
        }

        return Student::query()
            ->where(function ($builder) use ($term) {
                $builder->where('full_name', 'like', '%' . $term . '%')
                    ->orWhere('name_with_initials', 'like', '%' . $term . '%')
                    ->orWhere('id_value', 'like', '%' . $term . '%')
                    ->orWhere('email', 'like', '%' . $term . '%');

                if (ctype_digit($term)) {
                    $builder->orWhere('student_id', (int) $term);
                }
            })
            ->orderBy('full_name')
            ->limit(20)
            ->get()
            ->map(function (Student $student) {
                $openRequest = $this->latestOpenRequest($student);

                return [
                    'student_id' => $student->student_id,
                    'full_name' => $student->full_name,
                    'name_with_initials' => $student->name_with_initials,
                    'id_type' => $student->id_type,
                    'id_value' => $student->id_value,
                    'email' => $student->email,
                    'mobile_phone' => $student->mobile_phone,
                    'whatsapp_phone' => $student->whatsapp_phone,
                    'institute_location' => $student->institute_location,
                    'existing_status' => $openRequest?->status,
                    'existing_request_id' => $openRequest?->id,
                ];
            });
    }

    public function generateLink(Student $student, User $staff): array
    {
        $existingSubmitted = StudentDocumentRequest::query()
            ->where('student_id', $student->student_id)
            ->where('status', StudentDocumentRequest::STATUS_SUBMITTED)
            ->where('document_deletion_status', StudentDocumentRequest::DELETION_RETAINED)
            ->latest('id')
            ->first();

        if ($existingSubmitted) {
            return [
                'created' => false,
                'message' => 'This student already has documents waiting for review. A new link can be generated after the retention period ends.',
                'request' => $existingSubmitted,
                'url' => null,
                'share' => null,
            ];
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $request = StudentDocumentRequest::query()
            ->where('student_id', $student->student_id)
            ->where('status', StudentDocumentRequest::STATUS_PENDING)
            ->latest('id')
            ->first();

        $payload = [
            'token_hash' => $tokenHash,
            'student_id' => $student->student_id,
            'status' => StudentDocumentRequest::STATUS_PENDING,
            'notification_status' => StudentDocumentRequest::NOTIFICATION_PENDING,
            'document_deletion_status' => StudentDocumentRequest::DELETION_RETAINED,
            'link_expires_at' => now()->addDays((int) config('student_documents.link_ttl_days', 14)),
            'submitted_at' => null,
            'retention_expires_at' => null,
            'notified_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'purged_at' => null,
            'submitted_full_name' => null,
            'submitted_id_value' => null,
            'submitted_email' => null,
            'submitted_phone' => null,
            'submitted_notes' => null,
            'submitted_file_count' => 0,
        ];

        if ($request) {
            $request->fill($payload);
            $request->created_by = $staff->getKey();
            $request->save();
        } else {
            $request = new StudentDocumentRequest($payload);
            $request->created_by = $staff->getKey();
            $request->save();
        }

        $url = route('student.documents.portal', ['token' => $plainToken]);

        return [
            'created' => true,
            'message' => 'Unique submission link generated.',
            'request' => $request->fresh(['student', 'creator']),
            'url' => $url,
            'share' => $this->shareLinks($student, $url),
        ];
    }

    public function findByToken(string $token): ?StudentDocumentRequest
    {
        $token = trim($token);
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        return StudentDocumentRequest::query()
            ->with(['student', 'files'])
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    public function submit(StudentDocumentRequest $request, array $details, array $files): StudentDocumentRequest
    {
        if (!$request->isAccessibleToStudent()) {
            throw new RuntimeException('This submission link is no longer available.');
        }

        $student = $request->student;
        if (!$student) {
            throw new RuntimeException('The student record for this link could not be found.');
        }

        $submittedId = $this->normalizeIdValue($details['id_value'] ?? '');
        $expectedId = $this->normalizeIdValue((string) $student->id_value);
        if ($submittedId === '' || $submittedId !== $expectedId) {
            throw new RuntimeException('The identification number does not match this student record.');
        }

        $disk = config('student_documents.disk');

        return DB::transaction(function () use ($request, $details, $files, $disk) {
            foreach ($request->files()->get() as $existing) {
                Storage::disk($disk)->delete($existing->stored_path);
                $existing->delete();
            }

            $storedCount = 0;
            foreach ($files as $type => $uploaded) {
                $uploads = $uploaded instanceof UploadedFile ? [$uploaded] : (array) $uploaded;
                foreach ($uploads as $index => $file) {
                    if (!$file instanceof UploadedFile) {
                        continue;
                    }
                    $this->storeUploadedFile($request, $type, $file, $index);
                    $storedCount++;
                }
            }

            $request->status = StudentDocumentRequest::STATUS_SUBMITTED;
            $request->submitted_at = now();
            $request->retention_expires_at = now()->addDays((int) config('student_documents.retention_days', 7));
            $request->submitted_full_name = $details['full_name'] ?? null;
            $request->submitted_id_value = $details['id_value'] ?? null;
            $request->submitted_email = $details['email'] ?? null;
            $request->submitted_phone = $details['phone'] ?? null;
            $request->submitted_notes = $details['notes'] ?? null;
            $request->submitted_file_count = $storedCount;
            $request->document_deletion_status = StudentDocumentRequest::DELETION_RETAINED;
            $request->save();

            return $request->fresh(['student', 'files', 'creator']);
        });
    }

    public function notifyStaff(StudentDocumentRequest $request): void
    {
        try {
            $recipients = $this->notificationRecipients($request);
            if ($recipients->isEmpty()) {
                $request->notification_status = StudentDocumentRequest::NOTIFICATION_FAILED;
                $request->save();
                return;
            }

            Notification::send($recipients, new StudentDocumentsSubmittedNotification($request));

            $request->notification_status = StudentDocumentRequest::NOTIFICATION_SENT;
            $request->notified_at = now();
            $request->save();
        } catch (\Throwable $e) {
            Log::warning('Failed to send student document submission notifications', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
            $request->notification_status = StudentDocumentRequest::NOTIFICATION_FAILED;
            $request->save();
        }
    }

    public function markReviewed(StudentDocumentRequest $request, User $staff): StudentDocumentRequest
    {
        if (!$request->documentsAreAvailable()) {
            throw new RuntimeException('These documents are no longer available for review.');
        }

        $request->reviewed_at = now();
        $request->reviewed_by = $staff->getKey();
        $request->save();

        return $request->fresh(['student', 'files', 'creator', 'reviewer']);
    }

    public function expireUnusedLinks(): int
    {
        $expired = StudentDocumentRequest::query()
            ->where('status', StudentDocumentRequest::STATUS_PENDING)
            ->where('link_expires_at', '<', now())
            ->get();

        foreach ($expired as $request) {
            $request->status = StudentDocumentRequest::STATUS_EXPIRED;
            $request->save();
        }

        return $expired->count();
    }

    public function purgeExpiredSubmissions(): int
    {
        $due = StudentDocumentRequest::query()
            ->with('files')
            ->where('status', StudentDocumentRequest::STATUS_SUBMITTED)
            ->where('document_deletion_status', StudentDocumentRequest::DELETION_RETAINED)
            ->where('retention_expires_at', '<=', now())
            ->get();

        foreach ($due as $request) {
            $this->purgeRequest($request);
        }

        return $due->count();
    }

    public function purgeRequest(StudentDocumentRequest $request): void
    {
        $disk = config('student_documents.disk');
        $directory = (string) $request->id;

        foreach ($request->files as $file) {
            if ($file->stored_path) {
                Storage::disk($disk)->delete($file->stored_path);
            }
        }

        if (Storage::disk($disk)->exists($directory)) {
            Storage::disk($disk)->deleteDirectory($directory);
        }

        $request->files()->delete();
        $request->status = StudentDocumentRequest::STATUS_PURGED;
        $request->document_deletion_status = StudentDocumentRequest::DELETION_PURGED;
        $request->purged_at = now();
        $request->token_hash = null;
        $request->submitted_full_name = null;
        $request->submitted_id_value = null;
        $request->submitted_email = null;
        $request->submitted_phone = null;
        $request->submitted_notes = null;
        $request->save();
    }

    public function shareLinks(Student $student, string $url): array
    {
        $name = $student->full_name ?: 'Student';
        $message = "Hello {$name}, please use this secure Nebula link to submit your documents. The link is unique to you: {$url}";
        $phone = $this->whatsappNumber($student->whatsapp_phone ?: $student->mobile_phone);
        $email = $student->email;

        return [
            'copy' => $url,
            'whatsapp' => $phone
                ? 'https://wa.me/' . $phone . '?text=' . rawurlencode($message)
                : 'https://wa.me/?text=' . rawurlencode($message),
            'email' => 'mailto:' . ($email ?: '') . '?subject=' . rawurlencode('Nebula document submission') . '&body=' . rawurlencode($message),
        ];
    }

    public function campusKey(?string $value): string
    {
        $text = trim((string) $value);
        foreach (['Welisara', 'Moratuwa', 'Peradeniya'] as $campus) {
            if ($text !== '' && stripos($text, $campus) !== false) {
                return $campus;
            }
        }

        return $text;
    }

    private function storeUploadedFile(StudentDocumentRequest $request, string $type, UploadedFile $file, int $index): StudentDocumentFile
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $filename = $type . '_' . ($index + 1) . '_' . Str::random(16) . '.' . $extension;
        $path = $request->id . '/' . $filename;
        $stored = Storage::disk(config('student_documents.disk'))->putFileAs((string) $request->id, $file, $filename);

        if ($stored === false) {
            throw new RuntimeException('The document could not be stored securely.');
        }

        return StudentDocumentFile::create([
            'document_request_id' => $request->id,
            'document_type' => $type,
            'original_filename' => $this->safeOriginalName($file->getClientOriginalName()),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize() ?: 0,
            'checksum' => hash_file('sha256', $file->getRealPath() ?: $file->getPathname()),
        ]);
    }

    private function notificationRecipients(StudentDocumentRequest $request): Collection
    {
        $roles = config('student_documents.authorized_roles', []);
        $campus = $this->campusKey($request->student?->institute_location);

        return User::query()
            ->where('status', '1')
            ->get()
            ->filter(function (User $user) use ($roles, $campus, $request) {
                if (!$user->hasAnyRole($roles)) {
                    return false;
                }

                if ((int) $user->getKey() === (int) $request->created_by) {
                    return true;
                }

                if ($campus === '') {
                    return true;
                }

                return $this->campusKey($user->user_location) === $campus;
            })
            ->values();
    }

    private function latestOpenRequest(Student $student): ?StudentDocumentRequest
    {
        return StudentDocumentRequest::query()
            ->where('student_id', $student->student_id)
            ->whereIn('status', [
                StudentDocumentRequest::STATUS_PENDING,
                StudentDocumentRequest::STATUS_SUBMITTED,
            ])
            ->latest('id')
            ->first();
    }

    private function normalizeIdValue(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($value)) ?? '');
    }

    private function safeOriginalName(?string $name): string
    {
        $name = basename((string) $name);
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'document';

        return Str::limit($name, 120, '');
    }

    private function whatsappNumber(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '94' . substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            $digits = '94' . $digits;
        }

        return $digits ?: null;
    }
}
