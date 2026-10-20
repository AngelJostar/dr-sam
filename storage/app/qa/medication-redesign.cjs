const assert=require('node:assert/strict');
const {createRequire}=require('node:module');
const rr=createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
(async()=>{
 const browser=await rr('playwright').chromium.launch({channel:'msedge',headless:true,ignoreDefaultArgs:['--hide-scrollbars']});
 const page=await browser.newPage({viewport:{width:1440,height:900},reducedMotion:'reduce'});const errors=[];page.on('pageerror',e=>errors.push(e.message));
 try{
  await page.goto('http://127.0.0.1:8000/acceso');await page.locator('#demo-username').selectOption('superadmin');
  await Promise.all([page.waitForURL(u=>!u.pathname.includes('/acceso')),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
  await page.goto('http://127.0.0.1:8000/superadmin/catalogo/medications');await page.evaluate(()=>document.fonts.ready);
  const table=page.locator('.superadmin-native-catalog-table');
  assert.equal(await table.locator('thead th').count(),10);assert.equal(await table.locator('.drsam-table-filter-button').count(),10);
  const geometry=await page.evaluate(()=>{const table=document.querySelector('.superadmin-native-catalog-table').getBoundingClientRect();const card=document.querySelector('.superadmin-native-catalog-card').getBoundingClientRect();return {table:table.width,card:card.width}});
  assert(Math.abs(geometry.table-geometry.card)<3);
  assert(await table.locator('.superadmin-medication-name').first().isVisible());assert(await table.locator('.superadmin-medication-code').first().isVisible());
  const name=await table.locator('.superadmin-medication-name').first().boundingBox();const code=await table.locator('.superadmin-medication-code').first().boundingBox();assert(code.y>=name.y+name.height);
  await page.screenshot({path:'storage/app/qa/medication-redesign-desktop.png'});
  await page.getByRole('button',{name:'Filtrar Denominación distintiva',exact:true}).click();
  const menu=page.getByRole('dialog',{name:'Filtrar columna'});assert(await menu.getByRole('checkbox',{name:'Por registrar',exact:true}).isVisible());
  await menu.getByRole('button',{name:'Aceptar',exact:true}).click();
  const csv=await page.request.get(await page.getByRole('link',{name:'Exportar CSV',exact:true}).getAttribute('href'));assert.equal(csv.status(),200);assert((await csv.text()).includes('Denominación distintiva'));
  await page.getByRole('link',{name:'Inactivos',exact:true}).click();assert.equal(await table.locator('tbody tr').count(),1);assert((await table.locator('tbody').innerText()).includes('Sin registros'));
  await page.getByRole('link',{name:'Todos',exact:true}).click();
  for(const width of [1100,900,390]){
   await page.setViewportSize({width,height:900});await page.screenshot({path:`storage/app/qa/medication-redesign-${width}.png`});
   assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1),false,`page overflow ${width}`);
  }
  assert.deepEqual(errors,[]);console.log('Medication full-width design, 10 column filters, code placement, CSV and responsive layouts passed.');
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
