# FAQ

**Commonly asked questions:**
- [How can I make all fields to be required?](#how-can-i-make-all-fields-to-be-required)
- [Will data be lost when updating to a newer version?](#will-data-be-lost-when-updating-to-a-newer-version-of-super-forms)
- [How to redirect a user after submitting the form?](#how-to-redirect-a-user-after-submitting-the-form)
- [Is it compatible with Visual Composer?](#is-it-compatible-with-visual-composer)
- [Where can I change the error message of a field?](#where-can-i-change-the-error-message-of-a-field)
- [Where can I change the form font styles?](#where-can-i-change-the-form-font-styles)
- [How to make the file upload mandatory?](#how-to-make-the-file-upload-mandatory)
- [Can I do price calculations based on user selection?](#can-i-do-price-calculations-based-on-user-selection)
- [Where can I add a tooltip when the user hovers over the field?](#where-can-i-add-a-tooltip-when-the-user-hovers-over-the-field)
- [I changed the settings but it doesn't seem to affect the form?](#i-changed-the-settings-but-it-doesn39t-seem-to-affect-the-form)
- [Is it compatible with MailChimp?](#is-it-compatible-with-mailchimp)
- [Is it compatible with MyMail (Mailster) plugin?](#is-it-compatible-with-mymail-mailster-plugin)

**Technical related questions:**
- [Why is my form not sending emails?](#why-is-my-form-not-sending-emails)
- [Why do I get an error message when uploading a file?](#why-do-i-get-an-error-message-when-uploading-a-file)
- [How to retrieve the Contact Entry ID in my email?](#how-to-retrieve-the-contact-entry-id-in-my-email)
- [Can I import options for a dropdown from a CSV file?](#can-i-import-options-for-a-dropdown-from-a-csv-file)
- [How can I use address autocomplete/search feature?](#how-can-i-use-address-autocompletesearch-feature)
- [Is the plugin Multi-site compatible?](#is-the-plugin-multi-site-compatible)
- [Why does the page refresh and doesn't do anything if I click on the submit button?](#why-does-the-page-refresh-and-doesn39t-do-anything-if-i-click-on-the-submit-button)
- [I want to see who the author of the post or page is where the form was sent from, how can I accomplish this?](#i-want-to-see-who-the-author-of-the-post-or-page-is-where-the-form-was-sent-from-how-can-i-accomplish-this)
- [Why are my conditional variable values on my hidden field not working?](#why-are-my-conditional-variable-values-on-my-hidden-field-not-working)
- [Can I customize the layout to include collapse groups?](#can-i-customize-the-layout-to-include-collapse-groups)
- [Is it translation ready / translatable?](#is-it-translation-ready-translatable)


#### How can I make all fields to be required?
This can be done per field individually because each field can have different type of [Validations](special-validation). To do this you can edit the field you wish and select the required validation under `General` > `Special Validation`, there you have several option to choose from.

#### Will data be lost when updating to a newer version of Super Forms?
No, all data will remain and will **not** be deleted. Even if you would delete Super Forms through FTP.

#### How to redirect a user after submitting the form?
When editing the form you can enable redirect under: `Form Settings (panel)` > `Form Settings (TAB)` > `Form redirect option`.

When using **custom URL redirect** you can retrieve form values with [{tags}](tags-system) to parse them in your GET request like so:
	
	http://domain.com/page/?name={first_name}+{last_name}&age={birthdate}

#### Is it compatible with Visual Composer?
Super Forms has it's own Visual Composer (JS Composer) element.

With this element you can simply **Drag & Drop** any form at a specific location in your page.
After you dropped the element you can choose which form it should load simply with the use of a dropdown that will list all the forms you have created.

The Super Forms [shortcode] can also be inserted into a Visual Composer **HTML element**. This makes it easy to insert it into any area within your Visual Composer pages.

#### Where can I change the error message of a field?
You can change the error message per field by editing the element and changing the `Error Message` option under the `General` TAB.

#### Where can I change the form font styles?
You can change the font styles when editing a form under `Form Settings` > `Font Styles`.

#### How to make the file upload mandatory?
When editing the file upload element under `Advanced` you can set a `Max` and `Min` value. If you set the Minimum to 1 or higher the field will become required.

#### Can I do price calculations based on user selection?
Yes, you can do this with the [Calculator Add-on](https://codecanyon.net/item/super-forms-calculator/16045945) for Super Forms.

#### Where can I add a tooltip when the user hovers over the field?
You can add a tooltip when editing the element under `General` > `Tooltip text`

#### I changed the settings but it doesn't seem to affect the form?
Please make change to the form it self, and not via the global settings under `Super Forms` > `Settings` from the menu. Each form upon creating will grab the global settings, and use them. When a setting is changed for a form and it equals to the global setting, it will use the global setting _now_ and in _the future_ **untill they differ** from each other and only then the form will use it's own setting.

#### Is it compatible with MailChimp?
The [MailChimp Add-on](https://codecanyon.net/item/super-forms-mailchimp-addon/14126404) for Super Forms makes it possible to integrate your form(s) with MailChimp service.

#### Is it compatible with MyMail (Mailster) plugin?
The [MyMail (Mailster) Add-on](https://codecanyon.net/item/super-forms-mailster-addon/19735910) for Super Forms makes it possible to integrate your form(s) with MyMail (Mailster) plugin.



#### Why is my form not sending emails?
First always check your **Spam folder**, your mail server might mark it as spam.

Next thing to check is to see if WordPress is sending E-mails when you use the **Lost password** form by WordPress itself.
You can test this on the login page of the dashboard by clicking on **Lost password?**. If you do not receive any E-mails  it could be that your hosting either has PHP `mail()` disabled, or something else isn't configured correctly on your server. In that case contact your hosting company.

If you do receive an E-mail with the lost password form, then it is most likely that your `From: header` isn't set correctly for your form. Make sure that on the form you have build, the setting is set to have your domain name as From: header like so: no-reply@`mydomain.com`. Some mail servers do not allow to use a From header different from the domain name it's being send from.

If you are still unable to receive E-mails after the above steps, check if any other plugin is being used that overrides WordPress `wp_mail()` functionality. If you are using **SMTP plugin** or settings, recheck if they are setup correctly.

If after all the above steps you think everything is correctly setup, you can [Contact support](support).

#### Why do I get an error message when uploading a file?
If you are unable to upload files via your form the first thing you should try is to check if the server returns a 403 error (Forbidden) on the following URL:
`http://yourdomain.com/wp-content/plugins/super-forms/uploads/php/`

If it returns a **403 error**, please contact your hosting company to let them fix this issue.
It should return a blank page in order for the file upload field to work correctly. 

Check if uploading a small file works. If this doesn't work, it is most likely due to incorrect file permissions on the plugin folders, contact your hosting company to let them look at the file permissions.

If you are able to upload smaller files, it is most likely due to your PHP settings regarding file uploads. In this case you can adjust the `post_max_size and`, `memory_limit` and `upload_max_filesize` values in your **php.ini** file or ask your hosting company to increase these values to suite your needs. Remember the following rules when changing these values:
1. To upload large files, `post_max_size` value must be larger than `upload_max_filesize`.
2. `memory_limit` should be larger than `post_max_size` 

#### How to retrieve the Contact Entry ID in my email?
You can use the tag `{contact_entry_id}` to retrieve the created contact entry ID in your email.
Of course you must have the option to create a contact entry enabled in order for this tag to work properly.

#### Can I import options for a dropdown from a CSV file?
Yes, dropdowns, radio buttons and checkboxes can retrieve options based on a selected CSV file that you uploaded in the media library of your wordpress site. It can contain the option `Label` in the first column and the option `Value` in the second column.

#### How can I use address autocomplete/search feature?
Please read the [Address Auto Complete](address-auto-complete) section on how to use this feature.

#### Is the plugin Multi-site compatible?
Yes, but the Contact Entries, Forms and Form settings will be saved individually per site and not on a global level.

#### Why does the page refresh and doesn't do anything if I click on the submit button?
This is normally due to either a **Theme** or **Plugin** having an JavaScript trigger on the submit button element of Super Forms. You can try and find out what plugin is causing this issue by disabling them one by one. You can also do the same thing with your Theme to see if the issue because of your theme. You can then contact the author of the Plugin or Theme to ask if they could look at this issue. If you think something else is wrong then you are free to contact support.

#### I want to see who the author of the post or page is where the form was sent from, how can I accomplish this?
You can add a [Text field](text-field) and put it inside a [Column](columns) and make the column invisible. Make sure that the **Default value** of the [Text field](text-field) is set to `{post_author}`

#### Why are my conditional variable values on my hidden field not working?
You can check the output of any hidden field value by adding a **HTML element** to your form with HTML set like this: `My hidden field: {replace_with_field_name}`
If it is empty then you must recheck your conditions, in most cases the [Conditional logic](conditional-logic) was set incorrectly. If you still do not succeed you can contact support.

#### Can i customize the layout to include collapse groups?
Yes, this can be accomplished by adding fields into a [Column](columns) and append [Conditional logic](conditional-logic) on that column.

#### Is it translation ready / translatable?
Yes, Super Forms is fully translation ready. You can translate the back-end with translation files, or use for instance a plugin like [Loco Translate](https://wordpress.org/plugins/loco-translate/).

Regarding the front-end, you can duplicate your forms and rename your fields accordingly.

_In the near future Super Forms will have a new feature that will let you translate your forms in a more user friendly way, so you no longer would have to duplicate your form in order to translate them._