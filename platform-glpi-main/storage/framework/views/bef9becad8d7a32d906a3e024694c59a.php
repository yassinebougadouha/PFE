<?php $__env->startSection('title', 'Admin — ' . $admin->name); ?>
<?php $__env->startSection('page-title', 'Fiche admin'); ?>

<?php $__env->startSection('content'); ?>

<?php
  $initials   = strtoupper(substr($admin->name, 0, 2));
  $statusData = [
    'pending'     => ['warning',   'En attente',  'hourglass_empty'],
    'in_progress' => ['info',      'En cours',    'autorenew'],
    'resolved'    => ['success',   'Résolu',      'check_circle'],
    'closed'      => ['secondary', 'Clôturé',     'lock'],
    'synced'      => ['info',      'Synchronisé', 'sync'],
    'failed'      => ['danger',    'Erreur',      'error'],
  ];
  $catLabels = [
    'incident_technique' => '🔧 Incident technique',
    'integration_api'    => '🔌 Intégration API SMS',
    'facturation'        => '💳 Facturation',
    'plateforme'         => '🖥️ Plateforme',
    'paiement_mobile'    => '📱 Paiement mobile',
    'autre'              => '📋 Autre',
  ];
  $prioColors = [1=>'secondary',2=>'info',3=>'warning',4=>'danger',5=>'dark'];
  $prioLabels = [1=>'Très basse',2=>'Basse',3=>'Moyenne',4=>'Haute',5=>'Critique'];
?>


<div class="d-flex align-items-center gap-2 mb-4">
  <a href="<?php echo e(route('super-admin.admins')); ?>" class="btn btn-sm btn-outline-secondary mb-0">
    <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">arrow_back</i>
    Admins
  </a>
  <span class="text-secondary">/</span>
  <span class="text-sm font-weight-bold"><?php echo e($admin->name); ?></span>
</div>

