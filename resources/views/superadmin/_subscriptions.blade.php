@php
  $plans = $subscriptionSettings['plans'] ?? [];
@endphp

<div id="subscriptions" data-subscription-overview>
<section class="superadmin-subscription-cards" aria-label="Resumen de planes">
  @foreach ($plans as $plan)
    <article>
      <small>{{ $plan['type'] === 'Gratis' ? 'GRATIS' : ($plan['id'] === 'complete' ? 'INDIVIDUAL' : ($plan['id'] === 'smart' ? 'CON IA' : ($plan['id'] === 'family' ? 'MULTIUSUARIO' : 'AVANZADO'))) }}</small>
      <h2>{{ $plan['name'] }}</h2>
      <strong>{{ $plan['price'] }}</strong>
      <p>{{ $plan['short_description'] }}</p>
    </article>
  @endforeach
</section>

<section class="superadmin-native-catalog-card superadmin-subscription-table-card">
  <div class="superadmin-native-section-heading">
    <div><h2>Planes de suscripcion</h2><p>{{ count($plans) }} planes configurados para usuarios pacientes de la plataforma.</p></div>
  </div>
  <div class="superadmin-native-catalog-scroll">
    <table class="superadmin-native-catalog-table">
      <thead><tr><th>Tipo de plan</th><th>Nombre recomendado</th><th>Descripcion acotada</th><th>Precio</th><th>Usuarios incluidos</th><th>Estatus</th><th>Acciones</th></tr></thead>
      <tbody>
        @foreach ($plans as $plan)
          <tr>
            <td>{{ $plan['type'] }} <a class="superadmin-subscription-inline-edit" href="#subscription-price-{{ $plan['id'] }}">Editar</a></td>
            <td><strong>{{ $plan['name'] }}</strong><a class="superadmin-subscription-name-edit" href="#subscription-name-{{ $plan['id'] }}">Editar</a></td><td>{{ $plan['short_description'] }}</td>
            <td>{{ $plan['price'] }}<a class="superadmin-subscription-name-edit" href="#subscription-price-{{ $plan['id'] }}">Editar</a></td><td>{{ $plan['users_included'] }}</td><td><span class="superadmin-native-status-pill">{{ $plan['status'] === 'active' ? 'Activo' : 'Inactivo' }}</span></td>
            <td><button type="button" data-show-subscription-plan="{{ $plan['id'] }}">Ver detalle</button> <a href="#subscription-description-{{ $plan['id'] }}">Editar descripcion</a></td>
          </tr>
          <tr id="subscription-edit-{{ $plan['id'] }}" class="superadmin-subscription-editor-row">
            <td colspan="7">
              <details>
                <summary>Editar {{ $plan['name'] }}</summary>
                <form method="post" action="{{ route('superadmin.settings.update', 'subscriptions') }}" class="superadmin-subscription-form">
                  @csrf
                  <input type="hidden" name="mode" value="plan"><input type="hidden" name="plan_id" value="{{ $plan['id'] }}">
                  <label>Tipo<input name="type" value="{{ $plan['type'] }}" required></label>
                  <label>Nombre<input name="name" value="{{ $plan['name'] }}" required></label>
                  <label>Precio<input name="price" value="{{ $plan['price'] }}" required></label>
                  <label>Usuarios incluidos<input name="users_included" value="{{ $plan['users_included'] }}" required></label>
                  <label class="is-wide">Descripcion acotada<textarea name="short_description" required>{{ $plan['short_description'] }}</textarea></label>
                  <label class="is-wide">Descripcion amplia<textarea name="long_description" required>{{ $plan['long_description'] }}</textarea></label>
                  <label>Estatus<select name="status"><option value="active" @selected($plan['status'] === 'active')>Activo</option><option value="inactive" @selected($plan['status'] === 'inactive')>Inactivo</option></select></label>
                  <button type="submit">Guardar plan</button>
                </form>
              </details>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>

