/* globals jQuery, SUPER, super_elements_i18n */
"use strict";
(function($) { // Hide scope, no $ conflict

    // Init dropdowns
    SUPER.init_dropdowns = function(){
        $('.super-dropdown-ui').each(function(){
            if($(this).children('.super-placeholder').html()===''){
                var $first_item = $(this).children('li:eq(1)');
                $first_item.addClass('super-active');
                $(this).children('.super-placeholder').attr('data-value',$first_item.attr('data-value')).html($first_item.html());
            }
        });
    };

    // @since 1.3   - input mask
    SUPER.init_masked_input = function(){
        $('.super-shortcode-field[data-mask]').each(function(){
            $(this).mask($(this).data('mask'));
        });
    };

    // @since 2.1.0  - currency field
    SUPER.init_currency_input = function(){
        $('.super-currency .super-shortcode-field').each(function(){
            var $currency = $(this).data('currency');
            var $format = $(this).data('format');
            var $decimals = $(this).data('decimals');
            var $thousand_separator = $(this).data('thousand-separator');
            var $decimal_seperator = $(this).data('decimal-separator');
            $(this).maskMoney({
                prefix: $currency,
                suffix: $format,
                affixesStay: true,
                allowNegative: true,
                allowZero: true,
                thousands: $thousand_separator,
                decimal: $decimal_seperator,
                precision: $decimals
            }).maskMoney('mask');
        });
    };

    // @since 2.0 - initialize color picker(s) 
    SUPER.init_colorpicker = function(){
        $('.super-color .super-shortcode-field').each(function(){
            if(typeof $.fn.spectrum === "function") { 
                if(!$(this).hasClass('super-picker-initialized')){
                    var $value = $(this).val();
                    if($value==='') $value = '#FFFFFF';
                    $(this).spectrum({
                        containerClassName: 'super-forms',
                        replacerClassName: 'super-forms',
                        color: $value,
                        preferredFormat: "hex",
                        showInput: true,
                        chooseText: "Accept",
                        cancelText: "Cancel",
                        change: function(color) {
                            this.value = color.toHexString();
                        },
                        move: function(color) {
                            this.value = color.toHexString();
                        }
                    }).addClass('super-picker-initialized');
                }
            }
        });
    };

    // @since 2.0 - calculate age in years, months and days
    SUPER.init_datepicker_get_age = function(dateString, return_value){
        var now = new Date(),
        yearNow = now.getYear(),
        monthNow = now.getMonth(),
        dateNow = now.getDate(),
        dob = new Date(dateString.substring(6,10), dateString.substring(0,2)-1, dateString.substring(3,5)),
        yearDob = dob.getYear(),
        monthDob = dob.getMonth(),
        dateDob = dob.getDate(),
        age = {},
        yearAge = yearNow - yearDob,
        monthAge,
        dateAge;
        if(monthNow >= monthDob){
            monthAge = monthNow - monthDob;
        }else{
            yearAge--;
             monthAge = 12 + monthNow -monthDob;
        }
        if(dateNow >= dateDob){
             dateAge = dateNow - dateDob;
        }else{
            monthAge--;
             dateAge = 31 + dateNow - dateDob;
            if (monthAge < 0) {
                monthAge = 11;
                yearAge--;
            }
        }
        age = {
            years: yearAge,
            months: monthAge,
            days: dateAge
        };
        if(return_value=='years'){
            return age.years;
        }
        if(return_value=='months'){
            return age.months;
        }
        if(return_value=='days'){
            return age.days;
        }
    };

    // init connected datepickers
    SUPER.init_connected_datepicker = function($this, selectedDate, $parse_format, oneDay){
        var original_selectedDate = selectedDate,
            $format = $this.data('jsformat'),
            d,
            year,month,day,
            firstDate,
            $connected_min,
            $connected_date,
            $connected_min_days,
            min_date,
            $parse,
            $connected_max,
            $connected_max_days,
            max_date;

        if(original_selectedDate!==''){
            d = Date.parseExact(original_selectedDate, $parse_format);
            if(d!==null){
                year = d.toString('yyyy');
                month = d.toString('MM');
                day = d.toString('dd');                        
                $this.attr('data-math-year', year);
                $this.attr('data-math-month', month);
                $this.attr('data-math-day', day);
                firstDate = new Date(Date.UTC(year, month-1, day));
                $this.attr('data-math-diff', firstDate.getTime());
                $this.attr('data-math-age', SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'years'));
                $this.attr('data-math-age-months', SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'months'));
                $this.attr('data-math-age-days', SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'days'));
                $connected_min = $this.data('connected_min');
                if( typeof $connected_min !== 'undefined' ) {
                    if( $connected_min!=='' ) {
                        $connected_date = $('.super-shortcode-field.super-datepicker[name="'+$connected_min+'"]');
                        if($connected_date.length){
                            $format = $connected_date.data('jsformat');
                            $connected_min_days = $this.data('connected_min_days');
                            min_date = Date.parseExact(original_selectedDate, $parse_format).add({ days: $connected_min_days }).toString($format);
                            $connected_date.datepicker('option', 'minDate', min_date );
                            if($connected_date.val()===''){
                                $connected_date.val(min_date);
                            }
                            $parse = Date.parseExact($connected_date.val(), $parse_format);
                            if($parse!==null){
                                selectedDate = $parse.toString($format);
                                d = Date.parseExact(selectedDate, $format);
                                year = d.toString('yyyy');
                                month = d.toString('MM');
                                day = d.toString('dd');          
                                selectedDate = new Date(Date.UTC(year, month-1, day));
                                $connected_date.attr('data-math-diff', selectedDate.getTime());
                                SUPER.init_connected_datepicker($connected_date, $connected_date.val(), $parse_format, oneDay);
                            }
                        }
                    }
                }
                $connected_max = $this.data('connected_max');
                if(typeof $connected_max !== 'undefined'){
                    if( $connected_max!=='' ) {
                        $connected_date = $('.super-shortcode-field.super-datepicker[name="'+$connected_max+'"]');
                        if($connected_date.length){
                            $format = $connected_date.data('jsformat');
                            $connected_max_days = $this.data('connected_max_days');
                            max_date = Date.parseExact(original_selectedDate, $parse_format).add({ days: $connected_max_days }).toString($format);
                            $connected_date.datepicker('option', 'maxDate', max_date );
                            if($connected_date.val()===''){
                                $connected_date.val(max_date);
                            }
                            $parse = Date.parseExact($connected_date.val(), $parse_format);
                            if($parse!==null){
                                selectedDate = $parse.toString($format);
                                d = Date.parseExact(selectedDate, $format);
                                year = d.toString('yyyy');
                                month = d.toString('MM');
                                day = d.toString('dd');          
                                selectedDate = new Date(Date.UTC(year, month-1, day));
                                $connected_date.attr('data-math-diff', selectedDate.getTime());
                                SUPER.init_connected_datepicker($connected_date, $connected_date.val(), $parse_format, oneDay);
                            }
                        }
                    }
                }
            }
        }
        SUPER.after_field_change_blur_hook($this);
    };

    // init Datepicker
    SUPER.init_datepicker = function(){

        // Init datepickers
        var oneDay = 24*60*60*1000; // hours*minutes*seconds*milliseconds

        $('.super-datepicker').each(function(){
            if(typeof datepicker === "function"){ 
                $(this).datepicker( "destroy" );
            }
        });

        $('.super-datepicker:not(.super-picker-initialized)').each(function(){
            var $this = $(this),
                $format = $this.data('format'), //'MM/dd/yyyy';
                $jsformat = $this.data('jsformat'), //'MM/dd/yyyy';
                $value = $this.val(),
                $is_rtl = $this.parents('.super-form:eq(0)').hasClass('super-rtl'),
                $parse,
                year,month,day,firstDate,$date,
                $min = $this.data('minlength'),
                $max = $this.data('maxlength'),
                $work_days = $this.data('work-days'),
                $weekends = $this.data('weekends'),
                $excl_days = $this.attr('data-excl-days'),
                $range = $this.data('range'),
                $first_day = $this.data('first-day'),
                $widget,
                $connected_min_days,
                $min_date,
                $connected_max_days,
                $max_date,

                // @since 2.5.0 - Date.parseExact compatibility
                $parse_format = [
                    $jsformat
                ];

            // yy = short year
            // yyyy = long year
            // M = month (1-12)
            // MM = month (01-12)
            // MMM = month abbreviation (Jan, Feb ... Dec)
            // MMMM = long month (January, February ... December)
            // d = day (1 - 31)
            // dd = day (01 - 31)
            // ddd = day of the week in words (Monday, Tuesday ... Sunday)
            // E = short day of the week in words (Mon, Tue ... Sun)
            // D - Ordinal day (1st, 2nd, 3rd, 21st, 22nd, 23rd, 31st, 4th...)
            // h = hour in am/pm (0-12)
            // hh = hour in am/pm (00-12)
            // H = hour in day (0-23)
            // HH = hour in day (00-23)
            // mm = minute
            // ss = second
            // SSS = milliseconds
            // a = AM/PM marker
            // p = a.m./p.m. marker

            if(typeof $min !== 'undefined') $min = $min.toString();
            if(typeof $max !== 'undefined') $max = $max.toString();

            $this.addClass('super-picker-initialized');
            if( $value!=='' ) {
                $parse = Date.parseExact($value, $parse_format);
                if($parse!==null){
                    year = $parse.toString('yyyy');
                    month = $parse.toString('MM');
                    day = $parse.toString('dd');
                    $this.attr('data-math-year', year);
                    $this.attr('data-math-month', month);
                    $this.attr('data-math-day', day);
                    firstDate = new Date(Date.UTC(year, month-1, day));
                    $this.attr('data-math-diff', firstDate.getTime());
                    $this.attr('data-math-age', SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'years'));
                    $this.attr('data-math-age-months', SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'months'));
                    $this.attr('data-math-age-days', SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'days'));
                    $date = Date.parseExact(day+'-'+month+'-'+year, $parse_format);
                    if($date!==null){
                        $date = $date.toString("dd-MM-yyyy");
                        SUPER.init_connected_datepicker($this, $date, $parse_format, oneDay);
                    }
                }
            }else{
                $this.attr('data-math-year', '0');
                $this.attr('data-math-month', '0');
                $this.attr('data-math-day', '0');
                $this.attr('data-math-diff', '0');
                $this.attr('data-math-age', '0');
            }

            $this.datepicker({
                onClose: function( selectedDate ) {
                    SUPER.init_connected_datepicker($this, selectedDate, $parse_format, oneDay);
                },
                beforeShowDay: function(dt) {
                    var day = dt.getDay();
                    if(typeof $excl_days !== 'undefined'){
                        var $days = $excl_days.split(',');
                        var $found = ($days.indexOf(day.toString()) > -1);
                        if($found){
                            return [false, "super-disabled-day"];
                        }
                    }
                    if( ($weekends===true) && ($work_days===true) ) {
                        return [true, ""];
                    }else{
                        if($weekends===true){
                            return [day === 0 || day == 6, ""];
                        }
                        if($work_days===true){
                            return [day == 1 || day == 2 || day == 3 || day == 4 || day == 5, ""];
                        }
                    }
                    return [];
                },
                beforeShow: function(input, inst) {
                    $widget = $(inst).datepicker('widget');
                    $widget.addClass('super-datepicker-dialog');
                    $('.super-datepicker[data-connected_min="'+$(this).attr('name')+'"]').each(function(){
                        if($(this).val()!==''){
                            $connected_min_days = $(this).data('connected_min_days');
                            $min_date = Date.parseExact($(this).val(), $parse_format).add({ days: $connected_min_days }).toString($jsformat);
                            $this.datepicker('option', 'minDate', $min_date );
                        }
                    });
                    $('.super-datepicker[data-connected_max="'+$(this).attr('name')+'"]').each(function(){
                        if($(this).val()!==''){
                            $connected_max_days = $(this).data('connected_max_days');
                            $max_date = Date.parseExact($(this).val(), $parse_format).add({ days: $connected_max_days }).toString($jsformat);
                            $this.datepicker('option', 'maxDate', $max_date );
                        }
                    });
                },
                yearRange: $range, //'-100:+5', // specifying a hard coded year range
                changeMonth: true,
                changeYear: true,
                showAnim: '',
                showOn: $(this).parent().find('.super-icon'),
                minDate: $min,
                maxDate: $max,
                dateFormat: $format, //mm/dd/yy    -    yy-mm-dd    -    d M, y    -    d MM, y    -    DD, d MM, yy    -    &apos;day&apos; d &apos;of&apos; MM &apos;in the year&apos; yy
                monthNames: super_elements_i18n.monthNames, // set month names
                monthNamesShort: super_elements_i18n.monthNamesShort, // set short month names
                dayNames: super_elements_i18n.dayNames, // set days names
                dayNamesShort: super_elements_i18n.dayNamesShort, // set short day names
                dayNamesMin: super_elements_i18n.dayNamesMin, // set more short days names
                weekHeader: super_elements_i18n.weekHeader,
                firstDay: $first_day,
                isRTL: $is_rtl,
                showMonthAfterYear: false,
                yearSuffix: ""
            });
            $(this).parent().find('.super-icon').css('cursor','pointer');
        });
        $('.super-datepicker').parent().find('.super-icon').on('click',function(){
            $(this).parent().find('.super-datepicker').datepicker('show');
        });
        $('.super-datepicker').on('click focus',function(){
            if($('.super-timepicker').length){
                $('.super-timepicker').timepicker('hide');
            }
            $(this).datepicker('show');
        });


        function set_timepicker_dif($this){
            var $value = $this.val(),
                hours,minutes,AMPM,sHours,sMinutes,
                $h,$m,$s,today,dd,mm,yyyy,d,$timestamp;

            // @since 3.4.0 - If Ante/Post meridiem 12 hour format make sure to convert it to 24 hour format
            if( $this.data('format')=='h:i A' ) {
                if($value==='') $value = '12:00 AM';
                hours = Number($value.match(/^(\d+)/)[1]);
                minutes = Number($value.match(/:(\d+)/)[1]);
                AMPM = $value.match(/\s(.*)$/)[1];
                if(AMPM == 'PM' && hours<12) hours = hours+12;
                if(AMPM == 'AM' && hours==12) hours = hours-12;
                sHours = hours.toString();
                sMinutes = minutes.toString();
                if( hours<10 ) sHours = '0' + sHours;
                if( minutes<10 ) sMinutes = '0' + sMinutes;
                $value = sHours + ':' + sMinutes;
            }

            $value = $value.split(':');
            if(typeof $value[0] === 'undefined') $value[0] = '00';
            if(typeof $value[1] === 'undefined') $value[1] = '00';
            $h = $value[0];
            $m = $value[1].split(' ');
            $m = $m[0];
            if(typeof $value[2] === 'undefined'){
                $s = '00';
            }else{
                $s = $value[2];
            }
            today = new Date();
            dd = today.getDate();
            mm = today.getMonth();
            yyyy = today.getFullYear();
            d = new Date(Date.UTC(yyyy, mm, dd, $h, $m, $s));
            $timestamp = d.getTime();
            $this.attr('data-math-diff', $timestamp);
            SUPER.after_field_change_blur_hook($this);
        }

        // Init timepickers
        $('.super-timepicker:not(.ui-timepicker-input)').each(function(){
            var $this = $(this),
                $is_rtl = $this.parents('.super-form:eq(0)').hasClass('super-rtl'),
                $orientation = 'l',
                format = $this.data('format'),
                step = $this.data('step'),
                range = $this.data('range'),
                min = $this.data('minlength'),
                max = $this.data('maxlength'),
                duration = $this.data('duration'),
                finalrange = [],
                $form_id,
                $form_size;

            if(min==='') min = '00:00';
            if(max==='') max = '23:59';
            if((range!=='') && (typeof range !== 'undefined')){
                range = range.split('\n');
                $.each(range, function(key, value ) {
                    finalrange.push(value.split('|'));
                });
            }
            $form_id = $this.parents('.super-form:eq(0)').attr('id');
            $form_size = $this.parents('.super-form:eq(0)').data('field-size');
            if($is_rtl===true){
                $orientation = 'r';
            }
            $this.timepicker({
                className: $form_id+' super-timepicker-dialog super-field-size-'+$form_size,
                timeFormat: format,
                step: step,
                disableTimeRanges: finalrange,
                minTime: min,
                maxTime: max,
                showDuration: duration,
                orientation: $orientation
            });
            $this.parent().find('.super-icon').css('cursor','pointer');
            set_timepicker_dif($this);
        });

        $('.super-timepicker').on('changeTime', function() {
            set_timepicker_dif($(this));
        });
        $('.super-timepicker').parent().find('.super-icon').on('click',function(){
            $(this).parent().find('.super-timepicker').timepicker('show');
        });
    };

    // Initialize button colors
    SUPER.init_button_colors = function( $this ) {    
        if(typeof $this === 'undefined'){
            $('.super-button .super-button-wrap').each(function(){
                SUPER.init_button_colors( $(this) );
            });
        }else{
            $this = $this.parent();
            var $type = $this.data('type'),
                $color = $this.data('color'),
                $light = $this.data('light'),
                $dark = $this.data('dark'),
                $font = $this.data('font'),
                $font_hover = $this.data('font-hover'),
                $wrap = $this.find('.super-button-wrap'),
                $btn_name = $wrap.find('.super-button-name'),
                $btn_name_icon = $btn_name.find('i');
            if($type=='diagonal'){
                if(typeof $color !== 'undefined'){
                    $wrap.css('border-color', $color);
                }else{
                    $wrap.css('border-color', '');
                }
                if(typeof $font !== 'undefined'){
                    $btn_name.css('color', $font);
                    $btn_name_icon.css('color', $font);
                }else{
                    $btn_name.css('color', '');
                    $btn_name_icon.css('color', '');
                }
                $this.find('.super-button-wrap .super-after').css('background-color',$color);
            }
            if($type=='outline'){
                if(typeof $color !== 'undefined'){
                    $wrap.css('border-color', $color);
                }else{
                    $wrap.css('border-color', '');
                }
                $wrap.css('background-color', '');
                if(typeof $font !== 'undefined'){
                    $btn_name.css('color', $font);
                    $btn_name_icon.css('color', $font);
                }else{
                    $btn_name.css('color', '');
                    $btn_name_icon.css('color', '');
                }
            }
            if($type=='2d'){
                $wrap.css('background-color', $color);
                $wrap.css('border-color', $light);
                $btn_name.css('color', $font);
                $btn_name_icon.css('color', $font);
            }
            if($type=='3d'){
                $wrap.css('background-color', $color);
                $wrap.css('color', $dark).css('border-color', $light);
                if(typeof $font_hover !== 'undefined'){
                    $btn_name.css('color', $font);
                    $btn_name_icon.css('color', $font);
                }else{
                    if(typeof $font !== 'undefined'){
                        $btn_name.css('color', $font);
                        $btn_name_icon.css('color', $font);
                    }else{
                        $btn_name.css('color', '');
                        $btn_name_icon.css('color', '');
                    }
                }
            }
            if($type=='flat'){
                $wrap.css('background-color', $color);
                $btn_name.css('color', $font);
                $btn_name_icon.css('color', $font);
            }
        }
    };

    // Init button hover colors
    SUPER.init_button_hover_colors = function( $this ) {  
        var $type = $this.data('type'),
            $color = $this.data('color'),
            $hover_color = $this.data('hover-color'),
            $hover_light = $this.data('hover-light'),
            $hover_dark = $this.data('hover-dark'),
            $font = $this.data('font'),
            $font_hover = $this.data('font-hover'),
            $wrap = $this.find('.super-button-wrap'),
            $btn_name = $wrap.find('.super-button-name'),
            $btn_name_icon = $btn_name.find('i');
        if($type=='2d'){
            $wrap.css('background-color', $hover_color);
            $wrap.css('border-color', $hover_light);
            $btn_name.css('color', $font_hover);
            $btn_name_icon.css('color', $font_hover);
        }
        if($type=='flat'){
            $wrap.css('background-color', $hover_color);
            $btn_name.css('color', $font_hover);
            $btn_name_icon.css('color', $font_hover);
        }
        if($type=='outline'){
            if(typeof $hover_color !== 'undefined'){
                $wrap.css('background-color',$hover_color);
            }else{
                if(typeof $color !== 'undefined'){
                    $wrap.css('background-color',$color);
                }else{
                    $wrap.css('background-color','');
                }
            }
            $wrap.css('border-color',$hover_color);
            if(typeof $font_hover !== 'undefined'){
                $btn_name.css('color', $font_hover);
                $btn_name_icon.css('color', $font_hover);
            }else{
                if(typeof $font !== 'undefined'){
                    $btn_name.css('color', $font);
                    $btn_name_icon.css('color', $font);
                }else{
                    $btn_name.css('color', '');
                    $btn_name_icon.css('color', '');
                }
            }
        }
        if($type=='diagonal'){
            if(typeof $color !== 'undefined'){
                $wrap.css('border-color', $hover_color);
            }else{
                $wrap.css('border-color', '');
            }
            if(typeof $font !== 'undefined'){
                $btn_name.css('color', $font_hover);
                $btn_name_icon.css('color', $font_hover);
            }else{
                $btn_name.css('color', '');
                $btn_name_icon.css('color', '');
            }
            $wrap.find('.super-after').css('background-color',$hover_color);
            return false;
        }
        if($type=='2d'){
            return false;
        }
        if(typeof $hover_color !== 'undefined'){
            $wrap.css('background-color',$hover_color);
            if($type=='3d'){
                $wrap.css('color',$hover_dark).css('border-color',$hover_light);
                if(typeof $font_hover !== 'undefined'){
                    $btn_name.css('color', $font_hover);
                    $btn_name_icon.css('color', $font_hover);
                }else{
                    if(typeof $font !== 'undefined'){
                        $btn_name.css('color', $font);
                        $btn_name_icon.css('color', $font);
                    }else{
                        $btn_name.css('color', '');
                        $btn_name_icon.css('color', '');
                    }
                }
            }
        }
    };

    // Function to unset focus on fields
    SUPER.unsetFocus = function(){
        $('.super-field.super-focus').removeClass('super-focus').blur();
    };

    // Retrieve amount of decimals based on a numberic value
    SUPER.get_decimal_places = function($number){
        var $match = (''+$number).match(/(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/);
        if (!$match) { return 0; }
        return Math.max(0, ($match[1] ? $match[1].length : 0) - ($match[2] ? +$match[2] : 0));
    };

    jQuery(document).ready(function ($) {
        
        SUPER.init_dropdowns();
        SUPER.init_datepicker();
        SUPER.init_masked_input();
        SUPER.init_currency_input();
        SUPER.init_colorpicker();

        var $doc = $(document);    

        // @since 3.7.0 - autosuggest keyword with wordpress tags
        // @since 4.4.0 - autosuggest keyword speed improvements
        var autosuggest_tags_timeout = null;

        // @since 4.8.0 - TABS switching
        $doc.on('click', '.super-shortcode .super-tabs-tab', function(e){
            // Make sure we stop any other events from being triggered
            e.preventDefault();
            var $this = $(this),
                $index = $this.index(),
                $tab_menu = $this.parent('.super-tabs-menu'),
                $tab_content = $tab_menu.parent().children('.super-tabs-contents');
            $tab_menu.children('.super-tabs-tab').removeClass('super-active');
            $this.addClass('super-active');
            $tab_content.children('.super-tabs-content').removeClass('super-active');
            $tab_content.children('.super-tabs-content:eq('+$index+')').addClass('super-active');
        });
        // @since 4.8.0 - Accordion toggles
        $doc.on('click', '.super-accordion-item .super-accordion-header', function(){
            var $this = $(this).parent(),
                $parent = $this.parent();
            if($this.hasClass('super-active')){
                // Close all accordion
                $parent.children('.super-accordion-item').removeClass('super-active');
            }else{
                // Close other accordions, then open this one
                $parent.children('.super-accordion-item').removeClass('super-active');
                // Open current one
                $this.addClass('super-active');
            }
        });

        // Empty any string when unfocussing the input/search/filter field
        $doc.on('blur', '.super-keyword-tags .super-shortcode-field', function(){
            $(this).val('');
        });

        // On keyup filter any keyword tags from the list
        $doc.on('keyup', '.super-keyword-tags .super-shortcode-field', function(){
            var el = $(this)[0];
            var time = 250;
            if (autosuggest_tags_timeout !== null) {
                clearTimeout(autosuggest_tags_timeout);
            }
            autosuggest_tags_timeout = setTimeout(function () {
                var value = el.value.toString();
                var parent = el.closest('.super-field');
                if( value==='' ) {
                    parent.classList.remove('super-string-found');
                }else{
                    var items_to_show = [];
                    var items_to_hide = [];
                    var wrapper = el.closest('.super-field-wrapper');
                    var items = wrapper.querySelectorAll('.super-dropdown-ui > li');
                    var found = false;
                    [].forEach.call(items, function(el) {
                        var string_value = el.dataset.searchValue.toString();
                        if(string_value.toLowerCase().indexOf(value.toLowerCase())!=-1){
                            items_to_show.push(el);
                            found = true;
                            var $regex = RegExp([value].join('|'), 'gi');
                            el.innerHTML = '<span class="super-wp-tag">'+el.innerText.replace($regex, '<span>$&</span>')+'</span>';
                        }else{
                            items_to_hide.push(el);
                            el.innerHTML = '<span class="super-wp-tag">'+string_value+'</span>';
                        }
                    });
                    [].forEach.call(items_to_show, function(el) {
                        el.style.display = 'inline-block';
                        el.classList.add('super-active');
                    });
                    [].forEach.call(items_to_hide, function(el) {
                        el.style.display = 'none';
                        el.classList.remove('super-active');
                    });

                    parent.classList.add('super-focus');
                    parent.classList.add('super-string-found');
                    if( found===true ) {
                        parent.classList.remove('super-no-match');
                    }else{
                        parent.classList.add('super-no-match');
                    }
                }
            }, time);
        });

        $doc.on('click', '.super-autosuggest-tags', function(){
            $(this).parents('.super-field:eq(0)').find('.super-shortcode-field').focus();
        });

        // @since 3.7.0 - add autosuggest keyword wordpress tag to field value/items
        $doc.on('click', '.super-keyword-tags .super-dropdown-ui li', function(){
            var $this = $(this);
            if($this.data('value')==='') return true;

            var $parent = $this.parent(),
                $field = $this.parents('.super-field:eq(0)'),
                $shortcode_field = $field.find('input.super-keyword:eq(0)'),
                $autosuggest = $field.find('.super-shortcode-field'),
                $tag_value = $this.data('value'),
                $tag_name = $this.data('search-value'),
                $tags = $shortcode_field.val().split(','),
                $found_tag = ($tags.indexOf($tag_value) > -1),
                $value,
                $counter = 0;

            if(!$found_tag){
                $('<span class="super-noselect" data-value="'+$tag_value+'" title="remove this tag">'+$tag_name+'</span>').appendTo($field.find('.super-autosuggest-tags > div'));
                $autosuggest.val('').focus();
                $value = '';
                $counter = 0;
                $field.find('.super-autosuggest-tags > div > span').each(function () {
                    if ($counter === 0) $value = $(this).text();
                    if ($counter !== 0) $value = $value + ',' + $(this).text();
                    $counter++;
                });
                $shortcode_field.val($value);

                // Clear placeholder
                if($value!==''){
                    $autosuggest.attr('placeholder','');
                }
                
                // Check if limit is reached after adding this, if so hide the filter field
                var $max_tags = $shortcode_field.data('keyword-max');
                if($counter>=$max_tags){
                    $autosuggest.hide();
                }
                SUPER.set_keyword_tags_width($field, $counter, $max_tags);

                $parent.find('li').removeClass('super-active');
                $(this).addClass('super-active');
                $field.removeClass('super-focus').removeClass('super-string-found');

                SUPER.after_field_change_blur_hook($shortcode_field);
            }
        });
        // @since 3.7.0 - delete autosuggest keyword wordpress tag
        $doc.on('click', '.super-autosuggest-tags > div > span', function(){
            var $this = $(this);
            var $field = $this.parents('.super-field:eq(0)');
            var $shortcode_field = $field.find('input.super-shortcode-field:eq(0)');
            var $autosuggest = $field.find('.super-shortcode-field');
            // Always display the filter field again
            $autosuggest.show();
            $this.remove();
            SUPER.set_keyword_tags_width($field);
            $autosuggest.val('').focus();
            var $value = '';
            var $counter = 0;
            $field.find('.super-autosuggest-tags > div > span').each(function () {
                if ($counter === 0) $value = $(this).text();
                if ($counter !== 0) $value = $value + ',' + $(this).text();
                $counter++;
            });
            $shortcode_field.val($value);
            // Add back the placeholder
            if($value===''){
                $autosuggest.attr('placeholder',$autosuggest.attr('data-placeholder'));
            }

            SUPER.after_field_change_blur_hook($shortcode_field);
        });
        // @since 3.7.0 - close tags list when clicked outside element
        $(window).click(function() {
            $('.super-form .super-keyword-tags.super-string-found.super-focus').removeClass('super-string-found');
        });

        // @since 3.1.0 - auto transform to uppercase
        $doc.on('input', '.super-form .super-uppercase .super-shortcode-field', function() {
            $(this).val(function(_, val) {
                return val.toUpperCase();
            });
        });

        // @since 1.7 - count words on textarea field (usefull for translation estimates)
        var word_count_timeout = null;
        $doc.on('keyup blur', 'textarea.super-shortcode-field', function(e) {
            var $this = $(this),
                $time = 250,
                $text,
                $words;

            if(e.type!='keyup') $time = 0;
            if (word_count_timeout !== null) {
                clearTimeout(word_count_timeout);
            }
            word_count_timeout = setTimeout(function () {
                $text = $this.val();
                $words = $text.match(/\S+/g);
                $words = $words ? $words.length : 0;
                $this.attr('data-word-count', $words);
                SUPER.after_field_change_blur_hook($this);
            }, $time);
        });

        // @since 1.2.4
        $doc.on('click', '.super-quantity .super-minus-button, .super-quantity .super-plus-button', function(){
            var $this = $(this),
                $input_field = $this.parent().find('.super-shortcode-field'),
                $min = parseFloat($input_field.data('minnumber')),
                $max = parseFloat($input_field.data('maxnumber')),
                $field_value = $input_field.val(),
                $new_value,
                $steps = parseFloat($input_field.data('steps')),
                $decimals = SUPER.get_decimal_places($steps);

            if($field_value==='') $field_value = 0;
            $field_value = parseFloat($field_value);
            if($this.hasClass('super-plus-button')){
                $new_value = $field_value + $steps;
                $new_value = parseFloat($new_value.toFixed($decimals));
                if($new_value > $max) return false;
            }else{
                $new_value = $field_value - $steps;
                $new_value = parseFloat($new_value.toFixed($decimals));
                if($new_value < $min) return false;
            }
            $input_field.val($new_value);
            SUPER.after_field_change_blur_hook($input_field);
        });


        // @since 2.9.0 - toggle button
        $doc.on('click', '.super-toggle-switch', function(){
            var $this = $(this),
                $input_field = $this.parent().find('.super-shortcode-field'),
                $new_value = $this.find('.super-toggle-on').data('value');
            if( $this.hasClass('super-active')) {
                $new_value = $this.find('.super-toggle-off').data('value');
            }
            $this.toggleClass('super-active');
            $input_field.val($new_value);
            SUPER.after_field_change_blur_hook($input_field);
        });


        // @since 2.9.0 - keyword tag field
        $doc.on('click', '.super-entered-keywords > span', function(){
            var $this = $(this);
            var $parent = $this.parent();
            $this.remove();
            var $tags = '';
            var $counter = 0;
            $parent.children('span').each(function(){
                if($counter===0){
                    $tags += $(this).text();
                }else{
                    $tags += ', '+$(this).text();
                }
                $counter++;
            });
            $parent.parent().find('.super-keyword').val($tags);
        });
        $doc.on('keyup keydown', '.super-keyword',function(){
            var $this = $(this),
                $value = $this.val(),
                $split_method = $this.data('split-method'),
                $max_tags = $this.data('keyword-max'),
                $tags,
                $tags_html = '',
                $counter = 0,
                $duplicate_tags = {};

            if($split_method=='both') $tags = $value.split(/[ ,]+/);
            if($split_method=='comma') $tags = $value.split(/[,]+/);
            if($split_method=='space') $tags = $value.split(/[ ]+/);
            $.each($tags,function(index,value){
                if(typeof $duplicate_tags[value]==='undefined'){
                    $counter++;
                    if($counter<=$max_tags){
                        if($split_method!='comma') value = value.replace(/ /g,'');
                        if( (value!=='') && (value.length>1) ) {
                            $tags_html += '<span class="super-noselect">'+value+'</span>';
                        }
                    }
                }
                $duplicate_tags[value] = value;
            });
            $this.parent().find('.super-entered-keywords').html($tags_html);
        });


        // @since 2.1 - trigger field change hook after currency field value has changed
        // @since 4.2 - trigger field change hook after quantity field value has changed
        var $calculation_threshold = null;
        $doc.on('keyup blur', '.super-form .super-currency .super-shortcode-field, .super-form .super-quantity .super-shortcode-field', function(e) {
            var $this = $(this);
            var $threshold = 0;
            if( (typeof $this.attr('data-threshold') !== 'undefined') && (e.type=='keyup') ) {
                $threshold = parseFloat($this.attr('data-threshold'));
                if ($calculation_threshold !== null) {
                    clearTimeout($calculation_threshold);
                }
                $calculation_threshold = setTimeout(function () {
                    SUPER.after_field_change_blur_hook($this);
                }, $threshold);
            }else{
                SUPER.after_field_change_blur_hook($this);
            }
        });
        
        // @since 1.7 - improved dynamic columns
        $doc.on('click', '.super-form .super-duplicate-column-fields .super-add-duplicate', function(){
            var $this,
                $parent,
                $column,
                $form,
                $first,
                $found,
                $limit,
                $unique_field_names = {},
                $field_names = {},
                $field_labels = {},
                $counter = 0,
                $field,
                $clone,
                $last_tab_index,
                $added_fields = {},
                $added_fields_with_suffix = {},
                $added_fields_without_suffix = [],
                $field_counter = 0,
                $element,
                $found_html_fields,
                $html_fields,
                $data_fields,
                $conditions,
                $new_data_attr,
                $new_data_fields,
                $new_text,
                $condition,
                $replace_names,
                $new_field,
                $new_count,
                $suffix,
                $math,
                $value_n,
                $names,
                $name,
                $values,
                $i,
                $array,
                $match,
                $number,
                $regular_expression = /\{(.*?)\}/g,
                $oldv,
                $duplicate_dynamically;
            function return_replace_names($value, $new_count, $replace_names){
                var $v,
                    $field_name,
                    $regex = /{(.*?)}/g;
                while (($v = $regex.exec($value)) !== null) {
                    // This is necessary to avoid infinite loops with zero-width matches
                    if ($v.index === $regex.lastIndex) {
                        $regex.lastIndex++;
                    }
                    $field_name = $v[1].split(';')[0];
                    $new_field = $field_name+'_'+$new_count;
                    $replace_names[$field_name] = $new_field;
                }
                return $replace_names;
            }

            $this = $(this);
            $parent = $this.parents('.super-duplicate-column-fields:eq(0)');
            $column = $parent.parents('.super-column:eq(0)');
            // If custom padding is being used set $column to be the padding wrapper `div`
            if($this.parent('.super-column-custom-padding').length){
                $column = $this.parents('.super-column-custom-padding:eq(0)');
            }

            $form = $column.parents('.super-form:eq(0)');
            $first = $column.find('.super-duplicate-column-fields:eq(0)');
            $found = $column.children('.super-duplicate-column-fields').length;
            $limit = $column.data('duplicate_limit');
            if( ($limit!==0) && ($found >= $limit) ) {
                return false;
            }

            $unique_field_names = {}; // @since 2.4.0
            $field_names = {};
            $field_labels = {};
            $counter = 0;
            $first.find('.super-shortcode').each(function(){
                $field = $(this).find('.super-shortcode-field');
                if($field.hasClass('super-fileupload')){
                    $field = $field.parent('.super-field-wrapper').find('.super-active-files');
                }
                $name = $field.attr('name');
                $unique_field_names[$name] = $name;
                $field_names[$counter] = $name;
                $field_labels[$counter] = $field.data('email');
                $counter++;
            });

            $counter = $column.children('.super-duplicate-column-fields').length;
            $clone = $first.clone();
            $clone = $($clone).appendTo($column);

            // @since 3.3.0 - hook after appending new column
            SUPER.after_appending_duplicated_column_hook($form, $unique_field_names, $clone);

            // Now reset field values to default
            SUPER.init_clear_form($clone);

            // Now reinitialize the slider fields
            SUPER.init_slider_field();
        
            // Now reinitialize the star rating fields
            SUPER.rating();

            // @since 3.2.0 - increment for tab index fields when dynamic column is cloned
            if($clone.find('.super-shortcode[data-super-tab-index]').last().length){
                $last_tab_index = $clone.find('.super-shortcode[data-super-tab-index]').last().attr('data-super-tab-index');
            }else{
                $last_tab_index = '';
            }
            $last_tab_index = parseFloat($last_tab_index);

            // First rename then loop through conditional logic and update names, otherwise we might think that the field didn't exist!
            $added_fields = {};
            $added_fields_with_suffix = {};
            $added_fields_without_suffix = [];
            $field_counter = 0;
            $clone.find('.super-shortcode').each(function(){
                $element = $(this);
                if( (typeof $element.attr('data-super-tab-index') !== 'undefined') && ($last_tab_index!=='') ) {
                    $last_tab_index = parseFloat(parseFloat($last_tab_index)+0.001).toFixed(3);
                    $element.attr('data-super-tab-index', $last_tab_index);
                }

                $field = $(this).find('.super-shortcode-field');
                $added_fields[$field.attr('name')] = $field;

                if($field.length){
                    if($field.hasClass('super-fileupload')){
                        $field.removeClass('super-rendered');
                        $field = $field.parent('.super-field-wrapper').find('.super-active-files');
                    }
                    $field.attr('name', $field_names[$field_counter]+'_'+($counter+1));

                    // Replace %d with counter if found, otherwise append it
                    // Remove whitespaces from start and end
                    $field_labels[$field_counter] = $field_labels[$field_counter].trim();
                    if($field_labels[$field_counter].indexOf("%d")===-1){
                        // Not found, just return with counter appended at the end
                        $field_labels[$field_counter] = $field_labels[$field_counter] + ' ' + $counter;
                    }else{
                        // Found, return with counter replaced at correct position
                        $field_labels[$field_counter] = $field_labels[$field_counter].replace('%d', $counter+1);
                    }
                    $field.attr('data-email', $field_labels[$field_counter]);
                    $added_fields_with_suffix[$field_names[$field_counter]] = $field_names[$field_counter]+'_'+($counter+1);
                    $added_fields_without_suffix.push($field_names[$field_counter]+'_'+($counter+1));
                    if( $field.hasClass('hasDatepicker') ) {
                        $field.removeClass('hasDatepicker').attr('id', '');
                    }
                }
                $field_counter++;
            });

            // @since 4.6.0 - update html field tags attribute
            // Get all HTML elements based on field tag attribute that contain one of these field names
            // Then convert it to an array and append the missing field names
            // @IMPORTANT: Only do this for HTML elements that are NOT inside a dynamic column
            $found_html_fields = [];
            $.each($added_fields_with_suffix, function( index ) {
                $html_fields = $form.find('.super-html-content[data-fields*="{'+index+'}"]');
                $html_fields.each(function(){
                    $this = $(this);
                    if(!$this.parents('.super-duplicate-column-fields:eq(0)').length){
                        $found = false;
                        $.each($found_html_fields, function( index, el ){
                            if(el.is($this)){
                                $found = true;
                            }
                        });
                        if(!$found){
                            $found_html_fields.push($this);
                        }
                    }
                });
            });
            $.each($found_html_fields, function( $index, $field ) {
                $field.attr('data-fields', $field.attr('data-fields')+'{' + $added_fields_without_suffix.join('}{') + '}');
            });

            // Now we have updated the names accordingly, we can proceed updating conditional logic and variable fields etc.
            $clone.find('.super-shortcode').each(function(){
                $element = $(this);
                $duplicate_dynamically = $column.attr('data-duplicate_dynamically');
                if($duplicate_dynamically=='true') {
                    // @since 3.1.0 - update html tags after adding dynamic column
                    if($element.hasClass('super-html')){
                        $new_count = $counter+1;
                        $data_fields = $element.children('.super-html-content').attr('data-fields');
                        $data_fields = $data_fields.split('}');
                        $new_data_fields = {};
                        $.each($data_fields, function( $k, $v ) {
                            if($v!==''){
                                $v = $v.replace('{','');
                                $oldv = $v;
                                $v = $v.toString().split(';');
                                $name = $v[0];
                                // First check if the field even exists, if not just skip
                                var $found_field = $form.find('.super-shortcode-field[name="'+$name+'"]');
                                if(typeof $found_field === 'undefined'){
                                    // Do nothing
                                    return;
                                }
                                // Next step is to check if this field is outside the dynamic column
                                // If this is the case we will not change it (we do not want to increase the field name from for instance `name` to `name_2`)
                                if($found_field.parents('.super-duplicate-column-fields:eq(0)').length===0){
                                    // Field exists, but is not inside dynamic column, so skip name change but
                                    // it is still a valid tag so it must be replaced with the field value
                                    // that's why we add it to the `$new_data_fields` array so it will be updated accordingly
                                    $new_data_fields[$oldv] = $name;
                                    return;
                                }
                                
                                // Check if this is a {dynamic_column_counter} tag
                                // If so we can skip it
                                if($name=='dynamic_column_counter'){
                                    return;
                                }

                                // If this is a advanced tag make sure to correctly append the number
                                $number = $v[1];
                                if(typeof $number==='undefined'){
                                    $number = '';
                                }else{
                                    $number = ';'+$number;
                                }

                                // Finally add the updated tag to the `$new_data_fields` array
                                $new_data_fields[$oldv] = $name+'_'+$new_count+$number;
                            }
                        });

                        // Loop through all tags that require to be updated, and add it as a string to combine all of them
                        $new_data_attr = '';
                        $.each($new_data_fields, function( k, v ) {
                            $new_data_attr += '{'+v+'}';
                        });
                        $element.children('.super-html-content').attr('data-fields',$new_data_attr);
                        $new_text = $element.children('textarea').val();
                        $.each($new_data_fields, function( k, v ) {
                            $new_text = $new_text.split('{'+k+';').join('{'+v+';');
                            $new_text = $new_text.split('{'+k+'}').join('{'+v+'}');
                        });
                        $element.children('textarea:first').val($new_text);
                    }

                    // Update conditional logic names
                    // Update variable condition names
                    $element.children('.super-conditional-logic, .super-variable-conditions').each(function(){
                        $condition = $(this);
                        $new_count = $counter+1;
                        // Make sure to grab the value of the element and not the HTML due to html being escaped otherwise
                        $conditions = jQuery.parseJSON($condition[0].value);
                        if(typeof $conditions !== 'undefined'){
                            $replace_names = {};
                            $.each($conditions, function( index, v ) {
                                if(v.field!=='' && v.field.indexOf('{')===-1) v.field = '{'+v.field+'}';
                                if(v.field_and!=='' && v.field_and.indexOf('{')===-1) v.field_and = '{'+v.field_and+'}';
                                $replace_names = return_replace_names(v.field, $new_count, $replace_names);
                                $replace_names = return_replace_names(v.value, $new_count, $replace_names);
                                $replace_names = return_replace_names(v.field_and, $new_count, $replace_names);
                                $replace_names = return_replace_names(v.value_and, $new_count, $replace_names);
                                if($condition.hasClass('super-variable-conditions')){
                                    $replace_names = return_replace_names(v.new_value, $new_count, $replace_names);
                                }
                            });
                            $.each($conditions, function( condition_index, condition_v ) {
                                $.each(condition_v, function( index ) {
                                    $math = $conditions[condition_index][index];
                                    if( $math!=='' ) {
                                        $array = [];
                                        $i = 0;
                                        while (($match = $regular_expression.exec($math)) !== null) {
                                            $array[$i] = $match[1];
                                            $i++;
                                        }
                                        for ($i = 0; $i < $array.length; $i++) {
                                            $values = $array[$i];
                                            $names = $values.toString().split(';');
                                            $name = $names[0];
                                            $suffix = '';
                                            if(typeof $names[1] === 'undefined'){
                                                $value_n = 0;
                                            }else{
                                                $value_n = $names[1];
                                                $suffix = ';'+$value_n;
                                            }

                                            $new_field = $name+'_'+$new_count;
                                            if($form.find('.super-shortcode-field[name="'+$new_field+'"]').length!==0){
                                                $math = $math.replace('{'+$name+$suffix+'}', '{'+$new_field+$suffix+'}');
                                            }
                                        }
                                    }
                                    $conditions[condition_index][index] = $math;
                                });
                            });
                            $data_fields = $condition.attr('data-fields');
                            $.each($replace_names, function( index, v ) {
                                // Only if field exists
                                if($form.find('.super-shortcode-field[name="'+v+'"]').length!==0){
                                    // @since 2.4.0 - also update the data fields and tags attribute names
                                    $data_fields = $data_fields.split('{'+index+';').join('{'+v+';');
                                    $data_fields = $data_fields.split('{'+index+'}').join('{'+v+'}');
                                }else{
                                    // The field does not exist
                                    $added_fields[index] = $form.find('.super-shortcode-field[name="'+index+'"]');
                                }
                            });

                            $condition.attr('data-fields', $data_fields).val(JSON.stringify($conditions));
                        }
                    });

                    SUPER.after_duplicate_column_fields_hook($this, $element, $counter, $column, $field_names, $field_labels);
                }
            });

            SUPER.init_datepicker();
            SUPER.init_masked_input();
            SUPER.init_currency_input();
            SUPER.init_colorpicker();
            SUPER.init_fileupload_fields();

            // @since 3.2.0 - init google autocomplete fields
            SUPER.google_maps_init();

            // ############ !!!! IMPORTANT !!!! ############
            // DO NOT TURN THE BELOW 2 HOOKS AROUND OR IT
            // WILL BRAKE THE CALCULATOR ELEMENT
            // ############ !!!! IMPORTANT !!!! ############

            // @since 2.4.0 - hook after adding new column
            SUPER.after_duplicating_column_hook($form, $unique_field_names, $clone);            

            // Now loop through all elements that have data-fields inside the cloned object
            // Loop through all the added fields and get all the according data-fields
            // These need to be added and triggered to make sure conditions and calculations can be executed properly
            var $all_data_fields = $clone.find('[data-fields]');
            $.each($all_data_fields, function(index, field){
                var $data_fields = field.dataset.fields;
                $data_fields = $data_fields.split('}{');
                // Loop through all of the data fields and remove { and }, and also split ; in case of advanced tags
                $.each($data_fields, function(index, value){
                    value = value.replace('{', '').replace('}', '');
                    value = value.split(';')[0]; // If advanced tags is used grab the field name
                    // Now add the field to the array but only if the field exists
                    var $found_field = $form.find('.super-shortcode-field[name="'+value+'"]');
                    if(typeof $found_field !== 'undefined'){
                        $added_fields[value] = $form.find('.super-shortcode-field[name="'+value+'"]');
                    }
                });
            });

            // @since 2.4.0 - update conditional logic and other variable fields based on the newly added fields
            $.each($added_fields, function( index, field ) {
                SUPER.after_field_change_blur_hook(field, $form);
            });

        });

        // Delete dynamic column
        $doc.on('click', '.super-duplicate-column-fields .super-delete-duplicate', function(){
            var $form = $(this).parents('.super-form:eq(0)');
            var $removed_fields = {};
            var $parent = $(this).parents('.super-duplicate-column-fields:eq(0)');
            $parent.find('.super-shortcode-field').each(function(){
                $removed_fields[$(this).attr('name')] = $(this);
            });
            $parent.remove();

            // @since 4.6.0 - update html field tags attribute
            // Get all HTML elements based on field tag attribute that contain one of these field names
            // Then convert it to an array and append the missing field names
            // Only do this for HTML elements that are NOT inside a dynamic column
            var $found_html_fields = [];
            $.each($removed_fields, function( index ) {
                var $html_fields = $form.find('.super-html-content[data-fields*="{'+index+'}"]');
                $html_fields.each(function(){
                    var $this = $(this);
                    if(!$this.parents('.super-duplicate-column-fields:eq(0)').length){
                        var $found = false;
                        $.each($found_html_fields, function( index, el ){
                            if(el.is($this)){
                                $found = true;
                            }
                        });
                        if(!$found){
                            $found_html_fields.push($this);
                        }
                    }
                });
            });
            $.each($found_html_fields, function( $index, $field ) {
                var $data_fields = $field.attr('data-fields');
                $.each($removed_fields, function( index ) {
                    $data_fields = $data_fields.replace('{'+index+'}','');
                });
                $field.attr('data-fields', $data_fields);
            });

            // ############ !!!! IMPORTANT !!!! ############
            // DO NOT TURN THE BELOW 2 HOOKS AROUND OR IT
            // WILL BRAKE THE CALCULATOR ELEMENT
            // ############ !!!! IMPORTANT !!!! ############

            // @since 2.4.0 - hook after deleting column
            SUPER.after_duplicating_column_hook($form, $removed_fields);

            // @since 2.4.0 - update conditional logic and other variable fields based on the newly added fields
            $.each($removed_fields, function( index, field ) {
                SUPER.after_field_change_blur_hook(field, $form);
            });

            // @since 4.6.0 - we must trigger this because the fields are already removed and there is no way of looking them up now
            // ############ !!!! IMPORTANT !!!! ############
            // DO NOT DELETE THE FOLLOWING FUNCTION
            // ############ !!!! IMPORTANT !!!! ############
            SUPER.init_replace_html_tags();
            
        });

        // Close messages
        $doc.on('click', '.super-msg .close', function(){
            $(this).parents('.super-msg:eq(0)').fadeOut(500);
        });

        $doc.on('click', '.super-fileupload-button', function(){
            $(this).parents('.super-field-wrapper:eq(0)').find('.super-fileupload').trigger('click');
        });
        $doc.on('click', '.super-fileupload-delete', function(){
            var $this = $(this);
            var $parent = $this.parents('.super-fileupload-files:eq(0)');
            var $wrapper = $parent.parents('.super-field-wrapper:eq(0)');
            var total = $wrapper.children('.super-fileupload').data('total-file-sizes') - $this.parent().data('file-size');
            $wrapper.children('.super-fileupload').data('total-file-sizes', total);
            $wrapper.children('input[type="hidden"]').val('');
            $this.parent('div').remove();
        });
        
        // @since 1.2.4 - autosuggest text field
        // @since 4.4.0 - autosuggest speed improvements
        var autosuggest_timeout = null;
        $doc.on('keyup', '.super-field.super-auto-suggest .super-shortcode-field', function(){
            var el = $(this)[0];
            var time = 250;
            if (autosuggest_timeout !== null) {
                clearTimeout(autosuggest_timeout);
            }
            autosuggest_timeout = setTimeout(function () {
                var value = el.value.toString();
                var parent = el.closest('.super-field');
                if( value==='' ) {
                    parent.classList.remove('super-string-found');
                }else{
                    var items_to_show = [];
                    var items_to_hide = [];
                    var wrapper = el.closest('.super-field-wrapper');
                    var items = wrapper.querySelectorAll('.super-dropdown-ui > li');
                    var found = false;
                    [].forEach.call(items, function(el) {
                        var string_value = el.dataset.searchValue.toString();
                        if(string_value.toLowerCase().indexOf(value.toLowerCase())!=-1){
                            items_to_show.push(el);
                            found = true;
                            var $regex = RegExp([value].join('|'), 'gi');
                            el.innerHTML = el.innerText.replace($regex, '<span>$&</span>');
                        }else{
                            items_to_hide.push(el);
                            el.innerHTML = string_value;
                        }
                    });
                    [].forEach.call(items_to_show, function(el) {
                        el.style.display = 'block';
                        //el.classList.add('super-active');
                    });
                    [].forEach.call(items_to_hide, function(el) {
                        el.style.display = 'none';
                        //el.classList.remove('super-active');
                    });

                    if( found===true ) {
                        parent.classList.add('super-string-found');
                        parent.classList.add('super-focus');
                    }else{
                        parent.classList.remove('super-string-found');
                    }
                }
            }, time);
        });

        // Focus dropdowns
        $doc.on('click', '.super-dropdown-ui:not(.super-autosuggest-tags-list), .super-dropdown-arrow', function(){
            var $this = $(this);
            if(!$this.parents('.super-field:eq(0)').hasClass('super-focus-dropdown')){
                $('.super-focus').removeClass('super-focus');
                $('.super-focus-dropdown').removeClass('super-focus-dropdown');
                $this.parents('.super-field:eq(0)').addClass('super-focus').addClass('super-focus-dropdown');
                $this.parent().find('input[name="super-dropdown-search"]').focus();
            }
        });

        // @since 1.2.8     - filter dropdown options based on keyboard press
        var timeout = null;
        $doc.on('keyup', 'input[name="super-dropdown-search"]', function(e){
            
            var keyCode = e.keyCode || e.which; 
            if( (keyCode == 13) || (keyCode == 40) || (keyCode == 38) ) {
                return false;
            }

            var $this = $(this);
            if (timeout !== null) {
                clearTimeout(timeout);
            }
            timeout = setTimeout(function () {
                $this.val('');
            }, 1000);

            var $value = $(this).val().toString();
            var $field = $(this).parents('.super-field:eq(0)');
            var $wrapper = $(this).parents('.super-field-wrapper:eq(0)');
            if( $value==='' ) {
                $field.removeClass('super-string-found');
            }else{
                var $items = $wrapper.find('.super-dropdown-ui > li:not(.super-placeholder)');
                var $found = false;
                var $first_found = null;
                $items.each(function() {
                    var $string_value = $(this).data('search-value').toString();
                    var $string_value_l = $string_value.toLowerCase();
                    var $isMatch = $string_value_l.indexOf($value.toLowerCase()) !== -1;
                    if( $isMatch===true ) {
                        if( $first_found===null ) {
                            $first_found = $(this);
                        }
                        $found = true;
                        var $words = [$value]; 
                        var $regex = RegExp($words.join('|'), 'gi');
                        var $replacement = '<span>$&</span>';
                        var $string_bold = $(this).text().replace($regex, $replacement);
                        $(this).html($string_bold);
                        $(this).addClass('super-active');
                    }else{
                        $(this).html($string_value);
                        $(this).removeClass('super-active');
                    }
                });
                if( $found===true ) {
                    $field.find('.super-dropdown-ui li.super-active').removeClass('super-active');
                    $first_found.addClass('super-active');
                    $field.addClass('super-string-found').addClass('super-focus');
                    var $dropdown_ui = $field.find('.super-dropdown-ui');
                    $dropdown_ui.scrollTop($dropdown_ui.scrollTop() - $dropdown_ui.offset().top + $first_found.offset().top - 50); 
                }else{
                    $field.removeClass('super-string-found');
                }
            }

        });

        $doc.on('mouseleave', '.super-dropdown-ui:not(.super-autosuggest-tags-list)', function(){
            $(this).parents('.super-field:eq(0)').removeClass('super-focus-dropdown').removeClass('super-string-found'); 
            $(this).parents('.super-field:eq(0)').find('input[name="super-dropdown-search"]').val('');
        });

        // Focus fields
        $doc.on('focus','.super-text .super-shortcode-field, .super-quantity .super-shortcode-field, .super-password .super-shortcode-field, .super-textarea .super-shortcode-field, .super-dropdown .super-shortcode-field, .super-countries .super-shortcode-field, .super-date .super-shortcode-field, .super-time .super-shortcode-field',function(){
            SUPER.unsetFocus();
            if( !$(this).hasClass('super-datepicker') && !$(this).hasClass('super-timepicker') ){
                if($('.super-datepicker').length) {
                    $('.super-datepicker').datepicker('hide');
                }
                if($('.super-timepicker').length) {
                    $('.super-timepicker').timepicker('hide');
                }
            }else{
                if(!$(this).hasClass('super-focus')){
                    if($(this).parents('.super-form:eq(0)').hasClass('super-window-first-responsiveness') || $(this).parents('.super-form:eq(0)').hasClass('super-window-second-responsiveness') ){
                        $('html, body').animate({
                            scrollTop: $(this).offset().top-20
                        }, 0);
                    }
                }
            }
            $(this).parents('.super-field:eq(0)').addClass('super-focus'); 
        });
        $doc.on('blur','.super-text .super-shortcode-field, .super-quantity .super-shortcode-field, .super-password .super-shortcode-field, .super-textarea .super-shortcode-field, .super-dropdown .super-shortcode-field, .super-countries .super-shortcode-field, .super-date .super-shortcode-field, .super-time .super-shortcode-field',function(){
            if( (!$(this).parents('.super-field:eq(0)').hasClass('super-wc-order-search')) && 
                (!$(this).parents('.super-field:eq(0)').hasClass('super-auto-suggest')) && 
                (!$(this).parents('.super-field:eq(0)').hasClass('super-keyword-tags')) ) {
                SUPER.unsetFocus();
            }
        });
    
        // On choosing item, populate form with data
        $doc.on('click', '.super-wc-order-search .super-field-wrapper:not(.super-overlap) .super-dropdown-ui li, .super-auto-suggest .super-field-wrapper:not(.super-overlap) .super-dropdown-ui li', function(){
            var $this = $(this);
            var $wrapper = $this.parents('.super-field-wrapper:eq(0)');
            var $parent = $this.parent();
            var $field = $this.parents('.super-field:eq(0)');
            var $value = $this.text();
            var $populate = $field.find('.super-shortcode-field').data('wcosp');
            $parent.find('li').removeClass('super-active');
            $(this).addClass('super-active');
            $field.find('.super-shortcode-field').val($value);
            $field.removeClass('super-focus').removeClass('super-string-found');
            $wrapper.addClass('super-overlap');
            SUPER.after_field_change_blur_hook($field.find('.super-shortcode-field'));
            if($populate===true){
                SUPER.populate_form_data_ajax($field);
            }
        });
        // On removing item
        $doc.on('click', '.super-wc-order-search .super-field-wrapper.super-overlap li, .super-auto-suggest .super-field-wrapper.super-overlap li', function(){
            var $this = $(this);
            var $wrapper = $this.parents('.super-field-wrapper:eq(0)');
            $wrapper.find('.super-shortcode-field').val('');
            $wrapper.find('.super-active').removeClass('super-active');
            $wrapper.removeClass('super-overlap');
            SUPER.after_field_change_blur_hook($wrapper.find('.super-shortcode-field'));
        });

        // Update autosuggest
        $doc.on('click', '.super-auto-suggest .super-field-wrapper:not(.super-overlap) .super-dropdown-ui li', function(){
            var $this = $(this);
            var $field = $this.parents('.super-field:eq(0)');
            var $parent = $this.parent();
            var $value = $this.text();
            $parent.find('li').removeClass('super-active');
            $(this).addClass('super-active');
            $field.find('.super-shortcode-field').val($value);
            $field.removeClass('super-focus').removeClass('super-string-found');
            SUPER.after_field_change_blur_hook($field.find('.super-shortcode-field'));
        });

        // Update dropdown
        $doc.on('click', '.super-dropdown-ui li:not(.super-placeholder)', function(e){
            var $input,$parent,$placeholder,$multiple,$value,$name,$validation,$duration,$max,$min,$total,$names,$values,$counter;
            e.stopPropagation();
            if($(this).parents('.super-field:eq(0)').hasClass('super-focus-dropdown')){
                $(this).parents('.super-field:eq(0)').removeClass('super-focus-dropdown');
                $input = $(this).parents('.super-field-wrapper:eq(0)').children('input');
                $parent = $(this).parents('.super-dropdown-ui:eq(0)');
                $placeholder = $parent.find('.super-placeholder:eq(0)');
                $multiple = false;
                if($parent.hasClass('multiple')) $multiple = true;
                if($multiple===false){
                    $value = $(this).attr('data-value');
                    $name = $(this).html();
                    $placeholder.html($name).attr('data-value',$value).addClass('super-active');
                    $parent.find('li').removeClass('super-active');
                    $(this).addClass('super-active');
                    $input.val($value);
                    $validation = $input.data('validation');
                    $duration = SUPER.get_duration($input.parents('.super-form'));
                    if(typeof $validation !== 'undefined' && $validation !== false){
                        SUPER.handle_validations($input, $validation, '', $duration);
                    }
                    SUPER.after_dropdown_change_hook($input);
                }else{
                    $max = $input.attr('data-maxlength');
                    $min = $input.attr('data-minlength');
                    $total = $parent.find('li.super-active:not(.super-placeholder)').length;
                    if($(this).hasClass('super-active')){
                        if($total>1){
                            if($total <= $min) return false;
                            $(this).removeClass('super-active');    
                        }
                    }else{
                        if($total >= $max) return false;
                        $(this).addClass('super-active');    
                    }
                    $names = '';
                    $values = '';
                    $total = $parent.find('li.super-active:not(.super-placeholder)').length;
                    $counter = 1;
                    $parent.find('li.super-active:not(.super-placeholder)').each(function(){
                        if(($total == $counter) || ($total==1)){
                            $names += $(this).html();
                            $values += $(this).attr('data-value');
                        }else{
                            $names += $(this).html()+',';
                            $values += $(this).attr('data-value')+',';
                        }
                        $counter++;
                    });
                    $placeholder.html($names);
                    $input.val($values);
                    $validation = $input.data('validation');
                    $duration = SUPER.get_duration($input.parents('.super-form'));
                    if(typeof $validation !== 'undefined' && $validation !== false){
                        SUPER.handle_validations($input, $validation, '', $duration);
                    }
                    SUPER.after_dropdown_change_hook($input);
                }
            }
        });

        $doc.on('click','.super-back-to-top',function(){
            $('html, body').animate({
                scrollTop: 0
            }, 1000);
        });

        $doc.on('change', '.super-shortcode-field', function (e) {
            var keyCode = e.keyCode || e.which; 
            if (keyCode != 9) { 
                var $duration = SUPER.get_duration($(this).parents('.super-form'));
                var $this = $(this);
                var $validation = $this.data('validation');
                var $conditional_validation = $this.data('conditional-validation');
                SUPER.handle_validations($this, $validation, $conditional_validation, $duration);
                SUPER.after_field_change_blur_hook($this);
            }
        });

        $doc.on('click', '.super-form .super-radio > .super-field-wrapper > label', function (e) {
            if( e.target.localName=='a' ) {
                if(e.target.target=='_blank'){
                    window.open(
                      e.target.href,
                      '_blank' // <- This is what makes it open in a new window.
                    );
                }else{
                    window.location.href = e.target.href;
                }
            }else{
                var $label = $(this);
                var $this = $label.children('input[type="radio"]');
                if($label.hasClass('super-active')) return true;
                var $parent = $label.parent('.super-field-wrapper');
                var $field = $parent.children('.super-shortcode-field');
                $parent.children('label').removeClass('super-active');
                $label.addClass('super-active');
                var $validation = $field.data('validation');
                var $duration = SUPER.get_duration($field.parents('.super-form'));
                $field.val($this.val());
                if(typeof $validation !== 'undefined' && $validation !== false){
                    SUPER.handle_validations($field, $validation, '', $duration);
                }
                SUPER.after_radio_change_hook($field);
            }
            return false;
        });

        $doc.on('click', '.super-form .super-checkbox > .super-field-wrapper > label', function (e) {
            var $checked,
                $value,
                $label,
                $checkbox,
                $parent,
                $field,
                $counter,
                $maxlength,
                $validation,
                $duration;

            if( e.target.localName=='a' ) {
                if(e.target.target=='_blank'){
                    window.open(
                      e.target.href,
                      '_blank' // <- This is what makes it open in a new window.
                    );
                }else{
                    window.location.href = e.target.href;
                }
            }else{
                $label = $(this);
                $checkbox = $label.children('input[type="checkbox"]');
                $parent = $checkbox.parents('.super-field-wrapper:eq(0)');
                $field = $parent.children('input[type="hidden"]');
                $counter = 0;
                $maxlength = $parent.find('.super-shortcode-field').data('maxlength');
                if($label.hasClass('super-active')){
                    $label.removeClass('super-active');
                }else{
                    $checked = $parent.find('label.super-active');
                    if($checked.length >= $maxlength){
                        return false;
                    }
                    $label.addClass('super-active');
                }
                $checked = $parent.find('label.super-active');
                $value = '';
                $checked.each(function () {
                    if ($counter === 0) $value = $(this).children('input').val();
                    if ($counter !== 0) $value = $value + ',' + $(this).children('input').val();
                    $counter++;
                });
                $field.val($value);
                $validation = $field.data('validation');
                $duration = SUPER.get_duration($field.parents('.super-form'));
                if(typeof $validation !== 'undefined' && $validation !== false){
                    SUPER.handle_validations($field, $validation, '', $duration);
                }
                SUPER.after_checkbox_change_hook($field);
            }
            return false;
        });

        $doc.on('change', '.super-form select', function () {
            var $this = $(this),
                $duration = SUPER.get_duration($this.parents('.super-form')),
                $min = $this.data('minlength'),
                $max = $this.data('maxlength'),
                $validation;
            if(($min>0) && ($this.val() === null)){
                SUPER.handle_errors($this, $duration);
            }else if($this.val().length > $max){
                SUPER.handle_errors($this, $duration);
            }else if($this.val().length < $min){
                SUPER.handle_errors($this, $duration);
            }else{
                $this.parents('.super-field:eq(0)').children('p').fadeOut($duration, function() {
                    $(this).remove();   
                });
            }
            $validation = $this.data('validation');
            $duration = SUPER.get_duration($this.parents('.super-form'));
            if(typeof $validation !== 'undefined' && $validation !== false){
                SUPER.handle_validations($this, $validation, '', $duration);
            }
            SUPER.after_dropdown_change_hook($this);
        });
        
        SUPER.init_button_colors();
        
        $doc.on('mouseleave','.super-button .super-button-wrap',function(){
            $(this).parent().removeClass('super-focus');
            SUPER.init_button_colors( $(this) );
        });
        $doc.on('mouseover','.super-button .super-button-wrap',function(){
            SUPER.init_button_hover_colors( $(this).parent() );
        });
        

        function super_focus_first_tab_index_field(e, $form, $multipart) {
            var $disable_autofocus = $multipart.attr('data-disable-autofocus');
            if( typeof $disable_autofocus === 'undefined' ) {
                var $fields = $multipart.find('.super-field:not('+super_elements_i18n.tab_index_exclusion+')[data-super-tab-index]');
                var $highest_index = 0;
                $fields.each(function(){
                    var $index = parseFloat($(this).attr('data-super-tab-index'));
                    if( $index>$highest_index ) {
                        $highest_index = $index;
                    }
                });
                var $lowest_index = $highest_index;
                $fields.each(function(){
                    var $index = parseFloat($(this).attr('data-super-tab-index'));
                    if( $index<$lowest_index ) {
                        $lowest_index = $index;
                    }
                });
                var $next = $multipart.find('.super-field:not('+super_elements_i18n.tab_index_exclusion+')[data-super-tab-index="'+$lowest_index+'"]');
                SUPER.super_focus_next_tab_field(e, $next, $form, $next);
            }
        }

        // @since 3.3.0 - make sure to skip the multi-part if no visible elements are found
        function super_skip_multipart($this, $form, $index, $active_index){
            var $skip = true;
            $form.find('.super-multipart.active .super-field:not(.super-button)').each(function(){
                var $this = $(this);
                var $field = $this.find('.super-shortcode-field');
                var $hidden = false;
                // This is a field
                if($field.length){
                    $field.parents('.super-shortcode.super-column').each(function(){
                        if($(this).css('display')=='none'){
                            $hidden = true;
                        }
                    });
                    var $parent = $field.parents('.super-shortcode:eq(0)');
                    if( ( $hidden===true )  || ( ( $parent.css('display')=='none' ) && ( !$parent.hasClass('super-hidden') ) ) ) {
                        // Exclude conditionally
                    }else{
                        $skip = false;
                    }
                // This is either a HTML element or something else without a field
                }else{
                    $this.parents('.super-shortcode.super-column').each(function(){
                        if($(this).css('display')=='none'){
                            $hidden = true;
                        }
                    });
                    if( ( $hidden===true )  || ( ( $this.css('display')=='none' ) && ( !$this.hasClass('super-hidden') ) ) ) {
                        // Exclude conditionally
                    }else{
                        $skip = false;
                    }
                }
            });
            if($skip===true){
                var $multipart = $form.find('.super-multipart.active');
                if( ($this.hasClass('super-prev-multipart')) || ($this.hasClass('super-next-multipart')) ){
                    if($this.hasClass('super-prev-multipart')){
                        $multipart.find('.super-prev-multipart').click();
                    }else{
                        $multipart.find('.super-next-multipart').click();
                    }
                }else{
                    if($index<$active_index){
                        $multipart.find('.super-prev-multipart').click();
                    }else{
                        $multipart.find('.super-next-multipart').click();
                    }
                }
            }
            return $skip;
        }


        // Multi Part Columns
        $doc.on('click','.super-multipart-step',function(e){
            var $this = $(this),
                $form = $this.parents('.super-form:eq(0)'),
                $current_active = $form.find('.super-multipart.active'),          
                $current_active_tab = $form.find('.super-multipart-step.active'),          
                $active_index = $current_active_tab.index(),
                $index = $this.index(),
                $total = $form.find('.super-multipart').length,
                $validate,
                $result,
                $progress,
                $multipart,
                $skip;

            // @since 2.0 - validate multi-part before going to next step
            $validate = $current_active.data('validate');
            if($validate===true){
                $result = SUPER.validate_form( $current_active, $this, true, e, true );
                if($result===false) return false;
            }

            $progress = 100 / $total;
            $progress = $progress * ($index+1);
            $multipart = $form.find('.super-multipart:eq('+$index+')');
            $form.find('.super-multipart-progress-bar').css('width',$progress+'%');
            $form.find('.super-multipart-step').removeClass('active');
            $form.find('.super-multipart').removeClass('active');
            $multipart.addClass('active');
            $this.addClass('active');

            // @since 3.3.0 - make sure to skip the multi-part if no visible elements are found
            $skip = super_skip_multipart($this, $form, $index, $active_index);
            if($skip===true) return false;

            // Focus first TAB index field in next multi-part
            super_focus_first_tab_index_field(e, $form, $multipart);

        });
        
        // @since 4.7.0 - translation language switcher
        // Open/close dropdown
        $doc.on('click', '.super-i18n-switcher .super-dropdown', function(){
            $(this).toggleClass('super-active');
        });      
        // Close when moved outside
        $doc.on('mouseleave', '.super-i18n-switcher .super-dropdown', function(){
            $(this).removeClass('super-active');
        });
        // Switch to different language when clicked
        $doc.on('click', '.super-i18n-switcher .super-dropdown-items > li', function(){
            var $this = $(this),
                $form = $this.parents('.super-form:eq(0)'),
                $form_id = $form.find('input[name="hidden_form_id"]').val(),
                $i18n = $this.attr('data-value');

            $this.parent().children('li').removeClass('super-active');
            $this.addClass('super-active');
            // Also move to placeholder
            $this.parents('.super-dropdown').children('.super-dropdown-placeholder').html($this.html());
            
            // Remove initialized class
            $form.children('form').html('');
            $form.removeClass('super-initialized');
            $.ajax({
                url: super_elements_i18n.ajaxurl,
                type: 'post',
                data: {
                    action: 'super_language_switcher',
                    form_id: $form_id,
                    i18n: $i18n
                },
                success: function (result) {
                    var data = JSON.parse(result);
                    if(data.rtl==true){
                        $form.addClass('super-rtl');
                    }else{
                        $form.removeClass('super-rtl');
                    }
                    $form.children('form').html(data.html);
                },
                complete: function(){
                    $form.addClass('super-initialized');
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    console.log(xhr, ajaxOptions, thrownError);
                    alert('Failed to process data, please try again');
                }
            });
            
        });


        // Multi Part Next Prev Buttons
        $doc.on('click','.super-prev-multipart, .super-next-multipart',function(e){
            var $index,
                $this = $(this),
                $form = $this.parents('.super-form:eq(0)'),
                $total = $form.find('.super-multipart').length,
                $current_step = $form.find('.super-multipart-step.active').index(),
                $validate,
                $result,
                $skip,
                $progress,
                $multipart;

            if($this.hasClass('super-prev-multipart')){
                if($current_step>0){
                    $form.find('.super-multipart').removeClass('active');   
                    $form.find('.super-multipart-step').removeClass('active');
                    $form.find('.super-multipart:eq('+($current_step-1)+')').addClass('active');   
                    $form.find('.super-multipart-step:eq('+($current_step-1)+')').addClass('active');
                    $index = $current_step-1;
                }
            }else{
            
                // @since 2.0.0 - validate multi-part before going to next step
                $validate = $form.find('.super-multipart.active').data('validate');
                if($validate===true){
                    $result = SUPER.validate_form( $form.find('.super-multipart.active'), $this, true, e, true );
                    if($result===false) return false;
                }
            
                if($total>$current_step+1){
                    $form.find('.super-multipart').removeClass('active');   
                    $form.find('.super-multipart-step').removeClass('active');
                    $form.find('.super-multipart:eq('+($current_step+1)+')').addClass('active');
                    $form.find('.super-multipart-step:eq('+($current_step+1)+')').addClass('active');
                    $index = $current_step+1;
                }
            }

            // @since 3.3.0 - make sure to skip the multi-part if no visible elements are found
            $skip = super_skip_multipart($this, $form);
            if($skip===true) return false;

            $total = $form.find('.super-multipart').length;
            $progress = 100 / $total;
            $progress = $progress * ($index+1);
            $form.find('.super-multipart-progress-bar').css('width',$progress+'%');
            $index = 0;
            $form.find('.super-multipart').each(function(){
                if(!$(this).find('.error-active').length){
                    $form.find('.super-multipart-steps').find('.super-multipart-step:eq('+$index+')').removeClass('super-error');
                }
                $index++;
            });
            
            $multipart = $form.find('.super-multipart.active');
            
            // @since 4.3.0 - disable scrolling for multi-part next prev
            if(typeof $multipart.attr('data-disable-scroll-pn') === 'undefined'){
                $('html, body').animate({
                    scrollTop: $form.offset().top - 30
                }, 500);
            }

            // Focus first TAB index field in next multi-part
            super_focus_first_tab_index_field(e, $form, $multipart);

        });
    });

})(jQuery);
