<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Payment Reminder - {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            font-size: 28px;
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
            margin-bottom: 30px;
            line-height: 1.7;
        }

        .subscription-box {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 2px dashed #667eea;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }

        .expiry-label {
            font-size: 14px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .expiry-date {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
            margin: 20px 0;
        }

        .days-remaining {
            font-size: 14px;
            color: #e53e3e;
            font-weight: 600;
            margin-top: 15px;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 15px 35px;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
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
            content: "⚠️";
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
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 30px 0;
        }

        @media (max-width: 600px) {
            .email-container { margin: 10px; }
            .header, .content, .footer { padding: 20px; }
            .expiry-date { font-size: 24px; }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <img src="https://www.suganta.com/logo/Su250.png" alt="International">
                </div>
            </div>
            <h1>Registration Payment Pending</h1>
            <p>Complete your onboarding on {{ config('app.name') }}</p>
        </div>

        <div class="content">
            <div class="greeting">Hello {{ $user->name ?? 'User' }}! 👋</div>

            <div class="message">
                We noticed that your registration payment is still pending. Please complete the payment to activate your account and access all platform features.
            </div>

            <div class="subscription-box">
                <div class="expiry-label">Payment Status</div>
                <div class="expiry-date">Pending</div>
                <div class="days-remaining">⏱️ Registered in the last 24 hours</div>

                <a href="{{ config('app.url') }}" class="cta-button">Complete Payment Now</a>
            </div>

            <div class="info-box">
                <h3>Important Notice</h3>
                <p>
                    Your account will remain in pending status until registration payment is completed.
                    Complete it now to continue using services without interruption.
                </p>
            </div>

            <div class="divider"></div>

            <div class="message" style="text-align: center;">
                If you have already completed the payment, please ignore this email.
            </div>
        </div>

        <div class="footer">
            <p>Best regards,</p>
            <p><strong>{{ config('app.name') }} Team</strong></p>

            <div class="social-links">
                <a href="{{ config('app.url') }}">Visit Website</a>
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
            </div>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <p style="font-size: 12px; color: #a0aec0;">
                    This email was sent to {{ $user->email }}.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
