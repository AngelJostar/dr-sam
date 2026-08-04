@extends('layouts.app', ['title' => 'Portal del Paciente'])

@section('body_class', 'patient-assistant-native-body patient-portal-body')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/patient-profile-panel.css') }}?v={{ filemtime(public_path('css/patient-profile-panel.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/communities.css') }}?v={{ filemtime(public_path('css/communities.css')) }}">
@endpush

@php
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
@endphp

@section('content')
<div class="patient-assistant-native-screen patient-portal" data-patient-portal>
  <header class="patient-assistant-native-topbar patient-portal-topbar">
    <button class="patient-portal-dots" type="button" aria-label="Abrir interacciones" aria-expanded="false" aria-controls="patient-support-menu" data-support-toggle>⋮</button>
    <form class="patient-portal-question" onsubmit="return false" data-ai-top-form>
      <input aria-label="Pregunta para Dr. Sam" placeholder="" data-ai-top-input>
      <button type="submit" aria-label="Preguntar" data-ai-top-submit>
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M22 2 11 13"></path>
          <path d="m22 2-7 20-4-9-9-4 20-7Z"></path>
        </svg>
      </button>
    </form>
    <button class="patient-portal-profile-trigger" type="button" data-profile-panel-open aria-label="Abrir perfil del paciente">
      <span>{{ $initials ?: 'PX' }}</span>
    </button>
  </header>

  <div class="patient-ai-chat-shell" data-ai-chat-panel aria-hidden="true" hidden>
    <section class="patient-ai-chat" aria-label="Chat con Dr. Sam">
      <button class="patient-ai-chat-close" type="button" aria-label="Cerrar chat" data-ai-chat-close>×</button>
      <div class="patient-ai-chat-messages" data-ai-chat-messages aria-live="polite"></div>
      <form class="patient-ai-chat-composer" data-ai-chat-form>
        <input aria-label="Escribe otro mensaje para Dr. Sam" placeholder="Escribe otro mensaje..." data-ai-chat-input>
        <button type="submit" aria-label="Enviar mensaje">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M22 2 11 13"></path>
            <path d="m22 2-7 20-4-9-9-4 20-7Z"></path>
          </svg>
        </button>
      </form>
    </section>
  </div>

  <div class="patient-portal-layout">
    <aside id="patient-support-menu" class="patient-assistant-native-menu patient-portal-menu" aria-label="Menú del paciente" aria-hidden="true" data-support-menu>
      <strong class="patient-portal-menu-title">Interacciones</strong>
      @foreach ($supportViews as $key => [$label, $icon])
        <button type="button" data-open-view="{{ $key }}">
          <span class="patient-support-icon patient-support-icon-{{ $icon }}" aria-hidden="true"></span>{{ $label }}
        </button>
      @endforeach
    </aside>

    <main class="patient-portal-content">
      @if (session('patient_notice'))
        <div class="patient-portal-notice">{{ session('patient_notice') }}</div>
      @endif
      @if ($errors->any())
        <div class="patient-portal-notice is-error">{{ $errors->first() }}</div>
      @endif

      <section class="patient-portal-view is-active" data-patient-view="home">
        <div class="patient-assistant-native-intro patient-portal-intro">
          <span class="patient-assistant-native-spark">+</span>
          <h1>¿Cómo amaneciste hoy?</h1>
        </div>
      </section>

      <section class="patient-portal-view" data-patient-view="profile">
        <div class="patient-portal-view-heading"><div><small>PERFIL DEL PACIENTE</small><h1>Mi perfil</h1><p>Información personal y datos de contacto.</p></div></div>
        <div class="patient-portal-two-columns">
          <form class="patient-portal-card patient-portal-form" method="post" action="{{ route('patient.profile.update') }}">
            @csrf @method('PATCH')
            <h2>Datos personales</h2>
            <div class="patient-portal-form-grid">
              <label>Nombre<input name="first_name" value="{{ old('first_name', $patient->first_name) }}"></label>
              <label>Apellidos<input name="last_name" value="{{ old('last_name', $patient->last_name) }}"></label>
              <label>Fecha de nacimiento<input type="date" name="birth_date" value="{{ old('birth_date', $patient->birth_date?->format('Y-m-d')) }}"></label>
              <label>Sexo<select name="sex"><option value="unspecified">Sin especificar</option><option value="female" @selected($patient->sex === 'female')>Femenino</option><option value="male" @selected($patient->sex === 'male')>Masculino</option><option value="other" @selected($patient->sex === 'other')>Otro</option></select></label>
              <label>CURP<input name="curp" maxlength="18" value="{{ old('curp', $patient->curp) }}"></label>
              <label>No. usuario plataforma<input value="{{ $patient->platform_number }}" disabled></label>
              <label>Teléfono<input name="phone" value="{{ old('phone', $patient->phone) }}"></label>
              <label>Correo<input type="email" name="email" value="{{ old('email', $patient->email) }}" readonly aria-readonly="true"></label>
            </div>
            <button class="patient-portal-primary" type="submit">Guardar cambios</button>
          </form>
          <article class="patient-portal-card patient-portal-preview">
            <div class="patient-portal-avatar">{{ $initials ?: 'PX' }}</div>
            <h2>{{ $patient->full_name }}</h2><p>{{ $patient->platform_number ?? 'Sin número de plataforma' }}</p>
            <dl><div><dt>Estatus</dt><dd>{{ $statusText($patient->status) }}</dd></div><div><dt>Médico principal</dt><dd>{{ $patient->primaryDoctor?->full_name ?? 'Sin asignar' }}</dd></div><div><dt>Perfil completado</dt><dd>{{ $patient->profile_completed_at?->format('d/m/Y') ?? 'Pendiente' }}</dd></div></dl>
          </article>
        </div>
      </section>

      <section class="patient-portal-view patient-portal-communities-view" data-patient-view="communities">
        <div id="patient-communities-root" data-communities-root></div>
      </section>

      <section class="patient-portal-view" data-patient-view="insurance">
        @php
          $policyMeta = $policy?->metadata ?? [];
          $advisorName = $policyMeta['advisor_name'] ?? 'Andrea Suarez';
          $insuranceComplete = $policy
            && $policy->policy_number
            && $policy->insurer_name
            && $policy->starts_at
            && $policy->ends_at;
          $insuranceValue = fn ($value, $fallback = 'No registrado') => filled($value) ? $value : $fallback;
        @endphp
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
              <div class="patient-insurance-alert {{ $insuranceComplete ? 'is-complete' : '' }}">
                <span>{{ $insuranceComplete ? '✓' : '◷' }}</span>
                <div><b>{{ $insuranceComplete ? 'Información completa' : 'Información pendiente' }}</b><small>{{ $insuranceComplete ? 'Tu póliza se encuentra registrada.' : 'Hay datos por completar para tu seguro.' }}</small></div>
              </div>
              <button class="patient-insurance-complete-link" type="button" data-insurance-form-open>Completa tu información →</button>
              <div class="patient-insurance-actions">
                <button class="patient-insurance-upload" type="button" data-insurance-form-open><span>●</span>Subir póliza</button>
                <button type="button" data-insurance-form-open>⌕&nbsp; Actualizar datos</button>
              </div>
            </section>
            <aside class="patient-insurance-advisor">
              <div class="patient-insurance-advisor-heading"><span>{{ collect(explode(' ', $advisorName))->filter()->take(2)->map(fn($part) => mb_substr($part, 0, 1))->implode('') ?: 'AS' }}</span><div><small>ASESOR DE SEGUROS VINCULADO</small><strong>{{ $advisorName }}</strong></div></div>
              <p>Cualquier solicitud de soporte se canalizará con tu asesor vinculado para dar seguimiento a coberturas, autorizaciones y reembolsos.</p>
              <div><small>ASEGURADORA</small><strong>{{ $insuranceValue($policy?->insurer_name, 'Por asignar') }}</strong></div>
              @if($policyMeta['advisor_email'] ?? null)
                <a href="mailto:{{ $policyMeta['advisor_email'] }}">Contactar asesor</a>
              @else
                <button type="button" disabled title="Asesor sin medio de contacto registrado">Contactar asesor</button>
              @endif
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
                <div><dt>♢&nbsp; Aseguradora</dt><dd class="{{ $policy?->insurer_name ? '' : 'is-missing' }}">{{ $insuranceValue($policy?->insurer_name, 'Aseguradora no registrada') }}</dd></div>
                <div><dt>▣&nbsp; Vigencia</dt><dd class="{{ $policy?->starts_at && $policy?->ends_at ? '' : 'is-missing' }}">{{ $policy?->starts_at && $policy?->ends_at ? $policy->starts_at->format('d/m/Y').' - '.$policy->ends_at->format('d/m/Y') : 'No registrada' }}</dd></div>
                <div><dt>▯&nbsp; Póliza</dt><dd class="{{ $policy?->policy_number ? '' : 'is-missing' }}">{{ $insuranceValue($policy?->policy_number) }}</dd></div>
                <div><dt>▣&nbsp; Deducible</dt><dd class="{{ filled($policyMeta['deductible'] ?? null) ? '' : 'is-missing' }}">{{ $insuranceValue($policyMeta['deductible'] ?? null) }}</dd></div>
                <div><dt>▣&nbsp; Plan</dt><dd class="{{ $policy?->plan_name ? '' : 'is-pending' }}">{{ $insuranceValue($policy?->plan_name, 'Plan pendiente de captura') }}</dd></div>
                <div><dt>♢&nbsp; Coaseguro</dt><dd class="{{ filled($policyMeta['coinsurance'] ?? null) ? '' : 'is-missing' }}">{{ $insuranceValue($policyMeta['coinsurance'] ?? null) }}</dd></div>
              </dl>
            </section>
            @foreach (['coverage' => ['Cobertura', 'Los beneficios y límites de cobertura aparecerán cuando sean registrados.'], 'programs' => ['Programas', 'No hay programas de apego vinculados a esta póliza.'], 'network' => ['Red de Médicos', 'No hay una red médica registrada para esta póliza.'], 'medications' => ['Medicamentos', 'No hay medicamentos cubiertos registrados.'], 'refunds' => ['Reembolsos', 'No hay solicitudes de reembolso registradas.']] as $tab => [$title, $description])
              <section class="patient-insurance-pane" data-insurance-pane="{{ $tab }}"><header><span>▣</span><div><h2>{{ $title }}</h2><p>{{ $description }}</p></div></header><div class="patient-insurance-empty">No registrado</div></section>
            @endforeach
          </div>
          <form class="patient-insurance-edit-form patient-portal-form" method="post" action="{{ route('patient.insurance.save') }}" data-insurance-form hidden>
            @csrf
            <div class="patient-insurance-form-heading"><div><h2>{{ $policy ? 'Actualizar póliza' : 'Registrar póliza' }}</h2><p>Completa los datos disponibles de tu seguro médico.</p></div><button type="button" data-insurance-form-close>×</button></div>
            <div class="patient-portal-form-grid">
              <label>Número de póliza<input required name="policy_number" value="{{ old('policy_number', $policy?->policy_number) }}"></label>
              <label>Aseguradora<input required name="insurer_name" value="{{ old('insurer_name', $policy?->insurer_name) }}"></label>
              <label>Plan<input name="plan_name" value="{{ old('plan_name', $policy?->plan_name) }}"></label>
              <label>Empresa<input name="employer_name" value="{{ old('employer_name', $policy?->employer_name) }}"></label>
              <label>Inicio<input type="date" name="starts_at" value="{{ old('starts_at', $policy?->starts_at?->format('Y-m-d')) }}"></label>
              <label>Fin<input type="date" name="ends_at" value="{{ old('ends_at', $policy?->ends_at?->format('Y-m-d')) }}"></label>
              <label>Estatus<select name="status"><option value="active">Activa</option><option value="pending" @selected($policy?->status === 'pending')>Pendiente</option><option value="inactive" @selected($policy?->status === 'inactive')>Inactiva</option><option value="expired" @selected($policy?->status === 'expired')>Vencida</option></select></label>
            </div>
            <button class="patient-portal-primary" type="submit">Guardar póliza</button>
          </form>
        </article>
      </section>

      <section class="patient-portal-view" data-patient-view="analyses">
        <div class="patient-portal-view-heading"><div><small>EXPEDIENTE DIGITAL</small><h1>Análisis clínicos</h1><p>Resultados y documentos médicos cargados a tu cuenta.</p></div><span>{{ $clinicalAnalyses->count() }} estudios</span></div>
        <div class="patient-portal-table-card"><table><thead><tr><th>Estudio</th><th>Fecha</th><th>Tipo</th><th>Estatus</th><th>Archivo</th></tr></thead><tbody>
          @forelse ($clinicalAnalyses as $document)<tr><td><strong>{{ $document->name }}</strong></td><td>{{ $document->loaded_at?->format('d/m/Y') ?? $document->created_at?->format('d/m/Y') }}</td><td>{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</td><td><span class="patient-portal-status">{{ $statusText($document->status) }}</span></td><td>@if($document->file_path)<a href="{{ asset('storage/'.$document->file_path) }}" target="_blank">Ver documento</a>@else Sin archivo @endif</td></tr>
          @empty <tr><td colspan="5" class="patient-portal-empty">Aún no hay análisis clínicos registrados.</td></tr>@endforelse
        </tbody></table></div>
      </section>

      <section class="patient-portal-view" data-patient-view="history">
        @php
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
            'manual' => 'Registro manual',
            'vaccine' => 'Vacunas',
          ];
        @endphp
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
              <button class="is-vaccine" type="button" data-history-filter="vaccine"><span>□</span>Vacunas</button>
              <button class="is-manual" type="button" data-history-filter="manual"><span>+</span>Registro manual</button>
              <button type="button" data-history-step="1" aria-label="Filtro siguiente">›</button>
            </nav>
            <div class="patient-history-table-wrap">
              <table class="patient-history-table">
                <thead><tr><th>Fecha</th><th>Categoría</th><th>Origen</th><th>Especialidad o tipo de servicio</th><th>Resumen clínico</th><th>Receta / Indicaciones</th></tr></thead>
                <tbody data-history-body>
                  @forelse ($historyItems as $item)
                    <tr data-history-row="{{ $item['category'] }}">
                      <td><div class="patient-history-date"><span>□</span><strong>{{ $item['date']?->format('d/m/Y') ?? 'No registrada' }}<small>{{ $item['date']?->translatedFormat('l') ?? '' }}</small></strong></div></td>
                      <td><span class="patient-history-category is-{{ $item['category'] }}">{{ $historyCategoryLabels[$item['category']] ?? 'Registro clínico' }}</span></td>
                      <td><div class="patient-history-origin"><span>♙</span><strong>{{ $item['origin'] }}</strong></div></td>
                      <td><div class="patient-history-service"><span>⌁</span><strong>{{ $item['service'] }}</strong></div></td>
                      <td>{{ $item['summary'] }}</td>
                      <td>
                        @if(count($item['indications']))
                          <div class="patient-history-indications">
                            <span>▤</span>
                            @foreach($item['indications'] as $indication)
                              <div><strong>{{ $indication['title'] }}</strong><p>{{ $indication['detail'] }}</p></div>
                            @endforeach
                          </div>
                        @else
                          <span class="patient-history-not-registered">No registrado</span>
                        @endif
                      </td>
                    </tr>
                  @empty
                    <tr data-history-empty><td colspan="6" class="patient-portal-empty">No hay registros clínicos.</td></tr>
                  @endforelse
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
                @forelse ($patient->prescriptions->sortByDesc('issued_at') as $prescription)
                  @php
                    $prescriptionMeta = $prescription->metadata ?? [];
                    $isExpiredPrescription = in_array($prescription->status, ['expired', 'inactive'], true)
                      || ($prescriptionMeta['expires_at'] ?? null) && \Illuminate\Support\Carbon::parse($prescriptionMeta['expires_at'])->isPast();
                    $hasAnalysis = !empty($prescriptionMeta['clinical_analysis']) || !empty($prescriptionMeta['clinical_analyses']);
                  @endphp
                  <tr data-prescription-row data-status="{{ $isExpiredPrescription ? 'expired' : 'active' }}" data-has-medications="{{ $prescription->items->isNotEmpty() ? '1' : '0' }}" data-has-analysis="{{ $hasAnalysis ? '1' : '0' }}">
                    <td>{{ $prescription->doctor?->full_name ?? 'Sin médico asignado' }}</td>
                    <td>{{ $prescription->doctor?->specialty ?? 'Medicina general' }}</td>
                    <td>{{ $prescription->issued_at?->format('d/m/Y') ?? 'Sin fecha' }}</td>
                    <td class="patient-prescription-medications">@forelse($prescription->items as $item)<div>{{ $item->medication_name }} @if($item->dose)| {{ $item->dose }} @endif @if($item->metadata['presentation'] ?? null)| {{ $item->metadata['presentation'] }} @endif @if($item->frequency)| {{ $item->frequency }} @endif</div>@empty Sin medicamentos indicados @endforelse</td>
                    <td>{{ $prescriptionMeta['clinical_analysis'] ?? ($hasAnalysis ? 'Análisis relacionados' : '—') }}</td>
                    <td><button class="patient-prescription-view-button" type="button" title="{{ $prescription->code ?? 'Receta médica' }}" data-prescription-open="patient-prescription-{{ $prescription->id }}">Ver</button></td>
                    <td>{{ $prescriptionMeta['hospital'] ?? $prescriptionMeta['medical_unit'] ?? 'Privada' }}</td>
                    <td>{{ $prescriptionMeta['institution'] ?? 'Privada' }}</td>
                  </tr>
                @empty <tr><td colspan="8" class="patient-portal-empty">No hay recetas registradas.</td></tr>@endforelse
                </tbody>
              </table>
            </div>
          </div>
        </article>
        @foreach ($patient->prescriptions->sortByDesc('issued_at') as $prescription)
          @php
            $prescriptionMeta = $prescription->metadata ?? [];
            $hospitalName = $prescriptionMeta['hospital'] ?? $prescriptionMeta['medical_unit'] ?? 'Privada';
            $institutionName = $prescriptionMeta['institution'] ?? 'Privada';
            $pharmacyName = $prescriptionMeta['external_pharmacy'] ?? 'Farmacia externa';
          @endphp
          <dialog class="patient-prescription-dialog" id="patient-prescription-{{ $prescription->id }}" aria-labelledby="patient-prescription-title-{{ $prescription->id }}">
            <header class="patient-prescription-dialog-header">
              <div><small>FORMATO PDF</small><h2 id="patient-prescription-title-{{ $prescription->id }}">Receta canjeable</h2></div>
              <button type="button" data-prescription-close aria-label="Cerrar receta">×</button>
            </header>
            <div class="patient-prescription-paper">
              <div class="patient-prescription-paper-title">
                <div><h3>Formato de Receta Médica</h3><p>{{ $pharmacyName }} - {{ $hospitalName === 'Privada' ? 'Consulta Externa Privada' : $hospitalName }}</p></div>
                <div><small>Folio</small><strong>{{ $prescription->code ?? 'RX-'.$prescription->id }}</strong></div>
              </div>
              <dl class="patient-prescription-detail-grid">
                <div><dt>Paciente</dt><dd>{{ $patient->full_name }}</dd></div>
                <div><dt>ID de paciente</dt><dd>{{ $patient->platform_number ?? $patient->id }}</dd></div>
                <div><dt>Médico tratante</dt><dd>{{ $prescription->doctor?->full_name ?? 'Sin médico asignado' }}</dd></div>
                <div><dt>Especialidad</dt><dd>{{ $prescription->doctor?->specialty ?? 'Medicina general' }}</dd></div>
                <div><dt>Fecha</dt><dd>{{ $prescription->issued_at?->format('d/m/Y') ?? 'Sin fecha' }}</dd></div>
                <div><dt>Hospital</dt><dd>{{ $hospitalName }}</dd></div>
                <div><dt>Institución</dt><dd>{{ $institutionName }}</dd></div>
                <div><dt>Farmacia externa</dt><dd>{{ $pharmacyName }}</dd></div>
                <div><dt>Formato</dt><dd>Formato de Receta Médica</dd></div>
                <div><dt>Canje</dt><dd>{{ $prescriptionMeta['redemption'] ?? 'Canje sujeto a validación de Farmacia externa.' }}</dd></div>
              </dl>
              <div class="patient-prescription-lines">
                @forelse ($prescription->items as $item)
                  <div><small>{{ $item->metadata['quantity'] ?? 1 }} {{ ($item->metadata['quantity'] ?? 1) == 1 ? 'pieza' : 'piezas' }}</small><strong>{{ $item->medication_name }}@if($item->dose) | {{ $item->dose }}@endif @if($item->metadata['presentation'] ?? null)| {{ $item->metadata['presentation'] }}@endif</strong>@if($item->frequency || $item->duration || $item->instructions)<p>{{ collect([$item->frequency, $item->duration, $item->instructions])->filter()->implode(' · ') }}</p>@endif</div>
                @empty
                  <div><strong>Sin medicamentos indicados</strong></div>
                @endforelse
              </div>
              <footer class="patient-prescription-paper-footer">
                <div class="patient-prescription-signature"><span></span><strong>{{ $prescription->doctor?->full_name ?? 'Médico tratante' }}</strong><small>Cédula profesional {{ $prescription->doctor?->professional_license ?? 'sin registrar' }}</small></div>
                <div class="patient-prescription-qr" aria-label="Código para canje"><span></span><small>QR para canje</small></div>
              </footer>
            </div>
          </dialog>
        @endforeach
      </section>

      <section class="patient-portal-view" data-patient-view="calendar">
        @php
          $calendarAppointments = $patient->appointments->sortBy('starts_at')->values();
          $calendarMonths = $calendarAppointments->filter(fn ($item) => $item->starts_at)->map(fn ($item) => ['key' => $item->starts_at->format('Y-m'), 'label' => ucfirst($item->starts_at->translatedFormat('F Y'))])->unique('key')->values();
          $consultationHistory = $patient->clinicalRecords->sortByDesc('recorded_at')->values();
        @endphp
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
              <span><b>☺</b> {{ $calendarAppointments->filter(fn ($item) => $item->starts_at?->isFuture())->count() }} próximas citas</span>
              <strong data-calendar-month-label>{{ $calendarMonths->first()['label'] ?? ucfirst(now()->translatedFormat('F Y')) }}</strong>
              <button type="button" aria-label="Mes siguiente" data-calendar-month-step="1">›</button>
            </div>
          </div>
          <div class="patient-calendar-list">
            @forelse ($calendarAppointments as $appointment)
              @php
                $calendarText = mb_strtolower(collect([$appointment->specialty, $appointment->modality, $appointment->reason])->filter()->implode(' '));
                $calendarType = str_contains($calendarText, 'laborat') || str_contains($calendarText, 'análisis') || str_contains($calendarText, 'muestra') ? 'laboratory' : 'consultation';
                $isUpcoming = $appointment->starts_at?->isFuture() && !in_array($appointment->status, ['cancelled', 'completed'], true);
                $appointmentUnit = $appointment->medicalUnit?->name ?? $appointment->location ?? 'Ubicación no registrada';
                $appointmentTitle = $calendarType === 'laboratory' ? 'Laboratorio' : ($appointment->specialty ?: 'Consulta médica');
              @endphp
              <article class="patient-calendar-card" data-calendar-item data-calendar-type="{{ $calendarType }}" data-calendar-upcoming="{{ $isUpcoming ? '1' : '0' }}" data-calendar-month="{{ $appointment->starts_at?->format('Y-m') }}">
                <div class="patient-calendar-type-icon is-{{ $calendarType }}" aria-hidden="true"><span>{{ $calendarType === 'laboratory' ? '♙' : '♥' }}</span></div>
                <div class="patient-calendar-card-main">
                  <h2>{{ $appointmentTitle }}</h2>
                  <div class="patient-calendar-date"><span>▣</span><strong>{{ $appointment->starts_at?->format('d/m/Y') ?? 'Fecha pendiente' }}</strong><i>•</i><span>◷</span><strong>{{ $appointment->starts_at?->format('H:i') ?? 'Sin hora' }}</strong></div>
                  <p>Con {{ $appointment->doctor?->full_name ?? 'médico por asignar' }}, en {{ $appointmentUnit }}. Motivo: {{ $appointment->reason ?: 'No registrado' }}</p>
                  <div class="patient-calendar-card-meta">
                    <div><small>MÉDICO</small><strong>{{ $appointment->doctor?->full_name ?? 'No registrado' }}</strong></div>
                    <div><small>ESPECIALIDAD</small><strong>{{ $appointment->specialty ?: 'No registrada' }}</strong></div>
                    <div><small>MODALIDAD</small><strong>{{ $appointment->modality ?: ($calendarType === 'laboratory' ? 'Toma de muestra' : 'Consulta presencial') }}</strong></div>
                    <div><small>UBICACIÓN</small><strong>{{ $appointmentUnit }}</strong></div>
                  </div>
                </div>
                <aside class="patient-calendar-card-actions">
                  <span class="patient-calendar-status">{{ $statusText($appointment->status) }}</span>
                  <button type="button" data-calendar-detail-open="patient-calendar-detail-{{ $appointment->id }}">Ver detalle <b>›</b></button>
                  <button type="button" disabled title="La cancelación todavía no está habilitada">Cancelar cita</button>
                </aside>
              </article>
              <dialog class="patient-calendar-dialog" id="patient-calendar-detail-{{ $appointment->id }}">
                <header><div><small>DETALLE DE CITA</small><h2>{{ $appointmentTitle }}</h2></div><button type="button" data-calendar-detail-close aria-label="Cerrar">×</button></header>
                <dl>
                  <div><dt>Fecha y hora</dt><dd>{{ $appointment->starts_at?->format('d/m/Y H:i') ?? 'No registrada' }}</dd></div>
                  <div><dt>Estatus</dt><dd>{{ $statusText($appointment->status) }}</dd></div>
                  <div><dt>Médico</dt><dd>{{ $appointment->doctor?->full_name ?? 'No registrado' }}</dd></div>
                  <div><dt>Especialidad</dt><dd>{{ $appointment->specialty ?: 'No registrada' }}</dd></div>
                  <div><dt>Modalidad</dt><dd>{{ $appointment->modality ?: 'No registrada' }}</dd></div>
                  <div><dt>Ubicación</dt><dd>{{ $appointmentUnit }}</dd></div>
                  <div class="is-wide"><dt>Motivo</dt><dd>{{ $appointment->reason ?: 'No registrado' }}</dd></div>
                </dl>
              </dialog>
            @empty
              <div class="patient-calendar-empty" data-calendar-empty>No hay citas o estudios programados.</div>
            @endforelse
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
                @forelse ($consultationHistory as $record)
                  @php
                    $recordPayload = $record->payload ?? [];
                    $recordMedication = $recordPayload['medications'] ?? $recordPayload['medication'] ?? null;
                    if (is_array($recordMedication)) {
                      $recordMedication = collect($recordMedication)->map(fn ($item) => is_array($item) ? collect($item)->filter()->implode(' · ') : $item)->filter()->implode('; ');
                    }
                  @endphp
                  <tr>
                    <td>{{ $record->recorded_at?->format('d/m/Y') ?? 'No registrada' }}</td><td>{{ $record->doctor?->full_name ?? 'No registrado' }}</td>
                    <td>{{ $recordPayload['specialty'] ?? $recordPayload['service'] ?? 'No registrada' }}</td><td>{{ $recordPayload['consultation_type'] ?? $record->title ?? ucfirst(str_replace('_', ' ', $record->record_type)) }}</td>
                    <td>{{ $recordPayload['reason'] ?? $record->summary ?? 'No registrado' }}</td><td>{{ $recordPayload['diagnosis'] ?? 'No registrado' }}</td>
                    <td>{{ $recordMedication ?: 'Sin receta generada' }}</td><td>{{ $recordPayload['unit'] ?? $recordPayload['location'] ?? 'No registrada' }}</td>
                    <td><span class="patient-calendar-history-status">Atendida</span></td>
                  </tr>
                @empty
                  <tr><td colspan="9" class="patient-portal-empty">No hay consultas anteriores registradas.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section class="patient-portal-view" data-patient-view="doctors">
        <div class="patient-portal-view-heading"><div><small>RED MÉDICA</small><h1>Médicos y terapeutas</h1><p>Consulta los profesionales disponibles en la plataforma.</p></div><span>{{ $doctors->count() }} profesionales</span></div>
        <div class="patient-portal-filters"><input type="search" placeholder="Buscar médico, especialidad o unidad" data-card-search="doctor-grid"></div>
        <div class="patient-portal-doctor-grid" id="doctor-grid">
          @forelse ($doctors as $doctor)<article><div class="patient-portal-avatar">{{ collect(explode(' ', $doctor->full_name))->filter()->take(2)->map(fn($part) => mb_substr($part,0,1))->implode('') }}</div><div><h2>{{ $doctor->full_name }}</h2><strong>{{ $doctor->specialty ?? 'Medicina general' }}</strong><p>{{ $doctor->subspecialty ?? $doctor->service_name ?? 'Atención médica' }}</p><small>{{ $doctor->medicalUnit?->name ?? 'Consulta privada' }}</small></div></article>
          @empty <div class="patient-portal-empty">No hay médicos activos.</div>@endforelse
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
              <button class="is-active" type="button" data-device-filter="all" aria-pressed="true"><span class="patient-device-filter-icon patient-device-filter-menu" aria-hidden="true"></span>Todos</button>
              <button class="is-linked" type="button" data-device-filter="linked" aria-pressed="false"><span class="patient-device-filter-icon patient-device-filter-check" aria-hidden="true"></span>Vinculados</button>
              <button class="is-pending" type="button" data-device-filter="pending" aria-pressed="false"><span class="patient-device-filter-icon patient-device-filter-clock" aria-hidden="true"></span>Pendientes</button>
              <button class="is-vitals" type="button" data-device-filter="vitals" aria-pressed="false"><span class="patient-device-filter-icon patient-device-filter-heart" aria-hidden="true"></span>Signos vitales</button>
              <button class="is-glucose" type="button" data-device-filter="glucose" aria-pressed="false"><span class="patient-device-filter-icon patient-device-filter-drop" aria-hidden="true"></span>Glucosa</button>
            </nav>

            <div class="patient-device-list">
              <article class="patient-device-card is-linked" data-device-card data-device-status="linked" data-device-kind="vitals">
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

              <article class="patient-device-card is-pending" data-device-card data-device-status="pending" data-device-kind="glucose">
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
              <div class="patient-device-empty" data-device-empty hidden>No hay dispositivos para este filtro.</div>
            </div>
          </div>
        </article>
      </section>

      <section class="patient-portal-view" data-patient-view="register">
        <article class="patient-register-panel">
          <div class="patient-register-workspace">
            <section class="patient-register-sheet patient-register-view-sheet" aria-labelledby="patient-register-title">
              <header class="patient-register-heading">
                <button class="patient-register-back" type="button" data-open-view="home" aria-label="Volver">←</button>
                <div>
                  <small>REGISTRO R&Aacute;PIDO</small>
                  <h2 id="patient-register-title">Registrar par&aacute;metro</h2>
                </div>
              </header>

              <section class="patient-register-option-block patient-register-option-block-favorites">
                <div class="patient-register-strip-title">
                  <span>Favoritos</span>
                </div>
                <div class="patient-register-options patient-register-favorite-options" data-register-favorites data-register-carousel aria-label="Parametros favoritos"></div>
              </section>

              <section class="patient-register-option-block">
                <div class="patient-register-strip-title">
                  <span>Todos los par&aacute;metros</span>
                </div>
                <div class="patient-register-options" data-register-options data-register-carousel aria-label="Tipo de parametro"></div>
              </section>

              <form class="patient-register-form" data-register-form>
                <input type="hidden" name="metricType" value="water" data-register-metric-input>
                <div class="patient-register-selected-metric" data-register-selected-panel>
                  <span class="patient-register-selected-icon" data-register-selected-icon aria-hidden="true"></span>
                  <div>
                    <small>Par&aacute;metro seleccionado</small>
                    <h3 data-register-selected-title>Agua</h3>
                    <p data-register-selected-description>Registra tu consumo de agua del d&iacute;a.</p>
                    <span data-register-selected-meta>Registro manual en litros</span>
                  </div>
                  <button type="button" data-register-selected-favorite>Agregar a favoritos</button>
                </div>
                <label data-register-custom-wrap hidden>
                  <span>Nombre del par&aacute;metro</span>
                  <input name="customMetric" placeholder="Ej. Temperatura, dolor, energia">
                </label>
                <label>
                  <span><span data-register-value-label>Valor</span> <em>*</em></span>
                  <div class="patient-register-value-row">
                    <input name="value" type="number" step="0.1" placeholder="Meta sugerida: 2.5 L" required data-register-value>
                    <b data-register-unit>L</b>
                  </div>
                </label>
                <label>
                  <span>Fecha y hora <em>*</em></span>
                  <div class="patient-register-date-row">
                    <i aria-hidden="true">▦</i>
                    <input name="recordedAt" type="datetime-local" required data-register-date>
                    <i aria-hidden="true">▦</i>
                  </div>
                </label>
                <label>
                  <span>Comentario opcional</span>
                  <div class="patient-register-notes-wrap">
                    <textarea name="notes" rows="3" maxlength="200" placeholder="Agrega contexto si lo necesitas" data-register-notes></textarea>
                    <small data-register-notes-count>0/200</small>
                  </div>
                </label>
                <div class="patient-register-attachment">
                  <span>Fotograf&iacute;a o documento <em>(opcional)</em></span>
                  <button class="patient-register-attachment-box" type="button" data-register-attachment-pick aria-label="Agregar fotografia o documento">
                    <span class="patient-register-attachment-button" aria-hidden="true">
                      <svg viewBox="0 0 24 24">
                        <path d="M14.5 4.5 13.2 3h-2.4L9.5 4.5H6.2A2.2 2.2 0 0 0 4 6.7v10.1A2.2 2.2 0 0 0 6.2 19h11.6a2.2 2.2 0 0 0 2.2-2.2V6.7a2.2 2.2 0 0 0-2.2-2.2h-3.3Z"></path>
                        <circle cx="12" cy="12" r="3.4"></circle>
                      </svg>
                    </span>
                    <div>
                      <strong>Agregar evidencia</strong>
                      <small data-register-attachment-name>Sin archivo seleccionado</small>
                    </div>
                    <i aria-hidden="true">›</i>
                  </button>
                  <input type="file" accept="image/*,.pdf" name="attachment" data-register-attachment-input hidden>
                  <section class="patient-register-uploaded-card" data-register-uploaded hidden>
                    <div class="patient-register-uploaded-file">
                      <span class="patient-register-file-type" data-register-file-type>PDF</span>
                      <div>
                        <b>Archivo cargado</b>
                        <strong data-register-file-name>Archivo cargado</strong>
                        <small data-register-file-size></small>
                      </div>
                      <button type="button" data-register-attachment-clear aria-label="Eliminar archivo">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                          <path d="M4 7h16"></path>
                          <path d="M10 11v6"></path>
                          <path d="M14 11v6"></path>
                          <path d="M6 7l1 14h10l1-14"></path>
                          <path d="M9 7V4h6v3"></path>
                        </svg>
                      </button>
                    </div>
                    <div class="patient-register-uploaded-status">
                      <span aria-hidden="true">✓</span>
                      <strong>Documento cargado correctamente</strong>
                      <button type="button" data-register-attachment-change>Cambiar archivo</button>
                    </div>
                  </section>
                  <section class="patient-register-transcript-card" data-register-transcript-card hidden>
                    <header>
                      <div>
                        <strong>Texto transcrito autom&aacute;ticamente</strong>
                        <small>Informaci&oacute;n transcrita del documento</small>
                        <span>IA</span>
                      </div>
                      <button type="button" data-register-transcript-edit>Editar texto</button>
                    </header>
                    <textarea name="attachmentTranscript" readonly data-register-transcript></textarea>
                    <p>La informaci&oacute;n puede contener errores. Revisa el texto antes de guardar.</p>
                  </section>
                  <label class="patient-register-attachment-category" data-register-attachment-category-wrap hidden>
                    <span>Guardar en el historial cl&iacute;nico como <em>*</em></span>
                    <select name="attachmentCategory" data-register-attachment-category>
                      <option value="">Selecciona una categor&iacute;a</option>
                      <option value="consultation">Consulta Medica</option>
                      <option value="laboratory">Analisis de Laboratorio</option>
                      <option value="study">Estudios</option>
                      <option value="prescription">Recetas</option>
                      <option value="hospitalization">Hospitalizacion</option>
                      <option value="manual">Registro Manual</option>
                      <option value="vaccine">Vacunas</option>
                    </select>
                    <small>La categor&iacute;a solo aplica al archivo adjunto y a su transcripci&oacute;n.</small>
                  </label>
                </div>
                <div class="patient-register-safe-note" aria-live="polite">
                  <span aria-hidden="true">⌄</span>
                  <div>
                    <strong>Tu informaci&oacute;n est&aacute; segura</strong>
                    <p>Este registro se guardar&aacute; en tu historial manual y solo t&uacute; podr&aacute;s verlo.</p>
                  </div>
                </div>
                <div class="patient-register-success" data-register-success hidden>
                  Registro guardado. Se agrego a tus datos manuales.
                </div>
                <div class="patient-register-actions">
                  <button class="patient-register-secondary" type="reset">Limpiar</button>
                  <button class="patient-register-primary" type="submit"><span aria-hidden="true">▣</span>Guardar registro</button>
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
  data-storage-key="{{ 'drsam_patient_profile_panel_'.$patient->id }}"
  data-profile-name="{{ e($patient->full_name) }}"
  data-profile-id="{{ e($patient->platform_number ?? (string) $patient->id) }}"
  data-profile-curp="{{ e($patient->curp ?: 'No registrado') }}"
  data-profile-email="{{ e($patient->email ?: 'paciente@demo.drsam.local') }}"
  data-profile-phone="{{ e($patient->phone ?: 'No registrado') }}"
  data-profile-status="{{ e($statusText($patient->status)) }}"
  data-profile-doctor="{{ e($patient->primaryDoctor?->full_name ?? 'Sin asignar') }}"
  data-profile-completed="{{ e($patient->profile_completed_at?->format('d/m/Y') ?? 'Pendiente') }}"
  data-logout-url="{{ route('logout') }}"
  data-login-url="{{ route('login') }}"
  data-csrf-token="{{ csrf_token() }}"
  aria-label="Perfil del paciente"
  aria-hidden="true"
  hidden>
  <div class="patient-profile-scroll">
    <header class="patient-profile-header">
      <button class="patient-profile-avatar-button" type="button" data-profile-view="photo" aria-label="Ver o cambiar foto de perfil">
        <span class="patient-profile-avatar" data-profile-panel-avatar>{{ $initials ?: 'PX' }}</span>
        <span class="patient-profile-avatar-add" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span>
      </button>
      <div class="patient-profile-identity">
        <strong data-profile-panel-name>{{ $patient->full_name }}</strong>
        <span>ID DE USUARIO - <b data-profile-panel-id>{{ $patient->platform_number ?? $patient->id }}</b></span>
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

