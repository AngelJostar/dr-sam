(() => {
  const dialog = document.querySelector('[data-expedient-tracking]');
  if (!dialog) return;
  const stages = ['Solicitud registrada', 'Validación médica', 'Compra confirmada', 'Permiso sanitario', 'En tránsito', 'Entrega en hospital'];
  const positions = {documentation: 0, pending: 1, cofepris: 3, transit: 4, delivered: 5};
  const set = (name, value) => { dialog.querySelector(`[data-tracking-${name}]`).textContent = value; };
  document.querySelectorAll('[data-expedient-dashboard]').forEach(button => button.addEventListener('click', () => {
    const position = positions[button.dataset.status] ?? 0;
    set('medicine', button.dataset.medicine); set('folio', button.dataset.folio);
    set('count', `Etapa ${position + 1} de ${stages.length}`);
    set('remaining', position === 5 ? 'Pedido entregado' : `${5 - position} etapas pendientes`);
    set('percent', `${Math.round(position / 5 * 100)}%`);
    set('current', button.dataset.stage);
    set('next', stages[position + 1] ?? 'Seguimiento completado');
    const flight = dialog.querySelector('[data-flight-route]');
    flight.dataset.currentStep = position + 1;
    flight.dataset.totalSteps = stages.length;
    flight.dataset.stageLabels = stages.join('|');
    flight.setAttribute('aria-valuenow', position + 1);
    flight.setAttribute('aria-valuetext', `Etapa ${position + 1} de 6: ${stages[position]}`);
    flight.style.setProperty('--flight-progress', `${position / 5 * 100}%`);
    flight.querySelector('[data-flight-current]').textContent = `Etapa ${position + 1} de 6 · ${stages[position]}`;
    flight.querySelector('[data-flight-remaining]').textContent = position === 5 ? 'Pedido entregado' : `Faltan ${5 - position} etapas`;
    flight.querySelectorAll('[data-step]').forEach((step, index) => {
      step.title = stages[index];
      step.classList.toggle('is-complete', index <= position);
    });
    flight.classList.remove('is-flying');
    const timeline = dialog.querySelector('[data-tracking-stages]');
    const history = dialog.querySelector('[data-tracking-history]');
    timeline.replaceChildren(); history.replaceChildren();
    stages.forEach((stage, index) => {
      const item = document.createElement('li');
      item.className = index < position || position === 5 ? 'is-complete' : index === position ? 'is-current' : '';
      const marker = document.createElement('span'); marker.textContent = item.className === 'is-complete' ? '✓' : index + 1;
      const title = document.createElement('strong'); title.textContent = stage;
      const state = document.createElement('small'); state.textContent = index < position ? 'Etapa previa · Ejemplo' : index === position ? 'Etapa actual' : 'Pendiente';
      item.append(marker, title, state); timeline.append(item);
      if (index <= position) { const movement = document.createElement('li'); movement.textContent = `${stage} · ${index === position ? 'Actual' : 'Etapa previa ilustrativa'}`; history.append(movement); }
    });
    dialog.showModal();
    void flight.offsetWidth;
    flight.classList.add('is-flying');
  }));
  dialog.querySelector('[data-tracking-close]').addEventListener('click', () => dialog.close());
})();
