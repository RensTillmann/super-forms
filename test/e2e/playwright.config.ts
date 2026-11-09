import { defineConfig } from '@playwright/test';

/**
 * The suite drives a deployed WordPress site, never a copy of this repo's code: `SF_SITE_URL` says which site.
 * The site's own plugin version decides the channel, so the same specs run on whichever line the site carries;
 * see `e2e/support/site.ts`.
 *
 * Everything site-specific comes from the environment, never from a file in this repo:
 *   SF_SITE_URL        the address of the site under test                          required
 *   SF_ADMIN_USER      the site's WordPress admin                                  for the builder and entries
 *   SF_ADMIN_PASSWORD  that admin's password
 */
export default defineConfig({
  testDir: './e2e',
  // Long enough for the build spec: four drags, four elements edited, saved and reopened.
  timeout: 150_000,
  expect: { timeout: 15_000 },
  // One site, one submission, one form being built: the specs are sequential on purpose.
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['list'], ['json', { outputFile: 'test-results/run.json' }]],
  outputDir: 'test-results/artifacts',
  use: {
    baseURL: process.env.SF_SITE_URL?.replace(/\/+$/, ''),
    viewport: { width: 1400, height: 1000 },
    trace: 'retain-on-failure',
    video: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
});
