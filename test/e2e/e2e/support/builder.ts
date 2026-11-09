/**
 * The form builder, as the site's channel presents it.
 *
 * 6.3.x (lts) and 6.4.x (beta) share the classic builder (`admin.php?page=super_create_form`): elements are
 * dragged from the palette into the canvas, edited through the element settings panel, and saved with the
 * header button. The rewrite (alpha) has its own React builder (Form Builder V2) and no adapter yet: its front
 * end does not render form fields today, so no browser spec can prove anything about it.
 */

import { Page, expect } from '@playwright/test';
import type { Channel } from './site';

export interface ElementSettings {
  /** Settings by their builder field name: name, label, email, math, ... */
  [setting: string]: string;
}

/** A builder element the specs drag and expect back: the palette slug and the tag it becomes in the canvas. */
export interface BuilderElement {
  slug: string;
  tag: string;
  settings?: ElementSettings;
}

/**
 * The overlays a fresh admin session meets before the builder is usable: the guided tour (enjoyhint) sits on
 * top of everything and swallows pointer events, and the first-time-setup panel covers the canvas. A person
 * closes the tour and skips the wizard; a test removes the tour (nothing under test depends on it) and clicks
 * "Skip wizard" the way a person would.
 */
async function dismissOnboarding(page: Page): Promise<void> {
  await page
    .evaluate(() => {
      document.querySelectorAll('.enjoyhint, .enjoyhint_disable_events').forEach((node) => node.remove());
    })
    .catch(() => undefined);
  const skip = page.locator('.super-skip-wizard');
  if (await skip.count()) {
    await skip.first().click({ timeout: 5_000, force: true }).catch(() => undefined);
  }
  await page
    .evaluate(() => {
      document.querySelectorAll('.super-first-time-setup, .super-first-time-setup-bg').forEach((node) => node.remove());
    })
    .catch(() => undefined);
}

export class LegacyBuilder {
  constructor(
    private readonly page: Page,
    readonly channel: Channel,
  ) {
    if (channel === 'alpha') {
      throw new Error('The alpha React builder has no adapter yet (see the note at the top of builder.ts).');
    }
  }

  /** The elements the canvas holds, by the tag each dropped element became. */
  async tags(): Promise<string[]> {
    return this.page
      .locator('.super-preview [data-shortcode-tag]')
      .evaluateAll((nodes) => nodes.map((node) => node.getAttribute('data-shortcode-tag') ?? ''));
  }

  /** Opens the "create a form" page with an empty canvas and a usable palette. */
  async openNew(): Promise<void> {
    await this.openBuilder('/wp-admin/admin.php?page=super_create_form');
  }

  /** Opens an existing form in the builder. */
  async openForm(id: number): Promise<void> {
    await this.openBuilder(`/wp-admin/admin.php?page=super_create_form&id=${id}`);
  }

  /**
   * Opens a builder page and gets past the onboarding. A fresh admin session meets the first-time-setup panel
   * with a guided tour over it, which hides the builder until it is closed; the panel can arrive a moment after
   * the document, so it is dismissed again when the builder is still hidden.
   */
  private async openBuilder(url: string): Promise<void> {
    await this.page.goto(url, { waitUntil: 'domcontentloaded' });
    await this.page.locator('.form-name').waitFor({ state: 'attached' });
    for (let attempt = 1; attempt <= 3; attempt += 1) {
      await dismissOnboarding(this.page);
      if (await this.page.locator('.form-name').isVisible()) {
        break;
      }
      await this.page.waitForTimeout(700);
    }
    await this.page.locator('.form-name').waitFor({ state: 'visible', timeout: 30_000 });
    await this.expandPalette();
  }

  async setTitle(title: string): Promise<void> {
    await this.page.locator('.form-name').fill(title);
  }

  /**
   * Expands the "Form Elements" group of the palette. The left panel is one accordion: opening an element's
   * settings collapses the palette again, so every drop starts by putting the palette back on show.
   */
  async expandPalette(): Promise<void> {
    const container = this.page.locator('.super-form-elements .super-elements-container');
    if ((await container.count()) === 0) {
      return;
    }
    for (let attempt = 1; attempt <= 3; attempt += 1) {
      if (await container.isVisible()) {
        return;
      }
      await this.page.locator('.super-form-elements h3').click();
      await this.page.waitForTimeout(300);
    }
    throw new Error('The builder palette (Form Elements) could not be expanded.');
  }

