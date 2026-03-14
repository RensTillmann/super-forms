---
description: >-
  How to pass information to tags with the use of the data layer object. This
  allows you to pass events or variables. It also allows you to setup triggers
  based the values of variables.
---

# Tracking Multi-part steps with GTM data layer (dataLayer.push)

{% hint style="info" %}
Make sure to also read the Google Data Layer documentation if you haven't already: [https://developers.google.com/tag-platform/tag-manager/datalayer](https://developers.google.com/tag-platform/tag-manager/datalayer)
{% endhint %}

{% hint style="warning" %}
The below JavaScript code is just an example, you will need to make changes accordingly based on your use case. Place this code in the footer of your site.
{% endhint %}

<pre class="language-javascript"><code class="lang-javascript"><strong>(function(){
</strong>    function hashChanged(storedHash){
        // If hash contains step
        if(storedHash.indexOf('#step-')===-1){
            // When no hash starting with `#step-` was found we cancel
            return;
        }
        // Track field value
        var brandFieldName = 'brand';
        var brandFieldValue = (document.querySelector('input[name="'+brandFieldName+'"') ? document.querySelector('input[name="'+brandFieldName+'"').value : 'Field not found!');
        // Grab current step number
        var step = '1', stepFormID, multiPart, step, explodedStep = currentStep.split('-');
        if(explodedStep[0]==='step'){
            stepFormID = explodedStep[1];
            multiPart = explodedStep[2].split(';');
            step = multiPart[0];
        }
        // Grab the current page including current multi-part step from the URL
        var path = location.pathname + location.search + location.hash;
        if(typeof dataLayer === 'undefined') return;
        dataLayer.push({
            event: 'step_'+step+'_complete',
            brand: brandFieldValue,
            form_step: step,
            page_title: document.title, // e.g: `Page title`
            page_location: location.href, // e.g: `https://domain.com/page`
            page_path: path // e.g: `https://domain.com/page/#step-12345-2` (Form ID 12345 and currently at step 2)
        });
        return;
    };
    // Track multi-part
    if('onhashchange' in window) { // event supported?
        window.onhashchange = function () {
            hashChanged(window.location.hash);
        }
    }else{ // event not supported:
        var storedHash = window.location.hash;
        window.setInterval(function () {
            if(window.location.hash!=storedHash){
                storedHash = window.location.hash;
                hashChanged(storedHash);
            }
        }, 100);
    }
})();
</code></pre>
