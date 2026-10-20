const assert = require('node:assert/strict');
const {createRequire} = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const {chromium} = rr('playwright');
(async () => {
  const browser = await chromium.launch({channel:'msedge',headless:true});
  try {
    const page = await browser.newPage({viewport:{width:1400,height:900}});
    await page.addInitScript(() => addEventListener('pagereveal', event => {
      if (!event.viewTransition) return;
      window.__qaTransition = {active:true};
      event.viewTransition.ready.then(() => {
        window.__qaTransition.started = performance.now();
        return event.viewTransition.finished;
      }).then(() => {window.__qaTransition.duration = performance.now() - window.__qaTransition.started;});
    }));
    const errors = [];
    page.on('pageerror',e=>errors.push(e.message));
    await page.goto('http://127.0.0.1:8000/acceso');
    await page.locator('#demo-username').selectOption('superadmin');
    await Promise.all([page.waitForURL('**/superadmin'),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
    const links = await page.locator('.superadmin-native-menu a').evaluateAll(els=>els.map(el=>el.href));
    for (const [index,url] of links.entries()) {
      await page.goto(url);
      await page.waitForLoadState('networkidle');
      const sizes = await page.evaluate(() => {
        const rect = s => {const r=document.querySelector(s).getBoundingClientRect();return {x:r.x,y:r.y,width:r.width,height:r.height};};
        return {sidebar:rect('.superadmin-native-sidebar'), header:rect('.superadmin-native-header'), workspace:rect('.superadmin-native-workspace'),
          menuHeights:[...document.querySelectorAll('.superadmin-native-menu a')].map(el=>el.getBoundingClientRect().height),
          overflow:document.documentElement.scrollWidth>innerWidth+1,
          transition:getComputedStyle(document.querySelector('.superadmin-native-workspace')).viewTransitionName,
          controls:[...document.querySelectorAll('.superadmin-native-catalog-filters input,.superadmin-native-catalog-filters select,.superadmin-native-catalog-filters button,.superadmin-native-csv')].map(el=>el.getBoundingClientRect().height)};
      });
      assert.equal(sizes.sidebar.width,224,url);
      assert.equal(sizes.sidebar.height,900,url);
      assert.equal(sizes.workspace.height,900,url);
      assert.equal(sizes.header.y,20,url);
      assert.equal(sizes.header.height,160,url);
      assert(sizes.menuHeights.every(h=>h===56),url);
      assert(sizes.controls.every(h=>h===40),url);
      assert(!sizes.overflow,url);
      assert.equal(sizes.transition,'superadmin-content');
      if(index===0 || /\/(specialties|doctors|medications)$/.test(url)) await page.screenshot({path:`storage/app/qa/admin-unified-${index}.png`});
    }
    await page.goto('http://127.0.0.1:8000/superadmin');
    await page.waitForLoadState('networkidle');
    const menu = page.locator('.superadmin-native-menu'), content=page.locator('.superadmin-native-workspace');
    await menu.evaluate(el=>el.scrollTop=0);
    await content.evaluate(el=>el.scrollTop=0);
    const logoY = await page.locator('.superadmin-native-brand').evaluate(el=>el.getBoundingClientRect().y);
    await menu.hover(); await page.mouse.wheel(0,450);
    await page.waitForFunction(()=>document.querySelector('.superadmin-native-menu').scrollTop>100);
    assert.equal(await content.evaluate(el=>el.scrollTop),0);
    const menuTop = await menu.evaluate(el=>el.scrollTop);
    await content.hover(); await page.mouse.wheel(0,600);
    await page.waitForFunction(()=>document.querySelector('.superadmin-native-workspace').scrollTop>100);
    assert.equal(await menu.evaluate(el=>el.scrollTop),menuTop);
    assert.equal(await page.evaluate(()=>window.scrollY),0);
    assert.equal(await page.locator('.superadmin-native-brand').evaluate(el=>el.getBoundingClientRect().y),logoY);
    await page.screenshot({path:'storage/app/qa/admin-independent-scroll.png'});
    const target = page.locator('.superadmin-native-menu a[href$="/specialties"]');
    await Promise.all([page.waitForURL('**/specialties'),target.click()]);
    await page.waitForLoadState('networkidle');
    assert.equal(await menu.evaluate(el=>el.scrollTop),menuTop);
    await page.waitForFunction(()=>window.__qaTransition?.duration>=450);
    await page.emulateMedia({reducedMotion:'reduce'});
    assert.equal(await menu.locator('a').first().evaluate(el=>getComputedStyle(el).transitionDuration),'0s');
    await page.setViewportSize({width:390,height:844});
    assert.notEqual(await page.locator('body').evaluate(el=>getComputedStyle(el).overflowY),'hidden');
    assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));
    await page.screenshot({path:'storage/app/qa/admin-unified-mobile.png'});
    assert.deepEqual(errors,[]);
    console.log(`PASS: ${links.length} secciones homologadas; dimensiones y controles uniformes, scroll independiente con rueda y menú conservado al navegar, transición gradual verificada, móvil y movimiento reducido.`);
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
