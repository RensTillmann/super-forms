---
description: >-
  How to create custom registration form for your WordPress site to register
  users with a specific user role and optionally custom user meta data.
---

# Custom registration form for WordPress

{% hint style="info" %}
This article explains how to setup and configure your custom **Registration form** for WordPress. In most use cases a registration form will work in combination with a Login and Lost password form so you may also be interested on how setup a [Login form](custom-login-form-for-wordpress.md) and or [Lost password](custom-lost-password-form-for-wordpress.md) form.
{% endhint %}

{% hint style="success" %}
A demo form is available under **Super Forms > Demos** named "Register form" which should help to quickly get a working Registration form up and running.&#x20;
{% endhint %}

### Creating a custom WordPress registration form <a href="#creating-a-login-form" id="creating-a-login-form"></a>

{% hint style="danger" %}
**Important:** Your form must contain fields named `user_email` (E-mail address) and optionally a `user_login` (username) field. Note that a Password field exactly named `user_pass` should be added, otherwise a random password will be generated on the fly instead. You are also strongly advised to not store or save the password field since for most use cases this is not desired. However Super Forms does give you the ability to do so. So please double check that your password field is not saved as Contact Entry data, and that you are excluding it from E-mails.
{% endhint %}

First edit your form and navigate to **Form Settings > Register & Login**. From the **Actions** option choose **Register a new user**. You should now see all settings that relate to registering a new user. Make sure to configure all the options that fits your use case.

### Verification of E-mail address

In case you enable the option to send a verification E-mail to the user, you must make sure you added the **Verification Code** element to your login form as described [here](custom-login-form-for-wordpress.md#verification-of-e-mail-address).

### Defining the login page URL

{% hint style="warning" %}
Make sure to define the **Login page URL** so that it points to the URL where the Login form is located, for instance: `https://mydomain.com/login` as shown in the image below. This URL can be retrieved with the tag `{register_login_url}` inside your E-mails if needed.
{% endhint %}

<div align="left"><figure><img src="../../.gitbook/assets/image (79).png" alt="Defining the login page URL for your WordPress site."><figcaption><p>Defining the login page URL for your WordPress site.</p></figcaption></figure></div>

{% hint style="info" %}
Please understand that the status of an account is not the same thing as the verification of the E-mail address. Whenever you have a form that has no verification requirement for the E-mail address, and the status is set to "Active" by default after registration, the user can login instantly after registration.
{% endhint %}

### Hiding the default WordPress toolbar for the user

If you wish to hide the default WordPress toolbar to the user when they are logged in, you can uncheck the setting **Show Toolbar when viewing site (enabled by default).**

<div align="left"><figure><img src="../../.gitbook/assets/image (39).png" alt="Hiding the WordPress toolbar for newly registered users."><figcaption><p>Hiding the WordPress toolbar for newly registered users.</p></figcaption></figure></div>

### **Saving custom user meta data**

If you require to store some custom user meta data, for instance in combination with a custom plugin or perhaps ACF (Advanced Custom Fields) plugin, you can define each user meta data under the setting **Save custom user meta**. Simply put each field and it's corresponding meta key on a new line. For instance, by default WordPress users do not have a "Age" field. If you ask the user for their age (or birthdate) on the registration form, you can map it like so:

```
age|field_6424a30691ebb
```

In the above example **age** is the fieldname in our form, and **field\_6424a30691ebb** is the meta key. If you use a plugin like ACF for custom user profile fields, you can find the meta key for your field under the column "Key" as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (52).png" alt="Mapping registration form fields with meta key to save custom user meta data in WordPress."><figcaption><p>Mapping registration form fields with meta key to save custom user meta data in WordPress.</p></figcaption></figure></div>

### Manually approving registrations

It is also possible to manually approve registrations. To do so, you will want to change the **User login status after registration** from "Active (default)" to the option "Pending". Now whenever a user registers (and optionally verified their E-mail address) their account won't be active yet. When they try to login they will see the message that their account is being reviewed before becoming activated.

### Block user accounts

Super Forms also provides you an extra option to completely block a specific user from being able to login until further notice. You can for instance change the **User login status after registration** setting to **Blocked** for any new registrations. Alternatively you can change any existing user's status to **Blocked** by editing the user and changing the **User status** to **Blocked** as shown below:

<div align="left"><figure><img src="../../.gitbook/assets/image (70).png" alt="Temporarily block a user from being able to login to your WordPress site."><figcaption><p>Temporarily block a user from being able to login to your WordPress site.</p></figcaption></figure></div>

###

### Allowing registration without entering a password

To create a registration form where a user doesn't require to enter a password, simply remove (or make sure to not add) a Password element. That way Super Forms will generate a new password for the user on the fly.

{% hint style="warning" %}
Don't forget to provide the generated password inside the E-mail with the use of tags `{user_pass}` or`{register_generated_password}` so that they can actually login.
{% endhint %}

This way you could have a registration form that only consists of a E-mail field named `user_email` (and optionally a `user_login` field if you wish to have the username different from the email address which might be desired in some cases).

### Sending an "Account approved" E-mail to the user

For this to work you must first define the **User login status after registration** to "Pending". As shown in the image below. When using this method, you may also want to enable the option so that a new **random password** is being generated for the user upon approving. If you leave this unchecked, you might want to delete the row that displays the users password inside the E-mail since the user should know their password already. Alternatively you could add an extra link to the E-mail that points to your [Lost password/Reset password](custom-lost-password-form-for-wordpress.md) form in case they forgot their E-mail. However, your [login form](custom-login-form-for-wordpress.md) should (in normal use cases) already contain a link to the Lost password form. So this might be redundant.

<div align="left"><figure><img src="../../.gitbook/assets/image (49).png" alt="Sending an &#x22;Account approved&#x22; E-mail to the user"><figcaption><p>Sending an "Account approved" E-mail to the user</p></figcaption></figure></div>

### Skipping the registration conditionally

Perhaps you only want the form to register a new user based on a specific condition. In this case you can enable the following option (see image below) and place your `user_email` and or `user_login` inside a [Column](../../elements/layout-elements/column-grid.md) and [conditionally hide the column](conditional-logic.md#how-to-set-conditions).

This allows you to skip the registration whenever you don't require the form submitter to become registered or create a new account. But still allow the form to be submitted.

<div align="left"><figure><img src="../../.gitbook/assets/image (41).png" alt="Conditionally registering a new user on your WordPress site."><figcaption><p>Conditionally registering a new user on your WordPress site.</p></figcaption></figure></div>
