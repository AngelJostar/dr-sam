@php
  $carrierFormId = $editingCarrier ? 'edit-insurance-carrier-'.$editingCarrier->id : 'create-insurance-carrier';
  $carrierFormValue = fn ($key, $default = '') => old('_carrier_form') === $carrierFormId ? old($key, $default) : data_get($editingCarrier, $key, $default);
@endphp
<section id="{{ $carrierFormId }}" class="superadmin-user-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $carrierFormId }}-title">
  <a class="superadmin-user-modal-backdrop" href="#" aria-label="Cerrar formulario"></a>
  <div class="superadmin-user-modal-card admin-insurance-form">
    <header><h2 id="{{ $carrierFormId }}-title">{{ $editingCarrier ? 'Editar aseguradora' : 'Alta de aseguradora' }}</h2><a href="#" aria-label="Cerrar formulario">×</a></header>
    <form method="post" action="{{ $editingCarrier ? route('superadmin.insurance-carriers.update', $editingCarrier) : route('superadmin.insurance-carriers.store') }}">
      @csrf @if($editingCarrier) @method('PATCH') @endif
      <input type="hidden" name="_carrier_form" value="{{ $carrierFormId }}">
      <div class="superadmin-user-modal-grid">
        <label>Nombre<input name="name" value="{{ $carrierFormValue('name') }}" maxlength="180" required></label>
        <label>Identificador<input name="slug" value="{{ $carrierFormValue('slug') }}" maxlength="180" placeholder="aseguradora-salud" required></label>
        <label>Tipo de seguro<input name="type" value="{{ $carrierFormValue('type') }}" maxlength="120" placeholder="GMM, Vida, GMM y Vida"></label>
        <label>Contacto<input name="contact" value="{{ $carrierFormValue('contact') }}" maxlength="160"></label>
        <label>Teléfono<input name="phone" type="tel" value="{{ $carrierFormValue('phone') }}" maxlength="60"></label>
        <label>Correo<input name="email" type="email" value="{{ $carrierFormValue('email') }}" maxlength="180"></label>
        <label>Estado<select name="status"><option value="active" @selected($carrierFormValue('status', 'active') === 'active')>Activo</option><option value="inactive" @selected($carrierFormValue('status') === 'inactive')>Inactivo</option></select></label>
        <label class="span-2">Alcance<textarea name="scope" rows="3" maxlength="500">{{ $carrierFormValue('scope') }}</textarea></label>
      </div>
      <footer><a href="#">Cancelar</a><button type="submit">{{ $editingCarrier ? 'Guardar aseguradora' : 'Crear aseguradora' }}</button></footer>
    </form>
  </div>
</section>
