<?php if(session('status')): ?>
  <div class="notice success"><?php echo e(session('status')); ?></div>
<?php endif; ?>

<?php if($errors->any()): ?>
  <div class="notice danger">
    <strong>Revisa la informacion capturada.</strong>
    <ul>
      <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><?php echo e($error); ?></li>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
  </div>
<?php endif; ?>
<?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views/insurance/_flash.blade.php ENDPATH**/ ?>