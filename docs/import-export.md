# Import & Export

This guide will walk you through the steps to import and export:

* [Global settings](#global-settings)
* [Form settings](#form-settings)
* [Contact entries](#contact-entries)
* [Forms](#forms)

## Global Settings

If you have 2 WordPress websites and you wish to import the same settings you have for **website 1** to **website 2** you can simply do this by going to `Super Forms` > `Settings` on **website 1**.

On this page navigate to the `Export & Import` TAB.

Here you will see a code named "**Export Settings**".

Now simply copy (_CTRL+C_ ) this code and navigate to the same location on **website 2** and paste (_CTRL+V_ ) it in the "**Import Settings**" section.

Click on the `Import Settings` button to override the current settings with the one you just pasted.

Super Forms will now save the settings and you will now have the same global settings that you have on **website 1**.

## Form Settings

To export just the form settings, you can go to (on the form builder page) `Form Settings` > `Export & Import`

A new section should appear where you can simply click `Export`.

A file will be downloaded (this is your export file).

If you'd like to import these settings into another form you can do this by going to the same location on that other form and upload the file.

Now you can choose to import both the `Settings` and `Elements`, in our case we will only want to select `Settings`.

This way your form elements will not be replaced with those from the exported form.

## Contact Entries

There are multiple ways to export Contact Entries

* [Export all entries to XML](#export-all-entries-to-xml)
* [Export entries based on date range to CSV](#export-entries-based-on-date-range-to-csv)
* [Export specific entries to CSV](#export-specific-entries-to-csv)

### Export all entries to XML

?> This method will download all of your Contact Entries as an **XML file**.

This is the standard way WordPress would export Post types.
Since Contact Entries are a custom post type you can do this by going to `Tools` > `Export`.
By default WordPress has not installed this tool, so you will have to install it first.
After installing the Export tool you can choose post types from a list.

Since we are going to export Contact Entries we can choose **Contact Entries** from the list and click on the `Download Export File` button.
This will download all of Super Forms **Contact Entries** in an **XML** format.
This XML file can now be used to import it into any other WordPress website.
Of course you can also use the XML file for other purposes.

### Export entries based on date range to CSV

?> This method allows you to export your Contact Entries as a **CSV file** based on a selected date range.

Navigate to `Super Forms` > `Settings` > `Export & Import`.
Scroll down to the **Export Contact Entries** section.
Optionally choose your date range (or leave blank to export all contact entries).
Optionally change the **Delimiter** and **Enclosure** characters.
Click on the `Export Contact Entries to CSV` button to download the CSV file.

This CSV file can be used to import Contact Entries on your other WordPress website.
To do this navigate to `Super Forms` > `Settings` > `Export & Import`.
Scroll down to the **Import Contact Entries** section.
Click the `Select CSV file` button and choose the .csv file.

After you have selected the file, you will have to map all the columns accordingly so Super Forms knows what type of field it should be saved as, it's label and the unique field name.
After mapping all the columns you can optionally choose to skip the first row of the CSV file.
This comes in handy whenever your CSV file has heading columns that do not require to be imported.
(which is the case when you exported the CSV via `Export Contact Entries to CSV`).

_Of course you can also use the CSV export file to do anything else, for instance import it into your MailChimp lists or any other program's that support CSV importing._

### Export specific entries to CSV

?> This method allows you to export specific selected Contact Entries to a **CSV file**.

In case you need only a couple Contact Entries to be exported to a CSV file you are able to this by going to `Super Forms` > `Contact Entries`.
Select the entries that you wish to export, for instance the first 6 Contact Entries.

At the action bar click on the `Export to CSV` button.
This will open up a popup where you can choose only the fields you wish to export to the CSV file.
Each field will become it's own column in the CSV file.
After selecting the fields you require in your CSV file click on the `Export` button.
_Your CSV file will now be downloaded._

## Forms

There are 2 ways to export Forms

* [Export specific forms](#export-specific-forms)
* [Export all forms](#export-all-forms)

### Export specific forms

?> This method allows you to export just one form.

_This is probably one of the most used and easiest ways to import your forms cross-site.
This method comes in handy if you just need to export and import a couple of forms and not all of them._

In order to do this you have to go to the form builder page of the form you wish to export and import.
You can do this by navigating to `Super Forms` > `Your Forms` and clicking on the form.

Click on `Form Settings` TAB, and select `Export & Import` from the dropdown menu.

Now click `Export` (this should save the export file to your computer)

The exported file will hold both the form elements and the form settings.

### Export all forms

?> This method allows you to export all forms at once.

To export your forms navigate to `Super Forms` > `Settings` > `Export & Import`.
Scroll down to the **Export Forms** section.
Click the `Export Forms` button to start downloading the file which will contain all forms.

After the file has been downloaded successfully you can import the file on your new site by clicking the `Select Import file` button.
Now search the import file on your computer and upload it.

The import will now run. Give it a couple of minutes (depending on the amount of forms) to process.
After the import finished your forms should have been imported and available for usage.
