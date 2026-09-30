<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 40px 16px; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 540px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0;">
        <!-- Header -->
        <tr>
            <td style="padding: 36px 36px 24px; text-align: center; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div style="display: inline-block; width: 44px; height: 44px; background: rgba(255, 255, 255, 0.1); border-radius: 12px; margin-bottom: 12px; text-align: center; line-height: 44px;">
                    <span style="font-size: 22px;">🔐</span>
                </div>
                <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: -0.02em;">
                    {{ config('app.name', 'API Boilerplate') }}
                </h1>
                <p style="margin: 6px 0 0; color: #94a3b8; font-size: 13px;">Password Reset Request</p>
            </td>
        </tr>

        <!-- Content Body -->
        <tr>
            <td style="padding: 36px 36px 28px;">
                <p style="margin: 0 0 16px; font-size: 15px; color: #334155; line-height: 1.6;">
                    Hello <strong style="color: #0f172a;">{{ $user->name ?? 'there' }}</strong>,
                </p>
                <p style="margin: 0 0 28px; font-size: 15px; color: #475569; line-height: 1.6;">
                    We received a request to reset your password. Click the button below to choose a new password:
                </p>

                <!-- Button -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 32px;">
                    <tr>
                        <td align="center">
                            <a href="{{ $url }}" target="_blank" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: 10px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35); letter-spacing: 0.01em;">
                                Reset Password
                            </a>
                        </td>
                    </tr>
                </table>

                <div style="background-color: #f8fafc; border-left: 3px solid #3b82f6; border-radius: 6px; padding: 12px 16px; margin-bottom: 24px;">
                    <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                        ⏳ <strong>Time sensitive:</strong> This link will expire in <strong>60 minutes</strong>.
                    </p>
                </div>

                <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                    If you didn't ask to reset your password, you can safely ignore this email. Your current password will remain unchanged.
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 20px 36px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;">
                <p style="margin: 0 0 8px; font-size: 12px; color: #94a3b8;">
                    Having trouble clicking the button? Copy and paste this URL into your browser:
                </p>
                <p style="margin: 0; font-size: 12px; word-break: break-all;">
                    <a href="{{ $url }}" style="color: #2563eb; text-decoration: underline;">{{ $url }}</a>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
