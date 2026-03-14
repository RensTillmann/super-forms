---
description: >-
  How to send E-mail reminders after user submits the form in WordPress. For
  Appointment reminders or follow E-mails for your customers.
---

# E-mail Reminders

### Example

A live demo can be found here: [https://super-forms.com/example-forms/e-mail-reminders-add-on/](https://super-forms.com/example-forms/e-mail-reminders-add-on/)

And a 1-click installable form is available via your WordPress menu: `Super Forms` > `Demos` > `E-mail Reminder`

### Description

Send E-mail reminders before and after a specific date (either form submission date or user defined date through `{tags}`). This is useful whenever you want to do the following:

* A simple appointment or event reminder e.g. **Don't forget your appointment with your dog!**
* Asking customers about their experience e.g. **How was your trip?**
* Ask customers to leave a review e.g. **Please rate our product!**

### Features

* Configure unlimited amount of reminders per form
* Send reminder based on form submission date or based on user defined date through `{date;timestamp}` tag (consult Tags System for more information about {tag} usage)
* Define how many days **before** or **after** the base date the reminder should be send example:\
  `0` = The same day, `1` = Next day, `5` = Five days after, `-1` = One day before, `-3` = Three days before
* Send reminder at a fixed time, or by offset
  * **Fixed** (e.g. always at 09:00)
    * _Define at what time the reminder should be send (Use 24h format e.g: 13:00, 09:30)_
  * **Offset** (e.g. 2 hours after date)
    * _Define at what offset the reminder should be send based of the base time example:_\
      `2` = Two hours after, `-5` = Five hours before, `0.5` = 30 minutes before

### Setup Guide

All settings for your email reminders can be found under each form via `Form Settings` > `E-mail Reminders`.

See below for some example use cases:

#### **1. Appointment reminder:**

When you wish to send an email reminder 1 day before the actual appointment you can grab your **Datepicker** field with a {tag} e.g. {date}.

You will put this tag in the setting `Send reminder based on the following date`.&#x20;

{% hint style="info" %}
Please note that when you are using a none English date format you will want to use a tag that explicit retrieves the timestamp from the selected date by entering `{date;timestamp}`. This allows you to use any date format on your datepicker element.
{% endhint %}

Now define on which day you require it to send a reminder. In our case we want to send it a day before the actual appointment. This can be defined in the next setting by entering `-1` (1 day before).

Now define at what time the reminder should be send, this can be a fixed time, but also a dynamic time, we will be using the `Fixed` time here and send a reminder at `09:00`.

The last step is to actually define the E-mail subject, body and other related settings required to send the desired E-mail.

#### **2. Please rate our product:**

When you want to get feedback from your service or product you can send emails after a specific amount of time the form was submitted.&#x20;

Let's say we want to send this email after 30 days to the customer. `Send reminder based on the following date` can be left blank in this case, and we can simply enter `30` to send the reminder 30 days after the form was submitted.&#x20;

The time does not matter what and can be set to `Fixed` at `09:00`. Last thing is to change the email Subject and content that you desire.
