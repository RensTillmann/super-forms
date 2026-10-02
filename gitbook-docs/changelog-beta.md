---
description: Release notes for the Super Forms beta channel (opt-in early access).
---

# Beta

Beta is opt-in for power users who want early access to new features before they ship to the stable channel. To switch your install to beta, follow the instructions on the [BETA version](developers/beta-version.md) page.

## Super Forms Beta - Changelog

### Oct 02, 2026 - Version 6.4.008

* **Security fix:** Hardened form file uploads, generated file downloads, account registration and updates, Listings entry actions, form administration, contact entry imports and exports, WooCommerce order search, unique code and invoice number generation, PayPal payment notifications and Mailchimp form data (the same fixes as stable 6.3.318).
* **Security fix:** Text a visitor types into a form field is always treated as plain text. `{tags}` and `[shortcodes]` inside submitted values are no longer resolved or run in e-mails (including Triggers e-mails), the success message, PayPal and WooCommerce details, saved entries, the Listings entry view or pre-filled fields. Tags and shortcodes you configure in your form keep working.
* **Security fix:** Values taken from the URL, from saved entries or from saved form progress (including "Add more" columns) are shown literally in form fields, so visitors can no longer read site options or secrets through a link.
* **Security fix:** E-mail `foreach(...)`, `if(...)` and `isset(...)` blocks and `<%field%>` placeholders a visitor types into a field are kept as plain text; only the ones in your own e-mail templates are evaluated (including the `{field}` and file loop `{url}` placeholders inside your own foreach loops).
* **Security fix:** The Register & Login activation e-mail and Front-end Posting tags no longer resolve tags a visitor typed.
* **Security fix:** A password chosen in a Register & Login form is shown exactly as typed in the activation e-mail and is never resolved as a tag or e-mail template code.
* **Security fix:** WooCommerce checkout fields pre-filled from a form no longer resolve tags the visitor typed.
* **Security fix:** Front-end Posting custom meta, Register & Login custom user meta and the Mailchimp, Mailster and MailPoet field mappings never create PHP objects from submitted serialized strings.
* **Security fix:** The contents of `{server_http_referrer}`, `{user_firstname}`, `{user_lastname}`, `{user_display}`, `{user_meta_*}` and `{author_meta_*}` are inserted as plain text; tags and shortcodes inside them are never resolved or run.
* **Security fix:** The "users" retrieve method only shows public name fields to visitors (no e-mail addresses), never renders credentials or private meta, and has a new `super_users_retrieve_allowed_fields` filter.
* **Security fix:** Update information is fetched over HTTPS, and an update is only installed from an HTTPS URL on a trusted host; otherwise administrators see a notice (hosts adjustable with the `super_forms_trusted_update_hosts` filter).
* **Security fix:** The Licenses screen's background requests require the Administrator capability, always go to the Super Forms API, and the license login token is validated before it is stored.
* **Changed:** The licence check times out after 3 seconds, is cached for the site address it was checked for, and falls back to the last known result instead of delaying forms or showing error text.
* **Changed:** `{tags}` passed through form shortcode parameters, for example `[super_form id="123" email="{user_email}"]`, now pre-fill the field literally. Put the tag in the field's own Default value instead.
* **Changed:** Dropdowns, checkboxes and radio buttons that use the "users" retrieve method show no e-mail address to visitors without the `list_users` capability. Use the `super_users_retrieve_allowed_fields` filter to allow more fields.
* **Changed:** WooCommerce Order Search now shows orders only to shop staff (all orders) and to logged-in customers (their own orders); the search settings saved in the form are always used. Site owners can adjust who may search with the `super_wc_order_search_scope` filter.
* **Changed:** Unique codes and invoice numbers are reserved when the form is submitted; the code shown while filling in the form is a preview.
* **Changed:** PayPal payments are now linked to their contact entry, post and user through a signed reference. Payments that were started before this update are still recorded, but do not change the entry, post or user status automatically; update those by hand if needed.
* **Fix:** Corrected validation of date formats without a year and the minimum number of selected dates.
* **Fix:** Custom printing can recover when its permission has expired, instead of immediately falling back to the default form printout.
* **Fix:** Adaptive placeholders update when a field changes without a key press, including browser autofill.
* **Fix:** Multi-step forms wait for asynchronous validation before switching steps (Next button and step tabs).
* **Fix:** Prefilled stored files are no longer uploaded again when a form is submitted a second time.
* **Fix:** Listings: a missing column setting or an empty entry status no longer breaks the listing; the entry status can only be changed with the edit and change-status permissions, and the change is never stored as entry data.
* **Fix:** "Delete files after submission" keeps working when an entry is edited; files that are no longer current are skipped instead of failing the edit.
* **Fix:** Stripe checkout: session expiry is calculated in UTC; unknown or expired sessions on the success, cancel and retry links redirect to the home page instead of showing an error.
* **Fix:** Rewrite rules are refreshed once per plugin version after an update, so sites coming from 6.3 get the new routes (for example the Stripe return URLs) without re-activating the plugin.
* **Fix:** Google Sheets connected fields show an error item instead of a fatal error on PHP older than 7.4 or when the library fails.
* **Fix:** Register & Login: safer user lookups for ACF-based fields; settings use the site URL instead of the server name.
* **Improved:** IBAN field validation is also enforced when the form is submitted.
* **Improved:** The bundled Google API client (2.16.1) and Stripe PHP library (14.2.0) are now managed from one Composer lockfile.
* **Note:** This version requires PHP 7.4 or newer.

