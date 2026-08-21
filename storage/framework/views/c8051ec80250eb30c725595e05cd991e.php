

<?php $__env->startSection('body_class', 'unit-native-body'); ?>

<?php
  $statusLabels = [
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'available' => 'Disponible',
    'scheduled' => 'Programada',
    'completed' => 'Completada',
    'requested' => 'Solicitada',
    'accepted' => 'Aceptada',
    'delivered' => 'Entregada',
    'rejected' => 'Rechazada',
    'pending' => 'Pendiente',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $unitCode = $unit->code ?? $unit->clues ?? 'Sin clave';
  $unitLocation = collect([$unit->city, $unit->municipality, $unit->state])->filter()->implode(', ') ?: 'Sin ubicacion';
  $sectionLabels = [
    'profile' => 'Perfil',
    'services' => 'Servicios habilitados',
    'users' => 'Usuarios operativos',
    'patients' => 'Catalogo de pacientes',
    'doctors' => 'Catalogo de medicos',
    'specialties' => 'Catalogo de especialidades',
    'external-pharmacy' => 'Catalogo de farmacia externa',
    'procedure-areas' => 'Catalogo de areas de procedimiento',
  ];
  $sectionEyebrow = strtoupper($sectionLabels[$section] ?? 'Servicios habilitados');
  $menu = [
    'profile' => ['P', 'Perfil'],
    'services' => ['=', 'Servicios habilitados'],
    'users' => ['U+', 'Usuarios operativos'],
    'patients' => ['P+', 'Catalogo de pacientes'],
    'doctors' => ['*', 'Catalogo de medicos'],
    'specialties' => ['E', 'Catalogo de especialidades'],
    'external-pharmacy' => ['Rx', 'Catalogo de farmacia externa'],
    'procedure-areas' => ['â–¦', 'Catalogo de areas de procedimiento'],
  ];
  $serviceChoices = ['Nutricion enteral', 'Nutricion parenteral', 'Quimioterapias', 'Hemodinamia', 'Analisis Clinicos', 'Histopatologia', 'Tomografia y resonancia', 'Hemodialisis', 'Mantenimiento', 'RPBI', 'Limpieza', 'Lavanderia', 'Dietas', 'Traslado terrestre y aereo'];
  $areaChoices = [
    'Enfermeria' => 'Seguimiento clinico y operativo del servicio.',
    'Farmacia intrahospitalaria' => 'Gestion de medicamentos, mezclas y soporte farmaceutico.',
    'Farmacia Externa' => 'Inventario, recetas, movimientos y almacenes de farmacia externa.',
    'Centro Oncologico' => 'Operacion y seguimiento de servicios oncologicos.',
    'Consulta Externa' => 'Atencion ambulatoria y coordinacion de servicios externos.',
  ];
?>

<?php $__env->startSection('content'); ?>
  <div class="unit-native-screen">
    <aside class="unit-native-sidebar" aria-label="Navegacion unidad">
      <div class="unit-native-brand">
        <span aria-hidden="true">+</span>
        <div>
          <strong>Unidad</strong>
          <small>Operacion hospitalaria</small>
        </div>
      </div>

      <nav class="unit-native-menu">
        <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$icon, $label]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <a class="<?php echo e($section === $key ? 'is-active' : ''); ?>" href="<?php echo e(route('unit.dashboard', ['section' => $key])); ?>">
            <span><?php echo e($icon); ?></span><?php echo e($label); ?>

          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </nav>
    </aside>

    <section class="unit-native-workspace">
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

      <header class="unit-native-header">
        <div>
          <p class="eyebrow"><?php echo e($sectionEyebrow); ?></p>
          <h1><?php echo e($unit->name); ?></h1>
          <p><?php echo e($unitCode); ?> - <?php echo e($unitLocation); ?></p>
        </div>
        <div class="unit-native-selector">
          <label>
            Unidad
            <select>
              <option><?php echo e($unit->name); ?> - <?php echo e($unitCode); ?></option>
            </select>
          </label>
          <time><?php echo e(now()->format('d M Y')); ?></time>
        </div>
      </header>

      <?php if($section === 'profile'): ?>
        <?php
          $profile = $unit->metadata['profile'] ?? [];
          $profileImage = $profile['image_path'] ?? null;
          $profileName = $profile['public_name'] ?? $unit->name;
          $profileInfo = $profile['general_info'] ?? '';
          $profileServices = $profile['services'] ?? $unit->contractedServices->pluck('service.name')->filter()->implode(', ');
          $profileNews = $profile['news'] ?? '';
          $profileSocial = $profile['social_text'] ?? '';
          $profileStationery = $profile['stationery_note'] ?? '';
          $initials = str($profileName)->explode(' ')->filter()->take(2)->map(fn ($word) => str($word)->substr(0, 1))->implode('');
        ?>
        <div class="unit-profile-layout" data-unit-profile>
          <section class="unit-profile-card">
            <header><h2>Perfil de la unidad</h2><p>Imagen, servicios y novedades</p></header>
            <form method="post" action="<?php echo e(route('unit.profile.update')); ?>" enctype="multipart/form-data" class="unit-profile-form">
              <?php echo csrf_field(); ?>
              <?php echo method_field('patch'); ?>
              <label>Imagen de perfil y papelerÃ­a</label>
              <div class="unit-profile-image-row">
                <div class="unit-profile-image-box" data-profile-image-preview><?php if($profileImage): ?><img src="<?php echo e(asset('storage/'.$profileImage)); ?>" alt="Imagen de <?php echo e($profileName); ?>"><?php else: ?><span>Sin imagen</span><?php endif; ?></div>
                <div><input type="file" name="profile_image" accept="image/png,image/jpeg,image/webp" data-profile-image-input><label class="unit-profile-remove"><input type="checkbox" name="remove_image" value="1"> Quitar imagen</label></div>
              </div>
              <label>Nombre pÃºblico de la unidad<input name="public_name" value="<?php echo e(old('public_name', $profileName)); ?>" data-profile-name required></label>
              <label>InformaciÃ³n general<textarea name="general_info" data-profile-info><?php echo e(old('general_info', $profileInfo)); ?></textarea></label>
              <label>Servicios de la unidad<textarea name="services" data-profile-services><?php echo e(old('services', $profileServices)); ?></textarea></label>
              <label>Novedades<textarea name="news" data-profile-news><?php echo e(old('news', $profileNews)); ?></textarea></label>
              <label>Texto para redes sociales<textarea name="social_text" data-profile-social><?php echo e(old('social_text', $profileSocial)); ?></textarea></label>
              <label>Nota para papelerÃ­a<textarea name="stationery_note" data-profile-stationery><?php echo e(old('stationery_note', $profileStationery)); ?></textarea></label>
              <button type="submit">Guardar perfil</button>
            </form>
          </section>
          <section class="unit-profile-card unit-profile-preview-card">
            <header><h2>Vista previa</h2><p>Redes sociales y papelerÃ­a</p></header>
            <div class="unit-profile-preview">
              <div class="unit-profile-cover"><strong data-profile-initials><?php echo e(strtoupper($initials)); ?></strong></div>
              <div class="unit-profile-preview-copy">
                <small><?php echo e($unitCode); ?> - <?php echo e(strtoupper($unitLocation)); ?></small><h2 data-profile-preview-name><?php echo e($profileName); ?></h2>
                <p data-profile-preview-info><?php echo e($profileInfo ?: 'InformaciÃ³n general pendiente.'); ?></p>
                <strong>SERVICIOS</strong><p data-profile-preview-services><?php echo e($profileServices ?: 'Sin servicios registrados.'); ?></p>
                <strong>NOVEDADES</strong><p data-profile-preview-news><?php echo e($profileNews ?: 'Sin novedades registradas.'); ?></p>
                <article><strong>REDES SOCIALES</strong><p data-profile-preview-social><?php echo e($profileSocial ?: 'InformaciÃ³n general pendiente.'); ?></p></article>
                <article><strong>PAPELERÃA</strong><p data-profile-preview-stationery><?php echo e($profileStationery ?: 'PapelerÃ­a institucional de la unidad.'); ?></p></article>
              </div>
            </div>
          </section>
        </div>
        <script>
          (() => {
            const root = document.querySelector('[data-unit-profile]');
            const bind = (input, output, fallback) => input.addEventListener('input', () => output.textContent = input.value.trim() || fallback);
            const name = root.querySelector('[data-profile-name]');
            bind(name, root.querySelector('[data-profile-preview-name]'), <?php echo json_encode($unit->name, 15, 512) ?>);
            bind(root.querySelector('[data-profile-info]'), root.querySelector('[data-profile-preview-info]'), 'InformaciÃ³n general pendiente.');
            bind(root.querySelector('[data-profile-services]'), root.querySelector('[data-profile-preview-services]'), 'Sin servicios registrados.');
            bind(root.querySelector('[data-profile-news]'), root.querySelector('[data-profile-preview-news]'), 'Sin novedades registradas.');
            bind(root.querySelector('[data-profile-social]'), root.querySelector('[data-profile-preview-social]'), 'InformaciÃ³n general pendiente.');
            bind(root.querySelector('[data-profile-stationery]'), root.querySelector('[data-profile-preview-stationery]'), 'PapelerÃ­a institucional de la unidad.');
            name.addEventListener('input', () => root.querySelector('[data-profile-initials]').textContent = name.value.trim().split(/\s+/).slice(0, 2).map(word => word[0] || '').join('').toUpperCase());
            root.querySelector('[data-profile-image-input]').addEventListener('change', (event) => { const file = event.target.files[0]; if (!file) return; root.querySelector('[data-profile-image-preview]').innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Vista previa">`; });
          })();
        </script>
      <?php elseif($section === 'services'): ?>
        <section class="unit-native-filters" aria-label="Filtros de servicios">
          <label class="search">
            <span aria-hidden="true">O</span>
            <input type="search" data-unit-service-search placeholder="Buscar servicio o categoria">
          </label>
          <label>
            Estatus
            <select data-unit-service-status><option value="all">Todos</option><?php $__currentLoopData = $unit->contractedServices->pluck('status')->filter()->unique()->sort(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contractStatus): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($contractStatus); ?>"><?php echo e($statusText($contractStatus)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
          </label>
          <button type="button" data-unit-service-export>&darr;&nbsp; Excel</button>
        </section>

        <section class="unit-native-table-card">
          <div class="unit-native-table-heading">
            <div>
              <h2>Servicios Habilitados por la Institucion</h2>
              <p><?php echo e($unit->contractedServices->count()); ?> servicios encontrados</p>
            </div>
          </div>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table unit-service-table">
              <thead>
                <tr>
                  <th>Operacion</th>
                  <th>Servicio</th>
                  <th>Categoria</th>
                  <th>Vigencia</th>
                  <th>Estatus</th>
                  <th>Reporte de unidad del servicio de nutricion parenteral</th>
                  <th>Ver catalogo de productos / servicios</th>
                </tr>
              </thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $unit->contractedServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $operationSearch = str(($contract->service?->name ?? '').' '.($contract->service?->category ?? '').' '.($contract->service?->specialty ?? ''))->lower()->toString();
                    $operationArea = str_contains($operationSearch, 'oncolo') || str_contains($operationSearch, 'quimio')
                      ? 'oncology'
                      : (str_contains($operationSearch, 'farmac') ? 'inpatient-pharmacy' : 'nursing');
                  ?>
                  <tr data-unit-service-row data-search="<?php echo e(str(($contract->service?->name ?? '').' '.($contract->service?->category ?? '').' '.($contract->service?->specialty ?? ''))->lower()); ?>" data-status="<?php echo e($contract->status); ?>">
                    <td><a class="unit-service-operation" href="<?php echo e(route('operational.dashboard', ['area' => $operationArea, 'section' => 'history', 'unit' => $unit->id, 'service' => $contract->service_id])); ?>">Ver Operacion</a></td>
                    <td><strong><?php echo e($contract->service?->name ?? 'Servicio sin nombre'); ?></strong><small><?php echo e($contract->service?->specialty ?? 'Servicio general'); ?></small></td>
                    <td><?php echo e($contract->service?->category ?? $contract->service?->specialty ?? 'Sin categoria'); ?></td>
                    <td><?php echo e($contract->starts_at?->format('d/m/Y') ?? 'Sin inicio'); ?> - <?php echo e($contract->ends_at?->format('d/m/Y') ?? 'Sin vencimiento'); ?></td>
                    <td><span class="unit-native-status"><?php echo e($statusText($contract->status)); ?></span></td>
                    <td><a class="unit-native-download" href="<?php echo e(route('unit.services.report', $contract)); ?>">Descargar</a></td>
                    <td><button type="button" data-open-service-catalog="<?php echo e($contract->id); ?>">VER</button></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="7" class="unit-native-empty">Sin servicios habilitados para los filtros actuales.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
        <?php $__currentLoopData = $unit->contractedServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $catalogServiceName = $contract->service?->name ?? 'Servicio sin nombre';
            $catalogProvider = data_get($contract->metadata, 'provider', 'Operacion clinica');
            $catalogKey = $contract->service?->code ?? str($catalogServiceName)->slug();
          ?>
          <dialog class="unit-service-catalog-dialog" data-service-catalog-dialog="<?php echo e($contract->id); ?>">
            <header><div><h2>Catalogo de productos/Servicios</h2><p><?php echo e($catalogServiceName); ?> - <?php echo e($catalogProvider); ?></p></div><button type="button" data-close-service-catalog>Cerrar</button></header>
            <div class="unit-service-catalog-facts">
              <article><small>Servicio</small><strong><?php echo e($catalogServiceName); ?></strong></article>
              <article><small>Proveedor</small><strong><?php echo e($catalogProvider); ?></strong></article>
              <article><small>Elementos habilitados</small><strong>1</strong></article>
            </div>
            <div class="unit-service-catalog-scroll">
              <table>
                <thead><tr><th>Clave</th><th>Producto / servicio</th><th>Tipo</th><th>Proveedor</th><th>Detalle</th><th>Estatus</th></tr></thead>
                <tbody><tr><td><?php echo e($catalogKey); ?></td><td><strong><?php echo e($catalogServiceName); ?></strong><small><?php echo e($contract->service?->specialty ?? 'Servicio general'); ?></small></td><td><?php echo e($contract->service?->category ?? 'Sin categoria'); ?></td><td><?php echo e($catalogProvider); ?></td><td>Vigencia <?php echo e($contract->starts_at?->format('d/m/Y') ?? 'sin inicio'); ?> - <?php echo e($contract->ends_at?->format('d/m/Y') ?? 'sin vencimiento'); ?></td><td><span class="unit-native-status"><?php echo e($statusText($contract->status)); ?></span></td></tr></tbody>
              </table>
            </div>
          </dialog>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <script>
          (() => {
            const search = document.querySelector('[data-unit-service-search]');
            const status = document.querySelector('[data-unit-service-status]');
            const rows = [...document.querySelectorAll('[data-unit-service-row]')];
            const apply = () => {
              const term = search.value.toLocaleLowerCase('es').trim();
              rows.forEach((row) => row.hidden = !row.dataset.search.includes(term) || (status.value !== 'all' && row.dataset.status !== status.value));
            };
            search.addEventListener('input', apply);
            status.addEventListener('change', apply);
            document.querySelector('[data-unit-service-export]').addEventListener('click', () => {
              const lines = [['Operacion', 'Servicio', 'Categoria', 'Vigencia', 'Estatus']];
              rows.filter((row) => !row.hidden).forEach((row) => lines.push([...row.cells].slice(0, 5).map((cell) => cell.innerText.trim())));
              const blob = new Blob([lines.map((line) => line.map((value) => `"${String(value).replaceAll('"', '""')}"`).join(',')).join('\n')], { type: 'text/csv;charset=utf-8' });
              const link = document.createElement('a'); link.href = URL.createObjectURL(blob); link.download = 'servicios-habilitados.csv'; link.click(); URL.revokeObjectURL(link.href);
            });
            document.querySelectorAll('[data-open-service-catalog]').forEach((button) => button.addEventListener('click', () => document.querySelector(`[data-service-catalog-dialog="${button.dataset.openServiceCatalog}"]`).showModal()));
            document.querySelectorAll('[data-service-catalog-dialog]').forEach((dialog) => {
              dialog.querySelector('[data-close-service-catalog]').addEventListener('click', () => dialog.close());
              dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
            });
          })();
        </script>
      <?php endif; ?>

      <?php if($section === 'users'): ?>
        <div class="unit-native-two-column">
          <section class="unit-native-form-card">
            <header>
              <h2 data-operational-form-title>Alta de usuario operativo</h2>
              <p>Actualiza informacion, rol, asignacion de area operativa y autorizaciones del usuario</p>
            </header>
            <form method="post" action="<?php echo e(route('unit.operational-users.store')); ?>" data-operational-user-form>
              <?php echo csrf_field(); ?>
              <input type="hidden" name="_method" value="patch" disabled data-operational-method>
              <label class="wide">Nombre completo<input name="name" required></label>
              <label>Usuario o correo<input name="username" required></label>
              <label>Contrasena<input name="password" type="password"></label>
              <label>Rol operativo<select name="role_label"><option>Responsable de Area</option><option>Operador</option></select></label>
              <label>Area autorizadora<select name="authority"><option>Direccion General</option><option>Direccion Administrativa</option></select></label>
              <label>Servicio asignado<select name="service"><?php $__currentLoopData = $unit->contractedServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option><?php echo e($contract->service?->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><option>Nutricion parenteral</option></select></label>

              <fieldset class="wide">
                <legend>Asignacion de Area Operativa</legend>
                <div class="unit-native-choice-grid">
                  <?php $__currentLoopData = $operationalAreas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $area): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label>
                      <input type="radio" name="operational_area_id" value="<?php echo e($area->id); ?>" <?php if($loop->first): echo 'checked'; endif; ?> required>
                      <span><strong><?php echo e($area->label); ?></strong><small><?php echo e($areaChoices[$area->label] ?? 'Operacion y seguimiento del area.'); ?></small></span>
                    </label>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
              </fieldset>

              <fieldset class="wide">
                <legend>Acciones en Area Operativa</legend>
                <div class="unit-native-choice-grid">
                  <?php $__currentLoopData = ['history' => ['Visualizar historial', 'Consultar solicitudes del modulo Area Operativa.'], 'detail' => ['Visualizar detalle', 'Abrir una solicitud y revisar su informacion.'], 'manage' => ['Administrar area operativa', 'Operar inventario, recetas, movimientos y almacenes.'], 'reports' => ['Descargar reportes', 'Exportar reportes del area operativa.']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission => [$action, $copy]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label>
                      <input type="checkbox" name="permissions[]" value="<?php echo e($permission); ?>" <?php if($loop->index < 2): echo 'checked'; endif; ?>>
                      <span><strong><?php echo e($action); ?></strong><small><?php echo e($copy); ?></small></span>
                    </label>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
              </fieldset>

              <button type="submit" data-operational-submit>Crear usuario</button><button type="button" data-operational-reset>Regresar</button>
            </form>
          </section>

          <section class="unit-native-table-card">
            <div class="unit-native-table-heading unit-native-heading-action">
              <div><h2>Usuarios de la unidad</h2><p><?php echo e($unit->operationalProfiles->count()); ?> usuarios</p></div><button type="button" data-operational-export>&darr;&nbsp; Excel</button>
            </div>
            <div class="unit-native-user-list">
              <?php $__empty_1 = true; $__currentLoopData = $unit->operationalProfiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article data-operational-user-row>
                  <div>
                    <h3><?php echo e($profile->area?->label ?? 'Area Operativa'); ?> - <?php echo e($unit->name); ?></h3>
                    <p><?php echo e($profile->role_label ?? 'Responsable de Area'); ?> - <?php echo e($profile->user?->username ?? 'sin-usuario'); ?></p>
                    <p>Asignacion Area Operativa: <?php echo e($profile->area?->label ?? 'Sin area'); ?></p>
                    <p>Acciones Area Operativa: <?php echo e(collect($profile->permissions)->map(fn ($permission) => ['history' => 'Visualizar historial', 'detail' => 'Visualizar detalle', 'manage' => 'Administrar area', 'reports' => 'Descargar reportes'][$permission] ?? $permission)->implode(', ')); ?></p>
                  </div>
                  <span class="unit-native-status"><?php echo e($statusText($profile->status)); ?></span>
                  <button type="button" data-edit-operational-user data-url="<?php echo e(route('unit.operational-users.update', $profile)); ?>" data-name="<?php echo e($profile->user?->name); ?>" data-username="<?php echo e($profile->user?->username); ?>" data-password="" data-role="<?php echo e($profile->role_label); ?>" data-authority="<?php echo e(data_get($profile->metadata, 'authority', 'Direccion Administrativa')); ?>" data-service="<?php echo e(data_get($profile->metadata, 'service', 'Nutricion parenteral')); ?>" data-area="<?php echo e($profile->operational_area_id); ?>" data-permissions='<?php echo json_encode($profile->permissions ?? [], 15, 512) ?>'>Editar</button>
                  <form method="post" action="<?php echo e(route('unit.operational-users.destroy', $profile)); ?>" onsubmit="return confirm('Â¿Eliminar este perfil operativo?')"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button type="submit">Eliminar</button></form>
                </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="unit-native-empty">Sin usuarios operativos registrados.</p>
              <?php endif; ?>
            </div>
          </section>
        </div>
        <script>
          (() => {
            const form = document.querySelector('[data-operational-user-form]'); const method = form.querySelector('[data-operational-method]'); const title = document.querySelector('[data-operational-form-title]'); const submit = form.querySelector('[data-operational-submit]');
            const reset = () => { form.reset(); form.action = <?php echo json_encode(route('unit.operational-users.store'), 15, 512) ?>; method.disabled = true; title.textContent = 'Alta de usuario operativo'; submit.textContent = 'Crear usuario'; form.elements.password.value = ''; };
            document.querySelectorAll('[data-edit-operational-user]').forEach((button) => button.addEventListener('click', () => { const data = button.dataset; form.action = data.url; method.disabled = false; title.textContent = 'Editar usuario operativo'; submit.textContent = 'Guardar cambios'; ['name','username','password'].forEach(key => form.elements[key].value = data[key] || ''); form.elements.role_label.value = data.role; form.elements.authority.value = data.authority; form.elements.service.value = data.service; form.querySelectorAll('[name="operational_area_id"]').forEach(input => input.checked = input.value === data.area); const permissions = JSON.parse(data.permissions || '[]'); form.querySelectorAll('[name="permissions[]"]').forEach(input => input.checked = permissions.includes(input.value)); window.scrollTo({top: 0, behavior: 'smooth'}); }));
            document.querySelector('[data-operational-reset]').addEventListener('click', reset);
            document.querySelector('[data-operational-export]').addEventListener('click', () => { const rows = [['Usuario operativo']]; document.querySelectorAll('[data-operational-user-row]').forEach(row => rows.push([row.innerText.replace(/\s+/g, ' ').trim()])); const blob = new Blob([rows.map(row => row.map(value => `"${value.replaceAll('"','""')}"`).join(',')).join('\n')], {type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='usuarios-operativos.csv'; link.click(); URL.revokeObjectURL(link.href); });
          })();
        </script>
      <?php endif; ?>

      <?php if($section === 'patients'): ?>
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading unit-native-heading-action">
            <div>
              <h2>Catalogo de pacientes</h2>
              <p><?php echo e($patients->count()); ?> pacientes</p>
            </div>
            <button type="button" data-doctor-export>&darr;&nbsp; Excel</button>
          </div>
          <p class="unit-native-card-copy">Pacientes dados de alta por el area operativa de cada hospital.</p>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table">
              <thead><tr><th>Paciente</th><th>Servicio</th><th>Medico / Area</th><th>Estatus</th><th>Actualizacion</th></tr></thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $lastAppointment = $patient->appointments->first(); ?>
                  <tr>
                    <td><strong><?php echo e($patient->full_name); ?></strong><span><?php echo e($patient->record_number ?? $patient->platform_number ?? 'Sin expediente'); ?></span></td>
                    <td><?php echo e($lastAppointment?->specialty ?? 'Sin servicio'); ?></td>
                    <td><?php echo e($lastAppointment?->doctor?->full_name ?? 'Sin medico'); ?></td>
                    <td><span class="unit-native-status"><?php echo e($statusText($patient->status)); ?></span></td>
                    <td><?php echo e($patient->updated_at?->format('d/m/Y')); ?></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="5" class="unit-native-empty">Sin pacientes dados de alta por el area operativa.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'doctors'): ?>
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading unit-native-heading-action">
            <div>
              <h2>Catalogo de medicos adscritos</h2>
              <p><?php echo e($unit->doctors->count()); ?> medicos adscritos</p>
            </div>
            <button type="button" data-doctor-export>Excel</button>
          </div>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table unit-doctors-table">
              <thead><tr><th>Medico</th><th>Cedula</th><th>Especialidad</th><th>Servicio</th><th>Autorizacion</th><th>Acciones</th></tr></thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $unit->doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <tr data-doctor-row>
                    <td><strong><?php echo e($doctor->full_name); ?></strong><small><?php echo e($doctor->subspecialty ?? 'Atencion clinica'); ?></small><span>Usuario plataforma: <?php echo e($doctor->user?->username ?? data_get($doctor->metadata, 'platform_user', 'Sin usuario de plataforma')); ?></span></td>
                    <td><?php echo e($doctor->professional_license ?? 'Sin cedula'); ?></td>
                    <td><?php echo e($doctor->specialty ?? 'Sin especialidad'); ?></td>
                    <td><?php echo e($doctor->service_name ?? 'Sin servicio'); ?></td>
                    <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['unit-native-status', 'is-warning' => $doctor->status === 'pending']); ?>"><?php echo e($statusText($doctor->status === 'active' ? 'active' : 'pending')); ?></span></td>
                    <td><button type="button">Editar autorizaciones</button><form method="post" action="<?php echo e(route('unit.doctors.destroy', $doctor)); ?>" onsubmit="return confirm('Â¿Eliminar este medico?')"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button type="submit">Eliminar</button></form></td>
                  </tr>
                  <tr class="unit-doctor-authorization-row" data-doctor-authorization hidden><td colspan="6">
                    <form method="post" action="<?php echo e(route('unit.doctors.authorizations.update', $doctor)); ?>" class="unit-doctor-authorization-form">
                      <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
                      <header><strong>Editar autorizaciones</strong><span><?php echo e($doctor->full_name); ?></span></header>
                      <label>Estatus de autorizacion<select name="status"><option value="active" <?php if($doctor->status === 'active'): echo 'selected'; endif; ?>>Autorizado</option><option value="pending" <?php if($doctor->status === 'pending'): echo 'selected'; endif; ?>>Pendiente</option><option value="inactive" <?php if($doctor->status === 'inactive'): echo 'selected'; endif; ?>>Inactivo</option></select></label>
                      <fieldset><legend>Servicios autorizados</legend><div class="unit-native-choice-grid">
                        <?php ($authorizedServices = data_get($doctor->metadata, 'services', [$doctor->service_name])); ?>
                        <?php $__empty_2 = true; $__currentLoopData = $unit->contractedServices->pluck('service.name')->filter()->unique()->values(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                          <label><input type="checkbox" name="services[]" value="<?php echo e($serviceName); ?>" <?php if(in_array($serviceName, $authorizedServices, true)): echo 'checked'; endif; ?>><span><strong><?php echo e($serviceName); ?></strong><small>Servicio habilitado para este medico.</small></span></label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                          <?php $__currentLoopData = $serviceChoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label><input type="checkbox" name="services[]" value="<?php echo e($serviceName); ?>" <?php if(in_array($serviceName, $authorizedServices, true)): echo 'checked'; endif; ?>><span><strong><?php echo e($serviceName); ?></strong><small>Servicio habilitado para este medico.</small></span></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                      </div></fieldset>
                      <div class="unit-doctor-authorization-actions"><button type="submit">Guardar autorizaciones</button><button type="button" data-close-doctor-authorization>Regresar</button></div>
                    </form>
                  </td></tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="6" class="unit-native-empty">Sin medicos adscritos registrados.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="unit-native-form-card unit-native-spaced">
          <header><h2>Alta de medico adscrito</h2><p>Catalogo local del hospital</p></header>
          <form method="post" action="<?php echo e(route('unit.doctors.store')); ?>" class="unit-doctor-create-form">
            <?php echo csrf_field(); ?>
            <label>Nombre<input name="first_name" required></label><label>Apellido<input name="last_name" required></label>
            <label>Usuario de la plataforma<input name="platform_user"></label><label>Cedula profesional<input name="professional_license" required></label>
            <label>Especialidad<select name="specialty" required><option value="">Selecciona una especialidad</option><?php $__currentLoopData = $specialties->pluck('specialty')->filter()->unique()->sort(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option><?php echo e($specialty); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <label>Subespecialidad<input name="subspecialty"></label>
            <fieldset class="wide">
              <legend>Servicio adscrito</legend>
              <div class="unit-native-choice-grid">
                <?php $__empty_1 = true; $__currentLoopData = $unit->contractedServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <label><input type="checkbox" name="services[]" value="<?php echo e($contract->service?->name); ?>" <?php if($loop->first): echo 'checked'; endif; ?>><span><strong><?php echo e($contract->service?->name); ?></strong><small>Servicio habilitado para adscripcion medica.</small></span></label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <?php $__currentLoopData = $serviceChoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $choice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label><input type="checkbox" name="services[]" value="<?php echo e($choice); ?>" <?php if($loop->first): echo 'checked'; endif; ?>><span><strong><?php echo e($choice); ?></strong><small>Servicio habilitado para adscripcion medica.</small></span></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
              </div>
            </fieldset>
            <button type="submit">Guardar medico</button>
          </form>
        </section>
        <script>
          document.querySelector('[data-doctor-export]').addEventListener('click', () => { const rows=[['Medico','Cedula','Especialidad','Servicio','Autorizacion']]; document.querySelectorAll('[data-doctor-row]').forEach(row => rows.push([...row.cells].slice(0,5).map(cell => cell.innerText.trim()))); const blob=new Blob([rows.map(row=>row.map(value=>`"${value.replaceAll('"','""')}"`).join(',')).join('\n')],{type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='catalogo-medicos.csv'; link.click(); URL.revokeObjectURL(link.href); });
          document.querySelectorAll('[data-doctor-row]').forEach(row => { const panel = row.nextElementSibling; const editButton = row.querySelector('td:last-child > button'); editButton?.addEventListener('click', () => { document.querySelectorAll('[data-doctor-authorization]').forEach(item => item.hidden = item !== panel); panel.hidden = false; }); panel?.querySelector('[data-close-doctor-authorization]')?.addEventListener('click', () => panel.hidden = true); });
        </script>
      <?php endif; ?>

      <?php if($section === 'specialties'): ?>
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading">
            <h2>Catalogo de especialidades</h2>
            <p><?php echo e($specialties->count()); ?> especialidades habilitadas</p>
          </div>
          <p class="unit-native-card-copy">Especialidades dadas de alta por la institucion padre de esta unidad.</p>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table">
              <thead><tr><th>No.</th><th>Especialidad</th><th>Estatus</th></tr></thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $specialties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <tr>
                    <td><?php echo e($loop->iteration); ?></td>
                    <td><strong><?php echo e($service->specialty ?? $service->name); ?></strong><span><?php echo e($unit->institution?->name ?? 'Institucion'); ?> - Catalogo institucional</span></td>
                    <td><span class="unit-native-status"><?php echo e($statusText($service->status)); ?></span></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="3" class="unit-native-empty">Sin especialidades habilitadas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'procedure-areas'): ?>
        <?php ($procedureCatalogs = [
          'consulting' => ['Catalogo de consultorios', 'Nuevo consultorio', 'consultorio'],
          'infusion' => ['Catalogo de salas de infusion', 'Nueva sala de infusion', 'sala de infusion'],
          'operating' => ['Catalogo de quirofanos', 'Nuevo quirofano', 'quirofano'],
          'recovery' => ['Catalogo de salas de recuperacion', 'Nueva sala de recuperacion', 'sala de recuperacion'],
        ]); ?>
        <?php ($procedureAreas = collect(data_get($unit->metadata, 'procedure_areas', []))); ?>
        <?php ($scheduleDays = ['monday' => 'Lunes', 'tuesday' => 'Martes', 'wednesday' => 'Miercoles', 'thursday' => 'Jueves', 'friday' => 'Viernes', 'saturday' => 'Sabado', 'sunday' => 'Domingo']); ?>
        <div class="unit-procedure-catalogs">
          <?php $__currentLoopData = $procedureCatalogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catalogKey => [$catalogTitle, $createLabel, $catalogSingular]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($catalogAreas = $procedureAreas->where('type', $catalogKey)->values()); ?>
            <?php ($editingArea = request('edit') ? $catalogAreas->firstWhere('id', request('edit')) : null); ?>
            <section class="unit-native-table-card unit-procedure-card" data-procedure-catalog="<?php echo e($catalogKey); ?>">
              <div class="unit-native-table-heading unit-native-heading-action">
                <div><h2><?php echo e($catalogTitle); ?></h2><p><?php echo e($catalogAreas->count()); ?> subunidades</p></div>
                <div class="unit-procedure-heading-actions"><button type="button" data-procedure-export>Excel</button><a class="is-primary" href="<?php echo e(route('unit.dashboard', ['section' => 'procedure-areas', 'create' => $catalogKey])); ?>"><?php echo e($createLabel); ?></a></div>
              </div>
              <div class="unit-native-table-scroll">
                <table class="unit-native-table unit-procedure-table">
                  <thead><tr><th>Ubicacion</th><th>Piso</th><th>Numero de unidad</th><th>Capacidad simultanea</th><th>Responsable de area</th><th>Horario de atencion</th><th>Acciones</th></tr></thead>
                  <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $catalogAreas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $area): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                      <?php ($activeSchedule = collect($area['schedule'] ?? [])->filter(fn ($day) => data_get($day, 'enabled'))->map(fn ($day, $key) => ($scheduleDays[$key] ?? ucfirst($key)).' '.data_get($day, 'start', '08:00').' - '.data_get($day, 'end', '16:00'))->implode(', ')); ?>
                      <tr><td><?php echo e($area['location']); ?></td><td><?php echo e($area['floor']); ?></td><td><strong><?php echo e($area['unit_number']); ?></strong><small><?php echo e(str_replace('Catalogo de ', '', $catalogTitle)); ?></small></td><td><?php echo e($area['capacity']); ?> <?php echo e($area['capacity'] == 1 ? 'paciente simultaneo' : 'pacientes simultaneos'); ?></td><td><?php echo e($area['responsible']); ?></td><td><?php echo e($activeSchedule ?: 'Sin horario'); ?></td><td><a class="unit-native-button" href="<?php echo e(route('unit.dashboard', ['section' => 'procedure-areas', 'edit' => $area['id']])); ?>">Editar</a></td></tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                      <tr><td colspan="7" class="unit-native-empty">Sin <?php echo e(strtolower(str_replace('Catalogo de ', 'catalogo de ', $catalogTitle))); ?> registrados.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
              <?php if(request('create') === $catalogKey || $editingArea): ?>
                <form method="post" action="<?php echo e($editingArea ? route('unit.procedure-areas.update', $editingArea['id']) : route('unit.procedure-areas.store')); ?>" class="unit-procedure-form">
                  <?php echo csrf_field(); ?> <?php if($editingArea): ?> <?php echo method_field('put'); ?> <?php endif; ?><input type="hidden" name="type" value="<?php echo e($catalogKey); ?>">
                  <header><strong><?php echo e($editingArea ? 'Editar '.$catalogSingular : 'Nueva subunidad'); ?></strong><span><?php echo e(ucfirst($catalogSingular)); ?></span></header>
                  <label>Ubicacion<input name="location" value="<?php echo e($editingArea['location'] ?? ''); ?>" required></label><label>Piso<input name="floor" value="<?php echo e($editingArea['floor'] ?? ''); ?>" required></label>
                  <label>Numero de unidad<input name="unit_number" value="<?php echo e($editingArea['unit_number'] ?? ''); ?>" required></label><label>Capacidad simultanea de pacientes<input type="number" min="1" name="capacity" value="<?php echo e($editingArea['capacity'] ?? ''); ?>" required></label>
                  <label class="wide">Responsable de area<select name="responsible"><option value="" <?php if(($editingArea['responsible'] ?? '') === 'Sin responsable'): echo 'selected'; endif; ?>>Sin responsable asignado</option><?php $__currentLoopData = $unit->operationalProfiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($profile->user?->name); ?>" <?php if(($editingArea['responsible'] ?? '') === $profile->user?->name): echo 'selected'; endif; ?>><?php echo e($profile->user?->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                  <fieldset class="wide"><legend>Horario de atencion de la unidad</legend><div class="unit-procedure-schedule">
                    <?php $__currentLoopData = $scheduleDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayKey => $dayLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <?php ($dayEnabled = $editingArea ? data_get($editingArea, "schedule.$dayKey.enabled", false) : $loop->iteration <= 5); ?><div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-enabled' => $dayEnabled]); ?>"><label><input type="checkbox" name="schedule[<?php echo e($dayKey); ?>][enabled]" value="1" <?php if($dayEnabled): echo 'checked'; endif; ?>><?php echo e($dayLabel); ?></label><span>+</span><label>Hora inicio<input type="time" name="schedule[<?php echo e($dayKey); ?>][start]" value="<?php echo e(data_get($editingArea, "schedule.$dayKey.start", '08:00')); ?>"></label><label>Hora fin<input type="time" name="schedule[<?php echo e($dayKey); ?>][end]" value="<?php echo e(data_get($editingArea, "schedule.$dayKey.end", '16:00')); ?>"></label></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </div></fieldset>
                  <div class="unit-procedure-form-actions wide"><button type="submit"><?php echo e($editingArea ? 'Guardar cambios' : 'Guardar subunidad'); ?></button><a href="<?php echo e(route('unit.dashboard', ['section' => 'procedure-areas'])); ?>">Regresar</a></div>
                </form>
              <?php endif; ?>
            </section>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <script>
          document.querySelectorAll('[data-procedure-catalog]').forEach(card => card.querySelector('[data-procedure-export]').addEventListener('click', () => { const headers=[...card.querySelectorAll('th')].map(cell=>cell.innerText.trim()); const link=document.createElement('a'); link.href=URL.createObjectURL(new Blob([headers.join(',')+'\n'],{type:'text/csv;charset=utf-8'})); link.download=`${card.dataset.procedureCatalog}.csv`; link.click(); URL.revokeObjectURL(link.href); }));
        </script>
      <?php endif; ?>

      <?php if($section === 'external-pharmacy'): ?>
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading unit-native-heading-action">
            <div><h2>Catalogo de farmacia externa</h2>
            <p><span data-pharmacy-visible><?php echo e($externalPharmacyCatalog->count()); ?></span> visibles de <?php echo e($externalPharmacyCatalog->count()); ?> medicamentos institucionales - <?php echo e($externalPharmacyCatalog->where('status', 'active')->count()); ?> activos / <?php echo e($externalPharmacyCatalog->where('status', 'inactive')->count()); ?> inactivos</p></div>
            <button type="button" data-pharmacy-export>â†“&nbsp; Excel</button>
          </div>
          <p class="unit-native-card-copy">Medicamentos del catalogo institucional disponibles para consulta de la unidad.</p>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table unit-external-pharmacy-table" data-pharmacy-table>
              <thead>
                <tr><th>CNIS</th><th>Insumo</th><th>Grupo</th><th>Descripcion</th><th>Cobertura</th><th>Estatus</th></tr>
                <tr class="unit-native-filter-row"><th><input placeholder="Filtrar CNIS"></th><th><input placeholder="Filtrar insumo"></th><th><input placeholder="Filtrar grupo"></th><th><input placeholder="Filtrar descripcion"></th><th></th><th></th></tr>
              </thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $externalPharmacyCatalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <tr data-pharmacy-row>
                    <td><?php echo e($item->cnis ?? 'Sin CNIS'); ?></td>
                    <td><strong><?php echo e($item->name); ?></strong><small>Catalogo de farmacia externa</small></td>
                    <td><?php echo e(data_get($item->metadata, 'group', 'Sin grupo')); ?></td>
                    <td><?php echo e($item->presentation ?? $item->generic_name ?? 'Sin descripcion'); ?></td>
                    <td><?php echo e(data_get($item->metadata, 'coverage', 'Unidades moviles, Nucleos basicos, CESSA')); ?></td>
                    <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['unit-native-status', 'is-warning' => $item->status === 'inactive']); ?>"><?php echo e($statusText($item->status)); ?></span><form method="post" action="<?php echo e(route('unit.external-pharmacy.status', $item)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('patch'); ?><input type="hidden" name="status" value="<?php echo e($item->status === 'active' ? 'inactive' : 'active'); ?>"><button type="submit"><?php echo e($item->status === 'active' ? 'Desactivar' : 'Activar'); ?></button></form></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="6" class="unit-native-empty">Sin medicamentos institucionales para esta unidad.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
        <script>
          (() => { const table=document.querySelector('[data-pharmacy-table]'); const filters=[...table.querySelectorAll('.unit-native-filter-row input')]; const rows=[...table.querySelectorAll('[data-pharmacy-row]')]; const visible=document.querySelector('[data-pharmacy-visible]'); const apply=()=>{ let count=0; rows.forEach(row=>{ const cells=[...row.cells]; const show=filters.every((input,index)=>cells[index].innerText.toLowerCase().includes(input.value.trim().toLowerCase())); row.hidden=!show; if(show) count++; }); visible.textContent=count; }; filters.forEach(input=>input.addEventListener('input',apply)); document.querySelector('[data-pharmacy-export]').addEventListener('click',()=>{ const data=[['CNIS','Insumo','Grupo','Descripcion','Cobertura','Estatus'],...rows.filter(row=>!row.hidden).map(row=>[...row.cells].map(cell=>cell.innerText.trim()))]; const blob=new Blob([data.map(row=>row.map(value=>`"${value.replaceAll('"','""')}"`).join(',')).join('\n')],{type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='catalogo-farmacia-externa.csv'; link.click(); URL.revokeObjectURL(link.href); }); })();
        </script>
      <?php endif; ?>
    </section>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Unidad medica'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views\unit\dashboard.blade.php ENDPATH**/ ?>