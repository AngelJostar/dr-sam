<?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <?php
    $contracts = $service->contractedServices;
    $activeContracts = $contracts->where('status', 'active');
    $start = $contracts->pluck('starts_at')->filter()->sort()->first();
    $end = $contracts->pluck('ends_at')->filter()->sortDesc()->first();
  ?>
  <section class="institution-service-detail" data-service-detail-panel="<?php echo e($service->id); ?>" data-service-detail-mode="edit" hidden>
    <header class="institution-service-detail-title">
      <div>
        <h2>Editar servicio</h2>
        <p><?php echo e($service->name); ?> &middot; <?php echo e($service->category ?? 'Sin categoria'); ?> &middot; <?php echo e($service->specialty ?? 'Servicio general'); ?></p>
      </div>
      <button type="button" data-service-detail-back>&larr;&nbsp; Cat&aacute;logo de servicios</button>
    </header>
    <nav><button type="button" class="is-active" data-service-tab="units">Habilitar unidades</button><button type="button" data-service-tab="data">Editar datos</button></nav>
    <form class="institution-service-enable" data-service-tab-panel="units" method="post" action="<?php echo e(route('institution.services.units.sync', $service)); ?>">
      <?php echo csrf_field(); ?>
      <header><h2>Habilitar a unidades</h2><p><?php echo e($activeContracts->count()); ?> unidades ya tienen este servicio contratado</p></header>
      <div class="institution-service-facts"><article><small>Categoria</small><strong><?php echo e($service->category ?? 'Sin categoria'); ?></strong></article><article><small>Especialidad</small><strong><?php echo e($service->specialty ?? 'Servicio general'); ?></strong></article><article><small>Servicio</small><strong><?php echo e($service->name); ?></strong></article></div>
      <div class="institution-service-enable-grid"><label>Buscar unidad<input type="search" data-detail-unit-search placeholder="Buscar por unidad, CLUES, entidad o municipio"></label><label>Inicio<input name="starts_at" type="date" value="<?php echo e(optional($start)->format('Y-m-d') ?? now()->format('Y-m-d')); ?>"></label><label>Fin<input name="ends_at" type="date" value="<?php echo e(optional($end)->format('Y-m-d') ?? now()->addYear()->format('Y-m-d')); ?>"></label><label>SLA<input name="sla" value="<?php echo e(data_get($activeContracts->first()?->metadata, 'sla', '24 h')); ?>"></label><label>Estatus<select name="status"><option value="active">Activo</option><option value="inactive">Inactivo</option></select></label></div>
      <label class="institution-service-enable-conditions">Condiciones particulares<textarea name="conditions"><?php echo e(data_get($activeContracts->first()?->metadata, 'conditions', 'Condiciones base para '.$service->name.': vigencia contractual, cumplimiento de SLA, evidencia operativa por hospital y seguimiento de incidencias.')); ?></textarea></label>
      <div class="institution-service-enable-selection"><strong><span data-detail-selected-count><?php echo e($activeContracts->count()); ?></span> unidades seleccionadas</strong><div><button type="button" data-detail-select-visible>Seleccionar visibles</button><button type="button" data-detail-clear>Limpiar</button></div></div>
      <div class="institution-service-enable-list"><?php $__currentLoopData = $institution->medicalUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label data-detail-unit data-search="<?php echo e(str($unit->name.' '.$unit->clues.' '.$unit->city.' '.$unit->state)->lower()); ?>"><input type="checkbox" name="unit_ids[]" value="<?php echo e($unit->id); ?>" data-detail-unit-checkbox <?php if($activeContracts->contains('medical_unit_id', $unit->id)): echo 'checked'; endif; ?>><span><strong><?php echo e($unit->name); ?></strong><small><?php echo e($unit->clues ?? 'Sin CLUES'); ?> - <?php echo e($unit->city ?? $unit->state ?? 'Sin ubicacion'); ?></small></span><em><?php echo e($activeContracts->contains('medical_unit_id', $unit->id) ? 'Ya contratado' : 'Disponible'); ?></em></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
      <footer><button type="button" data-service-detail-back>Cancelar</button><button type="submit" class="is-primary">Guardar unidades</button></footer>
    </form>
    <form class="institution-service-editor" data-service-tab-panel="data" method="post" action="<?php echo e(route('institution.services.update', $service)); ?>" enctype="multipart/form-data" hidden>
      <?php echo csrf_field(); ?>
      <?php echo method_field('PATCH'); ?>
      <header><div><h2>Datos generales del servicio</h2><p>Edicion de condiciones, contrato y estado del servicio</p></div><button type="button" data-service-status-toggle><span class="institution-native-status" data-service-status-label><?php echo e($statusText($service->status)); ?></span> <span data-service-status-action><?php echo e($service->status === 'active' ? 'Inactivar servicio' : 'Activar servicio'); ?></span></button></header>
      <div class="institution-service-facts"><article><small>Categoria</small><strong><?php echo e($service->category ?? 'Sin categoria'); ?></strong></article><article><small>Especialidad</small><strong><?php echo e($service->specialty ?? 'Servicio general'); ?></strong></article><article><small>Servicio</small><strong><?php echo e($service->name); ?></strong></article></div>
      <input type="hidden" name="status" value="<?php echo e($service->status === 'active' ? 'active' : 'inactive'); ?>" data-service-status-input>
      <label>Resumen del servicio<textarea name="summary"><?php echo e(data_get($service->metadata, 'summary', 'Monitorea continuidad clinica, disponibilidad del servicio, capacidad instalada y atencion por unidad.')); ?></textarea></label>
      <label>Condiciones<textarea name="conditions"><?php echo e(data_get($service->metadata, 'conditions', 'Condiciones base para '.$service->name.': vigencia contractual, cumplimiento de SLA, evidencia operativa por hospital y seguimiento de incidencias.')); ?></textarea></label>
      <div class="institution-service-edit-grid"><label>SLA base<input name="sla" value="<?php echo e(data_get($service->metadata, 'sla', data_get($activeContracts->first()?->metadata, 'sla', '24 h'))); ?>"></label><label>Nuevo contrato PDF<input name="contract_pdf" type="file" accept="application/pdf"></label></div>
      <strong class="institution-service-contract-file"><?php echo e(data_get($service->metadata, 'contract_filename', 'Sin contrato PDF cargado')); ?></strong>
      <section class="institution-service-active-units">
        <header><h3>Unidades con servicio activo</h3><p><?php echo e($activeContracts->count()); ?> unidades seleccionadas</p></header>
        <label class="institution-service-active-search">Buscar unidad<input type="search" data-edit-unit-search placeholder="Buscar por unidad, CLUES, entidad o municipio"></label>
        <div class="institution-service-active-selection"><strong><span data-edit-selected-count><?php echo e($activeContracts->count()); ?></span> seleccionadas &middot; <span data-edit-visible-count><?php echo e($institution->medicalUnits->count()); ?></span> visibles</strong><div><button type="button" data-edit-select-visible>Seleccionar visibles</button><button type="button" data-edit-clear-visible>Deseleccionar visibles</button></div></div>
        <div class="institution-service-active-list">
          <?php $__currentLoopData = $institution->medicalUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($isContracted = $activeContracts->contains('medical_unit_id', $unit->id)); ?>
            <label data-edit-unit data-search="<?php echo e(str($unit->name.' '.$unit->clues.' '.$unit->city.' '.$unit->state)->lower()); ?>">
              <input type="checkbox" name="unit_ids[]" value="<?php echo e($unit->id); ?>" data-edit-unit-checkbox <?php if($isContracted): echo 'checked'; endif; ?>>
              <span><strong><?php echo e($unit->name); ?></strong><small><?php echo e($unit->clues ?? 'Sin CLUES'); ?> - <?php echo e($unit->city ?? $unit->state ?? 'Sin ubicacion'); ?></small></span>
              <em><?php echo e($isContracted ? 'Contratado' : 'Sin contratar'); ?></em>
            </label>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </section>
      <footer><button type="button" data-service-detail-back>Cancelar</button><button type="submit" class="is-primary">Guardar cambios</button></footer>
    </form>
  </section>

  <section class="institution-service-detail" data-service-detail-panel="<?php echo e($service->id); ?>" data-service-detail-mode="contract" hidden>
    <header class="institution-service-detail-title"><div><h2><?php echo e($service->name); ?></h2><p><?php echo e($service->category ?? 'Sin categoria'); ?> &middot; <?php echo e($service->specialty ?? 'Servicio general'); ?> &middot; actualizado <?php echo e(optional($service->updated_at)->format('h:i:s a')); ?></p></div><button type="button" data-service-detail-back>&larr; Catalogo de servicios</button></header>
    <div class="institution-contract-layout">
      <section class="institution-contract-preview"><header><div><h2>Contrato del servicio</h2><p>PDF de referencia contractual</p></div><button type="button">Adjuntar</button></header><div class="institution-contract-document"><strong>Contrato del Servicio</strong><p>Documento de referencia para visualizar el contrato asociado al servicio seleccionado.</p><hr><h3>1. Objeto</h3><p>Prestacion, seguimiento y control operativo del servicio contratado por unidad medica.</p><h3>2. Alcance</h3><p>Incluye hospitales contratantes, niveles de servicio, reportes e indicadores operativos.</p><h3>3. SLA</h3><p>El cumplimiento se monitorea por hospital y se actualiza en el tablero operativo.</p></div><footer>contrato-servicio.pdf</footer></section>
      <form class="institution-contract-summary" method="post" action="<?php echo e(route('institution.services.update', $service)); ?>"><h2>Resumen</h2><p>Captura de informacion contractual</p><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><input type="hidden" name="status" value="<?php echo e($service->status === 'active' ? 'active' : 'inactive'); ?>"><label>Nombre del servicio<input value="<?php echo e($service->name); ?>" readonly></label><label>Proveedor<input name="contract_provider" value="<?php echo e(data_get($service->metadata, 'contract_provider')); ?>" placeholder="Captura el nombre del proveedor"></label><label>Vigencia de contrato<input name="contract_validity" value="<?php echo e(data_get($service->metadata, 'contract_validity', $start && $end ? $start->format('d/m/Y').' al '.$end->format('d/m/Y') : '')); ?>" placeholder="Ej. 01/01/2026 al 31/12/2026"></label><?php $__currentLoopData = ['Catalogo de productos y servicios. (Anexo Tecnico)', 'Terminos y Condiciones', 'Tabla de Unidades']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label class="institution-contract-upload"><?php echo e($attachment); ?><input type="file" disabled><small>Los anexos se administran desde el PDF contractual.</small><button type="button" disabled>Ver archivo</button></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><button type="submit" class="is-primary">Guardar resumen</button></form>
    </div>
  </section>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\institution\_service_details.blade.php ENDPATH**/ ?>