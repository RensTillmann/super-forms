---
description: Adding a dropdown element to your WordPress form
---

# Dropdown

### What is a Dropdown element?

A dropdown element (also know as "select menu" or "select") is an element where a user can choose an item (or multiple items) from a list. For instance when you ask what color they want for their t-shirt, you could use a Dropdown element with different colors to choose from.

### How to add a Dropdown element?

On the builder page, open the Form Elements panel. Search for the `Dropdown` element, and drag it to your canvas. You can now edit the element and configure it as desired by following the settings and the descriptions.

### How to set a maximum or minimum selection?

By default a user can only select 1 item from the dropdown. If you require a user to be able to select multiple items or a maximum of X items, you can configure this by editing the Dropdown element, navigating to the **Advanced** section. And configuring the max/min selections.

### How to disable the option to filter items?

By default a user is able to filter dropdown items by typing while the dropdown is opened (focused). In some use cases this might be undesired. In that case you can disable the filter by enabling "Disallow users to filter items" as shown below:

<div align="left"><figure><img src="../../.gitbook/assets/image (55).png" alt="Disabling dropdown search/filter."><figcaption><p>Disabling dropdown search/filter.</p></figcaption></figure></div>

### Retrieving WordPress taxonomy as items

To populate the dropdown with taxonomy items e.g. Post categories (category), or WooCommerce product categories (product\_cat) you can set the **Retrieve method** to "Specific taxonomy (categories)". Simply enter the **Taxonomy slug**. Optionally you can exlude specific category ID's to be excluded from the list. And to hide any empty categories as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (57).png" alt="Retrieving taxonomy as items for your Dropdown element."><figcaption><p>Retrieving taxonomy as items for your Dropdown element.</p></figcaption></figure></div>

### Retrieving WordPress posts as items

To populate your dropdown with posts (or a custom post\_type) you can set the **Retrieve method** to "Specific posts (post\_type)". For example, you could return a list of WooCommerce products by entering `products`. You may also filter based on post status, and define a limit. Ordering by title, date or other parameters is also possible.

If you require to exclude a product you may enter the post ID's separated by comma's.

<div align="left"><figure><img src="../../.gitbook/assets/image (42).png" alt="Retrieving posts as items for your Dropdown element."><figcaption><p>Retrieving posts as items for your Dropdown element.</p></figcaption></figure></div>

When you wish to filter posts by a specific taxonomy then you can define each filter. For instance to only return products based on a the taxonomy `books` and `movies`, you can define a filter like so:

```
slug|books,movies|product_cat|IN
```

Alternatively you can filter based on tags like so:

```
slug|red,green|product_tag|IN
```

Operators you may use are `IN, NOT IN, AND, EXISTS` and `NOT EXISTS`

Since you are retrieving posts, you can also define what value you'd like to return for the dropdown items. For instance you can choose to return the **Slug**, **ID**, **Title** or some **Custom post meta data** (which allows you to return multiple values for a single item). Which allows you to use [Advanced tags](../../features/advanced/tags-system.md#advanced-tags) `{fieldname;2}` as shown below, which returns both the product ID and Product price. Which can be retrieve with `{fieldname;1}` and `{fieldname;2}` respectively.

<div align="left"><figure><img src="../../.gitbook/assets/image (83).png" alt="Returning custom meta data from posts for the Dropdown item value."><figcaption><p>Returning custom meta data from posts for the Dropdown item value.</p></figcaption></figure></div>

### Features & Options

This element shares the same options as the Checkbox element + the following extra feature:

* [Setting up Google Sheets for your Dropdown element.](../../features/advanced/wordpress-form-with-google-sheets-dropdown.md)
* Distance / Duration calculation (google directions) setup instructions
  * Return distance in meters
  * Return duration in seconds
  * Return distance text in km/meters (metric) or miles/feet (imperial)
  * Return duration text in minutes

