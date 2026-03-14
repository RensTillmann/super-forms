---
description: Understanding and configuring file upload settings for your WordPress forms
---

# Secure file uploads

## Secure file uploads <a href="#h_01evyfxh09d6g79c75vgx9mjyj" id="h_01evyfxh09d6g79c75vgx9mjyj"></a>

By default Super Forms will not display files uploaded through any of your forms inside the WordPress Media Library.

When you update to Super Forms **v4.9.500+** any files that were previously uploaded will no longer be visible in the Media Library.

If you require these to be visible you will have to go to **Super Forms > Settings > File Upload Settings** and uncheck **Do not show file uploads in the Media Library**.

<div align="left"><figure><img src="https://webrehab.zendesk.com/hc/article_attachments/360015849977/mceclip0.png" alt="Enable or disable the option to not display uploaded files in the Media Library"><figcaption><p>Enable or disable the option to not display uploaded files in the Media Library</p></figcaption></figure></div>

If you want to store your files **securely** you will have to make sure that the files are uploaded outside the root folder of your site. To do this you can define a custom upload path relative to your site root directory.

### Example upload directories <a href="#h_01evyfx4p5e323djpgzbv9thhq" id="h_01evyfx4p5e323djpgzbv9thhq"></a>

#### Examples for secure file upload directories are:

| Path:                  | Use case:                                                       |
| ---------------------- | --------------------------------------------------------------- |
| `../my-private-dir`    | To store files just one directory above your site root          |
| `../../my-private-dir` | In case your site is running inside a subdirectory in your root |

{% hint style="warning" %}
**Note:** On some servers it isn't possible for Super Forms to create the private directory due to permissions, in that case contact your provider for a solution.
{% endhint %}

#### Examples for public file upload directories are:

| Path:                           | Use case:                                                     |
| ------------------------------- | ------------------------------------------------------------- |
| `my-public-dir`                 | Upload folder directly inside the root of your site           |
| `subdir/my-public-dir`          | Upload folder inside a subdirectory in the root of your site  |
| `wp-content/uploads/superforms` | The default directory that Super Forms uses out of the box is |

## Automatically delete uploaded files from server <a href="#h_01evyfwy3bs08b5bvy0mgx5zpq" id="h_01evyfwy3bs08b5bvy0mgx5zpq"></a>

You can optionally delete any uploaded files automatically from your server after a form submission by enabling the option **Delete files from server after the form was submitted**.

When this setting is enabled, any E-mails will still contain the file as an attachment (unless you defined to exclude the file from emails on the file upload element itself), but these files will no longer be stored on your server.

## Delete associated files after deleting a contact entry <a href="#h_01evyfwsanc8x2g9fkdkjs9s8m" id="h_01evyfwsanc8x2g9fkdkjs9s8m"></a>

To automatically delete any associated files from the server after deleting a Contact Entry you can enable  **Delete associated files after deleting a Contact Entry**.

{% hint style="success" %}
Once you configured your Global settings and File upload settings, you should be ready to create your first form. Continue to the next article to start building!
{% endhint %}
