<?php
  $plans = $subscriptionSettings['plans'] ?? [];
?>

<div id="subscriptions" data-subscription-overview>
<section class="superadmin-subscription-cards" aria-label="Resumen de planes">
  <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <article>
      <small><?php echo e($plan['type'] === 'Gratis' ? 'GRATIS' : ($plan['id'] === 'complete' ? 'INDIVIDUAL' : ($plan['id'] === 'smart' ? 'CON IA' : ($plan['id'] === 'family' ? 'MULTIUSUARIO' : 'AVANZADO')))); ?></small>
      <h2><?php echo e($plan['name']); ?></h2>
      <strong><?php echo e($plan['price']); ?></strong>
      <p><?php echo e($plan['short_description']); ?></p>
    </article>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</section>

<section class="superadmin-native-catalog-card superadmin-subscription-table-card">
  <div class="superadmin-native-section-heading">
    <div><h2>Planes de suscripcion</h2><p><?php echo e(count($plans)); ?> planes configurados para usuarios pacientes de la plataforma.</p></div>
  </div>
  <div class="superadmin-native-catalog-scroll">
    <table class="superadmin-native-catalog-table">
      <thead><tr><th>Tipo de plan</th><th>Nombre recomendado</th><th>Descripcion acotada</th><th>Precio</th><th>Usuarios incluidos</th><th>Estatus</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <tr>
            <td><?php echo e($plan['type']); ?> <a class="superadmin-subscription-inline-edit" href="#subscription-price-<?php echo e($plan['id']); ?>">Editar</a></td>
            <td><strong><?php echo e($plan['name']); ?></strong><a class="superadmin-subscription-name-edit" href="#subscription-name-<?php echo e($plan['id']); ?>">Editar</a></td><td><?php echo e($plan['short_description']); ?></td>
            <td><?php echo e($plan['price']); ?><a class="superadmin-subscription-name-edit" href="#subscription-price-<?php echo e($plan['id']); ?>">Editar</a></td><td><?php echo e($plan['users_included']); ?></td><td><span class="superadmin-native-status-pill"><?php echo e($plan['status'] === 'active' ? 'Activo' : 'Inactivo'); ?></span></td>
            <td><button type="button" data-show-subscription-plan="<?php echo e($plan['id']); ?>">Ver detalle</button> <a href="#subscription-description-<?php echo e($plan['id']); ?>">Editar descripcion</a></td>
          </tr>
          <tr id="subscription-edit-<?php echo e($plan['id']); ?>" class="superadmin-subscription-editor-row">
            <td colspan="7">
              <details>
                <summary>Editar <?php echo e($plan['name']); ?></summary>
                <form method="post" action="<?php echo e(route('superadmin.settings.update', 'subscriptions')); ?>" class="superadmin-subscription-form">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="mode" value="plan"><input type="hidden" name="plan_id" value="<?php echo e($plan['id']); ?>">
                  <label>Tipo<input name="type" value="<?php echo e($plan['type']); ?>" required></label>
                  <label>Nombre<input name="name" value="<?php echo e($plan['name']); ?>" required></label>
                  <label>Precio<input name="price" value="<?php echo e($plan['price']); ?>" required></label>
                  <label>Usuarios incluidos<input name="users_included" value="<?php echo e($plan['users_included']); ?>" required></label>
                  <label class="is-wide">Descripcion acotada<textarea name="short_description" required><?php echo e($plan['short_description']); ?></textarea></label>
                  <label class="is-wide">Descripcion amplia<textarea name="long_description" required><?php echo e($plan['long_description']); ?></textarea></label>
                  <label>Estatus<select name="status"><option value="active" <?php if($plan['status'] === 'active'): echo 'selected'; endif; ?>>Activo</option><option value="inactive" <?php if($plan['status'] === 'inactive'): echo 'selected'; endif; ?>>Inactivo</option></select></label>
                  <button type="submit">Guardar plan</button>
                </form>
              </details>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
  </div>
</section>

