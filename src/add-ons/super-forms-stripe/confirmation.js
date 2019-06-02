(function($) { // Hide scope, no $ conflict

	document.addEventListener('DOMContentLoaded', function(event) {
	  	var stripe = Stripe(super_stripe_confirmation_i18n.stripe_pk),
	  		status = super_stripe_confirmation_i18n.status,
	  		client_secret = super_stripe_confirmation_i18n.client_secret,
	  		livemode = super_stripe_confirmation_i18n.livemode,
	  		source = super_stripe_confirmation_i18n.source,
	  		node = document.querySelector('.verifying-payment');
        
	  	// Set path attributes
		function create_path($d, $stroke, $class){
			var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
			path.style.opacity = '0';
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


        // Handle Animation
        function handle_animation(status){

        	// canceled == payment was canceled
        	// pending == payment method can take up to a few days to be processed
            // chargeable == waiting for bank to process payment
            // failed == canceled by user or due to other reason
            // consumed == completed
        	status = 'consumed';

		  	// If status is chargeable
		  	if(status=='chargeable'){
				setTimeout(function(){
					node.classList.add('verifying');
				},0);
				// Check for payment status for a specific set of time, otherwise timeout
				// If status didn't return 'failed' show message to the user that the payment might be processed at a later time
				setTimeout(function(){
					stripe.retrieveSource({
						id: source+'.',
						client_secret: client_secret,
					}).then(function(result) {
						// Handle result.error or result.source
						if (result.error) {
							setTimeout(function(){
            					// Add path 1
								var path = create_path('M 10,80 L 40,30', '#ff6868', 'p1');
								path.style.strokeDasharray = '90';
								path.style.strokeDashoffset = '-65';
								path.style.transitionDelay = '0.2s';
								path.style.transitionDuration = '0.2s';
								node.querySelector('svg').appendChild(path);
								// Add path 2
								var path = create_path('M 10,80 L 70,80', '#ff6868', 'p2');
								path.style.strokeDasharray = '90';
								path.style.strokeDashoffset = '90';
								path.style.transitionDelay = '0.4s';
								path.style.transitionDuration = '0.2s';
								node.querySelector('svg').appendChild(path);
								// Add path 3
								var path = create_path('M 40,30 L 70,80', '#ff6868', 'p3');
								path.style.strokeDasharray = '90';
								path.style.strokeDashoffset = '-60';
								path.style.transitionDelay = '0.6s';
								path.style.transitionDuration = '0.2s';
								node.querySelector('svg').appendChild(path);
								// Add path 4
								var path = create_path('M 40,48 L 40,63', '#ff6868', 'p4');
								path.style.strokeDasharray = '90';
								path.style.strokeDashoffset = '-16';
								path.style.transitionDelay = '0.4s';
								path.style.transitionDuration = '0.2s';
								node.querySelector('svg').appendChild(path);
								// Add path 5
								var path = create_path('M 40,70 L 40,70', '#ff6868', 'p5');
								path.style.strokeDasharray = '90';
								path.style.strokeDashoffset = '0';
								path.style.transitionDelay = '0.4s';
								path.style.transitionDuration = '0.2s';
								node.querySelector('svg').appendChild(path);
								// Add caption
								var caption = document.createElement('div');
								caption.className = 'caption failed';
								caption.innerHTML = '<div class="title">Error: '+result.error.code+'</div><div class="description">'+result.error.message+'</div>';
								node.querySelector('.wrapper').appendChild(caption);
								setTimeout(function(){
									node.querySelector('svg').style.marginBottom = '120px';
									node.querySelector('.border').style.opacity = '0';
									node.querySelector('.checkmark.p1').style.opacity = '1';
									node.querySelector('.checkmark.p1').style.strokeDashoffset = '0';
									node.querySelector('.checkmark.p2').style.opacity = '1';
									node.querySelector('.checkmark.p2').style.strokeDashoffset = '30';
									node.querySelector('.checkmark.p3').style.opacity = '1';
									node.querySelector('.checkmark.p3').style.strokeDashoffset = '0';
									node.querySelector('.checkmark.p4').style.opacity = '1';
									node.querySelector('.checkmark.p4').style.strokeDashoffset = '0';
									node.querySelector('.checkmark.p5').style.opacity = '1';
									node.querySelector('.checkmark.p5').style.strokeDashoffset = '0';
									node.classList.remove('verifying');
									node.classList.add('completed');
								},1000);
							},2000);
						}else{
							handle_animation(result.source.status);
						}
					});
				},2000);
		  	}
		  	
		  	// Consumed / completed payment
		  	if(status=='consumed'){
				setTimeout(function(){
					node.classList.add('verifying');
				},0);
				setTimeout(function(){
					// Add path 1
					var path = create_path('M 27,42 L 40,55', '#83ca6b', 'p1');
					path.style.strokeDasharray = '60';
					path.style.strokeDashoffset = '60';
					path.style.transitionDelay = '0.4s';
					path.style.transitionDuration = '0.2s';
					path.style.transitionProperty = 'stroke-dashoffset';
					node.querySelector('svg').appendChild(path);
					// Add path 2
					var path = create_path('M 60,30 L 40,55', '#83ca6b', 'p2');
					path.style.strokeDasharray = '60';
					path.style.strokeDashoffset = '-33';
					path.style.transitionDelay = '0.55s';
					path.style.transitionDuration = '0.2s';
					path.style.transitionProperty = 'stroke-dashoffset';
					node.querySelector('svg').appendChild(path);
					// Add caption
					var caption = document.createElement('div');
					caption.className = 'caption failed';
					caption.innerHTML = '<div class="title">Thank you for your order!</div><div class="description">We\'ll send your receipt to jenny@example.com as soon as your payment is confirmed.</div>';
					node.querySelector('.wrapper').appendChild(caption);
					setTimeout(function(){
						node.querySelector('svg').style.marginBottom = '130px';
						node.querySelector('.border').style.stroke = '#83ca6b';
						node.querySelector('.border').style.strokeDashoffset = '0';
						node.querySelector('.checkmark.p1').style.opacity = '1';
						node.querySelector('.checkmark.p1').style.strokeDashoffset = '40';
						node.querySelector('.checkmark.p2').style.opacity = '1';
						node.querySelector('.checkmark.p2').style.strokeDashoffset = '0';
						node.classList.remove('verifying');
						node.classList.add('completed');
					},1000);
				},1000);
		  	}

			// If payment was canceled
		  	if(status=='canceled'){
				setTimeout(function(){
					node.classList.add('verifying');
				},0);
				setTimeout(function(){
					// Add path 1
					var path = create_path('M 30,30 L 55,55', '#c5c5c5', 'p1');
					path.style.strokeDasharray = '60';
					path.style.strokeDashoffset = '-36';
					path.style.transitionDelay = '0.4s';
					path.style.transitionDuration = '0.4s';
					path.style.transitionProperty = 'stroke-dashoffset';
					node.querySelector('svg').appendChild(path);
					// Add path 2
					var path = create_path('M 55,30 L 30,55', '#c5c5c5', 'p2');
					path.style.strokeDasharray = '60';
					path.style.strokeDashoffset = '-36';
					path.style.transitionDelay = '0.8s';
					path.style.transitionDuration = '0.4s';
					path.style.transitionProperty = 'stroke-dashoffset';
					node.querySelector('svg').appendChild(path);
					// Add caption
					var caption = document.createElement('div');
					caption.className = 'caption failed';
					caption.innerHTML = '<div class="title">Payment canceled!</div>';
					node.querySelector('.wrapper').appendChild(caption);
					setTimeout(function(){
						node.querySelector('.border').style.stroke = '#c5c5c5';
						node.querySelector('.border').style.strokeDashoffset = '0';
						node.querySelector('.checkmark.p1').style.opacity = '1';
						node.querySelector('.checkmark.p1').style.strokeDashoffset = '0';
						node.querySelector('.checkmark.p2').style.opacity = '1';
						node.querySelector('.checkmark.p2').style.strokeDashoffset = '0';
						node.classList.remove('verifying');
						node.classList.add('completed');
					},1000);
				},1000);
		  	}

		  	// Failed payment
		  	if(status=='failed'){
				setTimeout(function(){
					node.classList.add('verifying');
				},0);
				setTimeout(function(){
					// Add path 1
					var path = create_path('M 30,30 L 55,55', '#ff6868', 'p1');
					path.style.strokeDasharray = '60';
					path.style.strokeDashoffset = '-36';
					path.style.transitionDelay = '0.4s';
					path.style.transitionDuration = '0.4s';
					path.style.transitionProperty = 'stroke-dashoffset';
					node.querySelector('svg').appendChild(path);
					// Add path 2
					var path = create_path('M 55,30 L 30,55', '#ff6868', 'p2');
					path.style.strokeDasharray = '60';
					path.style.strokeDashoffset = '-36';
					path.style.transitionDelay = '0.8s';
					path.style.transitionDuration = '0.4s';
					path.style.transitionProperty = 'stroke-dashoffset';
					node.querySelector('svg').appendChild(path);
					// Add caption
					var caption = document.createElement('div');
					caption.className = 'caption failed';
					caption.innerHTML = '<div class="title">Payment failed!</div><div class="description">We couldn\'t process your order.</div>';
					node.querySelector('.wrapper').appendChild(caption);
					setTimeout(function(){
						node.querySelector('svg').style.marginBottom = '130px';
						node.querySelector('.border').style.stroke = '#ff6868';
						node.querySelector('.border').style.strokeDashoffset = '0';
						node.querySelector('.checkmark.p1').style.opacity = '1';
						node.querySelector('.checkmark.p1').style.strokeDashoffset = '0';
						node.querySelector('.checkmark.p2').style.opacity = '1';
						node.querySelector('.checkmark.p2').style.strokeDashoffset = '0';
						node.classList.remove('verifying');
						node.classList.add('completed');
					},1000);
				},1000);
		  	}
        }
        handle_animation(status);
	});

})(jQuery);