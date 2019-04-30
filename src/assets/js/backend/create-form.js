"use strict";
(function($) { // Hide scope, no $ conflict

    // Loading States
    SUPER.loading_states = function(button, status){
        status = status || 'loading';
        if(status=='loading'){
            var old_html = button.html();
            button.data('old-html',old_html);
            button.parents('.super-form-button:eq(0)').addClass('super-loading');
            button.html('<i class="fas fa-refresh fa-spin"></i>');
        }else{
            button.parents('.super-form-button:eq(0)').removeClass('super-loading');
            button.html(button.data('old-html'));
        }
    };

    // Check if the added field has an unique field name
    SUPER.check_for_unique_field_name = function($element){
        var $field = $element.find('.super-shortcode-field');
        // @since v1.2.3 check if we are not importing predefined element
        if($field.length==1){
            if( typeof $field !== 'undefined' ) {
                if( typeof $field.attr('name') !== 'undefined' ) {
                    var $name = $field.attr('name').replace('[','').replace(']','');
                    var $exists = $('.super-preview-elements .super-shortcode-field[name="'+$name+'"]');
                    if($exists.length===0){
                        $field = $element.find('.super-active-files');
                        $name = $field.attr('name').replace('[','').replace(']','');
                        $exists = $('.super-preview-elements .super-active-files[name="'+$name+'"]');
                    }
                    if($exists.length>0){
                        var $unique_name = SUPER.generate_unique_field_name($field, $name, $name, 0);
                        $field.attr('name',$unique_name);
                        var $data = $.parseJSON($element.children('textarea[name="element-data"]').val());
                        $data.name = $unique_name;

                        // @since 3.7.0 - change unique field name on the fly
                        $element.find('.super-title > input').val($unique_name);
                        
                        $data = JSON.stringify($data);
                        $element.children('textarea[name="element-data"]').val($data);
                    }
                }
            }
        }
    };

    // Generate unique field name for a given element
    SUPER.generate_unique_field_name = function($field, $name, $new_name, $counter){
        var $exists = $('.super-preview-elements .super-shortcode-field[name="'+$new_name+'"]');
        if( $exists.length===0 ) {
            $exists = $('.super-preview-elements .super-active-files[name="'+$new_name+'"]');
        }
        if( $exists.length>1 ) {
            $counter++;
            $new_name = $name+'_'+$counter;
            $field.attr('name',$new_name);
            return SUPER.generate_unique_field_name($field, $name, $new_name, $counter);
        }else{
            return $new_name;
        }
    };

    // Regenerate Element Final Output (inner)
    SUPER.regenerate_element_inner = function($target, $history){
        var $elements,
            $old_code;
        if(typeof $history === 'undefined') $history = true;
        if($target==2){
            $elements = SUPER.get_session_data('_super_elements');
        }else{
            $old_code = SUPER.get_session_data('_super_elements');
            $elements = SUPER.regenerate_element_inner.get_elements($target);
        }
        SUPER.set_session_data('_super_elements', JSON.stringify($elements));
        if($target==2){
            SUPER.save_form($('.super-actions .save'), 2);
        }else{
            if($history){
                SUPER.trigger_redo_undo($elements, $old_code);
            }
        }
    };
    SUPER.regenerate_element_inner.get_elements = function($target){
        var $elements = [];
        $target.children('.super-element').each(function(){
            var $this = $(this);
            var $tag = $this.data('shortcode-tag');
            var $group = $this.data('group');
            var $data = $.parseJSON($this.children('textarea[name="element-data"]').val());
            if($data===null) $data = {};
            if( typeof $this.attr('data-minimized') !== 'undefined' ) {
                if($this.attr('data-minimized')=='no'){
                    if( typeof $data.minimized !== 'undefined' ) {
                        delete $data.minimized;
                    }
                }else{
                    $data.minimized = $this.attr('data-minimized');
                }
            }
            if($tag=='column'){
                var $size = $this.attr('data-size');
                if( $size!='1/1' ) {
                    $data.size = $size;
                }else{
                    if( typeof $data.size !== 'undefined' ) {
                        delete $data.size;
                    }
                }
            }
            var $push = {};
            $push.tag = $tag;
            $push.group = $group;

            if( ($tag=='column') || ($tag=='multipart') ) {
                var $inner = SUPER.regenerate_element_inner_children($this);
                $push.inner = $inner;
            }

            // Delete empty values
            Object.keys($data).forEach(function(key){
                if($data[key] === null){
                    delete $data[key];
                }
            });
            if(Object.keys($data).length !== 0 && $data.constructor === Object){
                $push.data = $data;
            }

            $elements.push($push);

        });
        return $elements;
    };

    // Also collect all inner items
    SUPER.regenerate_element_inner_children = function($target){
        $target = $target.children('.super-element-inner');
        if($target.children('.super-element').length){
            return SUPER.regenerate_element_inner.get_elements($target);
        }else{
            return '';
        }
    };

    // Re initialize drop here placeholder (image)
    SUPER.init_drop_here_placeholder = function(){
        $('.super-preview-elements').addClass('drop-here-placeholder');
        SUPER.init_drag_and_drop();
    };

    // Initialize elements so they can be sortable
    SUPER.init_drag_and_drop = function(){
        $('.super-preview-elements').sortable({
            scroll: false,
            scrollSensitivity: 100,
            opacity: 0.8,
            forcePlaceholderSize: true,
            forceHelperSize: true,
            connectWith: ".super-preview-elements > .super-element, .super-preview-elements > .super-element .super-element-inner",
            stop: function( event, ui ) {
                var $tag = ui.item.data('shortcode-tag');
                var $parent_tag = ui.item.parents('.super-element:eq(0)').data('shortcode-tag');
                if( typeof $parent_tag !== 'undefined' ) {
                    if( ( $tag==='multipart_pre' ) && ( $tag == $parent_tag ) ) {
                        alert(super_create_form_i18n.alert_multipart_error);
                        return false;
                    }
                }
                SUPER.init_drop_here_placeholder();
                SUPER.regenerate_element_inner($('.super-preview-elements'));
            }
        });    
        var $target = $('.super-preview-elements .super-element.super-column > .super-element-inner, .super-preview-elements .super-element.multipart > .super-element-inner');
        $target.sortable({
            scroll: false,
            scrollSensitivity: 100,
            opacity: 0.8,
            forcePlaceholderSize: true,
            forceHelperSize: true,
            connectWith: ".super-preview-elements, .super-preview-elements > .super-element .super-element-inner",
            stop: function( event, ui ) {
                var $tag = ui.item.data('shortcode-tag');
                var $parent_tag = ui.item.parents('.super-element:eq(0)').data('shortcode-tag');
                if( typeof $parent_tag !== 'undefined' ) {
                    if( ( $tag==='multipart_pre' ) && ( $tag == $parent_tag ) ) {
                        alert(super_create_form_i18n.alert_multipart_error);
                        return false;
                    }
                }
                SUPER.init_drop_here_placeholder();
                SUPER.regenerate_element_inner($('.super-preview-elements'));
            }
        });
    };

    // Scroll function when dropable or sortable element is activated
    SUPER.handleNear = function(){
        var $scrolled = $(window).scrollTop();
        var $buffer = 20;
        var $docHeight = $(document).outerHeight(true);
        var $windowHeight = $(window).outerHeight(true);
        var $near_top = $scrolled - this.ev.y >= $buffer;  
        var $near_bottom = $scrolled + $windowHeight - this.ev.y <= $buffer;  
        if($near_top){
            window.scrollTo(0, $scrolled - $buffer);
        }
        if(($near_bottom) && ((this.ev.y+$buffer) < $docHeight)){
            window.scrollTo(0, this.ev.y + $buffer - $windowHeight);
        }        
    };

    SUPER.init_previously_created_fields = function(){
        
        var $options = {};
        $('.super-preview-elements .super-element').each(function(){
            var $data = $(this).find('textarea[name="element-data"]').val();
            $data = JSON.parse($data);
            // Skip element if data is null
            if( $data!==null ) {
                var $name = $data.name;
                var $email = $data.email;
                if( typeof $name !== 'undefined' ) {
                    if( typeof $email === 'undefined' ) {
                        $email = $name;
                    }
                    $options[$name] = {
                        selected: '<option selected="selected" value="'+$name+'">'+$name+': '+$email+'</option>',
                        default: '<option value="'+$name+'">'+$name+': '+$email+'</option>'
                    };
                }
            }
        });


        $('.super-multi-items .super-previously-created, .previously-created-fields').each(function(){
            var $this = $(this),
                $options_html,
                $value = $this.data('value');
            if( $this.parent().hasClass('address-auto-popuplate-item') ) {
                $options_html = '<option value="">- select a field -</option>';  
            }else{
                $options_html = '';
            }
            $.each($options, function(key, value){
                if( $value==key ) {
                    $options_html += value.selected;
                }else{
                    $options_html += value.default;
                }
            });
            $this.html($options_html);
        });
       
        $('.super-element-settings .super-elements-container select[name="connected_min"]').each(function(){
            var $this = $(this);
            var $current = $('.super-element.editing').find('.super-shortcode-field');
            var $value = $current.attr('data-connected_min');
            var $options_html = '';
            $.each($options, function(key, value){
                var $found = $this.find('option[value="'+key+'"]').length;
                if( $found===0 ) {
                    if( $value==key ) {
                        $options_html += value.selected;
                    }else{
                        $options_html += value.default;
                    }
                }
            });
            $options_html = '<option value="">- Not connected -</option>'+$options_html;
            $this.html($options_html);
        });
        $('.super-element-settings .super-elements-container select[name="connected_max"]').each(function(){
            var $this = $(this);
            var $current = $('.super-element.editing').find('.super-shortcode-field');
            var $value = $current.attr('data-connected_max');
            var $options_html = '';
            $.each($options, function(key, value){
                var $found = $this.find('option[value="'+key+'"]').length;
                if( $found===0 ) {
                    if( $value==key ) {
                        $options_html += value.selected;
                    }else{
                        $options_html += value.default;
                    }
                }
            });
            $options_html = '<option value="">- Not connected -</option>'+$options_html;
            $this.html($options_html);
        });
    };

    SUPER.update_multi_items = function($this){
        var $field = $this.parents('.field:eq(0)');
        // Only proceed if not hidden
        if($field.hasClass('hidden')) return true;

        var $field_name = $this.parents('.super-elements-container:eq(0)').find('input[name="name"]').val();
        var $parent = $this.parents('.field-input:eq(0)');
        var $items = [];
        var $error = false;
        var $regex = /{(.*?)}/g;
        var $tag_name;
        var $v;
        $parent.find('.super-multi-items').each(function(){
            var $this = $(this);
            if($this.hasClass('super-conditional-item')){
                // Check if `conditional_field` or `conditional_field_and` points to it's own field
                // If yes then display error to the user
                var $conditional_field = $this.children('input[name="conditional_field"]');
                var $str = $conditional_field.val();
                var $conditional_field_names = [];
                while (($v = $regex.exec($str)) !== null) {
                    // This is necessary to avoid infinite loops with zero-width matches
                    if ($v.index === $regex.lastIndex) {
                        $regex.lastIndex++;
                    }
                    $tag_name = $v[1].split(';')[0];
                    $conditional_field_names.push($tag_name);
                }
                var $conditional_field_and = $this.children('input[name="conditional_field_and"]');
                $str = $conditional_field_and.val();
                var $conditional_field_and_names = [];
                while (($v = $regex.exec($str)) !== null) {
                    // This is necessary to avoid infinite loops with zero-width matches
                    if ($v.index === $regex.lastIndex) {
                        $regex.lastIndex++;
                    }
                    $tag_name = $v[1].split(';')[0];
                    $conditional_field_names.push($tag_name);
                }
                var $conditional_and_method = $this.children('select[name="conditional_and_method"]').val();
                if($conditional_field_names.indexOf($field_name)!==-1){
                    $error = true;
                    $conditional_field.addClass('super-error');
                }else{
                    $conditional_field.removeClass('super-error');
                }
                if($conditional_field_and_names.indexOf($field_name)!==-1 && $conditional_and_method!==''){
                    $error = true;
                    $conditional_field_and.addClass('super-error');
                }else{
                    $conditional_field_and.removeClass('super-error');
                }
                $items.push({ 
                    field: $this.children('input[name="conditional_field"]').val(),
                    logic: $this.children('select[name="conditional_logic"]').val(),
                    value: $this.children('input[name="conditional_value"]').val(),
                    and_method: $conditional_and_method,
                    field_and: $this.children('input[name="conditional_field_and"]').val(),
                    logic_and: $this.children('select[name="conditional_logic_and"]').val(),
                    value_and: $this.children('input[name="conditional_value_and"]').val(),
                    new_value: $this.children('textarea[name="conditional_new_value"]').val()
                });
            }else if($this.hasClass('address-auto-popuplate-item')){
                $items.push({ 
                    key: $this.children('input[name="key"]').val(),
                    field: $this.children('select[name="field"]').val(),
                    type: $this.children('select[name="type"]').val()
                });
            }else{
                var $checked;
                if($this.children('input[type="checkbox"]').length){
                    $checked = $this.children('input[type="checkbox"]').is(':checked');
                }
                if($this.children('input[type="radio"]').length){
                    $checked = $this.children('input[type="radio"]').is(':checked');
                }
                if($('.super-preview-elements').hasClass('super-translation-mode')){
                    $items.push({ 
                        label: $this.children('input[name="label"]').val()
                    });
                }else{
                    $items.push({ 
                        checked: $checked,
                        image: $this.find('input[name="image"]').val(),
                        max_width: $this.find('input[name="max_width"]').val(),
                        max_height: $this.find('input[name="max_height"]').val(),
                        label: $this.children('input[name="label"]').val(),
                        value: $this.children('input[name="value"]').val()
                    });
                }
            }
        });
        if( $error===true) {
            var $first_error = $('.super-element-settings .super-error:eq(0)');
            if($first_error.length){
                var topPos = $first_error[0].offsetTop+($first_error[0].offsetParent ? $first_error[0].offsetParent.offsetTop : 0);
                $parent = $first_error.parents('.tab-content:eq(0)');
                // Make this tab active
                $parent.parents('.super-elements-container:eq(0)').find('.tab-content').removeClass('active');
                $parent.addClass('active');
                $parent.parents('.super-elements-container:eq(0)').find('.super-element-settings-tabs > select').val($parent.index()-1);
                $parent[0].scrollTop = topPos;
                return false;
            }
        }
        $items = JSON.stringify($items);
        $parent.children('textarea').val($items);
        return true;
    };

    SUPER.init_dragable_elements = function() {
        $('.draggable-element').pep({
            activeClass: 'active',
            droppableActiveClass: 'dropping-allowed',
            droppable: '.super-dropable',
            start: function(ev, obj){
                SUPER.init_drop_here_placeholder();
                obj.noCenter = true;
                var top = obj.$el.css('top').replace('px','');
                var left = obj.$el.css('left').replace('px','');
                if(typeof obj.$el.attr('data-start-position-top') === 'undefined'){
                    obj.$el.attr('data-start-position-top', top);
                    obj.$el.attr('data-start-position-left', left);
                }            
            },
            drag:function() {  
                SUPER.handleNear.apply(this);
            },
            stop: function(ev, obj){
                if(this.activeDropRegions.length>0){
                    var $tag = obj.$el.data('shortcode');
                    var $target = $('.dropping-allowed:not(:has(.dropping-allowed))');
                    var $multipart_found = $target.closest('[data-shortcode-tag="multipart"]').length;
                    if( ( ($multipart_found>0) && ($tag==='multipart_pre') ) ) {
                        alert(super_create_form_i18n.alert_multipart_error);
                        return false;
                    }
                    var $predefined = '';
                    if( typeof obj.$el.find('.predefined').val() !== 'undefined' ) {
                        $predefined = JSON.parse(obj.$el.find('.predefined').val());
                    }
                    $.ajax({
                        type: 'post',
                        url: ajaxurl,
                        data: {
                            action: 'super_get_element_builder_html',
                            tag: obj.$el.data('shortcode'),
                            group: obj.$el.data('group'),
                            predefined: $predefined,
                            form_id: $('input[name="form_id"]').val()
                        },
                        success: function (data) {
                            var $element = $(data).appendTo($target);
                            SUPER.init_resize_element_labels();
                            SUPER.check_for_unique_field_name($element);
                            SUPER.regenerate_element_inner($('.super-preview-elements'));
                            SUPER.init_skype();
                            SUPER.init_tooltips();
                            SUPER.init_datepicker();
                            SUPER.init_masked_input();
                            SUPER.init_currency_input();
                            SUPER.init_colorpicker();
                            SUPER.init_slider_field();
                            SUPER.init_button_colors();
                            SUPER.init_drop_here_placeholder();
                        }
                    });
                }else{
                    obj.cssX = 0;
                    obj.cssY = 0;
                    obj.translation = "matrix(1, 0, 0, 1, 0, 0)";
                    obj.transform(obj.translation);
                    obj.$el.css('top', '0').css('left', '0');
                }
            },
            revert: true,
            cssEaseDuration: 0,
        });
    };
    SUPER.save_form = function( $this, $method, $button, callback ) {
        var $fields = $('.super-preview-elements .super-shortcode-field, .super-preview-elements .super-active-files');
        var $error = false;
        var $duplicate_fields;

        // First reste all classes
        $('.super-preview-elements .super-element.error').removeClass('error');

        // @since 4.0.0 - see if we need to skip this validation when user choose to disable validation check on unique field names
        var $allow = $('input[name="allow_duplicate_names"]').is(':checked');
        if( !$allow ) {
            $fields.each(function(){
                var $origin_field = $(this);
                if($origin_field.parents('.super-file:eq(0)').length) {
                    $duplicate_fields = $('.super-preview-elements .super-active-files[name="'+$(this).attr('name')+'"]');
                }else{
                    $duplicate_fields = $('.super-preview-elements .super-shortcode-field[name="'+$(this).attr('name')+'"]');
                }
                if($duplicate_fields.length > 1){
                    $duplicate_fields.parents('.super-element').addClass('error');
                    $error = true;
                }
            });
            if($error === true) {
                alert(super_create_form_i18n.alert_duplicate_field_names);
                return false;
            } 
        }
        SUPER.regenerate_element_inner($('.super-preview-elements'), false);
        
        $this.html('<i class="fas fa-save"></i>Saving...');

        var $settings = {};
        $('.super-create-form .super-form-settings .element-field').each(function(){
            var $this = $(this);
            var $name = $this.attr('name');
            var $value = $this.val();
            $settings[$name] = $value;
        });

        $.ajax({
            type: 'post',
            url: ajaxurl,
            data: {
                action: 'super_save_form',
                id: $('.super-create-form input[name="form_id"]').val(),
                title: $('.super-create-form input[name="title"]').val(),
                shortcode: SUPER.get_session_data('_super_elements'),
                settings: $settings,
            },
            success: function (data) {
                $('.super-create-form .super-get-form-shortcodes').val('[super_form id="'+data+'"]');
                $('.super-create-form input[name="form_id"]').val(data);
                $('.super-create-form .super-actions .save').html('<i class="fas fa-save"></i>Save');
                if($method==3){ // When switching from language
                    callback($button);
                }else{
                    if($method==1){
                        var $this = $('.super-create-form .super-actions .preview:eq(3)');
                        SUPER.preview_form($this);
                    }else{
                        var href = window.location.href;
                        var page = href.substr(href.lastIndexOf('/') + 1);
                        var str2 = "admin.php?page=super_create_form&id";
                        if(page.indexOf(str2) == -1){
                            window.location.href = "admin.php?page=super_create_form&id="+data;
                        }else{
                            if($method==2){
                                location.reload();
                            }
                        }
                    }
                }
            }
        });
    };
    SUPER.preview_form = function( $this ) {  
        if($('input[name="form_id"]').val()===''){
            alert(super_create_form_i18n.alert_save);
            return false;
        }
        if(!$this.hasClass('active')){
            $this.html('Loading...');
            $('.super-live-preview').html('');
            $('.super-preview-elements').css('display','none');
            $('.super-live-preview').addClass('super-loading').css('display','block');
            var $form_id = $('.super-create-form input[name="form_id"]').val();
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_load_preview',
                    id: $form_id,
                },
                success: function (data) {
                    $('.super-live-preview').removeClass('super-loading');
                    $('.super-live-preview').html(data);
                    $this.html('Builder');
                },
                complete: function () {
                    SUPER.handle_columns();
                    SUPER.init_button_colors();
                    SUPER.init_super_responsive_form_fields();
                    SUPER.init_super_form_frontend();
                    SUPER.after_preview_loaded_hook($form_id);
                }
            });
        }else{
            $('.super-live-preview').css('display','none');
            $('.super-preview-elements').css('display','block');
            $this.html('Preview');
        }
        $this.toggleClass('active');
    };

    // Update export json
    SUPER.init_resize_element_labels = function() {
        $('.super-create-form .super-element-header .super-element-label > input').each(function(){
            var $span = $(this).parent().children('span');
            var $width = $span.outerWidth(true);
            $(this).parent().css('width', $width+'px').css('margin-left', '-'+($width/2)+'px');
        });   
    };

    // @since 2.9.0 - form setup wizard
    SUPER.update_wizard_preview = function($theme, $size, $icon, $save) {
        var $theme_setting, $icon_setting;
        if($theme===null) $theme = $('.super-theme-style-wizard li.super-active').attr('data-value');
        if($size===null) $size = $('.super-field-size-wizard li.super-active').attr('data-value');
        if($icon===null) $icon = $('.super-theme-hide-icons-wizard li.super-active').attr('data-value');
        if($theme=='squared') $theme_setting = '';
        if($theme=='rounded') $theme_setting = 'super-default-rounded';
        if($theme=='full-rounded') $theme_setting = 'super-full-rounded';
        if($theme=='minimal') $theme_setting = 'super-style-one';
        if($icon=='no') $icon_setting = 'yes';
        if($icon=='yes') $icon_setting = 'no';
        if($save===true){
            $('.super-create-form select[name="theme_style"]').val($theme_setting);
            $('.super-create-form select[name="theme_field_size"]').val($size);
            $('.super-create-form select[name="theme_hide_icons"]').val($icon_setting);
            $('.super-create-form input[name="title"]').val($('.super-create-form input[name="wizard_title"]').val());
            
            $('.super-create-form input[name="header_to"]').val($('.super-create-form input[name="wizard_header_to"]').val());
            $('.super-create-form select[name="header_from_type"]').val('custom');
            $('.super-create-form input[name="header_from"]').val($('.super-create-form input[name="wizard_header_from"]').val());
            $('.super-create-form input[name="header_from_name"]').val($('.super-create-form input[name="wizard_header_from_name"]').val());
            $('.super-create-form input[name="header_subject"]').val($('.super-create-form input[name="wizard_header_subject"]').val());
            $('.super-create-form textarea[name="email_body_open"]').val($('.super-create-form textarea[name="wizard_email_body_open"]').val());

            $('.super-create-form input[name="confirm_to"]').val($('.super-create-form input[name="wizard_confirm_to"]').val());
            $('.super-create-form select[name="confirm_from_type"]').val('custom');
            $('.super-create-form input[name="confirm_from"]').val($('.super-create-form input[name="wizard_confirm_from"]').val());
            $('.super-create-form input[name="confirm_from_name"]').val($('.super-create-form input[name="wizard_confirm_from_name"]').val());
            $('.super-create-form input[name="confirm_subject"]').val($('.super-create-form input[name="wizard_confirm_subject"]').val());
            $('.super-create-form textarea[name="confirm_body_open"]').val($('.super-create-form textarea[name="wizard_confirm_body_open"]').val());

            $('.super-create-form input[name="form_thanks_title"]').val($('.super-create-form input[name="wizard_form_thanks_title"]').val());
            $('.super-create-form textarea[name="form_thanks_description"]').val($('.super-create-form textarea[name="wizard_form_thanks_description"]').val());

        }
        var $img_preview = $theme+'-'+$size;
        if($icon=='yes') $img_preview = $img_preview+'-icon';
        var $img_preview_url = $('.super-wizard-preview img').attr('data-preview-url')+'assets/images/wizard-preview/'+$img_preview+'.png';
        $('.super-wizard-preview img').attr('src', $img_preview_url);
    };

    // @since 3.1.0 - trigger undo/redo after _super_elements was changed
    SUPER.insertAfter = function(referenceNode, newNode) {
        referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
    };


    SUPER.trigger_redo_undo = function($new_code, $old_code) {
        $old_code = ( typeof !$old_code || $old_code==='' ? '' : JSON.parse($old_code) );
        // Before saving the form data, add it to form history for our Undo and Redo functionality
        var $history = SUPER.get_session_data('_super_form_history');
        
        if($history){
            $history = JSON.parse($history);
            $history.push($new_code);
        }else{
            // Update form history
            $history = [];
            $history.push($old_code);
            $history.push($new_code);
        }
        var $total_history = Object.keys($history).length;
        // Max history we store is 50 steps, if above 21 delete the first key from history
        if($total_history>50){
            $history.splice(0,1);
        }
        // Disable buttons
        var $undo = document.querySelector('.super-undo');
        var $redo = document.querySelector('.super-redo');
        $undo.dataset.index = $total_history-1;
        $redo.dataset.index = $total_history-1;
        $redo.classList.add('super-disabled');
        if($total_history<=1){
            $undo.classList.add('super-disabled');
        }else{
            $undo.classList.remove('super-disabled');
        }
        // Update form history
        SUPER.set_session_data('_super_form_history', JSON.stringify($history));
        // Update form data
        SUPER.set_session_data('_super_elements', JSON.stringify($new_code));
    };

    // @since 3.7.0 - function for random name generation when duplicate action button is clicked
    SUPER.generate_new_field_name = function() {
        var $field_name = "";
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        for (var i = 0; i < 5; i++) {
            $field_name += possible.charAt(Math.floor(Math.random() * possible.length));
        }
        // First check if this fieldname already exists inside builder
        if($('.super-preview-elements .super-shortcode-field[name="'+$field_name+'"]').length){
            $field_name = SUPER.generate_new_field_name();   
        }
        return 'field_'+$field_name;
    };


    jQuery(document).ready(function ($) {
        
        var $doc = $(document),
            $super_hints,
            $super_hints_steps;

        // @since 4.6.0 - transfer elements with other forms
        setInterval(function(){
            var element = SUPER.get_session_data('_super_transfer_element_html', 'local');
            if(element && element!==''){
                $('.super-preview-elements').addClass('super-transfering');
            }else{
                $('.super-preview-elements').removeClass('super-transfering');
            }
        },300); // check every 3 milli seconds

        // @since 4.0.0 - hints/introduction
        var $skip = $('input[name="super_skip_tutorial"]').val();
        var $elements_found = $('.super-preview-elements .super-element').length;
        if( ($skip!='true') && (!$elements_found) ) {
            var $git = 'https://renstillmann.github.io/super-forms/#/';
            var $timeout = 0;
            var $margin = 0;
            var $timeout_s = 400;
            var $event = 'next';
            var $showSkip = false;
            var $showNext = true;
            var $tags_allowed = '<span class="super-tip">You are allowed to use {tags} for this setting,<br />for more information about tags refer to the documentation section:<br /><a target="blank" href="'+$git+'tags-system">Tags system</a></span>';


            // Check if field `wizard_title` exists
            if($('input[name="wizard_title"]').length){
                $super_hints = new SUPER.EnjoyHint({});
                $super_hints_steps = [
                    {
                        selector: '.enjoyhint_close_btn',
                        shape: 'circle',
                        radius: 50,
                        nextButton : {text: "Start"},
                        description: '<h1>Hello! This is a short tutorial to get you up and running with Super Forms. Please click "Start" to begin the tutorial :)</h1><span class="super-tip">If you wish to skip the tutorial, you can skip it by clicking the close button</span><span class="super-tip">We strongly suggest you complete this step by step guide. It will help you get started nicely and quickly without any issues.</span><label class="tutorial-do-not-show-again"><input type="checkbox" name="tutorial_do_not_show_again" />Do not show me this tuturial again.</label>',
                    },
                    {
                        onBeforeStart: function() {
                            $('input[name="wizard_title"]').keydown(function(e) {
                                if( (e.keyCode == 13) && ($(this).val() !== '') ) {
                                    $super_hints.trigger('next');
                                }
                            });
                        },
                        selector: 'input[name="wizard_title"]',
                        event: 'form_title_entered',
                        event_type: 'custom',
                        description: '<h1>Enter your form title and press Enter.</h1>',
                    },
                    {
                        selector: '.super-theme-style-wizard',
                        event: 'click',
                        description: '<h1>Choose a form theme.</h1>',
                    },
                    {
                        selector: '.super-field-size-wizard',
                        event: 'click',
                        description: '<h1>Now choose a field size for your form elements.</h1>',
                    },
                    {
                        selector: '.super-theme-hide-icons-wizard',
                        event: 'click',
                        description: '<h1>Select wether or not to display icons for fields</h1><span class="super-tip">Don\'t worry, you can change all these settings at a later time</span>',
                    },
                    {
                        selector: '.super-wizard-preview',
                        description: '<h1>Here you can preview your form and see how it will look on the front-end of your website</h1><span class="super-tip">Note that this is an example only, and the elements are just for demonstration purpose only. You will soon build your very own form with your own elements.</span>',
                    },
                    {
                        selector: '.super-wizard-settings .super-tabs > li:eq(1)',
                        event: 'click',
                        showNext: false,
                        description: '<h1>Click on the "Admin email" TAB to change how your admin emails are send</h1><span class="super-tip">By default this email will be send to the wordpress admin email address, but you can change this to any email address.</span>',
                    },
                    {
                        selector: 'input[name="wizard_header_to"]',
                        description: '<h1>Enter the email address where admin emails should be send to</h1>' + $tags_allowed,
                    },

                    {
                        selector: 'input[name="wizard_header_from"]',
                        description: '<h1>Enter the email address where the email was send from</h1>' + $tags_allowed,
                    },

                    {
                        selector: 'input[name="wizard_header_from_name"]',
                        description: '<h1>Enter the name of your company or website</h1>' + $tags_allowed,
                    },

                    {
                        selector: 'input[name="wizard_header_subject"]',
                        description: '<h1>Enter the email subject that relates to this form</h1>' + $tags_allowed,
                    },
                    {
                        selector: 'textarea[name="wizard_email_body_open"]',
                        description: '<h1>Here you can enter a short description that will be placed at the top of your admin email</h1><span class="super-tip">The email body itself can be changed under the "Form Settings" panel on the builder page, which we will be covering at a later time in this tutorial.</span><span class="super-tip">The email body itself will by default simply loop all the user input that was submitted by the user. You can of course write your custom email body if you require to do so.</span>' + $tags_allowed,
                    },
                    {
                        selector: '.super-wizard-settings .super-tabs > li:eq(2)',
                        event: 'click',
                        showNext: false,
                        description: '<h1>Click on the "Confirmation email" TAB to change how confirmation emails are send</h1><span class="super-tip">By default this email will be send to the user who submitted the form if an email address was provided</span>',
                    },
                    {
                        selector: 'input[name="wizard_confirm_to"]',
                        description: '<h1>The email address where the confirmation email should be send to.</h1><span class="super-tip">By default this is set to {email} which is a <a target="_blank" href="'+$git+'tags-system">tag</a> that will automatically retrieve the email address that the user entered in the form.</span><span class="super-tip">You can seperate emails with comma\'s to send to multiple addresses</span>' + $tags_allowed,
                    },
                    {
                        selector: 'input[name="wizard_confirm_from"]',
                        description: '<h1>Enter the email address where the email was send from</h1>' + $tags_allowed,
                    },
                    {
                        selector: 'input[name="wizard_confirm_from_name"]',
                        description: '<h1>Enter the name of your company or website</h1>' + $tags_allowed,
                    },
                    {
                        selector: 'input[name="wizard_confirm_subject"]',
                        description: '<h1>Enter the confirmation email subject that relates to this form</h1>' + $tags_allowed,
                    },
                    {
                        selector: 'textarea[name="wizard_confirm_body_open"]',
                        description: '<h1>Here you can enter a short description that will be placed at the top of your confirmation email</h1><span class="super-tip">The email body itself can be changed under the "Form Settings" panel on the builder page, which we will be covering at a later time in this tutorial.</span><span class="super-tip">The email body itself will by default simply loop all the user input that was submitted by the user. You can of course write your custom email body if you require to do so.</span>' + $tags_allowed,
                    },
                    {
                        selector: '.super-wizard-settings .super-tabs > li:eq(3)',
                        event: 'click',
                        showNext: false,
                        description: '<h1>Click on the "Thank you message" TAB to change the Success message</h1><span class="super-tip">This message will by default be displayed to the user after they successfully submitted the form.</span>' + $tags_allowed,
                    },
                    {
                        selector: 'input[name="wizard_form_thanks_title"]',
                        description: '<h1>The Title for your thank you message</h1>' + $tags_allowed,
                    },
                    {
                        selector: 'textarea[name="wizard_form_thanks_description"]',
                        description: '<h1>The Description for your thank you message</h1><span class="super-tip">This can be used to provide some additional information that is important to the user after they successfully submitted the form.</span>' + $tags_allowed,
                    },
                    {
                        selector: '.super-button.save-wizard',
                        event: 'click',
                        showNext: false,
                        description: '<h1>Click this button to save the configuration and to start building your form</h1>',
                    },
                ];
                $.each($super_hints_steps, function(key, value){
                    if( typeof value.event === 'undefined')
                        $super_hints_steps[key].event = $event;
                    if( typeof value.showSkip === 'undefined')
                        $super_hints_steps[key].showSkip = $showSkip;
                    if( typeof value.showNext === 'undefined')
                        $super_hints_steps[key].showNext = $showNext;
                    if( typeof value.timeout === 'undefined')
                        $super_hints_steps[key].timeout = $timeout;
                    if( typeof value.margin === 'undefined')
                        $super_hints_steps[key].margin = $margin;
                });
                $super_hints.set($super_hints_steps);
                $super_hints.run();
            }else{
                $super_hints = new SUPER.EnjoyHint({});
                $super_hints_steps = [
                    {
                        selector: '.super-preview-elements',
                        description: '<h1>This is your "Canvas" where you will be dropping all your "Elements"</h1><span class="super-tip">"Elements" can be fields, but also HTML elements and columns. Basically anything that can be dragged and dropped onto the canvas is a so called element.</span><label class="tutorial-do-not-show-again"><input type="checkbox" name="tutorial_do_not_show_again" />Do not show me this tuturial again.</label>',
                    },
                    {
                        selector: '.super-element.super-form-elements',
                        event: 'click',
                        description: '<h1>Let\'s open up the "Form Elements" panel by clicking on it</h1>',
                    },
                    {
                        selector: '.super-element.super-form-elements .super-elements-container',
                        description: '<h1>Here you can find all "Form Elements"</h1><span class="super-tip">Form elements are elements that a user can enter data into (also referred to as "user input"). As you can see there is a variety of form elements to choose from to create any type of form you want.</span>',
                        timeout: $timeout_s
                    },
                    {
                        onBeforeStart: function() {
                            $doc.find('.super-element.draggable-element.super-shortcode-email').on('mouseleave',function(){
                                if( $(this).hasClass('pep-start') ) {
                                    $super_hints.trigger('next');
                                }
                            });
                        },
                        showNext: false,
                        selector: '.super-element.super-form-elements .super-elements-container .super-shortcode-email',
                        description: '<h1>Let\'s drag the "Email Address" field on to your "Canvas"</h1>',
                    },
                    {
                        onBeforeStart: function() {
                            // Keep searching for element until we found it, then automatically go to next step
                            var loop = setInterval(function() {
                                if($('.super-element .super-field-wrapper input[name="email"]').length){
                                    $super_hints.trigger('next');
                                    clearInterval(loop);
                                }
                            }, 100);
                        },
                        selector: '.super-preview-elements',
                        showNext: false,
                        description: '<h1>Drop the element on to your "Canvas"</h1>',
                    },
                    {
                        selector: '.super-element-title .super-title > input',
                        description: '<h1>Here you can quickly change the "Unique field name" of your field</h1><span class="super-tip">The unique field name relates to the user input. The {email} <a target="_blank" href="'+$git+'tags-system">tag</a> in this case would retrieve the entered email address of the user which you can then use within your custom emails, HTML elements and <a target="_blank" href="'+$git+'variable-fields">Variable fields</a> or inside your email subjects or other settings that support the use of {tags}.</span>',
                    },
                    {
                        onBeforeStart: function() {
                            $('.super-element-actions .minimize').css( 'pointer-events', 'none' );
                            $('.super-element-actions .delete').css( 'pointer-events', 'none' );
                        },
                        selector: '.super-element-actions .minimize',
                        radius: 10,
                        shape: 'circle',
                        description: '<h1>Here you can minimize your element</h1><span class="super-tip">This comes in handy especially when working with large forms. To benefit from this feature make sure you use columns to group your elements. With columns you can minimize a set of elements at once to make building forms even easier, faster and better manageable.</span>',
                    },
                    {
                        onBeforeStart: function() {
                            $('.super-element-actions .minimize').css( 'pointer-events', '' );
                            $('.super-element-actions .delete').css( 'pointer-events', '' );
                        },
                        selector: '.super-element-actions .delete',
                        radius: 10,
                        event: 'click',
                        shape: 'circle',
                        description: '<h1>Click on the delete icon and delete this element</h1><span class="super-tip">Removing a "Layout Element" (<a target="_blank" href="'+$git+'columns">Column</a> or <a target="_blank" href="'+$git+'multi-parts">Multi-part</a>) will also delete all it\'s inner elements along with it.</span><span class="super-tip">Don\'t worry, we will cover Columns and Multi-part elements very soon!</span>',
                    },
                    {
                        selector: '.super-form-history .super-undo',
                        event: 'click',
                        shape: 'circle',
                        description: '<h1>Undo previous change, click and undo your previous change to get back our element</h1><span class="super-tip">If you accidently deleted an element and want to get it back or when you moved an element where you did not want it to be moved to by accident, then you can undo your latest change with this button.</span><span class="super-tip">You can undo/redo any changes you made to your form that affected elements.</span><span class="super-tip">Please understand that the Undo/Redo buttons act like scrolling through micro back-ups of your form (which aren\'t really saved), so after a page refresh you can no longer undo any previously made changes).</span>',
                    },
                    {
                        onBeforeStart: function() {
                            $('.super-form-history .super-redo').css( 'pointer-events', 'none' );
                        },
                        selector: '.super-form-history .super-redo',
                        shape: 'circle',
                        description: '<h1>Redo previous change</h1><span class="super-tip">Does the same thing as undo but opposite.</span>',
                    },
                    {
                        onBeforeStart: function() {
                            $('.super-form-history .super-backups').css( 'pointer-events', 'none' );
                            $('.super-form-history .super-redo').css( 'pointer-events', '' );
                        },
                        selector: '.super-form-history .super-backups',
                        shape: 'circle',
                        description: '<h1>Load or restore to previous backups</h1><span class="super-tip">Backups are automatically made when saving your form, so whenever you want to go back in history you can restore directly to a previous backup that was automatically made for you.</span>',
                    },
                    {
                        onBeforeStart: function() {
                            $('.super-form-history .super-backups').css( 'pointer-events', '' );
                        },
                        selector: '.super-form-history .super-minimize-toggle',
                        event: 'click',
                        shape: 'circle',
                        description: '<h1>Let\'s minimize all our elements, we only have 1 element but for the demonstration purpose this doesn\'t matter right now ;)</h1><span class="super-tip">This comes in handy especially when working with large forms. To benefit from this feature make sure you use columns to group your elements. With columns you can minimize a set of elements at once to make building forms even easier, faster and better manageable.</span></span>',
                    },
                    {
                        selector: '.super-form-history .super-maximize-toggle',
                        event: 'click',
                        shape: 'circle',
                        description: '<h1>Maximizing all elements</h1><span class="super-tip">Whenever you working with large forms and used the minimize button, you can maximize all of your elements at once to quickly find the element that you need to edit.</span>',
                    },
                    {
                        selector: '.super-element-actions .transfer',
                        radius: 10,
                        shape: 'circle',
                        description: '<h1>Transfering elements between different forms can be done via this button</h1><span class="super-tip">When transfering Columns or Multi-parts all inner elements will be also be transfered along with them, making life even easier :)</span><span class="super-tip">You can also use this feature to clone the element and reposition it at a different location within the form you are working on. If needed you can also navigate to a different form and transfer this element over to that form.</span>',
                    },
                    {
                        selector: '.super-element-actions .move',
                        radius: 10,
                        shape: 'circle',
                        description: '<h1>Moving your element can be done via this button</h1><span class="super-tip">Drag & Drop your element into a different location or inside a different layout element with ease.</span>',
                    },
                    {
                        selector: '.super-element-actions .duplicate',
                        event: 'click',
                        radius: 10,
                        shape: 'circle',
                        description: '<h1>Duplicate this element</h1><span class="super-tip">Duplicating elements that you already created will speed up your building process! When duplicating Columns or Multi-parts all inner elements will be duplicated along with them, making life even easier :)</span>',
                    },
                    {
                        selector: '.super-element-actions .edit',
                        radius: 10,
                        event: 'click',
                        shape: 'circle',
                        description: '<h1>Click on the pencil icon to edit this element</h1><span class="super-tip">All elements can be edited, and each element will have it\'s very own settings and features. </span>',
                    },
                    {
                        selector: '.super-element.super-element-settings',
                        description: '<h1>Here you can find all the settings for the element you are editing</h1><span class="super-tip">By default the General TAB is opened where you will find the most commonly used settings that you will often be changing.</span>',
                        timeout: $timeout_s
                    },
                    {
                        selector: '.super-element.super-element-settings .super-element-settings-tabs > select',
                        event: 'change',
                        showNext: false,
                        description: '<h1>We have devided all element settings into sections which you can choose from via this dropdown, Open the dropdown and switch to a different section to find out about all the other features and settings for the element you are editing.</h1><span class="super-tip">Remember that all elements have different settings and features, so make sure to explore them all!</span><span class="super-tip">Note that the Email Address element that we added to our form, is a <a target="_blank" href="'+$git+'text">Text field</a>. It is a predefined element that basically has the <a target="_blank" href="'+$git+'special-validation?id=email-address">Email address validation</a> enabled by default. There are several other predefined elements for you just to make building even easier for you.',
                    },
                    {
                        selector: '.super-element.super-element-settings',
                        description: '<h1>Perfect! Now you know how to edit elements and how to find all settings and features available for each element you edit.</h1>',
                    },
                    {
                        selector: '.super-element-settings .tab-content.active .super-tooltip',
                        shape: 'circle',
                        radius: 20,
                        description: '<h1>Not sure what a field is used for, just hover over the question icon to find out more information about it.</h1>',
                    },
                    {
                        selector: '.super-element.super-layout-elements',
                        event: 'click',
                        description: '<h1>Let\'s explore the rest of Super Forms shall we? Open up the "Layout Elements" panel by clicking on it</h1>',
                    },
                    {
                        selector: '.super-element.super-layout-elements .super-elements-container',
                        description: '<h1>These are your "Layout Elements"</h1><span class="super-tip">The <a target="_blank" href="'+$git+'columns">Columns (Grid Element)</a> can be used to create the layout of your form.</span><span class="super-tip">You can use columns to put fields next to eachother and to do <a target="_blank" href="'+$git+'conditional-logic">Conditional Logic</a>.</span><span class="super-tip">A column can also be used to create <a target="_blank" href="'+$git+'columns?id=dynamic-add-more">Dynamic fields</a> that can be duplicated by users. This way a set of fields can be dynamically added by clicking on a "+" icon.</span><span class="super-tip">Columns can be nested inside of each other as many times as you wish, they can also be inserted into a <a target="_blank" href="'+$git+'multi-parts">Multi-part</a> element.</span><span class="super-tip">The <a target="_blank" href="'+$git+'multi-parts">Multi-part</a> element can be used to split a form into multiple parts (also called steps). For each step you will have to add a new Multi-part element with inside the elements that belong to this particular step.</span>',
                        timeout: $timeout_s
                    },
                    {
                        selector: '.super-element.super-html-elements',
                        event: 'click',
                        description: '<h1>Now open the "HTML Elements" panel</h1>',
                    },
                    {
                        selector: '.super-element.super-html-elements .super-elements-container',
                        description: '<h1>Here you can find all HTML elements</h1><span class="super-tip">HTML elements are elements that users can not change or alter (they are fixed html items that do not require user input). However you can make some elements dynamically change with the use of <a target="_blank" href="'+$git+'conditional-logic">Conditional Logic</a> and the use of <a target="_blank" href="'+$git+'variable-fields">Variable fields</a> and the <a target="_blank" href="'+$git+'tags-system">{tags} system</a>. These elements can help you to change the aesthetics of your form.</span>',
                        timeout: $timeout_s
                    },
                    {
                        selector: '.super-element.super-form-settings',
                        event: 'click',
                        description: '<h1>Open the "Form Settings" panel to edit form settings</h1>',
                    },
                    {
                        selector: '.super-element.super-form-settings .super-elements-container',
                        description: '<h1>Here you can change all the "Form Settings" which will only apply to this specific form</h1><span class="super-tip">Under [Super Forms > Settings] (WordPress menu) you will find all your global settings that will be applied to all of your forms when creating a new form. After creating a form you can change each of these form settings over here. If both the global setting and form setting are the same the setting will not be saved for the form and instead the global setting will be used now and in the future until they differ from eachother.</span><span class="super-tip"><strong>Important to understand:</strong> If both the global setting and form setting are the same the setting will basically not be saved, but instead the global setting will be used by default now and in the future until they differ from eachother. This means that when you change a global setting at a later time it will affect all previously created forms that where initially setup with this exact same setting. This way you can control all of your forms if required from a global point of view.</span>',
                        timeout: $timeout_s
                    },
                    {
                        selector: '.super-form-settings-tabs > select',
                        event: 'change',
                        showNext: false,
                        description: '<h1>We have devided all form settings into sections which you can choose from via this dropdown, Open the dropdown and switch to a different section to find out about all the other settings that you can change with Super Forms</h1>',
                    },
                    {
                        selector: '.super-element.super-form-settings .super-elements-container',
                        description: '<h1>Great, now you know how to change all the settings related to a specific form!</h1><span class="super-tip">Please note that in some cases you have to change settings under [Super Forms > Settings] (WordPress menu). For instance if you require to setup SMTP you can do it only via the global settings and not individually per form. The same goes for reCAPTCHA API key and secret. For some <a target="_blank" href="'+$git+'add-ons">Add-ons</a> you will also only find the settings under [Super Forms > Settings] and not under the "Form Settings" panel on the builder page.</span>',
                        timeout: $timeout_s
                    },
                    {
                        selector: '.form-name',
                        description: '<h1>Here you can change the name of your form</h1><span class="super-tip">Always choose a name that relates to the purpose of the form itself.</span><span class="super-tip">The "Form name" is for your own reference only, and is not visible to anyone other than you and other WordPress admins.</span>',
                    },
                    {
                        selector: '.super-switch-forms',
                        description: '<h1>Here you can easily switch to a different form that you previously created</h1><span class="super-tip">A list will open with previously created forms to quickly switch to.</span>',
                    },
                    {
                        onBeforeStart: function() {
                            $('.super-switch-forms').removeClass('active');
                        },
                        selector: '.super-get-form-shortcodes',
                        description: '<h1>This is the [shortcode] of your form. You can display your form by copy pasting the shortcode to any of your posts/pages.</h1><span class="super-tip">You can add your shortcode in posts, pages and widgets (e.g: sidebars or in your footer). Anywhere within your site where your theme supports shortcodes you can basically display your form. In case you want to read more about how to build and publish your first form you can read the <a target="_blank" href="'+$git+'build">Documentation</a></span>',
                    },
                    {
                        selector: '.super-actions .save',
                        description: '<h1>You can save your form simply by clicking the "Save" button</h1>',
                    },
                    {
                        selector: '.super-actions .clear',
                        description: '<h1>If you want to start with a blank form you can use the "Clear" button</h1><span class="super-tip">Please note that this will erase your current work in progress and will delete all the elements that are currently on the canvas.</span>',
                    },
                    {
                        selector: '.super-actions .delete',
                        onBeforeStart: function() {
                            $('.super-actions .delete').css( 'pointer-events', 'none' );
                        },
                        description: '<h1>Here you can delete your form</h1><span class="super-tip">This will delete the form itself allong with it\'s Elements, Settings and all it\'s backups. It will not delete the associated Contact Entries that where created by the form.</span>',
                    },
                    {
                        onBeforeStart: function() {
                            $('.super-actions .delete').css( 'pointer-events', '' );
                            $('.enjoyhint_close_btn').css('display','none');
                        },
                        selector: '.super-actions .preview.switch',
                        description: '<h1>To see how your form will look on the front-end you can click the "Preview" button</h1><span class="super-tip">You can also preview the form on mobile and tablet devices to test it\'s responsiveness.</span>',
                    },
                    {
                        selector: '.super-actions > label:last',
                        description: '<h1>(For Developers Only) Enable this whenever you require to be able to save a form that has duplicate field names</h1><span class="super-tip">Whenever you are a developer and require the need to save a form that consists of duplicate field names, then you have to enable this setting. By default Super Forms prevents saving a form that contains duplicate field names.</span>',
                    },
                    {
                        selector: '.wp-submenu a[href*="page=super_marketplace"]',
                        description: '<h1>You finished the tutorial! Now you know how to navigate around Super Forms page and create awesome forms with it.<br /><br />Please check out the Marketplace with awesome one click install forms that can get you up and running in no time!</h1><span class="super-tip">We hope you will enjoy the plugin, if you have future questions do not hesitate to contact support!</span><span class="super-tip">Don\'t forget to checkout the <a target="_blank" href="'+$git+'">Documentation</a> whenever you need more information about the plugin and all of it\'s features :)</i></span><span class="super-tip">Want to do more? Check out these awesome <a target="_blank" href="'+$git+'add-ons">Add-ons</a> for Super Forms!</span>',
                        nextButton : {
                            text: "Finish"
                        },
                    },
                ];
                $.each($super_hints_steps, function(key, value){
                    if( typeof value.event === 'undefined')
                        $super_hints_steps[key].event = $event;
                    if( typeof value.showSkip === 'undefined')
                        $super_hints_steps[key].showSkip = $showSkip;
                    if( typeof value.showNext === 'undefined')
                        if($super_hints_steps[key].event=='click'){
                            $super_hints_steps[key].showNext = false;
                        }else{
                            $super_hints_steps[key].showNext = $showNext;
                        }
                    if( typeof value.timeout === 'undefined')
                        $super_hints_steps[key].timeout = $timeout;
                    if( typeof value.margin === 'undefined')
                        $super_hints_steps[key].margin = $margin;
                });
                $super_hints.set($super_hints_steps);
                $super_hints.run();
            }
        }

        // @since 4.0.0 - update conditional checks values
        $doc.on('change keydown keyup blur','.super-conditional-check input[type="text"], .super-conditional-check select',function(){
            var $parent = $(this).parents('.super-conditional-check:eq(0)');
            var $value = '';
            $parent.children('input[type="text"], select').each(function(){
                if($(this).index()===0){
                    $value += $(this).val();
                }else{
                    $value += ',' + $(this).val();
                }
            });
            if($value==',') $value = '';
            $parent.children('input[type="hidden"]').val($value);
        });

        // @since 4.0.0 - skip tutorial if checkbox is checked.
        $doc.on('click', '.tutorial-do-not-show-again', function(e){
            e.preventDefault();
            var $status = $(this).children('input').is(':checked');
            if($status===false){
                $(this).children('input').prop('checked', true);
            }else{
                $(this).children('input').prop('checked', false);
            }
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_tutorial_do_not_show_again',
                    status: $status
                }
            });
        });
        
        // @since 4.7.0 - tabs
        $doc.on('click', '.super-tabs > span', function(){
            var $this = $(this),
                $parent = $this.parent(),
                $tab = $this.attr('data-tab');
            $parent.children('span').removeClass('super-active');
            $this.addClass('super-active');
            $('.super-tabs-content .super-tab-content').removeClass('super-active');
            $('.super-tabs-content .super-tab-'+$tab).addClass('super-active');
        });

        // @since 4.7.0 - translations
        // close dropdown when clicked outside
        $doc.on('click', '*:not(.super-dropdown)', function(e){
            if(!$(e.target).parents('.super-dropdown.super-active:eq(0)').length){
                $doc.find('.super-dropdown.super-active').removeClass('super-active');
            }
        });
        // open/close dropdown when clicked on the element
        $doc.on('click', '.super-tab-translations .super-dropdown', function(e){
            if(e.target.tagName==='LI') return;
            if(e.target.tagName==='INPUT') return;
            var $this = $(this);
            if($this.hasClass('super-active')){
                $this.removeClass('super-active');
            }else{
                $doc.find('.super-dropdown.super-active').removeClass('super-active');
                $this.addClass('super-active');
                // Focus text field
                $this.find('input').focus();
            }
        });
        // upon choosing an item set it to active and deactivate others
        $doc.on('click', '.super-tab-translations .super-dropdown-items > li', function(){
            var $this = $(this),
                $language = $this.html(),
                $dropdown = $this.parents('.super-dropdown:eq(0)');
            $dropdown.find('li.super-active').removeClass('super-active');
            if($this.hasClass('super-active')){
                $(this).removeClass('super-active');
            }else{
                $(this).addClass('super-active');
            }
            $dropdown.children('.super-dropdown-placeholder').html($language);
            $dropdown.removeClass('super-active');
        });
        // filter method for filtering dropdown items
        $doc.on('keyup', '.super-tab-translations .super-dropdown-search input', function(){
            var $this = $(this),
                $dropdown = $this.parents('.super-dropdown:eq(0)'),
                $value = $this.val().toLowerCase(),
                $items_found = [];
            if($value===''){
                // No longer filtering, show all
                $dropdown.removeClass('super-filtering');
                $dropdown.find('.super-dropdown-items li').removeClass('super-match');
                return;
            }
            // We are filtering
            $dropdown.addClass('super-filtering');
            $dropdown.find('.super-dropdown-items li').each(function(){
                if($(this).html().toLowerCase().indexOf($value)!==-1){
                    $(this).addClass('super-match');
                }else{
                    $(this).removeClass('super-match');
                }
            });
        });
        // create translation
        $doc.on('click', '.super-create-translation', function(){
            // Validate
            var $row = $('.super-default-language'),
                $language = $row.find('.super-dropdown[data-name="language"] .super-active'),
                $flag = $row.find('.super-dropdown[data-name="flag"] .super-active');
            $row.find('.super-dropdown[data-name="language"], .super-dropdown[data-name="flag"]').removeClass('super-error');
            if(!$language.length || !$flag.length){
                if(!$language.length)
                    $row.find('.super-dropdown[data-name="language"]').addClass('super-error');
                if(!$flag.length)
                    $row.find('.super-dropdown[data-name="flag"]').addClass('super-error');
                return false;
            }
            // We will grab the so called "dummy" html, which is the first item in our list
            var $dummy = $('.translations-list > li').first(),
                $last = $('.translations-list > li').last(),
                $clone = $dummy.clone(),
                $edit = $clone.find('.edit'),
                $delete = $clone.find('.delete');

            // First reset the tooltips for our buttons
            $clone.find('.tooltipstered').removeClass('tooltipstered');
            $edit.attr('title', $edit.attr('data-title'));
            $delete.attr('title', $delete.attr('data-title'));
            $clone.insertAfter($last);
            SUPER.init_tooltips();
        });
        // edit translation
        $doc.on('click', '.translations-list .edit', function(){
            var $row = $(this).parent(),
                $language = $row.find('.super-dropdown[data-name="language"] .super-active'),
                $language_title = $language.html(),
                $flag = $row.find('.super-dropdown[data-name="flag"] .super-active'),
                $tab = $('.super-tabs .super-tab-builder');
            
            // Validate
            $row.find('.super-dropdown[data-name="language"], .super-dropdown[data-name="flag"]').removeClass('super-error');
            if(!$language.length || !$flag.length){
                if(!$language.length)
                    $row.find('.super-dropdown[data-name="language"]').addClass('super-error');
                if(!$flag.length)
                    $row.find('.super-dropdown[data-name="flag"]').addClass('super-error');
                return false;
            }
            // Set i18n (language key)
            $language = $language.attr('data-value')
            console.log($language);
            $('.super-preview-elements').attr('data-i18n', $language);
            // Remove active class from all tabs
            $('.super-tabs > span').removeClass('super-active');
            $('.super-tab-content.super-active').removeClass('super-active');
            // Add active class to builder tabs
            $('.super-tabs .super-tab-builder').addClass('super-active');
            $('.super-tab-content.super-tab-builder').addClass('super-active');
            // Set new tab title including language flag
            $flag = $flag.children('img')[0].outerHTML
            $tab.html($tab.attr('data-title')+$flag);

            // Check switching to default language
            var $language_changed = $('.super-preview-elements').attr('data-language-changed');
            console.log($language_changed);
            if( $row.hasClass('super-default-language') ) {
                $('.super-translation-mode-notice').hide();
                $('.super-preview-elements').removeClass('super-translation-mode').attr('data-i18n', null);
                if(typeof $language_changed === 'undefined'){
                    return false;
                }
            }else{
                // Set translation mode notice
                $('.super-translation-mode-notice').show();
                $('.super-translation-mode-notice .super-i18n-language').html($language_title);
                // We were in builder mode, so let's activate translation mode and reload the form elements with the correct language
                console.log($language);
                $('.super-preview-elements').addClass('super-translation-mode').attr('data-i18n', $language);
            }

            // Display loading icon, and hide all elements/settings
            $('.super-preview-elements, .super-form-settings').addClass('super-loading');

            // Always check if user was updating an element, if so cancel it
            cancel_update();

            if(typeof $language_changed !== 'undefined'){
                $('.super-preview-elements').attr('data-language-changed', null);
            }

            // Always save the form before switching to a different language
            // This will prevent loading "old" / "unsaved" form elements and settings
            SUPER.save_form($('.super-actions .save'), 3, $(this), function($button){
                // When saving finished we can continue
                var $row = $button.parent(),
                    $language = $row.find('.super-dropdown[data-name="language"] .super-active'),
                    $flag = $row.find('.super-dropdown[data-name="flag"] .super-active');

                // Get language
                var $language = $language.attr('data-value'),
                    $flag = $flag.children('img')[0].outerHTML;

                // Reload builder html, and form settings TAB
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_switch_language',
                        form_id: $('.super-create-form input[name="form_id"]').val(),
                        i18n: $('.super-preview-elements').attr('data-i18n')
                    },
                    success: function (data) {
                        data = JSON.parse(data);
                        $('.super-preview-elements').html(data.elements);
                        $('.super-form-settings .super-elements-container').html(data.settings);
                        $('.super-preview-elements, .super-form-settings').removeClass('super-loading');
                    },
                    error: function(){
                        alert(super_create_form_i18n.export_form_error);
                    },
                    complete: function(){
                        
                    }
                });
            });
        });
        // delete translation
        $doc.on('click', '.translations-list .delete', function(){
            var $delete = confirm(super_create_form_i18n.confirm_deletion);
            if($delete === true) {
                // Before removing language check if currently in translation mode for this language
                // If this is the case we must switch back to the default language and thus the "builder" mode
                if($('.super-preview-elements').hasClass('super-translation-mode')){
                    var $row = $(this).parent(),
                        $language = $row.find('.super-dropdown[data-name="language"] .super-active'),
                        $flag = $('.super-default-language .super-dropdown[data-name="flag"] .super-active'),
                        $i18n = $('.super-preview-elements').attr('data-i18n'),
                        $tab = $('.super-tabs .super-tab-builder');
                    if($language){
                        $language = $language.attr('data-value');
                        if($language==$i18n){
                            // Switch back to builder mode
                            $('.super-translation-mode-notice').hide();
                            $('.super-preview-elements').removeClass('super-translation-mode').attr('data-i18n', null);
                            // Set flag to default language flag
                            $flag = $flag.children('img')[0].outerHTML;
                            // Set new tab title including language flag
                            $tab.html($tab.attr('data-title')+$flag);
                        }
                    }
                }
                // Always check if user was updating an element, if so cancel it
                cancel_update();
                // Remove language from the list
                $(this).parent().remove();
            }
        });

        // @since 3.1.0 - backup history
        $doc.on('click', '.super-form-history .super-backups', function(){
            $('.super-backup-history, .super-first-time-setup-bg').addClass('super-active');
            $('.super-backup-history').addClass('super-loading');
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_restore_backup',
                    form_id: $('.super-create-form input[name="form_id"]').val()
                },
                success: function (data) {
                    $('.super-wizard-backup-history > ul').remove();
                    $('.super-wizard-backup-history').find('i').remove();
                    $(data).appendTo($('.super-wizard-backup-history'));
                },
                complete: function(){
                    $('.super-backup-history').removeClass('super-loading');
                }
            });

        });
        $doc.on('click', '.super-wizard-backup-history > ul > li > span', function(){
            $(this).html('Restoring...').addClass('super-loading');
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_restore_backup',
                    form_id: $('.super-create-form input[name="form_id"]').val(),
                    backup_id: $(this).parent('li').attr('data-id')
                },
                success: function () {
                    location.reload();
                }
            });
        });
        $doc.on('click', '.super-wizard-backup-history > ul > li > i', function(){
            var $parent = $(this).parents('ul:eq(0)');
            var $delete = confirm(super_create_form_i18n.confirm_deletion);
            if($delete === true) {
                var $backup = $(this).parent();
                $backup.html(super_create_form_i18n.deleting);
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_delete_backups',
                        backup_id: $backup.data('id')
                    },
                    success: function () {
                        $backup.slideUp("normal", function(){
                            $(this).remove();
                            if($parent.children('li').length===0){
                                $('.super-wizard-backup-history > ul').remove();
                                $('<i>'+super_create_form_i18n.no_backups_found+'</i>').appendTo($('.super-wizard-backup-history'));
                            }
                        });
                    }
                });
            }

        });
        $doc.on('click', '.delete-backups', function(){
            var $old_html = $(this).html();
            var $button = $(this);
            $button.html(super_create_form_i18n.deleting).addClass('super-loading');
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_delete_backups',
                    form_id: $('.super-create-form input[name="form_id"]').val()
                },
                success: function () {
                    $('.super-wizard-backup-history > ul').remove();
                    $('<i>'+super_create_form_i18n.no_backups_found+'</i>').appendTo($('.super-wizard-backup-history'));
                    $button.html($old_html).removeClass('super-loading');
                }
            });
        });

        // @since 4.0.0 - minimize toggle button to toggle all elements minimized or maximize
        $doc.on('click', '.super-form-history .super-minimize-toggle, .super-form-history .super-maximize-toggle', function(){
            var $minimize = 'yes';
            if( $(this).hasClass('super-maximize-toggle') ) {
                $minimize = 'no';
            }
            $('.super-preview-elements .super-element').each(function(){
                if( $minimize=='yes' ) {
                    $(this).attr('data-minimized', 'yes').addClass('super-minimized');
                }else{
                    $(this).attr('data-minimized', 'no').removeClass('super-minimized');
                }
            });
            SUPER.init_resize_element_labels();
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner($('.super-preview-elements'));
        });

        // @since 4.6.0 - improved undo/redo buttons
        $doc.on('click', '.super-form-history .super-undo, .super-form-history .super-redo', function(){
            var $this = $(this);
            if($this.hasClass('super-disabled')){
                return true;
            }
            var $history,
                $total_history,
                $index,
                $other;
            $history = SUPER.get_session_data('_super_form_history');
            if($history){
                $history = JSON.parse($history);
                $total_history = Object.keys($history).length;
                $index = parseFloat($this.attr('data-index'));
                if($this.hasClass('super-undo')){
                    $index = $index-1;
                    $other = $('.super-form-history .super-redo');
                }else{
                    $index = $index+1;
                    $other = $('.super-form-history .super-undo');
                }
                $other.removeClass('super-disabled');
                if($this.hasClass('super-undo')){
                    // Add correct indexes to the undo/redo buttons
                    if($index-1 < 0){
                        $this.addClass('super-disabled');
                    }else{
                        $this.removeClass('super-disabled');
                    }
                }else{
                    if($index >= $total_history-1){
                        $this.addClass('super-disabled');
                    }else{
                        $this.removeClass('super-disabled');
                    }
                }
                $this.addClass('super-loading');
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_undo_redo',
                        form_id: $('.super-create-form input[name="form_id"]').val(),
                        elements: JSON.stringify($history[$index])
                    },
                    success: function (result) {
                        $('.super-preview .super-preview-elements').html(result);
                        SUPER.init_resize_element_labels();
                    },
                    complete: function(){
                        $this.removeClass('super-loading');
                    }
                });
                $other.attr('data-index', $index);
                $this.attr('data-index', $index);
            }
        });


        SUPER.init_drop_here_placeholder();
        SUPER.init_dragable_elements();
        SUPER.init_image_browser();

        $('.super-layout-elements .super-elements-container').css('display','block');

        SUPER.init_resize_element_labels();

        // @since 2.9.0 - Form setup wizard
        $doc.on('click', '.super-theme-style-wizard > li, .super-field-size-wizard > li, .super-theme-hide-icons-wizard > li', function(){
            var $this = $(this);
            var $parent = $this.parent();
            $parent.children('li').removeClass('super-active');
            $this.addClass('super-active');
            var $value = $this.attr('data-value');
            if($parent.hasClass('super-theme-style-wizard')) SUPER.update_wizard_preview($value, null, null, false);
            if($parent.hasClass('super-field-size-wizard')) SUPER.update_wizard_preview(null, $value, null, false);
            if($parent.hasClass('super-theme-hide-icons-wizard')) SUPER.update_wizard_preview(null, null, $value, false);
        });

        $doc.on('click', '.skip-wizard, .super-first-time-setup-bg', function(){
            $('.super-first-time-setup, .super-first-time-setup-bg').removeClass('super-active');
        }); 

        $doc.on('click', '.save-wizard', function(){
            $(this).addClass('super-loading').html('Saving settings...');
            SUPER.update_wizard_preview(null, null, null, true);
            $('.super-actions .save').trigger('click');
        });
        $doc.on('click', '.super-wizard-settings .super-tabs > li', function(){
            var $index = $(this).index();
            $(this).parent().children('li').removeClass('super-active');
            $(this).addClass('super-active');
            $('.super-wizard-settings .super-tab-content > li').removeClass('super-active');
            $('.super-wizard-settings .super-tab-content > li:eq('+$index+')').addClass('super-active');
        });


        // @since 1.5
        $doc.on('change keyup blur','.super-element.super-element-settings input[name="name"]',function(){
            var $editing = $('.super-preview-elements .super-element.editing');
            var $tag = $editing.data('shortcode-tag');
            if( $tag!='button' ) {
                var $value = $(this).val().replace(/\s+/gi,'_');
                $value = $value.replace(/ /g,"_");
                $value = $value.replace(/\//g,"");
                $value = $value.replace(/[^a-zA-Z0-9-_\.]+/g,"");
                $value = $value.replace(/\.+/g, "_");
                $value = $value.replace(/[--]+/g, "-");
                $value = $value.replace(/[__]+/g, "_");
                $(this).val($value);

                // @since 3.7.0 - change unique field name on the fly
                $('.super-element.editing .super-title > input').val($value);
            }
        });

        $doc.on('change','.super-create-form .super-element-header .super-element-label > input',function(){
            var $this = $(this);
            var $value = $this.val();
            var $span = $this.parent().children('span');
            $span.html($value);
            $this.attr('value',$value);
            var $width = $span.outerWidth(true);
            $this.parent().css('width', $width+'px').css('margin-left', '-'+($width/2)+'px');
            var $parent = $this.parents('.super-element:eq(0)');
            var $data = $parent.children('textarea[name="element-data"]').val();
            var $tag = $parent.data('shortcode-tag');
            $data = JSON.parse($data);
            if( ($tag=='column') || ($tag=='multipart') ) {
                $data.label = $value;
            }
            $data = JSON.stringify($data);
            $parent.children('textarea[name="element-data"]').val($data);
            SUPER.regenerate_element_inner($('.super-preview-elements'));
        });

        $doc.on('click','.super-element-actions .duplicate',function(){
            var $parent = $(this).parents('.super-element:eq(0)');
            $parent.find('.tooltip').remove();
            var $new = $parent.clone();
            
            // @since 3.7.0 - bug fix remove editing class when duplicating column with active editing element inside
            $new.find('.super-element.editing').removeClass('editing');

            // @since 3.7.0 - automatically rename duplicated fields for more user-friendly work flow
            $new.find('.super-shortcode-field').each(function(){
                var $old_name = $(this).attr('name');
                var $new_field_name = SUPER.generate_new_field_name();
                $(this).attr('name', $new_field_name);
                var $parent = $(this).parents('.super-element:eq(0)');
                $parent.find('.super-title > input').val($new_field_name);
                var $element_data_field = $parent.children('textarea[name="element-data"]');
                var $element_data = $element_data_field.val();
                $element_data = $element_data.replace('"name":"'+$old_name+'"', '"name":"'+$new_field_name+'"');
                $element_data_field.val($element_data);
            });
            $new.removeClass('editing');
            $new.insertAfter($parent);
            $new.slideUp(0);
            $new.slideDown(300);
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner($('.super-preview-elements'));
        });

        // @since 4.6.0 - transfer this element to either a different location in the current form or to a completely different form (works cross-site)
        $doc.on('click','.super-element-actions .transfer',function(){
            var $parent = $(this).parents('.super-element:eq(0)');
            $parent.find('.tooltip').remove();
            var $node = $parent.clone();
            $node.find('.super-element.editing').removeClass('editing');
            $node.removeClass('editing');
            SUPER.set_session_data('_super_transfer_element_html', $node[0].outerHTML, 'local');
        });
        // @since 4.6.0 - transfer this element to either a different location in the current form or to a completely different form (works cross-site)
        $doc.on('click','.super-element-actions .transfer-drop',function(){
            var $html = SUPER.get_session_data('_super_transfer_element_html', 'local');
            var $parent = $(this).parents('.super-element:eq(0)');
            $($html).insertAfter($parent);
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner($('.super-preview-elements'));
            localStorage.removeItem('_super_transfer_element_html');
            $('.super-preview-elements').removeClass('super-transfering');
        });
        $doc.on('click','.super-preview-elements.super-transfering',function(e){
            if(e.target.hasClass('super-preview-elements')){
                var $html = SUPER.get_session_data('_super_transfer_element_html', 'local');
                $($html).appendTo($(this));
                SUPER.init_drag_and_drop();
                SUPER.regenerate_element_inner($('.super-preview-elements'));
                localStorage.removeItem('_super_transfer_element_html');
                $('.super-preview-elements').removeClass('super-transfering');
            }
        });

        // @since 3.7.0 - change unique field name on the fly
        $doc.on('change', '.super-element-header .super-title > input', function(){
            var $this = $(this);
            var $parent = $this.parents('.super-element:eq(0)');
            var $old_name = $parent.find('.super-shortcode-field').attr('name');
            var $new_field_name = $this.val();
            $new_field_name = $this.val().replace(/\s+/gi,'_');
            $new_field_name = $new_field_name.replace(/ /g,"_");
            $new_field_name = $new_field_name.replace(/\//g,"");
            $new_field_name = $new_field_name.replace(/[^a-zA-Z0-9-_\.]+/g,"");
            $new_field_name = $new_field_name.replace(/\.+/g, "_");
            $new_field_name = $new_field_name.replace(/[--]+/g, "-");
            $new_field_name = $new_field_name.replace(/[__]+/g, "_");
            $this.val($new_field_name);
            $parent.find('.super-shortcode-field').attr('name', $new_field_name);
            var $element_data_field = $parent.children('textarea[name="element-data"]');
            var $element_data = $element_data_field.val();
            $element_data = $element_data.replace('"name":"'+$old_name+'"', '"name":"'+$new_field_name+'"');
            $element_data_field.val($element_data);
            if($parent.hasClass('editing')){
                $('.super-elements-container .field .element-field[name="name"]').val($new_field_name);
            }
            SUPER.regenerate_element_inner($('.super-preview-elements'));
        });

        $doc.on('click', '.super-element-actions .minimize', function(){
            var $this = $(this).parents('.super-element:eq(0)');
            var $minimized = $this.attr('data-minimized');
            if( $minimized === 'undefined' ) $minimized = 'no';
            if($minimized=='yes'){
                $this.attr('data-minimized', 'no').removeClass('super-minimized');
            }else{
                $this.attr('data-minimized', 'yes').addClass('super-minimized');
            }
            SUPER.init_resize_element_labels();
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner($('.super-preview-elements'));
        });
        $doc.on('click', '.super-element-actions .delete', function(){
            $(this).parents('.super-element:eq(0)').remove();
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner($('.super-preview-elements'));
            cancel_update();
        });
        $doc.on('click','.super-element > .super-element-header > .resize > span',function(){
            var $parent = $(this).parents('.super-element:eq(0)');
            var $data = $parent.find('textarea[name="element-data"]').val();
            $data = JSON.parse($data);
            var $size = $data.size;
            if( typeof $parent.attr('data-size') !== 'undefined' ){
                $size = $parent.attr('data-size');
            }
            var $sizes = {
                '1/1':'super_one_full',
                '4/5':'super_four_fifth',
                '3/4':'super_three_fourth',
                '2/3':'super_two_third',
                '3/5':'super_three_fifth',
                '1/2':'super_one_half',
                '2/5':'super_two_fifth',
                '1/3':'super_one_third',
                '1/4':'super_one_fourth',
                '1/5':'super_one_fifth'};
            var $keys = ['1/1','4/5','3/4','2/3','3/5','1/2','2/5','1/3','1/4','1/5'];
            var $start = $size;
            var $next = $keys[($.inArray($start, $keys) + 1) % $keys.length];
            var $prev = $keys[($.inArray($start, $keys) - 1 + $keys.length) % $keys.length];
            if($(this).hasClass('smaller')){
                if($size=='1/5'){
                    return false;
                }
                $parent.attr('data-size',$next);
                $parent.removeClass($sizes[$start]).addClass($sizes[$next]);
                $parent.children('.super-element-header').find('.resize > .current').html($next);
            }
            if($(this).hasClass('bigger')){
                if($size=='1/1'){
                    return false;
                }
                $parent.attr('data-size',$prev);
                $parent.removeClass($sizes[$start]).addClass($sizes[$prev]);
                $parent.children('.super-element-header').find('.resize > .current').html($prev);
            }
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner($('.super-preview-elements'));
        });
        $doc.on('click','.super-switch-forms',function(){
            var $this = $(this);
            if($this.hasClass('active')){
                $this.children('.fa').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $this.removeClass('active');
                $this.children('ul').slideUp(300);
            }else{
                $this.children('.fa').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $this.addClass('active');
                $this.children('ul').slideDown(300);
            }
        });
        $doc.on('mouseleave','.super-switch-forms ul',function(){
            var $this = $(this).parent();
            $this.children('.fa').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            $this.removeClass('active');
            $this.children('ul').slideUp(300);
        });

        $doc.on('change','.super-form-settings-tabs > select, .super-element-settings-tabs > select',function(){
            $(this).parents('.super-elements-container:eq(0)').children('.tab-content').removeClass('active');
            $(this).parents('.super-elements-container:eq(0)').children('.tab-content:eq('+($(this).val())+')').addClass('active');
        });
        
        $doc.on('click','.super-multi-items .add',function(){
            var $this = $(this);
            var $parent = $this.parents('.super-multi-items:eq(0)');

            var $fields = {};
            $parent.find('select').each(function(){
                $fields[$(this).attr('name')] = $(this).val();
            });

            var $item = $parent.clone();
            $item.find('select').each(function(){
                $(this).val($fields[$(this).attr('name')]);
            });

            $item = $item.insertAfter($parent);
            $item.find('.super-initialized').removeClass('super-initialized');
            $item.find('input[type="radio"]').prop('checked', false);
            if($parent.find('.super-multi-items').length > 1){
                $parent.find('.delete').css('visibility','');
            }else{
                $parent.find('.delete').css('visibility','hidden');
            }
            if(!$parent.hasClass('super-conditional-item')){
                SUPER.init_image_browser();
            }
        });    

        $doc.on('click','.super-multi-items .delete',function(){
            var $this = $(this);
            var $parent = $this.parents('.field-input:eq(0)');
            if($parent.find('.super-multi-items').length <= 2){
                $parent.find('.delete').css('visibility','hidden');
            }else{
                $parent.find('.delete').css('visibility','');
            }
            $(this).parent().remove();
        }); 
        
        $doc.on('click','.super-element-settings .update-element',function(){
            
            var $error = false;
            // First check for empty required fields
            $('.super-element-settings .element-field[required="true"]').each(function(){
                var $this = $(this);
                if( $this.val()==='' ) {
                    var $hidden = false;
                    $this.parents('.field.filter').each(function(){
                        if($(this).css('display')=='none'){
                            $hidden = true;
                        }
                    });
                    if($hidden===false){
                        $error = true;
                        $this.addClass('super-error');
                    }
                }else{
                    $this.removeClass('super-error');
                }
            });

            if( $error===true) {
                var $first_error = $('.super-element-settings .super-error:eq(0)').parents('.field:eq(0)');
                var $parent = $first_error.parents('.tab-content:eq(0)');
                var $position = $first_error.position().top + $parent.scrollTop() - $first_error.outerHeight();
                $parent.animate({
                    scrollTop: $position
                }, 0);
                return false;
            }
            
            var $continue = true;
            $(this).parents('.super-elements-container:eq(0)').find('.super-multi-items').each(function(){
                if(!SUPER.update_multi_items($(this))){
                    $continue = false;
                }
            });
            if(!$continue){
                alert('Conditional field pointer may not point to the field itself. This would create an infinite loop and results in a stack overflow. Please choose a different field for your conditional logic!');
                return false;
            }

            var $button = $(this);
            $button.addClass('super-loading');
            var $fields = {};
            $('.super-element-settings .element-field').each(function(){
                var $this = $(this);
                var $default = $this.parents('.field-input:eq(0)').attr('data-default');
                
                var $hidden = false;
                $this.parents('.field.filter').each(function(){
                    if($(this).css('display')=='none'){
                        $hidden = true;
                    }
                });
                if($hidden===false){
                    var $name = $this.attr('name');
                    var $value = $this.val();
                    if( ($value!=='') && ($value!=$default) ) {
                        if($this.parents('.field-input:eq(0)').find('.super-multi-items').length){
                            $fields[$name] = $.parseJSON($value);
                        }else{
                            $fields[$name] = $value;
                        }
                    }else{
                        if( $value==='' ) {
                            var $allow_empty = $this.parents('.field-input:eq(0)').attr('data-allow-empty');
                            if( typeof $allow_empty !== 'undefined' ) {
                                $fields[$name] = $value;
                            }
                        } 
                    }
                }
            });
            if( (typeof $fields.name !== 'undefined') && ($fields.name==='') ){
                $button.removeClass('super-loading');
                $('.super-element-settings .element-field[name="name"]').css('border','1px solid #ff9898').css('background-color', '#ffefef');
                alert(super_create_form_i18n.alert_empty_field_name);
                return false;
            }
            $('.super-element-settings .element-field[name="name"]').css('border','').css('background-color', '');

            var $element = $('.super-element.editing');
            var $value;
            var $element_data;
            var $translating = $('.super-preview-elements').hasClass('super-translation-mode');
            
            // Always get possible translation data from current element
            $element_data = JSON.parse($element.children('textarea[name="element-data"]').val());

            // Check if in translation mode
            if($translating){
                // First grab current field data, then add the translation data
                if(typeof $element_data.i18n === 'undefined'){
                    $element_data.i18n = {}
                }
                $element_data.i18n[$('.super-preview-elements').attr('data-i18n')] = $fields;
                $fields = $element_data;
            }else{
                // Always append any existing translation data to the current fields array
                if(typeof $element_data.i18n !== 'undefined'){
                    $fields.i18n = $element_data.i18n;
                }
            }
            $element_data = JSON.stringify($fields);
            $element.children('textarea[name="element-data"]').val($element_data);

            var $tag = $element.data('shortcode-tag');
            var $group = $element.data('group');
            var $i18n = $('.super-preview-elements').attr('data-i18n');
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_get_element_builder_html',
                    tag: $tag,
                    group: $group,
                    builder: 0,
                    data: $fields,
                    translating: $translating,
                    i18n: $i18n,
                    form_id: $('input[name="form_id"]').val()
                },
                success: function (data) {
                    if(typeof $i18n !== 'undefined'){
                        $('.super-preview-elements').attr('data-language-changed', 'true');
                    }
                    if( $element.children('.super-element-inner').hasClass('super-dropable') ) {
                        var $shortcode = $element.children('.super-element-inner').children('.super-shortcode');
                        $(data).insertAfter($shortcode);
                        $shortcode.remove();
                    }else{
                        $element.children('.super-element-inner').html(data);
                    }
                },
                complete: function() {
                    if($tag=='column'){
                        var $sizes = {
                            '1/1':'super_one_full',
                            '4/5':'super_four_fifth',
                            '3/4':'super_three_fourth',
                            '2/3':'super_two_third',
                            '3/5':'super_three_fifth',
                            '1/2':'super_one_half',
                            '2/5':'super_two_fifth',
                            '1/3':'super_one_third',
                            '1/4':'super_one_fourth',
                            '1/5':'super_one_fifth'
                        };
                        $element.attr('class', 'super-element drop-here '+$sizes[$fields.size]+' editing');
                        $element.attr('data-size', $fields.size).find('.super-element-header .resize .current').html($fields.size);
                    }
                    SUPER.regenerate_element_inner($('.super-preview-elements'));        
                    SUPER.init_skype();
                    SUPER.init_tooltips();
                    SUPER.init_datepicker();
                    SUPER.init_masked_input();
                    SUPER.init_currency_input();
                    SUPER.init_colorpicker();
                    SUPER.init_slider_field();
                    SUPER.init_button_colors();
                    SUPER.init_text_editors();
                    $button.removeClass('super-loading');
                }
            });
        });
        
        function cancel_update(){
            $('.super-preview-elements .super-element').removeClass('editing');
            $('.super-element.super-element-settings .super-elements-container').html('<p>'+super_create_form_i18n.not_editing_an_element+'</p>');
        }

        $doc.on('click','.super-element-settings .cancel-update',function(){
            cancel_update();
        });
        
        $doc.on('change click blur keyup keydown focus', '.super-multi-items *',function(){
            SUPER.update_multi_items($(this));
        });

        $doc.on('click', '.super-checkbox input[type="checkbox"]',function(){
            var $this = $(this);
            var $parent = $this.parents('.super-checkbox:eq(0)');
            var $field = $parent.parent().children('.element-field');
            var $selected = '';
            var $counter = 0;
            $parent.find('input[type="checkbox"]').each(function(){
                if($(this).prop('checked')===true){
                    if($counter===0){
                        $selected += $(this).val();
                    }else{
                        $selected += ','+$(this).val();
                    }
                    $counter++;
                }
            });
            $field.val($selected);
            // Just to fix anoying safari and internet explorer browser issues
            SUPER.init_field_filter_visibility($this.parents('.field:eq(0)'));
        });
        
        $doc.on('click','.super-multi-items.super-dropdown-item .sorting span.up i',function(){
            var $parent = $(this).parents('.field-input:eq(0)');
            var $count = $parent.find('.super-multi-items').length;
            if($count>1){
                var $this = $(this).parents('.super-multi-items:eq(0)');
                var $prev = $this.prev();
                var $index = $this.index();
                if($index>0){
                    $this.insertBefore($prev);
                }else{
                    $this.insertAfter($parent.find('.super-multi-items').last());
                }
            }
        });

        $doc.on('click','.super-multi-items.super-dropdown-item .sorting span.down i',function(){
            var $parent = $(this).parents('.field-input:eq(0)');
            var $count = $parent.find('.super-multi-items').length;
            if($count>1){
                var $this = $(this).parents('.super-multi-items:eq(0)');
                var $next = $this.next();
                var $index = $this.index();
                if($index+1 == $count){
                    $this.insertBefore($parent.find('.super-multi-items').first());
                }else{
                    $this.insertAfter($next);
                }
            }
        });

        $doc.on('click','.super-multi-items.super-dropdown-item input[type="checkbox"]',function(){
            var $prev = $(this).attr('data-prev');
            if($prev=='true'){
                $(this).prop('checked', false).attr('data-prev','false');
            }else{
                $(this).prop('checked', true).attr('data-prev','true');
            }
        });

        $doc.on('click','.super-multi-items.super-dropdown-item input[type="radio"]',function(){
            var $prev = $(this).attr('data-prev');
            $(this).parents('.field-input:eq(0)').find('input[type="radio"]').prop('checked',false).attr('data-prev','false');
            if($prev=='true'){
                $(this).prop('checked', false).attr('data-prev','false');
            }else{
                $(this).prop('checked', true).attr('data-prev','true');
            }
        });

        $doc.on('click','.super-elements .super-element h3',function(){
            $(this).parent().children('.super-elements-container').slideToggle();
            $(this).parent().siblings().children().next('.super-elements-container').slideUp();
            return false;
        });
        
        $doc.on('click','.super-create-form .super-actions .clear',function(){
            var $clear = confirm(super_create_form_i18n.confirm_clear_form);
            if($clear === true) {
                SUPER.set_session_data('_super_elements', '');
                $('.super-preview-elements').html('');
                $('.super-element.super-element-settings .super-elements-container').html('<p>'+super_create_form_i18n.not_editing_an_element+'</p>');
            }
        });

        $doc.on('click','.super-create-form .super-actions .delete',function(){
            var $delete = confirm(super_create_form_i18n.confirm_deletion);
            if($delete === true) {
                var $this = $(this);
                $this.html('<i class="fas fa-trash-alt"></i>'+super_create_form_i18n.deleting);
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_delete_form',
                        id: $('.super-create-form input[name="form_id"]').val(),
                    },
                    success: function () {
                        $this.html('<i class="fas fa-check"></i>Deleted!');
                        window.location.href = "edit.php?post_type=super_form";
                    }
                }); 
            }
        });

        $doc.on('click','.super-load-form .load-form',function(){
            var $confirm = confirm(super_create_form_i18n.confirm_load_form);
            if($confirm === true) {
                var $parent = $(this).parent();
                var $value = $('select[name="super-forms"]').val();
                if($value===''){
                    alert(super_create_form_i18n.alert_select_form);
                }else{
                    if(($value%1)===0) {
                        $.ajax({
                            type: 'post',
                            url: ajaxurl,
                            data: {
                                action: 'super_load_form',
                                id: $('select[name="super-forms"]').val(),
                            },
                            success: function (data) {
                                SUPER.set_session_data('_super_elements', data);
                                SUPER.regenerate_element_inner(2);
                            }
                        });
                    }else{
                        var $html = $parent.find('textarea[name="'+$value+'"]').val();
                        SUPER.set_session_data('_super_elements', $html);
                        SUPER.regenerate_element_inner(2);
                    }
                }
            }
            return false;
        });    
        

        $doc.on('click','.super-element-actions .edit',function(){
            var $parent = $(this).parents('.super-element:eq(0)');
            if($parent.hasClass('editing')){
                return false;
            }
            var $data = $parent.children('textarea[name="element-data"]').val();
            var $tag = $parent.data('shortcode-tag');
            var $group = $parent.data('group');
            $data = JSON.parse($data);
            if($tag=='column'){
                $data.size = $parent.attr('data-size');
            }
            var $target = $('.super-element-settings > .super-elements-container');
            $target.html('');
            $('.super-preview-elements .super-element').removeClass('editing');
            $parent.addClass('editing');

            $('.super-element .super-elements-container').hide();
            $('.super-element.super-element-settings .super-elements-container').show().addClass('super-loading');
            
            // Check if in translation mode
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_load_element_settings',
                    id: $('.super-create-form input[name="form_id"]').val(),
                    tag: $tag,
                    group: $group,
                    data: $data,
                    translating: $('.super-preview-elements').hasClass('super-translation-mode')
                },
                success: function (data) {
                    $target.html(data);
                    init_form_settings_container_heights();
                },
                complete: function () {
                    SUPER.init_previously_created_fields();
                    SUPER.init_slider_field();
                    SUPER.init_tooltips();
                    SUPER.init_image_browser();
                    SUPER.init_color_pickers();
                    SUPER.init_field_filter_visibility();
                    $('.super-element.super-element-settings .super-elements-container').removeClass('super-loading');
                }
            });
            return false;
        });
        
        $doc.on('click','.super-create-form .super-actions .save',function(){
            var $this = $(this);
            SUPER.save_form($this);
        });

        $doc.on('click','.super-create-form .super-actions .preview',function(){
            var $this = $('.super-create-form .super-actions .preview:eq(3)');
            if($(this).hasClass('mobile')){
                $('.super-live-preview').removeClass('tablet');
                $('.super-create-form .super-actions .preview.tablet').removeClass('active');
                $('.super-create-form .super-actions .preview.desktop').removeClass('active');    
                $(this).addClass('active');
                $('.super-live-preview').addClass('mobile');
                if(!$this.hasClass('active')){
                    $this.html('Loading...');
                    SUPER.save_form($('.super-actions .save'), 1);
                }
                SUPER.init_super_responsive_form_fields();
                return false;
            }
            if($(this).hasClass('tablet')){
                $('.super-live-preview').removeClass('mobile');
                $('.super-create-form .super-actions .preview.mobile').removeClass('active');
                $('.super-create-form .super-actions .preview.desktop').removeClass('active');
                $(this).addClass('active');
                $('.super-live-preview').addClass('tablet');
                if(!$this.hasClass('active')){
                    $this.html('Loading...');
                    SUPER.save_form($('.super-actions .save'), 1);
                }
                SUPER.init_super_responsive_form_fields();
                return false;
            }
            if($(this).hasClass('desktop')){
                $('.super-live-preview').removeClass('tablet');
                $('.super-live-preview').removeClass('mobile');
                $('.super-create-form .super-actions .preview.mobile').removeClass('active');
                $('.super-create-form .super-actions .preview.tablet').removeClass('active');
                $(this).addClass('active');
                if(!$this.hasClass('active')){
                    $this.html('Loading...');
                    SUPER.save_form($('.super-actions .save'), 1);
                }
                SUPER.init_super_responsive_form_fields();
                return false;
            } 
            if(!$this.hasClass('active')){
                $this.html('Loading...');
                SUPER.save_form($('.super-actions .save'), 1);
            }else{
                $('.super-live-preview').css('display','none');
                $('.super-preview-elements').css('display','block');
                $this.html('Preview').removeClass('active');
            }
        });


        // @since 3.8.0 - reset user submission counter
        $doc.on('click','.reset-user-submission-counter', function(){
            var $reset = confirm(super_create_form_i18n.confirm_reset_submission_counter);
            if($reset === true) {
                var $button = $(this);
                $button.addClass('super-loading');
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_reset_user_submission_counter',
                        id: $('.super-create-form input[name="form_id"]').val()
                    },
                    complete: function(){
                        $button.removeClass('super-loading');
                    }
                });
            }
        });

        // @since 3.4.0 - reset submission counter
        $doc.on('click','.reset-submission-counter', function(){
            var $reset = confirm(super_create_form_i18n.confirm_reset_submission_counter);
            if($reset === true) {
                var $button = $(this);
                $button.addClass('super-loading');
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_reset_submission_counter',
                        counter: $('.super-create-form input[name="form_locker_submission_reset"]').val(),
                        id: $('.super-create-form input[name="form_id"]').val()
                    },
                    complete: function(){
                        $button.removeClass('super-loading');
                    }
                });
            }
        });
        

        // @since   1.0.6
        $doc.on('focus','.super-get-form-shortcodes',function(){
            var $this = $(this);
            $this.select();
            // Work around Chrome's little problem
            $this.mouseup(function() {
                // Prevent further mouseup intervention
                $this.unbind("mouseup");
                return false;
            });
        });

        // @since 4.0.0 - export single form settings and elements
        $doc.on('click','.super-export-import-single-form .super-export',function(){
            var $button = $(this);
            $button.addClass('super-loading');

            var $settings = {};
            $('.super-create-form .super-form-settings .element-field').each(function(){
                var $this = $(this);
                var $hidden = false;

                // select parent based on .filter class
                var $parent = $this.parents('.field.filter');
                $parent.each(function(){
                    if($(this).css('display')=='none'){
                        $hidden = true;
                    }
                });

                // now select based on only .field class
                $parent = $this.parents('.field');
                if($hidden===false){
                    var $name = $this.attr('name');
                    var $value = $this.val();
                    $settings[$name] = $value;
                }
            });
            $settings = JSON.stringify($settings);

            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_export_single_form',
                    form_id: $('.super-create-form input[name="form_id"]').val(),
                    elements: SUPER.get_session_data('_super_elements'),
                    settings: $settings
                },
                success: function (data) {
                    window.location.href = data;
                },
                error: function(){
                    alert(super_create_form_i18n.export_form_error);
                },
                complete: function(){
                    $button.removeClass('super-loading');
                }
            });
        });

        // @since 4.0.0 - import single form settings and elements
        $doc.on('click','.super-export-import-single-form .super-import',function(){
            var $confirm = confirm(super_create_form_i18n.confirm_import);
            if($confirm === true) {
                var $button = $(this);
                var $parent = $button.parents('.field:eq(0)');
                var $form_id = $('.super-create-form input[name="form_id"]').val();

                var $file_id = $parent.find('.file-preview > li').attr('data-file');
                if(typeof $file_id === 'undefined'){
                    alert(super_create_form_i18n.import_form_choose_file);
                    return false;
                }

                var $settings = $parent.find('input[name="import-settings"]').is(':checked');
                var $elements = $parent.find('input[name="import-elements"]').is(':checked');
                if($settings === false && $elements === false){
                    alert(super_create_form_i18n.import_form_select_option);
                    return false;
                }

                $button.addClass('super-loading');
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_import_single_form',
                        form_id: $form_id,
                        file_id: $file_id,
                        settings: $settings,
                        elements: $elements
                    },
                    success: function (data) {
                        data = $.parseJSON(data);
                        if( data.error ) {
                            alert(data.error);
                        }else{
                            if( $form_id===0 || $form_id==='' ) {
                                window.location.href = "admin.php?page=super_create_form&id="+data;
                            }else{
                                location.reload();
                            }
                        }
                    },
                    error: function(){
                        alert(super_create_form_i18n.import_form_error);
                    }
                });
            }
        });
        
        // @since 4.0.0 - reset single form settings
        $doc.on('click','.super-export-import-single-form .super-reset-global-settings',function(){
            var $confirm = confirm(super_create_form_i18n.confirm_reset);
            if($confirm === true) {
                var $button = $(this);
                $button.addClass('super-loading');
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_reset_form_settings',
                        form_id: $('.super-create-form input[name="form_id"]').val(),
                    },
                    success: function(data){
                        var href = window.location.href;
                        var page = href.substr(href.lastIndexOf('/') + 1);
                        var str2 = "admin.php?page=super_create_form&id";
                        if(page.indexOf(str2) == -1){
                            window.location.href = "admin.php?page=super_create_form&id="+data;
                        }else{
                            location.reload();
                        }
                    },
                    complete: function(){
                        $button.removeClass('super-loading');
                    }
                });
            }
        });

        // @since   1.0.6
        var $elements = $('.super-create-form .super-elements');
        $(window).on('load scroll resize', function() {
            var $width = $elements.outerWidth(true);
            init_form_settings_container_heights();
            var $window_width = $(window).outerWidth(true);
            if($window_width >= 983){ 
                var $scroll = $(window).scrollTop(); 
                if($scroll > 40){
                    $('.super-create-form .super-elements').css('max-width', $width+'px');
                    $('.super-create-form').addClass('sticky');
                }else{
                    $('.super-create-form .super-elements').css('max-width','');
                    $('.super-create-form').removeClass('sticky');
                }
            }else{
                $('.super-create-form .super-elements').css('max-width','');
                $('.super-create-form').removeClass('sticky');
            }
        });
        function init_form_settings_container_heights(){
            var $window_height = $(window).outerHeight(true);
            var $wp_admin_bar = $('#wpadminbar').outerHeight(true) + 55;
            var $offset_top = $('.super-create-form').offset().top;
            var $tabs_height = 0;
            var $container_padding = 50;
            var $settings_tab = 20;
            $('.super-create-form .super-elements > .super-element h3').each(function(){
                $tabs_height = $(this).outerHeight(true) + $tabs_height;  
            });
            var $max_height = $window_height - $tabs_height - $wp_admin_bar - $offset_top;
            $('.super-element-settings > .super-elements-container > .tab-content').css('max-height',$max_height-$settings_tab-$container_padding);
            $('.super-form-settings > .super-elements-container > .tab-content').css('max-height',$max_height-$settings_tab-$container_padding);
            $('.super-form-elements > .super-elements-container').css('max-height',$max_height); 
            $('.super-shortcode-fields .tabs_content').css('max-height',($window_height/2));  
        }

        SUPER.regenerate_element_inner($('.super-preview-elements'), false);
        
    });
})(jQuery);