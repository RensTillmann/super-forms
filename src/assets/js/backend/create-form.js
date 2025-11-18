/* globals jQuery, SUPER, super_create_form_i18n, ajaxurl, tinymce */
"use strict";
(function ($) { // Hide scope, no $ conflict

    function checkNewerForVersion(){
        // Check if this form was edited somewhere else, and the current version is outdated
        // Notify the user so their changes won't be lost
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            if (this.readyState == 4) {
                if (this.status == 200) {
                    // Success:
                    if(this.responseText==='true'){
                        var reload = confirm(super_create_form_i18n.new_version_found);
                        if(reload === true){
                            window.location = window.location.href
                        }else{
                            SUPER.alertWhenSaving = true;
                        }
                    }else{
                        // Already latest version, do nothing
                        clearTimeout(SUPER.new_version_check);
                        SUPER.new_version_check = setTimeout(function(){
                            checkNewerForVersion();
                        }, 30000);
                    }
                }
                // Complete:
            }
        };
        xhttp.onerror = function () {
            console.log(this);
            console.log("** An error occurred during the transaction");
        };
        xhttp.open("POST", ajaxurl, true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
        var params = {
            action: 'super_new_version_check',
            form_id: $('.super-create-form input[name="form_id"]').val(),
            modifiedTime: $('.super-create-form .super-header .super-get-form-shortcodes')[0].dataset.modifiedTime
        };
        params = $.param(params);
        xhttp.send(params);
    }
    SUPER.new_version_check = setTimeout(function(){
        checkNewerForVersion();
    }, 30000);

    function isEmpty(obj) {
        for (var prop in obj) {
            if (Object.prototype.hasOwnProperty.call(obj, prop)) {
                return false;
            }
        }
        return JSON.stringify(obj) === JSON.stringify({});
    }
    SUPER.ui = {
        settings: {},
        tmpSettings: {}, // used for translation mode only
        colorpickers: {
            init: function(){
                if($.isFunction($.fn.wpColorPicker)){
                    var i, nodes = document.querySelectorAll('.sfui-colorpicker > input');
                    for(i=0; i<nodes.length; i++){
                        if(!nodes[i].closest('.wp-picker-container')){
                            $(nodes[i]).wpColorPicker({
                                change: function(event, ui){
                                    this.value = ui.color.toString();
                                    // Debounce updateSettings for this element
                                    if (this._colorUpdateTimeout) clearTimeout(this._colorUpdateTimeout);
                                    this._colorUpdateTimeout = setTimeout(() => {
                                        SUPER.ui.updateSettings(null, this);
                                    }, 500);
                                },
                                palettes: [
                                    // Neutrals
                                    '#FFFFFF', '#000000', '#444444', '#8E8E8E', '#9A9A9A', '#CDCDCD', '#F5F5F5', '#E0E0E0',
                                    // Blues
                                    '#1976D2', // Material Blue 700
                                    '#2196F3', // Material Blue 500
                                    '#49B4B6', // your existing teal
                                    '#00BCD4', // Cyan
                                    // Greens
                                    '#388E3C', // Material Green 700
                                    '#4CAF50', // Material Green 500
                                    '#8BC34A', // Light Green
                                    // Yellows
                                    '#FFEB3B', // Yellow
                                    '#FFC107', // Amber
                                    // Oranges
                                    '#FF9800', // Orange
                                    '#FF5722', // Deep Orange
                                    // Reds
                                    '#F44336', // Red
                                    '#E91E63', // Pink
                                    '#F26C68', // your existing red-orange
                                    // Purples
                                    '#9C27B0', // Purple
                                    '#673AB7', // Deep Purple
                                    // Misc
                                    '#6E7177', // your gray blue
                                    '#607D8B', // Blue Gray

                                    // -- Added colors below --
                                    '#795548', // Brown (Material Brown 500)
                                    '#00E676', // Bright Green / Accent Green
                                    '#FFA07A', // Light Salmon (Nice soft pastel)
                                    '#FFD700'  // Gold (Classic gold for highlights)
                                ]
                            });
                        }
                    }
                    document.querySelectorAll('.sfui-colorpicker-wrap').forEach(function(node) {
                        var container = node.querySelector('.wp-picker-container');
                        if(!container) return;
                        var observer = new MutationObserver(function() {
                            if(container.classList.contains('wp-picker-active')){
                                node.classList.add('sfui-colorpicker-open');
                            }else{
                                node.classList.remove('sfui-colorpicker-open');
                            }
                        });
                        observer.observe(container, {attributes: true, attributeFilter: ['class']});
                    });
                }
            }
        },
        getKeyPath: function(field, tab) {
            var keyPath = [];
            var parent = field.parentElement;
            var i18nField = null;
            while (parent && parent !== tab) {
                if (parent.classList.contains('sfui-repeater-item')) {
                    var repeater = parent.parentElement;
                    var index = Array.from(repeater.children).indexOf(parent);
                    keyPath.unshift(index);
                    if (!i18nField) {
                        i18nField = parent.querySelector('textarea[name="i18n"], input[name="i18n"]');
                    }
                } else if (parent.hasAttribute('data-g')) {
                    keyPath.unshift(parent.getAttribute('data-g'));
                } else if (parent.hasAttribute('data-r')) {
                    keyPath.unshift(parent.getAttribute('data-r') + '[]');
                }
                parent = parent.parentElement;
            }

            if(!i18nField){
                i18nField = tab.querySelector('textarea[name="i18n"], input[name="i18n"]');
            }
            return {
                keyPath: keyPath,
                i18nField: i18nField
            };
        },
        setTabFieldValue: function(field, value){
            if(field.tagName === 'TEXTAREA' && tinymce.get(field.id)){
                tinymce.get(field.id).setContent(value);
            } else {
                if(field.type === 'checkbox'){
                    field.checked = (value === 'true');
                }else{
                    if (field.value !== value) {
                        // Re-load attachment image preview
                        if(field.parentNode.closest('.sfui-setting').classList.contains('sfui-type-files')) {
                            field.value = value;
                            SUPER.ui.i18n.reload_attachments(field);
                        }
                        if(field.parentNode.closest('.sfui-colorpicker')){
                            $(field).wpColorPicker('color', value);
                        }
                    }
                    field.value = value;
                }
            }
        },
        getTabFieldValue: function(el,_tab){
            var value = el.value;
            if(!el.type) alert('not a field, might want to fix/check the create-form.js code...');
            if(value === true) return 'true';
            if(value === false) return 'false';
            if(el.type === 'checkbox') return el.checked ? 'true' : 'false';
            if(el.tagName === 'TEXTAREA' && tinymce.get(el.id)){
                console.log('=== TinyMCE getValue() ===');
                console.log('Element ID:', el.id);
                console.log('Element name:', el.name);
                value = tinymce.get(el.id).getContent();
                console.log('TinyMCE content:', value);
            }
            return value;
        },
        setRepeaterFieldValues: function(element, values){
            for (var fieldName in values) {
                if (Object.prototype.hasOwnProperty.call(values, fieldName)) {
                    var fieldValue = values[fieldName];
                    if (typeof fieldValue === 'object' && fieldValue !== null) {
                        // Handle nested objects
                        var nestedElement = element.querySelector(`[data-g="${fieldName}"]`);
                        if (nestedElement) {
                            SUPER.ui.setRepeaterFieldValues(nestedElement, fieldValue);
                        } else {
                            console.log(`Nested element not found for field: ${fieldName}`);
                        }
                    } else {
                        // Handle simple fields
                        var field = element.querySelector(`[name="${fieldName}"]`);
                        if (field) {
                            console.log(`Setting value for field: ${fieldName} to ${fieldValue}`);
                            field.value = fieldValue;
                        } else {
                            console.log(`Field not found: ${fieldName}`);
                        }
                    }
                }
            }
        },
        btn: function(e, el, action, skipAlert){
            if(typeof skipAlert==='undefined') skipAlert = false;
            var node;
            if (action === 'toggleConditionSettings') {
                node = el.closest('.sfui-repeater-item').querySelector('.sfui-conditional-logic-settings');
                if (node.classList.contains('sfui-active')) {
                    node.classList.remove('sfui-active');
                } else {
                    node.classList.add('sfui-active');
                }
                e.preventDefault();
                return false;
            }
            if (action === 'toggleRepeaterSettings') {
                node = el.closest('.sfui-repeater-item').querySelector('.sfui-setting-group');
                if (node.classList.contains('sfui-active')) {
                    node.classList.remove('sfui-active');
                } else {
                    node.classList.add('sfui-active');
                }
                e.preventDefault();
                return false;
            }
            if (action === 'addRepeaterItem') {
                if(!skipAlert && !SUPER.ui.i18n.addDeleteAllowed()){
                    alert(super_create_form_i18n.alert_add_delete_not_allowed);
                    return;
                }
                var clone = el.closest('.sfui-repeater-item').cloneNode(true);
                var p = el.closest('.sfui-repeater');
                p.appendChild(clone);
                if (p.dataset.r === 'lists') {
                    var input = clone.querySelector('input[value*="[super_listings"]');
                    if (input) {
                        var id = SUPER.generate_new_field_name().replace('field_', '');
                        var form_id = document.querySelector('.super-header input[name="form_id"]').value;
                        input.value = '[super_listings list="' + id + '" id="' + (form_id) + '"]';
                        // Set unique list ID
                        input = clone.querySelector('input[name="id"]');
                        input.value = id;
                    }
                    input = clone.querySelector('input[name="name"]');
                    input.value = 'Listing #' + p.children.length;
                }
                var i, tinymce_editors = clone.querySelectorAll('.sfui-textarea-tinymce');
                for (i = 0; i < tinymce_editors.length; i++) {
                    tinymce_editors[i].id = '';
                    tinymce_editors[i].style.display = '';
                    if (tinymce_editors[i].nextElementSibling) {
                        tinymce_editors[i].nextElementSibling.remove();
                    }
                    // Initialize TinyMCE for the cloned wrapper
                    SUPER.initTinyMCE('.sfui-textarea-tinymce');
                }
                SUPER.ui.showHideSubsettings(clone);
                e.preventDefault();
                return false;
            }
            if (action === 'deleteRepeaterItem') {
                if(!skipAlert && !SUPER.ui.i18n.addDeleteAllowed()){
                    alert(super_create_form_i18n.alert_add_delete_not_allowed);
                    return;
                }
                // Do not delete last item
                var repeater = el.closest('.sfui-repeater');
                var repeaterItems = repeater.querySelectorAll(':scope > .sfui-repeater-item');
                if(repeaterItems.length > 1){
                    var itemToDelete = el.closest('.sfui-repeater-item');
                    if (repeater.closest('.super-tab-content') && document.querySelector('.super-create-form').dataset.i18n) {
                        // In translation mode
                        var i18n = document.querySelector('.super-create-form').dataset.i18n;
                        var tabParent = repeater.closest('[data-g="data"]') ? repeater.closest('[data-g="data"]') : repeater.closest('.super-tab-content');
                        var i18nInputField = tabParent.querySelector('[name="i18n"]');
                        var i18nValue = i18nInputField.value.trim();
                        var i18nData = i18nValue ? JSON.parse(i18nValue) : {};

                        var repeaterKey = repeater.getAttribute('data-r');
                        var deleteIndex = Array.prototype.indexOf.call(repeaterItems, itemToDelete);

                        if (i18nData[i18n] && i18nData[i18n][repeaterKey]) {
                            i18nData[i18n][repeaterKey].splice(deleteIndex, 1); // Remove the corresponding i18n data
                            if (i18nData[i18n][repeaterKey].length === 0) {
                                delete i18nData[i18n][repeaterKey]; // Remove the key if the array is empty
                            }
                            i18nInputField.value = JSON.stringify(i18nData, undefined, 4);
                        }
                    }else{
                        // Not in translation mode, make sure to delete the corresponding index from the translated version for this repeater
                        var tab = itemToDelete.closest('.super-tab-content');
                        var _slug = tab.className.replace('super-active', '').replace('super-tab-content', '').replace('super-tab-', '').split(' ').join('');
                        var keyPath = [];
                        var parent = itemToDelete;
                        // Traverse up to build the key path
                        while (parent && parent !== tab) {
                            if(parent.classList.contains('sfui-repeater-item')){
                                var parentRepeater = parent.parentElement;
                                var itemIndex = Array.from(parentRepeater.children).indexOf(parent);
                                keyPath.unshift(itemIndex);
                            } else if (parent.hasAttribute('data-g')) {
                                keyPath.unshift(parent.getAttribute('data-g'));
                            } else if (parent.hasAttribute('data-r')) {
                                // Find the index of the current repeater item
                                keyPath.unshift(parent.getAttribute('data-r')+'[]');
                            }
                            parent = parent.parentElement;
                        }
                        console.log(keyPath);
                        var _elseTabParent = repeater.closest('[data-g="data"]') ? repeater.closest('[data-g="data"]') : repeater.closest('.super-tab-content');
                        var _elseI18nInputField = _elseTabParent.querySelector('[name="i18n"]');
                        var _elseI18nValue = _elseI18nInputField.value.trim();
                        var _elseI18nData = _elseI18nValue ? JSON.parse(_elseI18nValue) : {};

                        var _elseRepeaterKey = repeater.getAttribute('data-r');
                        var _elseDeleteIndex = Array.prototype.indexOf.call(repeaterItems, itemToDelete);

                        Object.keys(_elseI18nData).forEach(function(langKey){
                            if(_elseI18nData[langKey]){
                                //if (_elseI18nData[langKey] && _elseI18nData[langKey][_elseRepeaterKey]) {
                                //    _elseI18nData[langKey][_elseRepeaterKey].splice(_elseDeleteIndex, 1); // Remove the corresponding i18n data
                                //    if (_elseI18nData[langKey][_elseRepeaterKey].length === 0) {
                                //        delete _elseI18nData[langKey][_elseRepeaterKey]; // Remove the key if the array is empty
                                //    }
                                //    _elseI18nInputField.value = JSON.stringify(_elseI18nData, undefined, 4);
                                //}
                            }

                        });

                    }
                    itemToDelete.remove();
                }
                e.preventDefault();
                return false;
            }
        },
        toggle: function(e, el){
            if(el.parentNode.classList.contains('sfui-open')){
                el.parentNode.classList.remove('sfui-open');
            }else{
                el.parentNode.classList.add('sfui-open');
            }
        },
        // Init `code` auto fill values
        init: function(){
            this.colorpickers.init();
            $('.sfui-setting').on('click', '.sfui-title code, .sfui-label code, .sfui-subline code', function(){
                if(this.closest('label')){
                    var input = this.closest('label').querySelector('input');
                    if(input){
                        input.value = this.innerText;
                        setTimeout(function(){
                            input.blur();
                            SUPER.ui.updateSettings(null, input)
                        },0);
                    }
                }
            });
        },
        // Show/Hide sub settings
        // Supports both legacy string format and new array-based conditional logic
        // Legacy: data-f="field_name;value" 
        // New: data-f='[{"field":"field_name","operator":"=","value":"expected_value"}]'
        // Operators: =, !=, ??, !??, !
        // Examples:
        // - Show when field equals value: [{"field":"my_field","operator":"=","value":"enabled"}]
        // - Show when field does NOT equal value: [{"field":"my_field","operator":"!=","value":"disabled"}]
        // - Show when field contains text: [{"field":"my_field","operator":"??","value":"search_text"}]
        // - Show when field does NOT contain text: [{"field":"my_field","operator":"!??","value":"unwanted"}]
        // - Show when field is not empty: [{"field":"my_field","operator":"!"}]
        // - Multiple conditions (AND logic): [{"field":"field1","operator":"=","value":"yes"},{"field":"field2","operator":"!=","value":"no"}]
        showHideSubsettings: function(el){
            SUPER.filtering.showHideSubsettings(el, 'form-builder');
        },
        // Update form settings
        updateSettings: function(e, el, isProgrammatic){
            if(el.tagName=='LABEL' && el.children[0].type=='radio'){
                el = el.querySelector('input');
            }
            if(el.tagName=='LABEL' && el.children[0].type=='checkbox'){
                el = el.querySelector('input');
            }
            
            // Skip filtering for programmatic changes that don't affect visibility
            if (!isProgrammatic) {
                SUPER.ui.showHideSubsettings(el);
            }

            var i18n = document.querySelector('.super-create-form').dataset.i18n; 
            var tab = el.closest('.super-tab-content');
            var slug = tab.className.replace('super-active', '').replace('super-tab-content', '').replace('super-tab-', '').split(' ').join('');
            // Check how many translatable fields there are
            var field = el;
            var fieldName = field.name || field.getAttribute('name') || 'unknown';
            var translatable = field.nextElementSibling && field.nextElementSibling.className === 'sfui-original-i18n-value';
            if(i18n && i18n !== '' && translatable){
                var { keyPath, i18nField } = SUPER.ui.getKeyPath(field, tab);
                console.log('>>> keyPath:', keyPath);

                // Get keyPath of i18nField itself
                var i18nKeyPath = SUPER.ui.getKeyPath(i18nField, tab).keyPath;
                console.log('>>> i18nKeyPath:', i18nKeyPath);

                // Remove shared leading segments safely
                let sharedPrefixLength = 0;
                for (let i = 0; i < Math.min(i18nKeyPath.length, keyPath.length); i++) {
                    if (i18nKeyPath[i] === keyPath[i]) {
                        sharedPrefixLength++;
                    } else {
                        break;
                    }
                }
                const strippedKeyPath = keyPath.slice(sharedPrefixLength);
                console.log('✅ strippedKeyPath:', strippedKeyPath);

                // Get existing JSON from i18nField or start fresh
                let translationData = {};
                try {
                    translationData = i18nField.value ? JSON.parse(i18nField.value) : {};
                } catch (_e) {
                    console.warn('⚠ Invalid JSON in i18n field. Starting fresh.');
                }

                // Get field values
                const fieldValue = SUPER.ui.getTabFieldValue(field, tab);
                const originalValueElement = field.parentNode.querySelector('.sfui-original-i18n-value');
                const originalValue = originalValueElement ? (originalValueElement.value || originalValueElement.textContent || '') : '';

                // Set translated value into nested structure
                function setNestedI18nValue(obj, path, fieldName, lang, fieldValue, originalValue) {
                    let current = obj;
                    const stack = [];

                    for (let i = 0; i < path.length; i++) {
                        const rawKey = path[i];
                        const key = String(rawKey);
                        const isArrayIndex = /^\d+$/.test(key);
                        const isArrayKey = key.includes('[]');

                        if (isArrayKey) {
                            const arrayKey = key.replace('[]', '');
                            if (!current[arrayKey]) current[arrayKey] = [];
                            stack.push({ parent: current, key: arrayKey });
                            current = current[arrayKey];
                        } else if (isArrayIndex) {
                            const index = parseInt(key);
                            while (current.length <= index) current.push({});
                            stack.push({ parent: current, key: index });
                            current = current[index];
                        } else {
                            if (!current[key]) current[key] = {};
                            stack.push({ parent: current, key: key });
                            current = current[key];
                        }
                    }

                    if (!current[fieldName]) current[fieldName] = {};

                    if (fieldValue === originalValue) {
                        console.log(`🧹 Removing '${fieldName}' translation for lang '${lang}' because it's identical to original`);
                        delete current[fieldName][lang];

                        if (Object.keys(current[fieldName]).length === 0) {
                            delete current[fieldName];
                        }

                        for (let i = stack.length - 1; i >= 0; i--) {
                            const { parent, key } = stack[i];
                            if (Array.isArray(parent)) {
                                if (Object.keys(parent[key]).length === 0) {
                                    parent[key] = {};
                                } else {
                                    break;
                                }
                            } else if (typeof parent[key] === 'object' && Object.keys(parent[key]).length === 0) {
                                delete parent[key];
                            } else {
                                break;
                            }
                        }
                    } else {
                        current[fieldName][lang] = fieldValue;
                    }
                }

                setNestedI18nValue(
                    translationData,
                    strippedKeyPath,
                    fieldName,
                    i18n,
                    fieldValue,
                    originalValue
                );

                i18nField.value = JSON.stringify(translationData, null, 2);
                console.log('✅ Updated i18nField:', i18nField.value);

                // Also store updated i18n data in SUPER.ui.settings
                const target = SUPER.ui.settings['_' + slug] = SUPER.ui.settings['_' + slug] || {};
                const i18nPath = i18nKeyPath.slice(); // Copy i18nKeyPath so we can traverse it
                let current = target;

                // Traverse and create structure
                for (let i = 0; i < i18nPath.length; i++) {
                    const rawKey = i18nPath[i];
                    const key = String(rawKey);
                    const isArrayIndex = /^\d+$/.test(key);
                    const isArrayKey = key.includes('[]');

                    if (isArrayKey) {
                        const arrayKey = key.replace('[]', '');
                        current[arrayKey] = current[arrayKey] || [];
                        current = current[arrayKey];
                    } else if (isArrayIndex) {
                        const index = parseInt(key);
                        while (current.length <= index) current.push({});
                        current = current[index];
                    } else {
                        current[key] = current[key] || {};
                        current = current[key];
                    }
                }

                // Assign the parsed JSON value to the final i18n object
                try {
                    current['i18n'] = JSON.parse(i18nField.value);
                    console.log('✅ Assigned i18n into SUPER.ui.settings:', current['i18n']);
                } catch (_e) {
                    console.warn('⚠ Failed to parse i18nField.value:', i18nField.value);
                }

            }
            // First get the field value
            var value = el.value;
            var type = el.type;
            if (type === 'checkbox') value = el.checked;
            if (type === 'radio') value = (tab.querySelector('[name="' + el.name + '"]:checked') ? tab.querySelector('[name="' + el.name + '"]:checked').value : '');
            if (value === true) value = "true";
            if (value === false) value = "false";
            if (el.tagName === 'TEXTAREA' && tinymce.get(el.id)) {
                value = tinymce.get(el.id).getContent();
            }
            if(!i18n || i18n===''){
                // On main language, update normal settings
                // Guard against Email v2 tab which doesn't have settings in the same format
                if (slug === 'email-v2') {
                    console.log('Skipping updateSettings for Email v2 tab');
                    return;
                }
                // Initialize settings object if it doesn't exist
                if (!SUPER.ui.settings['_'+slug]) {
                    SUPER.ui.settings['_'+slug] = {};
                }
                SUPER.ui.settings['_'+slug] = SUPER.ui.i18n.collectDataFromParents(SUPER.ui.settings['_'+slug], el, value, slug, tab);
            }
        },

            // tmp if(i18n && i18n !== '' && translatable){
            // tmp     // Check if value has changed by comparing to original value
            // tmp     var originalValueElement = field.parentNode.querySelector('.sfui-original-i18n-value');
            // tmp     var originalValue = originalValueElement ? (originalValueElement.value || originalValueElement.textContent || '') : '';
            // tmp     // Skip if the value hasn't changed from original
            // tmp     if (fieldValue === originalValue) {
            // tmp         console.log('Field value unchanged, skipping:', fieldName);
            // tmp         return; // Don't add to i18n JSON if value is identical to original
            // tmp     }

            // tmp     var { keyPath, i18nField } = SUPER.ui.getKeyPath(field, tab);
            // tmp     console.log('>>> keyPath:', keyPath);

            // tmp     // Get keyPath of i18nField itself
            // tmp     var i18nKeyPath = SUPER.ui.getKeyPath(i18nField, tab).keyPath;
            // tmp     console.log('>>> i18nKeyPath:', i18nKeyPath);

            // tmp     // Remove shared leading segments safely (without mutating during iteration)
            // tmp     let sharedPrefixLength = 0;
            // tmp     for (let i = 0; i < Math.min(i18nKeyPath.length, keyPath.length); i++) {
            // tmp         if (i18nKeyPath[i] === keyPath[i]) {
            // tmp             sharedPrefixLength++;
            // tmp         } else {
            // tmp             break;
            // tmp         }
            // tmp     }
            // tmp     const strippedKeyPath = keyPath.slice(sharedPrefixLength);
            // tmp     console.log('✅ strippedKeyPath:', strippedKeyPath);
            // tmp     // Get existing JSON from i18nField or start fresh
            // tmp     let translationData = {};
            // tmp     try {
            // tmp         translationData = i18nField.value ? JSON.parse(i18nField.value) : {};
            // tmp     } catch (e) {
            // tmp         console.warn('⚠ Invalid JSON in i18n field. Starting fresh.');
            // tmp     }

            // tmp     // Set translated value into nested structure
            // tmp     function setNestedI18nValue(obj, path, fieldName, lang, value) {
            // tmp         let current = obj;
            // tmp         for (let i = 0; i < path.length; i++) {
            // tmp             const rawKey = path[i];
            // tmp             const key = String(rawKey); // ✅ Ensure we always work with string

            // tmp             const isArrayIndex = /^\d+$/.test(key);
            // tmp             const isArrayKey = key.includes('[]');

            // tmp             if (isArrayKey) {
            // tmp                 const arrayKey = key.replace('[]', '');
            // tmp                 if (!current[arrayKey]) current[arrayKey] = [];
            // tmp                 current = current[arrayKey];
            // tmp             } else if (isArrayIndex) {
            // tmp                 const index = parseInt(key);
            // tmp                 while (current.length <= index) current.push({});
            // tmp                 current = current[index];
            // tmp             } else {
            // tmp                 if (!current[key]) current[key] = {};
            // tmp                 current = current[key];
            // tmp             }
            // tmp         }

            // tmp         if (!current[fieldName]) current[fieldName] = {};
            // tmp         current[fieldName][lang] = value;
            // tmp     }

            // tmp     setNestedI18nValue(translationData, strippedKeyPath, fieldName, i18n, SUPER.ui.getTabFieldValue(field, tab));

            // tmp     // Store back
            // tmp     i18nField.value = JSON.stringify(translationData, null, 2);
            // tmp     console.log('✅ Updated i18nField:', i18nField.value);
            // tmp }

            // if(i18n && i18n!=='' && translatable){
            //     console.log('fieldName:', fieldName);
            //     var { keyPath, i18nField } = SUPER.ui.getKeyPath(field, tab);
            //     console.log('>>> keyPath for field:', fieldName, keyPath);
            //     console.log('>>> i18nField:', i18nField);

            //     // tmp translatableFields.forEach(field => {
            //     // tmp     var keyPath = SUPER.ui.getKeyPath(field, tab);
            //     // tmp     if(keyPath[0] === 'triggers[]'){
            //     // tmp         var tmpKey = keyPath[0].replace('[]','');
            //     // tmp         var dataRParent = field.closest('[data-r="' + tmpKey + '"]');
            //     // tmp         
            //     // tmp         // Fastest way: Find topmost repeater item within the data-r boundary
            //     // tmp         var topmostRepeaterItem = null;
            //     // tmp         var currentElement = field;
            //     // tmp         
            //     // tmp         while (currentElement && currentElement !== dataRParent.parentElement) {
            //     // tmp             if (currentElement.classList?.contains('sfui-repeater-item')) {
            //     // tmp                 topmostRepeaterItem = currentElement;
            //     // tmp             }
            //     // tmp             currentElement = currentElement.parentElement;
            //     // tmp             
            //     // tmp             // Stop if we've reached the data-r container
            //     // tmp             if (currentElement === dataRParent) break;
            //     // tmp         }
            //     // tmp         
            //     // tmp         // Find the first input/textarea named 'i18n' within the topmost repeater item
            //     // tmp         var i18nField = topmostRepeaterItem?.querySelector('input[name="i18n"], textarea[name="i18n"]');
            //     // tmp         
            //     // tmp         if (i18nField && topmostRepeaterItem) {
            //     // tmp             // Get existing translations from the i18n field (if any)
            //     // tmp             var existingTranslations = {};
            //     // tmp             try {
            //     // tmp                 existingTranslations = i18nField.value ? JSON.parse(i18nField.value) : {};
            //     // tmp             } catch (e) {
            //     // tmp                 existingTranslations = {};
            //     // tmp             }
            //     // tmp             
            //     // tmp             // Get the current field's translation value and field name using existing function
            //     // tmp             var fieldValue = SUPER.ui.getTabFieldValue(field, tab);
            //     // tmp             var fieldName = field.name || field.getAttribute('name') || 'unknown';
            //     // tmp             
            //     // tmp             // Check if value has changed by comparing to original value
            //     // tmp             var originalValueElement = field.parentNode.querySelector('.sfui-original-i18n-value');
            //     // tmp             var originalValue = originalValueElement ? (originalValueElement.value || originalValueElement.textContent || '') : '';
            //     // tmp             
            //     // tmp             // Skip if the value hasn't changed from original
            //     // tmp             if (fieldValue === originalValue) {
            //     // tmp                 console.log('Field value unchanged, skipping:', fieldName);
            //     // tmp                 return; // Don't add to i18n JSON if value is identical to original
            //     // tmp             }
            //     // tmp             
            //     // tmp             // Convert keyPath to nested object structure
            //     // tmp             function setNestedValue(obj, keyPath, fieldName, value) {
            //     // tmp                 var current = obj;
            //     // tmp                 
            //     // tmp                 for (var i = 0; i < keyPath.length; i++) {
            //     // tmp                     var key = String(keyPath[i]); // Convert to string to ensure .includes() works
            //     // tmp                     var isArrayKey = key.includes('[]');
            //     // tmp                     var isIndexKey = /^\d+$/.test(key);
            //     // tmp                     
            //     // tmp                     if (isArrayKey) {
            //     // tmp                         // Handle array notation like "triggers[]"
            //     // tmp                         var arrayKey = key.replace('[]', '');
            //     // tmp                         if (!current[arrayKey]) current[arrayKey] = [];
            //     // tmp                         current = current[arrayKey];
            //     // tmp                     } else if (isIndexKey) {
            //     // tmp                         // Handle array index like "0"
            //     // tmp                         var index = parseInt(key);
            //     // tmp                         while (current.length <= index) {
            //     // tmp                             current.push({});
            //     // tmp                         }
            //     // tmp                         current = current[index];
            //     // tmp                     } else {
            //     // tmp                         // Handle regular object key
            //     // tmp                         if (!current[key]) current[key] = {};
            //     // tmp                         current = current[key];
            //     // tmp                     }
            //     // tmp                 }
            //     // tmp                 
            //     // tmp                 // Set the final field name as the key with the value
            //     // tmp                 current[fieldName] = value;
            //     // tmp             }
            //     // tmp             
            //     // tmp             // Use the full keyPath array and set the nested value with field name
            //     // tmp             setNestedValue(existingTranslations, keyPath, fieldName, fieldValue);
            //     // tmp             
            //     // tmp             // Update the i18n field with the pretty-formatted nested JSON
            //     // tmp             i18nField.value = JSON.stringify(existingTranslations, null, 2);
            //     // tmp             
            //     // tmp             // Update SUPER.ui.settings to reflect the i18n textarea content
            //     // tmp             // Get the keyPath for the i18n field itself to know where to store it in settings
            //     // tmp             var i18nKeyPath = SUPER.ui.getKeyPath(i18nField, tab);
            //     // tmp             
            //     // tmp             // Update the global settings at the correct location
            //     // tmp             function updateSettingsAtPath(settingsObj, keyPath, value, currentLang) {
            //     // tmp                 var current = settingsObj;
            //     // tmp                 
            //     // tmp                 // Navigate through the keyPath structure
            //     // tmp                 for (var i = 0; i < keyPath.length; i++) {
            //     // tmp                     var key = String(keyPath[i]);
            //     // tmp                     var isArrayKey = key.includes('[]');
            //     // tmp                     var isIndexKey = /^\d+$/.test(key);
            //     // tmp                     var isLastKey = (i === keyPath.length - 1);
            //     // tmp                     
            //     // tmp                     if (isArrayKey) {
            //     // tmp                         var arrayKey = key.replace('[]', '');
            //     // tmp                         if (!current[arrayKey]) current[arrayKey] = [];
            //     // tmp                         current = current[arrayKey];
            //     // tmp                     } else if (isIndexKey) {
            //     // tmp                         var index = parseInt(key);
            //     // tmp                         while (current.length <= index) {
            //     // tmp                             current.push({});
            //     // tmp                         }
            //     // tmp                         if (isLastKey) {
            //     // tmp                             // This shouldn't happen for i18n field, but handle it
            //     // tmp                             current[index] = value;
            //     // tmp                         } else {
            //     // tmp                             current = current[index];
            //     // tmp                         }
            //     // tmp                     } else {
            //     // tmp                         // Regular object key
            //     // tmp                         if (isLastKey) {
            //     // tmp                             // Last key - this is where we store the i18n data
            //     // tmp                             if (!current[key]) current[key] = {};
            //     // tmp                             current[key][currentLang] = value;
            //     // tmp                         } else {
            //     // tmp                             if (!current[key]) current[key] = {};
            //     // tmp                             current = current[key];
            //     // tmp                         }
            //     // tmp                     }
            //     // tmp                 }
            //     // tmp             }
            //     // tmp             
            //     // tmp             // Ensure the settings structure exists
            //     // tmp             if (!SUPER.ui.settings['_'+slug]) SUPER.ui.settings['_'+slug] = {};
            //     // tmp             
            //     // tmp             // Update settings with the i18n textarea content for the current language
            //     // tmp             updateSettingsAtPath(SUPER.ui.settings['_'+slug], i18nKeyPath, existingTranslations, i18n);
            //     // tmp             
            //     // tmp             console.log('Updated nested i18n storage:', existingTranslations);
            //     // tmp             console.log('Field keyPath:', keyPath);
            //     // tmp         } else {
            //     // tmp             console.log('No i18n storage field found in repeater item');
            //     // tmp         }
            //     // tmp     }
            //     // tmp });

            //     // tmp disabled// Translating
            //     // tmp disabled//var s = SUPER.get_stripe_settings(false, true);
            //     // tmp disabled// Now that we have all the settings for this language, we will find all the translatable field and re-generate the i18n JSON for this language
            //     // tmp disabled//console.log(s);
            //     // tmp disabled//console.log(translatableFields);
            //     // tmp disabledif(!SUPER.ui.settings['_'+slug].i18n) SUPER.ui.settings['_'+slug].i18n = {};
            //     // tmp disabledif(!SUPER.ui.settings['_'+slug].i18n[i18n]) SUPER.ui.settings['_'+slug].i18n[i18n] = {};
            //     // tmp disabledvar i18nObject = SUPER.ui.settings['_'+slug].i18n[i18n];
            //     // tmp disabledvar mainLanguageObject = SUPER.ui.settings['_'+slug];
            //     // tmp disabledtranslatableFields.forEach(field => {
            //     // tmp disabled    var value = SUPER.ui.getTabFieldValue(field,tab);
            //     // tmp disabled    var keyPath = [];
            //     // tmp disabled    var parent = field.parentElement;
            //     // tmp disabled    // Traverse up to build the key path
            //     // tmp disabled    while (parent && parent !== tab) {
            //     // tmp disabled        if(parent.classList.contains('sfui-repeater-item')){
            //     // tmp disabled            var repeater = parent.parentElement;
            //     // tmp disabled            var index = Array.from(repeater.children).indexOf(parent);
            //     // tmp disabled            keyPath.unshift(index);
            //     // tmp disabled        } else if (parent.hasAttribute('data-g')) {
            //     // tmp disabled            keyPath.unshift(parent.getAttribute('data-g'));
            //     // tmp disabled        } else if (parent.hasAttribute('data-r')) {
            //     // tmp disabled            // Find the index of the current repeater item
            //     // tmp disabled            keyPath.unshift(parent.getAttribute('data-r')+'[]');
            //     // tmp disabled        }
            //     // tmp disabled        parent = parent.parentElement;
            //     // tmp disabled    }
            //     // tmp disabled    // Construct the nested object in i18nObject
            //     // tmp disabled    if(field.name==='custom_tax_rate'){
            //     // tmp disabled    }
            //     // tmp disabled    if(field.name==='tax_rates'){
            //     // tmp disabled    }
            //     // tmp disabled    var lastKey = field.name;
            //     // tmp disabled    var obj = i18nObject;
            //     // tmp disabled    var objCompare = mainLanguageObject;
            //     // tmp disabled    var parentIsArray = false;
            //     // tmp disabled    keyPath.forEach(key => {
            //     // tmp disabled        if(!isNaN(key)){
            //     // tmp disabled            if(!obj[key]) obj[key] = {};
            //     // tmp disabled            obj = obj[key];
            //     // tmp disabled            objCompare = objCompare[key];
            //     // tmp disabled        }else{
            //     // tmp disabled            var tmpKey = key.replace('[]','');
            //     // tmp disabled            if(!obj[tmpKey] && key.indexOf('[]')!==-1){
            //     // tmp disabled                if(parentIsArray===true){
            //     // tmp disabled                    // Settings that are of type array() don't directly have children that are of type array
            //     // tmp disabled                    obj[tmpKey] = {};
            //     // tmp disabled                    parentIsArray = false;
            //     // tmp disabled                }else{
            //     // tmp disabled                    obj[tmpKey] = [];
            //     // tmp disabled                    parentIsArray = true;
            //     // tmp disabled                }
            //     // tmp disabled            }else{
            //     // tmp disabled                parentIsArray = false;
            //     // tmp disabled            }
            //     // tmp disabled            if(!obj[tmpKey]){
            //     // tmp disabled                obj[tmpKey] = {};
            //     // tmp disabled            }
            //     // tmp disabled            obj = obj[tmpKey];
            //     // tmp disabled            objCompare = objCompare[tmpKey];
            //     // tmp disabled        }
            //     // tmp disabled    });

            //     // tmp disabled    if(objCompare[lastKey]===value){
            //     // tmp disabled        // When this value equals the on from the main language delete it
            //     // tmp disabled        if(obj[lastKey]){
            //     // tmp disabled            delete obj[lastKey];
            //     // tmp disabled        }
            //     // tmp disabled    }else{
            //     // tmp disabled        // Value is different, keep it
            //     // tmp disabled        obj[lastKey] = value;
            //     // tmp disabled        if(obj.length===0){
            //     // tmp disabled            obj = {};
            //     // tmp disabled            obj[lastKey] = value;
            //     // tmp disabled        }
            //     // tmp disabled    }
            //     // tmp disabled});
            //     // tmp disabled// Clean up the data object
            //     // tmp disabledSUPER.ui.settings['_'+slug].i18n[i18n] = SUPER.ui.i18n.removeEmpty(SUPER.ui.settings['_'+slug].i18n[i18n]);
            //     // tmp disabledconsole.log(JSON.stringify(SUPER.ui.settings['_'+slug].i18n));
            //     // tmp disabledSUPER.ui.settings['_'+slug].i18n = JSON.parse(JSON.stringify(SUPER.ui.settings['_'+slug].i18n));
            //     // tmp disabledconsole.log(JSON.stringify(SUPER.ui.settings['_'+slug].i18n));

            //     // tmp disabledvar i18n_input_field = tab.querySelector('[name="i18n"]');
            //     // tmp disabledif(i18n_input_field){
            //     // tmp disabled    i18n_input_field.value = JSON.stringify(SUPER.ui.settings['_'+slug].i18n, undefined, 4);
            //     // tmp disabled}else{
            //     // tmp disabled    alert('Could not find i18n field to store translation settings for tab: '+slug);
            //     // tmp disabled}
            //     return;
            // }else{
            //     // Not translating or the tab doesn't have any translatable fields
            //     //var s = SUPER.get_stripe_settings(false, true);
            //     //console.log(s);
            //     //return;
            // }

            // // First get the field value
            // var value = el.value;
            // var type = el.type;
            // //if (type === 'checkbox') value = el.checked;
            // //if (type === 'radio') value = (tab.querySelector('[name="' + el.name + '"]:checked') ? tab.querySelector('[name="' + el.name + '"]:checked').value : '');
            // if (value === true) value = "true";
            // if (value === false) value = "false";
            // if (el.tagName === 'TEXTAREA' && tinymce.get(el.id)) {
            //     value = tinymce.get(el.id).getContent();
            // }
            // if(!i18n || i18n===''){
            //     // On main language, update normal settings
            //     SUPER.ui.settings['_'+slug] = SUPER.ui.i18n.collectDataFromParents(SUPER.ui.settings['_'+slug], el, value, slug, tab);
            // }

        i18n: {
            translating: false,
            mainLanguage: '',
            lastLanguage: '',
            addDeleteAllowed: function(){
                if(SUPER.ui.i18n.translating) return false;
                return true;
            },
            deleteFromParents: function(data, el){
                var defaultValue = el.nextElementSibling && el.nextElementSibling.className === 'sfui-original-i18n-value' ? el.nextElementSibling.value : null;
                let currentParent = el.parentElement;
                let hasDataRorG = false;
            
                while (currentParent) {
                    if (currentParent.hasAttribute('data-r')) {
                        hasDataRorG = true;
                        const key = currentParent.getAttribute('data-r');
                        const repeaterItems = Array.from(currentParent.children).filter(child => child.classList.contains('sfui-repeater-item'));
                        const index = repeaterItems.indexOf(el.closest('.sfui-repeater-item'));
            
                        if (data[key] && data[key][index]) {
                            const item = data[key][index];
                            if (el.name && el.value === defaultValue) {
                                delete item[el.name];
                            }
                            // Clean up if the item becomes empty
                            if (Object.keys(item).length === 0) {
                                data[key].splice(index, 1);
                                if (data[key].length === 0) {
                                    delete data[key];
                                }
                            }
                        }
            
                    } else if (currentParent.hasAttribute('data-g')) {
                        hasDataRorG = true;
                        const key = currentParent.getAttribute('data-g');
                        if (data[key]) {
                            if (el.name && el.value === defaultValue) {
                                delete data[key][el.name];
                            }
                            // Clean up if the group becomes empty
                            if (Object.keys(data[key]).length === 0) {
                                delete data[key];
                            }
                        }
                    }
                    currentParent = currentParent.parentElement;
                }
            
                // If no data-r or data-g was found, delete the element directly from the data object
                if (!hasDataRorG) {
                    if (el.name && el.value === defaultValue) {
                        delete data[el.name];
                    }
                }
            
                return data;
            },
            collectDataFromParents: function(data, el, value, slug, tab){
                var mainLanguageObject = data;
                var field = el;
                value = SUPER.ui.getTabFieldValue(field,tab);
                var { keyPath, i18nField: _i18nField } = SUPER.ui.getKeyPath(field, tab);
                console.log('>>> keyPath:', keyPath);
                //var keyPath = [];
                //var parent = field.parentElement;
                //// Traverse up to build the key path
                //while (parent && parent !== tab) {
                //    if(parent.classList.contains('sfui-repeater-item')){
                //        var repeater = parent.parentElement;
                //        var index = Array.from(repeater.children).indexOf(parent);
                //        keyPath.unshift(index);
                //    } else if (parent.hasAttribute('data-g')) {
                //        keyPath.unshift(parent.getAttribute('data-g'));
                //    } else if (parent.hasAttribute('data-r')) {
                //        // Find the index of the current repeater item
                //        keyPath.unshift(parent.getAttribute('data-r')+'[]');
                //    }
                //    parent = parent.parentElement;
                //}
                // Construct the nested object in i18nObject
                var lastKey = field.name;
                var obj = mainLanguageObject;
                var parentIsArray = false;
                keyPath.forEach(key => {
                    if(!isNaN(key)){
                        if(!obj[key]) obj[key] = {};
                        obj = obj[key];
                    }else{
                        var tmpKey = key.replace('[]','');
                        if(!obj[tmpKey] && key.indexOf('[]')!==-1){
                            if(parentIsArray===true){
                                // Settings that are of type array() don't directly have children that are of type array
                                obj[tmpKey] = {};
                                parentIsArray = false;
                            }else{
                                obj[tmpKey] = [];
                                parentIsArray = true;
                            }
                        }else{
                            parentIsArray = false;
                        }
                        if(!obj[tmpKey]){
                            obj[tmpKey] = {};
                        }
                        obj = obj[tmpKey];
                    }
                });
                // Value is different, keep it
                // Guard against obj being undefined (can happen with Email v2 tab)
                if (obj) {
                    obj[lastKey] = value;
                } else {
                    console.warn('Cannot set property on undefined object in collectDataFromParents', { lastKey, value, keyPath });
                }
                // Clean up the data object
                data = SUPER.ui.i18n.removeEmpty(data);
                return data;

                // tmp // Check for translation value
                // tmp var i18n = document.querySelector('.super-create-form').dataset.i18n;
                // tmp var defaultValue = el.nextElementSibling && el.nextElementSibling.className === 'sfui-original-i18n-value' ? el.nextElementSibling.value : null;
                // tmp let currentParent = el.parentElement;
                // tmp let hasDataRorG = false;
                // tmp while (currentParent) {
                // tmp     if (currentParent.hasAttribute('data-r')) {
                // tmp         hasDataRorG = true;
                // tmp         const key = currentParent.getAttribute('data-r');
                // tmp         const repeaterItems = Array.from(currentParent.children).filter(child => child.classList.contains('sfui-repeater-item'));
                // tmp         const index = repeaterItems.indexOf(el.closest('.sfui-repeater-item'));
        
                // tmp         if (!data[key]) {
                // tmp             data[key] = [];
                // tmp         }
        
                // tmp         const item = data[key][index] || {};
        
                // tmp         // Collect all fields within the repeater item
                // tmp         const fields = el.closest('.sfui-repeater-item').querySelectorAll('[name]');
                // tmp         let allTranslatableFieldsMatchDefault = true;
        
                // tmp         fields.forEach(field => {
                // tmp             const fieldDefaultValue = field.nextElementSibling && field.nextElementSibling.className === 'sfui-original-i18n-value' ? field.nextElementSibling.value : null;
                // tmp             if (field.name && field.value !== fieldDefaultValue) {
                // tmp                 item[field.name] = field.value;
                // tmp                 if (fieldDefaultValue !== null) {
                // tmp                     allTranslatableFieldsMatchDefault = false;
                // tmp                 }
                // tmp             } else if (field.name && field.value === fieldDefaultValue) {
                // tmp                 delete item[field.name];
                // tmp             }
                // tmp         });
        
                // tmp         for (const attr of currentParent.attributes) {
                // tmp             if (attr.name.startsWith('data-') && attr.name !== 'data-r' && attr.name !== 'data-g') {
                // tmp                 item[attr.name.replace('data-', '')] = attr.value;
                // tmp             }
                // tmp         }
        
                // tmp         if (i18n && i18n!=='' && allTranslatableFieldsMatchDefault) {
                // tmp             delete data[key][index]; // Remove the item if all translatable fields match the default value
                // tmp         } else {
                // tmp             data[key][index] = item;
                // tmp         }
        
                // tmp     } else if (currentParent.hasAttribute('data-g')) {
                // tmp         hasDataRorG = true;
                // tmp         const key = currentParent.getAttribute('data-g');
                // tmp         if (!data[key]) {
                // tmp             data[key] = {};
                // tmp         }
        
                // tmp         for (const attr of currentParent.attributes) {
                // tmp             if (attr.name.startsWith('data-') && attr.name !== 'data-r' && attr.name !== 'data-g') {
                // tmp                 data[key][attr.name.replace('data-', '')] = attr.value;
                // tmp             }
                // tmp         }
        
                // tmp         if (el.name && value !== defaultValue) {
                // tmp             data[key][el.name] = value;
                // tmp         } else if (el.name && value === defaultValue) {
                // tmp             delete data[key][el.name];
                // tmp         }
                // tmp     }
                // tmp     currentParent = currentParent.parentElement;
                // tmp }
                // tmp // If no data-r or data-g was found, add the element directly to the data object
                // tmp if (!hasDataRorG) {
                // tmp     if (el.name && value !== defaultValue) {
                // tmp         data[el.name] = value;
                // tmp     } else if (el.name && value === defaultValue) {
                // tmp         delete data[el.name];
                // tmp     }
                // tmp }
                // tmp // Clean up the data object
                // tmp data = SUPER.ui.i18n.removeEmpty(data);
                // tmp return data;
            },
            removeEmpty: function(obj) {
                // console.log("Initial obj (deep copy):", JSON.parse(JSON.stringify(obj))); // Log a deep copy to preserve initial state
                function hasNamedProperties(item) {
                    // Check if the item is an object with named properties
                    return typeof item === 'object' && !Array.isArray(item) && Object.keys(item).length > 0;
                }
                function isNonEmptyArrayWithNamedProperties(item) {
                    // Check if it's an array with additional named properties (length == 0 but has keys)
                    return Array.isArray(item) && Object.keys(item).length > 0 && item.length === 0;
                }
                if (Array.isArray(obj)) {
                    console.log("Object is an array. Length:", obj.length);
                    return obj
                        .map((item, index) => {
                            // console.log(`Processing array item at index ${index}:`, item, "Type:", typeof item);
                            try {
                                const result = SUPER.ui.i18n.removeEmpty(item);
                                // console.log(`Result after recursive call for item at index ${index}:`, result);
                                return result;
                            } catch (error) {
                                console.error(`Error processing array item at index ${index}:`, error);
                                return undefined;
                            }
                        })
                        .filter((item, _index) => {
                            const keepItem = item !== undefined && 
                                ((Array.isArray(item) && item.length > 0) || 
                                 (hasNamedProperties(item)) || 
                                 isNonEmptyArrayWithNamedProperties(item) || 
                                 (Object.entries(item).length > 0));
                            // console.log(`Filtering array item at index ${index}:`, item, "Keep:", keepItem);
                            return keepItem;
                        });
                } else if (typeof obj === 'object' && obj !== null) {
                    console.log("Object is a non-null object.");
                    const newObj = {};
                    Object.keys(obj).forEach(key => {
                        // console.log(`Processing object key "${key}":`, obj[key], "Type:", typeof obj[key]);
            
                        try {
                            const _beforeRemoveEmpty = JSON.parse(JSON.stringify(obj[key]));
                            const value = SUPER.ui.i18n.removeEmpty(obj[key]);
                            if(value===undefined && obj[key] !== undefined){
                                delete obj[key]; 
                                return;
                            }
                            // console.log(`Before recursive call for key "${key}":`, beforeRemoveEmpty);
                            // console.log(`Result after recursive call for key "${key}":`, value);
            
                            // New criteria: Check both length, named properties, and entries to determine if the original value should be kept
                            const hasEntries = (Array.isArray(obj[key]) && obj[key].length > 0) || 
                                               hasNamedProperties(obj[key]) || 
                                               isNonEmptyArrayWithNamedProperties(obj[key]) || 
                                               Object.entries(obj[key]).length > 0;
                            // console.log(`Filtering key "${key}":`, obj[key], "Has Entries:", hasEntries);
                            if(obj[key] !== undefined && hasEntries) {
                                newObj[key] = obj[key];
                            }
                        } catch (error) {
                            console.error(`Error processing key "${key}":`, error);
                        }
                    });
                    const hasKeys = Object.keys(newObj).length > 0;
                    // console.log("New object after filtering:", newObj, "Has keys:", hasKeys);
                    return hasKeys ? newObj : undefined;
                }
            
                console.log("Return obj as is:", obj);
                return obj;
            },
            // Function to load image based on attachment ID
            reload_attachments: function(node){
                if(node.value===''){
                    node.parentNode.querySelector(':scope .file-preview').innerHTML = '';
                    return;
                }
                var fileIDs = node.value.split(',');
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function(){
                    if(this.readyState==4 && this.status==200){
                        var html='', attachments = JSON.parse(this.responseText);
                        Object.keys(attachments).forEach(function(key){
                            html += '<li data-file="'+attachments[key].id+'">';
                            html += '<div class="super-image">';
                            html += '<img src="' + attachments[key].url + '" />';
                            html += '</div>';
                            html += '<a target="_blank" href="'+attachments[key].editLink+'">' + attachments[key].filename + '</a>';
                            html += '<a href="#" class="super-delete">Delete</a>';
                            html += '</li>';
                        });
                        node.parentNode.querySelector(':scope .file-preview').innerHTML = html;
                    }
                };
                xhttp.onerror = function () {
                    console.log(this);
                    console.log("** An error occurred during the transaction");
                };
                xhttp.open("POST", ajaxurl, true);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
                var params = $.param({action: 'super_ui_i18n_reload_attachments', fileIDs: fileIDs});
                xhttp.send(params);
            },
            getTranslatedValue: function(el, i18n_data, i18n, tab){
                var field = el;
                var keyPath = [];
                var parent = field.parentElement;
                // Traverse up to build the key path
                while (parent && parent !== tab) {
                    if(parent.classList.contains('sfui-repeater-item')){
                        var repeater = parent.parentElement;
                        var index = Array.from(repeater.children).indexOf(parent);
                        keyPath.unshift(index);
                    } else if (parent.hasAttribute('data-g')) {
                        keyPath.unshift(parent.getAttribute('data-g'));
                    } else if (parent.hasAttribute('data-r')) {
                        // Find the index of the current repeater item
                        keyPath.unshift(parent.getAttribute('data-r')+'[]');
                    }
                    parent = parent.parentElement;
                }
                var obj = i18n_data[i18n];
                if(typeof obj !=='undefined'){
                    keyPath.forEach(key => {
                        if(!isNaN(key)){
                            obj = obj[key];
                        }else{
                            obj = obj[key.replace('[]','')];
                        }
                        if(!obj) return;
                    });
                    if(!obj || !obj[field.name]) return field.value;
                    return obj[field.name];
                }
                return field.value;
            },
            findParentsWithAttributes: function(el, attrs){
                var parents = [];
                var parent = el.parentElement;
                while(parent){
                    if (attrs.some(attr => parent.hasAttribute(attr))){
                        parents.push(parent);
                    }
                    parent = parent.parentElement;
                }
                return parents;
            }, 
            get_keys: function(keys, el, value, slug){
                var p = el.parentNode.closest('[data-g]');
                if(p){
                    // is group
                    if(slug==='triggers' && p.dataset.g!=='data'){
                        // Skip `data` key for triggers
                        keys.unshift(p.dataset.g);
                    }
                    return SUPER.ui.i18n.get_keys(keys, p, value, slug);
                }
                return keys;
            },
            restore_original_value: function(){
                SUPER.ui.i18n.lastLanguage = SUPER.ui.i18n.mainLanguage;
                var i, nodes = document.querySelectorAll('.sfui-i18n [name]');
                for(i=0; i<nodes.length; i++){
                    // Delete if exists 
                    if(nodes[i].nextElementSibling && nodes[i].nextElementSibling.className==='sfui-original-i18n-value'){
                        // Set value to this again
                        SUPER.ui.setTabFieldValue(nodes[i], nodes[i].nextElementSibling.value);
                        // if(nodes[i].tagName==='TEXTAREA' && tinymce.get(nodes[i].id)){
                        //     tinymce.get(nodes[i].id).setContent(nodes[i].nextElementSibling.value);
                        // }else{
                        //     if(nodes[i].type==='checkbox'){
                        //         nodes[i].checked = (nodes[i].nextElementSibling.value==='true' ? true : false);
                        //     }else{
                        //         if(nodes[i].value!==nodes[i].nextElementSibling.value){
                        //             // Re-load attachment image preview
                        //             if(nodes[i].parentNode.closest('.sfui-setting').classList.contains('sfui-type-files')){
                        //                 nodes[i].value = nodes[i].nextElementSibling.value;
                        //                 SUPER.ui.i18n.reload_attachments(nodes[i]);
                        //             }
                        //             nodes[i].value = nodes[i].nextElementSibling.value;
                        //         }
                        //     }
                        // }
                        nodes[i].nextElementSibling.remove();
                        continue;
                    }
                }
            },
        }
    };

    SUPER.update_form_elements = function(string){
        document.querySelector('.super-raw-code-form-elements textarea').value = SUPER.get_form_elements(string);
    };
    SUPER.update_emails_settings = function(string){
        document.querySelector('.super-raw-code-emails-settings textarea').value = SUPER.get_emails_settings(string);
    };
    SUPER.update_form_settings = function(string, el){
        document.querySelector('.super-raw-code-form-settings textarea').value = SUPER.get_form_settings(string, el);
    };
    SUPER.update_theme_settings = function(string){
        document.querySelector('.super-raw-code-theme-settings textarea').value = SUPER.get_theme_settings(string);
    };
    SUPER.update_trigger_settings = function(string){
        document.querySelector('.super-raw-code-trigger-settings textarea').value = SUPER.get_trigger_settings(string);
    };
    SUPER.update_woocommerce_settings = function(string){
        document.querySelector('.super-raw-code-woocommerce-settings textarea').value = SUPER.get_woocommerce_settings(string);
    };
    SUPER.update_listings_settings = function(string){
        document.querySelector('.super-raw-code-listings-settings textarea').value = SUPER.get_listings_settings(string);
    };
    SUPER.update_pdf_settings = function(string){
        document.querySelector('.super-raw-code-pdf-settings textarea').value = SUPER.get_pdf_settings(string);
    };
    SUPER.update_stripe_settings = function(string){
        document.querySelector('.super-raw-code-stripe-settings textarea').value = SUPER.get_stripe_settings(string);
    };
    SUPER.update_translation_settings = function(string){
        document.querySelector('.super-raw-code-translation-settings textarea').value = SUPER.get_translation_settings(string);
    };
    SUPER.formatUniqueFieldName = function(value){
        value = value.replace(/\s+/gi, '_');
        value = value.replace(/ /g, "_");
        value = value.replace(/\//g, "");
        value = value.replace(/[^a-zA-Z0-9-_.]+/g, "");
        value = value.replace(/\.+/g, "_");
        value = value.replace(/[--]+/g, "-");
        value = value.replace(/[__]+/g, "_");
        return value;
    };
    SUPER.get_form_elements = function(string){
        var $elements = SUPER.regenerate_element_inner.get_elements();
        if(string===true) {
            if(!isEmpty($elements)) return JSON.stringify($elements, undefined, 4);
            return '';
        }
        return $elements;
    };
    SUPER.get_form_settings = function(string, el){
        if(typeof string === 'undefined') string = false;
        var specificTab = false;
        if(el){
            // If a setting was updated, determine which tab it belongs to.
            // Then only update that specific part in the settings for speed improvements
            var tab = el.closest('.super-tab-content');
            if(tab){
                var tabSlug = tab.className.replace('super-tab-content super-tab-', '');
                tabSlug = tabSlug.replace('super-active', '').trim();
                if(tabSlug==='triggers' || tabSlug==='woocommerce' || tabSlug==='pdf' || tabSlug==='listings' || tabSlug==='stripe'){
                    var specificTab = true;
                }
            }
        }
        if(specificTab){
            var $settings = JSON.parse(document.querySelector('.super-raw-code-form-settings textarea').value);
            // Triggers settings OR
            // WooCommerce settings OR
            // PDF settings OR
            // Listing settings OR
            // Stripe settings
            $settings = SUPER.get_tab_settings($settings, tabSlug);
        }else{
            // Grab all settings anew
            var $settings = {};
            var includeGlobalValues = false;
            if(string===false){
                includeGlobalValues = document.querySelector('input[name="retain_underlying_global_values"]').checked;
            }
            $('.super-create-form .super-form-settings .super-element-field').each(function () {
                var $this = $(this);
                var $hidden = false;

                // select parent based on .filter class
                var $parent = $this.parents('.super-field.super-filter');
                $parent.each(function () {
                    if ($(this).css('display') == 'none') {
                        $hidden = true;
                    }
                });

                // now select based on only .super-field class
                $parent = $this.parents('.super-field');
                if ($hidden === false) {
                    var $name = $this.attr('name');
                    if(includeGlobalValues){
                        $value = $this.val();
                    }else{
                        var $value = '_g_';
                        if(!$this[0].closest('._g_')){ //!$parent.hasClass('_g_')){
                            $value = $this.val();
                        }
                    }
                    $settings[$name] = $value;
                }
            });
        }
        if(string===true) {
            if(!isEmpty($settings)) return JSON.stringify($settings, undefined, 4);
            return '';
        }
        return $settings;
    };
    // tmp disabled SUPER.processRepeaterItems = function(args){
    // tmp disabled     var i, x, k = args.node.dataset.r, nodes = args.node.querySelectorAll(':scope > .sfui-repeater-item'),
    // tmp disabled         subData, keys, fields, value, names,
    // tmp disabled         parentRepeater;
    // tmp disabled     for(i=0; i<nodes.length; i++){
    // tmp disabled         // Check if key consist of multiple levels
    // tmp disabled         keys = k.split('.');
    // tmp disabled         if(keys.length>1){
    // tmp disabled             if(typeof args.data[keys[0]] === 'undefined') args.data[keys[0]] = {};
    // tmp disabled             if(typeof args.data[keys[0]][keys[1]] === 'undefined') args.data[keys[0]][keys[1]] = {};
    // tmp disabled             if(typeof args.data[keys[0]][keys[1]][i] === 'undefined') args.data[keys[0]][keys[1]][i] = {};
    // tmp disabled         }else{
    // tmp disabled             if(typeof args.data[k] === 'undefined') args.data[k] = {};
    // tmp disabled             if(typeof args.data[k][i] === 'undefined') args.data[k][i] = {};
    // tmp disabled         }
    // tmp disabled         x, fields = nodes[i].querySelectorAll('[name]');
    // tmp disabled         for(x=0; x<fields.length; x++){
    // tmp disabled             parentRepeater = fields[x].closest('.sfui-repeater');
    // tmp disabled             if(parentRepeater && parentRepeater!==args.node){
    // tmp disabled                 // is inner repeater, must process it
    // tmp disabled                 if(keys.length>1){
    // tmp disabled                     args.data[keys[0]][keys[1]][i] = SUPER.processRepeaterItems({tab: args.tab, node: parentRepeater, depth: args.depth, data: args.data[keys[0]][keys[1]][i]});
    // tmp disabled                 }else{
    // tmp disabled                     args.data[k][i] = SUPER.processRepeaterItems({tab: args.tab, node: parentRepeater, depth: args.depth, data: args.data[k][i]});
    // tmp disabled                 }
    // tmp disabled                 continue;
    // tmp disabled             }
    // tmp disabled             // is direct inner field, must add it to the data
    // tmp disabled             value = fields[x].value;
    // tmp disabled             if(fields[x].type==='checkbox') value = fields[x].checked;
    // tmp disabled             if(fields[x].type==='radio') value = (args.tab.querySelector('[name="'+fields[x].name+'"]:checked') ? args.tab.querySelector('[name="'+fields[x].name+'"]:checked').value : '');
    // tmp disabled             if(value===true) value = "true"; 
    // tmp disabled             if(value===false) value = "false"; // We use "empty" instead of "false" to have a more cleaned up data
    // tmp disabled             if(fields[x].tagName==='TEXTAREA' && tinymce.get(fields[x].id)){
    // tmp disabled                 value = tinymce.get(fields[x].id).getContent();
    // tmp disabled             }
    // tmp disabled             // @TODO: if(value==='') continue;
    // tmp disabled             var name = fields[x].name.split('.').pop();
    // tmp disabled             args.data[k][i][name] = value;
    // tmp disabled             // tmp names = fields[x].name.split('.');
    // tmp disabled             // tmp if(names.length>1){
    // tmp disabled             // tmp     if(keys.length>1){
    // tmp disabled             // tmp         subData = args.data[keys[0]][keys[1]][i];
    // tmp disabled             // tmp     }else{
    // tmp disabled             // tmp         subData = args.data[k][i];
    // tmp disabled             // tmp     }
    // tmp disabled             // tmp     if(typeof subData[names[0]] === 'undefined') subData[names[0]] = {};
    // tmp disabled             // tmp     if(names.length===2){
    // tmp disabled             // tmp         subData[names[0]][names[1]] = value;
    // tmp disabled             // tmp     }else{
    // tmp disabled             // tmp         if(names.length===3){
    // tmp disabled             // tmp             if(typeof subData[names[0]][names[1]] === 'undefined') subData[names[0]][names[1]] = {};
    // tmp disabled             // tmp             subData[names[0]][names[1]][names[2]] = value;
    // tmp disabled             // tmp         }else{
    // tmp disabled             // tmp             if(names.length===4){
    // tmp disabled             // tmp                 if(typeof subData[names[0]][names[1]][names[2]] === 'undefined') subData[names[0]][names[1]][names[2]] = {};
    // tmp disabled             // tmp                 subData[names[0]][names[1]][names[2]][names[3]] = value;
    // tmp disabled             // tmp             }
    // tmp disabled             // tmp         }
    // tmp disabled             // tmp     }
    // tmp disabled             // tmp     if(keys.length>1){
    // tmp disabled             // tmp         args.data[keys[0]][keys[1]][i] = subData;
    // tmp disabled             // tmp     }else{
    // tmp disabled             // tmp         args.data[k][i] = subData;
    // tmp disabled             // tmp     }
    // tmp disabled             // tmp }else{
    // tmp disabled            // tmp     if(keys.length>1){
    // tmp disabled            // tmp         args.data[keys[0]][keys[1]][i][names[0]] = value;
    // tmp disabled            // tmp     }else{
    // tmp disabled            // tmp         args.data[k][i][names[0]] = value;
    // tmp disabled            // tmp     }
    // tmp disabled            // tmp }
    // tmp disabled         }
    // tmp disabled     }
    // tmp disabled     return args.data;
    // tmp disabled };

    SUPER.get_obj_value_by_key = function(obj, is, value){
        if(typeof is==='string'){
            return SUPER.get_obj_value_by_key(obj, is.split('.'), value);
        }else if(is.length===1 && value!==undefined && value!==''){
            obj[is[0]] = value;
            return obj;
        }else if(is.length===0){
            return obj;
        }else{
            if(typeof obj[is[0]]==='undefined'){
                //if(is.length===1) return obj[is[0]] = value;
                if(is.length>1) obj[is[0]] = {}
            }
            return SUPER.get_obj_value_by_key(obj[is[0]], is.slice(1), value);
        }
    };
    SUPER.add_country_flags = function(i18n, flag, nodes){
        if(flag){
            for(var i=0; i<nodes.length; i++){
                // Add the country flag next to the setting title to indicate translatable option
                var node = nodes[i].closest('label').querySelector('.sfui-title');
                if(node){
                    var cloneFlag = flag.cloneNode();
                    cloneFlag.title = 'Translation for ' + (i18n ? i18n : 'main language');
                    if (node.querySelector('.flag')) node.querySelector('.flag').remove();
                    node.appendChild(cloneFlag);
                }else{
                    var node = nodes[i].closest('label').querySelector('.sfui-subline');
                    if(node){
                        var cloneFlag = flag.cloneNode();
                        cloneFlag.title = 'Translation for ' + (i18n ? i18n : 'main language');
                        if (node.querySelector('.flag')) node.querySelector('.flag').remove();
                        node.appendChild(cloneFlag);
                    }
                }
            }
        }
    };
    SUPER.get_tab_settings = function(settings, slug, tab, data, returnData){
        if(typeof returnData === 'undefined') returnData = false;
        var nodes, i, i18n_data = null, field, fieldName, newField, mainLanguageValue,
        i18n = document.querySelector('.super-create-form').dataset.i18n;
        if(SUPER.ui.settings['_'+slug]){
            // Get the current country flag
            var flag = document.querySelector(':scope .super-tabs > .super-tab-builder > .flag');
            // Remember the original value for translatable settings
            if(typeof tab==='undefined'){
                tab = document.querySelector('.super-tab-content.super-tab-' + slug);
            }
            nodes = tab.querySelectorAll('.sfui-i18n [name]');
            SUPER.add_country_flags(i18n, flag, nodes);
            if(SUPER.ui.i18n.translating) {
                for (i = 0; i < nodes.length; i++) {
                    mainLanguageValue = undefined;
                    field = nodes[i];
                    fieldName = field.name;
                    if(fieldName==='i18n') continue;
                    console.log('>>> fieldName:', fieldName);
                    var { keyPath, i18nField } = SUPER.ui.getKeyPath(field, tab);
                    console.log('>>> keyPath:', keyPath);
                    console.log('>>> i18nField:', i18nField);
                    // Skip if already exists
                    if (nodes[i].nextElementSibling && nodes[i].nextElementSibling.className === 'sfui-original-i18n-value') {
                        mainLanguageValue = nodes[i].nextElementSibling.value;
                    }else{
                        var fieldValue = SUPER.ui.getTabFieldValue(field, tab);
                        console.log('>>> fieldValue:', fieldValue);
                        if(nodes[i].type === 'textarea'){
                            newField = document.createElement('textarea');
                        }else{
                            newField = document.createElement('input');
                        }
                        newField.value = fieldValue;
                        newField.className = 'sfui-original-i18n-value';
                        newField.style.display = 'none';
                        nodes[i].parentNode.insertBefore(newField, nodes[i].nextSibling);
                    }
                    // Get the translated value if it exists and update the field value with it
                    if(i18nField && i18nField.value){
                        try {
                            var i18nData = JSON.parse(i18nField.value);
                            // Find where the keyPath intersects with the i18n data structure
                            var current = i18nData;
                            var pathStartIndex = -1;
                            // Try to find a matching key in the i18n data to determine where to start traversing
                            for (var startIdx = 0; startIdx < keyPath.length; startIdx++) {
                                var testKey = keyPath[startIdx];
                                var cleanKey = typeof testKey === 'string' ? testKey.replace(/\[\]$/, '') : testKey;
                                if (current && typeof current === 'object' && Object.prototype.hasOwnProperty.call(current, cleanKey)) {
                                    pathStartIndex = startIdx;
                                    break;
                                }
                            }
                            // If we found a starting point, traverse from there
                            if(pathStartIndex >= 0){
                                for (var j = pathStartIndex; j < keyPath.length; j++) {
                                    var key = keyPath[j];
                                    if (current && typeof current === 'object') {
                                        if (Array.isArray(current) && !isNaN(key)) {
                                            // Handle array index
                                            current = current[parseInt(key)];
                                        } else if (typeof key === 'string') {
                                            // Handle object property (remove [] suffix if present)
                                            var cleanKey = key.replace(/\[\]$/, '');
                                            current = current[cleanKey];
                                        }
                                    } else {
                                        current = null;
                                        break;
                                    }
                                }
                            }else{
                                // If no intersection found, current remains as the root i18n data
                                current = i18nData;
                            }
                            // Now look for the fieldName in the current object
                            if (current && typeof current === 'object' && current[fieldName]) {
                                var translatedValue = current[fieldName];
                                // If translatedValue is an object (like {"am": "New question1nl"}),
                                // get the value for the current language code
                                if(typeof translatedValue === 'object' && translatedValue[i18n]){
                                    translatedValue = translatedValue[i18n];
                                }
                                // Update the original field with the translated value
                                if(typeof translatedValue !== 'object' && translatedValue){
                                    SUPER.ui.setTabFieldValue(field, translatedValue);
                                    console.log('>>> Updated field with translated value for language "' + i18n + '":', translatedValue);
                                }else{
                                    if(mainLanguageValue){
                                        SUPER.ui.setTabFieldValue(field, mainLanguageValue);
                                        console.log('>>> Updated field with main language "' + i18n + '":', translatedValue);
                                    }else{
                                        console.log('>>> value unknown?')
                                        SUPER.ui.setTabFieldValue(field, '');
                                    }
                                }
                            }
                        }catch(e){
                            console.error('Error parsing i18n JSON:', e);
                        }
                    }
                    // tmp // Get the translated value if it exists and update the field value with it
                    // tmp if (i18nField && i18nField.value) {
                    // tmp     try {
                    // tmp         var json = JSON.parse(i18nField.value);

                    // tmp         // Skip the top-level repeater key and its index if present
                    // tmp         var kp = keyPath.slice(); // copy
                    // tmp         if (kp.length >= 2 && typeof kp[0] === 'string' && kp[0].endsWith('[]') && typeof kp[1] === 'number') {
                    // tmp             kp = kp.slice(2);
                    // tmp         }

                    // tmp         var obj = json;
                    // tmp         for (var j = 0; j < kp.length; j++) {
                    // tmp             var key = kp[j];
                    // tmp             if (typeof key === 'string' && key.endsWith('[]')) {
                    // tmp                 key = key.slice(0, -2);
                    // tmp             }
                    // tmp             obj = obj?.[key];
                    // tmp             if (typeof kp[j + 1] === "number" && Array.isArray(obj)) {
                    // tmp                 obj = obj[kp[j + 1]];
                    // tmp                 j++;
                    // tmp             }
                    // tmp             if (!obj) break;
                    // tmp         }

                    // tmp         if (obj && obj[fieldName]) {
                    // tmp             var firstLangKey = Object.keys(obj[fieldName])[0];
                    // tmp             var translated = obj[fieldName][firstLangKey];
                    // tmp             if (translated) nodes[i].value = translated;
                    // tmp         }
                    // tmp     } catch (e) {
                    // tmp         console.warn('Failed to parse or lookup i18n JSON:', e);
                    // tmp     }
                    // tmp }


                }

                // Populate fields with i18n data
                if(SUPER.ui.settings['_'+slug].i18n){
                    var i18n_data = JSON.parse(JSON.stringify(SUPER.ui.settings['_'+slug].i18n));
                    if(i18n_data[i18n]){
                        for(i = 0; i < nodes.length; i++){
                            var value = nodes[i].value;
                            var translatedValue = SUPER.ui.i18n.getTranslatedValue(nodes[i], i18n_data, i18n, tab);
                            if(translatedValue!==null){
                                k = nodes[i].name.split('.').pop();
                                if(!i18n_data[k]){
                                    SUPER.ui.setTabFieldValue(nodes[i], translatedValue);
                                    //if(nodes[i].tagName === 'TEXTAREA' && tinymce.get(nodes[i].id)){
                                    //    i18n_data[k] = nodes[i].nextElementSibling.value;
                                    //    tinymce.get(nodes[i].id).setContent(translatedValue);
                                    //} else {
                                    //    if(nodes[i].type === 'checkbox'){
                                    //        nodes[i].checked = (translatedValue === 'true');
                                    //    }else{
                                    //        if (nodes[i].value !== translatedValue) {
                                    //            // Re-load attachment image preview
                                    //            if (nodes[i].parentNode.closest('.sfui-setting').classList.contains('sfui-type-files')) {
                                    //                nodes[i].value = translatedValue;
                                    //                SUPER.ui.i18n.reload_attachments(nodes[i]);
                                    //            }
                                    //        }
                                    //        nodes[i].value = translatedValue;
                                    //    }
                                    //}
                                }
                            }
                        }
                    }
                }
            } else {
                // Reset remembered original value for translatable settings
                if(slug==='triggers' || slug==='stripe'){
                    if(SUPER.ui.i18n.lastLanguage!=='' && SUPER.ui.i18n.lastLanguage!==SUPER.ui.i18n.mainLanguage){
                        SUPER.ui.i18n.restore_original_value();
                    }
                }else{
                    //SUPER.ui.i18n.restore_original_value();
                }
            }
            if(SUPER.ui.i18n.lastLanguage==='' || (SUPER.ui.i18n.lastLanguage!=='' && SUPER.ui.i18n.lastLanguage!==SUPER.ui.i18n.mainLanguage)){
                // Do not return settings, instead update
            }else{
                if(returnData){
                    return SUPER.ui.settings['_'+slug];
                }
            }
            return SUPER.ui.settings['_'+slug];
        }
        if(returnData){
            return SUPER.ui.settings['_'+slug];
        }
        SUPER.ui.i18n.mainLanguage = (document.querySelector('.super-default-language .super-item.super-active') ? document.querySelector('.super-default-language .super-item.super-active').dataset.value : '');
        var returnObj = false;
        if (typeof data === 'undefined') {
            data = {};
        } else {
            returnObj = true;
        }
        if (typeof tab === 'undefined') {
            tab = document.querySelector('.super-tab-content.super-tab-' + slug);
        }
        if (!tab) {
            return settings;
        }
        // Get the current country flag
        var flag = document.querySelector(':scope .super-tabs > .super-tab-builder > .flag');
        // Remember the original value for translatable settings
        nodes = tab.querySelectorAll('.sfui-i18n [name]');
        SUPER.add_country_flags(i18n, flag, nodes);
        if(SUPER.ui.i18n.translating) {
            for (i = 0; i < nodes.length; i++) {
                // Skip if already exists
                if (nodes[i].nextElementSibling && nodes[i].nextElementSibling.className === 'sfui-original-i18n-value') {
                    continue;
                }
                if (nodes[i].type === 'textarea') {
                    if (tinymce.get(nodes[i].id)) {
                        var value = tinymce.get(nodes[i].id).getContent();
                    } else {
                        var value = nodes[i].value;
                    }
                    var field = document.createElement('textarea');
                } else {
                    var value = nodes[i].value;
                    if (nodes[i].type === 'checkbox') {
                        value = nodes[i].checked ? 'true' : 'false';
                    }
                    var field = document.createElement('input');
                    field.type = 'hidden';
                }
                field.value = value;
                field.className = 'sfui-original-i18n-value';
                field.style.display = 'none';
                nodes[i].parentNode.insertBefore(field, nodes[i].nextSibling);
            }
        } else {
            // Reset remembered original value for translatable settings
            if(SUPER.ui.i18n.lastLanguage!=='' && SUPER.ui.i18n.lastLanguage!==SUPER.ui.i18n.mainLanguage){
                //if (!$('.super-create-form').hasClass('super-translation-mode')) {
                SUPER.ui.i18n.restore_original_value();
            }
        }
    
        // Adjust the number of repeater items based on i18n_data
        if (SUPER.ui.i18n.translating && (slug==='triggers' || slug==='stripe') && tab.classList.contains('super-tab-content')){
            var p = tab;
            // Initialize i18n_data if not already done
            // tmp if (i18n_data === null) {
            // tmp     var i18n_input_field = p.querySelector('[name="i18n"]');
            // tmp     var i18n_value = i18n_input_field.value.trim();
            // tmp     i18n_input_field.classList.remove('sfui-red');
            // tmp     if (i18n_value === '') {
            // tmp         i18n_data = {};
            // tmp         i18n_data[i18n] = {};
            // tmp     } else {
            // tmp         try {
            // tmp             i18n_data = JSON.parse(i18n_value);
            // tmp             var changed = false;
            // tmp             Object.keys(i18n_data).forEach(function(key) {
            // tmp                 if (Array.isArray(i18n_data[key])) {
            // tmp                     i18n_data[key] = {};
            // tmp                     changed = true;
            // tmp                 }
            // tmp             });
            // tmp             if (changed) {
            // tmp                 i18n_input_field.value = JSON.stringify(i18n_data, undefined, 4);
            // tmp             }
            // tmp         }
            // tmp         catch (e) {
            // tmp             console.error(e);
            // tmp             i18n_data = {};
            // tmp             i18n_data[i18n] = {};
            // tmp             i18n_input_field.classList.add('sfui-red');
            // tmp         }
            // tmp     }
            // tmp }
            if(!SUPER.ui.settings['_'+slug].i18n){
                SUPER.ui.settings['_'+slug].i18n = {};
            }
            i18n_data = SUPER.ui.settings['_'+slug].i18n;
            // Adjust repeater items based on i18n_data
            var repeaters = tab.querySelectorAll('[data-r]');
            repeaters.forEach(function(repeater) {
                var key = repeater.getAttribute('data-r');
                if (i18n_data[i18n] && i18n_data[i18n][key]) {
                    var repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                    var targetCount = i18n_data[i18n][key].length;
    
                    // Add missing items
                    while (repeaterItems.length < targetCount) {
                        SUPER.ui.btn(event, repeater.querySelector('.add-repeater-item-btn'), 'addRepeaterItem', true);
                        repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                    }
    
                    // Remove excess items
                    while (repeaterItems.length > targetCount) {
                        SUPER.ui.btn(event, repeaterItems[repeaterItems.length - 1].querySelector('.delete-repeater-item-btn'), 'deleteRepeaterItem', true);
                        repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                    }
                }
            });
        }
        // First grab all settings that are not inside a repeater element
        var i, k, nodes = tab.querySelectorAll('[data-g], [data-r], [name]');
        for (i = 0; i < nodes.length; i++) {
            if (nodes[i].classList.contains('sf-processed')) {
                continue;
            }
            nodes[i].classList.add('sf-processed');
            if (nodes[i].dataset.r) {
                var ri, repeaterItems = nodes[i].querySelectorAll(':scope > .sfui-repeater-item');
                k = nodes[i].dataset.r;
                for (ri = 0; ri < repeaterItems.length; ri++) {
                    if (!data[k]) data[k] = [];
                    if (!data[k][ri]) data[k][ri] = {};
                    data[k][ri] = SUPER.get_tab_settings(settings, slug, repeaterItems[ri], data[k][ri]);
                }
                continue;
            }
            if (nodes[i].dataset.g) {
                // is group
                k = nodes[i].dataset.g;
                if (!data[k]) data[k] = {};
                // Lookup any inner fields
                data[k] = SUPER.get_tab_settings(settings, slug, nodes[i], data[k]);
                continue;
            }
            if (nodes[i].name) {
                // is field
                // first check if we are in translation mode
                if (SUPER.ui.i18n.translating && nodes[i].closest('.sfui-setting').classList.contains('sfui-i18n')) {
                    // Try to grab existing translated string
                    if (i18n_data === null) {
                        var p = (nodes[i].closest('[data-g="data"]') ? nodes[i].closest('[data-g="data"]') : nodes[i].closest('.super-tab-content'));
                        if (p.classList.contains('super-tab-content')) {
                            var i18n_input_field = p.querySelector('[name="i18n"]');
                        } else {
                            var i18n_input_field = p.nextElementSibling.querySelector('[name="i18n"]');
                        }
                        if(i18n_input_field){
                            var i18n_value = i18n_input_field.value.trim();
                            i18n_input_field.classList.remove('sfui-red');
                            if (i18n_value === '') {
                                var i18n_data = {};
                                i18n_data[i18n] = {};
                            } else {
                                try {
                                    var i18n_data = JSON.parse(i18n_value);
                                    var changed = false;
                                    Object.keys(i18n_data).forEach(function(key) {
                                        if (Array.isArray(i18n_data[key])) {
                                            i18n_data[key] = {};
                                            changed = true;
                                        }
                                    });
                                    if (changed) {
                                        i18n_input_field.value = JSON.stringify(i18n_data, undefined, 4);
                                    }
                                }
                                catch (e) {
                                    console.error(e);
                                    var i18n_data = {};
                                    i18n_data[i18n] = {};
                                    i18n_input_field.classList.add('sfui-red');
                                }
                            }
                        }
                    }
                    // tmp if (i18n_data !== null) {
                    // tmp     if (Array.isArray(i18n_data[i18n])) {
                    // tmp         i18n_data[i18n] = {};
                    // tmp     }
                    // tmp     if (i18n_data[i18n]) {
                    // tmp         var value = nodes[i].value;
                    // tmp         k = nodes[i].name.split('.').pop();
                    // tmp         if (!data[k]) {
                    // tmp             if (nodes[i].tagName === 'TEXTAREA' && tinymce.get(nodes[i].id)) {
                    // tmp                 data[k] = nodes[i].nextElementSibling.value;
                    // tmp                 const translatedValue = SUPER.ui.i18n.getTranslatedValue(nodes[i], i18n_data, i18n, tab);
                    // tmp                 if (translatedValue !== null) {
                    // tmp                     tinymce.get(nodes[i].id).setContent(translatedValue);
                    // tmp                 }
                    // tmp             } else {
                    // tmp                 data[k] = nodes[i].nextElementSibling.value;
                    // tmp                 if (nodes[i].name === 'i18n') {
                    // tmp                     data[k] = JSON.parse(data[k], undefined, 4);
                    // tmp                 }
                    // tmp                 const translatedValue = SUPER.ui.i18n.getTranslatedValue(nodes[i], i18n_data, i18n, tab);
                    // tmp                 if (translatedValue !== null) {
                    // tmp                     if (nodes[i].type === 'checkbox') {
                    // tmp                         nodes[i].checked = (translatedValue === 'true');
                    // tmp                     } else {
                    // tmp                         if (nodes[i].value !== translatedValue) {
                    // tmp                             // Re-load attachment image preview
                    // tmp                             if (nodes[i].parentNode.closest('.sfui-setting').classList.contains('sfui-type-files')) {
                    // tmp                                 nodes[i].value = translatedValue;
                    // tmp                                 SUPER.ui.i18n.reload_attachments(nodes[i]);
                    // tmp                             }
                    // tmp                         }
                    // tmp                         nodes[i].value = translatedValue;
                    // tmp                     }
                    // tmp                 }
                    // tmp             }
                    // tmp         }
                    // tmp     }
                    // tmp }
                } else {
                    var value = nodes[i].value;
                    var type = nodes[i].type;
                    k = nodes[i].name.split('.').pop();
                    if (type === 'checkbox') value = nodes[i].checked;
                    if (type === 'radio') value = (tab.querySelector('[name="' + nodes[i].name + '"]:checked') ? tab.querySelector('[name="' + nodes[i].name + '"]:checked').value : '');
                    if (value === true) value = "true";
                    if (value === false) value = "false";
                    if (nodes[i].tagName === 'TEXTAREA' && tinymce.get(nodes[i].id)) {
                        value = tinymce.get(nodes[i].id).getContent();
                    }
                    if (!data[k]) {
                        if(nodes[i].name === 'i18n' && value!==''){
                            try {
                                value = JSON.parse(value);
                            }
                            catch (e) {
                                console.error(e);
                                value = '{}';
                            }
                        }
                        data[k] = value;
                    }
                }
                continue;
            }
        }
        if (returnObj) {
            return data;
        }
    
        // Remove processed class
        tab = document.querySelector('.super-tab-content.super-tab-' + slug);
        nodes = tab.querySelectorAll('.sf-processed');
        for (i = 0; i < nodes.length; i++) {
            nodes[i].classList.remove('sf-processed');
        }
        if(SUPER.ui.i18n.translating && data.i18n){
            // Store i18n settings globally
            SUPER.ui.settings['_' + slug].i18n[i18n] = data.i18n[i18n];
        }else{
            // Store settings globally
            if((slug==='triggers' || slug==='stripe') && SUPER.ui.i18n.lastLanguage!=='' && SUPER.ui.i18n.lastLanguage!==SUPER.ui.i18n.mainLanguage){
                SUPER.ui.settings['_'+slug].i18n[SUPER.ui.i18n.lastLanguage] = data.i18n[SUPER.ui.i18n.lastLanguage];
                data = SUPER.ui.settings['_'+slug];
                // Adjust repeater items based on i18n_data
                var repeaters = tab.querySelectorAll('[data-r]');
                repeaters.forEach(function(repeater) {
                    var key = repeater.getAttribute('data-r');
                    if (data[key]) {
                        var repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                        var targetCount = data[key].length;
                        // Add missing items
                        while (repeaterItems.length < targetCount) {
                            SUPER.ui.btn(event, repeater.querySelector('.add-repeater-item-btn'), 'addRepeaterItem', true);
                            repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                        }
                        // Remove excess items
                        while (repeaterItems.length > targetCount) {
                            SUPER.ui.btn(event, repeaterItems[repeaterItems.length - 1].querySelector('.delete-repeater-item-btn'), 'deleteRepeaterItem', true);
                            repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                        }
                        // Restore values for main language
                        repeater.querySelectorAll('.sfui-repeater-item').forEach((item, index) => {
                            var itemValues = SUPER.ui.settings['_'+slug][key][index] || {};
                            SUPER.ui.setRepeaterFieldValues(item, itemValues);
                        });
                        
                        // repeater.querySelectorAll('.sfui-repeater-item').forEach((item, index) => {
                        //     var itemValues = SUPER.ui.settings['_'+slug][key][index] || {};
                        //     for (var fieldName in itemValues) {
                        //         if (itemValues.hasOwnProperty(fieldName)) {
                        //             var fieldValue = itemValues[fieldName];
                        //             if (typeof fieldValue === 'object' && fieldValue !== null) {
                        //                 // Handle nested objects
                        //                 var nestedElement = item.querySelector(`[data-g="${fieldName}"]`);
                        //                 if (nestedElement) {
                        //                     for (var nestedFieldName in fieldValue) {
                        //                         if (fieldValue.hasOwnProperty(nestedFieldName)) {
                        //                             var nestedField = nestedElement.querySelector(`[name="${nestedFieldName}"]`);
                        //                             if (nestedField) {
                        //                                 console.log(`Setting value for nested field: ${nestedFieldName} to ${fieldValue[nestedFieldName]}`);
                        //                                 nestedField.value = fieldValue[nestedFieldName];
                        //                             } else {
                        //                                 console.log(`Nested field not found: ${nestedFieldName}`);
                        //                             }
                        //                         }
                        //                     }
                        //                 } else {
                        //                     console.log(`Nested element not found for field: ${fieldName}`);
                        //                 }
                        //             } else {
                        //                 // Handle simple fields
                        //                 var field = item.querySelector(`[name="${fieldName}"]`);
                        //                 if (field) {
                        //                     console.log(`Setting value for field: ${fieldName} to ${fieldValue}`);
                        //                     field.value = fieldValue;
                        //                 } else {
                        //                     console.log(`Field not found: ${fieldName}`);
                        //                 }
                        //             }
                        //         }
                        //     }
                        // });
                    }
                });
            }else{
                SUPER.ui.settings['_' + slug] = data;
            }
        }
        // Return tab specific settings
        return data;
        //settings['_' + slug] = data;
        //return settings;
    };
//    SUPER.get_tab_settings = function(settings, slug, tab, data){
//        var nodes, i, translating = false, i18n_data = null;
//        var i18n = document.querySelector('.super-create-form').dataset.i18n;
//        if(i18n && i18n!=='' && (slug==='triggers' || slug==='woocommerce' || slug==='listings' || slug==='pdf' || slug==='stripe')){
//            // Translating...
//            translating = true;
//        }
//        var returnObj = false;
//        if(typeof data === 'undefined') {
//            data = {};
//        }else{
//            returnObj = true;
//        }
//        if(typeof tab === 'undefined'){
//            tab = document.querySelector('.super-tab-content.super-tab-'+slug);
//        }
//        if(!tab) {
//            return settings;
//        }
//        // Get the current country flag
//        var flag = document.querySelector(':scope .super-tabs > .super-tab-builder > .flag');
//        // Remember the original value for translatable settings
//        nodes = tab.querySelectorAll('.sfui-i18n [name]');
//        if(flag){
//            for(i=0; i<nodes.length; i++){
//                // Add the country flag next to the setting title to indicate translatable option
//                var title = nodes[i].closest('label').querySelector('.sfui-title');
//                if(title){
//                    var cloneFlag = flag.cloneNode();
//                    cloneFlag.title = 'Translation for '+(i18n ? i18n : 'main language');
//                    if(title.querySelector('.flag')) title.querySelector('.flag').remove();
//                    title.appendChild(cloneFlag);
//                }
//            }
//        }
//        if(translating){
//            for(i=0; i<nodes.length; i++){
//                // Skip if already exists
//                if(nodes[i].nextElementSibling && nodes[i].nextElementSibling.className==='sfui-original-i18n-value') {
//                    continue;
//                }
//                if(nodes[i].type==='textarea'){
//                    if(tinymce.get(nodes[i].id)) {
//                        var value = tinymce.get(nodes[i].id).getContent();
//                    }else{
//                        var value = nodes[i].value;
//                    }
//                    var field = document.createElement('textarea');
//                }else{
//                    var value = nodes[i].value;
//                    if(nodes[i].type==='checkbox'){
//                        value = nodes[i].checked ? 'true' : 'false';
//                    }
//                    var field = document.createElement('input');
//                    field.type = 'hidden';
//                }
//                field.value = value;
//                field.className = 'sfui-original-i18n-value';
//                field.style.display = 'none';
//                nodes[i].parentNode.insertBefore(field, nodes[i].nextSibling);
//            }
//        }else{
//            // Reset remembered original value for translatable settings
//            if(!$('.super-create-form').hasClass('super-translation-mode')){
//                SUPER.ui.i18n.restore_original_value();
//            }
//        }
//
//        // First grab all settings that are not inside a repeater element
//        var i, k, nodes = tab.querySelectorAll('[data-g], [data-r], [name]');
//        for(i=0; i<nodes.length; i++){
//            if(nodes[i].classList.contains('sf-processed')){
//                continue;
//            }
//            nodes[i].classList.add('sf-processed');
//            if(nodes[i].dataset.r){
//                var ri, repeaterItems = nodes[i].querySelectorAll(':scope > .sfui-repeater-item');
//                k = nodes[i].dataset.r;
//                for(ri=0; ri<repeaterItems.length; ri++){
//                    if(!data[k]) data[k] = [];
//                    if(!data[k][ri]) data[k][ri] = {};
//                    data[k][ri] = SUPER.get_tab_settings(settings, slug, repeaterItems[ri], data[k][ri]);
//                }
//                continue;
//            }
//            if(nodes[i].dataset.g){
//                // is group
//                k = nodes[i].dataset.g;
//                if(!data[k]) data[k] = {};
//                // Lookup any inner fields
//                data[k] = SUPER.get_tab_settings(settings, slug, nodes[i], data[k]);
//                continue;
//            }
//            if(nodes[i].name){
//                // is field
//                // first check if we are in translation mode
//                if(translating && nodes[i].closest('.sfui-setting').classList.contains('sfui-i18n')){
//                    // Try to grab existing translated string
//                    if(i18n_data===null){
//                        var p = (nodes[i].closest('[data-g="data"]') ? nodes[i].closest('[data-g="data"]') : nodes[i].closest('.super-tab-content'));
//                        if(p.classList.contains('super-tab-content')){
//                            var i18n_input_field = p.querySelector('[name="i18n"]');
//                        }else{
//                            var i18n_input_field = p.nextElementSibling.querySelector('[name="i18n"]');
//                        }
//                        var i18n_value = i18n_input_field.value.trim();
//                        i18n_input_field.classList.remove('sfui-red');
//                        if(i18n_value===''){
//                            var i18n_data = {};
//                            i18n_data[i18n] = {};
//                        }else{
//                            try{
//                                var i18n_data = JSON.parse(i18n_value);
//                                var changed = false;
//                                Object.keys(i18n_data).forEach(function(key){
//                                    if(Array.isArray(i18n_data[key])){
//                                        i18n_data[key] = {};
//                                        changed = true;
//                                    }
//                                });
//                                if(changed){
//                                    i18n_input_field.value = JSON.stringify(i18n_data, undefined, 4);
//                                }
//                            }
//                            catch(e){
//                                console.error(e);
//                                var i18n_data = {};
//                                i18n_data[i18n] = {};
//                                i18n_input_field.classList.add('sfui-red');
//                            }
//                        }
//                    }
//                    if(Array.isArray(i18n_data[i18n])){
//                        i18n_data[i18n] = {};
//                    }
//                    if (i18n_data[i18n]) {
//                        var value = nodes[i].value;
//                        k = nodes[i].name.split('.').pop();
//                        if (!data[k]) {
//                          if (nodes[i].tagName === 'TEXTAREA' && tinymce.get(nodes[i].id)) {
//                            data[k] = nodes[i].nextElementSibling.value;
//                            const translatedValue = SUPER.ui.i18n.getTranslatedValue(nodes[i], i18n_data, i18n, tab);
//                            if (translatedValue !== null) {
//                              tinymce.get(nodes[i].id).setContent(translatedValue);
//                            }
//                          } else {
//                            data[k] = nodes[i].nextElementSibling.value;
//                            if (nodes[i].name === 'i18n') {
//                              data[k] = JSON.parse(data[k], undefined, 4);
//                            }
//                            const translatedValue = SUPER.ui.i18n.getTranslatedValue(nodes[i], i18n_data, i18n, tab);
//                            if (translatedValue !== null) {
//                              if (nodes[i].type === 'checkbox') {
//                                nodes[i].checked = (translatedValue === 'true');
//                              } else {
//                                if (nodes[i].value !== translatedValue) {
//                                  // Re-load attachment image preview
//                                  if (nodes[i].parentNode.closest('.sfui-setting').classList.contains('sfui-type-files')) {
//                                    nodes[i].value = translatedValue;
//                                    SUPER.ui.i18n.reload_attachments(nodes[i]);
//                                  }
//                                }
//                                nodes[i].value = translatedValue;
//                              }
//                            }
//                          }
//                        }
//                    }
//                    // tmp if(i18n_data[i18n] && i18n_data[i18n][nodes[i].name]){
//                    // tmp     // If a translated version exists
//                    // tmp     var value = nodes[i].value;
//                    // tmp     k = nodes[i].name.split('.').pop();
//                    // tmp     if(!data[k]){
//                    // tmp         if(nodes[i].tagName==='TEXTAREA' && tinymce.get(nodes[i].id)){
//                    // tmp             data[k] = nodes[i].nextElementSibling.value;
//                    // tmp             tinymce.get(nodes[i].id).setContent(i18n_data[i18n][nodes[i].name]);
//                    // tmp         }else{
//                    // tmp             data[k] = nodes[i].nextElementSibling.value;
//                    // tmp             if(nodes[i].name==='i18n'){
//                    // tmp                 data[k] = JSON.parse(data[k], undefined, 4);
//                    // tmp             }
//                    // tmp             if(nodes[i].type==='checkbox'){
//                    // tmp                 nodes[i].checked = (i18n_data[i18n][nodes[i].name]==='true' ? true : false);
//                    // tmp             }else{
//                    // tmp                 if(nodes[i].value!==i18n_data[i18n][nodes[i].name]){
//                    // tmp                     // Re-load attachment image preview
//                    // tmp                     if(nodes[i].parentNode.closest('.sfui-setting').classList.contains('sfui-type-files')){
//                    // tmp                         nodes[i].value = i18n_data[i18n][nodes[i].name];
//                    // tmp                         SUPER.ui.i18n.reload_attachments(nodes[i]);
//                    // tmp                     }
//                    // tmp                 }
//                    // tmp                 nodes[i].value = i18n_data[i18n][nodes[i].name];
//                    // tmp             }
//                    // tmp         }
//                    // tmp     }
//                    // tmp }
//                }else{
//                    var value = nodes[i].value;
//                    var type = nodes[i].type;
//                    k = nodes[i].name.split('.').pop();
//                    if(type==='checkbox') value = nodes[i].checked;
//                    if(type==='radio') value = (tab.querySelector('[name="'+nodes[i].name+'"]:checked') ? tab.querySelector('[name="'+nodes[i].name+'"]:checked').value : '');
//                    if(value===true) value = "true"; 
//                    if(value===false) value = "false"; 
//                    if(nodes[i].tagName==='TEXTAREA' && tinymce.get(nodes[i].id)){
//                        value = tinymce.get(nodes[i].id).getContent();
//                    }
//                    if(!data[k]) {
//                        if(nodes[i].name==='i18n'){
//                            if(value==='' || value==='[]'){
//                                value = '{}';
//                            }else{
//                                try{
//                                    value = JSON.parse(value);
//                                    var changed = false;
//                                    Object.keys(value).forEach(function(key){
//                                        if(Array.isArray(value[key])){
//                                            value[key] = {};
//                                            changed = true;
//                                        }
//                                    });
//                                    if(changed){
//                                        value = JSON.stringify(value, undefined, 4);
//                                    }
//                                }
//                                catch(e){
//                                    console.error(e);
//                                    value = '{}';
//                                }
//                            }
//                            if(typeof value!=='object'){
//                                value = JSON.parse(value, undefined, 4);
//                            }
//                        }
//                        data[k] = value;
//                    }
//                }
//                continue;
//            }
//        }
//        if(returnObj) return data;
//
//        // Remove processed class
//        tab = document.querySelector('.super-tab-content.super-tab-'+slug);
//        nodes = tab.querySelectorAll('.sf-processed');
//        for(i=0; i<nodes.length; i++){
//            nodes[i].classList.remove('sf-processed');
//        }
//        // Return settings
//        settings['_'+slug] = data;
//        return settings;
//    }
    SUPER.get_emails_settings = function(string, returnData){
        if(typeof string === 'undefined') string = false;
        if(typeof returnData === 'undefined') returnData = false;
        var $s = SUPER.get_tab_settings({}, 'emails', undefined, undefined, returnData);
        $s = $s['emails'];
        if(string===true) {
            if(!isEmpty($s)) return JSON.stringify($s, undefined, 4);
            return '';
        }
        return $s;
    };
    SUPER.get_theme_settings = function(string, returnData){
        if(typeof string === 'undefined') string = false;
        if(typeof returnData === 'undefined') returnData = false;
        var $s = SUPER.get_tab_settings({}, 'theme', undefined, undefined, returnData);
        if(string===true) {
            if(!isEmpty($s)) return JSON.stringify($s, undefined, 4);
            return '';
        }
        return $s;
    };
    SUPER.get_trigger_settings = function(string, returnData){
        if(typeof string === 'undefined') string = false;
        if(typeof returnData === 'undefined') returnData = false;
        var $s = SUPER.get_tab_settings({}, 'triggers', undefined, undefined, returnData);
        $s = $s['triggers'];
        if(string===true) {
            if(!isEmpty($s)) return JSON.stringify($s, undefined, 4);
            return '';
        }
        return $s;
    };
    SUPER.get_woocommerce_settings = function(string){
        if(typeof string === 'undefined') string = false;
        var $s = SUPER.get_tab_settings({}, 'woocommerce');
        if(string===true) {
            if(!isEmpty($s)) return JSON.stringify($s, undefined, 4);
            return '';
        }
        return $s;
    };
    SUPER.get_listings_settings = function(string){
        if(typeof string === 'undefined') string = false;
        var $s = SUPER.get_tab_settings({}, 'listings');
        if(string===true) {
            if(!isEmpty($s)) return JSON.stringify($s, undefined, 4);
            return '';
        }
        return $s;
    };
    SUPER.get_pdf_settings = function(string, returnData){
        if(typeof string === 'undefined') string = false;
        if(typeof returnData === 'undefined') returnData = false;
        var $s = SUPER.get_tab_settings({}, 'pdf', undefined, undefined, returnData);
        if(string===true) {
            if(!isEmpty($s)) return JSON.stringify($s, undefined, 4);
            return '';
        }
        return $s;
    };
    SUPER.get_stripe_settings = function(string, returnData){
        if(typeof string === 'undefined') string = false;
        if(typeof returnData === 'undefined') returnData = false;
        var $s = SUPER.get_tab_settings({}, 'stripe', undefined, undefined, returnData);
        if(string===true) {
            if(!isEmpty($s)) return JSON.stringify($s, undefined, 4);
            return '';
        }
        return $s;
    };
    SUPER.get_translation_settings = function(string){
        if(typeof string === 'undefined') string = false;
        var $translations = {};
        $('.super-translations-list > li:not(:first)').each(function () {
            // Validate
            var $row = $(this),
                $language = $row.find('.super-dropdown[data-name="language"] .super-active'),
                $flag = $row.find('.super-dropdown[data-name="flag"] .super-active'),
                $rtl = $row.find('.super-rtl').hasClass('super-active');
            $row.find('.super-dropdown[data-name="language"], .super-dropdown[data-name="flag"]').removeClass('super-error');
            if (!$language.length || !$flag.length) {
                if (!$language.length)
                    $row.find('.super-dropdown[data-name="language"]').addClass('super-error');
                if (!$flag.length)
                    $row.find('.super-dropdown[data-name="flag"]').addClass('super-error');
                return false;
            }
            var $i18n = $language.attr('data-value');
            if (typeof $translations[$i18n] !== 'undefined') {
                $row.find('.super-dropdown[data-name="language"]').addClass('super-error');
                return false;
            }
            // Add language to object
            $language = $language.html();
            $flag = $flag.attr('data-value');
            $translations[$i18n] = {
                language: $language,
                flag: $flag,
                rtl: $rtl
            };
        });
        if(string===true) {
            if(!isEmpty($translations)) return JSON.stringify($translations, undefined, 4);
            return '';
        }
        return $translations;
    };

    SUPER.backend_setting_changed = function (field, value = undefined) {
        var replace,
            regex,
            str,
            m,
            counter;

        if (typeof value === 'undefined') value = field.val();
        // Update element style
        var input = field.parents('.super-field-input');
        if (typeof input !== 'undefined') {
            var _styles = field.parents('.super-field-input').data('styles');
            if (typeof _styles !== 'undefined') {
                Object.keys(_styles).forEach(function (selector) {
                    var editing = $('.super-preview-elements .super-element.editing'),
                        style = editing.children('.super-element-inner').children('style'),
                        shortcode = editing.children('.super-element-inner').children('.super-shortcode'),
                        identifier = shortcode.attr('id'),
                        properties = _styles[selector].split(',');
                    Object.keys(properties).forEach(function (pk) {
                        var property = properties[pk];
                        var suffix = '';
                        // Convert to proper justify-content
                        if (property == 'justify-content') {
                            if (value == 'left') value = 'flex-start';
                            if (value == 'center') value = 'center';
                            if (value == 'right') value = 'flex-end';
                        }
                        // In some cases we need to add "px", for instance with font size and line-height
                        if ((property == 'font-size') || (property == 'line-height') || (property.indexOf('margin-') != -1) || (property.indexOf('-radius') != -1) || (property.indexOf('-width') != -1)) {
                            suffix = 'px';
                            value = value.replace('px', ''); // Remove px from value if it contains any
                        }
                        // Only add style if value is not 0 or empty
                        if (value == '' || value == 0) {
                            // Otherwise try to delete the style if it exists
                            value = value + suffix;
                            if (typeof style !== 'undefined' && typeof style[0] !== 'undefined') {
                                // Find all possible matches based on the unique identifier
                                // When match is found, compare and look for identical Selector, Property and Value
                                // The star operator * needs to be escaped, or must be inside []
                                replace = "#" + identifier + selector.replace("*", "\\*") + " {" + property + ": (.*?)!important;}";
                                regex = new RegExp(replace, "gm");
                                str = style[0].innerHTML;
                                m;
                                while ((m = regex.exec(str)) !== null) {
                                    // This is necessary to avoid infinite loops with zero-width matches
                                    if (m.index === regex.lastIndex) {
                                        regex.lastIndex++;
                                    }
                                    // Remove this rule from the styles
                                    style[0].innerHTML = style[0].innerHTML.replace(m[0], '');
                                }
                            } else {
                                // Doesn't exist, do nothing
                            }
                        } else {
                            value = value + suffix;
                            if (typeof style !== 'undefined' && typeof style[0] !== 'undefined') {
                                // Find all possible matches based on the unique identifier
                                // When match is found, compare and look for identical Selector, Property and Value
                                // The star operator * needs to be escaped, or must be inside []
                                replace = "#" + identifier + selector.replace("*", "\\*") + " {" + property + ": (.*?)!important;}";
                                regex = new RegExp(replace, "gm");
                                str = style[0].innerHTML;
                                m;
                                counter = 0;
                                while ((m = regex.exec(str)) !== null) {
                                    // This is necessary to avoid infinite loops with zero-width matches
                                    if (m.index === regex.lastIndex) {
                                        regex.lastIndex++;
                                    }
                                    // Check if selector, property and value are identical
                                    if (m[1] != value) {
                                        style[0].innerHTML = style[0].innerHTML.replace(m[0], '#' + identifier + selector + ' {' + property + ': ' + value + '!important;}');
                                    }
                                    counter++;
                                }
                                if (counter == 0) {
                                    // Style does not exists yet, let's add it
                                    style[0].innerHTML = style[0].innerHTML + '\n#' + identifier + selector + ' {' + property + ': ' + value + '!important;}';
                                }
                            } else {
                                // Doesn't exist, create new style
                                var node = document.createElement('style');
                                node.id = 'style-' + identifier;
                                node.innerHTML = '#' + identifier + selector + ' {' + property + ': ' + value + '!important;}';
                                $(node).insertBefore(shortcode);
                            }
                        }
                    });

                });
            }
        }
    };

    // Loading States
    SUPER.loading_states = function (button, status) {
        status = status || 'loading';
        if (status == 'loading') {
            var oldHtml = button.html();
            button.data('old-html', oldHtml);
            button.parents('.super-form-button:eq(0)').addClass('super-loading');
        } else {
            button.parents('.super-form-button:eq(0)').removeClass('super-loading');
            button.html(button.data('old-html'));
        }
    };

    // Check if the added field has an unique field name
    SUPER.check_for_unique_field_name = function ($element, dragDrop) {
        if(typeof dragDrop === 'undefined') dragDrop = false;
        var i, nodes = $element.querySelectorAll('.super-shortcode-field');
        for(i=0; i<nodes.length; i++){
            var field = nodes[i];
            if (typeof field.name !== 'undefined') {
                var elementWrapper = field.closest('.super-element');
                if(field.classList.contains('super-fileupload')){
                    field = elementWrapper.querySelector('.super-active-files');
                }
                var name = field.name; // strip levels from field name if any
                var form = field.closest('.super-preview-elements');
                if(SUPER.field(form, name)){
                    var elementWrapperInput = elementWrapper.querySelector('.super-title > input');
                    if(!elementWrapperInput) {
                        // Skip if there is no field name (for instance for the MailChimp element)
                        continue;
                    }
                    var uniqueName = SUPER.generate_unique_field_name(field, name, undefined, undefined, dragDrop);
                    // Now set the new field name for this element
                    elementWrapperInput.value = uniqueName;
                    field.name = uniqueName;
                    var elementDataField = elementWrapper.querySelector('textarea[name="element-data"]');
                    var elementData = JSON.parse(elementDataField.value);
                    elementData.name = uniqueName;
                    elementData = JSON.stringify(elementData);
                    elementDataField.value = elementData;
                }
            }
        }
    };

    // Generate unique field name for a given element
    SUPER.generate_unique_field_name = function (field, name, newName, counter, dragDrop) {
        if(typeof newName === 'undefined') newName = name;
        if(typeof counter === 'undefined') counter = 1;
        if(typeof dragDrop === 'undefined') dragDrop = false;
        var form = document.querySelector('.super-preview-elements'),
            fields = SUPER.field(form, newName, 'all'),
            levels = SUPER.get_dynamic_column_depth(fields[0]);

        // Lookup field name after levels are added
        if(counter>1){
            field.name = name+'_'+counter+levels;
        }else{
            if(dragDrop){
                name = name.split('[')[0]; // strip levels from field name if any
                field.name = name+levels;
                newName = name;
            }else{
                field.name = newName+levels;
            }
        }
        fields = SUPER.field(form, newName+levels, 'all');
        if (fields.length > 1) {
            counter++;
            newName = field.name;
            return SUPER.generate_unique_field_name(field, name, newName, counter, dragDrop);
        } else {
            if(field.closest('.super-element').classList.contains('editing')){
                var settingsName = document.querySelector('.super-element.super-element-settings input[name="name"]');
                if(settingsName){
                    settingsName.value = field.name;
                }
            }
            return field.name;
        }
    };

    // Regenerate Element Final Output (inner)
    SUPER.regenerate_element_inner = function($history, $whatToUpdate){
        if (typeof $history === 'undefined') $history = true;
        if (typeof $whatToUpdate === 'undefined') $whatToUpdate = [
            "form_settings",
            "emails_settings",
            "theme_settings",
            "trigger_settings",
            "listings_settings",
            "pdf_settings",
            "stripe_settings",
            "translation_settings"
        ];
        var $elements, $old_code;
        SUPER.set_session_data('_super_builder_has_unsaved_changes', 'true');
        $old_code = document.querySelector('.super-raw-code-form-elements > textarea').value;
        $elements = SUPER.get_form_elements(true);
        document.querySelector('.super-raw-code-form-elements > textarea').value = $elements;
        if ($history) SUPER.trigger_redo_undo($elements, $old_code);
        SUPER.init_common_fields();
        const settingsMap = {
            form_settings: () => SUPER.update_form_settings(true),
            emails_settings: () => SUPER.update_emails_settings(true),
            theme_settings: () => SUPER.update_theme_settings(true),
            trigger_settings: () => SUPER.update_trigger_settings(true),
            listings_settings: () => SUPER.update_listings_settings(true),
            pdf_settings: () => SUPER.update_pdf_settings(true),
            stripe_settings: () => SUPER.update_stripe_settings(true),
            translation_settings: () => SUPER.update_translation_settings(true),
        };
        $whatToUpdate.forEach(key => {
            if (settingsMap[key]) {
                settingsMap[key](); // Call the corresponding method
            } else {
                console.warn(`Unknown setting: ${key}`);
            }
        });
    };
    SUPER.regenerate_element_inner.get_elements = function ($target) {
        if(typeof $target === 'undefined') $target = $('.super-preview-elements');
        var $elements = [];
        $target.children('.super-element').each(function () {
            var $this = $(this);
            var $tag = $this.data('shortcode-tag');
            var $group = $this.data('group');
            var $data = $.parseJSON($this.children('textarea[name="element-data"]').val());
            if ($data === null) $data = {};
            if (typeof $this.attr('data-minimized') !== 'undefined') {
                if ($this.attr('data-minimized') == 'no') {
                    if (typeof $data.minimized !== 'undefined') {
                        delete $data.minimized;
                    }
                } else {
                    $data.minimized = $this.attr('data-minimized');
                }
            }
            if ($tag == 'column') {
                var $size = $this.attr('data-size');
                if ($size != '1/1') {
                    $data.size = $size;
                } else {
                    if (typeof $data.size !== 'undefined') {
                        delete $data.size;
                    }
                }
            }
            var $push = {};
            $push.tag = $tag;
            $push.group = $group;

            // If this is a Column or Multi-part element also add the inner elements to the data object
            // The TABS element will also be included, but will be handled differently due to the fact it has multiple "drop area" elements (each for one TAB)
            if (($tag == 'column') || ($tag == 'multipart') || ($tag == 'tabs')) {
                var $inner = SUPER.regenerate_element_inner_children($this, $tag);
                $push.inner = $inner;
            }

            // Delete empty values
            Object.keys($data).forEach(function (key) {
                if ($data[key] === null) {
                    delete $data[key];
                }
            });
            if (Object.keys($data).length !== 0 && $data.constructor === Object) {
                $push.data = $data;
            }

            $elements.push($push);

        });
        return $elements;
    };

    // Also collect all inner items
    SUPER.regenerate_element_inner_children = function ($target, $tag) {
        var tabs_inner,
            inner;

        // If this is a TAB element loop over all the TAB "drop area" elements
        if ($tag == 'tabs') {

            // Since this is a TAB element we will need to determine it's Layout (tabs, accordion, list)
            // This is required because each layout have different classNames, and thus will be handled by a different selector
            // First grab the shortcode which will be the element itself
            // This way we can loop through each TAB items e.g (TABs, Accordions, Lists) which will hold the inner elements which we want to retrieve
            var $shortcode = $target.children('.super-element-inner').children('.super-shortcode.super-tabs');

            // If TABs layout
            if ($shortcode.hasClass('super-layout-tabs')) {
                tabs_inner = $shortcode.children('.super-tabs-contents').children('.super-tabs-content').children('.super-padding').children('.super-element-inner.super-dropable');
            }
            // If Accordion layout
            if ($shortcode.hasClass('super-layout-accordion')) {
                tabs_inner = $shortcode.children('.super-accordion-item').children('.super-accordion-content').children('.super-padding').children('.super-element-inner.super-dropable');
            }
            // Check if there are any inner elements
            if (typeof tabs_inner !== 'undefined' && tabs_inner.length) {
                inner = [];
                tabs_inner.each(function () {
                    inner.push(SUPER.regenerate_element_inner.get_elements($(this)));
                });
                return inner;
            } else {
                return '';
            }

        } else {
            $target = $target.children('.super-element-inner');
            if ($target.children('.super-element').length) {
                return SUPER.regenerate_element_inner.get_elements($target);
            } else {
                return '';
            }
        }
    };

    // Re initialize drop here placeholder (image)
    SUPER.init_drop_here_placeholder = function () {
        $('.super-preview-elements').addClass('drop-here-placeholder');
        SUPER.init_drag_and_drop();
    };

    // Initialize elements so they can be sortable
    SUPER.init_drag_and_drop = function () {
        $('.super-preview-elements').sortable({
            scroll: false,
            scrollSensitivity: 100,
            opacity: 0.8,
            forcePlaceholderSize: true,
            forceHelperSize: true,
            items: ".super-element:not(.sfui-notice)",
            connectWith: ".super-preview-elements > .super-element, .super-preview-elements > .super-element .super-element-inner",
            stop: function (event, ui) {
                var $tag = ui.item.data('shortcode-tag');
                var $parent_tag = ui.item.parents('.super-element:eq(0)').data('shortcode-tag');
                if (typeof $parent_tag !== 'undefined') {
                    if (($tag === 'multipart_pre') && ($tag == $parent_tag)) {
                        alert(super_create_form_i18n.alert_multipart_error);
                        return false;
                    }
                }
                SUPER.check_for_unique_field_name(ui.item[0], true);
                SUPER.init_drop_here_placeholder();
                SUPER.regenerate_element_inner(true, []); //["form_settings", "trigger_settings", "listings_settings", "pdf_settings", "stripe_settings", "translation_settings"]);
            }
        });
        var $target = $('.super-preview-elements .super-element.super-column > .super-element-inner.super-dropable, .super-preview-elements .super-element.super-multipart > .super-element-inner.super-dropable, .super-preview-elements .super-element.super-tabs .super-element-inner.super-dropable');
        $target.sortable({
            scroll: false,
            scrollSensitivity: 100,
            opacity: 0.8,
            forcePlaceholderSize: true,
            forceHelperSize: true,
            connectWith: ".super-preview-elements, .super-preview-elements > .super-element .super-element-inner",
            stop: function (event, ui) {
                var $tag = ui.item.data('shortcode-tag');
                var $parent_tag = ui.item.parents('.super-element:eq(0)').data('shortcode-tag');
                if (typeof $parent_tag !== 'undefined') {
                    if (($tag === 'multipart_pre') && ($tag == $parent_tag)) {
                        alert(super_create_form_i18n.alert_multipart_error);
                        return false;
                    }
                }
                SUPER.check_for_unique_field_name(ui.item[0], true);
                SUPER.init_drop_here_placeholder();
                SUPER.regenerate_element_inner(true, []); //["form_settings", "trigger_settings", "listings_settings", "pdf_settings", "stripe_settings", "translation_settings"]);
            }
        });
    };

    // Scroll function when dropable or sortable element is activated
    SUPER.handleNear = function () {
        var $scrolled = $(window).scrollTop();
        var $buffer = 20;
        var $docHeight = $(document).outerHeight(true);
        var $windowHeight = $(window).outerHeight(true);
        var $near_top = $scrolled - this.ev.y >= $buffer;
        var $near_bottom = $scrolled + $windowHeight - this.ev.y <= $buffer;
        if ($near_top) {
            window.scrollTo(0, $scrolled - $buffer);
        }
        if (($near_bottom) && ((this.ev.y + $buffer) < $docHeight)) {
            window.scrollTo(0, this.ev.y + $buffer - $windowHeight);
        }
    };

    SUPER.init_previously_created_fields = function () {

        var $options = {};
        $('.super-preview-elements .super-element').each(function () {
            var $data = $(this).find('textarea[name="element-data"]').val();
            $data = JSON.parse($data);
            // Skip element if data is null
            if ($data !== null) {
                var $name = $data.name;
                var $email = $data.email;
                if (typeof $name !== 'undefined') {
                    if (typeof $email === 'undefined') {
                        $email = $name;
                    }
                    $options[$name] = {
                        selected: '<option selected="selected" value="' + $name + '">' + $name + ': ' + $email + '</option>',
                        default: '<option value="' + $name + '">' + $name + ': ' + $email + '</option>'
                    };
                }
            }
        });


        $('.super-multi-items .super-previously-created, .previously-created-fields').each(function () {
            var $this = $(this),
                $options_html,
                $value = $this.data('value');
            if ($this.parent().hasClass('address-auto-popuplate-item')) {
                $options_html = '<option value="">- select a field -</option>';
            } else {
                $options_html = '';
            }
            $.each($options, function (key, value) {
                if ($value == key) {
                    $options_html += value.selected;
                } else {
                    $options_html += value.default;
                }
            });
            $this.html($options_html);
        });

        $('.super-element-settings .super-elements-container select[name="connected_min"]').each(function () {
            var $this = $(this);
            var $current = $('.super-element.editing').find('.super-shortcode-field');
            var $value = $current.attr('data-connected-min');
            var $options_html = '';
            $.each($options, function (key, value) {
                var $found = $this.find('option[value="' + key + '"]').length;
                if ($found === 0) {
                    if ($value == key) {
                        $options_html += value.selected;
                    } else {
                        $options_html += value.default;
                    }
                }
            });
            $options_html = '<option value="">- Not connected -</option>' + $options_html;
            $this.html($options_html);
        });
        $('.super-element-settings .super-elements-container select[name="connected_max"]').each(function () {
            var $this = $(this);
            var $current = $('.super-element.editing').find('.super-shortcode-field');
            var $value = $current.attr('data-connected-max');
            var $options_html = '';
            $.each($options, function (key, value) {
                var $found = $this.find('option[value="' + key + '"]').length;
                if ($found === 0) {
                    if ($value == key) {
                        $options_html += value.selected;
                    } else {
                        $options_html += value.default;
                    }
                }
            });
            $options_html = '<option value="">- Not connected -</option>' + $options_html;
            $this.html($options_html);
        });
    };

    SUPER.update_multi_items = function () {
        var $error = false,
            $items,
            $this,
            $parent,
            $field_name,
            $field,
            $input_fields;

        $('.super-element-settings .super-elements-container .multi-items-json').each(function () {
            $items = [];
            $this = $(this);
            $parent = $this.parents('.super-field-input:eq(0)');
            $field_name = $this.parents('.super-elements-container:eq(0)').find('input[name="name"]').val();

            // Only proceed if not hidden
            $field = $this.parents('.super-field:eq(0)');
            if ($field.hasClass('super-hidden')) return true;

            // Loop over all the items
            $parent.find('.super-multi-items').each(function () {
                $this = $(this);
                if ($this.hasClass('super-conditional-item')) {
                    // Check if any of the conditional settings are pointing to it's own field
                    // This is not allowed
                    $input_fields = $this.children('input[name="conditional_field"], input[name="conditional_value"], input[name="conditional_field_and"], input[name="conditional_value_and"], textarea[name="conditional_new_value"]');
                    $.each($input_fields, function (key, field) {
                        // Conditional logic `field` may not point to it's own field
                        if(field.name==='conditional_field' || field.name==='conditional_field_and'){
                            if (field.value.indexOf($field_name) !== -1) {
                                $error = true;
                                field.classList.add('super-error');
                                return false;
                            } else {
                                field.classList.remove('super-error');
                            }
                        }
                        // Conditional logic `value` can not compare against it's own field name...
                        if(field.name==='conditional_value' || field.name==='conditional_value_and'){
                            if ( (field.value.indexOf('{'+$field_name+'}') !== -1) || (field.value.indexOf('{'+$field_name+';') !== -1) ) {
                                $error = true;
                                field.classList.add('super-error');
                                return false;
                            } else {
                                field.classList.remove('super-error');
                            }
                        }
                    });
                    // Add the conditions to the object so we can store it later 
                    $items.push({
                        field: $this.children('input[name="conditional_field"]').val(),
                        logic: $this.children('select[name="conditional_logic"]').val(),
                        value: $this.children('input[name="conditional_value"]').val(),
                        and_method: $this.children('select[name="conditional_and_method"]').val(),
                        field_and: $this.children('input[name="conditional_field_and"]').val(),
                        logic_and: $this.children('select[name="conditional_logic_and"]').val(),
                        value_and: $this.children('input[name="conditional_value_and"]').val(),
                        new_value: $this.children('textarea[name="conditional_new_value"]').val()
                    });
                } else if ($this.hasClass('address-auto-popuplate-item')) {
                    $items.push({
                        key: $this.children('input[name="key"]').val(),
                        field: $this.children('select[name="field"]').val(),
                        type: $this.children('select[name="type"]').val()
                    });
                } else {
                    if ($this.hasClass('super-tab-item')) {
                        // If we are in translation mode do not update image data
                        if ($('.super-create-form').hasClass('super-translation-mode')) {
                            $items.push({
                                title: $this.children('input[name="title"]').val(),
                                desc: $this.children('textarea[name="desc"]').val()
                            });
                        } else {
                            $items.push({
                                title: $this.children('input[name="title"]').val(),
                                desc: $this.children('textarea[name="desc"]').val(),
                                image: $this.find('input[name="image"]').val(),
                                max_width: $this.find('input[name="max_width"]').val(),
                                max_height: $this.find('input[name="max_height"]').val()
                            });
                        }
                    } else {
                        var $checked;
                        if ($this.children('input[type="checkbox"]').length) {
                            $checked = $this.children('input[type="checkbox"]').is(':checked');
                        }
                        if ($this.children('input[type="radio"]').length) {
                            $checked = $this.children('input[type="radio"]').is(':checked');
                        }
                        if ($('.super-create-form').hasClass('super-translation-mode')) {
                            $items.push({
                                label: $this.children('input[name="label"]').val()
                            });
                        } else {
                            // @since 6.4.013 - should not contain any comma's because SF stores it as a comma separated list in the database, which would otherwise cause issues when populating the form data at a later point into the form
                            var value = $this.children('input[name="value"]').val().split(',').join('');
                            $items.push({
                                checked: $checked,
                                image: $this.find('input[name="image"]').val(),
                                max_width: $this.find('input[name="max_width"]').val(),
                                max_height: $this.find('input[name="max_height"]').val(),
                                label: $this.children('input[name="label"]').val(),
                                value: value 
                            });
                            $this.children('input[name="value"]').val(value);
                        }
                    }
                }
            });
            $items = JSON.stringify($items);
            $parent.children('textarea').val($items);
        });
        // Make correct TAB with the error active/visible
        // Scroll to the error so that the setting will be in the viewport
        if ($error === true) {
            var $first_error = $('.super-element-settings .super-error:eq(0)');
            if ($first_error.length) {
                var topPos = $first_error[0].offsetTop + ($first_error[0].offsetParent ? $first_error[0].offsetParent.offsetTop : 0);
                $parent = $first_error.parents('.tab-content:eq(0)');
                // Make this tab active
                $parent.parents('.super-elements-container:eq(0)').find('.tab-content').removeClass('super-active');
                $parent.addClass('super-active');
                $parent.parents('.super-elements-container:eq(0)').find('.super-element-settings-tabs > select').val($parent.index() - 1);
                $parent[0].scrollTop = topPos;
                return false;
            }
        }
        return true;
    };

    SUPER.init_dragable_elements = function () {
        $('.draggable-element').pep({
            activeClass: 'super-active',
            droppableActiveClass: 'dropping-allowed',
            droppable: '.super-dropable',
            start: function (ev, obj) {
                SUPER.init_drop_here_placeholder();
                obj.noCenter = true;
                var top = obj.$el.css('top').replace('px', '');
                var left = obj.$el.css('left').replace('px', '');
                if (typeof obj.$el.attr('data-start-position-top') === 'undefined') {
                    obj.$el.attr('data-start-position-top', top);
                    obj.$el.attr('data-start-position-left', left);
                }
            },
            drag: function () {
                SUPER.handleNear.apply(this);
            },
            stop: function (ev, obj) {
                if (this.activeDropRegions.length > 0) {
                    var $tag = obj.$el.data('shortcode');
                    var $target = $('.dropping-allowed:not(:has(.dropping-allowed))');

                    // Check if user tries to drop a multi-part inside another multi-part
                    var $multipart_found = $target.closest('[data-shortcode-tag="multipart"]').length;
                    if ((($multipart_found > 0) && ($tag === 'multipart_pre'))) {
                        alert(super_create_form_i18n.alert_multipart_error);
                        return false;
                    }
                    var $predefined = '';
                    if (typeof obj.$el.find('.predefined').val() !== 'undefined') {
                        $predefined = JSON.parse(obj.$el.find('.predefined').val());
                    }

                    var xhttp = new XMLHttpRequest();
                    xhttp.onreadystatechange = function () {
                        if (this.readyState == 4) {
                            if (this.status == 200) {
                                // Success:
                                var $elements = $(this.responseText).appendTo($target);
                                SUPER.init_resize_element_labels();
                                $elements.each(function(){
                                    SUPER.check_for_unique_field_name(this);
                                });
                                SUPER.regenerate_element_inner(true, []); //["form_settings", "trigger_settings", "listings_settings", "pdf_settings", "stripe_settings", "translation_settings"]);
                                SUPER.init_drop_here_placeholder();
                            }
                            // Complete:
                        }
                    };
                    xhttp.onerror = function () {
                        console.log(this);
                        console.log("** An error occurred during the transaction");
                    };
                    xhttp.open("POST", ajaxurl, true);
                    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
                    var params = {
                        action: 'super_get_element_builder_html',
                        tag: obj.$el.data('shortcode'),
                        group: obj.$el.data('group'),
                        predefined: $predefined,
                        form_id: $('.super-create-form input[name="form_id"]').val()
                    };
                    params = $.param(params);
                    xhttp.send(params);
                } else {
                    obj.cssX = 0;
                    obj.cssY = 0;
                    obj.translation = "matrix(1, 0, 0, 1, 0, 0)";
                    obj.transform(obj.translation);
                    obj.$el.css('top', '0').css('left', '0');
                }
            },
            revert: true,
            cssEaseDuration: 0,
        });
    };

    SUPER.before_save_form = function(callback){
        var save = false;
        if(SUPER.alertWhenSaving){
            // Tell user that she will be overriding other changes made to this form...
            var reload = confirm(super_create_form_i18n.override_newer_version_found);
            if(reload === true){
                // Save the form, override other version
                save = true;
            }else{
                // Otherwise cancel this action
            }
        }else{
            // Save form
            save = true;
        }
        if(save){
            if (typeof callback === "function") { 
                SUPER.alertWhenSaving = false;
                document.querySelector('.super-create-form .super-actions .super-save').innerHTML = '<i class="fas fa-save"></i>Saving...';
                if(typeof tinymce !== 'undefined' && tinymce.triggerSave){
                    tinymce.triggerSave();
                }
                callback(); // safe to trigger callback
                return false;
            }
        }
    };
    SUPER.save_form = function ($this, $method, $button, $initial_i18n, callback, updatingRawFormCode) {
        var i,ii,
            form = document.querySelector('.super-preview-elements'),
            fields = SUPER.fields(form, '.super-shortcode-field, .super-active-files'),            
            fieldsWithError = SUPER.fields(form, '.super-element.error'),         
            error = false,
            duplicateFields,
            allowDuplicateNames = document.querySelector('input[name="allow_duplicate_names"]').checked;

        if(typeof updatingRawFormCode === 'undefined') updatingRawFormCode = false;

        // First remove all duplicate name errors
        for (i = 0; i < fieldsWithError.length; ++i) {
            fieldsWithError[i].classList.remove('error');
        }

        // @since 4.0.0 - see if we need to skip this validation when user choose to disable validation check on unique field names
        if (allowDuplicateNames===false) {
            for (i = 0; i < fields.length; ++i) {
                duplicateFields = SUPER.fieldsByName(document.querySelector('.super-preview-elements'), fields[i].name)
                if (duplicateFields && duplicateFields.length > 1) {
                    for (ii = 0; ii < duplicateFields.length; ++ii) {
                        duplicateFields[ii].closest('.super-element').classList.add('error');
                        error = true;
                    }
                }
            }
            if (error === true) {
                alert(super_create_form_i18n.alert_duplicate_field_names);
                return false;
            }
        }

        // We should skip this function in case we are updating the form code manually
        if(updatingRawFormCode===false) {
            SUPER.regenerate_element_inner(false, ["form_settings", "trigger_settings", "listings_settings", "pdf_settings", "stripe_settings", "translation_settings"]);
        }
        
        $this.html('<i class="fas fa-save"></i>Saving...');

        if (typeof $initial_i18n === 'undefined') {
            if (!$('.super-create-form').hasClass('super-translation-mode')) {
                $initial_i18n = '';
            } else {
                $initial_i18n = $('.super-preview-elements').attr('data-i18n');
            }
        }

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            if (this.readyState == 4) {
                // Success:
                if (this.status == 200) {
                    SUPER.alertWhenSaving = false;
                    document.querySelector('.super-create-form .super-actions .super-save').innerHTML = '<i class="fas fa-save"></i>Save';
                    SUPER.set_session_data('_super_builder_has_unsaved_changes', false);
                    var response = this.responseText;
                    response = JSON.parse(response);
                    if(response.error===true){
                        // Display error message
                        alert(response.msg);
                    }else{
                        if(!isNaN(Number(response))){
                            response = {
                                form_id: response,
                                modifiedTime: 0
                            }
                        }
                        $('.super-create-form .super-header .super-get-form-shortcodes')[0].dataset.modifiedTime = response.modifiedTime;
                        $('.super-create-form .super-header .super-get-form-shortcodes').val('[super_form id="' + response.form_id + '"]');
                        $('.super-create-form input[name="form_id"]').val(response.form_id);
                        if ($method == 3) { // When switching from language
                            if (typeof callback === "function") { 
                                callback($button); // safe to trigger callback
                                return false;
                            }
                        } else {
                            if ($method == 1) {
                                var $this = $('.super-create-form .super-actions .super-preview:eq(3)');
                                if (typeof callback === "function") { 
                                    callback(); // safe to trigger callback
                                }
                                SUPER.preview_form($this);
                                return false;
                            } else {
                                var href = window.location.href;
                                var page = href.substr(href.lastIndexOf('/') + 1);
                                var str2 = "admin.php?page=super_create_form&id";
                                if (page.indexOf(str2) == -1) {
                                    window.location.href = "admin.php?page=super_create_form&id=" + response.form_id;
                                } else {
                                    if ($method == 2) {
                                        location.reload();
                                    }
                                }
                            }
                        }
                    }
                }
                // Complete:
                if (typeof callback === "function") { 
                    callback(); // safe to trigger callback
                }
            }
        };
        xhttp.onerror = function () {
            console.log(this);
            console.log("** An error occurred during the transaction");
        };
        xhttp.open("POST", ajaxurl, true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
        var params = {};
        params.action = 'super_save_form';
        params.form_id = $('.super-create-form input[name="form_id"]').val();
        params.title = $('.super-create-form input[name="title"]').val();
        if(!super_create_form_i18n.version){
            params.formElements = document.querySelector('.super-raw-code-form-elements textarea').value;
            params.formSettings = document.querySelector('.super-raw-code-form-settings textarea').value;
            params.triggerSettings = (document.querySelector('.super-raw-code-trigger-settings textarea') ? document.querySelector('.super-raw-code-trigger-settings textarea').value : '');
            params.translationSettings = document.querySelector('.super-raw-code-translation-settings textarea').value; // @since 4.7.0 translation
            params.i18n = $initial_i18n; // @since 4.7.0 translation
            params.i18n_disable_browser_translation = ($('.super-i18n-disable-browser-translation').hasClass('super-active') ? 'true' : 'false');
            params.i18n_switch = ($('.super-i18n-switch').hasClass('super-active') ? 'true' : 'false'); // @since 4.7.0 translation
        }
        console.log(SUPER.ui.settings);
        // @since 4.9.6 - Secrets
        var localSecrets = [], 
            globalSecrets = [],
            nodes = document.querySelectorAll('.super-local-secrets > ul > li');
        for(i=0; i<nodes.length; i++){
            localSecrets.push({
                name: nodes[i].querySelector('input[name="secretName"]').value,
                value: nodes[i].querySelector('input[name="secretValue"]').value
            });
        }
        nodes = document.querySelectorAll('.super-global-secrets > ul > li');
        for(i=0; i<nodes.length; i++){
            globalSecrets.push({
                name: nodes[i].querySelector('input[name="secretName"]').value,
                value: nodes[i].querySelector('input[name="secretValue"]').value
            });
        }
        params.localSecrets = localSecrets;
        params.globalSecrets = globalSecrets;
        if(super_create_form_i18n.version){
            params.form_data = {
                elements: document.querySelector('.super-raw-code-form-elements textarea').value,
                settings: document.querySelector('.super-raw-code-form-settings textarea').value,
                triggers: (document.querySelector('.super-raw-code-trigger-settings textarea') ? document.querySelector('.super-raw-code-trigger-settings textarea').value : ''),
                woocommerce: (document.querySelector('.super-raw-code-woocommerce-settings textarea') ? document.querySelector('.super-raw-code-woocommerce-settings textarea').value : ''),
                listings: (document.querySelector('.super-raw-code-listings-settings textarea') ? document.querySelector('.super-raw-code-listings-settings textarea').value : ''),
                pdf: (document.querySelector('.super-raw-code-pdf-settings textarea') ? document.querySelector('.super-raw-code-pdf-settings textarea').value : ''),
                stripe: (document.querySelector('.super-raw-code-stripe-settings textarea') ? document.querySelector('.super-raw-code-stripe-settings textarea').value : ''),
                translations: document.querySelector('.super-raw-code-translation-settings textarea').value,
                i18n: $initial_i18n, // @since 4.7.0 translation
                i18n_disable_browser_translation: ($('.super-i18n-disable-browser-translation').hasClass('super-active') ? 'true' : 'false'),
                i18n_switch: ($('.super-i18n-switch').hasClass('super-active') ? 'true' : 'false') // @since 4.7.0 translation
            };
            params.form_data.elements  = params.form_data.elements.trim();
            params.form_data.settings  = params.form_data.settings.trim();
            params.form_data.triggers  = params.form_data.triggers.trim();
            params.form_data.woocommerce  = params.form_data.woocommerce.trim();
            params.form_data.listings  = params.form_data.listings.trim();
            params.form_data.pdf  = params.form_data.pdf.trim();
            params.form_data.stripe  = params.form_data.stripe.trim();
            params.form_data.translations  = params.form_data.translations.trim();
            try{
                if(params.form_data.elements!=='') params.form_data.elements = JSON.parse(params.form_data.elements);
                if(params.form_data.settings!=='') params.form_data.settings = JSON.parse(params.form_data.settings);
                if(params.form_data.triggers!=='') params.form_data.triggers = JSON.parse(params.form_data.triggers);
                if(params.form_data.woocommerce!=='') params.form_data.woocommerce = JSON.parse(params.form_data.woocommerce);
                if(params.form_data.listings!=='') params.form_data.listings = JSON.parse(params.form_data.listings);
                if(params.form_data.pdf!=='') params.form_data.pdf = JSON.parse(params.form_data.pdf);
                if(params.form_data.stripe!=='') params.form_data.stripe = JSON.parse(params.form_data.stripe);
                if(params.form_data.translations!=='') params.form_data.translations = JSON.parse(params.form_data.translations);
                params.form_data = JSON.stringify(params.form_data);
            }catch(e){
                alert('Unable to save form due to JSON parse error, check the browser console for more details.');
                console.log(e);
                return;
            }
        }
        params.z = 1; // Hack to test if request was submitted fully, and server didn't cut off the payload
        params = SUPER.save_form_params_filter(params);
        params = $.param(params);
        xhttp.send(params);
    };
    SUPER.preview_form = function ($this) {
        if ($('input[name="form_id"]').val() === '') {
            alert(super_create_form_i18n.alert_save);
            return false;
        }
        if (!$this.hasClass('super-active')) {
            $this.html('Loading...');
            $('.super-live-preview').html('');
            $('.super-live-preview').addClass('super-loading').css('display', 'block');
            var formId = $('.super-create-form input[name="form_id"]').val();
            SUPER.preFlightMappings = {}; // Reset
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (this.readyState == 4) {
                    // Success:
                    if (this.status == 200) {
                        $('.super-live-preview').removeClass('super-loading');
                        $('.super-live-preview').html(this.responseText);
                        $this.html('Builder');
                    }
                    // Complete:
                    SUPER.clearFormCache();
                    SUPER.files = [];
                    SUPER.init_super_form_frontend({callback:function(formId){
                        if(SUPER.form_js && SUPER.form_js[formId] && SUPER.form_js[formId]['_entry_data']){
                            var data = SUPER.form_js[formId]['_entry_data'];
                            if(data) SUPER.populate_form_with_entry_data(data, $('.super-live-preview .super-form')[0], false);
                        }
                        SUPER.after_preview_loaded_hook(formId);
                    }});
                }
            };
            xhttp.onerror = function () {
                console.log(this);
                console.log("** An error occurred during the transaction");
            };
            xhttp.open("POST", ajaxurl, true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
            var params = {
                action: 'super_load_preview',
                form_id: formId
            };
            params = $.param(params);
            xhttp.send(params);
        } else {
            $('.super-live-preview').css('display', 'none');
            $('.super-tabs-content').css('display', '');
            $this.html('Preview');
        }
        $this.toggleClass('super-active');
    };

    // Update export json
    SUPER.init_resize_element_labels = function () {
        $('.super-create-form .super-element-header .super-element-label > input').each(function () {
            var $span = $(this).parent().children('span');
            var $width = $span.outerWidth(true);
            $(this).parent().css('width', $width + 'px').css('margin-left', '-' + ($width / 2) + 'px');
        });
    };

    // @since 2.9.0 - form setup wizard
    SUPER.update_wizard_preview = function ($theme, $size, $icon, $save) {
        var $theme_setting, $icon_setting;
        if ($theme === null) $theme = $('.super-theme-style-wizard li.super-active').attr('data-value');
        if ($size === null) $size = $('.super-field-size-wizard li.super-active').attr('data-value');
        if ($icon === null) $icon = $('.super-theme-hide-icons-wizard li.super-active').attr('data-value');
        if ($theme == 'squared') $theme_setting = '';
        if ($theme == 'rounded') $theme_setting = 'super-default-rounded';
        if ($theme == 'full-rounded') $theme_setting = 'super-full-rounded';
        if ($theme == 'minimal') $theme_setting = 'super-style-one';
        if ($icon == 'no') $icon_setting = 'yes';
        if ($icon == 'yes') $icon_setting = 'no';
        if ($save === true) {
            $('.super-create-form select[name="theme_style"]').val($theme_setting);
            $('.super-create-form select[name="theme_field_size"]').val($size);
            $('.super-create-form select[name="theme_hide_icons"]').val($icon_setting);
            $('.super-create-form input[name="title"]').val($('.super-create-form input[name="wizard_title"]').val());

            $('.super-create-form input[name="header_to"]').val($('.super-create-form input[name="wizard_header_to"]').val());
            $('.super-create-form select[name="header_from_type"]').val('custom');
            $('.super-create-form input[name="header_from"]').val($('.super-create-form input[name="wizard_header_from"]').val());
            $('.super-create-form input[name="header_from_name"]').val($('.super-create-form input[name="wizard_header_from_name"]').val());
            $('.super-create-form input[name="header_subject"]').val($('.super-create-form input[name="wizard_header_subject"]').val());

            $('.super-create-form input[name="confirm_to"]').val($('.super-create-form input[name="wizard_confirm_to"]').val());
            $('.super-create-form select[name="confirm_from_type"]').val('custom');
            $('.super-create-form input[name="confirm_from"]').val($('.super-create-form input[name="wizard_confirm_from"]').val());
            $('.super-create-form input[name="confirm_from_name"]').val($('.super-create-form input[name="wizard_confirm_from_name"]').val());
            $('.super-create-form input[name="confirm_subject"]').val($('.super-create-form input[name="wizard_confirm_subject"]').val());

            $('.super-create-form input[name="form_thanks_title"]').val($('.super-create-form input[name="wizard_form_thanks_title"]').val());
            $('.super-create-form textarea[name="form_thanks_description"]').val($('.super-create-form textarea[name="wizard_form_thanks_description"]').val());

        }
        var $img_preview = $theme + '-' + $size;
        if ($icon == 'yes') $img_preview = $img_preview + '-icon';
        var $img_preview_url = $('.super-wizard-preview img').attr('data-preview-url') + 'assets/images/wizard-preview/' + $img_preview + '.png';
        $('.super-wizard-preview img').attr('src', $img_preview_url);
    };

    SUPER.trigger_redo_undo = function ($new_code, $old_code) {
        // First convert string to json
        if($new_code==='') { $new_code = {}; }else{ $new_code = JSON.parse($new_code); }
        if($old_code==='') { $old_code = {}; }else{ $old_code = JSON.parse($old_code); }

        // Before saving the form data, add it to form history for our Undo and Redo functionality
        var $history = SUPER.get_session_data('_super_form_history');
        if ($history) {
            $history = JSON.parse($history);
            $history.push($new_code);
        } else {
            // Update form history
            $history = [];
            $history.push($old_code);
            $history.push($new_code);
        }
        var $total_history = Object.keys($history).length;
        // Max history we store is 50 steps, if above 21 delete the first key from history
        if ($total_history > 50) {
            $history.splice(0, 1);
        }
        // Disable buttons
        var $undo = document.querySelector('.super-undo');
        var $redo = document.querySelector('.super-redo');
        $undo.dataset.index = $total_history - 1;
        $redo.dataset.index = $total_history - 1;
        $redo.classList.add('super-disabled');
        if ($total_history <= 1) {
            $undo.classList.add('super-disabled');
        } else {
            $undo.classList.remove('super-disabled');
        }
        // Update form history
        SUPER.set_session_data('_super_form_history', JSON.stringify($history));
        // Update form data
        document.querySelector('.super-raw-code-form-elements > textarea').value = JSON.stringify($new_code, undefined, 4);
    };

    // @since 3.7.0 - function for random name generation when duplicate action button is clicked
    SUPER.generate_new_field_name = function () {
        var field_name = "";
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        for (var i = 0; i < 5; i++) {
            field_name += possible.charAt(Math.floor(Math.random() * possible.length));
        }
        // First check if this fieldname already exists inside builder
        var form = document.querySelector('.super-preview-elements');
        if (SUPER.field_exists(form, field_name).length) {
            field_name = SUPER.generate_new_field_name();
        }
        return 'field_' + field_name;
    };

    SUPER.initTinyMCE = function(selector, remove){
        console.log('Initializing TinyMCE for selector:', selector);
        console.log('current value: ', selector);
        if(typeof remove === 'undefined') remove = false;
        if(remove===true) tinymce.remove(selector);
        tinymce.init({
            selector: selector,

            //var content = tinymce.get('your_editor_id').getContent();
            //content = content.replace(/{stripe_retry_payment_expiry_placeholder}/g, '{stripe_retry_payment_expiry}');
            //content = content.replace(/{stripe_retry_payment_url_placeholder}/g, '{stripe_retry_payment_url}');
            //tinymce.get('your_editor_id').setContent(content);
            setup: function(editor){
                editor.on('init', function() {
                    console.log('TinyMCE editor initialized');
                    // When editor is initialized, get the textarea value and set it as content
                    var textarea = editor.getElement();
                    console.log('Initial textarea value:', textarea.value);
                    if(textarea.value) {
                        editor.setContent(textarea.value);
                        console.log('Set initial content from textarea');
                    }
                    var input = editor.getElement();
                    var content = editor.getContent();
                    input.value = content;
                    SUPER.ui.updateSettings(null, input);
                });
                editor.on('BeforeSetContent', function(e) {
                    console.log('BeforeSetContent - Original content:', e.content);
                    // Replace non-breaking spaces with regular spaces
                    e.content = e.content.replace(/%7B(.+?)%7D/g, '{$1}');
                    console.log('BeforeSetContent - Modified content:', e.content);
                });
                editor.on('Change', function(_e) {
                    console.log('TinyMCE content changed');
                    // Required to store trigger translations properly
                    var input = editor.getElement();
                    var content = editor.getContent();
                    console.log('New content:', content);
                    input.value = content; // Update textarea value to ensure it's preserved
                    console.log('Updated textarea value:', input.value);
                    SUPER.ui.updateSettings(null, input);
                });
            },
            // Other initialization options...
            toolbar_mode: 'scrolling', //'floating', 'sliding', 'scrolling', or 'wrap'
            contextmenu: false,
            plugins: [
                'advlist anchor charmap code fullscreen hr image importcss link lists media paste preview searchreplace table visualblocks'
                // not working as of now: 'wordpress wpautoresize wpdialogs wpeditimage wpemoji wpgallery wplink wptextpattern wpview',
            ],
            fontsize_formats: '8pt 9pt 10pt 11pt 12pt 13pt 14pt 16pt 18pt 20pt 22pt 24pt 36pt 48pt',
            menubar: 'edit view insert format',
            toolbar1: 'bold italic forecolor backcolor alignleft aligncenter alignright alignjustify outdent indent',
            toolbar2: 'numlist bullist image link media table code preview fullscreen',
            content_style: 'body {margin:5px 10px 5px 10px; color:#2c3338; font-family:Helvetica,Arial,sans-serif; font-size:12px }'
            //valid_elements: 'table[!class|id],td,tr,tbody,thead,tfoot,{loop_fields}', // Allow the <table> element with class and id attributes, as well as its child elements: <td>, <tr>, <tbody>, <thead>, <tfoot>, and the {loop_fields} placeholder
            //valid_children: '+body[style],+table[tbody|thead|tfoot|tr|{loop_fields}|loop_fields],+thead[tr],+tbody[tr],+tfoot[tr],+tr[td]', // Allow specific child elements for <body> and <table> elements
        });
    };

    SUPER.ui.positionResetBtns = function(p, input, btn){
        var parentRect = p.getBoundingClientRect();
        var inputRect = input.getBoundingClientRect();
        var inputTopRelativeToParent;
        btn.style.position = 'absolute';
        if(input.closest('.sfui-colorpicker-wrap')){
            btn.style.top = '6px';
            btn.style.right = '15px';
        }else{
            if(p.classList.contains('sfui-tinymce')){
                // For TinyMCE editors, we need to get position relative to the iframe
                var editor = tinymce.get(input.id);
                if(editor && editor.iframeElement){
                    var iframeRect = editor.iframeElement.getBoundingClientRect();
                    inputTopRelativeToParent = iframeRect.top - parentRect.top;
                } else {
                    // Fallback if editor not initialized
                    inputTopRelativeToParent = inputRect.top - parentRect.top;
                }
            } else {
                inputTopRelativeToParent = inputRect.top - parentRect.top;
            }
            var btnRect = btn.getBoundingClientRect();
            if(btnRect.height===0) btnRect.height = 19;
            var btnTop = inputTopRelativeToParent+((inputRect.height-btnRect.height)/2);
            btn.style.top = btnTop+'px';
            btn.style.right = (parseFloat(getComputedStyle(p).paddingRight)+5)+'px';
        }

    };

    jQuery(document).ready(function ($) {
        SUPER.ui.init();
        SUPER.ui.showHideSubsettings();
        var resetBtns = document.querySelectorAll('.sfui-setting .super-reset-settings-buttons');
        resetBtns.forEach(function(btn){
            var p = btn.closest('.sfui-setting'), input = p.querySelector('[name]');
            SUPER.ui.positionResetBtns(p, input, btn);
        });
        $(document).on('mouseenter', '.sfui-setting [name], .sfui-setting.sfui-tinymce > label', function(e){
            if(e.currentTarget!==this) return;
            // This condition ensures that the event target is the topmost `.sfui-setting` element
            var p = this.closest('.sfui-setting');
            var btn = p.querySelector(':scope > .super-reset-settings-buttons, :scope > label > .super-reset-settings-buttons');
            if(!btn) return;
            btn.style.display = 'flex';
            SUPER.ui.positionResetBtns(p, this, btn);
        });
        $(document).on('mouseout', '.sfui-setting [name], .sfui-setting.sfui-tinymce > label', function(e){
            if(e.currentTarget!==this) return;
            if(e.relatedTarget && e.relatedTarget.classList && e.relatedTarget.classList.contains('super-reset-settings-buttons')) return;
            if(e.relatedTarget && e.relatedTarget.parentNode && e.relatedTarget.parentNode.classList && e.relatedTarget.parentNode.classList.contains('super-reset-settings-buttons')) return;
            // This condition ensures that the event target is the topmost `.sfui-setting` element
            var p = this.closest('.sfui-setting');
            var btn = p.querySelector(':scope > .super-reset-settings-buttons, :scope > label > .super-reset-settings-buttons');
            if(!btn) return;
            btn.style.display = '';
        });
        // Mouse enters any child of the colorpicker wrap, show the button
        $(document).on('mouseenter', '.sfui-colorpicker-wrap, .sfui-colorpicker-wrap *', function(_e){
            var wrap = $(this).closest('.sfui-colorpicker-wrap')[0];
            var btn = wrap.querySelector('.super-reset-settings-buttons');
            if (btn) {
                btn.style.display = 'flex';
                SUPER.ui.positionResetBtns(wrap, this, btn);
            }
        });
        // Hide when the mouse leaves the whole colorpicker wrap area
        $(document).on('mouseleave', '.sfui-colorpicker-wrap', function(_e){
            var wrap = this;
            var btn = wrap.querySelector('.super-reset-settings-buttons');
            if (btn) btn.style.display = '';
        });
        $(document).on('click', '.sfui-setting .super-reset-default-value, .sfui-setting .super-reset-last-value', function(){
            // If parent is settings tab
            var value = this.dataset.value;
            var p = this.parentNode.closest('.sfui-setting');
            if(this.parentNode.closest('.sfui-colorpicker-wrap')) p = this.parentNode.closest('.sfui-colorpicker-wrap');
            var input = p.querySelector('[name]');
            SUPER.ui.setTabFieldValue(input, value);
            SUPER.ui.updateSettings(null, input);
            return;
        });
        SUPER.init_docs();

        $('body.wp-admin').addClass('folded');
        init_form_settings_container_heights();
        
        // Set up event delegation for form settings changes
        // For checkboxes, only listen to 'change' event which fires after the state is updated
        $(document).on('change', '.super-tabs-content input[type="checkbox"]', function(e) {
            // Ignore if this is a programmatic change
            if (this.dataset.programmaticChange) {
                delete this.dataset.programmaticChange;
                return;
            }
            console.log('Checkbox change event, checked state:', this.checked);
            console.log('Checkbox name:', this.name);
            console.log('Checkbox parent data-g:', this.closest('[data-g]') ? this.closest('[data-g]').getAttribute('data-g') : 'none');
            SUPER.ui.updateSettings(e, this);
        });
        
        // For other inputs, continue using change/keyup
        $(document).on('change keyup', '.super-tabs-content input:not(.sfui-colorpicker input):not([type="checkbox"]), .super-tabs-content select, .super-tabs-content textarea:not(.sfui-textarea-tinymce)', function(e) {
            // Ignore if this is a programmatic change
            if (this.dataset.programmaticChange) {
                delete this.dataset.programmaticChange;
                return;
            }
            
            // Debounce keyup events
            if (e.type === 'keyup') {
                if (this._keyupTimeout) clearTimeout(this._keyupTimeout);
                this._keyupTimeout = setTimeout(() => {
                    SUPER.ui.updateSettings(e, this);
                }, 500);
            } else {
                SUPER.ui.updateSettings(e, this);
            }
        });

        var $doc = $(document),
            $super_hints,
            $super_hints_steps,
            $node,
            $activePanel = SUPER.get_session_data('_super_builder_last_active_panel'),
            $activeFormSettingsTab = SUPER.get_session_data('_super_builder_last_active_form_settings_tab'),
            $activeElementSettingsTab = SUPER.get_session_data('_super_builder_last_active_element_settings_tab');

        SUPER.initTinyMCE('.sfui-textarea-tinymce');
        document.querySelector('.super-raw-code-form-settings textarea').value = SUPER.get_form_settings(true);
        document.querySelector('.super-raw-code-emails-settings textarea').value = SUPER.get_emails_settings(true);
        document.querySelector('.super-raw-code-theme-settings textarea').value = SUPER.get_theme_settings(true);
        document.querySelector('.super-raw-code-trigger-settings textarea').value = SUPER.get_trigger_settings(true);
        document.querySelector('.super-raw-code-woocommerce-settings textarea').value = SUPER.get_woocommerce_settings(true);
        document.querySelector('.super-raw-code-listings-settings textarea').value = SUPER.get_listings_settings(true);
        document.querySelector('.super-raw-code-pdf-settings textarea').value = SUPER.get_pdf_settings(true);
        document.querySelector('.super-raw-code-stripe-settings textarea').value = SUPER.get_stripe_settings(true);
        document.querySelector('.super-raw-code-translation-settings textarea').value = SUPER.get_translation_settings(true);

        // Check if there is an active panel
        if($activePanel){
            $node = $('.super-elements .super-element h3:eq('+$activePanel+')');
            if($node.length){
                $('.super-elements .super-element.super-active').removeClass('super-active');
                $node.parent().addClass('super-active');
                $('.super-elements').addClass('super-active');
            }
        }else{
            // Defaults to layout panel
            $('.super-layout-elements').addClass('super-active');
        }
        // Check if there is an active form settings TAB
        if($activeFormSettingsTab){
            $node = $('.super-form-settings .super-elements-container .tab-content:eq(' + $activeFormSettingsTab + ')');
            if($node.length){
                $('.super-form-settings .super-elements-container .tab-content.super-active').removeClass('super-active');
                $node.addClass('super-active');
                $('.super-form-settings-tabs > select > option[selected]').prop({selected: false});
                $('.super-form-settings-tabs > select > option:eq(' + $activeFormSettingsTab + ')').prop({selected: true});
            }
        }
        // Check if there is an active element settings TAB
        if($activeElementSettingsTab){
            $node = $('.super-element-settings .super-elements-container .tab-content:eq(' + $activeElementSettingsTab + ')');
            if($node.length){
                $('.super-element-settings .super-elements-container .tab-content.super-active').removeClass('super-active');
                $node.addClass('super-active');
                $('.super-element-settings-tabs > select > option[selected]').prop({selected: false});
                $('.super-element-settings-tabs > select > option:eq(' + $activeElementSettingsTab + ')').prop({selected: true});
            }
        }

        // TAB setting change by user
        $doc.on('click', '.super-elements .super-element h3', function () {
            if($(this).parent().hasClass('super-active')){
                $('.super-elements .super-element.super-active').removeClass('super-active');
                $('.super-elements.super-active').removeClass('super-active');
            }else{
                $('.super-elements .super-element.super-active').removeClass('super-active');
                $(this).parent().addClass('super-active');
                $('.super-elements').addClass('super-active');
            }
            // Remember which TAB was active for the last time
            SUPER.set_session_data('_super_builder_last_active_panel', $(this).parent().index());
            init_form_settings_container_heights();
            return false;
        });

        // Form settings TAB change by the user
        $doc.on('change', '.super-form-settings-tabs > select, .super-element-settings-tabs > select', function () {
            $(this).parents('.super-elements-container:eq(0)').children('.tab-content').removeClass('super-active');
            $(this).parents('.super-elements-container:eq(0)').children('.tab-content:eq(' + ($(this).val()) + ')').addClass('super-active');
            // Remember which TAB was active for the last time
            if(this.closest('.super-form-settings-tabs')){
                var option = this.options[this.selectedIndex];
                this.closest('.super-form-settings-tabs').classList.remove('_g_');
                if(option.classList.contains('_g_')){
                    this.closest('.super-form-settings-tabs').classList.add('_g_');
                }
                SUPER.set_session_data('_super_builder_last_active_form_settings_tab', $(this).val());
            }
            if(this.closest('.super-element-settings-tabs')){
                var key = 0;
                if(this.children[this.selectedIndex]){
                    key = this.children[this.selectedIndex].dataset.key;
                }
                SUPER.set_session_data('_super_builder_last_active_element_settings_tab', key);
            }
        });

        // @since 4.6.0 - transfer elements with other forms
        setInterval(function () {
            var element = SUPER.get_session_data('_super_transfer_element_html', 'local');
            if (element && element !== '') {
                $('.super-preview-elements').addClass('super-transfering');
            } else {
                $('.super-preview-elements').removeClass('super-transfering');
            }
        }, 300); // check every 3 milli seconds


        // @since 4.9.6 - Secrets
        $doc.on('click', '.super-add-secret', function(){
            var clone = this.closest('li').cloneNode(true);
            // Empty field
            clone.querySelector('input[name="secretName"]').value = '';
            clone.querySelector('input[name="secretValue"]').value = '';
            clone.querySelector('.super-secret-tag').innerHTML = '{@}';
            this.closest('ul').appendChild(clone);
            if(this.closest('.super-global-secrets')){
                var node = this.closest('li');
                var nodes = node.querySelectorAll('input');
                for(var i=0; i<nodes.length; i++){
                    nodes[i].disabled = true;
                }
            }
        });
        $doc.on('click', '.super-delete-secret', function(){
            // Only delete if there are more than 1 items
            if(this.closest('ul').children.length>1){
                // Confirm if deleting a global secret
                if(this.closest('.super-global-secrets')){
                    var $delete = confirm(super_create_form_i18n.confirm_deletion);
                    if ($delete === true) {
                        this.closest('li').remove();
                    }
                }else{
                    this.closest('li').remove();
                }
            }
        });
        $doc.on('click', '.super-edit-secret', function(){
            var node = this.closest('li');
            var nodes = node.querySelectorAll('input');
            for(var i=0; i<nodes.length; i++){
                nodes[i].disabled = false;
            }
        });
        $doc.on('change keyup blur', '.super-secrets input[name="secretName"]', function () {
            this.value = SUPER.formatUniqueFieldName(this.value);
            // Update the {@tag}
            if(this.value===''){
                this.closest('li').querySelector('.super-secret-tag').innerHTML = '{@}';
            }else{
                this.closest('li').querySelector('.super-secret-tag').innerHTML = '{@'+this.value+'}';
            }
            // Check if exact same secret name exists, if so notify user visually about it
            var nodes = document.querySelectorAll('.super-secrets input[name="secretName"]');
            var duplicateFound = false;
            for(var i=0; i<nodes.length; i++){
                if(nodes[i]===this || nodes[i].value==='') continue;
                if(nodes[i].value === this.value){
                    nodes[i].classList.add('super-error');
                    duplicateFound = true;
                }else{
                    nodes[i].classList.remove('super-error');
                }
            }
            if(!duplicateFound){
                this.classList.remove('super-error');
            }
        });

        // @since 4.9.0 - update form code manually
        $doc.on('click', '.super-update-raw-code', function () {
            var html, notice, field, rawCodeNodes = [
                'form-elements',
                'form-settings',
                'trigger-settings',
                'woocommerce-settings',
                'listings-settings',
                'pdf-settings',
                'stripe-settings',
                'translation-settings'
            ];
            // Handle non-exception-throwing cases:
            // Neither JSON.parse(false) or JSON.parse(1234) throw errors, hence the type-checking,
            // but... JSON.parse(null) returns null, and typeof null === "object", 
            // so we must check for that, too. Thankfully, null is falsey, so this suffices:
            for(var i=0; i<rawCodeNodes.length; i++){
                var selector = rawCodeNodes[i];
                notice = document.querySelector('.super-raw-code-'+selector+' .sfui-notice');
                field = document.querySelector('.super-raw-code-'+selector+' textarea');
                try{
                    (field.value!=='' ? JSON.parse(field.value) : {});
                }catch(e){
                    html = '<strong>'+super_create_form_i18n.invalid_json+'</strong>';
                    html += '<br /><br />------<br />'+e+'<br />------<br /><br />';
                    html += super_create_form_i18n.try_jsonlint;
                    notice.innerHTML = html;
                    notice.classList.remove('sfui-yellow');
                    notice.classList.add('sfui-red');
                    field.classList.add('sfui-red');
                    notice.scrollIntoView();
                    return false;
                }
                notice.innerHTML = super_create_form_i18n.edit_json_notice_n1;          
                notice.classList.remove('sfui-red');
                notice.classList.add('sfui-yellow');
                field.classList.remove('sfui-red');
            }
            // Add loading state to button
            var button = this;
            var oldHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-save"></i>'+super_create_form_i18n.save_loading;
            clearTimeout(SUPER.new_version_check);
            SUPER.before_save_form(function(){
                SUPER.save_form($('.super-actions .super-save'), 2, undefined, undefined, function(){
                    clearTimeout(SUPER.new_version_check);
                    SUPER.new_version_check = setTimeout(function(){
                        checkNewerForVersion();
                    }, 30000);
                    button.innerHTML = oldHtml;
                }, true);
            });
        });

        // @since 4.0.0 - update conditional checks values
        $doc.on('change keydown keyup blur', '.super-conditional-check input[type="text"], .super-conditional-check select', function () {
            var $parent = $(this).parents('.super-conditional-check:eq(0)');
            var $value = '';
            $parent.children('input[type="text"], select').each(function () {
                if ($(this).index() === 0) {
                    $value += $(this).val();
                } else {
                    $value += ',' + $(this).val();
                }
            });
            if ($value == ',') $value = '';
            $parent.children('input[type="hidden"]').val($value);
        });

        // @since 4.0.0 - skip tutorial if checkbox is checked.
        $doc.on('click', '.tutorial-do-not-show-again', function (e) {
            e.preventDefault();
            var $status = $(this).children('input').is(':checked');
            if ($status === false) {
                $(this).children('input').prop('checked', true);
            } else {
                $(this).children('input').prop('checked', false);
            }
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_tutorial_do_not_show_again',
                    status: $status
                }
            });
        });

        // @since 4.8.0 - Image select field type
        $doc.on('click', '.super-image-select-option', function () {
            var $this = $(this),
                $parent = $this.parent();
            $parent.children().removeClass('super-active');
            $this.addClass('super-active');
            $parent.find('input').prop('checked', false);
            $this.find('input').prop('checked', true);
        });

        $doc.on('click', '.super-retain-underlying-global-values', function(){
            var checked = this.querySelector('input').checked;
            var i, nodes = document.querySelectorAll('input[name="retain_underlying_global_values"]');
            for(i=0; i<nodes.length; i++){
                nodes[i].checked = checked;
            }
            SUPER.update_form_settings(true);
        });

        // @since 4.7.0 - tabs
        $doc.on('click', '.super-tabs > span', function () {
            var $this = $(this),
                $parent = $this.parent(),
                $tab = $this.attr('data-tab');
            
            $('.super-element.super-form-settings').removeClass('super-editing-raw-code');
            if($tab==='code'){
                $('.super-element.super-form-settings').addClass('super-editing-raw-code');
            }
            $parent.children('span').removeClass('super-active');
            $this.addClass('super-active');
            // If code tab, update translation code
            if($this.hasClass('super-tab-code')){
                document.querySelector('.super-raw-code-form-settings textarea').value = SUPER.get_form_settings(true);
                document.querySelector('.super-raw-code-emails-settings textarea').value = SUPER.get_emails_settings(true);
                document.querySelector('.super-raw-code-theme-settings textarea').value = SUPER.get_theme_settings(true);
                document.querySelector('.super-raw-code-trigger-settings textarea').value = SUPER.get_trigger_settings(true);
                document.querySelector('.super-raw-code-woocommerce-settings textarea').value = SUPER.get_woocommerce_settings(true);
                document.querySelector('.super-raw-code-listings-settings textarea').value = SUPER.get_listings_settings(true);
                document.querySelector('.super-raw-code-pdf-settings textarea').value = SUPER.get_pdf_settings(true);
                document.querySelector('.super-raw-code-stripe-settings textarea').value = SUPER.get_stripe_settings(true);
                document.querySelector('.super-raw-code-translation-settings textarea').value = SUPER.get_translation_settings(true);
            }
            $('.super-tabs-content').css('display', '');
            $('.super-preview.super-switch').removeClass('super-active');
            $('.super-preview.super-switch').html('Builder');
            $('.super-live-preview').css('display', 'none');
            $('.super-live-preview').html('');
            $('.super-tabs-content .super-tab-content').removeClass('super-active');
            $('.super-tabs-content .super-tab-' + $tab).addClass('super-active');
        });

        // upon choosing an item set it to active and deactivate others
        $doc.on('click', '.super-tab-translations .super-dropdown-list .super-item', function () {
            var $this = $(this),
                $form_id = $('.super-header input[name="form_id"]').val(),
                $shortcode = '[form-not-saved-yet]',
                $language = $this.html(),
                $value = $this.attr('data-value'),

                $dropdown = $this.parents('.super-dropdown:eq(0)'),
                $row = $dropdown.parents('li:eq(0)');
            $dropdown.find('li.super-active').removeClass('super-active');
            if ($this.hasClass('super-active')) {
                $(this).removeClass('super-active');
            } else {
                $(this).addClass('super-active');
            }
            $dropdown.children('.super-dropdown-placeholder').html($language);
            $dropdown.removeClass('super-open');
            // Update shortcode accordingly
            if ($dropdown.attr('data-name') == 'language') {
                if ($form_id !== '') {
                    if ($language !== '') {
                        $shortcode = '[super_form i18n="' + $value + '" id="' + $form_id + '"]';
                    } else {
                        $shortcode = '';
                    }
                }
                $row.find('.super-get-form-shortcodes').val($shortcode);
            }
        });
        // filter method for filtering dropdown items
        $doc.on('keyup', '.super-tab-translations .super-dropdown-search input', function () {
            var $this = $(this),
                $dropdown = $this.parents('.super-dropdown:eq(0)'),
                $value = $this.val().toLowerCase();
            if ($value === '') {
                // No longer filtering, show all
                $dropdown.removeClass('super-filtering');
                $dropdown.find('.super-dropdown-list .super-item').removeClass('super-match');
                return;
            }
            // We are filtering
            $dropdown.addClass('super-filtering');
            $dropdown.find('.super-dropdown-list .super-item').each(function () {
                if ($(this).html().toLowerCase().indexOf($value) !== -1) {
                    $(this).addClass('super-match');
                } else {
                    $(this).removeClass('super-match');
                }
            });
        });
        // enable RTL layout
        $doc.on('click', '.super-translations-list .super-rtl', function () {
            $(this).toggleClass('super-active');
        });
        // disable browser translation
        $doc.on('click', '.super-tab-translations .super-i18n-disable-browser-translation', function () {
            $(this).toggleClass('super-active');
        });
        // enable language switch
        $doc.on('click', '.super-tab-translations .super-i18n-switch', function () {
            $(this).toggleClass('super-active');
        });
        // create translation
        $doc.on('click', '.super-create-translation', function () {
            // Validate
            var $row = $('.super-default-language'),
                $language = $row.find('.super-dropdown[data-name="language"] .super-active'),
                $flag = $row.find('.super-dropdown[data-name="flag"] .super-active');
            $row.find('.super-dropdown[data-name="language"], .super-dropdown[data-name="flag"]').removeClass('super-error');
            if (!$language.length || !$flag.length) {
                if (!$language.length)
                    $row.find('.super-dropdown[data-name="language"]').addClass('super-error');
                if (!$flag.length)
                    $row.find('.super-dropdown[data-name="flag"]').addClass('super-error');
                return false;
            }
            // We will grab the so called "dummy" html, which is the first item in our list
            var $dummy = $('.super-translations-list > li').first(),
                $last = $('.super-translations-list > li').last(),
                $clone = $dummy.clone();
            // First reset the tooltips for our buttons
            $clone.find('.tooltipstered').removeClass('tooltipstered');
            $clone.find('.super-tooltip').each(function () {
                $(this).attr('title', $(this).attr('data-title'));
            });
            $clone.insertAfter($last);
            SUPER.init_tooltips();
            SUPER.init_docs();
        });
        // edit translation
        $doc.on('click', '.super-translations-list .super-edit', function () {
            console.log(SUPER.ui.settings);
            var $row = $(this).parent(),
                $language = $row.find('.super-dropdown[data-name="language"] .super-active'),
                $language_title = $language.html(),
                $flag = $row.find('.super-dropdown[data-name="flag"] .super-active'),
                $tab = $('.super-tabs .super-tab-builder'),
                $initial_i18n = $('.super-preview-elements').attr('data-i18n');

            SUPER.ui.i18n.lastLanguage = ($initial_i18n ? $initial_i18n : '');

            // Validate
            $row.find('.super-dropdown[data-name="language"], .super-dropdown[data-name="flag"]').removeClass('super-error');
            if (!$language.length || !$flag.length) {
                if (!$language.length)
                    $row.find('.super-dropdown[data-name="language"]').addClass('super-error');
                if (!$flag.length)
                    $row.find('.super-dropdown[data-name="flag"]').addClass('super-error');
                return false;
            }

            if (!$('.super-create-form').hasClass('super-translation-mode')) {
                $initial_i18n = '';
            }

            // Set i18n (language key)
            $language = $language.attr('data-value');
            $('.super-preview-elements').attr('data-i18n', $language);
            // Remove active class from all tabs
            $('.super-tabs > span').removeClass('super-active');
            $('.super-tab-content.super-active').removeClass('super-active');
            // Add active class to builder tabs
            $('.super-tabs .super-tab-builder').addClass('super-active');
            $('.super-tab-content.super-tab-builder').addClass('super-active');
            // Set new tab title including language flag
            $flag = $flag.children('img')[0].outerHTML;
            $tab.html($tab.attr('data-title') + $flag);

            // Check switching to default language
            var $language_changed = $('.super-preview-elements').attr('data-language-changed');
            var $i18n = '';
            if ($row.hasClass('super-default-language')) {
                // Remove translation mode notice
                $('.super-translation-mode-notice').hide();
                $('.super-create-form').removeClass('super-translation-mode').attr('data-i18n', null);
            } else {
                $i18n = $('.super-preview-elements').attr('data-i18n');
                // Set translation mode notice
                $('.super-translation-mode-notice').show();
                $('.super-translation-mode-notice .super-i18n-language').html($language_title);
                // We were in builder mode, so let's activate translation mode and reload the form elements with the correct language
                $('.super-create-form').addClass('super-translation-mode').attr('data-i18n', $language);
            }
            SUPER.ui.i18n.translating = false;
            if($i18n!==''){
                SUPER.ui.i18n.translating = true;
            }
            // Display loading icon, and hide all elements/settings
            $('.super-preview-elements, .super-form-settings').addClass('super-loading');

            // Always check if user was updating an element, if so cancel it
            cancel_update();

            if (typeof $language_changed !== 'undefined') {
                $('.super-preview-elements').attr('data-language-changed', null);
            }

            // Always save the form before switching to a different language
            // This will prevent loading "old" / "unsaved" form elements and settings
            clearTimeout(SUPER.new_version_check);
            SUPER.before_save_form(function(){
                console.log(SUPER.ui.settings);
                SUPER.save_form($('.super-actions .super-save'), 3, $(this), $initial_i18n, function ($button) {
                    console.log(SUPER.ui.settings);
                    clearTimeout(SUPER.new_version_check);
                    SUPER.new_version_check = setTimeout(function(){
                        checkNewerForVersion();
                    }, 30000);
                    // When saving finished we can continue
                    $row = $button.parent();
                    // Reload builder html, and form settings TAB
                    $.ajax({
                        type: 'post',
                        url: ajaxurl,
                        data: {
                            action: 'super_switch_language',
                            form_id: $('.super-create-form input[name="form_id"]').val(),
                            i18n: $i18n
                        },
                        success: function (data) {
                            console.log(SUPER.ui.settings);
                            data = JSON.parse(data);
                            $('.super-preview-elements').html(data.elements);
                            SUPER.regenerate_element_inner(true, ["form_settings", "trigger_settings", "listings_settings", "pdf_settings", "stripe_settings", "translation_settings"]);
                            console.log(SUPER.ui.settings);
                            $('.super-form-settings .super-elements-container').html(data.settings);
                            // When switching from translating back to main language, restore the repeater items
                            var i, nodes = document.querySelectorAll('input[value^="[super_listings"');
                            for(i=0; i<nodes.length; i++){
                                var repeater = nodes[i].closest('.sfui-repeater-item');
                                var list_id = repeater.querySelector('input[name="id"]').value;
                                var form_id = document.querySelector('.super-header input[name="form_id"]').value;
                                var listingShortcode = '[super_listings ';
                                if($i18n!=='') listingShortcode += 'i18n="'+$i18n+'"';
                                listingShortcode += ' list="'+list_id+'" id="'+form_id+'"]';
                                nodes[i].value = listingShortcode;
                            }
                            if($i18n===''){
                                var slug = 'stripe';
                                var tab = document.querySelector('.super-tab-'+slug);
                                if(typeof SUPER.ui.settings['_' + slug]!=='undefined' && slug==='stripe'){
                                    var x = SUPER.ui.settings['_'+slug];
                                    // Adjust repeater items based on i18n_data
                                    var repeaters = tab.querySelectorAll('[data-r]');
                                    repeaters.forEach(function(repeater) {
                                        var key = repeater.getAttribute('data-r');
                                        if (x[key]) {
                                            var repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                                            var targetCount = x[key].length;
                                            // Add missing items
                                            while (repeaterItems.length < targetCount) {
                                                SUPER.ui.btn(event, repeater.querySelector('.add-repeater-item-btn'), 'addRepeaterItem', true);
                                                repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                                            }
                                            // Remove excess items
                                            while (repeaterItems.length > targetCount) {
                                                SUPER.ui.btn(event, repeaterItems[repeaterItems.length - 1].querySelector('.delete-repeater-item-btn'), 'deleteRepeaterItem', true);
                                                repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                                            }
                                        }
                                    });
                                }
                                var slug = 'triggers';
                                var tab = document.querySelector('.super-tab-'+slug);
                                if(typeof SUPER.ui.settings['_' + slug]!=='undefined' && slug==='triggers'){
                                    var x = SUPER.ui.settings['_'+slug];
                                    // Adjust repeater items based on i18n_data
                                    var repeaters = tab.querySelectorAll('[data-r]');
                                    repeaters.forEach(function(repeater) {
                                        var key = repeater.getAttribute('data-r');
                                        if (x[key]) {
                                            var repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                                            var targetCount = x[key].length;
                                            // Add missing items
                                            while (repeaterItems.length < targetCount) {
                                                SUPER.ui.btn(event, repeater.querySelector('.add-repeater-item-btn'), 'addRepeaterItem', true);
                                                repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                                            }
                                            // Remove excess items
                                            while (repeaterItems.length > targetCount) {
                                                SUPER.ui.btn(event, repeaterItems[repeaterItems.length - 1].querySelector('.delete-repeater-item-btn'), 'deleteRepeaterItem', true);
                                                repeaterItems = repeater.querySelectorAll('.sfui-repeater-item');
                                            }
                                        }
                                    });
                                }
                            }
                            $('.super-preview-elements, .super-form-settings').removeClass('super-loading');
                        },
                        error: function () {
                            alert(super_create_form_i18n.export_form_error);
                        },
                        complete: function () {
                            console.log(SUPER.ui.settings);
                            SUPER.init_slider_field();
                            SUPER.init_tooltips();
                            SUPER.init_docs();
                            SUPER.init_image_browser();
                            SUPER.init_color_pickers();
                            SUPER.init_field_filter_visibility();
                            init_form_settings_container_heights();
                            // Disable sortable functionality
                            if ($('.super-create-form').hasClass('super-translation-mode')) {
                                $('.super-preview-elements').sortable('disable');
                            } else {
                                // Enable sortable functionality
                                $('.super-preview-elements').sortable('enable');
                            }
                            SUPER.ui.showHideSubsettings();
                        }
                    });
                });
            });
        });
        // delete translation
        $doc.on('click', '.super-translations-list .super-delete', function () {
            var $delete = confirm(super_create_form_i18n.confirm_deletion);
            if ($delete === true) {
                // Before removing language check if currently in translation mode for this language
                // If this is the case we must switch back to the default language and thus the "builder" mode
                var $row = $(this).parent(),
                    $language = $row.find('.super-dropdown[data-name="language"] .super-active'),
                    $flag = $('.super-default-language .super-dropdown[data-name="flag"] .super-active'),
                    $i18n = $('.super-preview-elements').attr('data-i18n'),
                    $tab = $('.super-tabs .super-tab-builder');
                if($language){
                    var $deleted_i18n = $language.attr('data-value');
                    // Clean up trigger translations where needed
                    var i, json='', nodes = document.querySelectorAll('.sfui-setting [name="i18n"]');
                    for(i=0; i<nodes.length; i++){
                        try {
                            if(nodes[i].value!==''){
                                json = JSON.parse(nodes[i].value);
                                if(json[$deleted_i18n]){
                                    delete json[$deleted_i18n];
                                }
                                nodes[i].value = JSON.stringify(json, undefined, 4);
                            }
                        } catch (_e) {
                            // Failed to parse json
                            nodes[i].value = '';
                        }
                    }
                }
                if ($('.super-create-form').hasClass('super-translation-mode')) {
                    if ($language) {
                        $language = $language.attr('data-value');
                        if ($language == $i18n) {
                            // Reset remembered original value for translatable settings
                            SUPER.ui.i18n.restore_original_value();
                            // Switch back to builder mode
                            $('.super-translation-mode-notice').hide();
                            $('.super-create-form').removeClass('super-translation-mode').attr('data-i18n', null);
                            // Set flag to default language flag
                            $flag = $flag.children('img')[0].outerHTML;
                            // Set new tab title including language flag
                            $tab.html($tab.attr('data-title') + $flag);
                        }
                    }
                }
                // Always check if user was updating an element, if so cancel it
                cancel_update();
                // Remove language from the list
                $(this).parent().remove();
            }
        });

        // @since 3.1.0 - backup history
        $doc.on('click', '.super-form-history .super-backups', function () {
            $('.super-backup-history, .super-first-time-setup-bg').addClass('super-active');
            $('.super-backup-history').addClass('super-loading');
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_restore_backup',
                    form_id: $('.super-create-form input[name="form_id"]').val()
                },
                success: function (data) {
                    $('.super-wizard-backup-history > ul').remove();
                    $('.super-wizard-backup-history').find('i').remove();
                    $(data).appendTo($('.super-wizard-backup-history'));
                },
                complete: function () {
                    $('.super-backup-history').removeClass('super-loading');
                }
            });

        });
        $doc.on('click', '.super-wizard-backup-history > ul > li > span', function () {
            $(this).html('Restoring...').addClass('super-loading');
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_restore_backup',
                    form_id: $('.super-create-form input[name="form_id"]').val(),
                    backup_id: $(this).parent('li').attr('data-id')
                },
                success: function () {
                    location.reload();
                }
            });
        });
        $doc.on('click', '.super-wizard-backup-history > ul > li > i', function () {
            var $parent = $(this).parents('ul:eq(0)');
            var $delete = confirm(super_create_form_i18n.confirm_deletion);
            if ($delete === true) {
                var $backup = $(this).parent();
                $backup.html(super_create_form_i18n.deleting);
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_delete_backups',
                        backup_id: $backup.data('id')
                    },
                    success: function () {
                        $backup.slideUp("normal", function () {
                            $(this).remove();
                            if ($parent.children('li').length === 0) {
                                $('.super-wizard-backup-history > ul').remove();
                                $('<i>' + super_create_form_i18n.no_backups_found + '</i>').appendTo($('.super-wizard-backup-history'));
                            }
                        });
                    }
                });
            }

        });
        $doc.on('click', '.super-delete-backups', function () {
            var $oldHtml = $(this).html();
            var $button = $(this);
            $button.html(super_create_form_i18n.deleting).addClass('super-loading');
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_delete_backups',
                    form_id: $('.super-create-form input[name="form_id"]').val()
                },
                success: function () {
                    $('.super-wizard-backup-history > ul').remove();
                    $('<i>' + super_create_form_i18n.no_backups_found + '</i>').appendTo($('.super-wizard-backup-history'));
                    $button.html($oldHtml).removeClass('super-loading');
                }
            });
        });

        // @since 4.0.0 - minimize toggle button to toggle all elements minimized or maximize
        $doc.on('click', '.super-form-history .super-minimize-toggle, .super-form-history .super-maximize-toggle', function () {
            var $minimize = 'yes';
            if ($(this).hasClass('super-maximize-toggle')) {
                $minimize = 'no';
            }
            $('.super-preview-elements .super-element').each(function () {
                if ($minimize == 'yes') {
                    $(this).attr('data-minimized', 'yes').addClass('super-minimized');
                } else {
                    $(this).attr('data-minimized', 'no').removeClass('super-minimized');
                }
            });
            SUPER.init_resize_element_labels();
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner(true, []);
        });

        // @since 4.6.0 - improved undo/redo buttons
        $doc.on('click', '.super-form-history .super-undo, .super-form-history .super-redo', function () {
            var $this = $(this);
            if ($this.hasClass('super-disabled')) {
                return true;
            }
            var $history,
                $total_history,
                $index,
                $other;
            $history = SUPER.get_session_data('_super_form_history');
            if ($history) {
                $history = JSON.parse($history);
                $total_history = Object.keys($history).length;
                $index = parseFloat($this.attr('data-index'));
                if ($this.hasClass('super-undo')) {
                    $index = $index - 1;
                    $other = $('.super-form-history .super-redo');
                } else {
                    $index = $index + 1;
                    $other = $('.super-form-history .super-undo');
                }
                $other.removeClass('super-disabled');
                if ($this.hasClass('super-undo')) {
                    // Add correct indexes to the undo/redo buttons
                    if ($index - 1 < 0) {
                        $this.addClass('super-disabled');
                    } else {
                        $this.removeClass('super-disabled');
                    }
                } else {
                    if ($index >= $total_history - 1) {
                        $this.addClass('super-disabled');
                    } else {
                        $this.removeClass('super-disabled');
                    }
                }
                $this.addClass('super-loading');
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_undo_redo',
                        form_id: $('.super-create-form input[name="form_id"]').val(),
                        elements: JSON.stringify($history[$index])
                    },
                    success: function (result) {
                        $('.super-preview .super-preview-elements').html(result);
                        SUPER.init_resize_element_labels();
                    },
                    complete: function () {
                        $this.removeClass('super-loading');
                    }
                });
                $other.attr('data-index', $index);
                $this.attr('data-index', $index);
            }
        });


        SUPER.init_drop_here_placeholder();
        SUPER.init_dragable_elements();
        SUPER.init_image_browser();
        SUPER.init_resize_element_labels();

        // @since 2.9.0 - Form setup wizard
        $doc.on('click', '.super-theme-style-wizard > li, .super-field-size-wizard > li, .super-theme-hide-icons-wizard > li', function () {
            var $this = $(this);
            var $parent = $this.parent();
            $parent.children('li').removeClass('super-active');
            $this.addClass('super-active');
            var $value = $this.attr('data-value');
            if ($parent.hasClass('super-theme-style-wizard')) SUPER.update_wizard_preview($value, null, null, false);
            if ($parent.hasClass('super-field-size-wizard')) SUPER.update_wizard_preview(null, $value, null, false);
            if ($parent.hasClass('super-theme-hide-icons-wizard')) SUPER.update_wizard_preview(null, null, $value, false);
        });

        $doc.on('click', '.super-save-wizard', function () {
            $(this).addClass('super-loading').html('Saving settings...');
            SUPER.update_wizard_preview(null, null, null, true);
            $('.super-actions .super-save').trigger('click');
        });
        $doc.on('click', '.super-wizard-settings .super-tabs > li', function () {
            var $index = $(this).index();
            $(this).parent().children('li').removeClass('super-active');
            $(this).addClass('super-active');
            $('.super-wizard-settings .super-tab-content > li').removeClass('super-active');
            $('.super-wizard-settings .super-tab-content > li:eq(' + $index + ')').addClass('super-active');
        });


        // @since 1.5
        $doc.on('focus', '.super-element.super-element-settings input[name="name"]', function () {
            this.value = this.value.split('[')[0];
        });
        $doc.on('blur', '.super-element.super-element-settings input[name="name"]', function () {
            var $editing = $('.super-preview-elements .super-element.editing');
            SUPER.check_for_unique_field_name($editing[0], true);
        });
        $doc.on('change keyup', '.super-element.super-element-settings input[name="name"]', function () {
            var $this = $(this);
            var $editing = $('.super-preview-elements .super-element.editing');
            if($editing && $editing.data('shortcode-tag')==='button'){
                return;
            }
            var $parent = $editing;
            var $field = $parent.find('.super-shortcode-field');
            var $old_name = $field.attr('name');
            if($field.hasClass('super-fileupload')){
                $field = $field.next()
                $old_name = $field.attr('name');
            }
            var $new_field_name = SUPER.formatUniqueFieldName($this.val());
            $this.val($new_field_name);
            $field.attr('name', $new_field_name);
            var $element_data_field = $parent.children('textarea[name="element-data"]');
            var $element_data = $element_data_field.val();
            $element_data = $element_data.replace('"name":"' + $old_name + '"', '"name":"' + $new_field_name + '"');
            $element_data_field.val($element_data);
            if ($parent.hasClass('editing')) {
                $parent.find('.super-title > input').val($new_field_name);
            }
        });

        $doc.on('change', '.super-create-form .super-element-header .super-element-label > input', function () {
            var $this = $(this);
            var $value = $this.val();
            var $span = $this.parent().children('span');
            $span.html($value);
            $this.attr('value', $value);
            var $width = $span.outerWidth(true);
            $this.parent().css('width', $width + 'px').css('margin-left', '-' + ($width / 2) + 'px');
            var $parent = $this.parents('.super-element:eq(0)');
            var $data = $parent.children('textarea[name="element-data"]').val();
            var $tag = $parent.data('shortcode-tag');
            $data = JSON.parse($data);
            if (($tag == 'column') || ($tag == 'multipart')) {
                $data.label = $value;
            }
            $data = JSON.stringify($data);
            $parent.children('textarea[name="element-data"]').val($data);
            SUPER.regenerate_element_inner(true, []);
        });

        $doc.on('click', '.super-element-actions .super-duplicate', function () {
            var $parent = $(this).parents('.super-element:eq(0)');
            $parent.find('.tooltip').remove();
            var $new = $parent.clone();
            var clone = $new[0];

            // @since 3.7.0 - bug fix remove editing class when duplicating column with active editing element inside
            $new.find('.super-element.editing').removeClass('editing');

            // @since 3.7.0 - automatically rename duplicated fields for more user-friendly work flow
            $new.find('.super-shortcode-field').each(function () {
                var $field = $(this)
                var $old_name = $field.attr('name');
                if($field.hasClass('super-fileupload')){
                    $field = $field.next()
                    $old_name = $field.attr('name');
                }
                var $new_field_name = SUPER.generate_new_field_name();
                $field.attr('name', $new_field_name);
                var $parent = $field.parents('.super-element:eq(0)');
                $parent.find('.super-title > input').val($new_field_name);
                var $element_data_field = $parent.children('textarea[name="element-data"]');
                var $element_data = $element_data_field.val();
                $element_data = $element_data.replace('"name":"' + $old_name + '"', '"name":"' + $new_field_name + '"');
                $element_data_field.val($element_data);
            });
            $new.removeClass('editing');
            $new.insertAfter($parent);

            // Signatures
            var i, nodes = clone.querySelectorAll('.super-signature .super-signature-canvas > canvas');
            for( i=0; i < nodes.length; i++){
                nodes[i].parentNode.closest('.super-signature').classList.remove('super-initialized');
                //nodes[i].remove();
            }
            // Timepickers
            nodes = clone.querySelectorAll('.super-timepicker.ui-timepicker-input');
            for (i = 0; i < nodes.length; i++) { 
                nodes[i].classList.remove('ui-timepicker-input');
            }
            // Colorpickers
            nodes = clone.querySelectorAll('.sp-replacer.super-forms');
            for (i = 0; i < nodes.length; i++) { 
                nodes[i].remove();
            }
            // Datepickers
            nodes = clone.querySelectorAll('.super-picker-initialized');
            for (i = 0; i < nodes.length; i++) { 
                nodes[i].classList.remove('super-picker-initialized');
                nodes[i].classList.remove('hasDatepicker');
                nodes[i].id = '';
                //SUPER.init_datepicker();
            }
            $new.slideUp(0);
            $new.slideDown(300);
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner(true, []);
        });

        // @since 4.6.0 - transfer this element to either a different location in the current form or to a completely different form (works cross-site)
        $doc.on('click', '.super-element-actions .super-transfer', function () {
            var $parent = $(this).parents('.super-element:eq(0)');
            $parent.find('.tooltip').remove();
            var $node = $parent.clone();
            $node.find('.super-element.editing').removeClass('editing');
            $node.removeClass('editing');
            SUPER.set_session_data('_super_transfer_element_html', $node[0].outerHTML, 'local');
        });
        // @since 4.6.0 - transfer this element to either a different location in the current form or to a completely different form (works cross-site)
        $doc.on('click', '.super-element-actions .super-transfer-drop', function () {
            var $html = SUPER.get_session_data('_super_transfer_element_html', 'local');
            var $parent = $(this).parents('.super-element:eq(0)');
            $($html).insertAfter($parent);
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner(true, []);
            localStorage.removeItem('_super_transfer_element_html');
            $('.super-preview-elements').removeClass('super-transfering');
        });
        $doc.on('click', '.super-preview-elements.super-transfering', function (e) {
            if ($(e.target).hasClass('super-preview-elements')) {
                var $html = SUPER.get_session_data('_super_transfer_element_html', 'local');
                $($html).appendTo($(this));
                SUPER.init_drag_and_drop();
                SUPER.regenerate_element_inner(true, []);
                localStorage.removeItem('_super_transfer_element_html');
                $('.super-preview-elements').removeClass('super-transfering');
            }
        });

        // @since 3.7.0 - change unique field name on the fly
        $doc.on('focus', '.super-element-header .super-title > input', function () {
            this.value = this.value.split('[')[0];
        });
        $doc.on('blur', '.super-element-header .super-title > input', function () {
            SUPER.check_for_unique_field_name(this.closest('.super-element'), true);
        });
        $doc.on('keyup change', '.super-element-header .super-title > input', function () {
            var $this = $(this);
            var $parent = $this.parents('.super-element:eq(0)');
            var $field = $parent.find('.super-shortcode-field');
            var $old_name = $field.attr('name');
            if($field.hasClass('super-fileupload')){
                $field = $field.next()
                $old_name = $field.attr('name');
            }
            var $new_field_name = SUPER.formatUniqueFieldName($this.val());
            $this.val($new_field_name);
            $field.attr('name', $new_field_name);
            var $element_data_field = $parent.children('textarea[name="element-data"]');
            var $element_data = $element_data_field.val();
            $element_data = $element_data.replace('"name":"' + $old_name + '"', '"name":"' + $new_field_name + '"');
            $element_data_field.val($element_data);
            if ($parent.hasClass('editing')) {
                $('.super-elements-container .super-field .super-element-field[name="name"]').val($new_field_name);
            }
            // Don't update history for just field name changes
            SUPER.regenerate_element_inner(false, []);
        });

        $doc.on('click', '.super-element-actions .super-minimize', function () {
            var $this = $(this).parents('.super-element:eq(0)');
            var $minimized = $this.attr('data-minimized');
            if ($minimized === 'undefined') $minimized = 'no';
            if ($minimized == 'yes') {
                $this.attr('data-minimized', 'no').removeClass('super-minimized');
                $(this).tooltipster('content', 'Minimize');
            } else {
                $this.attr('data-minimized', 'yes').addClass('super-minimized');
                $(this).tooltipster('content', 'Maximize');
            }
            SUPER.init_resize_element_labels();
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner(true, []);
        });
        $doc.on('click', '.super-element-actions .super-delete', function () {
            $(this).parents('.super-element:eq(0)').remove();
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner(true, []);
            cancel_update();
        });
        $doc.on('click', '.super-element > .super-element-header > .super-resize > span', function () {
            var $parent = $(this).parents('.super-element:eq(0)');
            var $data = $parent.find('textarea[name="element-data"]').val();
            $data = JSON.parse($data);
            var $size = $data.size;
            if (typeof $parent.attr('data-size') !== 'undefined') {
                $size = $parent.attr('data-size');
            }
            var $sizes = {
                '1/1': 'super_one_full',
                '4/5': 'super_four_fifth',
                '3/4': 'super_three_fourth',
                '2/3': 'super_two_third',
                '3/5': 'super_three_fifth',
                '1/2': 'super_one_half',
                '2/5': 'super_two_fifth',
                '1/3': 'super_one_third',
                '1/4': 'super_one_fourth',
                '1/5': 'super_one_fifth'
            };
            var $keys = ['1/1', '4/5', '3/4', '2/3', '3/5', '1/2', '2/5', '1/3', '1/4', '1/5'];
            var $start = $size;
            var $next = $keys[($.inArray($start, $keys) + 1) % $keys.length];
            var $prev = $keys[($.inArray($start, $keys) - 1 + $keys.length) % $keys.length];
            if ($(this).hasClass('smaller')) {
                if ($size == '1/5') {
                    return false;
                }
                $parent.attr('data-size', $next);
                $parent.removeClass($sizes[$start]).addClass($sizes[$next]);
                $parent.children('.super-element-header').find('.super-resize > .current').html($next);
            }
            if ($(this).hasClass('bigger')) {
                if ($size == '1/1') {
                    return false;
                }
                $parent.attr('data-size', $prev);
                $parent.removeClass($sizes[$start]).addClass($sizes[$prev]);
                $parent.children('.super-element-header').find('.super-resize > .current').html($prev);
            }
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner(true, []);
        });
        $doc.on('click', '.super-switch-forms', function () {
            var $this = $(this);
            if ($this.hasClass('super-active')) {
                $this.children('.fa').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $this.removeClass('super-active');
                $this.children('ul').slideUp(300);
            } else {
                $this.children('.fa').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $this.addClass('super-active');
                $this.children('ul').slideDown(300);
            }
        });
        $doc.on('mouseleave', '.super-switch-forms ul', function () {
            var $this = $(this).parent();
            $this.children('.fa').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            $this.removeClass('super-active');
            $this.children('ul').slideUp(300);
        });

        $doc.on('click', '.super-multi-items .super-add', function () {
            var $this = $(this);
            var $parent = $this.parents('.super-multi-items:eq(0)');

            var $fields = {};
            $parent.find('select').each(function () {
                $fields[$(this).attr('name')] = $(this).val();
            });

            var $item = $parent.clone();
            $item.find('select').each(function () {
                $(this).val($fields[$(this).attr('name')]);
            });

            $item = $item.insertAfter($parent);
            $item.find('.super-initialized').removeClass('super-initialized');
            $item.find('input[type="radio"]').prop('checked', false);
            if ($parent.find('.super-multi-items').length > 1) {
                $parent.find('.super-delete').css('visibility', '');
            } else {
                $parent.find('.super-delete').css('visibility', 'hidden');
            }
            if (!$parent.hasClass('super-conditional-item')) {
                SUPER.init_image_browser();
            }
        });

        // Before updating, check for errors
        SUPER.update_element_check_errors = function () {
            var $error = false;
            // First check for empty required fields
            $('.super-element-settings .super-element-field[required="true"]').each(function () {
                var $this = $(this);
                if ($this.val() === '') {
                    var $hidden = false;
                    $this.parents('.super-field.super-filter').each(function () {
                        if ($(this).css('display') == 'none') {
                            $hidden = true;
                        }
                    });
                    if ($hidden === false) {
                        $error = true;
                        $this.parents('.super-field').addClass('super-error');
                    }
                } else {
                    $this.parents('.super-field').removeClass('super-error');
                }
            });
            if ($error === true) {
                var $first_error = $('.super-element-settings .super-error:eq(0)');
                var $container = $first_error.parents('.super-elements-container');
                $container.find('.tab-content.super-active').removeClass('super-active');
                var $parent = $first_error.parents('.tab-content:eq(0)');
                $parent.addClass('super-active');
                var $position = $first_error.position().top + $parent.scrollTop() - $first_error.outerHeight(true) - 25;
                $parent.animate({
                    scrollTop: $position
                }, 0);
                return false;
            }

            // Check if the conditional logic field pointer is pointing to it's self
            // This isn't logical to do and not possible either so we should notify the user about this
            // If a conditional field pointer points to the field itself it would result in a Stack Overflow
            var $continue = true;
            $(this).parents('.super-elements-container:eq(0)').find('.super-multi-items').each(function () {
                if (!SUPER.update_multi_items($(this))) {
                    $continue = false;
                }
            });
            if (!$continue) {
                alert('Conditional field pointer may not point to the field itself. This would create an infinite loop and results in a stack overflow. Please choose a different field for your conditional logic!');
                return false;
            }
        };
        // Add loading state to update button
        SUPER.update_element_btn_loading = function ($button) {
            $button.addClass('super-loading');
        };
        // Retrieve all settings and their values
        SUPER.update_element_get_fields = function () {
            var i, x, y,
                nodes,
                radios,
                value,
                defaultValue,
                allowEmpty,
                fields = {},
                elementField,
                elementFields;

            nodes = document.querySelectorAll('.super-element-settings .super-field');
            for (i = 0; i < nodes.length; ++i) {
                if(!nodes[i].classList.contains('super-hidden')){
                    // Find element field
                    elementFields = nodes[i].querySelectorAll('.super-element-field');
                    for (x = 0; x < elementFields.length; ++x) {
                        elementField = elementFields[x];
                        if(elementField.tagName==='TEXTAREA' && tinymce.get('super-tinymce-instance-'+elementField.name)){
                            value = tinymce.get('super-tinymce-instance-'+elementField.name).getContent();
                        }else{
                            value = elementField.value;
                        }
                        if(elementField.type=='radio'){
                            radios = nodes[i].querySelectorAll('input[name="'+elementField.name+'"]');
                            for (y = 0; y < radios.length; ++y) {
                                if (radios[y].checked) {
                                    value = radios[y].value;
                                    break;
                                }
                            }
                        }
                        defaultValue = undefined;
                        if(elementField.closest('.super-field-input')) defaultValue = elementField.closest('.super-field-input').dataset.default;
                        if( (value!=='') && (value!=defaultValue) ) {
                            if($(elementField).parents('.super-field-input:eq(0)').find('.super-multi-items').length){
                                fields[elementField.name] = $.parseJSON(value);
                            }else{
                                fields[elementField.name] = value;
                            }
                        }else{
                            if( value==='' || (elementField.name==='exclude' && value==='0') ) {
                                allowEmpty = undefined;
                                if(elementField.closest('.super-field-input')) allowEmpty = elementField.closest('.super-field-input').dataset.allowEmpty;
                                if( typeof allowEmpty !== 'undefined' ) {
                                    fields[elementField.name] = value;
                                }
                            } 
                        }

                    }

                }
            }
            return fields;
        };
        // Check if 'name' setting is not empty
        SUPER.update_element_name_required = function ($fields, $button) {
            if ((typeof $fields.name !== 'undefined') && ($fields.name === '')) {
                $button.removeClass('super-loading');
                $('.super-element-settings .super-element-field[name="name"]').css('border', '1px solid #ff9898').css('background-color', '#ffefef');
                alert(super_create_form_i18n.alert_empty_field_name);
                return false;
            }
            $('.super-element-settings .super-element-field[name="name"]').css('border', '').css('background-color', '');

        };
        // Update the currently editing field element data
        SUPER.update_element_update_data = function ($fields) {
            var $element = $('.super-element.editing');
            var $element_data;
            // Always get possible translation data from current element
            $element_data = JSON.parse($element.children('textarea[name="element-data"]').val());
            // Check if in translation mode
            if (SUPER.ui.i18n.translating) {
                // First grab current field data, then add the translation data
                if (typeof $element_data.i18n === 'undefined' || $element_data.i18n.length === 0) {
                    $element_data.i18n = {};
                }
                $element_data.i18n[$('.super-preview-elements').attr('data-i18n')] = $fields;
                $fields = $element_data;
            } else {
                // Always append any existing translation data to the current fields array
                if ($element_data && typeof $element_data.i18n !== 'undefined') {
                    $fields.i18n = $element_data.i18n;
                }
            }
            $element_data = JSON.stringify($fields).replace(/\\+"/g, '\\"');
            $element.children('textarea[name="element-data"]').val($element_data);
            return $element;
        };
        // Update settings to element-data
        SUPER.update_element_data = function ($button) {
            // Before updating, check for errors
            SUPER.update_element_check_errors();
            // Add loading state to update button
            SUPER.update_element_btn_loading($button);
            // Retrieve all settings and their values
            var $fields = SUPER.update_element_get_fields();
            // Check if 'name' setting is not empty
            SUPER.update_element_name_required($fields, $button);
            // Update the currently editing field element data
            var $element = SUPER.update_element_update_data($fields);
            return $element;
        };
        // Push updates for saving (no need to press Update button)
        SUPER.update_element_push_updates = function () {
            // Retrieve all settings and their values
            var $fields = SUPER.update_element_get_fields();
            // Update the currently editing field element data
            SUPER.update_element_update_data($fields);
        };

        $doc.on('click', '.super-element-settings .super-update-element', function () {
            // Update element data (json code)
            // This json code holds all the settings for this specific element
            var $button = $(this);
            var $element = SUPER.update_element_data($button);
            // Retrieve all settings and their values
            var $fields = SUPER.update_element_get_fields();

            var $tag = $element.data('shortcode-tag');
            var $group = $element.data('group');
            var $i18n = $('.super-preview-elements').attr('data-i18n');

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (this.readyState == 4) {
                    // Success:
                    if (this.status == 200) {
                        var response = this.responseText;
                        // If the response is a valid JSON string, then we are updating the TAB element
                        // In this case we need to do a special thing which is only updating the TABS headers
                        // We will not update the inner elements, or in other words the TAB content wrappers
                        try {
                            var json = JSON.parse(response);
                            if (typeof json.builder !== 'undefined') {
                                var $from = json.builder[0],
                                    $to = json.builder[1];
                                // If changing TAB to TAB layout (no change) then only update the TAB menu/header
                                if ($from == 'tabs' && $to == 'tabs') {
                                    $element.children('.super-element-inner').children('.super-tabs').children('.super-tabs-menu').html(json.html);
                                }
                                // If changing Accordion to Accordion layout (no change) then only update the Accordion headers
                                if ($from == 'accordion' && $to == 'accordion') {
                                    $.each(json.header_items, function (key, value) {
                                        var $item = $element.children('.super-element-inner').children('.super-tabs').children('.super-accordion-item:eq(' + key + ')');
                                        if (typeof $item !== 'undefined') {
                                            // Update Title
                                            $item.find('.super-accordion-title').html(value.title);
                                            // Update Description
                                            $item.find('.super-accordion-desc').html(value.desc);
                                            // Update Image (icon)
                                        }
                                    });
                                    // Check if we need to delete any items that no longer exist
                                    var i = 0;
                                    var total = json.header_items.length;
                                    $element.children('.super-element-inner').children('.super-tabs').children('.super-accordion-item').each(function () {
                                        i++;
                                        if (i < total + 1) return true;
                                        $(this).remove();
                                    });
                                }
                            }
                        } catch (_e) {
                            if (!$element.children('.super-element-inner').hasClass('super-dropable')) {
                                // When no inner elements, just update the element itself
                                $element.children('.super-element-inner').html(response);
                            }
                        }
                        // If i18n is set, update the "language-changed" attribute to "true"
                        if (typeof $i18n !== 'undefined') {
                            $('.super-preview-elements').attr('data-language-changed', 'true');
                        }
                    }
                    // Complete:
                    if ($tag == 'column') {
                        var $sizes = {
                            '1/1': 'super_one_full',
                            '4/5': 'super_four_fifth',
                            '3/4': 'super_three_fourth',
                            '2/3': 'super_two_third',
                            '3/5': 'super_three_fifth',
                            '1/2': 'super_one_half',
                            '2/5': 'super_two_fifth',
                            '1/3': 'super_one_third',
                            '1/4': 'super_one_fourth',
                            '1/5': 'super_one_fifth'
                        };
                        if(!$fields.size) $fields.size = '1/1';
                        var className = 'super-element super-column drop-here ui-sortable-handle ' + $sizes[$fields.size];
                        if($fields.align_elements && $fields.align_elements!==''){
                             className += ' super-builder-align-inner-elements-' + $fields.align_elements;
                        }
                        if($fields.duplicate && $fields.duplicate==='enabled'){
                            className += ' super-duplicate-column-fields';
                        }
                        className += ' editing';
                        $element.attr('class', className);
                        if($fields.duplicate && $fields.duplicate==='enabled'){
                            SUPER.check_for_unique_field_name($element[0], true);
                        }
                        $element.attr('data-size', $fields.size).find('.super-element-header .super-resize .current').html($fields.size);
                    }
                    SUPER.regenerate_element_inner(true, []);
                    $button.removeClass('super-loading');
                }
            };
            xhttp.onerror = function () {
                console.log(this);
                console.log("** An error occurred during the transaction");
            };
            xhttp.open("POST", ajaxurl, true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
            // If TAB element, check what layout it has
            var $builder = '';
            var $layout = 'tabs';
            if ($tag == 'tabs') {
                if (typeof $fields.layout !== 'undefined') {
                    $layout = $fields.layout;
                }
                var $shortcode = $element.children('.super-element-inner').children('.super-shortcode');
                // Tabs
                if ($shortcode.hasClass('super-layout-tabs')) {
                    $builder = 'tabs;' + $layout; // [FROM];[TO]
                }
                // Accordion
                if ($shortcode.hasClass('super-layout-accordion')) {
                    $builder = 'accordion;' + $layout; // [FROM];[TO]
                }
            }
            var params = {
                action: 'super_get_element_builder_html',
                tag: $tag,
                group: $group,
                builder: ($tag == 'tabs' ? $builder : 0),
                data: $fields,
                translating: SUPER.ui.i18n.translating,
                i18n: $i18n,
                form_id: $('.super-create-form input[name="form_id"]').val()
            };
            params = $.param(params);
            xhttp.send(params);
        });

        function cancel_update() {
            $('.super-elements.super-active, .super-element.super-active').removeClass('super-active');
            $('.super-preview-elements .super-element').removeClass('editing');
        }

        $doc.on('click', '.super-element-settings .super-cancel-update', function () {
            cancel_update();
        });

        $doc.on('change click blur keyup keydown focus', '.super-multi-items *', function () {
            SUPER.update_multi_items($(this));
        });

        $doc.on('click', '.super-checkbox input[type="checkbox"]', function () {
            var i, selected = '', counter = 0,
                parent = this.closest('.super-checkbox'),
                field = parent.parentNode.querySelector('.super-element-field'),
                nodes = parent.querySelectorAll('input[type="checkbox"]');
            if(!field) return;

            for( i=0; i < nodes.length; i++ ) {
                if (nodes[i].checked) {
                    if (counter === 0) {
                        selected += nodes[i].value;
                    } else {
                        selected += ',' + nodes[i].value;
                    }
                    counter++;
                }
            }
            field.value = selected;

            if(this.closest('.super-form-settings')){
                SUPER.init_field_filter_visibility($(this.closest('.super-field')));
            }else{
                SUPER.init_field_filter_visibility($(this.closest('.super-field')), 'element_settings');
            }
        });

        $doc.on('click', '.super-multi-items .super-sorting span.up i', function () {
            var $parent = $(this).parents('.super-field-input:eq(0)');
            var $count = $parent.find('.super-multi-items').length;
            if ($count > 1) {
                var $this = $(this).parents('.super-multi-items:eq(0)');
                var $prev = $this.prev();
                var $index = $this.index();
                if ($index > 0) {
                    $this.insertBefore($prev);
                } else {
                    $this.insertAfter($parent.find('.super-multi-items').last());
                }
            }
        });

        $doc.on('click', '.super-multi-items .super-sorting span.down i', function () {
            var $parent = $(this).parents('.super-field-input:eq(0)');
            var $count = $parent.find('.super-multi-items').length;
            if ($count > 1) {
                var $this = $(this).parents('.super-multi-items:eq(0)');
                var $next = $this.next();
                var $index = $this.index();
                if ($index + 1 == $count) {
                    $this.insertBefore($parent.find('.super-multi-items').first());
                } else {
                    $this.insertAfter($next);
                }
            }
        });

        $doc.on('click', '.super-multi-items.super-dropdown-item input[type="checkbox"]', function () {
            var $prev = $(this).attr('data-prev');
            if ($prev == 'true') {
                $(this).prop('checked', false).attr('data-prev', 'false');
            } else {
                $(this).prop('checked', true).attr('data-prev', 'true');
            }
        });

        $doc.on('click', '.super-multi-items.super-dropdown-item input[type="radio"]', function () {
            var $prev = $(this).attr('data-prev');
            $(this).parents('.super-field-input:eq(0)').find('input[type="radio"]').prop('checked', false).attr('data-prev', 'false');
            if ($prev == 'true') {
                $(this).prop('checked', false).attr('data-prev', 'false');
            } else {
                $(this).prop('checked', true).attr('data-prev', 'true');
            }
        });

        $doc.on('click', '.super-create-form .super-actions .super-clear', function () {
            var $clear = confirm(super_create_form_i18n.confirm_clear_form);
            if ($clear === true) {
                document.querySelector('.super-raw-code-form-elements > textarea').value = '';
                $('.super-preview-elements').html('');
                $('.super-element.super-element-settings .super-elements-container').html('<p>' + super_create_form_i18n.not_editing_an_element + '</p>');
            }
        });

        $doc.on('click', '.super-create-form .super-actions .super-delete', function () {
            var $delete = confirm(super_create_form_i18n.confirm_deletion);
            if ($delete === true) {
                var $this = $(this);
                $this.html('<i class="fas fa-trash-alt"></i>' + super_create_form_i18n.deleting);
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_delete_form',
                        form_id: $('.super-create-form input[name="form_id"]').val(),
                    },
                    success: function () {
                        $this.html('<i class="fas fa-check"></i>Deleted!');
                        window.location.href = "edit.php?post_type=super_form";
                    }
                });
            }
        });

        $doc.on('click', '.super-element-actions .super-edit', function () {
            cancel_update();
            $('.super-elements').addClass('super-active');
            var $parent = $(this).parents('.super-element:eq(0)');
            if ($parent.hasClass('editing')) {
                return false;
            }
            var $data = $parent.children('textarea[name="element-data"]').val();
            var $tag = $parent.data('shortcode-tag');
            var $group = $parent.data('group');
            $data = JSON.parse($data);
            if ($tag == 'column') {
                $data.size = $parent.attr('data-size');
            }
            $('.super-element-settings').addClass('super-active');
            var $target = $('.super-element-settings > .super-elements-container');
            $target.html('');
            $('.super-preview-elements .super-element').removeClass('editing');
            $parent.addClass('editing');
            $target.addClass('super-loading');

            // Check if in translation mode
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (this.readyState == 4) {
                    // Success:
                    if (this.status == 200) {
                        $target.html(this.responseText);
                        init_form_settings_container_heights();
                        SUPER.initTinyMCE('.super-element-settings .super-textarea-tinymce', true);
                        // Open up last element settings tab
                        $activeElementSettingsTab = SUPER.get_session_data('_super_builder_last_active_element_settings_tab')
                        if($activeElementSettingsTab){
                            $node = $('.super-element-settings .super-elements-container .tab-content[data-key="' + $activeElementSettingsTab + '"]');
                            if($node.length){
                                $('.super-element-settings .super-elements-container .tab-content.super-active').removeClass('super-active');
                                $node.addClass('super-active');
                                $('.super-element-settings-tabs > select > option[selected]').prop({selected: false});
                                $('.super-element-settings-tabs > select > option[data-key="' + $activeElementSettingsTab + '"]').prop({selected: true});
                            }
                        }
                    }
                    // Complete:
                    SUPER.init_previously_created_fields();
                    SUPER.init_slider_field();
                    SUPER.init_tooltips();
                    SUPER.init_docs();
                    SUPER.init_image_browser();
                    SUPER.init_color_pickers();
                    SUPER.init_field_filter_visibility(undefined, 'element_settings');
                    $('.super-element.super-element-settings .super-elements-container').removeClass('super-loading');
                }
            };
            xhttp.onerror = function () {
                console.log(this);
                console.log("** An error occurred during the transaction");
            };
            xhttp.open("POST", ajaxurl, true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
            if($tag==='html' && (typeof $data.name === 'undefined' || $data.name === '')){
                $data.name = SUPER.generate_new_field_name();
            }
            var params = {
                action: 'super_load_element_settings',
                form_id: $('.super-create-form input[name="form_id"]').val(),
                tag: $tag,
                group: $group,
                data: $data,
                translating: SUPER.ui.i18n.translating,
                i18n: $('.super-preview-elements').attr('data-i18n')
            };
            params = $.param(params);
            xhttp.send(params);
            return false;
        });

        $doc.on('click', '.super-create-form .super-actions .super-save', function () {
            if($('.super-tab-code.super-active').length){
                alert(super_create_form_i18n.alert_save_not_allowed_code_tab);
                return false;
            }
            var $this = $(this);
            clearTimeout(SUPER.new_version_check);
            SUPER.before_save_form(function(){
                SUPER.save_form($this, undefined, undefined, undefined, function(){
                    clearTimeout(SUPER.new_version_check);
                    SUPER.new_version_check = setTimeout(function(){
                        checkNewerForVersion();
                    }, 30000);
                });
            });
        });

        $doc.on('click', '.super-create-form .super-actions .super-preview', function () {
            var $this = $('.super-create-form .super-actions .super-preview:eq(3)');
            if ($(this).hasClass('super-mobile')) {
                $('.super-live-preview').removeClass('super-tablet');
                $('.super-create-form .super-actions .super-preview.super-tablet').removeClass('super-active');
                $('.super-create-form .super-actions .super-preview.super-desktop').removeClass('super-active');
                $(this).addClass('super-active');
                $('.super-live-preview').addClass('super-mobile');
                if (!$this.hasClass('super-active')) {
                    $this.html('Loading...');
                    clearTimeout(SUPER.new_version_check);
                    SUPER.before_save_form(function(){
                        SUPER.save_form($('.super-actions .super-save'), 1, undefined, undefined, function () {
                            clearTimeout(SUPER.new_version_check);
                            SUPER.new_version_check = setTimeout(function(){
                                checkNewerForVersion();
                            }, 30000);
                            $('.super-tabs-content').css('display', 'none');
                        });
                    });
                    return false; // Do not execute responsiveness yet, must first save form then reload it then apply responsiveness
                }
                SUPER.init_super_responsive_form_fields();
                return false;
            }
            if ($(this).hasClass('super-tablet')) {
                $('.super-live-preview').removeClass('super-mobile');
                $('.super-create-form .super-actions .super-preview.super-mobile').removeClass('super-active');
                $('.super-create-form .super-actions .super-preview.super-desktop').removeClass('super-active');
                $(this).addClass('super-active');
                $('.super-live-preview').addClass('super-tablet');
                if (!$this.hasClass('super-active')) {
                    $this.html('Loading...');
                    clearTimeout(SUPER.new_version_check);
                    SUPER.before_save_form(function(){
                        SUPER.save_form($('.super-actions .super-save'), 1, undefined, undefined, function () {
                            clearTimeout(SUPER.new_version_check);
                            SUPER.new_version_check = setTimeout(function(){
                                checkNewerForVersion();
                            }, 30000);
                            $('.super-tabs-content').css('display', 'none');
                        });
                    });
                    return false; // Do not execute responsiveness yet, must first save form then reload it then apply responsiveness
                }
                SUPER.init_super_responsive_form_fields();
                return false;
            }
            if ($(this).hasClass('super-desktop')) {
                $('.super-live-preview').removeClass('super-tablet');
                $('.super-live-preview').removeClass('super-mobile');
                $('.super-create-form .super-actions .super-preview.super-mobile').removeClass('super-active');
                $('.super-create-form .super-actions .super-preview.super-tablet').removeClass('super-active');
                $(this).addClass('super-active');
                if (!$this.hasClass('super-active')) {
                    $this.html('Loading...');
                    clearTimeout(SUPER.new_version_check);
                    SUPER.before_save_form(function(){
                        SUPER.save_form($('.super-actions .super-save'), 1, undefined, undefined, function () {
                            clearTimeout(SUPER.new_version_check);
                            SUPER.new_version_check = setTimeout(function(){
                                checkNewerForVersion();
                            }, 30000);
                            $('.super-tabs-content').css('display', 'none');
                        });
                    });
                    return false; // Do not execute responsiveness yet, must first save form then reload it then apply responsiveness
                }
                SUPER.init_super_responsive_form_fields();
                return false;
            }
            if (!$this.hasClass('super-active')) {
                $this.html('Loading...');
                clearTimeout(SUPER.new_version_check);
                SUPER.before_save_form(function(){
                    SUPER.save_form($('.super-actions .super-save'), 1, undefined, undefined, function () {
                        clearTimeout(SUPER.new_version_check);
                        SUPER.new_version_check = setTimeout(function(){
                            checkNewerForVersion();
                        }, 30000);
                        $('.super-tabs-content').css('display', 'none');
                    });
                });
            } else {
                $('.super-tabs-content').css('display', '');
                $('.super-live-preview').css('display', 'none');
                $('.super-live-preview').html('');
                $this.html('Preview').removeClass('super-active');
            }
        });

        // @since 3.8.0 - reset user submission counter
        $doc.on('click', '.super-reset-user-submission-counter', function () {
            var $reset = confirm(super_create_form_i18n.confirm_reset_submission_counter);
            if ($reset === true) {
                var $button = $(this);
                $button.addClass('super-loading');
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_reset_user_submission_counter',
                        form_id: $('.super-create-form input[name="form_id"]').val()
                    },
                    complete: function () {
                        $button.removeClass('super-loading');
                    }
                });
            }
        });

        // @since 3.4.0 - reset submission counter
        $doc.on('click', '.super-reset-submission-counter', function () {
            var $reset = confirm(super_create_form_i18n.confirm_reset_submission_counter);
            if ($reset === true) {
                var $button = $(this);
                $button.addClass('super-loading');
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_reset_submission_counter',
                        counter: $('.super-create-form input[name="form_locker_submission_reset"]').val(),
                        form_id: $('.super-create-form input[name="form_id"]').val()
                    },
                    complete: function () {
                        $button.removeClass('super-loading');
                    }
                });
            }
        });


        // @since   1.0.6
        $doc.on('focus', '.super-get-form-shortcodes', function () {
            var $this = $(this);
            $this.select();
            // Work around Chrome's little problem
            $this.mouseup(function () {
                // Prevent further mouseup intervention
                $this.unbind("mouseup");
                return false;
            });
        });

        // @since 4.0.0 - export single form settings and elements
        $doc.on('click', '.super-export-import-single-form .super-export', function () {
            var $button = $(this);
            $button.addClass('super-loading');
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'super_export_single_form',
                    form_id: $('.super-create-form input[name="form_id"]').val(),
                    formElements: document.querySelector('.super-raw-code-form-elements > textarea').value,
                    formSettings: SUPER.get_form_settings(),
                    emailsSettings: SUPER.get_emails_settings(),
                    themeSettings: SUPER.get_theme_settings(),
                    triggerSettings: SUPER.get_trigger_settings(),
                    woocommerceSettings: SUPER.get_woocommerce_settings(),
                    listingsSettings: SUPER.get_listings_settings(),
                    pdfSettings: SUPER.get_pdf_settings(),
                    stripeSettings: SUPER.get_stripe_settings(),
                    translationSettings: SUPER.get_translation_settings()
                },
                success: function (data) {
                    var sfdlfi = data;
                    window.open(sfdlfi);
                },
                error: function () {
                    alert(super_create_form_i18n.export_form_error);
                },
                complete: function () {
                    $button.removeClass('super-loading');
                }
            });
        });

        // @since 4.0.0 - import single form settings and elements
        $doc.on('click', '.super-export-import-single-form .super-import', function () {
            var $confirm = confirm(super_create_form_i18n.confirm_import);
            if ($confirm === true) {
                var $button = $(this);
                var $parent = $button.parents('.super-field:eq(0)');
                var $form_id = $('.super-create-form input[name="form_id"]').val();

                var $file_id = $parent.find('.file-preview > li').attr('data-file');
                if (typeof $file_id === 'undefined') {
                    alert(super_create_form_i18n.import_form_choose_file);
                    return false;
                }

                var $settings = $parent.find('input[name="import-settings"]').is(':checked');
                var $elements = $parent.find('input[name="import-elements"]').is(':checked');
                var $translations = $parent.find('input[name="import-translations"]').is(':checked');
                var $secrets = $parent.find('input[name="import-secrets"]').is(':checked');
                if ($settings === false && $elements === false && $translations === false) {
                    alert(super_create_form_i18n.import_form_select_option);
                    return false;
                }

                $button.addClass('super-loading');
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_import_single_form',
                        form_id: $form_id,
                        file_id: $file_id,
                        settings: $settings,
                        elements: $elements,
                        translations: $translations,
                        secrets: $secrets
                    },
                    success: function (data) {
                        data = $.parseJSON(data);
                        if (data.error) {
                            alert(data.msg);
                        } else {
                            if ($form_id === 0 || $form_id === '') {
                                window.location.href = "admin.php?page=super_create_form&id=" + data;
                            } else {
                                location.reload();
                            }
                        }
                    },
                    error: function () {
                        alert(super_create_form_i18n.import_form_error);
                    }
                });
            }
        });

        // @since 4.0.0 - reset single form settings
        $doc.on('click', '.super-export-import-single-form .super-reset-global-settings', function () {
            var $confirm = confirm(super_create_form_i18n.confirm_reset);
            if ($confirm === true) {
                var $button = $(this);
                $button.addClass('super-loading');
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_reset_form_settings',
                        form_id: $('.super-create-form input[name="form_id"]').val(),
                    },
                    success: function (data) {
                        var href = window.location.href;
                        var page = href.substr(href.lastIndexOf('/') + 1);
                        var str2 = "admin.php?page=super_create_form&id";
                        if (page.indexOf(str2) == -1) {
                            window.location.href = "admin.php?page=super_create_form&id=" + data;
                        } else {
                            location.reload();
                        }
                    },
                    complete: function () {
                        $button.removeClass('super-loading');
                    }
                });
            }
        });

        // @since   1.0.6
        $(window).on('load resize', function () {
            init_form_settings_container_heights();
        });

        $(window).on('scroll', function () {
            var $window_width = $(window).outerWidth(true);
            if ($window_width > 1145) {
                var $scrolled = $(window).scrollTop();
                $('.super-actions, .super-elements').css('transform','translateY('+$scrolled+'px)');
            }
        });

        function init_form_settings_container_heights() {
            var $window_width = $(window).outerWidth(true);
            if ($window_width > 1145) {
                var windowHeight = $(window).outerHeight(true);
                var actions = $('.super-actions').outerHeight(true);
                var elementsHeight = 0;
                $('.super-elements .super-element h3').each(function(){
                    elementsHeight += $(this).outerHeight(true);
                })
                $('.super-elements-container > .tab-content').css('max-height', windowHeight-(elementsHeight+actions)-167);
            }
        }

        SUPER.regenerate_element_inner(false, []);
        SUPER.set_session_data('_super_builder_has_unsaved_changes', false);

        // @since 4.0.0 - hints/introduction
        var $skip = $('input[name="super_skip_tutorial"]').val();
        var $elements_found = $('.super-preview-elements .super-element').length;
        if (($skip != 'true') && (!$elements_found)) {
            var $git = 'https://renstillmann.github.io/super-forms/#/';
            var $timeout = 0;
            var $margin = 0;
            var $timeout_s = 400;
            var $event = 'next';
            var $showSkip = false;
            var $showNext = true;
            var $tags_allowed = '<span class="super-tip">You are allowed to use {tags} for this setting,<br />for more information about tags refer to the documentation section:<br /><a target="blank" href="' + $git + 'tags-system">Tags system</a></span>';

            // Check if field `wizard_title` exists
            if ($('input[name="wizard_title"]').length) {
                $super_hints = new SUPER.EnjoyHint({});
                $super_hints_steps = [{
                        selector: '.enjoyhint_close_btn',
                        shape: 'circle',
                        radius: 50,
                        nextButton: {
                            text: "Start"
                        },
                        description: '<h1>PLEASE DO NOT SKIP THIS TUTORIAL...</h1><h1>Especially when you are new to Super Forms it is recommended to read through each step!</h1><span class="super-tip">We strongly suggest you complete this step by step guide. It will help you get started nicely and quickly without any issues.</span><span class="super-tip">If you wish to skip the tutorial, you can skip it by clicking the close button</span><label class="tutorial-do-not-show-again"><input type="checkbox" name="tutorial_do_not_show_again" />Do not show me this tuturial again.</label>',
                    },
                    {
                        onBeforeStart: function () {
                            $('input[name="wizard_title"]').keydown(function (e) {
                                if ((e.keyCode == 13) && ($(this).val() !== '')) {
                                    $super_hints.trigger('next');
                                }
                            });
                        },
                        selector: 'input[name="wizard_title"]',
                        event: 'form_title_entered',
                        event_type: 'custom',
                        description: '<h1>Enter your form title and press Enter.</h1>',
                    },
                    {
                        selector: '.super-theme-style-wizard',
                        event: 'click',
                        description: '<h1>Choose a form theme.</h1>',
                    },
                    {
                        selector: '.super-field-size-wizard',
                        event: 'click',
                        description: '<h1>Now choose a field size for your form elements.</h1>',
                    },
                    {
                        selector: '.super-theme-hide-icons-wizard',
                        event: 'click',
                        description: '<h1>Select wether or not to display icons for fields</h1><span class="super-tip">Don\'t worry, you can change all these settings at a later time</span>',
                    },
                    {
                        selector: '.super-wizard-preview',
                        description: '<h1>Here you can preview your form and see how it will look on the front-end of your website</h1><span class="super-tip">Note that this is an example only, and the elements are just for demonstration purpose only. You will soon build your very own form with your own elements.</span>',
                    },
                    {
                        selector: '.super-wizard-settings .super-tabs > li:eq(1)',
                        event: 'click',
                        showNext: false,
                        description: '<h1>Click on the "Admin email" TAB to change how your admin emails are send</h1><span class="super-tip">By default this email will be send to the wordpress admin email address, but you can change this to any email address.</span>',
                    },
                    {
                        selector: 'input[name="wizard_header_to"]',
                        description: '<h1>Enter the email address where admin emails should be send to</h1>' + $tags_allowed,
                    },

                    {
                        selector: 'input[name="wizard_header_from"]',
                        description: '<h1>Enter the email address where the email was sent from</h1>' + $tags_allowed,
                    },

                    {
                        selector: 'input[name="wizard_header_from_name"]',
                        description: '<h1>Enter the name of your company or website</h1>' + $tags_allowed,
                    },

                    {
                        selector: 'input[name="wizard_header_subject"]',
                        description: '<h1>Enter the email subject that relates to this form</h1>' + $tags_allowed,
                    },
                    {
                        selector: '.super-wizard-settings .super-tabs > li:eq(2)',
                        event: 'click',
                        showNext: false,
                        description: '<h1>Click on the "Confirmation email" TAB to change how confirmation emails are send</h1><span class="super-tip">By default this email will be send to the user who submitted the form if an email address was provided</span>',
                    },
                    {
                        selector: 'input[name="wizard_confirm_to"]',
                        description: '<h1>The email address where the confirmation email should be send to.</h1><span class="super-tip">By default this is set to {email} which is a <a target="_blank" href="' + $git + 'tags-system">tag</a> that will automatically retrieve the email address that the user entered in the form.</span><span class="super-tip">You can separate emails with comma\'s to send to multiple addresses</span>' + $tags_allowed,
                    },
                    {
                        selector: 'input[name="wizard_confirm_from"]',
                        description: '<h1>Enter the email address where the email was sent from</h1>' + $tags_allowed,
                    },
                    {
                        selector: 'input[name="wizard_confirm_from_name"]',
                        description: '<h1>Enter the name of your company or website</h1>' + $tags_allowed,
                    },
                    {
                        selector: 'input[name="wizard_confirm_subject"]',
                        description: '<h1>Enter the confirmation email subject that relates to this form</h1>' + $tags_allowed,
                    },
                    {
                        selector: '.super-wizard-settings .super-tabs > li:eq(3)',
                        event: 'click',
                        showNext: false,
                        description: '<h1>Click on the "Thank you message" TAB to change the Success message</h1><span class="super-tip">This message will by default be displayed to the user after they successfully submitted the form.</span>' + $tags_allowed,
                    },
                    {
                        selector: 'input[name="wizard_form_thanks_title"]',
                        description: '<h1>The Title for your thank you message</h1>' + $tags_allowed,
                    },
                    {
                        selector: 'textarea[name="wizard_form_thanks_description"]',
                        description: '<h1>The Description for your thank you message</h1><span class="super-tip">This can be used to provide some additional information that is important to the user after they successfully submitted the form.</span>' + $tags_allowed,
                    },
                    {
                        selector: '.super-button.super-save-wizard',
                        event: 'click',
                        showNext: false,
                        description: '<h1>Click this button to save the configuration and to start building your form</h1>',
                    },
                ];
                $.each($super_hints_steps, function (key, value) {
                    if (typeof value.event === 'undefined')
                        $super_hints_steps[key].event = $event;
                    if (typeof value.showSkip === 'undefined')
                        $super_hints_steps[key].showSkip = $showSkip;
                    if (typeof value.showNext === 'undefined')
                        $super_hints_steps[key].showNext = $showNext;
                    if (typeof value.timeout === 'undefined')
                        $super_hints_steps[key].timeout = $timeout;
                    if (typeof value.margin === 'undefined')
                        $super_hints_steps[key].margin = $margin;
                });
                $super_hints.set($super_hints_steps);
                $super_hints.run();
            } else {
                $super_hints = new SUPER.EnjoyHint({});
                $super_hints_steps = [{
                        selector: '.super-preview-elements',
                        description: '<h1>This is your "Canvas" where you will be dropping all your "Elements"</h1><span class="super-tip">"Elements" can be fields, but also HTML elements and columns. Basically anything that can be dragged and dropped onto the canvas is a so called element.</span><label class="tutorial-do-not-show-again"><input type="checkbox" name="tutorial_do_not_show_again" />Do not show me this tuturial again.</label>',
                    },
                    {
                        selector: '.super-element.super-form-elements',
                        event: 'click',
                        description: '<h1>Let\'s open up the "Form Elements" panel by clicking on it</h1>',
                    },
                    {
                        selector: '.super-element.super-form-elements .super-elements-container',
                        description: '<h1>Here you can find all "Form Elements"</h1><span class="super-tip">Form elements are elements that a user can enter data into (also referred to as "user input"). As you can see there is a variety of form elements to choose from to create any type of form you want.</span>',
                        timeout: $timeout_s
                    },
                    {
                        onBeforeStart: function () {
                            $doc.find('.super-element.draggable-element.super-shortcode-email').on('mouseleave', function () {
                                if ($(this).hasClass('pep-start')) {
                                    $super_hints.trigger('next');
                                }
                            });
                        },
                        showNext: false,
                        selector: '.super-element.super-form-elements .super-elements-container .super-shortcode-email',
                        description: '<h1>Let\'s drag the "E-mail Address" field on to your "Canvas"</h1>',
                    },
                    {
                        onBeforeStart: function () {
                            // Keep searching for element until we found it, then automatically go to next step
                            var loop = setInterval(function () {
                                if ($('.super-element .super-field-wrapper input[name="email"]').length) {
                                    $super_hints.trigger('next');
                                    clearInterval(loop);
                                }
                            }, 100);
                        },
                        selector: '.super-preview-elements',
                        showNext: false,
                        description: '<h1>Drop the element on to your "Canvas"</h1>',
                    },
                    {
                        selector: '.super-element-title .super-title > input',
                        description: '<h1>Here you can quickly change the "Unique field name" of your field</h1><span class="super-tip">The unique field name relates to the user input. The {email} <a target="_blank" href="' + $git + 'tags-system">tag</a> in this case would retrieve the entered email address of the user which you can then use within your custom emails, HTML elements and <a target="_blank" href="' + $git + 'variable-fields">Variable fields</a> or inside your email subjects or other settings that support the use of {tags}.</span>',
                    },
                    {
                        onBeforeStart: function () {
                            $('.super-element-actions .super-minimize').css('pointer-events', 'none');
                            $('.super-element-actions .super-delete').css('pointer-events', 'none');
                        },
                        selector: '.super-element-actions .super-minimize',
                        radius: 10,
                        shape: 'circle',
                        description: '<h1>Here you can minimize your element</h1><span class="super-tip">This comes in handy especially when working with large forms. To benefit from this feature make sure you use columns to group your elements. With columns you can minimize a set of elements at once to make building forms even easier, faster and better manageable.</span>',
                    },
                    {
                        onBeforeStart: function () {
                            $('.super-element-actions .super-minimize').css('pointer-events', '');
                            $('.super-element-actions .super-delete').css('pointer-events', '');
                        },
                        selector: '.super-element-actions .super-delete',
                        radius: 10,
                        event: 'click',
                        shape: 'circle',
                        description: '<h1>Click on the delete icon and delete this element</h1><span class="super-tip">Removing a "Layout Element" (<a target="_blank" href="' + $git + 'columns">Column</a> or <a target="_blank" href="' + $git + 'multi-parts">Multi-part</a>) will also delete all it\'s inner elements along with it.</span><span class="super-tip">Don\'t worry, we will cover Columns and Multi-part elements very soon!</span>',
                    },
                    {
                        selector: '.super-form-history .super-undo',
                        event: 'click',
                        shape: 'circle',
                        description: '<h1>Undo previous change, click and undo your previous change to get back our element</h1><span class="super-tip">If you accidently deleted an element and want to get it back or when you moved an element where you did not want it to be moved to by accident, then you can undo your latest change with this button.</span><span class="super-tip">You can undo/redo any changes you made to your form that affected elements.</span><span class="super-tip">Please understand that the Undo/Redo buttons act like scrolling through micro back-ups of your form (which aren\'t really saved), so after a page refresh you can no longer undo any previously made changes).</span>',
                    },
                    {
                        onBeforeStart: function () {
                            $('.super-form-history .super-redo').css('pointer-events', 'none');
                        },
                        selector: '.super-form-history .super-redo',
                        shape: 'circle',
                        description: '<h1>Redo previous change</h1><span class="super-tip">Does the same thing as undo but opposite.</span>',
                    },
                    {
                        onBeforeStart: function () {
                            $('.super-form-history .super-backups').css('pointer-events', 'none');
                            $('.super-form-history .super-redo').css('pointer-events', '');
                        },
                        selector: '.super-form-history .super-backups',
                        shape: 'circle',
                        description: '<h1>Load or restore to previous backups</h1><span class="super-tip">Backups are automatically made when saving your form, so whenever you want to go back in history you can restore directly to a previous backup that was automatically made for you.</span>',
                    },
                    {
                        onBeforeStart: function () {
                            $('.super-form-history .super-backups').css('pointer-events', '');
                        },
                        selector: '.super-form-history .super-minimize-toggle',
                        event: 'click',
                        shape: 'circle',
                        description: '<h1>Let\'s minimize all our elements, we only have 1 element but for the demonstration purpose this doesn\'t matter right now ;)</h1><span class="super-tip">This comes in handy especially when working with large forms. To benefit from this feature make sure you use columns to group your elements. With columns you can minimize a set of elements at once to make building forms even easier, faster and better manageable.</span></span>',
                    },
                    {
                        selector: '.super-form-history .super-maximize-toggle',
                        event: 'click',
                        shape: 'circle',
                        description: '<h1>Maximizing all elements</h1><span class="super-tip">Whenever you are working with large forms and used the minimize button, you can maximize all of your elements at once to quickly find the element that you need to edit.</span>',
                    },
                    {
                        selector: '.super-element-actions .super-transfer',
                        radius: 10,
                        shape: 'circle',
                        description: '<h1>Transfering elements between different forms can be done via this button</h1><span class="super-tip">When transfering Columns or Multi-parts all inner elements will be also be transfered along with them, making life even easier :)</span><span class="super-tip">You can also use this feature to clone the element and reposition it at a different location within the form you are working on. If needed you can also navigate to a different form and transfer this element over to that form.</span>',
                    },
                    {
                        selector: '.super-element-actions .super-move',
                        radius: 10,
                        shape: 'circle',
                        description: '<h1>Moving your element can be done via this button</h1><span class="super-tip">Drag & Drop your element into a different location or inside a different layout element with ease.</span>',
                    },
                    {
                        selector: '.super-element-actions .super-duplicate',
                        event: 'click',
                        radius: 10,
                        shape: 'circle',
                        description: '<h1>Duplicate this element</h1><span class="super-tip">Duplicating elements that you already created will speed up your building process! When duplicating Columns or Multi-parts all inner elements will be duplicated along with them, making life even easier :)</span>',
                    },
                    {
                        selector: '.super-element-actions .super-edit',
                        radius: 10,
                        event: 'click',
                        shape: 'circle',
                        description: '<h1>Click on the pencil icon to edit this element</h1><span class="super-tip">All elements can be edited, and each element will have it\'s very own settings and features. </span>',
                    },
                    {
                        selector: '.super-element.super-element-settings',
                        description: '<h1>Here you can find all the settings for the element you are editing</h1><span class="super-tip">By default the General TAB is opened where you will find the most commonly used settings that you will often be changing.</span>',
                        timeout: $timeout_s
                    },
                    {
                        selector: '.super-element.super-element-settings .super-element-settings-tabs > select',
                        event: 'change',
                        showNext: false,
                        description: '<h1>We have devided all element settings into sections which you can choose from via this dropdown, Open the dropdown and switch to a different section to find out about all the other features and settings for the element you are editing.</h1><span class="super-tip">Remember that all elements have different settings and features, so make sure to explore them all!</span><span class="super-tip">Note that the E-mail Address element that we added to our form, is a <a target="_blank" href="' + $git + 'text">Text field</a>. It is a predefined element that basically has the <a target="_blank" href="' + $git + 'validation?id=email-address">E-mail address validation</a> enabled by default. There are several other predefined elements for you just to make building even easier for you.',
                    },
                    {
                        selector: '.super-element.super-element-settings',
                        description: '<h1>Perfect! Now you know how to edit elements and how to find all settings and features available for each element you edit.</h1>',
                    },
                    {
                        selector: '.super-element-settings .tab-content.super-active .super-tooltip',
                        shape: 'circle',
                        radius: 20,
                        description: '<h1>Not sure what a field is used for, just hover over the question icon to find out more information about it.</h1>',
                    },
                    {
                        selector: '.super-element.super-layout-elements',
                        event: 'click',
                        description: '<h1>Let\'s explore the rest of Super Forms shall we? Open up the "Layout Elements" panel by clicking on it</h1>',
                    },
                    {
                        selector: '.super-element.super-layout-elements .super-elements-container',
                        description: '<h1>These are your "Layout Elements"</h1><span class="super-tip">The <a target="_blank" href="' + $git + 'columns">Columns (Grid Element)</a> can be used to create the layout of your form.</span><span class="super-tip">You can use columns to put fields next to eachother and to do <a target="_blank" href="' + $git + 'conditional-logic">Conditional Logic</a>.</span><span class="super-tip">A column can also be used to create <a target="_blank" href="' + $git + 'columns?id=dynamic-add-more">Dynamic fields</a> that can be duplicated by users. This way a set of fields can be dynamically added by clicking on a "+" icon.</span><span class="super-tip">Columns can be nested inside of each other as many times as you wish, they can also be inserted into a <a target="_blank" href="' + $git + 'multi-parts">Multi-part</a> element.</span><span class="super-tip">The <a target="_blank" href="' + $git + 'multi-parts">Multi-part</a> element can be used to split a form into multiple parts (also called steps). For each step you will have to add a new Multi-part element with inside the elements that belong to this particular step.</span>',
                        timeout: $timeout_s
                    },
                    {
                        selector: '.super-element.super-html-elements',
                        event: 'click',
                        description: '<h1>Now open the "HTML Elements" panel</h1>',
                    },
                    {
                        selector: '.super-element.super-html-elements .super-elements-container',
                        description: '<h1>Here you can find all HTML elements</h1><span class="super-tip">HTML elements are elements that users can not change or alter (they are fixed html items that do not require user input). However you can make some elements dynamically change with the use of <a target="_blank" href="' + $git + 'conditional-logic">Conditional Logic</a> and the use of <a target="_blank" href="' + $git + 'variable-fields">Variable fields</a> and the <a target="_blank" href="' + $git + 'tags-system">{tags} system</a>. These elements can help you to change the aesthetics of your form.</span>',
                        timeout: $timeout_s
                    },
                    {
                        selector: '.super-element.super-form-settings',
                        event: 'click',
                        description: '<h1>Open the "Form Settings" panel to edit form settings</h1>',
                    },
                    {
                        selector: '.super-element.super-form-settings .super-elements-container',
                        description: '<h1>Here you can change all the "Form Settings" which will only apply to this specific form</h1><span class="super-tip">Under [Super Forms > Settings] (WordPress menu) you will find all your global settings that will be applied to all of your forms when creating a new form. After creating a form you can change each of these form settings over here. If both the global setting and form setting are the same the setting will not be saved for the form and instead the global setting will be used now and in the future until they differ from eachother.</span><span class="super-tip"><strong>Important to understand:</strong> If both the global setting and form setting are the same the setting will basically not be saved, but instead the global setting will be used by default now and in the future until they differ from eachother. This means that when you change a global setting at a later time it will affect all previously created forms that where initially setup with this exact same setting. This way you can control all of your forms if required from a global point of view.</span>',
                        timeout: $timeout_s
                    },
                    {
                        selector: '.super-form-settings-tabs > select',
                        event: 'change',
                        showNext: false,
                        description: '<h1>We have devided all form settings into sections which you can choose from via this dropdown, Open the dropdown and switch to a different section to find out about all the other settings that you can change with Super Forms</h1>',
                    },
                    {
                        selector: '.super-element.super-form-settings .super-elements-container',
                        description: '<h1>Great, now you know how to change all the settings related to a specific form!</h1><span class="super-tip">Please note that in some cases you have to change settings under [Super Forms > Settings] (WordPress menu). For instance if you require to setup SMTP you can do it only via the global settings and not individually per form. The same goes for reCAPTCHA API key and secret.</span>',
                        timeout: $timeout_s
                    },
                    {
                        selector: '.form-name',
                        description: '<h1>Here you can change the name of your form</h1><span class="super-tip">Always choose a name that relates to the purpose of the form itself.</span><span class="super-tip">The "Form name" is for your own reference only, and is not visible to anyone other than you and other WordPress admins.</span>',
                    },
                    {
                        selector: '.super-switch-forms',
                        description: '<h1>Here you can easily switch to a different form that you previously created</h1><span class="super-tip">A list will open with previously created forms to quickly switch to.</span>',
                    },
                    {
                        onBeforeStart: function () {
                            $('.super-switch-forms').removeClass('super-active');
                        },
                        selector: '.super-header .super-get-form-shortcodes',
                        description: '<h1>This is the [shortcode] of your form. You can display your form by copy pasting the shortcode to any of your posts/pages.</h1><span class="super-tip">You can add your shortcode in posts, pages and widgets (e.g: sidebars or in your footer). Anywhere within your site where your theme supports shortcodes you can basically display your form. In case you want to read more about how to build and publish your first form you can read the <a target="_blank" href="' + $git + 'build">Documentation</a></span>',
                    },
                    {
                        selector: '.super-tab-translations',
                        event: 'click',
                        description: '<h1>Build-in Translation feature!</h1><span class="super-tip">With the build in translation system you can easily translate all elements and all form settings to the configured languages.</span>',
                    },
                    {
                        selector: '.super-default-language',
                        description: '<h1>Set a default language for your form</h1><span class="super-tip">Whenever you are going to have multiple languages you will want to define a default language for your form, you can do this here</span>'
                    },
                    {
                        selector: '.super-create-translation',
                        description: '<h1>Adding a new translation</h1><span class="super-tip">To add a new translation (language) for your form, you can click on this button</span>'
                    },
                    {
                        selector: '.super-i18n-switch',
                        description: '<h1>Language switcher</h1><span class="super-tip">If you don\'t want to use shortcodes to explicit define the form language you can also allow your users to choose the desired language from a dropdown. When this option is enabled it will add a so called "Language Switcher" at the top of your form.</span>'
                    },
                    {
                        selector: '.super-actions .super-save',
                        description: '<h1>You can save your form simply by clicking the "Save" button</h1><span class="super-tip">Every time you save your form an automatic backup of your form will be stored, this way you can always revert back to a previous version in case you made a mistake.</span>',
                    },
                    {
                        selector: '.super-actions .super-clear',
                        description: '<h1>If you want to start with a blank form you can use the "Clear" button</h1><span class="super-tip">Please note that this will erase your current work in progress and will delete all the elements that are currently on the canvas.</span>',
                    },
                    {
                        selector: '.super-actions .super-delete',
                        onBeforeStart: function () {
                            $('.super-actions .super-delete').css('pointer-events', 'none');
                        },
                        description: '<h1>Here you can delete your form</h1><span class="super-tip">This will delete the form itself allong with it\'s Elements, Settings and all it\'s backups. It will not delete the associated Contact Entries that were created by the form.</span>',
                    },
                    {
                        onBeforeStart: function () {
                            $('.super-actions .super-delete').css('pointer-events', '');
                            $('.enjoyhint_close_btn').css('display', 'none');
                        },
                        selector: '.super-actions .super-preview.super-switch',
                        description: '<h1>To see how your form will look on the front-end you can click the "Preview" button</h1><span class="super-tip">You can also preview the form on mobile and tablet devices to test it\'s responsiveness.</span>',
                    },
                    {
                        selector: '.super-actions > label:last',
                        description: '<h1>(For Developers Only) Enable this whenever you require to save a form with duplicate field names</h1><span class="super-tip">Whenever you are a developer and require the need to save a form that consists of duplicate field names, then you have to enable this setting. By default Super Forms prevents saving a form that contains duplicate field names.</span>',
                    },
                    {
                        selector: '#collapse-menu',
                        event: 'click',
                        description: '<h1>Open the WordPres menu</h1>'
                    },
                    {
                        selector: '.wp-submenu a[href*="page=super_demos"]',
                        description: '<h1>You finished the tutorial! Now you know how to navigate around in Super Forms and create awesome forms with it.<br /><br />Please check out the Demos with awesome one click installable forms that can get you up and running in no time!</h1><span class="super-tip">We hope you will enjoy the plugin, if you have future questions do not hesitate to contact support!</span><span class="super-tip">Don\'t forget to checkout the <a target="_blank" href="' + $git + '">Documentation</a> whenever you need more information about the plugin and all of it\'s features :)</i></span>',
                        nextButton: {
                            text: "Finish"
                        },
                    }
                ];
                $.each($super_hints_steps, function (key, value) {
                    if (typeof value.event === 'undefined')
                        $super_hints_steps[key].event = $event;
                    if (typeof value.showSkip === 'undefined')
                        $super_hints_steps[key].showSkip = $showSkip;
                    if (typeof value.showNext === 'undefined')
                        if ($super_hints_steps[key].event == 'click') {
                            $super_hints_steps[key].showNext = false;
                        } else {
                            $super_hints_steps[key].showNext = $showNext;
                        }
                    if (typeof value.timeout === 'undefined')
                        $super_hints_steps[key].timeout = $timeout;
                    if (typeof value.margin === 'undefined')
                        $super_hints_steps[key].margin = $margin;
                });
                $super_hints.set($super_hints_steps);
                $super_hints.run();
            }
        }

        // A more responsive method of updating items will go here.
        // Currently only being used for TAB element
        // Will extend upon this over time, at some time all elements will/should use this method

        // Add Tab, Accordion, List Item
        $doc.on('click', '.super-element-settings .super-tab-item .super-add-item', function () {
            var clone,
                item = $(this),
                index = item.parent().index(),
                layout = item.parents('.super-elements-container:eq(0)').find('input[name="layout"]:checked').val(),
                editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs');
            // Tabs
            if (layout == 'tabs') {
                // First clone the TAB menu item
                item = parent.children('.super-tabs-menu').children('.super-tabs-tab:eq(' + index + ')');
                clone = item.clone();
                // Always remove the 'super-active' status
                clone.removeClass('super-active');
                $(clone).insertAfter(item);
                // Now clone the TAB content and clear it's contents
                item = parent.children('.super-tabs-contents').children('.super-tabs-content:eq(' + index + ')');
                clone = item.clone();
                // Always remove the 'super-active' status
                clone.removeClass('super-active');
                // Also remove any inner elements
                clone.children('.super-element-inner').html('');
                // Insert the new TAB after the previous TAB contents
                $(clone).insertAfter(item);
            }
            // Accordion
            if (layout == 'accordion') {
                // Copy the element that we will clone, then empty the contents and alter the title etc.
                item = parent.children('.super-accordion-item:eq(' + index + ')');
                clone = item.clone();
                // Clear content of the cloned element
                clone.children('.super-accordion-content').children('.super-padding').children('.super-element-inner').html('');
                // Let's append the clone after this item
                $(clone).insertAfter(item);
            }
            // Push updates
            SUPER.update_element_push_updates();
        });
        // Delete Accordion Item
        $doc.on('click', '.super-element-settings .super-tab-item .super-delete', function () {
            var active,
                item = $(this),
                index = item.parent().index(),
                layout = item.parents('.super-elements-container:eq(0)').find('input[name="layout"]:checked').val(),
                editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs');
            // Remove multi-item
            item.parents('.super-multi-items:eq(0)').remove();
            // Tabs
            if (layout == 'tabs') {
                // Remove TAB menu item
                item = parent.children('.super-tabs-menu').children('.super-tabs-tab:eq(' + index + ')');
                item.remove();
                // Remove TAB content item
                item = parent.children('.super-tabs-contents').children('.super-tabs-content:eq(' + index + ')');
                item.remove();
                // After deleting a TAB check if we still have an active TAB, if not make the first one active
                active = parent.children('.super-tabs-menu').children('.super-tabs-tab.super-active').length;
                if (!active) {
                    // Make first TAB active
                    parent.children('.super-tabs-menu').children('.super-tabs-tab:eq(0)').addClass('super-active');
                    parent.children('.super-tabs-contents').children('.super-tabs-content:eq(0)').addClass('super-active');
                }
            }
            // Accordion
            if (layout == 'accordion') {
                item = parent.children('.super-accordion-item:eq(' + index + ')');
                item.remove();
            }
            // First make sure to update the multi items json
            SUPER.update_multi_items();
            // Push updates
            SUPER.update_element_push_updates();
        });
        // Update Title of Accordion Item
        $doc.on('keyup change', '.super-element-settings .super-tab-item input[name="title"]', function () {
            var item = $(this),
                index = item.parent().index(),
                layout = item.parents('.super-elements-container:eq(0)').find('input[name="layout"]:checked').val(),
                value = item.val(),
                editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs');
            // Tabs
            if (layout == 'tabs') {
                item = parent.children('.super-tabs-menu').children('.super-tabs-tab:eq(' + index + ')');
                item.children('.super-tab-title').html(value);
            }
            // Accordion
            if (layout == 'accordion') {
                item = parent.children('.super-accordion-item:eq(' + index + ')');
                item.children('.super-accordion-header').children('.super-accordion-title').html(value);
            }
            // Push updates
            SUPER.update_element_push_updates();
        });
        // Update Description of Accordion Item
        $doc.on('keyup change', '.super-element-settings .super-tab-item textarea[name="desc"]', function () {
            var item = $(this),
                index = item.parent().index(),
                layout = item.parents('.super-elements-container:eq(0)').find('input[name="layout"]:checked').val(),
                value = item.val(),
                editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs');
            // Tabs
            if (layout == 'tabs') {
                item = parent.children('.super-tabs-menu').children('.super-tabs-tab:eq(' + index + ')');
                item.children('.super-tab-desc').html(value);
            }
            // Accordion
            if (layout == 'accordion') {
                item = parent.children('.super-accordion-item:eq(' + index + ')');
                item.children('.super-accordion-header').children('.super-accordion-desc').html(value);
            }
            // Push updates
            SUPER.update_element_push_updates();
        });
        // Update Image of TAB Item
        $doc.on('keyup change', '.super-element-settings .super-tab-item input[name="image"]', function () {
            var style,
                item = $(this),
                image_parent = item.parents('.image-field:eq(0)'),
                image_url = image_parent.find('.image > img').attr('src'),
                max_width = image_parent.find('input[name="max_width"]').val(),
                max_height = image_parent.find('input[name="max_height"]').val(),
                index = item.parents('.super-tab-item:eq(0)').index(),
                layout = item.parents('.super-elements-container:eq(0)').find('input[name="layout"]:checked').val(),
                editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs');
            
            max_width = (max_width === '' ? 50 : max_width);
            max_height = (max_height === '' ? 50 : max_height);

            // Tabs
            if (layout == 'tabs') {
                item = parent.children('.super-tabs-menu').children('.super-tabs-tab:eq(' + index + ')');
                // First check if the image element already exists
                // If this is the case then we update the image src
                if (item.children('.super-tab-image').length) {
                    // Just update the src
                    item.children('.super-tab-image').children('img').attr('src', image_url);
                } else {
                    // Create image element and prepend it
                    // We will need to set the width and height accordingly
                    style = 'style="max-width:' + max_width + 'px;max-height:' + max_height + 'px;"';
                    item.prepend('<div class="super-tab-image"><img src="' + image_url + '"' + style + ' /></div>');
                }
            }
            // Accordion
            if (layout == 'accordion') {
                item = parent.children('.super-accordion-item:eq(' + index + ')');
                // First check if the image element already exists
                // If this is the case then we update the image src
                if (item.children('.super-accordion-header').children('.super-accordion-image').length) {
                    // Just update the src
                    item.children('.super-accordion-header').children('.super-accordion-image').children('img').attr('src', image_url);
                } else {
                    // Create image element and prepend it
                    // We will need to set the width and height accordingly
                    style = 'style="max-width:' + max_width + 'px;max-height:' + max_height + 'px;"';
                    item.children('.super-accordion-header').prepend('<div class="super-accordion-image"><img src="' + image_url + '"' + style + ' /></div>');
                }
            }
            // Push updates
            SUPER.update_element_push_updates();
        });
        // Update Image dimensions of a TAB item
        $doc.on('keyup change', '.super-element-settings .super-tab-item input[name="max_width"]', function () {
            var item = $(this),
                image_parent = item.parents('.image-field:eq(0)'),
                max_width = image_parent.find('input[name="max_width"]').val(),
                index = item.parents('.super-tab-item:eq(0)').index(),
                layout = item.parents('.super-elements-container:eq(0)').find('input[name="layout"]:checked').val(),
                editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs');

            max_width = (max_width === '' ? 50 : max_width);

            // Tabs
            if (layout == 'tabs') {
                item = parent.children('.super-tabs-menu').children('.super-tabs-tab:eq(' + index + ')');
                item.children('.super-tab-image').children('img')[0].style.maxWidth = max_width + 'px';
            }
            // Accordion
            if (layout == 'accordion') {
                item = parent.children('.super-accordion-item:eq(' + index + ')');
                item.children('.super-accordion-header').children('.super-accordion-image').children('img')[0].style.maxWidth = max_width + 'px';
            }
            // Push updates
            SUPER.update_element_push_updates();
        });
        $doc.on('keyup change', '.super-element-settings .super-tab-item input[name="max_height"]', function () {
            var item = $(this),
                image_parent = item.parents('.image-field:eq(0)'),
                max_height = image_parent.find('input[name="max_height"]').val(),
                index = item.parents('.super-tab-item:eq(0)').index(),
                layout = item.parents('.super-elements-container:eq(0)').find('input[name="layout"]:checked').val(),
                editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs');

            max_height = (max_height === '' ? 50 : max_height);

            // Tabs
            if (layout == 'tabs') {
                item = parent.children('.super-tabs-menu').children('.super-tabs-tab:eq(' + index + ')');
                item.children('.super-tab-image').children('img')[0].style.maxHeight = max_height + 'px';
            }
            // Accordion
            if (layout == 'accordion') {
                item = parent.children('.super-accordion-item:eq(' + index + ')');
                item.children('.super-accordion-header').children('.super-accordion-image').children('img')[0].style.maxHeight = max_height + 'px';
            }
            // Push updates
            SUPER.update_element_push_updates();
        });

        // Update TAB layout
        $doc.on('click change', '.super-element-settings .super-image-select-option', function () {
            var contents,
                item = $(this),
                items = JSON.parse(item.parents('.super-elements-container:eq(0)').find('textarea[name="items"]').val()),
                editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs'),
                layout = item.children('input[name="layout"]').val();

            // Check if changing layout
            // Change layout to TABS (if needed)
            if (layout == 'tabs') {
                // Changing from accordion layout to tabs
                if (parent.hasClass('super-layout-accordion')) {
                    // Before converting, grab each Accordion inner content section
                    contents = parent.children('.super-accordion-item').children('.super-accordion-content').children('.super-padding');
                    // Loop over all the items to generate the HTML
                    var menu_html = '<div class="super-tabs-menu">';
                    var content_html = '<div class="super-tabs-contents">';
                    $.each(items, function (key, value) {
                        // Generate TAB menu HTML
                        menu_html += '<div class="super-tabs-tab' + (key == 0 ? ' super-active' : '') + '">';
                        var image_url = parent.children('.super-accordion-item:nth-child(' + (key + 1) + ')').children('.super-accordion-header').children('.super-accordion-image').children('img').attr('src');
                        if (typeof image_url !== 'undefined') {
                            // Set default dimensions
                            value.max_width = (value.max_width === '' ? 50 : value.max_width),
                                value.max_height = (value.max_height === '' ? 50 : value.max_height),
                                menu_html += '<div class="super-tab-image">';
                            menu_html += '<img src="' + image_url + '" style="max-width:' + value.max_width + 'px;max-height:' + value.max_height + 'px;" />';
                            menu_html += '</div>';
                        }
                        menu_html += '<div class="super-tab-title">' + value.title + '</div>';
                        menu_html += '<div class="super-tab-desc">' + value.desc + '</div>';
                        menu_html += '</div>';
                        // Generate TAB content HTML
                        content_html += '<div class="super-tabs-content' + (key == 0 ? ' super-active' : '') + '">';
                        content_html += '<div class="super-padding">';
                        content_html += contents[key].innerHTML;
                        content_html += '</div>';
                        content_html += '</div>';
                    });
                    menu_html += '</div>';
                    content_html += '</div>';
                    // Insert new HTML
                    parent.html(menu_html + content_html);
                    // Rename layout className
                    parent.removeClass('super-layout-accordion').addClass('super-layout-' + layout);
                }
            }
            // Change layout to Accordion (if needed)
            if (layout == 'accordion') {
                // Changing from tabs layout to accordion
                if (parent.hasClass('super-layout-tabs')) {
                    // Before converting, grab each TAB inner content section
                    contents = parent.children('.super-tabs-contents').children('.super-tabs-content').children('.super-padding');
                    // Clone parent, then change it accordingly.
                    // After changes have been made insert it after the current parent and remove the previous parent
                    var clone = parent.clone();
                    // Loop over all the items to generate the HTML
                    var html = '';
                    $.each(items, function (key, value) {
                        html += '<div class="super-accordion-item">';
                        // Generate Accordion header
                        html += '<div class="super-accordion-header">';
                        var image_url = parent.children('.super-tabs-menu').children('.super-tabs-tab:nth-child(' + (key + 1) + ')').children('.super-tab-image').children('img').attr('src');
                        if (typeof image_url !== 'undefined') {
                            // Set default dimensions
                            value.max_width = (value.max_width === '' ? 50 : value.max_width),
                                value.max_height = (value.max_height === '' ? 50 : value.max_height),
                                html += '<div class="super-accordion-image">';
                            html += '<img src="' + image_url + '" style="max-width:' + value.max_width + 'px;max-height:' + value.max_height + 'px;" />';
                            html += '</div>';
                        }
                        html += '<div class="super-accordion-title">';
                        html += value.title;
                        html += '</div>';
                        html += '<div class="super-accordion-desc">';
                        html += value.desc;
                        html += '</div>';
                        html += '</div>';
                        // Generate Accordion content
                        html += '<div class="super-accordion-content">';
                        html += '<div class="super-padding">';
                        html += contents[key].innerHTML;
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    });
                    clone.html(html);
                    // Rename layout className
                    clone.removeClass('super-layout-tabs').addClass('super-layout-' + layout);
                    // Insert new HTML
                    $(clone).insertAfter(parent);
                    // Remove parent
                    parent.remove();
                }
            }
            // Update filter items
            SUPER.init_field_filter_visibility(item, 'element_settings');
            // Push updates
            SUPER.update_element_push_updates();
        });

        // Make switching layouts responsive (life updating)
        $doc.on('change', '.super-element-settings select[name="tab_location"]', function () {
            var location = $(this).val(),
                editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs');

            // If location of TABs need to become vertical then add the proper class
            if (location == 'vertical') {
                parent.removeClass('super-horizontal').addClass('super-vertical');
            } else {
                // If not, then remove the class and add the default class
                parent.removeClass('super-vertical').addClass('super-horizontal');
            }
            // Push updates
            SUPER.update_element_push_updates();
        });

        // Show prev and next buttons for accordion element
        $doc.on('click', '.super-element-settings .super-checkbox input[type="checkbox"]', function () {
            var editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs'),
                field = $(this).parents('.super-field-input:eq(0)').children('input[name="tab_show_prev_next"]'),
                show = field.val();
            // If location of TABs need to become vertical then add the proper class
            if (show == 'true') {
                parent.addClass('super-prev-next');
            } else {
                parent.removeClass('super-prev-next');
            }
        });

        // Update TAB class
        $doc.on('keyup change', '.super-element-settings input[name="tab_class"]', function () {
            var tab_class = $(this).val(),
                editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs.super-layout-tabs').children('.super-tabs-menu'),
                tabs = editing.children('.super-element-inner').children('.super-tabs.super-layout-accordion').children('.super-accordion-item').children('.super-accordion-header');
            // Update class on Tab element
            parent.attr('class', '').addClass('super-tabs-menu').addClass(tab_class);
            // Update class on Accordion element
            tabs.each(function () {
                $(this).attr('class', 'super-accordion-header').addClass(tab_class);
            });
            // Push updates
            SUPER.update_element_push_updates();
        });
        // Update TAB Content class
        $doc.on('keyup change', '.super-element-settings input[name="content_class"]', function () {
            var content_class = $(this).val(),
                editing = $('.super-element.editing'),
                parent = editing.children('.super-element-inner').children('.super-tabs.super-layout-tabs').children('.super-tabs-contents'),
                contents = editing.children('.super-element-inner').children('.super-tabs.super-layout-accordion').children('.super-accordion-item').children('.super-accordion-content');
            parent.attr('class', '').addClass('super-tabs-contents').addClass(content_class);
            // Update class on Accordion element
            contents.each(function () {
                $(this).attr('class', 'super-accordion-content').addClass(content_class);
            });
            // Push updates
            SUPER.update_element_push_updates();
        });

        // Update regular Text input setting
        $doc.on('keyup change', '.super-element-settings input, .super-element-settings select', function () {
            SUPER.backend_setting_changed($(this));
        });

        // Change to different subtab
        $doc.on('click', '.super-subtab', function () {
            if (!$(this).hasClass('super-active')) {
                var $this = $(this),
                    index = $this.index(),
                    nodes = $this.parents('.tab-content').children('.super-subtabscontent').children('.super-subtabcontent');
                $this.parent().children().removeClass('super-active');
                $this.addClass('super-active');
                nodes.removeClass('super-active');
                nodes[index].classList.add('super-active');
            }
        });

        // @IMPORTANT - must be executed at the very last, before life updates are being done to the canvas
        $doc.on('click', '.super-multi-items .super-delete', function () {
            var $this = $(this);
            var $parent = $this.parents('.super-field-input:eq(0)');
            if ($parent.find('.super-multi-items').length <= 2) {
                $parent.find('.super-delete').css('visibility', 'hidden');
            } else {
                $parent.find('.super-delete').css('visibility', '');
            }
            $(this).parent().remove();
            // First make sure to update the multi items json
            SUPER.update_multi_items();
        });

        // Required for fields like Toggle element to be rendered properly
        SUPER.init_super_responsive_form_fields({form: $('.super-preview-elements')[0]});
        
        // Clean Email Preview System - preview-centric approach
        var pausedPreviews = new Set(); // Track paused previews
        var lastEmailContent = new Map(); // Cache last content for each preview to prevent unnecessary updates
        
        // Generate a content hash for comparison
        function getContentHash(content) {
            // Simple hash function to detect content changes without being sensitive to minor whitespace differences
            var hash = 0;
            if (!content || content.length === 0) return hash;
            
            // Normalize content by removing extra whitespace
            var normalized = content.replace(/\s+/g, ' ').trim();
            
            for (var i = 0; i < normalized.length; i++) {
                var char = normalized.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32bit integer
            }
            return hash;
        }
        
        // Process {loop_fields} tag with dummy data for live preview
        function processLoopFieldsForPreview(bodyValue, repeaterItem) {
            // Dummy data matching the PHP version
            var dummyData = {
                'first_name': { label: 'First Name', value: 'John' },
                'last_name': { label: 'Last Name', value: 'Doe' },
                'email': { label: 'E-mail address', value: 'john.doe@example.com' },
                'phone': { label: 'Phone number', value: '+1 (555) 123-4567' },
                'question': { label: 'Question', value: 'What services are you interested in?<br />I am particularly interested in web development and design services.<br /><br /><br />Regards John' },
                'choice': { label: 'Select your preference', value: 'First choice' },
                'message': { label: 'Message', value: 'This is a test message to verify email functionality.' }
            };
            
            // Get loop settings from the repeater item
            var loopOpenField = repeaterItem.querySelector('[name="loop_open"]');
            var loopField = repeaterItem.querySelector('[name="loop"]');
            var loopCloseField = repeaterItem.querySelector('[name="loop_close"]');
            var excludeEmptyField = repeaterItem.querySelector('[name="exclude_empty"]');
            
            var loopOpen = loopOpenField ? loopOpenField.value : '<table cellpadding="5">';
            var loopTemplate = loopField ? loopField.value : '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>';
            var loopClose = loopCloseField ? loopCloseField.value : '</table>';
            var excludeEmpty = excludeEmptyField ? excludeEmptyField.checked : true;
            
            // Build the loop content
            var emailLoop = '';
            for (var fieldName in dummyData) {
                var fieldData = dummyData[fieldName];
                
                // Skip empty fields if exclude_empty is enabled
                if (excludeEmpty && !fieldData.value) {
                    continue;
                }
                
                // Process each field using the user's loop template
                var row = loopTemplate;
                row = row.replace(/{loop_label}/g, fieldData.label);
                row = row.replace(/{loop_value}/g, fieldData.value);
                emailLoop += row;
            }
            
            // Wrap with loop_open and loop_close
            var loopFieldsContent = loopOpen + emailLoop + loopClose;
            
            // Replace {loop_fields} with the processed content
            return bodyValue.replace(/{loop_fields}/g, loopFieldsContent);
        }
        
        // Process email template for live preview
        function processEmailTemplateForPreview(bodyValue, repeaterItem) {
            console.log('processEmailTemplateForPreview called with bodyValue:', bodyValue);
            
            // Check if email template is enabled - look within template group
            var templateGroup = repeaterItem.querySelector('[data-g="template"]');
            if (!templateGroup) {
                console.log('Template group not found');
                return bodyValue;
            }
            
            var templateSlugField = templateGroup.querySelector('[name="slug"]');
            console.log('Template slug field found:', templateSlugField);
            console.log('Template slug value:', templateSlugField ? templateSlugField.value : 'not found');
            
            if (!templateSlugField || templateSlugField.value !== 'email_template_1') {
                console.log('Template not enabled, returning original bodyValue');
                return bodyValue;
            }
            
            // console.log('Template is enabled, proceeding with template processing');
            
            // Get template settings from the repeater item
            var templateSettings = getTemplateSettings(repeaterItem);
            console.log('Template settings:', templateSettings);
            
            // Build responsive email template HTML
            var templateHTML = buildEmailTemplateHTML(bodyValue, templateSettings);
            console.log('Generated template HTML:', templateHTML);
            
            return templateHTML;
        }
        
        // Get template settings from repeater item
        function getTemplateSettings(repeaterItem) {
            console.log('getTemplateSettings called with repeaterItem:', repeaterItem);
            
            var settings = {
                logo: '',
                title: 'Your title',
                subtitle: 'Your subtitle',
                copyright: '&copy; Company Name and Address 2016',
                socials: [],
                email_bg: '#f4f4f4',
                header_bg: '#5ba1d3',
                header_title: '#ffffff',
                body_bg: '#ffffff',
                body_subtitle: '#474747',
                body_font: '#9e9e9e',
                footer_bg: '#ee4c50',
                footer_font: '#ffffff'
            };
            
            // Get template group
            var templateGroup = repeaterItem.querySelector('[data-g="template"]');
            if (templateGroup) {
                // Get logo from file upload within template group
                var logoField = templateGroup.querySelector('[name="logo"]');
                console.log('Logo field found:', logoField);
                if (logoField && logoField.value) {
                    // Get image preview URL if available
                    var filePreview = templateGroup.querySelector('.file-preview img');
                    console.log('File preview found:', filePreview);
                    if (filePreview) {
                        settings.logo = filePreview.src;
                    }
                }
                
                // Get text fields within template group
                var titleField = templateGroup.querySelector('[name="title"]');
                console.log('Title field found:', titleField, 'value:', titleField ? titleField.value : 'N/A');
                if (titleField && titleField.value.trim()) settings.title = titleField.value.trim();
                
                var subtitleField = templateGroup.querySelector('[name="subtitle"]');
                console.log('Subtitle field found:', subtitleField, 'value:', subtitleField ? subtitleField.value : 'N/A');
                if (subtitleField && subtitleField.value.trim()) settings.subtitle = subtitleField.value.trim();
                
                var copyrightField = templateGroup.querySelector('[name="copyright"]');
                console.log('Copyright field found:', copyrightField, 'value:', copyrightField ? copyrightField.value : 'N/A');
                if (copyrightField && copyrightField.value.trim()) settings.copyright = copyrightField.value.trim();
                
                // Get socials repeater data
                var socialsRepeater = templateGroup.querySelector('[data-r="socials"]');
                console.log('Socials repeater found:', socialsRepeater);
                if (socialsRepeater) {
                    var socialItems = [];
                    var socialsItems = socialsRepeater.querySelectorAll('.sfui-repeater-item');
                    console.log('Social items found:', socialsItems.length);
                    
                    socialsItems.forEach(function(item) {
                        var urlField = item.querySelector('[name="url"]');
                        var nameField = item.querySelector('[name="name"]');
                        var iconTypeField = item.querySelector('[name="icon_type"]');
                        var iconUploadField = item.querySelector('[name="icon_upload"]');
                        var iconFontawesomeField = item.querySelector('[name="icon_fontawesome"]');
                        var iconUrlField = item.querySelector('[name="icon_url"]');
                        var colorField = item.querySelector('[name="color"]');
                        
                        var url = urlField ? urlField.value.trim() : '';
                        var name = nameField ? nameField.value.trim() : '';
                        var iconType = iconTypeField ? iconTypeField.value : 'none';
                        var color = colorField ? colorField.value : '#ffffff';
                        var icon = '';
                        
                        // Get icon based on type
                        if (iconType === 'upload' && iconUploadField && iconUploadField.value) {
                            var filePreview = item.querySelector('.file-preview img');
                            if (filePreview) {
                                icon = filePreview.src;
                            }
                        } else if (iconType === 'fontawesome' && iconFontawesomeField) {
                            icon = iconFontawesomeField.value.trim();
                        } else if (iconType === 'url' && iconUrlField) {
                            icon = iconUrlField.value.trim();
                        }
                        
                        console.log('Social item:', { url: url, name: name, iconType: iconType, icon: icon, color: color });
                        
                        if (url && name) {
                            socialItems.push({
                                url: url,
                                name: name,
                                iconType: iconType,
                                icon: icon,
                                color: color
                            });
                        }
                    });
                    
                    settings.socials = socialItems;
                    console.log('Final socials array:', settings.socials);
                }
            }
            
            // Get color fields from their respective groups
            var emailColorsGroup = repeaterItem.querySelector('[data-g="email_colors"]');
            if (emailColorsGroup) {
                var emailBgField = emailColorsGroup.querySelector('[name="bg"]');
                console.log('Email BG field found:', emailBgField, 'value:', emailBgField ? emailBgField.value : 'N/A');
                if (emailBgField && emailBgField.value) settings.email_bg = emailBgField.value;
            }
            
            var headerColorsGroup = repeaterItem.querySelector('[data-g="header_colors"]');
            if (headerColorsGroup) {
                var headerBgField = headerColorsGroup.querySelector('[name="bg"]');
                console.log('Header BG field found:', headerBgField, 'value:', headerBgField ? headerBgField.value : 'N/A');
                if (headerBgField && headerBgField.value) settings.header_bg = headerBgField.value;
                
                var headerTitleField = headerColorsGroup.querySelector('[name="title"]');
                console.log('Header title field found:', headerTitleField, 'value:', headerTitleField ? headerTitleField.value : 'N/A');
                if (headerTitleField && headerTitleField.value) settings.header_title = headerTitleField.value;
            }
            
            var bodyColorsGroup = repeaterItem.querySelector('[data-g="body_colors"]');
            if (bodyColorsGroup) {
                var bodyBgField = bodyColorsGroup.querySelector('[name="bg"]');
                console.log('Body BG field found:', bodyBgField, 'value:', bodyBgField ? bodyBgField.value : 'N/A');
                if (bodyBgField && bodyBgField.value) settings.body_bg = bodyBgField.value;
                
                var bodySubtitleField = bodyColorsGroup.querySelector('[name="subtitle"]');
                console.log('Body subtitle field found:', bodySubtitleField, 'value:', bodySubtitleField ? bodySubtitleField.value : 'N/A');
                if (bodySubtitleField && bodySubtitleField.value) settings.body_subtitle = bodySubtitleField.value;
                
                var bodyFontField = bodyColorsGroup.querySelector('[name="font"]');
                console.log('Body font field found:', bodyFontField, 'value:', bodyFontField ? bodyFontField.value : 'N/A');
                if (bodyFontField && bodyFontField.value) settings.body_font = bodyFontField.value;
            }
            
            var footerColorsGroup = repeaterItem.querySelector('[data-g="footer_colors"]');
            if (footerColorsGroup) {
                var footerBgField = footerColorsGroup.querySelector('[name="bg"]');
                console.log('Footer BG field found:', footerBgField, 'value:', footerBgField ? footerBgField.value : 'N/A');
                if (footerBgField && footerBgField.value) settings.footer_bg = footerBgField.value;
                
                var footerFontField = footerColorsGroup.querySelector('[name="font"]');
                console.log('Footer font field found:', footerFontField, 'value:', footerFontField ? footerFontField.value : 'N/A');
                if (footerFontField && footerFontField.value) settings.footer_font = footerFontField.value;
            }
            
            return settings;
        }
        
        // Build responsive email template HTML
        function buildEmailTemplateHTML(bodyContent, settings) {
            // Check if we have any content for title, subtitle, copyright or socials to determine default values
            var hasTitle = settings.title && settings.title.trim() && settings.title !== 'Your title';
            var hasSubtitle = settings.subtitle && settings.subtitle.trim() && settings.subtitle !== 'Your subtitle';
            var hasCopyright = settings.copyright && settings.copyright.trim() && settings.copyright !== '&copy; Company Name and Address 2016';
            var hasSocials = settings.socials && Array.isArray(settings.socials) && settings.socials.length > 0;
            
            // Process social media icons
            var socialIconsHTML = '';
            if (hasSocials) {
                socialIconsHTML = '<td align="right" style="vertical-align:top;"><table cellpadding="0" cellspacing="0" border="0"><tr>';
                
                settings.socials.forEach(function(social) {
                    if (social.url && social.name) {
                        var linkColor = social.color || '#ffffff';
                        
                        if (social.iconType === 'fontawesome' && social.icon) {
                            // Font Awesome icon
                            socialIconsHTML += '<td style="padding-left:8px;"><a href="' + social.url + '" target="_blank" style="color:' + linkColor + ';text-decoration:none;font-size:18px;"><i class="' + social.icon + '"></i></a></td>';
                        } else if ((social.iconType === 'upload' || social.iconType === 'url') && social.icon) {
                            // Image icon (uploaded or URL)
                            socialIconsHTML += '<td style="padding-left:8px;"><a href="' + social.url + '" target="_blank"><img src="' + social.icon + '" alt="' + social.name + '" style="display:block;width:24px;height:24px;" border="0"></a></td>';
                        } else {
                            // Text fallback (no icon or icon type is 'none')
                            socialIconsHTML += '<td style="padding-left:8px;"><a href="' + social.url + '" target="_blank" style="color:' + linkColor + ';text-decoration:none;font-size:12px;font-weight:bold;">' + social.name + '</a></td>';
                        }
                    }
                });
                
                socialIconsHTML += '</tr></table></td>';
            }
            
            // Build the complete email template
            var template = '<div style="background-color:' + settings.email_bg + ';padding:20px 10px;font-family:Arial,sans-serif;line-height:1.6;">' +
                '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);">';
            
            // Header Section - only include if we have logo or title
            if (settings.logo || hasTitle) {
                template += '<tr><td style="background-color:' + settings.header_bg + ';padding:40px 30px;text-align:center;">';
                
                // Logo - use a unique id to help with caching
                if (settings.logo) {
                    template += '<img src="' + settings.logo + '" alt="Logo" style="max-width:200px;height:auto;margin-bottom:20px;display:block;margin-left:auto;margin-right:auto;" data-logo-url="' + settings.logo + '">';
                }
                
                // Title - only include if not empty and not default
                if (hasTitle) {
                    template += '<h1 style="margin:0;color:' + settings.header_title + ';font-size:28px;font-weight:bold;">' + settings.title + '</h1>';
                }
                
                template += '</td></tr>';
            }
            
            // Body Section
            template += '<tr><td style="background-color:' + settings.body_bg + ';padding:40px 30px;">';
            
            // Subtitle - only include if not empty and not default
            if (hasSubtitle) {
                template += '<h2 style="margin:0 0 20px 0;color:' + settings.body_subtitle + ';font-size:20px;font-weight:bold;">' + settings.subtitle + '</h2>';
            }
            
            // Body Content
            template += '<div style="color:' + settings.body_font + ';font-size:16px;line-height:1.6;">' + bodyContent + '</div>' +
                '</td></tr>';
            
            // Footer Section - only include if we have copyright or socials
            if (hasCopyright || hasSocials) {
                template += '<tr><td style="background-color:' + settings.footer_bg + ';padding:30px;">' +
                    '<table width="100%" cellpadding="0" cellspacing="0" border="0">' +
                    '<tr>';
                
                if (hasCopyright) {
                    template += '<td style="color:' + settings.footer_font + ';font-size:14px;vertical-align:top;">' + settings.copyright + '</td>';
                } else if (hasSocials) {
                    // If no copyright but we have socials, add empty cell to maintain layout
                    template += '<td style="color:' + settings.footer_font + ';font-size:14px;vertical-align:top;"></td>';
                }
                
                template += socialIconsHTML +
                    '</tr></table>' +
                    '</td></tr>';
            }
            
            template += '</table>' +
                '</div>';
            
            return template;
        }
        
        function updateSingleEmailPreview(emailPreview) {
            // Only update if the toggle is open (has sfui-open class) and not paused by mouse hover
            var toggle = emailPreview.closest('.sfui-toggle');
            if (!toggle || !toggle.classList.contains('sfui-open')) {
                return;
            }
            
            // Skip if user is hovering over this preview
            if (pausedPreviews.has(emailPreview)) {
                return;
            }
            
            // Find the repeater item that contains this email preview
            var repeaterItem = emailPreview.closest('.sfui-repeater-item');
            if (!repeaterItem) return;
            
            var previewTo = emailPreview.querySelector('.super-preview-to');
            var previewFrom = emailPreview.querySelector('.super-preview-from');
            var previewReplyTo = emailPreview.querySelector('.super-preview-reply-to');
            var previewReplyToWrapper = emailPreview.querySelector('.super-preview-reply-to-wrapper');
            var previewSubject = emailPreview.querySelector('.super-preview-subject');
            var previewBody = emailPreview.querySelector('.super-preview-body');
            var previewCc = emailPreview.querySelector('.super-preview-cc');
            var previewBcc = emailPreview.querySelector('.super-preview-bcc');
            var previewCcWrapper = emailPreview.querySelector('.super-preview-cc-wrapper');
            var previewBccWrapper = emailPreview.querySelector('.super-preview-bcc-wrapper');
            var previewAttachments = emailPreview.querySelector('.super-preview-attachments');
            var previewAttachmentList = emailPreview.querySelector('.super-preview-attachment-list');
            
            // Get field values from within the same repeater item
            var toField = repeaterItem.querySelector('[name="to"]');
            var fromEmailField = repeaterItem.querySelector('[name="from_email"]');
            var fromNameField = repeaterItem.querySelector('[name="from_name"]');
            var subjectField = repeaterItem.querySelector('[name="subject"]');
            var bodyField = repeaterItem.querySelector('[name="body"]');
            var ccField = repeaterItem.querySelector('[name="cc"]');
            var bccField = repeaterItem.querySelector('[name="bcc"]');
            var attachmentsField = repeaterItem.querySelector('[name="attachments"]');
            
            // Reply-to fields (nested within reply_to group) - more specific selectors
            var replyToEnabledField = repeaterItem.querySelector('[data-g="reply_to"] [name="enabled"]');
            var replyToEmailField = repeaterItem.querySelector('[data-g="reply_to"] [name="email"]');
            var replyToNameField = repeaterItem.querySelector('[data-g="reply_to"] [name="name"]');
            
            // Update To field
            if (toField && previewTo) {
                var toValue = toField.value.trim();
                if (toValue) {
                    previewTo.textContent = toValue;
                    previewTo.classList.remove('super-email-empty');
                } else {
                    previewTo.textContent = 'Select recipients...';
                    previewTo.classList.add('super-email-empty');
                }
            }
            
            // Update From field
            if (fromEmailField && previewFrom) {
                var fromEmailValue = fromEmailField.value.trim();
                var fromNameValue = fromNameField ? fromNameField.value.trim() : '';
                var displayFrom = '';
                
                if (fromNameValue && fromEmailValue) {
                    displayFrom = fromNameValue + ' <' + fromEmailValue + '>';
                } else if (fromEmailValue) {
                    displayFrom = fromEmailValue;
                }
                
                if (displayFrom) {
                    previewFrom.textContent = displayFrom;
                    previewFrom.classList.remove('super-email-empty');
                } else {
                    previewFrom.textContent = 'Enter from email...';
                    previewFrom.classList.add('super-email-empty');
                }
            }
            
            // Update Reply-To field
            if (previewReplyTo && previewReplyToWrapper) {
                // First check if Reply-To is enabled
                var replyToEnabled = replyToEnabledField ? replyToEnabledField.checked : false;
                
                if (replyToEnabled) {
                    var replyToEmailValue = replyToEmailField ? replyToEmailField.value.trim() : '';
                    var replyToNameValue = replyToNameField ? replyToNameField.value.trim() : '';
                    var displayReplyTo = '';
                    
                    if (replyToNameValue && replyToEmailValue) {
                        displayReplyTo = replyToNameValue + ' <' + replyToEmailValue + '>';
                    } else if (replyToEmailValue) {
                        displayReplyTo = replyToEmailValue;
                    }
                    
                    if (displayReplyTo) {
                        previewReplyTo.textContent = displayReplyTo;
                        previewReplyToWrapper.style.display = 'block';
                    } else {
                        previewReplyToWrapper.style.display = 'none';
                    }
                } else {
                    // Reply-To is disabled, hide the field
                    previewReplyToWrapper.style.display = 'none';
                }
            }
            
            // Update CC field
            if (ccField && previewCc && previewCcWrapper) {
                var ccValue = ccField.value.trim();
                if (ccValue) {
                    previewCc.textContent = ccValue;
                    previewCcWrapper.style.display = 'block';
                } else {
                    previewCcWrapper.style.display = 'none';
                }
            }
            
            // Update BCC field
            if (bccField && previewBcc && previewBccWrapper) {
                var bccValue = bccField.value.trim();
                if (bccValue) {
                    previewBcc.textContent = bccValue;
                    previewBccWrapper.style.display = 'block';
                } else {
                    previewBccWrapper.style.display = 'none';
                }
            }
            
            // Update Subject field
            if (subjectField && previewSubject) {
                var subjectValue = subjectField.value.trim();
                if (subjectValue) {
                    previewSubject.textContent = subjectValue;
                    previewSubject.classList.remove('super-email-empty');
                } else {
                    previewSubject.textContent = 'Enter email subject...';
                    previewSubject.classList.add('super-email-empty');
                }
            }
            
            // Update Body field
            if (bodyField && previewBody) {
                var bodyValue = '';
                
                // Check if TinyMCE editor exists for this field
                var editorId = bodyField.id;
                if (window.tinymce && editorId && tinymce.get(editorId)) {
                    // Use getContent() for TinyMCE - get HTML content, not text
                    bodyValue = tinymce.get(editorId).getContent().trim();
                } else {
                    // Fallback to textarea value
                    bodyValue = bodyField.value.trim();
                }
                
                if (bodyValue) {
                    // Process {loop_fields} with dummy data if present
                    if (bodyValue.indexOf('{loop_fields}') !== -1) {
                        bodyValue = processLoopFieldsForPreview(bodyValue, repeaterItem);
                    }
                    
                    // If it's plain text, convert newlines to HTML
                    if (bodyValue.indexOf('<') === -1) {
                        bodyValue = bodyValue.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>');
                        if (!bodyValue.startsWith('<p>')) {
                            bodyValue = '<p>' + bodyValue + '</p>';
                        }
                    }
                    
                    // Apply email template if enabled
                    console.log('About to call processEmailTemplateForPreview');
                    bodyValue = processEmailTemplateForPreview(bodyValue, repeaterItem);
                    console.log('After processEmailTemplateForPreview, bodyValue:', bodyValue);
                    
                    // Only update if content has actually changed to prevent image flickering
                    var previewId = emailPreview.id || 'preview-' + Array.prototype.indexOf.call(emailPreview.parentNode.children, emailPreview);
                    var contentHash = getContentHash(bodyValue);
                    var lastContentHash = lastEmailContent.get(previewId);
                    
                    if (lastContentHash !== contentHash) {
                        console.log('Content changed, updating preview (hash:', lastContentHash, '->', contentHash, ')');
                        lastEmailContent.set(previewId, contentHash);
                        previewBody.innerHTML = bodyValue;
                    } else {
                        console.log('Content unchanged, skipping update');
                    }
                    
                    previewBody.classList.remove('super-email-empty');
                } else {
                    previewBody.textContent = 'Enter email body content...';
                    previewBody.classList.add('super-email-empty');
                }
            }
            
            // Update Attachments
            if (previewAttachments && previewAttachmentList) {
                var attachmentList = [];
                
                // Handle regular file attachments - get filenames directly from the file-preview HTML
                if (attachmentsField && attachmentsField.value.trim()) {
                    // Find the file-preview container within this repeater item
                    var filePreview = repeaterItem.querySelector('.file-preview');
                    if (filePreview) {
                        // Get all file items (li elements with data-file attribute)
                        var fileItems = filePreview.querySelectorAll('li[data-file]');
                        fileItems.forEach(function(fileItem) {
                            // Get the filename from the link text (not the delete link)
                            var fileLink = fileItem.querySelector('a:not(.super-delete)');
                            if (fileLink) {
                                var fileName = fileLink.textContent.trim();
                                var editUrl = fileLink.href;
                                attachmentList.push('<a href="' + editUrl + '" target="_blank">' + fileName + '</a>');
                            }
                        });
                    }
                }
                
                // Check for CSV attachment setting (nested field name)
                var csvAttachmentField = repeaterItem.querySelector('[data-g="csv_attachment"] [name="enabled"]');
                if (csvAttachmentField && csvAttachmentField.checked) {
                    var csvNameField = repeaterItem.querySelector('[data-g="csv_attachment"] [name="name"]');
                    var csvFilename = (csvNameField && csvNameField.value.trim()) ? csvNameField.value.trim() : 'form-entries.csv';
                    // Ensure .csv extension
                    if (!csvFilename.toLowerCase().endsWith('.csv')) {
                        csvFilename += '.csv';
                    }
                    attachmentList.push('<span>' + csvFilename + '</span>');
                }
                
                // Check for XML attachment setting (nested field name)
                var xmlAttachmentField = repeaterItem.querySelector('[data-g="xml_attachment"] [name="enabled"]');
                if (xmlAttachmentField && xmlAttachmentField.checked) {
                    var xmlNameField = repeaterItem.querySelector('[data-g="xml_attachment"] [name="name"]');
                    var xmlFilename = (xmlNameField && xmlNameField.value.trim()) ? xmlNameField.value.trim() : 'form-entries.xml';
                    // Ensure .xml extension
                    if (!xmlFilename.toLowerCase().endsWith('.xml')) {
                        xmlFilename += '.xml';
                    }
                    attachmentList.push('<span>' + xmlFilename + '</span>');
                }
                
                if (attachmentList.length > 0) {
                    previewAttachmentList.innerHTML = attachmentList.join(', ');
                    previewAttachments.style.display = 'block';
                } else {
                    previewAttachments.style.display = 'none';
                }
            }
        }
        
        // Clean Email Preview System - Variables
        var emailPreviewIntervals = new Map();
        
        // Initialize email preview for a specific preview element
        function initializeEmailPreview(emailPreview) {
            console.log('initializeEmailPreview called for:', emailPreview);
            
            // Set up toggle open/close handler for this specific preview
            var toggle = emailPreview.closest('.sfui-toggle');
            console.log('Toggle found:', !!toggle);
            if (!toggle) return;
            
            // Mark this preview as initialized to avoid duplicates
            if (emailPreview._superFormsInitialized) {
                console.log('Email preview already initialized, skipping');
                return;
            }
            emailPreview._superFormsInitialized = true;
            console.log('Initializing email preview');
            
            // Function to handle toggle state changes
            function handleToggleState() {
                var isOpen = toggle.classList.contains('sfui-open');
                console.log('Toggle state changed, isOpen:', isOpen);
                
                if (isOpen) {
                    console.log('Starting interval for email preview');
                    
                    // Start interval for this preview
                    if (emailPreviewIntervals.has(emailPreview)) {
                        clearInterval(emailPreviewIntervals.get(emailPreview));
                    }
                    
                    var interval = setInterval(function() {
                        updateSingleEmailPreview(emailPreview);
                    }, 500);
                    
                    emailPreviewIntervals.set(emailPreview, interval);
                    
                    // Initial update
                    updateSingleEmailPreview(emailPreview);
                } else {
                    console.log('Stopping interval for email preview');
                    
                    // Stop interval
                    if (emailPreviewIntervals.has(emailPreview)) {
                        clearInterval(emailPreviewIntervals.get(emailPreview));
                        emailPreviewIntervals.delete(emailPreview);
                    }
                }
            }
            
            // Check initial state immediately
            console.log('Checking initial toggle state...');
            handleToggleState();
            
            // Handle toggle clicks for this specific preview
            var toggleLabel = toggle.querySelector('.sfui-toggle-label');
            if (toggleLabel) {
                $(toggleLabel).on('click.emailPreview', function() {
                    setTimeout(handleToggleState, 100);
                });
            }
        }
        
        // Handle mouse enter/leave for pausing updates
        var pausedPreviews = new Set();
        
        $doc.on('mouseenter', '.super-email-preview', function() {
            pausedPreviews.add(this);
            console.log('Paused updates for email preview (mouse enter)');
        });
        
        $doc.on('mouseleave', '.super-email-preview', function() {
            pausedPreviews.delete(this);
            console.log('Resumed updates for email preview (mouse leave)');
            // Immediate update when mouse leaves
            updateSingleEmailPreview(this);
        });

        // Handle test email data type selection
        $doc.on('change', 'input[name="super_test_email_data_type"]', function() {
            var $entryOptions = $(this).closest('.super-test-email-options').find('.super-test-email-entry-options');
            if ($(this).val() === 'entry') {
                $entryOptions.show();
            } else {
                $entryOptions.hide();
            }
        });

        // Handle send test email button click
        $doc.on('click', '.super-send-test-email', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $controls = $button.closest('.super-test-email-controls');
            var $status = $controls.find('.super-test-email-status');
            var $emailPreview = $controls.closest('.super-email-preview');
            
            // Get selected data type
            var dataType = $controls.find('input[name="super_test_email_data_type"]:checked').val();
            var entryId = '';
            if (dataType === 'entry') {
                entryId = $controls.find('.super-test-email-entry-id').val();
            }
            
            // Get test recipient
            var testRecipient = $controls.find('.super-test-email-recipient-input').val();
            if (!testRecipient) {
                $status.removeClass('success loading').addClass('error').html(super_common_i18n.errors.invalid_email).show();
                return;
            }
            
            // Find the current email repeater item
            var $repeaterItem = $emailPreview.closest('.sfui-repeater-item');
            if (!$repeaterItem.length) {
                $status.removeClass('success loading').addClass('error').html('Could not find email settings').show();
                return;
            }
            
            // Collect email settings from the repeater item using same approach as live preview
            var toField = $repeaterItem.find('[name="to"]')[0];
            var fromEmailField = $repeaterItem.find('[name="from_email"]')[0];
            var fromNameField = $repeaterItem.find('[name="from_name"]')[0];
            var subjectField = $repeaterItem.find('[name="subject"]')[0];
            var bodyField = $repeaterItem.find('[name="body"]')[0];
            var ccField = $repeaterItem.find('[name="cc"]')[0];
            var bccField = $repeaterItem.find('[name="bcc"]')[0];
            var attachmentsField = $repeaterItem.find('[name="attachments"]')[0];
            
            var emailSettings = {
                to: toField ? toField.value : '',
                from: fromEmailField ? fromEmailField.value : '',
                from_name: fromNameField ? fromNameField.value : '',
                subject: subjectField ? subjectField.value : '',
                cc: ccField ? ccField.value : '',
                bcc: bccField ? bccField.value : ''
            };
            
            // Check if Reply-To is enabled (nested within reply_to group)
            var replyToEnabledField = $repeaterItem.find('[data-g="reply_to"] [name="enabled"]')[0];
            if (replyToEnabledField && replyToEnabledField.checked) {
                var replyToEmailField = $repeaterItem.find('[data-g="reply_to"] [name="email"]')[0];
                var replyToNameField = $repeaterItem.find('[data-g="reply_to"] [name="name"]')[0];
                emailSettings.reply = replyToEmailField ? replyToEmailField.value : '';
                emailSettings.reply_name = replyToNameField ? replyToNameField.value : '';
            }
            
            // Get body content from TinyMCE editor
            if (bodyField) {
                var bodyValue = '';
                var editorId = bodyField.id;
                if (window.tinymce && editorId && tinymce.get(editorId)) {
                    bodyValue = tinymce.get(editorId).getContent().trim();
                } else {
                    bodyValue = bodyField.value.trim();
                }
                emailSettings.body = bodyValue;
            }
            
            // Collect attachments the same way as live preview
            emailSettings.attachments = [];
            
            // Handle regular file attachments - get attachment IDs from file-preview HTML
            if (attachmentsField && attachmentsField.value.trim()) {
                var filePreview = $repeaterItem.find('.file-preview')[0];
                if (filePreview) {
                    var fileItems = filePreview.querySelectorAll('li[data-file]');
                    fileItems.forEach(function(fileItem) {
                        var attachmentId = fileItem.getAttribute('data-file');
                        if (attachmentId) {
                            emailSettings.attachments.push(attachmentId);
                        }
                    });
                }
            }
            
            // Check CSV attachment settings (nested field name)
            var csvAttachmentField = $repeaterItem.find('[data-g="csv_attachment"] [name="enabled"]')[0];
            if (csvAttachmentField && csvAttachmentField.checked) {
                var csvNameField = $repeaterItem.find('[data-g="csv_attachment"] [name="name"]')[0];
                var csvFilename = (csvNameField && csvNameField.value.trim()) ? csvNameField.value.trim() : 'form-entries.csv';
                emailSettings.csv_enabled = true;
                emailSettings.csv_filename = csvFilename;
            }
            
            // Check XML attachment settings (nested field name)
            var xmlAttachmentField = $repeaterItem.find('[data-g="xml_attachment"] [name="enabled"]')[0];
            if (xmlAttachmentField && xmlAttachmentField.checked) {
                var xmlNameField = $repeaterItem.find('[data-g="xml_attachment"] [name="name"]')[0];
                var xmlFilename = (xmlNameField && xmlNameField.value.trim()) ? xmlNameField.value.trim() : 'form-entries.xml';
                emailSettings.xml_enabled = true;
                emailSettings.xml_filename = xmlFilename;
            }
            
            // Collect loop settings from Advanced options
            var loopOpenField = $repeaterItem.find('[name="loop_open"]')[0];
            var loopField = $repeaterItem.find('[name="loop"]')[0];
            var loopCloseField = $repeaterItem.find('[name="loop_close"]')[0];
            var excludeEmptyField = $repeaterItem.find('[name="exclude_empty"]')[0];
            
            emailSettings.loop_open = loopOpenField ? loopOpenField.value : '<table cellpadding="5">';
            emailSettings.loop = loopField ? loopField.value : '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>';
            emailSettings.loop_close = loopCloseField ? loopCloseField.value : '</table>';
            emailSettings.exclude_empty = excludeEmptyField ? excludeEmptyField.checked : true;
            
            // Disable button and show loading
            $button.prop('disabled', true);
            $status.removeClass('success error').addClass('loading').html('<span class="dashicons dashicons-update super-spin"></span> ' + super_common_i18n.loading).show();
            
            // Prepare AJAX data
            var ajaxData = {
                action: 'super_send_test_email',
                form_id: $('.super-create-form input[name="form_id"]').val(),
                data_type: dataType,
                entry_id: entryId,
                test_recipient: testRecipient,
                email_settings: emailSettings
            };
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    $button.prop('disabled', false);
                    if (response.success) {
                        $status.removeClass('loading error').addClass('success').html('<span class="dashicons dashicons-yes"></span> ' + response.data.message).show();
                    } else {
                        $status.removeClass('loading success').addClass('error').html('<span class="dashicons dashicons-no"></span> ' + response.data.message).show();
                    }
                    
                    // Hide status after 5 seconds
                    setTimeout(function() {
                        $status.fadeOut();
                    }, 5000);
                },
                error: function(_xhr, _status, _error) {
                    $button.prop('disabled', false);
                    $status.removeClass('loading success').addClass('error').html('<span class="dashicons dashicons-no"></span> ' + super_common_i18n.errors.failed).show();
                }
            });
        });
        
        // Watch for new email previews being added
        if (window.MutationObserver) {
            var emailsTabObserver = new MutationObserver(function(mutations) {
                console.log('MutationObserver triggered, mutations:', mutations.length);
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        console.log('Child nodes added:', mutation.addedNodes.length);
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) {
                                // console.log('Processing element node:', node.className);
                                
                                // Check for email previews in the new node
                                var emailPreviews = node.querySelectorAll ? node.querySelectorAll('.super-email-preview') : [];
                                console.log('Email previews found via querySelectorAll:', emailPreviews.length);
                                
                                // Also check if the node itself is an email preview
                                if (node.classList && node.classList.contains('super-email-preview')) {
                                    emailPreviews = [node];
                                    console.log('Node itself is an email preview');
                                }
                                
                                // Also check for email previews that might be nested deeper
                                if (emailPreviews.length === 0) {
                                    // Try to find email previews in any descendant nodes
                                    setTimeout(function() {
                                        var nestedPreviews = node.querySelectorAll ? node.querySelectorAll('.super-email-preview') : [];
                                        console.log('Delayed check for nested email previews:', nestedPreviews.length);
                                        for (var j = 0; j < nestedPreviews.length; j++) {
                                            console.log('Initializing nested email preview');
                                            initializeEmailPreview(nestedPreviews[j]);
                                        }
                                    }, 500); // Wait for DOM to be fully constructed
                                }
                                
                                // Initialize each email preview found
                                for (var i = 0; i < emailPreviews.length; i++) {
                                    console.log('Initializing email preview', i);
                                    initializeEmailPreview(emailPreviews[i]);
                                }
                            }
                        });
                    }
                });
            });
            
            // Start observing the emails tab
            var emailsTab = document.querySelector('.super-tab-emails');
            if (emailsTab) {
                console.log('Starting MutationObserver on emails tab');
                emailsTabObserver.observe(emailsTab, {
                    childList: true,
                    subtree: true
                });
            } else {
                console.log('Could not find emails tab for MutationObserver');
            }
        }
        
        // Initialize existing email previews
        document.querySelectorAll('.super-email-preview').forEach(function(emailPreview) {
            initializeEmailPreview(emailPreview);
        });
        
        // Sticky email preview functionality
        function initializeStickyEmailPreviews() {
            // Function to update the list of email previews
            function updateEmailPreviewsList() {
                return document.querySelectorAll('.super-email-preview');
            }
            
            function handleScroll() {
                var emailPreviews = updateEmailPreviewsList();
                emailPreviews.forEach(function(emailPreview) {
                    var toggle = emailPreview.closest('.sfui-toggle');
                    if (!toggle || !toggle.classList.contains('sfui-open')) {
                        return; // Skip if toggle is not open
                    }
                    
                    var repeaterItem = emailPreview.closest('.sfui-repeater-item');
                    if (!repeaterItem) return;
                    
                    var previewRect = emailPreview.getBoundingClientRect();
                    var repeaterRect = repeaterItem.getBoundingClientRect();
                    var windowHeight = window.innerHeight;
                    
                    // Calculate optimal height based on window size
                    var maxPreviewHeight = Math.min(windowHeight - 100, 800);
                    var emailClient = emailPreview.querySelector('.super-email-client');
                    var emailBody = emailPreview.querySelector('.super-email-body');
                    
                    // Sticky positioning logic
                    var _scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    var toggleRect = toggle.getBoundingClientRect();
                    var shouldStick = toggleRect.top <= 50;

                    // Get toggle width for proper constraints
                    var toggleWidth = toggleRect.width;
                    var _currentPreviewHeight = previewRect.height;
                    
                    // Reset all positioning first
                    emailPreview.classList.remove('sticky', 'sticky-bottom');
                    emailPreview.style.maxHeight = '';
                    emailPreview.style.width = '';
                    emailPreview.style.left = '';
                    emailPreview.style.top = '';
                    emailPreview.style.bottom = '';
                    
                    // Reset parent toggle min-height when not sticky
                    var parentToggle = emailPreview.closest('.sfui-toggle');
                    if (parentToggle && !shouldStick) {
                        parentToggle.style.minHeight = '';
                    }
                    
                    if (!shouldStick) {
                        // Normal positioning - not sticky
                        return;
                    }
                    
                    // Calculate constraints for sticky positioning
                    var repeaterBottom = repeaterRect.bottom;
                    var stickyTop = 50;
                    var bottomMargin = 20; // Add 20px margin from bottom
                    var availableHeight = repeaterBottom - stickyTop - bottomMargin;
                    
                    // Check if we have enough space for sticky positioning
                    if (availableHeight < 100) {
                        // Not enough space, revert to normal positioning
                        return;
                    }
                    
                    // Set min-height on parent toggle to prevent scrollbar issues
                    var parentToggle = emailPreview.closest('.sfui-toggle');
                    if (parentToggle) {
                        var currentHeight = parentToggle.offsetHeight;
                        parentToggle.style.minHeight = Math.max(400, currentHeight) + 'px';
                    }
                    
                    // Apply sticky positioning with constrained height
                    emailPreview.classList.add('sticky');
                    emailPreview.style.width = toggleWidth + 'px';
                    emailPreview.style.left = toggleRect.left + 'px';
                    
                    // Always use the smaller of maxPreviewHeight or availableHeight
                    var constrainedHeight = Math.min(maxPreviewHeight, availableHeight);
                    emailPreview.style.maxHeight = constrainedHeight + 'px';
                    
                    // Set dynamic heights based on the final constrained height
                    if (emailClient) {
                        // Leave some room for padding and proper scrollbar display
                        emailClient.style.maxHeight = (constrainedHeight - 10) + 'px';
                    }
                    if (emailBody && emailClient) {
                        // Calculate height of all children except the email body and test email controls (which are absolutely positioned)
                        var totalOtherHeight = 0;
                        var clientChildren = emailClient.children;
                        
                        for (var i = 0; i < clientChildren.length; i++) {
                            var child = clientChildren[i];
                            // Skip email body and absolutely positioned test email controls
                            if (child !== emailBody && !child.classList.contains('super-test-email-controls')) {
                                totalOtherHeight += child.offsetHeight;
                            }
                        }
                        
                        var scrollbarBuffer = 15; // Extra space for scrollbar arrows and padding
                        var bodyMaxHeight = Math.max(100, constrainedHeight - totalOtherHeight - scrollbarBuffer);
                        emailBody.style.maxHeight = bodyMaxHeight + 'px';
                        emailBody.style.overflowY = 'auto'; // Ensure scrolling is enabled
                    }
                });
            }
            
            // Add scroll listener with throttling for better performance
            var scrollTimeout;
            function throttledHandleScroll() {
                if (scrollTimeout) return;
                scrollTimeout = setTimeout(function() {
                    handleScroll();
                    scrollTimeout = null;
                }, 16); // ~60fps
            }
            
            window.addEventListener('scroll', throttledHandleScroll);
            window.addEventListener('resize', throttledHandleScroll);
            
            // Initial call
            handleScroll();
        }
        
        // Initialize sticky behavior
        initializeStickyEmailPreviews();
        
        // Backup method: Check for new email previews periodically (without logging)
        var backupCheckInterval = setInterval(function() {
            var allPreviews = document.querySelectorAll('.super-email-preview');
            // Only process if there are uninitialized previews
            var hasUninitialized = false;
            allPreviews.forEach(function(emailPreview) {
                if (!emailPreview._superFormsInitialized) {
                    hasUninitialized = true;
                    initializeEmailPreview(emailPreview);
                }
            });
            // Stop checking if all are initialized and we're on a stable state
            if (!hasUninitialized && allPreviews.length > 0) {
                clearInterval(backupCheckInterval);
                // Set up a mutation observer instead for new elements
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && node.classList && node.classList.contains('super-email-preview')) {
                                if (!node._superFormsInitialized) {
                                    initializeEmailPreview(node);
                                }
                            }
                        });
                    });
                });
                var container = document.querySelector('.super-tabs-content');
                if (container) {
                    observer.observe(container, { childList: true, subtree: true });
                }
            }
        }, 2000);
    });

    window.addEventListener('beforeunload', function (e) {
        e = e || window.event;
        var unsaved = SUPER.get_session_data('_super_builder_has_unsaved_changes');
        if(unsaved==='true'){
            var $msg = 'Changes you made may not be saved.';
            e.returnValue = $msg;
            return $msg;
        }
    });

    // Font Awesome Brand Icons for Social Media Icon Picker
    var fontAwesomeBrandIcons = [
        'fab fa-42-group', 'fab fa-500px', 'fab fa-accessible-icon', 'fab fa-accusoft', 'fab fa-adn', 'fab fa-adversal', 'fab fa-affiliatetheme', 'fab fa-airbnb', 'fab fa-algolia', 'fab fa-alipay', 'fab fa-amazon', 'fab fa-amazon-pay', 'fab fa-amilia', 'fab fa-android', 'fab fa-angellist', 'fab fa-angrycreative', 'fab fa-angular', 'fab fa-app-store', 'fab fa-app-store-ios', 'fab fa-apper', 'fab fa-apple', 'fab fa-apple-pay', 'fab fa-artstation', 'fab fa-asymmetrik', 'fab fa-atlassian', 'fab fa-audible', 'fab fa-autoprefixer', 'fab fa-avianex', 'fab fa-aviato', 'fab fa-aws', 'fab fa-bandcamp', 'fab fa-battle-net', 'fab fa-behance', 'fab fa-behance-square', 'fab fa-bilibili', 'fab fa-bimobject', 'fab fa-bitbucket', 'fab fa-bitcoin', 'fab fa-bity', 'fab fa-black-tie', 'fab fa-blackberry', 'fab fa-blogger', 'fab fa-blogger-b', 'fab fa-bluetooth', 'fab fa-bluetooth-b', 'fab fa-bootstrap', 'fab fa-bots', 'fab fa-btc', 'fab fa-buffer', 'fab fa-buromobelexperte', 'fab fa-buy-n-large', 'fab fa-buysellads', 'fab fa-canadian-maple-leaf', 'fab fa-cc-amazon-pay', 'fab fa-cc-amex', 'fab fa-cc-apple-pay', 'fab fa-cc-diners-club', 'fab fa-cc-discover', 'fab fa-cc-jcb', 'fab fa-cc-mastercard', 'fab fa-cc-paypal', 'fab fa-cc-stripe', 'fab fa-cc-visa', 'fab fa-centercode', 'fab fa-centos', 'fab fa-chrome', 'fab fa-chromecast', 'fab fa-cloudflare', 'fab fa-cloudscale', 'fab fa-cloudsmith', 'fab fa-cloudversify', 'fab fa-cmplid', 'fab fa-codepen', 'fab fa-codiepie', 'fab fa-confluence', 'fab fa-connectdevelop', 'fab fa-contao', 'fab fa-cotton-bureau', 'fab fa-cpanel', 'fab fa-creative-commons', 'fab fa-creative-commons-by', 'fab fa-creative-commons-nc', 'fab fa-creative-commons-nc-eu', 'fab fa-creative-commons-nc-jp', 'fab fa-creative-commons-nd', 'fab fa-creative-commons-pd', 'fab fa-creative-commons-pd-alt', 'fab fa-creative-commons-remix', 'fab fa-creative-commons-sa', 'fab fa-creative-commons-sampling', 'fab fa-creative-commons-sampling-plus', 'fab fa-creative-commons-share', 'fab fa-creative-commons-zero', 'fab fa-critical-role', 'fab fa-css3', 'fab fa-css3-alt', 'fab fa-cuttlefish', 'fab fa-d-and-d', 'fab fa-d-and-d-beyond', 'fab fa-dailymotion', 'fab fa-dashcube', 'fab fa-deezer', 'fab fa-delicious', 'fab fa-deploydog', 'fab fa-deskpro', 'fab fa-dev', 'fab fa-deviantart', 'fab fa-dhl', 'fab fa-diaspora', 'fab fa-digg', 'fab fa-digital-ocean', 'fab fa-discord', 'fab fa-discourse', 'fab fa-dochub', 'fab fa-docker', 'fab fa-draft2digital', 'fab fa-dribbble', 'fab fa-dribbble-square', 'fab fa-dropbox', 'fab fa-drupal', 'fab fa-dyalog', 'fab fa-earlybirds', 'fab fa-ebay', 'fab fa-edge', 'fab fa-edge-legacy', 'fab fa-elementor', 'fab fa-ello', 'fab fa-ember', 'fab fa-empire', 'fab fa-envira', 'fab fa-erlang', 'fab fa-ethereum', 'fab fa-etsy', 'fab fa-evernote', 'fab fa-expeditedssl', 'fab fa-facebook', 'fab fa-facebook-f', 'fab fa-facebook-messenger', 'fab fa-facebook-square', 'fab fa-fantasy-flight-games', 'fab fa-fedex', 'fab fa-fedora', 'fab fa-figma', 'fab fa-firefox', 'fab fa-firefox-browser', 'fab fa-first-order', 'fab fa-first-order-alt', 'fab fa-firstdraft', 'fab fa-flickr', 'fab fa-flipboard', 'fab fa-fly', 'fab fa-font-awesome', 'fab fa-fonticons', 'fab fa-fonticons-fi', 'fab fa-fort-awesome', 'fab fa-fort-awesome-alt', 'fab fa-forumbee', 'fab fa-foursquare', 'fab fa-free-code-camp', 'fab fa-freebsd', 'fab fa-fulcrum', 'fab fa-galactic-republic', 'fab fa-galactic-senate', 'fab fa-get-pocket', 'fab fa-gg', 'fab fa-gg-circle', 'fab fa-git', 'fab fa-git-alt', 'fab fa-git-square', 'fab fa-github', 'fab fa-github-alt', 'fab fa-github-square', 'fab fa-gitkraken', 'fab fa-gitlab', 'fab fa-gitter', 'fab fa-glide', 'fab fa-glide-g', 'fab fa-gofore', 'fab fa-golang', 'fab fa-goodreads', 'fab fa-goodreads-g', 'fab fa-google', 'fab fa-google-drive', 'fab fa-google-pay', 'fab fa-google-play', 'fab fa-google-plus', 'fab fa-google-plus-g', 'fab fa-google-plus-square', 'fab fa-google-wallet', 'fab fa-gratipay', 'fab fa-grav', 'fab fa-gripfire', 'fab fa-grunt', 'fab fa-guilded', 'fab fa-gulp', 'fab fa-hacker-news', 'fab fa-hacker-news-square', 'fab fa-hackerrank', 'fab fa-hashnode', 'fab fa-hips', 'fab fa-hire-a-helper', 'fab fa-hive', 'fab fa-hooli', 'fab fa-hornbill', 'fab fa-hotjar', 'fab fa-houzz', 'fab fa-html5', 'fab fa-hubspot', 'fab fa-ideal', 'fab fa-imdb', 'fab fa-instagram', 'fab fa-instagram-square', 'fab fa-instalod', 'fab fa-intercom', 'fab fa-internet-explorer', 'fab fa-invision', 'fab fa-ioxhost', 'fab fa-itch-io', 'fab fa-itunes', 'fab fa-itunes-note', 'fab fa-java', 'fab fa-jedi-order', 'fab fa-jenkins', 'fab fa-jira', 'fab fa-joget', 'fab fa-joomla', 'fab fa-js', 'fab fa-js-square', 'fab fa-jsfiddle', 'fab fa-kaggle', 'fab fa-keybase', 'fab fa-keycdn', 'fab fa-kickstarter', 'fab fa-kickstarter-k', 'fab fa-korvue', 'fab fa-laravel', 'fab fa-lastfm', 'fab fa-lastfm-square', 'fab fa-leanpub', 'fab fa-less', 'fab fa-line', 'fab fa-linkedin', 'fab fa-linkedin-in', 'fab fa-linode', 'fab fa-linux', 'fab fa-lyft', 'fab fa-magento', 'fab fa-mailchimp', 'fab fa-mandalorian', 'fab fa-markdown', 'fab fa-mastodon', 'fab fa-maxcdn', 'fab fa-mdb', 'fab fa-medapps', 'fab fa-medium', 'fab fa-medrt', 'fab fa-meetup', 'fab fa-megaport', 'fab fa-mendeley', 'fab fa-microblog', 'fab fa-microsoft', 'fab fa-mix', 'fab fa-mixcloud', 'fab fa-mixer', 'fab fa-mizuni', 'fab fa-modx', 'fab fa-monero', 'fab fa-napster', 'fab fa-neos', 'fab fa-nimblr', 'fab fa-node', 'fab fa-node-js', 'fab fa-npm', 'fab fa-ns8', 'fab fa-nutritionix', 'fab fa-octopus-deploy', 'fab fa-odnoklassniki', 'fab fa-odnoklassniki-square', 'fab fa-old-republic', 'fab fa-opencart', 'fab fa-openid', 'fab fa-opera', 'fab fa-optin-monster', 'fab fa-orcid', 'fab fa-osi', 'fab fa-padlet', 'fab fa-page4', 'fab fa-pagelines', 'fab fa-palfed', 'fab fa-patreon', 'fab fa-paypal', 'fab fa-perbyte', 'fab fa-periscope', 'fab fa-phabricator', 'fab fa-phoenix-framework', 'fab fa-phoenix-squadron', 'fab fa-php', 'fab fa-pied-piper', 'fab fa-pied-piper-alt', 'fab fa-pied-piper-hat', 'fab fa-pied-piper-pp', 'fab fa-pied-piper-square', 'fab fa-pinterest', 'fab fa-pinterest-p', 'fab fa-pinterest-square', 'fab fa-pix', 'fab fa-playstation', 'fab fa-product-hunt', 'fab fa-pushed', 'fab fa-python', 'fab fa-qq', 'fab fa-quinscape', 'fab fa-quora', 'fab fa-r-project', 'fab fa-raspberry-pi', 'fab fa-ravelry', 'fab fa-react', 'fab fa-reacteurope', 'fab fa-readme', 'fab fa-rebel', 'fab fa-red-river', 'fab fa-reddit', 'fab fa-reddit-alien', 'fab fa-reddit-square', 'fab fa-redhat', 'fab fa-renren', 'fab fa-replyd', 'fab fa-researchgate', 'fab fa-resolving', 'fab fa-rev', 'fab fa-rocketchat', 'fab fa-rockrms', 'fab fa-rust', 'fab fa-safari', 'fab fa-salesforce', 'fab fa-sass', 'fab fa-schlix', 'fab fa-scribd', 'fab fa-searchengin', 'fab fa-sellcast', 'fab fa-sellsy', 'fab fa-servicestack', 'fab fa-shirtsinbulk', 'fab fa-shopify', 'fab fa-shopware', 'fab fa-simplybuilt', 'fab fa-sistrix', 'fab fa-sith', 'fab fa-sitrox', 'fab fa-sketch', 'fab fa-skyatlas', 'fab fa-skype', 'fab fa-slack', 'fab fa-slideshare', 'fab fa-snapchat', 'fab fa-snapchat-square', 'fab fa-soundcloud', 'fab fa-sourcetree', 'fab fa-speakap', 'fab fa-speaker-deck', 'fab fa-spotify', 'fab fa-square-font-awesome', 'fab fa-square-font-awesome-stroke', 'fab fa-squarespace', 'fab fa-stack-exchange', 'fab fa-stack-overflow', 'fab fa-stackpath', 'fab fa-staylinked', 'fab fa-steam', 'fab fa-steam-square', 'fab fa-steam-symbol', 'fab fa-sticker-mule', 'fab fa-strava', 'fab fa-stripe', 'fab fa-stripe-s', 'fab fa-studiovinari', 'fab fa-stumbleupon', 'fab fa-stumbleupon-circle', 'fab fa-superpowers', 'fab fa-supple', 'fab fa-suse', 'fab fa-swift', 'fab fa-symfony', 'fab fa-teamspeak', 'fab fa-telegram', 'fab fa-tencent-weibo', 'fab fa-the-red-yeti', 'fab fa-themeco', 'fab fa-themeisle', 'fab fa-think-peaks', 'fab fa-tiktok', 'fab fa-trade-federation', 'fab fa-trello', 'fab fa-tumblr', 'fab fa-tumblr-square', 'fab fa-twitch', 'fab fa-twitter', 'fab fa-twitter-square', 'fab fa-typo3', 'fab fa-uber', 'fab fa-ubuntu', 'fab fa-uikit', 'fab fa-umbraco', 'fab fa-uncharted', 'fab fa-uniregistry', 'fab fa-unity', 'fab fa-unsplash', 'fab fa-untappd', 'fab fa-ups', 'fab fa-usb', 'fab fa-usps', 'fab fa-ussunnah', 'fab fa-vaadin', 'fab fa-viacoin', 'fab fa-viadeo', 'fab fa-viadeo-square', 'fab fa-viber', 'fab fa-vimeo', 'fab fa-vimeo-square', 'fab fa-vimeo-v', 'fab fa-vine', 'fab fa-vk', 'fab fa-vnv', 'fab fa-vuejs', 'fab fa-watchman-monitoring', 'fab fa-waze', 'fab fa-weebly', 'fab fa-weibo', 'fab fa-weixin', 'fab fa-whatsapp', 'fab fa-whatsapp-square', 'fab fa-whmcs', 'fab fa-wikipedia-w', 'fab fa-windows', 'fab fa-wirsindhandwerk', 'fab fa-wix', 'fab fa-wizards-of-the-coast', 'fab fa-wodu', 'fab fa-wolf-pack-battalion', 'fab fa-wordpress', 'fab fa-wordpress-simple', 'fab fa-wpbeginner', 'fab fa-wpexplorer', 'fab fa-wpforms', 'fab fa-wpressr', 'fab fa-xbox', 'fab fa-xing', 'fab fa-xing-square', 'fab fa-y-combinator', 'fab fa-yahoo', 'fab fa-yammer', 'fab fa-yandex', 'fab fa-yandex-international', 'fab fa-yarn', 'fab fa-yelp', 'fab fa-yoast', 'fab fa-youtube', 'fab fa-youtube-square', 'fab fa-zhihu'
    ];

    // Social Media Icon Picker functionality
    $(document).on('click', '.super-social-icon-picker-btn', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var picker = button.closest('.super-social-icon-picker');
        var hiddenInput = picker.find('input[type="hidden"]');
        var _display = picker.find('.super-social-icon-display');
        
        // Create modal if it doesn't exist
        if (!$('#super-social-icon-modal').length) {
            var modalHTML = '<div id="super-social-icon-modal" class="super-modal" style="display:none;">' +
                '<div class="super-modal-content">' +
                    '<div class="super-modal-header">' +
                        '<span class="super-modal-close">&times;</span>' +
                        '<h3>Choose Font Awesome Brand Icon</h3>' +
                    '</div>' +
                    '<div class="super-modal-body">' +
                        '<div class="super-icon-search-wrapper">' +
                            '<input type="text" id="super-icon-search" placeholder="Search icons..." />' +
                        '</div>' +
                        '<div class="super-icon-grid"></div>' +
                    '</div>' +
                    '<div class="super-modal-footer">' +
                        '<button type="button" class="button super-modal-cancel">Cancel</button>' +
                        '<button type="button" class="button button-primary super-modal-clear">Clear Selection</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            $('body').append(modalHTML);
            
            // Populate icon grid
            var iconGrid = $('#super-social-icon-modal .super-icon-grid');
            fontAwesomeBrandIcons.forEach(function(iconClass) {
                iconGrid.append('<div class="super-icon-item" data-icon="' + iconClass + '"><i class="' + iconClass + '"></i><span>' + iconClass.replace('fab fa-', '') + '</span></div>');
            });
        }
        
        // Show modal
        $('#super-social-icon-modal').show();
        
        // Highlight current selection
        var currentValue = hiddenInput.val();
        $('.super-icon-item').removeClass('selected');
        if (currentValue) {
            $('.super-icon-item[data-icon="' + currentValue + '"]').addClass('selected');
        }
        
        // Store reference to current picker
        $('#super-social-icon-modal').data('currentPicker', picker);
    });

    // Modal close functionality
    $(document).on('click', '.super-modal-close, .super-modal-cancel', function() {
        $('#super-social-icon-modal').hide();
    });

    // Icon selection
    $(document).on('click', '.super-icon-item', function() {
        var iconClass = $(this).data('icon');
        var picker = $('#super-social-icon-modal').data('currentPicker');
        
        // Update hidden input
        var hiddenInput = picker.find('input[type="hidden"]');
        hiddenInput.val(iconClass).trigger('change');
        
        // Update display
        var display = picker.find('.super-social-icon-display');
        display.find('i').attr('class', iconClass);
        display.find('.super-social-icon-name').text(iconClass);
        
        // Close modal
        $('#super-social-icon-modal').hide();
    });

    // Clear selection
    $(document).on('click', '.super-modal-clear', function() {
        var picker = $('#super-social-icon-modal').data('currentPicker');
        
        // Clear hidden input
        var hiddenInput = picker.find('input[type="hidden"]');
        hiddenInput.val('').trigger('change');
        
        // Reset display
        var display = picker.find('.super-social-icon-display');
        display.find('i').attr('class', 'fas fa-plus');
        display.find('.super-social-icon-name').text('Select an icon');
        
        // Close modal
        $('#super-social-icon-modal').hide();
    });

    // Search functionality
    $(document).on('keyup', '#super-icon-search', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('.super-icon-item').each(function() {
            var iconName = $(this).data('icon').toLowerCase();
            if (iconName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Close modal when clicking outside
    $(document).on('click', '#super-social-icon-modal', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

})(jQuery);
