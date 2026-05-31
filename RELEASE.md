# Release runbook (prescriptive)

## 0. Reading order

This file is **prescriptive**. It documents how a Super Forms v6 release
is cut, what gets verified, and where the customer-facing artifacts
land. The companion file `store/repos/super-forms-v7/RELEASE.md` in
the cc-inbox checkout is the **descriptive** counterpart for v7 alpha.

The single source-of-truth for routing vocabulary (5 reference points +
LLM-internal prefix tags + 4 customer-facing buckets) lives in the
cc-inbox `AGENTS.md::Source repositories::Release lifecycle and
promotion`. This file mirrors that vocabulary on the product-repo side
and adds the cut mechanic the operator runs.

**Promotion gate: Rens decides.** No calendar. No promotion cadence.
Cuts happen when Rens decides a fix is mature for the next public
ship.

## 1. Channel reference points

Two customer-facing channels and three internal dev refs (the same
five referenced in cc-inbox AGENTS.md):

| Ref-point | Surface | Tag convention | Customer-visible? |
|---|---|---|---|
| `public-stable-zip` | `super-forms.com/download-super-forms-stable.php` → `super-forms.zip` (served by `f4d.nl:@super-forms-updates/packages/`) | `vX.Y.Z` (bare semver, no suffix) | YES |
| `public-beta-zip` | `super-forms.com/download-super-forms-beta.php` → `super-forms-beta.zip` (same f4d.nl host) | `vX.Y.Z-beta.N` | YES (opt-in download) |
| `dev-tag` | `git tag -l 'v6.4.*'` in `super-forms.git` | `vX.Y.Z` rolling along master HEAD | NO — cc-inbox testing only |
| `master-HEAD` | `super-forms.git@master` | (untagged tip) | NO |
| `v7-alpha` | `super-forms-v7.git@next/v7` | `v7.0.0-alphaN` | NO — internal roadmap only |

The `release/6.3.x` long-lived branch is the source-of-truth for the
stable channel from Track 1 (v6.3.313) forward. The
`release/6.4.x-beta` long-lived branch carries the beta channel.

## 2. Tag-naming convention

| Channel | Pattern | Example |
|---|---|---|
| Stable | `vX.Y.Z` (bare semver) | `v6.3.313` |
| Beta | `vX.Y.Z-beta.N` (suffix counter) | `v6.4.201-beta.1` |
| Alpha (internal) | `vX.0.0-alphaN` | `v7.0.0-alpha1` |

All tags are annotated, signed when the operator's GPG is available,
and pushed to `origin`. Tags MUST exist before a GitHub Release is
created so the release attaches to a real ref.

## 3. Cut a stable release — happy path

For backports to the stable channel (typical case: new WordPress
version compat advisory + minimal patches). Source-of-truth is the
`release/6.3.x` branch; previous cuts that pre-date the branch's
existence are recorded by the bundled ZIP, not by git.

1. **Work in a fresh clone or worktree** of `release/6.3.x`. Apply
   the backport on top of the branch HEAD. Bump the `Version:` header
   and the `$version` property in `super-forms.php` from the prior cut
   (e.g. `6.3.313 → 6.3.314`). Update `Tested up to:` if the WP-compat
   bump is the driver.
2. **Update the bundled changelog.** Append the entry to
   `super-forms/docs/changelog.md` in docsify format (`## <Date> -
   Version X.Y.Z` H2 followed by `- **Improved:**` / `**Fix:**` /
   `**Added:**` bullets) on the working tree. This file MUST land in
   the customer ZIP — see §5 below.
3. **Update the GitBook-rendered changelog** on master via PR (branch
   protection forbids direct push to master): append to
   `gitbook-docs/changelog-stable.md` and bump the "Current release"
   pointer on `gitbook-docs/changelog.md`. Do NOT touch
   `gitbook-docs/changelog-beta.md` or master's `docs/changelog.md`.
4. **Build the ZIP.** Re-zip the modified `super-forms/` tree
   directly (the master `build.sh` tooling has drifted from v6.3.x
   layout):
   ```
   python3 -c "import zipfile,os; \
     z=zipfile.ZipFile('super-forms.zip','w',zipfile.ZIP_DEFLATED,6); \
     [z.write(os.path.join(r,f),os.path.join(r,f)) \
      for r,_,fs in os.walk('super-forms') for f in fs]; z.close()"
   ```
5. **Runtime smoke-test** the ZIP against the matching test stack
   (`wp.stable.super-forms.com`). Static grep of removed APIs is
   insufficient — the v6.3.313 backport caught a PHP 8.2 `${var}`
   interpolation deprecation in `super-forms-csv-attachment` that
   grep had missed.
6. **Backup the current customer ZIP** before overwriting on f4d.nl:
   `sftp <auth>@<host>; get @super-forms-updates/packages/super-forms.zip`.
7. **SFTP-upload** the new ZIP to
   `f4d.nl:@super-forms-updates/packages/super-forms.zip`.
