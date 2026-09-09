<?php
  $isOperationalOncologyRequest = ($oncologyRequestContext ?? 'doctor') === 'operational';
  $oncologyExistingRequest = $oncologyExistingRequest ?? null;
  $isIncomingOncologyRequest = $isOperationalOncologyRequest && filled($oncologyExistingRequest);
  $existingRequestPayload = $oncologyExistingRequest?->payload ?? [];
  $existingClinicalFormat = data_get($existingRequestPayload, 'clinical_format', []);
  $clinicalValue = fn (string $key, $default = null) => old("oncology.$key", data_get($existingClinicalFormat, $key, $default));
  $oncologyDoctor = $doctor ?? null;
  $oncologyMedicines = [
    'Ciclofosfamida', 'Ifosfamida', 'Cisplatino', 'Carboplatino', 'Oxaliplatino',
    'Paclitaxel', 'Docetaxel', 'Doxorrubicina', 'Epirrubicina', 'Fluorouracilo 5-FU',
    'Metotrexato', 'Gemcitabina', 'Citarabina', 'Vincristina', 'Vinblastina',
    'Etoposido', 'Irinotecan', 'Rituximab', 'Trastuzumab', 'Pembrolizumab',
    'Nivolumab', 'Ondansetron', 'Dexametasona',
  ];
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
?>

