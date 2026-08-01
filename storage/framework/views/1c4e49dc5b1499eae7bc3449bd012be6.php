<?php $__env->startSection('content'); ?>
  <section class="page-heading">
    <div>
      <p class="eyebrow">Sesion de revision</p>
      <h1><?php echo e($user->name); ?></h1>
      <p>Rol: <?php echo e($user->role); ?> / Modulo inicial: <?php echo e($user->module); ?></p>
    </div>
  </section>

  <section class="metric-grid" aria-label="Resumen de datos">
    <?php $__currentLoopData = $summary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <article class="metric-card">
        <span><?php echo e($label); ?></span>
        <strong><?php echo e(number_format($value)); ?></strong>
      </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </section>

  <section class="module-section">
    <div class="section-title">
      <h2>Flujos disponibles</h2>
      <span><?php echo e($modules->count()); ?> modulos</span>
    </div>

    <div class="module-grid">
      <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a class="module-card<?php echo e($module['priority'] ? ' module-card-priority-'.$module['priority'] : ''); ?>" href="<?php echo e($module['url']); ?>">
          <?php if($module['priority']): ?>
            <span class="module-priority-mark">
              <i aria-hidden="true"></i>
              <?php echo e($module['priority'] === 'high' ? 'Prioridad alta' : 'Prioridad media'); ?>

            </span>
          <?php endif; ?>
          <span><?php echo e($module['label']); ?></span>
          <small><?php echo e($module['target']); ?></small>
          <?php if(in_array($module['key'], ['operational', 'operational_outpatient', 'external_pharmacy'], true)): ?>
            <strong class="module-card-access">Acceder</strong>
          <?php endif; ?>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Dashboard Dr. Sam'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\dashboard.blade.php ENDPATH**/ ?>