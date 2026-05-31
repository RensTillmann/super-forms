// (function($) { // Hide scope, no $ conflict
// 	"use strict";
// 	// Custom styling can be passed to options when creating an Element.
// 	// (Note that this demo uses a wider set of styles than the guide below.)
// 	var style = {

// 		// base, base style—all other variants inherit from this style
// 		base: {
// 		    padding: '10px 12px 10px 12px',
// 		    color: '#444444',
// 		    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
// 		    fontSmoothing: 'antialiased',
// 		    fontSize: '12px',
// 		    '::placeholder': {
// 		      color: '#aab7c4'
// 		    },

//    			// color: '#444444',
// 			// fontFamily: '"Open Sans",sans-serif',
// 			// fontSize: '12px',
// 			// padding: '8px 12px 8px 12px',
// 			// fontSmoothing: 'antialiased',

// 			// fontStyle: 'none', // normal|italic|oblique|initial|inherit;
// 			// fontVariant: 'normal', // normal|small-caps|initial|inherit;
// 			// fontWeight: 'normal', // normal|bold|bolder|lighter|number|initial|inherit;
// 			// iconColor: 'red',
// 			// lineHeight: 'normal', // normal|number|length|initial|inherit;
// 			// letterSpacing: 'normal', // normal|length|initial|inherit;
// 			// textAlign: 'left', // left|right|center|justify|initial|inherit;
// 			// padding: '7px 12px',
// 			// //textDecoration: 'underline overline wavy blue', // text-decoration: text-decoration-line text-decoration-color text-decoration-style|initial|inherit;
// 			// //textShadow: '2px 2px 4px #000000', //h-shadow v-shadow blur-radius color|none|initial|inherit;
// 			// textTransform: 'none', // none|capitalize|uppercase|lowercase|initial|inherit;
// 			// ':hover': {
// 			// 	color: '#aab7c4'
// 			// },
// 			// ':focus': {
// 			// 	color: '#aab7c4'
// 			// },
// 			// '::placeholder': {
// 			// 	color: '#aab7c4'
// 			// },
// 			// '::selection': {
// 			// 	color: '#aab7c4'
// 			// },
// 			// ':-webkit-autofill': {
// 			// 	color: '#aab7c4'
// 			// },
// 			// ':disabled': {
// 			// 	color: '#aab7c4'
// 			// },
// 			// '::-ms-clear': {
// 			// 	color: '#aab7c4'
// 			// },
// 		},

// 		// // complete, applied when the Element has valid input
// 		// complete: {
// 		// 	color: 'green',
// 		// 	fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
// 		// 	fontSize: '12px',
// 		// 	fontSmoothing: 'antialiased',
// 		// 	fontStyle: 'normal', // normal|italic|oblique|initial|inherit;
// 		// 	fontVariant: 'normal', // normal|small-caps|initial|inherit;
// 		// 	fontWeight: 'normal', // normal|bold|bolder|lighter|number|initial|inherit;
// 		// 	iconColor: 'red',
// 		// 	lineHeight: 'normal', // normal|number|length|initial|inherit;
// 		// 	letterSpacing: 'normal', // normal|length|initial|inherit;
// 		// 	textAlign: 'left', // left|right|center|justify|initial|inherit;
// 		// 	padding: '10px 12px',
// 		// 	textDecoration: 'underline overline wavy blue', // text-decoration: text-decoration-line text-decoration-color text-decoration-style|initial|inherit;
// 		// 	textShadow: '2px 2px 4px #000000', //h-shadow v-shadow blur-radius color|none|initial|inherit;
// 		// 	textTransform: 'none', // none|capitalize|uppercase|lowercase|initial|inherit;
// 		// 	':hover': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	':focus': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	'::placeholder': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	'::selection': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	':-webkit-autofill': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	':disabled': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	'::-ms-clear': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// },
		
// 		// // empty, applied when the Element has no customer input
// 		// empty: {
// 		// 	color: 'blue',
// 		// 	fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
// 		// 	fontSize: '12px',
// 		// 	fontSmoothing: 'antialiased',
// 		// 	fontStyle: 'normal', // normal|italic|oblique|initial|inherit;
// 		// 	fontVariant: 'normal', // normal|small-caps|initial|inherit;
// 		// 	fontWeight: 'normal', // normal|bold|bolder|lighter|number|initial|inherit;
// 		// 	iconColor: 'red',
// 		// 	lineHeight: 'normal', // normal|number|length|initial|inherit;
// 		// 	letterSpacing: 'normal', // normal|length|initial|inherit;
// 		// 	textAlign: 'left', // left|right|center|justify|initial|inherit;
// 		// 	padding: '10px 12px',
// 		// 	textDecoration: 'underline overline wavy blue', // text-decoration: text-decoration-line text-decoration-color text-decoration-style|initial|inherit;
// 		// 	textShadow: '2px 2px 4px #000000', //h-shadow v-shadow blur-radius color|none|initial|inherit;
// 		// 	textTransform: 'none', // none|capitalize|uppercase|lowercase|initial|inherit;
// 		// 	':hover': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	':focus': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	'::placeholder': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	'::selection': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	':-webkit-autofill': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	':disabled': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	'::-ms-clear': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// },

