---
description: >-
  Updating a currently logged in WordPress user through a form or register as
  new if the user is not currently logged in.
---

# Update current logged in user

{% hint style="info" %}
This article explains how to setup a custom form where a logged in user can edit their user data, and where (optionally) logged out users can register a new account. If you are just looking for a way to register new users please read the [Registration form](custom-registration-form-for-wordpress.md) article.
{% endhint %}

### Updating current logged in user

Let's assume you have a single form where you want your users to be able to edit their data, and (optionally) if the user is logged out, you want them to be able to register a new account.

To do this, first edit your form and navigate to **Form Settings > Register & Login**. From the **Actions** option choose **Update current logged in user**. You should now see all settings that relate to updating a logged in user (as shown in the image below).

{% hint style="warning" %}
In case you enable the option to **Register new user if user is not logged in** you will want to make sure you first configure all the settings as per the [Registration form](custom-registration-form-for-wordpress.md) article.
{% endhint %}

Now whenever a logged in user submits the form their data would be updated. And when a logged out user tries to submits the form a new account would be created instead.

### Leave the "User role" setting empty

{% hint style="danger" %}
**Important:** in most cases you will want to leave the `User role` option empty when updating existing users, this will make sure that the current user role is left untouched. Always double check these type of settings because they can potentially have a big impact if setup incorrectly.
{% endhint %}

<div align="left"><figure><img src="../../.gitbook/assets/image (48).png" alt="Updating a currently logged in user or register a new one if logged out via a custom WordPress form."><figcaption><p>Updating a currently logged in user or register a new one if logged out via a custom WordPress form.</p></figcaption></figure></div>
