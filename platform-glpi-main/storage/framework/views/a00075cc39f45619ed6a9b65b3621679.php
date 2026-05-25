<?php $__env->startSection('title','Super Admin Dashboard'); ?>
<?php $__env->startSection('page-title','Super Admin'); ?>

<?php $__env->startSection('content'); ?>


<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-lg border-radius-lg p-3"
         style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center">
          <div class="bg-white border-radius-lg me-3 shadow d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
            <i class="material-symbols-rounded" style="font-size:34px;color:var(--color-primary);">admin_panel_settings</i>
          </div>
          <div>
            <h5 class="text-white font-weight-bolder mb-0">Panneau Super Admin</h5>
          </div>
        </div>
        <div class="text-end d-none d-md-block">
          <p class="text-white text-sm mb-0 opacity-8"><?php echo e(now()->format('l, d F Y')); ?></p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.dash-card { border-radius:18px;padding:22px 24px;color:#fff;display:flex;align-items:center;justify-content:space-between;min-height:110px;position:relative;overflow:hidden;box-shadow:0 8px 28px rgba(0,0,0,.13);transition:transform .18s,box-shadow .18s; }
.dash-card:hover { transform:translateY(-3px);box-shadow:0 14px 36px rgba(0,0,0,.18); }
.dash-card::before { content:'';position:absolute;top:-30px;right:-30px;width:110px;height:110px;border-radius:50%;background:rgba(255,255,255,.08); }
.dash-card::after { content:'';position:absolute;bottom:-20px;right:30px;width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,.05); }
.dash-card-num { font-size:36px;font-weight:800;line-height:1;margin-bottom:4px; }
.dash-card-label { font-size:12px;opacity:.85;font-weight:500; }
.dash-card-icon { font-size:48px;opacity:.3;position:absolute;right:18px;top:50%;transform:translateY(-50%);z-index:0; }
.dash-card-link { font-size:11px;opacity:.85;font-weight:600;text-decoration:none;color:#fff;display:inline-flex;align-items:center;gap:4px;margin-top:8px; }
.dash-card-link:hover { opacity:1;color:#fff; }
.dash-card-sub { font-size:11px;opacity:.7;margin-top:3px;display:flex;align-items:center;gap:5px; }
.dash-card-content { position:relative;z-index:1; }
</style>

<div class="row mb-4 g-3">

  
  <div class="col-xl-3 col-sm-6">
    <a href="<?php echo e(route('super-admin.clients')); ?>?client_type=user" style="text-decoration:none;">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;cursor:pointer;transition:transform .18s,box-shadow .18s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 14px 36px rgba(0,0,0,.18)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
      <div class="dash-card" style="background:linear-gradient(135deg,#7C3AED 0%,#A78BFA 100%);">
        <i class="material-symbols-rounded dash-card-icon">mark_email_unread</i>
        <div class="dash-card-content">
          <div class="dash-card-num"><?php echo e($reclamationsExternes); ?></div>
          <div class="dash-card-label">Réclamations externes</div>
          <div class="dash-card-sub">
            <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;">🟠 Non classifiés</span>
          </div>
          <span class="dash-card-link">Voir clients non classifiés →</span>
        </div>
      </div>
    </div>
    </a>
  </div>

  
  <div class="col-xl-3 col-sm-6">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;">
      <div class="dash-card" style="background:linear-gradient(135deg,#0284C7 0%,#38BDF8 100%);">
        <i class="material-symbols-rounded dash-card-icon">group</i>
        <div class="dash-card-content" style="cursor:pointer;" onclick="window.location='<?php echo e(route('super-admin.clients')); ?>?client_type=client'">
          <div class="dash-card-num"><?php echo e($clientsActifs); ?></div>
          <div class="dash-card-label">Clients actifs</div>
          <div class="dash-card-sub">
            <a href="<?php echo e(route('super-admin.clients')); ?>?client_type=client"
               onclick="event.stopPropagation()"
               style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;color:white;text-decoration:none;">
              🟣 <?php echo e($clientsActifs); ?> classifiés
            </a>
          </div>
          <a href="<?php echo e(route('super-admin.clients')); ?>?client_type=client" class="dash-card-link" onclick="event.stopPropagation()">
            Voir les clients actifs →
          </a>
        </div>
      </div>
    </div>
  </div>

  
  <div class="col-xl-3 col-sm-6">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;">
      <div class="dash-card" style="background:linear-gradient(135deg,#059669 0%,#34D399 100%);">
        <i class="material-symbols-rounded dash-card-icon">admin_panel_settings</i>
        <div class="dash-card-content">
          <div class="dash-card-num"><?php echo e($totalAdmins); ?></div>
          <div class="dash-card-label">Admins</div>
          <div class="dash-card-sub">
            <?php $activeAdmins = \App\Models\User::where('role','admin')->where('is_active',true)->count(); ?>
            <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;"><?php echo e($activeAdmins); ?> actif<?php echo e($activeAdmins > 1 ? 's' : ''); ?></span>
          </div>
          <a href="<?php echo e(route('super-admin.admins')); ?>" class="dash-card-link">Gérer les admins →</a>
        </div>
      </div>
    </div>
  </div>

  
  <div class="col-xl-3 col-sm-6">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;">
      <div class="dash-card" style="background:linear-gradient(135deg,#DC2626 0%,#F87171 100%);">
        <i class="material-symbols-rounded dash-card-icon">pending_actions</i>
        <div class="dash-card-content">
          <div class="dash-card-num"><?php echo e($ticketsNonResolus); ?></div>
          <div class="dash-card-label">Tickets non résolus</div>
          <div class="dash-card-sub">
            <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;">⏳ En attente</span>
          </div>
          <a href="<?php echo e(route('super-admin.tickets')); ?>?status=pending" class="dash-card-link">Voir les tickets en attente →</a>
        </div>
      </div>
    </div>
  </div>

</div>


<div class="row mb-4">
  <div class="col-xl-7 col-lg-12 mb-4">
    <div class="card h-100" style="min-width:0;">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="material-symbols-rounded" style="color:var(--color-primary);font-size:20px;">leaderboard</i>
            <h6 class="mb-0 font-weight-bold">Performance des admins</h6>
            <span class="badge badge-sm" style="background:#eef2ff;color:#4c51bf;font-size:10px;">IA Score</span>
          </div>
          <button onclick="loadLeaderboard()" class="btn btn-sm btn-outline-secondary mb-0 py-1" style="font-size:11px;">
            <i class="material-symbols-rounded" style="font-size:13px;vertical-align:middle;">refresh</i>
          </button>
        </div>
      </div>
      <div class="card-body px-4 pb-3">
        <div id="lbLoading" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div><p class="text-xs text-secondary mt-2 mb-0">Chargement...</p></div>
        <div id="lbContent" class="d-none"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-5 col-lg-12 mb-4">
    <div class="card h-100" style="min-width:0;">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="material-symbols-rounded" style="color:#e53e3e;font-size:20px;">priority_high</i>
            <h6 class="mb-0 font-weight-bold">Tickets urgents</h6>
            <?php if($urgentTickets->count() > 0): ?><span class="badge text-white" style="background:#e53e3e;font-size:10px;"><?php echo e($urgentTickets->count()); ?></span><?php endif; ?>
          </div>
          <a href="<?php echo e(route('super-admin.urgent-tickets')); ?>" class="btn btn-sm mb-0 text-white" style="background:linear-gradient(135deg,#e53e3e,#c53030);font-size:11px;padding:4px 10px;">Voir tous →</a>
        </div>
      </div>
      <div class="card-body px-3 pb-3" style="max-height:400px;overflow-y:auto;">
        <?php $__empty_1 = true; $__currentLoopData = $urgentTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ut): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $pLabels = [5=>'CRITIQUE',4=>'HAUTE',3=>'MOYENNE',2=>'BASSE',1=>'TRÈS BASSE'];
          $pColors = [5=>'#e53e3e',4=>'#f59e0b',3=>'#ecc94b',2=>'#4299e1',1=>'#a0aec0'];
          $pc = $pColors[$ut->priority] ?? '#e53e3e';
          $pl = $pLabels[$ut->priority] ?? 'HAUTE';
          $slaBreach = $ut->sla_breached ?? false;
          $slaRisk   = $ut->sla_risk ?? false;
          $hoursLeft = $ut->sla_hours_left ?? null;
        ?>
        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
          <div style="width:6px;height:6px;border-radius:50%;background:<?php echo e($pc); ?>;flex-shrink:0;"></div>
          <div class="flex-grow-1" style="overflow:hidden;">
            <p class="text-xs font-weight-bold mb-0" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">#<?php echo e($ut->id); ?> — <?php echo e($ut->title); ?></p>
            <div class="d-flex align-items-center gap-1 mt-1">
              <span class="badge" style="background:<?php echo e($pc); ?>;color:#fff;font-size:9px;padding:2px 5px;"><?php echo e($pl); ?></span>
              <span class="text-xs text-secondary"><?php echo e($ut->user->name ?? 'N/A'); ?></span>
            </div>
          </div>
          <div class="d-flex align-items-center gap-1 flex-shrink-0">
            <?php if($slaBreach): ?><span class="badge" style="background:#fef2f2;color:#dc2626;font-size:9px;padding:2px 6px;border:1px solid #fecaca;font-weight:700;">SLA!</span><span style="font-size:10px;font-weight:600;color:#dc2626;white-space:nowrap;">+<?php echo e(abs($hoursLeft)); ?>h</span>
            <?php elseif($slaRisk): ?><span class="badge" style="background:#fff7ed;color:#c2410c;font-size:9px;padding:2px 6px;border:1px solid #fed7aa;">Risque</span><span style="font-size:10px;font-weight:600;color:#ed8936;white-space:nowrap;"><?php echo e($hoursLeft); ?>h rest.</span><?php endif; ?>
            <a href="<?php echo e(route('super-admin.decision-engine')); ?>?ticket=<?php echo e($ut->id); ?>" class="btn btn-sm mb-0 px-2 py-1" style="background:linear-gradient(135deg,#e53e3e,#c53030);color:#fff;font-size:10px;min-width:24px;text-align:center;">→</a>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="text-center py-5">
          <i class="material-symbols-rounded text-success" style="font-size:36px;">check_circle</i>
          <p class="text-xs text-secondary mt-2 mb-0">Aucun ticket urgent</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>


<div class="row mb-4">
  <div class="col-xl-6 col-lg-12 mb-4">
    <div class="card h-100" style="min-width:0;">
      <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
        <div>
          <h6 class="mb-0 font-weight-bold"><i class="material-symbols-rounded me-1 text-info" style="font-size:18px;vertical-align:middle;">shield_person</i>Gestion des Admins</h6>
          <p class="text-xs text-secondary mb-0"><?php echo e($totalAdmins); ?> admin(s)</p>
        </div>
        <a href="<?php echo e(route('super-admin.admins.create')); ?>" class="btn btn-sm mb-0 text-white" style="background:linear-gradient(135deg,#11cdef,#1171ef);">
          <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">add</i>Nouveau
        </a>
      </div>
      <div class="card-body px-0 pb-0 table-responsive">
        <table class="table align-items-center mb-0">
          <thead><tr>
            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Admin</th>
            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Statut</th>
            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
          </tr></thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentAdmins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td>
                <div class="d-flex px-3 py-2 align-items-center">
                  <div class="me-2 border-radius-md d-flex align-items-center justify-content-center bg-gradient-info" style="width:32px;height:32px;min-width:32px;">
                    <span class="text-white" style="font-size:11px;font-weight:700;"><?php echo e(strtoupper(substr($admin->name,0,2))); ?></span>
                  </div>
                  <div><p class="mb-0 text-xs font-weight-bold"><?php echo e($admin->name); ?></p><p class="text-xs text-secondary mb-0"><?php echo e($admin->email); ?></p></div>
                </div>
              </td>
              <td class="text-center align-middle">
                <?php if($admin->is_active): ?><span class="badge badge-sm bg-gradient-success">Actif</span>
                <?php else: ?><span class="badge badge-sm bg-gradient-danger">Inactif</span><?php endif; ?>
              </td>
              <td class="text-center align-middle">
                <div class="d-flex justify-content-center gap-1">
                  <form action="<?php echo e(route('super-admin.admins.toggle', $admin->id)); ?>" method="POST"><?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-sm mb-0 px-2 py-1" style="background:<?php echo e($admin->is_active?'#ffc107':'#2dce89'); ?>;color:white;font-size:11px;min-width:72px;"><?php echo e($admin->is_active?'Desactiver':'Activer'); ?></button>
                  </form>
                  <form action="<?php echo e(route('super-admin.admins.delete', $admin->id)); ?>" method="POST" onsubmit="return confirm('Supprimer ?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-sm bg-gradient-danger mb-0 px-2 py-1"><i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">delete</i></button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="3" class="text-center py-4 text-secondary text-sm">Aucun admin</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-6 col-lg-12 mb-4">
    <div class="card h-100" style="min-width:0;">
      <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
        <div>
          <h6 class="mb-0 font-weight-bold"><i class="material-symbols-rounded me-1 text-success" style="font-size:18px;vertical-align:middle;">group</i>Gestion des Clients</h6>
          <p class="text-xs text-secondary mb-0"><?php echo e($totalClients); ?> client(s)</p>
        </div>
        <a href="<?php echo e(route('super-admin.clients')); ?>" class="btn btn-sm mb-0 text-white" style="background:linear-gradient(135deg,#2dce89,#2dcecc);">Voir tout</a>
      </div>
      <div class="card-body px-0 pb-0 table-responsive">
        <table class="table align-items-center mb-0">
          <thead><tr>
            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Client</th>
            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tickets</th>
            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Statut</th>
            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Action</th>
          </tr></thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentClients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td>
                <div class="d-flex px-3 py-2 align-items-center">
                  <div class="me-2 border-radius-md d-flex align-items-center justify-content-center bg-gradient-success" style="width:32px;height:32px;min-width:32px;">
                    <span class="text-white" style="font-size:11px;font-weight:700;"><?php echo e(strtoupper(substr($client->name,0,2))); ?></span>
                  </div>
                  <div><p class="mb-0 text-xs font-weight-bold"><?php echo e($client->name); ?></p><p class="text-xs text-secondary mb-0"><?php echo e($client->email); ?></p></div>
                </div>
              </td>
              <td class="text-center align-middle"><span class="badge badge-sm bg-gradient-dark"><?php echo e($client->tickets_count ?? 0); ?></span></td>
              <td class="text-center align-middle">
                <?php if($client->is_active): ?><span class="badge badge-sm bg-gradient-success">Actif</span>
                <?php else: ?><span class="badge badge-sm bg-gradient-danger">Inactif</span><?php endif; ?>
              </td>
              <td class="text-center align-middle">
                <form action="<?php echo e(route('super-admin.clients.toggle', $client->id)); ?>" method="POST"><?php echo csrf_field(); ?>
                  <button type="submit" class="btn btn-sm mb-0 px-2 py-1" style="background:<?php echo e($client->is_active?'#ffc107':'#2dce89'); ?>;color:white;font-size:11px;min-width:72px;"><?php echo e($client->is_active?'Desactiver':'Activer'); ?></button>
                </form>
              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="4" class="text-center py-4 text-secondary text-sm">Aucun client</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>


<div class="row mb-4">
  <div class="col-xl-5 col-lg-12 mb-4">
    <div class="card h-100" style="min-width:0;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="mb-0 font-weight-bold">Tickets par mois</h6>
        <p class="text-xs text-secondary mb-0">6 derniers mois</p>
      </div>
      <div class="card-body p-3"><div style="position:relative;height:220px;"><canvas id="monthlyChart"></canvas></div></div>
    </div>
  </div>
  <div class="col-xl-7 col-lg-12 mb-4">
    <div class="card h-100" style="min-width:0;">
      <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
        <div>
          <h6 class="mb-0 font-weight-bold">Activité récente</h6>
          <p class="text-xs text-secondary mb-0">Dernières actions sur la plateforme</p>
        </div>
        <a href="<?php echo e(route('super-admin.logs')); ?>" class="btn btn-sm btn-outline-primary px-3" style="font-size:11px;">Voir tout →</a>
      </div>
      <div class="card-body p-3" style="overflow-y:auto;max-height:370px;">
        <?php $recentActivity = \App\Models\AuditLog::latest()->take(12)->get(); ?>
        <?php $__empty_1 = true; $__currentLoopData = $recentActivity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $colorMap = ['success'=>['bg'=>'#2dce8920','icon'=>'#2dce89','border'=>'#2dce89'],'danger'=>['bg'=>'#f5365c20','icon'=>'#f5365c','border'=>'#f5365c'],'warning'=>['bg'=>'#fb634020','icon'=>'#fb6340','border'=>'#fb6340'],'info'=>['bg'=>'#11cdef20','icon'=>'#11cdef','border'=>'#11cdef'],'primary'=>['bg'=>'rgba(108,99,255,0.1)','icon'=>'var(--color-primary)','border'=>'var(--color-primary)'],'secondary'=>['bg'=>'#adb5bd20','icon'=>'#adb5bd','border'=>'#adb5bd'],'dark'=>['bg'=>'#21263320','icon'=>'#212633','border'=>'#212633']];
          $c = $colorMap[$log->action_color] ?? $colorMap['secondary'];
        ?>
        <div class="d-flex align-items-start mb-3">
          <div class="d-flex align-items-center justify-content-center rounded-circle me-3 flex-shrink-0" style="width:34px;height:34px;background:<?php echo e($c['bg']); ?>;border:1.5px solid <?php echo e($c['border']); ?>;">
            <i class="material-symbols-rounded" style="font-size:16px;color:<?php echo e($c['icon']); ?>;"><?php echo e($log->action_icon); ?></i>
          </div>
          <div class="flex-grow-1" style="min-width:0;">
            <div class="d-flex justify-content-between align-items-start">
              <div style="min-width:0;">
                <p class="text-xs font-weight-bold mb-0 text-truncate" style="max-width:280px;"><?php echo e($log->description); ?></p>
                <p class="text-xs text-secondary mb-0 mt-1">
                  <span class="fw-bold" style="color:<?php echo e($c['icon']); ?>;"><?php echo e($log->user_name); ?></span> &nbsp;·&nbsp;
                  <span class="badge px-2 py-1" style="background:<?php echo e($c['bg']); ?>;color:<?php echo e($c['icon']); ?>;font-size:9px;border-radius:6px;"><?php echo e($log->module); ?></span>
                </p>
              </div>
              <span class="text-xs text-secondary flex-shrink-0 ms-2" style="white-space:nowrap;"><?php echo e($log->created_at->diffForHumans()); ?></span>
            </div>
          </div>
        </div>
        <?php if(!$loop->last): ?><hr class="my-2" style="border-color:rgba(0,0,0,0.06);"><?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="text-center py-5 text-secondary"><i class="material-symbols-rounded" style="font-size:48px;">history</i><p class="mt-2 text-sm">Aucune activité enregistrée</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>


<style>
.sa-dash-table{width:100%;border-collapse:collapse;}
.sa-dash-table thead tr{background:var(--bs-tertiary-bg,#f8fafc);}
.sa-dash-table thead th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;padding:10px 14px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);white-space:nowrap;}
.sa-dash-table tbody tr{border-bottom:1px solid var(--bs-border-color,#f1f5f9);transition:background .12s;cursor:pointer;}
.sa-dash-table tbody tr:hover{background:rgba(59,91,219,.04);}
.sa-dash-table tbody tr:last-child{border-bottom:none;}
.sa-dash-table td{padding:12px 14px;vertical-align:middle;}

/* ── Responsive ── */
.sa-table-scroll{position:relative;overflow-x:auto;-webkit-overflow-scrolling:touch;}
.sa-table-scroll::after{
  content:'';pointer-events:none;position:absolute;top:0;right:0;bottom:0;width:36px;
  background:linear-gradient(to right,transparent,rgba(255,255,255,.85));
  border-radius:0 14px 14px 0;opacity:0;transition:opacity .3s;
}
.sa-table-scroll.has-scroll::after{opacity:1;}
@media(max-width:991px){
  .sa-col-hide{display:none !important;}
}
@media(max-width:575px){
  .sa-dash-table td,.sa-dash-table th{padding:9px 9px;}
  .sa-col-hide-sm{display:none !important;}
}
.sa-ctype{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;}
.sa-ctype-client{background:#F5F3FF;color:#7C3AED;}
.sa-ctype-new{background:#FFF7ED;color:#C2410C;}
.sa-prio{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap;}
.sa-prio-1{background:#f1f5f9;color:#475569;}.sa-prio-2{background:#e0f2fe;color:#0369a1;}.sa-prio-3{background:#fef3c7;color:#b45309;}.sa-prio-4{background:#fee2e2;color:#b91c1c;}.sa-prio-5{background:#1e1b4b;color:#fff;}
.sa-stbadge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;}
.sa-st-pending{background:#fef3c7;color:#b45309;}.sa-st-inprogress{background:#dbeafe;color:#1d4ed8;}.sa-st-resolved{background:#d1fae5;color:#065f46;}.sa-st-closed{background:#f1f5f9;color:#475569;}
.sa-replied{background:#d1fae5;color:#065f46;font-size:9px;font-weight:700;padding:2px 6px;border-radius:99px;display:inline-block;margin-top:3px;}
.sa-btn-reply{display:inline-flex;align-items:center;gap:5px;padding:6px 16px;border-radius:12px;font-size:12px;font-weight:600;border:none;cursor:pointer;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:#fff;text-decoration:none;transition:opacity .15s;white-space:nowrap;}
.sa-btn-reply:hover{opacity:.85;color:#fff;}

/* ── DARK MODE overrides for dashboard badges & table ── */
[data-bs-theme="dark"] .sa-dash-table thead tr { background: #0f172a !important; }
[data-bs-theme="dark"] .sa-dash-table thead th { color: #475569 !important; border-bottom-color: #334155 !important; }
[data-bs-theme="dark"] .sa-dash-table tbody tr { border-bottom-color: #334155 !important; }
[data-bs-theme="dark"] .sa-dash-table tbody tr:hover { background: rgba(255,255,255,0.04) !important; }
[data-bs-theme="dark"] .sa-ctype-tns { background: #1e1b4b !important; color: #a5b4fc !important; }
[data-bs-theme="dark"] .sa-ctype-l2t { background: #2e1065 !important; color: #c4b5fd !important; }
[data-bs-theme="dark"] .sa-ctype-new { background: #431407 !important; color: #fdba74 !important; }
[data-bs-theme="dark"] .sa-prio-1 { background: #1e293b !important; color: #94a3b8 !important; }
[data-bs-theme="dark"] .sa-prio-2 { background: #0c4a6e !important; color: #7dd3fc !important; }
[data-bs-theme="dark"] .sa-prio-3 { background: #451a03 !important; color: #fcd34d !important; }
[data-bs-theme="dark"] .sa-prio-4 { background: #450a0a !important; color: #f87171 !important; }
[data-bs-theme="dark"] .sa-prio-5 { background: #312e81 !important; color: #c4b5fd !important; }
[data-bs-theme="dark"] .sa-st-pending { background: #451a03 !important; color: #fcd34d !important; }
[data-bs-theme="dark"] .sa-st-inprogress { background: #1e3a5f !important; color: #93c5fd !important; }
[data-bs-theme="dark"] .sa-st-resolved { background: #064e3b !important; color: #6ee7b7 !important; }
[data-bs-theme="dark"] .sa-st-closed { background: #1e293b !important; color: #94a3b8 !important; }
[data-bs-theme="dark"] .sa-replied { background: #064e3b !important; color: #6ee7b7 !important; }
</style>

<div class="row">
  <div class="col-12 mb-4">
    <div class="card" style="border-radius:14px;border:1px solid var(--bs-border-color,#e2e8f0);overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.04);">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);">
        <div><h6 style="margin:0;font-weight:700;font-size:14px;">Derniers tickets</h6><p class="text-xs text-secondary mb-0">Support recent</p></div>
        <div class="d-flex align-items-center gap-2">
          <span style="background:var(--bs-tertiary-bg,#f1f5f9);color:#64748b;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;"><?php echo e(count($recentTickets)); ?> ticket(s)</span>
          <a href="<?php echo e(route('super-admin.tickets')); ?>" style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:9px;font-size:12px;font-weight:600;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:#fff;text-decoration:none;">
            <i class="material-symbols-rounded" style="font-size:15px;">open_in_new</i>Voir tout
          </a>
        </div>
      </div>
      <div class="sa-table-scroll table-responsive" id="saRecentTable">
        <table class="sa-dash-table">
          <thead>
            <tr>
              <th>ID</th><th>Titre</th><th>Client</th><th>Type</th><th class="sa-col-hide">Catégorie</th>
              <th class="sa-col-hide-sm" style="text-align:center;">Priorité</th><th style="text-align:center;">Statut</th><th class="sa-col-hide" style="text-align:center;">Date</th><th style="text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $catLabels = ['incident_technique'=>['🔴','Incident'],'integration_api'=>['🔵','API SMS'],'facturation'=>['🟡','Facturation'],'plateforme'=>['🟢','Plateforme'],'paiement_mobile'=>['🟠','Paiement'],'autre'=>['⚪','Autre']];
              $cat = $catLabels[$ticket->category] ?? ['⚪', $ticket->category ?? 'Autre'];
              $p = $ticket->priority ?? 3;
              $pLabels = [1=>'Très basse',2=>'Basse',3=>'Moyenne',4=>'Haute',5=>'Critique'];
              $ct = $ticket->user?->client_type;
              $ctBadge = $ct === 'client'
                ? '<span class="sa-ctype sa-ctype-client">🟣 Client</span>'
                : '<span class="sa-ctype sa-ctype-new">🟠 Non classifié</span>';
              $stMap = ['pending'=>['sa-st-pending','En attente','schedule'],'in_progress'=>['sa-st-inprogress','En cours','autorenew'],'resolved'=>['sa-st-resolved','Résolu','check_circle'],'closed'=>['sa-st-closed','Clôturé','lock'],'synced'=>['sa-st-pending','Sync','sync'],'failed'=>['sa-st-pending','Erreur','error']];
              $st = $stMap[$ticket->sync_status] ?? ['sa-st-pending','Inconnu','help'];
            ?>
            <tr onclick="window.location='<?php echo e(route('super-admin.decision-engine')); ?>?ticket=<?php echo e($ticket->id); ?>'">
              <td><span style="font-size:11px;font-weight:700;color:#3b5bdb;">#<?php echo e($ticket->id); ?></span></td>
              <td style="max-width:230px;">
                <p style="font-size:12px;font-weight:600;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e(Str::limit($ticket->title ?? 'Sans titre', 38)); ?></p>
                <?php if($ticket->description): ?><p style="font-size:11px;color:#94a3b8;margin:0;"><?php echo e(Str::limit($ticket->description, 48)); ?></p><?php endif; ?>
                <?php if($ticket->solution): ?><span class="sa-replied">✅ Répondu</span><?php endif; ?>
              </td>
              <td>
                <div style="display:flex;align-items:center;gap:7px;">
                  <?php if($ticket->user?->avatar): ?><img src="<?php echo e(asset('storage/' . $ticket->user->avatar)); ?>" style="width:26px;height:26px;border-radius:50%;object-fit:cover;border:1.5px solid #e2e8f0;flex-shrink:0;" alt="">
                  <?php else: ?><div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#3b5bdb,#6741d9);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><span style="font-size:9px;font-weight:700;color:#fff;"><?php echo e(strtoupper(substr($ticket->user->name ?? 'U', 0, 2))); ?></span></div><?php endif; ?>
                  <span style="font-size:12px;color:#64748b;"><?php echo e($ticket->user->name ?? 'N/A'); ?></span>
                </div>
              </td>
              <td><?php echo $ctBadge; ?></td>
              <td class="sa-col-hide"><span style="font-size:12px;"><?php echo e($cat[0]); ?> <?php echo e($cat[1]); ?></span></td>
              <td class="sa-col-hide-sm" style="text-align:center;"><span class="sa-prio sa-prio-<?php echo e($p); ?>"><?php echo e($pLabels[$p] ?? 'Moyenne'); ?></span></td>
              <td style="text-align:center;"><span class="sa-stbadge <?php echo e($st[0]); ?>"><i class="material-symbols-rounded" style="font-size:11px;vertical-align:middle;"><?php echo e($st[2]); ?></i> <?php echo e($st[1]); ?></span></td>
              <td class="sa-col-hide" style="text-align:center;"><span style="font-size:11px;color:#94a3b8;"><?php echo e($ticket->created_at->format('d/m/Y')); ?></span></td>
              <td style="text-align:center;" onclick="event.stopPropagation()">
                <a href="<?php echo e(route('super-admin.tickets.show', $ticket->id)); ?>#tab-reply"
                   class="sa-btn-reply">
                  <i class="material-symbols-rounded" style="font-size:15px;">reply</i>Répondre
                </a>
              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;"><i class="material-symbols-rounded" style="font-size:48px;color:#cbd5e1;display:block;">confirmation_number</i><p style="color:#94a3b8;font-size:14px;margin:8px 0 0;">Aucun ticket pour le moment</p></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>


<script>
(function(){
  var wrap = document.getElementById('saRecentTable');
  if(!wrap) return;
  function check(){ wrap.classList.toggle('has-scroll', wrap.scrollWidth > wrap.clientWidth + 5); }
  check();
  window.addEventListener('resize', check);
})();
</script>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('page-scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {getAttribute:function(){return '';}}).getAttribute('content');

  if (typeof Chart !== 'undefined') {
    var ctxM = document.getElementById('monthlyChart');
    if (ctxM) {
      var _chartDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
      var _tickClr   = _chartDark ? '#64748b' : '#aaa';
      var _gridClr   = _chartDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
      var _fillClr   = _chartDark ? 'rgba(102,126,234,0.08)' : 'rgba(102,126,234,0.12)';
      new Chart(ctxM, {
        type: 'line',
        data: {
          labels: <?php echo json_encode(array_column($ticketsByMonth, 'month')); ?>,
          datasets: [{ label: 'Tickets', data: <?php echo json_encode(array_column($ticketsByMonth, 'count')); ?>,
            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim(), backgroundColor: _fillClr, borderWidth: 3,
            pointBackgroundColor: '#764ba2', pointBorderColor: '#fff', pointBorderWidth: 2,
            pointRadius: 5, fill: true, tension: 0.4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, grid: { color: _gridClr }, ticks: { color: _tickClr, font: { size: 11 }, precision: 0 } },
                    x: { grid: { display: false }, ticks: { color: _tickClr, font: { size: 11 } } } } }
      });
    }
  }

  window.loadLeaderboard = function() {
    var lb = document.getElementById('lbLoading'), lc = document.getElementById('lbContent');
    if (lb) { lb.classList.remove('d-none'); lb.innerHTML='<div class="spinner-border spinner-border-sm text-primary"></div>'; }
    if (lc) lc.classList.add('d-none');
    var ctrl = new AbortController(); setTimeout(function() { ctrl.abort(); }, 10000);
    fetch('/super-admin/ai/leaderboard', { headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF}, signal:ctrl.signal })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (lb) lb.classList.add('d-none');
      var admins = data.admins || [];
      var html = '';
      if (!admins.length) {
        html = '<p class="text-xs text-secondary text-center py-3">Aucun admin actif</p>';
      } else {
        var _isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        var _borderClr   = _isDark ? '#334155'  : '#f1f5f9';
        var _progressBg  = _isDark ? '#334155'  : '#e2e8f0';
        var _strongClr   = _isDark ? '#e2e8f0'  : '#1e293b';
        var _suggBg      = _isDark ? '#1e293b'  : '#f8f9ff';
        var _suggTxtClr  = _isDark ? '#94a3b8'  : '#475569';

        admins.forEach(function(a, i) {
          var medal = ['🥇','🥈','🥉'][i] || ('#'+(i+1));
          var barColor = a.score>=80?'#10b981':a.score>=60?'#3b82f6':a.score>=30?'#f59e0b':'#ef4444';
          var initials = (a.name||'??').substring(0,2).toUpperCase();
          var answered = a.answered || a.resolved || 0;
          var total    = a.total || 0;

          html += '<div style="border-bottom:1px solid '+_borderClr+';padding:11px 0;">';
          html += '<div class="d-flex align-items-center gap-2">';
          html += '<span style="font-size:16px;min-width:24px;">' + medal + '</span>';
          html += '<div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));font-size:12px;font-weight:700;color:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;">'+initials+'</div>';
          html += '<div style="flex:1;min-width:0;">';
          html += '<div class="d-flex align-items-center justify-content-between mb-1">';
          html += '<p class="text-sm font-weight-bold mb-0">'+a.name+'</p>';
          html += '<span class="text-xs font-weight-bold" style="color:'+barColor+';white-space:nowrap;">'+a.score+' pts</span>';
          html += '</div>';
          // Progress bar
          html += '<div style="height:6px;border-radius:3px;background:'+_progressBg+';margin-bottom:5px;">';
          html += '<div style="height:6px;border-radius:3px;width:'+Math.min(100,a.score)+'%;background:'+barColor+';transition:width .6s ease;"></div>';
          html += '</div>';
          // Stats row
          html += '<div class="d-flex flex-wrap gap-2">';
          html += '<span class="text-xs text-secondary">✉️ <strong style="color:'+_strongClr+';">'+answered+'</strong> réponse(s)</span>';
          html += '<span class="text-xs text-secondary">✅ <strong style="color:'+_strongClr+';">'+a.resolved+'</strong> résolu(s)</span>';
          html += '<span class="text-xs text-secondary">📋 <strong style="color:'+_strongClr+';">'+total+'</strong> ticket(s)</span>';
          if (a.avg_hours) {
            html += '<span class="text-xs text-secondary">⏱️ <strong style="color:'+_strongClr+';">'+a.avg_hours+'h</strong> moy. résolution</span>';
          }
          if (a.urgent_handled > 0) {
            html += '<span class="text-xs" style="color:#ef4444;">🚨 <strong>'+a.urgent_handled+'</strong> urgent(s) traité(s)</span>';
          }
          if (a.days_active) {
            html += '<span class="text-xs text-secondary">📅 <strong style="color:'+_strongClr+';">'+a.days_active+'</strong> jours de service</span>';
          }
          html += '</div>';
          if (a.email) {
            html += '<div class="text-xs" style="color:#94a3b8;margin-top:2px;">'+a.email+'</div>';
          }
          html += '</div></div>';
          if (a.suggestion) {
            html += '<div style="background:'+_suggBg+';border-left:3px solid '+barColor+';padding:5px 10px;margin-top:6px;border-radius:0 6px 6px 0;">';
            html += '<p class="text-xs mb-0" style="color:'+_suggTxtClr+';">💡 '+a.suggestion+'</p></div>';
          }
          html += '</div>';
        });
      }
      if (lc) { lc.innerHTML = html; lc.classList.remove('d-none'); }
    })
    .catch(function() {
      if (lb) lb.classList.add('d-none');
      if (lc) { lc.innerHTML='<p class="text-xs text-secondary text-center py-3">Service IA indisponible</p>'; lc.classList.remove('d-none'); }
    });
  };

  loadLeaderboard();
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/super-admin/dashboard.blade.php ENDPATH**/ ?>