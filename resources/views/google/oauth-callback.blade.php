<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Connection Status</title>
    <style>
        :root {
            color-scheme: light dark;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 28px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            margin-bottom: 14px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .badge.success {
            background: #dcfce7;
            color: #166534;
        }
        .badge.error {
            background: #fee2e2;
            color: #991b1b;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 24px;
        }
        p {
            margin: 0 0 24px;
            font-size: 15px;
            line-height: 1.5;
            color: #374151;
        }
        .btn {
            display: inline-block;
            text-decoration: none;
            background: #111827;
            color: #ffffff;
            font-weight: 600;
            border-radius: 8px;
            padding: 12px 18px;
        }
        .btn:hover {
            background: #000000;
        }
    </style>
</head>
<body>
    <main class="card">
        <span class="badge {{ $success ? 'success' : 'error' }}">
            {{ $success ? 'CONNECTED' : 'FAILED' }}
        </span>
        <h1>{{ $success ? 'You are connected successfully' : 'Connection failed' }}</h1>
        <p>{{ $message }}</p>
        <a class="btn" href="{{ $backUrl }}">Go Back</a>
    </main>
</body>
</html>
