/**
 * ─────────────────────────────────────────────────────────────────────────────
 *  KEARLEY GROUP — PORTFOLIO DATA
 *  This is the single file to edit when a company is added, removed or updated.
 * ─────────────────────────────────────────────────────────────────────────────
 *
 *  ADDING A COMPANY
 *  Copy one of the entries below, give it a unique `slug`, and choose a `layout`.
 *  Companies appear on the site in the order they are listed here.
 *
 *  LAYOUTS — each one is a different magazine-style "feature" spread:
 *    'cover'        Tall architectural image, oversized title, offset inset image.
 *    'diptych'      Two staggered images on a warm dark ground. Use sparingly.
 *    'minimal'      Quiet, gallery-like: the name set either side of a small image.
 *    'panorama'     Full-bleed cinematic image with a caption-style text band.
 *    'typographic'  No photography — the name itself is the image.
 *  Layouts can be reused, but alternating them keeps the portfolio feeling curated.
 *
 *  IMAGES
 *  `src` can be a full URL or a file placed in /public/images (e.g. '/images/bkifg.jpg').
 *  Every image currently in this file is a TEMPORARY placeholder from Unsplash.
 *  Replace each with photography you own or have licensed before launch.
 *  If an image fails to load, the site shows a quiet tonal panel instead.
 *
 *  LOGOS
 *  Place logo files in /public/images/logos and set `logo.src`. Until then a
 *  refined monogram plate is shown in its place. SVG is preferred.
 *
 *  WEBSITE LINKS
 *  Leave `url` as '' to show "Website forthcoming" instead of a link.
 */

export type FeatureLayout = 'cover' | 'diptych' | 'minimal' | 'panorama' | 'typographic';

export interface CompanyImage {
  src: string;
  alt: string;
  /** CSS object-position, e.g. 'center 30%'. Optional. */
  focus?: string;
}

export interface Company {
  slug: string;
  name: string;
  /** Optional: split the display name across two lines, the second set in italics. */
  displayName?: [string, string];
  /** Small label shown above the company name. */
  sector: string;
  /** Optional location line. */
  location?: string;
  /** One or two short sentences. */
  description: string;
  /** External website. '' shows "Website forthcoming". */
  url: string;
  logo?: { src: string; alt: string };
  layout: FeatureLayout;
  image?: CompanyImage;
  secondaryImage?: CompanyImage;
  /** Marks a company whose details are still being confirmed. */
  pending?: boolean;
}

const unsplash = (id: string) => `https://images.unsplash.com/${id}`;

export const companies: Company[] = [
  {
    slug: 'benson-kearley-ifg',
    name: 'Benson Kearley IFG',
    displayName: ['Benson Kearley', 'IFG'],
    sector: 'Insurance & Financial Services',
    description:
      'Insurance and financial services. A fuller profile of Benson Kearley IFG will be published here.',
    // TODO: confirm the official website address.
    url: 'https://www.bensonkearleyifg.com',
    layout: 'cover',
    image: {
      src: unsplash('photo-1486406146926-c627a92ad1ab'),
      alt: 'Looking up at a glass office tower against a pale sky',
      focus: 'center 40%',
    },
    secondaryImage: {
      src: unsplash('photo-1497366216548-37526070297c'),
      alt: 'A quiet, light-filled modern office interior',
    },
  },
  {
    slug: 'the-bogey-club',
    name: 'The Bogey Club',
    displayName: ['The', 'Bogey Club'],
    sector: 'Golf & Hospitality',
    description:
      'Indoor golf and hospitality. A fuller profile of The Bogey Club will be published here.',
    url: '', // TODO: add website
    layout: 'diptych',
    image: {
      src: unsplash('photo-1587174486073-ae5e5cff23aa'),
      alt: 'A manicured golf green at first light',
    },
    secondaryImage: {
      src: unsplash('photo-1514933651103-005eec06c04b'),
      alt: 'A dark, warmly lit hospitality interior',
    },
  },
  {
    slug: 'condo-corp',
    name: 'Condo Corp',
    displayName: ['Condo', 'Corp'],
    sector: 'Industry to be confirmed',
    description: 'Further details about Condo Corp will be shared here in due course.',
    url: '',
    layout: 'minimal',
    pending: true,
    image: {
      src: unsplash('photo-1600607687939-ce8a6c25118c'),
      alt: 'A calm, neutral interior with soft natural light',
    },
  },
  {
    slug: 'expedia-cruises-newmarket-aurora',
    name: 'Expedia Cruises Newmarket & Aurora',
    displayName: ['Expedia Cruises', 'Newmarket & Aurora'],
    sector: 'Travel & Cruises',
    location: 'Newmarket & Aurora',
    description:
      'Travel and cruise services. A fuller profile of Expedia Cruises Newmarket & Aurora will be published here.',
    url: '', // TODO: add website
    layout: 'panorama',
    image: {
      src: unsplash('photo-1507525428034-b723cf961d3e'),
      alt: 'A calm turquoise sea meeting a pale sand shore',
      focus: 'center 60%',
    },
    secondaryImage: {
      src: unsplash('photo-1548574505-5e239809ee19'),
      alt: 'A cruise ship on open water',
    },
  },
  {
    slug: 'caddychat',
    name: 'CaddyChat',
    sector: 'Industry to be confirmed',
    description: 'Further details about CaddyChat will be shared here in due course.',
    url: '',
    layout: 'typographic',
    pending: true,
  },
];
