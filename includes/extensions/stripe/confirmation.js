/* globals jQuery, Stripe, super_stripe_confirmation_i18n */
(function() { // Hide scope, no $ conflict
	"use strict";
	document.addEventListener('DOMContentLoaded', function() {
	  	var stripe = Stripe(super_stripe_confirmation_i18n.stripe_pk),
	  		status = super_stripe_confirmation_i18n.status,
	  		client_secret = super_stripe_confirmation_i18n.client_secret,
	  		//livemode = super_stripe_confirmation_i18n.livemode,
	  		source = super_stripe_confirmation_i18n.source,	  		
	  		node = document.querySelector('.verifying-payment');

        // Handle Animation
        function handle_animation(status){
        	// canceled == payment was canceled
        	// pending == payment method can take up to a few days to be processed
            // chargeable == waiting for bank to process payment
            // failed == canceled by user or due to other reason
            // consumed == completed

		  	// If status is chargeable
		  	if(status=='chargeable'){
				setTimeout(function(){
					node.classList.add('verifying');
				},50);
				// Check for payment status for a specific set of time, otherwise timeout
				// If status didn't return 'failed' show message to the user that the payment might be processed at a later time
				setTimeout(function(){
					stripe.retrieveSource({
						id: source,
						client_secret: client_secret,
					}).then(function(result) {
						// Handle result.error or result.source
						if (result.error) {
							path_animation('error', node, result);
						}else{
							path_animation('chargeable', node, result);
						}
					});
				},2000);
		  	}else{
		  		// Other statuses
		  		path_animation(status, node);
		  	}
        }
        handle_animation(status);

	  	// Set path attributes
		function create_path( $d, $stroke, $strokeDasharray, $strokeDashoffset, $transitionDelay, $transitionDuration, $transitionProperty, $class ) {
			var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
			path.style.opacity = '0';
			path.style.strokeDasharray = $strokeDasharray;
			path.style.strokeDashoffset = $strokeDashoffset;
			path.style.transitionDelay = $transitionDelay;
			path.style.transitionDuration = $transitionDuration;
			if($transitionProperty!=='undefined'){
				path.style.transitionProperty = 'stroke-dashoffset';
			}
			path.setAttribute('stroke-linecap', 'round');
			path.setAttribute('stroke-linejoin', 'round');
			path.setAttribute('d', $d);
			path.setAttribute('stroke-width', '4');
			path.setAttribute('stroke', $stroke);
			path.setAttribute('fill', 'none');
			path.classList.add('checkmark');
			path.classList.add($class);
			return path;
		}
		function update_checkmark(node, selector, opacity, strokeDashoffset){
			node.querySelector(selector).style.opacity = opacity;
			node.querySelector(selector).style.strokeDashoffset = strokeDashoffset;
		}
		function path_animation($type, node, result){
			if($type=='chargeable'){
				var path = create_path('M 27,42 L 40,55', '#000', '60', '60', '0.4s', '0.2s', 'stroke-dashoffset', 'p1');
				node.querySelector('.caption .title svg').appendChild(path);
				path = create_path('M 60,30 L 40,55', '#000', '60', '86', '0.55s', '0.2s', 'stroke-dashoffset', 'p2');
				node.querySelector('.caption .title svg').appendChild(path);
				setTimeout(function(){
					update_checkmark(node, '.checkmark.p1', '1', '40');
					update_checkmark(node, '.checkmark.p2', '1', '120');
				},50);

				var clone = node.querySelector('.caption').cloneNode(true);
				clone.classList.add('completing');
				clone.style.opacity = '0';
				clone.style.transform = 'translateY(80px) scale(1)';
				update_checkmark(clone, '.checkmark.p1', '0', '60');
				update_checkmark(clone, '.checkmark.p2', '0', '86');
				clone.querySelector('.caption .title span').innerHTML = super_stripe_confirmation_i18n.chargeable;
				node.querySelector('.wrapper').appendChild(clone);
				setTimeout(function(){
					node.querySelector('.completing').style.opacity = '1';
				},550);
				setTimeout(function(){
					if(result.source.status=='consumed'){
						update_checkmark(node, '.completing .checkmark.p1', '1', '40');
						update_checkmark(node, '.completing .checkmark.p2', '1', '120');
					}
					handle_animation(result.source.status);
				},2000);
			}
			if($type=='consumed'){
				setTimeout(function(){
					node.classList.add('verifying');
				},50);
				setTimeout(function(){
					var path = create_path('M 27,42 L 40,55', '#83ca6b', '60', '60', '0.4s', '0.2s', 'stroke-dashoffset', 'p1');
					node.querySelector('svg').appendChild(path);
					path = create_path('M 60,30 L 40,55', '#83ca6b', '60', '86', '0.55s', '0.2s', 'stroke-dashoffset', 'p2');
					node.querySelector('svg').appendChild(path);
					// Add caption
					var caption = document.createElement('div');
					caption.className = 'caption failed';
					caption.innerHTML = super_stripe_confirmation_i18n.consumed;
					node.querySelector('.wrapper').appendChild(caption);
					setTimeout(function(){
						node.querySelectorAll('.caption').forEach(function(el) {
						  	el.style.opacity = '0';
						});
						node.querySelector('.caption.failed').style.opacity = '1';
						//node.querySelector('svg').style.marginBottom = '130px';
						node.querySelector('.border').style.stroke = '#83ca6b';
						node.querySelector('.border').style.strokeDashoffset = '0';
						update_checkmark(node, '.checkmark.p1', '1', '40');
						update_checkmark(node, '.checkmark.p2', '1', '120');
						node.classList.remove('verifying');
						node.classList.add('completed');
					},1000);
				},2000);
			}
			if($type=='pending'){
				setTimeout(function(){
					node.classList.add('verifying');
				},50);
				setTimeout(function(){
					var path = create_path('M 42,15 L 42,45', '#ff9600', '60', '90', '0.4s', '0.4s', 'stroke-dashoffset', 'p1');
					node.querySelector('svg').appendChild(path);
					path = create_path('M 58,55 L 42,45', '#ff9600', '60', '100', '0.8s', '0.4s', 'stroke-dashoffset', 'p2');
					node.querySelector('svg').appendChild(path);
					// Add caption
					var caption = document.createElement('div');
					caption.className = 'caption failed';
					caption.innerHTML = super_stripe_confirmation_i18n.pending;
					node.querySelector('.wrapper').appendChild(caption);
					setTimeout(function(){
						//node.querySelector('svg').style.marginBottom = '130px';
						node.querySelector('.border').style.stroke = '#ffe3bb';
						node.querySelector('.border').style.strokeDashoffset = '0';
						update_checkmark(node, '.checkmark.p1', '1', '120');
						update_checkmark(node, '.checkmark.p2', '1', '120');
						node.classList.remove('verifying');
						node.classList.add('completed');
					},1000);
				},1000);
			}
			if($type=='canceled'){
				setTimeout(function(){
					node.classList.add('verifying');
				},50);
				setTimeout(function(){
					var path = create_path('M 30,30 L 55,55', '#616161', '60', '84', '0.4s', '0.4s', 'stroke-dashoffset', 'p1');
					node.querySelector('svg').appendChild(path);
					path = create_path('M 55,30 L 30,55', '#616161', '60', '84', '0.8s', '0.4s', 'stroke-dashoffset', 'p2');
					node.querySelector('svg').appendChild(path);
					// Add caption
					var caption = document.createElement('div');
					caption.className = 'caption failed';
	  				caption.innerHTML = super_stripe_confirmation_i18n.canceled;
					node.querySelector('.wrapper').appendChild(caption);
					setTimeout(function(){
						node.querySelector('.border').style.stroke = '#e8e8e8';
						node.querySelector('.border').style.strokeDashoffset = '0';
						update_checkmark(node, '.checkmark.p1', '1', '120');
						update_checkmark(node, '.checkmark.p2', '1', '120');
						node.classList.remove('verifying');
						node.classList.add('completed');
					},1000);
				},1000);
			}
			if($type=='failed'){
				setTimeout(function(){
					node.classList.add('verifying');
				},50);
				setTimeout(function(){
					var path = create_path('M 30,30 L 55,55', '#ff6868', '60', '84', '0.4s', '0.4s', 'stroke-dashoffset', 'p1');
					node.querySelector('svg').appendChild(path);
					path = create_path('M 55,30 L 30,55', '#ff6868', '60', '84', '0.8s', '0.4s', 'stroke-dashoffset', 'p2');
					node.querySelector('svg').appendChild(path);
					// Add caption
					var caption = document.createElement('div');
					caption.className = 'caption failed';
					caption.innerHTML = super_stripe_confirmation_i18n.failed;
					node.querySelector('.wrapper').appendChild(caption);
					setTimeout(function(){
						//node.querySelector('svg').style.marginBottom = '130px';
						node.querySelector('.border').style.stroke = '#ffdcdc';
						node.querySelector('.border').style.strokeDashoffset = '0';
						update_checkmark(node, '.checkmark.p1', '1', '120');
						update_checkmark(node, '.checkmark.p2', '1', '120');
						node.classList.remove('verifying');
						node.classList.add('completed');
					},1000);
				},1000);
			}
			if($type=='error'){
				setTimeout(function(){
					var path = create_path('M 10,80 L 40,30', '#ff6868', '90', '120', '0.2s', '0.2s', undefined, 'p1');
					node.querySelector('svg').appendChild(path);
					path = create_path('M 10,80 L 70,80', '#ff6868', '90', '90', '0.4s', '0.2s', undefined, 'p2');
					node.querySelector('svg').appendChild(path);
					path = create_path('M 40,30 L 70,80', '#ff6868', '90', '120', '0.6s', '0.2s', undefined, 'p3');
					node.querySelector('svg').appendChild(path);
					path = create_path('M 40,48 L 40,63', '#ff6868', '90', '165', '0.4s', '0.2s', undefined, 'p4');
					node.querySelector('svg').appendChild(path);
					path = create_path('M 40,70 L 40,70', '#ff6868', '90', '0', '0.4s', '0.2s', undefined, 'p5');
					node.querySelector('svg').appendChild(path);
					// Add caption
					var caption = document.createElement('div');
					caption.className = 'caption failed';
					caption.innerHTML = '<div class="title">Error: '+result.error.code+'</div><div class="description">'+result.error.message+'</div>';
					node.querySelector('.wrapper').appendChild(caption);
					setTimeout(function(){
						//node.querySelector('svg').style.marginBottom = '120px';
						node.querySelector('.border').style.opacity = '0';
						update_checkmark(node, '.checkmark.p1', '1', '180');
						update_checkmark(node, '.checkmark.p2', '1', '30');
						update_checkmark(node, '.checkmark.p3', '1', '180');
						update_checkmark(node, '.checkmark.p4', '1', '180');
						update_checkmark(node, '.checkmark.p5', '1', '0');
						node.classList.remove('verifying');
						node.classList.add('completed');
					},1000);
				},2000);
			}
		}
	});

})(jQuery);