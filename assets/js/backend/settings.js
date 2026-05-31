/* globals jQuery, SUPER, wp, ajaxurl, super_settings_i18n */

"use strict";
(function() { // Hide scope, no $ conflict

    jQuery(document).ready(function ($) {
    
        var $doc = $(document);

        SUPER.init_image_browser();
       
        $doc.on('click', '.super-checkbox input[type="checkbox"]',function(){
            var $this = $(this);
            var $parent = $this.parents('.super-checkbox:eq(0)');
            var $field = $parent.parent().children('.super-element-field');
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
        });
       
        var dateFormat = "dd-mm-yy";
        var from = $( '.super-export-import-entries input[name="from"]' ).datepicker({
            dateFormat: dateFormat,
            changeYear: true,
            defaultDate: "+1w",
            beforeShow: function(input, inst) {
                var widget = $(inst).datepicker('widget');
                widget.addClass('super-datepicker-dialog');
            }
        }) .on( "change", function() {
            to.datepicker( "option", "minDate", getDate( this ) );
        });
        var to = $( '.super-export-import-entries input[name="till"]' ).datepicker({
            dateFormat: dateFormat,
            changeYear: true,
            defaultDate: "+1w",
            beforeShow: function(input, inst) {
                var widget = $(inst).datepicker('widget');
                widget.addClass('super-datepicker-dialog');
            }
        }).on( "change", function() {
            from.datepicker( "option", "maxDate", getDate( this ) );
        });
        function getDate( element ) {
            var date;
            try {
                date = $.datepicker.parseDate( dateFormat, element.value );
            } catch( error ) {
                date = null;
            }
            return date;
        } 


        $('.browse-csv-import-file').each(function () {
            var $this = $(this);
            var $title = 'Select a CSV file';
            var $btn_name = 'Add CSV';
            var $button = $this.children('.button');
            var $preview = $this.children('.file-preview');
            var $field = $this.children('input');
            var $frame;
            var $id = $field.val();
            $preview.on('click', 'a.super-delete', function () {
                $field.val('');
                $preview.html('');
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
                        type: 'text/csv'
                    },
                    multiple: false
                });

                // When a file is selected, run a callback.
                $frame.on('select', function () {
                    var $selection = $frame.state().get('selection');
                    $selection.map(function ($attachment) {
                        $attachment = $attachment.toJSON();
                        if($attachment.mime != 'text/csv'){
                            alert('Selected file is not a CSV file!');
                        }else{
                            if ($attachment.id) {
                                $id = $attachment.id;
                                var $import_delimiter = $('input[name="import_delimiter"]').val();
                                var $import_enclosure = $('input[name="import_enclosure"]').val();
                                $.ajax({
                                    type: 'post',
                                    url: ajaxurl,
                                    data: {
                                        action: 'super_prepare_contact_entry_import',
                                        file_id: $id,
                                        import_delimiter: $import_delimiter,
                                        import_enclosure: $import_enclosure

                                    },
                                    success: function (result) {
                                        var $result = JSON.parse(result);
                                        var $html = '';
                                        $html += '<div class="image"><img src="' + $attachment.icon + '" /></div>';
                                        $html += $attachment.filename;
                                        $html += '<a href="#" class="super-delete">Delete</a>';
                                        $html += '<ul class="import-column-connections">';
                                        $.each($result, function( index, value ) {
                                            $html += '<li>';
                                            var $dropdown = '<select name="column">';
                                            $dropdown += '<option value="var">VARCHAR (default)</option>';
                                            $dropdown += '<option value="text">TEXT</option>';
                                            var $lower_case_value = value.toLowerCase();
                                            if( ( $lower_case_value=='post author' ) || ( $lower_case_value=='post_author' ) || ( $lower_case_value=='author' ) || ( $lower_case_value=='author_id' ) || ( $lower_case_value=='author id' ) || ( $lower_case_value=='user_id' ) || ( $lower_case_value=='user id' ) || ( $lower_case_value=='user' ) || ( $lower_case_value=='id' ) ) {
                                                $dropdown += '<option value="post_author" selected="selected">Author (User ID)</option>';
                                            }else{
                                                $dropdown += '<option value="post_author">Author (User ID)</option>';
                                            }
                                            if( ( $lower_case_value=='post_title' ) || ( $lower_case_value=='post title' ) || ( $lower_case_value=='entry title' ) || ( $lower_case_value=='title' ) || ( $lower_case_value=='name' ) ) {
                                                $dropdown += '<option value="post_title" selected="selected">Contact Entry Title (Post Title)</option>';
                                            }else{
                                                $dropdown += '<option value="post_title">Contact Entry Title (Post Title)</option>';
                                            }
                                            if( ( $lower_case_value=='date' ) || ( $lower_case_value=='post_date' ) || ( $lower_case_value=='post date' ) || ( $lower_case_value=='publish_date' ) || ( $lower_case_value=='publish date' ) ) {
                                                $dropdown += '<option value="post_date" selected="selected">Date (publish date)</option>';
                                            }else{
                                                $dropdown += '<option value="post_date">Date (publish date)</option>';
                                            }
                                            if( ( $lower_case_value=='ip' ) || ( $lower_case_value=='ip_address' ) || ( $lower_case_value=='ip address' ) ) {
                                                $dropdown += '<option value="ip_address" selected="selected">IP address</option>';
                                            }else{
                                                $dropdown += '<option value="ip_address">IP address</option>';
                                            }
                                            if( ( $lower_case_value=='form' ) || ( $lower_case_value=='form_id' ) || ( $lower_case_value=='form id' ) ) {
                                                $dropdown += '<option value="form_id" selected="selected">Form ID</option>';
                                            }else{
                                                $dropdown += '<option value="form_id">Form ID</option>';
                                            }
                                            if( ( $lower_case_value=='file' ) || ( $lower_case_value=='files' )  || ( $lower_case_value=='image' ) || ( $lower_case_value=='images' ) ) {
                                                $dropdown += '<option value="file" selected="selected">File / Image</option>';
                                            }else{
                                                $dropdown += '<option value="file">File / Image</option>';
                                            }

                                            $dropdown += '</select>';
                                            $html += '<label><span>Save as: </span>'+$dropdown+'</label>';
                                            $html += '<label><span>Field Label: </span><input type="text" name="label" value="'+value+'" /></label>';
                                            $html += '<label><span>Field Name: </span><input type="text" name="name" value="'+value+'" /></label>';
                                            $html += '</li>';
                                        });
                                        $html += '</ul>';
                                        $html += '<div class="delimiter-enclosure">';
                                        $html += '<span>Delimiter:</span> <input type="text" value="," name="import_delimiter" />';
                                        $html += '<span>Enclosure:</span> <input type="text" value="' + (String('"').replace(/"/g, '&quot;')) + '" name="import_enclosure" />';
                                        $html += '</div>';
                                        $html += '<label class="skip-first-row"><input type="checkbox" name="skip_first" /> Skip the first row of the CSV file</label>';
                                        $html += '<span class="button super-button super-import-contact-entries"><i class="fas fa-cogs"></i> Click here to start the Import</span>';
                                        $preview.html($html);
                                    }
                                });
                                
                            }
                        }
                    });
                    $field.val($id);
                });

                $frame.on('close', function () {
                    $('.ui-widget-overlay').show();
                    $this.parents('.shortcode-dialog').show();
                });
                
                // Finally, open the modal.
                $frame.open();
                
            });
        });

        $doc.on('click', '.super-import-contact-entries', function () {
            var $id = $('input[name="csv_import_file"]').val();
            var $column_connections = {};
            var $i = 0;
            $('.import-column-connections > li ').each(function(){
                var $this = $(this);
                var $column = $this.find('select[name="column"]').val();
                var $label = $this.find('input[name="label"]').val();
                var $name = $this.find('input[name="name"]').val();
                if( $column=='form_id' ) $name = 'hidden_form_id';
                $column_connections[$i] = {
                    'column':$column,
                    'name':$name,
                    'label':$label,
                };
                $i++;
            });
            var $skip_first = $('input[name="skip_first"]').is(':checked');
            var $import_delimiter = $('input[name="import_delimiter"]').val();
            var $import_enclosure = $('input[name="import_enclosure"]').val();
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_import_contact_entries',
                    file_id: $id,
                    column_connections: $column_connections,
                    skip_first: $skip_first,
                    import_delimiter: $import_delimiter,
                    import_enclosure: $import_enclosure
                },
                success: function (result) {
                    $('.super-export-import-entries .file-preview').html(result);
                }
            });
        });

        $doc.on('click','.super-settings .super-export-entries',function(){
            var $this = $(this);
            var $oldHtml = $this.html();
            var $type = $this.data('type');
            var $from = $('.super-export-import-entries input[name="from"]').val();
            var $form_ids = $('.super-export-import-entries input[name="form_ids"]').val();
            var $order_by = $('.super-export-import-entries select[name="order_by"]').val();
            var $till = $('.super-export-import-entries input[name="till"]').val();
            var $delimiter = $('.super-export-import-entries input[name="delimiter"]').val();
            var $enclosure = $('.super-export-import-entries input[name="enclosure"]').val();
            $this.html(super_settings_i18n.export_entries_working);
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_export_entries',
                    type: $type,
                    from: $from,
                    form_ids: $form_ids,
                    order_by: $order_by,
                    till: $till,
                    delimiter: $delimiter,
                    enclosure: $enclosure
                },
                success: function (data) {
                    window.location.href = data;
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    console.log(xhr, ajaxOptions, thrownError);
                    alert(super_settings_i18n.export_entries_error);
                },
                complete: function(){
                    $this.html($oldHtml);
                }
            });
        });
        $doc.on('click', '.super-field .super-tags li', function(){
            var $tag = $(this).data('value');
            var $field = $(this).parents('.super-field:eq(0)').find('textarea');
            $(this).parents('.super-tags:eq(0)').removeClass('super-active');
            var cursorPosStart = $field.prop('selectionStart');
            var cursorPosEnd = $field.prop('selectionEnd');
            var v = $field.val();
            var textBefore = v.substring(0,  cursorPosStart );
            var textAfter  = v.substring( cursorPosEnd, v.length );
            $field.val( textBefore+ $tag +textAfter );
            return false;
        });

        $doc.on('click', '.super-field .super-tags', function(){
            $(this).toggleClass('super-active');
        });

        $('.super-settings .super-wrapper .super-fields .super-field textarea').each(function(){
            var $tags = $('.super-settings > .super-wrapper > .super-tags').clone();
            $($tags).insertBefore($(this));
        });
            
        $doc.on('click','.super-settings .super-restore-default',function(){ 
            if(confirm(super_settings_i18n.restore_default_confirm) === true) {
                var $this = $(this);
                $this.val(super_settings_i18n.restore_default_working);
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_load_default_settings',
                    },
                    success: function () {
                        location.reload();
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        $('.save .message').removeClass('super-success').addClass('error').html(super_settings_i18n.restore_default_error);
                        console.log(xhr, ajaxOptions, thrownError);
                    }
                });
            }
        });

        $doc.on('click','.super-settings .save-settings',function(){ 
            var $this = $(this);
            $this.html(super_settings_i18n.save_loading);
            $('.save .message').removeClass('error').removeClass('super-success');
            var $data = [];
            $('.super-fields .super-element-field').each(function(){
                var $this = $(this);
                var $hidden = false;
                $this.parents('.super-field.super-filter').each(function(){
                    if($(this).css('display')=='none'){
                        $hidden = true;
                    }
                });
                if($hidden===false){
                    var $name = $this.attr('name');
                    var $value = $this.val();
                    $data.push({
                        'name':$name,
                        'value':$value
                    });
                }
            });
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_save_settings',
                    data: $data,
                },
                success: function (data) {
                    data = $.parseJSON(data);
                    if((data !== null) && (data.error !== 'undefined')){
                        if(data.error=='smtp_error'){
                            $('.save .message').removeClass('super-success').addClass('error').html(data.msg);
                            var $tab = $('input[name="smtp_username"]').parents('.super-fields:eq(0)').index() - 1;
                            $('.super-tabs > li, .super-wrapper > .super-fields').removeClass('super-active');
                            $('.super-tabs li:eq('+$tab+')').addClass('super-active');
                            $('.super-wrapper .super-fields:eq('+$tab+')').addClass('super-active');
                            return false;
                        }
                    }
                    $this.html(super_settings_i18n.save_settings);
                    $('.save .message').removeClass('error').addClass('super-success').html(super_settings_i18n.save_success);
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    $('.save .message').removeClass('super-success').addClass('error').html(super_settings_i18n.save_error);
                    console.log(xhr, ajaxOptions, thrownError);
                }
            });
        });

        $doc.on('click','.super-tabs li',function(){
            if(!$(this).hasClass('save')){
                $('.super-tabs li').removeClass('super-active');
                $(this).addClass('super-active');
                $('.super-wrapper .super-fields').removeClass('super-active');
                $('.super-wrapper .super-fields:eq('+$(this).index()+')').addClass('super-active');
                location.hash = $(this).attr('data-key');
            }
        });

        var $current_tab = window.location.hash.substring(1);
        if($current_tab!==''){
            if($('.super-tabs li[data-key="'+$current_tab+'"]').length){
                $('.super-tabs li[data-key="'+$current_tab+'"]').trigger('click');
            }
        }
        if ("onhashchange" in window) { // event supported?
            window.onhashchange = function () {
                var $current_tab = window.location.hash.substring(1);
                if($current_tab!==''){
                    if($('.super-tabs li[data-key="'+$current_tab+'"]').length){
                        $('.super-tabs li[data-key="'+$current_tab+'"]').trigger('click');
                    }
                }
            };
        }
        else { // event not supported:
            var storedHash = window.location.hash;
            window.setInterval(function () {
                if (window.location.hash != storedHash) {
                    var $current_tab = window.location.hash.substring(1);
                    if($current_tab!==''){
                        if($('.super-tabs li[data-key="'+$current_tab+'"]').length){
                            $('.super-tabs li[data-key="'+$current_tab+'"]').trigger('click');
                        }
                    }
                }
            }, 100);
        }

        // @since   1.0.6
        $doc.on('click','.super-settings .super-import-settings, .super-settings .super-load-default-settings',function(){
            var $method = 'import';
            var $button = $(this);
            var $settings = $('.super-export-import textarea[name="import-json"]').val();
            $button.addClass('super-loading');
            if($button.hasClass('super-load-default-settings')){
                $method = 'load-default';
            }
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_import_global_settings',
                    settings: $settings,
                    method: $method,
                },
                success: function(){
                    $button.val(super_settings_i18n.save_settings);
                    $('.save .message').removeClass('error').addClass('super-success').html(super_settings_i18n.save_success);
                    location.reload();
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    $('.save .message').removeClass('super-success').addClass('error').html(super_settings_i18n.save_error);
                    console.log(xhr, ajaxOptions, thrownError);
                },
                complete: function(){
                    $button.removeClass('super-loading');

                }
            });
        });

        // @since 1.9 - export forms
        function super_export_forms($this, offset, found){
            var limit = 100;
            if(typeof offset === 'undefined') offset = 0;
            if(typeof found === 'undefined') found = '';
            if(found===''){
                $this.html(super_settings_i18n.export_entries_working);
            }
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_export_forms',
                    offset: offset,
                    limit: limit,
                    found: found
                },
                success: function (data) {
                    var do_timeout;
                    data = JSON.parse(data);
                    if(data.offset>data.found){
                        setTimeout(function() {
                            $this.html('Completed ('+data.found+'/'+data.found+')');
                            window.open(data.file_url);
                        }, 10*limit);
                        return false;
                    }else{
                        do_timeout = true;
                        var prev_offset = data.offset-limit;
                        if(prev_offset<=0) prev_offset = 0;
                        setInterval(function() {
                          if (prev_offset < data.offset) {
                            $this.html(super_settings_i18n.export_entries_working+' ('+prev_offset+'/'+data.found+')');
                          }
                          prev_offset++;
                        }, 20);
                    }
                    if(typeof do_timeout !== 'undefined'){
                        setTimeout(function() {
                            super_export_forms($this, data.offset, data.found);
                        }, 10*limit);
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    console.log(xhr, ajaxOptions, thrownError);
                    alert(super_settings_i18n.export_forms_error);
                }
            });
        }

        $doc.on('click','.super-settings .super-export-forms',function(){
            super_export_forms($(this));
        });

        // @since 1.9 - import forms
        $('.browse-forms-import-file').each(function () {
            var $this = $(this);
            var $title = 'Select import file';
            var $btn_name = 'Add file';
            var $button = $this.children('.button');
            var $preview = $this.children('.file-preview');
            var $field = $this.children('input');
            var $frame;
            var $id = $field.val();
            $preview.on('click', 'a.super-delete', function () {
                $field.val('');
                $preview.html('');
            });
            $button.on('click', function () {
                var $oldHtml = $button.html();
                $button.html(super_settings_i18n.import_working);
                $('.ui-widget-overlay').hide();
                $this.parents('.shortcode-dialog').hide();
                if ($frame) {
                    $frame.open();
                    return;
                }
                $frame = wp.media.frames.downloadable_file = wp.media({
                    title: $title,
                    button: {
                        text: $btn_name
                    },
                    library: { 
                        type: ['text/html','text/plain']
                    },
                    multiple: false
                });
                $frame.on('select', function () {
                    var $selection = $frame.state().get('selection');
                    $selection.map(function ($attachment) {
                        $attachment = $attachment.toJSON();
                        if($attachment.mime != 'text/html' && $attachment.mime != 'text/plain'){
                            alert('Selected file is not a TXT file!');
                        }else{
                            if ($attachment.id) {
                                $id = $attachment.id;
                                $.ajax({
                                    type: 'post',
                                    url: ajaxurl,
                                    data: {
                                        action: 'super_start_forms_import',
                                        file_id: $id,
                                    },
                                    success: function () {
                                        window.location.href = "edit.php?post_type=super_form";
                                        $('<div>'+super_settings_i18n.import_completed+'!</div>').insertAfter($button);
                                        $button.remove();
                                    },
                                    error: function (xhr, ajaxOptions, thrownError) {
                                        console.log(xhr, ajaxOptions, thrownError);
                                        alert(super_settings_i18n.import_error);
                                    },
                                    complete: function(){
                                        $button.html($oldHtml);
                                    }
                                });
                            }
                        }
                    });
                });
                $frame.on('close', function () {
                    $('.ui-widget-overlay').show();
                    $this.parents('.shortcode-dialog').show();
                });
                $frame.open();
            });
        });
    });
})(jQuery);
