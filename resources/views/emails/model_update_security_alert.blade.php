<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Alert - {{ config('app.name') }}</title>
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

        .activity-box {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 2px dashed #667eea;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }

        .event-label {
            font-size: 14px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .event-title {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
            margin: 15px 0 20px;
        }

        .event-meta {
            font-size: 14px;
            color: #4a5568;
            line-height: 1.8;
            margin-top: 10px;
            text-align: left;
            display: inline-block;
        }

        .event-meta strong {
            color: #2d3748;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 15px 35px;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        /* ---- Changed fields section ---- */
        .changes-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
        }

        .changes-heading {
            font-size: 14px;
            color: #2d3748;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .changes-heading .count-pill {
            background: #edf2f7;
            color: #667eea;
            padding: 3px 12px;
            border-radius: 999px;
            font-size: 11px;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        .change-item {
            padding: 14px 0;
            border-bottom: 1px solid #edf2f7;
        }
        .change-item:last-child { border-bottom: none; }
        .change-item:first-child { padding-top: 0; }

        .field-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            text-transform: capitalize;
        }

        .type-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2px 8px;
            border-radius: 4px;
            background: #edf2f7;
            color: #4a5568;
        }
        .type-badge.number { background: #e6fffa; color: #2c7a7b; }
        .type-badge.boolean { background: #fefcbf; color: #975a16; }
        .type-badge.datetime { background: #e9d8fd; color: #6b46c1; }
        .type-badge.json { background: #bee3f8; color: #2c5282; }
        .type-badge.null { background: #fed7d7; color: #c53030; }

        .value-row {
            display: table;
            width: 100%;
            border-spacing: 6px 0;
        }

        .value-cell {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
            padding: 10px 12px;
            border-radius: 8px;
            font-family: 'SFMono-Regular', Consolas, Menlo, monospace;
            font-size: 13px;
            line-height: 1.5;
            word-break: break-word;
        }

        .value-cell .tag {
            display: block;
            font-family: 'Segoe UI', sans-serif;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            opacity: 0.65;
            margin-bottom: 4px;
        }

        .value-old {
            background: #fff5f5;
            color: #742a2a;
            border: 1px solid #fed7d7;
            text-decoration: line-through;
            text-decoration-color: rgba(116, 42, 42, 0.4);
        }

        .value-arrow {
            display: table-cell;
            width: 24px;
            vertical-align: middle;
            text-align: center;
            color: #a0aec0;
            font-weight: 700;
        }

        .value-new {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #c6f6d5;
            font-weight: 600;
        }

        .value-single {
            background: #f7fafc;
            color: #2d3748;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            font-family: 'SFMono-Regular', Consolas, Menlo, monospace;
            font-size: 13px;
            line-height: 1.5;
            word-break: break-word;
        }

        .info-box {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border-left: 4px solid #fc8181;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .info-box h3 {
            color: #c53030;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .info-box h3::before {
            content: "🛡️";
            margin-right: 8px;
        }

        .info-box p {
            color: #742a2a;
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

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 30px 0;
        }

        @media (max-width: 600px) {
            .email-container { margin: 10px; }
            .header, .content, .footer { padding: 20px; }
            .event-title { font-size: 22px; }
            .changes-box { padding: 18px; }
            .value-row, .value-cell, .value-arrow { display: block; width: 100%; }
            .value-arrow { padding: 4px 0; transform: rotate(90deg); }
            .value-cell { margin-bottom: 6px; }
        }
    </style>
</head>

<body>
    @php
        $event = $event ?? 'updated';
        $isCreated = $event === 'created';
        $headerTitle = $isCreated ? 'New Activity on Your Account!' : 'Account Activity Detected!';
        $headerSubtitle = 'Keeping you informed about changes at ' . config('app.name');
        $eventEmoji = $isCreated ? '✨' : '🔄';
        $eventActionText = $isCreated ? 'was just created' : 'was just updated';
        $fieldsHeadingText = $isCreated ? 'Fields' : 'Changes';

        // Convert CamelCase model class basename into a human-friendly label.
        // e.g. "ProfileStudentInfo" -> "Profile Student Info".
        $prettyModelLabel = \Illuminate\Support\Str::headline($modelLabel);

        // Normalize $changedFields.
        $normalized = [];
        if (!empty($changedFields) && is_array($changedFields)) {
            foreach ($changedFields as $key => $val) {
                if (is_int($key) && is_string($val)) {
                    $normalized[$val] = ['new' => '—', 'type' => 'string', 'has_old' => false];
                } elseif (is_string($key) && is_array($val)) {
                    $normalized[$key] = [
                        'old'  => array_key_exists('old', $val) ? $val['old'] : null,
                        'new'  => array_key_exists('new', $val) ? $val['new'] : '—',
                        'type' => $val['type'] ?? 'string',
                        'has_old' => array_key_exists('old', $val),
                    ];
                }
            }
        }
        $changeCount = count($normalized);
    @endphp

    <div class="email-container">
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <img src="https://www.suganta.com/logo/Su250.png" alt="{{ config('app.name') }}">
                </div>
            </div>
            <h1>{{ $headerTitle }}</h1>
            <p>{{ $headerSubtitle }}</p>
        </div>

        <div class="content">
            <div class="greeting">Hello {{ $user->name ?? 'User' }}! 👋</div>

            <div class="message">
                A <strong>{{ $prettyModelLabel }}</strong>@if($modelId) record (#{{ $modelId }})@endif linked to your {{ config('app.name') }} account {{ $eventActionText }}.
                If this was you, no further action is required. If not, please secure your account immediately.
            </div>

            <div class="activity-box">
                <div class="event-label">Activity Detected</div>
                <div class="event-title">{{ $eventEmoji }} {{ $prettyModelLabel }} {{ $isCreated ? 'Created' : 'Updated' }}</div>

                <div class="event-meta">
                    <strong>👤 Performed by:</strong> {{ $actorName }}<br>
                    <strong>🕒 When:</strong> {{ $eventTime }}<br>
                    <strong>🌐 IP address:</strong> {{ $ipAddress }}<br>
                    <strong>💻 Device:</strong> {{ \Illuminate\Support\Str::limit($userAgent, 60) }}
                </div>

                <a href="{{ config('app.frontend_url') }}/account/security" class="cta-button">Review Account Activity</a>
            </div>

            @if($changeCount > 0)
            <div class="changes-box">
                <div class="changes-heading">
                    <span>📝 {{ $isCreated ? 'Fields Provided' : 'What Changed' }}</span>
                    <span class="count-pill">{{ $changeCount }} {{ \Illuminate\Support\Str::plural('field', $changeCount) }}</span>
                </div>

                @foreach($normalized as $field => $info)
                    @php
                        $type = $info['type'] ?? 'string';
                        $hasOld = !empty($info['has_old']);
                        $prettyField = \Illuminate\Support\Str::of($field)->replace('_', ' ')->title();
                    @endphp
                    <div class="change-item">
                        <div class="field-label">
                            <span>{{ $prettyField }}</span>
                            <span class="type-badge {{ $type }}">{{ $type }}</span>
                        </div>

                        @if($isCreated || !$hasOld)
                            <div class="value-single">{{ $info['new'] ?? '—' }}</div>
                        @else
                            <div class="value-row">
                                <div class="value-cell value-old">
                                    <span class="tag">Before</span>
                                    {{ $info['old'] ?? '—' }}
                                </div>
                                <div class="value-arrow">→</div>
                                <div class="value-cell value-new">
                                    <span class="tag">After</span>
                                    {{ $info['new'] ?? '—' }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif

            <div class="info-box">
                <h3>Didn't recognize this activity?</h3>
                <p>
                    If you did <strong>not</strong> perform or authorize this change, please secure your account immediately by updating your password and contacting our support team.
                    Your account security is our top priority!
                </p>
            </div>

            <div class="divider"></div>

            <div class="message" style="text-align: center;">
                If you recognize this activity or have already reviewed it, please ignore this email or contact support with any questions.
            </div>
        </div>

        <div class="footer">
            <p>Stay safe,</p>
            <p><strong>{{ config('app.name') }} Security Team</strong></p>

            <div class="social-links">
                <a href="{{ config('app.frontend_url') }}">Visit Website</a>
                <a href="{{ config('app.contact_url') }}">Contact Support</a>
                <a href="{{ config('app.help_center_url') }}">Help Center</a>
            </div>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <p style="font-size: 12px; color: #a0aec0;">
                    This email was sent to {{ $user->email ?? '' }}.
                </p>
            </div>
        </div>
    </div>
</body>

</html>
