<?php $__env->startSection('body_class', 'insurance-health-native-body'); ?>

<?php $__env->startSection('content'); ?>
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    <?php echo $__env->make('insurance._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="insurance-health-native-workspace">
      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Reportes</p>
          <h1>Reportes operativos</h1>
          <p>Consultas filtrables para seguimiento clinico-administrativo y control financiero.</p>
        </div>
      </section>

  <form class="filter-bar insurance-filter-bar" method="get">
    <label>Desde<input type="date" name="period_from" value="<?php echo e($filters['period_from'] ?? ''); ?>"></label>
    <label>Hasta<input type="date" name="period_to" value="<?php echo e($filters['period_to'] ?? ''); ?>"></label>
    <button type="submit">Actualizar</button>
  </form>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Pacientes activos por diagnostico</h2></div>
      <div class="compact-list">
        <?php $__currentLoopData = $reports['active_patients_by_diagnosis']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div><strong><?php echo e($row->label); ?></strong><span><?php echo e($row->total); ?> pacientes</span></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </article>
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Hospitalizaciones por hospital</h2></div>
      <div class="compact-list">
        <?php $__currentLoopData = $reports['hospitalizations_by_hospital']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div><strong><?php echo e($row->label); ?></strong><span><?php echo e($row->total); ?> eventos / <?php echo e(number_format((float) $row->average_stay, 1)); ?> dias promedio</span></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Medicamentos entregados</h2></div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $reports['delivered_medications']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div><strong><?php echo e($delivery->patient?->full_name); ?></strong><span><?php echo e($delivery->treatment?->medication_name); ?> / <?php echo e($delivery->actual_delivery_date?->format('d/m/Y')); ?></span></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin entregas en el periodo.</p>
        <?php endif; ?>
      </div>
    </article>
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Entregas pendientes</h2></div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $reports['pending_medication_deliveries']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div><strong><?php echo e($delivery->patient?->full_name); ?></strong><span><?php echo e($delivery->treatment?->medication_name); ?> / <?php echo e($delivery->scheduled_delivery_date?->format('d/m/Y')); ?></span></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin entregas pendientes.</p>
        <?php endif; ?>
      </div>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Facturacion por proveedor</h2></div>
      <div class="compact-list">
        <?php $__currentLoopData = $reports['billing_by_provider']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div><strong><?php echo e($row->label); ?></strong><span><?php echo e($row->invoices_count); ?> facturas / $<?php echo e(number_format((float) $row->total, 2)); ?></span></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </article>
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Facturas pendientes de pago</h2></div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $reports['pending_invoices']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div><strong><?php echo e($invoice->invoice_number); ?></strong><span><?php echo e($invoice->status); ?> / $<?php echo e(number_format((float) $invoice->total, 2)); ?></span></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin facturas pendientes.</p>
        <?php endif; ?>
      </div>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Autorizaciones pendientes</h2></div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $reports['pending_authorizations']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $authorization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div><strong><?php echo e($authorization->patient?->full_name); ?></strong><span><?php echo e($authorization->type); ?> / <?php echo e($authorization->requested_at?->format('d/m/Y')); ?></span></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin autorizaciones pendientes.</p>
        <?php endif; ?>
      </div>
    </article>
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Tratamientos por renovar</h2></div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $reports['treatments_to_renew']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $treatment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div><strong><?php echo e($treatment->patient?->full_name); ?></strong><span><?php echo e($treatment->medication_name); ?> / vence <?php echo e($treatment->ends_at?->format('d/m/Y')); ?></span></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin tratamientos por renovar.</p>
        <?php endif; ?>
      </div>
    </article>
      </section>
    </main>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Reportes aseguradora'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\insurance\reports\index.blade.php ENDPATH**/ ?>