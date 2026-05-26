# Checkbox element

## Features & Options

- Option to define dropdown items manually with a `Label` and `Value` (when using a `{tag}` it will return the item value, when using `{tag;label}` it will return the label instead)
- Option to create a list based of a specific taxonomy (category) e.g: `category` for Post categories or `product_cat` (for WooCommerce categories)
  - Exclude categories by ID's
  - Hide empty categories
  - Retrieve categories based on a parent ID
  - Optionally choose to return the `Slug`, `ID`, `Title` or `Custom post meta data` as the item value based of the category
- Option to create a list based of a specific post type e.g: `post`, `page`, `product` or any other custom post type your site supports
  - Filter by post status e.g: `publish`, `inherit`, `pending`, `private`, `future`, `draft`, `trash`
  - Order posts by `Title`, `Date`, `ID`, `Author`, `Last modified`, `Parent ID`
  - Order Ascending or Descending
  - Exclude posts by ID's
  - Filter posts by specific taxonomy, example to create a filter based of slug for Post category:: `slug|cars|category|IN`
    - Set filter relation to `OR` or `AND`
  - Retrieve posts based on a parent ID
  - Optionally choose to return the `Slug`, `ID`, `Title` or `Custom post meta data` as the item value based of the category
    - When custom post meta data is choosen you can for instance return `_regular_price` (WooCommerce Product Price) as the item value
- Option to create a list with product attributes based of the slug e.g: `color` or `condition` etc. (only used when using woocommerce of course)
- Option to create a list with post tags
- Option to create a list with all wordpress users
  - Filter by user role
  - Exclude users by ID
  - Option to define the Label e.g: `[#{ID} - {first_name} {last_name} ({user_email})]` which would translate to `[#1845 - John Wilson (john@email)]`
  - Option to define the Value based on user data or user meta data
- Option to create a list based of an uploaded CSV file, where column `A` represents the `Label` and column `B` represents the `Value` of each item
  - Set custom delimiter/enclosure
- Option to create a list based of a meta field name for the current page/post
  - Compatible with ACF fields
- Specific database table
  - Databse table name e.g: `wp_mycustomtable`
  - Define returned Label per row e.g: to return the column **first_name** of each row use `{first_name}`
  - Define returned Value per row e.g: to return the column **ID** of each row use `{ID}`
- Option to disallow users to filter items
- Option to set Max/Min selections (for instance, this could allow users to select more than 1 item, or no more than 1)
- Option to exclude from email
  - Do not exclude from emails
  - Exclude from confirmation emails
  - Exclude from all emails
- Option to not save field in Contact Entry data
- Option to set [Conditional Logic](conditional-logic)

## Conditional visibility of individual checkbox options

Conditional logic applied to a checkbox field controls the **entire field** — all options appear or disappear together. There is no built-in way to hide a single option within a grouped checkbox based on other field values.

### Workaround: split into individual single-option checkbox fields

Create one checkbox field per option, each with a single item. You can then attach a separate conditional logic rule to each field.

**Example — show services based on a selected plan:**

| Field name | Label | Value | Shown when |
|---|---|---|---|
| `service_design` | Design | `yes` | Always |
| `service_development` | Development | `yes` | Plan = Professional or Enterprise |
| `service_hosting` | Hosting | `yes` | Plan = Enterprise |

Steps:

1. Add three separate **Checkbox** elements; give each a single option with value `yes`.
2. Name them `service_design`, `service_development`, and `service_hosting`.
3. Open the [Conditional Logic](conditional-logic) tab on `service_development` and add the rule:
   _Show this element when `plan` equals `professional` OR `plan` equals `enterprise`_
4. Do the same for `service_hosting` with the rule:
   _Show this element when `plan` equals `enterprise`_

Each field's `{tag}` will be empty (`""`) when the checkbox is not checked and `"yes"` when it is.

---

## Using email if statements with individual checkbox fields

Because each split checkbox field returns `"yes"` or `""`, you can use [email if statements](email-if-statements) to include service-specific content only when that option was selected.

