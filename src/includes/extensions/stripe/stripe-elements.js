/* eslint-disable no-undef */


// Submit button is clicked
// Before sending email we must check:
// - if ALL stripe elements are empty
// - if Stripe checkout is enabled and if it is conditionally set







(function ($) { // Hide scope, no $ conflict
    "use strict";

    SUPER.Stripe = {};

    SUPER.Stripe.StripesIdeal = [];
    SUPER.Stripe.StripesIban = [];
    SUPER.Stripe.StripesCc = [];
    SUPER.Stripe.elements = [];
    SUPER.Stripe.cards = [];
    SUPER.Stripe.ideal = [];
    SUPER.Stripe.iban = [];
    SUPER.Stripe.forms = document.querySelectorAll('.super-form, .super-preview-elements');
    console.log(SUPER.Stripe.forms);

    var classes = {
        base: 'super-stripe-base',
        complete: 'super-stripe-complete',
        empty: 'super-stripe-empty',
        focus: 'super-stripe-focus',
        invalid: 'super-stripe-invalid',
        webkitAutofill: 'super-stripe-autofill'
    };

    console.log(super_stripe_i18n.styles.idealPadding);

    var style = {
        base: {
            color: super_stripe_i18n.styles.color,
            iconColor: super_stripe_i18n.styles.iconColor,
            fontFamily: super_stripe_i18n.styles.fontFamily,
            fontSize: super_stripe_i18n.styles.fontSize + 'px',
            padding: super_stripe_i18n.styles.idealPadding, // padding // available for the idealBank Element. Accepts integer px values.
            ':focus': {
                color: super_stripe_i18n.styles.colorFocus,
                iconColor: super_stripe_i18n.styles.iconColorFocus,
                '::placeholder': {
                    color: super_stripe_i18n.styles.placeholderFocus
                },
            },
            '::placeholder': {
                color: super_stripe_i18n.styles.placeholder
            },
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };

    // Validate before submitting form
    SUPER.stripe_validate = function(event, form, data, oldHtml, callback){
        console.log(event, form, data, oldHtml, callback);
        console.log(SUPER.Stripe.forms);
        var stripeFound = false;
        var notEmpty = false;
        SUPER.Stripe.forms.forEach(function (stripeForm, index) {
            if ((stripeForm == form) && (SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element'))) {
                stripeFound = true;
                console.log('stripe ideal found!');
                // Check if element exists and if not empty
                if (SUPER.Stripe.ideal[index] && !SUPER.Stripe.ideal[index]._empty) {
                    notEmpty = true;
                }
            }
            if ((stripeForm == form) && (SUPER.Stripe.forms[index].querySelector('.super-stripe-iban-element'))) {
                stripeFound = true;
                console.log('stripe iban found!');
                // Check if element exists and if not empty
                if (SUPER.Stripe.iban[index] && !SUPER.Stripe.iban[index]._empty) {
                    notEmpty = true;
                }
            }
            if ((stripeForm == form) && (SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element'))) {
                stripeFound = true;
                console.log('stripe cc found!');
                // Check if element exists and if not empty
                if (SUPER.Stripe.cards[index] && !SUPER.Stripe.cards[index]._empty) {
                    notEmpty = true;
                }
            }
        });
        if(stripeFound && !notEmpty){
            return '{"error":true,"msg":"'+super_stripe_i18n.choose_payment_method+'"}';
        }
        return '{"error":false}';
    };

    // Initialize Stripe Elements
    SUPER.init_stripe_elements = function () {
        console.log('test1');
        console.log(SUPER.Stripe.forms);
        SUPER.Stripe.forms = document.querySelectorAll('.super-form, .super-preview-elements');
        SUPER.Stripe.forms.forEach(function (form, index) {
            console.log('test2');
            if (SUPER.Stripe.forms[index].querySelector('.super-stripe-iban-element')) {
                console.log('test3');
                // Check if not yet initialized
                if (!SUPER.Stripe.forms[index].querySelector('.super-stripe-iban-element').classList.contains('super-stripe-initialized')) {
                    console.log('test4');
                    // Add initialized class
                    SUPER.Stripe.forms[index].querySelector('.super-stripe-iban-element').classList.add('super-stripe-initialized');
                    // Create an instance of Elements.
                    SUPER.Stripe.StripesIban[index] = Stripe(super_stripe_i18n.stripe_pk);
                    SUPER.Stripe.elements[index] = SUPER.Stripe.StripesIban[index].elements();
                    console.log(style);
                    SUPER.Stripe.iban[index] = SUPER.Stripe.elements[index].create('iban', {
                        supportedCountries: ['SEPA'],
                        classes: classes,
                        style: style,
                        hidePostalCode: true, // Hide the postal code field. Default is false. If you are already collecting a full billing address or postal code elsewhere, set this to true.
                        iconStyle: 'solid', // Appearance of the icon in the Element. Either 'solid' or 'default'.
                        hideIcon: false // Hides the icon in the Element. Default is false.
                    });
                    SUPER.Stripe.iban[index].mount(SUPER.Stripe.forms[index].querySelector('.super-stripe-iban-element'));
                }
            }
            if (SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element')) {
                console.log('test3');
                // Check if not yet initialized
                if (!SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element').classList.contains('super-stripe-initialized')) {
                    console.log('test4');
                    // Add initialized class
                    SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element').classList.add('super-stripe-initialized');
                    // Create an instance of Elements.
                    SUPER.Stripe.StripesIdeal[index] = Stripe(super_stripe_i18n.stripe_pk);
                    SUPER.Stripe.elements[index] = SUPER.Stripe.StripesIdeal[index].elements();
                    console.log(style);
                    SUPER.Stripe.ideal[index] = SUPER.Stripe.elements[index].create('idealBank', {
                        classes: classes,
                        style: style,
                        hidePostalCode: true, // Hide the postal code field. Default is false. If you are already collecting a full billing address or postal code elsewhere, set this to true.
                        iconStyle: 'solid', // Appearance of the icon in the Element. Either 'solid' or 'default'.
                        hideIcon: false // Hides the icon in the Element. Default is false.
                    });
                    SUPER.Stripe.ideal[index].mount(SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element'));
                }
            }
            if (SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element')) {
                // Check if not yet initialized
                if (!SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element').classList.contains('super-stripe-initialized')) {
                    // Add initialized class
                    SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element').classList.add('super-stripe-initialized');
                    // Create an instance of Elements.
                    SUPER.Stripe.StripesCc[index] = Stripe(super_stripe_i18n.stripe_pk);
                    SUPER.Stripe.elements[index] = SUPER.Stripe.StripesCc[index].elements();
                    SUPER.Stripe.cards[index] = SUPER.Stripe.elements[index].create('card', {
                        classes: classes,
                        style: style,
                        hidePostalCode: true, // Hide the postal code field. Default is false. If you are already collecting a full billing address or postal code elsewhere, set this to true.
                        iconStyle: 'solid', // Appearance of the icon in the Element. Either 'solid' or 'default'.
                        hideIcon: false // Hides the icon in the Element. Default is false.
                    });
                    SUPER.Stripe.cards[index].mount(SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element'));
                    SUPER.Stripe.cards[index].addEventListener('change', function (event) {
                        var $parent = $(SUPER.Stripe.cards[index]._parent).parents('.super-field:eq(0)');
                        if (event.error) {
                            if ($parent.children('.super-error-msg').length === 0) {
                                $('<div class="super-error-msg">' + event.error.message + '</div>').appendTo($parent);
                            }
                            $parent.addClass('super-error-active');
                        } else {
                            $parent.removeClass('super-error-active');
                        }
                    });
                }
            }
        });
    };

    // Handle form submission.
    SUPER.stripe_ideal_create_payment_method = function (args){ //$form, $data, $oldHtml, $response) {
        SUPER.Stripe.forms = document.querySelectorAll('.super-form, .super-preview-elements');
        SUPER.Stripe.forms.forEach(function (form, index) {
            if ((args.form == form) && (SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element'))) {
                console.log('match ideal!');
                // Check if element exists and if not empty
                if (SUPER.Stripe.ideal[index] && SUPER.Stripe.ideal[index]._empty) {
                    console.log('skip ideal element, because it is empty');
                    return false;
                }
                // Only if element is not conditionally hidden
                var $this = $(SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element')),
                    $hidden = false,
                    $parent = $this.parents('.super-shortcode:eq(0)');
                $this.parents('.super-shortcode.super-column').each(function () {
                    if ($(this).css('display') == 'none') {
                        $hidden = true;
                    }
                });
                console.log($parent);
                if (($hidden === true) || (($parent.css('display') == 'none') && (!$parent.hasClass('super-hidden')))) {
                    // Conditionally hidden
                    console.log('test1');
                } else {
                    console.log('test2');
                    // First make sure that the form will not hide, otherwise the data would be gone, and stripe won't know the credit card information
                    args.from.data('is-redirecting', 'true');
                    // Make payment intent
                    $.ajax({
                        url: super_stripe_i18n.ajaxurl,
                        type: 'post',
                        data: {
                            action: 'super_stripe_prepare_payment',
                            payment_method: 'ideal',
                            data: $data,
                            response: $response
                        },
                        success: function (result) {
                            result = JSON.parse(result);
                            args.result = result;
                            args.stripe = SUPER.Stripe.StripesIdeal[index]; 
                            console.log(result);
                            if (result.error) {
                                SUPER.stripe_proceed(args); //result, args.form, args.oldHtml, args.data, SUPER.Stripe.StripesIdeal[index]);
                                return false;
                            }
                            if (result.stripe_method == 'subscription') {
                                result.error = true;
                                result.msg = super_stripe_i18n.ideal_subscription_error;
                                SUPER.stripe_proceed(args); //result, args.form, args.oldHtml, args.data, SUPER.Stripe.StripesIdeal[index]);
                                return false;
                            } else {
                                // Single payment checkout
                                // Redirect to Stripe iDeal payment page
                                SUPER.Stripe.StripesIdeal[index].confirmIdealPayment(result.client_secret, {
                                    payment_method: {
                                        ideal: SUPER.Stripe.ideal[index],
                                        billing_details: {
                                           name: 'Rens Tillmann3'
                                        }
                                    },
                                    return_url: result.return_url // Required for iDeal payment method
                                }).then(function (result) {
                                    if (result.error) {
                                        SUPER.stripe_proceed(args); //result, args.form, args.oldHtml, args.data, SUPER.Stripe.StripesIdeal[index]);
                                        return false;
                                    }
                                });
                            }
                        },
                        complete: function () {
                            console.log('completed1');
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            console.log(xhr, ajaxOptions, thrownError);
                            alert('Failed to process data, please try again');
                        }
                    });
                }
            }
        });
    };

    // Handle form submission.
    SUPER.stripe_iban_create_payment_method = function (args){ //$form, $data, $oldHtml, $response) {
        SUPER.Stripe.forms = document.querySelectorAll('.super-form, .super-preview-elements');
        SUPER.Stripe.forms.forEach(function (form, index) {
            if ((args.form == form) && (SUPER.Stripe.forms[index].querySelector('.super-stripe-iban-element'))) {
                console.log('match iban!');
                // Check if element exists and if not empty
                if (SUPER.Stripe.iban[index] && SUPER.Stripe.iban[index]._empty) {
                    console.log('skip iban element, because it is empty');
                    return false;
                }
                // Only if element is not conditionally hidden
                var $this = $(SUPER.Stripe.forms[index].querySelector('.super-stripe-iban-element')),
                    $hidden = false,
                    $parent = $this.parents('.super-shortcode:eq(0)');
                $this.parents('.super-shortcode.super-column').each(function () {
                    if ($(this).css('display') == 'none') {
                        $hidden = true;
                    }
                });
                console.log($parent);
                if (($hidden === true) || (($parent.css('display') == 'none') && (!$parent.hasClass('super-hidden')))) {
                    // Conditionally hidden
                    console.log('test1');
                } else {
                    console.log('test2');
                    // First make sure that the form will not hide, otherwise the data would be gone, and stripe won't know the credit card information
                    args.form.data('is-redirecting', 'true');
                    args.form.data('is-doing-things', 'true');
                    // Make payment intent
                    $.ajax({
                        url: super_stripe_i18n.ajaxurl,
                        type: 'post',
                        data: {
                            action: 'super_stripe_prepare_payment',
                            payment_method: 'sepa_debit',
                            data: $data,
                            response: $response
                        },
                        success: function (result) {
                            result = JSON.parse(result);
                            console.log('test@1');
                            console.log(result);
                            args.result = result;
                            args.stripe = SUPER.Stripe.StripesIban[index];
                            if (result.stripe_method == 'subscription') {
                                console.log('test@2');
                                // requires_confirmation
                                SUPER.Stripe.StripesIban[index].confirmSepaDebitPayment(result.client_secret, {
                                    payment_method: {
                                        sepa_debit: SUPER.Stripe.iban[index],
                                        billing_details: {
                                            name: 'Jenny Rosen4',
                                            email: 'jenny@example.com',
                                        },
                                    },
                                }).then(function(result) {
                                    console.log('test@3');
                                    SUPER.stripe_proceed(args); //result, $form, $oldHtml, $data, SUPER.Stripe.StripesIban[index]);
                                });
                            }else{
                                console.log('test@4');
                                // Single payment checkout
                                // Create a charge?
                                SUPER.Stripe.StripesIban[index].confirmSepaDebitPayment(result.client_secret, {
                                    payment_method: {
                                        sepa_debit: SUPER.Stripe.iban[index],
                                        billing_details: {
                                            name: 'Jenny Rosen5',
                                            email: 'jenny@example.com',
                                        },
                                    },
                                }).then(function(result) {
                                    SUPER.stripe_proceed(args); //result, $form, $oldHtml, $data, SUPER.Stripe.StripesIban[index]);
                                });
                            }

                            // result = JSON.parse(result);
                            // console.log(result);
                            // if (result.stripe_method == 'subscription') {
                            //     result.error = true;
                            //     result.msg = super_stripe_i18n.ideal_subscription_error;
                            //     SUPER.stripe_proceed(result, $form, $oldHtml);
                            //     return false;
                            // } else {
                            //     // Single payment checkout
                            //     // Redirect to Stripe iDeal payment page
                            //     SUPER.Stripe.StripesIdeal[index].confirmIdealPayment(result.client_secret, {
                            //         payment_method: {
                            //             ideal: SUPER.Stripe.ideal[index],
                            //             //billing_details: {
                            //             //    name: 'Rens Tillmann'
                            //             //}
                            //         },
                            //         return_url: result.return_url // Required for iDeal payment method
                            //     }).then(function (result) {
                            //         if (result.error) {
                            //             SUPER.stripe_proceed(result, $form, $oldHtml);
                            //             return false;
                            //         }
                            //     });

                            //     // Single payment checkout
                            //     SUPER.Stripe.StripesCc[index].confirmCardPayment(result.client_secret, {
                            //         payment_method: {
                            //             card: SUPER.Stripe.cards[index],
                            //             //billing_details: {
                            //             //    name: 'Rens Tillmann'
                            //             //}
                            //         }
                            //     }).then(function (result) {
                            //         console.log('test2');
                            //         SUPER.stripe_proceed(result, $form, $oldHtml, $data, SUPER.Stripe.StripesCc[index]);
                            //     });

                            // }

                            // result = JSON.parse(result);
                            // console.log(result);
                            // if (result.stripe_method == 'subscription') {
                            //     // Subscription checkout
                            //     // In case of subscription we must provide it with billing details
                            //     if (result.sepa_debit) {
                            //         // Because this is a subscription that is paid via iDeal we must create a source to handle Sepa Debit
                            //         console.log(SUPER.Stripe.iban[index]);
                            //         console.log(result.source.id);
                            //         SUPER.Stripe.StripesIban[index].createSource({
                            //             type: 'sepa_debit',
                            //             sepa_debit: {
                            //                 ideal: result.source.id,
                            //             },
                            //             currency: 'eur',
                            //             owner: {
                            //                 name: 'Jenny SEPA',
                            //             },
                            //         }).then(function (result) {
                            //             console.log(result);
                            //             // payment_method_not_available
                            //             // processing_error
                            //             // invalid_bank_account_iban
                            //             // invalid_owner_name

                            //             // handle result.error or result.source
                            //         });
                            //     }
                            // } else {
                            //     // Single payment checkout
                            //     // Create a charge?
                            //     SUPER.Stripe.StripesIban[index].confirmSepaDebitPayment('{PAYMENT_INTENT_CLIENT_SECRET}', {
                            //         payment_method: {
                            //           sepa_debit: ibanElement,
                            //           billing_details: {
                            //             name: 'Jenny Rosen',
                            //             email: 'jenny@example.com',
                            //           },
                            //         },
                            //       })
                            //       .then(function(result) {
                            //         // Handle result.error or result.paymentIntent
                            //       });
                            // }
                        },
                        complete: function () {
                            console.log('completed2');
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            console.log(xhr, ajaxOptions, thrownError);
                            alert('Failed to process data, please try again');
                        }
                    });
                }
            }
        });
    };


    // Handle error
    SUPER.stripe_proceed = function (args){ //result, $form, $oldHtml, $data, stripe) {
        console.log('test@5');
        console.log(args.result);
        console.log(args.data);
        if (args.result.error) {
            console.log('test@6');
            // Display error.msg in your UI.
            $('.super-msg').remove();
            var $html = '<div class="super-msg super-error">';
            $html += (typeof args.result.msg !== 'undefined' ? args.result.msg : args.result.error.message);
            $html += '<span class="super-close"></span>';
            $html += '</div>';
            $($html).prependTo($form);
            // keep loading state active
            var $proceed = SUPER.before_scrolling_to_message_hook($form, $form.offset().top - 30);
            if ($proceed === true) {
                if($form[0].closest('.super-popup-content')){
                    $($form[0].closest('.super-popup-content')).animate({
                        scrollTop: $form.offset().top - 200
                    }, 1000);
                }else{
                    $('html, body').animate({
                        scrollTop: $form.offset().top - 200
                    }, 1000);
                }
            }
            $form.find('.super-form-button.super-loading .super-button-name').html($oldHtml);
            $form.find('.super-form-button.super-loading').removeClass('super-loading');
            return false;
        } else {
            var paymentMethodId = undefined;
            console.log('test@7');
            // if (typeof result.paymentIntent !== 'undefined') {
            //     paymentMethodId = result.paymentIntent.payment_method; // When SEPA was used
            // }
            if (typeof args.result.paymentMethod !== 'undefined') {
                paymentMethodId = args.result.paymentMethod.id; // When CC was used
            }
            if (typeof paymentMethodId !== 'undefined') {
                // Create the subscriptions
                $.ajax({
                    url: super_stripe_i18n.ajaxurl,
                    type: 'post',
                    data: {
                        action: 'super_stripe_create_subscription',
                        payment_method: paymentMethodId,
                        metadata: args.result.metadata,
                        data: args.data, //$data
                    },
                    success: function (result) {
                        result = JSON.parse(result);
                        args.result = result;
                        // If an error occured
                        if (result.error) {
                            SUPER.stripe_proceed(args); //result, $form, $oldHtml, $data, stripe);
                            return false;
                        }
                        // Outcome 1: Payment succeeds
                        if ((result.subscription_status == 'active') && (result.invoice_status == 'paid') && (result.paymentintent_status == 'succeeded')) {
                            console.log('Payment succeeds');
                            // The payment has succeeded. Display a success message.
                            console.log('The payment has succeeded, show success message1.');
                            $form.data('is-doing-things', ''); // Finish form submission
                        }
                        // Outcome 2: Trial starts
                        if ((result.subscription_status == 'trialing') && (result.invoice_status == 'paid')) {
                            console.log('Trial starts');
                            $form.data('is-doing-things', ''); // Finish form submission
                        }
                        // Outcome 3: Payment fails
                        if ((result.subscription_status == 'incomplete') && (result.invoice_status == 'open') && (result.paymentintent_status == 'requires_payment_method')) {
                            console.log('Payment fails');
                            console.log(result);
                            // result.error = {message: 'The charge attempt for the subscription failed, please try with a new payment method'};
                        }
                        // Outcome 4: Requires action
                        if ((result.subscription_status == 'incomplete') && (result.invoice_status == 'open') && (result.paymentintent_status == 'requires_action')) {
                            // Notify customer that further action is required
                            args.stripe.confirmCardPayment(result.client_secret).then(function (result) {
                                if (result.error) {
                                    // Display error.msg in your UI.
                                    SUPER.stripe_proceed(args); //result, $form, $oldHtml, $data, stripe);
                                    return false;
                                } else {
                                    // The payment has succeeded. Display a success message.
                                    console.log('The payment has succeeded, show success message2.');
                                    $form.data('is-doing-things', ''); // Finish form submission
                                }
                            });
                        }
                    },
                    complete: function () {
                        console.log('completed3');
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        console.log(xhr, ajaxOptions, thrownError);
                        alert('Failed to process data, please try again');
                    }
                });
            } else {
                console.log('test@10');
                if (result.paymentIntent.status === 'succeeded' || 
                    result.paymentIntent.status === 'processing') { // `processing` in case of SEPA Debit payments
                    console.log('test@11');
                    // The payment has succeeded. Display a success message.
                    console.log('The payment has succeeded, show success message3.');
                    $form.data('is-doing-things', ''); // Finish form submission
                }
            }
        }
    };

    // Handle form submission.
    SUPER.stripe_cc_create_payment_method = function (args) { //$form, $data, $oldHtml, $response) {
        console.log('test2222');
        SUPER.Stripe.forms = document.querySelectorAll('.super-form, .super-preview-elements');
        SUPER.Stripe.forms.forEach(function (form, index) {
            if ((args.form == form) && (SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element'))) {
                console.log('match cc!');
                // Check if element exists and if not empty
                if (SUPER.Stripe.cards[index] && SUPER.Stripe.cards[index]._empty) {
                    console.log('skip card element, because it is empty');
                    return false;
                }
                // Only if element is not conditionally hidden
                var $this = $(SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element')),
                    $hidden = false,
                    $parent = $this.parents('.super-shortcode:eq(0)');
                $this.parents('.super-shortcode.super-column').each(function () {
                    if ($(this).css('display') == 'none') {
                        $hidden = true;
                    }
                });
                console.log($parent);
                if (($hidden === true) || (($parent.css('display') == 'none') && (!$parent.hasClass('super-hidden')))) {
                    // Conditionally hidden
                    console.log('test1');
                } else {
                    console.log('test2');
                    args.form.data('is-redirecting', 'true');
                    args.form.data('is-doing-things', 'true');
                    // Submit form data so that we can prepare for a payment
                    $.ajax({
                        url: super_stripe_i18n.ajaxurl,
                        type: 'post',
                        data: {
                            action: 'super_stripe_prepare_payment',
                            payment_method: 'card',
                            data: $data,
                            response: $response
                        },
                        success: function (result) {
                            result = JSON.parse(result);
                            console.log(result);
                            args.result = result;
                            args.stripe = SUPER.Stripe.StripesCc[index];
                            // Check for errors
                            if (result.error) {
                                // Display error.msg in your UI.
                                SUPER.stripe_proceed(args) //result, $form, $oldHtml, $data, SUPER.Stripe.StripesCc[index]);
                                return false;
                            }
                            // Check if this is a single payment or a subscription
                            if (result.stripe_method == 'subscription') {
                                // Subscription checkout
                                // In case of subscription we must provide it with billing details
                                var $atts = {};
                                if (result.sepa_debit) {
                                    $atts.type = result.payment_method;
                                    $atts.sepa_debit.iban = '';
                                    $atts.iban = SUPER.Stripe.iban[index];
                                } else {
                                    $atts.type = result.payment_method;
                                    $atts.card = SUPER.Stripe.cards[index];
                                }
                                var $metadata = result.metadata;
                                $atts.billing_details = {
                                   name: 'Rens Tillmann6'
                                };
                                SUPER.Stripe.StripesCc[index].createPaymentMethod($atts).then(function (result) {
                                    // It will return "paymentMethodId" which is the payment ID e.g: pm_XXXXXXX
                                    result.metadata = $metadata; // Must add metadata
                                    console.log('test1');
                                    SUPER.stripe_proceed(args); //result, $form, $oldHtml, $data, SUPER.Stripe.StripesCc[index]);
                                });
                            } else {
                                // Single payment checkout
                                SUPER.Stripe.StripesCc[index].confirmCardPayment(result.client_secret, {
                                    payment_method: {
                                        card: SUPER.Stripe.cards[index],
                                        billing_details: {
                                           name: 'Rens Tillmann7'
                                        }
                                    }
                                }).then(function (result) {
                                    console.log('test2');
                                    SUPER.stripe_proceed(args); //result, $form, $oldHtml, $data, SUPER.Stripe.StripesCc[index]);
                                });
                            }
                        },
                        complete: function () {
                            console.log('completed4');
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            console.log(xhr, ajaxOptions, thrownError);
                            alert('Failed to process data, please try again');
                        }
                    });
                }
            }
        });
    }

})(jQuery);
