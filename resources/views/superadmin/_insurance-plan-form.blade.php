@php
  $formId = $editingPlan ? 'edit-insurance-plan' : 'create-insurance-plan';
  $formValue = fn ($key, $default = '') => old($key, data_get($editingPlan, $key, $default));
@endphp
<section id="{{ $formId }}" class="superadmin-user-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $formId }}-title">
  <a class="superadmin-user-modal-backdrop" href="#" aria-label="Cerrar formulario"></a>
  <div class="superadmin-user-modal-card admin-insurance-form"><header><div><h2 id="{{ $formId }}-title">{{ $editingPlan ? 'Editar seguro' : 'Nuevo seguro' }}</h2><p>Esta información se mostrará en Mi seguro al vincular el plan.</p></div><a href="#" aria-label="Cerrar formulario">×</a></header>
    <form method="post" action="{{ $editingPlan ? route('superadmin.insurance-plans.update', $editingPlan) : route('superadmin.insurance-plans.store') }}">@csrf @if($editingPlan) @method('PATCH') @endif
      <div class="superadmin-user-modal-grid">
        <label>Nombre del plan<input name="name" value="{{ $formValue('name') }}" maxlength="180" required></label>
        <label>Tipo de seguro<select name="category">@foreach(\App\Services\Insurance\InsurancePlanCatalog::CATEGORIES as $key => $label)<option value="{{ $key }}" @selected($formValue('category', $insuranceCategory) === $key)>{{ $label }}</option>@endforeach</select></label>
        <label>Aseguradora<select name="insurance_carrier_id"><option value="">Por asignar</option>@foreach($insuranceCarriers as $carrier)<option value="{{ $carrier->id }}" @selected((string)$formValue('insurance_carrier_id', $selectedInsuranceCarrier?->id ?? '') === (string)$carrier->id)>{{ $carrier->name }}{{ $carrier->status === 'active' ? '' : ' (Inactiva)' }}</option>@endforeach</select></label>
        <label>Estatus<select name="status"><option value="active" @selected($formValue('status', 'active') === 'active')>Activo</option><option value="inactive" @selected($formValue('status') === 'inactive')>Inactivo</option></select></label>
        <label>Datos de demostración<select name="is_demo"><option value="1" @selected((bool)$formValue('is_demo', true))>Sí</option><option value="0" @selected(!(bool)$formValue('is_demo', true))>No</option></select></label>
        @foreach(['monthly_premium' => 'Prima mensual (MXN)', 'insured_amount' => 'Suma asegurada (MXN)', 'deductible' => 'Deducible (MXN)', 'coinsurance' => 'Coaseguro (%)'] as $key => $label)
          <label>{{ $label }}<input type="number" name="{{ $key }}" value="{{ $formValue($key) }}" min="0" step="0.01" @if($key === 'coinsurance') max="100" @endif placeholder="Por definir"></label>
        @endforeach
      </div>
      <h3>Coberturas y condiciones</h3><div class="superadmin-user-modal-grid">
        @foreach(\App\Services\Insurance\InsurancePlanCatalog::DETAIL_FIELDS as $key => $label)<label>{{ $label }}<textarea name="details[{{ $key }}]" rows="3" maxlength="5000" @if($key === 'coverages') placeholder="Una cobertura por renglón" @endif>{{ $formValue('details.'.$key) }}</textarea></label>@endforeach
      </div>
      <h3>Complementos opcionales</h3><p>Deja el importe vacío para excluir un complemento.</p><div class="superadmin-user-modal-grid">
        @foreach(['dental' => 'Dental', 'vision' => 'Visión', 'travel' => 'Viajes'] as $key => $label)<label>{{ $label }} · Nombre<input name="details[addons][{{ $key }}][name]" value="{{ $formValue('details.addons.'.$key.'.name', $label) }}" maxlength="100"></label><label>{{ $label }} · Precio mensual (MXN)<input type="number" name="details[addons][{{ $key }}][price]" value="{{ $formValue('details.addons.'.$key.'.price') }}" min="0" max="999999" step="0.01"></label>@endforeach
      </div><footer><a href="#">Cancelar</a><button type="submit">Guardar seguro</button></footer>
    </form>
  </div>
</section>
