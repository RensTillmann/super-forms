---
description: >-
  Troubleshoot and fix broken Super Forms datepicker styling caused by
  conflicting global jQuery UI CSS from other plugins.
---

# Datepicker Styling Conflict Issues

### **Description**

Some plugins (such as **YITH Booking & Appointments**) load **jQuery UI CSS globally on every page**, even when their functionality is not used.\
Since Super Forms also uses the native jQuery UI Datepicker, this can cause **conflicting styles**, resulting in broken or inconsistent datepicker styling on the front end.

Super Forms already namespaces and scopes its datepicker styles correctly.\
The issue occurs when **another plugin overrides these styles by loading global jQuery UI CSS**.

This guide explains how to identify the conflict and how to safely resolve it.

***

## **How to Fix Datepicker CSS Conflicts**

### **1. Identify the Problem**

If your Super Forms datepicker looks incorrect on the front end, inspect the page and look for a CSS file similar to:

```
/wp-content/plugins/yith-woocommerce-booking-premium/assets/css/jquery-ui/jquery-ui.min.css
```

This stylesheet contains generic jQuery UI styles loaded on _every page_, overriding Super Forms' custom styles.

***

## **Recommended Fix: Remove the Conflicting CSS Only on Affected Pages**

### **Option A — PHP: Unregister the CSS on specific pages (best practice)**

Add the snippet below to your theme’s `functions.php`, a custom plugin, or a Code Snippet:

```php
add_action( 'wp_enqueue_scripts', function () {
    if ( is_admin() ) {
        return;
    }

    // Run only on this specific page (change 'example-page' to your page slug)
    if ( ! is_page( 'example-page' ) ) {
        return;
    }

    global $wp_styles;

    if ( ! $wp_styles ) {
        return;
    }

    foreach ( $wp_styles->registered as $handle => $style ) {
        if ( ! empty( $style->src ) 
             && strpos( $style->src, 'yith-woocommerce-booking-premium/assets/css/jquery-ui/jquery-ui.min.css' ) !== false ) {

            wp_dequeue_style( $handle );
            wp_deregister_style( $handle );
        }
    }
}, 200 );
```

***

### **Option B — PHP: Remove based on URL path**

Useful for dynamic or nested URLs:

```php
add_action( 'wp_enqueue_scripts', function () {
    if ( is_admin() ) {
        return;
    }

    $current_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

    if ( $current_path !== '/example/path/' ) {
        return;
    }

    global $wp_styles;

    foreach ( $wp_styles->registered as $handle => $style ) {
        if ( ! empty( $style->src ) 
             && strpos( $style->src, 'yith-woocommerce-booking-premium/assets/css/jquery-ui/jquery-ui.min.css' ) !== false ) {
                 
            wp_dequeue_style( $handle );
            wp_deregister_style( $handle );
        }
    }
}, 200 );
```

***

## **Alternative Fix: Remove the CSS Using jQuery**

This is simpler if you already use Elementor Custom Code or Code Snippets PRO.

### **Option C — Remove the CSS via jQuery**

```html
<script>
jQuery(document).ready(function($) {
    $('link[href*="yith-woocommerce-booking-premium/assets/css/jquery-ui/jquery-ui.min.css"]').remove();
});
</script>
```

***

### **Apply conditionally to a specific page**

```html
<script>
jQuery(document).ready(function($) {
    if (window.location.pathname.indexOf('/example-page/') !== -1) {
        $('link[href*="yith-woocommerce-booking-premium/assets/css/jquery-ui/jquery-ui.min.css"]').remove();
    }
});
</script>
```

Replace `example-page` with your actual slug.

***

## **How to Add the Script**

#### **Using Elementor → Custom Code**

1. Go to **Elementor → Custom Code**
2. Create new snippet
3. Paste the script
4. Set _Display Conditions_ to specific pages
5. Publish
6. Clear cache

#### **Using Code Snippets Plugin**

* **PRO version:** supports page-level conditions
* **Free version:** use URL checking via JavaScript

***

## **Why Super Forms Is Not the Cause**

Super Forms:

* Uses custom datepicker classnames to prevent conflicts
* Loads styles only when a form is present
* Does **not** enqueue global jQuery UI styling
* Follows WordPress best practices for conditional loading

Conflicts happen when another plugin loads **unscoped, global CSS** for jQuery UI.

***

## **Summary**

| Issue                       | Cause                                         | Fix                                         |
| --------------------------- | --------------------------------------------- | ------------------------------------------- |
| Datepicker looks incorrect  | Another plugin loads global jQuery UI CSS     | Remove or block conflicting CSS             |
| Backend OK, frontend broken | Admin does not include conflicting stylesheet | Dequeue/remove stylesheet on specific pages |
| CSS override                | Global jQuery UI theme from booking plugin    | Conditional dequeue recommended             |

***

## **Final Tip**

If possible, contact the conflicting plugin developer and request that they load jQuery UI CSS **only on pages where their booking form is present**. This improves compatibility and site performance for all users.
