@php
  $stock = (int) ($product->available_stock ?? 0);
@endphp

<article class="orders-store-native-product-card">
  <div class="orders-store-native-product-art">
    <span>{{ mb_substr($product->name, 0, 12) }}</span>
    <i></i>
  </div>
  <div class="orders-store-native-product-body">
    <div class="orders-store-native-tags">
      <span>Promocion</span>
      <span>{{ $product->requires_prescription ? 'Con receta' : 'Sin receta' }}</span>
    </div>
    <h3>{{ $product->name }}</h3>
    <p>{{ $product->presentation ?? $product->generic_name ?? 'Sin presentacion registrada' }}</p>
    <strong>${{ number_format((float) $product->price, 2) }}</strong>
    <small>{{ $stock }} disponibles</small>
  </div>
  <form class="orders-store-native-product-actions" method="post" action="{{ route('orders.store') }}">
    @csrf
    <input type="hidden" name="pharmacy_product_id" value="{{ $product->id }}">
    <input type="hidden" name="quantity" value="1">
    <input type="hidden" name="delivery_mode" value="Entrega a domicilio">
    <input type="hidden" name="payment_method" value="Pago digital">
    <button type="button">Detalle</button>
    <button type="submit" @disabled($stock <= 0)>{{ $stock > 0 ? 'Agregar' : 'Sin stock' }}</button>
  </form>
</article>
