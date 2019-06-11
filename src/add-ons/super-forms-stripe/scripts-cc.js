(function($) { // Hide scope, no $ conflict
	"use strict";
	// Custom styling can be passed to options when creating an Element.
	// (Note that this demo uses a wider set of styles than the guide below.)
	var style = {
	  base: {
	    color: '#32325d',
	    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
	    fontSmoothing: 'antialiased',
	    fontSize: '16px',
	    '::placeholder': {
	      color: '#aab7c4'
	    }
	  },
	  invalid: {
	    color: '#fa755a',
	    iconColor: '#fa755a'
	  }
	};

	var stripes = [];
	var elements = [];
	var cards = [];
	var forms = document.querySelectorAll('.super-form');
    forms.forEach(function(form, index){
		// Create an instance of Elements.
		stripes[index] = Stripe(super_stripe_cc_i18n.stripe_pk);
		elements[index] = stripes[index].elements();
		cards[index] = elements[index].create('card', {style: style});
    	cards[index].mount(forms[index].querySelector('.super-stripe-cc-element'));
		cards[index].addEventListener('change', function(event) {
		  var displayError = forms[index].querySelector('.super-stripe-cc-element').parentNode.querySelector('.super-card-errors');
		  if (event.error) {
		    displayError.textContent = event.error.message;
		  } else {
		    displayError.textContent = '';
		  }
		});
    });
	function handleServerResponse(response, stripe, $form, $old_html, callback) {
	  if (response.error) {
	    // Show error from server on payment form
	    console.log('Show error from server on payment form');
	  } else if (response.requires_action) {
	    // Use Stripe.js to handle required card action
	    console.log('Use Stripe.js to handle required card action');
	    stripe.handleCardAction(
	      response.payment_intent_client_secret
	    ).then(function(result) {
	      if (result.error) {
	        // Show error in payment form
            $('.super-msg').remove();
            $html = '<div class="super-msg super-error">';
            $html += result.error.message;
            $html += '<span class="close"></span>';
            $html += '</div>';
            $($html).prependTo($form);
			$form.find('.super-form-button.super-loading .super-button-name').html($old_html);
			$form.find('.super-form-button.super-loading').removeClass('super-loading');
	      } else {
	        // The card action has been handled
	        // The PaymentIntent can be confirmed again on the server
	    	console.log('The PaymentIntent can be confirmed again on the server');
	        fetch('/dev/wp-content/plugins/super-forms-bundle/add-ons/super-forms-stripe/stripe-server.php', {
	          method: 'POST',
	          headers: { 'Content-Type': 'application/json' },
	          body: JSON.stringify({ payment_intent_id: result.paymentIntent.id })
	        }).then(function(response) {
	          return response.json();
	        }).then(function(json){
		        handleServerResponse(json, undefined, $form, $old_html, callback);
	        });
	      }
	    });
	  } else {
	    // Show success message
	    console.log('Continue submitting the form / Show success message');
	    callback();
	  }
	}
	// Handle form submission.
	SUPER.stripe_cc_create_payment_method = function($event, $form, $data, $old_html, callback){
	  forms.forEach(function(form, index){
 		if($form[0] == form){
 			console.log('match!');
 			// Only if element is not conditionally hidden
 			var $this = $(forms[index].querySelector('.super-stripe-cc-element')),
 			  	$hidden = false,
				$parent = $this.parents('.super-shortcode:eq(0)');
            $this.parents('.super-shortcode.super-column').each(function(){
                if($(this).css('display')=='none'){
                    $hidden = true;
                }
            });
 			console.log($parent);
            if( ( $hidden===true )  || ( ( $parent.css('display')=='none' ) && ( !$parent.hasClass('super-hidden') ) ) ) {
                // Conditionally hidden
                console.log('test1');
            }else{
                console.log('test2');
				stripes[index].createPaymentMethod('card', cards[index], {
					billing_details: {name: 'Rens Tillmann'}
				}).then(function(result) {
					console.log(result);
					if (result.error) {
					  	// Show error in payment form
		    			console.log('Show error in payment form 1');
					} else {
						// Otherwise send paymentMethod.id to your server (see Step 2)
		    			console.log('Otherwise send paymentMethod.id to your server (see Step 2)');
						fetch('/dev/wp-content/plugins/super-forms-bundle/add-ons/super-forms-stripe/stripe-server.php', {
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify({ payment_method_id: result.paymentMethod.id })
						}).then(function(result) {
							// Handle server response (see Step 3)
							result.json().then(function(json) {
								handleServerResponse(json, stripes[index], $form, $old_html, callback);
							})
						});
					}
				});
            }
	  	}
	  });
	}
})(jQuery);