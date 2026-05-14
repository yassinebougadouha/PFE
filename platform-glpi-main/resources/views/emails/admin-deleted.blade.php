@php
$_emailPrimary   = \App\Models\Setting::get("primary_color", "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Votre compte a été supprimé - {{ \App\Models\Setting::get('app_name', 'L2T') }} Support</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">

                <!-- Header -->
                <tr>
                    <td style="background:linear-gradient(135deg,#718096 0%,#2d3748 100%);padding:40px 30px;text-align:center;">
                        <h1 style="color:#ffffff;margin:0;font-size:28px;font-weight:700;">{{ \App\Models\Setting::get('app_name', 'L2T') }} Support</h1>
                        <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:15px;">🗑️ Compte supprimé</p>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:40px 30px;">
                        <h2 style="color:#2d3748;margin:0 0 16px;font-size:22px;">🗑️ Votre compte a été supprimé</h2>
                        <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 30px;">
                            Bonjour <strong>{{ $name }}</strong>,<br><br>
                            Votre compte Administrateur sur <strong>{{ \App\Models\Setting::get('app_name', 'L2T') }} Support</strong> a été <strong style="color:#e53e3e;">définitivement supprimé</strong> par le Super Administrateur.
                        </p>

                        <!-- Info Box -->
                        <div style="background:#fff5f5;border-left:4px solid #e53e3e;border-radius:4px;padding:20px;margin:0 0 24px;">
                            <p style="color:#9b2c2c;margin:0;font-size:14px;line-height:1.6;">
                                ⛔ <strong>Accès révoqué :</strong> Vos identifiants ne sont plus valides. Vous ne pouvez plus vous connecter à la plateforme.
                            </p>
                        </div>

                        <div style="background:#f7fafc;border-radius:8px;padding:20px;margin:0 0 24px;">
                            <table width="100%" cellpadding="6" cellspacing="0">
                                <tr>
                                    <td style="color:#718096;font-size:14px;width:40%;"><strong>Compte :</strong></td>
                                    <td style="color:#2d3748;font-size:14px;">{{ $email }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#718096;font-size:14px;"><strong>Date :</strong></td>
                                    <td style="color:#2d3748;font-size:14px;">{{ now()->format('d/m/Y à H:i') }}</td>
                                </tr>
                            </table>
                        </div>

                        <p style="color:#718096;font-size:13px;line-height:1.6;margin:0;text-align:center;">
                            Si vous pensez qu'il s'agit d'une erreur, veuillez contacter le Super Administrateur.
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