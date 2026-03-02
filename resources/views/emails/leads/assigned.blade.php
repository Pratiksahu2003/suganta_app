<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Learner Want To Connect With YOU</title>
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
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .item { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; }
        .item .label { font-size: 12px; color: #64748b; margin-bottom: 4px; }
        .item .value { font-weight: 600; color: #1e293b; }
        .action { text-align: center; margin: 28px 0 6px; }
        .btn { display: inline-block; padding: 12px 22px; border-radius: 12px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #fff !important; text-decoration: none; font-weight: 700; box-shadow: 0 10px 25px -5px rgba(79,70,229,0.4); }
        .footer { background: #f8fafc; padding: 20px; text-align: center; color: #64748b; font-size: 14px; border-top: 1px solid #e2e8f0; }
        @media (max-width: 600px) { .content { padding: 20px; } .row { grid-template-columns: 1fr; } }
    </style>
    </head>
    <body>
        <div class="email-wrapper">
            <div class="header">
                    <img src="{{ asset('logo/Su250.png') }}" alt="{{ config('app.name') }}" class="logo-img">
            </div>

            <div class="content">
                <div class="greeting">New Learner Want To Connect With YOU</div>
                <div class="message">A new Learner has been assigned to you. Here are the details:</div>

                <div class="panel">
                    <div class="row">
                        <div class="item">
                            <div class="label">SUG ID</div>
                            <div class="value">{{ $lead->lead_id ?? $lead->id }}</div>
                        </div>
                        <div class="item">
                            <div class="label">Status</div>
                            <div class="value">{{ ucfirst($lead->status ?? 'new') }}</div>
                        </div>
                        <div class="item">
                            <div class="label">Name</div>
                            <div class="value">{{ $lead->name ?? '—' }}</div>
                        </div>
                        <div class="item">
                            <div class="label">Email</div>
                            <div class="value">{{ $lead->email ?? '—' }}</div>
                        </div>
                        <div class="item">
                            <div class="label">Phone</div>
                            <div class="value">{{ $lead->phone ?? '—' }}</div>
                        </div>
                        <div class="item">
                            <div class="label">Source</div>
                            <div class="value">{{ $lead->source ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                @if(!empty($lead->message))
                    <div class="panel" style="background:#fff;">
                        <div class="label" style="margin-bottom:6px;">Message</div>
                        <div class="value" style="font-weight:500; color:#334155;">{{ $lead->message }}</div>
                    </div>
                @endif

                <div class="action">
                    <a href="{{ $url }}" class="btn">View Lead</a>
                </div>
            </div>

            <div class="footer">
                <div><strong>Thank you for using {{ config('app.name') }}!</strong></div>
                <div>This is an automated notification. Please do not reply to this email.</div>
            </div>
        </div>
    </body>
    </html>


