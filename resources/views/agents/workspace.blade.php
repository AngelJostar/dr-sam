@push('styles')
  <link rel="stylesheet" href="{{ asset('css/unit-agents.css') }}?v={{ filemtime(public_path('css/unit-agents.css')) }}">
@endpush
<div class="unit-agent-center" data-agent-center>
  <section class="{{ $agentUiPrefix }}-service-carousel-card {{ $agentUiPrefix }}-catalog-carousel-card" data-carousel-style="compact" data-agent-carousel aria-label="{{ $agentCarouselLabel }}">
    <button type="button" class="{{ $agentUiPrefix }}-service-carousel-arrow" data-agent-prev aria-label="Agente anterior">&lsaquo;</button>
    <div class="{{ $agentUiPrefix }}-service-carousel {{ $agentUiPrefix }}-catalog-carousel" data-agent-track role="group" aria-label="Filtrar por agente">
      <button type="button" class="{{ $agentUiPrefix }}-service-carousel-button is-active" data-agent-filter="all" aria-pressed="true"><span class="{{ $agentUiPrefix }}-service-carousel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 3h7v7H3ZM14 3h7v7h-7ZM3 14h7v7H3ZM14 14h7v7h-7Z"/></svg></span><strong>Todos</strong></button>
      @foreach ($agents as $agent)
        <button type="button" class="{{ $agentUiPrefix }}-service-carousel-button" data-agent-filter="{{ $agent['id'] }}" aria-pressed="false"><span class="{{ $agentUiPrefix }}-service-carousel-icon" aria-hidden="true">@include('unit.agents.icon')</span><strong>{{ $agent['name'] }}</strong></button>
      @endforeach
    </div>
    <button type="button" class="{{ $agentUiPrefix }}-service-carousel-arrow" data-agent-next aria-label="Agente siguiente">&rsaquo;</button>
  </section>
  <header class="{{ $agentUiPrefix }}-service-panel-header {{ $agentUiPrefix }}-catalog-info-card">
    <div class="{{ $agentUiPrefix }}-service-panel-copy"><h2>Centro de agentes</h2><p>{{ $agentDescription }}</p><div class="{{ $agentUiPrefix }}-service-panel-badges"><span class="{{ $agentUiPrefix }}-native-status">Configuración</span><span>{{ $agents->count() }} agentes · Ejecución automática inactiva</span></div></div>
  </header>
  <div class="{{ $agentUiPrefix }}-catalog-toolbar">
    <div class="{{ $agentFilterClass }}" role="group" aria-label="Filtrar configuración">
      <button type="button" class="is-active" data-agent-configuration="all" aria-pressed="true">Todos</button>
      <button type="button" data-agent-configuration="configured" aria-pressed="false">Personalizados</button>
      <button type="button" data-agent-configuration="default" aria-pressed="false">Instrucciones iniciales</button>
    </div>
    <div class="{{ $agentUiPrefix }}-catalog-toolbar-actions">
      <label class="{{ $agentUiPrefix }}-catalog-search"><span class="sr-only">Buscar agente</span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input type="search" placeholder="Buscar agente" data-agent-search></label>
      <label class="agent-state-filter"><span class="sr-only">Estado del agente</span><select data-agent-state><option value="all">Estado: Todos</option><option value="inactive">Inactivos</option><option value="active">Activos</option></select></label>
      <span class="agent-count" aria-live="polite" data-agent-count>{{ $agents->count() }} agentes</span>
      <button type="button" class="{{ $agentCreateClass }}" data-agent-create>+ Crear agente</button>
    </div>
  </div>
  <section class="{{ $agentUiPrefix }}-native-table-card {{ $agentUiPrefix }}-catalog-content-card">
    <div class="{{ $agentUiPrefix }}-native-table-scroll">
      <table class="{{ $agentUiPrefix }}-native-table unit-agent-table" data-drsam-table-filter-skip>
        <caption class="sr-only">Agentes e instrucciones de {{ $agentOwnerName }}</caption>
        <thead><tr><th>Agente</th><th>Instrucciones del agente</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          @foreach ($agents as $agent)
            <tr data-agent-row="{{ $agent['id'] }}">
              <td><span class="agent-name"><span aria-hidden="true">@include('unit.agents.icon')</span><strong data-agent-name>{{ $agent['name'] }}</strong></span></td>
              <td><span data-agent-objective>{{ $agent['objective'] }}</span><small data-agent-updated>{{ $agent['updated_at'] ? 'Instrucciones personalizadas' : 'Instrucciones iniciales' }}</small></td>
              <td><span class="agent-status"><span aria-hidden="true">●</span> Inactivo</span></td>
              <td><button type="button" data-agent-edit="{{ $agent['id'] }}" aria-label="Editar instrucciones de {{ $agent['name'] }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 4 5 5M4 20l5-1L21 7a2 2 0 0 0-5-5L4 14Z"/></svg>Editar instrucciones</button></td>
            </tr>
          @endforeach
          <tr data-agent-empty hidden><td colspan="4" class="{{ $agentUiPrefix }}-native-empty">No hay agentes que coincidan con los filtros.</td></tr>
        </tbody>
      </table>
    </div>
  </section>
