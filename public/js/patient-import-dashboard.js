(() => {
  const dialog = document.querySelector('[data-patient-dashboard-dialog]');
  if (!dialog) return;
  const stages = ['Solicitud recibida', 'Documentación y revisión', 'Autorización', 'Preparación del pedido', 'Listo para envío', 'En camino', 'Entregado'];
  const descriptions = ['Registramos tu solicitud.', 'Revisamos los documentos de tu pedido.', 'Confirmamos la autorización.', 'Preparamos el medicamento para su envío.', 'Tu pedido está listo para salir.', 'Tu medicamento está en ruta.', 'El pedido llegó a su destino.'];
  const indexes = { requested: 0, received: 0, materialized: 0, draft: 0, accepted: 1, authorized: 2, dispensed: 3, preparing: 3, ready: 4, in_route: 5, delivered: 6 };
  let orders = [];
  const select = dialog.querySelector('[data-patient-dashboard-order]');
  const set = (key, text) => { dialog.querySelector(`[data-patient-dashboard-${key}]`).textContent = text; };
  const render = () => {
    const order = orders[Number(select.value)] ?? orders[0];
    const stopped = ['cancelled', 'rejected', 'materialization_failed'].includes(order.status);
    const known = Object.hasOwn(indexes, order.status);
    const current = known ? indexes[order.status] : -1;
    const percent = stopped || !known ? 0 : Math.round(current / (stages.length - 1) * 100);
    set('folio', order.folio);
    set('medicine', order.medicine);
    set('stage', stopped ? 'Pedido detenido' : known ? stages[current] : 'Estado pendiente de confirmar');
    set('percent', `${percent}%`);
    dialog.querySelector('[data-patient-dashboard-progress]').style.width = `${percent}%`;
    set('remaining', stopped ? 'El pedido necesita atención antes de continuar.' : !known ? 'Todavía no podemos calcular las etapas restantes.' : current === 6 ? '¡Tu pedido está entregado!' : `${stages.length - 1 - current} etapas pendientes para completar tu pedido.`);
    const timeline = dialog.querySelector('[data-patient-dashboard-timeline]');
    timeline.replaceChildren();
    stages.forEach((stage, index) => {
      const item = document.createElement('li');
      item.className = !stopped && current >= 0 ? index < current || current === 6 ? 'is-complete' : index === current ? 'is-current' : '' : '';
      const marker = document.createElement('span'); marker.textContent = item.className === 'is-complete' ? '✓' : index + 1;
      const content = document.createElement('div');
      const title = document.createElement('strong'); title.textContent = stage;
      const detail = document.createElement('small'); detail.textContent = descriptions[index];
      content.append(title, detail); item.append(marker, content); timeline.append(item);
    });
    set('note', order.demo ? 'Vista de demostración: este ejemplo no representa un pedido real.' : 'Estado registrado al abrir esta pantalla. Recarga la página para consultar actualizaciones. El avance representa etapas, no tiempo de entrega.');
  };
  document.querySelectorAll('[data-patient-dashboard]').forEach((button, index) => button.addEventListener('click', () => {
    set('name', `Hola, ${button.dataset.patientName}`);
    orders = JSON.parse(button.dataset.patientOrders);
    if (!orders.length) orders = [{ folio: 'Ejemplo de seguimiento', medicine: ['Tocilizumab', 'Asfotasa alfa', 'Cannabidiol', 'Nivolumab', 'Pembrolizumab'][index % 5], status: ['authorized', 'in_route', 'accepted', 'preparing', 'delivered'][index % 5], demo: true }];
    select.replaceChildren(...orders.map((order, index) => { const option = document.createElement('option'); option.value = index; option.textContent = order.folio; return option; }));
    render(); dialog.showModal();
  }));
  select.addEventListener('change', render);
  dialog.querySelector('[data-close-patient-dashboard]').addEventListener('click', () => dialog.close());
})();
