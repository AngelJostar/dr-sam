@php
  $money = fn ($amount) => $amount === null ? 'Por cotizar' : '$'.number_format((float) $amount, 2).' MXN';
  $detailColumns = $insuranceCategory === 'gmm'
    ? ['teleconsultations' => 'Teleconsultas', 'ambulance' => 'Ambulancia', 'coverages' => 'Coberturas', 'conditions' => 'Condiciones', 'exclusions' => 'Exclusiones', 'waiting_periods' => 'Periodos de espera', 'cancellation' => 'Cancelación y privacidad', 'beneficiaries' => 'Beneficiarios', 'term' => 'Plazo', 'main_coverage' => 'Cobertura principal']
    : ['term' => 'Plazo', 'main_coverage' => 'Cobertura principal', 'beneficiaries' => 'Beneficiarios', 'conditions' => 'Condiciones', 'coverages' => 'Coberturas', 'exclusions' => 'Exclusiones', 'waiting_periods' => 'Periodos de espera', 'cancellation' => 'Cancelación y privacidad'];
  $columns = ['Plan', 'Aseguradora', 'Prima mensual', 'Suma asegurada'];
  if ($insuranceCategory === 'gmm') $columns = array_merge($columns, ['Deducible', 'Coaseguro']);
  $columns = array_merge($columns, array_values($detailColumns), ['Complementos', 'Documentos', 'Mi seguro', 'Estatus', 'Demostración', 'Vincular a app', 'Editar']);
@endphp
<section class="superadmin-native-catalog-card admin-insurance-plans" data-insurance-plan-admin>
  <header class="superadmin-native-section-heading">
    <div><h2>{{ $catalog['title'] }}</h2><p>Planes disponibles para Mi seguro · {{ $records->total() }} seguros @if($selectedInsuranceCarrier ?? null) · {{ $selectedInsuranceCarrier->name }} <a href="{{ request()->fullUrlWithQuery(['carrier' => null, 'page' => null, 'edit_plan' => null]) }}">Ver todas las aseguradoras</a>@endif</p></div>
    <div class="superadmin-native-heading-actions">
      <button type="button" data-plan-preview>Vista previa móvil</button>
      <a class="superadmin-native-csv" href="{{ $csvUrl }}">↓ Exportar CSV</a>
      <a class="admin-insurance-primary" href="#create-insurance-plan">+ Nuevo seguro</a>
    </div>
  </header>
  <div class="superadmin-native-catalog-scroll">
    <table class="superadmin-native-catalog-table admin-insurance-table" data-drsam-table-filter-empty>
      <thead><tr>@foreach($columns as $column)<th scope="col" @class(['admin-insurance-link-column' => $column === 'Vincular a app', 'admin-insurance-edit-column' => $column === 'Editar']) @if(in_array($column, ['Vincular a app', 'Editar'], true)) data-drsam-table-filter-skip-column @endif>{{ $column }}</th>@endforeach</tr></thead>
      <tbody>@forelse($records as $plan)
        <tr data-insurance-plan-id="{{ $plan->id }}">
          <td><strong>{{ $plan->name }}</strong></td><td>{{ $plan->carrier?->name ?? 'Por asignar' }}</td>
          <td>{{ $money($plan->monthly_premium) }}</td><td>{{ $plan->insured_amount === null ? 'Por definir' : $money($plan->insured_amount) }}</td>
          @if($insuranceCategory === 'gmm')<td>{{ $plan->deductible === null ? 'Por definir' : $money($plan->deductible) }}</td><td>{{ $plan->coinsurance === null ? 'Por definir' : (float)$plan->coinsurance.'%' }}</td>@endif
          @foreach($detailColumns as $key => $label)
            <td>@if(filled(data_get($plan->details, $key)))@if(mb_strlen(data_get($plan->details, $key)) > 60)<details class="admin-insurance-cell-detail"><summary>Ver detalle</summary><p>{{ data_get($plan->details, $key) }}</p></details>@else{{ data_get($plan->details, $key) }}@endif @else Por definir @endif</td>
          @endforeach
          <td>@if(data_get($plan->details, 'addons'))<details class="admin-insurance-cell-detail"><summary>Ver complementos</summary>@foreach($plan->details['addons'] as $addon)<p>{{ $addon['name'] }} · {{ $money($addon['price']) }}/mes</p>@endforeach</details>@else Sin complementos @endif</td>
          <td><a href="#insurance-plan-documents-{{ $plan->id }}">Adjuntar / Ver ({{ $plan->documents->count() }})</a></td>
          <td><span class="admin-insurance-link-state">{{ $plan->is_linked ? ($plan->status === 'active' && (!$plan->carrier || $plan->carrier->status === 'active') ? 'Vinculado' : 'Vinculación pausada') : 'No vinculado' }}</span></td>
          <td>{{ $plan->status === 'active' ? 'Activo' : 'Inactivo' }}</td><td>{{ $plan->is_demo ? 'Sí' : 'No' }}</td>
          <td class="admin-insurance-actions admin-insurance-link-column"><div class="superadmin-native-row-actions">
            <form method="post" action="{{ route('superadmin.insurance-plans.link', $plan) }}">@csrf @method('PATCH')<input type="hidden" name="is_linked" value="{{ $plan->is_linked ? '0' : '1' }}"><button type="submit">{{ $plan->is_linked ? 'Desvincular de app' : 'Vincular a app' }}</button></form>
          </div></td>
          <td class="admin-insurance-actions admin-insurance-edit-column"><div class="superadmin-native-row-actions">
            <a href="{{ request()->fullUrlWithQuery(['edit_plan' => $plan->id]) }}#edit-insurance-plan" aria-label="Editar {{ $plan->name }}">Editar</a>
          </div></td>
        </tr>
      @empty<tr><td colspan="{{ count($columns) }}">No hay seguros con estos filtros.</td></tr>@endforelse</tbody>
    </table>
  </div>
  <footer class="admin-insurance-footer"><span>{{ $records->total() }} seguros</span><span>Desliza para consultar todas las columnas</span>{{ $records->links() }}</footer>
