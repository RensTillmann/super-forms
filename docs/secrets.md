# Secrets

* [What are secrets?](#what-are-secrets)
* [When to use secrets?](#when-to-use-secrets)
* [Difference between local and global secrets](#difference-between-local-and-global-secrets)
* [How to use secrets?](#how-to-use-secrets)

## What are secrets?

Secrets are values (or data) which you can store locally or globally.

You can retrieve these secrets inside your form settings with the use of tags prefixed with a `@` sign e.g: `{@secret_email}` or `{@my_secret_name}` and so on.

It's also possible to use these secrets inside your fields. The difference with normal [{tags} (Tags system)](tags-system.md) being that they will not be replaced with their underlaying value upon page load. This prevents it's value from being exposed to the client via the source code.

## When to use secrets?

A good use case to use secrets is when you wish to [conditionally (Conditional Logic)](conditional-logic.md) send an email to a specific email address based on what the user selected/choose in the form.

For instance: your company might have different departments `support@domain.com`, `sales@domain.com`.

Normally you could do this by inserting these email addresses directly inside a [Dropdown element](dropdown.md), or perhaps via the use of a hidden field or [Variable field](variable-fields.md).

This would however expose the email address inside the HTML source code (client side). This would allow bots to crawl/scrape the email address from the source code and ending up sending spam to the email address.

By using `Secrets` you can prevent this. The value of a secret is not retrieved upon page load, and will never be visible to the client.

A secret tag e.g `{@sales_email}` will only be replaced with it's underlaying value upon form submission on the server side.

## Difference between local and global secrets

There are two types of secrets: `Local` and `Global` secrets.

The difference between the two are that local secrets can only be used on the form you are working on while global secrets are site wide and if you define them they also become available to your other forms.

!> **NOTE:** Keep in mind that it's best practise to use local secrets unless you have a good usecase that requires the use of global secrets. This is because if you change one of your global secrets it can possible cause issues on forms that also used this global secret. So you must keep this in mind when defining your secrets.

## How to use secrets?

There are a couple of ways to implement secrets into your forms. The most common situation would be when you need to conditionally retrieve sensitive value based on some user selection.

A good example would be sending the form submission to a specific department conditionally.

First you will want to define your secrets. You can do so by editing your form and navigating to the TAB `Secrets` at the top left of the builder page.

Secret name: `sales_email`,  Secret value: `sales@domain.com`

Secret name: `support_email`,  Secret value: `support@domain.com`

Once you defined your secrets you can copy the tags `{@sales_email}` and `{@support_email}`.

Now create a dropdown element and define the items of your dropdown.

We will set a **Label** and **Value** for each dropdown item where the **Value** will contain the secret tag like so:

Label: `Sales department`, Value: `{@sales_email}`

Label: `Support department`, Value: `{@support_email}`

Rename the dropdown to `department` and update the element.

Now open up your form settings `Form Settings` and choose `Admin E-mail` from the dropdown.

Enable the sending of Admin emails for the form and change `Send email to:` so it contains the {tag} of the dropdown: `{department}`.

You are also allowed to use the secret tags `{@secret_tag}` directly in your form settings if needed. 

?> **Sidenote** It's best practice to make sure to set `Send email from:` to `Custom from` and define the `From email:` to reflect your domain name e.g: `no-reply@domain.com`. Also make sure to define the `From name:` if you haven't already to reflect your site name or just the domain name e.g: `domain.com`. If your users leave their email address and you want to allow your support or staff to reply directly to this email address you can enable `Set a custom reply to header`. This allows you to define the reply headers. You can retrieve the email address entered by the user via the usage of {tags} e.g: `{email}` (if your email field is named `email`). You can leave the `Reply to name:` empty if you are not asking for a name, otherwise you can also enter it's tag here e.g: `{full_name}`

