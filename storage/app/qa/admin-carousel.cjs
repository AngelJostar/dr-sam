const assert = require('node:assert/strict');
const {createRequire}=require('node:module');
const rr=createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const {chromium}=rr('playwright');
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true,ignoreDefaultArgs:['--hide-scrollbars']});
 try {
  const page=await browser.newPage({viewport:{width:1400,height:900},reducedMotion:'reduce'});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://127.0.0.1:8000/acceso');
  await page.locator('#demo-username').selectOption('superadmin');
  await Promise.all([page.waitForURL('**/superadmin'),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
  const urls=await page.locator('.superadmin-native-menu a').evaluateAll(es=>es.map(e=>e.href));
  const results=[];
  for(const url of urls){
   await page.goto(url);await page.waitForLoadState('networkidle');
   assert.equal(await page.locator('[data-admin-carousel]').count(),1,url);
   assert.equal(await page.locator('.superadmin-native-catalog-filters').count(),0,url);
   const result=await page.evaluate(()=>{
    const tiles=[...document.querySelectorAll('[data-admin-filter]')];
    const rail=document.querySelector('.superadmin-bottom-scroll');
    return {path:location.pathname,tiles:tiles.length,size:tiles[0].getBoundingClientRect().toJSON(),labelsClipped:tiles.filter(e=>e.scrollHeight>e.clientHeight+1).map(e=>e.textContent.trim()),overflow:document.documentElement.scrollWidth>innerWidth+1,rail:rail.hidden?null:rail.getBoundingClientRect().toJSON()};
   });
   assert(Math.abs(result.size.width-63)<1 && Math.abs(result.size.height-66.6)<1,JSON.stringify(result));
   assert(!result.overflow,JSON.stringify(result));
   assert.deepEqual(result.labelsClipped,[],url+' labels clipped');
   if(result.rail) assert(Math.abs(result.rail.bottom-900)<1,JSON.stringify(result));
   results.push(result);
   if(url.endsWith('/users'))await page.screenshot({path:'storage/app/qa/admin-carousel-users.png'});
   const panelButton=page.locator('button[data-admin-filter]').nth(1);
   if(await panelButton.count()){
    const selected=await panelButton.getAttribute('data-admin-filter');
    await panelButton.click();
    const visible=await page.locator('[data-admin-filter-panel]:visible').evaluateAll(es=>es.map(e=>e.dataset.adminFilterPanel));
    assert(visible.length>0 && visible.every(x=>x===selected),`${url}: ${visible}`);
    await page.locator('button[data-admin-filter="all"]').click();
   }
  }
  await page.goto('http://127.0.0.1:8000/superadmin/catalogo/users');await page.waitForLoadState('networkidle');
  const rail=page.locator('.superadmin-bottom-scroll');
  await rail.evaluate(e=>{e.scrollLeft=e.scrollWidth;});await page.waitForTimeout(100);
  const right=await page.locator('.superadmin-native-catalog-scroll').first().evaluate(e=>({left:e.scrollLeft,max:e.scrollWidth-e.clientWidth}));
  assert(Math.abs(right.left-right.max)<2,JSON.stringify(right));
  await page.locator('.superadmin-native-catalog-scroll').first().evaluate(e=>e.scrollLeft=0);await page.waitForTimeout(100);
  assert.equal(await rail.evaluate(e=>e.scrollLeft),0);
  await page.locator('a[data-admin-filter="inactive"]').click();await page.waitForLoadState('networkidle');
  assert.equal(new URL(page.url()).searchParams.get('status'),'inactive');
  assert.equal(await page.locator('a[data-admin-filter="inactive"]').getAttribute('aria-current'),'true');
  await page.goto('http://127.0.0.1:8000/superadmin/catalogo/prescription-format');
  const clinical=page.locator('[data-admin-filter-panel="clinical"] textarea');
  await clinical.fill('Texto de prueba que debe conservarse');
  await page.locator('button[data-admin-filter="patient"]').click();
  await page.locator('button[data-admin-filter="clinical"]').click();
  assert.equal(await clinical.inputValue(),'Texto de prueba que debe conservarse');
  await page.setViewportSize({width:390,height:844});
  for(const path of ['users','access','mix-request-format']){
   await page.goto('http://127.0.0.1:8000/superadmin/catalogo/'+path);await page.waitForLoadState('networkidle');
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),path+' mobile overflow');
   const next=page.locator('[data-admin-carousel-next]');
   if(await next.isEnabled()) {await next.click();await page.waitForTimeout(100);assert(await page.locator('[data-admin-carousel-track]').evaluate(e=>e.scrollLeft>0));}
  }
  await page.goto('http://127.0.0.1:8000/superadmin/catalogo/users');await page.waitForLoadState('networkidle');
  await page.screenshot({path:'storage/app/qa/admin-carousel-mobile.png'});
  assert.deepEqual(errors,[]);
  console.log(JSON.stringify({menus:results.length,cardSize:results[0].size.width+' x '+results[0].size.height,fixedRails:results.filter(x=>x.rail).length,errors}));
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
