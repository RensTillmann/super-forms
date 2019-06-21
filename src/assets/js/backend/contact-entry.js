/* globals jQuery, inlineEditPost, ajaxurl */
"use strict";
(function() { // Hide scope, no $ conflict

    jQuery(document).ready(function ($) {

        var $doc = $(document);
        
        var sffrom = $('input[name="sffrom"]'),
            sfto = $('input[name="sfto"]');
        $('input[name="sffrom"], input[name="sfto"]' ).datepicker();
            // To make it 2018-01-01, add this - datepicker({dateFormat : "yy-mm-dd"});
            sffrom.on( 'change', function() {
            sfto.datepicker( 'option', 'minDate', sffrom.val() );
        });
        sfto.on( 'change', function() {
            sfto.datepicker( 'option', 'maxDate', sfto.val() );
        });

        $('.post-type-super_contact_entry select[name="_status"]').each(function(){
            $(this).html('<option value="-1">— No changes —</option><option value="super_unread">Unread</option><option value="super_read">Read</option>');
        });
        
        // @since 1.7 - update the contact entry values
        $doc.on('click', '.super-update-contact-entry', function(){
            var $button = $(this);
            var $old_html = $button.html();
            var $id = $button.data('contact-entry');
            var $entry_status = $('select[name="entry_status"]').val();
            $button.html('Loading...').addClass('disabled');
            var $data = {};
            $('.super-shortcode-field').each(function(){
                var $name = $(this).attr('name');
                var $value = $(this).val();
                $data[$name] = $value;
            });
            
            // @since 3.3.0 - ability to update Contact Entry title
            $data.super_contact_entry_post_title = $('input[name="super_contact_entry_post_title"]').val();

            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_update_contact_entry',
                    id: $id,
                    entry_status: $entry_status,
                    data: $data
                },
                success: function (result) {
                    var $msg = '';
                    var $result = jQuery.parseJSON(result);
                    if($result.error===true){
                        $msg += '<div id="message" class="error notice notice-error is-dismissible">';
                    }else{
                        $msg += '<div id="message" class="updated notice notice-success is-dismissible">';
                    }
                    $msg += '<p>'+$result.msg+'</p>';
                    $msg += '</div>';
                    $($msg).insertBefore('#poststuff');
                },
                complete: function() {
                    $button.html($old_html).removeClass('disabled');
                }
            });
        });

        // @since 1.7 - export individual contact entries
        $doc.on('click', '.super-export-entries', function(){
            var $button = $(this);
            var $old_html = $button.html();
            var $selected_entries = $('input[name="post[]"]:checked');
            if($selected_entries.length===0){
                alert('No Contact Entries Selected!');
            }else{
                var $entries = [];
                $.each($selected_entries, function( index, v ) {
                    $entries[index] = v.value;
                });
                $button.html('Loading...').addClass('disabled');
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_get_entry_export_columns',
                        entries: $entries
                    },
                    success: function (data) {
                        $('.super-export-entries-thickbox').trigger('click');
                        $('#TB_ajaxContent').html(data);
                        $('.super-export-entry-columns').sortable({
                            placeholder: "super-entry-column sortable-placeholder",
                            opacity: 0.8,
                            forcePlaceholderSize: true,
                            forceHelperSize: true,
                            axis: "y"
                        }); 
                    },
                    complete: function() {
                        $button.html($old_html).removeClass('disabled');
                    }
                });
            }
        });
        $doc.on('click', '.super-export-selected-columns-toggle', function(){
            var $checkboxes = $('.super-export-entry-columns input[type="checkbox"]');
            $checkboxes.prop("checked", !$checkboxes.prop("checked"));
        });
        $doc.on('click', '.super-export-selected-columns', function(){
            var $dialog = $(this).parent();
            var $query = $dialog.find('input[name="query"]').val();
            var $columns = {};
            $dialog.find('.super-export-entry-columns > .super-entry-column').each(function(){
                var $checked = $(this).children('input[type="checkbox"]').is(":checked");
                if($checked){
                    var $field_name = $(this).children('span.name').text();
                    var $column_name = $(this).children('input[type="text"]').val();
                    $columns[$field_name] = $column_name;
                }
            });
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_export_selected_entries',
                    columns: $columns,
                    query: $query
                },
                success: function (data) {
                    window.location.href = data;
                }
            });
        });

        $doc.on('click','.super-mark-unread',function(){
            var $this = $(this);
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_mark_unread',
                    contact_entry: $(this).attr('data-contact-entry')
                },
                success: function () {
                   $this.parents('.status-super_read').removeClass('status-super_read').addClass('status-super_unread');
                }
            });
        });
        $doc.on('click','.super-mark-read',function(){
            var $this = $(this);
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_mark_read',
                    contact_entry: $(this).attr('data-contact-entry')
                },
                success: function () {
                   $this.parents('.status-super_unread').removeClass('status-super_unread').addClass('status-super_read');
                }
            });
        });
        $doc.on('click','.super-delete-contact-entry',function(){
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_delete_contact_entry',
                    contact_entry: $(this).attr('data-contact-entry')
                },
                success: function () {
                    window.location.href = "edit.php?post_type=super_contact_entry";
                }
            });
        });  
        
        $doc.on('click','.super-print-contact-entry',function(){
            var myWindow=window.open();
            myWindow.document.write("<style type=\"text/css\">");
            myWindow.document.write("body {font-family:Arial,sans-serif;color:#444;-webkit-print-color-adjust:exact;}");
            myWindow.document.write("table {font-size:12px;}");
            myWindow.document.write("table th{text-align:right;font-weight:bold;font-size:12px;padding-right:5px;}");
            myWindow.document.write("table td{font-size:12px;}");
            myWindow.document.write("table tr:last-child{visibility:hidden;}");
            myWindow.document.write("</style>");
            var $html = '<table>';
            $('#super-contact-entry-data .inside tr').each(function(){
                $html += '<tr>';
                $html += '<th>';
                $html += $(this).children('th').text();
                $html += '</th>';
                $html += '<td>';
                    if($(this).find('input').length){
                        $html += $(this).find('input').val();
                    }
                    if($(this).find('textarea').length){
                        $html += $(this).find('textarea').val();
                    }
                    if($(this).find('img').length){
                        $html += '<img src="'+$(this).find('img').attr('src')+'" />';
                    }
                $html += '</td>';
                $html += '</tr>';
            });
            $html += '</table>';
            myWindow.document.write($html);
            myWindow.document.close();
            myWindow.focus();
            // @since 2.3 - chrome browser bug
            setTimeout(function() {
                myWindow.print();
                myWindow.close();
            }, 250);
            return false;
        });

        // we create a copy of the WP inline edit post function
        var $wp_inline_edit = inlineEditPost.edit;

        // and then we overwrite the function with our own code
        inlineEditPost.edit = function( id ) {

            // "call" the original WP edit function
            // we don't want to leave WordPress hanging
            $wp_inline_edit.apply( this, arguments );

            // now we take care of our business

            // get the post ID
            var $post_id = 0;
            if ( typeof( id ) == 'object' ) {
                $post_id = parseInt( this.getId( id ), 10 );
            }

            if ( $post_id > 0 ) {
                // define the edit row
                var $edit_row = $( '#edit-' + $post_id );
                var $post_row = $( '#post-' + $post_id );

                // get the data
                var $entry_status = $( '.column-entry_status', $post_row ).text();

                // populate the data
                $( ':select[name="entry_status"]', $edit_row ).val( $entry_status );
            }
        };


        // @since 3.4.0 - custom entry status updating
        // we create a copy of the WP inline edit post function
        $wp_inline_edit = inlineEditPost.edit;
        
        // and then we overwrite the function with our own code
        inlineEditPost.edit = function( id ) {
            // "call" the original WP edit function
            // we don't want to leave WordPress hanging
            $wp_inline_edit.apply( this, arguments );

            // now we take care of our business

            // get the post ID
            var $post_id = 0;
            if ( typeof( id ) == 'object' )
                $post_id = parseInt( this.getId( id ), 10 );

            if ( $post_id > 0 ) {
                // define the edit row
                var $edit_row = $( '#edit-' + $post_id );
                var $post_row = $( '#post-' + $post_id );

                // get the data
                var $entry_status = $( '.column-entry_status', $post_row ).html();

                // populate the data
                $( ':select[name="entry_status"]', $edit_row ).val( $entry_status );
            }
        };

        $( document ).on( 'click', '#bulk_edit', function() {
            // define the bulk edit row
            var $bulk_row = $( '#bulk-edit' );

            // get the selected post ids that are being edited
            var $post_ids = [];
            $bulk_row.find( '#bulk-titles' ).children().each( function() {
                $post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
            });

            // get the data
            var $entry_status = $bulk_row.find( 'select[name="entry_status"]' ).val();

            // save the data
            $.ajax({
                url: ajaxurl, // this is a variable that WordPress has already defined for us
                type: 'POST',
                async: false,
                cache: false,
                data: {
                    action: 'super_bulk_edit_entries', // this is the name of our WP AJAX function that we'll set up next
                    post_ids: $post_ids, // and these are the 2 parameters we're passing to our function
                    entry_status: $entry_status
                }
            });
        });


    });

})(jQuery);
