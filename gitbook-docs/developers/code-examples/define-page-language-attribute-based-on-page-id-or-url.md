---
description: >-
  Setting the WordPress page language attribute for a specific page based on the
  ID or URL
---

# Define page language attribute based on page ID or URL

{% hint style="info" %}
**Note:** The below filter hook is only useful whenever you are not using a translation plugin
{% endhint %}

When you are not using any language plugin to translate your website but still want to have a specific page in a different language (other than your main site language), you will have to change the `<html>` language attribute based on the current page URL. This is important so that whenever a user visits a page that is different from your main site language the browser won't try to translate the page (again) including your form. Let's say your site is in English by default, but you also have a translated version of your form in Deutsch and French. When the user visists the page `/de/contact` or `/fr/contact` the browser will still think that the page is in your main language (English) because the language attribute would still be set to `en`. This can cause unexpected translations being done by the browser. The only way to avoid this is to make sure the correct language attribute for these pages is set accordingly. A filter hook on how to do this can be found below.

```php
function f4d_language_attributes($lang){
    if(strpos(get_permalink(), home_url().'/de/')!==false){
        // When user is on the German site
        return "lang=\"de-DE\"";
    }
    if(strpos(get_permalink(), home_url().'/fr/')!==false){
        // When user is on the French site
        return "lang=\"fr-FR\"";
    }
    if(get_permalink()===home_url().'/specific-url-in-deutch/'){
        // When on a Deutsch page
        return "lang=\"de-DE\"";
    }
    if(get_permalink()===home_url().'/specific-url-in-french/'){
        // When on a French page
        return "lang=\"fr-FR\"";
    }
    // Return default language attribute
    return $lang;
}
add_filter('language_attributes', 'f4d_language_attributes');
```
