import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const [,, out, frac, plan] = process.argv;
const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 1440, height: 900 } });
await p.goto('file://' + process.cwd() + '/index.html', { waitUntil: 'networkidle' }); await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(800);
if (plan) { await p.evaluate(n => { window.ttSet(+n, 3); document.querySelector('.plan').scrollIntoView(); window.scrollBy(0, -40); }, plan); }
else await p.evaluate(f => { const w = document.getElementById('walk'); window.scrollTo(0, w.offsetTop + (w.offsetHeight - innerHeight) * f); }, +frac);
await p.waitForTimeout(900); await p.screenshot({ path: out }); await b.close();
