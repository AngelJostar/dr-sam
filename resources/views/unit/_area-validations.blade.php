@php
  $validationAreas = \App\Support\UnitAreaCatalog::navigation();
  $validationRules = \App\Support\UnitAreaValidations::rules($unit);
  $validationStates = ['active' => 'Activa', 'draft' => 'Borrador', 'inactive' => 'Inactiva'];
  $validationHistory = \App\Models\AuditLog::query()->with('user')->where('auditable_type', \App\Models\MedicalUnit::class)
      ->where('auditable_id', $unit->id)->where('event', 'unit.area_validation.saved')->latest('id')->limit(100)->get();
@endphp
@push('styles')
  <link rel="stylesheet" href="{{ asset('css/unit-area-validations.css') }}?v={{ filemtime(public_path('css/unit-area-validations.css')) }}">
@endpush

<section data-area-pane="validations" data-area-validations hidden>
  <header class="unit-native-table-heading">
    <div><h2>Validaciones por área operativa</h2><p>Define qué áreas deben dar su visto bueno o autorizar las solicitudes de otras áreas.</p></div>
    <div class="unit-validation-actions">
      <button type="button" class="unit-native-button" data-validation-history>Historial de cambios</button>
      <button type="button" class="unit-nutrition-new-request" data-validation-new>+ Nueva regla</button>
    </div>
  </header>
  <div class="unit-validation-filters">
    <label><span class="sr-only">Buscar área operativa</span><input type="search" placeholder="Buscar área operativa" data-validation-search></label>
    <label><span class="sr-only">Estado de las reglas</span><select data-validation-state><option value="all">Todos los estados</option><option value="none">Sin configurar</option><option value="draft">Borrador</option><option value="active">Activa</option><option value="inactive">Inactiva</option></select></label>
    <span data-validation-selection>Todas las áreas</span>
  </div>
  <div class="unit-native-table-scroll">
    <table class="unit-native-table unit-validation-table" data-drsam-table-filter-skip>
      <caption class="sr-only">Validaciones por área operativa</caption>
      <thead><tr><th>Área solicitante</th><th>Productos / servicios</th><th>Área que da visto bueno</th><th>Área que autoriza</th><th>Reglas</th><th>Estado</th><th>Acción</th></tr></thead>
      <tbody>
        @foreach ($validationAreas as $areaKey => $area)
          @php
            $areaRules = $validationRules->where('requester_area', $areaKey);
            $states = $areaRules->pluck('status')->unique()->values();
          @endphp
          <tr data-validation-area="{{ $areaKey }}" data-validation-states="{{ $states->isEmpty() ? 'none' : $states->implode(' ') }}">
            <td>{{ $area['label'] }}</td>
            <td>{{ $areaRules->pluck('scope_label')->unique()->implode(' · ') ?: 'Por definir' }}</td>
            <td>{{ \App\Support\UnitAreaValidations::areaNames($areaRules->pluck('review_areas')->flatten()->unique()->all()) ?: 'Por definir' }}</td>
            <td>{{ \App\Support\UnitAreaValidations::areaNames($areaRules->pluck('authorize_areas')->flatten()->unique()->all()) ?: 'Por definir' }}</td>
            <td>{{ $areaRules->count() }}</td>
            <td>@forelse ($states as $state)<span class="unit-validation-status is-{{ $state }}">{{ $validationStates[$state] ?? $state }}</span>@empty<span class="unit-validation-status">Sin configurar</span>@endforelse</td>
            <td><button type="button" class="unit-native-button" @if($areaRules->isEmpty()) data-validation-new="{{ $areaKey }}" @else data-validation-expand="{{ $areaKey }}" aria-expanded="false" @endif>{{ $areaRules->isEmpty() ? 'Configurar' : 'Ver reglas' }}</button></td>
          </tr>
          @if ($areaRules->isNotEmpty())
            <tr data-validation-details="{{ $areaKey }}" hidden><td colspan="7">
              <div class="unit-validation-rule-list">
                @foreach ($areaRules as $rule)
                  <article>
                    <div><strong>{{ $rule['scope_label'] }}</strong><span class="unit-validation-status is-{{ $rule['status'] }}">{{ $validationStates[$rule['status']] }}</span>
                      <p>Visto bueno: {{ \App\Support\UnitAreaValidations::areaNames($rule['review_areas']) ?: 'No requerido' }}</p>
                      <p>Autoriza: {{ \App\Support\UnitAreaValidations::areaNames($rule['authorize_areas']) ?: 'No requerido' }}</p>
                      @if($rule['notes'] ?? null)<p>{{ $rule['notes'] }}</p>@endif
                    </div>
                    <button type="button" class="unit-native-button" data-validation-edit="{{ $rule['id'] }}">Editar regla</button>
                  </article>
                @endforeach
                <button type="button" class="unit-native-button" data-validation-new="{{ $areaKey }}">+ Agregar regla para {{ $area['label'] }}</button>
              </div>
            </td></tr>
          @endif
        @endforeach
        <tr data-validation-empty hidden><td colspan="7" class="unit-native-empty">No hay áreas que coincidan con los filtros.</td></tr>
      </tbody>
    </table>
  </div>
</section>