// 		// // invalid, applied when the Element has invalid input
// 		// invalid: {
// 		// 	color: 'red',
// 		// 	fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
// 		// 	fontSize: '12px',
// 		// 	fontSmoothing: 'antialiased',
// 		// 	fontStyle: 'normal', // normal|italic|oblique|initial|inherit;
// 		// 	fontVariant: 'normal', // normal|small-caps|initial|inherit;
// 		// 	fontWeight: 'normal', // normal|bold|bolder|lighter|number|initial|inherit;
// 		// 	iconColor: 'red',
// 		// 	lineHeight: 'normal', // normal|number|length|initial|inherit;
// 		// 	letterSpacing: 'normal', // normal|length|initial|inherit;
// 		// 	textAlign: 'left', // left|right|center|justify|initial|inherit;
// 		// 	padding: '10px 12px',
// 		// 	textDecoration: 'underline overline wavy blue', // text-decoration: text-decoration-line text-decoration-color text-decoration-style|initial|inherit;
// 		// 	textShadow: '2px 2px 4px #000000', //h-shadow v-shadow blur-radius color|none|initial|inherit;
// 		// 	textTransform: 'none', // none|capitalize|uppercase|lowercase|initial|inherit;

// 		// 	':hover': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	':focus': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	'::placeholder': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	'::selection': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	':-webkit-autofill': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	':disabled': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// 	'::-ms-clear': {
// 		// 		color: '#aab7c4'
// 		// 	},
// 		// },


// 		// For each of the above, the properties below can be customized.
// 		// color
// 		// fontFamily
// 		// fontSize
// 		// fontSmoothing
// 		// fontStyle
// 		// fontVariant
// 		// fontWeight
// 		// iconColor
// 		// lineHeight, to avoid cursors being rendered inconsistently across browsers, consider using a padding on the Element's container instead.
// 		// letterSpacing
// 		// textAlign, available for the cardNumber, cardExpiry, and cardCvc Elements.
// 		// padding, available for the idealBank Element.
// 		// textDecoration
// 		// textShadow
// 		// textTransform

// 		// The following pseudo-classes and pseudo-elements can also be styled with the above properties, as a nested object inside the variant.
// 		// :hover
// 		// :focus
// 		// ::placeholder
// 		// ::selection
// 		// :-webkit-autofill
// 		// :disabled, available for all Elements except the paymentRequestButton Element.
// 		// ::-ms-clear, available for the cardNumber, cardExpiry, and cardCvc Elements. Inside the ::-ms-clear selector, the display property can be customized.

// 		// The `paymentRequestButton` Element supports a single variant: `paymentRequestButton`.
// 		// The properties below are customizable for this variant.
// 		// type, one of default, donate, or buy. The default is default.
// 		// theme, one of dark, light, or light-outline. The default is dark.
// 		// height

// 	  invalid: {
// 	    color: '#fa755a',
// 	  }
// 	};
// 	var classes = {
// 	  	base: 'StripeElement',
// 	  	complete: 'StripeElement--complete',
// 	  	empty: 'StripeElement--empty',
// 	  	focus: 'StripeElement--focus',
// 	  	invalid: 'StripeElement--invalid',
// 	  	webkitAutofill: 'StripeElement--webkit-autofill'
// 	}

