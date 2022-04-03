/* globals jQuery, SUPER, super_create_form_i18n, ajaxurl */
"use strict";
(function ($) { // Hide scope, no $ conflict

    function isEmpty(obj) {
        for (var prop in obj) {
            if (obj.hasOwnProperty(prop)) {
                return false;
            }
        }
        return JSON.stringify(obj) === JSON.stringify({});
    }
    SUPER.ui = {
        btn: function(e, el, action){
            if(action==='toggleListingSettings'){
                var node = el.closest('.sfui-repeater-item').querySelector('.sfui-setting-group');
                if(node.classList.contains('sfui-active')){
                    node.classList.remove('sfui-active');
                }else{
                    node.classList.add('sfui-active');
                }
                e.preventDefault();
                return false;
            }
            if(action==='addRepeaterItem'){
                var clone = el.closest('.sfui-repeater-item').cloneNode(true);
                el.closest('.sfui-repeater').appendChild(clone);
                e.preventDefault();
                return false;
            }
            if(action==='deleteRepeaterItem'){
                // Do not delete last item
                if(el.closest('.sfui-repeater').querySelectorAll(':scope > .sfui-repeater-item').length>1){
                    el.closest('.sfui-repeater-item').remove();
                }
                e.preventDefault();
                return false;
            }
        },
         
        // Show/Hide sub settings
        showHideSubsettings: function(el){
            var i,
                nodes,
                filter,
                value,
                node,
                tab;

            if(el){
                tab = el.closest('.super-tab-content');
            }else{
                tab = document.querySelector('.super-tabs-content');
            }
            nodes = tab.querySelectorAll('.sfui-sub-settings');
            for(i=0; i < nodes.length; i++){
                if(!nodes[i].dataset.f) continue;
                value = '';
                filter = nodes[i].dataset.f.split(';');
                tab = nodes[i].closest('.super-tab-content');
                if(nodes[i].closest('.sfui-repeater-item')){
                    tab = nodes[i].closest('.sfui-repeater-item');
                }
                node = tab.querySelectorAll('[name="'+filter[0]+'"]');
                if(node.length>=1){
                    // Radio or checkbox?
                    if(node[0].type==='checkbox' || node[0].type==='radio'){
                        value = (tab.querySelector('[name="'+filter[0]+'"]:checked') ? tab.querySelector('[name="'+filter[0]+'"]:checked').value : '');
                        nodes[i].classList.remove('sfui-active');
                        if(filter[1]===value){
                            nodes[i].classList.add('sfui-active');
                        }
                        continue;
                    }
                    if(node[0].type==='select-one'){
                        value = node[0].options[node[0].selectedIndex].value;
                        nodes[i].parentNode.querySelector('.sfui-sub-settings').classList.remove('sfui-active');
                        if(filter[1].split(',').indexOf(value)!==-1){
                            nodes[i].parentNode.querySelector('.sfui-sub-settings').classList.add('sfui-active');
                        }
                        continue;
                    }
                }
            }
        },
        // Update form settings
        updateSettings: function(e, el){
            SUPER.ui.showHideSubsettings(el);
            // Update form settings
            SUPER.update_form_settings(true);
        }
    };
    SUPER.update_form_elements = function(string){
        document.querySelector('.super-raw-code-form-elements textarea').value = SUPER.get_form_elements(string);
    };
    SUPER.update_form_settings = function(string){
        document.querySelector('.super-raw-code-form-settings textarea').value = SUPER.get_form_settings(string);
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
    SUPER.get_form_settings = function(string){
        if(typeof string === 'undefined') string = false;
        var $settings = {};
        var includeGlobalValues = document.querySelector('input[name="retain_underlying_global_values"]').checked;
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
        // PDF settings
        $settings = SUPER.get_tab_settings($settings, 'pdf');
        // Listing settings
        $settings = SUPER.get_tab_settings($settings, 'listings');
        // Stripe settings
        //$settings = SUPER.get_tab_settings($settings, 'stripe');
        if(string===true) {
            if(!isEmpty($settings)) return JSON.stringify($settings, undefined, 4);
            return '';
        }
        return $settings;
    };
    SUPER.processRepeaterItems = function(args){
        var i, x, k = args.node.dataset.k, nodes = args.node.querySelectorAll(':scope > .sfui-repeater-item'),
            subData, keys, fields, value, names,
            parentRepeater;
        for(i=0; i<nodes.length; i++){
            // Check if key consist of multiple levels
            keys = k.split('.');
            if(keys.length>1){
                if(typeof args.data[keys[0]] === 'undefined') args.data[keys[0]] = {};
                if(typeof args.data[keys[0]][keys[1]] === 'undefined') args.data[keys[0]][keys[1]] = {};
                if(typeof args.data[keys[0]][keys[1]][i] === 'undefined') args.data[keys[0]][keys[1]][i] = {};
            }else{
                if(typeof args.data[k] === 'undefined') args.data[k] = {};
                if(typeof args.data[k][i] === 'undefined') args.data[k][i] = {};
            }
            x, fields = nodes[i].querySelectorAll('[name]');
            for(x=0; x<fields.length; x++){
                parentRepeater = fields[x].closest('.sfui-repeater');
                if(parentRepeater && parentRepeater!==args.node){
                    // is inner repeater, must process it
                    if(keys.length>1){
                        args.data[keys[0]][keys[1]][i] = SUPER.processRepeaterItems({tab: args.tab, node: parentRepeater, depth: args.depth, data: args.data[keys[0]][keys[1]][i]});
                    }else{
                        args.data[k][i] = SUPER.processRepeaterItems({tab: args.tab, node: parentRepeater, depth: args.depth, data: args.data[k][i]});
                    }
                    continue;
                }
                // is direct inner field, must add it to the data
                value = fields[x].value;
                if(fields[x].type==='checkbox') value = fields[x].checked;
                if(fields[x].type==='radio') value = (args.tab.querySelector('[name="'+fields[x].name+'"]:checked') ? args.tab.querySelector('[name="'+fields[x].name+'"]:checked').value : '');
                if(value===true) value = "true"; 
                if(value===false) value = "false"; 
                names = fields[x].name.split('.');
                if(names.length>1){
                    if(keys.length>1){
                        subData = args.data[keys[0]][keys[1]][i];
                    }else{
                        subData = args.data[k][i];
                    }
                    if(typeof subData[names[0]] === 'undefined') subData[names[0]] = {};
                    if(names.length===2){
                        subData[names[0]][names[1]] = value;
                    }else{
                        if(names.length===3){
                            if(typeof subData[names[0]][names[1]] === 'undefined') subData[names[0]][names[1]] = {};
                            subData[names[0]][names[1]][names[2]] = value;
                        }else{
                            if(names.length===4){
                                if(typeof subData[names[0]][names[1]][names[2]] === 'undefined') subData[names[0]][names[1]][names[2]] = {};
                                subData[names[0]][names[1]][names[2]][names[3]] = value;
                            }
                        }
                    }
                    if(keys.length>1){
                        args.data[keys[0]][keys[1]][i] = subData;
                    }else{
                        args.data[k][i] = subData;
                    }
                }else{
                    if(keys.length>1){
                        args.data[keys[0]][keys[1]][i][names[0]] = value;
                    }else{
                        args.data[k][i][names[0]] = value;
                    }
                }
            }
        }
        return args.data;
    };
    SUPER.get_tab_settings = function(settings, slug){
        var i, nodes, p, sub, repeater, value, name, names, tab = document.querySelector('.super-tab-content.super-tab-'+slug), data = {};
        if(tab){
            // First grab all settings that are not inside a repeater element
            nodes = tab.querySelectorAll('.sfui-setting > label > [name]');
            for(i=0; i<nodes.length; i++){
                repeater = nodes[i].closest('.sfui-repeater-item');
                if(repeater) continue; // skip if inside repater element
                // is direct inner field, must add it to the data
                value = nodes[i].value;
                if(nodes[i].type==='checkbox') value = nodes[i].checked;
                if(nodes[i].type==='radio') value = (tab.querySelector('[name="'+nodes[i].name+'"]:checked') ? tab.querySelector('[name="'+nodes[i].name+'"]:checked').value : '');
                if(value===true) value = "true"; 
                if(value===false) value = "false"; 
                name = nodes[i].name;
                names = name.split('.');
                if(names.length>1){
                    if(typeof data[names[0]] === 'undefined') data[names[0]] = {};
                    if(names.length>2){
                        if(typeof data[names[0]][names[1]] === 'undefined') data[names[0]][names[1]] = {};
                        data[names[0]][names[1]][names[2]] = value;
                    }else{
                        data[names[0]][names[1]] = value;
                    }
                }else{
                    data[name] = value;
                }
                p = nodes[i].closest('.sfui-setting');
                if(p){
                    sub = p.querySelector('.sfui-sub-settings');
                    if(sub){
                        var x, xnodes = sub.querySelectorAll(':scope > .sfui-repeater');
                        for(x=0; x<xnodes.length; x++){
                            data = SUPER.processRepeaterItems({tab: tab, node: xnodes[x], depth: 0, data: data});
                        }
                    }
                }
            }
            settings['_'+slug] = data;
        }
        return settings;
    }
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
    SUPER.regenerate_element_inner = function ($history) {
        if (typeof $history === 'undefined') $history = true;
        var $elements, $old_code;
        SUPER.set_session_data('_super_builder_has_unsaved_changes', 'true');
        $old_code = document.querySelector('.super-raw-code-form-elements > textarea').value;
        $elements = SUPER.get_form_elements(true);
        SUPER.update_form_settings(true);
        SUPER.update_translation_settings(true);
        document.querySelector('.super-raw-code-form-elements > textarea').value = $elements;
        if ($history) SUPER.trigger_redo_undo($elements, $old_code);
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
                SUPER.regenerate_element_inner();
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
                SUPER.regenerate_element_inner();
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
                            $items.push({
                                checked: $checked,
                                image: $this.find('input[name="image"]').val(),
                                max_width: $this.find('input[name="max_width"]').val(),
                                max_height: $this.find('input[name="max_height"]').val(),
                                label: $this.children('input[name="label"]').val(),
                                value: $this.children('input[name="value"]').val()
                            });
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
                                SUPER.regenerate_element_inner();
                                SUPER.init_common_fields();
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
            SUPER.regenerate_element_inner(false);
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
                    $('.super-create-form .super-actions .super-save').html('<i class="fas fa-save"></i>Save');
                    SUPER.set_session_data('_super_builder_has_unsaved_changes', false);
                    var response = this.responseText;
                    response = JSON.parse(response);
                    if(response.error===true){
                        // Display error message
                        alert(response.msg);
                    }else{
                        $('.super-create-form .super-header .super-get-form-shortcodes').val('[super_form id="' + response + '"]');
                        $('.super-create-form input[name="form_id"]').val(response);
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
                                $('.super-create-form .super-actions .super-save').html('<i class="fas fa-save"></i>Save');
                                return false;
                            } else {
                                var href = window.location.href;
                                var page = href.substr(href.lastIndexOf('/') + 1);
                                var str2 = "admin.php?page=super_create_form&id";
                                if (page.indexOf(str2) == -1) {
                                    window.location.href = "admin.php?page=super_create_form&id=" + response;
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

        var params = {
            action: 'super_save_form',
            form_id: $('.super-create-form input[name="form_id"]').val(),
            title: $('.super-create-form input[name="title"]').val(),
            formElements: document.querySelector('.super-raw-code-form-elements textarea').value,
            formSettings: document.querySelector('.super-raw-code-form-settings textarea').value,
            translationSettings: document.querySelector('.super-raw-code-translation-settings textarea').value, // @since 4.7.0 translation
            i18n: $initial_i18n, // @since 4.7.0 translation
            i18n_switch: ($('.super-i18n-switch').hasClass('super-active') ? 'true' : 'false') // @since 4.7.0 translation
        };

        // @since 4.9.6 - secrets
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
            var $form_id = $('.super-create-form input[name="form_id"]').val();
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
                    SUPER.files = [];
                    SUPER.handle_columns();
                    SUPER.init_button_colors();
                    SUPER.init_super_responsive_form_fields({form: $('.super-live-preview > .super-form')[0]});
                    SUPER.init_super_form_frontend();
                    SUPER.after_preview_loaded_hook($form_id);
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
                form_id: $form_id
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
            $('.super-create-form textarea[name="email_body_open"]').val($('.super-create-form textarea[name="wizard_email_body_open"]').val());

            $('.super-create-form input[name="confirm_to"]').val($('.super-create-form input[name="wizard_confirm_to"]').val());
            $('.super-create-form select[name="confirm_from_type"]').val('custom');
            $('.super-create-form input[name="confirm_from"]').val($('.super-create-form input[name="wizard_confirm_from"]').val());
            $('.super-create-form input[name="confirm_from_name"]').val($('.super-create-form input[name="wizard_confirm_from_name"]').val());
            $('.super-create-form input[name="confirm_subject"]').val($('.super-create-form input[name="wizard_confirm_subject"]').val());
            $('.super-create-form textarea[name="confirm_body_open"]').val($('.super-create-form textarea[name="wizard_confirm_body_open"]').val());

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

    SUPER.initTinyMCE = function(selector){
        tinymce.remove();
        tinymce.init({
            selector: selector, 
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
        });
    };

    jQuery(document).ready(function ($) {

        SUPER.ui.showHideSubsettings();

        $('body.wp-admin').addClass('folded');
        init_form_settings_container_heights();

        var $doc = $(document),
            $super_hints,
            $super_hints_steps,
            $node,
            $activePanel = SUPER.get_session_data('_super_builder_last_active_panel'),
            $activeFormSettingsTab = SUPER.get_session_data('_super_builder_last_active_form_settings_tab'),
            $activeElementSettingsTab = SUPER.get_session_data('_super_builder_last_active_element_settings_tab');

        document.querySelector('.super-raw-code-form-settings textarea').value = SUPER.get_form_settings(true);
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
                SUPER.set_session_data('_super_builder_last_active_element_settings_tab', $(this).val());
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
            clone.querySelector('.super-secret-tag').innerHTML = '&nbsp;';
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
                this.closest('li').querySelector('.super-secret-tag').innerHTML = '';
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
            var html,
                n1 = document.querySelector('.super-raw-code-form-elements .sfui-notice'),
                n2 = document.querySelector('.super-raw-code-form-settings .sfui-notice'),
                n3 = document.querySelector('.super-raw-code-translation-settings .sfui-notice'),
                formElements = document.querySelector('.super-raw-code-form-elements textarea').value,
                formSettings = document.querySelector('.super-raw-code-form-settings textarea').value,
                translationSettings = document.querySelector('.super-raw-code-translation-settings textarea').value;

            // Handle non-exception-throwing cases:
            // Neither JSON.parse(false) or JSON.parse(1234) throw errors, hence the type-checking,
            // but... JSON.parse(null) returns null, and typeof null === "object", 
            // so we must check for that, too. Thankfully, null is falsey, so this suffices:

            try {
                (formElements!=='' ? JSON.parse(formElements) : {});
            }
            catch (e) {
                html = '<strong>'+super_create_form_i18n.invalid_json+'</strong>';
                html += '<br /><br />------<br />'+e+'<br />------<br /><br />';
                html += super_create_form_i18n.try_jsonlint;
                n1.innerHTML = html;
                n1.classList.remove('sfui-yellow');
                n1.classList.add('sfui-red');
                document.querySelector('.super-raw-code-form-elements textarea').classList.add('sfui-red');
                n1.scrollIntoView();
                return false;
            }
            n1.innerHTML = super_create_form_i18n.edit_json_notice_n1;          
            n1.classList.remove('sfui-red');
            n1.classList.add('sfui-yellow');
            document.querySelector('.super-raw-code-form-elements textarea').classList.remove('sfui-red');

            try {
                (formSettings!=='' ? JSON.parse(formSettings) : {});
            }
            catch (e) {
                html = '<strong>'+super_create_form_i18n.invalid_json+'</strong>';
                html += '<br /><br />------<br />'+e+'<br />------<br /><br />';
                html += super_create_form_i18n.try_jsonlint;
                n2.innerHTML = html;
                n2.classList.remove('sfui-yellow');
                n2.classList.add('sfui-red');
                document.querySelector('.super-raw-code-form-settings textarea').classList.add('sfui-red');
                n2.scrollIntoView();
                return false;
            }
            n2.innerHTML = super_create_form_i18n.edit_json_notice_n2;
            n2.classList.remove('sfui-red');
            n2.classList.add('sfui-yellow');
            document.querySelector('.super-raw-code-form-settings textarea').classList.remove('sfui-red');

            try {
                (translationSettings!=='' ? JSON.parse(translationSettings) : {});
            }
            catch (e) {
                html = '<strong>'+super_create_form_i18n.invalid_json+'</strong>';
                html += '<br /><br />------<br />'+e+'<br />------<br /><br />';
                html += super_create_form_i18n.try_jsonlint;
                n3.innerHTML = html;
                n3.classList.remove('sfui-yellow');
                n3.classList.add('sfui-red');
                document.querySelector('.super-raw-code-translation-settings textarea').classList.add('sfui-red');
                n3.scrollIntoView();
                return false;
            }
            n3.innerHTML = super_create_form_i18n.edit_json_notice_n3;
            n3.classList.remove('sfui-red');
            n3.classList.add('sfui-yellow');
            document.querySelector('.super-raw-code-translation-settings textarea').classList.remove('sfui-red');

            // Add loading state to button
            var button = this;
            var oldHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-save"></i>'+super_create_form_i18n.save_loading;
            SUPER.save_form($('.super-actions .super-save'), 2, undefined, undefined, function(){
                button.innerHTML = oldHtml;
            }, true);
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
                document.querySelector('.super-raw-code-translation-settings textarea').value = SUPER.get_translation_settings(true);
            }
            $('.super-tabs-content').css('display', '');
            $('.super-preview.switch').removeClass('super-active');
            $('.super-live-preview').css('display', 'none');
            $('.super-preview.switch').removeClass('super-active');
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
        });
        // edit translation
        $doc.on('click', '.super-translations-list .super-edit', function () {
            var $row = $(this).parent(),
                $language = $row.find('.super-dropdown[data-name="language"] .super-active'),
                $language_title = $language.html(),
                $flag = $row.find('.super-dropdown[data-name="flag"] .super-active'),
                $tab = $('.super-tabs .super-tab-builder'),
                $initial_i18n = $('.super-preview-elements').attr('data-i18n');

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

            // Display loading icon, and hide all elements/settings
            $('.super-preview-elements, .super-form-settings').addClass('super-loading');

            // Always check if user was updating an element, if so cancel it
            cancel_update();

            if (typeof $language_changed !== 'undefined') {
                $('.super-preview-elements').attr('data-language-changed', null);
            }

            // Always save the form before switching to a different language
            // This will prevent loading "old" / "unsaved" form elements and settings
            SUPER.save_form($('.super-actions .super-save'), 3, $(this), $initial_i18n, function ($button) {
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
                        data = JSON.parse(data);
                        $('.super-preview-elements').html(data.elements);
                        SUPER.init_common_fields();
                        $('.super-form-settings .super-elements-container').html(data.settings);
                        $('.super-preview-elements, .super-form-settings').removeClass('super-loading');
                    },
                    error: function () {
                        alert(super_create_form_i18n.export_form_error);
                    },
                    complete: function () {
                        init_form_settings_container_heights();
                        // Disable sortable functionality
                        if ($('.super-create-form').hasClass('super-translation-mode')) {
                            $('.super-preview-elements').sortable('disable');
                        } else {
                            // Enable sortable functionality
                            $('.super-preview-elements').sortable('enable');
                        }
                    }
                });
            });
        });
        // delete translation
        $doc.on('click', '.super-translations-list .super-delete', function () {
            var $delete = confirm(super_create_form_i18n.confirm_deletion);
            if ($delete === true) {
                // Before removing language check if currently in translation mode for this language
                // If this is the case we must switch back to the default language and thus the "builder" mode
                if ($('.super-create-form').hasClass('super-translation-mode')) {
                    var $row = $(this).parent(),
                        $language = $row.find('.super-dropdown[data-name="language"] .super-active'),
                        $flag = $('.super-default-language .super-dropdown[data-name="flag"] .super-active'),
                        $i18n = $('.super-preview-elements').attr('data-i18n'),
                        $tab = $('.super-tabs .super-tab-builder');
                    if ($language) {
                        $language = $language.attr('data-value');
                        if ($language == $i18n) {
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
            SUPER.regenerate_element_inner();
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

        $doc.on('click', '.super-skip-wizard, .super-first-time-setup-bg', function () {
            $('.super-first-time-setup, .super-first-time-setup-bg').removeClass('super-active');
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
            SUPER.regenerate_element_inner();
        });

        $doc.on('click', '.super-element-actions .super-duplicate', function () {
            var $parent = $(this).parents('.super-element:eq(0)');
            $parent.find('.tooltip').remove();
            var $new = $parent.clone();

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
            $new.slideUp(0);
            $new.slideDown(300);
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner();
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
            SUPER.regenerate_element_inner();
            localStorage.removeItem('_super_transfer_element_html');
            $('.super-preview-elements').removeClass('super-transfering');
        });
        $doc.on('click', '.super-preview-elements.super-transfering', function (e) {
            if ($(e.target).hasClass('super-preview-elements')) {
                var $html = SUPER.get_session_data('_super_transfer_element_html', 'local');
                $($html).appendTo($(this));
                SUPER.init_drag_and_drop();
                SUPER.regenerate_element_inner();
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
            SUPER.regenerate_element_inner();
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
            SUPER.regenerate_element_inner();
        });
        $doc.on('click', '.super-element-actions .super-delete', function () {
            $(this).parents('.super-element:eq(0)').remove();
            SUPER.init_drag_and_drop();
            SUPER.regenerate_element_inner();
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
            SUPER.regenerate_element_inner();
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
            var $translating = $('.super-create-form').hasClass('super-translation-mode');
            // Always get possible translation data from current element
            $element_data = JSON.parse($element.children('textarea[name="element-data"]').val());
            // Check if in translation mode
            if ($translating) {
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
                        } catch (e) {
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
                    SUPER.regenerate_element_inner();
                    SUPER.init_common_fields();
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
                translating: $('.super-create-form').hasClass('super-translation-mode'),
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
                        SUPER.initTinyMCE('.super-element-settings .super-textarea-tinymce');
                        // Open up last element settings tab
                        $activeElementSettingsTab = SUPER.get_session_data('_super_builder_last_active_element_settings_tab')
                        if($activeElementSettingsTab){
                            $node = $('.super-element-settings .super-elements-container .tab-content:eq(' + $activeElementSettingsTab + ')');
                            if($node.length){
                                $('.super-element-settings .super-elements-container .tab-content.super-active').removeClass('super-active');
                                $node.addClass('super-active');
                                $('.super-element-settings-tabs > select > option[selected]').prop({selected: false});
                                $('.super-element-settings-tabs > select > option:eq(' + $activeElementSettingsTab + ')').prop({selected: true});
                            }
                        }
                    }
                    // Complete:
                    SUPER.init_previously_created_fields();
                    SUPER.init_slider_field();
                    SUPER.init_tooltips();
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
                translating: $('.super-create-form').hasClass('super-translation-mode'),
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
            SUPER.save_form($this);
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
                    SUPER.save_form($('.super-actions .super-save'), 1, undefined, undefined, function () {
                        $('.super-tabs-content').css('display', 'none');
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
                    SUPER.save_form($('.super-actions .super-save'), 1, undefined, undefined, function () {
                        $('.super-tabs-content').css('display', 'none');
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
                    SUPER.save_form($('.super-actions .super-save'), 1, undefined, undefined, function () {
                        $('.super-tabs-content').css('display', 'none');
                    });
                    return false; // Do not execute responsiveness yet, must first save form then reload it then apply responsiveness
                }
                SUPER.init_super_responsive_form_fields();
                return false;
            }
            if (!$this.hasClass('super-active')) {
                $this.html('Loading...');
                SUPER.save_form($('.super-actions .super-save'), 1, undefined, undefined, function () {
                    $('.super-tabs-content').css('display', 'none');
                });
            } else {
                $('.super-tabs-content').css('display', '');
                $('.super-live-preview').css('display', 'none');
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
                            alert(data.error);
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

        SUPER.regenerate_element_inner(false);
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
                        description: '<h1>Enter the email address where the email was send from</h1>' + $tags_allowed,
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
                        selector: 'textarea[name="wizard_email_body_open"]',
                        description: '<h1>Here you can enter a short description that will be placed at the top of your admin email</h1><span class="super-tip">The email body itself can be changed under the "Form Settings" panel on the builder page, which we will be covering at a later time in this tutorial.</span><span class="super-tip">The email body itself will by default simply loop all the user input that was submitted by the user. You can of course write your custom email body if you require to do so.</span>' + $tags_allowed,
                    },
                    {
                        selector: '.super-wizard-settings .super-tabs > li:eq(2)',
                        event: 'click',
                        showNext: false,
                        description: '<h1>Click on the "Confirmation email" TAB to change how confirmation emails are send</h1><span class="super-tip">By default this email will be send to the user who submitted the form if an email address was provided</span>',
                    },
                    {
                        selector: 'input[name="wizard_confirm_to"]',
                        description: '<h1>The email address where the confirmation email should be send to.</h1><span class="super-tip">By default this is set to {email} which is a <a target="_blank" href="' + $git + 'tags-system">tag</a> that will automatically retrieve the email address that the user entered in the form.</span><span class="super-tip">You can seperate emails with comma\'s to send to multiple addresses</span>' + $tags_allowed,
                    },
                    {
                        selector: 'input[name="wizard_confirm_from"]',
                        description: '<h1>Enter the email address where the email was send from</h1>' + $tags_allowed,
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
                        selector: 'textarea[name="wizard_confirm_body_open"]',
                        description: '<h1>Here you can enter a short description that will be placed at the top of your confirmation email</h1><span class="super-tip">The email body itself can be changed under the "Form Settings" panel on the builder page, which we will be covering at a later time in this tutorial.</span><span class="super-tip">The email body itself will by default simply loop all the user input that was submitted by the user. You can of course write your custom email body if you require to do so.</span>' + $tags_allowed,
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

})(jQuery);