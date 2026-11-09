/**
 * The site's WordPress admin: the login the builder and the entries list need, and the readings the submit
 * spec takes from the entries list and one entry.
 */

import { Page } from '@playwright/test';
import { ADMIN_PASSWORD, ADMIN_USER, ENTRIES_PAGE } from './site';

export function requireAdmin(): void {
  if (!ADMIN_USER || !ADMIN_PASSWORD) {
    throw new Error(
      'SF_ADMIN_USER and SF_ADMIN_PASSWORD must be set: they are the WordPress admin of the site under test.',
    );
  }
}

/** Logs in as the site admin (a fresh browser context, so the session cookie has to be made every run). */
export async function login(page: Page): Promise<void> {
  requireAdmin();
  await page.goto('/wp-login.php');
  await page.locator('#user_login').fill(ADMIN_USER);
  await page.locator('#user_pass').fill(ADMIN_PASSWORD);
  await Promise.all([page.waitForURL(/\/wp-admin\//), page.locator('#wp-submit').click()]);
}

/** How many contact entries the site holds, as the admin's entries list reports it ("12 items"). */
export async function entriesCount(page: Page): Promise<number> {
  await page.goto(ENTRIES_PAGE);
  const text = (await page.locator('.displaying-num').first().innerText()).trim();
  const digits = /^([\d.,\s]+)/.exec(text);
  if (!digits) {
    throw new Error(`The entries list did not report a count ("${text}").`);
  }
  return Number(digits[1].replace(/[^\d]/g, ''));
}

/** Opens the newest entry in the list and returns its id. */
export async function openNewestEntry(page: Page): Promise<string> {
  await page.goto(ENTRIES_PAGE);
  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    page.locator('#the-list tr').first().locator('a.row-title, strong a').first().click(),
  ]);
  const id = new URL(page.url()).searchParams.get('id');
  if (!id) {
    throw new Error(`The entries list opened no entry (ended on ${page.url()}).`);
  }
  return id;
}

/**
 * One submitted value of the entry on screen. The entry page lists every field of the form; a field is an
 * input with its stored value, or text for fields without one (a file upload shows its file name).
 */
export async function entryValue(page: Page, field: string): Promise<string> {
  const cell = page.locator(`.super-contact-entry-data-value`).filter({ has: page.locator(`[name="${field}"]`) }).first();
  if ((await cell.count()) === 0) {
    throw new Error(`The entry page has no field "${field}".`);
  }
  const input = cell.locator(`input[name="${field}"], textarea[name="${field}"]`).first();
  return (await input.count()) > 0 ? input.inputValue() : (await cell.innerText()).trim();
}
