# FAQ

**Commonly asked questions:**

<details>
  <summary id="1">
      How can I make all fields to be required?
  </summary>

  This can be done per field individually because each field can have different type of [Validations](validation). To do this you can edit the field you wish and select the required validation under `General` > `Validation`, there you have several option to choose from.
</details>

<details>
  <summary id="2">
      Why is my form not sending emails?
  </summary>

  First always check your **Spam folder**, your mail server might mark it as spam.

  Next thing to check is to see if WordPress is sending E-mails when you use the **Lost password** form by WordPress itself.
  You can test this on the login page of the dashboard by clicking on **Lost password?**. If you do not receive any E-mails  it could be that your hosting either has PHP `mail()` disabled, or something else isn't configured correctly on your server. In that case contact your hosting company.

  If you do receive an E-mail with the lost password form, then it is most likely that your `From: header` isn't set correctly for your form. Make sure that on the form you have build, the setting is set to have your domain name as From: header like so: no-reply@`mydomain.com`. Some mail servers do not allow to use a From header different from the domain name it's being send from.

  If you are still unable to receive E-mails after the above steps, check if any other plugin is being used that overrides WordPress `wp_mail()` functionality. If you are using **SMTP plugin** or settings, recheck if they are setup correctly.

  If after all the above steps you think everything is correctly setup, you can [Contact support](support).
</details>

<details>
  <summary id="3">
      Why are emails going into spam folder/inbox?
  </summary>

  It is important to note that emails are not marked as spam by Super Forms. Instead they are marked as spam by interent spam protection measures.
  Because spam protection rules are constantly getting stricter, a form that previously worked can sometimes stop working out of the blue, even when nothing was changed on your website.

  One way to solve the problem is to let your site send emails over SMTP rather than the built-in WordPress mail service.
  E-mails send over SMTP "look" more legitimate and will help your emails pass spam filters.

  **Other things you should check are:**

  - The `From` address must match the domain of your website e.g: noreply@`mydomain.com`
  - Your `To` address should never match your `From` address because it can trigger spam deletion
  - If you specified a `Reply-To` address, it should never match your `To` address
  - Even though you can add multiple recipients in your `To` setting, it is recommended to use `CC` and `BCC` for multiple recipients
  - Minimize the links you include. E-mail messages with a ton of links might trigger spam filters
</details>

<details>
  <summary id="4">
      Why do I get an error message when uploading a file?
  </summary>

  If you are unable to upload files via your form the first thing you should try is to check if the server returns a 403 error (Forbidden) on the following URL:
  `http://yourdomain.com/wp-content/plugins/super-forms/uploads/php/`

  If it returns a **403 error**, please contact your hosting company to let them fix this issue.
  It should return a blank page in order for the file upload field to work correctly.

  Check if uploading a small file works. If this doesn't work, it is most likely due to incorrect file permissions on the plugin folders, contact your hosting company to let them look at the file permissions.

  If you are able to upload smaller files, it is most likely due to your PHP settings regarding file uploads. In this case you can adjust the `post_max_size and`, `memory_limit` and `upload_max_filesize` values in your **php.ini** file or ask your hosting company to increase these values to suite your needs. Remember the following rules when changing these values:

  1. To upload large files, `post_max_size` value must be larger than `upload_max_filesize`.
  2. `memory_limit` should be larger than `post_max_size`
</details>

<details>
  <summary id="5">
      Will data be lost when updating to a newer version of Super Forms?
  </summary>

  No, all data will remain and will **not** be deleted. Even if you would delete Super Forms through FTP.
</details>

<details>
  <summary id="6">
      How to redirect a user after submitting the form?
  </summary>

  When editing the form you can enable redirect under: `Form Settings (panel)` > `Form Settings (TAB)` > `Form redirect option`.

  When using **custom URL redirect** you can retrieve form values with [{tags}](tags-system) to parse them in your GET request like so:

  <http://domain.com/page/?name={first_name}+{last_name}&age={birthdate}>
</details>

<details>
  <summary id="7">
      Is it compatible with Visual Composer?
  </summary>

  Super Forms has it's own Visual Composer (JS Composer) element.

  With this element you can simply **Drag & Drop** any form at a specific location in your page.
  After you dropped the element you can choose which form it should load simply with the use of a dropdown that will list all the forms you have created.

  The Super Forms [shortcode] can also be inserted into a Visual Composer **HTML element**. This makes it easy to insert it into any area within your Visual Composer pages.
</details>

<details>
  <summary id="8">
      Is it compatible with Elementor?
  </summary>

  Super Forms has it's own widget inside Elementor.

  With this element you can simply **Drag & Drop** any form at a specific location in your page.
  On this widget you can easily choose which form to load.
</details>

<details>
  <summary id="9">
      Where can I change the error message of a field?
  </summary>

  You can change the error message per field by editing the element and changing the `Error Message` option under the `General` TAB.
</details>

<details>
  <summary id="10">
      Where can I change the form font styles?
  </summary>
  
  You can change the font styles when editing a form under `Form Settings` > `Font Styles`.
</details>

<details>
  <summary id="11">
      How to make the file upload required/mandatory?
  </summary>

  When editing the file upload element under `Advanced` you can set a `Max` and `Min` value. If you set the Minimum to 1 or higher the field will become required.
