# Super Forms - Changelog

## Listings Add-on now available! More info here:

- [Listings Add-on](https://renstillmann.github.io/super-forms/#/listings-add-on)

## PDF Generator Add-on now available! More info here:

- [PDF Generator Add-on](https://renstillmann.github.io/super-forms/#/pdf-generator-add-on)

## Jul 14, 2022 - Version 6.3.312

- **Added:** Missing country `Kosovo` for the `Countries (ISO2)` and `Countries (FULL)` elements

## Jul 13, 2022 - Version 6.3.311

- **Fix:** When using `Dynamic column` inside `Multi-parts` make sure when adding a new column it doesn't switch to the first multi-part

## Jul 12, 2022 - Version 6.3.310

- **Fix:** When storing client data make sure the generate ID does not exceed 64 characters in length, due to WordPress options table > `option_name varchar(64)` limit

## Jul 07, 2022 - Version 6.3.309

- **Fix:** `Calculator` element should not replace tags starting with `option_` with custom predefined values, instead if a user has a field named `option_radio` it should grab that value, and not try to grab the option value from the DB table

## Jul 04, 2022 - Version 6.3.308

- **Fix:** When using Dynamic Column the `%d` parameter wouldn't be replaced with the current column number correctly for the E-mail label/Entry Label setting

## Jun 26, 2022 - Version 6.3.307

- **Fix:** When using `Name Your Price` with `WooCommerce Checkout` in combination with  thousand seperator `.` (dot) and decimal `,` (comma) for prices (can be defined in the WooCommerce settings), make sure the price is formatted accordingly before parsing it to Name Your Price.

## Jun 22, 2022 - Version 6.3.306

- **Improved:** When using `Quantity` field with steps defined to `0.5` make sure the user can enter a single decimal point by hand. When using `0.05` user will be able to enter 2 decimal point numbers instead. When the step is defined to `1` user won't be able to enter any decimals

## Jun 21, 2022 - Version 6.3.305

- **Added:** Option to count words for `Textarea field` with use of tag `{fieldname;word}` to count words
- **Added:** Option to count words for `Textarea field` with use of tag `{fieldname;chars}` to count characters excluding, carriage return, line-feed (newline), tab, form-feed, vertical whitespace
- **Added:** Option to count words for `Textarea field` with use of tag `{fieldname;allchars}` to count all characters including, carriage return, line-feed (newline), tab, form-feed, vertical whitespace
- **Improved:** Do not store `server_http_referrer_session` and/or `tags_values` as client data when not needed, which could stress the database on high traffic websites
- **Improved:** Cookie/Session system, in case WP Cron is disabled, make sure we still clear expired client data from the database
- **Fix:** Bug with `Calculation` not summing up amounts correctly when inside dynamic column
- **Fix:** icon for `Calculator` element not being displayed even if defined
- **Fix:** `Keyword/tags field` should not filter case sensitive
- **Fix:** `WooCommerce Order Search` feature for `Text field` having issues with rendering the search results on front-end properly, plus additional CSS improvements accross theme style/icon positioning
- **Fix:** `Enfold` theme was causing some issues with `Keyword/Tags` feature due to CSS styles being used by the theme
- **Fix:** Bug/Issue with `Avada Builder` when typing in TinyMCE editor it would not update the live view properly
- **Fix:** `Listings` bug fix when leaving role settings empty (did not allow the user to view/edit/delete their own entries)

## Apr 26, 2022 - Version 6.3.0

- **Added:** MailPoet v3 settings under `Form Settings > MailPoet Settings` which allows you to subscribe users after they submit the form. Optionally you can mape custom fields to store additional information/date for the user
- **Added:** New option under `Form Settings > Form Settings` called `Disable Multi-part current step parameter in the URL` which prevents the step parameters `#step-58731-2` from being added to the URL
- **Added:** New option under `Form Settings > Popup Settings` called `Clear form after closing popup` which will reset/clear the form when the popup is being closed, this is especially useful when you are using the same form but populating it with different values when the popup is being opened
- **Added:** `WooCommerce Checkout` Option to conditionally checkout to WooCommerce 
- **Added:** Buttons/Icons to reset form settings to it's Default, Last known or Global value. Or to lock the setting to it's global value
- **Added:** New predefined tag `{option_****}` to retrieve any option from the wp_options database table. If the option is of type Array, you can also filter the sub values by defining a key like so `{option_****;arrayKey}`. When the arrayKey is omitted, a json representation of the Array will be returned.
- **Added:** Extra filter logic `Starts with (from left to right)` to filter from start to end instead of "Contains" method for both the `Auto suggest` and `Tags/Keyword` field field
- **Improved:** Cookie/Session system, which allows to filter the expiry and expiration variation values to increase or decrease the lifetime of client data
- **Improved:** `Register & Login` Option to define custom headers for emails such as `Verification`, `Approval` and `Reset Password` E-mails
- **Improved:** `Register & Login` Don't show activation status on profile page in back-end for current logged in user, no need
- **Improved:** `{dynamic_column_counter}` can now be used on field label and description, when used inside a dynamic column
- **Fix:** When using Elementor Popup use `elementor/popup/show` event provided by Elementor v2.7 to initialize the form in case it's not initialized
- **Fix:** When using a multi-language form with `PDF Generator` enabled and the `Add Language Switch` being enabled, the language switch dropdown should not be included in the PDF
- **Fix:** Issue with columns not aligned properly in Safari browser when RTL is enabled
- **Fix:** When the form is inside a Popup, make sure that scrolling events are applied to the content of the popup and not to the site page itself, for instance when scrolling to the first error, or other similar form scrolling events
- **Fix:** JavaScript error for Safari browser on iPhone when generating PDF, causing the PDF generation to get stuck/hang on loading/spinner icon
- **Fix:** Issue with `Print` button on Contact Entry page
- **Fix:** Issue with `Exclude dates or a range of dates` setting for datepickers when being used on multiple datepickers in the form
- **Fix:** An issue since v6.2.200 with dynamic columns where newly columns were inserted after the first column
- **Fix:** `{dynamic_column_counter}` not correctly counting when used in combination with a dynamic column that has conditional logic enabled
- **Fix:** JavaScript error in older Safari v12 browsers due to `replaceAll()` function not known by the browser, replaced with `replace()` with use of `RegExp()`
- **Fix:** Issue with `Register & Login` when changing user status from Pending to Active in backend
- **Fix:** Issue with `Register & Login` form, when having a login form that allows all user roles, filter array and remove any possible empty values, because this could cause any user to be unable to login
- **Fix:** When setting `Send email confirmation/verification email` to `Do nothing` make sure to set user status to 1, since they are not required to confirm their E-mail address, they will still not be able to login in case the `User login status` is set to anything other than `Active`

## Feb 22, 2022 - Version 6.2.0

- **Improved:** Disable the browsers's font boosting and text inflation algorithm by using the `max-height:XXXXXem;` hack, and setting `text-size-adjust:none;` (which is used by some smartphones and tablets, some browsers will still ignore this property)
- **Improved:** Do not load "Roboto" font, because no longer used
- **Improved:** `Tags/Keyword` element `Advanced > Max char/selection` blocks user from adding more tags if limit was reached instead of validating on form submission
- **Improved:** Code cleanup
- **Fix:** When renaming the `Button` name, spaces were being replaced with underscores
- **Fix:** Fix an issue with the `File upload` element when a maximum is set in combination with a user trying to add more files at once via the file explorer
- **Fix:** When deleting a `CSV file` from a `Dropdown` element it would scroll the user to the top of the page
- **Fix:** In the back-end when defining condtional logic for a field the user was not able to define a `Value` comparison that had the same value as the current field name e.g: `DC` field name and having a conditional logic value equals to `DC` which should be allowed. However the `Field name` isn't allowed to point to the current field, so that still needs to be validated 
- **Fix:** Redirect message undefined in JS, causing form not redirecting at all
- **Fix:** Toggle button alignment for `Field label` and `Field description`
- **Fix:** Issue with WooCommerce Checkout

## Feb 01, 2022 - Version 6.1.0

- **Added:** Predefined `Tags/Keyword` element
- **Added:** Predefined `Autosuggest` element
- **Added:** Option to disable CSRF check under `Super Forms > Settings > Form Settings > Cross-Site Request Forgery (CSRF) check`. This allows a user to submit the form that was loaded via an iframe from a different origin address
- **Improved:** Only load Cyrillic text font for PDF Generator if the option to do so is enabled and if Text rendering is enabled, there is no need to load these fonts otherwise
- **Improved:** Tags/Keyword field improvements
- **Fix:** If thousand seperator and decimal seperator was set to the same value, set the thousand seperator to an empty value, since it shouldn't be possible to have the same values, it would throw a JS error and prevent the form from loading
- **Fix:** Regenerate nonce for sites that use cache
- **Fix:** When `editing` is enabled for a `Listings` (Listings Add-on) make sure the styles/scripts are loaded so that normal form functions and styles are applied
- **Fix:** Validate requests made to switch form language by using a custom nonce system
- **Fix:** PHP parse error when loading a php file used for PHP versions below v8
- **Fix:** Option `Delete files from server after the form was submitted` wasn't working properly when storing files outside site root (secure file uploads)
- **Fix:** Back-end preview not generating the PDF due to fonts not being enqueued
- **Fix:** Issue with files not being attached to E-mails when upload directory was setup to be outside the site root (secure file uploads)
- **Fix:** PHP Warning related to `MailChimp`, `Mailster` and `Password Protect`
- **Fix:** PHP Warning when calling `file_get_contents()` to load PDF generator fonts
- **Changed:** File upload names are now fully visible when user added a file on the file upload element, they are no longer truncated. If you still want the truncated version you will have to apply custom CSS and and set overflow to ellipsis method
- **Changed:** By default the `secure` parameter for cookies is now set to true, you can still filter this with `super_session_cookie_secure` hook if needed
- **Changed:** By default the `httponly` parameter for cookies is now set to true, you can still filter this with `super_session_cookie_httponly` hook if needed

## Jan 20, 2022 - Version 6.0.0

- **Added:** `TinyMCE` element, allows you to add HTML by using the WordPress rich text editor.
- **Added:** `HTML (raw)` and `TinyMCE` elements are now treated as fields, meaning they can be retrieved and included inside emails and stored as data inside contact entries. Optionally you can exclude them from being retrieved in your emails and stored as entry data just like all other fields.
- **Added:** Ability to insert dynamic columns inside other dyanmic columns.
- **Added:** Ability to use `foreach()` loop inside other foreach loops inside `HTML (raw)` elements (this doesn't work directly inside email body settings, so if you need to use this you must use a `HTML (raw)` element instead), maybe in a future version it will be possible to use it directly in the emails too.
- **Added:** New filter hook `super_csv_bom_header_filter` to alter/delete the byte order mark added for Excel compatibility
- **Fix:** Some PHP version 8.0+ warnings
- **Fix:** Bug with generating invoice numbers on the `Hidden` field (depending on what settings are defined by the user)

## Dec 09, 2021 - Version 5.0.200

- **Added:** Option to filter based on form ID when exporting entries to CSV via `Super Forms > Settings > Export & Import` 
- **Added:** Option to sort by oldest or newest first when exporting entries to CSV via `Super Forms > Settings > Export & Import` and `Super Forms > Contact Enties`
- **Added:** Option to define custom delimiter and enclosure when exportin entries to CSV via `Super Forms > Contact Enties`
- **Added:** Option for datepicker to use {tags} inside the `Default value` setting
- **Added:** Option for datepicker to return the year, month, day or the timestamp by calling tags: `{date;year}`, `{date;month}`, `{date;day}`, `{date;timestamp}`
- **Added:** Ability to use `{dynamic_column_counter}` inside conditional logic and variable conditions, can be used to hide/display elements inside a specific dyanmically added column
- **Added:** Option for hidden field `Unique code generation` to define a `Unique invoice key` which allows you to generate multiple invoice numbers. This is useful for when you require both Invoice numbers and Quote numbers
- **Improved:** RTL layout for elements when site uses dir="rtl" on html tag
- **Fix:** International Phonenumber bug on mobile devices
- **Fix:** Issue with reCapthca validation when files are being uploaded
- **Fix:** When using SMTP on WP v5.5+ make sure to include the PHPMailer `Exceptions.php` file
- **Fix:** Bug with datepicker being cleared upon form submission
- **Fix:** `PDF Generator Add-on` Issue with Cyrillic/Arabic text rendering for PDF file, when copying text it would give you strange characters
- **Fix:** `PDF Generator Add-on` PDF Page break sometimes generating white/blank page
- **Fix:** When `Prevent submitting form on pressing "Enter" keyboard button` is enabled, make sure to still allow line breaks on `Textarea` elements
- **Fix:** Form settings translation bug
- **Fix:** HTML element automatic linebreak not working when using {tags} inside the HTML
- **Fix:** HTML element causing 404 when using {tag} to apply dynamic source tag on images
- **Fix:** Issue with HTML not being generated when no {tags} are used, due to above 404 bug fix 
- **Fix:** Slider label positioning on theme `minimal` and when `adaptive placeholders` are enabled
- **Fix:** Slider amount positioning incorrect when conditionally became visible and when value was the same as last known value
- **Fix:** JS error `Uncaught ReferenceError: nodes is not defined`

## Nov 04, 2021 - Version 5.0.111

- **Added:** International Phonenumber field
- **Added:** Option for WooCommerce Checkout Add-on to hide product gallery on single product page so that only the Super Form is visible
- **Improved:** Improved CSS for dropdown placeholder and radio/checkbox items for compatibility
- **Improved:** Replace `file_get_contents()` with `wp_safe_remote_get()` for improved security and compatibility accross hosting providers
- **Fix:** Issue with PDF Generator and Visual Compose/WP Bakery builder when form is inside a Accordion element
- **Fix:** Slider amount/label update positioning based on default value after becoming conditionally visible, or when inside tab/accordion
- **Fix:** JS error in back-end builder with renaming field names automatically to avoid duplicate field names
- **Fix:** JS error in firefox for focusin method and missing `e.target.closest` function
- **Fix:** JS error with file uploads
- **Fix:** Text field `keyword field` method not working with retrieve method `Specific posts (post_type)`
- **Fix:** Slider element default value positioning for amount label not correctly aligned
- **Fix:** JS error with Radio/Checkbox as Display Layout: Slider (Carousel)
- **Fix:** Bug with multiple file upload elements that would skip uploading files when last element was not containing any files
- **Fix:** Make sure rand() function only passes a max int value to avoid overflow on 32 bit systems
- **Fix:** Conditional logic not properly working upon page load
- **Fix:** When no files are selected for upload skip the request to try upload files (no need to do this)
- **Fix:** Bug fix for conditional logic with Calculator element

## Oct 15, 2021 - Version 5.0.020

- **Added:** `Listings Add-on` display entries on front-end, more info here (https://renstillmann.github.io/super-forms/#/listings-add-on)
- **Added:** New element `HTML Elements > PDF Page Break` for PDF Generator Add-on, which allows you to start a new page after a specific element. You can also switch between orientation `Portrait` and `Landscape` if needed.
- **Added:** New file upload system, file upload element will now display image/document in thumbnail preview before it's being uploaded to the server, they will also be visible in the generated PDF `PDF Generator Add-on`
- **Added:** New tags for file upload element, can be used inside HTML element on front-end and inside E-mail body
  - `{fieldname}` (retrieve list with file name(s))
  - `{fieldname;count}` (retrieve total amount of files connected to this file upload element)
  - `{fieldname;new_count}` (retrieve total amount of files that are yet to be uploaded)
  - `{fieldname;existing_count}` (retrieve total amount of files already/previously uploaded)
  - `{fieldname;url}` (retrieve file  "blob" or "URL")
  - `{fieldname;size}` (retrieve file size)
  - `{fieldname;type}` (retrieve file type)
  - `{fieldname;name}` (retrieve file name)
  - `{fieldname;ext}` (retrieve file extension)
  - `{fieldname;attachment_id}` (retrieve file ID after file has been uploaded when form is submitted)
  - `{fieldname;url[2]}` (retrieve specific file data, this example retrieves the third file URL if it exists based on array index)
  - `{fieldname;allFileNames}` (retrieve list with all file names, it's possible to filter this list with filter hook: `super_filter_all_file_names_filter`
  - `{fieldname;allFileUrls}` (retrieve list with all file URLs, it's possible to filter this list with filter hook: `super_filter_all_file_urls_filter`
  - `{fieldname;allFileLinks}` (retrieve list with a link to the file, it's possible to filter this list with filter hook: `super_filter_all_file_links_filter`
- **Added:** Compatibility for file upload with `foreach` loop inside HTML element and E-mail body example:
  ```php
  foreach(fileupload_field_name_here;loop):
      <strong>Name (<%counter%>):</strong> <%name%><br />
      <strong>URL (<%counter%>):</strong> <%url%><br />
      <strong>Extension (<%counter%>):</strong> <%ext%><br />
      <strong>Type (<%counter%>):</strong> <%type%><br />
      <strong>ID (<%counter%>):</strong> <%attachment_id%><br />
  endforeach;
  ```
- **Added:** New option under global settings `Super Forms > Settings > WooCommerce My Account Menu Items` to add custom menu items with custom content/shortcode or a custom URL to redirect to a custom page. This allows you to display any extra content for the `/my-account` page. For instance you could list contact entries with the use of the `Listings Add-on` on the `My Account` page. Since you can use shortcodes you could also use it for other usecases that are not even related to Super Forms.
- **Added:** Option to override form settings via shortcode attribute e.g: `[super_form id="54903" _setting_retrieve_last_entry_data="false"]` would override the option defined under `Form Settings > Form Settings > Retrieve form data from users last submission`. This allows you to have a single form to maintain while having seperate forms with slightly different settings/options defined. If you don't know the `key` of a settings just submit a ticket. But most settings can be found in the file `includes/class-settings.php`
- **Added:** Option to define colors for Dropdowns via `Form Settings > Theme & Colors`
- **Added:** Option to define colors for Tooltips via `Form Settings > Theme & Colors`
- **Added:** Option to define colors for Calculator element (Calculator Add-on) via `Form Settings > Theme & Colors`
- **Added:** `{user_last_entry_status_any_form}` tag to retrieve the latest contact entry status of the logged in user for any form
- **Added:** `{user_last_entry_id_any_form}` tag to retrieve the latest contact entry ID of the logged in user for any form
- **Added:** New filter hook `super_attachments_filter` to alter/add/delete the email attachments
- **Added:** `MailChimp Add-on` escape html in output message and replace psuedo after/before elements with normal DOM element
- **Added:** New tags to be used inside E-mails `{_generated_pdf_file_label}`, `{_generated_pdf_file_name}`, `{_generated_pdf_file_url}` allows you to retrieve the Generated PDF url so you can create a button that links to the file for download
- **Added:** New actions `Prev/Next Multipart/Step` for `Button` element to have more control over when to show the Previous / Next buttons in a multi-part element.
- **Added:** New filter hook `super_form_enctype_filter` to alter the form enctype attribute which defaults to `multipart/form-data`
  ```php
  add_filter( 'super_form_enctype_filter', 'f4d_change_enctype', 10, 2 );
  function f4d_change_enctype($enctype, $attr){
      return 'application/x-www-form-urlencoded':
  }
  ```

- **Improved:** Created a new Tabbing system (TAB/Shift TAB) to navigate through all elements properly and allow to select/deselect items such as radio/checkbox/dropdown items
- **Improved:** `Front-end posting Add-on` - create connection between created post and contact entry by storing the ID as meta data
- **Fix:** `PDF Generator Add-on` option to exclude generated PDF from contact entry not working
- **Fix:** When switching language through language switcher, preserver URL parameters so that the form is populated with data after switching language
- **Fix:** `Front-end Register & Login Add-on` when using language switcher, make sure the `code` parameters is preserverd (for account/email verifications)
- **Fix:** `Front-end Posting Add-on` issue with saving google map data for ACF map field
- **Fix:** `Signature Add-on` fix issue when clicking on canvas would create a vertical line instead of a small dot
- **Fix:** `WooCommerce Checkout Add-on` issue with `External/Affiliate product` URL being reset to the product permalink
- **Fix:** `WooCommerce Checkout Add-on` bug with shortcodes of other plugins not being able to list/retrieve products due to a bug in the new setting option `Super Forms > Settings > WooCommerce Checkout > Hide products from the shop`
- **Fix:** `Zapier Add-on` use numbered index instead of filenames as index for the array, otherwise you would not be able to map/retrieve the file within zapier interface
- **Fix:** Possible RCE (Remote Code Exectuion) vulnerability in old file upload system (doesn't affect most servers, but it's recommended to update to the latest version anyway)

## Mar 16, 2021 - Version 4.9.800
- **Added:** `WooCommerce Checkout Add-on` option via `Super Forms > Settings > WooCommerce Checkout` to exclude products the shop so that they can only be ordered via the form
- **Added:** `WooCommerce Checkout Add-on` option via `Super Forms > Settings > WooCommerce Checkout` to replace the default "Add to cart" section with a specific form
- **Added:** `Signature Add-on` Option to set signature line color
- **Added:** Options to define the `region` and `language` for the Googla Maps API. This will affect the `Google Map` element, `Address autocomplete` and `Distance calculation` features
- **Added:** Option to use `{tags}` for `Time picker` settings `The time that should appear first in the dropdown list (Minimum Time)` and `The time that should appear last in the dropdown list (Maximum Time)` which makes it possible to retrieve a "manipulated" timestamp which could for instance be set 6 hours in the future based on the current time. This can be used in combination with the `Calculator Add-on` demo form available here: [Dynamic time picker that is always 6 hours in the future](https://webrehab.zendesk.com/hc/en-gb/articles/360018108338)
- **Added:** New option for column element `Align inner elements` which allows you to center, left, right align directly inner elements
- **Added:** New `Retrieve method` called `Current Page or Post terms` for dropdown, radio, checkboxes to retrieve specific taxonomy terms based on the current page/post the form is on
- **Added:** Predefined tags `{post_term_names_****}`, `{post_term_slugs_****}`, `{post_term_ids_****}`. This way you can retrieve specific terms based on taxonomy. For instance to retrieve category names of a post you could use `{post_term_names_category}`
- **Added:** When `Prevent submitting form when entry title already exists` is enabled there is an extra option called `Also compare against trashed contact entries` which allows you to also check against possible trashed contact entries
- **Added:** `Calculator Add-on` option to use space for Decimal and Thousand seperator via `Advanced` tab
- **Improved:** Add missing escaped attributes
- **Fix:** PDF Generator Add-on fix for iPhone specifically, psuedo elements `:after`, `:before` not being generated 
- **Fix:** When using google address autocomplete field, the value was not being displayed on the entry page in the back-end `Super Forms > Contact Entry`
- **Fix:** `Signature Add-on` Changing signature line thickness not working
- **Fix:** Some hosts use a firewall rule that didn't allow to upload files due to it being uploaded inside a folder called `uploads` and `files` (uploads/php/files). This is now changed to (u/f) which solves a 403 error returned by the host
- **Fix:** JavaScript error when using Google Map in combination with PDF Generator Add-on
- **Fix:** Undo code that would speed up form loading time when using a lot of HTML elements with tags, however this code caused issues when using variable fields. Temporarily disabled / undo the code until we find a work-around or alternative
- **Fix:** A recent speed improvement in the code caused issues with variable fields that contains more than one {tag} as value. Only the first {tag} would be replaced with a value, skipping any other {tags}.
- **Fix:** Arbitrary File Upload to Remote Code Execution
- **Fix:** When saving contact entry with default title, make sure there is a space between the entry ID and the title

## Jan 28, 2021 - Version 4.9.700

- **Fix:** Typo `from` should be `form`, causing issues with PDF generator when using `Currency` field together with `Calculator` element
- **Improved:** Time picker element now uses WP core `current_time()` function when `Return current time as default value` is enabled to get time with the GMT offset in the WordPress option.
- **Improved:** Contact entry search will also trigger when "copy/pasting" text into the input field on mobile devices
- **Added:** Back-end translations:
  - Afrikaans
  - Arabic
  - Bengali (Bangladesh)
  - Czech
  - Danish
  - Dutch
  - French (Canada)
  - French (France)
  - German
  - Gujarati
  - Hindi
  - Hungarian
  - Indonesian
  - Italian
  - Japanese
  - Javanese
  - Kannada
  - Korean
  - Marathi
  - Norwegian (Nynorsk)
  - Persian
  - Polish
  - Portuguese (Portugal)
  - Punjabi
  - Russian
  - Spanish (Spain)
  - Swahili
  - Swedish
  - Tamil
  - Telugu
  - Thai
  - Turkish
  - Urdu
  - Vietnamese
  - 香港中文版
  - 繁體中文
  - 简体中文

## Jan 19, 2021 - Version 4.9.600

- **Added:** Option to prevent saving contact entry if a contact entry with the same title already exists, more info here:
  - [Prevent/disallow duplicate contact entry titles](https://webrehab.zendesk.com/hc/en-gb/articles/360017147758)
- **Added:** `MailChimp Add-on` option to unsubscribe users by setting `Send the Mailchimp confirmation email` to `No` and setting `Subscriber status after submitting the form` to `Unsubscribed`
- **Improved:** Form loading speed when using many HTML elements that contain many {tags}. Super Forms now remembers tag values and will not re-process these if they haven't changed since. This speeds up the loading speed significantly for forms that are using many HTML elements with many {tags}
- **Fix:** When `Enable form POST method` is enabled in combination with `Enable custom parameter string for POST method` do not store `Thank you message` into a session, otherwise it would be displayed twice when user navigates to a different page.
- **Fix:** When using multiple google address autocomplete elements in a form they would conflict with eachother.

## Jan 14, 2021 - Version 4.9.584

- **Added:** Option to add field {tags} inside the `Default value` setting, which would populate it on page load with the value from that field value. Previously you could only use predefined tags.
- **Added:** `Calculator Add-on` option to directly retrieve [predefined tags](https://webrehab.zendesk.com/hc/en-gb/articles/360016934317#h_01EVVEFFDD34J8V4FM6W4ZPC6N) inside math, e.g: to retrieve current year, month or price of current WooCommerce product etc.
- **Added:** Option to set separate error messages for validation error or empty field `Validation error message`, `Empty error message` more info here:
  - [Displaying a separate error message for validation and when a field is empty](https://webrehab.zendesk.com/hc/en-gb/articles/360017041918-Displaying-a-separate-error-message-for-validation-and-when-a-field-is-empty)
- **Fix:** Bug with `Date` element when setting `Allow user to choose a maximum of X dates` to anything higher than `1` causing it to switch to current month e.g when choosing 2 dates in month `Feb`, it would switch back to month `Jan`
- **Fix:** Issue with dragging elements in Accordion element
- **Fix:** Back-end settings CSS fix

## Jan 12, 2021 - Version 4.9.580

- **Added:** Option to map the so called `Formatted address (full address)` for Google address autocomplete
- **Added:** Option for [Contact entry search](contact-entry-search.md) to return contact entry status, ID and Title by adding fields named `hidden_contact_entry_status`, `hidden_contact_entry_id` or `hidden_contact_entry_title`
- **Added:** `WooCommerce Checkout Add-on` option to update Contact Entry status after WooCommerce order completed
- **Added:** Option for google address autocomplete to return `The place's name`, `Formatted phone number`, `International phone number` and `Website of the business`
- **Improved:** Google address autocomplete now also returns `City` if mapped as `postal_town` and or `sublocality_leve_1` see: [Maps JavaScript API documentation](https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete-addressform) for more info
- **Improved:** When translating form make sure to only display settings that the main language field uses
- **Improved:** When using google autocomplete the code will now determine what data is being used in your form, and strip out any unnecessary data from the API request which could considerably reduce costs
- **Improved:** Focus/Filled status for currency field
- **Fix:** JavaScript error on currency field when `Number format` contained space(s)
- **Fix:** Builder UI scrolling bug in Firefox browser causing a continues scroll
- **Fix:** When selecting default items for `Dropdown` element it would still display the placeholder instead of the actual selected item
- **Fix:** US States element contained some incorrectly sorted items by alphabet
- **Fix:** Issue with SMTP on older PHP versions due to WordPress moving the class to a different location
- **Fix:** PHP Notice

## Dec 22, 2020 - Version 4.9.570

- **Added:** Option for [Address auto complete (google places)](address-auto-complete.md) to specify the types of results to return e.g:
  - `geocode`: return only geocoding results, rather than business results. Generally, you use this request to disambiguate results where the location specified may be indeterminate.
  - `address`: return only geocoding results with a precise address. Generally, you use this request when you know the user will be looking for a fully specified address.
  - `establishment`: return only business results.
  - `(regions)`: return any result matching the following types: locality, sublocality, postal_code, country, administrative_area_level_1, administrative_area_level_2
  - `(cities)`: type collection instructs the Places service to return results that match locality or administrative_area_level_3
- **Added:** Option for [Address auto complete (google places)](address-auto-complete.md) to restrict results by countrie(s) e.g: fr,nl,de (to restrict results by France, Netherlands and Germany)
- **Improved:** `Keyword field` style improvements
- **Improved:** [Auto suggest](auto-suggest.md) style improvements
- **Improved:** Allow setting `The types of place results to return` to be empty for `Address auto complete` feature, so that all types can be returned when left blank
- **Improved:** Clean up generated PDF datauri, no need to store it in contact entry data in database, it could also cause the database to throw error due to reaching maximum Text/Blob size
- **Fix:** When filtering [Auto suggest](auto-suggest.md) make sure to preserve any spaces in the search results
- **Fix:** Firefox adaptive placeholders focus automatically getting unfocussed
- **Fix:** When using `Currency` field with a `Default value` make sure the masked is applied upon page load
- **Fix:** When using connected datepickers in combination with a custom `Localization` e.g `Czech` and a min/max connected date, the connection would not function due to difference in date formats
- **Fix:** Multi-part thinking there was still a field that required validation when in fact the field had become conditionally hidden while after the field had thrown a validation error. This caused the form being unable to submit.

## Dec 08, 2020 - Version 4.9.556

- **Fix:** When using double quotes in radio/checkbox/dropdown Labels (when using custom HTML for instance) make sure any backslashes are not saved
- **Fix:** Calculator Add-on conditional logic wasn't working due to new CSS rule `display:fex!important;`

## Dec 02, 2020 - Version 4.9.555

- **Added:** `Default value` setting for `Timepicker` element, this way you can set a default time upon page load
- **Improved:** PDF Generation will now be identical between mobile and desktop, no longer applies bigger font size, and or responsiveness
- **Improved:** `{tag}` regular expression, so that values in a HTML element like `{something like this / and this}` are not being detected as valid `{tags}`
- **Fix:** JS error when using signature element in combination with any top level conditional logic
- **Fix:** WordPress moved PHPMailer class into different location from v5.5 and above. Causing issues for those that had SMTP enabled in super forms.

## Nov 16, 2020 - Version 4.9.550

- **Added:** [Secrets](secrets.md) to safely store sensitive data on server side, but still be able to retrieve it conditionally and use it in the form settings
- **Fix:** When using Accordion or TAB element and using columns inside the column was not correctly closed in some scenario's
- **Fix:** When using a `Global secret` inside a Hidden fields default value, it would be converted to the underlaying value upon page load
- **Fix:** Problem with datepicker connected to a datepicker that allows a user to choose multiple dates automatically clearing the field value upon selecting dates
- **Fix:** Issue when adaptive placeholders are being used, but are empty, would cause it to be prefixed with numbers "1" or "2"
- **Fix:** JS error when not using adaptive placeholders

## Oct 22, 2020 - Version 4.9.530

- **New:** PDF Generator will now generate text to make PDF searchable
- **Improved:** Use the build in WordPress PHP Mailer library instead of included one, and removed the library from plugin source code
- **Fix:** Form not loading when using multiple forms on a single page
- **Fix:** Internet Explorer javascript error (added polyfill for promises)
- **Fix:** FireFox issue with adaptive placeholders causing to overlapping placeholder with text from the browsers remembered values (fields history)
- **Fix:** Some PHP Notices/Warnings
- **Fix:** {tags} for PDF filename not working
- **Fix:** When using a field to search previous contact entry a JavaScript error is thrown causing the form to not load.
- **Changed:** Default font family is now set to `"Helvetica", "Arial", sans-serif`

## Sep 22, 2020 - Version 4.9.520

- **Added:** When going to next multi-part super forms will remember the last step the user was on so when the user refreshes the page the last step will be opened.
- **Added:** Option to link to a specific Multi-part via URL anchor e.g: `domain.com/order-form#step-12345-3` where `step` is the identifier, `12345` the form ID and `3` the step (multi-part) that needs to be openend/shown upon page load.
- **Added:** Options for `Heading` element to use the WordPress theme styles by setting options to `none` or `-1`, for instance for Font weight, Line height etc.
- **Added:** Options to control font styles (size, line height, font weight) globally (all elements) and for field labels/descriptions specifically, plus with the option to define the font styles on mobile devices (first/second responsiveness based on window/screen width).
- **Fix:** Field placeholder font size not correctly set based on font settings.

## Sep 03, 2020 - Version 4.9.514

- **Fix:** JavaScript error when using conditional validation e.g: `Allow field to be empty > Yes, but not when the following conditions are met`

## Aug 27, 2020 - Version 4.9.513

- **Added:** Setting to turn of the new "Processing overlay/popup" and fall back to the legacy (old) thank you message `Form Settings > Form Settings > Display form processing overlay (popup)`
- **Added:** Prefix `super` to `close` classname for zero conflict policy
- **Improved:** When using the `Popup Add-on` and `Show thank you message` is disabled the popup should automatically close after form submission (this will prevent displaying an empty popup)
- **Improved:** Added timestamp to Contact Entries export file name (solves problem with cached files)

## Aug 13, 2020 - Version 4.9.512

- **WP Requirements Compliant: Maintains items to Envato's current standards for WordPress**
- **Improved:** Regex that looks for {tags} inside HTML element to exclude any tags that include double quote or single quote, because some third party plugin shortcodes might generate HTML with element attributes like `{"something":"here"}` which caused Super Forms to throw a JS error
- **Fix:** HTML element with foreach loop without {tags} inside Multi-part not being updated
- **Fix:** Make sure that third party plugins do not display notifications on the builder page that are not using the build in admin notice function within wordpress but a custom one
- **Fix:** Responsiveness when using multiple form on a single page

## Aug 04, 2020 - Version 4.9.508

- **Fix:** Multi-part autostep function passed incorrect parameter causing problems with checkboxes and other selectable elements

## Aug 03, 2020 - Version 4.9.507

- **Fix:** Google Maps not loading due to JavaScript error
- **Fix:** Several other JS errors fixed

## Jul 31, 2020 - Version 4.9.506

- **Fix:** JS error with Multi-part element when `Check for errors before going to next step` is enabled

## Jul 30, 2020 - Version 4.9.504

- **Improved:** Code refactoring
- **Improved:** Responsiveness for Radio/Checkboxes with Grid layout enabled
- **Fix:** When using radio/checkbox/dropdown with values like `0.2`, `0.20`, `0.200` and the option with `0.2` was set as the default value the other options should not be set as default value on the front-end. Fixed by enabling `strict` on `in_array()` function
- **Fix:** When using address autocomplete with mapped fields and adaptive placeholder the "filled" status should be activated for the field

## Jul 22, 2020 - Version 4.9.503

- **Fix:** Allow user to trial Add-on even when on a non-secure connection (http)

## Jul 21, 2020 - Version 4.9.502

- **Improved:** Envato Quality Indicator Application
  - Removed prefix from third-party assets handles
  - Renamed asset handles to match filename, and without extension
  - Missing translatable text strings
  - Escape translatable strings
  - Remove all unused code
  - Escape all translatable strings
  - Use `.on()` rather than `.click()`, `.bind()`, `.hover()`, `.submit()` etc.
- **Fix:** Clear form after submission throwing javascript error

## Jul 20, 2020 - Version 4.9.501

- **Fix:** Compatibility with PayPal Add-on (not redirecting to PayPal checkout page after form submission)

## Jul 17, 2020 - Version 4.9.500

- **NEW:** PDF Generator Add-on, read the docs for more info here: [PDF Generator Add-on](https://renstillmann.github.io/super-forms/#/pdf-generator-add-on)
- **Added:** Documentation for [Register & Login Add-on](https://renstillmann.github.io/super-forms/#/register-login)
- **Added:** Documentation for [Zapier Add-on](https://renstillmann.github.io/super-forms/#/zapier)
- **Added:** Documentation for [MailChimp Add-on](https://renstillmann.github.io/super-forms/#/mailchimp)
- **Added:** Documentation for [Mailster Add-on](https://renstillmann.github.io/super-forms/#/mailster)

## Jun 29, 2020 - Version 4.9.471

- **Improved:** Tooltips are now also triggered by both `click` and `hover` events
- **Improved:** Resonpsiveness for radio/checkbox items when using Large or Huge field size
- **Fix:** Multipart autostep not working when having hidden fields at the bottom of a step
- **Fix:** When using a predefined tag inside `Default value` setting in combination with a button with action set to `Clear/Reset form` it was replacing the field value to the raw {tag} instead of it's value
- **Fix:** Bug with dynamic columns and field name incremental

## Jun 11, 2020 - Version 4.9.466

- **Added:** New operator `??` (contains) and `!??` (does not contain) for E-mail/HTML if statements.
- **Added:** Option for datepicker to specify dates or date range to override the `Exclude days` setting. This way you can for instance exclude all Mondays, and Tuesdays, with the exception for some hollidays.
- **Improved:** Country strings are now translation ready (used on Country element)
- **Improved:** Google Map element responsiveness on mobile devices
- **Improved:** Google Map indicator in back-end to notify users that the Map will only be generated on the Front-end
- **Fix/Improved:** When using third party shortcodes inside HTML element and it does not contain any {tag} we shouldn't refresh the HTML content. This would cause losing any initialized DOM elements. This also should improve speed for forms that use a lot of HTML elements but didn't contain any {tags}
- **Fix:** PHP throwing Fatal error when using `Retrieve method` > `Post type`
- **Fix:** 3 demo forms were no longer installing properly due to invalid serialized array
- **Fix:** Issue with slider field inside dynamic column, not updating the amount label position relative to "dragger" when dynamic column becomes visible. Issue also applied to Accordion/Tab element.

## May 29, 2020 - Version 4.9.460

- **Added:** Option for Google Map element to set region code e.g `nl`, `de`, `uk`, `us` etc.uu
- **Added:** Option for Google Map element to set/adjust `zoom`
- **Added:** Option for Google Map element to enable/disable `UI (buttons)`
- **Added:** Option for Google Map element to `draw Route` from address A (origin) to address B (destination)
- **Added:** Option for Google Map element to optionally display the `directions panel` (list with route instructions)
- **Added:** Option for Google Map element to set it's travel mode `DRIVING`, `BICYCLKING`, `TRANSIT`, `WALKING`
- **Added:** Option for Google Map element to populate `distance` to field (including Calculator Add-on)
- **Added:** Option for Google Map element to populate `duration` to field (including Calculator Add-on)
- **Added:** Option for Google Map element to define the unit system `METRIC` or `IMPERIAL`
- **Added:** Option for Google Map element to draw Route with `Waypoints` (stops in between the route)
- **Added:** Option for Google Map element to optimize route with waypoints (to rearrange it in a more efficient order)
- **Added:** Option for Google Map element to avoid `Ferries`, `Major highways`, `Toll roads` (if possible)
- **Fix:** Incorrect incrementing field names in dynamic columns

## May 21, 2020 - Version 4.9.455

- **Fix:** MySQL error in prepare() statement when unique code is generated

## May 20, 2020 - Version 4.9.454

- **Fix:** Issue with browser `Back` button remembering the uniquely generated code (Hidden field with Unique code generation enabled), should instead generate a new one so that it is a unique code.

## May 15, 2020 - Version 4.9.453

- **Fix:** Limit for dynamic column was no longer working
- **Fix:** Fix for datepicker, user was able to click days in next and previous months while they shouldn't be able to do so

## May 13, 2020 - Version 4.9.450

- **New:** `Secure file uploads` setting under `Super Forms > Settings > File Upload Settings`
  - option to define a custom directory name relative to the site root e.g:
    `wp-content/uploads/superforms` _the default upload directory for file uploads_
    `my-custom-public-folder` _custom file upload directory outside wp-content directory (which is still publically accessible but will not store in Media Library)_
    `../my-custom-private-folder` _secure file uploads_
    `../../my-custom-private-folder` _secure file uploads when WP is installed in a subdirectory_
  - optionally choose to organize uploaded files in a month/year based structure e.g: `2020/05`
  - option to hide file uploads from the `Media Library` even if the file was uploaded to a directory inside wp-content directory
    - Note that if you are uploading files outside the root of your site then files will not be uploaded to the Media Library by default
  - only allow logged in users to download secure/private files
  - only allow specific roles to download secure/private files
- **New:** Option to remove hyperlinks (URLs) of file uploads in the email list
- **New:** Option to remove the uploaded files in the email list `{loop_fields}` (this will still send it as an attachment though)
- **New:** Option to hide file uploads from Media Library via `Super Forms > Settings > File Upload Settings`
- **New:** `PayPal Add-on`: option to send custom email after payment completed
- **New:** `PayPal Add-on` & `Register & Login Add-on`: option to update the registered user role after payment completed
- **New:** `Register & Login Add-on`: option to change user role for action `Update existing user`
- **New:** `WooCommerce Checkout Add-on` & `Register & Login Add-on`: option to update the registered user role after payment completed
- **New:** `WooCommerce Checkout Add-on`: option to populate checkout fields more easily with native setting, matching the field names e.g `billing_first_name` will still work and will be the `leading` value if it exists. Otherwise you can define values like so in the settings: `billing_first_name|{yourfieldnamehere}`. You can define both `billing_` and `shipping_` fields, both will work.
- **New:** Added `Custom regex` validation option for `Textarea` element
- **Added:** New filter hook `super_export_selected_entries_filter` to alter data format, e.g: if you wish to change the date format or seperate date and time and put them in a seperate column
- **Added:** New option under `Super Forms > Settings` called `Global Overriding` where you can now "override" specific settings for all forms. This gives you more power/control in case you need the same setting for all of your forms. These option will not actually replace or override the individual form setting in the database, but simply ignore them. This means you can simply revert back to whatever setting was previously used on the individual form. _In a future version we might add an option that allows including/excluding specific forms from being overridden based on their form ID_
- **Added:** 2 new raw code options under `Code` TAB on builder page: `Form settings` and `Translation settings` in an improved user friendly JSON format
- **Added:** Option to send an SMTP test email from within the settings that will show a log and any possible errors returned by the SMTP server
- **Added:** Option to enable RTL (Right to left) layout for E-mails
- **Fix:** Issue with regex backslash and with custom CSS backslashes
- **Fix:** Form/User Locker would stil display a message even when disabled
- **Fix:** Brand icons not working on `Button` element
- **Fix:** Issue inside `Translation mode` where the form would say that there are 2 duplicate field names (which was due to the field names being empty)
and Contact Entry
- **Fix:** When using field typ `number` on a `Text` field make sure the `Max/Min number` settings are correctly added as min/max attributes
- **Fix:** JavaScript error when using Accordion/TABs element
- **Added:** Two new options for datepicker element to allow users to select multiple dates independently from eachother
  - `Allow user to choose a maximum of X dates` _Defaults to 1, which allows a user to only pick 1 date)_
  - `Require user to choose a minimum of X dates` _(Defaults to 0, which allows a user to pick no date at all)_
- **Fix:** Conflict when using multiple datepickers and one of them had `Allow users to select work days` or `Allow users to select weekends` disabled causing dates for other datepickers to be affected
- **Fix:** `Print` action not working for Button element
- **Fix:** Custom regex validation would still be applied even though validation was set to `None`
- **Fix:** Problem with dropdown default selected items not overiding placeholder value
- **Fix:** Slider CSS conflicting with possible other site elements, added `super` prefix
- **Fix:** JavaScript error in back-end when updating timepicker element
- **Fix:** Timepicker not popping up due to incorrectly typeof check
- **Fix:** Max selection for `Keyword field` not working
- **Fix:** Predefined tags inside `Default value` not working with translated forms
- **Improved:** Super Forms now remembers the last TAB you where editing a field in. If this TAB exsists for the next field you edit it will open in this specific TAB. This way you can faster edit many of the same settings that are below the same settings TAB. Upon page reload it will still remember. The same goes for the form settings TABS and the last openend panel
- **Improved:** Better readability for font size on larger resolution monitors
- **Improved:** When `Delete files from server after form submissions` is enabled we should not add hyperlink on the filename inside the E-mail
- **Improved:** When using `Elementor` only enqueue all scripts/styles when in preview/editor mode
- **Improved:** Show where the error is when using TABs or Accordion elements (just like with Multi-parts)

## Mar 09, 2020 - Version 4.9.400

- **NEW:** `Adaptive Placeholders`, can be enabled under `Form Settings > Theme & Colors > Enable Adaptive Placeholders`
- **Added:** `Exclude dates or a range of dates` for `Datepicker` element to disallow users from selecting specific dates, examples:
  - `2020-03-25` (excludes a specific date)
  - `2020-06-12;2020-07-26` (excludes a date range)
  - `01` (excludes first day for all months)
  - `10` (excludes 10th day for all months)
  - `Jan` (excludes the month January)
  - `Mar` (excludes the month March)
  - `Dec` (excludes the month December)
- **Added:** Localization options for `Datepicker` element:
  `English / Western (default)`, `Afrikaans`, `Algerian Arabic`, `Arabic`, `Azerbaijani`, `Belarusian`, `Bulgarian`, `Bosnian`, `Català`, `Czech`, `Welsh/UK`, `Danish`, `German`, `Greek`, `English/Australia`, `English/UK`, `English/New Zealand`, `Esperanto`, `Español`, `Estonian`, `Karrikas-ek`, `Persian`, `Finnish`, `Faroese`, `Canadian-French`, `Swiss-French`, `French`, `Galician`, `Hebrew`, `Hindi`, `Croatian`, `Hungarian`, `Armenian`, `Indonesian`, `Icelandic`, `Italian`, `Japanese`, `Georgian`, `Kazakh`, `Khmer`, `Korean`, `Kyrgyz`, `Luxembourgish`, `Lithuanian`, `Latvian`, `Macedonian`, `Malayalam`, `Malaysian`, `Norwegian Bokmål`, `Dutch (Belgium)`, `Dutch`, `Norwegian Nynorsk`, `Norwegian`, `Polish`, `Brazilian`, `Portuguese`, `Romansh`, `Romanian`, `Russian`, `Slovak`, `Slovenian`, `Albanian`, `Serbian`, `Swedish`, `Tamil`, `Thai`, `Tajiki`, `Turkish`, `Ukrainian`, `Vietnamese`, `Chinese zh-CN`, `Chinese zh-HK`, `Chinese zh-TW`
- **Added:** Super Forms Widget for `Elementor` plugin. You are now no longer allowed to use a Text widget to render your forms. Instead you must either use the native `Super Forms Widget` or the build in `Shortcode Widget` of Elementor (if you don't an error will be shown)
- **Added:** `PayPal Add-on` - option to conditionally checkout to PayPal, this allows you to optionally let the user pay via PayPal
- **Added:** Option for Dropdown element to choose a `Filter logic` between `Contains` or `Starts with (from left to right)` so that when a user starts typing it either filters from the beginning of the string instead of doing a global search. This is useful for filtering countries, because you would want the user to jump to `Switzerland` when typing `Sw` and not to `Botswana`.
- **Fixed:** {tags} where no longer working on custom URL for `Button` element due to usage of `esc_url()`
- **Fixed:** `Form settings` > `Prevent submitting form on pressing "Enter" keyboard button` was no longer working
- **Fixed:** W3C validation errors
- **Fixed:** JavaScript error with dropdown filter
- **Fixed:** Issue with `Allow field to be empty` not taking effect in combination with for instance `Website` validation method
- **Fix:** Currency field with decimal (precision) set to 0 would return value in cents, 1 would become 0.01 instead
- **Fix:** JavaScript error in Elementor builder interface
- **Fix:** Hidden field could not have a default value of `0` due to the usage of the `empty()` PHP function
- **Fix:** `MailChimp Add-on` - when displaying Groups/Interests loop over all groups instead of just one
- **Fix:** Filter users by role(s) for retrieve method `Users (wp_users)` was not working
- **Fix:** JavaScript error due to new localization feature on datepicker
- **Fix:** Issue with multi-part Next button inheritting the action from the form Submit button causing possible action to become `Clear` or `Print` instead of going to next multi-part section
- **Fix:** Bug with datepicker settings not being applied due to name mismatch/typo e.g `Show week numbers` was always shown even when not enabled
- **Fix:** Remove padding for inactive TAB items for for TAB element with location set to: `Vertical tabs`
- **Fix:** `Popup Add-on` updated GSAP to v3.2.4 (solves conflict with Avada theme)
- **Fix:** Conflicting `Conditional Variable` with `Conditional Logic` causing the items to not properly being saved for the `Text` element
- **Improved:** Radio/Checkbox Label vertical alignment for long text/html
- **Improved:** Allow user to go to previous step in multi-part when `Check for errors before going to next step` is enabled.
- **Improved:** When reading CSV file make sure to remove BOM (Byte order mark)

## Jan 15, 2020 - Version 4.9.200

- **Improved:** UX, better naming convention in regards to validation settings
- **Improved:** Only bind events for TinyMCE editor on those that are inside a Super Form
- **Fixed:** ACF compatibility with none ACF meta keys like `_sku`
- **Fixed:** W3C validator error
- **Fixed:** Multi-part automatically go to next step and prevent going next step
- **Fixed:** Unable to upload file when WordPress is installed under a subdomain

## Jan 07, 2020 - Version 4.9.1

- **Added:** New `Code` TAB to change raw form code, can also be used to export/import forms quickly
- **Added:** Option to conditionally make fields "Readonly/Disabled" with use of columns and conditional logic
- **Added:** Sub option for `Validation` > `Required Field (not empty)` to conditionally make a field required based on other fields values `Allow field to be empty` > `Yes, but not if the following conditions are met`, otherwise field is allowed to be left empty, and can be skipped by the user
- **Added:** `!! Not contains` method for: Conditional logic, Conditional variables and Conditional validations
- **Added:** Option for autosuggest to sort items by menu order
- **Added:** Option for autosuggest to sort items by price for WooCommerce products
- **Added:** Option for autosuggest feature to search WooCommerce products by both title and SKU
- **Added:** Option to optionally display Prev/Next arrows for TABs element
- **Added:** Compatibility for {tags} usage on Accordion title and description
- **Fixed:** File upload elements always throwing alert in the builder that the form has `Duplicate field names`
- **Fixed:** Bug with TAB/Accordion element inside Dynamic Column in combination with "Save form progress"
- **Fixed:** Bug with advanced tag {field;label} not populated correctly for radio/checkbox/dropdown items upon page load
- **Fixed:** Bug with Google Address Autocomplete and Distance Calculation sometimes parsing wrong address
- **Fixed:** `Signature` wasn't being displayed due esc_attr() function, replaced it with esc_url() and add `data` as an excluded protocol
  - this was caused after Envato requirements update
- **Fixed:** [E-mail foreach loops](email-foreach-loops.md) on HTML element not working with Text field with keyword feature enabled
- **Fixed:** JS error related to HTML element that has conditional logic and put inside a dynamic column
- **Fixed:** Export/Import missing translation languages
- **Fixed:** Text field with `Enable keyword field` in dynamic columns causing javascript error
- **Fixed:** Masked input in combination with `Uppercase transformation` was not working
- **Improved:** Prevent from going to next/prev TAB via keyboard input `<`, `>` keys when a field is focussed
- **Improved:** Only allow number input for Quantity field
- **Improved:** JavaScript code optimization
- **Improved:** `Contact Entry Search` feature for `Text field` to better populate form with all the data
- **Improved:** `Signature Add-on` - when using `Contact Entry Search` the signature will be redrawn based on the entry data
- **Improved:** Print Contact Entries will now display Images with the following file types `jpeg, jpg, gif, png`

## Nov 06, 2019 - Version 4.8.10

- **Fixed:** JavaScript error when using masked input
- **Fixed:** JavaScript error for datepicker and timepicker elements inside dynamic column
- **Fixed:** When sending custom HTML emails and `Automatically add line breaks (enabled by default)` is disabled, make sure to still parse Textarea fields with proper line-breaks
- **Improved:** custom Ajax handler, causing to much trouble because some hosts don't know how to work with it, each host acts differently and each security plugin too. Now will always use the default WP Ajax handler.

## Oct 31, 2019 - Version 4.8.0

- **Improved:** Fall back to default WP ajax request when server returns an error. For instance, iThemes security might block requests when "Disable PHP in Plugins" is enabled.
- **Improved:** When exporting entries to CSV under `Super Forms > Contact Entries > Export to CSV` it will remember sorting and selection of fields on the client
- **Added:** [Mailchimp] Option to define "Tags" to be saved along with the subscriber (this is different from Interests, which can be selected by the user himself)
- **Added:** [Super Forms] accordion border radius and margins settings
- **Added:** [CSV Attachments] display an error message when unable to write file
- **Added:** `Slider` layout for Checkbox/Radio elements
- **Added:** 3 new dropdown fields `Country`, `Country ISO2`, `Country (FULL)` which will allow to retrieve ISO2, ISO3, Official name and Short name of a country
  - will no longer use the `contries.txt` to retrieve items, instead you can now use the `Custom items` method to change the list.
  - the old `Country` element still exists and is available for backwards compatibility, it is advised to start using the new `Country` element(s)
- **Added:** New setting `Do not create a new Contact Entry when an existing one was updated` when `Enable contact entry updating` is enabled
- **Added:** Ability to update the users last Contact Entry (without a "Search Contact Entry" field or $_GET $_POST key) simply by adding a `Hidden` field named `hidden_contact_entry_id` with {tag} `{user_last_entry_id}` as it's `Default value`
- **Added:** New tag `{last_entry_id}` to retrieve the latest `Contact Entry ID` that was created for the form
- **Added:** New tag `{user_last_entry_id}` to retrieve the latest `Contact Entry ID` that was created by the logged in user
- **Added:** New tag `{user_last_entry_status}` to retrieve the latest `Contact Entry status` that was created by the logged in user
- **Fixed:** Issue with Mailchimp Add-on conditional logic not working (only when placed in column with conditional logic)
- **Fixed:** Issue with dynamic column and Hidden field containing default values (wouldn't update properly)
- **Fixed:** Slider dragger incorrect position upon page load when intially conditionally hidden
- **Fixed:** Custom error message not working for file upload element
- **Fixed:** Column system issue in combination with dynamic column system and save form progression causing incorrect closing of columns
- **Fixed:** PHP Warnings and Notices
- **Fixed:** When POST method is enabled values containing string "0" where not populated because script thought is was an empty variable due to use of !empty() function.
- **Fixed:** On form load checkbox default value was not set based on "Default selected options" causing the validation to think the field was empty, while it was not.
- **Fixed:** JS error Cannot read property 'trim' of undefined
- **Fixed:** Not being able to submit the form for a second time when "Retrieve form data from users last submission" is enabled in combination with a File upload element.
- **Fixed:** WooCommerce Order Search (populate form with order data) was missing class "super-item" causing click event not being fired
- **Fixed:** Issue with Dynamic columns that contain columns with conditional logic in combination with "Save form progression" not correctly updating connected fields, causing conditions not being applied correctly.
- **Fixed:** Making sure that when a user is deleted with the option "Delete all content" enabled, the Contact Entries and Forms created by this user will also be deleted.

## Aug 18, 2019 - Version 4.7.63

- **Added:** `US States` dropdown element
- **Fixed:** Make sure the `Default value` for `Rating` element is of type `int`.
- **Fixed:** Bug with `Dynamic Columns` in combination with `Retrieve form data from users last submission` upon adding a new dynamic column the `Default value` would be incorrect.
- **Fixed:** Bug with `Signature Add-on` and `Color picker` not initializing upon dragging it on the canvas (due to Envato rules `¯\_(ツ)_/¯`)
- **Fixed:** Bug with checkbox/radio items not being updated upon "Update Element"
- **Removed:** Skype element, API doesn't exist anymore
- **Added:** [Data Storage](data-storage) section in documentation describing where specific data being stored by super forms
- **Added:** Option to load list into radio/checkbox/dropdown based on custom meta data field of the current post
- **Improved:** Rephrased "Current page, post or profile author meta data" to "Current author meta data"
- **Changed:** temporary disabling nonce check because it is causing a lot of problems with websites that are caching their pages.
- **Fixed:** Textarea not populating with Entry data
- **Fixed:** Allow email addresses to contain a "+" symbol
- **Fixed:** When no variable conditions where met, do not empty the field, but rather keep the value it currently has (this makes sure it won't conflict with `?contact_entry_id=XXXX` when form was populated with entry data or possibly other data that was set via a GET parameter)
- **Fixed:** issue with default radio button option and conditional logic on page load not affected
- **Fixed:** Missing arguments for `generate_random_code()`
- **Fixed:** Bug when both `Autosuggest` and `Keyword` is enabled for Text field

## Jun 26, 2019 - Version 4.7.40

- **Added:** Option to choose which Image Library to use to scale and orient images via `Super Forms > Settings > File Upload Settings`
- **Added:** Option to delete files from server after form submission via `Super Forms > Settings > File Upload Settings`
- **Added:** Option to delete associated files after deleting a Contact Entry via `Super Forms > Settings > File Upload Settings`
- **Fixed:** Due to Envato plugin requirements not allowing us to to prefix `$handle` with `super-` to enqueue scripts, it caused issues with plugins loading old versions of Font Awesome, resulting in none existing icons. This is unacceptable and we decided to change the $handle to `font-awesome-v5.9` so technically it doesn't have a prefix, and it makes sure that the latest version of Font Awesome will be loaded no matter what (when needed of course), even when a theme or plugin loads an older version.
- **Fixed:** `$_GET` parameters containing "Advanced tag values" not working on dropdown/checkbox/radio
- **Fixed:** Calculator Add-on JavaScript error `split()` is not a function on none string data
- Fix issue with email settings translation string escaping HTML resulting in raw HTML emails
- Fix $functions undefined (for none bundle super forms)

## Jun 15, 2019 - Version 4.7.0

- **Compliance:** Working towards Envato WordPress Requirements Badge/Compliance
  - Calculator Add-on: now using MathJS library for improved security when doing calculations
  - Passed all JavaScript files through `JShint` excluding third party libraries3
  - Escaping all Translatable strings
- **Added:** Missing Font Awesome 5 brand icons & updated Font Awesome to v5.9
- **Added:** Option to define a so called `specifier` to position the counter for `E-mail Labels` when using Dynamic Columns, example:
  - `Product %d quantity:` would be converted into `Product 3 quantity:`
  - `Product %d price:` would be converted into `Product 3 price:`
- **Added:** Compatibility for TinyMCE Visual editor to count words with Calculator Add-on
- **Added:** Option to specify field type for "Text" fields, allowing to determine what "Keyboard Layout" it should use on mobile devices. To name a few:
  - `email` (for email keyboard layout)
  - `tel` (for phone number keyboard layout)
  - `url` (for URL keyboard layout)
  - `number` (for number keyboard layout)
  - `date` (for keyboard layout to choose a specific date
  - `month` (for keyboard layout to choose a specific month)
- **Added:** A custom Ajax handler for faster Ajax requests (significant speed improvement for building/editing forms)
- **Added:** Translation feature (allows you to translate your form into multiple languages, this also includes translating specific form settings)
*when in translation mode, you won't be able to delete and change the layout of the form, just the strings of each element and the form settings*
- **Added:** Compatibility for HTML elements to handle {tags} with regexes `*` (contains), `$` (ends with) and `^` (starts with)
- **Improved:** Custom ajax handler compatible with older WP versions (tested up to v4.7)
- **Improved:** Mailchimp error debugging and other small improvements
- **Improved:** Speed improvement upon page load, now skipping calculator elements of which the value didn't yet change, so no need to loop through any elements connected to this field
- **Improved:** Currency field will now have field type set to `tel` for phonenumber keyboard layout to enter numbers easily on mobile devices
- **Fixed:** Text field with variable condition should not be reset/applied upon submitting form due to possible custom user input
- **Fixed:** CSV Attachment Add-on not applying correct delimiter from settings
- **Fixed:** issue with new ajax handler stripping slashes (it shouldn't be doing this) was resulting in issues with HTML element and line breaks
- **Fixed:** PHP notice about undefined variables
- **Fixed:** Issue with autosuggest keywords on mobile phone when autofill is applied by the browser, it would not validate the field correctly
- **Fixed:** Issue with new ajax handler not working in combination with active WC installation
- **Fixed:** Signature attachment not being a valid bitmap file when sending email over SMTP
- **Fixed:** Bug fix conditional logic when setting $_GET on radio buttons
- **Fixed:** Radio buttons not responsding to predefined `$_GET` or `$_POST` parameters
- **Fixed:** When doing custom POST and "Enable custom parameter string for POST method" is enabled file URL's where not parsed as data
- **Fixed:** Bug in Ajax handler, make sure to not load external unrequired plugins, because they might depend on functions that we didn't load
- **Fixed:** Compatibility for Ajax handler with Multisites
- **Fixed:** reCAPTCHA v2 bug
- **Fixed:** HTML element in back-end not wrapping words
- **Fixed:** Calculator element not working when using both regex and advanced tags like so: `{_option$;3}` or `{server_*;4}` or `{server_^;2}` etc.

## Apr 22, 2019 - Version 4.6.0

- **Improved:** Update plugin checker system
- **NEW:** E-mail Reminders Add-on
- **Added:** Option to retrieve timestamp with {tag;timestamp} for datepicker elements
- **Added:** Option for dropdowns and checkboxes etc. to filter based on post status for retrieve method `post_type`
- **Added:** reCAPTCHA v3 support
- **Added:** Option to hide Multi-part steps on mobile devices (useful to keep things clean when working with a lot of multi-parts)
- **Added:** Possibility to do if statements inside if statements and to use `&&` and `||` operators. Works for both HTML elements and email bodies. Example:

```php
  if({field}=='1' && {field2}!='2'):
    if({age}==16 || {age}==17):
      Show this text only when age is sixteen or seventeen, and only when field equals 1 and field2 not equals 2
    endif;
  endif;
```

- **Added:** New option `Include dynamic data (enable this when using dynamic columns)` for sending POST data, this can be used with for instance `WebMerge` to loop through dynamic columns when creating PDF's
- **Added:** Conditional logic field selected can now be entered manually, this allows you to use advanced tags to get a field value, but it also allows you to combine 2 field selectors together like so: {option;2}_{color;2} == [your conditional value] etc.
- **Added:** Option to do foreach() loops inside HTML elements to create a summary when using dynamic columns. Read here for more info [https://renstillmann.github.io/super-forms/#/email-foreach-loops](email-foreach-loops).
- **Added:** Option to do if() statements inside HTML elements. Read here for more info [https://renstillmann.github.io/super-forms/#/email-if-statements](email-if-statements)
- **Added:** Uploaded files will now be parsed onto `super_before_email_success_msg_action` action hook, allowing to transfer files to DropBox or Google Drive through Zapier Add-on
- **Added:** In the back-end when creating forms you will now be able to `Transfer` elements from form A to form B, or to reposition it easily within form A itself
- **Added:** Text fields can now also become a so called `Variable field` just like hidden fields, meaning you can populate them with data dynamically, while still allowing the user to edit this value
- **Added:** Option to parse parameter tags on to the shortcode to poupulate fields with data e.g: `[super_form id="1234" first_name="John" last_name="Willson"]`
- **Added:** Option for Text fields to search for WooCommerce Orders
- **Added:** Option to disable cookie storage for Varnish cache or other caching engines via `Super Forms > Settings > Form Settings` > `Allow storing cookies`
- **Changed:** file extion from .txt to .html for export and import files due to PHP recognizing .txt file as text/plain MIME type, which causes WordPress to fail to upload this .txt file resulting in a "Sorry, this file type is not permitted for security reasons". It is strongly discouraged to solve this problem by setting `ALLOW_UNFILTERED_UPLOADS` to true in wp-config.php.
- **Changed:** Updated Font Awesome to v5.7.2
- **Changed:** When leaving `Enter custom parameter string` option blank when doing custom POST, it will now submit all form data.
- **Improved:** A new way/method to verify the reCAPTCHA response, no longer checking via seperate Ajax call but instead upon form submission itself (this solves the error message hanging issue/bug)
- **Improved:** Make sure that .txt files can be uploaded due to new mimes type upload policy of wordpress not being able to upload txt files for security reasons
- **Improved:** replaced `eval()` function with `Function('"use strict";return ()')()`
- **Improved:** always parse the radix on parseInt() functions
- **Improved:** When defining conditional logic notify/alert user about possible loop creation when user is pointing conditional logic to it's own field (this would otherwise cause a stack overflow)
- **Improved:** `do_shortcode()` now called on the email body making it shortcode compatible
- **Improved:** Slider label positioning improved
- **Improved:** Only show admin notice once after updating plugin to check out `What's new` in the latest version. Also added option to completely disable to show update notices in the future from `Settings > Backend settings`
- **Improved:** Undo/Redo feature
- **Improved:** Form elements json now saved in localStorage, instead of a textarea element
- **Improved:** When using dynamic columns, a seperate data key called `_super_dynamic_data` will hold all the dynamic column data as an Array object (useful for usage with for instance `WebMerge`) to generate PDF files with product tables/rows
- **Fixed:** WooCommerce Checkout setting `Send email after order completed` was not compatible with [E-mail IF statements](email-if-statements)
- **Fixed:** Issue with File Upload element when using custom Image button, it would still display the placeholder text
- **Fixed:** Issue with WooCommerce Checkout not saving CC and BCC settings
- **Fixed:** bug in Calculator Add-on when using advanced tags in combination with wildcards e.g: `{field_*;2}` inside math
- **Fixed:** when excluding sundays "0" wasn't working, had to put "0,"
- **Fixed:** Star rating was not intialized inside dynamic column
- **Fixed:** reCaptcha trying to be rendered more than once
- **Fixed:** dynamic column foreach email loop bug when custom padding enabled on column
- **Fixed:** Multi-part autostep not working in some circumstances with conditional logic being used
- **Fixed:** Using star rating element inside conditional logic doesn't allow to go to next step automatically
- **Fixed:** Slider label initial value not correctly displayed based on decimal settings
- **Fixed:** Colorpicker inside multi-part should never focus upon clicking "Next" button when colorpicker is the first element
- **Fixed:** Multi-part skipping radio/checkboxes (would skip to for instance textarea below radio button and autofocus the textarea skipping the radio buttons)
- **Added:** option for dropdown retrieve method "post type" to filter based on categories and or tags (taxonomy filter)
- **Added:** new option for dropdowns to not only choose from Slug, ID or Title as value for dropdown items when using for instance custom post type, you can now also choose a "custom" method, and define custom meta data to return instead.
- **Improved:** When a dropdown has retrieve method post type 'product' and the product is a variable product it will list all it's variations
- **Fixed:** Bug with HTML element inside dynamic columns not correctly renaming tags that retrieve multi values e.g: changing `{fieldname;3}` to `{fieldname_2;3}` etc.
- **Fixed:** Path Traversal in File Upload via PHPSESSID Cookie and potentially Remote Code Execution
- **Fixed:** issue with conditional logic running based of page load via field values that where set through $_GET parameters
- **Added:** option to add post meta data as item attribute for dropdown elements (to do things from the front-end useful for developers)
- **Fixed:** Javascript error when Conditional Logic was set based on an element that was deleted at a later stage in time

## Jan 31, 2019 - Version 4.5.0

- **Added:** option to not exclude empty values from being saved for contact entries
- **Added:** option to automatically exclude empty fields from email loop
- **Added:** Polyfill for IE9+ support for JS `closest()` function
- **Added:** Compatibility with {tags} for Custom form post URL
- **Added:** option to filter entries based on date range
- **Added:** option to return rows from custom db table for dropdowns
- **Fixed:** color picker not initialized correctly inside dynamic columns
- **Fixed:** bug with conditional logic and dropdown when using `greater than` methods
- **Fixed:** Issue with dropdown searching
- **Fixed:** Call to undefined function wc_get_product()
- **Fixed:** Keyword autosuggest CSV retrieve method not correctly retrieving items
- **Fixed:** Keyword autosuggest Max/Min selections
- **Improved:** Keyword autosuggest search speed for larger amount of items

## Nov 13, 2018 - Version 4.4.0

- **Added:** Option to disallow users to filter items on dropdowns, which will also prevent keyboard from popping up on mobile devices
- **Added:** tag to retrieve product regular price `{product_regular_price}`
- **Added:** tag to retrieve product sale price `{product_sale_price}`
- **Added:** tag to retrieve product price `{product_price}` (returns sale price if any otherwise regular price)
- **Added:** option to retrieve product attributes for dropdown,radio,checkboxes
- **Added:** tag `{product_attributes_****}` to retrieve product attributes
- **Added:** option to send POST as JSON string
- **Added:** Russian languages files
- **Added:** tag to retrieve Form Settings with {form_setting_*****} e.g: {form_setting_email_body} or {form_setting_header_subject}
- **Added:** Option to set the maximum upload size for all files combined for a file upload element
- **Added:** Documentation about [Save Form Progression](save-form-progression.md)
- **Added:** Documentation about [Retrieve form data from users last submission](retrieve-data-last-submission.md)
- **Added:** Documentation about [Prevent submitting form on pressing "Enter" keyboard button](prevent-submit-on-enter-button.md)
- **Added:** Documentation about [Hide form after submitting](hide-form-after-submitting.md)
- **Added:** Documentation about [Form redirect](form-redirect.md)
- **Added:** Documentation about [Custom form POST URL](custom-form-post-url.md)
- **Added:** Documentation about [Contact Entries](contact-entries.md)
- **Added:** Documentation about [Clear/reset form after submitting](clear-reset-form-after-submitting.md)
- **Added:** Documentation about [Autopopulate fields](autopopulate-fields.md)
- **Improved:** autosuggest filter speed when dealing with 1000+ records
- **Improved:** Slider element, amount positioining sometimes a little bit off
- **Improved:** Decode email header function
- **Fixed:** Multi-item element not remembering default selected options correctly
- **Fixed:** IE bug fixes
- **Fixed:** E-mails where being stripped from + characters, which is a valid email address
- **Fixed:** Navigate through global settings and remove slashes from the values, to fix escaped quote issues in emails

## Jul 29, 2018 - Version 4.3.0

- **Added:** new filter hook - `super_redirect_url_filter`  (filter hook to change the redirect URL after form submission)
- **Added:** Option to disable scrolling for multi-part next prev buttons
- **Added:** Option to prevent scrolling effect for multi-part when an error was found
- **Added:** Variable fields in combination with {tags} will now also be able to have dynamic values within dynamic columns (add more +)
- **Added:** New filter hook `super_' . $tag . '_' . $atts['name'] . '_items_filter` (to filter items of dropdowns/checkboxes/radio)
- **Fixed:** Bug with checkboxes/radio precheck not working
- **Fixed:** use wp_slash() to make sure any backslashes used in custom regex is escaped properly
- **Fixed:** Error message on file upload element not disappearing after trying to upload to large file size or not allowed file extension
- **Fixed:** Issue with dynamic columns in combination with calculator element (not updating calculation correctly after adding column)

## Jun 18, 2018 - Version 4.2.0

- **Added:** Option to set a threshold for `keyup` event on currency field to only execute hook when user stopped typing (useful for large forms with above average calculations etc.)
- **Added:** Option to automatically replace line breaks for `<br />` tags on HTML element content
- **Added:** Option to add custom javascript under `Super Forms > Settings > Custom JS`
- **Added:** Option to create variable conditional logic with a CSV file, see `[Variable Fields]` documentation for more information
- **Added:** new filter hook - `super_conditional_items_*****_filter`  (filter hook to change conditional items on the fly for specific element)
- **Added:** new filter hook - `super_variable_conditions_*****_filter`  (filter hook to change variable conditions on the fly for specific field)
- **Improved:** Bind `keyup` for Quantity field to trigger field change hook
- **Fixed:** Google ReCAPTCHA not always being rendered on page load
- **Fixed:** Quantity field not populating with last entry data
- **Fixed:** Currency field blur/focus bug
- **Fixed:** Website URL validation only allowed lowercase letters
- **Fixed:** Google ReCAPTCHA no longer allows to use callback function that contains a . (dot) in the function name. Replaced `SUPER.reCaptcha` with `SUPERreCaptcha`
- **Fixed:** Multi-part not autmoatically switching to next step (if enabled) when hidden field is located inside the mulit-part
- **Fixed:** Bug with {tags} in combination with calculator element, would retrieve the HTML value version for calculations
- **Fixed:** Make forms and entries none plublic so that search engines won't be able to index them
- **Fixed:** Javascript Syntax Error in Safari

## Apr 13, 2018 - Version 4.1.0

- **Added:** Option to do if statements in success message
- **Added:** `{author_meta_****}` tag to retrieve current post author or profile user custom meta data
- **Improved:** hide text "Allow saving form with duplicate field names (for developers only)" in back-end when action bar is sticky
- **Improved:** Conditional Validation option can now also work in combination with float numbers
- **Improved:** File upload button name line height and checkbox/radio :after top position RTL forms
- **Improved:** Currency field now compatible with conditional validations
- **Fixed:** bug with variable field in combination with conditionally hidden
- **Fixed:** Conflict with jquery scope for hint.js causing a javascript error
- **Fixed:** Columns responsiveness was broken because of some future development code
- **Fixed:** Bug with front-end forms not loading correct settings/styles from global settings (not merging correctly)
- **Fixed:** Bug fix with automatic line breaks for HTML element

## Mar 16, 2018 - Version 4.0.0

- **Added:** Introduction tutorial (to explain back-end)
- **Added:** de_DE_formal translation file
- **Added:** `{user_meta_****}` tag to retrieve current logged in user custom meta data
- **Added:** `{post_meta_****}` tag to retrieve current post custom meta data
- **Added:** Option to retrieve current author meta data for dropdown element with
- **Added:** `{author_id}` and `{author_name}` tags which do the same thing as the `{post_author_id}` and `{post_author_name}` tags
- **Added:** minimize/maximize toggle button on builder page
- **Added:** option to even save form when it contains duplicate field names (for developers)
- **Added:** (GDPR compliance) Option to only save contact entry when a specific condition is met (Form Settings > Form Settings)
- **Improved:** author tags will now also retrieve the author ID and author name when located on profile page of an author
- **Improved:** Export/import system for single forms via Form Settings > Export & Import
- **Improved:** Global settings and form settings are now merged for better sync and more controllable way when having to deal with many forms
- **Improved:** Use `CSS Flexbox Layout Module` to solve Safari 0px height issue/bug for conditional hidden items
- **Updated:** de_DE translation file
- **Fixed:** removed 'wpembed' from tinymce plugin list (was dropped since wordpress 4.8)
- **Fixed:** Issue with Register & Login Add-on when saving custom user meta data
- **Fixed:** Issue with Print action for Button element when no HTML file was choosen

## Feb 28, 2018 - Version 3.9.0

- **Added:** Tag to retrieve selected option label in emails with `{fieldname;label}`
- **Added:** Option to replace comma's with HTML in emails for checkbox/radio/dropdown elements under Advanced TAB
- **Added:** Cool new feature to do if foreach loops inside email body content with {tag} compatibility e.g:
  - This method is intended to be used in combination with dynamic columns
  - **Example:** `foreach(first_name): Person #<%counter%>: <%first_name%> <%last_name%><br /> endforeach;`
- **Added:** Cool new feature to do if `isset` and `!isset` checks inside email body content with {tag} compatibility e.g:
  - This method should be used whenever you conditionally hide fields and they are no longer set and {tags} inside email would then not be converted because no such field was found
  - **Example 1:** `isset(first_name): The field exists! endif;`
  - **Example 2:** `!isset(first_name): This field does not exists! endif;`
  - **Example 3:** `isset(first_name): This field exists! elseif: This field does not exists! endif;`
- **Added:** Option for submit button to print or save PDF based on custom HTML that supports {tags} to dynamically retrieve form data
- **Added:** Print button can support signatures when used like `<embed type="image/png" src="{signature}"></embed>`
- **Added:** tag `{dynamic_column_counter}` to retrieve current dynamic column number added by user (this tag can currently only be used inside HTML element)
- **Added:** `stripslashes` for heading title / desciption
- **Added:** `htmlentities` Flags `ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED`
- **Improved:** Don't save settings that are the same as global settings
- **Fixed:** Form settings that did not have a filter value where not correctly updates when changing and saving form.
- **Fixed:** &quot was being replaced with " when updating/saving elements
- **Fixed:** `{tag;label}` not removed from HTML element when field is conditionally hidden

## Jan 29, 2018 - Version 3.8.0

- **Added:** Compatibility for variable fields with advanced tags e.g: `{field;2}`
- **Added:** Option "User Form locker / submission limit", this option allows you to only allow a logged in user to submit a specific form once only
- **Added:** Option to Toggle all fields to be exported to CSV on Contact Entry page in back-end
- **Added:** "Submitted by:" on Contact Entries page when a form was submitted by a logged in user
- **Added:** Option to retrieve entry data based on `$_GET['contact_entry_id']` or `$_POST['contact_entry_id']` (this will override the logged in user last submission data if it is set)
- **Improved:** When registering new user with Register & Login Add-on and entry is created the author will be the newly created user
- **Improved:** Builder speed
- **Improved:** Compressed the form json code by roughly 50% up to 80%
- **Improved:** Compressed the form settings json code by roughly 50% up to 80%
- **Improved:** Redo / Undo system, resulting in a smoother user experience when building forms on low end devices
- **Fixed:** Undefined index: admin_attachments
- **Fixed:** Form backup history restore sometimes returns blank forms (json error)
- **Fixed:** Button link open new tab not working
- **Fixed:** Google analytics conversion tracking not working when Custom form POST method is enabled
- **Fixed:** Only save tracking settings on global level and not on form level
- **Fixed:** HTML entities in json form code should not be decoded, e.g: &quot should be &quot and not converted to "
- **Fixed:** Honeypot captcha is filled out by Google Chrome saved username/passwords
- **Fixed:** Distance calculations variable overridden with destination address
- **Fixed:** Icons inside field with Medium size field
- **Fixed:** CSV + Checkbox issue

## Dec 22, 2017 - Version 3.7.0

- **Added:** Tags field (text field can be converted into a tag/keyword field) via "Enable keyword field" TAB when editing the text field
- **Added:** Deutsch/German translation (if you have translation files let us know so we can add them to the core files)
- **Added:** Option to retrieve tags for autosuggest fields
- **Added:** Option to change unique field name on the fly on builder page
- **Improved:** When Ajax is enabled, and google map API is not filled out, do not load the js library
- **Improved:** Automatically rename duplicated fields for more user-friendly work flow
- **Improved:** Back-end field filter code execution/speed improvement
- **Improved:** Google Map element can now udpate map address dynamically based on {tag}
- **Fixed:** Bug with conditional logic not scanning on form level but on active multi-part level in rare occasions.
- **Fixed:** Toggle field start value "On" not affected

## Dec 08, 2017 - Version 3.6.0

- **Added:** Option to add google analytics tracking events via: Super Forms > Settings > Form Settings
- **Added:** Option to center form via: Form Settings > Theme & Colors
- **Added:** Cool new feature to do if statements inside email body content with {tag} compatibility e.g:
  - (possible constructors are: ==, !=, >, <, >=, <=)
  - **Example 1:** `if({field}==123): Extra information here... endif;`
  - **Example 2:** `if({age}<18): You are underaged! elseif: You are an adult! endif;`
- **Added:** Extra conditional validation methods with option to compare 2 values instead of just 1 e.g:
`> && < Greater than AND Less than`
`> || < Greater than OR Less than`
`>= && < Greater than or equal to AND Less than`
`>= || < Greater than or equal to OR Less than`
`> && <= Greater than AND Less than or equal to`
`> || <= Greater than OR Less than or equal to`
`>= && <= Greater than or equal to AND Less than or equal to`
`>= || <= Greater than or equal to OR Less than or equal to`
- **Added:** Ability to retrieve checkbox/radio/dropdown Label with tag `{field;label}` (currently works for variable fields, conditional logics only in combination with checkbox/radio/dropdowns)
- **Added:** Option for datepicker field to exclude specific days from the calendar so users won't be able to select them when choosing a date
- **Added:** Option to disable autofocus for first element inside multi-part when multi-part becomes active
- **Added:** {tags} to retrieve cart information when WooCommerce is installed and activated: `{wc_cart_total}`, `{wc_cart_total_float}`, `{wc_cart_items}`, `{wc_cart_items_price}`
- **Added:** Option to disable autocompletion for specific fields via "Advanced > Disable autocompletion"
- **Added:** Option to do custom POST request with custom parameters instead of sending all available data (because some API's only allow you to POST parameters they request)
- **Improved:** RTL styles
- **Improved:** Changed submit button from `<a>` tag to `<div>` tag to avoid conflict with themes that care less about no-conflict policy
- **Fixed:** issue with validating fields in combination with conditional logic and multi-parts that have "Check for errors before going to next step" enabled
- **Fixed:** Issue with dropdowns inside dynamic column (clear field function was not updating field value correctly) result in conditions and calculations not properly updating
- **Fixed:** Issue with conditional logic AND method
- **Fixed:** bug with TAB index for fields
- **Fixed:** Some PHP warnings

## Nov 23, 2017 - Version 3.5.0

- **Added:** Compatibility with {tags} for conditional logic values and AND values
- **Added:** Google Map element (with polylines options for drawing point A to B dynamically, for instance for calc. distance with google address autocomplete)
- **Added:** Option for Google Address Autocomplete to populate street name and number and visa versa at once (combined)
- **Added:** Backwards compatibility with older form codes that have image field and other HTML field in group form_elements instead of html_elements
- **Added:** Shortcode compatibility for default field value
- **Added:** Google distance calculation setting for dropdown element (allows to let user choose specific locations and calculate distance based on that)
- **Improved:** Split up Form and HTML elements under their own TAB
- **Improved:** When google API query limit is reached for distance calculations show message to the user
- **Improved:** Skip AND method if not used for variable fields
- **Fixed:** Bug fixed after improving skipping AND method if not used for variable fields
- **Fixed:** Remove datepicker intialize class after column is dynamically duplicated
- **Fixed:** When using custom submit button with custom URL redirect enable the option to set custom Button name was hidden
- **Fixed:** Buttons dropdown setting for custom contact entry statuses
- **Changed:** Auto updates for Envato element users
- **Removed:** product activation TAB

## Nov 9, 2017 - Version 3.4.0

- **Added:** Option to reset submission counter
- **Added:** New tag `{submission_count}` Retrieves the total submission count (if form locker is used)
- **Added:** New tag `{last_entry_status}` Retrieves the latest Contact Entry status
- **Added:** contact entry statuses
- **Added:** lock form after specific amount of submissions
- **Added:** option to reset form lock daily/weekly/monthly/yearly
- **Added:** Option to send default attachment(s) for all email for both admin and confirmation emails
- **Improved:** Allow decimal values for quantity field
- **Fixed:** Issue with conditional logic function when using multi-parts and when "Check for errors before going to next step" is enabled on the multi-part
- **Fixed:** Check if HTTP_REFERRER is defined, otherwise php will throw error
- **Fixed:** `{tag;1}`, `{tag;2}` etc. where only accepting int types and not var types
- **Fixed:** If Ante/Post meridiem 12 hour format make sure to convert it to 24 hour format in order to return correct timestamp to do calculations with Calculator element
- **Fixed:** In rare cases custom regex e.g: \d would result in invalid json string seeing blank form in back-end
- **Fixed:** Contact entry not updating if Contact Entry saving itself is disabled but Updating Contact Entries is enabled
- **Updated:** Fontawesome to v4.7

## Oct 16, 2017 - Version 3.3.0

- **Changed:** made plugin ready for Envato Elements
- **Added:** Tag `{server_http_referrer}` to retrieve the previous location where the user navigate from before landing on the page with the form
- **Added:** Tag `{server_http_referrer_session}` saves HTTP_REFERRER into session so it will not be subject to change after navigating away and returning back at later time
- **Added:** Tags to retrieve current date values: `{server_timestamp}`, `{server_day}`, `{server_month}`, `{server_year}`, `{server_hour}`, `{server_minute}`, `{server_seconds}`
- **Added:** Ability to update Contact Entry title
- **Added:** Option to exclude fields from being save in Contact Entries
- **Added:** Option to duplicate Contact Entries
- **Added:** Compatibility with Visual Composer (JS Composer), Added a "Super Form" element
- **Added:** Option to disable form submission on pressing "Enter" key on keyboard (via Form Settings > Form Settings)
- **Added:** Option to show/hide Multi-part progress bar via Form Settings > Theme & Colors
- **Added:** Option to show/hide Multi-part steps via Form Settings > Theme & Colors
- **Added:** JS action hook: SUPER.after_appending_duplicated_column_hook()
- **Improved:** Make sure to skip the multi-part if no visible elements are found (in case multi-part is blank because conditional logic hides every element)
- **Fixed:** Issue with double quotes in json elements, sometimes backups are giving back a invalid json format resulting in a blank form
- **Fixed:** Do not skip multi-part when only HTML element or other similar element is found
- **Fixed:** $skip undefined
- **Fixed:** Dynamic columns not correctly populated with entry data if GET was set with value of search field
- **Fixed:** bug with max/min selection on checkbox element (displaying error message while not should display error message)
- **Fixed:** Fix RTL columns, may not reverse when responsiveness is disabled on mobile device width.
- **Fixed:** Bug fix, when form progress save enabled and using datepickers, causes ajax loop (since v3.2.0)
- **Fixed:** Sessions not being cleaned from database, and do not save empty sessions (empty array or session with status false)
- **Fixed:** Issue with AddStringAttachment function PHP7+ (decode after string has been parsed)

## Sep 27, 2017 - Version 3.2.0

- **Added:** Option to save form progress (when user leaves the page, and returns, form will not have lost it's progression)
- **Added:** Option for variable field (hidden field) to return varchar or integer based on specified {tag} e.g: `{field;2;int}` to sum values and return as integer, or `{field;2;var}` to return as varchar/text (var is used by default)
- **Added:** Option to skip specific fields from being populated with entry data after a successfull contact entry search
- **Added:** Honeypot captcha to avoid spam by default (of course you can still use Google reCAPTCHA for a better anti-spam if required)
- **Added:** Option to add custom TAB index (order) for fields
- **Added:** new filter hook for javascrip translation string and other manipulation such as tab index class exclusion - super_common_i18n_filter
- **Added:** new filter hook for javascrip translation string and other manipulation such as tab index class exclusion - super_elements_i18n_filter
- **Added:** Tag to retrieve user roles from logged in user `{user_roles}`
- **Changed:** Allow uppercase for unique field names in backend (previously uppercase characters where converted to lowercase automatically)
- **Removed:** use of session_start() for performance improvements, replaced with custom Session manager
- **Improved:** TAB index, and mobile TAB index for RTL
- **Improved:** google places address complete
- **Improved:** distance calculation between addresses
- **Fixed:** Add address latitude and longitude for ACF google map compatibility
- **Fixed:** issue with multi-part TAB index
- **Fixed:** CSS bug with gradient background in style-default.php
- **Fixed:** RTL support for columns incorrect order on mobile devices
- **Fixed:** Issue with google address autocomplete inside dynamic columns (add more +)

## Sep 01, 2017 - Version 3.1.0

- **Added:** Option to set custom colors for radio/checkbox
- **Added:** Dutch (NL) translation
- **Added:** Backup history and restore previous form backup/autosave
- **Added:** Redo/undo buttons on form builder page
- **Added:** Option to save multiple values for dropdown/radio/checkbox and retrieve with tags like: value1;value2  retrieve value1 with `{field;1}` and value2 with `{field;2}` etc.
- **Added:** Distance/Duration calculator between 2 addresses / zipcodes (google directions api)
- **Added:** Option to change the first day of the week on date picker element
- **Added:** new filter hook - super_form_before_first_form_element_filter
- **Added:** new filter hook - super_form_after_last_form_element_filter
- **Added:** Option for text fields to automatically transform user input to uppercase text
- **Added:** Option to disable automatic line breaks in emails (useful for pure HTML emails)
- **Added:** Option to add the IP address to the columns on the Contact Entries listing
- **Added:** When $_GET or $_POST contains the key of a field that is used to search/autopopulate the form based on a contact entry title, automatically update the form on page load if a entry was found based on the parameter value
- **Improved:** datepicker improvements
- **Improved:** Documentation extended with some chapters
- **Fixed:** iOS devices upload file extension uppercase/lowercase issue
- **Fixed:** Only execute conditional logic for "and field" if the "and method" is set
- **Fixed:** Theme Salient manipulates form HTML causing file upload button not triggering anymore, applied a hotfix for it, but theme author should solve this in future, bad selectors are being used!
- **Fixed:** Make sure any type of On value for toggle button is compatible with the setting "Retrieve form data from users last submission"
- **Fixed:** Field that has tags system enabled not working with setting "Retrieve form data from users last submission"
- **Fixed:** multipart validation not scrolling to first field with error when "Check for errors before going to next step" is enabled
- **Fixed:** write file header for correct encoding when exporting contact entries
- **Fixed:** Toggle field and dropdown reset
- **Fixed:** Time calculation bug with timepickers with the format Ante/Post meridiem
- **Fixed:** Success message not being displayed if custom POST method is enabled
- **Fixed:** Dropdown issue placeholder not correctly set after populating form data based on search field
- **Fixed:** Fileupload + icon little offset top little bit out of sync with field itself
- **Fixed:** Fileupload issue when mendatory and conditionally hidden
- **Fixed:** PHP notice "A non well formed numeric value encountered" for PHP 7.1+ (Hexadecimal strings are no longer considered numeric)
- **Fixed:** Bug with variable field (hidden field) when using "?? contain" condition on checkbox/radio/dropdowns
- **Fixed:** Bug in IE11 with checkbox/radio that have images
- **Fixed:** issue with quotes on variable fields values
- **Fixed:** Issue with multi-parts and tags not updating correctly
- **Fixed:** Mailster bug issue with parsing an empty header via array() Mailster doesn't like this when they hook into wp_mail()
- **Fixed:** When disabling the Thank you message via checkbox the session should not write the thank you information
- **Fixed:** Datepicker table padding
- **Fixed:** Because JS Composer developers using global CSS selector and didn't wanted to change it we decided to take matter in our own hands and fixed a margin issue when form is placed inside Text Block element of Visual Composer
- **Fixed:** When using reset form, dropdown value must be updated accordingly to the default selected option of de dropdown and if case none, make sure it is emptied. (this was conflicting with conditional logic and other calculations)
- **Fixed:** Make sure google.api script is loaded after super forms script
- **Fixed:** Issue with default value that only contains a number
- **Fixed:** Make sure ajax request URL contains lang parameter for WPML compatibility

## May 11, 2017 - Version 3.0.0

- **Added:** Documentation section (will be updated from time to time, under construction)
- **Added:** Google places autocomplete/autopopulate fields
- **Added:** Ability to replace variable field {tags} with actual field values
- **Added:** New tag `{post_permalink}` (will retrieve the currnt post URL)
- **Added:** Option to retrieve {tags} for hidden fields default value setting
- **Added:** Option to set max width/height for radio and checkbox image selections (if empty it will apply 100% width and no max height)
- **Added:** Option to set width and height for radio/checkbox images (leave blank for original image size)
- **Added:** Option to set color for preloader icon (form loading icon)
- **Changed:** populating form search entry loading icon replaced gif for font loading icon
- **Changed:** Form preloader icon updated for fontawesome icon (GIF would freeze because Js processing is single threaded)
- **Improved:** Variable fields can now also contain {tags} inside it's condition (it will be replaced with the field value dynamically)
- **Improved:** Notify user if Unique field name is empty (from now on this field is required)
- **Fixed:** loading icon not completely centered (25px of to left)
- **Fixed:** RTL alignment issues / padding issues with some elements depending on theme field sizes
- **Fixed:** Issue with replacing whitespace for value containing only the letter "s"
- **Fixed:** issue with dropdowns items that have the same value being send to email while user didn't select them
- **Fixed:** $entry_data not required for output_builder_html()
- **Fixed:** buttons radio/checkbox icon left alignment for medium size theme
- **Fixed:** Issue with empty default values returning Array in field and textarea due to {tag} not found
- **Fixed:** ReCAPTCHA alignment fix
- **Fixed:** Checkbox and radio buttons images now based on image original size (no longer cut of/cropped)
- **Fixed:** Checkbox and radio default selected item bug with conditional logic

## Apr 17, 2017 - Version 2.9.0

- **Added:** Form setup wizard (easy for selecting theme and setting other common options like: E-mail To: and Subject: settings)
- **Added:** Toggle switch (on/off) field
- **Added:** Tags/keyword option for text field
- **Added:** Option to auto populate form with data based on last contact entry from a logged in user.
- **Added:** `{post_author_id}` and `{post_author_email}` tags to retrieve author information inside the form based on the current page/post
- **Improved:** when doing a POST also send json formatted data
- **Fixed:** plus icon not correctly positioned in IE in back-end for items on dropdown/checkboxes etc.
- **Fixed:** Problem with checkbox/radio images cutting the image, using contain method instead of cover now.
- **Fixed:** issue with showing "All" Contact entries also showing deleted items
- **Fixed:** Browsing images not working back-end checkbox images when adding new checkbox option
- **Fixed:** When using "Clear / reset the form after submitting" do not empty hidden_form_id field
- **Fixed:** When using "Clear / reset the form after submitting" make sure we trigger conditional logic and other actions after fields are emptied
- **Fixed:** issue with unique code gerator when length is set to 0
- **Fixed:** issue with file upload field inside dynamic columns

## Mar 12, 2017 - Version 2.8.0

- **Added:** Option to generate invoice numbers with leading zero's for hidden fields when "generate random number" is enabled
- **Added:** custom reply to headers independent for admin and confirmation emails
- **Added:** Option to send independent additional headers for admin and confirmation emails
- **Added:** Option to also add CC and BCC for confirmation emails
- **Added:** new filter hook - super_countries_list_filter
- **Added:** unique id attribute on form, might become in handy for any plugin/script that only accepts selection by id attribute
- **Improved:** User friendly and logical navigation for the email settings and headers
- **Improved:** Quantity field can now have decimal steps e.g 0.5 or custom increment below 1
- **Improved:** Ability to use line breaks as default value on textarea element
- **Improved:** user friendly settings more logically displayed/sorted
- **Improved:** Save generated code(s) into options table instaed of postmeta table per contact entry
- **Improved:** Use transient to cache generated codes instead of saving it to database on each page load
- **Fixed:** Reply-To: header setting for admin and confirmation email not replacing {tags} with values
- **Fixed:** issue with generating random codes typo: upercase > uppercase
- **Fixed:** issue with dynamic columns and variable fields not updating {tags} correctly on data attribute and {tags} inside new_value attribute
- **Fixed:** SMPT throws PHP error when additional headers are not empty
- **Fixed:** not triggering to update field values based on fields that where conditionally hidden and after visible again
- **Removed:** placeholder setting for currency fields

## Feb 26, 2017 - Version 2.7.0

- **Improved:** When replacing {tags} with correct value, replace logged in user data after all other tags have been replaced
- **Improved:** When choosing CSV file for a field to retrieve data, make sure only CSV files can be selected from media library
- **Improved:** When using dynamic columns clear/reset the field values inside the newly added set of fields
- **Fixed:** Prefix and Suffix not being added for unique code generation setting (hidden field)
- **Fixed:** Uppercase must be empty by default when generating unique code generation
- **Fixed:** When a file upload field was used, it would be replaced with previous loop field value in confirmation emails only
- **Fixed:** Some issues with icons in other themes
- **Fixed:** When file upload was empty, show the field in contact entry and tell user that no files where uploaded

## Feb 22, 2017 - Version 2.6.0

- **Added:** IBAN validation for text fields
- **Improved:** When not using preloader, and using multi-parts make sure the first multi-part is active within php code so we don't have to wait for js script to be loaded and handle this
- **Fixed:** Themes overriding styles on the conditional logic textarea and variable conditions textarea
- **Fixed:** make sure to exclude forms from the default wordpress search
- **Fixed:** Datepicker issue when connecting 2 dates with eachother and the other having both weekends and work days disabled (beforeShowDay function must always return array)
- **Fixed:** Issue with updating contact entry data if search field is used
- **Fixed:** If any theme would ever remove the href tag completely from the button do a typeof undefined check
- **Fixed:** Currency field when populating data initialize the field with the correct format on page load
- **Fixed:** Conflict with conditional logic when using multiple forms on single page that contain the same field names (on submit button click)
- **Fixed:** Issue replacing tags when using a custom redirect after form submission
- **Fixed:** Filtering contact entries from back-end when custom db prefix is used
- **Fixed:** When deleting dynamic column make sure we do not skip the fields that need to be triggered based on deleted fields
- **Fixed:** Make sure that if a radio element has 2 or more items with the same value to only set 1 to be active by default (maybe in future we should add a check for duplicate values before saving the element in back-end)
- **Fixed:** IE issue with function variable parsed as object (IE didn't like this)
- **Fixed:** Custom submit button Redirect to link or URL not retrieving correct permalink by ID for pages and posts
- **Fixed:** When redirecting form to custom page that doesn't contain the form styles, make sure the success message still uses the theme styles based on it's form settings

## Feb 06, 2017 - Version 2.5.0

- **Improved:** Speed, skipping fields that have been triggered previously by the same changed field when calling JS hook: after_field_change_blur_hook()
- **Fixed:** Some third party plugins sometimes conflict with file upload element
- **Fixed:** RTL for success message
- **Fixed:** Back-end preview mode conflict with conditional logic (finding 2 fields with same name because of builder page containing the same field)
- **Fixed:** Issue with datepicker format returning a javascript error with Date.parseExact()

## Jan 25, 2017 - Version 2.4.0

- **Added:** Loading icon for search field for contact entry/auto populate field with entry data
- **Added:** JS action hook: SUPER.after_duplicating_column_hook()
- **Changed:** CSS selector for messages from: error to: super-error, success to: super-success, info to: super-info
- **Improved:** Overal code/speed optimization
- **Improved:** Dropdown item responsiveness (don't cut words)
- **Fixed:** When auto populating contact entry data make sure to update conditions / variable fields
- **Fixed:** issue with variable fields containing tags that have the same name inside it as the tag name itself e.g: `option_{option}`
- **Fixed:** when updating conditional logic and the column is updated to become either hidden or visible, make sure to call the blur hook to the fields inside the column
- **Fixed:** issue with removing dynamic column and updating conditions/math/variable fields
- **Fixed:** Drag & Drop issue with multiple file upload elements (adding image to all the file uploads instead of only one)
- **Fixed:** undefined variable $class on currency element
- **Fixed:** File upload issue: cannot call methods on fileupload prior to initialization
- **Fixed:** Even when max / min file upload was set to 0 it would still display an error message
- **Fixed:** checking with !session_id() instead of session_status()==PHP_SESSION_NONE for PHP 5.4+

## Jan 18, 2017 - Version 2.3.0

- **Improved:** speed for conditional logic
- **Improved:** speed for variable fields
- **Improved:** overal code optimizations
- **Fixed:** Issue with variable fields containing {tags} and not being updated if the {tag} field was updated
- **Fixed:** image not being visible when printing contact entry
- **Fixed:** compatibility with conditional logic for currency fields
- **Fixed:** Image max width problem (responsiveness)
- **Updated:** PHPMailer to v5.2.22 due to remote code execution vulnerability

## Jan 05, 2017 - Version 2.2.0

- **Added:** Option to let hidden fields generate a random unique number (options: length, prefix, suffix, uppercase, lowercase, characters, symbols, numbers)
- **Added:** Convert text field to search field to search contact entries by title, and auto populate form fields with entry data (search methods: equals / contains)
- **Added:** Option to enable updating contact entry data if one was found based on a search field
- **Added:** Option to do a custom POST method to a custom URL with all form fields posted
- **Fixed:** First dropdown fields automatically focussed when going to next / prev multi-part step
- **Fixed:** JS Composer using global styles conflicting with super forms duplicate column + button making it invisible when it should be visible

## Dec 18, 2016 - Version 2.1.0

- **Added:** JS action hook: SUPER.before_scrolling_to_message_hook()
- **Added:** JS action hook: SUPER.before_scrolling_to_error_hook()
- **Added:** Option to use {tags} in variable field conditional logic e.g: [Field 1] >= {field2}
- **Fixed:** Make sure grid system column counter is reset after form has been generated to prevent issues with multiple forms on a single page
- Included: Document with all actions and filter hooks

## Dec 12, 2016 - Version 2.0.0

- **Added:** Currency field
- **Added:** Button option to reset / clear the form fields
- **Added:** Option to reset / clear the form after submitting
- **Added:** JS action hook: SUPER.after_form_cleared_hook()
- **Added:** Option to enter the submit button loading state text e.g: Loading...
- **Added:** Option to change button loading state name via settings
- **Added:** Option to hide / show the form after form being submitted
- **Added:** Option to set margin for success message (thank you message)
- **Added:** validate multi-part before going to next step
- **Added:** new filter hook - super_before_sending_email_attachments_filter
- **Added:** new filter hook - super_before_sending_email_confirm_attachments_filter
- **Fixed:** datepicker not showing because of timepicker undefined bug
- **Fixed:** bug with max / min selection for dropdown and checkboxes
- **Fixed:** multi-part validation trying to submit the form if no errors where found in the mulit-part
- **Fixed:** Slider field thousand seperator
- **Improved:** A better mobile user friendly datepicker
- **Improved:** A better overall mobile user friendly experience
- **Changed:** When checkbox has set a maximum don't show an error to users after selecting to many items, instead disable selecting items

## Nov 17, 2016 - Version 1.9.0

- **Added:** Own custom Import & Export functionality for Forms (no longer need to install the default WP import/export plugin that uses XML format)
- **Added:** Option to hide column on mobile devices based on form width
- **Added:** Option to hide column on mobile devices based on screen width
- **Added:** Option to disable resizing to 100% on mobile devices based on form width
- **Added:** Option to disable resizing to 100% on mobile devices based on screen width
- **Added:** Option to force 100% on mobile devices even if one of the other responsive settings are enabled
- **Added:** Position option for columns: static, absolute, relative, fixed
- **Added:** Positioning option for columns in pixels (top, left, right, bottom)
- **Added:** Custom field class option for all elements
- **Added:** Custom (wrapper) class option for all elements
- **Added:** Background image option for columns
- **Added:** Option to set background opacity on columns
- **Added:** JS action hook: SUPER.after_preview_loaded_hook()
- **Added:** JS action hook: SUPER.before_submit_button_click_hook()
- **Fixed:** File upload field not displaying errors inside multi-part column
- **Fixed:** HTML element {tags} must only reflect on the form elements inside it's current form and not an other form (when more than 1 is used on a single page)
- **Fixed:** Issue with masked input not converting the mask to a string
- **Fixed:** applied stripslashes on HTML element for title, description and html to avoid backslashes when qoutes are used
- **Fixed:** replaced field type 'varchar' with 'var' due to some servers do not like varchar being parsed in an object or string via wordpress ajax calls
- **Fixed:** Image alignment
- **Fixed:** .popup class replaced with .super-popup to avoid conflicts on builder page
- **Fixed:** Browse images in back-end initialized multiple times
- **Fixed:** When using multiple forms the second form submit button wouldn't appear
- **Fixed:** When multiple custom submit buttons are used always the last button was being removed thinking it was the default submit button
- **Improved:** Code optimization, massive speed improvement for large forms on mobile devices
- **Improved:** When icon border color is empty do not add the border

## Nov 7, 2016 - Version 1.8.0

- **Fixed:** Conditional logic / Variable logic issue with incorrect float convertion
- **Fixed:** Issue with form autocomplete
- **Fixed:** file upload element exclude from email setting not only working on body content but not for the email attachment
- **Fixed:** conditional logic not being updated on columns that are inside a dynamic column
- **Fixed:** Using custom submit button with preloader disabled shows the default button for a split second
- **Fixed:** $forms_custom_css undefined
- **Fixed:** Search issue contact entries
- **Improved:** Updated plugin activation timeout from 5 seconds to 60 seconds for slow servers
- **Added:** new filter hook - super_before_sending_email_data_filter

## Oct 25, 2016 - Version 1.7.0

- **Added:** Option to update contact entry data via back-end
- **Added:** Option to export individual Contact entries and select the fields to export + rename the column names
- **Added:** Option to filter contact entries based on a specific form
- **Added:** Radio buttons now can return custom taxonomy, post type and CSV items
- **Added:** Option to count words on textarea fields that can be used with the Calculator element (useful for translation estimations)
- **Improved:** Contact entry search query
- **Improved:** Conditional logic speed
- **Improved:** Variable conditions speed
- **Improved:** Code optimization
- **Improved:** When adding dynamic fields update conditional logic and variable logic field names only if they exists otherwise skip them
- **Improved:** Variable fields can now contain multiple {tags}
- **Improved:** File Upload system (no refreshing required when one file didn't make it or when any other error is returned)
- **Fixed:** Conditional logic not working on dropdown
- **Fixed:** Issue with submit button name being stripped/validated on builder page
- **Fixed:** Dynamic fields not updating calculations after deleting a row
- **Fixed:** Not able to download contact entry CSV export
- **Fixed:** Incorrect offset on builder page when other plugin messages are being shown
- **Fixed:** Minimal theme radio buttons without icon to much padding left
- **Fixed:** Avada making the datepicker month next/prev buttons font color white
- **Fixed:** undefined $data, issue with dynamic columns and updating the conditional logic dynamically
- **Fixed:** When using reCAPTCHA and only sending dropdown label the value is duplicated in email
- **Removed:** filter function do_shortcode on the_content, causes issues in some ocasions (let the theme handle this filter instead)

## Oct 15, 2016 - Version 1.6.0

- Fixed Vulnrebility: Unrestricted File Upload
- **Fixed:** Small bug with incorrect calculation order in combination with conditional logic

## Oct 12, 2016 - Version 1.5.0

- **Fixed:** Javascript compatibility issue with Safari browser
- **Fixed:** Last field duplicated in confirmation email (send to submitted)
- **Improved:** When typing a unique field name unwanted characters are stripped, only numbers, letters, - and _ are allowed.
- **Added:** Option to only allow users to select weekends or work days for datepickers

## Oct 8, 2016 - Version 1.4.0

- **Fixed:** Issue with file uploading when filename contains comma's
- **Fixed:** Issue with variable fields and calculations incorrect order resulting in wrong calculations
- **Added:** Option to retrieve Contact Entry ID with tag: `{contact_entry_id}`  (can be used in success message and emails)

## Oct 5, 2016 - Version 1.3.0

- **Fixed:** Conflict class WP_AutoUpdate, changed it to SUPER_WP_AutoUpdate
- **Fixed:** Dropdown no longer being largen when focussed
- **Fixed:** Duplicate column fields no longer hiding dropdown content (overflow:hidden removed)
- **Fixed:** saving directory home_url() changed to site_url() (in case core files are located different location on server)
- **Fixed:** Checkbox images retrieving thumbnail version, now returning original image
- **Fixed:** Issue with font-awesome stylesheet not having a unique name, changed it to super-font-awesome
- **Fixed:** {tag} in HTML element not displaying negative calculator value correctly
- **Added:** Option to update conditional logic dynamically when using dynamic fields (add more +)
- **Added:** JS action hook: SUPER.after_responsive_form_hook()
- **Added:** JS action hook: SUPER.after_duplicate_column_fields_hook()
- **Added:** JS filter hook: SUPER.after_form_data_collected_hook()
- **Added:** option to add padding to columns
- **Added:** option to add background color to columns
- **Added:** Option to return current date (server time) for datepicker field
- **Added:** Option to return current time (server time) for timepicker field
- **Added:** Option to add input mask (useful for phone numbers and other validations)
- **Changed:** Removed bottom padding of form, you can now change the padding with settings
- **Improved:** several CSS styles

## Sep 20, 2016 - Version 1.2.9

- **Fixed:** Greek characters issue with CSV file
- **Fixed:** Datepicker field not initialized within dynamic columns
- **Fixed:** Datepicker max/min range affecting the validation max/min characters
- **Fixed:** Icon color settings not showing when selected "No (show)"
- **Fixed:** Class align-left conflict with Heading elements in Visual Composer
- **Fixed:** HTML value not updated correctly with {tag} for calculator element
- **Added:** Option to save only the value or both value and label for contact entry data for elements dropdown/checkbox/radio
- **Added:** new action hook - super_after_saving_contact_entry_action
- **Added:** new filter hook - super_after_contact_entry_data_filter
- **Added:** Option to make disable fields (disallow user from editing input value)
- **Added:** Option to use {tags} within the variable field update value setting
- **Added:** Option to add the Form name to columns on the the contact entries listing

## Sep 5, 2016 - Version 1.2.8

- **Fixed:** Avada giving styles to anything with popup class, conflicting Super Forms tooltips
- **Fixed:** Firefox issue with editing labels in form builder
- **Added:** Super Forms Demos (share / sell your own forms)
- **Added:** RTL support (text from right to left)
- **Added:** Option to add custom CSS per form
- **Added:** Option to allow user input filter the dropdown options/values
- **Added:** Option to add custom class on button element
- **Added:** new filter hook - super_form_settings_filter
- **Improved:** Grid system
- **Improved:** In backend font-awesome only loaded on the Super Forms pages that uses fontawesom icons

## Aug 5, 2016 - Version 1.2.7

- **Added:** 5 new demo forms!
- **Fixed:** Small bug when changing column size (in some cases not being saved/remembered)
- **Fixed:** Uncaught TypeError when datepicker default value is empty
- **Fixed:** Only apply meta_query custom search for super forms contact entries
- **Fixed:** When WP network site is enabled, wrong directory is called for media uploads
- **Added:** Option to calculate difference between 2 timepickers (Calculator element required!)
- **Added:** Option to calculate age based on birth date for datepickers (Calculator element required!)
- **Added:** Date range option when exporting contact entries to CSV
- **Added:** Labeling for Columns and Multi-parts on form builder page (easier to keep track of sections)
- **Added:** Option to make hidden field a variable (change value dynamically with conditional logic)
- **Added:** Ability to use {tags} in HTML elements (tags will be updated on the fly!)
- **Added:** Option to use {tags} inside Additional headers setting
- **Added:** Setting to chose what value should be send to emails for dropdowns, checkbox and radio buttons
- **Added:** `{field_label_****}` tag to use in emails and subjects etc.
- **Added:** Option to do math between datepickers with Calculator element
- **Added:** new filter hook - super_common_attributes_filter
- **Improved:** Contact entry export to CSV now includes: entry_id, entry_title, entry_date, entry_author, entry_status and entry_ip

## July 26, 2016 - Version 1.2.6

- **Fixed:** Missing options for Slider field
- **Added:** Option to save custom contact entry titles including the option to use {tags}
- **Added:** Ability to automatically update the plugin without the need to delete it first
- **Added:** Option to import Contact Entries from CSV file
- **Improved:** Contact entry filter / search function
- **Improved:** __DIR__ replaced with dirname( __FILE__ ) due to PHP version < 5.4

## July 14, 2016 - Version 1.2.5

- **Fixed:** min/max number for quantity field
- **Fixed:** File upload on multi-part sites are not working
- **Fixed:** Issue with drag and drop in some cases the page scrolls down to the bottom automatically
- **Fixed:** Issue with Internet Explorer and WP text editor
- **Fixed:** Removed limitation of 5 for dropdowns when custom post type is selected
- **Added:** Option to add custom regex for field validation
- **Added:** Float regex as a ready to use option to for field validation
- **Added:** Option to add/deduct days between connected datepickers (this will change the max/min date between connected dates)
- **Added:** Option to choose to return slug, ID or title for autosuggest for both post and taxonomy
- **Added:** Option to choose to return slug, ID or title for dropdowns for both post and taxonomy
- **Added:** Option to set delimiter and enclosure for dropdowns and autosuggest when using CSV file
- **Added:** Option to translate/rename multi-part Prev and Next buttons independently
- **Added:** 5 demo forms for Add-on Front-end posting
- **Added:** new filter hook - super_form_before_do_shortcode_filter
- **Improved:** General CSS improvements
- **Improved:** Dropdown items now have overflow hidden to avoid problems with long options
- **Improved:** TAB functionality for both multi-part and without multi-part columns
- **Improved:** When checkbox/radio images are being used, and the image no longer exists, a placeholder image will show up

## June 27, 2016 - Version 1.2.4

- **Fixed:** Safari input field line-height
- **Fixed:** Multi-part prev button not correctly aligned on front-end
- **Fixed:** When button setting is set to full width multi-part buttons are also affected
- **Fixed:** Image browser not intialized when adding new checkbox element dynamically in backend
- **Fixed:** Conditional logic display block/none issue in safari and IE
- **Fixed:** Attachment meta data not being saved correctly
- **Fixed:** Conditional logic for file upload field
- **Added:** Option to transform textarea field into a text editor (TinyMCE)
- **Added:** Autosuggest/Autocomplete option for text field
- **Added:** Quantity field (with -/+ buttons)
- **Added:** Option to set a transparent background for fields
- **Added:** Option to retrieve specific post types for dropdown and autosuggest
- **Updated:** Fontawesome icons

## May 26, 2016 - Version 1.2.3

- **Fixed:** PHP Zend error when APC is enabled (only appeared on specific PHP versions)
- **Fixed:** Radio button dot alignment with horizontal alignment
- **Fixed:** Issue with "contains" conditional logic in combination on dropdown/checkbox/radio
- **Fixed:** Finger touch for slider element on mobile devices
- **Fixed:** When slider is put inside multi-part it's not set to default positioning due to multi-part having display:none; before form is rendered
- **Fixed:** Issue with prev/next buttons being removed when adding custom button to multi-part
- **Fixed:** When predefined elements are being dropped, make sure to check if we are dropping multiple items and then do the check to rename existing field names
- **Improved:** Tooltips for mobile devices
- **Improved:** Responsiveness backend (multi-items dropdown/radio/checkbox)
- **Improved:** Conditional logic filter priority set to 50 so it will be fired at later point
- **Added:** Option to automatically go to next step for multi-parts
- **Added:** Dummy content (40+ example forms)
- **Added:** Option to add image to checkbox/radio items (image selection)
- **Removed:** Placeholer option on slider element (not needed)

## May 15, 2016 - Version 1.2.2

- **Fixed:** wp_enqueue_media(); not called on settings page
- **Fixed:** Conditional logic in combination with preloader
- **Fixed:** File upload error message fading out after 1 sec.
- **Fixed:** Default radio/checkbox/dropdown selection now automatically apply/filter conditional logics
- **Fixed:** Enqueue datepicker / timepicker if Ajax calls are enabled
- **Improved:** Now using wp_remote_post instead of file_get_contents because of the 15 sec. open connection on some hosts
- **Improved:** Allowed extensions for file uploads
- **Improved:** Overall conditional logic
- **Improved:** Overall drag & drop sensitity
- **Improved:** When using SMTP settings it will now check wether or not the settings are correct and if we could establish a connection
- **Improved:** default "Field is required" string now translation ready (instead of manually adding error messages for each field)
- **Added:** Option to set text and textarea fields to be disabled
- **Added:** Option to make columns invisible although they can still be used for calculations and saved or send by mail
- **Added:** Option to minimize elements and columns/multiparts in backend (even more user friendly form building!)
- **Added:** Currency, Decimals, Thousand separator, Decimal separator options for Slider field
- **Added:** parameter entry_id on action hook "super_before_email_success_msg_action"
- **Added:** Option to do a single condition with 2 seperate validations with (AND / OR)

## May 3, 2016 - Version 1.2.1

- **Fixed:** When multi-part is being used with multiple custom buttons skip the button clone function
- **Fixed:** Color settings for custom button not being retrieved correctly when editing button
- **Fixed:** z-index on Save/Clear/Edit/Preview actions lowered due to overlapping the WP admin bar
- **Fixed:** Dropdown with Icon inside field and right aligned arrow is hidden below the Icon
- **Improved:** Bug fixed combination columns inside multipart
- **Improved:** Conditional logic (contains ??) in combination with checkbox/dropdown with multi select
- **Improved:** When reCAPTCHA key or secret is not filled out, show a notice to the user
- **Added:** Option to remove margin on field
- **Added:** Option to set a fixed width on the field wrapper
- **Added:** Option to append class to the HTML element
- **Added:** New element: Slider (dragger)
- **Added:** More flexibility with HTML element
- **Changed:** Checkbox/Radio buttons will now have their custom UI instead of default browser UI with custom colors
- **Changed:** Don't show reCAPTCHA key/secret under settings on create form page

## April 29, 2016 - Version 1.2

- **Fixed:** If a theme is using an ajax call get_the_title cannot be used for `{post_title}` to retrieve the Post Title, now it will check if post_id is set by the ajax call, if this is the case it will try to use it to retrieve the title, otherwise the field value will stay empty
- **Fixed:** Conditional logic broken on column after changing .column class to .super-column for js_composer conflict with styles
- **Fixed:** If multiple forms are used on a single page the form will scroll to the first error on the page instead of checking on the current form itself
- **Fixed:** For the element button the target attribute (open in new browser) was not being affected
- **Fixed:** If contact entries are exported to CSV the /uploads/files folder must exist
- **Improved:** Column system
- **Added:** Option to enable Ajax mode if theme uses Ajax to load content dynamically
- **Added:** Option to align the reCAPTCHA element (left, center, right) default is right alignment
- **Changed:** Default positioning for errors are now bottom right

## April 24, 2016 - Version 1.1.9

- **Fixed:** wp_mail() additional headers not parsed since v1.1.7
- **Added:** Option to export Contact entries to CSV file (including attachments via URLs)
- **Added:** Progress bar on file upload element
- **Improved:** When alement is added, it will automatically be renamed if same field name exists
- **Improved:** Better script for processing attachments to email for both wp_mail & smtp
- **Improved:** Form builder page is now more user friendly (backend)
- **Improved:** Responsiveness of form builder page (backend)

## April 22, 2016 - Version 1.1.8

- **Fixed:** translation issue name conversion
- **Added:** Option to override button color and icon and other settings for the button element or just select to use the default settings
- **Added:** All fields can now auto populate values if an URL parameter with the field name has been set
- **Added:** Datepicker can now connect with another datepicker (useful to set a max/min range for both pickers
- **Changed:** Upload files to Media Library instead of plugin folder (prevents missing files after deleting plugin)
- **Changed:** Submit button cannot be clicked twice, and will display a loading icon

## April 17, 2016 - Version 1.1.7

- **Fixed:** style class ".column" changed to ".super-column" because of JS Composer conflicting on .column class
- **Fixed:** added line-height to fields to make sure theme styles don't override it
- **Added:** "Add more +"" option for columns to let users duplicate all the fields inside the column dynamically

## April 15, 2016 - Version 1.1.6

- **Fixed:** Uncaught TypeError: Cannot convert object to primitive value
- **Fixed:** reCAPTCHA conditional-validation-value undefined
- **Fixed:** When minimum files are not set for file upload it will not proceed to submit the form
- **Fixed:** textarea cannot add line breaks, form is trying to submit after pressing enter when textarea is focussed
- **Fixed:** Warning: array_merge(): Argument #2 is not an array, when first time creating Form
- **Added:** Submit Button element, allows you to add conditional logic on submit button if placed inside colum
- **Added:** Tags to retrieve values of logged in user `{user_login}`, `{user_email}`, `{user_firstname}`, `{user_lastname}`, `{user_display}`, `{user_id}`

## April 12, 2016 - Version 1.1.5

- **Fixed:** When a Form is duplicated in some case the fields are not being rendered
- **Fixed:** Dropdown with Minimal theme not closing correctly
- **Improved:** Calendar translation strings
- **Added:** Deactivate button added to Settings page

## March 16, 2016 - Version 1.1.4

- **Fixed:** Some small issues with TABBING through fields in combination with hidden fields and conditional logics inside double columns
- **Fixed:** Datepicker minimum date negative number not being applied (date range min/max)
- **Fixed:** When countries.txt now being loaded through Curl to avoid problems on servers with scraping security
- **Fixed:** When conditional logic is used and the field is inside 2 columns it is still being validated
- **Fixed:** Special conditional field validation not working with numbers
- **Fixed:** Divider width percentage not working, only pixels are working
- **Added:** Option to allow field to be empty and to only validate the field when field is not empty
- **Added:** Max/Min number for text field
- **Added:** default value option for datepicker field
- **Added:** Year range for datepicker field
- **Added:** validation option to conditionally check on an other fields value with field tag e.g `{password}`, this way you can for instance add a password confirm check (useful for registration forms)
- **Changed:** function to return dynamic functions as an array, this way it could easily be implemented into the preview in the backend while creating forms

## March 4, 2016 - Version 1.1.3

- **Fixed:** using stripslashes() for email data to remove possible quotes
- **Fixed:** version not included in some styles/scripts (problems with cache not updated after new version is uploaded)
- **Fixed:** issue with dropdown and file upload maximum items setting not triggered to set field to multiple items allowed
- **Fixed:** $ conflicting, use jQuery instead
- **Fixed:** when TABBING through fields inside multipart it will switch to next multipart automatically
- **Fixed:** when keyboard arrows are being used to select dropdown arrows the conditional logic was not being triggered
- **Fixed:** if next field is a checkbox or radio button the TAB did not focus this field
- **Improved:** line height for dropdown items adjusted for more user friendly expierience
- **Added:** functionality to dynamically add and execute javascript functions with new provided filter hooks
- **Added:** new filter hook - super_common_js_dynamic_functions_filter

## February 28, 2016 - Version 1.1.2

- **Fixed:** When pressed enter on selected dropdown item conditional logic was not triggered
- **Fixed:** When submit is clicked and multi-part does not contain errors the error clas is not being removed
- **Improved:** responsiveness for dropdowns on mobile
- **Improved:** Removed the check icon on dropdown selected items, only highlighted from now on
- **Added:** Option to redirect to a custom URL and add paramaters with the use of tags e.g: `?username={field_username}`

## February 25, 2016 - Version 1.1.1

- **Fixed:** Not able to use arrow up/down and Enter key when dropdown element is focussed
- **Improved:** When TABBING through fields, the submit button will also be focused and enter can trigger to submit the form
- **Improved:** For a better user experience field validation is now only triggered on change and blur (unfocus)
- **Improved:** When Multi-part contains errors it will scroll to this section and will make it visible

## February 24, 2016 - Version 1.1.0

- **Fixed:** Multi-part buttons (prev/next/submit) not correctly aligned and improved Responsiveness for mobile devices
- **Improved:** For some themes no alert icon was shown for the multi-part section if fields where not correctly filled out inside it
- **Improved:** When using TAB to go through the form, the dropdown element was being skipped (since custom UI)
- **Improved:** Changed color to a lighter color of the placeholder for settings like CC/BCC
- **Improved:** When TAB is used the very next field will not be validated instantly, but only after a change was made
- **Improved:** When Multi-part next/prev button is being clicked scroll to top of the next multi-part section (useful for long sections)
- **Changed:** countries.txt is now no longer automatically sorted with asort()
- **Changed:** countries.txt can now be customized (e.g add new countries or add most used countries to the top of the file)

## February 19, 2016 - Version 1.0.9

- **Fixed:** Result 'status' in filter super_before_email_loop_data_filter not being set caused uncaught error
- **Fixed:** When in preview mode conditional logic not triggered after changing dropdown selection
- **Fixed:** reCAPTCHA initialized twice instead of once, which results in error 'placeholder must be empty'
- **Fixed:** reCAPTCHA now also loaded in preview mode
- **Changed:** When deleting plugin and uploading newer version do not reset default settings
- **Added:** Purchase code API activation
- **Added:** Possibility to not display message after redirect if Thanks title and description are both empty

## February 11, 2016 - Version 1.0.8.1

- **Fixed:** after previous update all fields could have duplicate field name
- **Added:** New filter hook - super_before_email_loop_data_filter

## February 9, 2016 - Version 1.0.8

- **Fixed:** Multiple file upload fields not seen as unique field names when actually containing unique names
- **Fixed:** When conditional logic used on an element inside a column that is placed inside a multipart it fails to display the multipart
- **Fixed:** Submit button sometimes not correctly aligned
- **Added:** New filter hook - super_form_styles_filter
- **Added:** New predefined element (E-mail address)

## January 14, 2016 - Version 1.0.7

- **Fixed:** Datepacker in some cases not visible when theme is overiding styles
- **Fixed:** Element to browse images only initialized when editing element and not on Create form page load
- **Fixed:** SUPER_Settings class php error when in preview mode
- **Added:** Possibility to translate the date picker month and day names

## January 9, 2016 - Version 1.0.6

- **Fixed:** For Add-on purposes, and future updates: Forms that have been saved after new settings have been added, it will use their default values
- **Fixed:** Nested conditional logic not working (elements inside columns)
- **Fixed:** Tooltips not being displayed when mouseover
- **Improved:** SMTP emailer with more options to adjust - keepalive, ssl, tls, timeout, smtp debug mode
- **Improved:** Element panel scrolls down with user (useful for long forms)
- **Improved:** Overal improvements for dropdown field
- **Improved:** Overal improvements for conditional logics
- **Improved:** Tags functions, add-ons can now hook into tags and add their own if needed
- **Added:** Files are now attached as an file in emails
- **Added:** Option to retrieve tags inside the thank you title and description after a successful submitted form
- **Added:** New notifications function for better and more flexible way to display messages to users
- **Added:** Option to retrieve Post title (post_title) and Post ID (post_ID) as default value
- **Added:** Conditional Validation for fields (== equal, ? contains, > greater than etc.)
- **Added:** Dropdown CSV upload possibility
- **Added:** Dropdown retrieve WP categories (by taxonomy name e.g category, product_cat etc.)
- **Added:** Option to export and import form settings per form and the default form settings
- **Added:** For Add-on purposes, a function to return error and success messages
- **Added:** New action hook - super_before_email_success_msg_action
- **Added:** New action hook - super_before_printing_message
- **Changed:** Action hook from super_before_printing_redirect_js_action to super_before_email_success_msg_action

## December 18, 2015 - Version 1.0.5

- **Added:** Possibility to use multiple forms on one page with each a different style
- **Added:** New date format dd-mm-yy for date field
- **Added:** Possibility to set a custom date format for date fields
- **Fixed:** When HTML is applied on checkbox/radio labels, it was not correctly escaping it's attributes on the builder page (backend)

## December 17, 2015 - Version 1.0.4

- **Added:** Option to exclude any field data from both emails instead of only the confirmation email
- **Added:** When reCAPTCHA key is not filled out, a notice will popup on the front-end
- **Added:** Add-ons can now insert hidden fields inside an element, this was not possible before
- **Fixed:** Color pickers on form builder page initialized when already initialized
- **Fixed:** Hidden fields where skipped from email in some cases
- **Fixed:** Icon positioning on some elements not always correctly aligned when selected Outside the field
- **Fixed:** Textarea at form builder within the load/insert form should not be visible
- **Fixed:** Diagonal button background hover color not correctly changing color after mouseleave
- **Fixed:** For Add-on purposes, we check if the field label is set or not before replacing it by the Tag system, otherwise PHP might throw an error in case the Add-on has not set a field with the name label (same goes for field value)
- **Fixed:** For Add-on purposes, if an Add-on element has been created and the Add-on is being deactivated make sure the element is skipped
- **Fixed:** Made sure themes do not override border-radius for input fields

## December 12, 2015 - Version 1.0.3

- **Added:** Possibility to have multiple forms on one page with each their own fileupload element
- **Fixed:** Not able to drop existing elements inside the multipart element on the builder page
- **Fixed:** Setting Exclude from email for fileupload element not working
- **Fixed:** If fileupload element is used, and large file uploads are taking place, the form will no longer be submitted to soon

## December 11, 2015 - Version 1.0.2

- **Added:** Action Hook (super_before_printing_redirect_js_action) to do something before displaying or redirecting after completed submitted form.
- **Fixed:** On editing column previously generated fields are not correctly retrieved.
- **Fixed:** For columns the conditional logic wasn't looping through multiple conditions only through the first condition.  

## December 10, 2015 - Version 1.0.1

- **Fixed:** Dropable snap not allowed when not a column or multipart
- **Fixed:** Conditional trigger, wasn't fired on dropdown change
- **Fixed:** Some PHP errors removed during debug mode
- **Fixed:** Some other smaller bug fixes

## December 9, 2015 – Version 1.0.0

- Initial release!
