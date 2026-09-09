@extends('layouts.app', ['title' => 'Farmacia Externa'])

@section('body_class', 'operational-native-body external-pharmacy-native-body external-pharmacy-module-body')

@php
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
  $institutionName = $profile?->medicalUnit?->institution?->name ?? 'IMSS BIENESTAR ESTADO DE MEXICO';
  $areaName = $profile?->area?->label ?? 'Area Operativa Farmacia';
  $menu = [
    'pending' => ['R', 'Recetas pendientes'],
    'filled' => ['S', 'Recetas surtidas'],
    'prescription' => ['F', 'Formato de Receta Medica'],
    'inventory' => ['I', 'Inventario'],
    'movements' => ['M', 'Movimientos'],
    'warehouses' => ['A', 'Almacenes'],
    'catalog' => ['C', 'Catalogo de farmacia'],
  ];
  $patientCatalogRouteParameters = $profile?->medicalUnit ? ['unit' => $profile->medicalUnit->id] : [];
  $areaDefaultRoutes = [
    'nursing' => ['area' => 'nursing', 'section' => 'pending'],
    'oncology' => ['area' => 'oncology', 'section' => 'calendar', 'oncology_track' => 'infusions'],
    'inpatient-pharmacy' => ['area' => 'inpatient-pharmacy', 'section' => 'pending'],
  ];
  $orderRows = $section === 'filled' ? $filledOrders : $pendingOrders;
  $warehouseRows = $inventory->groupBy(fn ($item) => $item->warehouse ?: 'Farmacia externa');
  $selectedMovementItem = $inventory->firstWhere('id', (int) request('movement_item'));
@endphp

