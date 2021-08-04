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
    
    // Get current query string
    SUPER.frontEndListing.getQueryString = function(el){
        debugger;
        var i, nodes, col, inputField, value, columnName, limit = 25, queryString = [], from, till;
        // Get the current limit from the pagination (if any otherwise default to 25)
        if(typeof document.querySelector('.super-pagination .super-limit') !== 'undefined' ){
            limit = document.querySelector('.super-pagination .super-limit').value;
        }
        queryString.push('limit='+limit);

        // If we are searching, then we skip the column search inputs, and just use the current one
        debugger;
        // Loop through all filters and grab the value and the column name
        // Then append each of them
        nodes = getParents(el, '.super-listings')[0].querySelectorAll('.super-columns .super-col-wrap');
        for (i = 0; i < nodes.length; i++) {
            // Check if this column can be filtered if so add it to the array
            // But only if value isn't empty
            value = nodes[i].querySelector('.super-col-filter input') ? nodes[i].querySelector('.super-col-filter input').value : (nodes[i].querySelector('.super-col-filter select') ? nodes[i].querySelector('.super-col-filter select').value : '');
            if( value!=='' ) {
                // Replace hashtag to avoid browser thinking to use it as anchor
                //if(value.charAt(0)==='#') value = '%23'+value.substring(1, value.length);
                value = encodeURIComponent(value);
                columnName = nodes[i].dataset.name;
                if(columnName==='date'){
                    from = getParents(el, '.super-listings')[0].querySelector('input[name="date_from"]');
                    till = getParents(el, '.super-listings')[0].querySelector('input[name="date_till"]');
                    queryString.push('fc_'+columnName+'='+from.value+';'+till.value);
                }else{
                    queryString.push('fc_'+columnName+'='+value);
                }
            }
        }

        // Get current active sort (if any)
        nodes = getParents(el, '.super-listings')[0].querySelectorAll('.super-columns .super-sort-down, .super-columns .super-sort-up');
        for (i = 0; i < nodes.length; i++) {
            if(nodes[i].classList.contains('super-active')){
                debugger;
                queryString.push('sc='+nodes[i].closest('.super-col-wrap').dataset.name);
                if(nodes[i].classList.contains('super-sort-down')){
                    queryString.push('sm=d');
                }else{
                    queryString.push('sm=a');
                }
                break;
            }
        }

        // Load the page with query strings and make sure to start at page 1
        // Except when a page change was issued
        debugger;
        var page = 1;
        if( el.classList.contains('super-switcher') ){
            page = parseInt(el.value, 10);
        }else{
            if( el.classList.contains('super-next') ){
                page = parseInt(el.closest('.super-pagination').querySelector('.super-switcher').value, 10) + 1;
            }
            if( el.classList.contains('super-prev') ){
                page = parseInt(el.closest('.super-pagination').querySelector('.super-switcher').value, 10) - 1;
            }
            if( el.classList.contains('super-page') ) {
                page = parseInt(el.innerText, 10); // Update to correct page
            }
            if( el.classList.contains('super-switcher') ) {
                page = parseInt(el.value, 10); // Update to correct page
            }
        }
        debugger;
        if(page<1) page = 1;
        queryString.push('sfp='+page);
        return queryString;
    };

    // When page is being changed
    SUPER.frontEndListing.changePage = function(event, el){
        event.preventDefault();
        var queryString = SUPER.frontEndListing.getQueryString(el);
        window.location.href = window.location.href.split('?')[0]+'?'+queryString.join("&");
    };
    // When limit dropdown is changed
    SUPER.frontEndListing.limit = function(event, el){
        event.preventDefault();
        var queryString = SUPER.frontEndListing.getQueryString(el);
        window.location.href = window.location.href.split('?')[0]+'?'+queryString.join("&");
    };
    // When search button is clicked
    SUPER.frontEndListing.search = function(event, el){
        event.preventDefault();
        var queryString = SUPER.frontEndListing.getQueryString(el);
        window.location.href = window.location.href.split('?')[0]+'?'+queryString.join("&");
    };
    // When sort button is clicked
    SUPER.frontEndListing.sort = function(event, el){
        event.preventDefault();
        var queryString = SUPER.frontEndListing.getQueryString(el);
        var i, nodes = getParents(el, '.super-listings')[0].querySelectorAll('.super-columns .super-sort-down, .super-columns .super-sort-up');
        for (i = 0; i < nodes.length; i++) {
            nodes[i].classList.remove('super-active');
        }
        el.classList.add('super-active');
        queryString.push('sc='+el.closest('.super-col-wrap').dataset.name); // sort column
        if(el.classList.contains('super-sort-down')){
            queryString.push('sm=d'); // sort method
        }else{
            queryString.push('sm=a'); // sort method
        }
        window.location.href = window.location.href.split('?')[0]+'?'+queryString.join("&");
    };

    // When switch to page is changed
    SUPER.frontEndListing.pageSwitcher = function(el){
        console.log(el.value);
    };

    // When view button is clicked open a modal/popup window and display entry data based on HTML {loop_fields} or custom HTML
    SUPER.frontEndListing.viewEntry = function(el){
        var parent = getParents(el, '.super-entry')[0];
        var entry_id = parent.dataset.id;
        var form_id = getParents(el, '.super-listings')[0].dataset.formId;
        var list_id = getParents(el, '.super-listings')[0].dataset.listId;
        // Create popup window and load the form + it's entry data
        var modal = document.createElement('div');
        modal.classList.add('super-listings-modal');
        // Resize according to the window
        SUPER.frontEndListing.resizeModal(modal);
        // Add loading icon
        var loadingIcon = document.createElement('div');
        loadingIcon.classList.add('super-loading');
        modal.appendChild(loadingIcon);
        // Add close button
        var closeBtn = document.createElement('div');
        closeBtn.classList.add('super-close');
        closeBtn.addEventListener('click', function(){
            this.parentNode.remove();
        });
        modal.appendChild(closeBtn);

        //parent.classList.add('super-loading');
        //var entry_id = parent.dataset.id;
        //var form_id = getParents(el, '.super-listings')[0].dataset.formId;
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            if (this.readyState == 4) {
                // Success:
                if (this.status == 200) {
                    var node = document.createElement('div');
                    node.classList.add('super-listing-entry-wrapper');
                    node.innerHTML = this.responseText;
                    modal.appendChild(node);
                    loadingIcon.remove();
                }
                // Complete:
                parent.classList.remove('super-loading');
            }
        };
        xhttp.onerror = function () {
            console.log(this);
            console.log("** An error occurred during the transaction");
        };
        xhttp.open("POST", super_listings_i18n.ajaxurl, true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
        var params = {
            action: 'super_listings_view_entry',
            entry_id: entry_id,
            form_id: form_id,
            list_id: list_id
        };
        params = jQuery.param(params);
        xhttp.send(params);
        
        //var iframe = document.createElement('iframe');
        //// eslint-disable-next-line no-undef
        //iframe.src = super_listings_i18n.get_home_url+'?super-listings-view='+entry_id+'&form-id='+form_id+'&list-id='+list_id;
        //iframe.style.width = '100%';
        //iframe.style.height = '100%';
        //iframe.style.border = '0px';
        //modal.appendChild(iframe);
        // Print modal
        document.body.appendChild(modal);
    };
    // When edit button is clicked create a modal/popup window and load the form + it's entry data
    SUPER.frontEndListing.editEntry = function(el){
        var parent = getParents(el, '.super-entry')[0];
        var entry_id = parent.dataset.id;
        // Create popup window and load the form + it's entry data
        var modal = document.createElement('div');
        modal.classList.add('super-listings-modal');
        // Resize according to the window
        SUPER.frontEndListing.resizeModal(modal);
        // Add loading icon
        var loadingIcon = document.createElement('div');
        loadingIcon.classList.add('super-loading');
        modal.appendChild(loadingIcon);
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
        iframe.src = super_listings_i18n.get_home_url+'?super-listings-edit='+entry_id;
        iframe.style.width = '100%';
        iframe.style.height = '100%';
        iframe.style.border = '0px';
        modal.appendChild(iframe);

        // Print modal
        document.body.appendChild(modal);
    };
 
    // Delete entry
    SUPER.frontEndListing.deleteEntry = function(el, list_id){
        debugger;
        var parent = getParents(el, '.super-entry')[0];
        parent.classList.add('super-loading');
        var entry_id = parent.dataset.id;
        var form_id = getParents(el, '.super-listings')[0].dataset.formId;
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            if (this.readyState == 4) {
                // Success:
                if (this.status == 200) {
                    debugger;
                    if(this.responseText==='1'){
                        // Delete entry
                        parent.remove();
                    }else{
                        alert(this.responseText);
                    }
                }
                // Complete:
                parent.classList.remove('super-loading');
            }
        };
        xhttp.onerror = function () {
            console.log(this);
            console.log("** An error occurred during the transaction");
        };
        xhttp.open("POST", super_listings_i18n.ajaxurl, true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
        var params = {
            action: 'super_listings_delete_entry',
            entry_id: entry_id,
            form_id: form_id,
            list_id: list_id
        };
        params = jQuery.param(params);
        xhttp.send(params);
    };

    // Resize modal
    SUPER.frontEndListing.resizeModal = function(modal){
        console.log(modal);
        if( typeof modal === 'undefined' ) {
            modal = document.querySelector('.super-listings-modal');
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
