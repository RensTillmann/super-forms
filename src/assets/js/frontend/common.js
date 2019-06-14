/* globals jQuery, SUPER */
"use strict";
(function() { // Hide scope, no $ conflict
	
    jQuery(document).ready(function ($) {
    
	    var $doc = $(document);
	    
        $doc.on('click', '.super-form-button > .super-button-wrap', function (e) {
            var $form = $(this).parents('.super-form:eq(0)');
            SUPER.conditional_logic(undefined, $form, true );
            SUPER.validate_form( $form, $(this), undefined, e, true );
            return false;
        });

	    SUPER.init_tooltips(); 
	    SUPER.init_distance_calculators();
	    SUPER.init_super_form_frontend();
		
		$( document ).ajaxComplete(function() {
			SUPER.init_super_form_frontend();
		});
	   
	});

})(jQuery);
