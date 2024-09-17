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
	SUPER.init_calculator_update_data_value = function($data, form0){
		// Now loop through all the calculator elements and update the $data object
		$(form0).find('.super-calculator').each(function(){
			var $field = $(this).find('.super-shortcode-field');
			var $name = $field.attr('name');
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
		var i,ii,wrapper,calculatorFields,column,counter,superMath,regex,array,match,values,names,name,suffix,newField;
		if(typeof clone !== 'undefined'){
			form = clone.closest('.super-form');
			column = clone.closest('.super-column');
			counter = column.querySelectorAll(':scope > .super-duplicate-column-fields, :scope > .super-column-custom-padding > .super-duplicate-column-fields').length-1;
			calculatorFields = clone.querySelectorAll('.super-shortcode.super-calculator');
			for (ii = 0; ii < calculatorFields.length; ii++) {
				wrapper = calculatorFields[ii].querySelector('.super-calculator-wrapper');
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
						}
					}
				}
				wrapper.dataset.superMath = superMath;
			}
		}
	};
	

	// Init Calculator
	SUPER.init_calculator = function(args){
		var form = SUPER.get_frontend_or_backend_form(args);
		if(!SUPER.calculatorFieldConnections) SUPER.calculatorFieldConnections = {};
		if(!SUPER.calculatorFieldConnections[form.id]) SUPER.calculatorFieldConnections[form.id] = {};

		var i,ii,iii,match,numericMath,values,names,name,oldName,elements,found,newMath,decimals,thousandSeparator,
			format,amount,currency,target,superMath,calculatorFields,doNotSkip,decimalSeparator,prevAmount,$jsformat,
			$timestamp,$parse,$field,$number,$numeric_amount,//span,
			regex = /{([^"']*?)}/g,
			updatedCalculatorFields = {},	
			array = [],
			oldNameSuffix = '';

        if(args.skip===false) doNotSkip = true;
        if(!args.el){
            doNotSkip = true;
            calculatorFields = form.querySelectorAll('.super-calculator-wrapper');
            // @since 1.5.0 - first update the data fields attribute to make sure regex tags are replaced with according field names
            //SUPER.init_calculator_update_fields_attribute(form, calculatorFields);
        }else{
			// We can't use oname (original field name) here because someone might use {hidden_2}+{hidden_3}+etc. inside their math, which would cause the Calculator to not update at all.
			// This could be avoided by using ({hidden}-{hidden})+({hidden_2}+{hidden_3}+etc.) as a work-around to this issue.
			// Not sure how this might affect other use-cases but we will be using args.el.name instead from now on going forward and not oname (original field name)
            // deprecated: calculatorFields = form.querySelectorAll('.super-calculator-wrapper[data-fields*="{'+args.el.dataset.oname+'}"]');
            calculatorFields = form.querySelectorAll('.super-calculator-wrapper[data-fields*="{'+args.el.name+'}"]');
			if(calculatorFields.length===0 && SUPER.calculatorFieldConnections[form.id][args.el.dataset.oname]){
				var count = Object.keys(SUPER.calculatorFieldConnections[form.id][args.el.dataset.oname]).length;
				if(count>0){
					calculatorFields = [];
					Object.keys(SUPER.calculatorFieldConnections[form.id][args.el.dataset.oname]).forEach(function (key) {
						calculatorFields.push(SUPER.calculatorFieldConnections[form.id][args.el.dataset.oname][key]);
					});
				}
			}
        }
        if(calculatorFields.length===0) {
			return true;
		}

        for (i = 0; i < calculatorFields.length; ++i) {
			target = calculatorFields[i];
			$field = target.parentNode.querySelector('.super-shortcode-field');

			// @since 1.5.0 - skip if parent column or element is hidden (conditionally hidden)
			if(typeof doNotSkip === 'undefined') {
				if(SUPER.has_hidden_parent(target, false, false)){
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
							if(!SUPER.calculatorFieldConnections[form.id][elements[iii].name]) SUPER.calculatorFieldConnections[form.id][elements[iii].name] = {};
							SUPER.calculatorFieldConnections[form.id][elements[iii].name][$field.name] = target;
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
					
					// YYYY-MM-DDTHH:mm:ss.sssZ
					$parse = new Date($timestamp); //.toUTCString();
					if($parse!=null){
						
						//amount = $parse.toString('r');
						// Methods on Date Object will convert from UTC to users timezone
						// Set minutes to current minutes (UTC) + User local time UTC offset
						$parse.setMinutes($parse.getMinutes() + $parse.getTimezoneOffset())
						// Now we can use methods on the date obj without the timezone conversion
						
						amount = $parse.toDateString();
						
						amount = $parse.toString($jsformat);
					}
					// tmp $parse = new Date($timestamp).toISOString();
					// tmp if($parse!=null){
					// tmp 	
					// tmp 	amount = $parse.toString($jsformat);
					// tmp }
					// tmp var $parse = new Date(Date.UTC( new Date($timestamp).getUTCFullYear(), new Date($timestamp).getUTCMonth(), new Date($timestamp).getUTCDate(), new Date($timestamp).getUTCHours(), new Date($timestamp).getUTCMinutes(), new Date($timestamp).getUTCSeconds()));
					// tmp if($parse!=null){
					// tmp 	
					// tmp 	amount = $parse.toString($jsformat);
					// tmp }
					//console.log(date.toUTCString());
					// tmp if($parse!=null){
					// tmp 	amount = $parse.toString($jsformat);
					// tmp }
					//
					//var parsedDate = Date.parse($timestamp);
					//var formattedDate = parsedDate.toUTCString($jsformat); //'yyyy-MM-dd HH:mm:ss');
				}
            }else{
				amount = amount.toFixed(decimals);
			}

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
						target.querySelector('.super-calculator-amount').innerText = amount;
						currency = target.querySelector('.super-calculator-currency').innerHTML;
						format = target.querySelector('.super-calculator-format').innerHTML;
						$field.dataset.value = currency+''+amount+''+format;
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
			// Try to lookup on real form instead of dynamic column
            if(form.classList.contains('super-duplicate-column-fields')){
                if(form.closest('.super-form')){
					form = form.closest('.super-form');
					element = SUPER.field(form, name);
				}
            }
        }
        if(!element) {
			// Lets just replace the field name with 0 as a value
			numericMath = target.dataset.superNumericMath.replace('{'+oldName+'}', 0);
			target.dataset.superNumericMath = numericMath;
			return numericMath;
		}

		// Check if parent column or element is hidden (conditionally hidden)
		if(SUPER.has_hidden_parent(element,false,false)){
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
			if( parent.classList.contains('super-field-type-datetime-local') || parent.classList.contains('super-date') || parent.classList.contains('super-field-type-date')){
				text_field = false;
				value = (element.getAttribute('data-math-diff')) ? parseFloat(element.getAttribute('data-math-diff')) : 0;
				if(parent.classList.contains('super-field-type-date')){
					// Text field with type=date
					if(value_n === 'day' || value_n === 'day_of_week' || value_n === 'day_name' || value_n === 'month' || value_n === 'year' || value_n === 'timestamp'){
						var d = Date.parseExact(value, ['yyyy-dd-MM']);
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
							if(value_n === 'day') value = day;
							if(value_n === 'month') value = month;
							if(value_n === 'year') value = year;
							if(value_n === 'day_of_week') value = mathDayw;
							if(value_n === 'day_name') value = mathDayn;
							if(value_n === 'day_name_short') value = mathDayns;
							if(value_n === 'day_name_shortest') value = mathDaynss;
							if(value_n === 'timestamp') value = mathDiff;
						}
					}
				}else{
					if(value_n === 'day' || value_n === 'day_of_week' || value_n === 'day_name' || value_n === 'month' || value_n === 'year' || value_n === 'timestamp'){
						if(value_n === 'day') value = (element.getAttribute('data-math-day')) ? parseFloat(element.getAttribute('data-math-day')) : 0;
						if(value_n === 'day_of_week') value = (element.getAttribute('data-math-dayw')) ? parseFloat(element.getAttribute('data-math-dayw')) : 0;
						if(value_n === 'day_name') value = (element.getAttribute('data-math-dayn')) ? element.getAttribute('data-math-dayn') : '';
						if(value_n === 'day_name_short') value = (element.getAttribute('data-math-dayns')) ? element.getAttribute('data-math-dayns') : '';
						if(value_n === 'day_name_shortest') value = (element.getAttribute('data-math-daynss')) ? element.getAttribute('data-math-daynss') : '';
						if(value_n === 'month') value = (element.getAttribute('data-math-month')) ? parseFloat(element.getAttribute('data-math-month')) : 0;
						if(value_n === 'year') value = (element.getAttribute('data-math-year')) ? parseFloat(element.getAttribute('data-math-year')) : 0;
						if(value_n === 'timestamp') value = (element.getAttribute('data-math-diff')) ? parseFloat(element.getAttribute('data-math-diff')) : 0;
					}else{
						if(element.getAttribute('data-return_age')=='true') value = (element.getAttribute('data-math-age')) ? parseFloat(element.getAttribute('data-math-age')) : 0;
						// @since 1.2.0 - check if we want to return the date birth years, months or days for calculations
						if(target.getAttribute('data-date-math')=='years') value = (element.getAttribute('data-math-age')) ? parseFloat(element.getAttribute('data-math-age')) : 0;
						if(target.getAttribute('data-date-math')=='months') value = (element.getAttribute('data-math-age-months')) ? parseFloat(element.getAttribute('data-math-age-months')) : 0;
						if(target.getAttribute('data-date-math')=='days') value = (element.getAttribute('data-math-age-days')) ? parseFloat(element.getAttribute('data-math-age-days')) : 0;
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
