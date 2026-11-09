#!/usr/bin/env bash
# One command for the browser suite: installs the pinned Playwright and its Chromium on the first run, then runs
# every spec against the site in SF_SITE_URL.
#
#   SF_SITE_URL=https://forms.example.com npm run e2e               the whole suite
#   SF_SITE_URL=https://forms.example.com npm run e2e -- --grep submit   one spec
#
# Environment (none belongs in this repo):
#   SF_SITE_URL        the address of the site under test   required
#   SF_ADMIN_USER      the site's WordPress admin           needed by every spec that logs in
#   SF_ADMIN_PASSWORD  that admin's password
set -euo pipefail
cd "$(dirname "$0")"

if [ ! -d node_modules/@playwright/test ]; then
  npm ci
  npx playwright install chromium
fi

exec npx playwright test "$@"
