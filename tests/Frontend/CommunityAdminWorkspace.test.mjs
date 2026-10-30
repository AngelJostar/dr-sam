import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

// Exercise the existing renderer without adding test-only globals to the application.
const source = readFileSync(new URL('../../public/vendor/klini-community/wellness.js', import.meta.url), 'utf8')
  .replace('window.renderPatientWellness = renderPatientWellness;', 'window.adminTest = { wellnessUi, filteredAdminEvents, filteredAdminActivities, renderAdminModuleWorkspace, renderAdminEventRows, adminInviteProfiles, addAdminProfileInvitation, renderAdminInviteResults, renderEventCreatedModal }; window.renderPatientWellness = renderPatientWellness;');
function mount(activities = [], profiles = []) {
  const values = new Map();
  const window = { addEventListener() {}, KliniCommunityActivities:{adminItems:activities,adminLoaded:true,date:item=>item.starts_at_utc,cost:item=>item} };
  window.KliniCommunities = { profiles: () => profiles };
  vm.runInNewContext(source, {
    window,
    document: { body: { dataset: { communityMode: 'admin', communityStorage: 'test', communityName: 'Admin' } }, addEventListener() {} },
    localStorage: { getItem: key => values.get(key) || null, setItem: (key, value) => values.set(key, value) },
    Date, setTimeout, clearTimeout,
  });
  return {...window.adminTest,publications:window.KliniWellnessCommunity,storage:values};
}
const now = new Date('2030-10-23T12:00:00Z');
test('success confirmation reflects only the saved server reward and escapes the event title', () => {
  const {wellnessUi,renderEventCreatedModal} = mount();
  wellnessUi.eventCreated = {eventId:'event-saved',title:'<script>bad()</script>',reward:{tokens:1,balance:8}};
  const html = renderEventCreatedModal();
  assert.match(html,/\+1 Token/);
  assert.match(html,/Saldo actual: 8 Tokens/);
  assert.match(html,/data-wellness-event-open="event-saved"/);
  assert.match(html,/&lt;script&gt;/);
  assert.doesNotMatch(html,/<script>/);
  wellnessUi.eventCreated.reward = null;
  assert.doesNotMatch(renderEventCreatedModal(),/\+1 Token/);
});
const event = { id: 'event-1', title: 'Respiración y movilidad', category: 'Yoga', locationName: 'Casa Klini', startAt: '2030-10-24T09:00:00Z', endAt: '2030-10-24T10:00:00Z', status: 'published', modality: 'in_person', cost: 0, capacity: 20 };

test('event tabs separate upcoming, past, cancelled and draft events including the time boundary', () => {
  const { filteredAdminEvents } = mount();
  const items = [event, { ...event, id: 'past', endAt: '2030-10-22T10:00:00Z' }, { ...event, id: 'boundary', endAt: now.toISOString() }, { ...event, id: 'cancelled', status: 'cancelled' }, { ...event, id: 'draft', status: 'draft' }];
  for (const [filter, expected] of [['Proximos', ['event-1', 'boundary']], ['Finalizados', ['past']], ['Cancelados', ['cancelled']], ['Borradores', ['draft']]]) {
    assert.deepEqual(Array.from(filteredAdminEvents(items, { adminEventFilter: filter }, now), item => item.id), expected);
  }
});

test('profile search matches names and usernames without accents, case or word-order restrictions', () => {
  const profiles = [{id:'ana',name:'Ana Sofía Ruiz'},{id:'diego',name:'Diego Ramírez'}];
  const {adminInviteProfiles} = mount([], profiles);
  for (const query of [' SOFIA ANA ', '@ana.sofia', 'Ruíz']) {
    assert.deepEqual(Array.from(adminInviteProfiles({}, 'community-a', query), item => item.id), ['ana']);
  }
  assert.equal(adminInviteProfiles({}, 'community-a', '').length, 2);
  assert.equal(adminInviteProfiles({}, 'community-a', 'nadie').length, 0);
  assert.equal(profiles[0].username, undefined);
});

