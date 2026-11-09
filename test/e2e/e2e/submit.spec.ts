/**
 * @project super-forms
 * @channel lts beta alpha
 * @covers a multi-step form with conditional logic and a calculator submits once, and its entry holds what was
 *         filled in - the number of entries, the revealed field, the calculated total and the attachment
 */

import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { entriesCount, entryValue, login, openNewestEntry } from './support/admin';
import { FIXTURE_FORM, FIXTURE_PAGE, tagLine } from './support/site';
import path from 'node:path';

const UPLOAD = path.join(__dirname, '..', 'fixtures', 'upload.pdf');

/** The visible "Next" / "Prev" / "Submit" button of the step on screen (they are divs, and every step has one). */
function stepButton(page: Page, label: string) {
  return page.locator('.super-form .super-field.super-button:visible', { hasText: label }).first();
}

/** Picks an item from a Super Forms dropdown: the collapsed list opens the widget, the item carries the value. */
async function choose(page: Page, field: string, value: string): Promise<void> {
  const dropdown = page.locator('.super-form .super-field.super-dropdown', {
    has: page.locator(`input[name="${field}"]`),
  });
  await dropdown.locator('ul.super-dropdown-list').click();
  await dropdown.locator(`li.super-item[data-value="${value}"]`).click();
  await expect(page.locator(`input[name="${field}"]`)).toHaveValue(value);
}

test('a multi-step form with conditional logic submits once and its entry holds the answers', async ({ page }) => {
  await tagLine();
  await login(page);
  const before = await entriesCount(page);

  const name = `E2E run ${Date.now()}`;
  await page.goto(FIXTURE_PAGE, { waitUntil: 'networkidle' });
  const form = page.locator('.super-form').first();

  // Step 1: the two fields that are always there.
  await form.locator('input[name="bt_name"]').fill(name);
  await form.locator('input[name="bt_email"]').fill('e2e@example.com');
  await stepButton(page, 'Next').click();

  // Step 2: the dropdown reveals the conditional field, the calculator adds up quantity x price.
  await expect(page.locator('input[name="bt_company"]')).toBeHidden();
  await choose(page, 'bt_type', 'business');
  await expect(page.locator('input[name="bt_company"]')).toBeVisible();
  await page.locator('input[name="bt_company"]').fill('E2E Company');

  const quantity = page.locator('input[name="bt_qty"]');
  await quantity.click();
  await quantity.press('ControlOrMeta+a');
  await quantity.type('3', { delay: 60 });
  await expect(page.locator('input[name="bt_total"]')).toHaveValue('30.00');

  // The field takes the file the moment it is chosen (the plugin keeps it, the input's FileList is emptied),
  // and lists it by name. 6.3 does not show uploaded files among an entry's fields, so this is where it shows.
  await page.locator('input[type="file"]').setInputFiles(UPLOAD);
  await expect(page.locator('.super-field.super-file')).toContainText('upload.pdf');
  await stepButton(page, 'Next').click();

  // Step 3: accept the terms and submit.
  await page.locator('.super-form .super-field.super-checkbox label.super-item').first().click();
  await expect(page.locator('input[name="bt_terms"]')).toHaveValue('yes');
  await stepButton(page, 'Submit').click();
  await expect(page.locator('.super-success')).toBeVisible({ timeout: 30_000 });
  await expect(page.locator('.super-success')).toContainText('Thank you');

  // One submission, one entry: the list grew by exactly one.
  const after = await entriesCount(page);
  expect(after, `the entries list went from ${before} to ${after} entries for one submission`).toBe(before + 1);

  // The entry holds what was filled in, including the revealed field and the calculated total.
  await openNewestEntry(page);
  expect(await entryValue(page, 'bt_name')).toBe(name);
  expect(await entryValue(page, 'bt_email')).toBe('e2e@example.com');
  expect(await entryValue(page, 'bt_type')).toBe('business');
  expect(await entryValue(page, 'bt_company')).toBe('E2E Company');
  expect(await entryValue(page, 'bt_qty')).toBe('3');
  // The entry stores the total as the form shows it, currency sign included.
  expect(await entryValue(page, 'bt_total')).toContain('30.00');
  await expect(page.locator('.postbox, #poststuff').first()).toContainText(FIXTURE_FORM);
});
