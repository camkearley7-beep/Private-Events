// Renders each concept scene to JPG: node shoot-scenes.mjs
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const jobs = (process.argv[2] ? [process.argv.slice(2)] : [
  ['bay', 1920, 1080], ['bay-tall', 1080, 1500], ['course', 1920, 1080], ['pour', 1600, 1100], ['pour-tall', 1000, 1400], ['upstairs', 1920, 1100], ['upstairs-tall', 1000, 1300]]);
const b = await chromium.launch();
for (const [name, w, h] of jobs) {
  const p = await b.newPage({ viewport: { width: +w, height: +h } });
  await p.goto(`file://${process.cwd()}/scenes.html?scene=${name.replace(/-(tall|close)$/, '')}${name.endsWith('-close') ? '&close=1' : ''}&w=${w}&h=${h}`);
  await p.waitForFunction(() => document.title === 'done');
  await p.locator('canvas').screenshot({ path: `../assets/render-${name}.jpg`, type: 'jpeg', quality: 86 });
  await p.close();
}
await b.close();
