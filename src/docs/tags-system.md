# Tags system

* [What are {tags}?](#what-are-tags)
* [How to use tags?](#how-to-use-tags)
* [Advanced tags](#advanced-tags)
* [Regular expressions](#regular-expressions)
* [When and where can I use tags?](#when-and-where-can-i-use-tags)
* [Predefined {tags} that are useful](#predefined-tags-that-are-useful)

## What are {tags}?

The tags system in **Super Forms** is a very simple but yet very powerfull feature that gives any form the **flexibility** you require.

A so called `{tag}` can retrieve the form data entered by a user **on the fly** for either later use in your emails, or to directly display it somewhere in your form to be displayed to the user itself.

A simple usecase would be to **summarize** the information entered by the user.

## How to use tags?

A tag is written with the so called curly braces `{}` with in between the curly braces the [unique field name](unique-field-name) of the element you wish to retrieve the value from.

When you have a field name called `first_name` and you want to retrieve this value in your email body you can retrieve it by placing `{first_name}` in the body.

**Example:**

    Dear {first_name} {last_name},
    ...

**Above will translate to:**

    Dear John Doe,
    ...

With checkboxes, radio buttons and dropdowns this will work exactly the same way except that you will also have the ability to retrieve the **label** instead of the **value**.

Let's say you added a checkbox element with the following options:

* My fav color is red | Red
* My fav color is green | Green
* My fav color is orange | Orange

The left side is your option `label` and on the right your `value`.

If you would call your checkbox `fav_color` then you would retrieve the selected option values with the tag `{fav_color}`. But if you want to retrieve the label you can use `{fav_color;label}`.

**Example:**

    Selected color(s): {fav_color}
    Selected color(s): {fav_color;label}

    ...

**Above will translate to:**

    Selected color(s): Orange
    Selected color(s): My fav color is orange
    ...

## Advanced tags

### Setting and retrieving multiple values per options

Another feature you have with checkboxes, radio buttons and dropdowns is to **save multiple values** per value. In order to do this the only thing you will have to do is **seperate each value** per option with a semicolon `;`.

For instance, when you sell multiple packages based on a specific membership, you might need a different price per membership. Let's say we have a **Standard membership** and a **Gold membership**. We will ask the user to select a package. We will use a dropdown field so the user can select the according membership.

The dropdown will be named `membership` and will have 2 items with Label / Value:

* `Standard` / `standard;10`
* `Gold` / `gold;25`

Now whenever the user has selected an item from the dropdown, we can retrieve the correct price depending on their membership.

* To retrieve the **Standard membership** price we would use the tag `{membership;2}` wich would return `10`
* To retrieve the **Gold membership** price we would also use the tag `{membership;2}` wich would return `25`

Now you might ask where should I actually place this tag? You can choose to use it in one of the following locations/functions:

* Inside a HTML element (to display it to the user in for instance a summary)
* Inside [Conditional logic](conditional-logic)
* Inside [Variable fields](variable-fields)
* Inside any of the **Form Settings**, think of setting a custom **Contact Entry Title**, or defining a custom **Subject** for your emails or perhaps inside the email body itself

## Regular expressions

?> Because this feature is especially useful in combination with the **Calculator** element, you can read about using regular expressions within tags here: [Calculation examples](calculator?id=calculation-examples)

## When and where can I use tags?

* Inside your E-mail bodies, Subjects, and all other email headers you could think of.
* In combination with [E-mail if statements](email-if-statements)
* Within your [HTML elements](html)
* Inside the Success Message that is displayed to the user after a successfull submitted form
* In combination with [Variable fields](variable-fields) and also within the conditional logic statements
* When redirecting form to a custom URL to add dynamic parameters e.g: domain.com/?first-name={first_name}&last-name={last_name}
* In validation option for text fields to conditionally check for same value as other field (to compare two field values)
* You can use tags when saving contact entries with a custom title

## Predefined {tags} that are useful

**Tags that are compatible in combination with Calculator element and the Textarea field:**

* `{your_textarea_field_name_here;word}` (will count all words entered in the textarea)
* `{your_textarea_field_name_here;chars}` (will count all characters entered in the textarea, excluding carriage return, line-feed/newline, tab, form-feed, vertical whitespace)
* `{your_textarea_field_name_here;allchars}` (will count all all characters entered in the textarea, including carriage return, line-feed/newline, tab, form-feed, vertical whitespace)

**Tags that are compatible with the file upload element:**

?> Also checkout the [file upload foreach example](email-foreach-loops#how-to-loop-over-files) for the file upload element

* `{fieldname}` (retrieve list with file name(s))
* `{fieldname;count}` (retrieve total amount of files connected to this file upload element)
* `{fieldname;new_count}` (retrieve total amount of files that are yet to be uploaded)
* `{fieldname;existing_count}` (retrieve total amount of files already/previously uploaded)
* `{fieldname;url}` (retrieve file  "blob" or "URL")
* `{fieldname;size}` (retrieve file size)
* `{fieldname;type}` (retrieve file type)
* `{fieldname;name}` (retrieve file name)
* `{fieldname;ext}` (retrieve file extension)
* `{fieldname;attachment_id}` (retrieve file ID after file has been uploaded when form is submitted)
* `{fieldname;url[2]}` (retrieve specific file data, this example retrieves the third file URL if it exists based on array index)
* `{fieldname;allFileNames}` (retrieve list with all file names, it's possible to filter this list with filter hook: `super_filter_all_file_names_filter`
* `{fieldname;allFileUrls}` (retrieve list with all file URLs, it's possible to filter this list with filter hook: `super_filter_all_file_urls_filter`
* `{fieldname;allFileLinks}` (retrieve list with a link to the file, it's possible to filter this list with filter hook: `super_filter_all_file_links_filter`

**Tag to retrieve the current page or post title:**

* `{post_title}`

**Tag to retrieve the the current page or post ID:**

* `{post_id}`

**Tag to retrieve the the current post custom meta data:**

* `{post_meta_****}`

**Tag to retrieve the IP-address of the submitter:**

* `{real_ip}`

**Tag to retrieve Cart information (when WooCommerce is installed and activated):**

* `{wc_cart_total}`, `{wc_cart_total_float}`, `{wc_cart_items}`, `{wc_cart_items_price}`

**Tag to retrieve the total submission count (if form locker is used):**

* `{submission_count}`

**Tag to retrieve the latest Contact Entry ID that was created for this form:**

* `{last_entry_id}`

**Tag to retrieve the latest Contact Entry status that was created for this form:**

* `{last_entry_status}`

**Tag to retrieve the latest Contact Entry ID that was created by the logged in user:**

* `{user_last_entry_id}`

**Tag to retrieve the latest Contact Entry status that was created by the logged in user:**

* `{user_last_entry_status}`

**Tag to save previous location (URL) in a session so it will not be subject to change after navigating away and returning back at later time:**

* `{server_http_referrer_session}` (saves HTTP_REFERRER (previous page URL) into session)

**Tag to retrieve the previous location (URL) where the user navigated from before landing on the page with the form:**

* `{server_http_referrer}` (saves HTTP_REFERRER (previous page URL) into session)

**Tags to retrieve current date values in server timestamp (UTC/GMT):**

* `{server_timestamp_gmt}`, `{server_day_gmt}`, `{server_month_gmt}`, `{server_year_gmt}`, `{server_hour_gmt}`, `{server_minute_gmt}`, `{server_seconds_gmt}`

**Tags to retrieve current date values in server timestamp (Local time):**

* `{server_timestamp}`, `{server_day}`, `{server_month}`, `{server_year}`, `{server_hour}`, `{server_minute}`, `{server_seconds}`

**Tag to retrieve current post URL (permalink):**

* `{post_permalink}` (will retrieve the current post permalink where the form is placed on)

**Tag to retrieve contact entry ID that was created after submitting form:**

* `{contact_entry_id}` (can only be used in **Success Message** and E-mails)

**Tags to retrieve author information based on the current page/post the form is placed on:**

* `{post_author_id}` and `{post_author_email}` (can be used in both the [Hidden field](hidden-field) and [Text field](text-field) **Default value** option)

**Tags to retrieve values of logged in user:**

* `{user_login}`, `{user_email}`, `{user_firstname}`, `{user_lastname}`, `{user_display}`, `{user_id}`, `{user_roles}` (can be used in both the [Hidden field](hidden-field) and [Text field](text-field) **Default value** option)
* `{user_meta_****}` tag to retrieve user custom meta data

**Tag to retrieve any option from the wp_options database table:**

* `{option_****}`

**Tag to retrieve any option from the wp_options database table that is of type Array, which allows you to retrieve a specific value from that array based on the provided index/key. When no index/key is provided a json representation of the array will be returned:**

* `{option_****;arrayKey}`

**Tag to retrieve the e-mail address of blog administrator:**

* `{option_admin_email}`

**Tag to retrieve the weblog title; set in General Options:**

* `{option_blogname}`

**Tag to retrieve the tagline for your blog; set in General Options:**

* `{option_blogdescription}`

**Tag to retrieve the blog Charset:**

* `{option_blog_charset}`

**Tag to retrieve the date Format:**

* `{option_date_format}`

**Tag to retrieve the default post category; set in Writing Options:**

* `{option_default_category}`

**Tag to retrieve the blog's home web address; set in General Options:**

* `{option_home}`

**Tag to retrieve the WordPress web address; set in General Options:**

* `{option_siteurl}`

**Tag to retrieve the current theme's name; set in Presentation:**

* `{option_template}`

**Tag to retrieve the start of the week:**

* `{option_start_of_week}`

**Tag to retrieve the default upload location; set in Miscellaneous Options:**

* `{option_upload_path}`

**Tag to retrieve the posts per page:**

* `{option_posts_per_page}`

**Tag to retrieve the posts per RSS feed:**

* `{option_posts_per_rss}`

**Tag to any field value submitted by the user:**

* `{field_XXXXX}`

**Tag to retrieve the field label for the field loop {loop_fields}:**

* `{loop_label}`

**Tag to retrieve the field value for the field loop {loop_fields}:**

* `{loop_value}`

**Tag to retrieve the loop anywhere in your email:**

* `{loop_fields}`

**Tag to retrieve timestamp from datepicker value:**

* `{datepickerfieldname;timestamp}`

**Tag to retrieve the current/total PDF pages (PDF Add-on):**

* `{pdf_page}`
* `{pdf_total_pages}`

**Tag to retrieve the generated PDF file label/name/url:**

* `{_generated_pdf_file_label}`
* `{_generated_pdf_file_name}`
* `{_generated_pdf_file_url}`