@foreach ($plans as $plan)
  <section id="subscription-price-{{ $plan['id'] }}" class="superadmin-subscription-modal" aria-labelledby="subscription-price-title-{{ $plan['id'] }}">
    <a class="superadmin-subscription-modal-backdrop" href="#subscriptions" aria-label="Cerrar"></a>
    <div class="superadmin-subscription-modal-window" role="dialog" aria-modal="true">
      <header>
        <div>
          <h2 id="subscription-price-title-{{ $plan['id'] }}">Editar precio - {{ $plan['name'] }}</h2>
          <p>Este precio se mostrara en el flujo de suscripcion del paciente.</p>
        </div>
        <a href="#subscriptions" aria-label="Cerrar">Ã—</a>
      </header>
      <form method="post" action="{{ route('superadmin.settings.update', 'subscriptions') }}">
        @csrf
        <input type="hidden" name="mode" value="plan"><input type="hidden" name="plan_id" value="{{ $plan['id'] }}">
        <input type="hidden" name="type" value="{{ $plan['type'] }}"><input type="hidden" name="name" value="{{ $plan['name'] }}">
        <input type="hidden" name="users_included" value="{{ $plan['users_included'] }}"><input type="hidden" name="short_description" value="{{ $plan['short_description'] }}">
        <input type="hidden" name="long_description" value="{{ $plan['long_description'] }}"><input type="hidden" name="status" value="{{ $plan['status'] }}">
        <label>Precio<input name="price" value="{{ $plan['price'] }}" required autofocus></label>
        <div><a href="#subscriptions">Cancelar</a><button type="submit">Guardar precio</button></div>
      </form>
    </div>
  </section>
  <section id="subscription-name-{{ $plan['id'] }}" class="superadmin-subscription-modal" aria-labelledby="subscription-name-title-{{ $plan['id'] }}">
    <a class="superadmin-subscription-modal-backdrop" href="#subscriptions" aria-label="Cerrar"></a>
    <div class="superadmin-subscription-modal-window" role="dialog" aria-modal="true">
      <header>
        <div>
          <h2 id="subscription-name-title-{{ $plan['id'] }}">Editar nombre - {{ $plan['name'] }}</h2>
          <p>Este nombre se mostrara en el flujo de suscripcion del paciente.</p>
        </div>
        <a href="#subscriptions" aria-label="Cerrar">Ã—</a>
      </header>
      <form method="post" action="{{ route('superadmin.settings.update', 'subscriptions') }}">
        @csrf
        <input type="hidden" name="mode" value="plan"><input type="hidden" name="plan_id" value="{{ $plan['id'] }}">
        <input type="hidden" name="type" value="{{ $plan['type'] }}"><input type="hidden" name="price" value="{{ $plan['price'] }}">
        <input type="hidden" name="users_included" value="{{ $plan['users_included'] }}"><input type="hidden" name="short_description" value="{{ $plan['short_description'] }}">
        <input type="hidden" name="long_description" value="{{ $plan['long_description'] }}"><input type="hidden" name="status" value="{{ $plan['status'] }}">
        <label>Nombre recomendado<input name="name" value="{{ $plan['name'] }}" required autofocus></label>
        <div><a href="#subscriptions">Cancelar</a><button type="submit">Guardar nombre</button></div>
      </form>
    </div>
  </section>
  <section id="subscription-description-{{ $plan['id'] }}" class="superadmin-subscription-modal" aria-labelledby="subscription-description-title-{{ $plan['id'] }}">
    <a class="superadmin-subscription-modal-backdrop" href="#subscriptions" aria-label="Cerrar"></a>
    <div class="superadmin-subscription-modal-window" role="dialog" aria-modal="true">
      <header>
        <div>
          <h2 id="subscription-description-title-{{ $plan['id'] }}">Editar descripcion - {{ $plan['name'] }}</h2>
          <p>Esta descripcion se mostrara en el flujo de suscripcion del paciente.</p>
        </div>
        <a href="#subscriptions" aria-label="Cerrar">&times;</a>
      </header>
      <form method="post" action="{{ route('superadmin.settings.update', 'subscriptions') }}">
        @csrf
        <input type="hidden" name="mode" value="plan"><input type="hidden" name="plan_id" value="{{ $plan['id'] }}">
        <input type="hidden" name="type" value="{{ $plan['type'] }}"><input type="hidden" name="name" value="{{ $plan['name'] }}">
        <input type="hidden" name="price" value="{{ $plan['price'] }}"><input type="hidden" name="users_included" value="{{ $plan['users_included'] }}">
        <input type="hidden" name="long_description" value="{{ $plan['long_description'] }}"><input type="hidden" name="status" value="{{ $plan['status'] }}">
        <label>Descripcion acotada<textarea name="short_description" rows="4" required autofocus>{{ $plan['short_description'] }}</textarea></label>
        <div><a href="#subscriptions">Cancelar</a><button type="submit">Guardar descripcion</button></div>
      </form>
    </div>
  </section>
@endforeach

<section class="superadmin-native-catalog-card">
  <div class="superadmin-native-section-heading"><div><h2>Descripcion amplia de planes</h2><p>Detalle funcional de cada suscripcion.</p></div></div>
  <div class="superadmin-native-catalog-scroll">
    <table class="superadmin-native-catalog-table">
      <thead><tr><th>Tipo de plan</th><th>Nombre recomendado</th><th>Descripcion</th><th>Acciones</th></tr></thead>
      <tbody>@foreach ($plans as $plan)<tr id="subscription-detail-{{ $plan['id'] }}"><td>{{ $plan['type'] }}</td><td><strong>{{ $plan['name'] }}</strong></td><td>{{ $plan['long_description'] }}</td><td><a href="#subscription-long-description-{{ $plan['id'] }}">Editar</a></td></tr>@endforeach</tbody>
    </table>
  </div>
