import test from 'node:test';
import assert from 'node:assert/strict';
import {seed} from '../src/data.js';
globalThis.HTMLElement=class{};
globalThis.customElements={get:()=>true};
const {KliniWorkspace}=await import('../src/app.js');
function workspace(){return Object.assign(Object.create(KliniWorkspace.prototype),{state:structuredClone(seed),category:'mine',slides:{},mode:'day',date:'2026-07-27',miniMonth:'2026-07-01',room:'C-01',specialty:'',onlySaved:false,showExplore:false});}
test('Feed contiene categorías, publicaciones y acciones solicitadas',()=>{const w=workspace(),html=w.communities();for(const text of ['Mis comunidades','Comunidades Wellness','Comunidades Sport','Nuevas comunidades','Explorar comunidades','data-action="like"','data-action="comments"','data-action="share"','data-action="save"'])assert.ok(html.includes(text),text);});
test('Texto ingresado se escapa en contenido y atributos',()=>{const w=workspace();w.state.posts[0].caption='<img src=x onerror=alert(1)>';const html=w.post(w.state.posts[0]);assert.ok(!html.includes('<img src=x'));assert.ok(html.includes('&lt;img src=x'));});
test('Inputs de comentarios del feed y del diálogo tienen IDs diferentes',()=>{const w=workspace();assert.ok(w.commentForm('p').includes('id="comment-p"'));assert.ok(w.commentForm('p',true).includes('id="comment-dialog-p"'));});
test('Día, semana, mes y lista producen vistas diferentes con citas',()=>{const w=workspace();const day=w.schedule();assert.ok(day.includes('10:00'));assert.ok(day.includes('Paciente de demostración'));w.mode='week';assert.ok(w.schedule().includes('schedule-table week'));assert.ok(w.monthView().includes('month-cell'));assert.ok(w.listView(w.dayAppointments()).includes('list-view'));assert.ok(w.miniCalendar().includes('data-id="2026-07-27"'));});
