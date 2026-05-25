<?php $__env->startSection('title','Tous les tickets'); ?>
<?php $__env->startSection('page-title','Tous les tickets'); ?>
<?php
  $todayFmt      = now()->format('Y-m-d');
  $hasAnyFilter  = request('search') || request('status') || request('date_from') || request('priority') || request('client_type');
  $isTodayActive = (request('date_from') === $todayFmt && request('date_to') === $todayFmt);
?>

<?php $__env->startSection('content'); ?>
<style>
*{box-sizing:border-box;}
.tk-wrap{font-family:inherit;}

/* ── Header ── */
.tk-header{
  background: #fff;
  border-radius: 20px;
  padding: 32px;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 24px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.tk-header-left{display:flex;align-items:center;gap:20px;}
.tk-header-icon{
  width:60px;height:60px;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
  border-radius:16px;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
  box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}
.tk-header h5{color:#1e293b;font-weight:800;margin:0;font-size:26px;letter-spacing:-0.03em;}
.tk-header p{color:#64748b;margin:0;font-size:14px;font-weight:500;}
.tk-stats{display:flex;gap:40px;}
.tk-stat{text-align:left;color:#1e293b;display:flex;flex-direction:column;position:relative;}
.tk-stat:not(:last-child)::after{
  content: '';
  position: absolute;
  right: -20px;
  top: 15%;
  height: 70%;
  width: 1px;
  background: #e2e8f0;
}
.tk-stat-num{font-size:28px;font-weight:800;line-height:1;letter-spacing:-0.03em;color:var(--color-primary);}
.tk-stat-label{font-size:11px;color:#94a3b8;margin-top:6px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;}

/* ── Filters ── */
.tk-filters{
  background: #fff;
  border-radius: 20px;
  border: 1px solid #f1f5f9;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.tk-search-bar{
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f8fafc;
  border: 2px solid #f1f5f9;
  border-radius: 16px;
  padding: 14px 20px;
  transition: all 0.2s ease;
  margin-bottom: 24px;
}
.tk-search-bar:focus-within{
  border-color: var(--color-primary);
  background: #fff;
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 10%, transparent);
}
.tk-search-bar input{
  border: none;
  outline: none;
  background: transparent;
  font-size: 15px;
  font-weight: 500;
  width: 100%;
  color: #1e293b;
}
.tk-search-bar input::placeholder{ color: #94a3b8; }
.tk-search-bar .si{ color: #64748b; font-size: 22px; }

.tk-filter-grid{
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  align-items: end;
}
.tk-filter-group{
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.tk-filter-group label{
  font-size: 11px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0;
}
.tk-filter-group select, .tk-filter-group input[type=date]{
  border: 2px solid #f1f5f9;
  border-radius: 12px;
  padding: 10px 14px;
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  background: #fff;
  transition: all 0.2s ease;
  cursor: pointer;
  width: 100%;
}
.tk-filter-group select:focus, .tk-filter-group input[type=date]:focus{
  border-color: var(--color-primary);
  outline: none;
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 8%, transparent);
}
.tk-filter-actions{
  display: flex;
  gap: 10px;
}
.btn-filter{
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 24px;
  border-radius: 12px;
  border: none;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
  color: #fff;
  flex-grow: 1;
}
.btn-filter:hover{
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  opacity: 0.95;
}
.btn-clear{
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: 2px solid #f1f5f9;
  background: #fff;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-clear:hover{
  border-color: #fca5a5;
  color: #ef4444;
  background: #fef2f2;
}

/* ── Pills ── */
.tk-pills{
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #f1f5f9;
  align-items: center;
}
.tk-pills-label{
  font-size: 12px;
  font-weight: 800;
  color: #94a3b8;
  white-space: nowrap;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-right: 8px;
}
.pill{
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s ease;
  border: 2px solid transparent;
  white-space: nowrap;
}
.pill .pill-count{
  background: rgba(0,0,0,0.08);
  border-radius: 8px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 800;
}
.pill-today{ background: #f0f7ff; color: #0284c7; border-color: #e0f2fe; }
.pill-today.active, .pill-today:hover{ background: #0284c7; color: #fff; border-color: #0284c7; }

.pill-all{ background: #f8fafc; color: #475569; border-color: #e2e8f0; }
.pill-all.active, .pill-all:hover{ background: #475569; color: #fff; border-color: #475569; }

.pill-pending{ background: #fffbeb; color: #d97706; border-color: #fef3c7; }
.pill-pending.active, .pill-pending:hover{ background: #d97706; color: #fff; border-color: #d97706; }

.pill-inprogress{ background: #f0f9ff; color: #0284c7; border-color: #e0f2fe; }
.pill-inprogress.active, .pill-inprogress:hover{ background: #0284c7; color: #fff; border-color: #0284c7; }

.pill-resolved{ background: #f0fdf4; color: #166534; border-color: #dcfce7; }
.pill-resolved.active, .pill-resolved:hover{ background: #166534; color: #fff; border-color: #166534; }

.pill-nonclass{ background: #fff1f2; color: #e11d48; border-color: #ffe4e6; }
.pill-nonclass.active, .pill-nonclass:hover{ background: #e11d48; color: #fff; border-color: #e11d48; }

/* ── Table card ── */
.tk-table-card{
  background: #fff;
  border-radius: 20px;
  border: 1px solid #f1f5f9;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.tk-table-head{
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 28px;
  border-bottom: 1px solid #f1f5f9;
}
.tk-table-head h6{
  margin: 0;
  font-weight: 800;
  font-size: 18px;
  letter-spacing: -0.02em;
  color: #1e293b;
}
table.tk-table{
  width: 100% !important;
  table-layout: auto;
}
table.tk-table thead tr{
  background: #f8fafc;
}
table.tk-table thead th{
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #64748b;
  padding: 16px 24px;
  border-bottom: 2px solid #f1f5f9;
}
table.tk-table tbody tr{
  border-bottom: 1px solid #f1f5f9;
  transition: all 0.2s ease;
}
table.tk-table tbody tr:hover{
  background: #f8fafc;
}
table.tk-table td{
  padding: 18px 24px;
  vertical-align: middle;
}

/* Badges */
.ctype{
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.01em;
}
.ctype-tns{background:#eff6ff;color:#2563eb;border:1px solid #dbeafe;}
.ctype-l2t{background:#f5f3ff;color:#7c3aed;border:1px solid #ede9fe;}
.ctype-new{background:#fff7ed;color:#ea580c;border:1px solid #ffedd5;}

.prio{
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 12px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 800;
  white-space: nowrap;
}
.prio-1{background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;}
.prio-2{background:#f0f9ff;color:#0ea5e9;border:1px solid #e0f2fe;}
.prio-3{background:#fffbeb;color:#d97706;border:1px solid #fef3c7;}
.prio-4{background:#fff1f2;color:#dc2626;border:1px solid #ffe4e6;}
.prio-5{background:#1e1b4b;color:#fff;}

.stbadge{
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.st-pending{background:#fffbeb;color:#d97706;border:1px solid #fef3c7;}
.st-inprogress{background:#eff6ff;color:#2563eb;border:1px solid #dbeafe;}
.st-resolved{background:#f0fdf4;color:#166534;border:1px solid #dcfce7;}
.st-closed{background:#f8fafc;color:#475569;border:1px solid #e2e8f0;}
.st-escalated{background:#fff1f2;color:#be123c;border:1px solid #ffe4e6;}

.tk-replied-badge{background:#d1fae5;color:#065f46;font-size:9px;font-weight:700;
  padding:2px 6px;border-radius:99px;display:inline-block;margin-top:3px;}

.sa-actions{display:flex;align-items:center;gap:6px;}
.sa-select{border:1.5px solid var(--bs-border-color,#e2e8f0);border-radius:8px;
  padding:5px 8px;font-size:11px;background:var(--bs-body-bg,#fff);color:inherit;
  outline:none;cursor:pointer;transition:.15s;}
.sa-select:focus{border-color:#3b5bdb;}
.btn-del{display:flex;align-items:center;justify-content:center;width:30px;height:30px;
  border-radius:8px;border:1.5px solid #fca5a5;background:transparent;
  color:#ef4444;cursor:pointer;transition:.15s;}
.btn-del:hover{background:#fef2f2;}

.tk-empty{text-align:center;padding:60px 20px;}
.tk-empty-icon{font-size:56px;color:#cbd5e1;display:block;margin-bottom:12px;}
.tk-empty p{color:#94a3b8;font-size:14px;margin:0;}
</style>

<div class="tk-wrap">


<div class="tk-header">
  <div class="tk-header-left">
    <div class="tk-header-icon">
      <i class="material-symbols-rounded text-white" style="font-size:32px;">confirmation_number</i>
    </div>
    <div>
      <h5>Tous les Tickets</h5>
      <p>Vue d'ensemble et gestion centralisée des demandes</p>
    </div>
  </div>
  <div class="tk-stats">
    <div class="tk-stat">
      <div class="tk-stat-num"><?php echo e($totalTickets); ?></div>
      <div class="tk-stat-label">Total</div>
    </div>
    <div class="tk-stat">
      <div class="tk-stat-num" style="color:#d97706;"><?php echo e($openTickets); ?></div>
      <div class="tk-stat-label">En attente</div>
    </div>
    <div class="tk-stat">
      <div class="tk-stat-num" style="color:#2563eb;"><?php echo e($inProgressTickets); ?></div>
      <div class="tk-stat-label">En cours</div>
    </div>
    <div class="tk-stat">
      <div class="tk-stat-num" style="color:#166534;"><?php echo e($closedTickets); ?></div>
      <div class="tk-stat-label">Résolus</div>
    </div>
  </div>
</div>


<div class="tk-filters">
  <form method="GET" action="<?php echo e(route('super-admin.tickets')); ?>" id="ticketFilterForm">

    <div class="tk-search-bar">
      <i class="material-symbols-rounded si">search</i>
      <input type="text" name="search" id="searchInput"
             placeholder="Rechercher par titre, client, description, email…"
             value="<?php echo e(request('search')); ?>" autocomplete="off">
      <span class="kbd">/ ou Ctrl+K</span>
    </div>

    <div class="tk-filter-grid">
      <div class="tk-filter-group">
        <label>📅 Date début</label>
        <input type="date" name="date_from" lang="fr" value="<?php echo e(request('date_from')); ?>">
      </div>
      <div class="tk-filter-group">
        <label>📅 Date fin</label>
        <input type="date" name="date_to" lang="fr" value="<?php echo e(request('date_to')); ?>">
      </div>
      <div class="tk-filter-group">
        <label>🏷️ Statut</label>
        <select name="status">
          <option value="">Tous les statuts</option>
          <option value="pending"     <?php echo e(request('status')==='pending'     ?'selected':''); ?>>⏳ En attente</option>
          <option value="in_progress" <?php echo e(request('status')==='in_progress' ?'selected':''); ?>>🔄 En cours</option>
          <option value="resolved"    <?php echo e(request('status')==='resolved'    ?'selected':''); ?>>✅ Résolus</option>
          <option value="closed"      <?php echo e(request('status')==='closed'      ?'selected':''); ?>>🔒 Clôturé</option>
        </select>
      </div>
      <div class="tk-filter-group">
        <label>📁 Catégorie</label>
        <select name="category">
          <option value="">Toutes les catégories</option>
          <option value="incident_technique" <?php echo e(request('category')==='incident_technique'?'selected':''); ?>>🔴 Incident technique</option>
          <option value="integration_api"    <?php echo e(request('category')==='integration_api'?'selected':''); ?>>🔵 Intégration API SMS</option>
          <option value="facturation"        <?php echo e(request('category')==='facturation'?'selected':''); ?>>🟡 Facturation</option>
          <option value="plateforme"         <?php echo e(request('category')==='plateforme'?'selected':''); ?>>🟢 Plateforme L2T</option>
          <option value="paiement_mobile"    <?php echo e(request('category')==='paiement_mobile'?'selected':''); ?>>🟠 Paiement Mobile</option>
          <option value="autre"              <?php echo e(request('category')==='autre'?'selected':''); ?>>⚪ Autre</option>
        </select>
      </div>
      <div class="tk-filter-group">
        <label>🔥 Priorité</label>
        <select name="priority">
          <option value="">Toutes</option>
          <option value="5" <?php echo e(request('priority')==='5' ?'selected':''); ?>>🔴 Critique</option>
          <option value="4" <?php echo e(request('priority')==='4' ?'selected':''); ?>>🟠 Haute</option>
          <option value="3" <?php echo e(request('priority')==='3' ?'selected':''); ?>>🟡 Moyenne</option>
          <option value="2" <?php echo e(request('priority')==='2' ?'selected':''); ?>>🔵 Basse</option>
          <option value="1" <?php echo e(request('priority')==='1' ?'selected':''); ?>>⚪ Très basse</option>
        </select>
      </div>
      <div class="tk-filter-group">
        <label>👤 Type client</label>
        <select name="client_type">
          <option value="">Tous les types</option>
          <option value="client"        <?php echo e(request('client_type')==='client'        ?'selected':''); ?>>🔵 Client</option>
          <option value="non_classifie" <?php echo e(request('client_type')==='non_classifie' ?'selected':''); ?>>🟠 Non classifié</option>
        </select>
      </div>
      <div class="tk-filter-actions">
        <button type="submit" class="btn-filter">
          <i class="material-symbols-rounded" style="font-size:20px;">filter_list</i>
          <span>Appliquer les filtres</span>
        </button>
        <a href="<?php echo e(route('super-admin.tickets')); ?>" class="btn-clear" title="Réinitialiser">
          <i class="material-symbols-rounded" style="font-size:20px;">close</i>
        </a>
      </div>
    </div>

    
    <div class="tk-pills">
      <span class="tk-pills-label">Raccourcis :</span>

      <a href="<?php echo e(route('super-admin.tickets')); ?>?date_from=<?php echo e($todayFmt); ?>&date_to=<?php echo e($todayFmt); ?>"
         class="pill pill-today <?php echo e($isTodayActive ? 'active' : ''); ?>">
        📅 Aujourd'hui
      </a>

      <a href="<?php echo e(route('super-admin.tickets')); ?>?all=1"
         class="pill pill-all <?php echo e(request('all')==='1' ? 'active' : ''); ?>">
        Tout <span class="pill-count"><?php echo e($totalTickets); ?></span>
      </a>

      <a href="<?php echo e(route('super-admin.tickets')); ?>?status=pending&all=1"
         class="pill pill-pending <?php echo e(request('status')==='pending' ? 'active' : ''); ?>">
        ⏳ En attente <span class="pill-count"><?php echo e($openTickets); ?></span>
      </a>

      <a href="<?php echo e(route('super-admin.tickets')); ?>?status=in_progress&all=1"
         class="pill pill-inprogress <?php echo e(request('status')==='in_progress' ? 'active' : ''); ?>">
        🔄 En cours <span class="pill-count"><?php echo e($inProgressTickets); ?></span>
      </a>

      <a href="<?php echo e(route('super-admin.tickets')); ?>?status=resolved&all=1"
         class="pill pill-resolved <?php echo e(request('status')==='resolved' ? 'active' : ''); ?>">
        ✅ Résolus <span class="pill-count"><?php echo e($closedTickets); ?></span>
      </a>

      <a href="<?php echo e(route('super-admin.tickets')); ?>?client_type=non_classifie&all=1"
         class="pill pill-nonclass <?php echo e(request('client_type')==='non_classifie' ? 'active' : ''); ?>">
        🟠 Non classifiés <span class="pill-count"><?php echo e($totalNonClass); ?></span>
      </a>
    </div>

  </form>
</div>


<div class="tk-table-card">
  <div class="tk-table-head">
    <h6>
      Liste des tickets
      <?php if(request('search') || request('status') || request('date_from') || request('client_type') || request('priority')): ?>
        <span style="background:#3b5bdb;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;margin-left:6px;">Filtrés</span>
      <?php endif; ?>
    </h6>
    <span class="tk-count-badge"><?php echo e($tickets->total()); ?> ticket(s)</span>
  </div>

  <div style="overflow-x:auto;">
    <table class="tk-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Titre</th>
          <th>Client</th>
          <th>Type</th>
          <th>Catégorie</th>
          <th>Priorité</th>
          <th style="text-align:center;">Statut</th>
          <th style="text-align:center;">Date</th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
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
          if ($ct === 'client') {
            $ctBadge = '<span class="ctype ctype-tns">🔵 Client</span>';
          } else {
            $ctBadge = '<span class="ctype ctype-new">🟠 Non classifié</span>';
          }

          // FIX: was $ticket->sync_status (non-existent field — always fell through to 'Inconnu').
          //      Corrected to $ticket->status. Also removed dead 'synced'/'failed' keys that are
          //      glpi_sync_status values and would never appear here. Added 'escalated' to match
          //      mapBackendStatusToLocal() in the controller.
          $stMap = [
            'pending'     => ['st-pending',   'En attente', 'schedule'],
            'in_progress' => ['st-inprogress', 'En cours',  'autorenew'],
            'escalated'   => ['st-escalated',  'Escaladé',  'trending_up'],
            'resolved'    => ['st-resolved',   'Résolu',    'check_circle'],
            'closed'      => ['st-closed',     'Clôturé',   'lock'],
          ];
          $st = $stMap[$ticket->status ?? $ticket->sync_status ?? 'pending'] ?? ['st-pending','En attente','schedule'];
        ?>
        <tr id="ticket-<?php echo e($ticket->id); ?>"
            onclick="window.location='<?php echo e(route('super-admin.decision-engine')); ?>?ticket=<?php echo e($ticket->id); ?>'">
          <td>
            <span style="font-size:11px;font-weight:700;color:#3b5bdb;">#<?php echo e($ticket->id); ?></span>
          </td>
          <td style="max-width:250px;">
            <div class="d-flex align-items-center gap-2 mb-1">
              <p style="font-size:14px;font-weight:700;margin:0;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e(Str::limit($ticket->title ?? 'Sans titre', 40)); ?></p>
              <?php if($ticket->ai_analysis): ?>
                <i class="material-symbols-rounded text-primary" style="font-size:16px;" title="Analysé par IA">smart_toy</i>
              <?php endif; ?>
            </div>
            <?php if($ticket->description): ?>
              <p style="font-size:12px;color:#64748b;margin:0;line-height:1.4;"><?php echo e(Str::limit($ticket->description, 55)); ?></p>
            <?php endif; ?>
            <?php if($ticket->solution): ?>
              <span class="tk-replied-badge" style="margin-top:6px;">✅ Répondu</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <?php if($ticket->user?->avatar): ?>
                <img src="<?php echo e(asset('storage/' . $ticket->user->avatar)); ?>"
                     style="width:32px;height:32px;border-radius:10px;object-fit:cover;border:1.5px solid #f1f5f9;flex-shrink:0;" alt="">
              <?php else: ?>
                <div style="width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                  <span style="font-size:11px;font-weight:700;color:#fff;"><?php echo e(strtoupper(substr($ticket->user->name ?? 'U', 0, 2))); ?></span>
                </div>
              <?php endif; ?>
              <div>
                <p style="font-size:13px;font-weight:700;color:#1e293b;margin:0;"><?php echo e($ticket->user->name ?? 'N/A'); ?></p>
                <p style="font-size:11px;color:#94a3b8;margin:0;"><?php echo e(Str::limit($ticket->user->email, 20)); ?></p>
              </div>
            </div>
          </td>
          <td><?php echo $ctBadge; ?></td>
          <td>
            <?php
              $catColors = [
                'incident_technique' => ['#fee2e2','#dc2626'],
                'integration_api'    => ['#dbeafe','#2563eb'],
                'facturation'        => ['#fef9c3','#ca8a04'],
                'plateforme'         => ['#dcfce7','#16a34a'],
                'paiement_mobile'    => ['#ffedd5','#ea580c'],
                'autre'              => ['#f1f5f9','#64748b'],
              ];
              $catKey   = $ticket->category ?? 'autre';
              $catColor = $catColors[$catKey] ?? $catColors['autre'];
            ?>
            <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:10px;font-size:12px;font-weight:700;background:<?php echo e($catColor[0]); ?>;color:<?php echo e($catColor[1]); ?>;">
              <span style="width:7px;height:7px;border-radius:50%;background:<?php echo e($catColor[1]); ?>;flex-shrink:0;"></span>
              <?php echo e($cat[1]); ?>

            </span>
          </td>
          <td>
            <span class="prio prio-<?php echo e($p); ?>"><?php echo e($pLabels[$p] ?? 'Moyenne'); ?></span>
          </td>
          <td style="text-align:center;">
            <span class="stbadge <?php echo e($st[0]); ?>">
              <i class="material-symbols-rounded" style="font-size:16px;"><?php echo e($st[2]); ?></i>
              <?php echo e($st[1]); ?>

            </span>
          </td>
          <td style="text-align:center;">
            <div class="d-flex flex-column align-items-center">
              <span style="font-size:13px;font-weight:600;color:#1e293b;"><?php echo e($ticket->created_at->format('d/m/Y')); ?></span>
              <span style="font-size:11px;color:#94a3b8;"><?php echo e($ticket->created_at->format('H:i')); ?></span>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          
          <td colspan="8">
            <div class="tk-empty">
              <i class="material-symbols-rounded tk-empty-icon">confirmation_number</i>
              <p>Aucun ticket trouvé</p>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div style="padding:14px 20px;">
    <?php echo e($tickets->appends(request()->query())->links()); ?>

  </div>
</div>

</div>

<script>
document.addEventListener('keydown', function(e) {
  if ((e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') ||
      (e.ctrlKey && e.key === 'k')) {
    e.preventDefault();
    document.getElementById('searchInput').focus();
    document.getElementById('searchInput').select();
  }
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/super-admin/tickets.blade.php ENDPATH**/ ?>