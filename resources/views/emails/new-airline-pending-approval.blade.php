<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Virtual Airline Requires Approval | V-Air Ops</title>
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
                            <div style="font-size: 11px; font-weight: 700; color: #6F3B84; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 10px;">
                                System Administrator Alert &bull; Approval Needed
                            </div>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 36px 30px;">
                            <h2 style="font-size: 20px; font-weight: 700; color: #ffffff; margin-top: 0; margin-bottom: 16px;">
                                Hello System Administrator,
                            </h2>
                            <p style="font-size: 14px; line-height: 24px; color: #cbd5e1; margin-bottom: 24px;">
                                A pilot has registered a new Virtual Airline on V-Air Ops and submitted it for review and approval:
                            </p>

                            <!-- Airline Details Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #060E22; border-radius: 8px; border: 1px solid #142954; margin-bottom: 28px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #142954; color: #94a3b8; font-size: 13px; width: 40%;">Airline Name:</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #142954; color: #ffffff; font-size: 13px; font-weight: 600;">{{ $tenant->name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #142954; color: #94a3b8; font-size: 13px;">ICAO Code:</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #142954; color: #21A19D; font-size: 13px; font-weight: 700; font-family: monospace;">{{ $tenant->icao }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #142954; color: #94a3b8; font-size: 13px;">Created By:</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #142954; color: #e2e8f0; font-size: 13px;">{{ $creator ? $creator->full_name . ' (' . $creator->email . ')' : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #142954; color: #94a3b8; font-size: 13px;">Primary Base Hub:</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #142954; color: #6F3B84; font-size: 13px; font-weight: 700; font-family: monospace;">{{ $baseHubIcao ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; color: #94a3b8; font-size: 13px;">SimBrief Format:</td>
                                    <td style="padding: 12px 16px; color: #e2e8f0; font-size: 13px; text-transform: uppercase;">{{ $tenant->default_simbrief_ofp_format ?? 'LIDO' }}</td>
                                </tr>
                            </table>

                            <!-- Action Button -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $adminUrl }}" style="display: inline-block; background-color: #21A19D; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 700; font-size: 14px; letter-spacing: 0.5px;">
                                            Review &amp; Approve Airline &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #64748b; font-size: 12px; line-height: 1.5; margin: 24px 0 0; text-align: center;">
                                If the button does not work, visit your platform admin dashboard at:<br>
                                <a href="{{ $adminUrl }}" style="color: #21A19D; word-break: break-all;">{{ $adminUrl }}</a>
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
