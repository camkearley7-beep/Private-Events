// Screenshots of preview.html: node tools/shoot.mjs <w> <h> <out.png> [full]
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const [,, w, h, out, mode] = process.argv;
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: +w, height: +h }, deviceScaleFactor: +w < 600 ? 2 : 1 });
await p.goto('file://' + process.cwd() + '/preview.html', { waitUntil: 'networkidle' });
await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(2800);
if (mode === 'full') {
  await p.addStyleTag({ content: '.walk{height:auto!important}.walk-pin{position:relative!important;top:0!important}.track{transform:none!important}.dock{display:none!important}.hdr{position:absolute!important}' });
  await p.waitForTimeout(300);
  await p.screenshot({ path: out, fullPage: true });
} else await p.screenshot({ path: out });
await b.close();
