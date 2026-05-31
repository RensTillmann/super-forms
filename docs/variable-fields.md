# Variable Fields

Creating the most complex forms is possible with **variable fields** ([Hidden field](hidden-field)).
A variable field it's value can be updated dynamically on the fly based on other fields values.
This allows you to have more flexibility within your final value or for doing complex calculations and speed things up when building your form.

* [What is a variable field?](#what-is-a-variable-field)
* [When to use a variable field?](#when-to-use-a-variable-field)
* [How to create a variable field?](#how-to-create-a-variable-field)
* [Creating variable conditions with CSV file](#creating-variable-conditions-with-csv-file)
* [Using {tags} with variable fields](#using-tags-with-variable-fields)
* [Example form](#example-form)

## What is a variable field?

A variable field is a [Hidden field](hidden-field) that contains a value that dynamically changes based on other field(s) values. In programming languages you also have a so called $variable. In general this will act the same way.

## When to use a variable field?

You should use a variable field whenever you require to have a specific final value that can vary based on user selected options in an other field or in other fields. A simple example would be whenever you want to apply 3 different discounts based on a selected quantity.

**Example:**<br />
When a user orders 10 products 0% discount should be applied, when more than 10 products are ordered the user receives 15% discount and when 30 or more products are ordered the user receives 35% discount.

Because the discount amount is dynamic you should use a variable field to be able to retrieve the correct discount.

## How to create a variable field?

From the `Form Elements` TAB drag and drop the `Hidden field` element in place.
Edit the element and choose `Conditional Variable (dynamic value)` from the dropdown.
Now set the **Make field variable** option to: Enable (make variable).
Now apply the conditions and enter the value that you require when the conditions are met.

These conditions work the exact same way as [Conditional Logic](conditional-logic) do except that it will update the value instead of showing/hiding elements.

## Creating variable conditions with CSV file

It is also possible to use a CSV file instead of manually adding each condition for your variable field.
You can do this by setting the `Retrieve method` to **CSV file**.

Let's say we need retrieve the **price** (our variable field) of a flyer based on the dimension in pixels.<br />
The user would choose the dimensions in pixels via two quantity fields.<br />
In this example we have a quantity field named **height** and **width**.<br />
When the user chooses a dimension of **150**x**10** (height x width) the price should be **$1.25**.<br />
_the price of course being our variable field_

With the above example in mind our spreadsheet would look something like the below table.<br />
_this spreadsheet can then be saved as a CSV file which you can then use on your variable field_

|    | **10**  | **20** |
| ------------- |:-------------:| -----: |
| **150**  | 1.25    | 1.50  |
| **160**  | 2.25    | 2.50  |
| **170**  | 3.25    | 3.50  |

?> You can download the above example spreadsheet via google drive: [https://goo.gl/s6Etgk](https://goo.gl/s6Etgk)<br />**Please note:** make sure to save it as a CSV file in order for it to work.

Once you have downloaded and edited the example, you can save it as a CSV file.<br />
Now edit your variable field, and upload the CSV file.

The last thing we will have to do is map the correct fields in your form with the **Row** headings and **Column** headings of your spreadsheet.

To map the fields correctly we have to edit the `Row heading` and `Column heading` options for our variable field.<br />
In our case we will map the **height** field as our Row heading, so we can enter `height` in `Row heading`.<br />
In our case we will map the **width** field as our Column heading, so we can enter `width` in `Column heading`.

> When above steps where correctly followed your variable field should now work correctly, you can test this by adding a HTML element and retrieve the value by placing the {tag} inside the HTML

## Using {tags} with variable fields

Variable fields can deal with {tags}, please read the [{tags} system](tags-system) section for more information about tags.

## Example form

You can find an example form that uses conditional logic under: `Super Forms` > `Demos` > `Variable Fields`
