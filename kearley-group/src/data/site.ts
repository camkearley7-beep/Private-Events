/**
 * Site-wide settings for Kearley Group.
 *
 * Everything marked TODO is a placeholder and must be confirmed before launch.
 */
export const site = {
  name: 'Kearley Group',

  // TODO: replace with the live domain connected through GoDaddy (no trailing slash).
  url: 'https://www.kearleygroup.com',

  title: 'Kearley Group — A Private Holding Group',
  description:
    'Kearley Group is a privately owned holding group bringing together a collection of independently operated businesses.',

  hero: {
    // The two lines of the opening headline. Italics are applied to the second line.
    headline: ['Independent by design.', 'United by name.'],
    standfirst:
      'A privately held group of independently operated businesses — each with its own name, its own character and its own course.',
  },

  contact: {
    // TODO: confirm the public enquiries address.
    email: 'inquiries@kearleygroup.com',
    // TODO: confirm the public location line (or set to '' to hide it).
    location: 'Ontario, Canada',
  },

  nav: [
    { label: 'The Group', href: '#the-group' },
    { label: 'Our Companies', href: '#our-companies' },
    { label: 'Contact', href: '#contact' },
  ],
} as const;
