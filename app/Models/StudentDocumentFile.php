<?php

namespace App\Models;

use App\Traits\UserTracking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StudentDocumentFile extends Model
{
    use UserTracking;

    protected $fillable = [
        'document_request_id',
        'document_type',
        'original_filename',
        'stored_path',
        'mime_type',
        'size_bytes',
        'checksum',
        'created_by',
        'updated_by',
    ];

    public function request()
    {
        return $this->belongsTo(StudentDocumentRequest::class, 'document_request_id');
    }

    public function absolutePath(): string
    {
        return Storage::disk(config('student_documents.disk'))->path($this->stored_path);
    }

    public function existsOnDisk(): bool
    {
        return Storage::disk(config('student_documents.disk'))->exists($this->stored_path);
    }

    public function isPreviewable(): bool
    {
        return in_array($this->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true)
            || in_array(strtolower(pathinfo($this->original_filename, PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png'], true);
    }
}
