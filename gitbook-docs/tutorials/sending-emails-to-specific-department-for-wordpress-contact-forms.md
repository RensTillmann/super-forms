---
description: >-
  Conditionally sending an email to a specific department E-mail address based
  on the user selected option from a dropdown on your WordPress form.
---

# Sending emails to specific department for WordPress contact forms



{% @supademo/embed demoId="QrM6ME2cWH_EP2X-JIrQF" url="https://app.supademo.com/demo/QrM6ME2cWH_EP2X-JIrQF" %}

Let's assume you have multiple departments within your company that handle different inquiries.

For instance, you might have a **Sales** department sales<img src="../.gitbook/assets/image (29).png" alt="@ sign" data-size="line">company.com and a **Product support** department support<img src="../.gitbook/assets/image (29).png" alt="@ sign" data-size="line">company.com.

When a user fills out the form you might ask the user if the inquiry is about sales or support.

Based on the selection you can then send it to the corresponding E-mail address.

There are multiple ways to accomplish this but the easiest way to do it is to add a dropdown named "department" and adding 2 items like so:

<div align="left" data-full-width="false"><figure><img src="../.gitbook/assets/define-department-email-addresses-for-dropdown-element.png" alt="Define each department email address for the dropdown element"><figcaption><p>Define each department email address for the dropdown element.</p></figcaption></figure></div>

Now you can set the "Send email to:" setting to retrieve the value from the dropdown by calling the **{department}** tag. This will either have the value "sales<img src="../.gitbook/assets/image (29).png" alt="@ sign" data-size="line">company.com" or "support<img src="../.gitbook/assets/image (29).png" alt="@ sign" data-size="line">company.com".

<div align="left" data-full-width="false"><figure><img src="../.gitbook/assets/setting-email-to-header.png" alt="Set the dropdown field name tag as your E-mail to header for your Admin email."><figcaption><p>Set the dropdown field name tag as your E-mail to header for your Admin email.</p></figcaption></figure></div>

The above example works just fine, however it is good practice to not expose an E-mail address directly into the source code of a web page (to prevent spam, because of bots being able to index it).

This is where the build in **Secrets** system comes into play.

You can configure secrets like below. This will allow you to set the dropdown values to be **{@sales\_email}** and **{@support\_email}** which will not expose the E-mail address in the source code. But by calling **{department}** tag it will replace the underlaying value with the actual E-mail address server side.

First go to the "Secrets" tab, and configure your secrets like so:

<div align="left" data-full-width="false"><figure><img src="../.gitbook/assets/securely-configure-email-addresses-secrets-for-email-departments.png" alt="Define email addresses under secrets for secure use"><figcaption><p>Define email addresses under secrets for secure use.</p></figcaption></figure></div>

After configuring the secrets, you can edit your dropdown and set the values to the secret tags like so:

<div align="left" data-full-width="false"><figure><img src="../.gitbook/assets/setting-secret-tags-as-values-for-each-department-dropdown-items.png" alt="Define secret tags as dropdown values for the department dropdown"><figcaption><p>Define secret tags as dropdown values for the department dropdown.</p></figcaption></figure></div>

Demonstration on how to configure this from scratch:

<div align="left" data-full-width="false"><figure><img src="../.gitbook/assets/demonstration-on-how-to-define-secrets-for-a-dropdown-element.gif" alt="Demonstration on how to define secrets for a dropdown element."><figcaption><p>Demonstration on how to define secrets for a dropdown element.</p></figcaption></figure></div>
