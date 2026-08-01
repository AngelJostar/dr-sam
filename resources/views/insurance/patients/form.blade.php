@extends('layouts.app', ['title' => $mode === 'create' ? 'Alta paciente' : 'Editar paciente'])

@section('content')
  @include('insurance._nav', ['abilities' => ['admin_users' => false]])
  @include('insurance._flash')

  <section class="page-heading insurance-section-hero">
    <div>
      <p class="eyebrow">Pacientes</p>
      <h1>{{ $mode === 'create' ? 'Alta de paciente' : 'Editar paciente' }}</h1>
      <p>Datos generales, poliza, medico tratante y nivel de riesgo operativo.</p>
    </div>
  </section>

  <form class="insurance-form insurance-patient-form" method="post" action="{{ $mode === 'create' ? route('insurance.patients.store') : route('insurance.patients.update', $patient) }}">
    @csrf
    @if($mode === 'edit')
      @method('PUT')
    @endif

    <section class="form-section insurance-form-panel">
      <h2>Datos generales</h2>
      <div class="form-grid">
        <label>Nombre completo<input name="full_name" value="{{ old('full_name', $patient->full_name) }}" required></label>
        <label>CURP<input name="curp" maxlength="18" value="{{ old('curp', $patient->curp) }}"></label>
        <label>RFC<input name="rfc" maxlength="13" value="{{ old('rfc', $patient->rfc) }}"></label>
        <label>Fecha nacimiento<input type="date" name="birth_date" value="{{ old('birth_date', $patient->birth_date?->format('Y-m-d')) }}"></label>
        <label>Sexo
          <select name="sex">
            @foreach(['' => 'Seleccionar', 'Femenino' => 'Femenino', 'Masculino' => 'Masculino', 'No especificado' => 'No especificado'] as $key => $label)
              <option value="{{ $key }}" @selected(old('sex', $patient->sex) === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
        <label>Telefono<input name="phone" value="{{ old('phone', $patient->phone) }}"></label>
        <label>Correo<input type="email" name="email" value="{{ old('email', $patient->email) }}"></label>
        <label>Medico tratante
          <select name="primary_doctor_id">
            <option value="">Sin asignar</option>
            @foreach($doctors as $doctor)
              <option value="{{ $doctor->id }}" @selected((string) old('primary_doctor_id', $patient->primary_doctor_id) === (string) $doctor->id)>{{ $doctor->full_name }} / {{ $doctor->specialty }}</option>
            @endforeach
          </select>
        </label>
        <label class="span-2">Direccion<textarea name="address">{{ old('address', $patient->address) }}</textarea></label>
      </div>
    </section>

    @if($mode === 'create')
      <section class="form-section insurance-form-panel">
        <h2>Poliza</h2>
        <div class="form-grid">
          <label>Numero de poliza<input name="policy_number" value="{{ old('policy_number', $policy->policy_number) }}" required></label>
          <label>Aseguradora<input name="insurer_name" value="{{ old('insurer_name', $policy->insurer_name) }}" required></label>
          <label>Plan<input name="plan_name" value="{{ old('plan_name', $policy->plan_name) }}"></label>
          <label>Empresa contratante<input name="employer_name" value="{{ old('employer_name', $policy->employer_name) }}"></label>
        </div>
      </section>
    @endif

    <section class="form-section insurance-form-panel">
      <h2>Control operativo</h2>
      <div class="form-grid">
        <label>Estatus
          <select name="status" required>
            @foreach(['active' => 'Activo', 'suspended' => 'Suspendido', 'discharged' => 'Dado de baja', 'deceased' => 'Fallecido'] as $key => $label)
              <option value="{{ $key }}" @selected(old('status', $patient->status) === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
        <label>Nivel de riesgo
          <select name="risk_level" required>
            @foreach(['low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto', 'critical' => 'Critico'] as $key => $label)
              <option value="{{ $key }}" @selected(old('risk_level', $patient->risk_level) === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
        <label>Fecha alta programa<input type="date" name="enrolled_at" value="{{ old('enrolled_at', $patient->enrolled_at?->format('Y-m-d')) }}"></label>
        <label class="span-2">Observaciones<textarea name="general_observations">{{ old('general_observations', $patient->general_observations) }}</textarea></label>
      </div>
    </section>

    <div class="form-actions">
      <a class="secondary-button" href="{{ route('insurance.patients.index') }}">Cancelar</a>
      <button type="submit">{{ $mode === 'create' ? 'Crear paciente' : 'Guardar cambios' }}</button>
    </div>
  </form>
@endsection
