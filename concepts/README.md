# Homepage creative reset: round 2 (directions for approval)

This round contains design direction only. The full website gets built after a direction is approved.

| File | What it is |
|---|---|
| `renders/board-0-overview.png` | The three directions side by side, the recommendation and the six-section homepage structure |
| `renders/board-a.png` | **Direction A: Open Membership (recommended).** Desktop hero and the "What's on" section, the full mobile homepage, type specimen, palette in context, accent alternatives, photography brief |
| `renders/board-b.png` | Direction B: After Hours |
| `renders/board-c.png` | Direction C: The Clubhouse Journal |
| `renders/*-desktop-*.png`, `renders/*-mobile.png` | The individual mockups at full resolution |
| `src/` | Mockup HTML. These are design comps, not production code. |

The grey areas in the mockups are **briefs for real photos**, not finished images. Each one says what to shoot. No venue photography has been invented.

The logo appears as a labelled slot because this environment cannot reach `cdn.shopify.com`, where the real files live (`font_logo.png` and `font_logo_white.png` in Shopify Files). Add the logo files (SVG preferred) to `assets/brand/` in this repo and the next round will use them directly.

Rebuild everything with `./render.sh`, which needs Python 3 and Playwright.
