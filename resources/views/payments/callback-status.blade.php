<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} | {{ $appName }}</title>
    <style>
        :root {
            --status-color: {{ $color }};
            --text-dark: #111827;
            --text-light: #6b7280;
            --bg: #f3f4f6;
            --card: #ffffff;
            --border: #e5e7eb;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top, #eef2ff 0%, var(--bg) 48%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-dark);
        }

        .card {
            width: 100%;
            max-width: 560px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 18px 48px rgba(17, 24, 39, 0.1);
        }

        .icon-wrap {
            width: 72px;
            height: 72px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            margin: 0 auto 16px;
            background: color-mix(in srgb, var(--status-color) 14%, white);
            border: 1px solid color-mix(in srgb, var(--status-color) 25%, white);
        }

        .icon {
            width: 34px;
            height: 34px;
            color: var(--status-color);
        }

        h1 {
            margin: 0;
            text-align: center;
            font-size: 28px;
            letter-spacing: -0.3px;
        }

        .msg {
            margin: 8px 0 0;
            text-align: center;
            color: var(--text-light);
            font-size: 15px;
        }

        .pill {
            margin: 18px auto 0;
            display: table;
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 12px;
            color: var(--status-color);
            background: color-mix(in srgb, var(--status-color) 12%, white);
            border: 1px solid color-mix(in srgb, var(--status-color) 30%, white);
        }

        .details {
            margin-top: 24px;
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        .row:last-child { border-bottom: 0; }
        .label { color: var(--text-light); }
        .value { font-weight: 600; word-break: break-word; text-align: right; }

        .footer-note {
            margin-top: 16px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }

        .actions {
            margin-top: 18px;
            display: flex;
            justify-content: center;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: var(--status-color);
            padding: 10px 16px;
            border-radius: 10px;
        }

        .btn:hover {
            opacity: 0.92;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="icon-wrap" aria-hidden="true">
            @if($icon === 'check')
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            @elseif($icon === 'cross')
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"></path>
                </svg>
            @elseif($icon === 'clock')
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 7v6l4 2" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            @else
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 11v5" stroke-linecap="round"></path>
                    <circle cx="12" cy="8" r="1"></circle>
                </svg>
            @endif
        </div>

        <h1>{{ $title }}</h1>
        <p class="msg">{{ $message }}</p>
        <div class="pill">{{ $status }}</div>

        <section class="details" aria-label="Payment details">
            <div class="row">
                <span class="label">Order ID</span>
                <span class="value">{{ $orderId }}</span>
            </div>
            <div class="row">
                <span class="label">Amount</span>
                <span class="value">{{ $currency }} {{ $amount }}</span>
            </div>
            <div class="row">
                <span class="label">Payment Type</span>
                <span class="value">{{ $type ? ucfirst($type) : 'N/A' }}</span>
            </div>
            <div class="row">
                <span class="label">Processed At</span>
                <span class="value">{{ $processedAt ?? 'Not processed yet' }}</span>
            </div>
        </section>

        @if(!empty($goBackUrl))
            <div class="actions">
                <a href="{{ $goBackUrl }}" class="btn">Go Back</a>
            </div>
        @endif

        <p class="footer-note">Need help? Contact support from {{ $appName }} app/website.</p>
    </main>
</body>
</html>
