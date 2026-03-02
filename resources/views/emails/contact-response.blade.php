<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Response to Your Inquiry</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-name {
            color: #667eea;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .subtitle {
            color: #666;
            font-size: 16px;
            margin: 5px 0 0 0;
        }
        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            color: #667eea;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }
        .original-message {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-left: 4px solid #667eea;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .original-message-title {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .response-message {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-left: 4px solid #667eea;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
            white-space: pre-wrap;
        }
        .response-message-title {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .contact-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
        .contact-info h3 {
            color: #667eea;
            margin-top: 0;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #5a6fd8;
        }
        .signature {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .signature-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .signature-title {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="company-name">{{ $companyInfo['name'] }}</h1>
            <p class="subtitle">Response to Your Inquiry</p>
        </div>

        <div class="greeting">
            <p>Dear {{ $contact->first_name }} {{ $contact->last_name }},</p>
            <p>Thank you for contacting {{ $companyInfo['name'] }}. We have received your inquiry and are pleased to provide you with the following response:</p>
        </div>

        <div class="section">
            <div class="original-message">
                <div class="original-message-title">Your Original Message:</div>
                <div><strong>Subject:</strong> {{ $contact->subject }}</div>
                <div style="margin-top: 10px;">{{ $contact->message }}</div>
            </div>
        </div>

        @if($responseMessage)
        <div class="section">
            <div class="response-message">
                <div class="response-message-title">Our Response:</div>
                <div>{{ $responseMessage }}</div>
            </div>
        </div>
        @else
        <div class="section">
            <div class="response-message">
                <div class="response-message-title">Our Response:</div>
                <div>Thank you for your inquiry. We have received your message and our team will get back to you shortly. If you have any urgent questions, please feel free to contact us directly.</div>
            </div>
        </div>
        @endif

        <div class="contact-info">
            <h3>Need Further Assistance?</h3>
            <p>If you have any additional questions or concerns, please don't hesitate to reach out to us:</p>
            @if($companyInfo['email'])
            <p><strong>Email:</strong> <a href="mailto:{{ $companyInfo['email'] }}">{{ $companyInfo['email'] }}</a></p>
            @endif
            @if($companyInfo['phone'])
            <p><strong>Phone:</strong> <a href="tel:{{ $companyInfo['phone'] }}">{{ $companyInfo['phone'] }}</a></p>
            @endif
            @if($companyInfo['website'])
            <p><strong>Website:</strong> <a href="{{ $companyInfo['website'] }}">{{ $companyInfo['website'] }}</a></p>
            @endif
        </div>

        <div class="signature">
            <div class="signature-name">{{ $adminName }}</div>
            <div class="signature-title">{{ $companyInfo['name'] }} Support Team</div>
        </div>

        <div class="footer">
            <p>This is an automated response to your inquiry submitted on {{ $contact->created_at->format('F j, Y \a\t g:i A') }}.</p>
            <p>If you did not submit this inquiry, please ignore this email.</p>
            <p>&copy; {{ date('Y') }} {{ $companyInfo['name'] }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

