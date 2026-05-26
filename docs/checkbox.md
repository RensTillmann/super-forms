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

The [Conditional Logic](conditional-logic) setting on a checkbox field controls the **entire field** — it shows or hides all of its options at once. It is **not possible** to conditionally show or hide individual options within a single multi-option checkbox group.

### Workaround: use individual checkbox fields

If you need to conditionally show or hide specific options based on another field's value, replace the grouped checkbox with **separate, individual checkbox fields** — one per option — and apply conditional logic to each field independently.

**Example:** You have a "Services" checkbox group with options "Design", "Development", and "Hosting". You want to hide "Hosting" unless the user has selected "Business" from a plan dropdown.

Instead of one checkbox field with three options, create:

* A checkbox field named `service_design` with a single option "Design"
* A checkbox field named `service_development` with a single option "Development"
* A checkbox field named `service_hosting` with a single option "Hosting" — then apply conditional logic to **show** this field only when `plan == business`

Each individual checkbox field can then have its own independent conditional logic rule.

?> **Note:** When splitting options into individual checkbox fields, use the `{tag;label}` syntax in your emails or confirmation messages if you need to display the option label rather than its value.
