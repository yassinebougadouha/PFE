@php
$_emailPrimary   = \App\Models\Setting::get("primary_color", "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouveau commentaire - {{ \App\Models\Setting::get('app_name', 'L2T') }} Support</title>
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
                        <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:15px;">💬 Nouveau commentaire client</p>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:40px 30px;">
                        <h2 style="color:#2d3748;margin:0 0 16px;font-size:20px;">💬 Le client a ajouté un commentaire</h2>
                        <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 24px;">
                            Bonjour,<br><br>
                            Le client <strong>{{ $ticket->user->name }}</strong> a ajouté un commentaire sur son ticket et attend votre réponse.
                        </p>

                        <!-- Ticket Info -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f7fafc;border-radius:8px;overflow:hidden;margin:0 0 24px;">
                            <tr>
                                <td style="background:{{ $_emailPrimary }};padding:12px 20px;">
                                    <span style="color:#fff;font-weight:600;font-size:14px;">🎫 Ticket #{{ $ticket->id }} — {{ $ticket->title }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 20px;">
                                    <table width="100%" cellpadding="4" cellspacing="0">
                                        <tr>
                                            <td style="color:#718096;font-size:13px;width:30%;"><strong>Client :</strong></td>
                                            <td style="color:#2d3748;font-size:13px;">{{ $ticket->user->name }} ({{ $ticket->user->email }})</td>
                                        </tr>
                                        <tr style="background:#edf2f7;">
                                            <td style="color:#718096;font-size:13px;padding:6px 4px;"><strong>Statut :</strong></td>
                                            <td style="color:#2d3748;font-size:13px;padding:6px 4px;">{{ ucfirst($ticket->sync_status) }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- Comment Box -->
                        <div style="background:#eff6ff;border-left:4px solid #3b82f6;border-radius:4px;padding:20px;margin:0 0 24px;">
                            <p style="color:#1e40af;font-size:13px;margin:0 0 8px;font-weight:600;text-transform:uppercase;letter-spacing:1px;">
                                💬 Commentaire du client :
                            </p>
                            <p style="color:#1e3a8a;font-size:15px;margin:0;line-height:1.7;">{{ $comment->content }}</p>
                        </div>

                        @if($comment->attachment_path)
                        <!-- Attachment -->
                        <div style="background:#f0fdf4;border-left:4px solid #22c55e;border-radius:4px;padding:16px;margin:0 0 24px;">
                            <p style="color:#166534;margin:0;font-size:14px;">
                                📎 <strong>Fichier joint :</strong>
                                <a href="{{ url('storage/' . $comment->attachment_path) }}"
                                   style="color:#16a34a;text-decoration:underline;">
                                    Voir le fichier joint →
                                </a>
                            </p>
                        </div>
                        @endif

                        <!-- CTA -->
                        <div style="text-align:center;margin:30px 0;">
                            <a href="{{ url('/admin/tickets/' . $ticket->id) }}"
                               style="display:inline-block;background:linear-gradient(135deg,{{ $_emailPrimary }},{{ $_emailSecondary }});color:#fff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:15px;font-weight:600;">
                                Répondre au ticket →
                            </a>
                        </div>
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