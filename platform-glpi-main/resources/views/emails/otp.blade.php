@php
$_emailPrimary   = \App\Models\Setting::get("primary_color", "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de vérification - {{ \App\Models\Setting::get('app_name', 'L2T') }} Support</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">

                <!-- Header -->
                <tr>
                    <td style="background:linear-gradient(135deg,{{ $_emailPrimary }} 0%,{{ $_emailSecondary }} 100%);padding:40px 30px;text-align:center;">
                        <h1 style="color:#ffffff;margin:0;font-size:28px;font-weight:700;">{{ \App\Models\Setting::get('app_name', 'L2T') }} Support</h1>
                        <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:15px;">Plateforme de gestion des tickets</p>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:40px 30px;">
                        <h2 style="color:#2d3748;margin:0 0 16px;font-size:22px;">Votre code de vérification</h2>
                        <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 30px;">
                            Bonjour <strong>{{ $name }}</strong>,<br><br>
                            Pour finaliser votre inscription sur <strong>{{ \App\Models\Setting::get('app_name', 'L2T') }} Support</strong>, veuillez utiliser le code de vérification ci-dessous :
                        </p>

                        <!-- OTP Code Box -->
                        <div style="background:linear-gradient(135deg,{{ $_emailPrimary }} 0%,{{ $_emailSecondary }} 100%);border-radius:12px;padding:30px;text-align:center;margin:0 0 30px;">
                            <p style="color:rgba(255,255,255,0.8);font-size:13px;margin:0 0 10px;text-transform:uppercase;letter-spacing:2px;">Code de vérification</p>
                            <span style="color:#ffffff;font-size:42px;font-weight:700;letter-spacing:10px;font-family:monospace;">{{ $otp }}</span>
                        </div>

                        <div style="background:#fff8e1;border-left:4px solid #f59e0b;border-radius:4px;padding:16px;margin:0 0 24px;">
                            <p style="color:#92400e;margin:0;font-size:14px;">
                                ⏱️ Ce code expire dans <strong>10 minutes</strong>. Ne le partagez avec personne.
                            </p>
                        </div>

                        <p style="color:#718096;font-size:14px;line-height:1.6;margin:0;">
                            Si vous n'avez pas créé de compte sur {{ \App\Models\Setting::get('app_name', 'L2T') }} Support, ignorez cet email.
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f7fafc;padding:24px 30px;text-align:center;border-top:1px solid #e2e8f0;">
                        <p style="color:#a0aec0;font-size:13px;margin:0;">© {{ date('Y') }} {{ \App\Models\Setting::get('app_name', 'L2T') }} Support — Tous droits réservés</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>