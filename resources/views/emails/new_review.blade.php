<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Review Received - {{ config('app.name') }}</title>
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

        .logo-container {
            margin-bottom: 20px;
        }

        .logo {
            width: 80px;
            height: 40px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img {
            max-width: 100%;
            height: auto;
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

        .review-container {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
        }

        .review-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .reviewer-avatar {
            width: 40px;
            height: 40px;
            background-color: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            font-size: 18px;
        }

        .reviewer-info {
            flex: 1;
        }

        .reviewer-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 16px;
        }

        .review-date {
            font-size: 12px;
            color: #718096;
        }

        .star-rating {
            color: #fbbf24;
            font-size: 20px;
            margin-bottom: 15px;
            letter-spacing: 2px;
        }

        .review-title {
            font-weight: 600;
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .review-body {
            color: #4a5568;
            font-style: italic;
            margin-bottom: 20px;
            position: relative;
            padding-left: 15px;
            border-left: 3px solid #cbd5e0;
        }

        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .info-box {
            background: linear-gradient(135deg, #ebf8ff 0%, #e6fffa 100%);
            border-left: 4px solid #3182ce;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .info-box h3 {
            color: #2c5282;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .info-box h3::before {
            content: "💡";
            margin-right: 8px;
        }

        .info-box p {
            color: #2a4365;
            font-size: 14px;
            margin: 0;
            line-height: 1.6;
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

            .review-container {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <img src="https://www.suganta.com/logo/Su250.png" alt="{{ config('app.name') }}">
                </div>
            </div>
            <h1>New Review Received!</h1>
            <p>Someone has shared their experience with you</p>
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="greeting">Hello {{ $notifiable->name ?? 'User' }}! 👋</div>

            <div class="message">
                Great news! You have received a new review on your profile. Feedback helps build trust and credibility in our community.
            </div>

            <!-- Review Display -->
            <div class="review-container">
                <div class="review-header">
                    <div class="reviewer-avatar">
                        {{ strtoupper(substr($reviewer->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="reviewer-info">
                        <div class="reviewer-name">{{ $reviewer->name ?? 'Anonymous User' }}</div>
                        <div class="review-date">Just now</div>
                    </div>
                </div>

                <div class="star-rating">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= $review->rating)
                            ★
                        @else
                            ☆
                        @endif
                    @endfor
                </div>

                @if($review->title)
                    <div class="review-title">{{ $review->title }}</div>
                @endif

                <div class="review-body">
                    "{{ \Illuminate\Support\Str::limit($review->comment, 200) }}"
                </div>
            </div>

            <!-- Tips Info Box -->
            <div class="info-box">
                <h3>Pro Tip</h3>
                <p>
                    Responding to reviews shows that you value feedback. A thoughtful reply can turn a good review into a great relationship and address any concerns raised in critical feedback.
                </p>
            </div>

            <div class="message">
                Keep up the great work! Consistent positive reviews can significantly improve your visibility on {{ config('app.name') }}.
            </div>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <p>Best regards,</p>
            <p><strong>{{ config('app.name') }} Community Team</strong></p>

            <div class="social-links">
                <a href="{{ config('app.url') }}">Visit Website</a>
                <a href="{{ config('app.contact_url') }}">Contact Support</a>
                <a href="{{ config('app.help_center_url') }}">Help Center</a>
            </div>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <p style="font-size: 12px; color: #a0aec0;">
                    You received this email because you are a registered user on {{ config('app.name') }}. 
                    To manage your notification preferences, visit your account settings.
                </p>
            </div>
        </div>
    </div>
</body>

</html>