  /**
   * Drags one palette element into the canvas. The builder reacts to pointer events (jquery-pep), so this is a
   * real press, a slow move and a release; the drop is retried because a drop can land before the canvas has
   * registered the press.
   */
  async dropElement(element: BuilderElement): Promise<void> {
    const palette = this.page.locator(`.draggable-element.super-shortcode-${element.slug}`).first();
    if ((await palette.count()) === 0) {
      throw new Error(
        `The builder has no "${element.slug}" in its palette. On this site that usually means an add-on is not ` +
          'active (the Calculator element comes from the Super Forms - Calculator add-on).',
      );
    }
    await this.expandPalette();
    const before = (await this.tags()).length;
    for (let attempt = 1; attempt <= 3; attempt += 1) {
      await palette.scrollIntoViewIfNeeded();
      const from = await palette.boundingBox();
      const drops = this.page.locator('.super-dropable');
      let to: { x: number; y: number; width: number; height: number } | null = null;
      for (let index = (await drops.count()) - 1; index >= 0; index -= 1) {
        const candidate = drops.nth(index);
        if (await candidate.isVisible()) {
          to = await candidate.boundingBox();
          break;
        }
      }
      if (!from || !to) {
        throw new Error(`Cannot drag "${element.slug}": the palette item or the canvas has no box.`);
      }
      const startX = from.x + from.width / 2;
      const startY = from.y + from.height / 2;
      const endX = to.x + to.width / 2;
      const endY = to.y + to.height / 2;
      await this.page.mouse.move(startX, startY);
      await this.page.waitForTimeout(120);
      await this.page.mouse.down();
      await this.page.waitForTimeout(120);
      for (let step = 1; step <= 30; step += 1) {
        await this.page.mouse.move(startX + ((endX - startX) * step) / 30, startY + ((endY - startY) * step) / 30);
        await this.page.waitForTimeout(20);
      }
      await this.page.mouse.up();
      await this.page.waitForTimeout(1500);
      if ((await this.tags()).length > before) {
        return;
      }
    }
    throw new Error(`Dragging "${element.slug}" into the canvas did not add an element after 3 attempts.`);
  }

  /** Adds one element and fills its settings; returns its index among the elements of its own tag. */
  async addElement(element: BuilderElement): Promise<number> {
    await this.dropElement(element);
    // The element just dropped is the last one of its tag: that is the one to configure.
    const index = (await this.page.locator(`.super-preview [data-shortcode-tag="${element.tag}"]`).count()) - 1;
    if (element.settings) {
      await this.editElement(element.tag, index, element.settings);
    }
    return index;
  }

  /** Opens the settings panel of element `index` (in canvas order) of `tag`. */
  async openSettings(tag: string, index: number): Promise<void> {
    const element = this.page.locator(`.super-preview [data-shortcode-tag="${tag}"]`).nth(index);
    const nameField = this.page.locator('.super-element-settings input[name="name"]:visible').first();
    for (let attempt = 1; attempt <= 3; attempt += 1) {
      await element.hover();
      const edit = element.locator('.super-edit');
      if ((await edit.count()) > 0) {
        await edit.first().click();
      }
      // The panel renders a moment after the click; clicking again would close it, so wait rather than poke.
      if (await nameField.waitFor({ state: 'visible', timeout: 4_000 }).then(() => true, () => false)) {
        return;
      }
    }
    const state = {
      tags: await this.tags(),
      editIcons: await element.locator('.super-edit').count(),
      panels: await this.page.locator('.super-element-settings').count(),
      nameFields: await this.page.locator('.super-element-settings input[name="name"]').count(),
    };
    throw new Error(`The settings panel of the ${tag} element did not open (${JSON.stringify(state)}).`);
  }

  /**
   * Writes each setting of element `index` of `tag` through its settings panel. The name is written last: the
   * panel re-renders some fields when the name changes.
   */
  async editElement(tag: string, index: number, settings: ElementSettings): Promise<void> {
    await this.openSettings(tag, index);
    const panel = this.page.locator('.super-element-settings');
    const entries = Object.entries(settings).sort(([a], [b]) => (a === 'name' ? 1 : b === 'name' ? -1 : 0));
    for (const [setting, value] of entries) {
      const field = panel.locator(`input[name="${setting}"]:visible, textarea[name="${setting}"]:visible`).first();
      if ((await field.count()) === 0) {
        throw new Error(`The element settings have no field "${setting}" for a ${tag} element.`);
      }
      await field.fill(value);
      await field.press('Tab');
    }
  }

  /** Saves the form and returns its id (the page URL carries it once the save landed). */
  async save(): Promise<number> {
    await this.page.locator('.super-header .super-actions .super-save').click();
    await this.page.waitForURL(/[?&]id=\d+/, { timeout: 20_000 });
    const id = new URL(this.page.url()).searchParams.get('id');
    if (!id) {
      throw new Error(`Saving the form did not put a form id in the URL (${this.page.url()}).`);
    }
    return Number(id);
  }

  /** Deletes the form on screen (the builder asks for confirmation in a native dialog). */
  async deleteOpenForm(): Promise<void> {
    this.page.once('dialog', (dialog) => void dialog.accept());
    await this.page.locator('.super-header .super-actions .super-delete').click();
    await this.page.waitForLoadState('domcontentloaded');
  }
}
