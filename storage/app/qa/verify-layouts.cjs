const assert = require('node:assert/strict');
const { createRequire } = require('node:module');
const requireRuntime = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const { chromium } = requireRuntime('playwright');
(async () => {
  const browser = await chromium.launch({ channel: 'msedge', headless: true });
  try {
    const page = await browser.newPage({viewport: {width: 1380, height: 900}});
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('http://127.0.0.1:8000/acceso');
    await page.locator('#demo-username').selectOption('unidad.demo');
    await Promise.all([page.waitForURL('**/dashboard'), page.getByRole('button', {name: 'Ingresar', exact: true}).click()]);
    for (const width of [1380, 1100, 1024, 768, 390]) {
      await page.setViewportSize({width, height: 900});
      const sections = {};
      for (const section of ['services', 'users']) {
        await page.goto('http://127.0.0.1:8000/unit?section=' + section);
        await page.locator('.klini-carousel').waitFor();
        await page.evaluate(() => document.fonts.ready);
        sections[section] = await page.evaluate(() => {
          const rect = selector => {
            const el = [...document.querySelectorAll(selector)].find(el => el.getBoundingClientRect().height > 0);
            if (!el) return null;
            const {x,y,width,height} = el.getBoundingClientRect();
            return {x,y,width,height};
          };
          return {carousel: rect('.unit-service-carousel-card'), header:rect('.unit-service-panel:not([hidden]) > .unit-service-panel-header, .unit-catalog-info-card'), toolbar:rect('.unit-consultation-submenu-card'), overflow: document.documentElement.scrollWidth > innerWidth};
        });
        if (width === 1380) await page.screenshot({path: `storage/app/qa/unit-${section}-after.png`});
      }
      assert.deepEqual(sections.services, sections.users, 'Unit layout mismatch at '+width); console.log('unit', width, 'aligned');
    }
    await page.setViewportSize({width: 1380, height: 900});
    await page.goto('http://127.0.0.1:8000/unit?section=users');
    const totalRows = await page.locator('[data-operational-user-row]:visible').count();
    await page.locator('[data-unit-carousel-filter="inactive"]').click();
    assert.equal(await page.locator('[data-operational-user-row]:visible').count(), 0);
    assert.equal(await page.locator('[data-unit-catalog-status="inactive"]').getAttribute('aria-selected'), 'true');
    await page.locator('[data-unit-catalog-status="all"]').click();
    assert.equal(await page.locator('[data-operational-user-row]:visible').count(), totalRows);
    await page.locator('[data-unit-catalog-search]').fill('unmatched-user-qa');
    assert.equal(await page.locator('[data-operational-user-row]:visible').count(), 0);
    await page.locator('[data-unit-catalog-search]').fill('');
    await page.locator('[data-edit-operational-user]').first().click();
    assert.equal(await page.locator('[data-operational-form-title]').textContent(), 'Editar usuario operativo');
    await page.locator('[data-operational-reset]').click();
    assert.equal(await page.locator('[data-operational-form-title]').textContent(), 'Alta de usuario operativo');
    await page.goto('http://127.0.0.1:8000/');
    for (const width of [320,360,375,390,430,620,768,1380]) {
      await page.setViewportSize({width, height: 850});
      await page.evaluate(() => document.fonts.ready);
      const positions = await page.evaluate(() => {
        const rect = selector => { const el = document.querySelector(selector); const {x,y,width,height} = el.getBoundingClientRect(); return {x,y,width,height,center:y+height/2}; };
        return {brand:rect('.header .brand'),actions:rect('.nav-actions'),signup:rect('.header-signup'), overflow: document.documentElement.scrollWidth > innerWidth};
      });
      assert(positions.signup.x + positions.signup.width <= width - (width <= 620 ? 15 : 0), 'Signup exceeds header at '+width); console.log('landing', width, 'aligned');
      assert(Math.abs(positions.brand.center - positions.actions.center) <= 1, 'Header controls are not aligned at '+width);
      assert(!positions.overflow, 'Horizontal overflow at '+width);
      if (width === 390) await page.screenshot({path:'storage/app/qa/klini-mobile-header-after.png'});
    }
    await page.setViewportSize({width:390,height:850});
    await page.locator('.mobile-menu').click();
    await page.locator('.mobile-login').click();
    assert(await page.locator('#login-dialog').isVisible());
    await page.locator('#login-dialog .dialog-close').click(); await page.waitForFunction(() => document.activeElement === document.querySelector('.mobile-menu')); assert(await page.locator('.mobile-menu').evaluate(el => el === document.activeElement));
    await page.locator('.header-signup').click();
    assert(await page.locator('#signup-dialog').isVisible());
    await page.locator('#signup-dialog .dialog-close').click();
    const imports = await browser.newPage({viewport:{width:1380,height:900}});
    await imports.goto('http://127.0.0.1:8000/acceso');
    await imports.locator('#demo-username').selectOption('proveedor.importacion');
    await Promise.all([imports.waitForURL('**/dashboard'), imports.getByRole('button', {name:'Ingresar',exact:true}).click()]);
    for(let section=0;section<9;section++) {
      await imports.goto('http://127.0.0.1:8000/providers/import?section='+section+'&action=0');
      assert.equal(await imports.locator('.import-section-banner, .provider-import-native-topbar').count(),0);
      assert(await imports.locator('.import-action-carousel').isVisible());
      assert(await imports.locator('[data-import-panel]').isVisible());
      if(section===1) await imports.screenshot({path:'storage/app/qa/import-without-header-after.png'});
    }
    assert.equal(errors.length,0, errors.join('\n'));
    console.log('Interactions and all nine import menus passed.');
  } finally { await browser.close(); }
})().catch(error => {console.error(error.stack); process.exitCode=1;});


