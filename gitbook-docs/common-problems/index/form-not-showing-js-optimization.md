---
description: >-
  Why is my form blank or not working after enabling JavaScript minify,
  combine or defer in an optimization plugin? And how to resolve it?
---

# Form not showing after JavaScript optimization

Optimization plugins can change how JavaScript loads on your site. Some settings rewrite, merge or delay the Super Forms scripts. When that happens, the form can stop working.

## Symptoms

You may see one or more of these:

* The page is blank where the form should be, even though the `[super_form]` shortcode is on the page.
* The form shows, but buttons, multi-part steps, conditional logic or calculations do nothing.
* The problem started after you enabled a JavaScript minify, combine, defer or delay setting, or after you installed an optimization plugin.
* The browser console shows errors such as `super_common_i18n is not defined` or `SUPER is not defined`. To open the console, open your browser's Developer Tools and go to **Console**.

## Step 1: Test without JavaScript optimization

First confirm that the optimization is the cause.

1. In your optimization plugin, turn off these JavaScript settings:
   * Minify JavaScript
   * Combine (aggregate, concatenate) JavaScript
   * Defer JavaScript
   * Delay JavaScript execution
2. Clear the cache of your optimization or caching plugin.
3. Clear your CDN cache, if you use a CDN.
4. Reload the form page and test the form again.

These settings are found in plugins such as Autoptimize, WP Rocket, LiteSpeed Cache and W3 Total Cache. Some hosting providers and CDNs have similar settings in their own dashboard.

If the form works now, the optimization was the cause. Go to step 2 to keep the optimization on for the rest of your site. If the form still does not work, the cause is something else. Turn your settings back on and see the last section of this page.

## Step 2: Exclude Super Forms from JavaScript optimization

Most optimization plugins have a field to exclude files or scripts from minify, combine, defer and delay. Add the entries below to each of those exclusion fields.

### Exclude the Super Forms JavaScript files

Exclude these folders:

* `/wp-content/plugins/super-forms/assets/js/`
* `/wp-content/plugins/super-forms/includes/extensions/pdf-generator/`
* `/wp-content/plugins/super-forms/add-ons/`

The first folder holds the main form scripts. The second folder holds the PDF Generator scripts. The third folder holds the scripts of the built-in add-ons, such as Calculator, Signature and Popups.

These paths are the same on Super Forms 6.3 and 6.4. If your site uses a different plugins folder, adjust the start of the path.

{% hint style="info" %}
Some plugins only accept part of a file name or path. In that case, `/plugins/super-forms/` matches all Super Forms files.
{% endhint %}

### Exclude the Super Forms inline scripts

Super Forms prints a few small inline scripts in the page. They hold settings and text that the form scripts need. Each one creates one of these variables:

* `super_common_i18n`
* `super_elements_i18n`
* `super_frontend_common_i18n`

If your optimization plugin combines or delays inline JavaScript, add these three names to its inline script exclusion field.

The name `super_common_i18n` also matches the inline script that loads the PDF Generator fonts for non-Latin text.

### Do not combine inline JavaScript on form pages

Combining inline JavaScript can change the order in which scripts run. If you cannot exclude the inline scripts above, turn off the setting that combines (aggregates) inline JavaScript. If your plugin supports it, you can turn it off only on the pages that show a form.

## Step 3: Clear caches and test again

1. Save the settings in your optimization plugin.
2. Clear the cache of your optimization or caching plugin.
3. Clear your CDN cache, if you use a CDN.
4. Reload the form page and test the form again.

## reCAPTCHA

If the form shows but the reCAPTCHA does not load, also exclude the Google reCAPTCHA script from optimization. See [reCaptcha Troubleshooting](recaptcha-troubleshooting-fix-not-loading-and-verification-errors.md).

## Still not working?

If the form still does not show with all JavaScript optimization turned off, feel free to submit a [support ticket](../../support.md). Mention which optimization or caching plugin you use, and copy any errors from the browser console.