<div class="row g-4">

  
  <div class="col-lg-4">

    
    <div class="card mb-4" style="border-radius:16px;overflow:hidden;">
      <div style="height:80px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));"></div>
      <div class="card-body text-center" style="margin-top:-45px;">
        <?php if($admin->avatar): ?>
          <img src="<?php echo e(asset('storage/'.$admin->avatar)); ?>"
               style="width:80px;height:80px;border-radius:50%;border:3px solid #fff;object-fit:cover;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
        <?php else: ?>
          <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));border:3px solid #fff;display:flex;align-items:center;justify-content:center;margin:0 auto;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
            <span style="font-size:28px;font-weight:700;color:#fff;"><?php echo e($initials); ?></span>
          </div>
        <?php endif; ?>

        <h6 class="font-weight-bold mt-3 mb-1" style="font-size:16px;"><?php echo e($admin->name); ?></h6>
        <p class="text-secondary text-sm mb-2"><?php echo e($admin->email); ?></p>

        
        <span class="badge px-3 py-2 mb-3"
              style="background:rgba(99,102,241,0.1);color:#4f46e5;border:1px solid #c7d2fe;border-radius:20px;font-size:12px;">
          <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">shield_person</i>
          Administrateur
        </span>

        
        <div class="mb-3">
          <form method="POST" action="<?php echo e(route('super-admin.admins.toggle', $admin->id)); ?>" style="display:inline;">
            <?php echo csrf_field(); ?>
            <button type="submit" class="badge border-0 px-3 py-2"
                    style="cursor:pointer;font-size:12px;
                           background:<?php echo e($admin->is_active ? 'linear-gradient(135deg,#22c55e,#16a34a)' : 'linear-gradient(135deg,#94a3b8,#64748b)'); ?>;
                           color:#fff;border-radius:20px;"
                    title="<?php echo e($admin->is_active ? 'Cliquer pour désactiver' : 'Cliquer pour activer'); ?>">
              <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">
                <?php echo e($admin->is_active ? 'check_circle' : 'cancel'); ?>

              </i>
              <?php echo e($admin->is_active ? 'Actif' : 'Inactif'); ?>

            </button>
          </form>
        </div>

        
        <div class="d-flex justify-content-center gap-2 flex-wrap">
          <a href="<?php echo e(route('super-admin.chat-access')); ?>/<?php echo e($admin->id); ?>"
             class="btn btn-sm mb-0 text-white"
             style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));border:none;">
            <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">chat</i>
            Conversation
          </a>
          <form method="POST" action="<?php echo e(route('super-admin.admins.delete', $admin->id)); ?>"
                onsubmit="return confirm('Supprimer cet admin définitivement?')">
            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
            <button type="submit" class="btn btn-sm mb-0 btn-outline-danger">
              <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">delete</i>
              Supprimer
            </button>
          </form>
        </div>
      </div>
    </div>

    
    <div class="card mb-4" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">contact_page</i>
          Coordonnées
        </h6>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="d-flex flex-column gap-3">

          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#f0f4ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:var(--color-primary);">email</i>
            </div>
            <div>
              <p class="text-xs text-secondary mb-0">Email</p>
              <p class="text-sm font-weight-bold mb-0"><?php echo e($admin->email); ?></p>
            </div>
          </div>

          <?php if($admin->phone): ?>
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:#16a34a;">phone</i>
            </div>
            <div>
              <p class="text-xs text-secondary mb-0">Téléphone</p>
              <p class="text-sm font-weight-bold mb-0"><?php echo e($admin->phone); ?></p>
            </div>
          </div>
          <?php endif; ?>

          <?php if($admin->teams_email): ?>
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:#2563eb;">groups</i>
            </div>
            <div>
              <p class="text-xs text-secondary mb-0">Microsoft Teams</p>
              <p class="text-sm font-weight-bold mb-0"><?php echo e($admin->teams_email); ?></p>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>

    
    <div class="card mb-4" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">bar_chart</i>
          Performance
        </h6>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="row g-2">
          <div class="col-6">
            <div class="text-center p-3" style="background:#f8f9fa;border-radius:12px;">
              <p class="font-weight-bold mb-0" style="font-size:22px;color:var(--color-primary);"><?php echo e($stats['total']); ?></p>
              <p class="text-xs text-secondary mb-0">Tickets assignés</p>
            </div>
          </div>
          <div class="col-6">
            <div class="text-center p-3" style="background:#fef3c7;border-radius:12px;">
              <p class="font-weight-bold mb-0" style="font-size:22px;color:#d97706;"><?php echo e($stats['pending']); ?></p>
              <p class="text-xs text-secondary mb-0">En attente</p>
            </div>
          </div>
          <div class="col-6">
            <div class="text-center p-3" style="background:#dbeafe;border-radius:12px;">
              <p class="font-weight-bold mb-0" style="font-size:22px;color:#2563eb;"><?php echo e($stats['inprog']); ?></p>
              <p class="text-xs text-secondary mb-0">En cours</p>
            </div>
          </div>
          <div class="col-6">
            <div class="text-center p-3" style="background:#dcfce7;border-radius:12px;">
              <p class="font-weight-bold mb-0" style="font-size:22px;color:#16a34a;"><?php echo e($stats['resolved']); ?></p>
              <p class="text-xs text-secondary mb-0">Résolus</p>
            </div>
          </div>
        </div>
        <hr class="horizontal dark my-3">
        <p class="text-xs text-secondary mb-1">Créé le</p>
        <p class="text-sm font-weight-bold mb-0"><?php echo e($admin->created_at->format('d/m/Y à H:i')); ?></p>
        <?php if($admin->last_login_at): ?>
        <p class="text-xs text-secondary mb-1 mt-2">Dernière connexion</p>
        <p class="text-sm font-weight-bold mb-0"><?php echo e(\Carbon\Carbon::parse($admin->last_login_at)->format('d/m/Y à H:i')); ?></p>
        <?php endif; ?>
      </div>
    </div>

    
    <?php if($logs->count() > 0): ?>
    <div class="card" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">history</i>
          Activité récente
        </h6>
      </div>
      <div class="card-body px-4 pb-3">
        <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="d-flex align-items-start gap-2 py-2 border-bottom">
          <div style="width:6px;height:6px;border-radius:50%;background:var(--color-primary);margin-top:6px;flex-shrink:0;"></div>
          <div style="overflow:hidden;">
            <p class="text-xs font-weight-bold mb-0" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?php echo e($log->action); ?>

            </p>
            <p class="text-xs text-secondary mb-0"><?php echo e($log->created_at->diffForHumans()); ?></p>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  
  <div class="col-lg-8">
    <div class="card" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex align-items-center justify-content-between">
          <h6 class="font-weight-bold mb-0">
            <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">confirmation_number</i>
            Tickets assignés à <?php echo e($admin->name); ?>

            <span class="badge ms-2" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:#fff;font-size:11px;">
              <?php echo e($stats['total']); ?>

            </span>
          </h6>
        </div>
      </div>
      <div class="card-body px-0 pb-0">
        <?php $__empty_1 = true; $__currentLoopData = $assignedTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <?php
            $st  = $statusData[$ticket->sync_status] ?? ['secondary','Inconnu','help'];
            $cat = $catLabels[$ticket->category] ?? '📋 Autre';
            $p   = $ticket->priority ?? 3;
          ?>
          <a href="<?php echo e(route('super-admin.decision-engine')); ?>?ticket=<?php echo e($ticket->id); ?>"
             class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom text-decoration-none"
             style="transition:background 0.15s;"
             onmouseover="this.style.background='rgba(102,126,234,0.05)'"
             onmouseout="this.style.background=''">
            <div class="d-flex align-items-center gap-3" style="min-width:0;">
              <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="material-symbols-rounded text-white" style="font-size:18px;"><?php echo e($st[2]); ?></i>
              </div>
              <div style="min-width:0;">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span class="badge text-white" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));font-size:10px;">#<?php echo e($ticket->id); ?></span>
                  <span class="text-sm font-weight-bold text-dark text-truncate"><?php echo e($ticket->title); ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <?php if($ticket->user): ?>
                    <span class="text-xs text-secondary"><?php echo e($ticket->user->name); ?></span>
                    <span class="text-xs text-secondary">·</span>
                  <?php endif; ?>
                  <span class="text-xs text-secondary"><?php echo e($cat); ?></span>
                  <span class="text-xs text-secondary">·</span>
                  <span class="badge badge-sm bg-gradient-<?php echo e($prioColors[$p] ?? 'secondary'); ?>" style="font-size:10px;">
                    <?php echo e($prioLabels[$p] ?? 'Moyenne'); ?>

                  </span>
                  <span class="text-xs text-secondary">·</span>
                  <span class="text-xs text-secondary"><?php echo e($ticket->created_at->format('d/m/Y')); ?></span>
                </div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <span class="badge bg-gradient-<?php echo e($st[0]); ?>"><?php echo e($st[1]); ?></span>
              <i class="material-symbols-rounded text-secondary" style="font-size:18px;">chevron_right</i>
            </div>
          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="text-center py-5">
            <i class="material-symbols-rounded text-secondary" style="font-size:48px;">inbox</i>
            <p class="text-secondary mt-2 mb-0">Aucun ticket assigné à cet admin</p>
          </div>
        <?php endif; ?>

        <?php if($assignedTickets->hasPages()): ?>
        <div class="px-4 py-3">
          <?php echo e($assignedTickets->links()); ?>

        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/super-admin/admin-detail.blade.php ENDPATH**/ ?>