---
description: >-
  Displaying data/content conditionally inside your HTML element and or email
  body with the build in "if statements" for your WordPress forms
---

# If statements

{% hint style="info" %}
If statements can be used inside your [HTML element](../../elements/html-elements/html-raw.md) and inside your [email body content](../basic/confirmations-emails.md). You can also combine it within [foreach loops](foreach-loops.md).
{% endhint %}

### What is an email if statement? <a href="#what-is-an-email-if-statement" id="what-is-an-email-if-statement"></a>

With the build in **if statements** feature you will have more flexibility as to how your email body and or HTML element is generated based on user input in your WordPress form.

In short it allows you to (just like any programming language) construct your own if statements inside your HTML element and or email body content.

### Construction of "if statements" explained <a href="#construction-of-quotif-statementsquot-explained" id="construction-of-quotif-statementsquot-explained"></a>

Each **if statement** has a so called `operator` to do a so called "Comparison Operation". Inside our WordPress forms (and email body content) we can use one of the following operators for our statements:

* `==` (equal to)
* `!=` (not equal)
* `>` (greater than)
* `<` (less than)
* `>=` (greater than or equal to)
* `<=` (less than or equal to)
* `??` (contains)
* `!??` (does not contains)

### How to create my own if statements? <a href="#how-to-create-my-own-if-statements" id="how-to-create-my-own-if-statements"></a>

A basic example of this would be to compare a field value with a hardcoded value, for instance if you want to display some additional information only to users who are named **John** we could do the following:

```html
if({first_name}=='John'):
    Your custom HTML here...
endif;
```

When you require to output a default text whenever `first_name` is not equal to **John** we can use the `elseif` statement as shown below:

```html
if({first_name}=='John'):
    This text is only for John :)
elseif:
    This text is for everyone who isn't named John
endif;
```

### Practical example use cases <a href="#practical-example-use-cases" id="practical-example-use-cases"></a>

_Below we will cover some practical use cases that you can apply to your own application(s)._

#### Use case 1: Show extra information based on a selected package: <a href="#use-case-1-show-extra-information-based-on-a-selected-package" id="use-case-1-show-extra-information-based-on-a-selected-package"></a>

A simple use case would be to add some text that is specific for a package that a user selected inside your WordPress form. Let's say the package name is called `package_1` and it's value when chosen by the user is `daily_backups`. When the user selected this package to be included in their order, we would want to display some important information regarding how these backups are being created by the company.

Inside our [Confirmation email body](../basic/confirmations-emails.md) (delivered to the user who filled out the WordPress form) we can enter the following if statement:

```html
if({package_1}=='daily_backups'):
    Your backups are being stored daily on 3 other independent servers.
    By default your server has a fallback server that will be activated whenever the server is down for more than 2 min.
    If you need more technical information about how we process backups read our <a href="domain.com/faq">FAQ</a>
endif;
```

The above if statement will output the content depending on your own needs whenever the tag `{package_1}` is equal to `daily_backups`

#### Use case 2: Ask for parental consent when underaged: <a href="#use-case-2-ask-for-parental-consent-when-underaged" id="use-case-2-ask-for-parental-consent-when-underaged"></a>

Another example could be to check if a user is underaged or not and display some information about needing a parental consent:

```html
if({age}<18):
    Because you are underaged we need a parental consent.
    Your parent(s) or guardian(s) need to sign the attached PDF file and return it by replying directly to this email address.
    They can also send it to the following post address: ...
endif;
```

The above if statement will display the message only when the user is underaged

### Checking if a field exists <a href="#checking-if-a-field-exists" id="checking-if-a-field-exists"></a>

When you are using [conditional logic](conditional-logic.md) in your form, in some cases a field might not be set due to it being conditionally hidden.

In these cases you might want to check if a field exists (is set). You can do so by using the `isset()` method. For example:

```html
if(!isset(company_name)):
    The field named `company_name` does not exists, this registration is not a business registration.
endif;

if(isset(tax_id)):
    The Tax field was conditionally shown, this is a business registration.
elseif:
    This is a regular customer registration.
endif;
```
