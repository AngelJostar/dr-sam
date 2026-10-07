import {KliniWorkspace} from '../vendor/klini-patient/src/app.js';
import {seed} from '../vendor/klini-patient/src/data.js';
import {icon} from '../vendor/klini-patient/src/icons.js';
import {dateKey,parseDate,shiftDate,monday,minutes,timeLabel,filteredAppointments} from '../vendor/klini-patient/src/model.js';

const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const statuses = {scheduled:'Programada',confirmed:'Confirmada',waiting:'En espera',in_progress:'En consulta',consulting:'En consulta',completed:'Finalizada',finished:'Finalizada',cancelled:'Cancelada',no_show:'No asistió'};
const statusClass = value => ({scheduled:'scheduled',confirmed:'confirmed',waiting:'waiting',in_progress:'consulting',consulting:'consulting',completed:'finished',finished:'finished',cancelled:'cancelled',no_show:'cancelled'}[value] || 'finished');
const statusLabel = value => statuses[value] || value?.replaceAll('_',' ') || 'Sin estatus';
const badge = value => `<span class="status ${statusClass(value)}"><i class="dot"></i>${escape(statusLabel(value))}</span>`;
const longDate = date => parseDate(date).toLocaleDateString('es-MX',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
const shortDate = date => parseDate(date).toLocaleDateString('es-MX',{weekday:'short',day:'numeric',month:'short'});
const portalNavigate = (element,target) => element.dispatchEvent(new CustomEvent('klini:portal-navigate',{detail:{target},bubbles:true,composed:true}));

// Persist the social demo only. Clinical appointments remain server-owned and in memory.
const socialKeys = ['communities','posts','stories','reports'];
function socialState(storageKey, patient) {
  const state = structuredClone(seed);
  state.appointments = [];
  state.rooms = [];
  state.specialties = [];
  state.stories.push({name:'Klini',avatar:'avatars/valeria.png',photo:'photos/caminata-runway.png'});
  state.communities.push({id:'caminemos',name:'Caminemos juntos',description:'Pequeños pasos, buenas conversaciones y nuevas conexiones.',category:'sport',joined:false,isNew:true,members:64,photo:'photos/caminata-runway.png'});
  try {
    const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
    if (saved?.version === 1 && socialKeys.slice(0,3).every(key => Array.isArray(saved[key]))) {
      for (const key of socialKeys) if (Array.isArray(saved[key])) state[key] = saved[key];
    }
  } catch { /* Storage may be unavailable; the demo remains usable for this visit. */ }
  state.user = {name:patient?.name || 'Mi perfil',avatar:patient?.photo || 'avatars/perfil.png'};
  return state;
}

export class KliniPatientWorkspace extends KliniWorkspace {
  connectedCallback() {
    super.connectedCallback();
    const stylesheet = document.createElement('link');
    stylesheet.rel = 'stylesheet';
    stylesheet.href = new URL('../css/klini-patient-component.css?v=20261006-compact',import.meta.url).href;
    this.shadowRoot.append(stylesheet);
  }

  mutate(fn) {
    if (this.section === 'calendar') return false;
    const next = structuredClone(this.state);
    fn(next);
    try {
      localStorage.setItem(this.storageKey,JSON.stringify({version:1,...Object.fromEntries(socialKeys.map(key => [key,next[key] || []]))}));
      this.state = next;
      this.render();
      return true;
    } catch {
      this.notify('No se pudo guardar la interacción. Revisa el espacio y los permisos del navegador.');
      return false;
    }
  }

  communities() {
    return super.communities()
      .replace(/<aside class="social-rail">[\s\S]*?<\/aside>/, '')
      .replace('<h1>Comunidades</h1>','<h1>Comunidades</h1><p class="community-intro">Tu gente. Tu ritmo. Un espacio para conectar.</p>')
      .replace('</header><div class="stories-shell">',`<button class="icon-btn" data-action="messages" aria-label="Abrir mensajes" title="Mensajes">${icon('message-circle')}</button></header><div class="stories-shell">`);
  }

  calendar() {
    const appointments = this.dayAppointments();
    const tabs = [['day','Día'],['week','Semana'],['month','Mes'],['list','Lista']];
    return `<section class="calendar-page"><header class="calendar-head row between"><div><p class="small">${icon('calendar')} MI CALENDARIO</p><h1>Tu agenda, en un vistazo.</h1><p class="muted">Un espacio para cuidar de ti. Consulta tus citas y planea tu próxima visita.</p></div><button class="primary" data-action="new-appointment">${icon('plus')} Agendar nueva cita</button></header>
      <div class="calendar-toolbar panel"><nav class="view-tabs" aria-label="Vistas del calendario">${tabs.map(([id,label]) => `<button data-action="view" data-id="${id}" class="${this.mode===id?'active':''}" aria-pressed="${this.mode===id}">${label}</button>`).join('')}</nav>
      <div class="date-nav"><button class="icon-btn" data-action="date-prev" aria-label="Periodo anterior">${icon('chevronleft')}</button><strong>${escape(longDate(this.date))}</strong><button class="icon-btn" data-action="date-next" aria-label="Periodo siguiente">${icon('chevron-right')}</button><button class="secondary small" data-action="today">Hoy</button></div>
      <div class="filters"><label class="field">Especialidad<select data-change="specialty"><option value="">Todas las especialidades</option>${this.state.specialties.map(s => `<option value="${escape(s)}" ${this.specialty===s?'selected':''}>${escape(s)}</option>`).join('')}</select></label><label class="field">Ubicación<select data-change="room"><option value="">Todas las ubicaciones</option>${this.state.rooms.map(r => `<option value="${escape(r.id)}" ${this.room===r.id?'selected':''}>${escape(r.label)}</option>`).join('')}</select></label></div></div>
      <p class="calendar-owner">${icon('shield')} Citas de ${escape(this.calendarData.patient)} <span>Zona horaria: ${escape(this.calendarData.timezone)}</span></p>
      <div class="calendar-body"><div class="schedule" tabindex="0" aria-label="Agenda, desplazamiento por horarios">${this.mode==='month'?this.monthView():this.mode==='list'?this.listView(appointments):this.schedule()}</div><aside class="calendar-rail stack"><div class="mini-calendar panel">${this.miniCalendar()}</div><div class="summary panel">${this.summary(appointments)}</div></aside></div></section>`;
  }

  schedule() {
    const rooms = this.state.rooms.filter(room => !this.room || this.room === room.id);
    const columns = this.mode === 'week'
      ? Array.from({length:7},(_,i) => {const date=shiftDate(monday(this.date),i);return {date,room:this.room,label:shortDate(date)};})
      : (rooms.length ? rooms : [{id:'',label:'Mis citas'}]).map(room => ({date:this.date,room:room.id,label:room.label}));
    const columnItems = columns.map(column => filteredAppointments(this.state.appointments,{date:column.date,room:column.room,specialty:this.specialty}));
    const visible = columnItems.flat();
    const start = Math.min(480,...visible.map(a => Math.floor(minutes(a.time)/30)*30));
    const end = Math.min(1440,Math.max(1080,...visible.map(a => Math.ceil((minutes(a.time)+a.duration)/30)*30)));
    return `<table class="schedule-table ${this.mode==='week'?'week':''}"><thead><tr><th scope="col">Hora</th>${columns.map(column=>`<th scope="col">${escape(column.label)}<span>${this.mode==='week'?'Mis citas':'Atención y bienestar'}</span></th>`).join('')}</tr></thead><tbody>${Array.from({length:(end-start)/30},(_,i)=>start+i*30).map(time => `<tr><td>${timeLabel(time)}</td>${columnItems.map(items => `<td>${items.filter(a => minutes(a.time)>=time && minutes(a.time)<time+30).map(a=>this.appointment(a)).join('')}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
  }

  appointment(appointment) {
    return `<button class="appointment ${statusClass(appointment.status)}" data-action="appointment" data-id="${escape(appointment.id)}" aria-label="${escape(`${appointment.specialty}, ${appointment.time}, ${statusLabel(appointment.status)}`)}"><span class="appointment-time">${escape(appointment.time)}</span><span class="appointment-copy"><strong>${escape(appointment.specialty)}</strong><small>${escape(appointment.doctor)}</small></span>${badge(appointment.status)}</button>`;
  }

  listView(items) {
    return `<div class="list-view">${items.length ? items.map(appointment => `${this.appointment(appointment)}<p class="list-location">${icon('map-pin')} ${escape(appointment.room)}</p>`).join('') : `<div class="empty">${icon('calendar')}<h2>Un espacio para ti</h2><p class="muted">No tienes citas para esta fecha y filtros.</p><button class="primary" data-action="new-appointment">Agendar nueva cita</button></div>`}</div>`;
  }

  summary(items) {
    const shownStatuses = [...new Set(['scheduled','confirmed','waiting','in_progress','completed','cancelled',...items.map(a=>a.status)])];
    return `<h2>Resumen del día</h2><p class="muted small">${escape(shortDate(this.date))}</p><div class="summary-row total"><span>Total de citas</span><strong>${items.length}</strong></div>${shownStatuses.map(status=>`<div class="summary-row">${badge(status)}<strong>${items.filter(a=>a.status===status).length}</strong></div>`).join('')}<button class="secondary" data-action="report">${icon('download')} Descargar agenda del día</button>`;
  }

  newAppointment() { portalNavigate(this,'doctors'); }

  appointmentDetails(id) {
    const appointment = this.state.appointments.find(item => item.id === id);
    if (!appointment) return;
    const details = [['Fecha',longDate(appointment.date)],['Hora',appointment.time],['Fin',appointment.endsAt || 'Por confirmar'],['Médico',appointment.doctor],['Ubicación',appointment.room],['Modalidad',appointment.modality],['Motivo',appointment.reason]];
    this.modal('Detalle de tu cita',`<div class="appointment-detail"><h3>${escape(appointment.specialty)}</h3>${badge(appointment.status)}<dl>${details.map(([label,value]) => `<div><dt>${label}</dt><dd>${escape(value)}</dd></div>`).join('')}</dl><button class="primary" data-action="close">Entendido</button></div>`);
  }

  async click(event) {
    const button = event.target.closest('[data-action]');
    if (!button || button.disabled) return;
    if (button.dataset.action === 'open-calendar') return portalNavigate(this,'calendar');
    if (button.dataset.action === 'messages') return portalNavigate(this,'messages');
    if (this.section === 'calendar' && button.dataset.action === 'today') {
      this.date = this.calendarData.today;
      this.miniMonth = this.date.slice(0,7)+'-01';
      return this.render();
    }
    if (this.section === 'calendar' && button.dataset.action === 'report') {
      const csvCell = value => '"'+String(value ?? '').replace(/^[=+@\-\t\r]/,"'$&").replaceAll('"','""')+'"';
      const rows = [['Fecha','Hora','Especialidad','Médico','Ubicación','Estado'],...this.dayAppointments().map(a=>[a.date,a.time,a.specialty,a.doctor,a.room,statusLabel(a.status)])];
      return this.download(`klini-mi-agenda-${this.date}.csv`,'\uFEFF'+rows.map(row=>row.map(csvCell).join(',')).join('\r\n'),'text/csv;charset=utf-8');
    }
    if (button.dataset.action === 'share') {
      const post = this.state.posts.find(item=>item.id===button.dataset.id);
      const url = new URL('/patient?view=communities',location.origin);
      url.hash = post.id;
      this.modal('Compartir publicación',`<label class="field">Enlace de esta demostración<input readonly id="share-link" value="${escape(url.href)}"></label><p class="small muted share-note">Las publicaciones de ejemplo requieren iniciar sesión. Las publicaciones que creas solo existen en este navegador.</p><button class="primary" data-action="copy" data-id="${escape(post.id)}">Copiar enlace</button>`);
      return;
    }
    return super.click(event);
  }

  async submit(event) {
    // The patient cannot modify a clinical appointment through the demo forms.
    if (this.section === 'calendar') { event.preventDefault(); return; }
    return super.submit(event);
  }
}

if (!customElements.get('klini-patient-workspace')) customElements.define('klini-patient-workspace',KliniPatientWorkspace);

window.KliniPatientCommunities = {
  mount(root,{patient,storageKey}) {
    let element;
    function render(profile,key) {
      element?.dialog?.close();
      element?.remove();
      element = document.createElement('klini-patient-workspace');
      element.storageKey = key;
      element.state = socialState(key,profile);
      element.setAttribute('section','communities');
      element.setAttribute('embedded','');
      element.setAttribute('hide-bottom-nav','');
      root.replaceChildren(element);
    }
    render(patient,storageKey);
    return {switchPatientProfile:render,destroy:()=>element.remove()};
  },
};

const calendarRoot = document.querySelector('[data-patient-calendar-root]');
const payload = document.getElementById('patient-calendar-data');
if (calendarRoot && payload) {
  const data = JSON.parse(payload.textContent);
  const element = document.createElement('klini-patient-workspace');
  element.calendarData = data;
  element.state = {...structuredClone(seed),appointments:data.appointments,rooms:data.rooms,specialties:data.specialties};
  element.date = data.date;
  element.miniMonth = data.date.slice(0,7)+'-01';
  element.room = '';
  element.setAttribute('section','calendar');
  element.setAttribute('embedded','');
  element.setAttribute('hide-bottom-nav','');
  calendarRoot.replaceChildren(element);
}
window.dispatchEvent(new Event('klini:workspace-ready'));
