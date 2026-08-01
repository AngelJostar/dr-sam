<?php $__env->startSection('body_class', 'insurance-health-native-body'); ?>

<?php $__env->startSection('content'); ?>
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    <?php echo $__env->make('insurance._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="insurance-health-native-workspace">
      <?php echo $__env->make('insurance._flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Autorizaciones</p>
          <h1>Solicitudes y vigencias</h1>
          <p>Medicamentos, hospitalizaciones, procedimientos, estudios y prorrogas de estancia.</p>
        </div>
      </section>

  <?php if($abilities['manage_authorizations']): ?>
    <form class="insurance-form insurance-entry-form compact" method="post" action="<?php echo e(route('insurance.authorizations.store')); ?>">
      <?php echo csrf_field(); ?>
      <section class="form-section insurance-form-panel">
        <h2>Nueva solicitud</h2>
        <div class="form-grid">
          <label>Paciente
            <select name="patient_id" required>
              <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($patient->id); ?>"><?php echo e($patient->full_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Tipo
            <select name="type">
              <option value="medication">Medicamento</option>
              <option value="hospitalization">Hospitalizacion</option>
              <option value="procedure">Procedimiento</option>
              <option value="study">Estudio</option>
              <option value="stay_extension">Prorroga estancia</option>
            </select>
          </label>
          <label>Tratamiento
            <select name="treatment_id">
              <option value="">Sin ligar</option>
              <?php $__currentLoopData = $treatments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $treatment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($treatment->id); ?>"><?php echo e($treatment->patient?->full_name); ?> / <?php echo e($treatment->medication_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Hospitalizacion
            <select name="hospitalization_id">
              <option value="">Sin ligar</option>
              <?php $__currentLoopData = $hospitalizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospitalization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($hospitalization->id); ?>"><?php echo e($hospitalization->patient?->full_name); ?> / <?php echo e($hospitalization->hospital_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Fecha solicitud<input type="date" name="requested_at" value="<?php echo e(now()->format('Y-m-d')); ?>"></label>
          <label>Fecha respuesta<input type="date" name="responded_at"></label>
          <label>Estatus
            <select name="status">
              <option value="requested">Solicitada</option>
              <option value="in_review">En revision</option>
              <option value="authorized">Autorizada</option>
              <option value="rejected">Rechazada</option>
              <option value="expired">Vencida</option>
            </select>
          </label>
          <label>Numero<input name="authorization_number"></label>
          <label>Monto<input type="number" step="0.01" min="0" name="authorized_amount" value="0"></label>
          <label>Vigencia<input type="date" name="valid_until"></label>
          <label class="span-2">Justificacion<textarea name="justification"></textarea></label>
        </div>
        <button type="submit">Crear autorizacion</button>
      </section>
    </form>
  <?php endif; ?>

  <section class="table-card insurance-table-panel">
    <table class="data-table">
      <thead>
        <tr>
          <th>Paciente</th>
          <th>Tipo</th>
          <th>Numero</th>
          <th>Vigencia</th>
          <th>Monto</th>
          <th>Estatus</th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $authorizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $authorization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td><a class="text-link" href="<?php echo e(route('insurance.patients.show', $authorization->patient)); ?>"><?php echo e($authorization->patient?->full_name); ?></a></td>
            <td><?php echo e($authorization->type); ?></td>
            <td><?php echo e($authorization->authorization_number ?? 'Sin numero'); ?></td>
            <td><?php echo e($authorization->valid_until?->format('d/m/Y') ?? 'Sin vigencia'); ?></td>
            <td>$<?php echo e(number_format((float) $authorization->authorized_amount, 2)); ?></td>
            <td><span class="badge"><?php echo e($authorization->status); ?></span></td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="6">Sin autorizaciones.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

      <div class="pagination-wrap"><?php echo e($authorizations->links()); ?></div>
    </main>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Autorizaciones'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\insurance\authorizations\index.blade.php ENDPATH**/ ?>