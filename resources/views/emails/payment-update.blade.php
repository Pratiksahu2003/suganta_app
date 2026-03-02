<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Payment Update' }}</title>
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
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .logo-container {
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo {
            max-width: 160px;
            width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
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

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .status-success {
            background-color: #d1fae5;
            color: #059669;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-failed {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .status-initiated {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .payment-details {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #4f46e5;
        }

        .payment-details h3 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #4b5563;
        }

        .detail-value {
            color: #1f2937;
            text-align: right;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .button {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
        }

        .invoice-button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            margin-left: 10px;
        }

        .invoice-button:hover {
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer p {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .footer a {
            color: #4f46e5;
            text-decoration: none;
        }

        .support-info {
            background-color: #eff6ff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }

        .support-info h4 {
            font-size: 16px;
            color: #1e40af;
            margin-bottom: 10px;
        }

        .support-info p {
            font-size: 14px;
            color: #1e3a8a;
            margin: 5px 0;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                border-radius: 0 !important;
            }

            .content {
                padding: 30px 20px !important;
            }

            .button {
                display: block;
                width: 100%;
                margin: 10px 0 !important;
            }

            .invoice-button {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <img src="{{ asset('logo/Su250.png') }}" alt="{{ config('app.name', 'SuGanta') }} Logo" class="logo">
            </div>
            <h1>{{ $title ?? 'Payment Update' }}</h1>
            <p>Your payment status has been updated</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hello {{ $userName ?? 'Customer' }}!
            </div>

            <div class="message">
                {{ $emailMessage ?? 'Your payment status has been updated.' }}
            </div>

            <!-- Status Badge -->
            @if(isset($status))
            <div class="status-badge status-{{ $status }}">
                {{ ucfirst($status) }}
            </div>
            @endif

            <!-- Payment Details -->
            <div class="payment-details">
                <h3>📋 Payment Details</h3>
                
                @if(isset($orderId))
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value"><code>{{ $orderId }}</code></span>
                </div>
                @endif

                @if(isset($transactionId))
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value">{{ $transactionId }}</span>
                </div>
                @endif

                @if(isset($amount) && isset($currency))
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value"><strong>{{ $currency }} {{ number_format($amount, 2) }}</strong></span>
                </div>
                @endif

                @if(isset($paymentDate))
                <div class="detail-row">
                    <span class="detail-label">Payment Date:</span>
                    <span class="detail-value">{{ $paymentDate }}</span>
                </div>
                @endif

                @if(isset($paymentType))
                <div class="detail-row">
                    <span class="detail-label">Payment Type:</span>
                    <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $paymentType)) }}</span>
                </div>
                @endif

                @if(isset($reason))
                <div class="detail-row">
                    <span class="detail-label">Reason:</span>
                    <span class="detail-value">{{ $reason }}</span>
                </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="button-container">
                @if(isset($invoiceUrl) && $status === 'success')
                <a href="{{ $invoiceUrl }}" class="button invoice-button" target="_blank">
                    📥 Download Invoice
                </a>
                @endif

                @if(isset($actionUrl))
                <a href="{{ $actionUrl }}" class="button" target="_blank">
                    {{ $actionText ?? 'View Details' }}
                </a>
                @endif
            </div>

            <!-- Support Information -->
            <div class="support-info">
                <h4>💬 Need Help?</h4>
                <p>If you have any questions or concerns about this payment, please don't hesitate to contact our support team.</p>
                <p><strong>Email:</strong> {{ config('company.contact.email', 'support@SuGanta.com') }}</p>
                <p><strong>Phone:</strong> {{ config('company.contact.phone', 'N/A') }}</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Thank you for choosing SuGanta!</strong></p>
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>
                <a href="{{ config('app.url') }}">Visit our website</a> | 
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
            </p>
            <p style="margin-top: 20px; font-size: 12px; color: #9ca3af;">
                © {{ date('Y') }} SuGanta. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>

