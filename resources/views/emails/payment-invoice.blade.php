<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Payment Receipt & Invoice</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1a202c;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .email-wrapper {
            max-width: 680px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        /* Header Section */
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .email-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .logo-wrapper {
            background: white;
            display: inline-block;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 1;
        }

        .logo {
            max-width: 180px;
            height: auto;
            display: block;
        }

        .header-title {
            color: #ffffff;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-subtitle {
            color: rgba(255, 255, 255, 0.95);
            font-size: 18px;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }

        /* Success Badge */
        .success-badge {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 20px;
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.4);
        }

        .checkmark {
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #38a169;
            font-size: 16px;
            font-weight: bold;
        }

        /* Content Section */
        .email-content {
            padding: 50px 40px;
        }

        .greeting {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .intro-text {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        /* Payment Summary Card */
        .payment-card {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            border: 2px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .payment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .payment-card-title {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #cbd5e0;
        }

        .payment-row:last-child {
            border-bottom: none;
        }

        .payment-label {
            font-size: 15px;
            color: #718096;
            font-weight: 500;
        }

        .payment-value {
            font-size: 15px;
            color: #2d3748;
            font-weight: 600;
            text-align: right;
        }

        .payment-value.highlight {
            font-size: 18px;
            color: #667eea;
        }

        .order-id-code {
            background: #ffffff;
            padding: 4px 10px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border: 1px solid #e2e8f0;
        }

        /* Download Button */
        .download-section {
            text-align: center;
            margin: 40px 0;
            padding: 30px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            border-radius: 12px;
        }

        .download-button {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            border: none;
        }

        .download-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(102, 126, 234, 0.5);
        }

        .download-icon {
            width: 24px;
            height: 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .download-text {
            margin-top: 15px;
            font-size: 14px;
            color: #718096;
        }

        /* Transaction Details */
        .transaction-details {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }

        .transaction-title {
            font-size: 18px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .detail-item {
            margin-bottom: 12px;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            font-size: 14px;
            color: #718096;
            font-weight: 500;
            display: block;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 15px;
            color: #2d3748;
            font-weight: 600;
        }

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, #ebf8ff 0%, #e6fffa 100%);
            border-left: 4px solid #4299e1;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }

        .info-box-title {
            font-size: 16px;
            font-weight: 700;
            color: #2c5282;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box-text {
            font-size: 14px;
            color: #2c5282;
            line-height: 1.6;
        }

        /* Support Section */
        .support-section {
            background: linear-gradient(135deg, #fef5e7 0%, #fdebd0 100%);
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: center;
        }

        .support-title {
            font-size: 18px;
            font-weight: 700;
            color: #744210;
            margin-bottom: 12px;
        }

        .support-text {
            font-size: 14px;
            color: #975a16;
            margin-bottom: 15px;
        }

        .support-contact {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .contact-item {
            font-size: 14px;
            color: #744210;
        }

        .contact-label {
            font-weight: 700;
            margin-right: 5px;
        }

        /* Footer */
        .email-footer {
            background: #2d3748;
            padding: 40px;
            text-align: center;
            color: #cbd5e0;
        }

        .footer-logo-wrapper {
            margin-bottom: 20px;
        }

        .footer-logo {
            max-width: 150px;
            height: auto;
            opacity: 0.9;
        }

        .footer-text {
            font-size: 14px;
            margin-bottom: 10px;
            color: #a0aec0;
        }

        .footer-links {
            margin: 20px 0;
        }

        .footer-link {
            color: #667eea;
            text-decoration: none;
            margin: 0 12px;
            font-size: 14px;
            font-weight: 500;
        }

        .footer-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .footer-copyright {
            margin-top: 20px;
            font-size: 13px;
            color: #718096;
        }

        .social-icons {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            background: rgba(102, 126, 234, 0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            text-decoration: none;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
        }

        /* Responsive Design */
        @media only screen and (max-width: 640px) {
            body {
                padding: 10px;
            }

            .email-header {
                padding: 40px 20px;
            }

            .header-title {
                font-size: 26px;
            }

            .header-subtitle {
                font-size: 16px;
            }

            .email-content {
                padding: 30px 20px;
            }

            .greeting {
                font-size: 20px;
            }

            .payment-card {
                padding: 20px;
            }

            .download-button {
                padding: 16px 30px;
                font-size: 16px;
                width: 100%;
                justify-content: center;
            }

            .support-contact {
                flex-direction: column;
                gap: 10px;
            }

            .email-footer {
                padding: 30px 20px;
            }

            .footer-link {
                display: block;
                margin: 8px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Header -->
        <div class="email-header">
            <div class="logo-wrapper">
                <img src="{{ asset('logo/Su250.png') }}" alt="{{ config('app.name', 'SuGanta') }}" class="logo">
            </div>
            <h1 class="header-title">🎉 Payment Successful!</h1>
            <p class="header-subtitle">Your transaction has been completed successfully</p>
            <div class="success-badge">
                <span class="checkmark">✓</span>
                <span>Payment Confirmed</span>
            </div>
        </div>

        <!-- Main Content -->
        <div class="email-content">
            <h2 class="greeting">Hello {{ $userData->name }}! 👋</h2>
            <p class="intro-text">
                Thank you for your payment! We're pleased to confirm that your transaction has been processed successfully. 
                Your invoice is ready and you can download it anytime using the button below.
            </p>

            <!-- Payment Summary Card -->
            <div class="payment-card">
                <h3 class="payment-card-title">
                    💳 Payment Summary
                </h3>
                
                <div class="payment-row">
                    <span class="payment-label">Order ID</span>
                    <span class="payment-value">
                        <span class="order-id-code">{{ $paymentData->order_id }}</span>
                    </span>
                </div>

                @if($paymentData->reference_id)
                <div class="payment-row">
                    <span class="payment-label">Transaction ID</span>
                    <span class="payment-value">{{ $paymentData->reference_id }}</span>
                </div>
                @endif

                <div class="payment-row">
                    <span class="payment-label">Amount Paid</span>
                    <span class="payment-value highlight">{{ $paymentData->currency }} {{ number_format($paymentData->amount, 2) }}</span>
                </div>

                <div class="payment-row">
                    <span class="payment-label">Payment Date</span>
                    <span class="payment-value">
                        {{ $paymentData->paid_at ? $paymentData->paid_at->format('F d, Y • h:i A') : $paymentData->created_at->format('F d, Y • h:i A') }}
                    </span>
                </div>

                @if($paymentData->meta && isset($paymentData->meta['type']))
                <div class="payment-row">
                    <span class="payment-label">Payment Type</span>
                    <span class="payment-value">{{ ucfirst(str_replace('_', ' ', $paymentData->meta['type'])) }}</span>
                </div>
                @endif

                <div class="payment-row">
                    <span class="payment-label">Status</span>
                    <span class="payment-value" style="color: #48bb78;">✓ Completed</span>
                </div>
            </div>

            <!-- Download Invoice Button -->
            <div class="download-section">
                <a href="{{ $invoiceDownloadUrl }}" class="download-button">
                    <span class="download-icon">📥</span>
                    <span>Download Your Invoice</span>
                </a>
                <p class="download-text">
                    Click the button above to download your invoice in PDF format
                </p>
            </div>

            <!-- Transaction Details -->
            <div class="transaction-details">
                <h3 class="transaction-title">📋 Transaction Details</h3>
                
                <div class="detail-item">
                    <span class="detail-label">Recipient</span>
                    <span class="detail-value">{{ $userData->name }}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Email</span>
                    <span class="detail-value">{{ $userData->email }}</span>
                </div>

                @if($userData->phone)
                <div class="detail-item">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value">{{ $userData->phone }}</span>
                </div>
                @endif

                <div class="detail-item">
                    <span class="detail-label">Invoice Number</span>
                    <span class="detail-value">SUGINTL-{{ $paymentData->id }}</span>
                </div>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <div class="info-box-title">
                    ℹ️ Important Information
                </div>
                <p class="info-box-text">
                    This invoice serves as your official receipt for the payment. Please keep it for your records. 
                    You can download it anytime using the link provided in this email. If you have any questions 
                    or concerns, our support team is here to help you.
                </p>
            </div>

            <!-- Support Section -->
            <div class="support-section">
                <h3 class="support-title">💬 Need Help?</h3>
                <p class="support-text">
                    Our support team is available to assist you with any questions or concerns
                </p>
                <div class="support-contact">
                    @if(isset($companyData['contact']['email']))
                    <div class="contact-item">
                        <span class="contact-label">Email:</span>
                        <span>{{ $companyData['contact']['email'] }}</span>
                    </div>
                    @endif
                    
                    @if(isset($companyData['contact']['phone']))
                    <div class="contact-item">
                        <span class="contact-label">Phone:</span>
                        <span>{{ $companyData['contact']['phone'] }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-logo-wrapper">
                <img src="{{ asset('logo/Su250.png') }}" alt="{{ config('app.name', 'SuGanta') }}" class="footer-logo">
            </div>
            
            <p class="footer-text">
                Thank you for choosing {{ config('app.name', 'SuGanta') }}!
            </p>
            
            <p class="footer-text">
                This is an automated email. Please do not reply to this message.
            </p>

            <div class="footer-links">
                <a href="{{ config('app.url') }}" class="footer-link">Visit Website</a>
                <a href="{{ config('app.url') }}/contact" class="footer-link">Contact Support</a>
                <a href="{{ config('app.url') }}/payment/history" class="footer-link">Payment History</a>
            </div>

            <div class="social-icons">
                <a href="#" class="social-icon" title="Facebook">📘</a>
                <a href="#" class="social-icon" title="Twitter">🐦</a>
                <a href="#" class="social-icon" title="LinkedIn">💼</a>
                <a href="#" class="social-icon" title="Instagram">📷</a>
            </div>

            <p class="footer-copyright">
                © {{ date('Y') }} {{ config('app.name', 'SuGanta') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>

