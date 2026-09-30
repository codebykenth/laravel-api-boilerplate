<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - {{ config('app.name', 'API Boilerplate') }}</title>
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
            --text-label: #334155;
            --bg-input: #ffffff;
            --border-input: #cbd5e1;
            --text-input: #0f172a;
            --placeholder-input: #94a3b8;
            --bg-input-readonly: #f1f5f9;
            --icon-bg: rgba(37, 99, 235, 0.1);
            --icon-border: rgba(37, 99, 235, 0.2);
            --icon-color: #2563eb;
            --alert-error-bg: #fef2f2;
            --alert-error-border: #fecaca;
            --alert-error-text: #b91c1c;
            --success-icon-bg: rgba(34, 197, 94, 0.12);
            --success-icon-border: rgba(34, 197, 94, 0.25);
            --success-icon-color: #16a34a;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-body: radial-gradient(circle at 50% 0%, #1e293b 0%, #0f172a 100%);
                --bg-card: rgba(30, 41, 59, 0.75);
                --border-card: rgba(255, 255, 255, 0.08);
                --shadow-card: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
                --text-heading: #ffffff;
                --text-subtitle: #94a3b8;
                --text-label: #cbd5e1;
                --bg-input: rgba(15, 23, 42, 0.6);
                --border-input: rgba(255, 255, 255, 0.12);
                --text-input: #ffffff;
                --placeholder-input: #64748b;
                --bg-input-readonly: rgba(15, 23, 42, 0.3);
                --icon-bg: rgba(59, 130, 246, 0.2);
                --icon-border: rgba(59, 130, 246, 0.3);
                --icon-color: #60a5fa;
                --alert-error-bg: rgba(239, 68, 68, 0.12);
                --alert-error-border: rgba(239, 68, 68, 0.25);
                --alert-error-text: #fca5a5;
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
            padding: 40px 32px;
            box-shadow: var(--shadow-card);
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .icon-wrap {
            width: 52px;
            height: 52px;
            margin: 0 auto 16px;
            background: var(--icon-bg);
            border: 1px solid var(--icon-border);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .icon-wrap svg {
            width: 24px;
            height: 24px;
            color: var(--icon-color);
        }

        h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-heading);
            margin-bottom: 8px;
        }

        p.subtitle {
            font-size: 14px;
            color: var(--text-subtitle);
            line-height: 1.5;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 24px;
            display: none;
        }

        .alert-error {
            background: var(--alert-error-bg);
            border: 1px solid var(--alert-error-border);
            color: var(--alert-error-text);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-label);
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border-input);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text-input);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.3s ease;
        }

        input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        input::placeholder {
            color: var(--placeholder-input);
        }

        input[readonly] {
            background: var(--bg-input-readonly);
            color: var(--text-subtitle);
            cursor: not-allowed;
        }

        .btn {
            width: 100%;
            padding: 13px 20px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 28px;
        }

        .btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .success-box {
            display: none;
            text-align: center;
            padding: 10px 0;
        }

        .success-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: var(--success-icon-bg);
            border: 1px solid var(--success-icon-border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .success-icon svg {
            width: 30px;
            height: 30px;
            color: var(--success-icon-color);
        }
    </style>
</head>
<body>
    <div class="card">
        <div id="resetSection">
            <div class="header">
                <div class="icon-wrap">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1>Set New Password</h1>
                <p class="subtitle">Please enter and confirm your new password below.</p>
            </div>

            <div id="errorAlert" class="alert alert-error"></div>

            <form id="resetForm">
                <input type="hidden" id="token" value="{{ $token }}">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" value="{{ $email ?? '' }}" placeholder="your.email@example.com" required {{ !empty($email) ? 'readonly' : '' }}>
                </div>

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" placeholder="At least 8 characters" minlength="8" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm New Password</label>
                    <input type="password" id="password_confirmation" placeholder="Re-enter password" minlength="8" required>
                </div>

                <button type="submit" id="submitBtn" class="btn">
                    <span class="spinner" id="btnSpinner"></span>
                    <span id="btnText">Reset Password</span>
                </button>
            </form>
        </div>

        <div id="successSection" class="success-box">
            <div class="success-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1>Password Reset!</h1>
            <p class="subtitle" style="margin-bottom: 24px;">Your password has been reset successfully. You can now use your new password to sign in.</p>
        </div>
    </div>

    <script>
        const form = document.getElementById('resetForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnSpinner = document.getElementById('btnSpinner');
        const btnText = document.getElementById('btnText');
        const errorAlert = document.getElementById('errorAlert');
        const resetSection = document.getElementById('resetSection');
        const successSection = document.getElementById('successSection');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorAlert.style.display = 'none';
            errorAlert.innerHTML = '';

            const token = document.getElementById('token').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;

            if (password !== passwordConfirmation) {
                errorAlert.textContent = 'Passwords do not match.';
                errorAlert.style.display = 'block';
                return;
            }

            // Loading state
            submitBtn.disabled = true;
            btnSpinner.style.display = 'inline-block';
            btnText.textContent = 'Resetting...';

            try {
                const response = await fetch('/api/v1/auth/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        token: token,
                        email: email,
                        password: password,
                        password_confirmation: passwordConfirmation
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    let message = data.message || 'Failed to reset password.';
                    if (data.errors) {
                        const errorList = Object.values(data.errors).flat().join('<br>');
                        message = errorList || message;
                    }
                    throw new Error(message);
                }

                // Show success
                resetSection.style.display = 'none';
                successSection.style.display = 'block';
            } catch (err) {
                errorAlert.innerHTML = err.message;
                errorAlert.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                btnSpinner.style.display = 'none';
                btnText.textContent = 'Reset Password';
            }
        });
    </script>
</body>
</html>
