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
							if( !$this.hasClass('super-not-empty') ) {
								$this.addClass('super-not-empty');
							}
							var $signature = $canvas[0].children;
							var $image_data_url = $signature[0].toDataURL("image/png");
							$field.val($image_data_url);
						}else{
							$this.removeClass('super-not-empty');
						}
					}
				});
				$canvas.signature('clear');
			}
		});
	};

	// Refresh Signature (Refresh the appearance of the signature area.)
	SUPER.refresh_signature = function(changedField){
        if(typeof changedField !== 'undefined'){
            if(changedField.closest('.super-signature')){
                if( SUPER.has_hidden_parent(changedField)===false ) {
                    $(changedField).parents('.super-signature:eq(0)').find('.super-signature-canvas').signature('resize');
                }
            }
        }
	};
	
    // @since 1.2.2 - remove initialized class from signature element after the column has been cloned
    SUPER.init_remove_initialized_class = function($form, $unique_field_names, $clone){
        if($clone.querySelector('.super-signature.super-initialized')){
			$clone.querySelector('.super-signature.super-initialized').classList.remove('super-initialized');
		}
    };

    // @since 1.2.2 - clear signatures after form is cleared
    SUPER.init_clear_signatures = function(form){
        $(form).find('.super-signature.super-initialized .super-signature-canvas').signature('clear');
    };

    // @since 1.2.2 - initialize dynamically added signature elements
    SUPER.init_signature_after_duplicating_column = function(form, uniqueFieldNames, clone){
		var i,nodes;
		if( typeof clone !== 'undefined' ) {
			nodes = clone.querySelectorAll('.super-signature .super-signature-canvas > canvas');
			for( i=0; i < nodes.length; i++){
				nodes[i].remove();
			}
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
			$parent.removeClass('super-not-empty');
			$parent.find('.super-shortcode-field').val('');
		});

		$doc.ajaxComplete(function() {
			SUPER.init_signature();
		});

	});

})(jQuery);	
