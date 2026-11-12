@pushOnce('scripts', 'service-formats')
  <script src="{{ asset('js/service-formats.js') }}?v={{ filemtime(public_path('js/service-formats.js')) }}" defer></script>
@endPushOnce
<?php
  $folioPrefix = $prescriptionSettings['folio_prefix'] ?? 'RX-SA';
  $footerNote = $prescriptionSettings['footer_note'] ?? 'Formato base copiado del modulo Area Operativa de Farmacia Externa.';
?>

@unless($hideFormatHeading ?? false)
@include('superadmin._catalog-heading', [
  'headingTitle' => $serviceFormat->name ?? 'Formato de Receta Medica',
  'headingDescription' => isset($formatService) ? 'Servicios integrales · '.$formatService->name : 'Datos del paciente, indicaciones y médico tratante.',
  'headingActions' => [
    ['label' => 'Volver a servicios integrales', 'href' => route('superadmin.catalog', 'services')],
    ['label' => 'Actualizar', 'href' => request()->fullUrl()],
  ],
])
@endunless
<section class="superadmin-native-prescription-paper">
  <header class="superadmin-native-prescription-head">
    <div>
      <p>Formato de receta medica de plataforma</p>
      <h2>Formato de Receta Medica</h2>
    </div>
    <div>
      <span>Folio</span>
      <strong><?php echo e($folioPrefix); ?>-20260714</strong>
      <span>Fecha 14/07/2026</span>
    </div>
  </header>

  <div class="superadmin-native-prescription-intake">
    <label>Paciente agendado en Consulta Externa
      <select>
        <option>Andrea Montserrat Pineda Lopez - 14/07/2026 - Dr. Carter Jimmy</option>
      </select>
    </label>
    <strong><?php echo e($footerNote); ?></strong>
  </div>

  <section class="superadmin-native-prescription-section" data-admin-filter-panel="patient">
    <h3>Datos del paciente</h3>
    <div class="superadmin-native-prescription-grid">
      <label>Nombre(s)<input value="Andrea Montserrat" readonly></label>
      <label>Apellidos<input value="Pineda Lopez" readonly></label>
      <label>CURP<input value="Sin CURP" readonly></label>
      <label>NSS / registro<input value="EXP-44007" readonly></label>
      <label>Edad<input value="36" readonly></label>
      <label>Sexo<input value="Femenino" readonly></label>
      <label>Servicio<input value="Hemodialisis" readonly></label>
      <label>Consultorio<input value="Consultorio asignado" readonly></label>
      <label>Fecha de atencion<input value="14/07/2026" readonly></label>
    </div>
  </section>

  <section class="superadmin-native-prescription-section" data-admin-filter-panel="clinical">
    <h3>Diagnostico y resumen clinico</h3>
    <div class="superadmin-native-prescription-clinical">
      <label>Diagnostico presuntivo o definitivo
        <input placeholder="Escribir diagnostico">
      </label>
      <label>Resumen clinico, exploracion, evolucion e indicaciones generales
        <textarea placeholder="Escribir resumen clinico" rows="7"></textarea>
      </label>
    </div>
  </section>

  <section class="superadmin-native-prescription-section" data-admin-filter-panel="medications">
    <h3>Medicamentos indicados</h3>
    <div class="superadmin-native-prescription-meds">
      <table>
        <thead>
          <tr>
            <th>No.</th>
            <th>Clave CNIS</th>
            <th>Medicamento</th>
            <th>Dosis</th>
            <th>Presentacion</th>
            <th>Via</th>
            <th>Frecuencia<br>(x veces por dia)</th>
            <th>Duracion</th>
            <th>Cantidad</th>
            <th>Indicaciones</th>
          </tr>
        </thead>
        <tbody data-prescription-medications>
          <tr data-prescription-medication>
            <td class="superadmin-prescription-row-control">
              <span data-medication-number>1</span>
              <button type="button" class="superadmin-native-prescription-remove" data-remove-medication aria-label="Quitar medicamento">-</button>
            </td>
            <td><input name="medications[0][cnis]" data-medication-field="cnis" placeholder="CNIS"></td>
            <td><input name="medications[0][medication]" data-medication-field="medication" placeholder="Escribir medicamento libre"></td>
            <td><input name="medications[0][dose]" data-medication-field="dose" placeholder="Dosis"></td>
            <td><input name="medications[0][presentation]" data-medication-field="presentation" placeholder="Presentacion"></td>
            <td><input name="medications[0][route]" data-medication-field="route" placeholder="Via"></td>
            <td><input name="medications[0][frequency]" data-medication-field="frequency" placeholder="Frecuencia (x veces)"></td>
            <td><input name="medications[0][duration]" data-medication-field="duration" placeholder="Duracion"></td>
            <td><input name="medications[0][quantity]" data-medication-field="quantity" placeholder="Piezas"></td>
            <td><textarea name="medications[0][instructions]" data-medication-field="instructions" placeholder="Indicaciones" rows="2"></textarea></td>
          </tr>
        </tbody>
      </table>
      <button type="button" class="superadmin-native-prescription-add" data-add-medication aria-label="Agregar medicamento">+</button>
    </div>
  </section>

  <section class="superadmin-native-prescription-section" data-admin-filter-panel="doctor">
    <h3>Medico tratante</h3>
    <div class="superadmin-native-prescription-grid is-doctor">
      <label>Nombre del medico<input value="Dr. Carter Jimmy" readonly></label>
            <label>Cedula profesional<input value="CED-2026" readonly></label>
      <label>Especialidad<input value="Medicina interna" readonly></label>
      <label>Firma y sello<input value="Dr. Carter Jimmy - Ced. 1234567890" readonly></label>
    </div>
  </section>
</section>
