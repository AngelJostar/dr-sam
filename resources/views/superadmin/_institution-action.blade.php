<div class="superadmin-institution-action">
  @if ($index === 7)
    <a class="superadmin-institution-action-button" href="{{ route('institution.dashboard', ['institution' => $record->id, 'section' => 'units']) }}" aria-label="Hospitales de {{ $record->name }}">Hospitales</a>
    <span class="superadmin-institution-hospital-count" aria-label="{{ $record->medical_units_count ?? 0 }} hospitales">{{ $record->medical_units_count ?? 0 }}</span>
  @elseif ($index === 8)
    <form method="post" action="{{ route('superadmin.institutions.status', $record) }}">
      @csrf
      @method('patch')
      <input type="hidden" name="status" value="{{ $record->status === 'active' ? 'inactive' : 'active' }}">
      <button class="superadmin-institution-action-button is-green" type="submit" aria-label="{{ $value }} {{ $record->name }}">{{ $value }}</button>
    </form>
  @elseif ($index === 9)
    <a class="superadmin-institution-action-button is-green" href="{{ route('superadmin.institutions.report', $record) }}" aria-label="Descargar reporte de {{ $record->name }}">Reporte</a>
  @elseif ($index === 10)
    <a class="superadmin-institution-action-button" href="#edit-institution-{{ $record->id }}" aria-label="Editar {{ $record->name }}">Editar</a>
  @elseif ($index === 11)
    <form method="post" action="{{ route('superadmin.institutions.destroy', $record) }}" data-institution-delete data-confirm-message="¿Eliminar la institución {{ $record->name }}? Esta acción no se puede deshacer.">
      @csrf
      @method('delete')
      <input type="hidden" name="confirm_delete" value="1">
      <button class="superadmin-institution-action-button is-red" type="submit" aria-label="Eliminar {{ $record->name }}">Eliminar</button>
    </form>
  @endif
</div>
