@php
$_emailPrimary   = \App\Models\Setting::get("primary_color", "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
@endphp
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0"
           style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
      <tr>
        <td style="background:linear-gradient(135deg,{{ $_emailPrimary }},{{ $_emailSecondary }});padding:40px 30px;text-align:center;">
          <h1 style="color:#fff;margin:0;font-size:28px;">{{ \App\Models\Setting::get('app_name', 'L2T') }} Support</h1>
          <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:15px;">Plateforme de gestion des tickets</p>
        </td>
      </tr>
      <tr>
        <td style="padding:40px 30px;">
          <h2 style="color:#2d3748;margin:0 0 16px;">🔐 Réinitialisation du mot de passe</h2>
          <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 28px;">
            Bonjour <strong>{{ $name }}</strong>,<br><br>
            Vous avez demandé la réinitialisation de votre mot de passe.
            Cliquez sur le bouton ci-dessous pour en définir un nouveau.
          </p>
          <div style="text-align:center;margin:32px 0;">
            <a href="{{ $resetUrl }}"
               style="display:inline-block;background:linear-gradient(135deg,{{ $_emailPrimary }},{{ $_emailSecondary }});color:#fff;text-decoration:none;padding:16px 44px;border-radius:8px;font-size:16px;font-weight:700;">
              Réinitialiser mon mot de passe →
            </a>
          </div>
          <p style="color:#a0aec0;font-size:12px;text-align:center;word-break:break-all;">
            Ou copiez ce lien : <a href="{{ $resetUrl }}" style="color:{{ $_emailPrimary }};">{{ $resetUrl }}</a>
          </p>
          <div style="background:#fff8e1;border-left:4px solid #f59e0b;padding:14px 18px;border-radius:4px;margin-top:20px;">
            <p style="color:#92400e;margin:0;font-size:13px;">
              ⚠️ Ce lien est valable <strong>60 minutes</strong>.<br>
              Si vous n'avez pas fait cette demande, ignorez cet email.
            </p>
          </div>
        </td>
      </tr>
      <tr>
        <td style="background:#f7fafc;padding:24px;text-align:center;border-top:1px solid #e2e8f0;">
          <p style="color:#a0aec0;font-size:13px;margin:0;">© {{ date('Y') }} {{ \App\Models\Setting::get('app_name', 'L2T') }} Support — Tous droits réservés</p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>