/* globals jQuery, SUPER */
"use strict";
(function() { // Hide scope, no $ conflict
    jQuery(document).ready(function ($) {
        $(document).on('click', '.super-form-button > .super-button-wrap', function (e) {
            var args = {
                el: undefined,
                form: this.closest('.super-form'),
                submitButton: this,
                validateMultipart: undefined,
                event: e,
                doingSubmit: true
            };
            SUPER.validate_form(args);
            return false;
        });
        SUPER.init_tooltips(); 
        SUPER.init_distance_calculators();
        SUPER.init_super_form_frontend();
		$( document ).ajaxComplete(function(event, xhr, settings) {
			SUPER.init_super_form_frontend({
                event: event,
                xhr: xhr,
                settings: settings
            });
        });
        // Add space for Elementor Menu Anchor link
        if ( window.elementorFrontend ) {
            // eslint-disable-next-line no-undef
            if ( elementorFrontend.hooks && elementorFrontend.hooks.addAction ) {
                // eslint-disable-next-line no-undef
                elementorFrontend.hooks.addAction( 'frontend/element_ready/widget', function() {
                    SUPER.init_super_form_frontend();
                });
            }
        }
    });
})(jQuery);
