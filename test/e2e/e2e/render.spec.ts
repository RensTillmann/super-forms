/**
 * @project super-forms
 * @channel lts beta alpha
 * @covers a form renders with its fields, labels and calculator, without console errors
 */

import { expect, test } from '@playwright/test';
import { FIXTURE_PAGE, tagLine } from './support/site';

test.describe('a form on the front end', () => {
  test.beforeEach(async () => {
    await tagLine();
  });

  test('renders the fixture form with its fields, labels and calculator', async ({ page }) => {
    const response = await page.goto(FIXTURE_PAGE, { waitUntil: 'networkidle' });
    expect(response?.status()).toBe(200);

    const form = page.locator('.super-form').first();
    await expect(form).toBeVisible();

    // The shortcode renders its fields, each with the label the builder stored.
    await expect(form.locator('input[name="bt_name"]')).toBeVisible();
    await expect(form.locator('input[name="bt_email"]')).toBeVisible();
    await expect(form.locator('.super-label', { hasText: 'Name' }).first()).toBeVisible();
    await expect(form.locator('.super-label', { hasText: 'Email address' }).first()).toBeVisible();

    // The Calculator element produced a total from the stored defaults (2 x 10).
    await expect(form.locator('.super-calculator-amount').first()).toHaveText(/^\d/);

    // A field with conditional logic is rendered but hidden until its condition matches.
    const company = form.locator('input[name="bt_company"]');
    await expect(company).toBeHidden();
    await expect(form.locator('.super-conditional-hidden')).toHaveCount(1);
  });

  test('renders the seeded test form with every field and no console error', async ({ page }) => {
    const consoleErrors: string[] = [];
    page.on('console', (message) => {
      if (message.type() === 'error') {
        consoleErrors.push(message.text());
      }
    });

    await page.goto('/test-form/', { waitUntil: 'networkidle' });
    const root = page.locator('.super-form').first();
    await expect(root).toBeVisible();
    const names = await root
      .locator('.super-shortcode-field[name]')
      .evaluateAll((nodes) => nodes.map((node) => node.getAttribute('name') ?? ''));
    // The 13 fields the test site seeds on that page; hidden_form_id and the honeypot are the plugin's own.
    for (const field of ['first_name', 'last_name', 'email', 'phone', 'company', 'street', 'city', 'postcode', 'country', 'topic', 'message', 'interests', 'terms']) {
      expect(names, `/test-form/ renders ${field}`).toContain(field);
    }
    await expect(root.locator('.super-label', { hasText: 'First name' }).first()).toBeVisible();
    await expect(root.locator('.super-label', { hasText: 'Country' }).first()).toBeVisible();

    expect(consoleErrors, `console errors on /test-form/: ${consoleErrors.join(' | ')}`).toEqual([]);
  });

  test('no page shows a critical-error message while the forms render', async ({ page }) => {
    for (const path of [FIXTURE_PAGE, '/test-form/', '/test-multipart-form/']) {
      const response = await page.goto(path, { waitUntil: 'networkidle' });
      expect(response?.status(), `${path} answers`).toBe(200);
      // WordPress replaces a page with this text when PHP fails: a form that renders anyway still fails here.
      await expect(page.locator('body')).not.toContainText('There has been a critical error');
    }
  });
});
