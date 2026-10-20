const assert = require('node:assert/strict');
const { createRequire } = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
(async () => {
  const browser = await rr('playwright').chromium.launch({ channel: 'msedge', headless: true, ignoreDefaultArgs: ['--hide-scrollbars'] });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' });
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const root = 'http://127.0.0.1:8000';
  try {
    await page.goto(root + '/acceso');
    await page.locator('#demo-username').selectOption('superadmin');
    await Promise.all([page.waitForURL(url => !url.pathname.includes('/acceso')), page.getByRole('button', { name: 'Ingresar', exact: true }).click()]);
    const sections = ['services', 'catalog', 'users', 'patients', 'doctors', 'specialties', 'external-pharmacy', 'procedure-areas', 'medications'];
    for (const width of [1440, 900, 390]) {
      await page.setViewportSize({ width, height: 900 });
      let reference;
      for (const section of sections) {
        assert.equal((await page.goto(root + '/unit?section=' + section)).status(), 200);
        await page.evaluate(() => document.fonts.ready);
        const measurements = await page.evaluate(() => {
          const visible = node => node.getClientRects().length > 0;
          const size = node => { const s = getComputedStyle(node); return [s.width, s.height, s.fontSize]; };
          const box = document.querySelector('.unit-service-carousel-card').getBoundingClientRect();
          return {
            carousel: [box.x, box.y, box.height],
            tiles: [...document.querySelectorAll('.unit-service-carousel-button')].filter(visible).map(size),
            menus: [...document.querySelectorAll('.unit-native-menu a')].map(node => getComputedStyle(node).height),
            tabs: [...document.querySelectorAll('.unit-nutrition-request-tabs button, .unit-consultation-submenu-button')].filter(visible).map(size),
            overflow: document.documentElement.scrollWidth > innerWidth + 1,
            clippedLabels: [...document.querySelectorAll('.unit-service-carousel-button strong')].filter(visible).filter(node => node.scrollHeight > node.clientHeight + 1).length
          };
        });
        reference ??= measurements.carousel;
        assert.deepEqual(measurements.carousel, reference, `${section} carousel position at ${width}`);
        measurements.tiles.forEach(size => assert.deepEqual(size.slice(0, 2), ['140px', '80px'], `${section} tile at ${width}`));
        measurements.menus.forEach(height => assert.equal(height, '60px', `${section} menu`));
        measurements.tabs.forEach(size => { assert(Math.abs(parseFloat(size[0]) - 144) < .1, `${section} tab width at ${width}: ${size[0]}`); assert.deepEqual(size.slice(1), ['40px', '12px'], `${section} tab at ${width}`); });
        assert.equal(measurements.overflow, false, `${section} overflow at ${width}`);
        assert.equal(measurements.clippedLabels, 0, `${section} clipped labels`);
        if (width === 1440 && ['services', 'procedure-areas', 'patients'].includes(section)) await page.screenshot({ path: `storage/app/qa/unit-uniform-${section}.png` });
        if (width === 390 && section === 'patients') await page.screenshot({ path: 'storage/app/qa/unit-uniform-mobile.png' });
      }
      console.log(`All Unit menu and carousel dimensions match at ${width}px.`);
    }
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(root + '/unit?section=patients');
    for (const view of ['record', 'pre-admission', 'admissions', 'transfers-discharges', 'all', 'active', 'inactive', 'all']) {
      const button = page.locator(`[data-unit-carousel-filter="${view}"]`);
      await button.click();
      const panel = ['all', 'active', 'inactive'].includes(view) ? 'catalog' : view;
      assert.equal(await button.getAttribute('aria-pressed'), 'true');
      assert.equal(await page.locator('[data-unit-patient-panel]:visible').count(), 1);
      assert(await page.locator(`[data-unit-patient-panel="${panel}"]`).isVisible());
      if (panel === 'catalog') assert.equal(await page.locator(`[data-unit-catalog-status="${view}"]`).getAttribute('aria-selected'), 'true');
      else {
        const filters = page.locator(`[data-unit-patient-panel="${panel}"] .drsam-table-filter-button`);
        assert.equal(await filters.count(), 6, `${panel} column filters`);
        for (const filter of await filters.all()) {
          await filter.click();
          assert.equal(await filter.getAttribute('aria-expanded'), 'true');
          const menu = page.getByRole('dialog', { name: 'Filtrar columna' });
          assert(await menu.isVisible());
          assert(await menu.getByText('No hay valores disponibles.').isVisible());
          assert(await menu.getByRole('button', { name: 'Aceptar', exact: true }).isDisabled());
          await menu.getByRole('button', { name: 'Cancelar', exact: true }).click();
        }
      }
    }
    await page.locator('[data-unit-catalog-search]').fill('sin-coincidencias-de-verificacion');
    assert.equal(await page.locator('[data-unit-catalog-row]:visible').count(), 0);
    await page.locator('[data-unit-catalog-search]').fill('');
    await page.screenshot({ path: 'storage/app/qa/unit-patients-new-carousel.png' });
    await page.locator('[data-unit-carousel-filter="admissions"]').click();
    await page.screenshot({ path: 'storage/app/qa/unit-patient-admissions.png' });
    await page.locator('[data-unit-patient-panel="admissions"] .drsam-table-filter-button').first().click();
    await page.screenshot({ path: 'storage/app/qa/unit-patient-admissions-filter.png' });
    await page.goto(root + '/unit?section=procedure-areas');
    await page.locator('[data-unit-carousel-filter=infusion]').click();
    assert.equal(await page.locator('[data-procedure-category=infusion]').getAttribute('aria-selected'), 'true');
    await page.goto(root + '/unit?section=services');
    for (const tab of await page.locator('[data-unit-service-tab]').all()) {
      await tab.click();
      assert(await page.locator(`[data-unit-service-panel="${await tab.getAttribute('data-unit-service-tab')}"]`).isVisible());
    }
    assert.deepEqual(errors, []);
    console.log('Filters, service navigation and JavaScript checks passed.');
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
