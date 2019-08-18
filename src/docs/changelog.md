### Aug 18, 2019 - Version 4.7.63
- Added: `US States` dropdown element
- Changed: `Countries` dropdown element will no longer use the `contries.txt` to retrieve items, instead you can now use the `Custom items` method to change the list. This was not possible with coutries.txt when updating super forms changes would be lost.
- Fix: Make sure the `Default value` for `Rating` element is of type `int`.
- Fix: Bug with `Dynamic Columns` in combination with `Retrieve form data from users last submission` upon adding a new dynamic column the `Default value` would be incorrect.
- Fix: Bug with `Signature Add-on` and `Color picker` not initializing upon dragging it on the canvas (due to Envato rules `¯\_(ツ)_/¯`)
- Fix: Bug with checkbox/radio items not being updated upon "Update Element"
- Removed: Skype element, API doesn't exist anymore
- Added: [Data Storage](data-storage) section in documentation describing where specific data being stored by super forms
- Added: Option to load list into radio/checkbox/dropdown based on custom meta data field of the current post
- Improved: Rephrased "Current page, post or profile author meta data" to "Current author meta data"
- Temp: temporary disabling nonce check because it is causing a lot of problems with websites that are caching their pages.
- Fix: Textarea not populating with Entry data
- Fix: Allow email addresses to contain a "+" symbol
- Fix: When no variable conditions where met, do not empty the field, but rather keep the value it currently has (this makes sure it won't conflict with `?contact_entry_id=XXXX` when form was populated with entry data or possibly other data that was set via a GET parameter)
- Fix: issue with default radio button option and conditional logic on page load not affected
- Fix: Missing arguments for `generate_random_code()`
- Fix: Bug when both `Autosuggest` and `Keyword` is enabled for Text field

### Jun 26, 2019 - Version 4.7.40
- Added: Option to choose which Image Library to use to scale and orient images via `Super Forms > Settings > File Upload Settings`
- Added: Option to delete files from server after form submission via `Super Forms > Settings > File Upload Settings`
- Added: Option to delete associated files after deleting a Contact Entry via `Super Forms > Settings > File Upload Settings`
- Fix: Due to Envato plugin requirements not allowing us to to prefix `$handle` with `super-` to enqueue scripts, it caused issues with plugins loading old versions of Font Awesome, resulting in none existing icons. This is unacceptable and we decided to change the $handle to `font-awesome-v5.9` so technically it doesn't have a prefix, and it makes sure that the latest version of Font Awesome will be loaded no matter what (when needed of course), even when a theme or plugin loads an older version.
- Fix: $_GET parameters containing "Advanced tag values" not working on dropdown/checkbox/radio
- Fix: Calculator Add-on JavaScript error `split()` is not a function on none string data
- Fix issue with email settings translation string escaping HTML resulting in raw HTML emails
- Fix $functions undefined (for none bundle super forms)

### Jun 15, 2019 - Version 4.7.0
- Compliance: Working towards Envato WordPress Requirements Badge/Compliance
  - Calculator Add-on: now using MathJS library for improved security when doing calculations
  - Passed all JavaScript files through `JShint` excluding third party libraries3
  - Escaping all Translatable strings
- Added: Missing Font Awesome 5 brand icons & updated Font Awesome to v5.9
- Added: Option to define a so called `specifier` to position the counter for `Email Labels` when using Dynamic Columns, example:
  - `Product %d quantity:` would be converted into `Product 3 quantity:`
  - `Product %d price:` would be converted into `Product 3 price:`
- Added: Compatibility for TinyMCE Visual editor to count words with Calculator Add-on
- Added: Option to specify field type for "Text" fields, allowing to determine what "Keyboard Layout" it should use on mobile devices. To name a few:
  - `email` (for email keyboard layout)
  - `tel` (for phone number keyboard layout)
  - `url` (for URL keyboard layout)
  - `number` (for number keyboard layout)
  - `date` (for keyboard layout to choose a specific date
  - `month` (for keyboard layout to choose a specific month)
- Added: A custom Ajax handler for faster Ajax requests (significant speed improvement for building/editing forms)
- Added: Translation feature (allows you to translate your form into multiple languages, this also includes translating specific form settings)
	*when in translation mode, you won't be able to delete and change the layout of the form, just the strings of each element and the form settings*
- Added: Compatibility for HTML elements to handle {tags} with regexes `*` (contains), `$` (ends with) and `^` (starts with)
- Improved: Custom ajax handler compatible with older WP versions (tested up to v4.7)
- Improved: Mailchimp error debugging and other small improvements
- Improved: Speed improvement upon page load, now skipping calculator elements of which the value didn't yet change, so no need to loop through any elements connected to this field
- Improved: Currency field will now have field type set to `tel` for phonenumber keyboard layout to enter numbers easily on mobile devices
- Fix: Text field with variable condition should not be reset/applied upon submitting form due to possible custom user input
- Fix: CSV Attachment Add-on not applying correct delimiter from settings
- Fix: issue with new ajax handler stripping slashes (it shouldn't be doing this) was resulting in issues with HTML element and line breaks
- Fix: PHP notice about undefined variables
- Fix: Issue with autosuggest keywords on mobile phone when autofill is applied by the browser, it would not validate the field correctly
- Fix: Issue with new ajax handler not working in combination with active WC installation
- Fix: Signature attachment not being a valid bitmap file when sending email over SMTP
- Fix: Bug fix conditional logic when setting $_GET on radio buttons
- Fix: Radio buttons not responsding to predefined `$_GET` or `$_POST` parameters
- Fix: When doing custom POST and "Enable custom parameter string for POST method" is enabled file URL's where not parsed as data
- Fix: Bug in Ajax handler, make sure to not load external unrequired plugins, because they might depend on functions that we didn't load
- Fix: Compatibility for Ajax handler with Multisites
- Fix: reCAPTCHA v2 bug
- Fix: HTML element in back-end not wrapping words
- Fix: Calculator add-on not working when using both regex and advanced tags like so: `{_option$;3}` or `{server_*;4}` or `{server_^;2}` etc.

### Apr 22, 2019 - Version 4.6.0
- Improved: Update plugin checker system
- New: Email Reminders Add-on
- Added: Option to retrieve timestamp with {tag;timestamp} for datepicker elements
- Added: Option for dropdowns and checkboxes etc. to filter based on post status for retrieve method `post_type`
- Added: reCAPTCHA v3 support
- Added: Option to hide Multi-part steps on mobile devices (useful to keep things clean when working with a lot of multi-parts)
- Added: Possibility to do if statements inside if statements and to use `&&` and `||` operators. Works for both HTML elements and email bodies. Example:
	`if({field}=='1' && {field2}!='2'):
		if({age}==16 || {age}==17):
			Show this text only when age is sixteen or seventeen, and only when field equals 1 and field2 not equals 2
		endif;
	endif;`
- Added: New option `Include dynamic data (enable this when using dynamic columns)` for sending POST data, this can be used with for instance `WebMerge` to loop through dynamic columns when creating PDF's
- Added: Conditional logic field selected can now be entered manually, this allows you to use advanced tags to get a field value, but it also allows you to combine 2 field selectors together like so: {option;2}_{color;2} == [your conditional value] etc.
- Added: Option to do foreach() loops inside HTML elements to create a summary when using dynamic columns. Read here for more info [https://renstillmann.github.io/super-forms/#/email-foreach-loops](email-foreach-loops).
- Added: Option to do if() statements inside HTML elements. Read here for more info [https://renstillmann.github.io/super-forms/#/email-if-statements](email-if-statements)
- Added: Uploaded files will now be parsed onto `super_before_email_success_msg_action` action hook, allowing to transfer files to DropBox or Google Drive through Zapier Add-on
- Added: In the back-end when creating forms you will now be able to `Transfer` elements from form A to form B, or to reposition it easily within form A itself
- Added: Text fields can now also become a so called `Variable field` just like hidden fields, meaning you can populate them with data dynamically, while still allowing the user to edit this value
- Added: Option to parse parameter tags on to the shortcode to poupulate fields with data e.g: `[super_form id="1234" first_name="John" last_name="Willson"]`
- Added: Option for Text fields to search for WooCommerce Orders
- Added: Option to disable cookie storage for Varnish cache or other caching engines via `Super Forms > Settings > Form Settings` > `Allow storing cookies`
- Changed: file extion from .txt to .html for export and import files due to PHP recognizing .txt file as text/plain MIME type, which causes WordPress to fail to upload this .txt file resulting in a "Sorry, this file type is not permitted for security reasons". It is strongly discouraged to solve this problem by setting `ALLOW_UNFILTERED_UPLOADS` to true in wp-config.php.
- Changed: Updated Font Awesome to v5.7.2
- Changed: When leaving `Enter custom parameter string` option blank when doing custom POST, it will now submit all form data.
- Improved: A new way/method to verify the reCAPTCHA response, no longer checking via seperate Ajax call but instead upon form submission itself (this solves the error message hanging issue/bug)
- Improved: Make sure that .txt files can be uploaded due to new mimes type upload policy of wordpress not being able to upload txt files for security reasons
- Improved: replaced `eval()` function with `Function('"use strict";return ()')()`
- Improved: always parse the radix on parseInt() functions
- Improved: When defining conditional logic notify/alert user about possible loop creation when user is pointing conditional logic to it's own field (this would otherwise cause a stack overflow)
- Improved: `do_shortcode()` now called on the email body making it shortcode compatible
- Improved: Slider label positioning improved
- Improved: Only show admin notice once after updating plugin to check out `What's new` in the latest version. Also added option to completely disable to show update notices in the future from `Settings > Backend settings`
- Improved: Undo/Redo feature
- Improved: Form elements json now saved in localStorage, instead of a textarea element
- Improved: When using dynamic columns, a seperate data key called `_super_dynamic_data` will hold all the dynamic column data as an Array object (usefull for usage with for instance `WebMerge`) to generate PDF files with product tables/rows
- Fix: WooCommerce Checkout add-on setting `Send email after order completed` was not compatible with [E-mail IF statements](email-if-statements)
- Fix: Issue with File Upload element when using custom Image button, it would still display the placeholder text
- Fix: Issue with WooCommerce Checkout not saving CC and BCC settings
- Fix: bug in Calculator Add-on when using advanced tags in combination with wildcards e.g: `{field_*;2}` inside math
- Fix: when excluding sundays "0" wasn't working, had to put "0,"
- Fix: Star rating was not intialized inside dynamic column
- Fix: reCaptcha trying to be rendered more than once
- Fix: dynamic column foreach email loop bug when custom padding enabled on column
- Fix: Multi-part autostep not working in some circumstances with conditional logic being used
- Fix: Using star rating element inside conditional logic doesn't allow to go to next step automatically
- Fix: Slider label initial value not correctly displayed based on decimal settings
- Fix: Colorpicker inside multi-part should never focus upon clicking "Next" button when colorpicker is the first element
- Fix: Multi-part skipping radio/checkboxes (would skip to for instance textarea below radio button and autofocus the textarea skipping the radio buttons)
- Added: option for dropdown retrieve method "post type" to filter based on categories and or tags (taxonomy filter)
- Added: new option for dropdowns to not only choose from Slug, ID or Title as value for dropdown items when using for instance custom post type, you can now also choose a "custom" method, and define custom meta data to return instead.
- Improved: When a dropdown has retrieve method post type 'product' and the product is a variable product it will list all it's variations
- Fix: Bug with HTML element inside dynamic columns not correctly renaming tags that retrieve multi values e.g: changing `{fieldname;3}` to `{fieldname_2;3}` etc.
- Fix: Path Traversal in File Upload via PHPSESSID Cookie and potentially Remote Code Execution
- Fix: issue with conditional logic running based of page load via field values that where set through $_GET parameters
- Added: option to add post meta data as item attribute for dropdown elements (to do things from the front-end useful for developers)
- Fix: Javascript error when Conditional Logic was set based on an element that was deleted at a later stage in time

### Jan 31, 2019 - Version 4.5.0
- Added: option to not exclude empty values from being saved for contact entries
- Added: option to automatically exclude empty fields from email loop
- Added: Polyfill for IE9+ support for JS `closest()` function
- Added: Compatibility with {tags} for Custom form post URL
- Added: option to filter entries based on date range
- Added: option to return rows from custom db table for dropdowns
- Fix: color picker not initialized correctly inside dynamic columns
- Fix: bug with conditional logic and dropdown when using `greater than` methods
- Fix: Issue with dropdown searching
- Fix: Call to undefined function wc_get_product()
- Fix: Keyword autosuggest CSV retrieve method not correctly retrieving items
- Fix: Keyword autosuggest Max/Min selections
- Improved: Keyword autosuggest search speed for larger amount of items

### Nov 13, 2018 - Version 4.4.0
- Added: Option to disallow users to filter items on dropdowns, which will also prevent keyboard from popping up on mobile devices
- Added: tag to retrieve product regular price `{product_regular_price}`
- Added: tag to retrieve product sale price `{product_sale_price}`
- Added: tag to retrieve product price `{product_price}` (returns sale price if any otherwise regular price) 
- Added: option to retrieve product attributes for dropdown,radio,checkboxes
- Added: tag `{product_attributes_****}` to retrieve product attributes
- Added: option to send POST as JSON string
- Added: Russian languages files
- Added: tag to retrieve Form Settings with {form_setting_*****} e.g: {form_setting_email_body} or {form_setting_header_subject}
- Added: Option to set the maximum upload size for all files combined for a file upload element
- Added: Documentation about [Save Form Progression](save-form-progression.md)
- Added: Documentation about [Retrieve form data from users last submission](retrieve-data-last-submission.md)
- Added: Documentation about [Prevent submitting form on pressing "Enter" keyboard button](prevent-submit-on-enter-button.md)
- Added: Documentation about [Hide form after submitting](hide-form-after-submitting.md)
- Added: Documentation about [Form redirect](form-redirect.md)
- Added: Documentation about [Custom form POST URL](custom-form-post-url.md)
- Added: Documentation about [Contact Entries](contact-entries.md)
- Added: Documentation about [Clear/reset form after submitting](clear-reset-form-after-submitting.md)
- Added: Documentation about [Autopopulate fields](autopopulate-fields.md)
- Improved: autosuggest filter speed when dealing with 1000+ records
- Improved: Slider element, amount positioining sometimes a little bit off
- Improved: Decode email header function
- Fix: Multi-item element not remembering default selected options correctly
- Fix: IE bug fixes
- Fix: Emails where being stripped from + characters, which is a valid email address
- Fix: Navigate through global settings and remove slashes from the values, to fix escaped quote issues in emails

### Jul 29, 2018 - Version 4.3.0
- Added: new filter hook - `super_redirect_url_filter`  (filter hook to change the redirect URL after form submission)
- Added: Option to disable scrolling for multi-part next prev buttons
- Added: Option to prevent scrolling effect for multi-part when an error was found
- Added: Variable fields in combination with {tags} will now also be able to have dynamic values within dynamic columns (add more +)
- Added: New filter hook `super_' . $tag . '_' . $atts['name'] . '_items_filter` (to filter items of dropdowns/checkboxes/radio)
- Fix: Bug with checkboxes/radio precheck not working
- Fix: use wp_slash() to make sure any backslashes used in custom regex is escaped properly
- Fix: Error message on file upload element not disappearing after trying to upload to large file size or not allowed file extension
- Fix: Issue with dynamic columns in combination with calculator element (not updating calculation correctly after adding column)

### Jun 18, 2018 - Version 4.2.0
- Added: Option to set a threshold for `keyup` event on currency field to only execute hook when user stopped typing (usefull for large forms with above average calculations etc.)
- Added: Option to automatically replace line breaks for `<br />` tags on HTML element content
- Added: Option to add custom javascript under `Super Forms > Settings > Custom JS`
- Added: Option to create variable conditional logic with a CSV file, see `[Variable Fields]` documentation for more information
- Added: new filter hook - `super_conditional_items_*****_filter`  (filter hook to change conditional items on the fly for specific element)
- Added: new filter hook - `super_variable_conditions_*****_filter`  (filter hook to change variable conditions on the fly for specific field)
- Improved: Bind `keyup` for Quantity field to trigger field change hook
- Fix: Google ReCAPTCHA not always being rendered on page load
- Fix: Quantity field not populating with last entry data
- Fix: Currency field blur/focus bug
- Fix: Website URL validation only allowed lowercase letters
- Fix: Google ReCAPTCHA no longer allows to use callback function that contains a . (dot) in the function name. Replaced `SUPER.reCaptcha` with `SUPERreCaptcha`
- Fix: Multi-part not autmoatically switching to next step (if enabled) when hidden field is located inside the mulit-part
- Fix: Bug with {tags} in combination with calculator add-on, would retrieve the HTML value version for calculations
- Fix: Make forms and entries none plublic so that search engines won't be able to index them
- Fix: Javascript Syntax Error in Safari

### Apr 13, 2018 - Version 4.1.0
- Added: Option to do if statements in success message
- Added: `{author_meta_****}` tag to retrieve current post author or profile user custom meta data
- Improved: hide text "Allow saving form with duplicate field names (for developers only)" in back-end when action bar is sticky
- Improved: Conditional Validation option can now also work in combination with float numbers
- Improved: File upload button name line height and checkbox/radio :after top position RTL forms
- Improved: Currency field now compatible with conditional validations
- Fix: bug with variable field in combination with conditionally hidden
- Fix: Conflict with jquery scope for hint.js causing a javascript error
- Fix: Columns responsiveness was broken because of some future development code
- Fix: Bug with front-end forms not loading correct settings/styles from global settings (not merging correctly)
- Fix: Bug fix with automatic line breaks for HTML element

### Mar 16, 2018 - Version 4.0.0
- Added: Introduction tutorial (to explain back-end)
- Added: de_DE_formal translation file
- Added: `{user_meta_****}` tag to retrieve current logged in user custom meta data
- Added: `{post_meta_****}` tag to retrieve current post custom meta data
- Added: Option to retrieve current author meta data for dropdown element with
- Added: `{author_id}` and `{author_name}` tags which do the same thing as the `{post_author_id}` and `{post_author_name}` tags
- Added: minimize/maximize toggle button on builder page
- Added: option to even save form when it contains duplicate field names (for developers)
- Added: (GDPR compliance) Option to only save contact entry when a specific condition is met (Form Settings > Form Settings)
- Improved: author tags will now also retrieve the author ID and author name when located on profile page of an author
- Improved: Export/import system for single forms via Form Settings > Export & Import
- Improved: Global settings and form settings are now merged for better sync and more controllable way when having to deal with many forms
- Improved: Use `CSS Flexbox Layout Module` to solve Safari 0px height issue/bug for conditional hidden items
- Updated: de_DE translation file
- Fix: removed 'wpembed' from tinymce plugin list (was dropped since wordpress 4.8)
- Fix: Issue with Register & Login Add-on when saving custom user meta data
- Fix: Issue with Print action for Button element when no HTML file was choosen

### Feb 28, 2018 - Version 3.9.0
- Added: Tag to retrieve selected option label in emails with `{fieldname;label}`
- Added: Option to replace comma's with HTML in emails for checkbox/radio/dropdown elements under Advanced TAB
- Added: Cool new feature to do if foreach loops inside email body content with {tag} compatibility e.g:
  - This method is intended to be used in combination with dynamic columns
  - **Example:** `foreach(first_name): Person #<%counter%>: <%first_name%> <%last_name%><br /> endforeach;`
- Added: Cool new feature to do if `isset` and `!isset` checks inside email body content with {tag} compatibility e.g:
  - This method should be used whenever you conditionally hide fields and they are no longer set and {tags} inside email would then not be converted because no such field was found
  - **Example 1:** `isset(first_name): The field exists! endif;`
  - **Example 2:** `!isset(first_name): This field does not exists! endif;`
  - **Example 3:** `isset(first_name): This field exists! elseif: This field does not exists! endif;`
- Added: Option for submit button to print or save PDF based on custom HTML that supports {tags} to dynamically retrieve form data
- Added: Print button can support signatures when used like `<embed type="image/png" src="{signature}"></embed>`
- Added: tag `{dynamic_column_counter}` to retrieve current dynamic column number added by user (this tag can currently only be used inside HTML element)
- Added: `stripslashes` for heading title / desciption
- Added: `htmlentities` Flags `ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED`
- Improved: Don't save settings that are the same as global settings
- Fix: Form settings that did not have a filter value where not correctly updates when changing and saving form.
- Fix: &quot was being replaced with " when updating/saving elements
- Fix: `{tag;label}` not removed from HTML element when field is conditionally hidden

### Jan 29, 2018 - Version 3.8.0
- Added: Compatibility for variable fields with advanced tags e.g: `{field;2}`
- Added: Option "User Form locker / submission limit", this option allows you to only allow a logged in user to submit a specific form once only
- Added: Option to Toggle all fields to be exported to CSV on Contact Entry page in back-end
- Added: "Submitted by:" on Contact Entries page when a form was submitted by a logged in user
- Added: Option to retrieve entry data based on `$_GET['contact_entry_id']` or `$_POST['contact_entry_id']` (this will override the logged in user last submission data if it is set)
- Improved: When registering new user with Register & Login Add-on and entry is created the author will be the newly created user
- Improved: Builder speed
- Improved: Compressed the form json code by roughly 50% up to 80%
- Improved: Compressed the form settings json code by roughly 50% up to 80%
- Improved: Redo / Undo system, resulting in a smoother user experience when building forms on low end devices
- Fix: Undefined index: admin_attachments
- Fix: Form backup history restore sometimes returns blank forms (json error)
- Fix: Button link open new tab not working
- Fix: Google analytics conversion tracking not working when Custom form POST method is enabled
- Fix: Only save tracking settings on global level and not on form level
- Fix: HTML entities in json form code should not be decoded, e.g: &quot should be &quot and not converted to "
- Fix: Honeypot captcha is filled out by Google Chrome saved username/passwords
- Fix: Distance calculations variable overridden with destination address
- Fix: Icons inside field with Medium size field
- Fix: CSV + Checkbox issue

### Dec 22, 2017 - Version 3.7.0
- Added: Tags field (text field can be converted into a tag/keyword field) via "Enable keyword field" TAB when editing the text field
- Added: Deutsch/German translation (if you have translation files let us know so we can add them to the core files)
- Added: Option to retrieve tags for autosuggest fields
- Added: Option to change unique field name on the fly on builder page
- Improved: When Ajax is enabled, and google map API is not filled out, do not load the js library
- Improved: Automatically rename duplicated fields for more user-friendly work flow
- Improved: Back-end field filter code execution/speed improvement
- Improved: Google Map element can now udpate map address dynamically based on {tag}
- Fix: Bug with conditional logic not scanning on form level but on active multi-part level in rare occasions.
- Fix: Toggle field start value "On" not affected

### Dec 08, 2017 - Version 3.6.0
- Added: Option to add google analytics tracking events via: Super Forms > Settings > Form Settings
- Added: Option to center form via: Form Settings > Theme & Colors
- Added: Cool new feature to do if statements inside email body content with {tag} compatibility e.g:
  - (possible constructors are: ==, !=, >, <, >=, <=)
  - **Example 1:** `if({field}==123): Extra information here... endif;`
  - **Example 2:** `if({age}<18): You are underaged! elseif: You are an adult! endif;`
- Added: Extra conditional validation methods with option to compare 2 values instead of just 1 e.g: 
 - `> && < Greater than AND Less than`
 - `> || < Greater than OR Less than`
 - `>= && < Greater than or equal to AND Less than`
 - `>= || < Greater than or equal to OR Less than`
 - `> && <= Greater than AND Less than or equal to`
 - `> || <= Greater than OR Less than or equal to`
 - `>= && <= Greater than or equal to AND Less than or equal to`
 - `>= || <= Greater than or equal to OR Less than or equal to`
- Added: Ability to retrieve checkbox/radio/dropdown Label with tag `{field;label}` (currently works for variable fields, conditional logics only in combination with checkbox/radio/dropdowns)
- Added: Option for datepicker field to exclude specific days from the calendar so users won't be able to select them when choosing a date
- Added: Option to disable autofocus for first element inside multi-part when multi-part becomes active
- Added: {tags} to retrieve cart information when WooCommerce is installed and activated: `{wc_cart_total}`, `{wc_cart_total_float}`, `{wc_cart_items}`, `{wc_cart_items_price}`
- Added: Option to disable autocompletion for specific fields via "Advanced > Disable autocompletion"
- Added: Option to do custom POST request with custom parameters instead of sending all available data (because some API's only allow you to POST parameters they request)
- Improved: RTL styles
- Improved: Changed submit button from `<a>` tag to `<div>` tag to avoid conflict with themes that care less about no-conflict policy
- Fix: issue with validating fields in combination with conditional logic and multi-parts that have "Check for errors before going to next step" enabled
- Fix: Issue with dropdowns inside dynamic column (clear field function was not updating field value correctly) result in conditions and calculations not properly updating
- Fix: Issue with conditional logic AND method
- Fix: bug with TAB index for fields
- Fix: Some PHP warnings

### Nov 23, 2017 - Version 3.5.0
- Added: Compatibility with {tags} for conditional logic values and AND values
- Added: Google Map element (with polylines options for drawing point A to B dynamically, for instance for calc. distance with google address autocomplete)
- Added: Option for Google Address Autocomplete to populate street name and number and visa versa at once (combined)
- Added: Backwards compatibility with older form codes that have image field and other HTML field in group form_elements instead of html_elements
- Added: Shortcode compatibility for default field value
- Added: Google distance calculation setting for dropdown element (allows to let user choose specific locations and calculate distance based on that)
- Improved: Split up Form and HTML elements under their own TAB
- Improved: When google API query limit is reached for distance calculations show message to the user
- Improved: Skip AND method if not used for variable fields
- Fix: Bug fixed after improving skipping AND method if not used for variable fields
- Fix: Remove datepicker intialize class after column is dynamically duplicated
- Fix: When using custom submit button with custom URL redirect enable the option to set custom Button name was hidden
- Fix: Buttons dropdown setting for custom contact entry statuses
- Changed: Auto updates for Envato element users
- Removed: product activation TAB

### Nov 9, 2017 - Version 3.4.0
- Added: Option to reset submission counter
- Added: New tag `{submission_count}` Retrieves the total submission count (if form locker is used)
- Added: New tag `{last_entry_status}` Retrieves the latest Contact Entry status
- Added: contact entry statuses
- Added: lock form after specific amount of submissions
- Added: option to reset form lock daily/weekly/monthly/yearly
- Added: Option to send default attachment(s) for all email for both admin and confirmation emails
- Improved: Allow decimal values for quantity field
- Fix: Issue with conditional logic function when using multi-parts and when "Check for errors before going to next step" is enabled on the multi-part
- Fix: Check if HTTP_REFERRER is defined, otherwise php will throw error
- Fix: `{tag;1}`, `{tag;2}` etc. where only accepting int types and not var types
- Fix: If Ante/Post meridiem 12 hour format make sure to convert it to 24 hour format in order to return correct timestamp to do calculations with calculator add-on
- Fix: In rare cases custom regex e.g: \d would result in invalid json string seeing blank form in back-end
- Fix: Contact entry not updating if Contact Entry saving itself is disabled but Updating Contact Entries is enabled
- Updated: Fontawesome to v4.7

### Oct 16, 2017 - Version 3.3.0
- Changed: made plugin ready for Envato Elements
- Added: Tag `{server_http_referrer}` to retrieve the previous location where the user navigate from before landing on the page with the form
- Added: Tag `{server_http_referrer_session}` saves HTTP_REFERRER into session so it will not be subject to change after navigating away and returning back at later time
- Added: Tags to retrieve current date values: `{server_timestamp}`, `{server_day}`, `{server_month}`, `{server_year}`, `{server_hour}`, `{server_minute}`, `{server_seconds}`
- Added: Ability to update Contact Entry title
- Added: Option to exclude fields from being save in Contact Entries
- Added: Option to duplicate Contact Entries
- Added: Compatibility with Visual Composer (JS Composer), Added a "Super Form" element
- Added: Option to disable form submission on pressing "Enter" key on keyboard (via Form Settings > Form Settings)
- Added: Option to show/hide Multi-part progress bar via Form Settings > Theme & Colors
- Added: Option to show/hide Multi-part steps via Form Settings > Theme & Colors
- Added: JS action hook: SUPER.after_appending_duplicated_column_hook()
- Improved: Make sure to skip the multi-part if no visible elements are found (in case multi-part is blank because conditional logic hides every element)
- Fix: Issue with double quotes in json elements, sometimes backups are giving back a invalid json format resulting in a blank form
- Fix: Do not skip multi-part when only HTML element or other similar element is found
- Fix: $skip undefined
- Fix: Dynamic columns not correctly populated with entry data if GET was set with value of search field 
- Fix: bug with max/min selection on checkbox element (displaying error message while not should display error message)
- Fix: Fix RTL columns, may not reverse when responsiveness is disabled on mobile device width.
- Fix: Bug fix, when form progress save enabled and using datepickers, causes ajax loop (since v3.2.0)
- Fix: Sessions not being cleaned from database, and do not save empty sessions (empty array or session with status false)
- Fix: Issue with AddStringAttachment function PHP7+ (decode after string has been parsed)

### Sep 27, 2017 - Version 3.2.0
- Added: Option to save form progress (when user leaves the page, and returns, form will not have lost it's progression)
- Added: Option for variable field (hidden field) to return varchar or integer based on specified {tag} e.g: `{field;2;int}` to sum values and return as integer, or `{field;2;var}` to return as varchar/text (var is used by default)
- Added: Option to skip specific fields from being populated with entry data after a successfull contact entry search
- Added: Honeypot captcha to avoid spam by default (of course you can still use Google reCAPTCHA for a better anti-spam if required)
- Added: Option to add custom TAB index (order) for fields
- Added: new filter hook for javascrip translation string and other manipulation such as tab index class exclusion - super_common_i18n_filter
- Added: new filter hook for javascrip translation string and other manipulation such as tab index class exclusion - super_elements_i18n_filter
- Added: Tag to retrieve user roles from logged in user `{user_roles}`
- Changed: Allow uppercase for unique field names in backend (previously uppercase characters where converted to lowercase automatically)
- Removed: use of session_start() for performance improvements, replaced with custom Session manager
- Improved: TAB index, and mobile TAB index for RTL
- Improved: google places address complete
- Improved: distance calculation between addresses
- Fix: Add address latitude and longitude for ACF google map compatibility
- Fix: issue with multi-part TAB index
- Fix: CSS bug with gradient background in style-default.php
- Fix: RTL support for columns incorrect order on mobile devices
- Fix: Issue with google address autocomplete inside dynamic columns (add more +)

### Sep 01, 2017 - Version 3.1.0
- Added: Option to set custom colors for radio/checkbox
- Added: Dutch (NL) translation
- Added: Backup history and restore previous form backup/autosave
- Added: Redo/undo buttons on form builder page
- Added: Option to save multiple values for dropdown/radio/checkbox and retrieve with tags like: value1;value2  retrieve value1 with `{field;1}` and value2 with `{field;2}` etc.
- Added: Distance/Duration calculator between 2 addresses / zipcodes (google directions api)
- Added: Option to change the first day of the week on date picker element
- Added: new filter hook - super_form_before_first_form_element_filter
- Added: new filter hook - super_form_after_last_form_element_filter
- Added: Option for text fields to automatically transform user input to uppercase text
- Added: Option to disable automatic line breaks in emails (usefull for pure HTML emails)
- Added: Option to add the IP address to the columns on the Contact Entries listing
- Added: When $_GET or $_POST contains the key of a field that is used to search/autopopulate the form based on a contact entry title, automatically update the form on page load if a entry was found based on the parameter value
- Improved: datepicker improvements
- Improved: Documentation extended with some chapters
- Fix: iOS devices upload file extension uppercase/lowercase issue
- Fix: Only execute conditional logic for "and field" if the "and method" is set
- Fix: Theme Salient manipulates form HTML causing file upload button not triggering anymore, applied a hotfix for it, but theme author should solve this in future, bad selectors are being used! 
- Fix: Make sure any type of On value for toggle button is compatible with the setting "Retrieve form data from users last submission"
- Fix: Field that has tags system enabled not working with setting "Retrieve form data from users last submission"
- Fix: multipart validation not scrolling to first field with error when "Check for errors before going to next step" is enabled
- Fix: write file header for correct encoding when exporting contact entries
- Fix: Toggle field and dropdown reset
- Fix: Time calculation bug with timepickers with the format Ante/Post meridiem
- Fix: Success message not being displayed if custom POST method is enabled
- Fix: Dropdown issue placeholder not correctly set after populating form data based on search field
- Fix: Fileupload + icon little offset top little bit out of sync with field itself
- Fix: Fileupload issue when mendatory and conditionally hidden
- Fix: PHP notice "A non well formed numeric value encountered" for PHP 7.1+ (Hexadecimal strings are no longer considered numeric)
- Fix: Bug with variable field (hidden field) when using "?? contain" condition on checkbox/radio/dropdowns
- Fix: Bug in IE11 with checkbox/radio that have images
- Fix: issue with quotes on variable fields values
- Fix: Issue with multi-parts and tags not updating correctly
- Fix: Mailster bug issue with parsing an empty header via array() Mailster doesn't like this when they hook into wp_mail()
- Fix: When disabling the Thank you message via checkbox the session should not write the thank you information
- Fix: Datepicker table padding
- Fix: Because JS Composer developers using global CSS selector and didn't wanted to change it we decided to take matter in our own hands and fixed a margin issue when form is placed inside Text Block element of Visual Composer
- Fix: When using reset form, dropdown value must be updated accordingly to the default selected option of de dropdown and if case none, make sure it is emptied. (this was conflicting with conditional logic and other calculations)
- Fix: Make sure google.api script is loaded after super forms script
- Fix: Issue with default value that only contains a number
- Fix: Make sure ajax request URL contains lang parameter for WPML compatibility

### May 11, 2017 - Version 3.0.0
- Added: Documentation section (will be updated from time to time, under construction)
- Added: Google places autocomplete/autopopulate fields
- Added: Ability to replace variable field {tags} with actual field values
- Added: New tag `{post_permalink}` (will retrieve the currnt post URL)
- Added: Option to retrieve {tags} for hidden fields default value setting
- Added: Option to set max width/height for radio and checkbox image selections (if empty it will apply 100% width and no max height)
- Added: Option to set width and height for radio/checkbox images (leave blank for original image size)
- Added: Option to set color for preloader icon (form loading icon)
- Changed: populating form search entry loading icon replaced gif for font loading icon
- Changed: Form preloader icon updated for fontawesome icon (GIF would freeze because Js processing is single threaded)
- Improved: Variable fields can now also contain {tags} inside it's condition (it will be replaced with the field value dynamically)
- Improved: Notify user if Unique field name is empty (from now on this field is required)
- Fix: loading icon not completely centered (25px of to left)
- Fix: RTL alignment issues / padding issues with some elements depending on theme field sizes
- Fix: Issue with replacing whitespace for value containing only the letter "s"
- Fix: issue with dropdowns items that have the same value being send to email while user didn't select them
- Fix: $entry_data not required for output_builder_html()
- Fix: buttons radio/checkbox icon left alignment for medium size theme 
- Fix: Issue with empty default values returning Array in field and textarea due to {tag} not found
- Fix: ReCAPTCHA alignment fix
- Fix: Checkbox and radio buttons images now based on image original size (no longer cut of/cropped)
- Fix: Checkbox and radio default selected item bug with conditional logic

### Apr 17, 2017 - Version 2.9.0
- Added: Form setup wizard (easy for selecting theme and setting other common options like: Email To: and Subject: settings)
- Added: Toggle switch (on/off) field
- Added: Tags/keyword option for text field
- Added: Option to auto populate form with data based on last contact entry from a logged in user.
- Added: `{post_author_id}` and `{post_author_email}` tags to retrieve author information inside the form based on the current page/post
- Improved: when doing a POST also send json formatted data
- Fix: plus icon not correctly positioned in IE in back-end for items on dropdown/checkboxes etc.
- Fix: Problem with checkbox/radio images cutting the image, using contain method instead of cover now.
- Fix: issue with showing "All" Contact entries also showing deleted items
- Fix: Browsing images not working back-end checkbox images when adding new checkbox option
- Fix: When using "Clear / reset the form after submitting" do not empty hidden_form_id field
- Fix: When using "Clear / reset the form after submitting" make sure we trigger conditional logic and other actions after fields are emptied
- Fix: issue with unique code gerator when length is set to 0
- Fix: issue with file upload field inside dynamic columns

### Mar 12, 2017 - Version 2.8.0
- Added: Option to generate invoice numbers with leading zero's for hidden fields when "generate random number" is enabled
- Added: custom reply to headers independent for admin and confirmation emails
- Added: Option to send independent additional headers for admin and confirmation emails
- Added: Option to also add CC and BCC for confirmation emails
- Added: new filter hook - super_countries_list_filter
- Added: unique id attribute on form, might become in handy for any plugin/script that only accepts selection by id attribute
- Improved: User friendly and logical navigation for the email settings and headers
- Improved: Quantity field can now have decimal steps e.g 0.5 or custom increment below 1
- Improved: Ability to use line breaks as default value on textarea element
- Improved: user friendly settings more logically displayed/sorted
- Improved: Save generated code(s) into options table instaed of postmeta table per contact entry
- Improved: Use transient to cache generated codes instead of saving it to database on each page load
- Fix: Reply-To: header setting for admin and confirmation email not replacing {tags} with values
- Fix: issue with generating random codes typo: upercase > uppercase
- Fix: issue with dynamic columns and variable fields not updating {tags} correctly on data attribute and {tags} inside new_value attribute
- Fix: SMPT throws PHP error when additional headers are not empty
- Fix: not triggering to update field values based on fields that where conditionally hidden and after visible again
- Removed: placeholder setting for currency fields

### Feb 26, 2017 - Version 2.7.0
- Improved: When replacing {tags} with correct value, replace logged in user data after all other tags have been replaced
- Improved: When choosing CSV file for a field to retrieve data, make sure only CSV files can be selected from media library
- Improved: When using dynamic columns clear/reset the field values inside the newly added set of fields
- Fix: Prefix and Suffix not being added for unique code generation setting (hidden field)
- Fix: Uppercase must be empty by default when generating unique code generation
- Fix: When a file upload field was used, it would be replaced with previous loop field value in confirmation emails only
- Fix: Some issues with icons in other themes
- Fix: When file upload was empty, show the field in contact entry and tell user that no files where uploaded

### Feb 22, 2017 - Version 2.6.0
- Added: IBAN validation for text fields
- Improved: When not using preloader, and using multi-parts make sure the first multi-part is active within php code so we don't have to wait for js script to be loaded and handle this
- Fix: Themes overriding styles on the conditional logic textarea and variable conditions textarea
- Fix: make sure to exclude forms from the default wordpress search
- Fix: Datepicker issue when connecting 2 dates with eachother and the other having both weekends and work days disabled (beforeShowDay function must always return array)
- Fix: Issue with updating contact entry data if search field is used
- Fix: If any theme would ever remove the href tag completely from the button do a typeof undefined check
- Fix: Currency field when populating data initialize the field with the correct format on page load
- Fix: Conflict with conditional logic when using multiple forms on single page that contain the same field names (on submit button click)
- Fix: Issue replacing tags when using a custom redirect after form submission
- Fix: Filtering contact entries from back-end when custom db prefix is used
- Fix: When deleting dynamic column make sure we do not skip the fields that need to be triggered based on deleted fields
- Fix: Make sure that if a radio element has 2 or more items with the same value to only set 1 to be active by default (maybe in future we should add a check for duplicate values before saving the element in back-end)
- Fix: IE issue with function variable parsed as object (IE didn't like this)
- Fix: Custom submit button Redirect to link or URL not retrieving correct permalink by ID for pages and posts
- Fix: When redirecting form to custom page that doesn't contain the form styles, make sure the success message still uses the theme styles based on it's form settings

### Feb 06, 2017 - Version 2.5.0
- Improved: Speed, skipping fields that have been triggered previously by the same changed field when calling JS hook: after_field_change_blur_hook()
- Fix: Some third party plugins sometimes conflict with file upload element
- Fix: RTL for success message
- Fix: Back-end preview mode conflict with conditional logic (finding 2 fields with same name because of builder page containing the same field)
- Fix: Issue with datepicker format returning a javascript error with Date.parseExact()

### Jan 25, 2017 - Version 2.4.0
- Added: Loading icon for search field for contact entry/auto populate field with entry data
- Added: JS action hook: SUPER.after_duplicating_column_hook()
- Changed: CSS selector for messages from: error to: super-error, success to: super-success, info to: super-info
- Improved: Overal code/speed optimization
- Improved: Dropdown item responsiveness (don't cut words)
- Fix: When auto populating contact entry data make sure to update conditions / variable fields
- Fix: issue with variable fields containing tags that have the same name inside it as the tag name itself e.g: `option_{option}`
- Fix: when updating conditional logic and the column is updated to become either hidden or visible, make sure to call the blur hook to the fields inside the column
- Fix: issue with removing dynamic column and updating conditions/math/variable fields
- Fix: Drag & Drop issue with multiple file upload elements (adding image to all the file uploads instead of only one)
- Fix: undefined variable $class on currency element
- Fix: File upload issue: cannot call methods on fileupload prior to initialization
- Fix: Even when max / min file upload was set to 0 it would still display an error message
- Fix: checking with !session_id() instead of session_status()==PHP_SESSION_NONE for PHP 5.4+

### Jan 18, 2017 - Version 2.3.0
- Improved: speed for conditional logic
- Improved: speed for variable fields
- Improved: overal code optimizations
- Fix: Issue with variable fields containing {tags} and not being updated if the {tag} field was updated
- Fix: image not being visible when printing contact entry
- Fix: compatibility with conditional logic for currency fields
- Fix: Image max width problem (responsiveness)
- Updated: PHPMailer to v5.2.22 due to remote code execution vulnerability

### Jan 05, 2017 - Version 2.2.0
- Added: Option to let hidden fields generate a random unique number (options: length, prefix, suffix, uppercase, lowercase, characters, symbols, numbers)
- Added: Convert text field to search field to search contact entries by title, and auto populate form fields with entry data (search methods: equals / contains)
- Added: Option to enable updating contact entry data if one was found based on a search field
- Added: Option to do a custom POST method to a custom URL with all form fields posted
- Fix: First dropdown fields automatically focussed when going to next / prev multi-part step
- Fix: JS Composer using global styles conflicting with super forms duplicate column + button making it invisible when it should be visible

### Dec 18, 2016 - Version 2.1.0
- Added: JS action hook: SUPER.before_scrolling_to_message_hook()
- Added: JS action hook: SUPER.before_scrolling_to_error_hook()
- Added: Option to use {tags} in variable field conditional logic e.g: [Field 1] >= {field2}
- Fix: Make sure grid system column counter is reset after form has been generated to prevent issues with multiple forms on a single page
- Included: Document with all actions and filter hooks

### Dec 12, 2016 - Version 2.0.0
- Added: Currency field
- Added: Button option to reset / clear the form fields
- Added: Option to reset / clear the form after submitting
- Added: JS action hook: SUPER.after_form_cleared_hook()
- Added: Option to enter the submit button loading state text e.g: Loading...
- Added: Option to change button loading state name via settings
- Added: Option to hide / show the form after form being submitted
- Added: Option to set margin for success message (thank you message)
- Added: validate multi-part before going to next step
- Added: new filter hook - super_before_sending_email_attachments_filter
- Added: new filter hook - super_before_sending_email_confirm_attachments_filter
- Fix: datepicker not showing because of timepicker undefined bug
- Fix: bug with max / min selection for dropdown and checkboxes
- Fix: multi-part validation trying to submit the form if no errors where found in the mulit-part
- Fix: Slider field thousand seperator
- Improved: A better mobile user friendly datepicker
- Improved: A better overall mobile user friendly experience
- Changed: When checkbox has set a maximum don't show an error to users after selecting to many items, instead disable selecting items

### Nov 17, 2016 - Version 1.9.0
- Added: Own custom Import & Export functionality for Forms (no longer need to install the default WP import/export plugin that uses XML format)
- Added: Option to hide column on mobile devices based on form width
- Added: Option to hide column on mobile devices based on screen width
- Added: Option to disable resizing to 100% on mobile devices based on form width
- Added: Option to disable resizing to 100% on mobile devices based on screen width
- Added: Option to force 100% on mobile devices even if one of the other responsive settings are enabled
- Added: Position option for columns: static, absolute, relative, fixed
- Added: Positioning option for columns in pixels (top, left, right, bottom)
- Added: Custom field class option for all elements 
- Added: Custom (wrapper) class option for all elements 
- Added: Background image option for columns
- Added: Option to set background opacity on columns
- Added: JS action hook: SUPER.after_preview_loaded_hook()
- Added: JS action hook: SUPER.before_submit_button_click_hook()
- Fix: File upload field not displaying errors inside multi-part column
- Fix: HTML element {tags} must only reflect on the form elements inside it's current form and not an other form (when more than 1 is used on a single page)
- Fix: Issue with masked input not converting the mask to a string
- Fix: applied stripslashes on HTML element for title, description and html to avoid backslashes when qoutes are used
- Fix: replaced field type 'varchar' with 'var' due to some servers do not like varchar being parsed in an object or string via wordpress ajax calls
- Fix: Image alignment
- Fix: .popup class replaced with .super-popup to avoid conflicts on builder page
- Fix: Browse images in back-end initialized multiple times
- Fix: When using multiple forms the second form submit button wouldn't appear
- Fix: When multiple custom submit buttons are used always the last button was being removed thinking it was the default submit button
- Improved: Code optimization, massive speed improvement for large forms on mobile devices
- Improved: When icon border color is empty do not add the border

### Nov 7, 2016 - Version 1.8.0
- Fix: Conditional logic / Variable logic issue with incorrect float convertion
- Fix: Issue with form autocomplete
- Fix: file upload element exclude from email setting not only working on body content but not for the email attachment
- Fix: conditional logic not being updated on columns that are inside a dynamic column
- Fix: Using custom submit button with preloader disabled shows the default button for a split second
- Fix: $forms_custom_css undefined
- Fix: Search issue contact entries
- Improved: Updated plugin activation timeout from 5 seconds to 60 seconds for slow servers
- Added: new filter hook - super_before_sending_email_data_filter

### Oct 25, 2016 - Version 1.7.0
- Added: Option to update contact entry data via back-end
- Added: Option to export individual Contact entries and select the fields to export + rename the column names
- Added: Option to filter contact entries based on a specific form
- Added: Radio buttons now can return custom taxonomy, post type and CSV items
- Added: Option to count words on textarea fields that can be used with the calculator add-on (usefull for translation estimations)
- Improved: Contact entry search query
- Improved: Conditional logic speed
- Improved: Variable conditions speed
- Improved: Code optimization
- Improved: When adding dynamic fields update conditional logic and variable logic field names only if they exists otherwise skip them
- Improved: Variable fields can now contain multiple {tags}
- Improved: File Upload system (no refreshing required when one file didn't make it or when any other error is returned)
- Fix: Conditional logic not working on dropdown
- Fix: Issue with submit button name being stripped/validated on builder page
- Fix: Dynamic fields not updating calculations after deleting a row
- Fix: Not able to download contact entry CSV export
- Fix: Incorrect offset on builder page when other plugin messages are being shown
- Fix: Minimal theme radio buttons without icon to much padding left
- Fix: Avada making the datepicker month next/prev buttons font color white
- Fix: undefined $data, issue with dynamic columns and updating the conditional logic dynamically
- Fix: When using reCAPTCHA and only sending dropdown label the value is duplicated in email
- Removed: filter function do_shortcode on the_content, causes issues in some ocasions (let the theme handle this filter instead)

### Oct 15, 2016 - Version 1.6.0
- Fixed Vulnrebility: Unrestricted File Upload
- Fix: Small bug with incorrect calculation order in combination with conditional logic

### Oct 12, 2016 - Version 1.5.0
- Fix: Javascript compatibility issue with Safari browser
- Fix: Last field duplicated in confirmation email (send to submitted)
- Improved: When typing a unique field name unwanted characters are stripped, only numbers, letters, - and _ are allowed.
- Added: Option to only allow users to select weekends or work days for datepickers

### Oct 8, 2016 - Version 1.4.0
- Fix: Issue with file uploading when filename contains comma's
- Fix: Issue with variable fields and calculations incorrect order resulting in wrong calculations
- Added: Option to retrieve Contact Entry ID with tag: `{contact_entry_id}`  (can be used in success message and emails)

### Oct 5, 2016 - Version 1.3.0
- Fix: Conflict class WP_AutoUpdate, changed it to SUPER_WP_AutoUpdate
- Fix: Dropdown no longer being largen when focussed
- Fix: Duplicate column fields no longer hiding dropdown content (overflow:hidden removed)
- Fix: saving directory home_url() changed to site_url() (in case core files are located different location on server)
- Fix: Checkbox images retrieving thumbnail version, now returning original image
- Fix: Issue with font-awesome stylesheet not having a unique name, changed it to super-font-awesome
- Fix: {tag} in HTML element not displaying negative calculator value correctly
- Added: Option to update conditional logic dynamically when using dynamic fields (add more +)
- Added: JS action hook: SUPER.after_responsive_form_hook()
- Added: JS action hook: SUPER.after_duplicate_column_fields_hook()
- Added: JS filter hook: SUPER.after_form_data_collected_hook()
- Added: option to add padding to columns
- Added: option to add background color to columns
- Added: Option to return current date (server time) for datepicker field
- Added: Option to return current time (server time) for timepicker field
- Added: Option to add input mask (usefull for phone numbers and other validations)
- Changed: Removed bottom padding of form, you can now change the padding with settings
- Improved: several CSS styles

### Sep 20, 2016 - Version 1.2.9
- Fix: Greek characters issue with CSV file
- Fix: Datepicker field not initialized within dynamic columns
- Fix: Datepicker max/min range affecting the validation max/min characters
- Fix: Icon color settings not showing when selected "No (show)"
- Fix: Class align-left conflict with Heading elements in Visual Composer
- Fix: HTML value not updated correctly with {tag} for calculator element
- Added: Option to save only the value or both value and label for contact entry data for elements dropdown/checkbox/radio
- Added: new action hook - super_after_saving_contact_entry_action
- Added: new filter hook - super_after_contact_entry_data_filter
- Added: Option to make disable fields (disallow user from editing input value)
- Added: Option to use {tags} within the variable field update value setting
- Added: Option to add the Form name to columns on the the contact entries listing

### Sep 5, 2016 - Version 1.2.8
- Fix: Avada giving styles to anything with popup class, conflicting Super Forms tooltips
- Fix: Firefox issue with editing labels in form builder
- Added: Super Forms Marketplace (share / sell your own forms)
- Added: RTL support (text from right to left)
- Added: Option to add custom CSS per form
- Added: Option to allow user input filter the dropdown options/values
- Added: Option to add custom class on button element
- Added: new filter hook - super_form_settings_filter
- Improved: Grid system
- Improved: In backend font-awesome only loaded on the Super Forms pages that uses fontawesom icons

### Aug 5, 2016 - Version 1.2.7
- Added: 5 new demo forms!
- Fix: Small bug when changing column size (in some cases not being saved/remembered)
- Fix: Uncaught TypeError when datepicker default value is empty
- Fix: Only apply meta_query custom search for super forms contact entries
- Fix: When WP network site is enabled, wrong directory is called for media uploads
- Added: Option to calculate difference between 2 timepickers (calculator add-on required!)
- Added: Option to calculate age based on birth date for datepickers (calculator add-on required!)
- Added: Date range option when exporting contact entries to CSV
- Added: Labeling for Columns and Multi-parts on form builder page (easier to keep track of sections)
- Added: Option to make hidden field a variable (change value dynamically with conditional logic)
- Added: Ability to use {tags} in HTML elements (tags will be updated on the fly!)
- Added: Option to use {tags} inside Additional headers setting
- Added: Setting to chose what value should be send to emails for dropdowns, checkbox and radio buttons
- Added: `{field_label_****}` tag to use in emails and subjects etc.
- Added: Option to do math between datepickers with calculator add-on
- Added: new filter hook - super_common_attributes_filter
- Improved: Contact entry export to CSV now includes: entry_id, entry_title, entry_date, entry_author, entry_status and entry_ip

### July 26, 2016 - Version 1.2.6
- Fix: Missing options for Slider field
- Added: Option to save custom contact entry titles including the option to use {tags}
- Added: Ability to automatically update the plugin without the need to delete it first
- Added: Option to import Contact Entries from CSV file
- Improved: Contact entry filter / search function
- Improved: __DIR__ replaced with dirname( __FILE__ ) due to PHP version < 5.4

### July 14, 2016 - Version 1.2.5
- Fix: min/max number for quantity field
- Fix: File upload on multi-part sites are not working
- Fix: Issue with drag and drop in some cases the page scrolls down to the bottom automatically
- Fix: Issue with Internet Explorer and WP text editor
- Fix: Removed limitation of 5 for dropdowns when custom post type is selected
- Added: Option to add custom regex for field validation
- Added: Float regex as a ready to use option to for field validation
- Added: Option to add/deduct days between connected datepickers (this will change the max/min date between connected dates)
- Added: Option to choose to return slug, ID or title for autosuggest for both post and taxonomy
- Added: Option to choose to return slug, ID or title for dropdowns for both post and taxonomy
- Added: Option to set delimiter and enclosure for dropdowns and autosuggest when using CSV file
- Added: Option to translate/rename multi-part Prev and Next buttons independently
- Added: 5 demo forms for Add-on Front-end posting
- Added: new filter hook - super_form_before_do_shortcode_filter
- Improved: General CSS improvements
- Improved: Dropdown items now have overflow hidden to avoid problems with long options
- Improved: TAB functionality for both multi-part and without multi-part columns
- Improved: When checkbox/radio images are being used, and the image no longer exists, a placeholder image will show up

### June 27, 2016 - Version 1.2.4
- Fix: Safari input field line-height
- Fix: Multi-part prev button not correctly aligned on front-end
- Fix: When button setting is set to full width multi-part buttons are also affected
- Fix: Image browser not intialized when adding new checkbox element dynamically in backend
- Fix: Conditional logic display block/none issue in safari and IE
- Fix: Attachment meta data not being saved correctly
- Fix: Conditional logic for file upload field
- Added: Option to transform textarea field into a text editor (TinyMCE)
- Added: Autosuggest/Autocomplete option for text field
- Added: Quantity field (with -/+ buttons)
- Added: Option to set a transparent background for fields
- Added: Option to retrieve specific post types for dropdown and autosuggest
- Updated: Fontawesome icons

### May 26, 2016 - Version 1.2.3
- Fix: PHP Zend error when APC is enabled (only appeared on specific PHP versions)
- Fix: Radio button dot alignment with horizontal alignment
- Fix: Issue with "contains" conditional logic in combination on dropdown/checkbox/radio
- Fix: Finger touch for slider element on mobile devices
- Fix: When slider is put inside multi-part it's not set to default positioning due to multi-part having display:none; before form is rendered
- Fix: Issue with prev/next buttons being removed when adding custom button to multi-part
- Fix: When predefined elements are being dropped, make sure to check if we are dropping multiple items and then do the check to rename existing field names
- Improved: Tooltips for mobile devices
- Improved: Responsiveness backend (multi-items dropdown/radio/checkbox)
- Improved: Conditional logic filter priority set to 50 so it will be fired at later point
- Added: Option to automatically go to next step for multi-parts
- Added: Dummy content (40+ example forms)
- Added: Option to add image to checkbox/radio items (image selection)
- Removed: Placeholer option on slider element (not needed)

### May 15, 2016 - Version 1.2.2
- Fix: wp_enqueue_media(); not called on settings page
- Fix: Conditional logic in combination with preloader
- Fix: File upload error message fading out after 1 sec.
- Fix: Default radio/checkbox/dropdown selection now automatically apply/filter conditional logics
- Fix: Enqueue datepicker / timepicker if Ajax calls are enabled
- Improved: Now using wp_remote_post instead of file_get_contents because of the 15 sec. open connection on some hosts
- Improved: Allowed extensions for file uploads
- Improved: Overall conditional logic
- Improved: Overall drag & drop sensitity
- Improved: When using SMTP settings it will now check wether or not the settings are correct and if we could establish a connection
- Improved: default "Field is required" string now translation ready (instead of manually adding error messages for each field)
- Added: Option to set text and textarea fields to be disabled
- Added: Option to make columns invisible although they can still be used for calculations and saved or send by mail
- Added: Option to minimize elements and columns/multiparts in backend (even more user friendly form building!)
- Added: Currency, Decimals, Thousand separator, Decimal separator options for Slider field
- Added: parameter entry_id on action hook "super_before_email_success_msg_action"
- Added: Option to do a single condition with 2 seperate validations with (AND / OR)

### May 3, 2016 - Version 1.2.1
- Fix: When multi-part is being used with multiple custom buttons skip the button clone function
- Fix: Color settings for custom button not being retrieved correctly when editing button
- Fix: z-index on Save/Clear/Edit/Preview actions lowered due to overlapping the WP admin bar
- Fix: Dropdown with Icon inside field and right aligned arrow is hidden below the Icon
- Improved: Bug fixed combination columns inside multipart
- Improved: Conditional logic (contains ??) in combination with checkbox/dropdown with multi select
- Improved: When reCAPTCHA key or secret is not filled out, show a notice to the user
- Added: Option to remove margin on field
- Added: Option to set a fixed width on the field wrapper
- Added: Option to append class to the HTML element
- Added: New element: Slider (dragger)
- Added: More flexibility with HTML element
- Changed: Checkbox/Radio buttons will now have their custom UI instead of default browser UI with custom colors
- Changed: Don't show reCAPTCHA key/secret under settings on create form page

### April 29, 2016 - Version 1.2
- Fix: If a theme is using an ajax call get_the_title cannot be used for `{post_title}` to retrieve the Post Title, now it will check if post_id is set by the ajax call, if this is the case it will try to use it to retrieve the title, otherwise the field value will stay empty
- Fix: Conditional logic broken on column after changing .column class to .super-column for js_composer conflict with styles
- Fix: If multiple forms are used on a single page the form will scroll to the first error on the page instead of checking on the current form itself
- Fix: For the element button the target attribute (open in new browser) was not being affected
- Fix: If contact entries are exported to CSV the /uploads/files folder must exist
- Improved: Column system
- Added: Option to enable Ajax mode if theme uses Ajax to load content dynamically
- Added: Option to align the reCAPTCHA element (left, center, right) default is right alignment
- Changed: Default positioning for errors are now bottom right

### April 24, 2016 - Version 1.1.9
- Fix: wp_mail() additional headers not parsed since v1.1.7
- Added: Option to export Contact entries to CSV file (including attachments via URLs)
- Added: Progress bar on file upload element
- Improved: When alement is added, it will automatically be renamed if same field name exists
- Improved: Better script for processing attachments to email for both wp_mail & smtp
- Improved: Form builder page is now more user friendly (backend)
- Improved: Responsiveness of form builder page (backend)

### April 22, 2016 - Version 1.1.8
- Fix: translation issue name conversion
- Added: Option to override button color and icon and other settings for the button element or just select to use the default settings
- Added: All fields can now auto populate values if an URL parameter with the field name has been set
- Added: Datepicker can now connect with another datepicker (usefull to set a max/min range for both pickers
- Changed: Upload files to Media Library instead of plugin folder (prevents missing files after deleting plugin)
- Changed: Submit button cannot be clicked twice, and will display a loading icon

### April 17, 2016 - Version 1.1.7
- Fix: style class ".column" changed to ".super-column" because of JS Composer conflicting on .column class
- Fix: added line-height to fields to make sure theme styles don't override it
- Added: "Add more +"" option for columns to let users duplicate all the fields inside the column dynamically

### April 15, 2016 - Version 1.1.6
- Fix: Uncaught TypeError: Cannot convert object to primitive value
- Fix: reCAPTCHA conditional-validation-value undefined
- Fix: When minimum files are not set for file upload it will not proceed to submit the form
- Fix: textarea cannot add line breaks, form is trying to submit after pressing enter when textarea is focussed
- Fix: Warning: array_merge(): Argument #2 is not an array, when first time creating Form
- Added: Submit Button element, allows you to add conditional logic on submit button if placed inside colum
- Added: Tags to retrieve values of logged in user `{user_login}`, `{user_email}`, `{user_firstname}`, `{user_lastname}`, `{user_display}`, `{user_id}`

### April 12, 2016 - Version 1.1.5
- Fix: When a Form is duplicated in some case the fields are not being rendered
- Fix: Dropdown with Minimal theme not closing correctly
- Improved: Calendar translation strings
- Added: Option to allow field to be empty and to only validate the field when field is not empty
- Added: Deactivate button added to Settings page

### March 16, 2016 - Version 1.1.4
- Fix: Some small issues with TABBING through fields in combination with hidden fields and conditional logics inside double columns
- Fix: Datepicker minimum date negative number not being applied (date range min/max)
- Fix: When countries.txt now being loaded through Curl to avoid problems on servers with scraping security
- Fix: When conditional logic is used and the field is inside 2 columns it is still being validated
- Fix: Special conditional field validation not working with numbers
- Fix: Divider width percentage not working, only pixels are working
- Added: Option to allow field to be empty and to only validate the field when field is not empty
- Added: Max/Min number for text field
- Added: default value option for datepicker field
- Added: Year range for datepicker field
- Added: validation option to conditionally check on an other fields value with field tag e.g `{password}`, this way you can for instance add a password confirm check (usefull for registration forms)
- Changed: function to return dynamic functions as an array, this way it could easily be implemented into the preview in the backend while creating forms

### March 4, 2016 - Version 1.1.3
- Fix: using stripslashes() for email data to remove possible quotes
- Fix: version not included in some styles/scripts (problems with cache not updated after new version is uploaded)
- Fix: issue with dropdown and file upload maximum items setting not triggered to set field to multiple items allowed
- Fix: $ conflicting, use jQuery instead
- Fix: when TABBING through fields inside multipart it will switch to next multipart automatically
- Fix: when keyboard arrows are being used to select dropdown arrows the conditional logic was not being triggered
- Fix: if next field is a checkbox or radio button the TAB did not focus this field
- Improved: line height for dropdown items adjusted for more user friendly expierience
- Added: functionality to dynamically add and execute javascript functions with new provided filter hooks
- Added: new filter hook - super_common_js_dynamic_functions_filter

### February 28, 2016 - Version 1.1.2
- Fixed: When pressed enter on selected dropdown item conditional logic was not triggered
- Fixed: When submit is clicked and multi-part does not contain errors the error clas is not being removed
- Improved: responsiveness for dropdowns on mobile
- Improved: Removed the check icon on dropdown selected items, only highlighted from now on
- Added: Option to redirect to a custom URL and add paramaters with the use of tags e.g: `?username={field_username}`

### February 25, 2016 - Version 1.1.1
- Fix: Not able to use arrow up/down and Enter key when dropdown element is focussed
- Improved: When TABBING through fields, the submit button will also be focused and enter can trigger to submit the form
- Improved: For a better user experience field validation is now only triggered on change and blur (unfocus)
- Improved: When Multi-part contains errors it will scroll to this section and will make it visible

### February 24, 2016 - Version 1.1.0
- Fix: Multi-part buttons (prev/next/submit) not correctly aligned and improved Responsiveness for mobile devices
- Improved: For some themes no alert icon was shown for the multi-part section if fields where not correctly filled out inside it
- Improved: When using TAB to go through the form, the dropdown element was being skipped (since custom UI)
- Improved: Changed color to a lighter color of the placeholder for settings like CC/BCC
- Improved: When TAB is used the very next field will not be validated instantly, but only after a change was made 
- Improved: When Multi-part next/prev button is being clicked scroll to top of the next multi-part section (usefull for long sections)
- Changed: countries.txt is now no longer automatically sorted with asort()
- Changed: countries.txt can now be customized (e.g add new countries or add most used countries to the top of the file)

### February 19, 2016 - Version 1.0.9
- Fix: Result 'status' in filter super_before_email_loop_data_filter not being set caused uncaught error
- Fix: When in preview mode conditional logic not triggered after changing dropdown selection
- Fix: reCAPTCHA initialized twice instead of once, which results in error 'placeholder must be empty'
- Fix: reCAPTCHA now also loaded in preview mode
- Changed: When deleting plugin and uploading newer version do not reset default settings
- Added: Purchase code API activation
- Added: Possibility to not display message after redirect if Thanks title and description are both empty

### February 11, 2016 - Version 1.0.8.1
- Fix: after previous update all fields could have duplicate field name
- Added: New filter hook - super_before_email_loop_data_filter

### February 9, 2016 - Version 1.0.8
- Fix: Multiple file upload fields not seen as unique field names when actually containing unique names
- Fix: When conditional logic used on an element inside a column that is placed inside a multipart it fails to display the multipart
- Fix: Submit button sometimes not correctly aligned
- Added: New filter hook - super_form_styles_filter
- Added: New predefined element (Email address)

### January 14, 2016 - Version 1.0.7
- Fix: Datepacker in some cases not visible when theme is overiding styles
- Fix: Element to browse images only initialized when editing element and not on Create form page load
- Fix: SUPER_Settings class php error when in preview mode
- Added: Possibility to translate the date picker month and day names

### January 9, 2016 - Version 1.0.6
- Fix: For Add-on purposes, and future updates: Forms that have been saved after new settings have been added, it will use their default values
- Fix: Nested conditional logic not working (elements inside columns)
- FIx: Tooltips not being displayed when mouseover
- Improved: SMTP emailer with more options to adjust - keepalive, ssl, tls, timeout, smtp debug mode
- Improved: Element panel scrolls down with user (usefull for long forms)
- Improved: Overal improvements for dropdown field
- Improved: Overal improvements for conditional logics
- Improved: Tags functions, add-ons can now hook into tags and add their own if needed
- Added: Files are now attached as an file in emails
- Added: Option to retrieve tags inside the thank you title and description after a successful submitted form
- Added: New notifications function for better and more flexible way to display messages to users
- Added: Option to retrieve Post title (post_title) and Post ID (post_ID) as default value
- Added: Conditional Validation for fields (== equal, ? contains, > greater than etc.)
- Added: Dropdown CSV upload possibility
- Added: Dropdown retrieve WP categories (by taxonomy name e.g category, product_cat etc.)
- Added: Option to export and import form settings per form and the default form settings
- Added: For Add-on purposes, a function to return error and success messages
- Added: New action hook - super_before_email_success_msg_action
- Added: New action hook - super_before_printing_message
- Changed: Action hook from super_before_printing_redirect_js_action to super_before_email_success_msg_action

### December 18, 2015 - Version 1.0.5
- Added: Possibility to use multiple forms on one page with each a different style
- Added: New date format dd-mm-yy for date field
- Added: Possibility to set a custom date format for date fields
- Fix: When HTML is applied on checkbox/radio labels, it was not correctly escaping it's attributes on the builder page (backend)

### December 17, 2015 - Version 1.0.4
- Added: Option to exclude any field data from both emails instead of only the confirmation email
- Added: When reCAPTCHA key is not filled out, a notice will popup on the front-end
- Added: Add-ons can now insert hidden fields inside an element, this was not possible before
- Fix: Color pickers on form builder page initialized when already initialized
- Fix: Hidden fields where skipped from email in some cases
- Fix: Icon positioning on some elements not always correctly aligned when selected Outside the field
- Fix: Textarea at form builder within the load/insert form should not be visible
- Fix: Diagonal button background hover color not correctly changing color after mouseleave
- Fix: For Add-on purposes, we check if the field label is set or not before replacing it by the Tag system, otherwise PHP might throw an error in case the Add-on has not set a field with the name label (same goes for field value)
- Fix: For Add-on purposes, if an Add-on element has been created and the Add-on is being deactivated make sure the element is skipped
- Fix: Made sure themes do not override border-radius for input fields

### December 12, 2015 - Version 1.0.3
- Added: Possibility to have multiple forms on one page with each their own fileupload element
- Fix: Not able to drop existing elements inside the multipart element on the builder page
- Fix: Setting Exclude from email for fileupload element not working
- Fix: If fileupload element is used, and large file uploads are taking place, the form will no longer be submitted to soon

### December 11, 2015 - Version 1.0.2
- Added: Action Hook (super_before_printing_redirect_js_action) to do something before displaying or redirecting after completed submitted form.
- Fix: On editing column previously generated fields are not correctly retrieved.
- Fix: For columns the conditional logic wasn't looping through multiple conditions only through the first condition.  

### December 10, 2015 - Version 1.0.1
- Fix: Dropable snap not allowed when not a column or multipart
- Fix: Conditional trigger, wasn't fired on dropdown change
- Fix: Some PHP errors removed during debug mode
- Fix: Some other smaller bug fixes

### December 9, 2015 – Version 1.0.0
- Initial release!
