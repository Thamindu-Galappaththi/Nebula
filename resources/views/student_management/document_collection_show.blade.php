@extends('inc.app')

@section('title', 'NEBULA | Document Submission')

@section('content')
@php
    $student = $documentRequest->student;
    $statusLabel = match ($documentRequest->status) {
        'pending' => 'Awaiting upload',
        'submitted' => 'Submitted',
        'expired' => 'Expired',
        'purged' => 'Deleted',
        default => ucfirst((string) $documentRequest->status),
    };
@endphp
<style nonce="{{ $cspNonce }}">
    .doc-show-page { max-width: 1080px; margin: 0 auto; min-width: 0; }
    .doc-show-page [class*="col-"] { min-width: 0; }
    .doc-show-hero,
    .doc-show-card {
        background: #fff;
        border: 0;
        border-radius: 22px;
        box-shadow: 0 14px 40px rgba(27, 44, 91, 0.1);
        overflow: hidden;
    }
    .doc-show-hero {
        background: linear-gradient(135deg, #1f3f9a 0%, #4c6bd8 58%, #7aa0ff 100%);
        color: #fff;
        padding: 1.4rem 1.4rem 1.5rem;
        margin-bottom: 1rem;
    }
    .doc-show-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.7rem;
        margin-bottom: 1.1rem;
    }
    .doc-show-actions .btn,
    .doc-file-actions .btn {
        min-height: 46px;
        padding: 0.65rem 1.15rem;
        font-weight: 600;
        border-radius: 12px;
    }
    .doc-show-hero h2 {
        font-size: clamp(1.35rem, 2vw, 1.85rem);
        font-weight: 700;
        margin: 0 0 0.75rem;
        line-height: 1.3;
        word-break: break-word;
    }
    .doc-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .doc-chip {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 0.25rem 0.8rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.16);
        font-size: 0.86rem;
        font-weight: 600;
    }
    .doc-info-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.85rem;
    }
    .doc-info-tile {
        background: #fff;
        border-radius: 16px;
        padding: 1rem 1.05rem;
        box-shadow: 0 10px 28px rgba(27, 44, 91, 0.07);
        min-width: 0;
    }
    .doc-info-label {
        color: #6b7a99;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 0.3rem;
    }
    .doc-info-value {
        color: #1b2c5b;
        font-weight: 700;
        word-break: break-word;
    }
    .doc-callout {
        border-radius: 16px;
        padding: 1rem 1.1rem;
        margin-top: 1rem;
        font-weight: 500;
    }
    .doc-callout.is-info { background: #eaf2ff; color: #23408e; }
    .doc-callout.is-warning { background: #fff4d6; color: #7a5b10; }
    .doc-callout.is-success { background: #e3f8ed; color: #0f7a45; }
    .doc-file-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 10px 28px rgba(27, 44, 91, 0.07);
        height: 100%;
    }
    .doc-preview {
        width: 100%;
        min-height: 280px;
        border: 1px solid #e4ebf6;
        border-radius: 14px;
        background: #f7f9fd;
        overflow: hidden;
    }
    .doc-preview iframe,
    .doc-preview img {
        width: 100%;
        min-height: 280px;
        border: 0;
        object-fit: contain;
    }
    @media (max-width: 991.98px) {
        .doc-info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .doc-show-hero { padding: 1.1rem; }
        .doc-info-grid { grid-template-columns: 1fr; }
        .doc-show-actions .btn,
        .doc-file-actions .btn { width: 100%; }
    }
</style>

<div class="container-fluid px-2 px-md-3">
    <div class="doc-show-page">
        <div class="doc-show-hero">
            <div class="doc-show-actions">
                <a href="{{ route('student.documents.index') }}" class="btn btn-light">Back to collection</a>
                @if($student)
                    <a href="{{ route('student_management.profile', ['studentId' => $documentRequest->student_id]) }}" class="btn btn-outline-light">Open student profile</a>
                @endif
                @if($documentRequest->documentsAreAvailable())
                    <form method="POST" action="{{ route('student.documents.review', $documentRequest) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning" {{ $documentRequest->reviewed_at ? 'disabled' : '' }}>
                            {{ $documentRequest->reviewed_at ? 'Already reviewed' : 'Mark as reviewed' }}
                        </button>
                    </form>
                @endif
            </div>
            <h2>{{ $student->full_name ?? 'Student submission' }}</h2>
            <div class="doc-chip-row">
                <span class="doc-chip">{{ $statusLabel }}</span>
                <span class="doc-chip">Notification {{ $documentRequest->notification_status }}</span>
                <span class="doc-chip">Documents {{ $documentRequest->document_deletion_status }}</span>
            </div>
        </div>

        @if(session('success'))
            <div class="doc-callout is-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="doc-callout is-warning">{{ session('error') }}</div>
        @endif

        <div class="doc-info-grid">
            <div class="doc-info-tile">
                <div class="doc-info-label">Student ID</div>
                <div class="doc-info-value">{{ $documentRequest->student_id }}</div>
            </div>
            <div class="doc-info-tile">
                <div class="doc-info-label">Identification</div>
                <div class="doc-info-value">{{ $student->id_type ?? 'ID' }} {{ $student->id_value ?? '—' }}</div>
            </div>
            <div class="doc-info-tile">
                <div class="doc-info-label">Link sent by</div>
                <div class="doc-info-value">{{ $documentRequest->creator->name ?? 'Staff' }}</div>
            </div>
            <div class="doc-info-tile">
                <div class="doc-info-label">Submitted</div>
                <div class="doc-info-value">{{ $documentRequest->submitted_at ? $documentRequest->submitted_at->timezone('Asia/Colombo')->format('Y-m-d H:i') : 'Not submitted yet' }}</div>
            </div>
            <div class="doc-info-tile">
                <div class="doc-info-label">Retention until</div>
                <div class="doc-info-value">{{ $documentRequest->retention_expires_at ? $documentRequest->retention_expires_at->timezone('Asia/Colombo')->format('Y-m-d H:i') : '—' }}</div>
            </div>
            <div class="doc-info-tile">
                <div class="doc-info-label">Student contact</div>
                <div class="doc-info-value">
                    @if($documentRequest->submitted_email || $documentRequest->submitted_phone)
                        {{ $documentRequest->submitted_email ?: '—' }}
                        @if($documentRequest->submitted_phone)<div>{{ $documentRequest->submitted_phone }}</div>@endif
                    @else
                        {{ $student->email ?: 'Not provided yet' }}
                    @endif
                </div>
            </div>
        </div>

        @if($documentRequest->submitted_notes)
            <div class="doc-info-tile mt-3">
                <div class="doc-info-label">Notes</div>
                <div class="doc-info-value">{{ $documentRequest->submitted_notes }}</div>
            </div>
        @endif

        @if($documentRequest->status === 'purged')
            <div class="doc-callout is-warning">Uploaded files and temporary submission details were automatically deleted after the 7-day retention period.</div>
        @elseif($documentRequest->status === 'pending')
            <div class="doc-callout is-info">The student has not submitted documents yet. Share the unique link and wait for the upload.</div>
        @endif

        @if($documentRequest->documentsAreAvailable())
            <div class="row g-3 mt-1">
                @foreach($documentRequest->files as $file)
                    <div class="col-lg-6">
                        <div class="doc-file-card p-3 p-md-4">
                            <h5 class="mb-1">{{ $types[$file->document_type]['label'] ?? ucfirst(str_replace('_', ' ', $file->document_type)) }}</h5>
                            <p class="text-muted mb-3">{{ $file->original_filename }} · {{ number_format($file->size_bytes / 1024, 1) }} KB</p>
                            <div class="doc-preview mb-3">
                                @if(str_starts_with((string) $file->mime_type, 'image/'))
                                    <img src="{{ route('student.documents.file', ['documentFile' => $file, 'inline' => 1]) }}" alt="Uploaded document preview">
                                @else
                                    <iframe src="{{ route('student.documents.file', ['documentFile' => $file, 'inline' => 1]) }}" title="Uploaded document preview"></iframe>
                                @endif
                            </div>
                            <div class="doc-file-actions">
                                <a class="btn btn-primary" href="{{ route('student.documents.file', $file) }}">Download</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
