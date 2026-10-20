const assert = require('node:assert/strict');
const {createRequire}=require('node:module');
const rr=createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const {chromium}=rr('playwright');
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try {
  const page=await browser.newPage({viewport:{width:1400,height:900}});
  const errors=[]; page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://127.0.0.1:8000/acceso');
  assert.equal(await page.locator('body').evaluate(e=>getComputedStyle(e).zoom),'0.9');
  await page.locator('#demo-username').selectOption('superadmin');
  await Promise.all([page.waitForURL('**/superadmin'),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
  const links=await page.locator('.superadmin-native-menu a').evaluateAll(es=>es.map(e=>e.href));
  const labels=await page.locator('.superadmin-native-menu a').allTextContents();
  assert.deepEqual(labels.slice(1,12).map(s=>s.trim()),['Usuarios de la plataforma','Instituciones','Médicos','Vendedores','Aseguradoras','Asesores de seguros','Especialidades','Medicamentos','Dispositivos médicos','Pacientes','Hospitales']);
  for(const url of links){
   await page.goto(url); await page.waitForLoadState('networkidle');
   const result=await page.evaluate(()=>{
    const dark=[];
    for(const e of document.querySelectorAll('.superadmin-native-screen *')){
     const r=e.getBoundingClientRect(),s=getComputedStyle(e);
     if(r.width<25||r.height<20||s.visibility==='hidden')continue;
     const colors=[s.backgroundColor,...(s.backgroundImage.match(/rgba?\([^)]+\)/g)||[])];
     if(colors.some(c=>{const v=c.match(/[\d.]+/g)?.map(Number)||[];return v.length>=3&&(v.length===3||v[3]>.5)&&.2126*v[0]+.7152*v[1]+.0722*v[2]<165;}))dark.push(e.tagName+'.'+e.className);
    }
    const sidebar=document.querySelector('.superadmin-native-sidebar');
    return {dark:[...new Set(dark)],sidebar:sidebar.getBoundingClientRect().height,topbar:document.querySelector('.superadmin-native-topbar').getBoundingClientRect().height,oldHero:!!document.querySelector('.superadmin-native-header'),background:getComputedStyle(sidebar).backgroundColor,overflow:document.documentElement.scrollWidth>innerWidth+1};
   });
   assert.deepEqual(result.dark,[],url);
   assert(Math.abs(result.sidebar+result.topbar-900)<1,url+' sidebar height');
   assert(!result.oldHero,url+' old hero');
   assert(await page.getByText('Superadministrador · Sesión activa',{exact:true}).isVisible(),url+' active user');
   assert(!result.overflow,url+' overflow');
   assert.equal(result.background,'rgb(239, 248, 246)');
  }
  await page.goto('http://127.0.0.1:8000/superadmin/catalogo/specialties');
  await page.waitForLoadState('networkidle');
  await page.screenshot({path:'storage/app/qa/admin-light-compact.png'});
  const menu=page.locator('.superadmin-native-menu'),content=page.locator('.superadmin-native-workspace');
  await menu.evaluate(e=>e.scrollTop=0); await content.evaluate(e=>e.scrollTop=0);
  await menu.hover(); await page.mouse.wheel(0,350);
  await page.waitForFunction(()=>document.querySelector('.superadmin-native-menu').scrollTop>100);
  const menuTop=await menu.evaluate(e=>e.scrollTop);
  assert.equal(await content.evaluate(e=>e.scrollTop),0);
  const item=page.locator('.superadmin-native-menu a[href$="/specialties"]');
  await item.hover();
  const contrast=await item.evaluate(e=>{
   const luminance=c=>{const v=c.match(/[\d.]+/g).slice(0,3).map(Number).map(n=>{n/=255;return n<=.04045?n/12.92:((n+.055)/1.055)**2.4;});return .2126*v[0]+.7152*v[1]+.0722*v[2];};
   const s=getComputedStyle(e),a=luminance(s.color),b=luminance(s.backgroundColor);return (Math.max(a,b)+.05)/(Math.min(a,b)+.05);
  });
  assert(contrast>=4.5,'Menu contrast');
  await page.goto('http://127.0.0.1:8000/superadmin'); await page.waitForLoadState('networkidle');
  await content.hover(); await page.mouse.wheel(0,500);
  await page.waitForFunction(()=>document.querySelector('.superadmin-native-workspace').scrollTop>100);
  assert.equal(await page.evaluate(()=>scrollY),0);
  const paths=['/unit?service=4','/unit?service=1','/institution','/providers/import?section=1&action=0','/providers/npt','/providers/chemo','/providers/medicines','/providers/clinical-labs','/doctor','/patient','/orders','/messenger','/operational','/outpatient','/pharmacy','/external-pharmacy','/insurance-advisor','/insurance','/communities/admin','/communities'];
  const issues=[];
  for(const path of paths){
   const response=await page.goto('http://127.0.0.1:8000'+path); await page.waitForLoadState('networkidle');
   assert(response.ok(),path+' HTTP '+response.status());
   const result=await page.evaluate(()=>({zoom:Number(getComputedStyle(document.body).zoom),overflow:document.documentElement.scrollWidth>innerWidth+1,height:document.body.getBoundingClientRect().height,body:document.body.className}));
   assert(result.zoom<1,path+' scale');
   if(result.overflow)issues.push({path,issue:'horizontal overflow'});
   if(result.height<899)issues.push({path,issue:'viewport gap'});
   if(['/unit?service=4','/providers/import?section=1&action=0','/doctor','/patient'].includes(path))await page.screenshot({path:'storage/app/qa/compact-'+path.split('?')[0].slice(1).replaceAll('/','-')+'.png'});
  }
  await page.setViewportSize({width:390,height:844});
  for(const path of ['/superadmin/catalogo/doctors','/unit?service=4','/providers/import?section=1&action=0','/patient']){
   await page.goto('http://127.0.0.1:8000'+path); await page.waitForLoadState('networkidle');
   const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);
   if(overflow)issues.push({path,issue:'mobile horizontal overflow'});
  }
  console.log(JSON.stringify({adminViews:links.length,profileViews:paths.length,contrast,issues,errors}));
  assert.deepEqual(errors,[]);
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
