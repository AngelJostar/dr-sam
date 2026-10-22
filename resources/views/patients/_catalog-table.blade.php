<table class="{{ $patientWorkspace === 'unit' ? 'unit-native-table' : 'operational-native-table' }} shared-patient-table" data-patient-catalog-table @if($patientWorkspace === 'unit') data-unit-catalog-table @endif>
  <thead><tr><th>Paciente</th><th>Fecha de nacimiento</th><th>Edad</th><th>Sexo</th><th>CURP</th><th>NSS federal</th><th>NSS estatal</th><th>Teléfono</th><th>Correo</th><th>Entidad federativa</th><th>Servicio</th><th>Médico / Área</th><th>Estatus</th><th>Actualización</th><th data-drsam-table-filter-skip-column>Acciones</th></tr></thead>
  <tbody>
    @forelse ($patients as $patient)
      @php $lastAppointment = $patient->appointments->first(); @endphp
      <tr data-patient-row data-patient-id="{{ $patient->id }}" data-status="{{ $patient->status }}" @if($patientWorkspace === 'unit') data-unit-catalog-row @endif>
        <td><strong>{{ $patient->full_name }}</strong><small>{{ $patient->platform_number ?: $patient->record_number ?: 'Sin número de plataforma' }}</small></td>
        <td>{{ $patient->birth_date?->format('d/m/Y') ?? 'Sin fecha' }}</td>
        <td>{{ $patient->birth_date?->age ?? data_get($patient->metadata, 'age', 'Sin dato') }}</td>
        <td>{{ ['female' => 'Femenino', 'male' => 'Masculino', 'other' => 'Otro'][$patient->sex] ?? 'Sin especificar' }}</td>
        <td>{{ $patient->curp ?: data_get($patient->metadata, 'curp') ?: 'Sin CURP' }}</td>
        <td>{{ data_get($patient->metadata, 'nss_federal') ?: data_get($patient->metadata, 'nss') ?: 'Sin NSS' }}</td>
        <td>{{ data_get($patient->metadata, 'nss_estatal') ?: 'Sin NSS' }}</td>
        <td>{{ $patient->phone ?: 'Sin teléfono' }}</td><td>{{ $patient->email ?: 'Sin correo' }}</td>
        <td>{{ data_get($patient->metadata, 'state') ?: 'Sin entidad' }}</td>
        <td>{{ $lastAppointment?->specialty ?: 'Sin servicio' }}</td>
        <td>{{ $lastAppointment?->doctor?->full_name ?: 'Sin médico' }}<small>{{ $lastAppointment?->procedureArea?->unit_number }}</small></td>
        <td><span class="shared-patient-status {{ $patient->status === 'active' ? 'is-active' : '' }}">{{ ['active' => 'Activo', 'inactive' => 'Inactivo'][$patient->status] ?? $patient->status }}</span></td>
        <td>{{ $patient->updated_at?->format('d/m/Y H:i') }}</td>
        <td><a class="shared-patient-edit" href="{{ $patientCatalogUrl }}&edit_patient={{ $patient->id }}">Editar</a></td>
      </tr>
    @empty
      <tr><td colspan="15">Sin pacientes registrados para esta unidad.</td></tr>
    @endforelse
    <tr data-patient-empty @if($patientWorkspace === 'unit') data-unit-catalog-empty @endif hidden><td colspan="15">No se encontraron pacientes con estos filtros.</td></tr>
  </tbody>
</table>
