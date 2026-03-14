---
description: >-
  How to configure E-mail confirmations for your WordPress forms (form
  submissions).
---

# Confirmations emails

{% hint style="info" %}
In case you are having problems with email delivery, read the [Email delivery problems](../../common-problems/index/email-delivery-problems/) guide on possible solutions.
{% endhint %}

{% hint style="info" %}
If you just want to know how to edit/change the recipient of your emails, you can skip to the [Email recipient(s)](confirmations-emails.md#email-recipient-s) section below.
{% endhint %}

### About

There are two types of emails sent after a form is being submitted. One to the **Admin** (site owner) also known as `Admin E-mail` and one to the **User** (the person who fills out the form) also known as `Confirmation E-mail`.

Typically you would want to sent a confirmation email to the user who fills out the form so they know it was successfully submitted. The actual form data (all information and details) would typically be sent to the Admin (site owner) or a [specific department](../../tutorials/sending-emails-to-specific-department-for-wordpress-contact-forms.md) to process the inquiry.

Apart from sending an email, the WordPress form will also create a so called `Contact Entry` with all the data which you can view via **Super Forms > Contact Entries** via your WordPress menu.

Both emails can have different body contents and both can be enabled or disabled depending on your use case via the Form Settings as shown in the below two pictures.

<div align="left"><figure><img src="../../.gitbook/assets/image (7).png" alt="Enabling Admin E-mail for your WordPress form"><figcaption><p>Enabling Admin E-mail for your WordPress form</p></figcaption></figure></div>

<div align="left"><figure><img src="../../.gitbook/assets/image (8).png" alt="Enabling Confirmation E-mail for your WordPress form"><figcaption><p>Enabling Confirmation E-mail for your WordPress form</p></figcaption></figure></div>

### Email recipient(s)&#x20;

The Confirmation E-mail will be sent to the email address entered by the user in your form. By default the tag `{email}` is defined. So make sure your form has an E-mail field named `email` in order for this to work. By default the `E-mail address` field has the name `email` when added, so it should work out of the box unless you re-named it to something different. In that case you can update the {tag} inside the setting or re-name the field back to `email`.

By default the form will sent admin emails to the administrative email address of your WordPress site via the tag `{option_admin_email}`.

You can find this email address via the WordPress menu **Settings > General > Administration Email Address** as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (9).png" alt="Viewing or changing your WordPress administration email address"><figcaption><p>Viewing or changing your WordPress administration email address</p></figcaption></figure></div>

If you wish to sent the emails to a different address, you can change it via one of the following methods:

* [Editing the individual Form Settings](confirmations-emails.md#editing-the-individual-form-settings) (**recommended**)
* [Using Global Overriding](confirmations-emails.md#using-global-overriding) (if you want to use the same email address for all forms)
* [Dynamically based on user selection/input](confirmations-emails.md#dynamically-based-on-user-selection-input) (e.g. sent to specific department)

#### Editing the individual Form Settings

Edit your form, open the `Form Settings` panel. Choose either Admin E-mail from the dropdown, and change the `Send email to:` setting to the desired E-mail address. In case you have problems with email delivery you can read the [Email delivery problems](../../common-problems/index/email-delivery-problems/) guide on common causes and how to fix them.

#### Using Global Overriding

You can override your form settings on a global level via `Super Forms > Settings > Global Overriding`. More information about global overriding can be read in the [First Time Setup](../../quick-start/first-time-setup.md#global-overriding) guide.

#### Dynamically based on user selection/input

You can read more about this method in the [Sending emails to specific department](../../tutorials/sending-emails-to-specific-department-for-wordpress-contact-forms.md) guide.

### Email body

By default both will contain all data that was filled out on the form.

Each field element has options to define if the data should be included in the Admin and/or Confirmation emails. By default all fields are included. Keep in mind that if you define it as being excluded the data will not be displayed inside the email body (content) unless you explicitly use[ {tags}](../advanced/tags-system.md) to retrieve the field value.

{% hint style="warning" %}
Fields that are conditionally hidden (see [Conditional Logic](../advanced/conditional-logic.md) section) will be excluded completely and can not be retrieved even when using {tags}.
{% endhint %}

Your email body content will loop over all fields and display it inside a table in your **Body content** via the tag `{loop_fields}` (which retrieves what is defined under **Field Loop** setting).

The **Field Loop** acts as a row for each field by retrieving the field **Label** using the tag`{loop_label}` and the field **Value** using the tag `{loop_value}` as shown below:

<div align="left"><figure><img src="../../.gitbook/assets/image (10).png" alt="WordPress form email body content"><figcaption><p>WordPress form email body content</p></figcaption></figure></div>

Of course you are not required to use this as your content, and you are free to create your custom HTML E-mail. You can use field [{tags}](../advanced/tags-system.md) to retrieve any of your form data in your email body. You may also use [if statements](../advanced/if-statements.md) to conditionally display additional information or text based on a field value or loop over [dynamic columns](../../elements/layout-elements/column-grid.md#dynamic-add-more) (allows users add more fields dynamically) with the use of [foreach loops](../advanced/foreach-loops.md).

