---
description: >-
  Translate your forms on the fly without requiring an additional plugin. You
  can translate elements and form settings so that everything can be read in a
  different language properly.
---

# Build In Translation System

## Defining translation for your form

Simply visit the `Translations` TAB when editing your form and define the languages required for your specific form as shown below.

<figure><img src="../../.gitbook/assets/define-translations-for-your-forms.gif" alt="Define languages for your form under the Translations TAB on the builder page."><figcaption><p>Define languages for your form under the Translations TAB on the builder page.</p></figcaption></figure>

## Enabling RTL (right to left) layout for your language

In case your language requires **RTL** (right to left layout) you can enable it per language individually:

<figure><img src="../../.gitbook/assets/mceclip4.png" alt="Enabling RTL (left to right) layout for your languages."><figcaption><p>Enabling RTL (left to right) layout for your languages.</p></figcaption></figure>

## Allowing users to switch to a different language manually

Enable the **Language Switch** if you want to display a dropdown above the form so that the user can change to a different language manually:

<figure><img src="../../.gitbook/assets/mceclip3.png" alt="Option to display a dropdown on the front-end so that users can switch to a different language manually."><figcaption><p>Option to display a dropdown on the front-end so that users can switch to a different language manually.</p></figcaption></figure>

<figure><img src="../../.gitbook/assets/translate-language-switcher (1).gif" alt="User manually switching language via the dropdown."><figcaption><p>User manually switching language via the dropdown.</p></figcaption></figure>

## Loading a specific language via a shortcode

You can also display a fixed language for your form by grabbing the **shortcode** e.g. `[super_form i18n="nl_NL" id="1234"]` by defining the language attribute e.g `en_GB`.

That way you can disable the **Language Switch**, and use a build in language plugin like WPML to display the correct form based on the language of the page.

<figure><img src="../../.gitbook/assets/mceclip7.png" alt="Language specific shortcode to display the form on your multilingual WordPress site"><figcaption><p>Language specific shortcode to display the form on your multilingual WordPress site</p></figcaption></figure>

## Demonstration of Translation form on the front-end

A front-end demo can be found here:

{% embed url="https://super-forms.com/example-forms/build-in-translation-system/" %}
Front-end demo of the build-in Translation system.
{% endembed %}

