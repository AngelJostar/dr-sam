<?php
  $catalogCarouselItems = [
    'all' => ['Catálogo', 'catalog'],
    'active' => ['Activas', 'active'],
    'inactive' => ['Inactivas', 'inactive'],
  ];
?>

<?php echo $__env->make('unit._catalog_carousel', ['carouselMenu' => 'specialties', 'carouselFilter' => 'status', 'catalogCarouselItems' => $catalogCarouselItems], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\laragon\www\dr-sam\resources\views/unit/carousels/specialties.blade.php ENDPATH**/ ?>