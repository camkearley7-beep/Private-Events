# The Bogey Club: homepage build ("Tee to Tap"), install guide

This is the production build of the approved design for WordPress and Elementor.

| Path | What it is |
|---|---|
| `site/dist/` | Paste-ready files, numbered in install order |
| `site/dist/uploads/` | Files to upload to WordPress |
| `site/preview.html` | The whole site in one file. Open it in a browser. |
| `site/dist/bogey-club-homepage.html` | **The complete finished page in one HTML file**: head/SEO, fonts, CSS, header, homepage, footer, structured data and script. Assets load from the WordPress uploads folders in §1. |
| `site/dist/bogey-club-homepage-standalone.html` | The same page with fonts and images embedded, so it opens anywhere (review only, `noindex`) |
| `site/src/` | Sources. Edit these, then run `site/build.sh`. |
| `site/tools/` | Automated tests, screenshot tool, link checker |
| `concepts/club/` | The approved design file and the concept renders |

Install everything on a **staging copy first**. Most hosts offer one-click staging, or use the WP Staging plugin. Go live only after §8 passes.

---

## 1. Upload files

1. **Fonts.** Upload the contents of `site/dist/uploads/fonts/` to `/wp-content/uploads/fonts/`, using SFTP or the host's file manager.
   - Or add them in Elementor → Custom Fonts with the family name `Satoshi`. If you use Custom Fonts, delete the `<style>` block from `00-head.html`.
2. **Images.** Upload `site/dist/uploads/bogey-home/` to `/wp-content/uploads/bogey-home/`. That folder contains:
   - `bogey-club-logo.png`: your logo, unchanged apart from trimming the empty margin (header, and footer on a white tile)
   - `karaoke-the-bogey-club-newmarket.jpg`: a real frame from the club's karaoke video
3. **Photos still needed.** Add these real photos to the same folder using exactly these filenames (JPG, sRGB, about 200–400 KB each):

| Filename | Shot | Size |
|---|---|---|
| `golf-simulator-bay-the-bogey-club-newmarket.jpg` | A bay from behind the golfer, screen glowing, ball on the mat | 1920×1080 |
| `golf-simulator-bay-the-bogey-club-newmarket-mobile.jpg` | Same scene, portrait crop | 1080×1500 |
| `golfzon-simulator-screen-the-bogey-club.jpg` | The Golfzon screen filling the frame | 1920×1080 |
| `draft-beer-bar-the-bogey-club-newmarket.jpg` | Main bar, taps and a fresh pour | 1600×1100 |
| `draft-beer-bar-the-bogey-club-newmarket-mobile.jpg` | Same, portrait | 1000×1400 |
| `private-event-space-the-bogey-club-newmarket.jpg` | Upstairs event space with its bar, set for a party | 1920×1100 |
| `private-event-space-the-bogey-club-newmarket-mobile.jpg` | Same, portrait | 1000×1300 |

Until a photo is uploaded, its frame shows a clean deep-pine panel instead of a broken image. The concept renders in `concepts/club/assets/` are for review only and must never be uploaded.

If you prefer the Media Library (which files under `/2026/10/`), find and replace `https://thebogeyclub.com/wp-content/uploads/bogey-home/` in `03`, `04` and `05` with the real folder.

## 2. Site-wide code (WPCode plugin, free)

| File | WPCode snippet type | Location |
|---|---|---|
| `00-head.html` | HTML | Site Wide Header |
| `01-styles.css` | CSS. Or paste into Elementor → Site Settings → Custom CSS. | Site wide |
| `02-script.html` | HTML | Site Wide Footer |
| `06-schema.html` | HTML | Header, **homepage only** (Page-specific → Home) |

## 3. Global header with Karaoke in the menu

