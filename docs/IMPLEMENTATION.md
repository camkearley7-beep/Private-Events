# The Bogey Club: homepage build ("Tee to Tap"), install guide

This is the production build of the approved design for WordPress and Elementor.

| Path | What it is |
|---|---|
| `site/dist/` | Paste-ready files, numbered in install order |
| `site/dist/uploads/` | Files to upload to WordPress |
| `site/preview.html` | The whole site in one file. Open it in a browser. |
| `site/dist/ELEMENTOR-PASTE-THIS.html` | **The file to paste into one Elementor HTML widget** (see Quick install) |
| `site/dist/bogey-club-homepage.html` | **The complete finished page in one HTML file**: head/SEO, fonts, CSS, header, homepage, footer, structured data and script. Assets load from the WordPress uploads folders in §1. |
| `site/dist/bogey-club-homepage-standalone.html` | The same page with fonts and images embedded, so it opens anywhere (review only, `noindex`) |
| `site/src/` | Sources. Edit these, then run `site/build.sh`. |
| `site/tools/` | Automated tests, screenshot tool, link checker |
| `concepts/club/` | Logo source and concept renders used by the preview build |

Install everything on a **staging copy first**. Most hosts offer one-click staging, or use the WP Staging plugin. Go live only after §8 passes.

---

## Quick install: one widget, nothing to upload

Use **`site/dist/ELEMENTOR-PASTE-THIS.html`**. It has your logo and the Satoshi font built in, and it loads the photos and video already on your WordPress site.

1. Pages → your homepage draft → **Edit with Elementor**.
2. Open Page Settings (the cog at the bottom left) and set **Page Layout: Elementor Canvas**. This hides the theme's own header and footer, because the paste includes them.
3. Delete everything on the page. Add one **Container**, then set **Content Width: Full Width** and **Padding: 0** on all sides.
4. Drag an **HTML** widget into it, open `ELEMENTOR-PASTE-THIS.html` in a text editor, copy **all** of it, and paste it into the HTML Code box.
5. Click **Update** or **Publish**.

Do **not** paste `bogey-club-homepage-standalone.html` or `bogey-club-homepage.html` into Elementor. Those are complete web pages for viewing in a browser.

The rest of this guide is the full site-wide setup: header and footer on every page, the Karaoke menu item, and SEO plugin settings. Use it when you're ready to replace the theme header and footer everywhere.

---

## Meet our pro page (/meet-our-pro/)

Same setup as the homepage: on the Meet Our Pro page, set Page Layout to **Elementor Canvas**, add one Full Width container with padding 0, add one **HTML** widget, and paste `site/dist/ELEMENTOR-PASTE-LESSONS.txt`. Header, footer, fonts and script are included.

- **Facts used** (from the current page): Andrew McDonald, PGA of Canada, teaching all levels for 8+ years; technique, strategy and the mental game; book on (905) 235-4653 or amcdonald@magnagolf.com. No lesson prices are shown, because none are published.
- **Book a lesson → request form:** every Book a lesson button scrolls to the "Request a lesson" card and puts the cursor in the first field (on phones the bottom bar steps aside while the form is on screen). The form is a WPForms form rendered from its shortcode inside the card. Build it in **WPForms → Add New** ("Lesson request"):

  | Field | Label | Settings |
  |---|---|---|
  | Name (Simple) | Name | Required · CSS class `wpforms-one-half wpforms-first` |
  | Phone | Phone | Required · CSS class `wpforms-one-half` |
  | Email | Email | Required · CSS class `wpforms-one-half wpforms-first` |
  | Dropdown | Experience level | New to golf (no handicap yet); Beginner: usually shoot 100+ (handicap 28+); Intermediate: usually shoot 85–100 (handicap 13–28); Advanced: usually shoot under 85 (handicap 12 or lower); Not sure · CSS class `wpforms-one-half` |
  | Paragraph Text | What do you want to work on? | Optional |
  | Single Line Text | Best days and times | Optional |

  Settings: Submit button text "Request a lesson"; Notifications → Send To `amcdonald@magnagolf.com` (add `golf@thebogeyclub.com` to copy the club), Subject "New lesson request from {field_id="1"}", Reply-To the Email field; Confirmations → Message "Thanks! Andrew will be in touch soon to set up your lesson."; anti-spam on. Then put the form's ID in `LESSON_FORM_ID` in `site/build.sh` and rebuild. Until then the card shows Email and Call buttons.
