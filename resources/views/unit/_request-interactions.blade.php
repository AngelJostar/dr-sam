<dialog class="unit-request-interaction-dialog" data-request-interaction-dialog aria-labelledby="unit-request-interaction-title">
  <header>
    <div>
      <p data-request-interaction-folio></p>
      <h2 id="unit-request-interaction-title">Mensajes y ajustes</h2>
    </div>
    <button type="button" data-request-interaction-close aria-label="Cerrar mensajes y ajustes">×</button>
  </header>
  <div class="unit-request-interaction-tabs" role="tablist" aria-label="Seguimiento de la solicitud">
    <button type="button" data-interaction-tab="message" role="tab" aria-selected="true" aria-controls="unit-request-interaction-panel" id="unit-request-messages-tab">Mensajes</button>
    <button type="button" data-interaction-tab="adjustment" role="tab" aria-selected="false" aria-controls="unit-request-interaction-panel" id="unit-request-adjustments-tab">Ajustes</button>
  </div>
  <p class="unit-request-interaction-notice" data-interaction-error role="alert" hidden></p>
  <div id="unit-request-interaction-panel" role="tabpanel" aria-labelledby="unit-request-messages-tab">
    <p class="unit-request-interaction-help" data-interaction-help>Conversación interna de esta solicitud.</p>
    <div class="unit-request-interaction-history" data-interaction-history aria-live="polite"></div>
    <form class="unit-request-interaction-form" data-interaction-form>
      @csrf
      <label for="unit-request-interaction-body" data-interaction-body-label>Nuevo mensaje</label>
      <textarea id="unit-request-interaction-body" name="body" rows="3" maxlength="3000" required placeholder="Escribe un mensaje…" disabled></textarea>
      <div class="unit-request-interaction-form-footer">
        <small>Hasta 3000 caracteres</small>
        <button type="submit" data-interaction-submit disabled>Enviar mensaje</button>
      </div>
    </form>
    <p class="unit-request-interaction-help" data-interaction-closed hidden>Esta solicitud está cerrada. Puedes consultar el historial de ajustes.</p>
  </div>
</dialog>
