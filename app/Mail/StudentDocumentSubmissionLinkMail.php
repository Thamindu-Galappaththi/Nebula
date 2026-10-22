<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentDocumentSubmissionLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public string $submissionUrl,
        public ?string $expiresAt = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Submit your documents to Nebula',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.student_document_link',
        );
    }
};
