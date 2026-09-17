<div class="patient-history-body" data-clinical-history>
  <nav class="patient-health-parameter-switch patient-history-view-switch" data-history-view-switch role="tablist" aria-label="Vista del historial clínico">
    <button type="button" data-history-view="favorites" role="tab" aria-selected="false" tabindex="-1"><span aria-hidden="true">&#9733;</span>Favoritos</button>
    <button class="is-active" type="button" data-history-view="all" role="tab" aria-selected="true">Todos</button>
  </nav>
  <nav class="patient-history-filters" aria-label="Filtros del historial clínico">
    <button class="is-all is-active" type="button" data-history-filter="all" aria-pressed="true"><span class="patient-register-history-image patient-register-history-all-image" aria-hidden="true"><img src="/images/history-icons/all.png?v=20260828-history-all" alt="" loading="lazy"></span><b>Todos</b></button>
    <button class="is-consultation" type="button" data-history-filter="consultation" aria-pressed="false"><span class="patient-register-history-image" aria-hidden="true"><img src="/images/history-icons/consultation.png?v=20260827-history-icons" alt="" loading="lazy"></span><b>Consulta médica</b></button>
    <button class="is-laboratory" type="button" data-history-filter="laboratory" aria-pressed="false"><span class="patient-register-history-image" aria-hidden="true"><img src="/images/history-icons/laboratory.png?v=20260827-history-icons" alt="" loading="lazy"></span><b>Análisis de laboratorio</b></button>
    <button class="is-study" type="button" data-history-filter="study" aria-pressed="false"><span class="patient-register-history-image" aria-hidden="true"><img src="/images/history-icons/studies.png?v=20260827-history-icons" alt="" loading="lazy"></span><b>Estudios</b></button>
    <button class="is-prescription" type="button" data-history-filter="prescription" aria-pressed="false"><span class="patient-register-history-image" aria-hidden="true"><img src="/images/history-icons/prescriptions.png?v=20260827-history-icons" alt="" loading="lazy"></span><b>Recetas</b></button>
    <button class="is-hospitalization" type="button" data-history-filter="hospitalization" aria-pressed="false"><span class="patient-register-history-image" aria-hidden="true"><img src="/images/history-icons/hospitalization.png?v=20260827-history-icons" alt="" loading="lazy"></span><b>Hospitalización</b></button>
    <button class="is-vaccine" type="button" data-history-filter="vaccine" aria-pressed="false"><span class="patient-register-history-image" aria-hidden="true"><img src="/images/history-icons/vaccines.png?v=20260827-history-icons" alt="" loading="lazy"></span><b>Vacunas</b></button>
    <button class="is-manual" type="button" data-history-filter="manual" aria-pressed="false"><span class="patient-register-history-image" aria-hidden="true"><img src="/images/history-icons/manual-register.png?v=20260827-history-icons" alt="" loading="lazy"></span><b>Registro manual</b></button>
  </nav>
  <div class="patient-history-table-wrap">
    <table class="patient-history-table">
      <thead><tr><th>Fecha</th><th>Categoría</th><th>Origen</th><th>Especialidad o tipo de servicio</th><th>Resumen clínico</th><th>Receta / Indicaciones</th></tr></thead>
      <tbody data-history-body>
        <?php $__empty_1 = true; $__currentLoopData = $historyItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr data-history-row="<?php echo e($item['category']); ?>" data-history-favorite-key="<?php echo e($item['key']); ?>" data-history-primary-row="1">
            <td>
              <div class="patient-history-date-cell">
                <div class="patient-history-date"><span>□</span><strong><?php echo e($item['date']?->format('d/m/Y') ?? 'No registrada'); ?><small><?php echo e($item['date']?->translatedFormat('l') ?? ''); ?></small></strong></div>
                <button class="patient-history-favorite" type="button" data-history-favorite aria-pressed="false" aria-label="Agregar registro a favoritos" title="Agregar a favoritos"><span aria-hidden="true">&#9734;</span></button>
              </div>
            </td>
            <td><span class="patient-history-category is-<?php echo e($item['category']); ?>"><?php echo e($historyCategoryLabels[$item['category']] ?? 'Registro clínico'); ?></span></td>
            <td><div class="patient-history-origin"><span>♙</span><strong><?php echo e($item['origin']); ?></strong></div></td>
            <td><div class="patient-history-service"><span>⌁</span><strong><?php echo e($item['service']); ?></strong></div></td>
            <td><?php echo e($item['summary']); ?></td>
            <td>
              <?php if(count($item['indications'])): ?>
                <div class="patient-history-indications">
                  <span>▤</span>
                  <?php $__currentLoopData = $item['indications']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $indication): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><strong><?php echo e($indication['title']); ?></strong><p><?php echo e($indication['detail']); ?></p></div>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
              <?php else: ?>
                <span class="patient-history-not-registered">No registrado</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr data-history-empty><td colspan="6" class="patient-portal-empty">No hay registros clínicos.</td></tr>
        <?php endif; ?>
        <tr data-history-filter-empty hidden><td colspan="6" class="patient-portal-empty">No hay registros en esta categoría.</td></tr>
      </tbody>
    </table>
  </div>
</div>
<?php /**PATH C:\laragon\www\dr-sam\resources\views/patient/partials/clinical-history.blade.php ENDPATH**/ ?>