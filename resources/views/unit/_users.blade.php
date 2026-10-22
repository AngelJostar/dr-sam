@push('styles')
  <link rel="stylesheet" href="{{ asset('css/unit-users.css') }}?v={{ filemtime(public_path('css/unit-users.css')) }}">
@endpush
@php
  $userStoreUrl = route('unit.operational-users.store', ['unit' => $unit->id]);
  $userPermissionLabels = ['history' => 'Visualizar historial', 'view-history' => 'Visualizar historial', 'detail' => 'Visualizar detalle', 'view-detail' => 'Visualizar detalle', 'manage' => 'Administrar área', 'reports' => 'Descargar reportes', 'download-reports' => 'Descargar reportes', 'update-nursing' => 'Actualizar enfermería', 'update-pharmacy-npt' => 'Actualizar nutrición parenteral', 'update-pharmacy-chemo' => 'Actualizar quimioterapia', 'manage-external-pharmacy' => 'Administrar farmacia externa', 'update-oncology' => 'Actualizar oncología', 'update-outpatient' => 'Actualizar consulta externa'];
@endphp
<div class="unit-catalog-screen unit-users-screen" data-unit-catalog-screen>
  <section class="unit-service-panel-header unit-catalog-info-card" data-unit-catalog-info>
    <div class="unit-service-panel-copy">
      <h2>Usuarios</h2>
      <p>Gestión de perfiles, áreas de trabajo y autorizaciones de la unidad</p>
      <div class="unit-service-panel-badges"><span class="unit-native-status">Activo</span><span>{{ $unit->operationalProfiles->count() }} usuarios registrados</span></div>
    </div>
  </section>
  <div class="unit-catalog-toolbar unit-consultation-submenu-card" data-unit-catalog-controls>
    <div class="unit-nutrition-request-tabs unit-consultation-submenu" role="tablist" aria-label="Filtrar usuarios operativos">
      <button type="button" class="unit-consultation-submenu-button is-active" data-unit-catalog-status="all" role="tab" aria-selected="true">Todos</button>
      <button type="button" class="unit-consultation-submenu-button" data-unit-catalog-status="active" role="tab" aria-selected="false">Activos <span>{{ $unit->operationalProfiles->where('status', 'active')->count() }}</span></button>
      <button type="button" class="unit-consultation-submenu-button" data-unit-catalog-status="inactive" role="tab" aria-selected="false">Inactivos <span>{{ $unit->operationalProfiles->where('status', 'inactive')->count() }}</span></button>
    </div>
    <div class="unit-catalog-toolbar-actions">
      <label class="unit-catalog-search"><span class="sr-only">Buscar usuario</span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input type="search" placeholder="Buscar usuario" data-unit-catalog-search></label>
      <button type="button" class="unit-catalog-secondary-action" data-operational-export>Descargar catalogo</button>
      <button type="button" class="unit-nutrition-new-request unit-consultation-submenu-action" data-open-operational-user><span aria-hidden="true">+</span>Nuevo usuario</button>
    </div>
  </div>
  <section class="unit-native-table-card unit-catalog-content-card" data-unit-catalog-content>
    <div class="unit-native-table-scroll">
      <table class="unit-native-table unit-users-table" data-operational-users-table>
        <caption class="sr-only">Usuarios de la unidad</caption>
        <thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Área asignada</th><th>Permisos</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          @forelse ($unit->operationalProfiles as $profile)
            <tr data-operational-user-row data-unit-catalog-row data-status="{{ $profile->status }}">
              <td><strong>{{ $profile->user?->name ?? 'Usuario no disponible' }}</strong><small>{{ $profile->user?->username ?? 'Sin usuario' }}</small></td>
              <td>{{ $profile->user?->email ?? 'Sin correo' }}</td>
              <td>{{ $profile->role_label ?? 'Responsable de Area' }}</td>
              <td>{{ $profile->area?->label ?? 'Sin área' }}</td>
              <td>{{ collect($profile->permissions)->map(fn ($permission) => $userPermissionLabels[$permission] ?? $permission)->implode(', ') ?: 'Sin permisos' }}</td>
              <td><span class="unit-native-status {{ $profile->status !== 'active' ? 'is-inactive' : '' }}">{{ $statusText($profile->status) }}</span></td>
              <td><div class="unit-users-actions">
                <button type="button" data-edit-operational-user data-profile="{{ $profile->id }}" data-url="{{ route('unit.operational-users.update', ['profile' => $profile, 'unit' => $unit->id]) }}" data-name="{{ $profile->user?->name }}" data-username="{{ $profile->user?->username }}" data-role="{{ $profile->role_label }}" data-authority="{{ data_get($profile->metadata, 'authority', 'Direccion Administrativa') }}" data-service="{{ data_get($profile->metadata, 'service', 'Nutricion parenteral') }}" data-area="{{ $profile->operational_area_id }}" data-permissions='@json($profile->permissions ?? [])'>Editar</button>
                <form method="post" action="{{ route('unit.operational-users.destroy', ['profile' => $profile, 'unit' => $unit->id]) }}" onsubmit="return confirm('¿Eliminar este perfil operativo?')">@csrf @method('delete')<button type="submit">Eliminar</button></form>
              </div></td>
            </tr>
          @empty
            <tr><td colspan="7" class="unit-native-empty">Sin usuarios operativos registrados.</td></tr>
          @endforelse
          <tr data-unit-catalog-empty hidden><td colspan="7" class="unit-native-empty">No se encontraron usuarios.</td></tr>
        </tbody>
      </table>
    </div>
  </section>
