<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if( !class_exists('SUPER_Data_Access') ) :

class SUPER_Data_Access {

    /**
     * Retrieve the canonical contact-entry payload.
     *
     * @param int $entry_id
     * @return mixed
     */
    public static function get_entry_data( $entry_id ) {
        $entry_id = absint($entry_id);
        if( $entry_id===0 ) {
            return '';
        }
        return get_post_meta( $entry_id, '_super_contact_entry_data', true );
    }

    /**
     * Persist the canonical contact-entry payload.
     *
     * @param int   $entry_id
     * @param array $data
     * @return bool|int
     */
    public static function update_entry_data( $entry_id, $data ) {
        $entry_id = absint($entry_id);
        if( $entry_id===0 || !is_array($data) ) {
            return false;
        }
        return update_post_meta( $entry_id, '_super_contact_entry_data', wp_slash($data) );
    }
}

endif;
