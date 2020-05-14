# File Uploads

## File Upload Element

The `File Upload` element is very flexible and has many options to fullfill any usecase. Some of the features are:

* Easily define allowed extensions e.g: `jpg|jpeg|png|gif|pdf`
* Set a maximum file size in `MB`
* Total upload limit for all files combined in `MB`
* Define custom placeholder for the file upload button
* Set maximum and minimum files required for validation
* Define a custom error message
* Optionally exclude from confirmation email
* Optionally exclude from all emails
* Optionally do not save file to Contact Entry
* Use custom image button instead of text
* Set a tooltip when user hovers over the file upload element

!> **Alert:** By default any files uploaded via your forms will **NOT** be visible in your Media Library (for privacy reasons). If you wish to display them inside your Media Library then you can uncheck the following setting `Super Forms > Settings > File Upload Settings` > `Do not show file uploads in the Media Library`

?> **Notice:** You can optionally delete any uploaded files automatically from your server after a form submission via `Super Forms > Settings > File Upload Settings` by enabling the option: `Delete files from server after the form was submitted`. Any E-mails will still contain the file as an attachment (unless you defined to exclude the file from emails of course) but will no longer be stored on your server.

?> **Notice:** You can optionally choose to delete associated files after deleting a Contact Entry via `Super Forms > Settings > File Upload Settings` > `Delete associated files after deleting a Contact Entry`

## Secure File Uploads

Because of more strict privacy rules it is very important to store any files uploaded by your users safe.
That is why we have implemented a couple of extra options so that you have more control over what should happen with files uploaded via your forms.

Going forward by default Super Forms will no longer show files uploaded through any of your forms inside the **Media Library**.
When you update to the latest version of Super Forms `v4.9.5+` any files that were previously uploaded will no longer be visible in the **Media Library**.
If you require these to be visible you will have to go to `Super Forms > Settings > File Upload Settings` and uncheck the following setting: `Do not show file uploads in the Media Library`.

If you want to store your files securely you will have to make sure that the files are uploaded outside the root folder of your site. To do this you can go to `Super Forms > Settings > File Upload Settings` and define a custom upload path relative to your site root directory.

**Examples for secure file upload directories are:**

`../my-private-dir` _to store files just one directory above your site root_

`../../my-private-dir` _in case your site is running inside a subdirectory in your root_

!> **NOTE:** On some servers it isn't possible for Super Forms to create the private directory due to permissions, in that case contact your provider for a solution.

**Examples for public file upload directories are:**

`my-public-dir` _upload folder directly inside the root of your site_

`subdir/my-public-dir` _upload folder inside a subdirectory in the root of your site_

**The default directory that Super Forms uses out of the box is:**

`wp-content/uploads/superforms`
