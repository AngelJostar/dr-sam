<?php $__env->startSection('body_class', $type === 'chemotherapy' ? 'provider-chemo-native-body' : ($type === 'import' ? 'provider-import-native-body' : 'provider-native-body')); ?>

<?php
  $statusLabels = [
    'requested' => 'Solicitada',
    'accepted' => 'Aceptada',
    'preparing' => 'Preparacion',
    'in_route' => 'En ruta',
    'delivered' => 'Entregada',
    'rejected' => 'Rechazada',
    'cancelled' => 'Cancelada',
  ];

  $typeLabels = [
    'npt' => 'NPT',
    'chemotherapy' => 'Oncologicas',
    'import' => 'Importacion',
  ];

  $serviceLabels = [
    'npt' => 'Nutricion Parenteral',
    'chemotherapy' => 'Quimioterapia',
    'import' => 'Medicamentos de importacion',
  ];

  $chemoCatalog = collect([
    ['family' => 'Agentes alquilantes', 'name' => 'Ciclofosfamida', 'unit' => 'mg', 'use' => 'Dosis capturada para esquemas de quimioterapia.'],
    ['family' => 'Agentes alquilantes', 'name' => 'Ifosfamida', 'unit' => 'mg', 'use' => 'Antineoplasico para protocolos con proteccion urotroxa.'],
    ['family' => 'Platinos', 'name' => 'Cisplatino', 'unit' => 'mg', 'use' => 'Medicamento de platino para mezcla oncologica.'],
    ['family' => 'Platinos', 'name' => 'Carboplatino', 'unit' => 'mg', 'use' => 'Dosis por AUC o calculo medico capturada en la solicitud.'],
    ['family' => 'Platinos', 'name' => 'Oxaliplatino', 'unit' => 'mg', 'use' => 'Platino usado en protocolos gastrointestinales.'],
    ['family' => 'Taxanos', 'name' => 'Paclitaxel', 'unit' => 'mg', 'use' => 'Taxano para infusion oncologica.'],
    ['family' => 'Taxanos', 'name' => 'Docetaxel', 'unit' => 'mg', 'use' => 'Taxano solicitado por protocolo.'],
    ['family' => 'Antraciclinas', 'name' => 'Doxorrubicina', 'unit' => 'mg', 'use' => 'Antraciclina capturada en el formato oncologico.'],
    ['family' => 'Antraciclinas', 'name' => 'Epirubicina', 'unit' => 'mg', 'use' => 'Antraciclina para preparacion centralizada.'],
    ['family' => 'Antimetabolitos', 'name' => 'Fluorouracilo 5-FU', 'unit' => 'mg', 'use' => 'Antimetabolito para infusion o bolo segun protocolo.'],
    ['family' => 'Antimetabolitos', 'name' => 'Metotrexato', 'unit' => 'mg', 'use' => 'Antimetabolito con dosis indicada por el medico.'],
    ['family' => 'Antimetabolitos', 'name' => 'Gemcitabina', 'unit' => 'mg', 'use' => 'Antimetabolito para protocolos oncologicos.'],
  ]);

  $importFiles = collect([
    ['folio' => 'IMP-20260607-003', 'patient' => 'Ximena Sofia Martinez Perez', 'medication' => 'Cannabidiol solucion oral', 'detail' => '100 mg/ml', 'stage' => 'En documentacion', 'responsible' => 'Area administrativa', 'progress' => 11],
    ['folio' => 'IMP-20260611-001', 'patient' => 'Claudia Beatriz Salinas Vega', 'medication' => 'Tocilizumab', 'detail' => '162 mg solucion inyectable', 'stage' => 'Permiso Cofepris', 'responsible' => 'Gestion de Cofepris', 'progress' => 44],
    ['folio' => 'IMP-20260609-002', 'patient' => 'Rafael Ortega Morales', 'medication' => 'Asfotasa alfa', 'detail' => '80 mg/0.8 ml', 'stage' => 'Aduana CDMX', 'responsible' => 'Operador logistico', 'progress' => 78],
  ]);

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $activeSection = $typeLabels[$type] ?? strtoupper($type);
?>

