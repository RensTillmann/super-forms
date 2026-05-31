/* globals jQuery, inlineEditPost, ajaxurl */
"use strict";
(function() { // Hide scope, no $ conflict

    jQuery(document).ready(function ($) {

        function super_save_export_order(){
            var $columns = [];
            $('.super-export-entry-columns > li').each(function(){
                var $field_name = $(this).children('span.name').text(),
                    $column_name = $(this).children('input[type="text"]').val(),
                    $checked = $(this).children('input[type="checkbox"]').is(":checked");
                $columns.push({
                    field_name: $field_name,
                    column_name: $column_name,
                    checked: $checked
                });
            });
            $columns = JSON.stringify($columns.reverse());
            localStorage.setItem('_super_entry_order', $columns);
            var $delimiter = $('.super-contact-entries-export-modal input[name="delimiter"]').val();
            var $enclosure = $('.super-contact-entries-export-modal input[name="enclosure"]').val();
            var $order_by = $('.super-contact-entries-export-modal select[name="order_by"]').val();
            localStorage.setItem('_super_export_single_entries_delimiter', $delimiter);
            localStorage.setItem('_super_export_single_entries_enclosure', $enclosure);
            localStorage.setItem('_super_export_single_entries_order_by', $order_by);
        }

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
            var $oldHtml = $button.html();
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
                    var $result = JSON.parse(result);
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
                    $button.html($oldHtml).removeClass('disabled');
                }
            });
        });

        // @since 1.7 - export individual contact entries
        $doc.on('click', '.super-export-entries', function(){
            var $button = $(this);
            var $oldHtml = $button.html();
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
                        $button.html($oldHtml).removeClass('disabled');
                        // Reorder and re-check based on local storage
                        var $delimiter = localStorage.getItem('_super_export_single_entries_delimiter');
                        var $enclosure = localStorage.getItem('_super_export_single_entries_enclosure');
                        var $order_by = localStorage.getItem('_super_export_single_entries_order_by');
                        var $columns = localStorage.getItem('_super_entry_order');
                        $columns = JSON.parse($columns);
                        if($columns){
                            // Loop over each item and put it at the top of the list each time
                            $.each($columns, function( index, v ) {
                                var $item = $('.super-export-entry-columns .super-entry-column[data-name="'+v.field_name+'"]');
                                if($item){
                                    $item.parent().prepend($item);
                                    // Check the item if required
                                    if(v.checked){
                                        $item.find('input[type="checkbox"]').prop("checked", true);
                                    }
                                }
                            });
                        }
                        if($delimiter) $('.super-contact-entries-export-modal input[name="delimiter"]').val($delimiter); 
                        if($enclosure) $('.super-contact-entries-export-modal input[name="enclosure"]').val($enclosure);
                        if($order_by) $('.super-contact-entries-export-modal select[name="order_by"]').val($order_by);
                    }
                });
            }
        });
        $doc.on('click', '.super-export-selected-columns-toggle', function(){
            var $checkboxes = $('.super-export-entry-columns input[type="checkbox"]');
            $checkboxes.prop("checked", !$checkboxes.prop("checked"));
        });
        $doc.on('click', '.super-export-selected-columns', function(){
            var $btn = $(this);
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
            $btn.addClass('super-loading');
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_export_selected_entries',
                    delimiter: $('.super-contact-entries-export-modal input[name="delimiter"]').val(),
                    enclosure: $('.super-contact-entries-export-modal input[name="enclosure"]').val(),
                    order_by: $('.super-contact-entries-export-modal select[name="order_by"]').val(),
                    columns: $columns,
                    query: $query
                },
                success: function (data) {
                    window.location.href = data;
                },
                complete: function(){
                    $btn.removeClass('super-loading');
                    super_save_export_order();
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
            var i,ii,nodes,files, url, fileName, myWindow,
            fileExtension,
            imageExtensions = ['jpeg', 'jpg', 'gif', 'png'],
            html = '<table>';

            nodes = document.querySelectorAll('#super-contact-entry-data .inside tr');
            for( i = 0; i < nodes.length; i++ ) {
                html += '<tr>';
                html += '<th>';
                html += nodes[i].querySelector('th').innerText;
                html += '</th>';
                html += '<td>';
                    if(nodes[i].querySelector('input')){
                        html += nodes[i].querySelector('input').value;
                    }else if(nodes[i].querySelector('textarea')){
                        html += nodes[i].querySelector('textarea').value;
                    }else if(nodes[i].classList.contains('super-signature')){
                        url = nodes[i].querySelector('img').src;
                        html += '<img src="'+url+'" />';
                    }else if(nodes[i].classList.contains('super-file-upload')){
                        files = nodes[i].querySelectorAll('.super-file');
                        for ( ii = 0; ii < files.length; ii++ ) {
                            url = files[ii].href;
                            fileName = files[ii].innerText;
                            var f = SUPER.get_file_name_and_extension(url);
                            fileExtension = f.ext.toLowerCase();
                            if(ii>0) html += '<br /><br />';
                            if(imageExtensions.indexOf(fileExtension)!==-1){
                                html += '['+fileName+']<br /><img class="super-image" src="'+url+'" />';
                            }else{
                                html += '['+fileName+']<br />('+url+')';
                            }
                        }
                    }else{
                        // Just get the HTML
                        html += nodes[i].querySelector('td').innerHTML;
                    }
                html += '</td>';
                html += '</tr>';
            }
            html += '</table>';

            myWindow = window.open();
            
            myWindow.document.write("<style type=\"text/css\">");
            myWindow.document.write("body {font-family:Arial,sans-serif;color:#444;-webkit-print-color-adjust:exact;}");
            myWindow.document.write("table {font-size:12px;}");
            myWindow.document.write("table th{vertical-align:top;text-align:right;font-weight:bold;font-size:12px;padding-right:5px;}");
            myWindow.document.write("table td{font-size:12px;vertical-align:top;}");
            myWindow.document.write(".super-image {margin-top:5px;border:1px solid #eee;padding:5px;max-width:75%}");
            myWindow.document.write("</style>");         
         
            myWindow.document.write(html);
            myWindow.document.close();
            myWindow.focus();
            // @since 2.3 - chrome browser bug
            setTimeout(function() {
                myWindow.print();
                myWindow.close();
            }, 250);
            return false;
        });

        if(typeof inlineEditPost !== 'undefined'){
            // @since 3.4.0 - custom entry status updating
            // we create a copy of the WP inline edit post function
            var $wp_inline_edit = inlineEditPost.edit;
            
            // and then we overwrite the function with our own code
            inlineEditPost.edit = function( id ) {
                // "call" the original WP edit function
                // we don't want to leave WordPress hanging
                $wp_inline_edit.apply( this, arguments );

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
        }

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