```html
if({service_design}=='yes'):
    <strong>Design</strong> has been added to your order.
    Our design team will contact you within 1 business day.
endif;

if({service_development}=='yes'):
    <strong>Development</strong> has been added to your order.
    Please prepare your repository access for the development team.
endif;

if({service_hosting}=='yes'):
    <strong>Hosting</strong> has been added to your order.
    Server provisioning begins within 24 hours of payment confirmation.
endif;
```

You can also use `elseif` to provide a fallback when none of the boxes were ticked:

```html
if({service_design}=='yes'):
    Design selected.
elseif:
    Design not selected.
endif;
```

To check whether a conditionally hidden field was never shown at all, use the `isset()` helper (see [email if statements — checking if a field exists](email-if-statements?id=checking-if-a-field-exists)):

```html
if(!isset(service_hosting)):
    Hosting option was not available for the selected plan.
endif;
```

---

## Using foreach loops to display selected checkbox options

When you use a **grouped** checkbox field (multiple options in one field), you can loop over every selected item in your email body or an HTML element using [foreach loops](email-foreach-loops):

```html
foreach(services;loop):
    #<%counter%>: <%label%> (value: <%value%>)<br />
endforeach;
```

Replace `services` with your checkbox field name. `<%label%>` outputs the option label and `<%value%>` outputs the stored value. `<%counter%>` gives the 1-based position in the selected list.

**Practical example — summary of chosen services:**

```html
<p>You selected the following services:</p>
<ul>
foreach(services;loop):
    <li><%label%></li>
endforeach;
</ul>
```

?> **Note:** `foreach` on a checkbox field only iterates over the **selected** options, so unchecked options are automatically excluded.

---

## Merging individual checkbox fields into one combined field with a Variable field

When you split a grouped checkbox into individual fields (see above), you may still need a single combined value — for example to display a tidy summary in an email or to store a comma-separated list in a contact entry.

Use a **Hidden field** set to _Conditional Variable (dynamic value)_ and enumerate every possible combination of checked boxes as condition rows.

**Example — three service fields: `service_design`, `service_development`, `service_hosting`**

| Condition | Variable value |
|---|---|
| design=yes, dev=yes, hosting=yes | `Design, Development, Hosting` |
| design=yes, dev=yes, hosting=_empty_ | `Design, Development` |
| design=yes, dev=_empty_, hosting=yes | `Design, Hosting` |
| design=_empty_, dev=yes, hosting=yes | `Development, Hosting` |
| design=yes, dev=_empty_, hosting=_empty_ | `Design` |
| design=_empty_, dev=yes, hosting=_empty_ | `Development` |
| design=_empty_, dev=_empty_, hosting=yes | `Hosting` |

Steps:

1. Drag a **Hidden field** into the form and edit it.
2. In the **Type** dropdown choose _Conditional Variable (dynamic value)_.
3. Enable **Make field variable**.
4. For each row in the table above, add a condition group using the AND connector (all fields in a group must match simultaneously) and set the **Variable value** to the corresponding label string.
5. Name this hidden field `selected_services`.
6. Use `{selected_services}` anywhere tags are supported — email body, success message, HTML element, etc.

?> **Tip:** With _n_ individual checkbox fields there are 2ⁿ − 1 non-empty combinations. For 3 fields that is 7 rows. For 4 fields it is 15 rows. If the number of combinations grows unwieldy, consider referencing the individual tags directly in your email template with if statements instead.

You can verify the variable field live by adding an **HTML element** to your form containing `{selected_services}` — it will update in real time as the user checks and unchecks boxes.

---

## Using regex tags to reference checkbox fields by pattern

When you have multiple individual checkbox fields that share a naming convention — for example `service_design`, `service_development`, `service_hosting` — you can reference all of them at once inside a **Calculator** element using regex tags:

- `{service_*}` — matches any field whose name **contains** `service_`
- `{^service}` — matches any field whose name **starts with** `service`

This is most useful when you want to count how many services a user selected. Because each field returns `0` (unchecked) or `1` (checked), summing them gives the total count:

```
{^service}
```

Place that expression in a Calculator field. If the user checks two out of three service boxes the calculator will display `2`.

?> See [Calculation examples — Regex tags](calculator?id=calculation-examples) for the full reference of supported regex patterns.
