const assert=require('node:assert/strict');
const {createRequire}=require('node:module');
const rr=createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
(async()=>{
 const browser=await rr('playwright').chromium.launch({channel:'msedge',headless:true,ignoreDefaultArgs:['--hide-scrollbars']});
 const page=await browser.newPage({viewport:{width:1440,height:900},reducedMotion:'reduce'});
 const errors=[];page.on('pageerror',e=>errors.push(e.message));const root='http://127.0.0.1:8000';
 const appearance=prefix=>page.evaluate(prefix=>{
  const s=selector=>getComputedStyle(document.querySelector(selector));
  return {scale:s('body').zoom,font:s('body').fontFamily,header:document.querySelector(`.${prefix}-native-topbar`).getBoundingClientRect().height,sidebar:document.querySelector(`.${prefix}-native-sidebar`).getBoundingClientRect().width,menuFont:s(`.${prefix}-native-menu a`).fontSize,menuColor:s(`.${prefix}-native-menu a:not(.is-active)`).color,sidebarColor:s(`.${prefix}-native-sidebar`).backgroundColor,workspaceColor:s(`.${prefix}-native-workspace`).backgroundColor,tileHeight:s(prefix==='unit'?'.unit-service-carousel-button':'.superadmin-filter-tile').height};
 },prefix);
 try{
  await page.goto(root+'/acceso');await page.locator('#demo-username').selectOption('superadmin');
  await Promise.all([page.waitForURL(u=>!u.pathname.includes('/acceso')),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
  await page.goto(root+'/superadmin/catalogo/users');await page.evaluate(()=>document.fonts.ready);
  const reference=await appearance('superadmin');
  await page.goto(root+'/unit?section=procedure-areas');await page.evaluate(()=>document.fonts.ready);
  const current=await appearance('unit');console.log(JSON.stringify({reference,current}));assert.deepEqual(current,reference);
  assert.equal(await page.locator('.klini-carousel').count(),0);
  await page.locator('[data-unit-carousel-filter=infusion]').click();
  assert.equal(await page.locator('[data-procedure-catalog=infusion]').getAttribute('hidden'),null);
  assert.equal(await page.locator('[data-procedure-category=infusion]').getAttribute('aria-selected'),'true');
  await page.locator('[data-unit-carousel-filter=all]').click();
  await page.screenshot({path:'storage/app/qa/unit-procedure-admin.png'});
  await page.locator('[data-procedure-create]:visible').click();
  assert(await page.locator('[data-procedure-create-dialog]').isVisible());
  await page.screenshot({path:'storage/app/qa/unit-procedure-form-admin.png'});
  for(const section of ['profile','services','users','patients','doctors','specialties','external-pharmacy','medications']){
   const response=await page.goto(root+'/unit?section='+section);assert.equal(response.status(),200);
   await page.evaluate(()=>document.fonts.ready);
   const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);assert.equal(overflow,false,section);
   await page.screenshot({path:`storage/app/qa/unit-${section}-admin.png`});
  }
  await page.goto(root+'/unit?section=services');
  for(const tab of await page.locator('[data-unit-service-tab]').all()){
   await tab.click();const id=await tab.getAttribute('data-unit-service-tab');assert(await page.locator(`[data-unit-service-panel="${id}"]`).isVisible());
  }
  for(const width of [900,390]){
   await page.setViewportSize({width,height:900});await page.goto(root+'/unit?section=procedure-areas');
   await page.screenshot({path:`storage/app/qa/unit-${width}-admin.png`});
   if(width===390) console.log(await page.evaluate(()=>[...document.querySelectorAll('body *')].filter(el=>el.getBoundingClientRect().right>innerWidth && getComputedStyle(el).display!=='none').slice(0,15).map(el=>({tag:el.tagName,class:el.className,width:el.getBoundingClientRect().width,right:el.getBoundingClientRect().right}))));
   assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1),false,`overflow ${width}`);
  }
  assert.deepEqual(errors,[]);console.log('Unit appearance, catalogs, service panels, procedure filters, dialog and responsive layouts passed.');
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
