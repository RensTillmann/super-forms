---
description: What you should understand before you start building forms
---

# First time setup

{% hint style="danger" %}
**Important:** Before creating your first form, please read this article in full. It contains important information about some of the core Super Forms functionalities which shouldn't be overlooked. Especially understanding the [differences between Global settings and Form settings](first-time-setup.md#h_01evm3d182pegrfy3r2zxyvka0) and the configuration of [Secure file uploads](secure-file-uploads.md).
{% endhint %}

After you have [installed Super Forms](installation.md) ([**super-forms.zip**](https://super-forms.com/download-super-forms-stable.php)) you will probably want to change a couple of settings depending on your use case. In regards to settings in Super Forms, there are two different types of settings. Your "local" **Form Settings**, and your **Global settings**.

* [Global settings](first-time-setup.md#h_01evm3ctsvapkbst5j5kq9bk3y)
* [Difference between Global settings and Form settings](first-time-setup.md#h_01evm3d182pegrfy3r2zxyvka0)&#x20;
* [File Upload Settings](first-time-setup.md#h_01evyfcgswjf598zgamfqdg85w)

### Global settings <a href="#h_01evm3ctsvapkbst5j5kq9bk3y" id="h_01evm3ctsvapkbst5j5kq9bk3y"></a>

Global settings are used to determine what settings you prefer whenever you create a brand new form from scratch. Any settings you have defined here will be used for a newly created form.

You can find your global settings under **Super Forms > Settings** menu.

When you first start using Super Forms, you will want to make sure you went over the most important settings and update them to your liking.

{% hint style="info" %}
**Note:** It is important to understand the difference between Global settings and Form settings. Changing your global settings will not affect existing forms as long as the existing forms did not share the same global setting at the time of saving the form. This means that in most cases you should and will be changing your Form settings (on the form builder page) instead of Global settings.
{% endhint %}

There are a couple of exceptions such as when setting up **SMTP server** settings. These type of settings are not form specific and are settings that all of your forms will be using. A couple of examples are:

* Global Overriding
* SMTP server
* [File Upload Settings](first-time-setup.md#h_01evyfcgswjf598zgamfqdg85w)
* Custom CSS
* Custom JS

### Global Overriding

{% hint style="danger" %}
Global Overriding can be used to hardcode a specific setting to always be exactly what you defined it to be, no matter what an individual form might use. There are only a couple of settings which you can overridden for the time being. It is generally a good idea not to use Global Overriding unless you really have to.
{% endhint %}

### Difference between Global settings and Form settings <a href="#h_01evm3d182pegrfy3r2zxyvka0" id="h_01evm3d182pegrfy3r2zxyvka0"></a>

When creating a form for the first time it will populate it's settings with those defined under **Super Forms > Settings** (which are your global settings).

Upon saving the form it will compare any settings with your global settings. Any settings that are equal will not be stored. While those that didn't equal will be stored. This allows you to have many forms with different settings but also settings they share. You could then change them under your global settings to so that all of those forms would reflect that change and point to the global setting. This is totally optional and in many cases you would just want to setup your global settings upon plugin installation and "never" look back. It's just much easier to make form changes on forms individually unless you are managing many different forms.

Again, in general it is good practice to setup your global settings upon plugin installation, and to make changes to settings on form level simply by editing your form and navigating to the "Form Settings" tab.

### File Upload Settings <a href="#h_01evyfcgswjf598zgamfqdg85w" id="h_01evyfcgswjf598zgamfqdg85w"></a>

When you are going to use file upload elements inside your forms, it is a good idea to first head over to **Super Forms > Settings > File Upload Settings**. You can change many options in regards to how your files are being processed, uploaded and deleted.

{% hint style="warning" %}
It is highly recommended to read the [Secure file uploads](secure-file-uploads.md) article for more information on how to configure secure file uploads.&#x20;
{% endhint %}

<figure><img src="https://webrehab.zendesk.com/hc/article_attachments/360015849737/mceclip0.png" alt="WordPress form file upload settings"><figcaption><p>WordPress form file upload settings</p></figcaption></figure>
