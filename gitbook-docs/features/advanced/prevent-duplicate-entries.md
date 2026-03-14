---
description: >-
  Prevent duplicate form submissions based on email address or other form values
  entered in your form on WordPress.
---

# Prevent duplicate entries

{% hint style="info" %}
If you are looking for a way to lock specific user roles or to password protect your form read [this article](password-protect.md) instead. If you want to allow users to only submit a form a maximum of X times read [this article](lock-and-hide-form.md) instead..
{% endhint %}

For this to work you must have the option to save contact entries enabled. This will be used to compare any previous form submission and decide if the user is allowed to submit the form with the entered data.

First edit your form and open up the "Form Settings" panel. From there, choose "Form settings" from the dropdown. Make sure that "Save data" is set to "Save as Contact Entry".&#x20;

Scroll down to and enable the "Enable custom entry titles". As it's value enter `{email}` so that the email address the user entered will be used as the title.&#x20;

Now also enable the "Prevent submitting form when entry title already exists". This will make sure that whenever the user submits the form it will first check if a similar entry existists with the same title. If this is the case the user will be displayed the error message as defined.&#x20;

{% hint style="info" %}
By default Super Forms will only compare the title to any entries that where created via the current form. If you want to globally check the title you might want to change that option too.
{% endhint %}

<div align="left"><figure><img src="../../.gitbook/assets/image (94).png" alt="Prevent submitting form when entry title already exists"><figcaption><p>Prevent submitting form when entry title already exists</p></figcaption></figure></div>