</details>

<details>
  <summary id="12">
      Can I do price calculations based on user selection?
  </summary>

  Yes, you can do this with the [Calculator Element]
</details>

<details>
  <summary id="13">
      Where can I add a tooltip when the user hovers over the field?
  </summary>

  You can add a tooltip when editing the element under `General` > `Tooltip text`
</details>

<details>
  <summary id="14">
      I changed the settings but it doesn't seem to affect the form?
  </summary>

  Please make change to the form it self, and not via the global settings under `Super Forms` > `Settings` from the menu. Each form upon creating will grab the global settings, and use them. When a setting is changed for a form and it equals to the global setting, it will use the global setting _now_ and in _the future_ **untill they differ** from each other and only then the form will use it's own setting.
</details>

<details>
  <summary id="15">
      Is it compatible with MailChimp?
  </summary>

  The `MailChimp Element` makes it possible to integrate your form(s) with MailChimp service.
</details>

<details>
  <summary id="16">
      Is it compatible with MyMail (Mailster) plugin?
  </summary>

  The `MyMail (Mailster)` integration makes it possible to integrate your form(s) with MyMail (Mailster) plugin.
</details>

<details>
  <summary id="17">
      How to retrieve the Contact Entry ID in my email?
  </summary>

  You can use the tag `{contact_entry_id}` to retrieve the created contact entry ID in your email.
  Of course you must have the option to create a contact entry enabled in order for this tag to work properly.
</details>

<details>
  <summary id="18">
      Can I import options for a dropdown from a CSV file?
  </summary>

  Yes, dropdowns, radio buttons and checkboxes can retrieve options based on a selected CSV file that you uploaded in the media library of your wordpress site. It can contain the option `Label` in the first column and the option `Value` in the second column.
</details>

<details>
  <summary id="19">
      How can I use address autocomplete/search feature?
  </summary>

  Please read the [Address Auto Complete](address-auto-complete) section on how to use this feature.
</details>

<details>
  <summary id="20">
      Is the plugin Multi-site compatible?
  </summary>

  Yes, but the Contact Entries, Forms and Form settings will be saved individually per site and not on a global level.
</details>

<details>
  <summary id="21">
      Why does the page refresh and doesn't do anything if I click on the submit button?
  </summary>

  This is normally due to either a **Theme** or **Plugin** having an JavaScript trigger on the submit button element of Super Forms. You can try and find out what plugin is causing this issue by disabling them one by one. You can also do the same thing with your Theme to see if the issue because of your theme. You can then contact the author of the Plugin or Theme to ask if they could look at this issue. If you think something else is wrong then you are free to contact support.

</details>

<details>
  <summary id="22">
      I want to see who the author of the post or page is where the form was sent from, how can I accomplish this?
  </summary>

  You can add a [Text field](text-field) and put it inside a [Column](columns) and make the column invisible. Make sure that the **Default value** of the [Text field](text-field) is set to `{post_author}`
</details>

<details>
  <summary id="23">
      Why are my conditional variable values on my hidden field not working?
  </summary>

  You can check the output of any hidden field value by adding a **HTML element** to your form with HTML set like this: `My hidden field: {replace_with_field_name}`
  If it is empty then you must recheck your conditions, in most cases the [Conditional logic](conditional-logic) was set incorrectly. If you still do not succeed you can contact support.
</details>

<details>
  <summary id="24">
      Can I customize the layout to include collapse groups?
  </summary>

  Yes, this can be accomplished by adding fields into a [Column](columns) and append [Conditional logic](conditional-logic) on that column.
</details>

<details>
  <summary id="25">
      Is it translation ready / translatable?
  </summary>

  Yes, Super Forms is fully translation ready. You can translate the back-end with translation files, or use for instance a plugin like [Loco Translate](https://wordpress.org/plugins/loco-translate/).

  Regarding the front-end, you can duplicate your forms and rename your fields accordingly.

  _In the near future Super Forms will have a new feature that will let you translate your forms in a more user friendly way, so you no longer would have to duplicate your form in order to translate them._
</details>

<details>
  <summary id="26">
      I disabled autocompletion on a field but it's not working
  </summary>

  Some browsers will simply ignore the `autocomplete` attribute. There are a couple of solutions which might help you:

  - For most browsers the simplest way to solve this would be to change the field name to a random string e.g `xY2a9z` instead of a normal name e.g `address`
  - For Safari browsers the best way to make autocompletion work is to make sure your field name contains the word `search`. So if you have a field named `address` and you wish to disable autocompletion make sure to rename it to either `search_address` (as long as it contains the word `search`).
  - If the above methods both do not work, you might also need to remove the `Placeholder` for the field so that it doesn't contain any reference name to any possible autocompletion. For example, if your placeholder contains `Enter your address` then the word `address` might trigger autocompletion in a given browser.
</details>

<details>
  <summary id="27">
      I am getting an error while updating the plugin
  </summary>

  If you are getting the following error while trying to update Super Forms:

  `Unable to rename the update to match the existing directory.`

  It is always a permissions problem on your server. In any case you should contact your host about this so they can correctly set the permissions for your WordPress installation.
</details>

