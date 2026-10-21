# "Tee to Tap": homepage concept (round 6: Satoshi + new hero headline)

This is one original concept for the homepage. It shares nothing with the karaoke page's layout, type, colour or interactions. What ties the two together is the logo and the brand's personality.

**The idea:** the homepage is one walk through the club, from the tee box to the tap.
- **Hero:** puts you at address in a Golfzon bay, looking down the fairway. An aim line draws to the flag.
- **Visual language:** comes from two real things in the venue.
  - The simulator's shot-data screen: precise mono readouts and aim lines.
  - The clubhouse: walnut, deep course green, warm white, and pin-flag red.
- **Neon:** none anywhere. No glow effects either.

**Round 5 (type and colour).** The layout, content and interactions are unchanged. Two things changed:
- **Palette:** the exact five colours: White #FFFFFF, Soft off-white #F3F6EF, Rich golf green #0D5B3B, Deep pine #233E31, Muted sand #D7C9A4. The page is roughly two-thirds white and off-white. Green is used for booking buttons, the private events section, the bay planner and the footer.
- **Type:** one family, General Sans. Medium 500 is used for body text and Semibold 600 for headings. Headings are sentence case with tight tracking. The page loads General Sans from Fontshare (free for commercial use under the ITF Free Font License). Hanken Grotesk is only a fallback; the PNG renders in this repo show it because Fontshare was not reachable from the build environment.
- **Logo:** the header uses `assets/bogey-club-logo-pine.png`, a single-colour pine version of the real white logo, so it reads on the white header.
- **Accessibility:** all text passes WCAG 2.1 AA contrast (axe-core, desktop and mobile).

**The two interactions:**
1. **Tee to Tap.** As you scroll on desktop, the camera dollies sideways through Bays, Bars, Stage and Upstairs. On a phone, it becomes a native swipe.
2. **How many are coming?** A bay planner. Players and hours light up the bays you'll need and show the per-person cost at $60 per bay per hour plus HST. Groups of 12 or more are nudged toward a private event.

| File | What it is |
|---|---|
| `index.html` | The interactive preview. Open it in a browser. |
| `renders/desktop-full.png`, `renders/mobile-full.png` | The full homepage on desktop and mobile |
| `renders/desktop-hero.png`, `renders/mobile-hero.png` | First screen on desktop and mobile |
| `renders/desktop-tee-to-tap.png`, `renders/desktop-plan-your-bay.png` | The two interactions |
| `renders/motion-desktop.mp4`, `renders/motion-mobile.mp4` | Screen recordings of the motion and interactions |
| `renders/type-and-colour.png` | Type scale and palette in use |

**Imagery**
- Every image labelled "Concept render" was drawn procedurally for this preview (`render/scenes.html`) and must be replaced with Bogey Club photography before launch.
- The karaoke stills are real frames from the club's own karaoke hero video.
- The logo is the real white logo.

**Round 6.**
- **Font:** Satoshi replaces General Sans throughout.
  - Bold 700: hero and section headings.
  - Semibold 600: subheadings and buttons.
  - Medium 500: navigation and labels.
  - Regular 400: body text.
- **Hero:** the headline is now "YOUR HANDICAP IS WELCOME HERE." The supporting copy is "Good golf, bad golf, and everything in between…"
- **Font loading:** self-hosted from `assets/fonts/Satoshi-Variable.woff2`, which comes from the official Fontshare download. The Fontshare CDN is a second source. The preview shows a banner if Satoshi fails to load, so a fallback can't be mistaken for it.
- **Renders:** the PNGs in `renders/` are still from round 5. They will be regenerated once the Satoshi file is in `assets/fonts/` or Fontshare is reachable from the build environment.
