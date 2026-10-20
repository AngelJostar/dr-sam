const assert = require('node:assert/strict');
const { createRequire } = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const { chromium } = rr('playwright');
const expected = [
  'Poder Simple (Cofepris)', 'Carta Motivo (Cofepris)', 'Permiso Cofepris',
  'Pantalla de verificación de domicilio localizado',
  'Opinión favorable del SAT reciente, cada inicio de mes solicitarla en adelante',
  'Encargo conferido dado de alta ante el SAT', 'Carta encomienda',
  'Escrito de datos de contacto',
  'Manifestación bajo protesta de decir verdad, artículos 69-B y 49 Bis CFF',
  'Declaración de bienes muebles e inmuebles para desarrollar sus actividades',
  'Sello VUCEM (archivo .KEY, archivo .CER, clave del sello y clave de Web Service)',
  'Escrito de conformidad con la regla 1.4.14',
  'Fotos de la fachada e interior del domicilio declarado',
];
(async () => {
  const browser = await chromium.launch({channel:'msedge',headless:true});
  try {
    const page = await browser.newPage({viewport:{width:1380,height:900}});
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('http://127.0.0.1:8000/acceso');
    await page.locator('#demo-username').selectOption('superadmin');
    await Promise.all([page.waitForURL('**/superadmin'), page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
    for (const section of [1, 6]) {
      await page.goto(`http://127.0.0.1:8000/providers/import?section=${section}&action=0`);
      const trigger = page.locator('[data-expedient-documents][data-folio="IMP-2026-005"][data-document-group="regulatory"]');
      assert.match(await trigger.textContent(), /de 13/);
      await trigger.click();
      const dialog = page.locator('[data-documents-dialog]');
      assert.deepEqual(await dialog.locator('[data-documents-list] tr td:first-child strong').allTextContents(), expected);
      assert.equal(await dialog.locator('.import-document-add:enabled').count(), 13);
      assert.equal(await dialog.locator('.import-document-view').count(), 13);
      assert.equal(await dialog.locator('[data-documents-upload]').count(), 0);
      await page.screenshot({path:`storage/app/qa/regulatory-checklist-${section}-top.png`});
      await dialog.locator('[data-document-key="address_photos"] .import-document-add').scrollIntoViewIfNeeded();
      const bounds = await dialog.boundingBox();
      assert(bounds.y >= 0 && bounds.y + bounds.height <= 901);
      await page.screenshot({path:`storage/app/qa/regulatory-checklist-${section}-bottom.png`});
      await page.keyboard.press('Escape');
    }
    await page.setViewportSize({width:390,height:850});
    await page.locator('[data-expedient-documents][data-folio="IMP-2026-005"][data-document-group="regulatory"]').click();
    const dialog = page.locator('[data-documents-dialog]');
    await dialog.locator('[data-document-key="address_photos"] .import-document-add').scrollIntoViewIfNeeded();
    const bounds = await dialog.boundingBox();
    assert(bounds.x >= 0 && bounds.x + bounds.width <= 391 && bounds.y >= 0 && bounds.y + bounds.height <= 851);
    await page.screenshot({path:'storage/app/qa/regulatory-checklist-mobile.png'});
    assert.deepEqual(errors, []);
    console.log('PASS: 13 nombres y acciones en Expedientes y Administración; listado desplazable y ventana dentro de pantalla en escritorio y móvil.');
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
