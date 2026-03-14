---
description: >-
  Below code is intended if you want to display a progress bar (or some text)
  like "Comparing the best offers for you……" inside a multi-part (form step)
  before automatically redirecting to the next step
---

# Automatically redirecting to next step after displaying text or a progress bar

The below code can (or should be) placed inside a HTML (raw) element. It will then be loaded only when the form itself is loaded. Just make sure to uncheck the "Automatically add line-breaks" so that it doesn't corrupt the JavaScript code.

Inside the below code you can change the milliseconds 5000 to something different so it shows the text longer or shorter depending on your use case. Replace the `step===3` with the step number that is displaying the text or progress bar.

This code can of course be altered for other use cases as well. For questions you can always contact support.

<pre class="language-javascript"><code class="lang-javascript"><strong>&#x3C;script>
</strong><strong>(function(){
</strong>  function hashChanged(storedHash){
    if(storedHash.indexOf('#step-')===-1) return;
    var currentStep = location.hash.substring(1);
    if(currentStep!==''){
        var form, explodedStep = currentStep.split('-');
        if(explodedStep[0]==='step' &#x26;&#x26; currentStep[4]==='-'){
            var stepFormID = explodedStep[1];
            form = document.querySelector('#super-form-'+stepFormID);
            if(form.classList.contains('super-initialized')){
                var multiPart = explodedStep[2].split(';');
                var step = Number(multiPart[0]);
                if(step===3){
                    // Redirect to step 4 after X seconds
                    setTimeout(function(){
                        debugger;
                        currentStep = 'step-'+stepFormID+'-'+(step+1);
                        window.location.hash = currentStep;
                        SUPER.switch_to_step_and_or_field(form, currentStep);
                    },5000);
                }
            }
        }
    }
  };
  if("onhashchange" in window) {
    window.onhashchange = function () {
      hashChanged(window.location.hash);
    }
  }else{
    var storedHash = window.location.hash;
    window.setInterval(function () {
      if (window.location.hash != storedHash) {
        storedHash = window.location.hash;
        hashChanged(storedHash);
      }
    }, 100);
  }
})();
&#x3C;/script>
</code></pre>
