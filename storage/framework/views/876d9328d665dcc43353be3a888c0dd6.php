

<?php $__env->startSection('body_class', 'patient-assistant-native-body patient-portal-body'); ?>

<?php
  $statusLabels = [
    'active' => 'Activo', 'inactive' => 'Inactivo', 'scheduled' => 'Programada',
    'created' => 'Creado', 'pending' => 'Pendiente', 'delivered' => 'Entregado',
    'in_route' => 'En ruta', 'requested' => 'Solicitado', 'expired' => 'Vencida',
  ];
  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $initials = collect(explode(' ', $patient->full_name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
  $supportViews = [
    'insurance' => ['Mi Seguro', 'shield'],
    'analyses' => ['Análisis Clínicos', 'flask'],
    'history' => ['Historial clínico', 'book'],
    'prescriptions' => ['Recetas', 'file'],
    'calendar' => ['Calendario', 'calendar'],
    'doctors' => ['Médicos y terapeutas', 'people'],
  ];
?>

<?php $__env->startSection('content'); ?>
<div class="patient-assistant-native-screen patient-portal" data-patient-portal>
  <header class="patient-assistant-native-topbar patient-portal-topbar">
    <button class="patient-portal-dots" type="button" aria-label="Abrir soporte médico" aria-expanded="false" aria-controls="patient-support-menu" data-support-toggle>⋮</button>
    <form class="patient-portal-question" onsubmit="return false">
      <input aria-label="Pregunta para Dr. Sam" placeholder="Soy Dr. Sam, hazme una pregunta">
      <button type="submit" aria-label="Preguntar">↑</button>
    </form>
    <button class="patient-portal-profile-trigger" type="button" data-open-view="profile" aria-label="Abrir mi perfil">
      <span><?php echo e($initials ?: 'PX'); ?></span>
    </button>
  </header>

  <div class="patient-portal-layout">
    <aside id="patient-support-menu" class="patient-assistant-native-menu patient-portal-menu" aria-label="Menú del paciente" aria-hidden="true" data-support-menu>
      <strong class="patient-portal-menu-title">Soporte médico</strong>
      <?php $__currentLoopData = $supportViews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button type="button" data-open-view="<?php echo e($key); ?>">
          <span class="patient-support-icon patient-support-icon-<?php echo e($icon); ?>" aria-hidden="true"></span><?php echo e($label); ?>

        </button>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </aside>

    <main class="patient-portal-content">
      <?php if(session('patient_notice')): ?>
        <div class="patient-portal-notice"><?php echo e(session('patient_notice')); ?></div>
      <?php endif; ?>
      <?php if($errors->any()): ?>
        <div class="patient-portal-notice is-error"><?php echo e($errors->first()); ?></div>
      <?php endif; ?>

      <section class="patient-portal-view is-active" data-patient-view="home">
        <div class="patient-assistant-native-intro patient-portal-intro">
          <span class="patient-assistant-native-spark">+</span>
          <h1>¿Cómo amaneciste hoy?</h1>
        </div>
      </section>

      <section class="patient-portal-view" data-patient-view="profile">
        <div class="patient-portal-view-heading"><div><small>PERFIL DEL PACIENTE</small><h1>Mi perfil</h1><p>Información personal y datos de contacto.</p></div></div>
        <div class="patient-portal-two-columns">
          <form class="patient-portal-card patient-portal-form" method="post" action="<?php echo e(route('patient.profile.update')); ?>">
            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
            <h2>Datos personales</h2>
            <div class="patient-portal-form-grid">
              <label>Nombre<input name="first_name" value="<?php echo e(old('first_name', $patient->first_name)); ?>"></label>
              <label>Apellidos<input name="last_name" value="<?php echo e(old('last_name', $patient->last_name)); ?>"></label>
              <label>Fecha de nacimiento<input type="date" name="birth_date" value="<?php echo e(old('birth_date', $patient->birth_date?->format('Y-m-d'))); ?>"></label>
              <label>Sexo<select name="sex"><option value="unspecified">Sin especificar</option><option value="female" <?php if($patient->sex === 'female'): echo 'selected'; endif; ?>>Femenino</option><option value="male" <?php if($patient->sex === 'male'): echo 'selected'; endif; ?>>Masculino</option><option value="other" <?php if($patient->sex === 'other'): echo 'selected'; endif; ?>>Otro</option></select></label>
              <label>CURP<input name="curp" maxlength="18" value="<?php echo e(old('curp', $patient->curp)); ?>"></label>
              <label>No. usuario plataforma<input value="<?php echo e($patient->platform_number); ?>" disabled></label>
              <label>Teléfono<input name="phone" value="<?php echo e(old('phone', $patient->phone)); ?>"></label>
              <label>Correo<input type="email" name="email" value="<?php echo e(old('email', $patient->email)); ?>"></label>
            </div>
            <button class="patient-portal-primary" type="submit">Guardar cambios</button>
          </form>
          <article class="patient-portal-card patient-portal-preview">
            <div class="patient-portal-avatar"><?php echo e($initials ?: 'PX'); ?></div>
            <h2><?php echo e($patient->full_name); ?></h2><p><?php echo e($patient->platform_number ?? 'Sin número de plataforma'); ?></p>
            <dl><div><dt>Estatus</dt><dd><?php echo e($statusText($patient->status)); ?></dd></div><div><dt>Médico principal</dt><dd><?php echo e($patient->primaryDoctor?->full_name ?? 'Sin asignar'); ?></dd></div><div><dt>Perfil completado</dt><dd><?php echo e($patient->profile_completed_at?->format('d/m/Y') ?? 'Pendiente'); ?></dd></div></dl>
          </article>
        </div>
      </section>

      <section class="patient-portal-view" data-patient-view="insurance">
        <?php
          $policyMeta = $policy?->metadata ?? [];
          $advisorName = $policyMeta['advisor_name'] ?? 'Andrea Suarez';
          $insuranceComplete = $policy
            && $policy->policy_number
            && $policy->insurer_name
            && $policy->starts_at
            && $policy->ends_at;
          $insuranceValue = fn ($value, $fallback = 'No registrado') => filled($value) ? $value : $fallback;
        ?>
        <article class="patient-insurance-shell">
          <header class="patient-insurance-hero">
            <div class="patient-insurance-hero-icon" aria-hidden="true"><span>✓</span></div>
            <div><h1>Mi Seguro</h1><p>Póliza, programas de apego y medicamentos cubiertos</p></div>
          </header>
          <div class="patient-insurance-overview">
            <div class="patient-insurance-illustration" aria-hidden="true"><span class="patient-insurance-case">＋</span><span class="patient-insurance-shield">⌁</span></div>
            <section class="patient-insurance-status">
              <h2>Ten tu información siempre actualizada</h2>
              <p>Mantener tus datos de seguro al día nos permite brindarte una mejor experiencia y evitar contratiempos en tus servicios de salud.</p>
              <strong>Estado de tu seguro <span title="Información del estado">ⓘ</span></strong>
              <div class="patient-insurance-alert <?php echo e($insuranceComplete ? 'is-complete' : ''); ?>">
                <span><?php echo e($insuranceComplete ? '✓' : '◷'); ?></span>
                <div><b><?php echo e($insuranceComplete ? 'Información completa' : 'Información pendiente'); ?></b><small><?php echo e($insuranceComplete ? 'Tu póliza se encuentra registrada.' : 'Hay datos por completar para tu seguro.'); ?></small></div>
              </div>
              <button class="patient-insurance-complete-link" type="button" data-insurance-form-open>Completa tu información →</button>
              <div class="patient-insurance-actions">
                <button class="patient-insurance-upload" type="button" data-insurance-form-open><span>●</span>Subir póliza</button>
                <button type="button" data-insurance-form-open>⌕&nbsp; Actualizar datos</button>
              </div>
            </section>
            <aside class="patient-insurance-advisor">
              <div class="patient-insurance-advisor-heading"><span><?php echo e(collect(explode(' ', $advisorName))->filter()->take(2)->map(fn($part) => mb_substr($part, 0, 1))->implode('') ?: 'AS'); ?></span><div><small>ASESOR DE SEGUROS VINCULADO</small><strong><?php echo e($advisorName); ?></strong></div></div>
              <p>Cualquier solicitud de soporte se canalizará con tu asesor vinculado para dar seguimiento a coberturas, autorizaciones y reembolsos.</p>
              <div><small>ASEGURADORA</small><strong><?php echo e($insuranceValue($policy?->insurer_name, 'Por asignar')); ?></strong></div>
              <?php if($policyMeta['advisor_email'] ?? null): ?>
                <a href="mailto:<?php echo e($policyMeta['advisor_email']); ?>">Contactar asesor</a>
              <?php else: ?>
                <button type="button" disabled title="Asesor sin medio de contacto registrado">Contactar asesor</button>
              <?php endif; ?>
            </aside>
          </div>
          <nav class="patient-insurance-tabs" aria-label="Secciones de Mi Seguro">
            <button type="button" aria-label="Anterior" data-insurance-tab-step="-1">‹</button>
            <button class="is-active" type="button" data-insurance-tab="summary">▣&nbsp; Resumen</button>
            <button type="button" data-insurance-tab="coverage">♢&nbsp; Cobertura</button>
            <button type="button" data-insurance-tab="programs">⌘&nbsp; Programas</button>
            <button type="button" data-insurance-tab="network">♧&nbsp; Red de Médicos</button>
            <button type="button" data-insurance-tab="medications">◇&nbsp; Medicamentos</button>
            <button type="button" data-insurance-tab="refunds">▣&nbsp; Reembolsos</button>
            <button type="button" aria-label="Siguiente" data-insurance-tab-step="1">›</button>
          </nav>
          <div class="patient-insurance-content">
            <section class="patient-insurance-pane is-active" data-insurance-pane="summary">
              <header><span>▣</span><div><h2>Resumen de información</h2><p>Detalle del estado de tu información de seguro</p></div></header>
              <dl class="patient-insurance-summary">
                <div><dt>♢&nbsp; Aseguradora</dt><dd class="<?php echo e($policy?->insurer_name ? '' : 'is-missing'); ?>"><?php echo e($insuranceValue($policy?->insurer_name, 'Aseguradora no registrada')); ?></dd></div>
                <div><dt>▣&nbsp; Vigencia</dt><dd class="<?php echo e($policy?->starts_at && $policy?->ends_at ? '' : 'is-missing'); ?>"><?php echo e($policy?->starts_at && $policy?->ends_at ? $policy->starts_at->format('d/m/Y').' - '.$policy->ends_at->format('d/m/Y') : 'No registrada'); ?></dd></div>
                <div><dt>▯&nbsp; Póliza</dt><dd class="<?php echo e($policy?->policy_number ? '' : 'is-missing'); ?>"><?php echo e($insuranceValue($policy?->policy_number)); ?></dd></div>
                <div><dt>▣&nbsp; Deducible</dt><dd class="<?php echo e(filled($policyMeta['deductible'] ?? null) ? '' : 'is-missing'); ?>"><?php echo e($insuranceValue($policyMeta['deductible'] ?? null)); ?></dd></div>
                <div><dt>▣&nbsp; Plan</dt><dd class="<?php echo e($policy?->plan_name ? '' : 'is-pending'); ?>"><?php echo e($insuranceValue($policy?->plan_name, 'Plan pendiente de captura')); ?></dd></div>
                <div><dt>♢&nbsp; Coaseguro</dt><dd class="<?php echo e(filled($policyMeta['coinsurance'] ?? null) ? '' : 'is-missing'); ?>"><?php echo e($insuranceValue($policyMeta['coinsurance'] ?? null)); ?></dd></div>
              </dl>
            </section>
            <?php $__currentLoopData = ['coverage' => ['Cobertura', 'Los beneficios y límites de cobertura aparecerán cuando sean registrados.'], 'programs' => ['Programas', 'No hay programas de apego vinculados a esta póliza.'], 'network' => ['Red de Médicos', 'No hay una red médica registrada para esta póliza.'], 'medications' => ['Medicamentos', 'No hay medicamentos cubiertos registrados.'], 'refunds' => ['Reembolsos', 'No hay solicitudes de reembolso registradas.']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tab => [$title, $description]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <section class="patient-insurance-pane" data-insurance-pane="<?php echo e($tab); ?>"><header><span>▣</span><div><h2><?php echo e($title); ?></h2><p><?php echo e($description); ?></p></div></header><div class="patient-insurance-empty">No registrado</div></section>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
          <form class="patient-insurance-edit-form patient-portal-form" method="post" action="<?php echo e(route('patient.insurance.save')); ?>" data-insurance-form hidden>
            <?php echo csrf_field(); ?>
            <div class="patient-insurance-form-heading"><div><h2><?php echo e($policy ? 'Actualizar póliza' : 'Registrar póliza'); ?></h2><p>Completa los datos disponibles de tu seguro médico.</p></div><button type="button" data-insurance-form-close>×</button></div>
            <div class="patient-portal-form-grid">
              <label>Número de póliza<input required name="policy_number" value="<?php echo e(old('policy_number', $policy?->policy_number)); ?>"></label>
              <label>Aseguradora<input required name="insurer_name" value="<?php echo e(old('insurer_name', $policy?->insurer_name)); ?>"></label>
              <label>Plan<input name="plan_name" value="<?php echo e(old('plan_name', $policy?->plan_name)); ?>"></label>
              <label>Empresa<input name="employer_name" value="<?php echo e(old('employer_name', $policy?->employer_name)); ?>"></label>
              <label>Inicio<input type="date" name="starts_at" value="<?php echo e(old('starts_at', $policy?->starts_at?->format('Y-m-d'))); ?>"></label>
              <label>Fin<input type="date" name="ends_at" value="<?php echo e(old('ends_at', $policy?->ends_at?->format('Y-m-d'))); ?>"></label>
              <label>Estatus<select name="status"><option value="active">Activa</option><option value="pending" <?php if($policy?->status === 'pending'): echo 'selected'; endif; ?>>Pendiente</option><option value="inactive" <?php if($policy?->status === 'inactive'): echo 'selected'; endif; ?>>Inactiva</option><option value="expired" <?php if($policy?->status === 'expired'): echo 'selected'; endif; ?>>Vencida</option></select></label>
            </div>
            <button class="patient-portal-primary" type="submit">Guardar póliza</button>
          </form>
        </article>
      </section>

      <section class="patient-portal-view" data-patient-view="analyses">
        <div class="patient-portal-view-heading"><div><small>EXPEDIENTE DIGITAL</small><h1>Análisis clínicos</h1><p>Resultados y documentos médicos cargados a tu cuenta.</p></div><span><?php echo e($clinicalAnalyses->count()); ?> estudios</span></div>
        <div class="patient-portal-table-card"><table><thead><tr><th>Estudio</th><th>Fecha</th><th>Tipo</th><th>Estatus</th><th>Archivo</th></tr></thead><tbody>
          <?php $__empty_1 = true; $__currentLoopData = $clinicalAnalyses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><strong><?php echo e($document->name); ?></strong></td><td><?php echo e($document->loaded_at?->format('d/m/Y') ?? $document->created_at?->format('d/m/Y')); ?></td><td><?php echo e(ucfirst(str_replace('_', ' ', $document->document_type))); ?></td><td><span class="patient-portal-status"><?php echo e($statusText($document->status)); ?></span></td><td><?php if($document->file_path): ?><a href="<?php echo e(asset('storage/'.$document->file_path)); ?>" target="_blank">Ver documento</a><?php else: ?> Sin archivo <?php endif; ?></td></tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?> <tr><td colspan="5" class="patient-portal-empty">Aún no hay análisis clínicos registrados.</td></tr><?php endif; ?>
        </tbody></table></div>
      </section>

      <section class="patient-portal-view" data-patient-view="history">
        <?php
          $clinicalCategory = function ($type) {
            $type = strtolower((string) $type);
            return str_contains($type, 'hospital') ? 'hospitalization'
              : (str_contains($type, 'study') || str_contains($type, 'estudio') ? 'study'
              : (str_contains($type, 'lab') || str_contains($type, 'analysis') ? 'laboratory' : 'consultation'));
          };
          $historyItems = $patient->clinicalRecords->map(fn ($record) => [
            'date' => $record->recorded_at ?? $record->created_at,
            'category' => $clinicalCategory($record->record_type),
            'origin' => $record->doctor?->full_name ?? ($record->payload['origin'] ?? 'No registrado'),
            'service' => $record->doctor?->specialty ?? $record->title ?? ucfirst(str_replace('_', ' ', $record->record_type)),
            'summary' => $record->summary ?? 'Sin resumen clínico capturado.',
            'indications' => [],
          ])->concat($patient->documents->map(fn ($document) => [
            'date' => $document->loaded_at ?? $document->created_at,
            'category' => in_array($document->document_type, ['clinical_analysis', 'laboratory', 'analysis'], true) ? 'laboratory' : 'study',
            'origin' => $document->metadata['origin'] ?? 'No registrado',
            'service' => $document->name ?? ucfirst(str_replace('_', ' ', $document->document_type)),
            'summary' => $document->metadata['summary'] ?? 'Documento clínico disponible en el expediente.',
            'indications' => [],
          ]))->concat($patient->hospitalizations->map(fn ($hospitalization) => [
            'date' => $hospitalization->admitted_at ?? $hospitalization->created_at,
            'category' => 'hospitalization',
            'origin' => $hospitalization->hospital?->name ?? $hospitalization->hospital_name ?? 'No registrado',
            'service' => $hospitalization->area ?? $hospitalization->event_type ?? 'Hospitalización',
            'summary' => $hospitalization->reason ?? $hospitalization->admission_diagnosis ?? 'Sin resumen clínico capturado.',
            'indications' => [],
          ]))->concat($patient->prescriptions->map(fn ($prescription) => [
            'date' => $prescription->issued_at ?? $prescription->created_at,
            'category' => 'prescription',
            'origin' => $prescription->doctor?->full_name ?? 'No registrado',
            'service' => $prescription->doctor?->specialty ?? 'Receta médica',
            'summary' => $prescription->notes ?? 'Indicaciones médicas emitidas.',
            'indications' => $prescription->items->map(fn ($item) => [
              'title' => trim(collect([$item->dose, $item->duration])->filter()->implode(' · ')) ?: $item->medication_name,
              'detail' => trim(collect([$item->medication_name, $item->frequency, $item->instructions])->filter()->implode('. ')),
            ])->all(),
          ]))->sortByDesc('date')->values();
          $historyCategoryLabels = [
            'consultation' => 'Consulta Médica',
            'laboratory' => 'Análisis de Laboratorio',
            'study' => 'Estudios',
            'prescription' => 'Recetas',
            'hospitalization' => 'Servicio Hospitalario',
          ];
        ?>
        <article class="patient-history-panel">
          <header class="patient-history-hero">
            <div class="patient-history-hero-icon" aria-hidden="true"><i></i><i></i><i></i></div>
            <div><h1>Historial clínico</h1><p>Consultas, diagnósticos y estudios recientes</p></div>
          </header>
          <div class="patient-history-body">
            <nav class="patient-history-filters" aria-label="Filtros del historial clínico">
              <button type="button" data-history-step="-1" aria-label="Filtro anterior">‹</button>
              <button class="is-active" type="button" data-history-filter="all"><span>◇</span>Todos</button>
              <button class="is-consultation" type="button" data-history-filter="consultation"><span>♧</span>Consulta médica</button>
              <button class="is-laboratory" type="button" data-history-filter="laboratory"><span>♙</span>Análisis de laboratorio</button>
              <button class="is-study" type="button" data-history-filter="study"><span>▧</span>Estudios</button>
              <button class="is-prescription" type="button" data-history-filter="prescription"><span>▤</span>Recetas</button>
              <button class="is-hospitalization" type="button" data-history-filter="hospitalization"><span>⚑</span>Hospitalización</button>
              <button type="button" data-history-step="1" aria-label="Filtro siguiente">›</button>
            </nav>
            <div class="patient-history-table-wrap">
              <table class="patient-history-table">
                <thead><tr><th>Fecha</th><th>Categoría</th><th>Origen</th><th>Especialidad o tipo de servicio</th><th>Resumen clínico</th><th>Receta / Indicaciones</th></tr></thead>
                <tbody>
                  <?php $__empty_1 = true; $__currentLoopData = $historyItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr data-history-row="<?php echo e($item['category']); ?>">
                      <td><div class="patient-history-date"><span>□</span><strong><?php echo e($item['date']?->format('d/m/Y') ?? 'No registrada'); ?><small><?php echo e($item['date']?->translatedFormat('l') ?? ''); ?></small></strong></div></td>
                      <td><span class="patient-history-category is-<?php echo e($item['category']); ?>"><?php echo e($historyCategoryLabels[$item['category']] ?? 'Registro clínico'); ?></span></td>
                      <td><div class="patient-history-origin"><span>♙</span><strong><?php echo e($item['origin']); ?></strong></div></td>
                      <td><div class="patient-history-service"><span>⌁</span><strong><?php echo e($item['service']); ?></strong></div></td>
                      <td><?php echo e($item['summary']); ?></td>
                      <td>
                        <?php if(count($item['indications'])): ?>
                          <div class="patient-history-indications">
                            <span>▤</span>
                            <?php $__currentLoopData = $item['indications']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $indication): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                              <div><strong><?php echo e($indication['title']); ?></strong><p><?php echo e($indication['detail']); ?></p></div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                          </div>
                        <?php else: ?>
                          <span class="patient-history-not-registered">No registrado</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="patient-portal-empty">No hay registros clínicos.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </article>
      </section>

      <section class="patient-portal-view patient-prescriptions-view" data-patient-view="prescriptions">
        <article class="patient-prescriptions-panel">
          <header class="patient-prescriptions-hero">
            <div class="patient-prescriptions-hero-icon" aria-hidden="true"><span></span></div>
            <div><h1>Recetas</h1><p>Indicaciones activas y recetas emitidas</p></div>
          </header>
          <div class="patient-prescriptions-redesign">
            <div class="patient-prescription-filter-bar" aria-label="Filtros de recetas">
              <button class="patient-prescription-filter is-active" type="button" data-prescription-filter="all"><span>◇</span>Todos</button>
              <button class="patient-prescription-filter patient-prescription-filter-active" type="button" data-prescription-filter="active"><span>▣</span>Vigentes</button>
              <button class="patient-prescription-filter patient-prescription-filter-expired" type="button" data-prescription-filter="expired"><span>□</span>Vencidas</button>
              <button class="patient-prescription-filter patient-prescription-filter-medications" type="button" data-prescription-filter="medications"><span>▤</span>Medicamentos</button>
              <button class="patient-prescription-filter patient-prescription-filter-analysis" type="button" data-prescription-filter="analysis"><span>♙</span>Análisis clínicos</button>
            </div>
            <div class="patient-prescriptions-table-card">
              <table class="patient-prescriptions-table">
                <thead><tr><th>Médico tratante</th><th>Especialidad</th><th>Fecha</th><th>Medicamento, dosis, presentación</th><th>Análisis clínico</th><th>Ver receta</th><th>Hospital donde se recetó</th><th>Institución donde se recetó</th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $patient->prescriptions->sortByDesc('issued_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prescription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $prescriptionMeta = $prescription->metadata ?? [];
                    $isExpiredPrescription = in_array($prescription->status, ['expired', 'inactive'], true)
                      || ($prescriptionMeta['expires_at'] ?? null) && \Illuminate\Support\Carbon::parse($prescriptionMeta['expires_at'])->isPast();
                    $hasAnalysis = !empty($prescriptionMeta['clinical_analysis']) || !empty($prescriptionMeta['clinical_analyses']);
                  ?>
                  <tr data-prescription-row data-status="<?php echo e($isExpiredPrescription ? 'expired' : 'active'); ?>" data-has-medications="<?php echo e($prescription->items->isNotEmpty() ? '1' : '0'); ?>" data-has-analysis="<?php echo e($hasAnalysis ? '1' : '0'); ?>">
                    <td><?php echo e($prescription->doctor?->full_name ?? 'Sin médico asignado'); ?></td>
                    <td><?php echo e($prescription->doctor?->specialty ?? 'Medicina general'); ?></td>
                    <td><?php echo e($prescription->issued_at?->format('d/m/Y') ?? 'Sin fecha'); ?></td>
                    <td class="patient-prescription-medications"><?php $__empty_2 = true; $__currentLoopData = $prescription->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?><div><?php echo e($item->medication_name); ?> <?php if($item->dose): ?>| <?php echo e($item->dose); ?> <?php endif; ?> <?php if($item->metadata['presentation'] ?? null): ?>| <?php echo e($item->metadata['presentation']); ?> <?php endif; ?> <?php if($item->frequency): ?>| <?php echo e($item->frequency); ?> <?php endif; ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?> Sin medicamentos indicados <?php endif; ?></td>
                    <td><?php echo e($prescriptionMeta['clinical_analysis'] ?? ($hasAnalysis ? 'Análisis relacionados' : '—')); ?></td>
                    <td><button class="patient-prescription-view-button" type="button" title="<?php echo e($prescription->code ?? 'Receta médica'); ?>" data-prescription-open="patient-prescription-<?php echo e($prescription->id); ?>">Ver</button></td>
                    <td><?php echo e($prescriptionMeta['hospital'] ?? $prescriptionMeta['medical_unit'] ?? 'Privada'); ?></td>
                    <td><?php echo e($prescriptionMeta['institution'] ?? 'Privada'); ?></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?> <tr><td colspan="8" class="patient-portal-empty">No hay recetas registradas.</td></tr><?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </article>
        <?php $__currentLoopData = $patient->prescriptions->sortByDesc('issued_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prescription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $prescriptionMeta = $prescription->metadata ?? [];
            $hospitalName = $prescriptionMeta['hospital'] ?? $prescriptionMeta['medical_unit'] ?? 'Privada';
            $institutionName = $prescriptionMeta['institution'] ?? 'Privada';
            $pharmacyName = $prescriptionMeta['external_pharmacy'] ?? 'Farmacia externa';
          ?>
          <dialog class="patient-prescription-dialog" id="patient-prescription-<?php echo e($prescription->id); ?>" aria-labelledby="patient-prescription-title-<?php echo e($prescription->id); ?>">
            <header class="patient-prescription-dialog-header">
              <div><small>FORMATO PDF</small><h2 id="patient-prescription-title-<?php echo e($prescription->id); ?>">Receta canjeable</h2></div>
              <button type="button" data-prescription-close aria-label="Cerrar receta">×</button>
            </header>
            <div class="patient-prescription-paper">
              <div class="patient-prescription-paper-title">
                <div><h3>Formato de Receta Médica</h3><p><?php echo e($pharmacyName); ?> - <?php echo e($hospitalName === 'Privada' ? 'Consulta Externa Privada' : $hospitalName); ?></p></div>
                <div><small>Folio</small><strong><?php echo e($prescription->code ?? 'RX-'.$prescription->id); ?></strong></div>
              </div>
              <dl class="patient-prescription-detail-grid">
                <div><dt>Paciente</dt><dd><?php echo e($patient->full_name); ?></dd></div>
                <div><dt>ID de paciente</dt><dd><?php echo e($patient->platform_number ?? $patient->id); ?></dd></div>
                <div><dt>Médico tratante</dt><dd><?php echo e($prescription->doctor?->full_name ?? 'Sin médico asignado'); ?></dd></div>
                <div><dt>Especialidad</dt><dd><?php echo e($prescription->doctor?->specialty ?? 'Medicina general'); ?></dd></div>
                <div><dt>Fecha</dt><dd><?php echo e($prescription->issued_at?->format('d/m/Y') ?? 'Sin fecha'); ?></dd></div>
                <div><dt>Hospital</dt><dd><?php echo e($hospitalName); ?></dd></div>
                <div><dt>Institución</dt><dd><?php echo e($institutionName); ?></dd></div>
                <div><dt>Farmacia externa</dt><dd><?php echo e($pharmacyName); ?></dd></div>
                <div><dt>Formato</dt><dd>Formato de Receta Médica</dd></div>
                <div><dt>Canje</dt><dd><?php echo e($prescriptionMeta['redemption'] ?? 'Canje sujeto a validación de Farmacia externa.'); ?></dd></div>
              </dl>
              <div class="patient-prescription-lines">
                <?php $__empty_1 = true; $__currentLoopData = $prescription->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <div><small><?php echo e($item->metadata['quantity'] ?? 1); ?> <?php echo e(($item->metadata['quantity'] ?? 1) == 1 ? 'pieza' : 'piezas'); ?></small><strong><?php echo e($item->medication_name); ?><?php if($item->dose): ?> | <?php echo e($item->dose); ?><?php endif; ?> <?php if($item->metadata['presentation'] ?? null): ?>| <?php echo e($item->metadata['presentation']); ?><?php endif; ?></strong><?php if($item->frequency || $item->duration || $item->instructions): ?><p><?php echo e(collect([$item->frequency, $item->duration, $item->instructions])->filter()->implode(' · ')); ?></p><?php endif; ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <div><strong>Sin medicamentos indicados</strong></div>
                <?php endif; ?>
              </div>
              <footer class="patient-prescription-paper-footer">
                <div class="patient-prescription-signature"><span></span><strong><?php echo e($prescription->doctor?->full_name ?? 'Médico tratante'); ?></strong><small>Cédula profesional <?php echo e($prescription->doctor?->professional_license ?? 'sin registrar'); ?></small></div>
                <div class="patient-prescription-qr" aria-label="Código para canje"><span></span><small>QR para canje</small></div>
              </footer>
            </div>
          </dialog>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </section>

      <section class="patient-portal-view" data-patient-view="calendar">
        <?php
          $calendarAppointments = $patient->appointments->sortBy('starts_at')->values();
          $calendarMonths = $calendarAppointments->filter(fn ($item) => $item->starts_at)->map(fn ($item) => ['key' => $item->starts_at->format('Y-m'), 'label' => ucfirst($item->starts_at->translatedFormat('F Y'))])->unique('key')->values();
          $consultationHistory = $patient->clinicalRecords->sortByDesc('recorded_at')->values();
        ?>
        <div class="patient-calendar-shell">
          <header class="patient-calendar-hero">
            <span class="patient-calendar-hero-icon" aria-hidden="true"></span>
            <div><h1>Calendario</h1><p>Citas y estudios programados</p></div>
          </header>
          <div class="patient-calendar-toolbar">
            <div class="patient-calendar-filters" aria-label="Filtrar calendario">
              <button class="is-active" type="button" data-calendar-filter="all"><span>✓</span>Todas</button>
              <button type="button" data-calendar-filter="upcoming"><span>▣</span>Próximas</button>
              <button type="button" data-calendar-filter="laboratory"><span>♙</span>Laboratorios</button>
              <button type="button" data-calendar-filter="consultation"><span>♧</span>Consultas</button>
            </div>
            <div class="patient-calendar-month-nav">
              <button type="button" aria-label="Mes anterior" data-calendar-month-step="-1">‹</button>
              <span><b>☺</b> <?php echo e($calendarAppointments->filter(fn ($item) => $item->starts_at?->isFuture())->count()); ?> próximas citas</span>
              <strong data-calendar-month-label><?php echo e($calendarMonths->first()['label'] ?? ucfirst(now()->translatedFormat('F Y'))); ?></strong>
              <button type="button" aria-label="Mes siguiente" data-calendar-month-step="1">›</button>
            </div>
          </div>
          <div class="patient-calendar-list">
            <?php $__empty_1 = true; $__currentLoopData = $calendarAppointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <?php
                $calendarText = mb_strtolower(collect([$appointment->specialty, $appointment->modality, $appointment->reason])->filter()->implode(' '));
                $calendarType = str_contains($calendarText, 'laborat') || str_contains($calendarText, 'análisis') || str_contains($calendarText, 'muestra') ? 'laboratory' : 'consultation';
                $isUpcoming = $appointment->starts_at?->isFuture() && !in_array($appointment->status, ['cancelled', 'completed'], true);
                $appointmentUnit = $appointment->medicalUnit?->name ?? $appointment->location ?? 'Ubicación no registrada';
                $appointmentTitle = $calendarType === 'laboratory' ? 'Laboratorio' : ($appointment->specialty ?: 'Consulta médica');
              ?>
              <article class="patient-calendar-card" data-calendar-item data-calendar-type="<?php echo e($calendarType); ?>" data-calendar-upcoming="<?php echo e($isUpcoming ? '1' : '0'); ?>" data-calendar-month="<?php echo e($appointment->starts_at?->format('Y-m')); ?>">
                <div class="patient-calendar-type-icon is-<?php echo e($calendarType); ?>" aria-hidden="true"><span><?php echo e($calendarType === 'laboratory' ? '♙' : '♥'); ?></span></div>
                <div class="patient-calendar-card-main">
                  <h2><?php echo e($appointmentTitle); ?></h2>
                  <div class="patient-calendar-date"><span>▣</span><strong><?php echo e($appointment->starts_at?->format('d/m/Y') ?? 'Fecha pendiente'); ?></strong><i>•</i><span>◷</span><strong><?php echo e($appointment->starts_at?->format('H:i') ?? 'Sin hora'); ?></strong></div>
                  <p>Con <?php echo e($appointment->doctor?->full_name ?? 'médico por asignar'); ?>, en <?php echo e($appointmentUnit); ?>. Motivo: <?php echo e($appointment->reason ?: 'No registrado'); ?></p>
                  <div class="patient-calendar-card-meta">
                    <div><small>MÉDICO</small><strong><?php echo e($appointment->doctor?->full_name ?? 'No registrado'); ?></strong></div>
                    <div><small>ESPECIALIDAD</small><strong><?php echo e($appointment->specialty ?: 'No registrada'); ?></strong></div>
                    <div><small>MODALIDAD</small><strong><?php echo e($appointment->modality ?: ($calendarType === 'laboratory' ? 'Toma de muestra' : 'Consulta presencial')); ?></strong></div>
                    <div><small>UBICACIÓN</small><strong><?php echo e($appointmentUnit); ?></strong></div>
                  </div>
                </div>
                <aside class="patient-calendar-card-actions">
                  <span class="patient-calendar-status"><?php echo e($statusText($appointment->status)); ?></span>
                  <button type="button" data-calendar-detail-open="patient-calendar-detail-<?php echo e($appointment->id); ?>">Ver detalle <b>›</b></button>
                  <button type="button" disabled title="La cancelación todavía no está habilitada">Cancelar cita</button>
                </aside>
              </article>
              <dialog class="patient-calendar-dialog" id="patient-calendar-detail-<?php echo e($appointment->id); ?>">
                <header><div><small>DETALLE DE CITA</small><h2><?php echo e($appointmentTitle); ?></h2></div><button type="button" data-calendar-detail-close aria-label="Cerrar">×</button></header>
                <dl>
                  <div><dt>Fecha y hora</dt><dd><?php echo e($appointment->starts_at?->format('d/m/Y H:i') ?? 'No registrada'); ?></dd></div>
                  <div><dt>Estatus</dt><dd><?php echo e($statusText($appointment->status)); ?></dd></div>
                  <div><dt>Médico</dt><dd><?php echo e($appointment->doctor?->full_name ?? 'No registrado'); ?></dd></div>
                  <div><dt>Especialidad</dt><dd><?php echo e($appointment->specialty ?: 'No registrada'); ?></dd></div>
                  <div><dt>Modalidad</dt><dd><?php echo e($appointment->modality ?: 'No registrada'); ?></dd></div>
                  <div><dt>Ubicación</dt><dd><?php echo e($appointmentUnit); ?></dd></div>
                  <div class="is-wide"><dt>Motivo</dt><dd><?php echo e($appointment->reason ?: 'No registrado'); ?></dd></div>
                </dl>
              </dialog>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <div class="patient-calendar-empty" data-calendar-empty>No hay citas o estudios programados.</div>
            <?php endif; ?>
            <div class="patient-calendar-empty" data-calendar-filter-empty hidden>No hay citas que coincidan con este filtro.</div>
          </div>
          <p class="patient-calendar-timezone">ⓘ Las citas y estudios se muestran en la zona horaria de tu ubicación actual.</p>
        </div>
        <section class="patient-calendar-history">
          <header><h2>Historial de consultas</h2><p>Consultas anteriores del paciente</p></header>
          <div class="patient-calendar-history-scroll">
            <table>
              <thead><tr><th>Fecha</th><th>Médico tratante</th><th>Especialidad</th><th>Tipo de consulta</th><th>Motivo</th><th>Diagnóstico</th><th>Medicamento, dosis y cantidad</th><th>Unidad</th><th>Estatus</th></tr></thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $consultationHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $recordPayload = $record->payload ?? [];
                    $recordMedication = $recordPayload['medications'] ?? $recordPayload['medication'] ?? null;
                    if (is_array($recordMedication)) {
                      $recordMedication = collect($recordMedication)->map(fn ($item) => is_array($item) ? collect($item)->filter()->implode(' · ') : $item)->filter()->implode('; ');
                    }
                  ?>
                  <tr>
                    <td><?php echo e($record->recorded_at?->format('d/m/Y') ?? 'No registrada'); ?></td><td><?php echo e($record->doctor?->full_name ?? 'No registrado'); ?></td>
                    <td><?php echo e($recordPayload['specialty'] ?? $recordPayload['service'] ?? 'No registrada'); ?></td><td><?php echo e($recordPayload['consultation_type'] ?? $record->title ?? ucfirst(str_replace('_', ' ', $record->record_type))); ?></td>
                    <td><?php echo e($recordPayload['reason'] ?? $record->summary ?? 'No registrado'); ?></td><td><?php echo e($recordPayload['diagnosis'] ?? 'No registrado'); ?></td>
                    <td><?php echo e($recordMedication ?: 'Sin receta generada'); ?></td><td><?php echo e($recordPayload['unit'] ?? $recordPayload['location'] ?? 'No registrada'); ?></td>
                    <td><span class="patient-calendar-history-status">Atendida</span></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="9" class="patient-portal-empty">No hay consultas anteriores registradas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section class="patient-portal-view" data-patient-view="doctors">
        <div class="patient-portal-view-heading"><div><small>RED MÉDICA</small><h1>Médicos y terapeutas</h1><p>Consulta los profesionales disponibles en la plataforma.</p></div><span><?php echo e($doctors->count()); ?> profesionales</span></div>
        <div class="patient-portal-filters"><input type="search" placeholder="Buscar médico, especialidad o unidad" data-card-search="doctor-grid"></div>
        <div class="patient-portal-doctor-grid" id="doctor-grid">
          <?php $__empty_1 = true; $__currentLoopData = $doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><article><div class="patient-portal-avatar"><?php echo e(collect(explode(' ', $doctor->full_name))->filter()->take(2)->map(fn($part) => mb_substr($part,0,1))->implode('')); ?></div><div><h2><?php echo e($doctor->full_name); ?></h2><strong><?php echo e($doctor->specialty ?? 'Medicina general'); ?></strong><p><?php echo e($doctor->subspecialty ?? $doctor->service_name ?? 'Atención médica'); ?></p><small><?php echo e($doctor->medicalUnit?->name ?? 'Consulta privada'); ?></small></div></article>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?> <div class="patient-portal-empty">No hay médicos activos.</div><?php endif; ?>
        </div>
      </section>

      <section class="patient-portal-view" data-patient-view="wellness">
        <div class="patient-portal-view-heading"><div><small>BIENESTAR</small><h1>Mi bienestar</h1><p>Seguimiento personal, metas y hábitos saludables.</p></div></div>
        <div class="patient-portal-summary-grid"><article><small>Actividad de hoy</small><strong>Sin registro</strong><span>Agrega tus métricas cuando estén disponibles</span></article><article><small>Meta actual</small><strong>Cuidar mi salud</strong><span>Seguimiento personal</span></article><article><small>Racha</small><strong>0 días</strong><span>Comienza hoy</span></article></div>
        <div class="patient-portal-card"><h2>Próximamente</h2><p>Este bloque queda preparado para métricas, metas, logros e historial de bienestar.</p></div>
      </section>

      <section class="patient-portal-view" data-patient-view="devices">
        <div class="patient-portal-view-heading"><div><small>SALUD CONECTADA</small><h1>Dispositivos médicos</h1><p>Equipos vinculados para seguimiento de métricas.</p></div></div>
        <div class="patient-portal-card patient-portal-empty">No hay dispositivos vinculados a este paciente.</div>
      </section>

      <nav class="patient-portal-bottom-nav" aria-label="Navegación principal del paciente">
        <button class="is-active" type="button" data-open-view="home"><span class="patient-bottom-icon">⌂</span>Home</button>
        <button type="button" data-open-view="devices"><span class="patient-bottom-icon">▯</span>Dispositivos</button>
        <button class="patient-bottom-register" type="button" data-open-view="wellness"><span>＋</span>Registro</button>
        <button type="button" data-open-view="wellness"><span class="patient-bottom-icon">☆</span>Mi salud</button>
        <button type="button" data-open-view="profile"><span class="patient-bottom-icon">○</span>Perfil</button>
      </nav>
    </main>
  </div>
</div>

<script>
(() => {
  const portal = document.querySelector('[data-patient-portal]');
  if (!portal) return;
  const openView = (name) => {
    portal.querySelectorAll('[data-patient-view]').forEach(el => el.classList.toggle('is-active', el.dataset.patientView === name));
    portal.querySelectorAll('[data-open-view]').forEach(el => el.classList.toggle('is-active', el.dataset.openView === name));
    sessionStorage.setItem('patientPortalView', name);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
  portal.addEventListener('click', event => {
    const supportToggle = event.target.closest('[data-support-toggle]');
    if (supportToggle) {
      const menu = portal.querySelector('[data-support-menu]');
      const willOpen = !portal.classList.contains('is-support-open');
      portal.classList.toggle('is-support-open', willOpen);
      supportToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      menu?.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
      return;
    }
    const trigger = event.target.closest('[data-open-view]');
    if (trigger) {
      openView(trigger.dataset.openView);
      portal.classList.remove('is-support-open');
      portal.querySelector('[data-support-toggle]')?.setAttribute('aria-expanded', 'false');
      portal.querySelector('[data-support-menu]')?.setAttribute('aria-hidden', 'true');
    }
  });
  document.addEventListener('click', event => {
    if (!portal.classList.contains('is-support-open') || event.target.closest('[data-support-toggle], [data-support-menu]')) return;
    portal.classList.remove('is-support-open');
    portal.querySelector('[data-support-toggle]')?.setAttribute('aria-expanded', 'false');
    portal.querySelector('[data-support-menu]')?.setAttribute('aria-hidden', 'true');
  });
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    portal.classList.remove('is-support-open');
    portal.querySelector('[data-support-toggle]')?.setAttribute('aria-expanded', 'false');
    portal.querySelector('[data-support-menu]')?.setAttribute('aria-hidden', 'true');
  });
  const initial = <?php echo json_encode($errors->any() ? (old('policy_number') ? 'insurance' : 'profile') : null, 15, 512) ?> || sessionStorage.getItem('patientPortalView') || 'home';
  openView(portal.querySelector(`[data-patient-view="${initial}"]`) ? initial : 'home');

  portal.querySelectorAll('[data-table-search]').forEach(input => input.addEventListener('input', () => {
    const table = document.getElementById(input.dataset.tableSearch);
    table?.querySelectorAll('tbody tr').forEach(row => row.hidden = !row.innerText.toLowerCase().includes(input.value.toLowerCase()));
  }));
  portal.querySelectorAll('[data-card-search]').forEach(input => input.addEventListener('input', () => {
    document.getElementById(input.dataset.cardSearch)?.querySelectorAll('article').forEach(card => card.hidden = !card.innerText.toLowerCase().includes(input.value.toLowerCase()));
  }));
  const historyFilters = [...portal.querySelectorAll('[data-history-filter]')];
  const selectHistoryFilter = button => {
    if (!button) return;
    historyFilters.forEach(item => item.classList.toggle('is-active', item === button));
    portal.querySelectorAll('[data-history-row]').forEach(row => {
      row.hidden = button.dataset.historyFilter !== 'all' && row.dataset.historyRow !== button.dataset.historyFilter;
    });
  };
  historyFilters.forEach(button => button.addEventListener('click', () => selectHistoryFilter(button)));
  portal.querySelectorAll('[data-history-step]').forEach(button => button.addEventListener('click', () => {
    const current = Math.max(0, historyFilters.findIndex(item => item.classList.contains('is-active')));
    const next = (current + Number(button.dataset.historyStep) + historyFilters.length) % historyFilters.length;
    selectHistoryFilter(historyFilters[next]);
    historyFilters[next]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
  }));
  portal.querySelectorAll('[data-prescription-filter]').forEach(button => button.addEventListener('click', () => {
    const filter = button.dataset.prescriptionFilter;
    portal.querySelectorAll('[data-prescription-filter]').forEach(item => item.classList.toggle('is-active', item === button));
    portal.querySelectorAll('[data-prescription-row]').forEach(row => {
      row.hidden = filter === 'active' ? row.dataset.status !== 'active'
        : filter === 'expired' ? row.dataset.status !== 'expired'
        : filter === 'medications' ? row.dataset.hasMedications !== '1'
        : filter === 'analysis' ? row.dataset.hasAnalysis !== '1'
        : false;
    });
  }));
  const insuranceForm = portal.querySelector('[data-insurance-form]');
  portal.querySelectorAll('[data-insurance-form-open]').forEach(button => button.addEventListener('click', () => {
    if (!insuranceForm) return;
    insuranceForm.hidden = false;
    insuranceForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }));
  portal.querySelector('[data-insurance-form-close]')?.addEventListener('click', () => {
    insuranceForm.hidden = true;
  });
  const insuranceTabs = [...portal.querySelectorAll('[data-insurance-tab]')];
  const selectInsuranceTab = button => {
    if (!button) return;
    insuranceTabs.forEach(item => item.classList.toggle('is-active', item === button));
    portal.querySelectorAll('[data-insurance-pane]').forEach(pane => {
      pane.classList.toggle('is-active', pane.dataset.insurancePane === button.dataset.insuranceTab);
    });
  };
  insuranceTabs.forEach(button => button.addEventListener('click', () => selectInsuranceTab(button)));
  portal.querySelectorAll('[data-insurance-tab-step]').forEach(button => button.addEventListener('click', () => {
    const current = Math.max(0, insuranceTabs.findIndex(item => item.classList.contains('is-active')));
    const next = (current + Number(button.dataset.insuranceTabStep) + insuranceTabs.length) % insuranceTabs.length;
    selectInsuranceTab(insuranceTabs[next]);
    insuranceTabs[next]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
  }));
  portal.querySelectorAll('[data-prescription-open]').forEach(button => button.addEventListener('click', () => {
    document.getElementById(button.dataset.prescriptionOpen)?.showModal();
  }));
  portal.querySelectorAll('.patient-prescription-dialog').forEach(dialog => {
    dialog.querySelector('[data-prescription-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
      const bounds = dialog.getBoundingClientRect();
      if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
  });
  const calendarItems = [...portal.querySelectorAll('[data-calendar-item]')];
  const calendarFilters = [...portal.querySelectorAll('[data-calendar-filter]')];
  const calendarMonths = <?php echo json_encode($calendarMonths ?? collect(), 15, 512) ?>;
  let calendarFilter = 'all';
  let calendarMonthIndex = 0;
  let calendarMonthEnabled = false;
  const renderCalendar = () => {
    let visible = 0;
    calendarItems.forEach(item => {
      const matchesType = calendarFilter === 'all'
        || (calendarFilter === 'upcoming' && item.dataset.calendarUpcoming === '1')
        || item.dataset.calendarType === calendarFilter;
      const matchesMonth = !calendarMonthEnabled || !calendarMonths.length || item.dataset.calendarMonth === calendarMonths[calendarMonthIndex]?.key;
      item.hidden = !(matchesType && matchesMonth);
      if (!item.hidden) visible++;
    });
    const label = portal.querySelector('[data-calendar-month-label]');
    if (label && calendarMonths.length) label.textContent = calendarMonths[calendarMonthIndex]?.label;
    const empty = portal.querySelector('[data-calendar-filter-empty]');
    if (empty) empty.hidden = visible > 0 || calendarItems.length === 0;
  };
  calendarFilters.forEach(button => button.addEventListener('click', () => {
    calendarFilter = button.dataset.calendarFilter;
    calendarFilters.forEach(item => item.classList.toggle('is-active', item === button));
    renderCalendar();
  }));
  portal.querySelectorAll('[data-calendar-month-step]').forEach(button => button.addEventListener('click', () => {
    if (!calendarMonths.length) return;
    calendarMonthEnabled = true;
    calendarMonthIndex = (calendarMonthIndex + Number(button.dataset.calendarMonthStep) + calendarMonths.length) % calendarMonths.length;
    renderCalendar();
  }));
  portal.querySelectorAll('[data-calendar-detail-open]').forEach(button => button.addEventListener('click', () => {
    document.getElementById(button.dataset.calendarDetailOpen)?.showModal();
  }));
  portal.querySelectorAll('.patient-calendar-dialog').forEach(dialog => {
    dialog.querySelector('[data-calendar-detail-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
      const bounds = dialog.getBoundingClientRect();
      if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
  });
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Portal del Paciente'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\patient\dashboard.blade.php ENDPATH**/ ?>