</section>

@foreach ($plans as $plan)
  <section id="subscription-long-description-{{ $plan['id'] }}" class="superadmin-subscription-modal" aria-labelledby="subscription-long-description-title-{{ $plan['id'] }}">
    <a class="superadmin-subscription-modal-backdrop" href="#subscriptions" aria-label="Cerrar"></a>
    <div class="superadmin-subscription-modal-window" role="dialog" aria-modal="true">
      <header>
        <div>
          <h2 id="subscription-long-description-title-{{ $plan['id'] }}">Editar descripcion amplia - {{ $plan['name'] }}</h2>
          <p>Esta descripcion se usara como detalle funcional del plan.</p>
        </div>
        <a href="#subscriptions" aria-label="Cerrar">&times;</a>
      </header>
      <form method="post" action="{{ route('superadmin.settings.update', 'subscriptions') }}">
        @csrf
        <input type="hidden" name="mode" value="plan"><input type="hidden" name="plan_id" value="{{ $plan['id'] }}">
        <input type="hidden" name="type" value="{{ $plan['type'] }}"><input type="hidden" name="name" value="{{ $plan['name'] }}">
        <input type="hidden" name="price" value="{{ $plan['price'] }}"><input type="hidden" name="users_included" value="{{ $plan['users_included'] }}">
        <input type="hidden" name="short_description" value="{{ $plan['short_description'] }}"><input type="hidden" name="status" value="{{ $plan['status'] }}">
        <label>Descripcion amplia<textarea name="long_description" rows="6" required autofocus>{{ $plan['long_description'] }}</textarea></label>
        <div><a href="#subscriptions">Cancelar</a><button type="submit">Guardar descripcion</button></div>
      </form>
    </div>
  </section>
@endforeach

@foreach (['usage_policies' => ['Propuesta de Politicas de uso', 'Estas politicas regiran el uso operativo de la plataforma.'], 'terms_conditions' => ['Propuesta de Terminos y condiciones', 'Estos terminos y condiciones regiran la relacion entre usuarios y plataforma.']] as $document => [$title, $subtitle])
  <section class="superadmin-native-catalog-card superadmin-subscription-legal" data-legal-editor>
    <div class="superadmin-native-section-heading">
      <div><h2>{{ $title }}</h2><p>{{ $subtitle }}</p></div>
      <button type="button" data-edit-legal>Editar</button>
      <div class="superadmin-subscription-legal-actions hidden" data-legal-actions>
        <button type="submit" form="subscription-legal-form-{{ $document }}">Guardar cambios</button>
        <button type="button" data-cancel-legal>Cancelar</button>
      </div>
    </div>
    <div class="superadmin-subscription-legal-copy" data-legal-copy>{!! nl2br(e($subscriptionSettings[$document] ?? '')) !!}</div>
      <form id="subscription-legal-form-{{ $document }}" class="superadmin-subscription-legal-form hidden" data-legal-form method="post" action="{{ route('superadmin.settings.update', 'subscriptions') }}">
        @csrf
        <input type="hidden" name="mode" value="legal"><input type="hidden" name="document" value="{{ $document }}">
        <textarea name="content" required>{{ $subscriptionSettings[$document] ?? '' }}</textarea>
      </form>
  </section>
@endforeach
</div>