<dialog class="unit-validation-dialog" data-validation-editor aria-labelledby="validation-editor-title">
  <header><div><h2 id="validation-editor-title">Nueva regla de validación</h2><p>{{ $unit->name }}</p></div><button type="button" class="unit-native-button" data-validation-close>Cerrar ×</button></header>
  <form method="post" action="{{ route('unit.area-validations.store', ['unit' => $unit->id]) }}" data-validation-form>
    @csrf
    <input type="hidden" name="_method" value="PUT" disabled>
    <input type="hidden" name="_validation_form" value="1">
    <input type="hidden" name="_validation_rule" value="">
    <input type="hidden" name="version" value="1">
    @if(old('_validation_form') && $errors->any())<div class="unit-validation-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="unit-validation-form-grid">
      <label>Área solicitante<select name="requester_area" required><option value="">Selecciona un área</option>@foreach($validationAreas as $key => $area)<option value="{{ $key }}">{{ $area['label'] }}</option>@endforeach</select></label>
      <label>Aplica a<select name="scope"><option value="all">Todos los productos y servicios</option><option value="service">Un servicio</option><option value="product">Un producto / medicamento</option></select></label>
      <label data-validation-service hidden>Servicio<select name="service_id" disabled><option value="">Selecciona un servicio</option>@foreach($specialties as $service)<option value="{{ $service->id }}">{{ $service->name }}</option>@endforeach</select></label>
      <label data-validation-product hidden>Producto / medicamento<select name="product_id" disabled><option value="">Selecciona un producto</option>@foreach($medicationCatalog as $product)<option value="{{ $product->id }}">{{ $product->name }}{{ $product->cnis ? ' · '.$product->cnis : '' }}</option>@endforeach</select></label>
      <label>Estado<select name="status"><option value="draft">Borrador</option><option value="active">Activa</option><option value="inactive">Inactiva</option></select></label>
    </div>
    <p class="unit-validation-help">Selecciona las áreas responsables. Todas las áreas seleccionadas forman parte de los requisitos de la regla.</p>
    <div class="unit-validation-form-grid">
      @foreach(['review_areas' => 'Áreas que dan visto bueno', 'authorize_areas' => 'Áreas que autorizan'] as $field => $label)
        <fieldset><legend>{{ $label }}</legend>@foreach($validationAreas as $key => $area)<label class="unit-validation-check"><input type="checkbox" name="{{ $field }}[]" value="{{ $key }}">{{ $area['label'] }}</label>@endforeach</fieldset>
      @endforeach
    </div>
    <label>Observaciones<textarea name="notes" rows="3" maxlength="1000" placeholder="Indicaciones para las áreas responsables"></textarea></label>
    <footer><button type="button" class="unit-native-button" data-validation-close>Cancelar</button><button type="submit" class="unit-nutrition-new-request">Guardar regla</button></footer>
  </form>
</dialog>

<dialog class="unit-validation-dialog is-history" data-validation-history-dialog aria-labelledby="validation-history-title">
  <header><div><h2 id="validation-history-title">Historial de cambios</h2><p>Últimos 100 cambios de {{ $unit->name }}</p></div><button type="button" class="unit-native-button" data-validation-close>Cerrar ×</button></header>
  <div class="unit-native-table-scroll"><table class="unit-native-table"><thead><tr><th>Fecha</th><th>Usuario</th><th>Área solicitante</th><th>Productos / servicios</th><th>Cambio</th><th>Requisitos guardados</th></tr></thead><tbody>
    @forelse($validationHistory as $event)
      @php($change = data_get($event->payload, 'context.after', []))
      @php($before = data_get($event->payload, 'context.before'))
      <tr><td>{{ $event->created_at?->format('d/m/Y H:i') }}</td><td>{{ $event->user?->name ?? 'Usuario no disponible' }}</td><td>{{ $validationAreas[$change['requester_area'] ?? '']['label'] ?? 'Área no disponible' }}</td><td>{{ $change['scope_label'] ?? '' }}</td><td>{{ $before ? 'Actualización' : 'Creación' }} · {{ $validationStates[$change['status'] ?? ''] ?? '' }} · v{{ $change['version'] ?? 1 }}</td><td>Visto bueno: {{ \App\Support\UnitAreaValidations::areaNames($change['review_areas'] ?? []) ?: 'No requerido' }}<br>Autoriza: {{ \App\Support\UnitAreaValidations::areaNames($change['authorize_areas'] ?? []) ?: 'No requerido' }}</td></tr>
    @empty<tr><td colspan="6" class="unit-native-empty">Todavía no hay cambios en las reglas de validación.</td></tr>@endforelse
  </tbody></table></div>
</dialog>

@push('scripts')
  <script>
    window.unitAreaValidationConfig = {{ Illuminate\Support\Js::from([
      'areas' => collect($validationAreas)->map(fn ($area) => $area['label'])->all(),
      'rules' => $validationRules->keyBy('id')->all(),
      'storeUrl' => route('unit.area-validations.store', ['unit' => $unit->id]),
      'updateUrls' => $validationRules->mapWithKeys(fn ($rule) => [$rule['id'] => route('unit.area-validations.update', ['ruleId' => $rule['id'], 'unit' => $unit->id])])->all(),
      'selectedArea' => $activeProcedureCatalog,
      'old' => old('_validation_form') ? session()->getOldInput() : null,
    ]) }};
  </script>
  <script src="{{ asset('js/unit-area-validations.js') }}?v={{ filemtime(public_path('js/unit-area-validations.js')) }}" defer></script>
@endpush