- **Andrew's photo:** `/wp-content/uploads/2024/12/1000004121-scaled.jpg`, cropped toward his face; the name tag sits over the bottom of the photo.
- **Simulator screenshots** ("Every swing, measured"): `sw_my_practice_img02.png` and `img03`–`img05.jpg` in the October 2026 uploads folder. The numbers beside each are labelled as sample shots.
- **SEO (Yoast or Rank Math, on the Meet Our Pro page):**
  - SEO title: `Private Golf Lessons in Newmarket | PGA of Canada Pro | The Bogey Club`
  - Meta description: `Private golf lessons in Newmarket with Andrew McDonald, Associate Professional, PGA of Canada. One-on-one indoor lessons on Golfzon simulators for every level. Book today.`
  - Focus keyphrase: `golf lessons Newmarket` (related: private golf lessons, indoor golf lessons, golf instruction).
  - Keep the URL `/meet-our-pro/` (it already has links pointing to it). Leave Yoast's own schema on; the page's JSON-LD adds the Person, Service and FAQ details.
- **On the page for Google:** keyword H1 text ("Private golf lessons · Newmarket, ON — Meet Our Pro"), a compact 7-question golf lessons FAQ at the bottom of the page (all closed; Google still reads it) (from `site/src/lessons-faq.json`, which also builds the FAQPage schema), and JSON-LD for WebPage, BreadcrumbList, the business, Andrew (Person), the lessons (Service, serving Newmarket, Aurora and East Gwillimbury) and the FAQ.
- **Off the page (biggest wins):** in Google Business Profile add "Golf lessons" / "Private golf lessons" as services with this page's URL, and post a photo of a lesson; ask lesson students for Google reviews that mention lessons; then in Google Search Console, inspect `https://thebogeyclub.com/meet-our-pro/` and click Request indexing.

## 1. Upload files

1. **Fonts.** Upload the contents of `site/dist/uploads/fonts/` to `/wp-content/uploads/fonts/`, using SFTP or the host's file manager.
   - Or add them in Elementor → Custom Fonts with the family name `Satoshi`. If you use Custom Fonts, delete the `<style>` block from `00-head.html`.
2. **Images.** Upload `site/dist/uploads/bogey-home/` to `/wp-content/uploads/bogey-home/`. That folder contains:
   - `bogey-club-logo.png`: your logo, unchanged apart from trimming the empty margin (header, and footer on a white tile)
3. **Photos already on WordPress (used directly, nothing to upload):**

| Where | Photo |
|---|---|
| Tee-to-tap stop 01 (The shop) | `/wp-content/uploads/2026/10/BogeyClubPhotoshoot_WordPress_1600px.jpg` |
| Tee-to-tap stop 03 (Karaoke) | `/wp-content/uploads/2026/10/ChatGPT-Image-Oct-16-2026-10_49_54-AM.png` |
| Tee-to-tap stop 04 (The upstairs) | `/wp-content/uploads/2026/08/Party-Room-1-edit-2.png` |
| Two full bars: Bar 01 (main floor) | `/wp-content/uploads/2026/08/Main-Bar.png` |
| Two full bars: Bar 02 (upstairs) | `/wp-content/uploads/2026/10/ChatGPT-Image-Oct-19-2026-02_24_43-PM.png` |

   Not used yet: `Party-room-2.png` (the unedited version), `Hallway.png`, and images `…Oct-19…02_24_43-PM.png`, `…02_24_35-PM.png` and `…02_24_31-PM.png`.

