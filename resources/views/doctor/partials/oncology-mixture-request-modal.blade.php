@php
  $isOperationalOncologyRequest = ($oncologyRequestContext ?? 'doctor') === 'operational';
  $oncologyExistingRequest = $oncologyExistingRequest ?? null;
  $isIncomingOncologyRequest = $isOperationalOncologyRequest && filled($oncologyExistingRequest);
  $existingRequestPayload = $oncologyExistingRequest?->payload ?? [];
  $existingClinicalFormat = data_get($existingRequestPayload, 'clinical_format', []);
  $clinicalValue = fn (string $key, $default = null) => old("oncology.$key", data_get($existingClinicalFormat, $key, $default));
  $oncologyDoctor = $doctor ?? null;
  $oncologyCatalogItems = collect(data_get($cbtaCatalogs ?? [], 'oncology', []));
  $oncologyCatalogVersion = data_get($cbtaCatalogVersions ?? [], 'oncology');
  $oncologyInfusors = collect(data_get($cbtaCatalogOptions ?? [], 'oncology_infusors', []));
  $oncologyServices = isset($oncologyServiceOptions)
    ? collect($oncologyServiceOptions)->filter()->unique()->values()
    : collect($services)
      ->where('request_type', 'chemo')
      ->pluck('name')
      ->filter()
      ->unique()
      ->values();
  if ($oncologyServices->isEmpty()) {
    $oncologyServices = collect(['Quimioterapia']);
  }
  $oncologyUnitName = data_get($existingClinicalFormat, 'facility')
    ?: ($oncologyUnitName ?? ($oncologyDoctor?->medicalUnit?->name ?? 'Atencion privada'));
  $oncologyDoctorName = data_get($existingClinicalFormat, 'doctor_name')
    ?: data_get($existingRequestPayload, 'doctor')
    ?: ($oncologyDoctorName ?? ($oncologyDoctor?->full_name ?? ''));
  $oncologyProfessionalLicense = data_get($existingClinicalFormat, 'professional_license')
    ?: ($oncologyProfessionalLicense ?? ($oncologyDoctor?->professional_license ?? ''));
  $oncologyMedicalUnitId = $oncologyMedicalUnitId ?? null;
  $oncologyFormAction = $oncologyFormAction ?? route('doctor.service_requests.store');
  $oncologyFormMethod = strtolower($oncologyFormMethod ?? 'post');
  $programmingDate = substr((string) old(
    'required_at',
    $oncologyExistingRequest?->required_at?->format('Y-m-d') ?: data_get($existingClinicalFormat, 'request_date', now()->toDateString()),
  ), 0, 10);
  $oldMedicationRows = collect(old('oncology.medications', data_get($existingClinicalFormat, 'medications', [])));
  $lastUsedMedicationRow = $oldMedicationRows
    ->filter(fn ($item) => collect($item)->flatten()->contains(fn ($value) => filled($value)))
    ->keys()
    ->map(fn ($key) => (int) $key)
    ->max();
  $visibleMedicationRows = $lastUsedMedicationRow === null ? 1 : min(8, $lastUsedMedicationRow + 1);
  $closeUrl = $oncologyCloseUrl ?? route('doctor.dashboard');
  $oncologyInfusionRooms = collect($oncologyInfusionRooms ?? []);
  $oncologyInfusionNurses = collect($oncologyInfusionNurses ?? [])->filter()->unique()->values();
  $existingAssignment = data_get($existingRequestPayload, 'infusion_assignment', []);
  $assignmentDate = old('assignment.application_date', data_get($existingAssignment, 'application_date', $programmingDate));
  $selectedPatientId = old('patient_id', $oncologyExistingRequest?->patient_id);
  $selectedService = old('service', data_get($existingRequestPayload, 'service'));
  $diagnosisValue = old('diagnosis', data_get($existingRequestPayload, 'diagnosis'));
  $notesValue = old('notes', data_get($existingRequestPayload, 'notes'));
  $attachmentName = data_get($existingRequestPayload, 'attachment.original_name', 'Firma y cedula recibida');
