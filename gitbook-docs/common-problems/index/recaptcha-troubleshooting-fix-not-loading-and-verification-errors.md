---
description: >-
  Learn how to resolve common Super Forms reCaptcha v2/v3 issues in WordPress:
  loading failures, verification errors, multiple-form conflicts, API key
  mistakes, and JavaScript conflicts.
---

# reCaptcha Troubleshooting – Fix “Not Loading” & Verification Errors

When using **Super Forms** on WordPress, you may occasionally encounter issues where the Google reCaptcha element:

* Doesn’t display on the page
* Returns the error **“Google reCAPTCHA verification failed!”**
* Malfunctions when you have **multiple forms** per page

This guide walks you through all the major causes and step-by-step fixes.

### 1. Verify & Regenerate Your API Keys

Many reCaptcha errors stem from invalid or misconfigured API keys.

1. **Locate Your Keys**
   * In WordPress admin, go to **Super Forms → Settings** and search for **“captcha”**, _or_
   * go to **Super Forms → Settings** **→ Form Settings** and scroll to reCaptcha API settings
2. **Regenerate Keys**
   * Visit the [Google reCaptcha admin console](https://www.google.com/recaptcha/admin).
   * Delete the existing key pair for your site.
   * Create a **new** v2 (Checkbox) or v3 key, ensuring you enter your site’s exact domain(s) under **Allowed domains**.
3. **Update in Super Forms**
   * Copy the **Site Key** and **Secret Key** into Super Forms’ settings fields.
   * **Save** and **clear** any caches (see § 4).

{% hint style="warning" %}
**Note:** If you switch from v3 to v2, you must generate a fresh v2 key pair—v2 and v3 keys are not interchangeable.
{% endhint %}

### 2. Check Domain & SSL Configuration

Google will refuse to load reCaptcha if the domain or SSL settings don’t match.

* **Domain Whitelist:**
  * In the reCaptcha console, ensure **exact** match of your site’s URL (e.g. `example.com` vs. `www.example.com`).
  * Add both variants if necessary.
* **HTTPS Requirement:**
  * reCaptcha requires a valid SSL certificate on your domain.
  * Mixed-content (HTTP scripts on HTTPS page) will be blocked by modern browsers.

### 3. Resolve JavaScript & Plugin Conflicts

reCaptcha injects its own JS; conflicts can prevent the widget from rendering.

1. **Console Errors:**
   * Open your browser’s Developer Tools → **Console**.
   * Look for errors like `grecaptcha is not defined` or `Blocked script`.
2. **Defer / Async Optimization Plugins:**
   * If you use WP Rocket, Autoptimize, or similar, _exclude_ `https://www.google.com/recaptcha/` from defer/minify lists.
   * Or disable JS optimization temporarily to confirm.
3. **Theme Hooks:**
   * Ensure your theme calls `<?php wp_head(); ?>` in the `<head>` and `<?php wp_footer(); ?>` before `</body>`.
   * Missing these hooks prevents plugin scripts from loading.

### 4. Caching & CDN Considerations

Caching layers may serve old JavaScript or block dynamic tokens.

* **Page Caching:**
  * Exclude pages with active forms from full-page caches (e.g. via WP Rocket “Never Cache URL”).
  * Or add query-strings to force fresh loads.
* **CDN Rules:**
  * If you use Cloudflare, create a **Page Rule** to _Disable Performance_ on your form pages so Cloudflare doesn’t strip query parameters from reCaptcha scripts.

<figure><img src="../../.gitbook/assets/image (97).png" alt=""><figcaption><p>Your WordPress form reCaptcha v2 and v3 API keys settings</p></figcaption></figure>
