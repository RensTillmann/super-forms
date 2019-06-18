/* globals jQuery, SUPER */
(function($) { // Hide scope, no $ conflict
	"use strict";
	// Init Signature
	SUPER.init_signature = function(){
		$('.super-signature:not(.super-initialized)').each(function(){
			var $this = $(this);
			$this.addClass('super-initialized');
			var $canvas = $this.find('.super-signature-canvas');
			var $field = $this.find('.super-shortcode-field');
			if(!$canvas.children('canvas').length){
				$canvas.signature({
					thickness: $field.data('thickness'),
					change: function(event) { 
					    var $target = $(event.target);
					    if( $target.signature('isEmpty')==false ) {
					    	if( !$this.hasClass('not-empty') ) {
					    		$this.addClass('not-empty');
					    	}
							var $signature = $canvas[0].children;
							var $image_data_url = $signature[0].toDataURL("image/png");
							$field.val($image_data_url);
					    }else{
							$this.removeClass('not-empty');
					    }
			    	}
				});
				$canvas.signature('clear');
			}
		});
	};

	// Refresh Signature (Refresh the appearance of the signature area.)
	SUPER.refresh_signature = function($changed_field, $form, $skip, $do_before, $do_after){
		if($changed_field.parents('.super-signature:eq(0)')){
            var $skip = false;
            $changed_field.parents('.super-shortcode.super-column').each(function(){
                if($(this).css('display')=='none') {
                    $skip = true;
                }
            });
            var $parent = $changed_field.parents('.super-shortcode:eq(0)');
            if( ( $parent.css('display')=='none' ) && ( !$parent.hasClass('super-hidden') ) ) {
                $skip = true;
            }
            if( $skip===false ) {
				var $canvas = $changed_field.parents('.super-signature:eq(0)').find('.super-signature-canvas');
				$canvas.signature('resize');
            }
		}
	};
	
    // @since 1.2.2 - remove initialized class from signature element after the column has been cloned
    SUPER.init_remove_initialized_class = function($form, $unique_field_names, $clone){
        $clone.find('.super-signature.super-initialized').removeClass('super-initialized');
    };

    // @since 1.2.2 - clear signatures after form is cleared
    SUPER.init_clear_signatures = function($form){
        $form.find('.super-signature.super-initialized .super-signature-canvas').signature('clear');
    };

    // @since 1.2.2 - initialize dynamically added signature elements
    SUPER.init_signature_after_duplicating_column = function($form, $unique_field_names, $clone){
        if( typeof $clone !== 'undefined' ) {
        	$clone.find('.super-signature .super-signature-canvas').children('canvas').remove();
        	SUPER.init_signature();
    	}
    };

	jQuery(document).ready(function ($) {
	    
	    var $doc = $(document);
	    SUPER.init_signature();
		$doc.on('click', '.super-signature-clear', function() {
		    var $parent = $(this).parents('.super-signature:eq(0)');
		    var $canvas = $parent.find('.super-signature-canvas');
		    $canvas.signature('clear');
		    $parent.removeClass('not-empty');
		   	$parent.find('.super-shortcode-field').val('');
		});

		$doc.ajaxComplete(function() {
			SUPER.init_signature();
		});

	});

})(jQuery);	