// Records a ~30s motion walkthrough: node record.mjs <w> <h> <dir>
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const [,, w, h, dir] = process.argv; const mobile = +w < 600;
const b = await chromium.launch();
const ctx = await b.newContext({ viewport: { width: +w, height: +h }, deviceScaleFactor: 1, recordVideo: { dir, size: { width: +w, height: +h } }, hasTouch: mobile });
const p = await ctx.newPage();
await p.goto('file://' + process.cwd() + '/index.html', { waitUntil: 'networkidle' });
await p.waitForTimeout(3800);
const glide = async (dy, steps = 30) => { for (let i = 0; i < steps; i++) { await p.mouse.wheel(0, dy / steps); await p.waitForTimeout(28); } };
await p.mouse.move(+w / 2, +h / 2);
if (!mobile) {
  await glide(900); await p.waitForTimeout(500);
  for (let i = 0; i < 6; i++) { await glide(520, 26); await p.waitForTimeout(350); }
  await glide(900); await p.waitForTimeout(1200);
} else {
  await glide(844); await p.waitForTimeout(600);
  for (let i = 0; i < 3; i++) { await p.evaluate(i => { const t = document.getElementById('track'); t.scrollTo({ left: t.children[i + 1].offsetLeft - 18, behavior: 'smooth' }); }, i); await p.waitForTimeout(1300); }
  await glide(1500); await p.waitForTimeout(800);
}
await p.evaluate(() => document.querySelector('.plan').scrollIntoView({ behavior: 'smooth', block: 'start' }));
await p.waitForTimeout(1200);
await p.evaluate(() => document.getElementById('console').scrollIntoView({ behavior: 'smooth', block: 'center' }));
await p.waitForTimeout(900);
const plus = p.locator('.stepper button[data-d="1"]');
for (let i = 0; i < 8; i++) { await plus.click(); await p.waitForTimeout(260); }
await p.locator('.seg button[data-h="3"]').click(); await p.waitForTimeout(1600);
await p.evaluate(() => document.getElementById('events').scrollIntoView({ behavior: 'smooth' })); await p.waitForTimeout(2600);
await ctx.close(); await b.close();
