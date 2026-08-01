<?php $__env->startSection('body_class', 'insurance-health-native-body'); ?>

<?php $__env->startSection('content'); ?>
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    <?php echo $__env->make('insurance._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="insurance-health-native-workspace">
      <?php echo $__env->make('insurance._flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

      <div class="insurance-health-native-pagebar">
        <div class="insurance-health-native-page-title">
          <p class="eyebrow">Modulo aseguradora</p>
          <h1>Control clinico-administrativo</h1>
          <p>Trazabilidad de pacientes asegurados, tratamientos continuos, entregas, hospitalizaciones, autorizaciones y facturacion.</p>
        </div>
        <div class="insurance-health-native-session">
          <span><?php echo e(auth()->user()?->name ?? 'Superadministrador'); ?></span>
          <form method="post" action="<?php echo e(route('logout')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit">Salir</button>
          </form>
        </div>
      </div>

      <section class="insurance-health-native-hero">
        <div>
          <p class="eyebrow">Vista general</p>
          <h2>Gestion clinico-administrativa</h2>
          <p>Resumen operativo de pacientes, riesgos, entregas, hospitalizaciones, autorizaciones y facturacion.</p>
        </div>
        <?php if($abilities['manage_patients']): ?>
          <a class="insurance-health-native-primary-action" href="<?php echo e(route('insurance.patients.create')); ?>">Nuevo paciente</a>
        <?php endif; ?>
      </section>

      <section class="insurance-health-native-metrics" aria-label="Indicadores operativos">
        <?php $__currentLoopData = $metrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <article>
            <span><?php echo e($label); ?></span>
            <strong><?php echo e(is_float($value) ? '$'.number_format($value, 2) : number_format($value)); ?></strong>
          </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </section>

      <section class="insurance-health-native-grid">
        <article class="insurance-health-native-panel">
          <div class="insurance-health-native-heading">
            <h2>Pacientes por diagnostico</h2>
            <span><?php echo e($patientsByDiagnosis->count()); ?> grupos</span>
          </div>
          <div class="insurance-health-native-list">
            <?php $__empty_1 = true; $__currentLoopData = $patientsByDiagnosis; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <div>
                <strong><?php echo e($row->label); ?></strong>
                <span><?php echo e($row->total); ?> pacientes</span>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p>Sin diagnosticos registrados.</p>
            <?php endif; ?>
          </div>
        </article>

        <article class="insurance-health-native-panel">
          <div class="insurance-health-native-heading">
            <h2>Alertas operativas</h2>
            <span><?php echo e($alerts->count()); ?> visibles</span>
          </div>
          <div class="insurance-health-native-alerts">
            <?php $__empty_1 = true; $__currentLoopData = $alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <a class="<?php echo e($alert['severity']); ?>" href="<?php echo e($alert['url']); ?>">
                <span><?php echo e($alert['type']); ?></span>
                <strong><?php echo e($alert['message']); ?></strong>
              </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p>No hay alertas criticas por atender.</p>
            <?php endif; ?>
          </div>
        </article>
      </section>

    </main>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Aseguradora salud'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\insurance\dashboard.blade.php ENDPATH**/ ?>