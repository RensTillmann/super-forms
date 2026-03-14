---
description: Frequently Asked Questions
---

# FAQ

{% content-ref url="../common-problems/index/email-delivery-problems/why-is-my-form-not-sending-emails.md" %}
[why-is-my-form-not-sending-emails.md](../common-problems/index/email-delivery-problems/why-is-my-form-not-sending-emails.md)
{% endcontent-ref %}

{% content-ref url="../common-problems/index/email-delivery-problems/why-are-emails-going-into-spam-folder-inbox.md" %}
[why-are-emails-going-into-spam-folder-inbox.md](../common-problems/index/email-delivery-problems/why-are-emails-going-into-spam-folder-inbox.md)
{% endcontent-ref %}

<details>

<summary>How can I make all fields to be required?</summary>

This can be done per field individually because each field can have different type of Validations. To do this you can edit the field you wish and select the required validation under `General` > `Validation`, there you have several option to choose from.

</details>

<details>

<summary>Why do I get an error message when uploading a file?</summary>

If you are unable to upload files via your form the first thing you should try is to check if the server returns a 403 error (Forbidden) on the following URL: `http://yourdomain.com/wp-content/plugins/super-forms/uploads/php/`

If it returns a **403 error**, please contact your hosting company to let them fix this issue. It should return a blank page in order for the file upload field to work correctly.

Check if uploading a small file works. If this doesn't work, it is most likely due to incorrect file permissions on the plugin folders, contact your hosting company to let them look at the file permissions.

If you are able to upload smaller files, it is most likely due to your PHP settings regarding file uploads. In this case you can adjust the `post_max_size and`, `memory_limit` and `upload_max_filesize` values in your **php.ini** file or ask your hosting company to increase these values to suite your needs. Remember the following rules when changing these values:

1. To upload large files, `post_max_size` value must be larger than `upload_max_filesize`.
2. `memory_limit` should be larger than `post_max_size`

</details>

<details>

<summary>Will data be lost when updating to a newer version of Super Forms?</summary>

No, all data will remain and will **not** be deleted. Even if you would delete Super Forms through FTP.

</details>

<details>

<summary>How to redirect a user after submitting the form?</summary>

When editing the form you can enable redirect under: `Form Settings (panel)` > `Form Settings (TAB)` > `Form redirect option`.

When using **custom URL redirect** you can retrieve form values with {tags} to parse them in your GET request like so:

