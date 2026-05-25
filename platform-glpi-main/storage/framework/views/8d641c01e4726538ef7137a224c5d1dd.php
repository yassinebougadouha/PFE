<?php if($paginator->hasPages()): ?>
<nav aria-label="Pagination" class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 px-2">

  
  <p class="text-xs text-secondary mb-0">
    Affichage <strong><?php echo e($paginator->firstItem()); ?></strong>–<strong><?php echo e($paginator->lastItem()); ?></strong>
    sur <strong><?php echo e($paginator->total()); ?></strong> résultats
  </p>

  
  <ul class="pagination mb-0" style="gap:4px;">

    
    <?php if($paginator->onFirstPage()): ?>
      <li class="page-item disabled">
        <span class="page-link border-radius-md px-3 py-2"
              style="font-size:13px;color:#adb5bd;border:1px solid #e9ecef;background:#f8f9fa;cursor:not-allowed;">
          <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">chevron_left</i>
        </span>
      </li>
    <?php else: ?>
      <li class="page-item">
        <a class="page-link border-radius-md px-3 py-2"
           href="<?php echo e($paginator->previousPageUrl()); ?>"
           style="font-size:13px;border:1px solid #e9ecef;color:var(--color-primary);background:white;transition:all 0.2s;"
           onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();this.style.color='white';"
           onmouseout="this.style.background='white';this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();">
          <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">chevron_left</i>
        </a>
      </li>
    <?php endif; ?>

    
    <?php $__currentLoopData = $elements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php if(is_string($element)): ?>
        <li class="page-item disabled">
          <span class="page-link border-radius-md px-3 py-2"
                style="font-size:13px;border:1px solid #e9ecef;background:#f8f9fa;color:#adb5bd;">
            …
          </span>
        </li>
      <?php endif; ?>

      <?php if(is_array($element)): ?>
        <?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php if($page == $paginator->currentPage()): ?>
            <li class="page-item active">
              <span class="page-link border-radius-md px-3 py-2"
                    style="font-size:13px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));
                           border:none;color:white;font-weight:600;
                           box-shadow:0 4px 12px rgba(102,126,234,0.4);">
                <?php echo e($page); ?>

              </span>
            </li>
          <?php else: ?>
            <li class="page-item">
              <a class="page-link border-radius-md px-3 py-2"
                 href="<?php echo e($url); ?>"
                 style="font-size:13px;border:1px solid #e9ecef;color:#495057;background:white;transition:all 0.2s;"
                 onmouseover="this.style.background='#f0f4ff';this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();this.style.borderColor='#c5cbe8';"
                 onmouseout="this.style.background='white';this.style.color='#495057';this.style.borderColor='#e9ecef';">
                <?php echo e($page); ?>

              </a>
            </li>
          <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    
    <?php if($paginator->hasMorePages()): ?>
      <li class="page-item">
        <a class="page-link border-radius-md px-3 py-2"
           href="<?php echo e($paginator->nextPageUrl()); ?>"
           style="font-size:13px;border:1px solid #e9ecef;color:var(--color-primary);background:white;transition:all 0.2s;"
           onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();this.style.color='white';"
           onmouseout="this.style.background='white';this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();">
          <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">chevron_right</i>
        </a>
      </li>
    <?php else: ?>
      <li class="page-item disabled">
        <span class="page-link border-radius-md px-3 py-2"
              style="font-size:13px;color:#adb5bd;border:1px solid #e9ecef;background:#f8f9fa;cursor:not-allowed;">
          <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">chevron_right</i>
        </span>
      </li>
    <?php endif; ?>

  </ul>
</nav>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/vendor/pagination/custom.blade.php ENDPATH**/ ?>