<?php

namespace App\Notifications;

use App\Models\StudentDocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentDocumentsSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public StudentDocumentRequest $request)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $student = $this->request->student;

        return [
            'request_id' => $this->request->id,
            'student_id' => $student?->student_id,
            'student_name' => $student?->full_name,
            'id_value' => $student?->id_value,
            'message' => ($student?->full_name ?: 'A student') . ' submitted documents for review.',
            'url' => route('student.documents.show', $this->request->id),
            'submitted_at' => optional($this->request->submitted_at)?->toDateTimeString(),
            'retention_expires_at' => optional($this->request->retention_expires_at)?->toDateTimeString(),
        ];
    }
}