<script src="{{ asset('js/patient-profile-panel.js') }}?v={{ filemtime(public_path('js/patient-profile-panel.js')) }}"></script>
<script src="{{ asset('js/communities.js') }}?v={{ filemtime(public_path('js/communities.js')) }}"></script>
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
        id: @json($patient->platform_number ?? (string) $patient->id),
        name: @json($patient->full_name),
      },
      storageKey: @json('drsam_patient_communities_'.$patient->id),
      profileStorageKey: @json('drsam_patient_profile_panel_'.$patient->id),
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
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
  const healthScreenLayer = portal.querySelector('[data-health-screen-layer]');
  const healthScreens = [...portal.querySelectorAll('[data-health-screen]')];
  const registerForm = document.querySelector('[data-register-form]');
  const registerOptions = document.querySelector('[data-register-options]');
  const registerFavorites = document.querySelector('[data-register-favorites]');
  const registerMetricInput = document.querySelector('[data-register-metric-input]');
  const registerValueInput = document.querySelector('[data-register-value]');
  const registerValueLabel = document.querySelector('[data-register-value-label]');
  const registerUnit = document.querySelector('[data-register-unit]');
  const registerDateInput = document.querySelector('[data-register-date]');
  const registerCustomWrap = document.querySelector('[data-register-custom-wrap]');
  const registerNotesInput = document.querySelector('[data-register-notes]');
  const registerNotesCount = document.querySelector('[data-register-notes-count]');
  const registerAttachmentPick = document.querySelector('[data-register-attachment-pick]');
  const registerAttachmentInput = document.querySelector('[data-register-attachment-input]');
  const registerAttachmentName = document.querySelector('[data-register-attachment-name]');
  const registerUploadedCard = document.querySelector('[data-register-uploaded]');
  const registerUploadedFileName = document.querySelector('[data-register-file-name]');
  const registerUploadedFileSize = document.querySelector('[data-register-file-size]');
  const registerUploadedFileType = document.querySelector('[data-register-file-type]');
  const registerAttachmentChange = document.querySelector('[data-register-attachment-change]');
  const registerAttachmentClear = document.querySelector('[data-register-attachment-clear]');
  const registerTranscriptCard = document.querySelector('[data-register-transcript-card]');
  const registerTranscriptInput = document.querySelector('[data-register-transcript]');
  const registerTranscriptEdit = document.querySelector('[data-register-transcript-edit]');
  const registerAttachmentCategoryWrap = document.querySelector('[data-register-attachment-category-wrap]');
  const registerAttachmentCategoryInput = document.querySelector('[data-register-attachment-category]');
  const registerSuccess = document.querySelector('[data-register-success]');
  const registerSelectedTitle = document.querySelector('[data-register-selected-title]');
  const registerSelectedIcon = document.querySelector('[data-register-selected-icon]');
  const registerSelectedDescription = document.querySelector('[data-register-selected-description]');
  const registerSelectedMeta = document.querySelector('[data-register-selected-meta]');
  const registerSelectedFavoriteButton = document.querySelector('[data-register-selected-favorite]');
  const historyBody = portal.querySelector('[data-history-body]');
  const historyEmptyRow = portal.querySelector('[data-history-empty]');
  const registerMetricConfig = {
    water: { label: 'Agua', image: '/images/register-icons/water.png', icon: '&#9676;', unit: 'L', inputType: 'number', step: '0.1', placeholder: 'Meta sugerida: 2.5 L' },
    steps: { label: 'Pasos', image: '/images/register-icons/steps.png', icon: '&#8961;', unit: 'pasos', inputType: 'number', step: '1', placeholder: 'Ej. 8000' },
    sleep: { label: 'Sueno', image: '/images/register-icons/sleep.png', icon: '&#9684;', unit: 'h', inputType: 'number', step: '0.1', placeholder: 'Ej. 7.5' },
    weight: { label: 'Peso', image: '/images/register-icons/weight.png', icon: '&#9635;', unit: 'kg', inputType: 'number', step: '0.1', placeholder: 'Ej. 72.4' },
    pressure: { label: 'Presion', image: '/images/register-icons/vitals.png', icon: '&#9825;', unit: 'mmHg', inputType: 'text', step: '', placeholder: 'Ej. 120/80' },
    glucose: { label: 'Glucosa', image: '/images/register-icons/glucose.png', icon: '&#9826;', unit: 'mg/dL', inputType: 'number', step: '0.1', placeholder: 'Ej. 92' },
    mood: { label: 'Animo', image: '/images/register-icons/mood.png', icon: '&#9786;', unit: '/5', inputType: 'number', step: '1', placeholder: 'Ej. 4' },
    medication: { label: 'Medicamentos', image: '/images/register-icons/medication.png', icon: '&#9877;', unit: '', inputType: 'text', step: '', placeholder: 'Ej. Metformina 500 mg' },
    activity: { label: 'Actividad fisica', image: '/images/register-icons/activity.png', icon: '&#10022;', unit: 'min', inputType: 'number', step: '1', placeholder: 'Ej. 45' },
    calories: { label: 'Calorias', image: '/images/register-icons/activity.png', icon: '&#9672;', unit: 'kcal', inputType: 'number', step: '1', placeholder: 'Ej. 1850' },
    temperature: { label: 'Temperatura', image: '/images/register-icons/vitals.png', icon: '&#8451;', unit: 'C', inputType: 'number', step: '0.1', placeholder: 'Ej. 36.7' },
    oxygen: { label: 'Oxigeno', image: '/images/register-icons/vitals.png', icon: '&#9711;', unit: '%', inputType: 'number', step: '1', placeholder: 'Ej. 98' },
    heartRate: { label: 'Frecuencia', image: '/images/register-icons/vitals.png', icon: '&#9825;', unit: 'lpm', inputType: 'number', step: '1', placeholder: 'Ej. 72' },
    pain: { label: 'Dolor', image: '/images/register-icons/vitals.png', icon: '&#9675;', unit: '/10', inputType: 'number', step: '1', placeholder: 'Ej. 3' },
    symptoms: { label: 'Sintomas', image: '/images/register-icons/vitals.png', icon: '&#8942;', unit: '', inputType: 'text', step: '', placeholder: 'Ej. Dolor de cabeza leve' },
    nutrition: { label: 'Nutricion', image: '/images/register-icons/medication.png', icon: '&#9671;', unit: '', inputType: 'text', step: '', placeholder: 'Ej. Desayuno alto en proteina' },
    meditation: { label: 'Meditacion', image: '/images/register-icons/sleep.png', icon: '&#10003;', unit: 'min', inputType: 'number', step: '1', placeholder: 'Ej. 15' },
    note: { label: 'Nota personal', icon: '+', unit: '', inputType: 'text', step: '', placeholder: 'Ej. Me senti con mas energia' },
    custom: { label: 'Personalizado', icon: '+', unit: '', inputType: 'text', step: '', placeholder: 'Ingresa el valor' },
  };
  const registerMetricViewConfig = {
    water: { description: 'Registra tu consumo de agua del dia.', meta: 'Registro manual en litros', valueLabel: 'Litros de agua' },
    steps: { description: 'Captura tus pasos o actividad caminada.', meta: 'Registro manual de movimiento', valueLabel: 'Cantidad de pasos' },
    sleep: { description: 'Anota las horas reales de descanso.', meta: 'Registro manual de sueno', valueLabel: 'Horas de sueno' },
    weight: { description: 'Guarda tu peso corporal actualizado.', meta: 'Registro manual de peso', valueLabel: 'Peso registrado' },
    pressure: { description: 'Registra tu lectura de presion arterial.', meta: 'Formato sugerido: 120/80', valueLabel: 'Presion arterial' },
    glucose: { description: 'Captura tu lectura de glucosa capilar.', meta: 'Registro manual en mg/dL', valueLabel: 'Nivel de glucosa' },
    mood: { description: 'Marca como te sientes hoy.', meta: 'Escala sugerida de 1 a 5', valueLabel: 'Estado de animo' },
    medication: { description: 'Anota medicamentos, dosis o tomas del dia.', meta: 'Registro manual de tratamiento', valueLabel: 'Medicamento o dosis' },
    activity: { description: 'Registra ejercicio, caminata o actividad fisica.', meta: 'Registro manual en minutos', valueLabel: 'Tiempo de actividad' },
    calories: { description: 'Captura una estimacion de calorias consumidas.', meta: 'Registro manual de nutricion', valueLabel: 'Calorias' },
    temperature: { description: 'Guarda una lectura de temperatura corporal.', meta: 'Registro manual en C', valueLabel: 'Temperatura' },
    oxygen: { description: 'Registra tu saturacion de oxigeno.', meta: 'Registro manual en porcentaje', valueLabel: 'Oxigeno en sangre' },
    heartRate: { description: 'Captura tu frecuencia cardiaca.', meta: 'Registro manual en lpm', valueLabel: 'Frecuencia cardiaca' },
    pain: { description: 'Indica el nivel de dolor percibido.', meta: 'Escala sugerida de 0 a 10', valueLabel: 'Nivel de dolor' },
    symptoms: { description: 'Describe sintomas relevantes del dia.', meta: 'Registro manual de sintomas', valueLabel: 'Sintomas' },
    nutrition: { description: 'Anota alimentacion, comidas o adherencia nutricional.', meta: 'Registro manual de nutricion', valueLabel: 'Detalle de nutricion' },
    meditation: { description: 'Captura minutos de meditacion o respiracion.', meta: 'Registro manual de bienestar', valueLabel: 'Minutos de practica' },
    note: { description: 'Guarda una nota personal de salud.', meta: 'Registro manual libre', valueLabel: 'Nota' },
    custom: { description: 'Crea un registro manual con el parametro que necesites.', meta: 'Registro manual personalizado', valueLabel: 'Valor' },
  };
  const registerAttachmentCategoryConfig = {
    consultation: { label: 'Consulta Medica', historyFilter: 'consultation', service: 'Documento de consulta' },
    laboratory: { label: 'Analisis de Laboratorio', historyFilter: 'laboratory', service: 'Resultados de laboratorio' },
    study: { label: 'Estudios', historyFilter: 'study', service: 'Estudio clinico' },
    prescription: { label: 'Recetas', historyFilter: 'prescription', service: 'Documento de receta' },
    hospitalization: { label: 'Hospitalizacion', historyFilter: 'hospitalization', service: 'Documento hospitalario' },
    manual: { label: 'Registro Manual', historyFilter: 'manual', service: 'Adjunto de registro manual' },
    vaccine: { label: 'Vacunas', historyFilter: 'vaccine', service: 'Cartilla o comprobante de vacuna' },
  };
  const registerStorageKey = @json('drsam_patient_manual_records_'.$patient->id);
  const registerFavoriteStorageKey = @json('drsam_patient_register_favorites_'.$patient->id);
  const registerPatientName = @json($patient->full_name);
  const aiTopForm = portal.querySelector('[data-ai-top-form]');
  const aiTopInput = portal.querySelector('[data-ai-top-input]');
  const aiChatPanel = portal.querySelector('[data-ai-chat-panel]');
  const aiChatMessages = portal.querySelector('[data-ai-chat-messages]');
  const aiChatForm = portal.querySelector('[data-ai-chat-form]');
  const aiChatInput = portal.querySelector('[data-ai-chat-input]');
  const aiChatStorageKey = @json('drsam_patient_ai_chat_'.$patient->id);
  const aiQuickActions = ['Consultar estudios', 'Mis medicamentos', 'Laboratorios', 'Recetas', 'Ver historial', 'Programar cita'];
  let aiConversation = [];
  try {
    aiConversation = JSON.parse(localStorage.getItem(aiChatStorageKey) || '[]');
  } catch (error) {
    aiConversation = [];
  }
  const escapeAiText = value => String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  }[char]));
  const aiTimeLabel = () => new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
  const saveAiConversation = () => {
    localStorage.setItem(aiChatStorageKey, JSON.stringify(aiConversation.slice(-40)));
  };
  const normalizeAiPrompt = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  const renderAiCard = card => {
    if (!card || card.type !== 'appointment') return '';
    return `
      <article class="patient-ai-smart-card">
        <small>Consulta próxima</small>
        <strong>${escapeAiText(card.date)}</strong>
        <span>${escapeAiText(card.time)}</span>
        <p>${escapeAiText(card.doctor)}</p>
        <p>${escapeAiText(card.specialty)}</p>
        <p>${escapeAiText(card.location)}</p>
        <div>
          <button type="button" data-ai-open-view="history">Ver expediente</button>
          <button type="button" data-ai-open-view="calendar">Agregar al calendario</button>
        </div>
      </article>
    `;
  };
  const renderAiChips = chips => {
    const items = chips?.length ? chips : aiQuickActions;
    return `<div class="patient-ai-chips">${items.map(item => `<button type="button" data-ai-chip="${escapeAiText(item)}">${escapeAiText(item)}</button>`).join('')}</div>`;
  };
  const renderAiConversation = () => {
    if (!aiChatMessages) return;
    aiChatMessages.innerHTML = aiConversation.map(message => {
      const isUser = message.role === 'user';
      return `
        <article class="patient-ai-message ${isUser ? 'is-user' : 'is-assistant'}">
          ${isUser ? '' : '<span class="patient-ai-avatar" aria-hidden="true">✦✦</span>'}
          <div class="patient-ai-bubble">
            <p>${escapeAiText(message.text)}</p>
            ${renderAiCard(message.card)}
            ${!isUser ? renderAiChips(message.chips) : ''}
          </div>
          <time>${escapeAiText(message.time || '')}</time>
        </article>
      `;
    }).join('');
    aiChatMessages.scrollTop = aiChatMessages.scrollHeight;
  };
  const openAiChat = () => {
    if (!aiChatPanel) return;
    aiChatPanel.hidden = false;
    aiChatPanel.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(() => {
      aiChatPanel.classList.add('is-open');
      renderAiConversation();
      aiChatInput?.focus({ preventScroll: true });
    });
  };
  const closeAiChat = () => {
    if (!aiChatPanel) return;
    if (aiTopInput) aiTopInput.value = '';
    aiChatPanel.classList.remove('is-open');
    aiChatPanel.setAttribute('aria-hidden', 'true');
    window.setTimeout(() => {
      if (!aiChatPanel.classList.contains('is-open')) aiChatPanel.hidden = true;
    }, 280);
  };
  const buildAiResponse = prompt => {
    const normalized = normalizeAiPrompt(prompt);
    if (normalized.includes('consulta') || normalized.includes('cita') || normalized.includes('proxima')) {
      return {
        text: 'Tu próxima consulta médica es el viernes 15 de agosto de 2026 a las 10:00 a. m. con la Dra. Laura Martínez, en Centro Biotecnológico.',
        card: {
          type: 'appointment',
          date: 'Viernes 15 de agosto de 2026',
          time: '10:00 a. m.',
          doctor: 'Dra. Laura Martínez',
          specialty: 'Medicina Interna',
          location: 'Centro Biotecnológico',
        },
        chips: ['Ver historial', 'Programar cita', 'Mis medicamentos'],
      };
    }
    if (normalized.includes('receta') || normalized.includes('medicamento')) {
      return {
        text: 'Puedo ayudarte a revisar tus recetas y medicamentos activos. También puedo llevarte al historial para ver indicaciones anteriores.',
        chips: ['Recetas', 'Mis medicamentos', 'Ver historial'],
      };
    }
    if (normalized.includes('estudio') || normalized.includes('laboratorio')) {
      return {
        text: 'Tus estudios y laboratorios se consultan desde el expediente. Puedo ayudarte a filtrar los registros clínicos o abrir análisis clínicos.',
        chips: ['Consultar estudios', 'Laboratorios', 'Ver historial'],
      };
    }
    if (normalized.includes('historial') || normalized.includes('expediente')) {
      return {
        text: 'Tu historial clínico reúne consultas, estudios, recetas, hospitalizaciones y registros manuales.',
        chips: ['Ver historial', 'Recetas', 'Laboratorios'],
      };
    }
    return {
      text: 'Listo. Te puedo ayudar con consultas, recetas, estudios, dispositivos, pagos, comunidades o registros manuales.',
      chips: aiQuickActions,
    };
  };
  const sendAiMessage = (value, options = {}) => {
    const text = String(value || '').trim();
    if (!text) return;
    aiConversation.push({ role: 'user', text, time: aiTimeLabel() });
    saveAiConversation();
    openAiChat();
    renderAiConversation();
    if (options.clearTop !== false && aiTopInput) aiTopInput.value = text;
    if (aiChatInput) aiChatInput.value = '';
    window.setTimeout(() => {
      const response = buildAiResponse(text);
      aiConversation.push({ role: 'assistant', text: response.text, card: response.card, chips: response.chips, time: aiTimeLabel() });
      saveAiConversation();
      renderAiConversation();
    }, 260);
  };
  const registerDateValue = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
  };
  const registerMetricOrder = Object.keys(registerMetricConfig);
  const defaultRegisterFavorites = ['water', 'steps', 'sleep', 'medication', 'activity'];
  const escapeRegisterText = value => String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  }[char]));
  const normalizeRegisterFavorites = values => [...new Set((Array.isArray(values) ? values : []).filter(type => registerMetricConfig[type]))];
  const loadRegisterFavorites = () => {
    try {
      const saved = JSON.parse(localStorage.getItem(registerFavoriteStorageKey) || 'null');
      const normalized = normalizeRegisterFavorites(saved);
      return normalized.length ? normalized : defaultRegisterFavorites;
    } catch (error) {
      return defaultRegisterFavorites;
    }
  };
  let registerFavoriteMetrics = loadRegisterFavorites();
  const saveRegisterFavorites = () => {
    localStorage.setItem(registerFavoriteStorageKey, JSON.stringify(registerFavoriteMetrics));
  };
  const renderRegisterMetricButton = (type) => {
    const config = registerMetricConfig[type] || registerMetricConfig.water;
    const active = (registerMetricInput?.value || 'water') === type;
    const favorite = registerFavoriteMetrics.includes(type);
    const favoriteLabel = favorite ? 'Quitar de favoritos' : 'Agregar a favoritos';
    const iconMarkup = config.image
      ? `<span class="patient-register-metric-image"><img src="${escapeRegisterText(config.image)}" alt="" loading="lazy"></span>`
      : `<span class="patient-register-metric-glyph">${config.icon || '+'}</span>`;
    return `
      <button class="${active ? 'is-active' : ''}" type="button" data-register-metric="${escapeRegisterText(type)}" aria-pressed="${active ? 'true' : 'false'}">
        <i class="patient-register-favorite-toggle ${favorite ? 'is-favorite' : ''}" data-register-favorite-toggle data-register-metric-favorite="${escapeRegisterText(type)}" role="button" tabindex="0" aria-label="${favoriteLabel}" aria-pressed="${favorite ? 'true' : 'false'}">${favorite ? '&#9733;' : '&#9734;'}</i>
        ${iconMarkup}
        <b>${escapeRegisterText(config.label)}</b>
      </button>
    `;
  };
  const renderRegisterSelectedIcon = config => {
    if (config?.image) {
      return `<img src="${escapeRegisterText(config.image)}" alt="">`;
    }
    return `<span>${config?.icon || '+'}</span>`;
  };
  const syncRegisterActiveButtons = () => {
    const current = registerMetricInput?.value || 'water';
    portal.querySelectorAll('[data-register-metric]').forEach(button => {
      const active = button.dataset.registerMetric === current;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    const currentConfig = registerMetricConfig[current] || registerMetricConfig.water;
    const currentView = registerMetricViewConfig[current] || registerMetricViewConfig.water;
    const isFavorite = registerFavoriteMetrics.includes(current);
    if (registerSelectedTitle) registerSelectedTitle.textContent = currentConfig.label;
    if (registerSelectedDescription) registerSelectedDescription.textContent = currentView.description || '';
    if (registerSelectedMeta) registerSelectedMeta.textContent = currentView.meta || 'Registro manual';
    if (registerSelectedIcon) registerSelectedIcon.innerHTML = renderRegisterSelectedIcon(currentConfig);
    registerSelectedIcon?.classList.toggle('has-image', Boolean(currentConfig.image));
    if (registerSelectedFavoriteButton) {
      registerSelectedFavoriteButton.textContent = isFavorite ? 'En favoritos' : 'Agregar a favoritos';
      registerSelectedFavoriteButton.classList.toggle('is-added', isFavorite);
      registerSelectedFavoriteButton.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
    }
  };
  const renderRegisterMetricRows = () => {
    if (registerFavorites) {
      registerFavorites.innerHTML = registerFavoriteMetrics.length
        ? registerFavoriteMetrics.map(renderRegisterMetricButton).join('')
        : '<div class="patient-register-favorites-empty">Marca la estrella de un par&aacute;metro para verlo aqu&iacute;.</div>';
    }
    if (registerOptions) {
      registerOptions.innerHTML = registerMetricOrder.map(renderRegisterMetricButton).join('');
    }
    syncRegisterActiveButtons();
  };
  const toggleRegisterFavorite = (type) => {
    if (!registerMetricConfig[type]) return;
    registerFavoriteMetrics = registerFavoriteMetrics.includes(type)
      ? registerFavoriteMetrics.filter(item => item !== type)
      : [...registerFavoriteMetrics, type];
    saveRegisterFavorites();
    renderRegisterMetricRows();
  };
  const addRegisterFavorite = (type) => {
    if (!registerMetricConfig[type] || registerFavoriteMetrics.includes(type)) {
      syncRegisterActiveButtons();
      return;
    }
    registerFavoriteMetrics = [...registerFavoriteMetrics, type];
    saveRegisterFavorites();
    renderRegisterMetricRows();
  };
  const selectRegisterMetric = (type) => {
    const config = registerMetricConfig[type] || registerMetricConfig.water;
    const view = registerMetricViewConfig[type] || registerMetricViewConfig.water;
    if (registerMetricInput) registerMetricInput.value = type;
    if (registerValueLabel) registerValueLabel.textContent = view.valueLabel || (type === 'note' ? 'Nota' : 'Valor');
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
    syncRegisterActiveButtons();
  };
  const saveRegisterRecord = (record) => {
    let records = [];
    try {
      records = JSON.parse(localStorage.getItem(registerStorageKey) || '[]');
    } catch (error) {
      records = [];
    }
    records.unshift(record);
    localStorage.setItem(registerStorageKey, JSON.stringify(records));
  };
  const updateRegisterNotesCount = () => {
    if (!registerNotesInput || !registerNotesCount) return;
    registerNotesCount.textContent = `${registerNotesInput.value.length}/200`;
  };
  const formatRegisterFileSize = bytes => {
    const size = Number(bytes || 0);
    if (size >= 1024 * 1024) return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    if (size >= 1024) return `${Math.max(1, Math.round(size / 1024))} KB`;
    return `${size} B`;
  };
  const buildRegisterTranscript = file => {
    const fileName = file?.name || 'Documento adjunto';
    const lowerName = fileName.toLowerCase();
    const loadedAt = new Date().toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
    if (lowerName.includes('lab') || lowerName.includes('analisis') || lowerName.includes('laboratorio')) {
      return [
        'Laboratorio: Centro Biotecnologico',
        `Fecha del estudio: ${loadedAt}`,
        `Paciente: ${registerPatientName}`,
        '',
        'Resultados:',
        '- Glucosa en ayunas: 92 mg/dL',
        '- Hemoglobina glicosilada: 5.4%',
        '- Colesterol total: 168 mg/dL',
        '- Trigliceridos: 120 mg/dL',
        '- HDL: 48 mg/dL',
        '- LDL: 96 mg/dL',
      ].join('\n');
    }
    if (lowerName.includes('receta') || lowerName.includes('prescripcion')) {
      return [
        'Documento de receta medica',
        `Fecha de carga: ${loadedAt}`,
        `Paciente: ${registerPatientName}`,
        '',
        'Indicaciones detectadas:',
        '- Medicamento y dosis pendientes de confirmar.',
        '- Frecuencia pendiente de revisar.',
        '- Duracion pendiente de revisar.',
      ].join('\n');
    }
    return [
      'Texto transcrito automaticamente',
      `Archivo: ${fileName}`,
      `Fecha de carga: ${loadedAt}`,
      `Paciente: ${registerPatientName}`,
      '',
      'Contenido detectado:',
      '- Documento adjunto al registro manual.',
      '- Revisa y edita esta transcripcion antes de guardar.',
    ].join('\n');
  };
  const setRegisterTranscriptEditing = editing => {
    if (!registerTranscriptInput || !registerTranscriptEdit) return;
    registerTranscriptInput.readOnly = !editing;
    registerTranscriptCard?.classList.toggle('is-editing', editing);
    registerTranscriptEdit.textContent = editing ? 'Guardar texto' : 'Editar texto';
    if (editing) registerTranscriptInput.focus({ preventScroll: true });
  };
  const setRegisterAttachmentState = file => {
    const hasFile = Boolean(file);
    if (registerAttachmentPick) registerAttachmentPick.hidden = hasFile;
    if (registerUploadedCard) registerUploadedCard.hidden = !hasFile;
    if (registerTranscriptCard) registerTranscriptCard.hidden = !hasFile;
    if (registerAttachmentCategoryWrap) registerAttachmentCategoryWrap.hidden = !hasFile;
    if (registerAttachmentCategoryInput) {
      registerAttachmentCategoryInput.required = hasFile;
      if (!hasFile) registerAttachmentCategoryInput.value = '';
      registerAttachmentCategoryInput.setCustomValidity('');
    }
    if (!hasFile) {
      if (registerAttachmentName) registerAttachmentName.textContent = 'Sin archivo seleccionado';
      if (registerTranscriptInput) registerTranscriptInput.value = '';
      setRegisterTranscriptEditing(false);
      return;
    }
    const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
    if (registerAttachmentName) registerAttachmentName.textContent = file.name;
    if (registerUploadedFileName) registerUploadedFileName.textContent = file.name;
    if (registerUploadedFileSize) registerUploadedFileSize.textContent = formatRegisterFileSize(file.size);
    if (registerUploadedFileType) {
      registerUploadedFileType.textContent = isPdf ? 'PDF' : 'IMG';
      registerUploadedFileType.classList.toggle('is-image', !isPdf);
    }
    if (registerTranscriptInput) registerTranscriptInput.value = buildRegisterTranscript(file);
    setRegisterTranscriptEditing(false);
  };
  const escapeManualHistoryText = value => String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  }[char]));
  const formatManualHistoryDate = value => {
    if (!value) return { date: 'No registrada', day: '' };
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return { date: value, day: '' };
    return {
      date: parsed.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' }),
      day: parsed.toLocaleDateString('es-MX', { weekday: 'long' }),
    };
  };
  const renderManualHistoryRecords = () => {
    if (!historyBody) return;
    historyBody.querySelectorAll('[data-history-manual-row]').forEach(row => row.remove());
    let records = [];
    try {
      records = JSON.parse(localStorage.getItem(registerStorageKey) || '[]');
    } catch (error) {
      records = [];
    }
    const fragment = document.createDocumentFragment();
    records.forEach(record => {
      const date = formatManualHistoryDate(record.recordedAt);
      const label = escapeManualHistoryText(record.label || 'Parametro manual');
      const unit = record.unit ? ` ${escapeManualHistoryText(record.unit)}` : '';
      const value = escapeManualHistoryText(record.value || 'Sin valor');
      const notes = String(record.notes || '').trim();
      const attachment = String(record.attachmentName || '').trim();
      const row = document.createElement('tr');
      row.dataset.historyRow = 'manual';
      row.dataset.historyManualRow = '1';
      row.innerHTML = `
        <td><div class="patient-history-date"><span>+</span><strong>${escapeManualHistoryText(date.date)}<small>${escapeManualHistoryText(date.day)}</small></strong></div></td>
        <td><span class="patient-history-category is-manual">Registro manual</span></td>
        <td><div class="patient-history-origin"><span>◇</span><strong>Paciente</strong></div></td>
        <td><div class="patient-history-service"><span>▣</span><strong>${label}</strong></div></td>
        <td><strong>${label}: ${value}${unit}</strong>${notes ? `<p class="patient-history-manual-note">${escapeManualHistoryText(notes)}</p>` : ''}</td>
        <td><span class="patient-history-not-registered">No registrado</span></td>
      `;
      fragment.appendChild(row);
      if (attachment) {
        const categoryKey = record.attachmentCategory || 'manual';
        const category = registerAttachmentCategoryConfig[categoryKey] || registerAttachmentCategoryConfig.manual;
        const transcript = String(record.attachmentTranscript || '').trim();
        const attachmentRow = document.createElement('tr');
        attachmentRow.dataset.historyRow = category.historyFilter;
        attachmentRow.dataset.historyManualRow = '1';
        attachmentRow.innerHTML = `
          <td><div class="patient-history-date"><span>+</span><strong>${escapeManualHistoryText(date.date)}<small>${escapeManualHistoryText(date.day)}</small></strong></div></td>
          <td><span class="patient-history-category is-${escapeManualHistoryText(category.historyFilter)}">${escapeManualHistoryText(record.attachmentCategoryLabel || category.label)}</span></td>
          <td><div class="patient-history-origin"><span>◇</span><strong>Registro manual</strong></div></td>
          <td><div class="patient-history-service"><span>▣</span><strong>${escapeManualHistoryText(category.service)}</strong></div></td>
          <td><strong>${escapeManualHistoryText(attachment)}</strong>${transcript ? `<p class="patient-history-manual-note patient-history-manual-transcript">${escapeManualHistoryText(transcript)}</p>` : ''}</td>
          <td><div class="patient-history-manual-evidence"><span>▣</span><div><strong>Archivo cargado</strong><p>${escapeManualHistoryText(attachment)}</p><small>Transcripcion automatica revisable.</small></div></div></td>
        `;
        fragment.appendChild(attachmentRow);
      }
    });
    historyBody.prepend(fragment);
    if (historyEmptyRow) historyEmptyRow.hidden = historyBody.querySelectorAll('[data-history-row]').length > 0;
    const activeFilter = portal.querySelector('[data-history-filter].is-active')?.dataset.historyFilter || 'all';
    historyBody.querySelectorAll('[data-history-manual-row]').forEach(row => {
      row.hidden = activeFilter !== 'all' && row.dataset.historyRow !== activeFilter;
    });
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
  const bindRegisterCarousel = (track) => {
    if (!track) return;
    let isRegisterPanning = false;
    let registerPanStart = 0;
    let registerPanScroll = 0;
    let registerPanMoved = false;
    let registerTapHandled = false;
    let registerPointerMetric = '';
    track.addEventListener('pointerdown', event => {
      if (event.target.closest('[data-register-favorite-toggle]')) return;
      const button = event.target.closest('[data-register-metric]');
      isRegisterPanning = true;
      registerPanStart = event.clientX;
      registerPanScroll = track.scrollLeft;
      registerPanMoved = false;
      registerTapHandled = false;
      registerPointerMetric = button && track.contains(button) ? button.dataset.registerMetric : '';
      track.setPointerCapture?.(event.pointerId);
    });
    track.addEventListener('pointermove', event => {
      if (!isRegisterPanning) return;
      const movement = event.clientX - registerPanStart;
      track.scrollLeft = registerPanScroll - movement;
      if (Math.abs(movement) > 8) {
        registerPanMoved = true;
        track.classList.add('is-panning');
      }
      if (registerPanMoved) event.preventDefault();
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(type => {
      track.addEventListener(type, event => {
        if (event.target.closest('[data-register-favorite-toggle]')) {
          isRegisterPanning = false;
          track.classList.remove('is-panning');
          registerPointerMetric = '';
          return;
        }
        if (type === 'pointerup' && !registerPanMoved && registerPointerMetric) {
          selectRegisterMetric(registerPointerMetric);
          registerTapHandled = true;
        }
        isRegisterPanning = false;
        track.classList.remove('is-panning');
        registerPointerMetric = '';
        if (track.hasPointerCapture?.(event.pointerId)) track.releasePointerCapture(event.pointerId);
      });
    });
    track.addEventListener('click', event => {
      const favoriteToggle = event.target.closest('[data-register-favorite-toggle]');
      if (favoriteToggle && track.contains(favoriteToggle)) {
        event.preventDefault();
        event.stopPropagation();
        toggleRegisterFavorite(favoriteToggle.dataset.registerMetricFavorite);
        return;
      }
      const button = event.target.closest('[data-register-metric]');
      if (!button || !track.contains(button)) return;
      if (registerTapHandled) {
        registerTapHandled = false;
        return;
      }
      if (registerPanMoved) {
        event.preventDefault();
        registerPanMoved = false;
        return;
      }
      selectRegisterMetric(button.dataset.registerMetric);
    });
    track.addEventListener('keydown', event => {
      const favoriteToggle = event.target.closest('[data-register-favorite-toggle]');
      if (!favoriteToggle || !track.contains(favoriteToggle)) return;
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      toggleRegisterFavorite(favoriteToggle.dataset.registerMetricFavorite);
    });
  };
  renderRegisterMetricRows();
  portal.querySelectorAll('[data-register-carousel]').forEach(bindRegisterCarousel);
  registerSelectedFavoriteButton?.addEventListener('click', () => {
    addRegisterFavorite(registerMetricInput?.value || 'water');
  });
  registerAttachmentInput?.addEventListener('change', () => {
    const file = registerAttachmentInput.files && registerAttachmentInput.files[0];
    setRegisterAttachmentState(file || null);
  });
  registerNotesInput?.addEventListener('input', updateRegisterNotesCount);
  registerAttachmentPick?.addEventListener('click', () => {
    registerAttachmentInput?.click();
  });
  registerAttachmentChange?.addEventListener('click', () => {
    registerAttachmentInput?.click();
  });
  registerAttachmentClear?.addEventListener('click', () => {
    if (registerAttachmentInput) registerAttachmentInput.value = '';
    setRegisterAttachmentState(null);
  });
  registerTranscriptEdit?.addEventListener('click', () => {
    setRegisterTranscriptEditing(Boolean(registerTranscriptInput?.readOnly));
  });
  registerAttachmentCategoryInput?.addEventListener('change', () => {
    registerAttachmentCategoryInput.setCustomValidity('');
  });
  registerForm?.addEventListener('submit', event => {
    event.preventDefault();
    if (!registerForm.reportValidity()) return;
    const data = new FormData(registerForm);
    const metricType = String(data.get('metricType') || 'water');
    const config = registerMetricConfig[metricType] || registerMetricConfig.water;
    const customMetric = String(data.get('customMetric') || '').trim();
    const attachment = registerAttachmentInput?.files?.[0];
    const attachmentCategoryKey = attachment ? String(data.get('attachmentCategory') || '') : '';
    if (attachment && !attachmentCategoryKey) {
      registerAttachmentCategoryInput?.setCustomValidity('Selecciona una categoria para guardar el adjunto.');
      registerAttachmentCategoryInput?.reportValidity();
      return;
    }
    registerAttachmentCategoryInput?.setCustomValidity('');
    const attachmentCategory = registerAttachmentCategoryConfig[attachmentCategoryKey] || null;
    saveRegisterRecord({
      id: Date.now(),
      metricType,
      label: metricType === 'custom' && customMetric ? customMetric : config.label,
      value: String(data.get('value') || '').trim(),
      unit: config.unit,
      recordedAt: String(data.get('recordedAt') || ''),
      notes: String(data.get('notes') || '').trim(),
      attachmentName: attachment ? attachment.name : '',
      attachmentSize: attachment ? attachment.size : 0,
      attachmentTranscript: attachment ? String(data.get('attachmentTranscript') || '').trim() : '',
      attachmentCategory: attachmentCategory ? attachmentCategory.historyFilter : '',
      attachmentCategoryLabel: attachmentCategory ? attachmentCategory.label : '',
      source: 'Registro manual',
    });
    renderManualHistoryRecords();
    if (registerSuccess) registerSuccess.hidden = false;
    registerForm.reset();
    if (registerDateInput) registerDateInput.value = registerDateValue();
    updateRegisterNotesCount();
    setRegisterAttachmentState(null);
    selectRegisterMetric(metricType);
  });
  registerForm?.addEventListener('reset', () => {
    setTimeout(() => {
      if (registerDateInput) registerDateInput.value = registerDateValue();
      updateRegisterNotesCount();
      setRegisterAttachmentState(null);
      if (registerSuccess) registerSuccess.hidden = true;
      selectRegisterMetric(registerMetricInput?.value || 'water');
    });
  });
  updateRegisterNotesCount();
  setRegisterAttachmentState(null);
  aiTopForm?.addEventListener('submit', event => {
    event.preventDefault();
    sendAiMessage(aiTopInput?.value, { clearTop: false });
  });
  aiChatForm?.addEventListener('submit', event => {
    event.preventDefault();
    sendAiMessage(aiChatInput?.value);
  });
  aiChatPanel?.addEventListener('click', event => {
    if (event.target.closest('[data-ai-chat-close]')) {
      closeAiChat();
      return;
    }
    const chip = event.target.closest('[data-ai-chip]');
    if (chip) {
      sendAiMessage(chip.dataset.aiChip);
      return;
    }
    const viewAction = event.target.closest('[data-ai-open-view]');
    if (viewAction) {
      openView(viewAction.dataset.aiOpenView);
    }
  });
  renderAiConversation();
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
  const initial = @json($errors->any() ? (old('policy_number') ? 'insurance' : 'profile') : null) || sessionStorage.getItem('patientPortalView') || 'home';
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
  renderManualHistoryRecords();
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
  const calendarMonths = @json($calendarMonths ?? collect());
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
@endsection
