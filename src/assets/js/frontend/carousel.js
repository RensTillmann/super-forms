// Author: Rens Tillmann
// URL: github.com/RensTillmann/CarouselJS
// Description: A lightweight carousel/slider script, designed for `Super Forms`

"use strict";
var CarouselJS = {

    // Settings & Options
    settings: {
        customClass: 'super-carousel',  // A custom class to be added on the container
        selector: '.carouseljs',        // Selector to intialize the carousel element
        method: 'multi',                // This determines how the slide should be executed
                                        // `single` : slide only one item forward/backward at a time
                                        // `multi` : slide all visible items forward/backward up to "nextItem"
        layout: 'grid',                 // Choose what layout to use
                                        // `grid` : use flex grid, allowing you to only display a specific amount of items per slide 
                                        // `auto` : puts each item simply behind eachother, not caring about how many are visible
        columns: 4,                     // The items per slide (only works when `grid` layout is enabled)
                                        // This will basically create slides of X items each
                                        // Each item will get a width based on the carousel container width
                                        // For instance: if the carousel is 900px in width, each element would be 300px in width when
                                        // this option is set to `columns: 3`
        minwidth: 100,                  // Define the minimum width an item must have before applying responsive settings.
                                        // For instance let's say the screen size of the device is 768 (iPad).
                                        // And let's assume that our carousel is inside a 100% width element meaning our carousel wrapper is 768 in width.
                                        // And let's assume we have defined `columns: 5` (5 items per slide).
                                        // 5x200=1000 (exceeds the width of the carousel wrapper which is 768).
                                        // This means that there is not enough space to create items with a width of 200.
                                        // In that case the script will determine a new width based on the 768 wrapper width.
                                        // It always first checks if 1000 is below the wrapper width, if it is below this, it will decrease the `columns: 5`.
                                        // It then checks if the new width of 800 is below the wrapper width, if not, it repeats the above.
                                        // The next check would be done with `columns: 3` resulting in a 600 width total against 768.
                                        // Of course this means that there is still some space left unused.
                                        // To solve this we would simply devide 768 by 3 to get the width for each item
                                        // In case there is only room for 1 item, it will apply 100% width on the item
        
        // Navigation
        navigation: true,                       // Display Prev/Next buttons (true|false)
        dots: true,                             // Display "Dots" naviagtion below the slider

        // Colors
        trackBg: '',        // Background color for the slider (track background)
        itemBg: '',         // Background color for each item

        // Items
        itemsMargin: '10px 10px 10px 10px',     // Define margin for each item
        itemsPadding: '',    // Define padding for each item

        // Animation
        animationSpeed: 0.3,                    // The scroll animation speed in seconds
        
        // Custom buttons HTML
        buttons: {
            previous: {
                html: ''    // HTML for inside the "prev/backward" button (leave blank for default buttons)
            },
            next: {
                html: ''    // HTML for inside the "next/forward" button (leave blank for default buttons)
            }
        },
        
    },

    // "action" holds the type of action to trigger the slide e.g `next` `prev`
    // ...you could think of it as the "direction" (forward/backward)
    trigger: function(button, action) {
        // If triggered via Dot navigation
        if(action=='dot'){
            // Look left of dot for "current" dot
            if(button.classList.contains('current')){
                // First check if the dot itself is the current, if so do nothing]
            }else{
                var prevDot = button,
                    nextDot = button,
                    nextButton = null,
                    i = 0,
                    direction = '',
                    clicked = 0;
                while (prevDot = prevDot.previousElementSibling) {
                    i++;
                    if(prevDot.classList.contains('carouseljs-current')){
                        prevDot.classList.remove('carouseljs-current');
                        direction = 'right';
                        break;
                    }
                }
                // If no direction is known at this point it means that we did not found the current on the left side
                if(direction==''){
                    i = 0;
                    while (nextDot = nextDot.nextElementSibling) {
                        i++;
                        if(nextDot.classList.contains('carouseljs-current')){
                            nextDot.classList.remove('carouseljs-current');
                            direction = 'left';
                            break;
                        }
                    }
                }
                if(direction=='right'){
                    // If we need to slide right
                    nextButton = button.parentNode.parentNode.querySelector('.next');
                }else{
                    // If we need to slide left
                    nextButton = button.parentNode.parentNode.querySelector('.prev');
                }
                // Click the next or prev button X times
                while (clicked < i){
                    nextButton.click();
                    clicked++;
                }
                // After sliding update "carouseljs-current" class
                button.classList.add('carouseljs-current');
            }
        }else{
            this._setters(button, action);
            this.doSlide();
        }
    },

    // Setters
    _setters: function(node, action) {
        this._self = node.parentNode;
        this._action = action;
        this._containerWidth = this.itemWidth(this._self);
        this._carouselTrack = this._self.querySelector('.carouseljs-track');
        this._currentItem = this._carouselTrack.querySelector('.carouseljs-current');
        this._currentItemWidth = this.itemWidth(this._currentItem);
        this._totalScrolled = (this._carouselTrack.style.marginLeft !== '' ? parseFloat(this._carouselTrack.style.marginLeft) : 0);
        this._dotNav = this._self.querySelector('.carouseljs-dots');
        if(this._dotNav) this._currentDot = this._dotNav.querySelector('.carouseljs-current');
    },
    _self: null,                 // Reference
    _containerWidth: null,       // The total width of the container
    _nextItem: null,             // The next item is the first item that is not completely visible
    _nextItemWidth: null,        // This is the width of the "nextItem" that was found
    _currentItem: null,          // Returns the first visible item in the slider
    _currentItemWidth: null,     // Returns the width of "currentItem"
    _carouselTrack: null,        // Holds the "track" of all items, this is the element that we will be animating
    _totalScrolled: null,        // Current amount the carousel was scrolled
    _dotNav: null,               // Element that holds dots navigation items
    _currentDot: null,           // Current dot navigation item

    // Slide carousel forward or backward 
    doSlide: function() {
        var _ = this.settings,
            nextNode = this._currentItem,
            width = this._currentItemWidth;

        if (this._action == 'next') {
            if (this.overlapRight() > 0) {
                width = this.overlapRight();
            }
        }
        
        // Single step method
        if (_.method == 'single' && this._action == 'next') {
            while (nextNode = nextNode.nextElementSibling) {
                width += this.itemWidth(nextNode);
                if (width > this._containerWidth) {
                    this.slideCarousel(width-this._containerWidth); // Slide carousel
                    // Update current item only if current item is no longer visible
                    if (this.overlapRight(width-this._containerWidth) <= 0) {
                        this.updateCurrentItem(this._currentItem.nextElementSibling); // Update current item
                    }
                    break;
                }
            }
        }
        if (_.method == 'single' && this._action == 'prev') {
            if (this.overlapLeft() > 0) {
                this.slideCarousel(this.overlapLeft()); // Slide carousel
            } else {
                // Simply grab previous sibling width and scroll
                if (this._currentItem.previousElementSibling) {
                    this.updateCurrentItem(this._currentItem.previousElementSibling); // Update current item
                    this.slideCarousel(this.itemWidth(this._currentItem.previousElementSibling)); // Slide carousel
                }
            }
        }

        // Multi method
        if (_.method == 'multi' && this._action == 'next') {
            while (nextNode = nextNode.nextElementSibling) {
                width += this.itemWidth(nextNode);
                if (width > this._containerWidth) {
                    
                    // Before scrolling, check if next item + next siblings width does not exceed container width
                    // If this is the case we can simply scroll to the last item of the carousel
                    var nextSibling = nextNode;
                    var siblingsWidth = (this.itemWidth(nextNode) - (this.itemWidth(nextNode) - (width - this._containerWidth)));
                    while (nextSibling = nextSibling.nextElementSibling) {
                        siblingsWidth += this.itemWidth(nextSibling);
                    }
                    if (siblingsWidth < this._containerWidth) {
                        this.slideCarousel(siblingsWidth); // Slide carousel
                        this.updateCurrentItem(nextNode.previousElementSibling); // Update current item
                    } else {
                        this.slideCarousel(width-this.itemWidth(nextNode)); // Slide carousel
                        this.updateCurrentItem(nextNode); // Update current item
                    }
                    break;
                }
            }
        }
        if (_.method == 'multi' && this._action == 'prev') {
            // We are at the beginning of the carousel, no need to do anything
            if (this._totalScrolled >= 0) {
                // Silence is golden
            } else {
                var overlapLeft = this.overlapLeft();
                if (overlapLeft > 0) {
                    // In this case we will scroll the item to the far right of the container so that the item becomes fully visible
                    // and so that the other items next (previous items really) will also become visible as much as possible
                    while (nextNode = nextNode.nextElementSibling) {
                        width += this.itemWidth(nextNode);
                        if (width > this._containerWidth) {
                            // Before scrolling, check if next item + next siblings width does not exceed container width
                            // If this is the case we can simply scroll to the last item of the carousel
                            var nextSibling = this._currentItem;
                            var siblingsWidth = overlapLeft;
                            var firstNode = null;
                            while (nextSibling = nextSibling.previousElementSibling) {
                                siblingsWidth += this.itemWidth(nextSibling);
                                firstNode = nextSibling;
                            }
                            if (siblingsWidth < this._containerWidth) {
                                this.slideCarousel(siblingsWidth); // Slide carousel
                                this.updateCurrentItem(firstNode); // Update current item
                            } else {
                                this.slideCarousel(width-this._currentItemWidth); // Slide carousel
                                nextNode = this._currentItem;
                                width = this._currentItemWidth;
                                while (nextNode = nextNode.previousElementSibling) {
                                    width += this.itemWidth(nextNode);
                                    if (width > this._containerWidth) {
                                        this.updateCurrentItem(nextNode); // Update current item
                                        break;
                                    }
                                }
                            }
                            break;
                        }
                    }
                } else {
                    width = 0;
                    var firstNode = null;
                    while (nextNode = nextNode.previousElementSibling) {
                        firstNode = nextNode;
                        width += this.itemWidth(nextNode);
                        if (width >= this._containerWidth) {
                            this.updateCurrentItem(nextNode); // Update current item
                            this.slideCarousel(this._containerWidth); // Slide carousel
                            break;
                        }
                    }
                    if (width <= this._containerWidth) {
                        this.updateCurrentItem(firstNode); // Update current item
                        this.slideCarousel(width); // Slide carousel
                    }
                }
            }
        }
    },
    updateCurrentItem: function(next) {
        if(next){
            this._currentItem.classList.remove('carouseljs-current');
            next.classList.add('carouseljs-current');
        }
    },
    updateDots: function(){
        // Update dots
        if(this._currentDot) {
            this._currentDot.classList.remove('carouseljs-current');
            if(this._action=='next'){
                if(this._currentDot.nextElementSibling){
                    this._currentDot.nextElementSibling.classList.add('carouseljs-current');
                }
            }else{
                if(this._currentDot.previousElementSibling){
                    this._currentDot.previousElementSibling.classList.add('carouseljs-current');
                }
            }
        }
    },
    slideCarousel: function(amount, node=null) {
        if(!node){
            if(this._action=='next'){
                amount = this._totalScrolled - amount; // If sliding forward (next)
            }else{
                amount = this._totalScrolled + amount; // If sliding backward (previous)
            }
            this.updateDots();
            // Amount to slide can not be above 0, let's make sure of that
            if(amount>0) amount = 0;
            // Slide carousel track
            this._carouselTrack.style.marginLeft = amount + 'px';
        }else{
            // Aso reset current to the first item
            // We could also adjust the marginLeft property upon resizing the window
            // but this is just the easy way around, and it's not that important
            node.carousel.querySelector('.carouseljs-current').classList.remove('carouseljs-current');
            node.carousel.firstElementChild.classList.add('carouseljs-current');
            node.carousel.style.marginLeft = amount + 'px'; 

            // If reset to start, update dot navigation
            if(node.dots){
                if(node.dots.querySelector('.carouseljs-current')) node.dots.querySelector('.carouseljs-current').classList.remove('carouseljs-current');
                node.dots.firstElementChild.classList.add('carouseljs-current');
            }
        }
    },
    overlapLeft: function() {
        return this._currentItemWidth - this.overlapRight();
    },
    overlapRight: function(visible) {
        var node = this._currentItem,
            width = this._currentItemWidth;
        while (node = node.previousElementSibling) {
            width += this.itemWidth(node);
        }
        if(this.settings.method=='single'){
            if(typeof visible === 'undefined') visible = 0;
            return (this._totalScrolled-visible) + width;
        }else{
            return this._totalScrolled + width;
        }
        return overlapRight;
    },
    itemWidth: function(node){
        var style = window.getComputedStyle ? getComputedStyle(node, null) : node.currentStyle,
            marginLeft = parseFloat(style.marginLeft) || 0,
            marginRight = parseFloat(style.marginRight) || 0;
        if(node.classList.contains('carouseljs-wrapper')){
            var paddingLeft = parseFloat(style.paddingLeft) || 0,
                paddingRight = parseFloat(style.paddingRight) || 0,
                padding = paddingLeft+paddingRight;
            return node.offsetWidth+(marginLeft+marginRight)-(paddingLeft+paddingRight);
        } 
        return node.offsetWidth+(marginLeft+marginRight);
    },
    setMarginPadding: function(node){
        var _ = this.settings;
        if(_.itemsMargin!='') node.style.margin = _.itemsMargin;
        if(_.itemsPadding!='') node.style.padding = _.itemsPadding;
        // First get the window width
        // Based on this we will adjust the margin/paddings based on screen size
        var windowWidth = window.innerWidth;
        if(windowWidth<1000){
            node.style.marginLeft = ((parseFloat(node.style.marginLeft)/100)*(windowWidth/20));
            node.style.marginRight = ((parseFloat(node.style.marginRight)/100)*(windowWidth/20));
            node.style.paddingLeft = ((parseFloat(node.style.paddingLeft)/100)*(windowWidth/20));
            node.style.paddingRight = ((parseFloat(node.style.paddingRight)/100)*(windowWidth/20));
        }

    },

    // Redraw (resize carousel). Will make sure the carousel is responsiveness based on it's parent width
    // Will fire upon initializing, and upon window.resize event
    redraw: function(fn, _, node){

        // Merge with core settings
        _ = Object.assign(_, node.settings);
        this.slideCarousel(0, node);
        // Setup item width if `grid` layout is being used
        if(_.layout=='grid'){
            var itemWidth = Number(node.container.clientWidth / _.columns),
                columns = _.columns,
                nodes = node.carousel.children,
                len = nodes.length,
                style = null,
                i = 0,
                marginLeft, marginRight, paddingLeft, paddingRight;

            // @IMPORTANT:
            // To speed up the loop, make sure we put the margins and paddings into our cache
            // instead of calling `getComputedStyle` inside the loop on each item
            
            // First set the margin and paddings based on the settings for the first item
            fn.setMarginPadding(nodes[i]);
            // After we have set the item padding and margin, we can set it's width
            // We must substract the items margin in order to get a correct width
            style = window.getComputedStyle ? getComputedStyle(nodes[i], null) : nodes[i].currentStyle;
            marginLeft = parseFloat(style.marginLeft) || 0;
            marginRight = parseFloat(style.marginRight) || 0;
            paddingLeft = parseFloat(style.paddingLeft) || 0;
            paddingRight = parseFloat(style.paddingRight) || 0;
            
            // Set correct width
            var newItemWidth = Number(itemWidth-(marginLeft+marginRight)-(paddingLeft+paddingRight));
            // Check if item width is lower than `minwidth` setting
            while (newItemWidth < _.minwidth){
                columns--;
                // Columns may never be 0 or lower
                if(columns<=0) columns = 1;
                itemWidth = Number(node.container.clientWidth / columns),
                newItemWidth = Number(itemWidth-(marginLeft+marginRight)-(paddingLeft+paddingRight));
                if(columns==1) break;
            }

            nodes[i].style.width = newItemWidth+'px';
            // Now that we have our margin and padding loop over all other items
            for (var i = 1; i < len; i++) {
                // Set margin and paddings for the item
                fn.setMarginPadding(nodes[i]);
                // Set correct width
                nodes[i].style.width = newItemWidth+'px';
            }
            // Also update dots navigation
            if(node.dots){
                // Determine how many dots we need to display
                var newTotal = Math.ceil(node.carousel.children.length/columns); 
                var currentTotal = node.dots.children.length;
                // Find out the difference between the current amount of dots, and the amount required
                if(currentTotal < newTotal){
                    // Not enough dots, we must add some
                    var toBeAdded = newTotal-currentTotal;
                    var i = 0;
                    var html = '';
                    while(i < toBeAdded){
                         var dot = document.createElement('span');
                         dot.setAttribute("onclick", "CarouselJS.trigger(this, 'dot')");
                         node.dots.appendChild(dot);
                         i++;
                    }
                }
                if(currentTotal > newTotal){
                    // To many dots, we must remove some
                    var toBeDeleted = currentTotal-newTotal
                    var i = 1;
                    while(toBeDeleted+currentTotal > currentTotal){
                        node.dots.children[currentTotal-i].remove();
                        toBeDeleted--;
                        i++;
                    }
                }
                // Determine if we need to hide the dots or not
                // If so, make sure that we can see the prev/next buttons
                var dots = node.dots.children;
                var len = dots.length;
                var width = 0;
                for (var i = 1; i < len; i++) {
                    width += this.itemWidth(dots[i]);
                }
                if(this.itemWidth(node.container) < width){
                    node.dots.style.visibility = 'hidden';
                    node.wrapper.querySelector('.carouseljs-button.prev').style.display = 'block';
                    node.wrapper.querySelector('.carouseljs-button.next').style.display = 'block';
                }else{
                    node.dots.style.visibility = '';
                    if(_.navigation==false){
                        node.wrapper.querySelector('.carouseljs-button.prev').style.display = 'none';
                        node.wrapper.querySelector('.carouseljs-button.next').style.display = 'none';
                    }
                }
            }
        }
    },

    // Initialize CarouselJS
    init: function() { // Returns the first visible item in the slider
        var fn = this; 
        var _ = fn.settings;
        // Search for DOM elements based on the selector
        if (typeof _.selector !== 'undefined' && _.selector !== '') {
            // Find and loop over all CarouselJS sliders
            var obj = document.querySelectorAll(_.selector);
            var containers = [];
            Object.keys(obj).forEach(function(key) {
                var carousel = obj[key];
                var firstElement = carousel.firstElementChild;
                // Before we do anything, check if we need to grab custom settings from the `<textarea>` element (if one exists)
                if(firstElement.tagName=='TEXTAREA'){
                    var customSettings = carousel.firstElementChild.value;
                    try {
                        customSettings = JSON.parse(customSettings);
                    } catch(e) {
                        alert(e);
                    }
                    // Merge with core settings
                    _ = Object.assign(_, customSettings);
                    // After successful merge, delete the element
                    firstElement.remove();
                }
                // Check if both navigation and dots navigations are disabled
                // If so, then we must enable at least one of the 2, in all cases we will enable the Prev/Next buttons by default
                if(_.navigation===false && _.dots===false) _.navigation = true;
                
                carousel.classList.remove('carouseljs');
                carousel.classList.add('carouseljs-track');
                carousel.firstElementChild.classList.add('carouseljs-current');
                if(_.trackBg!=='') carousel.style.backgroundColor = _.trackBg;
                // Set transitions
                carousel.style.WebkitTransition = "all " + Number(_.animationSpeed) + "s"; // Code for Safari 3.1 to 6.0
                carousel.style.transition = "all " + Number(_.animationSpeed) + "s"; // Standard syntax  
                // Set item class
                var items = carousel.children;
                for (var i = 0; i < items.length; i++) {
                    if(_.itemBg!=='') items[i].style.backgroundColor = _.itemBg;
                    items[i].classList.add('carouseljs-item');
                }
                // Create wrapper
                var wrapper = document.createElement('div');
                wrapper.classList.add('carouseljs-wrapper');
                wrapper.classList.add(_.customClass + '-wrapper');
                // Create container
                var container = document.createElement('div');
                container.classList.add('carouseljs-container');
                // Add "Previous" button
                var prevButton = document.createElement('div');
                prevButton.innerHTML = _.buttons.previous.html ? _.buttons.previous.html : '<i class="top-line"></i><i class="bottom-line"></i>';
                prevButton.setAttribute("onclick", "CarouselJS.trigger(this, 'prev')");
                prevButton.className = 'carouseljs-button prev';
                if (_.navigation === false) {
                    prevButton.style.display = 'none'; 
                }
                wrapper.appendChild(prevButton);
                // Add "Next" button
                var nextButton = document.createElement('div');
                nextButton.innerHTML = _.buttons.next.html ? _.buttons.next.html : '<i class="top-line"></i><i class="bottom-line"></i>';
                nextButton.setAttribute("onclick", "CarouselJS.trigger(this, 'next')");
                nextButton.className = 'carouseljs-button next';
                if (_.navigation === false) {
                    nextButton.style.display = 'none'; 
                }
                wrapper.appendChild(nextButton);
                // Add dots navigation if enabled
                if (_.dots === true) {
                     // Determine how many dots we need to display
                     var total = Math.ceil(carousel.children.length/_.columns), 
                         html = '<span class="carouseljs-current" onclick="CarouselJS.trigger(this, \'dot\')"></span>', // First slide is always the current one upon intialization
                         i=1;
                     while(i < total){
                         html += '<span onclick="CarouselJS.trigger(this, \'dot\')"></span>';
                         i++;
                     }
                     // Now create the dots navigation and append it to the wrapper
                     var dots = document.createElement('div');
                     dots.classList.add('carouseljs-dots');
                     dots.innerHTML = html;
                     wrapper.appendChild(dots); 

                }
                // Insert wrapper before carousel slider in the DOM tree
                carousel.parentNode.insertBefore(wrapper, carousel);
                // Move carousel slider into container
                container.appendChild(carousel);
                // Move container into wrapper
                wrapper.appendChild(container);
                // Add container to object
                containers.push({
                    wrapper: wrapper,
                    dots: dots,
                    container: container,
                    carousel: carousel,
                    settings: customSettings
                });
            });
            
            // Loop over all containers, and resize elements accordingly
            Object.keys(containers).forEach(function(key) {
                fn.redraw(fn, _, containers[key]);
                // Also redraw upon resizing window
                window.addEventListener("resize", function(){
                    fn.redraw(fn, _, containers[key]);
                });
            });

        } else {
            // Display error to the user about a missing option/setting
            alert('You forgot to define a selector in the CarouselJS options section!');
        }
    }
};
// Initialize CarouselJS
CarouselJS.init();
