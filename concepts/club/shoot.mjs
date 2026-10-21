// node shoot.mjs <w> <h> <out> [full|scrollY]
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const [,, w, h, out, mode, extra] = process.argv;
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: +w, height: +h }, deviceScaleFactor: +w < 600 ? 2 : 1 });
await p.goto('file://' + process.cwd() + '/index.html', { waitUntil: 'networkidle' });
await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(2800);
if (extra) await p.evaluate(extra);
if (mode === 'full') {
  // flatten the pinned dolly for a single tall capture
  await p.addStyleTag({ content: '.walk{height:auto!important}.walk-pin{position:relative!important;top:0!important}.track{transform:none!important}.dock{display:none!important}' });
  await p.evaluate(() => { document.getElementById('track').style.transform = ''; });
  await p.waitForTimeout(400);
  await p.screenshot({ path: out, fullPage: true });
} else if (mode && mode !== 'view') {
  await p.evaluate(y => window.scrollTo(0, +y), mode); await p.waitForTimeout(700);
  await p.screenshot({ path: out });
} else await p.screenshot({ path: out });
await b.close();
