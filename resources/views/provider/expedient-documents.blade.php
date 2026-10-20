<dialog class="import-provider-dialog import-patient-documents-dialog import-expedient-documents-dialog" aria-labelledby="expedient-documents-title" data-documents-dialog data-reopen-folio="{{ $errors->importDocuments->any() ? old('folio') : '' }}" data-reopen-group="{{ old('document_group', 'patient') }}">
  <form method="post" enctype="multipart/form-data" action="{{ route('provider.import.documents.store') }}" data-documents-form>
    @csrf
    <input type="hidden" name="folio" data-documents-folio>
    <input type="hidden" name="document_group" data-documents-group>
    <input type="hidden" name="return_section" value="{{ in_array($importSection, [1, 4, 5, 6], true) ? $importSection : 6 }}">
    <header><span class="import-documents-heading-icon" aria-hidden="true">▤</span><div><h2 id="expedient-documents-title" data-documents-title>Documentos del expediente</h2><p data-documents-subtitle></p></div><button type="button" data-documents-close aria-label="Cerrar documentos">×</button></header>
    <div class="import-expedient-documents-body">
      @if ($errors->importDocuments->any())
        <div class="notice danger" role="alert" data-documents-server-errors>@foreach ($errors->importDocuments->all() as $error)<p>{{ $error }}</p>@endforeach<p>Selecciona nuevamente los archivos que deseas guardar.</p></div>
      @endif
      <p role="alert" class="notice danger" data-documents-error hidden></p>
      <p class="import-document-help" data-documents-upload-help>Selecciona desde Agregar o arrastra el archivo a su fila. PDF, JPG o PNG · Máximo 10 MB por archivo.</p>
      <p class="import-document-help" data-documents-read-help hidden>Solo consulta. Puedes ver y descargar los documentos disponibles.</p>
      <div class="import-document-table-wrap" data-documents-view>
        <table class="import-patient-documents-table" data-drsam-table-filter-skip><thead><tr><th scope="col">Documento</th><th scope="col">Estado</th><th scope="col" data-documents-add-heading>Agregar documento</th><th scope="col">Ver documento</th></tr></thead><tbody data-documents-list></tbody></table>
      </div>
    </div>
    <footer>
      <button type="button" data-documents-close>Cerrar</button>
      <button type="button" data-documents-download>Descargar todo</button>
      <button type="submit" data-documents-save hidden disabled>Guardar archivos</button>
    </footer>
  </form>
</dialog>
@push('scripts')
  <script src="{{ asset('js/import-expedient-documents.js') }}?v={{ filemtime(public_path('js/import-expedient-documents.js')) }}" defer></script>
@endpush
