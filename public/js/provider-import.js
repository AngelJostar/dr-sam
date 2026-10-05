(() => {
  // Bind navigation before any optional panel or carousel initialization.
  document.querySelectorAll('[data-import-navigate]').forEach(button => {
    button.addEventListener('click', event => {
      event.preventDefault();
      event.stopPropagation();
      window.location.assign(button.dataset.importNavigate);
    }, true);
  });
  const supplierDialog = document.querySelector('[data-new-supplier-dialog]');
  document.querySelector('[data-new-supplier]')?.addEventListener('click', () => supplierDialog.showModal());
  supplierDialog?.querySelectorAll('[data-close-supplier]').forEach(button => button.addEventListener('click', () => supplierDialog.close()));
  if (supplierDialog?.dataset.errors === 'true') supplierDialog.showModal();
  document.querySelectorAll('.import-action-carousel').forEach(carousel => {
    const track = carousel.querySelector('.import-carousel-track');
    const arrows = [...carousel.querySelectorAll('[data-import-scroll]')];
    const update = () => arrows.forEach(button => {
      button.disabled = Number(button.dataset.importScroll) < 0
        ? track.scrollLeft <= 1
        : track.scrollLeft + track.clientWidth >= track.scrollWidth - 1;
    });
    track.addEventListener('scroll', update);
    window.addEventListener('resize', update);
    track.querySelector('.is-active')?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    update();
  });
  const groups = {
    'Activos': ['active'], 'Inactivos': ['inactive'],
    'Preferentes': ['preferred'], 'En validación': ['pending'], 'Riesgo documental': ['document_risk'],
    'Activas': ['active'], 'Por vencer': ['expiring'], 'Aprobadas': ['approved'], 'Rechazadas': ['rejected'],
    'En tránsito': ['transit'], 'En aduana': ['customs'], 'En entrega': ['in_route'],
    'En documentación': ['documentation'], 'Permiso Cofepris': ['cofepris'],
    'Pendientes': ['requested', 'received', 'materialized', 'pending', 'assigned'],
    'En proceso': ['pending', 'accepted', 'authorized', 'dispensed', 'preparing', 'ready', 'in_route'],
    'Autorizados': ['authorized'], 'Rechazados': ['rejected'], 'Vencidos': ['expired'],
    'Finalizados': ['delivered', 'completed'], 'En ruta': ['in_route'],
    'Entregados': ['delivered'], 'Aprobados': ['accepted', 'authorized'],
    'Cancelados': ['cancelled', 'rejected'],
  };
  document.querySelectorAll('[data-import-panel]').forEach(panel => {
    const rows = [...panel.querySelectorAll('[data-import-row]')];
    const search = panel.querySelector('[data-import-search]');
    const buttons = [...panel.querySelectorAll('[data-import-filter]')];
    const columnFilters = [...panel.querySelectorAll('[data-import-column]')];
    let filter = 'Todos';
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const apply = () => {
      const query = normalize(search.value.trim());
      rows.forEach(row => {
        row.hidden = !(filter === 'Todos' || (groups[filter] ?? []).includes(row.dataset.status))
          || !normalize(row.textContent).includes(query)
          || columnFilters.some(select => select.value && (row.cells[Number(select.dataset.importColumn)].dataset.filterValue ?? row.cells[Number(select.dataset.importColumn)].textContent.trim()) !== select.value);
      });
      const count = rows.filter(row => !row.hidden && !row.classList.contains('drsam-table-filter-hidden')).length;
      panel.querySelector('[data-import-count]').textContent = `${count} ${count === 1 ? 'registro' : 'registros'}`;
      panel.querySelector('[data-import-empty]').hidden = count > 0;
      panel.querySelector('[data-import-export]').disabled = count === 0;
    };
    buttons.forEach(button => button.addEventListener('click', () => {
      filter = button.dataset.importFilter;
      buttons.forEach(item => {
        const selected = item === button;
        item.classList.toggle('is-active', selected);
        item.setAttribute('aria-pressed', String(selected));
      });
      apply();
    }));
    search.addEventListener('input', apply);
    panel.addEventListener('drsam:filtered', apply);
    columnFilters.forEach(select => select.addEventListener('change', apply));
    apply();
    panel.querySelectorAll('[data-import-open]').forEach(button => button.addEventListener('click', () => {
      const dialog = document.querySelector('[data-import-dialog]');
      const row = button.closest('tr');
      dialog.querySelector('[data-import-detail-title]').textContent = row.cells[0].querySelector('strong')?.textContent ?? row.cells[0].textContent.trim();
      const fields = dialog.querySelector('[data-import-detail-fields]');
      fields.replaceChildren();
      [...row.cells].slice(0, -1).forEach((cell, index) => {
        const term = document.createElement('dt');
        term.textContent = panel.querySelector('thead').rows[0].cells[index].firstChild.textContent.trim();
        const value = document.createElement('dd');
        value.textContent = cell.textContent.trim();
        fields.append(term, value);
      });
      dialog.showModal();
    }));
    panel.querySelector('[data-import-export]').addEventListener('click', () => {
      const cells = row => [...row.cells].map(cell => {
        let value = cell.textContent.trim();
        if (/^[=+@\-\t\r]/.test(value)) value = `'${value}`;
        return `"${value.replaceAll('"', '""')}"`;
      }).join(',');
      const table = panel.querySelector('table');
      const headers = [...table.tHead.rows[0].cells].map(cell => `"${cell.firstChild.textContent.trim().replaceAll('"', '""')}"`).join(',');
      const csv = [headers, ...rows.filter(row => !row.hidden && !row.classList.contains('drsam-table-filter-hidden')).map(cells)].join('\r\n');
      const url = URL.createObjectURL(new Blob(['\ufeff', csv], { type: 'text/csv;charset=utf-8;' }));
      const link = document.createElement('a');
      link.href = url;
      link.download = 'importacion.csv';
      document.body.appendChild(link);
      link.click();
      link.remove();
      setTimeout(() => URL.revokeObjectURL(url), 1000);
    });
  });
})();
