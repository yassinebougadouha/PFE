@php
$_emailPrimary   = \App\Models\Setting::get("primary_color", "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre compte Admin - {{ \App\Models\Setting::get('app_name', 'L2T') }} Support</title>
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
                        <h2 style="color:#2d3748;margin:0 0 16px;font-size:22px;">🎉 Bienvenue dans l'équipe !</h2>
                        <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 30px;">
                            Bonjour <strong>{{ $name }}</strong>,<br><br>
                            Un compte <strong>Administrateur</strong> vient d'être créé pour vous sur la plateforme <strong>{{ \App\Models\Setting::get('app_name', 'L2T') }} Support</strong>.
                            Voici vos informations de connexion :
                        </p>

                        <!-- Credentials Box -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f7fafc;border-radius:8px;overflow:hidden;margin:0 0 30px;">
                            <tr>
                                <td style="background:{{ $_emailPrimary }};padding:12px 20px;">
                                    <span style="color:#fff;font-weight:600;font-size:14px;">🔐 Vos identifiants de connexion</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:24px;">
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="color:#718096;font-size:14px;width:40%;padding:8px 0;"><strong>Adresse email :</strong></td>
                                            <td style="padding:8px 0;">
                                                <span style="background:#edf2f7;color:#2d3748;font-family:monospace;font-size:14px;padding:6px 12px;border-radius:4px;display:inline-block;">{{ $email }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="color:#718096;font-size:14px;padding:8px 0;"><strong>Mot de passe :</strong></td>
                                            <td style="padding:8px 0;">
                                                <span style="background:#edf2f7;color:#2d3748;font-family:monospace;font-size:14px;padding:6px 12px;border-radius:4px;display:inline-block;">{{ $password }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="color:#718096;font-size:14px;padding:8px 0;"><strong>Rôle :</strong></td>
                                            <td style="padding:8px 0;">
                                                <span style="display:inline-block;background:#e0e7ff;color:#3730a3;padding:4px 14px;border-radius:20px;font-size:13px;font-weight:600;">Administrateur</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- Warning -->
                        <div style="background:#fff8e1;border-left:4px solid #f59e0b;border-radius:4px;padding:16px;margin:0 0 30px;">
                            <p style="color:#92400e;margin:0;font-size:14px;line-height:1.6;">
                                ⚠️ <strong>Important :</strong> Pour votre sécurité, veuillez changer votre mot de passe dès votre première connexion via <strong>Mon Profil</strong>.
                            </p>
                        </div>

                        <!-- CTA Button -->
                        <div style="text-align:center;margin:30px 0;">
                            <a href="{{ url('/login') }}"
                               style="display:inline-block;background:linear-gradient(135deg,{{ $_emailPrimary }},{{ $_emailSecondary }});color:#fff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:16px;font-weight:600;">
                                Se connecter à la plateforme →
                            </a>
                        </div>

                        <p style="color:#718096;font-size:13px;line-height:1.6;margin:0;text-align:center;">
                            Si vous pensez avoir reçu cet email par erreur, contactez le super administrateur.
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