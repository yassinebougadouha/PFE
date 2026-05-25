<?php $__env->startSection('title','Gérer les admins'); ?>
<?php $__env->startSection('page-title','Gérer les admins'); ?>

<?php $__env->startSection('content'); ?>


<div class="row mb-4">
  <div class="col-12">
    <div class="card p-4" style="background:linear-gradient(135deg,var(--color-primary) 0%,var(--color-secondary) 100%);border:none;box-shadow:0 10px 30px -5px rgba(0,0,0,0.15);">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center">
          <div class="bg-white border-radius-lg p-3 me-4 shadow-sm d-flex align-items-center justify-content-center" style="width:72px;height:72px;border-radius:20px !important;">
            <i class="material-symbols-rounded" style="font-size:42px;color:var(--color-primary);">shield_person</i>
          </div>
          <div>
            <h4 class="text-white font-weight-bolder mb-1">Gestion des Admins</h4>
            <p class="text-white text-sm mb-0 opacity-9" style="font-weight:500;">Créer, activer ou supprimer des administrateurs de la plateforme</p>
          </div>
        </div>
        <a href="<?php echo e(route('super-admin.admins.create')); ?>"
           class="btn bg-white mb-0 shadow-sm" style="color:var(--color-primary); font-weight:700; border-radius:14px !important; padding: 12px 24px;">
          <i class="material-symbols-rounded me-2" style="font-size:20px;vertical-align:middle;">add</i>
          Nouvel Admin
        </a>
      </div>
    </div>
  </div>
</div>

<?php if(session('success')): ?>
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
  <i class="material-symbols-rounded me-2">check_circle</i><?php echo e(session('success')); ?>

  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>


