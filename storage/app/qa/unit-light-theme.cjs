const assert = require('node:assert/strict');
const {createRequire} = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const {chromium} = rr('playwright');
(async () => {
  const browser = await chromium.launch({channel:'msedge',headless:true});
  try {
    const page = await browser.newPage({viewport:{width:1400,height:900}});
    const errors = [];
    page.on('pageerror',e=>errors.push(e.message));
    await page.goto('http://127.0.0.1:8000/acceso');
    await page.locator('#demo-username').selectOption('superadmin');
    await Promise.all([page.waitForURL('**/superadmin'),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
    for (const query of ['service=1','service=4','section=profile','section=users','section=patients','section=doctors','section=specialties','section=external-pharmacy','section=procedure-areas','section=medications']) {
      await page.goto('http://127.0.0.1:8000/unit?'+query);
      await page.waitForLoadState('networkidle');
      const dark = await page.evaluate(() => [...document.querySelectorAll('body *')].filter(el=>{
        const r=el.getBoundingClientRect(); if(!r.width || !r.height || r.bottom<0 || r.top>innerHeight) return false;
        const s=getComputedStyle(el), c=s.backgroundColor.match(/[\d.]+/g)?.map(Number);
        return c && (c.length<4 || c[3]>.8) && (c[0]+c[1]+c[2])/3<140;
      }).map(el=>({class:el.className,color:getComputedStyle(el).backgroundColor})).filter((v,i,a)=>a.findIndex(o=>o.class===v.class && o.color===v.color)===i));
      console.log(JSON.stringify({query,dark}));
      if(['service=1','service=4','section=users','section=profile'].includes(query)) await page.screenshot({path:`storage/app/qa/unit-light-${query.replace('=','-')}.png`});
      assert.equal(await page.locator('body').evaluate(el=>getComputedStyle(el).zoom),'0.85');
      assert.equal(await page.locator('.unit-native-sidebar').evaluate(el=>getComputedStyle(el).backgroundColor),'rgb(239, 248, 246)');
      assert(await page.evaluate(()=>document.documentElement.scrollWidth <= innerWidth + 1));
    }
    assert.deepEqual(errors,[]);
    console.log('PASS: páginas de Unidad, menú claro, escala uniforme y sin desbordamiento horizontal.');
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