// 	var stripes = [];
// 	var elements = [];
// 	var ideals = [];
// 	var forms = document.querySelectorAll('.super-form');
//     forms.forEach(function(form, index){
// 		// Create an instance of Elements.
// 		stripes[index] = Stripe(super_stripe_ideal_i18n.stripe_pk);
// 		elements[index] = stripes[index].elements();
// 		ideals[index] = elements[index].create('idealBank', {
// 			style: style
// 			//value: 'abn_amro'
// 			//hideIcon: true
// 		});
//     	ideals[index].mount(forms[index].querySelector('.super-stripe-ideal-element'));
// 		form.addEventListener('mouseleave', function(event) {
// 			document.querySelector('body').click();
// 		});
// 		ideals[index].addEventListener('change', function(event) {
//             var $form = SUPER.get_frontend_or_backend_form(this);
//             var $duration = SUPER.get_duration();
//             var $this = $(form.querySelector('input[name="super_stripe_ideal"]'));
//             $this.val(event.value);
//             var $validation = $this.data('validation');
//             var $conditional_validation = $this.data('conditional-validation');
//             SUPER.handle_validations($this, $validation, $conditional_validation, $duration, $form);
//             SUPER.after_field_change_blur_hook($this);
// 			// var $duration = SUPER.get_duration();
// 			// var $this = $(form.querySelector('input[name="super_stripe_ideal"]'));
// 			// $this.val(event.value);
// 			// var $validation = $this.dataset.validation;
// 			// var $conditional_validation = $this.dataset.conditionalValidation;
// 			// SUPER.handle_validations($this, $validation, $conditional_validation, $duration, $form);
// 			// SUPER.after_field_change_blur_hook($this);
// 			// SUPER.after_field_change_blur_hook($(form.querySelector('input[name="super_stripe_ideal"]')));
// 		});
// 		// super_stripe_ideal
//     });
// 	function handleServerResponse(response, stripe) {
// 	  if (response.error) {
// 	    // Show error from server on payment form
// 	    console.log('Show error from server on payment form');
// 	  } else if (response.requires_action) {
// 	    // Use Stripe.js to handle required card action
// 	    console.log('Use Stripe.js to handle required card action');
// 	    stripe.handleCardAction(
// 	      response.payment_intent_client_secret
// 	    ).then(function(result) {
// 	      if (result.error) {
// 	        // Show error in payment form
// 	    	console.log('Show error in payment form 2');
// 	      } else {
// 	        // The card action has been handled
// 	        // The PaymentIntent can be confirmed again on the server
// 	    	console.log('The PaymentIntent can be confirmed again on the server');
// 	        fetch('/dev/wp-content/plugins/super-forms-bundle/add-ons/super-forms-stripe/stripe-server.php', {
// 	          method: 'POST',
// 	          headers: { 'Content-Type': 'application/json' },
// 	          body: JSON.stringify({ payment_intent_id: result.paymentIntent.id })
// 	        }).then(function(confirmResult) {
// 	          return confirmResult.json();
// 	        }).then(handleServerResponse);
// 	      }
// 	    });
// 	  } else {
// 	    // Show success message
// 	    console.log('Show success message');
// 	  }
// 	}

// 	// Redirect to Stripe
// 	SUPER.stripe_ideal_redirect = function($form){
// 		// var url = window.super_stripe_redirect;
// 		// delete window.super_stripe_redirect;
// 		// // If there is a sucess message show it for at least 2 seconds before redirecting to Stripe gateway
// 		// if($form.find('.super-msg.super-success')){
// 		// 	setTimeout(function(){
// 		// 		document.location.href = url;
// 		// 	},2000);
// 		// }else{
// 		// 	// Otherwise redirect instantly to Stripe, no need to let the user wait.
// 		// 	document.location.href = url;
// 		// }
// 	}

// 	// Handle form submission.
// 	SUPER.stripe_ideal_create_source = function($event, $form, $data, $oldHtml, callback){
// 	  forms.forEach(function(form, index){
//  		if($form[0] == form){
//  			console.log('match!');
// 			$event.preventDefault();
//  			console.log('ideal $data:', $data);
// 			var sourceData = {
// 				type: 'ideal',
// 				//amount: 1099,
// 				amount: $form.find('input[name="amount"]').val()*100,
// 				currency: 'eur',
// 				owner: {
// 					name: 'Rens Tillmann',
// 				},
// 				// Specify the URL to which the customer should be redirected after paying.
// 				redirect: {
// 					return_url: 'https://f4d.nl/dev',
// 				},
// 			};
// 			// Call `stripe.createSource` with the idealBank Element and additional options.
// 			stripes[index].createSource(ideals[index], sourceData).then(function(result) {
// 				var displayError = forms[index].querySelector('.super-stripe-ideal-element').parentNode.querySelector('.super-ideal-errors');
// 				if(!result.source.ideal.bank){
// 					// Inform the customer that there was an error.
// 					displayError.textContent = 'Choose your bank!';
// 					displayError.classList.add('visible');
// 				}else{
// 					if (result.error) {
// 						// Inform the customer that there was an error.
// 						displayError.textContent = result.error.message;
// 						displayError.classList.add('visible');
// 					} else {
// 						// Redirect the customer to the authorization URL.
// 						displayError.classList.remove('visible');
// 						// Redirect the customer to the authorization URL.
// 						console.log(result);
// 						console.log(result.source);
// 						window.super_stripe_redirect = result.source.redirect.url;
// 						callback(); // Submit the form
// 						//document.location.href = result.source.redirect.url;
// 						//document.location.href = window.super_stripe_redirect;
// 						// // Insert the token ID into the form so it gets submitted to the server
// 						//    var source = result.token;
// 						// var form = $form.find('form')[0];
// 						//   var div = document.createElement('div');
// 						//   div.className = 'super-shortcode super-field super-hidden';
// 						//   var input = document.createElement('input');
// 						//   input.className = 'super-shortcode-field';
// 						// input.setAttribute('type', 'hidden');
// 						// input.setAttribute('name', '_stripe_source');
// 						// input.setAttribute('value', source);
// 						// div.appendChild(input);
// 						// form.appendChild(div);
// 						// callback();
// 					}
// 				}
// 			});
// 	  	}
// 	  });
// 	}














// })(jQuery);