<form method="get" class="mobile-admin-search" role="search">
  <input type="hidden" name="section" value="users">
  <label for="mobile-user-search">Buscar usuarios Klini</label><input id="mobile-user-search" name="q" maxlength="100" value="{{ request('q') }}" placeholder="Nombre, usuario o número Klini"><button type="submit">Buscar</button>
  <a href="{{ route('superadmin.catalog', ['section' => 'users', 'category' => 'klini']) }}">Administrar perfiles</a>
</form>
<div class="superadmin-native-catalog-scroll"><table class="superadmin-native-catalog-table">
  <thead><tr><th>Usuario Klini</th><th>Nombre</th><th>Perfil</th><th>Estado</th><th>Acción</th></tr></thead>
  <tbody>@forelse ($users as $user)
    <tr><td>{{ $user->klini_member_id ?? '—' }}</td><td>{{ $user->name }}<br><small>{{ $user->username }}</small></td><td>{{ $user->roleEnum?->label() ?? $user->role }}</td><td>{{ ['active' => 'Activo', 'inactive' => 'Inactivo', 'suspended' => 'Suspendido'][$user->status] ?? $user->status }}</td><td>
      @if ($user->id !== auth()->id())
        <form class="mobile-admin-inline" method="post" action="{{ route('superadmin.users.update', $user) }}">@csrf @method('PATCH')
          <select name="status" aria-label="Estado de {{ $user->name }}">@foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'suspended' => 'Suspendido'] as $value => $label)<option value="{{ $value }}" @selected($value === $user->status)>{{ $label }}</option>@endforeach</select><button type="submit">Guardar</button>
        </form>
      @else Tu cuenta @endif
    </td></tr>
  @empty<tr><td colspan="5">No se encontraron usuarios Klini.</td></tr>@endforelse</tbody>
</table></div><div class="mobile-admin-pagination">{{ $users->links() }}</div>
