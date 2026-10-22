@push('styles')
  <link rel="stylesheet" href="{{ asset('css/patient-catalog.css') }}?v={{ filemtime(public_path('css/patient-catalog.css')) }}">
@endpush
@push('scripts')
  <script src="{{ asset('js/patient-catalog.js') }}?v={{ filemtime(public_path('js/patient-catalog.js')) }}" defer></script>
@endpush
<div id="alta-paciente" @class(['operational-modal', 'is-open' => request('modal') === 'patient' || request('section') === 'patient-create' || (bool) $editingPatient || old('_patient_form'), 'shared-patient-editor']) role="dialog" aria-modal="true" aria-labelledby="alta-paciente-title" data-patient-editor>
  <a class="operational-modal-backdrop" href="{{ $patientCatalogUrl }}" aria-label="Cerrar"></a>
  <form class="operational-modal-card" method="post" action="{{ $editingPatient ? route('operational.patients.update', $editingPatient) : route('operational.patients.store') }}">
    @csrf
    @if($editingPatient) @method('patch') @endif
    <input type="hidden" name="unit" value="{{ $patientUnit->id }}">
    <input type="hidden" name="_patient_return" value="{{ $patientWorkspace }}">
    <input type="hidden" name="_patient_form" value="1">
    <header><div><h2 id="alta-paciente-title">{{ $editingPatient ? 'Editar paciente' : 'Alta de paciente' }}</h2><p>{{ $patientUnit->name }} · Catálogo compartido de pacientes</p></div><a href="{{ $patientCatalogUrl }}" data-patient-editor-close>Cerrar</a></header>
    @if ($errors->any() && old('_patient_form'))<div class="shared-patient-errors" role="alert"><strong>Revisa los datos del paciente.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="operational-modal-body">
      <label>Nombre<input name="first_name" required maxlength="120" value="{{ old('first_name', $editingPatient?->first_name ?: ($editingPatient ? str($editingPatient->full_name)->before(' ') : '')) }}"></label>
      <label>Apellidos<input name="last_name" required maxlength="160" value="{{ old('last_name', $editingPatient?->last_name ?: ($editingPatient ? str($editingPatient->full_name)->after(' ') : '')) }}"></label>
      <label>Fecha de nacimiento<input name="birth_date" type="date" value="{{ old('birth_date', $editingPatient?->birth_date?->format('Y-m-d')) }}"></label>
      <label>Sexo<select name="sex"><option value="">Sin especificar</option>@foreach (['female'=>'Femenino', 'male'=>'Masculino', 'other'=>'Otro'] as $value=>$label)<option value="{{ $value }}" @selected(old('sex', $editingPatient?->sex) === $value)>{{ $label }}</option>@endforeach</select></label>
      <label>CURP<input name="curp" maxlength="18" value="{{ old('curp', $editingPatient?->curp ?: data_get($editingPatient?->metadata, 'curp')) }}"></label>
      <label>NSS federal<input name="nss_federal" maxlength="40" value="{{ old('nss_federal', data_get($editingPatient?->metadata, 'nss_federal') ?: data_get($editingPatient?->metadata, 'nss')) }}"></label>
      <label>NSS estatal<input name="nss_estatal" maxlength="40" value="{{ old('nss_estatal', data_get($editingPatient?->metadata, 'nss_estatal')) }}"></label>
      <label>Número de usuario de la plataforma<input name="platform_number" maxlength="80" value="{{ old('platform_number', $editingPatient?->platform_number) }}"></label>
      <label>Teléfono<input name="phone" type="tel" maxlength="40" value="{{ old('phone', $editingPatient?->phone) }}"></label>
      <label>Correo<input name="email" type="email" maxlength="160" value="{{ old('email', $editingPatient?->email) }}"></label>
      <label>Entidad federativa<input name="state" maxlength="100" value="{{ old('state', data_get($editingPatient?->metadata, 'state')) }}"></label>
      <label>Estatus<select name="status">@foreach (['active'=>'Activo', 'inactive'=>'Inactivo'] as $value=>$label)<option value="{{ $value }}" @selected(old('status', $editingPatient?->status ?? 'active') === $value)>{{ $label }}</option>@endforeach</select></label>
    </div>
    <footer><a href="{{ $patientCatalogUrl }}">Cancelar</a><button type="submit">{{ $editingPatient ? 'Guardar cambios' : 'Guardar paciente' }}</button></footer>
  </form>
</div>
