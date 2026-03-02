<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Password Activity Notification' }}</title>
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
            background-color: #f8f9fa;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
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
            color: #2d3748;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .message {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 25px;
            line-height: 1.7;
        }
        
        .activity-details {
            background-color: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }
        
        .activity-details h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #4a5568;
        }
        
        .detail-value {
            color: #718096;
        }
        
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 25px 0;
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
        }
        
        .security-notice {
            background-color: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .security-notice h4 {
            color: #c53030;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .security-notice p {
            color: #742a2a;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .footer {
            background-color: #2d3748;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .footer p {
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .footer .company-name {
            font-weight: 600;
            color: #667eea;
        }
        
        .social-links {
            margin-top: 20px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #a0aec0;
            text-decoration: none;
            font-size: 14px;
        }
        
        .social-links a:hover {
            color: #667eea;
        }
        
        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .header {
                padding: 25px 15px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>🔐 Password Activity</h1>
            <p>SuGanta Tutors - Security Notification</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hello {{ $user->name }}!
            </div>
            
            <div class="message">
                {{ $content }}
            </div>
            
            <!-- Activity Details -->
            <div class="activity-details">
                <h3>📋 Activity Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Activity Type:</span>
                    <span class="detail-value">
                        @switch($activityType)
                            @case('password_updated')
                                🔄 Password Updated
                                @break
                            @case('password_reset')
                                🔑 Password Reset
                                @break
                            @case('password_changed')
                                ✏️ Password Changed
                                @break
                            @default
                                🔐 Password Activity
                        @endswitch
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Timestamp:</span>
                    <span class="detail-value">{{ now()->format('F j, Y \a\t g:i A') }}</span>
                </div>
                
                @if(isset($additionalInfo['ip_address']))
                <div class="detail-row">
                    <span class="detail-label">IP Address:</span>
                    <span class="detail-value">{{ $additionalInfo['ip_address'] }}</span>
                </div>
                @endif
                
                @if(isset($additionalInfo['source']))
                <div class="detail-row">
                    <span class="detail-label">Source:</span>
                    <span class="detail-value">
                        @switch($additionalInfo['source'])
                            @case('profile_update')
                                Profile Settings
                                @break
                            @case('api_update')
                                API Update
                                @break
                            @case('forgot_password_reset')
                                Forgot Password Flow
                                @break
                            @case('auth_password_update')
                                Authentication Settings
                                @break
                            @case('dashboard_password_update')
                                Dashboard
                                @break
                            @default
                                {{ ucfirst(str_replace('_', ' ', $additionalInfo['source'])) }}
                        @endswitch
                    </span>
                </div>
                @endif
            </div>
            
            <!-- Action Button -->
            @if($actionText && $actionUrl)
            <div style="text-align: center;">
                <a href="{{ $actionUrl }}" class="action-button">
                    {{ $actionText }}
                </a>
            </div>
            @endif
            
            <!-- Security Notice -->
            <div class="security-notice">
                <h4>⚠️ Security Notice</h4>
                <p>
                    If you did not perform this action, please contact our support team immediately. 
                    Your account security is our top priority.
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Thank you for using <span class="company-name">{{ config('app.name') }}</span></p>
            <p>This is an automated security notification. Please do not reply to this email.</p>
            
            <div class="social-links">
                <a href="{{ config('app.url') }}">Visit Website</a>
                <a href="{{ config('app.url') }}/support">Support</a>
                <a href="{{ config('app.url') }}/contact">Contact Us</a>
            </div>
            
            <p style="margin-top: 20px; font-size: 12px; color: #a0aec0;">
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
