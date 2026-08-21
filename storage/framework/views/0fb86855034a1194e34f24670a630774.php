

<?php $__env->startSection('body_class', 'external-pharmacy-native-body'); ?>

<?php
  $statusLabels = [
    'active' => 'Activo',
    'available' => 'Bajo',
    'created' => 'Pendiente',
    'received' => 'Pendiente',
    'preparing' => 'Parcial',
    'in_route' => 'Surtida',
    'delivered' => 'Surtida',
    'cancelled' => 'Cancelada',
    'assigned' => 'Asignada',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $unitName = $profile?->medicalUnit?->name ?? 'B. Hospital General con Especialidades Juan Maria de Salvatierra';
  $unitCode = $profile?->medicalUnit?->code ?? $profile?->medicalUnit?->clues ?? 'BSIMB000672';
  $areaName = $profile?->area?->label ?? 'Area Operativa Farmacia';
  $unitDescription = $unitName.' - '.$unitCode.' - '.$areaName.' - '.$unitName;
  $sectionTitles = [
    'pending' => 'Recetas y surtimiento',
    'filled' => 'Recetas surtidas',
    'prescription' => 'Formato de Receta Medica',
    'inventory' => 'Inventario de farmacia externa',
    'movements' => 'Bitacora de movimientos',
    'warehouses' => 'Almacenes externos',
    'catalog' => 'Catalogo de farmacia',
  ];
  $menu = [
    'pending' => ['R', 'Recetas pendientes'],
    'filled' => ['S', 'Recetas Surtidas'],
    'prescription' => ['F', 'Formato de Receta Medica'],
    'inventory' => ['I', 'Inventario'],
    'movements' => ['M', 'Movimientos'],
    'warehouses' => ['A', 'Almacenes'],
    'catalog' => ['C', 'Catalogo de farmacia'],
  ];
  $orderRows = $section === 'filled' ? $filledOrders : $pendingOrders;
  $warehouseRows = $inventory->groupBy(fn ($item) => $item->warehouse ?: 'Farmacia externa');
  $selectedMovementItem = $inventory->firstWhere('id', (int) request('movement_item'));
?>

<?php $__env->startSection('content'); ?>
  <div class="external-pharmacy-native-screen">
    <aside class="external-pharmacy-native-sidebar" aria-label="Navegacion farmacia externa">
      <div class="external-pharmacy-native-brand">
        <span aria-hidden="true">+</span>
        <div>
          <strong>Farmacia Externa</strong>
          <small>Panel independiente</small>
        </div>
      </div>

      <nav class="external-pharmacy-native-menu">
        <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$icon, $label]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $section === $key]); ?>" href="<?php echo e(route('external-pharmacy.dashboard', ['section' => $key])); ?>"><span><?php echo e($icon); ?></span><?php echo e($label); ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </nav>

      <a class="external-pharmacy-native-back" href="<?php echo e(route('dashboard')); ?>">â† Modulo de acceso</a>
    </aside>

    <section class="external-pharmacy-native-workspace">
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

      <header class="external-pharmacy-native-header">
        <div>
          <p class="eyebrow">Farmacia externa</p>
          <h1><?php echo e($sectionTitles[$section]); ?></h1>
          <p><?php echo e($unitDescription); ?></p>
        </div>
        <div class="external-pharmacy-native-selectors">
          <label>
            Operar como
            <select>
              <option><?php echo e($areaName); ?> - <?php echo e($unitName); ?></option>
            </select>
          </label>
          <label>
            Unidad
            <select>
              <option><?php echo e($unitName); ?> - <?php echo e($unitCode); ?></option>
            </select>
          </label>
          <time><?php echo e(strtolower(now()->format('d M Y'))); ?></time>
        </div>
      </header>

      <?php if(in_array($section, ['pending', 'filled'], true)): ?>
        <section class="external-pharmacy-native-card">
          <div class="external-pharmacy-native-heading">
            <div>
              <h2><?php echo e($section === 'filled' ? 'Recetas surtidas' : 'Recetas activas'); ?></h2>
                <?php
                    $visibleMedicationCount = $orderRows->sum(function ($order) {
                        return $order->items->count();
                    });
                ?>
              <p><?php echo e($orderRows->count()); ?> recetas - <?php echo e($visibleMedicationCount); ?> medicamentos <?php echo e($section === 'filled' ? 'surtidos' : 'visibles de '.$visibleMedicationCount); ?></p>
            </div>
          </div>

          <?php if($section === 'pending'): ?>
            <form class="external-pharmacy-native-filters" method="get">
              <input type="hidden" name="section" value="pending">
              <label class="search">
                Buscar
                <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Folio, paciente, medico o medicamento">
              </label>
              <label>
                Estado
                <select name="status" onchange="this.form.submit()">
                  <option value="all">Todos</option>
                  <option value="received" <?php if(request('status') === 'received'): echo 'selected'; endif; ?>>Pendiente</option>
                  <option value="preparing" <?php if(request('status') === 'preparing'): echo 'selected'; endif; ?>>Parcial</option>
                </select>
              </label>
            </form>
          <?php endif; ?>

          <div class="external-pharmacy-native-table-scroll">
            <table class="external-pharmacy-native-table">
              <thead>
                <tr>
                  <th>Folio</th>
                  <th>Paciente</th>
                  <th>Medico / servicio</th>
                  <th>Medicamento</th>
                  <th>Cantidad</th>
                  <th>Estado</th>
                  <th>Fecha</th>
                  <?php if($section === 'pending'): ?>
                    <th>Acciones</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $orderRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                      $prescribedQuantity = (int) data_get($item->metadata, 'prescribed_quantity', $item->quantity);
                      $filledQuantity = (int) data_get($item->metadata, 'filled_quantity', 0);
                      $remainingQuantity = max(0, $prescribedQuantity - $filledQuantity);
                      $itemStatus = $remainingQuantity === 0 ? 'Surtida' : ($filledQuantity > 0 ? 'Parcial' : 'Pendiente');
                    ?>
                    <tr>
                      <td><strong><?php echo e($order->order_number ?? 'RX-'.$order->id); ?></strong></td>
                      <td><strong><?php echo e($order->patient?->full_name ?? 'Sin paciente'); ?></strong></td>
                      <td>
                        <strong><?php echo e(data_get($order->metadata, 'doctor', 'Dra. Consulta Externa')); ?></strong>
                        <span><?php echo e(data_get($order->metadata, 'service', 'Consulta externa')); ?></span>
                      </td>
                      <td><?php echo e($item->product_name); ?></td>
                      <td><?php echo e($prescribedQuantity); ?> <?php echo e($prescribedQuantity === 1 ? 'pieza' : 'piezas'); ?><?php if($filledQuantity): ?><span><?php echo e($filledQuantity); ?> surtida(s) Â· <?php echo e($remainingQuantity); ?> pendiente(s)</span><?php endif; ?></td>
                      <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['external-pharmacy-native-status', 'is-filled' => $remainingQuantity === 0, 'is-partial' => $filledQuantity > 0 && $remainingQuantity > 0]); ?>"><?php echo e($itemStatus); ?></span></td>
                      <td><?php echo e($order->ordered_at?->format('d/m/Y') ?? 'Sin fecha'); ?></td>
                      <?php if($section === 'pending'): ?>
                        <td>
                          <?php if($remainingQuantity > 0 && $item->pharmacy_product_id): ?>
                          <form method="post" action="<?php echo e(route('external-pharmacy.items.dispense', $item)); ?>" class="external-pharmacy-dispense-form">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('patch'); ?>
                            <input type="hidden" name="quantity" value="<?php echo e($remainingQuantity); ?>">
                            <button type="submit"><?php echo e($filledQuantity ? 'Completar' : 'Surtir'); ?></button>
                          </form>
                          <?php else: ?>
                            <span><?php echo e($remainingQuantity === 0 ? 'Completo' : 'Sin catalogar'); ?></span>
                          <?php endif; ?>
                        </td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr>
                    <td colspan="<?php echo e($section === 'pending' ? 8 : 7); ?>">No hay recetas para esta vista.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'prescription'): ?>
        <section class="external-pharmacy-native-card external-prescription-paper">
          <div class="external-prescription-inner">
            <header>
              <div>
                <p class="eyebrow">Farmacia externa - Consulta externa</p>
                <h2>Formato de Receta Medica</h2>
                <strong><?php echo e($unitName); ?> - <?php echo e($unitCode); ?></strong>
              </div>
              <div>
                <span>Folio</span>
                <strong>RX-CE-000000</strong>
                <span>Fecha <?php echo e(now()->format('d/m/Y')); ?></span>
              </div>
            </header>

            <div class="external-prescription-select">
              <label>Paciente agendado en Consulta Externa<select><option>Sin pacientes agendados para esta unidad</option></select></label>
              <strong>Selecciona una cita de Consulta Externa para autollenar el formato.</strong>
            </div>

            <h3>Datos del paciente</h3>
            <div class="external-prescription-grid">
              <?php $__currentLoopData = ['Nombre(s)' => 'Sin paciente agendado', 'Apellidos' => 'Sin paciente agendado', 'CURP' => 'Sin CURP', 'NSS / registro' => 'Sin registro', 'Edad' => 'Sin edad', 'Sexo' => 'Sin sexo', 'Servicio' => 'Consulta externa', 'Consultorio' => 'Consultorio', 'Fecha de atencion' => now()->format('d/m/Y')]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div><span><?php echo e($label); ?></span><strong><?php echo e($value); ?></strong></div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <h3>Diagnostico y resumen clinico</h3>
            <div class="external-prescription-box">
              <label>Diagnostico presuntivo o definitivo<input value="Escribir diagnostico"></label>
              <label>Resumen clinico, exploracion, evolucion e indicaciones generales<textarea>Escribir resumen clinico</textarea></label>
            </div>

            <h3>Medicamentos indicados</h3>
            <div class="external-prescription-table-wrap" data-prescription-medications>
              <table class="external-prescription-table">
                <thead>
                  <tr>
                    <th>No.</th><th>Clave CNIS</th><th>Medicamento</th><th>Dosis</th><th>Presentacion</th><th>Via</th>
                    <th>Frecuencia <span>(x veces por dia)</span></th><th>Duracion</th><th>Cantidad</th><th>Indicaciones</th>
                  </tr>
                </thead>
                <tbody data-prescription-medication-rows>
                  <tr data-prescription-medication-row>
                    <td><div class="external-prescription-row-tools"><span data-prescription-medication-number>1</span><button type="button" class="external-prescription-remove-row" data-remove-prescription-medication aria-label="Eliminar medicamento">−</button></div></td>
                    <td><input name="medications[0][cnis]" placeholder="CNIS"></td>
                    <td><div class="external-prescription-medication-picker"><input name="medications[0][name]" list="external-prescription-products" placeholder="Escribir medicamento" autocomplete="off"><small>Buscar en catalogo de farmacia</small></div></td>
                    <td><input name="medications[0][dose]" placeholder="Dosis"></td>
                    <td><input name="medications[0][presentation]" placeholder="Presentacion"></td>
                    <td><input name="medications[0][route]" placeholder="Via"></td>
                    <td><input name="medications[0][frequency]" placeholder="Frecuencia"></td>
                    <td><input name="medications[0][duration]" placeholder="Duracion"></td>
                    <td><input name="medications[0][quantity]" type="number" min="0" step="1" placeholder="Piezas"></td>
                    <td><textarea name="medications[0][instructions]" rows="2" placeholder="Indicaciones"></textarea></td>
                  </tr>
                </tbody>
              </table>
              <div class="external-prescription-add-row-footer"><button type="button" data-add-prescription-medication aria-label="Agregar medicamento">+</button></div>
              <datalist id="external-prescription-products">
                <?php $__currentLoopData = $products->where('status', 'active'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($product->name); ?>"><?php echo e($product->cnis ?: $product->code); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </datalist>
            </div>

            <h3>Medico tratante</h3>
            <div class="external-prescription-grid">
              <?php $__currentLoopData = ['Nombre del medico' => 'Medico asignado', 'Cedula profesional' => 'Sin cedula', 'Especialidad' => 'Consulta externa', 'Firma y sello' => 'Sin firma y sello']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div><span><?php echo e($label); ?></span><strong><?php echo e($value); ?></strong></div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'inventory'): ?>
        <section class="external-pharmacy-native-card">
          <div class="external-pharmacy-native-heading external-heading-action">
            <div>
              <h2>Inventario operativo</h2>
              <p><?php echo e($inventory->count()); ?> visibles - <?php echo e($products->count()); ?> medicamentos</p>
            </div>
            <button type="button">Descargar</button>
          </div>
          <form class="external-pharmacy-native-filters">
            <label class="search">Buscar<input placeholder="CNIS, insumo, descripcion, almacen o lote"></label>
            <label>Estado<select><option>Todos</option></select></label>
          </form>
          <div class="external-pharmacy-native-table-scroll">
            <table class="external-pharmacy-native-table external-inventory-table">
              <thead><tr><th>Clave CNIS</th><th>Insumo</th><th>Existencia</th><th>Almacen</th><th>Lote / caducidad</th><th>Cobertura</th><th>Estado</th><th>Actualizado</th><th>Acciones</th></tr></thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $inventory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <tr>
                    <td><strong><?php echo e($item->product?->cnis ?? '010.000.0104.00'); ?></strong></td>
                    <td><strong><?php echo e($item->product?->name ?? 'Producto no localizado'); ?></strong><span><?php echo e($item->product?->description ?? $item->product?->generic_name ?? 'Catalogo operativo'); ?></span></td>
                    <td><strong><?php echo e($item->quantity); ?></strong><span>min <?php echo e(max(1, $item->quantity - 12)); ?> / max <?php echo e($item->quantity + 320); ?></span></td>
                    <td><span class="external-pill"><?php echo e($item->warehouse ?? 'Farmacia externa'); ?></span></td>
                    <td><strong><?php echo e($item->lot ?? data_get($item->metadata, 'lot', 'L-'.str_pad((string) $item->id, 5, '0', STR_PAD_LEFT))); ?></strong><span>Caduca <?php echo e($item->expires_at?->format('d/m/Y') ?? '14/05/2027'); ?></span></td>
                    <td><span class="external-pill">UM <?php echo e($item->product?->sector_health ? 'Si' : 'No'); ?></span> <span class="external-pill">NB Si</span> <span class="external-pill">CESSA Si</span></td>
                    <td><span class="external-pharmacy-native-status">Bajo</span></td>
                    <td><?php echo e($item->updated_at?->format('d/m/Y')); ?></td>
                    <td>
                      <a class="external-table-action" href="<?php echo e(route('external-pharmacy.dashboard', ['section' => 'inventory', 'movement_item' => $item->id, 'movement_type' => 'entry'])); ?>">Entrada</a>
                      <a class="external-table-action" href="<?php echo e(route('external-pharmacy.dashboard', ['section' => 'inventory', 'movement_item' => $item->id, 'movement_type' => 'exit'])); ?>">Salida</a>
                    </td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="9">Sin inventario operativo registrado.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <?php if($selectedMovementItem): ?>
            <div class="external-inventory-movement-panel">
              <div class="external-pharmacy-native-heading">
                <div>
                  <h2>Registrar movimiento</h2>
                  <p><?php echo e($selectedMovementItem->product?->name); ?> Â· existencia actual <?php echo e($selectedMovementItem->quantity); ?></p>
                </div>
                <a class="external-table-action" href="<?php echo e(route('external-pharmacy.dashboard', ['section' => 'inventory'])); ?>">Cerrar</a>
              </div>
              <form method="post" action="<?php echo e(route('external-pharmacy.inventory.movements.store', $selectedMovementItem)); ?>" class="external-warehouse-form">
                <?php echo csrf_field(); ?>
                <label>Tipo
                  <select name="type">
                    <option value="entry" <?php if(request('movement_type') === 'entry'): echo 'selected'; endif; ?>>Entrada</option>
                    <option value="exit" <?php if(request('movement_type') === 'exit'): echo 'selected'; endif; ?>>Salida</option>
                    <option value="adjustment">Ajuste de existencia</option>
                  </select>
                </label>
                <label>Cantidad<input name="quantity" type="number" min="0" required value="<?php echo e(old('quantity', 1)); ?>"></label>
                <label>Notas<input name="notes" maxlength="1000" value="<?php echo e(old('notes')); ?>" placeholder="Motivo u observaciones"></label>
                <button type="submit">Guardar movimiento</button>
              </form>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <?php if($section === 'movements'): ?>
        <section class="external-pharmacy-native-card">
          <div class="external-pharmacy-native-heading external-heading-action">
            <div>
              <h2>Movimientos de inventario</h2>
              <p><?php echo e($movements->count()); ?> movimientos visibles</p>
            </div>
            <button type="button">Descargar</button>
          </div>
          <form class="external-pharmacy-native-filters">
            <label class="search">Buscar<input placeholder="CNIS, insumo, usuario, almacen o nota"></label>
            <label>Tipo<select><option>Todos</option></select></label>
          </form>
          <div class="external-pharmacy-native-table-scroll">
            <table class="external-pharmacy-native-table">
              <thead><tr><th>Fecha</th><th>Tipo</th><th>Clave CNIS</th><th>Insumo</th><th>Cantidad</th><th>Almacen</th><th>Usuario / origen</th><th>Notas</th></tr></thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $movements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $movement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <tr>
                    <td><?php echo e($movement->created_at?->format('d M Y, h:i p')); ?></td>
                    <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['external-pharmacy-native-status', 'is-filled' => $movement->type === 'entry', 'is-partial' => $movement->type === 'adjustment']); ?>"><?php echo e(['entry' => 'Entrada', 'exit' => 'Salida', 'adjustment' => 'Ajuste'][$movement->type] ?? $movement->type); ?></span></td>
                    <td><strong><?php echo e($movement->inventoryItem?->product?->cnis ?? 'Sin CNIS'); ?></strong></td>
                    <td><?php echo e($movement->inventoryItem?->product?->name ?? 'Producto'); ?></td>
                    <td><?php echo e($movement->quantity); ?> <span><?php echo e($movement->stock_before); ?> â†’ <?php echo e($movement->stock_after); ?></span></td>
                    <td><span class="external-pill"><?php echo e($movement->inventoryItem?->warehouse ?? 'Farmacia externa'); ?></span></td>
                    <td><strong><?php echo e($movement->user?->name ?? $areaName); ?></strong><span><?php echo e($movement->source); ?></span></td>
                    <td><?php echo e($movement->notes ?: 'Sin notas.'); ?></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="8">Sin movimientos registrados.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <?php if($section === 'warehouses'): ?>
        <section class="external-warehouse-grid">
          <article class="external-pharmacy-native-card">
            <div class="external-pharmacy-native-heading"><h2>Nuevo almacen</h2><p>Alta rapida de ubicaciones de resguardo.</p></div>
            <form class="external-warehouse-form" method="post" action="<?php echo e(route('external-pharmacy.warehouses.store')); ?>">
              <?php echo csrf_field(); ?>
              <label>Nombre<input name="name" required value="<?php echo e(old('name')); ?>" placeholder="Ej. Ventanilla de farmacia externa"></label>
              <label>Tipo<select name="type"><option value="general">General</option><option value="dispensing">Dispensacion</option><option value="controlled">Controlados</option><option value="cold_chain">Cadena fria</option></select></label>
              <label>Responsable<input name="responsible" value="<?php echo e(old('responsible')); ?>" placeholder="Responsable operativo"></label>
              <button type="submit">Guardar almacen</button>
            </form>
          </article>
          <article class="external-pharmacy-native-card">
            <div class="external-pharmacy-native-heading external-warehouse-heading">
              <h2>Almacenes activos</h2>
              <p><?php echo e($warehouses->count()); ?> <?php echo e($warehouses->count() === 1 ? 'almacen' : 'almacenes'); ?></p>
            </div>
            <div class="external-warehouse-list">
              <?php $__empty_1 = true; $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warehouse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article class="external-warehouse-row">
                  <div class="external-warehouse-identity">
                    <strong><?php echo e($warehouse->name); ?></strong>
                    <span>
                      <?php echo e(['general' => 'General', 'dispensing' => 'Dispensacion externa', 'controlled' => 'Controlados', 'cold_chain' => 'Cadena fria'][$warehouse->type] ?? ucfirst(str_replace('_', ' ', $warehouse->type))); ?>

                      - <?php echo e($warehouse->responsible ?: 'Sin responsable'); ?>

                    </span>
                  </div>
                  <?php
                    $warehouseInventory = $warehouseRows->get($warehouse->name, collect());
                  ?>
                  <div class="external-warehouse-totals">
                    <span><?php echo e($warehouseInventory->count()); ?> claves</span>
                    <span><?php echo e($warehouseInventory->sum('quantity')); ?> unidades</span>
                  </div>
                  <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['external-pharmacy-native-status', 'is-filled' => $warehouse->status === 'active', 'is-cancelled' => $warehouse->status !== 'active']); ?>">
                    <?php echo e($warehouse->status === 'active' ? 'Activo' : 'Inactivo'); ?>

                  </span>
                  <form method="post" action="<?php echo e(route('external-pharmacy.warehouses.status', $warehouse)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('patch'); ?>
                    <input type="hidden" name="status" value="<?php echo e($warehouse->status === 'active' ? 'inactive' : 'active'); ?>">
                    <button type="submit"><?php echo e($warehouse->status === 'active' ? 'Inactivar' : 'Activar'); ?></button>
                  </form>
                </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="external-warehouse-empty">Sin almacenes registrados.</p>
              <?php endif; ?>
            </div>
          </article>
        </section>
      <?php endif; ?>

      <?php if($section === 'catalog'): ?>
        <section class="external-pharmacy-native-card">
          <div class="external-pharmacy-native-heading">
            <h2>Catalogo de farmacia</h2>
            <p><?php echo e($products->count()); ?> visibles - <?php echo e($products->count()); ?> medicamentos - <?php echo e($products->where('status', 'active')->count()); ?> activos / <?php echo e($products->where('status', '!=', 'active')->count()); ?> inactivos</p>
          </div>
          <form class="external-pharmacy-native-filters">
            <label class="search">Buscar<input placeholder="CNIS, medicamento, descripcion o grupo"></label>
            <label>Estado<select><option>Todos</option></select></label>
          </form>
          <div class="external-pharmacy-native-table-scroll">
            <table class="external-pharmacy-native-table">
              <thead><tr><th>Clave CNIS</th><th>Medicamento</th><th>Descripcion</th><th>Grupo</th><th>Cobertura</th><th>Estado</th><th>Actualizado</th></tr></thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <tr>
                    <td><strong><?php echo e($product->cnis ?? '010.000.0104.00'); ?></strong></td>
                    <td><strong><?php echo e($product->name); ?></strong><span><?php echo e($product->code ?? 'med-cnis-'.$product->id); ?></span></td>
                    <td><?php echo e($product->description ?? $product->generic_name ?? 'Descripcion de catalogo de farmacia externa'); ?></td>
                    <td><?php echo e(data_get($product->metadata, 'group', 'GRUPO NOI: ANALGESIA GRUPO N023: CUIDADOS PALIATIVOS')); ?></td>
                    <td><span class="external-pill">UM <?php echo e($product->sector_health ? 'Si' : 'No'); ?></span> <span class="external-pill">NB Si</span> <span class="external-pill">CESSA Si</span></td>
                    <td><span class="external-pharmacy-native-status is-filled"><?php echo e($statusText($product->status)); ?></span></td>
                    <td><?php echo e($product->updated_at?->format('d/m/Y')); ?></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="7">Sin productos registrados.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>
    </section>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
  <?php if($section === 'prescription'): ?>
    <template id="external-prescription-medication-template">
      <tr data-prescription-medication-row>
        <td><div class="external-prescription-row-tools"><span data-prescription-medication-number></span><button type="button" class="external-prescription-remove-row" data-remove-prescription-medication aria-label="Eliminar medicamento">−</button></div></td>
        <td><input data-field="cnis" placeholder="CNIS"></td>
        <td><div class="external-prescription-medication-picker"><input data-field="name" list="external-prescription-products" placeholder="Escribir medicamento" autocomplete="off"><small>Buscar en catalogo de farmacia</small></div></td>
        <td><input data-field="dose" placeholder="Dosis"></td><td><input data-field="presentation" placeholder="Presentacion"></td>
        <td><input data-field="route" placeholder="Via"></td><td><input data-field="frequency" placeholder="Frecuencia"></td>
        <td><input data-field="duration" placeholder="Duracion"></td><td><input data-field="quantity" type="number" min="0" step="1" placeholder="Piezas"></td>
        <td><textarea data-field="instructions" rows="2" placeholder="Indicaciones"></textarea></td>
      </tr>
    </template>
    <script>
      (() => {
        const container = document.querySelector('[data-prescription-medications]');
        const rows = container?.querySelector('[data-prescription-medication-rows]');
        const template = document.getElementById('external-prescription-medication-template');
        if (!container || !rows || !template) return;

        const renumber = () => {
          rows.querySelectorAll('[data-prescription-medication-row]').forEach((row, index) => {
            row.querySelector('[data-prescription-medication-number]').textContent = index + 1;
            row.querySelectorAll('input, textarea').forEach((field) => {
              const key = field.dataset.field || field.name.match(/\[([^\]]+)\]$/)?.[1];
              if (key) field.name = `medications[${index}][${key}]`;
            });
          });
        };

        container.addEventListener('click', (event) => {
          if (event.target.closest('[data-add-prescription-medication]')) {
            rows.append(template.content.cloneNode(true));
            renumber();
            rows.lastElementChild?.querySelector('input')?.focus();
            return;
          }
          const remove = event.target.closest('[data-remove-prescription-medication]');
          if (!remove) return;
          const row = remove.closest('[data-prescription-medication-row]');
          if (rows.children.length === 1) {
            row.querySelectorAll('input, textarea').forEach((field) => field.value = '');
          } else {
            row.remove();
            renumber();
          }
        });
      })();
    </script>
  <?php endif; ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Farmacia Externa'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views\external_pharmacy\dashboard.blade.php ENDPATH**/ ?>