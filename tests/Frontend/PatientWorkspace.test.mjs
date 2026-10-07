import test from 'node:test';
import assert from 'node:assert/strict';

globalThis.HTMLElement = class {};
globalThis.customElements = {get:()=>true};
globalThis.window = {dispatchEvent:()=>{}};
globalThis.document = {querySelector:()=>null,getElementById:()=>null};
const {KliniPatientWorkspace} = await import('../../public/js/klini-patient-workspace.js');
const {seed} = await import('../../public/vendor/klini-patient/src/data.js');

function workspace() {
  return Object.assign(Object.create(KliniPatientWorkspace.prototype),{
    section:'calendar',date:'2026-10-06',room:'',specialty:'',mode:'day',
    state:{...structuredClone(seed),rooms:[{id:'Sede & Norte',label:'Sede & Norte'}],appointments:[]},
    render:()=>{},notify:()=>{},
  });
}
const appointment = (id,time) => ({id,date:'2026-10-06',time,duration:30,room:'Sede & Norte',specialty:'Medicina general',doctor:'Dra. Ejemplo',status:'scheduled'});

test('la agenda muestra horarios fuera del rango de la demo e intervalos no redondos',()=>{
  const w=workspace();w.state.appointments=[appointment('early','07:15'),appointment('late','19:45')];
  const html=w.schedule();
  assert.ok(html.includes('07:15'));assert.ok(html.includes('19:45'));
  assert.ok(html.includes('data-id="early"'));assert.ok(html.includes('data-id="late"'));
  assert.ok(!html.includes('data-action="slot"'));
});

test('detalles clínicos mantienen el estado programado y escapan contenido',()=>{
  const w=workspace(),a=appointment('one','10:00');
  a.specialty='<img src=x onerror=alert(1)>';
  w.state.appointments=[a];w.modal=(title,body)=>{w.detail=body;};
  assert.ok(w.appointment(a).includes('Programada'));
  w.appointmentDetails('one');
  assert.ok(w.detail.includes('&lt;img'));assert.ok(!w.detail.includes('<img src=x'));
  assert.ok(!w.detail.includes('data-form="status"'));
});

test('las citas del servidor nunca se guardan al interactuar con comunidades',()=>{
  const w=workspace();w.section='communities';w.storageKey='patient-a';
  w.state.appointments=[appointment('private','10:00')];
  let saved;globalThis.localStorage={setItem:(key,value)=>{saved=JSON.parse(value);assert.equal(key,'patient-a');}};
  assert.equal(w.mutate(state=>{state.posts[0].liked=true;}),true);
  assert.equal(saved.posts[0].liked,true);
  assert.equal(saved.appointments,undefined);assert.equal(saved.rooms,undefined);assert.equal(saved.user,undefined);
  w.section='calendar';assert.equal(w.mutate(()=>{throw Error('No debe ejecutar');}),false);
});

test('agendar usa la navegación existente de médicos, sin crear citas ficticias',()=>{
  const w=workspace();let event;w.dispatchEvent=value=>{event=value;};
  w.newAppointment();assert.equal(event.type,'klini:portal-navigate');assert.equal(event.detail.target,'doctors');
  assert.equal(w.state.appointments.length,0);
});

test('las cuatro vistas consultan los mismos datos y respetan filtros',()=>{
  const w=workspace();w.state.appointments=[appointment('one','10:00')];
  assert.equal(w.dayAppointments().length,1);
  w.mode='week';assert.ok(w.schedule().includes('schedule-table week'));
  assert.ok(w.monthView().includes('data-id="one"'));
  assert.ok(w.listView(w.dayAppointments()).includes('data-id="one"'));
  w.specialty='Otra';assert.equal(w.dayAppointments().length,0);
});
