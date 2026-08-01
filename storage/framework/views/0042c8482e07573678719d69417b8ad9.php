<nav class="insurance-nav insurance-health-nav" aria-label="Navegacion aseguradora">
  <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => request()->routeIs('insurance.dashboard')]); ?>" href="<?php echo e(route('insurance.dashboard')); ?>">Dashboard</a>
  <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => request()->routeIs('insurance.patients.*')]); ?>" href="<?php echo e(route('insurance.patients.index')); ?>">Pacientes</a>
  <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => request()->routeIs('insurance.deliveries.*')]); ?>" href="<?php echo e(route('insurance.deliveries.index')); ?>">Entregas</a>
  <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => request()->routeIs('insurance.hospitalizations.*')]); ?>" href="<?php echo e(route('insurance.hospitalizations.index')); ?>">Hospitalizaciones</a>
  <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => request()->routeIs('insurance.invoices.*')]); ?>" href="<?php echo e(route('insurance.invoices.index')); ?>">Facturacion</a>
  <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => request()->routeIs('insurance.authorizations.*')]); ?>" href="<?php echo e(route('insurance.authorizations.index')); ?>">Autorizaciones</a>
  <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => request()->routeIs('insurance.documents.*')]); ?>" href="<?php echo e(route('insurance.documents.index')); ?>">Documentos</a>
  <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => request()->routeIs('insurance.reports.*')]); ?>" href="<?php echo e(route('insurance.reports.index')); ?>">Reportes</a>
  <?php if(($abilities['admin_users'] ?? false)): ?>
    <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => request()->routeIs('insurance.admin.*')]); ?>" href="<?php echo e(route('insurance.admin.users')); ?>">Usuarios y roles</a>
  <?php endif; ?>
</nav>
<?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views/insurance/_nav.blade.php ENDPATH**/ ?>