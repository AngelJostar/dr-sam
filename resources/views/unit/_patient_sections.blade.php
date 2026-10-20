@php
  $patientSections = [
    'record' => ['Expediente', 'Expedientes de los pacientes de la unidad'],
    'pre-admission' => ['Preingreso', 'Pacientes pendientes de ingreso a la unidad'],
    'admissions' => ['Admisiones', 'Ingresos de pacientes a la unidad'],
    'transfers-discharges' => ['Traslados/Altas', 'Movimientos y egresos de pacientes de la unidad'],
  ];
@endphp

@foreach ($patientSections as $patientSection => [$patientSectionTitle, $patientSectionDescription])
  <section class="unit-catalog-screen" data-unit-patient-panel="{{ $patientSection }}" hidden aria-label="{{ $patientSectionTitle }}">
    <header class="unit-service-panel-header unit-catalog-info-card">
      <div class="unit-service-panel-copy">
        <h2>{{ $patientSectionTitle }}</h2>
        <p>{{ $patientSectionDescription }}</p>
      </div>
    </header>
    <section class="unit-native-table-card unit-catalog-content-card">
      <div class="unit-native-table-scroll">
        <table class="unit-native-table" data-drsam-table-filter-empty>
          <thead><tr><th>Paciente</th><th>Expediente</th><th>Servicio</th><th>Médico / Área</th><th>Fecha</th><th>Estatus</th></tr></thead>
          <tbody><tr><td colspan="6" class="unit-native-empty">Esta sección todavía no tiene registros vinculados.</td></tr></tbody>
        </table>
      </div>
    </section>
  </section>
@endforeach
