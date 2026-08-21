

<?php $__env->startSection('content'); ?>
  <?php echo $__env->make('insurance._nav', ['abilities' => ['admin_users' => false]], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  <?php echo $__env->make('insurance._flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  <section class="page-heading insurance-section-hero">
    <div>
      <p class="eyebrow">Pacientes</p>
      <h1><?php echo e($mode === 'create' ? 'Alta de paciente' : 'Editar paciente'); ?></h1>
      <p>Datos generales, poliza, medico tratante y nivel de riesgo operativo.</p>
    </div>
  </section>

  <form class="insurance-form insurance-patient-form" method="post" action="<?php echo e($mode === 'create' ? route('insurance.patients.store') : route('insurance.patients.update', $patient)); ?>">
    <?php echo csrf_field(); ?>
    <?php if($mode === 'edit'): ?>
      <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    <section class="form-section insurance-form-panel">
      <h2>Datos generales</h2>
      <div class="form-grid">
        <label>Nombre completo<input name="full_name" value="<?php echo e(old('full_name', $patient->full_name)); ?>" required></label>
        <label>CURP<input name="curp" maxlength="18" value="<?php echo e(old('curp', $patient->curp)); ?>"></label>
        <label>RFC<input name="rfc" maxlength="13" value="<?php echo e(old('rfc', $patient->rfc)); ?>"></label>
        <label>Fecha nacimiento<input type="date" name="birth_date" value="<?php echo e(old('birth_date', $patient->birth_date?->format('Y-m-d'))); ?>"></label>
        <label>Sexo
          <select name="sex">
            <?php $__currentLoopData = ['' => 'Seleccionar', 'Femenino' => 'Femenino', 'Masculino' => 'Masculino', 'No especificado' => 'No especificado']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($key); ?>" <?php if(old('sex', $patient->sex) === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <label>Telefono<input name="phone" value="<?php echo e(old('phone', $patient->phone)); ?>"></label>
        <label>Correo<input type="email" name="email" value="<?php echo e(old('email', $patient->email)); ?>"></label>
        <label>Medico tratante
          <select name="primary_doctor_id">
            <option value="">Sin asignar</option>
            <?php $__currentLoopData = $doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($doctor->id); ?>" <?php if((string) old('primary_doctor_id', $patient->primary_doctor_id) === (string) $doctor->id): echo 'selected'; endif; ?>><?php echo e($doctor->full_name); ?> / <?php echo e($doctor->specialty); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <label class="span-2">Direccion<textarea name="address"><?php echo e(old('address', $patient->address)); ?></textarea></label>
      </div>
    </section>

    <?php if($mode === 'create'): ?>
      <section class="form-section insurance-form-panel">
        <h2>Poliza</h2>
        <div class="form-grid">
          <label>Numero de poliza<input name="policy_number" value="<?php echo e(old('policy_number', $policy->policy_number)); ?>" required></label>
          <label>Aseguradora<input name="insurer_name" value="<?php echo e(old('insurer_name', $policy->insurer_name)); ?>" required></label>
          <label>Plan<input name="plan_name" value="<?php echo e(old('plan_name', $policy->plan_name)); ?>"></label>
          <label>Empresa contratante<input name="employer_name" value="<?php echo e(old('employer_name', $policy->employer_name)); ?>"></label>
        </div>
      </section>
    <?php endif; ?>

    <section class="form-section insurance-form-panel">
      <h2>Control operativo</h2>
      <div class="form-grid">
        <label>Estatus
          <select name="status" required>
            <?php $__currentLoopData = ['active' => 'Activo', 'suspended' => 'Suspendido', 'discharged' => 'Dado de baja', 'deceased' => 'Fallecido']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($key); ?>" <?php if(old('status', $patient->status) === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <label>Nivel de riesgo
          <select name="risk_level" required>
            <?php $__currentLoopData = ['low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto', 'critical' => 'Critico']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($key); ?>" <?php if(old('risk_level', $patient->risk_level) === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <label>Fecha alta programa<input type="date" name="enrolled_at" value="<?php echo e(old('enrolled_at', $patient->enrolled_at?->format('Y-m-d'))); ?>"></label>
        <label class="span-2">Observaciones<textarea name="general_observations"><?php echo e(old('general_observations', $patient->general_observations)); ?></textarea></label>
      </div>
    </section>

    <div class="form-actions">
      <a class="secondary-button" href="<?php echo e(route('insurance.patients.index')); ?>">Cancelar</a>
      <button type="submit"><?php echo e($mode === 'create' ? 'Crear paciente' : 'Guardar cambios'); ?></button>
    </div>
  </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => $mode === 'create' ? 'Alta paciente' : 'Editar paciente'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views\insurance\patients\form.blade.php ENDPATH**/ ?>