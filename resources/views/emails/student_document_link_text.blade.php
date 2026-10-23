Hello {{ $student->full_name ?: 'Student' }},

Please use this secure Nebula link to submit your documents. The link is unique to you — do not share it with anyone else.

{{ $submissionUrl }}
@if($expiresAt)

This link expires on {{ $expiresAt }}.
@endif

If you were not expecting this email, you can ignore it.

{{ config('app.name', 'Nebula') }}
