<?php
$_emailPrimary   = \App\Models\Setting::get("primary_color", "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouveau ticket - <?php echo e(\App\Models\Setting::get('app_name', 'L2T')); ?> Support</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">

                <!-- Header -->
                <tr>
                    <td style="background:linear-gradient(135deg,#f59e0b 0%,#ef4444 100%);padding:40px 30px;text-align:center;">
                        <h1 style="color:#ffffff;margin:0;font-size:28px;font-weight:700;">🎫 Nouveau Ticket</h1>
                        <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:15px;"><?php echo e(\App\Models\Setting::get('app_name', 'L2T')); ?> Support — Action requise</p>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:40px 30px;">
                        <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 24px;">
                            Bonjour,<br><br>
                            Un nouveau ticket a été soumis et nécessite votre attention.
                        </p>

                        <!-- Ticket Info -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f7fafc;border-radius:8px;overflow:hidden;margin:0 0 24px;">
                            <tr>
                                <td style="background:<?php echo e($_emailPrimary); ?>;padding:12px 20px;">
                                    <span style="color:#fff;font-weight:600;font-size:14px;">Informations du ticket</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:20px;">
                                    <table width="100%" cellpadding="6" cellspacing="0">
                                        <tr>
                                            <td style="color:#718096;font-size:14px;width:35%;"><strong>Ticket #</strong></td>
                                            <td style="color:#2d3748;font-size:14px;"><?php echo e($ticket->id); ?></td>
                                        </tr>
                                        <tr style="background:#edf2f7;">
                                            <td style="color:#718096;font-size:14px;padding:8px 6px;"><strong>Titre</strong></td>
                                            <td style="color:#2d3748;font-size:14px;padding:8px 6px;"><?php echo e($ticket->title); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="color:#718096;font-size:14px;"><strong>Client</strong></td>
                                            <td style="color:#2d3748;font-size:14px;"><?php echo e($ticket->user->name); ?> (<?php echo e($ticket->user->email); ?>)</td>
                                        </tr>
                                        <tr style="background:#edf2f7;">
                                            <td style="color:#718096;font-size:14px;padding:8px 6px;"><strong>Catégorie</strong></td>
                                            <td style="color:#2d3748;font-size:14px;padding:8px 6px;"><?php echo e($ticket->category); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="color:#718096;font-size:14px;"><strong>Date</strong></td>
                                            <td style="color:#2d3748;font-size:14px;"><?php echo e($ticket->created_at->format('d/m/Y à H:i')); ?></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- Description -->
                        <div style="background:#fffbeb;border-left:4px solid #f59e0b;border-radius:4px;padding:16px;margin:0 0 24px;">
                            <p style="color:#92400e;font-size:13px;margin:0 0 6px;font-weight:600;">Description :</p>
                            <p style="color:#78350f;font-size:14px;margin:0;line-height:1.6;"><?php echo e(Str::limit($ticket->description, 300)); ?></p>
                        </div>

                        <!-- Pièces jointes -->
                        <?php if(!empty($attachmentPaths) && count($attachmentPaths) > 0): ?>
                        <div style="background:#f0fdf4;border-left:4px solid #22c55e;border-radius:4px;padding:16px;margin:0 0 24px;">
                            <p style="color:#166534;font-size:13px;margin:0 0 10px;font-weight:600;">
                                📎 Pièces jointes (<?php echo e(count($attachmentPaths)); ?>) :
                            </p>
                            <?php $__currentLoopData = $attachmentPaths; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $path): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div style="margin-bottom:6px;">
                                <a href="<?php echo e(url('storage/' . $path)); ?>"
                                   style="color:#16a34a;font-size:13px;text-decoration:underline;">
                                    📄 <?php echo e(basename($path)); ?>

                                </a>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php endif; ?>

                        <!-- CTA Button -->
                        <div style="text-align:center;margin:30px 0;">
                            <a href="<?php echo e(url('/admin/tickets/' . $ticket->id)); ?>"
                               style="display:inline-block;background:linear-gradient(135deg,<?php echo e($_emailPrimary); ?>,<?php echo e($_emailSecondary); ?>);color:#fff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:15px;font-weight:600;">
                                Voir et traiter le ticket →
                            </a>
                        </div>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f7fafc;padding:24px 30px;text-align:center;border-top:1px solid #e2e8f0;">
                        <p style="color:#a0aec0;font-size:13px;margin:0;">© <?php echo e(date('Y')); ?> <?php echo e(\App\Models\Setting::get('app_name', 'L2T')); ?> Support — Tous droits réservés</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html><?php /**PATH /var/www/html/resources/views/emails/new-ticket.blade.php ENDPATH**/ ?>