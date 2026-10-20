const assert = require('node:assert/strict');
const {createRequire} = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
(async () => {
 const browser = await rr('playwright').chromium.launch({channel:'msedge', headless:true, ignoreDefaultArgs:['--hide-scrollbars']});
 const page = await browser.newPage({viewport:{width:1440,height:900}, reducedMotion:'reduce'});
 const errors = [];
 page.on('pageerror', error => errors.push(error.message));
 const root = 'http://127.0.0.1:8000';
 const appearance = async prefix => page.evaluate(prefix => {
   const css = selector => getComputedStyle(document.querySelector(selector));
   const box = selector => {const r=document.querySelector(selector).getBoundingClientRect(); return {width:r.width,height:r.height}};
   return {scale:css('body').zoom, font:css('body').fontFamily, topbar:box(`.${prefix}-native-topbar`), sidebar:box(`.${prefix}-native-sidebar`), menuFont:css(`.${prefix}-native-menu a`).fontSize, menuColor:css(`.${prefix}-native-menu a:not(.is-active)`).color, sidebarColor:css(`.${prefix}-native-sidebar`).backgroundColor, workspaceColor:css(`.${prefix}-native-workspace`).backgroundColor, heading:css(prefix==='operational'?'.operational-native-table-heading h2':'.superadmin-native-section-heading h2').fontSize, tableFont:css(prefix==='operational'?'.operational-native-table td':'.superadmin-native-catalog-table td').fontSize};
 },prefix);
 try {
  await page.goto(root+'/acceso');
  await page.locator('#demo-username').selectOption('superadmin');
  await Promise.all([page.waitForURL(u=>!u.pathname.includes('/acceso')),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
  await page.goto(root+'/superadmin/catalogo/users'); await page.evaluate(()=>document.fonts.ready);
  const reference = await appearance('superadmin');
  await page.goto(root+'/operational'); await page.evaluate(()=>document.fonts.ready);
  const current = await appearance('operational');
  console.log(JSON.stringify({reference,current}));
  for (const key of ['scale','font','menuFont','menuColor','sidebarColor','workspaceColor','heading','tableFont']) assert.equal(current[key],reference[key],key);
  assert(Math.abs(current.topbar.height-reference.topbar.height)<2,'header height');
  assert.equal(current.sidebar.width,reference.sidebar.width,'sidebar width');
  assert.equal(await page.locator('.klini-carousel').count(),0);
  await page.screenshot({path:'storage/app/qa/operational-workspace-desktop.png'});
  await page.getByRole('button',{name:'Filtrar Folio',exact:true}).click();
  assert(await page.getByRole('dialog',{name:'Filtrar columna'}).isVisible());
  await page.getByRole('dialog',{name:'Filtrar columna'}).getByRole('button',{name:'Cancelar',exact:true}).click();
  await page.locator('.operational-view-button').first().click();
  assert(await page.locator('#solicitud-detalle').isVisible());
  await page.locator('#solicitud-detalle header a').click();
  for (const [name,path] of [
   ['hospital-calendar','/operational?area=nursing&section=calendar'],
   ['oncology-calendar','/operational?area=oncology&section=calendar'],
   ['rooms','/operational?area=oncology&section=infusion-rooms&oncology_track=rooms'],
   ['pharmacy','/operational?area=inpatient-pharmacy&section=pending'],
   ['patients','/operational/patients'],
   ['patient-form','/operational/patients?modal=patient'],
  ]) {
   const response=await page.goto(root+path); assert.equal(response.status(),200,name);
   await page.evaluate(()=>document.fonts.ready);
   assert(await page.locator('.operational-native-sidebar').isVisible(),name);
   await page.screenshot({path:`storage/app/qa/operational-${name}.png`});
  }
  await page.goto(root+'/operational?area=oncology&section=calendar');
  await page.locator('.operational-oncology-filter').filter({hasText:'Soporte'}).click();
  assert(page.url().includes('section=support'));
  for (const width of [900,390]) {
   await page.setViewportSize({width,height:900});
   await page.goto(root+'/operational');
   const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);
   assert.equal(overflow,false,`page overflow ${width}`);
   await page.screenshot({path:`storage/app/qa/operational-${width}.png`});
  }
  assert.deepEqual(errors,[]);
  console.log('Operational appearance, navigation, filters, detail, calendars, patient form and responsive views passed.');
 } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1});