</section>

@include('superadmin._insurance-plan-form', ['editingPlan' => null])
@if($insuranceEditingPlan) @include('superadmin._insurance-plan-form', ['editingPlan' => $insuranceEditingPlan]) @endif
@foreach($records as $plan)
  <section id="insurance-plan-documents-{{ $plan->id }}" class="superadmin-user-modal" role="dialog" aria-modal="true" aria-labelledby="insurance-doc-title-{{ $plan->id }}">
    <a class="superadmin-user-modal-backdrop" href="#" aria-label="Cerrar documentos"></a>
    <div class="superadmin-user-modal-card"><header><div><h2 id="insurance-doc-title-{{ $plan->id }}">Documentos de {{ $plan->name }}</h2><p>PDF, JPG o PNG · Máximo 10 MB por archivo.</p></div><a href="#" aria-label="Cerrar documentos">×</a></header>
      <div class="admin-insurance-document-list">@forelse($plan->documents as $document)<div><a href="{{ route('patient.insurance-plans.documents', $document) }}">{{ $document->name }}</a><form method="post" action="{{ route('superadmin.insurance-plans.documents.destroy', $document) }}">@csrf @method('DELETE')<button type="submit">Eliminar</button></form></div>@empty<p>No hay documentos adjuntos.</p>@endforelse</div>
      <form method="post" enctype="multipart/form-data" action="{{ route('superadmin.insurance-plans.documents.store', $plan) }}">@csrf<label>Agregar documentos<input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png" multiple required></label><footer><a href="#">Cerrar</a><button type="submit">Guardar documentos</button></footer></form>
    </div>
  </section>
@endforeach
<dialog class="admin-insurance-preview" data-plan-preview-dialog aria-labelledby="insurance-preview-title"><header><div><h2 id="insurance-preview-title">Vista previa de Mi seguro</h2><p>Así aparecen los planes vinculados de esta categoría.</p></div><button type="button" data-plan-preview-close aria-label="Cerrar vista previa">×</button></header>
  <div class="admin-insurance-preview-phone"><strong>Klini · Mi seguro</strong><h3>Contratar un nuevo seguro</h3>
    @forelse(\App\Models\InsurancePlan::query()->published()->with(['carrier', 'documents'])->where('category', $insuranceCategory)->get() as $plan)
      @include('patient.partials.insurance-plan-card', ['option' => \App\Services\Insurance\InsurancePlanCatalog::payload($plan), 'preview' => true])
    @empty<p>No hay planes vinculados. Usa “Vincular a app” para mostrarlos al paciente.</p>@endforelse
  </div>
</dialog>
@push('scripts')<script src="{{ asset('js/superadmin-insurance.js') }}?v={{ filemtime(public_path('js/superadmin-insurance.js')) }}" defer></script>@endpush
