<x-mail::message>
Hello {{ $student->full_name ?: 'Student' }},

Please use this secure Nebula link to submit your documents. The link is unique to you — do not share it with anyone else.

<x-mail::button :url="$submissionUrl">
Open document submission
</x-mail::button>

If the button does not work, copy and paste this link into your browser:

{{ $submissionUrl }}

@if($expiresAt)
This link expires on **{{ $expiresAt }}**.
@endif

If you were not expecting this email, you can ignore it.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
