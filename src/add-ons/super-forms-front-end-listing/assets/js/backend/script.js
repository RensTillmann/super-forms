/* globals SUPER */
"use strict";
(function($) { // Hide scope, no $ conflict

    SUPER.frontEndListing = {};

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
        var node = source.cloneNode(true);
        removeClass(node.querySelectorAll('.tooltipstered'), 'tooltipstered');
        var elements = node.querySelectorAll('.super-tooltip');
        for (var key = 0; key < elements.length; key++) {
            elements[key].title = elements[key].dataset.title;
        }
        target_element.insertBefore(node, source.nextSibling);
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