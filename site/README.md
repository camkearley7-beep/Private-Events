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
- **Fonts:** `fonts/Satoshi-Variable.woff2` (converted from the supplied `Satoshi-Variable.ttf`) covers weights 300–900. The build uploads it from `dist/uploads/fonts/`.