<?php $__env->startSection('content'); ?>
  <?php if($type === 'import'): ?>
    <div class="provider-import-native-screen">
      <aside class="provider-import-native-sidebar" aria-label="Navegacion proveedor importacion">
        <div class="provider-import-native-brand">
          <span>I</span>
          <div>
            <strong>Proveedor Importacion</strong>
            <small>Medicamentos internacionales</small>
          </div>
        </div>
        <nav class="provider-import-native-menu">
          <a class="is-active" href="<?php echo e(route('provider.import.dashboard')); ?>"><span>T</span>Tablero</a>
          <a href="<?php echo e(route('provider.import.dashboard')); ?>"><span>D</span>Dashboard de Entrega</a>
          <a href="<?php echo e(route('provider.import.dashboard')); ?>"><span>E</span>Expedientes</a>
          <a href="<?php echo e(route('provider.import.dashboard')); ?>"><span>S</span>Seguimiento</a>
          <a href="<?php echo e(route('provider.import.dashboard')); ?>"><span>A</span>Area Administrativa</a>
          <a href="<?php echo e(route('provider.import.dashboard')); ?>"><span>P</span>Proveedor Extranjero</a>
          <a href="<?php echo e(route('provider.import.dashboard')); ?>"><span>C</span>Gestion Cofepris</a>
          <a href="<?php echo e(route('provider.import.dashboard')); ?>"><span>O</span>Operador Logistico</a>
          <a href="<?php echo e(route('provider.import.dashboard')); ?>"><span>DO</span>Documentos</a>
          <a href="<?php echo e(route('provider.import.dashboard')); ?>"><span>R</span>Reportes</a>
        </nav>
      </aside>

      <section class="provider-import-native-workspace">
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

        <header class="provider-import-native-topbar">
          <div>
            <p class="eyebrow">Proveedor extranjero y tramite sanitario</p>
            <h1>Tablero de importacion</h1>
          </div>
          <div>
            <a href="<?php echo e(route('dashboard')); ?>">&larr; Atras</a>
            <time><?php echo e(now()->format('d M Y')); ?></time>
          </div>
        </header>

        <section class="provider-import-native-card">
          <div class="provider-import-native-heading">
            <div>
              <h2>Expedientes con prioridad operativa</h2>
              <p>Seguimiento de recetas, permisos, aduana y almacen.</p>
            </div>
            <button type="button">Nuevo expediente</button>
          </div>
          <div class="provider-import-native-table-scroll">
            <table class="provider-import-native-table">
              <thead>
                <tr>
                  <th>Folio</th>
                  <th>Paciente</th>
                  <th>Medicamento</th>
                  <th>Etapa</th>
                  <th>Responsable</th>
                  <th>Avance</th>
                  <th>Accion</th>
                </tr>
              </thead>
              <tbody>
                <?php $__currentLoopData = $importFiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <tr>
                    <td><strong><?php echo e($file['folio']); ?></strong></td>
                    <td><?php echo e($file['patient']); ?></td>
                    <td>
                      <strong><?php echo e($file['medication']); ?></strong>
                      <span><?php echo e($file['detail']); ?></span>
                    </td>
                    <td><span class="provider-import-native-stage"><?php echo e($file['stage']); ?></span></td>
                    <td><?php echo e($file['responsible']); ?></td>
                    <td>
                      <strong><?php echo e($file['progress']); ?>%</strong>
                      <span class="provider-import-native-progress"><i style="width: <?php echo e($file['progress']); ?>%"></i></span>
                    </td>
                    <td><button type="button">Abrir</button></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="provider-import-native-card">
          <div class="provider-import-native-heading">
            <div>
              <h2>Solicitudes pendientes</h2>
              <p><?php echo e($requests->total()); ?> solicitudes asignadas al proveedor.</p>
            </div>
          </div>
          <div class="provider-import-native-requests">
            <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <article>
                <strong><?php echo e($request->external_id ?? 'Solicitud '.$request->id); ?></strong>
                <span><?php echo e($request->patient?->full_name ?? 'Sin paciente'); ?> / <?php echo e($request->medicalUnit?->name ?? 'Sin unidad'); ?></span>
                <?php if(data_get($request->payload, 'prescription_code')): ?>
                  <small>Receta CE: <?php echo e(data_get($request->payload, 'prescription_code')); ?> · <?php echo e(collect(data_get($request->payload, 'prescription_items', []))->count()); ?> partida(s)</small>
                <?php endif; ?>
                <?php if(data_get($request->payload, 'diagnosis')): ?>
                  <small>Diagnostico: <?php echo e(data_get($request->payload, 'diagnosis')); ?></small>
                <?php endif; ?>
                <form method="post" action="<?php echo e(route('provider.requests.status', [$type, $request])); ?>">
                  <?php echo csrf_field(); ?>
                  <?php echo method_field('patch'); ?>
                  <select name="status">
                    <?php $__currentLoopData = ['accepted', 'preparing', 'in_route', 'delivered', 'rejected', 'cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <option value="<?php echo e($status); ?>" <?php if($request->status === $status): echo 'selected'; endif; ?>><?php echo e($statusText($status)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </select>
                  <input name="notes" placeholder="Nota del proveedor">
                  <button type="submit">Actualizar</button>
                </form>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p>No hay solicitudes para este proveedor.</p>
            <?php endif; ?>
          </div>
        </section>
      </section>
    </div>
  <?php elseif($type === 'chemotherapy'): ?>
    <div class="provider-chemo-native-screen">
      <aside class="provider-chemo-native-sidebar" aria-label="Navegacion proveedor quimioterapias">
        <div class="provider-chemo-native-brand">
          <span>Q</span>
          <div>
            <strong>Proveedor Quimioterapias</strong>
            <small>Central oncologica</small>
          </div>
        </div>
        <nav class="provider-chemo-native-menu">
          <a class="is-active" href="<?php echo e(route('provider.chemo.dashboard')); ?>"><span>M</span>Medicamentos</a>
          <a href="<?php echo e(route('provider.chemo.dashboard')); ?>"><span>H</span>Historial de Solicitudes</a>
          <a href="<?php echo e(route('provider.chemo.dashboard')); ?>"><span>R</span>Reportes</a>
        </nav>
      </aside>

      <section class="provider-chemo-native-workspace">
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

        <header class="provider-chemo-native-topbar">
          <div>
            <p class="eyebrow">Servicio medico integral</p>
            <h1>Catalogo de medicamentos general</h1>
          </div>
          <div>
            <a href="<?php echo e(route('dashboard')); ?>">&larr; Atras</a>
            <time><?php echo e(now()->format('d M Y')); ?></time>
          </div>
        </header>

        <div class="provider-chemo-native-tabs">
          <a class="is-active" href="<?php echo e(route('provider.chemo.dashboard')); ?>">Catalogo general</a>
          <a href="<?php echo e(route('provider.chemo.dashboard')); ?>">Catalogos especificos</a>
        </div>

        <form class="provider-chemo-native-filters">
          <label class="search">
            <span>Q</span>
            <input placeholder="Buscar medicamento, familia o uso">
          </label>
          <label>Familia
            <select>
              <option>Todas</option>
              <?php $__currentLoopData = $chemoCatalog->pluck('family')->unique(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $family): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option><?php echo e($family); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
        </form>

        <section class="provider-chemo-native-card">
          <div class="provider-chemo-native-heading">
            <div>
              <h2>Catalogo de medicamentos general</h2>
              <p><?php echo e($chemoCatalog->count()); ?> visibles Â· <?php echo e($chemoCatalog->count()); ?> activos</p>
            </div>
            <button type="button">Ver Formato de Solicitud</button>
          </div>
          <div class="provider-chemo-native-table-scroll">
            <table class="provider-chemo-native-table">
              <thead>
                <tr>
                  <th>Activo</th>
                  <th>Estado</th>
                  <th>Familia</th>
                  <th>Medicamento</th>
                  <th>Unidad</th>
                  <th>Uso clinico / operativo</th>
                </tr>
              </thead>
              <tbody>
                <?php $__currentLoopData = $chemoCatalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <tr>
                    <td><span class="provider-chemo-native-check">âœ“</span></td>
                    <td><span class="provider-chemo-native-status">Activo</span></td>
                    <td><?php echo e($item['family']); ?></td>
                    <td><strong><?php echo e($item['name']); ?></strong></td>
                    <td><?php echo e($item['unit']); ?></td>
                    <td><?php echo e($item['use']); ?></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="provider-chemo-native-card">
          <div class="provider-chemo-native-heading">
            <div>
              <h2>Solicitudes pendientes</h2>
              <p><?php echo e($requests->total()); ?> solicitudes asignadas al proveedor.</p>
            </div>
            <span>Quimioterapia</span>
          </div>
          <div class="provider-chemo-native-requests">
            <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <article>
                <strong><?php echo e($request->external_id ?? 'Solicitud '.$request->id); ?></strong>
                <span><?php echo e($request->patient?->full_name ?? 'Sin paciente'); ?> / <?php echo e($request->medicalUnit?->name ?? 'Sin unidad'); ?></span>
                <?php if(data_get($request->payload, 'prescription_code')): ?>
                  <small>Receta CE: <?php echo e(data_get($request->payload, 'prescription_code')); ?> · <?php echo e(collect(data_get($request->payload, 'prescription_items', []))->count()); ?> partida(s)</small>
                <?php endif; ?>
                <?php if(data_get($request->payload, 'diagnosis')): ?>
                  <small>Diagnostico: <?php echo e(data_get($request->payload, 'diagnosis')); ?></small>
                <?php endif; ?>
                <form method="post" action="<?php echo e(route('provider.requests.status', [$type, $request])); ?>">
                  <?php echo csrf_field(); ?>
                  <?php echo method_field('patch'); ?>
                  <select name="status">
                    <?php $__currentLoopData = ['accepted', 'preparing', 'in_route', 'delivered', 'rejected', 'cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <option value="<?php echo e($status); ?>" <?php if($request->status === $status): echo 'selected'; endif; ?>><?php echo e($statusText($status)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </select>
                  <input name="notes" placeholder="Nota del proveedor">
                  <button type="submit">Actualizar</button>
                </form>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p>No hay solicitudes para este proveedor.</p>
            <?php endif; ?>
          </div>
        </section>
      </section>
    </div>
  <?php else: ?>
    <?php
      $activeProviderSection = request('section', 'history');
      $activeProviderScope = request('scope', 'integral');
      if (! in_array($activeProviderScope, ['integral', 'npt', 'oncology'], true)) {
          $activeProviderScope = 'integral';
      }
      $validProviderSections = ['history', 'pending', 'prices', 'messengers', 'shipments'];
      if (! in_array($activeProviderSection, $validProviderSections, true)) {
          $activeProviderSection = 'history';
      }

      $providerRows = collect([
          ['service' => 'Oncologica', 'folio' => 'QT-18', 'hospital' => 'Hospital General Norte', 'patient' => 'Torres Pineda, Mariana', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'Oncologica', 'folio' => 'QT-19', 'hospital' => 'Hospital General Norte', 'patient' => 'Castillo Vega, Roberto', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'Oncologica', 'folio' => 'QT-20', 'hospital' => 'Hospital General Norte', 'patient' => 'Flores Mendoza, Renata', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'Oncologica', 'folio' => 'QT-21', 'hospital' => 'Hospital General Norte', 'patient' => 'Sanchez Ruiz, Diego', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'Oncologica', 'folio' => 'QT-22', 'hospital' => 'Hospital General Norte', 'patient' => 'Rodriguez Luna, Camila', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'Oncologica', 'folio' => 'QT-23', 'hospital' => 'Hospital General Norte', 'patient' => 'Garcia Moreno, Valeria', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'NPT', 'folio' => 'NPT-17', 'hospital' => 'Hospital General Bajio', 'patient' => 'Hernandez, Arturo', 'doctor' => 'Carter Jimmy', 'auth' => ['Enfermeria', 'Farm. Intra.'], 'date' => '27/05/2026', 'obs' => 'Con Observacion', 'obs_state' => 'danger', 'central' => 'En Revision', 'central_state' => 'warning', 'delivery' => 'Pendiente', 'delivery_state' => 'muted'],
          ['service' => 'NPT', 'folio' => 'NPT-16', 'hospital' => 'Hospital General Norte', 'patient' => 'Hernandez Villanueva, Javier Alejandro', 'doctor' => 'Carter Jimmy', 'auth' => ['Enfermeria', 'Farm. Intra.'], 'date' => '27/05/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Entregada', 'delivery_state' => 'success'],
          ['service' => 'Oncologica', 'folio' => 'QT-17', 'hospital' => 'Centro de Alta Especialidad Regio', 'patient' => 'Hernandez, Arturo', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '27/05/2026', 'obs' => 'Con Observacion', 'obs_state' => 'success', 'central' => 'En Revision', 'central_state' => 'warning', 'delivery' => 'Pendiente', 'delivery_state' => 'muted'],
      ]);

      $requestRows = $requests->map(function ($request) use ($statusText, $type) {
          $centralStatus = $request->status === 'rejected'
              ? 'Rechazada'
              : (in_array($request->status, ['accepted', 'preparing', 'in_route', 'delivered'], true) ? 'Autorizada' : 'En Revision');
          $prescriptionItems = collect(data_get($request->payload, 'prescription_items', []));
          $diagnosis = data_get($request->payload, 'diagnosis');
          $prescriptionCode = data_get($request->payload, 'prescription_code');
          $serviceName = data_get($request->payload, 'service') ?: ($type === 'chemotherapy' ? 'Oncologica' : 'NPT');
          $observations = $request->status === 'rejected'
              ? 'Con Observacion'
              : trim(collect([$prescriptionCode ? 'Receta CE '.$prescriptionCode : null, $diagnosis, $prescriptionItems->count() ? $prescriptionItems->count().' partida(s)' : null])->filter()->join(' / '));

          return [
              'service' => str_contains(strtolower($serviceName), 'npt') || str_contains(strtolower($serviceName), 'nutric') ? 'NPT' : 'Oncologica',
              'folio' => $request->external_id ?? 'Solicitud '.$request->id,
              'hospital' => $request->medicalUnit?->name ?? 'Hospital sin unidad',
              'patient' => $request->patient?->full_name ?? 'Sin paciente',
              'doctor' => data_get($request->payload, 'doctor') ?: 'Sin medico',
              'auth' => $prescriptionCode ? ['Consulta Ext.', 'Central'] : ['Enfermeria', 'Farm. Intra.'],
              'date' => $request->requested_at?->format('d/m/Y') ?? 'Sin fecha',
              'obs' => $observations !== '' ? $observations : 'Sin Observacion',
              'obs_state' => $request->status === 'rejected' ? 'danger' : ($prescriptionCode || $diagnosis ? 'success' : 'neutral'),
              'central' => $centralStatus,
              'central_state' => match ($centralStatus) {
                  'Autorizada' => 'success',
                  'Rechazada' => 'danger',
                  default => 'warning',
              },
              'delivery' => in_array($request->status, ['delivered'], true) ? 'Entregada' : 'Pendiente',
              'delivery_state' => in_array($request->status, ['delivered'], true) ? 'success' : 'muted',
              'request_id' => $request->id,
              'detail' => data_get($request->payload, 'clinical_summary') ?: data_get($request->payload, 'diagnosis') ?: 'Solicitud registrada en la central de mezclas.',
              'location_url' => data_get($request->payload, 'location_url'),
              'remission' => data_get($request->payload, 'remission') ?: 'Remision pendiente de carga.',
          ];
      });

      $providerRows = $providerRows->map(fn ($row) => array_merge([
          'request_id' => null,
          'detail' => $row['obs'] === 'Sin Observacion' ? 'La solicitud no tiene observaciones registradas.' : 'La solicitud requiere revision antes de continuar.',
          'location_url' => 'https://www.google.com/maps/search/?api=1&query=19.4125,-99.1528',
          'remission' => $row['delivery'] === 'Entregada' ? 'Remision de entrega registrada y confirmada.' : 'Remision pendiente de entrega.',
      ], $row));

      $allProviderRows = $requestRows->concat($providerRows);
      $pendingProviderRows = $allProviderRows->filter(fn ($row) => $row['central'] !== 'Autorizada' || $row['delivery'] !== 'Entregada')->values();

    $scopedProviderRows = match ($activeProviderScope) {
        'npt' => $allProviderRows->where('service', 'NPT')->values(),
        'oncology' => $allProviderRows->where('service', 'Oncologica')->values(),
        default => $allProviderRows,
    };
    $scopedPendingProviderRows = $scopedProviderRows->filter(fn ($row) => $row['central'] !== 'Autorizada' || $row['delivery'] !== 'Entregada')->values();
    $displayProviderRows = $activeProviderSection === 'history' ? $scopedProviderRows : $scopedPendingProviderRows;
?>

    <div class="provider-native-screen">
      <aside class="provider-native-sidebar" aria-label="Navegacion proveedor">
        <div class="provider-native-brand">
          <span>P</span>
          <div>
            <strong>Proveedor Integral</strong>
            <small>Central de mezclas NPT y oncologicas</small>
          </div>
        </div>

        <nav class="provider-native-menu">
          <p>Vista integral</p>
          <strong>NPT y Oncologicas</strong>
          <a class="<?php echo e($activeProviderScope === 'integral' && $activeProviderSection === 'history' ? 'is-active' : ''); ?>" href="<?php echo e(route('provider.npt.dashboard', ['section' => 'history', 'scope' => 'integral'])); ?>"><span>H</span>Historial de Solicitudes</a>
          <a class="<?php echo e($activeProviderScope === 'integral' && $activeProviderSection === 'pending' ? 'is-active' : ''); ?>" href="<?php echo e(route('provider.npt.dashboard', ['section' => 'pending', 'scope' => 'integral'])); ?>"><span>*</span>Solicitudes Pendientes</a>
          <a class="<?php echo e($activeProviderSection === 'prices' ? 'is-active' : ''); ?>" href="<?php echo e(route('provider.npt.dashboard', ['section' => 'prices'])); ?>"><span>=</span>Catalogo y Lista de Precios</a>
          <a class="<?php echo e($activeProviderSection === 'messengers' ? 'is-active' : ''); ?>" href="<?php echo e(route('provider.npt.dashboard', ['section' => 'messengers'])); ?>"><span>Cb</span>Mensajeros</a>
          <a class="<?php echo e($activeProviderSection === 'shipments' ? 'is-active' : ''); ?>" href="<?php echo e(route('provider.npt.dashboard', ['section' => 'shipments'])); ?>"><span>Cb</span>Envios</a>

          <div class="provider-native-menu-divider"></div>
          <p>Nutricion parenteral</p>
          <strong>NPT</strong>
          <a class="<?php echo e($activeProviderScope === 'npt' && $activeProviderSection === 'history' ? 'is-active' : ''); ?>" href="<?php echo e(route('provider.npt.dashboard', ['section' => 'history', 'scope' => 'npt'])); ?>"><span>H</span>Historial de Solicitudes</a>
          <a class="<?php echo e($activeProviderScope === 'npt' && $activeProviderSection === 'pending' ? 'is-active' : ''); ?>" href="<?php echo e(route('provider.npt.dashboard', ['section' => 'pending', 'scope' => 'npt'])); ?>"><span>*</span>Solicitudes Pendientes</a>

          <div class="provider-native-menu-divider"></div>
          <p>Quimioterapia</p>
          <strong>Oncologicas</strong>
          <a class="<?php echo e($activeProviderScope === 'oncology' && $activeProviderSection === 'history' ? 'is-active' : ''); ?>" href="<?php echo e(route('provider.npt.dashboard', ['section' => 'history', 'scope' => 'oncology'])); ?>"><span>H</span>Historial de Solicitudes</a>
          <a class="<?php echo e($activeProviderScope === 'oncology' && $activeProviderSection === 'pending' ? 'is-active' : ''); ?>" href="<?php echo e(route('provider.npt.dashboard', ['section' => 'pending', 'scope' => 'oncology'])); ?>"><span>*</span>Solicitudes Pendientes</a>

          <div class="provider-native-menu-divider"></div>
          <a href="<?php echo e(route('provider.npt.dashboard', ['section' => 'history'])); ?>"><span>R</span>Reportes</a>
        </nav>
      </aside>

      <section class="provider-native-workspace">
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

        <header class="provider-native-topbar">
          <div>
            <p class="eyebrow">
              <?php if($activeProviderSection === 'messengers'): ?>
                Proveedor integral / Mensajeros
              <?php elseif($activeProviderSection === 'prices' || $activeProviderSection === 'shipments'): ?>
                Vista integral / NPT y Oncologicas
              <?php else: ?>
                <?php echo e($activeProviderScope === 'npt' ? 'Proveedor NPT / Central de mezclas' : ($activeProviderScope === 'oncology' ? 'Proveedor Oncologico / Central de mezclas' : 'Proveedor integral / Central de mezclas NPT y Oncologicas')); ?>

              <?php endif; ?>
            </p>
            <h1>
              <?php if($activeProviderSection === 'prices'): ?>
                Catalogo y Lista de Precios
              <?php elseif($activeProviderSection === 'messengers'): ?>
                Mensajeros
              <?php elseif($activeProviderSection === 'shipments'): ?>
                Envios
              <?php else: ?>
                <?php echo e($activeProviderScope === 'npt' ? 'Historial de Solicitudes' : ($activeProviderScope === 'oncology' ? 'Historial de Solicitudes Oncologicas' : 'NPT y Oncologicas')); ?>

              <?php endif; ?>
            </h1>
          </div>
          <div class="provider-native-actions">
            <a href="<?php echo e(route('dashboard')); ?>">&larr; Atras</a>
            <time><?php echo e(now()->locale('es')->translatedFormat('d \d\e F \d\e Y')); ?></time>
          </div>
        </header>

        <?php if(in_array($activeProviderSection, ['history', 'pending'], true)): ?>
          <div class="provider-native-history-page-heading">
            <div>
              <h2><?php echo e($activeProviderScope === 'npt' ? 'Historial de Solicitudes Nutricion Parenteral' : ($activeProviderScope === 'oncology' ? 'Historial de Solicitudes Oncologicas' : 'NPT y Oncologicas')); ?></h2>
              <p><?php echo e($activeProviderScope === 'npt' ? 'Solicitudes de nutricion parenteral enviadas por hospitales' : ($activeProviderScope === 'oncology' ? 'Solicitudes oncologicas enviadas por hospitales' : 'Vista integral de solicitudes de Nutricion Parenteral y Oncologia')); ?></p>
            </div>
            <div class="provider-native-kpi-pills">
              <?php if($activeProviderScope === 'npt'): ?>
                <a class="provider-native-area-link" href="<?php echo e(route('operational.dashboard', ['area' => 'nursing', 'section' => 'history'])); ?>">↗ Area Operativa</a>
              <?php endif; ?>
              <span class="provider-native-count-pill"><?php echo e($scopedProviderRows->count()); ?> solicitudes</span>
              <span class="provider-native-count-pill"><?php echo e($scopedPendingProviderRows->count()); ?> <?php echo e($activeProviderScope === 'npt' ? 'en revision' : 'pendientes'); ?></span>
            </div>
          </div>

          <section class="provider-native-card">
            <div class="provider-native-heading">
              <div>
                <h2><?php echo e($activeProviderSection === 'history' ? ($activeProviderScope === 'npt' ? 'Historial de Solicitudes Nutricion Parenteral' : 'Historial de Solicitudes') : 'Solicitudes Pendientes'); ?></h2>
                <p><?php echo e($activeProviderSection === 'history' ? ($activeProviderScope === 'npt' ? 'Solicitudes de nutricion parenteral enviadas por hospitales' : 'Todas las solicitudes NPT y Oncologicas') : 'Solicitudes que siguen en revision por central'); ?></p>
              </div>
            </div>
                        <div class="provider-native-table-wrap">
                            <table class="provider-native-table">
                                <thead>
                                    <tr>
                                        <?php if($activeProviderScope === 'integral'): ?><th>Servicio</th><?php endif; ?>
                                        <th>Folio</th>
                                        <th>Hospital</th>
                                        <th>Paciente</th>
                                        <th>Medico</th>
                                        <th>Autorizaciones</th>
                                        <th>Fecha</th>
                                        <th>Observaciones</th>
                                        <th>Estado de Central</th>
                                        <?php if($activeProviderScope === 'integral'): ?><th>Estatus de Entrega</th><?php endif; ?>
                                        <th>Visualizar Solicitud</th>
                                        <th>Ubicacion</th>
                                        <th>Remision de entrega</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $displayProviderRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                          $centralEnabled = collect($row['auth'])->every(fn ($auth) => ! str_contains($auth, 'Pendiente'));
                                          $rowKey = \Illuminate\Support\Str::slug($row['folio']);
                                        ?>
                                        <tr data-provider-request-row="<?php echo e($rowKey); ?>">
                                            <?php if($activeProviderScope === 'integral'): ?><td><span class="provider-native-service <?php echo e($row['service'] === 'NPT' ? 'is-npt' : 'is-oncology'); ?>"><?php echo e($row['service']); ?></span></td><?php endif; ?>
                                            <td><strong><?php echo e($row['folio']); ?></strong></td>
                                            <td><strong><?php echo e($row['hospital']); ?></strong></td>
                                            <td><strong><?php echo e($row['patient']); ?></strong></td>
                                            <td><?php echo e($row['doctor']); ?></td>
                                            <td>
                                                <?php $__currentLoopData = $row['auth']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $auth): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <span class="provider-native-mini-badge <?php echo e(str_contains($auth, 'Farm') ? 'is-warning' : 'is-success'); ?>"><?php echo e($auth); ?></span>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </td>
                                            <td><?php echo e($row['date']); ?></td>
                                            <td><button class="provider-native-observation is-<?php echo e($row['obs_state']); ?>" type="button" data-provider-dialog="observation-<?php echo e($rowKey); ?>"><?php echo e($row['obs']); ?></button></td>
                                            <td>
                                              <?php if($row['request_id'] && $centralEnabled): ?>
                                                <form method="post" action="<?php echo e(route('provider.requests.status', [$type, $row['request_id']])); ?>" data-provider-central-form>
                                                  <?php echo csrf_field(); ?>
                                                  <?php echo method_field('PATCH'); ?>
                                                  <select class="provider-native-central-select is-<?php echo e($row['central_state']); ?>" name="status" aria-label="Estado de Central <?php echo e($row['folio']); ?>">
                                                    <option class="is-warning" value="requested" <?php if($row['central'] === 'En Revision'): echo 'selected'; endif; ?>>En Revision</option>
                                                    <option class="is-success" value="accepted" <?php if($row['central'] === 'Autorizada'): echo 'selected'; endif; ?>>Autorizada</option>
                                                    <option class="is-danger" value="rejected" <?php if($row['central'] === 'Rechazada'): echo 'selected'; endif; ?>>Rechazada</option>
                                                  </select>
                                                </form>
                                              <?php else: ?>
                                                <select class="provider-native-central-select is-<?php echo e($row['central_state']); ?>" aria-label="Estado de Central <?php echo e($row['folio']); ?>" <?php if(! $centralEnabled): echo 'disabled'; endif; ?>>
                                                  <option class="is-warning" <?php if($row['central'] === 'En Revision'): echo 'selected'; endif; ?>>En Revision</option>
                                                  <option class="is-success" <?php if($row['central'] === 'Autorizada'): echo 'selected'; endif; ?>>Autorizada</option>
                                                  <option class="is-danger" <?php if($row['central'] === 'Rechazada'): echo 'selected'; endif; ?>>Rechazada</option>
                                                </select>
                                              <?php endif; ?>
                                            </td>
                                            <?php if($activeProviderScope === 'integral'): ?><td><span class="provider-native-status-pill is-<?php echo e($row['delivery_state']); ?>"><?php echo e($row['delivery']); ?></span></td><?php endif; ?>
                                            <td><button class="provider-native-cta" type="button" data-provider-dialog="request-<?php echo e($rowKey); ?>">Ver Formato de Solicitud <span>&rsaquo;</span></button></td>
                                            <td>
                                              <?php if($row['location_url']): ?>
                                                <a class="provider-native-view-button is-location" href="<?php echo e($row['location_url']); ?>" target="_blank" rel="noopener">○ Ver</a>
                                              <?php else: ?>
                                                <button class="provider-native-view-button is-location" type="button" disabled>○ Ver</button>
                                              <?php endif; ?>
                                            </td>
                                            <td><button class="provider-native-view-button is-green" type="button" data-provider-dialog="remission-<?php echo e($rowKey); ?>">○ Ver</button></td>
                                        </tr>
                                        <tr class="provider-native-dialog-row">
                                          <td colspan="<?php echo e($activeProviderScope === 'integral' ? 13 : 11); ?>">
                                            <dialog id="observation-<?php echo e($rowKey); ?>" class="provider-native-dialog">
                                              <header><strong>Observaciones · <?php echo e($row['folio']); ?></strong><button type="button" data-provider-dialog-close>Cerrar</button></header>
                                              <p><?php echo e($row['detail']); ?></p>
                                            </dialog>
                                            <dialog id="request-<?php echo e($rowKey); ?>" class="provider-native-dialog">
                                              <header><strong>Formato de solicitud · <?php echo e($row['folio']); ?></strong><button type="button" data-provider-dialog-close>Cerrar</button></header>
                                              <dl>
                                                <div><dt>Servicio</dt><dd><?php echo e($row['service']); ?></dd></div>
                                                <div><dt>Hospital</dt><dd><?php echo e($row['hospital']); ?></dd></div>
                                                <div><dt>Paciente</dt><dd><?php echo e($row['patient']); ?></dd></div>
                                                <div><dt>Medico</dt><dd><?php echo e($row['doctor']); ?></dd></div>
                                                <div><dt>Fecha</dt><dd><?php echo e($row['date']); ?></dd></div>
                                                <div><dt>Estado</dt><dd><?php echo e($row['central']); ?></dd></div>
                                              </dl>
                                              <p><?php echo e($row['detail']); ?></p>
                                            </dialog>
                                            <dialog id="remission-<?php echo e($rowKey); ?>" class="provider-native-dialog">
                                              <?php
                                                $isOncologyRemission = $row['service'] === 'Oncologica';
                                                $remissionProduct = $isOncologyRemission ? 'Docetaxel' : 'Mezcla de nutricion parenteral';
                                                $remissionQuantity = $isOncologyRemission ? '1 mg (1 frasco facturado)' : '1 bolsa (1 preparacion)';
                                                $remissionPriceType = $isOncologyRemission ? 'Precio unitario por frasco' : 'Precio unitario por mezcla';
                                                $remissionAmount = $isOncologyRemission ? '$100.00' : '$1,250.00';
                                              ?>
                                              <header class="provider-native-remission-header">
                                                <div>
                                                  <small>REMISION DE ENTREGA</small>
                                                  <strong>Remision de entrega <?php echo e($row['folio']); ?></strong>
                                                  <span><?php echo e($row['hospital']); ?> - <?php echo e($row['date']); ?> - <?php echo e($row['service']); ?></span>
                                                </div>
                                                <button type="button" data-provider-dialog-close>Cerrar</button>
                                              </header>
                                              <div class="provider-native-remission-body">
                                                <div class="provider-native-remission-summary">
                                                  <div>
                                                    <small><?php echo e(strtoupper($row['service'])); ?></small>
                                                    <strong>Productos entregados y precios</strong>
                                                    <span>Precio de referencia <?php echo e($row['service']); ?>: <?php echo e($remissionAmount); ?> por <?php echo e($isOncologyRemission ? 'frasco' : 'mezcla'); ?></span>
                                                  </div>
                                                  <span class="provider-native-remission-status"><?php echo e($row['central']); ?></span>
                                                </div>
                                                <dl class="provider-native-remission-data">
                                                  <div><dt>Folio</dt><dd><?php echo e($row['folio']); ?></dd></div>
                                                  <div><dt>Hospital</dt><dd><?php echo e($row['hospital']); ?></dd></div>
                                                  <div><dt>Paciente</dt><dd><?php echo e($row['patient']); ?></dd></div>
                                                  <div><dt>Medico</dt><dd><?php echo e($row['doctor']); ?></dd></div>
                                                  <div><dt>Fecha de entrega</dt><dd><?php echo e($row['date']); ?>, 10:40 a.m.</dd></div>
                                                  <div><dt>Total remision</dt><dd><?php echo e($remissionAmount); ?></dd></div>
                                                </dl>
                                                <div class="provider-native-remission-table-wrap">
                                                  <table class="provider-native-remission-table">
                                                    <thead>
                                                      <tr>
                                                        <th>Producto</th>
                                                        <th>Cantidad entregada</th>
                                                        <th>Tipo de precio</th>
                                                        <th>Precio unitario</th>
                                                        <th>Importe</th>
                                                      </tr>
                                                    </thead>
                                                    <tbody>
                                                      <tr>
                                                        <td><strong><?php echo e($remissionProduct); ?></strong><small>Precio de referencia del catalogo</small></td>
                                                        <td><?php echo e($remissionQuantity); ?></td>
                                                        <td><?php echo e($remissionPriceType); ?></td>
                                                        <td><?php echo e($remissionAmount); ?></td>
                                                        <td><?php echo e($remissionAmount); ?></td>
                                                      </tr>
                                                    </tbody>
                                                    <tfoot>
                                                      <tr><th colspan="4">Subtotal</th><td><?php echo e($remissionAmount); ?></td></tr>
                                                      <tr><th colspan="4">Total</th><td><?php echo e($remissionAmount); ?></td></tr>
                                                    </tfoot>
                                                  </table>
                                                </div>
                                                <p class="provider-native-remission-note">Hay productos sin precio en la lista asignada. Se uso el precio de referencia del catalogo para mantener la remision visible.</p>
                                                <div class="provider-native-remission-signatures">
                                                  <span>Entrega proveedor integral</span>
                                                  <span>Recibe area operativa</span>
                                                </div>
                                              </div>
                                            </dialog>
                                          </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
          </section>
        <?php elseif($activeProviderSection === 'prices'): ?>
          <section class="provider-native-card">
            <div class="provider-native-heading">
              <div>
                <h2>Catalogo y Lista de Precios</h2>
                <p>Catalogos generales y listas asignadas por institucion o unidad</p>
              </div>
            </div>
            <div class="provider-native-catalogs">
              <article class="is-selected">
                <span>N</span>
                <div>
                  <strong>Catalogo NPT</strong>
                  <p>Administrar catalogo general de Nutricion Parenteral</p>
                </div>
              </article>
              <article>
                <span>O</span>
                <div>
                  <strong>Catalogo Oncologicas</strong>
                  <p>Administrar catalogo general de medicamentos de oncologia</p>
                </div>
              </article>
            </div>
          </section>

          <section class="provider-native-card provider-native-price-card">
            <div class="provider-native-heading">
              <div>
                <h2>Listas de precios</h2>
                <p>Cada lista se relaciona con un catalogo y puede asignarse a instituciones o unidades.</p>
              </div>
              <div class="provider-native-price-actions">
                <label>Ver listas de precios
                  <select>
                    <option>Todas</option>
                    <option>NPT</option>
                    <option>Oncologicas</option>
                  </select>
                </label>
                <button type="button">Crear lista de precios</button>
                <button type="button">Importar lista de precios</button>
              </div>
            </div>
            <div class="provider-native-empty">
              Aun no hay listas de precios creadas.
            </div>
          </section>
        <?php elseif($activeProviderSection === 'messengers'): ?>
          <?php
            $providerMessengers = $messengers->map(function ($profile) {
              $name = $profile->user?->name ?: 'Mensajero sin usuario';
              $parts = preg_split('/\s+/', trim($name), 2);
              $routes = $profile->deliveryRoutes->map(function ($route) {
                return [
                  'number' => $route->route_code ?: (string) $route->id,
                  'name' => $route->destination ?: 'Ruta sin destino',
                  'time' => $route->scheduled_at?->format('H:i') ?: 'Horario pendiente',
                  'pending' => in_array($route->status, ['delivered', 'cancelled'], true) ? 0 : 1,
                  'units' => $route->providerRequest?->medical_unit_id ? 1 : 0,
                ];
              })->values()->all();

              return [
                'initials' => collect($parts)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join(''),
                'name' => $name,
                'status' => match ($profile->status) {
                  'active' => 'Disponible',
                  'in_route' => 'En ruta',
                  default => ucfirst(str_replace('_', ' ', $profile->status)),
                },
                'tone' => $profile->status === 'active' ? 'success' : 'danger',
                'routes' => $profile->deliveryRoutes->map(fn ($route) => trim(($route->route_code ?: 'Ruta').' - '.($route->destination ?: 'Destino pendiente')))->join(', ') ?: 'Sin rutas activas asignadas',
                'first' => $parts[0] ?? $name,
                'last' => $parts[1] ?? '',
                'unit' => $profile->vehicle ?: 'Unidad no registrada',
                'shift' => data_get($profile->metadata, 'shift', 'Sin turno registrado'),
                'phone' => $profile->phone ?: 'Sin telefono registrado',
                'count' => $profile->deliveryRoutes->count().' ruta(s) asignada(s)',
                'route_rows' => $routes,
              ];
            });
          ?>
          <section class="provider-native-card" data-provider-messenger-list>
            <div class="provider-native-heading">
              <div>
                <h2>Mensajeros</h2>
                <p>Gestion de mensajeros, rutas asignadas y entregas por turno.</p>
              </div>
              <button type="button">Dar de alta mensajero</button>
            </div>
            <div class="provider-native-info-banner">La ruta puede cambiar dependiendo de la demanda.</div>
            <div class="provider-native-messenger-grid">
              <?php $__currentLoopData = $providerMessengers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $messengerIndex => $messenger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <article class="provider-native-messenger-card">
                  <span class="provider-native-avatar is-<?php echo e($messenger['tone']); ?>"><?php echo e($messenger['initials']); ?></span>
                  <div class="provider-native-messenger-body">
                    <strong><?php echo e($messenger['name']); ?></strong>
                    <em><?php echo e($messenger['status']); ?></em>
                    <hr>
                    <p>Rutas asignadas</p>
                    <b><?php echo e($messenger['routes']); ?></b>
                    <small>Nombre: <?php echo e($messenger['first']); ?></small>
                    <small>Apellido: <?php echo e($messenger['last']); ?></small>
                    <small>Unidad que maneja: <?php echo e($messenger['unit']); ?></small>
                    <small>Turno asignado: <?php echo e($messenger['shift']); ?></small>
                    <small>Telefono: <?php echo e($messenger['phone']); ?></small>
                    <small><?php echo e($messenger['count']); ?></small>
                    <div class="provider-native-card-actions">
                      <button type="button" data-provider-messenger-open="<?php echo e($messengerIndex); ?>">Ver</button>
                      <button type="button">Editar</button>
                    </div>
                  </div>
                </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          </section>
          <?php $__currentLoopData = $providerMessengers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $messengerIndex => $messenger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
              $messengerRoutes = $messenger['route_rows'];
            ?>
            <section class="provider-native-messenger-detail" data-provider-messenger-detail="<?php echo e($messengerIndex); ?>" hidden>
              <header class="provider-native-messenger-detail-hero">
                <button type="button" data-provider-messenger-back>&larr; Regresar</button>
                <span class="provider-native-avatar is-<?php echo e($messenger['tone']); ?>"><?php echo e($messenger['initials']); ?></span>
                <div>
                  <small>INFORMACION DEL MENSAJERO</small>
                  <strong><?php echo e($messenger['name']); ?></strong>
                  <p><?php echo e($messenger['phone']); ?> · <?php echo e($messenger['shift']); ?> · <?php echo e($messenger['unit']); ?></p>
                </div>
                <aside><small>RUTAS ASIGNADAS</small><strong><?php echo e($messenger['routes']); ?></strong></aside>
                <button type="button">Editar informacion</button>
              </header>

              <section class="provider-native-messenger-detail-card">
                <header><strong>Datos generales</strong><small>Informacion administrativa del mensajero.</small></header>
                <dl>
                  <div><dt>Nombre</dt><dd><?php echo e($messenger['first']); ?></dd></div>
                  <div><dt>Apellido</dt><dd><?php echo e($messenger['last']); ?></dd></div>
                  <div><dt>Telefono</dt><dd><?php echo e($messenger['phone']); ?></dd></div>
                  <div><dt>Unidad que maneja</dt><dd><?php echo e($messenger['unit']); ?></dd></div>
                  <div><dt>Turno asignado</dt><dd><?php echo e($messenger['shift']); ?></dd></div>
                  <div><dt>Ruta base</dt><dd><?php echo e(data_get($messengerRoutes, '0.name', 'Sin ruta base')); ?></dd></div>
                  <div><dt>Estado</dt><dd><?php echo e($messenger['status']); ?></dd></div>
                </dl>
              </section>

              <section class="provider-native-messenger-detail-card">
                <header><strong>Rutas asignadas</strong><small>Rutas activas relacionadas al mensajero desde Envios.</small></header>
                <?php $__currentLoopData = $messengerRoutes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $routeIndex => $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <article class="provider-native-assigned-route">
                    <div class="provider-native-assigned-route-heading">
                      <div>
                        <small>RUTA NO. <?php echo e($route['number']); ?></small>
                        <strong><?php echo e($route['name']); ?></strong>
                        <p><?php echo e($route['time']); ?> - <?php echo e($route['pending']); ?> solicitudes pendientes</p>
                        <span>NUMERO DE RUTA: <?php echo e($route['number']); ?></span>
                        <span>QR: QR-RUTA-<?php echo e($route['number']); ?></span>
                        <span><?php echo e($route['units']); ?> unidades asignadas</span>
                      </div>
                      <button type="button" <?php if($route['pending'] === 0): echo 'disabled'; endif; ?>>Comenzar ruta</button>
                    </div>
                    <table>
                      <thead><tr><th>Cliente</th><th>Hospital</th><th>Direccion</th><th>Paciente</th><th>Entrega</th></tr></thead>
                      <tbody>
                        <?php if($route['pending'] === 0): ?>
                          <tr><td colspan="5" class="provider-native-route-empty">Sin solicitudes pendientes para esta ruta.</td></tr>
                        <?php else: ?>
                          <?php $__currentLoopData = $displayProviderRows->take(min($route['pending'], 6)); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                              <td><strong>IMSS Bienestar Estado de Mexico</strong><small><?php echo e($delivery['folio']); ?></small></td>
                              <td><?php echo e($delivery['hospital']); ?></td>
                              <td>Entrada de proveedores, <?php echo e($delivery['hospital']); ?>, Ciudad de Mexico</td>
                              <td><?php echo e($delivery['patient']); ?></td>
                              <td><span>Enviada</span></td>
                            </tr>
                          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </section>
            </section>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php elseif($activeProviderSection === 'shipments'): ?>
          <?php
            $providerShipmentRoutes = $deliveryRoutes->map(function ($route) {
              $routeNumber = $route->route_code ?: (string) $route->id;
              $pending = in_array($route->status, ['delivered', 'cancelled'], true) ? 0 : 1;

              return [
                'id' => $route->id,
                'status' => $route->status,
                'name' => $route->destination ?: 'Ruta sin destino',
                'badge' => 'Ruta No. '.$routeNumber,
                'number' => $routeNumber,
                'subtitle' => $route->origin ?: 'Origen pendiente',
                'units' => $route->providerRequest?->medical_unit_id ? '1' : '0',
                'mixes' => (string) $pending,
                'pending' => (string) $pending,
                'schedule' => $route->scheduled_at?->format('H:i') ?: 'Pendiente de asignacion',
                'messenger' => $route->messenger?->user?->name ?: 'Sin asignar',
              ];
            });
          ?>
          <section class="provider-native-card">
            <div class="provider-native-heading">
              <div>
                <h2>Envios</h2>
                <p>Solicitudes en proceso de entrega y gestion de rutas diarias.</p>
              </div>
              <button type="button">Historial de Entregas</button>
            </div>
          </section>

          <section class="provider-native-card">
            <div data-provider-shipment-list>
            <div class="provider-native-heading">
              <div>
                <h2>Solicitudes en proceso de entrega</h2>
                <p>Solicitudes autorizadas por central listas para asignacion de ruta.</p>
              </div>
              <button type="button">Catalogo de Rutas</button>
            </div>
            <div class="provider-native-panel-toolbar">
              <label class="provider-native-select-block">Filtrar por institucion
                <select><option>Todas las instituciones</option></select>
              </label>
            </div>
            <div class="provider-native-routes-grid">
              <?php $__currentLoopData = $providerShipmentRoutes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $routeIndex => $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <article class="provider-native-route-card">
                  <div>
                    <span class="provider-native-route-icon">#</span>
                    <span class="provider-native-route-badge"><?php echo e($route['badge']); ?></span>
                  </div>
                  <h3><?php echo e($route['name']); ?></h3>
                  <p><?php echo e($route['subtitle']); ?></p>
                  <div class="provider-native-route-stats">
                    <span><small>Unidades</small><b><?php echo e($route['units']); ?></b></span>
                    <span><small>Mezclas totales del dia</small><b><?php echo e($route['mixes']); ?></b></span>
                    <span><small>Pendientes de entregar</small><b><?php echo e($route['pending']); ?></b></span>
                    <span><small>Horario</small><b><?php echo e($route['schedule']); ?></b></span>
                    <span><small>Mensajero</small><b><?php echo e($route['messenger']); ?></b></span>
                  </div>
                  <button class="provider-native-route-button" type="button" data-provider-shipment-open="<?php echo e($routeIndex); ?>">Abrir detalle</button>
                </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            </div>

            <?php $__currentLoopData = $providerShipmentRoutes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $routeIndex => $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="provider-native-shipment-detail" data-provider-shipment-detail="<?php echo e($routeIndex); ?>" hidden>
                <header>
                  <div>
                    <small>DETALLE DE RUTA</small>
                    <strong>Solicitudes pendientes de entregar</strong>
                    <p>Listado de solicitudes relacionadas a la ruta seleccionada.</p>
                  </div>
                  <button type="button" data-provider-shipment-back>&larr; Regresar</button>
                </header>
                <article class="provider-native-shipment-route-summary">
                  <div class="provider-native-route-card">
                    <div><span class="provider-native-route-icon">▦</span><span class="provider-native-route-badge"><?php echo e($route['badge']); ?></span></div>
                    <h3><?php echo e($route['name']); ?></h3>
                    <p><?php echo e($route['subtitle']); ?></p>
                    <div class="provider-native-route-stats">
                      <span><small>Unidades</small><b><?php echo e($route['units']); ?></b></span>
                      <span><small>Mezclas totales del dia</small><b><?php echo e($route['mixes']); ?></b></span>
                      <span><small>Pendientes de entregar</small><b><?php echo e($route['pending']); ?></b></span>
                      <span><small>Horario</small><b><?php echo e($route['schedule']); ?></b></span>
                      <span><small>Mensajero</small><b><?php echo e($route['messenger']); ?></b></span>
                    </div>
                  </div>
                </article>
                <section class="provider-native-shipment-pending">
                  <header>
                    <div><strong>Solicitudes pendientes de entregar</strong><small>Mezclas autorizadas por central que aun no tienen confirmacion de entrega.</small></div>
                    <?php if((int) $route['pending'] > 0): ?>
                      <form method="post" action="<?php echo e(route('provider.delivery-routes.status', [$type, $route['id']])); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        <input type="hidden" name="status" value="<?php echo e($route['status'] === 'in_route' ? 'delivered' : 'in_route'); ?>">
                        <input type="hidden" name="notes" value="<?php echo e($route['status'] === 'in_route' ? 'Entrega confirmada desde el portal del proveedor.' : 'Ruta enviada desde el portal del proveedor.'); ?>">
                        <button type="submit"><?php echo e($route['status'] === 'in_route' ? 'Confirmar entrega' : 'Enviar'); ?></button>
                      </form>
                    <?php else: ?>
                      <button type="button" disabled>Entregada</button>
                    <?php endif; ?>
                  </header>
                  <?php if((int) $route['pending'] === 0): ?>
                    <p>Sin solicitudes pendientes de entregar en esta ruta.</p>
                  <?php else: ?>
                    <table>
                      <thead><tr><th>Folio</th><th>Hospital</th><th>Paciente</th><th>Servicio</th><th>Estado</th></tr></thead>
                      <tbody>
                        <?php $__currentLoopData = $displayProviderRows->take(min((int) $route['pending'], 6)); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                          <tr>
                            <td><strong><?php echo e($delivery['folio']); ?></strong></td>
                            <td><?php echo e($delivery['hospital']); ?></td>
                            <td><?php echo e($delivery['patient']); ?></td>
                            <td><?php echo e($delivery['service']); ?></td>
                            <td><span>Pendiente</span></td>
                          </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                      </tbody>
                    </table>
                  <?php endif; ?>
                </section>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </section>
        <?php endif; ?>
      </section>
    </div>
  <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const messengerList = document.querySelector('[data-provider-messenger-list]');
  const messengerDetails = document.querySelectorAll('[data-provider-messenger-detail]');

  document.querySelectorAll('[data-provider-messenger-open]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      if (messengerList) messengerList.hidden = true;
      messengerDetails.forEach((detail) => {
        detail.hidden = detail.dataset.providerMessengerDetail !== trigger.dataset.providerMessengerOpen;
      });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });

  document.querySelectorAll('[data-provider-messenger-back]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      messengerDetails.forEach((detail) => { detail.hidden = true; });
      if (messengerList) messengerList.hidden = false;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });

  const shipmentList = document.querySelector('[data-provider-shipment-list]');
  const shipmentDetails = document.querySelectorAll('[data-provider-shipment-detail]');

  document.querySelectorAll('[data-provider-shipment-open]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      if (shipmentList) shipmentList.hidden = true;
      shipmentDetails.forEach((detail) => {
        detail.hidden = detail.dataset.providerShipmentDetail !== trigger.dataset.providerShipmentOpen;
      });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });

  document.querySelectorAll('[data-provider-shipment-back]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      shipmentDetails.forEach((detail) => { detail.hidden = true; });
      if (shipmentList) shipmentList.hidden = false;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });

  document.querySelectorAll('[data-provider-dialog]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const dialog = document.getElementById(trigger.dataset.providerDialog);
      if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
    });
  });

  document.querySelectorAll('[data-provider-dialog-close]').forEach((trigger) => {
    trigger.addEventListener('click', () => trigger.closest('dialog')?.close());
  });

  document.querySelectorAll('.provider-native-dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) dialog.close();
    });
  });

  document.querySelectorAll('[data-provider-central-form] select').forEach((select) => {
    select.addEventListener('change', () => select.form.requestSubmit());
  });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', ['title' => $type === 'chemotherapy' ? 'Proveedor Quimioterapias' : ($type === 'import' ? 'Proveedor Importacion' : 'Proveedor Integral')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views/provider/dashboard.blade.php ENDPATH**/ ?>