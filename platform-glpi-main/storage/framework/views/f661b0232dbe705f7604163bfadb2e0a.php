<?php
$_emailPrimary   = \App\Models\Setting::get("primary_color", "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Compte créé — L2T Support</title></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">

      <!-- Header -->
      <tr>
        <td style="background:linear-gradient(135deg,<?php echo e($_emailPrimary); ?> 0%,<?php echo e($_emailSecondary); ?> 100%);padding:40px 30px;text-align:center;">
          <h1 style="color:#fff;margin:0;font-size:26px;font-weight:700;">🎉 Bienvenue sur L2T Support</h1>
          <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:15px;">Votre compte a été créé automatiquement</p>
        </td>
      </tr>

      <!-- Body -->
      <tr>
        <td style="padding:40px 30px;">
          <p style="color:#4a5568;font-size:15px;line-height:1.7;margin:0 0 24px;">
            Bonjour <strong><?php echo e($name); ?></strong>,<br><br>
            Nous avons bien reçu votre email et créé automatiquement un compte sur notre plateforme de support L2T.
            Votre ticket a été enregistré et notre équipe va traiter votre demande rapidement.
          </p>

          <!-- Credentials -->
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4ff;border-radius:10px;overflow:hidden;margin:0 0 28px;border:1px solid #c3cfe2;">
            <tr>
              <td style="background:linear-gradient(135deg,<?php echo e($_emailPrimary); ?>,<?php echo e($_emailSecondary); ?>);padding:12px 20px;">
                <span style="color:#fff;font-weight:700;font-size:14px;">🔐 Vos identifiants de connexion</span>
              </td>
            </tr>
            <tr>
              <td style="padding:20px;">
                <table width="100%" cellpadding="8" cellspacing="0">
                  <tr>
                    <td style="color:#718096;font-size:14px;width:35%;"><strong>Email</strong></td>
                    <td style="color:#2d3748;font-size:14px;font-family:monospace;"><?php echo e($email); ?></td>
                  </tr>
                  <tr style="background:#e8eeff;border-radius:6px;">
                    <td style="color:#718096;font-size:14px;padding:10px 8px;"><strong>Mot de passe</strong></td>
                    <td style="font-size:15px;padding:10px 8px;">
                      <span style="background:#fff;border:1px solid <?php echo e($_emailPrimary); ?>;border-radius:6px;padding:4px 12px;font-family:monospace;font-weight:700;color:<?php echo e($_emailPrimary); ?>;letter-spacing:1px;"><?php echo e($password); ?></span>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>

          <!-- Info box -->
          <div style="background:#fef3c7;border-left:4px solid #f59e0b;border-radius:4px;padding:14px 16px;margin:0 0 28px;">
            <p style="color:#92400e;font-size:14px;margin:0;line-height:1.6;">
              ⚠️ <strong>Important :</strong> Nous vous recommandons de changer votre mot de passe à la première connexion depuis votre espace profil.
            </p>
          </div>

          <!-- What next -->
          <div style="background:#f0fdf4;border-left:4px solid #22c55e;border-radius:4px;padding:14px 16px;margin:0 0 28px;">
            <p style="color:#166534;font-size:14px;margin:0 0 8px;font-weight:700;">✅ Ce que vous pouvez faire :</p>
            <ul style="color:#166534;font-size:14px;margin:0;padding-left:20px;line-height:1.8;">
              <li>Suivre l'avancement de votre ticket en temps réel</li>
              <li>Ajouter des informations supplémentaires à votre demande</li>
              <li>Soumettre de nouveaux tickets directement depuis la plateforme</li>
              <li>Recevoir les réponses de notre équipe par email</li>
            </ul>
          </div>

          <!-- CTA -->
          <div style="text-align:center;margin:30px 0;">
            <a href="<?php echo e(url('/login')); ?>"
               style="display:inline-block;background:linear-gradient(135deg,<?php echo e($_emailPrimary); ?>,<?php echo e($_emailSecondary); ?>);color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-size:15px;font-weight:600;">
              🚀 Accéder à mon espace →
            </a>
          </div>
        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style="background:#f7fafc;padding:24px 30px;text-align:center;border-top:1px solid #e2e8f0;">
          <p style="color:#718096;font-size:13px;margin:0 0 4px;"><strong>L2T — Landolsi Telecom Technology</strong></p>
          <p style="color:#a0aec0;font-size:12px;margin:0;">Vous recevez cet email car vous avez contacté notre support.</p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html><?php /**PATH /var/www/html/resources/views/emails/client-auto-created.blade.php ENDPATH**/ ?>