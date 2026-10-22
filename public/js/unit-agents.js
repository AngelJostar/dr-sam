(() => {
  'use strict';
  const config = window.agentCenterConfig;
  const root = document.querySelector('[data-agent-center]');
  if (!config || !root) return;
  const agents = new Map(config.agents.map(agent => [agent.id, agent]));
  const dialog = document.querySelector('[data-agent-dialog]');
  const form = dialog.querySelector('form');
  const success = document.querySelector('[data-agent-success]');
  const fields = ['name', 'objective', 'instructions', 'response_format', 'report_columns'];
  const sourceInputs = [...form.querySelectorAll('input[name="sources"]')];
  const sourceToggle = form.querySelector('[data-agent-source-toggle]');
  const errorBox = form.querySelector('[data-agent-errors]');
  const saveState = form.querySelector('[data-agent-save-state]');
  let editing = null;
  let trigger = null;
  let saving = false;
  let reloadAfterSuccess = false;
  let selected = 'all';
  let configuration = 'all';
  const normalize = value => String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');

  const filter = () => {
    const search = normalize(root.querySelector('[data-agent-search]').value.trim());
    const status = root.querySelector('[data-agent-state]').value;
    let visible = 0;
    root.querySelectorAll('[data-agent-row]').forEach(row => {
      const agent = agents.get(row.dataset.agentRow);
      row.hidden = (selected !== 'all' && selected !== agent.id)
        || (status !== 'all' && status !== agent.status)
        || (configuration === 'configured' && !agent.updated_at)
        || (configuration === 'default' && !!agent.updated_at)
        || !normalize(`${agent.name} ${agent.objective} ${agent.instructions}`).includes(search);
      if (!row.hidden) visible++;
    });
    root.querySelector('[data-agent-count]').textContent = `${visible} de ${agents.size} agentes`;
    root.querySelector('[data-agent-empty]').hidden = visible !== 0;
  };
  root.querySelectorAll('[data-agent-filter]').forEach(button => button.addEventListener('click', () => {
    selected = button.dataset.agentFilter;
    root.querySelectorAll('[data-agent-filter]').forEach(item => {
      item.classList.toggle('is-active', item === button);
      item.setAttribute('aria-pressed', String(item === button));
    });
    filter();
  }));
  root.querySelectorAll('[data-agent-configuration]').forEach(button => button.addEventListener('click', () => {
    configuration = button.dataset.agentConfiguration;
    root.querySelectorAll('[data-agent-configuration]').forEach(item => {
      item.classList.toggle('is-active', item === button);
      item.setAttribute('aria-pressed', String(item === button));
    });
    filter();
  }));
  root.querySelector('[data-agent-search]').addEventListener('input', filter);
  root.querySelector('[data-agent-state]').addEventListener('change', filter);
  const track = root.querySelector('[data-agent-track]');
  const scroll = direction => track.scrollBy({left: direction * 320, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'});
  root.querySelector('[data-agent-prev]').addEventListener('click', () => scroll(-1));
  root.querySelector('[data-agent-next]').addEventListener('click', () => scroll(1));

  const showSources = expanded => {
    sourceToggle.setAttribute('aria-expanded', String(expanded));
    sourceToggle.textContent = expanded ? 'Mostrar solo seleccionadas' : 'Cambiar fuentes';
    sourceInputs.forEach(input => { input.closest('label').hidden = !expanded && !input.checked; });
  };
  sourceToggle.addEventListener('click', () => showSources(sourceToggle.getAttribute('aria-expanded') !== 'true'));
  const open = (agent, button) => {
    editing = agent;
    trigger = button;
    form.reset();
    fields.forEach(name => { form.elements[name].value = agent?.[name] || ''; });
    form.querySelector('[data-agent-name-field]').hidden = !!agent;
    sourceInputs.forEach(input => { input.checked = (agent?.sources || []).includes(input.value); });
    showSources(!agent);
    errorBox.hidden = true;
    form.querySelector('[data-agent-preview]').hidden = true;
    saveState.textContent = agent?.updated_at ? 'Instrucciones guardadas' : 'Instrucciones iniciales';
    if (!agent) saveState.textContent = 'Sin guardar';
    dialog.querySelector('h2').textContent = agent ? 'Editar instrucciones' : 'Crear agente';
    dialog.querySelector('[data-agent-editor-name]').textContent = agent ? `Agente: ${agent.name}` : config.createLabel;
    dialog.showModal();
    form.elements[agent ? 'objective' : 'name'].focus({preventScroll: true});
  };
  root.querySelectorAll('[data-agent-edit]').forEach(button => button.addEventListener('click', () => open(agents.get(button.dataset.agentEdit), button)));
  root.querySelector('[data-agent-create]').addEventListener('click', event => open(null, event.currentTarget));
  form.addEventListener('input', () => { saveState.textContent = 'Cambios sin guardar'; form.querySelector('[data-agent-preview]').hidden = true; });
  form.querySelectorAll('[data-agent-close]').forEach(button => button.addEventListener('click', () => { if (!saving) dialog.close(); }));
  dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
  dialog.addEventListener('close', () => trigger?.focus({preventScroll: true}));
  form.querySelector('[data-agent-preview-button]').addEventListener('click', () => {
    const columns = form.elements.report_columns.value.split(/[·,\n|]/).map(value => value.trim()).filter(Boolean);
    const headings = form.querySelector('[data-agent-preview-columns]');
    const row = form.querySelector('[data-agent-preview-row]');
    headings.replaceChildren();
    row.replaceChildren();
    (columns.length ? columns : ['Pendiente', 'Responsable', 'Estado']).forEach(column => {
      const th = document.createElement('th'); th.textContent = column; headings.append(th);
      const td = document.createElement('td'); td.textContent = '—'; row.append(td);
    });
    const preview = form.querySelector('[data-agent-preview]');
    preview.hidden = false;
    preview.scrollIntoView({block: 'nearest'});
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (saving || !form.reportValidity()) return;
    const payload = Object.fromEntries(fields.map(name => [name, form.elements[name].value.trim()]));
    payload.sources = sourceInputs.filter(input => input.checked).map(input => input.value);
    if (editing) payload.version = editing.version;
    if (!payload.sources.length) {
      errorBox.textContent = 'Selecciona al menos una fuente de información.';
      errorBox.hidden = false;
      showSources(true);
      errorBox.scrollIntoView({block: 'nearest'});
      return;
    }
    saving = true;
    errorBox.hidden = true;
    form.querySelectorAll('button').forEach(button => { button.disabled = true; });
    saveState.textContent = 'Guardando…';
    try {
      const url = editing ? `${config.updateBase}/${encodeURIComponent(editing.id)}?${new URLSearchParams(config.context)}` : config.storeUrl;
      const response = await fetch(url, {method: editing ? 'PATCH' : 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf}, body: JSON.stringify(payload)});
      const result = await response.json();
      if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join('\n') : result.message || 'No se pudo guardar. Intenta de nuevo.');
      reloadAfterSuccess = !editing;
      if (editing) {
        agents.set(result.agent.id, result.agent);
        const row = [...root.querySelectorAll('[data-agent-row]')].find(item => item.dataset.agentRow === result.agent.id);
        row.querySelector('[data-agent-name]').textContent = result.agent.name;
        row.querySelector('[data-agent-objective]').textContent = result.agent.objective;
        row.querySelector('[data-agent-updated]').textContent = 'Instrucciones personalizadas';
        row.querySelector('[data-agent-edit]').setAttribute('aria-label', `Editar instrucciones de ${result.agent.name}`);
        const tile = [...root.querySelectorAll('[data-agent-filter]')].find(item => item.dataset.agentFilter === result.agent.id);
        tile.querySelector('strong').textContent = result.agent.name;
        const index = [...track.children].indexOf(tile);
        root.querySelectorAll('.workspace-carousel-indicator')[index]?.setAttribute('aria-label', `Mostrar ${result.agent.name}`);
        filter();
      }
      dialog.close();
      success.showModal();
    } catch (error) {
      errorBox.textContent = error instanceof SyntaxError ? 'No se pudo guardar. Verifica tu sesión y vuelve a intentarlo.' : error.message;
      errorBox.hidden = false;
      saveState.textContent = 'Cambios sin guardar';
      errorBox.scrollIntoView({block: 'nearest'});
    } finally {
      saving = false;
      form.querySelectorAll('button').forEach(button => { button.disabled = false; });
    }
  });
  success.querySelector('[data-agent-success-close]').addEventListener('click', () => success.close());
  success.addEventListener('close', () => { if (reloadAfterSuccess) window.location.reload(); else trigger?.focus({preventScroll: true}); });
  filter();
})();
