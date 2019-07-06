/* globals SUPER */
"use strict";
(function($) { // Hide scope, no $ conflict

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

    SUPER.frontEndListing = {};

    // When search button is clicked filter entries
    SUPER.frontEndListing.search = function(event, el){
        event.preventDefault();
        var query_string = [];
        // Regenerate query string
        // Loop through all filters and grab the value and the column name
        // Then append each of them
        var columns = getParents(el, '.super-fel')[0].querySelectorAll('.super-columns .super-col-wrap');
        for (var key = 0; key < columns.length; key++) {
            // Check if this column can be filtered if so add it to the array
            // But only if value isn't empty
            var value = ( columns[key].querySelector('.super-col-filter input') ? columns[key].querySelector('.super-col-filter input').value : columns[key].querySelector('.super-col-filter select').value );
            if( value!=='' ) {
                var column_name = columns[key].dataset.name;
                query_string.push(column_name+'='+value);
            }
        }
        // Get the current limit from the pagination (if any otherwise default to 25)
        var $limit = 25;
        if(typeof document.querySelector('.super-pagination .super-limit') !== 'undefined' ){
            $limit = document.querySelector('.super-pagination .super-limit').value;
        }
        // Load the page with query strings and make sure to start at page 1
        // Except when a page change was issued
        var page = 1;
        if( el.classList.contains('super-page') ) {
            page = el.innerText; // Update to correct page
        }
        if( el.classList.contains('super-switcher') ) {
            page = el.value; // Update to correct page
        }
        window.location.href = window.location.href.split('?')[0]+'?page='+page+'&limit='+$limit+'&'+query_string.join("&");
    };

    // When search button is clicked filter entries
    SUPER.frontEndListing.sort = function(el, method){
        console.log(method);
        console.log(el);
        var parent = getParents(el, '.super-col-wrap')
        var column_name = parent[0].dataset.name;
        console.log(column_name);
    };

    // When switch to page is changed
    SUPER.frontEndListing.pageSwitcher = function(el){
        console.log(el.value);
    };
    
 
})();