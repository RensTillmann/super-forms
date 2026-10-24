---
description: >-
  How to stop the page from scrolling to the next field when a visitor presses
  Tab in a WordPress form.
---

# Stop the page from scrolling when tabbing through fields

Pressing Tab moves the visitor to the next field, and Super Forms brings that field into view. How far the page moves depends on the Super Forms version:

* **6.3 (LTS):** every Tab scrolls the next field to the middle of the screen, also when that field is already visible.
* **6.4 (beta):** the form only scrolls when the next field is out of view or close to the bottom of the screen. That field is then centered, and a field above the screen is moved to the top.

On long forms, or when the form sits between other content on the page, that movement can be unwanted. One line of JavaScript turns the automatic scrolling off:

```javascript
SUPER.disable_tab_scroll = true;
```

Add the line under `Super Forms` > `Settings` > `Custom JS` (without the `<script>` tags), or in any script on the page that runs before the visitor starts tabbing. The next field still receives focus in the usual order; only the scrolling is skipped. On 6.4 (beta) the flag also skips the scroll-when-needed behavior described above.

{% hint style="info" %}
Only a strict `true` turns the scrolling off. A string such as `'yes'` is ignored and the field is scrolled as usual.
{% endhint %}

The browser itself still scrolls the page when the next field is outside the visible area. That is the normal behavior for keyboard focus, not the scrolling Super Forms adds.

Removing the line, or setting it to `false`, gives you the default behavior back. The flag is supported on the 6.3 (LTS) and 6.4 (beta) lines from the release that adds it; earlier versions ignore it, so leaving the line in place does no harm.
