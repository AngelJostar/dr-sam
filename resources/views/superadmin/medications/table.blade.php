@push('styles')
<link rel="stylesheet" href="{{ asset('css/universal-medication-catalog.css') }}?v={{ filemtime(public_path('css/universal-medication-catalog.css')) }}">
@endpush
@push('scripts')
<script src="{{ asset('js/universal-medication-catalog.js') }}?v={{ filemtime(public_path('js/universal-medication-catalog.js')) }}" defer></script>
@endpush
@php
    $pharmacyCatalog = $pharmacyCatalog ?? false;
    $catalogRoute = $pharmacyCatalog ? route('superadmin.digital-pharmacy') : route('superadmin.catalog', 'medications');
    $identifierErrors = $errors->hasAny(['sku', 'barcode', 'barcode_type']);
@endphp
<section class="superadmin-native-catalog-card universal-medication-catalog">
    <header class="superadmin-native-section-heading">
        <div>
            <h2>{{ $pharmacyCatalog ? 'Administración de productos' : $catalog['title'] }}</h2>
            <p>{{ $records->total() }} presentaciones · {{ $pharmacyCatalog ? 'Conectadas al catálogo universal' : 'Medicamentos y presentaciones' }}</p>
        </div>
        <div class="superadmin-native-heading-actions">
            @if($pharmacyCatalog)<a href="{{ route('superadmin.catalog', 'medications') }}">Catálogo universal</a>@endif
            <a href="{{ route('superadmin.catalog.csv', ['section' => 'medications', 'q' => request('q'), 'status' => request('status')]) }}" aria-label="Exportar CSV">CSV</a>
            <a href="{{ request()->fullUrl() }}">Actualizar</a>
        </div>
    </header>
    <form method="GET" action="{{ $catalogRoute }}" class="medication-catalog-search">
        @if($pharmacyCatalog)<input type="hidden" name="section" value="catalog">@endif
        <label class="medication-search-label"><span>Buscar en todo el catálogo</span><input type="search" name="q" value="{{ request('q') }}" maxlength="160" placeholder="SKU, código de barras, CNIS, medicamento o descripción"></label>
        <label><span>Estatus</span><select name="status"><option value="all">Todos</option>@foreach(\App\Models\MedicationCatalogItem::STATUS_LABELS as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></label>
        <button type="submit">Buscar</button>
        @if(request()->filled('q') || array_key_exists(request('status', ''), \App\Models\MedicationCatalogItem::STATUS_LABELS))<a href="{{ $catalogRoute }}">Limpiar</a>@endif
        <button type="button" class="medication-add-product" data-new-medication aria-haspopup="dialog" aria-controls="new-medication-dialog">+ Agregar producto</button>
    </form>
    <details class="medication-catalog-sources">
        <summary>Fuentes oficiales e identificadores de demostración</summary>
        <p>Las descripciones, claves CNIS y disponibilidad por tipo de unidad de la carga demo proceden del <a href="{{ \Database\Seeders\ImssBienestarMedicationSeeder::SOURCE_URL }}" target="_blank" rel="noopener">listado de IMSS Bienestar · Rutas de la Salud v5.1</a>. Se contrastaron 152 claves también presentes en el <a href="https://data.salud.cdmx.gob.mx/ssdf/portalut/archivo/Actualizaciones/4toTrimestre21/Dir_Med_Tec_Ins/CIM.pdf" target="_blank" rel="noopener">catálogo de la Secretaría de Salud CDMX, edición 2020</a>. Consulta: 12 de noviembre de 2026.</p>
        <p>Los SKU y códigos marcados «Interno · demo» son identificadores de Klini. Las fuentes no proporcionan marcas ni códigos comerciales GTIN/EAN por fabricante; esos datos quedan por registrar.</p>
        @if($pharmacyCatalog)<p>Esta tabla administra la base de medicamentos. Los precios, existencias y publicación de la tienda se gestionan con los productos de los distribuidores.</p>@endif
    </details>
    @if(!$pharmacyCatalog && session('status'))<p role="status" class="medication-catalog-notice">{{ session('status') }}</p>@endif
    <p class="medication-publication-feedback" role="status" hidden></p>
    <div class="superadmin-native-catalog-scroll">
        <table class="superadmin-native-catalog-table medication-catalog-table" data-drsam-table-filter-empty>
            <thead><tr>
                <th>SKU interno</th><th>Clave CNIS</th><th>Medicamento</th><th>Descripción</th>
                <th>Código de barras numérico</th><th data-drsam-table-filter-skip-column>Código de barras imagen</th>
                <th>Grupo</th><th>Denominación distintiva</th><th>Unidades móviles</th><th>Núcleos básicos</th><th>CESSA</th><th>Catálogo institucional de</th><th>Estatus</th><th data-drsam-table-filter-skip-column>Mostrar en farmacia digital</th><th data-drsam-table-filter-skip-column>Administrar</th>
            </tr></thead>
            <tbody>
                @forelse($records as $record)
                @php($values = $catalog['map']($record))
                <tr>
                    <td><code>{{ $values[9] }}</code></td>
                    <td><span class="medication-cnis">{{ $values[0] }}</span></td>
                    <td><strong class="superadmin-medication-name">{{ $record->name }}</strong><small class="superadmin-medication-code">{{ $record->presentation }}</small></td>
                    <td>{{ $values[3] }}</td>
                    <td><code>{{ $values[10] }}</code>@if($record->barcode)<small class="medication-code-origin">{{ $record->barcode_type === 'internal' ? 'Interno · demo' : ($record->barcode_type === 'commercial' ? 'Comercial · capturado' : 'Origen por registrar') }}</small>@endif</td>
                    <td>@if($record->barcode)<a href="{{ $values[11] }}" target="_blank" rel="noopener" title="Abrir código de barras de {{ $record->name }}"><img class="medication-barcode" src="{{ $values[11] }}" alt="Código de barras {{ $record->barcode }}" loading="lazy" width="286" height="120"></a>@else<span class="superadmin-medication-missing">Por registrar</span>@endif</td>
                    <td>{{ $values[1] }}</td><td>{{ $values[4] }}</td><td>{{ $values[5] }}</td><td>{{ $values[6] }}</td><td>{{ $values[7] }}</td><td>{{ $values[8] }}</td>
                    <td><button type="button" class="medication-status is-{{ $record->status }}" aria-label="Cambiar estatus de {{ $record->name }}" aria-haspopup="dialog" aria-controls="medication-status-dialog" data-status-medication="{{ json_encode(['id' => $record->id, 'name' => $record->name, 'sku' => $record->sku, 'status' => $record->status, 'action' => route('superadmin.medications.status', $record)]) }}">{{ $record->statusLabel() }} ▾</button></td>
                    <td><form method="POST" action="{{ route('superadmin.medications.publication', $record) }}" class="medication-publication-form" data-medication-publication>@csrf @method('PATCH')
                        <input type="hidden" name="digital_pharmacy_enabled" value="{{ $record->digital_pharmacy_enabled ? '0' : '1' }}">
                        <button type="submit" role="switch" class="medication-publication-switch" aria-checked="{{ $record->digital_pharmacy_enabled ? 'true' : 'false' }}" aria-label="Mostrar {{ $record->name }} en farmacia digital"><span aria-hidden="true"></span><b>{{ $record->digital_pharmacy_enabled ? 'ON' : 'OFF' }}</b></button>
                        <small>{{ $record->pharmacyCategory() }}</small>
                        @if($record->status !== 'active')<small>Visible cuando esté activo</small>@endif
                    </form></td>
                    <td><button type="button" class="medication-edit" aria-haspopup="dialog" aria-controls="medication-identifiers-dialog" data-medication-id="{{ $record->id }}" data-medication="{{ json_encode(['id' => $record->id, 'name' => $record->name, 'cnis' => $record->cnis, 'sku' => $record->sku, 'barcode' => $record->barcode, 'barcode_type' => $record->barcode_type, 'action' => route('superadmin.medications.identifiers', $record)]) }}">Editar identificadores</button></td>
                </tr>
                @empty<tr><td colspan="15" class="medication-empty">No hay medicamentos que coincidan con la búsqueda.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <footer class="medication-catalog-pagination">
        <span>{{ $records->total() }} presentaciones · {{ $records->perPage() }} por página · Página {{ $records->currentPage() }} de {{ $records->lastPage() }}</span>
        <nav aria-label="Paginación del catálogo">
            @if($records->previousPageUrl())<a href="{{ $records->previousPageUrl() }}">Anterior</a>@endif
            @if($records->nextPageUrl())<a href="{{ $records->nextPageUrl() }}">Siguiente</a>@endif
        </nav>
    </footer>
</section>
<dialog id="medication-identifiers-dialog" class="medication-identifiers-dialog" aria-labelledby="medication-identifiers-title" data-reopen-id="{{ $identifierErrors ? old('identifier_id') : '' }}" data-old-identifiers="{{ json_encode(['sku' => old('sku'), 'barcode' => old('barcode'), 'barcode_type' => old('barcode_type')]) }}">
    <header><div><h2 id="medication-identifiers-title">Editar identificadores</h2><p data-medication-caption></p></div><button type="button" data-close-medication aria-label="Cerrar">×</button></header>
    <form method="POST" data-medication-form>
        @csrf @method('PATCH')<input type="hidden" name="identifier_id">
        @if($identifierErrors)<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <label>SKU interno<input name="sku" inputmode="numeric" maxlength="9" minlength="9" pattern="1[0-9]{8}" autocomplete="off" required><small>Nueve dígitos; comienza con 1. Ejemplo: 100000001.</small></label>
        <label>Código de barras numérico<input name="barcode" inputmode="numeric" pattern="[0-9]{8,32}" minlength="8" maxlength="32" autocomplete="off"><small>Conserva los ceros iniciales. Entre 8 y 32 dígitos.</small></label>
        <label>Origen del código<select name="barcode_type"><option value="internal">Interno de Klini · demo</option><option value="commercial">Comercial del fabricante</option></select></label>
        <p class="medication-editor-hint">La imagen se genera al guardar. Para un código comercial, captura el número del envase del fabricante.</p>
        <footer><button type="button" data-close-medication>Cancelar</button><button type="submit">Guardar identificadores</button></footer>
    </form>
</dialog>
@include('superadmin.medications.create-dialog')
@include('superadmin.medications.status-dialog')
