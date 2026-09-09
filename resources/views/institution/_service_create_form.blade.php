<form method="post" action="{{ route('institution.services.store') }}" class="institution-unit-create-form institution-service-create-form">
  @csrf
  <input type="hidden" name="institution" value="{{ $institution->id }}">
  <input type="hidden" name="form_context" value="service">
  <label class="is-wide">Unidades con servicio contratado
    <select name="unit_ids[]" multiple size="1" aria-label="Seleccionar hospitales">
      @foreach ($institution->medicalUnits as $unit)
        <option value="{{ $unit->id }}" @selected(in_array($unit->id, old('unit_ids', [])))>{{ $unit->name }}{{ $unit->clues ? ' - '.$unit->clues : '' }}</option>
      @endforeach
    </select>
    <small>Usa Ctrl para seleccionar mas de un hospital.</small>
  </label>
  <div class="institution-service-create-divider"><strong>Datos del Servicio</strong></div>
  <label>Categoria
    <select name="category" required>
      @foreach (['Asistenciales', 'Criticos', 'Diagnosticos', 'Farmaceuticos', 'Quirurgicos'] as $category)
        <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
      @endforeach
    </select>
  </label>
  <label>Especialidad
    <select name="specialty" required>
      @foreach ($servicesCatalog->pluck('specialty')->filter()->unique()->sort() as $specialty)
        <option value="{{ $specialty }}" @selected(old('specialty') === $specialty)>{{ $specialty }}</option>
      @endforeach
      <option value="Servicio general" @selected(old('specialty') === 'Servicio general')>Servicio general</option>
    </select>
  </label>
  <label>Servicio<input name="name" value="{{ old('form_context') === 'service' ? old('name') : '' }}" placeholder="Captura el nombre del servicio" required></label>
  <label>Inicio<input name="starts_at" type="date" value="{{ old('form_context') === 'service' ? old('starts_at') : '' }}" required></label>
  <label>Fin<input name="ends_at" type="date" value="{{ old('form_context') === 'service' ? old('ends_at') : '' }}" required></label>
  <label>SLA<input name="sla" value="{{ old('form_context') === 'service' ? old('sla', 'Horario habil') : 'Horario habil' }}" required></label>
  <button type="submit">Guardar servicio</button>
</form>
