(() => {
  const dialog = document.querySelector('[data-request-interaction-dialog]');
  if (!dialog) return;
  const history = dialog.querySelector('[data-interaction-history]');
  const error = dialog.querySelector('[data-interaction-error]');
  const form = dialog.querySelector('[data-interaction-form]');
  const body = form.elements.body;
  const submit = form.querySelector('[data-interaction-submit]');
  const tabs = [...dialog.querySelectorAll('[data-interaction-tab]')];
  const csrf = form.querySelector('[name="_token"]').value;
  let activeRow;
  let kind = 'message';
  let data;
  let loading = false;
  let saving = false;
  let loadController;
  let opener;

  const element = (tag, text, className) => {
    const node = document.createElement(tag);
    if (text != null) node.textContent = text;
    if (className) node.className = className;
    return node;
  };
  const showError = message => {
    error.textContent = message;
    error.hidden = !message;
  };
  const request = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      ...options,
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(Object.values(payload.errors || {}).flat().join(' ') || payload.message || 'No se pudo completar la operación. Intenta de nuevo.');
    }
    return payload;
  };
  const updateRows = payload => {
    document.querySelectorAll('[data-interaction-request]').forEach(row => {
      if (row.dataset.interactionRequest !== activeRow.dataset.interactionRequest) return;
      row.dataset.messageCount = payload.message_count;
      row.dataset.adjustmentCount = payload.open_adjustment_count;
      const count = row.querySelector('[data-request-message-count]');
      count.textContent = payload.message_count;
      count.hidden = !payload.message_count;
      row.querySelector('.unit-request-message-button').classList.toggle('has-messages', payload.message_count > 0);
      row.querySelector('.unit-request-adjustment-button').classList.toggle('has-adjustments', payload.open_adjustment_count > 0);
      row.querySelector('[data-request-adjustment-label]').textContent = payload.open_adjustment_count ? `En ajuste (${payload.open_adjustment_count})` : 'Sin ajustes';
      row.closest('[data-unit-request-board]').dispatchEvent(new CustomEvent('unit:request-interactions-updated'));
    });
  };
  const render = () => {
    const adjustment = kind === 'adjustment';
    tabs.forEach(tab => {
      const selected = tab.dataset.interactionTab === kind;
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
      tab.disabled = saving;
    });
    dialog.querySelector('[role="tabpanel"]').setAttribute('aria-labelledby', adjustment ? 'unit-request-adjustments-tab' : 'unit-request-messages-tab');
    dialog.querySelector('[data-interaction-help]').textContent = adjustment
      ? 'Registra el cambio que necesita revisión y consulta su seguimiento.'
      : 'Conversación interna para los usuarios autorizados de esta unidad.';
    dialog.querySelector('[data-interaction-body-label]').textContent = adjustment ? 'Motivo del ajuste' : 'Nuevo mensaje';
    body.placeholder = adjustment ? 'Describe el ajuste solicitado…' : 'Escribe un mensaje…';
    submit.textContent = saving ? 'Guardando…' : adjustment ? 'Solicitar ajuste' : 'Enviar mensaje';
    const closed = adjustment && data && !data.can_request_adjustment;
    form.hidden = Boolean(closed);
    dialog.querySelector('[data-interaction-closed]').hidden = !closed;
    body.disabled = submit.disabled = loading || saving || !data || Boolean(closed);
    history.replaceChildren();
    if (loading) {
      history.append(element('p', 'Cargando historial…', 'unit-request-interaction-empty'));
      return;
    }
    if (!data) {
      const retry = element('button', 'Reintentar', 'unit-request-interaction-retry');
      retry.type = 'button';
      retry.addEventListener('click', load);
      history.append(retry);
      return;
    }
    const entries = data.interactions.filter(entry => entry.type === kind);
    if (!entries.length) history.append(element('p', adjustment ? 'No hay ajustes registrados para esta solicitud.' : 'Aún no hay mensajes. Inicia la conversación.', 'unit-request-interaction-empty'));
    entries.forEach(entry => {
      const card = element('article', null, 'unit-request-interaction-entry');
      const header = element('header');
      header.append(element('strong', entry.author), element('time', entry.created_at));
      if (adjustment) header.append(element('span', entry.status === 'open' ? 'Pendiente' : 'Resuelto', `unit-request-interaction-badge is-${entry.status}`));
      card.append(header, element('p', entry.body, 'unit-request-interaction-text'));
      if (entry.resolved_at) {
        const resolution = element('div', null, 'unit-request-interaction-resolution');
        resolution.append(element('strong', `Resuelto por ${entry.resolved_by || 'Usuario no disponible'} · ${entry.resolved_at}`), element('p', entry.resolution, 'unit-request-interaction-text'));
        card.append(resolution);
      } else if (adjustment && entry.resolve_url) {
        const resolveForm = element('form', null, 'unit-request-resolution-form');
        const label = element('label', 'Nota de resolución');
        const input = element('textarea');
        input.name = 'resolution';
        input.rows = 2;
        input.maxLength = 3000;
        input.required = true;
        input.disabled = saving;
        label.append(input);
        const button = element('button', 'Marcar como resuelto');
        button.type = 'submit';
        button.disabled = saving;
        resolveForm.append(label, button);
        resolveForm.addEventListener('submit', event => {
          event.preventDefault();
          if (!saving && input.value.trim()) save(entry.resolve_url, 'PATCH', {resolution: input.value.trim()}, false);
        });
        card.append(resolveForm);
      }
      history.append(card);
    });
  };
  const load = async () => {
    loadController?.abort();
    const controller = new AbortController();
    loadController = controller;
    loading = true;
    data = null;
    showError('');
    render();
    try {
      data = await request(activeRow.dataset.interactionUrl, {signal: controller.signal});
      updateRows(data);
    } catch (failure) {
      if (failure.name === 'AbortError') return;
      showError(failure.message);
    } finally {
      if (!controller.signal.aborted) {
        loading = false;
        render();
      }
    }
  };
  const save = async (url, method, payload, clearBody) => {
    saving = true;
    showError('');
    // Disable controls without rebuilding forms, preserving drafts if the request fails.
    dialog.querySelectorAll('button, textarea').forEach(control => { control.disabled = true; });
    submit.textContent = 'Guardando…';
    try {
      data = await request(url, {method, body: JSON.stringify(payload)});
      updateRows(data);
      if (clearBody) body.value = '';
      saving = false;
      render();
      history.scrollTop = history.scrollHeight;
    } catch (failure) {
      showError(failure.message);
      saving = false;
      submit.textContent = kind === 'adjustment' ? 'Solicitar ajuste' : 'Enviar mensaje';
    } finally {
      dialog.querySelectorAll('button, textarea').forEach(control => { control.disabled = false; });
    }
  };
  form.addEventListener('submit', event => {
    event.preventDefault();
    if (saving || loading || !data || !body.value.trim()) return;
    save(activeRow.dataset.interactionUrl, 'POST', {type: kind, body: body.value.trim()}, true);
  });
  const drafts = {message: '', adjustment: ''};
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      if (saving) return;
      drafts[kind] = body.value;
      kind = tab.dataset.interactionTab;
      body.value = drafts[kind];
      showError('');
      render();
    });
    tab.addEventListener('keydown', event => {
      if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        event.preventDefault();
        const next = tabs.find(item => item !== tab);
        next.click();
        next.focus();
      }
    });
  });
  document.querySelectorAll('[data-request-interaction-open]').forEach(button => {
    button.addEventListener('click', async () => {
      opener = button;
      activeRow = button.closest('[data-interaction-request]');
      kind = button.dataset.requestInteractionOpen;
      drafts.message = drafts.adjustment = body.value = '';
      dialog.querySelector('[data-request-interaction-folio]').textContent = `Solicitud ${activeRow.dataset.requestFolio}`;
      dialog.showModal();
      await load();
      if (dialog.open && button.hasAttribute('data-request-compose')) body.focus();
    });
  });
  dialog.querySelector('[data-request-interaction-close]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
  dialog.addEventListener('click', event => { if (event.target === dialog && !saving) dialog.close(); });
  dialog.addEventListener('close', () => {
    loadController?.abort();
    opener?.focus({preventScroll: true});
  });
})();
