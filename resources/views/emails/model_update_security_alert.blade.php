<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Alert - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fafc;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .logo-container {
            margin-bottom: 20px;
        }

        .logo {
            width: 80px;
            height: 40px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img {
            max-width: 100%;
            height: auto;
        }

        .header h1 {
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
        }

        .message {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 25px;
            line-height: 1.7;
        }

        .alert-box {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 2px dashed #e53e3e;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
        }

        .alert-label {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .alert-title {
            font-size: 22px;
            font-weight: 700;
            color: #c53030;
            margin-bottom: 15px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .details-table td {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: #2d3748;
            vertical-align: top;
        }

        .details-table td.label {
            color: #718096;
            font-weight: 600;
            width: 40%;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .details-table tr:last-child td {
            border-bottom: none;
        }

        .fields-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 4px;
        }

        .field-chip {
            display: inline-block;
            background: #fff5f5;
            color: #c53030;
            border: 1px solid #feb2b2;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            margin: 10px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .info-box {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border-left: 4px solid #fc8181;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .info-box h3 {
            color: #c53030;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .info-box h3::before {
            content: "🔒";
            margin-right: 8px;
        }

        .info-box p {
            color: #742a2a;
            font-size: 14px;
            margin: 0;
            line-height: 1.6;
        }

        .footer {
            background: #f7fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            color: #718096;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #e53e3e;
            text-decoration: none;
            font-size: 14px;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 25px 0;
        }

        @media (max-width: 600px) {
            .email-container { margin: 10px; }
            .header, .content, .footer { padding: 20px; }
            .alert-title { font-size: 18px; }
            .details-table td.label { width: 45%; }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <img src="https://www.suganta.com/logo/Su250.png" alt="{{ config('app.name') }}">
                </div>
            </div>
            @php
                $event = $event ?? 'updated';
                $isCreated = $event === 'created';
                $headerSubtitle = $isCreated
                    ? 'A new record was created on your ' . config('app.name') . ' account'
                    : 'An update was made on your ' . config('app.name') . ' account';
                $activityTitle = $isCreated
                    ? $modelLabel . ' Created'
                    : $modelLabel . ' Updated';
                $bodyAction = $isCreated ? 'was just created' : 'was just updated';
                $fieldsLabel = $isCreated ? 'Provided Fields' : 'Changed Fields';
            @endphp
            <h1>🛡️ Security Alert</h1>
            <p>{{ $headerSubtitle }}</p>
        </div>

        <div class="content">
            <div class="greeting">Hello {{ $user->name ?? 'User' }}! 👋</div>

            <div class="message">
                We're writing to let you know that a
                <strong>{{ $modelLabel }}</strong>@if($modelId) record (#{{ $modelId }})@endif
                {{ $bodyAction }} on your {{ config('app.name') }} account. If this was you, no further action is required.
            </div>

            <div class="alert-box">
                <div class="alert-label">Activity Detected</div>
                <div class="alert-title">{{ $activityTitle }}</div>

                <table class="details-table">
                    <tr>
                        <td class="label">Performed By</td>
                        <td>{{ $actorName }}</td>
                    </tr>
                    <tr>
                        <td class="label">When</td>
                        <td>{{ $eventTime }}</td>
                    </tr>
                    <tr>
                        <td class="label">IP Address</td>
                        <td>{{ $ipAddress }}</td>
                    </tr>
                    <tr>
                        <td class="label">Device</td>
                        <td style="word-break: break-word;">{{ $userAgent }}</td>
                    </tr>
                    @if(!empty($changedFields))
                    <tr>
                        <td class="label">{{ $fieldsLabel }}</td>
                        <td>
                            <div class="fields-list">
                                @foreach($changedFields as $field)
                                    <span class="field-chip">{{ $field }}</span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endif
                </table>

                <div style="text-align:center; margin-top: 20px;">
                    <a href="{{ config('app.url') }}/account/security" class="cta-button">Review Account Activity</a>
                </div>
            </div>

            <div class="info-box">
                <h3>Didn't recognize this activity?</h3>
                <p>
                    If you did <strong>not</strong> perform or authorize this change, please secure your account immediately
                    by changing your password and contacting our support team. Your account security is our top priority.
                </p>
            </div>

            <div class="divider"></div>

            <div class="message" style="text-align: center; font-size: 14px;">
                This is an automated security notification. You are receiving it because activity alerts are enabled on your account.
            </div>
        </div>

        <div class="footer">
            <p>Stay safe,</p>
            <p><strong>{{ config('app.name') }} Security Team</strong></p>

            <div class="social-links">
                <a href="{{ config('app.url') }}">Visit Website</a>
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
            </div>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <p style="font-size: 12px; color: #a0aec0;">
                    This email was sent to {{ $user->email ?? '' }}.
                </p>
            </div>
        </div>
    </div>
</body>

</html>
