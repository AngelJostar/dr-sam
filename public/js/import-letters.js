(() => {
  const dialog = document.querySelector('[data-letter-dialog]');
  if (!dialog) return;
  const select = dialog.querySelector('[data-letter-type]');
  const content = dialog.querySelector('[data-letter-content]');
  const drafts = new Map();
  let folio = '', medicine = '', activeKey = '', patient = {};
  const load = () => {
    if (activeKey) drafts.set(activeKey, content.value);
    activeKey = `${folio}:${select.value}`;
    const purposes = [
      'Por medio de la presente, [nombre o razón social] encomienda a [agencia aduanal y agente, patente] las gestiones aduanales correspondientes al medicamento indicado, con el alcance siguiente: [describir alcance].',
      '[Nombre del responsable] manifiesta bajo su responsabilidad que la información y documentación presentada corresponde al medicamento indicado. Obligaciones y alcance de la responsabilidad: [completar].',
      '[Nombre del otorgante] otorga a [nombre del representante] poder para realizar ante Cofepris las gestiones siguientes: [especificar trámite y alcance].',
      'A quien corresponda en Cofepris: solicito [trámite] respecto del medicamento indicado. Motivo de la solicitud: [describir necesidad, antecedentes y justificación].',
    ];
    content.value = drafts.get(activeKey) ?? `${select.value.toUpperCase()}\nBORRADOR PARA REVISIÓN\n\n[Lugar], [fecha]\nExpediente: ${folio}\nMedicamento: ${medicine}\nPaciente: ${patient.name ?? '[nombre completo]'}\nCURP: ${patient.curp ?? '[pendiente]'}\nFecha de nacimiento: ${patient.birth ?? '[pendiente]'}\nDirección: ${patient.address ?? '[pendiente]'}\nHospital: ${patient.hospital ?? '[pendiente]'}\nPermiso Cofepris: [pendiente]\nAgente aduanal: [pendiente]\n\n${purposes[select.selectedIndex]}\n\nDocumentos anexos: [enumerar]\n\nNombre y firma: ____________________\nIdentificación: [completar]\nDatos de contacto: [completar]\n\nTestigos y firmas, cuando corresponda:\n1. ____________________\n2. ____________________`;
  };
  document.querySelectorAll('[data-expedient-editor]').forEach(button => button.addEventListener('click', () => {
    if (activeKey) drafts.set(activeKey, content.value);
    activeKey = ''; folio = button.dataset.folio; medicine = button.dataset.medicine;
    patient = JSON.parse(button.dataset.letterPatient ?? '{}');
    [...select.options].forEach(option => { option.hidden = option.dataset.group !== button.dataset.letterGroup; option.disabled = option.hidden; });
    select.value = [...select.options].find(option => !option.disabled).value;
    if (button.dataset.letterType) select.value = button.dataset.letterType;
    dialog.querySelector('[data-letter-folio]').textContent = folio;
    load(); dialog.showModal();
  }));
  select.addEventListener('change', load);
  dialog.querySelector('[data-letter-close]').addEventListener('click', () => dialog.close());
  dialog.querySelector('[data-letter-download]').addEventListener('click', () => {
    drafts.set(activeKey, content.value);
    const url = URL.createObjectURL(new Blob(['\ufeff', content.value], {type: 'text/plain;charset=utf-8'}));
    const link = document.createElement('a'); link.href = url; link.download = `${folio}-${select.value.replace(/[^a-zA-Z0-9]/g, '-')}.txt`;
    document.body.append(link); link.click(); link.remove(); setTimeout(() => URL.revokeObjectURL(url), 1000);
  });
})();
