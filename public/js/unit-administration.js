(() => {
  'use strict';
  const config = window.unitAdministrationConfig;
  const root = document.querySelector('[data-administration-workspace]');
  if (!config || !root) return;
  let state = config.state;
  const dialog = document.querySelector('[data-admin-dialog]');
  const content = root.querySelector('[data-admin-content]');
  const esc = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[character]));
  const money = cents => new Intl.NumberFormat('es-MX', {style:'currency', currency:'MXN'}).format((cents || 0) / 100);
  const date = value => value ? new Date(value.slice(0,10) + 'T12:00:00').toLocaleDateString('es-MX') : 'Por definir';
  const labels = {pending:'Pendiente', open:'Abierta', paid:'Pagada', active:'Activo', inactive:'Inactivo', draft:'Borrador', issued:'Emitida · Demo', review:'En revisión', documents:'Documentos pendientes', authorized:'Autorizada', rejected:'Rechazada', registered:'Registrado'};
  const badge = status => `<span class="admin-badge is-${esc(status)}">${esc(labels[status] || status)}</span>`;
  const button = (action, label, id = '', primary = false) => `<button type="button" data-admin-action="${esc(action)}" data-id="${esc(id)}"${primary ? ' class="is-primary"' : ''}>${esc(label)}</button>`;
  const record = (collection, id) => state[collection].find(item => item.id === id);
  const insurerName = id => record('insurers', id)?.name || 'Por definir';
  const coverage = account => ({particular:'Particular', agreement:'Convenio', insurer:insurerName(account.insurer_id)}[account.coverage]);
  const balance = id => {
    const charges = state.charges.filter(row => row.account_id === id).reduce((sum,row) => sum + row.amount, 0);
    const payments = state.payments.filter(row => row.account_id === id).reduce((sum,row) => sum + row.amount, 0);
    return {charges, payments, balance:charges-payments, status:charges > 0 && charges === payments ? 'paid' : payments > 0 ? 'open' : 'pending'};
  };
  const filters = {search:'', status:'all', coverage:config.insurerFilter || 'all', insurer:config.insurerFilter || 'all', type:'all', from:'', to:''};
  const quickFilters = {
    accounts: {key:'status', items:[['all','Todas'],['open','Abiertas'],['pending','Pendientes'],['paid','Pagadas']]},
    billing: {key:'type', items:[['all','Todos'],['payments','Pagos'],['invoices','Facturas']]},
    insurers: {key:'status', items:[['all','Todas'],['active','Activas'],['draft','Borradores'],['inactive','Inactivas']]},
    authorizations: {key:'status', items:[['all','Todas'],['review','En revisión'],['documents','Documentos pendientes'],['authorized','Autorizadas'],['rejected','Rechazadas']]},
  }[config.tab];
  let selectedAccount = state.accounts[0]?.id;
  let selectedInsurer = state.insurers[0]?.id;
  const matches = (...values) => values.join(' ').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es').includes(filters.search.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es'));
  const withinDates = row => (!filters.from || row.date >= filters.from) && (!filters.to || row.date <= filters.to);
  const table = (headings, rows, footer = '') => `<div class="admin-table-scroll"><table data-drsam-table-filter-skip><thead><tr>${headings.map(value=>`<th>${esc(value)}</th>`).join('')}</tr></thead><tbody>${rows.join('') || `<tr><td colspan="${headings.length}" class="admin-empty">No hay registros que coincidan con los filtros.</td></tr>`}</tbody>${footer}</table></div>`;
  const row = (cells, selected = false) => `<tr${selected ? ' class="is-selected"' : ''}>${cells.map(cell => `<td${cell?.money ? ' class="admin-money"' : ''}>${cell?.money ? esc(money(cell.value)) : cell}</td>`).join('')}</tr>`;
  const amount = value => ({money:true, value});
  const section = (title, body) => `<section class="admin-section">${title ? `<h3>${esc(title)}</h3>` : ''}${body}</section>`;
  const link = (tab, insurer) => `${config.baseUrl}&tab=${encodeURIComponent(tab)}&insurer=${encodeURIComponent(insurer || '')}`;
  const documentUrl = (id, format) => `${config.invoiceBase}/${encodeURIComponent(id)}/${format}?unit=${encodeURIComponent(config.unit)}`;
  const options = (items, selected) => items.map(([value,label]) => `<option value="${esc(value)}"${String(selected) === String(value) ? ' selected' : ''}>${esc(label)}</option>`).join('');
  const accountOptions = () => state.accounts.map(account => [account.id, `${account.id} · ${account.patient} · Saldo ${money(balance(account.id).balance)}`]);
  const insurerOptions = () => state.insurers.map(insurer=>[insurer.id,insurer.name]);
  const input = (label,name,value = '',type = 'text',extra = '') => `<label>${esc(label)}<input name="${esc(name)}" type="${type}" value="${esc(value)}" ${extra}></label>`;
  const select = (label,name,items,value = '',extra = '') => `<label>${esc(label)}<select name="${esc(name)}" ${extra}>${options(items,value)}</select></label>`;
  const notes = value => `<label class="admin-wide">Observaciones<textarea name="notes" rows="3" maxlength="2000">${esc(value || '')}</textarea></label>`;
  const hidden = (name,value) => `<input type="hidden" name="${esc(name)}" value="${esc(value)}">`;
  const amountInput = value => input('Importe (MXN)','amount',value ?? '', 'number','required min="0.01" max="99999999.99" step="0.01"');
  const details = pairs => `<dl>${pairs.map(([key,value])=>`<dt>${esc(key)}</dt><dd>${esc(value)}</dd>`).join('')}</dl>`;
  const documents = (kind,id) => {
    const items = state.documents.filter(document=>document.kind === kind && document.record_id === id);
    return `<h3>Documentos</h3>${items.length ? `<ul>${items.map(document=>`<li><a href="${esc(document.url)}">${esc(document.name)}</a> · ${esc(date(document.date))}</li>`).join('')}</ul>` : '<p class="admin-help">Sin documentos adjuntos.</p>'}`;
  };

  function renderFilters() {
    root.querySelector('[data-admin-filter-buttons]').innerHTML = quickFilters.items.map(([value,label]) => `<button type="button" data-admin-quick-filter="${value}" aria-pressed="${filters[quickFilters.key] === value}" class="${filters[quickFilters.key] === value ? 'is-active' : ''}">${esc(label)}</button>`).join('');
    const search = `<label class="admin-search"><span class="sr-only">Buscar</span><input type="search" data-admin-filter="search" placeholder="${config.tab === 'insurers' ? 'Buscar aseguradora o convenio' : config.tab === 'authorizations' ? 'Buscar folio, cuenta o aseguradora' : config.tab === 'billing' ? 'Buscar cuenta, recibo o factura' : 'Buscar cuenta, paciente o episodio'}"></label>`;
    let html = search;
    if (config.tab === 'accounts') html += `<label><span class="sr-only">Cobertura</span><select data-admin-filter="coverage">${options([['all','Todas las coberturas'],['particular','Particular'],['agreement','Convenio'],...insurerOptions()],filters.coverage)}</select></label>`;
    if (config.tab === 'authorizations') html += `<label><span class="sr-only">Aseguradora</span><select data-admin-filter="insurer">${options([['all','Todas las aseguradoras'],...insurerOptions()],filters.insurer)}</select></label>`;
    if (config.tab === 'billing') html += `<label class="admin-date"><span>Desde</span><input type="date" data-admin-filter="from" title="Desde"></label><label class="admin-date"><span>Hasta</span><input type="date" data-admin-filter="to" title="Hasta"></label>`;
    root.querySelector('[data-admin-filters]').innerHTML = html;
    Object.entries(filters).forEach(([key,value])=>{ const element = root.querySelector(`[data-admin-filter="${key}"]`); if(element) element.value=value; });
  }

  function render() {
    if (config.tab === 'accounts') {
      const accounts = state.accounts.filter(account => matches(account.id,account.patient,account.episode,coverage(account)) && (filters.coverage === 'all' || account.coverage === filters.coverage || account.insurer_id === filters.coverage) && (filters.status === 'all' || balance(account.id).status === filters.status));
      if (!accounts.some(account=>account.id === selectedAccount)) selectedAccount = accounts[0]?.id;
      const sums = accounts.reduce((totals,account)=>{ const sums=balance(account.id); for(const key of ['charges','payments','balance']) totals[key]+=sums[key]; return totals; },{charges:0,payments:0,balance:0});
      const rows = accounts.map(account=>{ const totals=balance(account.id); return row([esc(account.id),esc(account.patient)+' / '+esc(account.episode),esc(coverage(account)),amount(totals.charges),amount(totals.payments),amount(totals.balance),badge(totals.status),button('account-view','Ver cuenta',account.id)],account.id === selectedAccount); });
      content.innerHTML = section('',table(['Cuenta','Paciente / episodio','Cobertura','Cargos','Pagos','Saldo','Estado','Acción'],rows,`<tfoot>${row(['Total mostrado','','',amount(sums.charges),amount(sums.payments),amount(sums.balance),'',''])}</tfoot>`));
      const selected = record('accounts',selectedAccount);
      if (selected) content.innerHTML += section('Acciones de la cuenta seleccionada',table(['Cuenta','Paciente','Acciones'],[row([esc(selected.id),esc(selected.patient),`<div class="admin-actions">${button('charges','Ver cargos',selected.id)}${button('payment','Registrar pago',selected.id)}</div>`])]));
    }
    if (config.tab === 'billing') {
      const payments = state.payments.filter(payment=>withinDates(payment) && matches(payment.id,payment.account_id,record('accounts',payment.account_id)?.patient));
      const invoices = state.accounts.map(account=>({account, invoice:state.invoices.find(invoice=>invoice.account_id === account.id)})).filter(({account,invoice})=>matches(account.id,account.patient,invoice?.id,invoice?.receiver) && (!invoice ? !filters.from && !filters.to : withinDates(invoice)));
      content.innerHTML = filters.type === 'invoices' ? '' : section('Pagos registrados',table(['Recibo','Cuenta','Paciente','Fecha','Medio de pago','Importe','Estado','Acción'],payments.map(payment=>row([esc(payment.id),esc(payment.account_id),esc(record('accounts',payment.account_id)?.patient),esc(date(payment.date)),esc(payment.method),amount(payment.amount),badge('registered'),button('receipt','Ver recibo',payment.id)]))));
      if (filters.type !== 'payments') content.innerHTML += section('Facturación',table(['Folio','Cuenta','Receptor','Importe','Estado','Acción'],invoices.map(({account,invoice})=>row([esc(invoice?.id || '—'),esc(account.id),esc(invoice?.receiver || (account.insurer_id ? insurerName(account.insurer_id) : account.patient)),amount(invoice?.amount ?? balance(account.id).charges),invoice ? badge(invoice.status) : '<span class="admin-badge">Por preparar</span>',invoice?.status === 'issued' ? `<div class="admin-actions"><a class="admin-button" target="_blank" rel="noopener" href="${esc(documentUrl(invoice.id,'print'))}">PDF</a><a class="admin-button" href="${esc(documentUrl(invoice.id,'xml'))}">XML</a></div>` : button('invoice',invoice ? 'Completar' : 'Preparar',account.id)]))));
    }
    if (config.tab === 'insurers') {
      const insurers = state.insurers.filter(insurer=>matches(insurer.name,insurer.agreement,insurer.contact) && (filters.status === 'all' || insurer.status === filters.status));
      if (!insurers.some(insurer=>insurer.id === selectedInsurer)) selectedInsurer=insurers[0]?.id;
      content.innerHTML = section('',table(['Aseguradora','Convenio','Contacto','Alcance del convenio','Vigencia','Estado','Acción'],insurers.map(insurer=>row([esc(insurer.name),esc(insurer.agreement || 'Por registrar'),esc(insurer.contact || 'Sin registrar'),esc(insurer.scope || 'Por definir'),esc(date(insurer.valid_until)),badge(insurer.status),button(insurer.status === 'draft' ? 'insurer-edit' : 'insurer-view',insurer.status === 'draft' ? 'Completar' : 'Ver convenio',insurer.id)],insurer.id === selectedInsurer))));
      const selected = record('insurers',selectedInsurer);
      if (selected) content.innerHTML += section('Gestión de la aseguradora seleccionada',table(['Aseguradora','Gestión','Acción'],[
        row([esc(selected.name),'Condiciones y documentos',button('insurer-view','Ver convenio',selected.id)]),
        row([esc(selected.name),'Solicitudes de cobertura',`<a class="admin-button" href="${esc(link('authorizations',selected.id))}">Ver autorizaciones</a>`]),
        row([esc(selected.name),'Cuentas vinculadas',`<a class="admin-button" href="${esc(link('accounts',selected.id))}">Ver cuentas</a>`]),
      ]));
    }
    if (config.tab === 'authorizations') {
      const authorizations = state.authorizations.filter(item=>matches(item.id,item.account_id,record('accounts',item.account_id)?.patient,insurerName(item.insurer_id)) && (filters.insurer === 'all' || filters.insurer === item.insurer_id) && (filters.status === 'all' || filters.status === item.status));
      content.innerHTML = section('',table(['Folio','Cuenta / paciente','Aseguradora','Servicio solicitado','Importe solicitado','Importe autorizado','Estado','Acción'],authorizations.map(item=>row([esc(item.id),esc(item.account_id)+' / '+esc(record('accounts',item.account_id)?.patient),esc(insurerName(item.insurer_id)),esc(item.service),amount(item.requested),item.authorized === null ? '—' : amount(item.authorized),badge(item.status),button(item.status === 'review' ? 'response' : 'authorization-view',item.status === 'review' ? 'Registrar respuesta' : item.status === 'documents' ? 'Completar' : 'Ver detalle',item.id)]))));
      content.innerHTML += section('Pendientes de seguimiento',table(['Folio','Pendiente','Responsable','Acción'],authorizations.filter(item=>['review','documents'].includes(item.status)).map(item=>row([esc(item.id),esc(item.status === 'documents' ? 'Adjuntar documentación solicitada' : 'Respuesta de la aseguradora'),esc(item.responsible),button(item.status === 'documents' ? 'attach-authorization' : 'followup',item.status === 'documents' ? 'Adjuntar' : 'Ver seguimiento',item.id)]))));
    }
  }

  function openDialog(title, html) {
    dialog.querySelector('h2').textContent = title;
    dialog.querySelector('[data-admin-dialog-content]').innerHTML = html;
    if (!dialog.open) dialog.showModal();
    dialog.scrollTop=0;
  }
  function openForm(action,title,fields) {
    openDialog(title,`<form data-admin-form="${action}"><div class="admin-errors" role="alert" hidden></div><div class="admin-form-grid">${fields}</div><footer><button type="button" data-admin-close>Cancelar</button><button type="submit" class="is-primary">Guardar y cerrar</button></footer></form>`);
    dialog.querySelector('form').dataset.operationId=crypto.randomUUID();
  }
  function act(action,id) {
    if (action === 'export') return exportCsv();
    if (action === 'account') return openForm('account','Nueva cuenta hospitalaria',input('Paciente de demostración','patient','','text','required maxlength="180"')+input('Episodio','episode','','text','required maxlength="80"')+select('Cobertura','coverage',[['particular','Particular'],['agreement','Convenio'],['insurer','Aseguradora']],'particular')+select('Aseguradora (si aplica)','insurer_id',[['','Selecciona una aseguradora'],...insurerOptions()])+amountInput()+input('Concepto del cargo inicial','description','','text','required maxlength="250"'));
    if (action === 'account-view') {
      selectedAccount=id; render(); const account=record('accounts',id), totals=balance(id);
      return openDialog('Cuenta '+id,details([['Paciente',account.patient],['Episodio',account.episode],['Cobertura',coverage(account)],['Cargos',money(totals.charges)],['Pagos',money(totals.payments)],['Saldo',money(totals.balance)]])+`<div class="admin-actions">${button('charges','Ver cargos',id)}${button('payment','Registrar pago',id)}${button('invoice','Preparar factura',id)}</div>`);
    }
    if (action === 'charges') return openDialog('Cargos de '+id,table(['Fecha','Concepto','Importe'],state.charges.filter(charge=>charge.account_id === id).map(charge=>row([esc(date(charge.date)),esc(charge.description),amount(charge.amount)])))+`<footer>${button('charge','+ Agregar cargo',id,true)}</footer>`);
    if (action === 'charge') return openForm('charge','Agregar cargo',hidden('account_id',id)+input('Concepto','description','','text','required maxlength="250"')+amountInput()+input('Fecha','date',config.today,'date',`required max="${config.today}"`));
    if (action === 'payment') return openForm('payment','Registrar pago · Demo',select('Cuenta','account_id',accountOptions(),id || selectedAccount,'required')+amountInput()+input('Fecha','date',config.today,'date',`required max="${config.today}"`)+select('Medio de pago','method',[['Transferencia','Transferencia'],['Tarjeta','Tarjeta'],['Efectivo','Efectivo']],'Transferencia')+input('Referencia','reference','','text','maxlength="150"')+'<p class="admin-help">Este registro actualiza el saldo de la cuenta de demostración.</p>');
    if (action === 'receipt') { const payment=record('payments',id), account=record('accounts',payment.account_id); return openDialog('Recibo '+id,details([['Cuenta',account.id],['Paciente',account.patient],['Fecha',date(payment.date)],['Medio de pago',payment.method],['Referencia',payment.reference || 'Sin referencia'],['Importe',money(payment.amount)]])+'<p class="admin-help">Recibo de demostración.</p>'); }
    if (action === 'invoice') {
      const account=record('accounts',id) || state.accounts.find(account=>!state.invoices.some(invoice=>invoice.account_id === account.id)) || state.accounts[0];
      if (!account) return openDialog('Preparar factura','<p>Crea primero una cuenta hospitalaria.</p>');
      const invoice=state.invoices.find(invoice=>invoice.account_id === account.id);
      if (invoice?.status === 'issued') return openDialog('Factura '+invoice.id,details([['Cuenta',account.id],['Receptor',invoice.receiver],['Importe',money(invoice.amount)]])+`<div class="admin-actions"><a class="admin-button" target="_blank" rel="noopener" href="${esc(documentUrl(invoice.id,'print'))}">PDF / Imprimir</a><a class="admin-button" href="${esc(documentUrl(invoice.id,'xml'))}">XML</a></div>`);
      return openForm('invoice',invoice ? 'Completar factura' : 'Preparar factura',hidden('id',invoice?.id || '')+select('Cuenta','account_id',invoice ? [[account.id,account.id+' · '+account.patient]] : accountOptions().filter(([key])=>!state.invoices.some(invoice=>invoice.account_id === key)),account.id,'required')+input('Receptor','receiver',invoice?.receiver || (account.insurer_id ? insurerName(account.insurer_id) : account.patient),'text','required maxlength="180"')+input('RFC de demostración','tax_id',invoice?.tax_id || '', 'text','maxlength="13"')+input('Código postal','postal_code',invoice?.postal_code || '', 'text','inputmode="numeric" pattern="[0-9]{5}" maxlength="5"')+input('Uso','use',invoice?.use || '', 'text','maxlength="80"')+select('Estado','status',[['draft','Borrador'],['issued','Emitir documento demo']],invoice?.status || 'draft')+'<p class="admin-help admin-wide">Documento de demostración sin validez fiscal. El PDF se abre en una vista de impresión.</p>');
    }
    if (action === 'insurer-edit') { const insurer=record('insurers',id) || {}; return openForm('insurer',id ? 'Editar aseguradora y convenio' : 'Agregar aseguradora',hidden('id',id || '')+input('Aseguradora','name',insurer.name,'text','required maxlength="180"')+input('Convenio','agreement',insurer.agreement,'text','maxlength="100"')+input('Correo de contacto','contact',insurer.contact,'email','maxlength="180"')+input('Vigencia','valid_until',insurer.valid_until,'date')+input('Alcance del convenio','scope',insurer.scope,'text','maxlength="500"')+select('Estado','status',[['draft','Borrador'],['active','Activo'],['inactive','Inactivo']],insurer.status || 'draft')+notes(insurer.notes)); }
    if (action === 'insurer-view') { selectedInsurer=id; render(); const insurer=record('insurers',id); return openDialog(insurer.name,details([['Convenio',insurer.agreement || 'Por registrar'],['Contacto',insurer.contact || 'Sin registrar'],['Alcance',insurer.scope || 'Por definir'],['Vigencia',date(insurer.valid_until)],['Estado',labels[insurer.status]],['Observaciones',insurer.notes || 'Sin observaciones']])+documents('insurers',id)+`<footer>${button('attach-insurer','Adjuntar documento',id)}${button('insurer-edit','Editar convenio',id,true)}</footer>`); }
    if (action === 'authorization') return openForm('authorization','Solicitar autorización · Demo',select('Cuenta','account_id',accountOptions().filter(([key])=>record('accounts',key).coverage === 'insurer'),'','required')+select('Aseguradora','insurer_id',insurerOptions(),'','required')+input('Servicio solicitado','service','','text','required maxlength="250"')+amountInput()+input('Responsable de seguimiento','responsible','Gestión de aseguradoras','text','required maxlength="180"')+notes());
    if (action === 'authorization-view' || action === 'followup') {
      const authorization=record('authorizations',id), account=record('accounts',authorization.account_id);
      return openDialog('Autorización '+id,details([['Cuenta / paciente',account.id+' / '+account.patient],['Aseguradora',insurerName(authorization.insurer_id)],['Servicio',authorization.service],['Solicitado',money(authorization.requested)],['Autorizado',authorization.authorized === null ? 'Pendiente' : money(authorization.authorized)],['Estado',labels[authorization.status]],['Responsable',authorization.responsible]])+'<h3>Seguimiento</h3><ul>'+authorization.history.map(entry=>`<li>${esc(entry.date)} · ${esc(entry.text)}</li>`).join('')+'</ul>'+documents('authorizations',id)+`<div class="admin-actions">${button('response','Registrar respuesta',id)}${button('followup-note','Agregar seguimiento',id)}${button('attach-authorization','Adjuntar documento',id)}</div>`);
    }
    if (action === 'response') { const authorization=record('authorizations',id); return openForm('response','Registrar respuesta · '+id,hidden('id',id)+select('Estado','status',[['review','En revisión'],['documents','Documentos pendientes'],['authorized','Autorizada'],['rejected','Rechazada']],authorization.status)+input('Importe autorizado (MXN)','amount',authorization.authorized === null ? '' : authorization.authorized/100,'number',`min="0" step="0.01" max="${authorization.requested/100}"`)+notes(authorization.notes)); }
    if (action === 'followup-note') return openForm('followup','Agregar seguimiento · '+id,hidden('id',id)+notes());
    if (action === 'attach-insurer' || action === 'attach-authorization') return openForm('document','Adjuntar documento',hidden('kind',action === 'attach-insurer' ? 'insurers' : 'authorizations')+hidden('id',id)+input('Nombre del documento','name','','text','required maxlength="180"')+'<label>Archivo (PDF, JPG o PNG; máximo 10 MB)<input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required></label>');
  }

  function exportCsv() {
    const rows=[['DEMOSTRACIÓN',config.unitName]];
    content.querySelectorAll('table').forEach(table=>{
      table.querySelectorAll('tr').forEach(row=>rows.push([...row.cells].slice(0,-1).map(cell=>cell.innerText.trim())));
      rows.push([]);
    });
    const csv=rows.map(row=>row.map(value=>`"${String(value).replace(/^[=+@-]/,"'$&").replaceAll('"','""')}"`).join(',')).join('\n');
    const url=URL.createObjectURL(new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8'}));
    const anchor=document.createElement('a'); anchor.href=url; anchor.download=`administracion-${config.tab}-demo.csv`; anchor.click(); URL.revokeObjectURL(url);
  }
  const actionHandler = event => { const control=event.target.closest('[data-admin-action]'); if(control) act(control.dataset.adminAction,control.dataset.id); if(event.target.closest('[data-admin-close]')) dialog.close(); };
  root.addEventListener('click',actionHandler); dialog.addEventListener('click',actionHandler);
  root.querySelector('[data-admin-filters]').addEventListener('input',event=>{ if(event.target.dataset.adminFilter) { filters[event.target.dataset.adminFilter]=event.target.value; render(); } });
  root.querySelector('[data-admin-filter-buttons]').addEventListener('click',event=>{
    const control=event.target.closest('[data-admin-quick-filter]');
    if (!control) return;
    filters[quickFilters.key]=control.dataset.adminQuickFilter;
    root.querySelectorAll('[data-admin-quick-filter]').forEach(button=>{
      const active=button.dataset.adminQuickFilter === filters[quickFilters.key];
      button.classList.toggle('is-active',active);
      button.setAttribute('aria-pressed',String(active));
    });
    render();
  });
  dialog.addEventListener('submit',async event=>{
    const form=event.target.closest('[data-admin-form]'); if(!form) return; event.preventDefault();
    const submit=form.querySelector('[type="submit"]'), errors=form.querySelector('.admin-errors'); submit.disabled=true; errors.hidden=true;
    const data=new FormData(form); data.set('action',form.dataset.adminForm); data.set('version',state.version); data.set('operation_id',form.dataset.operationId); data.set('_token',config.csrf);
    try {
      const response=await fetch(config.storeUrl,{method:'POST',body:data,headers:{Accept:'application/json'},credentials:'same-origin'});
      const payload=await response.json().catch(()=>({message:'No se pudo procesar la respuesta. Vuelve a intentar.'}));
      if(!response.ok) {
        if(response.status === 409) { const refreshed=await fetch(config.stateUrl,{headers:{Accept:'application/json'},credentials:'same-origin'}); if(refreshed.ok) { state=await refreshed.json(); render(); } }
        throw new Error(payload.errors ? Object.values(payload.errors).flat().join(' ') : payload.message || 'No se pudo guardar.');
      }
      state=payload.state; renderFilters(); render();
      openDialog('Guardado correctamente','<p>Los cambios se guardaron en la demostración de esta unidad.</p><footer><button type="button" data-admin-close>Cerrar</button></footer>');
    } catch(error) { errors.textContent=error.message; errors.hidden=false; errors.scrollIntoView({block:'nearest'}); }
    finally { submit.disabled=false; }
  });
  const descriptions={accounts:'Cargos, pagos y saldos por episodio de atención.',billing:'Seguimiento de pagos, recibos y documentos de facturación.',insurers:'Relaciones de cobertura y atención de la unidad.',authorizations:'Solicitudes de cobertura y respuestas recibidas.'};
  const titles={accounts:'Cuentas hospitalarias',billing:'Cobros y facturas',insurers:'Aseguradoras y convenios',authorizations:'Autorizaciones de aseguradoras'};
  root.querySelector('h2').textContent=titles[config.tab]; root.querySelector('[data-admin-description]').textContent=descriptions[config.tab];
  root.querySelector('[data-admin-primary-actions]').innerHTML=config.tab === 'accounts' ? button('export','Exportar')+button('account','+ Nueva cuenta','',true) : config.tab === 'billing' ? button('invoice','Preparar factura')+button('payment','+ Registrar pago','',true) : config.tab === 'insurers' ? button('export','Exportar')+button('insurer-edit','+ Agregar aseguradora','',true) : button('export','Exportar')+button('authorization','+ Solicitar autorización','',true);
  renderFilters(); render();
})();