4. **Hero video.** The hero plays `/wp-content/uploads/2026/10/copy_DACCDB79-A1F6-4B0D-801B-D09CF8530201.mov`, muted and looping, over the hero still. It fades in only once it is actually playing. If a browser can't play it, if the visitor prefers reduced motion, or if autoplay is blocked, the still image stays, so nothing ever looks broken.
   - **Strongly recommended:** `.mov` reliably plays only in Safari. Chrome, Edge and many Android phones often can't play it, especially iPhone HEVC recordings.
     - Export the clip as **MP4 (H.264)**: 1920×1080, 10–20 seconds, no audio, ideally under 8 MB. HandBrake (free) "Fast 1080p30" works.
     - Upload it, then add `<source src="…/your-video.mp4" type="video/mp4">` as the **first** source inside `<video class="hero-video">` in `04-home.html`.

   **Photos still needed** (golf). Upload these to `/wp-content/uploads/bogey-home/` with exactly these filenames:

| Filename | Shot | Size |
|---|---|---|
| `golf-simulator-bay-the-bogey-club-newmarket.jpg` | A bay from behind the golfer, screen glowing, ball on the mat (hero) | 1920×1080 |
| `golf-simulator-bay-the-bogey-club-newmarket-mobile.jpg` | Same scene, portrait crop | 1080×1500 |
| `golfzon-simulator-screen-the-bogey-club.jpg` | The Golfzon screen filling the frame (tee-to-tap stop 01) | 1920×1080 |

Until a photo is uploaded, its frame shows a clean deep-pine panel instead of a broken image. The concept renders in `concepts/club/assets/` are for review only and must never be uploaded.

If you prefer the Media Library (which files under `/2026/10/`), find and replace `https://thebogeyclub.com/wp-content/uploads/bogey-home/` in `03`, `04` and `05` with the real folder.

## Sponsors ("Our playing partners")

Each sponsor gets a green bay card: a large outlined bay number, the logo on a raised panel, the sponsor's name and a "Visit site" pill. Once the section is on screen, a spotlight cycles through the sponsors every 3 seconds: the featured card lifts, its number fills in sand, a sand line traces its border and a light sweeps across the logo. Hovering or keyboard focus takes the spotlight.
- **Order:** Bay 1 Benson Kearley IFG, Bay 2 Bird Infrastructure, Bay 3 Newmarket Tire 'N' Mag (black screen).
- **Logos:** always full colour.
- **Links:** each bay opens the sponsor's website in a new tab (bkifg.com, birdinfrastructure.ca, newmarkettire.com).

## Contact form (Visit section)

The "Send us a message" form asks for name, email, phone (optional), a topic (General question, Golf booking, Karaoke, Private event, Leagues & lessons, Sponsorship) and a message, and checks the required fields before sending.

