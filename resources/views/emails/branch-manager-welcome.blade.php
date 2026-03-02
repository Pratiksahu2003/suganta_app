<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }} - Branch Manager Account</title>
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

        .credentials-box {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 2px solid #667eea;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }

        .credentials-box h3 {
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .credentials-box h3::before {
            content: "🔑";
            margin-right: 10px;
        }

        .credential-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .credential-label {
            font-weight: 600;
            color: #4a5568;
        }

        .credential-value {
            font-family: monospace;
            background: #f1f5f9;
            padding: 8px 12px;
            border-radius: 6px;
            color: #2d3748;
            font-weight: 500;
        }

        .login-button {
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

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .branch-info-box {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
            border-left: 4px solid #48bb78;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .branch-info-box h3 {
            color: #2d3748;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .branch-info-box h3::before {
            content: "🏢";
            margin-right: 8px;
        }

        .branch-info-box p {
            color: #4a5568;
            font-size: 14px;
            margin: 5px 0;
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
            content: "🎓";
            margin-right: 8px;
        }

        .info-box p {
            color: #4a5568;
            font-size: 14px;
            margin: 0;
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

            .credential-item {
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
            <h1>Welcome to {{ config('app.name') }}!</h1>
            <p>Your branch manager account has been created successfully</p>
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="greeting">Hello {{ $branchManager->name }}! 👋</div>

            <div class="message">
                Welcome to <strong>{{ config('app.name') }}</strong>! We're excited to have you join our team as a Branch Manager for <strong>{{ $branch->branch_name }}</strong> under <strong>{{ $instituteProfile->institute_name ?? $instituteProfile->instituteInfo->institute_name ?? 'the Institute' }}</strong>.
            </div>

            <div class="message">
                Your branch manager account has been created and you can now access all the features available to branch managers on our platform.
            </div>

            <!-- Branch Information -->
            <div class="branch-info-box">
                <h3>Your Branch Information</h3>
                <p><strong>Branch Name:</strong> {{ $branch->branch_name }}</p>
                <p><strong>Branch Code:</strong> {{ $branch->branch_code }}</p>
                <p><strong>Address:</strong> {{ $branch->address }}, {{ $branch->city }}, {{ $branch->state }} - {{ $branch->pincode }}</p>
                @if($branch->contact_phone)
                <p><strong>Contact Phone:</strong> {{ $branch->contact_phone }}</p>
                @endif
                @if($branch->contact_email)
                <p><strong>Contact Email:</strong> {{ $branch->contact_email }}</p>
                @endif
            </div>

            <!-- Login Credentials -->
            <div class="credentials-box">
                <h3>Your Login Credentials</h3>
                
                <div class="credential-item">
                    <span class="credential-label">Email Address:</span>
                    <span class="credential-value">{{ $branchManager->email }}</span>
                </div>
                
                <div class="credential-item">
                    <span class="credential-label">Password:</span>
                    <span class="credential-value">{{ $password }}</span>
                </div>
            </div>

            <!-- Login Button -->
            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="login-button">
                    🚀 Login to Your Account
                </a>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <h3>What you can do now?</h3>
                <p>Manage your branch, oversee teachers, handle student enrollments, and coordinate branch operations. Your account is already verified and ready to use!</p>
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <p>🔒 For security reasons, please change your password after your first login.</p>
            </div>

            <!-- Features Preview -->
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">👥</div>
                    <h4>Manage Teachers</h4>
                    <p>Oversee and coordinate with branch teachers</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📚</div>
                    <h4>Student Management</h4>
                    <p>Handle student enrollments and progress</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📅</div>
                    <h4>Schedule Coordination</h4>
                    <p>Manage branch schedules and timetables</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📊</div>
                    <h4>Branch Analytics</h4>
                    <p>Track branch performance and metrics</p>
                </div>
            </div>

            <div class="divider"></div>

            <div class="message">
                If you have any questions or need assistance getting started, please don't hesitate to contact our support team or reach out to your institute administrator.
            </div>

            <div class="message">
                We're here to help you succeed in managing your branch effectively!
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
                    This email was sent to {{ $branchManager->email }}. If you have any questions, please contact our support team.
                </p>
            </div>
        </div>
    </div>
</body>

</html>
