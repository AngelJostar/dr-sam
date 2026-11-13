@php
    $editorId = $product?->id ?? 'new';
    $requestedEditor = old('_editor') ?? request('edit') ?? (request('section') === 'create' ? 'new' : null);
    $autoOpen = (string) $requestedEditor === (string) $editorId;
@endphp
<dialog id="distributor-product-{{ $editorId }}" class="distributor-dialog distributor-product-dialog" aria-labelledby="distributor-product-title-{{ $editorId }}" @if($autoOpen) data-distributor-auto-open @endif>
    <header><div><h2 id="distributor-product-title-{{ $editorId }}">{{ $product ? 'Editar presentación' : 'Nuevo producto' }}</h2><p>{{ $product?->name ?? 'Completa los datos para agregarlo a tu catálogo.' }}</p></div><button type="button" class="distributor-close" data-close-distributor-dialog aria-label="Cerrar formulario">×</button></header>
    <div class="distributor-dialog-content">
        @if($autoOpen && $errors->any())<div class="distributor-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form id="distributor-product-form-{{ $editorId }}" class="pharmacy-form" method="POST" enctype="multipart/form-data" action="{{ $product ? route('provider.pharmacy.products.update', $product) : route('provider.pharmacy.products.store') }}">
            @csrf @if($product) @method('PATCH') @endif
            <input type="hidden" name="_editor" value="{{ $editorId }}">
            @include('pharmacy-store.product-fields', ['product' => $product])
        </form>
    </div>
    <footer><button type="button" class="distributor-button secondary" data-close-distributor-dialog>Cancelar</button><button type="submit" class="distributor-button" form="distributor-product-form-{{ $editorId }}">{{ $product ? 'Guardar cambios' : 'Crear producto' }}</button></footer>
</dialog>
