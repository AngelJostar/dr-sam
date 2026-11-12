const fs=require('fs'); const path=require('path');
const {chromium}=require('C:/laragon/www/klini_mobile/.build-tmp/iphone-release-20261109-2055/node_modules/playwright');
(async()=>{
 const browser=await chromium.launch({headless:true,channel:"msedge"});
 const context=await browser.newContext();
 const root='C:/laragon/www/dr-sam';
 const pages=['orders'];
 await context.route('**/*',async route=>{
  const u=new URL(route.request().url()); const p=decodeURIComponent(u.pathname);
  let file= p.startsWith('/qa/') ? path.join(root,'storage/app/qa','pharmacy-'+p.slice(4)+'.html') : path.join(root,'public',p);
  if(!path.resolve(file).startsWith(path.resolve(root)+path.sep)||!fs.existsSync(file)||!fs.statSync(file).isFile())return route.abort();
  const ext=path.extname(file); const types={'.html':'text/html; charset=utf-8','.css':'text/css','.js':'application/javascript','.svg':'image/svg+xml','.woff':'font/woff','.woff2':'font/woff2','.png':'image/png'};
  await route.fulfill({body:fs.readFileSync(file),contentType:types[ext]||'application/octet-stream'});
 });
 const page=await context.newPage(); const results=[];
 for(const width of [390]){
  await page.setViewportSize({width,height:900});
  for(const name of pages){
   await page.goto('http://qa.local/qa/'+name); await page.waitForTimeout(250);
   if(name==='products') {const d=page.locator('details').first(); if(await d.count())await d.evaluate(e=>e.open=true);}
   const metrics=await page.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth,body:document.body.scrollWidth,
    headings:[...document.querySelectorAll('h1,h2')].map(e=>e.textContent.trim()),tables:[...document.querySelectorAll('table')].map(e=>({width:e.scrollWidth,parent:e.parentElement.clientWidth})),
    over:[...document.querySelectorAll('body *')].filter(e=>e.getBoundingClientRect().right>innerWidth+2 && getComputedStyle(e).position!=='fixed').map(e=>({el:e.tagName+'.'+e.className,right:e.getBoundingClientRect().right,overflow:getComputedStyle(e.parentElement).overflowX,parent:e.parentElement.className}))}));
   results.push({name,width,...metrics});
   await page.screenshot({path:path.join(root,'storage/app/qa/pharmacy-'+name+'-'+width+'.png'),fullPage:true});
  }
 }
 fs.writeFileSync(path.join(root,'storage/app/qa/pharmacy-visual-results.json'),JSON.stringify(results,null,2));
 console.log(JSON.stringify(results)); await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
