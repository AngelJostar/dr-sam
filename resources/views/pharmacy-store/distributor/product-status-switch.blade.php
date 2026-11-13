<form method="POST" action="{{ route('provider.pharmacy.products.status', $product) }}" class="distributor-status-form" data-distributor-status-form>
    @csrf
    @method('PATCH')
    <input type="hidden" name="status" value="{{ $product->status === 'active' ? 'inactive' : 'active' }}">
    <button type="submit" role="switch" class="distributor-status-switch" aria-checked="{{ $product->status === 'active' ? 'true' : 'false' }}"
        aria-label="Activación de {{ $product->name }}" title="{{ $product->status === 'active' ? 'Inactivar producto' : 'Activar producto' }}"></button>
    <span>{{ $product->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
</form>
