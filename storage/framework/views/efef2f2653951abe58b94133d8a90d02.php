<?php $__env->startSection('content'); ?>
  <?php echo $__env->make('insurance._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  <?php echo $__env->make('insurance._flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  <?php ($policy = $patient->insurancePolicies->sortByDesc('created_at')->first()); ?>

  <section class="record-header insurance-record-header">
    <div>
      <p class="eyebrow">Expediente del paciente</p>
      <h1><?php echo e($patient->full_name); ?></h1>
      <p><?php echo e($policy?->insurer_name ?? 'Sin aseguradora'); ?> / Poliza <?php echo e($policy?->policy_number ?? 'sin poliza'); ?></p>
    </div>
    <div class="record-actions">
      <span class="badge risk-<?php echo e($patient->risk_level); ?>"><?php echo e($patient->risk_level); ?></span>
      <span class="badge"><?php echo e($patient->status); ?></span>
      <?php if($abilities['manage_patients']): ?>
        <a class="secondary-button" href="<?php echo e(route('insurance.patients.edit', $patient)); ?>">Editar</a>
      <?php endif; ?>
    </div>
  </section>

  <section class="record-summary insurance-record-summary">
    <article>
      <span>CURP</span>
      <strong><?php echo e($patient->curp ?? 'No capturada'); ?></strong>
    </article>
    <article>
      <span>RFC</span>
      <strong><?php echo e($patient->rfc ?? 'No capturado'); ?></strong>
    </article>
    <article>
      <span>Edad</span>
      <strong><?php echo e($patient->birth_date ? $patient->birth_date->age.' anos' : 'No disponible'); ?></strong>
    </article>
    <article>
      <span>Usuario plataforma</span>
      <strong><?php echo e($patient->user?->username ?? 'Sin usuario'); ?></strong>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Datos generales</h2>
      </div>
      <dl class="detail-list">
        <div><dt>Telefono</dt><dd><?php echo e($patient->phone ?? 'Sin telefono'); ?></dd></div>
        <div><dt>Correo</dt><dd><?php echo e($patient->email ?? 'Sin correo'); ?></dd></div>
        <div><dt>Direccion</dt><dd><?php echo e($patient->address ?? 'Sin direccion'); ?></dd></div>
        <div><dt>Medico tratante</dt><dd><?php echo e($patient->primaryDoctor?->full_name ?? 'Sin asignar'); ?></dd></div>
        <div><dt>Alta programa</dt><dd><?php echo e($patient->enrolled_at?->format('d/m/Y') ?? 'Sin fecha'); ?></dd></div>
        <div><dt>Observaciones</dt><dd><?php echo e($patient->general_observations ?? 'Sin observaciones'); ?></dd></div>
      </dl>
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Poliza</h2>
      </div>
      <dl class="detail-list">
        <div><dt>Numero</dt><dd><?php echo e($policy?->policy_number ?? 'Sin poliza'); ?></dd></div>
        <div><dt>Aseguradora</dt><dd><?php echo e($policy?->insurer_name ?? 'Sin aseguradora'); ?></dd></div>
        <div><dt>Plan</dt><dd><?php echo e($policy?->plan_name ?? 'Sin plan'); ?></dd></div>
        <div><dt>Empresa</dt><dd><?php echo e($policy?->employer_name ?? 'No aplica'); ?></dd></div>
      </dl>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Diagnosticos</h2>
        <span><?php echo e($patient->diagnoses->count()); ?></span>
      </div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $patient->diagnoses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $diagnosis): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div>
            <strong><?php echo e($diagnosis->condition_name); ?></strong>
            <span><?php echo e($diagnosis->cie10 ?? 'Sin CIE-10'); ?> / <?php echo e($diagnosis->status); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin diagnosticos.</p>
        <?php endif; ?>
      </div>

      <?php if($abilities['manage_treatments']): ?>
        <form class="inline-form insurance-inline-form" method="post" action="<?php echo e(route('insurance.diagnoses.store')); ?>">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="patient_id" value="<?php echo e($patient->id); ?>">
          <label>Padecimiento
            <select name="chronic_condition_id" onchange="this.form.condition_name.value=this.options[this.selectedIndex].text">
              <option value="">Otra</option>
              <?php $__currentLoopData = $conditions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $condition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($condition->id); ?>"><?php echo e($condition->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Nombre diagnostico<input name="condition_name" required></label>
          <label>CIE-10<input name="cie10"></label>
          <label>Fecha diagnostico<input type="date" name="diagnosed_at"></label>
          <label>Medico
            <select name="doctor_id">
              <option value="">Sin asignar</option>
              <?php $__currentLoopData = $doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($doctor->id); ?>"><?php echo e($doctor->full_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Especialidad<input name="specialty"></label>
          <label>Seguimiento<input name="follow_up_frequency" placeholder="Mensual, trimestral"></label>
          <label>Estatus
            <select name="status">
              <option value="controlled">Controlado</option>
              <option value="in_surveillance">En vigilancia</option>
              <option value="decompensated">Descompensado</option>
              <option value="critical">Critico</option>
            </select>
          </label>
          <label class="span-2">Tratamiento indicado<textarea name="indicated_treatment"></textarea></label>
          <label class="span-2">Estudios requeridos<textarea name="required_studies"></textarea></label>
          <button type="submit">Agregar diagnostico</button>
        </form>
      <?php endif; ?>
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Tratamientos activos</h2>
        <span><?php echo e($patient->treatments->where('status', 'active')->count()); ?></span>
      </div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $patient->treatments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $treatment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div>
            <strong><?php echo e($treatment->medication_name); ?></strong>
            <span><?php echo e($treatment->dose); ?> / <?php echo e($treatment->frequency); ?> / <?php echo e($treatment->status); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin tratamientos.</p>
        <?php endif; ?>
      </div>

      <?php if($abilities['manage_treatments']): ?>
        <form class="inline-form insurance-inline-form" method="post" action="<?php echo e(route('insurance.treatments.store')); ?>">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="patient_id" value="<?php echo e($patient->id); ?>">
          <label>Diagnostico
            <select name="patient_diagnosis_id">
              <option value="">Sin ligar</option>
              <?php $__currentLoopData = $patient->diagnoses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $diagnosis): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($diagnosis->id); ?>"><?php echo e($diagnosis->condition_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Medicamento catalogo
            <select name="medication_id">
              <option value="">Captura manual</option>
              <?php $__currentLoopData = $medications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $medication): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($medication->id); ?>"><?php echo e($medication->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Nombre medicamento<input name="medication_name" required></label>
          <label>Sustancia activa<input name="active_substance"></label>
          <label>Presentacion<input name="presentation"></label>
          <label>Dosis<input name="dose"></label>
          <label>Frecuencia<input name="frequency"></label>
          <label>Duracion<input name="duration"></label>
          <label>Inicio<input type="date" name="starts_at"></label>
          <label>Termino<input type="date" name="ends_at"></label>
          <label>Medico prescribe
            <select name="prescribing_doctor_id">
              <option value="">Sin asignar</option>
              <?php $__currentLoopData = $doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($doctor->id); ?>"><?php echo e($doctor->full_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Estatus
            <select name="status">
              <option value="active">Activo</option>
              <option value="suspended">Suspendido</option>
              <option value="changed">Cambiado</option>
              <option value="finished">Terminado</option>
            </select>
          </label>
          <label class="checkbox-line"><input type="checkbox" name="requires_authorization" value="1"> Requiere autorizacion</label>
          <label class="span-2">Motivo de cambio<textarea name="change_reason"></textarea></label>
          <button type="submit">Registrar tratamiento</button>
        </form>
      <?php endif; ?>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Entregas de medicamentos</h2>
        <a class="text-link" href="<?php echo e(route('insurance.deliveries.index')); ?>">Ver todas</a>
      </div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $patient->medicationDeliveries->sortByDesc('scheduled_delivery_date')->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div>
            <strong><?php echo e($delivery->treatment?->medication_name ?? 'Medicamento'); ?></strong>
            <span><?php echo e($delivery->scheduled_delivery_date?->format('d/m/Y')); ?> / <?php echo e($delivery->status); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin entregas.</p>
        <?php endif; ?>
      </div>

      <?php if($abilities['manage_deliveries']): ?>
        <form class="inline-form insurance-inline-form" method="post" action="<?php echo e(route('insurance.deliveries.store')); ?>">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="patient_id" value="<?php echo e($patient->id); ?>">
          <label>Tratamiento
            <select name="treatment_id">
              <option value="">Sin ligar</option>
              <?php $__currentLoopData = $patient->treatments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $treatment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($treatment->id); ?>"><?php echo e($treatment->medication_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Cantidad<input type="number" min="0" name="quantity_delivered" value="1" required></label>
          <label>Periodo cubierto<input name="covered_period" placeholder="30 dias"></label>
          <label>Fecha programada<input type="date" name="scheduled_delivery_date" required></label>
          <label>Responsable<input name="delivery_responsible"></label>
          <label>Estatus
            <select name="status">
              <option value="pending">Pendiente</option>
              <option value="in_route">En ruta</option>
              <option value="delivered">Entregado</option>
              <option value="rescheduled">Reprogramado</option>
            </select>
          </label>
          <label class="span-2">Domicilio entrega<textarea name="delivery_address"><?php echo e($patient->address); ?></textarea></label>
          <button type="submit">Programar entrega</button>
        </form>
      <?php endif; ?>
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Hospitalizaciones</h2>
        <a class="text-link" href="<?php echo e(route('insurance.hospitalizations.index')); ?>">Ver tablero</a>
      </div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $patient->hospitalizations->sortByDesc('admitted_at')->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospitalization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <a href="<?php echo e(route('insurance.hospitalizations.show', $hospitalization)); ?>">
            <strong><?php echo e($hospitalization->hospital_name ?? $hospitalization->hospital?->name ?? 'Hospital'); ?></strong>
            <span><?php echo e($hospitalization->admitted_at?->format('d/m/Y')); ?> / <?php echo e($hospitalization->status); ?> / <?php echo e($hospitalization->stay_days); ?> dias</span>
          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin hospitalizaciones.</p>
        <?php endif; ?>
      </div>

      <?php if($abilities['manage_hospitalizations']): ?>
        <form class="inline-form insurance-inline-form" method="post" action="<?php echo e(route('insurance.hospitalizations.store')); ?>">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="patient_id" value="<?php echo e($patient->id); ?>">
          <label>Hospital
            <select name="hospital_id">
              <option value="">Captura manual</option>
              <?php $__currentLoopData = $hospitals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospital): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($hospital->id); ?>"><?php echo e($hospital->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Nombre hospital<input name="hospital_name"></label>
          <label>Ingreso<input type="datetime-local" name="admitted_at" required></label>
          <label>Area
            <select name="area">
              <option value="urgencias">Urgencias</option>
              <option value="hospitalizacion">Hospitalizacion</option>
              <option value="uci">UCI</option>
              <option value="quirofano">Quirofano</option>
              <option value="terapia_intermedia">Terapia intermedia</option>
            </select>
          </label>
          <label>Tipo evento
            <select name="event_type">
              <option value="programmed">Programado</option>
              <option value="emergency">Urgencia</option>
              <option value="complication">Complicacion</option>
              <option value="relapse">Recaida</option>
            </select>
          </label>
          <label>Estatus
            <select name="status">
              <option value="active">Activa</option>
              <option value="in_review">En revision</option>
              <option value="discharged">Egresada</option>
            </select>
          </label>
          <label>Autorizacion<input name="authorization_number"></label>
          <label>Monto autorizado<input type="number" step="0.01" min="0" name="authorized_amount" value="0"></label>
          <label class="span-2">Motivo<textarea name="reason"></textarea></label>
          <label class="span-2">Diagnostico ingreso<input name="admission_diagnosis"></label>
          <button type="submit">Registrar hospitalizacion</button>
        </form>
      <?php endif; ?>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Autorizaciones</h2>
        <a class="text-link" href="<?php echo e(route('insurance.authorizations.index')); ?>">Ver todas</a>
      </div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $patient->authorizations->sortByDesc('requested_at')->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $authorization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div>
            <strong><?php echo e($authorization->type); ?> / <?php echo e($authorization->status); ?></strong>
            <span><?php echo e($authorization->authorization_number ?? 'Sin numero'); ?> / $<?php echo e(number_format((float) $authorization->authorized_amount, 2)); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin autorizaciones.</p>
        <?php endif; ?>
      </div>

      <?php if($abilities['manage_authorizations']): ?>
        <form class="inline-form insurance-inline-form" method="post" action="<?php echo e(route('insurance.authorizations.store')); ?>">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="patient_id" value="<?php echo e($patient->id); ?>">
          <label>Tipo
            <select name="type">
              <option value="medication">Medicamento</option>
              <option value="hospitalization">Hospitalizacion</option>
              <option value="procedure">Procedimiento</option>
              <option value="study">Estudio</option>
              <option value="stay_extension">Prorroga estancia</option>
            </select>
          </label>
          <label>Estatus
            <select name="status">
              <option value="requested">Solicitada</option>
              <option value="in_review">En revision</option>
              <option value="authorized">Autorizada</option>
              <option value="rejected">Rechazada</option>
              <option value="expired">Vencida</option>
            </select>
          </label>
          <label>Fecha solicitud<input type="date" name="requested_at" value="<?php echo e(now()->format('Y-m-d')); ?>"></label>
          <label>Numero autorizacion<input name="authorization_number"></label>
          <label>Monto autorizado<input type="number" step="0.01" min="0" name="authorized_amount" value="0"></label>
          <label>Vigencia<input type="date" name="valid_until"></label>
          <label class="span-2">Justificacion<textarea name="justification"></textarea></label>
          <button type="submit">Solicitar autorizacion</button>
        </form>
      <?php endif; ?>
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Documentos</h2>
        <a class="text-link" href="<?php echo e(route('insurance.documents.index')); ?>">Ver documentos</a>
      </div>
      <div class="compact-list">
        <?php $__empty_1 = true; $__currentLoopData = $patient->documents->sortByDesc('loaded_at')->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div>
            <strong><?php echo e($document->name); ?></strong>
            <span><?php echo e($document->document_type); ?> / <?php echo e($document->status); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="empty-state">Sin documentos.</p>
        <?php endif; ?>
      </div>

      <?php if($abilities['manage_documents']): ?>
        <form class="inline-form insurance-inline-form" method="post" action="<?php echo e(route('insurance.documents.store')); ?>" enctype="multipart/form-data">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="patient_id" value="<?php echo e($patient->id); ?>">
          <label>Nombre<input name="name" required></label>
          <label>Tipo
            <select name="document_type">
              <option value="official_id">Identificacion oficial</option>
              <option value="policy">Poliza</option>
              <option value="prescription">Receta medica</option>
              <option value="treatment_authorization">Autorizacion tratamiento</option>
              <option value="clinical_summary">Resumen clinico</option>
              <option value="laboratory">Laboratorio</option>
              <option value="imaging">Imagen</option>
              <option value="invoice_pdf">Factura PDF</option>
              <option value="fiscal_xml">XML fiscal</option>
              <option value="delivery_evidence">Evidencia entrega</option>
              <option value="other">Otros</option>
            </select>
          </label>
          <label>Estatus
            <select name="status">
              <option value="current">Vigente</option>
              <option value="expired">Vencido</option>
              <option value="replaced">Sustituido</option>
              <option value="rejected">Rechazado</option>
            </select>
          </label>
          <label>Vence<input type="date" name="expires_at"></label>
          <label class="span-2">Archivo<input type="file" name="document_file"></label>
          <button type="submit">Adjuntar documento</button>
        </form>
      <?php endif; ?>
    </article>
  </section>

  <section class="insurance-panel insurance-detail-panel insurance-timeline-panel">
    <div class="section-title">
      <h2>Linea de tiempo</h2>
      <span><?php echo e($timeline->count()); ?> eventos</span>
    </div>
    <div class="timeline">
      <?php $__empty_1 = true; $__currentLoopData = $timeline; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div>
          <time><?php echo e($event['date']?->format('d/m/Y')); ?></time>
          <strong><?php echo e($event['title']); ?></strong>
          <span><?php echo e($event['detail']); ?></span>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p class="empty-state">Sin eventos historicos.</p>
      <?php endif; ?>
    </div>
  </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Expediente '.$patient->full_name], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views/insurance/patients/show.blade.php ENDPATH**/ ?>