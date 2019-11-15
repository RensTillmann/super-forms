(function($) { // Hide scope, no $ conflict
    "use strict";

    SUPER.Stripe = {};

    SUPER.Stripe.stripes = [];
    SUPER.Stripe.elements = [];
    SUPER.Stripe.cards = [];
    SUPER.Stripe.ideal = [];
    SUPER.Stripe.forms = document.querySelectorAll('.super-form, .super-create-form');

    var classes = {
        base: 'super-stripe-base',
        complete: 'super-stripe-complete',
        empty: 'super-stripe-empty',
        focus: 'super-stripe-focus',
        invalid: 'super-stripe-invalid',
        webkitAutofill: 'super-stripe-autofill'
    };
    var style = {
        base: {
            color: super_stripe_i18n.styles.color,
            iconColor: super_stripe_i18n.styles.iconColor,
            fontFamily: super_stripe_i18n.styles.fontFamily,
            fontSize: super_stripe_i18n.styles.fontSize+'px',
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

    // Initialize Stripe Elements
    SUPER.init_stripe_elements = function() {
        SUPER.Stripe.forms.forEach(function(form, index) {
            if(SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element')){
                // Check if not yet initialized
                if(!SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element').classList.contains('super-stripe-initialized')){
                    // Add initialized class
                    SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element').classList.add('super-stripe-initialized');
                    // Create an instance of Elements.
                    SUPER.Stripe.stripes[index] = Stripe(super_stripe_i18n.stripe_pk);
                    SUPER.Stripe.elements[index] = SUPER.Stripe.stripes[index].elements();
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
            if(SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element')){
                // Check if not yet initialized
                if(!SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element').classList.contains('super-stripe-initialized')){
                    // Add initialized class
                    SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element').classList.add('super-stripe-initialized');
                    // Create an instance of Elements.
                    SUPER.Stripe.stripes[index] = Stripe(super_stripe_i18n.stripe_pk);
                    SUPER.Stripe.elements[index] = SUPER.Stripe.stripes[index].elements();
                    SUPER.Stripe.cards[index] = SUPER.Stripe.elements[index].create('card', {
                        classes: classes,
                        style: style,
                        hidePostalCode: true, // Hide the postal code field. Default is false. If you are already collecting a full billing address or postal code elsewhere, set this to true.
                        iconStyle: 'solid', // Appearance of the icon in the Element. Either 'solid' or 'default'.
                        hideIcon: false // Hides the icon in the Element. Default is false.
                    });
                    SUPER.Stripe.cards[index].mount(SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element'));
                    SUPER.Stripe.cards[index].addEventListener('change', function(event) {
                        var $parent = $(SUPER.Stripe.cards[index]._parent).parents('.super-field:eq(0)');
                        if (event.error) {
                            if($parent.children('p').length===0) {
                                $('<p style="display:none;">' + event.error.message + '</p>').appendTo($parent);
                            }
                            $parent.addClass('error-active');
                            $parent.children('p').fadeIn(500);
                        }else{
                            $parent.removeClass('error-active');
                            $parent.children('p').fadeOut(500, function() {
                                $(this).remove();
                            });
                        }
                    });
                }
            }
        });
    };

    // Handle form submission.
    SUPER.stripe_ideal_create_payment_method = function($form, $data) {
        console.log('test1111');
        SUPER.Stripe.forms.forEach(function(form, index) {
            if( ($form[0] == form) && (SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element')) ) {
                console.log('match ideal!');
                // Only if element is not conditionally hidden
                var $this = $(SUPER.Stripe.forms[index].querySelector('.super-stripe-ideal-element')),
                    $hidden = false,
                    $parent = $this.parents('.super-shortcode:eq(0)');
                $this.parents('.super-shortcode.super-column').each(function() {
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
                    $form.data('is-redirecting', 'true');
                    // Make payment intent
                    $.ajax({
                        url: super_stripe_i18n.ajaxurl,
                        type: 'post',
                        data: {
                            action: 'super_stripe_payment_intent',
                            ideal: true,
                            data: $data
                        },
                        success: function(result) {
                            result = JSON.parse(result);
                            console.log(result.client_secret);
                            console.log(SUPER.Stripe.stripes);
                            console.log(SUPER.Stripe.cards);
                            console.log(SUPER.Stripe.ideal);
                            // Redirect to Stripe iDeal payment page
                            SUPER.Stripe.stripes[index].confirmIdealPayment(result.client_secret, {
                                payment_method: {
                                    ideal: SUPER.Stripe.ideal[index],
                                },
                                return_url: result.return_url //'http://f4d.nl/dev/checkout/complete',
                            });
                        },
                        complete: function() {
                            console.log('completed');
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            console.log(xhr, ajaxOptions, thrownError);
                            alert('Failed to process data, please try again');
                        }
                    });
                }
            }
        });

    };

    // Handle form submission.
    SUPER.stripe_cc_create_payment_method = function($event, $form, $data, $old_html, callback) {
        console.log('test2222');
        SUPER.Stripe.forms.forEach(function(form, index) {
            if( ($form[0] == form) && (SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element')) ) {
                console.log('match cc!');
                // Only if element is not conditionally hidden
                var $this = $(SUPER.Stripe.forms[index].querySelector('.super-stripe-cc-element')),
                    $hidden = false,
                    $parent = $this.parents('.super-shortcode:eq(0)');

                console.log($this);

                $this.parents('.super-shortcode.super-column').each(function() {
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
                    $.ajax({
                        url: super_stripe_i18n.ajaxurl,
                        type: 'post',
                        data: {
                            action: 'super_stripe_payment_intent',
                            data: $data
                        },
                        success: function(result) {
                            result = JSON.parse(result);
                            console.log(result.client_secret);
                            console.log(SUPER.Stripe.stripes);
                            console.log(SUPER.Stripe.cards);
                            SUPER.Stripe.stripes[index].confirmCardPayment(result.client_secret, {
                                payment_method: {
                                    card: SUPER.Stripe.cards[index],
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
                                    if ($proceed === true) {
                                        $('html, body').animate({
                                            scrollTop: $form.offset().top - 200
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
                        complete: function() {
                            console.log('completed');
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            console.log(xhr, ajaxOptions, thrownError);
                            alert('Failed to process data, please try again');
                        }
                    });
                }
            }
        });
    }

})(jQuery);