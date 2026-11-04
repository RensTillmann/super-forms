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

Use `--group render` to capture actual currency attributes through `SUPER_Shortcodes::output_element_html`, then set `SF_RENDER_RECEIPT` to that immutable receipt when running the Node tests. The client verifies its source hash against the receipt and consumes the actual rendered datasets, including omitted defaults and saved translations. Rendering metadata is not an acceptance verdict on the displayed input.

The currency parser gives valid saved grouping syntax precedence over a canonical raw-number fallback. Server defaults come from the same generated shortcode defaults as rendering, after the existing saved-language resolution. Literal-only/weekday-only custom dates are rejected before calendar anchors; Unix/tick formats retain their separate paths.

`python tests/test-validation-runner-guards.py` runs unit-only subprocess mocks: output reuse must invoke zero PHP children, and simulated timeouts must preserve failure diagnostics. It does not test WordPress. Real claim tests delete only a successfully owned `_sf_unique_code-` reservation and read back its absence. Outputs are reserved exclusively before children; partial runs retain source/harness hashes and diagnostics.

The current native development bootstrap is under the project's `dev/validation-compatibility-20261007/`, with an independent SQLite database and offline filters. It reuses installed WordPress core read-only and loads PDO SQLite only for each test process. Random unrelated session cleanup is pinned away from a MySQL-only GC query. This is not a MySQL security suite or a fork-based full-submit/HTTP test.

Cases cover missing/empty/custom/full date formats, exact server timestamps, invalid dates, partial date anchors, email agreement, currency syntax and precision (without float conversion), and code preview/first claim/duplicate rejection. Native negative controls require the specific `Invalid form data.` response, rather than treating any crash as expected rejection. Beta's inherited `triggerEvent(sf.before.submission)` informational log is retained; all other stderr fails the check.

The added PHPUnit hotfix test follows the existing public `submit_form_checks` seam, but its native PHPUnit suite has not been executed in this session. Run the existing release suite with its pcntl/MySQL requirements before channel release.

## Generated PDF with Signature elements

A form with Signature elements posts its own generated PDF back as a data URI. Two checks cover it:

```sh
python tests/run-validation-compatibility.py --plugin ABSOLUTE_PLUGIN_RUNTIME_ROOT --bootstrap OWNED_WORDPRESS_BOOTSTRAP --group signatures --out NEW_RECEIPT.json
```

`--group signatures` submits a form with three Signature elements through `SUPER_Ajax::submit_form_checks` and expects `CHECKS_ACCEPTED` with an 8,215,751 byte `application/pdf` file record, no surviving data URI, and no stderr. `tests/generated-pdf-signature-fixture.php` builds that exact-size PDF with seven embedded images; the PHPUnit test uses the same fixture, so a signed membership form is represented by size, not by a placeholder.

The PHPUnit side is `tests/test-generated-pdf-size-limit.php` (same WordPress test suite as the other `tests/*.php` files). It pins three things: the default PDF size limit accepts the plugin's own PDF above the 5 MB upload-element default on a host whose PHP allows 20 MB, a payload above the site's own upload cap is still refused, and an explicitly configured `_pdf.filesize` is still enforced. The upload cap is set through the `upload_size_limit` filter, so the test does not depend on the runner's php.ini.

Date, currency, code and layout cases live in the client contract above; the generated-PDF cases are the ones that cover the submission path after upload validation.

## Release boundary

Focused green results do not replace the established full suites, independent review, exact ZIP build/install, browser/upgrade matrix or operator acceptance. Neither test runner publishes, deploys or sends customer messages.
