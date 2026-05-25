<?php
$_emailPrimary   = \App\Models\Setting::get("primary_color", "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réponse à votre ticket - <?php echo e(\App\Models\Setting::get('app_name', 'L2T')); ?> Support</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">

                <!-- Header dynamique selon statut -->
                <tr>
                    <td style="padding:0;">
                        <?php if($ticket->sync_status === 'resolved'): ?>
                        <div style="background:linear-gradient(135deg,#38a169 0%,#276749 100%);padding:36px 30px;text-align:center;">
                            <div style="font-size:48px;margin:0 0 12px;">✅</div>
                            <h1 style="color:#ffffff;margin:0 0 6px;font-size:26px;font-weight:700;">Ticket Résolu !</h1>
                            <p style="color:rgba(255,255,255,0.85);margin:0;font-size:14px;"><?php echo e(\App\Models\Setting::get('app_name', 'L2T')); ?> Support — Votre problème a été traité</p>
                        </div>
                        <?php elseif($ticket->sync_status === 'closed'): ?>
                        <div style="background:linear-gradient(135deg,#718096 0%,#4a5568 100%);padding:36px 30px;text-align:center;">
                            <div style="font-size:48px;margin:0 0 12px;">🔒</div>
                            <h1 style="color:#ffffff;margin:0 0 6px;font-size:26px;font-weight:700;">Ticket Clôturé</h1>
                            <p style="color:rgba(255,255,255,0.85);margin:0;font-size:14px;"><?php echo e(\App\Models\Setting::get('app_name', 'L2T')); ?> Support</p>
                        </div>
                        <?php else: ?>
                        <div style="background:linear-gradient(135deg,<?php echo e($_emailPrimary); ?> 0%,<?php echo e($_emailSecondary); ?> 100%);padding:36px 30px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0 0 6px;font-size:26px;font-weight:700;"><?php echo e(\App\Models\Setting::get('app_name', 'L2T')); ?> Support</h1>
                            <p style="color:rgba(255,255,255,0.85);margin:0;font-size:14px;">Mise à jour de votre ticket</p>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:36px 30px;">

                        <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 24px;">
                            Bonjour <strong><?php echo e($ticket->user->name); ?></strong>,
                        </p>

                        <?php if($ticket->sync_status === 'resolved'): ?>
                        

                        <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 20px;">
                            Notre équipe a <strong style="color:#38a169;">résolu votre ticket</strong>.
                            Veuillez vérifier que votre problème est bien résolu.
                        </p>

                        
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#f8fafc;border-radius:10px;overflow:hidden;margin:0 0 20px;border:1px solid #e2e8f0;">
                            <tr><td style="background:#38a169;padding:10px 20px;">
                                <span style="color:#fff;font-weight:700;font-size:14px;">🎫 Ticket #<?php echo e($ticket->id); ?></span>
                            </td></tr>
                            <tr><td style="padding:16px 20px;">
                                <p style="color:#718096;font-size:12px;margin:0 0 2px;">Titre</p>
                                <p style="color:#2d3748;font-size:15px;font-weight:600;margin:0 0 12px;"><?php echo e($ticket->title); ?></p>
                                <span style="background:#d1fae5;color:#065f46;padding:4px 14px;border-radius:20px;font-size:13px;font-weight:700;">✅ Résolu</span>
                            </td></tr>
                        </table>

                        
                        <div style="background:#f0fff4;border-left:4px solid #38a169;border-radius:6px;padding:20px;margin:0 0 20px;">
                            <p style="color:#276749;font-size:12px;margin:0 0 10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">
                                💬 Réponse de notre équipe :
                            </p>
                            <p style="color:#2f855a;font-size:15px;margin:0;line-height:1.8;"><?php echo e($ticket->solution); ?></p>
                        </div>

                        
                        <div style="background:linear-gradient(135deg,#fffbeb,#fef9c3);border:2px solid #fcd34d;border-radius:12px;padding:24px;margin:0 0 24px;text-align:center;">
                            <p style="color:#92400e;font-size:17px;font-weight:700;margin:0 0 10px;">
                                👀 Vérifiez votre ticket maintenant
                            </p>
                            <p style="color:#78350f;font-size:14px;margin:0 0 8px;line-height:1.6;">
                                Connectez-vous à la plateforme pour confirmer que votre problème est bien résolu.
                            </p>
                            <p style="color:#b45309;font-size:13px;margin:0;line-height:1.6;">
                                ✅ Si tout est en ordre — <strong>aucune action requise</strong><br>
                                ⏰ Le ticket sera <strong>clôturé automatiquement après 5 jours</strong>
                            </p>
                        </div>

                        
                        <div style="text-align:center;margin:0 0 24px;">
                            <a href="<?php echo e(url('/tickets')); ?>"
                               style="display:inline-block;background:linear-gradient(135deg,#38a169,#276749);
                                      color:#fff;text-decoration:none;padding:16px 48px;border-radius:10px;
                                      font-size:16px;font-weight:700;box-shadow:0 4px 14px rgba(56,161,105,0.35);">
                                ✅ Accéder à mon ticket →
                            </a>
                        </div>

                        
                        <div style="background:#eff6ff;border-left:4px solid #3b82f6;border-radius:6px;padding:16px;margin:0 0 16px;">
                            <p style="color:#1e40af;margin:0;font-size:13px;line-height:1.7;">
                                🔁 <strong>Problème toujours présent ?</strong><br>
                                Ajoutez un commentaire sur votre ticket <strong>avant les 5 jours</strong>.
                                Notre équipe vous recontactera. Passé ce délai, le ticket sera clôturé automatiquement.
                            </p>
                        </div>

                        <?php elseif($ticket->sync_status === 'closed'): ?>
                        

                        <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 20px;">
                            Votre ticket a été <strong>clôturé</strong> par notre équipe.
                        </p>

                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#f8fafc;border-radius:10px;overflow:hidden;margin:0 0 20px;border:1px solid #e2e8f0;">
                            <tr><td style="background:#718096;padding:10px 20px;">
                                <span style="color:#fff;font-weight:700;font-size:14px;">🎫 Ticket #<?php echo e($ticket->id); ?></span>
                            </td></tr>
                            <tr><td style="padding:16px 20px;">
                                <p style="color:#2d3748;font-size:15px;font-weight:600;margin:0 0 10px;"><?php echo e($ticket->title); ?></p>
                                <span style="background:#e0e7ff;color:#3730a3;padding:4px 14px;border-radius:20px;font-size:13px;font-weight:700;">🔒 Clôturé</span>
                                <?php if($ticket->solution): ?>
                                <div style="background:#f0fff4;border-left:3px solid #38a169;padding:14px;border-radius:4px;margin-top:14px;">
                                    <p style="color:#276749;font-size:12px;font-weight:700;margin:0 0 6px;">Solution apportée :</p>
                                    <p style="color:#2f855a;font-size:14px;margin:0;line-height:1.7;"><?php echo e($ticket->solution); ?></p>
                                </div>
                                <?php endif; ?>
                            </td></tr>
                        </table>

                        <div style="text-align:center;margin:24px 0;">
                            <a href="<?php echo e(url('/tickets/create')); ?>"
                               style="display:inline-block;background:linear-gradient(135deg,<?php echo e($_emailPrimary); ?>,<?php echo e($_emailSecondary); ?>);
                                      color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-size:15px;font-weight:700;">
                                Créer un nouveau ticket →
                            </a>
                        </div>

                        <?php else: ?>
                        

                        <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 20px;">
                            Notre équipe a mis à jour le statut de votre ticket.
                        </p>

                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#f8fafc;border-radius:10px;overflow:hidden;margin:0 0 20px;border:1px solid #e2e8f0;">
                            <tr><td style="background:<?php echo e($_emailPrimary); ?>;padding:10px 20px;">
                                <span style="color:#fff;font-weight:700;font-size:14px;">🎫 Ticket #<?php echo e($ticket->id); ?></span>
                            </td></tr>
                            <tr><td style="padding:16px 20px;">
                                <p style="color:#2d3748;font-size:15px;font-weight:600;margin:0 0 8px;"><?php echo e($ticket->title); ?></p>
                                <?php if($ticket->solution): ?>
                                <p style="color:#4a5568;font-size:14px;margin:0;line-height:1.6;"><?php echo e($ticket->solution); ?></p>
                                <?php endif; ?>
                            </td></tr>
                        </table>

                        <div style="text-align:center;margin:24px 0;">
                            <a href="<?php echo e(url('/tickets')); ?>"
                               style="display:inline-block;background:linear-gradient(135deg,<?php echo e($_emailPrimary); ?>,<?php echo e($_emailSecondary); ?>);
                                      color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-size:15px;font-weight:700;">
                                Voir mes tickets →
                            </a>
                        </div>

                        <?php endif; ?>

                        <p style="color:#cbd5e1;font-size:12px;margin:0;text-align:center;padding-top:16px;border-top:1px solid #f1f5f9;">
                            Email automatique — Merci de ne pas répondre directement à cet email.
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f7fafc;padding:20px 30px;text-align:center;border-top:1px solid #e2e8f0;">
                        <p style="color:#a0aec0;font-size:12px;margin:0;">© <?php echo e(date('Y')); ?> <?php echo e(\App\Models\Setting::get('app_name', 'L2T')); ?> Support — Tous droits réservés</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html><?php /**PATH /var/www/html/resources/views/emails/ticket-reply.blade.php ENDPATH**/ ?>