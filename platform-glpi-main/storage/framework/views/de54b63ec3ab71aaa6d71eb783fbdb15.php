<?php $__env->startSection('title','Admin Dashboard'); ?>
<?php $__env->startSection('page-title','Admin Dashboard'); ?>

<?php $__env->startSection('content'); ?>


<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-lg border-radius-lg p-3"
         style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-xl bg-white border-radius-lg p-2 me-3 shadow">
            <i class="material-symbols-rounded" style="font-size:40px; color:var(--color-primary);">support_agent</i>
          </div>
          <div>
            <h5 class="text-white font-weight-bolder mb-0">Tableau de bord Admin</h5>
            <p class="text-white text-sm mb-0 opacity-8">
              Bienvenue, <strong><?php echo e(auth()->user()->name); ?></strong> 👋
            </p>
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
.dash-card {
  border-radius:18px;padding:22px 24px;color:#fff;
  display:flex;align-items:center;justify-content:space-between;
  min-height:110px;position:relative;overflow:hidden;
  box-shadow:0 8px 28px rgba(0,0,0,.13);
  transition:transform .18s,box-shadow .18s;
}
.dash-card:hover{transform:translateY(-3px);box-shadow:0 14px 36px rgba(0,0,0,.18);}
.dash-card::before{content:'';position:absolute;top:-30px;right:-30px;width:110px;height:110px;border-radius:50%;background:rgba(255,255,255,.08);}
.dash-card::after{content:'';position:absolute;bottom:-20px;right:30px;width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,.05);}
.dash-card-num{font-size:36px;font-weight:800;line-height:1;margin-bottom:4px;}
.dash-card-label{font-size:12px;opacity:.85;font-weight:500;}
.dash-card-icon{font-size:48px;opacity:.3;position:absolute;right:18px;top:50%;transform:translateY(-50%);z-index:0;}
.dash-card-link{font-size:11px;opacity:.85;font-weight:600;text-decoration:none;color:#fff;display:inline-flex;align-items:center;gap:4px;margin-top:8px;}
.dash-card-link:hover{opacity:1;color:#fff;}
.dash-card-sub{font-size:11px;opacity:.7;margin-top:3px;display:flex;align-items:center;gap:5px;}
.dash-card-content{position:relative;z-index:1;}
</style>

<div class="row mb-4 g-3">

  
  <div class="col-xl-3 col-sm-6">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;">
      <div class="dash-card" style="background:linear-gradient(135deg,#7C3AED 0%,#A78BFA 100%);">
        <i class="material-symbols-rounded dash-card-icon">mark_email_unread</i>
        <div class="dash-card-content">
          <div class="dash-card-num"><?php echo e($reclamationsExternes); ?></div>
          <div class="dash-card-label">Réclamations externes</div>
          <div class="dash-card-sub">
            <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;">🟠 Non classifiés</span>
          </div>
          <a href="<?php echo e(route('admin.clients')); ?>?client_type=user" class="dash-card-link">Voir clients non classifiés →</a>
        </div>
      </div>
    </div>
  </div>

  
  <div class="col-xl-3 col-sm-6">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;">
      <div class="dash-card" style="background:linear-gradient(135deg,#0284C7 0%,#38BDF8 100%);">
        <i class="material-symbols-rounded dash-card-icon">group</i>
        <div class="dash-card-content">
          <div class="dash-card-num"><?php echo e($clientsActifs); ?></div>
          <div class="dash-card-label">Clients actifs</div>
          <div class="dash-card-sub">
            <a href="<?php echo e(route('admin.clients')); ?>?client_type=client"
               style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;color:white;text-decoration:none;">
              🟣 <?php echo e($countClient); ?> classifiés
            </a>
          </div>
          <a href="<?php echo e(route('admin.clients')); ?>?client_type=client" class="dash-card-link">Voir les clients actifs →</a>
        </div>
      </div>
    </div>
  </div>

  
  <div class="col-xl-3 col-sm-6">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;">
      <div class="dash-card" style="background:linear-gradient(135deg,#059669 0%,#34D399 100%);">
        <i class="material-symbols-rounded dash-card-icon">today</i>
        <div class="dash-card-content">
          <div class="dash-card-num"><?php echo e($ticketsAujourdhui); ?></div>
          <div class="dash-card-label">Tickets aujourd'hui</div>
          <div class="dash-card-sub">
            <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;"><?php echo e(now()->format('d/m/Y')); ?></span>
          </div>
          <a href="<?php echo e(route('admin.tickets')); ?>?date_from=<?php echo e(now()->format('Y-m-d')); ?>&date_to=<?php echo e(now()->format('Y-m-d')); ?>" class="dash-card-link">Voir les tickets du jour →</a>
        </div>
      </div>
    </div>
  </div>

  
  <div class="col-xl-3 col-sm-6">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;">
      <div class="dash-card" style="background:linear-gradient(135deg,#DC2626 0%,#F87171 100%);">
        <i class="material-symbols-rounded dash-card-icon">pending_actions</i>
        <div class="dash-card-content">
          <div class="dash-card-num"><?php echo e($openTickets); ?></div>
          <div class="dash-card-label">Tickets en attente</div>
          <div class="dash-card-sub">
            <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;">⏳ À traiter</span>
          </div>
          <a href="<?php echo e(route('admin.tickets')); ?>?status=pending" class="dash-card-link">Voir les tickets en attente →</a>
        </div>
      </div>
    </div>
  </div>

</div>


<div class="row mb-4">

  
  <div class="col-lg-4 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0 font-weight-bold">Répartition des tickets</h6>
            <p class="text-sm text-muted mb-0">Par statut</p>
          </div>
          <span class="badge badge-sm" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%); color:white;">
            Total: <?php echo e($totalTickets); ?>

          </span>
        </div>
      </div>
      <div class="card-body p-3">
        <div class="chart">
          <canvas id="statusChart" height="200"></canvas>
        </div>
        <div class="row mt-3">
          <div class="col-4 text-center">
            <a href="<?php echo e(route('admin.tickets')); ?>?status=pending"
               class="badge text-white w-100 text-decoration-none d-block"
               style="background:#ef4444;padding:6px 4px;border-radius:8px;font-size:11px;">
              <?php echo e($openTickets); ?> EN ATTENTE
            </a>
          </div>
          <div class="col-4 text-center">
            <a href="<?php echo e(route('admin.tickets')); ?>?status=in_progress"
               class="badge text-white w-100 text-decoration-none d-block"
               style="background:#f59e0b;padding:6px 4px;border-radius:8px;font-size:11px;">
              <?php echo e($inProgressTickets); ?> EN COURS
            </a>
          </div>
          <div class="col-4 text-center">
            <a href="<?php echo e(route('admin.tickets')); ?>?status=resolved"
               class="badge text-white w-100 text-decoration-none d-block"
               style="background:#22c55e;padding:6px 4px;border-radius:8px;font-size:11px;">
              <?php echo e($closedTickets); ?> RÉSOLUS
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  
  <div class="col-lg-4 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="material-symbols-rounded" style="color:#e53e3e;font-size:20px;">priority_high</i>
            <h6 class="mb-0 font-weight-bold">Tickets urgents</h6>
            <?php if($urgentTickets->count() > 0): ?>
              <span class="badge text-white" style="background:#e53e3e;font-size:10px;"><?php echo e($urgentTickets->count()); ?></span>
            <?php endif; ?>
          </div>
          <a href="<?php echo e(route('admin.urgent-tickets')); ?>"
             class="btn btn-sm mb-0 text-white"
             style="background:linear-gradient(135deg,#e53e3e,#c53030);font-size:11px;padding:4px 10px;">
            Voir tous →
          </a>
        </div>
      </div>
      <div class="card-body px-3 pb-3" style="max-height:340px;overflow-y:auto;">
        <?php $__empty_1 = true; $__currentLoopData = $urgentTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ut): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $pLabels = [5=>'CRITIQUE', 4=>'HAUTE', 3=>'MOYENNE', 2=>'BASSE', 1=>'TRÈS BASSE'];
          $pColors = [5=>'#e53e3e', 4=>'#f59e0b', 3=>'#ecc94b', 2=>'#4299e1', 1=>'#a0aec0'];
          $pc = $pColors[$ut->priority] ?? '#e53e3e';
          $pl = $pLabels[$ut->priority] ?? 'HAUTE';
          $slaBreach = $ut->sla_breached ?? false;
          $slaRisk   = $ut->sla_risk ?? false;
          $hoursLeft = $ut->sla_hours_left ?? null;
        ?>
        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
          <div style="width:6px;height:6px;border-radius:50%;background:<?php echo e($pc); ?>;flex-shrink:0;"></div>
          <div class="flex-grow-1" style="overflow:hidden;">
            <p class="text-xs font-weight-bold mb-0" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              #<?php echo e($ut->id); ?> — <?php echo e($ut->title); ?>

            </p>
            <div class="d-flex align-items-center gap-1 mt-1">
              <span class="badge" style="background:<?php echo e($pc); ?>;color:#fff;font-size:9px;padding:2px 5px;"><?php echo e($pl); ?></span>
              <span class="text-xs text-secondary"><?php echo e($ut->user->name ?? 'N/A'); ?></span>
            </div>
          </div>
          <div class="d-flex align-items-center gap-1 flex-shrink-0">
            <?php if($slaBreach): ?>
              <span class="badge" style="background:#fef2f2;color:#dc2626;font-size:9px;padding:2px 6px;border:1px solid #fecaca;font-weight:700;">SLA!</span>
              <span style="font-size:10px;font-weight:600;color:#dc2626;white-space:nowrap;">+<?php echo e(abs($hoursLeft)); ?>h</span>
            <?php elseif($slaRisk): ?>
              <span class="badge" style="background:#fff7ed;color:#c2410c;font-size:9px;padding:2px 6px;border:1px solid #fed7aa;">Risque</span>
              <span style="font-size:10px;font-weight:600;color:#ed8936;white-space:nowrap;"><?php echo e($hoursLeft); ?>h rest.</span>
            <?php endif; ?>
            <a href="<?php echo e(route('admin.tickets.show', $ut->id)); ?>"
               class="btn btn-sm mb-0 px-2 py-1"
               style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:#fff;font-size:10px;min-width:24px;text-align:center;">→</a>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="text-center py-4">
          <i class="material-symbols-rounded text-success" style="font-size:36px;">check_circle</i>
          <p class="text-xs text-secondary mt-2 mb-0">Aucun ticket urgent</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  
  <div class="col-lg-4 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0 font-weight-bold">Top clients</h6>
            <p class="text-sm text-muted mb-0">Les plus actifs par nombre de tickets</p>
          </div>
          <a href="<?php echo e(route('admin.clients')); ?>" class="btn btn-sm btn-outline-primary px-3" style="font-size:11px;">Voir tous →</a>
        </div>
      </div>
      <div class="card-body px-4 pb-3 pt-3">
        <?php
          $topClients = $recentClients->sortByDesc('tickets_count')->take(5);
          $maxTickets = $topClients->max('tickets_count') ?: 1;
        ?>
        <?php $__empty_1 = true; $__currentLoopData = $topClients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <?php
            $pct  = round(($client->tickets_count / $maxTickets) * 100);
            $type = $client->client_type ?? 'user';
            $color = $type === 'client' ? '#6d28d9' : '#adb5bd';
            $initials = strtoupper(substr($client->name, 0, 1));
          ?>
          <div class="d-flex align-items-center mb-3">
            <div class="me-3 d-flex align-items-center justify-content-center rounded-circle text-white fw-bold"
                 style="width:36px;height:36px;min-width:36px;background:<?php echo e($color); ?>;font-size:14px;">
              <?php echo e($initials); ?>

            </div>
            <div class="flex-grow-1" style="min-width:0;">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <a href="<?php echo e(route('admin.clients.show', $client->id)); ?>"
                   class="text-dark text-decoration-none font-weight-bold text-sm text-truncate"
                   style="max-width:140px;" title="<?php echo e($client->name); ?>">
                  <?php echo e($client->name); ?>

                </a>
                <span class="text-xs text-muted ms-2 flex-shrink-0">
                  <?php echo e($client->tickets_count); ?> ticket<?php echo e($client->tickets_count > 1 ? 's' : ''); ?>

                </span>
              </div>
              <div class="progress" style="height:5px;border-radius:3px;">
                <div class="progress-bar" role="progressbar"
                     style="width:<?php echo e($pct); ?>%;background:<?php echo e($color); ?>;">
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="text-muted text-sm text-center py-3">Aucun client pour l'instant.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>


