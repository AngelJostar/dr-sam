<?php $__env->startSection('body_class', 'digital-pharmacy-native-body'); ?>

<?php
  $statusLabels = [
    'active' => 'Activo',
    'available' => 'Disponible',
    'created' => 'En preparacion',
    'received' => 'En preparacion',
    'preparing' => 'En preparacion',
    'in_route' => 'En camino',
    'delivered' => 'Entregado',
    'cancelled' => 'Cancelado',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $section = $section ?? 'dashboard';
  $activeProducts = (int) ($metrics['Productos activos'] ?? 0);
  $openOrders = (int) ($metrics['Pedidos abiertos'] ?? 0);
  $deliveredOrders = (int) ($metrics['Pedidos entregados'] ?? 0);
  $missingProducts = max(0, $products->total() - $inventory->count());
  $productRows = ($allProducts ?? collect())->isNotEmpty() ? $allProducts : $products->getCollection();
  $routeRows = ($routes ?? collect())->values();
  $messengerRows = ($messengers ?? collect())->values();
  $deliveryReportRows = ($deliveryReports ?? collect())->values();
  $openOrderRows = $orders->getCollection()->filter(fn ($order) => ! in_array($order->status, ['delivered', 'cancelled'], true))->values();
  $deliveredOrderRows = $orders->getCollection()->filter(fn ($order) => $order->status === 'delivered')->values();
  $controlledRows = $productRows->filter(fn ($product) => $product->controlled)->values();
  $controlledRows = $controlledRows->isNotEmpty() ? $controlledRows : $productRows->filter(fn ($product) => $product->requires_prescription)->take(2)->values();
  $prescriptionRows = $productRows->filter(fn ($product) => $product->requires_prescription)->values();
  $prescriptionRows = $prescriptionRows->isNotEmpty() ? $prescriptionRows : $productRows->take(6)->values();
  $specialties = ($specialties ?? collect())->values();
  $missingRows = $productRows->map(function ($product) {
    $stock = (int) ($product->available_stock ?? 0);
    $minimum = max($stock + 6, 12);

    return ['product' => $product, 'stock' => $stock, 'minimum' => $minimum];
  })->filter(fn ($row) => $row['stock'] < $row['minimum'])->values();
  $promotionRows = $productRows->take(3);
  $recommendedRows = $productRows->skip(3)->take(9)->values();
  $sectionTitles = [
    'dashboard' => ['Operacion integral', 'Tablero de farmacia digital', 'Catalogos, inventario, envios y cumplimiento operativo.'],
    'patient-home' => ['Farmacia digital', 'Pagina de inicio paciente', 'Seleccion de productos para carruseles y promociones visibles en la portada.'],
    'catalog' => ['Catalogo maestro', 'Catalogo regulatorio de productos', 'Claves del sector salud, registro Cofepris, codigos de barras y estatus comercial.'],
    'specialties' => ['Catalogo de especialidades', 'Catalogo de Especialidades', 'Especialidades medicas disponibles para clasificacion y consulta.'],
    'inventory' => ['Existencias', 'Inventario por lote y almacen', 'Stock, caducidades, maximos, minimos, almacenes y temperatura de resguardo.'],
    'missing' => ['Abasto', 'Seguimiento de faltantes', 'Riesgo de desabasto, prioridad, reposicion y cierre operativo.'],
    'orders' => ['Ultima milla', 'Gestion de pedidos y envios', 'Preparacion, empaque, guia, ruta y avance de pedidos de farmacia digital.'],
    'messengers' => ['Reparto', 'Mensajeros', 'Alta de repartidores, perfiles de acceso y validacion de entregas por etiqueta QR.'],
    'traceability' => ['Auditoria', 'Trazabilidad de envios y medicamentos', 'Linea de tiempo por pedido, lote, usuario y evento operativo.'],
    'deliveries' => ['Entrega', 'Registros de entrega de medicamentos', 'Confirmacion de receptor, evidencia operativa y condiciones de entrega.'],
    'cold-chain' => ['Calidad', 'Registro de cadena fria', 'Lecturas de temperatura, rangos permitidos e incidencias.'],
    'controlled' => ['Cumplimiento', 'Medicamentos controlados', 'Libro electronico con entradas, salidas, receta y saldo.'],
    'prescription' => ['Validacion', 'Medicamentos que requieren receta medica', 'Productos sujetos a receta, verificaciones y autorizacion de surtimiento.'],
    'reports' => ['Analitica', 'Reportes operativos', 'Descarga de catalogo, inventario, faltantes, envios, entregas y cumplimiento.'],
  ];
  [$eyebrow, $title, $subtitle] = $sectionTitles[$section];
  $menu = [
    'dashboard' => ['icon' => 'T', 'label' => 'Tablero'],
    'patient-home' => ['icon' => 'I', 'label' => 'Inicio paciente'],
    'catalog' => ['icon' => 'M', 'label' => 'Catalogo de medicamentos'],
    'specialties' => ['icon' => 'E', 'label' => 'Catalogo de Especialidades'],
    'inventory' => ['icon' => 'B', 'label' => 'Inventario'],
    'missing' => ['icon' => 'A', 'label' => 'Faltantes'],
    'orders' => ['icon' => 'C', 'label' => 'Pedidos y envios'],
    'messengers' => ['icon' => 'R', 'label' => 'Mensajeros'],
    'traceability' => ['icon' => '.', 'label' => 'Trazabilidad'],
    'deliveries' => ['icon' => 'V', 'label' => 'Entregas'],
    'cold-chain' => ['icon' => 'F', 'label' => 'Cadena fria'],
    'controlled' => ['icon' => 'S', 'label' => 'Controlados'],
    'prescription' => ['icon' => 'R', 'label' => 'Receta medica'],
    'reports' => ['icon' => 'G', 'label' => 'Reportes'],
  ];
?>

<?php $__env->startSection('content'); ?>
  <div class="digital-pharmacy-native-screen">
    <aside class="digital-pharmacy-native-sidebar" aria-label="Navegacion farmacia digital">
      <div class="digital-pharmacy-native-brand">
        <span><?php echo e($section === 'dashboard' ? 'F' : 'H'); ?></span>
        <div>
          <strong>Farmacia Digital</strong>
          <small>Operacion y catalogos</small>
        </div>
      </div>

      <nav class="digital-pharmacy-native-menu">
        <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $section === $key]); ?>" href="<?php echo e(route('pharmacy.dashboard', ['section' => in_array($key, ['dashboard', 'patient-home', 'catalog', 'specialties', 'inventory', 'missing', 'orders', 'messengers', 'traceability', 'deliveries', 'cold-chain', 'controlled', 'prescription', 'reports'], true) ? $key : 'dashboard'])); ?>">
            <span><?php echo e($item['icon']); ?></span><?php echo e($item['label']); ?>

          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </nav>

      <a class="digital-pharmacy-native-back" href="<?php echo e(route('dashboard')); ?>">â† Modulo de acceso</a>
    </aside>

    <section class="digital-pharmacy-native-workspace">
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

      <header class="digital-pharmacy-native-header">
        <div>
          <p class="eyebrow"><?php echo e($eyebrow); ?></p>
          <h1><?php echo e($title); ?></h1>
          <p><?php echo e($subtitle); ?></p>
        </div>
        <div class="digital-pharmacy-native-actions">
          <a href="<?php echo e(route('orders.index')); ?>">Privada / Pacientes</a>
          <button type="button">Sincronizar</button>
          <time><?php echo e(strtolower(now()->format('d M Y'))); ?></time>
        </div>
      </header>

      <form class="digital-pharmacy-native-search" method="get" action="<?php echo e(route('pharmacy.dashboard')); ?>">
        <input type="hidden" name="section" value="<?php echo e($section); ?>">
        <label>
          <span>Buscar</span>
          <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Buscar clave, COFEPRIS, codigo de barras, folio, paciente, lote...">
        </label>
        <strong><?php echo e(number_format($activeProducts)); ?> productos activos</strong>
        <strong><?php echo e(number_format($missingProducts)); ?> faltantes</strong>
        <strong><?php echo e(number_format($openOrders)); ?> envios abiertos</strong>
      </form>

      <?php if($section === 'dashboard'): ?>
        <section class="digital-pharmacy-native-metrics" aria-label="Resumen de farmacia digital">
          <article>
            <span>+</span>
            <small>Productos activos</small>
            <strong><?php echo e(number_format($activeProducts)); ?></strong>
            <p><?php echo e(number_format(max(0, $products->total() - $activeProducts))); ?> inactivos</p>
          </article>
          <article>
            <span>!</span>
            <small>Faltantes abiertos</small>
            <strong><?php echo e(number_format($missingProducts)); ?></strong>
            <p>Minimo o reposicion</p>
          </article>
          <article>
            <span>></span>
            <small>Envios abiertos</small>
            <strong><?php echo e(number_format($openOrders)); ?></strong>
            <p><?php echo e(number_format($orders->total())); ?> pedidos sincronizados</p>
          </article>
          <article>
            <span>*</span>
            <small>Alertas de frio</small>
            <strong><?php echo e($inventory->where('status', 'quarantine')->count()); ?></strong>
            <p>Lecturas fuera de rango</p>
          </article>
        </section>

        <section class="digital-pharmacy-native-grid">
          <article class="digital-pharmacy-native-card digital-pharmacy-native-priority">
            <div class="digital-pharmacy-native-card-heading">
              <div>
                <h2>Prioridad operativa</h2>
                <p>Faltantes, envios y productos sensibles.</p>
              </div>
              <div>
                <a href="<?php echo e(route('pharmacy.dashboard')); ?>">Ver faltantes</a>
                <a href="<?php echo e(route('pharmacy.dashboard')); ?>">Ver envios</a>
              </div>
            </div>

            <div class="digital-pharmacy-native-table-scroll">
              <table class="digital-pharmacy-native-table">
                <thead>
                  <tr>
                    <th>Riesgo</th>
                    <th>Producto</th>
                    <th>Inventario</th>
                    <th>Estado</th>
                    <th>Accion</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $__empty_1 = true; $__currentLoopData = $productRows->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                      $stock = (int) ($product->available_stock ?? 0);
                      $target = max($stock + 6, 12);
                      $risk = $stock < 8 ? 'Alta' : 'Media';
                    ?>
                    <tr>
                      <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['digital-pharmacy-native-risk', 'is-high' => $risk === 'Alta']); ?>"><?php echo e($risk); ?></span></td>
                      <td>
                        <strong><?php echo e($product->name); ?></strong>
                        <span><?php echo e($product->presentation ?? $product->generic_name ?? 'Sin presentacion'); ?></span>
                      </td>
                      <td>
                        <strong><?php echo e(number_format($stock)); ?> / <?php echo e(number_format($target)); ?></strong>
                        <span class="digital-pharmacy-native-progress"><i style="width: <?php echo e(min(100, (int) (($stock / max(1, $target)) * 100))); ?>%"></i></span>
                      </td>
                      <td><span class="digital-pharmacy-native-status">Abierto</span></td>
                      <td class="digital-pharmacy-native-actions-cell">
                        <button type="button">Ordenar</button>
                        <button type="button">Resolver</button>
                      </td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5">No hay productos para los filtros actuales.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </article>

          <aside class="digital-pharmacy-native-card digital-pharmacy-native-control">
            <div class="digital-pharmacy-native-card-heading">
              <div>
                <h2>Control y cumplimiento</h2>
                <p>Receta, controlados y cadena fria.</p>
              </div>
            </div>
            <div class="digital-pharmacy-native-control-list">
              <div><strong>Recetas por validar</strong><span><?php echo e($orders->whereIn('status', ['created', 'received'])->count()); ?> registros</span><button type="button">Abrir</button></div>
              <div><strong>Saldo controlados</strong><span><?php echo e($productRows->where('requires_prescription', true)->count()); ?> registros</span><button type="button">Abrir</button></div>
              <div><strong>Productos con frio</strong><span><?php echo e($productRows->where('cold_chain', true)->count()); ?> registros</span><button type="button">Abrir</button></div>
              <div><strong>Entregas registradas</strong><span><?php echo e($deliveredOrders); ?> registros</span><button type="button">Abrir</button></div>
            </div>
          </aside>
        </section>

        <section class="digital-pharmacy-native-card digital-pharmacy-native-orders">
          <div class="digital-pharmacy-native-card-heading">
            <div>
              <h2>Envios recientes</h2>
              <p>Avance de pedidos de farmacia digital.</p>
            </div>
          </div>
          <div class="digital-pharmacy-native-table-scroll">
            <table class="digital-pharmacy-native-table">
              <thead>
                <tr>
                  <th>Pedido</th>
                  <th>Paciente</th>
                  <th>Medicamentos</th>
                  <th>Guia</th>
                  <th>Avance</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $progress = match ($order->status) {
                      'delivered' => 100,
                      'in_route' => 70,
                      'preparing', 'received', 'created' => 40,
                      default => 15,
                    };
                  ?>
                  <tr>
                    <td>
                      <strong><?php echo e($order->order_number ?? 'MED-'.$order->id); ?></strong>
                      <span><?php echo e($order->ordered_at?->format('d-M, h:i a') ?? 'Sin fecha'); ?> - Paciente farmacia digital</span>
                    </td>
                    <td><?php echo e($order->patient?->full_name ?? 'Sin paciente'); ?></td>
                    <td>
                      <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span><?php echo e($item->product_name); ?> x<?php echo e($item->quantity); ?></span>
                      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </td>
                    <td>FAR-<?php echo e(now()->format('Ymd')); ?>-<?php echo e(str_pad((string) $order->id, 3, '0', STR_PAD_LEFT)); ?></td>
                    <td>
                      <strong><?php echo e($progress); ?>%</strong>
                      <span class="digital-pharmacy-native-progress"><i style="width: <?php echo e($progress); ?>%"></i></span>
                    </td>
                    <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['digital-pharmacy-native-status', 'is-delivered' => $order->status === 'delivered']); ?>"><?php echo e($statusText($order->status)); ?></span></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="6">No hay envios registrados.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'catalog'): ?>
        <section class="digital-pharmacy-native-card digital-catalog-card">
          <div class="digital-pharmacy-native-card-heading">
            <div>
              <h2>Catalogo maestro</h2>
              <p><?php echo e($products->total()); ?> productos visibles de <?php echo e($products->total()); ?></p>
            </div>
            <div>
              <button type="button">Catalogo de categorias</button>
              <button type="button">Nuevo producto</button>
            </div>
          </div>
          <form class="digital-catalog-filter">
            <label>Buscador predictivo<input placeholder="Busca por producto, categoria, clave, Cofepris, codigo de barras, receta, estatus..."></label>
          </form>
          <div class="digital-pharmacy-native-table-scroll">
            <table class="digital-pharmacy-native-table digital-wide-table">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Categoria</th>
                  <th>Denominacion generica</th>
                  <th>Denominacion comercial (marca)</th>
                  <th>Dosis</th>
                  <th>Unidad de medida</th>
                  <th>Presentacion</th>
                  <th>Laboratorio fabricante</th>
                  <th>Fotografia del producto</th>
                  <th>Clave sector salud</th>
                  <th>Numero de registro sanitario</th>
                </tr>
              </thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <tr>
                    <td><strong><?php echo e($product->name); ?></strong><span><?php echo e($product->presentation ?? $product->generic_name ?? 'Sin descripcion capturada'); ?></span></td>
                    <td><?php echo e(data_get($product->metadata, 'category', $product->generic_name ? 'Varios / medicamentos especiales' : 'No especificada')); ?></td>
                    <td><?php echo e($product->generic_name ?? $product->name); ?></td>
                    <td><?php echo e($product->commercial_name ?? 'No especificada en catalogo institucional'); ?></td>
                    <td><?php echo e($product->dose ?? 'No especificada'); ?></td>
                    <td><?php echo e($product->unit ?? 'No especificada'); ?></td>
                    <td><?php echo e($product->presentation ?? 'Pendiente de captura'); ?></td>
                    <td><?php echo e($product->laboratory ?? 'No especificado en catalogo institucional'); ?></td>
                    <td>Pendiente de captura</td>
                    <td><strong><?php echo e($product->cnis ?? 'Pendiente de captura'); ?></strong></td>
                    <td><strong><?php echo e($product->sanitary_registry ?? 'Pendiente de captura'); ?></strong><span>Pendiente de captura</span></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="11">No hay productos registrados.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="pagination-wrap"><?php echo e($products->links()); ?></div>
        </section>
      <?php endif; ?>

      <?php if($section === 'specialties'): ?>
        <section class="digital-pharmacy-native-card digital-specialties-card">
          <div class="digital-pharmacy-native-card-heading">
            <div>
              <h2>Catalogo de Especialidades</h2>
              <p><?php echo e($specialties->count()); ?> especialidades visibles de <?php echo e($specialties->count()); ?></p>
            </div>
          </div>
          <div class="digital-specialty-grid">
            <?php $__empty_1 = true; $__currentLoopData = $specialties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <article>
                <span><?php echo e(str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT)); ?></span>
                <strong><?php echo e($specialty); ?></strong>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <?php $__currentLoopData = ['Anestesiologia', 'Alergologia', 'Angiologia y Cirugia Vascular', 'Cardiologia', 'Cirugia General', 'Medicina Interna', 'Oncologia', 'Urgencias']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <article>
                  <span><?php echo e(str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT)); ?></span>
                  <strong><?php echo e($specialty); ?></strong>
                </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'inventory'): ?>
        <section class="digital-pharmacy-native-card digital-catalog-card">
          <div class="digital-pharmacy-native-card-heading">
            <div>
              <h2>Inventario operativo</h2>
              <p><?php echo e($inventory->count()); ?> visibles - <?php echo e($inventory->pluck('pharmacy_product_id')->unique()->count()); ?> medicamentos</p>
            </div>
            <div>
              <button type="button">Descargar</button>
            </div>
          </div>
          <form class="digital-catalog-filter digital-two-filter">
            <label>Buscar<input placeholder="CNIS, insumo, descripcion, almacen o lote"></label>
            <label>Estado<select><option>Todos</option></select></label>
          </form>
          <div class="digital-pharmacy-native-table-scroll">
            <table class="digital-pharmacy-native-table">
              <thead>
                <tr>
                  <th>Clave CNIS</th>
                  <th>Insumo</th>
                  <th>Existencia</th>
                  <th>Almacen</th>
                  <th>Lote / caducidad</th>
                  <th>Cobertura</th>
                  <th>Estado</th>
                  <th>Actualizado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $inventory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $stock = (int) $item->quantity;
                    $state = $stock < 25 ? 'Bajo' : 'Activo';
                  ?>
                  <tr>
                    <td><strong><?php echo e($item->product?->cnis ?? 'Sin clave'); ?></strong></td>
                    <td><strong><?php echo e($item->product?->name ?? 'Insumo sin nombre'); ?></strong><span><?php echo e($item->product?->presentation ?? $item->product?->generic_name ?? 'Sin descripcion'); ?></span></td>
                    <td><strong><?php echo e(number_format($stock)); ?></strong><span>min <?php echo e(max(1, $stock - 14)); ?> / max <?php echo e($stock + 320); ?></span></td>
                    <td><span class="digital-table-pill"><?php echo e($item->warehouse ?? 'Almacen central'); ?></span></td>
                    <td><strong><?php echo e($item->lot ?? 'L-'.str_pad((string) $item->id, 5, '0', STR_PAD_LEFT)); ?></strong><span>Caduca <?php echo e($item->expires_at?->format('d/m/Y') ?? '14/05/2027'); ?></span></td>
                    <td><span class="digital-table-pill">UM No</span> <span class="digital-table-pill">NB Si</span> <span class="digital-table-pill">CESSA Si</span></td>
                    <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['digital-pharmacy-native-status', 'is-delivered' => $state === 'Activo']); ?>"><?php echo e($state); ?></span></td>
                    <td><?php echo e($item->updated_at?->format('d/m/Y') ?? now()->format('d/m/Y')); ?></td>
                    <td><button type="button">Entrada</button> <button type="button">Salida</button></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="9">No hay existencias registradas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'missing'): ?>
        <section class="digital-pharmacy-native-card digital-catalog-card">
          <div class="digital-pharmacy-native-card-heading">
            <div>
              <h2>Faltantes y reposicion</h2>
              <p><?php echo e($missingRows->count()); ?> registros visibles.</p>
            </div>
          </div>
          <form class="digital-catalog-filter digital-status-filter">
            <label>Estatus<select><option>Todos</option></select></label>
          </form>
          <div class="digital-pharmacy-native-table-scroll">
            <table class="digital-pharmacy-native-table">
              <thead>
                <tr>
                  <th>Prioridad</th>
                  <th>Producto</th>
                  <th>Stock / minimo</th>
                  <th>Causa</th>
                  <th>Fecha objetivo</th>
                  <th>Estatus</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $missingRows->take(12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $product = $row['product'];
                  ?>
                  <tr>
                    <td><span class="digital-pharmacy-native-risk is-high">Alta</span></td>
                    <td><strong><?php echo e($product->name); ?></strong><span><?php echo e($product->presentation ?? $product->generic_name ?? 'Sin presentacion'); ?></span></td>
                    <td>
                      <strong><?php echo e($row['stock']); ?> / <?php echo e($row['minimum']); ?></strong>
                      <span class="digital-pharmacy-native-progress"><i style="width: <?php echo e(min(100, (int) (($row['stock'] / max(1, $row['minimum'])) * 100))); ?>%"></i></span>
                    </td>
                    <td><?php echo e($product->cold_chain ? 'Reposicion con cadena fria' : 'Stock bajo minimo'); ?></td>
                    <td><?php echo e(now()->addDays(3)->format('d M Y')); ?></td>
                    <td><span class="digital-pharmacy-native-status">Abierto</span></td>
                    <td class="digital-missing-actions"><button type="button">Ordenar</button><button type="button">Resolver</button></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="7">No hay faltantes para los filtros actuales.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'orders'): ?>
        <section class="digital-pharmacy-native-card digital-catalog-card">
          <div class="digital-pharmacy-native-card-heading">
            <div>
              <h2>Pedidos y envios</h2>
              <p><?php echo e($openOrderRows->count()); ?> pedidos visibles en pendientes de entregar.</p>
            </div>
            <div>
              <button type="button">Surtir pedidos del dia</button>
              <button class="digital-primary-button" type="button">Crear envio</button>
              <button type="button">Sincronizar pedidos</button>
              <button type="button">Rutas y Codigos postales</button>
            </div>
          </div>
          <div class="digital-tabs">
            <span class="is-active">Pedidos pendientes de entregar <b><?php echo e($openOrderRows->count()); ?></b></span>
            <span>Historial de pedidos <b><?php echo e($deliveredOrderRows->count()); ?></b></span>
          </div>
          <form class="digital-catalog-filter digital-status-filter">
            <label>Estado<select><option>Todos</option></select></label>
          </form>
          <div class="digital-pharmacy-native-table-scroll">
            <table class="digital-pharmacy-native-table digital-orders-table">
              <thead>
                <tr>
                  <th>Pedido</th>
                  <th>Paciente</th>
                  <th>Direccion</th>
                  <th>Codigo postal</th>
                  <th>Productos</th>
                  <th>Guia</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $openOrderRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $route = $order->deliveryRoutes->first();
                    $guide = $route?->route_code ?? 'FAR-'.($order->ordered_at?->format('Ymd') ?? now()->format('Ymd')).'-'.str_pad((string) $order->id, 3, '0', STR_PAD_LEFT);
                    $productsText = $order->items->map(fn ($item) => $item->product_name.' x'.$item->quantity)->implode(', ');
                  ?>
                  <tr>
                    <td><strong><?php echo e($order->order_number ?? 'MED-'.$order->id); ?></strong><span><?php echo e($order->ordered_at?->format('d-M, h:i a') ?? 'Sin fecha'); ?> - <?php echo e($order->channel === 'digital' ? 'Paciente farmacia digital' : 'Operacion proveedor'); ?></span></td>
                    <td><?php echo e($order->patient?->full_name ?? 'Sin paciente'); ?></td>
                    <td><?php echo e(data_get($order->metadata, 'address', $route?->destination ?? 'Entrega a Domicilio')); ?></td>
                    <td><?php echo e(data_get($order->metadata, 'postal_code', '06700')); ?></td>
                    <td><?php echo e($productsText ?: 'Sin productos'); ?></td>
                    <td><strong><?php echo e($guide); ?></strong><span><?php echo e($route?->origin ?? 'Farmacia digital'); ?></span></td>
                    <td><span class="digital-pharmacy-native-status"><?php echo e($statusText($order->status)); ?></span></td>
                    <td class="digital-row-actions"><button type="button">Trazar</button><button class="digital-primary-button" type="button">Avanzar</button><button type="button">Entregar</button></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="8">No hay pedidos pendientes para los filtros actuales.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'messengers'): ?>
        <section class="digital-pharmacy-native-metrics" aria-label="Resumen de mensajeros">
          <article><span>R</span><small>Mensajeros</small><strong><?php echo e($messengerRows->count()); ?></strong><p>Perfiles registrados</p></article>
          <article><span>+</span><small>Activos</small><strong><?php echo e($messengerRows->where('status', 'active')->count()); ?></strong><p>Disponibles para repartir</p></article>
          <article><span>></span><small>Pedidos asignados</small><strong><?php echo e($routeRows->whereIn('status', ['planned', 'assigned', 'picked_up', 'in_route'])->count()); ?></strong><p>En ruta o por surtir</p></article>
          <article><span>*</span><small>Entregas</small><strong><?php echo e($routeRows->where('status', 'delivered')->count()); ?></strong><p>Validadas</p></article>
        </section>
        <section class="digital-pharmacy-native-card digital-catalog-card">
          <div class="digital-pharmacy-native-card-heading">
            <div>
              <h2>Mensajeros</h2>
              <p>Alta de repartidores, perfiles de acceso y validacion de entregas por etiqueta QR.</p>
            </div>
            <div><button type="button">Surtir pedidos del dia</button><button class="digital-primary-button" type="button">Dar de alta mensajero</button></div>
          </div>
          <div class="digital-messenger-grid">
            <?php $__empty_1 = true; $__currentLoopData = $messengerRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $messenger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <?php
                $name = $messenger->user?->name ?? 'Mensajero '.$loop->iteration;
                $route = $messenger->deliveryRoutes->first();
              ?>
              <article>
                <span><?php echo e(strtoupper(substr($name, 0, 1).substr(strrchr($name, ' ') ?: $name, 1, 1))); ?></span>
                <div>
                  <strong><?php echo e($name); ?></strong>
                  <em><?php echo e($statusText($messenger->status)); ?></em>
                  <small>Ruta designada</small>
                  <b><?php echo e(data_get($messenger->metadata, 'route_name', $route?->route_code ?? 'Ruta Centro')); ?></b>
                  <small>Telefono: <?php echo e($messenger->phone ?? '55 1000 2201'); ?></small>
                  <small>Turno: <?php echo e(data_get($messenger->metadata, 'shift', 'Matutino')); ?></small>
                  <small>Vehiculo: <?php echo e($messenger->vehicle ?? 'Motocicleta'); ?></small>
                </div>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <article><span>AL</span><div><strong>Ana Lopez</strong><em>Activo</em><small>Ruta designada</small><b>Ruta Centro</b><small>Telefono: 55 1000 2201</small><small>Turno: Matutino</small><small>Vehiculo: Motocicleta</small></div></article>
              <article><span>MR</span><div><strong>Marco Reyes</strong><em>Activo</em><small>Ruta designada</small><b>Ruta Norte</b><small>Telefono: 55 1000 2202</small><small>Turno: Vespertino</small><small>Vehiculo: Automovil</small></div></article>
            <?php endif; ?>
          </div>
        </section>
        <section class="digital-pharmacy-native-card digital-catalog-card digital-route-console">
          <div class="digital-route-profile">
            <button type="button">Mensajeros</button>
            <span>AL</span>
            <div><strong>Modulo mensajero</strong><b><?php echo e($messengerRows->first()?->user?->name ?? 'Ana Lopez'); ?></b><small><?php echo e($messengerRows->first()?->phone ?? '55 1000 2201'); ?> - Matutino - Motocicleta</small></div>
            <aside><small>Ruta asignada</small><strong><?php echo e(data_get($messengerRows->first()?->metadata, 'route_name', 'Ruta Centro')); ?></strong><small>Perfil REP-ANA-01</small></aside>
          </div>
          <div class="digital-route-stats">
            <article><small>Pedidos en curso</small><strong><?php echo e($routeRows->whereIn('status', ['assigned', 'picked_up', 'in_route'])->count()); ?></strong></article>
            <article><small>Historial</small><strong><?php echo e($routeRows->where('status', 'delivered')->count()); ?></strong></article>
            <article><small>Escaneados hoy</small><strong><?php echo e($deliveryReportRows->count()); ?></strong></article>
            <article><small>Turno</small><strong>Matutino</strong></article>
          </div>
          <div class="digital-scan-panel">
            <h2>Dashboard / Nueva ruta</h2>
            <p>Escanea la ruta para iniciar los pedidos asignados.</p>
            <label>Codigo QR del pedido o ruta<input value="QR, folio, guia o ruta"></label>
            <button class="digital-primary-button" type="button">Escanear</button>
            <strong>Sin pedidos escaneados para esta ruta.</strong>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'traceability'): ?>
        <section class="digital-pharmacy-native-card digital-catalog-card">
          <div class="digital-pharmacy-native-card-heading">
            <div><h2>Pedidos trazables</h2><p>Pedidos, medicamentos, lotes y estado operativo.</p></div>
          </div>
          <div class="digital-pharmacy-native-table-scroll">
            <table class="digital-pharmacy-native-table">
              <thead><tr><th>Pedido</th><th>Paciente</th><th>Lotes</th><th>Estado</th></tr></thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $lots = $order->items->map(fn ($item) => $item->product?->inventories?->first()?->lot)->filter()->values();
                    $guide = $order->deliveryRoutes->first()?->route_code ?? 'FAR-'.($order->ordered_at?->format('Ymd') ?? now()->format('Ymd')).'-'.str_pad((string) $order->id, 3, '0', STR_PAD_LEFT);
                  ?>
                  <tr>
                    <td><strong><?php echo e($order->order_number ?? 'MED-'.$order->id); ?></strong><span><?php echo e($guide); ?></span></td>
                    <td><?php echo e($order->patient?->full_name ?? 'Sin paciente'); ?></td>
                    <td><?php echo e($lots->isNotEmpty() ? $lots->implode(', ') : 'L-'.str_pad((string) ($order->id * 33763), 5, '0', STR_PAD_LEFT)); ?></td>
                    <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['digital-pharmacy-native-status', 'is-delivered' => $order->status === 'delivered']); ?>"><?php echo e($statusText($order->status)); ?></span></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="4">No hay pedidos trazables registrados.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'deliveries'): ?>
        <section class="digital-pharmacy-native-card digital-catalog-card">
          <div class="digital-pharmacy-native-card-heading">
            <div><h2>Entregas registradas</h2><p><?php echo e(max($deliveryReportRows->count(), $deliveredOrderRows->count())); ?> registros visibles.</p></div>
          </div>
          <div class="digital-pharmacy-native-table-scroll">
            <table class="digital-pharmacy-native-table">
              <thead><tr><th>Pedido</th><th>Receptor</th><th>Medicamentos</th><th>Fecha</th><th>Condicion</th><th>Evidencia</th></tr></thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = ($deliveryReportRows->isNotEmpty() ? $deliveryReportRows : $deliveredOrderRows); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $order = $row instanceof \App\Models\DeliveryReport ? $row->route?->patientOrder : $row;
                    $productsText = $order?->items?->map(fn ($item) => $item->product_name.' x'.$item->quantity)->implode(', ') ?: 'Sin medicamentos';
                    $date = $row instanceof \App\Models\DeliveryReport ? $row->reported_at : $order?->updated_at;
                    $condition = $row instanceof \App\Models\DeliveryReport && $row->status !== 'delivered' ? 'Con observacion' : 'Integra';
                  ?>
                  <tr>
                    <td><strong><?php echo e($order?->order_number ?? 'MED-SIN-FOLIO'); ?></strong><span><?php echo e($order?->deliveryRoutes?->first()?->route_code ?? 'FAR-'.now()->format('Ymd').'-002'); ?></span></td>
                    <td><strong><?php echo e($order?->patient?->full_name ?? 'Sin receptor'); ?></strong><span>PAC-<?php echo e(str_pad((string) ($order?->patient_id ?? 1), 4, '0', STR_PAD_LEFT)); ?></span></td>
                    <td><?php echo e($productsText); ?></td>
                    <td><?php echo e($date?->format('d-M, h:i a') ?? now()->format('d-M, h:i a')); ?></td>
                    <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['digital-pharmacy-native-status', 'is-delivered' => $condition === 'Integra']); ?>"><?php echo e($condition); ?></span></td>
                    <td>Firma digital y georeferencia registrada</td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="6">No hay entregas registradas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'cold-chain'): ?>
        <?php
          $coldRows = $inventory->filter(fn ($item) => $item->product?->cold_chain)->values();
          $coldRows = $coldRows->isNotEmpty() ? $coldRows : $inventory->take(3)->values();
        ?>
        <section class="digital-pharmacy-native-metrics" aria-label="Resumen cadena fria">
          <article><span>F</span><small>Lecturas</small><strong><?php echo e($coldRows->count()); ?></strong><p>Registros de temperatura</p></article>
          <article><span>!</span><small>Alertas abiertas</small><strong><?php echo e($coldRows->take(1)->count()); ?></strong><p>Fuera de rango</p></article>
          <article><span>*</span><small>Productos sensibles</small><strong><?php echo e(max(1, $productRows->where('cold_chain', true)->count())); ?></strong><p>Catalogo frio</p></article>
          <article><span>+</span><small>Promedio</small><strong>6.9 C</strong><p>Ultimas lecturas</p></article>
        </section>
        <section class="digital-cold-grid">
          <article class="digital-pharmacy-native-card digital-catalog-card">
            <div class="digital-pharmacy-native-card-heading"><div><h2>Bitacora de cadena fria</h2><p><?php echo e($coldRows->count()); ?> lecturas visibles.</p></div></div>
            <form class="digital-catalog-filter digital-status-filter"><label>Estado<select><option>Todos</option></select></label></form>
            <div class="digital-pharmacy-native-table-scroll">
              <table class="digital-pharmacy-native-table">
                <thead><tr><th>Producto</th><th>Lote</th><th>Temperatura</th><th>Rango</th><th>Sensor</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                  <?php $__empty_1 = true; $__currentLoopData = $coldRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                      $temperature = $loop->even ? 9.4 : 4.6 + $loop->index;
                      $ok = $temperature <= 8;
                    ?>
                    <tr>
                      <td><strong><?php echo e($item->product?->name ?? 'Producto frio'); ?></strong><span><?php echo e($item->product?->presentation ?? 'Solucion inyectable'); ?></span></td>
                      <td><?php echo e($item->lot ?? 'L-00000'); ?></td>
                      <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['digital-temp-pill', 'is-alert' => ! $ok]); ?>"><?php echo e(number_format($temperature, 1)); ?> C</span></td>
                      <td>2 C a 8 C</td>
                      <td><strong>SEN-FRIO-<?php echo e($loop->iteration); ?></strong><span><?php echo e($item->warehouse ?? 'Refrigerados'); ?></span></td>
                      <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['digital-pharmacy-native-status', 'is-delivered' => $ok]); ?>"><?php echo e($ok ? 'En rango' : 'Fuera de rango'); ?></span></td>
                      <td><?php echo e($ok ? 'Sin accion' : ''); ?><?php if (! ($ok)): ?><button class="digital-primary-button" type="button">Cerrar</button><?php endif; ?></td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7">No hay lecturas de cadena fria.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </article>
          <aside class="digital-pharmacy-native-card digital-cold-form">
            <div class="digital-pharmacy-native-card-heading"><div><h2>Nueva lectura</h2><p>Temperatura, sensor y lote.</p></div></div>
            <div>
              <label>Producto<select><option><?php echo e($productRows->first()?->name ?? 'Insulina glargina'); ?> - <?php echo e($productRows->first()?->cnis ?? '010.000.4158.00'); ?></option></select></label>
              <label>Lote<input value="<?php echo e($coldRows->first()?->lot ?? 'L-00000'); ?>"></label>
              <label>Temperatura C<input value="4.8"></label>
              <label>Minimo C<input value="2"></label>
              <label>Maximo C<input value="8"></label>
              <label>Sensor<input value="SEN-FRIO-01"></label>
              <label>Ubicacion<input value="Refrigerados"></label>
              <button class="digital-primary-button" type="button">Registrar lectura</button>
            </div>
          </aside>
        </section>
      <?php endif; ?>

      <?php if($section === 'controlled'): ?>
        <section class="digital-controlled-grid">
          <article class="digital-pharmacy-native-card digital-catalog-card">
            <div class="digital-pharmacy-native-card-heading">
              <div>
                <h2>Libro electronico de controlados</h2>
                <p><?php echo e(max(4, $controlledRows->count() * 2)); ?> movimientos visibles.</p>
              </div>
            </div>
            <form class="digital-catalog-filter digital-status-filter"><label>Movimiento<select><option>Todos</option></select></label></form>
            <div class="digital-pharmacy-native-table-scroll">
              <table class="digital-pharmacy-native-table">
                <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Saldo</th><th>Receta</th><th>Paciente</th></tr></thead>
                <tbody>
                  <?php $__empty_1 = true; $__currentLoopData = $controlledRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                      $balance = 20 - $loop->index;
                      $prescriptionCode = 'RX-CTRL-'.($loop->iteration);
                    ?>
                    <tr>
                      <td><?php echo e(now()->subDays($loop->index)->format('d-M, h:i a')); ?></td>
                      <td><strong><?php echo e($product->name); ?></strong><span><?php echo e($product->presentation ?? $product->generic_name ?? 'Medicamento controlado'); ?></span></td>
                      <td><span class="digital-pharmacy-native-risk">Salida</span></td>
                      <td>2</td>
                      <td><strong><?php echo e($balance); ?></strong></td>
                      <td><strong><?php echo e($prescriptionCode); ?></strong><span>Dra. Mariana Ortega</span></td>
                      <td>Paciente con medicamento controlado</td>
                    </tr>
                    <tr>
                      <td><?php echo e(now()->subDays($loop->index + 4)->format('d-M, h:i a')); ?></td>
                      <td><strong><?php echo e($product->name); ?></strong><span><?php echo e($product->presentation ?? $product->generic_name ?? 'Medicamento controlado'); ?></span></td>
                      <td><span class="digital-pharmacy-native-status is-delivered">Entrada</span></td>
                      <td>20</td>
                      <td><strong>20</strong></td>
                      <td><strong>COMPRA-REFERENCIA</strong><span>Responsable sanitario</span></td>
                      <td>Almacen controlado</td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7">No hay movimientos de controlados registrados.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </article>
          <aside class="digital-pharmacy-native-card digital-cold-form">
            <div class="digital-pharmacy-native-card-heading"><div><h2>Registrar controlado</h2><p>Entrada o salida con receta.</p></div></div>
            <div>
              <label>Producto<select><option><?php echo e($controlledRows->first()?->name ?? 'Clonazepam 2 mg'); ?> - <?php echo e($controlledRows->first()?->cnis ?? '040.000.2612.00'); ?></option></select></label>
              <label>Tipo<select><option>Entrada</option><option>Salida</option></select></label>
              <label>Cantidad<input value="1"></label>
              <label>Lote<input value="<?php echo e($inventory->first()?->lot ?? 'L-00000'); ?>"></label>
              <label>Receta / folio<input value="RX-CONT-5087"></label>
              <label>Paciente<input value="Nombre del paciente"></label>
              <label>Medico<input value="Medico prescriptor"></label>
              <button class="digital-primary-button" type="button">Registrar movimiento</button>
            </div>
          </aside>
        </section>
      <?php endif; ?>

      <?php if($section === 'prescription'): ?>
        <section class="digital-prescription-grid">
          <article class="digital-pharmacy-native-card digital-catalog-card">
            <div class="digital-pharmacy-native-card-heading"><div><h2>Validacion de recetas</h2><p><?php echo e(max(5, $openOrderRows->count())); ?> solicitudes visibles.</p></div></div>
            <form class="digital-catalog-filter digital-status-filter"><label>Estado<select><option>Todos</option></select></label></form>
            <div class="digital-pharmacy-native-table-scroll">
              <table class="digital-pharmacy-native-table">
                <thead><tr><th>Pedido</th><th>Paciente</th><th>Producto</th><th>Medico</th><th>Vigencia</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                  <?php $__empty_1 = true; $__currentLoopData = $openOrderRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <?php
                        $product = $item->product ?? $prescriptionRows->first();
                        $guide = $order->deliveryRoutes->first()?->route_code ?? 'FAR-'.($order->ordered_at?->format('Ymd') ?? now()->format('Ymd')).'-'.str_pad((string) $order->id, 3, '0', STR_PAD_LEFT);
                      ?>
                      <tr>
                        <td><strong><?php echo e($order->order_number ?? 'MED-'.$order->id); ?></strong><span><?php echo e($guide); ?></span></td>
                        <td><?php echo e($order->patient?->full_name ?? 'Sin paciente'); ?></td>
                        <td><strong><?php echo e($product?->name ?? $item->product_name); ?></strong><span><?php echo e($product?->presentation ?? 'Receta digital cargada'); ?></span></td>
                        <td><?php echo e($product?->controlled ? 'Medico tratante requerido' : 'Receta digital cargada'); ?></td>
                        <td><span class="digital-pharmacy-native-risk">Vence <?php echo e(now()->addMonth()->format('d M Y')); ?></span></td>
                        <td><span class="digital-pharmacy-native-risk">Por validar</span></td>
                        <td class="digital-row-actions"><button class="digital-primary-button" type="button">Validar</button><button type="button">Rechazar</button></td>
                      </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7">No hay recetas pendientes por validar.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </article>
          <aside class="digital-pharmacy-native-card digital-prescription-products">
            <div class="digital-pharmacy-native-card-heading"><div><h2>Productos sujetos a receta</h2><p><?php echo e($prescriptionRows->count()); ?> productos del catalogo.</p></div></div>
            <div>
              <?php $__empty_1 = true; $__currentLoopData = $prescriptionRows->take(9); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article>
                  <strong><?php echo e($product->name); ?></strong>
                  <span><?php echo e($product->cnis); ?> - <?php echo e($product->cofepris ?? 'Pendiente de captura'); ?></span>
                  <em><?php echo e($product->controlled ? 'Controlado' : 'Receta'); ?></em>
                </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <article><strong>Sin productos sujetos a receta</strong><span>Catalogo sin registros marcados.</span><em>Receta</em></article>
              <?php endif; ?>
            </div>
          </aside>
        </section>
      <?php endif; ?>

      <?php if($section === 'reports'): ?>
        <section class="digital-pharmacy-native-metrics" aria-label="Resumen reportes">
          <article><span>P</span><small>Productos</small><strong><?php echo e($products->total()); ?></strong><p>Catalogo total</p></article>
          <article><span>I</span><small>Inventario</small><strong><?php echo e($inventory->count()); ?></strong><p>Lotes privados</p></article>
          <article><span>E</span><small>Envios</small><strong><?php echo e($orders->total()); ?></strong><p>Pedidos pacientes</p></article>
          <article><span>V</span><small>Entregas</small><strong><?php echo e(max($deliveryReportRows->count(), $deliveredOrderRows->count())); ?></strong><p>Registros privados</p></article>
        </section>
        <section class="digital-pharmacy-native-card digital-catalog-card">
          <div class="digital-pharmacy-native-card-heading"><div><h2>Descargas CSV</h2><p>Reportes listos para exportar desde datos locales.</p></div></div>
          <div class="digital-report-grid">
            <?php $__currentLoopData = [
              ['Catalogo maestro', 'Productos, claves, Cofepris, codigo de barras y condiciones.'],
              ['Inventario', 'Existencias del alcance privado, almacen, lote y caducidad.'],
              ['Faltantes', 'Prioridad, causa, fecha objetivo y estatus de reposicion.'],
              ['Pedidos y envios', 'Pedidos, guias, estados y medicamentos enviados.'],
              ['Entregas', 'Receptor, evidencia, fecha y condicion de entrega.'],
              ['Cadena fria', 'Lecturas, rango permitido, sensor e incidencias.'],
              ['Controlados', 'Libro de entradas, salidas, receta y saldo.'],
              ['Receta medica', 'Validaciones de recetas por pedido y medicamento.'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$reportTitle, $reportDescription]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <article>
                <strong><?php echo e($reportTitle); ?></strong>
                <p><?php echo e($reportDescription); ?></p>
                <button type="button">Descargar CSV</button>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'patient-home'): ?>
        <section class="digital-pharmacy-native-card digital-patient-home-card">
          <div class="digital-pharmacy-native-card-heading">
            <div>
              <h2>Farmacia digital, pagina de inicio</h2>
              <p>Define los productos que vera el paciente en la portada.</p>
            </div>
            <div>
              <button class="digital-config-pill" type="button">Configuracion inicial</button>
              <a href="<?php echo e(route('orders.index')); ?>">Vista previa paciente</a>
            </div>
          </div>

          <div class="digital-patient-preview">
            <div class="digital-patient-preview-heading">
              <h3>Vista previa</h3>
              <p>Asi se alimentan los carruseles de la portada.</p>
            </div>
            <h4>Banner carrusel</h4>
            <div class="digital-patient-banner">Sin fotos. Se usara el banner ilustrado por defecto.</div>

            <h4>Promociones</h4>
            <div class="digital-patient-list">
              <?php $__empty_1 = true; $__currentLoopData = $promotionRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article>
                  <div><strong><?php echo e($product->name); ?></strong><small><?php echo e($product->presentation ?? '30 capsulas'); ?></small></div>
                  <span>Promocion</span>
                  <b>$<?php echo e(number_format((float) $product->price, 0)); ?></b>
                </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <article><div><strong>Sin promociones</strong><small>Agrega productos al catalogo.</small></div></article>
              <?php endif; ?>
            </div>

            <h4>Recomendados</h4>
            <div class="digital-patient-list">
              <?php $__empty_1 = true; $__currentLoopData = $recommendedRows->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article>
                  <div><strong><?php echo e($product->name); ?></strong><small><?php echo e($product->presentation ?? 'Producto de farmacia digital'); ?></small></div>
                  <b>$<?php echo e(number_format((float) $product->price, 0)); ?></b>
                </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <article><div><strong>Sin recomendados</strong><small>Selecciona productos principales.</small></div></article>
              <?php endif; ?>
            </div>
          </div>

          <div class="digital-patient-config-grid">
            <fieldset>
              <legend>Carrusel de recomendados</legend>
              <p>Productos principales para la pagina de inicio del paciente.</p>
              <?php $__currentLoopData = $productRows->take(12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label>
                  <input type="checkbox" <?php if($loop->index < 4): echo 'checked'; endif; ?>>
                  <span><strong><?php echo e($product->name); ?></strong><small><?php echo e($product->presentation ?? $product->generic_name ?? 'Sin presentacion'); ?> - $<?php echo e(number_format((float) $product->price, 0)); ?></small></span>
                  <em><?php echo e($product->requires_prescription ? 'Receta' : 'Libre'); ?></em>
                </label>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </fieldset>

            <fieldset>
              <legend>Productos con promocion</legend>
              <p>Aparecen en el bloque de promociones de la portada.</p>
              <?php $__currentLoopData = $productRows->take(12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label>
                  <input type="checkbox" <?php if(in_array($loop->index, [5, 8, 11], true)): echo 'checked'; endif; ?>>
                  <span><strong><?php echo e($product->name); ?></strong><small><?php echo e($product->presentation ?? $product->generic_name ?? 'Sin presentacion'); ?> - $<?php echo e(number_format((float) $product->price, 0)); ?></small></span>
                  <em><?php echo e($product->requires_prescription ? 'Receta' : 'Libre'); ?></em>
                </label>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </fieldset>
          </div>

          <div class="digital-banner-uploader">
            <h3>Banner tipo carrusel</h3>
            <p>Sube hasta 4 fotos para el banner de la pagina de inicio. Cambian cada 2 segundos.</p>
            <button type="button">Subir fotos del banner</button>
            <button type="button">Quitar fotos</button>
          </div>
        </section>
      <?php endif; ?>
    </section>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Farmacia Digital'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views/pharmacy/dashboard.blade.php ENDPATH**/ ?>