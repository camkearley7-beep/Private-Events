// Automated checks against preview.html: fonts, layout, a11y, interactions.
// usage: PLAYWRIGHT_MODULE=... AXE=path/to/axe.min.js node tools/test-preview.mjs
import { createRequire } from 'module';
import fs from 'fs';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const axe = process.env.AXE ? fs.readFileSync(process.env.AXE, 'utf8') : null;
const url = 'file://' + process.cwd() + '/preview.html';
const b = await chromium.launch(); let fail = 0;
const ok = (c, m) => { console.log((c ? 'PASS ' : 'FAIL ') + m); if (!c) fail++; };
for (const [w, h] of [[1440, 900], [390, 844]]) {
  const p = await b.newPage({ viewport: { width: w, height: h } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(url, { waitUntil: 'networkidle' }); await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(600);
  const r = await p.evaluate(() => ({
    bold: document.fonts.check('700 20px Satoshi'),
    h1Font: getComputedStyle(document.querySelector('h1 .ln')).fontFamily,
    h1Weight: getComputedStyle(document.querySelector('h1 .ln')).fontWeight,
    overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    h1s: document.querySelectorAll('h1').length,
    karaoke: [...document.querySelectorAll('[data-tt-link="karaoke-booking"]')].map(a => a.href),
    deadLinks: [...document.querySelectorAll('a')].filter(a => !a.getAttribute('href') || a.getAttribute('href') === '#').length,
    brokenImgs: [...document.querySelectorAll('img')].filter(i => i.complete && i.naturalWidth === 0 && !i.loading === 'lazy').length,
  }));
  const tag = `[${w}px]`;
  ok(r.bold, `${tag} Satoshi Bold loaded and rendering (h1: ${r.h1Font} ${r.h1Weight})`);
  ok(r.overflow === 0, `${tag} no horizontal overflow`);
  ok(r.h1s === 1, `${tag} exactly one H1`);
  ok(r.deadLinks === 0, `${tag} no empty or "#" links`);
  const m = new Date(); const ym = m.getFullYear() + '-' + String(m.getMonth() + 1).padStart(2, '0');
  ok(r.karaoke.length >= 3 && r.karaoke.every(u => u.includes('date=' + ym) && u.includes('bayfilter=4') && u.includes('templateId=50')), `${tag} ${r.karaoke.length} karaoke links open the karaoke calendar on ${ym}`);
  // planner
  await p.evaluate(() => document.getElementById('tt-console').scrollIntoView());
  for (let i = 0; i < 8; i++) await p.click('.stepper button[data-d="1"]');
  await p.click('.seg button[data-h="3"]');
  const pl = await p.evaluate(() => ({ n: document.getElementById('tt-pl').textContent, nb: document.getElementById('tt-nb').textContent, pp: document.getElementById('tt-pp').textContent, ev: document.getElementById('tt-ev').classList.contains('show'), on: document.querySelectorAll('.bayc.on').length }));
  ok(pl.n === '14' && pl.nb === '4 bays' && pl.on === 4 && pl.pp.startsWith('$51') && pl.ev, `${tag} planner: 14 players × 3 hrs → ${pl.nb}, ${pl.pp.trim()}, event nudge shown`);
  if (w > 860) {
    const at = f => p.evaluate(f => { const wk = document.getElementById('walk'); window.scrollTo({ top: wk.offsetTop + (wk.offsetHeight - innerHeight) * f, behavior: 'instant' }); }, f);
    await at(0); await p.waitForTimeout(250);
    const before = await p.evaluate(() => document.getElementById('tt-track').style.transform);
    await at(.6); await p.waitForTimeout(250);
    const after = await p.evaluate(() => document.getElementById('tt-track').style.transform);
    ok(after && after !== before, `${tag} tee-to-tap dolly moves on scroll (${after})`);
  } else {
    await p.evaluate(() => window.scrollTo(0, 0));
    await p.click('.tt .menu');
    const open = await p.evaluate(() => ({ o: document.getElementById('tt-mnav').classList.contains('open'), e: document.querySelector('.tt .menu').getAttribute('aria-expanded'), f: document.activeElement.textContent.trim() }));
    await p.keyboard.press('Escape');
    const closed = await p.evaluate(() => !document.getElementById('tt-mnav').classList.contains('open'));
    ok(open.o && open.e === 'true' && closed, `${tag} mobile menu opens (focus: ${open.f}) and closes on Esc`);
    const dock = await p.evaluate(() => { const d = document.querySelector('.tt .dock'); const r = d.getBoundingClientRect(); return getComputedStyle(d).display !== 'none' && r.bottom <= innerHeight; });
    ok(dock, `${tag} booking dock visible`);
  }
  if (axe) {
    await p.addScriptTag({ content: axe });
    const v = await p.evaluate(async () => (await axe.run(document, { runOnly: ['wcag2a', 'wcag2aa'] })).violations.map(x => x.id + ' x' + x.nodes.length));
    ok(v.length === 0, `${tag} axe WCAG 2 A/AA: ${v.length ? v.join(', ') : '0 violations'}`);
  }
  ok(errs.length === 0, `${tag} no JavaScript errors ${errs.join(' ')}`);
  await p.close();
}
await b.close();
console.log(fail ? `\n${fail} check(s) failed` : '\nAll checks passed');
process.exit(fail ? 1 : 0);
