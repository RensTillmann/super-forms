---
description: >-
  This WordPress form will only allow bookings that are at least booked 24 hours
  ahead of time. This can be useful for example reservations or renting
  services.
---

# Booking 24 hours ahead of time

{% hint style="info" %}
You can copy paste the below [Form elements code](booking-24-hours-ahead-of-time.md#form-elements-code) under the \[Code] tab on your form builder page
{% endhint %}

## Explanation

This form contains two input fields for the user to fill out:

* [Datepicker](../elements/form-elements/datepicker.md) (named `date` in this example)
* Timepicker (named `time` in this example)

The form will also contain four other fields to:

* A Timepicker to set/grab the epoch of the current day @ 00:00 (`start_time`)
* A Timepicker to set/grab the epoch of the current date and time (`current_time`)
* A [Calculator](../elements/form-elements/calculator.md) to add an additional 24 hours to the epoch based on the current date and time (`future_timestamp`)
* A [Calculator](../elements/form-elements/calculator.md) the calculate the difference between the user's chosen date and time and the start time (`diff_timestamp`)&#x20;

Based on these values the epoch (timestamp) will be compared to the epoch that is 24 hours ahead of the current time. If the chosen date and time by the user is not 24 hours in the future the form will display a message (with the use of [Conditional Logic](../features/advanced/conditional-logic.md)) stating that they will have to pick a date and time that is at least 24 hours ahead in order to submit the form. When the selected date and time is 24 hours ahead of time a Submit button will be displayed to the user to submit the form.

{% hint style="warning" %}
**Note:** you will want to enable the option to "Prevent submitting form on pressing "Enter" keyboard button" under Form Settings > Form Settings on the builder page, so that the form can't be accidently submitted. This is a requirement because there is basically no validation applied on the form.
{% endhint %}

## Builder page:

<figure><img src="../.gitbook/assets/image (6).png" alt="WordPress reservation/booking form that requires a reservation date of 24 hours ahead of time"><figcaption><p>WordPress reservation/booking form that requires a reservation date of 24 hours ahead of time</p></figcaption></figure>

## Form elements code:

```
[
    {
        "tag": "date",
        "group": "form_elements",
        "data": {
            "name": "date",
            "email": "Date:",
            "placeholder": "Select a date",
            "minlength": "1",
            "showMonthAfterYear": "",
            "showWeek": "",
            "showOtherMonths": "",
            "icon": "calendar"
        }
    },
    {
        "tag": "time",
        "group": "form_elements",
        "data": {
            "name": "time",
            "email": "Time:",
            "placeholder": "Select a time",
            "current_time": "true",
            "icon": "clock;far"
        }
    },
    {
        "tag": "column",
        "group": "layout_elements",
        "inner": [
            {
                "tag": "time",
                "group": "form_elements",
                "data": {
                    "name": "start_time",
                    "email": "Time:",
                    "placeholder": "Select a time",
                    "value": "00:00",
                    "exclude": "2",
                    "exclude_entry": "true",
                    "icon": "clock;far"
                }
            },
            {
                "tag": "time",
                "group": "form_elements",
                "data": {
                    "name": "current_time",
                    "email": "Time:",
                    "placeholder": "Select a time",
                    "current_time": "true",
                    "exclude": "2",
                    "exclude_entry": "true",
                    "icon": "clock;far"
                }
            },
            {
                "tag": "calculator",
                "group": "form_elements",
                "data": {
                    "name": "future_timestamp",
                    "email": "Subtotal:",
                    "math": "({current_time;timestamp}/1000)+86400",
                    "decimals": "0",
                    "exclude": "2",
                    "exclude_entry": "true",
                    "icon": "calculator"
                }
            },
            {
                "tag": "calculator",
                "group": "form_elements",
                "data": {
                    "name": "diff_timestamp",
                    "email": "Subtotal:",
                    "math": "({date;timestamp}/1000)+({time;timestamp}/1000)-({start_time;timestamp}/1000)",
                    "decimals": "0",
                    "exclude": "2",
                    "exclude_entry": "true",
                    "icon": "calculator"
                }
            }
        ],
        "data": {
            "invisible": "true"
        }
    },
    {
        "tag": "column",
        "group": "layout_elements",
        "inner": [
            {
                "tag": "html",
                "group": "html_elements",
                "data": {
                    "name": "html",
                    "email": "HTML:",
                    "html": "<strong style=\"color:red;\">Reservations must be placed 24 hour ahead of time. Please choose a different time.</strong>",
                    "exclude": "2",
                    "exclude_entry": "true"
                }
            }
        ],
        "data": {
            "conditional_action": "show",
            "conditional_items": [
                {
                    "field": "{future_timestamp}",
                    "logic": "greater_than",
                    "value": "{diff_timestamp}",
                    "and_method": "",
                    "field_and": "",
                    "logic_and": "",
                    "value_and": ""
                }
            ]
        }
    },
    {
        "tag": "column",
        "group": "layout_elements",
        "inner": [
            {
                "tag": "button",
                "group": "form_elements",
                "data": {
                    "name": "Submit",
                    "loading": "Loading..."
                }
            }
        ],
        "data": {
            "conditional_action": "show",
            "conditional_items": [
                {
                    "field": "{future_timestamp}",
                    "logic": "less_than_or_equal",
                    "value": "{diff_timestamp}",
                    "and_method": "",
                    "field_and": "",
                    "logic_and": "",
                    "value_and": ""
                }
            ]
        }
    }
]
```
