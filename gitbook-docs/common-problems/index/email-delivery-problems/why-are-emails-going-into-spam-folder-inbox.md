# Why are emails going into spam folder/inbox?

It is important to note that emails are not marked as spam by Super Forms. Instead they are marked as spam by internet spam protection measures. Because spam protection rules are constantly getting stricter, a form that previously worked can sometimes stop working out of the blue, even when nothing was changed on your website.

One way to solve the problem is to let your site send emails over SMTP rather than the built-in WordPress mail service. E-mails send over SMTP "look" more legitimate and will help your emails pass spam filters.

**Other things you should check on your form settings:**

* The `From` address must match the domain of your website e.g. noreply@`mydomain.com`
* Your `To` address should never match your `From` address because it can trigger spam deletion
* If you specified a `Reply-To` address, it should never match your `To` address
* Even though you can add multiple recipients in your `To` setting, it is recommended to use `CC` and `BCC` for multiple recipients
* Minimize the links you include. E-mail messages with a ton of links might trigger spam filters

<figure><img src="../../../.gitbook/assets/super-forms-set-correct-from-email-headers.png" alt="Set the correct From header for your WordPress emails to match your current domain name"><figcaption><p>Set the correct From header for your WordPress emails to match your current domain name</p></figcaption></figure>
