<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Activity Notification' }}</title>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        /* Container and Layout */
        .email-wrapper {
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        
        /* Header Section */
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .logo {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        
        .tagline {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        
        .header-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
            z-index: 1;
        }
        
        .header-icon svg {
            width: 30px;
            height: 30px;
            fill: white;
        }
        
        /* Content Section */
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
            line-height: 1.3;
        }
        
        .message {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 32px;
            line-height: 1.7;
        }
        
        /* Details Section */
        .details {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px;
            margin: 32px 0;
            position: relative;
            overflow: hidden;
        }
        
        .details::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
        
        .details h3 {
            margin: 0 0 20px 0;
            color: #1e293b;
            font-size: 18px;
            font-weight: 700;
        }
        
        .detail-item {
            background: white;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }
        
        .detail-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .detail-item:last-child {
            margin-bottom: 0;
        }
        
        /* Priority-based styling */
        .priority-high::before {
            border-left-color: #ef4444;
        }
        
        .priority-normal::before {
            border-left-color: #4f46e5;
        }
        
        .priority-low::before {
            border-left-color: #10b981;
        }
        
        /* Action Button */
        .action-section {
            text-align: center;
            margin: 40px 0;
        }
        
        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.6);
        }
        
        .action-button::after {
            content: '→';
            font-size: 18px;
            transition: transform 0.3s ease;
        }
        
        .action-button:hover::after {
            transform: translateX(4px);
        }
        
        /* Info Boxes */
        .info-box {
            padding: 20px;
            border-radius: 12px;
            margin: 24px 0;
            border-left: 4px solid;
        }
        
        .info-box h3 {
            margin: 0 0 12px 0;
            font-size: 16px;
            font-weight: 700;
        }
        
        .warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left-color: #f59e0b;
            color: #92400e;
        }
        
        .info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-left-color: #3b82f6;
            color: #1e40af;
        }
        
        .success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left-color: #10b981;
            color: #065f46;
        }
        
        /* Footer */
        .footer {
            background: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer-content {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .footer-content p {
            margin-bottom: 8px;
        }
        
        .footer-content strong {
            color: #1e293b;
        }
        
        .disclaimer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #94a3b8;
        }
        
        /* Responsive Design */
        @media (max-width: 600px) {
            .email-wrapper {
                margin: 10px;
                border-radius: 16px;
            }
            
            .header, .content {
                padding: 30px 20px;
            }
            
            .details {
                padding: 20px;
            }
        }
        
        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-in {
            animation: slideIn 0.8s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="email-wrapper fade-in">
        <!-- Header Section -->
        <div class="header">
            <div class="header-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            </div>
            <div class="logo">SuGanta</div>
            <div class="tagline">Empowering Education Through Technology</div>
        </div>

        <!-- Content Section -->
        <div class="content">
            <!-- Greeting -->
            <div class="greeting">
                {{ $greeting ?? 'Hello there!' }}
            </div>

            <!-- Main Message -->
            <div class="message">
                {{ $message ?? 'You have a new notification from SuGanta.' }}
            </div>

            <!-- Activity Details -->
            @if(isset($details) && !empty($details))
            <div class="details priority-{{ $priority ?? 'normal' }} slide-in">
                <h3>📋 Activity Details</h3>
                @foreach($details as $key => $value)
                    @if(is_string($value) && !empty($value))
                    <div class="detail-item">
                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}
                    </div>
                    @endif
                @endforeach
            </div>
            @endif

            <!-- Additional Information -->
            @if(isset($additionalInfo) && !empty($additionalInfo))
            <div class="info-box info slide-in">
                <h3>📋 Additional Information</h3>
                @foreach($additionalInfo as $key => $value)
                    @if(is_string($value) && !empty($value))
                    <div class="detail-item" style="background: transparent; padding: 8px 0; border: none;">
                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}
                    </div>
                    @endif
                @endforeach
            </div>
            @endif

            <!-- Action Button -->
            @if(isset($actionUrl) && !empty($actionUrl))
            <div class="action-section slide-in">
                <a href="{{ $actionUrl }}" class="action-button">
                    {{ $actionText ?? 'View Details' }}
                </a>
            </div>
            @endif

            <!-- Footer Message -->
            @if(isset($footerMessage) && !empty($footerMessage))
            <div class="message slide-in">
                {{ $footerMessage }}
            </div>
            @endif

            <!-- Security Warning -->
            @if(isset($securityWarning) && $securityWarning)
            <div class="info-box warning slide-in">
                <h3>🔒 Security Notice</h3>
                <p>If you did not initiate this action, please contact our support team immediately to secure your account.</p>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-content">
                <p><strong>Thank you for using SuGanta!</strong></p>
                <p>We're committed to providing you with the best educational experience.</p>
                <p>If you have any questions, please contact our support team.</p>
            </div>
            <div class="disclaimer">
                <p>This is an automated notification. Please do not reply to this email.</p>
                <p>For immediate assistance, please contact our support team directly.</p>
            </div>
        </div>
    </div>
</body>
</html>
