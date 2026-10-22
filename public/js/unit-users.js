(() => {
  'use strict';
  const dialog = document.querySelector('[data-operational-user-dialog]');
  const config = window.unitUserDialogConfig;
  if (!dialog || !config) return;
  const form = dialog.querySelector('form');
  const method = form.querySelector('[data-operational-method]');
  const title = dialog.querySelector('[data-operational-form-title]');
  const errors = dialog.querySelector('[data-operational-user-errors]');
  const editButtons = [...document.querySelectorAll('[data-edit-operational-user]')];
  const setSelect = (name, value) => {
    const select = form.elements[name];
    if (![...select.options].some(option => option.value === value)) select.add(new Option(value, value));
    select.value = value;
  };
  const populate = data => {
    for (const name of ['name', 'username']) form.elements[name].value = data[name] || '';
    for (const name of ['role_label', 'authority', 'service']) if (data[name]) setSelect(name, data[name]);
    form.querySelectorAll('[name="operational_area_id"]').forEach(input => { input.checked = input.value === String(data.operational_area_id); });
    form.querySelectorAll('[name="permissions[]"]').forEach(input => { input.checked = (data.permissions || []).includes(input.value); });
  };
  const open = (button = null, previous = null) => {
    form.reset();
    if (errors) errors.hidden = !previous;
    form.action = button?.dataset.url || config.storeUrl;
    method.disabled = !button;
    form.elements._operational_profile.value = button?.dataset.profile || '';
    form.elements.password.value = '';
    form.elements.password.required = !button;
    title.textContent = button ? 'Editar usuario operativo' : 'Alta de usuario operativo';
    dialog.querySelector('[data-operational-password-help]').textContent = button ? 'Déjala vacía para conservar la contraseña actual.' : 'Mínimo 6 caracteres.';
    if (button) {
      const data = button.dataset;
      populate({name: data.name, username: data.username, role_label: data.role, authority: data.authority, service: data.service, operational_area_id: data.area, permissions: JSON.parse(data.permissions || '[]')});
    }
    if (previous) populate(previous);
    dialog.showModal();
    dialog.querySelector('.unit-doctor-dialog-body').scrollTop = 0;
  };
  document.querySelector('[data-open-operational-user]')?.addEventListener('click', () => open());
  editButtons.forEach(button => button.addEventListener('click', () => open(button)));
  dialog.querySelectorAll('[data-close-operational-user]').forEach(button => button.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('close', () => { form.elements.password.value = ''; });
  if (config.old) open(editButtons.find(button => button.dataset.profile === String(config.old._operational_profile)) || null, config.old);
  document.querySelector('[data-operational-export]')?.addEventListener('click', () => {
    const rows = [['Usuario', 'Correo', 'Rol', 'Área asignada', 'Permisos', 'Estado']];
    document.querySelectorAll('[data-operational-user-row]').forEach(row => {
      if (!row.hidden) rows.push([...row.cells].slice(0, 6).map(cell => cell.innerText.replace(/\s+/g, ' ').trim()));
    });
    const csv = rows.map(row => row.map(value => `"${value.replaceAll('"', '""')}"`).join(',')).join('\n');
    const url = URL.createObjectURL(new Blob(['\uFEFF' + csv], {type: 'text/csv;charset=utf-8'}));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'usuarios-operativos.csv';
    link.click();
    URL.revokeObjectURL(url);
  });
})();
