(() => {
  const dialog = document.querySelector('[data-new-expedient-dialog]');
  if (!dialog) return;
  const form = dialog.querySelector('form');
  const panels = [...dialog.querySelectorAll('[data-wizard-step]')];
  const nav = [...dialog.querySelectorAll('[data-wizard-go]')];
  const feedback = dialog.querySelector('[data-wizard-feedback]');
  const scroll = dialog.querySelector('.ew-scroll');
  const next = dialog.querySelector('[data-wizard-next]');
  const input = name => form.elements.namedItem(name);
  const value = name => input(name)?.value?.trim() ?? '';
  const selected = name => input(name)?.selectedOptions?.[0]?.textContent?.trim() ?? '';
  const text = (tag, content, className) => { const el = document.createElement(tag); el.textContent = content; if (className) el.className = className; return el; };
  let step = 0, reached = 0, draftId = null, busy = false, files = [], removed = [], matches = [], checkedIdentity = '';
  const fullName = () => ['first_name', 'first_surname', 'second_surname'].map(value).filter(Boolean).join(' ');
  const birth = () => value('birth_date') ? value('birth_date').split('-').reverse().join('/') : 'Fecha pendiente';
  const identity = () => ['first_name', 'first_surname', 'second_surname', 'birth_date', 'curp'].map(value).join('|');
  function notify(message, error = false) {
    feedback.textContent = message; feedback.hidden = !message; feedback.classList.toggle('is-error', error);
    if (message) scroll.scrollTop = 0;
  }
  function navigation() {
    nav.forEach((button, index) => {
      button.disabled = busy || index > reached;
      if (index === step) button.setAttribute('aria-current', 'step'); else button.removeAttribute('aria-current');
      button.parentElement.classList.toggle('is-done', index < step);
      button.querySelector('span').textContent = index < step ? '✓' : index + 1;
    });
    dialog.querySelector('[data-wizard-back]').disabled = busy || step === 0;
    next.textContent = busy ? 'Procesando…' : step === 5 ? 'Crear expediente' : 'Continuar  →';
  }
  function setBusy(state) {
    busy = state; dialog.setAttribute('aria-busy', String(state)); scroll.inert = state;
    dialog.querySelectorAll('button').forEach(button => { button.disabled = state; }); navigation();
  }
  function showStep(index, focus = true) {
    step = Math.max(0, Math.min(5, index)); reached = Math.max(reached, step);
    panels.forEach((panel, i) => { panel.hidden = i !== step; });
    dialog.querySelector('[data-wizard-patient-summary]').hidden = step === 0 || step === 5;
    dialog.querySelector('[data-patient-summary-name]').textContent = fullName() || 'Paciente pendiente';
    dialog.querySelector('[data-patient-summary-detail]').textContent = [birth(), value('sex')].filter(Boolean).join(' · ');
    scroll.scrollTop = 0; navigation();
    if (focus) panels[step].querySelector('h3').focus();
    if (step === 5) renderReview();
  }
  function validThrough(last) {
    for (let index = 0; index <= last; index++) {
      for (const field of panels[index].querySelectorAll('input,select,textarea')) {
        if (field.disabled || field.type === 'hidden' || field.type === 'file') continue;
        field.value = field.value.trim();
        if (!field.checkValidity()) {
          showStep(index); field.setAttribute('aria-invalid', 'true'); field.reportValidity(); field.focus(); return false;
        }
      }
    }
    return true;
  }
  function renderReview() {
    const review = dialog.querySelector('[data-wizard-review]'); review.replaceChildren();
    const rows = [
      ['patient', 'Paciente', fullName(), [birth(), value('sex'), value('curp')].filter(Boolean).join(' · '), 0],
      ['contact', 'Contacto', value('phone') || 'Teléfono pendiente', [value('email'), value('address'), value('contact_name'), value('contact_phone')].filter(Boolean).join(' · '), 1],
      ['care', 'Atención', value('new_hospital_name') || (value('medical_unit_id') ? selected('medical_unit_id') : 'Hospital pendiente'), [value('institution_id') ? selected('institution_id') : '', value('doctor_id') ? selected('doctor_id') : '', value('specialty'), value('service')].filter(Boolean).join(' · '), 2],
      ['record', 'Expediente', value('expedient_type'), [`Prioridad ${value('priority').toLowerCase()}`, 'En documentación', value('medication_name'), value('notes')].filter(Boolean).join(' · '), 3],
      ['files', 'Documentos', files.length ? files.map(file => file.name).join(' · ') : 'Sin documentos adjuntos', files.length ? `${files.length} archivo(s)` : 'Puedes agregarlos después', 4],
    ];
    rows.forEach(([icon, label, main, detail, target]) => {
      const row = text('div', '', 'ew-review-row');
      const symbol = text('span', '', 'ew-symbol'); symbol.setAttribute('aria-hidden', 'true'); symbol.append(dialog.querySelector(`[data-wizard-icon="${icon}"]`).content.cloneNode(true));
      const content = text('div', ''); content.append(text('strong', main), text('small', detail));
      const edit = text('button', '✎ Editar', 'ew-text-button'); edit.type = 'button'; edit.setAttribute('aria-label', `Editar ${label.toLowerCase()}`); edit.addEventListener('click', () => showStep(target));
      row.append(symbol, text('strong', label), content, edit); review.append(row);
    });
    const pending = [!value('phone') && 'Contacto telefónico', !value('medication_name') && 'medicamento', !value('medical_unit_id') && !value('new_hospital_name') && 'hospital'].filter(Boolean);
    const notice = dialog.querySelector('[data-wizard-pending]'); notice.hidden = !pending.length;
    notice.textContent = `ⓘ ${pending.join(', ')}: pendientes de completar.`;
  }
  async function json(url, options = {}) {
    const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': value('_token'), ...options.headers } });
    let data; try { data = await response.json(); } catch { throw new Error('No se pudo completar la solicitud. Revisa tu conexión y vuelve a intentar.'); }
    if (!response.ok) {
      const error = new Error(data.errors ? Object.values(data.errors).flat().join(' ') : response.status === 419 ? 'Tu sesión ha caducado. Abre de nuevo la sesión antes de guardar.' : 'No se pudo completar la solicitud. Vuelve a intentar.');
      error.fields = data.errors ?? {}; throw error;
    }
    return data;
  }
  function report(error) {
    const fieldName = Object.keys(error.fields ?? {})[0];
    const field = fieldName && input(fieldName.split('.')[0]);
    const panel = field?.closest('[data-wizard-step]');
    if (panel) { showStep(Number(panel.dataset.wizardStep)); field.setAttribute('aria-invalid', 'true'); }
    notify(error.message || 'No se pudo guardar. Intenta de nuevo.', true);
  }
  async function checkMatches() {
    if (checkedIdentity === identity()) return;
    const signature = identity(); const payload = new FormData();
    ['first_name', 'first_surname', 'second_surname', 'birth_date', 'curp'].forEach(name => payload.append(name, value(name)));
    const data = await json(dialog.dataset.matchesUrl, { method: 'POST', body: payload });
    if (signature !== identity()) return;
    matches = data.matches; checkedIdentity = signature;
    const box = dialog.querySelector('[data-patient-matches]'); box.replaceChildren(); box.hidden = !matches.length;
    if (!matches.length) { input('patient_id').value = ''; input('confirm_new_patient').value = '0'; return; }
    box.append(text('strong', 'Encontramos posibles coincidencias. Revisa antes de crear.'));
    [...matches.map(patient => [String(patient.id), `${patient.name} · ${patient.birth_date ? patient.birth_date.split('-').reverse().join('/') : 'Fecha pendiente'}`]), ['new', 'Es una persona distinta; crear un paciente nuevo']].forEach(([id, labelText]) => {
      const label = text('label', ''); const radio = document.createElement('input'); radio.type = 'radio'; radio.name = 'patient_match_choice'; radio.value = id;
      radio.checked = id === value('patient_id') || (id === 'new' && value('confirm_new_patient') === '1');
      radio.addEventListener('change', () => { input('patient_id').value = id === 'new' ? '' : id; input('confirm_new_patient').value = id === 'new' ? '1' : '0'; });
      label.append(radio, text('span', labelText)); box.append(label);
    });
  }
  function renderFiles() {
    const list = dialog.querySelector('[data-file-list]'); list.replaceChildren();
    const types = ['Receta médica', 'INE', 'Comprobante de domicilio', 'Cédula fiscal', 'CURP', 'Resumen clínico', 'Otro documento'];
    files.forEach(item => {
      const row = text('div', '', 'ew-file'); const detail = text('div', '');
      detail.append(text('strong', item.name), text('small', `${Math.max(1, Math.round(item.size / 1024))} KB${item.id ? ' · Guardado' : ''}`));
      row.append(text('span', '▤'), detail);
      if (!item.id) {
        const type = document.createElement('select'); type.setAttribute('aria-label', `Tipo de documento: ${item.name}`);
        types.forEach(name => { const option = text('option', name); option.value = name; type.append(option); });
        type.value = item.type; type.addEventListener('change', () => { item.type = type.value; }); row.append(type);
      } else row.append(text('span', item.type));
      row.append(text('span', '✓ Adjunto', 'ew-attached'));
      const preview = text('a', '↗'); preview.href = item.url; preview.target = '_blank'; preview.rel = 'noopener'; preview.setAttribute('aria-label', `Ver ${item.name}`);
      const remove = text('button', '×'); remove.type = 'button'; remove.setAttribute('aria-label', `Quitar ${item.name}`);
      remove.addEventListener('click', () => { if (item.id) removed.push(item.id); else URL.revokeObjectURL(item.url); files = files.filter(file => file !== item); renderFiles(); });
      row.append(preview, remove); list.append(row);
    });
  }
  function addFiles(incoming) {
    const errors = [];
    for (const file of incoming) {
      if (!/\.(pdf|jpe?g|png)$/i.test(file.name) || !['application/pdf', 'image/jpeg', 'image/png', ''].includes(file.type)) { errors.push(`${file.name}: usa PDF, JPG o PNG.`); continue; }
      if (file.size > 10 * 1024 * 1024) { errors.push(`${file.name}: supera los 10 MB.`); continue; }
      if (files.length >= 20) { errors.push('Puedes adjuntar hasta 20 documentos.'); break; }
      if (files.some(item => !item.id && item.name === file.name && item.size === file.size && item.file.lastModified === file.lastModified)) continue;
      files.push({ name: file.name, size: file.size, file, type: /receta/i.test(file.name) ? 'Receta médica' : /ine|identificaci[oó]n/i.test(file.name) ? 'INE' : 'Otro documento', url: URL.createObjectURL(file) });
    }
    renderFiles(); notify(errors.join(' '), errors.length > 0);
  }
  async function save(intent) {
    if (busy || (intent === 'create' && !validThrough(4))) return;
    const body = new FormData(form); body.set('intent', intent); body.set('step', String(step)); if (draftId) body.set('draft_id', String(draftId));
    files.filter(item => !item.id).forEach(item => { body.append('files[]', item.file); body.append('document_types[]', item.type); });
    removed.forEach(id => body.append('removed_documents[]', String(id)));
    setBusy(true); notify('');
    try {
      if (intent === 'create') {
        await checkMatches();
        if (matches.length && !value('patient_id') && value('confirm_new_patient') !== '1') throw new Error('Selecciona una coincidencia o confirma que se trata de una persona distinta.');
        body.set('patient_id', value('patient_id')); body.set('confirm_new_patient', value('confirm_new_patient'));
      }
      const result = await json(form.action, { method: 'POST', body });
      if (result.status !== 'draft') { window.location.assign(result.redirect); return; }
      draftId = result.id; files.filter(item => !item.id).forEach(item => URL.revokeObjectURL(item.url)); files = result.documents; removed = [];
      dialog.querySelector('[data-wizard-folio]').textContent = result.folio;
      dialog.querySelector('[data-wizard-folio-input]').value = result.folio;
      if (input('provider_id')?.tagName === 'SELECT') input('provider_id').setAttribute('data-saved-provider', value('provider_id'));
      renderFiles(); notify(result.message);
      const url = new URL(location.href); url.searchParams.set('draft', String(draftId)); history.replaceState(null, '', url);
    } catch (error) { report(error); } finally { setBusy(false); }
  }
  async function go(index) {
    if (busy || (index > step && !validThrough(index - 1))) return;
    notify(''); showStep(index);
    if (index === 5) { setBusy(true); try { await checkMatches(); } catch (error) { report(error); } finally { setBusy(false); } }
  }
  const units = input('medical_unit_id'), doctors = input('doctor_id');
  function filterAttention() {
    [...units.options].forEach(option => { option.hidden = option.disabled = !!option.value && !!value('institution_id') && option.dataset.institution !== value('institution_id'); });
    if (units.selectedOptions[0]?.disabled) units.value = '';
    [...doctors.options].forEach(option => { option.hidden = option.disabled = !!option.value && !!option.dataset.unit && option.dataset.unit !== value('medical_unit_id'); });
    if (doctors.selectedOptions[0]?.disabled) doctors.value = '';
  }
  input('institution_id').addEventListener('change', filterAttention);
  units.addEventListener('change', () => { input('new_hospital_name').value = ''; dialog.querySelector('[data-new-hospital-field]').hidden = true; dialog.querySelector('[data-add-hospital]').textContent = '⊕ Agregar hospital'; filterAttention(); });
  doctors.addEventListener('change', () => { const option = doctors.selectedOptions[0]; input('specialty').value = option.dataset.specialty ?? ''; input('service').value = option.dataset.service ?? ''; });
  dialog.querySelector('[data-add-hospital]').addEventListener('click', event => {
    const field = dialog.querySelector('[data-new-hospital-field]'); field.hidden = !field.hidden;
    event.currentTarget.textContent = field.hidden ? '⊕ Agregar hospital' : 'Cancelar nuevo hospital';
    if (!field.hidden) { units.value = ''; doctors.value = ''; input('new_hospital_name').focus(); } else input('new_hospital_name').value = '';
    filterAttention();
  });
  form.addEventListener('input', event => { event.target.removeAttribute('aria-invalid'); if (panels[0].contains(event.target)) { checkedIdentity = ''; input('patient_id').value = ''; input('confirm_new_patient').value = '0'; } });
  form.addEventListener('submit', event => { event.preventDefault(); if (step === 5) save('create'); else go(step + 1); });
  nav.forEach(button => button.addEventListener('click', () => go(Number(button.dataset.wizardGo))));
  dialog.querySelector('[data-wizard-back]').addEventListener('click', () => go(step - 1));
  dialog.querySelector('[data-wizard-save]').addEventListener('click', () => save('draft'));
  const chooser = dialog.querySelector('[data-expedient-files]');
  dialog.querySelectorAll('[data-pick-files]').forEach(button => button.addEventListener('click', () => chooser.click()));
  chooser.addEventListener('change', () => { addFiles(chooser.files); chooser.value = ''; });
  const drop = dialog.querySelector('[data-file-drop]');
  ['dragenter', 'dragover'].forEach(type => drop.addEventListener(type, event => { event.preventDefault(); drop.classList.add('is-over'); }));
  drop.addEventListener('dragleave', () => drop.classList.remove('is-over'));
  drop.addEventListener('drop', event => { event.preventDefault(); drop.classList.remove('is-over'); addFiles(event.dataTransfer.files); });
  let previousOverflow = '';
  function open() { if (dialog.open) return; previousOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden'; dialog.showModal(); showStep(step, false); }
  document.querySelector('[data-new-expedient]')?.addEventListener('click', open);
  dialog.querySelector('[data-close-expedient]').addEventListener('click', () => { if (!busy) dialog.close(); });
  dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
  dialog.addEventListener('close', () => { document.body.style.overflow = previousOverflow; if (draftId) location.assign(new URL(location.href).pathname + '?section=1&action=0'); });
  async function resume(id) {
    open(); setBusy(true);
    try {
      const data = await json(`${dialog.dataset.draftBase}/${encodeURIComponent(id)}/draft`);
      Object.entries(data.fields).forEach(([name, val]) => { const field = input(name); if (field && typeof val !== 'object') field.value = val ?? ''; });
      draftId = data.id; files = data.documents; removed = [];
      dialog.querySelector('[data-new-hospital-field]').hidden = !value('new_hospital_name');
      dialog.querySelector('[data-wizard-folio]').textContent = data.folio; dialog.querySelector('[data-wizard-folio-input]').value = data.folio;
      filterAttention(); renderFiles(); showStep(Number(data.fields.step ?? 0));
      if (step === 5) await checkMatches();
    } catch (error) { report(error); } finally { setBusy(false); }
  }
  const resumeId = new URL(location.href).searchParams.get('draft');
  filterAttention(); showStep(0, false);
  if (resumeId) resume(resumeId);
})();
