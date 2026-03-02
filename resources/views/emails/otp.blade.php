<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Login OTP - {{ config('app.name') }}</title>
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

        .otp-container {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 2px dashed #667eea;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }

        .otp-label {
            font-size: 14px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .otp-code {
            font-size: 48px;
            font-weight: 700;
            color: #667eea;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            margin: 20px 0;
            text-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);
        }

        .otp-expiry {
            font-size: 14px;
            color: #718096;
            margin-top: 15px;
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

        .security-notice {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border: 1px solid #feb2b2;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }

        .security-notice p {
            color: #c53030;
            font-size: 14px;
            font-weight: 500;
            margin: 0;
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

        .social-links {
            margin-top: 20px;
        }

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .social-links a:hover {
            text-decoration: underline;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 30px 0;
        }

        .help-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }

        .help-section h4 {
            color: #2d3748;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .help-section h4::before {
            content: "💡";
            margin-right: 8px;
        }

        .help-section ul {
            list-style: none;
            padding: 0;
        }

        .help-section li {
            color: #4a5568;
            font-size: 14px;
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }

        .help-section li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }

        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }

            .header,
            .content,
            .footer {
                padding: 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .otp-code {
                font-size: 36px;
                letter-spacing: 6px;
            }

            .otp-container {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <img src="{{ asset('logo/Su250.png') }}" alt="International">
                </div>
            </div>
            @if($type === 'email_verification')
            <h1>Your Email Verification Code</h1>
            <p>Verify your email address for {{ config('app.name') }}</p>
            @elseif($type === 'study_requirement')
            <h1>Confirm Your Study Requirement</h1>
            <p>Verify your contact details for {{ config('app.name') }}</p>
            @else
            <h1>Your Login Verification Code</h1>
            <p>Secure access to your {{ config('app.name') }} account</p>
            @endif
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="greeting">Hello {{ $notifiable->name ?? 'User' }}! 👋</div>

            <div class="message">
                @if($type === 'email_verification')
                You've requested to verify your email address for your <strong>{{ config('app.name') }}</strong> account. Use the verification code below to complete your email verification:
                @elseif($type === 'study_requirement')
                You've shared a study requirement on <strong>{{ config('app.name') }}</strong>. Use the verification code below to confirm your email and phone number:
                @else
                You've requested to sign in to your <strong>{{ config('app.name') }}</strong> account. Use the verification code below to complete your login:
                @endif
            </div>

            <!-- OTP Display -->
            <div class="otp-container">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
                @php
                    $expiryCopy = $type === 'email_verification' ? '10' : ($type === 'study_requirement' ? '10' : '5');
                @endphp
                <div class="otp-expiry">⏱️ This code expires in {{ $expiryCopy }} minutes</div>
            </div>

            <!-- Security Info Box -->
            <div class="info-box">
                <h3>Security Notice</h3>
                <p>
                    For your security, never share this code with anyone. {{ config('app.name') }} staff will never ask for your verification code. 
                    If you didn't request this code, please ignore this email or contact our support team immediately.
                </p>
            </div>

            <!-- Expiry Notice -->
            <div class="security-notice">
                <p>⚠️ This verification code will expire in {{ $type === 'email_verification' ? '10' : '5' }} minutes for security reasons.</p>
            </div>

            <!-- Help Section -->
            <div class="help-section">
                <h4>Need Help?</h4>
                <ul>
                    @if($type === 'email_verification')
                    <li>Enter this code on the verification page to verify your email address</li>
                    <li>Make sure you're using the correct email address</li>
                    <li>If the code doesn't work, request a new one</li>
                    <li>Contact support if you continue to have issues</li>
                    @else
                    <li>Enter this code on the login page to complete your sign-in</li>
                    <li>Make sure you're using the correct email or phone number</li>
                    <li>If the code doesn't work, request a new one</li>
                    <li>Contact support if you continue to have issues</li>
                    @endif
                </ul>
            </div>

            <div class="divider"></div>

            <div class="message">
                <strong>Didn't request this code?</strong>
                @if($type === 'study_requirement')
                You can safely ignore this email; your request will not be processed without verification.
                @else
                If you didn't attempt to sign in, please ignore this email. Your account remains secure, and no changes have been made.
                @endif

            <div class="message">
                If you're having trouble signing in or have security concerns, please contact our support team immediately.
            </div>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <p>Best regards,</p>
            <p><strong>{{ config('app.name') }} Security Team</strong></p>

            <div class="social-links">
                <a href="{{ config('app.url') }}">Visit Website</a>
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
                <a href="{{ config('app.url') }}/help">Help Center</a>
            </div>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <p style="font-size: 12px; color: #a0aec0;">
                    This email was sent to {{ $notifiable->email }}. If you have any questions or concerns about your account security, please contact our support team.
                </p>
            </div>
        </div>
    </div>
</body>

</html>

