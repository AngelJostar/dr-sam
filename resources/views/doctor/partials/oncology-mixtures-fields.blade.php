@php
  $catalogItems = collect($catalogItems ?? [])->values();
  $infusors = collect($infusors ?? [])->values();
  $savedMixtures = old('oncology.mixtures', data_get($existingClinicalFormat ?? [], 'mixtures', []));
@endphp

<div class="doctor-oncology-mixture-builder" data-oncology-mixture-builder>
  <div class="doctor-oncology-mixture-count">
    <label>N&uacute;mero de mezclas solicitadas*</label>
    <div>
      <button type="button" data-mixture-count-down aria-label="Quitar una mezcla">&minus;</button>
      <input type="number" name="oncology[mixture_count]" min="1" max="99" value="{{ max(1, count($savedMixtures)) }}" required data-mixture-count>
      <button type="button" data-mixture-count-up aria-label="Agregar una mezcla">+</button>
    </div>
    <small>Cada fecha de entrega generar&aacute; un ID de mezcla y una remisi&oacute;n independiente.</small>
  </div>

  <div data-mixtures-container></div>
  <button type="button" class="doctor-oncology-add-mixture" data-add-mixture>+ Agregar mezcla</button>
</div>

<style>
  .doctor-oncology-mixture-count{margin:0 0 14px}.doctor-oncology-mixture-count>label{display:block;font-weight:700;margin-bottom:5px}.doctor-oncology-mixture-count>div{display:flex;width:142px}.doctor-oncology-mixture-count button,.doctor-oncology-mixture-count input{height:38px;border:1px solid #cbd5e1;background:#fff;text-align:center}.doctor-oncology-mixture-count button{width:42px;font-size:18px}.doctor-oncology-mixture-count input{width:58px;border-left:0;border-right:0}.doctor-oncology-mixture-count small{display:block;margin-top:5px;color:#64748b}.doctor-oncology-mixture{border:1px solid #cbd5e1;border-radius:10px;padding:14px;margin:14px 0}.doctor-oncology-mixture-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}.doctor-oncology-mixture-head h3{margin:0;font-size:16px}.doctor-oncology-mixture-head button,.doctor-oncology-remove-row,.doctor-oncology-remove-date{border:0;border-radius:5px;background:#ef4444;color:#fff;padding:7px 10px}.doctor-oncology-mixture table{width:100%;border-collapse:collapse}.doctor-oncology-mixture th,.doctor-oncology-mixture td{border:1px solid #dbe3ee;padding:6px}.doctor-oncology-mixture th{background:#f5f7fa;font-size:11px}.doctor-oncology-mixture input,.doctor-oncology-mixture select{width:100%;min-height:36px;border:1px solid #cbd5e1;border-radius:5px;padding:5px}.doctor-oncology-mixture-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}.doctor-oncology-mixture-actions>button,.doctor-oncology-add-mixture,.doctor-oncology-add-date{border:1px solid #059669;border-radius:5px;background:#fff;color:#047857;font-weight:700;padding:8px 12px}.doctor-oncology-mixture-details{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px}.doctor-oncology-dates{margin-top:12px;padding:12px;border:1px solid #a7f3d0;background:#ecfdf5;border-radius:6px}.doctor-oncology-date-row{display:flex;gap:8px;margin-top:7px}.doctor-oncology-date-row input{max-width:760px}.doctor-oncology-mixture-options{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}@media(max-width:900px){.doctor-oncology-mixture{overflow:auto}.doctor-oncology-mixture table{min-width:920px}.doctor-oncology-mixture-details,.doctor-oncology-mixture-options{grid-template-columns:1fr}}
  .doctor-oncology-mixture-actions>button,
  .doctor-oncology-add-mixture,
  .doctor-oncology-add-date {
    border-color: #047857;
    background: #059669;
    color: #fff;
  }
  .doctor-oncology-mixture-actions>button:hover,
  .doctor-oncology-add-mixture:hover,
  .doctor-oncology-add-date:hover {
    background: #047857;
    color: #fff;
  }
  .doctor-oncology-mixture-actions>button:focus-visible,
  .doctor-oncology-add-mixture:focus-visible,
  .doctor-oncology-add-date:focus-visible {
    outline: 3px solid rgba(5, 150, 105, .3);
    outline-offset: 2px;
  }
</style>

<script>
(() => {
  const root = document.querySelector('[data-oncology-mixture-builder]');
  if (!root) return;
  const catalog = @json($catalogItems);
  const infusors = @json($infusors);
  const saved = @json($savedMixtures);
  const container = root.querySelector('[data-mixtures-container]');
  const countInput = root.querySelector('[data-mixture-count]');
  const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
  const medicineOptions = selected => '<option value="">Seleccione el medicamento</option>' + catalog.map(item => {
    const value = `${item.product_code}|${item.presentation_code}`;
    return `<option value="${escapeHtml(value)}" ${value === selected ? 'selected' : ''}>${escapeHtml(item.generic_name)} — ${escapeHtml(item.presentation)}</option>`;
  }).join('');
  const infusorOptions = selected => '<option value="">Selecciona infusor</option>' + infusors.map(item => `<option value="${item.id}" ${String(item.id) === String(selected || '') ? 'selected' : ''}>${escapeHtml(item.generic_name || item.commercial_name)}</option>`).join('');
  const selectedItem = value => catalog.find(item => `${item.product_code}|${item.presentation_code}` === value);

  function medicationRow(mixtureIndex, rowIndex, data = {}) {
    return `<tr data-mixture-medication>
      <td><select name="oncology[mixtures][${mixtureIndex}][medications][${rowIndex}][catalog_item]" required data-mixture-medicine>${medicineOptions(data.catalog_item)}</select><input type="hidden" name="oncology[mixtures][${mixtureIndex}][medications][${rowIndex}][medication]" value="${escapeHtml(data.medication)}" data-mixture-medicine-name></td>
      <td><input type="number" min="0.01" step="0.01" name="oncology[mixtures][${mixtureIndex}][medications][${rowIndex}][dose]" value="${escapeHtml(data.dose)}" required></td>
      <td>MG</td>
      <td><select name="oncology[mixtures][${mixtureIndex}][medications][${rowIndex}][diluent_id]" required data-mixture-diluent data-selected="${escapeHtml(data.diluent_id)}"><option value="">Diluyentes</option></select></td>
      <td><select name="oncology[mixtures][${mixtureIndex}][medications][${rowIndex}][route_id]" required data-mixture-route data-selected="${escapeHtml(data.route_id)}"><option value="">V&iacute;a de admin</option></select></td>
      <td><button type="button" class="doctor-oncology-remove-row" data-remove-medication aria-label="Eliminar medicamento">Eliminar</button></td>
    </tr>`;
  }
  function dateRow(mixtureIndex, dateIndex, value = '') {
    return `<div class="doctor-oncology-date-row" data-delivery-date><input type="datetime-local" name="oncology[mixtures][${mixtureIndex}][delivery_dates][${dateIndex}]" value="${escapeHtml(String(value).slice(0,16))}" required><button type="button" class="doctor-oncology-remove-date" data-remove-date>&times;</button></div>`;
  }
  function mixtureCard(index, data = {}) {
    const medications = data.medications?.length ? data.medications : [{}];
    const dates = data.delivery_dates?.length ? data.delivery_dates : [''];
    return `<section class="doctor-oncology-mixture" data-mixture>
      <div class="doctor-oncology-mixture-head"><h3>Mezcla #${index + 1}</h3><button type="button" data-remove-mixture>Eliminar mezcla</button></div>
      <table><thead><tr><th>MEDICAMENTO</th><th>DOSIS</th><th>UNIDAD</th><th>DILUYENTE</th><th>V&Iacute;A DE ADMINISTRACI&Oacute;N</th><th>ACCI&Oacute;N</th></tr></thead><tbody data-medications>${medications.map((med,row) => medicationRow(index,row,med)).join('')}</tbody></table>
      <div class="doctor-oncology-mixture-actions"><button type="button" data-add-medication>+ Agregar medicamento</button></div>
      <div class="doctor-oncology-mixture-details"><label>Volumen total de diluci&oacute;n (ml)*<input type="number" min="0.01" step="0.01" name="oncology[mixtures][${index}][dilution_volume]" value="${escapeHtml(data.dilution_volume)}" required></label><label>Tiempo de infusi&oacute;n (min)*<input type="number" min="1" name="oncology[mixtures][${index}][infusion_minutes]" value="${escapeHtml(data.infusion_minutes)}" required></label></div>
      <div class="doctor-oncology-dates"><strong>Fecha de entrega*</strong><small> Agrega una fecha por cada entrega programada de esta mezcla.</small><div data-dates>${dates.map((date,i) => dateRow(index,i,date)).join('')}</div><button type="button" class="doctor-oncology-add-date" data-add-date>+ Agregar fecha</button></div>
      <div class="doctor-oncology-mixture-options"><label><input type="hidden" name="oncology[mixtures][${index}][set_infusion]" value="0"><input type="checkbox" name="oncology[mixtures][${index}][set_infusion]" value="1" ${data.set_infusion ? 'checked' : ''} data-mixture-set> Set de infusi&oacute;n</label><label>Infusor<select name="oncology[mixtures][${index}][infusor_id]" data-mixture-infusor>${infusorOptions(data.infusor_id)}</select><small>Solo aparece para medicamentos configurados para infusor.</small></label></div>
    </section>`;
  }
  function renumber() {
    [...container.querySelectorAll('[data-mixture]')].forEach((card, mixIndex) => {
      card.querySelector('h3').textContent = `Mezcla #${mixIndex + 1}`;
      card.querySelectorAll('[name]').forEach(field => field.name = field.name.replace(/oncology\[mixtures\]\[\d+\]/, `oncology[mixtures][${mixIndex}]`));
      card.querySelectorAll('[data-mixture-medication]').forEach((row,rowIndex) => row.querySelectorAll('[name]').forEach(field => field.name = field.name.replace(/\[medications\]\[\d+\]/, `[medications][${rowIndex}]`)));
      card.querySelectorAll('[data-delivery-date]').forEach((row,dateIndex) => row.querySelector('input').name = `oncology[mixtures][${mixIndex}][delivery_dates][${dateIndex}]`);
    });
    countInput.value = Math.max(1, container.querySelectorAll('[data-mixture]').length);
  }
  function refreshRow(row) {
    const select = row.querySelector('[data-mixture-medicine]'); const item = selectedItem(select.value);
    const fill = (target, options) => { const selected = target.dataset.selected || target.value; target.innerHTML = '<option value="">Seleccionar</option>' + (options || []).map(option => `<option value="${option.id}" ${String(option.id) === String(selected) ? 'selected' : ''}>${escapeHtml(option.name)}</option>`).join(''); target.dataset.selected = ''; };
    fill(row.querySelector('[data-mixture-diluent]'), item?.diluents); fill(row.querySelector('[data-mixture-route]'), item?.administration_routes);
    row.querySelector('[data-mixture-medicine-name]').value = item?.generic_name || '';
  }
  function bind() {
    container.querySelectorAll('[data-mixture-medication]').forEach(row => { const select = row.querySelector('[data-mixture-medicine]'); if (!select.dataset.bound) { select.dataset.bound='1'; select.addEventListener('change', () => refreshRow(row)); } refreshRow(row); });
  }
  function addMixture(data = {}) { container.insertAdjacentHTML('beforeend', mixtureCard(container.querySelectorAll('[data-mixture]').length, data)); renumber(); bind(); }
  root.addEventListener('click', event => {
    const card = event.target.closest('[data-mixture]');
    if (event.target.closest('[data-add-mixture]')) addMixture();
    if (event.target.closest('[data-remove-mixture]') && container.querySelectorAll('[data-mixture]').length > 1) { card.remove(); renumber(); }
    if (event.target.closest('[data-add-medication]')) { const body=card.querySelector('[data-medications]'); const mix=[...container.children].indexOf(card); body.insertAdjacentHTML('beforeend', medicationRow(mix,body.children.length)); renumber(); bind(); }
    if (event.target.closest('[data-remove-medication]') && card.querySelectorAll('[data-mixture-medication]').length > 1) { event.target.closest('[data-mixture-medication]').remove(); renumber(); }
    if (event.target.closest('[data-add-date]')) { const dates=card.querySelector('[data-dates]'); const mix=[...container.children].indexOf(card); dates.insertAdjacentHTML('beforeend',dateRow(mix,dates.children.length)); renumber(); }
    if (event.target.closest('[data-remove-date]') && card.querySelectorAll('[data-delivery-date]').length > 1) { event.target.closest('[data-delivery-date]').remove(); renumber(); }
  });
  root.querySelector('[data-mixture-count-up]').addEventListener('click', () => addMixture());
  root.querySelector('[data-mixture-count-down]').addEventListener('click', () => { const cards=container.querySelectorAll('[data-mixture]'); if(cards.length>1){cards[cards.length-1].remove();renumber();} });
  countInput.addEventListener('change', () => { const wanted=Math.min(99,Math.max(1,Number(countInput.value)||1)); while(container.children.length<wanted)addMixture(); while(container.children.length>wanted)container.lastElementChild.remove();renumber(); });
  root.addEventListener('change', event => { const card=event.target.closest('[data-mixture]'); if(!card)return; const set=card.querySelector('[data-mixture-set]'); const inf=card.querySelector('[data-mixture-infusor]'); if(event.target===set&&set.checked)inf.value=''; if(event.target===inf&&inf.value)set.checked=false; });
  (saved.length ? saved : [{}]).forEach(addMixture);
})();
</script>
