

<?php $__env->startSection('body_class', 'formal-login-body'); ?>

<?php $__env->startSection('content'); ?>
  <?php
    $moduleLabels = [
      'superadmin' => 'Superadministrador',
      'institution' => 'Institución',
      'unit' => 'Unidad',
      'operational' => 'Área operativa',
      'digitalPharmacy' => 'Farmacia digital',
      'externalPharmacy' => 'Farmacia externa',
      'doctor' => 'Médico',
      'patient' => 'Paciente',
      'insurance' => 'Seguros GMM',
      'insuranceAdvisor' => 'Asesor de seguros',
      'messenger' => 'Mensajero',
      'provider' => 'Proveedor',
    ];
    $groupedUsers = $users->groupBy(fn ($user) => $user['module'] ?? $user->module);
    $demoPassword = 'Demo2026';
  ?>

  <div class="formal-login-screen">
    <header class="formal-login-header">
      <a class="formal-login-brand" href="<?php echo e(route('login')); ?>" aria-label="Dr. Sam inicio">
        <span class="formal-login-brand-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24">
            <path d="M6 4v5a5 5 0 0 0 10 0V4"></path>
            <path d="M9 4H5"></path>
            <path d="M17 4h-4"></path>
            <path d="M11 14v2a4 4 0 0 0 8 0v-3"></path>
            <circle cx="19" cy="10" r="2"></circle>
          </svg>
        </span>
        <strong>Dr. Sam</strong>
        <small>Plataforma externa de salud</small>
      </a>

      <div class="formal-login-profile">
        <span class="formal-login-avatar">DS</span>
        <div>
          <strong>Acceso de usuarios</strong>
          <small>Sesión segura</small>
        </div>
      </div>
    </header>

    <main class="formal-login-stage">
      <section class="formal-login-welcome" aria-labelledby="login-title">
        <p class="eyebrow">Ingreso unificado</p>
        <h1 id="login-title">¿A qué módulo quieres ingresar hoy?</h1>
        <p>Usa tus credenciales y Dr. Sam abrirá automáticamente el módulo autorizado: institución, unidad, operación, mensajero, proveedor, médico, paciente o seguros.</p>
        <div class="formal-login-modules" aria-label="Rutas disponibles">
          <?php $__currentLoopData = ['Institución', 'Unidad', 'Área operativa', 'Médico', 'Paciente', 'Seguros GMM', 'Mensajero', 'Proveedor']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <span><?php echo e($module); ?></span>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </section>
    </main>

    <section class="formal-login-composer" aria-labelledby="access-panel-title">
      <span class="formal-login-menu" aria-hidden="true">⋮</span>
      <div class="formal-login-composer-content">
        <div class="formal-login-composer-title">
          <h2 id="access-panel-title">Panel de ingreso</h2>
          <span>El usuario entra al módulo que tiene autorizado.</span>
        </div>

        <?php if(request()->boolean('session_expired')): ?>
          <div class="formal-login-session-notice" role="status">
            La sesión anterior venció. Ya generamos un acceso nuevo; puedes ingresar nuevamente.
          </div>
        <?php endif; ?>

        <form method="post" action="<?php echo e(route('demo-login.store')); ?>" class="formal-login-form" id="demo-login-form">
          <?php echo csrf_field(); ?>
          <label>
            <span>Usuario</span>
            <select name="username" id="demo-username" autocomplete="username" required>
              <?php $__currentLoopData = $groupedUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module => $moduleUsers): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <optgroup label="Módulo <?php echo e(strtolower($moduleLabels[$module] ?? $module)); ?>">
                  <?php $__currentLoopData = $moduleUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                      $username = $user['username'] ?? $user->username;
                      $name = $user['name'] ?? $user->name;
                    ?>
                    <option
                      value="<?php echo e($username); ?>"
                      data-name="<?php echo e($name); ?>"
                      data-module="<?php echo e($moduleLabels[$module] ?? ucfirst($module)); ?>"
                      <?php if(old('username') === $username): echo 'selected'; endif; ?>
                    ><?php echo e($name); ?> (<?php echo e($username); ?>)</option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </optgroup>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>

          <label>
            <span>Contraseña</span>
            <input name="password" id="demo-password" type="password" value="<?php echo e($demoPassword); ?>" autocomplete="current-password" <?php if(!$passwordless): echo 'required'; endif; ?>>
          </label>

          <button class="formal-login-submit" type="submit">Ingresar</button>

          <div class="formal-login-register" aria-label="Opciones de registro">
            <button type="button" title="Flujo disponible próximamente">Registrarme, soy paciente</button>
            <button type="button" title="Flujo disponible próximamente">Registrarme, soy médico</button>
          </div>

          <?php if($errors->any()): ?>
            <p class="formal-login-error" role="alert"><?php echo e($errors->first()); ?></p>
          <?php endif; ?>
        </form>

      </div>
    </section>
  </div>

  <script>
    (() => {
      const select = document.querySelector('#demo-username');
      const password = document.querySelector('#demo-password');
      const syncCredentials = () => {
        const option = select?.selectedOptions[0];
        if (!option) return;
        password.value = <?php echo json_encode($demoPassword, 15, 512) ?>;
      };

      select?.addEventListener('change', syncCredentials);
      syncCredentials();
    })();
  </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Dr. Sam - Acceso de usuarios'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views/auth/demo-login.blade.php ENDPATH**/ ?>