/* globals jQuery, SUPER, super_popup_i18n */
(function($) { // Hide scope, no $ conflict
	"use strict";
	// Prevent scrolling on validation
	SUPER.init_before_scrolling_to_error_popup = function(proceed, form, $scroll){
		var popupContent = form.closest('.super-popup-content');
		if(popupContent){
            $(popupContent).animate({
                scrollTop: $scroll
            }, 1000);
            proceed = false;
        }
		return proceed;
	};

	// Scroll to top of popup content after appending message
	SUPER.init_before_scrolling_to_message_popup = function(proceed, form){
        if($(form).parents('.super-popup-content:eq(0)').length){
            $(form).parents('.super-popup-content:eq(0)').animate({
				scrollTop: 0
            }, 1000);
            proceed = false;
        }
		return proceed;
	};

	// Show popup in builder preview mode
	SUPER.init_show_preview_popup = function(){
		var $this = $('.super-live-preview').find('.super-popup');
		var $settings = $this.find('textarea[name="super-popup-settings"]').val();
		if(typeof $settings === 'string'){
			$settings = JSON.parse($settings);		
			SUPER.init_popups.show( $this, $settings );
		}
	};

	// Check if button contains class .super-popup-close
	SUPER.init_check_submit_button_close_popup = function(e, $proceed, $submit_button){
		if( $submit_button.parentNode.classList.contains('super-popup-close')) {
			$proceed = false;
			SUPER.init_popups.close(true);
			e.preventDefault();
		}
		return $proceed;
	};

	// Set expiration when form has been submitted by the user
	SUPER.init_set_expiration_cookie_on_submit_popup = function(args){
		var $wrapper = $(args.form).parents('.super-popup-wrapper');
		var $settings = $wrapper.find('textarea[name="super-popup-settings"]').val();
		if(typeof $settings === 'string'){
			$settings = JSON.parse($settings);
			if($settings!=null){
				if($settings.expire_trigger=='submit'){
					SUPER.init_popups.set_expiration_cookie($wrapper, $settings);
				}
			}
		}
	};

	// Init Popups
	SUPER.init_popups = function() {
		$('.super-popup-wrapper').appendTo(document.body);
		$('.super-popup-wrapper > .super-popup').each(function(){
			var $this = $(this);
			var $settings = $this.find('textarea[name="super-popup-settings"]').val();
			if(typeof $settings === 'string'){
				$settings = JSON.parse($settings);

				// Display on page load
				if( ($settings.page_load) == 'true' ) {
					SUPER.init_popups.show( $this, $settings );
				}

				// Exit intent
				if( ($settings.exit_intent)  == 'true' ) {
					jQuery(document).on('mouseleave', function() {
						SUPER.init_popups.show( $this, $settings );
					});
				}

				// Display the popup on page leave / page close/exit
				if( ($settings.leave)  == 'true' ) {
					jQuery(document).on('mouseleave', function() {
						SUPER.init_popups.show( $this, $settings );
					});
					window.onbeforeunload = function(e) {
						SUPER.init_popups.show( $this, $settings );
						var $msg = $settings.leave_msg;
						(e || window.event).returnValue = $msg;
						return $msg;
					};
				}

				// Display the popup when user scrolled xx% of the page
				if( parseInt($settings.scrolled, 10) > 0 ) {
					var page_height = $(document).height() - $(window).height();  
					$( window ).scroll(function() {    
						var scroll_level = $(window).scrollTop();  
						var scroll_level_percentage = ( parseInt(scroll_level, 10) * 100 ) / page_height; 
						if( parseInt(scroll_level_percentage, 10) == parseInt($settings.scrolled, 10) ) {
							SUPER.init_popups.show( $this, $settings );
						} 
					}); 
				}

				// Display popup after X seconds  
				if( $settings.enable_seconds=='true' ) {
					setTimeout(function(){
						SUPER.init_popups.show( $this, $settings );
					}, parseInt($settings.seconds, 10) * 1000 );
				}

				// Display popup after X seconds of inactivity
				if( parseInt($settings.inactivity, 10) > 0 ) {
					var idleTimer = null;
					var idleState = false;
					var idleWait = parseInt($settings.inactivity, 10) * 1000;
					$('*').on('mousemove hover dblclick mouseenter mouseleave click mouseup mousedown keydown scroll', function () {
						if(!idleState) {
							clearTimeout(idleTimer);
							idleTimer = setTimeout(function () {
								SUPER.init_popups.show( $this, $settings );
								idleState = true;
								clearTimeout(idleTimer);
							}, idleWait);
						}
					});
					$('body').trigger('mousemove');
				}
			}

		});
	};

	// Function to show / display the popup
	SUPER.init_popups.show = function( $this, $settings ) {
		if( (typeof $settings !== 'undefined') && ($settings!=null) ) {
			if($settings.enabled=='true'){
				var $wrapper = $this.parents('.super-popup-wrapper');
				if( !$wrapper.hasClass('initialized') ) {
					if( $settings.enable_schedule=='true' ) {
						if( $settings.from != '' && $settings.till != '' ) {
							var from = $settings.from;
							var till = $settings.till;
							var currentDate = new Date();
							from = from.split('-');
							from = from[1]+'/'+from[2]+'/'+from[0];
							till = till.split('-');
							till = till[1]+'/'+till[2]+'/'+till[0];
							currentDate =  (currentDate.getMonth() + 1)+'/'+currentDate.getDate()+'/'+currentDate.getFullYear();
							if(SUPER.init_popups.datecheck(from, till, currentDate)) {
								SUPER.init_popups.slide_in( $this, $settings );
							}
						}
					}else{
						SUPER.init_popups.slide_in( $this, $settings );
					}
				}
			}
		}
	};

	// Slide in function
	SUPER.init_popups.slide_in = function( $this, $settings ) {

        var $window_height;
        var $top_position;
        var left_position;
        var right_position;

        // @since 1.4.20
        // Before doing anything delete initialized class and remove index from perhaps already activate popups
        // This make sure there is never 2 popups active at the same time, which would result in overlapping problems.
        $('.super-popup-wrapper.initialized').removeClass('initialized').css('opacity','').css('z-index', '');

        var $wrapper = $this.parents('.super-popup-wrapper');
		$wrapper.addClass('initialized');
		
		// Set expiration instantly when popup has been viewed by the user
		if($settings.expire_trigger=='view'){
			SUPER.init_popups.set_expiration_cookie($wrapper, $settings);
		}

		$wrapper.css({ 
			'z-index': 1999999999 // @since 1.2.1 - fix with reCAPTCHA puzzle overlay
		}).animate({
			opacity: 1
		}, parseInt($settings.fade_duration, 10));
		
		// Default popup slide show
		SUPER.reset_popup_origin_position($this, $settings);
		if($settings.slide=='none'){
			$this.fadeIn(parseInt($settings.fade_duration, 10));  
		}

		// Slide from bottom
		if($settings.slide=='from_bottom'){
			$window_height = $(window).height();
			$top_position = ($window_height-$this.outerHeight())/2;
			$this.animate({
				opacity: 1
			}, { duration: parseInt($settings.fade_duration, 10), queue: false });
			$this.animate({
				top: $top_position
			}, { duration: parseInt($settings.slide_duration, 10), queue: false });
		}
		// Slide from right
		if($settings.slide=='from_right'){
			right_position = (($(window).width() - $this.outerWidth()) / 2) + $(window).scrollLeft();
			$this.animate({
				opacity: 1
			}, { duration: parseInt($settings.fade_duration, 10), queue: false });
			$this.animate({
				right: right_position + 'px'
			}, { duration: parseInt($settings.slide_duration, 10), queue: false });
		}
		// Slide from top
		if($settings.slide=='from_top'){
			$window_height = $(window).height();
			$top_position = ($window_height-$this.outerHeight())/2;
			$this.animate({
				opacity: 1
			}, { duration: parseInt($settings.fade_duration, 10), queue: false });
			$this.animate({
				top: $top_position
			}, { duration: parseInt($settings.slide_duration, 10), queue: false });
		}
		// Slide from left
		if($settings.slide=='from_left'){
			left_position = (($(window).width() - $this.outerWidth()) / 2) + $(window).scrollLeft();
			$this.animate({
				opacity: 1
			}, { duration: parseInt($settings.fade_duration, 10), queue: false });
			$this.animate({
				left: left_position + 'px' 
			}, { duration: parseInt($settings.slide_duration, 10), queue: false });
		}
	};


	// Compare date with date range
	SUPER.init_popups.datecheck = function( $fromdate, $todate, $checkdate ) {
		var from, to_date, check_date;
		from = Date.parse($fromdate);
		to_date = Date.parse($todate);
		check_date = Date.parse($checkdate);
		if( ( check_date <= to_date && check_date >= from ) ) {
			return true;
		}
		return false;
	};

	// Position the popup in it's hidden state
	SUPER.reset_popup_origin_position = function( $this, $settings ){
		var space_from_top = 100;
		var left_position = (($(window).width() - $this.outerWidth()) / 2) + $(window).scrollLeft();
		var top_position = ($(window).scrollTop()+space_from_top);
        var $width = $this.outerWidth(true);
        var $height = $this.outerHeight(true);
		var $window_height = $(window).height();
        var $difference_height;
        if( $window_height > $height ) {
			$difference_height = ($window_height - $height) / 2;
        }else{
			$difference_height = 30;
        }
        if($difference_height<=30){
			$difference_height=50;
        }
        top_position = $difference_height;
		if($settings.slide=='none'){
			$this.css({ 
				'top': top_position+'px',
				'left': left_position+'px' , 
				'opacity': 1,
			});  
		}
		if($settings.slide=='from_top'){
			$this.css({ 
				'top': -$height, 
				'left': left_position+'px' , 
			});
		}
		if($settings.slide=='from_bottom'){
			$this.css({ 
				'top': top_position+$window_height+'px',
				'left': left_position+'px',
			});
		}
		if($settings.slide=='from_right'){
			$this.css({ 
				'top': top_position+'px',
				'right': -$width, 
			});
		}
		if($settings.slide=='from_left'){
			$this.css({ 
				'top': top_position+'px',
				'left': -$width, 
			});
		}
	};

	// Init Responsive Popup
	SUPER.init_responsive_popup = function($classes) {
		var $w = $(window).outerWidth(true);
        $('.super-popup-wrapper > .super-popup').each(function(){
			var $this = $(this);
            if($w > 0 && $w < 530){
                SUPER.remove_super_form_classes($this,$classes);
                $this.addClass($classes[0]);
            }
            if($w >= 530 && $w < 760){
                SUPER.remove_super_form_classes($this,$classes);
                $this.addClass($classes[1]);
            }
            if($w >= 760 && $w < 1200){
                SUPER.remove_super_form_classes($this,$classes);
                $this.addClass($classes[2]);
            }
            if($w >= 1200 && $w < 1400){
                SUPER.remove_super_form_classes($this,$classes);
                $this.addClass($classes[3]);
            }
            if($w >= 1400){
                SUPER.remove_super_form_classes($this,$classes);
                $this.addClass($classes[4]);
            }

			var $settings = $this.find('textarea[name="super-popup-settings"]').val();
			$settings = JSON.parse($settings);
			var $width = $(this).children('.super-popup-content').outerWidth(true);
			var $height = $(this).children('.super-popup-content').outerHeight(true);
			var $body_width = $('body').outerWidth(true);
			var $difference = ($body_width-$width) / 2;
			var $window_height = $( window ).height();
			var $difference_height;
			if( $window_height > $height ) {
				$difference_height = ($window_height - $height) / 2;
			}else{
				$difference_height = 30;
			}
			if($difference_height<=30){
				$difference_height=50;
			}

			if($this.parents('.super-popup-wrapper:eq(0)').hasClass('initialized')){
				if($settings.slide=='from_right'){
					$this.css('right', $difference+'px');
				}else{
					$this.css('left', $difference+'px');
				}
				$this.css('top', $difference_height+'px');
			}else{
				SUPER.reset_popup_origin_position($this, $settings);
			}
						
			$this.children('.super-popup-content').css('max-height', ($window_height-70)+'px');
		});
	};

	// Set expiration cookie
	SUPER.init_popups.set_expiration_cookie = function($wrapper, $settings) {
		var $form_id = $wrapper.find('input[name="hidden_form_id"]').val();
		$.ajax({
			type: 'post',
			url: super_popup_i18n.ajaxurl,
			data: {
				action: 'super_set_popup_expire_cookie',
				form_id: $form_id,
				expire: $settings.expire
			}
		});
	};

	// Hide popup
	SUPER.init_popups.close = function($force_close) {
		if(typeof $force_close === 'undefined') $force_close = false;
		var $wrapper = $('.super-popup-wrapper.initialized');
		if($wrapper.length){
			var $settings = $wrapper.find('textarea[name="super-popup-settings"]').val();
			$settings = JSON.parse($settings);
			if( ($settings.disable_closing!='true') || ($force_close==true) ) {
				$wrapper.animate({
					opacity: 0
				}, parseInt($settings.fade_out_duration/2, 10), function() {
					$wrapper.css('z-index','');
					$wrapper.removeClass('initialized');
					SUPER.reset_popup_origin_position($wrapper.children('.super-popup'), $settings);
					// Set expiration when popup has been closed by the user
					if($settings.expire_trigger=='close'){
						SUPER.init_popups.set_expiration_cookie($wrapper, $settings);
					}
					// Remove form success/error messages upon closing
					$wrapper.find('.super-msg').remove();
					// Clear form?
					if($settings.clear_form==='true'){
						var form = $wrapper.find('.super-form')[0];
						SUPER.init_clear_form({form: form});
					}
				});
			}
		}
	};

	$(document).ready(function(){
		
		SUPER.init_popups();

		var $doc = $(document);
		$doc.on('click', '.super-popup-close:not(.super-form-button)', function(){
			SUPER.init_popups.close(true);
		});
		$(window).on('click', function(e){
            if(e.target.classList.contains('super-popup-wrapper')){
				SUPER.init_popups.close();
            }
		});
		$(window).keyup(function(e){
			if(e.keyCode === 27){
				SUPER.init_popups.close();
			}
		});

		$doc.on('click', 'a[href^="#super-popup-"]', function(){
			var $id = $(this).attr('href');
			$id = $id.replace('#super-popup-', '');
			var $this = $('.super-popup-wrapper > .super-popup-'+$id);
			if($this.length==0){
				alert('Popup could not be opened, Form ID: '+$id+' does not exist!');
			}else{
				var $settings = $this.find('textarea[name="super-popup-settings"]').val();
				$settings = JSON.parse($settings);
				SUPER.init_popups.show( $this, $settings );
			}
			return false;
		});

	});

})(jQuery);
