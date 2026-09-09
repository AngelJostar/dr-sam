@extends('layouts.app', ['title' => 'Seguros GMM'])

@section('body_class', 'insurance-advisor-native-body')

@php
  $statusLabels = [
    'active' => 'Vigente',
    'inactive' => 'Inactiva',
    'expired' => 'Vencida',
    'cancelled' => 'Cancelada',
    'pending' => 'Pendiente',
    'in_route' => 'En ruta',
    'rescheduled' => 'Reprogramada',
    'delivered' => 'Entregada',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $syncLabels = ['not_synced' => 'No', 'pending' => 'Pendiente', 'approved' => 'Si', 'synced' => 'Si'];
  $syncText = fn (?string $value) => $syncLabels[$value ?? 'not_synced'] ?? 'No';
  $syncClass = fn (?string $value) => in_array($value, ['approved', 'synced'], true) ? 'is-ok' : ($value === 'pending' ? 'is-warning' : 'is-danger');
  $section = $section ?? 'home';
  $selectedPatient = $selectedPolicy?->patient;
  $totalPolicies = $allPolicies->count();
  $activePoliciesCount = $activePolicies->count();
  $expiredPoliciesCount = $expiredPolicies->count();
  $dueSoonPoliciesCount = $dueSoonPolicies->count();
  $claimsCount = $claims->count();
  $sectionRoute = fn (string $target) => route('insurance-advisor.dashboard', $target === 'home' ? [] : ['section' => $target]);
  $menu = [
    ['key' => 'home', 'icon' => 'H', 'label' => 'Home'],
    ['key' => 'all', 'icon' => 'L', 'label' => 'Todas'],
    ['key' => 'due', 'icon' => '!', 'label' => 'Por vencer'],
    ['key' => 'expired', 'icon' => 'A', 'label' => 'Vencidas'],
    ['key' => 'messages', 'icon' => 'M', 'label' => 'Mensajes'],
    ['key' => 'alerts', 'icon' => 'N', 'label' => 'Alertas'],
    ['key' => 'claims', 'icon' => 'S', 'label' => 'Siniestros'],
  ];
  $tabs = [
    'all' => 'Todas',
    'due' => 'Por vencer',
    'expired' => 'Vencidas',
    'messages' => 'Mensajes',
    'alerts' => 'Alertas',
    'claims' => 'Siniestros',
  ];
@endphp

@section('content')
  <div class="insurance-advisor-native-screen">
    <aside class="insurance-advisor-native-sidebar" aria-label="Navegacion asesor seguros">
      <div class="insurance-advisor-native-brand">
        <span>OK</span>
        <div>
          <strong>Seguros GMM</strong>
          <small>Asesor de polizas</small>
        </div>
      </div>

      <nav class="insurance-advisor-native-menu">
        @foreach ($menu as $item)
          <a class="{{ $section === $item['key'] ? 'is-active' : '' }}" href="{{ $sectionRoute($item['key']) }}">
            <span>{{ $item['icon'] }}</span>{{ $item['label'] }}
          </a>
        @endforeach
      </nav>

      <a class="insurance-advisor-native-back" href="{{ route('dashboard') }}">Regresar al acceso</a>
    </aside>

    <section class="insurance-advisor-native-workspace">
      @if (session('status'))
        <div class="notice success">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="notice danger">
          <strong>No se pudo actualizar.</strong>
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <header class="insurance-advisor-native-topbar">
        <div class="insurance-advisor-native-user">
          <span>DS</span>
          <div>
            <strong>Dr. Sam</strong>
            <small>Asesor de seguros GMM</small>
          </div>
        </div>
        <div class="insurance-advisor-native-actions">
          <strong>{{ auth()->user()?->name ?? 'Superadministrador' }}</strong>
          <a href="{{ route('dashboard') }}">Cambiar usuario</a>
        </div>
      </header>

      <section class="insurance-advisor-native-hero">
        <div>
          <p class="eyebrow">Gastos medicos mayores</p>
          <h1>Cartera de polizas y siniestros</h1>
          <p>Gestiona y da seguimiento a tus polizas, renovaciones y siniestros desde un solo lugar.</p>
        </div>
        <div class="insurance-advisor-native-hero-actions">
          <form method="post" action="{{ route('insurance-advisor.alerts.sync') }}">
            @csrf
            <button type="submit">Sincronizar alertas</button>
          </form>
        </div>
      </section>

      <section class="insurance-advisor-native-metrics" aria-label="Resumen de seguros GMM">
        <article>
          <span>P</span>
          <small>Polizas</small>
          <strong>{{ number_format($totalPolicies) }}</strong>
          <p>Cartera asignada</p>
        </article>
        <article>
          <span>OK</span>
          <small>Vigentes</small>
          <strong>{{ number_format($activePoliciesCount) }}</strong>
          <p>Polizas activas</p>
        </article>
        <article>
          <span>!</span>
          <small>Por vencer</small>
          <strong>{{ number_format($dueSoonPoliciesCount) }}</strong>
          <p>Dos meses o menos</p>
        </article>
        <article>
          <span>!</span>
          <small>Vencidas</small>
          <strong>{{ number_format($expiredPoliciesCount) }}</strong>
          <p>Polizas fuera de vigencia</p>
        </article>
        <article>
          <span>S</span>
          <small>Siniestros</small>
          <strong>{{ number_format($claimsCount) }}</strong>
          <p>3 docs pendientes</p>
        </article>
      </section>

      <div class="insurance-advisor-native-tabs">
        @foreach ($tabs as $key => $label)
          <a class="{{ ($section === 'home' ? 'all' : $section) === $key ? 'is-active' : '' }}" href="{{ $sectionRoute($key) }}">{{ $label }}</a>
        @endforeach
      </div>

      @if (in_array($section, ['home', 'all'], true))
        <section class="insurance-advisor-native-grid">
          <article class="insurance-advisor-native-table-card">
            <div class="insurance-advisor-native-heading">
              <div>
                <h2>Listado de polizas</h2>
                <p>{{ $totalPolicies }} registros</p>
              </div>
              <div class="insurance-advisor-native-heading-actions">
                <details class="insurance-advisor-native-sync-bulk">
                  <summary>Sincronizacion</summary>
                  <form method="post" action="{{ route('insurance-advisor.policies.sync.bulk') }}">
                    @csrf
                    @foreach (['search', 'status', 'insurer', 'sort'] as $filter)
                      @if (request()->filled($filter))<input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">@endif
                    @endforeach
                    <button name="target" value="no">Enviar a no sincronizados</button>
                    <button name="target" value="pending">Reenviar pendientes</button>
                    <button name="target" value="both">Enviar ambas</button>
                  </form>
                </details>
                <a href="{{ route('insurance-advisor.policies.export', request()->only(['search', 'status', 'insurer', 'sort'])) }}">Descargar Datos</a>
              </div>
            </div>

            <form class="insurance-advisor-native-filters" method="get">
              @if ($section !== 'home')
                <input type="hidden" name="section" value="{{ $section }}">
              @endif
              <label>Buscar<input name="search" value="{{ request('search') }}" placeholder="Paciente, poliza o aseguradora"></label>
              <label>Estatus
                <select name="status" onchange="this.form.submit()">
                  <option value="all">Todos</option>
                  <option value="active" @selected(request('status') === 'active')>Vigentes</option>
                  <option value="due" @selected(request('status') === 'due')>Por vencer</option>
                  <option value="expired" @selected(request('status') === 'expired')>Vencidas</option>
                </select>
              </label>
              <label>Aseguradora
                <select name="insurer" onchange="this.form.submit()">
                  <option value="all">Todas</option>
                  @foreach ($insurers as $insurer)
                    <option value="{{ $insurer }}" @selected(request('insurer') === $insurer)>{{ $insurer }}</option>
                  @endforeach
                </select>
              </label>
              <label>Ordenar
                <select name="sort" onchange="this.form.submit()">
                  <option value="expiration">Vencimiento cercano</option>
                  <option value="patient" @selected(request('sort') === 'patient')>Paciente</option>
                  <option value="recent" @selected(request('sort') === 'recent')>Registro reciente</option>
                </select>
              </label>
              <button type="submit" class="insurance-advisor-native-filter-submit">Buscar</button>
            </form>

            <div class="insurance-advisor-native-table-scroll">
              <table class="insurance-advisor-native-table">
                <thead>
                  <tr>
                    <th>Poliza</th>
                    <th>Asegurado</th>
                    <th>Aseguradora</th>
                    <th>Producto</th>
                    <th>Vigencia</th>
                    <th>Vencimiento</th>
                    <th>Prima</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                    <th>Sincronizacion</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($policies as $policy)
                    @php
                      $isExpired = $policy->status === 'expired' || ($policy->ends_at && $policy->ends_at->isPast());
                      $daysUntilExpiration = $policy->ends_at ? now()->startOfDay()->diffInDays($policy->ends_at->copy()->startOfDay(), false) : null;
                      $isDue = ! $isExpired && $daysUntilExpiration !== null && $daysUntilExpiration <= 60;
                      $policySyncStatus = data_get($policy->metadata, 'doctor_sync.status', 'not_synced');
                    @endphp
                    <tr class="{{ $selectedPolicy?->is($policy) ? 'is-selected' : ($isExpired ? 'is-expired' : ($isDue ? 'is-due' : '')) }}">
                      <td>
                        <a class="insurance-advisor-native-policy-link" href="{{ route('insurance-advisor.dashboard', array_filter(['section' => $section === 'home' ? null : $section, 'policy' => $policy->id, 'search' => request('search'), 'status' => request('status'), 'insurer' => request('insurer'), 'sort' => request('sort')])) }}">{{ $policy->policy_number }}</a>
                        <span>{{ $policy->metadata['external_id'] ?? 'POL-GMM-'.str_pad((string) $policy->id, 4, '0', STR_PAD_LEFT) }}</span>
                      </td>
                      <td>
                        <strong>{{ $policy->patient?->full_name ?? 'Sin paciente' }}</strong>
                        <span>{{ $policy->patient?->platform_number ?? 'Paciente usuario de plataforma' }}</span>
                      </td>
                      <td>
                        <strong>{{ $policy->insurer_name }}</strong>
                        <span>{{ strtoupper(mb_substr($policy->insurer_name, 0, 3)) }}</span>
                      </td>
                      <td>{{ $policy->plan_name ?? 'GMM Hospitalario' }}</td>
                      <td>{{ $policy->starts_at?->format('d M Y') ?? 'N/A' }} - {{ $policy->ends_at?->format('d M Y') ?? 'N/A' }}</td>
                      <td>{{ $isExpired ? 'Vencida hace '.abs((int) $daysUntilExpiration).' dias' : ($daysUntilExpiration !== null ? 'Vence en '.(int) $daysUntilExpiration.' dias' : 'Sin fecha') }}</td>
                      <td>${{ number_format((float) data_get($policy->metadata, 'premium', 31750), 0) }}</td>
                      <td><span class="insurance-advisor-native-status {{ $isExpired ? 'is-danger' : ($isDue ? 'is-warning' : 'is-ok') }}">{{ $isExpired ? 'Vencida' : ($isDue ? 'Por vencer' : $statusText($policy->status)) }}</span></td>
                      <td>
                        <div class="insurance-advisor-native-row-actions">
                          @if ($isExpired || $isDue)
                            <button
                              type="button"
                              data-open-renewal
                              data-action="{{ route('insurance-advisor.policies.renew', $policy) }}"
                              data-patient="{{ $policy->patient?->full_name ?? 'Paciente asegurado' }}"
                              data-policy="{{ $policy->policy_number }}"
                              data-amount="{{ (float) data_get($policy->metadata, 'premium', 31750) }}"
                            >Subir pago</button>
                          @endif
                          <button type="button" data-open-policy-assistant data-policy-id="{{ $policy->id }}">Consultar</button>
                        </div>
                      </td>
                      <td>
                        <div class="insurance-advisor-native-sync-cell">
                          <span class="insurance-advisor-native-status {{ $syncClass($policySyncStatus) }}">{{ $syncText($policySyncStatus) }}</span>
                          @unless (in_array($policySyncStatus, ['approved', 'synced'], true))
                            <form method="post" action="{{ route('insurance-advisor.policies.sync', $policy) }}">
                              @csrf
                              <button type="submit">{{ $policySyncStatus === 'pending' ? 'Reenviar' : 'Enviar' }}</button>
                            </form>
                          @endunless
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="10">No hay polizas registradas.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
            <div class="pagination-wrap">{{ $policies->links() }}</div>
          </article>

          <aside class="insurance-advisor-native-detail">
            @if ($selectedPolicy)
              <div class="insurance-advisor-native-detail-head">
                <span>{{ $selectedPolicy->ends_at && now()->startOfDay()->diffInDays($selectedPolicy->ends_at->copy()->startOfDay(), false) <= 60 ? 'Por vencer' : $statusText($selectedPolicy->status) }}</span>
                <small>Poliza seleccionada</small>
                <strong>{{ strtoupper(mb_substr($selectedPolicy->insurer_name, 0, 3)) }}</strong>
              </div>
              <h2>{{ $selectedPatient?->full_name ?? 'Paciente sin nombre' }}</h2>
              <p>{{ $selectedPolicy->policy_number }} / {{ $selectedPolicy->insurer_name }}</p>
              <div class="insurance-advisor-native-detail-grid">
                <div><span>Paciente usuario</span><strong>{{ $selectedPatient?->platform_number ?? '100000001' }}<br>{{ $selectedPatient?->user?->username ?? 'paciente' }}</strong></div>
                <div><span>Contacto</span><strong>{{ $selectedPatient?->email ?? 'claudia.salinas@example.com' }}<br>{{ $selectedPatient?->phone ?? '5555550101' }}</strong></div>
                <div><span>Deducible</span><strong>${{ number_format((float) data_get($selectedPolicy->metadata, 'deductible', 18000), 0) }}</strong></div>
                <div><span>Coaseguro</span><strong>{{ data_get($selectedPolicy->metadata, 'coinsurance', '10%') }}</strong></div>
                <div><span>Prima anual</span><strong>${{ number_format((float) data_get($selectedPolicy->metadata, 'premium_annual', 42800), 0) }}</strong></div>
                <div><span>Pago</span><strong>{{ data_get($selectedPolicy->metadata, 'payment_status', 'Pendiente de renovacion') }}</strong></div>
                <div><span>Sincronizacion</span><strong>{{ $syncText(data_get($selectedPolicy->metadata, 'doctor_sync.status', 'not_synced')) }}</strong></div>
                <div><span>Medico tratante</span><strong>{{ data_get($selectedPolicy->metadata, 'doctor_sync.doctor_name', $selectedPatient?->primaryDoctor?->full_name ?? 'Por asignar') }}</strong></div>
              </div>
              <div class="insurance-advisor-native-detail-actions">
                <form method="post" action="{{ route('insurance-advisor.policies.messages.store', $selectedPolicy) }}">@csrf<button type="submit">Enviar mensaje</button></form>
                <button
                  type="button"
                  data-open-renewal
                  data-action="{{ route('insurance-advisor.policies.renew', $selectedPolicy) }}"
                  data-patient="{{ $selectedPatient?->full_name ?? 'Paciente asegurado' }}"
                  data-policy="{{ $selectedPolicy->policy_number }}"
                  data-amount="{{ (float) data_get($selectedPolicy->metadata, 'premium', 31750) }}"
                >Subir pago</button>
                <button class="is-wide" type="button" data-open-policy-assistant data-policy-id="{{ $selectedPolicy->id }}">Consultar poliza con Dr. Sam</button>
              </div>
              @php
                $latestRenewal = collect(data_get($selectedPolicy->metadata, 'renewal_history', []))->first();
              @endphp
              <p class="insurance-advisor-native-note">
                <strong>{{ $latestRenewal ? 'Ultima renovacion registrada' : 'Sin renovaciones cargadas' }}</strong><br>
                {{ $latestRenewal ? (($latestRenewal['paid_at'] ?? 'Sin fecha').' / $'.number_format((float) ($latestRenewal['amount'] ?? 0), 0)) : 'El siguiente pago actualizara el periodo.' }}
              </p>
            @else
              <p class="empty-state">Selecciona una poliza para ver el detalle.</p>
            @endif
          </aside>
        </section>
      @elseif ($section === 'due')
        <section class="insurance-advisor-native-panel">
          <div class="insurance-advisor-native-heading">
            <h2>Por vencer</h2>
            <span>{{ $dueSoonPoliciesCount }} por vencer</span>
          </div>
          <div class="insurance-advisor-native-policy-cards">
            @forelse ($dueSoonPolicies as $policy)
              <article class="is-warning">
                <strong>{{ $policy->patient?->full_name ?? 'Paciente asegurado' }}</strong>
                <span>{{ $policy->policy_number }} / Vence en {{ $policy->ends_at ? (int) now()->startOfDay()->diffInDays($policy->ends_at->copy()->startOfDay(), false) : 0 }} dias</span>
                <em>Por vencer</em>
                <div>
                  <button
                    type="button"
                    data-open-renewal
                    data-action="{{ route('insurance-advisor.policies.renew', $policy) }}"
                    data-patient="{{ $policy->patient?->full_name ?? 'Paciente asegurado' }}"
                    data-policy="{{ $policy->policy_number }}"
                    data-amount="{{ (float) data_get($policy->metadata, 'premium', 31750) }}"
                  >Subir pago</button>
                  <form method="post" action="{{ route('insurance-advisor.policies.messages.store', $policy) }}">@csrf<button type="submit">Avisar</button></form>
                </div>
              </article>
            @empty
              <p class="empty-state">No hay polizas por vencer.</p>
            @endforelse
          </div>
        </section>
      @elseif ($section === 'expired')
        <section class="insurance-advisor-native-panel">
          <div class="insurance-advisor-native-heading">
            <h2>Vencidas</h2>
            <span>{{ $expiredPoliciesCount }} vencidas</span>
          </div>
          <div class="insurance-advisor-native-policy-cards">
            @forelse ($expiredPolicies as $policy)
              <article class="is-danger">
                <strong>{{ $policy->patient?->full_name ?? 'Paciente asegurado' }}</strong>
                <span>{{ $policy->policy_number }} / Vencida hace {{ $policy->ends_at ? abs((int) $policy->ends_at->diffInDays(now())) : 0 }} dias</span>
                <em>Vencida</em>
                <div>
                  <button
                    type="button"
                    data-open-renewal
                    data-action="{{ route('insurance-advisor.policies.renew', $policy) }}"
                    data-patient="{{ $policy->patient?->full_name ?? 'Paciente asegurado' }}"
                    data-policy="{{ $policy->policy_number }}"
                    data-amount="{{ (float) data_get($policy->metadata, 'premium', 31750) }}"
                  >Subir pago</button>
                  <form method="post" action="{{ route('insurance-advisor.policies.messages.store', $policy) }}">@csrf<button type="submit">Avisar</button></form>
                </div>
              </article>
            @empty
              <p class="empty-state">No hay polizas vencidas.</p>
            @endforelse
          </div>
        </section>
      @elseif ($section === 'messages')
        <section class="insurance-advisor-native-panel">
          <div class="insurance-advisor-native-heading">
            <h2>Mensajes a asegurados</h2>
            <span>{{ $policyMessages->count() }}</span>
          </div>
          <div class="insurance-advisor-native-advisor-alerts insurance-advisor-native-messages">
            @forelse ($policyMessages as $message)
              <article>
                <small>{{ $message['sent_at'] }} &nbsp; Plataforma Dr. Sam &nbsp; {{ $message['patient'] }}</small>
                <strong>{{ $message['subject'] }}</strong>
                <p>{{ $message['body'] }}</p>
              </article>
            @empty
              <p class="empty-state">No hay mensajes enviados.</p>
            @endforelse
          </div>
        </section>
      @elseif ($section === 'alerts')
        <section class="insurance-advisor-native-panel">
          <div class="insurance-advisor-native-heading">
            <h2>Alertas del asesor</h2>
            <span>{{ $policyAlerts->count() }}</span>
          </div>
          <div class="insurance-advisor-native-advisor-alerts">
            @forelse ($policyAlerts as $alert)
              <article>
                <small>{{ $alert['created_at'] }} &nbsp; Plataforma Dr. Sam &nbsp; Asesor</small>
                <strong>{{ $alert['subject'] }}</strong>
                <p>{{ $alert['body'] }}</p>
              </article>
            @empty
              <p class="empty-state">No hay alertas de pólizas.</p>
            @endforelse
          </div>
        </section>
      @elseif ($section === 'claims')
        <div class="insurance-advisor-native-claims-title">
          <div>
            <h2>Siniestros</h2>
            <p>Consulta y gestiona reembolsos, pagos directos y altas hospitalarias.</p>
          </div>
          <button type="button" data-new-claim-toggle>+ Nuevo siniestro</button>
        </div>
        <section class="insurance-advisor-native-panel insurance-advisor-native-new-claim" data-new-claim-panel @if (! request()->boolean('new_claim') && ! $errors->any()) hidden @endif>
          <div class="insurance-advisor-native-heading">
            <h2>Nuevo siniestro</h2>
          </div>
          <form method="post" action="{{ route('insurance-advisor.claims.store') }}">
            @csrf
            <label>Póliza
              <select name="policy_id" required>
                @foreach ($allPolicies as $policy)
                  <option value="{{ $policy->id }}" @selected((string) old('policy_id', $selectedPolicy?->id) === (string) $policy->id)>{{ $policy->patient?->full_name ?? 'Paciente asegurado' }} / {{ $policy->policy_number }}</option>
                @endforeach
              </select>
            </label>
            <label>Tipo de trámite
              <select name="type" required>
                <option value="reimbursement" @selected(old('type') === 'reimbursement')>Reembolso</option>
                <option value="direct_payment" @selected(old('type') === 'direct_payment')>Pago directo a hospital</option>
                <option value="hospital_discharge" @selected(old('type') === 'hospital_discharge')>Alta hospitalaria</option>
              </select>
            </label>
            <label>Hospital<input name="hospital" value="{{ old('hospital') }}" required></label>
            <label>Fecha del evento<input name="event_date" type="date" value="{{ old('event_date') }}" required></label>
            <label>Monto estimado<input name="estimated_amount" type="number" min="0" step="0.01" value="{{ old('estimated_amount') }}" required></label>
            <label>Diagnóstico<input name="diagnosis" value="{{ old('diagnosis') }}" required></label>
            <button type="submit">Crear siniestro</button>
          </form>
        </section>
        <section class="insurance-advisor-native-panel" data-claims-list @if (request()->boolean('new_claim') || $errors->any()) hidden @endif>
          <div class="insurance-advisor-native-heading">
            <h2>Listado de siniestros</h2>
            <span>{{ $claims->count() }}</span>
          </div>
          <div class="insurance-advisor-native-claims">
            <div class="insurance-advisor-native-claim-columns" aria-hidden="true">
              <span>Siniestro</span><span>Asegurado</span><span>Fecha</span><span>Documentos pendientes</span><span>Estatus</span>
            </div>
            @forelse ($claims as $claim)
              @php
                $claimIsOpen = request()->filled('claim') ? request('claim') === $claim['folio'] : $loop->first;
                $claimPendingDocuments = $claim['documents_pending'];
                $claimInReview = in_array($claim['stage'], ['En revision aseguradora', 'Alta hospitalaria'], true);
                $claimResolved = $claim['stage'] === 'Resolucion / pago';
              @endphp
              <article class="{{ $claimIsOpen ? 'is-open' : '' }}" data-claim-card data-claim-folio="{{ $claim['folio'] }}">
                <button class="insurance-advisor-native-claim-row" type="button" data-claim-toggle aria-expanded="{{ $claimIsOpen ? 'true' : 'false' }}">
                  <div><strong>{{ $claim['folio'] }}</strong><span>{{ $claim['type'] }}</span></div>
                  <div><strong>{{ $claim['patient'] }}</strong><span>{{ $claim['hospital'] }}</span></div>
                  <div>{{ $claim['date'] }}</div>
                  <div><span class="insurance-advisor-native-status {{ $claimPendingDocuments ? 'is-warning' : 'is-ok' }}">{{ $claimPendingDocuments ? 'Pendiente' : 'Completo' }}</span><small>{{ $claimPendingDocuments }} por subir</small></div>
                  <div><span class="insurance-advisor-native-status {{ $claimInReview || $claimResolved ? 'is-ok' : 'is-warning' }}">{{ $claim['status'] }}</span><small data-claim-toggle-label>{{ $claimIsOpen ? 'Ocultar detalle ^' : 'Ver detalle >' }}</small></div>
                </button>
                <div class="insurance-advisor-native-claim-detail" data-claim-detail @if (! $claimIsOpen) hidden @endif>
                    <div class="insurance-advisor-native-claim-summary">
                      <div>
                    <span>{{ $claim['type'] }}</span>
                    <h2>{{ $claim['folio'] }} / {{ $claim['patient'] }}</h2>
                    <p>{{ $claim['hospital'] }} / {{ $claim['date'] }}</p>
                      </div>
                      <strong>{{ $claimPendingDocuments ? $claimPendingDocuments.' docs pendientes' : 'Documentos completos' }}</strong>
                    </div>
                    <div class="insurance-advisor-native-claim-meta">
                      <div><span>Poliza</span><strong>{{ $claim['policy']->policy_number }}</strong></div>
                      <div><span>Aseguradora</span><strong>{{ $claim['policy']->insurer_name }}</strong></div>
                      <div><span>Diagnostico</span><strong>{{ $claim['diagnosis'] }}</strong></div>
                      <div><span>Monto estimado</span><strong>${{ number_format($claim['amount']) }}</strong></div>
                    </div>
                    <div class="insurance-advisor-native-claim-steps">
                      <div class="{{ ! $claimInReview && ! $claimResolved ? 'is-active' : '' }}"><strong>Documentacion</strong><span>{{ ! $claimInReview && ! $claimResolved ? 'Etapa actual' : 'Completada' }}</span></div>
                      <div class="{{ $claimInReview ? 'is-active' : '' }}"><strong>En revision aseguradora</strong><span>{{ $claimInReview ? 'Etapa actual' : ($claimResolved ? 'Completada' : 'Pendiente') }}</span></div>
                      <div class="{{ $claimResolved ? 'is-active' : '' }}"><strong>Resolucion / pago</strong><span>{{ $claimResolved ? 'Etapa actual' : 'Pendiente' }}</span></div>
                    </div>
                    <div class="insurance-advisor-native-claim-actions">
                      <form method="post" action="{{ route('insurance-advisor.claims.documents.request', [$claim['policy'], $claim['folio']]) }}">@csrf<button type="submit">Solicitar documentos</button></form>
                      <form method="post" action="{{ route('insurance-advisor.claims.discharge', [$claim['policy'], $claim['folio']]) }}">@csrf<button type="submit">Tramitar alta hospitalaria</button></form>
                      <button type="button" data-open-claim-follow-up data-action="{{ route('insurance-advisor.claims.follow-up', [$claim['policy'], $claim['folio']]) }}" data-claim="{{ $claim['folio'] }}">Seguimiento</button>
                      <form method="post" action="{{ route('insurance-advisor.claims.send-insurer', [$claim['policy'], $claim['folio']]) }}">@csrf<button type="submit">Enviar a aseguradora</button></form>
                    </div>
                    <table class="insurance-advisor-native-documents">
                      <thead><tr><th>Documento</th><th>Requerido</th><th>Estatus</th><th>Archivo cargado</th><th>Subir archivo</th></tr></thead>
                      <tbody>
                        @foreach ($claim['documents'] as $document)
                          @php($received = filled($document['file_path'] ?? null) || filled($document['file_name'] ?? null) || ($document['status'] ?? null) === 'received')
                          <tr class="{{ $received ? 'is-received' : 'is-pending' }}">
                            <td>{{ $document['name'] }} {{ $document['required'] ? '*' : '' }}</td>
                            <td>{{ $document['required'] ? 'Si' : 'No' }}</td>
                            <td><span class="insurance-advisor-native-status {{ $received ? 'is-ok' : 'is-warning' }}">{{ $received ? 'Recibido' : 'Pendiente' }}</span></td>
                            <td>{{ $document['file_name'] ?? 'Sin archivo' }}</td>
                            <td>
                              <form class="insurance-advisor-native-document-upload" method="post" enctype="multipart/form-data" action="{{ route('insurance-advisor.claims.documents.store', [$claim['policy'], $claim['folio']]) }}">
                                @csrf
                                <input type="hidden" name="document_key" value="{{ $document['key'] }}">
                                <input name="document_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                                <button type="submit">{{ $received ? 'Reemplazar' : 'Subir' }}</button>
                              </form>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                    <details class="insurance-advisor-native-quotations">
                      <summary><span>Cotizaciones</span><small>{{ count($claim['quotations']) ? count($claim['quotations']).' solicitudes registradas' : 'Sin cotizaciones solicitadas' }}</small></summary>
                      <div>
                        @if (count($claim['quotations']))
                          <table>
                            <thead><tr><th>Folio</th><th>Servicio</th><th>Institucion / unidad</th><th>Fecha</th><th>Estatus</th></tr></thead>
                            <tbody>
                              @foreach ($claim['quotations'] as $quotation)
                                <tr><td>{{ $quotation['id'] }}</td><td>{{ $quotation['service_label'] }}</td><td>{{ $quotation['institution'] }} / {{ $quotation['unit'] }}</td><td>{{ filled($quotation['created_at'] ?? null) ? \Illuminate\Support\Carbon::parse($quotation['created_at'])->format('d M Y') : 'Sin fecha' }}</td><td><span class="insurance-advisor-native-status is-warning">{{ $quotation['status'] ?? 'Solicitada' }}</span></td></tr>
                              @endforeach
                            </tbody>
                          </table>
                        @endif
                        <form class="insurance-advisor-native-quotation-form" method="post" enctype="multipart/form-data" action="{{ route('insurance-advisor.claims.quotations.store', [$claim['policy'], $claim['folio']]) }}">
                          @csrf
                          <label>Servicio<select name="service" required>@foreach ($quotationServices as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
                          <label>Institucion<input name="institution" required></label>
                          <label>Unidad<input name="unit" required></label>
                          <label>Receta<input name="prescription_file" type="file" accept=".pdf,.jpg,.jpeg,.png" required></label>
                          <label>Resumen clinico<input name="clinical_summary_file" type="file" accept=".pdf,.jpg,.jpeg,.png" required></label>
                          <button type="submit">Solicitar cotizacion</button>
                        </form>
                      </div>
                    </details>
                    <div class="insurance-advisor-native-claim-notes" aria-label="Bitacora del siniestro">
                      @forelse ($claim['notes'] as $note)
                        <p>{{ filled($note['at'] ?? null) ? \Illuminate\Support\Carbon::parse($note['at'])->format('d M Y, H:i') : 'Sin fecha' }} - {{ $note['text'] }}</p>
                      @empty
                        <p>Sin seguimientos registrados.</p>
                      @endforelse
                    </div>
                </div>
              </article>
            @empty
              <p class="empty-state">No hay siniestros registrados.</p>
            @endforelse
          </div>
          <a class="insurance-advisor-native-claims-footer" href="{{ route('insurance-advisor.dashboard', ['section' => 'claims']) }}">Ver todos los siniestros</a>
        </section>
      @endif
    </section>

    <div class="insurance-advisor-renewal-backdrop" data-renewal-modal hidden>
      <section class="insurance-advisor-renewal-dialog" role="dialog" aria-modal="true" aria-labelledby="insurance-renewal-title">
        <header>
          <div>
            <h2 id="insurance-renewal-title">Subir pago y renovar</h2>
            <span data-renewal-policy>Selecciona una poliza</span>
          </div>
          <button type="button" data-close-renewal>Cerrar</button>
        </header>
        <form method="post" enctype="multipart/form-data" data-renewal-form>
          @csrf
          <label>Comprobante de pago<input name="payment_file" type="file" accept=".pdf,.jpg,.jpeg,.png" required></label>
          <label>Monto pagado<input name="amount" type="number" min="0" step="0.01" required></label>
          <label>Fecha de pago<input name="paid_at" type="date" value="{{ now()->format('Y-m-d') }}" required></label>
          <label class="is-wide">Notas<textarea name="notes" rows="3" placeholder="Referencia bancaria, banco o comentario del asesor"></textarea></label>
          <div class="insurance-advisor-renewal-actions is-wide">
            <button type="submit">Registrar pago y generar periodo</button>
            <button type="button" data-close-renewal>Cancelar</button>
          </div>
        </form>
      </section>
    </div>

    <div class="insurance-advisor-renewal-backdrop" data-claim-follow-up-modal hidden>
      <section class="insurance-advisor-renewal-dialog" role="dialog" aria-modal="true" aria-labelledby="insurance-claim-follow-up-title">
        <header>
          <div>
            <h2 id="insurance-claim-follow-up-title">Seguimiento de siniestro</h2>
            <span data-claim-follow-up-label>Selecciona un siniestro</span>
          </div>
          <button type="button" data-close-claim-follow-up aria-label="Cerrar">Cerrar</button>
        </header>
        <form method="post" data-claim-follow-up-form>
          @csrf
          <label class="is-wide">Nota de seguimiento<textarea name="note" rows="5" maxlength="1200" required placeholder="Acuerdo, llamada, documento solicitado o avance del tramite"></textarea></label>
          <div class="insurance-advisor-renewal-actions is-wide">
            <button type="submit">Guardar seguimiento</button>
            <button type="button" data-close-claim-follow-up>Cancelar</button>
          </div>
        </form>
      </section>
    </div>

    <div class="insurance-advisor-renewal-backdrop" data-policy-assistant-modal hidden>
      <section class="insurance-advisor-native-assistant" role="dialog" aria-modal="true" aria-labelledby="insurance-policy-assistant-title">
        <header>
          <div class="insurance-advisor-native-assistant-identity">
            <span>DS</span>
            <div><h2 id="insurance-policy-assistant-title">Dr. Sam</h2><small>Asistente de polizas</small></div>
          </div>
          <div><strong data-policy-assistant-status>Poliza</strong><small data-policy-assistant-label>Selecciona una poliza</small></div>
          <button type="button" data-close-policy-assistant aria-label="Cerrar">X</button>
        </header>
        <div class="insurance-advisor-native-assistant-messages" data-policy-assistant-messages aria-live="polite"></div>
        <form class="insurance-advisor-native-assistant-form" data-policy-assistant-form>
          <button type="button" data-policy-assistant-attach>Adjuntar</button>
          <input type="file" data-policy-assistant-file accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" hidden>
          <input name="question" autocomplete="off" placeholder="Escribe tu pregunta sobre la poliza" required>
          <button type="button" data-policy-assistant-voice>Dictar</button>
          <button type="submit">Enviar</button>
        </form>
      </section>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    (() => {
      const modal = document.querySelector('[data-renewal-modal]');
      const form = document.querySelector('[data-renewal-form]');
      const policyLabel = document.querySelector('[data-renewal-policy]');
      const followUpModal = document.querySelector('[data-claim-follow-up-modal]');
      const followUpForm = document.querySelector('[data-claim-follow-up-form]');
      const followUpLabel = document.querySelector('[data-claim-follow-up-label]');
      if (!modal || !form || !policyLabel) return;
      let selectedCard = null;

      const close = () => {
        modal.hidden = true;
        form.reset();
        form.querySelector('[name="paid_at"]').value = '{{ now()->format('Y-m-d') }}';
        policyLabel.textContent = 'Selecciona una poliza';
        selectedCard?.classList.remove('is-selected');
        selectedCard = null;
      };
      const closeFollowUp = () => {
        if (!followUpModal || !followUpForm || !followUpLabel) return;
        followUpModal.hidden = true;
        followUpForm.reset();
        followUpLabel.textContent = 'Selecciona un siniestro';
      };

      document.addEventListener('click', (event) => {
        const followUpOpener = event.target.closest('[data-open-claim-follow-up]');
        if (followUpOpener && followUpModal && followUpForm && followUpLabel) {
          followUpForm.action = followUpOpener.dataset.action;
          followUpLabel.textContent = followUpOpener.dataset.claim;
          followUpModal.hidden = false;
          followUpForm.querySelector('textarea')?.focus();
          return;
        }

        if (event.target.closest('[data-close-claim-follow-up]') || event.target === followUpModal) {
          closeFollowUp();
          return;
        }

        const newClaimToggle = event.target.closest('[data-new-claim-toggle]');
        if (newClaimToggle) {
          const newClaimPanel = document.querySelector('[data-new-claim-panel]');
          const claimsList = document.querySelector('[data-claims-list]');
          const opening = newClaimPanel?.hasAttribute('hidden');
          newClaimPanel?.toggleAttribute('hidden', !opening);
          claimsList?.toggleAttribute('hidden', opening);
          if (opening) {
            newClaimPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            newClaimPanel?.querySelector('select')?.focus();
          }
          return;
        }

        const claimToggle = event.target.closest('[data-claim-toggle]');
        if (claimToggle) {
          const card = claimToggle.closest('[data-claim-card]');
          const detail = card?.querySelector('[data-claim-detail]');
          const label = claimToggle.querySelector('[data-claim-toggle-label]');
          const willOpen = !card?.classList.contains('is-open');

          document.querySelectorAll('[data-claim-card].is-open').forEach((openCard) => {
            if (openCard === card) return;
            openCard.classList.remove('is-open');
            openCard.querySelector('[data-claim-detail]')?.setAttribute('hidden', '');
            openCard.querySelector('[data-claim-toggle]')?.setAttribute('aria-expanded', 'false');
            const openLabel = openCard.querySelector('[data-claim-toggle-label]');
            if (openLabel) openLabel.textContent = 'Ver detalle >';
          });

          card?.classList.toggle('is-open', willOpen);
          detail?.toggleAttribute('hidden', !willOpen);
          claimToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
          if (label) label.textContent = willOpen ? 'Ocultar detalle ^' : 'Ver detalle >';
          return;
        }

        const opener = event.target.closest('[data-open-renewal]');
        if (opener) {
          selectedCard?.classList.remove('is-selected');
          selectedCard = opener.closest('.insurance-advisor-native-policy-cards article');
          selectedCard?.classList.add('is-selected');
          form.action = opener.dataset.action;
          form.querySelector('[name="amount"]').value = opener.dataset.amount;
          policyLabel.textContent = `${opener.dataset.patient} / ${opener.dataset.policy}`;
          modal.hidden = false;
          form.querySelector('[name="payment_file"]').focus();
          return;
        }
        if (event.target.closest('[data-close-renewal]') || event.target === modal) close();
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) close();
        if (event.key === 'Escape' && followUpModal && !followUpModal.hidden) closeFollowUp();
      });
    })();

    (() => {
      const policies = {{ Illuminate\Support\Js::from($policyAssistantData) }};
      const modal = document.querySelector('[data-policy-assistant-modal]');
      const messages = document.querySelector('[data-policy-assistant-messages]');
      const form = document.querySelector('[data-policy-assistant-form]');
      const label = document.querySelector('[data-policy-assistant-label]');
      const status = document.querySelector('[data-policy-assistant-status]');
      const fileInput = document.querySelector('[data-policy-assistant-file]');
      if (!modal || !messages || !form || !label || !status || !fileInput) return;

      let activePolicy = null;
      let recognition = null;
      const conversations = new Map();
      const currency = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
      const normalize = (value) => String(value || '').toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');

      const addMessage = (role, content, persist = true) => {
        const node = document.createElement('p');
        node.className = role === 'user' ? 'is-user' : 'is-assistant';
        node.textContent = content;
        messages.appendChild(node);
        messages.scrollTop = messages.scrollHeight;
        if (activePolicy && persist) {
          const thread = conversations.get(String(activePolicy.id)) || [];
          thread.push({ role, content });
          conversations.set(String(activePolicy.id), thread.slice(-30));
        }
      };

      const responseFor = (policy, question) => {
        const query = normalize(question);
        const claims = Array.isArray(policy.claims) ? policy.claims : [];
        const pendingDocuments = claims.reduce((total, claim) => total + Number(claim.documents_pending || 0), 0);
        const syncLabel = ['approved', 'synced'].includes(policy.sync_status) ? 'sincronizada' : (policy.sync_status === 'pending' ? 'pendiente' : 'no sincronizada');

        if (/(venc|vigenc|fecha|estatus)/.test(query)) {
          return `La poliza ${policy.policy_number} tiene vigencia del ${policy.starts_at} al ${policy.ends_at}. Su estatus registrado es ${policy.status}.`;
        }
        if (/(pago|prima|renov|recibo|comprobante)/.test(query)) {
          return `La prima registrada es ${currency.format(policy.premium)} y el estatus de pago es ${policy.payment_status}. La renovacion se registra desde Subir pago.`;
        }
        if (/(deduc|coaseg|participacion)/.test(query)) {
          return `El deducible es ${currency.format(policy.deductible)} y el coaseguro registrado es ${policy.coinsurance}.`;
        }
        if (/(siniestro|reembolso|hospital|tramite|document)/.test(query)) {
          if (!claims.length) return 'Esta poliza no tiene siniestros registrados.';
          return `Hay ${claims.length} siniestro(s) relacionado(s) y ${pendingDocuments} documento(s) pendiente(s). Puedes abrir Siniestros para gestionar archivos, seguimiento y envio a la aseguradora.`;
        }
        if (/(sincron|medico|vincul|plataforma)/.test(query)) {
          return `La poliza esta ${syncLabel} con el expediente medico. Puedes enviar o reenviar la solicitud desde la columna Sincronizacion.`;
        }
        if (/(cancer|oncolog|quimio|cobertura|cubre)/.test(query)) {
          return `La cobertura especifica debe validarse contra las condiciones generales, endosos, diagnostico, periodos de espera y autorizacion de ${policy.insurer}. Los datos actuales muestran ${policy.product}, deducible ${currency.format(policy.deductible)} y coaseguro ${policy.coinsurance}.`;
        }

        return `Resumen: ${policy.patient}, poliza ${policy.policy_number} con ${policy.insurer}, producto ${policy.product}, prima ${currency.format(policy.premium)}, sincronizacion ${syncLabel} y ${claims.length} siniestro(s).`;
      };

      const renderThread = () => {
        messages.replaceChildren();
        const thread = conversations.get(String(activePolicy.id));
        if (thread?.length) {
          thread.forEach((entry) => addMessage(entry.role, entry.content, false));
          return;
        }
        addMessage('assistant', `Puedo ayudarte a revisar la poliza ${activePolicy.policy_number}, su vigencia, renovacion, documentos, sincronizacion y siniestros.`);
      };

      const open = (policyId) => {
        activePolicy = policies[String(policyId)];
        if (!activePolicy) return;
        label.textContent = `${activePolicy.patient} / ${activePolicy.policy_number}`;
        status.textContent = activePolicy.status === 'active' ? 'Poliza activa' : 'Poliza '+activePolicy.status;
        renderThread();
        modal.hidden = false;
        form.querySelector('[name="question"]')?.focus();
      };

      const close = () => {
        modal.hidden = true;
        form.reset();
        recognition?.stop();
        recognition = null;
      };

      document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-open-policy-assistant]');
        if (opener) {
          open(opener.dataset.policyId);
          return;
        }
        if (event.target.closest('[data-close-policy-assistant]') || event.target === modal) close();
        if (event.target.closest('[data-policy-assistant-attach]')) fileInput.click();
        if (event.target.closest('[data-policy-assistant-voice]')) {
          const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
          if (!SpeechRecognition) {
            addMessage('assistant', 'El dictado no esta disponible en este navegador.');
            return;
          }
          recognition?.stop();
          recognition = new SpeechRecognition();
          recognition.lang = 'es-MX';
          recognition.interimResults = false;
          recognition.onresult = (speechEvent) => {
            form.querySelector('[name="question"]').value = speechEvent.results[0][0].transcript;
          };
          recognition.start();
        }
      });

      fileInput.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (!file) return;
        addMessage('user', `Archivo adjunto: ${file.name}`);
        addMessage('assistant', 'Archivo recibido en esta consulta. Indica que dato deseas revisar.');
        fileInput.value = '';
      });

      form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!activePolicy) return;
        const input = form.querySelector('[name="question"]');
        const question = input.value.trim();
        if (!question) return;
        addMessage('user', question);
        input.value = '';
        window.setTimeout(() => addMessage('assistant', responseFor(activePolicy, question)), 120);
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) close();
      });
    })();
  </script>
@endpush
