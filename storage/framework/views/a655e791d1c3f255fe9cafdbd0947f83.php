<?php $__env->startSection('body_class', 'insurance-health-native-body'); ?>

<?php $__env->startSection('content'); ?>
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    <?php echo $__env->make('insurance._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="insurance-health-native-workspace">
      <?php echo $__env->make('insurance._flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Hospitalizaciones</p>
          <h1>Eventos hospitalarios</h1>
          <p>Ingreso, estancia, egreso, autorizaciones pendientes y facturacion asociada.</p>
        </div>
      </section>

      <?php if($abilities['manage_hospitalizations']): ?>
        <form class="insurance-form insurance-entry-form compact" method="post" action="<?php echo e(route('insurance.hospitalizations.store')); ?>">
          <?php echo csrf_field(); ?>
          <section class="form-section insurance-form-panel">
            <h2>Registrar hospitalizacion</h2>
            <div class="form-grid">
              <label>Paciente
                <select name="patient_id" required>
                  <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($patient->id); ?>"><?php echo e($patient->full_name); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </label>
              <label>Hospital
                <select name="hospital_id">
                  <option value="">Manual</option>
                  <?php $__currentLoopData = $hospitals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospital): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($hospital->id); ?>"><?php echo e($hospital->name); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </label>
              <label>Hospital manual<input name="hospital_name"></label>
              <label>Medico
                <select name="doctor_id">
                  <option value="">Sin asignar</option>
                  <?php $__currentLoopData = $doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($doctor->id); ?>"><?php echo e($doctor->full_name); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </label>
              <label>Ingreso<input type="datetime-local" name="admitted_at" required></label>
              <label>Egreso<input type="datetime-local" name="discharged_at"></label>
              <label>Area
                <select name="area">
                  <option value="urgencias">Urgencias</option>
                  <option value="hospitalizacion">Hospitalizacion</option>
                  <option value="uci">UCI</option>
                  <option value="quirofano">Quirofano</option>
                  <option value="terapia_intermedia">Terapia intermedia</option>
                </select>
              </label>
              <label>Estatus
                <select name="status">
                  <option value="active">Activa</option>
                  <option value="in_review">En revision</option>
                  <option value="discharged">Egresada</option>
                  <option value="billed">Facturada</option>
                </select>
              </label>
              <label>Tipo
                <select name="event_type">
                  <option value="programmed">Programado</option>
                  <option value="emergency">Urgencia</option>
                  <option value="complication">Complicacion</option>
                  <option value="relapse">Recaida</option>
                </select>
              </label>
              <label>Autorizacion<input name="authorization_number"></label>
              <label>Monto autorizado<input type="number" min="0" step="0.01" name="authorized_amount" value="0"></label>
              <label class="span-2">Motivo<textarea name="reason"></textarea></label>
            </div>
            <button type="submit">Registrar</button>
          </section>
        </form>
      <?php endif; ?>

      <form class="filter-bar insurance-filter-bar" method="get">
        <label>Estatus
          <select name="status">
            <option value="">Todos</option>
            <?php $__currentLoopData = ['active', 'discharged', 'cancelled', 'in_review', 'billed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($status); ?>" <?php if(request('status') === $status): echo 'selected'; endif; ?>><?php echo e($status); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <label>Hospital<input name="hospital" value="<?php echo e(request('hospital')); ?>"></label>
        <button type="submit">Filtrar</button>
      </form>

      <section class="table-card insurance-table-panel">
        <div class="insurance-health-native-table-title">
          <h2>Hospitalizaciones</h2>
          <span><?php echo e($hospitalizations->total()); ?> registros</span>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Hospital</th>
              <th>Ingreso</th>
              <th>Area</th>
              <th>Dias</th>
              <th>Estatus</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $hospitalizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospitalization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td><?php echo e($hospitalization->patient?->full_name); ?></td>
                <td><?php echo e($hospitalization->hospital_name ?? $hospitalization->hospital?->name); ?></td>
                <td><?php echo e($hospitalization->admitted_at?->format('d/m/Y H:i')); ?></td>
                <td><?php echo e($hospitalization->area); ?></td>
                <td><?php echo e($hospitalization->stay_days); ?></td>
                <td><span class="badge"><?php echo e($hospitalization->status); ?></span></td>
                <td><a class="text-link" href="<?php echo e(route('insurance.hospitalizations.show', $hospitalization)); ?>">Detalle</a></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="7">Sin hospitalizaciones.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

      <div class="pagination-wrap"><?php echo e($hospitalizations->links()); ?></div>
    </main>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Hospitalizaciones'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\insurance\hospitalizations\index.blade.php ENDPATH**/ ?>