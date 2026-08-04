<?php $__env->startSection('body_class', 'patient-assistant-native-body patient-portal-body'); ?>

<?php $__env->startPush('styles'); ?>
  <link rel="stylesheet" href="<?php echo e(asset('css/patient-profile-panel.css')); ?>?v=<?php echo e(filemtime(public_path('css/patient-profile-panel.css'))); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/communities.css')); ?>?v=<?php echo e(filemtime(public_path('css/communities.css'))); ?>">
<?php $__env->stopPush(); ?>

<?php
  $statusLabels = [
    'active' => 'Activo', 'inactive' => 'Inactivo', 'scheduled' => 'Programada',
    'created' => 'Creado', 'pending' => 'Pendiente', 'delivered' => 'Entregado',
    'in_route' => 'En ruta', 'requested' => 'Solicitado', 'expired' => 'Vencida',
  ];
  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $initials = collect(explode(' ', $patient->full_name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
  $supportViews = [
    'history' => ['Historial clínico', 'book'],
    'insurance' => ['Mi Seguro', 'shield'],
    'analyses' => ['Análisis Clínicos', 'flask'],
    'prescriptions' => ['Recetas', 'file'],
    'calendar' => ['Calendario', 'calendar'],
    'doctors' => ['Médicos y terapeutas', 'people'],
  ];
?>

<?php $__env->startSection('content'); ?>
<div class="patient-assistant-native-screen patient-portal" data-patient-portal>
  <header class="patient-assistant-native-topbar patient-portal-topbar">
    <button class="patient-portal-dots" type="button" aria-label="Abrir interacciones" aria-expanded="false" aria-controls="patient-support-menu" data-support-toggle>⋮</button>
    <form class="patient-portal-question" onsubmit="return false">
      <input aria-label="Pregunta para Dr. Sam" placeholder="Soy Dr. Sam, hazme una pregunta">
      <button type="submit" aria-label="Preguntar">↑</button>
    </form>
    <button class="patient-portal-profile-trigger" type="button" data-profile-panel-open aria-label="Abrir perfil del paciente">
      <span><?php echo e($initials ?: 'PX'); ?></span>
    </button>
  </header>

  <div class="patient-portal-layout">
    <aside id="patient-support-menu" class="patient-assistant-native-menu patient-portal-menu" aria-label="Menú del paciente" aria-hidden="true" data-support-menu>
      <strong class="patient-portal-menu-title">Interacciones</strong>
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
              <label>Correo<input type="email" name="email" value="<?php echo e(old('email', $patient->email)); ?>" readonly aria-readonly="true"></label>
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

      <section class="patient-portal-view patient-portal-communities-view" data-patient-view="communities">
        <div id="patient-communities-root" data-communities-root></div>
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
        <article class="patient-health-panel">
          <header class="patient-health-hero">
            <div class="patient-health-hero-icon" aria-hidden="true">
              <svg viewBox="0 0 48 48"><path d="M24 39s-13-7.5-17-17.5C3.5 12.7 9 7 15.2 7c3.8 0 6.4 2 8.8 5.1C26.4 9 29 7 32.8 7 39 7 44.5 12.7 41 21.5 37 31.5 24 39 24 39Z"></path><path d="M15 23h5l3-6 5 12 3-6h4"></path></svg>
            </div>
            <div>
              <h1>Mi Salud</h1>
              <p>Tu bienestar, en equilibrio</p>
            </div>
          </header>

          <nav class="patient-health-filters" data-health-filter-carousel aria-label="Filtros de salud">
            <button class="is-active" type="button" data-health-filter="all" aria-pressed="true"><span>☰</span>Todos</button>
            <button class="is-activity" type="button" data-health-filter="activity" aria-pressed="false"><span>⌁</span>Actividad</button>
            <button class="is-nutrition" type="button" data-health-filter="nutrition" aria-pressed="false"><span>●</span>Nutrición</button>
            <button class="is-sleep" type="button" data-health-filter="sleep" aria-pressed="false"><span>◔</span>Sueño</button>
            <button class="is-vitals" type="button" data-health-filter="vitals" aria-pressed="false"><span>▱</span>Signos vitales</button>
            <button class="is-wellness" type="button" data-health-filter="wellness" aria-pressed="false"><span>☺</span>Bienestar</button>
            <button class="is-biomarkers" type="button" data-health-filter="biomarkers" aria-pressed="false"><span>♢</span>Biomarcadores</button>
          </nav>

          <section class="patient-health-metrics" aria-label="Resumen de salud">
            <article class="patient-health-metric is-green">
              <span class="patient-health-metric-icon"><svg viewBox="0 0 24 24"><path d="M12 20s-7-4.1-9-9.5C1.3 5.9 4.1 3 7.4 3c2 0 3.4 1 4.6 2.7C13.2 4 14.6 3 16.6 3 19.9 3 22.7 5.9 21 10.5 19 15.9 12 20 12 20Z"></path></svg></span>
              <strong>87</strong>
              <small>Puntaje de salud</small>
              <em>Bueno</em>
              <svg class="patient-health-sparkline" viewBox="0 0 180 44"><polyline points="0,31 14,30 28,27 42,31 56,28 70,29 84,22 98,30 112,25 126,27 140,18 154,24 168,20 180,22"></polyline></svg>
            </article>
            <article class="patient-health-metric is-blue">
              <span class="patient-health-metric-icon"><svg viewBox="0 0 24 24"><path d="M12 3s6 6.7 6 11a6 6 0 0 1-12 0c0-4.3 6-11 6-11Z"></path></svg></span>
              <strong>1.6 L</strong>
              <small>Agua hoy</small>
              <em>64% de meta</em>
              <svg class="patient-health-sparkline" viewBox="0 0 180 44"><polyline points="0,32 14,30 28,29 42,27 56,32 70,29 84,24 98,33 112,28 126,26 140,18 154,24 168,20 180,25"></polyline></svg>
            </article>
            <article class="patient-health-metric is-orange">
              <span class="patient-health-metric-icon"><svg viewBox="0 0 24 24"><path d="M8 4c1 0 2 .8 2 2v3l2 1 2-2 3 3-2 2 1 2h3c1.2 0 2 .9 2 2v3h-5l-3-3-3 3H6v-5l3-3-3-3V4h2Z"></path></svg></span>
              <strong>6,240</strong>
              <small>Pasos</small>
              <em>78% de meta</em>
              <svg class="patient-health-sparkline" viewBox="0 0 180 44"><polyline points="0,31 14,29 28,34 42,25 56,31 70,24 84,27 98,33 112,25 126,23 140,28 154,18 168,22 180,24"></polyline></svg>
            </article>
            <article class="patient-health-metric is-purple">
              <span class="patient-health-metric-icon"><svg viewBox="0 0 24 24"><path d="M18.5 15.5A8 8 0 0 1 8.5 5.5 7 7 0 1 0 18.5 15.5Z"></path><path d="M17 3v4M15 5h4"></path></svg></span>
              <strong>7h 20m</strong>
              <small>Sueño</small>
              <em>Bueno</em>
              <svg class="patient-health-sparkline" viewBox="0 0 180 44"><polyline points="0,32 14,31 28,29 42,30 56,27 70,32 84,30 98,23 112,31 126,26 140,25 154,18 168,23 180,24"></polyline></svg>
            </article>
          </section>

          <section class="patient-health-section patient-health-indicators">
            <header><h2>Indicadores clave</h2><button class="patient-health-view-all" type="button" data-health-open="indicators" aria-haspopup="dialog">Ver todos <b>›</b></button></header>
            <div class="patient-health-ring-grid">
              <article class="patient-health-ring is-green" style="--value:72"><span><strong>72%</strong></span><h3>Actividad física</h3><p>Meta semanal</p></article>
              <article class="patient-health-ring is-blue" style="--value:64"><span><strong>64%</strong></span><h3>Hidratación</h3><p>Meta diaria</p></article>
              <article class="patient-health-ring is-orange" style="--value:58"><span><strong>58%</strong></span><h3>Alimentación</h3><p>Meta diaria</p></article>
              <article class="patient-health-ring is-purple" style="--value:80"><span><strong>80%</strong></span><h3>Sueño</h3><p>Meta diaria</p></article>
            </div>
          </section>

          <section class="patient-health-section patient-health-plan">
            <header><h2>Tu plan de hoy</h2><button class="patient-health-view-all" type="button" data-health-open="plan" aria-haspopup="dialog">Ver plan completo <b>›</b></button></header>
            <div class="patient-health-plan-list">
              <article class="is-green"><span>▱</span><div><strong>Beber 2.5 L de agua</strong><small>1.6 L / 2.5 L</small><i style="--progress:64%"></i></div><b>›</b></article>
              <article class="is-orange"><span>⌁</span><div><strong>Caminar 8,000 pasos</strong><small>6,240 / 8,000 pasos</small><i style="--progress:78%"></i></div><b>›</b></article>
              <article class="is-purple"><span>◔</span><div><strong>Dormir al menos 7.5 horas</strong><small>7h 20m / 7.5h</small><i style="--progress:88%"></i></div><b>›</b></article>
            </div>
          </section>

          <section class="patient-health-section patient-health-recent">
            <header><h2>Datos recientes</h2><button class="patient-health-view-all" type="button" data-health-open="recent" aria-haspopup="dialog">Ver todos <b>›</b></button></header>
            <div class="patient-health-recent-grid">
              <article class="is-green"><span>▣</span><strong>72.4 kg</strong><small>Peso</small><em>Hoy</em><svg class="patient-health-sparkline" viewBox="0 0 150 34"><polyline points="0,24 15,22 30,26 45,18 60,23 75,16 90,25 105,18 120,20 135,16 150,18"></polyline></svg></article>
              <article class="is-blue"><span>♡</span><strong>115/75</strong><small>Presión arterial</small><em>Ayer</em><svg class="patient-health-sparkline" viewBox="0 0 150 34"><polyline points="0,25 15,23 30,27 45,24 60,14 75,25 90,20 105,13 120,26 135,21 150,22"></polyline></svg></article>
              <article class="is-orange"><span>♢</span><strong>92 <small>mg/dL</small></strong><small>Glucosa</small><em>Ayer</em><svg class="patient-health-sparkline" viewBox="0 0 150 34"><polyline points="0,25 15,18 30,27 45,23 60,16 75,25 90,20 105,18 120,24 135,21 150,19"></polyline></svg></article>
              <article class="is-purple"><span>♙</span><strong>Óptimos</strong><small>Biomarcadores</small><em>12/05/2026</em><svg class="patient-health-sparkline" viewBox="0 0 150 34"><polyline points="0,22 15,24 30,27 45,18 60,23 75,17 90,13 105,23 120,18 135,20 150,19"></polyline></svg></article>
            </div>
          </section>

          <div class="patient-health-mobile-screen-layer" data-health-screen-layer aria-hidden="true" hidden>
            <section class="patient-health-mobile-screen" data-health-screen="indicators" role="dialog" aria-modal="true" aria-labelledby="patient-health-screen-indicators-title" hidden>
              <header class="patient-health-screen-header">
                <button type="button" data-health-screen-close aria-label="Volver a Mi Salud">&larr;</button>
                <h2 id="patient-health-screen-indicators-title">Indicadores clave</h2>
              </header>
              <p class="patient-health-screen-lead">Tu progreso general hacia una vida m&aacute;s saludable.</p>
              <div class="patient-health-screen-list">
                <article class="patient-health-mobile-card patient-health-indicator-detail is-green" style="--value:72">
                  <span class="patient-health-screen-ring"><strong>72%</strong></span>
                  <div><h3>Actividad f&iacute;sica</h3><p>Meta semanal</p><small>&iexcl;Vas por buen camino! Sigue as&iacute;, tu cuerpo te lo agradece.</small></div>
                  <b>&rsaquo;</b>
                </article>
                <article class="patient-health-mobile-card patient-health-indicator-detail is-blue" style="--value:64">
                  <span class="patient-health-screen-ring"><strong>64%</strong></span>
                  <div><h3>Hidrataci&oacute;n</h3><p>Meta diaria</p><small>Bebe un poco m&aacute;s de agua para alcanzar tu meta diaria.</small></div>
                  <b>&rsaquo;</b>
                </article>
                <article class="patient-health-mobile-card patient-health-indicator-detail is-orange" style="--value:58">
                  <span class="patient-health-screen-ring"><strong>58%</strong></span>
                  <div><h3>Alimentaci&oacute;n</h3><p>Meta diaria</p><small>Sigue enfoc&aacute;ndote en elegir alimentos nutritivos.</small></div>
                  <b>&rsaquo;</b>
                </article>
                <article class="patient-health-mobile-card patient-health-indicator-detail is-purple" style="--value:80">
                  <span class="patient-health-screen-ring"><strong>80%</strong></span>
                  <div><h3>Sue&ntilde;o</h3><p>Meta diaria</p><small>&iexcl;Excelente! Est&aacute;s durmiendo lo necesario para recuperarte.</small></div>
                  <b>&rsaquo;</b>
                </article>
              </div>
              <aside class="patient-health-screen-tip">
                <span>&#127942;</span>
                <div><strong>Cada peque&ntilde;o paso cuenta</strong><p>Sigue as&iacute;, est&aacute;s construyendo h&aacute;bitos que duran toda la vida.</p></div>
              </aside>
            </section>

            <section class="patient-health-mobile-screen" data-health-screen="plan" role="dialog" aria-modal="true" aria-labelledby="patient-health-screen-plan-title" hidden>
              <header class="patient-health-screen-header">
                <button type="button" data-health-screen-close aria-label="Volver a Mi Salud">&larr;</button>
                <h2 id="patient-health-screen-plan-title">Tu plan de hoy</h2>
              </header>
              <p class="patient-health-screen-lead">Acciones simples para cuidar de tu bienestar d&iacute;a a d&iacute;a.</p>
              <h3 class="patient-health-screen-subtitle">Tu progreso del d&iacute;a</h3>
              <div class="patient-health-screen-list">
                <article class="patient-health-mobile-card patient-health-plan-detail is-green">
                  <span class="patient-health-screen-icon">&#9633;</span>
                  <div><h3>Beber 2.5 L de agua</h3><p>1.6 L / 2.5 L</p><i style="--progress:64%"></i><em>64%</em></div>
                  <b>&rsaquo;</b>
                </article>
                <article class="patient-health-mobile-card patient-health-plan-detail is-orange">
                  <span class="patient-health-screen-icon">&#8981;</span>
                  <div><h3>Caminar 8,000 pasos</h3><p>6,240 / 8,000 pasos</p><i style="--progress:78%"></i><em>78%</em></div>
                  <b>&rsaquo;</b>
                </article>
                <article class="patient-health-mobile-card patient-health-plan-detail is-purple">
                  <span class="patient-health-screen-icon">&#9684;</span>
                  <div><h3>Dormir al menos 7.5 horas</h3><p>7h 20m / 7.5h</p><i style="--progress:96%"></i><em>96%</em></div>
                  <b>&rsaquo;</b>
                </article>
              </div>
              <h3 class="patient-health-screen-subtitle">Consejo del d&iacute;a</h3>
              <aside class="patient-health-screen-tip">
                <span>&#128161;</span>
                <div><strong>Peque&ntilde;os h&aacute;bitos hoy, grandes cambios ma&ntilde;ana.</strong><p>Intenta completar tus metas antes de dormir.</p></div>
              </aside>
              <button class="patient-health-screen-primary" type="button">Ver plan completo</button>
            </section>

            <section class="patient-health-mobile-screen" data-health-screen="recent" role="dialog" aria-modal="true" aria-labelledby="patient-health-screen-recent-title" hidden>
              <header class="patient-health-screen-header">
                <button type="button" data-health-screen-close aria-label="Volver a Mi Salud">&larr;</button>
                <h2 id="patient-health-screen-recent-title">Datos recientes</h2>
              </header>
              <p class="patient-health-screen-lead">Un vistazo r&aacute;pido a tus m&eacute;tricas m&aacute;s importantes.</p>
              <div class="patient-health-screen-list patient-health-data-list">
                <article class="patient-health-mobile-card patient-health-data-detail is-green">
                  <span class="patient-health-screen-icon">&#9635;</span>
                  <div><strong>72.4 kg</strong><small>Peso</small><em>Hoy</em><svg class="patient-health-sparkline" viewBox="0 0 150 34"><polyline points="0,24 15,22 30,26 45,18 60,23 75,16 90,25 105,18 120,20 135,16 150,18"></polyline></svg></div>
                  <b>&rsaquo;</b>
                </article>
                <article class="patient-health-mobile-card patient-health-data-detail is-blue">
                  <span class="patient-health-screen-icon">&#9825;</span>
                  <div><strong>115/75</strong><small>Presi&oacute;n arterial</small><em>Ayer</em><svg class="patient-health-sparkline" viewBox="0 0 150 34"><polyline points="0,25 15,23 30,27 45,24 60,14 75,25 90,20 105,13 120,26 135,21 150,22"></polyline></svg></div>
                  <b>&rsaquo;</b>
                </article>
                <article class="patient-health-mobile-card patient-health-data-detail is-orange">
                  <span class="patient-health-screen-icon">&#9826;</span>
                  <div><strong>92 <small>mg/dL</small></strong><small>Glucosa</small><em>Ayer</em><svg class="patient-health-sparkline" viewBox="0 0 150 34"><polyline points="0,25 15,18 30,27 45,23 60,16 75,25 90,20 105,18 120,24 135,21 150,19"></polyline></svg></div>
                  <b>&rsaquo;</b>
                </article>
                <article class="patient-health-mobile-card patient-health-data-detail is-purple">
                  <span class="patient-health-screen-icon">&#9817;</span>
                  <div><strong>&Oacute;ptimos</strong><small>Biomarcadores</small><em>12/05/2026</em><svg class="patient-health-sparkline" viewBox="0 0 150 34"><polyline points="0,22 15,24 30,27 45,18 60,23 75,17 90,13 105,23 120,18 135,20 150,19"></polyline></svg></div>
                  <b>&rsaquo;</b>
                </article>
              </div>
              <aside class="patient-health-screen-tip">
                <span>&#128202;</span>
                <div><strong>La consistencia es clave</strong><p>Revisa tus datos regularmente para mantenerte en equilibrio.</p></div>
              </aside>
              <button class="patient-health-screen-primary" type="button">Ver todos los datos</button>
            </section>
          </div>
        </article>
      </section>

      <section class="patient-portal-view" data-patient-view="devices">
        <article class="patient-devices-panel">
          <header class="patient-devices-hero">
            <div class="patient-devices-hero-icon" aria-hidden="true">
              <svg viewBox="0 0 52 52" role="img" aria-label="">
                <path d="M20 7h12"></path>
                <path d="M22 7v13L12.5 38.5A5 5 0 0 0 17 46h18a5 5 0 0 0 4.5-7.5L30 20V7"></path>
                <path d="M17.5 36h17"></path>
                <path d="M20.5 31h11"></path>
              </svg>
            </div>
            <div>
              <h1>Dispositivos médicos</h1>
              <p>Dispositivos vinculados y equipos para seguimiento</p>
            </div>
          </header>

          <div class="patient-devices-redesign">
            <nav class="patient-device-filters" aria-label="Filtros de dispositivos">
              <button class="is-active" type="button"><span class="patient-device-filter-icon patient-device-filter-menu" aria-hidden="true"></span>Todos</button>
              <button class="is-linked" type="button"><span class="patient-device-filter-icon patient-device-filter-check" aria-hidden="true"></span>Vinculados</button>
              <button class="is-pending" type="button"><span class="patient-device-filter-icon patient-device-filter-clock" aria-hidden="true"></span>Pendientes</button>
              <button class="is-vitals" type="button"><span class="patient-device-filter-icon patient-device-filter-heart" aria-hidden="true"></span>Signos vitales</button>
              <button class="is-glucose" type="button"><span class="patient-device-filter-icon patient-device-filter-drop" aria-hidden="true"></span>Glucosa</button>
            </nav>

            <div class="patient-device-list">
              <article class="patient-device-card is-linked">
                <div class="patient-device-icon-wrap" aria-hidden="true">
                  <span class="patient-device-presence-dot"></span>
                  <div class="patient-device-icon-tile patient-device-icon-pressure">
                    <svg viewBox="0 0 44 44" role="img" aria-label="">
                      <rect x="13" y="8" width="18" height="28" rx="2.5"></rect>
                      <path d="M18 14h8M18 20h8M18 26h8"></path>
                      <path d="M31 12h2.5c2.5 0 4.5 2 4.5 4.5v6"></path>
                    </svg>
                  </div>
                </div>
                <div class="patient-device-content">
                  <div class="patient-device-main">
                    <div class="patient-device-copy">
                      <h2>Monitor de presión arterial</h2>
                      <p>Seguimiento de signos vitales y control cardiovascular.</p>
                    </div>
                    <span class="patient-device-badge is-linked">Vinculado</span>
                  </div>
                  <dl class="patient-device-details">
                    <div><dt>Tipo</dt><dd>Signos vitales</dd></div>
                    <div><dt>Última sincronización</dt><dd>15/06/2026 08:40</dd></div>
                    <div><dt>Lectura reciente</dt><dd>120/80 mmHg</dd></div>
                  </dl>
                </div>
                <div class="patient-device-actions">
                  <button class="patient-device-action is-primary" type="button">Ver detalle</button>
                  <button class="patient-device-action" type="button">Sincronizar</button>
                </div>
              </article>

              <article class="patient-device-card is-pending">
                <div class="patient-device-icon-wrap" aria-hidden="true">
                  <span class="patient-device-presence-dot"></span>
                  <div class="patient-device-icon-tile patient-device-icon-glucose">
                    <svg viewBox="0 0 44 44" role="img" aria-label="">
                      <path d="M17 18V8h10v10l5 7v9a5 5 0 0 1-5 5H17a5 5 0 0 1-5-5v-9l5-7Z"></path>
                      <path d="M17 15h10M22 25v8M18 29h8"></path>
                    </svg>
                  </div>
                </div>
                <div class="patient-device-content">
                  <div class="patient-device-main">
                    <div class="patient-device-copy">
                      <h2>Glucómetro digital</h2>
                      <p>Registro de glucosa capilar y alertas de control metabólico.</p>
                    </div>
                    <span class="patient-device-badge is-pending">Pendiente</span>
                  </div>
                  <dl class="patient-device-details">
                    <div><dt>Tipo</dt><dd>Glucosa capilar</dd></div>
                    <div><dt>Última sincronización</dt><dd>Sin sincronización</dd></div>
                    <div><dt>Acción requerida</dt><dd>Configurar enlace</dd></div>
                  </dl>
                </div>
                <div class="patient-device-actions">
                  <button class="patient-device-action is-primary" type="button">Configurar</button>
                  <button class="patient-device-action" type="button">Ver guía</button>
                </div>
              </article>
            </div>
          </div>
        </article>
      </section>

      <section class="patient-portal-view" data-patient-view="register">
        <article class="patient-register-panel">
          <header class="patient-register-hero">
            <div class="patient-register-hero-icon" aria-hidden="true">
              <span>+</span>
            </div>
            <div>
              <small>REGISTRO MANUAL</small>
              <h1>Registro diario</h1>
              <p>Captura parametros manuales y datos del dia.</p>
            </div>
          </header>

          <div class="patient-register-workspace">
            <section class="patient-register-sheet patient-register-view-sheet" aria-labelledby="patient-register-title">
              <small>Registro rapido</small>
              <h2 id="patient-register-title">Registrar parametro</h2>
              <p>Selecciona el parametro, captura el valor y guarda el registro en el historial manual.</p>

              <div class="patient-register-options" data-register-options aria-label="Tipo de parametro">
                <button class="is-active" type="button" data-register-metric="water" aria-pressed="true"><span>◌</span>Agua</button>
                <button type="button" data-register-metric="steps" aria-pressed="false"><span>⌁</span>Pasos</button>
                <button type="button" data-register-metric="sleep" aria-pressed="false"><span>◔</span>Sue&ntilde;o</button>
                <button type="button" data-register-metric="weight" aria-pressed="false"><span>▣</span>Peso</button>
                <button type="button" data-register-metric="pressure" aria-pressed="false"><span>♡</span>Presi&oacute;n</button>
                <button type="button" data-register-metric="glucose" aria-pressed="false"><span>♢</span>Glucosa</button>
                <button type="button" data-register-metric="mood" aria-pressed="false"><span>☺</span>&Aacute;nimo</button>
                <button type="button" data-register-metric="note" aria-pressed="false"><span>✎</span>Nota</button>
                <button type="button" data-register-metric="custom" aria-pressed="false"><span>+</span>Otro</button>
              </div>

              <form class="patient-register-form" data-register-form>
                <input type="hidden" name="metricType" value="water" data-register-metric-input>
                <label data-register-custom-wrap hidden>
                  <span>Nombre del parametro</span>
                  <input name="customMetric" placeholder="Ej. Temperatura, dolor, energia">
                </label>
                <label>
                  <span data-register-value-label>Valor</span>
                  <div class="patient-register-value-row">
                    <input name="value" type="number" step="0.1" placeholder="Meta sugerida: 2.5 L" required data-register-value>
                    <b data-register-unit>L</b>
                  </div>
                </label>
                <label>
                  <span>Fecha y hora</span>
                  <input name="recordedAt" type="datetime-local" required data-register-date>
                </label>
                <label>
                  <span>Comentario opcional</span>
                  <textarea name="notes" rows="3" placeholder="Agrega contexto si lo necesitas"></textarea>
                </label>
                <div class="patient-register-attachment">
                  <span>Fotografia o documento</span>
                  <div class="patient-register-attachment-box">
                    <button class="patient-register-attachment-button" type="button" data-register-attachment-pick aria-label="Agregar fotografia o documento">+</button>
                    <div>
                      <strong>Agregar evidencia</strong>
                      <small data-register-attachment-name>Sin archivo seleccionado</small>
                    </div>
                    <input type="file" accept="image/*,.pdf" name="attachment" data-register-attachment-input hidden>
                  </div>
                </div>
                <div class="patient-register-success" data-register-success hidden>
                  Registro guardado. Se agrego a tus datos manuales.
                </div>
                <div class="patient-register-actions">
                  <button class="patient-register-secondary" type="reset">Limpiar</button>
                  <button class="patient-register-primary" type="submit">Guardar registro</button>
                </div>
              </form>
            </section>
          </div>
        </article>
      </section>

      <nav class="patient-portal-bottom-nav" aria-label="Navegación principal del paciente">
        <button class="is-active" type="button" data-open-view="home"><span class="patient-bottom-icon">⌂</span>Home</button>
        <button type="button" data-open-view="devices"><span class="patient-bottom-icon">▯</span>Dispositivos</button>
        <button class="patient-bottom-register" type="button" data-open-view="register"><span>＋</span>Registro</button>
        <button type="button" data-open-view="wellness"><span class="patient-bottom-icon">☆</span>Mi salud</button>
        <button type="button" data-open-view="communities"><span class="patient-bottom-icon">○</span>Comunidades</button>
      </nav>
    </main>
  </div>
</div>

<div class="patient-profile-shade" data-patient-profile-shade hidden></div>
<aside
  class="patient-profile-panel"
  data-patient-profile-panel
  data-storage-key="<?php echo e('drsam_patient_profile_panel_'.$patient->id); ?>"
  data-profile-name="<?php echo e(e($patient->full_name)); ?>"
  data-profile-id="<?php echo e(e($patient->platform_number ?? (string) $patient->id)); ?>"
  data-profile-curp="<?php echo e(e($patient->curp ?: 'No registrado')); ?>"
  data-profile-email="<?php echo e(e($patient->email ?: 'paciente@demo.drsam.local')); ?>"
  data-profile-phone="<?php echo e(e($patient->phone ?: 'No registrado')); ?>"
  data-profile-status="<?php echo e(e($statusText($patient->status))); ?>"
  data-profile-doctor="<?php echo e(e($patient->primaryDoctor?->full_name ?? 'Sin asignar')); ?>"
  data-profile-completed="<?php echo e(e($patient->profile_completed_at?->format('d/m/Y') ?? 'Pendiente')); ?>"
  data-logout-url="<?php echo e(route('logout')); ?>"
  data-login-url="<?php echo e(route('login')); ?>"
  data-csrf-token="<?php echo e(csrf_token()); ?>"
  aria-label="Perfil del paciente"
  aria-hidden="true"
  hidden>
  <div class="patient-profile-scroll">
    <header class="patient-profile-header">
      <button class="patient-profile-avatar-button" type="button" data-profile-view="photo" aria-label="Ver o cambiar foto de perfil">
        <span class="patient-profile-avatar" data-profile-panel-avatar><?php echo e($initials ?: 'PX'); ?></span>
        <span class="patient-profile-avatar-add" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span>
      </button>
      <div class="patient-profile-identity">
        <strong data-profile-panel-name><?php echo e($patient->full_name); ?></strong>
        <span>ID DE USUARIO - <b data-profile-panel-id><?php echo e($patient->platform_number ?? $patient->id); ?></b></span>
      </div>
      <button class="patient-profile-icon-button" type="button" data-profile-panel-close aria-label="Cerrar perfil">
        <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </header>

    <button class="patient-profile-row patient-profile-finance-row" type="button" data-profile-view="finance">
      <span class="patient-profile-finance-art" aria-hidden="true">
        <span class="patient-profile-wallet-shape"></span>
        <span class="patient-profile-coin patient-profile-coin-one"></span>
        <span class="patient-profile-coin patient-profile-coin-two"></span>
      </span>
      <span class="patient-profile-row-copy"><strong>Billetera y Suscripción</strong><small>Pagos, saldo y plan de acceso</small></span>
      <span class="patient-profile-row-action" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></span>
    </button>

    <section class="patient-profile-support-group" data-profile-support-group>
      <button class="patient-profile-row" type="button" data-profile-support-toggle aria-expanded="false">
        <span class="patient-profile-row-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 13v-2a8 8 0 0 1 16 0v2"/><path d="M4 13h3v6H5a2 2 0 0 1-2-2v-2a2 2 0 0 1 1-2Zm16 0h-3v6h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-1-2ZM17 19c0 1.1-2.2 2-5 2"/></svg></span>
        <span class="patient-profile-row-copy"><strong>Interacciones</strong><small>Solicitudes, mensajes, alertas y enlaces</small></span>
        <span class="patient-profile-row-action patient-profile-support-chevron" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></span>
      </button>
      <div class="patient-profile-support-menu" data-profile-support-menu hidden>
        <button class="patient-profile-support-item" type="button" data-profile-view="requests">
          <span class="patient-profile-row-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 3h6v3H9zM9 11h6M9 15h4"/></svg></span>
          <span class="patient-profile-row-copy"><strong>Solicitudes</strong><small data-profile-count-label="requests">Sin solicitudes pendientes</small></span>
          <b class="patient-profile-badge" data-profile-count="requests">0</b>
        </button>
        <button class="patient-profile-support-item" type="button" data-profile-view="messages">
          <span class="patient-profile-row-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/><path d="M8 9h8M8 13h5"/></svg></span>
          <span class="patient-profile-row-copy"><strong>Mensajes</strong><small data-profile-count-label="messages">Médicos, seguros y unidades</small></span>
          <b class="patient-profile-badge" data-profile-count="messages">3</b>
        </button>
        <button class="patient-profile-support-item" type="button" data-profile-view="alerts">
          <span class="patient-profile-row-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM9 21h6"/></svg></span>
          <span class="patient-profile-row-copy"><strong>Alertas</strong><small data-profile-count-label="alerts">Recordatorios y avisos importantes</small></span>
          <b class="patient-profile-badge" data-profile-count="alerts">2</b>
        </button>
        <button class="patient-profile-support-item" type="button" data-profile-view="sync">
          <span class="patient-profile-row-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 7h-6V1M4 17h6v6"/><path d="M20 7a9 9 0 0 0-15-3M4 17a9 9 0 0 0 15 3"/></svg></span>
          <span class="patient-profile-row-copy"><strong>Sincronización</strong><small data-profile-count-label="sync">2 enlaces activos</small></span>
          <b class="patient-profile-badge" data-profile-count="sync">2</b>
        </button>
      </div>
    </section>

    <div class="patient-profile-divider"></div>
    <button class="patient-profile-row" type="button" data-profile-view="profile">
      <span class="patient-profile-row-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
      <span class="patient-profile-row-copy"><strong>Información de perfil</strong><small>Datos personales e identificación</small></span>
      <span class="patient-profile-row-action" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></span>
    </button>
    <button class="patient-profile-row patient-profile-logout-row" type="button" data-profile-view="logout">
      <span class="patient-profile-row-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/></svg></span>
      <span class="patient-profile-row-copy"><strong>Cerrar sesión</strong></span>
    </button>
  </div>
</aside>

<div class="patient-profile-modal" data-patient-profile-modal aria-hidden="true" hidden>
  <section class="patient-profile-dialog" data-patient-profile-dialog role="dialog" aria-modal="true" aria-labelledby="patient-profile-modal-title">
    <header class="patient-profile-modal-header">
      <div><small data-patient-profile-modal-eyebrow>PERFIL DEL PACIENTE</small><h2 id="patient-profile-modal-title" data-patient-profile-modal-title>Detalle</h2></div>
      <button class="patient-profile-icon-button" type="button" data-profile-close-modal aria-label="Cerrar ventana"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    </header>
    <div class="patient-profile-modal-body" data-patient-profile-modal-body></div>
  </section>
</div>
<input type="file" accept="image/*" data-patient-profile-photo hidden>

<script src="<?php echo e(asset('js/patient-profile-panel.js')); ?>?v=<?php echo e(filemtime(public_path('js/patient-profile-panel.js'))); ?>"></script>
<script src="<?php echo e(asset('js/communities.js')); ?>?v=<?php echo e(filemtime(public_path('js/communities.js'))); ?>"></script>
<script>
(() => {
  const portal = document.querySelector('[data-patient-portal]');
  if (!portal) return;
  let communitiesApp = null;
  const mountCommunities = () => {
    const root = portal.querySelector('[data-communities-root]');
    if (!root || communitiesApp || !window.KliniCommunities) return;
    communitiesApp = window.KliniCommunities.mount(root, {
      patient: {
        id: <?php echo json_encode($patient->platform_number ?? (string) $patient->id, 15, 512) ?>,
        name: <?php echo json_encode($patient->full_name, 15, 512) ?>,
      },
      storageKey: <?php echo json_encode('drsam_patient_communities_'.$patient->id, 15, 512) ?>,
      profileStorageKey: <?php echo json_encode('drsam_patient_profile_panel_'.$patient->id, 15, 512) ?>,
      onAction: event => window.dispatchEvent(new CustomEvent('drsam:communities-action', { detail: event })),
    });
  };
  const openView = (name) => {
    portal.querySelectorAll('[data-patient-view]').forEach(el => el.classList.toggle('is-active', el.dataset.patientView === name));
    portal.querySelectorAll('[data-open-view]').forEach(el => el.classList.toggle('is-active', el.dataset.openView === name));
    sessionStorage.setItem('patientPortalView', name);
    if (name === 'communities') mountCommunities();
    if (name === 'register') {
      if (registerSuccess) registerSuccess.hidden = true;
      if (registerDateInput && !registerDateInput.value) registerDateInput.value = registerDateValue();
      selectRegisterMetric(registerMetricInput?.value || 'water');
      setTimeout(() => registerValueInput?.focus({ preventScroll: true }), 120);
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
  const healthScreenLayer = portal.querySelector('[data-health-screen-layer]');
  const healthScreens = [...portal.querySelectorAll('[data-health-screen]')];
  const registerForm = document.querySelector('[data-register-form]');
  const registerOptions = document.querySelector('[data-register-options]');
  const registerMetricInput = document.querySelector('[data-register-metric-input]');
  const registerValueInput = document.querySelector('[data-register-value]');
  const registerValueLabel = document.querySelector('[data-register-value-label]');
  const registerUnit = document.querySelector('[data-register-unit]');
  const registerDateInput = document.querySelector('[data-register-date]');
  const registerCustomWrap = document.querySelector('[data-register-custom-wrap]');
  const registerAttachmentInput = document.querySelector('[data-register-attachment-input]');
  const registerAttachmentName = document.querySelector('[data-register-attachment-name]');
  const registerSuccess = document.querySelector('[data-register-success]');
  const registerMetricConfig = {
    water: { label: 'Agua', unit: 'L', inputType: 'number', step: '0.1', placeholder: 'Meta sugerida: 2.5 L' },
    steps: { label: 'Pasos', unit: 'pasos', inputType: 'number', step: '1', placeholder: 'Ej. 8000' },
    sleep: { label: 'Sueno', unit: 'h', inputType: 'number', step: '0.1', placeholder: 'Ej. 7.5' },
    weight: { label: 'Peso', unit: 'kg', inputType: 'number', step: '0.1', placeholder: 'Ej. 72.4' },
    pressure: { label: 'Presion arterial', unit: 'mmHg', inputType: 'text', step: '', placeholder: 'Ej. 120/80' },
    glucose: { label: 'Glucosa', unit: 'mg/dL', inputType: 'number', step: '0.1', placeholder: 'Ej. 92' },
    mood: { label: 'Estado de animo', unit: '/5', inputType: 'number', step: '1', placeholder: 'Ej. 4' },
    note: { label: 'Nota personal', unit: '', inputType: 'text', step: '', placeholder: 'Ej. Me senti con mas energia' },
    custom: { label: 'Parametro personalizado', unit: '', inputType: 'text', step: '', placeholder: 'Ingresa el valor' },
  };
  const registerStorageKey = <?php echo json_encode('drsam_patient_manual_records_'.$patient->id, 15, 512) ?>;
  const registerDateValue = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
  };
  const selectRegisterMetric = (type) => {
    const config = registerMetricConfig[type] || registerMetricConfig.water;
    if (registerMetricInput) registerMetricInput.value = type;
    if (registerValueLabel) registerValueLabel.textContent = type === 'note' ? 'Nota' : 'Valor';
    if (registerValueInput) {
      registerValueInput.type = config.inputType;
      registerValueInput.step = config.step || '';
      registerValueInput.placeholder = config.placeholder;
      registerValueInput.value = '';
    }
    if (registerUnit) {
      registerUnit.textContent = config.unit || ' ';
      registerUnit.hidden = !config.unit;
    }
    if (registerCustomWrap) {
      registerCustomWrap.hidden = type !== 'custom';
      registerCustomWrap.querySelector('input')?.toggleAttribute('required', type === 'custom');
    }
    registerOptions?.querySelectorAll('[data-register-metric]').forEach(button => {
      const active = button.dataset.registerMetric === type;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      if (active) button.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    });
  };
  const saveRegisterRecord = (record) => {
    let records = [];
    try {
      records = JSON.parse(localStorage.getItem(registerStorageKey) || '[]');
    } catch (error) {
      records = [];
    }
    records.unshift(record);
    localStorage.setItem(registerStorageKey, JSON.stringify(records.slice(0, 50)));
  };
  const closeHealthScreen = () => {
    if (!healthScreenLayer) return;
    healthScreenLayer.hidden = true;
    healthScreenLayer.setAttribute('aria-hidden', 'true');
    healthScreens.forEach(screen => {
      screen.hidden = true;
      screen.classList.remove('is-active');
    });
    document.body.classList.remove('is-health-screen-open');
  };
  const openHealthScreen = (name) => {
    const target = healthScreens.find(screen => screen.dataset.healthScreen === name);
    if (!healthScreenLayer || !target) return;
    healthScreens.forEach(screen => {
      const active = screen === target;
      screen.hidden = !active;
      screen.classList.toggle('is-active', active);
    });
    healthScreenLayer.hidden = false;
    healthScreenLayer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('is-health-screen-open');
    healthScreenLayer.scrollTop = 0;
    target.scrollTop = 0;
  };
  const healthFilterCarousel = portal.querySelector('[data-health-filter-carousel]');
  if (healthFilterCarousel) {
    let isDragging = false;
    let dragStart = 0;
    let dragScroll = 0;
    healthFilterCarousel.addEventListener('pointerdown', event => {
      isDragging = true;
      dragStart = event.clientX;
      dragScroll = healthFilterCarousel.scrollLeft;
      healthFilterCarousel.classList.add('is-dragging');
      healthFilterCarousel.setPointerCapture?.(event.pointerId);
    });
    healthFilterCarousel.addEventListener('pointermove', event => {
      if (!isDragging) return;
      healthFilterCarousel.scrollLeft = dragScroll - (event.clientX - dragStart);
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(type => {
      healthFilterCarousel.addEventListener(type, event => {
        isDragging = false;
        healthFilterCarousel.classList.remove('is-dragging');
        if (healthFilterCarousel.hasPointerCapture?.(event.pointerId)) healthFilterCarousel.releasePointerCapture(event.pointerId);
      });
    });
    healthFilterCarousel.querySelectorAll('[data-health-filter]').forEach(button => {
      button.addEventListener('click', () => {
        healthFilterCarousel.querySelectorAll('[data-health-filter]').forEach(item => {
          const active = item === button;
          item.classList.toggle('is-active', active);
          item.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        button.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      });
    });
  }
  if (registerOptions) {
    let isRegisterPanning = false;
    let registerPanStart = 0;
    let registerPanScroll = 0;
    registerOptions.addEventListener('pointerdown', event => {
      isRegisterPanning = true;
      registerPanStart = event.clientX;
      registerPanScroll = registerOptions.scrollLeft;
      registerOptions.classList.add('is-panning');
      registerOptions.setPointerCapture?.(event.pointerId);
    });
    registerOptions.addEventListener('pointermove', event => {
      if (!isRegisterPanning) return;
      registerOptions.scrollLeft = registerPanScroll - (event.clientX - registerPanStart);
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(type => {
      registerOptions.addEventListener(type, event => {
        isRegisterPanning = false;
        registerOptions.classList.remove('is-panning');
        if (registerOptions.hasPointerCapture?.(event.pointerId)) registerOptions.releasePointerCapture(event.pointerId);
      });
    });
    registerOptions.querySelectorAll('[data-register-metric]').forEach(button => {
      button.addEventListener('click', () => selectRegisterMetric(button.dataset.registerMetric));
    });
  }
  registerAttachmentInput?.addEventListener('change', () => {
    const file = registerAttachmentInput.files && registerAttachmentInput.files[0];
    if (registerAttachmentName) registerAttachmentName.textContent = file ? file.name : 'Sin archivo seleccionado';
  });
  document.querySelector('[data-register-attachment-pick]')?.addEventListener('click', () => {
    registerAttachmentInput?.click();
  });
  registerForm?.addEventListener('submit', event => {
    event.preventDefault();
    if (!registerForm.reportValidity()) return;
    const data = new FormData(registerForm);
    const metricType = String(data.get('metricType') || 'water');
    const config = registerMetricConfig[metricType] || registerMetricConfig.water;
    const customMetric = String(data.get('customMetric') || '').trim();
    const attachment = registerAttachmentInput?.files?.[0];
    saveRegisterRecord({
      id: Date.now(),
      metricType,
      label: metricType === 'custom' && customMetric ? customMetric : config.label,
      value: String(data.get('value') || '').trim(),
      unit: config.unit,
      recordedAt: String(data.get('recordedAt') || ''),
      notes: String(data.get('notes') || '').trim(),
      attachmentName: attachment ? attachment.name : '',
      source: 'Registro manual',
    });
    if (registerSuccess) registerSuccess.hidden = false;
    registerForm.reset();
    if (registerDateInput) registerDateInput.value = registerDateValue();
    if (registerAttachmentName) registerAttachmentName.textContent = 'Sin archivo seleccionado';
    selectRegisterMetric(metricType);
  });
  registerForm?.addEventListener('reset', () => {
    setTimeout(() => {
      if (registerDateInput) registerDateInput.value = registerDateValue();
      if (registerAttachmentName) registerAttachmentName.textContent = 'Sin archivo seleccionado';
      if (registerSuccess) registerSuccess.hidden = true;
      selectRegisterMetric(registerMetricInput?.value || 'water');
    });
  });
  portal.addEventListener('click', event => {
    const healthClose = event.target.closest('[data-health-screen-close]');
    if (healthClose) {
      closeHealthScreen();
      return;
    }
    const healthOpen = event.target.closest('[data-health-open]');
    if (healthOpen) {
      openHealthScreen(healthOpen.dataset.healthOpen);
      return;
    }
    if (healthScreenLayer && event.target === healthScreenLayer) {
      closeHealthScreen();
      return;
    }
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
    closeHealthScreen();
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

<?php echo $__env->make('layouts.app', ['title' => 'Portal del Paciente'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views/patient/dashboard.blade.php ENDPATH**/ ?>