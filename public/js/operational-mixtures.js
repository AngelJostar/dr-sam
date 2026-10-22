(() => {
  const board = document.querySelector('[data-oncology-mixtures-board]');
  if (!board) return;
  const rows = [...board.querySelectorAll('[data-mixture-row]')];
  const search = board.querySelector('[data-mixtures-search]');
  const selected = board.dataset.mixturesFilter || 'all';
  const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  const matches = (row, status) => status === 'all' || status === 'scheduled'
    || (status === 'messages' ? Number(row.dataset.messageCount) > 0
      : status === 'adjustments' ? Number(row.dataset.adjustmentCount) > 0
        : status === 'history' ? row.dataset.closed === 'true' : row.dataset.category === status);
  const visibleRows = () => rows.filter(row => !row.hidden && !row.classList.contains('drsam-table-filter-hidden'));
  const summarize = () => {
    const count = visibleRows().length;
    board.querySelector('[data-mixtures-visible]').textContent = `${count} solicitudes visibles`;
    board.querySelector('[data-mixtures-empty]').hidden = count > 0;
  };
  const apply = () => {
    rows.forEach(row => { row.hidden = !matches(row, selected) || !normalize(row.textContent).includes(normalize(search.value.trim())); });
    summarize();
  };
  search.addEventListener('input', apply);
  board.addEventListener('drsam:filtered', summarize);
  board.addEventListener('unit:request-interactions-updated', apply);
  board.querySelector('[data-mixtures-export]').addEventListener('click', () => {
    const cellText = cell => {
      const copy = cell.cloneNode(true);
      copy.querySelectorAll('.drsam-table-filter-button').forEach(node => node.remove());
      return copy.textContent.replace(/\s+/g, ' ').trim();
    };
    const csvCell = value => `"${(/^[=+\-@\t\r]/.test(value) ? "'" + value : value).replace(/"/g, '""')}"`;
    const columns = [0, 1, 2, 3, 4, 5, 6, 7, 8, 11, 12, 13];
    const csv = [board.querySelector('thead tr'), ...visibleRows()]
      .map(row => columns.map(index => csvCell(cellText(row.cells[index]))).join(',')).join('\r\n');
    const url = URL.createObjectURL(new Blob(['\ufeff' + csv], {type: 'text/csv;charset=utf-8'}));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'mezclas-oncologicas.csv';
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  });
  board.querySelectorAll('[data-mixture-detail]').forEach(button => {
    const dialog = document.getElementById(button.dataset.mixtureDetail);
    button.addEventListener('click', () => dialog.showModal());
    dialog.querySelector('[data-mixture-detail-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => button.focus());
  });
  apply();
})();
