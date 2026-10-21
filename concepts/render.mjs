// Renders an HTML file to PNG with Playwright.
// usage: node render.mjs <in.html> <out.png> <width> [height|full] [scale]
// Set PLAYWRIGHT_MODULE if playwright is not resolvable from here.
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const [,, inp, out, w, h = 'full', scale = '1'] = process.argv;
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: +w, height: h === 'full' ? 900 : +h }, deviceScaleFactor: +scale });
await p.goto('file://' + inp, { waitUntil: 'networkidle' });
await p.evaluate(() => document.fonts.ready);
if (process.env.HIDE_FIXED) await p.addStyleTag({ content: '.dock{display:none!important}' });
await p.screenshot({ path: out, fullPage: h === 'full' });
await b.close();
