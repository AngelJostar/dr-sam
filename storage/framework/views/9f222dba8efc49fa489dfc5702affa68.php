<form method="post" action="<?php echo e(route('institution.services.store')); ?>" class="institution-unit-create-form institution-service-create-form">
  <?php echo csrf_field(); ?>
  <input type="hidden" name="institution" value="<?php echo e($institution->id); ?>">
  <input type="hidden" name="form_context" value="service">
  <label class="is-wide">Unidades con servicio contratado
    <select name="unit_ids[]" multiple size="1" aria-label="Seleccionar hospitales">
      <?php $__currentLoopData = $institution->medicalUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($unit->id); ?>" <?php if(in_array($unit->id, old('unit_ids', []))): echo 'selected'; endif; ?>><?php echo e($unit->name); ?><?php echo e($unit->clues ? ' - '.$unit->clues : ''); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <small>Usa Ctrl para seleccionar mas de un hospital.</small>
  </label>
  <div class="institution-service-create-divider"><strong>Datos del Servicio</strong></div>
  <label>Categoria
    <select name="category" required>
      <?php $__currentLoopData = ['Asistenciales', 'Criticos', 'Diagnosticos', 'Farmaceuticos', 'Quirurgicos']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($category); ?>" <?php if(old('category') === $category): echo 'selected'; endif; ?>><?php echo e($category); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
  </label>
  <label>Especialidad
    <select name="specialty" required>
      <?php $__currentLoopData = $servicesCatalog->pluck('specialty')->filter()->unique()->sort(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($specialty); ?>" <?php if(old('specialty') === $specialty): echo 'selected'; endif; ?>><?php echo e($specialty); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <option value="Servicio general" <?php if(old('specialty') === 'Servicio general'): echo 'selected'; endif; ?>>Servicio general</option>
    </select>
  </label>
  <label>Servicio<input name="name" value="<?php echo e(old('form_context') === 'service' ? old('name') : ''); ?>" placeholder="Captura el nombre del servicio" required></label>
  <label>Inicio<input name="starts_at" type="date" value="<?php echo e(old('form_context') === 'service' ? old('starts_at') : ''); ?>" required></label>
  <label>Fin<input name="ends_at" type="date" value="<?php echo e(old('form_context') === 'service' ? old('ends_at') : ''); ?>" required></label>
  <label>SLA<input name="sla" value="<?php echo e(old('form_context') === 'service' ? old('sla', 'Horario habil') : 'Horario habil'); ?>" required></label>
  <button type="submit">Guardar servicio</button>
</form>
<?php /**PATH C:\laragon\www\dr-sam\resources\views/institution/_service_create_form.blade.php ENDPATH**/ ?>