---
description: >-
  Read, filter and export the Contact Entries that your WordPress forms have
  saved.
---

# Contact Entries

Every submission that a form saves becomes a **Contact Entry**. You will find them all in one list under `Super Forms` > `Contact Entries`.

{% hint style="info" %}
**Please note:** a form only saves entries when **Save data** is set to **Save as Contact Entry**, in `Form Settings` > `Form Settings`. A form set to **Do not save data** still sends its e-mails, but adds nothing to this list.
{% endhint %}

## Columns

Each row in the list is one submission:

* **Title** – the entry title. Click it to open the entry and read every value that was submitted, including the fields that the list does not show.
* **Status** – the entry status: Unread, Read, or one of the statuses you added yourself. You change it when you open the entry.
* **Based on Form** – the form the entry was submitted through.
* **IP-address** – only shown when you enable it (see below).
* **Field columns** – the field values you chose to show, for example e-mail address, phone number and message.
* **Date** – when the entry was submitted. WordPress puts the label **Last Modified** in front of it, because a Contact Entry is never a published post, but the time shown is the submission date and time. Editing an entry does not change it.

The form name, the IP-address and the field columns are set in `Super Forms` > `Settings` > `Backend Settings`:

* **Columns for contact entries** – one `fieldname|Field label` per line, for example `email|Email`.
* **Add the form name to the contact entry list** – shows the **Based on Form** column.
* **Add the IP address to the contact entry list** – shows the **IP-address** column.

## Filter the list

Above the list:

* **All forms** – choose a form to see only the entries submitted through that form. Choose **All forms** to see every entry again.
* **Date From** and **Date To** – choose the first and the last day of the period you are looking for.
* The search box – searches entry titles, the submitted values, the IP-address and the status.

Click **Filter** to apply the form and date selection. The search box has its own **Search Entries** button, and it works together with the filters.

{% hint style="info" %}
**Please note:** the date range only applies when both **Date From** and **Date To** are filled in. With one of them empty, the entries are not filtered on date at all.
{% endhint %}

To remove a filter, set the dropdown back to **All forms**, empty both date fields and click **Filter** again.

## Export the entries you see

The filters decide which entries are in the list, and only entries in the list can be selected. So filter first, then tick the entries you want and use **Export to CSV** at the top of the list. In the popup you choose which fields become columns in the file, and then you download it. [Import & Export](import-and-export.md#export-specific-entries-to-csv) describes that popup in more detail.

For a CSV of all entries, or of a whole date range in one go, use the export in `Super Forms` > `Settings` > `Export & Import`, described under [Export entries based on date range to CSV](import-and-export.md#export-entries-based-on-date-range-to-csv).
