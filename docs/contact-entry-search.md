# Contact Entry Search

This feature can be enabled for any `Text` field.

Edit the field and select `Contact entry search (populate form with data)` from the dropdown.

Check the `Enable contact entry search by title`, this allows you to lookup contact entries based on their title.

If you wish you can enable the option to save custom entry titles under `Form Settings > Form Settings > Enable custom entry titles`. You are allowd to use [{tags}](tags-system.md).

When contact entry search is enabled and a match was found, the form will be populated automatically with all the data from that entry.

Any fields in the current form that share the same field name will be populated unless you defined to skip field under `Fields to skip` setting.

The contact entry status, ID and Title are not part of the form data, that's why there are 2 special field names available in this situation to still populate them in your form if you need to.

Simply name your field `hidden_contact_entry_status` to retrieve the entry status, `hidden_contact_entry_id` to retrieve the entry ID or `hidden_contact_entry_title` to return the entry title.

