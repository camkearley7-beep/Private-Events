# The Bogey Club website: "Tee to Tap"

The production build of the approved homepage, plus the site-wide header and footer, for WordPress and Elementor.

- **Install:** follow [`docs/IMPLEMENTATION.md`](../docs/IMPLEMENTATION.md) and paste the numbered files from `dist/`.
- **Preview:** open `preview.html` in a browser. Images marked "Concept render" stand in for photos that are still needed.
- **Edit and rebuild:** edit `src/`, then run `./build.sh`.
- **Test:**
  ```
  PLAYWRIGHT_MODULE=… AXE=…/axe.min.js node tools/test-preview.mjs
  tools/check-links.sh
  ```
- **Fonts:** Satoshi WOFF2 files live in `fonts/`. To add a weight, convert the OTF with `fonttools` (`TTFont(path).flavor = 'woff2'`), name it `Satoshi-Regular.woff2` / `Satoshi-Medium.woff2`, and rebuild.
