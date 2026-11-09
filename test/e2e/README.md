# Browser suite (Playwright)

Drives a **deployed WordPress site** that has Super Forms installed, through the browser people use: the
front-end form, the form builder and the contact entries. It never renders this repo's code itself, so it
proves what a site carrying the plugin actually does.

Run it from the repo root:

```bash
SF_SITE_URL=https://forms.example.com npm run e2e
```

The first run installs the pinned Playwright and its Chromium; every run after that takes a couple of minutes
against a test site. One spec only: `npm run e2e -- --grep submit`.

## What it needs

| Environment | What for |
|---|---|
| `SF_SITE_URL` | The address of the site under test. Required. |
| `SF_ADMIN_USER`, `SF_ADMIN_PASSWORD` | The site's WordPress admin, for the builder and the entries list. |

None of these belongs in this repo.

## The fixture

The submit spec needs the **Browser test form** on the site (`/browser-test-form/`): three steps, a dropdown
that reveals a company field (conditional logic), a Calculator that adds up quantity x price, and a file
upload. The render spec also expects the seeded test form at `/test-form/` (13 fields). A site without those
pages fails the spec by name instead of skipping silently.

## What each spec proves

| Spec | Channel header | Proves |
|---|---|---|
| `channel.spec.ts` | `lts beta alpha` | The site serves a line this suite knows, read from the plugin version in its own assets, and the page states that same version. |
| `render.spec.ts` | `lts beta alpha` | A form renders its fields, labels and calculator; the seeded test form renders all 13 fields; no console error, and no page falls back to WordPress's critical-error message. |
| `submit.spec.ts` | `lts beta alpha` | A three-step form with conditional logic and a calculator submits **once** (the entries list grows by one), and the entry holds the revealed field, the calculated total and the attachment. |
| `build.spec.ts` | `lts beta` | Text, dropdown, file and calculator elements are dragged into the builder, their settings written, and both the elements and their settings survive closing and reopening the form. The form it builds is deleted again. |

## Channel tags

Every spec starts with a header a suite catalogue is generated from:

```ts
/**
 * @project super-forms
 * @channel lts beta
 * @covers ...
 */
```

`@channel` lists the lines the spec claims to cover (`lts` = 6.3.x, `beta` = 6.4.x, `alpha` = the rewrite).
A spec without a tag is untagged, never assumed to cover everything. The **run** carries the channel it
actually proved, read from the site and attached to every test's report as a `channel` annotation.

## Adding a line

`channelOf()` in `e2e/support/site.ts` maps a plugin version to a channel and throws on a version it does not
know; add the new line there. The builder adapter (`e2e/support/builder.ts`) is per channel: `LegacyBuilder`
covers 6.3.x and 6.4.x, and the alpha React builder has no adapter yet.