[http://domain.com/page/?name={first\_name}+{last\_name}\&age={birthdate}](http://domain.com/page/?name={first_name}+{last_name}\&age={birthdate})

</details>

<details>

<summary>Is it compatible with Visual Composer?</summary>

Super Forms has it's own Visual Composer (JS Composer) element.

With this element you can simply **Drag & Drop** any form at a specific location in your page. After you dropped the element you can choose which form it should load simply with the use of a dropdown that will list all the forms you have created.

The Super Forms \[shortcode] can also be inserted into a Visual Composer **HTML element**. This makes it easy to insert it into any area within your Visual Composer pages.

</details>

<details>

<summary>Is it compatible with Elementor?</summary>

Super Forms has it's own widget inside Elementor.

With this element you can simply **Drag & Drop** any form at a specific location in your page. On this widget you can easily choose which form to load.

</details>

<details>

<summary>Where can I change the error message of a field?</summary>

You can change the error message per field by editing the element and changing the `Error Message` option under the `General` TAB.

</details>

<details>

<summary>Where can I change the form font styles?</summary>

You can change the font styles when editing a form under `Form Settings` > `Font Styles`.

</details>

<details>

<summary>How to make the file upload required/mandatory?</summary>

When editing the file upload element under `Advanced` you can set a `Max` and `Min` value. If you set the Minimum to 1 or higher the field will become required.

</details>

<details>

<summary>Can I do price calculations based on user selection?</summary>

Yes, you can do this with the \[Calculator Element]

</details>

<details>

<summary>Where can I add a tooltip when the user hovers over the field?</summary>

You can add a tooltip when editing the element under `General` > `Tooltip text`

</details>

<details>

<summary>I changed the settings but it doesn't seem to affect the form?</summary>

Please make change to the form it self, and not via the global settings under `Super Forms` > `Settings` from the menu. Each form upon creating will grab the global settings, and use them. When a setting is changed for a form and it equals to the global setting, it will use the global setting _now_ and in _the future_ **untill they differ** from each other and only then the form will use it's own setting.

</details>

<details>

<summary>Is it compatible with MailChimp?</summary>

The `MailChimp Element` makes it possible to integrate your form(s) with MailChimp service.

</details>

<details>

<summary>Is it compatible with MyMail (Mailster) plugin?</summary>

The `MyMail (Mailster)` integration makes it possible to integrate your form(s) with MyMail (Mailster) plugin.

</details>

<details>

<summary>How to retrieve the Contact Entry ID in my email?</summary>

You can use the tag `{contact_entry_id}` to retrieve the created contact entry ID in your email. Of course you must have the option to create a contact entry enabled in order for this tag to work properly.

</details>

<details>

<summary>Can I import options for a dropdown from a CSV file?</summary>

Yes, dropdowns, radio buttons and checkboxes can retrieve options based on a selected CSV file that you uploaded in the media library of your wordpress site. It can contain the option `Label` in the first column and the option `Value` in the second column.

</details>

<details>

<summary>How can I use address autocomplete/search feature?</summary>

Please read the [Address Auto Complete](../features/advanced/address-lookup-auto-complete.md) guide on how to use this feature.

</details>

<details>

<summary>Is the plugin Multi-site compatible?</summary>

Yes, but the Contact Entries, Forms and Form settings will be saved individually per site and not on a global level.

</details>

<details>

<summary>Why does the submit button not do anything, or why does it reload the page?</summary>

This is normally due to either a **Theme** or **Plugin** having an JavaScript trigger on the submit button element of Super Forms. You can try and find out what plugin is causing this issue by disabling them one by one. You can also do the same thing with your Theme to see if the issue is caused by your theme. You can then contact the author of the Plugin or Theme to ask if they could look at this issue.

</details>

<details>

<summary>How to get the post or page author where the form was submitted from?</summary>

Add a `Hidden` field and set it's **Default value** to `{post_author_id}` or `{post_author_email}` depending on your needs. You can find a full list of [predefined tags](../features/advanced/tags-system.md#predefined-tags-that-are-useful) here.

</details>

<details>

<summary>Why are my conditional variable values on my hidden field not working?</summary>

You can check the output of any hidden field value by adding a **HTML element** to your form with HTML set like this:

{% code overflow="wrap" %}
```html
My hidden field: {replace_with_field_name}
```
{% endcode %}

If the value is empty then you must recheck your Conditional variable logic. In most cases the Conditional variable logic was set incorrectly.

</details>

<details>

<summary>Can I customize the layout to include collapse groups?</summary>

Depending on your use case you can either add an `Accordion` element or `TABs` element. Alternatively you can use `Columns` with [Conditional logic](../features/advanced/conditional-logic.md) defined to display or hide the column and it's contents based on user selection.

</details>

<details>

<summary>Is it translation ready / translatable?</summary>

Yes, Super Forms is fully translation ready. You can translate the back-end with translation files, or use for instance a plugin like [Loco Translate](https://wordpress.org/plugins/loco-translate/).

Super Forms comes with a build-in translation method. You can translate all your form elements, and form settings via the `Translation` TAB on the builder page.

</details>

<details>

<summary>I disabled autocompletion on a field but it's not working</summary>

Some browsers will simply ignore the `autocomplete` attribute. There are a couple of solutions which might help you:

* For most browsers the simplest way to solve this would be to change the field name to a random string e.g `xY2a9z` instead of a normal name e.g `address`
* For Safari browsers the best way to make autocompletion work is to make sure your field name contains the word `search`. So if you have a field named `address` and you wish to disable autocompletion make sure to rename it to either `search_address` (as long as it contains the word `search`).
* If the above methods both do not work, you might also need to remove the `Placeholder` for the field so that it doesn't contain any reference name to any possible autocompletion. For example, if your placeholder contains `Enter your address` then the word `address` might trigger autocompletion in a given browser.

</details>

<details>

<summary>I am getting an error while updating the plugin</summary>

If you are getting the following error while trying to update Super Forms:

`Unable to rename the update to match the existing directory.`

It is always a permissions problem on your server. In any case you should contact your host about this so they can correctly set the permissions for your WordPress installation.

</details>
