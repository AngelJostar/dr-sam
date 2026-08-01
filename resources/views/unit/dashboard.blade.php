@extends('layouts.app', ['title' => 'Unidad medica'])

@section('body_class', 'unit-native-body')

@php
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
@endphp

@section('content')
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
        @foreach ($menu as $key => [$icon, $label])
          <a class="{{ $section === $key ? 'is-active' : '' }}" href="{{ route('unit.dashboard', ['section' => $key]) }}">
            <span>{{ $icon }}</span>{{ $label }}
          </a>
        @endforeach
      </nav>
    </aside>

    <section class="unit-native-workspace">
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

      <header class="unit-native-header">
        <div>
          <p class="eyebrow">{{ $sectionEyebrow }}</p>
          <h1>{{ $unit->name }}</h1>
          <p>{{ $unitCode }} - {{ $unitLocation }}</p>
        </div>
        <div class="unit-native-selector">
          <label>
            Unidad
            <select>
              <option>{{ $unit->name }} - {{ $unitCode }}</option>
            </select>
          </label>
          <time>{{ now()->format('d M Y') }}</time>
        </div>
      </header>

      @if ($section === 'profile')
        @php
          $profile = $unit->metadata['profile'] ?? [];
          $profileImage = $profile['image_path'] ?? null;
          $profileName = $profile['public_name'] ?? $unit->name;
          $profileInfo = $profile['general_info'] ?? '';
          $profileServices = $profile['services'] ?? $unit->contractedServices->pluck('service.name')->filter()->implode(', ');
          $profileNews = $profile['news'] ?? '';
          $profileSocial = $profile['social_text'] ?? '';
          $profileStationery = $profile['stationery_note'] ?? '';
          $initials = str($profileName)->explode(' ')->filter()->take(2)->map(fn ($word) => str($word)->substr(0, 1))->implode('');
        @endphp
        <div class="unit-profile-layout" data-unit-profile>
          <section class="unit-profile-card">
            <header><h2>Perfil de la unidad</h2><p>Imagen, servicios y novedades</p></header>
            <form method="post" action="{{ route('unit.profile.update') }}" enctype="multipart/form-data" class="unit-profile-form">
              @csrf
              @method('patch')
              <label>Imagen de perfil y papelerÃ­a</label>
              <div class="unit-profile-image-row">
                <div class="unit-profile-image-box" data-profile-image-preview>@if ($profileImage)<img src="{{ asset('storage/'.$profileImage) }}" alt="Imagen de {{ $profileName }}">@else<span>Sin imagen</span>@endif</div>
                <div><input type="file" name="profile_image" accept="image/png,image/jpeg,image/webp" data-profile-image-input><label class="unit-profile-remove"><input type="checkbox" name="remove_image" value="1"> Quitar imagen</label></div>
              </div>
              <label>Nombre pÃºblico de la unidad<input name="public_name" value="{{ old('public_name', $profileName) }}" data-profile-name required></label>
              <label>InformaciÃ³n general<textarea name="general_info" data-profile-info>{{ old('general_info', $profileInfo) }}</textarea></label>
              <label>Servicios de la unidad<textarea name="services" data-profile-services>{{ old('services', $profileServices) }}</textarea></label>
              <label>Novedades<textarea name="news" data-profile-news>{{ old('news', $profileNews) }}</textarea></label>
              <label>Texto para redes sociales<textarea name="social_text" data-profile-social>{{ old('social_text', $profileSocial) }}</textarea></label>
              <label>Nota para papelerÃ­a<textarea name="stationery_note" data-profile-stationery>{{ old('stationery_note', $profileStationery) }}</textarea></label>
              <button type="submit">Guardar perfil</button>
            </form>
          </section>
          <section class="unit-profile-card unit-profile-preview-card">
            <header><h2>Vista previa</h2><p>Redes sociales y papelerÃ­a</p></header>
            <div class="unit-profile-preview">
              <div class="unit-profile-cover"><strong data-profile-initials>{{ strtoupper($initials) }}</strong></div>
              <div class="unit-profile-preview-copy">
                <small>{{ $unitCode }} - {{ strtoupper($unitLocation) }}</small><h2 data-profile-preview-name>{{ $profileName }}</h2>
                <p data-profile-preview-info>{{ $profileInfo ?: 'InformaciÃ³n general pendiente.' }}</p>
                <strong>SERVICIOS</strong><p data-profile-preview-services>{{ $profileServices ?: 'Sin servicios registrados.' }}</p>
                <strong>NOVEDADES</strong><p data-profile-preview-news>{{ $profileNews ?: 'Sin novedades registradas.' }}</p>
                <article><strong>REDES SOCIALES</strong><p data-profile-preview-social>{{ $profileSocial ?: 'InformaciÃ³n general pendiente.' }}</p></article>
                <article><strong>PAPELERÃA</strong><p data-profile-preview-stationery>{{ $profileStationery ?: 'PapelerÃ­a institucional de la unidad.' }}</p></article>
              </div>
            </div>
          </section>
        </div>
        <script>
          (() => {
            const root = document.querySelector('[data-unit-profile]');
            const bind = (input, output, fallback) => input.addEventListener('input', () => output.textContent = input.value.trim() || fallback);
            const name = root.querySelector('[data-profile-name]');
            bind(name, root.querySelector('[data-profile-preview-name]'), @json($unit->name));
            bind(root.querySelector('[data-profile-info]'), root.querySelector('[data-profile-preview-info]'), 'InformaciÃ³n general pendiente.');
            bind(root.querySelector('[data-profile-services]'), root.querySelector('[data-profile-preview-services]'), 'Sin servicios registrados.');
            bind(root.querySelector('[data-profile-news]'), root.querySelector('[data-profile-preview-news]'), 'Sin novedades registradas.');
            bind(root.querySelector('[data-profile-social]'), root.querySelector('[data-profile-preview-social]'), 'InformaciÃ³n general pendiente.');
            bind(root.querySelector('[data-profile-stationery]'), root.querySelector('[data-profile-preview-stationery]'), 'PapelerÃ­a institucional de la unidad.');
            name.addEventListener('input', () => root.querySelector('[data-profile-initials]').textContent = name.value.trim().split(/\s+/).slice(0, 2).map(word => word[0] || '').join('').toUpperCase());
            root.querySelector('[data-profile-image-input]').addEventListener('change', (event) => { const file = event.target.files[0]; if (!file) return; root.querySelector('[data-profile-image-preview]').innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Vista previa">`; });
          })();
        </script>
      @elseif ($section === 'services')
        <section class="unit-native-filters" aria-label="Filtros de servicios">
          <label class="search">
            <span aria-hidden="true">O</span>
            <input type="search" data-unit-service-search placeholder="Buscar servicio o categoria">
          </label>
          <label>
            Estatus
            <select data-unit-service-status><option value="all">Todos</option>@foreach ($unit->contractedServices->pluck('status')->filter()->unique()->sort() as $contractStatus)<option value="{{ $contractStatus }}">{{ $statusText($contractStatus) }}</option>@endforeach</select>
          </label>
          <button type="button" data-unit-service-export>&darr;&nbsp; Excel</button>
        </section>

        <section class="unit-native-table-card">
          <div class="unit-native-table-heading">
            <div>
              <h2>Servicios Habilitados por la Institucion</h2>
              <p>{{ $unit->contractedServices->count() }} servicios encontrados</p>
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
                @forelse ($unit->contractedServices as $contract)
                  @php
                    $operationSearch = str(($contract->service?->name ?? '').' '.($contract->service?->category ?? '').' '.($contract->service?->specialty ?? ''))->lower()->toString();
                    $operationArea = str_contains($operationSearch, 'oncolo') || str_contains($operationSearch, 'quimio')
                      ? 'oncology'
                      : (str_contains($operationSearch, 'farmac') ? 'inpatient-pharmacy' : 'nursing');
                  @endphp
                  <tr data-unit-service-row data-search="{{ str(($contract->service?->name ?? '').' '.($contract->service?->category ?? '').' '.($contract->service?->specialty ?? ''))->lower() }}" data-status="{{ $contract->status }}">
                    <td><a class="unit-service-operation" href="{{ route('operational.dashboard', ['area' => $operationArea, 'section' => 'history', 'unit' => $unit->id, 'service' => $contract->service_id]) }}">Ver Operacion</a></td>
                    <td><strong>{{ $contract->service?->name ?? 'Servicio sin nombre' }}</strong><small>{{ $contract->service?->specialty ?? 'Servicio general' }}</small></td>
                    <td>{{ $contract->service?->category ?? $contract->service?->specialty ?? 'Sin categoria' }}</td>
                    <td>{{ $contract->starts_at?->format('d/m/Y') ?? 'Sin inicio' }} - {{ $contract->ends_at?->format('d/m/Y') ?? 'Sin vencimiento' }}</td>
                    <td><span class="unit-native-status">{{ $statusText($contract->status) }}</span></td>
                    <td><a class="unit-native-download" href="{{ route('unit.services.report', $contract) }}">Descargar</a></td>
                    <td><button type="button" data-open-service-catalog="{{ $contract->id }}">VER</button></td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="unit-native-empty">Sin servicios habilitados para los filtros actuales.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
        @foreach ($unit->contractedServices as $contract)
          @php
            $catalogServiceName = $contract->service?->name ?? 'Servicio sin nombre';
            $catalogProvider = data_get($contract->metadata, 'provider', 'Operacion clinica');
            $catalogKey = $contract->service?->code ?? str($catalogServiceName)->slug();
          @endphp
          <dialog class="unit-service-catalog-dialog" data-service-catalog-dialog="{{ $contract->id }}">
            <header><div><h2>Catalogo de productos/Servicios</h2><p>{{ $catalogServiceName }} - {{ $catalogProvider }}</p></div><button type="button" data-close-service-catalog>Cerrar</button></header>
            <div class="unit-service-catalog-facts">
              <article><small>Servicio</small><strong>{{ $catalogServiceName }}</strong></article>
              <article><small>Proveedor</small><strong>{{ $catalogProvider }}</strong></article>
              <article><small>Elementos habilitados</small><strong>1</strong></article>
            </div>
            <div class="unit-service-catalog-scroll">
              <table>
                <thead><tr><th>Clave</th><th>Producto / servicio</th><th>Tipo</th><th>Proveedor</th><th>Detalle</th><th>Estatus</th></tr></thead>
                <tbody><tr><td>{{ $catalogKey }}</td><td><strong>{{ $catalogServiceName }}</strong><small>{{ $contract->service?->specialty ?? 'Servicio general' }}</small></td><td>{{ $contract->service?->category ?? 'Sin categoria' }}</td><td>{{ $catalogProvider }}</td><td>Vigencia {{ $contract->starts_at?->format('d/m/Y') ?? 'sin inicio' }} - {{ $contract->ends_at?->format('d/m/Y') ?? 'sin vencimiento' }}</td><td><span class="unit-native-status">{{ $statusText($contract->status) }}</span></td></tr></tbody>
              </table>
            </div>
          </dialog>
        @endforeach
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
      @endif

      @if ($section === 'users')
        <div class="unit-native-two-column">
          <section class="unit-native-form-card">
            <header>
              <h2 data-operational-form-title>Alta de usuario operativo</h2>
              <p>Actualiza informacion, rol, asignacion de area operativa y autorizaciones del usuario</p>
            </header>
            <form method="post" action="{{ route('unit.operational-users.store') }}" data-operational-user-form>
              @csrf
              <input type="hidden" name="_method" value="patch" disabled data-operational-method>
              <label class="wide">Nombre completo<input name="name" required></label>
              <label>Usuario o correo<input name="username" required></label>
              <label>Contrasena<input name="password" type="password"></label>
              <label>Rol operativo<select name="role_label"><option>Responsable de Area</option><option>Operador</option></select></label>
              <label>Area autorizadora<select name="authority"><option>Direccion General</option><option>Direccion Administrativa</option></select></label>
              <label>Servicio asignado<select name="service">@foreach ($unit->contractedServices as $contract)<option>{{ $contract->service?->name }}</option>@endforeach<option>Nutricion parenteral</option></select></label>

              <fieldset class="wide">
                <legend>Asignacion de Area Operativa</legend>
                <div class="unit-native-choice-grid">
                  @foreach ($operationalAreas as $area)
                    <label>
                      <input type="radio" name="operational_area_id" value="{{ $area->id }}" @checked($loop->first) required>
                      <span><strong>{{ $area->label }}</strong><small>{{ $areaChoices[$area->label] ?? 'Operacion y seguimiento del area.' }}</small></span>
                    </label>
                  @endforeach
                </div>
              </fieldset>

              <fieldset class="wide">
                <legend>Acciones en Area Operativa</legend>
                <div class="unit-native-choice-grid">
                  @foreach (['history' => ['Visualizar historial', 'Consultar solicitudes del modulo Area Operativa.'], 'detail' => ['Visualizar detalle', 'Abrir una solicitud y revisar su informacion.'], 'manage' => ['Administrar area operativa', 'Operar inventario, recetas, movimientos y almacenes.'], 'reports' => ['Descargar reportes', 'Exportar reportes del area operativa.']] as $permission => [$action, $copy])
                    <label>
                      <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked($loop->index < 2)>
                      <span><strong>{{ $action }}</strong><small>{{ $copy }}</small></span>
                    </label>
                  @endforeach
                </div>
              </fieldset>

              <button type="submit" data-operational-submit>Crear usuario</button><button type="button" data-operational-reset>Regresar</button>
            </form>
          </section>

          <section class="unit-native-table-card">
            <div class="unit-native-table-heading unit-native-heading-action">
              <div><h2>Usuarios de la unidad</h2><p>{{ $unit->operationalProfiles->count() }} usuarios</p></div><button type="button" data-operational-export>&darr;&nbsp; Excel</button>
            </div>
            <div class="unit-native-user-list">
              @forelse ($unit->operationalProfiles as $profile)
                <article data-operational-user-row>
                  <div>
                    <h3>{{ $profile->area?->label ?? 'Area Operativa' }} - {{ $unit->name }}</h3>
                    <p>{{ $profile->role_label ?? 'Responsable de Area' }} - {{ $profile->user?->username ?? 'sin-usuario' }}</p>
                    <p>Asignacion Area Operativa: {{ $profile->area?->label ?? 'Sin area' }}</p>
                    <p>Acciones Area Operativa: {{ collect($profile->permissions)->map(fn ($permission) => ['history' => 'Visualizar historial', 'detail' => 'Visualizar detalle', 'manage' => 'Administrar area', 'reports' => 'Descargar reportes'][$permission] ?? $permission)->implode(', ') }}</p>
                  </div>
                  <span class="unit-native-status">{{ $statusText($profile->status) }}</span>
                  <button type="button" data-edit-operational-user data-url="{{ route('unit.operational-users.update', $profile) }}" data-name="{{ $profile->user?->name }}" data-username="{{ $profile->user?->username }}" data-password="" data-role="{{ $profile->role_label }}" data-authority="{{ data_get($profile->metadata, 'authority', 'Direccion Administrativa') }}" data-service="{{ data_get($profile->metadata, 'service', 'Nutricion parenteral') }}" data-area="{{ $profile->operational_area_id }}" data-permissions='@json($profile->permissions ?? [])'>Editar</button>
                  <form method="post" action="{{ route('unit.operational-users.destroy', $profile) }}" onsubmit="return confirm('Â¿Eliminar este perfil operativo?')">@csrf @method('delete')<button type="submit">Eliminar</button></form>
                </article>
              @empty
                <p class="unit-native-empty">Sin usuarios operativos registrados.</p>
              @endforelse
            </div>
          </section>
        </div>
        <script>
          (() => {
            const form = document.querySelector('[data-operational-user-form]'); const method = form.querySelector('[data-operational-method]'); const title = document.querySelector('[data-operational-form-title]'); const submit = form.querySelector('[data-operational-submit]');
            const reset = () => { form.reset(); form.action = @json(route('unit.operational-users.store')); method.disabled = true; title.textContent = 'Alta de usuario operativo'; submit.textContent = 'Crear usuario'; form.elements.password.value = ''; };
            document.querySelectorAll('[data-edit-operational-user]').forEach((button) => button.addEventListener('click', () => { const data = button.dataset; form.action = data.url; method.disabled = false; title.textContent = 'Editar usuario operativo'; submit.textContent = 'Guardar cambios'; ['name','username','password'].forEach(key => form.elements[key].value = data[key] || ''); form.elements.role_label.value = data.role; form.elements.authority.value = data.authority; form.elements.service.value = data.service; form.querySelectorAll('[name="operational_area_id"]').forEach(input => input.checked = input.value === data.area); const permissions = JSON.parse(data.permissions || '[]'); form.querySelectorAll('[name="permissions[]"]').forEach(input => input.checked = permissions.includes(input.value)); window.scrollTo({top: 0, behavior: 'smooth'}); }));
            document.querySelector('[data-operational-reset]').addEventListener('click', reset);
            document.querySelector('[data-operational-export]').addEventListener('click', () => { const rows = [['Usuario operativo']]; document.querySelectorAll('[data-operational-user-row]').forEach(row => rows.push([row.innerText.replace(/\s+/g, ' ').trim()])); const blob = new Blob([rows.map(row => row.map(value => `"${value.replaceAll('"','""')}"`).join(',')).join('\n')], {type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='usuarios-operativos.csv'; link.click(); URL.revokeObjectURL(link.href); });
          })();
        </script>
      @endif

      @if ($section === 'patients')
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading unit-native-heading-action">
            <div>
              <h2>Catalogo de pacientes</h2>
              <p>{{ $patients->count() }} pacientes</p>
            </div>
            <button type="button" data-doctor-export>&darr;&nbsp; Excel</button>
          </div>
          <p class="unit-native-card-copy">Pacientes dados de alta por el area operativa de cada hospital.</p>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table">
              <thead><tr><th>Paciente</th><th>Servicio</th><th>Medico / Area</th><th>Estatus</th><th>Actualizacion</th></tr></thead>
              <tbody>
                @forelse ($patients as $patient)
                  @php $lastAppointment = $patient->appointments->first(); @endphp
                  <tr>
                    <td><strong>{{ $patient->full_name }}</strong><span>{{ $patient->record_number ?? $patient->platform_number ?? 'Sin expediente' }}</span></td>
                    <td>{{ $lastAppointment?->specialty ?? 'Sin servicio' }}</td>
                    <td>{{ $lastAppointment?->doctor?->full_name ?? 'Sin medico' }}</td>
                    <td><span class="unit-native-status">{{ $statusText($patient->status) }}</span></td>
                    <td>{{ $patient->updated_at?->format('d/m/Y') }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="unit-native-empty">Sin pacientes dados de alta por el area operativa.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      @endif

      @if ($section === 'doctors')
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading unit-native-heading-action">
            <div>
              <h2>Catalogo de medicos adscritos</h2>
              <p>{{ $unit->doctors->count() }} medicos adscritos</p>
            </div>
            <button type="button" data-doctor-export>Excel</button>
          </div>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table unit-doctors-table">
              <thead><tr><th>Medico</th><th>Cedula</th><th>Especialidad</th><th>Servicio</th><th>Autorizacion</th><th>Acciones</th></tr></thead>
              <tbody>
                @forelse ($unit->doctors as $doctor)
                  <tr data-doctor-row>
                    <td><strong>{{ $doctor->full_name }}</strong><small>{{ $doctor->subspecialty ?? 'Atencion clinica' }}</small><span>Usuario plataforma: {{ $doctor->user?->username ?? data_get($doctor->metadata, 'platform_user', 'Sin usuario de plataforma') }}</span></td>
                    <td>{{ $doctor->professional_license ?? 'Sin cedula' }}</td>
                    <td>{{ $doctor->specialty ?? 'Sin especialidad' }}</td>
                    <td>{{ $doctor->service_name ?? 'Sin servicio' }}</td>
                    <td><span @class(['unit-native-status', 'is-warning' => $doctor->status === 'pending'])>{{ $statusText($doctor->status === 'active' ? 'active' : 'pending') }}</span></td>
                    <td><button type="button">Editar autorizaciones</button><form method="post" action="{{ route('unit.doctors.destroy', $doctor) }}" onsubmit="return confirm('Â¿Eliminar este medico?')">@csrf @method('delete')<button type="submit">Eliminar</button></form></td>
                  </tr>
                  <tr class="unit-doctor-authorization-row" data-doctor-authorization hidden><td colspan="6">
                    <form method="post" action="{{ route('unit.doctors.authorizations.update', $doctor) }}" class="unit-doctor-authorization-form">
                      @csrf @method('put')
                      <header><strong>Editar autorizaciones</strong><span>{{ $doctor->full_name }}</span></header>
                      <label>Estatus de autorizacion<select name="status"><option value="active" @selected($doctor->status === 'active')>Autorizado</option><option value="pending" @selected($doctor->status === 'pending')>Pendiente</option><option value="inactive" @selected($doctor->status === 'inactive')>Inactivo</option></select></label>
                      <fieldset><legend>Servicios autorizados</legend><div class="unit-native-choice-grid">
                        @php($authorizedServices = data_get($doctor->metadata, 'services', [$doctor->service_name]))
                        @forelse ($unit->contractedServices->pluck('service.name')->filter()->unique()->values() as $serviceName)
                          <label><input type="checkbox" name="services[]" value="{{ $serviceName }}" @checked(in_array($serviceName, $authorizedServices, true))><span><strong>{{ $serviceName }}</strong><small>Servicio habilitado para este medico.</small></span></label>
                        @empty
                          @foreach ($serviceChoices as $serviceName)<label><input type="checkbox" name="services[]" value="{{ $serviceName }}" @checked(in_array($serviceName, $authorizedServices, true))><span><strong>{{ $serviceName }}</strong><small>Servicio habilitado para este medico.</small></span></label>@endforeach
                        @endforelse
                      </div></fieldset>
                      <div class="unit-doctor-authorization-actions"><button type="submit">Guardar autorizaciones</button><button type="button" data-close-doctor-authorization>Regresar</button></div>
                    </form>
                  </td></tr>
                @empty
                  <tr><td colspan="6" class="unit-native-empty">Sin medicos adscritos registrados.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <section class="unit-native-form-card unit-native-spaced">
          <header><h2>Alta de medico adscrito</h2><p>Catalogo local del hospital</p></header>
          <form method="post" action="{{ route('unit.doctors.store') }}" class="unit-doctor-create-form">
            @csrf
            <label>Nombre<input name="first_name" required></label><label>Apellido<input name="last_name" required></label>
            <label>Usuario de la plataforma<input name="platform_user"></label><label>Cedula profesional<input name="professional_license" required></label>
            <label>Especialidad<select name="specialty" required><option value="">Selecciona una especialidad</option>@foreach ($specialties->pluck('specialty')->filter()->unique()->sort() as $specialty)<option>{{ $specialty }}</option>@endforeach</select></label>
            <label>Subespecialidad<input name="subspecialty"></label>
            <fieldset class="wide">
              <legend>Servicio adscrito</legend>
              <div class="unit-native-choice-grid">
                @forelse ($unit->contractedServices as $contract)
                  <label><input type="checkbox" name="services[]" value="{{ $contract->service?->name }}" @checked($loop->first)><span><strong>{{ $contract->service?->name }}</strong><small>Servicio habilitado para adscripcion medica.</small></span></label>
                @empty
                  @foreach ($serviceChoices as $choice)<label><input type="checkbox" name="services[]" value="{{ $choice }}" @checked($loop->first)><span><strong>{{ $choice }}</strong><small>Servicio habilitado para adscripcion medica.</small></span></label>@endforeach
                @endforelse
              </div>
            </fieldset>
            <button type="submit">Guardar medico</button>
          </form>
        </section>
        <script>
          document.querySelector('[data-doctor-export]').addEventListener('click', () => { const rows=[['Medico','Cedula','Especialidad','Servicio','Autorizacion']]; document.querySelectorAll('[data-doctor-row]').forEach(row => rows.push([...row.cells].slice(0,5).map(cell => cell.innerText.trim()))); const blob=new Blob([rows.map(row=>row.map(value=>`"${value.replaceAll('"','""')}"`).join(',')).join('\n')],{type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='catalogo-medicos.csv'; link.click(); URL.revokeObjectURL(link.href); });
          document.querySelectorAll('[data-doctor-row]').forEach(row => { const panel = row.nextElementSibling; const editButton = row.querySelector('td:last-child > button'); editButton?.addEventListener('click', () => { document.querySelectorAll('[data-doctor-authorization]').forEach(item => item.hidden = item !== panel); panel.hidden = false; }); panel?.querySelector('[data-close-doctor-authorization]')?.addEventListener('click', () => panel.hidden = true); });
        </script>
      @endif

      @if ($section === 'specialties')
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading">
            <h2>Catalogo de especialidades</h2>
            <p>{{ $specialties->count() }} especialidades habilitadas</p>
          </div>
          <p class="unit-native-card-copy">Especialidades dadas de alta por la institucion padre de esta unidad.</p>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table">
              <thead><tr><th>No.</th><th>Especialidad</th><th>Estatus</th></tr></thead>
              <tbody>
                @forelse ($specialties as $service)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $service->specialty ?? $service->name }}</strong><span>{{ $unit->institution?->name ?? 'Institucion' }} - Catalogo institucional</span></td>
                    <td><span class="unit-native-status">{{ $statusText($service->status) }}</span></td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="unit-native-empty">Sin especialidades habilitadas.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      @endif

      @if ($section === 'procedure-areas')
        @php($procedureCatalogs = [
          'consulting' => ['Catalogo de consultorios', 'Nuevo consultorio', 'consultorio'],
          'infusion' => ['Catalogo de salas de infusion', 'Nueva sala de infusion', 'sala de infusion'],
          'operating' => ['Catalogo de quirofanos', 'Nuevo quirofano', 'quirofano'],
          'recovery' => ['Catalogo de salas de recuperacion', 'Nueva sala de recuperacion', 'sala de recuperacion'],
        ])
        @php($procedureAreas = collect(data_get($unit->metadata, 'procedure_areas', [])))
        @php($scheduleDays = ['monday' => 'Lunes', 'tuesday' => 'Martes', 'wednesday' => 'Miercoles', 'thursday' => 'Jueves', 'friday' => 'Viernes', 'saturday' => 'Sabado', 'sunday' => 'Domingo'])
        <div class="unit-procedure-catalogs">
          @foreach ($procedureCatalogs as $catalogKey => [$catalogTitle, $createLabel, $catalogSingular])
            @php($catalogAreas = $procedureAreas->where('type', $catalogKey)->values())
            @php($editingArea = request('edit') ? $catalogAreas->firstWhere('id', request('edit')) : null)
            <section class="unit-native-table-card unit-procedure-card" data-procedure-catalog="{{ $catalogKey }}">
              <div class="unit-native-table-heading unit-native-heading-action">
                <div><h2>{{ $catalogTitle }}</h2><p>{{ $catalogAreas->count() }} subunidades</p></div>
                <div class="unit-procedure-heading-actions"><button type="button" data-procedure-export>Excel</button><a class="is-primary" href="{{ route('unit.dashboard', ['section' => 'procedure-areas', 'create' => $catalogKey]) }}">{{ $createLabel }}</a></div>
              </div>
              <div class="unit-native-table-scroll">
                <table class="unit-native-table unit-procedure-table">
                  <thead><tr><th>Ubicacion</th><th>Piso</th><th>Numero de unidad</th><th>Capacidad simultanea</th><th>Responsable de area</th><th>Horario de atencion</th><th>Acciones</th></tr></thead>
                  <tbody>
                    @forelse ($catalogAreas as $area)
                      @php($activeSchedule = collect($area['schedule'] ?? [])->filter(fn ($day) => data_get($day, 'enabled'))->map(fn ($day, $key) => ($scheduleDays[$key] ?? ucfirst($key)).' '.data_get($day, 'start', '08:00').' - '.data_get($day, 'end', '16:00'))->implode(', '))
                      <tr><td>{{ $area['location'] }}</td><td>{{ $area['floor'] }}</td><td><strong>{{ $area['unit_number'] }}</strong><small>{{ str_replace('Catalogo de ', '', $catalogTitle) }}</small></td><td>{{ $area['capacity'] }} {{ $area['capacity'] == 1 ? 'paciente simultaneo' : 'pacientes simultaneos' }}</td><td>{{ $area['responsible'] }}</td><td>{{ $activeSchedule ?: 'Sin horario' }}</td><td><a class="unit-native-button" href="{{ route('unit.dashboard', ['section' => 'procedure-areas', 'edit' => $area['id']]) }}">Editar</a></td></tr>
                    @empty
                      <tr><td colspan="7" class="unit-native-empty">Sin {{ strtolower(str_replace('Catalogo de ', 'catalogo de ', $catalogTitle)) }} registrados.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              @if (request('create') === $catalogKey || $editingArea)
                <form method="post" action="{{ $editingArea ? route('unit.procedure-areas.update', $editingArea['id']) : route('unit.procedure-areas.store') }}" class="unit-procedure-form">
                  @csrf @if($editingArea) @method('put') @endif<input type="hidden" name="type" value="{{ $catalogKey }}">
                  <header><strong>{{ $editingArea ? 'Editar '.$catalogSingular : 'Nueva subunidad' }}</strong><span>{{ ucfirst($catalogSingular) }}</span></header>
                  <label>Ubicacion<input name="location" value="{{ $editingArea['location'] ?? '' }}" required></label><label>Piso<input name="floor" value="{{ $editingArea['floor'] ?? '' }}" required></label>
                  <label>Numero de unidad<input name="unit_number" value="{{ $editingArea['unit_number'] ?? '' }}" required></label><label>Capacidad simultanea de pacientes<input type="number" min="1" name="capacity" value="{{ $editingArea['capacity'] ?? '' }}" required></label>
                  <label class="wide">Responsable de area<select name="responsible"><option value="" @selected(($editingArea['responsible'] ?? '') === 'Sin responsable')>Sin responsable asignado</option>@foreach ($unit->operationalProfiles as $profile)<option value="{{ $profile->user?->name }}" @selected(($editingArea['responsible'] ?? '') === $profile->user?->name)>{{ $profile->user?->name }}</option>@endforeach</select></label>
                  <fieldset class="wide"><legend>Horario de atencion de la unidad</legend><div class="unit-procedure-schedule">
                    @foreach ($scheduleDays as $dayKey => $dayLabel) @php($dayEnabled = $editingArea ? data_get($editingArea, "schedule.$dayKey.enabled", false) : $loop->iteration <= 5)<div @class(['is-enabled' => $dayEnabled])><label><input type="checkbox" name="schedule[{{ $dayKey }}][enabled]" value="1" @checked($dayEnabled)>{{ $dayLabel }}</label><span>+</span><label>Hora inicio<input type="time" name="schedule[{{ $dayKey }}][start]" value="{{ data_get($editingArea, "schedule.$dayKey.start", '08:00') }}"></label><label>Hora fin<input type="time" name="schedule[{{ $dayKey }}][end]" value="{{ data_get($editingArea, "schedule.$dayKey.end", '16:00') }}"></label></div>@endforeach
                  </div></fieldset>
                  <div class="unit-procedure-form-actions wide"><button type="submit">{{ $editingArea ? 'Guardar cambios' : 'Guardar subunidad' }}</button><a href="{{ route('unit.dashboard', ['section' => 'procedure-areas']) }}">Regresar</a></div>
                </form>
              @endif
            </section>
          @endforeach
        </div>
        <script>
          document.querySelectorAll('[data-procedure-catalog]').forEach(card => card.querySelector('[data-procedure-export]').addEventListener('click', () => { const headers=[...card.querySelectorAll('th')].map(cell=>cell.innerText.trim()); const link=document.createElement('a'); link.href=URL.createObjectURL(new Blob([headers.join(',')+'\n'],{type:'text/csv;charset=utf-8'})); link.download=`${card.dataset.procedureCatalog}.csv`; link.click(); URL.revokeObjectURL(link.href); }));
        </script>
      @endif

      @if ($section === 'external-pharmacy')
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading unit-native-heading-action">
            <div><h2>Catalogo de farmacia externa</h2>
            <p><span data-pharmacy-visible>{{ $externalPharmacyCatalog->count() }}</span> visibles de {{ $externalPharmacyCatalog->count() }} medicamentos institucionales - {{ $externalPharmacyCatalog->where('status', 'active')->count() }} activos / {{ $externalPharmacyCatalog->where('status', 'inactive')->count() }} inactivos</p></div>
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
                @forelse ($externalPharmacyCatalog as $item)
                  <tr data-pharmacy-row>
                    <td>{{ $item->cnis ?? 'Sin CNIS' }}</td>
                    <td><strong>{{ $item->name }}</strong><small>Catalogo de farmacia externa</small></td>
                    <td>{{ data_get($item->metadata, 'group', 'Sin grupo') }}</td>
                    <td>{{ $item->presentation ?? $item->generic_name ?? 'Sin descripcion' }}</td>
                    <td>{{ data_get($item->metadata, 'coverage', 'Unidades moviles, Nucleos basicos, CESSA') }}</td>
                    <td><span @class(['unit-native-status', 'is-warning' => $item->status === 'inactive'])>{{ $statusText($item->status) }}</span><form method="post" action="{{ route('unit.external-pharmacy.status', $item) }}">@csrf @method('patch')<input type="hidden" name="status" value="{{ $item->status === 'active' ? 'inactive' : 'active' }}"><button type="submit">{{ $item->status === 'active' ? 'Desactivar' : 'Activar' }}</button></form></td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="unit-native-empty">Sin medicamentos institucionales para esta unidad.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
        <script>
          (() => { const table=document.querySelector('[data-pharmacy-table]'); const filters=[...table.querySelectorAll('.unit-native-filter-row input')]; const rows=[...table.querySelectorAll('[data-pharmacy-row]')]; const visible=document.querySelector('[data-pharmacy-visible]'); const apply=()=>{ let count=0; rows.forEach(row=>{ const cells=[...row.cells]; const show=filters.every((input,index)=>cells[index].innerText.toLowerCase().includes(input.value.trim().toLowerCase())); row.hidden=!show; if(show) count++; }); visible.textContent=count; }; filters.forEach(input=>input.addEventListener('input',apply)); document.querySelector('[data-pharmacy-export]').addEventListener('click',()=>{ const data=[['CNIS','Insumo','Grupo','Descripcion','Cobertura','Estatus'],...rows.filter(row=>!row.hidden).map(row=>[...row.cells].map(cell=>cell.innerText.trim()))]; const blob=new Blob([data.map(row=>row.map(value=>`"${value.replaceAll('"','""')}"`).join(',')).join('\n')],{type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='catalogo-farmacia-externa.csv'; link.click(); URL.revokeObjectURL(link.href); }); })();
        </script>
      @endif
    </section>
  </div>
@endsection
