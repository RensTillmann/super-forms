---
description: >-
  Why am I getting a "session expired" error message when submitting the form on
  my WordPress website? And how to resolve it?
---

# Session expired

If you are getting a "Unable to submit form, session expired!" error message, there could be several reasons for this. And there could be a couple of solution. Below are the solutions you can try.

1. First check if **Allow storing cookies** is set to **"Enabled"** under **Super Forms > Settings > Form Settings**. If it was set to disabled, change it to enabled and test the form again. If it still doesn't work continue to step 2.
2. You can skip this step if you are not loading the form via an iframe. If you do load your form via an iframe, and this is done from a different origin address you must disable the CSRF check under **Super Forms > Settings > Form Settings** by setting it to "Disabled". This isn't recommended, so it's better to not load forms via iframes from a different domain. Use this as a last resort, and only if you don't have any other choice.
3. Go to **Dashboard > Updates > "Check again"** and install any updates if available. After updating, make sure to empty any cache (if you are using a caching plugin) and temporarily disable the caching plugin to test out the form.

If you are still getting the message after completing above steps, feel free to submit a [support ticket](../../support.md).
