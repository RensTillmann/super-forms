---
description: How to resolve file upload problems on your WordPress site
---

# File upload problems

If you are unable to upload files via your form the first thing you should try is to check if there are any updates available for the plugin, and update to the latest version if so.

Check if uploading a small file works. If this doesn't work, it is most likely due to incorrect permissions on the server, contact your hosting company to let them look at the folder permissions. If you are able to upload smaller files, it is most likely due to your PHP settings regarding file uploads. In this case you can try to increase the following values in your **php.ini**.

```
memory_limit = 256M
max_input_vars = 5000
upload_max_filesize = 2024M
post_max_size = 2024M
max_execution_time = 3300
max_input_time = 600
```

Check if you are able to upload files via your Media library from the WordPress menu. If this doesn't work the issue is with your WordPress site or server. You will need to contact your host and inform them about this issue.

If your server is returning a timeout error,  you can try to increase these settings instead:

```
max_execution_time = 3300
max_input_time = 600
```

Remember the following rules when changing above values:

1. To upload large files, `post_max_size` value must be larger than `upload_max_filesize`.
2. `memory_limit` should be larger than `post_max_size`

If you don't know how to change these values, ask your webmaster or your hosting company.

{% hint style="info" %}
When all the above checks and changes have been made, and you are still running into problems make sure to double check if the Super Forms file upload settings are properly configured under "Super Forms > Settings > [Secure file upload](../../quick-start/secure-file-uploads.md)". Also make sure to check the **server error log** for any information about the incident.
{% endhint %}
