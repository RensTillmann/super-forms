# Auto Suggest

The concept of the **auto suggest** is to create a **Google Search** like [Text field](text) where users can search for a specific value, but also allow to enter their own value.
The auto suggest feature of the **Text field** comes in handy whenever you want to allow the submitter to filter through a set of possible values themselves by typing into the text field.
This allows the user to still enter a custom value, but also choose from a **predefined** set of values/options.
It will display matches based on the entered characters and shows the user a list/dropdown where the user can click on to automatically fill the field with this value.

To enable the Auto Suggest feature for your Text field go ahead and edit your Text field by clicking on the :pencil2: icon.
Under the `Element Settings & Options` section choose the `Auto Suggest` option from the dropdown.
Enable the Auto Suggest setting and choose on of the below **Retrieve methods** (to define these so called **predefined** values):

* [Custom items](#custom-items)
* [Specific taxonomy](#specific-taxonomy)
* [Specific posts](#specific-posts)
* [Tags](#tags)
* [CSV file](#csv-file)

## Custom Items

This retrieve method allows you to enter your own predefined values by hand. It allows to enter a `Label` and `Value` for each option.

The `Label` represents the searchable string and will be visible for the user.

The `Value` will be used as the field value that will be stored upon a user selecting the filtered option and will not be visible to the user.
The `Value` will be saved in the [Contact Entry](contact-entry) and used in the [Admin E-mail](admin-email) and [Confirmation E-mail](confirmation-email) send.
In case no match was found, the entered string of the user will be saved instead, meaning the user is free to enter whatever they like for the field.
If you do not want a user to have this permission you should consider using a [Dropdown Field](dropdown) instead.

## Specific taxonomy

This retrieve method allows to filter a specific taxonomy (category) based on it's **slug** name.

* For **Post Categories** the slug name would `category`.
* For **WooCommerce Product Categories** the slug name would be `product_cat`.

To exclude categories from the list you can enter each category ID seperated by comma's under the **Exclude a category** option.

If you wish to hide empty categories you can do this by enabling the **Hide empty categories** option.

Whenever you need to retrieve child categories based on a parent category you can enter the category parent ID under the **Based on parent ID** option.
You will have the ability to either return the `Slug`, `Title` or `ID` of the category.

## Specific posts

This retrieve method allows to filter on posts based on the given `Post type`.

* For **Pages** the post type would be `page`
* For **Posts** the post type would be `post`
* For **WooCommerce Products** the post type would be `product`

To exclude posts from the list you can enter each post ID seperated by comma's under the **Exclude a post** option.

Whenever you need to retrieve child post based on a parent post you can enter the post parent ID under the **Based on parent ID** option.
You will have the ability to either return the `Slug`, `Title` or `ID` of the post.

## Tags

This retrieve method allows you to filter on post tags.

You will have the ability to either return the `Slug`, `Title` or `ID` of the tag.

## CSV file

This retrieve method allows you to filter based on a CSV file you uploaded.

This option works the same as the [Custom Items](#custom-items) retrieve method, except that you will upload a CSV file that will represent the options to filter on.

The CSV file only requires 2 columns in order to work properly. If only 1 column was used, the `Label` and `Value` will share the same values. When 2 columns are used the first column represent the `Label`, and the second column it's `Value`.

Depending on the CSV you might need to set a custom delimiter or enclosure, this can be optionally changed if required.
