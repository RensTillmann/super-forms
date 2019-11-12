(function($) { // Hide scope, no $ conflict
	"use strict";
	// Custom styling can be passed to options when creating an Element.
	// (Note that this demo uses a wider set of styles than the guide below.)
	/*
	var stripe = Stripe('pk_test_Uh5AjjHRjRJo7tliDMrSpq8j');
	var elements = stripe.elements();
	var cardElement = elements.create('card');
	cardElement.mount('#card-element');
	var cardholderName = document.getElementById('cardholder-name');
	var cardButton = document.getElementById('card-button');
	var clientSecret = cardButton.dataset.secret;
	cardButton.addEventListener('click', function(ev) {
	  stripe.confirmCardPayment(clientSecret, {
	    payment_method: {
	      card: cardElement,
	      billing_details: {name: cardholderName.value},
	    }
	  }).then(function(result) {
	    console.log(result);
	    if (result.error) {
	      console.log(result.error.message);
	      // Display error.message in your UI.
	    } else {
	      // The payment has succeeded. Display a success message.
	    }
	  });
	});
	*/

	SUPER.init_stripe_cc = function(){
		console.log('test init stripe cc');

		var classes = {
			base: 'super-stripe-base',
			complete: 'super-stripe-complete',
			empty: 'super-stripe-empty',
			focus: 'super-stripe-focus',
			invalid: 'super-stripe-invalid',
			webkitAutofill: 'super-stripe-autofill'
		};

		var style = {	
			// base style—all other variants inherit from this style
		  base: {
				// // backgroundColor, this property works best with the ::selection pseudo-class. In other cases, consider setting the background color on the Element's container instaed.
				//color: '#32325d',
				//fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
				//fontSize: '12px',
				//fontSmoothing: 'antialiased',
				// fontStyle
				// fontVariant
				// fontWeight
				// iconColor
				// lineHeight // to avoid cursors being rendered inconsistently across browsers, consider using a padding on the Element's container instead.
				// letterSpacing
				// textAlign // available for the cardNumber, cardExpiry, and cardCvc Elements.
				// padding // available for the idealBank Element. Accepts integer px values.
				// textDecoration
				// textShadow
				// textTransform

				// // The following pseudo-classes and pseudo-elements can also be styled with the above properties, as a nested object inside the variant.
				// :hover
				// :focus
		    //'::placeholder': {
		    //  color: '#aab7c4'
		    //},
		    // ::selection
				// :-webkit-autofill
				// :disabled // available for all Elements except the paymentRequestButton Element.
				// ::-ms-clear // available for the cardNumber, cardExpiry, and cardCvc Elements. Inside the ::-ms-clear selector, the display property can be customized.
				
				// // The paymentRequestButton Element supports a single variant: paymentRequestButton. The properties below are customizable for this variant.
				// type // one of default, book, buy, or donate. The default is default.
				//theme: 'light-outline' //one of dark, light, or light-outline. The default is dark.
				// height
		  },
		  // complete, applied when the Element has valid input
		  // empty, applied when the Element has no customer input
		  // invalid, applied when the Element has invalid input
		  invalid: {
		    color: '#fa755a',
		    iconColor: '#fa755a'
		  }
		};

		var stripes = [];
		var elements = [];
		var cards = [];
		var forms = document.querySelectorAll('.super-form, .super-create-form');
	  forms.forEach(function(form, index){
			// Create an instance of Elements.
			stripes[index] = Stripe(super_stripe_cc_i18n.stripe_pk);
			elements[index] = stripes[index].elements();
			cards[index] = elements[index].create('card', {
				classes: classes,
				style: style,
				hidePostalCode: false, // Hide the postal code field. Default is false. If you are already collecting a full billing address or postal code elsewhere, set this to true.
				iconStyle: 'solid' // Appearance of the icon in the Element. Either 'solid' or 'default'.
				//hideIcon: false, // Hides the icon in the Element. Default is false.
				//disabled: false // Applies a disabled state to the Element such that user input is not accepted. Default is false.

				//cardNumber, cardExpiry, cardCvc
				//placeholder: // Customize the placeholder text.
				//disabled: false // Applies a disabled state to the Element such that user input is not accepted. Default is false.
				//paymentRequestButton 

				// iban 
				// supportedCountries: ['SEPA'], // Specify the list of countries or country-groups whose IBANs you want to allow. Must be ['SEPA'].
				// placeholderCountry: 'DE', // Customize the country and format of the placeholder IBAN. Default is DE.
				// iconStyle: 'solid', // Appearance of the icon in the Element. Either 'solid' or 'default'.
				// hideIcon: false, // Hides the icon in the Element. Default is false.
				// disabled: false, // Applies a disabled state to the Element such that user input is not accepted. Default is false.

				// idealBank 
				// value: 'abn_amro', // A pre-filled value for the Element. Can be one of the banks listed in the iDEAL guide (e.g., abn_amro).
				// hideIcon: false, // Hides the bank icons in the Element. Default is false.
				// disabled: false // Applies a disabled state to the Element such that user input is not accepted. Default is false.

			});
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
	};

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
                $.ajax({
                    url: super_stripe_cc_i18n.ajaxurl,
                    type: 'post',
                    data: {
                        action: 'super_stripe_payment_intent',
                        data: $data
                    },
                    success: function (client_secret) {
                    	console.log(client_secret);
                    	console.log(stripes);
                    	console.log(cards);
						stripes[index].confirmCardPayment(client_secret, {
							payment_method: {
								card: cards[index],
								billing_details: {
									name: 'Rens Tillmann'
								}
							}
						}).then(function(result) {
							console.log(result);
							if (result.error) {
								// Display error.message in your UI.
								console.log(result.error.message);
			                    $('.super-msg').remove();
			                    var $html = '<div class="super-msg super-error">';
			                    $html += result.error.message;
			                    $html += '<span class="close"></span>';
			                    $html += '</div>';
			                    $($html).prependTo($form);
			                    // keep loading state active
			                    var $proceed = SUPER.before_scrolling_to_message_hook($form, $form.offset().top - 30);
			                    if($proceed===true){
			                        $('html, body').animate({
			                            scrollTop: $form.offset().top-200
			                        }, 1000);
			                    }
			                    $form.find('.super-form-button.super-loading .super-button-name').html($old_html);
			                    $form.find('.super-form-button.super-loading').removeClass('super-loading');
							} else {
								// The payment has succeeded. Display a success message.
								console.log('The payment has succeeded, submit the form');
								callback();
							}
						});
                    },
                    complete: function(){
                    	console.log('completed');
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        console.log(xhr, ajaxOptions, thrownError);
                        alert('Failed to process data, please try again');
                    }
                });

				// var stripe = Stripe('pk_test_Uh5AjjHRjRJo7tliDMrSpq8j');
				// var elements = stripe.elements();
				// var cardElement = elements.create('card');
				// cardElement.mount('#card-element');

				// var cardholderName = document.getElementById('cardholder-name');
				// var cardButton = document.getElementById('card-button');
				// var clientSecret = cardButton.dataset.secret;
				// cardButton.addEventListener('click', function(ev) {
				//   stripe.confirmCardPayment(clientSecret, {
				//     payment_method: {
				//       card: cardElement,
				//       billing_details: {name: cardholderName.value},
				//     }
				//   }).then(function(result) {
				//     console.log(result);
				//     if (result.error) {
				//       console.log(result.error.message);
				//       // Display error.message in your UI.
				//     } else {
				//       // The payment has succeeded. Display a success message.
				//     }
				//   });
				// });





				// stripes[index].createPaymentMethod('card', cards[index], {
				// 	billing_details: {name: 'Rens Tillmann'}
				// }).then(function(result) {
				// 	console.log(result);
				// 	if (result.error) {
				// 	  	// Show error in payment form
		  //   			console.log('Show error in payment form 1');
				// 	} else {
				// 		// Otherwise send paymentMethod.id to your server (see Step 2)
		  //   			console.log('Otherwise send paymentMethod.id to your server (see Step 2)');
				// 		fetch('/dev/wp-content/plugins/super-forms-bundle/add-ons/super-forms-stripe/stripe-server.php', {
				// 			method: 'POST',
				// 			headers: { 'Content-Type': 'application/json' },
				// 			body: JSON.stringify({ payment_method_id: result.paymentMethod.id })
				// 		}).then(function(result) {
				// 			// Handle server response (see Step 3)
				// 			result.json().then(function(json) {
				// 				handleServerResponse(json, stripes[index], $form, $old_html, callback);
				// 			})
				// 		});
				// 	}
				// });



            }
	  	}
	  });
	}

})(jQuery);