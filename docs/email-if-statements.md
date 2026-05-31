# E-mail if statements

?> **NOTE:** This feature also works with HTML elements since v4.6.0+

?> **Please note:** this feature was build specifically for the [{tags} system](tags-system). Without undertanding or using [tags](tags-system) this feature will not have any purpose or value to you.

* [What is an email if statement?](#what-is-an-email-if-statement)
* [Construction of "if statements" explained](#construction-of-quotif-statementsquot-explained)
* [How to create my own if statements?](#how-to-create-my-own-if-statements)
* [Practical example use cases](#practical-example-use-cases)

## What is an email if statement?

With the Super Forms build in `if statements` for your emails you will have more flexibility the way your emails will be generated based on user input.

If you are familiar with any programming language you can construct your own if statements with ease for your emails.

## Construction of "if statements" explained

Each if statement has a so called `operator` to do a so called `Comparison Operation`. In Super Forms we can use one of the following operators for our statements:

* `==` (equal to)
* `!=` (not equal)
* `>` (greater than)
* `<` (less than)
* `>=` (greater than or equal to)
* `<=` (less than or equal to)
* `??` (contains)
* `!??` (does not contains)

## How to create my own if statements?

An example if statement would look like this:

```html
if('John'=='John'):
    Your custom HTML here...
endif;
```

Of course this example will always output the HTML because `John` equals `John`.

But of course we will want to use `{tags}` inside our if statement to compare the `first_name` input field with the name `John`:

```html
if({first_name}=='John'):
    Your custom HTML here...
endif;
```

When you require to output a default text whenever `first_name` is not equal to `John` we can use the `elseif` statement:

```html
if({first_name}=='John'):
    This text is only for John :)
elseif:
    This text is for everyone who isn't named John
endif;
```

## Practical example use cases

_Below we will cover some pratical use cases that you can apply for your own application(s)._

### Use case 1: Show extra information based on a selected package:

A simple use case would be to add some text that is specific for a package that a user selected inside your form.

Let's say the package name is called `package_1` and it's value when choosen by the user is `daily_backups`

When the user selected this package to be included in their order we want to display some important information regarding how these backups are being created by the company.

In our Confirmation email body (which is send to the user) we can enter the following if statement:

```html
if({package_1}=='daily_backups'):
    Your backups are being stored daily on 3 other independent servers.
    By default your server has a fallback server that will be activated whenever the server is down for more than 2 min.
    If you need more technical information about how we process backups read our <a href="domain.com/faq">FAQ</a>
endif;
```

The above if statement will output the text or HTML depending on your own needs whenever the tag `{package_1}` is equal to `daily_backups`

### Use case 2: Ask for parental consent when underaged:

Another example could be to check if a user is underaged or not and display some information about needing a Parental consent:

```html
if({age}<18):
    Because you are underaged we need a parental consent.
    Your parent(s) or guardian(s) need to sign the attached PDF file and return it by replying directly to this email address.
    They can also send it to the following post address: ...
endif;
```

The above if statement will display the message only when the user is underaged
