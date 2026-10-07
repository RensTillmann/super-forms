# Submission compatibility regression checks

`validation-compatibility-cases.json` is the shared email/currency contract. Both the complete JavaScript client and real PHP submission checks consume it. Each test must execute the actual plugin revision, not a copied implementation.

## Client

```sh
node --test tests/test-validation-client.cjs
```

The test runs the complete client in an isolated VM with DOM/event adapters. It observes the field-validation return/error state and unchanged display bytes. It is not real-browser or installed-package proof.

## Public WordPress checks

```sh
python tests/run-validation-compatibility.py --plugin ABSOLUTE_PLUGIN_RUNTIME_ROOT --bootstrap OWNED_WORDPRESS_BOOTSTRAP --group all --out NEW_RECEIPT.json
```

LTS's runtime root is the checkout itself; beta's is its `src/`. The bootstrap must load real WordPress, an owned synthetic database, this exact plugin root from `SF_PLUGIN_ROOT`, and block all network/mail. The runner sets the child working directory and asserts the loaded Forms/Ajax/Common class paths, to catch PHP relative-include cross-lane contamination. It records interpreter, source hashes, response bytes and stderr. Reusing an output path is refused.

The current native development bootstrap is under the project's `dev/validation-compatibility-20261007/`, with an independent SQLite database and offline filters. It reuses installed WordPress core read-only and loads PDO SQLite only for each test process. Random unrelated session cleanup is pinned away from a MySQL-only GC query. This is not a MySQL security suite or a fork-based full-submit/HTTP test.

Cases cover missing/empty/custom/full date formats, exact server timestamps, invalid dates, partial date anchors, email agreement, currency syntax and precision (without float conversion), and code preview/first claim/duplicate rejection. Native negative controls require the specific `Invalid form data.` response, rather than treating any crash as expected rejection. Beta's inherited `triggerEvent(sf.before.submission)` informational log is retained; all other stderr fails the check.

The added PHPUnit hotfix test follows the existing public `submit_form_checks` seam, but its native PHPUnit suite has not been executed in this session. Run the existing release suite with its pcntl/MySQL requirements before channel release.

## Release boundary

Focused green results do not replace the established full suites, independent review, exact ZIP build/install, browser/upgrade matrix or operator acceptance. Neither test runner publishes, deploys or sends customer messages.
