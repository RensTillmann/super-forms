# Data storage

?> Below you can find out where all the data and settings are stored by Super Form

- [Global Settings](where-are-the-global-settings-stored)
- [Contact Entries](where-are-the-contact-entries-stored)
- [Forms](where-are-the-forms-stored)
- [Individual Form Settings](where-are-the-individual-form-settings-stored)
- [Form Elements](where-are-the-form-elements-stored)
- [Form Translations](where-are-the-form-translations-stored)

## Where are the global settings stored?

The global settings are stored inside `wp_option` table under option key `super_settings`

## Where are the Contact Entries stored?

All entries are stored inside `wp_posts` table as post_type `super_contact_entry`

## Where are the Forms stored?

All forms are stored inside `wp_posts` table as post_type `super_form`

## Where are the individual form settings stored?

Individual form settings are stored inside `wp_postmeta` table under the meta key `_super_form_settings`

## Where are the form elements stored?

Individual form settings are stored inside `wp_postmeta` table under the meta key `_super_elements`

## Where are the form translations stored?

Individual form settings are stored inside `wp_postmeta` table under the meta key `_super_translations`
