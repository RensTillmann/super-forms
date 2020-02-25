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
							if( !$this.hasClass('super-filled') ) {
								$this.addClass('super-filled');
							}
							var $signature = $canvas[0].children;
							var $image_data_url = $signature[0].toDataURL("image/png");
							$field.val($image_data_url);
						}else{
							$this.removeClass('super-filled');
						}
					}
				});
				$canvas.signature('clear');
			}
		});
	};

	// Refresh Signature (Refresh the appearance of the signature area.)
	SUPER.refresh_signature = function(changedField){
		console.log('test1');
		if(typeof changedField !== 'undefined'){
			console.log('test2');
			if(changedField.closest('.super-signature')){
				console.log('test3');
				if( SUPER.has_hidden_parent(changedField)===false ) {
					console.log('test4');
					$(changedField).parents('.super-signature:eq(0)').find('.super-signature-canvas').signature('resize');
                }
            }
        }
	};

	
	// After responsiveness changed, resize the canvas of the signature
	SUPER.refresh_signatures_timeout = null;

	SUPER.refresh_signatures = function(){
		if (SUPER.refresh_signatures_timeout !== null) {
			clearTimeout(SUPER.refresh_signatures_timeout);
		}
		SUPER.refresh_signatures_timeout = setTimeout(function () {
			var i,x,y, 
				nodes = document.querySelectorAll('.super-signature-canvas'),
				minWidth = 0,
				minHeight = 0,
				width = 0,
				height = 0;

			for( i = 0; i < nodes.length; i++ ) {
				// Make drawing smaller by 50% (just as an example)
				var canvasWrapper = nodes[i];
				var json = $(canvasWrapper).signature('toJSON');
				var lines = JSON.parse(json).lines;
				if(lines.length===0) {
					//debugger;
					json = $(canvasWrapper).signature('toDataURL');
					$(canvasWrapper).signature('draw', json);
					if(canvasWrapper.parentNode.querySelector('.super-shortcode-field').value!==''){
						canvasWrapper.closest('.super-signature').classList.add('super-filled');
					}else{
						canvasWrapper.closest('.super-signature').classList.remove('super-filled');
					}
					continue;
				}
				var canvasWrapperWidth = canvasWrapper.offsetWidth;
				var canvasWrapperHeight = canvasWrapper.offsetHeight;
				var canvas = nodes[i].querySelector('canvas');
				var canvasWidth = canvas.offsetWidth;
				var ratio = (canvasWidth/canvasWrapperWidth)*100;
				console.log(canvasWrapperWidth, canvasWidth, ratio);
				canvas.width = canvasWrapperWidth;
				canvas.height = canvasWrapperHeight;

				var newLines = [];
				for(x=0; x < lines.length; x++){
					for(y=0; y < lines[x].length; y++){
						if(!newLines[x]) newLines[x] = [];
						if(!newLines[x][y]) newLines[x][y] = [];
						if(canvasWrapperWidth < canvasWidth){
							ratio = canvasWidth/canvasWrapperWidth;
							console.log(ratio);
							newLines[x][y][0] = lines[x][y][0]/ratio;
							newLines[x][y][1] = lines[x][y][1]/ratio;
						}else{
							ratio = canvasWrapperWidth/canvasWidth;
							console.log(ratio);
							newLines[x][y][0] = lines[x][y][0]*ratio;
							newLines[x][y][1] = lines[x][y][1]*ratio;
						}
						// Check if signature becomes bigger than the canvas wrapper
						width = newLines[x][y][0];
						height = newLines[x][y][1];
						if(minWidth < width) minWidth = width+2; // plus 2 for some margin
						if(minHeight < height) minHeight = height+2; // plus 2 for some margin
					}
				}
				// Check if the signature exceeds height limits
				if(canvasWrapperHeight < minHeight){
					console.log('exceeds limit, use default json');
				}else{
					json = {"lines":newLines};
					json = JSON.stringify(json);
				}
				$(canvasWrapper).signature('draw', json);
				console.log(lines);
				console.log(newLines);

				// var wrapper = nodes[i].closest('.super-field-wrapper');	
				// var wrapperWidth = wrapper.offsetWidth;
				// var wrapperHeight = wrapper.offsetHeight;
				// var canvasWrapper = nodes[i];
				// canvasWrapper.style.minWidth = '';
				// canvasWrapper.style.minHeight = '';
				// var canvasWrapperWidth = canvasWrapper.offsetWidth;
				// var canvasWrapperHeight = canvasWrapper.offsetHeight;
				// console.log(wrapperWidth, canvasWrapperWidth);
				// var canvas = nodes[i].querySelector('canvas');
				// var json = $(canvasWrapper).signature('toJSON')
				// //console.log(json);
				// // Fill the entire canvas with black.
				// //var json = '{"lines":[[[22,2.73],[22,3.73],[20,3.73],[19,3.73],[19,5.73],[17,5.73],[17,6.73],[16,6.73],[14,8.73],[13,9.73],[11,9.73],[11,11.73],[10,11.73],[10,12.73],[8,12.73],[8,14.73],[7,14.73],[7,15.73]],[[38,2.73],[38,3.73],[37,3.73],[37,5.73],[35,5.73],[35,6.73],[34,6.73],[34,8.73],[32,8.73],[32,9.73],[31,9.73],[31,11.73],[29,11.73],[29,12.73],[28,12.73],[28,14.73],[26,14.73],[26,15.73],[25,15.73],[23,15.73],[23,17.73],[22,17.73],[22,18.73],[20,18.73],[20,20.73],[19,20.73],[19,21.73],[17,21.73],[16,21.73],[16,23.73]]]}';
				// // Before resizing, check if current drawing exceeds the offset of the wrapper
				// // If this is the case we should not make it smaller than the drawing itself
				// var lines = JSON.parse(json).lines;
				// for(x=0; x < lines.length; x++){
				// 	for(y=0; y < lines[x].length; y++){
				// 		//console.log(lines[x][y]);
				// 		width = lines[x][y][0];
				// 		height = lines[x][y][1];
				// 		if(minWidth < width) minWidth = width+2; // plus 2 for some margin
				// 		if(minHeight < height) minHeight = height+2; // plus 2 for some margin
				// 	}
				// }
				// if(canvasWrapperWidth < minWidth){
				// 	//console.log(canvasWrapperWidth, canvasWrapperHeight);
				// 	//console.log(minWidth, minHeight);
				// 	canvasWrapper.style.minWidth = minWidth+'px';
				// }
				// if(canvasWrapperHeight < minHeight){
				// 	//console.log(canvasWrapperWidth, canvasWrapperHeight);
				// 	//console.log(minWidth, minHeight);
				// 	canvasWrapper.style.minHeight = minHeight+'px';
				// }
				// var ctx = canvas.getContext("2d");
				// ctx.canvas.width = canvasWrapperWidth;
				// ctx.canvas.height = canvasWrapperHeight;
				// // Set the canvas's resolution and size (not CSS).
				// canvas.width = canvasWrapperWidth;
				// canvas.height = canvasWrapperHeight;
				// $(canvasWrapper).signature('draw', json);
				
				// console.log(wrapperWidth, canvasWrapperWidth);

							// if(canvasWrapperWidth < minWidth){
							// 	console.log('scaling :)');
							// 	ctx.scale(.5, .5);
							// }


								// ctx.fillStyle = "#eee";
								// ctx.fillRect(0, 0, wrapperWidth, wrapperHeight);
								// ctx.fillStyle = "#ff0000";
								// ctx.fillRect(wrapperWidth/2-10, wrapperHeight/2-10, 20, 20);
								
								// // create a pixel buffer for one transparent pixel
								// var imageData = ctx.getImageData(0, 0, 1, 1);
								// var pixel32 = new Uint32Array(imageData.data.buffer);
								// pixel32[0] = 0;
								// ctx.putImageData(imageData, wrapperWidth/2, wrapperHeight/2);
							
							// Scale down the canvas preserving the transparent pixel's relative location.
							// var width = canvas.width / 2;
							// var height = canvas.height / 2;
							// var xScale = .5;
							// var yScale = .5;
							// var initialWidth = canvas.width;
							// var initialHeight = canvas.height;
							// // Get the true overlay's current image data.
							// imageData = ctx.getImageData(0, 0, initialWidth, initialHeight);
							// // Create an in-memory canvas at the new resolution.
							// var newCanvas = $("<canvas>").attr("width", initialWidth).attr("height", initialHeight)[0];
							// // Draw the true overlay's image data into the in-memory canvas.
							// newCanvas.getContext("2d").putImageData(imageData, 0, 0);
							// // Update the size/resolution of the true overlay.
							// ctx.canvas.width = width;
							// ctx.canvas.height = height;
							// // Scale the true overlay's context.
							// //ctx.scale(xScale, yScale);
							// // Draw the in-memory canvas onto the true overlay.
							// ctx.drawImage(newCanvas, 0, 0);	
							
							// console.log(nodes[i]);
							// console.log(nodes[i].querySelector('canvas'));
							// var wrapper = nodes[i];
							// var wrapperWidth = wrapper.offsetWidth;
							// var wrapperHeight = wrapper.offsetHeight;
							// var canvas = nodes[i].querySelector('canvas');
							// var initialWidth = canvas.width;
							// var initialHeight = canvas.height;
							// console.log(wrapperWidth, wrapperHeight);
							// console.log(initialWidth, initialHeight);

							// var ctx = canvas.getContext("2d");
							// var imageData = ctx.getImageData(0, 0, initialWidth, initialHeight);
							// var newCanvas = $("<canvas>").attr("width", initialWidth).attr("height", initialHeight)[0];
							// newCanvas.getContext("2d").putImageData(imageData, 0, 0);
							// // Update the size/resolution of the true overlay.
							// ctx.canvas.width = wrapperWidth;
							// ctx.canvas.height = wrapperHeight;
							// //ctx.drawImage(newCanvas, 0, 0);
							// ctx.drawImage(newCanvas, 0, 0, initialWidth, initialHeight);
							// // Set the canvas's resolution and size (not CSS).
							// //canvas.width = 100;
							// //canvas.height = 100;
			}
		}, 500);
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
			$parent.removeClass('super-filled');
			$parent.find('.super-shortcode-field').val('');
		});

		$doc.ajaxComplete(function() {
			SUPER.init_signature();
		});

	});

})(jQuery);	
