<?php $__env->startSection('body_class', 'messenger-native-body'); ?>

<?php
  $statusLabels = [
    'planned' => 'Planeada',
    'assigned' => 'En curso',
    'picked_up' => 'Recolectada',
    'in_route' => 'En ruta',
    'delivered' => 'Entregada',
    'failed' => 'Fallida',
    'cancelled' => 'Cancelada',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $messengerName = $profile?->user?->name ?? auth()->user()?->name ?? 'Luis Hernandez';
  $messengerPhone = $profile?->phone ?? '55 2148 9300';
  $messengerShift = data_get($profile?->metadata, 'shift', 'Matutino');
  $routeCollection = $routes->getCollection();
  $hasRoutes = $routeCollection->isNotEmpty();
  $activeRoutes = $routeCollection->filter(fn ($route) => in_array($route->status, ['planned', 'assigned', 'picked_up', 'in_route'], true));
  $routeNames = $hasRoutes
    ? $routeCollection->pluck('route_code')->filter()->take(2)->implode(', ')
    : 'Ruta EdoMex Norte, Ruta CDMX Oncologica';
  $scannedToday = $recentReports->where('reported_at', '>=', now()->startOfDay())->count();

  $demoRoutes = collect([
    [
      'code' => 'Ruta EdoMex Norte',
      'qr' => 'QR-RUTA-EDOMEX-NORTE',
      'client' => 'IMSS Bienestar Estado de Mexico',
      'hospital' => 'Hospital Monica Pretelini / Hospital Nicolas San Juan',
      'schedule' => '08:00 - 15:00',
      'status' => 'Sin solicitudes enviadas para esta ruta.',
      'rows' => collect(),
    ],
    [
      'code' => 'Ruta CDMX Oncologica',
      'qr' => 'QR-RUTA-CDMX-ONCOLOGICA',
      'client' => 'IMSS Bienestar Estado de Mexico',
      'hospital' => 'Hospital General Norte',
      'schedule' => '09:00 - 14:00',
      'status' => null,
      'rows' => collect([
        ['client' => 'IMSS Bienestar Estado de Mexico', 'hospital' => 'Hospital General Norte', 'address' => 'Entrada de proveedores, Hospital General Norte, Ciudad de Mexico', 'patient' => 'Garcia Moreno, Valeria', 'folio' => 'QT-23'],
        ['client' => 'IMSS Bienestar Estado de Mexico', 'hospital' => 'Hospital General Norte', 'address' => 'Entrada de proveedores, Hospital General Norte, Ciudad de Mexico', 'patient' => 'Rodriguez Luna, Camila', 'folio' => 'QT-22'],
        ['client' => 'IMSS Bienestar Estado de Mexico', 'hospital' => 'Hospital General Norte', 'address' => 'Entrada de proveedores, Hospital General Norte, Ciudad de Mexico', 'patient' => 'Sanchez Ruiz, Diego', 'folio' => 'QT-21'],
        ['client' => 'IMSS Bienestar Estado de Mexico', 'hospital' => 'Hospital General Norte', 'address' => 'Entrada de proveedores, Hospital General Norte, Ciudad de Mexico', 'patient' => 'Flores Mendoza, Renata', 'folio' => 'QT-20'],
      ]),
    ],
  ]);
?>

<?php $__env->startSection('content'); ?>
  <div class="messenger-native-screen">
    <aside class="messenger-native-sidebar" aria-label="Navegacion mensajero">
      <div class="messenger-native-brand">
        <span>M</span>
        <div>
          <strong>Modulo Mensajero</strong>
          <small>Entregas proveedor NPT</small>
        </div>
      </div>

      <nav class="messenger-native-menu">
        <a class="is-active" href="<?php echo e(route('messenger.dashboard')); ?>"><span>D</span>Dashboard / Nueva ruta</a>
        <a href="<?php echo e(route('messenger.dashboard')); ?>"><span>E</span>Entregas en Curso</a>
      </nav>
    </aside>

    <main class="messenger-native-workspace">
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

      <header class="messenger-native-topbar">
        <div>
          <p class="eyebrow">Usuario mensajero</p>
          <h1>Dashboard / Nueva ruta</h1>
          <span><?php echo e($messengerName); ?> - <?php echo e($messengerPhone); ?> - <?php echo e($messengerShift); ?></span>
        </div>
        <a href="<?php echo e(route('dashboard')); ?>">&larr; Salir</a>
      </header>

      <section class="messenger-native-summary" aria-label="Resumen de mensajeria">
        <article class="messenger-native-wide-stat">
          <span>Ruta asignada</span>
          <strong><?php echo e($routeNames ?: 'Sin ruta asignada'); ?></strong>
        </article>
        <article>
          <span>Pedidos en curso</span>
          <strong><?php echo e(number_format($activeRoutes->count())); ?></strong>
        </article>
        <article>
          <span>Historial</span>
          <strong><?php echo e(number_format($recentReports->count())); ?></strong>
        </article>
        <article>
          <span>Escaneados hoy</span>
          <strong><?php echo e(number_format($scannedToday)); ?></strong>
        </article>
      </section>

      <section class="messenger-native-card messenger-native-scan">
        <div class="messenger-native-card-head">
          <div>
            <h2>Dashboard / Nueva ruta</h2>
            <p>Escanea el codigo QR de la ruta que vas a entregar.</p>
          </div>
        </div>
        <form>
          <label>Codigo QR de la Ruta</label>
          <div>
            <input value="<?php echo e($hasRoutes ? ($routeCollection->first()->route_code ?? 'QR-RUTA-ASIGNADA') : 'QR-RUTA-EDOMEX-NORTE'); ?>" aria-label="Codigo QR de la ruta">
            <button type="button">Escanear ruta</button>
          </div>
        </form>
      </section>

      <section class="messenger-native-route-grid">
        <?php if($hasRoutes): ?>
          <?php $__currentLoopData = $routeCollection; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="messenger-native-route-card">
              <div class="messenger-native-route-head">
                <div>
                  <span>Ruta asignada</span>
                  <h3><?php echo e($route->route_code ?? 'Ruta asignada'); ?></h3>
                  <p><?php echo e($route->origin ?? 'Origen pendiente'); ?></p>
                  <p><?php echo e($route->scheduled_at?->format('H:i') ?? 'Sin horario'); ?> - <?php echo e($route->destination ?? 'Destino pendiente'); ?></p>
                </div>
                <form method="post" action="<?php echo e(route('messenger.routes.status', $route)); ?>">
                  <?php echo csrf_field(); ?>
                  <?php echo method_field('patch'); ?>
                  <input type="hidden" name="status" value="in_route">
                  <input type="hidden" name="notes" value="Ruta iniciada desde tablero">
                  <button type="submit">Comenzar ruta</button>
                </form>
              </div>

              <div class="messenger-native-tags">
                <span>QR: <?php echo e($route->route_code ?? 'QR-RUTA'); ?></span>
                <span><?php echo e($route->destination ?? $route->patientOrder?->patient?->full_name ?? $route->providerRequest?->medicalUnit?->name ?? 'Destino pendiente'); ?></span>
              </div>

              <div class="messenger-native-table-wrap">
                <table class="messenger-native-table">
                  <thead>
                    <tr>
                      <th>Cliente</th>
                      <th>Hospital</th>
                      <th>Direccion</th>
                      <th>Paciente</th>
                      <th>Estatus</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>
                        <strong><?php echo e($route->providerRequest?->medicalUnit?->institution?->name ?? 'Farmacia Digital'); ?></strong>
                        <span><?php echo e($route->patientOrder?->order_number ?? $route->providerRequest?->external_id ?? 'Pedido asignado'); ?></span>
                      </td>
                      <td><?php echo e($route->providerRequest?->medicalUnit?->name ?? $route->origin ?? 'Farmacia Digital'); ?></td>
                      <td><?php echo e($route->destination ?? 'Destino pendiente'); ?></td>
                      <td><?php echo e($route->patientOrder?->patient?->full_name ?? $route->providerRequest?->patient?->full_name ?? 'Sin paciente asociado'); ?></td>
                      <td>
                        <form method="post" action="<?php echo e(route('messenger.routes.status', $route)); ?>">
                          <?php echo csrf_field(); ?>
                          <?php echo method_field('patch'); ?>
                          <select name="status">
                            <?php $__currentLoopData = ['planned', 'assigned', 'picked_up', 'in_route', 'delivered', 'failed', 'cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                              <option value="<?php echo e($status); ?>" <?php if($route->status === $status): echo 'selected'; endif; ?>><?php echo e($statusText($status)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                          </select>
                          <input name="notes" placeholder="Nota">
                          <button type="submit">Reportar</button>
                        </form>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </article>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
          <?php $__currentLoopData = $demoRoutes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $demoRoute): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="messenger-native-route-card">
              <div class="messenger-native-route-head">
                <div>
                  <span>Ruta asignada</span>
                  <h3><?php echo e($demoRoute['code']); ?></h3>
                  <p><?php echo e($demoRoute['client']); ?></p>
                  <p><?php echo e($demoRoute['schedule']); ?> - <?php echo e($demoRoute['rows']->count()); ?> solicitudes enviadas</p>
                </div>
                <button type="button">Comenzar ruta</button>
              </div>

              <div class="messenger-native-tags">
                <span>QR: <?php echo e($demoRoute['qr']); ?></span>
                <span><?php echo e($demoRoute['hospital']); ?></span>
              </div>

              <div class="messenger-native-table-wrap">
                <table class="messenger-native-table">
                  <thead>
                    <tr>
                      <th>Cliente</th>
                      <th>Hospital</th>
                      <th>Direccion</th>
                      <th>Paciente</th>
                      <th>Estatus</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $demoRoute['rows']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                      <tr>
                        <td><strong><?php echo e($row['client']); ?></strong><span><?php echo e($row['folio']); ?></span></td>
                        <td><?php echo e($row['hospital']); ?></td>
                        <td><?php echo e($row['address']); ?></td>
                        <td><?php echo e($row['patient']); ?></td>
                        <td><span class="messenger-native-status">En curso</span></td>
                      </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                      <tr>
                        <td colspan="5" class="messenger-native-empty"><?php echo e($demoRoute['status']); ?></td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </article>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
      </section>

      <section class="messenger-native-card messenger-native-history">
        <div class="messenger-native-card-head">
          <div>
            <h2>Historial reciente</h2>
            <p>Ultimos reportes y entregas registradas por el mensajero.</p>
          </div>
          <span><?php echo e($recentReports->count()); ?> registros</span>
        </div>
        <div class="messenger-native-history-list">
          <?php $__empty_1 = true; $__currentLoopData = $recentReports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div>
              <strong><?php echo e($report->route?->route_code ?? 'Ruta sin folio'); ?> / <?php echo e($statusText($report->status)); ?></strong>
              <span><?php echo e($report->route?->patientOrder?->patient?->full_name ?? 'Sin paciente asociado'); ?></span>
              <span><?php echo e($report->notes ?? 'Sin notas'); ?> / <?php echo e($report->reported_at?->format('d/m/Y H:i') ?? 'Sin fecha'); ?></span>
            </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p>Todavia no hay reportes de ruta.</p>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Modulo Mensajero'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\messenger\dashboard.blade.php ENDPATH**/ ?>