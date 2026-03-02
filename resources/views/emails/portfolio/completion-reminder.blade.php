<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Portfolio - {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #1f2937; background: #f3f4f6; padding: 20px; }
        .email-wrapper { max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; }
        .header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; padding: 30px 24px; text-align: center; position: relative; }
        .logo-img { height: 42px; display: block; margin: 0 auto 8px; }
        .brand { font-size: 28px; font-weight: 800; }
        .content { padding: 32px 24px; }
        .greeting { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
        .message { color: #4b5563; margin-bottom: 20px; }
        .panel { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin: 20px 0; }
        .progress-bar { background: #e2e8f0; border-radius: 10px; height: 24px; margin: 12px 0; overflow: hidden; position: relative; }
        .progress-fill { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); height: 100%; border-radius: 10px; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600; }
        .completion-text { text-align: center; font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
        .completion-percentage { font-size: 32px; font-weight: 800; color: #4f46e5; }
        .action { text-align: center; margin: 28px 0 6px; }
        .btn { display: inline-block; padding: 12px 22px; border-radius: 12px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #fff !important; text-decoration: none; font-weight: 700; box-shadow: 0 10px 25px -5px rgba(79,70,229,0.4); }
        .footer { background: #f8fafc; padding: 20px; text-align: center; color: #64748b; font-size: 14px; border-top: 1px solid #e2e8f0; }
        .benefits { list-style: none; padding: 0; margin: 20px 0; }
        .benefits li { padding: 10px 0; padding-left: 30px; position: relative; color: #4b5563; }
        .benefits li:before { content: "✓"; position: absolute; left: 0; color: #10b981; font-weight: bold; font-size: 18px; }
        @media (max-width: 600px) { .content { padding: 20px; } }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <img src="{{ $logoUrl ?? asset('logo/Su250.png') }}" alt="{{ config('app.name') }}" class="logo-img">
        </div>

        <div class="content">
            <div class="greeting">Hello {{ $userName }}!</div>
            <div class="message">
                We noticed that your portfolio is not yet complete. Complete your portfolio to unlock more opportunities and get better visibility on {{ config('app.name') }}!
            </div>

            <div class="panel">
                <div class="completion-text">Your Portfolio Status</div>
                @if($hasPortfolio)
                    <div style="text-align: center; font-size: 18px; font-weight: 600; color: #1e293b; margin: 12px 0;">
                        @if($portfolioStatus === 'published')
                            <span style="color: #10b981;">✅ Published</span>
                        @elseif($portfolioStatus === 'draft')
                            <span style="color: #f59e0b;">📝 Draft</span>
                        @else
                            <span style="color: #6b7280;">📦 {{ ucfirst($portfolioStatus) }}</span>
                        @endif
                    </div>
                    @if($portfolio && $portfolio->title)
                        <div style="text-align: center; color: #4b5563; margin-top: 8px;">
                            <strong>Title:</strong> {{ $portfolio->title }}
                        </div>
                    @endif
                @else
                    <div style="text-align: center; font-size: 18px; font-weight: 600; color: #ef4444; margin: 12px 0;">
                        ❌ No Portfolio Created
                    </div>
                    <div style="text-align: center; color: #6b7280; margin-top: 8px;">
                        You haven't created your portfolio yet
                    </div>
                @endif
            </div>

            <div class="panel" style="background: #fff;">
                <div style="font-weight: 600; margin-bottom: 12px; color: #1e293b;">Complete your portfolio to:</div>
                <ul class="benefits">
                    <li>Get more visibility and opportunities</li>
                    <li>Build trust with potential clients/students</li>
                    <li>Showcase your skills and experience</li>
                    <li>Increase your chances of getting hired</li>
                    <li>Access premium features and benefits</li>
                </ul>
            </div>

            <div class="action">
                <a href="{{ $url }}" class="btn">Complete My Portfolio Now</a>
            </div>

            <div style="margin-top: 24px; padding: 16px; background: #fef3c7; border-radius: 12px; border-left: 4px solid #f59e0b;">
                <div style="font-weight: 600; color: #92400e; margin-bottom: 4px;">💡 Quick Tip</div>
                <div style="color: #78350f; font-size: 14px;">
                    @if(!$hasPortfolio)
                        Create your portfolio with a title, description, and images to showcase your work!
                    @elseif($portfolioStatus === 'draft')
                        Don't forget to publish your portfolio! Add a title, description, and set the status to "Published" to make it visible.
                    @else
                        Make sure your portfolio has a clear title, detailed description, and relevant images to attract more opportunities!
                    @endif
                </div>
            </div>
        </div>

        <div class="footer">
            <div><strong>Thank you for using {{ config('app.name') }}!</strong></div>
            <div>This is an automated notification. Please do not reply to this email.</div>
            <div style="margin-top: 12px; font-size: 12px;">
                If you have any questions, please contact our support team.
            </div>
        </div>
    </div>
</body>
</html>