<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header pb-0 pt-4 px-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h6 class="mb-0 font-weight-bold" style="font-size:18px;letter-spacing:-0.01em;">Liste des administrateurs</h6>
            <p class="text-sm text-muted mb-0"><?php echo e($admins->count()); ?> admin(s) configuré(s)</p>
          </div>
          <div style="display:flex;align-items:center;gap:12px;background:#f8fafc;border:2px solid #f1f5f9;border-radius:16px;padding:10px 16px;min-width:300px;transition:all 0.2s ease;" onfocusin="this.style.borderColor='var(--color-primary)';this.style.background='#fff';this.style.boxShadow='0 0 0 4px color-mix(in srgb,var(--color-primary) 12%,transparent)'" onfocusout="this.style.borderColor='#f1f5f9';this.style.background='#f8fafc';this.style.boxShadow='none'">
            <i class="material-symbols-rounded" style="font-size:20px;color:#94a3b8;flex-shrink:0;">search</i>
            <input type="text" id="searchAdmin" placeholder="Rechercher par nom ou email…"
                   autocomplete="off"
                   style="border:none;outline:none;background:transparent;font-size:14px;color:#1e293b;width:100%;font-weight:500;">
            <span style="flex-shrink:0;font-size:10px;font-weight:700;color:#94a3b8;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:2px 8px;font-family:monospace;box-shadow:0 1px 2px rgba(0,0,0,0.05);">/</span>
          </div>
        </div>
      </div>
      <div class="card-body px-0 pb-2">
        <div class="table-responsive">
          <table class="table align-items-center mb-0">
            <thead>
              <tr style="background:#f8f9fa;">
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Admin</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Email</th>
                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Statut <span style="font-size:9px;opacity:0.6;text-transform:none;">(cliquable)</span></th>
                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Créé le</th>
                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php $__empty_1 = true; $__currentLoopData = $admins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr id="user-<?php echo e($admin->id); ?>" class="border-bottom admin-row" style="cursor:pointer; transition: all 0.2s ease;"
                  onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'"
                  onclick="window.location='<?php echo e(route('super-admin.admins.show', $admin->id)); ?>'">
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <?php if($admin->avatar): ?>
                      <img src="<?php echo e(asset('storage/' . $admin->avatar)); ?>"
                           style="width:44px;height:44px;border-radius:14px;object-fit:cover;margin-right:16px;border:2px solid #fff;box-shadow: 0 4px 10px rgba(0,0,0,0.08);flex-shrink:0;"
                           alt="">
                    <?php else: ?>
                      <div class="avatar shadow-sm me-3 d-flex align-items-center justify-content-center"
                           style="width:44px;height:44px;border-radius:14px;background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
                        <span class="text-white text-sm font-weight-bold">
                          <?php echo e(strtoupper(substr($admin->name, 0, 2))); ?>

                        </span>
                      </div>
                    <?php endif; ?>
                    <div>
                      <p class="text-sm font-weight-bold mb-0 admin-name" style="color:#1e293b;"><?php echo e($admin->name); ?></p>
                      <p class="text-xs text-secondary mb-0" style="font-weight:500;">Administrateur</p>
                    </div>
                  </div>
                </td>
                <td><p class="text-sm mb-0 admin-email" style="font-weight:600;color:#64748b;"><?php echo e($admin->email); ?></p></td>
                <td class="text-center">
                  
                  <form action="<?php echo e(route('super-admin.admins.toggle', $admin->id)); ?>" method="POST" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit"
                            class="badge border-0 px-4 py-2"
                            style="cursor:pointer;font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;
                                   background:<?php echo e($admin->is_active ? 'linear-gradient(135deg,#10b981,#059669)' : 'linear-gradient(135deg,#94a3b8,#64748b)'); ?>;
                                   color:#fff;border-radius:12px; box-shadow: 0 4px 10px <?php echo e($admin->is_active ? 'rgba(16,185,129,0.2)' : 'rgba(148,163,184,0.2)'); ?>;"
                            title="<?php echo e($admin->is_active ? 'Cliquer pour désactiver' : 'Cliquer pour activer'); ?>">
                      <?php echo e($admin->is_active ? 'Actif' : 'Inactif'); ?>

                    </button>
                  </form>
                </td>
                <td class="text-center">
                  <p class="text-xs mb-0" style="font-weight:700;color:#64748b;"><?php echo e($admin->created_at->format('d/m/Y')); ?></p>
                </td>
                <td class="text-center" onclick="event.stopPropagation()">
                  
                  <a href="<?php echo e(route('super-admin.chat-access')); ?>/<?php echo e($admin->id); ?>"
                     class="btn btn-sm mb-0 shadow-sm"
                     style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:white;border:none;width:38px;height:38px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;transition:all 0.2s ease;"
                     onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"
                     title="Voir la conversation de <?php echo e($admin->name); ?>">
                    <i class="material-symbols-rounded" style="font-size:18px;">chat</i>
                  </a>
                </td>
              </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr>
                <td colspan="5" class="text-center py-5">
                  <i class="material-symbols-rounded text-secondary" style="font-size:48px;">person_off</i>
                  <p class="text-secondary mt-2">Aucun admin trouvé</p>
                  <a href="<?php echo e(route('super-admin.admins.create')); ?>" class="btn btn-sm"
                     style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;">
                    Créer le premier admin
                  </a>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
    <div class="modal-content border-0 shadow-lg overflow-hidden">

      
      <div class="modal-header border-0 p-0">
        <div class="w-100 p-4 d-flex align-items-center"
             style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
          <div id="modal-avatar-wrap"
               style="width:60px;height:60px;border-radius:50%;overflow:hidden;margin-right:12px;flex-shrink:0;border:2px solid rgba(255,255,255,0.4);">
            <img id="modal-avatar-img" src="" alt=""
                 style="width:100%;height:100%;object-fit:cover;display:none;">
            <div class="avatar shadow d-flex align-items-center justify-content-center"
                 style="width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,0.25);font-size:22px;font-weight:700;color:white;"
                 id="modal-initials">FA</div>
          </div>
          <div>
            <h5 class="text-white font-weight-bolder mb-0" id="modal-name">-</h5>
            <p class="text-white opacity-8 text-sm mb-1" id="modal-email">-</p>
            <span class="badge text-white" style="background:rgba(255,255,255,0.2);font-size:11px;" id="modal-role">Admin</span>
          </div>
          <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
        </div>
      </div>

      
      <div class="modal-body p-4">
        <div class="row g-3">

          <div class="col-6">
            <div class="p-3 border-radius-md" style="background:#f8f9fa;">
              <p class="text-xs text-secondary mb-1 text-uppercase font-weight-bold">Statut</p>
              <span id="modal-status" class="badge bg-gradient-success">Actif</span>
            </div>
          </div>

          <div class="col-6">
            <div class="p-3 border-radius-md" style="background:#f8f9fa;">
              <p class="text-xs text-secondary mb-1 text-uppercase font-weight-bold">Membre depuis</p>
              <p class="text-sm font-weight-bold mb-0" id="modal-date">-</p>
            </div>
          </div>

          <div class="col-6">
            <div class="p-3 border-radius-md" style="background:#f8f9fa;">
              <p class="text-xs text-secondary mb-1 text-uppercase font-weight-bold">
                <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">phone</i> Téléphone
              </p>
              <p class="text-sm font-weight-bold mb-0" id="modal-phone">-</p>
            </div>
          </div>

          <div class="col-6">
            <div class="p-3 border-radius-md" style="background:#f8f9fa;">
              <p class="text-xs text-secondary mb-1 text-uppercase font-weight-bold">
                <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">smartphone</i> Mobile
              </p>
              <p class="text-sm font-weight-bold mb-0" id="modal-mobile">-</p>
            </div>
          </div>

          <div class="col-12">
            <div class="p-3 border-radius-md" style="background:#f8f9fa;">
              <p class="text-xs text-secondary mb-1 text-uppercase font-weight-bold">
                <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">confirmation_number</i> Tickets
              </p>
              <p class="text-sm font-weight-bold mb-0" id="modal-tickets">0 ticket(s)</p>
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary btn-sm mb-0" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

