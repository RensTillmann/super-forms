/**
 * @project super-forms
 * @channel lts beta alpha
 * @covers which line the site under test serves, read from the site itself
 */

import { expect, test } from '@playwright/test';
import { FIXTURE_PAGE, tagLine } from './support/site';

test('the site serves a line this suite knows', async ({ page }) => {
  const line = await tagLine();
  expect(['lts', 'beta', 'alpha']).toContain(line.channel);
  expect(line.version).not.toBe('');

  const response = await page.goto(FIXTURE_PAGE);
  expect(response?.status()).toBe(200);

  // The page states the version the channel was read from, so a run cannot claim another site's line.
  expect(await page.content()).toContain(`ver=${line.version}`);
});
