const assert=require('node:assert/strict');
const fs=require('node:fs');
const {createRequire}=require('node:module');
const rr=createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
(async()=>{
 const browser=await rr('playwright').chromium.launch({channel:'msedge',headless:true});
 try {
  const page=await browser.newPage({viewport:{width:1440,height:900},reducedMotion:'reduce'});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://127.0.0.1:8000/acceso');
  await page.locator('#demo-username').selectOption('superadmin');
  await Promise.all([page.waitForURL('**/superadmin'),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
  await page.route('**/superadmin/catalogo/institutions?feedback=*',async route=>{
   const response=await route.fetch();
   const variant=new URL(route.request().url()).searchParams.get('feedback');
   const fixture=fs.readFileSync(`storage/app/qa/superadmin-feedback-${variant}.html`,'utf8');
   await route.fulfill({response,body:(await response.text()).replace(/(<body\b[^>]*>)/,'$1'+fixture)});
  });
  for(const variant of ['success','error']) {
   await page.goto('http://127.0.0.1:8000/superadmin/catalogo/institutions?feedback='+variant);
   const dialog=page.locator('[data-admin-feedback-dialog]');
   await dialog.waitFor({state:'visible'});
   assert(await dialog.evaluate(el=>el.matches(':modal')));
   const close=dialog.getByRole('button',{name:'Cerrar mensaje',exact:true});
   assert(await close.evaluate(el=>el===document.activeElement));
   await page.keyboard.press('Tab');
   await page.keyboard.press('Tab');
   assert(await dialog.evaluate(el=>el.contains(document.activeElement)));
   const box=await dialog.boundingBox();const buttonBox=await close.boundingBox();
   assert(buttonBox.x>box.x+box.width/2&&buttonBox.y<box.y+60);
   await page.screenshot({path:`storage/app/qa/superadmin-feedback-${variant}.png`});
   if(variant==='success') await close.click();else await page.keyboard.press('Escape');
   assert.equal(await dialog.isVisible(),false);
   await page.waitForFunction(()=>document.querySelector('.superadmin-native-workspace')===document.activeElement);
  }
  await page.setViewportSize({width:390,height:844});
  await page.goto('http://127.0.0.1:8000/superadmin/catalogo/institutions?feedback=error');
  const modal=page.locator('[data-admin-feedback-dialog]');await modal.waitFor({state:'visible'});
  const b=await modal.boundingBox();assert(b.x>=0&&b.x+b.width<=390&&b.y>=0&&b.y+b.height<=844);
  await page.screenshot({path:'storage/app/qa/superadmin-feedback-mobile.png'});
  assert.deepEqual(errors,[]);
  console.log(JSON.stringify({checks:['success popup','validation popup','close at upper right','focus trap','close button','Escape','mobile bounds'],errors}));
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
