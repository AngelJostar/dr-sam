<dialog class="expedient-wizard" data-new-expedient-dialog aria-labelledby="new-expedient-title"
  data-matches-url="{{ route('provider.import.expedients.matches') }}" data-draft-base="{{ url('/providers/import/expedients') }}">
  <form method="POST" action="{{ route('provider.import.expedients.store') }}" enctype="multipart/form-data" novalidate>
    @csrf
    <input type="hidden" name="submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
    <input type="hidden" name="patient_id"><input type="hidden" name="confirm_new_patient" value="0">
    <header class="ew-header">
      <div><h2 id="new-expedient-title">Nuevo expediente</h2><p>Completa lo esencial; puedes agregar más información después.</p></div>
      <button type="button" class="ew-close" data-close-expedient aria-label="Cerrar nuevo expediente">×</button>
    </header>
    <ol class="ew-progress" aria-label="Pasos del nuevo expediente">
      @foreach (['Paciente', 'Contacto', 'Atención', 'Expediente', 'Documentos', 'Revisión'] as $stepLabel)
        <li><button type="button" data-wizard-go="{{ $loop->index }}"><span>{{ $loop->iteration }}</span>{{ $stepLabel }}</button></li>
      @endforeach
    </ol>
    <div class="ew-scroll">
      <div class="ew-feedback" data-wizard-feedback role="status" hidden></div>
      <div class="ew-patient-summary" data-wizard-patient-summary hidden><span class="ew-symbol" aria-hidden="true"><x-klini-icon name="paciente" /></span><div><strong data-patient-summary-name></strong><small data-patient-summary-detail></small></div><span class="ew-tag" data-wizard-folio>Nuevo expediente</span></div>
      <section data-wizard-step="0" aria-labelledby="ew-patient-title">
        <h3 id="ew-patient-title" tabindex="-1">Datos del paciente</h3><p class="ew-description">Los campos con <span class="ew-required">*</span> son obligatorios.</p>
        <div class="ew-grid">
          <label class="ew-field ew-wide"><span>Nombre(s) <b>*</b></span><input name="first_name" maxlength="80" autocomplete="given-name" required></label>
          <label class="ew-field"><span>Primer apellido <b>*</b></span><input name="first_surname" maxlength="80" autocomplete="family-name" required></label>
          <label class="ew-field"><span>Segundo apellido</span><input name="second_surname" maxlength="80" autocomplete="off"></label>
          <label class="ew-field"><span>Fecha de nacimiento <b>*</b></span><input name="birth_date" type="date" max="{{ now()->toDateString() }}" required></label>
          <label class="ew-field"><span>Sexo registrado</span><select name="sex"><option value="">Seleccionar</option>@foreach (['Femenino', 'Masculino', 'Otro', 'No especificado'] as $sex)<option>{{ $sex }}</option>@endforeach</select></label>
          <label class="ew-field ew-wide"><span>CURP (opcional)</span><input name="curp" maxlength="18" minlength="18" placeholder="Puedes agregarla después" autocomplete="off" style="text-transform:uppercase"></label>
        </div>
        <p class="ew-note"><span aria-hidden="true">ⓘ</span> Verificaremos posibles coincidencias antes de guardar.</p>
      </section>
      <section data-wizard-step="1" aria-labelledby="ew-contact-title" hidden>
        <h3 id="ew-contact-title" tabindex="-1">Datos de contacto</h3><p class="ew-description">Agrega los medios de contacto disponibles.</p>
        <div class="ew-grid">
          <label class="ew-field"><span>Teléfono (opcional)</span><input name="phone" type="tel" maxlength="30" autocomplete="tel" placeholder="Número de contacto"></label>
          <label class="ew-field"><span>Correo electrónico (opcional)</span><input name="email" type="email" maxlength="254" autocomplete="email" placeholder="correo@ejemplo.com"></label>
          <label class="ew-field ew-wide"><span>Domicilio (opcional)</span><input name="address" maxlength="500" autocomplete="street-address" placeholder="Calle, número, colonia y ciudad"></label>
          <label class="ew-field"><span>Contacto alterno (opcional)</span><input name="contact_name" maxlength="180" placeholder="Nombre completo"></label>
          <label class="ew-field"><span>Teléfono del contacto</span><input name="contact_phone" type="tel" maxlength="30"></label>
        </div>
        <p class="ew-note"><span aria-hidden="true">ⓘ</span> Puedes dejar estos datos pendientes y completarlos después.</p>
      </section>
      <section data-wizard-step="2" aria-labelledby="ew-care-title" hidden>
        <h3 id="ew-care-title" tabindex="-1">Institución y atención médica</h3><p class="ew-description">Vincula el expediente con el lugar de atención.</p>
        <div class="ew-grid">
          <label class="ew-field"><span>Institución</span><select name="institution_id"><option value="">Pendiente de asignar</option>@foreach ($importInstitutions as $institution)<option value="{{ $institution->id }}">{{ $institution->name }}</option>@endforeach</select></label>
          <div><label class="ew-field"><span>Hospital</span><select name="medical_unit_id"><option value="">Pendiente de asignar</option>@foreach ($importUnits as $unit)<option value="{{ $unit->id }}" data-institution="{{ $unit->institution_id }}">{{ $unit->name }}</option>@endforeach</select></label><button class="ew-text-button" type="button" data-add-hospital>⊕ Agregar hospital</button></div>
          <label class="ew-field ew-wide" data-new-hospital-field hidden><span>Nombre del nuevo hospital</span><input name="new_hospital_name" maxlength="180" placeholder="Nombre del hospital"></label>
          <label class="ew-field"><span>Médico tratante</span><select name="doctor_id"><option value="">Pendiente de asignar</option>@foreach ($importDoctors as $doctor)<option value="{{ $doctor->id }}" data-unit="{{ $doctor->medical_unit_id }}" data-specialty="{{ $doctor->specialty }}" data-service="{{ $doctor->service_name }}">{{ $doctor->full_name }}</option>@endforeach</select></label>
          <label class="ew-field"><span>Especialidad</span><input name="specialty" maxlength="180" list="ew-specialties" placeholder="Pendiente de asignar"></label>
          <label class="ew-field ew-wide"><span>Servicio o área</span><input name="service" maxlength="180" list="ew-services" placeholder="Pendiente de asignar"></label>
        </div>
        <datalist id="ew-specialties">@foreach ($importDoctors->pluck('specialty')->filter()->unique() as $specialty)<option value="{{ $specialty }}">@endforeach</datalist>
        <datalist id="ew-services"><option value="Consulta externa"><option value="Hospitalización"><option value="Urgencias"></datalist>
        <p class="ew-note"><span aria-hidden="true">ⓘ</span> Puedes dejar estos datos pendientes y completarlos después.</p>
      </section>
      <section data-wizard-step="3" aria-labelledby="ew-record-title" hidden>
        <h3 id="ew-record-title" tabindex="-1">Datos del expediente</h3><p class="ew-description">Define la información general del expediente.</p>
        <div class="ew-grid">
          <label class="ew-field ew-readonly"><span>Folio</span><input value="Se asignará al crear" data-wizard-folio-input readonly></label>
          <div class="ew-field ew-readonly"><span>Estado inicial</span><div class="ew-status">En documentación</div></div>
          <label class="ew-field ew-wide"><span>Tipo de expediente</span><select name="expedient_type"><option>Importación de medicamento</option><option>Importación de dispositivo médico</option><option>Otro</option></select></label>
          <label class="ew-field"><span>Medicamento solicitado (opcional)</span><input name="medication_name" maxlength="180" placeholder="Buscar medicamento" list="ew-medications"></label>
          <label class="ew-field"><span>Prioridad</span><select name="priority"><option>Estándar</option><option>Alta</option><option>Urgente</option></select></label>
          @if ($provider)
            <input type="hidden" name="provider_id" value="{{ $provider->id }}">
          @else
            <label class="ew-field ew-wide"><span>Proveedor responsable <b>*</b></span><select name="provider_id" required><option value="">Seleccionar proveedor</option>@foreach ($importProviders as $importProvider)<option value="{{ $importProvider->id }}">{{ $importProvider->name }}</option>@endforeach</select></label>
          @endif
          <label class="ew-field ew-wide"><span>Observaciones (opcional)</span><textarea name="notes" maxlength="2000" rows="3" placeholder="Agrega información relevante para el seguimiento"></textarea></label>
        </div>
        <datalist id="ew-medications">@foreach ($importRecords->pluck('payload')->map(fn ($payload) => data_get($payload, 'prescription_items.0.medication_name'))->filter()->unique() as $medication)<option value="{{ $medication }}">@endforeach</datalist>
        <p class="ew-note"><span aria-hidden="true">ⓘ</span> Los permisos y el seguimiento se gestionan después del alta.</p>
      </section>
      <section data-wizard-step="4" aria-labelledby="ew-documents-title" hidden>
        <h3 id="ew-documents-title" tabindex="-1">Documentos del expediente</h3><p class="ew-description">Adjunta los archivos disponibles. Puedes completar esta sección después.</p>
        <div class="ew-dropzone" data-file-drop><span class="ew-symbol" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M8 17H6a4 4 0 0 1-1-8 7 7 0 0 1 14 1 3.5 3.5 0 0 1-1 7h-2M12 20V9m-4 4 4-4 4 4"/></svg></span><div><strong>Arrastra archivos o selecciona desde tu equipo</strong><small>PDF, JPG o PNG · Hasta 10 MB por archivo · Máximo 20 archivos</small><button type="button" class="ew-outline" data-pick-files>Seleccionar archivos</button></div></div>
        <input type="file" accept=".pdf,.jpg,.jpeg,.png" multiple data-expedient-files hidden>
        <div class="ew-files" data-file-list></div>
        <button type="button" class="ew-more-files" data-pick-files><span>▤ &nbsp; Otros documentos</span><strong>＋ Agregar</strong></button>
        <p class="ew-note"><span aria-hidden="true">ⓘ</span> Los archivos quedan vinculados al expediente.</p>
      </section>
      <section data-wizard-step="5" aria-labelledby="ew-review-title" hidden>
        <h3 id="ew-review-title" tabindex="-1">Revisa antes de crear</h3><p class="ew-description">Confirma los datos principales del expediente.</p>
        <div class="ew-review" data-wizard-review></div>
        <div class="ew-matches" data-patient-matches hidden></div>
        <p class="ew-note ew-warning" data-wizard-pending hidden></p>
        <p class="ew-note"><x-klini-icon name="permisos" /> Visible para el equipo autorizado.</p>
      </section>
    </div>
    <footer class="ew-footer"><button type="button" class="ew-outline" data-wizard-back>Atrás</button><button type="button" class="ew-text-button" data-wizard-save>Guardar borrador</button><button type="submit" class="ew-primary" data-wizard-next>Continuar <span aria-hidden="true">→</span></button></footer>
  </form>
  <template data-wizard-icon="patient"><x-klini-icon name="paciente" /></template>
  <template data-wizard-icon="contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3H3v4c0 8 6 14 14 14h4v-4l-5-2-2 2a15 15 0 0 1-7-7l2-2-3-5z"/></svg></template>
  <template data-wizard-icon="care"><x-klini-icon name="hospital" /></template>
  <template data-wizard-icon="record"><x-klini-icon name="documento" /></template>
  <template data-wizard-icon="files"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="m8 13 7-7a3 3 0 0 1 4 4L9 20a5 5 0 0 1-7-7L13 2a7 7 0 0 1 10 10L12 23M8 13l-2 2a1 1 0 0 0 2 2l9-9" transform="translate(1 0) scale(.9)"/></svg></template>
</dialog>
@push('styles')<link rel="stylesheet" href="{{ asset('css/import-expedient.css') }}?v={{ filemtime(public_path('css/import-expedient.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/import-expedient.js') }}?v={{ filemtime(public_path('js/import-expedient.js')) }}" defer></script>@endpush
