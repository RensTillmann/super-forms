---
description: >-
  With the autosuggest field you can let users filter through a set of
  predefined values by typing in a text field, yet still allow them to freely
  enter a value on your WordPress form.
---

# Autosuggest

The concept of **autosuggest** is to create a **Google Search** like Text field where users can search for a specific value, but also allow to enter their own value.&#x20;

When a user starts typing into the text field, it will search for matches based on the entered characters and return a list/dropdown to the user. The user can then click on the suggestion to automatically fill the field with this value.

You can find the `Autosuggest` field under the **Form Elements** section. Alternatively you can add a Text field and enable the autosuggest feature manually.

When you edit the Autosuggest element you can configure it's values and options under the Autosuggest section from the dropdown. Here you can define the **Filter logic** and **Retrieve method**.

## Filter logic options

* Contains ??
* Starts with ..%
* Exact match ==
* Contains ?? (case sensitive)
* Starts with ..% (case sensitive)
* Exact match == (case sensitive)

## Retrieve method options

* [Custom items](autosuggest.md#custom-items)
* [Specific taxonomy (categories)](autosuggest.md#specific-taxonomy)
* [Specific posts (post\_type)](autosuggest.md#specific-posts)
* [Tags (post\_tag)](autosuggest.md#tags)
* [CSV file](autosuggest.md#csv-file)
* Users (wp\_users)
* Product attribute (product\_attributes)
* Current Author meta data
* Current Page or Post meta data
* Current Page or Post terms (based on specified taxonomy slug)
* Specific database table

## Custom Items

This retrieve method allows you to enter your own predefined values by hand. It allows to enter a `Label` and `Value` for each option.

The `Label` represents the searchable string and will be visible for the user.

The `Value` will be used as the field value that will be stored upon a user selecting the filtered option and will not be visible to the user. The `Value` will be saved in the Contact Entry and used in the Admin E-mail and Confirmation E-mail send. In case no match was found, the entered string of the user will be saved instead, meaning the user is free to enter whatever they like for the field. If you do not want a user to have this permission you should consider using a Dropdown Field instead.

## Specific taxonomy

This retrieve method allows to filter a specific taxonomy (category) based on it's **slug** name.

* For **Post Categories** the slug name would `category`.
* For **WooCommerce Product Categories** the slug name would be `product_cat`.

To exclude categories from the list you can enter each category ID separated by comma's under the **Exclude a category** option.

If you wish to hide empty categories you can do this by enabling the **Hide empty categories** option.

Whenever you need to retrieve child categories based on a parent category you can enter the category parent ID under the **Based on parent ID** option. You will have the ability to either return the `Slug`, `Title` or `ID` of the category.

## Specific posts

This retrieve method allows to filter on posts based on the given `Post type`.

* For **Pages** the post type would be `page`
* For **Posts** the post type would be `post`
* For **WooCommerce Products** the post type would be `product`

To exclude posts from the list you can enter each post ID separated by comma's under the **Exclude a post** option.

Whenever you need to retrieve child post based on a parent post you can enter the post parent ID under the **Based on parent ID** option. You will have the ability to either return the `Slug`, `Title` or `ID` of the post.

## Tags

This retrieve method allows you to filter on post tags.

You will have the ability to either return the `Slug`, `Title` or `ID` of the tag.

## CSV file

This retrieve method allows you to filter based on a CSV file you uploaded.

This option works the same as the [Custom Items](/broken/pages/Ss4cGlMwua87zD6X5ztJ) retrieve method, except that you will upload a CSV file that will represent the options to filter on.

{% hint style="warning" %}
**Important:** The CSV file only requires two columns in order to work properly. The first column represent the **Value**, and the second column it's **Label**. If you don't need separate values for the label and value, then you can simply define one column.
{% endhint %}

Depending on the CSV you might need to set a custom delimiter or enclosure, this can be optionally changed if required.

An **example CSV** file with a list of products and their color and price: [https://shorturl.at/adtKT](https://shorturl.at/adtKT). This CSV example uses multiple values for the `value` column.

This allows you to retrieve the selected product title, color and price separately by using advanced tags: `{fieldname;1}`, `{fieldname;2}` `{fieldname;3}` respectively. You can use these tags inside [Conditional logic](../../features/advanced/conditional-logic.md), [Variable fields](../../features/advanced/variable-fields.md) and to populate them into fields inside your form if needed. You can even use a validation method to compare user entered data with values from the CSV file. In case you need to confirm some personal details such as "birthdate", "address" etc.

See [this demo form](https://super-forms.com/example-forms/search-csv-file-and-populate-form-with-advanced-tags/) to see it in action.