</div>

<dialog class="unit-agent-dialog" data-agent-dialog aria-labelledby="agent-editor-title">
  <form data-agent-form>
    <header><div><h2 id="agent-editor-title">Editar instrucciones</h2><p data-agent-editor-name></p></div><span class="agent-status">● Inactivo</span><button type="button" class="agent-close" data-agent-close aria-label="Cerrar formulario" title="Cerrar">&times;</button></header>
    <div class="agent-dialog-body">
      <div class="agent-errors" data-agent-errors role="alert" hidden></div>
      <label data-agent-name-field>Nombre del agente<input name="name" required maxlength="100" autocomplete="off"></label>
      <label>Objetivo<input name="objective" required maxlength="500"></label>
      <label>Instrucciones del agente<textarea name="instructions" required maxlength="10000" rows="6"></textarea></label>
      <fieldset class="agent-sources"><legend>Fuentes de información</legend><p class="agent-help">{{ $agentSourcesHelp }}</p><div class="agent-source-options">
        @foreach ($agentSources as $key => $label)
          <label data-agent-source-option><input type="checkbox" name="sources" value="{{ $key }}"><span>{{ $label }}</span></label>
        @endforeach
      </div><button type="button" class="agent-source-toggle" data-agent-source-toggle aria-expanded="false">Cambiar fuentes</button></fieldset>
      <div class="agent-form-grid"><label>Alcance<input value="{{ $agentScope }}" readonly></label><label>Formato de respuesta<input name="response_format" required maxlength="200"></label></div>
      <label>Columnas del reporte<input name="report_columns" required maxlength="500" placeholder="Servicio · Área · Responsable · Fecha · Estado"></label>
      <section class="agent-preview" data-agent-preview hidden aria-live="polite"><h3>Vista previa del reporte</h3><p>Ejemplo de estructura, sin ejecutar el agente ni consultar registros.</p><div class="{{ $agentUiPrefix }}-native-table-scroll"><table><thead><tr data-agent-preview-columns></tr></thead><tbody><tr data-agent-preview-row></tr></tbody></table></div></section>
      <p class="agent-help">Guardar personaliza las instrucciones. El agente permanece inactivo.</p>
    </div>
    <footer><button type="button" data-agent-preview-button>Vista previa</button><span class="agent-save-state" data-agent-save-state aria-live="polite"></span><button type="button" data-agent-close>Cancelar</button><button type="submit" class="is-primary" data-agent-save>Guardar instrucciones</button></footer>
  </form>
</dialog>
<dialog class="unit-agent-dialog agent-success-dialog" data-agent-success aria-labelledby="agent-success-title"><header><h2 id="agent-success-title">Guardado correctamente</h2><button type="button" class="agent-close" data-agent-success-close aria-label="Cerrar mensaje" title="Cerrar">&times;</button></header><p>{{ $agentSavedMessage }}</p></dialog>
<script>
  window.agentCenterConfig = {{ Illuminate\Support\Js::from([
    'agents' => $agents->all(), 'storeUrl' => $agentStoreUrl, 'updateBase' => $agentUpdateBase,
    'context' => $agentContext, 'createLabel' => $agentCreateLabel, 'csrf' => csrf_token(),
  ]) }};
</script>
@push('scripts')
  <script src="{{ asset('js/unit-agents.js') }}?v={{ filemtime(public_path('js/unit-agents.js')) }}" defer></script>
@endpush
