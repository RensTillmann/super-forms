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

window.SUPER = {};
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
        args.data = SUPER.after_form_data_collected_hook(args.data);

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
        args.loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+super_common_i18n.loadingOverlay.processing+'</span>';
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
        var html = '', classes = '';
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
            if (f.name.length > 10) f.name = f.name.substring(0, 10)+'...';
            if(uploaded){
                html += '<a href="'+fileUrl+'" target="_blank">'+f.name+'.'+f.ext+'</a>';
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
            timeout: 60000, // 1m
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
                    // Display error message
                    SUPER.form_submission_finished(args, result);
                }else{
                    var i, uploadedFiles, updateHtml=[], html=[], activeFiles, fieldWrapper, filesWrapper, field, file, files = result;
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
                        for(i=0; i<field.files.length; i++){
                            file = field.files[i];
                            updateHtml[fieldName].html += SUPER.get_single_uploaded_file_html(false, false, file.value, file.type, file.url);
                            if(args.data[fieldName]){
                                args.data[fieldName]['files'][i]['value'] = file.value;
                                args.data[fieldName]['files'][i]['type'] = file.type;
                                args.data[fieldName]['files'][i]['url'] = file.url;
                                if(file.subdir) {
                                    args.data[fieldName]['files'][i]['subdir'] = file.subdir;
                                }
                                if(file.attachment) {
                                    args.data[fieldName]['files'][i]['attachment'] = file.attachment;
                                }
                            }
                        }
                        filesWrapper.innerHTML = html;
                    });
                    Object.keys(updateHtml).forEach(function(fieldName) {
                        updateHtml[fieldName].filesWrapper.innerHTML = updateHtml[fieldName].html;
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
    SUPER.process_form_data = function(args){
        args.generatePdf = false;
        args.pdfSettings = null;
        if( typeof SUPER.form_js !== 'undefined' && 
            typeof SUPER.form_js[args.form_id] !== 'undefined' && 
            typeof SUPER.form_js[args.form_id]._pdf !== 'undefined' && 
            SUPER.form_js[args.form_id]._pdf.generate === "true" ) {
                args.generatePdf = true;
                args.pdfSettings = SUPER.form_js[args.form_id]._pdf;
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
                if(args.pdfSettings.debug==="true"){
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
                }
                SUPER.save_data(args); 
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
        if(target && target.closest('.super-initialized')){
            target.classList.add('super-form-focussed');
            target.tabIndex = -1;
            SUPER.lastFocussedForm = target;
        }
    };
    // Reset focussed fields
    SUPER.resetFocussedFields = function(){
        var i, nodes = document.querySelectorAll('.super-focus');
        for(i=0; i<nodes.length; i++){
            nodes[i].classList.remove('super-focus');
        }
    };
    // Focus field
    SUPER.focusField = function(target){
        // Only when form is initialized
        if(target && target.closest('.super-initialized')){
            SUPER.resetFocussedFields();
            if(target.classList.contains('super-field')){
                target.classList.add('super-focus');
            }else{
                if(target.closest('.super-field')) {
                    target.closest('.super-field').classList.add('super-focus');
                }
            }
        }
    };
    SUPER.focusNextTabField = function(e, next, form, skipNext){
        var i, nodes, parentTabElement, tabsElement, menuWrapper, menuNodes, contentsWrapper, contentNodes, keyCode = -1;
        if(e) keyCode = e.keyCode || e.which;
         
        if(typeof skipNext !== 'undefined'){
            next = skipNext;
        }else{
            next = SUPER.nextTabField(e, next, form);
        }
        if(!next) return false;

        // Only for front-end and/or live preview, but not builder mode
        if(next.closest('.super-preview-elements')){
            return false;
        }
        
        if(next.classList.contains('super-item')){
            next = next.closest('.super-field');
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
            next.scrollIntoView({behavior: "auto", block: "center", inline: "center"});
        }
        if(keyCode != 32){
            // If not Space key press
            nodes = form.querySelectorAll('.super-focus');
            for ( i = 0; i < nodes.length; i++){
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
                    if(e.shiftKey){
                        innerNodes[innerNodes.length-1].classList.add('super-focus');
                    }else{
                        innerNodes[0].classList.add('super-focus');
                    }
                }
            }
            e.preventDefault();
            return false;
        }
        if( next.classList.contains('super-form-button') ) {
            next.classList.add('super-focus');
            SUPER.init_button_hover_colors( next );
            next.querySelector('.super-button-wrap').focus();
            e.preventDefault();
            return false;
        }
        if( next.classList.contains('super-color')) {
            next.classList.add('super-focus');
            $(next.querySelector('.super-shortcode-field')).spectrum('show');
            e.preventDefault();
            return false;
        }
        if( next.classList.contains('super-keyword-tags')) {
            next.classList.add('super-focus');
            next.querySelector('.super-keyword-filter').focus();
            e.preventDefault();
            return false;
        }
        if( next.classList.contains('super-dropdown') ) {
            next.classList.add('super-focus');
            next.classList.add('super-open');
            if(next.querySelector('input[name="super-dropdown-search"]')){
                next.querySelector('input[name="super-dropdown-search"]').focus();
                e.preventDefault();
                return false;
            }
        }else{
            next.classList.add('super-focus');
        }
        if(next.querySelector('.super-shortcode-field')){
            next.querySelector('.super-shortcode-field').focus();
        }
        e.preventDefault();
        return false;
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
        // If no regex was defined return all field just by their exact name match
        if(name!=='' && regex==='') return form.querySelector('.super-shortcode-field:not(.super-fileupload)[name="'+name+'"], .super-active-files[name="'+name+'"]');
        // If regex is set to 'all' we want to search for multiple fields
        // This is currently being used by the builder to determine duplicate field names
        if(name!=='' && regex=='all') return form.querySelectorAll('.super-shortcode-field:not(.super-fileupload)[name="'+name+'"], .super-active-files[name="'+name+'"]');
        // If a regex is defined, search for fields based on the regex
        return form.querySelectorAll('.super-shortcode-field:not(.super-fileupload)[name'+regex+'="'+name+'"], .super-active-files[name="'+name+'"]');
    };
    SUPER.fields = function(form, selector){
        return form.querySelectorAll(selector);
    };
    SUPER.fieldsByName = function(form, name){
        if(name==='') return null; // Skip empty names due to "translation mode"
        return form.querySelectorAll('.super-shortcode-field:not(.super-fileupload)[name="'+name+'"], .super-active-files[name="'+name+'"]');
    };
    
    SUPER.has_hidden_parent = function(changedField, includeMultiParts){
        if(changedField[0]) changedField = changedField[0];
        
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
                $(this).removeClass('finished');
                $(this).parents('.super-field-wrapper:eq(0)').find('.super-fileupload-files > div.error').remove();
                data.context = $('<div/>').appendTo($(this).parents('.super-field-wrapper:eq(0)').find('.super-fileupload-files'));
                var field = $(this).parents('.super-field-wrapper:eq(0)').find('.super-active-files')[0];
                var fieldName = $(this).parents('.super-field-wrapper:eq(0)').find('.super-active-files').attr("name");
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
                        alert(super_common_i18n.errors.file_upload.upload_limit_reached);
                    }else{
                        var f = SUPER.get_file_name_and_extension(file.name);
                        if( (file_types_object.indexOf(f.ext)!=-1) || (accepted_file_types==='') ) {
                            el.data('total-file-sizes', total);
                            data.context.parent('div').children('div[data-name="'+file.name+'"]').remove();
                            if(typeof SUPER.files[formId] === 'undefined'){
                                SUPER.files[formId] = [];
                            }
                            if(typeof SUPER.files[formId][fieldName] === 'undefined'){
                                SUPER.files[formId][fieldName] = [];
                            }
                            if(file.type && file.type.indexOf("image/") === 0){
                                var src = URL.createObjectURL(file)
                            }
                            var totalFiles = SUPER.files[formId][fieldName].length;
                            SUPER.files[formId][fieldName][totalFiles] = file; //SUPER.files[formId][totalFiles] = file;
                            SUPER.files[formId][fieldName][totalFiles]['url'] = src; // blob
                            var html = SUPER.get_single_uploaded_file_html(true, false, file.name, file.type, src);
                            data.context.data(data).attr('data-name',file.name).attr('title',file.name).attr('data-type',file.type).html(html);
                            data.context.data('file-size',file.size);
                            if(data.context[0].querySelector('img')){
                                var img = data.context[0].querySelector('img');
                                img.onload = function(){
                                    //URL.revokeObjectURL(img.src); // free memory
                                }
                            }
                            SUPER.after_field_change_blur_hook({el: field, form: form});
                            //SUPER.init_replace_html_tags({el: el, form: form}); //undefined, form);
                        }else{
                            data.context.remove();
                            alert(super_common_i18n.errors.file_upload.incorrect_file_extension);
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
                        adaptivePlaceholder.dataset.placeholder = selectedCountryPlaceholder;
                        adaptivePlaceholder.querySelector('span').innerHTML = selectedCountryPlaceholder;
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
        if(!args.el) return false;
        if(args.el.classList.contains('super-distance-calculator')){
            var form = SUPER.get_frontend_or_backend_form(args),
                $method = args.el.dataset.distanceMethod,
                $origin_field,
                $origin,
                $destination_field,
                $destination,
                $value,
                $units,
                $result,
                $leg,
                $field,
                $calculation_value,
                $html,
                $alert_msg;
            if($method=='start'){
                $origin_field = args.el;
                $origin = args.el.value;
                $destination = args.el.dataset.distanceDestination;
                if(SUPER.field_exists(form, $destination)){
                    $destination_field = SUPER.field(form, $destination);
                    $destination = ($destination_field ? $destination_field.value : '');
                }
            }else{
                $origin_field = SUPER.field(form, args.el.dataset.distanceStart);
                $origin = ($origin_field ? $origin_field.value : '');
                $destination_field = args.el;
                $destination = args.el.value;
            }
            $value = $origin_field.dataset.distanceValue;
            $units = $origin_field.dataset.distanceUnits;
            if($value!='dis_text'){
                $units = 'metric';
            }
            if( ($origin==='') || ($destination==='') ) {
                return true;
            }
            if(distance_calculator_timeout !== null){
                clearTimeout(distance_calculator_timeout);
            }
            distance_calculator_timeout = setTimeout(function () {
                args.el.closest('.super-field-wrapper').classList.add('super-calculating-distance');
                $.ajax({
                    url: super_common_i18n.ajaxurl,
                    type: 'post',
                    data: {
                        action: 'super_calculate_distance',
                        units: $units,
                        origin: $origin,
                        destination: $destination
                    },
                    success: function (result) {
                        $result = JSON.parse(result);
                        if($result.status=='OK'){
                            $leg = $result.routes[0].legs[0];
                            $field = $origin_field.dataset.distanceField;
                            // distance  - Distance in meters
                            if( $value=='distance' ) {
                                $calculation_value = $leg.distance.value;
                            }
                            // dis_text  - Distance text in km or miles
                            if( $value=='dis_text' ) {
                                $calculation_value = $leg.distance.text;
                            }
                            // duration  - Duration in seconds
                            if( $value=='duration' ) {
                                $calculation_value = $leg.duration.value;
                            }
                            // dur_text  - Duration text in minutes
                            if( $value=='dur_text' ) {
                                $calculation_value = $leg.duration.text;
                            }
                            $field = SUPER.field(form, $field);
                            $field.value = $calculation_value;
                            if($calculation_value===''){
                                $field.closest('.super-shortcode').classList.remove('super-filled');
                            }else{
                                $field.closest('.super-shortcode').classList.add('super-filled');
                            }
                            SUPER.after_field_change_blur_hook({el: $field});
                            SUPER.init_replace_html_tags({el: $field, form: form}); //undefined, form);
                        }else{
                            if($result.status=='ZERO_RESULTS'){
                                $alert_msg = super_common_i18n.errors.distance_calculator.zero_results;
                            }else{
                                if($result.status=='OVER_QUERY_LIMIT'){
                                    $alert_msg = $result.error_message;
                                }else{
                                    if($result.error===true){
                                        $alert_msg = $result.msg;
                                    }else{
                                        $alert_msg = super_common_i18n.errors.distance_calculator.error;
                                    }
                                }
                            }
                            $('.super-msg').remove();
                            $result = JSON.parse(result);
                            $html = '<div class="super-msg super-error">';                            
                            $origin_field.blur();
                            if(typeof $destination_field !== 'undefined') $destination_field.blur();
                            $html += $alert_msg;
                            $html += '<span class="super-close"></span>';
                            $html += '</div>';
                            $($html).prependTo($(form));
                            $('html, body').animate({
                                scrollTop: $(form).offset().top-200
                            }, 1000);
                        }
                    },
                    complete: function(){
                        args.el.closest('.super-field-wrapper').classList.remove('super-calculating-distance');
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        // eslint-disable-next-line no-console
                        console.log(xhr, ajaxOptions, thrownError);
                        alert(super_common_i18n.errors.failed_to_process_data);
                    }
                });
            }, 1000);
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
        if( (typeof $parent !== 'undefined') && ( ($parent.hasClass('super-dropdown')) || ($parent.hasClass('super-checkbox')) ) ) {
            var $values = $value.toString().split(',');
            var $new_values = '';
            $.each($values, function( index, value ) {
                var $value = value.toString().split(';');
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
        switch(v.logic) {
          case 'equal':
            if( v.value==$shortcode_field_value ) $i++;
            break;
          case 'not_equal':
            if( v.value!=$shortcode_field_value ) $i++;
            break;
          case 'greater_than':
            if( parseFloat($shortcode_field_value)>parseFloat(v.value) ) $i++;
            break;
          case 'less_than':
            if( parseFloat($shortcode_field_value)<parseFloat(v.value) ) $i++;
            break;
          case 'greater_than_or_equal':
            if( parseFloat($shortcode_field_value)>=parseFloat(v.value) ) $i++;
            break;
          case 'less_than_or_equal':
            if( parseFloat($shortcode_field_value)<=parseFloat(v.value) ) $i++;
            break;
          case 'contains':
            if( (typeof $parent !== 'undefined') && (
                $parent.classList.contains('super-checkbox') || 
                $parent.classList.contains('super-radio') || 
                $parent.classList.contains('super-dropdown')) ) {
                $checked = $shortcode_field_value.split(',');
                $string_value = v.value.toString();
                Object.keys($checked).forEach(function(key) {
                    if( $checked[key].indexOf($string_value) >= 0) {
                        $i++;
                        return false;
                    }
                });
            }else{
                // If other field
                if( $shortcode_field_value.indexOf(v.value) >= 0) $i++;
            }
            break;
          case 'not_contains':
            if( (typeof $parent !== 'undefined') && (
                $parent.classList.contains('super-checkbox') || 
                $parent.classList.contains('super-radio') || 
                $parent.classList.contains('super-dropdown')) ) {
                $checked = $shortcode_field_value.split(',');
                $string_value = v.value.toString();
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
                if( $shortcode_field_value.indexOf(v.value) == -1) $i++;
            }
            break;

          default:
          // code block
        }
        if( v.and_method!=='' ) {
            switch(v.logic_and) {
              case 'equal':
                if( v.value_and==$shortcode_field_and_value ) $i++;
                break;
              case 'not_equal':
                if( v.value_and!=$shortcode_field_and_value ) $i++;
                break;
              case 'greater_than':
                if( parseFloat($shortcode_field_and_value)>parseFloat(v.value_and) ) $i++;
                break;
              case 'less_than':
                if( parseFloat($shortcode_field_and_value)<parseFloat(v.value_and) ) $i++;
                break;
              case 'greater_than_or_equal':
                if( parseFloat($shortcode_field_and_value)>=parseFloat(v.value_and) ) $i++;
                break;
              case 'less_than_or_equal':
                if( parseFloat($shortcode_field_and_value)<=parseFloat(v.value_and) ) $i++;
                break;
              case 'contains':
                if( (typeof $parent_and !== 'undefined') && ( 
                    $parent_and.classList.contains('super-checkbox') || 
                    $parent_and.classList.contains('super-radio') || 
                    $parent_and.classList.contains('super-dropdown')) ) {
                    $checked = $shortcode_field_and_value.split(',');
                    $string_value = v.value_and.toString();
                    Object.keys($checked).forEach(function(key) {
                        if( $checked[key].indexOf($string_value) >= 0) {
                            $i++;
                            return false;
                        }
                    });
                }else{
                    // If other field
                    if( $shortcode_field_and_value.indexOf(v.value_and) >= 0) $i++;
                }
                break;
              case 'not_contains':
                if( (typeof $parent_and !== 'undefined') && ( 
                    $parent_and.classList.contains('super-checkbox') || 
                    $parent_and.classList.contains('super-radio') || 
                    $parent_and.classList.contains('super-dropdown')) ) {
                    $checked = $shortcode_field_and_value.split(',');
                    $string_value = v.value_and.toString();
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
                    if( $shortcode_field_and_value.indexOf(v.value_and) == -1) $i++;
                }
                break;
              default:
              // code block
            }
        }
        // When we are checking for variable condition return on matches
        if($variable) return $i;
        // When we are checking conditional logic then we need to know the total matches as a whole, because we have a method (One/All)
        if( v.and_method=='and' ) {
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
            regex = /{([^\\\/\s"'+]*?)}/g;
            name = regex.exec(value);
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
            var $sum = 0,
                $selected;
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
        args.regex = /{([^\\\/\s"'+]*?)}/g;
        var v,
            $v,
            $this,
            $json,
            $wrapper,
            $field,
            $trigger,
            $action,
            $conditions,
            $total,
            $regex = /{([^\\\/\s"'+]*?)}/g,
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
            $validation_error = false;

        Object.keys(args.conditionalLogic).forEach(function(key) {
            $prev_match_found = false;
            $this = args.conditionalLogic[key];
            args.currentTextarea = $this;
            $wrapper = $this.closest('.super-shortcode');
            $field = $wrapper.querySelector('.super-shortcode-field');
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
                $conditions = JSON.parse($json);
                if($conditions){
                    $total = 0;
                    $match_found = 0;
                    Object.keys($conditions).forEach(function(key) {
                        if(!$prev_match_found){
                            $total++;
                            v = $conditions[key];
                            // @since 3.5.0 - make sure {tags} are replaced with the correct field value to check conditional logic
                            args.value = v.value;
                            v.value = SUPER.update_variable_fields.replace_tags(args);
                            args.value = v.value_and;
                            v.value_and = SUPER.update_variable_fields.replace_tags(args);
                            args.value = v.field;
                            args.bwc = true;
                            $shortcode_field_value = SUPER.update_variable_fields.replace_tags(args);
                            args.value = v.field_and;
                            args.bwc = true;
                            $shortcode_field_and_value = SUPER.update_variable_fields.replace_tags(args);
                            delete args.bwc;
                            $continue = false;
                            $continue_and = false;
                            $skip = false;
                            $skip_and = false;
                            // If conditional field selectors don't contain curly braces, then append and prepend them for backwards compatibility
                            if(v.field!=='' && v.field.indexOf('{')===-1) v.field = '{'+v.field+'}';
                            if(typeof v.field_and !== 'undefined' && v.field_and!=='' && v.field_and.indexOf('{')===-1) v.field_and = '{'+v.field_and+'}';

                            while (($v = $regex.exec(v.field)) !== null) {
                                // This is necessary to avoid infinite loops with zero-width matches
                                if ($v.index === $regex.lastIndex) {
                                    $regex.lastIndex++;
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
                                $skip = SUPER.has_hidden_parent($shortcode_field);
                                $parent = $shortcode_field.closest('.super-shortcode');
                            }
                            if(v.and_method!==''){ 
                                if(v.and_method==='and' && $continue) return;
                                while (($v = $regex.exec(v.field_and)) !== null) {
                                    // This is necessary to avoid infinite loops with zero-width matches
                                    if ($v.index === $regex.lastIndex) {
                                        $regex.lastIndex++;
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
                                    $skip_and = SUPER.has_hidden_parent($shortcode_field_and);
                                    $parent_and = $shortcode_field_and.closest('.super-shortcode');
                                }
                                if(v.and_method==='or' && !$continue_and){
                                    $continue = false;
                                }
                            }
                            if($continue || $continue_and) return;
                            if( (v.and_method==='and' && ($skip || $skip_and) && !$is_variable) ||
                               (v.and_method==='or' && ($skip && $skip_and) && !$is_variable) ) {
                                // Exclude conditionally
                            }else{
                                $shortcode_field_value = SUPER.return_dynamic_tag_value($($parent), $shortcode_field_value);
                                $shortcode_field_and_value = SUPER.return_dynamic_tag_value($($parent_and), $shortcode_field_and_value);
                                if(!$shortcode_field_value) $shortcode_field_value = '';
                                if(!$shortcode_field_and_value) $shortcode_field_and_value = '';
                                // Generate correct value before checking conditional logic
                                $shortcode_field_value = SUPER.conditional_logic.get_field_value(v.logic, $shortcode_field_value, $shortcode_field, $parent);
                                // Generate correct and value before checking conditional logic
                                if(v.and_method!==''){ 
                                    $shortcode_field_and_value = SUPER.conditional_logic.get_field_value(v.logic_and, $shortcode_field_and_value, $shortcode_field_and, $parent_and);
                                }
                                if($is_variable){
                                    $match_found = SUPER.conditional_logic.match_found(0, v, $shortcode_field_value, $shortcode_field_and_value, $parent , $parent_and, true);
                                    if( v.and_method=='and' ) {
                                        if($match_found>=2) {
                                            $prev_match_found = true;
                                            if( v.new_value!=='' ) {
                                                args.value = v.new_value;
                                                v.new_value = SUPER.update_variable_fields.replace_tags(args);
                                            }
                                            $field.value = v.new_value;
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
                                            if( v.new_value!=='' ) {
                                                args.value = v.new_value;
                                                v.new_value = SUPER.update_variable_fields.replace_tags(args);
                                            }
                                            $field.value = v.new_value;
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
                                    $updated_variable_fields[$field.name] = $field;
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
                            // Hide wrappers
                            Object.keys($hide_wrappers).forEach(function() {
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
                                    // Make sure signatures resizes/refreshes after becoming visible
                                    if(typeof SUPER.refresh_signatures === 'function'){
                                        SUPER.refresh_signatures('', $show_wrappers[key]);
                                    }
                                    // Resize toggle elements
                                    SUPER.resize_toggle_element($show_wrappers[key]);
                                    // Reposition slider dragger
                                    SUPER.reposition_slider_element($show_wrappers[key], true);
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
            return $validation_error;
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
    };

    // @since 5.0.120 Filter foreach() statements
    SUPER.filter_foreach_statements = function($htmlElement, $counter, $depth, $html, $fileLoopRows, formId, originalFormReference){
        // Before we continue replace any foreach(file_upload_fieldname)
        var regex = /(<%|{|foreach\()([-_a-zA-Z0-9]{1,})(\[.*?\])?(_\d{1,})?(?:;([a-zA-Z0-9]{1,}))?(%>|}|\):)/g;
        var m;
        var $originalHtml = $html;
        var replaceTagsWithValue = {};
        //replaceTagsWithValue['<%counter%>'] = '';
        while ((m = regex.exec($html)) !== null) {
            // This is necessary to avoid infinite loops with zero-width matches
            if (m.index === regex.lastIndex) {
                regex.lastIndex++;
            }
            var $o = (m[0] ? m[0] : ''); // original name
            var $start = (m[1] ? m[1] : ''); // starts with e.g: foreach( or <%
            var $n = (m[2] ? m[2] : ''); // name
            var $d = (m[3] ? m[3] : ''); // depth
            var $dr = $d.replace(/[0-9]/g, "0") // depth reset to 0
            var $c = (m[4] ? m[4] : ''); // counter e.g: _2 or _3 etc.
            var $s = (m[5] ? m[5] : ''); // suffix
            if($s!=='') $s = ';'+$s;
            var $end = (m[6] ? m[6] : ''); // ends with e.g: ): or %>
            var fieldType = SUPER.get_field_type(originalFormReference, $n+$d);
            if(fieldType.type==='file'){
                var childParentIndex = $($htmlElement).parents('.super-duplicate-column-fields:eq(0)').index();
                if(childParentIndex>0){
                    $html = $html.replaceAll('foreach('+$n+$d+')', 'foreach('+$n+$d+'_'+(childParentIndex+1)+')');
                }
            }
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
            $ii = 0,
            $rows = '';

        var currentField = SUPER.field(originalFormReference, $field_name);
        var fieldType = SUPER.get_field_type(originalFormReference, $field_name);
        while( currentField ) {
            var currentFieldParent = $(currentField).parents('.super-duplicate-column-fields:eq(0)');
            var regex = /(<%|{|foreach\()([-_a-zA-Z0-9]{1,})(\[.*?\])?(_\d{1,})?(?:;([a-zA-Z0-9]{1,}))?(%>|}|\):)/g;
            var m;
            $row = $original;
            var replaceTagsWithValue = {};
            //replaceTagsWithValue['<%counter%>'] = '';
            while ((m = regex.exec($row)) !== null) {
                // This is necessary to avoid infinite loops with zero-width matches
                if (m.index === regex.lastIndex) {
                    regex.lastIndex++;
                }
                var $o = (m[0] ? m[0] : ''); // original name
                var $start = (m[1] ? m[1] : ''); // starts with e.g: foreach( or <%
                var $n = (m[2] ? m[2] : ''); // name
                var $d = (m[3] ? m[3] : ''); // depth
                var $dr = $d.replace(/[0-9]/g, "0") // depth reset to 0
                var $c = (m[4] ? m[4] : ''); // counter e.g: _2 or _3 etc.
                var $s = (m[5] ? m[5] : ''); // suffix
                if($s!=='') $s = ';'+$s;
                var $end = (m[6] ? m[6] : ''); // ends with e.g: ): or %>
                var fieldType = SUPER.get_field_type(originalFormReference, $field_name);
                //replaceTagsWithValue[$o] = $start+$n+levels+$c+$s+$end;
                //var dynamicColumnIndex = $(fieldType.field).parents('.super-duplicate-column-fields:eq(0)').index()+1;
                //replaceTagsWithValue['<%counter%>'] = dynamicColumnIndex;
                if(fieldType.type==='file'){
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
                                replaceTagsWithValue['<%attachment_id%>'] = files[x].name;
                                replaceTagsWithValue['<%attachment%>'] = files[x].name;
                                var key;
                                $row = $original;
                                for(key in replaceTagsWithValue) {
                                    $row = $row.replaceAll(key, replaceTagsWithValue[key]);
                                }
                                $rows += $row;
                            }
                            replaceTagsWithValue = [];
                            $row = '';
                        }
                    }
                }else{
                    var findChildField = $(currentFieldParent).find('.super-shortcode-field[data-oname="'+$n+'"][data-olevels="'+$dr+'"]').first();
                    if(findChildField.length===0) {
                        continue;
                    } 
                    var allParents = $(findChildField).parents('.super-duplicate-column-fields');
                    var childParentIndex = $(findChildField).parents('.super-duplicate-column-fields:eq(0)').index();
                    var suffix = [];
                    $(allParents).each(function(key){
                        var currentParentIndex = $(this).index();  // e.g: 0, 1, 2
                        if(key===0 && currentParentIndex===0){
                            return;
                        }
                        suffix.push('['+currentParentIndex+']');
                    });
                    if(childParentIndex!==0){
                        delete suffix[0];
                    }
                    var levels = suffix.reverse().join('');
                    if(childParentIndex!==0){
                        //replaceTagsWithValue['<%counter%>'] = (childParentIndex+1);
                        replaceTagsWithValue[$o] = $start+$n+levels+'_'+(childParentIndex+1)+$s+$end;
                        if($start!=='foreach('){
                            replaceTagsWithValue['<%counter%>'] = $start+$n+levels+'_'+(childParentIndex+1)+';index'+$end;
                        }
                        continue;
                    }
                    replaceTagsWithValue[$o] = $start+$n+levels+$c+$s+$end;
                    if($start!=='foreach('){
                        replaceTagsWithValue['<%counter%>'] = $start+$n+levels+$c+';index'+$end;
                    }
                    //$row = $row.replaceAll('<%counter%>', '<%'+$field_name+';index%>');
                }
            }
            var key;
            for(key in replaceTagsWithValue) {
                $row = $row.replaceAll(key, replaceTagsWithValue[key]);
            }
            if($htmlElement.closest('.super-duplicate-column-fields')){
                $rows += $row;
                break;
            }else{
                $i++;
                $field_name = $original_field_name+'_'+$i;
                currentField = SUPER.field(originalFormReference, $field_name);
                if(currentField){
                    //$row = $row.replaceAll('<%counter%>', '<%'+$field_name+';index%>');
                    //var dynamicColumnIndex = $(currentField).parents('.super-duplicate-column-fields:eq(0)').index()+1;
                    //$row = $row.replaceAll('<%counter%>', dynamicColumnIndex);
                }
                $rows += $row;
            }
        }

        $rows = $prefix + $rows + $suffix;
        $innerContent = $innerContent.split($original).join($rows);
        $ii++;
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
            args.conditionalLogic = args.form.querySelectorAll('.super-variable-conditions[data-fields*="{'+SUPER.get_original_field_name(args.el)+'}"]');
        }else{
            args.conditionalLogic = args.form.querySelectorAll('.super-variable-conditions');
        }
        if(typeof args.conditionalLogic !== 'undefined'){
            if(args.conditionalLogic.length!==0){
                SUPER.conditional_logic.loop(args);
            }
        }
    };

    // @since 3.0.0 - replace variable field {tags} with actual field values
    SUPER.update_variable_fields.replace_tags = function(args){
        if(args.form.classList.contains('super-generating-pdf')){
            // Must reference to original form (which is currently the placeholder)
            var formId = parseInt(args.form.id.replace('super-form-', ''), 10);
            args.form = document.querySelector('#super-form-'+formId+'-placeholder');
        }
        if(typeof args.defaultValues === 'undefined') args.defaultValues = false;
        if(typeof args.bwc === 'undefined') args.bwc = false;
        if(typeof args.target === 'undefined') args.target = null;
        if(typeof args.value !== 'undefined' && args.bwc){
            // If field name is empty do nothing
            if(args.value==='') return '';
            // If field name doesn't contain any curly braces, then append and prepend them and continue;
            if(args.value.indexOf('{')===-1) {
                args.value = '{'+args.value+'}';   
            } 
        }
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
            $element,
            $regex = /{([^\\\/\s"'+]*?)}/g;

        while (($match = $regex.exec(args.value)) !== null) {
            if($match[0]==='{}') continue;
            $array[$i] = $match[1];
            $i++;
        }
        for ($i = 0; $i < $array.length; $i++) {
            $element = undefined; // @important!
            $name = $array[$i];
            if($name=='pdf_page' && typeof SUPER.pdf_tags !== 'undefined' ){
                return SUPER.pdf_tags.pdf_page;
            }
            if($name=='pdf_total_pages' && typeof SUPER.pdf_tags !== 'undefined' ){
                return SUPER.pdf_tags.pdf_total_pages;
            }
            if($name=='dynamic_column_counter'){
                
                if(args.target){
                    
                    args.value = $(args.target).parents('.super-duplicate-column-fields:eq(0)').index()+1;
                    return args.value;
                }else{
                    if(args.currentTextarea){
                        
                        args.value = $(args.currentTextarea).parents('.super-duplicate-column-fields:eq(0)').index()+1;
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
            if($element){
                if($element[0]) $element = $element[0];
                // Check if parent column or element is hidden (conditionally hidden)
                if( SUPER.has_hidden_parent($element) ) {
                    // Exclude conditionally
                    // Lets just replace the field name with 0 as a value
                    args.value = args.value.replace('{'+$old_name+'}', $default_value);
                }else{
                    $parent = $element.closest('.super-shortcode');
                    if( !$element ) {
                        // Lets just replace the field name with 0 as a value
                        args.value = args.value.replace('{'+$old_name+'}', $default_value);
                    }else{
                        $text_field = true;
                        $parent = $element.closest('.super-field');

                        if($value_n=='index') {
                            var dynamicParentIndex = $($element).parents('.super-duplicate-column-fields:eq(0)').index();
                            args.value = args.value.replace('{'+$old_name+'}', dynamicParentIndex+1);
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
                                    $new_value = $selected[key].textContent;
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
                                // Check contains semicolon as seperator, but also check if it doesn't contain a double quote,
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
                                    files, formId = parseInt(args.form.id.replace('super-form-', ''), 10);
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
                        if($parent.classList.contains('super-date')){
                            $text_field = false;
                            $value = $element.value;
                            if($value_n === 'day' || $value_n === 'month' || $value_n === 'year' || $value_n === 'timestamp'){
                                if($value_n === 'day'){
                                    $value = ($element.getAttribute('data-math-day')) ? parseFloat($element.getAttribute('data-math-day')) : 0;
                                }
                                if($value_n === 'month'){
                                    $value = ($element.getAttribute('data-math-month')) ? parseFloat($element.getAttribute('data-math-month')) : 0;
                                }
                                if($value_n === 'year'){
                                    $value = ($element.getAttribute('data-math-year')) ? parseFloat($element.getAttribute('data-math-year')) : 0;
                                }
                                if($value_n === 'timestamp'){
                                    $value = ($element.getAttribute('data-math-diff')) ? parseFloat($element.getAttribute('data-math-diff')) : 0;
                                }
                            }else{
                                if($element.getAttribute('data-return_age')=='true'){
                                    $value = ($element.getAttribute('data-math-age')) ? parseFloat($element.getAttribute('data-math-age')) : 0;
                                }
                                // @since 1.2.0 - check if we want to return the date birth years, months or days for calculations
                                if($element.getAttribute('data-date-math')=='years'){
                                    $value = ($element.getAttribute('data-math-age')) ? parseFloat($element.getAttribute('data-math-age')) : 0;
                                }
                                if($element.getAttribute('data-date-math')=='months'){
                                    $value = ($element.getAttribute('data-math-age-months')) ? parseFloat($element.getAttribute('data-math-age-months')) : 0;
                                }
                                if($element.getAttribute('data-date-math')=='days'){
                                    $value = ($element.getAttribute('data-math-age-days')) ? parseFloat($element.getAttribute('data-math-age-days')) : 0;
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
                            if($value.indexOf('%d')!==-1){
                                var dynamicParentIndex = $($element).parents('.super-duplicate-column-fields:eq(0)').index();
                                $value = $value.replaceAll('%d', dynamicParentIndex+1);
                            }
                        }
                        if( ($value_type=='int') && (isNaN($value)) ) {
                            $value = $default_value;
                        }
                        args.value = args.value.replace('{'+$old_name+'}', $value);
                    }
                }
            }
        }
        return args.value;
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
                }else{
                    args.loadingOverlay.classList.add('super-success');
                    if(args.generatePdf && args.pdfSettings.downloadBtn==='true'){
                        SUPER.show_pdf_download_btn(args);
                    }
                    // Close Popup (if any)
                    if(typeof SUPER.init_popups === 'function' && typeof SUPER.init_popups.close === 'function' ){
                        SUPER.init_popups.close(true);
                    }
                }
                // Display the error/success message
                if(innerText) innerText.innerHTML = result.msg;
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
        }else{
            // Display message in legacy mode
            // But only display if not empty
            if(result.msg!==''){
                // Remove existing messages
                var ii,
                    html,
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
                    $('html, body').animate({
                        scrollTop: $(args.form).offset().top-200
                    }, 1000);
                }
            }
        }
        // Redirect user to specified url
        if(result.redirect){
            window.location.href = result.redirect;
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
        if(args.el.closest('[data-conditional-action="show"]')){
            if(args.el.closest('[data-conditional-action="show"]').classList.contains('super-conditional-hidden')){
                return false;
            }
        }
        if(args.el.closest('.super-shortcode').classList.contains('super-hidden')) return false;
        var parent = args.el.closest('.super-field'),
            result,
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
            mayBeEmpty = (typeof args.el.dataset.mayBeEmpty !== 'undefined' ? args.el.dataset.mayBeEmpty : 'false'),
            allowEmpty = false,
            urlRegex = /^(http(s)?:\/\/)?(www\.)?[a-zA-Z0-9]+([-.]{1}[a-zA-Z0-9]+)*\.[a-zA-Z]{2,5}(:[0-9]{1,5})?(\/.*)?$/;
        
        // @since   4.9.0 -  Conditional required fields
        // Before we proceed, check if field is empty
        if (args.el.value === '') {
            // If it is empty, check if it allowed to be empty
            if (typeof mayBeEmpty !== 'undefined') {
                if (mayBeEmpty == 'false') {
                    allowEmpty = false; // Do not allow field to be empty
                }
                if (mayBeEmpty == 'true') {
                    allowEmpty = true; // Allow field to be empty
                }
                if (mayBeEmpty == 'conditions') {
                    // Allow field to be empty only when following conditions are met
                    allowEmpty = true; 
                    args.conditionalLogic = args.form.querySelectorAll('.super-validate-conditions');
                    if(typeof args.conditionalLogic !== 'undefined'){
                        if(args.conditionalLogic.length!==0){
                            result = SUPER.conditional_logic.loop(args);
                            if (!result) {
                                allowEmpty = false; // when condition is met, we do not allow field to be empty
                            }
                        }
                    }
                }
            }
        }
        regex = new RegExp(custom_regex);
        if( custom_regex && args.validation=='custom' ) {
            if(!regex.test(args.el.value)) error = true;
        }
        if (args.validation == 'captcha') {
            error = true;
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
                total = parent.querySelectorAll('.super-autosuggest-tags > div > span').length;
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
                total = parent.querySelectorAll('.super-autosuggest-tags > div > span').length;
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
            if(!super_int_phone.isValidNumber()){
                error = true;
            }
        }

        // Display error messages
        if(allowEmpty && args.el.value==='') error = false;
        if(typeof args.validation !== 'undefined' && !allowEmpty && args.el.value==='') error = true;
        if(error){
            SUPER.handle_errors(args.el);
            SUPER.add_error_status_parent_layout_element($, args.el);
        }else{
            if(args.el.closest('.super-field')) args.el.closest('.super-field').classList.remove('super-error-active');
            SUPER.remove_error_status_parent_layout_element($, args.el);
        }
        return error;
    };

    // Output errors for each field
    SUPER.handle_errors = function(el){
        if(el.closest('.super-field')) el.closest('.super-field').classList.add('super-error-active');
    };

    // Validate the form
    SUPER.validate_form = function(args){ // form, submitButton, validateMultipart, e, doingSubmit
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
            regex = /{([^\\\/\s"'+]*?)}/g,
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
                while ((match = regex.exec(url)) !== null) {
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

        // Activate possible TABS and Accordions to display errors
        var tabs = args.form.querySelectorAll('.super-tabs-tab.super-error');
        if(tabs && tabs[0]) tabs[0].click();
        var accordions = args.form.querySelectorAll('.super-accordion-item.super-error');
        if(accordions && accordions[0]) accordions[0].querySelector('.super-accordion-header').click();
        if(error===false){
            // Check if there are other none standard elements that have an active error
            // Currently used by Stripe Add-on to check for invalid card numbers for instance
            if(args.form.querySelectorAll('.super-error-active').length){
                SUPER.scrollToError(args.form);
                return true;
            }
            // @since 2.0.0 - multipart validation
            if(args.validateMultipart===true) return true;
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
                    var formData = SUPER.prepare_form_data($(args.form)); // returns {data:$data, form_id:$form_id, entry_id:$entry_id, list_id:$list_id};
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
                }
            }, 100);

        }else{
            SUPER.scrollToError(args.form, args.validateMultipart);
        }
        SUPER.after_validating_form_hook(undefined, args.form);

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
                $('html, body').animate({
                    scrollTop: $(form).offset().top-30
                }, 1000);
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
                current.scrollIntoView({behavior: "auto", block: "center", inline: "center"});
                // Focus first error field
                SUPER.focusNextTabField({keyCode: 32, preventDefault: function(){}}, current, form, current);
            }
        }else{
            // @since 2.1.0
            proceed = SUPER.before_scrolling_to_error_hook(form, $(form).find('.super-error-active').offset().top-200);
            if(proceed!==true) return false;
            $('html, body').animate({
                scrollTop: $(form).find('.super-error-active').offset().top-200
            }, 1000);

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
        var i, name, functions = super_common_i18n.dynamic_functions.save_form_params_filter;
        if(typeof functions !== 'undefined'){
            for( i = 0; i < functions.length; i++){
                name = functions[i].name;
                if(typeof SUPER[name] === 'undefined') continue;
                params = SUPER[name](params);
            }
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
                    var btn = args.form.querySelector('.super-form-button.super-loading');
                    if(btn) {
                        var btnName = btn.querySelector('.super-button-name');
                        btnName.innerHTML = args.oldHtml;
                        btn.classList.remove('super-loading');
                    }
                    $('html, body').animate({
                        scrollTop: $(args.form).offset().top-200
                    }, 1000);
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
    SUPER.after_field_change_blur_hook = function(args){
        args.form = SUPER.get_frontend_or_backend_form(args);
        var $functions = super_common_i18n.dynamic_functions.after_field_change_blur_hook;
        jQuery.each($functions, function(key, value){
            if(typeof SUPER[value.name] !== 'undefined') {
                SUPER[value.name](args);
            }
        });
        if( typeof args.el !== 'undefined'  && (args.skip!==true) ) {
            SUPER.auto_step_multipart(args);
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

    // @since 3.2.0 - save form progress
    SUPER.save_form_progress_timeout = null; 
    SUPER.save_form_progress = function(args){
        if( !args.form.classList.contains('super-save-progress') ) {
            return false;
        }
        if(SUPER.save_form_progress_timeout !== null){
            clearTimeout(SUPER.save_form_progress_timeout);
        }
        SUPER.save_form_progress_timeout = setTimeout(function () {
            var $data = SUPER.prepare_form_data($(args.form));
            var $form_id = $data.form_id;
            $data = SUPER.after_form_data_collected_hook($data.data);
            $.ajax({
                url: super_common_i18n.ajaxurl,
                type: 'post',
                data: {
                    action: 'super_save_form_progress',
                    data: $data,
                    form_id: $form_id
                }
            });
        }, 1000); 
        // 1 second timeout, to make sure that we do not make unnecessary requests to the server
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

                    if($super_field.hasClass('super-signature')){
                        $data[$this.attr('name')].signatureLines = $super_field.find('.super-signature-lines').val();
                        //$data[$this.attr('name')].value = $super_field.find('.super-signature-canvas').signature('toJSON');
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
    SUPER.prepare_form_data = function($form){
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

        // Loop through all dynamic columns and create an JSON string based on all the fields
        $form.find('.super-column[data-duplicate-limit]').each(function(){
            $dynamic_arrays = [];
            $map_key_names = [];
            $first_property_name = undefined;
            $(this).find('.super-duplicate-column-fields').each(function(){
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
        return {
            data:$data, 
            form_id:$form_id, 
            entry_id:$entry_id, 
            list_id:$list_id,
            sf_nonce: $form.find('input[name="sf_nonce"]').val()
        };
    };

    // @since 1.3
    SUPER.after_form_data_collected_hook = function(data){
        var i, name, functions = super_common_i18n.dynamic_functions.after_form_data_collected_hook;
        for ( i = 0; i < functions.length; i++) {
            name = functions[i].name;
            if(typeof SUPER[name] !== 'undefined') {
                data = SUPER[name](data);
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
        var node = form.querySelector('.super-shortcode-field[name="'+name+'"]');
        if(node) {
            return {field: node, type: node.type}
        }
        node = form.querySelector('.super-active-files[name="'+name+'"]');
        if(node) {
            return {field: node, type: 'file'}
        }
        return {field: undefined, type: 'text'}
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
                    var regex = /{([^\\\/\s"'+]*?)}/g;
                    if(regex.exec($lat)!==null){
                        $field_name = $lat.replace('{','').replace('}','');                       
                        $lat = SUPER.field(args.form, $field_name).dataset.lat;
                        if(!$lat) $lat = 0;
                    }
                    if(regex.exec($lng)!==null){
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
                SUPER.google_maps_api.initAutocompleteCallback(args);
            }
        });
    };
    SUPER.google_maps_api.initAutocompleteCallback = function(args){
        var i, x, s, obj = {}, inputField, autocomplete = [];
            autocomplete[args.el.name] = new google.maps.places.Autocomplete(args.el);
        var mapping = {
            street_number: 'street_number',
            route: 'street_name',
            locality: 'city', // see: https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete-addressform
            postal_town: 'city', // see: https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete-addressform
            sublocality_level_1: 'city', // see: https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete-addressform
            administrative_area_level_2: 'municipality',
            administrative_area_level_1: 'state',
            country: 'country',
            postal_code: 'postal_code',
            lat: 'lat',
            lng: 'lng'
        };
        
        // Check if any of the address components is mapped
        var $returnAddressComponent = false;
        for (var key in mapping) {
            if($(args.el).data('map-'+mapping[key])){
                $returnAddressComponent = true;
            }
        }
        
        var $returnName = false;
        if($(args.el).data('map-name')) $returnName = true;

        mapping.formatted_phone_number = 'formatted_phone_number';
        var $returnFormattedPhoneNumber = false;
        if($(args.el).data('map-formatted_phone_number')) $returnFormattedPhoneNumber = true;

        mapping.international_phone_number = 'international_phone_number';
        var $returnInternationalPhoneNumber = false;
        if($(args.el).data('map-international_phone_number')) $returnInternationalPhoneNumber = true;

        mapping.website = 'website';
        var $returnWebsite = false;
        if($(args.el).data('map-website')) $returnWebsite = true;

        var fields = ['formatted_address', 'geometry.location']; // This data is always used
        if($returnAddressComponent) fields.push('address_components');
        if($returnName) fields.push('name');
        if($returnFormattedPhoneNumber) fields.push('formatted_phone_number');
        if($returnInternationalPhoneNumber) fields.push('international_phone_number');
        if($returnWebsite) fields.push('website');

        var thisAutocomplete = autocomplete[args.el.name];
        thisAutocomplete.setFields(fields);
        thisAutocomplete.el = args.el;
        thisAutocomplete.form = args.form;

        s = $(args.el).data('countries'); // Could be empty or a comma seperated string e.g: fr,nl,de
        if(s){
            x = s.split(',');
            obj.countries = [];
            for(i=0; i<x.length; i++){
                obj.countries.push(x[i].trim());
            }
            thisAutocomplete.setComponentRestrictions({
                country: obj.countries, // e.g: ["us", "pr", "vi", "gu", "mp"],
            });
        }
        s = $(args.el).data('types'); // Could be empty or a comma seperated string e.g: fr,nl,de
        if(s){
            x = s.split(',');
            obj.types = [];
            for(i=0; i<x.length; i++){
                obj.types.push(x[i].trim());
            }
            thisAutocomplete.setTypes(obj.types);
        }
        thisAutocomplete.addListener( 'place_changed', function () {
            // Set text field to the formatted address
            var place = thisAutocomplete.getPlace();
            thisAutocomplete.el.value = place.formatted_address;
            SUPER.calculate_distance({el: thisAutocomplete.el});

            var street_data = {
                number: {
                    long: '',
                    short: ''
                },
                name: {
                    long: '',
                    short: ''
                }
            };

            // @since 3.2.0 - add address latitude and longitude for ACF google map compatibility
            var lat = place.geometry.location.lat();
            var lng = place.geometry.location.lng();
            thisAutocomplete.el.dataset.lat = lat;
            thisAutocomplete.el.dataset.lng = lng;

            // @since 3.5.0 - trigger / update google maps in case {tags} have been used
            args.el = thisAutocomplete.el;
            args.form = thisAutocomplete.form;
            SUPER.google_maps_init(args);

            $(thisAutocomplete.el).trigger('keyup');
            var $attribute;
            var $val;
            var $address;
            
            if($returnAddressComponent){
                place.address_components.push({
                    long_name: lat,
                    short_name: lat,
                    types: ["lat"]
                });
                place.address_components.push({
                    long_name: lng,
                    short_name: lng,
                    types: ["lng"]
                });
                for (var i = 0; i < place.address_components.length; i++) {
                    var item = place.address_components[i];
                    var long = item.long_name;
                    var short = item.short_name;
                    var types = item.types;
                    // Street number
                    if(types.indexOf('street_number')!==-1){
                        street_data.number.long = long;
                        street_data.number.short = short;
                    }
                    // Street name
                    if(types.indexOf('route')!==-1){
                        street_data.name.long = long;
                        street_data.name.short = short;
                    }
                    $attribute = $(thisAutocomplete.el).data('map-'+mapping[types[0]]);
                    if(typeof $attribute !=='undefined'){
                        $attribute = $attribute.split('|');
                        inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
                        if(inputField){
                            if($attribute[1]==='') $attribute[1] = 'long';
                            $val = place.address_components[i][$attribute[1]+'_name'];
                            inputField.value = $val;
                            if($val===''){
                                inputField.closest('.super-shortcode').classList.remove('super-filled');
                            }else{
                                inputField.closest('.super-shortcode').classList.add('super-filled');
                            }
                            SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
                        }
                    }
                }
            }

            // Name of the place
            $attribute = $(thisAutocomplete.el).data('map-name');
            if(typeof $attribute !=='undefined'){
                $attribute = $attribute.split('|');
                inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
                if(inputField){
                    if($attribute[1]==='') $attribute[1] = 'long';
                    $val = place.name;
                    inputField.value = $val;
                    if($val===''){
                        inputField.closest('.super-shortcode').classList.remove('super-filled');
                    }else{
                        inputField.closest('.super-shortcode').classList.add('super-filled');
                    }
                    SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
                }
            }

            // Formatted address of the place
            $attribute = $(thisAutocomplete.el).data('map-formatted_address');
            if(typeof $attribute !=='undefined'){
                $attribute = $attribute.split('|');
                inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
                if(inputField){
                    if($attribute[1]==='') $attribute[1] = 'long';
                    $val = place.formatted_address;
                    inputField.value = $val;
                    if($val===''){
                        inputField.closest('.super-shortcode').classList.remove('super-filled');
                    }else{
                        inputField.closest('.super-shortcode').classList.add('super-filled');
                    }
                    SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
                }
            }

            // Formatted phone number
            $attribute = $(thisAutocomplete.el).data('map-formatted_phone_number');
            if(typeof $attribute !=='undefined'){
                $attribute = $attribute.split('|');
                inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
                if(inputField){
                    if($attribute[1]==='') $attribute[1] = 'long';
                    $val = place.formatted_phone_number;
                    inputField.value = $val;
                    if($val===''){
                        inputField.closest('.super-shortcode').classList.remove('super-filled');
                    }else{
                        inputField.closest('.super-shortcode').classList.add('super-filled');
                    }
                    SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
                }
            }

            // International phone number
            $attribute = $(thisAutocomplete.el).data('map-international_phone_number');
            if(typeof $attribute !=='undefined'){
                $attribute = $attribute.split('|');
                inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
                if(inputField){
                    if($attribute[1]==='') $attribute[1] = 'long';
                    $val = place.international_phone_number;
                    inputField.value = $val;
                    if($val===''){
                        inputField.closest('.super-shortcode').classList.remove('super-filled');
                    }else{
                        inputField.closest('.super-shortcode').classList.add('super-filled');
                    }
                    SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
                }
            }

            // Busniness website
            $attribute = $(thisAutocomplete.el).data('map-website');
            if(typeof $attribute !=='undefined'){
                $attribute = $attribute.split('|');
                inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
                if(inputField){
                    if($attribute[1]==='') $attribute[1] = 'long';
                    $val = place.website;
                    inputField.value = $val;
                    if($val===''){
                        inputField.closest('.super-shortcode').classList.remove('super-filled');
                    }else{
                        inputField.closest('.super-shortcode').classList.add('super-filled');
                    }
                    SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
                }
            }

            // @since 3.5.0 - combine street name and number
            $attribute = $(thisAutocomplete.el).data('map-street_name_number');
            if( typeof $attribute !=='undefined' ) {
                $attribute = $attribute.split('|');
                inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
                if(inputField){
                    $address = '';
                    if( street_data.name[$attribute[1]]!=='' ) $address += street_data.name[$attribute[1]];
                    if( $address!=='' ) {
                        $address += ' '+street_data.number[$attribute[1]];
                    }else{
                        $address += street_data.number[$attribute[1]];
                    }
                    inputField.value = $address;
                    if($address===''){
                        inputField.closest('.super-shortcode').classList.remove('super-filled');
                    }else{
                        inputField.closest('.super-shortcode').classList.add('super-filled');
                    }
                    SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
                }
            }

            // @since 3.5.1 - combine street number and name
            $attribute = $(thisAutocomplete.el).data('map-street_number_name');
            if( typeof $attribute !=='undefined' ) {
                $attribute = $attribute.split('|');
                inputField = SUPER.field(thisAutocomplete.form, $attribute[0]);
                if(inputField){
                    $address = '';
                    if( street_data.number[$attribute[1]]!=='' ) $address += street_data.number[$attribute[1]];
                    if( $address!=='' ) {
                        $address += ' '+street_data.name[$attribute[1]];
                    }else{
                        $address += street_data.name[$attribute[1]];
                    }
                    inputField.value = $address;
                    if($address===''){
                        inputField.closest('.super-shortcode').classList.remove('super-filled');
                    }else{
                        inputField.closest('.super-shortcode').classList.add('super-filled');
                    }
                    SUPER.after_field_change_blur_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
                }
            }
        });
    };

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

        var forms = document.querySelectorAll('.super-form');
        Object.keys(forms).forEach(function(key) {
            $this = forms[key];

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
            // ... but for signatures (if add-on is active)
            nodes = $this.querySelectorAll('.super-signature');
            for(i=0; i<nodes.length; i++){
                value = nodes[i].querySelector('.super-signature-lines').value;
                if(value!==''){
                    value = value.replace('\\"lines\\"', '"lines"');
                    $(nodes[i]).find('.super-signature-canvas').signature('enable').signature('draw', value);
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
                    if (!args.form.classList.contains('preload-disabled')) {
                        if (!args.form.classList.contains('super-initialized')) {
                            setTimeout(function (){
                                $(args.form).fadeOut(100, function () {
                                    args.form.classList.add('super-initialized');
                                    $(args.form).fadeIn(500);
                                });
                            }, 500);
                        }
                    } else {
                        args.form.classList.add('super-initialized');
                    }
                }
            };
            SUPER.after_initializing_forms_hook(args);

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
            $i,
            $v,
            $row_regex,
            $html_fields,
            $target,
            $html,
            $originalHtml,
            $splitName,
            $newName,
            $original,
            $field_name,
            $value_n,
            $original_field_name,
            $rv,
            $return,
            $rows,
            $row,
            $regex,
            $array,
            $values,
            $new_value,
            $match,
            $fileLoopRows = [],
            formId = parseInt(args.form.id.replace('super-form-', ''), 10);

        // Only when not on canvas in builder mode
        if(args.form.classList.contains('super-preview-elements')){
            return false;
        }

        // Continue otherwise
        if(typeof args.foundElements !== 'undefined') {
            if(args.foundElements.length>0){
                $html_fields = args.foundElements;
            }else{
                $html_fields = args.form.querySelectorAll('[data-tags], .super-google-map, .super-html-content');
            }
        }else{
            if(typeof args.el === 'undefined') {
                $html_fields = args.form.querySelectorAll('[data-tags], .super-google-map, .super-html-content');
            }else{
                var n = SUPER.get_original_field_name(args.el);
                $html_fields = args.form.querySelectorAll('[data-tags*="{'+n+'}"], .super-google-map[data-fields*="{'+n+'}"], .super-html-content[data-fields*="{'+n+'}"]');
            }
        }
        $regex = /{([^\\\/\s"'+]*?)}/g;
        Object.keys($html_fields).forEach(function(key) {
            var $counter = 0;
            $target = $html_fields[key];
            // @since 4.9.0 - accordion title description {tags} compatibility
            if( $target.dataset.tags ) {
                $html = $target.dataset.original;
                //classList.contains('super-heading-title') || $target.classList.contains('super-heading-description') || 
                //$target.classList.contains('super-tab-title') || $target.classList.contains('super-tab-desc') || 
                //$target.classList.contains('super-accordion-title') || $target.classList.contains('super-accordion-desc') ) {
            }else{
                if(!$target.parentNode.querySelector('textarea')){
                    return true;
                }
                $html = $target.parentNode.querySelector('textarea').value;
            }
            // If empty skip
            if($html===''){
                return true;
            }
            
            // When generating PDF, we must have a reference to the original form
            originalFormReference = args.form;
            if(args.form.classList.contains('super-generating-pdf')){
                originalFormReference = document.querySelector('#super-form-'+formId+'-placeholder');
            }

            // @since 5.0.120 - foreach statement compatibility
            $regex = /<%(.*?)%>|{(.*?)}/;
            var $skipUpdate = true;
            if ((m = $regex.exec($html)) !== null) {
                $skipUpdate = false;
            }
            $html = SUPER.filter_foreach_statements($target, 0, 0, $html, undefined, formId, originalFormReference);
            $html = $html.replaceAll('<%', '{');
            $html = $html.replaceAll('%>', '}');

            // Check if html contains {tags}, if not we don't have to do anything.
            // This also solves bugs with for instance third party plugins
            // That use shortcodes to initialize elements, which initialization would be lost
            // upon updating the HTML content based on {tags}.
            // This can be solved by NOT using either of the {} curly braces inside the HTML content
            $regex = /({|foreach\()([-_a-zA-Z0-9\[\]]{1,})(\[.*?])?(?:;([a-zA-Z0-9]{1,}))?(}|\):)/g;
            // If it has {tags} then continue
            var m;
            var replaceTagsWithValue = {};
            while ((m = $regex.exec($html)) !== null) {
                decodeHtml = true;
                $skipUpdate = false;
                // This is necessary to avoid infinite loops with zero-width matches
                if (m.index === $regex.lastIndex) {
                    $regex.lastIndex++;
                }
                var $n = (m[2] ? m[2] : ''); // name
                var $d = (m[3] ? m[3] : ''); // depth
                var $s = (m[4] ? m[4] : ''); // suffix
                if($s==='allFileNames' || $s==='allFileUrls' || $s==='allFileLinks'){
                    decodeHtml = false;
                }else{
                    var fieldType = SUPER.get_field_type(originalFormReference, $n);
                    if($s==='' && fieldType.type==='file'){
                        decodeHtml = false;
                    }
                }
                // Get field type
                if($s!=='') $s = ';'+$s;
                $values = $n+$d+$s;
                args.value = '{'+$values+'}'; //values[1];
                args.target = $target;
                $new_value = SUPER.update_variable_fields.replace_tags(args);
                delete args.target;
                if(decodeHtml){
                    $new_value = SUPER.html_encode($new_value);
                }
                replaceTagsWithValue[$values] = $new_value;
            }
            var key;
            for(key in replaceTagsWithValue) {
                $html = $html.replaceAll('{'+key+'}', replaceTagsWithValue[key]);
            }
            if($skipUpdate) return true;
            // @since 4.6.0 - if statement compatibility
            $html = SUPER.filter_if_statements($html);
            
            if($target.value || $target.dataset.value){
                if($target.value) $target.value = $html;
                if($target.dataset.value) $target.dataset.value = $html;
            }else{
                $target.innerHTML = $html;
            }
            var $parent = $target.closest('.super-shortcode');
            if($parent){
                var $field = $parent.querySelector('.super-shortcode-field');
                if($field) $field.value = $html;
            }
        });
    };

    // Replace datepickers default value {tags} with field values
    SUPER.init_replace_datepicker_default_value_tags = function(args){
        var i, nodes;
        if(typeof args.el === 'undefined') {
            nodes = args.form.querySelectorAll('.super-shortcode-field.super-datepicker');
        }else{
            nodes = args.form.querySelectorAll('.super-shortcode-field.super-datepicker[data-absolute-default*="{'+SUPER.get_field_name(args.el)+'}"]');
        }
        // Update default value for datepickers in case they contain {tags}
        for(i=0; i<nodes.length; i++){
            var absoluteDefault = nodes[i].dataset.absoluteDefault,
                $match,
                $regex = /{([^\\\/\s"'+]*?)}/g,
                $array = [],
                $counter = 0,
                $values,
                $new_value;
            if(absoluteDefault==='') continue;
            while (($match = $regex.exec(absoluteDefault)) !== null) {
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
            SUPER.init_datepicker(nodes[i]);
            $(nodes[i]).datepicker('setDate', absoluteDefault);
        }
    };

    // Replace form action attribute {tags} with field values
    // @since 4.4.6
    SUPER.init_replace_post_url_tags = function(args){
        var $match,
            $target = args.form.querySelector('form'),
            $actiontags = ($target ? $target.dataset.actiontags : ''),
            $regex = /{([^\\\/\s"'+]*?)}/g,
            $array = [],
            $counter = 0,
            $values,
            $new_value;

        // Only if action is defined
        if($target){
            while (($match = $regex.exec($actiontags)) !== null) {
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
            $data = SUPER.prepare_form_data($(args.form));
            $data = SUPER.after_form_data_collected_hook($data.data);
            $.ajax({
                url: super_common_i18n.ajaxurl,
                type: 'post',
                data: {
                    action: 'super_print_custom_html',
                    data: $data,
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
        nodes = args.form.querySelectorAll('.super-keyword-tags .super-autosuggest-tags > div > span');
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
                        if((parseInt(value,10)-1) < ii){
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
        
        // After form cleared
        SUPER.after_form_cleared_hook(args.form);
    };


    // Populate form with entry data found after ajax call
    SUPER.populate_form_with_entry_data = function(data, form, clear){
        if(typeof clear === 'undefined') clear = true;
        var i,ii,iii,nodes,items,item,options,wrapper,input,innerNodes,firstValue,dropdown,setFieldValue,itemFirstValue,
            html,files,element,field,stars,currentStar,firstField,firstFieldName,
            switchBtn,activeItem,signatureDataUrl,fieldName,
            dynamicFields = {},        
            updatedFields = {};        
        
        data = JSON.parse(data);
        if(data!==false && data.length!==0){
        
            // First clear the form
            SUPER.init_clear_form({form: form, clear: clear});

            // Find all dynamic columns and get the first field name
            nodes = form.querySelectorAll('.super-duplicate-column-fields');
            for ( i = 0; i < nodes.length; i++ ) {
                firstField = SUPER.field(nodes[i]);
                if(firstField){
                    firstFieldName = firstField.name;
                    dynamicFields[firstFieldName] = firstField;
                }
            }
            // Create extra dynamic columns as long as they exist in the data
            Object.keys(dynamicFields).forEach(function(index) {
                i = 2;
                while(typeof data[index+'_'+i] !== 'undefined'){
                    if(SUPER.field_exists(form, index+'_'+i)===0) {
                        dynamicFields[index].closest('.super-duplicate-column-fields').querySelector('.super-add-duplicate').click();
                    }
                    i++;
                }
            });
            Object.keys(data).forEach(function(i) {
                if(data[i].length===0) return true;
                html = '';
                files = '';
                fieldName = data[i].name;
                // If we are dealing with files we must set name to the first item (if it exists), if no files exists, we skip it
                if( data[i].type=='files' ) {
                    if( (typeof data[i].files !== 'undefined') && (data[i].files.length!==0) ) {
                        fieldName = data[i].files[0].name;
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
                if(element.value!=data[i].value) {
                    updatedFields[fieldName] = element;
                }

                field = element.closest('.super-field');
                // Update field value by default
                element.value = data[i].value;
                
                // If field is filled out add the class otherwise remove the class
                if(element.value===''){
                    element.closest('.super-shortcode').classList.remove('super-filled');
                }else{
                    element.closest('.super-shortcode').classList.add('super-filled');
                }
                
                // Internation phonenumber
                if(field.classList.contains('super-int-phone-field')){
                    var intPhone = window.superTelInputGlobals.getInstance(element);
                    intPhone.setNumber(data[i].value);
                    return true;
                }

                // Color picker
                if(field.classList.contains('super-color')){
                    if(typeof $.fn.spectrum === "function") {
                        $(field.querySelector('.super-shortcode-field')).spectrum('set', data[i].value);
                    }
                    return true;
                }

                // Signature field (Add-on)
                if(field.classList.contains('super-signature')){
                    if(typeof $.fn.signature === "function") {
                        signatureDataUrl = data[i].value;
                        field.classList.add('super-filled'); // Make sure to be able to delete signature to be able to draw a new one
                        $(field.querySelector('.super-signature-canvas')).signature('draw', signatureDataUrl)
                    }
                    return true;
                }

                // Toggle field
                if(field.classList.contains('super-toggle')){
                    switchBtn = field.querySelector('.super-toggle-switch');
                    activeItem = switchBtn.querySelector('label[data-value="'+data[i].value+'"]');
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
                    SUPER.reposition_slider_amount_label(field, data[i].value);
                    return true;
                }
                // Autosuggest field
                if(field.classList.contains('super-auto-suggest')){
                    if(data[i].value!==''){
                        firstValue = data[i].value.split(';')[0];
                        dropdown = field.querySelector('.super-dropdown-list');
                        setFieldValue = '';
                        nodes = dropdown.querySelectorAll('.super-item.super-active');
                        for ( ii = 0; ii < nodes.length; ii++){
                            nodes[ii].classList.remove('super-active');
                        }
                        nodes = dropdown.querySelectorAll('.super-item[data-value^="'+firstValue+'"]');
                        for ( ii = 0; ii < nodes.length; ii++){
                            itemFirstValue = nodes[ii].dataset.value.split(';')[0];
                            if(itemFirstValue==firstValue){
                                field.querySelector('.super-field-wrapper').classList.add('super-overlap');
                                nodes[ii].classList.add('super-active');
                                if(setFieldValue===''){
                                    setFieldValue += nodes[ii].innerText;
                                }else{
                                    setFieldValue += ','+nodes[ii].innerText;
                                }
                            }
                        }
                        element.value = setFieldValue;
                    }else{
                        nodes = dropdown.querySelectorAll('.super-dropdown-list .super-item.super-active');
                        for ( ii = 0; ii < nodes.length; ii++){
                            nodes[ii].classList.remove('super-active');
                        }
                    }
                    return true;
                }
                // Dropdown field
                if(field.classList.contains('super-dropdown')){
                    if(data[i].value!==''){
                        options = data[i].value.split(',');
                        dropdown = field.querySelector('.super-dropdown-list');
                        setFieldValue = '';
                        nodes = dropdown.querySelectorAll('.super-item.super-active');
                        for ( ii = 0; ii < nodes.length; ii++){
                            nodes[ii].classList.remove('super-active');
                        }
                        for ( ii = 0; ii < options.length; ii++){
                            innerNodes = dropdown.querySelectorAll('.super-item:not(.super-placeholder)[data-value^="'+options[ii]+'"]');
                            for ( iii = 0; iii < innerNodes.length; iii++){
                                itemFirstValue = innerNodes[iii].dataset.value.split(';')[0];
                                innerNodes[iii].classList.add('super-active');
                                if(setFieldValue===''){
                                    setFieldValue += itemFirstValue;
                                }else{
                                    setFieldValue += ','+itemFirstValue;
                                }
                            }
                        }
                        element.value = setFieldValue;
                    }else{
                        nodes = field.querySelectorAll('.super-dropdown-list .super-item.super-active');
                        for ( ii = 0; ii < nodes.length; ii++){
                            nodes[ii].classList.remove('super-active');
                        }
                        nodes = field.querySelectorAll('.super-dropdown-list .super-item.super-default-selected');
                        for ( ii = 0; ii < nodes.length; ii++){
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
                        if(data[i].value!=='' && input.value == data[i].value){
                            input.checked = true;
                            items[ii].classList.add('super-active');
                            break; // Radio button can only have 1 active item
                        }
                    }
                    if(data[i].value===''){
                        // Radio button can only have 1 active item
                        item = wrapper.querySelector('.super-item.super-default-selected');
                        item.classList.add('super-active');  
                        item.querySelector('input').checked = true;
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

                        if(data[i].value!==''){
                            options = data[i].value.split(',');
                            if(options.indexOf(input.value)!==-1){
                                input.checked = true;
                                items[ii].classList.add('super-active');
                            }
                        }
                    }
                    if(data[i].value===''){
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
                    currentStar = parseInt(data[i].value) || 0;
                    for( ii = 0; ii < stars.length; ii++){
                        if(ii+1 <= currentStar){
                            stars[ii].classList.add('super-active');
                        }else{
                            stars[ii].classList.remove('super-active');
                        }
                    }
                    return true;
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
    SUPER.init_super_form_frontend = function(){

        // Do not do anything if all forms where intialized already
        if(document.querySelectorAll('.super-form').length===document.querySelectorAll('.super-form.super-initialized').length){
            return true;
        }
      
        // @since 3.3.0 - make sure to load dynamic columns correctly based on found contact entry data when a search field is being used
        $('.super-shortcode-field[data-search="true"]:not(.super-dom-populated)').each(function(){
            var field = this;
            if(field.value!==''){
                field.classList.add('super-dom-populated');
                SUPER.populate_form_data_ajax({el: field});
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

        // Populate signature element with possible saved form progress
        $('.super-form').each(function(){
            if($(this).hasClass('super-save-progress')){
                $(this).find('.super-signature').each(function(){
                    var value = $(this).find('.super-signature-lines').val();
                    if(value!==''){
                        value = value.replace('\\"lines\\"', '"lines"');
                        $(this).find('.super-signature-canvas').signature('enable').signature('draw', value);
                    }
                });
            }
        });

        // Loop over all fields that are inside dynamic column and rename them accordingly
        var i, nodes = document.querySelectorAll('.super-duplicate-column-fields .super-shortcode-field[name]');
        for (i = 0; i < nodes.length; ++i) {
            var field = nodes[i];
            if(field.classList.contains('super-fileupload')){
                field = field.parentNode.querySelector('.super-active-files');
            }
            // Figure out how deep this node is inside dynamic columns
            var parent = nodes[i].closest('.super-duplicate-column-fields');
            var parentIndex = $(parent).index();  // e.g: 0, 1, 2
            var nameSuffix = '_'+parentIndex;
            if(parentIndex===0) nameSuffix = '';
            var originalFieldName = field.dataset.oname;
            var newName = originalFieldName+nameSuffix;
            field.name = newName;
            var allParents = $(nodes[i]).parents('.super-duplicate-column-fields');
            var suffix = [];
            $(allParents).each(function(key){
                // Skip last parent, because we don't need it
                if(allParents.length===(key+1)){
                    return;
                }
                var currentParentIndex = $(this).index();  // e.g: 0, 1, 2
                suffix.push('['+currentParentIndex+']');
            });
            var levels = suffix.reverse().join('');
            field.name = originalFieldName+levels;
            //field.value = field.name; 
            field.dataset.olevels = levels;
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

            if($form.parent().hasClass('elementor-text-editor')){
                var $form_id = $form.find('input[name="hidden_form_id"]').val();
                $form.html('<p style="color:red;font-size:12px;"><strong>'+super_common_i18n.elementor.notice+':</strong> [Form ID: '+$form_id+ '] - '+super_common_i18n.elementor.msg+'</p>');
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
                var currentStep = window.location.hash.substring(1);
                if(currentStep!==''){
                    var explodedStep = currentStep.split('-');
                    if(explodedStep[0]==='step'){
                        var stepFormID = explodedStep[1];
                        var multiPart = explodedStep[2];
                        // Lookup the form based on the ID
                        var multiPartForm = document.querySelector('.super-form-'+stepFormID);
                        if(multiPartForm){
                            // We found a form, check if it contains a multi-part, if so then make it active
                            var nodes = multiPartForm.querySelectorAll('.super-multipart');
                            // If there are not enough multi-parts default to the first one
                            if(nodes.length < multiPart) multiPart = "1";
                            for(var i = 0; i < nodes.length; i++){
                                if(multiPart==(i+1)){
                                    nodes[i].classList.add('super-active');
                                }else{
                                    nodes[i].classList.remove('super-active');
                                }
                            }
                            nodes = multiPartForm.querySelectorAll('.super-multipart-step');
                            for(i = 0; i < nodes.length; i++){
                                if(multiPart==(i+1)){
                                    nodes[i].classList.add('super-active');
                                }else{
                                    nodes[i].classList.remove('super-active');
                                }
                            }
                            var progress = 100 / nodes.length;
                            progress = progress * parseInt(multiPart, 10);
                            form.querySelector('.super-multipart-progress-bar').style.width = progress+'%';
                        }
                    }
                }
            }
            SUPER.init_common_fields({form: form});
            SUPER.init_super_responsive_form_fields({form: form});
        });

        $(window).resize(function() {
            var i, nodes = document.querySelectorAll('.super-form:not(.super-generating-pdf)');
            for(i=0; i<nodes.length; i++){
                SUPER.init_super_responsive_form_fields({form: nodes[i]});
            }
        });
        
        var $handle_columns_interval = setInterval(function(){
            if(($('.super-form').length != $('.super-form.super-rendered').length) || ($('.super-form').length===0)){
                SUPER.handle_columns();
            }else{
                clearInterval($handle_columns_interval);
            }
        }, 100);
    };

    // Reposition slider amount label
    SUPER.reposition_slider_amount_label = function(field, value, conditionalUpdate){
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
                        $amount_width = $amount[0].offsetWidth;
                        $dragger_margin_left = $dragger.style.marginLeft.replace('px','');
                        if($dragger_margin_left<0){
                            $dragger_margin_left = -$dragger_margin_left;
                        }
                        $offset_left = $dragger.offsetLeft + $dragger_margin_left;
                        // If offset doesn't have to be less than 0
                        if($offset_left<0){
                            $offset_left = 0;
                        }
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
                if(nodes[i].querySelector('.slider')){
                    nodes[i].querySelector('.slider').remove();
                }
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
        if(typeof args.form === 'undefined') {
            args.form = document.querySelector('.super-form');
        }
        var formId = (args.form.querySelector('input[name="hidden_form_id"]') ? args.form.querySelector('input[name="hidden_form_id"]').value : 0);
		if (SUPER.responsive_form_fields_timeout[formId] !== null) {
			clearTimeout(SUPER.responsive_form_fields_timeout[formId]);
		}
        SUPER.responsive_form_fields_timeout[formId] = setTimeout(function () {
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
            SUPER.reposition_slider_element(args.form);

            // @since 1.3
            SUPER.after_responsive_form_hook($classes, args.form, $new_class, $window_classes, $new_window_class);

            if(typeof args.callback === 'function'){
                args.callback();
            }
        }, 500);
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
            }else{
                $visibility = 'visible';
            }
            $filtervalues = $filtervalue.toString().split(',');
            $string_value = $value.toString();
            $.each($filtervalues, function( index, value ) {
                if( value==$string_value ) {
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
        args._pdf = new jsPDF({
            orientation: args.orientation,   // Orientation of the first page. Possible values are "portrait" or "landscape" (or shortcuts "p" or "l").
            format: args.format,             // The format of the first page.  Default is "a4"
            putOnlyUsedFonts: false,    // Only put fonts into the PDF, which were used.
            compress: false,            // Compress the generated PDF.
            precision: 16,              // Precision of the element-positions.
            userUnit: 1.0,              // Not to be confused with the base unit. Please inform yourself before you use it.
            floatPrecision: 16,         // or "smart", default is 16
            unit: args.pdfSettings.unit                  // Measurement unit (base unit) to be used when coordinates are specified.
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

        args._pdf.addFileToVFS('NotoSans-Regular-normal.ttf', super_common_i18n.fonts.NotoSans.regular);
        args._pdf.addFont('NotoSans-Regular-normal.ttf', 'NotoSans-Regular', 'normal');
        args._pdf.addFileToVFS('NotoSans-Bold-bold.ttf', super_common_i18n.fonts.NotoSans.bold);
        args._pdf.addFont('NotoSans-Bold-bold.ttf', 'NotoSans-Bold', 'bold');

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
        var pageWidth = args._pdf.internal.pageSize.getWidth();
        var pageHeight = args._pdf.internal.pageSize.getHeight();
        args.pageWidthPortrait = pageWidth;
        args.pageHeightPortrait = pageHeight;
        args.pageWidthLandscape = pageHeight;
        args.pageHeightLandscape = pageWidth;
        args.pageWidthInPixels = pageWidth / args.unitRatio;
        args.pageHeightInPixels = pageHeight / args.unitRatio;
        
        // Make form scrollable based on a4 height
        var scrollAmount = 0;

        args.scrollAmount = scrollAmount;
        args.pdfSettings.filename = SUPER.update_variable_fields.replace_tags({form: args.form0, value: args.pdfSettings.filename});

        // Blur/unfocus any focussed field
        // bug in google chrome on mobile devices
        // .....
        //
        // Add a timeout (just to be sure)
        setTimeout(function(){
            SUPER.pdf_generator_prepare(args, function(args){
                // Start generating pages (starting at page 1)
                args.currentPage = 1;
                SUPER.pdf_generator_generate_page(args);
            });
        }, 5000);
        return false;
    };
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
    SUPER.pdf_generator_prepare = function(args, callback){
        var form = args.form0;

        // Define PDF tags
        SUPER.pdf_tags = {
            pdf_page: '{pdf_page}',
            pdf_total_pages: '{pdf_total_pages}'
        };

        // Must hide scrollbar
        document.documentElement.classList.add('super-hide-scrollbar');
        form.classList.add('super-generating-pdf');
        
        // Normalize font styles
        var normalizeFontStylesNodesClasses = 'h1, h2, h3, h4, h5, h6, .super-label, .super-description, .super-heading-title, .super-heading-description, .super-text .super-shortcode-field, .super-textarea .super-shortcode-field, .super-filled .super-adaptive-placeholder > span, .super-dropdown.super-filled .super-item.super-placeholder, .super-checkbox .super-item > div, .super-radio .super-item > div, .super-quantity .super-shortcode-field, .super-toggle-switch, .super-currency .super-shortcode-field, .super-slider .amount, .super-calculator-currency-wrapper, .super-calculator-label, .super-fileupload-name, .super-fileupload-button-text, .super-toggle-prefix-label > span, .super-toggle-suffix-label > span, .super-html-title, .super-html-subtitle, .super-html-content',
        normalizeFontStylesNodesClassesExploded = normalizeFontStylesNodesClasses.split(','),
        newNormalizeFontStylesNodesClasses = '';
        for(i=0; i<normalizeFontStylesNodesClassesExploded.length; i++){
            if(i>0) newNormalizeFontStylesNodesClasses += ', ';
            newNormalizeFontStylesNodesClasses += '.super-pdf-page-container '+normalizeFontStylesNodesClassesExploded[i];
        }

        // Must hide elements
        var css = '.super-hide-scrollbar {overflow: -moz-hidden-unscrollable!important; overflow: hidden!important;}';
        // Required to render pseudo elements (html2canvas code was altered for this)
        css += '.super-pdf-page-container.super-pdf-clone .super-form *:before,';
        css += '.super-pdf-page-container.super-pdf-clone .super-form *:after {display:none!important;}';
        // Set font weight, line height and letter spacing to normal sizes to avoid inconsistencies between PDF and rendered text in PDF
        css += newNormalizeFontStylesNodesClasses + '{font-family:"Helvetica", "Arial", sans-serif!important;font-weight:normal!important;line-height:1.2!important;letter-spacing:0!important;}';
        // Remove any form padding
        css += '.super-pdf-page-container .super-form.super-adaptive { padding-top: 0px!important; }';
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
        css += '.super-pdf-header, .super-pdf-body, .super-pdf-footer { display: block; float: left; width: 100%; overflow: hidden; }';
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
        //debugger;
        //pdfPageContainer.style.zIndex = "9999999999";
        //pdfPageContainer.style.left = "0px";
        //pdfPageContainer.style.top = "0px";
        // ------- for debugging only: ----
        pdfPageContainer.style.position = "fixed";
        pdfPageContainer.style.backgroundColor = "#ffffff";
        pdfPageContainer.style.height = (args.pageHeightInPixels*2)+'px';
        pdfPageContainer.style.maxHeight = (args.pageHeightInPixels*2)+'px';
        pdfPageContainer.style.overflow = "hidden";
        pdfPageContainer.querySelector('.super-pdf-header').appendChild(headerClone);
        pdfPageContainer.querySelector('.super-pdf-body').appendChild(args.form0);
        pdfPageContainer.querySelector('.super-pdf-footer').appendChild(footerClone);

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
        var headerFooterHeight = 0;
        headerFooterHeight += pdfPageContainer.querySelector('.super-pdf-header').clientHeight;
        headerFooterHeight += pdfPageContainer.querySelector('.super-pdf-footer').clientHeight;
        args.scrollAmount = (args.pageHeightInPixels*2)-headerFooterHeight;
        pdfPageContainer.querySelector('.super-pdf-body').style.height = args.scrollAmount+'px';
        pdfPageContainer.querySelector('.super-pdf-body').style.maxHeight = args.scrollAmount+'px';

        // Make all mutli-parts visible
        // Make all TABs visible
        // Make all accordions visible
        var nodes = form.querySelectorAll('.super-multipart,.super-tabs-content,.super-accordion-item');
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
        nodes = pdfPageContainer.querySelectorAll(normalizeFontStylesNodesClasses);
        for( i=0; i < nodes.length; i++ ) {
            var el = nodes[i];
            if(el.classList.contains('super-heading-title')){
                el = el.children[0];
            }
            var fontSize = parseFloat(window.getComputedStyle(el, null).getPropertyValue('font-size'));
            var newFontSize = 2.5 * Math.ceil(fontSize/2.5);
            el.style.fontSize = newFontSize+'px';
        }

        SUPER.init_super_responsive_form_fields({form: form, callback: function(){

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
                var outerHeight = parseInt(window.getComputedStyle(el).height, 10);
                var diff = outerHeight - el.clientHeight;
                // set the height to 0 in case of it has to be shrinked
                el.style.height = 0;
                // set the correct height
                // el.scrollHeight is the full height of the content, not just the visible part
                el.style.height = Math.max(minHeight, el.scrollHeight + diff) + 'px';
            }
            // we use the "data-adaptheight" attribute as a marker
            // iterate through all the textareas on the page
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
            nodes = form.querySelectorAll('.super-pdf_page_break');
            args.pageOrientationChanges = {};
            for(i=0; i<nodes.length; i++){
                var pos = nodes[i].getBoundingClientRect();
                var headerHeight = args.pdfPageContainer.querySelector('.super-pdf-header').clientHeight;
                var belongsToPage = Math.ceil((pos.top-headerHeight)/args.scrollAmount)-1;
                var dynamicHeight = (args.scrollAmount*(belongsToPage+1)) - (pos.top - headerHeight);
                nodes[i].style.height = dynamicHeight+'px';
                args.pageOrientationChanges[belongsToPage+2] = 'unchanged';
                if(nodes[i].classList.contains('pdf-orientation-portrait')){
                    args.pageOrientationChanges[belongsToPage+2] = 'portrait';
                }
                if(nodes[i].classList.contains('pdf-orientation-landscape')){
                    args.pageOrientationChanges[belongsToPage+2] = 'landscape';
                }
                if(nodes[i].classList.contains('pdf-orientation-default')){
                    args.pageOrientationChanges[belongsToPage+2] = 'default';
                }
            }

            // Grab the total form height, this is required to know how many pages will be generated for the PDF file
            // This way we can also show the progression to the end user
            //scrollAmount = (pageHeightInPixels*2);
            args.totalPages = Math.ceil(form.clientHeight/args.scrollAmount);
            args.totalPercentagePerPage = (100/args.totalPages) / 3;
            args.pdfPercentageCompleted = 0;
            callback(args);
        }});
    };



    // PDF Generation
    SUPER.pdf_generator_generate_page = function(args){
        args.pdfPercentageCompleted += args.totalPercentagePerPage;
        if(args.progressBar) args.progressBar.style.width = args.pdfPercentageCompleted+"%";  
        var form = args.form0.closest('.super-form');
        // When canceled the following class will no longer exist, and we should not proceed
        if(form && !form.classList.contains('super-generating-pdf')){
            return false;
        }
        if(args.currentPage===1){
            if(args.orientation==='portrait'){
                args.pageWidth = args.pageWidthPortrait;
                args.pageHeight = args.pageHeightPortrait;
            }else{
                args.pageWidth = args.pageWidthLandscape;
                args.pageHeight = args.pageHeightLandscape;
            }
        }else{
            // Change the PDF container width based on orientation
            var headerFooterHeight = 0;
            if(args.pageOrientationChanges[args.currentPage]==='portrait'){
                args.pdfPageContainer.style.width = (args.pageWidthInPixels*2)+'px';
                args.pdfPageContainer.style.height = (args.pageHeightInPixels*2)+'px';
                args.pdfPageContainer.style.maxHeight = (args.pageHeightInPixels*2)+'px';
                args.pageWidth = args.pageWidthPortrait;
                args.pageHeight = args.pageHeightPortrait;
                args.pageWidthInPixels = args.pageWidth / args.unitRatio;
                args.pageHeightInPixels = args.pageHeight / args.unitRatio;
                headerFooterHeight += args.pdfPageContainer.querySelector('.super-pdf-header').clientHeight;
                headerFooterHeight += args.pdfPageContainer.querySelector('.super-pdf-footer').clientHeight;
                args.scrollAmount = (args.pageHeightInPixels*2)-headerFooterHeight;
            }
            if(args.pageOrientationChanges[args.currentPage]==='landscape'){
                args.pdfPageContainer.style.width = (args.pageHeightInPixels*2)+'px';
                args.pdfPageContainer.style.height = (args.pageWidthInPixels*2)+'px';
                args.pdfPageContainer.style.maxHeight = (args.pageWidthInPixels*2)+'px';
                args.pageWidth = args.pageWidthLandscape;
                args.pageHeight = args.pageHeightLandscape;
                args.pageWidthInPixels = args.pageWidth / args.unitRatio;
                args.pageHeightInPixels = args.pageHeight / args.unitRatio;
                headerFooterHeight += args.pdfPageContainer.querySelector('.super-pdf-header').clientHeight;
                headerFooterHeight += args.pdfPageContainer.querySelector('.super-pdf-footer').clientHeight;
                args.scrollAmount = (args.pageWidthInPixels*2)-headerFooterHeight;
            }
            // Reset any PDF page break heights
            var i, nodes = form.querySelectorAll('.super-pdf_page_break');
            for(i=0; i<nodes.length; i++){
                nodes[i].style.height = '0px';
            }
            // Reset scroll
            form.querySelector('form').style.marginTop = '';

            // Loop over any possible PDF page break elements, and add the height to fill up the rest of the page with "nothing"
            nodes = form.querySelectorAll('.super-pdf_page_break');
            args.pageOrientationChanges = {};
            for(i=0; i<nodes.length; i++){
                var pos = nodes[i].getBoundingClientRect();
                var headerHeight = args.pdfPageContainer.querySelector('.super-pdf-header').clientHeight;
                var belongsToPage = Math.ceil((pos.top-headerHeight)/args.scrollAmount)-1;
                var dynamicHeight = (args.scrollAmount*(belongsToPage+1)) - (pos.top - headerHeight);
                nodes[i].style.height = dynamicHeight+'px';
                args.pageOrientationChanges[belongsToPage+2] = 'unchanged';
                if(nodes[i].classList.contains('pdf-orientation-portrait')){
                    args.pageOrientationChanges[belongsToPage+2] = 'portrait';
                }
                if(nodes[i].classList.contains('pdf-orientation-landscape')){
                    args.pageOrientationChanges[belongsToPage+2] = 'landscape';
                }
                if(nodes[i].classList.contains('pdf-orientation-default')){
                    args.pageOrientationChanges[belongsToPage+2] = 'default';
                }
            }
            args.pdfPageContainer.querySelector('.super-pdf-body').style.height = args.scrollAmount+'px';
            args.pdfPageContainer.querySelector('.super-pdf-body').style.maxHeight = args.scrollAmount+'px';
            // Scroll to the "fake" page
            form.querySelector('form').style.marginTop = "-"+(args.scrollAmount * (args.currentPage-1))+'px';
        }
        

        // Set form width and height according to a4 paper size minus the margins
        // 210 == 793px
        // 297 == 1122px
        // Media                Page size           Print area              Margins
        // A4 (Metric)          210 x 297 mm        200 x 287 mm            5 mm        5 mm        5 mm

        // Update PDF tags
        SUPER.pdf_tags = {
            pdf_page: args.currentPage,
            pdf_total_pages: args.totalPages
        };

        // Update pdf {tags}
        SUPER.after_field_change_blur_hook({el: undefined, form: form});
        var pdfHeaderForm = document.querySelector('.super-pdf-header .super-form');
        SUPER.after_field_change_blur_hook({el: undefined, form: pdfHeaderForm});
        var pdfFooterForm = document.querySelector('.super-pdf-footer .super-form');
        SUPER.after_field_change_blur_hook({el: undefined, form: pdfFooterForm});

        // Scroll to the "fake" page
        //form.querySelector('form').style.marginTop = "-"+(args.scrollAmount * (args.currentPage-1))+'px';

        // Because disabling the UI takes some time, add a timeout
        var timeout = (args.currentPage===1 ? 200 : 0);
        setTimeout(function(){
            // Now allow printing
            try {
                // Only if not already canceled/reset
                if(form && !form.classList.contains('super-generating-pdf')){
                    return false;
                }
                // eslint-disable-next-line no-undef
                args.pdfPercentageCompleted += args.totalPercentagePerPage;
                if(args.progressBar) args.progressBar.style.width = args.pdfPercentageCompleted+"%";  
                html2canvas(document.querySelector('.super-pdf-page-container'), {
                    scrollX: 0, // Important, do not remove
                    scrollY: 0,  // -window.scrollY, // Important, do not remove
                    scale: args.pdfSettings.renderScale, // The scale to use for rendering (higher means better quality, but larger file size)
                    currentPage: args.currentPage,
                    useCORS: true,
                    allowTaint: false,
                    backgroundColor: '#ffffff'
                }).then(function(canvas) {
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
                        args.pageHeight
                    );
                    // Make PDF searchable when text rendering is enabled
                    if(!args.pdfSettings.textRendering) args.pdfSettings.textRendering = 'true';
                    if(args.pdfSettings.textRendering==='true'){
                        SUPER.pdf_generator_render_text(args);
                    }
                    // If there are more pages to be processed, go ahead
                    if(form.querySelector('form').clientHeight > (args.scrollAmount * args.currentPage)){
                        args.currentPage++;
                        if(typeof args.pageOrientationChanges[args.currentPage] !== 'undefined' ){
                            if(args.pageOrientationChanges[args.currentPage]==='unchanged'){
                                if(typeof args.lastPageOrientation === 'undefined' ){
                                    args.lastPageOrientation = args.pdfSettings.orientation;
                                }
                                args._pdf.addPage(args.pdfSettings.format, args.lastPageOrientation);
                            }else{
                                if(args.pageOrientationChanges[args.currentPage]==='default'){
                                    args._pdf.addPage(args.pdfSettings.format, args.pdfSettings.orientation);
                                    args.lastPageOrientation = args.pdfSettings.orientation;
                                }else{
                                    args._pdf.addPage(args.pdfSettings.format, args.pageOrientationChanges[args.currentPage]);
                                    args.lastPageOrientation = args.pageOrientationChanges[args.currentPage];
                                }
                            }
                        }else{
                            args._pdf.addPage(args.pdfSettings.format, args.pdfSettings.orientation);
                            args.lastPageOrientation = args.pdfSettings.orientation;
                        }
                        SUPER.pdf_generator_generate_page(args);
                    }else{                   
                        // No more pages to generate (submit form / send email)
                        SUPER._pdf_generator_done_callback(args);
                    }
                });
            }
            catch(error) {
                console.log("Error: ", error);
            }
        }, timeout );
    }
    // PDF render text
    SUPER.pdf_generator_render_text = function(args){
        // If so add a text node on the exact position
        var i, nodes, formWidth, pdfPageWidth, scale,
            lineHeight = 1.194,
            drawRectangle = false, // true,
            renderingMode = 'invisible', // fill,
            resume, el,
            // Loop over all elements and see if the element is included in the PDF
            pdfPageContainer = document.querySelector('.super-pdf-page-container'),
            //pdfHeader = pdfPageContainer.querySelector('.super-pdf-header'),
            //pdfBody = pdfPageContainer.querySelector('.super-pdf-body'),
            //pdfFooter = pdfPageContainer.querySelector('.super-pdf-footer'),
            convertToPixel = 1,
            convertFromPixel = 1,
            charSpaceMultiplier = 0.00135;

        // Convert unit to pixel
        if(args.pdfSettings.unit=='pt') convertToPixel = 1.333333333333333;
        if(args.pdfSettings.unit=='mm') convertToPixel = 3.7795275591;
        if(args.pdfSettings.unit=='cm') convertToPixel = 37.7952755906
        if(args.pdfSettings.unit=='in') convertToPixel = 96;
        // Convert pixel to unit
        if(args.pdfSettings.unit=='pt') convertFromPixel = 0.75;
        if(args.pdfSettings.unit=='mm') convertFromPixel = 0.2645833333;
        if(args.pdfSettings.unit=='cm') convertFromPixel = 0.0264583333;
        if(args.pdfSettings.unit=='in') convertFromPixel = 0.0104166667;
        // Convert pixel to unit
        if(args.pdfSettings.unit=='pt') charSpaceMultiplier = 0.00200;
        if(args.pdfSettings.unit=='mm') charSpaceMultiplier = 0.00200;
        if(args.pdfSettings.unit=='cm') charSpaceMultiplier = 0.00200;
        if(args.pdfSettings.unit=='in') charSpaceMultiplier = 0.00200;
        var topLineHeightDivider = 1;
        if(args.pdfSettings.unit=='px') topLineHeightDivider = 2;
        var m = args.pdfSettings.margins;
        var bodyMargins = {
            top: parseFloat(m.body.top)*convertToPixel,
            right: parseFloat(m.body.right)*convertToPixel,
            bottom: parseFloat(m.body.bottom)*convertToPixel,
            left: parseFloat(m.body.left)*convertToPixel,
        };
        
        // Determine scale
        formWidth = args.form0.clientWidth;
        formWidth = formWidth + bodyMargins.left + bodyMargins.right;
        pdfPageWidth = args._pdf.internal.pageSize.getWidth()*convertToPixel;
        scale = formWidth / pdfPageWidth;
        args._pdf.setFont('NotoSans-Regular');
        args._pdf.setTextColor('red');
        args._pdf.setLineWidth(1*convertFromPixel);

        nodes = pdfPageContainer.querySelectorAll('.super-label, .super-description, .super-heading-title, .super-heading-description, .super-filled .super-adaptive-placeholder > span, .super-dropdown.super-filled .super-item.super-placeholder, .super-checkbox .super-item > div, .super-radio .super-item > div, .super-toggle-switch, .super-slider .amount, .super-calculator-currency-wrapper, .super-calculator-label, .super-fileupload-name, .super-fileupload-button-text, .super-toggle-prefix-label > span, .super-toggle-suffix-label > span, .super-html-title, .super-html-subtitle, .super-html-content, .super-text .super-shortcode-field, .super-textarea .super-shortcode-field, .super-quantity .super-shortcode-field, .super-currency .super-shortcode-field');
        for( i=0; i < nodes.length; i++ ) {
            el = nodes[i];
            // For HTML content we will need to do some special things because a HTML element will contain unkown nodes with unkown styles
            // We will first have to wrap any stand alone text inside a <div> tag. This way we can more accurately position the overlapping text
            // After that we can loop over nodes and check for child nodes and do the same thing
            // Once done we can then do a final loop
            if(el.classList.contains('super-html-content')){
                // Wrap individual text inside "span" tags
                //var regex = /(?<=(>))((?![<])(.{1,}?))(?=<)/gm;
                //var str = el.innerHTML;
                //var subst = '<span class="super-pdf-text">$2</span>';
                //// The substituted value will be contained in the result variable
                //var result = str.replace(regex, subst);
                //regex = /(?<=<.+?>)([\s]+(?!<).{1,}[\s]+)(?=<.+?>)/gm;
                //str = result;
                //subst = '<span class="super-pdf-text">$1</span>';
                //// The substituted value will be contained in the result variable
                //el.innerHTML = str.replace(regex, subst);
                //var childNodes = el.querySelectorAll('.super-pdf-text');
                //for(var x=0; x < childNodes.length; x++){
                //    resume = SUPER.draw_pdf_text(i, childNodes[x], childNodes, args, renderingMode, charSpaceMultiplier, convertFromPixel, scale, pdfPageContainer, lineHeight, topLineHeightDivider, drawRectangle);
                //    if(resume) continue;
                //}
                continue;
            }
            resume = SUPER.pdf_generator_draw_pdf_text(i, el, nodes, args, renderingMode, charSpaceMultiplier, convertFromPixel, scale, pdfPageContainer, lineHeight, topLineHeightDivider, drawRectangle);
            if(resume) continue;
        }
    };
    // PDF Draw text
    SUPER.pdf_generator_draw_pdf_text = function(i, el, nodes, args, renderingMode, charSpaceMultiplier, convertFromPixel, scale, pdfPageContainer, lineHeight, topLineHeightDivider, drawRectangle){
        args._pdf.setFont('NotoSans-Regular');
        args._pdf.setFontType('normal');

        var tmpPosTop, paddingRight, paddingLeft, paddingTop, pos, value = '';
        if(el.classList.contains('super-heading-title')){
            el = nodes[i].children[0];
        }
        if(el.classList.contains('super-heading-description')){
            el = nodes[i].children[0];
        }
        if(el.classList.contains('super-toggle-switch')){
            if(el.classList.contains('super-active')){
                el = el.querySelector('.super-toggle-on');
                value = el.querySelector('.super-toggle-on > span').innerText;
                paddingRight = (parseFloat(window.getComputedStyle(el, null).getPropertyValue('padding-right'))/scale)*convertFromPixel;
            }else{
                el = el.querySelector('.super-toggle-off');
                value = el.querySelector('.super-toggle-off > span').innerText;
                paddingLeft = (parseFloat(window.getComputedStyle(el, null).getPropertyValue('padding-left'))/scale)*convertFromPixel;
            }
        }else{
            if(el.closest('.super-text, .super-textarea, .super-quantity, .super-currency')){
                if(el.value) {
                    value = el.value;
                }else if(el.innerText) {
                    value = el.innerText;
                }
            }else{
                value = el.innerText;
            }
        }
        if(value==='') return true; //continue;
        pos = el.getBoundingClientRect();
        // Before we print the text, we must check if it's visible for this specific PDF page
        tmpPosTop = pos.top;
        // Only if not header and not footer, because these are printed on every single page
        if((!el.closest('.super-pdf-header')) && !el.closest('.super-pdf-footer')){
            var headerHeight = pdfPageContainer.querySelector('.super-pdf-header').clientHeight;
            if((tmpPosTop-(headerHeight-1)) < 0 || tmpPosTop > (args.scrollAmount+(headerHeight-1))){
                return true; //continue;
            }
        }
        var posWidth = (pos.width/scale)*convertFromPixel;
        var posHeight = (pos.height/scale)*convertFromPixel;
        var posLeft = ((pos.left+9999)/scale)*convertFromPixel;
        var posTop = ((tmpPosTop)/scale)*convertFromPixel;
        if(el.classList.contains('super-pdf-text')){
            if(el.parentNode.tagName==='STRONG' || el.parentNode.tagName==='TH'){
                args._pdf.setFont('NotoSans-Bold');
                args._pdf.setFontType('bold');
            }
            posWidth = ((pos.width+1)/scale)*convertFromPixel;
        }
        if(el.closest('.super-toggle-prefix-label') || el.closest('.super-toggle-suffix-label')){
            posTop = ((tmpPosTop+1)/scale)*convertFromPixel;
            posWidth = ((pos.width+1)/scale)*convertFromPixel;
        }
        if(el.closest('.super-radio') || el.closest('.super-checkbox') || el.classList.contains('super-fileupload-button-text')){
            posWidth = ((pos.width+1)/scale)*convertFromPixel;
        }
        var fontSize = parseFloat(window.getComputedStyle(el, null).getPropertyValue('font-size'));
        var fontSizePoint = fontSize * 0.67;
        value = args._pdf.setFontSize(fontSizePoint).splitTextToSize(value, posWidth);
        var charSpace = -(fontSize*charSpaceMultiplier)*convertFromPixel;
        var topLineHeight = (((fontSize*lineHeight)-fontSize)/topLineHeightDivider)*convertFromPixel;
        if(el.closest('.super-adaptive-placeholder')){
            posLeft = posLeft+(posWidth/2);
            if(drawRectangle) args._pdf.rect(posLeft, posTop+topLineHeight, posWidth, posHeight);
            args._pdf.text(value, posLeft, posTop+topLineHeight, {align: 'center', charSpace: charSpace, lineHeightFactor: lineHeight, baseline: 'hanging', renderingMode: renderingMode});
            return true; //continue;
        }
        if(el.closest('.super-dropdown')){
            paddingLeft = (parseFloat(window.getComputedStyle(el, null).getPropertyValue('padding-left'))/scale)*convertFromPixel;
            args._pdf.text(value, posLeft+paddingLeft, posTop+(posHeight/2), {charSpace: charSpace, lineHeightFactor: lineHeight, baseline: 'middle', renderingMode: renderingMode}); 
            return true; //continue;
        }
        if(el.closest('.super-radio') || el.closest('.super-checkbox')){
            if(drawRectangle) args._pdf.rect(posLeft, posTop+topLineHeight, posWidth, posHeight);
            args._pdf.text(value, posLeft, posTop+topLineHeight, {charSpace: charSpace, lineHeightFactor: lineHeight, baseline: 'hanging', renderingMode: renderingMode}); 
            return true; //continue;
        }
        if(el.closest('.super-toggle-switch')){
            if(el.closest('.super-toggle-switch').classList.contains('super-active')){
                if(drawRectangle) args._pdf.rect(posLeft+((posWidth-paddingRight)/2), posTop, posWidth-paddingRight, posHeight);
                args._pdf.text(value, posLeft+((posWidth-paddingRight)/2), posTop+(posHeight/2), {align: 'center', charSpace: charSpace, lineHeightFactor: lineHeight, baseline: 'middle', renderingMode: renderingMode}); 
            }else{
                if(drawRectangle) args._pdf.rect(posLeft+paddingLeft+((posWidth-paddingLeft)/2), posTop, posWidth-paddingLeft, posHeight);
                args._pdf.text(value, posLeft+paddingLeft+((posWidth-paddingLeft)/2), posTop+(posHeight/2), {align: 'center', charSpace: charSpace, lineHeightFactor: lineHeight, baseline: 'middle', renderingMode: renderingMode}); 
            }
            return true; //continue;
        }
        if(el.closest('.super-quantity')){
            if(drawRectangle) args._pdf.rect(posLeft, posTop+(posHeight/2), posWidth, posHeight);
            posLeft = posLeft+(posWidth/2);
            args._pdf.text(value, posLeft, posTop+(posHeight/2), {align: 'center', charSpace: charSpace, lineHeightFactor: lineHeight, baseline: 'middle', renderingMode: renderingMode}); 
            return true; //continue;
        }
        if(el.closest('.super-textarea')){
            paddingLeft = (parseFloat(window.getComputedStyle(el, null).getPropertyValue('padding-left'))/scale)*convertFromPixel;
            paddingTop = (parseFloat(window.getComputedStyle(el, null).getPropertyValue('padding-top'))/scale)*convertFromPixel;
            if(drawRectangle) args._pdf.rect(posLeft, posTop, posWidth, posHeight);
            args._pdf.text(value, posLeft+paddingLeft, posTop+paddingTop+topLineHeight, {charSpace: charSpace, lineHeightFactor: lineHeight, baseline: 'hanging', renderingMode: renderingMode}); 
            return true; //continue;
        }
        if(el.closest('.super-text') || el.closest('.super-currency')){
            paddingLeft = (parseFloat(window.getComputedStyle(el, null).getPropertyValue('padding-left'))/scale)*convertFromPixel;
            if(drawRectangle) args._pdf.rect(posLeft, posTop, posWidth, posHeight);
            args._pdf.text(value, posLeft+paddingLeft, posTop+(posHeight/2), {charSpace: charSpace, lineHeightFactor: lineHeight, baseline: 'middle', renderingMode: renderingMode}); 
            return true; //continue;
        }
        if(drawRectangle) args._pdf.rect(posLeft, posTop+topLineHeight, posWidth, posHeight);
        args._pdf.text(value, posLeft, posTop+topLineHeight, {charSpace: charSpace, lineHeightFactor: lineHeight, baseline: 'hanging', renderingMode: renderingMode}); 
        return true; //continue;
    };
    SUPER.pdf_generator_reset = function(form){
        // Only if not already canceled/reset
        if(form && !form.classList.contains('super-generating-pdf')){
            return false;
        }
        // Show scrollbar again
        document.documentElement.classList.remove('super-hide-scrollbar');
        var inlineStyle = document.querySelector('#super-generating-pdf');
        if(inlineStyle) inlineStyle.remove();
        // Make all mutli-parts invisible again (except for the last active multi-part)
        // Make all TABs invisible
        // Make all accordions invisible
        var nodes = form.querySelectorAll('.super-multipart,.super-tabs-content,.super-accordion-item');
        for(var i=0; i < nodes.length; i++){
            if(!nodes[i].classList.contains('super-active-origin')){
                nodes[i].classList.remove('super-active');
            }else{
                nodes[i].classList.remove('super-active-origin');
            }
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
        form.querySelector('form').style.marginTop = '';
        SUPER.reset_submit_button_loading_state(form);
        var placeholder = document.querySelector('.super-pdf-placeholder');
        placeholder.parentNode.insertBefore(form, placeholder.nextSibling);
        form.classList.remove('super-generating-pdf');
        placeholder.remove();
        var pdfPageContainer = document.querySelector('.super-pdf-page-container');
        if(pdfPageContainer) pdfPageContainer.remove();
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
        formData.append('i18n', args.form.data('i18n')); // @since 4.7.0 translation
        $.ajax({
            type: 'post',
            url: super_common_i18n.ajaxurl,
            data: formData,
            async: true,
            cache: false,
            contentType: false,
            processData: false,
            timeout: 60000, // 1m
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                if(args.showOverlay==="true"){
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = evt.loaded / evt.total;
                            //Do something with upload progress here
                            if(args.progressBar) args.progressBar.style.width = (100*percentComplete)+"%";  
                            //if(args._pdf!==false){
                            //    if(args.progressBar) args.progressBar.style.width = ((50*percentComplete)+50)+"%";  
                            //}else{
                            //    if(args.progressBar) args.progressBar.style.width = (100*percentComplete)+"%";  
                            //}
                        }
                    }, false);
                }
                return xhr;
            },
            success: function(result){
                result = JSON.parse(result);
                if(result.error===true){
                    // Display error message
                    SUPER.form_submission_finished(args, result);
                }else{
                    // Update new nonce
                    if(result.response_data && result.response_data.sf_nonce){
                        $('input[name="sf_nonce"]').val(result.response_data.sf_nonce);
                    }
                    // Clear form progression (if enabled)
                    if( args.form[0].classList.contains('super-save-progress') ) {
                        $.ajax({
                            url: super_common_i18n.ajaxurl,
                            type: 'post',
                            data: {
                                action: 'super_save_form_progress',
                                data: '',
                                form_id: args.form_id
                            }
                        });
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
        $doc.on('change keyup keydown blur', '.super-form .super-text .super-distance-calculator:not(.super-address-autopopulate)', function(){
            var field = this;
            if (timeout !== null) clearTimeout(timeout);
            timeout = setTimeout(function () {
                SUPER.calculate_distance({el: field});
            }, 1000);
        });

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
            var $this = $(this);
            if (timeout2 !== null) {
                clearTimeout(timeout2);
            }
            timeout2 = setTimeout(function () {
                var $value = $this.val();
                var $method = $this.data('wcosm');
                var $filterby = $this.data('wcosfb');
                var $return_label = $this.data('wcosrl');
                var $return_value = $this.data('wcosrv');
                var $populate = $this.data('wcosp');
                var $skip = $this.data('wcoss');
                var $status = $this.data('wcosst');
                if( $value.length>0 ) {
                    $this.parents('.super-field-wrapper:eq(0)').addClass('super-populating');
                    $.ajax({
                        url: super_common_i18n.ajaxurl,
                        type: 'post',
                        data: {
                            action: 'super_search_wc_orders',
                            value: $value,
                            method: $method,
                            filterby: $filterby,
                            return_label: $return_label,
                            return_value: $return_value,
                            populate: $populate,
                            skip: $skip,
                            status: $status
                        },
                        success: function (result) {
                            if(result!==''){
                                $this.parents('.super-shortcode:eq(0)').addClass('super-focus');
                                $this.parents('.super-shortcode:eq(0)').addClass('super-string-found');
                            }
                            var ul = $this.parents('.super-field-wrapper:eq(0)').children('.super-dropdown-list');
                            if(ul.length){
                                ul.html(result);
                            }else{
                                $('<ul class="super-dropdown-list">'+result+'</ul>').appendTo($this.parents('.super-field-wrapper:eq(0)'));
                            }
                        },
                        complete: function(){
                            $this.parents('.super-field-wrapper:eq(0)').removeClass('super-populating');
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            // eslint-disable-next-line no-console
                            console.log(xhr, ajaxOptions, thrownError);
                            alert(super_common_i18n.errors.failed_to_process_data);
                        }
                    });
                }
            }, 1000);
        });

        SUPER.init_common_fields();
    });

})(jQuery);