@endphp

<div @class(['doctor-oncology-request-modal', 'is-operational' => $isOperationalOncologyRequest]) data-oncology-request-modal role="dialog" aria-modal="true" aria-labelledby="doctor-oncology-request-title">
  <a class="doctor-oncology-request-backdrop" href="{{ $closeUrl }}" aria-label="Cerrar solicitud"></a>

  <section class="doctor-oncology-request-card">
    <form method="post" action="{{ $oncologyFormAction }}" enctype="multipart/form-data" data-oncology-request-form @if($isIncomingOncologyRequest) data-oncology-incoming-request @endif>
      @csrf
      @if ($oncologyFormMethod !== 'post') @method($oncologyFormMethod) @endif
      <input type="hidden" name="request_type" value="chemo">
      <input type="hidden" name="priority" value="routine">
      <input type="hidden" name="oncology[request_date]" value="{{ $programmingDate }}" data-oncology-request-date>
      <input type="hidden" name="oncology[professional_license]" value="{{ old('oncology.professional_license', $oncologyProfessionalLicense) }}">
      <input type="hidden" name="integration_catalog_version" value="{{ $oncologyCatalogVersion }}">
      @if ($isOperationalOncologyRequest)
        <input type="hidden" name="workflow" value="oncology_center">
        @if (filled($oncologyMedicalUnitId))<input type="hidden" name="unit" value="{{ $oncologyMedicalUnitId }}">@endif
      @endif

      <header class="doctor-oncology-request-header">
        <h1 id="doctor-oncology-request-title">Solicitud de mezcla oncol&oacute;gica</h1>
        <a href="{{ $closeUrl }}" aria-label="Cerrar formato de solicitud">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </a>
      </header>

      <div class="doctor-oncology-request-body">
        @if ($errors->any() && (old('request_type') === 'chemo' || $isOperationalOncologyRequest))
          <div class="doctor-oncology-request-errors" role="alert">
            <strong>Revisa la informaci&oacute;n de la solicitud.</strong>
            <span>{{ $errors->first() }}</span>
          </div>
        @endif

        <section class="doctor-oncology-form-section">
          <h2>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 12h7M9 16h7"/></svg>
            Informaci&oacute;n general
          </h2>
          <div class="doctor-oncology-general-grid">
            <label>Fecha de programaci&oacute;n*<input type="date" name="required_at" value="{{ $programmingDate }}" required data-oncology-programming-date @disabled($isIncomingOncologyRequest)></label>
            <label>Hospital / Centro*
              <select name="oncology[facility]" required @disabled($isIncomingOncologyRequest)>
                <option value="{{ $oncologyUnitName }}" @selected($clinicalValue('facility', $oncologyUnitName) === $oncologyUnitName)>{{ $oncologyUnitName }}</option>
              </select>
            </label>
            <label>Servicio*
              <select name="service" required @disabled($isIncomingOncologyRequest)>
                <option value="">Seleccionar servicio</option>
                @foreach ($oncologyServices as $serviceName)
                  <option value="{{ $serviceName }}" @selected($selectedService === $serviceName)>{{ $serviceName }}</option>
                @endforeach
              </select>
            </label>
            <label>Piso*<input name="oncology[floor]" value="{{ $clinicalValue('floor') }}" maxlength="50" required placeholder="Piso" @readonly($isIncomingOncologyRequest)></label>
            <label>Cama*<input name="oncology[bed]" value="{{ $clinicalValue('bed') }}" maxlength="50" required placeholder="Cama" @readonly($isIncomingOncologyRequest)></label>
            <label>Registro del paciente*<input name="oncology[patient_identifier]" value="{{ $clinicalValue('patient_identifier') }}" maxlength="255" required placeholder="Registro" data-oncology-patient-identifier @readonly($isIncomingOncologyRequest)></label>
          </div>
        </section>

        <section class="doctor-oncology-form-section">
          <h2>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 21v-1.5a7 7 0 0 1 14 0V21"/></svg>
            Datos del paciente
          </h2>
          <div class="doctor-oncology-patient-grid">
            <label class="is-patient-name">Nombre del paciente*
              <select name="patient_id" required data-oncology-patient-select @disabled($isIncomingOncologyRequest)>
                <option value="">Nombre completo del paciente</option>
                @foreach ($patients as $patient)
                  <option
                    value="{{ $patient->id }}"
                    data-patient-identifier="{{ $patient->curp ?: $patient->platform_number }}"
                    data-patient-sex="{{ $patient->sex }}"
                    data-patient-birth="{{ $patient->birth_date?->format('Y-m-d') }}"
                    data-patient-age="{{ $patient->birth_date?->age }}"
                    data-patient-weight="{{ data_get($patient->metadata, 'weight', data_get($patient->metadata, 'peso')) }}"
                    data-patient-height="{{ data_get($patient->metadata, 'height', data_get($patient->metadata, 'talla')) }}"
                    data-patient-surface="{{ data_get($patient->metadata, 'body_surface', data_get($patient->metadata, 'superficie_corporal')) }}"
                    @selected((string) $selectedPatientId === (string) $patient->id)
                  >{{ $patient->full_name }}</option>
                @endforeach
              </select>
            </label>

            <fieldset class="doctor-oncology-sex-field">
              <legend>Sexo*</legend>
              <label><input type="radio" name="oncology[sex]" value="Femenino" required @checked($clinicalValue('sex') === 'Femenino') @disabled($isIncomingOncologyRequest)> Femenino</label>
              <label><input type="radio" name="oncology[sex]" value="Masculino" @checked($clinicalValue('sex') === 'Masculino') @disabled($isIncomingOncologyRequest)> Masculino</label>
            </fieldset>

            <label class="is-age">Edad*<input type="number" min="0" max="130" name="oncology[age]" value="{{ $clinicalValue('age') }}" placeholder="A&ntilde;os" required data-oncology-patient-age @readonly($isIncomingOncologyRequest)></label>
            <label class="is-birth-date">Fecha de nacimiento*<input type="date" name="oncology[birth_date]" value="{{ $clinicalValue('birth_date') }}" required data-oncology-patient-birth @readonly($isIncomingOncologyRequest)></label>
            <label class="is-weight">Peso*<span class="doctor-oncology-input-unit"><input type="number" min="1" max="500" step="0.01" name="oncology[weight]" value="{{ $clinicalValue('weight') }}" required @readonly($isIncomingOncologyRequest)><em>kg</em></span></label>
            <label class="is-height">Talla*<span class="doctor-oncology-input-unit"><input type="number" min="0" max="300" step="0.01" name="oncology[height]" value="{{ $clinicalValue('height') }}" required @readonly($isIncomingOncologyRequest)><em>cm</em></span></label>
            <label class="is-surface">Superficie corporal<span class="doctor-oncology-input-unit"><input type="number" min="0" max="10" step="0.01" name="oncology[body_surface]" value="{{ $clinicalValue('body_surface') }}" @readonly($isIncomingOncologyRequest)><em>m&sup2;</em></span></label>
            <label class="is-diagnosis">Diagn&oacute;stico*
              <textarea name="diagnosis" rows="3" maxlength="250" required placeholder="Escribir diagn&oacute;stico" data-oncology-count-input="diagnosis" @readonly($isIncomingOncologyRequest)>{{ $diagnosisValue }}</textarea>
              <small><span data-oncology-count="diagnosis">{{ mb_strlen((string) $diagnosisValue) }}</span> / 250</small>
            </label>
          </div>
        </section>

        <section class="doctor-oncology-form-section">
          <h2>
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="M3 10h18M8 5v14M14 10v9"/></svg>
            Tabla de medicamentos
          </h2>
          @if ($oncologyCatalogItems->isEmpty() && ! $isIncomingOncologyRequest)
            <div class="doctor-oncology-request-errors" role="alert">El hospital no tiene medicamentos oncol&oacute;gicos disponibles en su cat&aacute;logo de CBTA.</div>
          @endif
          @if (false)
          <div class="doctor-oncology-medication-scroll">
            <table class="doctor-oncology-medication-table">
              <colgroup>
                <col class="is-number"><col class="is-medicine"><col class="is-dose">
                <col class="is-check"><col class="is-check"><col class="is-check">
                <col class="is-volume"><col class="is-bolus"><col class="is-time">
                <col class="is-date"><col class="is-date"><col class="is-date">
                @unless ($isIncomingOncologyRequest)<col class="is-action">@endunless
              </colgroup>
              <thead>
                <tr>
                  <th rowspan="2">#</th><th rowspan="2">Medicamento*</th><th rowspan="2">Dosis*</th>
                  <th colspan="2">Diluyente*</th><th rowspan="2">V&iacute;a*</th><th rowspan="2">Volumen de diluci&oacute;n total (ml)*</th>
                  <th rowspan="2">No. de bolos por d&iacute;a*</th><th rowspan="2">Tiempo de infusi&oacute;n (min)*</th>
                  <th colspan="3">Fecha de entrega*</th>
                  @unless ($isIncomingOncologyRequest)<th rowspan="2">Acci&oacute;n</th>@endunless
                </tr>
                <tr><th colspan="2">Compatible con el medicamento</th><th>1</th><th>2</th><th>3</th></tr>
              </thead>
              <tbody>
                @for ($row = 0; $row < 8; $row++)
                  @php
                    $medicationRow = $oldMedicationRows->get($row, []);
                    $selectedDiluents = old("oncology.medications.$row.diluents", data_get($medicationRow, 'diluents', []));
                    $deliveryDates = old("oncology.medications.$row.delivery_dates", data_get($medicationRow, 'delivery_dates', []));
                  @endphp
                  <tr data-oncology-medication-row @if($row >= $visibleMedicationRows) hidden @endif>
                    <th scope="row">{{ $row + 1 }}</th>
                    <td>
                      <select name="oncology[medications][{{ $row }}][catalog_item]" data-oncology-catalog-item @disabled($isIncomingOncologyRequest)>
                        <option value="">Seleccionar medicamento</option>
                        @foreach ($oncologyCatalogItems as $item)
                          @php
                            $catalogValue = data_get($item, 'product_code').'|'.data_get($item, 'presentation_code');
                          @endphp
                          <option value="{{ $catalogValue }}" data-catalog='@json($item)' @selected(old("oncology.medications.$row.catalog_item", data_get($medicationRow, 'catalog_item')) === $catalogValue)>{{ data_get($item, 'generic_name') }} — {{ data_get($item, 'presentation') }}</option>
                        @endforeach
                      </select>
                      <input type="hidden" name="oncology[medications][{{ $row }}][medication]" value="{{ old("oncology.medications.$row.medication", data_get($medicationRow, 'medication')) }}" data-oncology-medication-name>
                      <input type="hidden" name="integration_items[{{ $row }}][catalog_item]" data-oncology-integration-catalog>
                      <input type="hidden" name="integration_items[{{ $row }}][quantity]" data-oncology-integration-quantity>
                      <input type="hidden" name="integration_items[{{ $row }}][unit]" value="mg">
                    </td>
                    <td><span class="doctor-oncology-input-unit"><input type="number" min="0.01" step="0.01" name="oncology[medications][{{ $row }}][dose]" value="{{ old("oncology.medications.$row.dose", data_get($medicationRow, 'dose')) }}" data-oncology-dose @readonly($isIncomingOncologyRequest)><em>mg</em></span></td>
                    <td colspan="2"><select name="oncology[medications][{{ $row }}][diluent_id]" data-oncology-diluent data-old-value="{{ old("oncology.medications.$row.diluent_id", data_get($medicationRow, 'diluent_id')) }}" @disabled($isIncomingOncologyRequest)><option value="">Diluyente</option></select></td>
                    <td><select name="oncology[medications][{{ $row }}][route_id]" data-oncology-route data-old-value="{{ old("oncology.medications.$row.route_id", data_get($medicationRow, 'route_id')) }}" @disabled($isIncomingOncologyRequest)><option value="">V&iacute;a</option></select></td>
                    <td><span class="doctor-oncology-input-unit"><input type="number" min="0.01" step="0.01" name="oncology[medications][{{ $row }}][dilution_volume]" value="{{ old("oncology.medications.$row.dilution_volume", data_get($medicationRow, 'dilution_volume')) }}" @readonly($isIncomingOncologyRequest)><em>ml</em></span></td>
                    <td><input type="number" min="1" name="oncology[medications][{{ $row }}][boluses_per_day]" value="{{ old("oncology.medications.$row.boluses_per_day", data_get($medicationRow, 'boluses_per_day', 1)) }}" @readonly($isIncomingOncologyRequest)></td>
                    <td><span class="doctor-oncology-input-unit"><input type="number" min="1" name="oncology[medications][{{ $row }}][infusion_minutes]" value="{{ old("oncology.medications.$row.infusion_minutes", data_get($medicationRow, 'infusion_minutes')) }}" @readonly($isIncomingOncologyRequest)><em>min</em></span></td>
                    @for ($date = 0; $date < 3; $date++)
                      <td><input class="doctor-oncology-delivery-date" type="date" name="oncology[medications][{{ $row }}][delivery_dates][]" value="{{ $deliveryDates[$date] ?? '' }}" aria-label="Fecha de entrega {{ $date + 1 }}, medicamento {{ $row + 1 }}" @readonly($isIncomingOncologyRequest)></td>
                    @endfor
                    @unless ($isIncomingOncologyRequest)
                      <td>
                        <button type="button" class="doctor-oncology-remove-medication" data-oncology-remove-medication aria-label="Eliminar medicamento {{ $row + 1 }}" title="Eliminar medicamento" @if($visibleMedicationRows <= 1) hidden @endif>
                          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/></svg>
                        </button>
                      </td>
                    @endunless
                  </tr>
                @endfor
              </tbody>
            </table>
          </div>
          @unless ($isIncomingOncologyRequest)
            <div class="doctor-oncology-medication-actions">
              <button type="button" data-oncology-add-medication>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Agregar medicamento
              </button>
            </div>
          @endunless

          <div class="doctor-oncology-general-grid">
            <label>Set de infusi&oacute;n
              <select name="oncology[medications][0][set_infusion]" data-oncology-set-infusion>
                <option value="0">No</option><option value="1">S&iacute;</option>
              </select>
            </label>
            <label>Infusor
              <select name="oncology[medications][0][infusor_id]" data-oncology-infusor>
                <option value="">Sin infusor</option>
                @foreach ($oncologyInfusors as $infusor)
                  <option value="{{ data_get($infusor, 'id') }}">{{ data_get($infusor, 'generic_name') ?: data_get($infusor, 'commercial_name') }}</option>
                @endforeach
              </select>
              <small>Solo aplica a medicamentos configurados para infusor.</small>
            </label>
          </div>
          @endif

          @include('doctor.partials.oncology-mixtures-fields', [
            'catalogItems' => $oncologyCatalogItems,
            'infusors' => $oncologyInfusors,
            'incoming' => $isIncomingOncologyRequest,
          ])

          <label class="doctor-oncology-observations">Observaciones adicionales y comentarios sobre v&iacute;as de administraci&oacute;n
            <textarea name="notes" rows="3" maxlength="500" placeholder="Escriba observaciones adicionales y comentarios sobre v&iacute;as de administraci&oacute;n (opcional)" data-oncology-count-input="notes" @readonly($isIncomingOncologyRequest)>{{ $notesValue }}</textarea>
            <small><span data-oncology-count="notes">{{ mb_strlen((string) $notesValue) }}</span> / 500</small>
          </label>
          <label class="doctor-oncology-observations">Alergias
            <input name="oncology[allergies]" maxlength="255" value="{{ $clinicalValue('allergies') }}" placeholder="Alergias conocidas (opcional)" @readonly($isIncomingOncologyRequest)>
          </label>
        </section>

        <section class="doctor-oncology-form-section doctor-oncology-final-grid">
          <div>
            <h2>
              <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>
              Informaci&oacute;n adicional
            </h2>
            <label>Maneras de entrega
              <select name="oncology[delivery_method]" @disabled($isIncomingOncologyRequest)>
                <option value="">Seleccionar opci&oacute;n</option>
                @foreach (['Entrega en la unidad', 'Recoger en central de mezclas', 'Entrega programada'] as $deliveryMethod)
                  <option value="{{ $deliveryMethod }}" @selected($clinicalValue('delivery_method') === $deliveryMethod)>{{ $deliveryMethod }}</option>
                @endforeach
              </select>
            </label>
            <dl class="doctor-oncology-glossary">
              <div><dt>CS</dt><dd>Cloruro de Sodio 0.9% - Sol. Fisiol&oacute;gica</dd></div>
              <div><dt>DX</dt><dd>Dextrosa 5% - Sol. Glucosada 5%</dd></div>
              <div><dt>IV</dt><dd>Intravenosa</dd></div>
              <div><dt>IM</dt><dd>Intramuscular</dd></div>
              <div><dt>SC</dt><dd>Subcut&aacute;nea</dd></div>
            </dl>
          </div>

          <div class="doctor-oncology-responsible">
            <h2>
              <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 21v-1.5a7 7 0 0 1 14 0V21"/></svg>
              M&eacute;dico responsable
            </h2>
            <label>Nombre del m&eacute;dico*<input name="oncology[doctor_name]" value="{{ old('oncology.doctor_name', $oncologyDoctorName) }}" required @readonly($isIncomingOncologyRequest)></label>
            @if ($isIncomingOncologyRequest)
              <div class="doctor-oncology-upload is-received">
                <span>Firma y c&eacute;dula</span>
                <b>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v5h14v-5"/></svg>
                  <strong>{{ $attachmentName }}</strong>
                  <small>Documento recibido con la solicitud</small>
                </b>
              </div>
            @else
              <label class="doctor-oncology-upload">
                <span>Firma y c&eacute;dula <small>(opcional)</small></span>
                <input type="file" name="authorization_file" accept=".jpg,.jpeg,.png,.pdf" data-oncology-file>
                <b>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v5h14v-5"/></svg>
                  <strong data-oncology-file-label>Subir firma y c&eacute;dula</strong>
                  <small>Formatos: JPG, PNG o PDF. M&aacute;x. 5MB</small>
                </b>
              </label>
            @endif
          </div>
        </section>

        @if ($isOperationalOncologyRequest)
          @unless ($isIncomingOncologyRequest)
            <div class="doctor-oncology-section-actions">
              <p><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>* Campos obligatorios</p>
              <a href="{{ $closeUrl }}">Cancelar</a>
              <button type="submit" name="save_mode" value="preparation" data-oncology-save-mode="preparation">Guardar solicitud</button>
            </div>
          @endunless

          <section class="doctor-oncology-form-section doctor-oncology-assignment-section">
            <h2>
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v4M17 3v4M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/><path d="M8 13h3v3H8zM14 13h2"/></svg>
              Asignaci&oacute;n de sala de infusi&oacute;n
            </h2>

            <div class="doctor-oncology-assignment-grid">
              <label>Sala de infusi&oacute;n*
                <select name="assignment[procedure_area_id]" data-oncology-infusion-room>
                  <option value="">Seleccionar sala de infusi&oacute;n</option>
                  @foreach ($oncologyInfusionRooms as $room)
                    <option value="{{ $room->id }}" @selected((string) old('assignment.procedure_area_id', data_get($existingAssignment, 'procedure_area_id')) === (string) $room->id)>{{ $room->unit_number }}{{ $room->location ? ' - '.$room->location : '' }}</option>
                  @endforeach
                </select>
              </label>

              <label>Sill&oacute;n o cama*
                <select name="assignment[seat]" data-oncology-infusion-seat>
                  <option value="">Seleccionar sill&oacute;n o cama</option>
                  @foreach ($oncologyInfusionRooms as $room)
                    @for ($seat = 1; $seat <= max(1, (int) $room->simultaneous_capacity); $seat++)
                      @php $seatValue = $room->id.'-'.$seat; @endphp
                      <option value="{{ $seatValue }}" data-infusion-room="{{ $room->id }}" @selected(old('assignment.seat', data_get($existingAssignment, 'procedure_area_id').'-'.data_get($existingAssignment, 'seat_number')) === $seatValue)>{{ $room->unit_number }} - Sill&oacute;n o cama {{ $seat }}</option>
                    @endfor
                  @endforeach
                </select>
              </label>

              <label>Fecha de infusi&oacute;n*<input type="date" name="assignment[application_date]" value="{{ $assignmentDate }}"></label>
              <label>Hora de inicio*<input type="time" name="assignment[starts_at]" value="{{ old('assignment.starts_at', data_get($existingAssignment, 'starts_at')) }}"></label>
              <label>Duraci&oacute;n estimada<span class="doctor-oncology-input-unit"><input type="number" min="1" max="1440" name="assignment[duration_minutes]" value="{{ old('assignment.duration_minutes', data_get($existingAssignment, 'duration_minutes', 60)) }}"><em>min</em></span></label>
              <label>Enfermera responsable
                <select name="assignment[nurse]">
                  <option value="">Seleccionar enfermera</option>
                  @foreach ($oncologyInfusionNurses as $nurse)
                    <option value="{{ $nurse }}" @selected(old('assignment.nurse', data_get($existingAssignment, 'nurse')) === $nurse)>{{ $nurse }}</option>
                  @endforeach
                </select>
              </label>
              <label>Tipo de sesi&oacute;n
                <select name="assignment[session_type]">
                  <option value="">Seleccionar tipo de sesi&oacute;n</option>
                  @foreach (['Quimioterapia', 'Inmunoterapia', 'Hidratacion', 'Transfusion', 'Otro'] as $sessionType)
                    <option value="{{ $sessionType }}" @selected(old('assignment.session_type', data_get($existingAssignment, 'session_type')) === $sessionType)>{{ $sessionType }}</option>
                  @endforeach
                </select>
              </label>
              <label class="is-room-notes">Observaciones de sala
                <textarea name="assignment[notes]" rows="3" maxlength="500" placeholder="Escriba observaciones relacionadas con la asignaci&oacute;n de sala (opcional)" data-oncology-count-input="assignment-notes">{{ old('assignment.notes', data_get($existingAssignment, 'notes')) }}</textarea>
                <small><span data-oncology-count="assignment-notes">{{ mb_strlen((string) old('assignment.notes', data_get($existingAssignment, 'notes'))) }}</span> / 500</small>
              </label>
            </div>

            <p class="doctor-oncology-assignment-notice">
              <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>
              El paciente podr&aacute; ser programado en una sala de infusi&oacute;n disponible seg&uacute;n la fecha y hora seleccionadas.
            </p>

            <div class="doctor-oncology-assignment-actions">
              @if ($isIncomingOncologyRequest)
                <a href="{{ $closeUrl }}">Cerrar</a>
              @else
                <button type="submit" name="save_mode" value="preparation" data-oncology-save-mode="preparation">Cancelar asignaci&oacute;n</button>
              @endif
              <button type="submit" name="save_mode" value="scheduled" data-oncology-save-mode="scheduled">Guardar y asignar sala</button>
            </div>
          </section>
        @endif
      </div>

      @unless ($isOperationalOncologyRequest)
        <footer class="doctor-oncology-request-footer">
          <p><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>* Campos obligatorios</p>
          <a href="{{ $closeUrl }}">Cancelar</a>
          <button type="submit">Guardar solicitud</button>
        </footer>
      @endunless
    </form>
  </section>
</div>
