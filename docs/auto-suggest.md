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


### Custom Items
- Label
- Value

### Specific taxonomy
 - Taxonomy slug
 - Exclude a category
 - Hide empty categories
 - Based on parent ID
 - Retrieve Slug, ID or Title as value
  - Slug
  - ID
  - Title

### Specific posts
 - Post type (e.g page, post or product)
 - Exclude a post
 - Based on parent ID
 - Retrieve Slug, ID or Title as value
  - Slug
  - ID
  - Title

### Tags
 - Retrieve Slug, ID or Title as value
  - Slug
  - ID
  - Title

### CSV file
 - Upload CSV file 
 - Custom delimiter - Set a custom delimiter to seperate the values on each row
 - Custom enclosure - Set a custom enclosure character for values



- Added: Option to retrieve tags for autosuggest fields
- Added: Option to choose to return slug, ID or title for autosuggest for both post and taxonomy
- Added: Option to set delimiter and enclosure for dropdowns and autosuggest when using CSV file
- Added: Option to retrieve specific post types for dropdown and autosuggest