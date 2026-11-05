// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';
import { site } from './src/data/site.ts';

// https://astro.build/config
export default defineConfig({
  site: site.url,
  integrations: [sitemap()],
  build: { inlineStylesheets: 'auto' },
});
