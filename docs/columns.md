# Column / Grid Element

This guide will explain what a column is, what features it has, when it's smart to use a column and how to add it.

* [What is a column?](#what-is-a-column)
* [What features does a column have?](#what-features-does-a-column-have)
* [When to use a column?](#when-to-use-a-column)
* [How to add a column?](#how-to-use-a-column)

## What is a column?

In short a column is a section of your form where you insert a set of elements.
A column will determine the layout of the elements that are inside the column.
You could see a column as a table (or a grid system).
You can put an unlimited amount of columns next to eachother.
This makes creating forms really flexible because you now do not have limitation on how many elements you need next to eachother.
A column can also be inserted into another column with unlimited nesting possibility.

## What features does a column have?

Each column has the following features:

* [Conditional Logic](#conditional-logic) - conditionally hide/show column based on other field values
* [Column Visibility](#column-visibility) - will make column invisible on front-end
* [Dynamic Add More](#dynamic-add-more) - allows users to add/duplicate a set of fields
* [Update Conditions Dynamically](#update-conditions-dynamically) - keeps conditional logic on the column itself when **Dynamic Add More** is enabled
* **Hide on mobile devices** - Based on form width (breaking point = 760px)
* **Keep original size on mobile devices (prevents 100% width)** - Based on form width (breaking point = 760px)
* **Hide on mobile devices** - Based on screen width (breaking point = 760px)
* **Keep original size on mobile devices (prevents 100% width)** - Based on screen width (breaking point = 760px)
* **Force responsiveness on mobile devices (always 100% width)** - Based on screen width (breaking point = 760px)
* **Styling options** - background (image, color, opacity), custom padding, positioning (static,absolute etc.)

### Conditional Logic:

Because conditional logic can be applied on almost all elements this part is covered in the [Conditional Logic](conditional-logic) guide.

?> **NOTE:** when a column or any element is **conditionally hidden** the fields will not be included in emails (if enabled) and will not be saved under contact entries (if enabled).

### Column Visibility:

Whenever you want to hide a set of fields on the front-end you can enable the option to make the column invisible.
In order to do this click on the :pencil2: icon of the column to start editing the column.

The `Element Settings & Options` section will open now. Make sure you are under the `General` TAB.

Now set the **Make column invisible** option to: Yes

Click the `Update Element` button to apply the changes to the column.
You will notice that on the form builder the column will still be visible, this is because you otherwise wouldn't be able to edit it anymore.
When you preview your form by clicking the `Preview` button at the top right of the page you will notice that the column and it's content will no longer be visible.

?> **NOTE:** when a column is **invisible** the fields will still be included in emails (if enabled) and saved under contact entries (if enabled).
If you still require specific fields inside a hidden column to be excluded, you can change this per field. To do this edit the field and go to `Advanced` TAB and change the **Exclude from email** and **Do not save field in Contact Entry** options accordingly.

### Dynamic Add More:

In some cases you want to allow the end user to add a new set of fields dynamically by clicking a :heavy_plus_sign: button.

**Example:** You have a team registration form and the teams vary from 2 up to 8 persons. For each person you need their first and last name.

What you will do is add a 1/1 column, enable the **Enable Add More** option. Then add 2 text fields to the column 1 for first name, 1 for last name.
Now when you preview the form you will notice the :heavy_plus_sign: button which allows you to add another set of fields.
So it basically duplicates the column on the front-end.

If you need it to have a maximum duplications of 8 (which is the case in our example) you can change the **Limit for dynamic fields (0 = unlimited)** option.

### Update Conditions Dynamically:

When you have enabled [Dynamic Add More](#dynamic-add-more) and you have elements inside the column that are using
conditional logic based on a field inside this same column and you require these conditional logic to keep working
on dynamically added columns you will have to enable the **Update conditional logic dynamically** option.

This will make sure that whenever a column is duplicated by the user on the front-end, it's conditions reference will be update to it's own column rather than the first original column.

## When to use a column?

A column comes in handy when you ever need to apply the same [conditional logic](conditional-logic) on each of those elements.
This prevents you from having to add the same conditions on each element one by one.
Now you can just add the [conditional logic](conditional-logic) to the column and it will affect all the elements inside it.

The same method can be applied whenever you need to hide a set of fields at once.

If you are working on a large form and you have a set of fields that need to be duplicated and adjusted only based on their field names
it would be a good idea to put these fields in a column. You can then duplicate the column which will then duplicate all of it's elements inside it automatically.

Another useful thing about columns is that when you are working with larger forms you can easily collapse the column on the builder page.
This will free up a lot of working space whenever you have finished this part of the form.
This will make it also a lot easier to drag & drop new elements into the correct place of your form.
When you otherwise would have to scroll down or up all the way, you now probably only have to scroll a little bit or not at all :wink:

## How to add a column?

In order to add a column you can open up the **Layout Elements** section on the builder page.
You will see 4 elements, 3 of them are columns the other one is a so called [Multi-part](multi-parts), which is not a column.
The first one is a 100% width column or so called 1/1 column. The second column is a 50% width (1/2). The third is a 33% width (1/3) column.
When added you can still change the column sizes to one of the following ratio's:

* 1/1 (100%)
* 1/2 (50%)
* 1/3 (33%)
* 1/4 (25%)
* 1/5 (20%)

Once you have added your column you can add any element inside it with the exception to **Multi-parts**.
