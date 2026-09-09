<?php
  $insuranceMenu = [
    ['label' => 'Dashboard', 'route' => 'insurance.dashboard', 'match' => 'insurance.dashboard', 'icon' => 'D'],
    ['label' => 'Pacientes', 'route' => 'insurance.patients.index', 'match' => 'insurance.patients.*', 'icon' => 'P'],
    ['label' => 'Entregas', 'route' => 'insurance.deliveries.index', 'match' => 'insurance.deliveries.*', 'icon' => 'E'],
    ['label' => 'Hospitalizaciones', 'route' => 'insurance.hospitalizations.index', 'match' => 'insurance.hospitalizations.*', 'icon' => 'H'],
    ['label' => 'Facturacion', 'route' => 'insurance.invoices.index', 'match' => 'insurance.invoices.*', 'icon' => 'F'],
    ['label' => 'Autorizaciones', 'route' => 'insurance.authorizations.index', 'match' => 'insurance.authorizations.*', 'icon' => 'A'],
    ['label' => 'Documentos', 'route' => 'insurance.documents.index', 'match' => 'insurance.documents.*', 'icon' => 'Doc'],
    ['label' => 'Reportes', 'route' => 'insurance.reports.index', 'match' => 'insurance.reports.*', 'icon' => 'R'],
  ];

  if (($abilities['admin_users'] ?? false)) {
    $insuranceMenu[] = ['label' => 'Usuarios y roles', 'route' => 'insurance.admin.users', 'match' => 'insurance.admin.*', 'icon' => 'U'];
  }
?>

<aside class="insurance-health-native-sidebar">
  <nav class="insurance-health-native-sidebar-menu" aria-label="Menu aseguradora salud">
    <?php $__currentLoopData = $insuranceMenu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => request()->routeIs($item['match'])]); ?>" href="<?php echo e(route($item['route'])); ?>">
        <span><?php echo e($item['icon']); ?></span>
        <strong><?php echo e($item['label']); ?></strong>
      </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </nav>

  <a class="insurance-health-native-access" href="<?php echo e(route('dashboard')); ?>">
    <span>&lt;</span>
    Modulo de acceso
  </a>
</aside>
<?php /**PATH C:\laragon\www\dr-sam\resources\views\insurance\_sidebar.blade.php ENDPATH**/ ?>