/**
 * The site under test: its address, and the line it serves.
 *
 * The channel is not configured, it is read from the site itself: the plugin enqueues its own assets with
 * `?ver=<plugin version>`, so any page carrying a form states which line is running. A version on no line
 * this suite knows fails loudly instead of being guessed at, so a new line is added here on purpose.
 */

import { test } from '@playwright/test';

export const SITE_URL = (process.env.SF_SITE_URL ?? '').replace(/\/+$/, '');
export const ADMIN_USER = process.env.SF_ADMIN_USER ?? '';
export const ADMIN_PASSWORD = process.env.SF_ADMIN_PASSWORD ?? '';

/** The fixture page the submit spec drives: a form with three steps, conditional logic and a calculator. */
export const FIXTURE_PAGE = '/browser-test-form/';
export const FIXTURE_FORM = 'Browser test form';
export const ENTRIES_PAGE = '/wp-admin/edit.php?post_type=super_contact_entry';

export type Channel = 'lts' | 'beta' | 'alpha';

export interface SiteLine {
  version: string;
  channel: Channel;
  /** Where the version was read (the page whose assets carried it). */
  readFrom: string;
}

/**
 * Which supported line a plugin version belongs to. 6.3.x is the LTS channel, 6.4.0xx the beta channel, and
 * the rewrite (7.0.0-alpha, and whatever number it takes) is alpha. Anything else throws: an unknown version
 * must not be silently treated as one of these.
 */
export function channelOf(version: string): Channel {
  const clean = version.trim();
  if (/alpha/i.test(clean)) {
    return 'alpha';
  }
  const match = /^(\d+)\.(\d+)\./.exec(clean);
  if (!match) {
    throw new Error(`Cannot read a channel from the version "${version}".`);
  }
  const line = `${match[1]}.${match[2]}`;
  if (line === '6.3') {
    return 'lts';
  }
  if (line === '6.4') {
    return 'beta';
  }
  throw new Error(
    `Version ${version} is on no line this suite knows: lts is 6.3.x, beta is 6.4.x, alpha speaks for itself. ` +
      'Add its channel to channelOf() in test/e2e/e2e/support/site.ts.',
  );
}

let linePromise: Promise<SiteLine> | undefined;

/** Reads the plugin version the site serves. Pages are tried in order; the first that carries the assets wins. */
export function siteLine(): Promise<SiteLine> {
  linePromise ??= (async () => {
    const pages = [FIXTURE_PAGE, '/test-form/', '/'];
    const problems: string[] = [];
    for (const path of pages) {
      const response = await fetch(`${SITE_URL}${path}`, { redirect: 'follow' });
      if (!response.ok) {
        problems.push(`${path}: HTTP ${response.status}`);
        continue;
      }
      const html = await response.text();
      const version = /\/plugins\/super-forms\/[^"']*?[?&]ver=([0-9A-Za-z][0-9A-Za-z.\-]*)/.exec(html)?.[1];
      if (version) {
        return { version, channel: channelOf(version), readFrom: path };
      }
      problems.push(`${path}: no super-forms asset with a version`);
    }
    throw new Error(`Could not read the plugin version from ${SITE_URL} (${problems.join('; ')}).`);
  })();
  return linePromise;
}

/** Attaches what the run proved to the test's report: the channel, the version and (when known) the commit. */
export async function tagLine(): Promise<SiteLine> {
  const line = await siteLine();
  test.info().annotations.push({ type: 'channel', description: line.channel }, { type: 'version', description: line.version });
  return line;
}
