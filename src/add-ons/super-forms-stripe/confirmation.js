(function($) { // Hide scope, no $ conflict

	document.addEventListener('DOMContentLoaded', function(event) {
	  	var stripe = Stripe(super_stripe_confirmation_i18n.stripe_pk),
	  		status = super_stripe_confirmation_i18n.status,
	  		client_secret = super_stripe_confirmation_i18n.client_secret,
	  		livemode = super_stripe_confirmation_i18n.livemode,
	  		source = super_stripe_confirmation_i18n.source,
	  		node = document.querySelector('.verifying-payment');
        // Handle Animation
        function handle_animation(status){
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
								var els = document.querySelectorAll('.checkmark');
								for (var x = 0; x < els.length; x++){
								    els[x].style.opacity = '0';
								}
								node.querySelector('.s1').style.opacity = '';
								node.querySelector('.s2').style.opacity = '';
								node.querySelector('.caption.redirect').style.display = 'none';
								node.querySelector('.caption.failed').style.display = '';
								node.querySelector('.failed .title').innerHTML = 'Error: '+result.error.code;
								node.querySelector('.failed .description').innerHTML = result.error.message;
								node.classList.remove('verifying');
								node.classList.add('failed');
							},2000);
						}else{
							handle_animation(result.source.status);
						}
					});
				},2000);
		  	}
		  	if(status=='consumed'){
				node.querySelector('.s1').remove();
				node.querySelector('.s2').remove();
				setTimeout(function(){
					node.classList.add('verifying');
				},0);
				setTimeout(function(){
					node.classList.remove('verifying');
					node.classList.add('redirect');
				},2000);
		  	}
		  	if(status=='failed'){
				setTimeout(function(){
					node.classList.add('verifying');
				},0);
				setTimeout(function(){
					var els = document.querySelectorAll('.checkmark');
					for (var x = 0; x < els.length; x++){
					    els[x].style.opacity = '0';
					}
					node.querySelector('.s1').style.opacity = '';
					node.querySelector('.s2').style.opacity = '';
					node.querySelector('.caption.completing').remove();
					node.querySelector('.caption.redirect').remove();
					node.classList.remove('verifying');
					node.classList.add('failed');
				},2000);
		  	}
        }
        handle_animation(status);
	});

})(jQuery);