---
description: >-
  Using the default WordPress "Lost password" form or creating your own custom
  "Lost password" form to let users reset their password.
---

# Custom lost password form for WordPress

{% hint style="info" %}
This article explains how to link your custom [Login form](custom-login-form-for-wordpress.md) with the default WordPress **Lost password form**, and how you can configure and setup your own custom **Lost password form** for your WordPress website. You may also be interested in reading how setup a [Login form](custom-login-form-for-wordpress.md) and or [Registration form](../../quick-start/registration.md).
{% endhint %}

### Using the default WordPress lost password form

By default WordPress already has a Lost password form that (on a default installation) can be accessed via the below URL which should look like this:

<pre><code><strong>https://domain.com/wp-login.php?action=lostpassword
</strong></code></pre>

<div align="left"><figure><img src="../../.gitbook/assets/image (71).png" alt="The default WordPress reset/lost password form."><figcaption><p>The default WordPress reset/lost password form.</p></figcaption></figure></div>

#### Changing the "Lost password" link on your login form

This works great for most use cases so if you are OK with having this as your "Lost password" form, then you can edit your custom [Login form](custom-login-form-for-wordpress.md) and make sure you point the "Lost password" link to the correct URL as shown below.

<figure><img src="../../.gitbook/assets/image (30).png" alt="Changing the Lost password link to point to the default Lost password form of WordPress"><figcaption><p>Changing the Lost password link to point to the default Lost password form of WordPress</p></figcaption></figure>

#### Using a Button to act as the "Lost password" link

{% hint style="warning" %}
If you wish to have a **Button element** to act as your "Lost password" link then you can simply add a Button element and define it to act as a regular link instead of submitting the form. Note that when you go this route, you will have to add another Button element that acts as a regular "Submit" as shown in the images below.
{% endhint %}

<div align="left"><figure><img src="../../.gitbook/assets/image (65).png" alt="Adding a Button to your form."><figcaption><p>Adding a Button to your form.</p></figcaption></figure></div>

<div align="left"><figure><img src="../../.gitbook/assets/image (56).png" alt="Adding a custom Button to act as the &#x22;Lost password&#x22; link."><figcaption><p>Adding a custom Button to act as the "Lost password" link.</p></figcaption></figure></div>

<div align="left"><figure><img src="../../.gitbook/assets/image (50).png" alt="Adding a custom Login button that acts as the &#x22;Submit form&#x22; button."><figcaption><p>Adding a custom Login button that acts as the "Submit form" button.</p></figcaption></figure></div>

### Creating your custom "Lost password" form

{% hint style="success" %}
A demo form is available under **Super Forms > Demos** named "Lost Password Form" which should help to quickly get a working reset password form up and running.&#x20;
{% endhint %}

If you prefer using a custom "Lost password" form over the default WordPress "Lost password" form, you can edit your form and navigate to **Form Settings > Register & Login**. From the **Actions** option choose **Reset password (lost password)**. You should now see all the settings that relate to resetting a password. Configure them as you see fit.

{% hint style="danger" %}
Make sure the **Login page URL** points to the URL where your custom [Login form](custom-login-form-for-wordpress.md) is displayed. Also make sure that your form has a field named `user_email`, which is where the user will receive the "Lost Password E-mail" at.
{% endhint %}

<div align="left"><figure><img src="../../.gitbook/assets/image (53).png" alt="Configuration options for your custom WordPress Lost Password form."><figcaption><p>Configuration options for your custom WordPress Lost Password form.</p></figcaption></figure></div>

