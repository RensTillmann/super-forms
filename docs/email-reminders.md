# E-mail Reminders

* [Download Link](#download-link)
* [Demo Form](#demo-form)
* [Description](#description)
* [Features](#features)
* [Setup Guide](#setup-guide)

## Demo Form:

A live demo can be found here: [http://f4d.nl/super-forms/documentation/email-reminders/](http://f4d.nl/super-forms/documentation/email-reminders/)

And a 1-click installable form is avilable via your WordPress menu: `Super Forms` > `Demos` > `E-mail Reminder`

## Description

Send email reminders before and after a specific date (either form submission date or user defined date through [{tags}](tags-system)).<br />This is useful whenever you want to send emails like:

* A simple apointment or event reminder e.g: **Don't forget your apointment with your dog!**
* Asking customers about their experience e.g: **How was your trip?**
* Ask customers to leave a review e.g: **Please rate our product!**

## Features

* Configure unlimited amount of reminders per form
* Send reminder based on form submission date or based on user defined date through `{date;timestamp}` tag (consult [Tags System](tags-system) for more information about {tag} usage)
* Define how many days **before** or **after** the base date the reminder should be send example:<br />
  `0` = The same day, `1` = Next day, `5` = Five days after, `-1` = One day before, `-3` = Three days before
* Send reminder at a fixed time, or by offset
  * **Fixed** (e.g: always at 09:00)
    * *Define at what time the reminder should be send (Use 24h format e.g: 13:00, 09:30)*
  * **Offset** (e.g: 2 hours after date)
    * *Define at what offset the reminder should be send based of the base time example:*<br />
      `2` = Two hours after, `-5` = Five hours before, `0.5` = 30 minutes before

## Setup Guide

All settings for your email reminders can be found under each form via `Form Settings` > `E-mail Reminders`.

See below for some example use cases:

**1. Appointment reminder:**

When you wish to send an email reminder 1 day before the actual appointment you can grab your datepicker field with a {tag} e.g: {date}.
You will put this tag in the setting `Send reminder based on the following date`. Please note that when you are using a none English date format you will want to use a tag that explicit retrieves the timestamp from the selected date by entering `{date;timestamp}`. This allows you to use any date format on your datepicker element.

Next step is to define at what day you want to send this reminder, in our case we want to send it a day before the actual appointment. This can be defined in the next setting by entering `-1` (1 day before).

The next step is to define at what time the reminder should be send, this can be a fixed time, but also a dynamic time, we will be using the `Fixed` time here and send a reminder at `09:00`.

The last step is to actually define the Subject, content and all the other settings normally required to send a proper email.

**2. Please rate our product:**

When you want to get feedback from your service or product you can send emails after a specific amount of time the form was submitted. Let's say we want to send this email after 30 days to the customer. `Send reminder based on the following date` can be left blank in this case, and we can simply enter `30` to send the reminder 30 days after the form was submitted. The time does not matter what and can be set to `Fixed` at `09:00`. Last thing is to change the email Subject and content that you desire.
