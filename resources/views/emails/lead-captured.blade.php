@php
    $agent = $lead->agent;
    $appUrl = rtrim((string) config('app.url'), '/');
    $logoUrl = $appUrl.'/images/login_logo.png';
    $companyName = $agent?->company_name ?: 'your company';
    $capturedAt = $lead->created_at?->format('M j, Y g:i A') ?: '-';
    $status = filled($lead->status) ? str($lead->status)->headline()->toString() : 'New';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New lead captured</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f7fb; color: #111827; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f5f7fb; margin: 0; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 640px; background-color: #ffffff; border: 1px solid #d7dce5; border-radius: 18px;">
                    <tr>
                        <td style="padding: 28px 32px 20px; border-bottom: 1px solid #e5e7eb;">
                            <img src="{{ $logoUrl }}" alt="K-Agent" style="display: block; width: 168px; max-width: 100%; height: auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px 32px 12px;">
                            <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #111827;">New Lead Captured</div>
                            <h1 style="margin: 10px 0 10px; font-size: 28px; line-height: 1.2; font-weight: 700; color: #111827;">A new lead came in for {{ $companyName }}</h1>
                            <p style="margin: 0; font-size: 15px; line-height: 1.7; color: #4b5563;">A visitor submitted their contact details through K-Agent. Review the dashboard for the full conversation and follow-up actions.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 32px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: separate; border-spacing: 0; border: 1px solid #d7dce5; border-radius: 16px; overflow: hidden;">
                                <tr>
                                    <td style="width: 40%; padding: 16px 18px; font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #111827; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">Lead Name</td>
                                    <td style="padding: 16px 18px; font-size: 15px; font-weight: 600; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $lead->name }}</td>
                                </tr>
                                <tr>
                                    <td style="width: 40%; padding: 16px 18px; font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #111827; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">Lead Email</td>
                                    <td style="padding: 16px 18px; font-size: 15px; font-weight: 600; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $lead->email ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="width: 40%; padding: 16px 18px; font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #111827; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">Lead Status</td>
                                    <td style="padding: 16px 18px; font-size: 15px; font-weight: 600; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $status }}</td>
                                </tr>
                                <tr>
                                    <td style="width: 40%; padding: 16px 18px; font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #111827; background-color: #f9fafb;">Captured At</td>
                                    <td style="padding: 16px 18px; font-size: 15px; font-weight: 600; color: #111827;">{{ $capturedAt }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 32px 32px;">
                            <a href="{{ $appUrl }}/admin/leads" style="display: inline-block; padding: 14px 22px; border-radius: 12px; background-color: #e6004d; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700;">Review Leads in Dashboard</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 32px 28px; font-size: 13px; line-height: 1.7; color: #6b7280;">
                            This notification was sent by K-Agent for {{ $companyName }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
