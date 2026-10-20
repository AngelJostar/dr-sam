const assert = require('node:assert/strict');
const {createRequire} = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const {chromium} = rr('playwright');
(async () => {
  const browser = await chromium.launch({channel:'msedge', headless:true, ignoreDefaultArgs:['--hide-scrollbars']});
  try {
    const context = await browser.newContext({viewport:{width:1536,height:1024}, reducedMotion:'reduce'});
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', e => errors.push(e.message));
    await page.goto('http://127.0.0.1:8000/acceso');
    await page.locator('#demo-username').selectOption('superadmin');
    await Promise.all([page.waitForURL('**/superadmin'), page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
    await page.waitForLoadState('networkidle');
    await page.screenshot({path:'storage/app/qa/superadmin-reference-desktop.png'});
    assert.equal(await page.locator('.superadmin-native-module-card').count(),9);
    const dimensions = await page.evaluate(() => ({
      zoom:getComputedStyle(document.body).zoom,
      topbar:document.querySelector('.superadmin-native-topbar').getBoundingClientRect().height,
      sidebar:document.querySelector('.superadmin-native-sidebar').getBoundingClientRect().width,
      grid:getComputedStyle(document.querySelector('.superadmin-native-module-grid')).gridTemplateColumns,
      overflow:document.documentElement.scrollWidth > innerWidth + 1
    }));
    assert.equal(dimensions.zoom,'1');
    assert(!dimensions.overflow);
    for (const filter of ['metrics','modules','activity','all']) {
      await page.locator(`button[data-admin-filter="${filter}"]`).click();
      const visible = await page.locator('[data-admin-filter-panel]:visible').evaluateAll(es => es.map(e => e.dataset.adminFilterPanel));
      assert(filter === 'all' ? visible.length === 3 : visible.length === 1 && visible[0] === filter);
    }
    await page.getByRole('link',{name:'Página siguiente',exact:true}).click();
    assert.equal(new URL(page.url()).searchParams.get('page'),'2');
    assert.equal(await page.locator('.superadmin-native-module-card').count(),9);
    await page.goto('http://127.0.0.1:8000/superadmin/catalogo/users');
    await page.locator('a[data-admin-filter="klini"]').click();
    await page.waitForLoadState('networkidle');
    assert.equal(new URL(page.url()).searchParams.get('category'),'klini');
    assert.equal(await page.locator('a[data-admin-filter="klini"]').getAttribute('aria-current'),'true');
    assert((await page.locator('.superadmin-native-catalog-table').innerText()).includes('KLI-'));
    await page.screenshot({path:'storage/app/qa/superadmin-klini-users.png'});
    await page.locator('a[data-admin-filter="operational"]').click();
    assert(!(await page.locator('.superadmin-native-catalog-table').innerText()).includes('KLI-'));
    await page.locator('a[data-admin-filter="all"]').click();
    assert(!new URL(page.url()).searchParams.has('category'));
    const links = await page.locator('.superadmin-native-menu a').evaluateAll(es=>es.map(e=>e.href));
    for(const url of links) {
      await page.goto(url); await page.waitForLoadState('networkidle');
      assert.equal(await page.locator('[data-admin-carousel]').count(),1,url);
      assert.equal(await page.locator('.superadmin-native-topbar').count(),1,url);
      assert(!(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1)),url+' horizontal overflow');
    }
    await page.goto('http://127.0.0.1:8000/superadmin/catalogo/users');
    const rail=page.locator('.superadmin-bottom-scroll');
    await page.waitForLoadState('networkidle');
    assert(await rail.isVisible());
    assert(Math.abs((await rail.boundingBox()).y+(await rail.boundingBox()).height-1024)<1);
    await rail.evaluate(e=>e.scrollLeft=e.scrollWidth);await page.waitForTimeout(100);
    assert(await page.locator('.superadmin-native-catalog-scroll').first().evaluate(e=>Math.abs(e.scrollLeft-(e.scrollWidth-e.clientWidth))<2));
    await page.setViewportSize({width:390,height:844});
    await page.goto('http://127.0.0.1:8000/superadmin');await page.waitForLoadState('networkidle');
    assert(!(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1)));
    await page.screenshot({path:'storage/app/qa/superadmin-reference-mobile.png',fullPage:true});
    await page.goto('http://127.0.0.1:8000/superadmin/catalogo/users?category=klini');
    await page.waitForLoadState('networkidle');
    assert(!(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1)));
    const guest = await browser.newPage({viewport:{width:1366,height:900},reducedMotion:'reduce'});
    guest.on('pageerror',e=>errors.push(e.message));
    await guest.goto('http://127.0.0.1:8000/');
    await guest.locator('[data-signup]').first().click();
    assert(await guest.locator('#signup-dialog').isVisible());
    assert(!(await guest.locator('#signup-doctor-fields').isVisible()));
    await guest.locator('#signup-type').selectOption('doctor');
    assert(await guest.locator('#signup-doctor-fields').isVisible());
    await guest.screenshot({path:'storage/app/qa/klini-registration-desktop.png'});
    await guest.setViewportSize({width:390,height:844});
    await guest.screenshot({path:'storage/app/qa/klini-registration-mobile.png'});
    assert(await guest.locator('#signup-dialog').evaluate(e=>e.scrollWidth<=e.clientWidth+1));
    await guest.locator('#signup-type').selectOption('patient');
    assert(await guest.locator('#signup-license').isDisabled());
    assert.deepEqual(errors,[]);
    console.log(JSON.stringify({dimensions,menus:links.length,checks:'filters, pagination, member IDs, fixed scroll, desktop/mobile registration',errors}));
  } finally { await browser.close(); }
})().catch(e=>{console.error(e);process.exitCode=1;});
