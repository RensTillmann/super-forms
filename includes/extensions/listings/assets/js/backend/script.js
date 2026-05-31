/* globals SUPER */
"use strict";
(function() { // Hide scope, no $ conflict

    /**
     * Get all DOM element up the tree that contain a class, ID, or data attribute
     * @param  {Node} elem The base element
     * @param  {String} selector The class, id, data attribute, or tag to look for
     * @return {Array} Null if no match
     */
    var getParents = function(elem, selector){
        var parents = [];
        var firstChar;
        if(selector){
            firstChar = selector.charAt(0);
        }
        // Get matches
        for(;elem && elem!==document; elem=elem.parentNode){
            if(selector){
                // If selector is a class
                if(firstChar==='.'){
                    if(elem.classList.contains(selector.substr(1))){
                        parents.push(elem);
                    }
                }
                // If selector is an ID
                if(firstChar==='#'){
                    if(elem.id===selector.substr(1)){
                        parents.push(elem);
                    }
                }
                // If selector is a data attribute
                if(firstChar==='['){
                    if(elem.hasAttribute(selector.substr(1, selector.length - 1))){
                        parents.push(elem);
                    }
                }
                // If selector is a tag
                if(elem.tagName.toLowerCase()===selector){
                    parents.push( elem );
                }
            }else{
                parents.push( elem );
            }
        }
        // Return parents if any exist
        if(parents.length===0){
            return null;
        }else{ 
            return parents;
        }
    };

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

    SUPER.add_listing = function(data){
        data.formSettings = JSON.parse(data.formSettings);
        data.formSettings._listings = {};
        // Loop through all the listings
        var list = document.querySelectorAll('.super-listings-list > li');
        for (var key = 0; key < list.length; key++) {
            data.formSettings._listings[key] = {};
            data.formSettings._listings[key].name = list[key].querySelector('input[name="name"]').value;
            data.formSettings._listings[key].retrieve = list[key].querySelector('[data-name="retrieve"]').querySelector('.super-active').dataset.value;
            if(data.formSettings._listings[key].retrieve=='specific_forms'){
                data.formSettings._listings[key].form_ids = list[key].querySelector('input[name="form_ids"]').value;
            }
            data.formSettings._listings[key].date_range = false;
            if(list[key].querySelector('[data-name="date_range"]').classList.contains('super-active')){
                data.formSettings._listings[key].date_range = {
                    from: list[key].querySelector('[data-name="date_range"] input[name="from"]').value,
                    until: list[key].querySelector('[data-name="date_range"] input[name="until"]').value
                };
            }
            data.formSettings._listings[key].show_title = false;
            if(list[key].querySelector('[data-name="show_title"]').classList.contains('super-active')){
                data.formSettings._listings[key].show_title = {
                    name: list[key].querySelector('[data-name="show_title"] input[name="name"]').value,
                    placeholder: list[key].querySelector('[data-name="show_title"] input[name="placeholder"]').value,
                    position: list[key].querySelector('[data-name="show_title"] input[name="position"]').value,
                    width: list[key].querySelector('[data-name="show_title"] input[name="width"]').value
                };
            }
            data.formSettings._listings[key].show_status = false;
            if(list[key].querySelector('[data-name="show_status"]').classList.contains('super-active')){
                data.formSettings._listings[key].show_status = {
                    name: list[key].querySelector('[data-name="show_status"] input[name="name"]').value,
                    placeholder: list[key].querySelector('[data-name="show_status"] input[name="placeholder"]').value,
                    position: list[key].querySelector('[data-name="show_status"] input[name="position"]').value,
                    width: list[key].querySelector('[data-name="show_status"] input[name="width"]').value
                };
            }
            data.formSettings._listings[key].show_date = false;
            if(list[key].querySelector('[data-name="show_date"]').classList.contains('super-active')){
                data.formSettings._listings[key].show_date = {
                    name: list[key].querySelector('[data-name="show_date"] input[name="name"]').value,
                    placeholder: list[key].querySelector('[data-name="show_date"] input[name="placeholder"]').value,
                    position: list[key].querySelector('[data-name="show_date"] input[name="position"]').value,
                    width: list[key].querySelector('[data-name="show_date"] input[name="width"]').value
                };
            }
            // Add custom columns
            data.formSettings._listings[key].custom_columns = false;
            if(list[key].querySelector('[data-name="custom_columns"]').classList.contains('super-active')){
                data.formSettings._listings[key].custom_columns = true;
                data.formSettings._listings[key].columns = {};
                var columns = document.querySelectorAll('.super-listings-list div[data-name="custom_columns"] li');
                for (var ckey = 0; ckey < columns.length; ckey++) {
                    data.formSettings._listings[key].columns[ckey] = {};
                    data.formSettings._listings[key].columns[ckey].name = columns[ckey].querySelector('input[name="name"]').value;
                    data.formSettings._listings[key].columns[ckey].field_name = columns[ckey].querySelector('input[name="field_name"]').value;
                    data.formSettings._listings[key].columns[ckey].width = columns[ckey].querySelector('input[name="width"]').value;
                    data.formSettings._listings[key].columns[ckey].filter = columns[ckey].querySelector('select[name="filter"]').value;
                    data.formSettings._listings[key].columns[ckey].filter_items = columns[ckey].querySelector('textarea[name="filter_items"]').value;
                }
            }
            data.formSettings._listings[key].edit_any = false;
            if(list[key].querySelector('[data-name="edit_any"]').classList.contains('super-active')){
                data.formSettings._listings[key].edit_any = {
                    user_roles: list[key].querySelector('input[name="user_roles"]').value,
                    user_ids: list[key].querySelector('input[name="user_ids"]').value,
                    method: (list[key].querySelector('[data-name="method"]').querySelector('.super-active') ? list[key].querySelector('[data-name="method"]').querySelector('.super-active').dataset.value : '')
                };
            }
            data.formSettings._listings[key].pagination = list[key].querySelector('[data-name="pagination"]').querySelector('.super-active').dataset.value;
            data.formSettings._listings[key].limit = list[key].querySelector('select[name="limit"]').value;
        }
        data.formSettings = JSON.stringify(data.formSettings);
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
        var target_element = document.querySelector('.super-listings-list');
        var source = target_element.firstElementChild;
        var list = document.querySelector('.super-listings-list');
        var node = source.cloneNode(true);
        // Change shortcode
        var form_id = document.querySelector('.super-header input[name="form_id"]').value;
        node.querySelector('.super-get-form-shortcodes').value = '[super_listings list="'+(list.children.length+1)+'" id="'+form_id+'"]';
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
        if(targ.tagName==='INPUT'){
            return false;
        }
        if(el.classList.contains('super-active')){
            el.classList.remove('super-active');
            el.parentNode.classList.remove('super-active');
        }else{
            el.parentNode.classList.add('super-active');
        }
    };


    // Display filter items for custom column when dropdown method is choosen
    SUPER.frontEndListing.showFilterItems = function(el){
        var item = getParents(el, 'li')[0];
        if(el.value=='dropdown'){
            item.querySelector('.super-filter-items').style.display = 'block';
        }else{
            item.querySelector('.super-filter-items').style.display = 'none';
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
    //     var $dummy = $('.super-translations-list > li').first(),
    //         $last = $('.super-translations-list > li').last(),
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
