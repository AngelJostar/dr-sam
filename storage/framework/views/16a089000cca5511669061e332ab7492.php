<?php
  $stock = (int) ($product->available_stock ?? 0);
?>

<article class="orders-store-native-product-card">
  <div class="orders-store-native-product-art">
    <span><?php echo e(mb_substr($product->name, 0, 12)); ?></span>
    <i></i>
  </div>
  <div class="orders-store-native-product-body">
    <div class="orders-store-native-tags">
      <span>Promocion</span>
      <span><?php echo e($product->requires_prescription ? 'Con receta' : 'Sin receta'); ?></span>
    </div>
    <h3><?php echo e($product->name); ?></h3>
    <p><?php echo e($product->presentation ?? $product->generic_name ?? 'Sin presentacion registrada'); ?></p>
    <strong>$<?php echo e(number_format((float) $product->price, 2)); ?></strong>
    <small><?php echo e($stock); ?> disponibles</small>
  </div>
  <form class="orders-store-native-product-actions" method="post" action="<?php echo e(route('orders.store')); ?>">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="pharmacy_product_id" value="<?php echo e($product->id); ?>">
    <input type="hidden" name="quantity" value="1">
    <input type="hidden" name="delivery_mode" value="Entrega a domicilio">
    <input type="hidden" name="payment_method" value="Pago digital">
    <button type="button">Detalle</button>
    <button type="submit" <?php if($stock <= 0): echo 'disabled'; endif; ?>><?php echo e($stock > 0 ? 'Agregar' : 'Sin stock'); ?></button>
  </form>
</article>
<?php /**PATH C:\laragon\www\dr-sam\resources\views\orders\partials\store-product-card.blade.php ENDPATH**/ ?>