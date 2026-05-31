/* globals jQuery, SUPER, super_common_i18n */

(function($) { // Hide scope, no $ conflict
	"use strict";
    // @since 1.1.4
    SUPER.after_init_calculator_hook = function(){
        var $functions = super_common_i18n.dynamic_functions.after_init_calculator_hook;
        jQuery.each($functions, function(key, value){
            if(typeof SUPER[value.name] !== 'undefined') SUPER[value.name]();
        });
    };

	// Find the first occurrence of Calculator math
	SUPER.init_calculator_strstr = function( $haystack, $needle ) {
		var $pos = 0;
		$haystack += "";
		$pos = $haystack.indexOf($needle); if ($pos == -1) {
			return false;
		} else {
			return true;
		}
	};

	// @since 1.1.2
	// Init Calculator
	SUPER.init_calculator_update_data_value = function($data){
		// Get the form ID
		var $form_id = parseInt($data.hidden_form_id.value, 10),
			$form = $('.super-form-'+$form_id), // Now get the form by class
			$field,
			$name;

		// Now loop through all the calculator elements and update the $data object
		$form.find('.super-calculator').each(function(){
			$field = $(this).find('.super-shortcode-field');
			$name = $field.attr('name');
			if(typeof $data[$name] !== 'undefined'){
				$data[$name].value = $field.attr('data-value');
			}
			
		});

		return $data;
	};

	// @since 1.1.3
	// Update the math after dynamically adding a new set of field (add more +)
	// @since 1.8.5 - make sure we execute this function AFTER all other fields have been renamed otherwise fields would be skipped if the are placed below the calculator element
	SUPER.init_calculator_update_math = function(form, uniqueFieldNames, clone){
		var i,ii,wrapper,calculatorFields,column,counter,dataFields,superMath,regex,array,match,values,names,name,suffix,newField;
		if(typeof clone !== 'undefined'){
			form = clone.closest('.super-form');
			column = clone.closest('.super-column');
			counter = column.querySelectorAll(':scope > .super-duplicate-column-fields').length-1;
			calculatorFields = clone.querySelectorAll('.super-shortcode.super-calculator');
			for (ii = 0; ii < calculatorFields.length; ii++) {
				wrapper = calculatorFields[ii].querySelector('.super-calculator-wrapper');
				dataFields = wrapper.dataset.fields;
				superMath = wrapper.dataset.superMath;
				if( superMath!='' ) {
					regex = /{([^\\\/\s"'+]*?)}/g;
					array = [];
					i = 0;
					match;
					while ((match = regex.exec(superMath)) != null) {
						array[i] = match[1];
						i++;
					}
					for (i = 0; i < array.length; i++) {
						values = array[i];
						names = values.toString().split(';');
						name = names[0];
						suffix = '';
						if(typeof names[1] !== 'undefined'){
							suffix = ';'+names[1];
						}
						newField = name+'_'+(counter+1);
						if(SUPER.field_exists(form, newField)!==0){
							superMath = superMath.replace('{'+name+suffix+'}', '{'+newField+suffix+'}');

							// @since 1.4.1 - also update the data fields attribute names
							dataFields = dataFields.replace('{'+name+'}', '{'+newField+'}');
						}
					}
				}
				wrapper.dataset.superMath = superMath;
				wrapper.dataset.fields = dataFields;
			}
		}
	};
	
	// @since 1.5.0 - update the data fields attribute after duplicating a column
	SUPER.init_calculator_after_duplicating_column = function(form, uniqueFieldNames){
		var i = 0, ii, elements, calculatorFields = [];
		Object.keys(uniqueFieldNames).forEach(function(fieldName) {
			elements = form.querySelectorAll('.super-calculator-wrapper[data-fields*="{'+fieldName+'}"]');
			for (ii = 0; ii < elements.length; ++ii) {
				calculatorFields[i] = elements[ii];
				i++;
			}
		});
		SUPER.init_calculator_update_fields_attribute(form, calculatorFields);
	};

	// @since 1.5.0 - update the data fields attribute to make sure regex tags are replaced with according field names
	SUPER.init_calculator_update_fields_attribute = function(form, calculatorFields){
		var i,ii,iii,$elements,$name,$field,dataFields,newDataFields,v,oldv;
		for (i = 0; i < calculatorFields.length; ++i) {
			$field = calculatorFields[i];
			dataFields = $field.dataset.fields;
			if(!dataFields) continue; // In case Math is empty this attribute will not exists
			
			dataFields = dataFields.split('}');
			newDataFields = {};
			for (ii = 0; ii < dataFields.length; ++ii) {
				v = dataFields[ii];
				if(v!=''){
					v = v.replace('{','');
                    v = v.toString().split(';');
                    v = v[0];
					oldv = v;
					if(v.indexOf('*') >= 0){
						v = v.replace('*','');
						$elements = SUPER.field(form, v, '*');
						newDataFields[oldv] = '{'+oldv+'}';
						for (iii = 0; iii < $elements.length; ++iii) {
							$name = $elements[iii].name;
							// Skip form id
							if($name!='hidden_form_id') newDataFields[$name] = '{'+$name+'}';
						}
					}
					if(v.indexOf('^') >= 0){
						v = v.replace('^','');
						$elements = SUPER.field(form, v, '^');
						newDataFields[oldv] = '{'+oldv+'}';
						for (iii = 0; iii < $elements.length; ++iii) {
							$name = $elements[iii].name;
							if($name!='hidden_form_id') newDataFields[$name] = '{'+$name+'}';
						}
					}
					if(v.indexOf('$') >= 0){
						v = v.replace('$','');
						$elements = SUPER.field(form, v, '$');
						newDataFields[oldv] = '{'+oldv+'}';
						for (iii = 0; iii < $elements.length; ++iii) {
							$name = $elements[iii].name;
							if($name!='hidden_form_id') newDataFields[$name] = '{'+$name+'}';
						}
					}
					newDataFields[v] = '{'+v+'}';
				}
			}
			dataFields = '';
            $.each(newDataFields, function( k, v ) {
                dataFields += v;
            });
            $field.dataset.fields = dataFields;
		}
	};

	// Init Calculator
	SUPER.init_calculator = function(args){
		var form = SUPER.get_frontend_or_backend_form(args);

		var i,ii,iii,match,numericMath,values,names,name,oldName,elements,found,newMath,decimals,thousandSeparator,
			format,amount,currency,target,superMath,calculatorFields,doNotSkip,decimalSeparator,prevAmount,$jsformat,
			$timestamp,$parse,$field,$number,$numeric_amount,span,
			regex = /{([^"']*?)}/g,
			updatedCalculatorFields = {},	
			array = [],
			oldNameSuffix = '';

        if(args.skip===false) doNotSkip = true;
        if(!args.el){
            doNotSkip = true;
            calculatorFields = form.querySelectorAll('.super-calculator-wrapper');
            // @since 1.5.0 - first update the data fields attribute to make sure regex tags are replaced with according field names
            SUPER.init_calculator_update_fields_attribute(form, calculatorFields);
        }else{
            calculatorFields = form.querySelectorAll('.super-calculator-wrapper[data-fields*="{'+args.el.name+'}"]');
        }

        if(calculatorFields.length===0) return true;

        for (i = 0; i < calculatorFields.length; ++i) {
			target = calculatorFields[i];

			// @since 1.5.0 - skip if parent column or element is hidden (conditionally hidden)
			if(typeof doNotSkip === 'undefined') {
				if(SUPER.has_hidden_parent(target)){
					continue;
				}
			}

			superMath = target.dataset.superMath;
			if( superMath=='' ) continue;

			if (!SUPER.init_calculator_strstr(target.dataset.superNumericMath, '[')){
				target.dataset.superNumericMath = superMath;
			}

			// First replace any regulare expression {tags}
			array = [];
			ii = 0;
			while ((match = regex.exec(superMath)) != null) {
				array[ii] = match[1];
				ii++;
			}

			for (ii = 0; ii < array.length; ii++) {
                values = array[ii];
                names = values.toString().split(';');
                name = names[0];
				oldName = name;
				oldNameSuffix = '';
				if(typeof names[1] !== 'undefined'){
					oldNameSuffix = ';'+names[1];
				}

				// @since 1.1.8 - able to use regular expressions in {tags}
				found = false;
			
				// Use * for contains search
				// e.g: {field_*}
				// usage: $("input[id*='DiscountType']")
				if(name.indexOf('*') >= 0){
					found = true;
					name = name.replace('*','');
					elements = SUPER.field(form, name, '*');
				}

				// Use ^ for starts with search
				// e.g: {field_^}
				// usage: $("input[id^='DiscountType']")
				if(name.indexOf('^') >= 0){
					found = true;
					name = name.replace('^','');
					elements = SUPER.field(form, name, '^');
				}

				// Use $ for ends with search
				// e.g: {field_$}
				// usage: $("input[id$='DiscountType']")
				if(name.indexOf('$') >= 0){
					found = true;
					name = name.replace('$','');
					elements = SUPER.field(form, name, '$');
				}
				
				if(found===true){
					newMath = '';
					for (iii = 0; iii < elements.length; iii++) { 
						if(!elements[iii]) continue;
						if(elements[iii].name!='hidden_form_id'){
							if(iii===0){
								newMath += '{'+elements[iii].name+oldNameSuffix+'}';
							}else{
								newMath += '+{'+elements[iii].name+oldNameSuffix+'}';
							}
						}
					}
					if(newMath=='') newMath = 0;
					superMath = superMath.split('{'+oldName+oldNameSuffix+'}').join(newMath);
					target.dataset.superNumericMath = superMath;
				}
			}
			array = [];
			ii = 0;
			numericMath = '';
			while ((match = regex.exec(superMath)) != null) {
				array[ii] = match[1];
				ii++;
			}
			if(array.length===0) numericMath = superMath;
			for (ii = 0; ii < array.length; ii++) {
				numericMath = SUPER.init_calculator.do_calculation(form, target, array[ii], numericMath);
			}
			if(numericMath=='') numericMath = parseFloat(superMath);
			// Lets save the field value before playing the counter animation
			decimals = target.dataset.decimals;
			thousandSeparator = target.dataset.thousandSeparator;
			decimalSeparator = target.dataset.decimalSeparator;
			prevAmount = target.parentNode.querySelector('.super-shortcode-field').value;
			if(prevAmount=='') prevAmount = (Math.ceil(0 * 100) / 100).toFixed(decimals);
			if(typeof numericMath !== 'number'){
				numericMath = numericMath.replace(/-/g, ' -');
			}
            numericMath = numericMath.toString().split('Math.').join('');
			try {
				// eslint-disable-next-line no-undef
				amount = math.evaluate(numericMath);
			}
			catch(error) {
				alert("There is a problem with the following calculation:\n\n "+error+"\n\n"+numericMath);
				continue;
			}
			// @since 1.8.6 - return date format based on timestamps
			$jsformat = target.dataset.jsformat; //'MM/dd/yyyy';
            if( typeof $jsformat !== 'undefined' ) {
				$timestamp = amount;
				if( $timestamp!='' ) {
					$parse = new Date($timestamp);
					if($parse!=null){
						amount = $parse.toString($jsformat);
					}
				}
            }else{
				amount = amount.toFixed(decimals);
			}

			$field = target.parentNode.querySelector('.super-shortcode-field');
			// Only if value was changed
			if($field.value!==amount){
				updatedCalculatorFields[$field.name] = $field;
				$field.value = amount;
			}

			if( ((typeof prevAmount === 'string' ) && ( prevAmount == 'NaN' )) ||
				((typeof prevAmount === 'number' ) && ( prevAmount == 'Infinity' )) ) {
					continue;
			}else{
				if( ((typeof amount === 'string' ) && ( amount == 'NaN' )) ||
					((typeof amount === 'number' ) && ( amount == 'Infinity' )) ) {
						continue;
				}else{
					if( typeof $jsformat !== 'undefined' ) {
						// Just output date
						target.querySelector('.super-calculator-amount').innerText = amount;
						currency = target.querySelector('.super-calculator-currency').innerHTML;
						format = target.querySelector('.super-calculator-format').innerHTML;
						$number = target.querySelector('.super-calculator-amount').innerHTML;
						$field.dataset.value = currency+''+$number+''+format;
					}else{
						amount = parseFloat(amount).toFixed(decimals);
						$numeric_amount = amount;
						amount = (decimalSeparator ? amount.replace('.', decimalSeparator) : amount).replace(new RegExp('\\d(?=(\\d{' + (3 || 3) + '})+' + (decimals > 0 ? '\\D' : '$') + ')', 'g'), '$&' + (thousandSeparator || ''));
						if ($numeric_amount >= 0) {
							if(target.querySelector('.super-calculator-currency .super-minus-value')){
								target.querySelector('.super-calculator-currency .super-minus-value').remove();
							}
							target.querySelector('.super-calculator-amount').innerText = amount;
							currency = target.querySelector('.super-calculator-currency').innerHTML;
							format = target.querySelector('.super-calculator-format').innerHTML;
							$field.dataset.value =currency+''+amount+''+format;
						}else{
							if(!target.querySelector('.super-calculator-currency .super-minus-value')){
								span = document.createElement('span');
								span.classList.add('super-minus-value');
								span.innerHTML = '-';
								target.querySelector('.super-calculator-currency').prepend(span);
							}
							target.querySelector('.super-calculator-amount').innerText = amount.replace('-','');
							currency = target.querySelector('.super-calculator-currency').innerHTML;
							format = target.querySelector('.super-calculator-format').innerHTML;
							$field.dataset.value = currency+''+amount+''+format;
						}
					}
				}
			}
		}

        // @since 1.4.0 - update conditional logic based on the updated calculator field
        $.each(updatedCalculatorFields, function( index, field ) {
            SUPER.after_field_change_blur_hook({el: field, form: form});
        });
	
	};
	
	// Do the calculation
	SUPER.init_calculator.do_calculation = function(form, target, name, numericMath){
		var element,text_field,selected,checked,values,new_value_array,
			parent,value,new_value,sum,value_n,
			oldName = name,
            names = name.toString().split(';');

        name = names[0];
        if(typeof names[1] === 'undefined'){
            value_n = 0;
        }else{
            value_n = names[1];
        }
		element = SUPER.field(form, name);
        if(!element) {
			// Lets just replace the field name with 0 as a value
			numericMath = target.dataset.superNumericMath.replace('{'+oldName+'}', 0);
			target.dataset.superNumericMath = numericMath;
			return numericMath;
        }

		// Check if parent column or element is hidden (conditionally hidden)
		if(SUPER.has_hidden_parent(element)){
			// Exclude conditionally
			// Lets just replace the field name with 0 as a value
			numericMath = target.dataset.superNumericMath.replace('{'+oldName+'}', 0);
			target.dataset.superNumericMath = numericMath;
		}else{
			text_field = true;
			parent = element.closest('.super-field');
			// Check if dropdown field
			if( (parent.classList.contains('super-dropdown')) ||
				(parent.classList.contains('super-auto-suggest'))){
				text_field = false;
				sum = 0;
				selected = $(parent).find('.super-dropdown-list .super-item.super-active:not(.super-placeholder)');
				selected.each(function () {
                    new_value = $(this).data('value').toString().split(';');
                    if(value_n===0){
                        new_value = new_value[0];
                    }else{
                        new_value = new_value[(value_n-1)];
                    }
                    if(typeof new_value==='undefined'){
                        new_value = '';
                    }
					sum += parseFloat(new_value);
				});
				value = sum;
			}

			// Check if checkbox field
			if(parent.classList.contains('super-checkbox')){
				text_field = false;
				checked = $(parent).find('.super-field-wrapper .super-item.super-active');
				values = '';
				checked.each(function () {
					if(values==''){
						values += $(this).children('input').val();
					}else{
						values += ','+$(this).children('input').val();
					}
				});
				sum = 0;

                // @since 1.7.0 - checkbox compatibility with advanced tags like {field;2} etc.
                new_value_array = values.toString().split(',');
                $.each(new_value_array, function( k, v ) {
                    v = v.toString().split(';');
                    if(value_n==0){
                        new_value = v[0];
                    }else{
                        new_value = v[(value_n-1)];
                    }
                    if(typeof new_value==='undefined'){
                        new_value = '';
                    }
                    sum += parseFloat(new_value);
                });
                value = sum;
			}
			// @since 1.7.0 - check for radio tags because it now can contain advanced tags like {field;2} etc.
			if(parent.classList.contains('super-radio')){
				text_field = false;
				new_value = element.value.toString().split(';');
                if(value_n==0){
                    new_value = new_value[0];
                }else{
                    new_value = new_value[(value_n-1)];
                }
                if(typeof new_value==='undefined'){
                    new_value = '';
                }
                value = parseFloat(new_value);
			}

			// Check if datepicker field
			if( parent.classList.contains('super-date') ) {
				text_field = false;
				value = (element.getAttribute('data-math-diff')) ? parseFloat(element.getAttribute('data-math-diff')) : 0;
				if(value_n === 'day' || value_n === 'month' || value_n === 'year' || value_n === 'timestamp'){
					if(value_n === 'day'){
						value = (element.getAttribute('data-math-day')) ? parseFloat(element.getAttribute('data-math-day')) : 0;
					}
					if(value_n === 'month'){
						value = (element.getAttribute('data-math-month')) ? parseFloat(element.getAttribute('data-math-month')) : 0;
					}
					if(value_n === 'year'){
						value = (element.getAttribute('data-math-year')) ? parseFloat(element.getAttribute('data-math-year')) : 0;
					}
					if(value_n === 'timestamp'){
						value = (element.getAttribute('data-math-diff')) ? parseFloat(element.getAttribute('data-math-diff')) : 0;
					}
				}else{
					if(element.getAttribute('data-return_age')=='true'){
						value = (element.getAttribute('data-math-age')) ? parseFloat(element.getAttribute('data-math-age')) : 0;
					}
					// @since 1.2.0 - check if we want to return the date birth years, months or days for calculations
					if(target.getAttribute('data-date-math')=='years'){
						value = (element.getAttribute('data-math-age')) ? parseFloat(element.getAttribute('data-math-age')) : 0;
					}
					if(target.getAttribute('data-date-math')=='months'){
						value = (element.getAttribute('data-math-age-months')) ? parseFloat(element.getAttribute('data-math-age-months')) : 0;
					}
					if(target.getAttribute('data-date-math')=='days'){
						value = (element.getAttribute('data-math-age-days')) ? parseFloat(element.getAttribute('data-math-age-days')) : 0;
					}
				}
			}
			// Check if timepicker field
			if( parent.classList.contains('super-time') ) {
				text_field = false;
				value = (element.getAttribute('data-math-diff')) ? parseFloat(element.getAttribute('data-math-diff')) : 0;
			}

			// @since 1.1.7
			// Check if textarea field
			if( parent.classList.contains('super-textarea') ) {
				text_field = false;
				value = (element.getAttribute('data-word-count')) ? parseFloat(element.getAttribute('data-word-count')) : 0;
				if(value_n === 'word' || value_n === 'words' || value_n === 'char' || value_n === 'chars' || value_n === 'characters' || value_n === 'allChars' || value_n === 'allchars' || value_n === 'allcharacters' || value_n === 'allCharacters' ){
					if(value_n === 'word' || value_n === 'words'){
						// already defaults to word, unless otherwise specified e.g: {questions;chars}, {questions;allchars}
					}
					if(value_n === 'char' || value_n === 'chars' || value_n === 'characters' ){
						value = (element.getAttribute('data-chars-count')) ? parseFloat(element.getAttribute('data-chars-count')) : 0;
					}
					if(value_n === 'allchars' || value_n === 'allChars' || value_n === 'allcharacters' || value_n === 'allCharacters' ){
						value = (element.getAttribute('data-allchars-count')) ? parseFloat(element.getAttribute('data-allchars-count')) : 0;
					}
				}
			}

			// @since 1.3.2
			// Check if currency field (since Super Forms v2.1)
			if( parent.classList.contains('super-currency') ) {
				text_field = false;
				value = $(element).maskMoney('unmasked')[0];
				value = (value) ? parseFloat(value) : 0;
			}

            // @since 1.8.5 - check if variable field and check for advanced tags like {field;2} etc.
            if(parent.classList.contains('super-hidden')){
                if(parent.dataset.conditionalVariableAction=='enabled'){
					text_field = false;
					new_value = element.value.toString().split(';');
                    if(value_n==0){
                        new_value = new_value[0];
                    }else{
                        new_value = new_value[(value_n-1)];
                    }
                    if(typeof new_value==='undefined'){
                        new_value = '';
                    }
					value = parseFloat(new_value);
                }
            }

			// Check if text or textarea field
			if(text_field===true){
				value = (element.value) ? parseFloat(element.value) : 0;
			}
			if(isNaN(value)) value = 0;
			numericMath = target.dataset.superNumericMath.replace('{'+oldName+'}', value);
			target.dataset.superNumericMath = numericMath;
		}
		return numericMath;
	};

})(jQuery);
