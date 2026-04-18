<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Security Alert - {{ config('app.name') }}</title>
    <style>
        /* ----------  Base reset  ---------- */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; }
        body {
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background-color: #eef2ff;
            background-image: radial-gradient(circle at 10% 0%, #e0e7ff 0%, transparent 45%),
                              radial-gradient(circle at 90% 100%, #fee2e2 0%, transparent 40%),
                              linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            -webkit-font-smoothing: antialiased;
            -webkit-text-size-adjust: 100%;
            min-height: 100vh;
            padding: 40px 16px;
        }
        img { border: 0; outline: none; text-decoration: none; display: block; max-width: 100%; }
        a { text-decoration: none; }

        /* ----------  Layout shell  ---------- */
        .preheader {
            display: none !important;
            visibility: hidden;
            opacity: 0;
            color: transparent;
            height: 0;
            width: 0;
            font-size: 1px;
            line-height: 1px;
            max-height: 0;
            max-width: 0;
            overflow: hidden;
            mso-hide: all;
        }

        .email-container {
            max-width: 620px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px -15px rgba(15, 23, 42, 0.18),
                        0 0 0 1px rgba(15, 23, 42, 0.04);
        }

        /* ----------  Header  ---------- */
        .header {
            position: relative;
            padding: 44px 36px 60px;
            color: #ffffff;
            background: radial-gradient(circle at top right, #ef4444 0%, #b91c1c 45%, #7f1d1d 100%);
            text-align: left;
            overflow: hidden;
        }
        .header::after {
            content: "";
            position: absolute;
            right: -60px;
            top: -60px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }
        .header::before {
            content: "";
            position: absolute;
            left: -50px;
            bottom: -80px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }
        .brand-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.95);
        }
        .brand img {
            width: 34px;
            height: auto;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.15);
            padding: 4px;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(8px);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #fff;
        }
        .status-pill .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #fecaca;
            box-shadow: 0 0 0 3px rgba(254, 202, 202, 0.35);
        }
        .header h1 {
            position: relative;
            z-index: 1;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }
        .header p {
            position: relative;
            z-index: 1;
            font-size: 15px;
            line-height: 1.6;
            max-width: 460px;
            color: rgba(255, 255, 255, 0.85);
        }

        /* ----------  Content  ---------- */
        .content {
            padding: 40px 36px 16px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
            letter-spacing: -0.2px;
        }
        .message {
            font-size: 15px;
            color: #475569;
            line-height: 1.7;
            margin-bottom: 28px;
        }
        .message strong { color: #0f172a; font-weight: 600; }

        /* ----------  Activity card  ---------- */
        .activity-card {
            border: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 28px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .activity-head {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #b91c1c;
            flex-shrink: 0;
            box-shadow: inset 0 0 0 1px rgba(185, 28, 28, 0.12);
        }
        .activity-head .meta-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .activity-head h2 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.3px;
        }

        .details {
            width: 100%;
            border-collapse: collapse;
        }
        .details td {
            padding: 12px 0;
            font-size: 14px;
            vertical-align: top;
            color: #334155;
            border-bottom: 1px solid #eef2f7;
        }
        .details tr:last-child td { border-bottom: none; }
        .details td.label {
            width: 38%;
            color: #64748b;
            font-weight: 500;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .details td.label .ico {
            display: inline-block;
            width: 18px;
            text-align: center;
            margin-right: 6px;
            opacity: 0.7;
        }
        .details td.value {
            color: #0f172a;
            font-weight: 500;
            word-break: break-word;
        }
        .details td.value code {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 13px;
            color: #0f172a;
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .chip {
            display: inline-block;
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.5;
            white-space: nowrap;
        }

        /* ----------  Changes section  ---------- */
        .changes-section {
            margin-top: 22px;
            padding-top: 22px;
            border-top: 1px dashed #e2e8f0;
        }
        .changes-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .changes-heading h3 {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .changes-heading .count {
            display: inline-block;
            background: #eef2ff;
            color: #4338ca;
            border: 1px solid #e0e7ff;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .change-row {
            background: #ffffff;
            border: 1px solid #eef2f7;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }
        .change-row:last-child { margin-bottom: 0; }
        .change-row .field-name {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.2px;
            margin-bottom: 10px;
            text-transform: capitalize;
        }
        .type-tag {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2px 8px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .type-tag.string { background: #f1f5f9; color: #475569; }
        .type-tag.number { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .type-tag.boolean { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .type-tag.datetime { background: #ede9fe; color: #5b21b6; border-color: #ddd6fe; }
        .type-tag.json { background: #e0f2fe; color: #075985; border-color: #bae6fd; }
        .type-tag.null { background: #fee2e2; color: #991b1b; border-color: #fecaca; }

        .diff {
            display: flex;
            align-items: stretch;
            gap: 10px;
        }
        .diff-side {
            flex: 1 1 0;
            min-width: 0;
            padding: 10px 12px;
            border-radius: 10px;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 13px;
            line-height: 1.55;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .diff-old {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            text-decoration: line-through;
            text-decoration-color: rgba(153, 27, 27, 0.4);
            text-decoration-thickness: 1px;
        }
        .diff-new {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            font-weight: 600;
        }
        .diff-arrow {
            align-self: center;
            font-size: 14px;
            color: #94a3b8;
            font-weight: 700;
            flex-shrink: 0;
        }
        .diff-side .side-label {
            display: block;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 4px;
            text-decoration: none;
        }
        .value-single {
            background: #f8fafc;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 13px;
            line-height: 1.55;
            word-break: break-word;
            overflow-wrap: anywhere;
            font-weight: 500;
        }

        /* ----------  CTA  ---------- */
        .cta-wrap {
            text-align: center;
            margin: 28px 0 12px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            color: #ffffff !important;
            padding: 14px 34px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.2px;
            box-shadow: 0 10px 20px -6px rgba(185, 28, 28, 0.5),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .cta-secondary {
            display: inline-block;
            margin-top: 10px;
            color: #64748b !important;
            font-size: 13px;
            font-weight: 500;
        }
        .cta-secondary:hover { color: #0f172a !important; }

        /* ----------  Notice  ---------- */
        .notice {
            display: flex;
            gap: 14px;
            padding: 20px 22px;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 1px solid #fed7aa;
            margin-bottom: 28px;
        }
        .notice-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: inset 0 0 0 1px rgba(234, 88, 12, 0.18);
        }
        .notice h3 {
            font-size: 14px;
            font-weight: 700;
            color: #9a3412;
            margin-bottom: 4px;
        }
        .notice p {
            font-size: 13px;
            line-height: 1.6;
            color: #9a3412;
        }
        .notice p strong { color: #7c2d12; font-weight: 700; }

        /* ----------  Divider & fine print  ---------- */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 24px 0;
        }
        .fine-print {
            text-align: center;
            font-size: 12.5px;
            color: #94a3b8;
            line-height: 1.6;
            padding: 0 4px 12px;
        }

        /* ----------  Footer  ---------- */
        .footer {
            background: #0f172a;
            color: #cbd5e1;
            padding: 32px 36px;
            text-align: center;
        }
        .footer-brand {
            font-size: 15px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 6px;
            letter-spacing: -0.2px;
        }
        .footer-tag {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 18px;
        }
        .footer-links {
            margin-bottom: 18px;
        }
        .footer-links a {
            display: inline-block;
            margin: 0 8px;
            color: #e2e8f0 !important;
            font-size: 13px;
            font-weight: 500;
            padding: 4px 0;
            border-bottom: 1px solid transparent;
        }
        .footer-legal {
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 11.5px;
            color: #64748b;
            line-height: 1.6;
        }

        /* ----------  Responsive  ---------- */
        @media (max-width: 620px) {
            body { padding: 16px 8px; }
            .email-container { border-radius: 16px; }
            .header { padding: 32px 22px 48px; }
            .header h1 { font-size: 24px; }
            .content { padding: 28px 22px 12px; }
            .activity-card { padding: 22px 18px; }
            .activity-head { flex-direction: row; }
            .activity-head h2 { font-size: 18px; }
            .details td.label { width: 44%; font-size: 11px; }
            .details td { font-size: 13px; padding: 10px 0; }
            .footer { padding: 26px 22px; }
            .cta-button { width: 100%; padding: 14px 20px; }
            .diff { flex-direction: column; gap: 6px; }
            .diff-arrow { align-self: flex-start; transform: rotate(90deg); padding: 2px 6px; }
        }

        /* ----------  Dark mode (supported clients only)  ---------- */
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; background-image: none; color: #e2e8f0; }
            .email-container { background-color: #111827; box-shadow: 0 0 0 1px rgba(255,255,255,0.04); }
            .greeting, .activity-head h2 { color: #f8fafc; }
            .message, .details td.value { color: #cbd5e1; }
            .activity-card { background: #1f2937; border-color: rgba(255,255,255,0.06); }
            .details td { border-bottom-color: rgba(255,255,255,0.06); color: #cbd5e1; }
            .details td.value code { background: #0b1220; color: #e2e8f0; }
            .chip { background: rgba(190, 18, 60, 0.18); border-color: rgba(244, 63, 94, 0.3); color: #fecdd3; }
            .notice { background: rgba(234, 88, 12, 0.12); border-color: rgba(234, 88, 12, 0.35); }
            .notice h3, .notice p { color: #fed7aa; }
            .fine-print { color: #64748b; }
            .changes-section { border-top-color: rgba(255,255,255,0.06); }
            .changes-heading h3 { color: #f8fafc; }
            .changes-heading .count { background: rgba(99, 102, 241, 0.18); color: #c7d2fe; border-color: rgba(99, 102, 241, 0.3); }
            .change-row { background: #0f172a; border-color: rgba(255,255,255,0.06); box-shadow: none; }
            .change-row .field-name { color: #f8fafc; }
            .type-tag { background: rgba(255,255,255,0.06); color: #cbd5e1; border-color: rgba(255,255,255,0.08); }
            .type-tag.number { background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border-color: rgba(16, 185, 129, 0.3); }
            .type-tag.boolean { background: rgba(234, 179, 8, 0.15); color: #fde68a; border-color: rgba(234, 179, 8, 0.3); }
            .type-tag.datetime { background: rgba(139, 92, 246, 0.15); color: #ddd6fe; border-color: rgba(139, 92, 246, 0.3); }
            .type-tag.json { background: rgba(14, 165, 233, 0.15); color: #bae6fd; border-color: rgba(14, 165, 233, 0.3); }
            .type-tag.null { background: rgba(244, 63, 94, 0.15); color: #fecdd3; border-color: rgba(244, 63, 94, 0.3); }
            .diff-old { background: rgba(239, 68, 68, 0.12); color: #fecaca; border-color: rgba(239, 68, 68, 0.3); }
            .diff-new { background: rgba(16, 185, 129, 0.12); color: #a7f3d0; border-color: rgba(16, 185, 129, 0.3); }
            .value-single { background: rgba(255,255,255,0.04); color: #e2e8f0; border-color: rgba(255,255,255,0.08); }
        }
    </style>
</head>

<body>
    @php
        $event = $event ?? 'updated';
        $isCreated = $event === 'created';
        $headerKicker = $isCreated ? 'New record created' : 'Account activity detected';
        $headerTitle = $isCreated
            ? 'A new ' . $modelLabel . ' was just created'
            : 'Your ' . $modelLabel . ' was just updated';
        $headerSubtitle = $isCreated
            ? 'We noticed a new record linked to your ' . config('app.name') . ' account. If you made this change, no further action is needed.'
            : 'We noticed a change on a record linked to your ' . config('app.name') . ' account. If you made this change, no further action is needed.';
        $activityTitle = $isCreated ? $modelLabel . ' Created' : $modelLabel . ' Updated';
        $fieldsLabel = $isCreated ? 'Fields' : 'Changed';
        $preheader = ($isCreated ? 'New record created' : 'Update detected') . ' on your ' . config('app.name') . ' account — review if this was you.';
    @endphp

    <span class="preheader">{{ $preheader }}</span>

    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="brand-row">
                <div class="brand">
                    <img src="https://www.suganta.com/logo/Su250.png" alt="{{ config('app.name') }}">
                    <span>{{ config('app.name') }}</span>
                </div>
                <span class="status-pill">
                    <span class="dot"></span>
                    {{ $isCreated ? 'New Activity' : 'Activity Alert' }}
                </span>
            </div>
            <h1>{{ $headerTitle }}</h1>
            <p>{{ $headerSubtitle }}</p>
        </div>

        <!-- Body -->
        <div class="content">
            <div class="greeting">Hi {{ $user->name ?? 'there' }} 👋</div>
            <div class="message">
                Here's a quick summary of what happened
                @if($modelId) on <strong>#{{ $modelId }}</strong>@endif.
                If you recognize this activity, you can safely ignore this email.
            </div>

            <!-- Activity card -->
            <div class="activity-card">
                <div class="activity-head">
                    <div class="activity-icon">{{ $isCreated ? '✨' : '🔄' }}</div>
                    <div>
                        <div class="meta-label">Event</div>
                        <h2>{{ $activityTitle }}</h2>
                    </div>
                </div>

                <table class="details" role="presentation" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="label"><span class="ico">👤</span> Performed by</td>
                        <td class="value">{{ $actorName }}</td>
                    </tr>
                    <tr>
                        <td class="label"><span class="ico">🕒</span> When</td>
                        <td class="value">{{ $eventTime }}</td>
                    </tr>
                    <tr>
                        <td class="label"><span class="ico">🌐</span> IP address</td>
                        <td class="value"><code>{{ $ipAddress }}</code></td>
                    </tr>
                    <tr>
                        <td class="label"><span class="ico">💻</span> Device</td>
                        <td class="value" style="font-size:13px;">{{ $userAgent }}</td>
                    </tr>
                </table>

                @php
                    // Normalize $changedFields. Supports both the old flat format (['field1', 'field2'])
                    // and the new rich format (['field1' => ['old' => x, 'new' => y, 'type' => 'string']]).
                    $normalized = [];
                    if (!empty($changedFields) && is_array($changedFields)) {
                        foreach ($changedFields as $key => $val) {
                            if (is_int($key) && is_string($val)) {
                                $normalized[$val] = ['new' => '—', 'type' => 'string'];
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

                @if($changeCount > 0)
                <div class="changes-section">
                    <div class="changes-heading">
                        <h3>📝 {{ $isCreated ? 'Provided fields' : 'What changed' }}</h3>
                        <span class="count">{{ $changeCount }} {{ \Illuminate\Support\Str::plural('field', $changeCount) }}</span>
                    </div>

                    @foreach($normalized as $field => $info)
                        @php
                            $type = $info['type'] ?? 'string';
                            $hasOld = !empty($info['has_old']);
                            $prettyField = \Illuminate\Support\Str::of($field)->replace('_', ' ')->title();
                        @endphp
                        <div class="change-row">
                            <div class="field-name">
                                <span>{{ $prettyField }}</span>
                                <span class="type-tag {{ $type }}">{{ $type }}</span>
                            </div>

                            @if($isCreated || !$hasOld)
                                <div class="value-single">{{ $info['new'] ?? '—' }}</div>
                            @else
                                <div class="diff">
                                    <div class="diff-side diff-old">
                                        <span class="side-label">Before</span>
                                        {{ $info['old'] ?? '—' }}
                                    </div>
                                    <div class="diff-arrow">→</div>
                                    <div class="diff-side diff-new">
                                        <span class="side-label">After</span>
                                        {{ $info['new'] ?? '—' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                @endif

                <div class="cta-wrap">
                    <a href="{{ config('app.url') }}/account/security" class="cta-button">Review account activity</a>
                    <br>
                    <a href="{{ config('app.url') }}/account/security" class="cta-secondary">or view your security settings →</a>
                </div>
            </div>

            <!-- Notice -->
            <div class="notice">
                <div class="notice-icon">🛡️</div>
                <div>
                    <h3>Didn't recognise this activity?</h3>
                    <p>
                        If you did <strong>not</strong> perform or authorise this change, secure your account right away
                        by updating your password and contacting our support team. Your safety is our top priority.
                    </p>
                </div>
            </div>

            <div class="divider"></div>

            <div class="fine-print">
                This is an automated security notification from {{ config('app.name') }}.<br>
                You're receiving it because activity alerts are enabled on your account.
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-brand">{{ config('app.name') }} Security Team</div>
            <div class="footer-tag">Keeping your account safe, 24/7.</div>

            <div class="footer-links">
                <a href="{{ config('app.frontend_url') }}">Website</a>
                <a href="{{ config('app.contact_url') }}">Contact support</a>
                <a href="{{ config('app.help_center_url') }}">Help center</a>
            </div>

            <div class="footer-legal">
                This email was sent to <span style="color:#e2e8f0;">{{ $user->email ?? '' }}</span>.<br>
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
        </div>
    </div>
</body>

</html>
