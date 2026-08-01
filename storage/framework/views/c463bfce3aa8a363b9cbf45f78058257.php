<?php $__env->startSection('content'); ?>
  <?php echo $__env->make('insurance._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  <?php echo $__env->make('insurance._flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  <section class="record-header insurance-record-header">
    <div>
      <p class="eyebrow">Detalle hospitalario</p>
      <h1><?php echo e($hospitalization->patient?->full_name); ?></h1>
      <p><?php echo e($hospitalization->hospital_name ?? $hospitalization->hospital?->name); ?> / <?php echo e($hospitalization->stay_days); ?> dias de estancia</p>
    </div>
    <div class="record-actions">
      <span class="badge"><?php echo e($hospitalization->status); ?></span>
      <a class="secondary-button" href="<?php echo e(route('insurance.patients.show', $hospitalization->patient)); ?>">Expediente</a>
    </div>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title"><h2>Datos del evento</h2></div>
      <dl class="detail-list">
        <div><dt>Ingreso</dt><dd><?php echo e($hospitalization->admitted_at?->format('d/m/Y H:i')); ?></dd></div>
        <div><dt>Egreso</dt><dd><?php echo e($hospitalization->discharged_at?->format('d/m/Y H:i') ?? 'Activo'); ?></dd></div>
        <div><dt>Area</dt><dd><?php echo e($hospitalization->area); ?></dd></div>
        <div><dt>Tipo</dt><dd><?php echo e($hospitalization->event_type); ?></dd></div>
        <div><dt>Autorizacion</dt><dd><?php echo e($hospitalization->authorization_number ?? 'Sin numero'); ?></dd></div>
        <div><dt>Monto autorizado</dt><dd>$<?php echo e(number_format((float) $hospitalization->authorized_amount, 2)); ?></dd></div>
        <div><dt>Motivo</dt><dd><?php echo e($hospitalization->reason ?? 'Sin motivo capturado'); ?></dd></div>
      </dl>
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Facturacion</h2>
        <span>$<?php echo e(number_format((float) $hospitalization->invoices->sum('total'), 2)); ?></span>
      </div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $hospitalization->invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div>
            <strong><?php echo e($invoice->invoice_number); ?> / <?php echo e($invoice->status); ?></strong>
            <span><?php echo e($invoice->fiscal_uuid ?? 'Sin UUID'); ?> / $<?php echo e(number_format((float) $invoice->total, 2)); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin facturas ligadas.</p>
        <?php endif; ?>
      </div>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel insurance-timeline-panel">
      <div class="section-title">
        <h2>Bitacora diaria</h2>
        <span><?php echo e($hospitalization->dailyNotes->count()); ?> notas</span>
      </div>
      <div class="timeline">
        <?php $__empty_1 = true; $__currentLoopData = $hospitalization->dailyNotes->sortByDesc('note_date'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $note): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div>
            <time><?php echo e($note->note_date?->format('d/m/Y')); ?></time>
            <strong><?php echo e($note->general_clinical_status ?? 'Seguimiento'); ?></strong>
            <span><?php echo e($note->administrative_evolution); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin notas diarias.</p>
        <?php endif; ?>
      </div>

      <?php if($abilities['manage_hospitalizations']): ?>
        <form class="inline-form insurance-inline-form" method="post" action="<?php echo e(route('insurance.hospitalization-notes.store')); ?>">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="hospitalization_id" value="<?php echo e($hospitalization->id); ?>">
          <label>Fecha<input type="date" name="note_date" value="<?php echo e(now()->format('Y-m-d')); ?>" required></label>
          <label>Estatus clinico<input name="general_clinical_status"></label>
          <label>Posible egreso<input type="date" name="possible_discharge_date"></label>
          <label class="checkbox-line"><input type="checkbox" name="prolonged_stay_risk" value="1"> Riesgo de prolongacion</label>
          <label class="span-2">Evolucion administrativa<textarea name="administrative_evolution"></textarea></label>
          <label class="span-2">Cambios relevantes<textarea name="relevant_changes"></textarea></label>
          <label class="span-2">Pendientes<textarea name="pending_authorizations"></textarea></label>
          <button type="submit">Agregar nota</button>
        </form>
      <?php endif; ?>
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title"><h2>Autorizaciones y documentos</h2></div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $hospitalization->authorizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $authorization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div>
            <strong><?php echo e($authorization->type); ?> / <?php echo e($authorization->status); ?></strong>
            <span><?php echo e($authorization->authorization_number ?? 'Sin numero'); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin autorizaciones ligadas.</p>
        <?php endif; ?>
      </div>
      <div class="compact-list separated">
        <?php $__empty_1 = true; $__currentLoopData = $hospitalization->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div>
            <strong><?php echo e($document->name); ?></strong>
            <span><?php echo e($document->document_type); ?> / <?php echo e($document->status); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin documentos hospitalarios.</p>
        <?php endif; ?>
      </div>
    </article>
  </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Detalle hospitalizacion'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\insurance\hospitalizations\show.blade.php ENDPATH**/ ?>