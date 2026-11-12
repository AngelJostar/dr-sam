<div class="mobile-admin-note" id="mobile-config-help">
  @if ($section === 'navigation')
    Cambia los nombres, el orden y los accesos visibles. Inicio siempre estará disponible. Ocultar un botón cambia su presentación, no los permisos del usuario.
  @elseif ($section === 'forms')
    Personaliza los textos de los formularios del portal móvil. Las validaciones y los datos clínicos mantienen sus reglas actuales.
  @else
    Estos nombres se aplican a los botones y menús del portal que abre Klini móvil. Los botones inferiores conservan sus iconos y usan el nombre como etiqueta accesible.
  @endif
</div>
<form id="mobile-configuration" class="mobile-admin-editor" method="post" action="{{ route('superadmin.mobile-app.update', $section) }}" aria-describedby="mobile-config-help">
  @csrf @method('PUT')
  <input type="hidden" name="revision" value="{{ old('revision', $configuration['revision']) }}">
  @if ($section === 'navigation')
    @foreach (['bottom' => 'Barra inferior', 'support' => 'Menú de soporte'] as $group => $label)
      <fieldset><legend>{{ $label }}</legend>
        @foreach ($configuration['navigation'] as $key => $item)
          @continue($item['group'] !== $group)
          <div class="mobile-admin-nav-row">
            <label>{{ config('klini-mobile.navigation.'.$key.'.label') }}<input name="values[{{ $key }}][label]" maxlength="40" required value="{{ old('values.'.$key.'.label', $item['label']) }}"></label>
            <label>Orden de {{ config('klini-mobile.navigation.'.$key.'.label') }}<input type="number" name="values[{{ $key }}][order]" min="1" max="20" required value="{{ old('values.'.$key.'.order', $item['order']) }}"></label>
            <label class="mobile-admin-toggle"><input type="hidden" name="values[{{ $key }}][visible]" value="{{ $key === 'home' ? 1 : 0 }}"><input type="checkbox" name="values[{{ $key }}][visible]" value="1" @checked(old('values.'.$key.'.visible', $item['visible'])) @disabled($key === 'home')> Visible<span class="mobile-admin-sr">: {{ config('klini-mobile.navigation.'.$key.'.label') }}</span></label>
          </div>
        @endforeach
      </fieldset>
    @endforeach
  @else
    <div class="mobile-admin-fields">
      @foreach (config('klini-mobile.'.$section) as $key => $field)
        <label>{{ $field['label'] }}<input name="values[{{ $key }}]" maxlength="160" required value="{{ old('values.'.$key, $configuration[$section][$key]) }}"><small>Original: {{ $field['default'] }}</small></label>
      @endforeach
    </div>
  @endif
  <footer><span>{{ $configuration['updated_at'] ? 'Última actualización: '.\Illuminate\Support\Carbon::parse($configuration['updated_at'])->format('d/m/Y H:i') : 'Configuración original' }}</span><button type="submit">Guardar cambios</button></footer>
</form>
@if ($section === 'forms')
  <div class="mobile-admin-links">
    <a href="{{ route('superadmin.catalog', 'prescription-format') }}">Configurar formato de receta</a>
    <a href="{{ route('superadmin.catalog', 'mix-request-format') }}">Consultar formatos de mezcla</a>
    <p>Los formatos del sistema y los formularios nativos de NPT y oncología se mantienen separados. Cambiar estos textos no modifica la estructura de las solicitudes nativas.</p>
  </div>
@endif
