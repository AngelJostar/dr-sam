const assert=require('node:assert/strict');
const fs=require('node:fs');
const {createRequire}=require('node:module');
const rr=createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
(async()=>{
 const browser=await rr('playwright').chromium.launch({channel:'msedge',headless:true,ignoreDefaultArgs:['--hide-scrollbars']});
 const page=await browser.newPage({viewport:{width:1440,height:900},reducedMotion:'reduce'});const errors=[];
 page.on('pageerror',e=>errors.push(e.message));
 const url='http://127.0.0.1:8000/superadmin/catalogo/insurance-carriers';
 try{
 await page.goto('http://127.0.0.1:8000/acceso');await page.locator('#demo-username').selectOption('superadmin');
 await Promise.all([page.waitForURL(u=>!u.pathname.includes('/acceso')),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
 for(const status of ['all','active','inactive']){
   await page.goto(url+'?view=carriers&status='+status);
   await page.getByRole('button',{name:'Filtrar Aseguradora',exact:true}).waitFor();
   assert.equal(await page.locator('.admin-insurance-carrier-table th .drsam-table-filter-button').count(),9);
   assert.equal(await page.locator('[data-admin-filter=carriers]').count(),0);
   await page.getByRole('button',{name:'Filtrar Aseguradora',exact:true}).click();
   const menu=page.getByRole('dialog',{name:'Filtrar columna'});
   assert(await menu.getByText('No hay valores disponibles.').isVisible());
   assert(await menu.getByRole('button',{name:'Aceptar',exact:true}).isDisabled());
   await menu.getByRole('button',{name:'Cancelar',exact:true}).click();
 }
 await page.screenshot({path:'storage/app/qa/insurance-header-filters-empty.png'});
 for(const status of ['all','active','inactive']){
   await page.route('**/superadmin/catalogo/insurance-carriers?view=carriers&status='+status,r=>r.fulfill({contentType:'text/html',body:fs.readFileSync('storage/app/qa/carriers-'+status+'.html','utf8')}));
   await page.goto(url+'?view=carriers&status='+status);
   const expected=status==='all'?3:status==='active'?2:1;
   const table=page.locator('.admin-insurance-carrier-table');
   const selected=status==='inactive'?'Aseguradora C':'Aseguradora A';
   await page.getByRole('button',{name:'Filtrar Aseguradora',exact:true}).click();
   let menu=page.getByRole('dialog',{name:'Filtrar columna'});
   await menu.locator('[data-filter-all]').uncheck();
   await menu.getByRole('checkbox',{name:selected,exact:true}).check();
   await menu.getByRole('button',{name:'Aceptar',exact:true}).click();
   assert.equal(await table.locator('tbody tr:visible').count(),1);
   await page.getByRole('button',{name:'Filtrar Aseguradora',exact:true}).click();
   menu=page.getByRole('dialog',{name:'Filtrar columna'});
   await menu.locator('[data-filter-all]').check();
   await menu.getByRole('button',{name:'Aceptar',exact:true}).click();
   assert.equal(await table.locator('tbody tr:visible').count(),expected);
 }
 await page.unrouteAll();
 for(const category of ['gmm','life']){
   await page.goto(url+'?view='+category+'&status=inactive');
   await page.getByRole('button',{name:'Filtrar Plan',exact:true}).waitFor();
   assert.equal(await page.locator('[data-admin-filter=carriers]').count(),0);
 }
 await page.goto(url+'?view=gmm&status=all');
 await page.getByRole('button',{name:'Filtrar Plan',exact:true}).click();
 const menu=page.getByRole('dialog',{name:'Filtrar columna'});
 await menu.locator('[data-filter-all]').uncheck();
 await menu.getByRole('checkbox',{name:'Klini Esencial',exact:true}).check();
 await menu.getByRole('button',{name:'Aceptar',exact:true}).click();
 assert.equal(await page.locator('.admin-insurance-table tbody tr:visible').count(),1);
 for(const width of [1440,900]){
   await page.setViewportSize({width,height:width===1440?900:650});
   await page.goto('http://127.0.0.1:8000/superadmin/catalogo/medications');
   await page.getByRole('button',{name:'Filtrar Grupo',exact:true}).waitFor();
   await page.evaluate(()=>document.fonts.ready);
   const sizes=await page.locator('.superadmin-native-catalog-table').evaluate(t=>({table:t.getBoundingClientRect().width,headers:[...t.tHead.rows[0].cells].map(c=>({width:c.getBoundingClientRect().width,clipped:c.querySelector('.drsam-table-filter-label').scrollWidth>c.querySelector('.drsam-table-filter-label').clientWidth+1})),cells:[...t.tBodies[0].rows[0].cells].map(c=>c.getBoundingClientRect().width)}));
   assert(sizes.cells[1]<130);assert(sizes.cells[2]<220);assert(sizes.cells[3]<220);
   assert(sizes.headers.every(h=>!h.clipped),JSON.stringify(sizes));
   assert(sizes.table<1000);
   await page.screenshot({path:'storage/app/qa/medications-compact-'+width+'.png'});
   console.log(width,JSON.stringify(sizes));
 }
 assert.deepEqual(errors,[]);
 console.log('Carrier filters (empty and populated), plan filters and compact medication columns passed.');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
