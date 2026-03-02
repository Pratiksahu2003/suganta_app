<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Application Update' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #1f2937; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
        .email-wrapper { max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; }
        .header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; padding: 40px 30px; text-align: center; position: relative; }
        .header::before { content: ''; position: absolute; inset: 0; opacity: .3; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>'); }
        .logo { font-size: 28px; font-weight: 800; margin-bottom: 6px; position: relative; z-index: 1; }
        .tagline { font-size: 14px; opacity: 0.9; font-weight: 500; position: relative; z-index: 1; }
        .content { padding: 32px 28px; }
        .greeting { font-size: 22px; font-weight: 700; color: #1f2937; margin-bottom: 12px; line-height: 1.3; }
        .message { font-size: 16px; color: #4b5563; margin-bottom: 24px; line-height: 1.7; }
        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 16px; margin: 20px 0; }
        .detail-item { background: white; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .detail-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: .5px; margin-bottom: 6px; }
        .detail-value { font-size: 15px; color: #1e293b; font-weight: 600; }
        .action-section { text-align: center; margin: 28px 0; }
        .action-button { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 15px; box-shadow: 0 10px 25px -5px rgba(79,70,229,0.4); }
        .footer { background: #f8fafc; padding: 24px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #64748b; font-size: 14px; }
        @media (max-width: 600px) { .email-wrapper { margin: 10px; border-radius: 16px; } .content { padding: 24px 18px; } .details-grid { grid-template-columns: 1fr; } }
    </style>
    </head>
<body>
<div class="email-wrapper">
    <div class="header">
        <div class="logo"> <img src="{{ asset('logo/Su250.png') }}" alt="{{ config('app.name', 'SuGanta Tutors') }} Logo" style="max-width: 160px; width: 100%; height: auto; display: block; border: 0;" /></div>
        <div class="tagline">Application Update</div>
    </div>
    <div class="content">
        <div class="greeting">{{ $greeting ?? ('Hi ' . ($application->applicant_name ?? 'there') . '!') }}</div>
        <div class="message">{!! nl2br(e($body ?? 'We have an update regarding your application.')) !!}</div>

        <div class="details-grid">
            <div class="detail-item">
                <div class="detail-label">Position</div>
                <div class="detail-value">{{ $application->job->title ?? 'N/A' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Company</div>
                <div class="detail-value">{{ $application->job->company_name ?? 'N/A' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Status</div>
                <div class="detail-value">{{ ucfirst($application->status ?? 'N/A') }}</div>
            </div>
            @isset($extra)
                @foreach($extra as $label => $value)
                    @if(!empty($value))
                    <div class="detail-item">
                        <div class="detail-label">{{ $label }}</div>
                        <div class="detail-value">{{ $value }}</div>
                    </div>
                    @endif
                @endforeach
            @endisset
        </div>

        @isset($actionUrl)
        <div class="action-section">
            <a href="{{ $actionUrl }}" class="action-button">{{ $actionText ?? 'View Application' }}</a>
        </div>
        @endisset
    </div>
    <div class="footer">
        <p>This is an automated notification from SuGanta Tutors. Please do not reply.</p>
    </div>
</div>
</body>
</html>

