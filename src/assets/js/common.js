/* globals jQuery, SUPER, grecaptcha, super_common_i18n, ajaxurl, IBAN, tinyMCE, google, quicktags */
"use strict";

// polyfill remove() ie9 and above
(function (arr) { arr.forEach(function (item) { if (item.hasOwnProperty('remove')) { return; } Object.defineProperty(item, 'remove', { configurable: true, enumerable: true, writable: true, value: function remove() { this.parentNode.removeChild(this); } }); }); })([Element.prototype, CharacterData.prototype, DocumentType.prototype]);
// polyfill es6 promises (auto)
!function(t,e){"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):t.ES6Promise=e()}(this,function(){"use strict";function t(t){var e=typeof t;return null!==t&&("object"===e||"function"===e)}function e(t){return"function"==typeof t}function n(t){W=t}function r(t){z=t}function o(){return function(){return process.nextTick(a)}}function i(){return"undefined"!=typeof U?function(){U(a)}:c()}function s(){var t=0,e=new H(a),n=document.createTextNode("");return e.observe(n,{characterData:!0}),function(){n.data=t=++t%2}}function u(){var t=new MessageChannel;return t.port1.onmessage=a,function(){return t.port2.postMessage(0)}}function c(){var t=setTimeout;return function(){return t(a,1)}}function a(){for(var t=0;t<N;t+=2){var e=Q[t],n=Q[t+1];e(n),Q[t]=void 0,Q[t+1]=void 0}N=0}function f(){try{var t=Function("return this")().require("vertx");return U=t.runOnLoop||t.runOnContext,i()}catch(e){return c()}}function l(t,e){var n=this,r=new this.constructor(p);void 0===r[V]&&x(r);var o=n._state;if(o){var i=arguments[o-1];z(function(){return T(o,r,i,n._result)})}else j(n,r,t,e);return r}function h(t){var e=this;if(t&&"object"==typeof t&&t.constructor===e)return t;var n=new e(p);return w(n,t),n}function p(){}function v(){return new TypeError("You cannot resolve a promise with itself")}function d(){return new TypeError("A promises callback cannot return that same promise.")}function _(t,e,n,r){try{t.call(e,n,r)}catch(o){return o}}function y(t,e,n){z(function(t){var r=!1,o=_(n,e,function(n){r||(r=!0,e!==n?w(t,n):A(t,n))},function(e){r||(r=!0,S(t,e))},"Settle: "+(t._label||" unknown promise"));!r&&o&&(r=!0,S(t,o))},t)}function m(t,e){e._state===Z?A(t,e._result):e._state===$?S(t,e._result):j(e,void 0,function(e){return w(t,e)},function(e){return S(t,e)})}function b(t,n,r){n.constructor===t.constructor&&r===l&&n.constructor.resolve===h?m(t,n):void 0===r?A(t,n):e(r)?y(t,n,r):A(t,n)}function w(e,n){if(e===n)S(e,v());else if(t(n)){var r=void 0;try{r=n.then}catch(o){return void S(e,o)}b(e,n,r)}else A(e,n)}function g(t){t._onerror&&t._onerror(t._result),E(t)}function A(t,e){t._state===X&&(t._result=e,t._state=Z,0!==t._subscribers.length&&z(E,t))}function S(t,e){t._state===X&&(t._state=$,t._result=e,z(g,t))}function j(t,e,n,r){var o=t._subscribers,i=o.length;t._onerror=null,o[i]=e,o[i+Z]=n,o[i+$]=r,0===i&&t._state&&z(E,t)}function E(t){var e=t._subscribers,n=t._state;if(0!==e.length){for(var r=void 0,o=void 0,i=t._result,s=0;s<e.length;s+=3)r=e[s],o=e[s+n],r?T(n,r,o,i):o(i);t._subscribers.length=0}}function T(t,n,r,o){var i=e(r),s=void 0,u=void 0,c=!0;if(i){try{s=r(o)}catch(a){c=!1,u=a}if(n===s)return void S(n,d())}else s=o;n._state!==X||(i&&c?w(n,s):c===!1?S(n,u):t===Z?A(n,s):t===$&&S(n,s))}function M(t,e){try{e(function(e){w(t,e)},function(e){S(t,e)})}catch(n){S(t,n)}}function P(){return tt++}function x(t){t[V]=tt++,t._state=void 0,t._result=void 0,t._subscribers=[]}function C(){return new Error("Array Methods must be provided an Array")}function O(t){return new et(this,t).promise}function k(t){var e=this;return new e(L(t)?function(n,r){for(var o=t.length,i=0;i<o;i++)e.resolve(t[i]).then(n,r)}:function(t,e){return e(new TypeError("You must pass an array to race."))})}function F(t){var e=this,n=new e(p);return S(n,t),n}function Y(){throw new TypeError("You must pass a resolver function as the first argument to the promise constructor")}function q(){throw new TypeError("Failed to construct 'Promise': Please use the 'new' operator, this object constructor cannot be called as a function.")}function D(){var t=void 0;if("undefined"!=typeof global)t=global;else if("undefined"!=typeof self)t=self;else try{t=Function("return this")()}catch(e){throw new Error("polyfill failed because global object is unavailable in this environment")}var n=t.Promise;if(n){var r=null;try{r=Object.prototype.toString.call(n.resolve())}catch(e){}if("[object Promise]"===r&&!n.cast)return}t.Promise=nt}var K=void 0;K=Array.isArray?Array.isArray:function(t){return"[object Array]"===Object.prototype.toString.call(t)};var L=K,N=0,U=void 0,W=void 0,z=function(t,e){Q[N]=t,Q[N+1]=e,N+=2,2===N&&(W?W(a):R())},B="undefined"!=typeof window?window:void 0,G=B||{},H=G.MutationObserver||G.WebKitMutationObserver,I="undefined"==typeof self&&"undefined"!=typeof process&&"[object process]"==={}.toString.call(process),J="undefined"!=typeof Uint8ClampedArray&&"undefined"!=typeof importScripts&&"undefined"!=typeof MessageChannel,Q=new Array(1e3),R=void 0;R=I?o():H?s():J?u():void 0===B&&"function"==typeof require?f():c();var V=Math.random().toString(36).substring(2),X=void 0,Z=1,$=2,tt=0,et=function(){function t(t,e){this._instanceConstructor=t,this.promise=new t(p),this.promise[V]||x(this.promise),L(e)?(this.length=e.length,this._remaining=e.length,this._result=new Array(this.length),0===this.length?A(this.promise,this._result):(this.length=this.length||0,this._enumerate(e),0===this._remaining&&A(this.promise,this._result))):S(this.promise,C())}return t.prototype._enumerate=function(t){for(var e=0;this._state===X&&e<t.length;e++)this._eachEntry(t[e],e)},t.prototype._eachEntry=function(t,e){var n=this._instanceConstructor,r=n.resolve;if(r===h){var o=void 0,i=void 0,s=!1;try{o=t.then}catch(u){s=!0,i=u}if(o===l&&t._state!==X)this._settledAt(t._state,e,t._result);else if("function"!=typeof o)this._remaining--,this._result[e]=t;else if(n===nt){var c=new n(p);s?S(c,i):b(c,t,o),this._willSettleAt(c,e)}else this._willSettleAt(new n(function(e){return e(t)}),e)}else this._willSettleAt(r(t),e)},t.prototype._settledAt=function(t,e,n){var r=this.promise;r._state===X&&(this._remaining--,t===$?S(r,n):this._result[e]=n),0===this._remaining&&A(r,this._result)},t.prototype._willSettleAt=function(t,e){var n=this;j(t,void 0,function(t){return n._settledAt(Z,e,t)},function(t){return n._settledAt($,e,t)})},t}(),nt=function(){function t(e){this[V]=P(),this._result=this._state=void 0,this._subscribers=[],p!==e&&("function"!=typeof e&&Y(),this instanceof t?M(this,e):q())}return t.prototype["catch"]=function(t){return this.then(null,t)},t.prototype["finally"]=function(t){var n=this,r=n.constructor;return e(t)?n.then(function(e){return r.resolve(t()).then(function(){return e})},function(e){return r.resolve(t()).then(function(){throw e})}):n.then(t,t)},t}();return nt.prototype.then=l,nt.all=O,nt.race=k,nt.resolve=h,nt.reject=F,nt._setScheduler=n,nt._setAsap=r,nt._asap=z,nt.polyfill=D,nt.Promise=nt,nt.polyfill(),nt});

// polyfill for 'closest()' to support IE9+
// reference: https://developer.mozilla.org/en-US/docs/Web/API/Element/closest
if (!Element.prototype.matches) {
  Element.prototype.matches = Element.prototype.msMatchesSelector || 
                              Element.prototype.webkitMatchesSelector;
}
if (!Element.prototype.closest) {
  Element.prototype.closest = function(s) {
    var el = this;

    do {
      if (el.matches(s)) return el;
      el = el.parentElement || el.parentNode;
    } while (el !== null && el.nodeType === 1);
    return null;
  };
}

if(typeof window.SUPER === 'undefined'){
    window.SUPER = {};
}
SUPER.tagsRegex = /{([^\\\/\s"'+]*?)}/g;
if(typeof SUPER.cachedFields === 'undefined'){
    SUPER.cachedFields = {};
}
if(typeof SUPER.cachedConditionalLogicByFieldName==='undefined') {
	SUPER.cachedConditionalLogicByFieldName = {};
}
if(typeof SUPER.cachedFormIds === 'undefined'){
    SUPER.cachedFormIds = {};
}
SUPER.clearFormCache = function(){
    SUPER.cachedFields = {};
    SUPER.cachedConditionalLogicByFieldName = {};
    SUPER.cachedFormIds = {};
};
SUPER.getFormIdByAttributeID = function(form){
    if(SUPER.cachedFormIds[form.id]){
        return SUPER.cachedFormIds[form.id];
    }else{
        if(form.id){
            var formId = parseInt(form.id.replace('super-form-', ''), 10);
            SUPER.cachedFormIds[form.id] = formId;
            return formId;
        }else{
            // Check if multi-part element
            // Check if duplicated column
            if(form.classList.contains('super-multipart') || form.classList.contains('super-duplicate-column-fields')){
                // Find form ID based on cloned column
                if(form.closest('.super-form')){
                    return parseInt(form.closest('.super-form').id.replace('super-form-', ''), 10);
                }
            }
        }
    }
};
SUPER.calculate_distance_speed = 1000;
SUPER.formFullyLoaded = {
    values: {}, 
    count: {}, 
    timerFunction: function(){
        var i, nodes = document.querySelectorAll('.super-form:not(.super-form-loaded)');
        if (nodes.length === 0) {
            clearInterval(SUPER.formFullyLoaded.timer);
            SUPER.formFullyLoaded.timer = null; // Mark timer as stopped
            return;
        }
        for (i = 0; i < nodes.length; i++) {
            var sfuid = nodes[i].dataset.sfuid;
            if (nodes[i].classList.contains('super-rendered') && !SUPER.after_field_change_blur_timeout[sfuid]) {
                // Form fully loaded
                var formId = parseInt(nodes[i].id.replace('super-form-', ''), 10);
                SUPER.after_form_fully_loaded({ form: nodes[i], formId: formId, sfuid: sfuid });
                continue;
            }
            if (!SUPER.formFullyLoaded.values[sfuid]) SUPER.formFullyLoaded.values[sfuid] = null;
            if (!SUPER.formFullyLoaded.count[sfuid]) SUPER.formFullyLoaded.count[sfuid] = 0;
            if (SUPER.after_field_change_blur_timeout[sfuid] && SUPER.formFullyLoaded.values[sfuid] === SUPER.after_field_change_blur_timeout[sfuid]) {
                SUPER.formFullyLoaded.count[sfuid]++;
            }
            if (SUPER.formFullyLoaded.count[sfuid] >= 3) {
                // Form fully loaded
                var formId = parseInt(nodes[i].id.replace('super-form-', ''), 10);
                SUPER.after_form_fully_loaded({ form: nodes[i], formId: formId, sfuid: sfuid });
                continue;
            }
            // Update prev values for comparing
            if (SUPER.after_field_change_blur_timeout[sfuid]) {
                SUPER.formFullyLoaded.values[sfuid] = SUPER.after_field_change_blur_timeout[sfuid];
            }
        }
    }
};
// Start check on page load
SUPER.formFullyLoaded.timer = setInterval(SUPER.formFullyLoaded.timerFunction, 100);


SUPER.after_form_fully_loaded = function(args){
    // Mark as loaded
    args.form.classList.add('super-form-loaded');
    if(args.form.parentNode.closest('.super-live-preview') || args.form.classList.contains('preload-disabled')){
        if(!args.form.classList.contains('preload-disabled')){
            setTimeout(function (){
                jQuery(args.form).fadeOut(100, function () {
                    args.form.classList.add('super-initialized');
                    jQuery(args.form).fadeIn(500, function(){
                        SUPER.switch_to_step_and_or_field(args.form);
                        console.log('Form fully loaded:', args);
                        args.form.classList.add('super-form-ready');
                        SUPER.after_form_ready.hook(args);
                    });
                });
            }, 500);
            return;
        }
        if(!args.form.classList.contains('super-initialized')) {
            args.form.classList.add('super-initialized');
            SUPER.switch_to_step_and_or_field(args.form);
            console.log('Form fully loaded:', args);
            args.form.classList.add('super-form-ready');
            SUPER.after_form_ready.hook(args);
        }
        return;
    }
    if (!args.form.classList.contains('super-initialized')) {
        setTimeout(function (){
            jQuery(args.form).fadeOut(100, function () {
                args.form.classList.add('super-initialized');
                if(SUPER.switched_language===false && args.form.querySelector('.super-i18n-switcher')){
                    // Check if we need to set the i18n data for this form based history, in case user went back via browser back button
                    var $i18n = sessionStorage.getItem('sf_'+formId+'_i18n');
                    if(args.form.querySelector('.super-i18n-switcher li[data-value="'+$i18n+'"]')){
                        SUPER.switched_language = true;
                        args.form.dataset.i18n = $i18n;
                        args.form.querySelector('.super-i18n-switcher li[data-value="'+$i18n+'"]').click();
                        jQuery(args.form).fadeIn(500, function(){
                            SUPER.switch_to_step_and_or_field(args.form);
                            console.log('Form fully loaded:', args);
                            args.form.classList.add('super-form-ready');
                            SUPER.after_form_ready.hook(args);
                        });
                        return;
                    }
                }
                jQuery(args.form).fadeIn(500, function(){
                    SUPER.switch_to_step_and_or_field(args.form);
                    console.log('Form fully loaded:', args);
                    args.form.classList.add('super-form-ready');
                    SUPER.after_form_ready.hook(args);
                });
            });
        }, 500);
    }
};
SUPER.after_form_ready = {
	functions: ['some_function_name','another_one_here'],
	hook: function(args){
		var f = super_common_i18n.dynamic_functions.after_form_ready;
		jQuery.each(f, function(key, value){
			if(typeof SUPER[value.name] !== 'undefined') SUPER[value.name](args);
		});
		f = SUPER.after_form_ready.functions;
		for(var i=0; i<f.length; i++){
			if(typeof SUPER[f[i]]!=='undefined') SUPER[f[i]](args);
		}
   	},
};

// Example code to hook into `after_form_ready_hook`:
//  (function($) {
//      SUPER.after_form_ready.functions.push('generate_pdf_on_page_load');
//      SUPER.generate_pdf_on_page_load = function(args){
//      	console.log('generate_pdf_on_page_load()');
//      	var formIds = [123, 124, 125]; // Only make snapshot for these form ID's
//      	var nodeId = parseInt(args.form.id.replace('super-form-', ''), 10);
//      	if(formIds.includes(nodeId)){
//      		if(typeof html2canvas === 'function'){
//      			html2canvas(args.form).then(function(canvas){
//      				var imgData = canvas.toDataURL('image/png');
//      				SUPER.prepare_form_data($(args.form), function(formData){
//      					$.ajax({
//      						url: ajaxurl,
//      						type: 'POST',
//      						data: {
//      							action: 'f4d_send_canvas_email',
//      							formData: formData,
//      							image: imgData
//      						},
//      						success: function (response) {
//      							console.log(response.data ? response.data : 'Screenshot sent successfully!');
//      						},
//      						error: function (xhr) {
//      							console.log('Error sending screenshot: ' + xhr.responseJSON.data);
//      						},
//      					});
//      				});
//      			});
//      		}
//      	};
//      	
//      };
//  })(jQuery);

SUPER.switched_language = false;

SUPER.round = function(number, decimals){
    if(typeof decimals === 'undefined') decimals = 0;
    return Number(Math.round(number + "e" + decimals) + "e-" + decimals);
};

SUPER.files = [];

// reCaptcha
SUPER.reCaptchaScriptLoaded = false;
SUPER.reCaptchaverifyCallback = function($response, $version, $element){
    // Set data attribute on recaptcha containing response so we can verify this upon form submission
    $element.attr('data-response', $response);
};
SUPER.add_error_status_parent_layout_element = function($, el){
    var index;
    // Add error class to Multi-part
    index = $(el).parents('.super-multipart:eq(0)').index('.super-form form .super-multipart');
    if(el.closest('.super-form') && el.closest('.super-form').querySelectorAll('.super-multipart-step')[index]){
        el.closest('.super-form').querySelectorAll('.super-multipart-step')[index].classList.add('super-error');
    }
    // Add error class to TABS
    if(el.closest('.super-tabs')){
        index = $(el.closest('.super-tabs-content')).index();
        if(el.closest('.super-tabs').querySelectorAll('.super-tabs-tab')[index]){
            el.closest('.super-tabs').querySelectorAll('.super-tabs-tab')[index].classList.add('super-error');
        }
    }
    // Add error class to Accordion
    if(el.closest('.super-accordion-item')){
        el.closest('.super-accordion-item').classList.add('super-error');
    }
};
SUPER.remove_error_status_parent_layout_element = function($, el){
    var index;
    if( el.closest('.super-multipart') && !el.closest('.super-multipart').querySelector('.super-error-active')){
        index = $(el).parents('.super-multipart:eq(0)').index('.super-form form .super-multipart');
        if(el.closest('.super-form') && el.closest('.super-form').querySelectorAll('.super-multipart-step')[index]){
            el.closest('.super-form').querySelectorAll('.super-multipart-step')[index].classList.remove('super-error');
        }
    }
    // Remove error class from TABS
    if( el.closest('.super-tabs-content') && !el.closest('.super-tabs-content').querySelector('.super-error-active')){
        index = $(el.closest('.super-tabs-content')).index();
        if(el.closest('.super-tabs') && el.closest('.super-tabs').querySelectorAll('.super-tabs-tab')[index]){
            el.closest('.super-tabs').querySelectorAll('.super-tabs-tab')[index].classList.remove('super-error');
        }
    }
    // Remove error class from Accordion if no more errors where found
    if( el.closest('.super-accordion-item') && !el.closest('.super-accordion-item').querySelector('.super-error-active')){
        el.closest('.super-accordion-item').classList.remove('super-error');
    }
};
function SUPERreCaptchaRender(){
    var $ = jQuery;
    $('.super-shortcode.super-field.super-recaptcha:not(.super-rendered)').each(function(){
        var $this = $(this);
        var $element = $this.find('.super-recaptcha');
        var $form = $this.closest('.super-form');
        var $form_id = $form.find('input[name="hidden_form_id"]').val();
        $element.attr('data-form',$form_id);
        $element.attr('id','super-recaptcha-'+$form_id);
        if($form.length===0){
            $this.html('<i>reCAPTCHA will only be generated and visible in the Preview or Front-end</i>');  
        }
        if($this.data('sitekey')===''){
            $this.html('<i>reCAPTCHA API key and secret are empty, please navigate to:<br />Super Forms > Settings > Form Settings and fill out your reCAPTCHA API key and secret</i>');  
        }else{
            if(typeof $form_id !== 'undefined'){
                var checkExist = setInterval(function() {
                    if( (typeof grecaptcha !== 'undefined') && (typeof grecaptcha.render !== 'undefined') ) {
                        clearInterval(checkExist);
                        $this.addClass('super-rendered');
                        try {
                            grecaptcha.render('super-recaptcha-'+$form_id, {
                                sitekey : $element.data('sitekey'),
                                theme : 'light',
                                callback : function(token) {
                                    SUPER.reCaptchaverifyCallback(token, 'v2', $element);
                                }
                            });
                        }
                        catch(error) {
                            // continue regardless of error
                        }
                    }
                }, 100);
            }
        }
    });
}
function SUPERreCaptcha(){
    var $ = jQuery;
    // Load recaptcha api manually if theme uses ajax requests
    if($('.super-shortcode.super-field.super-recaptcha:not(.super-rendered)').length){
        if( (typeof grecaptcha === 'undefined') || (typeof grecaptcha.render === 'undefined') ) {
            if(!SUPER.reCaptchaScriptLoaded){
                $.getScript( 'https://www.google.com/recaptcha/api.js?onload=SUPERreCaptcha&render=explicit', function() {
                    SUPER.reCaptchaScriptLoaded = true;
                    SUPERreCaptchaRender();
                });
            }
        }else{
            if(!SUPER.reCaptchaScriptLoaded){
                SUPER.reCaptchaScriptLoaded = true;
                SUPERreCaptchaRender();
            }
        }
    }
}

(function($) {

    if(typeof super_common_i18n.ajaxurl === 'undefined'){
        super_common_i18n.ajaxurl = ajaxurl;
    }

    SUPER.debug_time = function($name){
        // eslint-disable-next-line no-console
        console.time($name);
    };
    SUPER.debug_time_end = function($name){
        // eslint-disable-next-line no-console
        console.timeEnd($name);
    };
    SUPER.debug = function($log){
        // eslint-disable-next-line no-console
        console.log($log);
    };

    // Submit the form
    SUPER.complete_submit = function(args){
        // If form has g-recaptcha element
        if(($(args.form).find('.g-recaptcha').length!=0) && (typeof grecaptcha !== 'undefined')) {
            grecaptcha.ready(function(){
                grecaptcha.execute($(args.form).find('.g-recaptcha .super-recaptcha').attr('data-sitekey'), {action: 'super_form_submit'}).then(function(token){
                    args.token = token;
                    SUPER.create_ajax_request(args);
                });
            });
        }else{
            SUPER.create_ajax_request(args);
        }
    };
    // Send form submission through ajax request
    SUPER.create_ajax_request = function(args){
        var json_data;
        args.form = $(args.form);
        args.form0 = args.form[0];
        args.showOverlay = args.form0.dataset.overlay;
        // already defined: args.form_id
        // already defined: args.entry_id
        // already defined: args.list_id

        // @since 1.3
        args.data = SUPER.after_form_data_collected_hook(args.data, args.form0);

        // @since 3.2.0 - honeypot captcha check, if value is not empty cancel form submission
        args.data.super_hp = args.form.find('input[name="super_hp"]').val();
        if(args.data.super_hp!==''){
            return false;
        }

        // @since 2.9.0 - json data POST
        json_data = JSON.stringify(args.data);
        args.form.find('textarea[name="json_data"]').val(json_data);

        if(typeof args.token === 'undefined'){
            if(args.form.find('.super-recaptcha:not(.g-recaptcha)').length!==0){
                args.version = 'v2';
                args.token = args.form.find('.super-recaptcha:not(.g-recaptcha) .super-recaptcha').attr('data-response');
            }
        }else{
            args.version = 'v3';
        }
        // Create loader overlay
        args = SUPER.createLoadingOverlay(args);
        args.callback = function(){
            SUPER.submit_form(args);
        };
        SUPER.before_email_send_hook(args);
    };
    SUPER.createLoadingOverlay = function(args){
        args.loadingOverlay = document.createElement('div');
        var html = '';
        html += '<div class="super-loading-wrapper">';
            html += '<div class="super-close"></div>';
            html += '<div class="super-loading-text">';
                html += '<div class="super-custom-el1"></div>';
                html += '<div class="super-inner-text"></div>';
                html += '<div class="super-progress">';
                    html += '<div class="super-progress-bar"></div>';
                html += '</div>';
                html += '<div class="super-custom-el2"></div>';
            html += '</div>';
        html += '</div>';
        args.loadingOverlay.innerHTML = html;
        args.loadingOverlay.classList.add('super-loading-overlay');
        args.loadingOverlay.classList.add('super-loading-overlay-'+args.form_id);
        if(args.custom_msg && args.custom_msg!==''){
            args.loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+args.custom_msg+'</span>';
        }else{
            args.loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+super_common_i18n.loadingOverlay.processing+'</span>';
        }
        args.loadingOverlay.querySelector('.super-close').innerHTML = '<span>'+super_common_i18n.loadingOverlay.close+'</span>';
        if(args.showOverlay==="true"){
            document.body.appendChild(args.loadingOverlay);
        }
        // Close modal (should also reset pdf generation)
        var closeBtn = args.loadingOverlay.querySelector('.super-close');
        if(closeBtn){
            closeBtn.addEventListener('click', function(){
                // Close overlay
                SUPER.close_loading_overlay(args.loadingOverlay);
            });
        }
        args.progressBar = document.querySelector('.super-loading-overlay .super-progress-bar');
        return args;
    };
    SUPER.submit_form = function(args){
        var total = 0;
        args.files = SUPER.files[args.form_id];
        if(args.files){
            Object.keys(args.files).forEach(function() {
                total++;
                return true;
            });
        }
        if(total>0){
            args.loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+super_common_i18n.loadingOverlay.uploading_files+'</span>';
            SUPER.upload_files(args, function(args){
                args.fileUpload = true;
                SUPER.process_form_data(args);
            });
        }else{
            SUPER.process_form_data(args);
        }
    };
    SUPER.get_file_name_and_extension = function(fileName){
        var m, n, e, regex = /(^.*)\.([^.]+)$/;
        if ((m = regex.exec(fileName)) !== null) {
            n = (m[1] ? m[1] : '');
            e = (m[2] ? m[2] : '');
        }
        return {name: n, ext: e};
    };
    SUPER.get_single_uploaded_file_html = function(withoutHeader, uploaded, fileName, fileType, fileUrl){
        var html = '',
            classes = '';
        if(typeof fileType==='undefined') {
            var f = SUPER.get_file_name_and_extension(fileName);
            if(f.ext==='jpg'  || f.ext==='jpeg') fileType = 'image/jpeg';
            if(f.ext==='png'  || f.ext==='gif' || f.ext==='webp' || f.ext==='bmp') fileType = 'image/'+f.ext;
            if(f.ext==='tiff' || f.ext==='tif') fileType = 'image/tiff';
            if(f.ext==='svg') fileType = 'svg+xml';
        }
        if(uploaded) classes = ' class="super-uploaded"';
        if(withoutHeader) {
            // We do not want a header
        }else{
            html += '<div data-name="'+fileName+'" title="'+fileName+'" data-type="'+fileType+'"'+classes+'" data-url="'+fileUrl+'">';
        }
        if(fileType && fileType.indexOf("image/") === 0){
            html += '<span class="super-fileupload-image super-file-type-'+fileType.replace('/','-')+'">';
                html += '<img src="'+fileUrl+'" />';
            html += '</span>';
        }else{
            html += '<span class="super-fileupload-document super-file-type-'+fileType.replace('/','-')+'"></span>';
        }
        html += '<span class="super-fileupload-info">';
            // Truncate file if it's too long
            var f = SUPER.get_file_name_and_extension(fileName);
            if(uploaded){
                html += '<a href="'+fileUrl+'" target="_blank">'+f.name+'.'+f.ext+'</a>';
                html += '<span class="super-fileupload-delete"></span>';
            }else{
                html += '<span class="super-fileupload-name">'+f.name+'.'+f.ext+'</span>';
                html += '<span class="super-fileupload-delete"></span>';
            }
        html += '</span>';
        if(withoutHeader) {
            // We do not want a header
        }else{
            html += '</div>';
        }
        return html;
    };
    // Upload files
    SUPER.upload_files = function(args, callback){
        args._process_form_data_callback = callback;
        args.formData = new FormData();
        var x = 0, y = 0;
        Object.keys(args.files).forEach(function(key) {
            for( y = 0; y < args.files[key].length; y++){
                x++;
                args.formData.append('files['+key+']['+y+']', args.files[key][y]); // holds: file, src, name, size, type
            }
        });
        if(x===0){
            // Now process form data
            args._process_form_data_callback(args);
            return true;
        }
        args.formData.append('action', 'super_upload_files');
        if(args.form_id) args.formData.append('form_id', args.form_id);
        if(args.entry_id) args.formData.append('entry_id', args.entry_id);
        if(args.list_id) args.formData.append('list_id', args.list_id);
        if(args.sf_nonce) args.formData.append('sf_nonce', args.sf_nonce);
        $.ajax({
            type: 'post',
            url: super_common_i18n.ajaxurl,
            data: args.formData,
            async: true,
            cache: false,
            contentType: false,
            processData: false,
            timeout: 60000*5, // 5m
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                if(args.showOverlay==="true"){
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = evt.loaded / evt.total;
                            //Do something with upload progress here
                            if(args.progressBar) args.progressBar.style.width = (100*percentComplete)+"%";  
                        }
                    }, false);
                }
                return xhr;
            },
            success: function(result){
                result = JSON.parse(result);
                if(result.error===true){
                    // If session expired, retry
                    if(result.type==='session_expired'){
                        // Generate new nonce
                        $.ajax({
                            url: super_common_i18n.ajaxurl,
                            type: 'post',
                            data: {
                                action: 'super_create_nonce'
                            },
                            success: function (nonce) {
                                // Update new nonce
                                args.sf_nonce = nonce.trim();
                                args.form0.querySelector('input[name="sf_nonce"]').value = nonce.trim();
                            },
                            complete: function(){
                                SUPER.upload_files(args, callback);
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                // eslint-disable-next-line no-console
                                console.log(xhr, ajaxOptions, thrownError);
                                alert('Could not generate nonce');
                            }
                        });
                    }else{
                        // Display error message
                        SUPER.form_submission_finished(args, result);
                    }
                }else{
                    var i, uploadedFiles, updateHtml=[], html=[], activeFiles, fieldWrapper, filesWrapper, field,
                        file, 
                        files = result.files;
                    // Update nonce
                    args.sf_nonce = result.sf_nonce;
                    args.form0.querySelector('input[name="sf_nonce"]').value = result.sf_nonce.trim();
                    Object.keys(files).forEach(function(fieldName) {
                        activeFiles = args.form0.querySelector('.super-active-files[name="'+fieldName+'"]');
                        if(!activeFiles) return true; // continue to next field
                        fieldWrapper = activeFiles.closest('.super-field-wrapper');
                        if(!fieldWrapper) return true; // continue to next field
                        filesWrapper = fieldWrapper.querySelector('.super-fileupload-files');
                        if(!filesWrapper) return true; // continue to next field
                        uploadedFiles = filesWrapper.querySelectorAll('.super-uploaded');
                        updateHtml[fieldName] = {
                            filesWrapper: filesWrapper,
                            html: ''
                        }
                        for(i=0; i<uploadedFiles.length; i++){
                            updateHtml[fieldName].html += uploadedFiles[i].outerHTML;
                        }
                    });
                    // Loop over files and update src for each image
                    // We do not have to do this for other file types
                    Object.keys(files).forEach(function(fieldName) {
                        if(typeof updateHtml[fieldName].filesWrapper === 'undefined'){
                            updateHtml[fieldName] = {
                                filesWrapper: filesWrapper,
                                html: ''
                            }
                        }
                        field = files[fieldName];
                        activeFiles = args.form0.querySelector('.super-active-files[name="'+fieldName+'"]');
                        if(!activeFiles) return true; // continue to next field
                        fieldWrapper = activeFiles.closest('.super-field-wrapper');
                        if(!fieldWrapper) return true; // continue to next field
                        filesWrapper = fieldWrapper.querySelector('.super-fileupload-files');
                        if(!filesWrapper) return true; // continue to next field
                        Object.keys(field.files).forEach(function(key) {
                            file = field.files[key];
                            if(args.data[fieldName]){
                                if(!args.data[fieldName]['files'][key]) return;
                                updateHtml[fieldName].html += SUPER.get_single_uploaded_file_html(false, false, file.value, file.type, file.url);
                                args.data[fieldName]['files'][key]['value'] = file.value;
                                args.data[fieldName]['files'][key]['type'] = file.type;
                                args.data[fieldName]['files'][key]['url'] = file.url;
                                var blob = SUPER.files[args.form_id][fieldName][key];
                                delete SUPER.files[args.form_id][fieldName][key];
                                SUPER.files[args.form_id][fieldName][key] = {
                                    url: file.url,
                                    lastModified: blob.lastModified,
                                    lastModifiedDate: blob.lastModifiedDate,
                                    name: file.value,
                                    size: blob.size,
                                    type: file.type,
                                    webkitRelativePath: blob.webkitRelativePath
                                };
                                if(file.subdir) {
                                    args.data[fieldName]['files'][key]['subdir'] = file.subdir;
                                }
                                if(file.attachment) {
                                    args.data[fieldName]['files'][key]['attachment'] = file.attachment;
                                }
                            }
                        });
                        filesWrapper.innerHTML = html;
                    });
                    Object.keys(updateHtml).forEach(function(fieldName) {
                        updateHtml[fieldName].filesWrapper.innerHTML = updateHtml[fieldName].html;
                        var field = SUPER.field(args.form0, fieldName);
                        SUPER.after_field_change_blur_hook({el: field, form: args.form0});
                    });

                    // Now process form data
                    args._process_form_data_callback(args);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                // eslint-disable-next-line no-console
                console.log(xhr, ajaxOptions, thrownError);
                alert(super_common_i18n.errors.failed_to_process_data);
            }
        });
    };
    SUPER.mergeTranslatedSettings = function(defaultSettings, translatedSettings) {
        for (var key in translatedSettings) {
            if (translatedSettings.hasOwnProperty(key)) {
                // Check if the key exists in default settings
                if (defaultSettings.hasOwnProperty(key)) {
                    // If the value is an object, we need to recursively merge
                    if (typeof translatedSettings[key] === 'object' && !Array.isArray(translatedSettings[key])) {
                        // Recursively merge objects
                        defaultSettings[key] = SUPER.mergeTranslatedSettings(defaultSettings[key], translatedSettings[key]);
                    } else {
                        // If not an object (or if it's an array), replace the value in default settings with the translated value
                        defaultSettings[key] = translatedSettings[key];
                    }
                } else {
                    // If the key doesn't exist in default settings, add it from the translated settings
                    defaultSettings[key] = translatedSettings[key];
                }
            }
        } 
        return defaultSettings;
    };
    SUPER.process_form_data = function(args){
        args.generatePdf = false;
        args.pdfSettings = null;
        if( typeof SUPER.form_js !== 'undefined' && 
            typeof SUPER.form_js[args.form_id] !== 'undefined' && 
            typeof SUPER.form_js[args.form_id]._pdf !== 'undefined' && 
            SUPER.form_js[args.form_id]._pdf.generate === "true" ) {
                args.generatePdf = true;
                args.pdfSettings = SUPER.form_js[args.form_id]._pdf;
                if(args.pdfSettings['i18n'] && args.pdfSettings['i18n'][args.form0.dataset.i18n]){
                    var $translatedSettings = args.pdfSettings['i18n'][args.form0.dataset.i18n];
                    args.pdfSettings = SUPER.mergeTranslatedSettings(args.pdfSettings, $translatedSettings);
                }
                if(args.progressBar) args.progressBar.style.width = 0+'%';
                if(args.pdfSettings.generatingText===''){
                    args.loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+super_common_i18n.loadingOverlay.generating_pdf+'</span>';
                }else{
                    args.loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+args.pdfSettings.generatingText+'</span>';
                }
        }else{
            // In case we are in back-end preview mode
            if( typeof SUPER.get_form_settings === 'function' && 
                typeof SUPER.get_form_settings()._pdf !== 'undefined' && 
                SUPER.get_form_settings()._pdf.generate === "true" ) {
                    args.generatePdf = true;
                    args.pdfSettings = SUPER.get_form_settings()._pdf;
                    if(args.pdfSettings['i18n'] && args.pdfSettings['i18n'][args.form0.dataset.i18n]){
                        var $translatedSettings = args.pdfSettings['i18n'][args.form0.dataset.i18n];
                        args.pdfSettings = SUPER.mergeTranslatedSettings(args.pdfSettings, $translatedSettings);
                    }
                    if(args.progressBar) args.progressBar.style.width = 0+'%';
                    if(args.pdfSettings.generatingText===''){
                        args.loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+super_common_i18n.loadingOverlay.generating_pdf+'</span>';
                    }else{
                        args.loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+args.pdfSettings.generatingText+'</span>';
                    }
            }
        }
        if(args.generatePdf){
            SUPER.pdf_generator_init(args, function(args){
                // When debugging is enabled download file instantly without submitting the form
                if(args.pdfSettings.debug!=="true"){
                    SUPER.save_data(args); 
                    return true;
                }
                var innerText = args.loadingOverlay.querySelector('.super-inner-text');
                // Direct download of PDF
                args._pdf.save(args.pdfSettings.filename, {returnPromise: true}).then(function() {
                    // Close loading overlay
                    if(args.progressBar) args.progressBar.style.width = (100)+"%";  
                    if(innerText) innerText.innerHTML = '<span>'+super_common_i18n.loadingOverlay.completed+'</span>';
                    args.loadingOverlay.classList.add('super-success');
                    if(args.pdfSettings.downloadBtn==='true'){
                        args.loadingOverlay.classList.add('super-success');
                        SUPER.show_pdf_download_btn(args);
                    }
                    // Close Popup (if any)
                    if(typeof SUPER.init_popups === 'function' && typeof SUPER.init_popups.close === 'function' ){
                        SUPER.init_popups.close(true);
                    }
                }, function() {
                    // Show error message
                    if(innerText) innerText.innerHTML = '<span>Something went wrong while downloading the PDF</span>';
                    args.loadingOverlay.classList.add('super-error');
                });
            });
        }else{
            SUPER.save_data(args);
        }
    };

    // Focus form
    SUPER.focusForm = function(target){
        if(!target) return false;
        if(target.tagName!=='FORM'){
            target = target.closest('form');
        }
        // Only when form is initialized
        if(target && (target.closest('.super-initialized') || target.closest('.super-preview-elements'))){
            target.classList.add('super-form-focussed');
            target.tabIndex = -1;
            SUPER.lastFocussedForm = target;
        }
    };
    // Reset focussed fields
    SUPER.resetFocussedFields = function(){
        var i, nodes = document.querySelectorAll('.super-focus,.super-string-found');
        for(i=0; i<nodes.length; i++){
            if(nodes[i].classList.contains('super-keyword-tags')){
                var f = nodes[i].querySelector('.super-keyword-filter');
                if(f) f.value = ''; // empty value
            }
            nodes[i].classList.remove('super-focus');
            if(nodes[i].classList.contains('super-auto-suggest')){
                nodes[i].classList.remove('super-string-found');
            }
        }
    };
    // Focus field
    SUPER.focusField = function(target){
        // Only when form is initialized
        if(target && (target.closest('.super-initialized') || target.closest('.super-preview-elements')) ){
            SUPER.resetFocussedFields();
            if(!target.classList.contains('super-field')) target = target.closest('.super-field');
            if(!target) return;
            target.classList.add('super-focus');
            var c1 = target.querySelector('.super-shortcode-field').classList.contains('super-auto-suggest');
            var c2 = target.classList.contains('super-filled');
            var c3 = target.querySelector('.super-dropdown-list > .super-item');
            if(c1 && c2 && c3){
                target.classList.add('super-string-found');
                if(target.querySelector('.super-item.super-active')){
                    SUPER.scrollToElement(target.querySelector('.super-item.super-active'));
                }
            }
            if(target.classList.contains('super-dropdown')){
                if(target.querySelector('.super-item.super-active')){
                    SUPER.scrollToElement(target.querySelector('.super-item.super-active'));
                }
            }
        }
    };
    SUPER.scrollToElement = function(el){
        var pos = el.getBoundingClientRect();
        var margin = 50; // 50 pixels margin top/bottom
        var height = window.innerHeight;
		var popupContent = el.closest('.super-popup-content');
		if(popupContent) {
            height = popupContent.getBoundingClientRect().height;
        }else{
            var dropdownList = el.closest('.super-dropdown-list');
            if(dropdownList) {
                height = dropdownList.getBoundingClientRect().height;
            }
        }
        if(pos.top >= 0 && pos.top < (height*0.8)) {
            // do nothing
            return false;
        }
        var block = 'center'; // by default scroll to center
        if(pos.top < 0) {
            block = 'start';
        }
        el.scrollIntoView({ behavior: 'auto', block: block });
        requestAnimationFrame(() => {
            el.scrollIntoView({ behavior: 'smooth', block: block });
        });
    };
    SUPER.focusNextTabField = function(e, next, form, skipNext){
        var i, nodes, parentTabElement, tabsElement, menuWrapper, menuNodes, contentsWrapper, contentNodes, keyCode = -1;
        if(e){
            keyCode = e.keyCode || e.which;
            if(typeof skipNext !== 'undefined'){
                next = skipNext;
            }else{
                next = SUPER.nextTabField(e, next, form);
            }
        }
        if(!next) return false;

        // Only for front-end and/or live preview, but not builder mode
        if(next.closest('.super-preview-elements')){
            return false;
        }
        
        if(next.classList.contains('super-item')){
            next = next.closest('.super-field');
        }
        
        // Check if inside invisble element
        if(next.closest('.super-invisible')){
            next = SUPER.nextTabField(e, next, form);
        } 

        // Check if inside multi-part, and if multi-part isn't active, make it active
        if(next.closest('.super-multipart') && !next.closest('.super-multipart').classList.contains('super-active')) {
            if(SUPER.lastTabKey==='shift+tab'){
                SUPER.switchMultipart(e, next, 'prev');
            }else{
                SUPER.switchMultipart(e, next, 'next');
            }
        }
        // Check if inside TAB element, and if TAB isn't active, make it active
        parentTabElement = next.closest('.super-tabs-content');
        while(parentTabElement){
            if(!parentTabElement.classList.contains('super-active')){
                tabsElement = parentTabElement.closest('.super-tabs');
                menuWrapper = tabsElement.querySelector('.super-tabs-menu');
                contentsWrapper = tabsElement.querySelector('.super-tabs-contents');
                menuNodes = menuWrapper.querySelectorAll('.super-tabs-tab');
                contentNodes = contentsWrapper.querySelectorAll('.super-tabs-content');
                for(i=0; i<contentNodes.length; i++){
                    if(contentNodes[i]===parentTabElement){
                        contentNodes[i].classList.add('super-active');
                        menuNodes[i].classList.add('super-active');
                    }else{
                        contentNodes[i].classList.remove('super-active');
                        menuNodes[i].classList.remove('super-active');
                    }
                }
            }
            parentTabElement = parentTabElement.parentNode.closest('.super-tabs-content');
        }
        // Check if inside Accordion element, and if Accordion isn't active, make it active
        parentTabElement = next.closest('.super-accordion-item');
        while(parentTabElement){
            if(!parentTabElement.classList.contains('super-active')){
                tabsElement = parentTabElement.closest('.super-tabs');
                contentNodes = tabsElement.querySelectorAll('.super-accordion-item');
                for(i=0; i<contentNodes.length; i++){
                    contentNodes[i].classList.remove('super-active');
                    if(contentNodes[i]===parentTabElement){
                        contentNodes[i].classList.add('super-active');
                    }
                }
            }
            parentTabElement = parentTabElement.parentNode.closest('.super-accordion-item');
        }

        if(e && e.type!=='click' && keyCode != 32){
            // Only scroll into view via tabbing, not via clicking
            SUPER.scrollToElement(next);
        }
        if(keyCode != 32){
            // If not Space key press
            nodes = form.querySelectorAll('.super-focus');
            for ( i = 0; i < nodes.length; i++){
                if(nodes[i].classList.contains('super-auto-suggest')){
                    nodes[i].classList.remove('super-string-found');
                }
                nodes[i].classList.remove('super-focus');
                nodes[i].classList.remove('super-open');
                if(nodes[i].querySelector('.super-shortcode-field')){
                    nodes[i].querySelector('.super-shortcode-field').blur();
                }
                if( nodes[i].classList.contains('super-button-wrap') ) {
                    SUPER.init_button_colors( nodes[i] );
                }
            }
        }
        nodes = form.querySelectorAll('.super-open');
        for ( i = 0; i < nodes.length; i++){
            if(nodes[i].classList.contains('super-auto-suggest')){
                nodes[i].classList.remove('super-string-found');
            }
            nodes[i].classList.remove('super-open');
        }
        nodes = form.querySelectorAll('.super-color .super-shortcode-field');
        for ( i = 0; i < nodes.length; i++){
            $(nodes[i]).spectrum("hide");
        }
        if( next.classList.contains('super-checkbox') || next.classList.contains('super-radio') ) {
            next.classList.add('super-focus');
            if( (next.querySelector('.super-item.super-focus')) && (keyCode != 32) ){
                var current = next.querySelector('.super-item.super-focus');
                var nextSibling = current.nextSibling;
                current.classList.remove('super-focus');
                nextSibling.classList.add('super-focus');
            }else{
                var innerNodes = next.querySelectorAll('.super-item');
                // Radio has active item
                if(next.classList.contains('super-radio')){
                    var activeFound = next.querySelector('.super-item.super-active');
                }
                if((SUPER.lastTabKey!=='tab' && keyCode != 9) || (keyCode == 1) ){
                    // If not TAB key press
                    return true;
                }
                if(activeFound){
                    activeFound.classList.add('super-focus');
                }else{
                    // var innerNodes = next.querySelectorAll('.super-item');
                    if(e && e.shiftKey){
                        innerNodes[innerNodes.length-1].classList.add('super-focus');
                    }else{
                        innerNodes[0].classList.add('super-focus');
                    }
                }
            }
            if(e) e.preventDefault();
            return false;
        }
        if( next.classList.contains('super-form-button') ) {
            next.classList.add('super-focus');
            SUPER.init_button_hover_colors( next );
            next.querySelector('.super-button-wrap').focus();
            if(e) e.preventDefault();
            return false;
        }
        if( next.classList.contains('super-color')) {
            next.classList.add('super-focus');
            $(next.querySelector('.super-shortcode-field')).spectrum('show');
            if(e) e.preventDefault();
            return false;
        }
        if( next.classList.contains('super-keyword-tags')) {
            next.classList.add('super-focus');
            next.querySelector('.super-keyword-filter').focus();
            if(e) e.preventDefault();
            return false;
        }
        if( next.querySelector('.super-shortcode-field').classList.contains('super-address-autopopulate')){
            SUPER.focusField(next);
            next.querySelector('.super-shortcode-field').focus();
            //next.classList.add('super-focus');
            //next.classList.add('super-open');
            //next.classList.add('super-open');
            return false;
        }
        if( next.classList.contains('super-dropdown') ) {
            next.classList.add('super-focus');
            next.classList.add('super-open');
            if(next.querySelector('input[name="super-dropdown-search"]')){
                next.querySelector('input[name="super-dropdown-search"]').focus();
                if(e) e.preventDefault();
                return false;
            }
        }else{
            next.classList.add('super-focus');
        }
        if(next.querySelector('.super-shortcode-field')){
            next.querySelector('.super-shortcode-field').focus();
            if(e) {
                if(form.classList.contains('super-form-focussed')){
                    SUPER.scrollToTopOfForm(e, form);
                }
            }
        }
        if(e){
            e.preventDefault();
        }else{
            SUPER.scrollToElement(next);
        }
        return false;
    };
    SUPER.scrollToTopOfForm = function(e, form){
        // First check if scrolling is disabled on next/prev button for multi-part
        var multipart = form.querySelector('.super-multipart.super-active');
        if(!multipart) return;
        if(typeof multipart.dataset.disableScrollPn === 'undefined'){
            if(e && !e.shiftKey){
                SUPER.scrollToElement(form);
            }
        }
    };
    // Only if field exists and not conditionally hidden
    SUPER.field_isset = function(form, name, regex){
        var el = SUPER.field(form, name, regex);
        if(!el) return 0; // does not exist, is not set
        if(SUPER.has_hidden_parent(el,false,false)) {
            return 0; // was conditionally hidden
        }
        return 1;
    };
    // Only if field exists
    SUPER.field_exists = function(form, name, regex){
        return (SUPER.field(form, name, regex) ? 1 : 0);
    };
    SUPER.field = function(form, name, regex){
        if(typeof name === 'undefined') name = '';
        regex = (typeof regex === 'undefined' ? '' : regex );
        // If we want to return all but not by name
        if(name==='' && regex=='all') return form.querySelectorAll('.super-shortcode-field:not(.super-fileupload), .super-active-files, .super-recaptcha');
        // If name is empty just return the first field only
        if(name==='' && regex==='') return form.querySelector('.super-shortcode-field:not(.super-fileupload), .super-active-files');
        // If no regex was defined return field just by their exact name match
        if(name!=='' && regex==='') {
            // Check if we can return the cached value
            if(typeof SUPER.cachedFields[form.id]!=='undefined' && typeof SUPER.cachedFields[form.id][name]!=='undefined'){
                return SUPER.cachedFields[form.id][name];
            }
            var field = form.querySelector('.super-shortcode-field:not(.super-fileupload)[name="'+name+'"], .super-active-files[name="'+name+'"]');
            if(typeof SUPER.cachedFields[form.id]==='undefined') SUPER.cachedFields[form.id] = {};
            if(typeof SUPER.cachedFields[form.id][name]==='undefined') {
                SUPER.cachedFields[form.id][name] = field;
            }
            return field;
        }
        // If regex is set to 'all' we want to search for multiple fields
        // This is currently being used by the builder to determine duplicate field names
        if(name!=='' && regex=='all') return form.querySelectorAll('.super-shortcode-field:not(.super-fileupload)[name="'+name+'"], .super-active-files[name="'+name+'"]');
        // If a regex is defined, search for fields based on the regex
		// Check if we can return the cached value
		if(typeof SUPER.cachedFields[form.id]!=='undefined' && typeof SUPER.cachedFields[form.id][regex+'='+name]!=='undefined'){
			SUPER.cachedFields_returned_counter++;
			return SUPER.cachedFields[form.id][regex+'='+name];
		}
		var fields = form.querySelectorAll('.super-shortcode-field:not(.super-fileupload)[name'+regex+'="'+name+'"], .super-active-files[name="'+name+'"]');
		if(typeof SUPER.cachedFields[form.id]==='undefined') SUPER.cachedFields[form.id] = {};
		if(typeof SUPER.cachedFields[form.id][regex+'='+name]==='undefined') {
            SUPER.cachedFields[form.id][regex+'='+name] = fields; 
        }
		return fields;
    };

    SUPER.fields = function(form, selector){
        return form.querySelectorAll(selector);
    };
    SUPER.fieldsByName = function(form, name){
        if(name==='') return null; // Skip empty names due to "translation mode"
        return form.querySelectorAll('.super-shortcode-field:not(.super-fileupload)[name="'+name+'"], .super-active-files[name="'+name+'"]');
    };
    SUPER.replaceAll = function(value, searchFor, replaceWith){
        try {
            // Convert needle so it can be used as a regex safely
            searchFor = searchFor.replace(/[/\-\\^$*+?.()|[\]{}]/g, '\\$&');
            var re = new RegExp(searchFor, "g");
            return value.replace(re, replaceWith);
        }catch (e) {
            // Do nothing
        }
        return value;
    };
    SUPER.has_hidden_parent = function(changedField, includeMultiParts, includeInvisibleColumns){
        if(typeof includeInvisibleColumns === 'undefined') includeInvisibleColumns = true;
        if(changedField[0]) changedField = changedField[0];
        if(changedField.parentNode.closest('.super-invisible') && includeInvisibleColumns) return true;
        var p, parent;
        parent = changedField.closest('.super-shortcode');
        if( parent && (parent.style.display=='none') && (!parent.classList.contains('super-hidden')) ) {
            return true;
        }
        if(parent.dataset.conditionalAction){
            if((parent.classList.contains('super-conditional-hidden')) ||
                (parent.dataset.conditionalAction==='hide' && parent.classList.contains('super-conditional-hidden')) ||
                (parent.dataset.conditionalAction==='show' && !parent.classList.contains('super-conditional-visible'))) {
                return true;
            }
        }

        for (p = changedField && changedField.parentElement; p; p = p.parentElement) {
            if(p.classList.contains('super-form')) break;
            if(p.dataset.conditionalAction){
                if((p.classList.contains('super-conditional-hidden')) ||
                   (p.dataset.conditionalAction==='hide' && p.classList.contains('super-conditional-hidden')) ||
                   (p.dataset.conditionalAction==='show' && !p.classList.contains('super-conditional-visible'))) {
                    return true;
                }
            }
        }
        
        // Also check for multi-parts if necessary
        if(typeof includeMultiParts === 'undefined') includeMultiParts = false;
        if(includeMultiParts && changedField.closest('.super-multipart') && !changedField.closest('.super-multipart').classList.contains('super-active')){
            return true;
        }
        return false;
    };

    // Get/Set session data based on pointer
    SUPER.get_session_pointer = function(key){
        function getUrlVars() {
            var vars = {};
            window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
                vars[key] = value;
            });
            return vars;
        }
        function getUrlParam(parameter, defaultvalue){
            var urlparameter = defaultvalue;
            if(window.location.href.indexOf(parameter) > -1){
                urlparameter = getUrlVars()[parameter];
            }
            return urlparameter;
        }
        return key+'_'+getUrlParam('id', 0);
    };
    SUPER.set_session_data = function(key, data, method, raw){
        if(typeof method === 'undefined') method = 'session';
        if(typeof raw === 'undefined') raw = false;
        if(key!=='_super_transfer_element_html') key = SUPER.get_session_pointer(key);
        if(method==='session'){
            try {
                if(data===false){
                    sessionStorage.removeItem(key);
                }else{
                    sessionStorage.setItem(key, data);
                }
            }
            catch (e) {
                // Empty data when localstorage is full
                data = JSON.parse(data);
                var length = data.length/2;
                var i = 0;
                while(i < length){
                    if(typeof data[i] !== 'undefined'){
                        delete data[i];
                    }
                    i++;
                }
                SUPER.set_session_data(key, data, method);
            }
        }else{
            if(data===false){
                localStorage.removeItem(key);
            }else{
                localStorage.setItem(key, data);
            }
        }
    };
    SUPER.get_session_data = function(key, method){
        if(typeof method === 'undefined') method = 'session';
        if(key!=='_super_transfer_element_html') key = SUPER.get_session_pointer(key);
        if(method==='session'){
            return sessionStorage.getItem(key);
        }else{
            return localStorage.getItem(key);
        }
    };

    // Barcode generator
    SUPER.generateBarcode = function(){
        $('.super-barcode').each(function(){
            var $this = $(this).find('input');
            var $renderer = 'css';
            var $barcode = $this.val();
            var $barcodetype = $this.data('barcodetype');
            var $background = $this.data('background');
            var $barcolor = $this.data('barcolor');
            var $barwidth = $this.data('barwidth');
            var $barheight = $this.data('barheight');
            var $modulesize = $this.data('modulesize');
            var $rectangular = $this.data('rectangular');
            var $quietzone = false;
            if ($this.data('quietzone')==1) $quietzone = true;
            var $settings = {
                output:$renderer,
                bgColor: $background,
                color: $barcolor,
                barWidth: $barwidth,
                barHeight: $barheight,
                moduleSize: $modulesize,
                addQuietZone: $quietzone
            };
            if($rectangular==1){
                $barcode = {code:$barcode, rect:true};
            }
            $this.parent().find('.super-barcode-target').barcode($barcode, $barcodetype, $settings);
        });
    };

    // init Rating
    SUPER.rating = function(){
        $('.super-rating').on('mouseleave',function(){
            $(this).find('.super-rating-star').removeClass('super-hover');
        });
        $('.super-rating-star').on('click',function(){
            SUPER.focusForm(this);
            SUPER.focusField(this);
            $(this).parent().find('.super-rating-star').removeClass('super-active');
            $(this).addClass('super-active');
            $(this).prevAll('.super-rating-star').addClass('super-active');
            var $rating = $(this).index()+1;
            $(this).parent().find('input').val($rating);
            SUPER.after_field_change_blur_hook({el: $(this).parent().find('input')[0]});
        });
        $('.super-rating-star').on('mouseover',function(){
            $(this).parent().find('.super-rating-star').removeClass('super-hover');
            $(this).addClass('super-hover');
            $(this).prevAll('.super-rating-star').addClass('super-hover');
        });
    };


    // @since 2.3.0 - init file upload fields
    SUPER.init_fileupload_fields = function(){
        $('.super-fileupload:not(.super-rendered)').each(function() {
            $(this).addClass('super-rendered');
            var formId = 0;
            var form = SUPER.get_frontend_or_backend_form({el: this});
            if(form.querySelector('input[name="hidden_form_id"]')){
                formId = form.querySelector('input[name="hidden_form_id"]').value;
            }
            var field = $(this).parents('.super-field-wrapper:eq(0)').find('.super-active-files');
            var fieldName = field.attr('name');
            if(typeof SUPER.files[formId] === 'undefined'){
                SUPER.files[formId] = [];
            }
            if(typeof SUPER.files[formId][fieldName] === 'undefined'){
                SUPER.files[formId][fieldName] = [];
            }
            var i, file, fileName, fileUrl, fileType,
                uploadedFiles = $(this).parents('.super-field-wrapper:eq(0)').find('.super-fileupload-files > .super-uploaded');
            for(i=0; i<uploadedFiles.length; i++){
                file = uploadedFiles[i];
                fileName = file.dataset.name;
                fileUrl = file.dataset.url;
                fileType = file.dataset.type;
                SUPER.files[formId][fieldName][i] = {};
                SUPER.files[formId][fieldName][i]['type'] = fileType;
                SUPER.files[formId][fieldName][i]['name'] = fileName;
                SUPER.files[formId][fieldName][i]['url'] = fileUrl;
            }

            $(this).fileupload({
                filesContainer : $(this).find(".super-fileupload-files"),
                dropZone : $(this).parent('.super-field-wrapper'),
                add: function(e, data) {
                    var uploadErrors = [];
                    if(data.originalFiles[0].size > ($(this).data('file-size')*1000000) ) {
                        $(this).parents('.super-field-wrapper:eq(0)').find('.super-fileupload-files').children('div[data-name="'+data.originalFiles[0].name+'"]').remove();
                        uploadErrors.push(super_common_i18n.errors.file_upload.filesize_too_big);
                    }
                    if(uploadErrors.length > 0) {
                        alert(uploadErrors.join("\n"));
                    }
                },
                dataType: 'json',
                autoUpload: false,
                maxFileSize: $(this).data('file-size')*1000000, // 5 MB
                progressall: function (e, data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    $(this).parent().children('.super-progress-bar').css('display','block').css('width', progress + '%');
                }        
            }).on('fileuploadadd', function (e, data) {
                var formId = 0;
                var form = SUPER.get_frontend_or_backend_form({el: this});
                if(form.querySelector('input[name="hidden_form_id"]')){
                    formId = form.querySelector('input[name="hidden_form_id"]').value;
                }
                var fieldName = $(this).parents('.super-field-wrapper:eq(0)').find('.super-active-files').attr("name");
                var field = $(this).parents('.super-field-wrapper:eq(0)').find('.super-active-files')[0];
                $(this).removeClass('finished');
                $(this).parents('.super-field-wrapper:eq(0)').find('.super-fileupload-files > div.error').remove();
                if(typeof SUPER.files[formId] === 'undefined'){
                    SUPER.files[formId] = [];
                }
                if(typeof SUPER.files[formId][fieldName] === 'undefined'){
                    SUPER.files[formId][fieldName] = [];
                }
                var totalFiles = SUPER.files[formId][fieldName].length;
                var maxFiles = field.dataset.maxfiles;
                if(totalFiles >= parseFloat(maxFiles)){
                    if(!e.target.classList.contains('super-max-reached')){
                        e.target.classList.add('super-max-reached');
                        alert(super_common_i18n.errors.file_upload.upload_limit_reached);
                    }
                    return;
                }
                data.context = $('<div/>').appendTo($(this).parents('.super-field-wrapper:eq(0)').find('.super-fileupload-files'));
                var el = $(this);
                var accepted_file_types = el.data('accept-file-types');
                var file_types_object = accepted_file_types.split('|');
                // @since 4.4.0 - Upload limitation for all files combined
                var upload_limit = $(this).data('upload-limit')*1000000; // e.g: 20 MB
                $.each(data.files, function (index, file) {
                    var total = el.data('total-file-sizes');
                    if(typeof total === 'undefined'){
                        total = file.size;
                    }else{
                        total = total+file.size;
                    }
                    if( (total>upload_limit) && (upload_limit!==0) ) {
                        el.parents('.super-field-wrapper:eq(0)').find('.super-fileupload-files > div').last().remove();
                        alert(super_common_i18n.errors.file_upload.upload_size_limit_reached);
                        return;
                    }else{
                        var f = SUPER.get_file_name_and_extension(file.name);
                        if( (file_types_object.indexOf(f.ext)!=-1) || (accepted_file_types==='') ) {
                            el.data('total-file-sizes', total);
                            if(file.type && file.type.indexOf("image/") === 0){
                                var src = URL.createObjectURL(file)
                            }
                            var totalFiles = SUPER.files[formId][fieldName].length;
                            SUPER.files[formId][fieldName][totalFiles] = file;
                            SUPER.files[formId][fieldName][totalFiles]['url'] = src; // blob
                            var html = SUPER.get_single_uploaded_file_html(true, false, file.name, file.type, src);
                            data.context.data(data).attr('data-name',file.name).attr('title',file.name).attr('data-type',file.type).html(html);
                            data.context.data('file-size',file.size);
                            if(data.context[0].querySelector('img')){
                                var img = data.context[0].querySelector('img');
                                img.onload = function(){
                                    // URL.revokeObjectURL(img.src); // free memory
                                }
                            }
                            SUPER.after_field_change_blur_hook({el: field, form: form});
                        }else{
                            data.context.remove();
                            alert(super_common_i18n.errors.file_upload.incorrect_file_extension);
                            return;
                        }
                    }
                });
            }).on('fileuploadprocessalways', function (e, data) {
                var index = data.index;
                var file = data.files[index];
                if (file.error) {
                    $(this).parents('.super-field-wrapper:eq(0)').find('.super-fileupload-files').find("[data-name='" + file.name + "']").remove();
                    alert(file.error);
                }
            }).on('fileuploadfail', function (e, data) {
                var el = e.target;
                var form = el.closest('.super-form');
                SUPER.handle_errors(el);
                $.each(data.files, function (index) {
                    var error = $('<span class="super-error"/>').text(' (file upload failed)');
                    $(data.context.children()[index]).children('.super-error').remove();
                    $(data.context.children()[index]).append(error);
                });
                alert(data.errorThrown.message);
                SUPER.reset_submit_button_loading_state(form);
                SUPER.handle_validations({el: el, form: form});
                SUPER.scrollToError(form);
            }).on('fileuploadsubmit', function (e, data) {
                data.formData = {
                    'accept_file_types': $(this).data('accept-file-types'),
                    'max_file_size': $(this).data('file-size')*1000000,
                    'image_library': super_common_i18n.image_library
                };
            });
        });
    };

    // @since 5.0.100 - international phonenumber field
    SUPER.init_international_phonenumber_fields = function(){
        $('.super-shortcode-field[type="int-phone"]:not(.super-rendered)').each(function(){
            var input = this;
            input.classList.add('super-rendered');
            var settings = JSON.parse(input.dataset.intPhone),
                preferredCountries = (settings.preferredCountries==='' ? '' : settings.preferredCountries.replace(/\s/g, '').split(',')),
                onlyCountries = (settings.onlyCountries==='' ? '' : settings.onlyCountries.replace(/\s/g, '').split(',')),
                localizedCountries = {},
                items = (settings.localizedCountries==='' ? '' : settings.localizedCountries.split('\n')),
                values;
            $(items).each(function(i, v){
                values = v.split("|");
                if(values[0] && values[1]){
                    localizedCountries[values[0]] = values[1];
                }
            });
            window.superTelInput(input, {
                separateDialCode: true,
                autoPlaceholder: "aggressive",
                utilsScript: super_common_i18n.super_int_phone_utils, //"utils.js", 
                preferredCountries: preferredCountries, // ["nl", "de", "be"] Specify the countries to appear at the top of the list.
                onlyCountries: onlyCountries, // ["nl", "de", "be"]
                localizedCountries: localizedCountries, //{ 'de': 'Deutschland' }
                placeholderNumberType: settings.placeholderNumberType, // "MOBILE" // "FIXED_LINE": 0, "MOBILE": 1, "FIXED_LINE_OR_MOBILE": 2, "TOLL_FREE": 3, "PREMIUM_RATE": 4, "SHARED_COST": 5, "VOIP": 6, "PERSONAL_NUMBER": 7, "PAGER": 8, "UAN": 9, "VOICEMAIL": 10, "UNKNOWN": -1
                customPlaceholder: function(selectedCountryPlaceholder) {
                    var adaptivePlaceholder = input.closest('.super-int-phone-field').querySelector('.super-adaptive-placeholder');
                    if(adaptivePlaceholder){
                        //adaptivePlaceholder.dataset.placeholder = selectedCountryPlaceholder;
                        if(input.closest('.super-shortcode').classList.contains('super-filled')){
                            // Is filled
                            adaptivePlaceholder.querySelector('span').innerHTML = adaptivePlaceholder.dataset.placeholderfilled;
                        }else{
                            adaptivePlaceholder.querySelector('span').innerHTML = adaptivePlaceholder.dataset.placeholder; // selectedCountryPlaceholder;
                        }
                    }
                    return selectedCountryPlaceholder;
                },
            });
            this.addEventListener("countrychange", function() {
                SUPER.after_field_change_blur_hook({el: this});
            });
            this.addEventListener("open:countrydropdown", function() {
                var form = SUPER.get_frontend_or_backend_form({el: this}),
                    i, nodes = form.querySelectorAll('.super-open');
                for ( i = 0; i < nodes.length; i++){
                    nodes[i].classList.remove('super-open');
                }
                this.closest('.super-shortcode').classList.add('super-open');
            });
            this.addEventListener("close:countrydropdown", function() {
                this.closest('.super-shortcode').classList.remove('super-open');
            });
        });
    };

    // @since 3.5.0 - calculate distance (google)
    var distance_calculator_timeout = null; 
    SUPER.calculate_distance = function(args){
        $('.super-msg.super-error.super-distance-calculation-error').remove();
        if(!args.el) return false;
        if(args.el.classList.contains('super-distance-calculator')){
            var form = SUPER.get_frontend_or_backend_form(args),
                $method = args.el.dataset.distanceMethod,
                $origin_field,
                $origin,
                $destination_field,
                $destination_field2,
                $destination,
                $destination2,
                $value,
                $units;
            if($method=='start'){
                $origin_field = args.el;
                $origin = args.el.value;
                $destination = args.el.dataset.distanceDestination;
                if(SUPER.field_exists(form, $destination)){
                    $destination_field = SUPER.field(form, $destination);
                    $destination = ($destination_field ? $destination_field.value : '');
                }
            }else{
                if($method=='both'){
                    $origin_field = SUPER.field(form, args.el.dataset.distanceStart);
                    $origin = ($origin_field ? $origin_field.value : '');
                    $destination_field = args.el;
                    $destination = args.el.value;
                    // Calculate two different distances
                    $destination2 = args.el.dataset.distanceDestination;
                    if(SUPER.field_exists(form, $destination2)){
                        $destination_field2 = SUPER.field(form, $destination2);
                        $destination2 = ($destination_field2 ? $destination_field2.value : '');
                    }
                }else{
                    $origin_field = SUPER.field(form, args.el.dataset.distanceStart);
                    $origin = ($origin_field ? $origin_field.value : '');
                    $destination_field = args.el;
                    $destination = args.el.value;
                }
            }
            $value = $origin_field.dataset.distanceValue;
            $units = $origin_field.dataset.distanceUnits;
            if($method=='both'){
                $value = $destination_field.dataset.distanceValue;
                $units = $destination_field.dataset.distanceUnits;
            }
            if($value!='dis_text'){
                $units = 'metric';
            }
            if( ($origin==='') || ($destination==='') ) {
                args.el.closest('.super-field-wrapper').classList.remove('super-calculating-distance');
                var $field = $origin_field.dataset.distanceField;
                $field = SUPER.field(form, $field);
                $field.value = '';
                SUPER.after_field_change_blur_hook({el: $field});
                return true;
            }
            if($method=='both'){
                if( ($origin==='') || ($destination==='' && $destination2==='') ) {
                    args.el.closest('.super-field-wrapper').classList.remove('super-calculating-distance');
                    return true;
                }
            }
            if(distance_calculator_timeout !== null) clearTimeout(distance_calculator_timeout);
            distance_calculator_timeout = setTimeout(function () {
                if(typeof google === 'undefined'){
                    console.log(super_common_i18n.google.maps.api.key)
                    var url = '//maps.googleapis.com/maps/api/js?';
                    var field = args.el;
                    if(field.dataset.apiRegion!=='') url += 'region='+field.dataset.apiRegion+'&';
                    if(field.dataset.apiLanguage!=='') url += 'language='+field.dataset.apiLanguage+'&';
                    url += 'key='+super_common_i18n.google.maps.api.key+'&libraries=drawing,geometry,places,visualization&callback=SUPER.google_maps_init'
                    $.getScript( url, function() {
                        args.el.closest('.super-field-wrapper').classList.add('super-calculating-distance');
                        args.el.closest('.super-field-wrapper').classList.add('super-calculating-distance1');
                        if($origin!=='' && $destination!==''){
                            SUPER.calculateDistance(args, form, $value, $origin, $destination, $units, $origin_field, $destination_field, $destination_field2, 1);
                        }
                        if($method=='both' && ($destination!=='' && $destination2!=='')){
                            SUPER.calculateDistance(args, form, $value, $destination, $destination2, $units, $origin_field, $destination_field, $destination_field2, 2);
                        }
                    });
                }else{
                    args.el.closest('.super-field-wrapper').classList.add('super-calculating-distance');
                    args.el.closest('.super-field-wrapper').classList.add('super-calculating-distance2');
                    if($origin!=='' && $destination!==''){
                        SUPER.calculateDistance(args, form, $value, $origin, $destination, $units, $origin_field, $destination_field, $destination_field2, 1);
                    }
                    if($method=='both' && ($destination!=='' && $destination2!=='')){
                        SUPER.calculateDistance(args, form, $value, $destination, $destination2, $units, $origin_field, $destination_field, $destination_field2, 2);
                    }
                }
            }, SUPER.calculate_distance_speed);
        }
    };
    SUPER.calculateDistance = async function(args, form, value, origin, destination, units, origin_field, destination_field, destination_field2, type){
        try {
            if(!SUPER.DistanceMatrixService) SUPER.DistanceMatrixService = new google.maps.DistanceMatrixService();
            SUPER.DistanceMatrixService.getDistanceMatrix({
                origins: [origin],
                destinations: [destination],
                travelMode: google.maps.TravelMode.DRIVING,
                unitSystem: (units === 'imperial' ? google.maps.UnitSystem.IMPERIAL : google.maps.UnitSystem.METRIC),
                language: super_common_i18n.google.maps.api.language,
                region: super_common_i18n.google.maps.api.region
              }, function(response, status){
                if(status !== google.maps.DistanceMatrixStatus.OK){
                  alert('Google API Distance Matrix Error: ' + status);
                  console.error(respons);
                } else {
                    var $leg, $calculation_value;
                    $('.super-msg').remove();
                    var $field = origin_field.dataset.distanceField;
                    $field = SUPER.field(form, $field);
                    if(response.rows[0] && response.rows[0].elements[0].status===google.maps.DistanceMatrixStatus.OK){
                        $leg = response.rows[0].elements[0];
                        // distance  - Distance in meters
                        if( value=='distance' ) $calculation_value = $leg.distance.value;
                        // dis_text  - Distance text in km or miles
                        if( value=='dis_text' ) $calculation_value = $leg.distance.text;
                        // duration  - Duration in seconds
                        if( value=='duration' ) $calculation_value = $leg.duration.value;
                        // dur_text  - Duration text in minutes
                        if( value=='dur_text' ) $calculation_value = $leg.duration.text;
                        $field.value = $calculation_value;
                    }else{
                        var blurFields = true;
                        $leg = response.rows[0].elements[0];
                        if($leg.status==='ZERO_RESULTS'){
                            var $alert_msg = super_common_i18n.errors.distance_calculator.not_found; // No route could be found between the origin and destination.
                        }
                        if($leg.status==='NOT_FOUND') {
                            blurFields = false;
                            var $alert_msg = super_common_i18n.errors.distance_calculator.not_found;
                        }else{
                            // INVALID_REQUEST // MAX_DIMENSIONS_EXCEEDED // MAX_ELEMENTS_EXCEEDED // OK // OVER_QUERY_LIMIT // REQUEST_DENIED // UNKNOWN_ERROR
                            var $alert_msg = super_common_i18n.errors.distance_calculator.error+' ('+$leg.status+')';
                        }
                        $field.value = '';
                        var $html = '<div class="super-msg super-error super-distance-calculation-error">';                            
                        // Not sure why we would want to blur these fields after displaying an errors?
                        if(blurFields===true){
                            if(type===1){
                                origin_field.blur();
                                if(typeof destination_field !== 'undefined') destination_field.blur();
                            }else{
                                destination_field.blur();
                                if(typeof destination_field2 !== 'undefined') destination_field2.blur();
                            }
                        }
                        $html += $alert_msg;
                        $html += '<span class="super-close"></span>';
                        $html += '</div>';
                        $($html).prependTo($(form));
                        SUPER.scrollToElement(form);
                    }

                    if($field.value===''){
                        $field.closest('.super-shortcode').classList.remove('super-filled');
                    }else{
                        $field.closest('.super-shortcode').classList.add('super-filled');
                    }
                    SUPER.after_field_change_blur_hook({el: $field});
                    args.el.closest('.super-field-wrapper').classList.remove('super-calculating-distance');
                }
            });
        }catch(error){
            console.error('Error fetching data:', error);
            alert(super_common_i18n.errors.distance_calculator.error);
        }
    };

    // Handle Conditional logic
    SUPER.conditional_logic = function(args){
        var logic,
            didLoop = false,
            form = SUPER.get_frontend_or_backend_form(args);
        if(typeof args.el !== 'undefined'){
            logic = form.querySelectorAll('.super-conditional-logic[data-fields*="{'+SUPER.get_original_field_name(args.el)+'}"]');
        }else{
            logic = form.querySelectorAll('.super-conditional-logic');
        }
        if(typeof logic !== 'undefined'){
            if(logic.length!==0){
                didLoop = true;
                args.conditionalLogic = logic;
                SUPER.conditional_logic.loop(args);
            }
        }
        // Make sure that we still update variable fields based on changed field.
        if( didLoop===false ) {
            SUPER.update_variable_fields(args);
        }
    };

    // @since 3.6.0 - always make sure to return the value of the field in case it uses advanced tags like function like: value;value2
    // Also make sure to return multiple values in case of dropdown/checkbox or other multi value elements
    // Function to return the dynamic tag value based on conditions field check
    SUPER.return_dynamic_tag_value = function($parent, $value){
        if( typeof $value === 'undefined' ) return '';
        if( $value==='' ) return $value;
		if($value.indexOf(';')===-1 && $value.indexOf(',')===-1) return $value;
        if( (typeof $parent !== 'undefined') && ( ($parent.hasClass('super-dropdown')) || ($parent.hasClass('super-checkbox')) ) ) {
            var $values = $value.toString().split(',');
            var $new_values = '';
            $.each($values, function( index, value ) {
				if (typeof value !== 'string') value = value.toString();
                var $value = value.split(';');
                $value = $value[0];
                if($new_values===''){
                    $new_values += $value;
                }else{
                    $new_values += ','+$value;
                }
            });
            $value = $new_values;
        }else{
            $value = $value.toString().split(';');
            $value = $value[0];      
        }
        return $value;
    };

    SUPER.conditional_logic.match_found = function($match_found, v, $shortcode_field_value, $shortcode_field_and_value, $parent, $parent_and, $variable){
        var $i = 0,
            $found,
            $checked,
            $string_value;
        switch(v.l) {
          case 'equal':
            if( v.v==$shortcode_field_value ) $i++;
            break;
          case 'not_equal':
            if( v.v!=$shortcode_field_value ) $i++;
            break;
          case 'greater_than':
            if( parseFloat($shortcode_field_value)>parseFloat(v.v) ) $i++;
            break;
          case 'less_than':
            if( parseFloat($shortcode_field_value)<parseFloat(v.v) ) $i++;
            break;
          case 'greater_than_or_equal':
            if( parseFloat($shortcode_field_value)>=parseFloat(v.v) ) $i++;
            break;
          case 'less_than_or_equal':
            if( parseFloat($shortcode_field_value)<=parseFloat(v.v) ) $i++;
            break;
          case 'contains':
            if( (typeof $parent !== 'undefined') && (
                $parent.classList.contains('super-checkbox') || 
                $parent.classList.contains('super-radio') || 
                $parent.classList.contains('super-dropdown')) ) {
                $checked = $shortcode_field_value.split(',');
                $string_value = v.v.toString();
                Object.keys($checked).forEach(function(key) {
                    if( $checked[key].indexOf($string_value) >= 0) {
                        $i++;
                        return false;
                    }
                });
            }else{
                // If other field
                if( $shortcode_field_value.indexOf(v.v) >= 0) $i++;
            }
            break;
          case 'not_contains':
            if( (typeof $parent !== 'undefined') && (
                $parent.classList.contains('super-checkbox') || 
                $parent.classList.contains('super-radio') || 
                $parent.classList.contains('super-dropdown')) ) {
                $checked = $shortcode_field_value.split(',');
                $string_value = v.v.toString();
                $found = false;
                Object.keys($checked).forEach(function(key) {
                    if( $checked[key].indexOf($string_value) >= 0) {
                        $found = true;
                        return false;
                    }
                });
                if(!$found) $i++;
            }else{
                // If other field
                if( $shortcode_field_value.indexOf(v.v) == -1) $i++;
            }
            break;

          default:
          // code block
        }
        if( v.a!=='' ) {
            switch(v.la) {
              case 'equal':
                if( v.va==$shortcode_field_and_value ) $i++;
                break;
              case 'not_equal':
                if( v.va!=$shortcode_field_and_value ) $i++;
                break;
              case 'greater_than':
                if( parseFloat($shortcode_field_and_value)>parseFloat(v.va) ) $i++;
                break;
              case 'less_than':
                if( parseFloat($shortcode_field_and_value)<parseFloat(v.va) ) $i++;
                break;
              case 'greater_than_or_equal':
                if( parseFloat($shortcode_field_and_value)>=parseFloat(v.va) ) $i++;
                break;
              case 'less_than_or_equal':
                if( parseFloat($shortcode_field_and_value)<=parseFloat(v.va) ) $i++;
                break;
              case 'contains':
                if( (typeof $parent_and !== 'undefined') && ( 
                    $parent_and.classList.contains('super-checkbox') || 
                    $parent_and.classList.contains('super-radio') || 
                    $parent_and.classList.contains('super-dropdown')) ) {
                    $checked = $shortcode_field_and_value.split(',');
                    $string_value = v.va.toString();
                    Object.keys($checked).forEach(function(key) {
                        if( $checked[key].indexOf($string_value) >= 0) {
                            $i++;
                            return false;
                        }
                    });
                }else{
                    // If other field
                    if( $shortcode_field_and_value.indexOf(v.va) >= 0) $i++;
                }
                break;
              case 'not_contains':
                if( (typeof $parent_and !== 'undefined') && ( 
                    $parent_and.classList.contains('super-checkbox') || 
                    $parent_and.classList.contains('super-radio') || 
                    $parent_and.classList.contains('super-dropdown')) ) {
                    $checked = $shortcode_field_and_value.split(',');
                    $string_value = v.va.toString();
                    $found = false;
                    Object.keys($checked).forEach(function(key) {
                        if( $checked[key].indexOf($string_value) >= 0) {
                            $found = true;
                            return false;
                        }
                    });
                    if(!$found) $i++;
                }else{
                    // If other field
                    if( $shortcode_field_and_value.indexOf(v.va) == -1) $i++;
                }
                break;
              default:
              // code block
            }
        }
        // When we are checking for variable condition return on matches
        if($variable) return $i;
        // When we are checking conditional logic then we need to know the total matches as a whole, because we have a method (One/All)
        if( v.a=='and' ) {
            if($i>=2) $match_found++;
        }else{
            if($i>=1) $match_found++;
        }
        return $match_found;
    };

    SUPER.get_conditional_validation_value = function(value, form){
        var conditionalParent,
            text_field,
            string_value,
            bracket,
            regex,
            name,
            element,
            sum,
            selected,
            checked;

        string_value = value.toString();
        bracket = "{";
        if(string_value.indexOf(bracket) != -1){
            name = SUPER.tagsRegex.exec(value);
            name = name[1];
            element = SUPER.field(form, name);
            if(element){
                text_field = true;
                conditionalParent = element.closest('.super-field');
                // Check if dropdown field
                if( (conditionalParent.classList.contains('super-dropdown')) ){
                    text_field = false;
                    sum = 0;
                    selected = conditionalParent.querySelectorAll('.super-dropdown-list .super-item.super-active:not(.super-placeholder)');
                    Object.keys(selected).forEach(function(key) {
                        sum += selected[key].dataset.value;
                    });
                    value = sum;
                }
                // Check if checkbox field
                if(conditionalParent.classList.contains('super-checkbox')){
                    text_field = false;
                    sum = 0;
                    checked = conditionalParent.querySelectorAll('input[type="checkbox"]:checked');
                    Object.keys(checked).forEach(function(key) {
                        sum += checked[key].value;
                    });
                    value = sum;
                }
                // Check if currency field
                if(conditionalParent.classList.contains('super-currency')){
                    text_field = false;
                    value = $(element).maskMoney('unmasked')[0];
                    value = (value) ? parseFloat(value) : 0;
                }
                // Check if text or textarea field
                if(text_field===true) value = (element.value) ? element.value : '';
            }
        }
        return value;
    }


    SUPER.conditional_logic.get_field_value = function($logic, $shortcode_field_value, $shortcode_field, $parent){
        if( $logic=='greater_than' || $logic=='less_than' || $logic=='greater_than_or_equal' || $logic=='less_than_or_equal' ) {
            var $sum = 0, $selected;
            // If it does not have parent, return
            if(typeof $parent === 'undefined') return $shortcode_field_value;
            // Check if dropdown field
            if( $parent.classList.contains('super-dropdown') ){
                $selected = $parent.querySelectorAll('.super-dropdown-list .super-item.super-active:not(.super-placeholder)');
                Object.keys($selected).forEach(function(key) {
                    $sum += parseFloat($selected[key].dataset.value);
                });
                $shortcode_field_value = $sum;
            }
            // Check if checkbox field
            if( $parent.classList.contains('super-checkbox') ) {
                $selected = $parent.querySelectorAll('.super-active');
                Object.keys($selected).forEach(function(key) {
                    $sum += parseFloat($selected[key].querySelector('input').value);
                });
                $shortcode_field_value = $sum;
            }
            // @since 2.3.0 - compatibility with conditional logic
            // Check if currency field (since Super Forms v2.1)
            if( $parent.classList.contains('super-currency') ) {
                var $value = $($shortcode_field).maskMoney('unmasked')[0];
                $shortcode_field_value = ($value) ? parseFloat($value) : 0;
            }
        }
        return $shortcode_field_value;
    };
    SUPER.conditional_logic.loop = function(args){
        var v,
            $v,
            $this,
            $json,
            $wrapper,
            $uid,
            $field,
            $trigger,
            $action,
            $conditions,
            $total,
            $shortcode_field_value,
            $shortcode_field_and_value,
            $continue,
            $continue_and,
            $skip,
            $skip_and,
            $field_name,
            $shortcode_field,
            $shortcode_field_and,
            $parent,
            $parent_and,
            $hide_wrappers,
            $show_wrappers,
            $changed_wrappers,
            $inner,
            $element,
            $data_fields,
            $is_variable,
            $is_validate,
            $match_found,
            $prev_match_found,
            $updated_variable_fields = {},
            $validation_error = false,
            $currentFieldValue = undefined;

        SUPER.hadAjaxRequest = 0;
        SUPER.completedAjaxRequests = 0;
        var form_id = (args.form.querySelector('input[name="hidden_form_id"]') ? args.form.querySelector('input[name="hidden_form_id"]').value : 0);
        if(!SUPER.allConditions) SUPER.allConditions = {};
        if(!SUPER.allConditions[form_id]) SUPER.allConditions[form_id] = {};
        Object.keys(args.conditionalLogic).forEach(function(key) {
            // First retrieve all the conditions, also for those that are retrieve via Ajax requests
            $this = args.conditionalLogic[key];
            $wrapper = $this.closest('.super-shortcode');
            if($this.classList.contains('super-variable-conditions')){
                $action = $wrapper.dataset.conditionalVariableAction;
            }else{
                if($this.classList.contains('super-validate-conditions')){
                    $action = 'show';
                }else{
                    $action = $wrapper.dataset.conditionalAction;
                }
            }
            if($this.value==='') {
                // Except when ajax request
                if($this.dataset.ajax && $this.dataset.ajax==='true'){
                    // Don't skip
                }else{
                    // Skip
                    return;
                }
            }
            $json = $this.value;
            if(($json!=='' || ($this.dataset.ajax && $this.dataset.ajax==='true')) && ($action) && ($action!='disabled')){
                $field = false;
                if($wrapper.closest('.super-field')){
                    $field = $wrapper.closest('.super-field').querySelector('.super-shortcode-field');
                }
                if(!$field) {
                    // Skip if we already retrieved the conditions before
                    $uid = $wrapper.dataset.sfuid; // e.g: data-sfuid="oEfVwYr6-2"
                    if(!SUPER.allConditions[form_id]['_element_'+$uid]){
                        SUPER.allConditions[form_id]['_element_'+$uid] = JSON.parse($json);
                    }
                    return;
                }
                $field_name = $field.name;
                if($json==='' && $this.dataset.ajax && $this.dataset.ajax==='true'){
                    // Retrieve the json via Ajax Request
                    // Skip if we already retrieved the conditions before
                    if(!SUPER.allConditions[form_id][$field_name]){
                        SUPER.hadAjaxRequest++;
                        $.ajax({
                            url: super_common_i18n.ajaxurl,
                            type: 'post',
                            data: {
                                action: 'super_retrieve_variable_conditions',
                                form_id: form_id,
                                field_name: $field_name,
                            },
                            success: function (response) {
                                response = JSON.parse(response);
                                SUPER.allConditions[form_id][response.field_name] = response.conditions;
                            },
                            complete: function(){
                                // Completed
                                SUPER.completedAjaxRequests++;
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                // eslint-disable-next-line no-console
                                console.log(xhr, ajaxOptions, thrownError);
                                alert('Could not retrieve variable conditions through Ajax request');
                            }
                        });
                    }
                }else{
                    SUPER.allConditions[form_id][$field_name] = JSON.parse($json);
                }
            }
        });
        var lookupDone = setInterval(function(args){
            //var size = Object.keys(SUPER.allConditions[form_id]).length;
            if((SUPER.hadAjaxRequest>0 && SUPER.hadAjaxRequest===SUPER.completedAjaxRequests) || SUPER.hadAjaxRequest===0){
                clearInterval(lookupDone);
                Object.keys(args.conditionalLogic).forEach(function(key) {
                    $prev_match_found = false;
                    $this = args.conditionalLogic[key];
                    args.currentTextarea = $this;
                    $wrapper = $this.closest('.super-shortcode');
                    $is_variable = false;
                    $is_validate = false;
                    if($this.classList.contains('super-variable-conditions')){
                        $is_variable = true;
                        $action = $wrapper.dataset.conditionalVariableAction;
                    }else{
                        if($this.classList.contains('super-validate-conditions')){
                            $is_validate = true;
                            $action = 'show';
                            $trigger = 'one';
                        }else{
                            $trigger = $wrapper.dataset.conditionalTrigger;
                            $action = $wrapper.dataset.conditionalAction;
                        }
                    }

                    // Check if condition is a variable condition, also check if this is a text field, and if the form is being submitted.
                    // If all are true, we must skip this condition to make sure any manual input data won't be reset/overwritten
                    if( ($is_variable===true) && ($wrapper.classList.contains('super-text')===true) && (args.doingSubmit===true) ) {
                        return false;                
                    }

                    $json = $this.value;
                    if(($action) && ($action!='disabled')){
                        $field = false;
                        if($wrapper.closest('.super-field')){
                            $field = $wrapper.closest('.super-field').querySelector('.super-shortcode-field');
                        }
                        $conditions = false;
                        $currentFieldValue = undefined;
                        if(!$field) {
                            $uid = $wrapper.dataset.sfuid; // e.g: data-sfuid="oEfVwYr6-2"
                            if(SUPER.allConditions[form_id]['_element_'+$uid]){
                                $conditions = JSON.parse(JSON.stringify(SUPER.allConditions[form_id]['_element_'+$uid]));
                            }
                        }else{
                            $currentFieldValue = $field.value;
                            if(SUPER.allConditions[form_id][$field.name]){
                                $conditions = JSON.parse(JSON.stringify(SUPER.allConditions[form_id][$field.name]));
                            }
                        }
                        if($conditions){
                            $total = 0;
                            $match_found = 0;
                            Object.keys($conditions).forEach(function(key) {
                                if(!$prev_match_found){
                                    $total++;
                                    v = $conditions[key];
                                    // @since 3.5.0 - make sure {tags} are replaced with the correct field value to check conditional logic
                                    args.value = v.v;
                                    v.v = SUPER.update_variable_fields.replace_tags(args);
                                    args.value = v.va;
                                    v.va = SUPER.update_variable_fields.replace_tags(args);
                                    args.value = v.f;
                                    args.bwc = true;
                                    $shortcode_field_value = SUPER.update_variable_fields.replace_tags(args);
                                    args.value = v.fa;
                                    args.bwc = true;
                                    $shortcode_field_and_value = SUPER.update_variable_fields.replace_tags(args);
                                    delete args.bwc;
                                    $continue = false;
                                    $continue_and = false;
                                    $skip = false;
                                    $skip_and = false;
                                    // If conditional field selectors don't contain curly braces, then append and prepend them for backwards compatibility
                                    if(v.f!=='' && v.f.indexOf('{')===-1) v.f = '{'+v.f+'}';
                                    if(typeof v.fa !== 'undefined' && v.fa!=='' && v.fa.indexOf('{')===-1) v.fa = '{'+v.fa+'}';

                                    while (($v = SUPER.tagsRegex.exec(v.f)) !== null) {
                                        // This is necessary to avoid infinite loops with zero-width matches
                                        if ($v.index === SUPER.tagsRegex.lastIndex) {
                                            SUPER.tagsRegex.lastIndex++;
                                        }
                                        $field_name = $v[1].split(';')[0];
                                        if($field_name=='dynamic_column_counter'){
                                            continue;
                                        }
                                        $shortcode_field = SUPER.field(args.form, $field_name);
                                        if(!$shortcode_field) {
                                            $continue = true;
                                            continue;
                                        }
                                        $skip = SUPER.has_hidden_parent($shortcode_field, false, false);
                                        $parent = $shortcode_field.closest('.super-shortcode');
                                    }
                                    if(v.a!==''){ 
                                        if(v.a==='and' && $continue) return;
                                        while (($v = SUPER.tagsRegex.exec(v.fa)) !== null) {
                                            // This is necessary to avoid infinite loops with zero-width matches
                                            if ($v.index === SUPER.tagsRegex.lastIndex) {
                                                SUPER.tagsRegex.lastIndex++;
                                            }
                                            $field_name = $v[1].split(';')[0];
                                            if($field_name=='dynamic_column_counter'){
                                                continue;
                                            }
                                            $shortcode_field_and = SUPER.field(args.form, $field_name);
                                            if(!$shortcode_field_and){
                                                $continue_and = true;
                                                continue;
                                            }
                                            $skip_and = SUPER.has_hidden_parent($shortcode_field_and, false, false);
                                            $parent_and = $shortcode_field_and.closest('.super-shortcode');
                                        }
                                        if(v.a==='or' && !$continue_and){
                                            $continue = false;
                                        }
                                    }
                                    if($continue || $continue_and) return;
                                    if( (v.a==='and' && ($skip || $skip_and) && !$is_variable) ||
                                    (v.a==='or' && ($skip && $skip_and) && !$is_variable) ) {
                                        // Exclude conditionally
                                    }else{
                                        $shortcode_field_value = SUPER.return_dynamic_tag_value($($parent), $shortcode_field_value);
                                        $shortcode_field_and_value = SUPER.return_dynamic_tag_value($($parent_and), $shortcode_field_and_value);
                                        if(!$shortcode_field_value) $shortcode_field_value = '';
                                        if(!$shortcode_field_and_value) $shortcode_field_and_value = '';
                                        // Generate correct value before checking conditional logic
                                        $shortcode_field_value = SUPER.conditional_logic.get_field_value(v.l, $shortcode_field_value, $shortcode_field, $parent);
                                        // Generate correct and value before checking conditional logic
                                        if(v.a && v.a!==''){
                                            $shortcode_field_and_value = SUPER.conditional_logic.get_field_value(v.la, $shortcode_field_and_value, $shortcode_field_and, $parent_and);
                                        }
                                        if($is_variable){
                                            $match_found = SUPER.conditional_logic.match_found(0, v, $shortcode_field_value, $shortcode_field_and_value, $parent , $parent_and, true);
                                            if( v.a=='and' ) {
                                                if($match_found>=2) {
                                                    $prev_match_found = true;
                                                    if( v.n!=='' ) {
                                                        args.value = v.n;
                                                        v.n = SUPER.update_variable_fields.replace_tags(args);
                                                    }
                                                    if($field.classList.contains('super-timepicker') && v.n.length===13){
                                                        v.n = SUPER.timestampTo24h(v.n);
                                                    } 
                                                    $field.value = v.n;
                                                }else{
                                                    // @important - we must check if changed field is undefined
                                                    // because when either "Save form progression" or "Retrieve last entry data" is enabled
                                                    // in combination with a variable field depending on a other field to retrieve it values
                                                    // (think of, search customer field, e.g: autosuggest feature with customer data)
                                                    // because it would try to re-populate the variable field (could be text field) with it's data
                                                    // but on page load this text field might already contain data which we rather not override :)
                                                    // @important - or should we grab the data-default-value attribute?
                                                    if(typeof args.el !== 'undefined'){
                                                        $field.value = ''; // No match was found just set to an empty string
                                                    }
                                                }
                                            }else{
                                                if($match_found>=1) {
                                                    $prev_match_found = true;
                                                    if( v.n!=='' ) {
                                                        args.value = v.n;
                                                        v.n = SUPER.update_variable_fields.replace_tags(args);
                                                    }
                                                    if($field.classList.contains('super-timepicker') && v.n.length===13){
                                                        v.n = SUPER.timestampTo24h(v.n);
                                                    } 
                                                    $field.value = v.n;
                                                }else{
                                                    // @important - we must check if changed field is undefined
                                                    // because when either "Save form progression" or "Retrieve last entry data" is enabled
                                                    // in combination with a variable field depending on a other field to retrieve it values
                                                    // (think of, search customer field, e.g: autosuggest feature with customer data)
                                                    // because it would try to re-populate the variable field (could be text field) with it's data
                                                    // but on page load this text field might already contain data which we rather not override :)
                                                    // @important - or should we grab the data-default-value attribute?
                                                    if(typeof args.el !== 'undefined'){
                                                        $field.value = ''; // No match was found just set to an empty string
                                                    }
                                                }
                                            }
                                            // No matter what, we will always apply and then remove the 'entry-data' attribute if it existed
                                            if(typeof $field.dataset.entryValue !== 'undefined'){
                                                $field.value = $field.dataset.entryValue;
                                                delete $field.dataset.entryValue;
                                            }
                                            // Add the field to the updated fields array 
                                            if($currentFieldValue!==$field.value){
                                                $updated_variable_fields[$field.name] = $field;
                                                $currentFieldValue = $field.value;
                                            }
                                        }else{
                                            $match_found = SUPER.conditional_logic.match_found($match_found, v, $shortcode_field_value, $shortcode_field_and_value, $parent , $parent_and, false);
                                        }
                                    }
                                }
                            });
                            if(!$is_variable){
                                $hide_wrappers = [];
                                $show_wrappers = [];
                                $changed_wrappers = [];
                                if($trigger=='all'){
                                    if($match_found==$total){
                                        if( ($action==='show') && (!$wrapper.classList.contains('super-conditional-visible')) ){
                                            $changed_wrappers.push($wrapper);
                                            $show_wrappers.push($wrapper);
                                        }
                                        if( ($action==='hide') && ($wrapper.classList.contains('super-conditional-visible')) ){
                                            $changed_wrappers.push($wrapper);
                                            $hide_wrappers.push($wrapper);
                                        }
                                        if( ($action==='readonly') && (!$wrapper.classList.contains('super-readonly')) ){
                                            $hide_wrappers.push($wrapper);
                                        }
                                    }else{
                                        if( ($action==='show') && ($wrapper.classList.contains('super-conditional-visible')) ){
                                            $changed_wrappers.push($wrapper);
                                            $hide_wrappers.push($wrapper);
                                        }
                                        if( ($action==='hide') && (!$wrapper.classList.contains('super-conditional-visible')) ){
                                            $changed_wrappers.push($wrapper);
                                            $show_wrappers.push($wrapper);
                                        }
                                        if( ($action==='readonly') && ($wrapper.classList.contains('super-readonly')) ){
                                            $show_wrappers.push($wrapper);
                                        }
                                    }
                                }else{
                                    if($match_found!==0){
                                        if( ($action==='show') && (!$wrapper.classList.contains('super-conditional-visible')) ){
                                            $changed_wrappers.push($wrapper);
                                            $show_wrappers.push($wrapper);
                                        }
                                        if( ($action==='hide') && ($wrapper.classList.contains('super-conditional-visible')) ){
                                            $changed_wrappers.push($wrapper);
                                            $hide_wrappers.push($wrapper);
                                        }
                                        if( ($action==='readonly') && (!$wrapper.classList.contains('super-readonly')) ){
                                            $hide_wrappers.push($wrapper);
                                        }
                                    }else{
                                        if( ($action==='show') && ($wrapper.classList.contains('super-conditional-visible')) ){
                                            $changed_wrappers.push($wrapper);
                                            $hide_wrappers.push($wrapper);
                                        }
                                        if( ($action==='hide') && (!$wrapper.classList.contains('super-conditional-visible')) ){
                                            $changed_wrappers.push($wrapper);
                                            $show_wrappers.push($wrapper);
                                        }
                                        if( ($action==='readonly') && ($wrapper.classList.contains('super-readonly')) ){
                                            $show_wrappers.push($wrapper);
                                        }
                                    }
                                }
                                
                                // Check if we are conditionally validating a field
                                if($is_validate){
                                    // Show wrappers
                                    Object.keys($show_wrappers).forEach(function() {
                                        $validation_error = true;
                                    });
                                }else{
                                    Object.keys($hide_wrappers).forEach(function(key) {
                                        if($hide_wrappers[key].classList.contains('super-error-active')){
                                            $hide_wrappers[key].classList.remove('super-error-active');
                                        }
                                        var $innerNodes = $hide_wrappers[key].querySelectorAll('.super-error-active');
                                        Object.keys($innerNodes).forEach(function(ikey) {
                                            $innerNodes[ikey].classList.remove('super-error-active');
                                        });
                                        // Check if parent is multi-part, tab, accordion, if so remove error class if no errors found
                                        // Remove error class from Multi-part if no more errors where found
                                        SUPER.remove_error_status_parent_layout_element($, $hide_wrappers[key]);
                                    });
                                    if($action=='readonly'){
                                        // Hide wrappers
                                        Object.keys($hide_wrappers).forEach(function(key) {
                                            $hide_wrappers[key].classList.add('super-readonly');
                                        });
                                        // Show wrappers
                                        Object.keys($show_wrappers).forEach(function(key) {
                                            $show_wrappers[key].classList.remove('super-readonly');
                                        });
                                    }else{
                                        // Hide wrappers
                                        Object.keys($hide_wrappers).forEach(function(key) {
                                            $hide_wrappers[key].classList.add('super-conditional-hidden');
                                            $hide_wrappers[key].classList.remove('super-conditional-visible');
                                        });
                                        // Show wrappers
                                        Object.keys($show_wrappers).forEach(function(key) {
                                            $show_wrappers[key].classList.remove('super-conditional-hidden');
                                            $show_wrappers[key].classList.add('super-conditional-visible');
                                            // Resize toggle elements
                                            SUPER.resize_toggle_element($show_wrappers[key]);
                                            // Reposition slider dragger
                                            SUPER.reposition_slider_element($show_wrappers[key], true);
                                            // Resize signatures
                                            var i, nodes = $show_wrappers[key].querySelectorAll('.super-signature.super-initialized');
                                            for(i=0; i<nodes.length; ++i){
                                                nodes[i].classList.remove('super-initialized');
                                            }
                                            if(typeof SUPER.init_signature === 'function'){
                                                SUPER.init_signature();
                                            }
                                        });
                                        // @since 2.4.0 - call change blur hook on the fields inside the update column
                                        Object.keys($changed_wrappers).forEach(function(key) {
                                            $inner = $changed_wrappers[key].querySelectorAll('.super-shortcode-field');
                                            Object.keys($inner).forEach(function(key) {
                                                $parent = $inner[key].closest('.super-shortcode');
                                                $element = $parent.querySelector('div[data-fields]');
                                                if($element){
                                                    $data_fields = $element.dataset.fields;
                                                    if($data_fields){
                                                        $data_fields = $data_fields.split('}');
                                                        Object.keys($data_fields).forEach(function(key) {
                                                            v = $data_fields[key];
                                                            if(v!==''){
                                                                v = v.replace('{','');
                                                                $field = SUPER.field(args.form, v);
                                                                if($field){
                                                                    SUPER.after_field_change_blur_hook({el: $field, form: args.form, skip: true});
                                                                }
                                                            }
                                                        });
                                                    }
                                                }
                                                SUPER.after_field_change_blur_hook({el: $inner[key], form: args.form, skip: true});
                                            });
                                        });
                                    }
                                }
                            }
                        }
                    }
                });

                if($is_validate){
                    if(typeof args.callback === 'function'){
                        args.callback($validation_error);
                    }
                }
                
                // @since 2.3.0 - update conditional logic and other variable fields based on the updated variable field
                $.each($updated_variable_fields, function( index, field ) {
                    if(field.value===''){
                        field.closest('.super-shortcode').classList.remove('super-filled');
                    }else{
                        field.closest('.super-shortcode').classList.add('super-filled');
                    }
                    SUPER.after_field_change_blur_hook({el: field});
                });

                // @since 1.4
                if(!$is_variable){
                    SUPER.update_variable_fields(args);
                }
            }
        // the function is called instantly the first time, this is required in case there was no javascript being executed.
        // if there was an Ajax request being made, then loop every 10-15ms (different accross browser) and try to execute the rest of the functions as soon as we can
        }, 0, args); 
    };

    // @since 5.0.120 Filter foreach() statements
    SUPER.filter_foreach_statements = function($htmlElement, $counter, $depth, $html, $fileLoopRows, formId, originalFormReference){
        // Before we continue replace any foreach(file_upload_fieldname)
        var regex = /(<%|{|foreach\()([-_a-zA-Z0-9]{1,})(\[.*?\])?(_\d{1,})?(?:;([-_a-zA-Z0-9]{1,}))?(%>|}|\):)/g;
        var m;
        var replaceTagsWithValue = {};
        //var oNames = {};
        //replaceTagsWithValue['<%counter%>'] = '';
        while ((m = regex.exec($html)) !== null) {
            // This is necessary to avoid infinite loops with zero-width matches
            if (m.index === regex.lastIndex) {
                regex.lastIndex++;
            }
            var $o = (m[0] ? m[0] : ''); // original name
            var $start = (m[1] ? m[1] : ''); // starts with e.g: foreach( or <%
            if($start!=='foreach(') continue;
            var $n = (m[2] ? m[2] : ''); // name
            var $d = (m[3] ? m[3] : ''); // depth
            var $c = (m[4] ? m[4] : ''); // counter e.g: _2 or _3 etc.
            var $s = (m[5] ? m[5] : ''); // suffix
            if($s!=='') $s = ';'+$s;
            var $end = (m[6] ? m[6] : ''); // ends with e.g: ): or %>
            var childParentIndex = $($htmlElement).parents('.super-duplicate-column-fields:eq(0)').index();
            if(childParentIndex>0){
                $html = SUPER.replaceAll($html, 'foreach('+$n+$d+$s+')', 'foreach('+$n+$d+'_'+(childParentIndex+1)+$s+')');
            }

            // tmp if(fieldType.type==='file' || fieldType.type==='checkbox' || fieldType.type==='dropdown'){
            // tmp     var childParentIndex = $($htmlElement).parents('.super-duplicate-column-fields:eq(0)').index();
            // tmp     if(childParentIndex>0){
            // tmp     }
            // tmp }

            // tmp if(originalFormReference.classList.contains('super-duplicate-column-fields')){
            // tmp     var childParentIndex = $(originalFormReference).index();
            // tmp     oNames[$n+$d+'_'+(childParentIndex+1)] = $n+$d;
            // tmp     var fieldType = SUPER.get_field_type(originalFormReference, $n+$d+'_'+(childParentIndex+1));
            // tmp }else{
            // tmp     oNames[$n+$d] = $n+$d;
            // tmp     var fieldType = SUPER.get_field_type(originalFormReference, $n+$d);
            // tmp }
            // tmp if(fieldType.type==='file' || fieldType.type==='checkbox' || fieldType.type==='dropdown'){
            // tmp     var childParentIndex = $($htmlElement).parents('.super-duplicate-column-fields:eq(0)').index();
            // tmp     if(childParentIndex>0){
            // tmp         $html = SUPER.replaceAll($html, 'foreach('+$n+$d+$s+')', 'foreach('+$n+$d+'_'+(childParentIndex+1)+$s+')');
            // tmp     }
            // tmp }
        }
        // var findChildField = $(currentFieldParent).find('.super-shortcode-field[data-oname="'+$n+'"][data-olevels="'+$dr+'"]').first();
        // if(findChildField.length===0) continue;
        // var allParents = $(findChildField).parents('.super-duplicate-column-fields');
        // var childParentIndex = $(findChildField).parents('.super-duplicate-column-fields:eq(0)').index();
        // var suffix = [];
        // $(allParents).each(function(key){
        //     var currentParentIndex = $(this).index();  // e.g: 0, 1, 2
        //     if(key===0 && currentParentIndex===0){
        //         return;
        //     }
        //     suffix.push('['+currentParentIndex+']');
        // });
        // if(childParentIndex!==0){
        //     delete suffix[0];
        // }
        // var levels = suffix.reverse().join('');
        // if(childParentIndex!==0){
        //     replaceTagsWithValue[$o] = $start+$n+levels+'_'+(childParentIndex+1)+$s+$end;
        //     continue;
        // }
        // replaceTagsWithValue[$o] = $start+$n+levels+$c+$s+$end;

        if(typeof $counter === 'undefined') $counter = 0;
        if(typeof $fileLoopRows === 'undefined') $fileLoopRows = [];
        // Check if endforeach; was found otherwise skip it
        if($html.indexOf('endforeach;')===-1) {
            return $html;  
        }
        var $chars = $html.split(''),
            $prefix = '', // any content before loop starts
            $innerContent = '', // any content inside the loop
            $suffix = '', // any content after loop ends
            $captureSuffix = false,
            $captureFieldname = false,
            $captureContent = false,
            $fieldName = '',
            $skipUpTo = 0,
            $k,
            $v;

        Object.keys($chars).forEach(function(k) {
            $k = parseInt(k, 10);
            $v = $chars[k];
            if($skipUpTo!==0 && $skipUpTo > k){
                return;
            }
            if($captureSuffix){
                $suffix += $v;
                return;
            }
            if($captureFieldname){
                if( ($chars[$k] && $chars[$k]===')') &&
                    ($chars[$k+1] && $chars[$k+1]===':') ) {
                    $captureFieldname = false;
                    $captureSuffix = false;
                    $captureContent = true;
                    $skipUpTo = $k+2; // Skip up to key 8
                    return;
                }else{
                    $fieldName += $v;
                }
            }
            if($captureContent){
                if((($chars[$k]) && $chars[$k]==='f') &&
                (($chars[$k+1]) && $chars[$k+1]==='o') &&
                (($chars[$k+2]) && $chars[$k+2]==='r') &&
                (($chars[$k+3]) && $chars[$k+3]==='e') &&
                (($chars[$k+4]) && $chars[$k+4]==='a') &&
                (($chars[$k+5]) && $chars[$k+5]==='c') &&
                (($chars[$k+6]) && $chars[$k+6]==='h') &&
                (($chars[$k+7]) && $chars[$k+7]==='(')){
                    $depth++;
                }
                if((($chars[$k]) && $chars[$k]==='e') &&
                (($chars[$k+1]) && $chars[$k+1]==='n') &&
                (($chars[$k+2]) && $chars[$k+2]==='d') &&
                (($chars[$k+3]) && $chars[$k+3]==='f') &&
                (($chars[$k+4]) && $chars[$k+4]==='o') &&
                (($chars[$k+5]) && $chars[$k+5]==='r') &&
                (($chars[$k+6]) && $chars[$k+6]==='e') &&
                (($chars[$k+7]) && $chars[$k+7]==='a') &&
                (($chars[$k+8]) && $chars[$k+8]==='c') &&
                (($chars[$k+9]) && $chars[$k+9]==='h') &&
                (($chars[$k+10]) && $chars[$k+10]===';')){
                    $depth--;
                    $captureFieldname = false;
                    $captureContent = true;
                    $captureSuffix = false;
                    if($depth===0){
                        // foreach ended
                        // capture suffix
                        $captureFieldname = false;
                        $captureContent = false;
                        $captureSuffix = true;
                        $skipUpTo = $k+11; // Skip up to key 11
                        return;
                    }
                    // capture inner content including inner foreach items
                    $innerContent += $v;
                    return;
                }
                // capture inner content including inner foreach items
                $innerContent += $v;
            }
            if($depth===0){
                if((($chars[$k]) && $chars[$k]==='f') &&
                (($chars[$k+1]) && $chars[$k+1]==='o') &&
                (($chars[$k+2]) && $chars[$k+2]==='r') &&
                (($chars[$k+3]) && $chars[$k+3]==='e') &&
                (($chars[$k+4]) && $chars[$k+4]==='a') &&
                (($chars[$k+5]) && $chars[$k+5]==='c') &&
                (($chars[$k+6]) && $chars[$k+6]==='h') &&
                (($chars[$k+7]) && $chars[$k+7]==='(')){
                    $depth++;
                    $captureSuffix = false;
                    $captureContent = false;
                    $captureFieldname = true;
                    $skipUpTo = $k+8; // Skip up to key 8
                    return;
                }else{
                    $prefix += $v; // any content before loop starts
                }
            }else{
                // Depth is not 0
            }
        });
        var $original = $innerContent,
            $row = $innerContent,
            $field_name = $fieldName,
            $splitName = $field_name.split(';'),
            $field_name = $splitName[0],
            $value_n = ($splitName[1] ? $splitName[1] : ''),
            $original_field_name = $field_name,
            $i = 1,
            $rows = '';


        //var $oName = (oNames[$field_name] ? oNames[$field_name] : $field_name);
        //var currentField = $(originalFormReference).find('.super-shortcode-field[data-oname="'+$oName+'"], .super-active-files[data-oname="'+$oName+'"]').first()[0];
        //var fieldType = SUPER.get_field_type(originalFormReference, $field_name);
        //var currentField = fieldType.field;
        var currentField = SUPER.field(originalFormReference, $field_name);
        while( currentField ) {
            var currentFieldParent = $(currentField).parents('.super-duplicate-column-fields:eq(0)');
            //var regex = /(<%|{|foreach\()([-_a-zA-Z0-9]{1,})(\[.*?\])?(_\d{1,})?(?:;([-_a-zA-Z0-9]{1,}))?(%>|}|\):)/g;
            regex = /(<%|{|foreach\(|isset\(|!isset\(|if\(!isset\(|if\(isset\()([-_a-zA-Z0-9]{1,})(\[.*?\])?(_\d{1,})?(?:;([-_a-zA-Z0-9]{1,}))?(%>|}|\):|\)\):)/g;
            $row = $original;
            replaceTagsWithValue = {};
            //replaceTagsWithValue['<%counter%>'] = '';
            while ((m = regex.exec($row)) !== null) {
                // This is necessary to avoid infinite loops with zero-width matches
                if (m.index === regex.lastIndex) {
                    regex.lastIndex++;
                }
                $o = (m[0] ? m[0] : ''); // original name
                $start = (m[1] ? m[1] : ''); // starts with e.g: foreach( or <%
                $n = (m[2] ? m[2] : ''); // name
                $d = (m[3] ? m[3] : ''); // depth
                $c = (m[4] ? m[4] : ''); // counter e.g: _2 or _3 etc.
                $s = (m[5] ? m[5] : ''); // suffix
                if($s!=='') $s = ';'+$s;
                $end = (m[6] ? m[6] : ''); // ends with e.g: ): or %>
                var fieldType = SUPER.get_field_type(originalFormReference, $field_name);
                if(fieldType.type==='file' && $value_n==='loop'){
                    // Loop over all current files
                    if(SUPER.files[formId]){
                        var files = SUPER.files[formId][$field_name];
                        if(!files){
                            $row = '';
                        }else{
                            if(files.length===0) $row = '';
                            //$rows = '';
                            for(var x=0; x<files.length; x++){
                                replaceTagsWithValue = [];
                                replaceTagsWithValue['<%counter%>'] = (x+1);
                                // This retrieves the total amount of files (uploaded and yet to be uploaded)
                                var totalFiles = currentField.closest('.super-shortcode').querySelectorAll('.super-fileupload-files > div').length;
                                replaceTagsWithValue['<%total_files%>'] = totalFiles;
                                replaceTagsWithValue['<%total%>'] = totalFiles;
                                replaceTagsWithValue['<%count%>'] = totalFiles;
                                // This retrieves the total amount of files selected for upload (yet to be uploaded files)
                                totalFiles = currentField.closest('.super-shortcode').querySelectorAll('.super-fileupload-files > div:not(.super-uploaded)').length;
                                replaceTagsWithValue['<%new_count%>'] = totalFiles;
                                // This retrieves the total amount of files that are already uploaded previously
                                totalFiles = currentField.closest('.super-shortcode').querySelectorAll('.super-fileupload-files > div.super-uploaded').length;
                                replaceTagsWithValue['<%existing_count%>'] = totalFiles;
                                // Field label
                                replaceTagsWithValue['<%label%>'] = currentField.dataset.email;
                                replaceTagsWithValue['<%email%>'] = currentField.dataset.email;
                                replaceTagsWithValue['<%email_label%>'] = currentField.dataset.email;
                                // Name
                                replaceTagsWithValue['<%name%>'] = files[x].name;
                                replaceTagsWithValue['<%basename%>'] = files[x].name;
                                // URL
                                replaceTagsWithValue['<%url%>'] = files[x].url;
                                replaceTagsWithValue['<%src%>'] = files[x].url;
                                // Size
                                replaceTagsWithValue['<%filesize%>'] = files[x].size;
                                replaceTagsWithValue['<%size%>'] = files[x].size;
                                // Type
                                replaceTagsWithValue['<%type%>'] = files[x].type;
                                replaceTagsWithValue['<%mime%>'] = files[x].type;
                                // Field Label
                                replaceTagsWithValue['<%label%>'] = files[x].label;
                                // Extension
                                var f = SUPER.get_file_name_and_extension(files[x].name);
                                replaceTagsWithValue['<%ext%>'] = f.ext.toLowerCase();
                                replaceTagsWithValue['<%extension%>'] = f.ext.toLowerCase();
                                // Attachment
                                replaceTagsWithValue['<%attachment_id%>'] = files[x].attachment;
                                replaceTagsWithValue['<%attachment%>'] = files[x].attachment;
                                var key;
                                $row = $original;
                                for(key in replaceTagsWithValue) {
                                    $row = SUPER.replaceAll($row, key, replaceTagsWithValue[key])
                                }
                                $rows += $row;
                            }
                            replaceTagsWithValue = [];
                            $row = '';
                        }
                    }
                }else{
                    if($value_n==='loop'){
                        // Check if checkbox field
                        if(fieldType.type==='checkbox'){
                            var y, items = fieldType.field.parentNode.querySelectorAll('.super-item.super-active');
                            $row = '';
                            for (x = 0; x < items.length; x++) {
                                // @since 3.6.0 - check if we want to return the label instead of a value
                                replaceTagsWithValue = [];
                                replaceTagsWithValue['<%counter%>'] = (x+1);
                                replaceTagsWithValue['<%label%>'] = items[x].textContent;
                                var itemValue = items[x].querySelector('input').value.toString().split(';');
                                if($s===0){
                                    replaceTagsWithValue['<%value%>'] = itemValue[0];
                                }else{
                                    if(typeof itemValue[($s-1)]==='undefined'){
                                        replaceTagsWithValue['<%value%>'] = itemValue[0];
                                    }else{
                                        replaceTagsWithValue['<%value%>'] = itemValue[($s-1)];
                                    }
                                }
                                $row = $original;
                                for(y in replaceTagsWithValue) {
                                    $row = SUPER.replaceAll($row, y, replaceTagsWithValue[y])
                                }
                                $rows += $row;
                            }
                            replaceTagsWithValue = [];
                            $row = '';
                        }
                        if(fieldType.type==='dropdown'){
                            items = fieldType.field.parentNode.querySelectorAll('.super-dropdown-list .super-item.super-active:not(.super-placeholder)');
                            $row = '';
                            for (x = 0; x < items.length; x++) {
                                // @since 3.6.0 - check if we want to return the label instead of a value
                                replaceTagsWithValue = [];
                                replaceTagsWithValue['<%counter%>'] = (x+1);
                                replaceTagsWithValue['<%label%>'] = (items[x].dataset.searchValue ? items[x].dataset.searchValue : items[x].textContent);
                                var itemValue = items[x].dataset.value.toString().split(';');
                                if($s===0){
                                    replaceTagsWithValue['<%value%>'] = itemValue[0];
                                }else{
                                    if(typeof itemValue[($s-1)]==='undefined'){
                                        replaceTagsWithValue['<%value%>'] = itemValue[0];
                                    }else{
                                        replaceTagsWithValue['<%value%>'] = itemValue[($s-1)];
                                    }
                                }
                                $row = $original;
                                for(y in replaceTagsWithValue) {
                                    $row = SUPER.replaceAll($row, y, replaceTagsWithValue[y])
                                }
                                $rows += $row;
                            }
                            replaceTagsWithValue = [];
                            $row = '';
                        }
                    }
                    if($n==='counter'){
                        var findChildField = $(currentFieldParent).find('.super-shortcode-field[data-oname="'+fieldType.oname+'"], .super-active-files[data-oname="'+fieldType.oname+'"]').first();
                        if(findChildField.length===0) {
                            continue;
                        } 
                        var childParentIndex = $(findChildField).parents('.super-duplicate-column-fields:eq(0)').index();
                        if(childParentIndex!==0) $c = '_'+(childParentIndex+1);
                        var levels = findChildField[0].closest('.super-column[data-duplicate-limit]').dataset.level;
                        if(!levels) levels = '';
                        replaceTagsWithValue['<%counter%>'] = $start+fieldType.oname+levels+$c+';index'+$end;
                        continue;
                    }else{
                        var findChildField = $(currentFieldParent).find('.super-shortcode-field[data-oname="'+fieldType.oname+'"], .super-active-files[data-oname="'+fieldType.oname+'"]').first();
                    }
                    if(findChildField.length===0) {
                        continue;
                    } 
                    var childParentIndex = $(findChildField).parents('.super-duplicate-column-fields:eq(0)').index();
                    if(childParentIndex!==0) $c = '_'+(childParentIndex+1);
                    var levels = findChildField[0].closest('.super-column[data-duplicate-limit]').dataset.level;
                    if(!levels) levels = '';
                    if($n==='counter'){
                        replaceTagsWithValue['<%counter%>'] = $start+fieldType.oname+levels+$c+';index'+$end;
                    }else{
                        replaceTagsWithValue[$o] = $start+$n+levels+$c+$s+$end;
                        replaceTagsWithValue['<%counter%>'] = $start+$n+levels+$c+';index'+$end;
                    }

                }
            }
            var key;
            for(key in replaceTagsWithValue) {
                $row = SUPER.replaceAll($row, key, replaceTagsWithValue[key]);
            }
            if($htmlElement.closest('.super-duplicate-column-fields')){
                $rows += $row;
                break;
            }else{
                $i++;
                $field_name = $original_field_name+'_'+$i;
                currentField = SUPER.field(originalFormReference, $field_name);
                if(currentField){
                    //$row = SUPER.replaceAll($row, '<%counter%>', '<%'+$field_name+';index%>');
                    //var dynamicColumnIndex = $(currentField).parents('.super-duplicate-column-fields:eq(0)').index()+1;
                    //$row = SUPER.replaceAll($row, '<%counter%>', dynamicColumnIndex);
                }
                $rows += $row;
            }
        }

        $rows = $prefix + $rows + $suffix;
        $innerContent = $innerContent.split($original).join($rows);
        $counter++;
        return SUPER.filter_foreach_statements($htmlElement, $counter, $depth, $innerContent, $fileLoopRows, formId, originalFormReference);
    };

    // @since 4.6.0 Filter if() statements
    SUPER.filter_if_statements = function($html){
        // If does not contain 'endif;' we can just return the `$html` without doing anything
        if($html.indexOf('endif;')===-1) {
            return $html;  
        }
        var re = /\s*['|"]?(.*?)['|"]?\s*(==|!=|>=|<=|>|<|\?\?|!\?\?)\s*['|"]?(.*?)['|"]?\s*$/,
            m,
            v,
            show_counter,
            method,
            conditions,
            array = $html.split(''),
            if_index = 0,
            skip_up_to = 0,
            capture_elseifcontent = false,
            capture_conditions = false,
            capture_suffix = false,
            statements = [],
            prefix = '',
            first_if_found = false,
            depth = 0,
            result = '',
            i,
            ci,
            cv,
            v1,
            v2,
            show,
            operator;

        Object.keys(array).forEach(function(k) {
            k = parseInt(k, 10);
            v = array[k];
            if(skip_up_to!==0 && skip_up_to > k){
                return;
            }
            if(!SUPER.if_match(array, k) && first_if_found===false ) {
                prefix += v;
            }else{
                first_if_found = true;
                if(capture_conditions){
                    if( ((typeof array[k] !== 'undefined') && array[k]===')') && 
                        ((typeof array[k+1] !== 'undefined') && (array[k+1]===':')) ) {
                        capture_elseifcontent = false;
                        capture_suffix = false;
                        capture_conditions = false;
                        skip_up_to = k+2;
                        return;
                    }
                    if(typeof statements[if_index] === 'undefined'){
                        statements[if_index] = [];
                    }
                    if(typeof statements[if_index].conditions === 'undefined'){
                        statements[if_index].conditions = '';
                    }
                    statements[if_index].conditions += v;
                    return;
                }
                if(depth===0){
                    if(SUPER.if_match(array, k)){
                        if_index++;
                        depth++;
                        capture_elseifcontent = false;
                        capture_suffix = false;
                        capture_conditions = true;
                        skip_up_to = k+3;
                        return;
                    }
                }else{
                    if(SUPER.if_match(array, k)){
                        depth++;
                    }
                }
                if( ((typeof array[k] !== 'undefined') && array[k]==='e') && 
                    ((typeof array[k+1] !== 'undefined') && array[k+1]==='n') && 
                    ((typeof array[k+2] !== 'undefined') && array[k+2]==='d') && 
                    ((typeof array[k+3] !== 'undefined') && array[k+3]==='i') && 
                    ((typeof array[k+4] !== 'undefined') && array[k+4]==='f') && 
                    ((typeof array[k+5] !== 'undefined') && array[k+5]===';') ) {
                    depth--;
                    if(depth===0){
                        capture_elseifcontent = false;
                        capture_conditions = false;
                        capture_suffix = true;
                        skip_up_to = k+6;
                        return;
                    }
                }
                if(depth==1){
                    if( ((typeof array[k] !== 'undefined') && array[k]==='e') && 
                        ((typeof array[k+1] !== 'undefined') && array[k+1]==='l') &&
                        ((typeof array[k+2] !== 'undefined') && array[k+2]==='s') &&
                        ((typeof array[k+3] !== 'undefined') && array[k+3]==='e') &&
                        ((typeof array[k+4] !== 'undefined') && array[k+4]==='i') &&
                        ((typeof array[k+5] !== 'undefined') && array[k+5]==='f') &&
                        ((typeof array[k+6] !== 'undefined') && array[k+6]===':') ) {
                        capture_elseifcontent = true;
                        capture_suffix = false;
                        capture_conditions = false;
                        skip_up_to = k+7;
                        return;
                    }
                }
                if(depth===0 && capture_suffix){
                    if(typeof statements[if_index].suffix === 'undefined') statements[if_index].suffix = ''; 
                    statements[if_index].suffix += v;
                    return;
                }
                if(depth>=1 && capture_elseifcontent){
                    if(typeof statements[if_index].elseif_content === 'undefined') statements[if_index].elseif_content = '';
                    statements[if_index].elseif_content += v;
                    return;
                }
                if(depth>=1){
                    if(typeof statements[if_index].inner_content === 'undefined') statements[if_index].inner_content = '';
                    statements[if_index].inner_content += v;
                    return;
                }
            }
        });

        for (i = 0; i < statements.length; i++) { 
            if(typeof statements[i]==='undefined') continue;
            v = statements[i];
            show_counter = 0;
            method = '&&';
            conditions = v.conditions.split('&&');
            if(conditions.length==1){
                conditions = v.conditions.split('||');
                if(conditions.length>1){
                    method = '||';
                }
            }
            for (ci = 0; ci < conditions.length; ci++) {
                if(typeof conditions[ci]==='undefined') continue;
                cv = conditions[ci];
                if ((m = re.exec(cv)) !== null) {
                    v1 = m[1];
                    operator = m[2];
                    v2 = m[3];
                    show = false;
                    if(operator==='==' && v1==v2) show = true;
                    if(operator==='!=' && v1!=v2) show = true;
                    if(operator==='>=' && v1>=v2) show = true;
                    if(operator==='<=' && v1<=v2) show = true;
                    if(operator==='>' && v1>v2) show = true;
                    if(operator==='<' && v1<v2) show = true;
                    if(operator==='??' && v1.indexOf(v2) > -1) show = true;
                    if(operator==='!??' && v1.indexOf(v2) === -1) show = true;
                    if(show){
                        show_counter++;
                    }
                }
            }            
            if(method=='||' && show_counter>0){
                if(typeof v.inner_content !== 'undefined' && v.inner_content!=='') {
                    result += SUPER.filter_if_statements(v.inner_content);
                }
            }else{
                if(conditions.length===show_counter){
                    if(typeof v.inner_content !== 'undefined' && v.inner_content!=='') {
                        result += SUPER.filter_if_statements(v.inner_content);
                    }
                }else{
                    if(typeof v.elseif_content !== 'undefined' && v.elseif_content!=='') {
                        result += SUPER.filter_if_statements(v.elseif_content);
                    }
                }
            }
            if(typeof v.suffix !== 'undefined' && v.suffix!=='') {
                result += v.suffix;
            }
        }
        return prefix+result;
    };

    // @since 5.0.120 - Find foreach() match
    SUPER.foreach_match = function($array, $k){
        if( ((typeof $array[$k] !== 'undefined') && $array[$k]==='f') && 
            ((typeof $array[$k+1] !== 'undefined') && $array[$k+1]==='o') && 
            ((typeof $array[$k+2] !== 'undefined') && $array[$k+2]==='r') && 
            ((typeof $array[$k+3] !== 'undefined') && $array[$k+3]==='e') && 
            ((typeof $array[$k+4] !== 'undefined') && $array[$k+4]==='a') && 
            ((typeof $array[$k+5] !== 'undefined') && $array[$k+5]==='c') && 
            ((typeof $array[$k+6] !== 'undefined') && $array[$k+6]==='h') && 
            ((typeof $array[$k+7] !== 'undefined') && $array[$k+7]==='(') ) {
            return true;
        }
        return false;       
    };
    // @since 4.6.0 - Find if() match
    SUPER.if_match = function($array, $k){
        if( ((typeof $array[$k] !== 'undefined') && $array[$k]==='i') && 
            ((typeof $array[$k+1] !== 'undefined') && $array[$k+1]==='f') && 
            ((typeof $array[$k+2] !== 'undefined') && $array[$k+2]==='(') ) {
            return true;
        }
        return false;       
    };

    // @since 1.4 - Update variable fields
    SUPER.update_variable_fields = function(args){
		if(typeof args.el !== 'undefined'){
			var n = SUPER.get_original_field_name(args.el); 
			if(SUPER.cachedConditionalLogicByFieldName[n]){
				args.conditionalLogic = SUPER.cachedConditionalLogicByFieldName[n];
			}else{
				args.conditionalLogic = args.form.querySelectorAll('.super-variable-conditions[data-fields*="{'+n+'}"]');
				SUPER.cachedConditionalLogicByFieldName[n] = args.conditionalLogic;
			}
        }else{
            args.conditionalLogic = args.form.querySelectorAll('.super-variable-conditions');
        }
        if(typeof args.conditionalLogic !== 'undefined'){
            if(args.conditionalLogic.length!==0){
                SUPER.conditional_logic.loop(args);
            }
        }
    };
    SUPER.timestampTo24h = function(value){
        var ts = Number(value);
        try{
            var date = new Date(ts);
            var hours = date.getUTCHours().toString().padStart(2, '0');
            var minutes = date.getUTCMinutes().toString().padStart(2, '0');
            return hours+':'+minutes;
        }catch(error){
            console.error(error);
            return value;
        }
    }

    // @since 3.0.0 - replace variable field {tags} with actual field values
    SUPER.update_variable_fields.replace_tags = function(args){
        if(typeof args.bwc === 'undefined') args.bwc = false;
        if(typeof args.value !== 'undefined' && args.bwc){
            // If field name is empty do nothing
            if(args.value==='' || typeof args.value==='undefined') return '';
            // If field name doesn't contain any curly braces, then append and prepend them and continue;
            if(args.value.indexOf('{')===-1) args.value = '{'+args.value+'}';   
        }
        // First check if tag exists
        if(args.value==='' || typeof args.value==='undefined') return '';
        var indexMapping = args.value;
        var formId = SUPER.getFormIdByAttributeID(args.form);
        if(typeof SUPER.preFlightMappings==='undefined') SUPER.preFlightMappings = {};
        if(typeof SUPER.preFlightMappings[formId]==='undefined') SUPER.preFlightMappings[formId] = {fieldNames: [], tags: {}}
        if(SUPER.preFlightMappings[formId].tags[indexMapping]){
            if(indexMapping!=='{pdf_page}' && indexMapping!=='{dynamic_column_counter}') {
                // Check if this field name still exists, this might happen in a dynamic column when the element gets deleted.
                // Otherwise we must unset it
                var name = indexMapping.replace('{','').replace('}','').split(';')[0];
                if(!SUPER.field(args.form, name)) {
                    delete SUPER.preFlightMappings[formId].tags[indexMapping];
                    return ''; // does not exist, is not set, return empty string
                }
                return SUPER.preFlightMappings[formId].tags[indexMapping];
            }
        }
        if(args.form.classList.contains('super-generating-pdf')){
            // Must reference to original form (which is currently the placeholder)
            args.form = document.querySelector('#super-form-'+formId+'-placeholder');
        }
        if(typeof args.defaultValues === 'undefined') args.defaultValues = false;
        if(typeof args.target === 'undefined') args.target = null;
        var $array = [],
            $value = '',
            $i = 0,
            $name,
            $old_name,
            $options,
            $value_type,
            $value_n,
            $default_value,
            $parent,
            $text_field,
            $sum,
            $selected,
            $new_value,
            $match,
            key,
            $values,
            $element;

        while (($match = SUPER.tagsRegex.exec(args.value)) !== null) {
            if($match[0]==='{}') continue;
            $array[$i] = $match[1];
            $i++;
        }
        for ($i = 0; $i < $array.length; $i++) {
            $element = undefined; // @important!
            $name = $array[$i];
            if($name=='pdf_page' && typeof SUPER.pdf_tags !== 'undefined' ){
                SUPER.beforeReturnReplacedTagValue(args, formId, $name, indexMapping, SUPER.pdf_tags.pdf_page);
                return SUPER.pdf_tags.pdf_page;
            }
            if($name=='pdf_total_pages' && typeof SUPER.pdf_tags !== 'undefined' ){
                SUPER.beforeReturnReplacedTagValue(args, formId, $name, indexMapping, SUPER.pdf_tags.pdf_total_pages);
                return SUPER.pdf_tags.pdf_total_pages;
            }
            if($name=='dynamic_column_counter'){
                if(args.target){
                    args.value = $(args.target).parents('.super-duplicate-column-fields:eq(0)').index()+1;
                    SUPER.beforeReturnReplacedTagValue(args, formId, $name, indexMapping, args.value);
                    return args.value;
                }else{
                    if(args.currentTextarea){
                        args.value = $(args.currentTextarea).parents('.super-duplicate-column-fields:eq(0)').index()+1;
                        SUPER.beforeReturnReplacedTagValue(args, formId, $name, indexMapping, args.value);
                        return args.value;
                    }
                }
            }
            
            // @since 3.2.0 - Compatibility with advanced tags {option;2;int}
            $old_name = $name;
            $options = $name.toString().split(';');
            $name = $options[0]; // this is the field name e.g: {option;2} the variable $name would contain: option

            $value_type = 'var'; // return field value as 'var' or 'int' {field;2;var} to return varchar or {field;2;int} to return integer

            if(typeof $options[1] === 'undefined'){
                $value_n = 0;
            }else{
                $value_n = $options[1];
                // if the index value is 1 set it to 0 so it will return the value
                if($value_n==1){
                    $value_n = 0;
                }
                if(typeof $options[2] !== 'undefined'){
                    if( ($options[2]!='var') && ($options[2]!='int') ) {
                        $value_type = 'var';
                    }else{
                        $value_type = $options[2];
                    }
                }
            }

            $default_value = '';
            if($value_type=='int'){
                $default_value = 0;
            }

            // Use * for contains search
            // e.g: {field_*}
            // usage: $("input[id*='field_']")
            if($name.indexOf('*') >= 0){
                $name = $name.replace('*','');
                $element = SUPER.field(args.form, $name, '*');
            }
            // Use ^ for starts with search
            // e.g: {field_1_^}
            // usage: $("input[id^='field_1_']")
            if($name.indexOf('^') >= 0){
                $name = $name.replace('^','');
                $element = SUPER.field(args.form, $name, '^');
            }
            // Use $ for ends with search
            // e.g: {$_option}
            // usage: $("input[id$='_option']")
            if($name.indexOf('$') >= 0){
                $name = $name.replace('$','');
                $element = SUPER.field(args.form, $name, '$');
            }


            if(!$element) $element = SUPER.field(args.form, $name);
            // Return empty value in case the field does not exist
            if(!$element) return '';
            if($element){
                // Check if parent column or element is hidden (conditionally hidden)
                if( SUPER.has_hidden_parent($element, false, false) ) {
                    // Exclude conditionally
                    // Lets just replace the field name with 0 as a value
                    args.value = args.value.replace('{'+$old_name+'}', $default_value);
                    SUPER.beforeReturnReplacedTagValue(args, formId, $name, indexMapping, args.value);
                }else{
                    $parent = $element.closest('.super-shortcode');
                    if( !$element ) {
                        // Lets just replace the field name with 0 as a value
                        args.value = args.value.replace('{'+$old_name+'}', $default_value);
                        SUPER.beforeReturnReplacedTagValue(args, formId, $name, indexMapping, args.value);
                    }else{
                        $text_field = true;
                        $parent = $element.closest('.super-field');

                        if($value_n=='index') {
                            var dynamicParentIndex = $($element).parents('.super-duplicate-column-fields:eq(0)').index();
                            args.value = args.value.replace('{'+$old_name+'}', dynamicParentIndex+1);
                            SUPER.beforeReturnReplacedTagValue(args, formId, $name, indexMapping, args.value);
                            continue;
                        }

                        // Check if international phonenumber field
                        if($parent.classList.contains('super-int-phone-field')){
                            $text_field = false;
                            //var input = document.querySelector('#phone');
                            var intPhone = window.superTelInputGlobals.getInstance($element);
                            $value = intPhone.getNumber();
                        }

                        // Check if dropdown field
                        if($parent.classList.contains('super-dropdown')){
                            $text_field = false;
                            $sum = '';

                            // @since 3.2.0 - check if we want to return integer for this {tag}  e.g: {field;2;int}
                            if($value_type=='int') {
                                $sum = 0;
                            }

                            $selected = $parent.querySelectorAll('.super-dropdown-list .super-item.super-active:not(.super-placeholder)');
                            for (key = 0; key < $selected.length; key++) {
                                // @since 3.6.0 - check if we want to return the label instead of a value
                                if($value_n=='label'){
                                    $new_value = ($selected[key].dataset.searchValue ? $selected[key].dataset.searchValue : $selected[key].textContent);
                                }else{
                                    $new_value = $selected[key].dataset.value.toString().split(';');
                                    if($value_n===0){
                                        $new_value = $new_value[0];
                                    }else{
                                        // return default value if undefined
                                        if(typeof $new_value[($value_n-1)]==='undefined'){
                                            $new_value = $new_value[0];
                                        }else{
                                            $new_value = $new_value[($value_n-1)];
                                        }
                                    }
                                }
                                if(typeof $new_value==='undefined'){
                                    $new_value = '';
                                }

                                // @since 3.2.0 - check if we want to return integer for this {tag}  e.g: {field;2;int}
                                if($value_type=='int'){
                                    $sum += parseFloat($new_value);
                                }else{
                                    if($sum===''){
                                        $sum += $new_value;
                                    }else{
                                        $sum += ','+$new_value;
                                    }
                                }                         
                            }
                            $value = $sum;
                        }
                        // Check if checkbox field
                        if($parent.classList.contains('super-checkbox')){
                            $text_field = false;
                            $selected = $parent.querySelectorAll('.super-field-wrapper .super-item.super-active');
                            $values = '';
                            for (key = 0; key < $selected.length; key++) {
                                // @since 3.6.0 - check if we want to return the label instead of a value
                                if($value_n=='label'){
                                    if($values===''){
                                        $values += $selected[key].textContent;
                                    }else{
                                        $values += ', '+$selected[key].textContent;
                                    }
                                }else{
                                    if($values===''){
                                        $values += $selected[key].querySelector('input').value;
                                    }else{
                                        $values += ','+$selected[key].querySelector('input').value;
                                    }
                                }
                            }
                            $sum = '';
                            
                            // @since 3.2.0 - check if we want to return integer for this {tag}  e.g: {field;2;int}
                            if($value_type=='int') {
                                $sum = 0;
                            }
                            // @since 3.6.0 - check if we want to return the label instead of a value
                            if($value_n=='label'){
                                $sum += $values;
                            }else{
                                // @since 1.7.0 - checkbox compatibility with advanced tags like {field;2} etc.
                                var $new_value_array = $values.toString().split(',');
                                for (key = 0; key < $new_value_array.length; key++) {
                                    var v = $new_value_array[key].toString().split(';');
                                    if($value_n===0){
                                        $new_value = v[0];
                                    }else{
                                        $new_value = v[($value_n-1)];
                                    }
                                    if(typeof $new_value==='undefined'){
                                        $new_value = '';
                                    }

                                    // @since 3.2.0 - check if we want to return integer for this {tag}  e.g: {field;2;int}
                                    if($value_type=='int'){
                                        $sum += parseFloat($new_value);
                                    }else{
                                        if($sum===''){
                                            $sum += $new_value;
                                        }else{
                                            $sum += ','+$new_value;
                                        }
                                    }
                                }
                            }

                            $value = $sum;
                        }
                        // @since 1.7.0 - check for radio tags because it now can contain advanced tags like {field;2} etc.
                        if($parent.classList.contains('super-radio')){
                            $text_field = false;
                            $new_value = $element.value.toString().split(';');
                            if($value_n===0){
                                $new_value = $new_value[0];
                            }else{
                                $new_value = $new_value[($value_n-1)];
                            }
                            if(typeof $new_value==='undefined'){
                                $new_value = '';
                            }

                            // @since 3.6.0 - check if we want to return the label instead of a value
                            if($value_n=='label'){
                                $new_value = '';
                                $selected = $element.closest('.super-field').querySelector('.super-field-wrapper .super-active');
                                if($selected){
                                    $new_value = $selected.textContent;
                                }
                            }

                            // @since 3.2.0 - check if we want to return integer for this {tag}  e.g: {field;2;int}
                            if($value_type=='int'){
                                $value = parseFloat($new_value);
                            }else{
                                $value = ($new_value);
                            }
                        }

                        // @since 3.8.0 - check if variable field and check for advanced tags like {field;2} etc.
                        if($parent.classList.contains('super-hidden')){
                            if($parent.dataset.conditionalVariableAction=='enabled'){
                                $text_field = false;
                                // Check contains semicolon as separator, but also check if it doesn't contain a double quote,
                                // because this would indicate that the user is trying to create a serialized array,
                                // to update for instance meta data for a third party plugin
                                if($element.value.indexOf('"')!==-1){
                                    $value = $element.value;
                                }else{
                                    $new_value = $element.value.toString().split(';');
                                    if($value_n===0){
                                        $new_value = $new_value[0];
                                    }else{
                                        $new_value = $new_value[($value_n-1)];
                                    }
                                    if(typeof $new_value==='undefined'){
                                        $new_value = '';
                                    }
                                    if($value_type=='int'){
                                        $value = parseFloat($new_value);
                                    }else{
                                        $value = $new_value;
                                    }
                                }
                            }
                        }
                        
                        // Check if file upload field
                        if($parent.classList.contains('super-file')){
                            $text_field = false;
                            $new_value = '';
                            if($value_n===0) {
                                $value_n = 'allFileNames';
                            }
                            if($value_n=='email' || $value_n=='email_label' || $value_n=='emailLabel'){
                                $new_value = $parent.querySelector('.super-active-files').dataset.email;
                            }else{
                                var regex = /\[(\d*)\]/,
                                    i=0, // file index
                                    m, // regex matches
                                    totalFiles=0,
                                    files, 
                                    formId = SUPER.getFormIdByAttributeID(args.form);
                                if($value_n=='allFileNames' || $value_n=='allFileUrls' || $value_n=='allFileLinks' ){
                                    var allFileNames = '';
                                    var allFileUrls = '';
                                    var allFileLinks = '';
                                    if(SUPER.files[formId]){
                                        if(SUPER.files[formId][$element.name]){
                                            files = SUPER.files[formId][$element.name];
                                            for(i=0; i<files.length; i++){
                                                if($value_n=='allFileNames') allFileNames += SUPER.html_encode(files[i].name)+'<br />';
                                                if($value_n=='allFileUrls') allFileUrls += SUPER.html_encode(files[i].url)+'<br />';
                                                if($value_n=='allFileLinks') allFileLinks += '<a href="'+SUPER.html_encode(files[i].url)+'">'+SUPER.html_encode(files[i].name)+'</a><br />';
                                            }
                                        }
                                    }
                                    if($value_n=='allFileNames'){ $new_value = allFileNames; }
                                    if($value_n=='allFileUrls'){ $new_value = allFileUrls; }
                                    if($value_n=='allFileLinks'){ $new_value = allFileLinks; }
                                }else{
                                    if(SUPER.files[formId]){
                                        if(SUPER.files[formId][$element.name]){
                                            files = SUPER.files[formId][$element.name]
                                            m = regex.exec($value_n);
                                            if(m) i = parseInt(m[1],10);
                                            // This retrieves the total amount of files (uploaded and yet to be uploaded)
                                            if($value_n.substring(0, 11)==='total_files' || $value_n.substring(0, 5)==='total' || $value_n.substring(0, 5)==='count'){
                                                totalFiles = $parent.querySelectorAll('.super-fileupload-files > div').length;
                                                $new_value = totalFiles;
                                            }
                                            // This retrieves the total amount of files selected for upload (yet to be uploaded files)
                                            if($value_n.substring(0, 9)==='new_count'){
                                                totalFiles = $parent.querySelectorAll('.super-fileupload-files > div:not(.super-uploaded)').length;
                                                $new_value = totalFiles;
                                            }
                                            // This retrieves the total amount of files that are already uploaded previously
                                            if($value_n.substring(0, 14)==='existing_count'){
                                                totalFiles = $parent.querySelectorAll('.super-fileupload-files > div.super-uploaded').length;
                                                $new_value = totalFiles;
                                            }
                                            if(files[i]) {
                                                if($value_n.substring(0, 3)==='url' || $value_n.substring(0, 3)==='src'){
                                                    $new_value = files[i].url;
                                                }
                                                if($value_n.substring(0, 4)==='size' || $value_n.substring(0, 8)==='filesize'){
                                                    $new_value = files[i].size;
                                                }
                                                if($value_n.substring(0, 4)==='type' || $value_n.substring(0, 4)==='mime'){
                                                    $new_value = files[i].type;
                                                }
                                                if($value_n.substring(0, 8)==='basename' || $value_n.substring(0, 4)==='name'){
                                                    $new_value = files[i].name;
                                                }
                                                if($value_n.substring(0, 3)==='ext'){
                                                    var f = SUPER.get_file_name_and_extension(files[i].name);
                                                    $new_value = f.ext.toLowerCase();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $value = $new_value;
                        }

                        // Check if datepicker field
                        if($parent.classList.contains('super-time') || $parent.classList.contains('super-field-type-datetime-local') || $parent.classList.contains('super-date') || $parent.classList.contains('super-field-type-date')){
                            $text_field = false;
                            $value = $element.value;
                            if($parent.classList.contains('super-field-type-date')){
                                // Text field with type=date
                                if($value_n === 'day' || $value_n === 'day_of_week' || $value_n === 'day_name' || $value_n === 'month' || $value_n === 'year' || $value_n === 'timestamp'){
                                    var d = Date.parseExact($value, ['yyyy-dd-MM']);
                                    if(d!==null){
                                        var year = d.toString('yyyy');
                                        var month = d.toString('MM');
                                        var day = d.toString('dd');                        
                                        var firstDate = new Date(Date.UTC(year, month-1, day));
                                        var dayIndex = firstDate.getDay();
                                        var mathDayw = dayIndex;
                                        var mathDayn = super_common_i18n.dayNames[dayIndex]; // long (default)
                                        var mathDayns = super_common_i18n.dayNamesShort[dayIndex]; // short
                                        var mathDaynss = super_common_i18n.dayNamesMin[dayIndex]; // super short
                                        var mathDiff = firstDate.getTime();
                                        if($value_n === 'day') $value = day;
                                        if($value_n === 'month') $value = month;
                                        if($value_n === 'year') $value = year;
                                        if($value_n === 'day_of_week') $value = mathDayw;
                                        if($value_n === 'day_name') $value = mathDayn;
                                        if($value_n === 'day_name_short') $value = mathDayns;
                                        if($value_n === 'day_name_shortest') $value = mathDaynss;
                                        if($value_n === 'timestamp') $value = mathDiff;
                                    }
                                }
                            }else{
                                // Datepicker
                                if($value_n === 'day' || $value_n === 'day_of_week' || $value_n === 'day_name' || $value_n === 'month' || $value_n === 'year' || $value_n === 'timestamp'){
                                    if($value_n === 'day') $value = ($element.getAttribute('data-math-day')) ? parseFloat($element.getAttribute('data-math-day')) : 0;
                                    if($value_n === 'day_of_week') $value = ($element.getAttribute('data-math-dayw')) ? parseFloat($element.getAttribute('data-math-dayw')) : 0;
                                    if($value_n === 'day_name') $value = ($element.getAttribute('data-math-dayn')) ? $element.getAttribute('data-math-dayn') : '';
                                    if($value_n === 'day_name_short') $value = ($element.getAttribute('data-math-dayns')) ? $element.getAttribute('data-math-dayns') : '';
                                    if($value_n === 'day_name_shortest') $value = ($element.getAttribute('data-math-daynss')) ? $element.getAttribute('data-math-daynss') : '';
                                    if($value_n === 'month') $value = ($element.getAttribute('data-math-month')) ? parseFloat($element.getAttribute('data-math-month')) : 0;
                                    if($value_n === 'year') $value = ($element.getAttribute('data-math-year')) ? parseFloat($element.getAttribute('data-math-year')) : 0;
                                    if($value_n === 'timestamp') $value = ($element.getAttribute('data-math-diff')) ? parseFloat($element.getAttribute('data-math-diff')) : 0;
                                }else{
                                    if($element.getAttribute('data-return_age')=='true') $value = ($element.getAttribute('data-math-age')) ? parseFloat($element.getAttribute('data-math-age')) : 0;
                                    // @since 1.2.0 - check if we want to return the date birth years, months or days for calculations
                                    if($element.getAttribute('data-date-math')=='years') $value = ($element.getAttribute('data-math-age')) ? parseFloat($element.getAttribute('data-math-age')) : 0;
                                    if($element.getAttribute('data-date-math')=='months') $value = ($element.getAttribute('data-math-age-months')) ? parseFloat($element.getAttribute('data-math-age-months')) : 0;
                                    if($element.getAttribute('data-date-math')=='days') $value = ($element.getAttribute('data-math-age-days')) ? parseFloat($element.getAttribute('data-math-age-days')) : 0;
                                }
                            }
                        }

                        if( $text_field===true ) {
                            // Check if text field is a auto-suggest, if so grab the value from the selected item
                            if($element.closest('.super-shortcode').classList.contains('super-auto-suggest') || $element.closest('.super-shortcode').classList.contains('super-wc-order-search')){
                                if($element.closest('.super-field-wrapper').querySelector('.super-active')){
                                    $new_value = $element.closest('.super-field-wrapper').querySelector('.super-active').dataset.value;
                                    $new_value = $new_value.toString().split(';');
                                    if($value_n===0){
                                        $new_value = $new_value[0];
                                    }else{
                                        $new_value = $new_value[($value_n-1)];
                                    }
                                    if(typeof $new_value==='undefined'){
                                        $new_value = '';
                                    }
                                    $value = $new_value;
                                }
                            }else{
                                $value = $element.value;
                            }
                            if( args.target ) {
                                if( (typeof $element.dataset.value !== 'undefined') && (args.target.classList.contains('super-html-content')) ) {
                                    $value = $element.dataset.value;
                                }
                            }
                            if( $value_type=='int' ) {
                                $value = ($value) ? parseFloat($value) : '';
                            }
                        }
                        // Grab E-mail Label
                        if($value_n=='email' || $value_n=='email_label' || $value_n=='emailLabel'){
                            $value = $element.dataset.email;
                            if($value.toString().indexOf('%d')!==-1){
                                var dynamicParentIndex = $($element).parents('.super-duplicate-column-fields:eq(0)').index();
                                $value = SUPER.replaceAll($value, '%d', dynamicParentIndex+1);
                            }
                        }
                        if( ($value_type=='int') && (isNaN($value)) ) {
                            $value = $default_value;
                        }
                        if( $value_n==='24h' && $value.length===13){
                            $value = SUPER.timestampTo24h($value);
                        }
                        args.value = args.value.replace('{'+$old_name+'}', $value);
                        SUPER.beforeReturnReplacedTagValue(args, formId, $name, indexMapping, args.value);
                    }
                }
            }
        }
        return args.value;
    };
    SUPER.beforeReturnReplacedTagValue = function(args, formId, $name, indexMapping, value){
        if(indexMapping!==value) {
            if(typeof SUPER.preFlightMappings[formId].fieldNames[$name] === 'undefined') SUPER.preFlightMappings[formId].fieldNames[$name] = [];
            if(SUPER.preFlightMappings[formId].fieldNames[$name].indexOf(indexMapping)===-1){
                SUPER.preFlightMappings[formId].fieldNames[$name].push(indexMapping);
            }
            SUPER.preFlightMappings[formId].tags[indexMapping] = value;
        }
    };

    SUPER.close_loading_overlay = function(loadingOverlay){
        if(loadingOverlay) loadingOverlay.remove();
    };
    SUPER.reset_submit_button_loading_state = function(form){
        var submitButton = form.querySelector('.super-form-button.super-loading');
        if(submitButton){
            submitButton.classList.remove('super-loading');
            var buttonName = submitButton.querySelector('.super-button-name');
            var normal = buttonName.dataset.normal;
            buttonName.innerHTML = normal;
        }
    };

    // Resend E-mail verification code
    SUPER.init_resend_verification_code = function(args){
        var i, nodes = document.querySelectorAll('.super-inner-text .resend-code, .super-msg .resend-code');
        for(i=0; i<nodes.length; i++){
            nodes[i].addEventListener('click', function(){
                var el = this;
                var container;
                var formId = el.dataset.form;
                var form = document.querySelector('.super-form-'+formId);
                if(!form) return;
                var data = {
                    form: formId,
                    username: el.dataset.user
                };
                el.classList.add('super-loading');
                $.ajax({
                    url: super_common_i18n.ajaxurl,
                    type: 'post',
                    data: {
                        action: 'super_resend_activation',
                        data: data
                    },
                    success: function (result) {
                        result = JSON.parse(result);
                        if(args && args.showOverlay==="true"){
                            // Check if there is a message
                            if(result.msg!==''){
                                // Check if this is an error message
                                if(result.error===true){
                                    args.loadingOverlay.classList.remove('super-success');
                                    args.loadingOverlay.classList.add('super-error');
                                }else{
                                    args.loadingOverlay.classList.remove('super-error');
                                    args.loadingOverlay.classList.add('super-success');
                                }
                                // Display message inside overlay
                                var innerText = args.loadingOverlay.querySelector('.super-inner-text');
                                if(innerText) innerText.innerHTML = '<span>'+result.msg+'</span>';
                            }
                        }else{
                            if(el.closest('.super-msg')){
                                container = el.closest('.super-msg');
                                var html = '<div class="super-msg super-success">';
                                if(result.error==true){
                                    html = '<div class="super-msg error">';
                                }
                                html += result.msg;
                                html += '<span class="super-close"></span>';
                                html += '</div>';
                                var $newMessage = $(html).insertBefore(container);
                                container.remove();
                                SUPER.scrollToElement(args.form);
                            }
                        }
                        if(result.error!==true && result.redirect){
                            window.location.replace(result.redirect);
                        }
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        console.log(xhr, ajaxOptions, thrownError);
                        alert('Failed to resend activation code, please try again');
                    },
                    complete: function() {
                        el.classList.remove('super-loading');
                    }
                });                    
                return false;
            });
        }
    };
    // Show PDF download button
    SUPER.show_pdf_download_btn = function(args){
        var btn = document.createElement('div');
        btn.classList.add('super-pdf-download-btn');
        btn.innerHTML = args.pdfSettings.downloadBtnText;
        args.loadingOverlay.querySelector('.super-loading-text').appendChild(btn);
        btn.addEventListener('click', function(){
            args._pdf.save(args.pdfSettings.filename);
        });
    };
    // Form submission is finished
    SUPER.form_submission_finished = function(args, result){ 
        // Update Listings entry?
        if(args.list_id && args.list_id!==''){
            if(document.querySelector('.super-loading-overlay')){
                if(result.response_data.form_processing_overlay!=='true'){
                    document.querySelector('.super-loading-overlay').remove();
                }
                if(result.response_data.form_processing_overlay && result.response_data.form_processing_overlay==='true'){
                    if(result.response_data.close_form_processing_overlay && result.response_data.close_form_processing_overlay==='true'){
                        document.querySelector('.super-loading-overlay').remove();
                    }
                }
            }
            if(document.querySelector('.super-listings-modal') && result.response_data.close_editor_window_after_editing && result.response_data.close_editor_window_after_editing==='true'){
                document.querySelector('.super-listings-modal').remove();
            }
            var nodes = document.querySelectorAll('.super-listings > .super-listings-wrap > .super-entries > .super-entry.super-updated');
            for(var i=0; i<nodes.length; i++){
                nodes[i].classList.remove('super-updated');
            }
            nodes = document.querySelectorAll('.super-listings > .super-listings-wrap > .super-entries > .super-entry[data-id="'+args.entry_id+'"]');
            for(i=0; i<nodes.length; i++){
                nodes[i].classList.add('super-updated');
                var cols = nodes[i].querySelectorAll('.super-col:not(.super-actions)');
                for(var x=0; x<cols.length; x++){
                    var fieldName = cols[x].className.replace('super-col super-','');
                    if(fieldName==='entry_status'){
                        var status = result.response_data.entry_status;
                        cols[x].innerHTML = '<span class="super-entry-status super-entry-status-' + status.key + '" style="color:' + status.color + ';background-color:' + status.bg_color + '">' + status.name + '</span>';
                        continue;
                    }

                    // // If not then it must be a special field, for instance file uploads
                    // if($data[$column_key]['type']==='files'){
                    //     $linkUrl = '';
                    //     if(isset($data[$column_key]['files'])){
                    //         $files = $data[$column_key]['files'];
                    //         foreach($files as $fk => $fv){
                    //             $url = (!empty($fv['url']) ? $fv['url'] : '');
                    //             if( !empty( $fv['attachment'] ) ) { // only if file was inserted to Media Library
                    //                 $url = wp_get_attachment_url( $fv['attachment'] );
                    //             }
                    //             if(!empty($url)){
                    //                 $cellValue .= '<a target="_blank" download href="' . esc_url( $url ) . '">';
                    //             }
                    //             if(!empty($url)){
                    //                 $cellValue .= '<span class="super-icon-download"></span></a>';
                    //             }
                    //             $cellValue .= esc_html( $fv['value'] ); // The filename
                    //             $cellValue .= '<br />';
                    //         }
                    //     }else{
                    //         $cellValue = esc_html__( 'No files uploaded', 'super-forms' );
                    //     }
                    // }

                    if(fieldName==='generated_pdf'){
                        // Replace URL with blob
                        if(result.response_data._generated_pdf_file){
                            // Check if contains a link
                            if(cols[x].querySelector('a')){
                                cols[x].querySelector('a').href = result.response_data._generated_pdf_file.files[0].url;
                                if(cols[x].querySelector('span')){
                                    cols[x].querySelector('a').innerHTML = cols[x].querySelector('a > span').outerHTML+result.response_data._generated_pdf_file.files[0].value;
                                }
                            }else{
                                // Just replace file name
                                if(cols[x].querySelector('span')){
                                    cols[x].innerHTML = cols[x].querySelector('span').outerHTML+result.response_data._generated_pdf_file.files[0].value;
                                }else{
                                    cols[x].innerHTML = result.response_data._generated_pdf_file.files[0].value;

                                }
                            }
                        }
                        continue;
                    }
                    // Grab field value from data
                    if(args.data[fieldName]){
                        if(args.data[fieldName].entry_value){
                            cols[x].innerText = args.data[fieldName].entry_value;
                            continue;
                        }
                        if(args.data[fieldName].value){
                            cols[x].innerText = args.data[fieldName].value;
                            continue;
                        }
                    }
                }
            }
        }
        if(args.showOverlay==="true"){
            // Display message inside overlay
            if(args.progressBar) args.progressBar.style.width = 100+"%";  
            var innerText = args.loadingOverlay.querySelector('.super-inner-text');
            if(innerText) {
                innerText.innerHTML = '<span>'+super_common_i18n.loadingOverlay.completed+'</span>';
            }
            // Check if there is a message
            if(result.msg!==''){
                // Check if this is an error message
                if(result.error===true){
                    args.loadingOverlay.classList.add('super-error');
                    // Display the error/success message
                    if(innerText) innerText.innerHTML = result.msg;
                    SUPER.scrollToElement(args.loadingOverlay);
                    // Visually indicate which fields have errors
                    if(typeof result.fields !== 'undefined'){
                        for(var i=0; i<result.fields.length; i++){
                            var el = SUPER.field(args.form0, result.fields[i]);
                            if(el){
                                SUPER.handle_errors(el);
                                SUPER.add_error_status_parent_layout_element($, el);
                                //if(args.el.closest('.super-field')) args.el.closest('.super-field').classList.remove('super-error-active');
                                //SUPER.remove_error_status_parent_layout_element($, args.el);
                            }
                        }
                    }                               
                    SUPER.init_resend_verification_code(args);
                }else{
                    args.loadingOverlay.classList.add('super-success');
                    if(args.generatePdf && args.pdfSettings.downloadBtn==='true'){
                        if(args.pdfSettings.debug!=="true"){ // If debug mode is enabled we alread have a Download button, so we can skip this
                            SUPER.show_pdf_download_btn(args);
                        }
                    }
                    // Close Popup (if any)
                    if(typeof SUPER.init_popups === 'function' && typeof SUPER.init_popups.close === 'function' ){
                        SUPER.init_popups.close(true);
                    }
                    if(result.redirect){
                        // When redirecting to different page show redirect message instead of thank you message
                        innerText.innerHTML = SUPER.update_variable_fields.replace_tags({form: args.form0, value: super_common_i18n.loadingOverlay.redirecting});
                    }else{
                        // Display the error/success message
                        if(innerText) innerText.innerHTML = result.msg;
                    }
                    SUPER.scrollToElement(args.loadingOverlay);
                }
                // Convert any JS to executable JS
                var node = innerText.querySelector('script');
                if(node && node.tagName === 'SCRIPT'){
                    var script  = document.createElement("script");
                    script.text = node.innerHTML;
                    for( var i = node.attributes.length-1; i >= 0; i-- ) {
                        script.setAttribute( node.attributes[i].name, node.attributes[i].value );
                    }
                    node.parentNode.replaceChild(script, node);
                }
            }else{
                // We do not want to display a thank you message, but might want to display a Download PDF button
                if(args.generatePdf && args.pdfSettings.downloadBtn==='true'){
                    args.loadingOverlay.classList.add('super-success');
                    SUPER.show_pdf_download_btn(args);
                }else{
                    // Just close the overlay, no need to show download button and we do not have a message to display
                    SUPER.close_loading_overlay(args.loadingOverlay);
                }
                // Close Popup (if any)
                if(typeof SUPER.init_popups === 'function' && typeof SUPER.init_popups.close === 'function' ){
                    SUPER.init_popups.close(true);
                }
            }
            if(args.loadingOverlay.querySelector('.super-loading-wrapper')){
                args.loadingOverlay.querySelector('.super-loading-wrapper').scrollTop = -args.loadingOverlay.querySelector('.super-loading-wrapper').scrollHeight;
            }
        }else{
            // Display message in legacy mode
            // But only display if not empty
            if(result.msg!==''){
                // Remove existing messages
                var ii, html;
                nodes = document.querySelectorAll('.super-msg');
                for (ii = 0; ii < nodes.length; ii++) { 
                    nodes[ii].remove();
                }
                // Check for errors, if there are any display them to the user 
                if(result.error===true){
                    html = '<div class="super-msg super-error">';
                    if(typeof result.fields !== 'undefined'){
                        $.each(result.fields, function( index, value ) {
                            $(value+'[name="'+index+'"]').parent().addClass('error');
                        });
                    }                               
                }else{
                    html = '<div class="super-msg super-success"';
                    // @since 3.4.0 - option to not display the message
                    if(result.display===false){
                        html += 'style="display:none;">';
                    }
                    html += '>';
                }
                html += result.msg;
                html += '<span class="super-close"></span>';
                html += '</div>';
                if(args.form){
                    $(html).prependTo($(args.form));
                    SUPER.init_resend_verification_code(args);
                    SUPER.scrollToElement(args.form0);
                }
            }
        }
        // Redirect user to specified url
        if(result.error!==true && result.redirect){
            // Construct the URL with the specific parameter appended
            if(result.back_url && result.back_url!==''){
                history.pushState(null, null, result.back_url);
            }
            window.location.href = result.redirect;
            return true;
        }
        // @since 3.4.0 - keep loading state active
        if(result.loading!==true){
            if(typeof args.form !== 'undefined') {
                SUPER.reset_submit_button_loading_state(args.form[0]);
                if(result.error===false){
                    // @since 2.0.0 - hide form or not
                    if($(args.form).data('hide')===true){
                        $(args.form).find('.super-field, .super-multipart-progress, .super-field, .super-multipart-steps').fadeOut(500);
                        setTimeout(function () {
                            $(args.form).find('.super-field, .super-shortcode').remove();
                            if(args.showOverlay==="true"){
                                if(result.msg!==''){
                                    SUPER.scrollToElement(args.loadingOverlay);
                                }
                            }
                        }, 500);
                    }else{
                        // @since 2.0.0 - clear form after submitting
                        if($(args.form).data('clear')===true){
                            SUPER.init_clear_form({form: args.form0});
                        }
                    }
                    if(result.msg===''){
                        // Close Popup (if any)
                        if(typeof SUPER.init_popups === 'function' && typeof SUPER.init_popups.close === 'function' ){
                            SUPER.init_popups.close(true);
                        }
                    }
                }
            }
        }
    };

    // Trim strings
    SUPER.trim = function($this) {
        if(typeof $this === 'string'){
            return $this.replace(/^\s+|\s+$|\s+(?=\s)/g, "");
        }
    };

    // Check for errors, validate fields
    SUPER.handle_validations = function(args){
        if(args.event) console.log(args.event.type);
        if(args.el.closest('[data-conditional-action="show"]')){
            if(args.el.closest('[data-conditional-action="show"]').classList.contains('super-conditional-hidden')){
                return false;
            }
        }
        if(args.el.closest('.super-shortcode').classList.contains('super-hidden')) return false;
        args.mayBeEmpty = (typeof args.el.dataset.mayBeEmpty !== 'undefined' ? args.el.dataset.mayBeEmpty : 'false');
        args.allowEmpty = false;
        args.emptyValue = false;
        if(args.el.classList.contains('super-address-autopopulate')){
            if(!args.el.dataset.lng || args.el.dataset.lng===''){
                args.emptyValue = true;
            }
        }else{
            // Currently used by autosuggest field only
            if(args.validation=='restrict_to_items'){
                // This validation will be used when an autosuggest does not have `Allow users to enter their own value` enabled
                // We simply check if an item was selected
                if(!args.el.parentNode.querySelector('.super-item.super-active')){
                    args.emptyValue = true;
                }
            }else{
                if(args.el.value===''){
                    args.emptyValue = true;
                }
            }
        }
        // @since   4.9.0 -  Conditional required fields
        // Before we proceed, check if field is empty
        if(args.emptyValue){
            // If it is empty, check if it allowed to be empty
            if(typeof args.mayBeEmpty!=='undefined'){
                if (args.mayBeEmpty == 'false') {
                    args.allowEmpty = false; // Do not allow field to be empty
                }
                if (args.mayBeEmpty == 'true') {
                    args.allowEmpty = true; // Allow field to be empty
                }
                if (args.mayBeEmpty == 'conditions') {
                    // Allow field to be empty only when following conditions are met
                    args.allowEmpty = true; 
                    args.conditionalLogic = args.el.parentNode.closest('.super-field').querySelectorAll('.super-validate-conditions');
                    if(typeof args.conditionalLogic !== 'undefined'){
                        if(args.conditionalLogic.length!==0){
                            args.callback = function(validation_match){
                                if(validation_match===true){
                                    args.allowEmpty = false; // when condition is met, we do not allow field to be empty
                                }
                                SUPER.validationLookups--;
                                return SUPER.handle_validations_finish(args);
                            }
                            SUPER.validationLookups++;
                            SUPER.conditional_logic.loop(args);
                            return;
                        }
                    }
                }
            }
        }
        return SUPER.handle_validations_finish(args);
    };
    SUPER.handle_validations_finish = function(args){
        var parent = args.el.closest('.super-field'),
            error = false,
            regex,
            value,
            numbers,
            pattern,
            attr,
            text_field,
            total,
            field_value,
            value2,
            counter,
            checked,
            custom_regex = (args.el.parentNode.querySelector('.super-custom-regex') ? args.el.parentNode.querySelector('.super-custom-regex').value : undefined), // @since 1.2.5 - custom regex
            urlRegex = /^(http(s)?:\/\/)?(www\.)?[a-zA-Z0-9]+([-.]{1}[a-zA-Z0-9]+)*\.[a-zA-Z]{2,5}(:[0-9]{1,5})?(\/.*)?$/;

        regex = new RegExp(custom_regex);

        if( custom_regex && args.validation=='custom' ) {
            if(!regex.test(args.el.value)) error = true;
        }
        if (args.validation == 'captcha') {
            error = true;
        }
        if (args.validation == 'restrict_to_items') {
            if((args.event && args.event.type!=='change') || !args.event){
                // This validation will be used when an autosuggest does not have `Allow users to enter their own value` enabled
                // We simply check if an item was selected
                if(!args.el.parentNode.querySelector('.super-item.super-active')){
                    args.el.parentNode.closest('.super-field').classList.remove('super-string-found');
                    // No option was selected,, return error unless the field may be left empty
                    // In that case, make sure to empty the value of the input field just in case someone typed something
                    error = true;
                    args.el.value = '';
                    if(args.allowEmpty){
                        error = false;
                    }
                }
            }
        }
        if (args.validation == 'numeric') {
            regex = /^\d+$/;
            if (!regex.test(args.el.value)) error = true;
        }
        if (args.validation == 'float') {
            regex = /^[+-]?\d+(\.\d+)?$/;
            if (!regex.test(args.el.value)) error = true;
        }
        if (args.validation == 'email') {
            regex = /^([\w-.+]+@([\w-]+\.)+[\w-]{2,63})?$/;
            if ((args.el.value.length < 4) || (!regex.test(args.el.value))) {
                error = true;
            }
        }
        if (args.validation == 'phone') {
            regex = /^((\+)?[1-9]{1,2})?([-\s.])?((\(\d{1,4}\))|\d{1,4})(([-\s.])?[0-9]{1,12}){1,2}$/;
            value = args.el.value;
            numbers = value.split("").length;
            if (10 <= numbers && numbers <= 20 && regex.test(value)) {
                // is valid, continue
            }else{
                error = true;
            }
        }
        if (args.validation == 'website') {
            pattern = new RegExp(urlRegex);
            if(!pattern.test(args.el.value)) error = true;
        }
        // @since 2.6.0 - IBAN validation
        if (args.validation == 'iban') {
            if( (IBAN.isValid(args.el.value)===false) && (args.el.value!=='') ) error = true;
        }
        attr = args.el.dataset.minlength;
        if (typeof attr !== 'undefined' && attr !== false) {
            text_field = true;
            total = 0;
            if(parent.classList.contains('super-checkbox')){
                text_field = false;
                checked = parent.querySelectorAll('.super-item.super-active');
                if(checked.length < attr){
                    error = true;
                }
            }
            if(parent.classList.contains('super-dropdown')){
                text_field = false;
                total = parent.querySelectorAll('.super-dropdown-list .super-item.super-active:not(.super-placeholder)').length;
                if(total < attr) error = true;
            }
            if(parent.classList.contains('super-keyword-tags')){
                text_field = false;
                total = parent.querySelectorAll('.super-autosuggest-tags > span').length;
                if(total < attr) error = true;
            }
            if(text_field===true){
                if(!parent.classList.contains('super-date')){
                    if(args.el.value.length < attr) error = true;
                }
            }       
        }
        attr = args.el.dataset.maxlength;
        if (typeof attr !== 'undefined' && attr !== false) {
            text_field = true;
            total = 0;
            if(parent.classList.contains('super-checkbox')){
                text_field = false;
                checked = parent.querySelectorAll('.super-item.super-active');
                if(checked.length > attr) error = true;
            }
            if(parent.classList.contains('super-dropdown')){
                text_field = false;
                total = parent.querySelectorAll('.super-dropdown-list .super-item.super-active:not(.super-placeholder)').length;
                if(total > attr) error = true;
            }
            if(parent.classList.contains('super-keyword-tags')){
                text_field = false;
                total = parent.querySelectorAll('.super-autosuggest-tags > span').length;
                if(total > attr) error = true;
            }
            if(text_field===true){
                if(!parent.classList.contains('super-date')){
                    if(args.el.value.length > attr) error = true;
                }
            }
        }
        attr = args.el.dataset.minnumber;
        if (typeof attr !== 'undefined' && attr !== false) {
            // Check if currency field
            if(parent.classList.contains('super-currency')){
                value = $(args.el).maskMoney('unmasked')[0];
                value = (value) ? parseFloat(value) : 0;
                if( value < parseFloat(attr) ) error = true;
            }else{
                if( parseFloat(args.el.value) < parseFloat(attr) ) error = true;
            }
        }
        attr = args.el.dataset.maxnumber;
        if (typeof attr !== 'undefined' && attr !== false) {
            // Check if currency field
            if(parent.classList.contains('super-currency')){
                value = $(args.el).maskMoney('unmasked')[0];
                value = (value) ? parseFloat(value) : 0;
                if( value > parseFloat(attr) ) error = true;
            }else{
                if( parseFloat(args.el.value) > parseFloat(attr) ) error = true;
            }
        }

        // Datepicker min dates
        if(parent && parent.classList.contains('super-date')){
            attr = args.el.dataset.minpicks;
            if (typeof attr !== 'undefined' && attr !== false) {
                if( parseInt(attr,10)>0 ) {
                    if(args.el.value==='') error = true;
                    total = args.el.value.split(',').length;
                    if( total < parseFloat(attr) ) error = true;
                }
            }
        }

        // @since   1.0.6
        if( typeof args.conditionalValidation!=='undefined' && args.conditionalValidation!='none' && args.conditionalValidation!=='' ) {
            field_value = args.el.value;
            // Check if currency field
            if(parent.classList.contains('super-currency')){
                value = $(args.el).maskMoney('unmasked')[0];
                field_value = (value) ? parseFloat(value) : 0;
            }
            value = args.el.dataset.conditionalValidationValue;
            value2 = args.el.dataset.conditionalValidationValue2;
            if(typeof value !== 'undefined') value = SUPER.get_conditional_validation_value(value, args.form);
            if(typeof value2 !== 'undefined') value2 = SUPER.get_conditional_validation_value(value2, args.form);
            counter = 0;
            if(args.conditionalValidation=='equal' && field_value==value) counter++;
            if(args.conditionalValidation=='not_equal' && field_value!=value) counter++;
            if(args.conditionalValidation=='contains' && field_value.indexOf(value) >= 0) counter++;
            if(args.conditionalValidation=='not_contains' && field_value.indexOf(value) == -1) counter++;
            field_value = parseFloat(field_value);
            value = parseFloat(value);
            value2 = parseFloat(value2);
            if(args.conditionalValidation=='greater_than' && field_value>value) counter++;
            if(args.conditionalValidation=='less_than' && field_value<value) counter++;
            if(args.conditionalValidation=='greater_than_or_equal' && field_value>=value) counter++;
            if(args.conditionalValidation=='less_than_or_equal' && field_value<=value) counter++;
            // @since 3.6.0 - more specific conditional validation options
            // > && <
            // > || <
            if( (args.conditionalValidation=='greater_than_and_less_than') && ((field_value>value) && (field_value<value2)) ) counter++;
            if( (args.conditionalValidation=='greater_than_or_less_than') && ((field_value>value) || (field_value<value2)) ) counter++;
            // >= && <
            // >= || <
            if( (args.conditionalValidation=='greater_than_or_equal_and_less_than') && ((field_value>=value) && (field_value<value2)) ) counter++;
            if( (args.conditionalValidation=='greater_than_or_equal_or_less_than') && ((field_value>=value) || (field_value<value2)) ) counter++;
            // > && <=
            // > || <=
            if( (args.conditionalValidation=='greater_than_and_less_than_or_equal') && ((field_value>value) && (field_value<=value2)) ) counter++;
            if( (args.conditionalValidation=='greater_than_or_less_than_or_equal') && ((field_value>value) || (field_value<=value2)) ) counter++;
            // >= && <=
            // >= || <=
            if( (args.conditionalValidation=='greater_than_or_equal_and_less_than_or_equal') && ((field_value>=value) && (field_value<=value2)) ) counter++;
            if( (args.conditionalValidation=='greater_than_or_equal_or_less_than_or_equal') && ((field_value>=value) || (field_value<=value2)) ) counter++;
            if(counter===0) error = true;
        }
        // @since 4.3.0 - extra validation check for files
        if(args.el.classList.contains('super-fileupload')){
            attr = args.el.parentNode.querySelector('.super-active-files').dataset.minfiles;
            if (typeof attr !== 'undefined' && attr !== false) {
                total = args.el.parentNode.querySelectorAll('.super-fileupload-files > div').length;
                if(total < attr) error = true;
            }
            attr = args.el.parentNode.querySelector('.super-active-files').dataset.maxfiles;
            if (typeof attr !== 'undefined' && attr !== false) {
                total = args.el.parentNode.querySelectorAll('.super-fileupload-files > div').length;
                if(total > attr) error = true;
            }
            if(args.el.closest('.super-shortcode').classList.contains('super-error-active')){
                error = true;
            }
        }
        // @since 5.0.022 - extra validation check for international phone numbers
        if(args.el.closest('.super-int-phone')){
            var super_int_phone = window.superTelInputGlobals.getInstance(args.el);
            if(!super_int_phone.isValidNumber()){ // If the phone validation causes false positives use super_int_phone.isPossibleNumber() instead
                error = true;
            }
        }

        // Display error messages
        if(args.allowEmpty && args.emptyValue) error = false;
        if(typeof args.validation !== 'undefined' && !args.allowEmpty && args.emptyValue) error = true;
        if(error){
            SUPER.handle_errors(args.el);
            SUPER.add_error_status_parent_layout_element($, args.el);
        }else{
            if(args.el.closest('.super-field')) args.el.closest('.super-field').classList.remove('super-error-active');
            SUPER.remove_error_status_parent_layout_element($, args.el);
        }
        return error;
    }

    // Output errors for each field
    SUPER.handle_errors = function(el){
        if(el.closest('.super-field')) el.closest('.super-field').classList.add('super-error-active');
    };

    // Validate the form
    SUPER.validate_form = function(args, callback){ // we use a callback in order to return true or false inside the interval
    //SUPER.validate_form = function(args){ // form, submitButton, validateMultipart, e, doingSubmit
        SUPER.validationLookups = 0;
        SUPER.resetFocussedFields();
        SUPER.conditional_logic(args);

        // // Check if any of the stripe elements are filled out correctly
        // // Pass this to the form data so we can do a check (if Stripe is enabled)
        // super-stripe-ideal-element
        // super-stripe-cc-element
        // super-stripe-iban-element
        SUPER.before_validating_form_hook(args);

        var i = 0, nodes,
            action = (args.submitButton.querySelector('.super-button-name') ? args.submitButton.querySelector('.super-button-name').dataset.action : ''),
            url = (typeof args.submitButton.dataset.href !== 'undefined' ? decodeURIComponent(args.submitButton.dataset.href) : undefined) ,
            proceed = SUPER.before_submit_button_click_hook(args.event, args.submitButton),
            array = [],
            error = false,
            name,
            field,
            element,
            target,
            submitButtonName,
            oldHtml,
            loading,
            total,
            match,
            value,
            fileError,
            attr,
            validation,
            conditionalValidation,
            textField;

        // Set action to empty string when the button is a multi-part button
        if(args.submitButton.classList.contains('super-next-multipart') || args.submitButton.classList.contains('super-prev-multipart')){
            action = '';
        }

        if(action=='clear'){
            SUPER.init_clear_form({form: args.form});
            return false;
        }
        if(action=='print'){
            SUPER.init_print_form(args);
            return false;
        }
        if(proceed===true){
            if( (url!=='') && (typeof url !== 'undefined') ){
                while ((match = SUPER.tagsRegex.exec(url)) !== null) {
                    array[i] = match[1];
                    i++;
                }
                for (i = 0; i < array.length; i++) {
                    name = array[i];
                    element = SUPER.field(args.form, name);
                    if(element){
                        value = element.value;
                        url = url.replace('{'+name+'}', value);
                        
                    }
                }
                url = url.replace('{', '').replace('}', '');
                if( url=='#' ) {
                    return false;
                }else{
                    target = args.submitButton.dataset.target;
                    if( (target!=='undefined') && (target=='_blank') ) {
                        window.open( url, '_blank' );
                    }else{
                        window.location.href = url;
                    }
                    return false;
                }
            }else{
                if(args.submitButton.closest('.super-form-button') && args.submitButton.closest('.super-form-button').classList.contains('super-loading')){
                    return false;
                }
            }
        }
        // @since 2.0 - multipart validation
        if(typeof args.validateMultipart === 'undefined') args.validateMultipart = '';

        // @since 1.2.4     make sure the text editor saves content to it's textarea
        if( typeof tinyMCE !== 'undefined' ) {
            if( typeof tinyMCE.triggerSave !== 'undefined' ) {
                tinyMCE.triggerSave();
            }
        }

        nodes = SUPER.field(args.form, '', 'all');
        for ( i = 0; i < nodes.length; i++) {
            field = nodes[i];
            textField = true;
            if(!SUPER.has_hidden_parent(field)){
                // super-shortcode-field super-fileupload super-rendered
                if(field.classList.contains('super-active-files')){
                    textField = false;
                    fileError = false;
                    attr = parseFloat(field.dataset.minfiles);
                    if (!isNaN(attr) && typeof attr !== 'undefined' && attr !== false) {
                        total = field.parentNode.querySelectorAll('.super-fileupload-files > div').length;
                        if(total < attr) {
                            fileError = true;
                        }
                    }
                    attr = parseFloat(field.dataset.maxfiles);
                    if (!isNaN(attr) && typeof attr !== 'undefined' && attr !== false) {
                        total = field.parentNode.querySelectorAll('.super-fileupload-files > div').length;
                        if(total > attr) {
                            fileError = true;
                        }
                    }
                    if(fileError===true){
                        error = true;
                        SUPER.handle_errors(field);
                        SUPER.add_error_status_parent_layout_element($, field);
                    }else{
                        if(field.closest('.super-field')) field.closest('.super-field').classList.remove('super-error-active');
                        SUPER.remove_error_status_parent_layout_element($, field);
                    }
                }
                if(textField===true){
                    validation = field.dataset.validation;
                    conditionalValidation = field.dataset.conditionalValidation;
                    if (SUPER.handle_validations({el: field, form: args.form, validation: validation, conditionalValidation: conditionalValidation})) {
                        error = true;
                    }
                }
            }
        }
        var validationDone = setInterval(function(args){
            if(SUPER.validationLookups===0){
                // Ready to continue 
                clearInterval(validationDone);
                // Activate possible TABS and Accordions to display errors
                var tabs = args.form.querySelectorAll('.super-tabs-tab.super-error');
                if(tabs && tabs[0]) tabs[0].click();
                var accordions = args.form.querySelectorAll('.super-accordion-item.super-error');
                if(accordions && accordions[0]) accordions[0].querySelector('.super-accordion-header').click();
                if(error===false){
                    // Check if there are other none standard elements that have an active error
                    // Currently used by Stripe feature to check for invalid card numbers for instance
                    if(args.form.querySelectorAll('.super-error-active').length){
                        SUPER.scrollToError(args.form);
                        if(typeof callback === 'function') callback(true); // Indicate the validation failed
                        return;
                    }
                    // @since 2.0.0 - multipart validation
                    if(args.validateMultipart===true) {
                        callback(false); // No errors found
                        return;
                    }
                    submitButtonName = args.submitButton.querySelector('.super-button-name');
                    args.submitButton.closest('.super-form-button').classList.add('super-loading');
                    oldHtml = submitButtonName.innerHTML;
                    // @since 2.0.0 - submit button loading state name
                    loading = args.submitButton.querySelector('.super-button-name').dataset.loading;
                    if(super_common_i18n.loading!='Loading...') {
                        loading = super_common_i18n.loading;
                    }
                    submitButtonName.innerHTML = loading;

                    var y=0, 
                        codeNodes = args.form.querySelectorAll('.super-shortcode-field[data-code="true"]'),
                        totalNodes = codeNodes.length;
                    for(y=0; y<codeNodes.length; y++){
                        codeNodes[y].classList.remove('super-generated');
                    }
                    for(y=0; y<codeNodes.length; y++){
                        SUPER.update_unique_code(codeNodes[y], 'true');
                    }
                    var completeSubmitInterval = setInterval(function(){
                        var codeNodes = args.form.querySelectorAll('.super-shortcode-field.super-generated[data-code="true"]');
                        if(codeNodes.length !== totalNodes){
                            // Still doing things...
                        }else{
                            clearInterval(completeSubmitInterval);
                            // Prepare arguments
                            SUPER.prepare_form_data($(args.form), function(formData){
                                args = {
                                    event: args.event,
                                    form: args.form,
                                    data: formData.data,
                                    form_id: formData.form_id,
                                    entry_id: formData.entry_id,
                                    list_id: formData.list_id,
                                    oldHtml: oldHtml,
                                    sf_nonce: formData.sf_nonce
                                };
                                args.callback = function(){
                                    SUPER.complete_submit(args);
                                };
                                SUPER.before_submit_hook(args);
                            }); 
                        }
                    }, 100);

                }else{
                    SUPER.scrollToError(args.form, args.validateMultipart);
                    if(typeof callback === 'function') callback(true); // Indicate the validation failed
                    return;
                }
                SUPER.after_validating_form_hook(undefined, args.form);
            }
            // Wait until all validations have finished
        }, 0, args);
    };

    SUPER.scrollToError = function(form, validateMultipart){
        var scroll = true, step, children, index, total, progress, multipart, proceed;
        // @since 2.0 - multipart validation
        if(validateMultipart===true) {
            scroll = true;
            if(typeof form.dataset.disableScroll !== 'undefined'){
                scroll = false;
            }
            if(scroll){
                SUPER.scrollToElement(form);
            }
            return false;
        }

        if(form.querySelector('.super-multipart-step.super-error')){
            step = form.querySelector('.super-multipart-step.super-error');
            children = Array.prototype.slice.call( step.parentNode.children );
            index = children.indexOf(step);
            total = form.querySelectorAll('.super-multipart').length;
            progress = 100 / total;
            progress = progress * (index+1);
            multipart = form.querySelectorAll('.super-multipart')[index];
            scroll = true;
            if(typeof multipart.dataset.disableScroll !== 'undefined'){
                scroll = false;
            }
            form.querySelector('.super-multipart-progress-bar').style.width = progress+'%';
            form.querySelector('.super-multipart-step.super-active').classList.remove('super-active');
            form.querySelector('.super-multipart.super-active').classList.remove('super-active');
            multipart.classList.add('super-active');
            step.classList.add('super-active');

            // @since 2.1.0
            proceed = SUPER.before_scrolling_to_error_hook(form, $(form).offset().top - 30);
            if(proceed!==true) return false;

            // @since 4.2.0 - disable scrolling when multi-part contains errors
            if(scroll){
                // Scroll to first error field
                var current = multipart.querySelector('.super-error-active');
                SUPER.scrollToElement(current);
                // Focus first error field
                SUPER.focusNextTabField({keyCode: 32, preventDefault: function(){}}, current, form, current);
            }
        }else{
            // @since 2.1.0
            proceed = SUPER.before_scrolling_to_error_hook(form, $(form).find('.super-error-active').offset().top-200);
            if(proceed!==true) return false;
            SUPER.scrollToElement($(form).find('.super-error-active')[0]);
        }
    };

    // @since 1.2.3
    SUPER.auto_step_multipart = function(args){
        // If triggered field change is not inside active multi-part we skip it
        var activeMultipart = args.el.closest('.super-multipart.super-active');
        if(!activeMultipart) return false;
        var i, nodes, totalFields, counter;
        if(activeMultipart){
            if( activeMultipart.dataset.stepAuto=='yes') {
                totalFields = 0;
                nodes = activeMultipart.querySelectorAll('.super-shortcode-field');
                for (i = 0; i < nodes.length; ++i) {
                    if(!SUPER.has_hidden_parent(nodes[i])){
                        // Also exclude any hidden fields, because `has_hidden_parent()` doesn't check for this
                        if(nodes[i].type=='hidden'){
                            if(nodes[i].closest('.super-shortcode').classList.contains('super-hidden')){
                                continue;
                            }
                        }
                        totalFields++;
                    }
                }
                counter = 1;
                nodes = activeMultipart.querySelectorAll('.super-shortcode-field');
                for (i = 0; i < nodes.length; ++i) {
                    if(!SUPER.has_hidden_parent(nodes[i])){
                        if(totalFields==counter){
                            if(nodes[i].name==args.el.name){
                                setTimeout(function (){
                                    var activeMultipart = args.form.querySelector('.super-multipart-step.super-active'),
                                    children = Array.prototype.slice.call(activeMultipart.parentNode.children),
                                    total = args.form.querySelectorAll('.super-multipart').length,
                                    currentStep = children.indexOf(activeMultipart);
                                    if(total===currentStep+1){
                                        // Except if already last step
                                    }else{
                                        SUPER.switchMultipart(undefined, args.el, 'next');
                                    }
                                }, 200);
                                break;
                            }
                        }
                        counter++;
                    }
                }
            }
        }
    };

    // Define Javascript Filters/Hooks
    SUPER.save_form_params_filter = function(params){
        debugger;
        var i, name, functions = super_common_i18n.dynamic_functions.save_form_params_filter;
        if(typeof functions !== 'undefined'){
            for( i = 0; i < functions.length; i++){
                name = functions[i].name;
                if(typeof SUPER[name] === 'undefined') continue;
                params = SUPER[name](params);
            }
        }
        try{
            params.form_data = JSON.parse(params.form_data);
            params.form_data.emails = (document.querySelector('.super-raw-code-emails-settings textarea') ? document.querySelector('.super-raw-code-emails-settings textarea').value : '');
            params.form_data.emails  = params.form_data.emails.trim();
            if(params.form_data.emails!=='') params.form_data.emails = JSON.parse(params.form_data.emails);
            params.form_data = JSON.stringify(params.form_data);
        }catch(e){
            alert('Unable to save form due to JSON parse error, check the browser console for more details.');
            console.log(e);
            return;
        }
        return params;
    };
    SUPER.before_submit_hook = function(args){
        var proceed=true, i, name, functions = super_common_i18n.dynamic_functions.before_submit_hook;
        if(typeof functions !== 'undefined'){
            for( i = 0; i < functions.length; i++){
                name = functions[i].name;
                if(typeof SUPER[name] === 'undefined') continue;
                var result = SUPER[name](args);
                result = JSON.parse(result);
                // Check for errors, if there are any display them to the user 
                if(result.error===true){
                    proceed = false;
                    var ii,
                        nodes = document.querySelectorAll('.super-msg'),
                        html = '<div class="super-msg super-error">';
                    for (ii = 0; ii < nodes.length; ii++) { 
                        nodes[ii].remove();
                    }
                    if(typeof result.fields !== 'undefined'){
                        $.each(result.fields, function( index, value ) {
                            $(value+'[name="'+index+'"]').parent().addClass('error');
                        });
                    }                               
                    html += result.msg;
                    html += '<span class="super-close"></span>';
                    html += '</div>';
                    $(html).prependTo($(args.form));
                    SUPER.init_resend_verification_code(args);
                    var btn = args.form.querySelector('.super-form-button.super-loading');
                    if(btn) {
                        var btnName = btn.querySelector('.super-button-name');
                        btnName.innerHTML = args.oldHtml;
                        btn.classList.remove('super-loading');
                    }
                    SUPER.scrollToElement(args.form0);
                }
            }
        }
        // Submit form if we may proceed
        if(proceed) args.callback();
    };
    SUPER.before_email_send_hook = function(args){
        var i, name, found = 0, functions = super_common_i18n.dynamic_functions.before_email_send_hook;
        if(typeof functions !== 'undefined'){
            for( i = 0; i < functions.length; i++){
                name = functions[i].name;
                if(typeof SUPER[name] === 'undefined') continue;
                found++;
                SUPER[name](args);
            }
        }
        // Call callback function when no functions were defined by third party add-ons
        if(found==0) args.callback(args);
    };
    SUPER.before_validating_form_hook = function(args){
        var i, name, functions = super_common_i18n.dynamic_functions.before_validating_form_hook;
        if(typeof functions !== 'undefined'){
            for( i = 0; i < functions.length; i++){
                name = functions[i].name;
                if(typeof SUPER[name] === 'undefined') continue;
                SUPER[name](args);
            }
        }
    };
    SUPER.after_validating_form_hook = function(changedField, form){
        var i, name, functions = super_common_i18n.dynamic_functions.after_validating_form_hook;
        if(typeof functions !== 'undefined'){
            for( i = 0; i < functions.length; i++){
                name = functions[i].name;
                if(typeof SUPER[name] === 'undefined') continue;
                SUPER[name](changedField, form);
            }
        }
    };
    SUPER.after_initializing_forms_hook = function(args){
        var i, name, functions = super_common_i18n.dynamic_functions.after_initializing_forms_hook;
        if(typeof functions !== 'undefined'){
            for( i = 0; i < functions.length; i++){
                name = functions[i].name;
                if(typeof SUPER[name] === 'undefined') continue;
                SUPER[name](args);
            }
        }
        // Replace {tags} for any fields with default value that contains tags
        var form = SUPER.get_frontend_or_backend_form(args);
        var defaultValues = form.querySelectorAll('.super-replace-tags .super-shortcode-field');
        if(typeof defaultValues !== 'undefined'){
            for(i = 0; i<defaultValues.length; i++){
                var oldValue = defaultValues[i].value;
                defaultValues[i].value = SUPER.update_variable_fields.replace_tags({form: form, value: defaultValues[i].value, defaultValues: true});
                var newValue = defaultValues[i].value;
                // If values changed
                if(oldValue!=newValue){
                    SUPER.after_field_change_blur_hook({el: defaultValues[i]});
                }
                defaultValues[i].closest('.super-replace-tags').classList.remove('super-replace-tags');
            }
        }
        args.callback(args);
    };

    // @since 3.6.0 - function to retrieve either the form element in back-end preview mode or on front-end
    SUPER.get_frontend_or_backend_form = function(args){
        var final_form = (typeof args.form === 'undefined' ? undefined : args.form);
        if(args.el){
            // If field exists, try to find parent
            if(args.el.closest('.super-form')) final_form = args.el.closest('.super-form');
            if(args.el.closest('.super-preview-elements')) final_form = args.el.closest('.super-preview-elements');
        }else{
            // If field doesn't exist
            if(!args.form){
                if(document.querySelector('.super-preview-elements')) final_form = document.querySelector('.super-preview-elements');
                if(document.querySelector('.super-live-preview')) final_form = document.querySelector('.super-live-preview');
            }else{
                final_form = args.form;
            }
        }
        // If we couldn't find anything return document body
        if(!final_form) final_form = document.body;
        // @since 3.7.0 - check if we need to change the $form element to the form level instead of multi-part level
        if(final_form && final_form.classList.contains('super-multipart')){
            final_form = final_form.closest('.super-form');
        }
        return final_form;
    };
    // After field value changed
    SUPER.after_field_change_blur_timeout = {};
    SUPER.after_field_change_blur_hook = function(args){
        if(args.el && args.el.closest('.super-shortcode')){
            if(args.el.value===''){
                args.el.closest('.super-shortcode').classList.remove('super-filled');
            }else{
                args.el.closest('.super-shortcode').classList.add('super-filled');
            }
        }
        args.form = SUPER.get_frontend_or_backend_form(args);
        if (SUPER.after_field_change_blur_timeout[args.form.dataset.sfuid] !== null) {
            clearTimeout(SUPER.after_field_change_blur_timeout[args.form.dataset.sfuid]);
        }
        SUPER.after_field_change_blur_timeout[args.form.dataset.sfuid] = setTimeout(function(){}, 555);
        // tmp disabled not sure? // Skip if google address autocomplete is enabled
        // tmp disabled not sure? if(args.el && args.el.classList.contains('super-address-autopopulate')){
        // tmp disabled not sure?     if( typeof args.el !== 'undefined'  && (args.skip!==true) ) {
        // tmp disabled not sure?         SUPER.auto_step_multipart(args);
        // tmp disabled not sure?     }
        // tmp disabled not sure?     return;
        // tmp disabled not sure? }
        // tmp disabled not sure? // Otherwise continue
        var formId = SUPER.getFormIdByAttributeID(args.form);
        if(typeof SUPER.preFlightMappings==='undefined') SUPER.preFlightMappings = {};
        if(typeof SUPER.preFlightMappings[formId]==='undefined') SUPER.preFlightMappings[formId] = {fieldNames: [], tags: {}}

        // Reset preflight values
        if(args.el && SUPER.preFlightMappings[formId].fieldNames[args.el.name]){
            for(var i=0; i<SUPER.preFlightMappings[formId].fieldNames[args.el.name].length; i++){
                var indexMapping = SUPER.preFlightMappings[formId].fieldNames[args.el.name][i];
                delete SUPER.preFlightMappings[formId].tags[indexMapping];
            }
            delete SUPER.preFlightMappings[formId].fieldNames[args.el.name];
        }
        // Values that we always need to reset
        delete SUPER.preFlightMappings[formId].tags['{pdf_page}'];
        delete SUPER.preFlightMappings[formId].tags['{dynamic_column_counter}'];

        var $functions = super_common_i18n.dynamic_functions.after_field_change_blur_hook;
        jQuery.each($functions, function(key, value){
            if(typeof SUPER[value.name] !== 'undefined') {
                SUPER[value.name](args);
            }
        });
        if( typeof args.el !== 'undefined'  && (args.skip!==true) ) {
            // If radio
            if(args.el.closest('.super-radio')){
                if(args.el.closest('.super-focus')){
                    if(typeof SUPER.preventGoingToNextMultipart!=='undefined'){
                        SUPER.preventGoingToNextMultipart = undefined;
                    }else{
                        SUPER.preventGoingToNextMultipart = undefined;
                        SUPER.auto_step_multipart(args, true);
                    }
                }
            }else{
                // Not if textarea
                if(!args.el.closest('.super-textarea')){
                    SUPER.auto_step_multipart(args, true);
                }
            }
        }
        if(typeof args.skipHtmlUpdate==='undefined' || args.skipHtmlUpdate===false){
            SUPER.update_html_elements(args);
        }
        SUPER.save_form_progress(args);
    };

    // @since 4.9.0 - hook so that add-ons can initialize their elements more easily
    SUPER.after_init_common_fields = function(){
        var $functions = super_common_i18n.dynamic_functions.after_init_common_fields;
        jQuery.each($functions, function(key, value){
            if(typeof SUPER[value.name] !== 'undefined') {
                SUPER[value.name]();
            }
        });
    };

    SUPER.update_html_elements_timeout = {};
    SUPER.update_html_elements = function(args){
		if (SUPER.update_html_elements_timeout[args.form.dataset.sfuid] !== null) {
			clearTimeout(SUPER.update_html_elements_timeout[args.form.dataset.sfuid]);
		}
        SUPER.update_html_elements_timeout[args.form.dataset.sfuid] = setTimeout(function () {
            SUPER.init_replace_html_tags({el: undefined, form: args.form, skipHtmlUpdate: true});
        }, 10);
    };

    // @since 3.2.0 - save form progress
    SUPER.save_form_progress_timeout = {};
    SUPER.save_form_progress = function(args){
        if(args.form.classList.contains('super-save-progress')){
            if (SUPER.save_form_progress_timeout[args.form.dataset.sfuid] !== null) {
                clearTimeout(SUPER.save_form_progress_timeout[args.form.dataset.sfuid]);
            }
            SUPER.save_form_progress_timeout[args.form.dataset.sfuid] = setTimeout(function () {
                SUPER.prepare_form_data($(args.form), function(formData){
                    var $form_id = formData.form_id;
                    formData = SUPER.after_form_data_collected_hook(formData.data, args.form0);
                    SUPER.save_form_progress_request(formData, $form_id);
                }, false); // define false, to skip saving nonce (not required when saving progress)
            }, 1000); // 1 second timeout, to make sure that we do not make unnecessary requests to the server
        }
    };

    SUPER.save_form_progress_request = function($data, $form_id){
        var formData = new FormData();
        formData.append('action', 'super_save_form_progress');
        formData.append('form_id', $form_id);
        var data = JSON.stringify($data);
        if(SUPER.form_js && SUPER.form_js[$form_id] && SUPER.form_js[$form_id]['_entry_data']){
            // Updating the entry data on the object is required for language switcher to get the latest form progression data.
            SUPER.form_js[$form_id]['_entry_data'] = data;
        }
        formData.append('data', data);
        $.ajax({
            type: 'post',
            url: super_common_i18n.ajaxurl,
            data: formData,
            async: true,
            cache: false,
            contentType: false,
            processData: false,
            timeout: 60000*5, // 5m
            xhr: function() {
                return new window.XMLHttpRequest();
            },
            error: function (xhr, ajaxOptions, thrownError) {
                // eslint-disable-next-line no-console
                console.log(xhr, ajaxOptions, thrownError);
            }
        });
    };

    // @since 1.2.8 
    SUPER.after_email_send_hook = function(args){
        var $event,
            ga = window[window.GoogleAnalyticsObject || 'ga'],
            $ga_tracking,
            $proceed,
            $values,
            $parameters;

        if (typeof ga == 'function') {
            $ga_tracking = super_common_i18n.ga_tracking;
            $ga_tracking = $ga_tracking.split('\n');
            $($ga_tracking).each(function(index, value){
                // Check if this is a global event or for a specific form (based on form ID):
                $proceed = true;
                $values = value.split(":");
                if($values.length>1){
                    $event = $values[1].split("|");
                    if(!args.form.hasClass('super-form-'+$values[0])){
                        $proceed = false;
                    }
                }else{
                    $event = $values[0].split("|");
                }

                // Only proceed if this was an event that needs to be executed globally, or if the ID matches the submitted form
                if($proceed){
                    if( ( (typeof $event[1] === 'undefined') || ($event[1]==='') ) || 
                        ( (typeof $event[2] === 'undefined') || ($event[2]==='') ) ) {
                        // eslint-disable-next-line no-console
                        console.log('Seems like we are missing required ga() parameters!');
                    }else{

                        // Event Tracking
                        if( ($event[0]=='send') && ($event[1]=='event') ) {
                            if( (typeof $event[3] === 'undefined') || ($event[3]==='') ) {
                                // eslint-disable-next-line no-console
                                console.log('ga() is missing the "eventAction" parameter (The type of interaction e.g. "play")');
                            }else{
                                $parameters = {};
                                $parameters.hitType = $event[1];
                                $parameters.eventCategory = $event[2];
                                $parameters.eventAction = $event[3];
                                if( typeof $event[4] !== 'undefined' ) {
                                    $parameters.eventLabel = $event[4];
                                }
                                if( typeof $event[5] !== 'undefined' ) {
                                    $parameters.eventValue = $event[5];
                                }
                                ga($event[0], $parameters);
                            }
                        }
                    }
                }

            });
        }
        var $functions = super_common_i18n.dynamic_functions.after_email_send_hook;
        jQuery.each($functions, function(key, value){
            if(typeof SUPER[value.name] !== 'undefined') {
                SUPER[value.name](args);
            }
        });
    };

    // @since 1.3
    SUPER.after_responsive_form_hook = function($classes, $form, $new_class, $window_classes, $new_window_class){
        var $functions = super_common_i18n.dynamic_functions.after_responsive_form_hook;
        jQuery.each($functions, function(key, value){
            if(typeof SUPER[value.name] !== 'undefined') {
                SUPER[value.name]($classes, $form, $new_class, $window_classes, $new_window_class);
            }
        });    
    };

    // Grab fields data and return all data as an object
    SUPER.prepare_form_data_fields = function($form){
        var $data = {},
            $field,
            $files;

        $form.find('.super-shortcode-field').each(function(){
            var $this = $(this),
                $hidden = false,
                $parent = $this.parents('.super-shortcode:eq(0)'),
                $i,
                $new_value,
                $selected_items,
                $email_value,
                $item_value;

            // Proceed only if it's a valid field (which must have a field name)
            if(typeof $this.attr('name')==='undefined') {
                return true;
            }

            $this.parents('.super-shortcode.super-column').each(function(){
                if($(this).css('display')=='none'){
                    $hidden = true;
                }
            });
            
            if( ( $hidden===true )  || ( ( $parent.css('display')=='none' ) && ( !$parent.hasClass('super-hidden') ) ) ) {
                // Exclude conditionally
            }else{
                // First replace %d with dynamic column number for E-mail label setting
                var $emailLabel = $this.data('email');
                if($emailLabel && $emailLabel.toString().indexOf('%d')!==-1){
                    var $dynamicParentIndex = $($this).parents('.super-duplicate-column-fields:eq(0)').index();
                    $emailLabel = SUPER.replaceAll($emailLabel, '%d', $dynamicParentIndex+1);
                    $this.data('email', $emailLabel);
                }
                if($this.hasClass('super-fileupload')){
                    $parent = $this.parents('.super-field-wrapper:eq(0)');
                    $field = $parent.find('.super-active-files');
                    $files = $parent.find('.super-fileupload-files > div');
                    $data[$field.attr('name')] = {
                        'label':$field.data('email'),
                        'type':'files',
                        'exclude':$field.data('exclude'),
                        'exclude_entry':$field.data('exclude-entry'),
                        'files':{}};
                    $files.each(function($index,$file){
                        $file = $(this);
                        $data[$field.attr('name')].files[$index] = { 
                            'name':$field.attr('name'),
                            'value':$file.attr('data-name'),
                            'url':$file.attr('data-url'),
                            'label':$field.data('email'),
                            'exclude':$field.data('exclude'),
                            'exclude_entry':$field.data('exclude-entry'),
                            'excludeconditional':$field.data('excludeconditional'),
                        };
                    });
                }else{
                    $data[$this.attr('name')] = {
                        'name':$this.attr('name'),
                        'value':$this.val(),
                        'label':$this.data('email'),
                        'exclude':$this.data('exclude'),
                        'replace_commas':$this.data('replace-commas'),
                        'exclude_entry':$this.data('exclude-entry'),
                        'excludeconditional':$this.data('excludeconditional'),
                        'type':'var'
                    };

                    if($this.attr('name')==='mailchimp_list_id'){
                        if($this.attr('data-subscriber-tags')) $data[$this.attr('name')].subscriber_tags = $this.attr('data-subscriber-tags');
                        if($this.attr('data-vip')) $data[$this.attr('name')].vip = $this.attr('data-vip');
                    }
                    var $super_field = $this.parents('.super-field:eq(0)');

                    // Check if international phonenumber field
                    if($super_field.hasClass('super-int-phone-field')){
                        var intPhone = window.superTelInputGlobals.getInstance($this[0]);
                        $data[$this.attr('name')].value = intPhone.getNumber();
                    }

                    if($super_field.hasClass('super-field-type-datetime-local')){
                        $data[$this.attr('name')].timestamp = $this[0].dataset.mathDiff;
                    }
                    if($super_field.hasClass('super-date')){
                        $data[$this.attr('name')].timestamp = $this[0].dataset.mathDiff;
                    }

                    if($super_field.hasClass('super-textarea')){
                        $data[$this.attr('name')].type = 'text';
                    }
                    if($super_field.hasClass('super-html')){
                        $data[$this.attr('name')].type = 'html';
                    }

                    // @since 3.2.0 - also save lat and lng for ACF google maps compatibility
                    if($this.hasClass('super-address-autopopulate')){
                        $data[$this.attr('name')].type = 'google_address';
                        $data[$this.attr('name')].geometry = {
                            location: {
                                'lat':$this.data('lat'),
                                'lng':$this.data('lng'),
                            }
                        };
                    }
                    
                    // @since 2.2.0 - generate unique code (make sure to save it after form completion)
                    if($super_field.hasClass('super-hidden')){
                        if($this.data('code')===true) {
                            $data[$this.attr('name')].code = 'true';
                            if($this.attr('data-invoice-padding')){
                                $data[$this.attr('name')].invoice_padding = $this.attr('data-invoice-padding');
                            }
                        }
                    }

                    // @since 3.6.0 - replace correct data value for autosuggest fields
                    // @since 4.6.0 - replace correct data value for wc order search
                    if( $super_field.hasClass('super-auto-suggest') || $super_field.hasClass('super-wc-order-search') ) {
                        var $value = $super_field.find('.super-field-wrapper .super-dropdown-list > .super-active').attr('data-value');
                        if( typeof $value !== 'undefined' ) {
                            // Also make sure to always save the first value
                            $data[$this.attr('name')].value = $value.split(";")[0];
                        }
                    }

                    if( $super_field.hasClass('super-dropdown') ) {
                        $data[$this.attr('name')].raw_value = $data[$this.attr('name')].value;
                        $i = 0;
                        $new_value = '';
                        $selected_items = $super_field.find('.super-field-wrapper .super-dropdown-list > .super-active');
                        $selected_items.each(function(){
                            if($i===0){
                                $new_value += $(this).text();
                                if($this.data('admin-email-value')=='both') {
                                    $new_value += ' ('+$(this).data('value')+')';
                                }
                            }else{
                                $new_value += ', '+$(this).text();
                                if($this.data('admin-email-value')=='both') {
                                    $new_value += ' ('+$(this).data('value')+')';
                                }
                            }
                            $i++;
                        });
                        $data[$this.attr('name')].option_label = $new_value;

                        if( ($this.data('admin-email-value')=='label') || ($this.data('admin-email-value')=='both') ) {
                            $data[$this.attr('name')].admin_value = $new_value; 
                        }else{
                            $i = 0;
                            $new_value = '';
                            $selected_items.each(function(){
                                $item_value = $(this).data('value').toString().split(';');
                                if($i===0){
                                    $new_value += $item_value[0];
                                }else{
                                    $new_value += ', '+$item_value[0];
                                }
                                $i++;
                            });
                            $data[$this.attr('name')].value = $new_value; 
                        }
                        $email_value = $this.data('confirm-email-value');
                        if( ($email_value=='label') || ($email_value=='both') ) {
                            $i = 0;
                            $new_value = '';
                            $selected_items.each(function(){
                                $item_value = $(this).data('value').toString().split(';');
                                if($i===0){
                                    $new_value += $(this).text();
                                    if($email_value=='both') {
                                        $new_value += ' ('+$item_value[0]+')';
                                    }
                                }else{
                                    $new_value += ', '+$(this).text();
                                    if($email_value=='both') {
                                        $new_value += ' ('+$item_value[0]+')';
                                    }
                                }
                                $i++;
                            });
                            $data[$this.attr('name')].confirm_value = $new_value; 
                        }
                        $email_value = $this.data('contact-entry-value');
                        if( ($email_value=='label') || ($email_value=='both') ) {
                            $i = 0;
                            $new_value = '';
                            $selected_items.each(function(){
                                $item_value = $(this).data('value').toString().split(';');
                                if($i===0){
                                    $new_value += $(this).text();
                                    if($email_value=='both') {
                                        $new_value += ' ('+$item_value[0]+')';
                                    }
                                }else{
                                    $new_value += ', '+$(this).text();
                                    if($email_value=='both') {
                                        $new_value += ' ('+$item_value[0]+')';
                                    }
                                }
                                $i++;
                            });
                            $data[$this.attr('name')].entry_value = $new_value; 
                        }
                    }
                    if( $super_field.hasClass('super-checkbox') || $super_field.hasClass('super-radio') ) {
                        $data[$this.attr('name')].raw_value = $data[$this.attr('name')].value;
                        $i = 0;
                        $new_value = '';
                        $selected_items = $super_field.find('.super-field-wrapper .super-active');
                        $selected_items.each(function(){
                            $item_value = $(this).find('input').val().toString().split(';');
                            if($i===0){
                                $new_value += $(this).text();
                                if($this.data('admin-email-value')=='both') {
                                    $new_value += ' ('+$item_value[0]+')';
                                }
                            }else{
                                $new_value += ', '+$(this).text();
                                if($this.data('admin-email-value')=='both') {
                                    $new_value += ' ('+$item_value[0]+')';
                                }
                            }
                            $i++;
                        });
                        $data[$this.attr('name')].option_label = $new_value;

                        if( ($this.data('admin-email-value')=='label') || ($this.data('admin-email-value')=='both') ) {
                            $data[$this.attr('name')].admin_value = $new_value; 
                        }else{
                            $i = 0;
                            $new_value = '';
                            $selected_items.each(function(){
                                $item_value = $(this).find('input').val().toString().split(';');
                                if($i===0){
                                    $new_value += $item_value[0];
                                }else{
                                    $new_value += ','+$item_value[0];
                                }
                                $i++;
                            });
                            $data[$this.attr('name')].value = $new_value; 
                        }
                        $email_value = $this.data('confirm-email-value');
                        if( ($email_value=='label') || ($email_value=='both') ) {
                            $i = 0;
                            $new_value = '';
                            $selected_items.each(function(){
                                $item_value = $(this).find('input').val().toString().split(';');
                                if($i===0){
                                    $new_value += $(this).text();
                                    if($email_value=='both') {
                                        $new_value += ' ('+$item_value[0]+')';
                                    }
                                }else{
                                    $new_value += ', '+$(this).text();
                                    if($email_value=='both') {
                                        $new_value += ' ('+$item_value[0]+')';
                                    }
                                }
                                $i++;
                            });
                            $data[$this.attr('name')].confirm_value = $new_value; 
                        }
                        $email_value = $this.data('contact-entry-value');
                        if( ($email_value=='label') || ($email_value=='both') ) {
                            $i = 0;
                            $new_value = '';
                            $selected_items.each(function(){
                                $item_value = $(this).find('input').val().toString().split(';');
                                if($i===0){
                                    $new_value += $(this).text();
                                    if($email_value=='both') {
                                        $new_value += ' ('+$item_value[0]+')';
                                    }
                                }else{
                                    $new_value += ', '+$(this).text();
                                    if($email_value=='both') {
                                        $new_value += ' ('+$item_value[0]+')';
                                    }
                                }
                                $i++;
                            });
                            $data[$this.attr('name')].entry_value = $new_value; 
                        }
                    }
                }
            }
        });
        return $data;
    };

    // @since 3.2.0 - prepare form data
    SUPER.prepare_form_data = function($form, callback, createNonce){
        if(typeof createNonce === 'undefined') createNonce = true;
        var $data = SUPER.prepare_form_data_fields($form),
            $form_id = '',
            $entry_id = '',
            $list_id = '',
            $dynamic_columns = {},
            $dynamic_arrays = [],
            $map_key_names = [],
            $first_property_name,
            new_key,
            i,
            $dynamic_column_fields_data;

        // Loop through all dynamic columns and create a JSON string based on all the fields
        $form.find('.super-column[data-duplicate-limit]').each(function(){
            $dynamic_arrays = [];
            $map_key_names = [];
            $first_property_name = undefined;
            $(this).find('> .super-duplicate-column-fields, > .super-column-custom-padding > .super-duplicate-column-fields').each(function(){
                $dynamic_column_fields_data = SUPER.prepare_form_data_fields($(this));
                if(typeof $first_property_name === 'undefined'){
                    $first_property_name = Object.getOwnPropertyNames($dynamic_column_fields_data)[0];
                }
                $dynamic_arrays.push($dynamic_column_fields_data);
            });
            if($first_property_name!==undefined){
                Object.keys($dynamic_arrays[0]).forEach(function(key) {
                    $map_key_names.push(key);
                });
                Object.keys($dynamic_arrays).forEach(function(key) {
                    if(key>0){
                        i = 0;
                        Object.keys($dynamic_arrays[key]).forEach(function(old_key) {
                            new_key = $map_key_names[i];
                            if (old_key !== new_key) {
                                Object.defineProperty($dynamic_arrays[key], new_key, Object.getOwnPropertyDescriptor($dynamic_arrays[key], old_key));
                                delete $dynamic_arrays[key][old_key];
                            }
                            i++;
                        });
                    }
                });
                $dynamic_columns[$first_property_name] = $dynamic_arrays;
            }
        });
        if(Object.keys($dynamic_columns).length>0){
            $data._super_dynamic_data = $dynamic_columns;
        }

        if($form.find('input[name="hidden_form_id"]').length !== 0) {
            $form_id = $form.find('input[name="hidden_form_id"]').val();
        }
        $data.hidden_form_id = {
            'name':'hidden_form_id',
            'value':$form_id,
            'type':'form_id'
        };

        // @since 2.2.0 - update contact entry by ID
        if($form.find('input[name="hidden_contact_entry_id"]').length !== 0) {
            $entry_id = $form.find('input[name="hidden_contact_entry_id"]').val();
        }
        $data.hidden_contact_entry_id = {
            'name':'hidden_contact_entry_id',
            'value':$entry_id,
            'type':'entry_id'
        };

        // When editing entry via Listings add-on
        if($form.find('input[name="hidden_list_id"]').length !== 0) {
            $list_id = $form.find('input[name="hidden_list_id"]').val();
        }

        if(createNonce===false){
            if(typeof callback === 'function'){
                callback({
                    data:$data, 
                    form_id:$form_id, 
                    entry_id:$entry_id, 
                    list_id:$list_id,
                    sf_nonce: $form.find('input[name="sf_nonce"]').val()
                });
            }
            return;
        }

        // Generate new nonce
        $.ajax({
            url: super_common_i18n.ajaxurl,
            type: 'post',
            data: {
                action: 'super_create_nonce'
            },
            success: function (nonce) {
                // Update new nonce
                $form.find('input[name="sf_nonce"]').val(nonce.trim());
            },
            complete: function(){
                if(typeof callback === 'function'){
                    callback({
                        data:$data, 
                        form_id:$form_id, 
                        entry_id:$entry_id, 
                        list_id:$list_id,
                        sf_nonce: $form.find('input[name="sf_nonce"]').val()
                    });
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                // eslint-disable-next-line no-console
                console.log(xhr, ajaxOptions, thrownError);
                alert('Could not generate nonce');
            }
        });

    };

    // @since 1.3
    SUPER.after_form_data_collected_hook = function(data, form0){
        var i, name, functions = super_common_i18n.dynamic_functions.after_form_data_collected_hook;
        for ( i = 0; i < functions.length; i++) {
            name = functions[i].name;
            if(typeof SUPER[name] !== 'undefined') {
                data = SUPER[name](data, form0);
            }
        }
        return data;
    };

    // @since 1.3
    SUPER.after_duplicate_column_fields_hook = function(el, field, counter, column, fieldNames, fieldLabels){
        var i, name, functions = super_common_i18n.dynamic_functions.after_duplicate_column_fields_hook;
        for ( i = 0; i < functions.length; i++) {
            name = functions[i].name;
            if(typeof SUPER[name] !== 'undefined') {
                SUPER[name](el, field, counter, column, fieldNames, fieldLabels);
            }
        }
    };

    // @since 3.3.0
    SUPER.after_appending_duplicated_column_hook = function(form, uniqueFieldNames, clone){
        var i, name, functions = super_common_i18n.dynamic_functions.after_appending_duplicated_column_hook;
        for ( i = 0; i < functions.length; i++) {
            name = functions[i].name;
            if(typeof SUPER[name] !== 'undefined') {
                SUPER[name](form, uniqueFieldNames, clone);
            }
        }
    };

    // @since 2.4.0
    SUPER.after_duplicating_column_hook = function(form, uniqueFieldNames, clone){
        var i, name, functions = super_common_i18n.dynamic_functions.after_duplicating_column_hook;
        for ( i = 0; i < functions.length; i++) {
            name = functions[i].name;
            if(typeof SUPER[name] !== 'undefined') {
                SUPER[name](form, uniqueFieldNames, clone);
            }
        }
    };

    // @since 1.9
    SUPER.before_submit_button_click_hook = function(e, $submit_button){
        var $proceed = true;
        var $functions = super_common_i18n.dynamic_functions.before_submit_button_click_hook;
        jQuery.each($functions, function(key, value){
            if(typeof SUPER[value.name] !== 'undefined') {
                $proceed = SUPER[value.name](e, $proceed, $submit_button);
            }
        });
        return $proceed;
    };
    SUPER.after_preview_loaded_hook = function($form_id){
        var $functions = super_common_i18n.dynamic_functions.after_preview_loaded_hook;
        jQuery.each($functions, function(key, value){
            if(typeof SUPER[value.name] !== 'undefined') {
                SUPER[value.name]($form_id);
            }
        });
    };

    // @since 2.0.0
    SUPER.after_form_cleared_hook = function(form){
        var functions = super_common_i18n.dynamic_functions.after_form_cleared_hook;
        jQuery.each(functions, function(key, value){
            if(typeof SUPER[value.name] !== 'undefined') {
                SUPER[value.name](form);
            }
        });
    };

    // @since 2.1.0
    SUPER.before_scrolling_to_error_hook = function(form, scroll){
        var proceed = true;
        var functions = super_common_i18n.dynamic_functions.before_scrolling_to_error_hook;
        jQuery.each(functions, function(key, value){
            if(typeof SUPER[value.name] !== 'undefined') {
                proceed = SUPER[value.name](proceed, form, scroll);
            }
        });
        return proceed;
    };
    SUPER.before_scrolling_to_message_hook = function($form, $scroll){
        var $proceed = true;
        var $functions = super_common_i18n.dynamic_functions.before_scrolling_to_message_hook;
        jQuery.each($functions, function(key, value){
            if(typeof SUPER[value.name] !== 'undefined') {
                $proceed = SUPER[value.name]($proceed, $form, $scroll);
            }
        });
        return $proceed;
    };

    // @since 3.0.0 - google maps API for places auto complete/populate and other libraries such as:
    
    // drawing provides a graphical interface for users to draw polygons, rectangles, polylines, circles, and markers on the map. 
    // Consult the Drawing library documentation for more information.

    // geometry includes utility functions for calculating scalar geometric values (such as distance and area) on the surface of the earth. 
    // Consult the Geometry library documentation for more information.

    // places enables your application to search for places such as establishments, geographic locations, or prominent points of interest, within a defined area.
    // Consult the Places library documentation for more information.

    // visualization provides heatmaps for visual representation of data.
    // Consult the Visualization library documentation for more information.

    SUPER.google_maps_api = function(){};
    SUPER.google_maps_init = function(args){
        if(typeof args === 'undefined') args = {};
        if(!args.form) return true;
        // @since 3.0.0
        SUPER.google_maps_api.initAutocomplete(args);
        // @since 3.5.0
        SUPER.google_maps_api.initMaps(args);
    };

    SUPER.get_field_name = function(field){
        if(field.classList.contains('super-fileupload')){
            field = field.parentNode.querySelector('.super-active-files');
        }
        if(field.name) return field.name;
    };
    SUPER.get_original_field_name = function(field){
        if(field.classList.contains('super-fileupload')){
            field = field.parentNode.querySelector('.super-active-files');
        }
        if(field.dataset.oname) return field.dataset.oname;
    };
    SUPER.get_field_type = function(form, name){
        var type = 'text';
        if(!form) {
            return {field: undefined, type: type}
        }
        var node = form.querySelector('.super-shortcode-field[name="'+name+'"]');
        if(node){
            type = node.type;
            if(node.parentNode.closest('.super-checkbox')) type = 'checkbox';
            if(node.parentNode.closest('.super-dropdown')) type = 'dropdown';
            return {field: node, type: type, oname: node.dataset.oname}
        }
        node = form.querySelector('.super-active-files[name="'+name+'"]');
        if(node){
            return {field: node, type: 'file', oname: node.dataset.oname}
        }
        return {field: undefined, type: type}
    };

    SUPER.strip_tags = function(input,allowed){
        allowed = (((allowed || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
        var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
        return input.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) { return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : ''; });
    }


    // @since 3.5.0 - function for intializing google maps elements
    SUPER.google_maps_api.allMaps = [];
    SUPER.google_maps_api.initMaps = function(args){
        var $form_id = 0;
        if(args.form.querySelector('input[name="hidden_form_id"]')){
            $form_id = args.form.querySelector('input[name="hidden_form_id"]').value;
        }

        if(typeof SUPER.google_maps_api.allMaps[$form_id] === 'undefined'){
            SUPER.google_maps_api.allMaps[$form_id] = [];
        }

        var $maps;
        if(!args.el){
            $maps = args.form.querySelectorAll('.super-google-map:not(.super-map-rendered)');
        }else{
            var field_name = SUPER.get_original_field_name(args.el);
            $maps = args.form.querySelectorAll('.super-google-map[data-fields*="{'+field_name+'}"]');
        }

        // Loop through maps
        Object.keys($maps).forEach(function(key) {
            $maps[key].classList.add('super-map-rendered');
            var $data = JSON.parse($maps[key].querySelector('textarea').value);
                // Address Marker location
                args.value = $data.address;
                var $address = SUPER.update_variable_fields.replace_tags(args);
                // Directions API (route)
                args.value = $data.origin;
                var $origin = SUPER.update_variable_fields.replace_tags(args);
                args.value = $data.destination;
                var $destination = SUPER.update_variable_fields.replace_tags(args);
                args.value = $data.directionsPanel;
                var $directionsPanel = SUPER.update_variable_fields.replace_tags(args);
                var $populateDistance = $data.populateDistance;
                var $populateDuration = $data.populateDuration;
                args.value = $data.travelMode;
                var $travelMode = SUPER.update_variable_fields.replace_tags(args);
                args.value = $data.unitSystem;
                var $unitSystem = SUPER.update_variable_fields.replace_tags(args);
                // Waypoints
                args.value = $data.optimizeWaypoints;
                var $optimizeWaypoints = SUPER.update_variable_fields.replace_tags(args);
                args.value = $data.provideRouteAlternatives;
                var $provideRouteAlternatives = SUPER.update_variable_fields.replace_tags(args);
                args.value = $data.avoidFerries;
                var $avoidFerries = SUPER.update_variable_fields.replace_tags(args);
                args.value = $data.avoidHighways;
                var $avoidHighways = SUPER.update_variable_fields.replace_tags(args);
                args.value = $data.avoidTolls;
                var $avoidTolls = SUPER.update_variable_fields.replace_tags(args);
                args.value = $data.region;
                var $region = SUPER.update_variable_fields.replace_tags(args);
                // we will implement this in a later version  DRIVING mode (only when travelMode is DRIVING)
                // we will implement this in a later version  $departureTime = SUPER.update_variable_fields.replace_tags(args),
                // we will implement this in a later version  $trafficModel = SUPER.update_variable_fields.replace_tags(args),
                // we will implement this in a later version  TRANSIT mode (only when travelMode is TRANSIT)
                // we will implement this in a later version  $transitArrivalTime = SUPER.update_variable_fields.replace_tags(args),
                // we will implement this in a later version  $transitDepartureTime = SUPER.update_variable_fields.replace_tags(args),
                // we will implement this in a later version  $transitModes = SUPER.update_variable_fields.replace_tags(args),
                // we will implement this in a later version  $routingPreference = SUPER.update_variable_fields.replace_tags(args),
                // UI Settings
                args.value = $data.disableDefaultUI;
                var $disableDefaultUI = SUPER.update_variable_fields.replace_tags(args);
                args.value = $data.zoom;
                var $zoom = SUPER.update_variable_fields.replace_tags(args);
                if($zoom==='') $zoom = 5; // Default to 5
                $zoom = parseInt($zoom, 10);
                var $address_marker = $data.address_marker,
                $polyline_stroke_weight = $data.polyline_stroke_weight,
                $polyline_stroke_color = $data.polyline_stroke_color,
                $polyline_stroke_opacity = $data.polyline_stroke_opacity,
                // currently not in use? $polyline_geodesic = $data.polyline_geodesic,
                $polylines,
                $path = [],
                $coordinates,
                $lat,
                $lng,
                $field_name,
                $lat_min = '',
                $lat_max = '',
                $lng_min = '',
                $lng_max = '',
                Path,
                geocoder;

                // Only if map wasn't yet initialized
                SUPER.google_maps_api.allMaps[$form_id][key] = new google.maps.Map(document.getElementById('super-google-map-'+$form_id), {
                    center: {lat: 0, lng: 0},
                    zoom: $zoom,
                    disableDefaultUI: ('true' === $disableDefaultUI),
                    //mapTypeId: \'terrain\'
                });
                SUPER.google_maps_api.allMaps[$form_id][key]['super_el'] = $maps[key];
                //SUPER.google_maps_api.allMaps[formId][i].setOptions({
                //});

            // Draw Polylines
            if( $data.enable_polyline=='true' ) {
                $polylines = $data.polylines.split('\n');
                $($polylines).each(function(index, value){
                    $coordinates = value.split("|");
                    $lat = $coordinates[0];
                    $lng = $coordinates[1];
                    // If {tag} was found
                    if(SUPER.tagsRegex.exec($lat)!==null){
                        $field_name = $lat.replace('{','').replace('}','');                       
                        $lat = SUPER.field(args.form, $field_name).dataset.lat;
                        if(!$lat) $lat = 0;
                    }
                    if(SUPER.tagsRegex.exec($lng)!==null){
                        $field_name = $lng.replace('{','').replace('}','');
                        $lng = SUPER.field(args.form, $field_name).dataset.lng;
                        if(!$lng) $lng = 0;
                    }
                    $lat = parseFloat($lat);
                    $lng = parseFloat($lng);
                    // Add markers at each point
                    if( $lat!==0 && $lng!==0 ) {
                        new google.maps.Marker({
                            position: {lat: $lat, lng: $lng},
                            map: SUPER.google_maps_api.allMaps[$form_id][key]
                        });
                    }
                    $path.push({lat: $lat, lng: $lng});
                    if( $lat_min==='' ) {
                        $lat_min = $lat;
                        $lat_max = $lat;
                        $lng_min = $lng;
                        $lng_max = $lng;
                    } 
                    if($lat_min>$lat) $lat_min = $lat;
                    if($lat_max<$lat) $lat_max = $lat;
                    if($lng_min>$lng) $lng_min = $lng;
                    if($lng_max<$lng) $lng_max = $lng;
                });
                if( $lat_min===0 || $lat_max===0 || $lng_min===0 || $lng_max===0 ) {
                    SUPER.google_maps_api.allMaps[$form_id][key].setCenter(new google.maps.LatLng(
                        (($lat_max + $lat_min) / 2.0),
                        (($lng_max + $lng_min) / 2.0)
                    ));                    
                }else{
                    SUPER.google_maps_api.allMaps[$form_id][key].setCenter(new google.maps.LatLng(
                        (($lat_max + $lat_min) / 2.0),
                        (($lng_max + $lng_min) / 2.0)
                    ));
                    SUPER.google_maps_api.allMaps[$form_id][key].fitBounds(new google.maps.LatLngBounds(
                        new google.maps.LatLng($lat_min, $lng_min), // bottom left
                        new google.maps.LatLng($lat_max, $lng_max) //top right
                    ));
                    Path = new google.maps.Polyline({
                        path: $path,
                        // currently not in use? geodesic: $polyline_geodesic,
                        strokeColor: $polyline_stroke_color,
                        strokeOpacity: $polyline_stroke_opacity,
                        strokeWeight: $polyline_stroke_weight
                    });
                    Path.setMap(SUPER.google_maps_api.allMaps[$form_id][key]);
                }
            }

            // If directions panel is enabled
            var target, panel=null;
            target = args.form.querySelector('.super-google-map-'+$form_id);
            if($directionsPanel=='true'){
                if(target.parentNode.querySelector('.super-google-map-directions')){
                    target.parentNode.querySelector('.super-google-map-directions').remove();
                }
                if( ($origin==='') || ($destination==='') ) {
                    target.parentNode.classList.remove('super-has-panel');
                }else{
                    target.parentNode.classList.add('super-has-panel');
                    // Only create if not exists
                    if(!target.parentNode.querySelector('.super-google-map-directions')){
                        panel = document.createElement('div');
                        panel.classList.add('super-google-map-directions');
                        panel.style.height = target.parentNode.offsetHeight+'px';
                        panel.style.overflowY = "scroll";
                        target.parentNode.appendChild(panel);
                        // this is only used when PDF addon is released var printBtn = document.createElement('div');
                        // this is only used when PDF addon is released printBtn.classList.add('super-google-map-print');
                        // this is only used when PDF addon is released printBtn.innerHTML = 'Print';
                        // this is only used when PDF addon is released target.parentNode.appendChild(printBtn);
                        // this is only used when PDF addon is released if(typeof jsPDF !== 'undefined') {
                        // this is only used when PDF addon is released     printBtn.addEventListener('click', function(){
                        // this is only used when PDF addon is released         var pdf = new jsPDF();
                        // this is only used when PDF addon is released         // Starting at page 1
                        // this is only used when PDF addon is released         if(typeof SUPER.generate_pdf !== 'undefined') {
                        // this is only used when PDF addon is released             SUPER.generate_pdf(target, pdf, 1, function(pdf){
                        // this is only used when PDF addon is released                 // Finally we download the PDF file
                        // this is only used when PDF addon is released                 pdf.save("download-page-1.pdf");
                        // this is only used when PDF addon is released             }); 
                        // this is only used when PDF addon is released         }
                        // this is only used when PDF addon is released     });
                        // this is only used when PDF addon is released }
                    }
                }
            }

            // Set Directions (route)
            if( ($origin!=='') && ($destination!=='') ) {
                var directionsService = new google.maps.DirectionsService();
                var directionsRenderer = new google.maps.DirectionsRenderer({
                    draggable: true,
                    map: SUPER.google_maps_api.allMaps[$form_id][key],
                    panel: ($directionsPanel=='true' ? SUPER.google_maps_api.allMaps[$form_id][i]['super_el'].querySelector('.super-google-map-directions') : null)
                    // panel: document.getElementById('right-panel')
                });
                //directionsRenderer.setMap($map);
                // If waypoints is not empty make sure to create a proper object for it
                if($data.waypoints!==''){
                    var w = $data.waypoints.split('\n');
                    var i = 0;
                    var $xw = [];
                    for( i=0; i < w.length; i++ ) {
                        // Get waypoint location
                        var v = w[i].split('|');
                        if(typeof v[1] === 'undefined') v[1] = 'false';
                        // {waypoint;2}
                        var location = v[0].replace('{','').replace('}','');
                        var isTag = false;
                        if(location!==v[0]) isTag = true;
                        var advancedTags = location.split(';');
                        var fieldName = advancedTags[0];
                        var originFieldName = fieldName;
                        var advancedIndex = "";
                        if(advancedTags[1]){
                            advancedIndex = advancedTags[1];
                        }

                        // {stopover;2}
                        var stopover = v[1].replace('{','').replace('}','');
                        var stopoverIsTag = false;
                        if(stopover!==v[1]) stopoverIsTag = true;
                        var stopoverAdvancedTags = stopover.split(';');
                        var stopoverFieldName = stopoverAdvancedTags[0];
                        var originStopoverFieldName = stopoverFieldName;
                        var stopoverAdvancedIndex = "";
                        if(stopoverAdvancedTags[1]){
                            stopoverAdvancedIndex = stopoverAdvancedTags[1];
                        }

                        // Check if either one is a tag, if so look for dynamic columns
                        if(isTag || stopoverIsTag){
                            var x=2;
                            var dynamicFieldName = originFieldName+'_'+x;
                            var stopoverDynamicFieldName = originStopoverFieldName+'_'+x;
                            var found = SUPER.field_exists(args.form, dynamicFieldName);
                            var stopoverFound = SUPER.field_exists(args.form, stopoverDynamicFieldName);
                            var rows = '';
                            while(found || stopoverFound){
                                // Location
                                var tag = '';
                                if(isTag){
                                    tag = '{'+dynamicFieldName+'}';
                                    if(advancedIndex!==''){ tag = '{'+dynamicFieldName+';'+advancedIndex+'}'; }
                                }else{
                                    tag = location;
                                }
                                rows += tag;

                                // Stopover
                                if(stopoverIsTag){
                                    tag = '{'+stopoverDynamicFieldName+'}';
                                    if(stopoverAdvancedIndex!==''){ tag = '{'+stopoverDynamicFieldName+';'+stopoverAdvancedIndex+'}'; }
                                }else{
                                    tag = stopover;
                                }
                                // Stopover
                                rows += "|"+tag+"\n";
                                // Find for next field and if it exists we add it
                                x++;
                                dynamicFieldName = fieldName+'_'+x;
                                found = SUPER.field_exists(args.form, dynamicFieldName)
                            }
                        }
                        var waypoints = w[i]+"\n"+rows;
                        var xw = waypoints.split("\n");
                        i = 0;
                        for(i=0; i < xw.length; i++){
                            if(xw[i]==='') continue;
                            var values = xw[i].split('|');
                            args.value = values[0];
                            location = SUPER.update_variable_fields.replace_tags(args);
                            // Waypoint may not be empty!
                            if(location==='') continue;
                            args.value = values[1];
                            stopover = SUPER.update_variable_fields.replace_tags(args);
                            stopover = ('true'===stopover); // convert to boolean
                            $xw.push({ location: location, stopover: stopover });
                        }
                    }
                }
                var request = {
                    origin: $origin,
                    destination: $destination,
                    travelMode: $travelMode,
                    unitSystem: google.maps.UnitSystem[$unitSystem],
                    waypoints: $xw, 
                    optimizeWaypoints: ('true' === $optimizeWaypoints),
                    provideRouteAlternatives: ('true' === $provideRouteAlternatives),
                    avoidFerries: ('true' === $avoidFerries),
                    avoidHighways: ('true' === $avoidHighways),
                    avoidTolls: ('true' === $avoidTolls),
                    region: $region // 'US', 'NL', 'DE', 'UK' etc.
                };

                // we will implement this in a later version  // transitOptions (only when travelMode is TRANSIT)
                // we will implement this in a later version  if($travelMode==='TRANSIT'){
                // we will implement this in a later version      request['transitOptions'] = {
                // we will implement this in a later version          arrivalTime: $transitArrivalTime,
                // we will implement this in a later version          departureTime: $transitDepartureTime,
                // we will implement this in a later version          modes: $transitModes, // ['BUS'],
                // we will implement this in a later version          routingPreference: $routingPreference // 'FEWER_TRANSFERS', 'LESS_WALKING'
                // we will implement this in a later version      }
                // we will implement this in a later version  }

                // we will implement this in a later version  // drivingOptions (only when travelMode is DRIVING)
                // we will implement this in a later version  if($travelMode==='DRIVING'){
                // we will implement this in a later version      if($departureTime==='') $departureTime = new Date(Date.now() + 0); // departureTime: new Date(Date.now() + N),  // for the time N milliseconds from now.
                // we will implement this in a later version      if($trafficModel==='') $trafficModel = 'bestguess';
                // we will implement this in a later version      request['drivingOptions'] = {
                // we will implement this in a later version          departureTime: $departureTime,
                // we will implement this in a later version          trafficModel: $trafficModel
                // we will implement this in a later version      }
                // we will implement this in a later version  }

                directionsService.route(request, function (result, status) {
                    if (status == 'OK') {
                        directionsRenderer.setDirections(result);
                        if($directionsPanel=='true'){
                            var totalDist = 0;
                            var totalTime = 0;
                            var myroute = result.routes[0];
                            for (var i = 0; i < myroute.legs.length; i++) {
                                totalDist += myroute.legs[i].distance.value; // indicates the distance in meters even when unitSystem is set to "IMPERIAL"
                                totalTime += myroute.legs[i].duration.value; // indicates the duration in seconds
                            }
                            // var distance;
                            // var unit = 'km';
                            // if($unitSystem=='IMPERIAL') {
                            //     // Convert meters to miles
                            //     distance = (totalDist / 1609).toFixed(1);
                            //     unit = 'mile';
                            // }else{
                            //     distance = (totalDist / 1000).toFixed(1);
                            // }
                            // var timeUnit;
                            // if((totalTime / 60).toFixed(2) >= 60){
                            //     timeUnit = "hours";
                            //     if((totalTime / 60).toFixed(2) == 60){
                            //         timeUnit = "hour";
                            //     }
                            // }else{
                            //     timeUnit = "minutes";
                            //     if((totalTime / 60).toFixed(2) == 1){
                            //         timeUnit = "minute";
                            //     }
                            // }
                            // var time = ((totalTime / 60).toFixed(2) < 60 ? (totalTime / 60).toFixed(0) : (totalTime / 60 / 60).toFixed(1));
                            // html = "<strong>Distance:</strong> " + distance + " "+unit+"<br /><strong>Duration:</strong> " + time + " " + timeUnit + "<br />" + html;
                            // panel.innerHTML = html;

                            // Populate fields with values if defined
                            var field; 
                            if($populateDistance!==''){
                                // Check if field exists in this form
                                if(SUPER.field_exists(args.form, $populateDistance)){
                                    field = SUPER.field(args.form, $populateDistance);
                                    field.value = totalDist; // indicates the distance in meters
                                    SUPER.after_field_change_blur_hook({el: field});
                                }
                            }
                            if($populateDuration!==''){
                                // Check if field exists in this form
                                if(SUPER.field_exists(args.form, $populateDuration)){
                                    field = SUPER.field(args.form, $populateDuration);
                                    field.value = totalTime; // indicates the duration in seconds
                                    SUPER.after_field_change_blur_hook({el: field});
                                }
                            }
                        }
                    }else{
                        // Display error message
                        result = {
                            msg: 'Route was not successful for the following reason: ' + status,
                            loading: true,
                            error: true
                        }
                        SUPER.form_submission_finished(args, result);
                    }
                });
                return true;
            }

            // Add Address Marker
            if( $address!=='' ) {
                geocoder = new google.maps.Geocoder();
                geocoder.geocode( { 'address': $address}, function(result, status) {
                    if (status == 'OK') {
                        // Center map based on given address
                        SUPER.google_maps_api.allMaps[$form_id][key].setCenter(result[0].geometry.location);
                        // Add marker on address location
                        if( $address_marker=='true' ) {
                            new google.maps.Marker({
                                map: SUPER.google_maps_api.allMaps[$form_id][key],
                                position: result[0].geometry.location
                            });
                        }
                    } else {
                        // Display error message
                        result = {
                            msg: 'Geocode was not successful for the following reason: ' + status,
                            loading: true,
                            error: true
                        }
                        SUPER.form_submission_finished(args, result);
                    }
                });
                return true;
            }
        });
    };
    SUPER.google_maps_api.initAutocomplete = function(args){
        var url, field, items = args.form.querySelectorAll('.super-address-autopopulate:not(.super-autopopulate-init)');
        Object.keys(items).forEach(function(key) {
            field = items[key];
            field.classList.add('super-autopopulate-init');
            args.el = field;
            if(typeof google === 'undefined'){
                url = '//maps.googleapis.com/maps/api/js?';
                if(field.dataset.apiRegion!=='') url += 'region='+field.dataset.apiRegion+'&';
                if(field.dataset.apiLanguage!=='') url += 'language='+field.dataset.apiLanguage+'&';
                url += 'key='+field.dataset.apiKey+'&libraries=drawing,geometry,places,visualization&callback=SUPER.google_maps_init'
                $.getScript( url, function() {
                    SUPER.google_maps_api.initAutocompleteCallback(args);
                });
            }else{
                if(google.maps && google.maps.places){
                    SUPER.google_maps_api.initAutocompleteCallback(args);
                }else{
                    console.log('Super Forms address autocomplete requires google Places API to be enabled!');
                }
            }
        });
    };
    SUPER.google_maps_api.initAutocompleteCallback = async function (args) {
        var el = args.el;
        var form = args.form;
        var { Place, AutocompleteSessionToken, AutocompleteSuggestion } = await google.maps.importLibrary("places");
        var mapping = {
            street_number: 'street_number',
            route: 'street_name',
            locality: 'city',
            postal_town: 'city',
            sublocality_level_1: 'city',
            administrative_area_level_2: 'municipality',
            administrative_area_level_1: 'state',
            country: 'country',
            postal_code: 'postal_code',
            lat: 'lat',
            lng: 'lng'
        };
    
        // Dynamically detect required fields
        var fieldsSet = new Set(['formattedAddress', 'location']); // always needed
        var placeKeyToAPIField = {
            name: 'displayName',
            formatted_address: 'formattedAddress',
            formatted_phone_number: 'nationalPhoneNumber',
            international_phone_number: 'internationalPhoneNumber',
            website: 'websiteURI'
        };
    
        for (var [placeKey, apiField] of Object.entries(placeKeyToAPIField)) {
            var attr = $(el).data(`map-${placeKey}`);
            if (attr) {
                var [fieldName] = attr.split('|');
                var input = SUPER.field(form, fieldName);
                if (input) fieldsSet.add(apiField);
            }
        }
    
        for (var [type, mapKey] of Object.entries(mapping)) {
            var attr = $(el).data(`map-${mapKey}`);
            if (attr) {
                var [fieldName] = attr.split('|');
                var input = SUPER.field(form, fieldName);
                if (input) {
                    fieldsSet.add('addressComponents'); // all mapping types use addressComponents
                }
            }
        }
    
        var fields = Array.from(fieldsSet);
    
        var typesData = el.dataset.types;
        var types = typesData ? typesData.split(',').map(t => t.trim()) : undefined;
        var types = typesData ? typesData.split(',').map(t => t.trim()).filter(Boolean) : undefined;
        var countryData = el.dataset.countries;
        var countryRestriction = countryData ? countryData.split(',').map(c => c.trim()).filter(Boolean) : [];
        
    
        el.setAttribute('autocomplete', 'off');
        var dropdown;
        var debounceTimeout = null;
        var token = new AutocompleteSessionToken(); // Reused for a session
    
        el.addEventListener('input', async function () {
            var inputVal = el.value.trim();
            if (inputVal.length < 3) return;
    
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(async () => {
                var form = SUPER.get_frontend_or_backend_form({ el: el });
                var form_id = parseInt(form.id.replace('super-form-', ''), 10);
    
                try {
                    var request = {
                        input: inputVal,
                        sessionToken: token,
                        includedRegionCodes: countryRestriction, // ["de", "fr"]
                        //region: countryRestriction,
                        includedPrimaryTypes: types,
                        language: el.dataset.apiLanguage || 'en'
                    };
    
                    var { suggestions } = await AutocompleteSuggestion.fetchAutocompleteSuggestions(request);
                    var parent = el.parentNode.closest('.super-shortcode');
                    parent.classList.remove('super-string-found');
    
                    if (suggestions && suggestions.length > 0) {
                        if (!dropdown) {
                            dropdown = document.createElement('ul');
                            dropdown.className = 'super-dropdown-list';
                        } else {
                            dropdown.innerHTML = '';
                        }
    
                        parent.classList.add('super-auto-suggest');
                        var searchValue = el.value.toLowerCase();
                        var matchRegex = new RegExp(`(${searchValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'i');
    
                        for (var suggestion of suggestions) {
                            var place = suggestion.placePrediction.toPlace();
    
                            var li = document.createElement('li');
                            li.className = 'super-item super-match';
    
                            var fullText = suggestion.placePrediction.text.text || '';
                            var match = fullText.match(matchRegex);
                            var html = match ? fullText.replace(matchRegex, `<span>${match[1]}</span>`) : fullText;
    
                            li.innerHTML = html;
                            li.setAttribute('data-value', place.id);
                            li.setAttribute('data-search-value', fullText); 
                            li.addEventListener('click', async (event) => {
                                try {
                                    var currentTarget = event.currentTarget;
                                    if (currentTarget.classList.contains('super-active')) {
                                        if (currentTarget.parentNode.querySelector('.super-dropdown-list')) currentTarget.parentNode.querySelector('.super-dropdown-list').remove();
                                        delete el.dataset.lat;
                                        delete el.dataset.lng;
                                        el.removeAttribute('lat');
                                        el.removeAttribute('lng');
                                        el.value = '';
                                        // Loop over any mapped fields and empty them.
                                        var place = new Place({ id: currentTarget.dataset.value });
                                        await place.fetchFields({ fields });
                                        SUPER.google_maps_api.populateFields(event, fieldsSet, place, el, form, mapping, true);
                                        return;
                                    }
                                    var place = new Place({ id: currentTarget.dataset.value });
                                    await place.fetchFields({ fields });
                                    var lat = place.location?.lat();
                                    var lng = place.location?.lng();
                                    if (lat) el.dataset.lat = lat;
                                    if (lng) el.dataset.lng = lng;
                                    var i, items = dropdown.querySelectorAll('.super-item.super-active');
                                    for( i = 0; i < items.length; i++ ) {
                                        items[i].classList.remove('super-active');
                                    }
                                    currentTarget.classList.add('super-active');
                                    parent.classList.remove('super-focus');
                                    parent.classList.remove('super-open');
                                    parent.classList.remove('super-string-found');
                                    var wrapper = parent.querySelector('.super-field-wrapper');
                                    wrapper.classList.add('super-overlap');
                                    parent.classList.add('super-filled');

                                    var displayAddress = place.formattedAddress;
                                    if (el.dataset.normalize === 'true') {
                                        displayAddress = displayAddress.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                                    }
                                    SUPER.calculate_distance_speed = 0;
                                    el.value = displayAddress; //place.formattedAddress;

                                    var validation = el.dataset.validation;
                                    var conditionalValidation = el.dataset.conditionalValidation;
                                    SUPER.handle_validations({event: event, el: el, form: form, validation: validation, conditionalValidation: conditionalValidation});
                                    SUPER.after_field_change_blur_hook({ el: el });
                                    SUPER.google_maps_api.populateFields(event, fieldsSet, place, el, form, mapping, false);
                                    SUPER.calculate_distance_speed = 1000;
                                } catch (error) {
                                    var overlayArgs = SUPER.createLoadingOverlay({
                                        showOverlay: 'true',
                                        form_id: form_id,
                                        custom_msg: 'Error fetching place details: ' + error
                                    });
                                    overlayArgs.loadingOverlay.classList.add('super-error');
                                    console.error('Error fetching place details:', error);
                                }
    
                                token = new AutocompleteSessionToken(); // new session after click
                            });
    
                            dropdown.appendChild(li);
                        }
    
                        parent.classList.add('super-string-found');
                        el.parentNode.appendChild(dropdown);
                    }
                } catch (error) {
                    var overlayArgs = SUPER.createLoadingOverlay({
                        showOverlay: 'true',
                        form_id: form_id,
                        custom_msg: 'Error fetching autocomplete suggestions: ' + error
                    });
                    overlayArgs.loadingOverlay.classList.add('super-error');
                    console.error('Error fetching autocomplete suggestions:', error);
                }
            }, 400);
        });
    };
    SUPER.google_maps_api.populateFields = function(event, fieldsSet, place, el, form, mapping, empty) {
        var lat = place.location?.lat();
        var lng = place.location?.lng();
        // Clone addressComponents if allowed
        var addressComponents = Array.isArray(place.addressComponents) ? [...place.addressComponents] : [];
        if (lat && lng && fieldsSet.has('addressComponents')) {
            addressComponents.push({ longText: lat, shortText: lat, types: ["lat"] });
            addressComponents.push({ longText: lng, shortText: lng, types: ["lng"] });
        }
        var street_data = { number: { long: '', short: '' }, name: { long: '', short: '' } };
        addressComponents.forEach(component => {
            var long = component.longText || component.long_name || '';
            var short = component.shortText || component.short_name || '';
            var types = component.types || [];
            if (!types.length) return;
    
            if (types.includes('street_number')) street_data.number = { long, short };
            if (types.includes('route')) street_data.name = { long, short };
    
            var mapKey = mapping[types[0]];
            if (!mapKey) return;
    
            var attr = $(el).data(`map-${mapKey}`);
            if (!attr) return;
    
            var [fieldName, mode = 'long'] = attr.split('|');
            var inputField = SUPER.field(form, fieldName);
            if (!inputField) return;
            var value = mode === 'short' ? short : long;
            SUPER.set_correct_placeholder_value_for_mapped_autocomplete_fields(event, form, empty, inputField, value);
        });
    
        SUPER.google_maps_api.updateFieldFromPlace(event, form, empty, place, el, 'displayName', 'map-name');
        SUPER.google_maps_api.updateFieldFromPlace(event, form, empty, place, el, 'formattedAddress', 'map-formatted_address');
        SUPER.google_maps_api.updateFieldFromPlace(event, form, empty, place, el, 'nationalPhoneNumber', 'map-formatted_phone_number');
        SUPER.google_maps_api.updateFieldFromPlace(event, form, empty, place, el, 'internationalPhoneNumber', 'map-international_phone_number');
        SUPER.google_maps_api.updateFieldFromPlace(event, form, empty, place, el, 'websiteURI', 'map-website');
    
        var combineFields = (event, form, empty, attrName, parts) => {
            var attr = $(el).data(attrName);
            if (!attr) return;
            var [fieldName, modeRaw] = attr.split('|');
            var mode = modeRaw?.trim() || 'long'; // Default to 'long' if empty or undefined
            var value = parts.map(p => p[mode]).filter(Boolean).join(' ');
            var inputField = SUPER.field(form, fieldName);
            SUPER.set_correct_placeholder_value_for_mapped_autocomplete_fields(event, form, empty, inputField, value);
        };
        combineFields(event, form, empty, 'map-street_name_number', [street_data.name, street_data.number]);
        combineFields(event, form, empty, 'map-street_number_name', [street_data.number, street_data.name]);
    };
    SUPER.google_maps_api.updateFieldFromPlace = function(event, form, empty, place, el, placeKey, dataAttr) {
        var attr = $(el).data(dataAttr);
        if (!attr) return;
        var [fieldName, mode = 'long'] = attr.split('|');
        var value = place[placeKey];
        var inputField = SUPER.field(form, fieldName);
        SUPER.set_correct_placeholder_value_for_mapped_autocomplete_fields(event, form, empty, inputField, value);
    };
    SUPER.set_correct_placeholder_value_for_mapped_autocomplete_fields = function(event, form, empty, inputField, value){
        if(!value) return;
        if(inputField){
            if(empty) value = '';
            var field = inputField.closest('.super-shortcode');
            field.classList.toggle('super-filled', !!value);
            var adaptivePlaceholder = field.querySelector('.super-adaptive-placeholder');
            if(adaptivePlaceholder){
                if(field.classList.contains('super-filled')){
                    adaptivePlaceholder.querySelector('span').innerHTML = adaptivePlaceholder.dataset.placeholderfilled;
                }else{
                    adaptivePlaceholder.querySelector('span').innerHTML = adaptivePlaceholder.dataset.placeholder;
                }
            }
            inputField.value = value;
            // Internation phonenumber
            if(field.classList.contains('super-int-phone-field')){
                var intPhone = window.superTelInputGlobals.getInstance(inputField);
                intPhone.setNumber(value);
            }
            var validation = inputField.dataset.validation;
            var conditionalValidation = inputField.dataset.conditionalValidation;
            SUPER.handle_validations({event: event, el: inputField, form: form, validation: validation, conditionalValidation: conditionalValidation});
            SUPER.after_field_change_blur_hook({ el: inputField });
        }
    };

    // old SUPER.google_maps_api.initAutocompleteCallback = function(args){
    // old     var i, x, s, obj = {}, inputField, autocomplete = [];
    // old     //autocomplete[args.el.name] = new google.maps.places.Autocomplete(args.el);
    // old     autocomplete[args.el.name] = new google.maps.places.PlaceAutocompleteElement();
    // old     var mapping = {
    // old         street_number: 'street_number',
    // old         route: 'street_name',
    // old         locality: 'city', // see: https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete-addressform
    // old         postal_town: 'city', // see: https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete-addressform
    // old         sublocality_level_1: 'city', // see: https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete-addressform
    // old         administrative_area_level_2: 'municipality',
    // old         administrative_area_level_1: 'state',
    // old         country: 'country',
    // old         postal_code: 'postal_code',
    // old         lat: 'lat',
    // old         lng: 'lng'
    // old     };
    // old     
    // old     // Check if any of the address components is mapped
    // old     var $returnAddressComponent = false;
    // old     for (var key in mapping) {
    // old         if($(args.el).data('map-'+mapping[key])){
    // old             $returnAddressComponent = true;
    // old         }
    // old     }
    // old     
    // old     var $returnName = false;
    // old     if($(args.el).data('map-name')) $returnName = true;

    // old     mapping.formatted_phone_number = 'formatted_phone_number';
    // old     var $returnFormattedPhoneNumber = false;
    // old     if($(args.el).data('map-formatted_phone_number')) $returnFormattedPhoneNumber = true;

    // old     mapping.international_phone_number = 'international_phone_number';
    // old     var $returnInternationalPhoneNumber = false;
    // old     if($(args.el).data('map-international_phone_number')) $returnInternationalPhoneNumber = true;

    // old     mapping.website = 'website';
    // old     var $returnWebsite = false;
    // old     if($(args.el).data('map-website')) $returnWebsite = true;

    // old     var fields = ['formatted_address', 'geometry.location']; // This data is always used
    // old     if($returnAddressComponent) fields.push('address_components');
    // old     if($returnName) fields.push('name');
    // old     if($returnFormattedPhoneNumber) fields.push('formatted_phone_number');
    // old     if($returnInternationalPhoneNumber) fields.push('international_phone_number');
    // old     if($returnWebsite) fields.push('website');

    // old     var thisAutocomplete = autocomplete[args.el.name];
    // old     //thisAutocomplete.setFields(fields);
    // old     thisAutocomplete.setOptions({
    // old         fields: fields //['id', 'formattedAddress', 'location', 'addressComponents', 'displayName']
    // old     });
    // old     thisAutocomplete.el = args.el;
    // old     thisAutocomplete.form = args.form;

    // old     s = $(args.el).data('countries'); // Could be empty or a comma separated string e.g: fr,nl,de
    // old     if(s){
    // old         x = s.split(',');
    // old         obj.countries = [];
    // old         for(i=0; i<x.length; i++){
    // old             obj.countries.push(x[i].trim());
    // old         }
    // old         thisAutocomplete.setComponentRestrictions({
    // old             country: obj.countries, // e.g: ["us", "pr", "vi", "gu", "mp"],
    // old         });
    // old     }
    // old     s = $(args.el).data('types'); // Could be empty or a comma separated string e.g: fr,nl,de
    // old     if(s){
    // old         x = s.split(',');
    // old         obj.types = [];
    // old         for(i=0; i<x.length; i++){
    // old             obj.types.push(x[i].trim());
    // old         }
    // old         thisAutocomplete.setTypes(obj.types);
    // old     }
    // old     thisAutocomplete.addListener( 'place_changed', function () {
    // old         // Set text field to the formatted address
    // old         var place = thisAutocomplete.getPlace();
    // old         if(args.el.dataset.normalize && args.el.dataset.normalize==='true'){
    // old             // If we need to normalize the results returned by google API:
    // old             var str = place.formatted_address;
    // old             if(typeof $val === 'string') str = thisAutocomplete.el.value = str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    // old             thisAutocomplete.el.value = str;
    // old         }else{
    // old             thisAutocomplete.el.value = place.formatted_address;
    // old         }
    // old         SUPER.handle_validations({el: thisAutocomplete.el, form: thisAutocomplete.form});
    // old         SUPER.calculate_distance({el: thisAutocomplete.el});

    // old         var street_data = {
    // old             number: {
    // old                 long: '',
    // old                 short: ''
    // old             },
    // old             name: {
    // old                 long: '',
    // old                 short: ''
    // old             }
    // old         };

    // old         // @since 3.2.0 - add address latitude and longitude for ACF google map compatibility
    // old         var lat = place.geometry.location.lat();
    // old         var lng = place.geometry.location.lng();
    // old         thisAutocomplete.el.dataset.lat = lat;
    // old         thisAutocomplete.el.dataset.lng = lng;

    // old         // @since 3.5.0 - trigger / update google maps in case {tags} have been used
    // old         args.el = thisAutocomplete.el;
    // old         args.form = thisAutocomplete.form;
    // old         SUPER.google_maps_init(args);

    // old         $(thisAutocomplete.el).trigger('keyup');
    // old         var $attribute;
    // old         var $val;
    // old         var $address;
    // old         var item, types, long, short;
    // old         
    // old         if($returnAddressComponent){
    // old             place.address_components.push({
    // old                 long_name: lat,
    // old                 short_name: lat,
    // old                 types: ["lat"]
    // old             });
    // old             place.address_components.push({
    // old                 long_name: lng,
    // old                 short_name: lng,
    // old                 types: ["lng"]
    // old             });
    // old             for (var i = 0; i < place.address_components.length; i++) {
    // old                 item = place.address_components[i];
    // old                 long = item.long_name;
    // old                 short = item.short_name;
    // old                 if(args.el.dataset.normalize && args.el.dataset.normalize==='true'){
    // old                     // If we need to normalize the results returned by google API:
    // old                     if(typeof long === 'string') long = long.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    // old                     if(typeof short === 'string') short = short.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    // old                 }
    // old                 types = item.types;
    // old                 // Street number
    // old                 if(types.indexOf('street_number')!==-1){
    // old                     street_data.number.long = long;
    // old                     street_data.number.short = short;
    // old                 }
    // old                 // Street name
    // old                 if(types.indexOf('route')!==-1){
    // old                     street_data.name.long = long;
    // old                     street_data.name.short = short;
    // old                 }
    // old                 $attribute = $(thisAutocomplete.el).data('map-'+mapping[types[0]]);
    // old                 if(typeof $attribute !=='undefined'){
    // old                     $attribute = $attribute.split('|');
    // old                     inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
    // old                     if(inputField){
    // old                         if($attribute[1]==='') $attribute[1] = 'long';
    // old                         $val = item[$attribute[1]+'_name'];
    // old                         if(args.el.dataset.normalize && args.el.dataset.normalize==='true'){
    // old                             if(typeof $val === 'string') $val = $val.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    // old                         }
    // old                         inputField.value = $val;
    // old                         SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
    // old                     }
    // old                 }
    // old             }
    // old         }

    // old         // Name of the place
    // old         $attribute = $(thisAutocomplete.el).data('map-name');
    // old         if(typeof $attribute !=='undefined'){
    // old             $attribute = $attribute.split('|');
    // old             inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
    // old             if(inputField){
    // old                 if($attribute[1]==='') $attribute[1] = 'long';
    // old                 $val = place.name;
    // old                 if(args.el.dataset.normalize && args.el.dataset.normalize==='true'){
    // old                     if(typeof $val === 'string') $val = $val.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    // old                 }
    // old                 inputField.value = $val;
    // old                 SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
    // old             }
    // old         }

    // old         // Formatted address of the place
    // old         $attribute = $(thisAutocomplete.el).data('map-formatted_address');
    // old         if(typeof $attribute !=='undefined'){
    // old             $attribute = $attribute.split('|');
    // old             inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
    // old             if(inputField){
    // old                 if($attribute[1]==='') $attribute[1] = 'long';
    // old                 $val = place.formatted_address;
    // old                 if(args.el.dataset.normalize && args.el.dataset.normalize==='true'){
    // old                     if(typeof $val === 'string') $val = $val.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    // old                 }
    // old                 inputField.value = $val;
    // old                 SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
    // old             }
    // old         }

    // old         // Formatted phone number
    // old         $attribute = $(thisAutocomplete.el).data('map-formatted_phone_number');
    // old         if(typeof $attribute !=='undefined'){
    // old             $attribute = $attribute.split('|');
    // old             inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
    // old             if(inputField){
    // old                 if($attribute[1]==='') $attribute[1] = 'long';
    // old                 $val = place.formatted_phone_number;
    // old                 if(args.el.dataset.normalize && args.el.dataset.normalize==='true'){
    // old                     if(typeof $val === 'string') $val = $val.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    // old                 }
    // old                 inputField.value = $val;
    // old                 SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
    // old             }
    // old         }

    // old         // International phone number
    // old         $attribute = $(thisAutocomplete.el).data('map-international_phone_number');
    // old         if(typeof $attribute !=='undefined'){
    // old             $attribute = $attribute.split('|');
    // old             inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
    // old             if(inputField){
    // old                 if($attribute[1]==='') $attribute[1] = 'long';
    // old                 $val = place.international_phone_number;
    // old                 if(args.el.dataset.normalize && args.el.dataset.normalize==='true'){
    // old                     if(typeof $val === 'string') $val = $val.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    // old                 }
    // old                 inputField.value = $val;
    // old                 SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
    // old             }
    // old         }

    // old         // Busniness website
    // old         $attribute = $(thisAutocomplete.el).data('map-website');
    // old         if(typeof $attribute !=='undefined'){
    // old             $attribute = $attribute.split('|');
    // old             inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
    // old             if(inputField){
    // old                 if($attribute[1]==='') $attribute[1] = 'long';
    // old                 $val = place.website;
    // old                 inputField.value = $val;
    // old                 SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
    // old             }
    // old         }

    // old         // @since 3.5.0 - combine street name and number
    // old         $attribute = $(thisAutocomplete.el).data('map-street_name_number');
    // old         if( typeof $attribute !=='undefined' ) {
    // old             $attribute = $attribute.split('|');
    // old             inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
    // old             if(inputField){
    // old                 $address = '';
    // old                 if( street_data.name[$attribute[1]]!=='' ) $address += street_data.name[$attribute[1]];
    // old                 if( $address!=='' ) {
    // old                     $address += ' '+street_data.number[$attribute[1]];
    // old                 }else{
    // old                     $address += street_data.number[$attribute[1]];
    // old                 }
    // old                 inputField.value = $address;
    // old                 SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
    // old             }
    // old         }

    // old         // @since 3.5.1 - combine street number and name
    // old         $attribute = $(thisAutocomplete.el).data('map-street_number_name');
    // old         if( typeof $attribute !=='undefined' ) {
    // old             $attribute = $attribute.split('|');
    // old             inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
    // old             if(inputField){
    // old                 $address = '';
    // old                 if( street_data.number[$attribute[1]]!=='' ) $address += street_data.number[$attribute[1]];
    // old                 if( $address!=='' ) {
    // old                     $address += ' '+street_data.name[$attribute[1]];
    // old                 }else{
    // old                     $address += street_data.name[$attribute[1]];
    // old                 }
    // old                 inputField.value = $address;
    // old                 SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
    // old             }
    // old         }
    // old     });
    // old };

    // Checkbox handler
    SUPER.checkboxes = function(){
        $('.super-checkbox').each(function(){
            var $value = '';
            var $counter = 0;
            var $checked = $(this).find('input[type="checkbox"]:checked');
            $checked.each(function () {
                if ($counter === 0) $value = $(this).val();
                if ($counter !== 0) $value = $value + ',' + $(this).val();
                $counter++;
            });
            $(this).find('input[type="hidden"]').val($value);
        });
        $('.super-radio, .super-shipping').each(function(){
            var $name = $(this).find('.super-shortcode-field').attr('name');
            $(this).find('input[type="radio"]').attr('name','group_'+$name);
        });
        $('.super-shipping').each(function(){
            if(!$(this).hasClass('html-finished')){
                var $currency = $(this).find('.super-shortcode-field').attr('data-currency');
                $(this).find('input[type="radio"]').each(function(){
                    var $html = $(this).parent().html();
                    var $value = $(this).val();
                    $(this).parent().html($html+'<span class="super-shipping-price"> &#8212; '+$currency+''+parseFloat($value).toFixed(2)+'</span>');
                });
                $(this).addClass('html-finished');
            }        
        });
    };

    // @since 3.2.0 - Reverse columns
    SUPER.reverse_columns = function($form){
        $($form).find('.super-grid').each(function(){
            var $grid = $(this);
            var $columns = $grid.children('div.super-column:not(.super-not-responsive)');
            $grid.append($columns.get().reverse());
            $grid.children('div.super-column:last-child').removeClass('first-column');
            $grid.children('div.super-column:eq(0)').addClass('first-column');
        });
    };

    // Handle columns
    SUPER.handle_columns = function(){
        var $this,
            $exclusion,
            $fields,
            $width = 0;

        $('div.super-field').each(function(){
            if($(this).hasClass('super-grouped')){
                if((!$(this).prev().hasClass('super-grouped')) || ($(this).prev().hasClass('super-grouped-end'))){
                    $(this).addClass('super-grouped-start'); 
                }
            }
        });
        $('.super-field > .super-label').each(function () {
            if($(this).parent().index()); 
            if (!$(this).parent().hasClass('super-grouped')) {
                if ($(this).outerWidth(true) > $width) $width = $(this).outerWidth(true);
            }
        });
        //Checkbox fields
        SUPER.checkboxes();
        //Barcodes
        SUPER.generateBarcode();
        //Rating
        SUPER.rating();

        var forms = document.querySelectorAll('.super-form:not(.super-rendered)');
        Object.keys(forms).forEach(function(key) {
            $this = forms[key];
            if($this.id==='') return;
            var formId = SUPER.getFormIdByAttributeID($this);
            if(typeof SUPER.preFlightMappings==='undefined') SUPER.preFlightMappings = {};
            if(typeof SUPER.preFlightMappings[formId]==='undefined') SUPER.preFlightMappings[formId] = {fieldNames: [], tags: {}}

            // @since 3.2.0 
            // Add tab indexes to all fields
            // Check if RTL support is enabled, if so we must reverse columns order before we add TAB indexes to fields
            if( $this.classList.contains('super-rtl') ) {
                // Reverse column order before adding TAB indexes
                SUPER.reverse_columns($this);
            }
            // - After we reverted the column order, loop through all the fields and add the correct TAB indexes
            $exclusion = super_common_i18n.tab_index_exclusion;

            $fields = $($($this).find('.super-field:not('+$exclusion+')').get());
            $fields.each(function(key, value){
                $(value).attr('data-super-tab-index', key);
            });
            // Now we have added the TAB indexes, make sure to reverse the order back to normal in case of RTL support
            if( $this.classList.contains('super-rtl') ) {
                SUPER.reverse_columns($this);
            }
            // Because of the FireFox bug with adaptive placeholders we must loop over all fields to check if they are not empty.
            // if the field is not empty we will add the super-filled class to it's parent node
            var i=0, el, value, nodes = $this.querySelectorAll('.super-text .super-shortcode-field, .super-textarea .super-shortcode-field, .super-currency .super-shortcode-field, .super-dropdown .super-shortcode-field');
            for(i=0; i<nodes.length; i++){
                if(nodes[i].value!==''){
                    el = nodes[i].closest('.super-shortcode');
                    if(el.querySelector('.super-adaptive-placeholder')){
                        el.querySelector('.super-adaptive-placeholder').children[0].innerHTML = el.querySelector('.super-adaptive-placeholder').dataset.placeholderfilled;
                        el.classList.add('super-filled');
                    }
                }
            }
            // .. but for toggle field
            nodes = $this.querySelectorAll('.super-toggle .super-shortcode-field');
            for(i=0; i<nodes.length; i++){
                if(nodes[i].value!==''){
                    el = nodes[i].closest('.super-shortcode');
                    if(nodes[i].value===el.querySelector('.super-toggle-on').dataset.value){
                        el.querySelector('.super-toggle-switch').classList.add('super-active');
                    }
                }
            }
            // .. but for radio buttons
            nodes = $this.querySelectorAll('.super-radio .super-shortcode-field');
            for(i=0; i<nodes.length; i++){
                if(nodes[i].value!==''){
                    el = nodes[i].closest('.super-shortcode');
                    var items = el.querySelectorAll('.super-item');
                    for(var ii = 0; ii < items.length; ii++){
                        var input = items[ii].querySelector('input');
                        if(input.value == nodes[i].value){
                            input.checked = true;
                            items[ii].classList.add('super-active');
                            break; // Radio button can only have 1 active item
                        }
                    }
                }
            }
            // .. but for checkboxes
            nodes = $this.querySelectorAll('.super-checkbox .super-shortcode-field');
            for(i=0; i<nodes.length; i++){
                if(nodes[i].value!==''){
                    el = nodes[i].closest('.super-shortcode');
                    items = el.querySelectorAll('.super-item');
                    for(ii = 0; ii < items.length; ii++){
                        input = items[ii].querySelector('input');
                        items[ii].classList.remove('super-active');
                        input.checked = false;
                        var options = nodes[i].value.split(',');
                        if(options.indexOf(input.value)!==-1){
                            input.checked = true;
                            items[ii].classList.add('super-active');
                        }
                    }
                }
            }

            // Collect arguments to be parsed to the after initialization hook
            var args = {
                el: undefined,
                form: $this,
                callback: function(args){
                    args.form.classList.add('super-rendered');
                }
            };
            SUPER.after_initializing_forms_hook(args);
            
            if(SUPER.form_js && SUPER.form_js[formId] && SUPER.form_js[formId]['_entry_data']){
                var data = SUPER.form_js[formId]['_entry_data'];
                if(data) {
                    SUPER.populate_form_with_entry_data(data, args.form, args.clear);
                }
            }

            // Trigger fake windows resize
            var resizeEvent = window.document.createEvent('UIEvents');
            resizeEvent.initUIEvent('resize', true, false, window, 0);
            window.dispatchEvent(resizeEvent);
        });

    };

    // Remove responsive class from the form
    SUPER.remove_super_form_classes = function($this, $classes){
        $.each($classes, function( k, v ) {
            $this.removeClass(v);
        });
    };

    // Escape any user input to prepare for injection into HTML (prevents XSS)
    SUPER.html_encode = function(value){
        return String(value).replace(/[^\w. ]/gi, function(c){
            return '&#'+c.charCodeAt(0)+';';
        });
    };

    // Replace HTML element {tags} with field values
    // @since 1.2.7
    SUPER.init_replace_html_tags = function(args){
        var originalFormReference,
            decodeHtml,
            htmlFields,
            target,
            html,
            originalHtml,
            original,
            field_name,
            regex,
            values,
            new_value,
            issetContent,
            notIssetContent,
            content,
            newHtml,
            formId = SUPER.getFormIdByAttributeID(args.form);

        // Only when not on canvas in builder mode
        if(args.form.classList.contains('super-preview-elements')){
            return false;
        }

        // Continue otherwise
        if(typeof args.foundElements !== 'undefined') {
            if(args.foundElements.length>0){
                htmlFields = args.foundElements;
            }else{
                htmlFields = args.form.querySelectorAll('[data-fields], .super-google-map, .super-html-content');
            }
        }else{
            if(typeof args.el === 'undefined') {
                htmlFields = args.form.querySelectorAll('[data-fields], .super-google-map, .super-html-content');
            }else{
                var n = SUPER.get_original_field_name(args.el);
                htmlFields = args.form.querySelectorAll('[data-fields*="{'+n+'}"], .super-google-map[data-fields*="{'+n+'}"], .super-html-content[data-fields*="{'+n+'}"]');
            }
        }
        Object.keys(htmlFields).forEach(function(key) {
            target = htmlFields[key];
            // @since 4.9.0 - accordion title description {tags} compatibility
            if(target.dataset.fields && target.dataset.original){
                html = target.dataset.original;
            }else{
                if(!target.parentNode.querySelector('textarea') || 
                    target.className==='super-conditional-logic' || 
                    target.className==='super-validate-conditions' || 
                    target.className==='super-variable-conditions') return true;
                html = target.parentNode.querySelector('textarea').value;
            }
            // If empty skip
            if(html===''){
                return true;
            }
            
            // When generating PDF, we must have a reference to the original form
            originalFormReference = args.form;
            if(args.form.classList.contains('super-generating-pdf')){
                originalFormReference = document.querySelector('#super-form-'+formId+'-placeholder');
            }

            // @since 5.0.120 - foreach statement compatibility
            regex = /<%(.*?)%>|{(.*?)}/;
            var skipUpdate = true;
            if ((m = regex.exec(html)) !== null) {
                skipUpdate = false;
            }

            // Check if endforeach; was found otherwise skip it
            html = SUPER.filter_foreach_statements(target, 0, 0, html, undefined, formId, originalFormReference);
            html = SUPER.replaceAll(html, '<%', '{');
            html = SUPER.replaceAll(html, '%>', '}');

            // Check if html contains {tags}, if not we don't have to do anything.
            // This also solves bugs with for instance third party plugins
            // That use shortcodes to initialize elements, which initialization would be lost
            // upon updating the HTML content based on {tags}.
            // This can be solved by NOT using either of the {} curly braces inside the HTML content
            regex = /({|foreach\()([-_a-zA-Z0-9\[\]]{1,})(\[.*?])?(?:;([-_a-zA-Z0-9]{1,}))?(}|\):)/g;
            // If it has {tags} then continue
            var m;
            var replaceTagsWithValue = {};
            while ((m = regex.exec(html)) !== null) {
                decodeHtml = true;
                skipUpdate = false;
                // This is necessary to avoid infinite loops with zero-width matches
                if (m.index === regex.lastIndex) {
                    regex.lastIndex++;
                }
                var n = (m[2] ? m[2] : ''); // name
                var d = (m[3] ? m[3] : ''); // depth
                var s = (m[4] ? m[4] : ''); // suffix
                if(s==='allFileNames' || s==='allFileUrls' || s==='allFileLinks'){
                    decodeHtml = false;
                }else{
                    var fieldType = SUPER.get_field_type(originalFormReference, n);
                    if(s==='' && (fieldType.type==='textarea' || fieldType.type==='file')){
                        decodeHtml = false;
                    }
                }
                // Get field type
                if(s!=='') s = ';'+s;
                values = n+d+s;
                args.value = '{'+values+'}'; //values[1];
                args.target = target;
                new_value = SUPER.update_variable_fields.replace_tags(args);
                delete args.target;
                if(decodeHtml){
                    new_value = SUPER.html_encode(new_value);
                }
                replaceTagsWithValue[values] = new_value;
            }
            newHtml = html;
            // @IMPORTANT - must check for !isset() matches before checking for isset() due to the replacement being done
            // Regex to check if field is not set (conditionally hidden)
            regex = /(?:!isset|if\(!isset)\s?\(\s?[\'|"|\s|]?(.*?)[\'|"|\s|]?(?:\)|\)\))\s?:([\s\S]*?)(?:end(?:if)\s?;|(?:(?:elseif|else)\s?:([\s\S]*?))end(?:if)\s?;)/gm;
            // If it has {tags} then continue
            while ((m = regex.exec(html)) !== null) {
                original = m[0];
                field_name = m[1];
                issetContent = (m[2] ? m[2] : '');
                notIssetContent = (m[3] ? m[3] : '');
                content = '';
                if(SUPER.field_isset(args.form, field_name)===0){
                    content = issetContent;
                }else{
                    content = notIssetContent;
                }
                originalHtml = html;
                newHtml = SUPER.replaceAll(newHtml, original, content);
                if(originalHtml!==newHtml) skipUpdate = false;
            }
            html = newHtml;

            regex = /(?:isset|if\(isset)\s?\(\s?[\'|"|\s|]?(.*?)[\'|"|\s|]?(?:\)|\)\))\s?:([\s\S]*?)(?:end(?:if)\s?;|(?:(?:elseif|else)\s?:([\s\S]*?))end(?:if)\s?;)/gm;
            // If it has {tags} then continue
            while ((m = regex.exec(html)) !== null) {
                original = m[0];
                field_name = m[1];
                issetContent = (m[2] ? m[2] : '');
                notIssetContent = (m[3] ? m[3] : '');
                content = '';
                if(SUPER.field_isset(args.form, field_name)===1){
                    content = issetContent;
                }else{
                    content = notIssetContent;
                }
                originalHtml = html;
                newHtml = SUPER.replaceAll(newHtml, original, content);
                if(originalHtml!==newHtml) skipUpdate = false;
            }
            html = newHtml;
            var key;
            for(key in replaceTagsWithValue) {
                html = SUPER.replaceAll(html, '{'+key+'}', replaceTagsWithValue[key]);
            }
            if(skipUpdate) return true;
            // @since 4.6.0 - if statement compatibility
            html = SUPER.filter_if_statements(html);
            
            if(target.value || target.dataset.value){
                if(target.value) target.value = html;
                if(target.dataset.value) target.dataset.value = html;
            }else{
                // Not if google map
                if(target.classList.contains('super-google-map')){
                    var textArea = target.querySelector(':scope > textarea.super-hidden');
                    if(textArea) textArea.value = html;
                }else{
                    if(target.tagName==='A'){
                        target.href = html;
                    }else{
                        if(target.dataset.js==='true'){
                            var i, script = document.createElement('script');
                            script.textContent = html;
                            // Select all script elements inside the given target
                            var scripts = target.querySelectorAll('script');
                            // Loop through each script element and remove it
                            for(i=0; i<scripts.length; i++){
                                scripts[i].remove();
                            }
                            target.appendChild(script);
                        }else{
                            target.innerHTML = html;
                        }
                    }
                }
            }
            // If field label or description we must skip because we don't want to override the field value
            if(target.classList.contains('super-label') || target.classList.contains('super-description') ||
               target.classList.contains('super-accordion-title') || target.classList.contains('super-accordion-desc')){
                return true;
            }
            var parent = target.closest('.super-shortcode');
            if(parent){
                var field = parent.querySelector('.super-shortcode-field');
                if(field) {
                    field.value = html;
                    if(typeof args.skipHtmlUpdate==='undefined' || args.skipHtmlUpdate===false){
                        SUPER.after_field_change_blur_hook({el: field, form: args.form, skipHtmlUpdate: true});
                    }
                }
            }
        });
    };

    // Replace datepickers default value {tags} with field values
    SUPER.update_datepickers = function(args){
        var i, nodes, parse, year, month, day, hour, minute, seconds;
        if(typeof args.el === 'undefined') {
            nodes = args.form.querySelectorAll('.super-shortcode-field.super-datepicker');
        }else{
            nodes = args.form.querySelectorAll('.super-shortcode-field.super-datepicker[data-absolute-default*="{'+SUPER.get_field_name(args.el)+'}"]');
        }
        // Update default value for datepickers in case they contain {tags}
        for(i=0; i<nodes.length; i++){
            var absoluteDefault = nodes[i].dataset.absoluteDefault,
                $match,
                $array = [],
                $counter = 0,
                $values,
                $new_value;
            if(absoluteDefault==='') continue;
            while (($match = SUPER.tagsRegex.exec(absoluteDefault)) !== null) {
                $array[$counter] = $match[1];
                $counter++;
            }
            if( $array.length===0 ) continue;
            if( $array.length>0 ) {
                for ($counter = 0; $counter < $array.length; $counter++) {
                    $values = $array[$counter];
                    args.value = '{'+$values+'}';
                    args.target = args.form;
                    $new_value = SUPER.update_variable_fields.replace_tags(args);
                    delete args.target;
                    absoluteDefault = absoluteDefault.replace('{'+$values+'}', $new_value);
                }
            }
            nodes[i].classList.remove('super-picker-initialized');
            nodes[i].classList.remove('hasDatepicker');
            nodes[i].id = '';
            SUPER.init_datepicker();
            $(nodes[i]).datepicker('setDate', absoluteDefault);
        }
        // For native datepickers update attributes
        nodes = document.querySelectorAll('.super-field-type-datetime-local .super-shortcode-field');
        for (i = 0; i < nodes.length; ++i) {
            var el = nodes[i];
            if(el.value && el.value!==''){
                //parseFormat = ['mm-dd-yyTH:i'];
                var parse = Date.parse(el.value);
                //parse = Date.parseExact(el.value, parseFormat);
                if( parse!==null ) {
                    // Now we can use methods on the date obj without the timezone conversion
                    //amount = parse.toString($jsformat);
                    year = parse.toString('yyyy');
                    month = parse.toString('MM');
                    day = parse.toString('dd');
                    hour = parse.toString('HH');
                    minute = parse.toString('mm');
                    seconds = parse.toString('ss');
                    el.dataset.mathYear = year;
                    el.dataset.mathMonth = month;
                    el.dataset.mathDay = day;
                    el.dataset.mathHour = hour;
                    el.dataset.mathMinute = minute;
                    el.dataset.mathSeconds = seconds;
                    firstDate = new Date(Date.UTC(year, month-1, day));
                    var dayIndex = firstDate.getDay();
                    //var dayIndex = parse.getDay();
                    el.dataset.mathDayw = dayIndex;
                    el.dataset.mathDayn = super_elements_i18n.dayNames[dayIndex]; // long (default)
                    el.dataset.mathDayns = super_elements_i18n.dayNamesShort[dayIndex]; // short
                    el.dataset.mathDaynss = super_elements_i18n.dayNamesMin[dayIndex]; // super short
                    el.dataset.mathDiff = firstDate.getTime();
                    el.dataset.mathAge = SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'years');
                    el.dataset.mathAgeMonths = SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'months');
                    el.dataset.mathAgeDays = SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'days');
                    // Set minutes to current minutes (UTC) + User local time UTC offset
                    //parse.setMinutes(parse.getMinutes() - parse.getTimezoneOffset());
                    //el.dataset.mathDiff = parse.getTime();
                    $date = Date.parseExact(day+'-'+month+'-'+year, parseFormat);
                    if($date!==null){
                        $date = $date.toString("dd-MM-yyyy");
                        SUPER.init_connected_datepicker(el, $date, parseFormat, oneDay);
                    }
                }
            }else{
                el.dataset.mathYear = '0';
                el.dataset.mathMonth = '0';
                el.dataset.mathDay = '0';
                el.dataset.mathDayw = '0';
                el.dataset.mathDayn = '';
                el.dataset.mathDiff = '0';
                el.dataset.mathAge = '0';
            }
            //SUPER.after_field_change_blur_hook({el: el});
        }
    };

    // Replace form action attribute {tags} with field values
    // @since 4.4.6
    SUPER.init_replace_post_url_tags = function(args){
        var $match,
            $target = args.form.querySelector('form'),
            $actiontags = ($target ? $target.dataset.actiontags : ''),
            $array = [],
            $counter = 0,
            $values,
            $new_value;

        // Only if action is defined
        if($target){
            while (($match = SUPER.tagsRegex.exec($actiontags)) !== null) {
                $array[$counter] = $match[1];
                $counter++;
            }
            if( $array.length>0 ) {
                for ($counter = 0; $counter < $array.length; $counter++) {
                    $values = $array[$counter];
                    args.value = '{'+$values+'}';
                    args.target = $target;
                    $new_value = SUPER.update_variable_fields.replace_tags(args);
                    delete args.target;
                    $actiontags = $actiontags.replace('{'+$values+'}', $new_value);
                }
            }
            $target.action = $actiontags;
        }
    };

    // Init text editors
    SUPER.init_text_editors = function(){
        if( typeof tinyMCE !== 'undefined' ) {
            $('.super-text-editor:not(.super-initialized)').each(function(){
                var $this = $(this),
                    $name = $this.attr('id'),
                    $incl_url = $this.data('incl-url');

                tinyMCE.execCommand('mceRemoveEditor', true, $name);
                var tinyMCEPreInit = {
                    baseURL: $this.data('baseurl'),
                    suffix: '.min',
                    mceInit: {},
                    qtInit: {},
                    ref: {},
                    load_ext: function(url,lang){
                        var sl=tinyMCE.ScriptLoader;
                        sl.markDone(url+'/langs/'+lang+'.js');
                        sl.markDone(url+'/langs/'+lang+'_dlg.js');
                    }
                };
                tinyMCEPreInit.mceInit[$name] = {
                    theme:"modern",
                    skin:"lightgray",
                    language:"en",
                    formats:{
                        alignleft: [{
                            selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", 
                            styles: {
                                textAlign:"left"
                            }
                        },{
                            selector: "img,table,dl.wp-caption", 
                            classes: "alignleft"
                        }],
                        aligncenter: [{
                            selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", 
                            styles: {
                                textAlign:"center"
                            }
                        },{
                            selector: "img,table,dl.wp-caption", 
                            classes: "aligncenter"
                        }],
                        alignright: [{
                            selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", 
                            styles: {
                                textAlign:"right"
                            }
                        },{
                            selector: "img,table,dl.wp-caption", 
                            classes: "alignright"
                        }],strikethrough: {
                            inline: "del"
                        }
                    },
                    relative_urls:false,
                    remove_script_host:false,
                    convert_urls:false,
                    browser_spellcheck:true,
                    fix_list_elements:true,
                    entities:"38,amp,60,lt,62,gt",
                    entity_encoding:"raw",
                    keep_styles:false,
                    cache_suffix:"wp-mce-4310-20160418",
                    preview_styles:"font-family font-size font-weight font-style text-decoration text-transform",
                    end_container_on_empty_block:true,
                    wpeditimage_disable_captions:false,
                    wpeditimage_html5_captions:true,
                    // @since 4.0.0 - delete 'wpembed' from plugin list  because Wordpress 4.8 and latest tinymce dropped wpembed 
                    plugins:"charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview",
                    wp_lang_attr:"en-US",
                    content_css:$incl_url+"/css/dashicons.css,"+$incl_url+"/js/tinymce/skins/wordpress/wp-content.css",
                    selector:"#"+$name,
                    resize:"vertical",
                    menubar:false,
                    wpautop:false,
                    indent:false,
                    toolbar1:"bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv",
                    toolbar2:"formatselect,underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help",
                    toolbar3:"",
                    toolbar4:"",
                    tabfocus_elements:":prev,:next",
                    body_class:$name+" post-type-page post-status-publish locale-en-us"
                };

                tinyMCEPreInit.qtInit[$name] = {
                    id:$name,
                    buttons:"strong,em,link,block,del,ins,img,ul,ol,li,code,more,close"
                };

                tinyMCEPreInit.ref = {
                    // @since 4.0.0 - delete 'wpembed' from plugin list  because Wordpress 4.8 and latest tinymce dropped wpembed 
                    plugins:"charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview",
                    theme:"modern",
                    language:"en"
                };

                if( ($this.data('teeny')=='true') || ($this.data('teeny')===true) ){
                    tinyMCEPreInit.mceInit[$name].toolbar2 = false;
                }
                if( ($this.data('force-br')=='true') || ($this.data('force-br')===true) ){
                    tinyMCEPreInit.mceInit[$name].forced_root_block = false;
                    tinyMCEPreInit.mceInit[$name].force_br_newlines = true;
                    tinyMCEPreInit.mceInit[$name].force_p_newlines = false;
                    tinyMCEPreInit.mceInit[$name].convert_newlines_to_brs = true;
                }

                var init, id, $wrap;
                for ( id in tinyMCEPreInit.mceInit ) {
                    init = tinyMCEPreInit.mceInit[id];
                    $wrap = tinyMCE.$( '#wp-' + id + '-wrap' );
                    if ( ( $wrap.hasClass( 'tmce-active' ) || ! Object.prototype.hasOwnProperty.call(tinyMCEPreInit.qtInit, id) ) && ! init.wp_skip_init ) {
                        tinyMCE.init( init );
                        if ( ! window.wpActiveEditor ) {
                            window.wpActiveEditor = id;
                        }
                    }
                }
                for ( id in tinyMCEPreInit.qtInit ) {
                    quicktags( tinyMCEPreInit.qtInit[id] );

                    if ( ! window.wpActiveEditor ) {
                        window.wpActiveEditor = id;
                    }
                }
            });
        }
    };

    // @since 2.0.0 - set dropdown placeholder function
    SUPER.init_set_dropdown_placeholder = function($form){
        if(typeof $form === 'undefined') $form = $('.super-form');

        $form.find('.super-dropdown-list').each(function(){
            var $this = $(this);
            var $field = $this.parent('.super-field-wrapper').find('.super-shortcode-field');
            var $first_item = $this.find('.super-item:eq(1)');

            // @since 3.1.0 - first check if the field is not empty by GET or POST
            var $value = $field.val();
            if($value===''){
                // @since   1.1.8    - check if we can find a default value
                $value = $field.data('default-value');
            }

            if( (typeof $value !== 'undefined') &&  ($value!=='') ) {
                $field.val($value);
                var $new_placeholder = '';
                $value = $value.toString().split(',');
                $.each($value, function( index, value ) {
                    value = $.trim(value).split(';')[0];
                    // Lets find the option name based on the matched value
                    $this.find('.super-item:not(.super-placeholder)').each(function(){
                        var $item_first_value = $(this).attr('data-value').split(';')[0];
                        if($item_first_value==value){
                            $(this).addClass('super-active');
                            if($new_placeholder===''){
                                $new_placeholder += $(this).html();
                            }else{
                                $new_placeholder += ', '+$(this).html();
                            }
                        }
                    });
                });
                // Only if placeholder is not empty
                if($new_placeholder!=='') $this.children('.super-placeholder').html($new_placeholder);
            }else{
                $field.val('');
                var $placeholder = $field.attr('placeholder');
                if( (typeof $placeholder !== 'undefined') &&  ($placeholder!=='') ) {
                    $this.children('.super-placeholder').attr('data-value', '').html($placeholder);
                }else{
                    if($this.children('.super-placeholder').html()===''){
                        $first_item.addClass('super-active');
                        $this.children('.super-placeholder').attr('data-value', $first_item.attr('data-value')).html($first_item.html());
                    }
                }
            }
        });
    };

    // @since 3.1.0 - print form data
    SUPER.init_print_form = function(args){
        var items,
            $data,
            $parent,
            $css,
            nodes,
            el,
            $items,
            i, ii,
            $file_id,
            win = window.open('','printwindow'),
            $html = '',
            $print_file = args.submitButton.querySelector('input[name="print_file"]');
        if( $print_file && $print_file.value!=='' && $print_file.value!='0' ) {
            // @since 3.9.0 - print custom HTML
            $file_id = $print_file.value;
            SUPER.prepare_form_data($(args.form), function(formData){
                formData = SUPER.after_form_data_collected_hook(formData.data, args.form0);
                $.ajax({
                    url: super_common_i18n.ajaxurl,
                    type: 'post',
                    data: {
                        action: 'super_print_custom_html',
                        data: formData,
                        file_id: $file_id
                    },
                    success: function (result) {
                        win.document.write(result);
                        win.document.close();
                        win.focus();
                        // @since 2.3 - chrome browser bug
                        setTimeout(function() {
                            win.print();
                            win.close();
                        }, 250);
                        return false;
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        // eslint-disable-next-line no-console
                        console.log(xhr, ajaxOptions, thrownError);
                        alert(super_common_i18n.errors.failed_to_process_data);
                        return false;
                    }
                });
            });
        }else{
            $css = "<style type=\"text/css\">";
            $css += "body {font-family:Arial,sans-serif;color:#444;-webkit-print-color-adjust:exact;}";
            $css += "table {font-size:12px;}";
            $css += "table th{text-align:right;font-weight:bold;font-size:12px;padding-right:5px;}";
            $css += "table td{font-size:12px;}";
            $css += "</style>";
            $html = $css;
            $html += '<table>';
            nodes = args.form.querySelectorAll('.super-shortcode-field');
            for (i = 0; i < nodes.length; i++) { 
                el = nodes[i];
                $items = '';
                if( (el.name=='hidden_form_id') || (el.name=='id') ) continue;
                $parent = el.closest('.super-shortcode');
                $html += '<tr>';
                $html += '<th>';
                $html += el.dataset.email;
                $html += '</th>';
                $html += '<td>';
                    if($parent.classList.contains('super-radio')){
                        $html += ($parent.querySelector('.super-active') ? $parent.querySelector('.super-active').innerText : '');
                    }else if($parent.classList.contains('super-dropdown')){
                        items = $parent.querySelectorAll('.super-dropdown-list .super-active');
                        for (ii = 0; ii < items.length; ii++) { 
                            $items += ($items==='' ? items[ii].innerText : ', '+items[ii].innerText);
                        }
                        $html += $items;
                    }else if($parent.classList.contains('super-checkbox')){
                        items = $parent.querySelectorAll('.super-active');
                        for (ii = 0; ii < items.length; ii++) { 
                            $items += ($items==='' ? items[ii].innerText : ', '+items[ii].innerText);
                        }
                        $html += $items;
                    }else{
                        $html += el.value;
                    }
                $html += '</td>';
                $html += '</tr>';
            }
            $html += '</table>';
            win.document.write($html);
            win.document.close();
            win.focus();
            // @since 2.3 - chrome browser bug
            setTimeout(function() {
                win.print();
                win.close();
            }, 250);
            return false;
        }
    };

    // @since 2.0.0 - clear / reset form fields
    SUPER.init_clear_form = function(args){
        var field, nodes, innerNodes, i, ii,
            children, index, element, dropdownItem, 
            option, switchBtn, activeItem,
            value = '',
            default_value,
            main_form = args.form,
            new_value = '',
            new_placeholder = '';

        if(typeof args.clone !== 'undefined') {
            args.form = args.clone;
            // @since 2.7.0 - reset fields after adding column dynamically
            // Slider field
            nodes = args.form.querySelectorAll('.super-shortcode.super-slider > .super-field-wrapper > *:not(.super-shortcode-field)');
            for (i = 0; i < nodes.length; i++) { 
                nodes[i].remove();
            }
            // Color picker
            nodes = args.form.querySelectorAll('.super-color .sp-replacer');
            for (i = 0; i < nodes.length; i++) { 
                nodes[i].remove();
            }
        }else{
            var formId = SUPER.getFormIdByAttributeID(args.form);
            SUPER.files[formId] = [];
            // Reset all preflight values
            delete SUPER.preFlightMappings[formId];
        }
        
        // @since 3.2.0 - remove the google autocomplete init class from fields
        nodes = args.form.querySelectorAll('.super-address-autopopulate.super-autopopulate-init');
        for (i = 0; i < nodes.length; i++) {
            nodes[i].classList.remove('super-autopopulate-init');
        }
        // @since 3.5.0 - remove datepicker picker initialized class
        // @since 4.5.0 - remove color picker initialized class
        nodes = args.form.querySelectorAll('.super-picker-initialized');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].classList.remove('super-picker-initialized');
            nodes[i].classList.remove('hasDatepicker');
            nodes[i].id = '';
            //SUPER.init_datepicker();
        }

        // @since 3.6.0 - remove the active class from autosuggest fields
        nodes = args.form.querySelectorAll('.super-auto-suggest .super-dropdown-list .super-item.super-active');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].style.display = '';
            nodes[i].classList.remove('super-active');
        }
        nodes = args.form.querySelectorAll('.super-overlap');
        for (i = 0; i < nodes.length; i++) { 
            if(args.clear===false){
                if(!nodes[i].closest('.super-wc-order-search')){
                    nodes[i].classList.remove('super-overlap');
                }
            }else{
                nodes[i].classList.remove('super-overlap');
            }
        }
        // @since 4.8.20 - reset keyword fields
        nodes = args.form.querySelectorAll('.super-keyword-tags .super-keyword-filter');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].style.display = 'block';
            nodes[i].value = '';
        }
        nodes = args.form.querySelectorAll('.super-keyword-tags .super-autosuggest-tags > span');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].remove();
        }
        nodes = args.form.querySelectorAll('.super-keyword-tags .super-autosuggest-tags-list .super-active');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].classList.remove('super-active');
        }
        nodes = args.form.querySelectorAll('.super-keyword-tags .super-shortcode-field');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].value = '';
        }
        //nodes = args.form.querySelectorAll('.super-keyword-tags');
        //for (i = 0; i < nodes.length; i++) {
        //    field = nodes[i].querySelector('.super-keyword-filter');
        //    field.placeholder = field.dataset.placeholder;
        //}
        // @since 4.8.0 - reset TABs to it's initial state (always first TAB active)
        nodes = args.form.querySelectorAll('.super-tabs-menu .super-tabs-tab');
        if(nodes){
            if(nodes[0]) nodes[0].click();
        }
        // Remove all dynamic added columns
        nodes = args.form.querySelectorAll('.super-duplicate-column-fields');
        for (i = 0; i < nodes.length; i++) {
            children = Array.prototype.slice.call( nodes[i].parentNode.children );
            index = children.indexOf(nodes[i]);
            if(index>0) nodes[i].remove();
        }

        // Remove error classes and filled classes
        nodes = args.form.querySelectorAll('.super-error, .super-error-active, .super-filled');
        for (i = 0; i < nodes.length; i++) { 
            if(nodes[i].parentNode.classList.contains('super-i18n-switcher')) {
                
                continue;
            }
            if(args.clear===false){
                if(!nodes[i].classList.contains('super-wc-order-search')){
                    nodes[i].classList.remove('super-error');
                    nodes[i].classList.remove('super-error-active');
                    nodes[i].classList.remove('super-filled');
                }
            }else{
                nodes[i].classList.remove('super-error');
                nodes[i].classList.remove('super-error-active');
                nodes[i].classList.remove('super-filled');
            }
        }

        // Clear all fields
        nodes = args.form.querySelectorAll('.super-shortcode-field');
        for (i = 0; i < nodes.length; i++) {
            if(nodes[i].name=='hidden_form_id') continue;
            if(nodes[i].name=='hidden_list_id') continue;
            if(nodes[i].name=='hidden_contact_entry_id') continue;
            element = nodes[i];
            default_value = '';
            default_value = element.dataset.defaultValue;
            if(typeof element.dataset.absoluteDefault!=='undefined'){
                default_value = element.dataset.absoluteDefault;
            }
            field = element.closest('.super-field');
            if(!field) continue; // Continue to next field
            if(args.clear===false && field.classList.contains('super-wc-order-search')){
                continue;
            }

            // If value is not empty, set filled status
            if( default_value !== "" ) {
                field.classList.add('super-filled');
            }else{
                field.classList.remove('super-filled');
            }

            // Checkbox and Radio buttons
            if( field.classList.contains('super-checkbox') || field.classList.contains('super-radio') ){
                innerNodes = field.querySelectorAll('.super-item');
                for (ii = 0; ii < innerNodes.length; ii++) { 
                    if(innerNodes[ii].classList.contains('super-default-selected')){
                        innerNodes[ii].classList.add('super-active');
                        innerNodes[ii].querySelector('input').checked = true;
                    }else{
                        innerNodes[ii].classList.remove('super-active');
                        innerNodes[ii].querySelector('input').checked = false;
                    }
                }
            }

            // Quantity field
            if(field.classList.contains('super-quantity')){
                if(default_value==='') default_value = 0;
            }

            // Currency field
            if(field.classList.contains('super-currency')){
                element.value = default_value;
                continue; // Continue to next field
            }

            // Color picker
            if(field.classList.contains('super-color')){
                if(typeof $.fn.spectrum === "function") {
                    if(default_value==='') default_value = '#fff';
                    $(field.querySelector('.super-shortcode-field')).spectrum('set', default_value);
                }
                continue; // Continue to next field
            }

            // Toggle field
            if(field.classList.contains('super-toggle')){
                switchBtn = field.querySelector('.super-toggle-switch');
                activeItem = switchBtn.querySelector('label[data-value="'+default_value+'"]');
                // If it does not exists, then we default to "off" setting
                if(!activeItem){
                    default_value = switchBtn.querySelector('.super-toggle-off').dataset.value;
                    activeItem = switchBtn.querySelector('label[data-value="'+default_value+'"]');
                }
                if(activeItem.classList.contains('super-toggle-on')){
                    switchBtn.classList.add('super-active');
                }else{
                    switchBtn.classList.remove('super-active');
                }
                element.value = activeItem.dataset.value;
                continue;
            }

            // Dropdown field
            if(field.classList.contains('super-dropdown')){
                innerNodes = field.querySelectorAll('.super-dropdown-list .super-item.super-active');
                for (ii = 0; ii < innerNodes.length; ii++) { 
                    innerNodes[ii].classList.remove('super-active');
                }
                innerNodes = field.querySelectorAll('.super-dropdown-list .super-item.super-default-selected');
                for (ii = 0; ii < innerNodes.length; ii++) { 
                    innerNodes[ii].classList.add('super-active');
                }
                if(innerNodes.length){
                    field.classList.add('super-filled');
                }
                if(typeof default_value === 'undefined') default_value = '';
                option = field.querySelector('.super-dropdown-list .super-item:not(.super-placeholder)[data-value="'+default_value+'"]:not(.super-placeholder)');
                if(option){
                    field.querySelector('.super-placeholder').innerHTML = option.innerText;
                    option.classList.add('super-active');
                    element.value = default_value;
                    element.value = '';
                }else{
                    if(field.querySelectorAll('.super-dropdown-list .super-item.super-active').length===0){
                        if( (typeof element.placeholder !== 'undefined') && (element.placeholder!=='') ) {
                            field.querySelector('.super-placeholder').innerHTML = element.placeholder;
                            dropdownItem = field.querySelector('.super-dropdown-list .super-item[data-value="'+element.placeholder+'"]');
                            if(dropdownItem) dropdownItem.classList.add('super-active');
                        }else{
                            field.querySelector('.super-placeholder').innerHTML = field.querySelector('.super-dropdown-list .super-item').innerText;
                        }
                        element.value = '';
                    }else{
                        innerNodes = field.querySelectorAll('.super-dropdown-list .super-item.super-active');
                        new_placeholder = '';
                        new_value = '';
                        for (ii = 0; ii < innerNodes.length; ii++) { 
                            if(new_value===''){
                                new_value += innerNodes[ii].dataset.value;
                            }else{
                                new_value += ','+innerNodes[ii].dataset.value;
                            }
                            if(new_placeholder===''){
                                new_placeholder += innerNodes[ii].innerText;
                            }else{
                                new_placeholder += ', '+innerNodes[ii].innerText;
                            }
                        }
                        field.querySelector('.super-placeholder').innerHTML = new_placeholder;
                        element.value = new_value;
                    }
                }
                continue;
            }

            // Autosuggest field
            if(field.classList.contains('super-auto-suggest')){
                innerNodes = field.querySelectorAll('.super-dropdown-list .super-item.super-active');
                for (ii = 0; ii < innerNodes.length; ii++) { 
                    innerNodes[ii].classList.remove('super-active');
                }
                field.querySelector('.super-field-wrapper').classList.remove('super-overlap');
                element.value = '';
                continue;
            }

            // File upload field
            if(field.classList.contains('super-file')){
                field.querySelector('.super-fileupload-files').innerHTML = '';
                field.querySelector('.super-progress-bar').removeAttribute('style');
                field.querySelector('.super-active-files').value = '';
                continue;
            }

            if(typeof default_value !== 'undefined'){
                value = default_value;
                element.value = value;
                // Slider field
                if(field.classList.contains('super-slider')){
                    // Only have to set new value if slider was already initialized (this depends on clearing form after a dynamic column was added)
                    if(element.parentNode.querySelector('.slider')){
                        SUPER.reposition_slider_amount_label(element, value);
                    }
                    continue;
                }
                // Rating field
                if(field.classList.contains('super-rating')){
                    innerNodes = field.querySelectorAll('.super-rating-star');
                    for (ii = 0; ii < innerNodes.length; ii++) {
                        if(ii<=(parseInt(value,10)-1)){
                            innerNodes[ii].classList.add('super-active');
                        }else{
                            innerNodes[ii].classList.remove('super-active');
                        }
                    }
                }
            }
            element.value = value;
        }

        if(typeof args.clone !== 'undefined') {
            // A column was duplicated
        }else{
            SUPER.after_field_change_blur_hook({form: main_form});
        }

        // If has multi-part reset to step 1
        if(main_form.querySelector('.super-multipart') && (typeof args.clone === 'undefined')){
            var step = main_form.querySelector('.super-multipart-step');
            children = Array.prototype.slice.call( step.parentNode.children );
            index = children.indexOf(step);
            var total = main_form.querySelectorAll('.super-multipart').length;
            var progress = 100 / total;
            progress = progress * (index+1);
            var multipart = main_form.querySelectorAll('.super-multipart')[index];
            main_form.querySelector('.super-multipart-progress-bar').style.width = progress+'%';
            main_form.querySelector('.super-multipart-step.super-active').classList.remove('super-active');
            main_form.querySelector('.super-multipart.super-active').classList.remove('super-active');
            step.classList.add('super-active');
            multipart.classList.add('super-active');
        }
        // After form cleared
        SUPER.after_form_cleared_hook(args.form);
    };

    // Populate form with entry data found after ajax call
    SUPER.populate_form_with_entry_data = function(data, form, clear){
        if(!data) return;
        if(typeof clear === 'undefined') clear = true;
        var i,ii,iii,nodes,items,item,options,wrapper,input,innerNodes,firstValue,dropdown,setFieldValue,setFieldHtml,itemFirstValue,
            raw_value,html,files,element,field,stars,currentStar,
            switchBtn,activeItem,fieldName,
            updatedFields = {};        

        data = JSON.parse(data);
        if(data!==false && data.length!==0){
            if(form.parentNode.classList.contains('super-listing-entry-wrapper')){
                // Clear cache for this form
                // Basically required whenever we are editing entry via Listings and the same form was previously loaded on the current page
                SUPER.clearFormCache();
                //SUPER.cachedConditionalLogicByFieldName = {};
                //SUPER.cachedFields[form.id] = {};
            }
            var formId = 0;
            if(form.querySelector('input[name="hidden_form_id"]')){
                formId = form.querySelector('input[name="hidden_form_id"]').value;
            }
            // First clear the form
            SUPER.init_clear_form({form: form, clear: clear});
            // Find all dynamic columns and get the first field name
            if(data._super_dynamic_data){
                Object.keys(data._super_dynamic_data).forEach(function(name) {
                    // Check how many times to click/duplicate this dynamic column
                    var clicks = data._super_dynamic_data[name].length;
                    if(clicks>1){
                        // Add it to the list
                        var field = SUPER.field_exists(form, name);
                        if(!field) return;
                        field = SUPER.field(form, name);
                        // Click so that other fields become available
                        var p = field.closest('.super-duplicate-column-fields');
                        if(!p) return;
                        // Click so that other fields become available
                        for(i=1; i<clicks; i++){
                            p.querySelector(':scope > .super-duplicate-actions .super-add-duplicate').click();
                        }
                    }
                });
            }
            Object.keys(data).forEach(function(i) {
                if(data[i].length===0) return true;
                html = '';
                files = '';
                fieldName = data[i].name;
                // Skip these fields (required for Listings edit)
                if( fieldName==='hidden_form_id' ||
                    fieldName==='hidden_list_id' ||
                    fieldName==='hidden_contact_entry_id') return;

                // If we are dealing with files we must set name to the first item (if it exists), if no files exists, we skip it
                if( data[i].type=='files' ) {
                    if( (typeof data[i].files !== 'undefined') && (data[i].files.length!==0) ) {
                        fieldName = data[i].files[0].name;
                        for(var x=0; x<data[i].files.length; x++){
                            var f = data[i].files[x];
                            if(!f.url) continue;
                            if(typeof SUPER.files[formId] === 'undefined') SUPER.files[formId] = [];
                            if(typeof SUPER.files[formId][fieldName] === 'undefined') SUPER.files[formId][fieldName] = [];
                            var fileName = f.value;
                            var fileUrl = f.url;
                            var fileType = '';
                            SUPER.files[formId][fieldName][x] = {};
                            SUPER.files[formId][fieldName][x]['type'] = fileType;
                            SUPER.files[formId][fieldName][x]['name'] = fileName;
                            SUPER.files[formId][fieldName][x]['url'] = fileUrl;
                        }
                    }else{
                        return true; // Skip it!
                    }
                }
                if(!fieldName) return true; // Either no field exists, or it's "_super_dynamic_data"
                // Get the element
                element = SUPER.field(form, fieldName);
                // If no element was found, go to next field
                if(!element) return true;
                // Add to list of updated fields, required to trigger hook `after_field_change_blur_hook`
                raw_value = (data[i].raw_value ? data[i].raw_value : data[i].value);
                if(element.value!=raw_value) {
                    updatedFields[fieldName] = element;
                }
                field = element.closest('.super-field');
                // Update field value by default
                element.value = raw_value;
                
                // If field is filled out add the class otherwise remove the class
                if(element.value===''){
                    element.closest('.super-shortcode').classList.remove('super-filled');
                }else{
                    element.closest('.super-shortcode').classList.add('super-filled');
                }
                
                // Internation phonenumber
                if(field.classList.contains('super-int-phone-field')){
                    var intPhone = window.superTelInputGlobals.getInstance(element);
                    intPhone.setNumber(raw_value);
                    return true;
                }

                // Color picker
                if(field.classList.contains('super-color')){
                    if(typeof $.fn.spectrum === "function") {
                        $(field.querySelector('.super-shortcode-field')).spectrum('set', raw_value);
                    }
                    return true;
                }

                // Signature element
                if(field.classList.contains('super-signature')){
                    if(typeof $.fn.SuperSignaturePad === "function") {
                        var canvasWrapper = field.querySelector('.super-signature-canvas');
                        var canvas = canvasWrapper.querySelector('canvas');
                        var width = canvasWrapper.getBoundingClientRect().width;
                        var height = canvasWrapper.getBoundingClientRect().height;
                        canvas.width = width;
                        canvas.height = height;
                        var formUid = field.closest('.super-form').dataset.sfuid;
                        var fieldName = field.querySelector('.super-shortcode-field').name;
                        if(typeof SUPER.signatures[formUid] === 'undefined') SUPER.signatures[formUid] = {};
                        if(typeof SUPER.signatures[formUid][fieldName] === 'undefined') SUPER.signatures[formUid][fieldName] = {};
                        var signaturePad = SUPER.signatures[formUid][fieldName]
                        signaturePad.fromDataURL(raw_value, { ratio: 1, width: width, height: height, xOffset: 0, yOffset: 0 });
                        // Remove clear button
                        if(raw_value!==''){
                            field.querySelector('.super-shortcode-field').dataset.disallowEdit = 'true';
                            if(canvasWrapper.parentNode.querySelector('.super-signature-clear')){
                                canvasWrapper.parentNode.querySelector('.super-signature-clear').remove();
                                signaturePad.off();
                            }
                            field.classList.add('super-filled'); // Make sure to be able to delete signature to be able to draw a new one
                        }
                    }
                    return true;
                }

                // Toggle field
                if(field.classList.contains('super-toggle')){
                    switchBtn = field.querySelector('.super-toggle-switch');
                    activeItem = switchBtn.querySelector('label[data-value="'+raw_value+'"]');
                    if(activeItem.classList.contains('super-toggle-on')){
                        switchBtn.classList.add('super-active');
                    }else{
                        switchBtn.classList.remove('super-active');
                    }
                    return true;
                }

                // File upload field
                if(data[i].type=='files'){
                    if((typeof data[i].files !== 'undefined') && (data[i].files.length!==0)){
                        $.each(data[i].files, function( fi, fv ) {
                            if(!fv.url || (fv.url && fv.url==='undefined')) return;
                            if(fi===0) {
                                files += fv.value;
                            }else{
                                files += ','+fv.value;
                            }
                            element = form.querySelector('.super-active-files[name="'+fv.name+'"]');
                            field = element.closest('.super-field');     
                            html += SUPER.get_single_uploaded_file_html(false, true, fv.value, fv.type, fv.url);
                        });
                        element.value = files;
                        field.querySelector('.super-fileupload-files').innerHTML = html;
                        field.querySelector('.super-fileupload').classList.add('finished');
                    }else{
                        field.querySelector('.super-fileupload-files').innerHTML = '';
                        field.querySelector('.super-progress-bar').removeAttribute('style');
                        field.querySelector('.super-active-files').value = '';
                    }
                    return true;
                }
                // Slider field
                if(field.classList.contains('super-slider')){
                    SUPER.reposition_slider_amount_label(field, raw_value);
                    return true;
                }
                // Keyword tags
                if(field.classList.contains('super-keyword-tags')){
                    if(raw_value!==''){
                        options = raw_value.split(',');
                        html = field.querySelector('.super-autosuggest-tags').innerHTML;
                        items = '';
                        for(ii=0; ii<options.length; ii++){
                            items += '<span class="super-noselect super-keyword-tag" sfevents=\'{"click":"keywords.remove"}\' data-value="'+options[ii]+'">'+options[ii]+'</span>';
                        }
                        field.querySelector('.super-autosuggest-tags').innerHTML = items + html;
                    }
                    return true;
                }
                // Autosuggest field
                if(field.classList.contains('super-auto-suggest')){
                    dropdown = field.querySelector('.super-dropdown-list');
                    if(raw_value!==''){
                        options = raw_value.split(',');
                        firstValue = raw_value; //.split(';')[0];
                        setFieldValue = '';
                        nodes = dropdown.querySelectorAll('.super-item.super-active');
                        for(ii=0;ii<nodes.length;ii++){
                            nodes[ii].classList.remove('super-active');
                        }
                        for(ii=0;ii<options.length;ii++){
                            innerNodes = dropdown.querySelectorAll('.super-item[data-value^="'+firstValue+'"]');
                            for(iii=0;iii<innerNodes.length;iii++){
                                if(options[ii]!==innerNodes[iii].dataset.value){
                                    // Important check, so we don't get duplicate selections for instance when having dropdown items `Son` and `Son-in-law`
                                    continue;
                                }
                                itemFirstValue = innerNodes[iii].dataset.value; //.split(';')[0];
                                if(itemFirstValue==firstValue){
                                    field.querySelector('.super-field-wrapper').classList.add('super-overlap');
                                    innerNodes[iii].classList.add('super-active');
                                    if(setFieldValue===''){
                                        setFieldValue += innerNodes[iii].innerText;
                                    }else{
                                        setFieldValue += ','+innerNodes[iii].innerText;
                                    }
                                }
                            }
                        }
                        element.value = setFieldValue;
                    }else{
                        nodes = dropdown.querySelectorAll('.super-dropdown-list .super-item.super-active');
                        for(ii=0;ii<nodes.length;ii++){
                            nodes[ii].classList.remove('super-active');
                        }
                    }
                    return true;
                }
                // Dropdown field
                if(field.classList.contains('super-dropdown')){
                    if(raw_value!==''){
                        options = raw_value.split(',');
                        dropdown = field.querySelector('.super-dropdown-list');
						setFieldValue = '';
						setFieldHtml = '';
                        nodes = dropdown.querySelectorAll('.super-item.super-active');
                        for(ii=0;ii<nodes.length;ii++){
                            nodes[ii].classList.remove('super-active');
                        } 
						if(element.dataset.minlength==='1' && element.dataset.maxlength==='1'){
							// Check against full raw value only
							innerNodes = dropdown.querySelectorAll('.super-item:not(.super-placeholder)[data-value="'+raw_value+'"]');
							for ( iii = 0; iii < innerNodes.length; iii++){
								setFieldValue = innerNodes[iii].dataset.value; 
								setFieldHtml = innerNodes[iii].innerHTML;
								innerNodes[iii].classList.add('super-active');
							}
							if(setFieldValue!==''){
								dropdown.querySelector('.super-placeholder').innerHTML = setFieldHtml; 
							}
							element.value = setFieldValue;
							return true;
						}else{
                            for(ii=0;ii<options.length;ii++){
								innerNodes = dropdown.querySelectorAll('.super-item:not(.super-placeholder)[data-value^="'+options[ii]+'"]');
                                for(iii=0;iii<innerNodes.length;iii++){
                                    if(options[ii]!==innerNodes[iii].dataset.value){
                                        // Important check, so we don't get duplicate selections for instance when having dropdown items `Son` and `Son-in-law`
                                        continue;
                                    }
									itemFirstValue = innerNodes[iii].dataset.value; //.split(';')[0];
									innerNodes[iii].classList.add('super-active');
									if(setFieldValue===''){
										setFieldValue += itemFirstValue;
									}else{
										setFieldValue += ','+itemFirstValue;
                                    }
								}
							}
						}
                        element.value = setFieldValue;
                    }else{
                        nodes = field.querySelectorAll('.super-dropdown-list .super-item.super-active');
                        for(ii=0;ii<nodes.length;ii++){
                            nodes[ii].classList.remove('super-active');
                        }
                        nodes = field.querySelectorAll('.super-dropdown-list .super-item.super-default-selected');
                        for(ii=0;ii<nodes.length;ii++){
                            nodes[ii].classList.add('super-active');
                        }
                    }
                    SUPER.init_set_dropdown_placeholder();
                    return true;
                }
                // Radio buttons
                if(field.classList.contains('super-radio')){
                    wrapper = field.querySelector('.super-field-wrapper');
                    items = wrapper.querySelectorAll('.super-item');
                    for( ii = 0; ii < items.length; ii++){
                        input = items[ii].querySelector('input');
                        items[ii].classList.remove('super-active');
                        input.checked = false;
                    }
                    for( ii = 0; ii < items.length; ii++){
                        input = items[ii].querySelector('input');
                        if(raw_value!=='' && input.value == raw_value){
                            input.checked = true;
                            items[ii].classList.add('super-active');
                            break; // Radio button can only have 1 active item
                        }
                    }
                    if(raw_value===''){
                        // Radio button can only have 1 active item
                        item = wrapper.querySelector('.super-item.super-default-selected');
                        if(item){
                            item.classList.add('super-active');  
                            item.querySelector('input').checked = true;
                        }
                    }
                    return true;
                }
                // Checkboxes
                if(field.classList.contains('super-checkbox')){
                    wrapper = field.querySelector('.super-field-wrapper');
                    items = wrapper.querySelectorAll('.super-item');
                    for( ii = 0; ii < items.length; ii++){
                        input = items[ii].querySelector('input');
                        items[ii].classList.remove('super-active');
                        input.checked = false;
                        if(raw_value!==''){
                            options = raw_value.split(',');
                            if(options.indexOf(input.value)!==-1){
                                input.checked = true;
                                items[ii].classList.add('super-active');
                            }
                        }
                    }
                    if(raw_value===''){
                        items = wrapper.querySelectorAll('.super-item.super-default-selected');
                        for( ii = 0; ii < items.length; ii++){
                            items[ii].classList.add('super-active');  
                            items[ii].querySelector('input').checked = true;
                        }
                    }
                    return true;
                }
                // Rating field
                if(field.classList.contains('super-rating')){
                    stars = field.querySelectorAll('.super-rating-star');
                    currentStar = parseInt(raw_value) || 0;
                    for( ii = 0; ii < stars.length; ii++){
                        if(ii+1 <= currentStar){
                            stars[ii].classList.add('super-active');
                        }else{
                            stars[ii].classList.remove('super-active');
                        }
                    }
                    return true;
                }
                if(field.classList.contains('super-field-type-datetime-local')){
                    element.value = raw_value;
                    element.classList.remove('super-picker-initialized');
                    SUPER.init_datepicker();
                    return true;
                }
                if(field.classList.contains('super-date')){
                    // Reset all values?
                    element.value = raw_value;
                    element.classList.remove('super-picker-initialized');
                    element.classList.remove('hasDatepicker');
                    element.id = '';
                    SUPER.init_datepicker();
                    $(field).datepicker('setDate', element.value);
                }
            });
            // @since 2.4.0 - after inserting all the fields, update the conditional logic and variable fields
            Object.keys(updatedFields).forEach(function(key) {
                SUPER.after_field_change_blur_hook({el: updatedFields[key]});
            });
        }
    };

    // Retrieve entry data through ajax
    // (this function is called when search field is changed, or when $_GET is set on page load)
    SUPER.populate_form_data_ajax = function(args){
        if(typeof args.clear === 'undefined') args.clear = true;
        var orderId,
            value,
            skipFields,
            method,
            form = SUPER.get_frontend_or_backend_form(args);

        // If we are populating based of WC order search
        if(args.el.classList.contains('super-wc-order-search')){
            // Get order ID based on active item
            value = args.el.querySelector('.super-active').dataset.value;
            orderId = value.split(';')[0];
            // Check if we need to skip any fields
            skipFields = '';
            if(args.el.querySelector('.super-shortcode-field')){
                if(args.el.querySelector('.super-shortcode-field').dataset.wcoss){
                    skipFields = args.el.querySelector('.super-shortcode-field').dataset.wcoss; 
                }
            } 
            // We now have the order ID, let's search the order and get entry data if possible
            args.el.querySelector('.super-field-wrapper').classList.add('super-populating');
            $.ajax({
                url: super_common_i18n.ajaxurl,
                type: 'post',
                data: {
                    action: 'super_populate_form_data',
                    order_id: orderId,
                    skip: skipFields
                },
                success: function (data) {
                    SUPER.populate_form_with_entry_data(data, form, args.clear);
                },
                complete: function(){
                    args.el.querySelector('.super-field-wrapper').classList.remove('super-populating');
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    // eslint-disable-next-line no-console
                    console.log(xhr, ajaxOptions, thrownError);
                    alert(super_common_i18n.errors.failed_to_process_data);
                }
            });
        }else{
            args.el.dataset.typing = 'false';
            value = args.el.value;
            method = args.el.dataset.searchMethod;
            // Check if we need to skip any fields
            skipFields = (args.el.dataset.searchSkip ? args.el.dataset.searchSkip : '');
            if( value.length > 2 ) {
                args.el.closest('.super-field-wrapper').classList.add('super-populating');
                $.ajax({
                    url: super_common_i18n.ajaxurl,
                    type: 'post',
                    data: {
                        action: 'super_populate_form_data',
                        value: value,
                        method: method,
                        skip: skipFields
                    },
                    success: function (data) {
                        SUPER.populate_form_with_entry_data(data, form, args.clear);
                    },
                    complete: function(){
                        args.el.closest('.super-field-wrapper').classList.remove('super-populating');
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        // eslint-disable-next-line no-console
                        console.log(xhr, ajaxOptions, thrownError);
                        alert(super_common_i18n.errors.failed_to_process_data);
                    }
                });
            }
        }
    };
    SUPER.populate_form_order_data_ajax = function(args){
        if( args.el.value.length>0 ) {
            args.el.closest('.super-field-wrapper').classList.add('super-populating');
            $.ajax({
                url: super_common_i18n.ajaxurl,
                type: 'post',
                data: {
                    action: 'super_search_wc_orders',
                    value: args.el.value,
                    method: args.el.dataset.wcosm,
                    filterby: args.el.dataset.wcosfb,
                    return_label: args.el.dataset.wcosrl,
                    return_value: args.el.dataset.wcosrv,
                    populate: args.el.dataset.wcosp,
                    skip: args.el.dataset.wcoss,
                    status: args.el.dataset.wcosst
                },
                success: function (result) {
                    if(result!==''){
                        args.el.closest('.super-shortcode').classList.add('super-focus');
                        args.el.closest('.super-shortcode').classList.add('super-string-found');
                    }
                    var ul = args.el.closest('.super-field-wrapper').querySelector('.super-dropdown-list');
                    if(ul){
                        ul.innerHTML = result;
                    }else{
                        $('<ul class="super-dropdown-list">'+result+'</ul>').appendTo($(args.el).parents('.super-field-wrapper:eq(0)'));
                    }
                    // If there is a single result, click it
                    var ul = args.el.closest('.super-field-wrapper').querySelector('.super-dropdown-list');
                    if(ul && ul.children.length===1){
                        ul.children[0].click();
                    }
                },
                complete: function(){
                    args.el.closest('.super-field-wrapper').classList.remove('super-populating');
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    // eslint-disable-next-line no-console
                    console.log(xhr, ajaxOptions, thrownError);
                    alert(super_common_i18n.errors.failed_to_process_data);
                }
            });
        }
    };


    SUPER.update_unique_code = function(el, submittingForm){
        $.ajax({
            url: super_common_i18n.ajaxurl,
            type: 'post',
            data: {
                action: 'super_update_unique_code',
                submittingForm: submittingForm,
                codesettings: el.dataset.codesettings // {"invoice_key:"","len":"7","char":"1","pre":"","inv":"","invp":"4","suf":"","upper":"true","lower":""}
            },
            success: function (result) {
                el.value = result;
                el.classList.add('super-generated');
                SUPER.after_field_change_blur_hook({el: el});
            },
            error: function (xhr, ajaxOptions, thrownError) {
                // eslint-disable-next-line no-console
                console.log(xhr, ajaxOptions, thrownError);
                alert('Failed to generate unique code');
            }
        });
    }
    // init the form on the frontend
    SUPER.init_super_form_frontend = function(args){
        if(typeof args==='undefined') args = { callback: null };
        // Do not do anything if all forms where intialized already
        if(document.querySelectorAll('.super-form').length===document.querySelectorAll('.super-form.super-initialized').length){
            return true;
        }
        // Restart at later time:
        if(SUPER.formFullyLoaded.timer===null){
            SUPER.formFullyLoaded.timer = setInterval(SUPER.formFullyLoaded.timerFunction, 100);
        }
        $('.super-form').each(function(){
            var formId = 0;
            if(!this.classList.contains('super-preview-elements')) formId = SUPER.getFormIdByAttributeID(this);
            if(typeof SUPER.preFlightMappings === 'undefined') SUPER.preFlightMappings = {};
            if(typeof SUPER.preFlightMappings[formId] === 'undefined') {
                SUPER.preFlightMappings[formId] = {
                    fieldNames: [],
                    tags: {}
                }
            }
        });
      
        // @since 3.3.0 - make sure to load dynamic columns correctly based on found contact entry data when a search field is being used
        $('.super-shortcode-field[data-search="true"]:not(.super-dom-populated)').each(function(){
            if(this.value!==''){
                this.classList.add('super-dom-populated');
                
                SUPER.populate_form_data_ajax({el: this});
            }
        });
        $('.super-text .super-shortcode-field[data-wcosm]:not(.super-dom-populated)').each(function(){
            if(this.value!==''){
                this.classList.add('super-dom-populated');
                SUPER.populate_form_order_data_ajax({el: this});
            }
        });

        SUPER.init_text_editors();

        // @since 2.3.0 - init file upload fields
        SUPER.init_fileupload_fields();

        SUPER.init_international_phonenumber_fields();

        //Set dropdown placeholder
        SUPER.init_set_dropdown_placeholder($('.super-form:not(.super-rendered)'));

        // @since 1.1.8     - set radio button to correct value
        $('.super-field.super-radio').each(function(){
            var field = this.querySelector('.super-shortcode-field');
            if(field){
                var fieldValue = field.value;
                var itemsList = field.closest('.super-field-wrapper');
                var i, selectedValues = fieldValue.split(',');
                var nodes = itemsList.querySelectorAll('.super-item input[type="radio"]');
                // Loop over all items
                for(i=0; i<nodes.length; i++){
                    // Check if value is in array
                    if( (fieldValue === nodes[i].value) || (fieldValue !== '' && ((selectedValues.indexOf(nodes[i].value)!==-1) || (selectedValues.indexOf(nodes[i].value.trim())!==-1))) ) {
                        nodes[i].closest('.super-item').classList.add('super-active');
                        nodes[i].checked = true;
                    }else{
                        nodes[i].closest('.super-item').classList.remove('super-active');
                        nodes[i].checked = false;
                    }
                }
            }
        });

        // @since 1.1.8     - set checkbox to correct value
        $('.super-field.super-checkbox').each(function(){
            var field = this.querySelector('.super-shortcode-field');
            if(field){
                var fieldValue = field.value;
                var itemsList = field.closest('.super-field-wrapper');
                var i, selectedValues = fieldValue.split(',');
                var nodes = itemsList.querySelectorAll('.super-item input[type="checkbox"]');
                // Loop over all items
                for(i=0; i<nodes.length; i++){
                    // Check if value is in array
                    if( (fieldValue === nodes[i].value) || (fieldValue !== '' && ((selectedValues.indexOf(nodes[i].value)!==-1) || (selectedValues.indexOf(nodes[i].value.trim())!==-1))) ) {
                        nodes[i].closest('.super-item').classList.add('super-active');
                        nodes[i].checked = true;
                    }else{
                        nodes[i].closest('.super-item').classList.remove('super-active');
                        nodes[i].checked = false;
                    }
                }
            }
        });

        // @since 1.3   - input mask
        $('.super-shortcode-field[data-mask]').each(function(){
            $(this).mask($(this).data('mask'));
        });

        // tmp // Populate signature element with possible saved form progress
        // tmp $('.super-form').each(function(){
        // tmp     if($(this).hasClass('super-save-progress')){
        // tmp         $(this).find('.super-signature').each(function(){
        // tmp             var value = $(this).find('.super-signature-lines').val();
        // tmp             if(value!==''){
        // tmp                 value = value.replace('\\"lines\\"', '"lines"');
        // tmp                 try {
        // tmp                     $(this).find('.super-signature-canvas').signature('enable').signature('draw', value);
        // tmp                 }
        // tmp                 catch(error) {
        // tmp                     SUPER.init_signature();
        // tmp                     $(this).find('.super-signature-canvas').signature('enable').signature('draw', value);
        // tmp                     console.log("Error: ", error);
        // tmp                     //$(this).find('.super-signature-canvas').signature('draw', value);
        // tmp                 }
        // tmp             }
        // tmp         });
        // tmp     }
        // tmp });

        // Provide each dynamic column with correct levels
        var i, nodes = document.querySelectorAll('.super-form:not(.super-preview-elements) .super-column[data-duplicate-limit]');
        for(i=0; i<nodes.length; i++){
            // Only proceed with those that are top level
            if(nodes[i].parentNode.closest('.super-column[data-duplicate-limit]')){
                continue;
            }
            // This is a top level dynamic column
            // Look for inner dynamic columns
            var counter=0, x, innerNodes = nodes[i].querySelectorAll('.super-column[data-duplicate-limit]');
            for(x=0; x<innerNodes.length; x++){
                // Compare this parents against this current dynamic column
                if(innerNodes[x].parentNode.closest('.super-column[data-duplicate-limit]')!==nodes[i]){
                    continue;
                }
                innerNodes[x].dataset.level = '['+counter+']';
                // Now lookup inner nodes of this inner node
                var counter2=0, y, innerNodes2 = innerNodes[x].querySelectorAll('.super-column[data-duplicate-limit]');
                for(y=0; y<innerNodes2.length; y++){
                    // Compare this parents against this current dynamic column
                    if(innerNodes2[y].parentNode.closest('.super-column[data-duplicate-limit]')!==innerNodes[x]){
                        continue;
                    }
                    innerNodes2[y].dataset.level = innerNodes[x].dataset.level+'['+counter2+']';
                    counter2++;
                }
                counter++;
            }
        }

        // Loop over all fields that are inside dynamic column and rename them accordingly
        nodes = document.querySelectorAll('.super-form:not(.super-preview-elements) .super-duplicate-column-fields .super-shortcode-field[name]');
        var levels;
        for (i = 0; i < nodes.length; ++i) {
            var field = nodes[i];
            if(field.classList.contains('super-fileupload')){
                field = field.parentNode.querySelector('.super-active-files');
            }
            // Figure out how deep this node is inside dynamic columns
            var originalFieldName = field.dataset.oname;
            var dynamicColum = nodes[i].parentNode.closest('.super-column[data-duplicate-limit]');
            levels = '';
            if(dynamicColum) levels = dynamicColum.dataset.level;
            if(levels && levels!==''){
                field.name = originalFieldName+levels;
                field.dataset.olevels = levels;
            }else{
                field.name = originalFieldName;
            }
        }

        // Multi-part
        $('.super-form:not(.super-preview-elements)').each(function(){
            var form = this,
                $form = $(this),
                $multipart = {},
                $multiparts = [],
                $submit_button,
                $button_clone,
                $total = $form.find('.super-multipart').length,
                $prev,
                $next,
                $progress,
                $progress_steps,
                $progress_bar,
                $clone;

            var formId = SUPER.getFormIdByAttributeID(this);
            if($form.parent().hasClass('elementor-text-editor')){
                $form.html('<p style="color:red;font-size:12px;"><strong>'+super_common_i18n.elementor.notice+':</strong> [Form ID: '+formId+ '] - '+super_common_i18n.elementor.msg+'</p>');
                return false;
            }
            if( $total!==0 ) {
                // Lets check if this form has already rendered the multi-parts
                if( !$form.find('.super-multipart:eq(0)').hasClass('super-rendered') ) {
                    // First Multi-part should be set to active automatically
                    $form.find('.super-multipart:eq(0)').addClass('super-active').addClass('super-rendered');
                    // If a custom prev/next multipart button was found do not add any automatically
                    var hasPrevNextBtns = true;
                    if( $form.find('.super-prev-multipart').length===0 && $form.find('.super-next-multipart').length===0 ) {
                        hasPrevNextBtns = false;
                    }
                    if(!hasPrevNextBtns){
                        $submit_button = $form.find('.super-button:last');
                        $clone = $submit_button.clone();
                        $($clone).appendTo($form.find('.super-multipart:last'));
                        $button_clone = $submit_button[0].outerHTML;
                        $submit_button.remove();
                        $($button_clone).appendTo($form.find('.super-multipart').not(':last')).removeClass('super-button-align-left').removeClass('super-button-align-center').removeClass('super-button-align-right').removeClass('super-form-button').addClass('super-next-multipart').find('.super-button-name').html(super_common_i18n.directions.next);
                        $($button_clone).appendTo($form.find('.super-multipart').not(':first')).removeClass('super-button-align-left').removeClass('super-button-align-center').removeClass('super-button-align-right').removeClass('super-form-button').addClass('super-prev-multipart').find('.super-button-name').html(super_common_i18n.directions.prev);
                    }

                    // Now lets loop through all the multi-parts and set the data such as name and description
                    $form.find('.super-multipart').each(function(){
                        if(!hasPrevNextBtns){
                            if( typeof $(this).data('prev-text') === 'undefined' ) {
                                $prev = super_common_i18n.directions.prev;
                            }else{
                                $prev = $(this).data('prev-text');
                            }
                            if( typeof $(this).data('next-text') === 'undefined' ) {
                                $next = super_common_i18n.directions.next;
                            }else{
                                $next = $(this).data('next-text');
                            }
                            $(this).find('.super-prev-multipart .super-button-name').html($prev);
                            $(this).find('.super-next-multipart .super-button-name').html($next);
                        }
                        $multipart = {
                            name: $(this).data('step-name'),
                            description: $(this).data('step-description'),
                            image: $(this).data('image'),
                            icon: $(this).data('icon'),
                        };
                        $multiparts.push($multipart);
                    });

                    // Lets setup the progress steps
                    $progress_steps  = '<ul class="super-multipart-steps">';
                    $.each($multiparts, function( index, value ) {
                        if($total==1){
                            $progress_steps += '<li class="super-multipart-step super-active last-step">';
                        }else{
                            if((index===0) && ($total != (index+1))){
                                $progress_steps += '<li class="super-multipart-step super-active">';
                            }else{
                                if($total == (index+1)){
                                    $progress_steps += '<li class="super-multipart-step last-step">';
                                }else{
                                    $progress_steps += '<li class="super-multipart-step">';
                                }
                            }
                        }
                        $progress_steps += '<span class="super-multipart-step-wrapper">';
                        if(value.image) {
                            $progress_steps += '<span class="super-multipart-step-image"><img src="'+value.image+'" /></span>';
                        }else{
                            if(value.icon) {
                                $progress_steps += '<span class="super-multipart-step-icon"><i class="fas fa-'+value.icon+'"></i></span>';  
                            }else{
                                $progress_steps += '<span class="super-multipart-step-count">'+(index+1)+'</span>';
                            }
                        }
                        if( value.name!=='' ) {
                            $progress_steps += '<span class="super-multipart-step-name">'+value.name+'</span>';
                        }
                        if( value.description!=='' ) {
                            $progress_steps += '<span class="super-multipart-step-description">'+value.description+'</span>';
                        }
                        $progress_steps += '</span>';
                        $progress_steps += '</li>';
                    });
                    $progress_steps += '</ul>';

                    // Here we set the correct progress bar in percentages
                    $progress = 100 / $total;
                    $progress_bar  = '<div class="super-multipart-progress">';
                    $progress_bar += '<div class="super-multipart-progress-inner">';
                    $progress_bar += '<div class="super-multipart-progress-bar" style="width:'+$progress+'%"></div>';
                    $progress_bar += '</div>';
                    $progress_bar += '</div>';

                    // @4.7.0 - place after language switcher
                    if($form.find('.super-i18n-switcher').length!=0){
                        $($progress_steps).insertAfter($form.find('.super-i18n-switcher'));
                        $($progress_bar).insertAfter($form.find('.super-i18n-switcher'));
                    }else{
                        $form.prepend($progress_steps);
                        $form.prepend($progress_bar);
                    }
                }
                
                // @SINCE 4.9.520 - Remember multi-part position/location and allow to anchor to a specific multi-part
                // Example: http://domain.com/page#step-49344-3
                //tmpSUPER.switch_to_step_and_or_field(form);
            }
            SUPER.init_super_responsive_form_fields({form: form, callback: args.callback, formId: formId});
        });

        $(window).resize(function() {
            var i, nodes = document.querySelectorAll('.super-form:not(.super-generating-pdf)');
            for(i=0; i<nodes.length; i++){
                SUPER.init_super_responsive_form_fields({form: nodes[i], timeout: 500});
            }
        });
        
        var $handle_columns_interval = setInterval(function(){
            if(($('.super-form:not(.super-preview-elements)').length != $('.super-form.super-rendered').length) || ($('.super-form:not(.super-preview-elements)').length===0)){
                SUPER.handle_columns();
            }else{
                clearInterval($handle_columns_interval);
            }
        }, 0);
    };
    $(window).on('hashchange', function() {
        //.. work ..
        var currentStep = window.location.hash.substring(1);
        if(currentStep!==''){
            var form, explodedStep = currentStep.split('-');
            if(explodedStep[0]==='step' && currentStep[4]==='-'){
                var stepFormID = explodedStep[1];
                form = document.querySelector('#super-form-'+stepFormID);
                if(form.classList.contains('super-initialized')){
                    SUPER.switch_to_step_and_or_field(form);
                }
            }else{
                // Check if a field with this name exists
                var fields = SUPER.fieldsByName(document, currentStep);
                if(fields.length){
                    // If so, then try to focus it
                    form = fields[0].closest('.super-form');
                    var formId = SUPER.getFormIdByAttributeID(form);
                    var multipartIndex = $(fields[0]).parents('.super-multipart:eq(0)').index('.super-form form .super-multipart');
                    currentStep = 'step-'+formId+'-'+(multipartIndex+1)+';'+currentStep;
                    if(form.classList.contains('super-initialized')){
                        SUPER.switch_to_step_and_or_field(form, currentStep);
                    }
                }
            }
        }
    });

    SUPER.switch_to_step_and_or_field = function(form, currentStep){
        if(typeof currentStep === 'undefined'){
            currentStep = window.location.hash.substring(1);
        }
        if(currentStep!==''){
            var explodedStep = currentStep.split('-');
            if(explodedStep[0]==='step'){
                var stepFormID = explodedStep[1];
                var multiPart = explodedStep[2].split(';');
                var fieldName = (multiPart[1] ? multiPart[1] : '');
                var step = multiPart[0];
                // Lookup the form based on the ID
                var multiPartForm = document.querySelector('.super-form-'+stepFormID);
                if(multiPartForm){
                    // We found a form, check if it contains a multi-part, if so then make it active
                    var nodes = multiPartForm.querySelectorAll('.super-multipart');
                    // If there are not enough multi-parts default to the first one
                    if(nodes.length < step) step = "1";
                    for(var i = 0; i < nodes.length; i++){
                        if(step==(i+1)){
                            nodes[i].classList.add('super-active');
                        }else{
                            nodes[i].classList.remove('super-active');
                        }
                    }
                    nodes = multiPartForm.querySelectorAll('.super-multipart-step');
                    for(i = 0; i < nodes.length; i++){
                        if(step==(i+1)){
                            nodes[i].classList.add('super-active');
                        }else{
                            nodes[i].classList.remove('super-active');
                        }
                    }
                    var progress = 100 / nodes.length;
                    progress = progress * parseInt(step, 10);
                    if(form.querySelector('.super-multipart-progress-bar')){
                        form.querySelector('.super-multipart-progress-bar').style.width = progress+'%';
                    }
                    SUPER.init_super_responsive_form_fields({form: form})
                    // Scroll to field
                    if(fieldName!==''){
                        var wrapper = form.querySelector('#sf-wrapper-'+stepFormID+'-'+$(form).index()+'-'+fieldName);
                        if(!wrapper) wrapper = form.querySelector('#sf-field-'+stepFormID+'-'+$(form).index()+'-'+fieldName);
                        if(!wrapper) {
                            wrapper = SUPER.field(form, fieldName);
                            wrapper = wrapper.closest('.super-field');
                        }
                        SUPER.scrollToElement(wrapper);
                        SUPER.focusNextTabField(undefined, wrapper, form);
                    }
                }
            }
        }
    };

    // Reposition slider amount label
    SUPER.reposition_slider_amount_label = function(field, value, conditionalUpdate){
        if(typeof jQuery(field).data('slider-object') === 'undefined'){
            // Regenerate slider because this is a cloned form
            if(field.querySelector('.slider')) field.querySelector('.slider').remove();
            if(field.querySelector('.amount')) field.querySelector('.amount').remove();
            SUPER.init_slider_field();
        }
        if(typeof conditionalUpdate === 'undefined') conditionalUpdate = false;
        if(typeof value === 'undefined') {
            value = field.value;
            // Set a class so that we don't focus this field
            if(conditionalUpdate) field.classList.add('super-prevent-focus');
            $(field).simpleSlider("setValue", 0);
            // Set a class so that we don't focus this field
            if(conditionalUpdate) field.classList.add('super-prevent-focus');
            $(field).simpleSlider("setValue", value);
        }else{
            if(value !== field.value){
                // Set a class so that we don't focus this field
                if(conditionalUpdate) field.classList.add('super-prevent-focus');
                $(field).simpleSlider("setValue", 0);
                // Set a class so that we don't focus this field
                if(conditionalUpdate) field.classList.add('super-prevent-focus');
                $(field).simpleSlider("setValue", value);
            }else{
                if(conditionalUpdate){
                    if(value===0 || value==="0"){
                        // Set a class so that we don't focus this field
                        if(conditionalUpdate) field.classList.add('super-prevent-focus');
                        $(field).simpleSlider("setValue", 1);
                    }else{
                        // Set a class so that we don't focus this field
                        if(conditionalUpdate) field.classList.add('super-prevent-focus');
                        $(field).simpleSlider("setValue", 0);
                    }
                    // Set a class so that we don't focus this field
                    if(conditionalUpdate) field.classList.add('super-prevent-focus');
                    $(field).simpleSlider("setValue", value);
                }
            }
        }
    };
    // Init Slider fields
    SUPER.init_slider_field = function(){
        $('.super-slider').each(function () {
            var $this = $(this),
                $field,
                $steps,
                $min,
                $max,
                $currency,
                $format,
                $value,
                $decimals,
                $thousand_separator,
                $decimal_separator,
                $regex,
                $wrapper,
                $number,
                $amount,
                $dragger,
                $slider_width,
                $amount_width,
                $dragger_margin_left,
                $offset_left;

            if( $this.find('.slider').length===0 ) {
                $field = $this.find('.super-shortcode-field');
                $steps = $field.data('steps');
                $min = $field.data('minnumber');
                $max = $field.data('maxnumber');
                if(typeof $min === 'undefined') $min = 0;
                if(typeof $max === 'undefined') $max = 100;
                $currency = $field.data('currency');
                $format = $field.data('format');
                $value = ($field.val()==='' ? 0 : parseFloat($field.val()));
                $decimals = $field.data('decimals');
                $thousand_separator = $field.data('thousand-separator');
                $decimal_separator = $field.data('decimal-separator');
                $regex = '\\d(?=(\\d{' + (3 || 3) + '})+' + ($decimals > 0 ? '\\D' : '$') + ')';
                if( $value<$min ) {
                    $value = $min;
                }
                $value = parseFloat($value).toFixed(Math.max(0, ~~$decimals));
                $value = ($decimal_separator ? $value.replace('.', $decimal_separator) : $value).replace(new RegExp($regex, 'g'), '$&' + ($thousand_separator || ''));
                $field.simpleSlider({
                    snap: true,
                    step: $steps,
                    range: [$min, $max],
                    animate: false
                });
                $wrapper = $field.parents('.super-field-wrapper:eq(0)');
                $wrapper.append('<span class="amount"><i>'+$currency+''+$value+''+$format+'</i></span>');
                $field.on("slider:changed", function ($event, $data) {
                    console.log('slider:changed');
                    // Only focus form/field when form is already initialized
                    if(this.classList.contains('super-prevent-focus')){
                        this.classList.remove('super-prevent-focus');
                    }else{
                        SUPER.focusForm(this);
                        SUPER.focusField(this);
                    }
                    $number = parseFloat($data.value).toFixed(Math.max(0, ~~$decimals));
                    $number = ($decimal_separator ? $number.replace('.', $decimal_separator) : $number).replace(new RegExp($regex, 'g'), '$&' + ($thousand_separator || ''));
                    $amount = $wrapper.children('.amount');
                    $dragger = $data.el[0].querySelector('.dragger');
                    $amount.children('i').html($currency+''+($number)+''+$format);
                    setTimeout(function(){
                        $slider_width = $data.el[0].offsetWidth;
                        if($slider_width===0 && $data.el[0].parentNode){
                            $slider_width = parseFloat(getComputedStyle($data.el[0].parentNode).width);
                        }
                        $amount_width = $amount[0].offsetWidth;
                        $dragger_margin_left = $dragger.style.marginLeft.replace('px','');
                        if($dragger_margin_left<0){
                            $dragger_margin_left = -$dragger_margin_left;
                        }
                        //$offset_left = $dragger.offsetLeft + $dragger_margin_left;
                        $offset_left = parseFloat(getComputedStyle($dragger).left) + $dragger_margin_left;
                        // If offset doesn't have to be less than 0
                        if($offset_left<0) $offset_left = 0;
                        if($slider_width===0) return false; // Required for PDF generator
                        if($slider_width < ($offset_left + $amount_width)){
                            $amount.css('right', '0px');
                            $amount.css('left', 'inherit');
                        }else{
                            $amount.css('right', 'inherit');
                            $amount.css('left', $offset_left+'px');
                        }
                    },1);
                });
                SUPER.reposition_slider_amount_label($field[0]);
            }
        });
        $('.slider-field').each(function () {
            var $this = $(this),
                $field = $this.children('input'),
                $steps = $field.data('steps'),
                $min = $field.data('min'),
                $max = $field.data('max');
            if($this.children('.slider').length===0){
                $field.simpleSlider({
                    snap: true,
                    step: $steps,
                    range: [$min, $max]
                });
                $field.show();
            }
        });
    };
    
    // Init Carousel/Sliders (currently used for checkboxes and radio buttons only)
    SUPER.init_carouseljs = function(){
        if(typeof CarouselJS !== 'undefined'){
            // Initialize CarouselJS
            // eslint-disable-next-line no-undef
            CarouselJS.init();
        }
    };

    // Init Tooltips
    SUPER.init_tooltips = function(){
        if ( $.isFunction($.fn.tooltipster) ) {
            var i, form, formId, formTheme, nodes = document.querySelectorAll('.super-tooltip:not(.tooltipstered)');
            for(i=0; i<nodes.length; i++){
                formId = 0;
                form = SUPER.get_frontend_or_backend_form({el: nodes[i]});
                if(form.querySelector('input[name="hidden_form_id"]')){
                    formId = form.querySelector('input[name="hidden_form_id"]').value;
                }
                formTheme = '';
                if(form.classList.contains('super-default-rounded')){
                    formTheme = 'tooltip-super-default-rounded';
                }
                if(form.classList.contains('super-full-rounded')){
                    formTheme = 'tooltip-super-full-rounded';
                }
                $(nodes[i]).tooltipster({
                    theme: 'tooltip-super-form tooltip-super-form-'+formId+' '+formTheme,
                    contentAsHTML: true,
                    trigger: 'custom',
                    triggerOpen: {
                        click: true,
                        tap: true,
                        mouseenter: true,
                        touchstart: true
                    },
                    triggerClose: {
                        click: true,
                        tap: true,
                        mouseleave: true,
                        originClick: true,
                        touchleave: true
                    }
                });
            }
        }
    };

    // Init color pickers
    SUPER.init_color_pickers = function(){
        if ( $.isFunction($.fn.wpColorPicker) ) {
            $('.super-color-picker').each(function(){
                if($(this).find('.wp-picker-container').length===0){
                    $(this).children('input').wpColorPicker({
                        change: function(event, ui) {
                            // event = standard jQuery event, produced by whichever control was changed.
                            // ui = standard jQuery UI object, with a color member containing a Color.js object
                            if(typeof SUPER.backend_setting_changed === "function") { 
                                SUPER.backend_setting_changed($(this), ui.color.toString());
                            }
                        },
                        palettes: ['#FFFFFF', '#000000', '#444444', '#8E8E8E', '#9A9A9A', '#CDCDCD', '#6E7177', '#F26C68', '#49B4B6' ]
                    });
                    $(this).children('input').wpColorPicker('color', 'red');//
                }
            });
        }
    };

    // Init common fields to init
    SUPER.init_common_fields = function(){
        if(typeof SUPERreCaptcha === 'function') SUPERreCaptcha(); // This function name may not contain a dot. so we have to call it manually.
        SUPER.after_init_common_fields();
    };

    SUPER.resize_toggle_element = function(p){
        var i, nodes = [];
        if(p.classList.contains('super-toggle')){
            nodes.push(p);
        }else{
            nodes = p.querySelectorAll('.super-toggle');
        }
        for( i=0; i<nodes.length; i++ ) {
            // Grab on/off nodes
            var sw = nodes[i].querySelector('.super-toggle-switch');
            var gr = nodes[i].querySelector('.super-toggle-group');
            var on = nodes[i].querySelector('.super-toggle-on');
            var ha = nodes[i].querySelector('.super-toggle-handle');
            var off = nodes[i].querySelector('.super-toggle-off');
            // Reset width for both
            sw.style.width = '';
            gr.style.width = '';
            on.style.width = '';
            off.style.width = '';
            ha.style.width = '';
            // Grab the width
            var width = 0;
            var onWidth = on.offsetWidth+40; // padding left/right 20px
            var offWidth = off.offsetWidth+40; // padding left/right 20px
            var haWidth = ha.offsetWidth+4;
            ha.style.width = haWidth+'px';
            // Now compare, and set new width
            width = onWidth;
            if(onWidth < offWidth){
                width = offWidth;
            }
            sw.style.width = width+(haWidth/2)+'px';
            gr.style.width = ((width+(haWidth/2))*2)-2+'px';
            on.style.width = width+'px';
            off.style.width = width+'px';
        }
    };
    SUPER.reposition_slider_element = function(p, conditionalUpdate){
        if(typeof conditionalUpdate === 'undefined') conditionalUpdate = false;
        var i, nodes = [];
        if(p.classList.contains('super-slider')){
            nodes.push(p);
        }else{
            nodes = p.querySelectorAll('.super-slider');
        }
        for( i=0; i<nodes.length; i++ ) {
            var $element = $(nodes[i]);
            var $wrapper = $element.children('.super-field-wrapper');
            var $field = $wrapper.children('.super-shortcode-field'); 
            if(typeof $field.data("slider-object") === 'undefined'){
                // Regenerate slider because this is a cloned form
                if(nodes[i].querySelector('.slider')) nodes[i].querySelector('.slider').remove();
                if(nodes[i].querySelector('.amount')) nodes[i].querySelector('.amount').remove();
                SUPER.init_slider_field();
            }else{
                var $value = $field.val();
                if($wrapper.children('.slider').length){
                    SUPER.reposition_slider_amount_label($field[0], $value, conditionalUpdate);
                }
            }
        }
    };

    // Handle the responsiveness of the form
    SUPER.responsive_form_fields_timeout = {};
    SUPER.init_super_responsive_form_fields = function(args){
        if(typeof $ === 'undefined') $ = jQuery;
        if(typeof args === 'undefined') args = {};
        if(typeof args.timeout === 'undefined') args.timeout = 0;
        if(typeof args.form === 'undefined') {
            args.form = document.querySelector('.super-form');
        }
        var formId = (args.form.querySelector('input[name="hidden_form_id"]') ? args.form.querySelector('input[name="hidden_form_id"]').value : 0);
		if (SUPER.responsive_form_fields_timeout[args.form.dataset.sfuid] !== null) {
			clearTimeout(SUPER.responsive_form_fields_timeout[args.form.dataset.sfuid]);
		}
        SUPER.responsive_form_fields_timeout[args.form.dataset.sfuid] = setTimeout(function () {
            SUPER.init_common_fields();
            var $classes = [
                'super-first-responsiveness',
                'super-second-responsiveness',
                'super-third-responsiveness',
                'super-fourth-responsiveness',
                'super-last-responsiveness'
            ];
            var $window_classes = [
                'super-window-first-responsiveness',
                'super-window-second-responsiveness',
                'super-window-third-responsiveness',
                'super-window-fourth-responsiveness',
                'super-window-last-responsiveness'
            ];

            var $new_class = '';
            var $new_window_class = '';
            var $window_width = $(window).outerWidth(true);

            var $this = $(args.form);
            var $width = $this.outerWidth(true);
            // Change in width, apply responsiveness
            if($width > 0 && $width < 530){
                SUPER.remove_super_form_classes($this,$classes);
                $this.addClass($classes[0]);
                $new_class = $classes[0];
            }
            if($width >= 530 && $width < 760){
                SUPER.remove_super_form_classes($this,$classes);
                $this.addClass($classes[1]);
                $new_class = $classes[1];
            }
            if($width >= 760 && $width < 1200){
                SUPER.remove_super_form_classes($this,$classes);
                $this.addClass($classes[2]);
                $new_class = $classes[2];
            }
            if($width >= 1200 && $width < 1400){
                SUPER.remove_super_form_classes($this,$classes);
                $this.addClass($classes[3]);
                $new_class = $classes[3];
            }
            if($width >= 1400){
                SUPER.remove_super_form_classes($this,$classes);
                $this.addClass($classes[4]);
                $new_class = $classes[4];
            }

            // @since 1.9 - add the window width responsiveness classes
            if($window_width > 0 && $window_width < 530){
                SUPER.remove_super_form_classes($this,$window_classes);
                $this.addClass($window_classes[0]);
                $new_window_class = $window_classes[0];
            }
            if($window_width >= 530 && $window_width < 760){
                SUPER.remove_super_form_classes($this,$window_classes);
                $this.addClass($window_classes[1]);
                $new_window_class = $window_classes[1];
            }
            if($window_width >= 760 && $window_width < 1200){
                SUPER.remove_super_form_classes($this,$window_classes);
                $this.addClass($window_classes[2]);
                $new_window_class = $window_classes[2];
            }
            if($window_width >= 1200 && $window_width < 1400){
                SUPER.remove_super_form_classes($this,$window_classes);
                $this.addClass($window_classes[3]);
                $new_window_class = $window_classes[3];
            }
            if($window_width >= 1400){
                SUPER.remove_super_form_classes($this,$window_classes);
                $this.addClass($window_classes[4]);
                $new_window_class = $window_classes[4];
            }

            // @since 3.2.0 - check if RTL support is enabled, if so we must revert column order on mobile devices
            if( $this.hasClass('super-rtl') ) {
                if( (!$this.hasClass('super-rtl-reversed')) && ($new_class=='super-first-responsiveness') ) {
                    $this.find('.super-grid').each(function(){
                        var $grid = $(this);
                        var $columns = $grid.children('div.super-column:not(.super-not-responsive)');
                        $grid.append($columns.get().reverse());
                        $grid.children('div.super-column:last-child').removeClass('first-column');
                        $grid.children('div.super-column:eq(0)').addClass('first-column');
                    });
                    $this.addClass('super-rtl-reversed');
                }else{
                    if( ($this.hasClass('super-rtl-reversed')) && ($new_class!='super-first-responsiveness') ) {
                        $this.find('.super-grid').each(function(){
                            var $grid = $(this);
                            var $columns = $grid.children('div.super-column:not(.super-not-responsive)');
                            $grid.append($columns.get().reverse());
                            $grid.children('div.super-column:last-child').removeClass('first-column');
                            $grid.children('div.super-column:eq(0)').addClass('first-column');
                        });
                        $this.removeClass('super-rtl-reversed');
                    }
                }
            }

            // Resize toggle elements
            SUPER.resize_toggle_element(args.form);
            // Reposition slider dragger
            SUPER.reposition_slider_element(args.form, true);

            // @since 1.3
            SUPER.after_responsive_form_hook($classes, args.form, $new_class, $window_classes, $new_window_class);

            if(typeof args.callback === 'function'){
                args.callback(formId);
            }
        }, args.timeout);
    };

    // Update field visibility
    SUPER.init_field_filter_visibility = function($this, type) {
        if(typeof type === 'undefined') type = '';
        var $nodes,
            $name;
        if(typeof $this ==='undefined'){
            $nodes = $('.super-elements-container .super-field.super-filter[data-filtervalue], .super-settings .super-field.super-filter[data-filtervalue]');
            $nodes.addClass('super-hidden');
        }else{
            $name = $this.find('.super-element-field').attr('name');
            $nodes =  $('.super-elements-container .super-field[data-parent="'+$name+'"], .super-settings .super-field[data-parent="'+$name+'"]');
        }
        $nodes.each(function(){
            var $this = $(this),
                $container = $this.parents('.super-elements-container:eq(0)'),
                $filtervalue = $this.data('filtervalue'),
                $parent,
                $value,
                $visibility,
                $filtervalues,
                $string_value,
                $match_found = false;

            if($container.length===0){
                $container = $this.parents('.super-settings:eq(0)');
            }
            $parent = $container.find('.super-element-field[name="'+$this.data('parent')+'"]');
            // If is radio button
            if($parent.attr('type')=='radio'){
                $parent = $container.find('.super-element-field[name="'+$this.data('parent')+'"]:checked');
            }
            $value = $parent.val();
            if(typeof $value==='undefined') $value = '';
            $parent = $parent.parents('.super-field.super-filter:eq(0)');

            $visibility = $parent.hasClass('super-hidden');
            if($visibility===true){
                $visibility = 'hidden';
                if($parent.hasClass('super-search-found')){
                    $visibility = 'visible';
                }
            }else{
                $visibility = 'visible';
            }
            $filtervalues = $filtervalue.toString().split(',');
            $string_value = $value.toString();
            $.each($filtervalues, function( index, value ) {
                if($string_value.indexOf(value)!==-1){ 
                    //}.indexOf()==$string_value ) {
                    $match_found = true;
                }
            });
            if( $match_found && ($visibility!='hidden') ) {
                $this.removeClass('super-hidden');
            }else{
                $this.addClass('super-hidden');
            }
            SUPER.init_field_filter_visibility($this, type);
        });

        // Apply styles after changing settings
        // Toggle RTL styles
        if(document.querySelector('input[name="theme_rtl"]')){
            var theme_rtl = document.querySelector('input[name="theme_rtl"]').value;
            if(document.querySelector('.super-preview-elements')){
                if(theme_rtl==='true'){
                    document.querySelector('.super-preview-elements').classList.add('super-rtl');
                }else{
                    document.querySelector('.super-preview-elements').classList.remove('super-rtl');
                }
            }
        }
    };

    // @since 3.1.0 - init distance calculator fields
    SUPER.init_distance_calculators = function(){
        var i, form, method, destination, destinationField,
            nodes = document.querySelectorAll('.super-form .super-distance-calculator');

        for( i = 0; i < nodes.length; i++){
            form = SUPER.get_frontend_or_backend_form({el: nodes[i]});
            method = nodes[i].dataset.distanceMethod;
            if(method=='start'){
                destination = nodes[i].dataset.distanceDestination;
                destinationField = SUPER.field(form, destination);
                if(destinationField){
                    destinationField.dataset.distanceStart = nodes[i].name;
                }
            }
        }
    };

    SUPER.pdf_get_font_data_from_url = function(url){
        return fetch(url).then((response) => {
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }
            return response.json();
        }).then((fontData) => {
            return fontData;
        }).catch((error) => {
            console.error("Error: Could not fetch the font data from the URL.", error);
            return null;
        });
    };

    SUPER.pdf_generator_init = function(args, callback){
        
        args._save_data_callback = callback;

        // Page margins and print area
        // Media                Page size           Print area              Margins
        //                                                                  Top         Bottom      Sides
        // A/Letter (U.S.)      8.5 x 11 in.        8.2 x 10.6 in.          .22 in.     .18 in      .15 in
        // A4 (Metric)          210 x 297 mm        200 x 287 mm            5 mm        5 mm        5 mm
        // Legal Short (U.S.)   8.5 x 14 in.        8.2 x 11.7 in           1.15 in.    1.15 in.    .15 in.
        // Legal (U.S.)         8.5 x 14 in.        8.2 x 11.7 in. (Color)  1.15 in     2.25 in.    .15 in.
        //                                          8.2 x 13.5 in. (Black)  .23 in      .23 in.     .15 in.

        // Page formats
        // Format       Size in Millimeters     Size in Inches          Point (pt)
        // A0           841 x 1189              33.1 x 46.8
        // A1           594 x 841               23.4 x 33.1
        // A2           420 x 594               16.5 x 23.4
        // A3           297 x 420               11.7 x 16.5
        // A4           210 x 297               8.3 x 11.7              595.28, 841.89
        // A5           148 x 210               5.8 x 8.3
        // A6           105 x 148               4.1 x 5.8
        // A7           74 x 105                2.9 x 4.1
        // A8           52 x 74                 2.0 x 2.9
        // A9           37 x 52	                1.5 x 2.0
        // A10          26 x 37                 1.0 x 1.5

        args.orientation = args.pdfSettings.orientation;
        args.format = args.pdfSettings.format;
        // Check if custom format is defined
        var customFormat = args.pdfSettings.customformat;
        if(typeof customFormat !== 'undefined' && customFormat!==''){
            customFormat = customFormat.split(',');
            if(typeof customFormat[1] !== 'undefined'){
                customFormat[0] = customFormat[0].trim();
                customFormat[1] = customFormat[1].trim();
                if(customFormat[0]!=='' && customFormat[1]!==''){
                    args.format = customFormat;
                }
            }
        }

        // For quick debugging purposes only:
        // eslint-disable-next-line no-undef
        //window.jsPDF = window.jspdf.jsPDF
        args._pdf = new window.jspdf.jsPDF({
            orientation: args.orientation,   // Orientation of the first page. Possible values are "portrait" or "landscape" (or shortcuts "p" or "l").
            format: args.format,             // The format of the first page.  Default is "a4"
            putOnlyUsedFonts: true,    // Only put fonts into the PDF, which were used.
            precision: 16,              // Precision of the element-positions.
            userUnit: 1.0,              // Not to be confused with the base unit. Please inform yourself before you use it.
            floatPrecision: 16,         // or "smart", default is 16
            unit: args.pdfSettings.unit                  // Measurement unit (base unit) to be used when coordinates are specified.

            // @TODO password protected --- // jsPDF supports encryption of PDF version 1.3.
            // @TODO password protected --- // Version 1.3 just uses RC4 40-bit which is kown to be weak and is NOT state of the art.
            // @TODO password protected --- // Keep in mind that it is just a minimal protection.
            // @TODO password protected --- encryption: {
            // @TODO password protected ---     userPassword: "user",
            // @TODO password protected ---     ownerPassword: "owner",
            // @TODO password protected ---     userPermissions: ["print", "modify", "copy", "annot-forms"]
            // @TODO password protected ---     // try changing the user permissions granted
            // @TODO password protected --- }

        });                             // Possible values are "pt" (points), "mm", "cm", "m", "in" or "px".
                                            // Can be:
                                            // a0 - a10
                                            // b0 - b10
                                            // c0 - c10
                                            // dl
                                            // letter
                                            // government-letter
                                            // legal
                                            // junior-legal
                                            // ledger
                                            // tabloid
                                            // credit-card




        // PDF width: 595.28 pt
        // PDF height: 841.89 pt
        
        // PDF width: 210.0015555555555 mm
        // PDF height: 297.0000833333333 mm

        // PDF width: 21.000155555555555 cm
        // PDF height: 29.700008333333333 cm

        // PDF width: 8.267777777777777 in
        // PDF height: 11.692916666666667 in

        // PDF width: 446.46 px
        // PDF height: 631.4175 px

        // pt to px  = X / 1.333333333333333
        // mm to px  = X / 0.4703703703703702
        // cm to px  = X / 0.04703703703703702
        // in to px  = X / 0.0185185185010975
        args.unitRatio = 1;
        if(args.pdfSettings.unit=='pt') args.unitRatio = 1.333333333333333;
        if(args.pdfSettings.unit=='mm') args.unitRatio = 0.4703703703703702;
        if(args.pdfSettings.unit=='cm') args.unitRatio = 0.04703703703703702;
        if(args.pdfSettings.unit=='in') args.unitRatio = 0.0185185185010975;
        if(args.orientation==='portrait'){
            var pageWidth = args._pdf.internal.pageSize.getWidth();
            var pageHeight = args._pdf.internal.pageSize.getHeight();
        }else{
            var pageHeight = args._pdf.internal.pageSize.getWidth();
            var pageWidth = args._pdf.internal.pageSize.getHeight();
        }
        args.pageWidthPortrait = pageWidth;
        args.pageHeightPortrait = pageHeight;
        args.pageWidthLandscape = pageHeight;
        args.pageHeightLandscape = pageWidth;
        if(args.orientation==='portrait'){
            args.pageWidthInPixels = args.pageWidthPortrait / args.unitRatio;
            args.pageHeightInPixels = args.pageHeightPortrait / args.unitRatio;
        }else{
            args.pageWidthInPixels = args.pageWidthLandscape / args.unitRatio;
            args.pageHeightInPixels = args.pageHeightLandscape / args.unitRatio;
        }
        // Make form scrollable based on a4 height
        args.scrollAmount = 0;
        args.pdfSettings.filename = SUPER.update_variable_fields.replace_tags({form: args.form0, value: args.pdfSettings.filename});

        if(super_common_i18n.fonts && super_common_i18n.fonts.link){
            // Replace 'your_url_here' with the actual URL to the JSON file.
            var fontURL = super_common_i18n.fonts.link+'.json';
            SUPER.pdf_get_font_data_from_url(fontURL).then((fontData) => {
                if(fontData){
                    args._pdf.addFileToVFS('NotoSans-Regular-normal.ttf', fontData.regular);
                    args._pdf.addFont('NotoSans-Regular-normal.ttf', 'NotoSans-Regular', 'normal');
                    if(!fontData.bold) {
                        args._pdf.addFileToVFS('NotoSans-Bold-bold.ttf',fontData.regular);
                    }else{
                        args._pdf.addFileToVFS('NotoSans-Bold-bold.ttf',fontData.bold);
                    }
                    args._pdf.addFont('NotoSans-Bold-bold.ttf', 'NotoSans-Bold', 'bold');

                    SUPER.pdf_generator_prepare(args, function(args){
                        // First add pdf-text 
                        SUPER.pdfWrapTextNodes(args.pdfPageContainer);
                        SUPER.pdfWrapTextNodesRender(args.pdfPageContainer);
                        // First scroll over all pages, and determine total pages we have
                        SUPER.pdf_determine_pages(args, function(args){
                            // Start generating pages (starting at page 1)
                            args.currentPage = 1;
                            SUPER.pdf_generator_generate_page(args);
                        });
                    });

                }
            });
            return false;
        }

        // Blur/unfocus any focussed field
        // bug in google chrome on mobile devices
        // .....
        //
        // Add a timeout (just to be sure)
        setTimeout(function(){
            SUPER.pdf_generator_prepare(args, function(args){
                // First add pdf-text 
                SUPER.pdfWrapTextNodes(args.pdfPageContainer);
                SUPER.pdfWrapTextNodesRender(args.pdfPageContainer);
                // First scroll over all pages, and determine total pages we have
                SUPER.pdf_determine_pages(args, function(args){
                    // Start generating pages (starting at page 1)
                    args.currentPage = 1;
                    SUPER.pdf_generator_generate_page(args);
                });
            });
        }, 5000);
        return false;
    };
    SUPER.pdf_determine_pages = function(args, callback){
        args.pageOrientationChanges = {};
        var result = {
            allowIncreasePage: true, // this is important
            belongsTo: [],
            totalScrolled: 0,
            currentPage: 1,
            firstPageBreak: false,
            totalPages: 0,
            currentPageHeight: 0
        }
        args.pageOrientationChanges[result.currentPage] = args.orientation;
        //var i, nodes = args.form0.querySelectorAll('.super-shortcode');
        var i, el, h, nodes = args.form0.querySelectorAll(':scope > form > .super-shortcode,:scope > form .super-shortcode .super-pdf-text');
        for(i=0; i<nodes.length; i++){
            el = nodes[i];
            h = SUPER.getNodeHeight(el, true, true, true);
            if(h===0 && !el.classList.contains('super-pdf_page_break')) {
                continue;
            }
            var top = SUPER.getNodeTop(el, args);
            if(top<=args.scrollAmount){
                // Part of current page
                args.allowIncreasePage = false; // can not contain overlapping elements
            }
            if((top+h)>args.scrollAmount){
                // Also part of next page
                args.allowIncreasePage = el; // might be that inside this element already are overlapping elements
            }else{
                // Only part of current page
            }
            // Reset belongsTo for parent element
            result.belongsTo = [];
            result = SUPER.pdf_determine_belong_to(nodes, nodes[i], result, args);
            args.totalScrolled = result.totalScrolled;
            if(result.belongsTo.length>0){
                nodes[i].dataset.belongsToPages = JSON.stringify(result.belongsTo);
            }
        }
        // Store total pages
        args.totalPages = result.totalPages;
        args.totalPercentagePerPage = (100/args.totalPages) / 3;
        args.pdfPercentageCompleted = 0;
        callback(args);
    };
    SUPER.getNodeHeight = function(el, padding, margin, border){
        var computedStyle = getComputedStyle(el), 
            //elementHeight = el.clientHeight, // Includes vertical padding
            //elementHeight = parseFloat(el.offsetHeight), 
            pos = el.getBoundingClientRect(),
            elementHeight = pos.height,
            paddingTop = 0, paddingBottom = 0, marginTop = 0, marginBottom = 0, borderTopWidth = 0, borderBottomWidth = 0,
            isVisible = computedStyle.visibility !== "hidden" && computedStyle.display !== "none";
        if(!isVisible) return 0;
        //if(padding){
        //    paddingTop = parseFloat(computedStyle.paddingTop);
        //    paddingBottom = parseFloat(computedStyle.paddingBottom);
        //}
        if(margin){
            marginTop = parseFloat(computedStyle.marginTop);
            marginBottom = parseFloat(computedStyle.marginBottom);
        }
        //if(border){
        //    borderTopWidth = parseFloat(computedStyle.borderTopWidth);
        //    borderBottomWidth = parseFloat(computedStyle.borderBottomWidth);
        //}
        return Math.round(elementHeight + paddingTop + paddingBottom + borderTopWidth + borderBottomWidth + marginTop + marginBottom, 1e2);
    }
    SUPER.getNodeTop = function(el, args){
        //var headerHeight = args.pdfPageContainer.querySelector('.super-pdf-header').clientHeight;
        //var header = args.pdfPageContainer.querySelector('.super-pdf-header');
        //var pos = header.getBoundingClientRect();
        //var headerHeight = pos.height;
        var headerHeight = SUPER.pdf_get_header_height(args);
        var pos = el.getBoundingClientRect();
        var computedStyle = getComputedStyle(el);
        var marginTop = parseFloat(computedStyle.marginTop);
        return pos.top-marginTop-headerHeight;
        //return Math.round(headerHeight, 1e2)+Math.round(pos.top, 1e2)-Math.round(marginTop, 1e2);
    };
    SUPER.pdf_determine_belong_to = function(nodes, el, result, args){
        if(el.parentNode.closest('[data-belongs-to-pages]')){
            if(JSON.parse(el.parentNode.closest('[data-belongs-to-pages]').dataset.belongsToPages).length===1){
                // Skip when parent is part of single page already
                return result;
            }
            if(JSON.parse(el.parentNode.closest('[data-belongs-to-pages]').dataset.belongsToPages).length>1){
                // Check if is element with margin
                if(JSON.parse(el.parentNode.closest('[data-belongs-to-pages]').classList.contains('super-pdf-el-with-margin'))){
                    // Skip when parent is part of single page already
                    return result;
                }
            }
        }
        //var i, innerNodes = el.querySelectorAll(':scope > .super-html-content > *');
        var i, h, innerNodes = el.children;
        for(i=0; i<innerNodes.length; i++){
            if(innerNodes[i].tagName==='STRONG' || innerNodes[i].tagName==='BR' || innerNodes[i].tagName==='SPAN') {
                continue;
            }
            // Check if has inner elements
            h = SUPER.getNodeHeight(innerNodes[i], true, true, true);
            if(h===0 && !innerNodes[i].classList.contains('super-pdf_page_break')){
                //innerNodes[i].setAttribute('data-html2canvas-ignore', 'true');
                //innerNodes[i].setAttribute('data-html2canvas-fake-ignore', 'true');
                continue;
            }
            result = SUPER.pdf_determine_belong_to(innerNodes, innerNodes[i], result, args);
            args.totalScrolled = result.totalScrolled;
        }
        h = SUPER.getNodeHeight(el, true, true, true);
        if(h===0 && !el.classList.contains('super-pdf_page_break')){
            return result;
        }
        result.alreadyIncreasedCurrentPage = false;
        if(args.allowIncreasePage===el){
            result.alreadyIncreasedCurrentPage = true;
            //result.currentPage++;
            // tmp ? result.currentPage = 1;
        }
        //if(el.classList.contains('super-html') && result.belongsTo.length>0){
            //return result;
        //}
        if(el.classList.contains('super-pdf_page_break')){
            var pageBreakAlreadyOnNextPage = 0;
            // Do not resize, but set new orienation
            if(el.classList.contains('pdf-orientation-portrait')) args.orientation = 'portrait';
            if(el.classList.contains('pdf-orientation-landscape')) args.orientation = 'landscape';
            if(el.classList.contains('pdf-orientation-default')) args.orientation = args.pdfSettings.orientation;
            if(result.currentPageHeight===0){
                // For current page
                args.pageOrientationChanges[result.currentPage] = args.orientation;
            }else{
                if(result.currentPageHeight<args.scrollAmount){
                    // For next page
                    args.pageOrientationChanges[result.currentPage+1] = args.orientation;
                }else{
                    // Is already on next page...
                    var pageBreakAlreadyOnNextPage = result.currentPage+1;
                    args.pageOrientationChanges[result.currentPage+1] = args.orientation;
                    args.pageOrientationChanges[result.currentPage+2] = args.orientation;
                }
            }
        }
        args.orientation = args.pageOrientationChanges[result.currentPage];
        if(args.orientation==='portrait'){
            args.pageWidth = args.pageWidthPortrait;
            args.pageHeight = args.pageHeightPortrait;
        }
        if(args.orientation==='landscape'){
            args.pageWidth = args.pageWidthLandscape;
            args.pageHeight = args.pageHeightLandscape;
        }
        args.pageWidthInPixels = args.pageWidth / args.unitRatio;
        args.pageHeightInPixels = args.pageHeight / args.unitRatio;
        args.pdfPageContainer.style.width = (args.pageWidthInPixels*2)+'px';
        args.pdfPageContainer.style.height = (args.pageHeightInPixels*2)+'px';
        args.pdfPageContainer.style.maxHeight = (args.pageHeightInPixels*2)+'px';
        var headerFooterHeight = SUPER.pdf_get_header_height(args)+SUPER.pdf_get_footer_height(args);
        args.scrollAmount = (args.pageHeightInPixels*2)-headerFooterHeight;
        args.scrollAmount = SUPER.round(args.scrollAmount, 2);
        args.pdfPageContainer.querySelector('.super-pdf-body').style.height = args.scrollAmount+'px';
        args.pdfPageContainer.querySelector('.super-pdf-body').style.maxHeight = args.scrollAmount+'px';
        var changeOrientation = false;
        var top = SUPER.getNodeTop(el, args);
        var belongsTo = [];
        var stop = false;
        var page = result.currentPage;
        var counter = 1;
        args.recheckChildrenAgain = false;
        while(!stop){
            var tmpTop = SUPER.round(top, 2);
            var tmpScrollAmount = SUPER.round(args.scrollAmount, 2);
            var elementTop = SUPER.round(top,2);
            var elementBottom = elementTop+h;
            // element 1 = 0 to 1112
            if(elementTop<tmpScrollAmount*counter){
                // part of this page
                var newPage = counter-1+result.currentPage;
                if(result.belongsTo.indexOf(newPage)===-1) {
                    result.belongsTo.push(newPage);
                    if(result.totalPages<newPage){
                        result.totalPages = newPage;
                    }
                }
                if(belongsTo.indexOf(newPage)===-1) {
                    belongsTo.push(newPage);
                    if(result.totalPages<newPage){
                        result.totalPages = newPage;
                    }
                }
                if(elementBottom<tmpScrollAmount*counter){
                    // part of this page, but not the next
                    stop = true;
                    continue;
                }
                // also part of next
                counter++;
                continue;
            }
            counter++;

            // tmp // page 1 = 0 to 1111.68
            // tmp // page 2 = 1111.68 to 2222.68
            // tmp // page 3 = 2222.68 to 3333.68
            // tmp // page 4 = 3333.68 to 4444.68

            // tmp // 0*1000 = 0
            // tmp // 1*1000 = 1000
            // tmp // ---------------
            // tmp // 1*1000 = 1000
            // tmp // 2*2000 = 2000

            // tmp // Check if only belongs to this page
            // tmp if( ((counter-1)*tmpScrollAmount <= tmpTop) && (counter*tmpScrollAmount>tmpTop+h) ) {
            // tmp     // Only belongs to this page
            // tmp     if(result.belongsTo.indexOf(counter)===-1) {
            // tmp         result.belongsTo.push(counter);
            // tmp         if(result.totalPages<counter){
            // tmp             result.totalPages = counter;
            // tmp         }
            // tmp     }
            // tmp     if(belongsTo.indexOf(counter)===-1) {
            // tmp         belongsTo.push(counter);
            // tmp         if(result.totalPages<counter){
            // tmp             result.totalPages = counter;
            // tmp         }
            // tmp     }
            // tmp     stop = true;
            // tmp     continue;
            // tmp }
            // tmp // Check if belongs to this page and the next page(s)
            // tmp if( ((counter-1)*tmpScrollAmount <= tmpTop) && (counter*tmpScrollAmount<=tmpTop+h) ) {
            // tmp     // Only belongs to this page
            // tmp     if(result.belongsTo.indexOf(counter)===-1) {
            // tmp         result.belongsTo.push(counter);
            // tmp         if(result.totalPages<counter){
            // tmp             result.totalPages = counter;
            // tmp         }
            // tmp     }
            // tmp     if(belongsTo.indexOf(counter)===-1) {
            // tmp         belongsTo.push(counter);
            // tmp         if(result.totalPages<counter){
            // tmp             result.totalPages = counter;
            // tmp         }
            // tmp     }
            // tmp }
            // tmp if(tmpTop+h<counter*tmpScrollAmount){
            // tmp     stop = true;
            // tmp     continue;
            // tmp }
            // tmp counter++;

            // tmp if(tmpTop >= tmpScrollAmount*counter){
            // tmp     page++;
            // tmp     counter++;
            // tmp     continue;
            // tmp }
            // tmp if(tmpScrollAmount*counter >= tmpTop && tmpScrollAmount*counter <= tmpTop+h){
            // tmp     // Belongs to this page
            // tmp     if(result.belongsTo.indexOf(page)===-1) {
            // tmp         result.belongsTo.push(page);
            // tmp         if(result.totalPages<page){
            // tmp             result.totalPages = page;
            // tmp         }
            // tmp     }
            // tmp     if(belongsTo.indexOf(page)===-1) {
            // tmp         belongsTo.push(page);
            // tmp         if(result.totalPages<page){
            // tmp             result.totalPages = page;
            // tmp         }
            // tmp     }
            // tmp     // Only belongs to this page?
            // tmp     if(tmpTop+h<=tmpScrollAmount*counter){
            // tmp         stop = true;
            // tmp         continue;
            // tmp     }
            // tmp }else{
            // tmp     if(tmpTop+h<=tmpScrollAmount*counter){
            // tmp         // Belongs to this page
            // tmp         if(result.belongsTo.indexOf(page)===-1) {
            // tmp             result.belongsTo.push(page);
            // tmp             if(result.totalPages<page){
            // tmp                 result.totalPages = page;
            // tmp             }
            // tmp         }
            // tmp         if(belongsTo.indexOf(page)===-1) {
            // tmp             belongsTo.push(page);
            // tmp             if(result.totalPages<page){
            // tmp                 result.totalPages = page;
            // tmp             }

            // tmp         }
            // tmp         // Also part of next page, apply top margin to put the element as a whole on to the next page
            // tmp         // Only if the height of the element is 40% of the scrollAmount
            // tmp         if(!args.pdfSettings.smartBreak) args.pdfSettings.smartBreak = 40;
            // tmp         args.pdfSettings.smartPageBreaksMaxHeight = (args.pdfSettings.smartPageBreaksMaxHeight ?  args.pdfSettings.smartPageBreaksMaxHeight : Number(args.pdfSettings.smartBreak)); // defulat to 50% of page height
            // tmp         if(belongsTo.length>1 && h<=(tmpScrollAmount/100)*args.pdfSettings.smartPageBreaksMaxHeight){
            // tmp             // If already has margin
            // tmp             if(el.querySelector('.super-pdf-el-with-margin')){
            // tmp                 stop = true;
            // tmp                 continue;
            // tmp             }
            // tmp             // In some cases we want to apply the top margin to the parent element.
            // tmp             // For instance, for the slider, dropdown 
            // tmp             // But not for radio/checkbox items, with the exception for `super-item` class
            // tmp             var allowMargin = true;
            // tmp             var disAllowed = ['li', 'toggle', 'item', 'text', 'textarea', 'dropdown', 'time', 'date', 'password', 'currency', 'calculator', 'rating', 'color', 'slider', 'signature', 'quantity'];
            // tmp             for(var x=0; x<disAllowed.length; x++){
            // tmp                 if(el.parentNode.closest('.super-'+disAllowed[x])){
            // tmp                     if(!el.classList.contains('super-'+disAllowed[x])) {
            // tmp                         allowMargin = false;
            // tmp                     }else{
            // tmp                         // Exceptions
            // tmp                         if(el.className==='super-rating'){
            // tmp                             allowMargin = false;
            // tmp                         }
            // tmp                     }
            // tmp                 }
            // tmp             }
            // tmp             if(allowMargin===false){
            // tmp                 stop = true;
            // tmp                 continue;
            // tmp             }
            // tmp             // Difference
            // tmp             var diff = (tmpScrollAmount*(counter-1))-(tmpTop);
            // tmp             var computedStyle = getComputedStyle(el);
            // tmp             el.dataset.oldMarginTop = computedStyle.marginTop;
            // tmp             var marginTop = parseFloat(computedStyle.marginTop) + diff;
            // tmp             // If we have adaptive placeholders, then apply an extra margin
            // tmp             if(el.querySelector('.super-adaptive-placeholder > span')){
            // tmp                 // Only apply when field is filled out e.g. has class `super-filled`
            // tmp                 if(el.classList.contains('super-filled')){
            // tmp                     h = SUPER.getNodeHeight(el.querySelector('.super-adaptive-placeholder > span'), true, false, true); // Exlude margine due to -8px top margin on adaptive placeholder
            // tmp                     marginTop = marginTop + (h/2);
            // tmp                 }
            // tmp             }
            // tmp             el.dataset.newMarginTop = marginTop;
            // tmp             el.style.marginTop = marginTop+'px';
            // tmp             el.classList.add('super-pdf-el-with-margin');
            // tmp             if(el.classList.contains('super-pdf-text-node')){
            // tmp                 // Special ocasion for PDF text
            // tmp                 el.style.display = 'inline-block';
            // tmp             }
            // tmp             // Recheck children again?
            // tmp             args.recheckChildrenAgain = true;
            // tmp             stop = true;
            // tmp             continue;
            // tmp         }
            // tmp     }
            // tmp     stop = true;
            // tmp }
            // tmp page++;
            // tmp counter++;
        }


        // Apply top margin to put the element as a whole on to the next page
        // Only if the height of the element is 95% of the scrollAmount
        if(belongsTo.length>1 && !el.querySelector('.super-pdf-el-with-margin') && !el.classList.contains('super-pdf-el-with-margin')){
            if(!args.pdfSettings.smartBreak) args.pdfSettings.smartBreak = 95;
            args.pdfSettings.smartPageBreaksMaxHeight = (args.pdfSettings.smartPageBreaksMaxHeight ?  args.pdfSettings.smartPageBreaksMaxHeight : Number(args.pdfSettings.smartBreak)); // default to 95% of page height
            if(h<=(tmpScrollAmount/100)*args.pdfSettings.smartPageBreaksMaxHeight){
                // In some cases we want to apply the top margin to the parent element.
                // For instance, for the slider, dropdown 
                // But not for radio/checkbox items, with the exception for `super-item` class
                var allowMargin = true;
                var disAllowed = ['li', 'toggle', 'item', 'text', 'textarea', 'dropdown', 'time', 'date', 'password', 'currency', 'calculator', 'rating', 'color', 'slider', 'signature', 'quantity'];
                for(var x=0; x<disAllowed.length; x++){
                    if(el.parentNode.closest('.super-'+disAllowed[x])){
                        if(!el.classList.contains('super-'+disAllowed[x])) {
                            allowMargin = false;
                        }else{
                            // Exceptions
                            if(el.className==='super-rating'){
                                allowMargin = false;
                            }
                        }
                    }
                }
                if(allowMargin===true){
                    // Difference
                    //var diff = (tmpScrollAmount*(belongsTo[0]))-(tmpTop);
                    var diff = (tmpScrollAmount*(belongsTo[0]))-(tmpTop)-args.totalScrolled;
                    var computedStyle = getComputedStyle(el);
                    el.dataset.oldMarginTop = computedStyle.marginTop;
                    var marginTop = parseFloat(computedStyle.marginTop) + diff;
                    // If we have adaptive placeholders, then apply an extra margin
                    if(el.querySelector('.super-adaptive-placeholder > span')){
                        // Only apply when field is filled out e.g. has class `super-filled`
                        if(el.classList.contains('super-filled')){
                            h = SUPER.getNodeHeight(el.querySelector('.super-adaptive-placeholder > span'), true, false, true); // Exlude margine due to -8px top margin on adaptive placeholder
                            marginTop = marginTop + (h/2);
                        }
                    }
                    el.dataset.newMarginTop = marginTop;
                    el.style.marginTop = marginTop+'px';
                    el.classList.add('super-pdf-el-with-margin');
                    if(el.classList.contains('super-pdf-text-node')){
                        // Special ocasion for PDF text
                        el.style.display = 'inline-block';
                    }
                    // Recheck children again?
                    args.recheckChildrenAgain = true;
                }
            }
        }

        if(args.recheckChildrenAgain===true){
            // Reset ignore attributes
            nodes = el.querySelectorAll('[data-belongs-to-pages], [data-html2canvas-ignore="true"], .super-pdf-el-with-margin');
            for(i=0; i<nodes.length; i++){
                nodes[i].removeAttribute('data-html2canvas-ignore');
                nodes[i].classList.remove('super-pdf-el-with-margin');
                nodes[i].style.marginTop = null
                delete nodes[i].dataset.belongsToPages;
            }
            // Reset currentPage offsets
            nodes = el.querySelectorAll('[data-offset-top]');
            for(i=0; i<nodes.length; i++){
                nodes[i].style.marginTop = null
                delete nodes[i].dataset.offsetTop;
            }
            // Reset any PDF page break heights
            nodes = el.querySelectorAll('.super-pdf_page_break');
            for(i=0; i<nodes.length; i++){
                nodes[i].style.height = '0px';
            }
            for(i=0; i<innerNodes.length; i++){
                if(innerNodes[i].tagName==='STRONG' || innerNodes[i].tagName==='BR' || innerNodes[i].tagName==='SPAN') {
                    continue;
                }
                h = SUPER.getNodeHeight(innerNodes[i], true, true, true);
                if(h===0 && !innerNodes[i].classList.contains('super-pdf_page_break')){
                    continue;
                }
                result = SUPER.pdf_determine_belong_to(innerNodes, innerNodes[i], result, args);
                args.totalScrolled = result.totalScrolled;
            }
        }
        if(args.allowIncreasePage===el && belongsTo.length>1){
            // When it belongs to both current and next page, then we must add an offset
            // When going to the next page we will apply the offset to move the element up relatively to the current page
            if(args.scrollAmount-top > 0){
                el.dataset.offsetTop = SUPER.round(args.scrollAmount-top, 2);
            }
        }else{
            if(el.classList.contains('super-pdf_page_break')){
                var top = SUPER.getNodeTop(el, args);
                h = ((belongsTo[0]*args.scrollAmount)-(top+result.totalScrolled));
                result.currentPageHeight = args.scrollAmount;
                el.style.height = h+'px';
                changeOrientation = true;
                if(pageBreakAlreadyOnNextPage>0){
                    changeOrientation = false;
                }
            }else{
                if(result.currentPageHeight < top+h){
                    result.currentPageHeight = top+h;
                }
            }
            if(belongsTo[0] && belongsTo[0]===pageBreakAlreadyOnNextPage){
                if(el.classList.contains('super-pdf_page_break')){
                    var top = SUPER.getNodeTop(el, args);
                    h = ((belongsTo[0]*args.scrollAmount)-(top+result.totalScrolled));
                    result.currentPageHeight = args.scrollAmount;
                    el.style.height = h+'px';
                    changeOrientation = true;
                    if(pageBreakAlreadyOnNextPage>0){
                        changeOrientation = false;
                    }
                }else{
                    if(result.currentPageHeight < top+h){
                        result.currentPageHeight = top+h;
                    }
                }
            }
        }

        // Find all inner belong to, and make sure that we add any missing pages
        for(i=0; i<innerNodes.length; i++){
            if(innerNodes[i].tagName==='STRONG' || innerNodes[i].tagName==='BR' || innerNodes[i].tagName==='SPAN') {
                continue;
            }
            if(!innerNodes[i].dataset.belongsToPages) continue;
            var innerBelongsTo = JSON.parse(innerNodes[i].dataset.belongsToPages);
            for(var x=0; x<innerBelongsTo.length; x++){
                if(belongsTo.indexOf(innerBelongsTo[x])===-1){
                    belongsTo.push(innerBelongsTo[x]);
                }
            }
        }
        if(belongsTo.length>0){
            el.dataset.belongsToPages = JSON.stringify(belongsTo);
        }

        if(changeOrientation){
            // Update page width and height based on current page orientation
            args.orientation = args.pageOrientationChanges[result.currentPage+1];
            //if(args.orientation==='unchanged'){
            //    if(args.pageOrientationChanges[args.currentPage-1]){
            //        // Use previous page orientation
            //        args.pageOrientationChanges[args.currentPage] = args.pageOrientationChanges[args.currentPage-1];
            //    }else{
            //        // Use default
            //        args.pageOrientationChanges[args.currentPage] = args.pdfSettings.orientation;
            //    }
            //    args.orientation = args.pageOrientationChanges[args.currentPage];
            //}
            if(args.orientation==='portrait'){
                args.pageWidth = args.pageWidthPortrait;
                args.pageHeight = args.pageHeightPortrait;
            }
            if(args.orientation==='landscape'){
                args.pageWidth = args.pageWidthLandscape;
                args.pageHeight = args.pageHeightLandscape;
            }
            args.pageWidthInPixels = args.pageWidth / args.unitRatio;
            args.pageHeightInPixels = args.pageHeight / args.unitRatio;
            args.pdfPageContainer.style.width = (args.pageWidthInPixels*2)+'px';
            args.pdfPageContainer.style.height = (args.pageHeightInPixels*2)+'px';
            args.pdfPageContainer.style.maxHeight = (args.pageHeightInPixels*2)+'px';
            var headerFooterHeight = SUPER.pdf_get_header_height(args)+SUPER.pdf_get_footer_height(args);
            args.scrollAmount = (args.pageHeightInPixels*2)-headerFooterHeight;
            args.scrollAmount = SUPER.round(args.scrollAmount, 2);
            args.pdfPageContainer.querySelector('.super-pdf-body').style.height = args.scrollAmount+'px';
            args.pdfPageContainer.querySelector('.super-pdf-body').style.maxHeight = args.scrollAmount+'px';

            result.currentPageHeight=0;
            result.currentPage++;
            // don't do this... if(result.belongsTo.indexOf(result.currentPage)===-1) {
            // don't do this...     // tmp result.belongsTo.push(result.currentPage);
            // don't do this... }
            SUPER.pdf_hide_previous_page_elements(args, result);
            //result.currentPage = result.totalPages+1;
            // Once we go to the next page, increase total "totalScrolled" amount
            result.totalScrolled = result.totalScrolled + args.scrollAmount;
            return result;
        }

        // tmp disabled ----> result.belongsTo = belongsTo;
        // If we exceed scroll amount, we are on the next page
        if(args.allowIncreasePage===el && result.currentPageHeight>=args.scrollAmount){
        // tmp disabled ----> if(result.currentPageHeight>=args.scrollAmount){
            result.currentPageHeight=0;
            //tmpresult.currentPage++;
            if(result.alreadyIncreasedCurrentPage){
                result.currentPage++;
            }else{
                result.currentPage = result.totalPages+1;
            } 
            SUPER.pdf_hide_previous_page_elements(args, result);
            //result.currentPage = result.totalPages+1;
            // Once we go to the next page, increase total "totalScrolled" amount
            result.totalScrolled = result.totalScrolled + args.scrollAmount;
        }
        return result;
    };
    SUPER.pdf_hide_previous_page_elements = function(args, result){
        // Now hide all elements that belong to this page or the previous and not the next
        var i, nodes = args.form0.querySelectorAll('[data-belongs-to-pages]');
        for(i=0; i<nodes.length; i++){
            var belongsTo = JSON.parse(nodes[i].dataset.belongsToPages);
            if((belongsTo.indexOf(result.currentPage-1)!==-1) && (belongsTo.indexOf(result.currentPage)===-1)){
                nodes[i].classList.add('super-hide-from-current-page');
            }
        }
        nodes = document.body.querySelectorAll('.super-pdf-el-with-margin');
        for(i=0; i<nodes.length; i++){
            belongsTo = JSON.parse(nodes[i].dataset.belongsToPages);
            if(belongsTo[1]>=result.currentPage){
                nodes[i].style.marginTop = null
                if(nodes[i].dataset.oldMarginTop){
                    nodes[i].style.marginTop = nodes[i].dataset.oldMarginTop;
                }
            }
        }
    }

    SUPER._pdf_generator_done_callback = function(args){
        // Reset everything to how it was
        SUPER.pdf_generator_reset(args.form0);
        // Attach as file to form data
        var datauristring = args._pdf.output('datauristring', {
            filename: args.pdfSettings.filename
        });
        var exclude = 0;
        if(args.pdfSettings.adminEmail!=='true' && args.pdfSettings.confirmationEmail!=='true'){
            exclude = 2; // Exclude from both emails
        }else{
            if(args.pdfSettings.adminEmail==='true' && args.pdfSettings.confirmationEmail==='true'){
                exclude = 0; // Do not exclude
            }else{
                if(args.pdfSettings.adminEmail==='true'){
                    exclude = 1; // Exclude from confirmation email only
                }
                if(args.pdfSettings.confirmationEmail==='true'){
                    exclude = 3; // Exclude from admin email only
                }
            }
        }
        args.data._generated_pdf_file = {
            files: [{
                label: args.pdfSettings.emailLabel,
                name: args.pdfSettings.filename,
                datauristring: datauristring,
                value: args.pdfSettings.filename
            }],
            label: args.pdfSettings.emailLabel,
            type: 'files',
            exclude: exclude
        };
        // We PDF has been generated, continue with form submission
        args._save_data_callback(args);
    };

    SUPER.recheckCanvasImages = function(args, callback){
        var node = args.pdfPageContainer.querySelector('.super-int-phone_selected-flag .super-int-phone_flag:not(.super-canvas-initialized)');
        if(!node || node.closest('[data-pdfoption="exclude"]') || node.closest('[data-html2cvanas-ignore="true"]')){
            callback();
        }else{
            node.classList.add('super-canvas-initialized');
            html2canvas(node, {
                scrollX: 0, // Important, do not remove
                scrollY: 0,  // -window.scrollY, // Important, do not remove
                scale: args.pdfSettings.renderScale, // The scale to use for rendering (higher means better quality, but larger file size)
                useCORS: true,
                allowTaint: false,
                backgroundColor: '#ffffff'
            }).then(function(canvas) {
                var imgData = canvas.toDataURL("image/jpeg", 1.0);
                node.dataset.imgData = imgData;
                SUPER.recheckCanvasImages(args, callback);
            });
        }
    }

    SUPER.pdf_generator_prepare = function(args, callback){
        args.debugger = false;
        var form = args.form0;

        // Define PDF tags
        SUPER.pdf_tags = {
            pdf_page: '{pdf_page}',
            pdf_total_pages: '{pdf_total_pages}'
        };

        // Must hide scrollbar
        // tmp no longer needed? document.documentElement.classList.add('super-hide-scrollbar');
        form.classList.add('super-generating-pdf');
        
        // Normalize font styles
        var normalizeFontStylesNodesClasses = 'h1, h2, h3, h4, h5, h6, .super-label, .super-description, .super-heading-title, .super-heading-description, .super-text .super-shortcode-field, .super-textarea .super-shortcode-field, .super-filled .super-adaptive-placeholder > span, .super-dropdown.super-filled .super-item.super-placeholder, .super-checkbox .super-item > div, .super-radio .super-item > div, .super-quantity .super-shortcode-field, .super-toggle-switch, .super-currency .super-shortcode-field, .super-date .super-shortcode-field, .super-slider .amount, .super-calculator-currency-wrapper, .super-calculator-label, .super-fileupload-name, .super-fileupload-button-text, .super-toggle-prefix-label > span, .super-toggle-suffix-label > span, .super-html-title, .super-html-subtitle, .super-html-content',
        normalizeFontStylesNodesClassesExploded = normalizeFontStylesNodesClasses.split(','),
        newNormalizeFontStylesNodesClasses = '',
        hidePseudoElements = '';
        for(var i=0; i<normalizeFontStylesNodesClassesExploded.length; i++){
            if(i>0) {
                newNormalizeFontStylesNodesClasses += ', ';
                hidePseudoElements += ', ';
            }
            newNormalizeFontStylesNodesClasses += '.super-pdf-page-container '+normalizeFontStylesNodesClassesExploded[i];
            hidePseudoElements += '.super-pdf-page-container '+normalizeFontStylesNodesClassesExploded[i]+':before,';
            hidePseudoElements += '.super-pdf-page-container '+normalizeFontStylesNodesClassesExploded[i]+':after';
        }

        // Must hide elements
        var css = '';
        // tmp no longer needed? var css = '.super-hide-scrollbar {overflow: -moz-hidden-unscrollable!important; overflow: hidden!important;}';
        // Toggle ignore elements (hide / show)
        // tmp disabled not sure? css += '.super-pdf-page-container:not(.super-pdf-toggle-ignore-items) .super-pdf-body .super-generating-pdf .super-pdf-ignored-el-placeholder {';
        // tmp disabled not sure?     css += 'display: none !important;';
        // tmp disabled not sure?     css += 'opacity: 0 !important;';
        // tmp disabled not sure? css += '}';
        css += '.super-pdf-page-container.super-pdf-toggle-ignore-items .super-pdf-body .super-generating-pdf [data-html2canvas-ignore="true"],';
        css += '.super-pdf-page-container.super-pdf-toggle-ignore-items .super-pdf-body .super-generating-pdf [data-pdfoption=include][data-html2canvas-ignore="true"] {';
            css += 'display: none !important;';
            css += 'opacity: 0 !important;';
        css += '}';
        // Heading title and description should not have display:flex;
        css += '.super-pdf-page-container .super-heading-title, .super-pdf-page-container .super-heading-description {display:block!important;float:left;width:100%;}';
        // Required to render pseudo elements (html2canvas code was altered for this)
        css += '.super-pdf-page-container.super-pdf-clone .super-form *:before,';
        css += '.super-pdf-page-container.super-pdf-clone .super-form *:after {display:none!important;}';
        // Set font weight, line height and letter spacing to normal sizes to avoid inconsistencies between PDF and rendered text in PDF
        if(!args.pdfSettings.imageQuality) args.pdfSettings.imageQuality = 'FAST'; //'FAST' // compression 'NONE', 'FAST', 'MEDIUM' or 'SLOW'
        if(args.pdfSettings.native==='true'){
            //css += newNormalizeFontStylesNodesClasses + '{font-family:"Helvetica",  "Arial", sans-serif!important;line-height:1.2!important;letter-spacing:0!important;}';
            //css += "@font-face {font-family:'SF-Unicode';src:url('"+(super_common_i18n.fonts.link)+".woff') format('woff');}";
            //css += "@font-face {font-family:'SF-Unicode';src:url('"+(super_common_i18n.fonts.link)+".woff2') format('woff2');}";
            css += newNormalizeFontStylesNodesClasses + '{font-family:"SF-Unicode", "Arial", sans-serif!important;line-height:1.2!important;letter-spacing:0!important;}';
        }
        if(!args.pdfSettings.normalizeFonts) args.pdfSettings.normalizeFonts = 'true';
        if(args.pdfSettings.normalizeFonts==='true'){
            if(args.pdfSettings.native!=='true'){
                css += newNormalizeFontStylesNodesClasses + '{font-family:"Helvetica", "Arial", sans-serif!important;font-weight:normal!important;line-height:1.2!important;letter-spacing:0!important;}';
            }
            css += newNormalizeFontStylesNodesClasses + '{max-height:5000em!important;text-size-adjust:none!important;-webkit-text-size-adjust:none!important;-moz-text-size-adjust:none!important;-ms-text-size-adjust:none!important;}';
            css += hidePseudoElements + '{display:none!important;height:0px!important;max-height:0px!important;}';
        }
        // Remove any form padding
        css += '.super-pdf-page-container .super-form.super-adaptive { padding-top: 0px!important; }';
        css += '.super-pdf-page-container .super-i18n-switcher { display: none!important; }';
        // Tmp text field placeholder to fix vertical alignment for text inputs
        css += '.super-pdf-page-container .super-pdf-tmp-text-field-placeholder { display:flex; align-items:center; }';
        //css += '.super-pdf-page-container .super-textarea .super-pdf-tmp-text-field-placeholder { display:flex; align-items:flex-start; }';
        css += '.super-pdf-page-container .super-quantity .super-pdf-tmp-text-field-placeholder { display:flex; justify-content:center; }';

        css += '.super-pdf-page-container .super-textarea .super-pdf-tmp-text-field-placeholder { display:block; }';
        if(args.pdfSettings.native==='true'){
            css += '.super-pdf-page-container .super-textarea .super-pdf-tmp-text-field-placeholder { display:block; }';
        }
        css += '.super-pdf-page-container .super-pdf-tmp-replaced { display:none!important; }';
        // @IMPORTANT NEEDED? css += '.super-pdf-page-container .super-html-content.super-nl2br { white-space:pre-line; word-break:break-word; }';
        // Required styles for correct element margin tops
        css += '.super-pdf-page-container .super-html-content ol {float:left;width:100%;}';
        css += '.super-pdf-page-container .super-html-content ul {float:left;width:100%;}';
        css += '.super-pdf-page-container li::marker { content: "" !important; display: none !important; visibility: hidden !important; }';
        css += '.super-pdf-page-container ul .super-li-marker { width: 20px; height: 100%; transform: translateY(-2px); margin-left: -20px !important; display: inline-flex; justify-content: center; align-items: center; }';
        css += '.super-pdf-page-container ul .super-li-marker:after { content: ""; width: 4px; height: 4px; background-color: black; border-radius: 100%; }';
        css += '.super-pdf-page-container ul .super-li-marker.super-li-marker-style-circle:after { width: 3px; height: 3px; background: none; border: 1px solid black; }';
        css += '.super-pdf-page-container ul .super-li-marker.super-li-marker-style-square:after { border-radius: 0; }';
        // Hide elements that do not belong to the current page

        // To resolve the "start" attribute on "ol" element
        css += '.super-pdf-page-container .super-hide-from-current-page { height:0px!important;overflow:hidden!important;margin:0!important;padding:0!important; }';
        //css += '.super-pdf-page-container .super-hide-from-current-page { opacity:0!important;height:0px!important;overflow:hidden!important;margin:0!important;padding:0!important; }';

        css += '.super-pdf-page-container .super-hide-from-current-page > * { display:none; }';
        //css += '.super-pdf-page-container .super-html-content { display:flex!important;flex-direction:column!important; }';
        //css += '.super-pdf-page-container .super-pdf-body .super-shortcode[data-pdfoption=include].super-hide-from-current-page { display:none!important; }';
        //css += '.super-pdf-page-container .super-pdf-body [data-pdfoption=include].super-hide-from-current-page { display:none!important; }';
        css += '.super-pdf-page-container .super-pdf_page_break { display:inline-block!important; }';
        css += '.super-pdf-page-container .super-pdf_page_break.super-hide-from-current-page { display:none!important; }';
        // Hide none essential elements/styles from the PDF output
        css += '.super-generating-pdf:not(.super-pdf-placeholder) *,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) *:after,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-accordion-header:after,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-accordion-header:before { transition: initial!important; }';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-accordion-header:before,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-accordion-header:after,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-form-button,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-multipart-progress,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-multipart-steps,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-prev-multipart,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-next-multipart,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-tabs-menu,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-duplicate-actions,';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-signature-clear { display: none!important; }';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-accordion-header { border: 1px solid #d2d2d2; }';
        css += '.super-generating-pdf:not(.super-pdf-placeholder) .super-accordion-header { border: 1px solid #d2d2d2; }';
        css += '.super-pdf-header, .super-pdf-body, .super-pdf-footer { display: block; float: left; width: 100%; overflow: hidden; box-sizing: border-box; }';
        // Header margins
        var headerMarginBottom = parseFloat(args.pdfSettings.margins.header.bottom)+parseFloat(args.pdfSettings.margins.body.top);
        css += '.super-pdf-header {padding: '+args.pdfSettings.margins.header.top+args.pdfSettings.unit+' '+args.pdfSettings.margins.header.right+args.pdfSettings.unit+' '+headerMarginBottom+args.pdfSettings.unit+' '+args.pdfSettings.margins.header.left+args.pdfSettings.unit+' }';
        css += '.super-pdf-header .super-form, .super-pdf-header .super-form form {padding:0!important;margin:0!important;float:left!important;width:100%!important;}';
        // Body margins
        css += '.super-pdf-body {padding: 0'+args.pdfSettings.unit+' '+args.pdfSettings.margins.body.right+args.pdfSettings.unit+' 0'+args.pdfSettings.unit+' '+args.pdfSettings.margins.body.left+args.pdfSettings.unit+';}';
        // Footer margins
        var footerMarginTop = parseFloat(args.pdfSettings.margins.footer.top)+parseFloat(args.pdfSettings.margins.body.bottom);
        css += '.super-pdf-footer {padding: '+footerMarginTop+args.pdfSettings.unit+' '+args.pdfSettings.margins.footer.right+args.pdfSettings.unit+' '+args.pdfSettings.margins.footer.bottom+args.pdfSettings.unit+' '+args.pdfSettings.margins.footer.left+args.pdfSettings.unit+'; }';
        css += '.super-pdf-footer .super-form, .super-pdf-footer .super-form form {padding:0!important;margin:0!important;float:left!important;width:100%!important;}';
        css += '.super-pdf-text-node {display:inline-block!important;white-space:pre!important;}';

        var head = document.head || document.getElementsByTagName('head')[0],
        style = document.createElement('style');
        style.id = 'super-generating-pdf';
        head.appendChild(style);
        style.type = 'text/css';
        if (style.styleSheet){
            // This is required for IE8 and below.
            style.styleSheet.cssText = css;
        } else {
            style.appendChild(document.createTextNode(css));
        }
        // window.devicePixelRatio
        // might need this in a future version $('.super-msg.super-info').html('devicePixelRatio: ' + window.devicePixelRatio);
        // might need this in a future version // Prevent browser zoom
        // might need this in a future version var currentViewport = document.querySelector("meta[name=viewport]");
        // might need this in a future version SUPER.currentViewportContentValue = '';
        // might need this in a future version if(currentViewport){
        // might need this in a future version     SUPER.currentViewportContentValue = currentViewport.getAttribute('content');
        // might need this in a future version     currentViewport.setAttribute('content', 'width=device-width, user-scalable=no' );
        // might need this in a future version }else{
        // might need this in a future version     // Create new viewport
        // might need this in a future version     var newViewport = document.createElement('meta');
        // might need this in a future version     newViewport.name = 'viewport';
        // might need this in a future version     newViewport.setAttribute('content', 'width=device-width, user-scalable=no' );
        // might need this in a future version     head.appendChild(newViewport);
        // might need this in a future version }

        var formId = form.querySelector('input[name="hidden_form_id"]').value;

        // Add form placeholder (fake form)
        var placeholder = form.cloneNode(true);
        placeholder.id = placeholder.id+'-placeholder';
        placeholder.classList.add('super-pdf-placeholder');
        args.placeholder = placeholder;
        form.parentNode.insertBefore(placeholder, form.nextSibling);

        // Remove responsiveness classes, so that mobile and desktop PDF look identical
        var clonedForm = form.cloneNode(true);
        var newClassName = '';
        //var oldClassName = clonedForm.className;
        for(var i=0; i<clonedForm.classList.length; i++){
            // e.g: super-first-responsiveness, super-window-first-responsiveness
            if(clonedForm.classList[i].indexOf('responsiveness')===-1){
                newClassName += clonedForm.classList[i]+' ';
            }
        }
        // Update classname
        clonedForm.className = newClassName;
        args.form0.className = newClassName;
        var headerClone = clonedForm.cloneNode(true);
        var footerClone = clonedForm.cloneNode(true);
        
        // PDF page container
        var pdfPageContainer = document.createElement('div');
        args.pdfPageContainer = pdfPageContainer;
        var html = '<div class="super-pdf-header">';
            // Put any header(s) here
        html += '</div>';
        html += '<div class="super-pdf-body">';
            // Put form here
        html += '</div>';
        html += '<div class="super-pdf-footer">';
            // Put any footer(s) here
        html += '</div>';
        pdfPageContainer.innerHTML = html;
        pdfPageContainer.classList.add('super-pdf-page-container');
        document.body.appendChild(pdfPageContainer);
        pdfPageContainer.style.width = (args.pageWidthInPixels*2)+'px';
        pdfPageContainer.style.zIndex = "-999999999";
        pdfPageContainer.style.left = "-9999px";
        pdfPageContainer.style.top = "0px";
        // ------- for debugging only: ----
        if(args.debugger===true){
            pdfPageContainer.style.zIndex = "9999999999";
            pdfPageContainer.style.left = "0px";
            pdfPageContainer.style.top = "0px";
        }
        // ------- for debugging only: ----
        pdfPageContainer.style.position = "fixed";
        pdfPageContainer.style.backgroundColor = "#ffffff";
        pdfPageContainer.style.height = (args.pageHeightInPixels*2)+'px';
        pdfPageContainer.style.maxHeight = (args.pageHeightInPixels*2)+'px';
        pdfPageContainer.style.overflow = "hidden";
        pdfPageContainer.querySelector('.super-pdf-body').appendChild(args.form0);
        args.pdfHeader = args.pdfPageContainer.querySelector('.super-pdf-header'),
        args.pdfFooter = args.pdfPageContainer.querySelector('.super-pdf-footer');
        args.pdfHeader.appendChild(headerClone);
        args.pdfFooter.appendChild(footerClone);

        // Put header before form
        headerClone.querySelector('form').innerHTML = '';
        var header = form.querySelector('.super-shortcode[data-pdfoption="header"]');
        if(header){
            if(header.classList.contains('super-column')){
                header = header.closest('.super-grid').cloneNode(true);
            }else{
                header = header.cloneNode(true);
            }
            header.classList.add('pdf-generated-header');
            headerClone.querySelector('form').appendChild(header);
        }

        // Put footer after form
        footerClone.querySelector('form').innerHTML = '';
        var footer = form.querySelector('.super-shortcode[data-pdfoption="footer"]');
        if(footer){
            if(footer.classList.contains('super-column')){
                footer = footer.closest('.super-grid').cloneNode(true);
            }else{
                footer = footer.cloneNode(true);
            }
            footer.classList.add('pdf-generated-footer');
            footerClone.querySelector('form').appendChild(footer);
        }

        // Resize PDF body height based on header/footer heights
        var headerFooterHeight = SUPER.pdf_get_header_height(args)+SUPER.pdf_get_footer_height(args);
        args.scrollAmount = (args.pageHeightInPixels*2)-headerFooterHeight;
        args.scrollAmount = SUPER.round(args.scrollAmount, 2);
        pdfPageContainer.querySelector('.super-pdf-body').style.height = args.scrollAmount+'px';
        pdfPageContainer.querySelector('.super-pdf-body').style.maxHeight = args.scrollAmount+'px';

        // Copy text fields and replace with dummy elements to fix line-height issues in generated PDF
        // Ignore any node that is excluded from the PDF
        nodes = pdfPageContainer.querySelectorAll('.super-text .super-shortcode-field, .super-password .super-shortcode-field, .super-textarea .super-shortcode-field, .super-quantity .super-shortcode-field, .super-date .super-shortcode-field, .super-time .super-shortcode-field, .super-currency .super-shortcode-field');
        for( i=0; i < nodes.length; i++ ) {
            if(nodes[i].closest('.super-keyword-tags')) continue;
            var newNode = document.createElement('div');
            var computedStyle = getComputedStyle(nodes[i]);
            newNode.style.paddingTop = parseFloat(computedStyle.getPropertyValue('padding-top'))+'px';
            newNode.style.paddingRight = parseFloat(computedStyle.getPropertyValue('padding-right'))+'px';
            newNode.style.paddingBottom = parseFloat(computedStyle.getPropertyValue('padding-bottom'))+'px';
            newNode.style.paddingLeft = parseFloat(computedStyle.getPropertyValue('padding-left'))+'px';
            newNode.classList.add('super-shortcode-field');
            newNode.classList.add('super-pdf-tmp-text-field-placeholder');
            if(nodes[i].closest('.super-textarea')){
                newNode.style.height = 'auto';
                newNode.innerText = nodes[i].value;
            }else{
                newNode.innerHTML = '<span class="super-pdf-text">'+nodes[i].value+'</span>';
                //newNode.innerHTML = '<span class="super-pdf-text-node">'+nodes[i].value+'</span>';
            }
            nodes[i].parentNode.insertBefore(newNode, nodes[i].nextSibling);
            // Hide/exclude origin element
            nodes[i].classList.add('super-pdf-tmp-replaced');
        }

        // Ignore any node that is excluded from the PDF
        var nodes = form.querySelector('form').querySelectorAll('.super-shortcode[data-pdfoption="exclude"]');
        for(i=0; i<nodes.length; i++){
            nodes[i].setAttribute('data-html2canvas-ignore', 'true');
        }

        // Make all mutli-parts visible
        // Make all TABs visible
        // Make all accordions visible
        nodes = form.querySelectorAll('.super-multipart,.super-tabs-content,.super-accordion-item');
        for( i=0; i < nodes.length; i++){
            if(nodes[i].classList.contains('super-active')){
                nodes[i].classList.add('super-active-origin');         
            }else{
                nodes[i].classList.add('super-active');
            }
        }

        // Normalize all font sizes
        // Example of allowed font sizes are: 10px, 12.5px, 15px, 17.5px, 20px etc. (increment with 2.5px)
        // Other font sizes creates issues within the PDF
        // We only have to loop over fields that we are going to print out
        if(!args.pdfSettings.normalizeFonts) args.pdfSettings.normalizeFonts = 'true';
        if(args.pdfSettings.normalizeFonts==='true'){
            nodes = pdfPageContainer.querySelectorAll(normalizeFontStylesNodesClasses);
            for( i=0; i < nodes.length; i++ ) {
                var el = nodes[i];
                if(el.classList.contains('super-heading-title')){
                    el = el.children[0];
                }
                if(!el) continue;
                var newFontSize = 12.5;
                try {
                    if(el.classList.contains('super-pdf-tmp-text-field-placeholder')){
                        var fontSize = parseFloat(getComputedStyle(el.previousSibling).getPropertyValue('font-size'));
                    }else{
                        var fontSize = parseFloat(getComputedStyle(el).getPropertyValue('font-size'));
                    }
                    newFontSize = 2.5 * Math.ceil(fontSize/2.5);
                }
                catch(error) {
                    console.log("Error: ", error);
                }
                if(el && el.style) {
                    el.style.fontSize = newFontSize+'px';
                }
            }
        }

        var greekLetters = ['α', 'β', 'γ', 'δ', 'ε', 'ζ', 'η', 'θ', 'ι', 'κ', 'λ', 'μ', 'ν', 'ξ', 'ο', 'π', 'ρ', 'σ', 'τ', 'υ', 'φ', 'χ', 'ψ', 'ω'];
        function convertToRoman(num, style) {
            var romanNumeralMap = {M: 1000, CM: 900, D: 500, CD: 400, C: 100, XC: 90, L: 50, XL: 40, X: 10, IX: 9, V: 5, IV: 4, I: 1};
            var romanNumeral = '';
            for (var key in romanNumeralMap) {
                while (num >= romanNumeralMap[key]) {
                    if(style==='lower'){
                        romanNumeral += key.toLowerCase();
                    }else{
                        romanNumeral += key;
                    }
                    num -= romanNumeralMap[key];
                }
            }
            return romanNumeral;
        }
        function applyMarkers(items) {
            var elCounter = 0;
            var prefixIndex = 0;
            var prefix = '';
            var start = 1;
            var spanWidth = 0;
            var baseCharCode = "a".charCodeAt(0);
            for (var i = 0; i < items.length; i++) {
                var marker = document.createElement("span");
                marker.classList.add('super-li-marker');
                marker.classList.add('super-li-marker-style-'+items[i].parentNode.style.listStyleType);
                if(items[i].parentNode.start){
                    start = Number(items[i].parentNode.start);
                    if(start>1 && i===0){
                        elCounter = start-1;
                    }
                }
                if(items[i].parentNode.style.listStyleType==='lower-alpha' || items[i].parentNode.style.listStyleType==='upper-alpha'){
                    if(26===elCounter){
                        elCounter = 0;
                        prefix = String.fromCharCode(baseCharCode + prefixIndex);
                        prefixIndex++;
                    }
                    if(items[i].parentNode.style.listStyleType==='upper-alpha'){
                        marker.innerText = (prefix+String.fromCharCode(baseCharCode + elCounter)).toUpperCase() + ". ";
                    }else{
                        marker.innerText = prefix+String.fromCharCode(baseCharCode + elCounter) + ". ";
                    }
                    items[i].insertBefore(marker, items[i].firstChild);
                    spanWidth = marker.offsetWidth;
                    marker.style.marginLeft = `-${spanWidth}px`;
                    elCounter++;
                    continue;
                }
                if(items[i].parentNode.style.listStyleType==='lower-greek' || items[i].parentNode.style.listStyleType==='upper-greek'){
                    if(greekLetters.length===elCounter){
                        elCounter = 0;
                        prefix = greekLetters[prefixIndex];
                        prefixIndex++;
                    }
                    if(items[i].parentNode.style.listStyleType==='upper-greek'){
                        marker.innerText = (prefix+greekLetters[elCounter]).toUpperCase() + ". ";
                    }else{
                        marker.innerText = prefix+greekLetters[elCounter] + ". ";
                    }
                    items[i].insertBefore(marker, items[i].firstChild);
                    spanWidth = marker.offsetWidth;
                    marker.style.marginLeft = `-${spanWidth}px`;
                    elCounter++;
                    continue;
                }
                if(items[i].parentNode.style.listStyleType==='lower-roman'){
                    marker.innerText = convertToRoman(i+1, 'lower') + ". ";
                    items[i].insertBefore(marker, items[i].firstChild);
                    spanWidth = marker.offsetWidth;
                    marker.style.marginLeft = `-${spanWidth}px`;
                    continue;
                }
                if(items[i].parentNode.style.listStyleType==='upper-roman'){
                    marker.innerText = convertToRoman(i+1, 'upper') + ". ";
                    items[i].insertBefore(marker, items[i].firstChild);
                    spanWidth = marker.offsetWidth;
                    marker.style.marginLeft = `-${spanWidth}px`;
                    continue;
                }
                // Default
                if(items[i].parentNode.tagName==='OL'){
                    marker.innerText = (elCounter+1) + ". ";
                    items[i].insertBefore(marker, items[i].firstChild);
                    spanWidth = marker.offsetWidth;
                    marker.style.marginLeft = `-${spanWidth}px`;
                    elCounter++;
                    continue;
                }
                if(items[i].parentNode.tagName==='UL'){
                    marker.innerText = "";
                    items[i].insertBefore(marker, items[i].firstChild);
                    spanWidth = marker.offsetWidth;
                    marker.style.marginLeft = `-${spanWidth}px`;
                    continue;
                }

            }
        }
        if(args.pdfSettings.native==='true'){
            nodes = document.querySelectorAll('.super-form ol, .super-form ul');
            for(i=0;i<nodes.length;i++){
                var list = nodes[i].querySelectorAll(':scope > li');
                applyMarkers(list);
            }
        }else{
            // Quick fix for issue where first line of textarea element is cut off
            pdfPageContainer.querySelectorAll('textarea').forEach(textarea => {
                const trimmed = textarea.value.trimStart();
                if (!trimmed.startsWith('\n')) {
                    textarea.value = '\n' + trimmed;
                } else {
                    textarea.value = trimmed; // remove excess leading spaces/tabs, keep existing \n
                }
            });
        }
        SUPER.recheckCanvasImages(args, function(){
            SUPER.init_super_responsive_form_fields({form: form, callback: function(formId){
                // First disable the UI on the map for nicer print of the map
                // And make map fullwidth and directions fullwidth
                for(i=0; i < SUPER.google_maps_api.allMaps[formId].length; i++){
                    SUPER.google_maps_api.allMaps[formId][i].setOptions({
                        disableDefaultUI: true
                    });
                    nodes = SUPER.google_maps_api.allMaps[formId][i]['super_el'].querySelectorAll(':scope > div');
                    for(var x=0; x < nodes.length; x++){
                        nodes[x].style.width = '100%';
                        if(nodes[x].classList.contains('super-google-map-directions')){
                            nodes[x].style.overflowY = 'initial';
                            nodes[x].style.height = 'auto';
                        }
                    }
                }
                
                // Convert height of textarea to fit content (otherwie it would be cut of during printing)
                function adjustHeight(el, minHeight) {
                    // compute the height difference which is caused by border and outline
                    var outerHeight = parseInt(getComputedStyle(el).height, 10);
                    var diff = outerHeight - Math.round(el.clientHeight, 1e2);
                    // set the height to 0 in case of it has to be shrinked
                    el.style.height = 0;
                    // set the correct height
                    // el.scrollHeight is the full height of the content, not just the visible part
                    el.style.height = Math.max(minHeight, el.scrollHeight + diff) + 'px';
                }
                // Iterate through all the textareas on the page
                var i, el, minHeight, nodes = form.querySelectorAll('.super-textarea .super-shortcode-field');
                for(i=0; i<nodes.length; i++){
                    el = nodes[i];
                    // we need box-sizing: border-box, if the textarea has padding
                    el.style.boxSizing = el.style.mozBoxSizing = 'border-box';
                    // we don't need any scrollbars, do we? :)
                    el.style.overflowY = 'hidden';
                    // the minimum height initiated through the "rows" attribute
                    minHeight = el.scrollHeight * 1.03;
                    el.addEventListener('input', function() {
                        adjustHeight(el, minHeight);
                    });
                    // we have to readjust when window size changes (e.g. orientation change)
                    window.addEventListener('resize', function() {
                        adjustHeight(el, minHeight);
                    });
                    // we adjust height to the initial content
                    adjustHeight(el, minHeight);
                }

                // Loop over any possible PDF page break elements, and add the height to fill up the rest of the page with "nothing"
                // tmp args = SUPER.resize_pdf_page_breaks(args);
                // tmp // Grab the total form height, this is required to know how many pages will be generated for the PDF file
                // tmp // This way we can also show the progression to the end user
                // tmp args.totalPages = Math.ceil(Math.round(form.clientHeight, 1e2)/args.scrollAmount);
                // tmp args.totalPercentagePerPage = (100/args.totalPages) / 3;
                // tmp args.pdfPercentageCompleted = 0;
                callback(args);
            }});
        });

    };

    // PDF Generation
    SUPER.pdf_generator_generate_page = function(args){
        var form = args.form0.closest('.super-form');
        args.pdfPercentageCompleted += args.totalPercentagePerPage;
        // Update PDF tags
        SUPER.pdf_tags = { pdf_page: args.currentPage, pdf_total_pages: args.totalPages };
        // -----
        // WE CAN'T CALL THIS BECAUSE IT WOULD BREAK PAGE BREAKS DIRECTLY USED INSIDE HTML ELEMENTS 
        // FOR INSTANCE WHEN LOOPING OVERR UPLOADED IMAGES WITH foreach() METHOD
        // SUPER.after_field_change_blur_hook({el: undefined, form: form});
        // -----
        // Loop over any possible PDF page break elements, and add the height to fill up the rest of the page with "nothing"
        // Because disabling the UI takes some time, add a timeout
        var timeout = (args.currentPage===1 ? 200 : 0);
        setTimeout(function(){
            // Now allow printing
            try {
                // Only if not already canceled/reset
                if(form && !form.classList.contains('super-generating-pdf')){
                    return false;
                }
                // Re-wrap text nodes because of possible updated HTML content such as pagination {tags}
                SUPER.pdfWrapTextNodes(args.pdfHeader);
                SUPER.pdfWrapTextNodesRender(args.pdfHeader);
                SUPER.pdfWrapTextNodes(args.pdfFooter);
                SUPER.pdfWrapTextNodesRender(args.pdfHeader);
                // Make sure to position slider amount labels correctly
                SUPER.reposition_slider_element(args.pdfPageContainer, true);
                // After setting slider position re-add text nodes
                nodes = args.pdfPageContainer.querySelectorAll('.super-shortcode.super-slider .amount');
                for(var i=0; i<nodes.length; i++){
                    SUPER.pdfWrapTextNodes(nodes[i]);
                    SUPER.pdfWrapTextNodesRender(nodes[i]);
                }
                if(args.currentPage===1){
                    nodes = args.form0.querySelectorAll('.super-hide-from-current-page');
                    for(i=0; i<nodes.length; i++){
                        nodes[i].classList.remove('super-hide-from-current-page');
                    }
                }
                // eslint-disable-next-line no-undef
                args.pdfPercentageCompleted += args.totalPercentagePerPage;
                if(args.pdfPercentageCompleted>100) args.pdfPercentageCompleted = 100;
                if(args.progressBar) args.progressBar.style.width = args.pdfPercentageCompleted+"%";  

                // Now loop over all the nodes and ignore those that are not visible for the current page
                var nodes = document.body.children;
                for(var i=0; i<nodes.length; i++){
                    if(nodes[i].classList.contains('super-pdf-page-container')){
                        continue;
                    }
                    if(nodes[i].tagName==='LINK' && (nodes[i].id.indexOf('super')!==-1 || nodes[i].id.indexOf('font-awesome')!==-1) ){
                        continue;
                    } 
                    if(nodes[i].tagName==='STYLE' && nodes[i].innerText.indexOf('super-form')!==-1){
                        continue;
                    }
                    if(!nodes[i].closest('.super-pdf-body')){
                        nodes[i].setAttribute('data-html2canvas-ignore', 'true');
                    }
                }
                // Toggle hide ignore items
                document.querySelector('.super-pdf-page-container').classList.add('super-pdf-toggle-ignore-items');
                nodes = args.form0.querySelectorAll('[data-belongs-to-pages]');
                var morePages = false;
                for(i=0; i<nodes.length; i++){
                    // Apply smart page margins on elements that would otherwise been cut in half due to being part of two pages
                    var belongsTo = JSON.parse(nodes[i].dataset.belongsToPages);
                    if(nodes[i].dataset.newMarginTop){
                        if(belongsTo[0]===args.currentPage){
                            nodes[i].style.marginTop = nodes[i].dataset.newMarginTop+'px';
                        }else{
                            if($(nodes[i]).parents('[data-offset-top]').length!==0){
                                // Keep the margin applied
                                nodes[i].style.marginTop = nodes[i].dataset.newMarginTop+'px';
                                continue;
                            }
                            // Revert to old margin
                            nodes[i].style.marginTop = nodes[i].dataset.oldMarginTop;
                        }
                    }
                    // @IMPORTANT - If it is part of a parent that has an offset we skip it
                    if($(nodes[i]).parents('[data-offset-top]').length!==0){
                        continue;
                    }
                    nodes[i].classList.remove('super-hide-from-current-page');
                    if($(nodes[i]).parents('.super-hide-from-current-page:eq(0)').length!==0){
                        continue;
                    }
                    if(belongsTo.indexOf(args.currentPage)===-1){
                        // Does not belong to current page
                        nodes[i].classList.add('super-hide-from-current-page');
                        if(morePages===false && belongsTo.indexOf(args.currentPage+1)!==-1){
                            morePages = true;
                        }
                        continue;
                    }
                    var index = belongsTo.indexOf(args.currentPage);
                    if(args.currentPage>1 && index!==-1){
                        if(nodes[i].dataset.offsetTop){
                            if(Number(nodes[i].dataset.offsetTop)>0){
                                if(index===1){
                                    // From first page to second page
                                    nodes[i].style.marginTop = '-'+(Math.round(Number(nodes[i].dataset.offsetTop), 1e2))+'px';
                                }else{
                                    if(index>1){
                                        // Second to third, or third to fourth, etc.
                                        // e.g. 1 equals (750)
                                        var initialOffset = (Math.round(Number(nodes[i].dataset.offsetTop), 1e2));
                                        var restOfPagesOffset = args.scrollAmount*(index-1);
                                        nodes[i].style.marginTop = '-'+(initialOffset+restOfPagesOffset)+'px';
                                    }
                                }
                            }else{
                                // Second to third, or third to fourth, etc.
                                // e.g. 1 equals (750)
                                var initialOffset = (Math.round(Number(nodes[i].dataset.offsetTop), 1e2));
                                var restOfPagesOffset = args.scrollAmount*(index-1);
                                nodes[i].style.marginTop = (initialOffset+restOfPagesOffset)+'px';
                            }
                        }
                    }
                    if(morePages===false && belongsTo.indexOf(args.currentPage+1)!==-1){
                        morePages = true;
                    }
                }
                // When the page is empty, we can skip it
                if(args.pdfPageContainer.querySelector(':scope > .super-pdf-body > .super-form').clientHeight===0){
                    // Maybe in some use cases we actually want to have blank pages?
                }
                // Update page width and height based on current page orientation
                args.orientation = args.pageOrientationChanges[args.currentPage];
                if(args.orientation==='portrait'){
                    args.pageWidth = args.pageWidthPortrait;
                    args.pageHeight = args.pageHeightPortrait;
                }
                if(args.orientation==='landscape'){
                    args.pageWidth = args.pageWidthLandscape;
                    args.pageHeight = args.pageHeightLandscape;
                }
                args.pageWidthInPixels = args.pageWidth / args.unitRatio;
                args.pageHeightInPixels = args.pageHeight / args.unitRatio;
                args.pdfPageContainer.style.width = (args.pageWidthInPixels*2)+'px';
                args.pdfPageContainer.style.height = (args.pageHeightInPixels*2)+'px';
                args.pdfPageContainer.style.maxHeight = (args.pageHeightInPixels*2)+'px';
                var headerFooterHeight = SUPER.pdf_get_header_height(args)+SUPER.pdf_get_footer_height(args);
                args.scrollAmount = (args.pageHeightInPixels*2)-headerFooterHeight;
                args.scrollAmount = SUPER.round(args.scrollAmount, 2);
                args.pdfPageContainer.querySelector('.super-pdf-body').style.height = args.scrollAmount+'px';
                args.pdfPageContainer.querySelector('.super-pdf-body').style.maxHeight = args.scrollAmount+'px';
                if(args.currentPage===1 && args.pdfSettings.orientation!==args.orientation){
                    // If we need to change the PDF orientation
                    // this is only required when there is a PDF page break at the beginning of the page
                    // and when the orientation is different from the PDF settings
                    // eslint-disable-next-line no-undef
                    args._pdf = new window.jspdf.jsPDF({
                        orientation: args.orientation,   // Orientation of the first page. Possible values are "portrait" or "landscape" (or shortcuts "p" or "l").
                        format: args.format,             // The format of the first page.  Default is "a4"
                        putOnlyUsedFonts: true,    // Only put fonts into the PDF, which were used.
                        precision: 16,              // Precision of the element-positions.
                        userUnit: 1.0,              // Not to be confused with the base unit. Please inform yourself before you use it.
                        floatPrecision: 16,         // or "smart", default is 16
                        unit: args.pdfSettings.unit                  // Measurement unit (base unit) to be used when coordinates are specified.

                        // @TODO password protected --- // jsPDF supports encryption of PDF version 1.3.
                        // @TODO password protected --- // Version 1.3 just uses RC4 40-bit which is kown to be weak and is NOT state of the art.
                        // @TODO password protected --- // Keep in mind that it is just a minimal protection.
                        // @TODO password protected --- encryption: {
                        // @TODO password protected ---     userPassword: "user",
                        // @TODO password protected ---     ownerPassword: "owner",
                        // @TODO password protected ---     userPermissions: ["print", "modify", "copy", "annot-forms"]
                        // @TODO password protected ---     // try changing the user permissions granted
                        // @TODO password protected --- }
                    });                             // Possible values are "pt" (points), "mm", "cm", "m", "in" or "px".
                                                    // Can be:
                                                    // a0 - a10
                                                    // b0 - b10
                                                    // c0 - c10
                                                    // dl
                                                    // letter
                                                    // government-letter
                                                    // legal
                                                    // junior-legal
                                                    // ledger
                                                    // tabloid
                                                    // credit-card
                    if(super_common_i18n.fonts){
                        args._pdf.addFileToVFS('NotoSans-Regular-normal.ttf', super_common_i18n.fonts.NotoSans.regular);
                        args._pdf.addFont('NotoSans-Regular-normal.ttf', 'NotoSans-Regular', 'normal');
                        if(!super_common_i18n.fonts.NotoSans.bold) {
                            args._pdf.addFileToVFS('NotoSans-Bold-bold.ttf', super_common_i18n.fonts.NotoSans.regular);
                        }else{
                            args._pdf.addFileToVFS('NotoSans-Bold-bold.ttf', super_common_i18n.fonts.NotoSans.bold);
                        }
                        args._pdf.addFont('NotoSans-Bold-bold.ttf', 'NotoSans-Bold', 'bold');
                    }
                }
                if(args.pdfSettings.native==='true'){
                    // Experimental
                    document.querySelector('.super-pdf-page-container').classList.remove('super-pdf-toggle-ignore-items');
                    // Only if not already canceled/reset
                    if(form && !form.classList.contains('super-generating-pdf')){
                        return false;
                    }
                    args.pdfPercentageCompleted += args.totalPercentagePerPage;
                    if(args.pdfPercentageCompleted > 99){
                        args.pdfPercentageCompleted = 100;
                    }
                    if(args.progressBar) args.progressBar.style.width = args.pdfPercentageCompleted+"%";  
                    args = SUPER.pdf_generator_render_text(args);
                    // Draw native elements, table cells, fields, radio/checkboxes etc.
                    SUPER.pdf_generator_render_elements(args);
                    // Now draw text for all elements that are in the current view
                    nodes = args.pdfPageContainer.querySelectorAll('.super-shortcode .super-pdf-text-node');
                    for(i=0; i<nodes.length; i++){
                        SUPER.pdf_generator_draw_pdf_text(i, nodes[i], nodes, args);
                    }

                    // If there are more pages to be processed, go ahead
                    if(morePages){
                        args.currentPage++;
                        if(!args.pageOrientationChanges[args.currentPage]){
                            args.pageOrientationChanges[args.currentPage] = args.pageOrientationChanges[args.currentPage-1];
                        }
                        args._pdf.addPage(args.pdfSettings.format, args.pageOrientationChanges[args.currentPage]);
                        SUPER.pdf_generator_generate_page(args);
                    }else{                   
                        // No more pages to generate (submit form / send email)
                        SUPER._pdf_generator_done_callback(args);
                    }
                }else{
                    html2canvas(document.querySelector('.super-pdf-page-container'), {
                        scrollX: 0, // Important, do not remove
                        scrollY: 0,  // -window.scrollY, // Important, do not remove
                        scale: args.pdfSettings.renderScale, // The scale to use for rendering (higher means better quality, but larger file size)
                        currentPage: args.currentPage,
                        useCORS: true,
                        allowTaint: false,
                        backgroundColor: '#ffffff'
                    }).then(function(canvas) {
                        document.querySelector('.super-pdf-page-container').classList.remove('super-pdf-toggle-ignore-items');
                        // Only if not already canceled/reset
                        if(form && !form.classList.contains('super-generating-pdf')){
                            return false;
                        }
                        args.pdfPercentageCompleted += args.totalPercentagePerPage;
                        if(args.pdfPercentageCompleted > 99){
                            args.pdfPercentageCompleted = 100;
                        }
                        if(args.progressBar) args.progressBar.style.width = args.pdfPercentageCompleted+"%";  
                        //var percentage = (100/args.totalPages)*args.currentPage;
                        //if(args.progressBar) args.progressBar.style.width = percentage+"%";  
                        var imgData = canvas.toDataURL("image/jpeg", 1.0);
                        // Add this image as 1 single page
                        args._pdf.addImage(
                            imgData,    // imageData as base64 encoded DataUrl or Image-HTMLElement or Canvas-HTMLElement
                            'JPEG',     // format of file if filetype-recognition fails or in case of a Canvas-Element needs to be specified (default for Canvas is JPEG),
                                        // e.g. 'JPEG', 'PNG', 'WEBP'
                            0,          // x Coordinate (in units declared at inception of PDF document) against left edge of the page
                            0,          // y Coordinate (in units declared at inception of PDF document) against upper edge of the page
                            args.pageWidth,
                            args.pageHeight,
                            'page-'+args.currentPage, // alias (page-1, page-2 etc.)
                            args.pdfSettings.imageQuality, //'FAST' // compression 'NONE', 'FAST', 'MEDIUM' or 'SLOW'
                        );
                        // Make PDF searchable when text rendering is enabled
                        if(!args.pdfSettings.textRendering) args.pdfSettings.textRendering = 'true';
                        if(args.pdfSettings.textRendering==='true'){
                            //args._pdf.text('Testing text'); //, 0, 0, {charSpace: charSpace, lineHeightFactor: lineHeight, baseline: 'middle', renderingMode: renderingMode}); 
                            //args._pdf.text(20, 20, 'Hello world!');
                            //args._pdf.text(120, 20, 'الرقم الوطني');
                            //args._pdf.text(220, 20, 'ФанцыТеxтЦлоуд.Цом');
                            args = SUPER.pdf_generator_render_text(args);
                            // Now draw text for all elements that are in the current view
                            nodes = args.pdfPageContainer.querySelectorAll('.super-shortcode .super-pdf-text-node');
                            for(i=0; i<nodes.length; i++){
                                SUPER.pdf_generator_draw_pdf_text(i, nodes[i], nodes, args);
                            }
                        }
                        // If there are more pages to be processed, go ahead
                        if(morePages){
                            args.currentPage++;
                            if(!args.pageOrientationChanges[args.currentPage]){
                                args.pageOrientationChanges[args.currentPage] = args.pageOrientationChanges[args.currentPage-1];
                            }
                            args._pdf.addPage(args.pdfSettings.format, args.pageOrientationChanges[args.currentPage]);
                            SUPER.pdf_generator_generate_page(args);
                        }else{                   
                            // No more pages to generate (submit form / send email)
                            SUPER._pdf_generator_done_callback(args);
                        }
                    });
                }
            }
            catch(error) {
                console.log("Error: ", error);
            }
        }, timeout );
    }

    // A pretty cool litle function, thanks chatGPT ;) 
    // A recursive function to wrap each text node with a <span> element
    SUPER.pdfWrapTextNodes = function(node){
        // Loop through each child node
        for(var childNode of node.childNodes){
            if(childNode.parentNode.className==='sp-dd'){
                continue;
            }
            if((childNode.parentNode.closest('.super-pdf-header') || childNode.parentNode.closest('.super-pdf-footer')) && childNode.dataset && childNode.dataset.fields){
                //  childNode.classList && childNode.classList.contains('super-html-content')){
                // Reset content to original with tags (solely required for {pdf_page} and {pdf_total_pages} tags)
                SUPER.init_replace_html_tags({el: undefined, form: childNode.parentNode});
                SUPER.pdfWrapTextNodes(childNode);
                SUPER.pdfWrapTextNodesRender(childNode);
                continue;
            }
            if(childNode.className==='super-pdf-text') continue;
            if((childNode.nodeType === Node.ELEMENT_NODE && SUPER.getNodeHeight(childNode, true, true, true)===0) ||
               (childNode.parentNode.nodeType === Node.ELEMENT_NODE && SUPER.getNodeHeight(childNode.parentNode, true, true, true)===0)) {
                continue;
            } 
            // If it's a text node, wrap it with a <span> element
            if(childNode.nodeType === Node.TEXT_NODE) {
                if(childNode.nodeValue==='\n') continue;
                if(childNode.textContent.trim()==='') continue;
                if(childNode.parentNode.closest('.super-toggle-switch')){
                    if(childNode.parentNode.closest('.super-toggle-switch').classList.contains('super-active')){
                        // Show on
                        if(childNode.parentNode.closest('.super-toggle-off')) continue;
                    }else{
                        // Show on
                        if(childNode.parentNode.closest('.super-toggle-on')) continue;
                    }
                }
                var spanElement = document.createElement('span');
                spanElement.className = 'super-pdf-text';
                spanElement.style.fontSize = getComputedStyle(childNode.parentNode).fontSize;
                var fontWeight = parseInt(getComputedStyle(childNode.parentNode).fontWeight);
                if(fontWeight>600) fontWeight = 600;
                spanElement.style.fontWeight = fontWeight;
                spanElement.textContent = childNode.textContent;
                childNode.replaceWith(spanElement);
            }else{
                // If it's not a text node, recursively call the function on its children
                SUPER.pdfWrapTextNodes(childNode);
            }
        }
    };

    // A pretty cool litle function, thanks chatGPT ;) 
    // A recursive function to wrap each text node with a <span> element
    SUPER.pdfWrapTextNodesRender = function(node){
        var i, nodes = node.querySelectorAll('.super-pdf-text');
        for(i=0; i<nodes.length; i++){
            if(nodes[i].closest('xmp')){
                var words = nodes[i].getInnerHTML().split(' ');
            }else{
                var words = nodes[i].textContent.split(' ');
            }
            var html = '';
            for(var x=0; x<words.length; x++){
                if(words[x]===''){
                    html += ' ';
                    continue;
                }
                var fontSize = getComputedStyle(nodes[i]).fontSize;
                var fontWeight = parseInt(getComputedStyle(nodes[i]).fontWeight);
                if(fontWeight>600) fontWeight = 600;
                var parts = words[x].split('-');
                if(parts.length>1 && words[x]!=='-'){
                    for(var y=0; y<parts.length; y++){
                        html += '<span class="super-pdf-text-node" style="font-size:'+fontSize+';font-weight:'+fontWeight+';">'+(parts[y])+'</span>';
                        if((y+1)<parts.length) {
                            html += '<span class="super-pdf-text-node" style="font-size:'+fontSize+';font-weight:'+fontWeight+';">-</span>';
                        }
                    }
                    continue;
                }
                html += '<span class="super-pdf-text-node" style="font-size:'+fontSize+';font-weight:'+fontWeight+';">'+words[x];
                if(words.length>=(x+1)) {
                    //
                }
                if(words.length>(x+1)) {
                    //
                    //html += '<span class="super-pdf-text-node super-pdf-space-node" style="padding: 0px 2px 0px 0px;"> </span>';
                    //html += '<span class="super-pdf-text-node super-pdf-space-node" style="min-width: 4px; display: inline-block;"> </span>';
                    html += ' ';
                }
                html += '</span>';
            }

            nodes[i].innerHTML = html;
        }
    };


    // PDF render text
    SUPER.pdf_generator_render_text = function(args){
        // If so add a text node on the exact position
        args.charSpaceMultiplier = 0.00135;
        args.convertFromPixel = 1;
        args.convertToPixel = 1;
        args.lineHeight = 1.194;
        args.renderingMode = 'invisible'; //'invisible', // fill,
        if(args.debugger===true || args.pdfSettings.native==='true'){
            args.renderingMode = 'fill'; //'invisible', // fill,
        }
        // Convert unit to pixel
        if(args.pdfSettings.unit=='pt') args.convertToPixel = 1.333333333333333;
        if(args.pdfSettings.unit=='mm') args.convertToPixel = 3.7795275591;
        if(args.pdfSettings.unit=='cm') args.convertToPixel = 37.7952755906
        if(args.pdfSettings.unit=='in') args.convertToPixel = 96;
        // Convert pixel to unit
        if(args.pdfSettings.unit=='pt') args.convertFromPixel = 0.75;
        if(args.pdfSettings.unit=='mm') args.convertFromPixel = 0.2645833333;
        if(args.pdfSettings.unit=='cm') args.convertFromPixel = 0.0264583333;
        if(args.pdfSettings.unit=='in') args.convertFromPixel = 0.0104166667;
        // Convert pixel to unit
        if(args.pdfSettings.unit=='pt') args.charSpaceMultiplier = 0.00200;
        if(args.pdfSettings.unit=='mm') args.charSpaceMultiplier = 0.00200;
        if(args.pdfSettings.unit=='cm') args.charSpaceMultiplier = 0.00200;
        if(args.pdfSettings.unit=='in') args.charSpaceMultiplier = 0.00200;
        args.topLineHeightDivider = 1;
        if(args.pdfSettings.unit=='px') args.topLineHeightDivider = 2;
        var m = args.pdfSettings.margins;
        var bodyMargins = {
            top: parseFloat(m.body.top)*args.convertToPixel,
            right: parseFloat(m.body.right)*args.convertToPixel,
            bottom: parseFloat(m.body.bottom)*args.convertToPixel,
            left: parseFloat(m.body.left)*args.convertToPixel,
        };
        // Determine scale
        var formWidth = args.form0.clientWidth;
        formWidth = formWidth + bodyMargins.left + bodyMargins.right;
        var pdfPageWidth = args._pdf.internal.pageSize.getWidth()*args.convertToPixel;
        args.scale = formWidth / pdfPageWidth;
        if(super_common_i18n.fonts){
            args._pdf.setFont('NotoSans-Regular', 'normal', 'normal');
        }else{
            args._pdf.setFont('Helvetica', 'normal', 'normal');
        }
        args._pdf.setTextColor('black');
        return args;
    };
    SUPER.pdf_get_header_height = function(args){
        var node = args.pdfPageContainer.querySelector('.super-pdf-header'),
            pos = node.getBoundingClientRect();
        return pos.height;
    }
    SUPER.pdf_get_footer_height = function(args){
        var pos = args.pdfFooter.getBoundingClientRect();
        return pos.height;
    }
    SUPER.pdf_get_native_el_position = function(el, args){
        var pos = el.getBoundingClientRect();
        var w = (pos.width/args.scale)*args.convertFromPixel;
        var h = (pos.height/args.scale)*args.convertFromPixel;
        var t = ((pos.top)/args.scale)*args.convertFromPixel;
        var l = ((pos.left+9999)/args.scale)*args.convertFromPixel;
        if(args.debugger===true) l = ((pos.left)/args.scale)*args.convertFromPixel;
        return { w: w, h: h, t: t, l: l }
    }
    SUPER.pdf_rgba2hex = function(args, rgba, setters, lineWidth){
        if(typeof args.lineWidth==='undefined') lineWidth = 1; // 1px by default
        if(args.renderingMode==='invisible') rgba = 'rgba(0,0,0,0)';
        //var hex = SUPER.pdf_rgba2hex(color, ['drawColor','fillColor']);
        var i, c, hex = '#'+(rgba.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+\.{0,1}\d*))?\)$/).slice(1).map((n, i) => (i === 3 ? Math.round(parseFloat(n) * 255) : parseFloat(n)).toString(16).padStart(2, '0').replace('NaN', '')).join(''));
        if(hex.length>=9){
            c = rgba.split(',');
            var ch1 = SUPER.round(c[0].split('(')[1]);
            var ch2 = SUPER.round(c[1]);
            var ch3 = SUPER.round(c[2]);
            var ch4 = SUPER.round(c[3].split(')')[0], 2);
            args._pdf.setGState(new args._pdf.GState({opacity: ch4}));
            for(i=0; i<setters.length; i++){
                if(setters[i]==='textColor'){
                    args._pdf.setTextColor(ch1, ch2, ch3, ch4);
                    continue;
                }
                if(setters[i]==='drawColor'){
                    args._pdf.setLineWidth(lineWidth*args.convertFromPixel);
                    args._pdf.setDrawColor(ch1, ch2, ch3, ch4);
                    continue;
                }
                if(setters[i]==='fillColor'){
                    args._pdf.setFillColor(ch1, ch2, ch3, ch4);
                    continue;
                }
            }
        }else{
            args._pdf.setGState(new args._pdf.GState({opacity: 1}));
            for(i=0; i<setters.length; i++){
                if(setters[i]==='textColor'){
                    args._pdf.setTextColor(hex);
                    continue;
                }
                if(setters[i]==='drawColor'){
                    args._pdf.setLineWidth(lineWidth*args.convertFromPixel);
                    args._pdf.setDrawColor(hex);
                    continue;
                }
                if(setters[i]==='fillColor'){
                    args._pdf.setFillColor(hex);
                    continue;
                }
            }
        }
    }

    // PDF Draw text
    SUPER.pdf_generator_draw_pdf_text = function(i, el, nodes, args){
        if($(el).parents('.super-hide-from-current-page:eq(0)').length){
            return true;
        }
        // Grab belongs to based of parent node
        var p = el.parentNode.closest('[data-belongs-to-pages]');
        if(p && p.classList.contains('super-pdf-el-with-margin')){
            if(JSON.parse(p.dataset.belongsToPages).length>1){
                var belongsToPages = JSON.parse(p.dataset.belongsToPages);
                if(args.currentPage===belongsToPages[0]){
                    // Skip for this page
                    return true;
                }
            }
        }else{
            if(p && p.dataset.belongsToPages.indexOf(args.currentPage)===-1){
                // Skip for this page
                return true;
            }
        }
        var tmpPosTop, paddingRight, paddingLeft, paddingTop, pos, value = '';
        value = el.innerText;
        if(value==='') return true; //continue;
        pos = el.getBoundingClientRect();
        if(el.classList.contains('super-pdf-space-node')){
            console.log(pos.width);
            //html += '<span class="super-pdf-text-node super-pdf-space-node" style="padding: 0px 2px 0px 0px;"> </span>';
        }

        if(pos.height===0) return true;
        if(super_common_i18n.fonts){
            args._pdf.setFont('NotoSans-Regular', 'normal', 'normal');
        }else{
            args._pdf.setFont('Helvetica', 'normal', 'normal');
        }
        // Reset opacity
        args._pdf.setGState(new args._pdf.GState({opacity: 1}));
        // Reset text color
        args._pdf.setTextColor('black');
        // Set draw/fill colors to be transparent
        SUPER.pdf_rgba2hex(args, 'rgba(0,0,0,0)', ['drawColor', 'fillColor']);

        // Before we print the text, we must check if it's visible for this specific PDF page
        tmpPosTop = pos.top;
        // Only if not header and not footer, because these are printed on every single page
        if((!el.closest('.super-pdf-header')) && !el.closest('.super-pdf-footer')){
            var headerHeight = SUPER.pdf_get_header_height(args);
            if((tmpPosTop-(headerHeight-1)) < 0 || tmpPosTop > (args.scrollAmount+(headerHeight-1))){
                // Not for adaptive placeholder
                if(!el.closest('.super-adaptive-placeholder')){
                    return true; //continue;
                }
            }
        }
        var posWidth = (pos.width/args.scale)*args.convertFromPixel;
        var posHeight = (pos.height/args.scale)*args.convertFromPixel;
        var posLeft = ((pos.left+9999)/args.scale)*args.convertFromPixel;
        if(el.parentNode.closest('.super-li-marker')){
            posLeft = ((pos.left-pos.width+9999)/args.scale)*args.convertFromPixel;
        }
        if(args.debugger===true){
            posLeft = ((pos.left)/args.scale)*args.convertFromPixel;
        }
        var posTop = ((tmpPosTop)/args.scale)*args.convertFromPixel;
        var fontSize = parseFloat(getComputedStyle(el.parentNode).fontSize);
        //var fontSizePoint = fontSize * 0.67;
        var fontSizePoint = fontSize * 0.66; // base value
        //var fontSizePoint = fontSize * 0.57; // base value
        var textAlign = 'left';
        if(args.pdfSettings.fontSizeTuning){
            args.pdfSettings.fontSizeTuning = parseFloat(args.pdfSettings.fontSizeTuning);
            fontSizePoint = fontSizePoint*args.pdfSettings.fontSizeTuning;
        }
        args._pdf.setFontSize(fontSizePoint);
        //value = args._pdf.setFontSize(fontSizePoint).splitTextToSize(value, posWidth+1);
        var charSpace = -(fontSize*args.charSpaceMultiplier)*args.convertFromPixel;
        var topLineHeight = (((fontSize*1.32)-fontSize)/args.topLineHeightDivider)*args.convertFromPixel;
        var tagName = el.parentNode.tagName;
        if(el.parentNode.closest('.super-pdf-text')){
            tagName = el.parentNode.closest('.super-pdf-text').parentNode.tagName;
        }
        if( tagName==='B' || tagName==='STRONG' || tagName==='TH' || (el.parentNode.closest('.super-pdf-text') && el.parentNode.closest('.super-pdf-text').closest('B')) || 
            $(el).parents('strong').length!==0 || $(el).parents('b').length!==0){
            if(super_common_i18n.fonts){
                args._pdf.setFont('NotoSans-Bold', 'normal', 'bold');
            }else{
                args._pdf.setFont('Helvetica', 'normal', 'bold');
            }
        }
        // If font weight is above X
        if(parseInt(getComputedStyle(el).fontWeight)>=700){
            if(super_common_i18n.fonts){
                args._pdf.setFont('NotoSans-Bold', 'normal', 'bold');
            }else{
                args._pdf.setFont('Helvetica', 'normal', 'bold');
            }
        }
        var color = getComputedStyle(el.parentNode).backgroundColor;
        // When background color of PDF itself matches the background color of element then don't draw it
        if(color!=='rgb(255, 255, 255)' && color!=='rgba(0, 0, 0, 0)'){
            SUPER.pdf_rgba2hex(args, color, ['fillColor']);
            args._pdf.rect(posLeft, posTop, posWidth, posHeight, 'F');
        }
        // Set color
        color = getComputedStyle(el.parentNode).color;
        SUPER.pdf_rgba2hex(args, color, ['textColor']);
        posWidth = ((pos.width+1)/args.scale)*args.convertFromPixel;
        if(textAlign==='right'){
            posLeft = posLeft+posWidth;
        }
        args._pdf.text(value, posLeft, posTop+(topLineHeight*1), {isInputVisual: false, isOutputVisual: true, isInputRtl: false, isOutputRtl: false, align: textAlign, charSpace: charSpace, lineHeightFactor: args.lineHeight, baseline: 'hanging', renderingMode: args.renderingMode}); 
        return true;
    };

    // Check if current element is visible on current page
    SUPER.isVisibleOnCurrentPage = function(args, el){
        if(el.classList.contains('super-invisible') || $(el).parents('.super-invisible:eq(0)').length!==0) return false;
        if(el.classList.contains('super-pdf-tmp-replaced') || $(el).parents('.super-pdf-tmp-replaced:eq(0)').length!==0) return false;
        if($(el).parents('.super-hide-from-current-page:eq(0)').length!==0) return false;
        if($(el).parents('.super-hide-from-current-page:eq(0)').length) return false;
        var pos = el.getBoundingClientRect();
        if(pos.height===0) {
            return false;
        }
        if(el.closest('.super-pdf-header') || el.closest('.super-pdf-footer')){
            // Always print
            return true;
        }
        if(!el.dataset.belongsToPages) {
            if(el.closest('[data-belongs-to-pages]').classList.contains('super-pdf-el-with-margin')){
                if(JSON.parse(el.closest('[data-belongs-to-pages]').dataset.belongsToPages).length>1){
                    var belongsToPages = JSON.parse(el.closest('[data-belongs-to-pages]').dataset.belongsToPages);
                    el.dataset.belongsToPages = '['+JSON.stringify(belongsToPages[1])+']';
                }
            }else{
                el.dataset.belongsToPages = el.closest('[data-belongs-to-pages]').dataset.belongsToPages;
            }
        }
        if(!el.dataset.belongsToPages) {
            return false;
        }
        var belongsTo = JSON.parse(el.dataset.belongsToPages);
        var index = belongsTo.indexOf(args.currentPage);
        if(index===-1){
            return false;
        }
        if(belongsTo.length>1 && index===0 && el.classList.contains('super-pdf-el-with-margin')){
            // Print on next page :)
            return false;
        }
        if(args.currentPage===1){
            // Check if element with margin
            if(el.classList.contains('super-pdf-el-with-margin')){
                if(index>0){
                    return false;
                }
            }
        }
        return true;
    }
    // PDF render native elements
    SUPER.pdf_generator_render_elements = function(args){
        var selectors = `
        .super-divider,
        .super-li-marker,
        .super-html-content hr,
        .super-html-content img,
        .super-column,
        .super-signature,
        .super-image,
        .super-accordion-header, .super-accordion-content, .super-tabs-content,
        .super-keyword-tags,
        .super-text .super-shortcode-field,
        .super-password .super-shortcode-field,
        .super-date .super-shortcode-field,
        .super-time .super-shortcode-field,
        .super-textarea .super-shortcode-field,
        .super-currency .super-shortcode-field,
        .super-quantity .super-shortcode-field,
        .super-filled .super-adaptive-placeholder > span,
        .super-dropdown .super-item.super-placeholder,
        .super-checkbox .super-item > div,
        .super-radio .super-item > div,
        .super-toggle-switch,
        .super-slider,
        .super-color,
        .super-rating > .super-field-wrapper > .super-rating,
        .super-fileupload-button,
        .super-fileupload-files > div,
        table
        `,
        i, el, node, inodes, nodes = Array.prototype.slice.call(args.pdfHeader.querySelectorAll(selectors)).concat(Array.prototype.slice.call(args.pdfPageContainer.querySelectorAll(selectors))).concat(Array.prototype.slice.call(args.pdfFooter.querySelectorAll(selectors))),
        x, y, z, cells, bgColor, color, after, before, width, margin, diff, pos, tmpPosTop, borderWidth, paddingRight, paddingLeft, paddingTop, value = '';
        //.super-slider
        //.super-calculator-currency-wrapper, 
        //.super-calculator-label, 
        //.super-fileupload-name, 
        //.super-fileupload-button-text, 
        //.super-toggle-prefix-label > span, 
        //.super-toggle-suffix-label > span, 
        //.super-textarea .super-shortcode-field, 
        //`;
        for( i=0; i < nodes.length; i++ ) {
            args._pdf.setLineDash(0);
            el = nodes[i];
            // Before we print the text, we must check if it's visible for this specific PDF page
            if(SUPER.isVisibleOnCurrentPage(args, nodes[i])===false) {
                continue;
            }
            // Reset opacity
            args._pdf.setGState(new args._pdf.GState({opacity: 1}));
            // Reset text color
            args._pdf.setTextColor('black');
            // Reset line width
            //args._pdf.setLineWidth(1*args.convertFromPixel);

            // Markers
            if(el.classList.contains('super-li-marker')){
                if(el.parentNode.closest('.super-dropdown-list')) continue;
                // Skip or draw a bullet, circle or square
                if(el.parentNode.parentNode.tagName==='UL'){
                    pos = SUPER.pdf_get_native_el_position(el, args);
                    if(el.parentNode.parentNode.style.listStyleType==='square'){
                        width = (3/args.scale)*args.convertFromPixel;
                        color = getComputedStyle(el,':after').backgroundColor;
                        SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                        args._pdf.rect(pos.l+(pos.w/2.3), pos.t+(pos.h/2), width, width, 'FD');
                        continue;
                    }
                    if(el.parentNode.parentNode.style.listStyleType==='circle'){
                        color = getComputedStyle(el,':after').borderColor;
                        SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                        args._pdf.setLineWidth(0.7*args.convertFromPixel);
                        radius = (2/args.scale)*args.convertFromPixel;
                        args._pdf.circle(pos.l+(pos.w/2), pos.t+(pos.h/1.2), radius, 'D');
                        continue;
                    }
                    color = getComputedStyle(el,':after').backgroundColor;
                    SUPER.pdf_rgba2hex(args, color, ['fillColor', 'drawColor']);
                    args._pdf.setLineWidth(0.7*args.convertFromPixel);
                    radius = (2/args.scale)*args.convertFromPixel;
                    args._pdf.circle(pos.l+(pos.w/2), pos.t+(pos.h/1.2), radius, 'FD');
                }
                continue;
            }
            // Divider element
            if(el.classList.contains('super-divider')){
                pos = SUPER.pdf_get_native_el_position(el, args);
                node = el.querySelector('.super-divider-inner');
                borderWidth = parseFloat(getComputedStyle(node).borderWidth);
                color = getComputedStyle(node).borderColor;  
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                args._pdf.setLineWidth(borderWidth*args.convertFromPixel);
                if(el.classList.contains('style-dashed')) args._pdf.setLineDash([2]);
                if(el.classList.contains('style-dotted')) args._pdf.setLineDash([0.5]);
                args._pdf.line(pos.l, pos.t, pos.l+pos.w, pos.t);
                continue;
            }
            // Image element
            if(el.classList.contains('super-image')){
                var image = el.querySelector('.super-image-inner');
                if(image){
                    var src = image.querySelector('img').src;
                    var type = el.dataset.type;
                    pos = SUPER.pdf_get_native_el_position(image, args);
                    var filename = src.split('/').pop();
                    args._pdf.addImage(src, 'JPEG', pos.l, pos.t, pos.w, pos.h, filename, args.pdfSettings.imageQuality, 0);
                }
                continue;
            }
            // Signature
            if(el.classList.contains('super-signature')){
                node = el.querySelector('.super-signature-canvas');
                color = getComputedStyle(node).borderColor;  
                bgColor = getComputedStyle(node).backgroundColor;
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                SUPER.pdf_rgba2hex(args, bgColor, ['fillColor']);
                pos = SUPER.pdf_get_native_el_position(node, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                var canvas = node.children[0];
                var src = canvas.toDataURL('image/png');
                pos = SUPER.pdf_get_native_el_position(canvas, args);
                var fieldName = el.closest('.super-shortcode').querySelector('.super-shortcode-field').name;
                args._pdf.addImage(src, 'JPEG', pos.l, pos.t, pos.w, pos.h, fieldName, args.pdfSettings.imageQuality, 0);
                continue;
            }
            // Accordion header
            if(el.classList.contains('super-accordion-header')){
                // Draw box
                bgColor = getComputedStyle(el).backgroundColor;  
                color = getComputedStyle(el).borderColor;  
                borderWidth = parseFloat(getComputedStyle(el).borderWidth);
                pos = SUPER.pdf_get_native_el_position(el, args);
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                SUPER.pdf_rgba2hex(args, bgColor, ['fillColor']);
                args._pdf.setLineWidth(borderWidth*args.convertFromPixel);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                // Accordion title
                if(el.querySelector('.super-accordion-title')){
                    el = el.querySelector('.super-accordion-title');
                    pos = SUPER.pdf_get_native_el_position(el, args);
                    var borderRightWidth = parseFloat(getComputedStyle(el).borderRightWidth);
                    if(borderRightWidth>0){
                        color = getComputedStyle(el).borderRightColor;
                        SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                        args._pdf.setLineWidth(borderRightWidth*args.convertFromPixel);
                        args._pdf.line(pos.l+pos.w, pos.t, pos.l+pos.w, pos.t+pos.h );
                    }
                    var borderLeftWidth = parseFloat(getComputedStyle(el).borderLeftWidth);
                    if(borderLeftWidth>0){
                        color = getComputedStyle(el).borderLeftColor;
                        SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                        args._pdf.setLineWidth(borderLeftWidth*args.convertFromPixel);
                        args._pdf.line(pos.l, pos.t, pos.l, pos.t+pos.h );
                    }
                }
                continue;
            }
            if(el.classList.contains('super-tabs-content')){
                // Draw box
                p = el.parentNode;
                bgColor = getComputedStyle(p).backgroundColor;  
                pos = SUPER.pdf_get_native_el_position(el, args);
                SUPER.pdf_rgba2hex(args, bgColor, ['drawColor', 'fillColor']);
                // For this element we must constrain the height to the scroll height
                if(!el.closest('.super-pdf-header')){
                    var rpos = el.getBoundingClientRect();
                    var rt = rpos.top-SUPER.pdf_get_header_height(args);
                    var rb = rpos.bottom-SUPER.pdf_get_header_height(args);
                    if(rt<0) {
                        pos.t = (SUPER.pdf_get_header_height(args)/args.scale)*args.convertFromPixel;
                        pos.h = ((rb/args.scale)*args.convertFromPixel);
                    }
                    if(rb>args.scrollAmount){
                        renderBottomBorder = false;
                        pos.h = (args.scrollAmount/args.scale)*args.convertFromPixel;
                    }
                }
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                // Left border
                var borderLeftWidth = parseFloat(getComputedStyle(p).borderLeftWidth);
                if(borderLeftWidth>0){
                    color = getComputedStyle(p).borderLeftColor;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                    args._pdf.setLineWidth(borderLeftWidth*args.convertFromPixel);
                    args._pdf.line(pos.l, pos.t, pos.l, pos.t+pos.h );
                }
                // Top border
                var borderTopWidth = parseFloat(getComputedStyle(p).borderTopWidth);
                if(borderTopWidth>0){
                    color = getComputedStyle(p).borderTopColor;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                    args._pdf.setLineWidth(borderTopWidth*args.convertFromPixel);
                    args._pdf.line(pos.l, pos.t, pos.l+pos.w, pos.t );
                }
                // Right border
                var borderRightWidth = parseFloat(getComputedStyle(p).borderRightWidth);
                if(borderRightWidth>0){
                    color = getComputedStyle(p).borderRightColor;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                    args._pdf.setLineWidth(borderRightWidth*args.convertFromPixel);
                    args._pdf.line(pos.l+pos.w, pos.t, pos.l+pos.w, pos.t+pos.h );
                }
                // Bottom border
                var borderBottomWidth = parseFloat(getComputedStyle(p).borderBottomWidth);
                if(borderBottomWidth>0){
                    color = getComputedStyle(p).borderBottomColor;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                    args._pdf.setLineWidth(borderBottomWidth*args.convertFromPixel);
                    args._pdf.line(pos.l, pos.t+pos.h, pos.l+pos.w, pos.t+pos.h );
                }
                continue;
            }
            // Accordion content & column
            if(el.classList.contains('super-accordion-content') || el.classList.contains('super-column')){
                var renderBottomBorder = true;
                // Draw box
                bgColor = getComputedStyle(el).backgroundColor;  
                pos = SUPER.pdf_get_native_el_position(el, args);
                SUPER.pdf_rgba2hex(args, bgColor, ['drawColor', 'fillColor']);
                // For this element we must constrain the height to the scroll height
                if(!el.closest('.super-pdf-header')){
                    var rpos = el.getBoundingClientRect();
                    var rt = rpos.top-SUPER.pdf_get_header_height(args);
                    var rb = rpos.bottom-SUPER.pdf_get_header_height(args);
                    if(rt<0) {
                        pos.t = (SUPER.pdf_get_header_height(args)/args.scale)*args.convertFromPixel;
                        pos.h = ((rb/args.scale)*args.convertFromPixel);
                    }
                    if(rb>args.scrollAmount){
                        renderBottomBorder = false;
                        pos.h = (args.scrollAmount/args.scale)*args.convertFromPixel;
                    }
                    //}else{
                    //    if(pos.t<0){
                    //        //pos.h = pos.h+pos.t-((SUPER.pdf_get_header_height(args))/args.scale)*args.convertFromPixel;
                    //    }else{
                    //        //pos.h = pos.h-pos.t-((SUPER.pdf_get_header_height(args))/args.scale)*args.convertFromPixel;
                    //    }
                    //}
                    //if(rt<0) {
                    //    //pos.t = ((SUPER.pdf_get_header_height(args))/args.scale)*args.convertFromPixel;
                    //}
                }
                if(el.classList.contains('super-column')){
                    args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'F');
                }else{
                    args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                }
                // Left border
                var borderLeftWidth = parseFloat(getComputedStyle(el).borderLeftWidth);
                if(borderLeftWidth>0){
                    color = getComputedStyle(el).borderLeftColor;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                    args._pdf.setLineWidth(borderLeftWidth*args.convertFromPixel);
                    args._pdf.line(pos.l, pos.t, pos.l, pos.t+pos.h );
                }
                // Top border
                var borderTopWidth = parseFloat(getComputedStyle(el).borderTopWidth);
                if(borderTopWidth>0){
                    color = getComputedStyle(el).borderTopColor;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                    args._pdf.setLineWidth(borderTopWidth*args.convertFromPixel);
                    args._pdf.line(pos.l, pos.t, pos.l+pos.w, pos.t );
                }
                // Right border
                var borderRightWidth = parseFloat(getComputedStyle(el).borderRightWidth);
                if(borderRightWidth>0){
                    color = getComputedStyle(el).borderRightColor;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                    args._pdf.setLineWidth(borderRightWidth*args.convertFromPixel);
                    args._pdf.line(pos.l+pos.w, pos.t, pos.l+pos.w, pos.t+pos.h );
                }
                // Bottom border
                if(!renderBottomBorder) continue;
                var borderBottomWidth = parseFloat(getComputedStyle(el).borderBottomWidth);
                if(borderBottomWidth>0){
                    color = getComputedStyle(el).borderBottomColor;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                    args._pdf.setLineWidth(borderBottomWidth*args.convertFromPixel);
                    args._pdf.line(pos.l, pos.t+pos.h, pos.l+pos.w, pos.t+pos.h );
                }
                continue;
            }

            // Keywords
            if(el.classList.contains('super-keyword-tags')){
                // Draw input box
                node = el.querySelector('.super-autosuggest-tags');
                bgColor = getComputedStyle(node).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                color = getComputedStyle(node).borderColor;  
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                pos = SUPER.pdf_get_native_el_position(node, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                // Render after border but before keywords
                if(el.querySelector('.super-adaptive-placeholder > span')){
                    bgColor = getComputedStyle(el.querySelector('.super-adaptive-placeholder > span')).backgroundImage;
                    if(bgColor!=='none'){
                        color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                        SUPER.pdf_rgba2hex(args, color, ['drawColor','fillColor']);
                        pos = SUPER.pdf_get_native_el_position(el.querySelector('.super-adaptive-placeholder > span'), args);
                        args._pdf.rect(pos.l, pos.t+(pos.h/4), pos.w, pos.h, 'FD');
                    }
                }
                // Render keywords after placeholder
                inodes = node.querySelectorAll('.super-keyword-tag');
                for(x=0; x<inodes.length; x++){
                    bgColor = getComputedStyle(inodes[x]).backgroundColor;
                    SUPER.pdf_rgba2hex(args, bgColor, ['fillColor']);
                    pos = SUPER.pdf_get_native_el_position(inodes[x], args);
                    args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'F');
                    // Close icon
                    color = getComputedStyle(inodes[x],':after').color;
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                    var radius = pos.h/3;
                    margin = (pos.h-radius)/4;
                    args._pdf.circle(pos.l+pos.w-radius-margin, pos.t+radius+margin, radius, 'F');
                    SUPER.pdf_rgba2hex(args, bgColor, ['drawColor']);
                    args._pdf.setLineWidth(1*args.convertFromPixel);
                    var x1 = pos.l+pos.w-radius+(radius/2);
                    var y1 = pos.t-radius+(radius/2);
                    var x2 = pos.l+pos.w+radius-(radius/2);
                    var y2 = pos.t+radius-(radius/2);
                    args._pdf.line(x1-radius-margin, y1+radius+margin, x2-radius-margin, y2+radius+margin);
                    args._pdf.line(x1-radius-margin, y2+radius+margin, x2-radius-margin, y1+radius+margin);
                }
                continue;
            }

            // First render adaptive placeholders to avoid conflicts
            if(el.closest('.super-adaptive-placeholder')){
                if(el.closest('.super-auto-suggest') || el.closest('.super-keyword-tags')){
                    // Skip it
                    continue;
                }
                bgColor = getComputedStyle(el).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['drawColor','fillColor']);
                    pos = SUPER.pdf_get_native_el_position(el, args);
                    args._pdf.rect(pos.l, pos.t+(pos.h/4), pos.w, pos.h, 'FD');
                }
                continue;
            }

            // Autosuggest
            if(el.closest('.super-auto-suggest')){
                var p = el.closest('.super-auto-suggest');
                if(!p.classList.contains('super-filled')){
                    p = el;
                }
                // Draw input box
                bgColor = getComputedStyle(el).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                color = getComputedStyle(el).borderColor;  
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                pos = SUPER.pdf_get_native_el_position(el, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                // Render after border but before keywords
                if(p.querySelector('.super-adaptive-placeholder > span')){
                    bgColor = getComputedStyle(p.querySelector('.super-adaptive-placeholder > span')).backgroundImage;
                    if(bgColor!=='none'){
                        color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                        SUPER.pdf_rgba2hex(args, color, ['drawColor','fillColor']);
                        pos = SUPER.pdf_get_native_el_position(p.querySelector('.super-adaptive-placeholder > span'), args);
                        args._pdf.rect(pos.l, pos.t+(pos.h/4), pos.w, pos.h, 'FD');
                    }
                }
                if(el.closest('.super-auto-suggest').classList.contains('super-filled')){
                    // Remove icon [x]
                    node = el.closest('.super-auto-suggest').querySelector('.super-item.super-active');
                    pos = SUPER.pdf_get_native_el_position(node, args);
                    var posLeft = pos.l;
                    var inode = el.closest('.super-auto-suggest').querySelector('.super-item.super-active > div');
                    if(!inode){
                        // No div exists, make sure to convert spans to div
                        var text = el.closest('.super-auto-suggest').querySelector('.super-item.super-active').textContent;
                        el.closest('.super-auto-suggest').querySelector('.super-item.super-active').innerHTML = '<div class="super-pdf-text-node">'+text+'</div>';
                        inode = el.closest('.super-auto-suggest').querySelector('.super-item.super-active > div');
                    }
                    ipos = SUPER.pdf_get_native_el_position(inode, args);
                    diff = Math.abs(posLeft - ipos.l);
                    margin = diff * 0.2;
                    width = diff * 0.6;
                    bgColor = getComputedStyle(node,':after').backgroundColor;
                    SUPER.pdf_rgba2hex(args, bgColor, ['drawColor', 'fillColor']);
                    args._pdf.rect(pos.l+margin, pos.t+margin, width, width, 'FD');
                    color = getComputedStyle(node,':after').color;
                    SUPER.pdf_rgba2hex(args, color, ['textColor']);
                    args._pdf.text('x', pos.l+margin+(width/2), pos.t+margin+(width/2), {align: 'center', lineHeightFactor: args.lineHeight, baseline: 'middle', renderingMode: args.renderingMode});
                }
                continue;
            }
            
            // Text field
            if(el.closest('.super-text')){
                // Draw input box
                bgColor = getComputedStyle(el).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                color = getComputedStyle(el).borderColor;  
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                pos = SUPER.pdf_get_native_el_position(el, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                // If int phone number
                var flag = el.closest('.super-text').querySelector('.super-int-phone_flag');
                if(flag){
                    var src = flag.dataset.imgData;
                    pos = SUPER.pdf_get_native_el_position(flag, args);
                    args._pdf.addImage(src, type, pos.l, pos.t, pos.w, pos.h, el.dataset.name, args.pdfSettings.imageQuality, 0);
                }
                continue;
            }
            // Password
            if(el.closest('.super-password')){
                // Draw input box
                bgColor = getComputedStyle(el).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                color = getComputedStyle(el).borderColor;  
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                pos = SUPER.pdf_get_native_el_position(el, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                continue;
            }
            // Date
            if(el.closest('.super-date')){
                // Draw input box
                pos = SUPER.pdf_get_native_el_position(el, args);
                bgColor = getComputedStyle(el).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                color = getComputedStyle(el).borderColor;  
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                continue;
            }
            // Time
            if(el.closest('.super-time')){
                // Draw input box
                bgColor = getComputedStyle(el).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                color = getComputedStyle(el).borderColor;  
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                pos = SUPER.pdf_get_native_el_position(el, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                continue;
            }
            // Textarea 
            if(el.closest('.super-textarea')){
                // Draw input box
                bgColor = getComputedStyle(el).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                color = getComputedStyle(el).borderColor;  
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                pos = SUPER.pdf_get_native_el_position(el, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                continue;
            }
            // File upload
            if(el.classList.contains('super-fileupload-button')){
                // Draw input box
                bgColor = getComputedStyle(el).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                color = getComputedStyle(el).borderColor;  
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                pos = SUPER.pdf_get_native_el_position(el, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                if(el.querySelector(':scope > i')){
                    // Plus button
                    node = el.querySelector(':scope > i');
                    color = getComputedStyle(el).color;
                    SUPER.pdf_rgba2hex(args, color, ['textColor']);
                    pos = SUPER.pdf_get_native_el_position(node, args);
                    var fontSize = parseFloat(getComputedStyle(el).fontSize);
                    var fontSizePoint = fontSize * 0.67 * 1.5;
                    args._pdf.setFontSize(fontSizePoint);
                    args._pdf.text('+', pos.l+(pos.w/2), pos.t+(pos.h/2), {align: 'center', lineHeightFactor: args.lineHeight, baseline: 'middle', renderingMode: args.renderingMode}); 
                }
                continue;
            }
            // File upload files
            if(el.parentNode.classList.contains('super-fileupload-files')){
                pos = SUPER.pdf_get_native_el_position(el, args);
                // Box
                bgColor = getComputedStyle(el).backgroundColor;
                SUPER.pdf_rgba2hex(args, bgColor, ['fillColor']);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'F');
                // Border
                color = getComputedStyle(el).borderColor;  
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'D');
                // Add the image itself:
                var image = el.querySelector('.super-fileupload-image');
                if(image){
                    var src = image.querySelector('img').src;
                    var type = el.dataset.type;
                    pos = SUPER.pdf_get_native_el_position(image, args);
                    var filename = src.split('/').pop();
                    args._pdf.addImage(src, 'JPEG', pos.l, pos.t, pos.w, pos.h, filename, args.pdfSettings.imageQuality, 0);
                }
                continue;
            }

            // Horizontal Rule (HR) inside HTML content
            if(el.tagName==='HR' && el.parentNode.closest('.super-html-content')){
                pos = SUPER.pdf_get_native_el_position(el, args);
                args._pdf.setLineWidth(0.1);
                args._pdf.setDrawColor(0, 0, 0);
                args._pdf.line(pos.l, pos.t, pos.l+pos.w, pos.t);
                continue;
            }

            // Images inside HTML content
            if(el.tagName==='IMG' && el.parentNode.closest('.super-html-content')){
                // Add the image itself:
                pos = SUPER.pdf_get_native_el_position(el, args);
                var src = el.src;
                var filename = src.split('/').pop();
                args._pdf.addImage(src, 'JPEG', pos.l, pos.t, pos.w, pos.h, filename, args.pdfSettings.imageQuality, 0);
                continue;
            }

            // Currency
            if(el.closest('.super-currency')){
                // Get position of the element
                pos = SUPER.pdf_get_native_el_position(el, args);
                // Always first draw background color before drawing border around it
                bgColor = getComputedStyle(el).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                // Draw input box
                color = getComputedStyle(el).borderColor;
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                continue;
            }
            // Quantity
            if(el.closest('.super-quantity')){
                // Draw input box
                bgColor = getComputedStyle(el).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                color = getComputedStyle(el).borderColor;  
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                pos = SUPER.pdf_get_native_el_position(el, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'F');
                args._pdf.line(pos.l, pos.t, pos.l+pos.w, pos.t);
                args._pdf.line(pos.l, pos.t+pos.h, pos.l+pos.w, pos.t+pos.h);
                // Minus button
                node = el.closest('.super-quantity').querySelector('.super-minus-button');
                var fontSize = parseFloat(getComputedStyle(node).fontSize);
                var fontSizePoint = fontSize * 0.67 * 1.5;
                args._pdf.setFontSize(fontSizePoint);
                color = getComputedStyle(node).getPropertyValue('background-color');  
                SUPER.pdf_rgba2hex(args, color, ['drawColor','fillColor']);
                pos = SUPER.pdf_get_native_el_position(node, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                args._pdf.setTextColor('white');
                args._pdf.text('-', pos.l+(pos.w/2), pos.t+(pos.h/2), {align: 'center', lineHeightFactor: args.lineHeight, baseline: 'middle', renderingMode: args.renderingMode}); 
                // Plus button
                node = el.closest('.super-quantity').querySelector('.super-plus-button');
                color = getComputedStyle(node).getPropertyValue('background-color');  
                SUPER.pdf_rgba2hex(args, color, ['drawColor','fillColor']);
                pos = SUPER.pdf_get_native_el_position(node, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                args._pdf.text('+', pos.l+(pos.w/2), pos.t+(pos.h/2), {align: 'center', lineHeightFactor: args.lineHeight, baseline: 'middle', renderingMode: args.renderingMode}); 
                continue;
            }
            // Slider
            if(el.closest('.super-slider')){
                // Track
                node = el.querySelector('.track');
                color = getComputedStyle(node).getPropertyValue('background-color');  
                SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                pos = SUPER.pdf_get_native_el_position(node, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'F');
                // Dragger
                node = el.querySelector('.dragger');
                color = getComputedStyle(node).backgroundColor;  
                SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                pos = SUPER.pdf_get_native_el_position(node, args);
                args._pdf.circle(pos.l+(pos.w/2), pos.t+(pos.h/2), pos.w/2, 'F');
            }
            // Color picker
            if(el.closest('.super-color')){
                // Wrapper
                node = el.closest('.super-color').querySelector('.sp-replacer');
                bgColor = getComputedStyle(node).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                color = getComputedStyle(node).borderColor;
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                pos = SUPER.pdf_get_native_el_position(node, args);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                // Draw input box
                node = el.closest('.super-color').querySelector('.sp-preview-inner');
                var ipos = SUPER.pdf_get_native_el_position(node, args);
                color = getComputedStyle(node).backgroundColor;  
                SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                color = getComputedStyle(node).borderColor;
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                args._pdf.rect(ipos.l, ipos.t, ipos.w, ipos.h, 'FD');
                // Triangle (open)
                var triangle = el.closest('.super-color').querySelector('.sp-dd');
                var tpos = SUPER.pdf_get_native_el_position(triangle, args);
                color = getComputedStyle(triangle).color;
                SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                args._pdf.triangle(
                    tpos.l, pos.t+(pos.h*0.366), // top left
                    tpos.l+(tpos.w/2), pos.t+(pos.h*0.566), // bottom mid
                    tpos.l+tpos.w, pos.t+(pos.h*0.366), // top right
                    'FD'
                );
                continue;
            }
            // Dropdown
            if(el.closest('.super-dropdown')){
                node = el.closest('.super-dropdown').querySelector('.super-dropdown-list');
                pos = SUPER.pdf_get_native_el_position(node, args);
                // Triangle (open)
                var triangle = el.closest('.super-dropdown').querySelector('.super-dropdown-arrow > span');
                var tpos = SUPER.pdf_get_native_el_position(triangle, args);
                color = getComputedStyle(triangle).color;
                SUPER.pdf_rgba2hex(args, color, ['drawColor', 'fillColor']);
                args._pdf.triangle(
                    tpos.l, pos.t+(pos.h*0.366), // top left
                    tpos.l+(tpos.w/2), pos.t+(pos.h*0.566), // bottom mid
                    tpos.l+tpos.w, pos.t+(pos.h*0.366), // top right
                    'FD'
                );
                // Draw input box
                bgColor = getComputedStyle(node).backgroundImage;
                if(bgColor!=='none'){
                    color = bgColor.split('(')[1]+'('+bgColor.split('(')[2].split(')')[0]+')'; //'linear-gradient(rgb(255, 255, 255) 25%, rgb(255, 255, 255) 100%)'
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                }
                color = getComputedStyle(node).borderColor;
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                continue;
            }
            // Radio / Checkbox
            if(el.closest('.super-radio') || el.closest('.super-checkbox')){
                if(el.closest('.super-radio')){
                    before = el.parentNode.querySelector('.super-before');
                    color = getComputedStyle(before).borderColor;  
                    borderWidth = parseFloat(getComputedStyle(before).borderWidth);
                    pos = SUPER.pdf_get_native_el_position(before, args);
                    SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                    args._pdf.setLineWidth(borderWidth*args.convertFromPixel);
                    args._pdf.circle(pos.l+(pos.w/2), pos.t+(pos.h/2), pos.w/2, 'D');
                    after = el.parentNode.querySelector('.super-after');
                    color = getComputedStyle(after).backgroundColor;  
                    pos = SUPER.pdf_get_native_el_position(after, args);
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                    args._pdf.circle(pos.l+(pos.w/2), pos.t+(pos.h/2), pos.w/2, 'F');
                }else{
                    before = el.parentNode.querySelector('.super-before');
                    color = getComputedStyle(before).borderColor;  
                    borderWidth = parseFloat(getComputedStyle(before).borderWidth);
                    pos = SUPER.pdf_get_native_el_position(before, args);
                    SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                    args._pdf.setLineWidth(borderWidth*args.convertFromPixel);
                    args._pdf.rect(pos.l, pos.t, pos.w, pos.h);
                    after = el.parentNode.querySelector('.super-after');
                    color = getComputedStyle(after).backgroundColor;  
                    pos = SUPER.pdf_get_native_el_position(after, args);
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                    args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'F');
                }
                continue;
            }
            // Toggle on/off switch
            if(el.closest('.super-toggle-switch')){
                // Tooltips
                // args._pdf.createAnnotation({ type: "text", title: "note", bounds: { x: 10, y: 10, w: 200, h: 80 },
                //     contents: "This is text annotation (closed by default)",
                //     open: false
                // });
                if(el.closest('.super-toggle-switch').classList.contains('super-active')){
                    node = el.closest('.super-toggle-switch').querySelector('.super-toggle-on');
                }else{
                    node = el.closest('.super-toggle-switch').querySelector('.super-toggle-off');
                }
                // Background
                color = getComputedStyle(node).backgroundColor;
                SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                node = el.closest('.super-toggle-switch');
                pos = SUPER.pdf_get_native_el_position(node, args);
                color = getComputedStyle(node).borderColor;
                SUPER.pdf_rgba2hex(args, color, ['drawColor']);

                var handle = el.closest('.super-toggle-switch').querySelector('.super-toggle-handle');
                var hpos = SUPER.pdf_get_native_el_position(handle, args);
                args._pdf.rect(pos.l, hpos.t, pos.w, pos.h, 'F');
                handle = el.closest('.super-toggle-switch').querySelector('.super-toggle-handle');
                color = getComputedStyle(handle).backgroundColor;
                SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                if(el.closest('.super-toggle-switch').classList.contains('super-active')){
                    args._pdf.rect(hpos.l, hpos.t, hpos.w/2, pos.h, 'F');
                }else{
                    args._pdf.rect(pos.l, hpos.t, hpos.w/2, pos.h, 'F');
                }
                // Draw the border around
                args._pdf.rect(pos.l, hpos.t, pos.w, pos.h, 'D');

                // Box
                //node = el.closest('.super-toggle-switch');
                //color = getComputedStyle(node).borderColor;
                //SUPER.pdf_rgba2hex(args, color, ['fillColor', 'drawColor']);
                //pos = SUPER.pdf_get_native_el_position(node, args);
                //args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'D');
                continue;
            }
            // Rating
            if(el.closest('.super-rating')){
                // Helper function
                args._pdf.polygon = function(points, scale, style, closed) {
                    var i, point, dx, dy, x1 = points[0][0], y1 = points[0][1], cx = x1, cy = y1, acc = [];
                    for(i=1; i<points.length; i++) { point = points[i]; dx = point[0]-cx; dy = point[1]-cy; acc.push([dx, dy]); cx += dx; cy += dy; }
                    this.lines(acc, x1, y1, scale, style, closed);
                }
                inodes = el.closest('.super-rating').querySelectorAll('.super-rating-star');
                for(z=0; z<inodes.length; z++){
                    pos = SUPER.pdf_get_native_el_position(inodes[z], args);
                    x = pos.l;
                    var y = pos.t;
                    var scale = 1;
                    width = pos.w*0.6;
                    x = x+(width*0.3);
                    var height = width*0.95;
                    y = y+(height*0.3);
                    var shared1 = (width*0.7+x)*scale,
                    shared2 = (width*0.35+y)*scale,
                    shared3 = ((width/2)-((width/4)/2)+x)*scale,
                    shared4 = ((width/2)+((width/4)/2)+x)*scale,
                    shared5 = ((width*0.5875)+y)*scale,
                    shared6 = (width/2+x)*scale,
                    shared7 = (width*0.725+y)*scale,
                    shared8 = (width*0.3625+y)*scale,
                    shared9 = (width*0.3+x)*scale;
                    // Border
                    bgColor = getComputedStyle(inodes[z]).backgroundColor;
                    SUPER.pdf_rgba2hex(args, bgColor, ['fillColor']);
                    color = getComputedStyle(inodes[z]).borderColor;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                    args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                    // Stars
                    color = getComputedStyle(inodes[z]).color;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor','fillColor']);
                    // Top triangle
                    args._pdf.triangle(shared6, y*scale, shared4, shared2, shared3, shared2, 'FD');
                    // Top right triangle
                    args._pdf.triangle((width*0.625+x)*scale, shared2, shared1, shared5, (width+x)*scale, shared8, 'FD');
                    // Bottom right triangle
                    args._pdf.triangle(shared1, shared5, shared6, shared7, (width*0.8125+x)*scale, (height+y)*scale, 'FD');
                    // Bottom left triangle
                    args._pdf.triangle(shared9, shared5, shared6, shared7, (width*0.1875+x)*scale, (height+y)*scale, 'FD');
                    // Top left triangle
                    args._pdf.triangle(shared3, shared2, shared9, shared5, (x)*scale, shared8, 'FD');
                    args._pdf.polygon([[shared3,shared2], [shared4, shared2], [shared1, shared5], [shared6, shared7], [shared9, shared5]], [1,1], 'F');
                }
                continue;
            }

            // Table
            if(el.tagName==='TABLE'){
                // Tables
                cells = el.querySelectorAll('th, td');
                for(x=0; x<cells.length; x++){
                    node = cells[x];
                    // Table border width 
                    borderWidth = parseFloat(getComputedStyle(el).borderWidth);
                    if(borderWidth===0) continue;
                    args._pdf.setLineWidth(borderWidth*args.convertFromPixel);
                    // Table border color 
                    color = getComputedStyle(el).borderColor;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                    // Table background color
                    color = getComputedStyle(el).backgroundColor;
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                    // Table position/width
                    pos = SUPER.pdf_get_native_el_position(el, args);
                    args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'FD');
                    // Background color of the cell
                    color = getComputedStyle(node).backgroundColor;
                    SUPER.pdf_rgba2hex(args, color, ['fillColor']);
                    // Position/width of the cell
                    pos = SUPER.pdf_get_native_el_position(node, args);
                    args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'F');
                }
                for(x=0; x<cells.length; x++){
                    node = cells[x];
                    // Cell border width
                    borderWidth = parseFloat(getComputedStyle(node).borderWidth);
                    if(borderWidth===0) continue;
                    args._pdf.setLineWidth(borderWidth*args.convertFromPixel);
                    // Border color of the cell
                    color = getComputedStyle(node).borderColor;
                    SUPER.pdf_rgba2hex(args, color, ['drawColor']);
                    pos = SUPER.pdf_get_native_el_position(node, args);
                    args._pdf.rect(pos.l, pos.t, pos.w, pos.h, 'D');
                }
                continue;
            }
        }
    };


    SUPER.pdf_generator_reset = function(form){
        // Only if not already canceled/reset
        if(form && !form.classList.contains('super-generating-pdf')){
            return false;
        }
        // Remove all 'super-pdf-text' span elements
        var nodes = form.querySelectorAll('span.super-pdf-text-node');
        for(var i=0; i < nodes.length; i++){
            nodes[i].replaceWith(nodes[i].textContent);
        }
        nodes = form.querySelectorAll('span.super-pdf-text');
        for(i=0; i < nodes.length; i++){
            nodes[i].replaceWith(nodes[i].textContent);
        }

        // Show scrollbar again
        // tmp no longer needed? document.documentElement.classList.remove('super-hide-scrollbar');
        var inlineStyle = document.querySelector('#super-generating-pdf');
        if(inlineStyle) inlineStyle.remove();
        // Make all mutli-parts invisible again (except for the last active multi-part)
        // Make all TABs invisible
        // Make all accordions invisible
        nodes = form.querySelectorAll('.super-multipart,.super-tabs-content,.super-accordion-item');
        for(var i=0; i < nodes.length; i++){
            if(!nodes[i].classList.contains('super-active-origin')){
                nodes[i].classList.remove('super-active');
            }else{
                nodes[i].classList.remove('super-active-origin');
            }
        }
        // Remove list markers (required for <li> items)
        nodes = form.querySelectorAll('.super-li-marker');
        for(var i=0; i < nodes.length; i++){
            nodes[i].remove();
        }
        // Reset ignore attributes
        nodes = document.body.querySelectorAll('[data-belongs-to-pages], [data-html2canvas-ignore="true"], .super-pdf-el-with-margin');
        for(i=0; i<nodes.length; i++){
            nodes[i].removeAttribute('data-html2canvas-ignore');
            //nodes[i].removeAttribute('data-html2canvas-fake-ignore');
            nodes[i].classList.remove('super-pdf-el-with-margin');
            nodes[i].style.marginTop = null
            delete nodes[i].dataset.belongsToPages;
        }
        // Reset currentPage offsets
        nodes = form.querySelectorAll('[data-offset-top]');
        for(i=0; i<nodes.length; i++){
            nodes[i].style.marginTop = null
            delete nodes[i].dataset.offsetTop;
        }
        // Reset any PDF page break heights
        nodes = form.querySelectorAll('.super-pdf_page_break');
        for(i=0; i<nodes.length; i++){
            nodes[i].style.height = '0px';
        }
        
        // @@@@@@@@@@@@@@
        // // Re-enable the UI for Maps and resize to original width
        // for(i=0; i < SUPER.google_maps_api.allMaps[$form_id].length; i++){
        //     SUPER.google_maps_api.allMaps[$form_id][i].setOptions({
        //         disableDefaultUI: false
        //     });
        //     var children = SUPER.google_maps_api.allMaps[$form_id][i]['super_el'].querySelectorAll(':scope > div');
        //     for(var x=0; x < children.length; x++){
        //         children[x].style.width = '';
        //         if(children[x].classList.contains('super-google-map-directions')){
        //             children[x].style.overflowY = 'scroll';
        //             children[x].style.height = SUPER.google_maps_api.allMaps[$form_id][i]['super_el'].querySelector('super-google-map-'+$form_id).offsetHeight+'px';
        //         }
        //     }
        // }
        // @@@@@@@@@@@@@@

        // Restore form position and remove the cloned form
        // Before removing cloned form, insert original form before cloned form
        // tmp form.querySelector('form').style.marginTop = '';
        SUPER.reset_submit_button_loading_state(form);
        var placeholder = document.querySelector('.super-pdf-placeholder');
        placeholder.parentNode.insertBefore(form, placeholder.nextSibling);
        form.classList.remove('super-generating-pdf');
        placeholder.remove();
        var pdfPageContainer = document.querySelector('.super-pdf-page-container');
        if(pdfPageContainer) pdfPageContainer.remove();
        // Restore temporary replaced fields back to original
        nodes = form.querySelectorAll('.super-pdf-tmp-text-field-placeholder');
        for( i=0; i < nodes.length; i++ ) {
            nodes[i].remove();
        }
        nodes = form.querySelectorAll('.super-pdf-tmp-replaced');
        for( i=0; i < nodes.length; i++ ) {
            nodes[i].classList.remove('super-pdf-tmp-replaced');
        }
        SUPER.init_super_responsive_form_fields({form: form});
    };
    SUPER.save_data = function(args){
        if(args.progressBar) args.progressBar.style.width = 0+'%';
        args.loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+super_common_i18n.loadingOverlay.processing+'</span>';
        var formData = new FormData();
        formData.append('action', 'super_submit_form');
        if(args.form_id) formData.append('form_id', args.form_id);
        if(args.entry_id) formData.append('entry_id', args.entry_id);
        if(args.list_id) formData.append('list_id', args.list_id);
        if(args.sf_nonce) formData.append('sf_nonce', args.sf_nonce);
        if(args.token) formData.append('token', args.token);
        if(args.version) formData.append('version', args.version);
        if(args.data) formData.append('data', JSON.stringify(args.data));
        if(args.fileUpload) formData.append('fileUpload', true);
        formData.append('i18n', (args.form.data('i18n') ? args.form.data('i18n') : '')); // @since 4.7.0 translation
        $.ajax({
            type: 'post',
            url: super_common_i18n.ajaxurl,
            data: formData,
            async: true,
            cache: false,
            contentType: false,
            processData: false,
            timeout: 60000*5, // 5m
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                if(args.showOverlay==="true"){
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = evt.loaded / evt.total;
                            //Do something with upload progress here
                            if(args.progressBar) args.progressBar.style.width = (100*percentComplete)+"%";  
                        }
                    }, false);
                }
                return xhr;
            },
            success: function(result){
                result = JSON.parse(result);
                if(result.error===true){
                    // If session expired, retry
                    if(result.type==='session_expired'){
                        // Generate new nonce
                        $.ajax({
                            url: super_common_i18n.ajaxurl,
                            type: 'post',
                            data: {
                                action: 'super_create_nonce'
                            },
                            success: function (nonce) {
                                // Update new nonce
                                args.sf_nonce = nonce.trim();
                                args.form0.querySelector('input[name="sf_nonce"]').value = nonce.trim();
                            },
                            complete: function(){
                                SUPER.save_data(args);
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                // eslint-disable-next-line no-console
                                console.log(xhr, ajaxOptions, thrownError);
                                alert('Could not generate nonce');
                            }
                        });


                    }else{
                        // Display error message
                        SUPER.form_submission_finished(args, result);
                    }
                }else{
                    // Update new nonce
                    if(result.response_data && result.response_data.sf_nonce){
                        args.form0.querySelector('input[name="sf_nonce"]').value = result.response_data.sf_nonce;
                    }
                    // Clear form progression (if enabled)
                    if( args.form[0].classList.contains('super-save-progress') ) {
                        SUPER.save_form_progress_request('', args.form_id);
                    }
                    // Trigger js hook and continue
                    SUPER.after_email_send_hook(args);
                    // If a hook is redirecting we should avoid doing other things
                    if(args.form.data('is-redirecting')){
                        // However if a hook is doing things in the back-end, we must check until finished
                        if(args.form.data('is-doing-things')){
                            clearInterval(SUPER.submit_form_interval);
                            SUPER.submit_form_interval = setInterval(function(){
                                if(args.form.data('is-doing-things')){
                                    // Still doing things...
                                }else{
                                    clearInterval(SUPER.submit_form_interval);
                                    // Form submission is finished
                                    SUPER.form_submission_finished(args, result);
                                }
                            }, 100);
                        }
                        return false; // Stop here, we are redirecting the form (used by Stripe)
                    }

                    // @since 2.2.0 - custom form POST method
                    if( (args.form.find('form').attr('method')=='post') && (args.form.find('form').attr('action')!=='') ){
                        args.form.find('form').submit(); // When doing custom POST, the form will redirect itself
                        return false;
                    }
                    // Form submission is finished
                    SUPER.form_submission_finished(args, result);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                // eslint-disable-next-line no-console
                console.log(xhr, ajaxOptions, thrownError);
                alert(super_common_i18n.errors.failed_to_process_data);
            }
        });
    };

    
    jQuery(document).ready(function ($) {
        
        var $doc = $(document);

        // Fix chrome autofill honeypot issue
        var $super_hp = $doc.find('input[name="super_hp"]');
        window.setInterval(function() {
            $super_hp.each(function(){
                var hasValue = $(this).val().length > 0; //Normal
                if(!hasValue){
                    if($(this).is("\\:-webkit-autofill")) {
                        hasValue = true;
                    }
                }
                if (hasValue) {
                    $super_hp.val('');
                }
            });
        }, 1000);

        // @since 3.1.0 - google distance calculation between 2 addresses
        //$doc.on('input change', '.super-form .super-text .super-distance-calculator:not(.super-address-autopopulate)', function(){
        ////$doc.on('change keyup keydown blur', '.super-form .super-text .super-distance-calculator:not(.super-address-autopopulate)', function(){
        //    var field = this;
        //    if(distance_calculator_timeout !== null) clearTimeout(distance_calculator_timeout);
        //    distance_calculator_timeout = setTimeout(function () {
        //        SUPER.calculate_distance({el: field});
        //    }, 1000);
        //});

        SUPER.init_resend_verification_code();

        SUPER.init_field_filter_visibility();
        $doc.on('change keyup keydown blur','.super-field.super-filter',function(){
            if(this.closest('.super-form-settings')){
                SUPER.init_field_filter_visibility($(this));
            }else{
                SUPER.init_field_filter_visibility($(this), 'element_settings');
            }
        });  

        $doc.on('click', '.super-field.super-currency',function(){
            var $field = $(this);
            var $form = $field.closest('.super-form');
            $form.find('.super-focus').removeClass('super-focus');
            $field.addClass('super-focus');
        });

        $doc.on('keyup', '.super-icon-search input', function(){
            var $value = $(this).val();
            var $icons = $(this).parents('.super-icon-field').children('.super-icon-list').children('i');
            if($value===''){
                $icons.css('display','inline-block');   
            }else{
                $icons.each(function(){
                    if($(this).is('[class*="'+$value+'"]')) {
                        $(this).css('display','inline-block');
                    }else{
                        $(this).css('display','none');
                    }
                });
            }
        });

        $doc.on('click','.super-icon-list i',function(){
            if($(this).hasClass('super-active')){
                $(this).parent().find('i').removeClass('super-active');
                $(this).parents('.super-icon-field').find('input').val('');
            }else{
                $(this).parent().find('i').removeClass('super-active');
                $(this).parents('.super-icon-field').find('input').val($(this).attr('class'));
                $(this).addClass('super-active');
            }
        });

        var timeout = null;
        $doc.on('keyup paste', '.super-text .super-shortcode-field[data-search="true"]', function(){ 
            var field = this;
            if (timeout !== null) clearTimeout(timeout);
            timeout = setTimeout(function () {
                
                SUPER.populate_form_data_ajax({el: field});
            }, 1000);
        });

        // WooCommerce Order Search Method (wcosm)
        var timeout2 = null;
        $doc.on('keyup', '.super-text .super-shortcode-field[data-wcosm]', function(){ 
            var field = this;
            if (timeout2 !== null) {
                clearTimeout(timeout2);
            }
            timeout2 = setTimeout(function () {
                SUPER.populate_form_order_data_ajax({el: field});
            }, 1000);
        });
    });
})(jQuery);