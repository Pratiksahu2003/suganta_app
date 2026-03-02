<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission</title>
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
        .field {
            margin-bottom: 15px;
        }
        .field-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 120px;
        }
        .field-value {
            color: #333;
        }
        .message-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
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
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #5a6fd8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="company-name">{{ $companyInfo['name'] }}</h1>
            <p class="subtitle">New Contact Form Submission</p>
        </div>

        <div class="section">
            <h2 class="section-title">Contact Information</h2>
            <div class="field">
                <span class="field-label">Name:</span>
                <span class="field-value">{{ $contact->first_name }} {{ $contact->last_name }}</span>
            </div>
            <div class="field">
                <span class="field-label">Email:</span>
                <span class="field-value">{{ $contact->email }}</span>
            </div>
            @if($contact->phone)
            <div class="field">
                <span class="field-label">Phone:</span>
                <span class="field-value">{{ $contact->phone }}</span>
            </div>
            @endif
            <div class="field">
                <span class="field-label">Subject:</span>
                <span class="field-value">{{ ucfirst($contact->subject) }}</span>
            </div>
            <div class="field">
                <span class="field-label">Submitted:</span>
                <span class="field-value">{{ $contact->created_at->format('F j, Y \a\t g:i A') }}</span>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Message</h2>
            <div class="message-box">
                {{ $contact->message }}
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Technical Details</h2>
            <div class="field">
                <span class="field-label">IP Address:</span>
                <span class="field-value">{{ $contact->ip_address }}</span>
            </div>
            <div class="field">
                <span class="field-label">User Agent:</span>
                <span class="field-value">{{ Str::limit($contact->user_agent, 100) }}</span>
            </div>
        </div>

        <div class="contact-info">
            <h3>Quick Actions</h3>
            <p><strong>Reply to:</strong> <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></p>
            <p><strong>Call:</strong> <a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a></p>
            
            <a href="{{ route('admin.contacts.show', $contact->id) }}" class="btn">View in Admin Panel</a>
        </div>

        <div class="footer">
            <p>This email was sent automatically from the {{ $companyInfo['name'] }} contact form.</p>
            <p>Please respond to this inquiry within 24 hours as per our service standards.</p>
            <p>&copy; {{ date('Y') }} {{ $companyInfo['name'] }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