@section('content')
  <div class="operational-native-screen external-pharmacy-operational-screen">
    <header class="operational-native-topbar" aria-label="Barra superior operativa">
      <strong>MODULO OPERATIVO</strong>
      <span>{{ strtoupper($institutionName) }}</span>
      <div class="operational-native-user">
        <button type="button" aria-label="Notificaciones">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg>
        </button>
        <div>
          <strong>Usuario activo</strong>
          <small>{{ $unitName }}</small>
        </div>
        <form method="post" action="{{ route('logout') }}" class="operational-native-logout">
          @csrf
          <button type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/></svg>
            Cerrar sesion
          </button>
        </form>
      </div>
    </header>

    <aside class="operational-native-sidebar" aria-label="Navegacion operativa">
      <nav class="operational-native-menu operational-area-menu">
        <a href="{{ route('operational.dashboard', $areaDefaultRoutes['nursing']) }}">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 21V5a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v16"/><path d="M16 9h3a1 1 0 0 1 1 1v11"/><path d="M8 8h4M10 6v4M8 14h4M8 18h4"/></svg>
          </span>
          Hospitalizacion
          <i aria-hidden="true">›</i>
        </a>
        <a href="{{ route('operational.dashboard', $areaDefaultRoutes['oncology']) }}">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 6h16v15H4z"/><path d="M9 3h6v3H9z"/><path d="M12 10v7M8.5 13.5h7"/></svg>
          </span>
          Centro Oncologico
          <i aria-hidden="true">›</i>
        </a>
        <a href="{{ route('operational.dashboard', $areaDefaultRoutes['inpatient-pharmacy']) }}">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="m7 16 9-9a3 3 0 0 1 4 4l-9 9a3 3 0 0 1-4-4Z"/><path d="m12 11 4 4"/></svg>
          </span>
          Farmacia intrahospitalaria
          <i aria-hidden="true">›</i>
        </a>
        <a href="{{ route('outpatient.dashboard') }}">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M6 4v5a5 5 0 0 0 10 0V4"/><path d="M9 4H5"/><path d="M17 4h-4"/><path d="M11 14v2a4 4 0 0 0 8 0v-3"/><circle cx="19" cy="10" r="2"/></svg>
          </span>
          Consulta Externa
          <i aria-hidden="true">›</i>
        </a>
        <a class="is-active" href="{{ route('external-pharmacy.dashboard') }}">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 9v11h16V9"/><path d="M3 9h18l-2-5H5L3 9Z"/><path d="M12 12v5M9.5 14.5h5"/></svg>
          </span>
          Farmacia Externa
          <i aria-hidden="true">›</i>
        </a>
        <a href="{{ route('operational.patients.index', $patientCatalogRouteParameters) }}">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </span>
          Catalogo de pacientes
          <i aria-hidden="true">›</i>
        </a>
      </nav>
    </aside>

    <section class="operational-native-workspace external-pharmacy-module-main">
      @if (session('status'))
        <div class="notice success">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="notice danger">
          <strong>No se pudo actualizar.</strong>
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <section class="operational-oncology-carousel operational-compact-carousel external-pharmacy-module-carousel" aria-label="Modulo de Farmacia Externa" data-external-pharmacy-carousel>
        <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-prev aria-label="Anterior">‹</button>
        <div class="operational-oncology-carousel-track">
          <a class="operational-oncology-filter is-active" href="{{ route('external-pharmacy.dashboard', ['section' => 'pending']) }}">
            <span class="operational-oncology-filter-initial" aria-hidden="true">F</span>
            <span class="operational-oncology-filter-copy">
              <strong>Farmacia Externa</strong>
              <small>✓ Seleccionada</small>
            </span>
          </a>
        </div>
        <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-next aria-label="Siguiente">›</button>
      </section>

      <nav class="operational-section-tabs external-pharmacy-module-tabs" aria-label="Secciones de Farmacia Externa">
        @foreach ($menu as $key => [$icon, $label])
          <a @class(['is-active' => $section === $key]) href="{{ route('external-pharmacy.dashboard', ['section' => $key]) }}">{{ $label }}</a>
        @endforeach
      </nav>

      @if (in_array($section, ['pending', 'filled'], true))
        <section class="external-pharmacy-native-card operational-native-table-card operational-section-card external-pharmacy-module-card">
          <div class="external-pharmacy-native-heading operational-native-table-heading">
            <div>
              <p class="eyebrow">{{ $section === 'filled' ? 'Historial de surtimiento' : 'Recetas activas' }}</p>
              <h2>{{ $section === 'filled' ? 'Recetas surtidas' : 'Recetas y surtimiento' }}</h2>
                @php
                    $visibleMedicationCount = $orderRows->sum(function ($order) {
                        return $order->items->count();
                    });
                @endphp
              <p>{{ $orderRows->count() }} recetas - {{ $visibleMedicationCount }} medicamentos {{ $section === 'filled' ? 'surtidos' : 'visibles de '.$visibleMedicationCount }}</p>
            </div>
          </div>

          @if ($section === 'pending')
            <form class="external-pharmacy-native-filters" method="get">
              <input type="hidden" name="section" value="pending">
              <label class="search">
                Buscar
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Folio, paciente, medico o medicamento">
              </label>
              <label>
                Estado
                <select name="status" onchange="this.form.submit()">
                  <option value="all">Todos</option>
                  <option value="received" @selected(request('status') === 'received')>Pendiente</option>
                  <option value="preparing" @selected(request('status') === 'preparing')>Parcial</option>
                </select>
              </label>
            </form>
          @endif

          <div class="external-pharmacy-native-table-scroll operational-native-table-scroll">
            <table class="external-pharmacy-native-table operational-native-table">
              <thead>
                <tr>
                  <th>Folio</th>
                  <th>Paciente</th>
                  <th>Medico / servicio</th>
                  <th>Medicamento</th>
                  <th>Cantidad</th>
                  <th>Estado</th>
                  <th>Fecha</th>
                  @if ($section === 'pending')
                    <th>Acciones</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @forelse ($orderRows as $order)
                  @foreach ($order->items as $item)
                    @php
                      $prescribedQuantity = (int) data_get($item->metadata, 'prescribed_quantity', $item->quantity);
                      $orderIsFilled = in_array($order->status, ['in_route', 'delivered'], true);
                      $filledQuantity = $orderIsFilled
                        ? $prescribedQuantity
                        : (int) data_get($item->metadata, 'filled_quantity', 0);
                      $remainingQuantity = max(0, $prescribedQuantity - $filledQuantity);
                      $itemStatus = $remainingQuantity === 0 ? 'Surtida' : ($filledQuantity > 0 ? 'Parcial' : 'Pendiente');
                    @endphp
                    <tr>
                      <td><strong>{{ $order->order_number ?? 'RX-'.$order->id }}</strong></td>
                      <td><strong>{{ $order->patient?->full_name ?? 'Sin paciente' }}</strong></td>
                      <td>
                        <strong>{{ data_get($order->metadata, 'doctor', 'Dra. Consulta Externa') }}</strong>
                        <span>{{ data_get($order->metadata, 'service', 'Consulta externa') }}</span>
                      </td>
                      <td>{{ $item->product_name }}</td>
                      <td>{{ $prescribedQuantity }} {{ $prescribedQuantity === 1 ? 'pieza' : 'piezas' }}@if($filledQuantity)<span>{{ $filledQuantity }} surtida(s) Â· {{ $remainingQuantity }} pendiente(s)</span>@endif</td>
                      <td><span @class(['external-pharmacy-native-status', 'is-filled' => $remainingQuantity === 0, 'is-partial' => $filledQuantity > 0 && $remainingQuantity > 0])>{{ $itemStatus }}</span></td>
                      <td>{{ $order->ordered_at?->format('d/m/Y') ?? 'Sin fecha' }}</td>
                      @if ($section === 'pending')
                        <td>
                          @if($remainingQuantity > 0 && $item->pharmacy_product_id)
                          <form method="post" action="{{ route('external-pharmacy.items.dispense', $item) }}" class="external-pharmacy-dispense-form">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="quantity" value="{{ $remainingQuantity }}">
                            <button type="submit">{{ $filledQuantity ? 'Completar' : 'Surtir' }}</button>
                          </form>
                          @else
                            <span>{{ $remainingQuantity === 0 ? 'Completo' : 'Sin catalogar' }}</span>
                          @endif
                        </td>
                      @endif
                    </tr>
                  @endforeach
                @empty
                  <tr>
                    <td colspan="{{ $section === 'pending' ? 8 : 7 }}">No hay recetas para esta vista.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      @endif

      @if ($section === 'prescription')
        <section class="external-pharmacy-native-card operational-section-card external-prescription-paper external-pharmacy-module-card">
          <div class="external-prescription-inner">
            <header>
              <div>
                <p class="eyebrow">Farmacia externa - Consulta externa</p>
                <h2>Formato de Receta Medica</h2>
                <strong>{{ $unitName }} - {{ $unitCode }}</strong>
              </div>
              <div>
                <span>Folio</span>
                <strong>RX-CE-000000</strong>
                <span>Fecha {{ now()->format('d/m/Y') }}</span>
              </div>
            </header>

            <div class="external-prescription-select">
              <label>Paciente agendado en Consulta Externa<select><option>Sin pacientes agendados para esta unidad</option></select></label>
              <strong>Selecciona una cita de Consulta Externa para autollenar el formato.</strong>
            </div>

            <h3>Datos del paciente</h3>
            <div class="external-prescription-grid">
              @foreach (['Nombre(s)' => 'Sin paciente agendado', 'Apellidos' => 'Sin paciente agendado', 'CURP' => 'Sin CURP', 'NSS / registro' => 'Sin registro', 'Edad' => 'Sin edad', 'Sexo' => 'Sin sexo', 'Servicio' => 'Consulta externa', 'Consultorio' => 'Consultorio', 'Fecha de atencion' => now()->format('d/m/Y')] as $label => $value)
                <div><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
              @endforeach
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
                @foreach ($products->where('status', 'active') as $product)
                  <option value="{{ $product->name }}">{{ $product->cnis ?: $product->code }}</option>
                @endforeach
              </datalist>
            </div>

            <h3>Medico tratante</h3>
            <div class="external-prescription-grid">
              @foreach (['Nombre del medico' => 'Medico asignado', 'Cedula profesional' => 'Sin cedula', 'Especialidad' => 'Consulta externa', 'Firma y sello' => 'Sin firma y sello'] as $label => $value)
                <div><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
              @endforeach
            </div>
          </div>
        </section>
      @endif

      @if ($section === 'inventory')
        <section class="external-pharmacy-native-card operational-native-table-card operational-section-card external-pharmacy-module-card">
          <div class="external-pharmacy-native-heading operational-native-table-heading external-heading-action">
            <div>
              <p class="eyebrow">Inventario operativo</p>
              <h2>Inventario de farmacia externa</h2>
              <p>{{ $inventory->count() }} registros visibles - {{ $products->count() }} medicamentos</p>
            </div>
            <button type="button">Descargar</button>
          </div>
          <form class="external-pharmacy-native-filters">
            <label class="search">Buscar<input placeholder="CNIS, insumo, descripcion, almacen o lote"></label>
            <label>Estado<select><option>Todos</option></select></label>
          </form>
          <div class="external-pharmacy-native-table-scroll operational-native-table-scroll">
            <table class="external-pharmacy-native-table operational-native-table external-inventory-table">
              <thead><tr><th>Clave CNIS</th><th>Insumo</th><th>Existencia</th><th>Almacen</th><th>Lote / caducidad</th><th>Cobertura</th><th>Estado</th><th>Actualizado</th><th>Acciones</th></tr></thead>
              <tbody>
                @forelse ($inventory as $item)
                  <tr>
                    <td><strong>{{ $item->product?->cnis ?? '010.000.0104.00' }}</strong></td>
                    <td><strong>{{ $item->product?->name ?? 'Producto no localizado' }}</strong><span>{{ $item->product?->description ?? $item->product?->generic_name ?? 'Catalogo operativo' }}</span></td>
                    <td><strong>{{ $item->quantity }}</strong><span>min {{ max(1, $item->quantity - 12) }} / max {{ $item->quantity + 320 }}</span></td>
                    <td><span class="external-pill">{{ $item->warehouse ?? 'Farmacia externa' }}</span></td>
                    <td><strong>{{ $item->lot ?? data_get($item->metadata, 'lot', 'L-'.str_pad((string) $item->id, 5, '0', STR_PAD_LEFT)) }}</strong><span>Caduca {{ $item->expires_at?->format('d/m/Y') ?? '14/05/2027' }}</span></td>
                    <td><span class="external-pill">UM {{ $item->product?->sector_health ? 'Si' : 'No' }}</span> <span class="external-pill">NB Si</span> <span class="external-pill">CESSA Si</span></td>
                    <td><span class="external-pharmacy-native-status">Bajo</span></td>
                    <td>{{ $item->updated_at?->format('d/m/Y') }}</td>
                    <td>
                      <a class="external-table-action" href="{{ route('external-pharmacy.dashboard', ['section' => 'inventory', 'movement_item' => $item->id, 'movement_type' => 'entry']) }}">Entrada</a>
                      <a class="external-table-action" href="{{ route('external-pharmacy.dashboard', ['section' => 'inventory', 'movement_item' => $item->id, 'movement_type' => 'exit']) }}">Salida</a>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="9">Sin inventario operativo registrado.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if($selectedMovementItem)
            <div class="external-inventory-movement-panel">
              <div class="external-pharmacy-native-heading operational-native-table-heading">
                <div>
                  <h2>Registrar movimiento</h2>
                  <p>{{ $selectedMovementItem->product?->name }} Â· existencia actual {{ $selectedMovementItem->quantity }}</p>
                </div>
                <a class="external-table-action" href="{{ route('external-pharmacy.dashboard', ['section' => 'inventory']) }}">Cerrar</a>
              </div>
              <form method="post" action="{{ route('external-pharmacy.inventory.movements.store', $selectedMovementItem) }}" class="external-warehouse-form">
                @csrf
                <label>Tipo
                  <select name="type">
                    <option value="entry" @selected(request('movement_type') === 'entry')>Entrada</option>
                    <option value="exit" @selected(request('movement_type') === 'exit')>Salida</option>
                    <option value="adjustment">Ajuste de existencia</option>
                  </select>
                </label>
                <label>Cantidad<input name="quantity" type="number" min="0" required value="{{ old('quantity', 1) }}"></label>
                <label>Notas<input name="notes" maxlength="1000" value="{{ old('notes') }}" placeholder="Motivo u observaciones"></label>
                <button type="submit">Guardar movimiento</button>
              </form>
            </div>
          @endif
        </section>
      @endif

      @if ($section === 'movements')
        <section class="external-pharmacy-native-card operational-native-table-card operational-section-card external-pharmacy-module-card">
          <div class="external-pharmacy-native-heading operational-native-table-heading external-heading-action">
            <div>
              <p class="eyebrow">Movimientos de inventario</p>
              <h2>Bitacora de movimientos</h2>
              <p>{{ $movements->count() }} movimientos visibles</p>
            </div>
            <button type="button">Descargar</button>
          </div>
          <form class="external-pharmacy-native-filters">
            <label class="search">Buscar<input placeholder="CNIS, insumo, usuario, almacen o nota"></label>
            <label>Tipo<select><option>Todos</option></select></label>
          </form>
          <div class="external-pharmacy-native-table-scroll operational-native-table-scroll">
            <table class="external-pharmacy-native-table operational-native-table">
              <thead><tr><th>Fecha</th><th>Tipo</th><th>Clave CNIS</th><th>Insumo</th><th>Cantidad</th><th>Almacen</th><th>Usuario / origen</th><th>Notas</th></tr></thead>
              <tbody>
                @forelse ($movements as $movement)
                  <tr>
                    <td>{{ $movement->created_at?->format('d M Y, h:i p') }}</td>
                    <td><span @class(['external-pharmacy-native-status', 'is-filled' => $movement->type === 'entry', 'is-partial' => $movement->type === 'adjustment'])>{{ ['entry' => 'Entrada', 'exit' => 'Salida', 'adjustment' => 'Ajuste'][$movement->type] ?? $movement->type }}</span></td>
                    <td><strong>{{ $movement->inventoryItem?->product?->cnis ?? 'Sin CNIS' }}</strong></td>
                    <td>{{ $movement->inventoryItem?->product?->name ?? 'Producto' }}</td>
                    <td>{{ $movement->quantity }} <span>{{ $movement->stock_before }} â†’ {{ $movement->stock_after }}</span></td>
                    <td><span class="external-pill">{{ $movement->inventoryItem?->warehouse ?? 'Farmacia externa' }}</span></td>
                    <td><strong>{{ $movement->user?->name ?? $areaName }}</strong><span>{{ $movement->source }}</span></td>
                    <td>{{ $movement->notes ?: 'Sin notas.' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="8">Sin movimientos registrados.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      @endif

      @if ($section === 'warehouses')
        <section class="external-pharmacy-warehouse-section operational-section-card">
          <header class="external-pharmacy-section-heading">
            <p class="eyebrow">Farmacia externa</p>
            <h2>Almacenes externos</h2>
            <p>Administra ubicaciones de resguardo, responsables y disponibilidad.</p>
          </header>
          <div class="external-warehouse-grid">
          <article class="external-pharmacy-native-card">
            <div class="external-pharmacy-native-heading"><h2>Nuevo almacen</h2><p>Alta rapida de ubicaciones de resguardo.</p></div>
            <form class="external-warehouse-form" method="post" action="{{ route('external-pharmacy.warehouses.store') }}">
              @csrf
              <label>Nombre<input name="name" required value="{{ old('name') }}" placeholder="Ej. Ventanilla de farmacia externa"></label>
              <label>Tipo<select name="type"><option value="general">General</option><option value="dispensing">Dispensacion</option><option value="controlled">Controlados</option><option value="cold_chain">Cadena fria</option></select></label>
              <label>Responsable<input name="responsible" value="{{ old('responsible') }}" placeholder="Responsable operativo"></label>
              <button type="submit">Guardar almacen</button>
            </form>
          </article>
          <article class="external-pharmacy-native-card">
            <div class="external-pharmacy-native-heading external-warehouse-heading">
              <h2>Almacenes activos</h2>
              <p>{{ $warehouses->count() }} {{ $warehouses->count() === 1 ? 'almacen' : 'almacenes' }}</p>
            </div>
            <div class="external-warehouse-list">
              @forelse ($warehouses as $warehouse)
                <article class="external-warehouse-row">
                  <div class="external-warehouse-identity">
                    <strong>{{ $warehouse->name }}</strong>
                    <span>
                      {{ ['general' => 'General', 'dispensing' => 'Dispensacion externa', 'controlled' => 'Controlados', 'cold_chain' => 'Cadena fria'][$warehouse->type] ?? ucfirst(str_replace('_', ' ', $warehouse->type)) }}
                      - {{ $warehouse->responsible ?: 'Sin responsable' }}
                    </span>
                  </div>
                  @php
                    $warehouseInventory = $warehouseRows->get($warehouse->name, collect());
                  @endphp
                  <div class="external-warehouse-totals">
                    <span>{{ $warehouseInventory->count() }} claves</span>
                    <span>{{ $warehouseInventory->sum('quantity') }} unidades</span>
                  </div>
                  <span @class(['external-pharmacy-native-status', 'is-filled' => $warehouse->status === 'active', 'is-cancelled' => $warehouse->status !== 'active'])>
                    {{ $warehouse->status === 'active' ? 'Activo' : 'Inactivo' }}
                  </span>
                  <form method="post" action="{{ route('external-pharmacy.warehouses.status', $warehouse) }}">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="status" value="{{ $warehouse->status === 'active' ? 'inactive' : 'active' }}">
                    <button type="submit">{{ $warehouse->status === 'active' ? 'Inactivar' : 'Activar' }}</button>
                  </form>
                </article>
              @empty
                <p class="external-warehouse-empty">Sin almacenes registrados.</p>
              @endforelse
            </div>
          </article>
          </div>
        </section>
      @endif

      @if ($section === 'catalog')
        <section class="external-pharmacy-native-card operational-native-table-card operational-section-card external-pharmacy-module-card">
          <div class="external-pharmacy-native-heading operational-native-table-heading">
            <div>
              <p class="eyebrow">Farmacia externa</p>
              <h2>Catalogo de farmacia</h2>
              <p>{{ $products->count() }} visibles - {{ $products->count() }} medicamentos - {{ $products->where('status', 'active')->count() }} activos / {{ $products->where('status', '!=', 'active')->count() }} inactivos</p>
            </div>
          </div>
          <form class="external-pharmacy-native-filters">
            <label class="search">Buscar<input placeholder="CNIS, medicamento, descripcion o grupo"></label>
            <label>Estado<select><option>Todos</option></select></label>
          </form>
          <div class="external-pharmacy-native-table-scroll operational-native-table-scroll">
            <table class="external-pharmacy-native-table operational-native-table">
              <thead><tr><th>Clave CNIS</th><th>Medicamento</th><th>Descripcion</th><th>Grupo</th><th>Cobertura</th><th>Estado</th><th>Actualizado</th></tr></thead>
              <tbody>
                @forelse ($products as $product)
                  <tr>
                    <td><strong>{{ $product->cnis ?? '010.000.0104.00' }}</strong></td>
                    <td><strong>{{ $product->name }}</strong><span>{{ $product->code ?? 'med-cnis-'.$product->id }}</span></td>
                    <td>{{ $product->description ?? $product->generic_name ?? 'Descripcion de catalogo de farmacia externa' }}</td>
                    <td>{{ data_get($product->metadata, 'group', 'GRUPO NOI: ANALGESIA GRUPO N023: CUIDADOS PALIATIVOS') }}</td>
                    <td><span class="external-pill">UM {{ $product->sector_health ? 'Si' : 'No' }}</span> <span class="external-pill">NB Si</span> <span class="external-pill">CESSA Si</span></td>
                    <td><span class="external-pharmacy-native-status is-filled">{{ $statusText($product->status) }}</span></td>
                    <td>{{ $product->updated_at?->format('d/m/Y') }}</td>
                  </tr>
                @empty
                  <tr><td colspan="7">Sin productos registrados.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      @endif
    </section>
  </div>
@endsection

@push('scripts')
  <script>
    (() => {
      document.querySelectorAll('[data-operational-carousel-prev], [data-operational-carousel-next]').forEach((button) => {
        button.addEventListener('click', () => {
          const carousel = button.closest('[data-external-pharmacy-carousel]');
          const track = carousel?.querySelector('.operational-oncology-carousel-track');
          if (!track) return;

          track.scrollBy({
            left: button.matches('[data-operational-carousel-prev]') ? -260 : 260,
            behavior: 'smooth',
          });
        });
      });
    })();
  </script>
  @if ($section === 'prescription')
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
  @endif
@endpush
