<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title ?? 'Dr. Sam'); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('css/drsam.css')); ?>?v=<?php echo e(filemtime(public_path('css/drsam.css'))); ?>">
    <?php echo $__env->yieldPushContent('styles'); ?>
  </head>
  <body class="native-shell <?php echo $__env->yieldContent('body_class'); ?>">
    <main class="shell">
      <?php echo $__env->yieldContent('content'); ?>
    </main>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <script src="<?php echo e(asset('js/drsam-table-filters.js')); ?>?v=<?php echo e(filemtime(public_path('js/drsam-table-filters.js'))); ?>" defer></script>
  </body>
</html>
<?php /**PATH C:\laragon\www\dr-sam\resources\views\layouts\app.blade.php ENDPATH**/ ?>