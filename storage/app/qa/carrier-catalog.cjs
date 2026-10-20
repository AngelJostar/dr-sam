const assert = require('node:assert/strict');
const fs = require('node:fs');
const {createRequire} = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
(async () => {
  const browser = await rr('playwright').chromium.launch({channel:'msedge',headless:true,ignoreDefaultArgs:['--hide-scrollbars']});
  const page = await browser.newPage({viewport:{width:1440,height:900},reducedMotion:'reduce'});
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));
  try {
    await page.goto('http://127.0.0.1:8000/acceso');
    await page.locator('#demo-username').selectOption('superadmin');
    await Promise.all([page.waitForURL(u=>!u.pathname.includes('/acceso')),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
    await page.goto('http://127.0.0.1:8000/superadmin/catalogo/insurance-carriers?view=carriers&status=all');
    assert.equal(await page.locator('.admin-insurance-carrier-table thead th').count(),10);
    await page.screenshot({path:'storage/app/qa/carriers-live.png'});
    for (const status of ['all','active','inactive']) {
      await page.route('**/superadmin/catalogo/insurance-carriers?view=carriers&status='+status, route=>route.fulfill({contentType:'text/html',body:fs.readFileSync('storage/app/qa/carriers-'+status+'.html','utf8')}));
    }
    for (const [status,count] of [['all',3],['active',2],['inactive',1]]) {
      await page.goto('http://127.0.0.1:8000/superadmin/catalogo/insurance-carriers?view=carriers&status='+status);
      await page.locator('.admin-insurance-carrier-table').waitFor();
      await page.evaluate(()=>document.fonts.ready);
      assert.equal(await page.locator('[data-insurance-carrier-id]').count(),count);
      const dimensions = await page.evaluate(()=>{
        const card=document.querySelector('.admin-insurance-carriers').getBoundingClientRect();
        const table=document.querySelector('.admin-insurance-carrier-table').getBoundingClientRect();
        const scroller=document.querySelector('.admin-insurance-carriers .superadmin-native-catalog-scroll').getBoundingClientRect();
        return {bottom:card.bottom, viewport:innerHeight,tableWidth:table.width,canvasWidth:scroller.width,rowHeight:document.querySelector('[data-insurance-carrier-id]').getBoundingClientRect().height};
      });
      assert(dimensions.viewport-dimensions.bottom<25);
      assert(dimensions.tableWidth >= dimensions.canvasWidth-2);
      assert(dimensions.rowHeight<75);
      assert.equal(await page.locator('[data-admin-filter="'+status+'"][aria-current=true]').count(),1);
      assert.equal(await page.locator('[data-admin-filter=carriers][aria-current=true]').count(),1);
      await page.screenshot({path:'storage/app/qa/carriers-'+status+'.png'});
      console.log(status,JSON.stringify(dimensions));
      const edit = page.locator('[data-insurance-carrier-id]').first().getByRole('link',{name:/^Editar/});
      await edit.click();
      assert(await page.locator('.superadmin-user-modal:target').isVisible());
      await page.locator('.superadmin-user-modal:target header a').click();
      assert.equal(await page.locator('.superadmin-user-modal:target').count(),0);
    }
    await page.setViewportSize({width:900,height:650});
    await page.goto('http://127.0.0.1:8000/superadmin/catalogo/insurance-carriers?view=carriers&status=all');
    await page.locator('.admin-insurance-carrier-table').waitFor();
    const scroll = page.locator('.admin-insurance-carriers .superadmin-native-catalog-scroll');
    assert(await scroll.evaluate(e=>e.scrollWidth>e.clientWidth));
    await scroll.evaluate(e=>{e.scrollLeft=e.scrollWidth});
    await page.screenshot({path:'storage/app/qa/carriers-900.png'});
    await page.setViewportSize({width:1440,height:900});
    await page.unrouteAll();
    for(const category of ['gmm','life']) {
      await page.goto('http://127.0.0.1:8000/superadmin/catalogo/insurance-carriers?view='+category);
      assert(await page.locator('.admin-insurance-table').isVisible());
    }
    assert.deepEqual(errors,[]);
    console.log('Carrier layouts, modals, compact scrolling and plan views passed.');
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
