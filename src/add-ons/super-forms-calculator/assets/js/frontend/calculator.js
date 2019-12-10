/* globals jQuery, SUPER, super_common_i18n, math */

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
		var $form_id = parseInt($data.hidden_form_id.value, 10);

		// Now get the form by class
		var $form = $('.super-form-'+$form_id);

		// Now loop through all the calculator elements and update the $data object
		$form.find('.super-calculator').each(function(){
			var $field = $(this).find('.super-shortcode-field');
			var $new_value = $field.attr('data-value');
			var $name = $field.attr('name');
			if(typeof $data[$name] !== 'undefined'){
				$data[$name].value = $new_value;
			}
			
		});

		return $data;
	};

	// @since 1.1.3
	// Update the math after dynamically adding a new set of field (add more +)
	// @since 1.8.5 - make sure we execute this function AFTER all other fields have been renamed otherwise fields would be skipped if the are placed below the calculator element
	SUPER.init_calculator_update_math = function($form, $unique_field_names, $clone){
		if(typeof $clone !== 'undefined'){
			$form = $clone.parents('.super-form:eq(0)');
	        var $column = $clone.parents('.super-column:eq(0)');
	        var $counter = $column.children('.super-duplicate-column-fields').length-1;
	        var $value_n;
	        $clone.find('.super-shortcode.super-calculator').each(function(){
	            var $element = $(this);
	        	var $data_fields = $element.find('.super-calculator-wrapper').attr('data-fields');
	        	var $math = $element.find('.super-calculator-wrapper').data('super-math');
	        	if( $math!='' ) {
				    var $regular_expression = /\{(.*?)\}/g;
				    var $array = [];
					var $i = 0;
				    var $match;
				    while (($match = $regular_expression.exec($math)) != null) {
				    	$array[$i] = $match[1];
				    	$i++;
				    }
				    for ($i = 0; $i < $array.length; $i++) {
	                    var $values = $array[$i];
	                    var $names = $values.toString().split(';');
	                    var $name = $names[0];
	                    var $suffix = '';
	                    if(typeof $names[1] === 'undefined'){
	                        $value_n = 0;
	                    }else{
	                        $value_n = $names[1];
	                        $suffix = ';'+$value_n;
	                    }
				    	var $new_field = $name+'_'+($counter+1);
				    	if(SUPER.field_exists($form, $new_field)!=0){
					    	$math = $math.replace('{'+$name+$suffix+'}', '{'+$new_field+$suffix+'}');

		                    // @since 1.4.1 - also update the data fields attribute names
		                    $data_fields = $data_fields.replace('{'+$name+'}', '{'+$new_field+'}');
					    }
				    }
				}
				$element.find('.super-calculator-wrapper').data('super-math', $math).attr('data-fields', $data_fields);
	        });
		}
	};
	
	// @since 1.5.0 - update the data fields attribute after duplicating a column
	SUPER.init_calculator_after_duplicating_column = function($form, $unique_field_names){
		var $calculator_fields = $();
		$.each($unique_field_names, function( k ) {
    		$calculator_fields = $calculator_fields.add($form.find('.super-calculator-wrapper[data-fields*="{'+k+'}"]'));
    	});
    	SUPER.init_calculator_update_fields_attribute($form, $calculator_fields);
	};

	// @since 1.5.0 - update the data fields attribute to make sure regex tags are replaced with according field names
	SUPER.init_calculator_update_fields_attribute = function($form, $calculator_fields){
		var i, ii, iii;
		var $elements;
		for (i = 0; i < $calculator_fields.length; ++i) {
			var $field = $calculator_fields[i];
			var $data_fields = $field.dataset.fields;
			$data_fields = $data_fields.split('}');
    		var $new_data_fields = {};
			for (ii = 0; ii < $data_fields.length; ++ii) {
				var v = $data_fields[ii];
				if(v!=''){
					v = v.replace('{','');
                    v = v.toString().split(';');
                    v = v[0];
					var oldv = v;
					if(v.indexOf('*') >= 0){
						v = v.replace('*','');
						$elements = SUPER.field($form, v, '*');
						$new_data_fields[oldv] = '{'+oldv+'}';
						for (iii = 0; iii < $elements.length; ++iii) {
							var $name = $elements[iii].name;
							// Skip form id
							if($name!='hidden_form_id') $new_data_fields[$name] = '{'+$name+'}';
						}
					}
					if(v.indexOf('^') >= 0){
						v = v.replace('^','');
						$elements = SUPER.field($form, v, '^');
						$new_data_fields[oldv] = '{'+oldv+'}';
						for (iii = 0; iii < $elements.length; ++iii) {
							var $name = $elements[iii].name;
							if($name!='hidden_form_id') $new_data_fields[$name] = '{'+$name+'}';
						}
					}
					if(v.indexOf('$') >= 0){
						v = v.replace('$','');
						$elements = SUPER.field($form, v, '$');
						$new_data_fields[oldv] = '{'+oldv+'}';
						for (iii = 0; iii < $elements.length; ++iii) {
							var $name = $elements[iii].name;
							if($name!='hidden_form_id') $new_data_fields[$name] = '{'+$name+'}';
						}
					}
					$new_data_fields[v] = '{'+v+'}';
				}
			}
    		$data_fields = '';
            $.each($new_data_fields, function( k, v ) {
                $data_fields += v;
            });
            $field.dataset.fields = $data_fields;
		}
	};

	// Init Calculator
	SUPER.init_calculator = function($changed_field, $form, $skip, $do_before, $do_after){
		$form = SUPER.get_frontend_or_backend_form($changed_field, $form);           

		var $calculator_fields,
        $do_not_skip,
		$regex = /\{(.*?)\}/g,
		$updated_calculator_fields = {},
        $amount,
        $currency,
        $format;

        if($skip===false) $do_not_skip = true;

        if(!$changed_field){
            $do_not_skip = true;
            $calculator_fields = $form.querySelectorAll('.super-calculator-wrapper');
            // @since 1.5.0 - first update the data fields attribute to make sure regex tags are replaced with according field names
            SUPER.init_calculator_update_fields_attribute($form, $calculator_fields);
        }else{
            $calculator_fields = $form.querySelectorAll('.super-calculator-wrapper[data-fields*="{'+$changed_field.name+'}"]');
        }

        if($calculator_fields.length==0) return true;

		if(typeof $do_before === 'undefined') $do_before = true;
		if(typeof $do_after === 'undefined') $do_after = true;

        for (var i = 0; i < $calculator_fields.length; ++i) {
			var $target = $calculator_fields[i];

		    // @since 1.5.0 - skip if parent column or element is hidden (conditionally hidden)
        	if(typeof $do_not_skip === 'undefined') {
				if(SUPER.has_hidden_parent($target)){
					return true;
				}
			}

			var $math = $target.dataset.superMath;
			if( $math=='' ) return true;

			if (!SUPER.init_calculator_strstr($target.dataset.superNumericMath, '[')){
		    	$target.dataset.superNumericMath = $math;
			}

	    	// First replace any regulare expression {tags}
		    var $array = [];
		    var $replace_regular_expression_tags = {};
		    var $value = '';
			var $i = 0;
		    var $match;
		    while (($match = $regex.exec($math)) != null) {
		    	$array[$i] = $match[1];
		    	$i++;
		    }

			for ($i = 0; $i < $array.length; $i++) {
                var $values = $array[$i];
                var $names = $values.toString().split(';');
                var $name = $names[0];
		        var $old_name = $name;
		        var $old_name_suffix = '';
		        var $elements;
		        if(typeof $names[1] !== 'undefined'){
			        $old_name_suffix = ';'+$names[1];
		        }

		        // @since 1.1.8 - able to use regular expressions in {tags}
				var $found = false;
			
				// Use * for contains search
		        // e.g: {field_*}
				// usage: $("input[id*='DiscountType']")
				if($name.indexOf('*') >= 0){
					$found = true;
					$name = $name.replace('*','');
					$elements = SUPER.field($form, $name, '*');
				}

				// Use ^ for starts with search
		        // e.g: {field_^}
				// usage: $("input[id^='DiscountType']")
				if($name.indexOf('^') >= 0){
					$found = true;
					$name = $name.replace('^','');
					$elements = SUPER.field($form, $name, '^');
				}

				// Use $ for ends with search
		        // e.g: {field_$}
				// usage: $("input[id$='DiscountType']")
				if($name.indexOf('$') >= 0){
					$found = true;
					$name = $name.replace('$','');
					$elements = SUPER.field($form, $name, '$');
				}
				
				if($found===true){
					var $new_math = '';
			        for (i = 0; i < $elements.length; i++) { 
			            if(!$elements[i]) continue;
						if($elements[i].name!='hidden_form_id'){
							if(i==0){
								$new_math += '{'+$elements[i].name+$old_name_suffix+'}';
							}else{
								$new_math += '+{'+$elements[i].name+$old_name_suffix+'}';
							}
						}
					}
					if($new_math=='') $new_math = 0;
					$math = $math.split('{'+$old_name+$old_name_suffix+'}').join($new_math);
					$target.dataset.superNumericMath = $math;
				}
		    }

		    $array = [];
		    $replace_regular_expression_tags = {};
		    $value = '';
			var $numeric_math = '';
			$i = 0;
		    while (($match = $regex.exec($math)) != null) {
		    	$array[$i] = $match[1];
		    	$i++;
		    }
		    if($array.length==0){
		    	$numeric_math = $math;
		    }
			for ($i = 0; $i < $array.length; $i++) {
				$numeric_math = SUPER.init_calculator.do_calculation($form, $target, $array[$i], $numeric_math);
		    }
		    if($numeric_math==''){
		    	$numeric_math = parseFloat($math);
		    }
		    
		    // Lets save the field value before playing the counter animation
		    var $decimals = $target.dataset.decimals;
		    var $thousand_separator = $target.dataset.thousandSeparator;
		    var $decimal_separator = $target.dataset.decimalSeparator;
		    var $prev_amount = $target.parentNode.querySelector('.super-shortcode-field').value;
		    if($prev_amount=='') $prev_amount = (Math.ceil(0 * 100) / 100).toFixed($decimals);
			if(typeof $numeric_math !== 'number'){
				$numeric_math = $numeric_math.replace(/\-/g, ' -');
			}
            
            $numeric_math = $numeric_math.toString().split('Math.').join('');
			try {
				$amount = math.evaluate($numeric_math);
			}
			catch(error) {
				alert("There is a problem with the following calculation:\n\n "+error+"\n\n"+$numeric_math);
				return false;
			}

			// @since 1.8.6 - return date format based on timestamps
            var $jsformat = $target.dataset.jsformat; //'MM/dd/yyyy';
            if( typeof $jsformat !== 'undefined' ) {
            	var $timestamp = $amount;
	            if( $timestamp!='' ) {
					var $parse = new Date($timestamp);
	                if($parse!=null){
	                	$amount = $parse.toString($jsformat);
	                }
	            }
            }else{
				$amount = $amount.toFixed($decimals);
            }

		    var $field = $target.parentNode.querySelector('.super-shortcode-field');
		    // Only if value was changed
		    if($field.value!==$amount){
			    $updated_calculator_fields[$field.name] = $field;
		    	$field.value = $amount;
			}

		    if( ((typeof $prev_amount === 'string' ) && ( $prev_amount == 'NaN' )) ||
		    	((typeof $prev_amount === 'number' ) && ( $prev_amount == 'Infinity' )) ) {
		    	return true;
			}else{
				if( ((typeof $amount === 'string' ) && ( $amount == 'NaN' )) ||
					((typeof $amount === 'number' ) && ( $amount == 'Infinity' )) ) {
			    	return true;
			    }else{
					if( typeof $jsformat !== 'undefined' ) {
						// Just output date
						$target.querySelector('.super-calculator-amount').innerText = $amount;
						$currency = $target.querySelector('.super-calculator-currency').innerHTML;
						$format = $target.querySelector('.super-calculator-format').innerHTML;
						var $number = $target.querySelector('.super-calculator-amount').innerHTML;
						$field.dataset.value = $currency+''+$number+''+$format;
					}else{
						$amount = parseFloat($amount).toFixed($decimals);
					    var $numeric_amount = $amount;
					    $amount = ($decimal_separator ? $amount.replace('.', $decimal_separator) : $amount).replace(new RegExp('\\d(?=(\\d{' + (3 || 3) + '})+' + ($decimals > 0 ? '\\D' : '$') + ')', 'g'), '$&' + ($thousand_separator || ''));
						if ($numeric_amount >= 0) {
							if($target.querySelector('.super-calculator-currency .super-minus-value')){
								$target.querySelector('.super-calculator-currency .super-minus-value').remove();
							}
							$target.querySelector('.super-calculator-amount').innerText = $amount;
							$currency = $target.querySelector('.super-calculator-currency').innerHTML;
							$format = $target.querySelector('.super-calculator-format').innerHTML;
							$field.dataset.value =$currency+''+$amount+''+$format;
						}else{
							if(!$target.querySelector('.super-calculator-currency .super-minus-value')){
								var span = document.createElement('span');
								span.classList.add('super-minus-value');
								span.innerHTML = '-';
								$target.querySelector('.super-calculator-currency').prepend(span);
							}
							$target.querySelector('.super-calculator-amount').innerText = $amount.replace('-','');
							$currency = $target.querySelector('.super-calculator-currency').innerHTML;
							$format = $target.querySelector('.super-calculator-format').innerHTML;
							$field.dataset.value = $currency+''+$amount+''+$format;
						}
					}
				}
			}
		}

        // @since 1.4.0 - update conditional logic based on the updated calculator field
        $.each($updated_calculator_fields, function( index, field ) {
        	SUPER.after_field_change_blur_hook(field, $form);
        });
	
	};
	
	// Do the calculation
	SUPER.init_calculator.do_calculation = function($form, $target, $name, $numeric_math){
        var $parent,
            $value,
            $new_value,
            $sum,
            $old_name = $name,
            $value_n,
            $names = $name.toString().split(';');

        $name = $names[0];
        if(typeof $names[1] === 'undefined'){
            $value_n = 0;
        }else{
            $value_n = $names[1];
        }
	    var $element = SUPER.field($form, $name);
        if(!$element) {
        	// Lets just replace the field name with 0 as a value
        	$numeric_math = $target.dataset.superNumericMath.replace('{'+$old_name+'}', 0);
        	$target.dataset.superNumericMath = $numeric_math;
        	return $numeric_math;
        }

	    // Check if parent column or element is hidden (conditionally hidden)
	    if(SUPER.has_hidden_parent($element)){
	        // Exclude conditionally
	        // Lets just replace the field name with 0 as a value
	        $numeric_math = $target.dataset.superNumericMath.replace('{'+$old_name+'}', 0);
	        $target.dataset.superNumericMath = $numeric_math;
	    }else{
        	var $text_field = true;
        	$parent = $element.closest('.super-field');
        	// Check if dropdown field
	        if( ($parent.classList.contains('super-dropdown')) || ($parent.classList.contains('super-countries')) ){
	            $text_field = false;
	            $sum = 0;
	            var $selected = $($parent).find('.super-dropdown-ui .super-item.super-active:not(.super-placeholder)');
	            $selected.each(function () {
                    $new_value = $(this).data('value').toString().split(';');
                    if($value_n==0){
                        $new_value = $new_value[0];
                    }else{
                        $new_value = $new_value[($value_n-1)];
                    }
                    if(typeof $new_value==='undefined'){
                        $new_value = '';
                    }
	                $sum += parseFloat($new_value);
	            });
	            $value = $sum;
	        }
        	// Check if checkbox field
        	if($parent.classList.contains('super-checkbox')){
        		$text_field = false;
        		var $checked = $($parent).find('.super-field-wrapper .super-item.super-active');
	            var $values = '';
	            $checked.each(function () {
	                if($values==''){
		                $values += $(this).children('input').val();
	                }else{
		                $values += ','+$(this).children('input').val();
	                }
	            });
	            $sum = 0;

                // @since 1.7.0 - checkbox compatibility with advanced tags like {field;2} etc.
                var $new_value_array = $values.toString().split(',');
                $.each($new_value_array, function( k, v ) {
                    v = v.toString().split(';');
                    if($value_n==0){
                        $new_value = v[0];
                    }else{
                        $new_value = v[($value_n-1)];
                    }
                    if(typeof $new_value==='undefined'){
                        $new_value = '';
                    }
                    $sum += parseFloat($new_value);
                });
                $value = $sum;
        	}
        	// @since 1.7.0 - check for radio tags because it now can contain advanced tags like {field;2} etc.
	        if($parent.classList.contains('super-radio')){
	            $text_field = false;
	            $new_value = $element.value.toString().split(';');
                if($value_n==0){
                    $new_value = $new_value[0];
                }else{
                    $new_value = $new_value[($value_n-1)];
                }
                if(typeof $new_value==='undefined'){
                    $new_value = '';
                }
                $value = parseFloat($new_value);
	        }

        	// Check if datepicker field
        	if( $parent.classList.contains('super-date') ) {
        		$text_field = false;
				$value = ($element.getAttribute('data-math-diff')) ? parseFloat($element.getAttribute('data-math-diff')) : 0;
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
        	// Check if timepicker field
        	if( $parent.classList.contains('super-time') ) {
        		$text_field = false;
				$value = ($element.getAttribute('data-math-diff')) ? parseFloat($element.getAttribute('data-math-diff')) : 0;
        	}

        	// @since 1.1.7
        	// Check if textarea field
        	if( $parent.classList.contains('super-textarea') ) {
        		$text_field = false;
        		$value = ($element.getAttribute('data-word-count')) ? parseFloat($element.getAttribute('data-word-count')) : 0;
        	}

        	// @since 1.3.2
        	// Check if currency field (since Super Forms v2.1)
        	if( $parent.classList.contains('super-currency') ) {
        		$text_field = false;
                $value = $element.value;
                var $currency = $element.dataset.currency;
                var $format = $element.dataset.format;
                var $thousand_separator = $element.dataset.thousandSeparator;
                var $decimal_seperator = $element.dataset.decimalSeparator;
				$value = $value.replace($currency, '').replace($format, '');
				$value = $value.split($thousand_separator).join('');
				$value = $value.split($decimal_seperator).join('.');
				$value = ($value) ? parseFloat($value) : 0;
        	}

            // @since 1.8.5 - check if variable field and check for advanced tags like {field;2} etc.
            if($parent.classList.contains('super-hidden')){
                if($parent.dataset.conditionalVariableAction=='enabled'){
		            $text_field = false;
		            $new_value = $element.value.toString().split(';');
                    if($value_n==0){
                        $new_value = $new_value[0];
                    }else{
                        $new_value = $new_value[($value_n-1)];
                    }
                    if(typeof $new_value==='undefined'){
                        $new_value = '';
                    }
	                $value = parseFloat($new_value);
                }
            }

	        // Check if text or textarea field
	        if($text_field===true){
				$value = ($element.value) ? parseFloat($element.value) : 0;
	        }
	        if(isNaN($value)) $value = 0;
			$numeric_math = $target.dataset.superNumericMath.replace('{'+$old_name+'}', $value);
        	$target.dataset.superNumericMath = $numeric_math;
	    }
	    return $numeric_math;
	};

})(jQuery);
