<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Please submit your documents to Nebula</title>
</head>
<body style="margin:0;padding:0;background-color:#e8eef8;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#e8eef8;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                    <tr>
                        <td style="background-color:#1f3f9a;background-image:linear-gradient(135deg,#1f3f9a 0%,#4c6bd8 100%);padding:28px 32px;border-radius:20px 20px 0 0;text-align:center;">
                            <div style="color:#ffffff;font-size:22px;line-height:1.2;font-weight:700;letter-spacing:0.16em;">NEBULA</div>
                            <div style="color:#d7e3ff;font-size:11px;line-height:1.4;letter-spacing:0.14em;margin-top:6px;font-weight:600;">INSTITUTE OF TECHNOLOGY</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff;padding:32px 32px 8px;">
                            <p style="margin:0 0 6px;color:#4c6bd8;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;">Document submission</p>
                            <h1 style="margin:0 0 16px;color:#1b2c5b;font-size:24px;line-height:1.3;font-weight:700;">Please submit your documents</h1>
                            <p style="margin:0 0 16px;color:#334155;font-size:16px;line-height:1.6;">Hello {{ $student->full_name ?: 'Student' }},</p>
                            <p style="margin:0 0 24px;color:#334155;font-size:16px;line-height:1.6;">Use this secure Nebula link to upload your documents. The link is unique to you, so do not share it with anyone else.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="background-color:#23408e;border-radius:12px;">
                                                    <a href="{{ $submissionUrl }}" style="display:inline-block;padding:14px 26px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;letter-spacing:0.01em;">Open document submission</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @if($expiresAt)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;background-color:#eef4ff;border-radius:12px;">
                                    <tr>
                                        <td align="center" style="padding:14px 16px;color:#23408e;font-size:14px;line-height:1.5;">
                                            This link expires on <strong>{{ $expiresAt }}</strong>.
                                        </td>
                                    </tr>
                                </table>
                            @endif
                            <p style="margin:0 0 8px;color:#64748b;font-size:13px;line-height:1.6;">If the button does not work, copy and paste this link into your browser:</p>
                            <p style="margin:0 0 24px;color:#23408e;font-size:13px;line-height:1.6;word-break:break-all;">
                                <a href="{{ $submissionUrl }}" style="color:#23408e;text-decoration:underline;">{{ $submissionUrl }}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8fafc;padding:20px 32px 28px;border-radius:0 0 20px 20px;border-top:1px solid #e4ebf6;">
                            <p style="margin:0 0 8px;color:#64748b;font-size:13px;line-height:1.6;">If you were not expecting this email, you can ignore it.</p>
                            <p style="margin:0;color:#1b2c5b;font-size:13px;font-weight:600;">{{ config('app.name', 'Nebula') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
