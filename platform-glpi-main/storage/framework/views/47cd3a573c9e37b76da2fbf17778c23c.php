<?php
$_emailPrimary   = \App\Models\Setting::get("primary_color", "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Code de connexion - <?php echo e(\App\Models\Setting::get('app_name', 'L2T')); ?> Support</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr><td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
            <tr>
                <td style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);padding:40px 30px;text-align:center;">
                    <div style="font-size:48px;margin-bottom:10px;">🔐</div>
                    <h1 style="color:#fff;margin:0;font-size:26px;">Connexion Super Admin</h1>
                    <p style="color:rgba(255,255,255,0.7);margin:8px 0 0;"><?php echo e(\App\Models\Setting::get('app_name', 'L2T')); ?> Support — Vérification de sécurité</p>
                </td>
            </tr>
            <tr>
                <td style="padding:40px 30px;">
                    <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 24px;">
                        Bonjour <strong><?php echo e($name); ?></strong>,<br><br>
                        Une tentative de connexion au panneau <strong>Super Admin</strong> a été détectée. Utilisez ce code pour confirmer votre identité :
                    </p>

                    <div style="background:linear-gradient(135deg,#1a1a2e,#0f3460);border-radius:12px;padding:30px;text-align:center;margin:0 0 24px;">
                        <p style="color:rgba(255,255,255,0.7);font-size:12px;margin:0 0 10px;text-transform:uppercase;letter-spacing:2px;">Code de vérification</p>
                        <span style="color:#fff;font-size:42px;font-weight:700;letter-spacing:10px;font-family:monospace;"><?php echo e($otp); ?></span>
                    </div>

                    <div style="background:#fee2e2;border-left:4px solid #ef4444;border-radius:4px;padding:16px;margin:0 0 20px;">
                        <p style="color:#991b1b;margin:0;font-size:14px;">
                            ⚠️ Si vous n'avez pas tenté de vous connecter, changez immédiatement votre mot de passe !
                        </p>
                    </div>

                    <p style="color:#718096;font-size:13px;margin:0;">⏱️ Ce code expire dans <strong>10 minutes</strong>.</p>
                </td>
            </tr>
            <tr>
                <td style="background:#f7fafc;padding:20px 30px;text-align:center;border-top:1px solid #e2e8f0;">
                    <p style="color:#a0aec0;font-size:12px;margin:0;">© <?php echo e(date('Y')); ?> <?php echo e(\App\Models\Setting::get('app_name', 'L2T')); ?> Support — Sécurité renforcée</p>
                </td>
            </tr>
        </table>
    </td></tr>
</table>
</body>
</html><?php /**PATH /var/www/html/resources/views/emails/otp-login.blade.php ENDPATH**/ ?>