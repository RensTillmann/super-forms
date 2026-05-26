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

### The limitation

Conditional logic applied to a checkbox field controls the visibility of the **entire field** — all of its options are shown or hidden together. There is no built-in way to show or hide a single option within a grouped checkbox field based on another field's value.

### Workaround: split into individual checkbox fields

To show or hide specific options independently, replace the grouped checkbox field with **separate single-option checkbox fields** — one per option. You can then apply standard conditional logic to each individual field.

**Example — Services offered based on selected plan:**

Suppose you have a `plan` radio-button field (options: `starter` / `professional`) and you want to offer three services as checkboxes, where "Hosting" is only available on the Professional plan.

1. Create three individual checkbox fields, each with a single option:
   - Field name `service_design`, option Label: `Design`, Value: `design`
   - Field name `service_development`, option Label: `Development`, Value: `development`
   - Field name `service_hosting`, option Label: `Hosting`, Value: `hosting`

2. Leave `service_design` and `service_development` without any conditional logic (always visible).

3. On `service_hosting`, open **Conditional Logic** and set:
   - Action: **Show** when condition is met
   - Condition: `plan` **== equals** `professional`

The Hosting checkbox now appears only when the Professional plan is selected.

### Using the selections in emails and summaries

Because each service is now a separate field you can reference them individually in email templates and HTML elements:

```
Selected services: {service_design}, {service_development}, {service_hosting}
```

When a field is conditionally hidden it is excluded from the submission entirely, so its tag returns an empty string and the corresponding value is not saved to the contact entry.

## Merging individual checkbox fields into one combined field with a Variable field

When you have split a grouped checkbox into individual fields (see above), you often need a single **combined value** for use in emails, calculators, or further conditional logic. A [Variable field](variable-fields) (hidden field set to *Conditional Variable* mode) lets you merge those separate values into one.

### How it works

A variable field evaluates a list of conditions in order and updates its value whenever a condition is met. By enumerating every possible combination of checked fields you can produce a clean, comma-separated summary in a single hidden field.

### Step-by-step example

Continuing the Services example above (`service_design`, `service_development`, `service_hosting`):

1. Drag a **Hidden field** onto the canvas and give it a unique name, e.g. `selected_services`.

2. In the field editor, change the type to **Conditional Variable (dynamic value)** and enable **Make field variable**.

3. Add one condition row per combination you need to handle. Use **All (when all conditions matched)** and list each field that must be checked (value `== equals` the option value) or unchecked (value `== equals` empty):

   | Condition(s) | Variable value |
   |---|---|
   | `service_design` == `design` AND `service_development` == `` AND `service_hosting` == `` | `Design` |
   | `service_design` == `` AND `service_development` == `development` AND `service_hosting` == `` | `Development` |
   | `service_design` == `` AND `service_development` == `` AND `service_hosting` == `hosting` | `Hosting` |
   | `service_design` == `design` AND `service_development` == `development` AND `service_hosting` == `` | `Design, Development` |
   | `service_design` == `design` AND `service_development` == `` AND `service_hosting` == `hosting` | `Design, Hosting` |
   | `service_design` == `` AND `service_development` == `development` AND `service_hosting` == `hosting` | `Development, Hosting` |
   | `service_design` == `design` AND `service_development` == `development` AND `service_hosting` == `hosting` | `Design, Development, Hosting` |

4. The trigger for **When to Trigger** should be set to **All (when all conditions matched)** so each row only fires for its exact combination.

5. Reference the merged value anywhere in your form using the tag `{selected_services}`.

### Keeping it manageable

For **two checkboxes** you have 3 non-empty combinations; for **three** you have 7; for **four** you have 15 (2ⁿ − 1 rows total). When you have many options, consider whether you actually need a single merged field or whether referencing each tag individually in the email template is simpler:

```
Design:      {service_design}
Development: {service_development}
Hosting:     {service_hosting}
```

For complex pricing or discount logic based on selected services, a [Calculator field](calculator) that reads each individual checkbox tag is often cleaner than building every combination into a variable field.

> **Tip:** You can verify your variable field is updating correctly by placing a `{selected_services}` tag inside an **HTML element** — it updates live as the user checks or unchecks options.
