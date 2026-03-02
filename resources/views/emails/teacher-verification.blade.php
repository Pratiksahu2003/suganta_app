<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Status Update - SuGanta Tutors</title>
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
            background: linear-gradient(135deg, {{ $isVerified ? '#48bb78 0%, #38a169 100%' : '#ed8936 0%, #dd6b20 100%' }});
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

        .status-box {
            background: linear-gradient(135deg, {{ $isVerified ? '#f0fff4 0%, #c6f6d5 100%' : '#fffaf0 0%, #fef5e7 100%' }});
            border: 2px solid {{ $isVerified ? '#48bb78' : '#ed8936' }};
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }

        .status-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .status-title {
            font-size: 24px;
            font-weight: 700;
            color: {{ $isVerified ? '#2d3748' : '#d69e2e' }};
            margin-bottom: 10px;
        }

        .status-description {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 20px;
        }

        .login-button {
            display: inline-block;
            background: linear-gradient(135deg, {{ $isVerified ? '#48bb78 0%, #38a169 100%' : '#ed8936 0%, #dd6b20 100%' }});
            color: white;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px {{ $isVerified ? 'rgba(72, 187, 120, 0.4)' : 'rgba(237, 137, 54, 0.4)' }};
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px {{ $isVerified ? 'rgba(72, 187, 120, 0.6)' : 'rgba(237, 137, 54, 0.6)' }};
        }

        .info-box {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-left: 4px solid {{ $isVerified ? '#48bb78' : '#ed8936' }};
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
            content: "{{ $isVerified ? '✅' : 'ℹ️' }}";
            margin-right: 8px;
        }

        .info-box p {
            color: #4a5568;
            font-size: 14px;
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
            color: {{ $isVerified ? '#48bb78' : '#ed8936' }};
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
            <h1>{{ $isVerified ? 'Account Verified!' : 'Verification Update' }}</h1>
            <p>{{ $isVerified ? 'Your teacher account is now verified' : 'Your verification status has been updated' }}</p>
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="greeting">Hello {{ $teacher->name }}! 👋</div>

            <div class="message">
                Your teacher account verification status has been updated by <strong>{{ $instituteProfile->institute_name ?? $instituteProfile->instituteInfo->institute_name ?? 'the Institute' }}</strong>.
            </div>

            <!-- Status Box -->
            <div class="status-box">
                <div class="status-icon">
                    {{ $isVerified ? '✅' : '⏳' }}
                </div>
                <div class="status-title">
                    {{ $isVerified ? 'Account Verified' : 'Verification Removed' }}
                </div>
                <div class="status-description">
                    @if($isVerified)
                        Congratulations! Your teacher account is now verified and you have full access to all platform features.
                    @else
                        Your teacher verification has been removed. Please contact your institute administrator for more information.
                    @endif
                </div>
            </div>

            <!-- Login Button -->
            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="login-button">
                    {{ $isVerified ? '🚀 Access Your Account' : '🔍 View Your Account' }}
                </a>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <h3>{{ $isVerified ? 'What this means for you' : 'Next Steps' }}</h3>
                <p>
                    @if($isVerified)
                        You now have verified status, which means students can trust your credentials and you have access to premium features on the platform.
                    @else
                        If you believe this change was made in error, please contact your institute administrator immediately to resolve the issue.
                    @endif
                </p>
            </div>

            <!-- Features Preview -->
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">{{ $isVerified ? '🎓' : '📝' }}</div>
                    <h4>{{ $isVerified ? 'Verified Status' : 'Profile Review' }}</h4>
                    <p>{{ $isVerified ? 'Your credentials are verified' : 'Your profile needs review' }}</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">{{ $isVerified ? '⭐' : '🔍' }}</div>
                    <h4>{{ $isVerified ? 'Premium Access' : 'Limited Access' }}</h4>
                    <p>{{ $isVerified ? 'Full platform features' : 'Basic features only' }}</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">{{ $isVerified ? '👥' : '📞' }}</div>
                    <h4>{{ $isVerified ? 'Student Trust' : 'Contact Admin' }}</h4>
                    <p>{{ $isVerified ? 'Students trust your profile' : 'Reach out for help' }}</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">{{ $isVerified ? '🚀' : '⏰' }}</div>
                    <h4>{{ $isVerified ? 'Full Features' : 'Awaiting Review' }}</h4>
                    <p>{{ $isVerified ? 'All tools available' : 'Verification pending' }}</p>
                </div>
            </div>

            <div class="divider"></div>

            <div class="message">
                @if($isVerified)
                    Thank you for being part of our teaching community! We're excited to see what you'll accomplish with your verified status.
                @else
                    If you have any questions about this verification change, please don't hesitate to contact your institute administrator or our support team.
                @endif
            </div>

            <div class="message">
                We're here to support you in your teaching journey!
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
