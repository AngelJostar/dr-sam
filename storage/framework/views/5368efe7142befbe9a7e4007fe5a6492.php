

<?php $__env->startSection('body_class', 'superadmin-native-body'); ?>

<?php
  $statusLabels = [
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'suspended' => 'Suspendido',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');

  $metricDescriptions = [
    'Modulos activos' => "{$activeModules} modulos administrables",
    'Unidades' => 'Modulo Unidad e Institucion',
    'Medicos' => 'Catalogo medico',
    'Usuarios plataforma' => 'Medicos y pacientes',
    'Vendedores' => 'Soporte a medicos',
    'Hosp. privados' => 'Listado administrable',
    'Pacientes' => 'Catalogo de pacientes',
    'Usuarios de acceso' => 'Modulo de Acceso',
    'Servicios' => 'Contratados o habilitados',
    'Medicamentos' => 'Catalogo universal',
  ];

  $moduleDescriptions = [
    'institution' => 'Catalogo institucional, unidades, servicios, contratos y cobertura.',
    'operational' => 'Solicitudes, autorizaciones, historial operativo y reportes por area.',
    'provider_npt' => 'Catalogos de productos, solicitudes NPT y quimioterapias, estatus de central y reportes.',
    'provider_import' => 'Expedientes de importacion, documentos del paciente, permiso Cofepris, aduana y entrega en almacen.',
    'external_pharmacy' => 'Operacion de farmacia externa, estatus de pedidos y rutas de entrega.',
    'digital_pharmacy' => 'Catalogo de farmacia, pedidos, surtido y trazabilidad de despacho.',
    'doctor' => 'Consulta medica, recetas, expediente clinico y seguimiento de pacientes.',
    'patient' => 'Portal de pacientes, pedidos, recetas y seguimiento personal.',
    'messenger' => 'Rutas de entrega, evidencia, confirmaciones y cierre de pedidos.',
    'insurance_health' => 'Aseguradora, pacientes, autorizaciones, facturacion, documentos y reportes.',
    'insurance_advisor' => 'Polizas GMM, alertas, entregas y seguimiento por asesor.',
    'orders' => 'Pedidos de paciente, carrito y solicitudes de farmacia.',
    'unit' => 'Agenda, inventario, solicitudes y administracion por unidad medica.',
    'superadmin' => 'Gobierno modular, catalogos globales, usuarios y permisos.',
  ];

  $moduleDepartments = [
    'doctor' => 'Direccion Medica',
    'patient' => 'Atencion a Pacientes',
    'superadmin' => 'Gobierno modular',
  ];

?>

<?php $__env->startSection('content'); ?>
  <div class="superadmin-native-screen">
    <?php echo $__env->make('superadmin._sidebar', ['active' => 'dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <section class="superadmin-native-workspace">
      <?php if(session('status')): ?>
        <div class="notice success"><?php echo e(session('status')); ?></div>
      <?php endif; ?>

      <?php if($errors->any()): ?>
        <div class="notice danger">
          <strong>No se pudo actualizar.</strong>
          <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </ul>
        </div>
      <?php endif; ?>

      <header class="superadmin-native-header">
        <div>
          <p class="eyebrow">Modulo superadministrador</p>
          <h1>Gobierno de modulos</h1>
          <p><?php echo e($activeModules); ?> de <?php echo e($totalModules); ?> modulos activos</p>
        </div>
        <div class="superadmin-native-actions">
          <a href="<?php echo e(route('superadmin.dashboard')); ?>">â†» Restablecer</a>
          <strong><?php echo e(auth()->user()?->name ?? 'Superadministrador'); ?> - <?php echo e(now()->format('d/m/Y')); ?></strong>
        </div>
      </header>

      <section class="superadmin-native-metrics" aria-label="Indicadores superadministrador">
        <?php $__currentLoopData = $metrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <article>
            <span><?php echo e($label); ?></span>
            <strong><?php echo e(number_format($value)); ?></strong>
            <small><?php echo e($metricDescriptions[$label] ?? 'Administrable'); ?></small>
          </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </section>

      <section class="superadmin-native-modules">
        <div class="superadmin-native-section-heading">
          <div>
            <h2>Estado de modulos</h2>
            <p><?php echo e($activeModules); ?> de <?php echo e($totalModules); ?> modulos activos</p>
          </div>
          <a href="<?php echo e(route('superadmin.catalog', 'modules')); ?>">â†’ Administrar</a>
        </div>

        <div class="superadmin-native-module-grid">
          <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="superadmin-native-module-card">
              <div class="superadmin-native-module-status">
                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-disabled' => ! $module->enabled]); ?>"></span>
                <?php echo e($module->enabled ? 'Activo' : 'Inactivo'); ?>

              </div>
              <h3><?php echo e($module->label); ?></h3>
              <small><?php echo e($moduleDepartments[$module->key] ?? 'Direccion Administrativa'); ?></small>
              <p><?php echo e($moduleDescriptions[$module->key] ?? 'Modulo administrable de plataforma.'); ?></p>
              <div class="superadmin-native-module-footer">
                <strong><?php echo e(count($module->roles ?? [])); ?> perfiles</strong>
                <a href="<?php echo e(route('superadmin.catalog', ['section' => 'modules', 'selected_module' => $module->key])); ?>#module-config">âŒ Editar</a>
              </div>
            </article>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </section>

      <section class="superadmin-native-bottom">
        <article>
          <div class="section-title compact-title">
            <div>
              <p class="eyebrow">Accesos</p>
              <h2>Usuarios recientes</h2>
            </div>
            <span><?php echo e($users->count()); ?></span>
          </div>
          <div class="superadmin-native-user-list">
            <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <form method="post" action="<?php echo e(route('superadmin.users.update', $user)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('patch'); ?>
                <div>
                  <strong><?php echo e($user->name); ?></strong>
                  <span><?php echo e($user->username); ?> / <?php echo e($roleLabels[$user->role] ?? $user->role); ?></span>
                </div>
                <select name="status">
                  <?php $__currentLoopData = ['active', 'inactive', 'suspended']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($status); ?>" <?php if($user->status === $status): echo 'selected'; endif; ?>><?php echo e($statusText($status)); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <button type="submit">Guardar</button>
              </form>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </article>

        <article>
          <div class="section-title compact-title">
            <div>
              <p class="eyebrow">Bitacora</p>
              <h2>Auditoria reciente</h2>
            </div>
            <span><?php echo e($auditLogs->count()); ?></span>
          </div>
          <div class="compact-list">
            <?php $__empty_1 = true; $__currentLoopData = $auditLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <div>
                <strong><?php echo e($log->event); ?></strong>
                <span><?php echo e($log->user?->name ?? 'Sistema'); ?> / <?php echo e($log->created_at?->format('d/m/Y H:i')); ?></span>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p class="empty-state">Todavia no hay eventos de auditoria.</p>
            <?php endif; ?>
          </div>
        </article>
      </section>
    </section>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Superadministrador'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views/superadmin/dashboard.blade.php ENDPATH**/ ?>