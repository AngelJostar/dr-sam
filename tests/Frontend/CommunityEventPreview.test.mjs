import test from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { previewData, imageUrl, activityData } = require('../../public/js/klini-community-composer.js');

test('a new event has useful placeholders and no invented engagement', () => {
  const data = previewData({}, 'Respira y Avanza');
  assert.equal(data.initials, 'RA');
  assert.equal(data.title, 'Nombre de tu evento');
  assert.equal(data.date, 'Fecha por definir');
  assert.equal(data.cost, 'Gratuito');
  assert.equal(data.image, '');
  assert.equal(data.likes, undefined);
});

test('editing event data updates the public details without mutating the form data', () => {
  const values = Object.freeze({title:'Yoga suave', shortDescription:'Una pausa', description:'Trae tu tapete.', category:'Yoga', date:'2026-10-24', startTime:'09:00', endTime:'10:00', timezone:'America/Mexico_City', modality:'mixed', location:'Casa Klini', capacity:'20', cost:'125'});
  const data = previewData(values, 'Respira y Avanza');
  assert.equal(data.title, 'Yoga suave');
  assert.equal(data.description, 'Trae tu tapete.');
  assert.match(data.date, /24.*oct.*2026/);
  assert.equal(data.time, '09:00 – 10:00');
  assert.equal(data.modality, 'Mixto');
  assert.equal(data.location, 'Casa Klini');
  assert.equal(data.capacity, '20 lugares');
  assert.equal(data.cost, '$125.00 MXN');
  assert.equal(previewData({...values, title:'Otro evento', cost:'0'}, 'Otra comunidad').title, 'Otro evento');
  assert.equal(values.title, 'Yoga suave');
});

test('virtual location follows the existing published event convention', () => {
  const data = previewData({modality:'remote', location:'https://meeting.example.test/private'}, 'Comunidad');
  assert.equal(data.modality, 'Virtual');
  assert.equal(data.location, 'Sala virtual Klini');
});

test('image preview accepts local and web images and rejects executable schemes', () => {
  for (const url of ['/brand/photo.png','https://example.test/photo.jpg','data:image/png;base64,aGVsbG8=']) assert.equal(imageUrl(url), url);
  for (const url of ['javascript:alert(1)', 'data:text/html,<script>', 'data:image/svg+xml;base64,AAAA', '//example.test/x', 'https://test/"><script>']) assert.equal(imageUrl(url), '');
});

test('clearing an image clears the preview and invalid numbers retain placeholders', () => {
  const data = previewData({imageUrl:'', date:'bad-date', capacity:'NaN', cost:'NaN'}, 'Comunidad');
  assert.equal(data.image, '');
  assert.equal(data.date, 'Fecha por definir');
  assert.equal(data.capacity, 'Cupo por definir');
  assert.equal(data.cost, 'Gratuito');
});

test('Klini publication uses the remaining description and keeps timezone and event details', () => {
  const data = activityData({communityId:'respira',title:' Yoga suave ',shortDescription:' Una pausa ',description:'Old unused text',date:'2030-10-24',startTime:'09:00',timezone:'America/Mexico_City',capacity:'12',cost:'150',imageUrl:'/images/yoga.png'},'Respira y Avanza');
  assert.deepEqual(data,{community_key:'respira',community_name:'Respira y Avanza',kind:'event',title:'Yoga suave',description:'Una pausa',image:'/images/yoga.png',starts_at:'2030-10-24T09:00',timezone:'America/Mexico_City',capacity:12,cost:150,published:true});
});
