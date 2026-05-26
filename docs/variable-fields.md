# Variable Fields

Creating the most complex forms is possible with **variable fields** ([Hidden field](hidden-field)).
A variable field it's value can be updated dynamically on the fly based on other fields values.
This allows you to have more flexibility within your final value or for doing complex calculations and speed things up when building your form.

* [What is a variable field?](#what-is-a-variable-field)
* [When to use a variable field?](#when-to-use-a-variable-field)
* [How to create a variable field?](#how-to-create-a-variable-field)
* [Creating variable conditions with CSV file](#creating-variable-conditions-with-csv-file)
* [Using {tags} with variable fields](#using-tags-with-variable-fields)
* [Using regex tags inside variable conditions](#using-regex-tags-inside-variable-conditions)
* [Using email if statements with variable fields](#using-email-if-statements-with-variable-fields)
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

## Using regex tags inside variable conditions

Inside each condition row of a variable field the **Condition value** you compare against is a plain string, but the **field tag** you pick can reference multiple fields at once using the regex tag syntax described in [Calculation examples](calculator?id=calculation-examples).

This is especially useful when your form uses a naming convention for related fields.

**Example — sum all quantity fields to decide a discount tier:**

Suppose you have `qty_shirts`, `qty_hats`, and `qty_bags`. Instead of adding three separate condition fields you can reference all of them as `{qty_*}` inside a Calculator element, then point your variable field at that calculator's tag.

| Variable condition | Value |
|---|---|
| `{total_qty}` >= `100` | `tier_bulk` |
| `{total_qty}` >= `50` | `tier_mid` |
| `{total_qty}` >= `1` | `tier_standard` |

Where `total_qty` is a Calculator field whose **Math** expression is `{qty_*}` (sums all fields starting with `qty_`).

?> **Tip:** Regex tags (`{field_*}`, `{^field}`, `{field$}`) only work inside **Calculator** elements. Inside variable field conditions you compare against the tag of a single named field — use a calculator as an intermediate step when you need to aggregate multiple fields first.

## Using email if statements with variable fields

Once a variable field has resolved to a value you can use [email if statements](email-if-statements) to show different content in your emails or HTML elements based on that value.

**Example — discount tier message in the confirmation email:**

```html
if({discount_tier}=='tier_bulk'):
    You qualify for our <strong>Bulk discount</strong> — 25 % off your order.
elseif:
    if({discount_tier}=='tier_mid'):
        You qualify for our <strong>Mid-volume discount</strong> — 15 % off your order.
    elseif:
        Standard pricing applies to your order.
    endif;
endif;
```

**Example — checking the combined services variable from the checkbox workaround:**

```html
if({selected_services}??'Design'):
    Our design team will follow up within 1 business day.
endif;

if({selected_services}??'Hosting'):
    Server provisioning begins within 24 hours.
endif;
```

The `??` operator means _contains_, so the block fires whenever the variable field's resolved value includes the given string — regardless of the other services that were selected.

**Checking whether a variable field was never set (conditionally hidden parent):**

When the parent fields that drive a variable are conditionally hidden, the variable itself may be absent. Use `isset()` to guard against that case:

```html
if(isset(selected_services)):
    Selected services: {selected_services}
elseif:
    No services were available for the selected plan.
endif;
```

?> See [Email if statements](email-if-statements) for the full list of operators (`==`, `!=`, `>`, `<`, `??`, `!??`, etc.) and the `isset()` / `!isset()` helpers.

## Example form

You can find an example form that uses conditional logic under: `Super Forms` > `Demos` > `Variable Fields`
