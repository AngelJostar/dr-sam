const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { createRequire } = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const { chromium } = rr('playwright');
const nonce = String(Date.now());
const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg==','base64');
(async () => {
  const browser = await chromium.launch({channel:'msedge',headless:true});
  try {
    const page = await browser.newPage({viewport:{width:1380,height:900}});
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('http://127.0.0.1:8000/acceso');
    await page.locator('#demo-username').selectOption('superadmin');
    await Promise.all([page.waitForURL('**/superadmin'),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
    for (const [group,key,total] of [['patient','ine',5],['regulatory','power',13],['logistics','entrustment',2]]) {
      const section = group === 'regulatory' ? 1 : group === 'logistics' ? 5 : 6;
      await page.goto(`http://127.0.0.1:8000/providers/import?section=${section}&action=0`);
      const trigger = page.locator(`[data-expedient-documents][data-folio="IMP-2026-005"][data-document-group="${group}"]`);
      await trigger.click();
      const dialog = page.locator('[data-documents-dialog]');
      assert(await dialog.evaluate(el=>el.open));
      assert.equal(await dialog.locator('[data-documents-list] tr').count(),total);
      assert.equal(await dialog.locator('[data-documents-add], [data-documents-back], [data-documents-upload]').count(), 0);
      assert.deepEqual(await dialog.locator('thead th').allTextContents(), ['Documento', 'Estado', 'Agregar documento', 'Ver documento']);
      assert.equal(await dialog.locator('[data-documents-list] tr .import-document-add').count(), total);
      assert.equal(await dialog.locator('[data-documents-list] tr .import-document-view').count(), total);
      const tableRow = dialog.locator(`[data-document-key="${key}"]`);
      assert(await tableRow.locator('.import-document-view').isDisabled());
      await page.screenshot({path:`storage/app/qa/documents-${group}-columns.png`});
      const [chooser] = await Promise.all([
        page.waitForEvent('filechooser'),
        tableRow.locator('.import-document-add').click(),
      ]);
      assert.equal(await chooser.element().getAttribute('name'), `files[${key}]`);
      await chooser.setFiles([]);
      assert.equal(await dialog.locator('[data-documents-list] input[type=file]').count(),total);
      assert(await dialog.locator('[data-documents-view]').isVisible());
      assert(await dialog.locator('[data-documents-save]').isDisabled());
      const input = dialog.locator(`[name="files[${key}]"]`);
      if(group === 'logistics') {
        await input.setInputFiles({name:'invalid.txt',mimeType:'text/plain',buffer:Buffer.from('unsupported')});
        assert(await dialog.locator('[data-documents-error]').isVisible());
        assert(await dialog.locator('[data-documents-save]').isDisabled());
      }
      const filename = `qa-import-docs-${nonce}-${group}.png`;
      if(group === 'regulatory') {
        await input.locator('..').evaluate((el, file) => {
          const transfer = new DataTransfer();
          transfer.items.add(new File([Uint8Array.from(atob(file.base64), c=>c.charCodeAt(0))],file.name,{type:'image/png'}));
          el.dispatchEvent(new DragEvent('drop',{bubbles:true,cancelable:true,dataTransfer:transfer}));
        },{name:filename,base64:png.toString('base64')});
      } else await input.setInputFiles({name:filename,mimeType:'image/png',buffer:png});
      assert(!(await dialog.locator('[data-documents-save]').isDisabled()));
      const row = input.locator('xpath=ancestor::tr');
      assert((await row.locator('a.import-document-view').getAttribute('href')).startsWith('blob:'));
      assert.equal(await row.locator('.import-document-status').textContent(), 'Por guardar');
      assert(await dialog.locator('[data-documents-view]').isVisible());
      if (group === 'patient') {
        await row.locator('.import-document-clear').click();
        assert(await row.locator('.import-document-view').isDisabled());
        assert(await dialog.locator('[data-documents-save]').isDisabled());
        await input.setInputFiles({name:filename,mimeType:'image/png',buffer:png});
      }
      await page.screenshot({path:`storage/app/qa/documents-${group}-upload.png`});
      await Promise.all([
        page.waitForResponse(r=>r.url().endsWith('/providers/import/expedient-documents') && r.request().method()==='POST' && r.status()===302),
        page.waitForNavigation({waitUntil:'domcontentloaded'}),
        dialog.locator('[data-documents-save]').click()
      ]);
      assert.equal(new URL(page.url()).searchParams.get('section'), String(section));
      await page.reload();
      const saved = JSON.parse(await trigger.getAttribute('data-documents'));
      const file = saved.find(item=>item.key===key);
      assert.equal(file.filename,filename);
      assert(file.url);
      const response = await page.request.get(file.url);
      assert.equal(response.status(),200);
      assert.deepEqual(await response.body(),png);
      assert.equal((await page.request.get(file.url+'?download=1')).status(),200);
      assert((await trigger.textContent()).includes(`de ${total}`));
      await trigger.click();
      assert((await dialog.locator('[data-documents-list]').textContent()).includes(filename));
      assert.equal(await tableRow.locator('a.import-document-view').getAttribute('href'), file.url);
      assert.equal(await tableRow.locator('.import-document-add').textContent(), 'Cambiar');
      await page.screenshot({path:`storage/app/qa/documents-${group}-saved.png`});
      await page.keyboard.press('Escape');
      await page.goto(`http://127.0.0.1:8000/providers/import?section=${section === 1 ? 6 : 1}&action=0`);
      assert(JSON.parse(await trigger.getAttribute('data-documents')).some(item=>item.filename===filename));
      await trigger.click();
      assert.equal(await page.locator('[data-documents-add]').count(), 0);
      assert.equal(await dialog.locator('.import-document-add:enabled').count(), total);
      await page.keyboard.press('Escape');
      for (const specialistSection of [4, 5]) {
        await page.goto(`http://127.0.0.1:8000/providers/import?section=${specialistSection}&action=0`);
        if (group === 'regulatory' && specialistSection === 4) {
          assert.equal(await trigger.count(), 0);
          continue;
        }
        assert(JSON.parse(await trigger.getAttribute('data-documents')).some(item=>item.filename===filename));
        await trigger.click();
        const editable = group === 'logistics';
        assert.equal(await dialog.locator('input[type=file]').count(), editable ? total : 0);
        assert.equal(await dialog.locator('.import-document-add, .import-document-clear').count(), editable ? total * 2 : 0);
        assert.equal(await dialog.locator('[data-documents-save]').isVisible(), editable);
        assert.deepEqual(await dialog.locator('thead th:visible').allTextContents(), editable
          ? ['Documento', 'Estado', 'Agregar documento', 'Ver documento']
          : ['Documento', 'Estado', 'Ver documento']);
        assert.equal(await tableRow.locator('a.import-document-view').getAttribute('href'), file.url);
        assert.equal(await tableRow.locator('.import-document-status').textContent(), 'Cargado');
        await page.screenshot({path:`storage/app/qa/documents-section-${specialistSection}-${group}.png`});
        await page.keyboard.press('Escape');
      }
    }
    await page.goto('http://127.0.0.1:8000/providers/import?section=6&action=0');
    await page.setViewportSize({width:390,height:850});
    await page.locator('[data-expedient-documents][data-folio="IMP-2026-005"][data-document-group="logistics"]').click();
    const dialog = page.locator('[data-documents-dialog]');
    await page.screenshot({path:'storage/app/qa/documents-mobile-columns.png'});
    assert.equal(await dialog.locator('[data-documents-add], [data-documents-upload]').count(), 0);
    const rect = await dialog.boundingBox();
    assert(rect.x>=0 && rect.x+rect.width<=391);
    await page.screenshot({path:'storage/app/qa/documents-mobile-upload.png'});
    await page.keyboard.press('Escape');
    assert(!(await dialog.evaluate(el=>el.open)));
    assert.deepEqual(errors,[]);
    console.log('PASS: carga real de paciente, Regulatorio y Logística; arrastre, validación, vista previa, guardado, recarga, consulta y descarga. Vista móvil verificada.');
  } finally {
    await browser.close();
    console.log(execFileSync('C:/laragon/bin/php/php-8.3.13-Win32-vs16-x64/php.exe',['storage/app/qa/cleanup-import-document-check.php',nonce],{cwd:'C:/laragon/www/dr-sam',encoding:'utf8'}).trim());
  }
})().catch(error=>{console.error(error);process.exitCode=1;});
