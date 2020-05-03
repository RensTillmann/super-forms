/* globals jQuery, ajaxurl, Stripe, super_addons_i18n */
"use strict";
(function($) { // Hide scope, no $ conflict
    var loader = {
        html: '<svg class="super-loader" viewBox="0 0 24 24"><g transform="translate(1 1)" fill-rule="nonzero" fill="none"><circle cx="11" cy="11" r="11"></circle><path d="M10.998 22a.846.846 0 0 1 0-1.692 9.308 9.308 0 0 0 0-18.616 9.286 9.286 0 0 0-7.205 3.416.846.846 0 1 1-1.31-1.072A10.978 10.978 0 0 1 10.998 0c6.075 0 11 4.925 11 11s-4.925 11-11 11z" fill="currentColor"></path></g></svg>',
    };  
    document.querySelector('.super-activate-addon').addEventListener('click', function(){
        if(this.classList.contains('loading')) return false;
        var btn = this;
        var oldHtml = btn.innerHTML;
        btn.classList.add('loading');
        btn.innerHTML = loader.html;
        var addon = btn.dataset.slug;
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            if (this.readyState == 4) {
                // Success:
                if (this.status == 200) {
                    var response = JSON.parse(this.responseText);
                    // If the response is a valid JSON string, then we are updating the TAB element
                    // In this case we need to do a special thing which is only updating the TABS headers
                    // We will not update the inner elements, or in other words the TAB content wrappers
                    var stripe = Stripe('pk_test_1i3UyFAuxbe3Po62oX1FV47U');
                    stripe.redirectToCheckout({
                        // Make the id field from the Checkout Session creation API response
                        // available to this file, so you can provide it as parameter here
                        // instead of the {{CHECKOUT_SESSION_ID}} placeholder.
                        sessionId: response.checkoutSessionId
                    }).then(function (result) {
                        // If `redirectToCheckout` fails due to a browser or network
                        // error, display the localized error message to your customer
                        // using `result.error.message`.
                        alert(result.error.message);
                    });
                }
                // Complete:
                btn.classList.remove('loading');
                btn.innerHTML = oldHtml;
            }
        };
        xhttp.onerror = function () {
            console.log(this);
            console.log("** An error occurred during the transaction");
        };
        xhttp.open("POST", ajaxurl, true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
        console.log(window.location);
        var params = {
            action: 'super_subscribe_addon',
            addon: addon,
            url: super_addons_i18n.addons_url
            // domain: 'test.com',
            // ip: '11.22.33.44',
            // email: 'test@test.com'
        };
        params = $.param(params);
        xhttp.send(params);
    });
    // // Set your secret key. Remember to switch to your live secret key in production!
    // // See your keys here: https://dashboard.stripe.com/account/apikeys
    // \Stripe\Stripe::setApiKey('sk_test_CczNHRNSYyr4TenhiCp7Oz05');

    // $session = \Stripe\Checkout\Session::create([
    // 'payment_method_types' => ['card'],
    // 'subscription_data' => [
    //     'items' => [[
    //     'plan' => 'plan_123',
    //     ]],
    // ],
    // 'success_url' => 'https://example.com/success?session_id={CHECKOUT_SESSION_ID}',
    // 'cancel_url' => 'https://example.com/cancel',
    // ]);    
})(jQuery);