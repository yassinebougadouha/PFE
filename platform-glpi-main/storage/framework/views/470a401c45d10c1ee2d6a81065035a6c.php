<?php $__env->startSection('title', 'Client — ' . $client->name); ?>
<?php $__env->startSection('page-title', 'Fiche client'); ?>

<?php $__env->startSection('content'); ?>

<?php
  $initials   = strtoupper(substr($client->name, 0, 2));
  $typeInfo   = $client->getClientTypeInfo();
  $statusData = [
    'pending'     => ['warning',   'En attente',  'hourglass_empty'],
    'in_progress' => ['info',      'En cours',    'autorenew'],
    'resolved'    => ['success',   'Résolu',      'check_circle'],
    'closed'      => ['secondary', 'Clôturé',     'lock'],
    'local'       => ['warning',   'En attente',  'hourglass_empty'],
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
  <a href="<?php echo e(route('super-admin.clients')); ?>" class="btn btn-sm btn-outline-secondary mb-0">
    <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">arrow_back</i> Clients
  </a>
  <span class="text-secondary">/</span>
  <span class="text-sm font-weight-bold"><?php echo e($client->name); ?></span>
</div>

<div class="row g-4">
  <div class="col-lg-4">

    
    <div class="card mb-4" style="border-radius:16px;overflow:hidden;">
      <div style="height:80px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));"></div>
      <div class="card-body text-center" style="margin-top:-45px;">
        <?php if($client->avatar): ?>
          <img src="<?php echo e(asset('storage/'.$client->avatar)); ?>" style="width:80px;height:80px;border-radius:50%;border:3px solid #fff;object-fit:cover;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
        <?php else: ?>
          <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));border:3px solid #fff;display:flex;align-items:center;justify-content:center;margin:0 auto;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
            <span style="font-size:28px;font-weight:700;color:#fff;"><?php echo e($initials); ?></span>
          </div>
        <?php endif; ?>
        <h6 class="font-weight-bold mt-3 mb-1"><?php echo e($client->name); ?></h6>
        <p class="text-secondary text-sm mb-2"><?php echo e($client->email); ?></p>

        
        <span class="badge mb-3 px-3 py-2"
              style="background:<?php echo e($typeInfo['css'] === 'ctype-client' ? 'rgba(139,92,246,0.1)' : 'rgba(249,115,22,0.1)'); ?>;
                     color:<?php echo e($typeInfo['css'] === 'ctype-client' ? '#7c3aed' : '#ea580c'); ?>;
                     border:1px solid <?php echo e($typeInfo['css'] === 'ctype-client' ? '#ddd6fe' : '#fed7aa'); ?>;
                     border-radius:20px;font-size:12px;">
          <?php echo e($typeInfo['icon']); ?> <?php echo e($typeInfo['label']); ?> — <?php echo e($typeInfo['desc']); ?>

        </span>

        <div class="mb-3">
          <form method="POST" action="<?php echo e(route('super-admin.clients.toggle', $client->id)); ?>" style="display:inline;">
            <?php echo csrf_field(); ?>
            <button type="submit" class="badge border-0 px-3 py-2"
                    style="cursor:pointer;font-size:12px;
                           background:<?php echo e($client->is_active ? 'linear-gradient(135deg,#22c55e,#16a34a)' : 'linear-gradient(135deg,#94a3b8,#64748b)'); ?>;
                           color:#fff;border-radius:20px;">
              <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">
                <?php echo e($client->is_active ? 'check_circle' : 'cancel'); ?>

              </i>
              <?php echo e($client->is_active ? 'Actif' : 'Inactif'); ?>

            </button>
          </form>
        </div>

        <div class="d-flex justify-content-center gap-2 flex-wrap">
          <form method="POST" action="<?php echo e(route('super-admin.clients.delete', $client->id)); ?>"
                onsubmit="return confirm('Supprimer ce client et tous ses tickets définitivement?')">
            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
            <button type="submit" class="btn btn-sm mb-0 btn-outline-danger">
              <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">delete</i>Supprimer
            </button>
          </form>
        </div>
      </div>
    </div>


    
    <?php if($client->first_name || $client->birthday || $client->gender): ?>
    <div class="card mb-4" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">person</i>Informations personnelles
        </h6>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="d-flex flex-column gap-3">
          <?php if($client->first_name || $client->last_name): ?>
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#f0f4ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:var(--color-primary);">badge</i>
            </div>
            <div>
              <p class="text-xs text-secondary mb-0">Prénom / Nom</p>
              <p class="text-sm font-weight-bold mb-0"><?php echo e($client->first_name); ?> <?php echo e($client->last_name); ?></p>
            </div>
          </div>
          <?php endif; ?>
          <?php if($client->birthday): ?>
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#fef9e7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:#d4a017;">cake</i>
            </div>
            <div>
              <p class="text-xs text-secondary mb-0">Date de naissance</p>
              <p class="text-sm font-weight-bold mb-0">
                <?php echo e(\Carbon\Carbon::parse($client->birthday)->format('d/m/Y')); ?>

                <span class="text-secondary" style="font-size:11px;font-weight:400;">
                  (<?php echo e(\Carbon\Carbon::parse($client->birthday)->age); ?> ans)
                </span>
              </p>
            </div>
          </div>
          <?php endif; ?>
          <?php if($client->gender): ?>
          <?php
            $genderLabels = ['male' => '♂ Homme', 'female' => '♀ Femme', 'other' => '⚧ Autre'];
            $genderColors = ['male' => '#3b82f6', 'female' => '#ec4899', 'other' => '#8b5cf6'];
            $genderBg     = ['male' => '#eff6ff', 'female' => '#fdf2f8', 'other' => '#f5f3ff'];
          ?>
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:<?php echo e($genderBg[$client->gender] ?? '#f8f9fa'); ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:<?php echo e($genderColors[$client->gender] ?? '#64748b'); ?>;">wc</i>
            </div>
            <div>
              <p class="text-xs text-secondary mb-0">Genre</p>
              <p class="text-sm font-weight-bold mb-0" style="color:<?php echo e($genderColors[$client->gender] ?? '#374151'); ?>;">
                <?php echo e($genderLabels[$client->gender] ?? ucfirst($client->gender)); ?>

              </p>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    
    <div class="card mb-4" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">contact_page</i>Coordonnées
        </h6>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="d-flex flex-column gap-3">
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#f0f4ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:var(--color-primary);">email</i>
            </div>
            <div><p class="text-xs text-secondary mb-0">Email</p><p class="text-sm font-weight-bold mb-0"><?php echo e($client->email); ?></p></div>
          </div>
          <?php if($client->phone): ?>
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:#16a34a;">phone</i>
            </div>
            <div><p class="text-xs text-secondary mb-0">Téléphone</p><p class="text-sm font-weight-bold mb-0"><?php echo e($client->phone); ?></p></div>
          </div>
          <?php endif; ?>
          <?php if($client->phone_mobile): ?>
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#fefce8;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:#ca8a04;">smartphone</i>
            </div>
            <div>
              <p class="text-xs text-secondary mb-0">Mobile</p>
              <p class="text-sm font-weight-bold mb-0">
                <?php echo e($client->phone_mobile); ?>

                <?php if($client->phone_verified ?? false): ?><span class="badge bg-gradient-success ms-1" style="font-size:10px;">✓ Vérifié</span><?php endif; ?>
              </p>
            </div>
          </div>
          <?php endif; ?>
          <?php if($client->whatsapp): ?>
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:#16a34a;">chat_bubble</i>
            </div>
            <div><p class="text-xs text-secondary mb-0">WhatsApp</p><p class="text-sm font-weight-bold mb-0"><?php echo e($client->whatsapp); ?></p></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    
    <div class="card mb-4" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">sell</i>Classifier ce client
        </h6>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="row g-2 mb-3">
          <div class="col-12">
            <div id="opt-client" onclick="selectType('client')"
                 style="border:2px solid <?php echo e($client->client_type === 'client' ? '#7c3aed' : '#e2e8f0'); ?>;border-radius:12px;padding:12px;cursor:pointer;transition:all 0.2s;text-align:center;background:<?php echo e($client->client_type === 'client' ? '#f5f3ff' : '#fff'); ?>;">
              <div style="font-size:24px;margin-bottom:4px;">🟣</div>
              <div style="font-weight:700;color:#6d28d9;font-size:13px;">Client</div>
              <div style="color:#64748b;font-size:11px;">Déjà en base</div>
            </div>
          </div>

        </div>
        <div id="type-feedback" class="alert alert-success py-2 px-3 mb-0 text-sm" style="display:none;border-radius:8px;"></div>
      </div>
    </div>

    
    <div class="card" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">bar_chart</i>Statistiques
        </h6>
      </div>
      <div class="card-body px-4 pb-4">
        <?php
          $total    = $client->tickets_count;
          $pending  = $client->tickets()->where('sync_status','pending')->count();
          $inprog   = $client->tickets()->where('sync_status','in_progress')->count();
          $resolved = $client->tickets()->whereIn('sync_status',['resolved','closed'])->count();
        ?>
        <div class="row g-2">
          <div class="col-6"><div class="text-center p-3" style="background:#f8f9fa;border-radius:12px;"><p class="font-weight-bold mb-0" style="font-size:22px;color:var(--color-primary);"><?php echo e($total); ?></p><p class="text-xs text-secondary mb-0">Total tickets</p></div></div>
          <div class="col-6"><div class="text-center p-3" style="background:#fef3c7;border-radius:12px;"><p class="font-weight-bold mb-0" style="font-size:22px;color:#d97706;"><?php echo e($pending); ?></p><p class="text-xs text-secondary mb-0">En attente</p></div></div>
          <div class="col-6"><div class="text-center p-3" style="background:#dbeafe;border-radius:12px;"><p class="font-weight-bold mb-0" style="font-size:22px;color:#2563eb;"><?php echo e($inprog); ?></p><p class="text-xs text-secondary mb-0">En cours</p></div></div>
          <div class="col-6"><div class="text-center p-3" style="background:#dcfce7;border-radius:12px;"><p class="font-weight-bold mb-0" style="font-size:22px;color:#16a34a;"><?php echo e($resolved); ?></p><p class="text-xs text-secondary mb-0">Résolus</p></div></div>
        </div>
        <hr class="horizontal dark my-3">
        <p class="text-xs text-secondary mb-1">Inscrit le</p>
        <p class="text-sm font-weight-bold mb-0"><?php echo e($client->created_at->format('d/m/Y à H:i')); ?></p>
        <?php if($client->last_login_at): ?>
        <p class="text-xs text-secondary mb-1 mt-2">Dernière connexion</p>
        <p class="text-sm font-weight-bold mb-0"><?php echo e(\Carbon\Carbon::parse($client->last_login_at)->format('d/m/Y à H:i')); ?></p>
        <?php endif; ?>
      </div>
    </div>

  </div>

  
  <div class="col-lg-8">
    <div class="card" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">confirmation_number</i>
          Tickets de <?php echo e($client->name); ?>

          <span class="badge ms-2" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:#fff;font-size:11px;"><?php echo e($client->tickets_count); ?></span>
        </h6>
      </div>
      <div class="card-body px-0 pb-0">
        <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <?php
            $st = $statusData[$ticket->sync_status] ?? ['secondary','Inconnu','help'];
            $cat = $catLabels[$ticket->category] ?? '📋 Autre';
            $p = $ticket->priority ?? 3;
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
                  <span class="text-xs text-secondary"><?php echo e($cat); ?></span>
                  <span class="text-xs text-secondary">·</span>
                  <span class="badge badge-sm bg-gradient-<?php echo e($prioColors[$p] ?? 'secondary'); ?>" style="font-size:10px;"><?php echo e($prioLabels[$p] ?? 'Moyenne'); ?></span>
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
            <p class="text-secondary mt-2 mb-0">Aucun ticket pour ce client</p>
          </div>
        <?php endif; ?>
        <?php if($tickets->hasPages()): ?><div class="px-4 py-3"><?php echo e($tickets->links()); ?></div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php $__env->startPush('page-scripts'); ?>
