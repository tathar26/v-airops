<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Virtual Airline Pilot Account</title>
</head>
<body style="margin: 0; padding: 0; background-color: #020617; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f8fafc;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #020617; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" max-width="600" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #0f172a; border-radius: 16px; border: 1px solid #1e293b; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 36px 30px 20px; background: linear-gradient(to right, #0284c7, #4f46e5); color: #ffffff;">
                            <div style="font-size: 28px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 6px;">✈️ V-OPS</div>
                            <div style="font-size: 14px; opacity: 0.9; font-weight: 500;">Virtual Airline Operations Platform</div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 30px;">
                            <h2 style="font-size: 22px; font-weight: 700; color: #ffffff; margin-top: 0; margin-bottom: 16px;">
                                Welcome Aboard, {{ $user->first_name ?? $user->name }}!
                            </h2>
                            <p style="font-size: 15px; line-height: 24px; color: #94a3b8; margin-bottom: 24px;">
                                Thank you for creating your pilot account. To complete your registration and begin onboarding with your Virtual Airline, please confirm your email address below.
                            </p>

                            <!-- Verification Button -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $verificationUrl }}" target="_blank" style="display: inline-block; padding: 14px 32px; background: linear-gradient(to right, #0284c7, #4f46e5); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 10px; box-shadow: 0 10px 15px -3px rgba(2, 132, 199, 0.4);">
                                            Confirm &amp; Activate Account &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Safe Links note -->
                            <div style="padding: 16px; background-color: #020617; border-radius: 10px; border: 1px solid #1e293b; margin-top: 24px;">
                                <p style="font-size: 12px; color: #64748b; margin: 0; line-height: 18px;">
                                    🔒 <strong>Safe Links &amp; Spam Protected:</strong> Clicking the button will open a secure confirmation page where you can activate your account with one click.
                                </p>
                            </div>

                            <p style="font-size: 13px; color: #64748b; margin-top: 24px; line-height: 20px;">
                                This link will expire in 24 hours. If you did not create an account on V-Ops, no further action is required.
                            </p>

                            <hr style="border: 0; border-top: 1px solid #1e293b; margin: 28px 0;">

                            <p style="font-size: 11px; color: #475569; word-break: break-all; margin: 0;">
                                Having trouble clicking the button? Copy and paste this URL into your browser:<br>
                                <a href="{{ $verificationUrl }}" style="color: #38bdf8;">{{ $verificationUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 20px 30px; background-color: #020617; border-top: 1px solid #1e293b; font-size: 12px; color: #475569;">
                            &copy; {{ date('Y') }} V-Ops Virtual Airline Platform. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
