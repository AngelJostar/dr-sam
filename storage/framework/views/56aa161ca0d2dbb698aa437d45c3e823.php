<?php
  $active = $active ?? 'dashboard';
  $sidebarItems = [
    ['key' => 'dashboard', 'icon' => '#', 'label' => 'Dashboard', 'href' => route('superadmin.dashboard')],
    ['key' => 'users', 'icon' => 'U', 'label' => 'Catalogo de Usuarios de la plataforma', 'href' => route('superadmin.catalog', 'users')],
    ['key' => 'institutions', 'icon' => 'I', 'label' => 'Catalogo de instituciones', 'href' => route('superadmin.catalog', 'institutions')],
    ['key' => 'doctors', 'icon' => 'M', 'label' => 'Catalogo de medicos', 'href' => route('superadmin.catalog', 'doctors')],
    ['key' => 'providers', 'icon' => 'V', 'label' => 'Catalogo de Vendedores', 'href' => route('superadmin.catalog', 'providers')],
    ['key' => 'insurance-carriers', 'icon' => 'A', 'label' => 'Catalogo de Aseguradoras', 'href' => route('superadmin.catalog', 'insurance-carriers')],
    ['key' => 'insurance-advisors', 'icon' => 'As', 'label' => 'Catalogo de Asesores de Seguros', 'href' => route('superadmin.catalog', 'insurance-advisors')],
    ['key' => 'specialties', 'icon' => 'E', 'label' => 'Catalogo de especialidades', 'href' => route('superadmin.catalog', 'specialties')],
    ['key' => 'medications', 'icon' => 'Rx', 'label' => 'Catalogo Universal de Medicamentos', 'href' => route('superadmin.catalog', 'medications')],
    ['key' => 'medical-devices', 'icon' => 'Dm', 'label' => 'Catalogo de dispositivos medicos', 'href' => route('superadmin.catalog', 'medical-devices')],
    ['key' => 'patients', 'icon' => 'P', 'label' => 'Catalogo de pacientes', 'href' => route('superadmin.catalog', 'patients')],
    ['key' => 'hospitals', 'icon' => 'H', 'label' => 'Listado de Hospitales Privados', 'href' => route('superadmin.catalog', 'hospitals')],
    ['key' => 'subscriptions', 'icon' => 'S', 'label' => 'Suscripciones', 'href' => route('superadmin.catalog', 'subscriptions')],
    ['key' => 'prescription-format', 'icon' => 'F', 'label' => 'Formato de receta medica', 'href' => route('superadmin.catalog', 'prescription-format')],
    ['key' => 'mix-request-format', 'icon' => 'Mx', 'label' => 'Formato de solicitud de mezcla', 'href' => route('superadmin.catalog', 'mix-request-format')],
    ['key' => 'advertising', 'icon' => 'Ad', 'label' => 'Publicidad', 'href' => route('superadmin.catalog', 'advertising')],
    ['key' => 'modules', 'icon' => 'Mo', 'label' => 'Modulos', 'href' => route('superadmin.catalog', 'modules')],
    ['key' => 'access', 'icon' => 'K', 'label' => 'Acceso', 'href' => route('superadmin.catalog', 'access')],
  ];
?>

<aside class="superadmin-native-sidebar" aria-label="Navegacion superadministrador">
  <div class="superadmin-native-brand">
    <span aria-hidden="true">OK</span>
    <div>
      <strong>Superadministrador</strong>
      <small>Gobierno modular</small>
    </div>
  </div>

  <nav class="superadmin-native-menu">
    <?php $__currentLoopData = $sidebarItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $active === $item['key']]); ?>" href="<?php echo e($item['href']); ?>">
        <span aria-hidden="true"><?php echo e($item['icon']); ?></span>
        <?php echo e($item['label']); ?>

      </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </nav>
</aside>
<?php /**PATH C:\laragon\www\dr-sam\resources\views/superadmin/_sidebar.blade.php ENDPATH**/ ?>