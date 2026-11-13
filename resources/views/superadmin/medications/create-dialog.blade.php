<dialog id="new-medication-dialog" class="medication-identifiers-dialog medication-create-dialog" aria-labelledby="new-medication-title" data-has-errors="{{ $errors->newMedication->any() ? 'true' : 'false' }}">
    <header><div><h2 id="new-medication-title">Agregar producto</h2><p>Nuevo medicamento del catálogo universal</p></div><button type="button" data-close-new-medication aria-label="Cerrar">×</button></header>
    <form method="POST" action="{{ route('superadmin.medications.store') }}" data-new-medication-form>
        @csrf
        <input type="hidden" name="catalog_context" value="{{ $pharmacyCatalog ? 'pharmacy' : 'medications' }}">
        @if($errors->newMedication->any())<div role="alert" class="medication-field-wide">@foreach($errors->newMedication->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <div class="medication-auto-sku medication-field-wide"><strong>SKU automático</strong><span>Se asignará el siguiente número consecutivo al guardar. Formato: 100000001.</span></div>
        <label>Nombre del medicamento *<input name="name" value="{{ old('name') }}" maxlength="255" required></label>
        <label>Nombre genérico<input name="generic_name" value="{{ old('generic_name') }}" maxlength="255"></label>
        <label>Clave CNIS<input name="cnis" value="{{ old('cnis') }}" placeholder="010.000.0000.00" pattern="[0-9]{3}\.[0-9]{3}\.[0-9]{4}\.[0-9]{2}" maxlength="15"></label>
        <label>Presentación *<input name="presentation" value="{{ old('presentation') }}" placeholder="Ej. Tabletas, envase con 10" maxlength="255" required></label>
        <label class="medication-field-wide">Descripción *<textarea name="description" maxlength="6000" rows="3" required>{{ old('description') }}</textarea></label>
        <label>Grupo terapéutico<input name="therapeutic_group" value="{{ old('therapeutic_group') }}" maxlength="255"></label>
        <label>Denominación distintiva<input name="distinctive_name" value="{{ old('distinctive_name') }}" maxlength="255"></label>
        <label>Código de barras numérico<input name="barcode" value="{{ old('barcode') }}" inputmode="numeric" pattern="[0-9]{8,32}" minlength="8" maxlength="32"><small>Opcional. Puedes registrarlo después.</small></label>
        <label>Origen del código<select name="barcode_type"><option value="commercial" @selected(old('barcode_type', 'commercial') === 'commercial')>Comercial del fabricante</option><option value="internal" @selected(old('barcode_type') === 'internal')>Interno de Klini · demo</option></select></label>
        <label>Catálogo institucional de<input name="catalog" value="{{ old('catalog') }}" maxlength="255" placeholder="Captura manual"></label>
        <label>Estatus<select name="status">@foreach(\App\Models\MedicationCatalogItem::STATUS_LABELS as $key => $label)<option value="{{ $key }}" @selected(old('status', 'active') === $key)>{{ $label }}</option>@endforeach</select></label>
        @foreach(['mobile_units' => 'Unidades móviles', 'basic_units' => 'Núcleos básicos', 'cessa' => 'CESSA'] as $key => $label)
        <label>{{ $label }}<select name="{{ $key }}"><option value="No especificado" @selected(old($key, 'No especificado') === 'No especificado')>No especificado</option><option value="Si" @selected(old($key) === 'Si')>Sí</option><option value="No" @selected(old($key) === 'No')>No</option></select></label>
        @endforeach
        <footer class="medication-field-wide"><button type="button" data-close-new-medication>Cancelar</button><button type="submit">Guardar producto</button></footer>
    </form>
</dialog>
