# Register & Login

* [About](#about)
* [Quick start](#quick-start)
  * [Creating a login form](#creating-a-login-form)
  * [Creating a registration form](#creating-a-registration-form)
  * [Creating a reset password form](#creating-a-reset-password-form)
  * [Update current logged in user](#update-current-logged-in-user)

## About

With this feature you can `register` or `login` users through your Super Forms.

You can also choose to only allow specific user role(s) to login. And specify what role a new registered user should get. Of course the Lost password form is also available which allows users to enter their email address to receive a new password. As an option you can select to send an verification email after registration or instantly let users login after their registration. Redirect to any page (for instance a dashboard).

## Quick start

The following actions are available:

* Register a new user
* Login (user will be logged in)
* Reset password (lost password)
* Update current logged in user

The quickest way to get started is to install the Demo forms available under `Super Forms > Demos`. Here you can find the demo forms:

* Register Form
* Login Form
* Lost Password Form

### Creating a login form

?> **NOTE:** Demo form available under `Super Forms > Demos` named `Login Form`

Edit your form and navigate to `Form Settings > Register & Login`.

From the `Actions` option choose `Login (user will be logged in)`.

Now select the roles that are allowed to login, or leave black to allow all roles.

Define the URL where the login page is located on your site.

In case you enable the option to send an verification email to the user, you must make sure you added the `Activation Code` element to your login form. This is where the user will enter the verification code to verify their account.

!> **Important:** Your form must have a password field exactly named `user_pass` or the user won't be able to login. Also make sure to exclude the password field from being saved in Contact Entries, and Exclude it from E-mails. There is no need to send the password, and it can only cause a security risk. However Super Forms gives you this possibility, you are free to do with the data as you please.

?> **Note:** Your form should also contain a field named `user_login` or `user_email` or the user won't be able to login.

?> **Note:** Do not store the password in your Contact Entries, nor send it Your form should also contain a field named `user_login` or `user_email` or the user won't be able to login.

### Creating a registration form

?> **NOTE:** Demo form available under `Super Forms > Demos` named `Register Form`

Edit your form and navigate to `Form Settings > Register & Login`.

From the `Actions` option choose `Register a new user`.

You should now see all settings that relate to registering a new user. Configure them as you see fit.

In case you enable the option to send an verification email to the user, you must make sure you added the `Activation Code` element to your login form. This is where the user will enter the verification code to verify their account. It is also important to setup the correct `Login page URL` or the user wouldn't be redirected to the login form correctly through the tag `{register_login_url}`.

It is also possible to manually approve registrations. If this is the case you will want to change the `User login status after registration` from `Active (default)` to `Pending`. Please understand that this isn't the same thing as the email verification.

?> **Important:** Your form must contain a password field exactly named `user_pass` or a random password will be generated instead. Also make sure to exclude the password field from being saved in Contact Entries, and Exclude it from E-mails. There is no need to send the password, and it can only cause a security risk. However Super Forms gives you this possibility, you are free to do with the data as you please.

?> **Note:** Your form should also contain a field named `user_login` or `user_email` or the user won't be able to login.

?> **Note:** Make sure you have defined the `Login page URL` to match the login page on your site.

### Creating a reset password form

?> **NOTE:** Demo form available under `Super Forms > Demos` named `Register Form`

Edit your form and navigate to `Form Settings > Register & Login`.

From the `Actions` option choose `Reset password (lost password)`.

You should now see all the settings that relate to resetting a password. Configure them as you see fit.

Just make sure the `Login page URL` matches the login page on your site.

Also make sure your form has a field named `user_email`, which is where the user will receive the password reset email at.

### Update current logged in user

Edit your form and navigate to `Form Settings > Register & Login`.

From the `Actions` option choose `Update current logged in user`

Optionally choose wether or not you want to register a new user if the user is not logged in.

Optionally enable the option to update a user based on a hidden field named `user_id`

Select the role which the user must become after submitting the form

!> **Important:** Be careful to not select `Administrator` by accident!

Define the custom meta data that needs to be updated, you can map your fields with the meta key like so: **field_name|meta_key**

Example to update WooCommerce billing first name:

```js
first_name|billing_first_name
```
