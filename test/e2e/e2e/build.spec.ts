/**
 * @project super-forms
 * @channel lts beta
 * @covers the builder: several element types are dropped, their settings saved, and both the elements and
 *         their settings survive closing and reopening the form
 */

import { expect, test } from '@playwright/test';
import { login } from './support/admin';
import { LegacyBuilder } from './support/builder';
import { siteLine, tagLine } from './support/site';

/** The element types the builder scenario builds: what people actually put together. */
const ELEMENTS = [
  { slug: 'text_predefined', tag: 'text', settings: { name: 'e2e_name', email: 'Name:', label: 'Your name' } },
  { slug: 'dropdown_predefined', tag: 'dropdown', settings: { name: 'e2e_choice', email: 'Choice:' } },
  { slug: 'file_predefined', tag: 'file', settings: { name: 'e2e_file', email: 'Attachment:' } },
  { slug: 'calculator_predefined', tag: 'calculator', settings: { name: 'e2e_total', math: '2*3' } },
];

test('the builder saves several element types and their settings survive a reopen', async ({ page }) => {
  const line = await tagLine();
  test.skip(line.channel === 'alpha', 'The alpha React builder has no adapter yet.');
  await login(page);

  const builder = new LegacyBuilder(page, line.channel);
  await builder.openNew();
  await builder.setTitle(`E2E build ${Date.now()}`);

  const built: { element: (typeof ELEMENTS)[number]; index: number }[] = [];
  for (const element of ELEMENTS) {
    built.push({ element, index: await builder.addElement(element) });
  }
  expect(await builder.tags()).toEqual(['text', 'dropdown', 'file', 'calculator']);

  const id = await builder.save();

  // Close and reopen: everything above came from the saved form, not from the page that built it.
  await page.goto('/wp-admin/admin.php?page=super_create_form');
  await builder.openForm(id);
  expect(await builder.tags()).toEqual(['text', 'dropdown', 'file', 'calculator']);
  // Every element keeps its settings: read each one back from its own panel.
  for (const { element, index } of built) {
    await builder.openSettings(element.tag, index);
    await expect(
      page.locator('.super-element-settings input[name="name"]:visible').first(),
      `the ${element.tag} element kept its field name`,
    ).toHaveValue((element.settings ?? {})['name'] as string);
  }
  await expect(page.locator('.super-preview [data-shortcode-tag="calculator"]')).toHaveCount(1);

  // Leave the site as it was found.
  await builder.deleteOpenForm();
  await expect(page.locator('.super-preview [data-shortcode-tag]')).toHaveCount(0);
});
