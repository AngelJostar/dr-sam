<?php
  $catalogCarouselItems = [
    'all' => ['Catálogo', 'catalog'],
    'consulting' => ['Consultorios', 'doctors'],
    'infusion' => ['Salas de infusión', 'areas'],
    'operating' => ['Quirófanos', 'areas'],
    'uci' => ['UCI', 'areas'],
    'uti' => ['UTI', 'areas'],
    'recovery' => ['Recuperación', 'areas'],
  ];
?>

<?php echo $__env->make('unit._catalog_carousel', ['carouselMenu' => 'procedure-areas', 'carouselFilter' => 'procedure', 'catalogCarouselItems' => $catalogCarouselItems], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\laragon\www\dr-sam\resources\views/unit/carousels/procedure-areas.blade.php ENDPATH**/ ?>