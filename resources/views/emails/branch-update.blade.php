<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Information Updated - {{ $branch->branch_name }}</title>
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

        .update-box {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
            border-left: 4px solid #48bb78;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .update-box h3 {
            color: #2d3748;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .update-box h3::before {
            content: "📝";
            margin-right: 8px;
        }

        .updated-field {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }

        .field-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .field-value {
            color: #2d3748;
            font-size: 16px;
        }

        .branch-info-box {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 2px solid #667eea;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
        }

        .branch-info-box h3 {
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .branch-info-box h3::before {
            content: "🏢";
            margin-right: 10px;
        }

        .info-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
        }

        .info-value {
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

            .info-item {
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
            <h1>Branch Information Updated</h1>
            <p>Your branch details have been successfully updated</p>
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="greeting">Hello {{ $branchManager->name }}! 👋</div>

            <div class="message">
                The information for your branch <strong>{{ $branch->branch_name }}</strong> under <strong>{{ $institute->institute_name }}</strong> has been updated by the institute administrator.
            </div>

            <!-- Updated Fields -->
            @if(!empty($updatedFields))
            <div class="update-box">
                <h3>Updated Information</h3>
                @foreach($updatedFields as $field => $value)
                <div class="updated-field">
                    <div class="field-label">{{ ucwords(str_replace('_', ' ', $field)) }}:</div>
                    <div class="field-value">{{ $value }}</div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Current Branch Information -->
            <div class="branch-info-box">
                <h3>Current Branch Information</h3>
                
                <div class="info-item">
                    <span class="info-label">Branch Name:</span>
                    <span class="info-value">{{ $branch->branch_name }}</span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Branch Code:</span>
                    <span class="info-value">{{ $branch->branch_code }}</span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Address:</span>
                    <span class="info-value">{{ $branch->address }}, {{ $branch->city }}, {{ $branch->state }} - {{ $branch->pincode }}</span>
                </div>
                
                @if($branch->contact_person)
                <div class="info-item">
                    <span class="info-label">Contact Person:</span>
                    <span class="info-value">{{ $branch->contact_person }}</span>
                </div>
                @endif
                
                @if($branch->contact_phone)
                <div class="info-item">
                    <span class="info-label">Contact Phone:</span>
                    <span class="info-value">{{ $branch->contact_phone }}</span>
                </div>
                @endif
                
                @if($branch->contact_email)
                <div class="info-item">
                    <span class="info-label">Contact Email:</span>
                    <span class="info-value">{{ $branch->contact_email }}</span>
                </div>
                @endif
                
                @if($branch->capacity)
                <div class="info-item">
                    <span class="info-label">Capacity:</span>
                    <span class="info-value">{{ $branch->capacity }} students</span>
                </div>
                @endif
            </div>

            <!-- Login Button -->
            <div style="text-align: center;">
                <a href="{{ route('login') }}" class="login-button">
                    🚀 Access Your Dashboard
                </a>
            </div>

            <div class="divider"></div>

            <div class="message">
                If you have any questions about these changes or need assistance, please don't hesitate to contact the institute administrator or our support team.
            </div>

            <div class="message">
                Thank you for being part of our educational community!
            </div>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <p>Best regards,</p>
            <p><strong>{{ config('app.name') }} Team</strong></p>
            <p><strong>{{ $institute->institute_name }}</strong></p>

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
