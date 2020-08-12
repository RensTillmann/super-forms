/* globals jQuery, SUPER, super_elements_i18n */
"use strict";
(function($) { // Hide scope, no $ conflict

    // Init dropdowns
    SUPER.init_dropdowns = function(){
        $('.super-dropdown-ui').each(function(){
            if($(this).children('.super-placeholder').html()===''){
                var $first_item = $(this).find('.super-item:eq(1)');
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
                thousands: $thousand_separator,
                decimal: $decimal_seperator,
                precision: $decimals
            });
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
        if(selectedDate===''){
            $this.parentNode.classList.remove('super-filled');
        }else{
            $this.parentNode.classList.add('super-filled');
        }

        var original_selectedDate = selectedDate,
            $format = $this.dataset.jsformat,
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
                $this.dataset.mathYear = year;
                $this.dataset.mathMonth = month;
                $this.dataset.mathDay = day;
                firstDate = new Date(Date.UTC(year, month-1, day));
                $this.dataset.mathDiff = firstDate.getTime();
                $this.dataset.mathAge = SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'years');
                $this.dataset.mathAgeMonths = SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'months');
                $this.dataset.mathAgeDays = SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'days');
                $connected_min = $this.dataset.connectedMin;
                if( typeof $connected_min !== 'undefined' ) {
                    if( $connected_min!=='' ) {
                        $connected_date = document.querySelector('.super-shortcode-field.super-datepicker[name="'+$connected_min+'"]');
                        if($connected_date){
                            $format = $connected_date.dataset.jsformat;
                            $connected_min_days = parseInt($this.dataset.connectedMinDays, 10);
                            min_date = Date.parseExact(original_selectedDate, $parse_format).add({ days: $connected_min_days }).toString($format);
                            $($connected_date).datepicker('option', 'minDate', min_date );
                            if($connected_date.value===''){
                                $connected_date.value = min_date;
                            }
                            $parse = Date.parseExact($connected_date.value, $parse_format);
                            if($parse!==null){
                                selectedDate = $parse.toString($format);
                                d = Date.parseExact(selectedDate, $format);
                                year = d.toString('yyyy');
                                month = d.toString('MM');
                                day = d.toString('dd');          
                                selectedDate = new Date(Date.UTC(year, month-1, day));
                                $connected_date.dataset.mathDiff = selectedDate.getTime();
                                SUPER.init_connected_datepicker($connected_date, $connected_date.value, $parse_format, oneDay);
                            }
                        }
                    }
                }
                $connected_max = $this.dataset.connectedMax;
                if(typeof $connected_max !== 'undefined'){
                    if( $connected_max!=='' ) {
                        $connected_date = document.querySelector('.super-shortcode-field.super-datepicker[name="'+$connected_max+'"]');
                        if($connected_date){
                            $format = $connected_date.dataset.jsformat;
                            $connected_max_days = parseInt($this.dataset.connectedMaxDays, 10);
                            max_date = Date.parseExact(original_selectedDate, $parse_format).add({ days: $connected_max_days }).toString($format);
                            $($connected_date).datepicker('option', 'maxDate', max_date );
                            if($connected_date.value===''){
                                $connected_date.value = max_date;
                            }
                            $parse = Date.parseExact($connected_date.value, $parse_format);
                            if($parse!==null){
                                selectedDate = $parse.toString($format);
                                d = Date.parseExact(selectedDate, $format);
                                year = d.toString('yyyy');
                                month = d.toString('MM');
                                day = d.toString('dd');          
                                selectedDate = new Date(Date.UTC(year, month-1, day));
                                $connected_date.dataset.mathDiff = selectedDate.getTime();
                                SUPER.init_connected_datepicker($connected_date, $connected_date.value, $parse_format, oneDay);
                            }
                        }
                    }
                }
            }
        }
        SUPER.after_field_change_blur_hook({el: $this});
    };

    // init Datepicker
    SUPER.init_datepicker = function(){
        var i;

        // Init datepickers
        var oneDay = 24*60*60*1000, // hours*minutes*seconds*milliseconds
            nodes = document.querySelectorAll('.super-datepicker');

        for (i = 0; i < nodes.length; ++i) {
            if(typeof datepicker === "function"){ 
                $(nodes[i]).datepicker('destroy');
            }
        }

        nodes = document.querySelectorAll('.super-datepicker:not(.super-picker-initialized)');
        for (i = 0; i < nodes.length; ++i) {
            var el = nodes[i],
                form = SUPER.get_frontend_or_backend_form({el: el}),
                format = el.dataset.format, //'MM/dd/yyyy';
                jsformat = el.dataset.jsformat, //'MM/dd/yyyy';
                isRTL = (el.closest('.super-form') ? el.closest('.super-form').classList.contains('super-rtl') : false),
                min = el.dataset.minlength,
                max = el.dataset.maxlength,
                workDays,
                weekends,
                regex = /{([^"']*?)}/g,
                range = el.dataset.range,
                maxPicks =(el.dataset.maxpicks ? parseInt(el.dataset.maxpicks, 10) : 1),
                firstDay = el.dataset.firstDay,
                localization = el.dataset.localization,
                widget,connectedMinDays,minDate,connectedMaxDays,maxDate,
                parse,year,month,firstDate,$date,days,found,date,fullDate,dateFrom,
                dateTo,d1,d2,from,to,check,day,exclDays,exclDaysOverride,exclDaysOverrideReplaced,exclDates,exclDatesReplaced,
                changeMonth =(el.dataset.changeMonth==='true' ? true : false),
                changeYear =(el.dataset.changeYear==='true' ? true : false),
                showMonthAfterYear = (el.dataset.showMonthAfterYear==='true' ? true : false),
                showWeek = (el.dataset.showWeek==='true' ? true : false),
                numberOfMonths = parseInt(el.dataset.numberOfMonths, 10),
                showOtherMonths = (el.dataset.showOtherMonths==='true' ? true : false),
                selectOtherMonths = (el.dataset.selectOtherMonths==='true' ? true : false),

                // @since 2.5.0 - Date.parseExact compatibility
                parseFormat = [
                    jsformat
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

            if(typeof min !== 'undefined') min = min.toString();
            if(typeof max !== 'undefined') max = max.toString();

            el.classList.add('super-picker-initialized');
            if( el.value!=='' ) {
                parse = Date.parseExact(el.value, parseFormat);
                if( parse!==null ) {
                    year = parse.toString('yyyy');
                    month = parse.toString('MM');
                    day = parse.toString('dd');
                    el.dataset.mathYear = year;
                    el.dataset.mathMonth = month;
                    el.dataset.mathDay = day;
                    firstDate = new Date(Date.UTC(year, month-1, day));
                    el.dataset.mathDiff = firstDate.getTime();
                    el.dataset.mathAge = SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'years');
                    el.dataset.mathAgeMonths = SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'months');
                    el.dataset.mathAgeDays = SUPER.init_datepicker_get_age(month+'/'+day+'/'+year, 'days');
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
                el.dataset.mathDiff = '0';
                el.dataset.mathAge = '0';
            }

            var multiDatesClassName = '',
                singleDatesClassName = '';
            if(maxPicks>1){
                multiDatesClassName = 'super-datepicker-multidates';
            }else{
                singleDatesClassName = 'super-datepicker-singledates';
            }
            var options = {
                onClose: function( selectedDate ) {
                    SUPER.init_connected_datepicker(this, selectedDate, parseFormat, oneDay);
                },
                beforeShowDay: function(dt) {
                    workDays = (this.dataset.workDays == 'true');
                    weekends = (this.dataset.weekends == 'true');
                    day = dt.getDay();
                    exclDays = this.dataset.exclDays;
                    exclDates = (typeof this.dataset.exclDates !=='undefined' ? this.dataset.exclDates : undefined);
                    exclDaysOverride = this.dataset.exclDaysOverride;
                    exclDaysOverride = (typeof this.dataset.exclDaysOverride !=='undefined' ? this.dataset.exclDaysOverride : undefined);

                    if(typeof exclDays !== 'undefined'){
                        days = exclDays.split(',');
                        found = (days.indexOf(day.toString()) > -1);
                        if(found){
                            if(typeof exclDaysOverride !== 'undefined'){
                                exclDaysOverrideReplaced = SUPER.update_variable_fields.replace_tags({form: form, regex: regex, value: exclDaysOverride});
                                exclDaysOverrideReplaced = exclDaysOverrideReplaced.split("\n");
                                date = ('0' + dt.getDate()).slice(-2);
                                month = ('0' + (dt.getMonth()+1)).slice(-2);
                                fullDate = dt.getFullYear() + '-' + month + '-' + date;
                                i;
                                for( i=0; i < exclDaysOverrideReplaced.length; i++ ) {
                                    if(exclDaysOverrideReplaced[i]==='') continue;
                                    // If excluding a fixed day of month
                                    if(exclDaysOverrideReplaced[i].length<=2){
                                        if(exclDaysOverrideReplaced[i]==day){
                                            return [true, ""];
                                        }
                                    }
                                    // If excluding a specific month
                                    if(exclDaysOverrideReplaced[i].length===3){
                                        if(exclDaysOverrideReplaced[i].toLowerCase()==dt.toString('MMM').toLowerCase()){
                                            return [true, ""];
                                        }
                                    }
                                    // If excluding a date range
                                    if(exclDaysOverrideReplaced[i].split(';').length>1){
                                        dateFrom = exclDaysOverrideReplaced[i].split(';')[0];
                                        dateTo = exclDaysOverrideReplaced[i].split(';')[1];
                                        d1 = dateFrom.split("-");
                                        d2 = dateTo.split("-");
                                        from = new Date(d1[0], parseInt(d1[1], 10)-1, d1[2]);  // -1 because months are from 0 to 11
                                        to   = new Date(d2[0], parseInt(d2[1], 10)-1, d2[2]);
                                        check = new Date(dt.getFullYear(), parseInt(month, 10)-1, date);
                                        if(check >= from && check <= to){
                                            return [true, ""];
                                        }
                                    }
                                    // If excluding single date
                                    if(exclDaysOverrideReplaced[i]==fullDate){
                                        return [true, ""];
                                    }
                                }
                                // Be default we disable this day
                                return [false, "super-disabled-day"];
                            }else{
                                return [false, "super-disabled-day"];
                            }
                        }
                    }
                    if(typeof exclDates !== 'undefined'){
                        exclDatesReplaced = SUPER.update_variable_fields.replace_tags({form: form, regex: regex, value: exclDates});
                        exclDatesReplaced = exclDatesReplaced.split("\n");
                        date = ('0' + dt.getDate()).slice(-2);
                        month = ('0' + (dt.getMonth()+1)).slice(-2);
                        fullDate = dt.getFullYear() + '-' + month + '-' + date;
                        i;
                        for( i=0; i < exclDatesReplaced.length; i++ ) {
                            if(exclDatesReplaced[i]==='') continue;
                            // If excluding a fixed day of month
                            if(exclDatesReplaced[i].length<=2){
                                if(exclDatesReplaced[i]==day){
                                    return [false, "super-disabled-day"];
                                }
                            }
                            // If excluding a specific month
                            if(exclDatesReplaced[i].length===3){
                                if(exclDatesReplaced[i].toLowerCase()==dt.toString('MMM').toLowerCase()){
                                    return [false, "super-disabled-day"];
                                }
                            }
                            // If excluding a date range
                            if(exclDatesReplaced[i].split(';').length>1){
                                dateFrom = exclDatesReplaced[i].split(';')[0];
                                dateTo = exclDatesReplaced[i].split(';')[1];
                                d1 = dateFrom.split("-");
                                d2 = dateTo.split("-");
                                from = new Date(d1[0], parseInt(d1[1], 10)-1, d1[2]);  // -1 because months are from 0 to 11
                                to   = new Date(d2[0], parseInt(d2[1], 10)-1, d2[2]);
                                check = new Date(dt.getFullYear(), parseInt(month, 10)-1, date);
                                if(check >= from && check <= to){
                                    return [false, "super-disabled-day"];
                                }
                            }
                            // If excluding single date
                            if(exclDatesReplaced[i]==fullDate){
                                return [false, "super-disabled-day"];
                            }
                        }
                    }
                    if( (weekends===true) && (workDays===true) ) {
                        return [true, ""];
                    }else{
                        if(weekends===true){
                            return [day === 0 || day == 6, ""];
                        }
                        if(workDays===true){
                            return [day == 1 || day == 2 || day == 3 || day == 4 || day == 5, ""];
                        }
                    }
                    return [];
                },
                beforeShow: function(input, inst) {
                    widget = $(inst).datepicker('widget');
                    widget[0].classList.add('super-datepicker-dialog');
                    if(multiDatesClassName!=='') widget[0].classList.add(multiDatesClassName);
                    if(singleDatesClassName!=='') widget[0].classList.add(singleDatesClassName);
                    $('.super-datepicker[data-connected-min="'+$(this).attr('name')+'"]').each(function(){
                        if($(this).val()!==''){
                            connectedMinDays = $(this).data('connected-min-days');
                            minDate = Date.parseExact($(this).val(), parseFormat).add({ days: connectedMinDays }).toString(jsformat);
                            $(el).datepicker('option', 'minDate', minDate );
                        }
                    });
                    $('.super-datepicker[data-connected-max="'+$(this).attr('name')+'"]').each(function(){
                        if($(this).val()!==''){
                            connectedMaxDays = $(this).data('connected-max-days');
                            maxDate = Date.parseExact($(this).val(), parseFormat).add({ days: connectedMaxDays }).toString(jsformat);
                            $(el).datepicker('option', 'maxDate', maxDate );
                        }
                    });
                },
                yearRange: range, //'-100:+5', // specifying a hard coded year range
                showAnim: '',
                showOn: $(this).parent().find('.super-icon'),
                minDate: min,
                maxDate: max,
                dateFormat: format, //mm/dd/yy    -    yy-mm-dd    -    d M, y    -    d MM, y    -    DD, d MM, yy    -    &apos;day&apos; d &apos;of&apos; MM &apos;in the year&apos; yy
                firstDay: firstDay,
                isRTL: isRTL,
                closeText: super_elements_i18n.closeText,
                prevText: super_elements_i18n.prevText,
                nextText: super_elements_i18n.nextText,
                currentText: super_elements_i18n.currentText,
                monthNames: super_elements_i18n.monthNames, // set month names
                monthNamesShort: super_elements_i18n.monthNamesShort, // set short month names
                dayNames: super_elements_i18n.dayNames, // set days names
                dayNamesShort: super_elements_i18n.dayNamesShort, // set short day names
                dayNamesMin: super_elements_i18n.dayNamesMin, // set more short days names
                weekHeader: super_elements_i18n.weekHeader,
                changeMonth: changeMonth,
                changeYear: changeYear,
                showMonthAfterYear: showMonthAfterYear,
                showWeek: showWeek,
                numberOfMonths: numberOfMonths,
                yearSuffix: '',
                showOtherMonths: showOtherMonths,
                selectOtherMonths: selectOtherMonths
            };
            if(maxPicks>1){
                options.maxPicks = maxPicks;
                $(el).multiDatesPicker(options);
            }else{
                $(el).datepicker(options);
            }

            // @since 4.9.3 - Datepicker localization (language and format)
            if(localization!==''){
                if(typeof $.datepicker.regional[localization] !== 'undefined'){
                    $.datepicker.regional[localization].yearSuffix = '';
                    $(el).datepicker( "option", $.datepicker.regional[localization] );
                }
            }

            $(el).parent().find('.super-icon').css('cursor','pointer');
        }
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
            $this[0].dataset.mathDiff = $timestamp;
            SUPER.after_field_change_blur_hook({el: $this[0]});
        }

        // Init timepickers
        $('.super-timepicker:not(.ui-timepicker-input)').each(function(){
            // Only if timepicker is a function
            if (typeof $.fn.timepicker !== 'function') return false;
            var $this = $(this),
                $is_rtl = $this.closest('.super-form').hasClass('super-rtl'),
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
            $form_id = $this.closest('.super-form').attr('id');
            $form_size = $this.closest('.super-form').data('field-size');
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
            if(this.value===''){
                this.parentNode.classList.remove('super-filled');
            }else{
                this.parentNode.classList.add('super-filled');
            }
        });
        $('.super-timepicker').parent().find('.super-icon').on('click',function(){
            $(this).parent().find('.super-timepicker').timepicker('show');
        });
    };

    // Initialize button colors
    SUPER.init_button_colors = function( el ) {    
        var i, nodes, type, color, light, dark, font, fontHover, wrap, btnName, btnNameIcon;

        if(!el){
            nodes = document.querySelectorAll('.super-button .super-button-wrap');
            for( i = 0; i < nodes.length; i++){
                SUPER.init_button_colors(nodes[i]);
            }
        }else{
            el = el.parentNode;
            type = el.dataset.type;
            color = el.dataset.color;
            light = el.dataset.light;
            dark = el.dataset.dark;
            font = el.dataset.font;
            fontHover = el.dataset.fontHover;
            wrap = el.querySelector('.super-button-wrap');
            btnName = wrap.querySelector('.super-button-name');
            btnNameIcon = btnName.querySelector('i');
            if(type=='diagonal'){
                if(typeof color !== 'undefined'){
                    wrap.style.borderColor = color;
                }else{
                    wrap.style.borderColor = '';
                }
                if(typeof font !== 'undefined'){
                    btnName.style.color = font;
                    if(btnNameIcon) btnNameIcon.style.color = font;
                }else{
                    btnName.style.color = '';
                    if(btnNameIcon) btnNameIcon.style.color = '';
                }
                el.querySelector('.super-button-wrap .super-after').style.backgroundColor = color;
            }
            if(type=='outline'){
                if(typeof color !== 'undefined'){
                    wrap.style.borderColor = color;
                }else{
                    wrap.style.borderColor = '';
                }
                wrap.style.backgroundColor = '';
                if(typeof font !== 'undefined'){
                    btnName.style.color = font;
                    if(btnNameIcon) btnNameIcon.style.color = font;
                }else{
                    btnName.style.color = '';
                    if(btnNameIcon) btnNameIcon.style.color = '';
                }
            }
            if(type=='2d'){
                wrap.style.backgroundColor = color;
                wrap.style.borderColor = light;
                btnName.style.color = font;
                if(btnNameIcon) btnNameIcon.style.color = font;
            }
            if(type=='3d'){
                wrap.style.backgroundColor = color;
                wrap.style.color = dark;
                wrap.style.borderColor = light;
                if(typeof fontHover !== 'undefined'){
                    btnName.style.color = font;
                    if(btnNameIcon) btnNameIcon.style.color = font;
                }else{
                    if(typeof font !== 'undefined'){
                        btnName.style.color = font;
                        if(btnNameIcon) btnNameIcon.style.color = font;
                    }else{
                        btnName.style.color = '';
                        if(btnNameIcon) btnNameIcon.style.color = '';
                    }
                }
            }
            if(type=='flat'){
                wrap.style.backgroundColor = color;
                btnName.style.color = font;
                if(btnNameIcon) btnNameIcon.style.color = font;
            }
        }
    };

    // Init button hover colors
    SUPER.init_button_hover_colors = function( el ) {  
        var type = el.dataset.type,
            color = el.dataset.color,
            hoverColor = el.dataset.hoverColor,
            hoverLight = el.dataset.hoverLight,
            hoverDark = el.dataset.hoverDark,
            font = el.dataset.font,
            fontHover = el.dataset.fontHover,
            wrap = el.querySelector('.super-button-wrap'),
            btnName = wrap.querySelector('.super-button-name'),
            btnNameIcon = btnName.querySelector('i');
        if(type=='2d'){
            wrap.style.backgroundColor = hoverColor;
            wrap.style.borderColor = hoverLight;
            btnName.style.color = fontHover;
            if(btnNameIcon) btnNameIcon.style.color = fontHover;
        }
        if(type=='flat'){
            wrap.style.backgroundColor = hoverColor;
            btnName.style.color = fontHover;
            if(btnNameIcon) btnNameIcon.style.color = fontHover;
        }
        if(type=='outline'){
            if(typeof hoverColor !== 'undefined'){
                wrap.style.backgroundColor = hoverColor;
            }else{
                if(typeof color !== 'undefined'){
                    wrap.style.backgroundColor = color;
                }else{
                    wrap.style.backgroundColor = '';
                }
            }
            wrap.style.borderColor = hoverColor;
            if(typeof fontHover !== 'undefined'){
                btnName.style.color = fontHover;
                if(btnNameIcon) btnNameIcon.style.color = fontHover;
            }else{
                if(typeof font !== 'undefined'){
                    btnName.style.color = font;
                    if(btnNameIcon) btnNameIcon.style.color = font;
                }else{
                    btnName.style.color = '';
                    if(btnNameIcon) btnNameIcon.style.color = '';
                }
            }
        }
        if(type=='diagonal'){
            if(typeof $color !== 'undefined'){
                wrap.style.borderColor = hoverColor;
            }else{
                wrap.style.borderColor = '';
            }
            if(typeof $font !== 'undefined'){
                btnName.style.color = fontHover;
                if(btnNameIcon) btnNameIcon.style.color = fontHover;
            }else{
                btnName.style.color = '';
                if(btnNameIcon) btnNameIcon.style.color = '';
            }
            wrap.querySelector('.super-after').style.backgroundColor = hoverColor;
            return false;
        }
        if(type=='2d'){
            return false;
        }
        if(typeof hoverColor !== 'undefined'){
            wrap.style.backgroundColor = hoverColor;
            if(type=='3d'){
                wrap.style.color = hoverDark;
                wrap.style.borderColor = hoverLight;
                if(typeof fontHover !== 'undefined'){
                    btnName.style.color = fontHover;
                    if(btnNameIcon) btnNameIcon.style.color = fontHover;
                }else{
                    if(typeof $font !== 'undefined'){
                        btnName.style.color = font;
                        if(btnNameIcon) btnNameIcon.style.color = font;
                    }else{
                        btnName.style.color = '';
                        if(btnNameIcon) btnNameIcon.style.color = '';
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
        
        SUPER.init_common_fields();

        var $doc = $(document);

        // @SINCE 4.9.0 - TABS content next/prev switching
        $doc.on('click', '.super-content-prev, .super-content-next', function(e){
            // Make sure we stop any other events from being triggered
            e.preventDefault();
            var $this = $(this),
                $tab_menu = $this.parents('.super-shortcode:eq(0)').children('.super-tabs-menu'),
                $tab_content = $this.parents('.super-tabs-contents:eq(0)'),
                $total = $tab_menu.children('.super-tabs-tab').length,
                $index = $tab_content.children('.super-tabs-content.super-active').index();
            // Next
            if($this.hasClass('super-content-next')){
                $index++;
                if($index >= $total) {
                    $index = $total-1;
                }
                if($index >= $total-1){
                    $tab_menu.find('.super-tab-next').hide();
                }else{
                    $tab_menu.find('.super-tab-next').show();
                }
                $tab_menu.find('.super-tab-prev').show();
            }
            // Prev
            if($this.hasClass('super-content-prev')){
                $index--;
                if($index < 0) {
                    $index = 0;
                }
                if($index===0){
                    $tab_menu.find('.super-tab-prev').hide();
                }else{
                    $tab_menu.find('.super-tab-prev').show();
                }
                $tab_menu.find('.super-tab-next').show();
            }
            $tab_menu.children('.super-tabs-tab').removeClass('super-active');
            $tab_menu.children('.super-tabs-tab:eq('+($index)+')').addClass('super-active');
            $tab_content.children('.super-tabs-content').removeClass('super-active');
            $tab_content.children('.super-tabs-content:eq('+($index)+')').addClass('super-active');
            return false;
        });
        // @SINCE 4.8.0 - TABS next/prev switching
        $doc.on('click', '.super-tab-prev, .super-tab-next', function(e){
            // Make sure we stop any other events from being triggered
            e.preventDefault();
            var $this = $(this),
                $tab_menu = $this.parents('.super-tabs-menu:eq(0)'),
                $tab_content = $tab_menu.parent().children('.super-tabs-contents'),
                $total = $this.parents('.super-tabs-menu:eq(0)').children('.super-tabs-tab').length,
                $index = $this.parents('.super-tabs-menu:eq(0)').children('.super-tabs-tab.super-active').index();
            // Next
            if($this.hasClass('super-tab-next')){
                $index++;
                if($index >= $total) {
                    $index = $total-1;
                }
                if($index >= $total-1){
                    $tab_menu.find('.super-tab-next').hide();
                }else{
                    $tab_menu.find('.super-tab-next').show();
                }
                $tab_menu.find('.super-tab-prev').show();

            }
            // Prev
            if($this.hasClass('super-tab-prev')){
                $index--;
                if($index < 0) {
                    $index = 0;
                }
                if($index===0){
                    $tab_menu.find('.super-tab-prev').hide();
                }else{
                    $tab_menu.find('.super-tab-prev').show();
                }
                $tab_menu.find('.super-tab-next').show();
            }
            $tab_menu.children('.super-tabs-tab').removeClass('super-active');
            $tab_menu.children('.super-tabs-tab:eq('+($index)+')').addClass('super-active');
            $tab_content.children('.super-tabs-content').removeClass('super-active');
            $tab_content.children('.super-tabs-content:eq('+($index)+')').addClass('super-active');
            return false;
        });
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
                // Make sure to correclty set the slider dragger when accordion is opened
                var i, nodes = $this[0].querySelectorAll('.super-shortcode.super-slider');
                for(i=0; i<nodes.length; i++){
                    if(nodes[i].querySelector('.slider')){
                        var field = nodes[i].querySelector('.super-shortcode-field');
                        SUPER.reposition_slider_amount_label(field, field.value);
                    }
                }
            }
            SUPER.init_super_responsive_form_fields({form: $parent[0]});
        });

        // @since 3.1.0 - auto transform to uppercase
        $doc.on('input', '.super-form .super-uppercase .super-shortcode-field', function() {
            $(this).val(function(_, val) {
                return val.toUpperCase();
            });
        });

        // @since 1.7 - count words on textarea field (useful for translation estimates)
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
                SUPER.after_field_change_blur_hook({el: $this[0]});
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
            SUPER.after_field_change_blur_hook({el: $input_field[0]});
        });
        // @since 4.9.0 - Quantity field only allow number input
        $doc.on('input', '.super-quantity .super-shortcode-field', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
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
            SUPER.after_field_change_blur_hook({el: $input_field[0]});
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
                    SUPER.after_field_change_blur_hook({el: $this[0]});
                }, $threshold);
            }else{
                SUPER.after_field_change_blur_hook({el: $this[0]});
            }
        });
        
        // @since 1.7 - improved dynamic columns
        $doc.on('click', '.super-form .super-duplicate-column-fields .super-add-duplicate', function(){
            var i, ii, iii, iiii,
                nodes,
                v,vv,
                found_field,
                el,
                parent,
                column,
                form,
                firstColumn,
                found,
                limit,
                unique_field_names = {},
                field_names = {},
                field_name,
                field_labels = {},
                counter = 0,
                field,
                clone,
                last_tab_index,
                added_fields = {},
                added_fields_with_suffix = {},
                added_fields_without_suffix = [],
                field_counter = 0,
                element,
                foundElements,
                html_fields,
                data_fields,
                conditions,
                new_data_attr,
                new_data_fields,
                new_text,
                condition,
                replace_names,
                new_field,
                new_count,
                suffix,
                superMath,
                value_n,
                names,
                name,
                values,
                array,
                match,
                number,
                regex = /{([^"']*?)}/g,
                oldv,
                duplicate_dynamically;

            function return_replace_names(value, new_count, replace_names){
                regex = /{([^"']*?)}/g;
                while ((vv = regex.exec(value)) !== null) {
                    // This is necessary to avoid infinite loops with zero-width matches
                    if (vv.index === regex.lastIndex) {
                        regex.lastIndex++;
                    }
                    field_name = vv[1].split(';')[0];
                    new_field = field_name+'_'+new_count;
                    replace_names[field_name] = new_field;
                }
                return replace_names;
            }

            el = $(this)[0];
            parent = el.closest('.super-duplicate-column-fields');
            // If custom padding is being used set $column to be the padding wrapper `div`
            column = ( el.parentNode.classList.contains('super-column-custom-padding') ? el.closest('.super-column-custom-padding') : parent.closest('.super-column') );
            form = SUPER.get_frontend_or_backend_form({el: el, form: form});
            var duplicateColumns = column.querySelectorAll('.super-duplicate-column-fields');
            firstColumn = duplicateColumns[0];
            found = column.querySelectorAll('.super-duplicate-column-fields').length;
            limit = parseInt(column.dataset.duplicateLimit, 10);
            if( (limit!==0) && (found >= limit) ) {
                return false;
            }

            unique_field_names = {}; // @since 2.4.0
            field_names = {};
            field_labels = {};
            counter = 0;
            nodes = firstColumn.querySelectorAll('.super-shortcode-field[name]');
            for (i = 0; i < nodes.length; ++i) {
                field = nodes[i];
                if(field.classList.contains('super-fileupload')){
                    field = field.parentNode.querySelector('.super-active-files');
                }
                name = field.name;
                unique_field_names[name] = name;
                field_names[counter] = name;
                field_labels[counter] = field.dataset.email;
                counter++;
            }

            counter = column.querySelectorAll('.super-duplicate-column-fields').length;
            clone = firstColumn.cloneNode(true);
            column.appendChild(clone);

            // @since 3.3.0 - hook after appending new column
            SUPER.after_appending_duplicated_column_hook(form, unique_field_names, clone);

            // Now reset field values to default
            SUPER.init_clear_form(form, clone);

            // @since 3.2.0 - increment for tab index fields when dynamic column is cloned
            if($(clone).find('.super-shortcode[data-super-tab-index]').last().length){
                last_tab_index = $(clone).find('.super-shortcode[data-super-tab-index]').last().attr('data-super-tab-index');
            }else{
                last_tab_index = '';
            }
            last_tab_index = parseFloat(last_tab_index);

            // First rename then loop through conditional logic and update names, otherwise we might think that the field didn't exist!
            added_fields = {};
            added_fields_with_suffix = {};
            added_fields_without_suffix = [];
            field_counter = 0;

            nodes = clone.querySelectorAll('.super-shortcode-field[name]');
            for (i = 0; i < nodes.length; ++i) {
                field = nodes[i];
                parent = field.closest('.super-field');
                if(field.classList.contains('super-fileupload')){
                     field.classList.remove('super-rendered');
                     field = field.parentNode.querySelector('.super-active-files');
                }
                // Keep valid TAB index
                if( (typeof parent.dataset.superTabIndex !== 'undefined') && (last_tab_index!=='') ) {
                    last_tab_index = parseFloat(parseFloat(last_tab_index)+0.001).toFixed(3);
                    parent.dataset.superTabIndex = last_tab_index;
                }
                added_fields[field.name] = field;
                field.name = field_names[field_counter]+'_'+(counter+1);
                // Replace %d with counter if found, otherwise append it
                // Remove whitespaces from start and end
                if(typeof field_labels[field_counter] !== 'undefined'){
                    field_labels[field_counter] = field_labels[field_counter].trim();
                    if(field_labels[field_counter].indexOf("%d")===-1){
                        // Not found, just return with counter appended at the end
                        field_labels[field_counter] = field_labels[field_counter] + ' ' + counter;
                    }else{
                        // Found, return with counter replaced at correct position
                        field_labels[field_counter] = field_labels[field_counter].replace('%d', counter+1);
                    }
                    field.dataset.email = field_labels[field_counter];
                }
                added_fields_with_suffix[field_names[field_counter]] = field_names[field_counter]+'_'+(counter+1);
                added_fields_without_suffix.push(field_names[field_counter]+'_'+(counter+1));
                if( field.classList.contains('hasDatepicker') ) field.classList.remove('hasDatepicker'); field.id = '';
                if( field.classList.contains('ui-timepicker-input') ) field.classList.remove('ui-timepicker-input');
                field_counter++;
            }
            // @since 4.6.0 - update html field tags attribute
            // @since 4.6.0 - update accordion title and description field tags attribute
            // @since 4.9.6 - update google maps field tags attribute
            // Get all elements based on field tag attribute that contain one of these field names
            // Then convert it to an array and append the missing field names
            // @IMPORTANT: Only do this for elements that are NOT inside a dynamic column
            foundElements = [];
            $.each(added_fields_with_suffix, function( index ) {
                html_fields = form.querySelectorAll('.super-google-map[data-fields*="{'+index+'}"], .super-html-content[data-fields*="{'+index+'}"], .super-accordion-title[data-fields*="{'+index+'}"], .super-accordion-desc[data-fields*="{'+index+'}"]');
                for (i = 0; i < html_fields.length; ++i) {
                    if(!html_fields[i].closest('.super-duplicate-column-fields')){
                        found = false;
                        for (ii = 0; ii < foundElements.length; ++ii) {
                            if($(foundElements[ii]).is(html_fields[i])) found = true;
                        }
                        if(!found) foundElements.push(html_fields[i]);
                    }
                }
            });
            for (i = 0; i < foundElements.length; ++i) {
                foundElements[i].dataset.fields = foundElements[i].dataset.fields+'{' + added_fields_without_suffix.join('}{') + '}';
            }

            // Now we have updated the names accordingly, we can proceed updating conditional logic and variable fields etc.
            nodes = clone.querySelectorAll('.super-shortcode');
            for (i = 0; i < nodes.length; ++i) {
                element = nodes[i];
                duplicate_dynamically = column.dataset.duplicateDynamically;
                if(duplicate_dynamically=='true') {
                    // @since 3.1.0 - update html tags after adding dynamic column
                    if(element.classList.contains('super-html')){
                        data_fields = element.querySelector('.super-html-content').dataset.fields;
                        if( typeof data_fields !== 'undefined' ) {
                            new_count = counter+1;
                            data_fields = data_fields.split('}');
                            new_data_fields = {};
                            $.each(data_fields, function( k, v ) {
                                if(v!==''){
                                    v = v.replace('{','');
                                    oldv = v;
                                    v = v.toString().split(';');
                                    name = v[0];
                                    // First check if the field even exists, if not just skip
                                    
                                    found_field = SUPER.field(form, name);
                                    if(!found_field){
                                        // Do nothing
                                        return;
                                    }
                                    // Next step is to check if this field is outside the dynamic column
                                    // If this is the case we will not change it (we do not want to increase the field name from for instance `name` to `name_2`)
                                    if(!found_field.closest('.super-duplicate-column-fields')){
                                        // Field exists, but is not inside dynamic column, so skip name change but
                                        // it is still a valid tag so it must be replaced with the field value
                                        // that's why we add it to the `$new_data_fields` array so it will be updated accordingly
                                        new_data_fields[oldv] = name;
                                        return;
                                    }
                                    // Check if this is a {dynamic_column_counter} tag
                                    // If so we can skip it
                                    if(name=='dynamic_column_counter') return;
                                    // If this is a advanced tag make sure to correctly append the number
                                    number = v[1];
                                    if(typeof number==='undefined'){
                                        number = '';
                                    }else{
                                        number = ';'+number;
                                    }
                                    // Finally add the updated tag to the `$new_data_fields` array
                                    new_data_fields[oldv] = name+'_'+new_count+number;
                                }
                            });
                            // Loop through all tags that require to be updated, and add it as a string to combine all of them
                            new_data_attr = '';
                            $.each(new_data_fields, function( k, v ) {
                                new_data_attr += '{'+v+'}';
                            });
                            element.querySelector('.super-html-content').dataset.fields = new_data_attr;
                            new_text = element.querySelector('textarea').value;
                            $.each(new_data_fields, function( k, v ) {
                                new_text = new_text.split('{'+k+';').join('{'+v+';');
                                new_text = new_text.split('{'+k+'}').join('{'+v+'}');
                            });
                            element.querySelector('textarea').value = new_text;
                        }
                    }

                    // Update conditional logic names
                    // Update validate condition names
                    // Update variable condition names 
                    var conditionElements = element.querySelectorAll(':scope > .super-conditional-logic,:scope > .super-validate-conditions,:scope > .super-variable-conditions');
                    for (ii = 0; ii < conditionElements.length; ++ii) {
                        condition = conditionElements[ii];
                        new_count = counter+1;
                        // Make sure to grab the value of the element and not the HTML due to html being escaped otherwise
                        conditions = JSON.parse(condition.value);
                        if(typeof conditions !== 'undefined'){
                            replace_names = {};
                            for (iii = 0; iii < conditions.length; ++iii) {
                                v = conditions[iii];
                                if(v.field!=='' && v.field.indexOf('{')===-1) v.field = '{'+v.field+'}';
                                if(v.field_and!=='' && v.field_and.indexOf('{')===-1) v.field_and = '{'+v.field_and+'}';
                                replace_names = return_replace_names(v.field, new_count, replace_names);
                                replace_names = return_replace_names(v.value, new_count, replace_names);
                                replace_names = return_replace_names(v.field_and, new_count, replace_names);
                                replace_names = return_replace_names(v.value_and, new_count, replace_names);
                                if(condition.classList.contains('super-variable-conditions')){
                                    replace_names = return_replace_names(v.new_value, new_count, replace_names);
                                }
                            }

                            for (iii = 0; iii < conditions.length; ++iii) {
                                Object.keys(conditions[iii]).forEach(function(index) {
                                    superMath = conditions[iii][index];
                                    if( superMath!=='' ) {
                                        array = [];
                                        iiii = 0;
                                        while ((match = regex.exec(superMath)) !== null) {
                                            array[iiii] = match[1];
                                            iiii++;
                                        }
                                        for (iiii = 0; iiii < array.length; iiii++) {
                                            values = array[iiii];
                                            names = values.toString().split(';');
                                            name = names[0];
                                            suffix = '';
                                            if(typeof names[1] === 'undefined'){
                                                value_n = 0;
                                            }else{
                                                value_n = names[1];
                                                suffix = ';'+value_n;
                                            }

                                            new_field = name+'_'+new_count;
                                            if(SUPER.field_exists(form, new_field)!==0){
                                                superMath = superMath.replace('{'+name+suffix+'}', '{'+new_field+suffix+'}');
                                            }
                                        }
                                    }
                                    conditions[iii][index] = superMath;
                                });
                            }

                            data_fields = condition.dataset.fields;
                            Object.keys(replace_names).forEach(function(index) {
                                v = replace_names[index];
                                if(SUPER.field_exists(form, v)!==0){
                                    // @since 2.4.0 - also update the data fields and tags attribute names
                                    data_fields = data_fields.split('{'+index+';').join('{'+v+';');
                                    data_fields = data_fields.split('{'+index+'}').join('{'+v+'}');
                                }else{
                                    // The field does not exist
                                    found_field = SUPER.field(form, index);
                                    if(found_field) added_fields[index] = found_field;
                                }
                            });
                            condition.dataset.fields = data_fields;
                            condition.value = JSON.stringify(conditions);
                        }
                    }

                    // SUPER.after_duplicate_column_fields_hook(el, $element, $counter, $column, $field_names, $field_labels);
                }
            }

            // ############ !!!! IMPORTANT !!!! ############
            // DO NOT TURN THE BELOW 2 HOOKS AROUND OR IT
            // WILL BRAKE THE CALCULATOR ELEMENT
            // ############ !!!! IMPORTANT !!!! ############

            // @since 2.4.0 - hook after adding new column
            SUPER.after_duplicating_column_hook(form, unique_field_names, clone);            

            // Now loop through all elements that have data-fields inside the cloned object
            // Loop through all the added fields and get all the according data-fields
            // These need to be added and triggered to make sure conditions and calculations can be executed properly
            var all_data_fields = clone.querySelectorAll('[data-fields]');
            for (i = 0; i < all_data_fields.length; ++i) {
                field = all_data_fields[i];
                data_fields = field.dataset.fields;
                data_fields = data_fields.split('}{');
                // Loop through all of the data fields and remove { and }, and also split ; in case of advanced tags
                for (ii = 0; ii < data_fields.length; ++ii) {
                    if(data_fields[ii]){
                        v = data_fields[ii];
                        v = v.replace('{', '').replace('}', '');
                        v = v.split(';')[0]; // If advanced tags is used grab the field name
                        // Now add the field to the array but only if the field exists                    
                        found_field = SUPER.field(form, v);
                        if(found_field) added_fields[v] = found_field;
                    }
                }
            }

            // @since 2.4.0 - update conditional logic and other variable fields based on the newly added fields
            Object.keys(added_fields).forEach(function(index) {
                SUPER.after_field_change_blur_hook({el: added_fields[index], form: form});
            });

            SUPER.init_common_fields();

        });

        // Delete dynamic column
        $doc.on('click', '.super-duplicate-column-fields .super-delete-duplicate', function(){
            var i, nodes,
                form = this.closest('.super-form'),
                removedFields = {},
                htmlFields,
                dataFields,
                parent = this.closest('.super-duplicate-column-fields'),
                foundElements = [];
               
            nodes = parent.querySelectorAll('.super-shortcode-field');
            for (i = 0; i < nodes.length; ++i) {
                removedFields[nodes[i].name] = nodes[i];
            }
            // @since 4.6.0 - update html field tags attribute
            // @since 4.6.0 - update accordion title and description field tags attribute
            // @since 4.9.6 - update google maps field tags attribute
            // Get all elements based on field tag attribute that contain one of these field names
            // Then convert it to an array and append the missing field names
            // @IMPORTANT: Only do this for elements that are NOT inside a dynamic column
            Object.keys(removedFields).forEach(function(index) {
                htmlFields = $(form).find('.super-google-map[data-fields*="{'+index+'}"], .super-html-content[data-fields*="{'+index+'}"], .super-accordion-title[data-fields*="{'+index+'}"], .super-accordion-desc[data-fields*="{'+index+'}"]');
                htmlFields.each(function(){
                    var $this = $(this);
                    if(!$this.parents('.super-duplicate-column-fields:eq(0)').length){
                        var $found = false;
                        $.each(foundElements, function( index, el ){
                            if(el.is($this)){
                                $found = true;
                            }
                        });
                        if(!$found){
                            foundElements.push($this);
                        }
                    }
                });
            });
            // Update fields attribute and remove all {tags} which where removed/deleted from the DOM
            for (i = 0; i < foundElements.length; ++i) {
                dataFields = foundElements[i][0].dataset.fields;
                $.each(removedFields, function( index ) {
                    dataFields = dataFields.replace('{'+index+'}','');
                });
                foundElements[i][0].dataset.fields = dataFields;
            }
            SUPER.init_replace_html_tags({el: undefined, form: form});

            // Because we must trigger field change, we can not delete the nodes just yet
            // Instead we make this dynamic column hidden, and then trigger field change hook
            // This way the form thinks the fields do not exists (are hidden)
            // After that, we can remove the dynamic column
            parent.style.display = 'none';

            // @IMPORTANT
            // The below hook should come before the field change hook
            // This is because it will update the fields attribute on for instance the calculator element
            // If this hook is placed below the field change hook it would cause incorrect results
            SUPER.after_duplicating_column_hook(form, removedFields);

            // Update conditional logic and other variable fields based on the removed fields in this dynamic column
            Object.keys(removedFields).forEach(function(index) {
                SUPER.after_field_change_blur_hook({el: removedFields[index], form: form});
            });

            // Now we can remove the dynamic column
            parent.remove();

            // Reload google maps
            SUPER.google_maps_api.initMaps(undefined, form);
            
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
        var autosuggestTimeout = null;
        $doc.on('keyup', '.super-auto-suggest .super-shortcode-field', function () {
            var i,
                el = this,
                parent = el.closest('.super-field'),
                itemsToShow = [],
                itemsToHide = [],
                value,
                text = '',
                searchValue,
                regex,
                stringBold,
                wrapper = el.closest('.super-field-wrapper'),
                nodes = wrapper.querySelectorAll('.super-dropdown-ui .super-item');

            if (autosuggestTimeout !== null) clearTimeout(autosuggestTimeout);
            autosuggestTimeout = setTimeout(function () {
                value = el.value.toString();
                if (value === '') {
                    parent.classList.remove('super-string-found');
                    return false;
                }
                for (i = 0; i < nodes.length; i++) {
                    searchValue = nodes[i].dataset.searchValue.toString();
                    text = searchValue.split(';')[0];
                    if (searchValue.toLowerCase().indexOf(value.toLowerCase()) !== -1) {
                        itemsToShow.push(nodes[i]);
                        regex = RegExp([value].join('|'), 'gi');
                        stringBold = text.replace(regex, '<span>$&</span>');
                        stringBold = stringBold.replace(/\r?\n|\r/g, "");
                        nodes[i].innerHTML = stringBold;
                    }else{
                        itemsToHide.push(nodes[i]);
                    }
                }
                [].forEach.call(itemsToShow, function (el) {
                    el.style.display = 'block';
                });
                [].forEach.call(itemsToHide, function (el) {
                    el.style.display = 'none';
                });
                if (itemsToShow.length>0) {
                    parent.classList.add('super-string-found');
                    parent.classList.add('super-focus');
                } else {
                    parent.classList.remove('super-string-found');
                }
            }, 250);
        });

        // Focus dropdowns
        $doc.on('click', '.super-dropdown-ui:not(.super-autosuggest-tags-list)', function(e){
            var i, nodes, field = e.target.closest('.super-field');
            if(e.target.classList.contains('super-placeholder')){
                if(field.classList.contains('super-focus-dropdown')){
                    field.classList.remove('super-focus');
                    field.classList.remove('super-focus-dropdown');
                    return false;
                }
            }
            if(!field.classList.contains('super-focus-dropdown')){
                nodes = document.querySelectorAll('.super-focus');
                for(i=0; i<nodes.length; i++){
                    nodes[i].classList.remove('super-focus');
                    nodes[i].classList.remove('super-focus-dropdown');
                }
                nodes = document.querySelectorAll('.super-focus-dropdown');
                for(i=0; i<nodes.length; i++){
                    nodes[i].classList.remove('super-focus-dropdown');
                }
                field.classList.add('super-focus');
                field.classList.add('super-focus-dropdown');
                var searchField = e.target.closest('.super-field-wrapper').querySelector('input[name="super-dropdown-search"]');
                if(searchField) searchField.focus();
            }
        });
        // Unfocus dropdown
        document.addEventListener('click', function(e){
            if(!e.target.closest('.super-dropdown-ui') && !e.target.classList.contains('super-dropdown-ui')){
                var nodes = document.querySelectorAll('.super-focus-dropdown');
                for(var i = 0; i < nodes.length; i++){
                    nodes[i].classList.remove('super-focus-dropdown');
                    nodes[i].classList.remove('super-focus');
                }
            }
        });
        
        // @since 1.2.8     - filter dropdown options based on keyboard press
        var timeout = null;
        $doc.on('keyup', 'input[name="super-dropdown-search"]', function(e){
            var i, nodes, el = this, stringValue, stringValue_l, words, regex, replacement, dropdownUI, stringBold, value, field, wrapper, found = false, firstFound=null, isMatch, keyCode = e.keyCode || e.which; 
            if( (keyCode == 13) || (keyCode == 40) || (keyCode == 38) ) {
                return false;
            }
            if (timeout !== null) clearTimeout(timeout);
            timeout = setTimeout(function () {
                el.value = '';
            }, 1000);

            value = el.value.toString().toLowerCase();
            field = el.closest('.super-field');
            wrapper = el.closest('.super-field-wrapper');
            if( value==='' ) {
                field.classList.remove('super-string-found');
            }else{
                nodes = wrapper.querySelectorAll('.super-dropdown-ui .super-item:not(.super-placeholder)');
                for(i=0; i<nodes.length; i++){
                    stringValue = nodes[i].dataset.searchValue.toString();
                    stringValue_l = stringValue.toLowerCase();
                    if(el.dataset.logic=='start'){
                        // Starts with filter logic:
                        isMatch = stringValue_l.startsWith(value);
                    }else{
                        // Contains filter logic:
                        isMatch = stringValue_l.indexOf(value) !== -1;
                    }
                    if( isMatch===true ) {
                        if( firstFound===null ) {
                            firstFound = nodes[i];
                        }
                        found = true;
                        words = [value]; 
                        regex = RegExp(words.join('|'), 'gi');
                        replacement = '<span>$&</span>';
                        stringBold = nodes[i].innerText.replace(regex, replacement);
                        stringBold = stringBold.replace(/\r?\n|\r/g, "");
                        nodes[i].innerHTML = stringBold;
                        nodes[i].classList.add('super-active');
                    }else{
                        nodes[i].innerHTML = stringValue;
                        nodes[i].classList.remove('super-active');
                    }
                }
                if( found===true ) {
                    nodes = field.querySelectorAll('.super-dropdown-ui .super-item.super-active');
                    for(i=0; i<nodes.length; i++){
                        nodes[i].classList.remove('super-active');
                    }
                    // If only one found, make it selected option by default
                    if(nodes.length===1){
                        field.classList.remove('super-string-found');
                    }else{
                        field.classList.add('super-string-found');
                    }
                    if(firstFound) firstFound.classList.add('super-active');
                    field.classList.add('super-focus');
                    dropdownUI = $(field).find('.super-dropdown-ui');
                    dropdownUI.scrollTop(dropdownUI.scrollTop() - dropdownUI.offset().top + $(firstFound).offset().top - 50); 
                }else{
                    // Nothing found, clear timeout
                    el.value = '';
                    field.classList.remove('super-string-found');
                }
            }
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
                    if($(this).closest('.super-form').hasClass('super-window-first-responsiveness') || $(this).closest('.super-form').hasClass('super-window-second-responsiveness') ){
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
        $doc.on('click', '.super-wc-order-search .super-field-wrapper:not(.super-overlap) .super-dropdown-ui .super-item, .super-auto-suggest .super-field-wrapper:not(.super-overlap) .super-dropdown-ui .super-item', function(){
            var i, items,
                wrapper = this.closest('.super-field-wrapper'),
                parent = this.parentNode,
                field = this.closest('.super-field'),
                value = this.innerText,
                populate = wrapper.querySelector('.super-shortcode-field').dataset.wcosp;

            items = parent.querySelectorAll('.super-item.super-active');
            for( i = 0; i < items.length; i++ ) {
                items[i].classList.remove('super-active');
            }
            this.classList.add('super-active');
            wrapper.querySelector('.super-shortcode-field').value = value;
            field.classList.remove('super-focus');
            field.classList.remove('super-string-found');
            wrapper.classList.add('super-overlap');
            wrapper.parentNode.classList.add('super-filled');
            SUPER.after_field_change_blur_hook({el: wrapper.querySelector('.super-shortcode-field')});
            if(populate=='true'){
                SUPER.populate_form_data_ajax(field);
            }
        });
        // On removing item
        $doc.on('click', '.super-wc-order-search .super-field-wrapper.super-overlap li, .super-auto-suggest .super-field-wrapper.super-overlap li', function(){
            var i,items,
                wrapper = this.closest('.super-field-wrapper'),
                field = wrapper.querySelector('.super-shortcode-field');
            
            field.value = '';
            items = wrapper.querySelectorAll('.super-active');
            for( i = 0; i < items.length; i++ ) {
                items[i].classList.remove('super-active');
            }
            wrapper.classList.remove('super-overlap');
            wrapper.parentNode.classList.remove('super-filled');
            field.focus();
            SUPER.after_field_change_blur_hook({el: field});
        });

        // Update autosuggest
        $doc.on('click', '.super-auto-suggest .super-field-wrapper:not(.super-overlap) .super-dropdown-ui .super-item', function(){
            var $this = $(this);
            var $field = $this.parents('.super-field:eq(0)');
            var $parent = $this.parent();
            var $value = $this.text();
            $parent.find('li').removeClass('super-active');
            $(this).addClass('super-active');
            $field.find('.super-shortcode-field').val($value);
            $field.removeClass('super-focus').removeClass('super-string-found');
            SUPER.after_field_change_blur_hook({el: $field.find('.super-shortcode-field')[0]});
        });

        // Update dropdown
        $doc.on('click', '.super-dropdown-ui .super-item:not(.super-placeholder)', function(e){
            var i, nodes,
                form,
                field = this.closest('.super-field'),
                input,
                wrapper,
                parent,
                placeholder,
                value,
                name,
                validation,
                max,
                min,
                total,
                names,
                values,
                counter;

            e.stopPropagation();
            if(field.classList.contains('super-focus-dropdown')){
                field.classList.remove('super-focus-dropdown');
                form = SUPER.get_frontend_or_backend_form({el: this});
                wrapper = this.closest('.super-field-wrapper');
                input = wrapper.querySelector('.super-shortcode-field');
                parent = this.closest('.super-dropdown-ui');
                placeholder = parent.querySelector('.super-placeholder');
                if(!parent.classList.contains('multiple')){
                    value = this.dataset.value;
                    name = this.innerHTML;
                    placeholder.innerHTML = name;
                    placeholder.dataset.value = value;
                    placeholder.classList.add('super-active');
                    nodes = parent.querySelectorAll('.super-item');
                    for ( i = 0; i < nodes.length; i++){
                        nodes[i].classList.remove('super-active');
                    }
                    this.classList.add('super-active');
                    input.value = value;
                    field.classList.remove('super-focus');
                }else{
                    max = input.dataset.maxlength;
                    min = input.dataset.minlength;
                    total = parent.querySelectorAll('.super-item.super-active:not(.super-placeholder').length;
                    if(this.classList.contains('super-active')){
                        if(total>1){
                            if(total <= min) return false;
                            this.classList.remove('super-active');    
                        }
                    }else{
                        if(total >= max) return false;
                        this.classList.add('super-active');    
                    }
                    names = '';
                    values = '';
                    nodes = parent.querySelectorAll('.super-item.super-active:not(.super-placeholder');
                    total = nodes.length;
                    counter = 1;
                    for ( i = 0; i < nodes.length; i++){
                        if((total == counter) || (total==1)){
                            names += nodes[i].innerHTML;
                            values += nodes[i].dataset.value;
                        }else{
                            names += nodes[i].innerHTML+',';
                            values += nodes[i].dataset.value+',';
                        }
                        counter++;
                    }
                    placeholder.innerHTML = names;
                    input.value = values;
                }
                if(input.value===''){
                    wrapper.classList.remove('super-filled');
                }else{
                    wrapper.classList.add('super-filled');
                }
                validation = input.dataset.validation;
                if(typeof validation !== 'undefined' && validation !== false){
                    SUPER.handle_validations({el: input, form: form, validation: validation});
                }
                SUPER.after_dropdown_change_hook({el: input});
            }
        });

        $doc.on('click','.super-back-to-top',function(){
            $('html, body').animate({
                scrollTop: 0
            }, 1000);
        });

        $doc.on('change', '.super-shortcode-field', function (e) {
            if(this.classList.contains('super-fileupload')) return false;
            var keyCode = e.keyCode || e.which; 
            if (keyCode != 9) { 
                var form = SUPER.get_frontend_or_backend_form({el: this}),
                    validation = this.dataset.validation,
                    conditionalValidation = this.dataset.conditionalValidation;
                SUPER.handle_validations({el: this, form: form, validation: validation, conditionalValidation: conditionalValidation});
                SUPER.after_field_change_blur_hook({el: this});
            }
        });

        $doc.on('click', '.super-form .super-radio > .super-field-wrapper .super-item', function (e) {
            var $form,$this,$parent,$field,$active,$validation;
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
                $form = SUPER.get_frontend_or_backend_form({el: this});
                $this = this.querySelector('input[type="radio"]');
                if(this.classList.contains('super-active')) return true;
                $parent = this.closest('.super-field-wrapper');
                $field = $parent.querySelector('.super-shortcode-field');
                $active = $parent.querySelector('.super-item.super-active');
                if($active) $active.classList.remove('super-active');
                this.classList.add('super-active');
                $validation = $field.dataset.validation;
                $field.value = $this.value;
                if(typeof $validation !== 'undefined' && $validation !== false){
                    SUPER.handle_validations({el: $field, form: $form, validation: $validation});
                }
                SUPER.after_radio_change_hook({el: $field});
            }
            return false;
        });

        $doc.on('click', '.super-form .super-checkbox > .super-field-wrapper .super-item', function (e) {
            var $form,
                $checked,
                $value,
                $checkbox,
                $parent,
                $field,
                $counter,
                $maxlength,
                $validation;

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
                $form = SUPER.get_frontend_or_backend_form({el: this});
                $checkbox = this.querySelector('input[type="checkbox"]');
                $parent = $checkbox.closest('.super-field-wrapper');
                $field = $parent.querySelector('input[type="hidden"]');
                $counter = 0;
                $maxlength = $parent.querySelector('.super-shortcode-field').dataset.maxlength;
                if(this.classList.contains('super-active')){
                    this.classList.remove('super-active');
                }else{
                    $checked = $parent.querySelectorAll('label.super-active');
                    if($checked.length >= $maxlength){
                        return false;
                    }
                    this.classList.add('super-active');
                }
                $checked = $parent.querySelectorAll('label.super-active');
                $value = '';
                for (var i = 0; i < $checked.length; ++i) {
                    if ($counter === 0) $value = $checked[i].querySelector('input').value;
                    if ($counter !== 0) $value = $value + ',' + $checked[i].querySelector('input').value;
                    $counter++;
                }
                $field.value = $value;
                $validation = $field.dataset.validation;
                if(typeof $validation !== 'undefined' && $validation !== false){
                    SUPER.handle_validations({el: $field, form: $form, validation: $validation});
                }
                SUPER.after_checkbox_change_hook({el: $field});
            }
            return false;
        });

        $doc.on('change', '.super-form select', function () {
            var $form = SUPER.get_frontend_or_backend_form({el: this}),
                $min = this.dataset.minlength,
                $max = this.dataset.maxlength,
                $validation;
            if(($min>0) && (this.value === null)){
                SUPER.handle_errors(this);
            }else if(this.value.length > $max){
                SUPER.handle_errors(this);
            }else if(this.value.length < $min){
                SUPER.handle_errors(this);
            }else{
                this.closest('.super-field').classList.remove('super-error-active');
            }
            $validation = this.dataset.validation;
            if(typeof $validation !== 'undefined' && $validation !== false){
                SUPER.handle_validations({el: this, form: $form, validation: $validation});
            }
            SUPER.after_dropdown_change_hook({el: this});
        });
        
        $doc.on('mouseleave','.super-button .super-button-wrap',function(){
            this.parentNode.classList.remove('super-focus');
            SUPER.init_button_colors( this );
        });
        $doc.on('mouseover','.super-button .super-button-wrap',function(){
            SUPER.init_button_hover_colors( this.parentNode );
        });
        

        function super_focus_first_tab_index_field(e, form, multipart) {
            var fields,highestIndex,lowestIndex,index,next,
                disableAutofocus = multipart.dataset.disableAutofocus;

            if( typeof disableAutofocus === 'undefined' ) {
                fields = $(multipart).find('.super-field:not('+super_elements_i18n.tab_index_exclusion+')[data-super-tab-index]');
                highestIndex = 0;
                fields.each(function(){
                    index = parseFloat($(this).attr('data-super-tab-index'));
                    if( index > highestIndex ) {
                        highestIndex = index;
                    }
                });
                lowestIndex = highestIndex;
                fields.each(function(){
                    index = parseFloat($(this).attr('data-super-tab-index'));
                    if( index < lowestIndex ) {
                        lowestIndex = index;
                    }
                });
                next = $(multipart).find('.super-field:not('+super_elements_i18n.tab_index_exclusion+')[data-super-tab-index="'+lowestIndex+'"]');
                SUPER.super_focus_next_tab_field(e, next[0], form, next[0]);
            }
        }

        // @since 3.3.0 - make sure to skip the multi-part if no visible elements are found
        function super_skip_multipart(el, form, index, activeIndex){
            var i, nodes, multipart, field, skip = true;
            
            nodes = form.querySelectorAll('.super-multipart.super-active .super-field:not(.super-button)');
            for( i = 0; i < nodes.length; i++ ) {
                field = nodes[i].querySelector('.super-shortcode-field');
                if(field){ // This is a field
                    if(!SUPER.has_hidden_parent(field)) skip = false;
                }else{ // This is either a HTML element or something else without a field
                    if(!SUPER.has_hidden_parent(nodes[i])) skip = false;
                }
            }
            if(skip){ // Only skip if no visible fields where to be found
                multipart = form.querySelector('.super-multipart.super-active');
                if( (el.classList.contains('super-prev-multipart')) || (el.classList.contains('super-next-multipart')) ){
                    if(el.classList.contains('super-prev-multipart')){
                        if(multipart.querySelector('.super-prev-multipart')) multipart.querySelector('.super-prev-multipart').click();
                    }else{
                        if(multipart.querySelector('.super-next-multipart')) multipart.querySelector('.super-next-multipart').click();
                    }
                }else{
                    if(index < activeIndex){
                        if(multipart.querySelector('.super-prev-multipart')) multipart.querySelector('.super-prev-multipart').click();
                    }else{
                        if(multipart.querySelector('.super-next-multipart')) multipart.querySelector('.super-next-multipart').click();
                    }
                }
            }
            return skip;
        }


        // Multi Part Columns
        $doc.on('click','.super-multipart-step',function(e){
            var i, nodes,
                el = this,
                form = el.closest('.super-form'),
                currentActive = form.querySelector('.super-multipart.super-active'),          
                currentActiveTab = form.querySelector('.super-multipart-step.super-active'),
                activeChildren = Array.prototype.slice.call( currentActiveTab.parentNode.children ),
                activeIndex = activeChildren.indexOf(currentActiveTab),
                clickedChildren = Array.prototype.slice.call( el.parentNode.children ),
                index = clickedChildren.indexOf(el),
                total = form.querySelectorAll('.super-multipart').length,
                validate,
                result,
                progress,
                multipart,
                skip;

            // @since 2.0.0 - validate multi-part before going to next step
            if(activeIndex < index){ // Always allow going to previous step
                validate = currentActive.dataset.validate;
                if(validate=='true'){
                    result = SUPER.validate_form({el: el, form: currentActive, submitButton: el, validateMultipart: true, event: e});
                    if(result!==true) return false;
                }
            }
            progress = 100 / total;
            progress = progress * (index+1);
            multipart = form.querySelectorAll('.super-multipart')[index];
            if(form.querySelector('.super-multipart-progress-bar')){
                form.querySelector('.super-multipart-progress-bar').style.width = progress+'%';
            }
            nodes = form.querySelectorAll('.super-multipart-step');
            for ( i = 0; i < nodes.length; i++){
                nodes[i].classList.remove('super-active');
            }
            nodes = form.querySelectorAll('.super-multipart');
            for ( i = 0; i < nodes.length; i++){
                nodes[i].classList.remove('super-active');
            }
            multipart.classList.add('super-active');
            el.classList.add('super-active');

            // @since 3.3.0 - make sure to skip the multi-part if no visible elements are found
            skip = super_skip_multipart(el, form, index, activeIndex);
            if(skip===true) return false;

            // Focus first TAB index field in next multi-part
            super_focus_first_tab_index_field(e, form, multipart);

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
        $doc.on('click', '.super-i18n-switcher .super-dropdown-items > .super-item', function(){
            var $this = $(this),
                $form = $this.closest('.super-form'),
                $form_id = $form.find('input[name="hidden_form_id"]').val(),
                $i18n = $this.attr('data-value');

            $this.parent().children('.super-item').removeClass('super-active');
            $this.addClass('super-active');
            // Also move to placeholder
            $this.parents('.super-dropdown').children('.super-dropdown-placeholder').html($this.html());
            
            // Remove initialized class
            $form.find('form').html('');
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
                    $form.find('form').html(data.html);
                },
                complete: function(){
                    $form.addClass('super-initialized');
                    SUPER.init_common_fields();
                    SUPER.init_replace_html_tags({el: undefined, form: $form[0]});
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    // eslint-disable-next-line no-console
                    console.log(xhr, ajaxOptions, thrownError);
                    alert('Failed to process data, please try again');
                }
            });
            
        });


        // Multi Part Next Prev Buttons
        $doc.on('click','.super-prev-multipart, .super-next-multipart',function(e){
            var i,nodes,
                index,
                el = this,
                form = el.closest('.super-form'),
                total = form.querySelectorAll('.super-multipart').length,
                current = form.querySelector('.super-multipart-step.super-active'),
                children = Array.prototype.slice.call( current.parentNode.children ),
                current_step = children.indexOf(current),
                validate,
                result,
                skip,
                progress,
                multipart;

            if(el.classList.contains('super-prev-multipart')){
                if(current_step>0){
                    nodes = form.querySelectorAll('.super-multipart');
                    for( i = 0; i < nodes.length; i++){
                        nodes[i].classList.remove('super-active');
                        if(i==(current_step-1)) nodes[i].classList.add('super-active');
                    }
                    nodes = form.querySelectorAll('.super-multipart-step');
                    for( i = 0; i < nodes.length; i++){
                        nodes[i].classList.remove('super-active');
                        if(i==(current_step-1)) nodes[i].classList.add('super-active');
                    }
                    index = current_step-1;
                }
            }else{
                // @since 2.0.0 - validate multi-part before going to next step
                validate = form.querySelector('.super-multipart.super-active').dataset.validate;
                if(validate=='true'){
                    result = SUPER.validate_form({el: el, form: form.querySelector('.super-multipart.super-active'), submitButton: el, validateMultipart: true, event: e});
                    if(result!==true) return false;
                }
                if(total>current_step+1){
                    nodes = form.querySelectorAll('.super-multipart');
                    for( i = 0; i < nodes.length; i++){
                        nodes[i].classList.remove('super-active');
                        if(i==(current_step+1)) nodes[i].classList.add('super-active');
                    }
                    nodes = form.querySelectorAll('.super-multipart-step');
                    for( i = 0; i < nodes.length; i++){
                        nodes[i].classList.remove('super-active');
                        if(i==(current_step+1)) nodes[i].classList.add('super-active');
                    }
                    index = current_step+1;
                }
            }

            // @since 3.3.0 - make sure to skip the multi-part if no visible elements are found
            skip = super_skip_multipart(el, form);
            if(skip===true) return false;

            progress = 100 / total;
            progress = progress * (index+1);
            form.querySelector('.super-multipart-progress-bar').style.width = progress+'%';
            index = 0;
           
            nodes = form.querySelectorAll('.super-multipart');
            for( i = 0; i < nodes.length; i++){
                if(!nodes[i].querySelector('.super-error-active')){
                    form.querySelectorAll('.super-multipart-steps .super-multipart-step')[index].classList.remove('super-error');
                }
                index++;
            }
            
            // @since 4.3.0 - disable scrolling for multi-part prev/next
            multipart = form.querySelector('.super-multipart.super-active');
            if(typeof multipart.dataset.disableScrollPn === 'undefined'){
                $('html, body').animate({
                    scrollTop: $(form).offset().top - 30
                }, 500);
            }

            // Focus first TAB index field in next multi-part
            super_focus_first_tab_index_field(e, form, multipart);

        });

        // @since 4.9.3 - Adaptive Placeholders
        var adaptivePlaceholder = document.querySelectorAll('.super-adaptive-placeholder');
        for (var i = 0; i < adaptivePlaceholder.length; i++) {
            var input = adaptivePlaceholder[i].parentNode.querySelector('.super-shortcode-field');
            if(!input) continue;

            input.onclick = input.onfocus = function () {
                this.closest('.super-shortcode').classList.add('super-focus');
            }
            input.onblur = function () {
                if(!this.closest('.super-shortcode').classList.contains('super-string-found')){
                    this.closest('.super-shortcode').classList.remove('super-focus');
                }
            }
            input.addEventListener('keyup', function () {
                var filled = true,
                    parent = this.closest('.super-shortcode');
                if(parent.classList.contains('super-currency')){
                    if($(this).maskMoney('unmasked')[0]===0){
                    filled = false;
                    }
                }
                if (this.value.length === 0) filled = false;
                if(filled){
                    parent.classList.add('super-filled');
                }else{
                    parent.classList.remove('super-filled');
                }
            });
            input.oncut = input.onpaste = function () {
                var filled = true,
                    input = event.target,
                    parent = event.target.closest('.super-shortcode');

                if(parent.classList.contains('super-currency')){
                    if($(input).maskMoney('unmasked')[0]===0){
                        filled = false;
                    }
                }
                if (event.type == 'cut' || event.type == 'paste') {
                    setTimeout(function () {
                        if (input.value.length === 0) filled = false;
                        if(filled){
                            parent.classList.add('super-filled');
                        }else{
                            parent.classList.remove('super-filled');
                        }
                    }, 100);
                }
            }
        }
    });

})(jQuery);

(function () {
    "use strict"; // Minimize mutable state :)
    // Define all the events for our elements
    var app = {};
    // querySelector shorthand
    app.q = function (s) {
        return document.querySelector(s);
    };
    // querySelectorAll shorthand
    app.qa = function (s) {
        return document.querySelectorAll(s);
    };
    // querySelectorAll based on parent shorthand
    app.qap = function (s, p) {
        // If no parent provided, default to Top element
        if (typeof p === 'undefined') p = app.wrapper;
        if (typeof p === 'string') p = app.wrapper.querySelector(p);
        return p.querySelectorAll(s);
    };
    // Remove all elements
    app.remove = function (e) {
        if (typeof e === 'undefined' || !e) return true;
        if (e.length) {
            for (var i = 0; i < e.length; i++) {
                e[i].remove();
            }
        } else {
            e.remove();
        }
        return true;
    };
    // Remove class from elements
    app.removeClass = function (elements, class_name) {
        if (elements.length === 0) return true;
        if (elements.length) {
            for (var key = 0; key < elements.length; key++) {
                elements[key].classList.remove(class_name);
            }
        } else {
            elements.classList.remove(class_name);
        }
    };
    // Add class from elements
    app.addClass = function (elements, class_name) {
        if (elements.length === 0) return true;
        if (elements.length) {
            for (var key = 0; key < elements.length; key++) {
                elements[key].classList.add(class_name);
            }
        } else {
            elements.classList.add(class_name);
        }
    };
    // Get index of element based on parent node
    app.index = function (node, class_name) {
        var index = 0;
        while (node.previousElementSibling) {
            node = node.previousElementSibling;
            // Based on specified class name
            if (class_name) {
                if (node.classList.contains(class_name)) {
                    index++;
                }
            } else {
                index++;
            }

        }
        return index;
    };
    // Check if clicked inside element, by looping over it's "path"
    app.inPath = function (e, class_name) {
        if (!e.path) return false;
        var found = false;
        Object.keys(e.path).forEach(function (key) {
            if (e.path[key].classList) {
                if (e.path[key].classList.contains(class_name)) {
                    found = true;
                }
            }
        });
        return found;
    };

    app.focusField = function(e, target){
        app.removeClass(app.qa('.super-focus'), 'super-focus');
        target.closest('.super-field').classList.add('super-focus');
    };
    app.unfocusField = function(e, target){
        app.removeClass(app.qa('.super-focus'), 'super-focus');
        target.closest('.super-field').classList.remove('super-focus');
    };
    app.autosuggestTagsTimeout = null;
    app.keywords = {
        updateValue: function(field, tagsContainer, keywordField, filterField, wrapper){
            var i, values=[], nodes = wrapper.querySelectorAll('.super-autosuggest-tags > div > span');
            for(i=0; i<nodes.length; i++){
                values.push(nodes[i].dataset.value);
            }
            keywordField.value = values.join(',');
            filterField.value = '';
            filterField.focus();
            if(!app.qap('span', tagsContainer).length){
                field.classList.remove('super-filled');
            }else{
                field.classList.add('super-filled');
            }
            // Scroll to bottom of tags container
            tagsContainer.scrollTop = tagsContainer.scrollHeight;
            SUPER.after_field_change_blur_hook({el: keywordField});
        },
        add: function(e, target){
            var i,
                html = '',
                field = target.closest('.super-field'),
                value = target.dataset.value, // first_choice
                searchValue = target.dataset.searchValue, // First choice
                wrapper = target.closest('.super-field-wrapper'),
                keywordField = wrapper.querySelector('.super-shortcode-field'),
                tagsContainer = wrapper.querySelector('.super-autosuggest-tags > div'),
                tags = tagsContainer.querySelectorAll('.super-keyword-tag'),
                filterField = wrapper.querySelector('.super-keyword-filter'),
                maxlength = parseInt(keywordField.dataset.maxlength, 10),
                existingTags = [];

            // Check if limit is reached
            if( maxlength!==0 && tags.length>=maxlength ) {
                field.classList.add('super-filled');
                field.classList.remove('super-string-found');
                field.classList.add('super-focus');
                app.keywords.updateValue(field, tagsContainer, keywordField, filterField, wrapper);
                return false; 
            }
            // Loop over allready existing tags and add their value to an array
            for(i=0; i < tags.length; i++){
                existingTags.push(tags[i].dataset.value);
            }
            // if this tag doesn't exists yet
            if(existingTags.indexOf(value)===-1){
                html = '<span class="super-noselect super-keyword-tag" sfevents=\'{"click":"keywords.remove"}\' data-value="'+value+'" title="remove this tag">'+searchValue+'</span>';
                tagsContainer.innerHTML = tagsContainer.innerHTML + html;
                target.classList.add('super-active');
            }
            if(!app.qap('span', tagsContainer).length){
                field.classList.remove('super-filled');
            }else{
                field.classList.add('super-filled');
            }
            field.classList.remove('super-string-found');
            field.classList.add('super-focus');
            app.keywords.updateValue(field, tagsContainer, keywordField, filterField, wrapper);
        },
        remove: function(e, target){
            var wrapper = target.closest('.super-field-wrapper'),
                keywordField = wrapper.querySelector('.super-shortcode-field'),
                filterField = wrapper.querySelector('.super-keyword-filter'),
                field = target.closest('.super-field'),
                tagsContainer = wrapper.querySelector('.super-autosuggest-tags > div');

            target.remove();
            filterField.focus();
            app.keywords.updateValue(field, tagsContainer, keywordField, filterField, wrapper);
        },
        filter: function(e, target){
            // On keyup filter any keyword tags from the list
            var i,
                parent = target.closest('.super-field'),
                counter = 0,
                html = '',
                tag, tags,
                duplicates = {},
                method = target.dataset.method,
                splitMethod = target.dataset.splitMethod,
                itemsToShow = [],
                itemsToHide = [],
                value,
                text = '',
                searchValue,
                regex,
                stringBold,
                wrapper = target.closest('.super-field-wrapper'),
                field = target.closest('.super-field'),
                tagsContainer = wrapper.querySelector('.super-autosuggest-tags > div'),
                keywordField = wrapper.querySelector('.super-shortcode-field'),
                max = parseInt(keywordField.dataset.maxlength, 10),
                nodes = wrapper.querySelectorAll('.super-dropdown-ui .super-item');

            if(method=='free'){
                if(splitMethod=='both') tags = target.value.split(/[ ,]+/);
                if(splitMethod=='comma') tags = target.value.split(/[,]+/);
                if(splitMethod=='space') tags = target.value.split(/[ ]+/);
                if(tags.length>1){
                    tag = tags[0];
                    // First check if already exists
                    if(keywordField.value.split(',').indexOf(tag)===-1){
                        if(typeof duplicates[tag]==='undefined'){
                            counter++;
                            if(counter<=max){
                                if(splitMethod!='comma') tag = tag.replace(/ /g,'');
                                if( (tag!=='') && (tag.length>1) ) {
                                    html += '<span class="super-noselect super-keyword-tag" sfevents=\'{"click":"keywords.remove"}\' data-value="'+tag+'" title="remove this tag">'+tag+'</span>';
                                }
                            }
                        }
                        duplicates[tag] = tag;
                        tagsContainer.innerHTML = tagsContainer.innerHTML + html;
                    }
                    app.keywords.updateValue(field, tagsContainer, keywordField, target, wrapper);
                }
            }else{
                if (app.autosuggestTagsTimeout !== null) clearTimeout(app.autosuggestTagsTimeout);
                app.autosuggestTagsTimeout = setTimeout(function () {
                    value = target.value.toString();
                    if (value === '') {
                        parent.classList.remove('super-string-found');
                        return false;
                    }
                    for (i = 0; i < nodes.length; i++) {
                        searchValue = nodes[i].dataset.searchValue.toString();
                        text = searchValue.split(';')[0];
                        if (searchValue.toLowerCase().indexOf(value.toLowerCase()) !== -1) {
                            itemsToShow.push(nodes[i]);
                            regex = RegExp([value].join('|'), 'gi');
                            stringBold = '<span class="super-wp-tag">'+text.replace(regex, '<span>$&</span>')+'</span>';
                            stringBold = stringBold.replace(/\r?\n|\r/g, "");
                            nodes[i].innerHTML = stringBold;
                        }else{
                            itemsToHide.push(nodes[i]);
                        }
                    }
                    [].forEach.call(itemsToShow, function (el) {
                        el.style.display = 'inline-block';
                        el.classList.add('super-active');
                    });
                    [].forEach.call(itemsToHide, function (el) {
                        el.style.display = 'none';
                        el.classList.remove('super-active');
                    });
                    if (itemsToShow.length>0) {
                        parent.classList.add('super-string-found');
                        parent.classList.add('super-focus');
                    } else {
                        parent.classList.remove('super-string-found');
                    }
                }, 250);
            }
        }
    }

    // Trigger Events
    app.triggerEvent = function (e, target, eventType) {
        // Get element actions, and check for multiple event methods
        var actions, _event, _function, _currentFunc, sfevents;
        try {
            sfevents = JSON.parse(target.attributes.sfevents.value);
        } catch (error) {
            // eslint-disable-next-line no-console
            console.log(error);
            alert(error);
        }
        Object.keys(sfevents).forEach(function (key) {
            // Check if contains comma, meaning it has multiple event methods for this function
            _event = key.split(',');
            if (_event.length > 1) {
                // Seems it contains multiple events, so let's split them up and create a new sfevents object
                Object.keys(_event).forEach(function (e_key) {
                    sfevents[_event[e_key]] = sfevents[key];
                });
                delete sfevents[key];
            }
        });
        actions = sfevents[eventType];
        if (actions) {
            if(typeof actions === 'string'){
                _currentFunc = app;
                if(actions.split('.').length>1){
                    _function = actions.split('.');
                    for (var i = 0; i < _function.length; i++) {
                        // Skip if function name is 'app'
                        if (_function[i] == 'app') continue;
                        if (_currentFunc[_function[i]]) {
                            _currentFunc = _currentFunc[_function[i]];
                        } else {
                            // eslint-disable-next-line no-console
                            console.log('Function ' + actions + '() is undefined!');
                            break;
                        }
                    }
                    _currentFunc(e, target, eventType, actions);
                }else{
                    if (_currentFunc[actions]) {
                        _currentFunc = _currentFunc[actions];
                    } else {
                        // eslint-disable-next-line no-console
                        console.log('Function ' + actions + '() is undefined!');
                    }
                    _currentFunc(e, target, eventType, actions);
                }
            }else{
                Object.keys(actions).forEach(function (key) { // key = function name
                    _currentFunc = app;
                    _function = key.split('.');
                    for (var i = 0; i < _function.length; i++) {
                        // Skip if function name is 'app'
                        if (_function[i] == 'app') continue;
                        if (_currentFunc[_function[i]]) {
                            _currentFunc = _currentFunc[_function[i]];
                        } else {
                            // eslint-disable-next-line no-console
                            console.log('Function ' + key + '() is undefined!');
                            break;
                        }
                    }
                    _currentFunc(e, target, eventType, actions[key]);
                });
            }
        }
    };
    // Equivalent for jQuery's .on() function
    app.delegate = function (element, event, elements, callback) {
        element.addEventListener(event, function (event) {
            var target = event.target;
            while (target && target !== this) {
                if (target.matches(elements)) {
                    callback(event, target);
                    return false;
                }
                target = target.parentNode;
            }
        });
    };
    // Iterate over all events, and listen to any event being triggered
    app.events = {
        click: [
            'body',
            '.super-keyword-filter',
            '.super-keyword-tags .super-item',
            '.super-keyword-tag'
        ],
        mousedown: [
            '.super-keyword-filter'
        ],
        onblur: [
            '.super-keyword-filter'
        ],
        keyup: [
            '.super-keyword-filter'
        ],
        keydown: [
            '.super-keyword-filter'
        ]
    };
    Object.keys(app.events).forEach(function (eventType) {
        var elements = app.events[eventType].join(", ");
        app.delegate(document, eventType, elements, function (e, target) {
            if (eventType == 'click') {
                if (!app.inPath(e, 'super-focus')) {
                    var i, nodes = document.querySelectorAll('.super-focus');
                    for(i=0; i < nodes.length; i++){
                        nodes[i].classList.remove('super-focus');
                    }
                }
            }
            // Trigger event(s)
            if (typeof target.attributes.sfevents !== 'undefined') app.triggerEvent(e, target, eventType);
        });
    });

})();
