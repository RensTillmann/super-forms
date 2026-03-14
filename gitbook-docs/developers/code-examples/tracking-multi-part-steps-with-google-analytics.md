---
description: Tracking WordPress Multi-part steps form with Google Analytics
---

# Tracking Multi-part steps with Google Analytics

```javascript
(function(){
  function hashChanged(storedHash){
    // @@ EDIT BELOW VARIABLES @@
    var library = 'gtag.js'; // Google Tag Manager (gtag.js)
    //var library = 'analytics.js'; // Universal analytics (analytics.js)
    //var library = 'ga.js'; // Legacy analytics (ga.js)
    var UA = 'UA-XXXXX-X'; // Required when using ga.js or analytics.js
    // @@ STOP EDITING @@

    // If has contains step
    if(storedHash.indexOf('#step-')===-1){
        // When no hash starting with `#step-` was found we cancel
        return;
    }
    
    // Grab the current page including current multi-part step from the URL
    var path = location.pathname + location.search + location.hash;
    if(library==='gtag.js'){
      if(typeof gtag === 'undefined') return;
      gtag('event', 'page_view', {
          page_title: document.title, // e.g: `Page title`
          page_location: location.href, // e.g: `https://domain.com/page`
          page_path: path // e.g: `https://domain.com/page/#step-12345-2` (Form ID 12345 and currently at step 2)
      });
      return;
    }
    if(library==='analytics.js'){
      if(typeof ga === 'undefined') return;
      ga('send', {
        hitType: 'pageview',
        page: path
      });
      return;
    }
    if(library==='ga.js'){
      if(typeof _gaq === 'undefined') return;
      // New asynchronous tracking code:
      _gaq.push(['_setAccount', UA]);
      _gaq.push(['_trackPageview', path])
      return;
    }
  };
  // Google Analytics track multi-part
  if("onhashchange" in window) { // event supported?
    window.onhashchange = function () {
      hashChanged(window.location.hash);
    }
  } else { // event not supported:
    var storedHash = window.location.hash;
    window.setInterval(function () {
      if (window.location.hash != storedHash) {
        storedHash = window.location.hash;
        hashChanged(storedHash);
      }
    }, 100);
  }
})();
```
