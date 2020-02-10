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
		// console.log('test1');
		// if(typeof changedField !== 'undefined'){
		// 	console.log('test2');
		// 	if(changedField.closest('.super-signature')){
		// 		console.log('test3');
		// 		if( SUPER.has_hidden_parent(changedField)===false ) {
		// 			console.log('test4');
		// 			$(changedField).parents('.super-signature:eq(0)').find('.super-signature-canvas').signature('resize');
        //         }
        //     }
        // }
	};
	// After responsiveness changed, resize the canvas of the signature
	SUPER.refresh_signatures = function(){
		// var i, nodes = document.querySelectorAll('.super-signature-canvas');
		// for( i = 0; i < nodes.length; i++ ) {
		// 	// console.log(nodes[i]);
		// 	// console.log(nodes[i].querySelector('canvas'));
		// 	// var canvas = nodes[i].querySelector('canvas');

		// 	// 	// Set the canvas's resolution and size (not CSS).
		// 	// 	canvas.width = 100;
		// 	// 	canvas.height = 100;

		// 	// 	// // Fill the entire canvas with black.
		// 	// 	// const ctx = canvas.getContext("2d");
		// 	// 	// ctx.fillStyle = "#000000";
		// 	// 	// ctx.fillRect(0, 0, 100, 100);
		// 	// 	// ctx.fillStyle = "#ff0000";
		// 	// 	// ctx.fillRect(40, 40, 20, 20);

		// 	// 	// // create a pixel buffer for one transparent pixel
		// 	// 	// const imageData = ctx.getImageData(0, 0, 1, 1);
		// 	// 	// const pixel32 = new Uint32Array(imageData.data.buffer);
		// 	// 	// pixel32[0] = 0;
		// 	// 	// ctx.putImageData(imageData, 50, 50);

		// 	// 	// // Scale down the canvas preserving the transparent pixel's relative location.
		// 	// 	// $("#button").click(function() {
		// 	// 	// ScaleCanvas(canvas.width / 2, canvas.height / 2, .5, .5);
		// 	// 	// });

		// 	// 	// // Create a newly scaled canvas from the original and then delete the original.
		// 	// 	// function ScaleCanvas(width, height, xScale, yScale) {
		// 	// 	// 	const initialWidth = canvas.width;
		// 	// 	// 	const initialHeight = canvas.height;

		// 	// 	// // Get the true overlay's current image data.
		// 	// 	// const imageData = ctx.getImageData(0, 0, initialWidth, initialHeight);

		// 	// 	// // Create an in-memory canvas at the new resolution.
		// 	// 	// const newCanvas = $("<canvas>")
		// 	// 	// 	.attr("width", initialWidth)
		// 	// 	// 	.attr("height", initialHeight)[0];

		// 	// 	// // Draw the true overlay's image data into the in-memory canvas.
		// 	// 	// newCanvas.getContext("2d").putImageData(imageData, 0, 0);

		// 	// 	// // Update the size/resolution of the true overlay.
		// 	// 	// ctx.canvas.width = width;
		// 	// 	// ctx.canvas.height = height;

		// 	// 	// // Scale the true overlay's context.
		// 	// 	// ctx.scale(xScale, yScale);

		// 	// 	// // Draw the in-memory canvas onto the true overlay.
		// 	// 	// ctx.drawImage(newCanvas, 0, 0);
		// 	// 	// }
		// }
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