<?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <section id="subscription-price-<?php echo e($plan['id']); ?>" class="superadmin-subscription-modal" aria-labelledby="subscription-price-title-<?php echo e($plan['id']); ?>">
    <a class="superadmin-subscription-modal-backdrop" href="#subscriptions" aria-label="Cerrar"></a>
    <div class="superadmin-subscription-modal-window" role="dialog" aria-modal="true">
      <header>
        <div>
          <h2 id="subscription-price-title-<?php echo e($plan['id']); ?>">Editar precio - <?php echo e($plan['name']); ?></h2>
          <p>Este precio se mostrara en el flujo de suscripcion del paciente.</p>
        </div>
        <a href="#subscriptions" aria-label="Cerrar">Ã—</a>
      </header>
      <form method="post" action="<?php echo e(route('superadmin.settings.update', 'subscriptions')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="mode" value="plan"><input type="hidden" name="plan_id" value="<?php echo e($plan['id']); ?>">
        <input type="hidden" name="type" value="<?php echo e($plan['type']); ?>"><input type="hidden" name="name" value="<?php echo e($plan['name']); ?>">
        <input type="hidden" name="users_included" value="<?php echo e($plan['users_included']); ?>"><input type="hidden" name="short_description" value="<?php echo e($plan['short_description']); ?>">
        <input type="hidden" name="long_description" value="<?php echo e($plan['long_description']); ?>"><input type="hidden" name="status" value="<?php echo e($plan['status']); ?>">
        <label>Precio<input name="price" value="<?php echo e($plan['price']); ?>" required autofocus></label>
        <div><a href="#subscriptions">Cancelar</a><button type="submit">Guardar precio</button></div>
      </form>
    </div>
  </section>
  <section id="subscription-name-<?php echo e($plan['id']); ?>" class="superadmin-subscription-modal" aria-labelledby="subscription-name-title-<?php echo e($plan['id']); ?>">
    <a class="superadmin-subscription-modal-backdrop" href="#subscriptions" aria-label="Cerrar"></a>
    <div class="superadmin-subscription-modal-window" role="dialog" aria-modal="true">
      <header>
        <div>
          <h2 id="subscription-name-title-<?php echo e($plan['id']); ?>">Editar nombre - <?php echo e($plan['name']); ?></h2>
          <p>Este nombre se mostrara en el flujo de suscripcion del paciente.</p>
        </div>
        <a href="#subscriptions" aria-label="Cerrar">Ã—</a>
      </header>
      <form method="post" action="<?php echo e(route('superadmin.settings.update', 'subscriptions')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="mode" value="plan"><input type="hidden" name="plan_id" value="<?php echo e($plan['id']); ?>">
        <input type="hidden" name="type" value="<?php echo e($plan['type']); ?>"><input type="hidden" name="price" value="<?php echo e($plan['price']); ?>">
        <input type="hidden" name="users_included" value="<?php echo e($plan['users_included']); ?>"><input type="hidden" name="short_description" value="<?php echo e($plan['short_description']); ?>">
        <input type="hidden" name="long_description" value="<?php echo e($plan['long_description']); ?>"><input type="hidden" name="status" value="<?php echo e($plan['status']); ?>">
        <label>Nombre recomendado<input name="name" value="<?php echo e($plan['name']); ?>" required autofocus></label>
        <div><a href="#subscriptions">Cancelar</a><button type="submit">Guardar nombre</button></div>
      </form>
    </div>
  </section>
  <section id="subscription-description-<?php echo e($plan['id']); ?>" class="superadmin-subscription-modal" aria-labelledby="subscription-description-title-<?php echo e($plan['id']); ?>">
    <a class="superadmin-subscription-modal-backdrop" href="#subscriptions" aria-label="Cerrar"></a>
    <div class="superadmin-subscription-modal-window" role="dialog" aria-modal="true">
      <header>
        <div>
          <h2 id="subscription-description-title-<?php echo e($plan['id']); ?>">Editar descripcion - <?php echo e($plan['name']); ?></h2>
          <p>Esta descripcion se mostrara en el flujo de suscripcion del paciente.</p>
        </div>
        <a href="#subscriptions" aria-label="Cerrar">&times;</a>
      </header>
      <form method="post" action="<?php echo e(route('superadmin.settings.update', 'subscriptions')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="mode" value="plan"><input type="hidden" name="plan_id" value="<?php echo e($plan['id']); ?>">
        <input type="hidden" name="type" value="<?php echo e($plan['type']); ?>"><input type="hidden" name="name" value="<?php echo e($plan['name']); ?>">
        <input type="hidden" name="price" value="<?php echo e($plan['price']); ?>"><input type="hidden" name="users_included" value="<?php echo e($plan['users_included']); ?>">
        <input type="hidden" name="long_description" value="<?php echo e($plan['long_description']); ?>"><input type="hidden" name="status" value="<?php echo e($plan['status']); ?>">
        <label>Descripcion acotada<textarea name="short_description" rows="4" required autofocus><?php echo e($plan['short_description']); ?></textarea></label>
        <div><a href="#subscriptions">Cancelar</a><button type="submit">Guardar descripcion</button></div>
      </form>
    </div>
  </section>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<section class="superadmin-native-catalog-card">
  <div class="superadmin-native-section-heading"><div><h2>Descripcion amplia de planes</h2><p>Detalle funcional de cada suscripcion.</p></div></div>
  <div class="superadmin-native-catalog-scroll">
    <table class="superadmin-native-catalog-table">
      <thead><tr><th>Tipo de plan</th><th>Nombre recomendado</th><th>Descripcion</th><th>Acciones</th></tr></thead>
      <tbody><?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr id="subscription-detail-<?php echo e($plan['id']); ?>"><td><?php echo e($plan['type']); ?></td><td><strong><?php echo e($plan['name']); ?></strong></td><td><?php echo e($plan['long_description']); ?></td><td><a href="#subscription-long-description-<?php echo e($plan['id']); ?>">Editar</a></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody>
    </table>
  </div>
