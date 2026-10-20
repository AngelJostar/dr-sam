const assert = require('node:assert/strict');
const { createRequire } = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const { chromium } = rr('playwright');
function contrast(a,b) {
  const luminance = c => c.match(/[\d.]+/g).slice(0,3).map(Number).map(n => n / 255).map(v => v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4).reduce((s,v,i) => s + v * [.2126,.7152,.0722][i],0);
  const [x,y] = [luminance(a),luminance(b)].sort((a,b) => b-a);
  return (x+.05)/(y+.05);
}
(async () => {
  const browser = await chromium.launch({ channel: 'msedge', headless: true });
  try {
    const page = await browser.newPage({ viewport: { width: 1380, height: 900 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('http://127.0.0.1:8000/acceso');
    await page.locator('#demo-username').selectOption('superadmin');
    await Promise.all([page.waitForURL('**/superadmin'), page.getByRole('button', {name:'Ingresar',exact:true}).click()]);
    let total = 0;
    for(let action = 0; action < 5; action++) {
      const response = await page.goto(`http://127.0.0.1:8000/providers/import?section=7&action=${action}`);
      assert.equal(response.status(), 200);
      assert.deepEqual((await page.locator('.import-carousel-item').allTextContents()).map(v => v.replace(/En uso|→/g,'').trim()), ['Directorio','Proveedores','Regulatorio','Logística','Administración']);
      const headers = (await page.locator('.import-column-title').allTextContents()).map(v => v.trim());
      assert.deepEqual(headers, ['Clave','Usuario','Nombre','Correo','Rol','Área','Estatus','Acciones']);
      const count = await page.locator('[data-import-row]').count();
      if(action === 0) { total = count; assert(count > 5); }
      else assert(count <= total);
      assert.equal(await page.locator('[data-new-supplier]').count(),0);
      if(count) {
        await page.locator('[data-import-open]').first().click();
        assert(await page.locator('[data-import-dialog]').evaluate(e => e.open));
        await page.keyboard.press('Escape');
      }
      await page.locator('[data-new-import-user]').click();
      const form = page.locator('[data-new-import-user-dialog]');
      assert(await form.evaluate(e => e.open));
      if(action) assert.equal(await form.locator('[name=import_area]').inputValue(), ['providers','regulatory','logistics','administration'][action-1]);
      assert.equal(await form.locator('[name=password]').inputValue(),'');
      await form.getByRole('button', {name:'Guardar usuario',exact:true}).click();
      assert(await form.evaluate(e => e.open));
      assert(await form.locator('[name=name]').evaluate(e => e.validity.valueMissing));
      await page.keyboard.press('Escape');
    }
    await page.goto('http://127.0.0.1:8000/providers/import?section=7&action=0');
    for (let i = 0; i < 9; i++) {
      const link = page.locator('.provider-import-native-menu [data-import-navigate]').nth(i);
      await link.hover();
      await page.waitForTimeout(240);
      const colors = await link.evaluate(el => ({fg:getComputedStyle(el.querySelector('span') || el).color,bg:getComputedStyle(el).backgroundColor}));
      assert(contrast(colors.fg,colors.bg) >= 4.5, JSON.stringify({i,colors}));
    }
    const target = page.locator('.provider-import-native-menu [data-import-navigate]').nth(1);
    await target.hover();
    await page.waitForTimeout(300);
    await page.screenshot({path:'storage/app/qa/import-menu-hover-fixed.png'});
    await page.mouse.move(1000,90);
    await target.focus();
    await page.keyboard.press('Tab');
    const focused = page.locator('.provider-import-native-menu [data-import-navigate]:focus');
    await page.waitForTimeout(250);
    const focusColors = await focused.evaluate(el => ({fg:getComputedStyle(el).color,bg:getComputedStyle(el).backgroundColor}));
    assert(contrast(focusColors.fg,focusColors.bg)>=4.5);
    for(const width of [1380, 390]) {
      await page.setViewportSize({width,height:850});
      await page.locator('[data-new-import-user]').click();
      const dialog = page.locator('[data-new-import-user-dialog]');
      const bounds = await dialog.boundingBox();
      assert(bounds.x >= 0 && bounds.x+bounds.width <= width+1);
      const closeBounds = await dialog.locator('header button').boundingBox();
      assert(closeBounds.width <= 40);
      await page.screenshot({path:`storage/app/qa/import-new-user-${width}.png`});
      await page.keyboard.press('Escape');
    }
    assert.deepEqual(errors,[]);
    const readTable = async section => {
      await page.setViewportSize({width:1380,height:900});
      await page.goto(`http://127.0.0.1:8000/providers/import?section=${section}&action=0`);
      return page.locator('.import-detail-table').evaluate(table => ({
        headers: [...table.querySelectorAll('th .import-column-title')].map(el => el.textContent.trim()),
        rows: [...table.querySelectorAll('[data-import-row]')].map(tr => [...tr.cells].map(td => td.textContent.trim())),
        actions: [...table.querySelectorAll('[data-expedient-documents], [data-expedient-dashboard]')].map(el => [el.dataset.documentTitle, el.dataset.folio, el.dataset.documents])
      }));
    };
    const original = await readTable(1);
    assert.deepEqual(await readTable(6), original);
    assert(original.rows.length > 2);
    await page.locator('[data-expedient-documents]').first().click();
    assert(await page.locator('[data-documents-dialog]').evaluate(el => el.open));
    await page.keyboard.press('Escape');
    await page.locator('.provider-import-native-table-scroll').evaluate(el => {el.scrollLeft = 0;});
    await page.screenshot({path:'storage/app/qa/import-administration-matched.png'});
    assert.deepEqual(errors,[]);
    console.log('PASS: 5 grupos reales, formulario, permisos de presentación y contraste de 9 botones; escritorio y móvil.');
    console.log('PASS: Administración y Expedientes tienen las mismas columnas, registros y acciones de documentos.');
  } finally { await browser.close(); }
})().catch(e => {console.error(e);process.exitCode=1;});
