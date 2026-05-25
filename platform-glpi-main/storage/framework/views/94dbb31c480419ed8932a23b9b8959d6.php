<?php $__env->startSection('title','Dashboard'); ?>
<?php $__env->startSection('page-title','Dashboard'); ?>

<?php $__env->startSection('content'); ?>


<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-lg border-radius-lg p-3"
         style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-xl bg-white border-radius-lg p-2 me-3 shadow"
               style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;">
            <span class="font-weight-bolder" style="font-size:22px;color:var(--color-primary);">
              <?php echo e(strtoupper(substr(auth()->user()->name, 0, 2))); ?>

            </span>
          </div>
          <div>
            <h5 class="text-white font-weight-bolder mb-0">
              Bonjour, <?php echo e(auth()->user()->name); ?> 👋
            </h5>
            <p class="text-white text-sm mb-0 opacity-8">
              <?php echo e(now()->format('l, d F Y')); ?>

            </p>
          </div>
        </div>
        <a href="<?php echo e(route('tickets.create')); ?>"
           class="btn bg-white mb-0 d-none d-md-block" style="color:var(--color-primary); font-weight:600;">
          <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">add</i>
          Nouveau Ticket
        </a>
      </div>
    </div>
  </div>
</div>


<div class="row mb-4">
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 h-100">
      <i class="material-symbols-rounded mb-2" style="font-size:32px;color:var(--color-primary);">confirmation_number</i>
      <h4 class="font-weight-bolder mb-0"><?php echo e($totalTickets); ?></h4>
      <p class="text-xs text-secondary mb-0">Total</p>
    </div>
  </div>
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 h-100">
      <i class="material-symbols-rounded mb-2 text-warning" style="font-size:32px;">schedule</i>
      <h4 class="font-weight-bolder mb-0 text-warning"><?php echo e($openTickets); ?></h4>
      <p class="text-xs text-secondary mb-0">En attente</p>
    </div>
  </div>
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 h-100">
      <i class="material-symbols-rounded mb-2 text-info" style="font-size:32px;">autorenew</i>
      <h4 class="font-weight-bolder mb-0 text-info"><?php echo e($inProgressTickets); ?></h4>
      <p class="text-xs text-secondary mb-0">En cours</p>
    </div>
  </div>
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 h-100">
      <i class="material-symbols-rounded mb-2 text-success" style="font-size:32px;">check_circle</i>
      <h4 class="font-weight-bolder mb-0 text-success"><?php echo e($closedTickets); ?></h4>
      <p class="text-xs text-secondary mb-0">Résolus</p>
    </div>
  </div>
</div>


