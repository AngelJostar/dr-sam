<dialog id="medication-status-dialog" class="medication-identifiers-dialog" aria-labelledby="medication-status-title" data-reopen-status="{{ $errors->medicationStatus->any() ? old('status_medication_id') : '' }}" data-old-status="{{ old('status') }}">
    <header><div><h2 id="medication-status-title">Cambiar estatus</h2><p data-status-caption></p></div><button type="button" data-close-status aria-label="Cerrar">×</button></header>
    <form method="POST" data-status-form>
        @csrf @method('PATCH')<input type="hidden" name="status_medication_id">
        @if($errors->medicationStatus->any())<div role="alert">@foreach($errors->medicationStatus->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <label>Estatus del medicamento<select name="status" required>@foreach(\App\Models\MedicationCatalogItem::STATUS_LABELS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
        <p class="medication-editor-hint">Solo los productos activos con publicación ON se muestran en la farmacia digital. Las ofertas de los distribuidores se conservan al cambiar el estatus.</p>
        <footer><button type="button" data-close-status>Cancelar</button><button type="submit">Guardar estatus</button></footer>
    </form>
</dialog>
