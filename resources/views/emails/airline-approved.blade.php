<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Airline Approved</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; margin: 0; padding: 24px; color: #f8fafc;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        <!-- Header -->
        <tr>
            <td style="padding: 32px 32px 24px; text-align: center; background: linear-gradient(135deg, #064e3b 0%, #047857 100%); border-bottom: 1px solid #059669;">
                <div style="font-size: 28px; margin-bottom: 8px;">🎉 ✈️</div>
                <h1 style="color: #ffffff; font-size: 20px; font-weight: 700; margin: 0; letter-spacing: 0.5px;">Virtual Airline Approved!</h1>
                <p style="color: #a7f3d0; font-size: 13px; margin: 6px 0 0;">Your VA is now active and ready for flight operations</p>
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding: 32px;">
                <p style="color: #e2e8f0; font-size: 15px; line-height: 1.6; margin: 0 0 20px;">
                    Hello {{ $owner->full_name ?? 'Airline Owner' }},
                </p>
                <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                    Great news! The platform administrators have reviewed and <strong style="color: #34d399;">approved</strong> your Virtual Airline:
                </p>

                <!-- Airline Card -->
                <div style="background-color: #0f172a; border-radius: 12px; border: 1px solid #334155; padding: 20px; text-align: center; margin-bottom: 28px;">
                    <h2 style="color: #ffffff; font-size: 18px; font-weight: 800; margin: 0 0 4px;">{{ $tenant->name }}</h2>
                    <p style="color: #38bdf8; font-family: monospace; font-size: 14px; font-weight: 700; margin: 0;">ICAO: {{ $tenant->icao }}</p>
                </div>

                <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 0 0 24px;">
                    You have full access to management tools including Fleet, Route Manager, Airport Hubs, Ranks, and Pilot Operations. Other pilots can now join your airline through the platform directory.
                </p>

                <!-- Action Button -->
                <div style="text-align: center; margin: 32px 0 20px;">
                    <a href="{{ $dashboardUrl }}" style="display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                        Launch Airline Dashboard →
                    </a>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 20px 32px; text-align: center; background-color: #0f172a; border-top: 1px solid #334155;">
                <p style="color: #64748b; font-size: 12px; margin: 0;">
                    Virtual Airline Operations System &bull; Platform Management
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
