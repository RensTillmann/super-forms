/* globals jQuery, SUPER, grecaptcha, super_common_i18n, ajaxurl, IBAN, tinyMCE, google, quicktags */
"use strict";
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

// reCaptcha
SUPER.reCaptchaScriptLoaded = false;
SUPER.reCaptchaverifyCallback = function($response, $version, $element){
    // Set data attribute on recaptcha containing response so we can verify this upon form submission
    $element.attr('data-response', $response);
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

        var p,
            parent = changedField.closest('.super-shortcode');

        for (p = changedField && changedField.parentElement; p; p = p.parentElement) {
            if(p.classList.contains('super-form')) break;
            if( (p.classList.contains('super-column') || p.classList.contains('super-duplicate-column-fields')) && (p.style.display === 'none') ) return true;
        }
        if( (parent.style.display=='none') && (!parent.classList.contains('super-hidden')) ) {
            return true;
        }
        
        // Also check for multi-parts if necessary
        if(typeof includeMultiParts === 'undefined') includeMultiParts = false;
        if(includeMultiParts){
            for (p = changedField && changedField.parentElement; p; p = p.parentElement) {
                if(p.classList.contains('super-form')) break;
                if( (p.classList.contains('super-multipart')) && (!p.classList.contains('super-active')) ) {
                    return true;
                }
            }
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
            }).on('fileuploaddone', function (e, data) {
                $.each(data.result.files, function (index, file) {
                    if (file.error) {
                        var error = $('<span class="super-error"/>').text(' ('+file.error+')');
                        $(data.context.children()[index]).children('.super-error').remove();
                        $(data.context.children()[index]).append(error);
                        $(data.context.children()[index]).parent('div').addClass('error');
                    }else{
                        $(data.context).addClass('super-uploaded');
                        data.context.attr('data-name',file.name).attr('data-url',file.url).attr('data-thumburl',file.thumbnailUrl);
                    }
                });
            }).on('fileuploadadd', function (e, data) {
                $(this).removeClass('finished');
                $(this).parents('.super-field-wrapper:eq(0)').find('.super-fileupload-files > div.error').remove();
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
                        alert(super_common_i18n.errors.file_upload.upload_limit_reached);
                    }else{
                        var ext = file.name.split('.').pop();
                        if( (file_types_object.indexOf(ext)!=-1) || (accepted_file_types==='') ) {
                            el.data('total-file-sizes', total);
                            data.context.parent('div').children('div[data-name="'+file.name+'"]').remove();
                            data.context.data(data).attr('data-name',file.name).html('<span class="super-fileupload-name">'+file.name+'</span><span class="super-fileupload-delete"></span>');
                            data.context.data('file-size',file.size);
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

    // @since 3.5.0 - calculate distance (google)
    var distance_calculator_timeout = null; 
    SUPER.calculate_distance = function(args){
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
                            $html += '<span class="close"></span>';
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
                        alert('Failed to process data, please try again');
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
            logic = form.querySelectorAll('.super-conditional-logic[data-fields*="{'+SUPER.get_field_name(args.el)+'}"]');
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
        if( (typeof $parent !== 'undefined') && ( ($parent.hasClass('super-dropdown')) || ($parent.hasClass('super-checkbox')) || ($parent.hasClass('super-countries')) ) ) {
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
                $parent.classList.contains('super-dropdown') || 
                $parent.classList.contains('super-countries') ) ) {
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
                $parent.classList.contains('super-dropdown') || 
                $parent.classList.contains('super-countries') ) ) {
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
                    $parent_and.classList.contains('super-dropdown') || 
                    $parent_and.classList.contains('super-countries') ) ) {
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
                    $parent_and.classList.contains('super-dropdown') || 
                    $parent_and.classList.contains('super-countries') ) ) {
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
            regex = /{([^"']*?)}/g;
            name = regex.exec(value);
            name = name[1];
            element = SUPER.field(form, name);
            if(element){
                text_field = true;
                conditionalParent = element.closest('.super-field');
                // Check if dropdown field
                if( (conditionalParent.classList.contains('super-dropdown')) || (conditionalParent.classList.contains('super-countries')) ){
                    text_field = false;
                    sum = 0;
                    selected = conditionalParent.querySelectorAll('.super-dropdown-ui .super-item.super-active:not(.super-placeholder)');
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
            if( $parent.classList.contains('super-dropdown') || $parent.classList.contains('super-countries') ){
                $selected = $parent.querySelectorAll('.super-dropdown-ui .super-item.super-active:not(.super-placeholder)');
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
        args.regex = /{([^"']*?)}/g;
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
            $regex = /{([^"']*?)}/g,
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
                                if( ($action==='show') && ($wrapper.style.display==='none' || $wrapper.style.display==='') ){
                                    $changed_wrappers.push($wrapper);
                                    $show_wrappers.push($wrapper);
                                }
                                if( ($action==='hide') && ($wrapper.style.display==='block' || $wrapper.style.display==='') ){
                                    $changed_wrappers.push($wrapper);
                                    $hide_wrappers.push($wrapper);
                                }
                                if( ($action==='readonly') && (!$wrapper.classList.contains('super-readonly')) ){
                                    $hide_wrappers.push($wrapper);
                                }
                            }else{
                                if( ($action==='show') && ($wrapper.style.display==='block' || $wrapper.style.display==='') ){
                                    $changed_wrappers.push($wrapper);
                                    $hide_wrappers.push($wrapper);
                                }
                                if( ($action==='hide') && ($wrapper.style.display==='none' || $wrapper.style.display==='') ){
                                    $changed_wrappers.push($wrapper);
                                    $show_wrappers.push($wrapper);
                                }
                                if( ($action==='readonly') && ($wrapper.classList.contains('super-readonly')) ){
                                    $show_wrappers.push($wrapper);
                                }
                            }
                        }else{
                            if($match_found!==0){
                                if( ($action==='show') && ($wrapper.style.display==='none' || $wrapper.style.display==='') ){
                                    $changed_wrappers.push($wrapper);
                                    $show_wrappers.push($wrapper);
                                }
                                if( ($action==='hide') && ($wrapper.style.display==='block' || $wrapper.style.display==='') ){
                                    $changed_wrappers.push($wrapper);
                                    $hide_wrappers.push($wrapper);
                                }
                                if( ($action==='readonly') && (!$wrapper.classList.contains('super-readonly')) ){
                                    $hide_wrappers.push($wrapper);
                                }
                            }else{
                                if( ($action==='show') && ($wrapper.style.display==='block' || $wrapper.style.display==='') ){
                                    $changed_wrappers.push($wrapper);
                                    $hide_wrappers.push($wrapper);
                                }
                                if( ($action==='hide') && ($wrapper.style.display==='none' || $wrapper.style.display==='') ){
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
                                    $hide_wrappers[key].style.display = 'none';
                                });
                                // Show wrappers
                                Object.keys($show_wrappers).forEach(function(key) {
                                    $show_wrappers[key].style.display = 'block';
                                    // Make sure signatures resizes/refreshes after becoming visible
                                    if(typeof SUPER.refresh_signatures === 'function'){
                                        SUPER.refresh_signatures('', args.form);
                                    }
                                    // Fix bug with slider element not having correct default position when initially conditionally hidden upon page load
                                    if($show_wrappers[key].classList.contains('super-slider')){
                                        var $element = $($show_wrappers[key]);
                                        var $wrapper = $element.children('.super-field-wrapper');
                                        var $field = $wrapper.children('.super-shortcode-field'); 
                                        var $value = $field.val();
                                        if($wrapper.children('.slider').length){
                                            SUPER.reposition_slider_amount_label($field[0], $value);
                                        }
                                    }else{
                                        var $sliders = $show_wrappers[key].querySelectorAll('.super-slider');
                                        Object.keys($sliders).forEach(function(skey) {
                                            var $element = $($sliders[skey]);
                                            var $wrapper = $element.children('.super-field-wrapper');
                                            var $field = $wrapper.children('.super-shortcode-field'); 
                                            var $value = $field.val();
                                            if($wrapper.children('.slider').length){
                                                SUPER.reposition_slider_amount_label($field[0], $value);
                                            }
                                        });
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
            return $validation_error;
        }

        // @since 2.3.0 - update conditional logic and other variable fields based on the updated variable field
        $.each($updated_variable_fields, function( index, field ) {
            SUPER.after_field_change_blur_hook({el: field});
        });

        // @since 1.4
        if(!$is_variable){
            SUPER.update_variable_fields(args);
        }
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
            args.conditionalLogic = args.form.querySelectorAll('.super-variable-conditions[data-fields*="{'+SUPER.get_field_name(args.el)+'}"]');
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
            $regex = /{([^"']*?)}/g;


        while (($match = $regex.exec(args.value)) !== null) {
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

                        // Check if dropdown field
                        if($parent.classList.contains('super-dropdown') || $parent.classList.contains('super-countries')){
                            $text_field = false;
                            $sum = '';

                            // @since 3.2.0 - check if we want to return integer for this {tag}  e.g: {field;2;int}
                            if($value_type=='int') {
                                $sum = 0;
                            }

                            $selected = $parent.querySelectorAll('.super-dropdown-ui .super-item.super-active:not(.super-placeholder)');
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

    // Send E-mail
    SUPER.send_email = function(args) {
        if(typeof args.pdfArgs === 'undefined') args.pdfArgs = false; 
        var innerText = args.loadingOverlay.querySelector('.super-inner-text');
        if(args.pdfArgs!==false){
            // When debugging is enabled download file instantly without submitting the form
            if(args.pdfArgs.pdfSettings.debug==="true"){
                // Direct download of PDF
                args.pdfArgs.pdf.save(args.pdfArgs.pdfSettings.filename).then(function() {
                    // Close loading overlay
                    SUPER.close_loading_overlay(args.loadingOverlay);
                }, function() {
                    // Show error message
                    if(innerText) innerText.innerHTML = '<span>Something went wrong while downloading the PDF</span>';
                });
                return false;
            }
            // Update processing state
            if(innerText) innerText.innerHTML = '<span>'+super_common_i18n.loadingOverlay.processing+'</span>';
        }
        $.ajax({
            url: super_common_i18n.ajaxurl,
            type: 'post',
            data: {
                action: 'super_send_email',
                super_ajax_nonce: args.super_ajax_nonce,
                data: args.data,
                form_id: args.form_id,
                entry_id: args.entry_id,
                token: args.token,
                version: args.version,
                i18n: args.form.data('i18n') // @since 4.7.0 translation
            },
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        //Do something with upload progress here
                        if(args.pdfArgs!==false){
                            args.progressBar.style.width = ((50*percentComplete)+50)+"%";  
                        }else{
                            args.progressBar.style.width = (100*percentComplete)+"%";  
                        }
                    }
               }, false);
               //xhr.addEventListener("progress", function(evt) {
               //    if (evt.lengthComputable) {
               //        var percentComplete = evt.loaded / evt.total;
               //        //Do something with download progress
               //    }
               //}, false);
               return xhr;
            },
            success: function(result){
                result = JSON.parse(result);
                var html;
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
                if(result.error===true){
                    // Display error message
                    SUPER.form_submission_finished(args, result, html);
                }else{
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
                                    SUPER.form_submission_finished(args, result, html);
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
                    SUPER.form_submission_finished(args, result, html);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                // eslint-disable-next-line no-console
                console.log(xhr, ajaxOptions, thrownError);
                alert('Failed to process data, please try again');
            }
        });
    };


    SUPER.close_loading_overlay = function(loadingOverlay){
        if(loadingOverlay) loadingOverlay.remove();
    };
    SUPER.reset_pdf_generation = function(form){
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
        // @@@@@@@@@@@@@@
        // // Re-enable the UI for Maps and resize to original width
        // for(i=0; i < SUPER.google_maps_api.allMaps[$form_id].length; i++){
        //     SUPER.google_maps_api.allMaps[$form_id][i].setOptions({
        //         disableDefaultUI: false
        //     });
        //     var children = SUPER.google_maps_api.allMaps[$form_id][i].__gm.Na.parentNode.querySelectorAll(':scope > div');
        //     for(var x=0; x < children.length; x++){
        //         children[x].style.width = '';
        //         if(children[x].classList.contains('super-google-map-directions')){
        //             children[x].style.overflowY = 'scroll';
        //             children[x].style.height = SUPER.google_maps_api.allMaps[$form_id][i].__gm.Na.offsetHeight+'px';
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
    SUPER.reset_submit_button_loading_state = function(form){
        var submitButton = form.querySelector('.super-form-button.super-loading');
        if(submitButton){
            submitButton.classList.remove('super-loading');
            var buttonName = submitButton.querySelector('.super-button-name');
            var normal = buttonName.dataset.normal;
            buttonName.innerHTML = normal;
        }
    };

    SUPER.before_generate_pdf = function(args, callback){
        var form = args.form0;

        // Define PDF tags
        SUPER.pdf_tags = {
            pdf_page: '{pdf_page}',
            pdf_total_pages: '{pdf_total_pages}'
        };

        // Must hide scrollbar
        document.documentElement.classList.add('super-hide-scrollbar');
        form.classList.add('super-generating-pdf');

        // Must hide elements
        var css = '.super-hide-scrollbar {overflow: -moz-hidden-unscrollable!important; overflow: hidden!important;}';
        // Required to render pseudo elements (html2canvas code was altered for this)
        css += '.super-pdf-page-container.super-pdf-clone .super-form *:before,';
        css += '.super-pdf-page-container.super-pdf-clone .super-form *:after {display:none!important;}';
        // Hide none essential elements/styles from the PDF output
        css += '.super-generating-pdf *,';
        css += '.super-generating-pdf *:after,';
        css += '.super-generating-pdf .super-accordion-header:after,';
        css += '.super-generating-pdf .super-accordion-header:before { transition: initial!important; }';
        css += '.super-generating-pdf .super-accordion-header:before,';
        css += '.super-generating-pdf .super-accordion-header:after,';
        css += '.super-generating-pdf .super-form-button,';
        css += '.super-generating-pdf .super-multipart-progress,';
        css += '.super-generating-pdf .super-multipart-steps,';
        css += '.super-generating-pdf .super-prev-multipart,';
        css += '.super-generating-pdf .super-next-multipart,';
        css += '.super-generating-pdf .super-tabs-menu,';
        css += '.super-generating-pdf .super-signature-clear { display: none!important; }';
        css += '.super-generating-pdf .super-accordion-header { border: 1px solid #d2d2d2; }';
        css += '.super-generating-pdf .super-accordion-header { border: 1px solid #d2d2d2; }';
        css += '.super-pdf-header, .super-pdf-body, .super-pdf-footer { display: block; float: left; width: 100%; overflow: hidden; }';
        // Header margins
        css += '.super-pdf-header { padding: '+args.pdfSettings.margins.header.top+args.pdfSettings.unit+' '+args.pdfSettings.margins.header.right+args.pdfSettings.unit+' '+args.pdfSettings.margins.header.bottom+args.pdfSettings.unit+' '+args.pdfSettings.margins.header.left+args.pdfSettings.unit+'; }';
        css += '.super-pdf-header .super-form, .super-pdf-header .super-form form { padding: 0!important; margin: 0!important; float: left!important; width: 100%!important; }';
        // Body margins
        css += '.super-pdf-body { padding: '+args.pdfSettings.margins.body.top+args.pdfSettings.unit+' '+args.pdfSettings.margins.body.right+args.pdfSettings.unit+' '+args.pdfSettings.margins.body.bottom+args.pdfSettings.unit+' '+args.pdfSettings.margins.body.left+args.pdfSettings.unit+'; }';
        // Footer margins
        css += '.super-pdf-footer { padding: '+args.pdfSettings.margins.footer.top+args.pdfSettings.unit+' '+args.pdfSettings.margins.footer.right+args.pdfSettings.unit+' '+args.pdfSettings.margins.footer.bottom+args.pdfSettings.unit+' '+args.pdfSettings.margins.footer.left+args.pdfSettings.unit+'; }';
        css += '.super-pdf-footer .super-form, .super-pdf-footer .super-form form { padding: 0!important; margin: 0!important; float: left!important; width: 100%!important; }';

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

        // Firstly clone the form, so we can display a "fake" form
        var placeholder = form.cloneNode(true);
        placeholder.classList.add('super-pdf-placeholder');
        args.placeholder = placeholder;
        var headerClone = form.cloneNode(true);
        var footerClone = form.cloneNode(true);
        form.parentNode.insertBefore(placeholder, form.nextSibling);
        
        // PDF page container
        var pdfPageContainer = document.createElement('div');
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
        pdfPageContainer.style.left = "9999px";
        pdfPageContainer.style.top = "-9999px";
        // ------- for debugging only: ----
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

        // @@@ WE DO NO LONGER NEED THIS, IT IS HANDLED BY CSS @@@
        // // Check for any specific PDF exclusions
        // nodes = form.querySelectorAll('.super-shortcode[data-pdfoption="exclude"]');
        // for(i=0; i < nodes.length; i++){
        //     nodes[i].style.display = 'none';
        // }
        // // Check for any specific PDF inclusions
        // nodes = form.querySelectorAll('.super-shortcode[data-pdfoption="include"],.super-shortcode[data-pdfoption="header"],.super-shortcode[data-pdfoption="footer"]');
        // for(i=0; i < nodes.length; i++){
        //     nodes[i].style.display = 'block';
        // }
        // @@@ WE DO NO LONGER NEED THIS, IT IS HANDLED BY CSS @@@

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

        // nodes = form.querySelector('.super-shortcode[data-pdfoption="footer"]');
        // footerClone.querySelector('form').innerHTML = '';
        // var footer;
        // for(i=0; i < nodes.length; i++){
        //     // Check if is column element, if so we must grab grid instead of column itself 
        //     if(nodes[i].classList.contains('super-column')){
        //         footer = nodes[i].closest('.super-grid').cloneNode(true);
        //     }else{
        //         footer = nodes[i].cloneNode(true);
        //     }
        //     footer.classList.add('pdf-generated-footer');
        //     footerClone.querySelector('form').appendChild(footer);
        // }

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
        for(var i=0; i < nodes.length; i++){
            if(nodes[i].classList.contains('super-active')){
                nodes[i].classList.add('super-active-origin');         
            }else{
                nodes[i].classList.add('super-active');
            }
        }

        SUPER.init_super_responsive_form_fields({form: form, callback: function(){
            // First disable the UI on the map for nicer print of the map
            // And make map fullwidth and directions fullwidth
            for(i=0; i < SUPER.google_maps_api.allMaps[formId].length; i++){
                SUPER.google_maps_api.allMaps[formId][i].setOptions({
                    disableDefaultUI: true
                });
                nodes = SUPER.google_maps_api.allMaps[formId][i].__gm.Na.parentNode.querySelectorAll(':scope > div');
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
            // var textAreas = [].slice.call(document.querySelectorAll('textarea[data-adaptheight]'));
            var textAreas = form.querySelectorAll('.super-textarea .super-shortcode-field');
            // iterate through all the textareas on the page
            textAreas.forEach(function(el) {
                // we need box-sizing: border-box, if the textarea has padding
                el.style.boxSizing = el.style.mozBoxSizing = 'border-box';
                // we don't need any scrollbars, do we? :)
                el.style.overflowY = 'hidden';
                // the minimum height initiated through the "rows" attribute
                var minHeight = el.scrollHeight;
                el.addEventListener('input', function() {
                    adjustHeight(el, minHeight);
                });
                // we have to readjust when window size changes (e.g. orientation change)
                window.addEventListener('resize', function() {
                    adjustHeight(el, minHeight);
                });
                // we adjust height to the initial content
                adjustHeight(el, minHeight);
            });

            // Grab the total form height, this is required to know how many pages will be generated for the PDF file
            // This way we can also show the progression to the end user
            //scrollAmount = (pageHeightInPixels*2);
            args.totalPages = Math.ceil(form.clientHeight/args.scrollAmount);
            args.progressBar.style.width = 5+'%';
            callback(args);
        }});
    };

    // Send form submission through ajax request
    SUPER.create_ajax_request = function(args){
        var form = $(args.form),
            data,
            form_id,
            entry_id,
            json_data,
            version,
            super_ajax_nonce;

        form_id = args.data.form_id;
        entry_id = args.data.entry_id;

        // @since 1.3
        data = SUPER.after_form_data_collected_hook(args.data.data);

        // @since 3.2.0 - honeypot captcha check, if value is not empty cancel form submission
        data.super_hp = form.find('input[name="super_hp"]').val();
        if(data.super_hp!==''){
            return false;
        }

        // @since 4.6.0 - Send ajax nonce
        super_ajax_nonce = form.find('input[name="super_ajax_nonce"]').val();

        // @since 2.9.0 - json data POST
        json_data = JSON.stringify(data);
        form.find('textarea[name="json_data"]').val(json_data);

        if(typeof args.token === 'undefined'){
            if(form.find('.super-recaptcha:not(.g-recaptcha)').length!==0){
                version = 'v2';
                args.token = form.find('.super-recaptcha:not(.g-recaptcha) .super-recaptcha').attr('data-response');
            }
        }else{
            version = 'v3';
        }
        args.callback = function(args){
            // Create loader overlay
            var loadingOverlay = document.createElement('div');
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
            loadingOverlay.innerHTML = html;
            loadingOverlay.classList.add('super-loading-overlay');
            loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+super_common_i18n.loadingOverlay.processing+'</span>';
            loadingOverlay.querySelector('.super-close').innerHTML = '<span>'+super_common_i18n.loadingOverlay.close+'</span>';
            var generatePdf = false, pdfSettings;
            if(typeof SUPER.form_js !== 'undefined' && typeof SUPER.form_js[form_id]._pdf !== 'undefined' && SUPER.form_js[form_id]._pdf.generate === "true"){
                generatePdf = true;
                pdfSettings = SUPER.form_js[form_id]._pdf;
                loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+pdfSettings.generatingText+'</span>';
            }else{
                // In case we are in back-end preview mode
                if(typeof SUPER.get_form_settings === 'function' && typeof SUPER.get_form_settings()._pdf !== 'undefined' && SUPER.get_form_settings()._pdf.generate === "true" ){
                    generatePdf = true;
                    pdfSettings = SUPER.get_form_settings()._pdf;
                    loadingOverlay.querySelector('.super-inner-text').innerHTML = '<span>'+pdfSettings.generatingText+'</span>';
                }
            }

            document.body.appendChild(loadingOverlay);
            // Close Popup (if any)
            if(typeof SUPER.init_popups === 'function' && typeof SUPER.init_popups.close === 'function' ){
                SUPER.init_popups.close(true);
            }

            // Close modal (should also reset pdf generation)
            var closeBtn = loadingOverlay.querySelector('.super-close');
            if(closeBtn){
                closeBtn.addEventListener('click', function(){
                    // Close overlay
                    SUPER.close_loading_overlay(loadingOverlay);
                });
            }

            var progressBar = document.querySelector('.super-loading-overlay .super-progress-bar');
            args = {
                form: form,
                form0: form[0],
                super_ajax_nonce: super_ajax_nonce,
                oldHtml: args.oldHtml,
                data: data,
                form_id: form_id,
                entry_id: entry_id,
                token: args.token,
                version: version,
                loadingOverlay: loadingOverlay,
                progressBar: progressBar
            }

            // Generate PDF
            if( generatePdf ){
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

                var orientation = pdfSettings.orientation;
                var format = pdfSettings.format;
                // Check if custom format is defined
                var customFormat = pdfSettings.customformat;
                if(typeof customFormat !== 'undefined' && customFormat!==''){
                    customFormat = customFormat.split(',');
                    if(typeof customFormat[1] !== 'undefined'){
                        customFormat[0] = customFormat[0].trim();
                        customFormat[1] = customFormat[1].trim();
                        if(customFormat[0]!=='' && customFormat[1]!==''){
                            format = customFormat;
                        }
                    }
                }

                // For quick debugging purposes only:
                // eslint-disable-next-line no-undef
                var pdf = new jsPDF({
                    orientation: orientation,   // Orientation of the first page. Possible values are "portrait" or "landscape" (or shortcuts "p" or "l").
                    format: format,             // The format of the first page.  Default is "a4"
                    putOnlyUsedFonts: false,    // Only put fonts into the PDF, which were used.
                    compress: false,            // Compress the generated PDF.
                    precision: 16,              // Precision of the element-positions.
                    userUnit: 1.0,              // Not to be confused with the base unit. Please inform yourself before you use it.
                    floatPrecision: 16,         // or "smart", default is 16
                    unit: pdfSettings.unit                  // Measurement unit (base unit) to be used when coordinates are specified.
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

                var pageWidth = pdf.internal.pageSize.getWidth();
                var pageHeight = pdf.internal.pageSize.getHeight();

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

                var k = 1;
                if(pdfSettings.unit=='pt') k = 1.333333333333333;
                if(pdfSettings.unit=='mm') k = 0.4703703703703702;
                if(pdfSettings.unit=='cm') k = 0.04703703703703702;
                if(pdfSettings.unit=='in') k = 0.0185185185010975;

                var pageWidthInPixels = pageWidth / k;
                var pageHeightInPixels = pageHeight / k;
                // Make form scrollable based on a4 height
                var scrollAmount = 0;

                args.pageWidth = pageWidth;
                args.pageHeight = pageHeight;
                args.pageWidthInPixels = pageWidthInPixels;
                args.pageHeightInPixels = pageHeightInPixels;
                args.pdfSettings = pdfSettings;
                args.scrollAmount = scrollAmount;
                args.pdf = pdf;

                // Blur/unfocus any focussed field
                // bug in google chrome on mobile devices
                // .....
                //
                // Add a timeout (just to be sure)
                setTimeout(function(){
                    SUPER.before_generate_pdf(args, function(args){
                        // Start generating pages (starting at page 1)
                        args.currentPage = 1;
                        pdf = SUPER.generate_pdf(args, function(pdf, form){
                            // Reset everything to how it was
                            SUPER.reset_pdf_generation(form);

                            // Attach as file to form data
                            var datauristring = pdf.output('datauristring', {
                                filename: pdfSettings.filename
                            });
                            var exclude = 0;
                            if(pdfSettings.adminEmail!=='true' && pdfSettings.confirmationEmail!=='true'){
                                exclude = 2; // Exclude from both emails
                            }else{
                                if(pdfSettings.adminEmail==='true' && pdfSettings.confirmationEmail==='true'){
                                    exclude = 0; // Do not exclude
                                }else{
                                    if(pdfSettings.adminEmail==='true'){
                                        exclude = 1; // Exclude from confirmation email only
                                    }
                                    if(pdfSettings.confirmationEmail==='true'){
                                        exclude = 3; // Exclude from admin email only
                                    }
                                }
                            }
                            data._generated_pdf_file = {
                                files: [{
                                    label: pdfSettings.emailLabel,
                                    name: pdfSettings.filename,
                                    datauristring: datauristring,
                                    value: pdfSettings.filename
                                }],
                                label: pdfSettings.emailLabel,
                                type: 'files',
                                exclude: exclude
                            };
                            args.pdfArgs = {
                                pdfSettings: pdfSettings,
                                pdf: pdf
                            }
                            SUPER.send_email(args);
                        }); 
                    });
                }, 500);
                return false;
            }
            // We do not need to generate a PDF
            SUPER.send_email(args);
        };
        SUPER.before_email_send_hook(args);
    };
    // Show PDF download button
    SUPER.show_pdf_download_btn = function(args){
        var btn = document.createElement('div');
        btn.classList.add('super-pdf-download-btn');
        btn.innerHTML = args.pdfArgs.pdfSettings.downloadBtnText;
        args.loadingOverlay.querySelector('.super-loading-text').appendChild(btn);
        btn.addEventListener('click', function(){
            args.pdfArgs.pdf.save(args.pdfArgs.pdfSettings.filename);
        });
    };
    // Form submission is finished
    SUPER.form_submission_finished = function(args, result){ 
        if(args.progressBar) args.progressBar.style.width = 100+"%";  
        var innerText = args.loadingOverlay.querySelector('.super-inner-text');
        // Redirect user to specified url
        if(result.redirect){
            // Show redirecting text
            if(innerText) innerText.innerHTML = '<span>'+super_common_i18n.loadingOverlay.redirecting+'</span>';
            window.location.href = result.redirect;
        }else{
            if(innerText) innerText.innerHTML = '<span>'+super_common_i18n.loadingOverlay.completed+'</span>';
            // Check if there is a message
            if(result.msg!==''){
                // Check if this is an error message
                if(result.error===true){
                    args.loadingOverlay.classList.add('super-error');
                }else{
                    args.loadingOverlay.classList.add('super-success');
                    if(args.pdfArgs && args.pdfArgs.pdfSettings.downloadBtn==='true'){
                        SUPER.show_pdf_download_btn(args);
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
                if(args.pdfArgs && args.pdfArgs.pdfSettings.downloadBtn==='true'){
                    args.loadingOverlay.classList.add('super-success');
                    SUPER.show_pdf_download_btn(args);
                }else{
                    // Just close the overlay, no need to show download button
                    SUPER.close_loading_overlay(args.loadingOverlay);
                }
            }
            // @since 3.4.0 - keep loading state active
            if(result.loading!==true){
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
                            SUPER.init_clear_form(args.form0);
                        }
                    }
                }
            }
        }
    };

    // File upload handler
    SUPER.upload_files = function(args){
        var i,nodes,minfiles,$this,wrapper,field,interval,total_file_uploads,shortcode_field;
        
        nodes = args.form.querySelectorAll('.super-fileupload-files');
        for( i = 0; i < nodes.length; i++) {
            minfiles = nodes[i].parentNode.querySelector('.super-active-files').dataset.minfiles;
            if( typeof minfiles === 'undefined' ) {
                minfiles = 0;
            }
            if( ( minfiles===0 ) && ( nodes[i].parentNode.querySelectorAll('.super-fileupload-files > div').length===0 ) ) {
                nodes[i].parentNode.querySelector('.super-fileupload').classList.add('finished');
            }
        }
        nodes = args.form.querySelectorAll('.super-fileupload-files > div:not(.super-uploaded)');
        for( i = 0; i < nodes.length; i++) {
            args.data = $(nodes[i]).data();
            args.data.submit();
        }
        $(args.form).find('.super-fileupload').on('fileuploaddone', function (e, data) {
            $this = $(this);
            wrapper = $this.parents('.super-field-wrapper:eq(0)');
            field = $(this).parents('.super-field-wrapper:eq(0)').children('input[type="hidden"]');
            $.each(data.result.files, function (index, file) {
                if(!file.error){
                    if(field.val()===''){
                        field.val(file.name);
                    }else{
                        field.val(field.val()+','+file.name);
                    }
                }
            });
            data[field.attr('name')] = field.val();
            if(wrapper.find('.super-fileupload-files > div.error').length){
                $(args.form).find('.super-form-button.super-loading .super-button-name').html(args.oldHtml);
                $(args.form).find('.super-form-button.super-loading').removeClass('super-loading');
                clearInterval(interval);
            }else{
                // Let's check if there are any errors with one of the files
                // If so we do not want to submit the form, we prevent this by not adding the "finished" class
                if(wrapper.find('.super-fileupload-files > div.error').length==0){
                    // There are no errors, let's check if the total list equals to the total files that were successfully uploaded
                    if(wrapper.find('.super-fileupload-files > div').length == wrapper.find('.super-fileupload-files > div.super-uploaded').length){
                        $(this).addClass('finished');
                    }
                }
            }
        });
        interval = setInterval(function() {
            total_file_uploads = 0;
            $(args.form).find('.super-fileupload').each(function(){
                shortcode_field = $(this);
                if( SUPER.has_hidden_parent(shortcode_field[0])===false ) {
                    total_file_uploads++;
                }else{
                    shortcode_field.removeClass('finished');
                }
            });
            if($(args.form).find('.super-fileupload.finished').length == total_file_uploads){
                clearInterval(interval);
                SUPER.init_fileupload_fields();
                $(args.form).find('.super-fileupload').removeClass('super-rendered').fileupload('destroy');
                args.data = SUPER.prepare_form_data($(args.form));
                args.callback = function(){
                    setTimeout(function() {
                        SUPER.complete_submit(args);
                    }, 1000);
                };
                SUPER.before_submit_hook(args);
            }
        }, 1000);
    };

    // Trim strings
    SUPER.trim = function($this) {
        if(typeof $this === 'string'){
            return $this.replace(/^\s+|\s+$|\s+(?=\s)/g, "");
        }
    };

    // Check for errors, validate fields
    SUPER.handle_validations = function(args){
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
            conditions,
            field_value,
            value2,
            counter,
            index,
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
                    conditions = parent.querySelectorAll('.super-validate-conditions');
                    if (conditions) {
                        result = SUPER.conditional_logic.loop(args);
                        if (!result) {
                            allowEmpty = false; // when condition is met, we do not allow field to be empty
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
            if( (parent.classList.contains('super-dropdown')) || (parent.classList.contains('super-countries')) ){
                text_field = false;
                total = parent.querySelectorAll('.super-dropdown-ui .super-item.super-active:not(.super-placeholder)').length;
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
            if( (parent.classList.contains('super-dropdown')) || (parent.classList.contains('super-countries')) ){
                text_field = false;
                total = parent.querySelectorAll('.super-dropdown-ui .super-item.super-active:not(.super-placeholder)').length;
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
        // Display error messages
        if(allowEmpty && args.el.value==='') error = false;
        if(typeof args.validation !== 'undefined' && !allowEmpty && args.el.value==='') error = true;
        if(error){
            SUPER.handle_errors(args.el);
            // Add error class to Multi-part
            index = $(args.el).parents('.super-multipart:eq(0)').index('.super-form:eq(0) .super-multipart');
            if(args.el.closest('.super-form') && args.el.closest('.super-form').querySelectorAll('.super-multipart-step')[index]){
                args.el.closest('.super-form').querySelectorAll('.super-multipart-step')[index].classList.add('super-error');
            }
            // Add error class to TABS
            if(args.el.closest('.super-tabs')){
                index = $(args.el.closest('.super-tabs-content')).index();
                if(args.el.closest('.super-tabs').querySelectorAll('.super-tabs-tab')[index]){
                    args.el.closest('.super-tabs').querySelectorAll('.super-tabs-tab')[index].classList.add('super-error');
                }
            }
            // Add error class to Accordion
            if(args.el.closest('.super-accordion-item')){
                args.el.closest('.super-accordion-item').classList.add('super-error');
            }
        }else{
            if(args.el.closest('.super-field')) args.el.closest('.super-field').classList.remove('super-error-active');
        }
        // Remove error class from Multi-part if no more errors where found
        if( args.el.closest('.super-multipart') && 
            !args.el.closest('.super-multipart').querySelector('.super-error-active')){
                index = $(args.el).parents('.super-multipart:eq(0)').index('.super-form:eq(0) .super-multipart');
                if(args.el.closest('.super-form') && args.el.closest('.super-form').querySelectorAll('.super-multipart-step')[index]){
                    args.el.closest('.super-form').querySelectorAll('.super-multipart-step')[index].classList.remove('super-error');
                }
        }
        // Remove error class from TABS
        if( args.el.closest('.super-tabs-content') && 
            !args.el.closest('.super-tabs-content').querySelector('.super-error-active')){
                index = $(args.el.closest('.super-tabs-content')).index();
                if(args.el.closest('.super-tabs') && args.el.closest('.super-tabs').querySelectorAll('.super-tabs-tab')[index]){
                    args.el.closest('.super-tabs').querySelectorAll('.super-tabs-tab')[index].classList.remove('super-error');
                }
        }
        // Remove error class from Accordion if no more errors where found
        if( args.el.closest('.super-accordion-item') && 
            !args.el.closest('.super-accordion-item').querySelector('.super-error-active')){
                args.el.closest('.super-accordion-item').classList.remove('super-error');
        }
        return error;
    };

    // Output errors for each field
    SUPER.handle_errors = function(el){       
        if(el.closest('.super-field')) el.closest('.super-field').classList.add('super-error-active');
    };

    // Validate the form
    SUPER.validate_form = function(args){ // form, submitButton, validateMultipart, e, doingSubmit
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
            regex = /{([^"']*?)}/g,
            array = [],
            error = false,
            name,
            field,
            element,
            target,
            submitButtonName,
            oldHtml,
            loading,
            index,
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
            SUPER.init_clear_form(args.form);
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

        //nodes = form.querySelectorAll('.super-field .super-shortcode-field, .super-field .super-recaptcha, .super-field .super-active-files');
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
                        index = $(field).parents('.super-multipart:eq(0)').index('.super-form:eq(0) .super-multipart');
                        $(field).parents('.super-form:eq(0)').find('.super-multipart-steps').children('.super-multipart-step:eq('+index+')').addClass('super-error');
                    }else{
                        field.closest('.super-field').classList.remove('super-error-active');
                    }
                    if(field.closest('.super-multipart')){
                        if(!field.closest('.super-multipart').querySelector('.super-error-active')){
                            index = $(field).parents('.super-multipart:eq(0)').index('.super-form:eq(0) .super-multipart');
                            $(field).parents('.super-form:eq(0)').find('.super-multipart-steps').children('.super-multipart-step:eq('+index+')').removeClass('super-error');
                        }
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

            submitButtonName.innerHTML = '<i class="fas fa-refresh fa-spin"></i>'+loading;
            // Prepare arguments
            args = {
                event: args.event,
                form: args.form,
                data: SUPER.prepare_form_data($(args.form)),
                oldHtml: oldHtml,
            };
            if (args.form.querySelectorAll('.super-fileupload-files > div').length !== 0) {
                SUPER.upload_files(args);
            }else{
                args.callback = function(){
                    SUPER.complete_submit(args);
                };
                SUPER.before_submit_hook(args);
            }
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
                $('html, body').animate({
                    scrollTop: $(form).offset().top - 30 
                }, 1000);
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
                                    activeMultipart.querySelector('.super-next-multipart').click();
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
                        nodes[i].remove();
                    }
                    if(typeof result.fields !== 'undefined'){
                        $.each(result.fields, function( index, value ) {
                            $(value+'[name="'+index+'"]').parent().addClass('error');
                        });
                    }                               
                    html += result.msg;
                    html += '<span class="close"></span>';
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

    SUPER.after_dropdown_change_hook = function(args){
        args.form = SUPER.get_frontend_or_backend_form(args);
        var $functions = super_common_i18n.dynamic_functions.after_dropdown_change_hook;
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
    SUPER.after_field_change_blur_hook = function(args){
        if( typeof args.el !== 'undefined' ) {
            if(args.el.value===''){
                if(args.el.closest('.super-shortcode')) {
                    args.el.closest('.super-shortcode').classList.remove('super-filled');
                }
            }else{
                if(args.el.closest('.super-shortcode')) args.el.closest('.super-shortcode').classList.add('super-filled');
            }
        }
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
    SUPER.after_radio_change_hook = function(args){
        args.form = SUPER.get_frontend_or_backend_form(args);
        var $functions = super_common_i18n.dynamic_functions.after_radio_change_hook;
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
    SUPER.after_checkbox_change_hook = function(args){
        args.form = SUPER.get_frontend_or_backend_form(args);
        var $functions = super_common_i18n.dynamic_functions.after_checkbox_change_hook;
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
                            'thumburl':$file.attr('data-thumburl'),
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
                        var $value = $super_field.find('.super-field-wrapper .super-dropdown-ui > .super-active').attr('data-value');
                        if( typeof $value !== 'undefined' ) {
                            // Also make sure to always save the first value
                            $data[$this.attr('name')].value = $value.split(";")[0];
                        }
                    }

                    if( $super_field.hasClass('super-dropdown') ) {
                        $i = 0;
                        $new_value = '';
                        $selected_items = $super_field.find('.super-field-wrapper .super-dropdown-ui > .super-active');
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
        return {data:$data, form_id:$form_id, entry_id:$entry_id};
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

    SUPER.get_field_name = function($field){
        if($field.name) return $field.name;
    };

    // PDF Generation
    SUPER.generate_pdf = function(args, callback){
        
        var form = args.form0.closest('.super-form');
        // When canceled the following class will no longer exist, and we should not proceed
        if(form && !form.classList.contains('super-generating-pdf')){
            return false;
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
        SUPER.after_field_change_blur_hook({el: undefined, from: pdfFooterForm});

        // Scroll to the "fake" page
        form.querySelector('form').style.marginTop = "-"+(args.scrollAmount * (args.currentPage-1))+'px';

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
                html2canvas(document.querySelector('.super-pdf-page-container'), {
                    scrollX: 0, // Important, do not remove
                    scrollY: 0,  // -window.scrollY, // Important, do not remove
                    scale: args.pdfSettings.renderScale, // The scale to use for rendering (higher means better quality, but larger file size)
                    currentPage: args.currentPage,
                    useCORS: true,
                    allowTaint: false,
                    backgroundColor: '#ffffff',
                    //onclone: function(document){
                    //    // Remove any elements that we do not need
                    //    var i, nodes = document.body.childNodes;
                    //    console.log(nodes.length);
                    //    for(i=0; i<nodes.length; i++){
                    //        if(nodes[i].tagName==='LINK') continue;
                    //        if(nodes[i].tagName==='SCRIPT') continue;
                    //        if(nodes[i].className==='super-pdf-page-container') continue;
                    //        nodes[i].remove();
                    //    }
                    //    nodes = document.body.childNodes;
                    //    console.log(nodes.length);
                    //},
                }).then(canvas => {    
                    // Only if not already canceled/reset
                    if(form && !form.classList.contains('super-generating-pdf')){
                        return false;
                    }
                    var percentage = ((50/(args.totalPages+1))*args.currentPage)+5;
                    if(percentage<5) percentage = 5;
                    if(percentage>=50) percentage = 50;
                    args.progressBar.style.width = percentage+"%";  
                    var imgData = canvas.toDataURL("image/jpeg", 1.0);
                    // Add this image as 1 single page
                    args.pdf.addImage(
                        imgData,    // imageData as base64 encoded DataUrl or Image-HTMLElement or Canvas-HTMLElement
                        'JPEG',     // format of file if filetype-recognition fails or in case of a Canvas-Element needs to be specified (default for Canvas is JPEG),
                                    // e.g. 'JPEG', 'PNG', 'WEBP'
                        0,          // x Coordinate (in units declared at inception of PDF document) against left edge of the page
                        0,          // y Coordinate (in units declared at inception of PDF document) against upper edge of the page
                        args.pageWidth,
                        args.pageHeight
                    );
                    // If there are more pages to be processed, go ahead
                    if(form.querySelector('form').clientHeight > (args.scrollAmount * args.currentPage)){
                        args.currentPage++;
                        args.pdf.addPage();
                        SUPER.generate_pdf(args, callback);
                    }else{                   
                        // No more pages to generate (submit form / send email)
                        callback(args.pdf, form);
                    }
                });
            }
            catch(error) {
                console.log("Error: ", error);
            }
        }, timeout );
    }
    SUPER.google_maps_api.allMaps = [];
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
            var field_name = SUPER.get_field_name(args.el);
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
                    var regex = /{([^"']*?)}/g;
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
                    panel: ($directionsPanel=='true' ? document.querySelector('.super-google-map-'+$form_id).parentNode.querySelector('.super-google-map-directions') : null)
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
                        var html = '<div class="super-msg super-error">';
                        SUPER.form_submission_finished(args, result, html);
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
                        var html = '<div class="super-msg super-error">';
                        SUPER.form_submission_finished(args, result, html);
                    }
                });
                return true;
            }

            // // Center map if needed
            // if( ($origin!=='') && ($center_based_on_address===true) ) {
            //     geocoder = new google.maps.Geocoder();
            //     // Replace with tag if needed
            //     $origin = SUPER.update_variable_fields.replace_tags($form, $regular_expression, $origin);

            //     // Check if address is not empty
            //     if($origin!==''){
            //         geocoder.geocode( { 'address': $origin}, function(results, status) {
            //             if (status == 'OK') {
            //                 // Center map based on given address
            //                 $map.setCenter(results[0].geometry.location);
            //                 // Add marker on address location
            //                 if( $address_marker=='true' ) {
            //                     new google.maps.Marker({
            //                         map: $map,
            //                         position: results[0].geometry.location
            //                     });
            //                 }
            //             } else {
            //                 console.log('Geocode was not successful for the following reason: ' + status);
            //             }
            //         });

            //         if($destination!=='') {
            //             $destination = SUPER.update_variable_fields.replace_tags($form, $regular_expression, $destination);
            //             $travelMode = SUPER.update_variable_fields.replace_tags($form, $regular_expression, $travelMode);
            //             var directionsService = new google.maps.DirectionsService();
            //             var directionsRenderer = new google.maps.DirectionsRenderer();
            //             directionsRenderer.setMap($map);
            //             var request = {
            //                 origin: $origin,
            //                 destination: $destination,
            //                 travelMode: $travelMode
            //             };
            //             directionsService.route(request, function (result, status) {
            //                 if (status == 'OK') {
            //                     directionsRenderer.setDirections(result);
            //                 }else{
            //                     // Display error message
            //                     result = {
            //                         msg: 'Route was not successful for the following reason: ' + status,
            //                         loading: true
            //                     }
            //                     var html = '<div class="super-msg super-error">';
            //                     SUPER.form_submission_finished($form[0], result, html);
            //                 }
            //             });
            //         }
            //     }
            // }
        });
    };

    SUPER.google_maps_api.initAutocomplete = function(args){
        var inputField,
            items = args.form.querySelectorAll('.super-address-autopopulate:not(.super-autopopulate-init)');
        
        Object.keys(items).forEach(function(key) {
            var field = items[key];
            field.classList.add('super-autopopulate-init');
            var autocomplete = new google.maps.places.Autocomplete( field, {types: ['geocode']} );
            autocomplete.addListener( 'place_changed', function () {
                // Set text field to the formatted address
                var place = autocomplete.getPlace();
                field.value = place.formatted_address;
                SUPER.calculate_distance({el: field});

                var mapping = {
                    street_number: 'street_number',
                    route: 'street_name',
                    locality: 'city',
                    administrative_area_level_2: 'municipality',
                    administrative_area_level_1: 'state',
                    country: 'country',
                    postal_code: 'postal_code'
                };
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
                field.dataset.lat = lat;
                field.dataset.lng = lng;

                // @since 3.5.0 - trigger / update google maps in case {tags} have been used
                args.el = field;
                SUPER.google_maps_init(args);

                $(field).trigger('keyup');
                var $attribute;
                var $val;
                var $address;
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
                    $attribute = $(field).data('map-'+mapping[types[0]]);
                    if(typeof $attribute !=='undefined'){
                        $attribute = $attribute.split('|');
                        if($attribute[1]==='') $attribute[1] = 'long';
                        $val = place.address_components[i][$attribute[1]+'_name'];
                        inputField = SUPER.field(args.form, $attribute[0]);
                        inputField.value = $val;
                        if($val===''){
                            inputField.closest('.super-shortcode').classList.remove('super-filled');
                        }else{
                            inputField.closest('.super-shortcode').classList.add('super-filled');
                        }
                        SUPER.after_dropdown_change_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
                    }
                }

                // @since 3.5.0 - combine street name and number
                $attribute = $(field).data('map-street_name_number');
                if( typeof $attribute !=='undefined' ) {
                    $attribute = $attribute.split('|');
                    $address = '';
                    if( street_data.name[$attribute[1]]!=='' ) $address += street_data.name[$attribute[1]];
                    if( $address!=='' ) {
                        $address += ' '+street_data.number[$attribute[1]];
                    }else{
                        $address += street_data.number[$attribute[1]];
                    }
                    inputField = SUPER.field(args.form, $attribute[0]);
                    inputField.value = $address;
                    if($address===''){
                        inputField.closest('.super-shortcode').classList.remove('super-filled');
                    }else{
                        inputField.closest('.super-shortcode').classList.add('super-filled');
                    }
                    SUPER.after_dropdown_change_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
                }

                // @since 3.5.1 - combine street number and name
                $attribute = $(field).data('map-street_number_name');
                if( typeof $attribute !=='undefined' ) {
                    $attribute = $attribute.split('|');
                    $address = '';
                    if( street_data.number[$attribute[1]]!=='' ) $address += street_data.number[$attribute[1]];
                    if( $address!=='' ) {
                        $address += ' '+street_data.name[$attribute[1]];
                    }else{
                        $address += street_data.name[$attribute[1]];
                    }
                    inputField = SUPER.field(args.form, $attribute[0]);
                    inputField.value = $address;
                    if($address===''){
                        inputField.closest('.super-shortcode').classList.remove('super-filled');
                    }else{
                        inputField.closest('.super-shortcode').classList.add('super-filled');
                    }
                    SUPER.after_dropdown_change_hook({el: inputField}); // @since 3.1.0 - trigger hooks after changing the value
                }
            });
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
            // - Add tab indexes to all fields
            // - Check if RTL support is enabled, if so we must reverse columns order before we add TAB indexes to fields
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
            // - Now we have added the TAB indexes, make sure to reverse the order back to normal in case of RTL support
            if( $this.classList.contains('super-rtl') ) {
                SUPER.reverse_columns($this);
            }
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
        });

    };

    // Remove responsive class from the form
    SUPER.remove_super_form_classes = function($this, $classes){
        $.each($classes, function( k, v ) {
            $this.removeClass(v);
        });
    };

    // Replace HTML element {tags} with field values
    // @since 1.2.7
    SUPER.init_replace_html_tags = function(args){
        var $i,
            $v,
            $row_regex,
            $html_fields,
            $target,
            $html,
            $row_str,
            $original,
            $field_name,
            $field,
            $return,
            $rows,
            $row,
            $found,
            $tag_items,
            $old_name,
            $new_name,
            $regex,
            $array,
            $values,
            $new_value,
            $match;

        // Only when not on canvas in builder mode
        if(args.form.classList.contains('super-preview-elements')){
            return false;
        }

        // Continue otherwise
        if(typeof args.el === 'undefined') {
            $html_fields = args.form.querySelectorAll('.super-html-content, .super-accordion-title, super-accordion-desc');
        }else{
            $html_fields = args.form.querySelectorAll('.super-html-content[data-fields*="{'+SUPER.get_field_name(args.el)+'}"], .super-accordion-title[data-fields*="{'+SUPER.get_field_name(args.el)+'}"], .super-accordion-desc[data-fields*="{'+SUPER.get_field_name(args.el)+'}"]');
        }
        $regex = /{([^"']*?)}/g;
        Object.keys($html_fields).forEach(function(key) {
            var $counter = 0;
            $target = $html_fields[key];
            // @since 4.9.0 - accordion title description {tags} compatibility
            if( $target.classList.contains('super-accordion-title') || $target.classList.contains('super-accordion-desc') ) {
                $html = $target.dataset.original;
            }else{
                $html = $target.parentNode.querySelector('textarea').value;
            }
            // Check if html contains {tags}, if not we don't have to do anything.
            // This also solves bugs with for instance third party plugins
            // That use shortcodes to initialize elements, which initialization would be lost
            // upon updating the HTML content based on {tags}.
            // This can be solved by NOT using either of the {} curly braces inside the HTML content
            $regex = /{([^"']*?)}/g;
            if(!$regex.exec($html)) return true;

            // If it has {tags} then continue
            if( $html!=='' ) {
                // @since 4.6.0 - foreach loop compatibility
                $regex = /foreach\s?\(\s?['|"|\s|]?(.*?)['|"|\s|]?\)\s?:([\s\S]*?)(?:endforeach\s?;)/g;
                while (($v = $regex.exec($html)) !== null) {
                    // This is necessary to avoid infinite loops with zero-width matches
                    if ($v.index === $regex.lastIndex) {
                        $regex.lastIndex++;
                    }
                    $original = $v[0];
                    $field_name = $v[1];
                    $return = '';
                    if(typeof $v[2] !== 'undefined') $return = $v[2];
                    $rows = '';
                    $field = SUPER.field(args.form, $field_name);
                    if($field){
                        // Of course we have at least one row, so always return the first row
                        $row = $return.split('<%counter%>').join(1);
                        $row = $row.split('<%').join('{');
                        $row = $row.split('%>').join('}');
                        $rows += $row;
                        // Loop through all the fields that have been dynamically added by the user
                        $i=2;
                        $found = SUPER.field_exists(args.form, $field_name + '_' + ($i));
                        while($found){
                            $found = SUPER.field_exists(args.form, $field_name + '_' + ($i));
                            if($found){
                                $row = $return.split('<%counter%>').join($i);
                                $row_regex = /<%(.*?)%>/g;
                                $row_str = $return;
                                while (($v = $row_regex.exec($row_str)) !== null) {
                                    // This is necessary to avoid infinite loops with zero-width matches
                                    if ($v.index === $row_regex.lastIndex) {
                                        $row_regex.lastIndex++;
                                    }
                                    $tag_items = $v[1].split(';');
                                    $old_name = $tag_items[0];
                                    if($old_name!=='counter'){
                                        $tag_items[0] = $tag_items[0]+'_'+$i;
                                        $new_name = $tag_items.join(';');
                                        $row = $row.split('<%'+$v[1]+'%>').join('{'+$new_name+'}');
                                    }
                                }
                                $rows += $row;
                            }
                            $i++;
                        }
                    }
                    $html = $html.split($original).join($rows);
                }
                $array = [];
                $regex = /{([^"']*?)}/g;
                while (($match = $regex.exec($html)) !== null) {
                    $array[$counter] = $match[1];
                    $counter++;
                }
                if( $array.length>0 ) {
                    for ($counter = 0; $counter < $array.length; $counter++) {
                        $values = $array[$counter];
                        args.value = '{'+$values+'}'; //values[1];
                        args.target = $target;
                        $new_value = SUPER.update_variable_fields.replace_tags(args);
                        delete args.target;
                        $html = $html.replace('{'+$values+'}', $new_value);
                    }
                }

                // @since 4.6.0 - if statement compatibility
                $html = SUPER.filter_if_statements($html);
                $target.innerHTML = $html;
            }
        });
    };

    // Replace form action attribute {tags} with field values
    // @since 4.4.6
    SUPER.init_replace_post_url_tags = function(args){
        var $match,
            $target = args.form.querySelector('form'),
            $actiontags = ($target ? $target.dataset.actiontags : ''),
            $regex = /{([^"']*?)}/g,
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

        $form.find('.super-dropdown-ui').each(function(){
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
                                $new_placeholder += ','+$(this).html();
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
                    alert('Failed to process data, please try again');
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
                        items = $parent.querySelectorAll('.super-dropdown-ui .super-active');
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
    SUPER.init_clear_form = function(form, clone){
        var field, nodes, innerNodes, el, i, ii,
            children, index, element, dropdown, dropdownItem, 
            option, switchBtn, activeItem,
            value = '',
            default_value,
            main_form = form,
            new_value = '',
            placeholder,
            new_placeholder = '';

        if(typeof clone !== 'undefined') {
            main_form = form;
            form = clone;
            // @since 2.7.0 - reset fields after adding column dynamically
            // Slider field
            nodes = form.querySelectorAll('.super-shortcode.super-slider > .super-field-wrapper > *:not(.super-shortcode-field)');
            for (i = 0; i < nodes.length; i++) { 
                nodes[i].remove();
            }
            // Color picker
            nodes = form.querySelectorAll('.super-color .sp-replacer');
            for (i = 0; i < nodes.length; i++) { 
                nodes[i].remove();
            }
        }

        // @since 3.2.0 - remove the google autocomplete init class from fields
        nodes = form.querySelectorAll('.super-address-autopopulate.super-autopopulate-init');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].classList.remove('super-autopopulate-init');
        }
        // @since 3.5.0 - remove datepicker picker initialized class
        // @since 4.5.0 - remove color picker initialized class
        nodes = form.querySelectorAll('.super-picker-initialized');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].classList.remove('super-picker-initialized');
        }

        // @since 3.6.0 - remove the active class from autosuggest fields
        nodes = form.querySelectorAll('.super-auto-suggest .super-dropdown-ui .super-item.super-active');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].style.display = '';
            nodes[i].classList.remove('super-active');
        }
        nodes = form.querySelectorAll('.super-overlap');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].classList.remove('super-overlap');
        }
        // @since 4.8.20 - reset keyword fields
        nodes = form.querySelectorAll('.super-keyword-tags .super-keyword-filter');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].style.display = 'block';
            nodes[i].value = '';
        }
        nodes = form.querySelectorAll('.super-keyword-tags .super-autosuggest-tags > div > span');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].remove();
        }
        nodes = form.querySelectorAll('.super-keyword-tags .super-autosuggest-tags-list .super-active');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].classList.remove('super-active');
        }
        nodes = form.querySelectorAll('.super-keyword-tags .super-shortcode-field');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].value = '';
        }
        nodes = form.querySelectorAll('.super-keyword-tags');
        for (i = 0; i < nodes.length; i++) {
            field = nodes[i].querySelector('.super-keyword-filter');
            field.placeholder = field.dataset.placeholder;
        }
        // @since 4.8.0 - reset TABs to it's initial state (always first TAB active)
        nodes = form.querySelectorAll('.super-tabs-menu .super-tabs-tab');
        if(nodes){
            if(nodes[0]) nodes[0].click();
        }
        // Remove all dynamic added columns
        nodes = form.querySelectorAll('.super-duplicate-column-fields');
        for (i = 0; i < nodes.length; i++) {
            children = Array.prototype.slice.call( nodes[i].parentNode.children );
            index = children.indexOf(nodes[i]);
            if(index>0) nodes[i].remove();
        }

        // Remove error classes and filled classes
        nodes = form.querySelectorAll('.super-error, .super-error-active, .super-filled');
        for (i = 0; i < nodes.length; i++) { 
            nodes[i].classList.remove('super-error');
            nodes[i].classList.remove('super-error-active');
            nodes[i].classList.remove('super-filled');
        }

        // Clear all fields
        nodes = form.querySelectorAll('.super-shortcode-field');
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

            // If value is not empty, set filled status
            if( default_value !== "" ) {
                field.classList.add('super-filled');
            }else{
                field.classList.remove('super-filled');
            }

            // Checkbox and Radio buttons
            if( field.classList.contains('super-checkbox') || field.classList.contains('super-radio') ){
                innerNodes = form.querySelectorAll('.super-field-wrapper .super-item.super-active');
                for (ii = 0; ii < innerNodes.length; ii++) { 
                    innerNodes[ii].classList.remove('super-active');
                }
                innerNodes = form.querySelectorAll('.super-field-wrapper .super-item input');
                for (ii = 0; ii < innerNodes.length; ii++) { 
                    $(innerNodes[ii]).prop('checked', false);
                }
                innerNodes = form.querySelectorAll('.super-field-wrapper .super-item.super-default-selected');
                for (ii = 0; ii < innerNodes.length; ii++) { 
                    innerNodes[ii].classList.add('super-active');
                }
                innerNodes = form.querySelectorAll('.super-field-wrapper .super-item.super-default-selected input');
                for (ii = 0; ii < innerNodes.length; ii++) { 
                    $(innerNodes[ii]).prop('checked', true);
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
                innerNodes = field.querySelectorAll('.super-dropdown-ui .super-item.super-active');
                for (ii = 0; ii < innerNodes.length; ii++) { 
                    innerNodes[ii].classList.remove('super-active');
                }
                innerNodes = field.querySelectorAll('.super-dropdown-ui .super-item.super-default-selected');
                for (ii = 0; ii < innerNodes.length; ii++) { 
                    innerNodes[ii].classList.add('super-active');
                }
                if(typeof default_value === 'undefined') default_value = '';
                option = field.querySelector('.super-dropdown-ui .super-item:not(.super-placeholder)[data-value="'+default_value+'"]:not(.super-placeholder)');
                if(option){
                    field.querySelector('.super-placeholder').innerHTML = option.innerText;
                    option.classList.add('super-active');
                    element.value = default_value;
                    element.value = '';
                }else{
                    if(field.querySelectorAll('.super-dropdown-ui .super-item.super-active').length===0){
                        if( (typeof element.placeholder !== 'undefined') && (element.placeholder!=='') ) {
                            field.querySelector('.super-placeholder').innerHTML = element.placeholder;
                            dropdownItem = field.querySelector('.super-dropdown-ui .super-item[data-value="'+element.placeholder+'"]');
                            if(dropdownItem) dropdownItem.classList.add('super-active');
                        }else{
                            field.querySelector('.super-placeholder').innerHTML = field.querySelector('.super-dropdown-ui .super-item').innerText;
                        }
                        element.value = '';
                    }else{
                        innerNodes = field.querySelectorAll('.super-dropdown-ui .super-item.super-active');
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
                innerNodes = field.querySelectorAll('.super-dropdown-ui .super-item.super-active');
                for (ii = 0; ii < innerNodes.length; ii++) { 
                    innerNodes[ii].classList.remove('super-active');
                }
                field.querySelector('.super-field-wrapper').classList.remove('super-overlap');
                element.value = '';
                continue;
            }

            // Countries field
            if(field.classList.contains('super-countries')){
                placeholder = element.placeholder;
                dropdown = field.querySelector('.super-dropdown-ui');
                innerNodes = dropdown.querySelectorAll('.super-item.super-active');
                if(typeof placeholder === 'undefined' ) {
                    option = field.querySelector('.super-dropdown-ui .super-item')[2];
                    for (ii = 0; ii < innerNodes.length; ii++) {
                        innerNodes[ii].classList.remove('super-active');
                    }
                    if(dropdown.querySelectorAll('.super-default-selected')){
                        dropdown.querySelectorAll('.super-default-selected').classList.add('super-active');
                    }
                    dropdown.querySelector('.super-placeholder').dataset.value = option.dataset.value;
                    dropdown.querySelector('.super-placeholder').innerHTML = option.innerHTML;
                    element.value = option.dataset.value;
                }else{
                    for (ii = 0; ii < innerNodes.length; ii++) {
                        innerNodes[ii].classList.remove('super-active');
                    }
                    el = dropdown.querySelector('.super-placeholder');
                    el.dataset.value = '';
                    el.innerHTML = placeholder;
                    element.value = '';
                }
                field.classList.remove('super-focus');
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

        // @since 2.9.0 - make sure to do conditional logic and calculations
        // This must not be done when a dynamic column is cloned
        // This would causes issues with variable conditions being executed again and updating fields, resulting
        // them in becoming emptied, instead of preserving their current value.
        // Think about a "customer_search" field that populates other fields based on variable conditions like:
        // {customer_search;2} etc.
        if(typeof clone === 'undefined') {
            SUPER.after_field_change_blur_hook({el: undefined, form: main_form});
        }

        // After form cleared
        SUPER.after_form_cleared_hook(form);
    };


    // Populate form with entry data found after ajax call
    SUPER.populate_form_with_entry_data = function(data, form){
        var i,ii,iii,nodes,items,item,options,wrapper,input,innerNodes,firstValue,dropdown,setFieldValue,itemFirstValue,
            html,files,element,field,stars,currentStar,placeholder,firstField,firstFieldName,
            switchBtn,activeItem,signatureDataUrl,placeholderHtml,fieldName,
            dynamicFields = {},        
            updatedFields = {};        
        
        data = JSON.parse(data);
        if(data!==false && data.length!==0){
        
            // First clear the form
            SUPER.init_clear_form(form);

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

                // Color picker
                if(field.classList.contains('super-color')){
                    if(typeof $.fn.spectrum === "function") {
                        $(field.querySelector('.super-shortcode-field')).spectrum('set', data[i].value);
                    }
                }

                // Signature field (Add-on)
                if(field.classList.contains('super-signature')){
                    if(typeof $.fn.signature === "function") {
                        signatureDataUrl = data[i].value;
                        field.classList.add('super-filled'); // Make sure to be able to delete signature to be able to draw a new one
                        $(field.querySelector('.super-signature-canvas')).signature('draw', signatureDataUrl)
                    }
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
                            html += '<div data-name="'+fv.value+'" class="super-uploaded"';
                            html += ' data-url="'+fv.url+'"';
                            html += ' data-thumburl="'+fv.thumburl+'">';
                            html += '<span class="super-fileupload-name"><a href="'+fv.url+'" target="_blank">'+fv.value+'</a></span>';
                            html += '<span class="super-fileupload-delete"></span>';
                            html += '</div>';
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
                        dropdown = field.querySelector('.super-dropdown-ui');
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
                        nodes = dropdown.querySelectorAll('.super-dropdown-ui .super-item.super-active');
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
                        dropdown = field.querySelector('.super-dropdown-ui');
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
                        nodes = field.querySelectorAll('.super-dropdown-ui .super-item.super-active');
                        for ( ii = 0; ii < nodes.length; ii++){
                            nodes[ii].classList.remove('super-active');
                        }
                        nodes = field.querySelectorAll('.super-dropdown-ui .super-item.super-default-selected');
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

                // Countries field
                if(field.classList.contains('super-countries')){
                    dropdown = field.querySelector('.super-dropdown-ui');
                    items = dropdown.querySelectorAll('.super-item');
                    if(data[i].value!==''){
                        options = data[i].value.split(',');
                        placeholderHtml = '';
                        for( ii = 0; ii < items.length; ii++ ) {
                            items[ii].classList.remove('super-active');
                            if(options.indexOf(items[ii].dataset.value)!==-1){
                                items[ii].classList.add('super-active');
                                if(placeholderHtml===''){
                                    placeholderHtml += items[ii].dataset.value;
                                }else{
                                    placeholderHtml += ', '+items[ii].dataset.value;
                                }
                            }
                        }
                        placeholder = dropdown.querySelector('.super-placeholder');
                        placeholder.dataset.value = '';
                        placeholder.innerHTML = placeholderHtml;
                    }else{
                        placeholder = element.placeholder;
                        if(typeof placeholder === 'undefined' ) {
                            for( ii = 0; ii < items.length; ii++ ) {
                                if(items[ii].classList.contains('super-default-selected')){
                                    items[ii].classList.add('super-active');
                                }else{
                                    items[ii].classList.remove('super-active');
                                }
                            }
                            item = field.querySelectorAll('.super-dropdown-ui .super-item')[1];
                            dropdown.querySelector('.super-placeholder').dataset.value = item.dataset.value;
                            dropdown.querySelector('.super-placeholder').innerHTML = item.innerHTML;
                            element.value = item.dataset.value;
                        }else{
                            for( ii = 0; ii < items.length; ii++ ) {
                                items[ii].classList.remove('super-active');
                            }
                            dropdown.querySelector('.super-placeholder').dataset.value = '';
                            dropdown.querySelector('.super-placeholder').innerHTML = placeholder;
                            element.value = '';
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
            form.classList.add('super-populating');
            $.ajax({
                url: super_common_i18n.ajaxurl,
                type: 'post',
                data: {
                    action: 'super_populate_form_data',
                    order_id: orderId,
                    skip: skipFields
                },
                success: function (data) {
                    SUPER.populate_form_with_entry_data(data, form);
                },
                complete: function(){
                    args.el.querySelector('.super-field-wrapper').classList.remove('super-populating');
                    form.classList.remove('super-populating');
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    // eslint-disable-next-line no-console
                    console.log(xhr, ajaxOptions, thrownError);
                    alert('Failed to process data, please try again');
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
                form.classList.add('super-populating');
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
                        SUPER.populate_form_with_entry_data(data, form);
                    },
                    complete: function(){
                        args.el.closest('.super-field-wrapper').classList.remove('super-populating');
                        form.classList.remove('super-populating');
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        // eslint-disable-next-line no-console
                        console.log(xhr, ajaxOptions, thrownError);
                        alert('Failed to process data, please try again');
                    }
                });
            }
        }
    };

    // init the form on the frontend
    SUPER.init_super_form_frontend = function(){
        
        // Do not do anything if all forms where intialized already
        if(document.querySelectorAll('.super-form').length===document.querySelectorAll('.super-form.super-initialized').length){
            return true;
        }
      
        // @since 4.9.46 - Generate random codes
        $('.super-shortcode-field[data-code="true"]:not(.super-generated)').each(function(){
            var el = this;
            $.ajax({
                url: super_common_i18n.ajaxurl,
                type: 'post',
                data: {
                    action: 'super_update_unique_code',
                    codesettings: el.dataset.codesettings // {"len":"7","char":"1","pre":"","inv":"","invp":"4","suf":"","upper":"true","lower":""}
                },
                success: function (result) {
                    el.value = result;
                    el.classList.add('super-generated');
                    el.removeAttribute("data-codesettings"); 
                    SUPER.after_field_change_blur_hook({el: el});
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    // eslint-disable-next-line no-console
                    console.log(xhr, ajaxOptions, thrownError);
                    alert('Failed to generate unique code');
                }
            });
        });

        // @since 3.3.0 - make sure to load dynamic columns correctly based on found contact entry data when a search field is being used
        $('.super-shortcode-field[data-search="true"]:not(.super-dom-populated)').each(function(){
            var field = this;
            if(field.value!==''){
                field.classList.add('super-dom-populated');
                SUPER.populate_form_data_ajax(field);
            }
        });

        SUPER.init_text_editors();

        // @since 2.3.0 - init file upload fields
        SUPER.init_fileupload_fields();

        //Set dropdown placeholder
        SUPER.init_set_dropdown_placeholder($('.super-form:not(.super-rendered)'));

        // @since 1.1.8     - set radio button to correct value
        $('.super-field.super-radio').each(function(){
            var $this = $(this);
            var $value = $this.find('.super-shortcode-field').val();
            if( typeof $value !== 'undefined' ) {
                $value = $value.split(',');
                $this.find('input[type="radio"]').prop("checked", false);
                $.each($value, function( index, value ) {
                    value = $.trim(value);
                    $this.find('input[type="radio"][value="'+value+'"]').prop("checked", true);
                });
            }
        });

        // @since 1.1.8     - set checkbox to correct value
        $('.super-field.super-checkbox').each(function(){
            var $this = $(this);
            var $value = $this.find('.super-shortcode-field').val();
            if( typeof $value !== 'undefined' ) {
                $value = $value.split(',');
                $this.find('input[type="checkbox"]').prop("checked", false);
                $.each($value, function( index, value ) {
                    value = $.trim(value);
                    $this.find('input[type="checkbox"][value="'+value+'"]').prop("checked", true);
                });
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

        // Multi-part
        $('.super-form').each(function(){
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
                    $submit_button = $form.find('.super-form-button:last');
                    $clone = $submit_button.clone();
                    $($clone).appendTo($form.find('.super-multipart:last'));
                    $button_clone = $submit_button[0].outerHTML;
                    $submit_button.remove();
                    $($button_clone).appendTo($form.find('.super-multipart').not(':last')).removeClass('super-form-button').addClass('super-next-multipart').find('.super-button-name').html(super_common_i18n.directions.next);
                    $($button_clone).appendTo($form.find('.super-multipart').not(':first')).removeClass('super-form-button').addClass('super-prev-multipart').find('.super-button-name').html(super_common_i18n.directions.prev);

                    // Now lets loop through all the multi-parts and set the data such as name and description
                    $form.find('.super-multipart').each(function(){
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
            }
            SUPER.init_super_responsive_form_fields({form: form});
            SUPER.init_common_fields({form: form});
        });

        $(window).resize(function() {
            var i, nodes = document.querySelectorAll('.super-form');
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
    SUPER.reposition_slider_amount_label = function(field, value){
        if(typeof value === 'undefined') value = field.value;
        $(field).simpleSlider("setValue", 0);
        $(field).simpleSlider("setValue", value);
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
                SUPER.reposition_slider_amount_label($field[0]);

                $field.on("slider:changed", function ($event, $data) {
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
            $('.super-tooltip:not(.tooltipstered)').tooltipster({
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
                        palettes: ['#F26C68', '#444444', '#6E7177', '#FFFFFF', '#000000']
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

    // Handle the responsiveness of the form
    SUPER.responsive_form_fields_timeout = {};
    SUPER.init_super_responsive_form_fields = function(args){
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

            // Check for slider fields, reposition "Dragger" element based on "Track" width
            // If the dragger position exceeds the track width adjust it to be 
            // selector.simpleSlider("setValue", value);
            // Sets the value of the slider.
            // selector.simpleSlider("setRatio", ratio);
            var nodes = $this[0].querySelectorAll('.super-slider');
            for(var i=0; i < nodes.length; i++){
                var $field = $(nodes[i].querySelector('.super-shortcode-field'));
                if(!$field) continue;
                // Must trigger a change:
                var originalValue = $field.val();
                if(typeof $field.data("slider-object") === 'undefined'){
                    // Must re-generate slider field, because this is a cloned form
                    if(nodes[i].querySelector('.slider')){
                        nodes[i].querySelector('.slider').remove();
                    }
                    SUPER.init_slider_field();
                }else{
                    SUPER.reposition_slider_amount_label($field[0], originalValue);
                }
            }

            ///var formId = 0;
            ///if(this.querySelector('input[name="hidden_form_id"]')){
            ///    formId = this.querySelector('input[name="hidden_form_id"]').value;
            ///}
            ///// First disable the UI on the map for nicer print of the map
            ///// And make map fullwidth and directions fullwidth
            ///for(i=0; i < SUPER.google_maps_api.allMaps[formId].length; i++){
            ///    nodes = SUPER.google_maps_api.allMaps[formId][i].__gm.Na.parentNode.querySelectorAll(':scope > div');
            ///    for(var x=0; x < nodes.length; x++){
            ///        nodes[x].style.width = '100%';
            ///        if(nodes[x].classList.contains('super-google-map-directions')){
            ///            nodes[x].style.overflowY = 'initial';
            ///            nodes[x].style.height = 'auto';
            ///        }
            ///    }
            ///}

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
            $name = $this.find('.element-field').attr('name');
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
            $parent = $container.find('.element-field[name="'+$this.data('parent')+'"]');
            // If is radio button
            if($parent.attr('type')=='radio'){
                $parent = $container.find('.element-field[name="'+$this.data('parent')+'"]:checked');
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

    // @since 3.2.0 - function to return next field based on TAB index
    SUPER.super_find_next_tab_field = function(field, form, nextTabIndex){
        var nextTabIndexSmallIncrement,
            nextField,
            nextFieldSmallIncrement,
            nextCustomField,
            customTabIndex;

        if(typeof nextTabIndex === 'undefined'){
            nextTabIndexSmallIncrement = parseFloat(parseFloat(field.dataset.superTabIndex)+0.001).toFixed(3);
            nextTabIndex = parseFloat(field.dataset.superTabIndex)+1;
        }
        if(typeof field.dataset.superCustomTabIndex !== 'undefined'){
            nextTabIndex = parseFloat(field.dataset.superCustomTabIndex)+1;
        }

        nextTabIndexSmallIncrement = parseFloat(nextTabIndexSmallIncrement);
        nextTabIndex = parseFloat(parseFloat(nextTabIndex).toFixed(0));
        nextFieldSmallIncrement = form.querySelector('.super-field[data-super-tab-index="'+nextTabIndexSmallIncrement+'"]');
        if(nextFieldSmallIncrement){
            nextField = nextFieldSmallIncrement;
        }else{
            nextField = form.querySelector('.super-field[data-super-tab-index="'+nextTabIndex+'"]');
        }
        nextCustomField = form.querySelector('.super-field[data-super-custom-tab-index="'+nextTabIndex+'"]');

        // If custom index TAB field was found, and is not currently focussed
        if( (nextCustomField) && (!nextCustomField.classList.contains('super-focus')) ) {
            nextField = nextCustomField;
        }
        
        if(nextField.dataset.superCustomTabIndex){
            customTabIndex = nextField.dataset.superCustomTabIndex;
            if(typeof customTabIndex !== 'undefined') {
                if(nextTabIndex < parseFloat(customTabIndex)){
                    nextField = SUPER.super_find_next_tab_field(field, form, nextTabIndex+1);
                }
            }
        }
        if(SUPER.has_hidden_parent(nextField)){
            // Exclude conditionally
            nextField = SUPER.super_find_next_tab_field(field, form, nextTabIndex+1);
        }
        return nextField;
    };
    SUPER.super_focus_next_tab_field = function(e, next, form, skipNext){
        var i,nodes,keyCode;

        if(typeof skipNext !== 'undefined'){
            next = skipNext;
        }else{
            next = SUPER.super_find_next_tab_field(next, form);
        }
        nodes = form.querySelectorAll('.super-focus *');
        for ( i = 0; i < nodes.length; i++){
            nodes[i].blur();
            if(nodes[i].closest('.super-focus')){
                nodes[i].closest('.super-focus').classList.remove('super-focus');
            }
        }
        nodes = form.querySelectorAll('.super-focus-dropdown');
        for ( i = 0; i < nodes.length; i++){
            nodes[i].classList.remove('super-focus-dropdown');
        }
        nodes = form.querySelectorAll('.super-color .super-shortcode-field');
        for ( i = 0; i < nodes.length; i++){
            $(nodes[i]).spectrum("hide");
        }
        if( next.classList.contains('super-form-button') ) {
            next.classList.add('super-focus');
            SUPER.init_button_hover_colors( next );
            next.querySelector('.super-button-wrap').focus();
            e.preventDefault();
            return false;
        }
        if( next.classList.contains('super-next-multipart') ) {
            keyCode = e.keyCode || e.which; 
            // 9 = TAB
            if (keyCode == 9) {
                next.click();
                next.classList.add('super-focus');
                SUPER.super_focus_next_tab_field(e, next, form);
            }
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
        if( (next.classList.contains('super-dropdown')) || (next.classList.contains('super-countries')) ) {
            next.classList.add('super-focus');
            next.classList.add('super-focus-dropdown');
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
        
        function super_update_dropdown_value(e, dropdown, key){
            var i,nodes,value,name,max,min,total,names='',values='',counter,validation,form,
                input = dropdown.querySelector('.super-field-wrapper > input'),
                parent = dropdown.querySelector('.super-dropdown-ui'),
                placeholder = parent.querySelector('.super-placeholder'),
                selected = parent.querySelector('.super-active');

            if(!parent.classList.contains('multiple')){
                if(selected){
                    value = selected.dataset.value;
                    name = selected.dataset.searchValue;
                    placeholder.innerHTML = (name);
                    placeholder.dataset.value = value;
                    placeholder.classList.add('super-active');
                    nodes = parent.querySelectorAll('.super-item.super-active');
                    for (i = 0; i < nodes.length; i++){
                        nodes[i].classList.remove('super-active');
                    }
                    selected.classList.add('super-active');
                    input.value = value;
                }
            }else{
                max = input.dataset.maxlength;
                min = input.dataset.minlength;
                total = parent.querySelectorAll('li.super-active:not(.super-placeholder)').length;
                if(selected.classList.contains('super-active')){
                    if(total>1){
                        if(total <= min) return false;
                        selected.classList.remove('super-active');    
                    }
                }else{
                    if(total >= max) return false;
                    selected.classList.add('super-active');    
                }
                nodes = parent.querySelectorAll('li.super-active:not(.super-placeholder)');
                total = nodes.length;
                counter = 1;
                for (i = 0; i < nodes.length; i++){
                    if((total == counter) || (total==1)){
                        names += nodes[i].dataset.searchValue;
                        values += nodes[i].dataset.value;
                    }else{
                        names += nodes[i].dataset.searchValue+', ';
                        values += nodes[i].dataset.value+', ';
                    }
                    counter++;
                }
                placeholder.innerHTML = names;
                input.value = values;
            }
            validation = input.dataset.validation;
            if(typeof validation !== 'undefined' && validation !== false){
                form = input.closest('.super-form');
                SUPER.handle_validations({el: input, form: form, validation: validation});
            }
            if(key=='enter') {
                dropdown.classList.remove('super-focus-dropdown');
                dropdown.classList.remove('super-string-found');
            }
            SUPER.after_dropdown_change_hook({el: input});
            e.preventDefault();
        }

        $doc.on('click', '.super-field.super-currency',function(){
            var $field = $(this);
            var $form = $field.closest('.super-form');
            $form.find('.super-focus').removeClass('super-focus');
            $form.find('.super-focus-dropdown').removeClass('super-focus-dropdown');
            $field.addClass('super-focus');
        });

        $doc.keydown(function(e){
            var i, nodes, total,
                field,
                form,
                children,
                dropdown,
                dropdown_ui,
                element,
                item,
                current,
                placeholder,
                nextIndex,
                submitButton,
                keyCode = e.keyCode || e.which;

            // 13 = enter
            if (keyCode == 13) {
                dropdown = document.querySelector('.super-focus-dropdown');
                if(dropdown){
                    super_update_dropdown_value(e, dropdown, 'enter');
                }else{
                    element = document.querySelector('.super-focus');
                    if(element){
                        form = element.closest('.super-form');
                        // @since 3.3.0 - Do not submit form if Enter is disabled
                        if(form.dataset.disableEnter=='true'){
                            e.preventDefault();
                            return false;
                        }
                        if( !element.classList.contains('super-textarea') ) {
                            if(!form.querySelector('.super-form-button.super-loading')){
                                submitButton = form.querySelector('.super-form-button .super-button-wrap .super-button-name[data-action="submit"]');
                                if(submitButton) {
                                    var args = {
                                        el: undefined,
                                        form: form,
                                        submitButton: submitButton.parentNode,
                                        validateMultipart: undefined,
                                        event: e,
                                        doingSubmit: true
                                    };
                                    SUPER.validate_form(args);
                                }
                            }
                            e.preventDefault();
                        }
                    }
                }
            }
            // 38 = up arrow
            // 40 = down arrow
            if ( (keyCode == 40) || (keyCode == 38) ) {
                dropdown = document.querySelector('.super-focus-dropdown');
                if(dropdown){
                    total = dropdown.querySelectorAll('.super-dropdown-ui .super-item').length;
                    placeholder = dropdown.querySelector('.super-dropdown-ui .super-placeholder');
                    if(!dropdown.querySelector('.super-dropdown-ui .super-active')){
                        item = dropdown.querySelectorAll('.super-dropdown-ui .super-item')[1];
                        if(keyCode == 38){
                            item = dropdown.querySelectorAll('.super-dropdown-ui .super-item')[total-1];
                        }
                        item.classList.add('super-active');
                        placeholder.dataset.value = item.dataset.value;
                        placeholder.innerHTML = item.innerHTML;
                    }else{
                        current = dropdown.querySelector('.super-dropdown-ui .super-item.super-active');
                        if(current){
                            children = Array.prototype.slice.call( current.parentNode.children );
                            if(keyCode == 38){
                                nextIndex = children.indexOf(current) - 1;
                                if(nextIndex===0) nextIndex = total-1;
                            }else{
                                nextIndex = children.indexOf(current) + 1;
                            }
                            item = dropdown.querySelectorAll('.super-dropdown-ui .super-item')[nextIndex];
                            if(!item){
                                item = dropdown.querySelectorAll('.super-dropdown-ui .super-item')[1];
                            }
                            nodes = dropdown.querySelectorAll('.super-dropdown-ui .super-item.super-active');
                            for (i = 0; i < nodes.length; i++) { 
                                nodes[i].classList.remove('super-active');
                            }
                            placeholder.dataset.value = item.dataset.value
                            placeholder.innerHTML = item.innerHTML;
                            item.classList.add('super-active');
                        }
                    }
                    dropdown_ui = $(dropdown.querySelector('.super-dropdown-ui'));
                    dropdown_ui.scrollTop(dropdown_ui.scrollTop() - dropdown_ui.offset().top + $(item).offset().top - 50); 
                    super_update_dropdown_value(e, dropdown);
                }
            }
            // 37 = left arrow
            // 39 = right arrow
            // TABs left/right navigation through keyboard keys
            if( (keyCode == 37) || (keyCode == 39) ) {
                nodes = document.querySelectorAll('.super-form .super-tabs-contents');
                for (i = 0; i < nodes.length; i++) {
                    field = nodes[i].closest('.super-shortcode');
                    if(!SUPER.has_hidden_parent(field, true)){ // Also include Multi-part for this check
                        // Only if no focussed field is found
                        // First get the currently active TAB
                        var activeTab = field.querySelector('.super-tabs-contents > .super-tabs-content.super-active');
                        var focusFound = activeTab.querySelectorAll('.super-focus').length;
                        if(focusFound) continue; // Do not go to next/prev TAB if there is a focussed field
                        // Only if not inside other TAB element
                        if(!field.closest('.super-tabs-contents')){
                            // Go left
                            if( keyCode == 37 ) nodes[i].querySelector(':scope > .super-content-prev').click();
                            // Go right
                            if( keyCode == 39 ) nodes[i].querySelector(':scope > .super-content-next').click();
                        }
                    }
                }
            }
            // 9 = TAB
            if (keyCode == 9) {
                // Only possible to switch to next field if a field is already focussed
                field = document.querySelector('.super-field.super-focus');
                if( field ) {
                    form = field.closest('.super-form');
                    SUPER.super_focus_next_tab_field(e, field, form);
                }     
            }
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
        $doc.on('keyup', '.super-text .super-shortcode-field[data-search="true"]', function(){ 
            var field = this;
            if (timeout !== null) clearTimeout(timeout);
            timeout = setTimeout(function () {
                SUPER.populate_form_data_ajax(field);
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
                var $form = $this.closest('.super-form');
                if( $value.length>0 ) {
                    $this.parents('.super-field-wrapper:eq(0)').addClass('super-populating');
                    $form.addClass('super-populating');
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
                            var ul = $this.parents('.super-field-wrapper:eq(0)').children('.super-dropdown-ui');
                            if(ul.length){
                                ul.html(result);
                            }else{
                                $('<ul class="super-dropdown-ui">'+result+'</ul>').appendTo($this.parents('.super-field-wrapper:eq(0)'));
                            }
                        },
                        complete: function(){
                            $this.parents('.super-field-wrapper:eq(0)').removeClass('super-populating');
                            $form.removeClass('super-populating');
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            // eslint-disable-next-line no-console
                            console.log(xhr, ajaxOptions, thrownError);
                            alert('Failed to process data, please try again');
                        }
                    });
                }
            }, 1000);
        });

        SUPER.init_common_fields();
    });

})(jQuery);
