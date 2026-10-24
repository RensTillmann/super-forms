---
description: >-
  How to stop the page from scrolling to the next field when a visitor presses
  Tab in a WordPress form.
---

# Stop the page from scrolling when tabbing through fields

By default, when a visitor presses Tab to move to the next field, Super Forms scrolls that field to the middle of the screen. On long forms, or when the form sits between other content on the page, that jump can be unwanted. One line of JavaScript turns the automatic centering off:

```javascript
SUPER.disable_tab_scroll = true;
```

Add the line under `Super Forms` > `Settings` > `Custom JS` (without the `<script>` tags), or in any script on the page that runs before the visitor starts tabbing. The next field still receives focus in the usual order; only the automatic centering is skipped.

{% hint style="info" %}
Only a strict `true` turns the scroll off. A string such as `'yes'` is ignored and the field is still centered.
{% endhint %}

The browser itself still scrolls the page when the next field is outside the visible area. That is the normal behavior for keyboard focus, not the Super Forms centering.

Removing the line, or setting it to `false`, gives you the default behavior back. The flag is supported on the 6.3 (LTS) and 6.4 (Beta) lines from the release that adds it; earlier versions ignore it, so leaving the line in place does no harm.