This beta release delivers the same security fixes as the stable 6.3.317 and 6.3.318 releases, including the fixes for CVE-2026-28167, CVE-2026-17609 and CVE-2026-17196. Sites on 6.4.001 to 6.4.007 should update.

### Jul 17, 2026 - Version 6.4.007

* **Fix:** Corrected server-side validation so optional fields can be left empty while required fields, including Toggle elements and fields in repeated rows, are enforced.
* **Improved:** Compatibility with WordPress 7.0.1

### Jul 11, 2026 - Version 6.4.006-beta

* **Security fix:** Hardened server-side submission validation so required-field and reCAPTCHA checks cannot be bypassed by direct or automated requests (CVE-2026-14894 follow-up).

### Jul 09, 2026 - Version 6.4.005-beta

* **Fix:** Fatal error after submitting a form containing a signature element when the confirmation email is enabled (500 response; the first email was sent but no success message was shown)
* **Improved:** Signature images are now also attached as regular file attachments in addition to being embedded inline, so email clients that hide inline images still show the signature

### Jul 07, 2026 - Version 6.4.004-beta

* **Security fix:** Hardened form file-upload and signature handling to prevent unauthorized file writes (CVE-2026-14894).

This beta release delivers the same CVE-2026-14894 hardening as the stable 6.3.314 release. See the [6.3.314 release notes](https://github.com/RensTillmann/super-forms/releases/tag/v6.3.314) for details.

### Apr 24, 2024 - Version 6.4.003-beta

* **Added:** New \[Stripe] tab to configure Stripe checkout, allowing for one time payments and recurring payments.
* **Added:** New \[WooCommerce] tab to configure anything related to WooCommerce checkouts, in the next BETA version Instant Orders will also be implemented under this tab.
* **Added:** New \[Triggers] tab to execute actions based on events that occur on your site, temporarily only supports sending E-mail after form submission for testing purposes. Actions can be scheduled at specific dates and times, any E-mail reminders will now be located under the \[Triggers] tab. Actions are compatible with the build-in translation system, allowing for more flexibility when it comes to translating. E-mail attachment may also be translatable so you that you can (for example) send different PDF files based on the users language.
* **Improved:** Inline Signature images inside E-mails instead of as an attachment
* **Improved:** Language switcher state remembered when returning back to form via previous button from Stripe checkout (as an example)
* **Improved:** When filling out the form in a specific language, when returning via the browser back button to the form, it will remember the language and try to populate with remembered form data (if option is enabled)
* **Fix:** `{tag;timestamp}` now also works for Timepicker and fields with field type set to `[date]` (native browser datepicker)
* **Fix:** When a min date for `Datepicker` element is set, make sure the timestamp (epoch) and any other date naming's are updated on page load
* **Fix:** When dealing with Calculator elements inside a dynamic column, when adding a new row, it should reset the value based on the current row fields
* **Fix:** Signature inside conditional hidden column render issue
* **Fix:** Both the `Divider` element and `<hr />` tag were not being printed on PDF when Native mode is enabled
* **Fix:** Issue with Calculator element where math that contains regex like {contains\*}, {^starts\_with\_}, {ends\_with$} would not be calculated in some scenario's
* **Added:** hour/minute/second conversion for Calculator element
* **Improved:** Exclude zero values `0` for WooCommerce emails, PayPal emails and E-mail reminders emails, when the option to exclude empty values is enabled
* **Fix:** Issue where in some scenario's the `Column` element would not close properly which could cause small padding issues
* **Fix:** Bug with displaying `Empty error message` instead of the `Validation error message` when validation is set to `Phone number`
* **Fix:** Bug allowing you to skip `Address Autocomplete` field by pressing `TAB` key without selecting any address
* **Fix:** Bug causing Entry data population to choose multiple dropdown items, example: when the entry value would be `Son` for the dropdown and there would be another dropdown item with value `Son-in-law` it would select both items as selected.
* **Fix:** Some hosting providers might add `.htaccess` files inside all directories for security reasons, skip these when loading Add-ons inside the `/add-ons` folder.
* **Fix:** On back-end Contact Entries page, the date filter would not work properly depending on the WordPress `Settings > General > Date Format` setting
* **Fix:** When field type is set to International phone number (int-phone) automatically set the field validation to `Required (not empty)`
* **Fix:** Do not render/display Forms that have been marked as trashed
* **Added:** Option to create vCards and attach them to Admin and Confirmation E-mails via `Form settings > vCard Attachment`
* **Added:** New `Signature` drawing method for improved device compatibility (Microsoft Surface Pro)
* **Added:** Option to connect Dropdown, Autosuggest, Keyword elements directly with Google Sheets (documentation: [form with google sheets dropdown](https://docs.super-forms.com/features/advanced/wordpress-form-with-google-sheets-dropdown))
* **Added:** Contact entry export now has an option to export the connected WooCommerce order ID (if any)
* **Added:** New option for `PDF Generator` to define image quality inside generated PDF file
* **Added:** New option for `PDF Generator` to generate native PDF elements
* **Added:** New option for `PDF Generator` to enable smart page breaks for elements (put the element on the next page automatically when possible)
* **Added:** New action hook `super_before_login_user_action` to allow for instance update user meta data directly before the user logs in
* **Added:** Option to jump to a specific field so that a user can edit it, simply use `#fieldname` on your href attribute like so `Summary:<br />First name: {first_name} - <a href="#first_name">EDIT</a>`
* **Added:** Option to define wrapper and or field ID attribute elements, when left blank the default ID will be `sf-wrapper-1234-yourfieldname` and `sf-field-582-1-yourfieldname` where `582` would be the form ID and `1` the form index (if you have multiple forms this will auto increment by one).
* **Added:** `Listings` when editing entries, you can define if the user is allowed to change the entry status, and disallow to change the entry if it already has a specific status
* **Improved:** `Form Settings > Form Settings > Custom redirect URL` can now be translated to redirect to custom pages e.g. `https://domain.com/thank-you/`, `https://domain.com/de/vielen-dank/`, \`https://domain.com/fr/merci-beaucoup/
* **Improved:** When `native` PDF generation is enabled, add the country flag next to the international phone number field
* **Improved:** Scrolling to next focused field or next multi-part. Only scroll when required based on the elements top/bottom positioning compared to window height
* **Improved:** When using keyboard arrows up/down on radio button do not go to next step automatically when enabled on multi-part
* **Improved:** Grid/Columns now using flex method
* **Improved:** `Color picker` element small responsiveness fix
* **Improved:** `PDF page breaks` are calculated after HTML block is updated/changed. Allowing to use the PDF page break html directly inside foreach loops. Example which loops over uploaded files:

```html
foreach(file;loop):
  if(<%counter%>!='1'):<div class="super-shortcode super-field super-pdf_page_break"></div>endif;
  <img src="<%url%>" style="display:block;width:300px;" />
endforeach;
```

* **Fix:** Bug when using the validation option `Allow field to be empty > Yes, but not when the following conditions are met`
* **Fix:** Issue with populating form with entry data in combination with saving existing entry while logged in as non administrator
* **Fix:** Stripe `Success URL` not working, so that user redirects to a specific thank you page after returning from a completed checkout
* **Fix:** Fix file upload when using ACF Pro Gallery field when saving a custom post via `Front-end Posting` feature
* **Fix:** Signature not populated from `Save form progression` and `Retrieve previous entry data`. Also, when retrieved from entry data disallow editing the existing signature.
* **Fix:** `Signature` element, rare bug which caused the canvas to not be full width, which would cut off the signature by 50%
* **Fix:** `<%attachment%>` and `<%attachment_id%>` inside `foreach` loop inside HTML element should return the file attachment ID not the file name
* **Fix:** Bug with updating existing contact entry and preventing creating a new one when using field name `hidden_contact_entry_id` with Default value set to: `{user_last_entry_id}`
* **Fix:** Issue when placing the same form on the same page multiple times, causing the submitted values for Calculator element to be incorrect
* **Fix:** Toggle element render issue when placing the same form multiple times on the same page
* **Fix:** Excluding Signature `string attachment` from Admin/Confirmation E-mail
* **Fix:** Issue when dragging elements inside column that are inside an Accordion/TAB element
* **Fix:** Issue with using `foreach loop` inside E-mails not replacing the tags with field value correctly
* **Fix:** New signature mobile canvas width not adjusting properly
* **Fix:** Conditional logic conflict with columns that are set to be hidden in some scenario's
* **Added:** Option to calculate distances between multiple addresses e.g: (between A to B, between B to C and from C back to A)
* **Added:** Option `Disable browser translation` under `Translations` TAB to disable browsers to translate the form
* **Added:** New predefined tags to retrieve form submission date inside emails: `submission_date_gmt`, `submission_hours_gmt`, `submission_timestamp_gmt`, `submission_date`, `submission_hours`, `submission_timestamp`
* **Added:** `isset()` and `!isset()` methods to check if a field was conditionally hidden/visible. Useful inside HTML elements and E-mails. Example here: (https://renstillmann.github.io/super-forms/#/email-if-statements?id=checking-if-a-field-exists)
* **Added:** Option to add attributes on the listings shortcode to apply hardcoded filters e.g: `[super_listings list="1" id="61602" entry_status="completed"]` would only display entries with status `Completed`
* **Added:** Extra tags to retrieve date names for datepicker element: `{date;day_name}`, `{date;day_name_short}`, `{date;day_name_shortest}`, `{date;day_of_week}`. This way you can display specific time slots based on a specific week day
* **Added:** Filter logic option `Exact match` for autosuggest feature to filter exact `Label` value for an item
* **Added:** Filter logic case sensitive search
* **Added:** Form locker option `Do not lock form, but still display a message` to only display a message but still allow user to submit the form even if the threshold was reached.
* **Added:** Option to pre-load conditional logics via Ajax request, to store it into an object on the client side, instead of in the source code. Useful/required when dealing with 500+ conditions
* **Added:** Option to attach XML file with form data to admin E-mails via `Form Settings > XML Attachment` on builder page
* **Improved:** Significant speed improvements/optimization for large/complex forms with a lot of conditional logic/variable conditions/calculations.
* **Fix:** Allow the `Currency` field to have zero value e.g: `0.00`
* **Fix:** Issue with `{register_generated_password}` tag not working when sending activation email after user registration
* **Fix:** Issue with `Unique code generation` when using invoice increment option. Not saving the invoice number increment in some ocassions depending on the configured settings
* **Fix:** Issue with generated PDF when theme placing footer scripts/styles inside a custom node. Causing PDF to miss specific styles. An example them is the famous `Avada` theme.
* **Fix:** PDF line-height/vertical alignment of text for text and textarea input fields improvements
* **Fix:** Issue with Listings filters causing to display entries that have `post_author` value `0` to all users even though it shouldn't
* **Fix:** Prevent users from entering with a year longer than 4 characters in size for datepicker
* **Fix:** Form data population issue for `Keyword/tags field`
* **Added:** Missing country `Kosovo` for the `Countries (ISO2)` and `Countries (FULL)` elements
* **Improved:** PDF Generator speed optimization
* **Improved:** When using `Quantity` field with steps defined to `0.5` make sure the user can enter a single decimal point by hand. When using `0.05` user will be able to enter 2 decimal point numbers instead. When the step is defined to `1` user won't be able to enter any decimals
* **Fix:** PDF page break element orientation change bug
* **Fix:** Javascript error `indexOf is not a function` when called on a number value
* **Fix:** When populating signatures via `Contact Entry Search` field make sure signature can't be edited by the user when defined to do so
* **Fix:** Column layout combination 3/5 + 1/5 + 1/5 cuasing last column to be placed on a new line
* **Fix:** When exporting entry data and the server returns an error e.g: `cURL error...`, make sure to delete the file before returning 404 error code, and log the incident
* **Fix:** When `Save form progress` is enabled make sure to not populate `Hidden fields` values
* **Fix:** When using `Dynamic column` inside `Multi-parts` make sure when adding a new column it doesn't switch to the first multi-part
* **Fix:** When storing client data make sure the generate ID does not exceed 64 characters in length, due to WordPress options table > `option_name varchar(64)` limit
* **Fix:** `Calculator` element should not replace tags starting with `option_` with custom predefined values, instead if a user has a field named `option_radio` it should grab that value, and not try to grab the option value from the DB table
* **Fix:** When using Dynamic Column the `%d` parameter wouldn't be replaced with the current column number correctly for the E-mail label/Entry Label setting
* **Fix:** When using `Name Your Price` with `WooCommerce Checkout` in combination with thousand separator `.` (dot) and decimal `,` (comma) for prices (can be defined in the WooCommerce settings), make sure the price is formatted accordingly before parsing it to Name Your Price.
* **Fix:** PHP notices/errors

### Older beta releases

The v6.3.7xx-beta series (Oct 2022 - Apr 2023) shipped without per-release changelog entries. The cumulative changes from that era are reflected in the stable releases v6.3.306 through v6.3.312 documented on the [Stable channel changelog](changelog-stable.md).