</div>

<dialog class="unit-doctor-create-dialog unit-user-dialog" data-operational-user-dialog aria-labelledby="unit-user-dialog-title">
  <form method="post" action="{{ $userStoreUrl }}" class="unit-doctor-dialog-form" data-operational-user-form autocomplete="off">
    @csrf
    <input type="hidden" name="_method" value="patch" disabled data-operational-method>
    <input type="hidden" name="_operational_user_form" value="1">
    <input type="hidden" name="_operational_profile" value="">
    <header class="unit-doctor-dialog-header">
      <div><h2 id="unit-user-dialog-title" data-operational-form-title>Alta de usuario operativo</h2><p>Información, rol, área operativa y autorizaciones del usuario</p></div>
      <button type="button" class="unit-doctor-dialog-close" data-close-operational-user aria-label="Cerrar formulario" title="Cerrar">&times;</button>
    </header>
    <div class="unit-doctor-dialog-body">
      @if (old('_operational_user_form') && $errors->any())
        <div class="unit-doctor-dialog-errors" role="alert" data-operational-user-errors><strong>No se pudo guardar el usuario.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
      @endif
      <label class="wide">Nombre completo<input name="name" required maxlength="255" autocomplete="off"></label>
      <label>Usuario o correo<input name="username" required maxlength="255" autocomplete="off"></label>
      <label>Contraseña<input name="password" type="password" required minlength="6" maxlength="255" autocomplete="new-password"><small data-operational-password-help>Mínimo 6 caracteres.</small></label>
      <label>Rol operativo<select name="role_label"><option>Responsable de Area</option><option>Operador</option></select></label>
      <label>Área autorizadora<select name="authority"><option>Direccion General</option><option>Direccion Administrativa</option></select></label>
      <label>Servicio asignado<select name="service">@foreach ($unit->contractedServices as $contract)<option>{{ $contract->service?->name }}</option>@endforeach<option>Nutricion parenteral</option></select></label>
      <fieldset class="wide">
        <legend>Asignación de Área Operativa</legend>
        <div class="unit-native-choice-grid">
          @foreach ($operationalAreas as $area)
            <label><input type="radio" name="operational_area_id" value="{{ $area->id }}" @checked($loop->first) required><span><strong>{{ $area->label }}</strong><small>{{ $areaChoices[$area->label] ?? 'Operación y seguimiento del área.' }}</small></span></label>
          @endforeach
        </div>
      </fieldset>
      <fieldset class="wide">
        <legend>Acciones en Área Operativa</legend>
        <div class="unit-native-choice-grid">
          @foreach (['history' => ['Visualizar historial', 'Consultar solicitudes del módulo Área Operativa.'], 'detail' => ['Visualizar detalle', 'Abrir una solicitud y revisar su información.'], 'manage' => ['Administrar área operativa', 'Operar inventario, recetas, movimientos y almacenes.'], 'reports' => ['Descargar reportes', 'Exportar reportes del área operativa.']] as $permission => [$action, $copy])
            <label><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked($loop->index < 2)><span><strong>{{ $action }}</strong><small>{{ $copy }}</small></span></label>
          @endforeach
        </div>
      </fieldset>
    </div>
    <footer class="unit-doctor-dialog-actions"><button type="button" data-close-operational-user>Cancelar</button><button type="submit" class="is-primary" data-operational-submit>Guardar y cerrar</button></footer>
  </form>
</dialog>
<script>
  window.unitUserDialogConfig = {{ Illuminate\Support\Js::from([
    'storeUrl' => $userStoreUrl,
    'old' => old('_operational_user_form') && $errors->any() ? collect(session()->getOldInput())->only(['name', 'username', 'role_label', 'authority', 'service', 'operational_area_id', 'permissions', '_operational_profile'])->all() : null,
  ]) }};
</script>
<script src="{{ asset('js/unit-users.js') }}?v={{ filemtime(public_path('js/unit-users.js')) }}" defer></script>
