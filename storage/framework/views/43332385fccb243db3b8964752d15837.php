<?php
  $catalogCarouselItems = [
    'all' => ['Catálogo', 'catalog'],
    'operation' => ['Operación', 'areas'],
    'clinical' => ['Clínicos', 'doctors'],
    'pharmacy' => ['Farmacia', 'pharmacy'],
  ];
?>

<?php echo $__env->make('unit._catalog_carousel', ['carouselMenu' => 'catalog', 'carouselFilter' => 'group', 'catalogCarouselItems' => $catalogCarouselItems], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\laragon\www\dr-sam\resources\views/unit/carousels/catalog.blade.php ENDPATH**/ ?>