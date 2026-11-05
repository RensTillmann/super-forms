---
description: >-
  With conditional logic you can show or hide specific elements or a set of
  elements that are inside a column based on a field value.
---

# Conditional Logic

### What is conditional logic?

Conditional logic allows you to **show** or **hide** specific elements based on a other fields value. Whenever you have set a condition that was met and the method or action of this condition was to hide the element, it will no longer be visible to the user and will not be submitted by the form either.

### When to use conditional logic?

You should use conditional logic to exclude fields from being submitted/saved. When a field or element is conditionally hidden it will not be visible to the user. _(the field will not be send in emails, and will not be saved in contact entries, the form basically does not submit the field at all)_

Whenever you wish to hide fields but still want them to be submitted to emails and saved in contact entries, instead use either a **Hidden field**, or put the fields in a **Column** and make the column invisible and make sure the column does not have any conditional logic enabled.

### Buttons and Dividers

Since **6.3.323 LTS**, [Button](../../elements/form-elements/button.md) and [Divider](../../elements/html-elements/divider.md) elements have their own **Conditional Logic** settings. Edit the element and choose **Conditional Logic** from the settings dropdown, then configure the conditions as described below. You can show, hide or make the element read-only. A read-only Button cannot be clicked; a Divider remains a visual separator.

For example, show a submit button only when a field has a particular value, and show a Heading or HTML message otherwise. In multi-part forms, the automatic Prev/Next copies do not inherit the submit button's conditions, so hiding or making the submit button read-only does not hide or disable those navigation copies.

{% hint style="info" %}
These direct Button and Divider settings are available in LTS since 6.3.323 only, not in 6.4. On older LTS versions and in 6.4, add the Button or Divider inside a Column and apply conditional logic to the Column instead. You can use another conditional Column for a Heading or HTML message.
{% endhint %}

{% hint style="warning" %}
Hiding a submit button is not a server-side access-control rule. It changes what the visitor sees, but does not authorize or forbid a direct submission request. Protect restricted actions and data with server-side permission checks.
{% endhint %}

In **6.3.323 LTS** and **6.4.010 Beta**, Enter skips submit buttons hidden by conditional logic or set to read-only. In a multi-part form, Enter before the last step uses that step's available Next button rather than submitting. See [Enter key navigation](../../elements/layout-elements/multi-part-step.md#enter-key-navigation).

### How to set conditions

A great example use case would be to have the submitter ask if they are registering as a **person** or as a **business**. Whenever they choose **business**, you will also want their **Company name**. But when they register as a **person** we do not want to display the **Company name** field because it is obsolete in this case. In this case we would apply the conditional logic on the `company_name` field to show when the submitter chooses to register as a **business**.

Given the above example with **personal** or **business** registration we will have a field called `company_name` that we only want to display when the user chooses to register as a **business** so they can enter their **Company name**. In this example we have the following fields:

* Radio button named `account_type` with the following options
  * Personal (with value: personal)
  * Business (with value: business)
* A text field named `company_name`

To apply the conditional logic on the `company_name` field edit the field and choose `Conditional Logic` from the dropdown menu.

You will now be asked if you want to **Show or Hide** the element when condition is met. In our case we will choose to **Show** the element when the condition is met.

Now we have to choose **When to Trigger** the condition(s) and show the field. You can choose from the below options:

* All (when all conditions matched) < this will show the element whenever all the conditions where met
* One (when one condition matched) < this will show the element whenever at least 1 condition was met

In our example it does not matter which one we choose, but for ease of use we leave it on the default option which is **All (when all conditions matched)**

The last step is to define the condition itself. You will be able to set the following options per condition:

* **Field** (in our example this would be the `account_type` field)
* **Logic** (in our example we will use the `== equals` condition, which is called the constructor) Possible constructors to choose from are:
  * ?? Contains
  * !! Not contains
  * \== Equal
  * != Not equal
  * \> Greater than
  * < Less than
  * \>= Greater than or equal to
  * <= Less than or equal
* **Value** (in our example this would be `business` based on the **account\_type** field value)
* **Or/And method** (each condition can have an `OR` or `AND` method to do an extra conditional check, but in our example we do not need it so leave it as is)

### Can I hide individual checkbox, radio or dropdown options?

No. Conditional logic always applies to a whole element. The options of a Checkbox, Radio button or Dropdown element do not have their own conditional logic settings.

If some options should only be visible in certain cases, split them into separate fields:

1. Create one Checkbox element for the options that are always visible.
2. Create a separate Checkbox element for each option (or group of options) that must be shown or hidden.
3. Apply conditional logic to each of these separate elements. You can also place them inside a **Column** and apply the conditional logic to the column.

Give each field its own unique name. Remember that a conditionally hidden field is not submitted.

To combine the separate fields into one value again, see [Combining several fields into one value](variable-fields.md#combining-several-fields-into-one-value). In emails you can also use [if statements](if-statements.md) and [foreach loops](foreach-loops.md#how-to-loop-over-selected-checkboxes-and-dropdown-items). More examples are on the [Checkbox](../../elements/form-elements/checkbox.md) page.

{% hint style="success" %}
**Tip:** To show another field based on one selected option of a Checkbox, use the `?? Contains` logic. For example: show the field `design_brief` when the checkbox `services` contains `design`. The condition checks each selected value. It matches when a selected value contains the text, so `design` also matches a value like `web_design`.
{% endhint %}

### Example Form

You can find an example form that uses conditional logic under: `Super Forms` > `Demos` > `Conditional Logic`
