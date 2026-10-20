const assert=require('node:assert/strict');
const {createRequire}=require('node:module');
const rr=createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
(async()=>{
 const browser=await rr('playwright').chromium.launch({channel:'msedge',headless:true,ignoreDefaultArgs:['--hide-scrollbars']});
 try {
  const page=await browser.newPage({viewport:{width:1440,height:900},reducedMotion:'reduce'});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://127.0.0.1:8000/acceso');await page.locator('#demo-username').selectOption('superadmin');
  await Promise.all([page.waitForURL('**/superadmin'),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
  const results=[];
  for(const section of ['dashboard','users','institutions','doctors','providers','insurance-carriers','insurance-advisors','hospitals','specialties','medications','medical-devices','patients','subscriptions','prescription-format','mix-request-format','advertising','modules','access']) {
   await page.goto('http://127.0.0.1:8000/superadmin'+(section==='dashboard'?'':'/catalogo/'+section));
   const metrics=await page.evaluate(()=>({zoom:getComputedStyle(document.body).zoom,header:document.querySelector('.superadmin-native-topbar').getBoundingClientRect().height,bottom:document.querySelector('.superadmin-native-screen').getBoundingClientRect().bottom,width:document.body.getBoundingClientRect().width,viewport:innerWidth,tables:[...document.querySelectorAll('.superadmin-native-workspace table')].filter(el=>el.getBoundingClientRect().width>0).map(el=>({width:el.getBoundingClientRect().width,min:getComputedStyle(el).minWidth}))}));
   assert.equal(Number(metrics.zoom),.8,section);assert(Math.abs(metrics.header-60.8)<1,section);assert(Math.abs(metrics.bottom-900)<1,section);
   metrics.tables.forEach(t=>assert.equal(t.min,'0px',section));
   results.push({section,...metrics});
  }
  await page.goto('http://127.0.0.1:8000/superadmin/catalogo/institutions?status=all');
  const rows=page.locator('[data-institution-row]');
  const secondName=await rows.nth(1).locator('[data-institution-select]').innerText();
  await rows.nth(1).locator('td').nth(3).click();await page.waitForLoadState('networkidle');
  assert.equal(await page.locator('#institution-detail-title').innerText(),secondName);
  const third=page.locator('[data-institution-select]').nth(2);const thirdName=await third.innerText();await third.focus();await page.keyboard.press('Space');await page.waitForLoadState('networkidle');
  assert.equal(await page.locator('#institution-detail-title').innerText(),thirdName);
  const selectedUrl=page.url();await page.locator('[data-institution-row]').first().getByRole('link',{name:'Editar',exact:true}).click();
  assert.equal(new URL(page.url()).search,new URL(selectedUrl).search);assert(await page.locator('.superadmin-user-modal:target').isVisible());
  await page.locator('.superadmin-user-modal:target').getByRole('link',{name:'Cancelar',exact:true}).click();
  await page.locator('.superadmin-native-workspace').evaluate(el=>el.scrollTop=0);
  const columns=await page.locator('[data-institution-row]').first().locator('td').evaluateAll(cells=>cells.map(el=>Math.round(el.getBoundingClientRect().width)));
  assert(columns[0]>columns[3]*2);
  const nameLines=await page.locator('[data-institution-select]').evaluateAll(elements=>elements.map(el=>({lines:Math.round(el.getBoundingClientRect().height/(parseFloat(getComputedStyle(el).lineHeight)*.8)),text:el.innerText})));
  assert(nameLines.every(el=>el.lines<=2),JSON.stringify(nameLines));
  await page.screenshot({path:'storage/app/qa/superadmin-compact-institutions.png'});
  await page.locator('.superadmin-native-catalog-table .drsam-table-filter-button').first().click();
  const filterMenu=page.locator('.drsam-table-filter-menu');await filterMenu.waitFor({state:'visible'});
  const filterBox=await filterMenu.boundingBox();assert(filterBox.x>=0&&filterBox.x+filterBox.width<=1440&&filterBox.y>=0&&filterBox.y+filterBox.height<=900);
  await page.keyboard.press('Escape');
  await page.goto('http://127.0.0.1:8000/superadmin/catalogo/doctors');
  for(const view of ['catalog','commissions','all']) {
   await page.locator(`[data-admin-filter="${view}"]`).click();await page.waitForLoadState('networkidle');
   assert.equal(await page.locator('[data-admin-filter-panel="catalog"]').isVisible(),view!=='commissions');
   assert.equal(await page.locator('#doctor-commissions').isVisible(),view!=='catalog');
   if(view!=='all')await page.screenshot({path:`storage/app/qa/superadmin-doctors-${view}.png`});
  }
  const rail=page.locator('.superadmin-bottom-scroll');await rail.waitFor({state:'visible'});
  await rail.evaluate(el=>el.scrollLeft=150);
  await page.waitForFunction(()=>[...document.querySelectorAll('.superadmin-native-catalog-scroll,.superadmin-doctor-commission-scroll')].some(el=>el.scrollLeft>100));
  const railBox=await rail.boundingBox();assert(Math.abs(railBox.y+railBox.height-900)<1);
  await page.setViewportSize({width:390,height:844});await page.goto('http://127.0.0.1:8000/superadmin/catalogo/institutions');
  assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
  await page.screenshot({path:'storage/app/qa/superadmin-compact-mobile.png',fullPage:true});
  assert.deepEqual(errors,[]);console.log(JSON.stringify({results,columns,nameLines,checks:['row click','keyboard selection','independent edit action','doctors table filters','scrollbar sync','mobile width'],errors}));
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
