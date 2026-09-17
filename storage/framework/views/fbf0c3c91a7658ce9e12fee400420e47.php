<?php
  $catalogCarouselItems = [
    'all' => ['Catálogo', 'catalog'],
    'active' => ['Activos', 'active'],
    'pending' => ['Pendientes', 'pending'],
    'inactive' => ['Inactivos', 'inactive'],
  ];
?>

<?php echo $__env->make('unit._catalog_carousel', ['carouselMenu' => 'doctors', 'carouselFilter' => 'status', 'catalogCarouselItems' => $catalogCarouselItems], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\laragon\www\dr-sam\resources\views/unit/carousels/doctors.blade.php ENDPATH**/ ?>