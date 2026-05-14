// Shoot full-page screenshots of export (local file://) vs staging (live).
// Triggers scroll-based reveals first.
const { chromium } = require('playwright');
const path = require('path');
const os = require('os');

const EXPORT_DIR = path.join(os.homedir(), 'ignite/exports/latest');
const COMPARE_DIR = path.join(os.homedir(), 'ignite/exports/.compare');
const STAGING = 'https://igniteiqstg.wpenginepowered.com';

const PAGES = [
  ['index.html', '/', 'home'],
  ['how-it-works.html', '/how-it-works/', 'how-it-works'],
  ['ontology.html', '/ontology/', 'ontology'],
  ['company.html', '/company/', 'company'],
  ['contact.html', '/contact/', 'contact'],
  ['signin.html', '/signin/', 'signin'],
];

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();

  for (const [exportFile, stagingPath, slug] of PAGES) {
    for (const which of ['export', 'staging']) {
      const url = which === 'export'
        ? 'file://' + path.join(EXPORT_DIR, exportFile)
        : STAGING + stagingPath;
      const out = path.join(COMPARE_DIR, which, `${slug}.png`);
      console.error(`[${which}] ${slug} ← ${url}`);
      try {
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
      } catch (e) {
        console.error(`  goto failed: ${e.message}`);
        continue;
      }
      // Trigger scroll-based reveals: scroll all the way down, wait, then back up.
      await page.evaluate(async () => {
        await new Promise(resolve => {
          let y = 0;
          const step = 400;
          const tick = () => {
            window.scrollTo(0, y);
            y += step;
            if (y < document.body.scrollHeight + 1000) {
              setTimeout(tick, 80);
            } else {
              window.scrollTo(0, 0);
              setTimeout(resolve, 600);
            }
          };
          tick();
        });
      });
      await page.waitForTimeout(800);
      try {
        await page.screenshot({ path: out, fullPage: true });
        console.error(`  → ${out}`);
      } catch (e) {
        console.error(`  screenshot failed: ${e.message}`);
      }
    }
  }

  await browser.close();
})();
