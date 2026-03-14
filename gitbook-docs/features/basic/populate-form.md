---
description: >-
  How to automatically populate your WordPress form with previous data or
  predefined data via parameters or previous form submission.
---

# Populate form

### What does "auto population" mean?

Auto population refers to the process of automatically filling in a field in a form with a predetermined value, which the field did not originally have on its own. In simpler terms, it means that a value is automatically inserted into a form field without the user having to manually enter it.

### Basic example of auto population

A basic example of auto population is to have an URL containing some information that you wish to process in your form. The URL could look like `domain.com/contact/?name=John&age=45&country=US`. When your form has fields named `name`, `age`, `country` those fields would then be auto populated with the values `John`, `45`, `US`.

### When and why would I use auto population?

Auto population is commonly used in two scenarios. First, when you have a multi-form setup and need to **transfer data from one form to another**. By automatically populating fields in subsequent forms with data from the previous form, users can easily navigate through the process without having to re-enter the same information.

The second scenario is when you want to retrieve data from a previously saved contact entry in your database. This can be useful for updating existing contact information or creating new entries based on the existing data. By auto-populating the fields, users can make updates where necessary and leave the rest untouched, saving time and effort.

These are just a couple of examples, but there are many other use cases where auto population can be implemented to streamline data entry processes and enhance user experience.

### How can I populate fields with data?

There are a few methods available to auto-populate fields with predefined values:

* [GET request](populate-form.md#get-request-via-query-strings) (via query strings)
* [POST request](populate-form.md#post-request) (via post data)
* [Auto populate form with last Contact Entry data](populate-form.md#auto-populate-form-with-last-contact-entry-data)

#### GET request (via query strings):

This is the most commonly used and straightforward method. It can be used to set a predefined value for one or multiple fields in your form via the URL that the user visits. You can also use it to **transfer data from one form to another**. You can have two forms where the first form asks for the user's first name and last name. The form then redirects to a second page that contains the second form, and the data is passed through the query string. By setting a custom redirect URL for your form and including the field values in the URL (e.g., `domain.com/page2/?first_name={first_name}&last_name={last_name}`), the second form will automatically populate the corresponding fields with the values entered in the first form.

A simple example would be to have two forms, where the first form would ask for the users **Frist name** and **Last name**. The form will then redirect to a second page that contains the second form and parse it's data via the query string.

You can set a custom redirect for your form under `Form Settings > Form Settings`. Then choose from **Form redirect option** to use a **Custom URL**.&#x20;

Now you can enter your URL which would look something like this: `domain.com/page2/?first_name={first_name}&last_name={last_name}`. This will redirect to `page2` which should contain your second form.&#x20;

Your second form will also require two fields named `first_name` and `last_name`. They will now automatically contain the values that the user entered on the first form.

<div align="left"><figure><img src="../../.gitbook/assets/image (87).png" alt="Redirecting to different form and populating the form with data."><figcaption><p>Redirecting to different form and populating the form with data.</p></figcaption></figure></div>

#### POST request

This method works similarly to the GET request, but the data is not visible in the URL. It provides a cleaner approach but functions in the same manner. Instead of using a redirect, you enable the form POST method and map the key-value pairs using custom parameter strings.

Instead of using a redirect method, you would use the **Enable form POST method**.

You can then map your key value pairs under **Enter custom parameter string**, which would look something like:

```
first_name|{first_name}
last_name|{last_name}
```

<div align="left"><figure><img src="../../.gitbook/assets/image (95).png" alt="Enable form POST method with custom parameters."><figcaption><p>Enable form POST method with custom parameters.</p></figcaption></figure></div>

#### Auto populate form with last contact entry data

{% hint style="info" %}
**Please note:** this method will only work when a user is logged in
{% endhint %}

This method requires the user to be logged in. If a user is logged in and has previously submitted a form that creates contact entries, you can auto-populate a form with their last submitted data. This is particularly useful when users need to submit the same form multiple times, with some data remaining unchanged. By retrieving the last submitted data for that user, the form fields can be automatically populated with the corresponding values.

### Update an already existing form submission (contact entry)

Another option that you have is to update the previous entry without creating a new one as shown below:

<div align="left"><figure><img src="../../.gitbook/assets/image (75).png" alt="Retrieve last form submission and update existing entry."><figcaption><p>Retrieve last form submission and update existing entry.</p></figcaption></figure></div>
