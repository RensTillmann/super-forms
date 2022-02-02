# Mailster

* [About](#about)
* [Quick start](#quick-start)

## About

With this you can subscribe users for Mailster after a user submits the form.

Optionally let users select their interests (Lists), and save custom fields set within your Mailster configuration.

## Quick start

You can enable this by editing your form and navigating to `Form Settings > Mailster Settings`. Then check the `Add Mailster subscriber` option, and configure the settings as desired.

Make sure to enter the Mailster **List ID** under `Subscriber list ID('s) seperated by comma's`. You are allowed to use tags if needed.

You can conditionally subscribe a user based on form data by enabling `Conditionally save subscriber based on user data`.

Inside the `Subscriber email address` you would always want to use a tag that retrieves the entered user email address. If your email field is named `email` then you should use the tag `{email}`. This is set by default for this setting.

Optionally you can save some custom Mailster user data. To to this you have to map the mailster field name with the field name in your form.

Let's say your form has fields `First name` and `Last name` which are named `first_name` and `last_name` respectively. In that case you could map it like so:

```js
firstname|first_name
lastname|last_name
```
