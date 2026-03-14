---
description: >-
  How to create custom login form for your WordPress site where (optionally)
  only specific user roles are allowed to login.
---

# Custom login form for WordPress

{% hint style="info" %}
This article explains how to setup and configure your custom **Login form** for WordPress. In most use cases a login form will work in combination with a registration and lost password form so you might also be interested on how setup a [Registration form](custom-registration-form-for-wordpress.md) and or [Lost password](custom-lost-password-form-for-wordpress.md) form.
{% endhint %}

{% hint style="success" %}
A demo form is available under **Super Forms > Demos** named "Login form" which should help to quickly get a working Login form up and running.&#x20;
{% endhint %}

### Creating a custom WordPress login form <a href="#creating-a-login-form" id="creating-a-login-form"></a>

{% hint style="danger" %}
**Important:** Your login form must have fields named exactly `user_login` and a password field named exactly `user_pass`. These are required for WordPress login to function properly. You are also strongly advised to not store or save the password field since for most use cases this is not desired. However Super Forms does give you the ability to do so. So please double check that your password field is not saved as Contact Entry data, and that you are excluding it from E-mails.
{% endhint %}

First edit your form and navigate to **Form Settings > Register & Login**. From the **Actions** option choose **Login (user will be logged in)**. Now when the form is being submitted it will try to login the user.

<div align="left"><figure><img src="../../.gitbook/assets/image (43).png" alt="Enabling the form to act as a login form for your WordPress site."><figcaption><p>Enabling the form to act as a login form for your WordPress site.</p></figcaption></figure></div>

### Allowing only specific user roles to login to your WordPress site

Now select the roles that should be allowed to login, or leave black to allow all roles.

<div align="left"><figure><img src="../../.gitbook/assets/image (93).png" alt="Allowing only specific user roles to login to your WordPress site."><figcaption><p>Allowing only specific user roles to login to your WordPress site.</p></figcaption></figure></div>

### Defining the login page URL

{% hint style="warning" %}
Make sure to define the **Login page URL** so that it points to the URL where the Login form is located, for instance: `https://mydomain.com/login` as shown in the image below. This URL can be retrieved with the tag `{register_login_url}` inside your E-mails if needed.
{% endhint %}

<div align="left"><figure><img src="../../.gitbook/assets/image (79).png" alt="Defining the login page URL for your WordPress site."><figcaption><p>Defining the login page URL for your WordPress site.</p></figcaption></figure></div>

### Verification of E-mail address

If your [registration form](custom-registration-form-for-wordpress.md) is configured to send a verification E-mail after registering a new account, you will want to make sure you add the **Verification Code** element to your **Login form**.

<div align="left"><figure><img src="../../.gitbook/assets/image (90).png" alt="Adding the &#x22;Verification Code&#x22; element to allow registered users to verify and activate their account on your WordPress website."><figcaption><p>Adding the "Verification Code" element to allow registered users to verify and activate their account on your WordPress website.</p></figcaption></figure></div>

Whenever the user clicks the [verification link](custom-registration-form-for-wordpress.md) inside the E-mail, they will be redirected to the [defined login page](custom-login-form-for-wordpress.md#defining-the-login-page-url) where they can enter the **verification code** to activate their account. As shown below.

<figure><img src="../../.gitbook/assets/image (19).png" alt="Login form for WordPress with verification code to verify their E-mail address was valid."><figcaption><p>Login form for WordPress with verification code to verify their E-mail address was valid.</p></figcaption></figure>

{% hint style="success" %}
You should now have a working login form which allows user to login to your WordPress site. Now might be a good time to read how to setup a [registration form](custom-registration-form-for-wordpress.md) and or [lost password form](custom-lost-password-form-for-wordpress.md).
{% endhint %}
