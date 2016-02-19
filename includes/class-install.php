<?php
/**
 * Installation related functions and actions.
 *
 * @author      feeling4design
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Install
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Install' ) ) :

/**
 * SUPER_Install Class
 */
class SUPER_Install {
    
    /** 
	 *	Activation
	 *
	 *	Upon plugin activation save the default settings
	 *
	 *	@since		1.0.0
	 */
	public static function install(){
		
        // Only save settings on first time
        // In case Super Forms is updated or replaced by a newer version
        // do not override to the default settings
        // The following checks if super_settings doesn't exist
        // If it doesn't we can save the default settings (for the first time)
        if( !get_option( 'super_settings' ) ) {

            // First retrieve all the fields and their default value
            $fields = SUPER_Settings::fields( null, 1 );
            
            // Loop through all the settings and create a nice array so we can save it to our database
            $array = array();
            foreach($fields as $k => $v){
                if(!isset($v['fields'])) continue;
                foreach($v['fields'] as $fk => $fv){
                    if((isset($fv['type'])) && ($fv['type']=='multicolor')){
                        foreach($fv['colors'] as $ck => $cv){
                            if(!isset($cv['default'])) $cv['default'] = '';
                            $array[$ck] = $cv['default'];
                        }
                    }else{
                        if(!isset($fv['default'])) $fv['default'] = '';
                        $array[$fk] = $fv['default'];
                    }
                }
                
            }
          
            // Now save the settings to the database
            update_option('super_settings', $array);
        }

    }

}
endif;