<dialog class="import-new-supplier import-user-dialog" aria-labelledby="import-user-title" data-new-import-user-dialog data-errors="{{ $errors->importUser->any() ? 'true' : 'false' }}">
  <header><div><h2 id="import-user-title">Nuevo usuario</h2><p>Registra una cuenta para el módulo de proveedor de importación.</p></div><button type="button" data-close-import-user aria-label="Cerrar formulario">×</button></header>
  <form method="post" action="{{ route('superadmin.providers.store') }}">
    @csrf
    <input type="hidden" name="provider_type" value="import">
    <input type="hidden" name="import_user_form" value="1">
    @if ($errors->importUser->any())<div class="notice danger" role="alert">@foreach ($errors->importUser->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <fieldset><legend>Datos de la cuenta</legend>
      <label>Nombre completo *<input name="name" value="{{ old('name') }}" maxlength="180" autocomplete="name" required></label>
      <div class="supplier-grid two">
        <label>Usuario de acceso *<input name="username" value="{{ old('username') }}" maxlength="80" autocomplete="off" required></label>
        <label>Correo electrónico *<input name="email" type="email" value="{{ old('email') }}" maxlength="180" autocomplete="email" required></label>
        <label>Contraseña inicial *<input name="password" type="password" minlength="6" maxlength="80" autocomplete="new-password" required></label>
        <label>Teléfono<input name="phone" type="tel" value="{{ old('phone') }}" maxlength="50" autocomplete="tel"></label>
        <label>Área *<select name="import_area" required>@foreach (\App\Services\Platform\ImportUserDirectory::AREAS as $value => $label)<option value="{{ $value }}" @selected(old('import_area', array_keys(\App\Services\Platform\ImportUserDirectory::AREAS)[$importAction - 1] ?? 'providers') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label>Estatus *<select name="status" required><option value="active" @selected(old('status', 'active') === 'active')>Activo</option><option value="inactive" @selected(old('status') === 'inactive')>Inactivo</option></select></label>
      </div>
      <p>La clave de registro se asigna al guardar. El área clasifica la cuenta dentro de este módulo; su rol de acceso será Proveedor.</p>
    </fieldset>
    <footer><button type="button" data-close-import-user>Cancelar</button><button type="submit">Guardar usuario</button></footer>
  </form>
</dialog>