1. **Appearance → Menus → Primary.** Set these items in this order, then save. This adds Karaoke to the global WordPress navigation.
   1. Golf (`/our-simulators/`)
   2. **Karaoke (`/karaoke/`)**
   3. Private events (`/private-events/`)
   4. Leagues (`/leagues-and-events/`)
   5. Lessons (`/meet-our-pro/`)
   6. Shop (`https://shop.thebogeyclub.com/`)
   7. Contact (`/faqs/`)
2. **Templates → Theme Builder → Header.**
   - Edit the active header, or add one with display condition Entire Site.
   - Delete its contents and add one full-width container with padding 0.
   - Inside it, add an **HTML widget** with `03-header.html`.
   - It contains the desktop menu and the mobile menu, with the same links as step 1.
   - It is fixed to the top and moves itself below the WordPress admin bar when you're logged in.
3. Make sure only one header renders: check the theme header is off (Hello Elementor does this automatically).

## 4. Homepage

1. Duplicate the current homepage first (Pages → Home → Duplicate) so you have a rollback.
2. Edit with Elementor. In Page Settings, set Layout to **Elementor Full Width** and turn **Hide Title** on.
3. Remove the old sections. Every destination they linked to is covered in §7.
4. Add one full-width container (padding 0, gap 0) holding one **HTML widget** with `04-home.html`.

## 5. Footer, newsletter and mobile booking bar

1. **Templates → Theme Builder → Footer** (Entire Site). Add a full-width container with padding 0.
2. Split `05-footer.html` at the line `<!--BCH:NEWSLETTER-->` into two HTML widgets.
3. Move your **existing newsletter signup widget** between those two widgets, so its provider and list keep working.
4. The second half of the file also contains the mobile booking bar (Book a bay / Karaoke / Events). It shows on phones on every page.

## 6. SEO settings (Yoast or Rank Math, homepage)

- **SEO title:** `The Bogey Club | Golf Simulator Sports Bar in Newmarket` (55 characters)
- **Meta description:** `Four Golfzon simulators, two full bars, cold beer on tap, karaoke and private event space in Newmarket, Ontario. Book a bay or plan your party.` (144 characters)
- **H1:** "Golf simulator sports bar · Newmarket, ON — Your handicap is welcome here." The location line sits inside the H1, so the keywords are in it.
- **H2s:** From the tee to the tap · The best rounds don't end at 18 · Book the room. We'll run the night. · There's always a next round · Visit The Bogey Club in Newmarket.
- **Structured data (`06-schema.html`):**
  - LocalBusiness (SportsActivityLocation + BarOrPub + EntertainmentBusiness) with address, phone, email, and the Newmarket, Aurora and East Gwillimbury service area
  - Amenities: 4 Golfzon simulators, 2 bars, draft beer, karaoke, event space
  - Two booking actions: golf and karaoke
  - Services: golf, karaoke, events, lessons, leagues
  - The WebPage node is linked to the SEO plugin's `#website`
  - Hours, ratings and coordinates are deliberately left out until verified
  - If the SEO plugin also outputs a LocalBusiness, turn its version off so there's only one
- **Checks:** Settings → Reading → "Discourage search engines" must be **off**. The sitemap must include `/`, `/karaoke/` and `/private-events/`. After launch, request indexing in Search Console.
- **Crawlers:** `robots.txt` must not block Googlebot, Bingbot, OAI-SearchBot, Claude-SearchBot or PerplexityBot.

## 7. Every link, and where it lives

