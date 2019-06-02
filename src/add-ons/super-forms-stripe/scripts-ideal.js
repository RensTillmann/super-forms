(function($) { // Hide scope, no $ conflict
	
	// Custom styling can be passed to options when creating an Element.
	// (Note that this demo uses a wider set of styles than the guide below.)
	var style = {

		// base, base style—all other variants inherit from this style
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

		// complete, applied when the Element has valid input

		// empty, applied when the Element has no customer input

		// invalid, applied when the Element has invalid input

		// For each of the above, the properties below can be customized.
		// color
		// fontFamily
		// fontSize
		// fontSmoothing
		// fontStyle
		// fontVariant
		// fontWeight
		// iconColor
		// lineHeight, to avoid cursors being rendered inconsistently across browsers, consider using a padding on the Element's container instead.
		// letterSpacing
		// textAlign, available for the cardNumber, cardExpiry, and cardCvc Elements.
		// padding, available for the idealBank Element.
		// textDecoration
		// textShadow
		// textTransform

		// The following pseudo-classes and pseudo-elements can also be styled with the above properties, as a nested object inside the variant.
		// :hover
		// :focus
		// ::placeholder
		// ::selection
		// :-webkit-autofill
		// :disabled, available for all Elements except the paymentRequestButton Element.
		// ::-ms-clear, available for the cardNumber, cardExpiry, and cardCvc Elements. Inside the ::-ms-clear selector, the display property can be customized.

		// The `paymentRequestButton` Element supports a single variant: `paymentRequestButton`.
		// The properties below are customizable for this variant.
		// type, one of default, donate, or buy. The default is default.
		// theme, one of dark, light, or light-outline. The default is dark.
		// height

	  invalid: {
	    color: '#fa755a',
	  }
	};
	var classes = {
	  	base: 'StripeElement',
	  	complete: 'StripeElement--complete',
	  	empty: 'StripeElement--empty',
	  	focus: 'StripeElement--focus',
	  	invalid: 'StripeElement--invalid',
	  	webkitAutofill: 'StripeElement--webkit-autofill'
	}

	var stripes = [];
	var elements = [];
	var ideals = [];
	var forms = document.querySelectorAll('.super-form');
    forms.forEach(function(form, index){
		// Create an instance of Elements.
		stripes[index] = Stripe(super_stripe_ideal_i18n.stripe_pk);
		elements[index] = stripes[index].elements();
		ideals[index] = elements[index].create('idealBank', {style: style});
    	ideals[index].mount(forms[index].querySelector('.super-stripe-ideal-element'));
		form.addEventListener('mouseleave', function(event) {
			document.querySelector('body').click();
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
	SUPER.stripe_ideal_create_source = function($event, $form, callback){
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
				// Specify the URL to which the customer should be redirected after paying.
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
					  // Redirect the customer to the authorization URL.
					  console.log(result);
					  console.log(result.source);
					  setTimeout(function(){
						  document.location.href = result.source.redirect.url;
					  },500);
					  // // Insert the token ID into the form so it gets submitted to the server
				   //    var source = result.token;
					  // var form = $form.children('form')[0];
				  	//   var div = document.createElement('div');
				  	//   div.className = 'super-shortcode super-field super-hidden';
				  	//   var input = document.createElement('input');
				  	//   input.className = 'super-shortcode-field';
					  // input.setAttribute('type', 'hidden');
					  // input.setAttribute('name', '_stripe_source');
					  // input.setAttribute('value', source);
					  // div.appendChild(input);
					  // form.appendChild(div);
					  // callback();
					}
				}
			});
	  	}
	  });
	}














})(jQuery);