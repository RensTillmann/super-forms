# Autopopulate fields

* [What does autopopulation stand for?](#what-does-autopopulation-stand-for)
* [When and why would I use autopopulation?](#when-and-why-would-i-use-autopopulation)
* [How can I populate fields with data?](#how-can-i-populate-fields-with-data)
* [Examples of autopopulation](#examples-of-autopopulation)
* [Autopopulate with last contact entry data](#autopopulate-with-last-contact-entry-data)

## What does autopopulation stand for?

In short, autopopulation means that a field in your form will be given a specific value which by default the
field did not had on it's own.

## When and why would I use autopopulation?

You will use autopopulation when you have a multi-form setup, where you require to pass data from 1 form to the other.

Another method could be to retrieve data from a [contact entry](contact-entries) that was previously saved in your database, for either updating the already existing [contact entry](contact-entries) or using it to create a new one.

This way the user would not have to re-enter all the fields, instead the user can now update those that need updating, and leave the rest untouched.

There are many other usecases which you could possibly use to implement autopopulation.

## How can I populate fields with data?

There are currently a couple of ways to autopopulate a field with a defined value:

* [GET request](#get-request) (via query strings)
* [POST request](#post-request) (via post data)
* [Autopopulate form with last Contact Entry data](#autopopulate-form-with-last-contact-entry-data)

### GET request

This is most likely the most used and easiest method of autopopulating form fields with data.

A simple example would be to have two forms, where the first form would ask for the users **Frist name** and **Last name**.

The form will then redirect to a second page that contains the second form and parse it's data via the query string.

You can set a custom redirect for your form under `Form Settings > Form Settings`. Then choose from **Form redirect option** to use a **Custom URL**.

Now you can enter your URL which would look something like this: `domain.com/page2/?first_name={first_name}&last_name={last_name}`

This will redirect to `page2` which should contain your second form.

Your second form will also require two fields named `first_name` and `last_name`. They will now automatically contain the values that the user entered on the first form.

### POST request

The POST request will work the same way as the GET request except that the data is not visible in the URL in the users browser.

This could possibly be a cleaner way to do it. Both methods work, it's up to you as a developer to choose which method you prefer.

Instead of using a redirect method, you would use the **Enable form POST method**.

You can then map your key value pairs under **Enter custom parameter string**, which would look something like:

    first_name|{first_name}
    last_name|{last_name}

### Autopopulate form with last contact entry data

?> **Please note:** this method will only work when a user is logged in

If a user is logged in and has previously submitted a form which creates [contact entries](contact-entries), you could autopopulate a form with this [contact entry](contact-entries) data based on the last submitted data of this user.

This is extremely useful if you have users that need to submit the same form many times, while a lot of data might possibly be exactly the same.

## Examples of autopopulation

A basic example of autopopulation is to have a URL containing some information that you wish to process in your form.

The URL could look like `domain.com/contact/?name=John&age=45&country=US`

When your form has fields named `name`, `age`, `country` those fields would then be autopopulated with the values `John`, `45`, `US`.
