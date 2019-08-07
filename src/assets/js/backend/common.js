/* globals jQuery, SUPER, wp */
"use strict";
(function($) { // Hide scope, no $ conflict

    // Init WP Image Browser
    SUPER.init_image_browser = function(){

        var $doc = $(document);
        $doc.find('.browse-images:not(.super-initialized), .browse-files:not(.super-initialized)').each(function () {
            var $this = $(this);
            $this.addClass('super-initialized');
            var $title = 'Select an Image';
            var $btn_name = 'Add Image';
            var $file_type = '';
            if(typeof $this.data('file-type') !== 'undefined'){
                $file_type = $this.data('file-type');
                $file_type = $file_type.split(',');
            }

            var $multiple = '';
            if(typeof $this.data('multiple') !== 'undefined'){
                $multiple = $this.data('multiple');
            }

            var $button = $this.children('.button');
            var $preview = $this.children('.image-preview');
            var $field = $this.children('input');
            var $frame;
            var $id = $field.val();
            if($this.hasClass('browse-files')){
                $title = 'Select an File';
                $btn_name = 'Add File';
                $preview = $this.children('.file-preview');
            }
            
            $preview.on('click', 'a.delete', function () {
                var $this = $(this);
                var $parent = $this.parents('ul:eq(0)');
                $this.parents('li:eq(0)').remove();
                var $new_files = '';
                $parent.children('li').each(function(){
                    $new_files = $new_files ? $new_files + "," + $(this).attr('data-file') : $(this).attr('data-file');
                });
                $field.val($new_files);
            });
            $button.on('click', function () {
                $('.ui-widget-overlay').hide();
                $this.parents('.shortcode-dialog').hide();

                // If the media frame already exists, reopen it.
                if ($frame) {
                    $frame.open();
                    return;
                }

                // Create the media frame.
                $frame = wp.media.frames.downloadable_file = wp.media({
                    title: $title,
                    button: {
                        text: $btn_name
                    },
                    library: { 
                        type: $file_type
                    },
                    multiple: $multiple
                });

                // When an image is selected, run a callback.
                if($this.hasClass('browse-images')){
                    $frame.on('select', function () {
                        var $selection = $frame.state().get('selection');
                        $selection.map(function ($attachment) {
                            $attachment = $attachment.toJSON();
                            if ($attachment.id) {
                                $id = $attachment.id;
                                var $url = $attachment.url;
                                if ($attachment.sizes.full) $url = $attachment.sizes.full.url;
                                var $wh = '';
                                if($this.parent().hasClass('super-multi-items')){
                                    $wh += '<input type="number" placeholder="width" value="" name="max_width">';
                                    $wh += '<span>px</span>';
                                    $wh += '<input type="number" placeholder="height" value="" name="max_height">';
                                    $wh += '<span>px</span>';                                    
                                }
                                $preview.html('<li data-file="'+$id+'"><div class="image"><img src="' + $url + '" /></div>'+$wh+'<a href="#" class="delete">Delete</a></li>');
                            }
                        });
                        $field.val($id);
                        $field.trigger('change'); // Required in order for live updates on builder page
                    });
                }

                // When a file is selected, run a callback.
                if($this.hasClass('browse-files')){
                    $frame.on('select', function () {
                        var $selection = $frame.state().get('selection');
                        var $id = $field.val();
                        $selection.map(function ($attachment) {
                            $attachment = $attachment.toJSON();
                            if ($attachment.id) {
                                if($multiple===true){
                                    $id = $id ? $id + "," + $attachment.id : $attachment.id;
                                    $('<li data-file="'+$attachment.id+'"><div class="image"><img src="' + $attachment.icon + '" /></div><a href="">' + $attachment.filename + '</a><a href="#" class="delete">Delete</a></li>').appendTo($preview);
                                }else{
                                    $id = $attachment.id;
                                    $preview.html('<li data-file="'+$attachment.id+'"><div class="image"><img src="' + $attachment.icon + '" /></div><a href="">' + $attachment.filename + '</a><a href="#" class="delete">Delete</a></li>');
                                }
                            }
                        });
                        $field.val($id);
                    });
                }

                $frame.on('close', function () {
                    $('.ui-widget-overlay').show();
                    $this.parents('.shortcode-dialog').show();
                });
                
                // Finally, open the modal.
                $frame.open();
                
            });
        });
    };

    jQuery(document).ready(function ($) {
        var $doc = $(document);
        $doc.on('click', '.super-form-button > .super-button-wrap', function (e) {
            var $form = $(this).parents('.super-form:eq(0)');
            SUPER.conditional_logic(undefined, $form );
            SUPER.validate_form( $form, $(this), undefined, e, true );
            return false;
        });
    });

})(jQuery);
