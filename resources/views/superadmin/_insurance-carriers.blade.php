@php
  $carrierStatusLabel = ['all' => 'Todas', 'active' => 'Activas', 'inactive' => 'Inactivas'][$filters['status'] ?: 'all'] ?? 'Todas';
  $carrierPlansUrl = fn ($carrier, $category) => route('superadmin.catalog', ['section' => 'insurance-carriers', 'view' => $category, 'carrier' => $carrier->id, 'status' => 'all']);
@endphp
<section class="superadmin-native-catalog-card admin-insurance-plans admin-insurance-carriers">
  <header class="superadmin-native-section-heading">
    <div><h2>Catálogo de aseguradoras</h2><p>{{ $carrierStatusLabel }} · {{ $records->total() }} {{ $records->total() === 1 ? 'aseguradora' : 'aseguradoras' }}</p></div>
    <div class="superadmin-native-heading-actions">
      <a class="superadmin-native-csv" href="{{ $csvUrl }}">↓ Exportar CSV</a>
      <a class="admin-insurance-primary" href="#create-insurance-carrier">+ Alta de aseguradora</a>
    </div>
  </header>
  <div class="superadmin-native-catalog-scroll">
    <table class="superadmin-native-catalog-table admin-insurance-carrier-table" aria-label="Catálogo de aseguradoras" data-drsam-table-filter-empty>
      <thead><tr>
        @foreach($catalog['columns'] as $column)<th scope="col" @if($column === 'Acciones') data-drsam-table-filter-skip-column @endif>{{ $column }}</th>@endforeach
      </tr></thead>
      <tbody>
        @forelse($records as $carrier)
          <tr data-insurance-carrier-id="{{ $carrier->id }}">
            <td><strong>{{ $carrier->name }}</strong></td>
            <td>{{ $carrier->coverageLabel() }}</td>
            <td>{{ $carrier->contact ?: 'Por registrar' }}</td>
            <td>{{ $carrier->phone ?: 'Por registrar' }}</td>
            <td>{{ $carrier->email ?: 'Por registrar' }}</td>
            <td>{{ $carrier->scope ?: 'Por definir' }}</td>
            @foreach(['gmm' => 'GMM', 'life' => 'Vida'] as $category => $label)
              @php($planCount = $carrier->{$category.'_plans_count'})
              <td><a class="admin-carrier-plan-count" href="{{ $carrierPlansUrl($carrier, $category) }}" aria-label="{{ $planCount }} {{ $planCount === 1 ? 'plan' : 'planes' }} de {{ $label }} de {{ $carrier->name }}">{{ $planCount }} {{ $planCount === 1 ? 'plan' : 'planes' }}</a></td>
            @endforeach
            <td><span @class(['admin-carrier-status', 'is-inactive' => $carrier->status !== 'active'])><span aria-hidden="true"></span>{{ $carrier->status === 'active' ? 'Activo' : 'Inactivo' }}</span></td>
            <td class="admin-carrier-actions"><div class="superadmin-native-row-actions">
              <a href="{{ $carrierPlansUrl($carrier, $carrier->gmm_plans_count || !$carrier->life_plans_count ? 'gmm' : 'life') }}" aria-label="Ver planes de {{ $carrier->name }}">Ver planes</a>
              <a href="#edit-insurance-carrier-{{ $carrier->id }}" aria-label="Editar {{ $carrier->name }}">Editar</a>
              <form method="post" action="{{ route('superadmin.insurance-carriers.update', $carrier) }}">
                @csrf @method('PATCH')
                @foreach(['name', 'slug', 'type', 'contact', 'phone', 'email', 'scope'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $carrier->{$field} }}">@endforeach
                <input type="hidden" name="status" value="{{ $carrier->status === 'active' ? 'inactive' : 'active' }}">
                <button type="submit" @class(['admin-carrier-activate' => $carrier->status !== 'active']) aria-label="{{ $carrier->status === 'active' ? 'Desactivar' : 'Activar' }} {{ $carrier->name }}">{{ $carrier->status === 'active' ? 'Desactivar' : 'Activar' }}</button>
              </form>
            </div></td>
          </tr>
        @empty
          <tr><td colspan="10" class="admin-carrier-empty">No hay aseguradoras con estos filtros.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <footer class="admin-insurance-footer"><span>{{ $records->total() }} {{ $records->total() === 1 ? 'aseguradora' : 'aseguradoras' }}</span>{{ $records->links() }}</footer>
</section>
@include('superadmin._insurance-carrier-form', ['editingCarrier' => null])
@foreach($records as $carrier)
  @include('superadmin._insurance-carrier-form', ['editingCarrier' => $carrier])
@endforeach
