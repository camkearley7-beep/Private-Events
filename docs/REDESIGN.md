# The Bogey Club: homepage redesign ("Front Nine / Back Nine")

The homepage build is in `homepage/`. Open `homepage/preview.html` in any browser to see the full page. Paste-ready Elementor files are in `homepage/dist/`. All sources are in `homepage/src/`. After you edit a source file, run `homepage/build.sh` to rebuild the preview and dist files. The script uses only bash and the Python standard library.

> **Before you publish:** every item in [§8 Verify before publishing](#8-verify-before-publishing) must be confirmed by the owner. The build marks the facts it could not verify, and it does not make up photos, reviews, hours, capacities, packages or menus.

---

## 1. Audit

### 1.1 How this audit was done (limits)

The cloud session that built this redesign could not reach `thebogeyclub.com` (blocked by the network policy). Because of that:

- The audit uses the URLs given in the brief, public search listings for the venue, and the live Shopify store through the Shopify connector (store `shop.thebogeyclub.com`, verified 2026-10-21).
- The live homepage's DOM was **not** crawled. A few items, such as testimonials, the exact newsletter provider, footer legal URLs and the karaoke booking URL, still need to be copied from the live site. Each one is listed in §8.
- Run `homepage/tools/check-links.sh` from any normal internet connection. It confirms every destination below returns HTTP 200.

### 1.2 Weaknesses found

| # | Issue | Fix in the redesign |
|---|---|---|
| 1 | The homepage presents the venue mainly as simulator rental. The two bars, karaoke and the event space are not at the forefront. | The H1, hero, scorecard, ticker and four experience cards all lead with golf, bars, karaoke and events. |
| 2 | Karaoke is missing from the global menu. | Karaoke is item 02 in the global header and in the WordPress Primary menu (§6.2). |
| 3 | Private events has low visibility. | Events appear in the hero, experience card 03, a full section, an inline inquiry block, the menu, the footer and the FAQ. |
| 4 | Hours conflict across sources. Search listings show both "Mon–Sun 9 AM–12 AM" and a "summer hours" block (Wed–Sat, other days by appointment). | One labelled golf-hours table and a separate **staffed bar hours** table. Both must be confirmed (§8). |
| 5 | An expired promotion is still on the site. The Bogey Club Classic took place on **June 4, 2026**, and the older 2025 registration page (`/registration-for-the-bogey-club-classic/`) still lists June 19, 2025. | The Classic is shown as "2026 · In the books" in the scorecard. Archive or redirect the 2025 registration page. |
| 6 | Directory listings still describe **two** simulators. | Correct the Google Business Profile, Golf Canada/Ontario, Teebly, GolfNow and Yellow Pages listings to four simulators, two bars, karaoke and the event space (§5.6). |
| 7 | This repo's existing `index.html` (a private events page) states "6 simulator bays", "up to 60 guests" and the phone number `905-555-0000`. All three conflict with the brief and the real number. | That file was not modified. Fix or retire it before it is used anywhere (see §8). |
| 8 | Directory reviews mention drink prices. | No pricing is shown for the bar. The copy stays on atmosphere and does not promote alcohol. |

### 1.3 Preservation matrix: every destination and where it lives now

| Destination | URL | Header menu | Homepage location(s) | Footer | How it is tested |
|---|---|---|---|---|---|
| Home | `https://thebogeyclub.com/` | Logo | – | – | check-links + click |
| **Book Golf** | `https://thebogeyclub.golfbooking.ca/calendar.php` | Header button + mobile menu | Hero, card 01, golf section, pricing card, visit section, FAQ, mobile booking bar | Play | Complete a test booking flow up to payment |
| Our Simulators | `/our-simulators/` | Golf Simulators | Golf section | Play | check-links |
| **Karaoke** | `/karaoke/` | **Karaoke (new)** | Hero, card 02, karaoke section, FAQ | Play | check-links |
| **Karaoke booking** | *copy from /karaoke/* → set `KARAOKE_BOOKING_URL` in `bch.js` | Mobile menu button | Karaoke section, visit section, mobile booking bar | – | Confirm it opens the karaoke flow, **not** the golf calendar |
| **Private Events** | `/private-events/` | Private Events | Hero (→ #bch-events), card 03, events section, FAQ | Events | check-links |
| Event inquiry form | existing form widget | – | `#bch-plan` block (existing widget moved/duplicated here) | – | Submit a test inquiry and confirm the email arrives |
| Leagues & Events | `/leagues-and-events/` | Leagues & Events | Card 04, scorecard rows 01–02 | Events | check-links |
| Private Lessons | `/meet-our-pro/` | Private Lessons | Scorecard row 03, FAQ | Play | check-links |
| Shop | `https://shop.thebogeyclub.com/` | Shop | Shop section and 4 product links | Club | check-links (product handles verified via Shopify) |
| About Us | `/about-us/` | – | – | Club | check-links |
| FAQs / Contact | `/faqs/` | Contact / FAQs | FAQ "All FAQs" link | Club + base | check-links |
| Bogey Club Classic | `/the-bogey-club-classic-2026/` | – | Scorecard row 04 | Events | check-links |
| Summer camp | `/summer-camp/` (found in search) | – | – | *add if still offered* | Confirm whether it is current |
| Newsletter signup | existing widget | – | – | Newsletter slot | Sign up with a test address |
| Phone / email | `tel:+19052354653`, `mailto:golf@thebogeyclub.com` | Mobile menu | Hero meta, events inquiry, visit, FAQ | Address | Tap on iPhone and Android |
| Directions | Google Maps `dir` link | – | Visit | – | Opens Maps with the destination prefilled |
| Instagram | `https://instagram.com/thebogeyclub` | – | – | Social | Confirm the handle is correct |
| Privacy policy | `/privacy-policy/` *(assumed)* | – | – | Base | **Copy the exact URL from the live footer** |
| Testimonials | existing | – | Reviews section (hidden until filled) | – | Paste real reviews, then remove `hidden` |
| Sponsors | existing | – | *not visible from here* | – | **Copy any sponsor links from the live homepage into the footer "Club" column** |

---

## 2. Creative concept: "Front Nine / Back Nine"

The homepage is laid out like one round at the club, from day to night.

1. **Tee box (hero):** a dark clubhouse-green hero with a cream "official scorecard" that summarises the venue: 4 simulators, 2 bars, beer on tap, karaoke, event space, "Reasons to leave early: 0".
2. **Front nine (daylight / cream):** choose your experience, then the golf.
3. **The Turn:** a gradient band that fades from clubhouse green to midnight. This is where the **two bars** appear, the moment the day turns into the night.
4. **Back nine (evening):** private events, the inquiry, then karaoke in midnight with an animated lime equaliser.
5. **The card (scorecard table):** leagues, the Bogey Cup, lessons and the Classic.
6. **19th hole:** pricing, shop, visit, FAQ and a footer reading "The round ends. The night doesn't."

### 2.1 Palette (as specified)

| Token | Hex | Use |
|---|---|---|
| Deep Clubhouse Green | `#103B2D` | Hero, golf, visit, inquiry, header |
| Fairway Green | `#287A4B` | Accents on light sections, large text only (4.19:1 on cream) |
| Golf Ball White | `#F8F8F0` | Page base |
| Scorecard Cream | `#EAE5D6` | Light sections, the scorecard |
| Electric Lime | `#D6F36A` | Primary CTAs, highlights, ticker (always with ink text) |
| Midnight Charcoal | `#142019` | Night sections, body text |
| Sandstone | `#CCBB93` | Serif accents, small labels on dark |

All text passes **WCAG 2.1 AA** contrast. axe-core 4.10 reports 0 violations at 375, 768 and 1440 px.

### 2.2 Typography

- **Bricolage Grotesque** (800, condensed `wdth` 85%), uppercase, for display text. It reads as tournament-poster lettering without falling back on Anton or Oswald.
- **Instrument Serif Italic** for clubhouse-style accents ("Great nights.", "The crowd isn't.").
- **Manrope** (400–800) for body text and UI.
- All three load from Google Fonts with `display=swap` and preconnect.

### 2.3 Signature details

- A scorecard table in the hero.
- "Hole / Par" tags on each section.
- Fairway mowing-stripe textures.
- Perforated scorecard dividers.
- A lime highlights ticker (static under reduced motion).
- A karaoke equaliser.
- "The Turn" sky gradient.

There are no cartoon golf balls, no stock vectors and no carousels.

### 2.4 Photography: real venue photos only

All photo slots point to `https://thebogeyclub.com/wp-content/uploads/bogey-home/<file>.webp`. Upload the real photos to the Media Library, then replace each `src` with its Media Library URL. Keep the SEO filenames listed here. Until a photo exists, the slot shows a designed fairway-stripe panel instead of a broken image, so the layout never shifts.

| Slot | Filename | Subject to shoot / supply | Size |
|---|---|---|---|
| Hero | `the-bogey-club-indoor-golf-bar-newmarket.webp` | Wide evening shot: bar in the foreground, lit bays behind | 1920×1280, ≤180 KB |
| Card 01 | `golfzon-twovision-nx-simulator-bay-newmarket.webp` | One bay mid-swing | 800×500 |
| Card 02 | `karaoke-night-the-bogey-club-newmarket.webp` | Group singing | 800×500 |
| Card 03 | `private-event-space-the-bogey-club-newmarket.webp` | Upstairs space set for a party | 800×500 |
| Card 04 | `golf-league-night-the-bogey-club.webp` | League night or leaderboard | 800×500 |
| Golf A | `golfzon-twovision-nx-bays-the-bogey-club.webp` | TwoVision NX bay, portrait | 900×1200 |
| Golf B | `golfzon-vision-plus-simulator-newmarket.webp` | Vision Plus bay | 800×800 |
| Bar 01 | `bar-one-the-bogey-club-newmarket.webp` | Main bar | 1200×900 |
| Bar 02 | `bar-two-event-space-the-bogey-club.webp` | Event-space bar | 1200×900 |
| Draft | `draft-beer-on-tap-the-bogey-club-newmarket.webp` | Tap handles or a pour (no faces of minors, no excess) | 600×600 |
| Events | `birthday-party-venue-newmarket-the-bogey-club.webp` | Real event in progress (with guest consent) | 1600×1000 |
| Karaoke | `karaoke-setup-the-bogey-club-newmarket.webp` | Karaoke screen and mics | 960×1200 |

Merchandise images come straight from the Shopify CDN, resized with `&width=600`.

### 2.5 Mobile navigation

- **Sticky header:** logo, a lime **Book Golf** button and a 48 px hamburger.
- **Menu:** full-screen, with seven large items in the brief's order, then Book Golf, Book Karaoke and Plan an Event, then tap-to-call, email and address. It closes on Esc and on link tap, focus moves into the menu, and the page behind it doesn't scroll.
- **Bottom bar:** **Book Golf | Book Karaoke**. It slides in only after the hero's own buttons scroll off-screen, so it never duplicates them, and it respects the iOS safe-area inset.

---

## 3. Homepage copy

The copy lives in `homepage/src/home-1.html` and `homepage/src/home-2.html`. Section order:

| § | Headline | Primary CTA |
|---|---|---|
| A Hero (H1) | Kicker: *Indoor golf, bar, karaoke & private events · Newmarket, Ontario*. Display: **Good golf. Cold beer. *Great nights.*** | Book Golf · Explore Karaoke · Plan an Event |
| B Experiences | **One club. Four ways to play.** | Book a bay / Explore karaoke / Plan your event / View leagues & events |
| C Golf | **Four bays. Real golf. No weather.** | Book a bay · Explore our simulators |
| D Bars | **Two bars. Zero reason to leave early.** + "Cold beer on tap. Good company on call." | (alcohol note: golf vs. staffed bar service, 19+) |
| E Events | **Your party. Our clubhouse.** | Plan your event · Explore our event space |
| E2 Inquiry | **Put your party on the tee sheet.** | (existing form) |
| F Karaoke | **The course is quiet. *The crowd isn't.*** | Book karaoke · Explore karaoke |
| G Leagues | **Keep score. Or don't.** | Details / Join a league / Meet our pro / See the Classic |
| H How it works | **Book in a minute. Swing all night.** | Book your bay |
| I Reviews | **Don't take our word for it.** (hidden until real reviews are added) | Read our Google reviews |
| J Shop | **Look good. Play average.** | Shop the club |
| K Visit | **Find the 19th hole.** | Directions · Call · Email · Book |
| L FAQ | **Before you tee off.** | All FAQs |
| Footer | **The round ends. The night doesn't.** | Newsletter |

---

## 4. Build: files and where each goes

| dist file | Where it goes in WordPress |
|---|---|
| `00-head-fonts.html` | WPCode → "Site-wide header" snippet. Skip it if Elementor already loads these fonts. |
| `01-global-styles.css` | Elementor → Site Settings → Custom CSS (Pro), **or** WPCode CSS snippet (site-wide), **or** Appearance → Customize → Additional CSS |
| `02-global-script.html` | WPCode → "Site-wide footer" snippet (4 KB, no dependencies) |
| `03-header.html` | Theme Builder → Header → HTML widget (see §6.1) |
| `04-home-part-1.html` | Homepage → HTML widget #1 |
| `05-event-inquiry-intro.html` + existing form | Homepage → inquiry container (see §6.3) |
| `06-home-part-2.html` | Homepage → HTML widget #2 |
| `07-footer.html` | Theme Builder → Footer → HTML widget (the newsletter widget goes beside it, see §6.5) |
| `08-mobile-bookbar.html` | Bottom of the footer template (site-wide) |
| `09-schema-homepage.html` | WPCode → header snippet, **homepage only** (see §5.4) |

Notes:

- All CSS classes are namespaced with `bch-`.
- The JavaScript is vanilla. It has no framework, no build step at install time and no tracking.
- CTAs carry `data-bch-cta="…"` attributes, so the site's **existing** GTM/GA can add click triggers without a second analytics tag.

---

## 5. Technical SEO

### 5.1 Title and meta description

Set these in Yoast or Rank Math on the homepage:

- **SEO title (64 chars; Google may trim the last word):** `The Bogey Club | Indoor Golf, Bar, Karaoke & Events in Newmarket`
  - Shorter alternative (55): `Indoor Golf, Karaoke & Event Space Newmarket | Bogey Club`
- **Meta description (≈150 chars):** `Four Golfzon simulators, two full bars, cold beer on tap, karaoke and private event space in Newmarket, Ontario. Book golf or plan your party.`
- **Canonical:** `https://thebogeyclub.com/`. Let the SEO plugin output it.
- **Open Graph image:** use the hero photo at 1200×630.

`preview.html` contains `noindex` on purpose. **Do not copy its `<head>` into WordPress.**

### 5.2 Heading structure

```
H1  Indoor golf, bar, karaoke & private events · Newmarket, Ontario — Good golf. Cold beer. Great nights.
 H2 One club. Four ways to play.          H3 ×4 Play golf / Grab the mic / Host your event / Join the fun
 H2 Four bays. Real golf. No weather.
 H2 Two bars. Zero reason to leave early. H3 Cold beer on tap…
 H2 Your party. Our clubhouse.
 H2 Put your party on the tee sheet.
 H2 The course is quiet. The crowd isn't.
 H2 Keep score. Or don't.
 H2 Book in a minute. Swing all night.    H3 ×4 steps
 H2 Don't take our word for it.           (when enabled)
 H2 Look good. Play average.
 H2 Find the 19th hole.
 H2 Before you tee off.
```

Make sure the theme does not also print the page title as a second H1. In Elementor: Page Settings → Hide Title.

### 5.3 Internal links

Each service page gets 3–6 contextual links with descriptive anchors: "Explore our simulators", "Explore our event space", "Explore karaoke", "Meet our pro", "View leagues & events", "All FAQs".

The same links appear in the global header and footer, so every service page is one click from every page.

### 5.4 Structured data (`homepage/src/schema.jsonld`)

The data is one `@graph`:

- **LocalBusiness** typed `SportsActivityLocation` + `BarOrPub` + `EntertainmentBusiness`, with address, phone, email, `areaServed` (Newmarket, Aurora, East Gwillimbury) and `amenityFeature` (4 Golfzon simulators, two full bars, draft beer, karaoke, private event space).
- `ReserveAction` → golf booking.
- An `OfferCatalog` with five **Service** entities: simulator rental ($60 CAD per hour, tax extra), karaoke, private event space, lessons, leagues.
- **WebPage**.

The two bars are amenities of one business, not separate listings.

Deliberately left out until verified:

- `openingHoursSpecification`
- `geo` coordinates
- `aggregateRating` / `review` (self-serving review markup is not eligible for stars anyway)
- `priceRange`
- `FAQPage` (Google limits FAQ rich results to authoritative government and health sites. The FAQ stays as visible HTML, which assistants can still read.)

Avoiding duplicates:

- If Yoast or Rank Math already outputs an Organization or LocalBusiness node for the homepage, either turn off its local-business output or delete the LocalBusiness node here. Keep only one.
- Add `"isPartOf": {"@id": "https://thebogeyclub.com/#website"}` to the WebPage node so it links to the plugin's WebSite node.

Validate with the [Schema Markup Validator](https://validator.schema.org/) and the [Rich Results Test](https://search.google.com/test/rich-results).

### 5.5 Image SEO

- Use the descriptive filenames in §2.4 and the alt text already written in the markup.
- Serve WebP or AVIF.
- Every `<img>` has `width`/`height` set to prevent layout shift.
- The hero image uses `fetchpriority="high"`. All other images use `loading="lazy"`.

### 5.6 Local search and AI discoverability

1. **Google Business Profile:**
   - Primary category: *Indoor golf course* (or *Golf driving range* if that is the only option).
   - Secondary categories: *Bar*, *Karaoke bar*, *Event venue*, *Banquet hall* (only if accurate).
   - Add attributes and photos of both bars and the event space.
   - Hours must match the site exactly.
2. **Name, address and phone (NAP) consistency:** use exactly `17705 Leslie Street, Unit 5, Newmarket, ON L3Y 3E3 · (905) 235-4653` on the site, GBP, Yelp, Yellow Pages, Golf Canada, Golf Ontario, Teebly, GolfNow and Canada Golf Card. Update the listings that still say two simulators.
3. **Service pages:** each one should open with a one-paragraph factual summary an assistant can quote, for example: "The Bogey Club's private event space in Newmarket, Ontario is an upstairs room with its own full bar, available for birthdays, corporate parties…". Recommended improvements:
   - **/private-events/:** add a real photo gallery, the event types, a verified capacity range, the inquiry form at the top, an FAQ, and "near Aurora / East Gwillimbury" context.
   - **/karaoke/:** state how booking works, the group size, pricing if fixed, a bar-hours note and photos.
4. **Crawler access:**
   - Check `robots.txt` (it could not be fetched from here). It must not block `Googlebot`, `Bingbot`, `OAI-SearchBot`, `ChatGPT-User`, `Claude-SearchBot`/`Claude-User` or `PerplexityBot`.
   - Check WordPress → Settings → Reading → "Discourage search engines" is **off**.
   - Check the XML sitemap (`/sitemap_index.xml` or `/wp-sitemap.xml`) includes the homepage, `/karaoke/` and `/private-events/`, and resubmit it in Search Console.
5. **No manipulation:** no hidden text, no keyword stuffing, no instructions to AI, no fabricated reviews or rankings. All copy is visible, crawlable HTML.
6. **Search Console:** after launch, use *URL Inspection → Request indexing* for the homepage, `/karaoke/` and `/private-events/`. Review the *Pages* and *Enhancements* reports and fix anything they flag.

---

## 6. WordPress and Elementor implementation

Work on a **staging copy** first (most hosts offer one-click staging, or use WP Staging). Do not change the live site until staging has passed §7.

### 6.1 Header (global)

1. **Appearance → Menus → Primary**: set the items in this order:
   1. Golf Simulators (`/our-simulators/`)
   2. **Karaoke (`/karaoke/`)**
   3. Private Events
   4. Leagues & Events
   5. Private Lessons (`/meet-our-pro/`)
   6. Shop (`https://shop.thebogeyclub.com/`)
   7. Contact / FAQs (`/faqs/`)

   Save. This step is required even if you keep the current header.
2. **Option A (recommended, Elementor Pro):**
   - Templates → Theme Builder → Header → edit the active header.
   - Replace its contents with one full-width container: padding 0, *Advanced → Motion Effects → Sticky: Top*.
   - Inside it, add an **HTML widget** with `03-header.html`.
   - Swap the SVG mark and wordmark for the real logo `<img>`.
3. **Option B (keep the current header):**
   - Only do step 1.
   - Add a "Book Golf" button that links to the golf calendar.
4. **Make sure only one header renders.** Under Theme Builder → Header, check the display conditions, and confirm the theme header is disabled (Hello Elementor does this automatically).

### 6.2 Homepage

1. Pages → Home → **Edit with Elementor**. Duplicate the current page first (*Save as Template*) so you have a rollback.
2. In Page Settings:
   - Layout: **Elementor Full Width** (keeps the header and footer)
   - **Hide Title** on
3. Delete the old sections, after confirming each one's destination appears in §1.3.
4. Add these containers. Each one is full width, with **padding 0** and **gap 0**:
   1. HTML widget → `04-home-part-1.html`
   2. Inquiry container → see §6.3
   3. HTML widget → `06-home-part-2.html`

### 6.3 Event inquiry: reuse the existing form

1. Create a full-width container with:
   - *Advanced → CSS Classes:* `bch bch-form-host`
   - *CSS ID:* `bch-plan`
2. Inside it, add an inner boxed container with CSS class `bch-wrap bch-form-host__grid`. It holds two children:
   - An HTML widget → `05-event-inquiry-intro.html`
   - A container with CSS class `bch-form-panel`, containing the **existing** private-events form. Copy/paste the Elementor Form widget, or insert the same WPForms/CF7/Gravity shortcode.
3. Keep the form's existing actions: email recipients, webhook/CRM, reCAPTCHA or honeypot, and the redirect or success message.
4. Recommended fields: Name, Email, Phone, Preferred event date, Estimated guests, Type of event (Birthday · Corporate · Holiday/Christmas · Team building · Bachelor/bachelorette · Group celebration · Other), Anything else (optional). Mark all except the last as required.
5. Set the **form name** so submissions from the homepage are distinguishable, for example "Event inquiry – Homepage".

### 6.4 Karaoke booking

1. Open `/karaoke/` and copy the URL behind its booking button.
2. Paste it into `KARAOKE_BOOKING_URL` at the top of `02-global-script.html`.
3. Every "Book Karaoke" CTA (header menu, karaoke section, visit section, mobile bar) will then use it.
4. Until it is set, those CTAs open `/karaoke/`. They never open the golf calendar.

### 6.5 Footer and newsletter

1. Theme Builder → Footer:
   - Add an HTML widget with `07-footer.html`.
   - Move the **existing** newsletter widget into the Newsletter column. The simplest way is to split the footer HTML at the `<!--BCH:NEWSLETTER-->` marker into two HTML widgets and place the newsletter widget between them.
   - Add an HTML widget with `08-mobile-bookbar.html` at the very end.
2. Copy over every legal, social and sponsor link from the current footer.

### 6.6 Performance settings

| Where | Setting |
|---|---|
| Elementor → Settings → Features | Turn on *Optimized DOM Output*, *Improved Asset Loading*, *Improved CSS Loading* and *Inline Font Icons* |
| Elementor → Settings → Advanced | If the kit fonts are unused, set *Google Fonts* to off so the fonts don't load twice |
| Caching plugin (WP Rocket / LiteSpeed) | Page caching, CSS minify, delay JS **except** `02-global-script` (it is tiny and drives the menu) |
| Images | WebP/AVIF via the host, ShortPixel or Imagify. Do not lazy-load the hero image. |

Targets on a 4G mid-tier phone: LCP ≤ 2.5 s, INP ≤ 200 ms, CLS ≤ 0.1. Measure with PageSpeed Insights.

---

## 7. Testing

### 7.1 Already run in this session (against `preview.html`, Playwright + Chromium)

| Check | Result |
|---|---|
| Horizontal overflow at 375 / 768 / 1440 px | none (`scrollWidth == clientWidth`) |
| axe-core 4.10 WCAG 2 A/AA | **0 violations** at all three widths |
| Exactly one H1 | ✓ |
| Tap targets < 40 px (outside inline text links) | none |
| Mobile menu | opens, sets `aria-expanded`, moves focus to the first link, closes on Esc |
| Booking bar | hidden at the top of the page, appears after scrolling past the hero CTAs |
| JavaScript errors | none |
| HTML tag balance, duplicate IDs, in-page anchors | balanced, no duplicates, all anchors resolve |
| JSON-LD | parses as valid JSON (Schema Validator still to run on staging) |
| Shopify products | 4 linked products confirmed **active** and in stock on 2026-10-21 |

### 7.2 Run on staging before launch

- [ ] `homepage/tools/check-links.sh`: every destination returns 200.
- [ ] Golf booking: open the calendar from the hero, the bar and the pricing card. Pick a slot and reach the checkout.
- [ ] Karaoke booking opens the **karaoke** flow from all four CTAs.
- [ ] Event inquiry: a submission reaches `golf@thebogeyclub.com` (or the current recipient) and the spam protection still works.
- [ ] Other contact forms on `/faqs/` and `/private-events/` still work.
- [ ] Newsletter signup adds a test address to the list.
- [ ] Shop: all four product links and "Shop the club" work.
- [ ] Karaoke appears in the header on **every** page, not only the homepage.
- [ ] Bar copy is accurate (see §8). The event space is reachable within one tap from the hero.
- [ ] Tested in iPhone Safari and Android Chrome: the menu, the bottom bar, `tel:` and `mailto:` links, and the Maps directions link.
- [ ] Only one header and one footer render. GA/GTM fires once per page view (check Tag Assistant).
- [ ] PageSpeed Insights mobile meets the Core Web Vitals targets.
- [ ] Rich Results Test and Schema Validator show no errors, and there is no duplicate LocalBusiness node.
- [ ] `robots.txt`, the "Discourage search engines" setting and the sitemap are correct (§5.6).

---

## 8. Verify before publishing

| Item | Current value in build | Source / status |
|---|---|---|
| Golf hours | Mon–Sun 9 AM – 12 AM | Site listing; conflicts with "summer hours". **Confirm.** |
| Staffed bar hours | "Call for today's bar hours" | Unknown. **Replace with real hours.** |
| Price | $60/hr per bay + HST, up to 4 players | From the brief. **Confirm.** (Also in the JSON-LD.) |
| Clubs | "Bring your own or ask us" | Listings mention rentals. **Confirm wording.** |
| Bar names | "The clubhouse bar" / "The event-space bar" (upstairs) | Inferred from the brief. **Confirm the names and that the second bar is in the event space.** |
| Draft beer | "Cold beer on tap" (venue-wide, not per bar) | From the brief. Not claimed per bar. |
| Men's Night | Row 02, "Active" | From the brief. Not found in search. **Confirm the schedule.** |
| Bogey Club Classic | 2026 edition played June 4 at Bond Head | Public page. Update when 2027 is announced. |
| Instagram | `instagram.com/thebogeyclub` | From the old repo file. **Confirm the handle.** Add Facebook/TikTok if they exist. |
| Privacy policy URL | `/privacy-policy/` | Assumed. **Copy from the live footer.** |
| Karaoke booking URL | not set | **Copy from /karaoke/.** |
| Reviews | section hidden | **Paste 3 real reviews**, then remove `hidden`. |
| Logo | placeholder SVG monogram + wordmark | **Swap in the real logo.** |
| Photos | 12 slots, fallback pattern | **Upload real venue photos.** |
| Summer camp / sponsors | not linked | **Decide whether to keep them**, then add to the footer. |
| Repo `index.html` | says 6 bays, 60 guests, phone 905-555-0000 | **Out of date. Fix or retire it before using.** |
