<?php $__env->startSection('body_class', 'insurance-health-native-body'); ?>

<?php $__env->startSection('content'); ?>
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    <?php echo $__env->make('insurance._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="insurance-health-native-workspace">
      <?php echo $__env->make('insurance._flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Pacientes</p>
          <h1>Pacientes asegurados</h1>
          <p>Consulta, filtrado y seguimiento de asegurados ligados a usuarios de la plataforma.</p>
        </div>
        <?php if($abilities['manage_patients']): ?>
          <a class="primary-button action-link" href="<?php echo e(route('insurance.patients.create')); ?>">Alta paciente</a>
        <?php endif; ?>
      </section>

      <form class="filter-bar insurance-filter-bar" method="get">
        <label>
          Buscar
          <input name="search" value="<?php echo e($filters['search'] ?? ''); ?>" placeholder="Nombre, CURP, RFC, poliza">
        </label>
        <label>
          Estatus
          <select name="status">
            <option value="">Todos</option>
            <?php $__currentLoopData = ['active' => 'Activo', 'suspended' => 'Suspendido', 'discharged' => 'Dado de baja', 'deceased' => 'Fallecido']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($key); ?>" <?php if(($filters['status'] ?? '') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <label>
          Riesgo
          <select name="risk_level">
            <option value="">Todos</option>
            <?php $__currentLoopData = ['low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto', 'critical' => 'Critico']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($key); ?>" <?php if(($filters['risk_level'] ?? '') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <label>
          Diagnostico
          <input name="diagnosis" value="<?php echo e($filters['diagnosis'] ?? ''); ?>" placeholder="Diabetes, cancer">
        </label>
        <button type="submit">Filtrar</button>
      </form>

      <section class="table-card insurance-table-panel">
        <div class="insurance-health-native-table-title">
          <h2>Pacientes asegurados</h2>
          <span><?php echo e($patients->total()); ?> registros</span>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Poliza</th>
              <th>Riesgo</th>
              <th>Estatus</th>
              <th>Actividad</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <?php ($policy = $patient->insurancePolicies->first()); ?>
              <tr>
                <td>
                  <strong><?php echo e($patient->full_name); ?></strong>
                  <span><?php echo e($patient->email ?? 'Sin correo'); ?> / <?php echo e($patient->phone ?? 'Sin telefono'); ?></span>
                </td>
                <td>
                  <strong><?php echo e($policy?->policy_number ?? 'Sin poliza'); ?></strong>
                  <span><?php echo e($policy?->insurer_name ?? 'Sin aseguradora'); ?></span>
                </td>
                <td><span class="badge risk-<?php echo e($patient->risk_level); ?>"><?php echo e($patient->risk_level); ?></span></td>
                <td><span class="badge"><?php echo e($patient->status); ?></span></td>
                <td>
                  <span><?php echo e($patient->diagnoses_count); ?> dx</span>
                  <span><?php echo e($patient->treatments_count); ?> tx</span>
                  <span><?php echo e($patient->hospitalizations_count); ?> hosp</span>
                </td>
                <td><a class="text-link" href="<?php echo e(route('insurance.patients.show', $patient)); ?>">Expediente</a></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr>
                <td colspan="6">No hay pacientes con esos filtros.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

      <div class="pagination-wrap"><?php echo e($patients->links()); ?></div>
    </main>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Pacientes asegurados'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\insurance\patients\index.blade.php ENDPATH**/ ?>