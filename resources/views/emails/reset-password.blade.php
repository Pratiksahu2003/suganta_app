<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - {{ config('company.name') }}</title>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        /* Header Section */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .logo-container {
            position: relative;
            z-index: 2;
            margin-bottom: 20px;
        }

        .logo {
            width: 120px;
            height: 60px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
           
        }

        .logo-text {
            color: #ffffff;
            font-size: 28px;
            font-weight: 800;
            margin: 0;
            position: relative;
            z-index: 2;
        }

        .header-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            font-weight: 500;
            margin: 0;
            position: relative;
            z-index: 2;
        }

        /* Main Content */
        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            text-align: center;
        }

        .message {
            font-size: 16px;
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 30px;
            text-align: center;
        }

        /* Reset Button */
        .reset-button-container {
            text-align: center;
            margin: 40px 0;
        }

        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .reset-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .reset-button:hover::before {
            left: 100%;
        }

        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.5);
        }

        /* Security Notice */
        .security-notice {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            text-align: center;
        }

        .security-notice h3 {
            color: #92400e;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .security-notice p {
            color: #78350f;
            font-size: 14px;
            margin: 0;
            line-height: 1.6;
        }

        .security-icon {
            width: 20px;
            height: 20px;
            background: #f59e0b;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
        }

        /* Expiry Notice */
        .expiry-notice {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }

        .expiry-notice p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
            font-weight: 500;
        }

        /* Footer */
        .footer {
            background: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer-links {
            margin-bottom: 20px;
        }

        .footer-link {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            margin: 0 15px;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: #5a6fd8;
        }

        .footer-text {
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
        }

        .company-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .company-name {
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .company-contact {
            color: #6b7280;
            font-size: 12px;
            margin: 0;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 15px;
            }

            .header,
            .content,
            .footer {
                padding: 25px 20px;
            }

            .logo {
                width: 100px;
                height: 50px;
            }

            .logo-text {
                font-size: 24px;
            }

            .greeting {
                font-size: 20px;
            }

            .reset-button {
                padding: 16px 32px;
                font-size: 15px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            }

            .email-container {
                background: #1f2937;
                color: #f9fafb;
            }

            .greeting {
                color: #f9fafb;
            }

            .message {
                color: #d1d5db;
            }

            .footer {
                background: #111827;
                border-top-color: #374151;
            }

            .footer-text {
                color: #9ca3af;
            }

            .company-name {
                color: #f9fafb;
            }

            .company-contact {
                color: #9ca3af;
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
            <h1 class="logo-text">{{ config('company.name') }}</h1>
            <p class="header-subtitle">Reset Your Password</p>
        </div>

        <!-- Main Content -->
        <div class="content">
            <h2 class="greeting">Hello {{ $notifiable->name ?? 'there' }}!</h2>

            <p class="message">
                We received a request to reset your password for your {{ config('company.name') }} account.
                If you didn't make this request, you can safely ignore this email.
            </p>

            <!-- Reset Button -->
            <div class="reset-button-container">
                <a href="{{ $url }}" class="reset-button">
                    Reset My Password
                </a>
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <h3>
                    <span class="security-icon">🔒</span>
                    Security Reminder
                </h3>
                <p>
                    This link will expire in 60 minutes for your security.
                    Never share this link with anyone, as it provides access to your account.
                </p>
            </div>

            <!-- Expiry Notice -->
            <div class="expiry-notice">
                <p>
                    <strong>Link expires:</strong> {{ now()->addMinutes(60)->format('F j, Y \a\t g:i A') }}
                </p>
            </div>

            <p class="message">
                If you're having trouble clicking the button above, copy and paste this URL into your browser:
                <br><br>
                <a href="{{ $url }}" style="color: #667eea; word-break: break-all;">{{ $url }}</a>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-links">
                <a href="{{ config('company.contact.website') }}" class="footer-link">Visit Website</a>
                <a href="{{ route('contact') }}" class="footer-link">Contact Support</a>
                <a href="{{ route('about') }}" class="footer-link">About Us</a>
            </div>

            <p class="footer-text">
                This email was sent to {{ $notifiable->email }} because you requested a password reset.
                If you didn't request this, please ignore this email or contact our support team.
            </p>

            <div class="company-info">
                <p class="company-name">{{ config('company.name') }}</p>
                <p class="company-contact">
                    {{ config('company.contact.email') }} | {{ config('company.contact.phone') }}<br>
                    {{ config('company.address.line1') }}, {{ config('company.address.city') }}, {{ config('company.address.state') }} {{ config('company.address.pincode') }}
                </p>
            </div>

            <p class="footer-text" style="margin-top: 20px;">
                &copy; {{ date('Y') }} {{ config('company.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>

</html>