# CSV Attachment

## About

With this you can attach a CSV file with all the form data to your emails.

## Quick start

Edit your form and navigate to `Form Settings > CSV Attachment`.

Check the `Send CSV attachment with form data to the admin email` option.

Define a name for the CSV file e.g `contact-details` (no need for the .csv extension)

If you need to exclude some fields form your CSV file, you can do so under `Exclude fields from CSV file`. Here you can put each field name on a new line e.g:

```js
first_name
last_name
birth_date
```

Optionally you can also set a custom `delimiter` (to seperate rows) and `enclosure` (to seperate values), but the default values should be the ones you'd normally need.