<style>
.dash-tk-table{width:100%;border-collapse:collapse;}
.dash-tk-table thead tr{background:var(--bs-tertiary-bg,#f8fafc);}
.dash-tk-table thead th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;padding:10px 14px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);white-space:nowrap;}
.dash-tk-table tbody tr{border-bottom:1px solid var(--bs-border-color,#f1f5f9);transition:background .12s;cursor:pointer;}
.dash-tk-table tbody tr:hover{background:color-mix(in srgb,var(--color-primary) 4%,transparent);}
.dash-tk-table tbody tr:last-child{border-bottom:none;}
.dash-tk-table td{padding:12px 14px;vertical-align:middle;}
.dash-ctype{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;}
.dash-ctype-client{background:#F5F3FF;color:#7C3AED;}
.dash-ctype-new{background:#FFF7ED;color:#C2410C;}
.dash-prio{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap;}
.dash-prio-1{background:#f1f5f9;color:#475569;}
.dash-prio-2{background:#e0f2fe;color:#0369a1;}
.dash-prio-3{background:#fef3c7;color:#b45309;}
.dash-prio-4{background:#fee2e2;color:#b91c1c;}
.dash-prio-5{background:#1e1b4b;color:#fff;}
.dash-stbadge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;}
.dash-st-pending{background:#fef3c7;color:#b45309;}
.dash-st-inprogress{background:#dbeafe;color:#1d4ed8;}
.dash-st-resolved{background:#d1fae5;color:#065f46;}
.dash-st-closed{background:#f1f5f9;color:#475569;}
.dash-tk-replied{background:#d1fae5;color:#065f46;font-size:9px;font-weight:700;padding:2px 6px;border-radius:99px;display:inline-block;margin-top:3px;}
.dash-btn-reply{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:9px;font-size:12px;font-weight:600;border:none;cursor:pointer;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:#fff;text-decoration:none;transition:opacity .15s;white-space:nowrap;}
.dash-btn-reply:hover{opacity:.85;color:#fff;}
</style>

<div class="row">
  <div class="col-12">
    <div class="card" style="border-radius:14px;border:1px solid var(--bs-border-color,#e2e8f0);overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.04);">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);">
        <div>
          <h6 style="margin:0;font-weight:700;font-size:14px;">Derniers tickets</h6>
          <p class="text-sm text-muted mb-0">Tickets récents de tous les clients</p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span style="background:var(--bs-tertiary-bg,#f1f5f9);color:#64748b;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;">
            <?php echo e($recentTickets->count()); ?> ticket(s)
          </span>
          <a href="<?php echo e(route('admin.tickets')); ?>" class="dash-btn-reply">
            <i class="material-symbols-rounded" style="font-size:15px;">open_in_new</i>Voir tous
          </a>
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table class="dash-tk-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Ticket</th>
              <th>Client</th>
              <th>Type</th>
              <th>Catégorie</th>
              <th style="text-align:center;">Priorité</th>
              <th style="text-align:center;">Statut</th>
              <th style="text-align:center;">Date</th>
              <th style="text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $catLabels = [
                'incident_technique' => ['🔴','Incident'],
                'integration_api'    => ['🔵','API SMS'],
                'facturation'        => ['🟡','Facturation'],
                'plateforme'         => ['🟢','Plateforme'],
                'paiement_mobile'    => ['🟠','Paiement'],
                'autre'              => ['⚪','Autre'],
              ];
              $cat = $catLabels[$ticket->category] ?? ['⚪', $ticket->category ?? 'Autre'];
              $p = $ticket->priority ?? 3;
              $pLabels = [1=>'Très basse',2=>'Basse',3=>'Moyenne',4=>'Haute',5=>'Critique'];
              $ct = $ticket->user?->client_type;
              $ctBadge = $ct === 'client'
                ? '<span class="dash-ctype dash-ctype-client">🟣 Client</span>'
                : '<span class="dash-ctype dash-ctype-new">🟠 Non classifié</span>';
              $stMap = [
                'pending'     => ['dash-st-pending','En attente','schedule'],
                'in_progress' => ['dash-st-inprogress','En cours','autorenew'],
                'resolved'    => ['dash-st-resolved','Résolu','check_circle'],
                'closed'      => ['dash-st-closed','Clôturé','lock'],
                'synced'      => ['dash-st-pending','Sync','sync'],
                'failed'      => ['dash-st-pending','Erreur','error'],
              ];
              $st = $stMap[$ticket->sync_status] ?? ['dash-st-pending','Inconnu','help'];
            ?>
            <tr onclick="window.location='<?php echo e(route('admin.tickets.show', $ticket->id)); ?>'">
              <td><span style="font-size:11px;font-weight:700;color:var(--color-primary);">#<?php echo e($ticket->id); ?></span></td>
              <td style="max-width:220px;">
                <p style="font-size:12px;font-weight:600;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e(Str::limit($ticket->title, 36)); ?></p>
                <p style="font-size:11px;color:#94a3b8;margin:0;"><?php echo e(Str::limit($ticket->description, 44)); ?></p>
                <?php if($ticket->solution): ?><span class="dash-tk-replied">✅ Répondu</span><?php endif; ?>
              </td>
              <td>
                <div style="display:flex;align-items:center;gap:7px;">
                  <?php if($ticket->user?->avatar): ?>
                    <img src="<?php echo e(asset('storage/' . $ticket->user->avatar)); ?>" style="width:26px;height:26px;border-radius:50%;object-fit:cover;border:1.5px solid #e2e8f0;flex-shrink:0;" alt="">
                  <?php else: ?>
                    <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                      <span style="font-size:9px;font-weight:700;color:#fff;"><?php echo e(strtoupper(substr($ticket->user->name ?? 'U', 0, 2))); ?></span>
                    </div>
                  <?php endif; ?>
                  <span style="font-size:12px;color:#64748b;"><?php echo e($ticket->user->name ?? 'N/A'); ?></span>
                </div>
              </td>
              <td><?php echo $ctBadge; ?></td>
              <td><span style="font-size:12px;"><?php echo e($cat[0]); ?> <?php echo e($cat[1]); ?></span></td>
              <td style="text-align:center;"><span class="dash-prio dash-prio-<?php echo e($p); ?>"><?php echo e($pLabels[$p] ?? 'Moyenne'); ?></span></td>
              <td style="text-align:center;">
                <span class="dash-stbadge <?php echo e($st[0]); ?>">
                  <i class="material-symbols-rounded" style="font-size:11px;vertical-align:middle;"><?php echo e($st[2]); ?></i>
                  <?php echo e($st[1]); ?>

                </span>
              </td>
              <td style="text-align:center;"><span style="font-size:11px;color:#94a3b8;"><?php echo e($ticket->created_at->format('d/m/Y')); ?></span></td>
              <td style="text-align:center;" onclick="event.stopPropagation()">
                <a href="<?php echo e(route('admin.tickets.show', $ticket->id)); ?>" class="dash-btn-reply">
                  <i class="material-symbols-rounded" style="font-size:15px;">reply</i>Répondre
                </a>
              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="9" style="text-align:center;padding:40px;">
                <i class="material-symbols-rounded text-secondary" style="font-size:48px;">confirmation_number</i>
                <p class="text-secondary mt-2">Aucun ticket pour le moment</p>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('page-scripts'); ?>
<script>
  var ctx = document.getElementById("statusChart");
  if (ctx) {
    var statusUrls = [
      '<?php echo e(route("admin.tickets")); ?>?status=pending',
      '<?php echo e(route("admin.tickets")); ?>?status=in_progress',
      '<?php echo e(route("admin.tickets")); ?>?status=resolved',
    ];
    new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: ["En attente", "En cours", "Résolus"],
        datasets: [{
          data: [<?php echo e($openTickets); ?>, <?php echo e($inProgressTickets); ?>, <?php echo e($closedTickets); ?>],
          backgroundColor: ["#ef4444", "#f59e0b", "#22c55e"],
          borderWidth: 3, borderColor: "#fff", hoverOffset: 8
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false, cutout: "75%",
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return ' ' + ctx.label + ': ' + ctx.raw + ' tickets'; } } } },
        onClick: function(e, elements) { if (elements.length > 0) window.location.href = statusUrls[elements[0].index]; },
        onHover: function(e, elements) { e.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default'; }
      }
    });
  }
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>