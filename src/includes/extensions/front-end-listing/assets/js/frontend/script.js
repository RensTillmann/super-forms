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

    // When edit button is clicked create a modal/popup window and load the form + it's entry data
    SUPER.frontEndListing.editEntry = function(el){
        var parent = getParents(el, '.super-entry')[0];
        var entry_id = parent.dataset.id;

        // Create popup window and load the form + it's entry data
        var modal = document.createElement('div');
        modal.classList.add('super-fel-modal');

        // Resize according to the window
        SUPER.frontEndListing.resizeModal(modal);
        
        // Add close button
        var closeBtn = document.createElement('div');
        closeBtn.classList.add('super-close');
        closeBtn.addEventListener('click', function(){
            this.parentNode.remove();
        });
        modal.appendChild(closeBtn);

        // Load iframe
        var iframe = document.createElement('iframe');
        // eslint-disable-next-line no-undef
        iframe.src = super_front_end_listing_i18n.get_home_url+'?super-fel-id='+entry_id;
        iframe.style.width = '100%';
        iframe.style.height = '100%';
        iframe.style.border = '0px';
        modal.appendChild(iframe);

        // Print modal
        document.body.appendChild(modal);
    };
 
    // Delete entry
    SUPER.frontEndListing.deleteEntry = function(el){
        var parent = getParents(el, '.super-entry')[0];
        var entry_id = parent.dataset.id;

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 ){
                if (this.status == 200) {
                    // Success:
                }
                // Complete:
            }
        };
        xhttp.onerror = function () {
          console.log(this);
          console.log("** An error occurred during the transaction");
        };
        // eslint-disable-next-line no-undef
        xhttp.open("POST", ajaxurl, true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
        var params = JSON.stringify({
            super_ajax : 'true',
            action: 'delete_entry',
            entry_id: entry_id,
            // eslint-disable-next-line no-undef
            wp_root: super_front_end_listing_i18n.wp_root
        });
        xhttp.send(params);
    };

    // Resize modal
    SUPER.frontEndListing.resizeModal = function(modal){
        console.log(modal);
        if( typeof modal === 'undefined' ) {
            modal = document.querySelector('.super-fel-modal');
            console.log(modal);
        }
        if( typeof modal === 'undefined' || modal == null ) {
            return false;
        }
        var height = window.innerHeight;
        var width = window.innerWidth;
        console.log('height: '+height, ' | width: '+width);
        if( width > 1200 ) {
            // Resize by 20%
            height = height - (height/100)*20;
            width = width - (width/100)*20;
        }else{
            if( width > 1000 ) {
                // Resize by 15%
                height = height - (height/100)*15;
                width = width - (width/100)*15;
            }else{
                if( width > 800 ) {
                    // Resize by 10%
                    height = height - (height/100)*10;
                    width = width - (width/100)*10;
                }else{
                    // Resize by 5%
                    height = height - (height/100)*5;
                    width = width - (width/100)*5;
                }
            }
        }
        modal.style.height = height+'px';
        modal.style.width = width+'px';
        modal.style.marginTop = '-'+parseFloat(height/2).toFixed(2)+'px';
        modal.style.marginLeft = '-'+parseFloat(width/2).toFixed(2)+'px';
    };

    // Reposition/resize the Modal according to the window
    window.onresize = SUPER.frontEndListing.resizeModal();
    window.addEventListener('resize', function(){
        SUPER.frontEndListing.resizeModal();
    });


})();
