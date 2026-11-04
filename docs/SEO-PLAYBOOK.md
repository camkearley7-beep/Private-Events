# SEO & AI search playbook: The Bogey Club

What was changed in code (this repo), what still needs doing in WordPress, and what needs your accounts. Updated November 4, 2026.

## 0. Scope and access

- **Done in code:** the three pages built in this repo — Homepage, Meet Our Pro and Leagues & Events (`site/dist/ELEMENTOR-PASTE-*.txt`). Nothing is live until you re-paste them.
- **Not reachable from here:** the live site, WordPress admin, plugins, robots.txt, sitemaps, Google Search Console, GA4 and the Google Business Profile.
  - The work session's network blocks thebogeyclub.com, so the live site couldn't be audited directly. To allow it, add `thebogeyclub.com` under Network access in the environment settings.
  - Everything in sections 2–6 is step-by-step for you to do (about an hour), or for me once I have access.
- **Not rebuilt yet:** Our Simulators, Private Events and Karaoke. These are the main landing pages for "golf simulator Newmarket", "private party venue Newmarket" and "corporate events Newmarket", so they're the biggest remaining opportunity (section 7).

## 1. What changed in this release

| Area | Change |
| --- | --- |
| Pricing in schema | Removed the outdated flat **$60/hour**. Added the real tiered rates as an `AggregateOffer` ($49.99–$69.99, per bay per hour, plus HST), with the four time bands matching the visible rates section. |
| Hours in schema | Two departments: **bar side + 2 staffed bays, daily 9 AM–12 AM**, and **2 automated bays, 24/7**. Automated access and staffed bar hours are now separate, and drinks are only claimed for bar hours. |
| Entity data | Added the `WebSite` entity (the page pointed at one that didn't exist), plus the logo, main photo and map link. Service area is now Newmarket, Aurora, East Gwillimbury, Bradford West Gwillimbury and York Region. Added "fully licensed", "cocktails" and "automated bays open 24/7". |
| Homepage FAQs | New answers written to be quoted on their own: "Where is The Bogey Club?", "Is there a 24-hour golf simulator near Newmarket or Aurora?", and "Can I book a Golfzon simulator online?" (folded into the booking answer). Drinks answer now mentions cocktails. Added `FAQPage` markup built from the same file, so every question in the markup is visible in the FAQs panel. |
| Homepage copy | Hero lede mentions draft beer and cocktails. The Visit block's "Nearby" row now reads **Serving: Newmarket · Aurora · East Gwillimbury · Bradford · York Region**. |
| Leagues page | Removed a second `fetchpriority="high"` that competed with the header photo for loading priority. |
| Lessons schema | Same service area as the homepage. |
| Booking tracking | Clicks are sent to your existing GA4 (`gtag`) and/or Tag Manager (`dataLayer`), if present: `booking_click`, `karaoke_booking_click`, `league_registration_click`, `phone_click`, `email_click`, `directions_click`. Each carries `cta` (which button) and `page_path`. The script loads no analytics itself. These are clicks; completed bookings happen on golfbooking.ca. |
| Font switch | `FONT_URL` setting in `site/build.sh` (section 5). |
| `llms.txt` | Hand-written, factual summary with links: `site/dist/llms.txt` (section 4). |

**Checked:**
- All JSON-LD parses, and every internal `@id` reference resolves.
- The schema matches the visible content (prices, hours, FAQs).
- One H1 per page, and the heading order is logical.
- Every image has alt text, and the hero/LCP images are not lazy-loaded.
- Layout shift (CLS): 0.000–0.019 on phone and desktop. Google's "good" threshold is 0.1.
- Accessibility: axe WCAG 2 A/AA, 0 violations.
- No JavaScript errors, and the booking links and forms are unchanged.

## 2. WordPress: All in One SEO (installed, currently inactive)

Activate it: **Plugins → All in One SEO → Activate**. Then:

1. **Search Appearance → Global Settings**
   - Site title: `The Bogey Club`
   - Separator: `|`
   - Knowledge Graph: **Organization**, name `The Bogey Club`
   - Logo: `Bogey-Club-Font-Logo-Green.png`
   - Phone: `+1-905-235-4653`
2. **Avoid duplicate business markup.** The pages already carry full `LocalBusiness` markup. If AIOSEO offers a Local Business / Local SEO module, leave it off.
   - AIOSEO's own WebSite/WebPage/Breadcrumb output can stay; it uses its own IDs and doesn't contradict ours.
   - Afterwards, test the homepage in Google's Rich Results Test: there should be **one** local business (`The Bogey Club`), not two with different details.
3. **Sitemaps → General Sitemap: On.** It lives at `https://thebogeyclub.com/sitemap.xml`. Exclude:
   - drafts and the old homepage
   - the About Us page, while it's unpublished
   - attachment pages (Search Appearance → Media → Redirect attachment URLs → **Attachment Parent**)
4. **Social Networks → Facebook:** set the default image to a photo of the club, so shared links show a picture.
5. **Titles and descriptions** (done Nov 4; titles shortened to fit Google's 600-pixel limit): for each page, open it in WordPress, scroll to the **AIOSEO Settings** box, and paste these:

| Page | SEO title | Meta description |
| --- | --- | --- |
| Home | `The Bogey Club \| Golf Simulator Bar in Newmarket` | `Four Golfzon simulators with two automated bays open 24/7, plus draft beer, cocktails, karaoke and private events in Newmarket. From $49.99 per bay per hour.` |
| Our Simulators | `Golfzon Golf Simulators in Newmarket \| The Bogey Club` | `Play 300+ courses on Golfzon TwoVision NX and Vision Plus simulators in Newmarket. Up to 4 players a bay, two bays open 24/7. Book a bay online.` |
| Private Events | `Party & Corporate Event Venue in Newmarket \| The Bogey Club` | `Birthdays, corporate events and team nights in our upstairs event space with its own full bar, golf simulators and karaoke. Newmarket, serving York Region.` |
| Karaoke | `Karaoke in Newmarket \| The Bogey Club` | `Grab the mic at The Bogey Club in Newmarket. Book karaoke online and pair it with golf simulators, draft beer and cocktails.` |
| Leagues & Events | `Golf Leagues & Men's Night in Newmarket \| The Bogey Club` | `Men's Night every Tuesday, The Bogey Cup monthly and The Bogey Club Classic. Indoor golf leagues on Golfzon simulators in Newmarket.` |
| Meet Our Pro | `Private Golf Lessons in Newmarket \| PGA Pro \| The Bogey Club` | `Private golf lessons in Newmarket with Andrew McDonald, Associate Professional, PGA of Canada. One-on-one indoor lessons on Golfzon simulators for every level.` |
| FAQs | `FAQs: Hours, Pricing & Booking \| The Bogey Club Newmarket` | `Hours, bay rates, 24/7 automated bays, booking, food and drinks, kids and private events at The Bogey Club in Newmarket.` |

The Karaoke description only says what is already on the site. Add specifics (rooms, group size, prices) once confirmed.

## 3. robots.txt

Under AIOSEO → Tools → Robots.txt Editor, the file should be no stricter than this:

```
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: https://thebogeyclub.com/sitemap.xml
```

- **Search and AI crawlers:** Googlebot, Bingbot, OAI-SearchBot (ChatGPT search), PerplexityBot and others are allowed by default. Don't add rules for them.
- **Blocked crawlers:** if you see `Disallow` lines for `GPTBot`, `Google-Extended`, `CCBot` or similar, those are AI-training crawlers. Blocking them is your choice and doesn't affect Google Search or ChatGPT search results.
- **Host and security settings:** check that your host or security plugin (e.g. Wordfence, a host "AI bot blocking" toggle) isn't blocking the crawlers above.

## 4. llms.txt

`site/dist/llms.txt` is a short, factual summary of the club with links to the key pages.
- **Install:** upload it to the site root, so it opens at `https://thebogeyclub.com/llms.txt`. Use the host's File Manager or SFTP (the folder that contains `wp-config.php`).
- **AIOSEO's version:** some SEO plugins, including recent AIOSEO versions, can generate one automatically. Use this hand-written one instead and turn the generated one off, because it has the correct rates and hours.
- **Be realistic:** there is no evidence yet that llms.txt improves rankings or AI citations. It's cheap, accurate and harmless. What actually helps AI answers is the visible text and structured data on the pages, which is where most of this work went.
- **Keep it current:** update it whenever prices or hours change.

## 5. Speed

1. **Font (biggest code-side saving) — done Nov 4: uploaded to `/wp-content/uploads/2026/11/`, `FONT_URL` set.** Every page currently carries the Satoshi font inside it (57 KB per page, re-downloaded on every page). Once it's uploaded, each page is about 25% smaller and the font is cached after the first visit.
   - Upload `site/dist/uploads/fonts/Satoshi-Variable.woff2` via the host's File Manager to `/wp-content/uploads/2026/11/`. WordPress's media uploader usually refuses `.woff2` files.
   - Send me the URL, or set it as `FONT_URL` in `site/build.sh` and rebuild.
2. **Images.** Several large images are PNGs, which are usually several times bigger than WebP for photos: `Main-Area.png`, `website-banner-heritage-hat.png` and `TaylorMade-P·790-Golf-Simulator-Setup.png`.
   - Convert them to WebP (max 2000 px wide, quality ~80) with Squoosh (free, in the browser) or an image plugin.
   - Re-upload them and send me the new URLs.
3. **Caching.** If the host has no page cache, enable one (host cache or a cache plugin).
   - With any "minify/combine JS" option, exclude the Elementor HTML widget's inline script. Combining it can break the page script.
4. **Measure** before and after with PageSpeed Insights (pagespeed.web.dev) for the homepage on mobile. Record LCP, INP, CLS and the score here.

| Date | Page | LCP | INP | CLS | Score |
| --- | --- | --- | --- | --- | --- |
| baseline | Home (mobile) | | | | |

## 6. Google accounts

- **Google Business Profile:** make it match the site exactly.
  - Name `The Bogey Club`; address `17705 Leslie Street, Unit 5, Newmarket, ON L3Y 3E3`; phone `(905) 235-4653`; website `https://thebogeyclub.com/`.
  - Regular hours: decide what Google should show. The usual choice is the staffed hours (9 AM–12 AM). Then add the 24/7 access under **More hours** (e.g. "Access" or "Online service hours") and mention it in the description.
  - Primary category: "Golf driving range" or "Indoor golf course"? Check which is offered. Add "Bar" and "Event venue" as secondary categories.
  - Booking link: the golfbooking.ca calendar.
- **Google Search Console:**
  - Add the property `https://thebogeyclub.com` (Domain or URL-prefix).
  - Verify with AIOSEO → General Settings → Webmaster Tools → Google (paste the code).
  - Submit `sitemap.xml`, then use URL Inspection → Request indexing for the homepage, Meet Our Pro and Leagues & Events.
- **Bing Webmaster Tools:** sign in and choose **Import from Google Search Console**. Bing also feeds Copilot and other AI search tools.
- **GA4:**
  - Install it in exactly **one** place: AIOSEO/MonsterInsights, Site Kit, *or* GTM. Two installs double-count.
  - In GA4 → Admin → Events, mark `booking_click`, `karaoke_booking_click` and `league_registration_click` as **key events**.
  - Form leads: WPForms can confirm submissions by redirecting to a thank-you page. Track that page view as a lead.
  - Completed bookings can only be measured if golfbooking.ca supports GA4 or conversion tracking. Ask them.
- **Privacy:** the privacy policy already mentions Google Analytics and cookies. If you add advertising pixels, update section 6 of the policy.

## 7. Facts to confirm (marked so they don't become wrong answers)

- **"6 simulator bays" and "up to 60 guests"** appear in this repo's older Private Events page (`/index.html`). Everywhere else says **4 simulators**. If the live Private Events page says 6, fix it. The 60-guest figure needs confirming before it goes in schema or AI summaries.
- **Pricing:** the brief says $60/hour; the site and schema use the tiered rates you gave ($49.99 / $59.99 / $69.99). Kept tiered. Tell me if that has changed.
- Still open from before: rental clubs ($20?), food (menu?), kids policy late on the bar side, holiday pricing, Men's Night weekly price.

## 8. Next build: highest-impact remaining pages

1. **Our Simulators** (`/our-simulators/`): primary page for "golf simulator Newmarket / Aurora", "Golfzon simulator Newmarket" and "24/7 golf simulator".
   - One H1 (e.g. "Golfzon Golf Simulators in Newmarket"), the two models, what you can play, rates, how booking and the 24/7 bays work, a FAQ, `Service` + `Offer` + `BreadcrumbList` markup, and a booking button above the fold.
2. **Private Events** (`/private-events/`): "private party venue / corporate events Newmarket".
   - Real capacity, packages, food and drink, a photo gallery, an inquiry form, and a FAQ.
3. **Karaoke** (`/karaoke/`).

**No separate town pages.** Aurora/Bradford pages would be near-duplicates that Google treats as doorway pages. The service area is covered on the real pages, in the FAQs and in the markup instead.
