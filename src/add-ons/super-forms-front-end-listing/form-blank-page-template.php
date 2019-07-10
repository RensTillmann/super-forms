<?php
/**
 * The template for displaying forms on a blank page
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="profile" href="https://gmpg.org/xfn/11" />
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php
    $entry_id = absint($_GET['super-fel-id']);
    $_GET['contact_entry_id'] = $entry_id; // Must be set to populate the form with the entry data
    // Check if invalid Entry ID
    if( $entry_id==0 ) {
        SUPER_Common::output_error(
            $error = true,
            $msg = esc_html__( 'No entry found with ID:', 'super-forms' ) . ' ' . $entry_id 
        );
        die();
    }
    // Check if this entry does not have the correct post type, if not then the entry doesn't exist
    if( get_post_type($entry_id)!='super_contact_entry' ) {
        SUPER_Common::output_error(
            $error = true,
            $msg = esc_html__( 'No entry found with ID:', 'super-forms' ) . ' ' . $entry_id 
        );
        die();
    }
    // Seems that everything is OK, continue and load the form
    $entry = get_post($entry_id);
    $form_id = $entry->post_parent; // This will hold the form ID
    // Now print out the form by executing the shortcode function
    echo SUPER_Shortcodes::super_form_func( array( 'id'=>$form_id ) );
    wp_footer();
    ?>
</body>
</html>
