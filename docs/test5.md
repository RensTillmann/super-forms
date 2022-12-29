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






