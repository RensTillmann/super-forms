(function($) { // Hide scope, no $ conflict
	
	// Custom styling can be passed to options when creating an Element.
	// (Note that this demo uses a wider set of styles than the guide below.)
	var style = {
	  base: {
	    padding: '10px 12px',
	    color: '#32325d',
	    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
	    fontSmoothing: 'antialiased',
	    fontSize: '16px',
	    '::placeholder': {
	      color: '#aab7c4'
	    },
	  },
	  invalid: {
	    color: '#fa755a',
	  }
	};

	var stripes = [];
	var elements = [];
	var ideals = [];
	var forms = document.querySelectorAll('.super-form');
    forms.forEach(function(form, index){
		// Create an instance of Elements.
		stripes[index] = Stripe(super_stripe_i18n.stripe_pk);
		elements[index] = stripes[index].elements();
		ideals[index] = elements[index].create('idealBank', {style: style});
    	ideals[index].mount(forms[index].querySelector('.super-stripe-ideal-element'));
		ideals[index].on('change', function(event){
		  	console.log('click idea2');
		});
		ideals[index].on('click', function(event){
		  	console.log('click ideal');
		});

    });
	function handleServerResponse(response, stripe) {
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
	    	console.log('Show error in payment form 2');
	      } else {
	        // The card action has been handled
	        // The PaymentIntent can be confirmed again on the server
	    	console.log('The PaymentIntent can be confirmed again on the server');
	        fetch('/dev/wp-content/plugins/super-forms-bundle/add-ons/super-forms-stripe/stripe-server.php', {
	          method: 'POST',
	          headers: { 'Content-Type': 'application/json' },
	          body: JSON.stringify({ payment_intent_id: result.paymentIntent.id })
	        }).then(function(confirmResult) {
	          return confirmResult.json();
	        }).then(handleServerResponse);
	      }
	    });
	  } else {
	    // Show success message
	    console.log('Show success message');
	  }
	}
	// Handle form submission.
	SUPER.create_stripe_token = function($event, $form, callback){
	  forms.forEach(function(form, index){
 		if($form[0] == form){
 			console.log('match!');
			$event.preventDefault();
			var sourceData = {
				type: 'ideal',
				amount: 1099,
				currency: 'eur',
				owner: {
					name: 'Rens Tillmann',
				},
				// Specify the URL to which the customer should be redirected
				// after paying.
				redirect: {
					return_url: 'https://f4d.nl/dev',
				},
			};
			// Call `stripe.createSource` with the idealBank Element and additional options.
			stripes[index].createSource(ideals[index], sourceData).then(function(result) {
				var displayError = forms[index].querySelector('.super-stripe-ideal-element').parentNode.querySelector('.super-ideal-errors');
				if(!result.source.ideal.bank){
				  // Inform the customer that there was an error.
				  displayError.textContent = 'Choose your bank!';
				  displayError.classList.add('visible');
				}else{
					if (result.error) {
					  // Inform the customer that there was an error.
					  displayError.textContent = result.error.message;
					  displayError.classList.add('visible');
					} else {
					  // Redirect the customer to the authorization URL.
					  displayError.classList.remove('visible');
					  // Insert the token ID into the form so it gets submitted to the server
				      var source = result.token;
					  var form = $form.children('form')[0];
				  	  var div = document.createElement('div');
				  	  div.className = 'super-shortcode super-field super-hidden';
				  	  var input = document.createElement('input');
				  	  input.className = 'super-shortcode-field';
					  input.setAttribute('type', 'hidden');
					  input.setAttribute('name', '_stripe_source');
					  input.setAttribute('value', source);
					  div.appendChild(input);
					  form.appendChild(div);
					  callback();
					}
				}
			});
	  	}
	  });
	}
})(jQuery);