@foreach ($plans as $plan)
  @php
    $planUsers = $subscriptionUsers->where('plan_id', $plan['id'])->values();
    $cities = $planUsers->pluck('city')->filter()->unique()->sort()->values();
  @endphp
  <section class="superadmin-subscription-users hidden" data-subscription-plan-panel="{{ $plan['id'] }}">
    <div class="superadmin-native-catalog-card">
      <div class="superadmin-native-section-heading">
        <div><h2>Usuarios con {{ $plan['name'] }}</h2><p><span data-visible-user-count>{{ $planUsers->count() }}</span> usuarios visibles - {{ $plan['price'] }}</p></div>
        <div class="superadmin-subscription-user-actions">
          <button type="button" data-back-to-subscriptions>Regresar</button>
          <a href="{{ route('superadmin.subscriptions.users.csv', $plan['id']) }}">â†“ Descargar datos</a>
        </div>
      </div>
      <div class="superadmin-subscription-user-filters">
        <label class="superadmin-native-search"><span>Q</span><input data-subscription-user-search placeholder="Buscar nombre, apellido, usuario, CURP, ciudad o correo"></label>
        <label>Tipo<select data-subscription-user-type><option value="">Todos</option><option value="Paciente">Paciente</option></select></label>
        <label>Estatus<select data-subscription-user-status><option value="">Todos</option><option value="active">Activo</option><option value="inactive">Inactivo</option></select></label>
        <label>Ciudad<select data-subscription-user-city><option value="">Todas</option>@foreach($cities as $city)<option value="{{ $city }}">{{ $city }}</option>@endforeach</select></label>
      </div>
      <div class="superadmin-native-catalog-scroll superadmin-subscription-user-table-wrap">
        <table class="superadmin-native-catalog-table">
          <thead><tr><th>Nombre</th><th>Apellidos</th><th>Pais</th><th>Ciudad</th><th>No. usuario plataforma</th><th>Edad</th><th>CURP</th><th>Correo</th><th>Telefono</th><th>Tipo de usuario</th><th>Plan</th><th>Estatus</th></tr></thead>
          <tbody>
            @forelse ($planUsers as $planUser)
              <tr data-subscription-user-row data-search="{{ strtolower(implode(' ', $planUser)) }}" data-type="{{ $planUser['user_type'] }}" data-status="{{ $planUser['status'] }}" data-city="{{ $planUser['city'] }}">
                <td><strong>{{ $planUser['first_name'] }}</strong></td><td>{{ $planUser['last_name'] }}</td><td>{{ $planUser['country'] }}</td><td>{{ $planUser['city'] }}</td><td>{{ $planUser['platform_number'] }}</td><td>{{ $planUser['age'] }}</td><td>{{ $planUser['curp'] }}</td><td>{{ $planUser['email'] }}</td><td>{{ $planUser['phone'] }}</td><td>{{ $planUser['user_type'] }}</td><td>{{ $planUser['plan_name'] }}</td><td><span class="superadmin-native-status-pill">{{ $planUser['status'] === 'active' ? 'Activo' : 'Inactivo' }}</span></td>
              </tr>
            @empty
              <tr data-empty-subscription-users><td colspan="12">Sin usuarios asignados a este plan.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>
@endforeach

<script>
  (() => {
    const overview = document.querySelector('[data-subscription-overview]');
    const panels = [...document.querySelectorAll('[data-subscription-plan-panel]')];
    document.querySelectorAll('[data-show-subscription-plan]').forEach((button) => button.addEventListener('click', () => {
      overview.classList.add('hidden');
      panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.subscriptionPlanPanel !== button.dataset.showSubscriptionPlan));
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }));
    document.querySelectorAll('[data-back-to-subscriptions]').forEach((button) => button.addEventListener('click', () => {
      panels.forEach((panel) => panel.classList.add('hidden'));
      overview.classList.remove('hidden');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }));
    document.querySelectorAll('[data-legal-editor]').forEach((editor) => {
      const editButton = editor.querySelector('[data-edit-legal]');
      const actions = editor.querySelector('[data-legal-actions]');
      const copy = editor.querySelector('[data-legal-copy]');
      const form = editor.querySelector('[data-legal-form]');
      const cancelButton = editor.querySelector('[data-cancel-legal]');
      const setEditing = (editing) => {
        editButton.classList.toggle('hidden', editing);
        actions.classList.toggle('hidden', !editing);
        copy.classList.toggle('hidden', editing);
        form.classList.toggle('hidden', !editing);
        if (editing) form.querySelector('textarea').focus();
      };
      editButton.addEventListener('click', () => setEditing(true));
      cancelButton.addEventListener('click', () => setEditing(false));
    });
    panels.forEach((panel) => {
      const search = panel.querySelector('[data-subscription-user-search]');
      const type = panel.querySelector('[data-subscription-user-type]');
      const status = panel.querySelector('[data-subscription-user-status]');
      const city = panel.querySelector('[data-subscription-user-city]');
      const rows = [...panel.querySelectorAll('[data-subscription-user-row]')];
      const count = panel.querySelector('[data-visible-user-count]');
      const filter = () => {
        const term = search.value.trim().toLowerCase();
        let visible = 0;
        rows.forEach((row) => {
          const show = (!term || row.dataset.search.includes(term)) && (!type.value || row.dataset.type === type.value) && (!status.value || row.dataset.status === status.value) && (!city.value || row.dataset.city === city.value);
          row.hidden = !show;
          if (show) visible++;
        });
        count.textContent = visible;
      };
      [search, type, status, city].forEach((control) => control.addEventListener(control.tagName === 'INPUT' ? 'input' : 'change', filter));
    });
  })();
</script>
