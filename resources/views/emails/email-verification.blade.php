<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - {{ config('app.name') }}</title>
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

        .verification-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .verification-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .info-box {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-left: 4px solid #667eea;
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

        .expiry-notice {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border: 1px solid #feb2b2;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }

        .expiry-notice p {
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

            .verification-button {
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
                <div class="logo w-40 h-16  flex items-center justify-center  transform transition-all duration-300 group-hover:rotate-6  overflow-hidden ">
                    <img src="{{ asset('logo/Su250.png') }}" alt="International" class="w-full h-full object-contain p-1">
                </div>
            </div>
            <h1>Verify Your Email Address</h1>
            <p>Complete your registration and unlock all features</p>
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="greeting">Hello {{ $notifiable->name }}! 👋</div>

            <div class="message">
                Thank you for creating your account on <strong>{{ config('app.name') }}</strong>! We're excited to have you join our learning community.
            </div>

            <div class="message">
                To complete your registration and start exploring our platform, please verify your email address by clicking the button below:
            </div>

            <!-- Verification Button -->
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="verification-button">
                    ✅ Verify Email Address
                </a>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <h3>What happens next?</h3>
                <p>After verifying your email, you'll have full access to our platform including personalized learning experiences, expert tutors, and comprehensive educational resources.</p>
            </div>

            <!-- Expiry Notice -->
            <div class="expiry-notice">
                <p>⚠️ This verification link will expire in {{ config('auth.verification.expire', 60) }} minutes for security reasons.</p>
            </div>

            <!-- Features Preview -->
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">🎓</div>
                    <h4>Expert Tutors</h4>
                    <p>Connect with verified educators</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📚</div>
                    <h4>Rich Content</h4>
                    <p>Access comprehensive materials</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">🚀</div>
                    <h4>Fast Learning</h4>
                    <p>Accelerate your progress</p>
                </div>
            </div>

            <div class="divider"></div>

            <div class="message">
                If you did not create an account on {{ config('app.name') }}, please ignore this email. No further action is required.
            </div>

            <div class="message">
                If you're having trouble clicking the verification button, copy and paste the following URL into your web browser:
            </div>

            <div style="background: #f1f5f9; padding: 15px; border-radius: 6px; margin: 15px 0; word-break: break-all; font-family: monospace; font-size: 12px; color: #475569;">
                {{ $verificationUrl }}
            </div>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <p>Best regards,</p>
            <p><strong>{{ config('app.name') }} Team</strong></p>

            <div class="social-links">
                <a href="{{ config('app.url') }}">Visit Website</a>
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
                <a href="{{ config('app.url') }}/help">Help Center</a>
            </div>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <p style="font-size: 12px; color: #a0aec0;">
                    This email was sent to {{ $notifiable->email }}. If you have any questions, please contact our support team.
                </p>
            </div>
        </div>
    </div>
</body>

</html>