8. **Bump the updates-server `tested` value** when WP-compat changed:
   edit `@super-forms-updates/MyCustomServer.php`, change
   `$meta['tested']`, `php -l` to syntax-check.
9. **Verify customer metadata** before considering the release done:
   `curl 'https://f4d.nl/@super-forms-updates/?action=get_metadata&slug=super-forms' | jq '.version, .tested'`.
10. **Tag and push.** Commit the backport on `release/6.3.x`, tag the
    commit with the bare-semver tag, push branch + tag to `origin`.
11. **Create the GitHub Release.** Single coherent surface for tag + ZIP + body:
    ```
    gh release create v$VERSION super-forms.zip \
        --repo RensTillmann/super-forms \
        --title "v$VERSION" \
        --notes-file <(awk '/^## .* - Version '$VERSION'/{f=1} f' \
                       super-forms/docs/changelog.md | head -50)
    gh release edit v$VERSION --repo RensTillmann/super-forms --latest
    ```
    The asset filename MUST match the f4d.nl customer-facing name
    (`super-forms.zip`) so a customer who downloads from GH gets a
    byte-identical artifact to the auto-update payload.

## 4. Cut a beta release — happy path

Same shape as §3 with three differences:

- Source branch: `release/6.4.x-beta` (seeded from `v6.4.003-beta`).
- Tag pattern: `vX.Y.Z-beta.N` (e.g. `v6.4.004-beta.1`).
- Customer-facing changelog edits target `gitbook-docs/changelog-beta.md`
  on master via PR, NOT `changelog-stable.md`. Also bump the
  version+date stamp in the success-hint download line on
  `gitbook-docs/developers/beta-version.md` (the page hosts the actual
  `super-forms-beta.zip` download link customers use to switch
  channels). Do NOT touch `changelog-stable.md` or master's
  `docs/changelog.md`.
- GitHub Release uses `--prerelease` (no `--latest`):
  ```
  gh release create v$VERSION-beta.$N super-forms-beta.zip \
      --repo RensTillmann/super-forms --prerelease \
      --title "v$VERSION-beta.$N" --notes-file …
  ```
- The f4d.nl SFTP target is `@super-forms-updates/packages/super-forms-beta.zip`.

## 5. Customer ZIP bundled-changelog requirement

Every customer-shipping ZIP (`super-forms.zip` and `super-forms-beta.zip`)
MUST contain `super-forms/docs/changelog.md` carrying the per-channel
history through and including the new version. Customers read this
file in `wp-admin → Plugins → View details`; omitting it leaves them
with only the 105-byte `super-forms/changelog.txt` stub pointing at the
external docsify URL.

Verification step before §3 step 7 (or §4 SFTP-upload):

```
python3 -c "import zipfile; z=zipfile.ZipFile('super-forms.zip'); \
  assert 'super-forms/docs/changelog.md' in z.namelist(); \
  print('OK', sum(1 for n in z.namelist() if n.endswith('/changelog.md')))"
```

When the full CI pipeline lands (separate plan), `release.yml`'s ZIP
integrity check will enforce this gate; until then it is a manual
checklist item.

## 6. Manual SFTP-upload fallback

If the eventual CI pipeline is unavailable (credential rotation,
GitHub Actions outage, runner-pool exhaustion), the §3 / §4 steps
above are the manual fallback verbatim. The SFTP credentials live in
1Password under "f4d.nl @super-forms-updates SFTP"; the operator who
holds them (Rens) executes the upload.

## 7. Rollback

A bad ZIP is rolled back by SFTP-uploading the prior version's ZIP
over the customer-facing name. The prior ZIP is recoverable from:

1. The pre-overwrite backup taken in §3 step 6 (preferred — known to
   match what customers were running).
2. The previous version's GitHub Release asset (verified-identical to
   the f4d.nl ZIP per §3 step 11). Example:
   ```
   gh release download v$PRIOR --repo RensTillmann/super-forms \
       --pattern 'super-forms.zip'
   ```

Re-upload the prior ZIP to f4d.nl per §3 step 7. Mark the bad GitHub
Release as `--draft` (so it disappears from the public listing) and
add a `## Rolled back` note to its release body explaining the
trigger. Do NOT delete the tag — the tag is the historical record.

Restore `MyCustomServer.php::$meta['tested']` to the prior value if
§3 step 8 was already executed.

## 8. Cross-references

- cc-inbox doctrine (single source-of-truth for vocabulary):
  `/termux-home/cc-inbox/AGENTS.md::Source repositories::Release
  lifecycle and promotion` — 5-ref-point table + LLM prefix tags +
  customer-facing buckets.
- v7 descriptive companion:
  `store/repos/super-forms-v7/RELEASE.md` in the cc-inbox checkout.
- Maintenance backport procedure (the source for §3-§5 above):
  cc-inbox `AGENTS.md::Maintenance backport procedure`.
