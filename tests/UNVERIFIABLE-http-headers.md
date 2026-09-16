# HTTP-SAPI-only observation boundary

This CLI harness cannot directly observe raw response headers, `Set-Cookie` transport, or `filter_input(INPUT_POST)` reads the way a real HTTP request can. Tests in this corpus therefore prove the durable boundary-visible core instead: body bytes, decoded JSON payloads, status captured through WordPress hooks, persisted option/post/user state, and explicitly labeled helper or reflection supplements where needed. Any residual contract that depends on actual header transport or HTTP input normalization remains an HTTP-SAPI-only concern and is documented here rather than inferred from CLI oracles that always report empty state.

Row 10 follows that boundary exactly: the dispatcher tests prove response body bytes, the denied-path status code captured through `status_header`, and the export record's persisted state before and after token use. Cache-policy arrays returned by `SUPER_Forms::download_cache_headers()` remain labeled reflection supplements here, not direct transport-header observations.

## Cookie publication (`_sfs_id` session and entry-access credentials)

Under the CLI SAPI `headers_sent()` is true for the entire run and `setcookie()` always returns `false`, so no code path that has to publish a cookie can succeed here. Two hardened writers fail closed on exactly that condition, by design:

- `SUPER_Common::startClientSession()` refuses to mint a brand-new browser session (`includes/class-common.php:610-617`), and its `$publish_session` closure refuses to publish a session id the client does not already hold (`includes/class-common.php:560-577`). A presented cookie whose server record is missing is therefore never adopted, and the freshly issued record is rolled back.
- `SUPER_Common::set_entry_access_cookie()` refuses while headers are sent (`includes/class-common.php:346-360`), so `issue_entry_access_credential()` cannot hand out a single-use entry-access credential.

Consequences for this corpus:

- Session-bound tests seed the exact `_sfs_id` cookie plus `_sfsdata_<id>` option row that a real first response persists, and then require the hardened adoption path to return that same id unchanged. A seeded row always carries one unrelated client-data record, because a row with fewer than three keys is deleted by the first `value => false` client-data write (`includes/class-common.php:649-657`) and the CLI can never publish the replacement cookie.
- The fresh-mint branch itself is proven only negatively (fail closed, presented id never adopted, no orphan `_sfsdata_` row left behind). Its positive half — a new random 64-hex session delivered as `Set-Cookie` — stays an HTTP-SAPI-only concern.
- `Test_Security_Entry_Access` and Row 5 exercise entry-access issuance through the product's own `protected static` transport seam (`set_entry_access_cookie`, dispatched with `static::`), overridden in a `SUPER_Common` subclass. Every authorization decision still runs the real hardened code; only the transport write is substituted.
- Product call sites that invoke `SUPER_Common::issue_entry_access_credential()` directly cannot reach that seam. The public/non-privileged Listings edit modal is one of them (`includes/extensions/listings/form-blank-page-template.php:67-72` requires the credential for any actor without `manage_options`), so those renders are skipped here with an explicit runtime guard rather than proven against a CLI oracle that always reports failure.

## `verifyCSRF()` input

`SUPER_Common::verifyCSRF()` reads the submitted nonce with `filter_input(INPUT_POST, 'sf_nonce')` (`includes/class-common.php:785-793`; unchanged from 6.3.x baseline and beta), and `INPUT_POST` is never populated outside a real HTTP request — assigning `$_POST` does not affect it. Submission tests that must reach a decision *after* the CSRF gate therefore disable only that gate (`csrf_check => 'false'`) for the submit step, after any grant was already issued in session mode. That switch does not relax what is under test: the submission grant check (`includes/class-ajax.php:5292-5338`) never consults the sessionless-mode flag, and the flag's only other effect is upload-receipt bearer binding (`includes/class-ajax.php:5270-5272`).

## PHP-received uploads (`is_uploaded_file()`)

`wp_handle_upload()` only accepts a file that PHP itself received as an upload (`is_uploaded_file()`, wordpress-develop `wp-admin/includes/file.php:936`; the `$action` is fixed to `wp_handle_upload` by the wrapper and cannot be changed through `wp_handle_upload_overrides`). No CLI process has such a file, and the product must not pass a sideload `action` override (`includes/class-ajax.php:8243-8249`): that would downgrade the check to `@is_readable()` and let a crafted `$_FILES['tmp_name']` read arbitrary readable server paths. The three tests that need a *successful* public upload as their control (`Test_Super_Forms_Upload_Policy_Security` at-limit boundary and partial batch, `Test_Super_Forms_Proof_Row7_Upload` valid `full_path`) therefore skip at that step with `require_php_received_upload()`; every rejection half before it still executes. Successful-upload evidence for those paths comes from the browser E2E cells of the strict matrix.
