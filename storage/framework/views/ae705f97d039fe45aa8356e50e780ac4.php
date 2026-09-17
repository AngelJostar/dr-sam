

<?php $__env->startSection('body_class', 'orders-store-native-body'); ?>

<?php
  $statusLabels = [
    'created' => 'Creado',
    'received' => 'Recibido',
    'preparing' => 'Preparacion',
    'in_route' => 'En camino',
    'delivered' => 'Entregado',
    'cancelled' => 'Cancelado',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $patientInitials = $patient ? collect(explode(' ', $patient->full_name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') : 'PX';
  $productRows = $products->getCollection();
  $featured = $productRows->take(3);
  $recommended = $productRows->skip(3)->take(4);
  if ($recommended->isEmpty()) {
    $recommended = $productRows->take(4);
  }
?>

<?php $__env->startSection('content'); ?>
  <div class="orders-store-native-screen">
    <header class="orders-store-native-topbar">
      <a class="orders-store-native-brand" href="<?php echo e(route('orders.index')); ?>">
        <span>+</span>
        <div>
          <strong>Farmacia Digital</strong>
          <small>Pagina de inicio</small>
        </div>
      </a>

      <form class="orders-store-native-search" method="get" action="<?php echo e(route('orders.index')); ?>">
        <span>Q</span>
        <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Buscar medicamentos, categorias o sintomas">
      </form>

      <nav class="orders-store-native-nav" aria-label="Navegacion farmacia paciente">
        <a class="is-active" href="<?php echo e(route('orders.index')); ?>">Inicio</a>
        <a href="#orders-catalog">Medicamentos</a>
        <a href="#orders-cart">Carrito <span>0</span></a>
        <a href="#orders-history">Pedidos</a>
      </nav>

      <a class="orders-store-native-home" href="<?php echo e(route('dashboard')); ?>">Pagina de inicio</a>

      <?php if($patient): ?>
        <a class="orders-store-native-patient" href="<?php echo e(route('patient.dashboard')); ?>">
          <span><?php echo e($patientInitials); ?></span>
          <strong><?php echo e($patient->full_name); ?></strong>
          <small>Paciente</small>
        </a>
      <?php endif; ?>
    </header>

    <main class="orders-store-native-main">
      <?php if(session('status')): ?>
        <div class="notice success"><?php echo e(session('status')); ?></div>
      <?php endif; ?>

      <?php if($errors->any()): ?>
        <div class="notice danger">
          <strong>No se pudo crear el pedido.</strong>
          <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </ul>
        </div>
      <?php endif; ?>

      <section class="orders-store-native-hero">
        <div>
          <p class="eyebrow">Compra segura y protegida</p>
          <h1>Medicamentos y entregas para tu tratamiento</h1>
          <p>
            <?php if($patient): ?>
              <?php echo e($patient->full_name); ?> / Expediente <?php echo e($patient->platform_number ?? 'sin folio'); ?>

            <?php else: ?>
              Catalogo digital de farmacia
            <?php endif; ?>
          </p>
        </div>
        <div class="orders-store-native-hero-card">
          <strong><?php echo e($products->total()); ?></strong>
          <span>productos disponibles</span>
        </div>
      </section>

      <section class="orders-store-native-section">
        <div class="orders-store-native-heading">
          <div>
            <p class="eyebrow">Promociones</p>
            <h2>Ofertas activas</h2>
          </div>
          <a href="#orders-catalog">Ver todos</a>
        </div>
        <div class="orders-store-native-products">
          <?php $__empty_1 = true; $__currentLoopData = $featured; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php echo $__env->make('orders.partials.store-product-card', ['product' => $product, 'compact' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="empty-state">No hay ofertas activas por ahora.</p>
          <?php endif; ?>
        </div>
      </section>

      <section class="orders-store-native-section">
        <div class="orders-store-native-heading">
          <div>
            <p class="eyebrow">Seleccion para ti</p>
            <h2>Recomendados</h2>
          </div>
          <a href="#orders-catalog">Ver todos</a>
        </div>
        <div class="orders-store-native-products is-four">
          <?php $__empty_1 = true; $__currentLoopData = $recommended; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php echo $__env->make('orders.partials.store-product-card', ['product' => $product, 'compact' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="empty-state">No hay recomendados disponibles.</p>
          <?php endif; ?>
        </div>
      </section>

      <section id="orders-catalog" class="orders-store-native-catalog">
        <div class="orders-store-native-heading">
          <div>
            <p class="eyebrow">Catalogo</p>
            <h2>Medicamentos</h2>
          </div>
          <span><?php echo e($products->total()); ?> productos</span>
        </div>

        <div class="orders-store-native-category-row">
          <?php $__currentLoopData = ['Todas', 'Sistema nervioso', 'Antiinfecciosos', 'Aparato digestivo', 'Varios / medicamentos']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a class="<?php echo e($loop->first ? 'is-active' : ''); ?>" href="<?php echo e(route('orders.index')); ?>"><?php echo e($category); ?></a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="orders-store-native-products is-four">
          <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php echo $__env->make('orders.partials.store-product-card', ['product' => $product, 'compact' => false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="empty-state">No hay productos con esos filtros.</p>
          <?php endif; ?>
        </div>
        <div class="pagination-wrap"><?php echo e($products->links()); ?></div>
      </section>

      <section id="orders-history" class="orders-store-native-history">
        <div class="orders-store-native-heading">
          <div>
            <p class="eyebrow">Seguimiento</p>
            <h2>Mis pedidos</h2>
          </div>
          <span><?php echo e($orders->total()); ?></span>
        </div>
        <div class="orders-store-native-list">
          <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <article>
              <div>
                <strong><?php echo e($order->order_number ?? 'Pedido sin folio'); ?></strong>
                <span><?php echo e($order->ordered_at?->format('d/m/Y H:i') ?? 'Sin fecha'); ?> / <?php echo e($order->channel); ?></span>
              </div>
              <span><?php echo e($statusText($order->status)); ?></span>
              <strong>$<?php echo e(number_format((float) $order->total, 2)); ?></strong>
            </article>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="empty-state">Todavia no hay pedidos registrados.</p>
          <?php endif; ?>
        </div>
        <div class="pagination-wrap"><?php echo e($orders->links()); ?></div>
      </section>
    </main>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Farmacia Digital'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views/orders/index.blade.php ENDPATH**/ ?>