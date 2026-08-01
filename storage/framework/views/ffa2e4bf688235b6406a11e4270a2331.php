<?php $__env->startSection('body_class', 'insurance-health-native-body'); ?>

<?php $__env->startSection('content'); ?>
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    <?php echo $__env->make('insurance._sidebar', ['abilities' => ['admin_users' => true]], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="insurance-health-native-workspace">
      <section class="page-heading insurance-section-hero insurance-admin-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Administracion</p>
          <h1>Usuarios, roles y permisos</h1>
          <p>Catalogo de roles operativos del modulo aseguradora y permisos asignados.</p>
        </div>
      </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-admin-panel">
      <div class="section-title"><h2>Usuarios</h2><span><?php echo e($users->count()); ?></span></div>
      <div class="compact-list">
        <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div>
            <strong><?php echo e($user->name); ?></strong>
            <span><?php echo e($user->username); ?> / <?php echo e($user->role); ?> / <?php echo e($user->status); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </article>

    <article class="insurance-panel insurance-admin-panel">
      <div class="section-title"><h2>Roles</h2><span><?php echo e($roles->count()); ?></span></div>
      <div class="compact-list">
        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div>
            <strong><?php echo e($role->name); ?></strong>
            <span><?php echo e($role->permissions->pluck('key')->implode(', ')); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </article>
  </section>

  <section class="insurance-panel insurance-admin-panel insurance-permissions-panel">
    <div class="section-title"><h2>Permisos disponibles</h2><span><?php echo e($permissions->count()); ?></span></div>
    <div class="permission-grid">
      <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <span><?php echo e($permission->key); ?> - <?php echo e($permission->name); ?></span>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
      </section>
    </main>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Usuarios aseguradora'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views/insurance/admin/users.blade.php ENDPATH**/ ?>