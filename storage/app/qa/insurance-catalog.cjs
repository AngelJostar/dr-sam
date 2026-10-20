const assert = require('node:assert/strict');
const fs = require('node:fs');
const {createRequire} = require('node:module');
const rr = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
(async () => {
  const browser = await rr('playwright').chromium.launch({channel:'msedge',headless:true,ignoreDefaultArgs:['--hide-scrollbars']});
  const created = [], errors = [], base = 'http://127.0.0.1:8000';
  const marker = 'QA Insurance ' + Date.now();
  async function login(page, user) {
    await page.goto(base+'/acceso'); await page.locator('#demo-username').selectOption(user);
    await Promise.all([page.waitForURL(u=>!u.pathname.includes('/acceso')),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
    page.on('pageerror',e=>errors.push(e.message));
  }
  async function feedback(page) { await page.locator('[data-admin-feedback-close]').click(); }
  try {
    const admin = await browser.newPage({viewport:{width:1440,height:900},reducedMotion:'reduce'});
    await login(admin,'superadmin');
    await admin.goto(base+'/superadmin/catalogo/users');
    assert((await admin.locator('th').allTextContents()).includes('Usuario del sistema'));
    assert((await admin.locator('th').allTextContents()).includes('Contraseña'));
    await admin.screenshot({path:'storage/app/qa/users-access-columns.png'});
    await admin.goto(base+'/superadmin/catalogo/insurance-carriers');
    for (const category of ['gmm','life']) {
      await admin.locator(`[data-admin-filter="${category}"]`).click();
      await admin.getByRole('link',{name:'+ Nuevo seguro',exact:true}).click();
      const form = admin.locator('#create-insurance-plan');
      await form.locator('[name=name]').fill(marker+' '+category);
      if(category==='gmm') {
        await form.locator('[name=monthly_premium]').fill('1532.45');
        await form.locator('[name=insured_amount]').fill('2500000');
        await form.locator('[name=deductible]').fill('23000');
        await form.locator('[name=coinsurance]').fill('12');
        await form.locator('[name="details[addons][dental][price]"]').fill('120.50');
      }
      await form.locator('[name="details[coverages]"]').fill(category==='gmm'?'Hospitalización QA\nAmbulancia QA':'Fallecimiento QA');
      await form.locator('[name="details[conditions]"]').fill('Condiciones QA del catálogo');
      await form.locator('[name="details[term]"]').fill('Un año QA');
      await form.getByRole('button',{name:'Guardar seguro',exact:true}).click();
      await feedback(admin);
      const row = admin.locator('[data-insurance-plan-id]').filter({hasText:marker+' '+category});
      const id = Number(await row.getAttribute('data-insurance-plan-id')); created.push({id,name:marker+' '+category});
      fs.writeFileSync('storage/app/qa/insurance-catalog-fixtures.json',JSON.stringify(created));
      await row.getByRole('button',{name:'Vincular a sección de Mi seguro',exact:true}).click(); await feedback(admin);
      assert.equal(await row.getByText('Vinculado',{exact:true}).count(),1);
      await admin.locator('[data-plan-preview]').click();
      const preview=admin.locator('[data-plan-preview-dialog]');
      assert(await preview.getByText(marker+' '+category,{exact:true}).isVisible());
      await preview.locator('[data-plan-preview-close]').click();
      await admin.locator('[name=q]').fill(marker); await admin.getByRole('button',{name:'Buscar',exact:true}).click();
      assert.equal(await admin.locator('[data-insurance-plan-id]').count(),1);
      await admin.locator('.admin-insurance-columns > summary').click();
      await admin.locator('[data-plan-column="1"]').uncheck();
      assert(!(await admin.locator('.admin-insurance-table tbody td').nth(1).isVisible()));
      await admin.locator('[data-plan-column="1"]').check();
      await admin.locator('.admin-insurance-columns > summary').click();
      await admin.goto(base+'/superadmin/catalogo/insurance-carriers?view='+category);
      await admin.screenshot({path:'storage/app/qa/insurance-catalog-'+category+'.png'});
    }
    const patient = await browser.newPage({viewport:{width:390,height:844},reducedMotion:'reduce'});
    await login(patient,'paciente'); await patient.goto(base+'/patient');
    await patient.getByRole('button',{name:'Abrir soporte',exact:true}).click();
    await patient.locator('[data-open-view="insurance"]').click();
    const drawer=patient.locator('.patient-insurance-drawer');
    await drawer.locator('[data-insurance-screen="purchase"]').click();
    await drawer.locator('[name=enroll_target][value=self]').check();
    await drawer.locator('[data-enroll-step="1"] [data-enroll-next]').click();
    await drawer.locator('[data-enroll-category]').selectOption('life');
    const life=drawer.locator('[data-enroll-step="2"] .ki-enroll-plan:visible');
    assert((await life.allInnerTexts()).some(t=>t.includes(marker+' life')));
    await life.filter({hasText:marker+' life'}).locator('input').check();
    await drawer.locator('[data-enroll-step="2"] [data-enroll-next]').click();
    assert.equal(await drawer.locator('[data-enroll-total]').innerText(),'Por cotizar');
    assert((await drawer.locator('[data-enroll-benefits]').innerText()).includes('Fallecimiento QA'));
    await drawer.locator('[data-insurance-drawer-back]').click();
    await drawer.locator('[data-enroll-category]').selectOption('gmm');
    await drawer.locator('.ki-enroll-plan:visible').filter({hasText:marker+' gmm'}).locator('input').check();
    await drawer.locator('[data-enroll-step="2"] [data-enroll-next]').click();
    await drawer.locator('[data-enroll-addon=dental]').check();
    assert((await drawer.locator('[data-enroll-total]').innerText()).includes('1,652.95'));
    await patient.screenshot({path:'storage/app/qa/insurance-patient-options.png'});
    for(let step=3;step<10;step++) {
      const panel=drawer.locator(`[data-enroll-step="${step}"]`); await panel.waitFor({state:'visible'});
      const required=panel.locator('[required]:not(:disabled)');
      for(let i=0;i<await required.count();i++) {
        const field=required.nth(i),type=await field.getAttribute('type');
        if(type==='checkbox')await field.check();
        else if(type==='radio'){if(await field.getAttribute('value')==='no')await field.check();}
        else if(type==='date')await field.fill('1988-03-14');
        else if(type==='email')await field.fill('qa@example.test');
        else if(type==='number')await field.fill('100');
        else if(type==='tel')await field.fill('5555550100');
        else await field.fill('Prueba de interfaz');
      }
      if(step===7)assert((await panel.locator('[data-enroll-terms]').textContent()).includes('Condiciones QA del catálogo'));
      if(step===8){const canvas=panel.locator('canvas');await canvas.scrollIntoViewIfNeeded();const r=await canvas.boundingBox();await patient.mouse.move(r.x+20,r.y+30);await patient.mouse.down();await patient.mouse.move(r.x+80,r.y+60,{steps:5});await patient.mouse.up();}
      await panel.locator('[data-enroll-next]').click();
    }
    assert.equal(await drawer.locator('[name=insurance_plan_id]').inputValue(),String(created[0].id));
    await drawer.getByRole('button',{name:'Ir a Inicio',exact:true}).click();
    assert(await drawer.locator('[data-insurance-screen-panel=entry]').isVisible());
    for(const record of created){
      const category=record.name.endsWith('gmm')?'gmm':'life';
      await admin.goto(base+'/superadmin/catalogo/insurance-carriers?view='+category);
      await admin.locator(`[data-insurance-plan-id="${record.id}"]`).getByRole('button',{name:'Desvincular de Mi seguro'}).click(); await feedback(admin);
    }
    await patient.reload();
    const feed=await patient.request.get(base+'/patient/insurance-plans');const json=await feed.json();
    assert(!json.data.plans.some(p=>created.some(record=>record.id===p.id)));
    assert.deepEqual(errors,[]);
    console.log('PASS: admin create/link/unlink, search, columns, preview; mobile GMM/life, exact prices/addons, 10 steps and return home; user access columns.');
  } finally { await browser.close(); }
})().catch(e=>{console.error(e);process.exitCode=1;});
