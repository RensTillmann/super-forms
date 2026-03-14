---
description: Installing the Super Forms plugin .zip file on your WordPress site
---

# Installation

1. Download the [**super-forms.zip**](https://super-forms.com/download-super-forms-stable.php) (or checkout the [BETA version](../developers/beta-version.md) instead)
2. Login to your WordPress site
3. Navigate to **Plugins > Add New**
4. Click **\[Upload Plugin]** and click **Choose File**
5. Upload the super-forms.zip you downloaded at step 1
6. Click **\[Install Now]**

{% hint style="success" %}
Wait for the .zip file to be uploaded and for the plugin to be installed, this might take a while depending on your hosting so be patient.
{% endhint %}

{% hint style="info" %}
If for some reason the installation fails, you can try to upload the plugin via FTP by unzipping the file, and uploading the `super-forms` folder into your WordPress plugins folder (normally located at `/wp-content/plugins`. If you are not sure how to do this, you can [**create a ticket**](../support.md) and we will help you with this. Just make sure you provide a temporary WordPress admin login, and if possible the FTP credentials.
{% endhint %}

After you successfully installed the plugin you should now see a **Super Forms** menu item in the WordPress Dashboard menu.

{% hint style="warning" %}
On some servers it might be required to re-save your permalinks via **Settings > Permalinks > Save Changes.** This may resolve some common issues with exporting/downloading data within Super Forms.
{% endhint %}

In case you do not see **Super Forms** from the WordPress menu, try to reload the page. If that doesn't help, double check that the plugin is both _installed_ and _activated_ via the _Plugins > Installed Plugins_ page. If so, it might be that you are not logged in with an Administrator account. Or you do not have the correct permissions for your account. To resolve this you will require to login as administrator.

{% hint style="success" %}
Now that Super Forms is installed you can continue to the next guide which covers how to [register your account](registration.md).
{% endhint %}
