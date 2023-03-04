/* globals jQuery, SUPER, wp */
"use strict";
(function($) { // Hide scope, no $ conflict
    
    // Init docs
    SUPER.init_docs = function(){
        $('.sf-docs').on('click', function(e){
            e.preventDefault();
            // create a new div element for the background overlay
            var overlay = document.createElement('div');
            // set the overlay styles
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            overlay.style.zIndex = '9998';
            // create a new div element
            var popup = document.createElement('div');
            // set the popup styles
            popup.style.position = 'fixed';
            popup.style.top = '5%';
            popup.style.left = '0';
            popup.style.height = '90%';
            popup.style.maxHeight = '100%';
            popup.style.width = '90%';
            popup.style.maxWidth = '1710px';
            popup.style.zIndex = '9999';
            popup.style.overflow = 'hidden';
            // create an X button to close the popup
            var closeButton = document.createElement('button');
            closeButton.innerText = 'x';
            closeButton.style.position = 'absolute';
            closeButton.style.width = '50px';
            closeButton.style.height = '50px';
            closeButton.style.top = '3px';
            closeButton.style.right = '3px';
            closeButton.style.fontSize = '1.5rem';
            closeButton.style.border = 'none';
            closeButton.style.backgroundColor = '#F5234B';
            closeButton.style.color = '#FFFFFF';
            closeButton.style.border = '1px solid #F5234B';
            closeButton.style.borderRadius = '100px';
            closeButton.style.cursor = 'pointer';
            closeButton.style.margin = '0px';
            closeButton.style.padding = '5px';
            closeButton.style.display = 'flex';
            closeButton.style.justifyContent = 'center';
            closeButton.style.zIndex = '4';
            //closeButton.style.alignItems = 'center';
            closeButton.addEventListener('click', function() {
                popup.remove();
                overlay.remove();
            });
            overlay.addEventListener('click', function() {
                popup.remove();
                overlay.remove();
            });
            // create the spinner element
            const spinner = document.createElement('div');
            spinner.style.position = 'absolute';
            spinner.style.top = '50%';
            spinner.style.left = '50%';
            spinner.style.border = '4px solid rgba(0, 0, 0, 0.1)';
            spinner.style.borderTopColor = '#333';
            spinner.style.borderRadius = '50%';
            spinner.style.width = '40px';
            spinner.style.height = '40px';
            spinner.style.margin = '-50px 0px 0px -20px';
            spinner.style.zIndex = '2';
            spinner.style.animation = 'spin 1s linear infinite';
            // create an iframe element and set its source to google.com
            var iframebg = document.createElement('div');
            iframebg.style.position = 'absolute';
            iframebg.style.height = 'calc(100% - 120px)'; // 40px is the height of the close buttons
            iframebg.style.width = 'calc(100% - 20px)'; // 40px is the height of the close buttons
            iframebg.style.marginTop = '30px';
            iframebg.style.marginRight = '0px';
            iframebg.style.marginLeft = '0px';
            iframebg.style.zIndex = '1';
            iframebg.style.backgroundColor = '#FFFFFF';
            // create an iframe element and set its source to google.com
            var iframe = document.createElement('iframe');
            iframe.style.border = '1px solid #EEE';
            iframe.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.5)'; // add box shadow
            iframe.style.height = 'calc(100% - 120px)'; // 40px is the height of the close buttons
            iframe.style.width = 'calc(100% - 20px)'; // 40px is the height of the close buttons
            iframe.style.marginTop = '30px';
            iframe.style.marginRight = '20px';
            iframe.style.marginLeft = '0px';
            iframe.style.backgroundColor = '#FFFFFF';
            iframe.src = this.attributes.href.value;
            iframe.addEventListener('load', function() {
                spinner.style.display = 'none';
                iframebg.style.display = 'none';
            });
            var bottomCloseButton = closeButton.cloneNode(true);
            bottomCloseButton.innerText = 'Close';
            bottomCloseButton.style.top = 'initial';
            bottomCloseButton.style.bottom = '0';
            bottomCloseButton.style.left = '50%';
            bottomCloseButton.style.transform = 'translate(-50%, 0%)';
            bottomCloseButton.style.width = '150px';
            bottomCloseButton.style.borderRadius = '4px';
            bottomCloseButton.addEventListener('click', function() {
                popup.remove();
                overlay.remove();
            });
            // add the close buttons and iframe element to the popup element
            popup.appendChild(spinner);
            popup.appendChild(iframebg);
            popup.appendChild(iframe);
            popup.appendChild(closeButton);
            popup.appendChild(bottomCloseButton);
            // append the popup and overlay to the document body
            document.body.appendChild(overlay);
            document.body.appendChild(popup);
        });
    };

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
            
            $preview.on('click', 'a.super-delete', function (e) {
                e.preventDefault();
                var $this = $(this);
                var $parent = $this.parents('ul:eq(0)');
                $this.parents('li:eq(0)').remove();
                var $new_files = '';
                $parent.children('li').each(function(){
                    $new_files = $new_files ? $new_files + "," + $(this).attr('data-file') : $(this).attr('data-file');
                });
                $field.val($new_files);
                // First make sure to update the multi items json
                SUPER.update_multi_items();
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
                                $preview.html('<li data-file="'+$id+'"><div class="super-image"><img src="' + $url + '" /></div>'+$wh+'<a href="#" class="super-delete">Delete</a></li>');
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
                                }else{
                                    $id = $attachment.id;
                                }
                                var $html = '';
                                $html += '<li data-file="'+$attachment.id+'">';
                                $html += '<div class="super-image">';
                                if($attachment.type==='image'){
                                    $html += '<img src="' + $attachment.url + '" />';
                                }else{
                                    $html += '<img src="' + $attachment.icon + '" />';
                                }
                                $html += '</div>';
                                $html += '<a target="_blank" href="'+$attachment.editLink+'">' + $attachment.filename + '</a>';
                                $html += '<a href="#" class="super-delete">Delete</a>';
                                $html += '</li>';
                                if($multiple===true){
                                    $($html).appendTo($preview);
                                }else{
                                    $preview.html($html);
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

        $(document).on('click', '.super-reset-default-value, .super-reset-last-value, .super-reset-global-value, .super-lock-global-setting', function () {
            // If parent is settings tab
            if(this.closest('.super-form-settings-tabs')){
                var settingsTab = this.closest('.super-form-settings-tabs');
                var select = settingsTab.querySelector('select');
                var option = select.options[select.selectedIndex];
                var i, nodes, 
                    p = this.closest('.super-elements-container'),
                    tab = p.querySelector('.tab-content.super-active');
                // Reset settings for current tab
                if(this.classList.contains('super-reset-default-value')) nodes = tab.querySelectorAll('.super-reset-default-value');
                if(this.classList.contains('super-reset-last-value')) nodes = tab.querySelectorAll('.super-reset-last-value');
                if(this.classList.contains('super-reset-global-value')) nodes = tab.querySelectorAll('.super-reset-global-value');
                if(this.classList.contains('super-lock-global-setting')) {
                    nodes = tab.querySelectorAll('.super-lock-global-setting');
                    if(option.classList.contains('_g_')){
                        option.classList.remove('_g_');
                        settingsTab.classList.remove('_g_');
                        this.title = "Lock all to global setting";
                        for(i=0; i<nodes.length; i++){
                            if(nodes[i].closest('._g_')){
                                nodes[i].click();
                            }
                        }
                        return;
                    }
                    option.classList.add('_g_');
                    settingsTab.classList.add('_g_');
                    this.title = "Unlock all from global setting";
                    for(i=0; i<nodes.length; i++){
                        if(!nodes[i].closest('._g_')){
                            nodes[i].click();
                        }
                    }
                    return;
                }
                for(i=0; i<nodes.length; i++){
                    nodes[i].click();
                }
                return;
            }
            var parent, value = this.dataset.value,
                colorPicker = this.closest('.super-color-picker');
            if(colorPicker){
                var input = colorPicker.querySelector('input[type="text"]');
                $(input).iris('color', value); // set the color to #000
                parent = this.closest('.super-color-picker-container');
                if(this.classList.contains('super-lock-global-setting')){
                    //var field = parent.querySelector('.wp-color-result');
                    //wp-picker-input-wrap
                    if(parent.classList.contains('_g_')){
                        parent.classList.remove('_g_');
                        //field.disabled = false;
                        this.title = "Lock to global setting";
                        return;
                    }
                    parent.classList.add('_g_');
                    //field.disabled = true;
                    this.title = "Unlock from global setting";
                    return;
                }
                parent.classList.remove('_g_');
                this.title = "Lock from global setting";
                return;
            }
            parent = this.closest('.super-field');
            var field = parent.querySelector('.super-element-field');
            var isCheckbox = parent.querySelector('.super-checkbox');
            field.value = value;
            if(isCheckbox){
                var checkbox = isCheckbox.querySelector('input[type="checkbox"]');
                if(checkbox){
                    checkbox.checked = false;
                    if(checkbox.value===value){
                        checkbox.checked = true;
                    }
                }
            }
            if(parent.closest('.super-form-settings')){
                SUPER.init_field_filter_visibility($(parent));
            }else{
                SUPER.init_field_filter_visibility($(parent), 'element_settings');
            }
            if(this.classList.contains('super-lock-global-setting')){
                if(parent.classList.contains('_g_')){
                    parent.classList.remove('_g_');
                    field.disabled = false;
                    if(isCheckbox && checkbox){
                        checkbox.disabled = false;
                    }
                    this.title = "Lock to global setting";
                    return;
                }
                parent.classList.add('_g_');
                field.disabled = true;
                if(isCheckbox && checkbox){
                    checkbox.disabled = true;
                }
                this.title = "Unlock from global setting";
                return;
            }
            field.disabled = false;
            if(isCheckbox && checkbox){
                checkbox.disabled = false;
            }
            return;
        })

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
        // Delete WP forms.css stylesheet (we don't want it!)
        $('#forms-css').remove();
    });

})(jQuery);
