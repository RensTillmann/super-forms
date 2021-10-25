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
			var $signatureLines = $this.find('.super-signature-lines');
			if(!$canvas.children('canvas').length){
				$canvas.signature({
                    thickness: $field.data('thickness'),
                    color: $field.data('color'),
                    change: function(event) {
                        var $target = $(event.target);
                        if( $target.signature('isEmpty')==false ) {
                            if( !$this.hasClass('super-filled') ) {
                                $this.addClass('super-filled');
                            }
                            var $signature = $canvas[0].children;
                            var $image_data_url = $signature[0].toDataURL("image/png");
                            var $lines = $target.signature('toJSON');
                            $field.val($image_data_url);
                            $signatureLines.val($lines);
                        }else{
                            $this.removeClass('super-filled');
                        }
                        SUPER.after_field_change_blur_hook({el: $field[0]});
                    }
				});
				$canvas.signature('clear');
			}
		});
	};

	// After responsiveness changed, resize the canvas of the signature
	SUPER.refresh_signatures = function(classes, form){
		if(typeof form === 'undefined') form = document;
		// Do not refresh if generating PDF
		if(form.closest('.super-pdf-page-container')) return true;
		var i,x,y, 
			nodes = form.querySelectorAll('.super-signature-canvas');

		for( i = 0; i < nodes.length; i++ ) {
			// Make drawing smaller by 50% (just as an example)
			var canvasWrapper = nodes[i];
			$(canvasWrapper).signature('enable');
			var json = $(canvasWrapper).signature('toJSON');
			var lines = JSON.parse(json).lines;
			var canvasWrapperWidth = canvasWrapper.clientWidth;
			var canvasWrapperHeight = canvasWrapper.clientHeight;
			if(canvasWrapperWidth===0 && canvasWrapperHeight===0){
				// Do not refresh in case this singature was inside a multi-part and user switched to previous or next multi-part the canvas would have size of 0px by 0px
				continue;
			}
			var canvas = nodes[i].querySelector('canvas');
			canvas.width = canvasWrapperWidth;
			canvas.height = canvasWrapperHeight;
			var newLines = [];

			var maxX = 0;
			var maxY = 0;
			for(x=0; x < lines.length; x++){
				for(y=0; y < lines[x].length; y++){
					if(maxX < lines[x][y][0]) maxX = lines[x][y][0];
					if(maxY < lines[x][y][1]) maxY = lines[x][y][1];
				}
			}
			var ratioX = maxX / canvasWrapper.clientWidth;
			var ratioY = maxY / canvasWrapper.clientHeight;
			var finalRatio = ratioX;
			if(ratioX < ratioY) finalRatio = ratioY;
			if(finalRatio<1) finalRatio = 1;
			// In case finalRatio equals Infinity, it means that the signature was inside a multipart, hence the size of canvas would equal to 0x0
			// we shouldn't resize the signature in these scenario's
			if(finalRatio!==Infinity && finalRatio>1){
				// Resize
				for(x=0; x < lines.length; x++){
					for(y=0; y < lines[x].length; y++){
						if(!newLines[x]) newLines[x] = [];
						if(!newLines[x][y]) newLines[x][y] = [];
						newLines[x][y][0] = lines[x][y][0]/finalRatio;
						newLines[x][y][1] = lines[x][y][1]/finalRatio;
					}
				}
                json = {"lines":newLines};
                json = JSON.stringify(json);
			}else{
				// Do not resize, keep original
			}
			var jsonLength = JSON.parse(json).lines.length;
			var disallowedit = canvasWrapper.parentNode.querySelector('.super-shortcode-field').dataset.disallowedit;
			var thickness = parseFloat(canvasWrapper.parentNode.querySelector('.super-shortcode-field').dataset.thickness);
			var color = canvasWrapper.parentNode.querySelector('.super-shortcode-field').dataset.color;
			$(canvasWrapper).signature({
                thickness: thickness,
                color: color
            });
			$(canvasWrapper).signature('draw', json);
			if(disallowedit==='true' && jsonLength>0 ){ // But only if form was populated with form data
				$(canvasWrapper).signature('disable');
				// Remove clear button
				if(canvasWrapper.parentNode.querySelector('.super-signature-clear')){
					canvasWrapper.parentNode.querySelector('.super-signature-clear').remove();
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
		$(form).find('.super-signature.super-initialized').each(function(){
			var canvas = $(this).find('.super-signature-canvas:not(.kbw-signature-disabled)');
			var disallow = $(this).find('.super-shortcode-field').data('disallowedit');
			if(canvas.length>0 && disallow!=='true'){
				canvas.signature('clear');
				$(this).find('.super-shortcode-field').val('');
				$(this).find('.super-signature-lines').val('');
			}else{
				// Make sure it has filled class if not empty
				if($(this).find('.super-shortcode-field').val()!=='' || $(this).find('.super-shortcode-lines').val()!==''){
					$(this).addClass('super-filled');
				}
			}
		});
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
			$parent.find('.super-signature-lines').val('');
		});

		$doc.ajaxComplete(function() {
			SUPER.init_signature();
		});

	});

})(jQuery);	
