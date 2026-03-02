<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Support Ticket Update' }}</title>
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
        
        /* Status and Priority Badges */
        .status-badges {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }
        
        .status-open { background: #dbeafe; color: #1e40af; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-resolved { background: #d1fae5; color: #065f46; }
        .status-closed { background: #f3f4f6; color: #374151; }
        .status-in_progress { background: #e0e7ff; color: #3730a3; }
        .status-waiting_for_user { background: #fef3c7; color: #92400e; }
        
        .priority-high { background: #fee2e2; color: #991b1b; }
        .priority-medium { background: #fef3c7; color: #92400e; }
        .priority-low { background: #d1fae5; color: #065f46; }
        .priority-urgent { background: #fef2f2; color: #dc2626; }
        
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
        
        /* Ticket Details Card */
        .ticket-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px;
            margin: 32px 0;
            position: relative;
            overflow: hidden;
        }
        
        .ticket-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .ticket-id {
            font-size: 20px;
            font-weight: 800;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .ticket-id::before {
            content: '#';
            color: #4f46e5;
            font-weight: 900;
        }
        
        .ticket-date {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }
        
        .ticket-subject {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 24px;
            line-height: 1.4;
        }
        
        /* Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .detail-item {
            background: white;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .detail-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .detail-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        
        .detail-value {
            font-size: 15px;
            color: #1e293b;
            font-weight: 600;
        }
        
        /* Description Section */
        .description-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-top: 20px;
        }
        
        .description-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .description-text {
            font-size: 14px;
            color: #374151;
            line-height: 1.6;
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
        
        .urgency-notice {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left-color: #f59e0b;
            color: #92400e;
        }
        
        .response-time {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-left-color: #3b82f6;
            color: #1e40af;
        }
        
        .additional-info {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-left-color: #0ea5e9;
            color: #0c4a6e;
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
            
            .details-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .ticket-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .status-badges {
                flex-direction: column;
                align-items: center;
            }
            
            .badge {
                width: 100%;
                justify-content: center;
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
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-5 14H4v-4h11v4zm0-5H4V9h11v4zm5 5h-4V9h4v9z"/>
                </svg>
            </div>
            <div class="logo">SuGanta</div>
            <div class="tagline">Premium Educational Support</div>
        </div>

        <!-- Content Section -->
        <div class="content">
            <!-- Status and Priority Badges -->
            @if(isset($ticket))
            <div class="status-badges slide-in">
                <span class="badge status-{{ strtolower($ticket->status ?? 'open') }}">
                    {{ ucfirst(str_replace('_', ' ', $ticket->status ?? 'Open')) }}
                </span>
                <span class="badge priority-{{ strtolower($ticket->priority ?? 'normal') }}">
                    {{ ucfirst($ticket->priority ?? 'Normal') }} Priority
                </span>
            </div>
            @endif

            <!-- Greeting -->
            <div class="greeting">
                {{ $greeting ?? 'Hello there!' }}
            </div>

            <!-- Main Message -->
            <div class="message">
                {{ $message ?? 'You have a new support ticket update from SuGanta.' }}
            </div>

            <!-- Ticket Details Card -->
            @if(isset($ticket))
            <div class="ticket-card slide-in">
                <div class="ticket-header">
                    <div class="ticket-id">{{ $ticket->id }}</div>
                    <div class="ticket-date">
                        {{ $ticket->created_at ? $ticket->created_at->format('M d, Y \a\t g:i A') : 'N/A' }}
                    </div>
                </div>

                <div class="ticket-subject">
                    {{ $ticket->subject ?? 'N/A' }}
                </div>

                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Category</div>
                        <div class="detail-value">{{ ucfirst($ticket->category ?? 'General') }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">{{ ucfirst(str_replace('_', ' ', $ticket->status ?? 'Open')) }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Priority</div>
                        <div class="detail-value">{{ ucfirst($ticket->priority ?? 'Normal') }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Created</div>
                        <div class="detail-value">{{ $ticket->created_at ? $ticket->created_at->format('M d, Y') : 'N/A' }}</div>
                    </div>
                </div>

                @if(isset($ticket->message) && !empty($ticket->message))
                <div class="description-section">
                    <div class="description-label">Message</div>
                    <div class="description-text">
                        {{ strlen($ticket->message) > 300 ? substr($ticket->message, 0, 300) . '...' : $ticket->message }}
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Additional Information -->
            @if(isset($additionalInfo) && !empty($additionalInfo))
            <div class="info-box additional-info slide-in">
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
                    {{ $actionText ?? 'View Ticket Details' }}
                </a>
            </div>
            @endif

            <!-- Footer Message -->
            @if(isset($footerMessage) && !empty($footerMessage))
            <div class="message slide-in">
                {{ $footerMessage }}
            </div>
            @endif

            <!-- High Priority Notice -->
            @if(isset($ticket) && $ticket->priority === 'high')
            <div class="info-box urgency-notice slide-in">
                <h3>🚨 High Priority Notice</h3>
                <p>This ticket has been marked as high priority. Our support team will respond to it as soon as possible to ensure your issue is resolved quickly.</p>
            </div>
            @endif

            <!-- Response Time Information -->
            <div class="info-box response-time slide-in">
                <h3>⏱️ Response Time</h3>
                <p><strong>Standard Response:</strong> We typically respond to support tickets within 24 hours.</p>
                <p><strong>Urgent Matters:</strong> For critical issues, please contact us directly through our support channels.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-content">
                <p><strong>Thank you for choosing SuGanta!</strong></p>
                <p>Our dedicated support team is here to help you succeed.</p>
                <p>We're committed to providing you with the best educational experience possible.</p>
            </div>
            <div class="disclaimer">
                <p>This is an automated notification. Please do not reply to this email.</p>
                <p>If you need immediate assistance, please contact our support team directly.</p>
            </div>
        </div>
    </div>
</body>
</html>
