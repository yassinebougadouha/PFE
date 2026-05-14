@php
$_emailPrimary   = \App\Models\Setting::get("primary_color",   "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
$_appName        = \App\Models\Setting::get("app_name",        "L2T");
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ticket assigné - {{ $_appName }} Support</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
                <tr>
                    <td>
                        <div style="background:linear-gradient(135deg,{{ $_emailPrimary }} 0%,{{ $_emailSecondary }} 100%);padding:36px 30px;text-align:center;">
                            <div style="font-size:48px;margin:0 0 12px;">🎫</div>
                            <h1 style="color:#ffffff;margin:0 0 6px;font-size:26px;font-weight:700;">Ticket Assigné</h1>
                            <p style="color:rgba(255,255,255,0.85);margin:0;font-size:14px;">{{ $_appName }} Support — Un ticket vous a été assigné</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px 30px;">
                        <p style="color:#4a5568;font-size:16px;margin:0 0 20px;">Bonjour <strong>{{ $admin->name }}</strong>,</p>
                        <p style="color:#4a5568;font-size:15px;margin:0 0 24px;">Le ticket suivant vient de vous être assigné :</p>

                        <div style="background:#f8f9fc;border-left:4px solid {{ $_emailPrimary }};border-radius:8px;padding:20px 24px;margin-bottom:24px;">
                            <p style="margin:0 0 8px;font-size:13px;color:#718096;text-transform:uppercase;letter-spacing:0.05em;">Ticket #{{ $ticket->id }}</p>
                            <h2 style="margin:0 0 12px;font-size:18px;color:#2d3748;font-weight:700;">{{ $ticket->title }}</h2>
                            <p style="margin:0;font-size:14px;color:#4a5568;">{{ \Illuminate\Support\Str::limit($ticket->description, 150) }}</p>
                        </div>

                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                            <tr>
                                <td style="padding:8px 12px;background:#f8f9fc;border-radius:6px;width:48%;">
                                    <p style="margin:0;font-size:12px;color:#718096;text-transform:uppercase;">Priorité</p>
                                    @php
                                        $prioLabels = [1=>'Très basse',2=>'Basse',3=>'Moyenne',4=>'Haute',5=>'Très haute'];
                                        $prioColors = [1=>'#718096',2=>'#3182ce',3=>'#dd6b20',4=>'#e53e3e',5=>'#822727'];
                                        $prio = $ticket->priority ?? 3;
                                    @endphp
                                    <p style="margin:4px 0 0;font-size:15px;font-weight:700;color:{{ $prioColors[$prio] ?? '#4a5568' }};">{{ $prioLabels[$prio] ?? 'Moyenne' }}</p>
                                </td>
                                <td style="width:4%;"></td>
                                <td style="padding:8px 12px;background:#f8f9fc;border-radius:6px;width:48%;">
                                    <p style="margin:0;font-size:12px;color:#718096;text-transform:uppercase;">Client</p>
                                    <p style="margin:4px 0 0;font-size:15px;font-weight:700;color:#2d3748;">{{ $ticket->user->name ?? 'N/A' }}</p>
                                </td>
                            </tr>
                        </table>

                        <p style="color:#718096;font-size:14px;margin:0 0 24px;">Merci de prendre en charge ce ticket dans les meilleurs délais.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 30px;background:#f8f9fc;border-top:1px solid #e2e8f0;text-align:center;">
                        <p style="margin:0;font-size:12px;color:#a0aec0;">© {{ date('Y') }} {{ $_appName }} — Plateforme de gestion des tickets</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>