test('demo invitations are scoped to the managed community and reject duplicates and unknown profiles', () => {
  const {addAdminProfileInvitation,adminInviteProfiles} = mount([], [{id:'ana',name:'Ana Sofía Ruiz'}]);
  const state = {communityMembers:['a','b'].map(id => ({communityId:id,userId:'community-admin-jimmy',role:'owner',status:'active'}))};
  assert.equal(addAdminProfileInvitation(state,'a','unknown'), false);
  assert.equal(addAdminProfileInvitation(state,'unmanaged','ana'), false);
  assert.equal(addAdminProfileInvitation(state,'a','ana'), true);
  assert.equal(addAdminProfileInvitation(state,'a','ana'), false);
  assert.equal(adminInviteProfiles(state,'a')[0].invited, true);
  assert.equal(adminInviteProfiles(state,'b')[0].invited, false);
  assert.equal(addAdminProfileInvitation(state,'b','ana'), true);
  assert.equal(state.communityProfileInvitations.length, 2);
});

test('profile search escapes result content and displays an empty state', () => {
  const {wellnessUi,renderAdminInviteResults} = mount([], [{id:'x',name:'<script>alert(1)</script>'}]);
  const html = renderAdminInviteResults({}, 'test');
  assert.match(html, /&lt;script&gt;/);
  assert.doesNotMatch(html, /<script>/);
  wellnessUi.adminInviteQuery = 'sin coincidencias';
  assert.match(renderAdminInviteResults({}, 'test'), /No encontramos perfiles/);
});

test('search ignores accents and combines with modality and cost without changing events', () => {
  const { filteredAdminEvents } = mount();
  const paid = Object.freeze({ ...event, id: 'paid', modality: 'remote', cost: '125.00' });
  const filters = { adminEventFilter: 'Proximos', adminEventQuery: '  RESPIRACION ', adminEventModality: 'remote', adminEventCost: 'paid' };
  assert.deepEqual(Array.from(filteredAdminEvents([event, paid], filters, now), item => item.id), ['paid']);
  assert.equal(filteredAdminEvents([event, paid], { ...filters, adminEventCost: 'free' }, now).length, 0);
  assert.equal(filteredAdminEvents([event], { adminEventQuery: 'casa klini' }, now).length, 1);
  assert.equal(paid.cost, '125.00');
});

test('the redesigned table retains reservation counts and every event action while escaping user content', () => {
  const { renderAdminEventRows } = mount();
  const html = renderAdminEventRows({ eventReservations: [{ eventId: event.id, status: 'confirmed' }, { eventId: event.id, status: 'waitlisted' }] }, [{ ...event, title: '<img src=x onerror=alert(1)>' }]);
  assert.match(html, /1 \/ 20/);
  assert.match(html, /1 en espera/);
  assert.match(html, /&lt;img src=x onerror=alert\(1\)&gt;/);
  assert.doesNotMatch(html, /<img/);
  for (const action of ['duplicate-event', 'cancel-event', 'delete-event', 'admin-event-attendees', 'send-event-reminder', 'close-reservations']) assert.ok(html.includes(`data-wellness-community-action="${action}"`));
  assert.match(html, /data-wellness-event-open="event-1"/);
});

test('each capacity button shows only the linked server reservations and follows cancellations', () => {
  const activity = {id:42,reservations:3,capacity:20};
  const {renderAdminEventRows} = mount([activity,{id:99,reservations:15}]);
  const state = {eventReservations:[{eventId:event.id,status:'confirmed'}]};
  const linked = {...event,publicActivityId:42};
  const html = renderAdminEventRows(state,[linked]);
  assert.match(html,/3 \/ 20/);
  assert.match(html,/aria-haspopup="dialog"/);
  assert.match(html,/data-admin-event-seats="event-1"/);
  assert.match(html,/Ver personas inscritas en Respiración y movilidad/);
  activity.reservations=2;
  assert.match(renderAdminEventRows(state,[linked]),/2 \/ 20/);
  assert.match(renderAdminEventRows(state,[{...linked,publicActivityId:100}]),/… \/ 20/);
});

