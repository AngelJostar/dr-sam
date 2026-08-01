<?php $__env->startSection('body_class', 'insurance-advisor-native-body'); ?>

<?php
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
?>

<?php $__env->startSection('content'); ?>
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
        <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <a class="<?php echo e($section === $item['key'] ? 'is-active' : ''); ?>" href="<?php echo e($sectionRoute($item['key'])); ?>">
            <span><?php echo e($item['icon']); ?></span><?php echo e($item['label']); ?>

          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </nav>

      <a class="insurance-advisor-native-back" href="<?php echo e(route('dashboard')); ?>">Regresar al acceso</a>
    </aside>

    <section class="insurance-advisor-native-workspace">
      <?php if(session('status')): ?>
        <div class="notice success"><?php echo e(session('status')); ?></div>
      <?php endif; ?>

      <?php if($errors->any()): ?>
        <div class="notice danger">
          <strong>No se pudo actualizar.</strong>
          <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </ul>
        </div>
      <?php endif; ?>

      <header class="insurance-advisor-native-topbar">
        <div class="insurance-advisor-native-user">
          <span>DS</span>
          <div>
            <strong>Dr. Sam</strong>
            <small>Asesor de seguros GMM</small>
          </div>
        </div>
        <div class="insurance-advisor-native-actions">
          <strong><?php echo e(auth()->user()?->name ?? 'Superadministrador'); ?></strong>
          <a href="<?php echo e(route('dashboard')); ?>">Cambiar usuario</a>
        </div>
      </header>

      <section class="insurance-advisor-native-hero">
        <div>
          <p class="eyebrow">Gastos medicos mayores</p>
          <h1>Cartera de polizas y siniestros</h1>
          <p>Gestiona y da seguimiento a tus polizas, renovaciones y siniestros desde un solo lugar.</p>
        </div>
        <div class="insurance-advisor-native-hero-actions">
          <form method="post" action="<?php echo e(route('insurance-advisor.alerts.sync')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit">Sincronizar alertas</button>
          </form>
        </div>
      </section>

      <section class="insurance-advisor-native-metrics" aria-label="Resumen de seguros GMM">
        <article>
          <span>P</span>
          <small>Polizas</small>
          <strong><?php echo e(number_format($totalPolicies)); ?></strong>
          <p>Cartera asignada</p>
        </article>
        <article>
          <span>OK</span>
          <small>Vigentes</small>
          <strong><?php echo e(number_format($activePoliciesCount)); ?></strong>
          <p>Polizas activas</p>
        </article>
        <article>
          <span>!</span>
          <small>Por vencer</small>
          <strong><?php echo e(number_format($dueSoonPoliciesCount)); ?></strong>
          <p>Dos meses o menos</p>
        </article>
        <article>
          <span>!</span>
          <small>Vencidas</small>
          <strong><?php echo e(number_format($expiredPoliciesCount)); ?></strong>
          <p>Polizas fuera de vigencia</p>
        </article>
        <article>
          <span>S</span>
          <small>Siniestros</small>
          <strong><?php echo e(number_format($claimsCount)); ?></strong>
          <p>3 docs pendientes</p>
        </article>
      </section>

      <div class="insurance-advisor-native-tabs">
        <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <a class="<?php echo e(($section === 'home' ? 'all' : $section) === $key ? 'is-active' : ''); ?>" href="<?php echo e($sectionRoute($key)); ?>"><?php echo e($label); ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>

      <?php if(in_array($section, ['home', 'all'], true)): ?>
        <section class="insurance-advisor-native-grid">
          <article class="insurance-advisor-native-table-card">
            <div class="insurance-advisor-native-heading">
              <div>
                <h2>Listado de polizas</h2>
                <p><?php echo e($totalPolicies); ?> registros</p>
              </div>
              <button type="button">Descargar Datos</button>
            </div>

            <form class="insurance-advisor-native-filters" method="get">
              <?php if($section !== 'home'): ?>
                <input type="hidden" name="section" value="<?php echo e($section); ?>">
              <?php endif; ?>
              <label>Buscar<input name="search" value="<?php echo e(request('search')); ?>" placeholder="Paciente, poliza o aseguradora"></label>
              <label>Estatus
                <select name="status" onchange="this.form.submit()">
                  <option value="all">Todos</option>
                  <option value="active" <?php if(request('status') === 'active'): echo 'selected'; endif; ?>>Vigentes</option>
                  <option value="due" <?php if(request('status') === 'due'): echo 'selected'; endif; ?>>Por vencer</option>
                  <option value="expired" <?php if(request('status') === 'expired'): echo 'selected'; endif; ?>>Vencidas</option>
                </select>
              </label>
              <label>Aseguradora
                <select name="insurer" onchange="this.form.submit()">
                  <option value="all">Todas</option>
                  <?php $__currentLoopData = $insurers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $insurer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($insurer); ?>" <?php if(request('insurer') === $insurer): echo 'selected'; endif; ?>><?php echo e($insurer); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </label>
              <label>Ordenar
                <select name="sort" onchange="this.form.submit()">
                  <option value="expiration">Vencimiento cercano</option>
                  <option value="patient" <?php if(request('sort') === 'patient'): echo 'selected'; endif; ?>>Paciente</option>
                  <option value="recent" <?php if(request('sort') === 'recent'): echo 'selected'; endif; ?>>Registro reciente</option>
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
                  </tr>
                </thead>
                <tbody>
                  <?php $__empty_1 = true; $__currentLoopData = $policies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                      $isExpired = $policy->status === 'expired' || ($policy->ends_at && $policy->ends_at->isPast());
                      $daysUntilExpiration = $policy->ends_at ? now()->startOfDay()->diffInDays($policy->ends_at->copy()->startOfDay(), false) : null;
                      $isDue = ! $isExpired && $daysUntilExpiration !== null && $daysUntilExpiration <= 60;
                    ?>
                    <tr class="<?php echo e($selectedPolicy?->is($policy) ? 'is-selected' : ($isExpired ? 'is-expired' : ($isDue ? 'is-due' : ''))); ?>">
                      <td>
                        <a class="insurance-advisor-native-policy-link" href="<?php echo e(route('insurance-advisor.dashboard', array_filter(['section' => $section === 'home' ? null : $section, 'policy' => $policy->id, 'search' => request('search'), 'status' => request('status'), 'insurer' => request('insurer'), 'sort' => request('sort')]))); ?>"><?php echo e($policy->policy_number); ?></a>
                        <span><?php echo e($policy->metadata['external_id'] ?? 'POL-GMM-'.str_pad((string) $policy->id, 4, '0', STR_PAD_LEFT)); ?></span>
                      </td>
                      <td>
                        <strong><?php echo e($policy->patient?->full_name ?? 'Sin paciente'); ?></strong>
                        <span><?php echo e($policy->patient?->platform_number ?? 'Paciente usuario de plataforma'); ?></span>
                      </td>
                      <td>
                        <strong><?php echo e($policy->insurer_name); ?></strong>
                        <span><?php echo e(strtoupper(mb_substr($policy->insurer_name, 0, 3))); ?></span>
                      </td>
                      <td><?php echo e($policy->plan_name ?? 'GMM Hospitalario'); ?></td>
                      <td><?php echo e($policy->starts_at?->format('d M Y') ?? 'N/A'); ?> - <?php echo e($policy->ends_at?->format('d M Y') ?? 'N/A'); ?></td>
                      <td><?php echo e($isExpired ? 'Vencida hace '.abs((int) $daysUntilExpiration).' dias' : ($daysUntilExpiration !== null ? 'Vence en '.(int) $daysUntilExpiration.' dias' : 'Sin fecha')); ?></td>
                      <td>$<?php echo e(number_format((float) data_get($policy->metadata, 'premium', 31750), 0)); ?></td>
                      <td><span class="insurance-advisor-native-status <?php echo e($isExpired ? 'is-danger' : ($isDue ? 'is-warning' : 'is-ok')); ?>"><?php echo e($isExpired ? 'Vencida' : ($isDue ? 'Por vencer' : $statusText($policy->status))); ?></span></td>
                      <td>
                        <div class="insurance-advisor-native-row-actions">
                          <?php if($isExpired || $isDue): ?>
                            <button
                              type="button"
                              data-open-renewal
                              data-action="<?php echo e(route('insurance-advisor.policies.renew', $policy)); ?>"
                              data-patient="<?php echo e($policy->patient?->full_name ?? 'Paciente asegurado'); ?>"
                              data-policy="<?php echo e($policy->policy_number); ?>"
                              data-amount="<?php echo e((float) data_get($policy->metadata, 'premium', 31750)); ?>"
                            >Subir pago</button>
                          <?php endif; ?>
                          <span>No</span>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                      <td colspan="9">No hay polizas registradas.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <div class="pagination-wrap"><?php echo e($policies->links()); ?></div>
          </article>

          <aside class="insurance-advisor-native-detail">
            <?php if($selectedPolicy): ?>
              <div class="insurance-advisor-native-detail-head">
                <span><?php echo e($selectedPolicy->ends_at && now()->startOfDay()->diffInDays($selectedPolicy->ends_at->copy()->startOfDay(), false) <= 60 ? 'Por vencer' : $statusText($selectedPolicy->status)); ?></span>
                <small>Poliza seleccionada</small>
                <strong><?php echo e(strtoupper(mb_substr($selectedPolicy->insurer_name, 0, 3))); ?></strong>
              </div>
              <h2><?php echo e($selectedPatient?->full_name ?? 'Paciente sin nombre'); ?></h2>
              <p><?php echo e($selectedPolicy->policy_number); ?> / <?php echo e($selectedPolicy->insurer_name); ?></p>
              <div class="insurance-advisor-native-detail-grid">
                <div><span>Paciente usuario</span><strong><?php echo e($selectedPatient?->platform_number ?? '100000001'); ?><br><?php echo e($selectedPatient?->user?->username ?? 'paciente'); ?></strong></div>
                <div><span>Contacto</span><strong><?php echo e($selectedPatient?->email ?? 'claudia.salinas@example.com'); ?><br><?php echo e($selectedPatient?->phone ?? '5555550101'); ?></strong></div>
                <div><span>Deducible</span><strong>$<?php echo e(number_format((float) data_get($selectedPolicy->metadata, 'deductible', 18000), 0)); ?></strong></div>
                <div><span>Coaseguro</span><strong><?php echo e(data_get($selectedPolicy->metadata, 'coinsurance', '10%')); ?></strong></div>
                <div><span>Prima anual</span><strong>$<?php echo e(number_format((float) data_get($selectedPolicy->metadata, 'premium_annual', 42800), 0)); ?></strong></div>
                <div><span>Pago</span><strong><?php echo e(data_get($selectedPolicy->metadata, 'payment_status', 'Pendiente de renovacion')); ?></strong></div>
              </div>
              <div class="insurance-advisor-native-detail-actions">
                <form method="post" action="<?php echo e(route('insurance-advisor.policies.messages.store', $selectedPolicy)); ?>"><?php echo csrf_field(); ?><button type="submit">Enviar mensaje</button></form>
                <button
                  type="button"
                  data-open-renewal
                  data-action="<?php echo e(route('insurance-advisor.policies.renew', $selectedPolicy)); ?>"
                  data-patient="<?php echo e($selectedPatient?->full_name ?? 'Paciente asegurado'); ?>"
                  data-policy="<?php echo e($selectedPolicy->policy_number); ?>"
                  data-amount="<?php echo e((float) data_get($selectedPolicy->metadata, 'premium', 31750)); ?>"
                >Subir pago</button>
              </div>
              <p class="insurance-advisor-native-note"><strong>Sin renovaciones cargadas</strong><br>El siguiente pago actualizara el periodo.</p>
            <?php else: ?>
              <p class="empty-state">Selecciona una poliza para ver el detalle.</p>
            <?php endif; ?>
          </aside>
        </section>
      <?php elseif($section === 'due'): ?>
        <section class="insurance-advisor-native-panel">
          <div class="insurance-advisor-native-heading">
            <h2>Por vencer</h2>
            <span><?php echo e($dueSoonPoliciesCount); ?> por vencer</span>
          </div>
          <div class="insurance-advisor-native-policy-cards">
            <?php $__empty_1 = true; $__currentLoopData = $dueSoonPolicies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <article class="is-warning">
                <strong><?php echo e($policy->patient?->full_name ?? 'Paciente asegurado'); ?></strong>
                <span><?php echo e($policy->policy_number); ?> / Vence en <?php echo e($policy->ends_at ? (int) now()->startOfDay()->diffInDays($policy->ends_at->copy()->startOfDay(), false) : 0); ?> dias</span>
                <em>Por vencer</em>
                <div>
                  <button
                    type="button"
                    data-open-renewal
                    data-action="<?php echo e(route('insurance-advisor.policies.renew', $policy)); ?>"
                    data-patient="<?php echo e($policy->patient?->full_name ?? 'Paciente asegurado'); ?>"
                    data-policy="<?php echo e($policy->policy_number); ?>"
                    data-amount="<?php echo e((float) data_get($policy->metadata, 'premium', 31750)); ?>"
                  >Subir pago</button>
                  <button type="button">Avisar</button>
                </div>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p class="empty-state">No hay polizas por vencer.</p>
            <?php endif; ?>
          </div>
        </section>
      <?php elseif($section === 'expired'): ?>
        <section class="insurance-advisor-native-panel">
          <div class="insurance-advisor-native-heading">
            <h2>Vencidas</h2>
            <span><?php echo e($expiredPoliciesCount); ?> vencidas</span>
          </div>
          <div class="insurance-advisor-native-policy-cards">
            <?php $__empty_1 = true; $__currentLoopData = $expiredPolicies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <article class="is-danger">
                <strong><?php echo e($policy->patient?->full_name ?? 'Paciente asegurado'); ?></strong>
                <span><?php echo e($policy->policy_number); ?> / Vencida hace <?php echo e($policy->ends_at ? abs((int) $policy->ends_at->diffInDays(now())) : 0); ?> dias</span>
                <em>Vencida</em>
                <div>
                  <button
                    type="button"
                    data-open-renewal
                    data-action="<?php echo e(route('insurance-advisor.policies.renew', $policy)); ?>"
                    data-patient="<?php echo e($policy->patient?->full_name ?? 'Paciente asegurado'); ?>"
                    data-policy="<?php echo e($policy->policy_number); ?>"
                    data-amount="<?php echo e((float) data_get($policy->metadata, 'premium', 31750)); ?>"
                  >Subir pago</button>
                  <button type="button">Avisar</button>
                </div>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p class="empty-state">No hay polizas vencidas.</p>
            <?php endif; ?>
          </div>
        </section>
      <?php elseif($section === 'messages'): ?>
        <section class="insurance-advisor-native-panel">
          <div class="insurance-advisor-native-heading">
            <h2>Mensajes a asegurados</h2>
            <span><?php echo e($policyMessages->count()); ?></span>
          </div>
          <div class="insurance-advisor-native-advisor-alerts insurance-advisor-native-messages">
            <?php $__empty_1 = true; $__currentLoopData = $policyMessages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <article>
                <small><?php echo e($message['sent_at']); ?> &nbsp; Plataforma Dr. Sam &nbsp; <?php echo e($message['patient']); ?></small>
                <strong><?php echo e($message['subject']); ?></strong>
                <p><?php echo e($message['body']); ?></p>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p class="empty-state">No hay mensajes enviados.</p>
            <?php endif; ?>
          </div>
        </section>
      <?php elseif($section === 'alerts'): ?>
        <section class="insurance-advisor-native-panel">
          <div class="insurance-advisor-native-heading">
            <h2>Alertas del asesor</h2>
            <span><?php echo e($policyAlerts->count()); ?></span>
          </div>
          <div class="insurance-advisor-native-advisor-alerts">
            <?php $__empty_1 = true; $__currentLoopData = $policyAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <article>
                <small><?php echo e($alert['created_at']); ?> &nbsp; Plataforma Dr. Sam &nbsp; Asesor</small>
                <strong><?php echo e($alert['subject']); ?></strong>
                <p><?php echo e($alert['body']); ?></p>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p class="empty-state">No hay alertas de pólizas.</p>
            <?php endif; ?>
          </div>
        </section>
      <?php elseif($section === 'claims'): ?>
        <div class="insurance-advisor-native-claims-title">
          <div>
            <h2>Siniestros</h2>
            <p>Consulta y gestiona tus siniestros de reembolso.</p>
          </div>
          <button type="button" data-new-claim-toggle>+ Nuevo siniestro</button>
        </div>
        <section class="insurance-advisor-native-panel insurance-advisor-native-new-claim" data-new-claim-panel <?php if(! request()->boolean('new_claim') && ! $errors->any()): ?> hidden <?php endif; ?>>
          <div class="insurance-advisor-native-heading">
            <h2>Nuevo siniestro</h2>
          </div>
          <form method="post" action="<?php echo e(route('insurance-advisor.claims.store')); ?>">
            <?php echo csrf_field(); ?>
            <label>Póliza
              <select name="policy_id" required>
                <?php $__currentLoopData = $allPolicies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($policy->id); ?>" <?php if((string) old('policy_id', $selectedPolicy?->id) === (string) $policy->id): echo 'selected'; endif; ?>><?php echo e($policy->patient?->full_name ?? 'Paciente asegurado'); ?> / <?php echo e($policy->policy_number); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </label>
            <label>Tipo de trámite
              <select name="type" required>
                <option value="reimbursement" <?php if(old('type') === 'reimbursement'): echo 'selected'; endif; ?>>Reembolso</option>
                <option value="direct_payment" <?php if(old('type') === 'direct_payment'): echo 'selected'; endif; ?>>Pago directo a hospital</option>
              </select>
            </label>
            <label>Hospital<input name="hospital" value="<?php echo e(old('hospital')); ?>" required></label>
            <label>Fecha del evento<input name="event_date" type="date" value="<?php echo e(old('event_date')); ?>" required></label>
            <label>Monto estimado<input name="estimated_amount" type="number" min="0" step="0.01" value="<?php echo e(old('estimated_amount')); ?>" required></label>
            <label>Diagnóstico<input name="diagnosis" value="<?php echo e(old('diagnosis')); ?>" required></label>
            <button type="submit">Crear siniestro</button>
          </form>
        </section>
        <section class="insurance-advisor-native-panel" data-claims-list <?php if(request()->boolean('new_claim') || $errors->any()): ?> hidden <?php endif; ?>>
          <div class="insurance-advisor-native-heading">
            <h2>Listado de siniestros</h2>
            <span><?php echo e($claims->count()); ?></span>
          </div>
          <div class="insurance-advisor-native-claims">
            <div class="insurance-advisor-native-claim-columns" aria-hidden="true">
              <span>Siniestro</span><span>Asegurado</span><span>Fecha</span><span>Documentos pendientes</span><span>Estatus</span>
            </div>
            <?php $__currentLoopData = $claims; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $claim): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <article class="<?php echo e($loop->first ? 'is-open' : ''); ?>" data-claim-card>
                <button class="insurance-advisor-native-claim-row" type="button" data-claim-toggle aria-expanded="<?php echo e($loop->first ? 'true' : 'false'); ?>">
                  <div><strong><?php echo e($claim['folio']); ?></strong><span><?php echo e($claim['type']); ?></span></div>
                  <div><strong><?php echo e($claim['patient']); ?></strong><span><?php echo e($claim['hospital']); ?></span></div>
                  <div><?php echo e($claim['date']); ?></div>
                  <div><span class="insurance-advisor-native-status is-warning">Pendiente</span><small><?php echo e($claim['documents']); ?> por subir</small></div>
                  <div><span class="insurance-advisor-native-status is-ok"><?php echo e($claim['status']); ?></span><small data-claim-toggle-label><?php echo e($loop->first ? 'Ocultar detalle ^' : 'Ver detalle >'); ?></small></div>
                </button>
                <div class="insurance-advisor-native-claim-detail" data-claim-detail <?php if(! $loop->first): ?> hidden <?php endif; ?>>
                    <div class="insurance-advisor-native-claim-summary">
                      <div>
                    <span>Reembolso</span>
                    <h2><?php echo e($claim['folio']); ?> / <?php echo e($claim['patient']); ?></h2>
                    <p><?php echo e($claim['hospital']); ?> / <?php echo e($claim['date']); ?></p>
                      </div>
                      <strong><?php echo e($claim['documents']); ?> docs pendientes</strong>
                    </div>
                    <div class="insurance-advisor-native-claim-meta">
                      <div><span>Poliza</span><strong><?php echo e($claim['policy']->policy_number); ?></strong></div>
                      <div><span>Aseguradora</span><strong><?php echo e($claim['policy']->insurer_name); ?></strong></div>
                      <div><span>Diagnostico</span><strong><?php echo e($claim['diagnosis']); ?></strong></div>
                      <div><span>Monto estimado</span><strong>$<?php echo e(number_format($claim['amount'])); ?></strong></div>
                    </div>
                    <div class="insurance-advisor-native-claim-steps">
                      <div class="is-active"><strong>Documentacion</strong><span>Etapa actual</span></div>
                      <div><strong>En revision aseguradora</strong><span>Pendiente</span></div>
                      <div><strong>Resolucion / pago</strong><span>Pendiente</span></div>
                    </div>
                    <div class="insurance-advisor-native-claim-actions">
                      <button type="button">Solicitar documentos</button>
                      <button type="button">Tramitar alta hospitalaria</button>
                      <button type="button">Seguimiento</button>
                      <button type="button">Enviar a aseguradora</button>
                    </div>
                    <table class="insurance-advisor-native-documents">
                      <thead><tr><th>Documento</th><th>Requerido</th><th>Estatus</th><th>Archivo cargado</th><th>Subir archivo</th></tr></thead>
                      <tbody>
                        <?php $__currentLoopData = ['Identificacion oficial', 'Informe medico', 'Facturas CFDI', 'Comprobantes de pago', 'Estado de cuenta', 'Solicitud de reembolso']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                          <?php ($received = ! in_array($document, ['Comprobantes de pago', 'Solicitud de reembolso'], true)); ?>
                          <tr class="<?php echo e($received ? 'is-received' : 'is-pending'); ?>"><td><?php echo e($document); ?> *</td><td>Si</td><td><span class="insurance-advisor-native-status <?php echo e($received ? 'is-ok' : 'is-warning'); ?>"><?php echo e($received ? 'Recibido' : 'Pendiente'); ?></span></td><td><?php echo e($received ? Str::slug($document).'.pdf' : 'Sin archivo'); ?></td><td><input type="file"></td></tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                      </tbody>
                    </table>
                    <p class="insurance-advisor-native-claim-note"><?php echo e(now()->subDays(5)->format('d M Y')); ?> - El paciente solicito reembolso por gastos hospitalarios.</p>
                </div>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
          <a class="insurance-advisor-native-claims-footer" href="<?php echo e(route('insurance-advisor.dashboard', ['section' => 'claims'])); ?>">Ver todos los siniestros</a>
        </section>
      <?php endif; ?>
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
          <?php echo csrf_field(); ?>
          <label>Comprobante de pago<input name="payment_file" type="file" accept=".pdf,.jpg,.jpeg,.png" required></label>
          <label>Monto pagado<input name="amount" type="number" min="0" step="0.01" required></label>
          <label>Fecha de pago<input name="paid_at" type="date" value="<?php echo e(now()->format('Y-m-d')); ?>" required></label>
          <label class="is-wide">Notas<textarea name="notes" rows="3" placeholder="Referencia bancaria, banco o comentario del asesor"></textarea></label>
          <div class="insurance-advisor-renewal-actions is-wide">
            <button type="submit">Registrar pago y generar periodo</button>
            <button type="button" data-close-renewal>Cancelar</button>
          </div>
        </form>
      </section>
    </div>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
  <script>
    (() => {
      const modal = document.querySelector('[data-renewal-modal]');
      const form = document.querySelector('[data-renewal-form]');
      const policyLabel = document.querySelector('[data-renewal-policy]');
      if (!modal || !form || !policyLabel) return;
      let selectedCard = null;

      const close = () => {
        modal.hidden = true;
        form.reset();
        form.querySelector('[name="paid_at"]').value = '<?php echo e(now()->format('Y-m-d')); ?>';
        policyLabel.textContent = 'Selecciona una poliza';
        selectedCard?.classList.remove('is-selected');
        selectedCard = null;
      };

      document.addEventListener('click', (event) => {
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
      });
    })();
  </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Seguros GMM'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\insurance_advisor\dashboard.blade.php ENDPATH**/ ?>