<script>
var _cid      = <?php echo e($client->id); ?>;
var _origType = <?php echo json_encode($client->client_type, 15, 512) ?>;
var _selType  = _origType;
var _csrf     = "<?php echo e(csrf_token()); ?>";

function selectType(t) {
  if (!confirm('Voulez-vous vraiment changer le type de ce client en "' + (t === 'client' ? 'Client' : 'Nouveau') + '" ?')) return;
  _selType = t;
  var optClient = document.getElementById('opt-client');
  if (optClient) { optClient.style.border = '2px solid #7c3aed'; optClient.style.background = '#f5f3ff'; }
  saveClientType(t);
}

function saveClientType(t) {
  var type = t || _selType;
  if (!type || !_cid) return;
  var fb = document.getElementById('type-feedback');
  if (fb) { fb.textContent = 'Enregistrement...'; fb.style.display = 'block'; fb.className = 'alert alert-info py-2 px-3 mb-0 text-sm'; }
  fetch('/super-admin/clients/' + _cid + '/type', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrf, 'Accept': 'application/json' },
    body: JSON.stringify({ client_type: type })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.success || data.ok) {
      _origType = type;
      if (fb) { fb.textContent = type === 'client' ? '✅ Client enregistré' : '✅ Nouveau enregistré'; fb.className = 'alert alert-success py-2 px-3 mb-0 text-sm'; }
      setTimeout(function() { location.reload(); }, 900);
    } else {
      if (fb) { fb.textContent = '❌ Erreur — réessayez'; fb.className = 'alert alert-danger py-2 px-3 mb-0 text-sm'; }
    }
  })
  .catch(function() {
    if (fb) { fb.textContent = '❌ Erreur réseau'; fb.className = 'alert alert-danger py-2 px-3 mb-0 text-sm'; }
  });
}
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/super-admin/client-detail.blade.php ENDPATH**/ ?>