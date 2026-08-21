<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your V-Air Ops Password</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0A1835; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f8fafc;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #0A1835; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #0F224A; border-radius: 12px; border: 1px solid #142954; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 32px 30px 24px; background-color: #060E22; border-bottom: 1px solid #142954;">
                            <img src="{{ config('app.url') }}/images/v-air-ops-logo.png" alt="V-Air Ops" width="160" style="display: block; width: 160px; max-width: 160px; height: auto; margin: 0 auto; border: 0;" />
                            <div style="font-size: 11px; font-weight: 700; color: #21A19D; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 10px;">
                                Password Reset Request
                            </div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 30px;">
                            <h2 style="font-size: 20px; font-weight: 700; color: #ffffff; margin-top: 0; margin-bottom: 16px;">
                                Password Reset Request
                            </h2>
                            <p style="font-size: 14px; line-height: 24px; color: #cbd5e1; margin-bottom: 24px;">
                                You are receiving this email because we received a password reset request for your V-Air Ops account. Click the button below to choose a new password.
                            </p>

                            <!-- Reset Button -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $url }}" target="_blank" style="display: inline-block; padding: 14px 32px; background-color: #21A19D; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; border-radius: 8px; letter-spacing: 0.5px;">
                                            Reset Password &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 13px; color: #94a3b8; margin-top: 24px; line-height: 20px;">
                                This password reset link will expire in {{ $count ?? 60 }} minutes. If you did not request a password reset, no further action is required and your account remains secure.
                            </p>

                            <hr style="border: 0; border-top: 1px solid #142954; margin: 28px 0;">

                            <p style="font-size: 11px; color: #64748b; word-break: break-all; margin: 0;">
                                Having trouble clicking the button? Copy and paste this URL into your browser:<br>
                                <a href="{{ $url }}" style="color: #21A19D; text-decoration: underline;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 20px 30px; background-color: #060E22; border-top: 1px solid #142954; font-size: 12px; color: #64748b;">
                            &copy; {{ date('Y') }} V-Air Ops SaaS Platform. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
