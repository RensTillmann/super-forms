/* globals SUPER */
"use strict";
(function($) { // Hide scope, no $ conflict

    // Get index of element based on parent node
    var getChildIndex = function(child){
        var parent = child.parentNode;
        var children = parent.children;
        var i = children.length - 1;
        for (; i >= 0; i--){
            if (child == children[i]){
                break;
            }
        }
        return i;
    };

    // Remove class from elements
    var removeClass = function(elements, class_name){
        for (var key = 0; key < elements.length; key++) {
            elements[key].classList.remove(class_name);
        }
    };

    // Get all siblings
    var getSiblings = function (node) {
        // Setup siblings array and get the first sibling
        var siblings = [];
        var sibling = node.parentNode.firstChild;
        // Loop through each sibling and push to the array
        while (sibling) {
            if (sibling.nodeType === 1 && sibling !== node) {
                siblings.push(sibling);
            }
            sibling = sibling.nextSibling
        }
        return siblings;
    };


    SUPER.frontEndListing = {};

    SUPER.add_listings = function(data){
        data = JSON.parse(data);
        data.settings._listings = {};
        // Loop through all the listings
        var list = document.querySelectorAll('.front-end-listing-list > li');
        for (var key = 0; key < list.length; key++) {
            data.settings._listings[key] = {};
            data.settings._listings[key].name = list[key].querySelector('input[name="name"]').value;
            data.settings._listings[key].display_based_on = list[key].querySelector('[data-name="display_based_on"]').querySelector('.super-active').dataset.value;
            if(data.settings._listings[key].display_based_on=='specific_forms'){
                data.settings._listings[key].form_ids = list[key].querySelector('input[name="form_ids"]').value;
            }
            data.settings._listings[key].date_range = false;
            data.settings._listings[key].date_range_from = '';
            data.settings._listings[key].date_range_till = '';
            if(list[key].querySelector('[data-name="date_range"]').classList.contains('super-active')){
                data.settings._listings[key].date_range_from = list[key].querySelector('input[name="date_range_from"]').value;
                data.settings._listings[key].date_range_till = list[key].querySelector('input[name="date_range_till"]').value;
                data.settings._listings[key].date_range = true;
            }
            data.settings._listings[key].show_title = false;
            data.settings._listings[key].title_position = '';
            if(list[key].querySelector('[data-name="show_title"]').classList.contains('super-active')){
                data.settings._listings[key].show_title = true;
                data.settings._listings[key].title_name = list[key].querySelector('input[name="title_name"]').value;
                data.settings._listings[key].title_placeholder = list[key].querySelector('input[name="title_placeholder"]').value;
                data.settings._listings[key].title_position = list[key].querySelector('input[name="title_position"]').value;
                data.settings._listings[key].title_width = list[key].querySelector('input[name="title_width"]').value;
            }
            data.settings._listings[key].show_status = false;
            data.settings._listings[key].status_position = '';
            if(list[key].querySelector('[data-name="show_status"]').classList.contains('super-active')){
                data.settings._listings[key].show_status = true;
                data.settings._listings[key].status_name = list[key].querySelector('input[name="status_name"]').value;
                data.settings._listings[key].status_placeholder = list[key].querySelector('input[name="status_placeholder"]').value;
                data.settings._listings[key].status_position = list[key].querySelector('input[name="status_position"]').value;
                data.settings._listings[key].status_width = list[key].querySelector('input[name="status_width"]').value;
            }
            data.settings._listings[key].show_date = false;
            data.settings._listings[key].date_position = '';
            if(list[key].querySelector('[data-name="show_date"]').classList.contains('super-active')){
                data.settings._listings[key].show_date = true;
                data.settings._listings[key].date_name = list[key].querySelector('input[name="date_name"]').value;
                data.settings._listings[key].date_placeholder = list[key].querySelector('input[name="date_placeholder"]').value;
                data.settings._listings[key].date_position = list[key].querySelector('input[name="date_position"]').value;
                data.settings._listings[key].date_width = list[key].querySelector('input[name="date_width"]').value;
            }

            // Add custom columns
            data.settings._listings[key].custom_columns = false;
            if(list[key].querySelector('[data-name="custom_columns"]').classList.contains('super-active')){
                data.settings._listings[key].custom_columns = true;
                data.settings._listings[key].columns = {};
                var columns = document.querySelectorAll('.front-end-listing-list div[data-name="custom_columns"] li');
                for (var ckey = 0; ckey < columns.length; ckey++) {
                    data.settings._listings[key].columns[ckey] = {};
                    data.settings._listings[key].columns[ckey].name = columns[ckey].querySelector('input[name="name"]').value;
                    data.settings._listings[key].columns[ckey].field_name = columns[ckey].querySelector('input[name="field_name"]').value;
                    data.settings._listings[key].columns[ckey].width = columns[ckey].querySelector('input[name="width"]').value;
                }
            }

            data.settings._listings[key].pagination = list[key].querySelector('[data-name="pagination"]').querySelector('.super-active').dataset.value;
            data.settings._listings[key].limit = list[key].querySelector('select[name="limit"]').value;
        }
        console.log(data);
        data = JSON.stringify(data);
        return data;
    };

    // Sort column up/down
    SUPER.frontEndListing.sortColumn = function(el, method){       
        if(typeof method==='undefined') method = 'up';
        var source = el.parentNode;
        var target_element = source.parentNode;
        if(method=='up' && source.previousElementSibling ) {
            target_element.insertBefore(source, source.previousElementSibling);
            return false;
        }
        if(method=='down' && source.nextElementSibling){
            target_element.insertBefore(source.nextElementSibling, source);
            return false;
        }
    };

    // Add/Delete custom columns
    SUPER.frontEndListing.addColumn = function(el){
        var source = el.parentNode;
        var target_element = source.parentNode;
        var node = source.cloneNode(true);
        target_element.insertBefore(node, target_element.lastChild);
    };
    SUPER.frontEndListing.deleteColumn = function(el){
        el.parentNode.remove();
    };

    // Show Listing settings
    SUPER.frontEndListing.toggleSettings = function(el){
        var li = el.parentNode;
        if(li.classList.contains('super-active')){
            li.classList.remove('super-active');
        }else{
           li.classList.add('super-active');
        }
    };

    // Delete Listing
    SUPER.frontEndListing.deleteListing = function(el){
        var li = el.parentNode;
        if(getChildIndex(li)!=0) {
            li.remove();
        }
    };

    // Create Listing
    SUPER.frontEndListing.addListing = function(){
        var target_element = document.querySelector('.front-end-listing-list');
        var source = target_element.firstElementChild;
        var list = document.querySelector('.front-end-listing-list');
        var node = source.cloneNode(true);
        // Change shortcode
        var form_id = document.querySelector('.super-header input[name="form_id"]').value;
        node.querySelector('.super-get-form-shortcodes').value = '[super_listing list="'+(list.children.length+1)+'" id="'+form_id+'"]';
        removeClass(node.querySelectorAll('.tooltipstered'), 'tooltipstered');
        var elements = node.querySelectorAll('.super-tooltip');
        for (var key = 0; key < elements.length; key++) {
            elements[key].title = elements[key].dataset.title;
        }
        target_element.insertBefore(node, list.lastChild);
        SUPER.init_tooltips();
    };

    // When radio button is clicked
    SUPER.frontEndListing.radio = function(el){
        removeClass(getSiblings(el), 'super-active');
        el.classList.add('super-active');
    };

    // When checkbox is clicked
    SUPER.frontEndListing.checkbox = function(e, el){
        e = e || window.event;
        var targ = e.target || e.srcElement;
        if (targ.nodeType == 3) targ = targ.parentNode; // defeat Safari bug
        console.log(targ.tagName);
        if(targ.tagName==='INPUT'){
            return false;
        }
        console.log(targ);
        if(el.classList.contains('super-active')){
            el.classList.remove('super-active');
            el.parentNode.classList.remove('super-active');
        }else{
            el.classList.add('super-active');
            el.parentNode.classList.add('super-active');
        }
    };

    // $doc.on('click', '.super-create-translation', function(){
    //     // Validate
    //     var $row = $('.super-default-language'),
    //         $language = $row.find('.super-dropdown[data-name="language"] .super-active'),
    //         $flag = $row.find('.super-dropdown[data-name="flag"] .super-active');
    //     $row.find('.super-dropdown[data-name="language"], .super-dropdown[data-name="flag"]').removeClass('super-error');
    //     if(!$language.length || !$flag.length){
    //         if(!$language.length)
    //             $row.find('.super-dropdown[data-name="language"]').addClass('super-error');
    //         if(!$flag.length)
    //             $row.find('.super-dropdown[data-name="flag"]').addClass('super-error');
    //         return false;
    //     }
    //     // We will grab the so called "dummy" html, which is the first item in our list
    //     var $dummy = $('.translations-list > li').first(),
    //         $last = $('.translations-list > li').last(),
    //         $clone = $dummy.clone();
    //     // First reset the tooltips for our buttons
    //     $clone.find('.tooltipstered').removeClass('tooltipstered');
    //     $clone.find('.super-tooltip').each(function(){
    //         $(this).attr('title', $(this).attr('data-title'));
    //     });
    //     $clone.insertAfter($last);
    //     SUPER.init_tooltips();
    // });
})();