<?php $__env->startSection('title', 'Tickets urgents (SLA)'); ?>
<?php $__env->startSection('page-title', 'Tickets urgents'); ?>

<?php $__env->startSection('content'); ?>

<style>
.sla-bar-bg {
    background: #f0f0f0;
    border-radius: 6px;
    height: 8px;
    width: 100%;
}
.sla-bar-fill {
    height: 8px;
    border-radius: 6px;
    transition: width .4s ease;
}
.priority-badge {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 20px;
    font-weight: 700;
    text-transform: uppercase;
}
.urgent-card {
    transition: transform .15s ease, box-shadow .15s ease;
    cursor: pointer;
}
.urgent-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,.12) !important;
}
</style>



  <div class="card mb-4" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));border:none;border-radius:16px;">
    <div class="card-body d-flex align-items-center justify-content-between px-4 py-3">
      <div class="d-flex align-items-center gap-3">
        <i class="material-symbols-rounded text-white" style="font-size:36px;">priority_high</i>
        <div>
          <h5 class="text-white mb-0 fw-bold">Tickets urgents — Risque SLA</h5>
          <p class="text-white mb-0" style="opacity:.8;font-size:12px;">
            <?php echo e($tickets->count()); ?> ticket(s) en dépassement ou à risque SLA
          </p>
        </div>
      </div>
      <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-sm text-white" style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);border-radius:12px;">
        ← Retour Dashboard
      </a>
    </div>
  </div>

</div>

  
  <?php if($tickets->isEmpty()): ?>
    <div class="card text-center py-5">
      <i class="material-symbols-rounded text-success mx-auto d-block" style="font-size:48px;">check_circle</i>
      <h6 class="mt-3 text-secondary">Aucun ticket urgent en ce moment 🎉</h6>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $sla = min($t->sla_used, 100);
          $barColor = $t->sla_breached ? '#e53e3e' : ($t->sla_risk ? '#ed8936' : '#48bb78');
          $priorityLabels = [5=>'Critique',4=>'Haute',3=>'Moyenne',2=>'Basse',1=>'Très basse'];
          $priorityColors = [5=>'#e53e3e',4=>'#ed8936',3=>'#ecc94b',2=>'#4299e1',1=>'#a0aec0'];
          $label = $priorityLabels[$t->priority] ?? '?';
          $color = $priorityColors[$t->priority] ?? '#888';
          $ticketUrl = route('admin.tickets.show', $t->id);
        ?>
        <div class="col-12">
          <div class="card shadow-sm border-0 urgent-card"
               style="border-left: 4px solid <?php echo e($color); ?> !important; border-radius:12px;"
               onclick="window.location='<?php echo e($ticketUrl); ?>'">
            <div class="card-body px-4 py-3">
              <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">

                <div>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="text-muted" style="font-size:12px;font-weight:600;">#<?php echo e($t->id); ?></span>
                    <span class="priority-badge text-white" style="background:<?php echo e($color); ?>;"><?php echo e($label); ?></span>
                    <?php if($t->sla_breached): ?>
                      <span class="priority-badge text-white" style="background:#e53e3e;">SLA dépassé</span>
                    <?php elseif($t->sla_risk): ?>
                      <span class="priority-badge" style="background:#fed7d7;color:#c53030;">Risque SLA</span>
                    <?php endif; ?>
                  </div>
                  <h6 class="mb-1 fw-bold" style="font-size:14px;"><?php echo e($t->title); ?></h6>
                  <p class="mb-0 text-secondary" style="font-size:12px;">
                    <i class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px;">person</i>
                    <?php echo e($t->client); ?>

                    &nbsp;·&nbsp;
                    <i class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px;">schedule</i>
                    Ouvert depuis <?php echo e($t->hours_open); ?>h
                    &nbsp;·&nbsp; SLA : <?php echo e($t->sla_limit); ?>h
                  </p>
                </div>

                <div style="min-width:200px;text-align:right;">
                  <?php if($t->sla_breached): ?>
                    <span style="font-size:12px;font-weight:700;color:#e53e3e;">
                      +<?php echo e(abs($t->hours_left)); ?>h dépassé
                    </span>
                  <?php else: ?>
                    <span style="font-size:12px;font-weight:700;color:<?php echo e($barColor); ?>;">
                      <?php echo e($t->sla_used); ?>% — <?php echo e($t->hours_left); ?>h restant
                    </span>
                  <?php endif; ?>
                  <div class="sla-bar-bg mt-1">
                    <div class="sla-bar-fill" style="width:<?php echo e($sla); ?>%;background:<?php echo e($barColor); ?>;"></div>
                  </div>
                  <div class="mt-2">
                    <a href="<?php echo e($ticketUrl); ?>"
                       class="btn btn-sm text-white"
                        style="background:#667eea;font-size:11px;padding:3px 10px;border-radius:12px;"
                       onclick="event.stopPropagation();">
                      Traiter le ticket →
                    </a>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  <?php endif; ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/urgent-tickets.blade.php ENDPATH**/ ?>