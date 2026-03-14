---
description: How to display a form via a popup or modal on your WordPress website
---

# Popups

### Creating a form and enabling the popup

Create your form and define the popup settings under **Form Settings > Popup Settings** ([see advanced popup settings below](popups.md#advanced-popup-settings)).

### Adding the popup to a single page or multiple pages

Grab the form shortcode and add it on the page(s) where you want to display the popup (form).

<figure><img src="../../.gitbook/assets/image (88).png" alt="Grab the form shortcode"><figcaption><p>Grab the form shortcode</p></figcaption></figure>

### Adding the popup to all pages

If you want to display the popup on all of your pages, it is recommended to use a **Text Widget** with the form shortcode and to put it into the footer of your website. This way the popup and form will be loaded on all pages and can be triggered on all pages. Via your WordPress main menu **Appearance > Menu** you can also add a **Custom Link** with it's URL set to `#super-popup-XXX` to open the Popup from your site menu.

### Opening the popup via a link or button

There are two different methods to open the popup manually. In the below examples simply replace `XXX` with your form ID. When using these methods you will probably want to configure the Popup settings on your form so that the popup does not display on page load.

{% hint style="danger" %}
**Important:** don't forget to put the actual form shortcode \[super-forms id="XXX"] on the page(s) where you wish to display a "Open form popup" button.
{% endhint %}

#### Open popup with URL

```html
<a href="#super-popup-XXX">Open the Popup</a>
```

#### Open popup with shortcode

```html
[super-popup id=XXX]Click Here for Extra Bonus[/super-popup]
```

### Advanced popup settings

<div align="left"><figure><img src="../../.gitbook/assets/image (92).png" alt="Advanced popup settings for WordPress forms"><figcaption><p>Form popup settings for WordPress website</p></figcaption></figure></div>
