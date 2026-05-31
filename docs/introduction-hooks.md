# What are hooks?

Hooks in WordPress essentially allow you to change or add code without editing core files. They are used extensively throughout WordPress and Super Forms and are very useful for developers.

There are two types of hook: actions and filters.

* [Action Hooks](action-hooks) allow you to insert custom code at various points (wherever the hook is run).
* [Filter Hooks](filter-hooks) allow you to manipulate and return a variable which it passes (for instance a product price).

## Using hooks

If you use a hook to add or manipulate code, you can add your custom code to your theme's `functions.php` file.

### Using action hooks

To execute your own code, you hook in by using the action hook `do_action('action_name');`. Here is where to place your code:

```php
add_action( 'action_name', 'your_function_name' );
function your_function_name() {
    // Your code
}
```

### Using filter hooks

Filter hooks are called using `apply_filter( 'filter_name', $variable );`. To manipulate the passed variable, you can do something like the following:

```php
add_filter( 'filter_name', 'your_function_name' );
function your_function_name( $variable ) {
    // Your code
    return $variable;
}
```

?> With filters, you must always return a value even if you did not change it.
