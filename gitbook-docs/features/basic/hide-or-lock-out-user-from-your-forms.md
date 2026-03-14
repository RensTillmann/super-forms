---
description: >-
  How to hide, password protect or lock out user from submitting a WordPress
  form.
---

# Hide or lock out user from your forms

{% hint style="info" %}
If you are looking for a way to prevent duplicate entries read [this article](../advanced/prevent-duplicate-entries.md) instead. If you want to lock a form after specific amount of submissions read [this article](../advanced/lock-and-hide-form.md). And if you are looking for a way to password protect a form read [this article](../advanced/password-protect.md). And in case you want to just hide the form after it was successfully submitted by the user read [this article](hide-form-after-submitting.md).
{% endhint %}

In this article we will explain what different option you have in regards to hiding, locking and password protecting your WordPress form. With these options you can hide a form, password protect or lock forms for specific users, so that they won't be able to submit the form.

For instance you can hide the form from logged out users or from specific user roles. You can also display a "lockout" message based on these conditions to the user.

### Only allow logged in users to submit a form

If you only want currently logged in users to be able to submit a form, you can enable the **Allow only logged in users** setting. Optionally you can choose to **Hide the form from not logged in users** (those that are logged out). Another options you have is to display a message to those that are logged out. And in case you are not hiding the form from the logged out users, you may also display a message after they tried to submit the form, as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (25).png" alt="Allow only logged in users to submit the form."><figcaption><p>Allow only logged in users to submit the form.</p></figcaption></figure></div>

### Hide form from logged in users

In some cases you might want to hide your form from already logged in users. For instance when you have a [Registration form](../advanced/custom-registration-form-for-wordpress.md), you don't really require a logged in user to see this form, simply because they are already registered. You can do so by enabling the **Hide form from logged in users** settings as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (23).png" alt="Hiding forms from currently logged in users."><figcaption><p>Hiding forms from currently logged in users.</p></figcaption></figure></div>

### Allow only specific user roles to submit the form

To allow only specific user roles from being able to submit the form you can enable the **Allow only specific user roles** setting. Followed by the roles you wish to allow to submit the form. You can select multiple roles by holding the **CTRL** key on your keyboard while **left clicking** the roles as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (33).png" alt="Allow only specific user roles to submt the form."><figcaption><p>Allow only specific user roles to submt the form.</p></figcaption></figure></div>

### Hiding the form from locked out users

In most cases when a user is locked out, you will probably want to hide the form from this user. You can do so by enabling the **Hide form from locked out users** setting, as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (74).png" alt="Hiding the form from a locked out user."><figcaption><p>Hiding the form from a locked out user.</p></figcaption></figure></div>

### Display a message for the locked out user

Whenever a user is locked out based on any of the conditions, you can optionally display a message to the user to inform them.

<div align="left"><figure><img src="../../.gitbook/assets/image (76).png" alt="Display a message or notification regarding the lockout."><figcaption><p>Display a message or notification regarding the lockout.</p></figcaption></figure></div>

### Password protect a form

We have a dedicated guide on how to password protect your forms here: [Advanced > Password protect](../advanced/password-protect.md).
