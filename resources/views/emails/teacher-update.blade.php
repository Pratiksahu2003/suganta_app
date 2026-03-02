<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Updated - {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
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

        .update-summary {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 2px solid #48bb78;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
        }

        .update-summary h3 {
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .update-summary h3::before {
            content: "📝";
            margin-right: 10px;
        }

        .update-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .update-label {
            font-weight: 600;
            color: #4a5568;
        }

        .update-value {
            font-family: monospace;
            background: #f0fff4;
            padding: 8px 12px;
            border-radius: 6px;
            color: #2d3748;
            font-weight: 500;
            border: 1px solid #9ae6b4;
        }

        .login-button {
            display: inline-block;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.6);
        }

        .info-box {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
            border-left: 4px solid #48bb78;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .info-box h3 {
            color: #2d3748;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .info-box h3::before {
            content: "ℹ️";
            margin-right: 8px;
        }

        .info-box p {
            color: #4a5568;
            font-size: 14px;
            margin: 0;
        }

        .no-changes-notice {
            background: linear-gradient(135deg, #fffaf0 0%, #fef5e7 100%);
            border: 1px solid #f6e05e;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }

        .no-changes-notice p {
            color: #d69e2e;
            font-size: 14px;
            font-weight: 500;
            margin: 0;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .feature {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .feature-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .feature h4 {
            color: #2d3748;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .feature p {
            color: #718096;
            font-size: 12px;
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
            color: #48bb78;
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

            .login-button {
                padding: 14px 28px;
                font-size: 15px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .update-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="header">
            <div class="logo-container">
                <div class="logo w-40 h-16 flex items-center justify-center transform transition-all duration-300 group-hover:rotate-6 overflow-hidden">
                    <img src="{{ asset('logo/Su250.png') }}" alt="{{ config('app.name') }} Logo" class="w-full h-full object-contain p-1">
                </div>
            </div>
            <h1>Profile Updated!</h1>
            <p>Your teacher profile has been successfully updated</p>
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="greeting">Hello {{ $teacher->name }}! 👋</div>

            <div class="message">
                Your teacher profile on <strong>{{ config('app.name') }}</strong> has been updated by <strong>{{ $instituteProfile->institute_name ?? $instituteProfile->instituteInfo->institute_name ?? 'the Institute' }}</strong>.
            </div>

            @if(count($updatedFields) > 0)
            <!-- Update Summary -->
            <div class="update-summary">
                <h3>Updated Information</h3>
                
                @foreach($updatedFields as $field => $value)
                <div class="update-item">
                    <span class="update-label">{{ ucwords(str_replace('_', ' ', $field)) }}:</span>
                    <span class="update-value">{{ $value }}</span>
                </div>
                @endforeach
            </div>
            @else
            <!-- No Changes Notice -->
            <div class="no-changes-notice">
                <p>📝 Your profile information has been reviewed and confirmed.</p>
            </div>
            @endif

            <!-- Login Button -->
            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="login-button">
                    🔍 View Updated Profile
                </a>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <h3>What's Next?</h3>
                <p>You can now log in to your account to see all the updated information. If you have any questions about these changes, please contact your institute administrator.</p>
            </div>

            <!-- Features Preview -->
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">👤</div>
                    <h4>Updated Profile</h4>
                    <p>Your information is now current</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📚</div>
                    <h4>Subject Updates</h4>
                    <p>Your teaching subjects are updated</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">🎯</div>
                    <h4>Better Matching</h4>
                    <p>Students can find you more easily</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📈</div>
                    <h4>Enhanced Visibility</h4>
                    <p>Improved profile performance</p>
                </div>
            </div>

            <div class="divider"></div>

            <div class="message">
                If you have any questions about these updates or need to make additional changes, please don't hesitate to contact your institute administrator or our support team.
            </div>

            <div class="message">
                Thank you for keeping your profile up to date!
            </div>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <p>Best regards,</p>
            <p><strong>{{ config('app.name') }} Team</strong></p>
            <p><strong>{{ $instituteProfile->institute_name ?? $instituteProfile->instituteInfo->institute_name ?? 'Institute' }}</strong></p>

            <div class="social-links">
                <a href="{{ config('app.url') }}">Visit Website</a>
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
                <a href="{{ config('app.url') }}/help">Help Center</a>
            </div>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <p style="font-size: 12px; color: #a0aec0;">
                    This email was sent to {{ $teacher->email }}. If you have any questions, please contact our support team.
                </p>
            </div>
        </div>
    </div>
</body>

</html>
