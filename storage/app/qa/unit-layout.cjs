const { createRequire } = require('node:module');
const requireRuntime = createRequire('C:/Users/Guillermo/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/package.json');
const { chromium } = requireRuntime('playwright');
(async () => {
  const browser = await chromium.launch({ channel: 'msedge', headless: true });
  try {
    const page = await browser.newPage({ viewport: {width: 1380, height: 900} });
    await page.goto('http://127.0.0.1:8000/acceso');
    await page.locator('#demo-username').selectOption('unidad.demo');
    await Promise.all([page.waitForURL('**/dashboard'), page.getByRole('button', {name: 'Ingresar', exact: true}).click()]);
    for (const section of ['services', 'users']) {
      await page.goto('http://127.0.0.1:8000/unit?section=' + section);
      await page.locator('.klini-carousel').waitFor();
      await page.evaluate(() => document.fonts.ready);
      await page.screenshot({path: `storage/app/qa/unit-${section}-before.png`});
      console.log(section, await page.evaluate(() => {
        const selectors = ['.unit-native-topbar','.unit-native-sidebar','.unit-service-carousel-card','.unit-service-panel:not([hidden]) > .unit-service-panel-header', '.unit-catalog-info-card', '.unit-catalog-toolbar','.unit-consultation-submenu-card'];
        return Object.fromEntries(selectors.map(sel => {
          const el = [...document.querySelectorAll(sel)].find(el => el.getBoundingClientRect().height > 0);
          if (!el) return [sel,null];
          const {x,y,width,height} = el.getBoundingClientRect();
          return [sel,{x,y,width,height}];
        }));
      }));
    }
  } finally { await browser.close(); }
})().catch(error => { console.error(error.message); process.exitCode = 1; });
