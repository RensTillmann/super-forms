# Calculator element :

* [Introduction](#introduction)
* [When is this element useful?](#when-is-this-element-useful)
* [How to define your calculations?](#how-to-define-your-calculations)
* [Using tags inside calculation](#using-tags-inside-calculation)
* [Using dynamic columns](#using-dynamic-columns)
* [Settings](#settings)
* [Calculation examples](#calculation-examples)
* [Math functions](#math-functions)

## Introduction

With this element you can display calculations by doing any sort of complicated calculation based on user input.

?> **Tip:** You can use any [Math()](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math) JavaScript functions inside your **Math** setting.

?> **Note:** The **Math** setting is compatible with the [{tags} system](tags-system).

## When is this element useful?

* Simply display a total amount based of the product price and the selected quantity
* Sum up all the totals and create a subtotal
* Calculate taxes and display prices Incl. or Excl. taxes
* Calculate price for a modular product with a variety of options/settings example:
  * Determine the product price based on dimensions selected by the user
* Calculate loans based on specific variables selected or entered by a user

*the list can go on, having doubts about if something is possible, just contact support!*

## How to define your calculations?

Each **Calculator** element will have a setting called **Math**. Inside here you will define your calculations.
For example if you want to calculate the total of `30x10` you would enter `30*10` inside your **Math** setting.

## Using tags inside calculations

Because the **Math** setting is compatible with the [{tags} system](tags-system) you can do calculations based on user input. A very basic example would be when you have a quantity element and let the user choose a quantity. For example let's say we are selling a keyboards which cost **$150** each.

The user chooses a quantity of 3. When your quantity field is named `quantity` you can calculate the total amount for all 3 keyboards like so: `{quantity}*150`. The total costs would be **$450**.

Since this **Calculator** element acts as a regular field, we can retrieve the value of 450 through a {tag} inside the Form Settings. This allows us to use the value within any of the features available. For example you could redirect the user to the PayPal checkout to place the order and purchase it instantly online.
Some features that are interesting to use alongside the Calculator element are:

* [PayPal](paypal-checkout)
* [WooCommerce Checkout](woocommerce-checkout)
* [WooCommerce Custom Orders (IN PROGRESS)](in-progress)
* *of course you can use it with any feature for any purpose you require as long as they accept {tags}*

## Using dynamic columns

When using a [Dynamic column](columns?id=dynamic-add-more) in your form, you will not be able to use plain {tags} in your calculation like normal. This is because when the column is duplicated by the user the fields inside this newly added column do not have the same name. They will contain a suffix `_x` where `x` is the number of the column.

For example if you have a dynamic column with inside it a field named `amount`, as soon as the user adds a new column the second column will instead have a field named `amount_2`. If the user adds another column the next field will be named `amount_3` and so on.
In many cases you will not be limiting the dynamic column to a specific amount, so you will not know how many of these fields there will be and thus you woudn't be able to define it correctly inside the calculation of your **Calculator** element. That's when wildcard tags come in place `{amount}+{amount_*}` translates to the value of `amount` plus all values of fields starting with `amount_`.

When a user added 4 columns the calculation would automatically be transformed to `{amount}+{amount_2}+{amount_3}+{amount_4}`

Let's try to explain it using a real example. We will be selling T-shirts with one of the following colors:

* Red ($10)
* Green ($15)
* Blue ($20)

We will have a dynamic column, and inside it we will have a dropdown named `product` where the user will choose one of the three colors. We will also have a quantity field named `qty` where the user can choose how many of these T-shirts they wish to order. And finally we will display the price with a **Calculator** element named `amount` based on the selected color and quantity. The dropdown item values contain the prices e.g: 10, 15, 20 which we can retrieve with `{product}`. Our **Calculator** calculation will contain `{product}*{qty}`. Of course we must make sure that [Update Conditions Dynamically](columns?id=update-conditions-dynamically) is enabled for our dynamic column, otherwise the calculation `{product}*{qty}` will not be adjusted to `{product_2}*{qty_2}` upon adding new columns, so make sure to enable it!

Now everything is set in place, we can add our final **Calculator** element **outside** the dynamic column and sum all the `amount` **Calculator** elements together to get our total. We can simply add the following calculation to do this: `{amount}+{amount_*}`

Now whenever a user chooses: Red T-shirt (3x) and Blue T-shirt (2x) the `amount` **Calculator** will contain the value **30**, and the `amount_2` will contain the value **40**. Our final **Calculator** will sum the two and will result in **70**, which will eventually display **$70.00** (depending on your **Calculator** [settings](#settings) of course)

## Settings

?> **Note:** when editing elements you can switch between different TAB's

* **[General] TAB**
  * Calculation *here all the math will be defined*
  * Amount label *this is an optional prefix to be placed before the amount*
  * Amount format *this is the optional format of the amount e.g: %, EUR, USD*
  * Currency *this is an optional currency to be placed before the amount e.g: $, €*
  * Field label *title placed above the amount*
  * Field description *description placed above the amount*
  * Tooltip text *a tooltip which will be visible when hovering over the amount with the mouse*
* **[Advanced] TAB**
  * Length of decimal *`0`, `1`, `2`, `3` etc.*
  * Decimal separator *`.` or `,`*
  * Thousand separator *`None/empty`, `.` or `,`*
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

If you read the [Advanced tags](tags-system?id=advanced-tags) section you will know that you can retrieve the price with a tag like so: `{dropdown;2}`

So whenever you also have a quantity field and wish to calculate the total amount your math should look something like this: `{dropdown;2}+{quantity}`

**Regex tags example:**

To grab all fields and sum their value together you can use one of the following regular expressions inside your tags:

* Contains `*`
* Ends with `$`
* Starts with `^`

Let say we have 3 fields named `server_costs_1`, `server_costs_2`, `server_costs_3` etc. and we would like to sum up all the fields together without the need to manually type in each single one of them in our calculation. What we can do here is use either one of the following calculations:

* `{server_costs_*}` - *this will sum up all fields containing **server_costs_** (it does not matter what it starts or ends with as long as it contains this string)*
* `{^server_costs}` - *this will sum up all fields starting with **server_costs** (it does not matter what it ends with as long as it's starts with this string)*

If you have 3 fields named `1_server_option`, `2_server_option`, `3_server_option` you could use the following regex in your calculation to sum up the fields

* `{server_option$}` - *this will sum up all fields ending with **server_option** (it does not matter what it starts with)*

## Math functions

* **Plus (addition)**: `2+3` = 5
* **Minus (subtraction)**: `20-4` = 16
* **Obelus (division)**: `50/2` = 25
* **Times (multiplication)**: `100*2` = 200
* **Absolute value of a number**: `Math.abs(3, 5)` = 2
* **Arccosine of a number**: `Math.acos(8, 10)` = 0.6435011087932843
* **Hyperbolic arccosine of a number**: `Math.acosh(2.5)` = 1.566799236972411
* **Arcsine of a number**: `Math.asin(6, 10)` = 0.6435011087932844
* **Hyperbolic arcsine of a number**: `Math.asinh(2)` = 1.4436354751788103
* **Arctangent of a number**: `Math.atan(8, 10)` = 0.6747409422235527
* **Hyperbolic arctangent of a number**: `Math.atanh(0.5)` = 0.549306144334055 (approximately)
* **Arctangent of the quotient of its arguments**: `Math.atan2(10, 0) * 180 / Math.PI` = 90
* **Cube root of a number**: `Math.cbrt(64)` = 4
* **Smallest integer greater than or equal to a number**: `Math.ceil(7.004)` = 8
* **Number of leading zeroes of a 32-bit integer**: `Math.clz32(4)` = 29
* **Cosine of a number**: `Math.cos(1) * 10` = 5.403023058681398
* **Hyperbolic cosine of a number**: `Math.cosh(2)` = 3.7621956910836314
* **Returns E<sup>x</sup>, where <var>x</var> is the argument, and E is Euler's constant, the base of the natural logarithm**: `Math.exp(2)` = 7.38905609893065
* **Subtracting 1 from exp(x)**: `Math.expm1(2)` = 6.38905609893065
* **Largest integer less than or equal to a number**: `Math.floor(5.05)` = 5
* **Nearest single precision float representation of a number**: `Math.fround(5.05)` = 5.050000190734863
* **Square root of the sum of squares of its arguments**: `Math.hypot(5, 12)` = 13
* **Result of a 32-bit integer multiplication**: `Math.imul(3, 4)` = 12
* **Natural logarithm (log<sub>e</sub>, also ln) of a number**: `Math.log(8) / Math.log(2)` = 3
* **Natural logarithm (log<sub>e</sub>, also ln) of 1 + x for a number x**: `Math.log1p(1)` = 0.6931471805599453
* **Base 10 logarithm of a number**: `Math.log10(2)` = 0.3010299956639812
* **Base 2 logarithm of a number**: `Math.log2(3)` = 1.584962500721156
* **Largest of zero or more numbers**: `Math.max(1, 3, 2)` = 3
* **Smallest of zero or more numbers**: `Math.min(2, 3, 1)` = 1
* **Base to the exponent power, that is, base<sup>exponent</sup>**: `Math.pow(4, 0.5)` = 2
* **Pseudo-random number between 0 and 1**: `Math.random()` = 0.04564961619624275
* **Value of a number rounded to the nearest integer**: `Math.round(0.9)` = 1
* **Sign of the x, indicating whether x is positive, negative or zero**: `Math.sign(-3)` = -1
* **Sine of a number**: `Math.sin(2) * 10` = 9.092974268256818
* **Hyperbolic sine of a number**: `Math.sinh(2)` = 3.626860407847019
* **Positive square root of a number**: `Math.sqrt((5 * 5) + (12 * 12))` = 13
* **Tangent of a number**: `Math.tan(90 * Math.PI/180)` = 16331239353195370
* **Hyperbolic tangent of a number**: `Math.tanh(1)` = 0.7615941559557649
* **Integer part of the number x, removing any fractional digits**: `Math.trunc(42.84)` = 42