<div class="row">

  
  <div class="col-lg-4 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="mb-0 font-weight-bold">Aperçu</h6>
        <p class="text-xs text-secondary mb-0">Répartition de vos tickets</p>
      </div>
      <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center">

        
        <div style="position: relative; height: 220px; width: 220px; max-width: 100%; margin: 0 auto;">
          <canvas id="clientChart"></canvas>
        </div>

        <div class="d-flex gap-3 mt-3 justify-content-center flex-wrap">
          <div class="text-center">
            <span class="badge bg-gradient-warning"><?php echo e($openTickets); ?> En attente</span>
          </div>
          <div class="text-center">
            <span class="badge" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:white;">
              <?php echo e($inProgressTickets); ?> En cours
            </span>
          </div>
          <div class="text-center">
            <span class="badge bg-gradient-success"><?php echo e($closedTickets); ?> Résolus</span>
          </div>
        </div>

      </div>
    </div>
  </div>

  
  <div class="col-lg-8 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0 font-weight-bold">Derniers tickets</h6>
            <p class="text-xs text-secondary mb-0">Vos demandes récentes</p>
          </div>
          
        </div>
      </div>
      <div class="card-body px-0 pb-2">
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
          $cat = $catLabels[$ticket->category] ?? ['⚪','Autre'];

          $statusData = [
            'pending'     => ['warning','En attente'],
            'in_progress' => ['info','En cours'],
            'resolved'    => ['success','Résolu'],
            'closed'      => ['secondary','Clôturé'],
            'local'       => ['warning','En attente'],
            'synced'      => ['success','Résolu'],
          ];
          $st = $statusData[$ticket->sync_status] ?? ['secondary','Inconnu'];
        ?>
        <a href="<?php echo e(route('tickets.show', $ticket->id)); ?>"
           class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between text-decoration-none"
           style="cursor:pointer; transition:background 0.15s;"
           onmouseover="this.style.background='rgba(0,0,0,0.03)'"
           onmouseout="this.style.background=''">
          <div class="d-flex align-items-center">
            <span class="badge text-white me-3"
                  style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));min-width:32px;">
              #<?php echo e($ticket->id); ?>

            </span>
            <div>
              <p class="text-sm font-weight-bold mb-0" style="color:inherit;"><?php echo e(Str::limit($ticket->title, 40)); ?></p>
              <p class="text-xs text-secondary mb-0">
                <?php echo e($cat[0]); ?> <?php echo e($cat[1]); ?> · <?php echo e($ticket->created_at->format('d/m/Y')); ?>

                <?php if($ticket->solution): ?>
                  · <span class="text-success font-weight-bold">✅ Répondu</span>
                <?php endif; ?>
              </p>
            </div>
          </div>
          <span class="badge bg-gradient-<?php echo e($st[0]); ?> ms-2"><?php echo e($st[1]); ?></span>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="text-center py-5">
          <i class="material-symbols-rounded text-secondary" style="font-size:48px;">confirmation_number</i>
          <p class="text-secondary mt-2 mb-3">Aucun ticket pour le moment</p>
          <a href="<?php echo e(route('tickets.create')); ?>" class="btn btn-sm text-white"
             style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
            Créer mon premier ticket
          </a>
        </div>
        <?php endif; ?>

        <?php if($totalTickets > 5): ?>
        <div class="text-center pt-3">
          <a href="<?php echo e(route('tickets.index')); ?>" class="btn btn-sm btn-outline-secondary mb-0">
            Voir tous mes tickets →
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('page-scripts'); ?>
<script>
(function() {
  var ctx = document.getElementById('clientChart');
  if (!ctx) return;

  var open    = <?php echo e($openTickets); ?>;
  var inProg  = <?php echo e($inProgressTickets); ?>;
  var closed  = <?php echo e($closedTickets); ?>;
  var total   = open + inProg + closed;

  // Si aucun ticket — afficher un cercle gris avec message
  if (total === 0) {
    var primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#667eea';
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Aucun ticket'],
        datasets: [{
          data: [1],
          backgroundColor: ['#e9ecef'],
          borderWidth: 0,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
          legend: { display: false },
          tooltip: { enabled: false }
        }
      },
      plugins: [{
        id: 'centerText',
        afterDraw: function(chart) {
          var width  = chart.width;
          var height = chart.height;
          var ctx2   = chart.ctx;
          ctx2.save();
          ctx2.font = 'bold 13px sans-serif';
          ctx2.fillStyle = '#94a3b8';
          ctx2.textAlign = 'center';
          ctx2.textBaseline = 'middle';
          ctx2.fillText('Aucun ticket', width / 2, height / 2 - 8);
          ctx2.font = '11px sans-serif';
          ctx2.fillText('pour le moment', width / 2, height / 2 + 10);
          ctx2.restore();
        }
      }]
    });
    return;
  }

  // Sinon — chart normal
  var primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#667eea';
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['En attente', 'En cours', 'Résolus'],
      datasets: [{
        data: [open, inProg, closed],
        backgroundColor: ['#ffc107', primaryColor, '#28a745'],
        borderWidth: 3,
        borderColor: '#fff',
        hoverOffset: 5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: { legend: { display: false } }
    },
    plugins: [{
      id: 'centerText',
      afterDraw: function(chart) {
        var width  = chart.width;
        var height = chart.height;
        var ctx2   = chart.ctx;
        ctx2.save();
        ctx2.font = 'bold 28px sans-serif';
        ctx2.fillStyle = '#1e293b';
        ctx2.textAlign = 'center';
        ctx2.textBaseline = 'middle';
        ctx2.fillText(total, width / 2, height / 2 - 8);
        ctx2.font = '11px sans-serif';
        ctx2.fillStyle = '#94a3b8';
        ctx2.fillText('tickets', width / 2, height / 2 + 12);
        ctx2.restore();
      }
    }]
  });

  // Fix chart display when sidebar opens/closes
  window.addEventListener('resize', function() {
    setTimeout(function() {
      var chartInstance = Chart.getChart(ctx);
      if (chartInstance) chartInstance.resize();
    }, 300);
  });
})();
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/client/dashboard.blade.php ENDPATH**/ ?>