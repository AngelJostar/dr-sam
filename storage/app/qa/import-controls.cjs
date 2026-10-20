const assert = require('node:assert/strict');
const { createRequire } = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const { chromium } = rr('playwright');
(async () => {
  const browser = await chromium.launch({ channel: 'msedge', headless: true });
  try {
    const page = await browser.newPage({ viewport: { width: 1380, height: 900 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('http://127.0.0.1:8000/acceso');
    await page.locator('#demo-username').selectOption('proveedor.importacion');
    await Promise.all([page.waitForURL('**/dashboard'), page.getByRole('button', { name: 'Ingresar', exact: true }).click()]);
    let overflowing = 0;
    for (let section = 0; section < 9; section++) {
      await page.goto(`http://127.0.0.1:8000/providers/import?section=${section}&action=0`);
      await page.locator('.provider-import-native-table-scroll').first().scrollIntoViewIfNeeded();
      await page.waitForTimeout(100);
      if (section === 4 || section === 5) {
        const headers = await page.locator('.import-detail-table th .import-column-title').allTextContents();
        assert.deepEqual(headers.slice(0, 3).map(value => value.trim()), ['Paciente', 'Folio/Contrato', 'Proveedor']);
        assert(!headers.some(value => ['Institución', 'Hospital'].includes(value.trim())));
        const filters = page.locator('.import-detail-table .drsam-table-filter-button');
        await filters.nth(1).click();
        let menu = page.getByRole('dialog', { name: 'Filtrar columna', exact: true });
        await menu.locator('[data-filter-all]').uncheck();
        await menu.getByRole('checkbox', { name: 'IMP-2026-001', exact: true }).check();
        await menu.getByRole('button', { name: 'Aceptar', exact: true }).click();
        assert.equal(await page.locator('[data-import-row]:visible').count(), 1);
        assert.equal(await page.locator('[data-import-row]:visible [data-expedient-dashboard]').getAttribute('data-folio'), 'IMP-2026-001');
        await filters.nth(1).click();
        menu = page.getByRole('dialog', { name: 'Filtrar columna', exact: true });
        await menu.locator('[data-filter-all]').check();
        await menu.getByRole('button', { name: 'Aceptar', exact: true }).click();
        assert(await page.locator('.import-detail-table').evaluate(table => [...table.tBodies[0].rows].filter(row => row.hasAttribute('data-import-row')).every(row => row.cells.length === table.tHead.rows[0].cells.length)));
        await page.screenshot({ path: `storage/app/qa/import-section-${section}-without-hospital.png` });
      }
      const state = await page.evaluate(() => {
        const viewport = document.querySelector('.provider-import-native-table-scroll');
        const bar = document.querySelector('.import-bottom-scroll');
        const rect = bar.getBoundingClientRect();
        return { overflow: viewport.scrollWidth > viewport.clientWidth + 1, visible: !bar.hidden, bottom: rect.bottom, height: innerHeight };
      });
      if (state.overflow) {
        overflowing++;
        assert(state.visible, `Section ${section}: bar missing`);
        assert(Math.abs(state.bottom - state.height) < 2, `Section ${section}: bottom ${state.bottom} != ${state.height}`);
        await page.locator('.import-bottom-scroll').first().evaluate(el => { el.scrollLeft = el.scrollWidth; });
        await page.waitForTimeout(60);
        assert(await page.locator('.provider-import-native-table-scroll').first().evaluate(el => Math.abs(el.scrollLeft - (el.scrollWidth - el.clientWidth)) < 3), `Section ${section}: scroll not synchronized`);
        await page.locator('.provider-import-native-table-scroll').first().evaluate(el => { el.scrollLeft = 0; });
        await page.waitForTimeout(60);
        assert(await page.locator('.import-bottom-scroll').first().evaluate(el => el.scrollLeft < 3), `Section ${section}: reverse sync failed`);
        for (const position of [0, 0.5, 1]) {
          await page.evaluate(factor => scrollTo(0, (document.documentElement.scrollHeight - innerHeight) * factor), position);
          await page.waitForTimeout(60);
          const correct = await page.locator('.import-bottom-scroll').first().evaluate(el => el.hidden || Math.abs(el.getBoundingClientRect().bottom - innerHeight) < 2);
          assert(correct, `Section ${section}: bar moved while scrolling`);
        }
      }
      if (section === 3) await page.screenshot({ path: 'storage/app/qa/import-fixed-scroll.png' });
    }
    await page.goto('http://127.0.0.1:8000/providers/import?section=1&action=0');
    await page.getByRole('button', { name: 'Nuevo Expediente', exact: false }).click();
    assert(await page.locator('[data-new-expedient-dialog]').isVisible());
    assert.equal(await page.locator('[data-wizard-step]').count(), 6);
    await page.screenshot({ path: 'storage/app/qa/import-new-expedient.png' });
    await page.keyboard.press('Escape');
    assert(!(await page.locator('[data-new-expedient-dialog]').isVisible()));
    for (const width of [1024, 390]) {
      await page.setViewportSize({ width, height: 844 });
      await page.locator('.provider-import-native-table-scroll').first().scrollIntoViewIfNeeded();
      await page.waitForTimeout(100);
      assert(await page.locator('.import-bottom-scroll').first().evaluate(el => !el.hidden && Math.abs(el.getBoundingClientRect().bottom - innerHeight) < 2));
      const horizontal = await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 2);
      assert(horizontal, `Page overflow at width ${width}`);
    }
    assert.deepEqual(errors, []);
    console.log(`PASS: 9 menus checked (${overflowing} wide tables), fixed bottom, bidirectional scroll, 3 viewport sizes, create dialog and Escape; no JS errors.`);
  } finally { await browser.close(); }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
