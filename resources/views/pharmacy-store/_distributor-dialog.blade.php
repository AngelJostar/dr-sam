<dialog id="new-distributor-dialog" class="pharmacy-distributor-dialog" aria-labelledby="new-distributor-title" aria-describedby="new-distributor-description" data-open-on-load="{{ $distributorErrors || (request('section') === 'create' && !session('status')) ? 'true' : 'false' }}">
  <header class="pharmacy-distributor-dialog-header">
    <div>
      <h2 id="new-distributor-title" tabindex="-1" autofocus>Nuevo distribuidor</h2>
      <p id="new-distributor-description">Agrega los datos de su cuenta para darlo de alta.</p>
    </div>
    <button type="button" class="pharmacy-distributor-close" data-close-distributor aria-label="Cerrar nuevo distribuidor">&times;</button>
  </header>
  <div class="pharmacy-distributor-dialog-content">
    @if($distributorErrors)
      <div role="alert" class="pharmacy-notice pharmacy-distributor-errors">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
      </div>
    @endif
    <form method="POST" action="{{ route('superadmin.distributors.store') }}" class="pharmacy-form" id="new-distributor-form">
      @csrf
      <label class="pharmacy-distributor-wide">Nombre<input name="name" value="{{ old('name') }}" required maxlength="200" autocomplete="name"></label>
      <label>Correo<input name="email" type="email" value="{{ old('email') }}" required maxlength="200" autocomplete="email"></label>
      <label>Usuario<input name="username" value="{{ old('username') }}" required maxlength="100" autocomplete="off"></label>
      <label>Contraseña inicial<input name="password" type="password" autocomplete="new-password" required minlength="12"><small>12 caracteres, mayúsculas, minúsculas y números.</small></label>
      <label>Confirmar contraseña<input name="password_confirmation" type="password" autocomplete="new-password" required></label>
      <p class="pharmacy-distributor-wide pharmacy-distributor-hint">La publicación en Farmacia digital estará desactivada hasta que la autorices.</p>
    </form>
    <details class="pharmacy-distributor-demo" @if($errors->has('user_id')) open @endif>
      <summary>Vincular cuenta demo existente</summary>
      @if($demoAccounts->isNotEmpty())
        <form method="POST" action="{{ route('superadmin.distributors.link-demo') }}" class="pharmacy-form">
          @csrf
          <label class="pharmacy-distributor-wide">Cuenta demo<select name="user_id" required><option value="">Selecciona una cuenta</option>@foreach($demoAccounts as $demoAccount)<option value="{{ $demoAccount->id }}" @selected(old('user_id') == $demoAccount->id)>{{ $demoAccount->name }} · Cuenta demo</option>@endforeach</select></label>
          <p class="pharmacy-distributor-wide">Se vincula su catálogo con publicación desactivada. Sus accesos se conservan.</p>
          <button type="submit">Vincular cuenta demo</button>
        </form>
      @else
        <p>No hay cuentas demo de distribuidor pendientes de vincular.</p>
      @endif
    </details>
  </div>
  <footer class="pharmacy-distributor-dialog-actions">
    <button type="button" class="pharmacy-distributor-cancel" data-close-distributor>Cancelar</button>
    <button type="submit" form="new-distributor-form">Crear distribuidor</button>
  </footer>
</dialog>
