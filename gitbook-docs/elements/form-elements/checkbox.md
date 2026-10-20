---
description: Adding a checkbox element to your WordPress form
---

# Checkbox

### What is a Checkbox element?

A Checkbox element shows a list of options. The user can select one or more of them. For instance, when you ask which extra services someone wants, you could use a Checkbox element with one option per service.

### How to add a Checkbox element?

On the builder page, open the Form Elements panel. Search for the `Check box` element, and drag it to your canvas. You can now edit the element and configure it as desired by following the settings and the descriptions.

To limit how many options a user can select, edit the element and go to the **Advanced** section. Set **Max characters/selections allowed** and **Min characters/selections allowed**. Set them to `0` to remove the limit.

### Options and values

Each option has a **Label** and a **Value**. The label is shown to the user. The value is what you retrieve with the {tag} of the field.

When the user selects more than one option, the tag `{fieldname}` returns all selected values separated by a comma. To retrieve the labels instead, use `{fieldname;label}`. See [How to use tags?](../../features/advanced/tags-system.md#how-to-use-tags)

You can save multiple values per option by separating them with a semicolon `;`. For example, a Checkbox named `extras` with these options:

* `Gift wrap` / `gift_wrap;5`
* `Greeting card` / `card;2.5`

Now `{extras}` returns the first value (`gift_wrap`), and `{extras;2}` returns the second value (`5`). See [Advanced tags](../../features/advanced/tags-system.md#advanced-tags).

### Using a Checkbox in the Calculator

In the [Calculator](calculator.md) element, a Checkbox tag returns the **sum** of the values of all selected options.

With the `extras` example above, `{extras;2}` returns `7.5` when both options are selected. A total price for a quantity field named `qty` with a price of 10 per item could look like this:

```
{qty}*10 + {extras;2}
```

With a quantity of 3 and both options selected, the result is **37.5**. With no option selected, `{extras;2}` is `0` and the result is **30**.

Keep the following in mind:

* The values you use in a calculation must be numbers. If one of the selected values is not a number, the whole tag counts as `0`. In the example above, `{extras}` (the first value) would always give `0`.
* A Checkbox that is hidden by conditional logic counts as `0`.
* To add up several Checkbox elements at once, use a regex tag. For example, `{extra_*}` adds up all fields whose name contains `extra_`. See [Calculation examples](calculator.md#calculation-examples).

### Showing or hiding individual options

Conditional logic applies to the whole Checkbox element. You cannot show or hide a single option.

If some options should only be visible in certain cases, split them into separate Checkbox elements and apply conditional logic to each of them. For the steps, see [Can I hide individual checkbox, radio or dropdown options?](../../features/advanced/conditional-logic.md#can-i-hide-individual-checkbox-radio-or-dropdown-options)

To combine the separate fields into one value again, see [Combining several fields into one value](../../features/advanced/variable-fields.md#combining-several-fields-into-one-value).

### Using the selected options in emails

* Use an [if statement](../../features/advanced/if-statements.md) with the `??` (contains) operator to show text when a specific option is selected. For example: `if({extras}??'gift_wrap'):`
* When you split a Checkbox into separate fields, a field that was hidden by conditional logic is not submitted. Use `isset()` to check if it exists. See [Checking if a field exists](../../features/advanced/if-statements.md#checking-if-a-field-exists).
* Use a [foreach loop](../../features/advanced/foreach-loops.md#how-to-loop-over-selected-checkboxes-and-dropdown-items) to list every selected option with its label and value.
