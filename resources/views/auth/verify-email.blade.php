<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Email Verification' }} - {{ config('app.name', 'API Boilerplate') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            /* Light theme (default) */
            --bg-body: radial-gradient(circle at 50% 0%, #f8fafc 0%, #e2e8f0 100%);
            --bg-card: rgba(255, 255, 255, 0.9);
            --border-card: rgba(226, 232, 240, 0.9);
            --shadow-card: 0 20px 40px -15px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.04);
            --text-heading: #0f172a;
            --text-subtitle: #64748b;
            --icon-bg: rgba(37, 99, 235, 0.1);
            --icon-border: rgba(37, 99, 235, 0.2);
            --icon-color: #2563eb;
            --success-icon-bg: rgba(34, 197, 94, 0.12);
            --success-icon-border: rgba(34, 197, 94, 0.25);
            --success-icon-color: #16a34a;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                /* Dark theme */
                --bg-body: radial-gradient(circle at 50% 0%, #1e293b 0%, #0f172a 100%);
                --bg-card: rgba(30, 41, 59, 0.75);
                --border-card: rgba(255, 255, 255, 0.08);
                --shadow-card: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
                --text-heading: #ffffff;
                --text-subtitle: #94a3b8;
                --icon-bg: rgba(59, 130, 246, 0.2);
                --icon-border: rgba(59, 130, 246, 0.3);
                --icon-color: #60a5fa;
                --success-icon-bg: rgba(34, 197, 94, 0.15);
                --success-icon-border: rgba(34, 197, 94, 0.3);
                --success-icon-color: #4ade80;
            }
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-body);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-heading);
            transition: background 0.3s ease;
        }

        .card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-card);
            border-radius: 20px;
            padding: 44px 32px;
            box-shadow: var(--shadow-card);
            text-align: center;
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .status-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .status-icon.success {
            background: var(--success-icon-bg);
            border: 1px solid var(--success-icon-border);
        }

        .status-icon.success svg {
            width: 32px;
            height: 32px;
            color: var(--success-icon-color);
        }

        .status-icon.info {
            background: var(--icon-bg);
            border: 1px solid var(--icon-border);
        }

        .status-icon.info svg {
            width: 32px;
            height: 32px;
            color: var(--icon-color);
        }

        h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-heading);
            margin-bottom: 12px;
        }

        p.subtitle {
            font-size: 14px;
            color: var(--text-subtitle);
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 13px 20px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn:hover {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }

        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 9999px;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.25);
            font-size: 13px;
            color: var(--success-icon-color);
            margin-top: 8px;
        }

        .user-pill-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 8px #22c55e;
        }
    </style>
</head>
<body>
    <div class="card">
        @if ($status === 'verified')
            <div class="status-icon success">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1>Email Verified!</h1>
            <p class="subtitle" style="margin-bottom: 16px;">Thank you! Your email address has been verified successfully. You can now return to the application.</p>
        @elseif ($status === 'already_verified')
            <div class="status-icon info">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1>Already Verified</h1>
            <p class="subtitle" style="margin-bottom: 16px;">Your email address has already been verified. You can return to the application.</p>
        @else
            <div class="status-icon info">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h1>Verify Your Email</h1>
            <p class="subtitle" style="margin-bottom: 16px;">A verification link has been sent to your email address. Please click the link inside the email to complete verification.</p>
        @endif

        @if (!empty($user))
            <div class="user-pill">
                <span class="user-pill-dot"></span>
                <span>Verified as <strong>{{ $user->email }}</strong></span>
            </div>
        @endif
    </div>
</body>
</html>
