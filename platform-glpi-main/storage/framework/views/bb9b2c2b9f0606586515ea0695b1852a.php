<?php
$_emailPrimary   = \App\Models\Setting::get("primary_color", "#667eea");
$_emailSecondary = \App\Models\Setting::get("secondary_color", "#764ba2");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ticket confirmé - L2T Support</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">

                <!-- Header -->
                <tr>
                    <td style="background:linear-gradient(135deg,<?php echo e($_emailPrimary); ?> 0%,<?php echo e($_emailSecondary); ?> 100%);padding:40px 30px;text-align:center;">
                        <div style="width:60px;height:60px;background:rgba(255,255,255,0.2);border-radius:50%;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;">
                            <span style="font-size:28px;">✅</span>
                        </div>
                        <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:700;">Ticket soumis avec succès</h1>
                        <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:15px;">L2T Support — Votre demande a bien été enregistrée</p>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:40px 30px;">

                        <p style="color:#4a5568;font-size:15px;line-height:1.7;margin:0 0 28px;">
                            Bonjour <strong><?php echo e($ticket->user->name); ?></strong>,<br><br>
                            Votre ticket a bien été reçu et enregistré dans notre système de support.
                            Notre équipe technique va prendre en charge votre demande dans les meilleurs délais.
                        </p>

                        <!-- Ticket Info Card -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f7fafc;border-radius:10px;overflow:hidden;margin:0 0 28px;border:1px solid #e2e8f0;">
                            <tr>
                                <td style="background:linear-gradient(135deg,<?php echo e($_emailPrimary); ?>,<?php echo e($_emailSecondary); ?>);padding:14px 20px;">
                                    <span style="color:#fff;font-weight:700;font-size:15px;">🎫 Récapitulatif de votre ticket</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:20px;">
                                    <table width="100%" cellpadding="7" cellspacing="0">
                                        <tr>
                                            <td style="color:#718096;font-size:14px;width:38%;"><strong>Numéro de ticket</strong></td>
                                            <td style="font-size:14px;">
                                                <span style="background:linear-gradient(135deg,<?php echo e($_emailPrimary); ?>,<?php echo e($_emailSecondary); ?>);color:#fff;padding:3px 12px;border-radius:20px;font-weight:700;font-size:13px;">#<?php echo e($ticket->id); ?></span>
                                            </td>
                                        </tr>
                                        <tr style="background:#edf2f7;">
                                            <td style="color:#718096;font-size:14px;padding:9px 7px;"><strong>Titre</strong></td>
                                            <td style="color:#2d3748;font-size:14px;padding:9px 7px;"><?php echo e($ticket->title); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="color:#718096;font-size:14px;"><strong>Catégorie</strong></td>
                                            <td style="color:#2d3748;font-size:14px;">
                                                <?php
                                                    $catMap = [
                                                        'incident_technique' => '🔧 Incident technique',
                                                        'integration_api'    => '🔌 Intégration API',
                                                        'facturation'        => '💳 Facturation',
                                                        'plateforme'         => '🖥️ Plateforme',
                                                        'paiement_mobile'    => '📱 Paiement mobile',
                                                        'autre'              => '📋 Autre',
                                                    ];
                                                    $prioMap = [1=>'Très basse',2=>'Basse',3=>'Moyenne',4=>'Haute',5=>'Critique'];
                                                    $prioColors = [1=>'#95a5a6',2=>'#3498db',3=>'#f39c12',4=>'#e67e22',5=>'#e74c3c'];
                                                ?>
                                                <?php echo e($catMap[$ticket->category] ?? $ticket->category); ?>

                                            </td>
                                        </tr>
                                        <tr style="background:#edf2f7;">
                                            <td style="color:#718096;font-size:14px;padding:9px 7px;"><strong>Priorité</strong></td>
                                            <td style="font-size:14px;padding:9px 7px;">
                                                <span style="background:<?php echo e($prioColors[$ticket->priority] ?? '#f39c12'); ?>;color:#fff;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600;">
                                                    <?php echo e($prioMap[$ticket->priority] ?? 'Moyenne'); ?>

                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="color:#718096;font-size:14px;"><strong>Date de soumission</strong></td>
                                            <td style="color:#2d3748;font-size:14px;"><?php echo e($ticket->created_at->format('d/m/Y à H:i')); ?></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- Description -->
                        <div style="background:#f0f4ff;border-left:4px solid <?php echo e($_emailPrimary); ?>;border-radius:4px;padding:16px;margin:0 0 28px;">
                            <p style="color:#4c51bf;font-size:13px;margin:0 0 8px;font-weight:700;">📝 Votre description :</p>
                            <p style="color:#434190;font-size:14px;margin:0;line-height:1.7;"><?php echo e(Str::limit($ticket->description, 400)); ?></p>
                        </div>

                        <!-- SLA Info -->
                        <?php
                            $slaHours = [5=>4, 4=>8, 3=>24, 2=>48, 1=>72];
                            $sla = $slaHours[$ticket->priority] ?? 24;
                        ?>
                        <div style="background:#f0fdf4;border-left:4px solid #22c55e;border-radius:4px;padding:16px;margin:0 0 28px;">
                            <p style="color:#166534;font-size:14px;margin:0;">
                                ⏱️ <strong>Délai de traitement estimé :</strong>
                                Notre équipe s'engage à traiter votre demande dans un délai de <strong><?php echo e($sla); ?>h</strong>.
                            </p>
                        </div>

                        <!-- CTA -->
                        <div style="text-align:center;margin:30px 0 10px;">
                            <a href="<?php echo e(url('/tickets')); ?>"
                               style="display:inline-block;background:linear-gradient(135deg,<?php echo e($_emailPrimary); ?>,<?php echo e($_emailSecondary); ?>);color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-size:15px;font-weight:600;letter-spacing:0.3px;">
                                📋 Suivre mes tickets →
                            </a>
                        </div>

                        <p style="color:#a0aec0;font-size:13px;text-align:center;margin:20px 0 0;">
                            Vous recevrez un email dès que notre équipe répond à votre ticket.
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f7fafc;padding:24px 30px;text-align:center;border-top:1px solid #e2e8f0;">
                        <p style="color:#718096;font-size:13px;margin:0 0 6px;">
                            <strong>L2T — Landolsi Telecom Technology</strong>
                        </p>
                        <p style="color:#a0aec0;font-size:12px;margin:0;">
                            Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.<br>
                            Pour toute question, connectez-vous à votre espace client.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html><?php /**PATH /var/www/html/resources/views/emails/ticket-confirmation.blade.php ENDPATH**/ ?>