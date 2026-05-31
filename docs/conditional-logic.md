# Conditional Logic

With this feature you can conditionally show or hide specific elements or a set of elements that are inside a column based on a other fields value.
This guide will explain what conditional logic is, how to set conditions and when to us conditional logic.

* [What is conditional logic?](#what-is-conditional-logic)
* [When to use conditional logic?](#when-to-use-conditional-logic)
* [How to set conditions?](#how-to-set-conditions)
* [Example Form](#example-form)

## What is conditional logic?

Conditional logic allows you to **show** or **hide** specific elements based on a other fields value.
Whenever you have set a condition that was met and the method or action of this condition was to hide the element, it will no longer be visible to the user and will not be submitted by the form either.

## When to use conditional logic?

You should use conditional logic to exclude fields from being submitted/saved. When a field or element is conditionally hidden it will not be visible to the user. _(the field will not be send in emails, and will not be saved in contact entries, the form basically does not submit the field at all)_

Whenever you wish to hide fields but still want them to be submitted to emails and saved in contact entries, instead use either a **Hidden field**, or put the fields in a **Column** and make the column invisible and make sure the column does not have any conditional logic enabled.

You can also use conditional logic to conditionally hide/show a [Button](button) element (submit button).
For instance whenever you do not allow a user to submit the form based on specific input, you can conditionally hide the submit button and instead display a message to the user. You can do this by adding a [Column](columns) element and adding a [Button](button) inside the column and applying the conditional logic on the column. The same thing you can do for the message to be displayed, you can add either a [Heading](heading) or [HTML element](html) to display a message.

## How to set conditions

A great example usecase would be to have the submitter ask if they are registering as a **person** or as a **business**. Whenever they choose **business**, you will also want their **Company name**. But when they register as a **person** we do not want to display the **Company name** field because it is absolete in this case. In this case we would apply the conditional logic on the `company_name` field to show when the submitter choosed to register as a **business**.

Given the above example with **personal** or **business** registration we will have a field called `company_name` that we only want to display when the user choosed to register as a **business** so they can enter their **Company name**. In this example we have the following fields:

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
  * == Equal
  * != Not equal
  * &gt; Greater than
  * <  Less than
  * &gt;= Greater than or equal to
  * <= Less than or equal
* **Value** (in our example this would be `business` based on the **account_type** field value)
* **Or/And method** (each condition can have an `OR` or `AND` method to do an extra conditional check, but in our example we do not need it so leave it as is)

## Example Form

You can find an example form that uses conditional logic under: `Super Forms` > `Demos` > `Conditional Logic`