- **With WPForms (how the live site works):** `ELEMENTOR-PASTE-THIS.html` already contains the WPForms shortcode for form 3352 inside the card, and WordPress renders it there (it runs shortcodes in Elementor's HTML widget). No extra widget is needed; delete any WPForms or Shortcode widget you added elsewhere on the page. The card's CSS restyles the WPForms fields to match. If you rebuild the form, keep these settings:
  1. Fields:

     | Field | Label | Settings |
     |---|---|---|
     | Name | Name | Format: Simple · Required · Advanced → Field Size: Large · CSS class `wpforms-one-half wpforms-first` |
     | Email | Email | Required · Large · CSS class `wpforms-one-half` |
     | Phone (Pro) or Single Line Text (Lite) | Phone | Not required · Large · CSS class `wpforms-one-half wpforms-first` |
     | Dropdown | What's this about? | Choices: General question, Golf booking, Karaoke, Private event, Leagues & lessons, Sponsorship · Large · CSS class `wpforms-one-half` |
     | Paragraph Text | Message | Required · Large |

  2. **Settings → General:** Submit Button Text "Send message". Anti-spam protection on.
  3. **Settings → Notifications:** Send To `golf@thebogeyclub.com`; Reply-To the Email field.
  4. **Settings → Confirmations:** Message, "Thanks! Your message is in. We'll be in touch soon."
  5. If the form's ID ever changes, update `wpforms id` in `site/build.sh` and `WPFORMS_ID` in `site/src/tt.js`, then rebuild.

  **Important:** never type a shortcode (anything in square brackets that WordPress recognises, like the WPForms one) inside the script or a code comment. WordPress runs it there too, which breaks the whole script.

- **Without WPForms, out of the box:** pressing Send opens the visitor's email app with a message to golf@thebogeyclub.com, filled in from the form.
- **To send directly from the page (recommended):**
  1. Create a free form at formspree.io with golf@thebogeyclub.com as the inbox.
  2. Copy its URL (`https://formspree.io/f/…`).
  3. Paste it between the quotes in `var FORM_ENDPOINT = "";` near the top of the script, at the bottom of `ELEMENTOR-PASTE-THIS.html` (or in `site/src/tt.js`, then rebuild).

  Messages then send in place, with a "Thanks, we'll be in touch" confirmation and a hidden spam trap.

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
   7. Contact (`https://thebogeyclub.com/#visit`, a Custom Link to the homepage Visit section)
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
- **H2s:** From the tee to the tap · The best rounds don't end at 18 · There's always a next round · Our playing partners · Visit The Bogey Club in Newmarket.
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
| Private events + inquiry form | `/private-events/` | Private events | Hero, Upstairs stop | Footer, dock |
| Our simulators | `/our-simulators/` | Golf | Bays stop | Footer |
| Leagues & events | `/leagues-and-events/` | Leagues | Club life ×3 | Footer |
| Private lessons | `/meet-our-pro/` | Lessons | Club life | Footer |
| Bogey Club Classic | `/the-bogey-club-classic-2026/` | – | Club life | Footer |
| Shop | `shop.thebogeyclub.com` | Shop | Club life | Footer |
| About us | `/about-us/` | Mobile menu | – | Footer |
| Contact (Visit section) | `https://thebogeyclub.com/#visit` | Contact | – | – |
| FAQs | `/faqs/` | Mobile menu | – | Footer |
| Phone | `tel:+19052354653` | Mobile menu | Visit | Footer |
| Email | `mailto:golf@thebogeyclub.com` | Mobile menu | Visit | – |
| Directions | Google Maps | – | Visit | – |
| Privacy policy | `/privacy-policy/` (**confirm the URL**) | – | – | Footer |
| Instagram | `instagram.com/the.bogeyclub` | – | – | Footer |
| Newsletter | existing widget | – | – | Footer |

The event inquiry buttons go to `/private-events/`, where the existing form lives, so its email routing and spam protection are untouched. If that form section has an anchor ID, append it, for example `/private-events/#inquire`.

Every CTA carries `data-tt-cta="…"`, so the site's existing Google Tag Manager or GA can track clicks without a second tag.

## 8. Testing

**Already run on `site/preview.html`** with `node site/tools/test-preview.mjs` (Playwright, Chromium, axe-core 4.10), at 1440px and 390px. All checks pass:

- Satoshi loads at 400, 500, 600 and 700, and both the headings and the body text render in it (verified with `document.fonts.check`, not just the CSS name)
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
| Satoshi | Done: `Satoshi-Variable.woff2` covers every weight (400 body, 500 nav/labels, 600 subheadings/buttons, 700 headings). |
| Real photos | 3 golf photos listed in §1 (bar, event and karaoke photos are in) |
| Hours: bar side "Daily 9 AM – 12 AM", automated bays "Open 24/7" | Confirmed by owner. Update Google Business Profile to match. |
| Staffed bar hours | Shown as "See posted hours". Add real hours when confirmed. |
| $60/bay/hour + HST, up to 4 players | Confirm. Used in the structured data. |
| Second bar is upstairs in the event space | Confirm |
| Men's Night | Confirm it's running |
| Privacy policy URL | Confirm |
| Sponsor links on the current homepage (if any) | Move them to the footer |
| AI-generated photos on `/karaoke/` (filenames starting `ChatGPT-Image…`) | Consider replacing them with real photos |
