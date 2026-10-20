(() => {
  const dialog = document.querySelector('[data-documents-dialog]');
  if (!dialog) return;
  const form = dialog.querySelector('[data-documents-form]');
  const list = dialog.querySelector('[data-documents-list]');
  const save = dialog.querySelector('[data-documents-save]');
  const download = dialog.querySelector('[data-documents-download]');
  const error = dialog.querySelector('[data-documents-error]');
  let canEdit = false;
  let documents = [];
  let submitting = false;
  const previews = new Set();
  const element = (tag, text, className) => {
    const el = document.createElement(tag);
    if (text) el.textContent = text;
    if (className) el.className = className;
    return el;
  };
  const updateSave = () => {
    save.hidden = !canEdit;
    save.disabled = submitting || ![...list.querySelectorAll('input[type=file]')].some(input => input.files.length);
  };
  function documentRow(item) {
    const row = list.insertRow();
    row.dataset.documentKey = item.key;
    const name = row.insertCell();
    name.append(element('strong', item.name));
    const filename = element('small', '', 'import-document-filename');
    name.append(filename);
    const status = element('span', '', 'import-document-status');
    status.setAttribute('aria-live', 'polite');
    row.insertCell().append(status);
    const addCell = canEdit ? row.insertCell() : null;
    const add = element('button', '', 'import-document-add');
    add.type = 'button';
    add.disabled = !canEdit;
    const input = element('input');
    input.type = 'file';
    input.name = `files[${item.key}]`;
    input.accept = '.pdf,.jpg,.jpeg,.png';
    input.hidden = true;
    input.disabled = !canEdit;
    input.setAttribute('aria-label', `Subir ${item.name}`);
    const clear = element('button', 'Quitar selección', 'import-document-clear');
    clear.type = 'button';
    clear.setAttribute('aria-label', `Quitar selección de ${item.name}`);
    addCell?.append(add, input, clear);
    const viewCell = row.insertCell();
    let previewUrl;
    const change = () => {
      if (previewUrl) { URL.revokeObjectURL(previewUrl); previews.delete(previewUrl); previewUrl = null; }
      error.hidden = true;
      const file = input.files[0];
      if (file && (file.size > 10 * 1024 * 1024 || !/\.(pdf|jpe?g|png)$/i.test(file.name))) {
        error.textContent = `${item.name}: selecciona un archivo PDF, JPG o PNG de hasta 10 MB.`;
        error.hidden = false;
        input.value = '';
      }
      const selected = input.files[0];
      filename.textContent = selected?.name || item.filename || '';
      filename.hidden = !filename.textContent;
      status.textContent = selected ? 'Por guardar' : item.url ? 'Cargado' : 'Pendiente';
      status.classList.toggle('is-loaded', !selected && Boolean(item.url));
      add.textContent = selected || item.url ? 'Cambiar' : 'Agregar';
      add.setAttribute('aria-label', `${add.textContent} ${item.name}`);
      clear.hidden = !selected;
      if (selected) {
        previewUrl = URL.createObjectURL(selected);
        previews.add(previewUrl);
      }
      const url = previewUrl || item.url;
      const view = element(url ? 'a' : 'button', selected ? 'Vista previa' : 'Ver', 'import-document-view');
      view.setAttribute('aria-label', `${selected ? 'Vista previa de' : 'Ver'} ${item.name}`);
      if (url) {
        view.href = url;
        view.target = '_blank';
        view.rel = 'noopener';
      } else {
        view.type = 'button';
        view.disabled = true;
        view.title = canEdit ? 'Agrega este documento para poder verlo.' : 'No hay archivo disponible.';
      }
      viewCell.replaceChildren(view);
      updateSave();
    };
    add.addEventListener('click', () => input.click());
    input.addEventListener('change', change);
    clear.addEventListener('click', () => { input.value = ''; change(); add.focus(); });
    row.addEventListener('dragover', event => {
      event.preventDefault();
      if (!canEdit) return;
      row.classList.add('is-dragging');
    });
    row.addEventListener('dragleave', event => {
      if (!row.contains(event.relatedTarget)) row.classList.remove('is-dragging');
    });
    row.addEventListener('drop', event => {
      event.preventDefault();
      row.classList.remove('is-dragging');
      if (!canEdit) return;
      if (event.dataTransfer.files.length !== 1) {
        error.textContent = 'Agrega un solo archivo en cada documento.';
        error.hidden = false;
        return;
      }
      const transfer = new DataTransfer();
      transfer.items.add(event.dataTransfer.files[0]);
      input.files = transfer.files;
      change();
    });
    change();
  }
  function open(button) {
    previews.forEach(url => URL.revokeObjectURL(url));
    previews.clear();
    form.reset();
    submitting = false;
    save.textContent = 'Guardar archivos';
    canEdit = button.dataset.canEdit === 'true';
    dialog.dataset.readOnly = String(!canEdit);
    dialog.querySelector('[data-documents-add-heading]').hidden = !canEdit;
    dialog.querySelector('[data-documents-upload-help]').hidden = !canEdit;
    dialog.querySelector('[data-documents-read-help]').hidden = canEdit;
    documents = JSON.parse(button.dataset.documents);
    dialog.querySelector('[data-documents-title]').textContent = button.dataset.documentTitle;
    dialog.querySelector('[data-documents-subtitle]').textContent = `Expediente ${button.dataset.folio}`;
    dialog.querySelector('[data-documents-folio]').value = button.dataset.folio;
    dialog.querySelector('[data-documents-group]').value = button.dataset.documentGroup;
    error.hidden = true;
    list.replaceChildren();
    documents.forEach(documentRow);
    download.disabled = !documents.some(item => item.url);
    updateSave();
    dialog.showModal();
  }
  document.querySelectorAll('[data-expedient-documents]').forEach(button => button.addEventListener('click', () => open(button)));
  dialog.querySelectorAll('[data-documents-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('close', () => { previews.forEach(url => URL.revokeObjectURL(url)); previews.clear(); });
  download.addEventListener('click', () => documents.filter(item => item.url).forEach(item => {
    const link = document.createElement('a');
    const url = new URL(item.url, location.origin);
    url.searchParams.set('download', '1');
    link.href = url.href;
    link.download = '';
    document.body.append(link);
    link.click();
    link.remove();
  }));
  form.addEventListener('submit', event => {
    if (!canEdit || save.disabled || submitting) { event.preventDefault(); return; }
    submitting = true;
    save.disabled = true;
    save.textContent = 'Guardando…';
  });
  window.addEventListener('pageshow', () => { submitting = false; save.textContent = 'Guardar archivos'; updateSave(); });
  if (dialog.dataset.reopenFolio) {
    const button = [...document.querySelectorAll('[data-expedient-documents]')].find(item =>
      item.dataset.folio === dialog.dataset.reopenFolio && item.dataset.documentGroup === dialog.dataset.reopenGroup);
    if (button) open(button);
  }
})();
