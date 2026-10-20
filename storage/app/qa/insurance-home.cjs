const assert=require('node:assert/strict');
const {createRequire}=require('node:module');
const rr=createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const {chromium}=rr('playwright');
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try{
  const page=await browser.newPage({viewport:{width:390,height:844},reducedMotion:'reduce'});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://127.0.0.1:8000/acceso');await page.locator('#demo-username').selectOption('paciente');
  await Promise.all([page.waitForURL(u=>!u.pathname.includes('/acceso')),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
  await page.goto('http://127.0.0.1:8000/patient');
  await page.getByRole('button',{name:'Abrir soporte',exact:true}).click();
  await page.locator('[data-open-view="insurance"]').click();
  const drawer=page.locator('.patient-insurance-drawer');
  await drawer.locator('[data-insurance-screen="purchase"]').click();
  for(let step=1;step<10;step++){
   const panel=drawer.locator(`[data-enroll-step="${step}"]`);
   await panel.waitFor({state:'visible'});
   const required=panel.locator('[required]');
   for(let i=0;i<await required.count();i++){
    const field=required.nth(i),type=await field.getAttribute('type');
    if(type==='checkbox')await field.check();
    else if(type==='radio'){if(['no','self'].includes(await field.getAttribute('value')))await field.check();}
    else if(await field.evaluate(e=>e.tagName)==='SELECT'){const option=await field.locator('option').evaluateAll(es=>es.find(e=>e.value)?.value);if(option)await field.selectOption(option);}
    else if(type==='date')await field.fill('1988-03-14');
    else if(type==='email')await field.fill('qa@example.test');
    else if(type==='number')await field.fill('100');
    else if(type==='tel')await field.fill('5555550100');
    else await field.fill('Dato de prueba');
   }
   if(step===8){
    const canvas=panel.locator('canvas');await canvas.scrollIntoViewIfNeeded();const r=await canvas.boundingBox();
    await page.mouse.move(r.x+20,r.y+30);await page.mouse.down();await page.mouse.move(r.x+80,r.y+60,{steps:5});await page.mouse.up();
   }
   await panel.locator('[data-enroll-next]').click();
  }
  const final=drawer.locator('[data-enroll-step="10"]');await final.waitFor({state:'visible'});
  assert.equal(await final.getByText('Ver mi seguro actual',{exact:true}).count(),0);
  assert.equal(await final.locator('[data-enroll-restart]').count(),0);
  await final.getByRole('button',{name:'Ir a Inicio',exact:true}).click();
  assert(await drawer.locator('[data-insurance-screen-panel="entry"]').isVisible());
  assert(!(await final.isVisible()));assert.deepEqual(errors,[]);
  await page.screenshot({path:'storage/app/qa/insurance-home-verified.png'});
  console.log('PASS: 10 demo steps; Ir a Inicio opens Mi Seguro home, without the two former buttons.');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
