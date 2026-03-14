---
description: >-
  Simple front-end (JavaScript) method to hide the "Eye" icon on your Listings
  based on specific user-role(s).
---

# Hide \`eye\` icon from Listings row based on user role

This is only a visual change, and not a server side prevention of being able to open the "View" modal. So keep this in mind when using this.

Put below PHP code in your child theme `functions.php`

```php
add_action('wp_footer', function(){
    if(is_admin()) return;
    $user = wp_get_current_user();
    $role = (is_user_logged_in() && !empty($user->roles)) ? $user->roles[0] : 'guest';
    ?>
    <script>
        (function(){
	    const role = '<?php echo esc_js($role); ?>';
	    document.addEventListener('DOMContentLoaded', function () {
	    if(role!=='administrator') {
	        document.querySelectorAll('.super-entries .super-entry .super-actions-menu > .super-view').forEach(el => el.remove());
            }
        });
    })();
    </script>
    <?php
});
```
