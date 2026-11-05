# Kearley Group — Website

A single-page editorial website for Kearley Group, built with [Astro](https://astro.build) as a fully
static site. It has no database, no backend and no CMS, and runs almost no JavaScript (about 3 KB).

## Quick start

```bash
npm install
npm run dev      # local preview at http://localhost:4321
npm run build    # production build → ./dist
```

## Editing content

| What | Where |
| --- | --- |
| **Companies** (names, sectors, descriptions, images, logos, links, order) | `src/data/companies.ts` |
| Site URL, hero headline, contact email, location, navigation | `src/data/site.ts` |
| The Group introduction | `src/components/Group.astro` |
| Philosophy statement | `src/components/Philosophy.astro` |
| Colours and type scale | `src/styles/global.css` (`:root`) |

### Adding a company
Open `src/data/companies.ts`, copy an existing entry, change the `slug`, and pick a `layout`:
`cover`, `diptych`, `minimal`, `panorama` or `typographic`. Each one is a different magazine-style
spread. Alternate them so the portfolio never repeats itself. The contents index, footer,
mobile menu, company count and structured data all update automatically.

### Images and logos
* Put files in `public/images/` (logos in `public/images/logos/`) and reference them as `/images/…`.
* **Every image currently referenced is a temporary Unsplash placeholder.** Replace each one with
  photography you own or have licensed before launch.
* If an image is missing or fails to load, the site shows a quiet tonal panel instead of a broken image.
* Until a `logo` is set, each company shows a monogram plate.

## Before launch: checklist
- [ ] `src/data/site.ts` → set `url` to the real domain, and confirm the `email` and `location`
- [ ] `public/robots.txt` → update the sitemap URL to the real domain
- [ ] `src/data/companies.ts` → confirm each website `url` (all are blank except Benson Kearley IFG's, which still needs confirming), plus the descriptions, and the details for Condo Corp and CaddyChat
- [ ] Replace all placeholder photography with licensed images, and add the logos

## Deploying with a GoDaddy domain

**Option A: Netlify, Vercel or Cloudflare Pages (recommended; free, fast, automatic HTTPS)**
1. Connect this repository. Set the *base / root directory* to `kearley-group`, the build command to `npm run build` and the output to `dist`.
2. Add your domain in the host's dashboard. It will show the DNS records it needs.
3. In GoDaddy → *My Products → DNS*, add those records (usually an `A` record for `@` and a `CNAME` for `www`).
   HTTPS is issued automatically once DNS resolves.

**Option B: GoDaddy web hosting (cPanel)**
1. Run `npm run build`.
2. Upload the *contents* of `dist/` into `public_html` using the cPanel File Manager or FTP.
   The included `.htaccess` forces HTTPS and sets caching headers.