<script>
// Search — name + email
document.getElementById('searchAdmin').addEventListener('input', function () {
  var search = this.value.toLowerCase();
  document.querySelectorAll('.admin-row').forEach(function (row) {
    var name  = row.querySelector('.admin-name')?.textContent.toLowerCase() ?? '';
    var email = row.querySelector('.admin-email')?.textContent.toLowerCase() ?? '';
    row.style.display = (name.includes(search) || email.includes(search)) ? '' : 'none';
  });
});
// Keyboard shortcut: /
document.addEventListener('keydown', function(e) {
  if (e.key === '/' && !['INPUT','TEXTAREA'].includes(document.activeElement.tagName)) {
    e.preventDefault();
    const searchEl = document.getElementById('searchAdmin');
    searchEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    searchEl.focus();
  }
});

// Modal
function showUserModal(id, name, email, date, status, role, phone, mobile, tickets, avatarUrl) {
  var initials = name.substring(0, 2).toUpperCase();
  document.getElementById('modal-initials').textContent = initials;

  // ✅ Show photo or fallback to initials
  var img  = document.getElementById('modal-avatar-img');
  var init = document.getElementById('modal-initials');
  if (avatarUrl && avatarUrl.length > 0) {
    img.src = avatarUrl;
    img.style.display = 'block';
    init.style.display = 'none';
  } else {
    img.style.display = 'none';
    init.style.display = 'flex';
  }
  document.getElementById('modal-name').textContent     = name;
  document.getElementById('modal-email').textContent    = email;
  document.getElementById('modal-role').textContent     = role;
  document.getElementById('modal-date').textContent     = date;
  document.getElementById('modal-phone').textContent    = phone || '-';
  document.getElementById('modal-mobile').textContent   = mobile || '-';
  document.getElementById('modal-tickets').textContent  = tickets + ' ticket(s)';

  var statusEl = document.getElementById('modal-status');
  statusEl.textContent  = status;
  statusEl.className    = 'badge ' + (status === 'Actif' ? 'bg-gradient-success' : 'bg-gradient-secondary');

  new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
  var hash = window.location.hash;
  if (hash && hash.startsWith('#user-')) {
    var row = document.getElementById('user-' + hash.replace('#user-', ''));
    if (row) {
      setTimeout(function() {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.style.transition = 'background 0.3s ease, box-shadow 0.3s ease';
        row.style.background = '#f0f4ff';
        row.style.boxShadow  = 'inset 4px 0 0 var(--color-primary)';
        setTimeout(function() { row.style.background=''; row.style.boxShadow=''; }, 3000);
      }, 300);
    }
  }
});
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/super-admin/admins.blade.php ENDPATH**/ ?>