| Destination | URL | Header | Homepage | Footer / dock |
|---|---|---|---|---|
| Book golf | `thebogeyclub.golfbooking.ca/calendar.php` | Book a bay, mobile menu | Hero, Bays stop | Footer, dock |
| Book karaoke | `…calendar.php?date=<this month>&bayfilter=4&templateId=50&lang=en` (month set automatically, same as the karaoke page) | Mobile menu | Stage stop | Dock |
| Karaoke page | `/karaoke/` | **Karaoke** | Hero, Stage stop | Footer |
| Private events + inquiry form | `/private-events/` | Private events | Hero (→ section), events section ×2, Upstairs stop | Footer, dock |
| Our simulators | `/our-simulators/` | Golf | Bays stop | Footer |
| Leagues & events | `/leagues-and-events/` | Leagues | Club life ×3 | Footer |
| Private lessons | `/meet-our-pro/` | Lessons | Club life | Footer |
| Bogey Club Classic | `/the-bogey-club-classic-2026/` | – | Club life | Footer |
| Shop | `shop.thebogeyclub.com` | Shop | Club life | Footer |
| About us | `/about-us/` | Mobile menu | – | Footer |
| FAQs / contact | `/faqs/` | Contact | – | Footer |
| Phone | `tel:+19052354653` | Mobile menu | Visit | Footer |
| Email | `mailto:golf@thebogeyclub.com` | Mobile menu | Visit | – |
| Directions | Google Maps | – | Visit | – |
| Privacy policy | `/privacy-policy/` (**confirm the URL**) | – | – | Footer |
| Instagram | `instagram.com/thebogeyclub` (**confirm the handle**) | – | – | Footer |
| Newsletter | existing widget | – | – | Footer |

The event inquiry buttons go to `/private-events/`, where the existing form lives, so its email routing and spam protection are untouched. If that form section has an anchor ID, append it, for example `/private-events/#inquire`.

Every CTA carries `data-tt-cta="…"`, so the site's existing Google Tag Manager or GA can track clicks without a second tag.

## 8. Testing

**Already run on `site/preview.html`** with `node site/tools/test-preview.mjs` (Playwright, Chromium, axe-core 4.10), at 1440px and 390px. All checks pass:

- Satoshi Bold loads and renders the H1 (verified with `document.fonts.check`, not just the CSS name)
- No horizontal overflow, exactly one H1, no empty or `#` links
- All karaoke links open the karaoke calendar on the current month
- Desktop: the tee-to-tap section moves sideways as you scroll
- Mobile: the menu opens, focuses the first link and closes on Esc; the booking bar is visible
- axe WCAG 2 A/AA: 0 violations; no JavaScript errors

**Run on staging before launch:**

- [ ] `site/tools/check-links.sh` from a normal connection (every link returns 200)
- [ ] Book golf: reach the checkout from the header and hero
- [ ] Book karaoke: the karaoke calendar opens, not the golf calendar
- [ ] Event inquiry on `/private-events/` arrives in the inbox
- [ ] Newsletter test signup is added to the list
- [ ] Karaoke shows in the header on every page
- [ ] iPhone Safari and Android Chrome: menu, booking bar, tel/mailto, directions
- [ ] Only one header and one footer render; GA/GTM fires once per page view
- [ ] PageSpeed Insights mobile: LCP ≤ 2.5 s, CLS ≤ 0.1, INP ≤ 200 ms
- [ ] Rich Results Test / Schema Validator: no errors, one LocalBusiness

## 9. Before you publish

| Item | Status |
|---|---|
| Satoshi Regular & Medium | **Needed.** Only Bold was supplied. Body text uses the system font until `Satoshi-Regular.otf` and `Satoshi-Medium.otf` (or `Satoshi-Variable.ttf`) are added to `site/fonts/` (as WOFF2) and `site/build.sh` is re-run. Satoshi has no Semibold; 600 maps to Bold. |
| Real photos | 7 files listed in §1 |
| Golf hours "Daily 9 AM – 12 AM" | Confirm. Listings conflict. |
| Staffed bar hours | Shown as "See posted hours". Add real hours when confirmed. |
| $60/bay/hour + HST, up to 4 players | Confirm. Used in the structured data. |
| Second bar is upstairs in the event space | Confirm |
| Men's Night | Confirm it's running |
| Privacy URL, Instagram handle | Confirm |
| Sponsor links on the current homepage (if any) | Move them to the footer |
| AI-generated photos on `/karaoke/` (filenames starting `ChatGPT-Image…`) | Consider replacing them with real photos |
