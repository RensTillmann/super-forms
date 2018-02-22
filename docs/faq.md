# FAQ

**Index:**
- [Why is my form not sending emails?](#why-is-my-form-not-sending-emails)
- [How to retrieve the Contact Entry ID in my email?](#how-to-retrieve-the-contact-entry-id-in-my-email)
- [Will data be lost when updating to a newer version?](#will-data-be-lost-when-updating-to-a-newer-version)
- [How to redirect a user after submitting the form?](#how-to-redirect-a-user-after-submitting-the-form)
- [Is it compatible with Visual Composer?](#is-it-compatible-with-visual-composer)
- [Can I import options for a dropdown from a CSV file?](#can-i-import-options-for-a-dropdown-from-a-csv-file)
- [Where can I change the error message of a field?](#where-can-i-change-the-error-message-of-a-field)
- [How can I use address autocomplete/search feature?](#)

### Why is my form not sending emails?

First always check your **Spam folder**, your mail server might mark it as spam.

Next thing to check is to see if WordPress is sending E-mails when you use the **Lost password** form by WordPress itself.
You can test this on the login page of the dashboard by clicking on **Lost password?**. If you do not receive any E-mails  it could be that your hosting either has PHP `mail()` disabled, or something else isn't configured correctly on your server. In that case contact your hosting company.

If you do receive an E-mail with the lost password form, then it is most likely that your `From: header` isn't set correctly for your form. Make sure that on the form you have build, the setting is set to have your domain name as From: header like so: no-reply@`mydomain.com`. Some mail servers do not allow to use a From header different from the domain name it's being send from.

If you are still unable to receive E-mails after the above steps, check if any other plugin is being used that overrides WordPress `wp_mail()` functionality. If you are using **SMTP plugin** or settings, recheck if they are setup correctly.

If after all the above steps you think everything is correctly setup, you can [Contact support](support).


### How to retrieve the Contact Entry ID in my email?

You can use the tag `{contact_entry_id}` to retrieve the created contact entry ID in your email.
Of course you must have the option to create a contact entry enabled in order for this tag to work properly.


### Will data be lost when updating to a newer version?

No, all data will remain and will not be deleted. Even if you would delete Super Forms through FTP.


### How to redirect a user after submitting the form?

When editing the form you can enable redirect under: `Form Settings` > `Form Settings` > `Form redirect option`.

When using **custom URL redirect** you can retrieve form values with **{tags}** to parse them in your GET request like so:
	
	http://domain.com/page/?name={first_name}+{last_name}&age={birthdate}


### Is it compatible with Visual Composer?

**Yes**, Super Forms has it's own Visual Composer (JS Composer) element.

With this element you can simply **drag & drop** any form at the location in your page you wish.
After you dropped the element you can choose which form it should load simply with the use of a dropdown that will list all the forms you have created.

The Super Forms shortcode can also be inserted into a Visual Composer **HTML element**. This makes it easy to insert it into any text area within your Visual Composer pages.


### Can I import options for a dropdown from a CSV file?

Yes, dropdowns, radio and checkboxes can retrieve options based on a selected CSV file that you uploaded in the media library of your wordpress site. It can contain the option Label in the first column and the option value in the second column.


### Where can I change the error message of a field?

You can change the error message per field by editing the element and changing `Error Message` option under the `General` TAB.


### How can I use address autocomplete/search feature?

You can read the [Address Auto Complete](address-auto-complete) section on how to use this feature.