</section>

<?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <section id="subscription-long-description-<?php echo e($plan['id']); ?>" class="superadmin-subscription-modal" aria-labelledby="subscription-long-description-title-<?php echo e($plan['id']); ?>">
    <a class="superadmin-subscription-modal-backdrop" href="#subscriptions" aria-label="Cerrar"></a>
    <div class="superadmin-subscription-modal-window" role="dialog" aria-modal="true">
      <header>
        <div>
          <h2 id="subscription-long-description-title-<?php echo e($plan['id']); ?>">Editar descripcion amplia - <?php echo e($plan['name']); ?></h2>
          <p>Esta descripcion se usara como detalle funcional del plan.</p>
        </div>
        <a href="#subscriptions" aria-label="Cerrar">&times;</a>
      </header>
      <form method="post" action="<?php echo e(route('superadmin.settings.update', 'subscriptions')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="mode" value="plan"><input type="hidden" name="plan_id" value="<?php echo e($plan['id']); ?>">
        <input type="hidden" name="type" value="<?php echo e($plan['type']); ?>"><input type="hidden" name="name" value="<?php echo e($plan['name']); ?>">
        <input type="hidden" name="price" value="<?php echo e($plan['price']); ?>"><input type="hidden" name="users_included" value="<?php echo e($plan['users_included']); ?>">
        <input type="hidden" name="short_description" value="<?php echo e($plan['short_description']); ?>"><input type="hidden" name="status" value="<?php echo e($plan['status']); ?>">
        <label>Descripcion amplia<textarea name="long_description" rows="6" required autofocus><?php echo e($plan['long_description']); ?></textarea></label>
        <div><a href="#subscriptions">Cancelar</a><button type="submit">Guardar descripcion</button></div>
      </form>
    </div>
  </section>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__currentLoopData = ['usage_policies' => ['Propuesta de Politicas de uso', 'Estas politicas regiran el uso operativo de la plataforma.'], 'terms_conditions' => ['Propuesta de Terminos y condiciones', 'Estos terminos y condiciones regiran la relacion entre usuarios y plataforma.']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document => [$title, $subtitle]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <section class="superadmin-native-catalog-card superadmin-subscription-legal" data-legal-editor>
    <div class="superadmin-native-section-heading">
      <div><h2><?php echo e($title); ?></h2><p><?php echo e($subtitle); ?></p></div>
      <button type="button" data-edit-legal>Editar</button>
      <div class="superadmin-subscription-legal-actions hidden" data-legal-actions>
        <button type="submit" form="subscription-legal-form-<?php echo e($document); ?>">Guardar cambios</button>
        <button type="button" data-cancel-legal>Cancelar</button>
      </div>
    </div>
    <div class="superadmin-subscription-legal-copy" data-legal-copy><?php echo nl2br(e($subscriptionSettings[$document] ?? '')); ?></div>
      <form id="subscription-legal-form-<?php echo e($document); ?>" class="superadmin-subscription-legal-form hidden" data-legal-form method="post" action="<?php echo e(route('superadmin.settings.update', 'subscriptions')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="mode" value="legal"><input type="hidden" name="document" value="<?php echo e($document); ?>">
        <textarea name="content" required><?php echo e($subscriptionSettings[$document] ?? ''); ?></textarea>
      </form>
  </section>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <?php
    $planUsers = $subscriptionUsers->where('plan_id', $plan['id'])->values();
    $cities = $planUsers->pluck('city')->filter()->unique()->sort()->values();
  ?>
  <section class="superadmin-subscription-users hidden" data-subscription-plan-panel="<?php echo e($plan['id']); ?>">
    <div class="superadmin-native-catalog-card">
      <div class="superadmin-native-section-heading">
        <div><h2>Usuarios con <?php echo e($plan['name']); ?></h2><p><span data-visible-user-count><?php echo e($planUsers->count()); ?></span> usuarios visibles - <?php echo e($plan['price']); ?></p></div>
        <div class="superadmin-subscription-user-actions">
          <button type="button" data-back-to-subscriptions>Regresar</button>
          <a href="<?php echo e(route('superadmin.subscriptions.users.csv', $plan['id'])); ?>">â†“ Descargar datos</a>
        </div>
      </div>
      <div class="superadmin-subscription-user-filters">
        <label class="superadmin-native-search"><span>Q</span><input data-subscription-user-search placeholder="Buscar nombre, apellido, usuario, CURP, ciudad o correo"></label>
        <label>Tipo<select data-subscription-user-type><option value="">Todos</option><option value="Paciente">Paciente</option></select></label>
        <label>Estatus<select data-subscription-user-status><option value="">Todos</option><option value="active">Activo</option><option value="inactive">Inactivo</option></select></label>
        <label>Ciudad<select data-subscription-user-city><option value="">Todas</option><?php $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($city); ?>"><?php echo e($city); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
      </div>
      <div class="superadmin-native-catalog-scroll superadmin-subscription-user-table-wrap">
        <table class="superadmin-native-catalog-table">
          <thead><tr><th>Nombre</th><th>Apellidos</th><th>Pais</th><th>Ciudad</th><th>No. usuario plataforma</th><th>Edad</th><th>CURP</th><th>Correo</th><th>Telefono</th><th>Tipo de usuario</th><th>Plan</th><th>Estatus</th></tr></thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $planUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $planUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr data-subscription-user-row data-search="<?php echo e(strtolower(implode(' ', $planUser))); ?>" data-type="<?php echo e($planUser['user_type']); ?>" data-status="<?php echo e($planUser['status']); ?>" data-city="<?php echo e($planUser['city']); ?>">
                <td><strong><?php echo e($planUser['first_name']); ?></strong></td><td><?php echo e($planUser['last_name']); ?></td><td><?php echo e($planUser['country']); ?></td><td><?php echo e($planUser['city']); ?></td><td><?php echo e($planUser['platform_number']); ?></td><td><?php echo e($planUser['age']); ?></td><td><?php echo e($planUser['curp']); ?></td><td><?php echo e($planUser['email']); ?></td><td><?php echo e($planUser['phone']); ?></td><td><?php echo e($planUser['user_type']); ?></td><td><?php echo e($planUser['plan_name']); ?></td><td><span class="superadmin-native-status-pill"><?php echo e($planUser['status'] === 'active' ? 'Activo' : 'Inactivo'); ?></span></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr data-empty-subscription-users><td colspan="12">Sin usuarios asignados a este plan.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

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
<?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\superadmin\_subscriptions.blade.php ENDPATH**/ ?>