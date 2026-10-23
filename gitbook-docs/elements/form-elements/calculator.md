---
description: >-
  How to do create a calculation form for your WordPress site to calculate
  prices, discounts and other service costs.
---

# Calculator

## Introduction

With this element you can display calculations by doing any sort of complicated calculation based on user input.

{% hint style="success" %}
**Tip:** The **Math** setting is evaluated with [math.js](https://mathjs.org/docs/reference/functions.html) (version 10), not with plain JavaScript. You can use the math.js functions, such as `round`, `ceil`, `floor`, `min` and `max`. The `Math.` prefix is optional: `Math.round(x)` and `round(x)` do the same thing.
{% endhint %}

{% hint style="info" %}
The **Math** setting is compatible with the {tags} system.
{% endhint %}

## When is this element useful?

* Simply display a total amount based of the product price and the selected quantity
* Sum up all the totals and create a subtotal
* Calculate taxes and display prices Incl. or Excl. taxes
* Calculate price for a modular product with a variety of options/settings example:
  * Determine the product price based on dimensions selected by the user
* Calculate loans based on specific variables selected or entered by a user

_the list can go on, having doubts about if something is possible, just contact support!_

## How to define your calculations?

Each **Calculator** element will have a setting called **Math**. Inside here you will define your calculations. For example if you want to calculate the total of `30x10` you would enter `30*10` inside your **Math** setting.

## Rounding results

Use `round(x, n)` to round a result to `n` decimals. Wrap the whole calculation inside `round()`. Use parentheses when you are not sure about the order of operations.

* `round({price}*{qty}*1.21, 2)` rounds the total including 21% tax to 2 decimals. With a price of 12.5 and a quantity of 3 the result is **45.38** (instead of 45.375).
* `Math.round({price}*{qty}*1.21, 2)` gives the same result. The `Math.` prefix is removed before the calculation runs.
* `round({price}*{qty}*1.21*100)/100` is the older way to do the same. It still works.

{% hint style="info" %}
In plain JavaScript `Math.round()` ignores a second argument. In the Calculator it does not: `Math.round(x, 2)` rounds to 2 decimals.
{% endhint %}

`ceil()` always rounds up, and `floor()` always rounds down. Both also accept a number of decimals:

* `ceil({amount}, 2)` rounds up to 2 decimals. 7.381 becomes **7.39**.
* `floor({amount}, 2)` rounds down to 2 decimals. 7.389 becomes **7.38**.

To round to a step instead of a number of decimals, divide by the step, round, and multiply again:

* `ceil({hours}*4)/4` rounds up to the next quarter hour. 2.3 hours becomes **2.5**.
* `floor({hours}*4)/4` rounds down to the quarter hour. 2.3 hours becomes **2.25**.
* `round({total}/5)*5` rounds to the nearest 5. 37 becomes **35**.
* `round({total}*2)/2` rounds to the nearest 0.5. 2.3 becomes **2.5**.

### Length of decimal also rounds the saved value

The **Length of decimal** setting (Advanced TAB) does more than change the display. The Calculator rounds the result to this number of decimals and stores the rounded value in the field. The rounded value is the value that is submitted with the form. It is also the value that other Calculator elements get when they use this field in their own **Math**.

* If one Calculator uses the result of another Calculator, it gets the already rounded value. Set enough decimals on the first Calculator if you need the precise value later.
* The setting uses JavaScript's `toFixed()`. It can round some values down that you would expect to round up. For example, 1.005 with 2 decimals becomes 1.00. `round(1.005, 2)` inside the **Math** setting gives **1.01**.

## Conditional calculations (if/else)

You can use a condition inside the **Math** setting with the `condition ? value_if_true : value_if_false` syntax. You can nest conditions to create more than two outcomes.

For example, a price per item that depends on the quantity:

* 100 or more items: 8 each
* 50 to 99 items: 9 each
* less than 50 items: 10 each

```
{qty} >= 100 ? {qty}*8 : ({qty} >= 50 ? {qty}*9 : {qty}*10)
```

With a quantity of 120 the result is **960**. With 60 it is **540**. With 10 it is **100**.

You can combine a condition with rounding. The below gives a 10% discount from 10 items and rounds to 2 decimals:

```
round(({qty} >= 10 ? 0.9 : 1) * {qty} * {price}, 2)
```

With a quantity of 12 and a price of 4.99 the result is **53.89**. With a quantity of 3 it is **14.97**.

{% hint style="warning" %}
Put a condition between parentheses when it is part of a larger calculation. Without them, `{qty} >= 10 ? 0.9 : 1 * {qty} * {price}` returns only **0.9** when the quantity is 10 or more.
{% endhint %}

You can use the following inside a condition:

* Comparisons: `==` (equal), `!=` (not equal), `>`, `<`, `>=`, `<=`
* Combine comparisons with `and`, `or` and `not`. Do not use `&&` or `||`; these cause an error. For example: `{weight} > 20 or {express} == 1 ? 25 : 10`
* Avoid a division by zero: `{people} > 0 ? {total}/{people} : 0`

Keep the following in mind:

* **Only numbers can be compared.** Every {tag} is replaced with a number before the calculation runs. Text that is not a number becomes `0`. So `{plan} == "pro"` does not work. Give your options numeric values instead.
* **Use advanced tags to compare a second value.** For a dropdown named `plan` with the items `basic;1` and `pro;2`, use `{plan;2} == 2 ? 49 : 19`. See [Advanced tags](../../features/advanced/tags-system.md#advanced-tags).
* **Fields hidden by conditional logic count as 0.**
* **Checkboxes and dropdowns give the sum of the selected values.** A checkbox named `express` with one option that has the value `1` gives `1` when it is checked and `0` when it is not.
* **Always use a condition with `? :`.** A comparison on its own, such as `{qty} > 3`, returns true or false instead of a number, and the Calculator cannot display it.

## Using tags inside calculations

Because the **Math** setting is compatible with the {tags} system you can do calculations based on user input. A very basic example would be when you have a quantity element and let the user choose a quantity. For example let's say we are selling a keyboards which cost **$150** each.

The user chooses a quantity of 3. When your quantity field is named `quantity` you can calculate the total amount for all 3 keyboards like so: `{quantity}*150`. The total costs would be **$450**.

Since this **Calculator** element acts as a regular field, we can retrieve the value of 450 through a {tag} inside the Form Settings. This allows us to use the value within any of the features available. For example you could redirect the user to the PayPal checkout to place the order and purchase it instantly online. Some features that are interesting to use alongside the Calculator element are:

* PayPal
* WooCommerce Checkout
* WooCommerce Instant Orders (IN PROGRESS)
* _of course you can use it with any feature for any purpose you require as long as they accept {tags}_

## Using dynamic columns

When using a Dynamic column in your form, you will not be able to use plain {tags} in your calculation like normal. This is because when the column is duplicated by the user the fields inside this newly added column do not have the same name. They will contain a suffix `_x` where `x` is the number of the column.

For example if you have a dynamic column with inside it a field named `amount`, as soon as the user adds a new column the second column will instead have a field named `amount_2`. If the user adds another column the next field will be named `amount_3` and so on. In many cases you will not be limiting the dynamic column to a specific amount, so you will not know how many of these fields there will be and thus you woudn't be able to define it correctly inside the calculation of your **Calculator** element. That's when wildcard tags come in place `{amount}+{amount_*}` translates to the value of `amount` plus all values of fields starting with `amount_`.

When a user added 4 columns the calculation would automatically be transformed to `{amount}+{amount_2}+{amount_3}+{amount_4}`

Let's try to explain it using a real example. We will be selling T-shirts with one of the following colors:

* Red ($10)
* Green ($15)
* Blue ($20)

We will have a dynamic column, and inside it we will have a dropdown named `product` where the user will choose one of the three colors. We will also have a quantity field named `qty` where the user can choose how many of these T-shirts they wish to order. And finally we will display the price with a **Calculator** element named `amount` based on the selected color and quantity. The dropdown item values contain the prices e.g: 10, 15, 20 which we can retrieve with `{product}`. Our **Calculator** calculation will contain `{product}*{qty}`. Of course we must make sure that Update Conditions Dynamically is enabled for our dynamic column, otherwise the calculation `{product}*{qty}` will not be adjusted to `{product_2}*{qty_2}` upon adding new columns, so make sure to enable it!

Now everything is set in place, we can add our final **Calculator** element **outside** the dynamic column and sum all the `amount` **Calculator** elements together to get our total. We can simply add the following calculation to do this: `{amount}+{amount_*}`

Now whenever a user chooses: Red T-shirt (3x) and Blue T-shirt (2x) the `amount` **Calculator** will contain the value **30**, and the `amount_2` will contain the value **40**. Our final **Calculator** will sum the two and will result in **70**, which will eventually display **$70.00** (depending on your **Calculator** [settings](#settings) of course)

## Settings

{% hint style="info" %}
Wen editing elements you can switch between different TAB's
{% endhint %}

* **\[General] TAB**
  * Calculation _here all the math will be defined_
  * Amount label _this is an optional prefix to be placed before the amount_
  * Amount format _this is the optional format of the amount e.g: %, EUR, USD_
  * Currency _this is an optional currency to be placed before the amount e.g: $, €_
  * Field label _title placed above the amount_
  * Field description _description placed above the amount_
  * Tooltip text _a tooltip which will be visible when hovering over the amount with the mouse_
* **\[Advanced] TAB**
  * Length of decimal _`0`, `1`, `2`, `3` etc._
  * Decimal separator _`.` or `,`_
  * Thousand separator _`None/empty`, `.` or `,`_
  * Enable birthdate calculations
    * Return years (age)
    * Return months
    * Return days
  * Convert timestamp to specific date format

## Calculation examples

**Grabbing multiple values with advanced tags system:**

Let's say we have a dropdown with product options, in this case the dropdown will have the color, and it's price. We define the following items for this dropdown:

* Red / red;10
* Green / green;15
* Blue / blue;20

If you read the Advanced tags section you will know that you can retrieve the price with a tag like so: `{dropdown;2}`

So whenever you also have a quantity field and wish to calculate the total amount your math should look something like this: `{dropdown;2}+{quantity}`

**Regex tags example:**

To grab all fields and sum their value together you can use one of the following regular expressions inside your tags:

* Contains `*`
* Ends with `$`
* Starts with `^`

Let say we have 3 fields named `server_costs_1`, `server_costs_2`, `server_costs_3` etc. and we would like to sum up all the fields together without the need to manually type in each single one of them in our calculation. What we can do here is use either one of the following calculations:

* `{server_costs_*}` - _this will sum up all fields containing **server\_costs\_** (it does not matter what it starts or ends with as long as it contains this string)_
* `{^server_costs}` - _this will sum up all fields starting with **server\_costs** (it does not matter what it ends with as long as it's starts with this string)_

If you have 3 fields named `1_server_option`, `2_server_option`, `3_server_option` you could use the following regex in your calculation to sum up the fields

* `{server_option$}` - _this will sum up all fields ending with **server\_option** (it does not matter what it starts with)_

## Math functions

* **Plus (addition)**: `2+3` = 5
* **Minus (subtraction)**: `20-4` = 16
* **Obelus (division)**: `50/2` = 25
* **Times (multiplication)**: `100*2` = 200
* **Absolute value of a number**: `Math.abs(3-5)` = 2
* **Arccosine of a number**: `Math.acos(0.8)` = 0.6435011087932843
* **Hyperbolic arccosine of a number**: `Math.acosh(2.5)` = 1.566799236972411
* **Arcsine of a number**: `Math.asin(0.6)` = 0.6435011087932844
* **Hyperbolic arcsine of a number**: `Math.asinh(2)` = 1.4436354751788103
* **Arctangent of a number**: `Math.atan(0.8)` = 0.6747409422235527
* **Hyperbolic arctangent of a number**: `Math.atanh(0.5)` = 0.5493061443340548
* **Arctangent of the quotient of its arguments**: `Math.atan2(10, 0) * 180 / Math.PI` = 90
* **Cube root of a number**: `Math.cbrt(64)` = 4
* **Smallest integer greater than or equal to a number**: `Math.ceil(7.004)` = 8
* **Cosine of a number**: `Math.cos(1) * 10` = 5.403023058681398
* **Hyperbolic cosine of a number**: `Math.cosh(2)` = 3.7621956910836314
* **Returns Ex, where x is the argument, and E is Euler's constant, the base of the natural logarithm**: `Math.exp(2)` = 7.38905609893065
* **Subtracting 1 from exp(x)**: `Math.expm1(2)` = 6.38905609893065
* **Largest integer less than or equal to a number**: `Math.floor(5.05)` = 5
* **Square root of the sum of squares of its arguments**: `Math.hypot(5, 12)` = 13
* **Natural logarithm (loge, also ln) of a number**: `Math.log(8) / Math.log(2)` = 3
* **Natural logarithm (loge, also ln) of 1 + x for a number x**: `Math.log1p(1)` = 0.6931471805599453
* **Base 10 logarithm of a number**: `Math.log10(2)` = 0.3010299956639812
* **Base 2 logarithm of a number**: `Math.log2(3)` = 1.584962500721156
* **Largest of zero or more numbers**: `Math.max(1, 3, 2)` = 3
* **Smallest of zero or more numbers**: `Math.min(2, 3, 1)` = 1
* **Base to the exponent power, that is, baseexponent**: `Math.pow(4, 0.5)` = 2
* **Pseudo-random number between 0 and 1**: `Math.random()` = 0.04564961619624275
* **Value of a number rounded to the nearest integer**: `Math.round(0.9)` = 1
* **Value of a number rounded to a number of decimals**: `round(2.345, 2)` = 2.35
* **Sign of the x, indicating whether x is positive, negative or zero**: `Math.sign(-3)` = -1
* **Sine of a number**: `Math.sin(2) * 10` = 9.092974268256818
* **Hyperbolic sine of a number**: `Math.sinh(2)` = 3.626860407847019
* **Positive square root of a number**: `Math.sqrt((5 * 5) + (12 * 12))` = 13
* **Tangent of a number**: `Math.tan(90 * Math.PI/180)` = 16331239353195370
* **Hyperbolic tangent of a number**: `Math.tanh(1)` = 0.7615941559557649
* **Integer part of the number x, removing any fractional digits**: `fix(42.84)` = 42

{% hint style="info" %}
JavaScript-only functions such as `Math.trunc()`, `Math.clz32()`, `Math.fround()` and `Math.imul()` do not exist in math.js. Using them shows the message "There is a problem with the following calculation". Use `fix()` instead of `Math.trunc()`.
{% endhint %}
