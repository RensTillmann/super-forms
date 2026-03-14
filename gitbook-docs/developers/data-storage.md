---
description: Where does Super Forms store all it's data inside the WordPress database?
---

# Data storage

{% hint style="info" %}
Below you can find out where all the data and settings are stored by Super Form
{% endhint %}

**Global settings** are stored inside `wp_option` table under option key `super_settings`

**Local form Settings** are stored inside `wp_postmeta` table under the meta key `_super_form_settings`

**Contact entries** are stored inside `wp_posts` table as post\_type `super_contact_entry`

All **forms** are stored inside `wp_posts` table as post\_type `super_form`

Individual form **Elements** are stored inside `wp_postmeta` table under the meta key `_super_elements`

Individual form **Triggers** are stored inside `wp_postmeta` table under the meta key `_super_triggers`

Individual form **translations** are stored inside `wp_postmeta` table under the meta key `_super_translations`

Any uploaded files are by default stored inside the following directory `wp-content/uploads/superforms/` you may changes this via [Super Forms > Settings > File Upload Settings](../quick-start/secure-file-uploads.md)
