<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Virtual Airline Requires Approval</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; margin: 0; padding: 24px; color: #f8fafc;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        <!-- Header -->
        <tr>
            <td style="padding: 32px 32px 24px; text-align: center; background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border-bottom: 1px solid #4338ca;">
                <div style="font-size: 28px; margin-bottom: 8px;">🏢 ✈️</div>
                <h1 style="color: #ffffff; font-size: 20px; font-weight: 700; margin: 0; letter-spacing: 0.5px;">New Virtual Airline Created</h1>
                <p style="color: #cbd5e1; font-size: 13px; margin: 6px 0 0;">Action Required: Approval Needed</p>
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding: 32px;">
                <p style="color: #e2e8f0; font-size: 15px; line-height: 1.6; margin: 0 0 20px;">
                    Hello System Administrator,
                </p>
                <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                    A pilot has registered a new Virtual Airline on the platform and submitted it for system administrator review and approval:
                </p>

                <!-- Airline Details Card -->
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; border-radius: 12px; border: 1px solid #334155; margin-bottom: 28px; overflow: hidden;">
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; color: #94a3b8; font-size: 13px; width: 40%;">Airline Name:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; color: #ffffff; font-size: 13px; font-weight: 600;">{{ $tenant->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; color: #94a3b8; font-size: 13px;">ICAO Code:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; color: #38bdf8; font-size: 13px; font-weight: 700; font-family: monospace;">{{ $tenant->icao }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; color: #94a3b8; font-size: 13px;">Created By:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; color: #e2e8f0; font-size: 13px;">{{ $creator ? $creator->full_name . ' (' . $creator->email . ')' : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; color: #94a3b8; font-size: 13px;">Primary Base Hub:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; color: #a855f7; font-size: 13px; font-weight: 700; font-family: monospace;">{{ $baseHubIcao ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; color: #94a3b8; font-size: 13px;">SimBrief Format:</td>
                        <td style="padding: 12px 16px; color: #e2e8f0; font-size: 13px; text-transform: uppercase;">{{ $tenant->default_simbrief_ofp_format ?? 'LIDO' }}</td>
                    </tr>
                </table>

                <!-- Action Button -->
                <div style="text-align: center; margin: 32px 0 20px;">
                    <a href="{{ $adminUrl }}" style="display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                        Review & Approve Virtual Airline →
                    </a>
                </div>

                <p style="color: #64748b; font-size: 12px; line-height: 1.5; margin: 24px 0 0; text-align: center;">
                    If the button does not work, visit your platform admin dashboard at:<br>
                    <a href="{{ $adminUrl }}" style="color: #38bdf8; word-break: break-all;">{{ $adminUrl }}</a>
                </p>
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
