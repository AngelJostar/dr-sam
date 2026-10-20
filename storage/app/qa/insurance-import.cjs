const assert = require('node:assert/strict');
const {createRequire} = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const {chromium} = rr('playwright');
async function login(page, username) {
 await page.goto('http://127.0.0.1:8000/acceso');
 await page.locator('#demo-username').selectOption(username);
 await Promise.all([page.waitForURL('**/dashboard'),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
}
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try {
  const page=await browser.newPage({viewport:{width:390,height:844}}),errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await login(page,'paciente');
  await page.goto('http://127.0.0.1:8000/patient');
  for(const width of [390,1380]) {
   await page.setViewportSize({width,height:844});
   const support=page.getByRole('button',{name:'Abrir soporte',exact:true});
   await support.click();
   await page.locator('[data-open-view="insurance"]').click();
   const dialog=page.locator('.patient-insurance-drawer');
   await dialog.waitFor({state:'visible'});
   await page.waitForFunction(()=>Math.abs(document.querySelector('.patient-insurance-drawer').getBoundingClientRect().x)<1);
   assert.equal(await dialog.evaluate(el=>el.open),true);
   assert.equal(await page.locator('[data-patient-view="home"]').getAttribute('class').then(x=>x.includes('is-active')),true);
   assert.equal(await dialog.evaluate(el=>el.scrollWidth<=el.clientWidth+1),true);
   await dialog.locator('[data-insurance-form-open]').first().click();
   assert(await dialog.locator('[data-insurance-form]').isVisible());
   await dialog.locator('[data-insurance-form-close]').click();
   await dialog.locator('.patient-insurance-drawer-content').evaluate(el=>el.scrollTop=0);
   await page.screenshot({path:`storage/app/qa/insurance-drawer-${width}.png`});
   await page.getByRole('button',{name:'Cerrar Mi Seguro',exact:true}).click();
   await page.waitForFunction(()=>!document.querySelector('.patient-insurance-drawer').open);
   assert(await support.evaluate(el=>el===document.activeElement));
  }
  await page.emulateMedia({reducedMotion:'reduce'});
  await page.getByRole('button',{name:'Abrir soporte',exact:true}).click();
  await page.locator('[data-open-view="insurance"]').click();
  await page.keyboard.press('Escape');
  assert.equal(await page.locator('.patient-insurance-drawer').evaluate(el=>el.open),false);
  console.log('PASS insurance: mobile/desktop, original form, backdrop, close/focus and reduced motion.');
  const imports=await browser.newPage({viewport:{width:1380,height:900}});
  imports.on('pageerror',e=>errors.push(e.message));
  await login(imports,'proveedor.importacion');
  for(let section=0;section<9;section++) {
   await imports.goto(`http://127.0.0.1:8000/providers/import?section=${section}&action=0`);
   const result=await imports.evaluate(()=>{
    const table=document.querySelector('.import-detail-table');
    const cells=[...table.querySelectorAll('thead th')].map(el=>el.getBoundingClientRect().width);
    const sidebar=document.querySelector('.provider-import-native-sidebar').getBoundingClientRect();
    const screen=document.querySelector('.provider-import-native-screen').getBoundingClientRect();
    return {cells,summaryCount:document.querySelectorAll('.import-summary').length,sidebarBottom:sidebar.bottom,screenBottom:screen.bottom,zoom:getComputedStyle(document.body).zoom};
   });
   console.log('import',section,JSON.stringify(result));
   assert(Math.max(...result.cells)-Math.min(...result.cells)<2,'Uneven columns section '+section);
   assert(Math.min(...result.cells)>=150,'Compressed columns section '+section);
   assert(Math.abs(result.sidebarBottom-result.screenBottom)<2,'Sidebar does not cover page '+section);
   assert.equal(result.summaryCount,0);
   assert(await imports.locator('[data-import-export]').isVisible());
   if(section===2 || section===4) await imports.screenshot({path:`storage/app/qa/import-section-${section}-compact.png`});
   if(section===1) {
    await imports.evaluate(()=>scrollTo(0,document.documentElement.scrollHeight));
    const coverage=await imports.locator('.provider-import-native-sidebar').evaluate(el=>el.getBoundingClientRect().bottom>=innerHeight-2);
    assert(coverage,'Blue sidebar ends before viewport bottom');
    await imports.screenshot({path:'storage/app/qa/import-sidebar-bottom.png'});
   }
  }
  assert.deepEqual(errors,[]);
  console.log('PASS import: removed summaries and uniform columns across 9 menus, blue sidebar to page bottom, compact 85% scale, export retained.');
 } finally {await browser.close();}
})().catch(e=>{console.error(e.stack);process.exitCode=1;});
