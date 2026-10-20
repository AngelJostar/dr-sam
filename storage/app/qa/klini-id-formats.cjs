const assert=require('node:assert/strict');
const {createRequire}=require('node:module');
const rr=createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
(async()=>{
  const browser=await rr('playwright').chromium.launch({channel:'msedge',headless:true,ignoreDefaultArgs:['--hide-scrollbars']});
  try {
    const page=await browser.newPage({viewport:{width:1440,height:900},reducedMotion:'reduce'});
    const errors=[];page.on('pageerror',error=>errors.push(error.message));
    await page.goto('http://127.0.0.1:8000/acceso');
    await page.locator('#demo-username').selectOption('superadmin');
    await Promise.all([page.waitForURL('**/superadmin'),page.getByRole('button',{name:'Ingresar',exact:true}).click()]);
    const results=[];
    for (const category of ['klini','operational']) {
      await page.goto('http://127.0.0.1:8000/superadmin/catalogo/users?category='+category);
      const rows=await page.locator('.superadmin-native-catalog-table tbody tr').evaluateAll(rows=>rows.map(row=>[...row.querySelectorAll('td')].map(td=>td.innerText.trim())));
      assert(rows.length>0);
      for(const row of rows) {
        assert.match(row[category==='klini'?1:2], category==='klini'?/^\d{8,}$/:/^OP\d{8,}$/);
        assert.equal(row[category==='klini'?2:1],'—');
      }
      const csv=await page.request.get('http://127.0.0.1:8000/superadmin/catalogo/users/csv?category='+category);
      assert.equal(csv.status(),200);
      assert((await csv.text()).includes(rows[0][category==='klini'?1:2]));
      results.push({category,checkedRows:rows.length});
    }
    await page.goto('http://127.0.0.1:8000/superadmin/catalogo/users');
    await page.waitForLoadState('networkidle');
    await page.screenshot({path:'storage/app/qa/klini-id-formats.png'});
    assert.deepEqual(errors,[]);
    console.log(JSON.stringify({results,errors}));
  } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1;});
