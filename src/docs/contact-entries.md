# Contact Entries

* [What are contact entries?](#what-are-contact-entries)
* [Saving contact entries](#viewing-contact-entries)
* [Viewing contact entries](#viewing-contact-entries)
* [Filtering contact entries](#filtereing-contact-entries)
* [Exporting contact entries](#exporting-contact-entries)
* [Columns for contact entries](#columns-for-contact-entries)
* [Contact Entry Statusses](#contact-entry-statusses)
* [GDPR Consent (conditionally save entry)](#gdpr-consent-conditionally-save-entry)
* [Custom Contact Entry title](#custom-contact-entry-title)
* [Contact Entry Updating](#contact-entry-updating)

## What are contact entries?

A so called **Contact Entry** is basically the form submission. Whenever a user fills out the form and submits the form, optionally a **Contact Entry** is created within under `Super Forms` > `Contact Entries`.

The **Contact Entry** will always contain all the submitted data (including hidden fields) unless otherwise specified for a specific field.

Each field has the option to make sure it will NOT be saved in the **Contact Entry**.

?> **Developer notice:** Contact Entries are a custom post type named: `super_contact_entry`

## Saving contact entries

By default a **Contact Entry** will be created after a form was submitted.

You can optionally disable this via `Form Settings > Form Settings` under the option `Save data` choose `Do not save data`.

!> **Please note:** some of the features require that a contact entry is saved in order to work properly, if this is the case you will always be notified about this.

## Viewing contact entries

When you wish to view the data that was submitted by a user, you can find it under `Super Forms > Contact Entries`.

Here all the data will be stored for each form submissions.

You can **Edit**, **Mark as read** and **Delete** contact entries.

Each contact entry will contain all the values entered by the user of the form, unless the field was specified to not be saved in the contact entry.

A contact entry will also contain the following information:

* Entry title _(you are able to define custom title structures if nessasary)_
* Leade information _(this will contain all the form data submitted by the user)_
* Date of submission e.g: August 2, 2018 @ 6:40 pm
* IP-address of the user who submitted the form
* Username of the user who submitted the form _(only if a user was logged in at the time)_
* Entry status _(you can update a status manually, and you can define custom statusses if required)_
* Based on Form _(the form name and link to the form via which this contact entry was created based of)_

## Exporting contact entries to CSV

On the `Super Forms > Contact Entries` page you can choose to export selected Contact Entries to a CSV file.

Just bulk select the contact entries and click `Export to CSV` button at the top of the list.

A new popup will appear where you can choose all the fields (data) that you wish to export to the CSV file.

Optionally you can adjust the column names for use in the CSV file.

After that you can click `Export` and the CSV file will be downloaded to your computer.

## Columns for contact entries

By default when you go to the **Contact Entries** list in the back-end under `Super Forms > Contact Entries` there will be the following columns that will display the information of each entry:

* Title _(can not be altered)_
* Status _(can not be altered)_
* Based on Form _(can not be altered)_
* E-mail
* Phonenumber
* Message
* Date _(can not be altered)_

In some cases you might wish to alter these tables to something else that suits your needs a little better.

To change these columns you can go to `Super Forms > Settings > Backend Settings`.

On this section you can define the columns as you wish.

On each line you will define a column by seperating the field name and the column label with a pipe `|` symbol like so:

    first_name|First name
    address|Address
    birth_date|Birth Date

## Contact Entry Statusses

As you can change the columns, you can also change the statusses that are available for use for your contact entries.

To define your entry statusses you can go to `Super Forms > Settings > Backend Settings`.

Under **Contact entry statuses** you can edit, remove or add your own statusses.

Each status has a so called **slug**, **label**, **background color** and **font color**.

Example statusses are:

    pending|Pending|#808080|#FFFFFF
    processing|Processing|#808080|#FFFFFF
    on_hold|On hold|#FF7700|#FFFFFF
    accepted|Accepted|#2BC300|#FFFFFF
    completed|Completed|#2BC300|#FFFFFF
    cancelled|Cancelled|#E40000|#FFFFFF
    declined|Declined|#E40000|#FFFFFF
    refunded|Refunded|#000000|#FFFFFF

## GDPR Consent (conditionally save entry)

?> **Please note:** this feature requires the use of {tags} reade the [Tags system](tags-system) section for more info.

In order to only save a **Contact Entry** when the user gave permission you can enable the option to conditionally save the contact entry.

You can enable this feature under `Form Settings > Form Settings`.

Simply enable the option **Conditionally save Contact Entry based on user data**.

After that a new set of fields will appear where you can define your conditional logic.

The condition will have the following structure:

    {tag} == value

Where {tag} is will be the field tag to retrieve the value from the field.

This could be a checkbox that the user checks in order to give his/her consent.

When your checkbox field is named `consent` and it has one option with the value `agree` your condition should look like this:

    {consent} == agree

With above condition in place the contact entry will only be saved if the user gave his/her consent.

Otherwise the contact entry will not be saved and thus no data will be stored in the database.

## Custom Contact Entry title

In some cases you might wish to save a custom entry title.

Let's say that you wish to save the entry title as the entered E-mail address of the user.

You can go to `Form Settings > Form Settings` and enable the option **Enable custom entry titles**.

When enabled, you will see two new options called **Enter a custom entry title** and **Append entry ID after the custom title**.

In the first option you can use {tags} to dynamically set a contact entry.

Optionally you can enable to append the created entry ID after your custom title.

In our example we will not append the entry ID, and only use the tag `{email}` to retrieve the entered E-mail address that the user entered in the form.

Of course the E-mail field must be named `email` in order for this to work properly.

## Contact Entry Updating

!> **Please note:** updating contact entries will only work when either the form contains a so called **Search field** that searches entries based on their title, or when either a **GET** or **POST** request contains a key called `contact_entry_id` with the **Contact Entry ID** that requires updating, or if the option **Retrieve form data from users last submission** is enabled.

In some cases you might need to let the user be able to update a previous created contact entry.

To user this feature go to `Form Settings > Form Settings` and enable **Enable contact entry updating**.

Optionally you can also select which entry status it should get after the user updated the contact entry by changing **Contact entry status after updating**.

There are a couple of ways to allow a user to update a previously created contact entry:

* [Search method](#search-method)
* [Retrieve data from users last submission](#retrieve-data-from-users-last-submission)
* [GET or POST method](#get-or-post-method)

### Search method

This method requires the form to have a **Search field** which will simply search for a contact entry based on the contact entry title.

What you could do is have form save a custom entry title (as explained above) and generate a [Unique random number](unique-random-number) and save this as the contact entry title.

The user who would submit this form would now have a unique number that he/she could use in the future to update this specific contact entry.

In order to do this, either the same form or a seconf form would contain a **Search field** in which the user will enter the unique number, which will then search the database for this specific contact entry.

When a match was found, it will populate the form with the entry data, and the user would be able to make edits where necessary.

As soon as the user submits the form, no longer will a new contact entry be created, but instead the existing contact entry will be updated.

### Retrieve dat from users last submission

?> This method will only work for logged in users

When a user is logged in, it will retrieve the last contact entry that was created by the logged in user.

If any was found, it will automatically populate the form with the contact entry data.

And if **Enable contact entry updating** is enabled it will update this last contact entry instead of creating a new one.

### GET or POST method

This method would be most likely for developers to allow them to create a system that can list entries, and give this list a so called **Edit** button.

The edit button would contain a query string named `contact_entry_id` which would contain the entry ID that requires editing.

An example GET request could be: **domain.com/mypage/?contact_entry_id=123**

A POST request would work the same way as long as it's contains a key called `contact_entry_id` with the entry ID as it's value.
