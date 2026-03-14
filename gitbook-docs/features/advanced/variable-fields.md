---
description: >-
  How to create a variable field for your WordPress form that gets updated
  dynamically based on other field values or user input
---

# Variable Fields

Creating the most complex forms is possible with **variable fields** (Hidden field). A variable field it's value can be updated dynamically on the fly based on other fields values. This allows you to have more flexibility within your final value or for doing complex calculations and speed things up when building your form.

### What is a variable field?

A variable field is a Hidden field that contains a value that dynamically changes based on other field(s) values. In programming languages you also have a so called $variable. In general this will act the same way.

### When to use a variable field?

You should use a variable field whenever you require to have a specific final value that can vary based on user selected options in an other field or in other fields. A simple example would be whenever you want to apply 3 different discounts based on a selected quantity.

#### **Example use case**

When a user orders 10 products 0% discount should be applied, when more than 10 products are ordered the user receives 15% discount and when 30 or more products are ordered the user receives 35% discount.

In the above example, the discount amount/value is dynamic. That's when a variable field comes into play. Based on user input you can assign the correct value to your variable field and use it to display information to your user, or to calculate the correct prices with the use of the [Calculator](../../elements/form-elements/calculator.md) element.

### How to create a variable field?

From the `Form Elements` TAB drag and drop the `Hidden field` element in place. Edit the element and choose `Conditional Variable (dynamic value)` from the dropdown. Now set the **Make field variable** option to: Enable (make variable). Now apply the conditions and enter the value that you require when the conditions are met.

These conditions work the exact same way as Conditional Logic do except that it will update the value instead of showing/hiding elements.

### Creating variable conditions with CSV file

It is also possible to use a CSV file instead of manually adding each condition for your variable field. You can do this by setting the `Retrieve method` to **CSV file**.

Let's say we need retrieve the **price** (our variable field) of a flyer based on the dimension in pixels.\
The user would choose the dimensions in pixels via two quantity fields.\
In this example we have a quantity field named **height** and **width**.\
When the user chooses a dimension of **150**x**10** (height x width) the price should be **$1.25**.\
&#xNAN;_&#x74;he price of course being our variable field_

With the above example in mind our spreadsheet would look something like the below table.\
&#xNAN;_&#x74;his spreadsheet can then be saved as a CSV file which you can then use on your variable field_

|         | **10** | **20** |
| ------- | :----: | -----: |
| **150** |  1.25  |   1.50 |
| **160** |  2.25  |   2.50 |
| **170** |  3.25  |   3.50 |

{% hint style="info" %}
You can download the above example spreadsheet via google drive: [https://goo.gl/s6Etgk](https://goo.gl/s6Etgk). **Please note:** make sure to save it as a CSV file in order for it to work.
{% endhint %}

Once you have downloaded and edited the example, you can save it as a CSV file.\
Now edit your variable field, and upload the CSV file.

The last thing we will have to do is map the correct fields in your form with the **Row** headings and **Column** headings of your spreadsheet.

To map the fields correctly we have to edit the `Row heading` and `Column heading` options for our variable field.\
In our case we will map the **height** field as our Row heading, so we can enter `height` in `Row heading`.\
In our case we will map the **width** field as our Column heading, so we can enter `width` in `Column heading`.

> When above steps where correctly followed your variable field should now work correctly, you can test this by adding a HTML element and retrieve the value by placing the {tag} inside the HTML

### Using {tags} with variable fields

Variable fields can deal with {tags}, please read the {tags} system section for more information about tags.

### Example form

You can find an example form that uses conditional logic under: `Super Forms` > `Demos` > `Variable Fields`
