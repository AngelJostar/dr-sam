<dialog class="import-new-supplier" data-new-supplier-dialog data-errors="{{ $errors->supplier->any() ? 'true' : 'false' }}">
  <header><div><h2>Nuevo proveedor</h2><p>Registra un nuevo proveedor en el sistema.</p></div><button type="button" data-close-supplier aria-label="Cerrar formulario">×</button></header>
  <form method="post" action="{{ route('provider.import.suppliers.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($errors->supplier->any())<div class="notice danger" role="alert">@foreach ($errors->supplier->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <fieldset><legend>Información general</legend>
      <div class="supplier-grid two">
        <label>Razón social *<input name="legal_name" value="{{ old('legal_name') }}" maxlength="180" placeholder="Ej. Global Pharma Solutions S.A. de C.V." required></label>
        <label>Nombre comercial<input name="trade_name" value="{{ old('trade_name') }}" maxlength="180" placeholder="Ej. Global Pharma"></label>
      </div>
      <div class="supplier-grid three">
        <label>País *<select name="country" required><option value="">Selecciona un país</option>@foreach (['Estados Unidos', 'Alemania', 'Japón', 'Suiza', 'Reino Unido', 'España', 'Corea del Sur', 'Países Bajos', 'México', 'Otro'] as $value)<option @selected(old('country') === $value)>{{ $value }}</option>@endforeach</select></label>
        <label>Tipo de proveedor *<select name="supplier_type" required><option value="">Selecciona un tipo</option>@foreach (['Fabricante', 'Distribuidor', 'Comercializador'] as $value)<option @selected(old('supplier_type') === $value)>{{ $value }}</option>@endforeach</select></label>
        <label>Categorías *<select name="categories[]" multiple required>@foreach (['Biotecnológicos', 'Oncológicos', 'Inmunológicos', 'Terapias avanzadas', 'Equipos médicos', 'Genéricos', 'Dispositivos médicos', 'Vacunas'] as $value)<option @selected(in_array($value, old('categories', [])))>{{ $value }}</option>@endforeach</select></label>
      </div>
    </fieldset>
    <fieldset><legend>Contacto principal</legend><div class="supplier-grid three">
      <label>Nombre completo *<input name="contact_name" value="{{ old('contact_name') }}" maxlength="180" placeholder="Ej. Juan Pérez Gómez" required></label>
      <label>Correo electrónico *<input name="email" type="email" value="{{ old('email') }}" maxlength="180" placeholder="ejemplo@proveedor.com" required></label>
      <label>Teléfono *<input name="phone" type="tel" value="{{ old('phone') }}" maxlength="50" placeholder="+52 55 1234 5678" required></label>
    </div></fieldset>
    <fieldset><legend>Dirección</legend><label>Dirección completa *<input name="address" value="{{ old('address') }}" maxlength="500" placeholder="Calle, número, ciudad y código postal" required></label></fieldset>
    <fieldset><legend>Información comercial</legend><div class="supplier-grid three">
      @foreach (['currency' => ['Moneda', ['USD', 'EUR', 'MXN', 'JPY', 'CHF', 'GBP', 'KRW']], 'response_time' => ['Tiempo de respuesta esperado', ['12 h', '24 h', '36 h', '48 h', '72 h']], 'payment_terms' => ['Condiciones de pago', ['Anticipado', 'Contado', 'Crédito 30 días', 'Crédito 60 días']]] as $name => [$label, $options])
        <label>{{ $label }} *<select name="{{ $name }}" required><option value="">Selecciona una opción</option>@foreach ($options as $value)<option @selected(old($name) === $value)>{{ $value }}</option>@endforeach</select></label>
      @endforeach
    </div></fieldset>
    <fieldset><legend>Documentos requeridos</legend><div class="supplier-grid two">
      <label>Documentos sanitarios *<input type="file" name="sanitary_document" accept=".pdf,.doc,.docx" required><small>PDF, DOC, DOCX · Máximo 10 MB</small></label>
      <label>Contrato marco *<input type="file" name="framework_contract" accept=".pdf,.doc,.docx" required><small>PDF, DOC, DOCX · Máximo 10 MB</small></label>
    </div></fieldset>
    <div class="supplier-grid two">
      <label>Estatus inicial *<select name="initial_status" required>@foreach (['active' => 'Activo', 'preferred' => 'Preferente', 'pending' => 'En validación', 'document_risk' => 'Riesgo documental'] as $value => $label)<option value="{{ $value }}" @selected(old('initial_status', 'active') === $value)>{{ $label }}</option>@endforeach</select></label>
      <label>Notas adicionales<textarea name="notes" maxlength="500" placeholder="Agrega notas relevantes sobre el proveedor">{{ old('notes') }}</textarea><small>Máximo 500 caracteres</small></label>
    </div>
    <footer><button type="button" data-close-supplier>Cancelar</button><button type="submit">Guardar proveedor</button></footer>
  </form>
</dialog>