test('each content menu owns one action row and routes its create button to the corresponding flow', () => {
  const { wellnessUi, renderAdminModuleWorkspace } = mount();
  const ctx = { events: [], requests: [], posts: [], upcomingEvents: [] };
  for (const [menu, kind] of [['classes', 'class'], ['events', null], ['workshops', 'workshop'], ['podcasts', 'podcast'], ['live', 'live'], ['posts', null], ['calendar', null]]) {
    wellnessUi.adminModule = menu;
    const html = renderAdminModuleWorkspace({}, { fullName: 'Admin', initials: 'A', role: 'Administrador' }, { id: 'community-test', name: 'Comunidad' }, ctx);
    assert.equal((html.match(/wellness-admin-module-toolbar/g) || []).length, 1);
    assert.equal((html.match(/data-wellness-admin-focus-post/g) || []).length, 1);
    assert.equal((html.match(/data-wellness-community-action="creator-profile"/g) || []).length, 1);
    assert.ok(html.includes(kind ? `data-activity-create="${kind}"` : 'data-wellness-modal="create-event"'));
    if (kind || menu === 'events') {
      for (const filter of ['Proximos','Finalizados','Cancelados','Borradores']) assert.equal((html.match(new RegExp(`data-klini-event-filter="${filter}"`, 'g')) || []).length, 1);
      assert.match(html,/data-admin-event-search/);
      assert.match(html,/wellness-admin-event-filters/);
    }
  }
});

test('every activity menu filters its own community, dates, drafts and cost without mixing types', () => {
  for (const kind of ['class','workshop','podcast','live']) {
    const base={kind,community_name:'Respira y Avanza',title:'Respiración guiada',published:true,starts_at_utc:'2030-10-24T12:00:00Z',cost:100};
    const {filteredAdminActivities}=mount([{...base,id:'future'},{...base,id:'past',starts_at_utc:'2030-10-22T12:00:00Z'},{...base,id:'draft',published:false},{...base,id:'cancelled',status:'cancelled'},{...base,id:'other-community',community_name:'Otra'},{...base,id:'other-kind',kind:'event'}]);
    for (const [filter,expected] of [['Proximos','future'],['Finalizados','past'],['Borradores','draft'],['Cancelados','cancelled']]) {
      assert.deepEqual(Array.from(filteredAdminActivities({name:'Respira y Avanza'},{kind,title:kind},{adminEventFilter:filter,adminEventQuery:'respiracion',adminEventCost:'paid'},now),item=>item.id),[expected]);
    }
    assert.equal(filteredAdminActivities({name:'Respira y Avanza'},{kind,title:kind},{adminEventFilter:'Proximos',adminEventCost:'free'},now).length,0);
  }
});

test('existing administrator events can be recovered and linked without importing seeded events',()=>{
 const {publications,storage}=mount();
 const key='kliniCommunityDemoV1:test:admin:100000002';
 const event={id:'event-1792770000000-abc123',communityId:'respira',title:'Taller de Barro',status:'published'};
 storage.set(key,JSON.stringify({communities:[{id:'respira',name:'Respira y Avanza'}],communityMembers:[{communityId:'respira',userId:'community-admin-jimmy',role:'owner',status:'active'}],communityEvents:[event,{...event,id:'event-demo'}]}));
 const records=publications.getCreatedEventPublications();assert.equal(records.length,1);assert.equal(records[0].event.title,'Taller de Barro');assert.equal(records[0].community.name,'Respira y Avanza');
 publications.linkPublicEvent(event.id,51,'published');const stored=JSON.parse(storage.get(key)).communityEvents[0];assert.equal(stored.publicActivityId,51);assert.equal(stored.publicActivityStatus,'published');
});