<div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['doctor-oncology-request-modal', 'is-operational' => $isOperationalOncologyRequest]); ?>" data-oncology-request-modal role="dialog" aria-modal="true" aria-labelledby="doctor-oncology-request-title">
  <a class="doctor-oncology-request-backdrop" href="<?php echo e($closeUrl); ?>" aria-label="Cerrar solicitud"></a>

  <section class="doctor-oncology-request-card">
    <form method="post" action="<?php echo e($oncologyFormAction); ?>" enctype="multipart/form-data" data-oncology-request-form <?php if($isIncomingOncologyRequest): ?> data-oncology-incoming-request <?php endif; ?>>
      <?php echo csrf_field(); ?>
      <?php if($oncologyFormMethod !== 'post'): ?> <?php echo method_field($oncologyFormMethod); ?> <?php endif; ?>
      <input type="hidden" name="request_type" value="chemo">
      <input type="hidden" name="priority" value="routine">
      <input type="hidden" name="oncology[request_date]" value="<?php echo e($programmingDate); ?>" data-oncology-request-date>
      <input type="hidden" name="oncology[professional_license]" value="<?php echo e(old('oncology.professional_license', $oncologyProfessionalLicense)); ?>">
      <?php if($isOperationalOncologyRequest): ?>
        <input type="hidden" name="workflow" value="oncology_center">
        <?php if(filled($oncologyMedicalUnitId)): ?><input type="hidden" name="unit" value="<?php echo e($oncologyMedicalUnitId); ?>"><?php endif; ?>
      <?php endif; ?>

      <header class="doctor-oncology-request-header">
        <h1 id="doctor-oncology-request-title">Solicitud de mezcla oncol&oacute;gica</h1>
        <a href="<?php echo e($closeUrl); ?>" aria-label="Cerrar formato de solicitud">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </a>
      </header>

      <div class="doctor-oncology-request-body">
        <?php if($errors->any() && (old('request_type') === 'chemo' || $isOperationalOncologyRequest)): ?>
          <div class="doctor-oncology-request-errors" role="alert">
            <strong>Revisa la informaci&oacute;n de la solicitud.</strong>
            <span><?php echo e($errors->first()); ?></span>
          </div>
        <?php endif; ?>

        <section class="doctor-oncology-form-section">
          <h2>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 12h7M9 16h7"/></svg>
            Informaci&oacute;n general
          </h2>
          <div class="doctor-oncology-general-grid">
            <label>Fecha de programaci&oacute;n*<input type="date" name="required_at" value="<?php echo e($programmingDate); ?>" required data-oncology-programming-date <?php if($isIncomingOncologyRequest): echo 'disabled'; endif; ?>></label>
            <label>Hospital / Centro*
              <select name="oncology[facility]" required <?php if($isIncomingOncologyRequest): echo 'disabled'; endif; ?>>
                <option value="<?php echo e($oncologyUnitName); ?>" <?php if($clinicalValue('facility', $oncologyUnitName) === $oncologyUnitName): echo 'selected'; endif; ?>><?php echo e($oncologyUnitName); ?></option>
              </select>
            </label>
            <label>Servicio*
              <select name="service" required <?php if($isIncomingOncologyRequest): echo 'disabled'; endif; ?>>
                <option value="">Seleccionar servicio</option>
                <?php $__currentLoopData = $oncologyServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($serviceName); ?>" <?php if($selectedService === $serviceName): echo 'selected'; endif; ?>><?php echo e($serviceName); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </label>
            <label>Piso<input name="oncology[floor]" value="<?php echo e($clinicalValue('floor')); ?>" placeholder="Piso" <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>></label>
            <label>Cama<input name="oncology[bed]" value="<?php echo e($clinicalValue('bed')); ?>" placeholder="Cama" <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>></label>
            <label>C&eacute;dula<input name="oncology[patient_identifier]" value="<?php echo e($clinicalValue('patient_identifier')); ?>" placeholder="C&eacute;dula del paciente" data-oncology-patient-identifier <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>></label>
          </div>
        </section>

        <section class="doctor-oncology-form-section">
          <h2>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 21v-1.5a7 7 0 0 1 14 0V21"/></svg>
            Datos del paciente
          </h2>
          <div class="doctor-oncology-patient-grid">
            <label class="is-patient-name">Nombre del paciente*
              <select name="patient_id" required data-oncology-patient-select <?php if($isIncomingOncologyRequest): echo 'disabled'; endif; ?>>
                <option value="">Nombre completo del paciente</option>
                <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option
                    value="<?php echo e($patient->id); ?>"
                    data-patient-identifier="<?php echo e($patient->curp ?: $patient->platform_number); ?>"
                    data-patient-sex="<?php echo e($patient->sex); ?>"
                    data-patient-birth="<?php echo e($patient->birth_date?->format('Y-m-d')); ?>"
                    data-patient-age="<?php echo e($patient->birth_date?->age); ?>"
                    data-patient-weight="<?php echo e(data_get($patient->metadata, 'weight', data_get($patient->metadata, 'peso'))); ?>"
                    data-patient-height="<?php echo e(data_get($patient->metadata, 'height', data_get($patient->metadata, 'talla'))); ?>"
                    data-patient-surface="<?php echo e(data_get($patient->metadata, 'body_surface', data_get($patient->metadata, 'superficie_corporal'))); ?>"
                    <?php if((string) $selectedPatientId === (string) $patient->id): echo 'selected'; endif; ?>
                  ><?php echo e($patient->full_name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </label>

            <fieldset class="doctor-oncology-sex-field">
              <legend>Sexo*</legend>
              <label><input type="radio" name="oncology[sex]" value="Femenino" required <?php if($clinicalValue('sex') === 'Femenino'): echo 'checked'; endif; ?> <?php if($isIncomingOncologyRequest): echo 'disabled'; endif; ?>> Femenino</label>
              <label><input type="radio" name="oncology[sex]" value="Masculino" <?php if($clinicalValue('sex') === 'Masculino'): echo 'checked'; endif; ?> <?php if($isIncomingOncologyRequest): echo 'disabled'; endif; ?>> Masculino</label>
            </fieldset>

            <label class="is-age">Edad*<input type="number" min="0" max="130" name="oncology[age]" value="<?php echo e($clinicalValue('age')); ?>" placeholder="A&ntilde;os" required data-oncology-patient-age <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>></label>
            <label class="is-birth-date">Fecha de nacimiento*<input type="date" name="oncology[birth_date]" value="<?php echo e($clinicalValue('birth_date')); ?>" required data-oncology-patient-birth <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>></label>
            <label class="is-weight">Peso*<span class="doctor-oncology-input-unit"><input type="number" min="0" max="500" step="0.01" name="oncology[weight]" value="<?php echo e($clinicalValue('weight')); ?>" required <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>><em>kg</em></span></label>
            <label class="is-height">Talla*<span class="doctor-oncology-input-unit"><input type="number" min="0" max="300" step="0.01" name="oncology[height]" value="<?php echo e($clinicalValue('height')); ?>" required <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>><em>cm</em></span></label>
            <label class="is-surface">Superficie corporal<span class="doctor-oncology-input-unit"><input type="number" min="0" max="10" step="0.01" name="oncology[body_surface]" value="<?php echo e($clinicalValue('body_surface')); ?>" <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>><em>m&sup2;</em></span></label>
            <label class="is-diagnosis">Diagn&oacute;stico*
              <textarea name="diagnosis" rows="3" maxlength="250" required placeholder="Escribir diagn&oacute;stico" data-oncology-count-input="diagnosis" <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>><?php echo e($diagnosisValue); ?></textarea>
              <small><span data-oncology-count="diagnosis"><?php echo e(mb_strlen((string) $diagnosisValue)); ?></span> / 250</small>
            </label>
          </div>
        </section>

        <section class="doctor-oncology-form-section">
          <h2>
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="M3 10h18M8 5v14M14 10v9"/></svg>
            Tabla de medicamentos
          </h2>
          <datalist id="doctor-oncology-medicines">
            <?php $__currentLoopData = $oncologyMedicines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $medicine): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($medicine); ?>"></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </datalist>
          <div class="doctor-oncology-medication-scroll">
            <table class="doctor-oncology-medication-table">
              <colgroup>
                <col class="is-number"><col class="is-medicine"><col class="is-dose">
                <col class="is-check"><col class="is-check"><col class="is-check">
                <col class="is-volume"><col class="is-bolus"><col class="is-time">
                <col class="is-date"><col class="is-date"><col class="is-date">
                <?php if (! ($isIncomingOncologyRequest)): ?><col class="is-action"><?php endif; ?>
              </colgroup>
              <thead>
                <tr>
                  <th rowspan="2">#</th><th rowspan="2">Medicamento*</th><th rowspan="2">Dosis*</th>
                  <th colspan="3">Diluyente*</th><th rowspan="2">Volumen de diluci&oacute;n total (ml)*</th>
                  <th rowspan="2">No. de bolos por d&iacute;a*</th><th rowspan="2">Tiempo de infusi&oacute;n (min)*</th>
                  <th colspan="3">Fecha de entrega*</th>
                  <?php if (! ($isIncomingOncologyRequest)): ?><th rowspan="2">Acci&oacute;n</th><?php endif; ?>
                </tr>
                <tr><th>CS</th><th>DX</th><th>Otro</th><th>1</th><th>2</th><th>3</th></tr>
              </thead>
              <tbody>
                <?php for($row = 0; $row < 8; $row++): ?>
                  <?php
                    $medicationRow = $oldMedicationRows->get($row, []);
                    $selectedDiluents = old("oncology.medications.$row.diluents", data_get($medicationRow, 'diluents', []));
                    $deliveryDates = old("oncology.medications.$row.delivery_dates", data_get($medicationRow, 'delivery_dates', []));
                  ?>
                  <tr data-oncology-medication-row <?php if($row >= $visibleMedicationRows): ?> hidden <?php endif; ?>>
                    <th scope="row"><?php echo e($row + 1); ?></th>
                    <td><input list="doctor-oncology-medicines" name="oncology[medications][<?php echo e($row); ?>][medication]" value="<?php echo e(old("oncology.medications.$row.medication", data_get($medicationRow, 'medication'))); ?>" placeholder="Buscar medicamento" <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>></td>
                    <td><span class="doctor-oncology-input-unit"><input name="oncology[medications][<?php echo e($row); ?>][dose]" value="<?php echo e(old("oncology.medications.$row.dose", data_get($medicationRow, 'dose'))); ?>" <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>><em>mg</em></span></td>
                    <?php $__currentLoopData = ['CS', 'DX', 'Otro']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $diluent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <td><input type="checkbox" name="oncology[medications][<?php echo e($row); ?>][diluents][]" value="<?php echo e($diluent); ?>" aria-label="Diluyente <?php echo e($diluent); ?>, medicamento <?php echo e($row + 1); ?>" <?php if(in_array($diluent, $selectedDiluents, true)): echo 'checked'; endif; ?> <?php if($isIncomingOncologyRequest): echo 'disabled'; endif; ?>></td>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <td><span class="doctor-oncology-input-unit"><input type="number" min="0" step="0.01" name="oncology[medications][<?php echo e($row); ?>][dilution_volume]" value="<?php echo e(old("oncology.medications.$row.dilution_volume", data_get($medicationRow, 'dilution_volume'))); ?>" <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>><em>ml</em></span></td>
                    <td><input type="number" min="0" name="oncology[medications][<?php echo e($row); ?>][boluses_per_day]" value="<?php echo e(old("oncology.medications.$row.boluses_per_day", data_get($medicationRow, 'boluses_per_day'))); ?>" <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>></td>
                    <td><span class="doctor-oncology-input-unit"><input type="number" min="0" name="oncology[medications][<?php echo e($row); ?>][infusion_minutes]" value="<?php echo e(old("oncology.medications.$row.infusion_minutes", data_get($medicationRow, 'infusion_minutes'))); ?>" <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>><em>min</em></span></td>
                    <?php for($date = 0; $date < 3; $date++): ?>
                      <td><input class="doctor-oncology-delivery-date" type="date" name="oncology[medications][<?php echo e($row); ?>][delivery_dates][]" value="<?php echo e($deliveryDates[$date] ?? ''); ?>" aria-label="Fecha de entrega <?php echo e($date + 1); ?>, medicamento <?php echo e($row + 1); ?>" <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>></td>
                    <?php endfor; ?>
                    <?php if (! ($isIncomingOncologyRequest)): ?>
                      <td>
                        <button type="button" class="doctor-oncology-remove-medication" data-oncology-remove-medication aria-label="Eliminar medicamento <?php echo e($row + 1); ?>" title="Eliminar medicamento" <?php if($visibleMedicationRows <= 1): ?> hidden <?php endif; ?>>
                          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/></svg>
                        </button>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
          <?php if (! ($isIncomingOncologyRequest)): ?>
            <div class="doctor-oncology-medication-actions">
              <button type="button" data-oncology-add-medication>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Agregar medicamento
              </button>
            </div>
          <?php endif; ?>

          <label class="doctor-oncology-observations">Observaciones adicionales y comentarios sobre v&iacute;as de administraci&oacute;n
            <textarea name="notes" rows="3" maxlength="500" placeholder="Escriba observaciones adicionales y comentarios sobre v&iacute;as de administraci&oacute;n (opcional)" data-oncology-count-input="notes" <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>><?php echo e($notesValue); ?></textarea>
            <small><span data-oncology-count="notes"><?php echo e(mb_strlen((string) $notesValue)); ?></span> / 500</small>
          </label>
        </section>

        <section class="doctor-oncology-form-section doctor-oncology-final-grid">
          <div>
            <h2>
              <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>
              Informaci&oacute;n adicional
            </h2>
            <label>Maneras de entrega
              <select name="oncology[delivery_method]" <?php if($isIncomingOncologyRequest): echo 'disabled'; endif; ?>>
                <option value="">Seleccionar opci&oacute;n</option>
                <?php $__currentLoopData = ['Entrega en la unidad', 'Recoger en central de mezclas', 'Entrega programada']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deliveryMethod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($deliveryMethod); ?>" <?php if($clinicalValue('delivery_method') === $deliveryMethod): echo 'selected'; endif; ?>><?php echo e($deliveryMethod); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
            <label>Nombre del m&eacute;dico*<input name="oncology[doctor_name]" value="<?php echo e(old('oncology.doctor_name', $oncologyDoctorName)); ?>" required <?php if($isIncomingOncologyRequest): echo 'readonly'; endif; ?>></label>
            <?php if($isIncomingOncologyRequest): ?>
              <div class="doctor-oncology-upload is-received">
                <span>Firma y c&eacute;dula*</span>
                <b>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v5h14v-5"/></svg>
                  <strong><?php echo e($attachmentName); ?></strong>
                  <small>Documento recibido con la solicitud</small>
                </b>
              </div>
            <?php else: ?>
              <label class="doctor-oncology-upload">
                <span>Firma y c&eacute;dula*</span>
                <input type="file" name="authorization_file" accept=".jpg,.jpeg,.png,.pdf" required data-oncology-file>
                <b>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v5h14v-5"/></svg>
                  <strong data-oncology-file-label>Subir firma y c&eacute;dula</strong>
                  <small>Formatos: JPG, PNG o PDF. M&aacute;x. 5MB</small>
                </b>
              </label>
            <?php endif; ?>
          </div>
        </section>

        <?php if($isOperationalOncologyRequest): ?>
          <?php if (! ($isIncomingOncologyRequest)): ?>
            <div class="doctor-oncology-section-actions">
              <p><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>* Campos obligatorios</p>
              <a href="<?php echo e($closeUrl); ?>">Cancelar</a>
              <button type="submit" name="save_mode" value="preparation" data-oncology-save-mode="preparation">Guardar solicitud</button>
            </div>
          <?php endif; ?>

          <section class="doctor-oncology-form-section doctor-oncology-assignment-section">
            <h2>
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v4M17 3v4M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/><path d="M8 13h3v3H8zM14 13h2"/></svg>
              Asignaci&oacute;n de sala de infusi&oacute;n
            </h2>

            <div class="doctor-oncology-assignment-grid">
              <label>Sala de infusi&oacute;n*
                <select name="assignment[procedure_area_id]" data-oncology-infusion-room>
                  <option value="">Seleccionar sala de infusi&oacute;n</option>
                  <?php $__currentLoopData = $oncologyInfusionRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($room->id); ?>" <?php if((string) old('assignment.procedure_area_id', data_get($existingAssignment, 'procedure_area_id')) === (string) $room->id): echo 'selected'; endif; ?>><?php echo e($room->unit_number); ?><?php echo e($room->location ? ' - '.$room->location : ''); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </label>

              <label>Sill&oacute;n o cama*
                <select name="assignment[seat]" data-oncology-infusion-seat>
                  <option value="">Seleccionar sill&oacute;n o cama</option>
                  <?php $__currentLoopData = $oncologyInfusionRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php for($seat = 1; $seat <= max(1, (int) $room->simultaneous_capacity); $seat++): ?>
                      <?php $seatValue = $room->id.'-'.$seat; ?>
                      <option value="<?php echo e($seatValue); ?>" data-infusion-room="<?php echo e($room->id); ?>" <?php if(old('assignment.seat', data_get($existingAssignment, 'procedure_area_id').'-'.data_get($existingAssignment, 'seat_number')) === $seatValue): echo 'selected'; endif; ?>><?php echo e($room->unit_number); ?> - Sill&oacute;n o cama <?php echo e($seat); ?></option>
                    <?php endfor; ?>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </label>

              <label>Fecha de infusi&oacute;n*<input type="date" name="assignment[application_date]" value="<?php echo e($assignmentDate); ?>"></label>
              <label>Hora de inicio*<input type="time" name="assignment[starts_at]" value="<?php echo e(old('assignment.starts_at', data_get($existingAssignment, 'starts_at'))); ?>"></label>
              <label>Duraci&oacute;n estimada<span class="doctor-oncology-input-unit"><input type="number" min="1" max="1440" name="assignment[duration_minutes]" value="<?php echo e(old('assignment.duration_minutes', data_get($existingAssignment, 'duration_minutes', 60))); ?>"><em>min</em></span></label>
              <label>Enfermera responsable
                <select name="assignment[nurse]">
                  <option value="">Seleccionar enfermera</option>
                  <?php $__currentLoopData = $oncologyInfusionNurses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nurse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($nurse); ?>" <?php if(old('assignment.nurse', data_get($existingAssignment, 'nurse')) === $nurse): echo 'selected'; endif; ?>><?php echo e($nurse); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </label>
              <label>Tipo de sesi&oacute;n
                <select name="assignment[session_type]">
                  <option value="">Seleccionar tipo de sesi&oacute;n</option>
                  <?php $__currentLoopData = ['Quimioterapia', 'Inmunoterapia', 'Hidratacion', 'Transfusion', 'Otro']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sessionType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($sessionType); ?>" <?php if(old('assignment.session_type', data_get($existingAssignment, 'session_type')) === $sessionType): echo 'selected'; endif; ?>><?php echo e($sessionType); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </label>
              <label class="is-room-notes">Observaciones de sala
                <textarea name="assignment[notes]" rows="3" maxlength="500" placeholder="Escriba observaciones relacionadas con la asignaci&oacute;n de sala (opcional)" data-oncology-count-input="assignment-notes"><?php echo e(old('assignment.notes', data_get($existingAssignment, 'notes'))); ?></textarea>
                <small><span data-oncology-count="assignment-notes"><?php echo e(mb_strlen((string) old('assignment.notes', data_get($existingAssignment, 'notes')))); ?></span> / 500</small>
              </label>
            </div>

            <p class="doctor-oncology-assignment-notice">
              <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>
              El paciente podr&aacute; ser programado en una sala de infusi&oacute;n disponible seg&uacute;n la fecha y hora seleccionadas.
            </p>

            <div class="doctor-oncology-assignment-actions">
              <?php if($isIncomingOncologyRequest): ?>
                <a href="<?php echo e($closeUrl); ?>">Cerrar</a>
              <?php else: ?>
                <button type="submit" name="save_mode" value="preparation" data-oncology-save-mode="preparation">Cancelar asignaci&oacute;n</button>
              <?php endif; ?>
              <button type="submit" name="save_mode" value="scheduled" data-oncology-save-mode="scheduled">Guardar y asignar sala</button>
            </div>
          </section>
        <?php endif; ?>
      </div>

      <?php if (! ($isOperationalOncologyRequest)): ?>
        <footer class="doctor-oncology-request-footer">
          <p><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>* Campos obligatorios</p>
          <a href="<?php echo e($closeUrl); ?>">Cancelar</a>
          <button type="submit">Guardar solicitud</button>
        </footer>
      <?php endif; ?>
    </form>
  </section>
</div>
<?php /**PATH C:\laragon\www\dr-sam\resources\views/doctor/partials/oncology-mixture-request-modal.blade.php ENDPATH**/ ?>