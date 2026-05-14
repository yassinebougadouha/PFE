@php
$_emailPrimary   = \App\Models\Setting::get("primary_color", "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ticket clôturé automatiquement - {{ \App\Models\Setting::get('app_name', 'L2T') }} Support</title>
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
                        <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:15px;">🔒 Clôture automatique</p>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:40px 30px;">
                        <h2 style="color:#2d3748;margin:0 0 16px;font-size:22px;">🔒 Votre ticket a été clôturé</h2>
                        <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 24px;">
                            Bonjour <strong>{{ $ticket->user->name }}</strong>,<br><br>
                            Votre ticket a été <strong>résolu depuis 5 jours</strong> sans retour de votre part.
                            Il a donc été <strong>clôturé automatiquement</strong>.
                        </p>

                        <!-- Ticket Info -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f7fafc;border-radius:8px;overflow:hidden;margin:0 0 24px;">
                            <tr>
                                <td style="background:{{ $_emailPrimary }};padding:12px 20px;">
                                    <span style="color:#fff;font-weight:600;font-size:14px;">🎫 Ticket #{{ $ticket->id }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:20px;">
                                    <p style="color:#718096;font-size:13px;margin:0 0 4px;"><strong>Titre :</strong></p>
                                    <p style="color:#2d3748;font-size:15px;margin:0 0 16px;">{{ $ticket->title }}</p>

                                    <span style="display:inline-block;background:#e0e7ff;color:#3730a3;padding:4px 14px;border-radius:20px;font-size:13px;font-weight:600;">
                                        🔒 CLÔTURÉ
                                    </span>
                                </td>
                            </tr>
                        </table>

                        @if($ticket->solution)
                        <!-- Solution -->
                        <div style="background:#f0fff4;border-left:4px solid #38a169;border-radius:4px;padding:20px;margin:0 0 24px;">
                            <p style="color:#276749;font-size:13px;margin:0 0 8px;font-weight:600;text-transform:uppercase;letter-spacing:1px;">✅ Solution apportée :</p>
                            <p style="color:#2f855a;font-size:15px;margin:0;line-height:1.7;">{{ $ticket->solution }}</p>
                        </div>
                        @endif

                        <!-- Info Box -->
                        <div style="background:#ebf8ff;border-left:4px solid #3182ce;border-radius:4px;padding:16px;margin:0 0 30px;">
                            <p style="color:#2c5282;margin:0;font-size:14px;line-height:1.6;">
                                💡 <strong>Avez-vous encore un problème ?</strong><br>
                                Si le problème persiste, n'hésitez pas à créer un nouveau ticket. Notre équipe reste à votre disposition.
                            </p>
                        </div>

                        <!-- CTA -->
                        <div style="text-align:center;margin:30px 0;">
                            <a href="{{ url('/tickets/create') }}"
                               style="display:inline-block;background:linear-gradient(135deg,{{ $_emailPrimary }},{{ $_emailSecondary }});color:#fff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:15px;font-weight:600;">
                                Créer un nouveau ticket →
                            </a>
                        </div>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f7fafc;padding:24px 30px;text-align:center;border-top:1px solid #e2e8f0;">
                        <p style="color:#a0aec0;font-size:13px;margin:0;">© {{ date('Y') }} {{ \App\Models\Setting::get('app_name', 'L2T') }} Support — Clôture automatique après 5 jours de résolution</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>