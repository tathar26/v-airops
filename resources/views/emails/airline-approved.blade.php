<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Airline Approved | V-Air Ops</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0A1835; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f8fafc;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #0A1835; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #0F224A; border-radius: 12px; border: 1px solid #142954; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.4);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 32px 30px 24px; background-color: #060E22; border-bottom: 1px solid #142954;">
                            <img src="{{ config('app.url') }}/images/v-air-ops-logo.png" alt="V-Air Ops" width="160" style="display: block; width: 160px; max-width: 160px; height: auto; margin: 0 auto; border: 0;" />
                            <div style="font-size: 11px; font-weight: 700; color: #21A19D; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 10px;">
                                Virtual Airline Approved
                            </div>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 36px 30px;">
                            <h2 style="font-size: 20px; font-weight: 700; color: #ffffff; margin-top: 0; margin-bottom: 16px;">
                                Hello {{ $owner->full_name ?? 'Airline Owner' }},
                            </h2>
                            <p style="font-size: 14px; line-height: 24px; color: #cbd5e1; margin-bottom: 24px;">
                                Great news! The platform administrators have reviewed and <strong style="color: #21A19D;">approved</strong> your Virtual Airline on V-Air Ops.
                            </p>

                            <!-- Airline Card -->
                            <div style="background-color: #060E22; border-radius: 8px; border: 1px solid #142954; padding: 20px; text-align: center; margin-bottom: 24px;">
                                <h3 style="color: #ffffff; font-size: 18px; font-weight: 700; margin: 0 0 6px;">{{ $tenant->name }}</h3>
                                <p style="color: #21A19D; font-family: monospace; font-size: 14px; font-weight: 700; margin: 0;">ICAO: {{ $tenant->icao }}</p>
                            </div>

                            <p style="font-size: 14px; line-height: 24px; color: #94a3b8; margin-bottom: 24px;">
                                You now have full access to management tools including Fleet, Route Manager, Airport Hubs, Ranks, and Pilot Operations. Other pilots can now find and join your airline through the platform directory.
                            </p>

                            <!-- Action Button -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $dashboardUrl }}" style="display: inline-block; background-color: #21A19D; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 700; font-size: 14px; letter-spacing: 0.5px;">
                                            Launch Airline Dashboard &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
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
