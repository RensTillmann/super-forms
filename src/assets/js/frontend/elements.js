/* globals jQuery, SUPER, super_elements_i18n */
"use strict";
(function($) { // Hide scope, no $ conflict
    // Switch between multiparts (prev/next or clicking on step)
    SUPER.switchMultipart = function(e, target, dir){
        // First get active part
        var i, index, validate, result, skip, progress, multipart,
            form = target.closest('.super-form'),
            stepParams = (form.dataset.stepParams ? form.dataset.stepParams : ''), // default
            nodes = form.querySelectorAll('.super-multipart'),
            steps = form.querySelectorAll('.super-multipart-step'),
            formId = form.querySelector('input[name="hidden_form_id"]').value,
            activeMultipart = form.querySelector('.super-multipart-step.super-active'),
            children = Array.prototype.slice.call(activeMultipart.parentNode.children),
            total = form.querySelectorAll('.super-multipart').length,
            currentStep = children.indexOf(activeMultipart);

        if(dir==='prev'){
            // From first to last?
            if(currentStep===0){
                index = nodes.length-1;
            }else{
                index = currentStep-1;
            }
        }
        if(dir==='next'){
            // From last to first?
            if(total===currentStep+1){
                index = 0;
            }else{
                // @since 2.0.0 - validate multi-part before going to next step
                validate = form.querySelector('.super-multipart.super-active').dataset.validate;
                if(validate=='true'){
                    result = SUPER.validate_form({el: target, form: form.querySelector('.super-multipart.super-active'), submitButton: target, validateMultipart: true, event: e});
                    if(result!==true) return false;
                }
                index = currentStep+1;
            }
        }
        for( i = 0; i < nodes.length; i++){
            nodes[i].classList.remove('super-active');
            steps[i].classList.remove('super-active');
            if(i===index){
                nodes[i].classList.add('super-active');
                steps[i].classList.add('super-active');
            }
        }
        
        // Required for toggle field and other possible elements to rerender proper size
        SUPER.init_super_responsive_form_fields({form: form});

        // Update URL parameters
        if(stepParams!=='false'){
            window.location.hash = 'step-'+formId+'-'+(parseInt(index,10)+1);
        }
        
        // Make sure to skip the multi-part if no visible elements are found
        skip = SUPER.skipMultipart(target, form);
        if(skip===true) return false;

        // Update progress bar
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
        // Disable scrolling for multi-part prev/next
        multipart = form.querySelector('.super-multipart.super-active');
        if(typeof multipart.dataset.disableScrollPn === 'undefined'){
            if(e && !e.shiftKey){
                if(form.closest('.super-popup-content')){
                    $(form.closest('.super-popup-content')).animate({
                        scrollTop: $(form).offset().top - 30
                    }, 500);
                }else{
                    $('html, body').animate({
                        scrollTop: $(form).offset().top - 30
                    }, 500);
                }
            }
        }
        // Focus first TAB index field in next multi-part
        SUPER.focusFirstTabIndexField(e, form, multipart);

    };
    // Make sure to skip the multi-part if no visible elements are found
    SUPER.skipMultipart = function(el, form, index, activeIndex){
        var i, nodes, multipart, field, skip = true;
        
        nodes = form.querySelectorAll('.super-multipart.super-active .super-field:not(.super-button)');
        for( i = 0; i < nodes.length; i++ ) {
            field = nodes[i].querySelector('.super-shortcode-field');
            if(field){ // This is a field
                if(!SUPER.has_hidden_parent(field)) skip = false;
            }else{ // This is either a HTML element or something else without a field
                if(!SUPER.has_hidden_parent(nodes[i])) skip = false;
            }
            if(skip===false) return false;
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
    };
    SUPER.focusFirstTabIndexField = function(e, form, multipart) {
        var fields,highestIndex,lowestIndex,index,next, disableAutofocus = multipart.dataset.disableAutofocus;

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
            SUPER.focusNextTabField(e, next[0], form, next[0]);
        }
    };

    // @since 3.2.0 - function to return next field based on TAB index
    SUPER.nextTabField = function(e, field, form, nextTabIndex){
        if(!field) return false;
        var nextTabIndexSmallIncrement,
            nextField,
            nextFieldSmallIncrement,
            nextCustomField,
            customTabIndex,
            incNext = 1,
            incNextSmall = 0.001;

        if(e.shiftKey){
            incNext = -incNext;
            incNextSmall = -incNextSmall;
        }
        if(typeof nextTabIndex === 'undefined'){
            nextTabIndexSmallIncrement = parseFloat(parseFloat(field.dataset.superTabIndex)+incNextSmall).toFixed(3);
            nextTabIndex = parseFloat(field.dataset.superTabIndex)+incNext;
        }
        if(typeof field.dataset.superCustomTabIndex !== 'undefined'){
            nextTabIndex = parseFloat(field.dataset.superCustomTabIndex)+incNext;
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
        
        if(!nextField) return false;

        if(nextField.dataset.superCustomTabIndex){
            customTabIndex = nextField.dataset.superCustomTabIndex;
            if(typeof customTabIndex !== 'undefined') {
                if(nextTabIndex < parseFloat(customTabIndex)){
                    nextField = SUPER.nextTabField(e, field, form, nextTabIndex+incNext);
                }
            }
        }
        if(SUPER.has_hidden_parent(nextField)){
            // Exclude conditionally
            nextField = SUPER.nextTabField(e, field, form, nextTabIndex+incNext);
        }
        return nextField;
    };

    SUPER.init_adaptive_placeholder = function(){
        var nodes = document.querySelectorAll('.super-adaptive-placeholder');
        for (var i = 0; i < nodes.length; i++) {
            var input = nodes[i].parentNode.querySelector('.super-shortcode-field');
            if(!input) continue;

            input.onclick = input.onfocus = function () {
            }
            input.onblur = function () {
            }
            input.addEventListener('keyup', function () {
                var wrapper = this.closest('.super-field-wrapper');
                var placeholder = wrapper.querySelector('.super-adaptive-placeholder');
                var span = placeholder.children[0];
                var filled = true,
                    parent = this.closest('.super-field');
                if(parent.classList.contains('super-currency')){
                    if($(this).maskMoney('unmasked')[0]===0){
                        filled = false;
                    }
                }
                if (this.value.length === 0) filled = false;
                if(filled){
                    parent.classList.add('super-filled');
                    span.innerHTML = placeholder.dataset.placeholderfilled;
                }else{
                    parent.classList.remove('super-filled');
                    span.innerHTML = placeholder.dataset.placeholder;
                }
            });
            input.oncut = input.onpaste = function (event) {
                var wrapper = this.closest('.super-field-wrapper');
                var placeholder = wrapper.querySelector('.super-adaptive-placeholder');
                var span = placeholder.children[0];
                var filled = true,
                    input = event.target,
                    parent = event.target.closest('.super-field');

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
                            span.innerHTML = placeholder.dataset.placeholderfilled;
                        }else{
                            parent.classList.remove('super-filled');
                            span.innerHTML = placeholder.dataset.placeholder;
                        }
                    }, 100);
                }
            }
        }
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
            if(this.dataset.defaultValue!==''){
                $(this).maskMoney('mask', this.dataset.defaultValue);
                $(this).parents('.super-currency').addClass('super-filled');
            }else{
                $(this).parents('.super-currency').removeClass('super-filled');
            }
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
                        },
                        beforeShow: function(){
                            SUPER.focusForm(this);
                            SUPER.focusField(this);
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
    SUPER.init_connected_datepicker = function($this, selectedDate, $parse_format, oneDay, skipFieldChangeForElement){
        if(selectedDate===''){
            $this.closest('.super-field').classList.remove('super-filled');
        }else{
            $this.closest('.super-field').classList.add('super-filled');
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
                            var maxPicks =($this.dataset.maxpicks ? parseInt($this.dataset.maxpicks, 10) : 1);
                            if(maxPicks<=1){
                                if($connected_date.value===''){
                                    $connected_date.value = min_date;
                                }
                                if($connected_date.value===''){
                                    $connected_date.closest('.super-field').classList.remove('super-filled');
                                }else{
                                    $connected_date.closest('.super-field').classList.add('super-filled');
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
                            if($connected_date.value===''){
                                $connected_date.closest('.super-field').classList.remove('super-filled');
                            }else{
                                $connected_date.closest('.super-field').classList.add('super-filled');
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
    SUPER.init_datepicker = function(skipFieldChangeForElement){
        if(typeof skipFieldChangeForElement === 'undefined') skipFieldChangeForElement = ''
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
                regex = /{([^\\\/\s"'+]*?)}/g,
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
                
                // If localization is being used, use this date format instead
                // Make sure to convert to correct format before using, plus update data attribute
                if(localization!==''){
                    if(typeof $.datepicker.regional[localization] !== 'undefined'){
                        format = $.datepicker.regional[localization].dateFormat;
                        jsformat = format;
                        jsformat = jsformat.replace('DD', 'dddd');
                        regex = /MM/i;
                        if(regex.test(jsformat)){
                            jsformat = jsformat.replace('MM', 'MMMM');
                        }else{
                            regex = /M/i;
                            if(regex.test(jsformat)){
                                jsformat = jsformat.replace('M', 'MMM');
                            }
                        }
                        jsformat = jsformat.replace('mm', 'MM');
                        regex = /yy/i;
                        if(regex.test(jsformat)){
                            jsformat = jsformat.replace('yy', 'yyyy');
                        }else{
                            regex = /y/i;
                            if(regex.test(jsformat)){
                                jsformat = jsformat.replace('y', 'yy');
                            }
                        }
                        parseFormat = [
                            jsformat
                        ];
                        el.dataset.format = format;
                        el.dataset.jsformat = jsformat;
                    }
                }

            // Check if range is valid value, if not set to default one
            if(range.indexOf(':')===-1){
                range = '-100:+5';
            }
            
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
                        SUPER.init_connected_datepicker(el, $date, parseFormat, oneDay, skipFieldChangeForElement);
                    }
                }
            }else{
                el.dataset.mathYear = '0';
                el.dataset.mathMonth = '0';
                el.dataset.mathDay = '0';
                el.dataset.mathDiff = '0';
                el.dataset.mathAge = '0';
            }

            var multiDatesClassName = 'super-datepicker-multidates',
                singleDatesClassName = 'super-datepicker-singledates';
            var options = {
                onClose: function( selectedDate ) {
                    SUPER.init_connected_datepicker(this, selectedDate, parseFormat, oneDay, skipFieldChangeForElement);
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
                                regex = /{([^\\\/\s"'+]*?)}/g;
                                exclDaysOverrideReplaced = SUPER.update_variable_fields.replace_tags({form: form, regex: regex, value: exclDaysOverride});
                                exclDaysOverrideReplaced = exclDaysOverrideReplaced.split("\n");
                                date = ('0' + dt.getDate()).slice(-2);
                                month = ('0' + (dt.getMonth()+1)).slice(-2);
                                fullDate = dt.getFullYear() + '-' + month + '-' + date;
                                var x;
                                for( x=0; x < exclDaysOverrideReplaced.length; x++ ) {
                                    if(exclDaysOverrideReplaced[x]==='') continue;
                                    // If excluding a fixed day of month
                                    if(exclDaysOverrideReplaced[x].length<=2){
                                        if(exclDaysOverrideReplaced[x]==day){
                                            return [true, ""];
                                        }
                                    }
                                    // If excluding a specific month
                                    if(exclDaysOverrideReplaced[x].length===3){
                                        if(exclDaysOverrideReplaced[x].toLowerCase()==dt.toString('MMM').toLowerCase()){
                                            return [true, ""];
                                        }
                                    }
                                    // If excluding a date range
                                    if(exclDaysOverrideReplaced[x].split(';').length>1){
                                        dateFrom = exclDaysOverrideReplaced[x].split(';')[0];
                                        dateTo = exclDaysOverrideReplaced[x].split(';')[1];
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
                                    if(exclDaysOverrideReplaced[x]==fullDate){
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
                        regex = /{([^\\\/\s"'+]*?)}/g;
                        exclDatesReplaced = SUPER.update_variable_fields.replace_tags({form: form, regex: regex, value: exclDates});
                        exclDatesReplaced = exclDatesReplaced.split("\n");
                        date = ('0' + dt.getDate()).slice(-2);
                        month = ('0' + (dt.getMonth()+1)).slice(-2);
                        fullDate = dt.getFullYear() + '-' + month + '-' + date;
                        var y;
                        for( y=0; y < exclDatesReplaced.length; y++ ) {
                            if(exclDatesReplaced[y]==='') continue;
                            // If excluding a fixed day of month
                            if(exclDatesReplaced[y].length<=2){
                                if(exclDatesReplaced[y]==day){
                                    return [false, "super-disabled-day"];
                                }
                            }
                            // If excluding a specific month
                            if(exclDatesReplaced[y].length===3){
                                if(exclDatesReplaced[y].toLowerCase()==dt.toString('MMM').toLowerCase()){
                                    return [false, "super-disabled-day"];
                                }
                            }
                            // If excluding a date range
                            if(exclDatesReplaced[y].split(';').length>1){
                                dateFrom = exclDatesReplaced[y].split(';')[0];
                                dateTo = exclDatesReplaced[y].split(';')[1];
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
                            if(exclDatesReplaced[y]==fullDate){
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
                    var maxPicks = (inst.settings.maxPicks ? inst.settings.maxPicks : 1);
                    widget = $(inst).datepicker('widget');
                    widget[0].classList.add('super-datepicker-dialog');
                    if(maxPicks<=1){
                        widget[0].classList.remove(multiDatesClassName);
                        widget[0].classList.add(singleDatesClassName);
                        $('.super-datepicker[data-connected-min="'+$(this).attr('name')+'"]').each(function(){
                            if($(this).val()!==''){
                                connectedMinDays = $(this).data('connected-min-days');
                                minDate = Date.parseExact($(this).val(), parseFormat).add({ days: connectedMinDays }).toString(jsformat);
                                $(el).datepicker('option', 'minDate', minDate );
                            }
                        });
                    }else{
                        widget[0].classList.add(multiDatesClassName);
                        widget[0].classList.remove(singleDatesClassName);
                    }
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
            
            // Do a try catch because otherwise user might be locked out from the builder page if they made a mistake in entering for instance the "range" (yearRange)
            try {
                if(maxPicks>1){
                    options.maxPicks = maxPicks;
                    $(el).multiDatesPicker(options);
                    // @since 4.9.583 - Fixes issue where the month would change back to January after selecting a second date or more
                    $.datepicker._selectDateOverload = $.datepicker._selectDate;
                    $.datepicker._selectDate = function (id, dateStr) {
                        var target = $(id);
                        var inst = this._getInst(target[0]);
                        inst.inline = true;
                        $.datepicker._selectDateOverload(id, dateStr);
                        inst.inline = false;
                        if (target[0].multiDatesPicker != null) {
                            target[0].multiDatesPicker.changed = false;
                        } else {
                            target.multiDatesPicker.changed = false;
                        }
                        this._updateDatepicker(inst);
                        // Close datepicker if it isn't a so called Multi-datepicker, or when maxPicks is set to 1
                        if(typeof inst.settings.maxPicks==='undefined' || inst.settings.maxPicks<=1 ){
                            $(target).datepicker('hide');
                        }
                    };
                }else{
                    $(el).datepicker(options);
                }
                if(typeof min !== 'undefined'){ 
                    $(el).datepicker('option', 'minDate', min );
                }

                // @since 4.9.3 - Datepicker localization (language and format)
                if(localization!==''){
                    if(typeof $.datepicker.regional[localization] !== 'undefined'){
                        $.datepicker.regional[localization].yearSuffix = '';
                        $(el).datepicker( "option", $.datepicker.regional[localization] );
                    }
                }
            } catch (error) {
                // eslint-disable-next-line no-console
                console.log(error);
                alert(error);
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
                form = SUPER.get_frontend_or_backend_form({el: this}),
                regex = /{([^\\\/\s"'+]*?)}/g,
                $is_rtl = form.classList.contains('super-rtl'),
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
            if(typeof min !== 'undefined') {
                min = min.toString();
                min = SUPER.update_variable_fields.replace_tags({form: form, regex: regex, value: min});
                if(min.indexOf(':')===-1){
                    if(min !== parseInt(min, 10)){
                        min = parseInt(min, 10);
                        var prevMin = min - (min % (step*60));
                        min = prevMin + 1800;
                    }
                }
            }
            if(typeof max !== 'undefined') {
                max = max.toString();
                max = SUPER.update_variable_fields.replace_tags({form: form, regex: regex, value: max});
                if(max.indexOf(':')===-1){
                    if(max !== parseInt(max, 10)){
                        max = parseInt(max, 10);
                        var prevMax = max - (max % (step*60));
                        max = prevMax + 1800;
                    }
                }
            }
            if((range!=='') && (typeof range !== 'undefined')){
                range = range.split('\n');
                $.each(range, function(key, value ) {
                    finalrange.push(value.split('|'));
                });
            }
            $form_id = form.id;
            $form_size = form.dataset.fieldSize;
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
                this.closest('.super-field').classList.remove('super-filled');
            }else{
                this.closest('.super-field').classList.add('super-filled');
            }
        });
        $('.super-timepicker').parent().find('.super-icon').on('click',function(){
            $(this).parent().find('.super-timepicker').timepicker('show');
        });
    };

    // Initialize button colors
    SUPER.init_button_colors = function( el ) {    
        var i, nodes, type, color, light, dark, font, fontHover, wrap, icon, btnName, btnNameIcon;

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
            icon = wrap.querySelector('.super-before');
            btnName = wrap.querySelector('.super-button-name');
            btnNameIcon = btnName.querySelector('i');
            if(type=='diagonal'){
                if(typeof color !== 'undefined'){
                    wrap.style.borderColor = color;
                }else{
                    wrap.style.borderColor = '';
                }
                if(typeof font !== 'undefined'){
                    if(icon) icon.style.color = font;
                    btnName.style.color = font;
                    if(btnNameIcon) btnNameIcon.style.color = font;
                }else{
                    if(icon) icon.style.color = '';
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
                    if(icon) icon.style.color = font;
                    btnName.style.color = font;
                    if(btnNameIcon) btnNameIcon.style.color = font;
                }else{
                    if(icon) icon.style.color = '';
                    btnName.style.color = '';
                    if(btnNameIcon) btnNameIcon.style.color = '';
                }
            }
            if(type=='2d'){
                wrap.style.backgroundColor = color;
                wrap.style.borderColor = light;
                if(icon) icon.style.color = font;
                btnName.style.color = font;
                if(btnNameIcon) btnNameIcon.style.color = font;
            }
            if(type=='3d'){
                wrap.style.backgroundColor = color;
                wrap.style.color = dark;
                wrap.style.borderColor = light;
                if(typeof fontHover !== 'undefined'){
                    if(icon) icon.style.color = font;
                    btnName.style.color = font;
                    if(btnNameIcon) btnNameIcon.style.color = font;
                }else{
                    if(typeof font !== 'undefined'){
                        if(icon) icon.style.color = font;
                        btnName.style.color = font;
                        if(btnNameIcon) btnNameIcon.style.color = font;
                    }else{
                        if(icon) icon.style.color = '';
                        btnName.style.color = '';
                        if(btnNameIcon) btnNameIcon.style.color = '';
                    }
                }
            }
            if(type=='flat'){
                wrap.style.backgroundColor = color;
                if(icon) icon.style.color = font;
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
            icon = wrap.querySelector('.super-before'),
            btnName = wrap.querySelector('.super-button-name'),
            btnNameIcon = btnName.querySelector('i');
        if(type=='2d'){
            wrap.style.backgroundColor = hoverColor;
            wrap.style.borderColor = hoverLight;
            if(icon) icon.style.color = fontHover;
            btnName.style.color = fontHover;
            if(btnNameIcon) btnNameIcon.style.color = fontHover;
        }
        if(type=='flat'){
            wrap.style.backgroundColor = hoverColor;
            if(icon) icon.style.color = fontHover;
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
                if(icon) icon.style.color = fontHover;
                btnName.style.color = fontHover;
                if(btnNameIcon) btnNameIcon.style.color = fontHover;
            }else{
                if(typeof font !== 'undefined'){
                    if(icon) icon.style.color = font;
                    btnName.style.color = font;
                    if(btnNameIcon) btnNameIcon.style.color = font;
                }else{
                    if(icon) icon.style.color = '';
                    btnName.style.color = '';
                    if(btnNameIcon) btnNameIcon.style.color = '';
                }
            }
        }
        if(type=='diagonal'){
            if(typeof color !== 'undefined'){
                wrap.style.borderColor = hoverColor;
            }else{
                wrap.style.borderColor = '';
            }
            if(typeof font !== 'undefined'){
                if(icon) icon.style.color = fontHover;
                btnName.style.color = fontHover;
                if(btnNameIcon) btnNameIcon.style.color = fontHover;
            }else{
                if(icon) icon.style.color = '';
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
                    if(icon) icon.style.color = fontHover;
                    btnName.style.color = fontHover;
                    if(btnNameIcon) btnNameIcon.style.color = fontHover;
                }else{
                    if(typeof font !== 'undefined'){
                        if(icon) icon.style.color = font;
                        btnName.style.color = font;
                        if(btnNameIcon) btnNameIcon.style.color = font;
                    }else{
                        if(icon) icon.style.color = '';
                        btnName.style.color = '';
                        if(btnNameIcon) btnNameIcon.style.color = '';
                    }
                }
            }
        }
    };

    // Retrieve amount of decimals based on a numberic value
    SUPER.get_decimal_places = function($number){
        var $match = (''+$number).match(/(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/);
        if (!$match) { return 0; }
        return Math.max(0, ($match[1] ? $match[1].length : 0) - ($match[2] ? +$match[2] : 0));
    };
    SUPER.getAllUrlParams = function(url) {
        var queryString = url ? url.split('?')[1] : window.location.search.slice(1);
        var obj = {};
        if (queryString) {
            queryString = queryString.split('#')[0];
            var arr = queryString.split('&');
            for (var i = 0; i < arr.length; i++) {
                var a = arr[i].split('=');
                var paramName = a[0];
                var paramValue = typeof (a[1]) === 'undefined' ? true : a[1];
                paramName = paramName.toLowerCase();
                if (typeof paramValue === 'string') paramValue = paramValue.toLowerCase();
                if (paramName.match(/\[(\d+)?\]$/)) {
                    var key = paramName.replace(/\[(\d+)?\]/, '');
                    if (!obj[key]) obj[key] = [];
                    if (paramName.match(/\[\d+\]$/)) {
                        var index = /\[(\d+)\]/.exec(paramName)[1];
                        obj[key][index] = paramValue;
                    } else {
                        obj[key].push(paramValue);
                    }
                } else {
                    if (!obj[paramName]) {
                        obj[paramName] = paramValue;
                    } else if (obj[paramName] && typeof obj[paramName] === 'string'){
                        obj[paramName] = [obj[paramName]];
                        obj[paramName].push(paramValue);
                    } else {
                        obj[paramName].push(paramValue);
                    }
                }
            }
        }
        return obj;
    };
    // Get index of element based on parent node
    SUPER.index = function (node, class_name) {
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
    SUPER.get_dynamic_column_depth = function(field, nameSuffix, clone, cloneIndex){
        if(typeof nameSuffix === 'undefined') nameSuffix = '';
        if(typeof cloneIndex === 'undefined') cloneIndex = 0;
        var allParents = $(field).parents('.super-duplicate-column-fields');
        var suffix = [];
        $(allParents).each(function(key){
            if(key===0){
                if(clone===this){
                    if(cloneIndex>0){
                        return;
                    }
                }
            }
            var currentParent = this;
            var currentParentIndex = SUPER.index(this, 'super-duplicate-column-fields');
            if(currentParentIndex===0 && key===0){
                // Skip it
                return;
            }
            suffix.push('['+currentParentIndex+']');
        });
        return suffix.reverse().join('');
    }
    SUPER.append_dynamic_column_depth = function(clone){
        var added_fields = {},
            added_fields_with_suffix = {},
            added_fields_without_suffix = [],
            i, field, parent, last_tab_index,
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

            // Figure out how deep this node is inside dynamic columns
            var cloneIndex = $(clone).index();
            var dynamicParent = field.closest('.super-duplicate-column-fields');
            var nameSuffix = '';
            if(cloneIndex>0 && clone===dynamicParent){
                nameSuffix = '_'+(cloneIndex+1);
            }
            var levels = SUPER.get_dynamic_column_depth(field, nameSuffix, clone, cloneIndex);
            var originalFieldName = field.dataset.oname;
            field.name = originalFieldName+levels+nameSuffix;
            //field.value = field.name; 
            field.dataset.levels = levels;
            added_fields_with_suffix[originalFieldName] = field.name; 
            added_fields_without_suffix.push(field.name);
            if( field.classList.contains('hasDatepicker') ) field.classList.remove('hasDatepicker'); field.id = '';
            if( field.classList.contains('ui-timepicker-input') ) field.classList.remove('ui-timepicker-input');
        }


        // Now replace {tags} inside conditional logic/variable logic
        // Update conditional logic names
        // Update validate condition names
        // Update variable condition names 
        nodes = clone.querySelectorAll('.super-conditional-logic, .super-validate-conditions, .super-variable-conditions');
        for(i=0; i<nodes.length; ++i){
            // Before we continue replace any foreach(file_upload_fieldname)
            var regex = /{([-_a-zA-Z0-9]{1,})(\[.*?\])?(_\d{1,})?(?:;([a-zA-Z0-9]{1,}))?}/g;
            var m;
            var conditions = nodes[i].value;
            var replaceTagsWithValue = {};
            while ((m = regex.exec(conditions)) !== null) {
                // This is necessary to avoid infinite loops with zero-width matches
                if (m.index === regex.lastIndex) {
                    regex.lastIndex++;
                }
                var $o = (m[0] ? m[0] : ''); // original tag
                var $n = (m[1] ? m[1] : ''); // name
                var $d = (m[2] ? m[2] : ''); // depth
                var $dr = $d.replace(/[0-9]/g, "0") // depth reset to 0
                var $c = (m[3] ? m[3] : ''); // counter e.g: _2 or _3 etc.
                var $s = (m[4] ? m[4] : ''); // suffix
                if($s!=='') $s = ';'+$s;
                var currentFieldParent = $(nodes[i]).parents('.super-duplicate-column-fields:eq(0)');
                var findChildField = $(currentFieldParent).find('.super-shortcode-field[data-oname="'+$n+'"][data-olevels="'+$dr+'"]').first();
                if(findChildField.length===0) continue;
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
                levels = suffix.reverse().join('');
                if(childParentIndex!==0){
                    replaceTagsWithValue[$o] = '{'+$n+levels+'_'+(childParentIndex+1)+$s+'}';
                    continue;
                }
                replaceTagsWithValue[$o] = '{'+$n+levels+$c+$s+'}';
            }
            var key;
            for(key in replaceTagsWithValue) {
                nodes[i].value = SUPER.replaceAll(nodes[i].value, key, replaceTagsWithValue[key]);
            }
        }
        $.each(added_fields, function(name, field) {
            SUPER.after_field_change_blur_hook({form: clone, el: field});
        });
        return {
            added_fields: added_fields,
            added_fields_with_suffix: added_fields_with_suffix,
            added_fields_without_suffix: added_fields_without_suffix
        }
    };

    jQuery(document).ready(function ($) {
        
        SUPER.init_common_fields();

        var $doc = $(document);

        // When Elementor popup is opened re-init super forms
        $doc.on('elementor/popup/show', () => {
            SUPER.init_super_form_frontend();
        });

        $doc.on('focusin', function(e){
            var i, nodes;
            if(!e.target.tagName || typeof e.target.closest !== 'function') return true;
            if(e.target.closest('.super-timepicker-dialog')){
                // timepicker element was clicked
                SUPER.focusForm(e.relatedTarget);
                return true;
            }
            if(e.target.closest('.super-datepicker-dialog')){
                // datepicker element was clicked
                SUPER.focusForm(e.relatedTarget);
                return true;
            }
            if(SUPER.lastTabKey){
                if(e.target.tagName==='FORM' && e.target.closest('.super-form')){
                    // Check if form is already focussed
                    if(e.target.classList.contains('super-form-focussed')){
                        // form is already focussed, just continue
                        return true;
                    }
                    // Focus form
                    // is a super form, lets focus it by adding super-form-focussed class
                    SUPER.focusForm(e.target);
                    SUPER.lastFocussedForm = e.target;
                    var form = SUPER.lastFocussedForm;
                    var field, visibleNodes = [];
                    if(SUPER.lastTabKey==='tab'){
                        e.target.blur();
                        // the form became focus via regular tab, so we must focus the first field
                        nodes = form.querySelectorAll('.super-field:not('+super_common_i18n.tab_index_exclusion+')');
                        for (i = 0; i < nodes.length; i++) {
                            if(SUPER.has_hidden_parent(nodes[i])) continue;
                            visibleNodes.push(nodes[i]);
                            break;
                        }
                        field = visibleNodes[0];
                        SUPER.focusNextTabField(e, field, form, field);
                    }
                    if(SUPER.lastTabKey==='shift+tab'){
                        if(SUPER.firstFocussedField){
                            // the form has focus, and the last focussed field was the first, so we do a regular shift+tab
                            return true;
                        }
                        e.target.blur();
                        // the form became focus via shift+tab, so we must focus the last field
                        nodes = form.querySelectorAll('.super-field:not('+super_common_i18n.tab_index_exclusion+')');
                        for (i = 0; i < nodes.length; i++) {
                            if(SUPER.has_hidden_parent(nodes[i])) continue;
                            visibleNodes.push(nodes[i]);
                        }
                        field = visibleNodes[visibleNodes.length-1];
                        SUPER.focusNextTabField(e, field, form, field);
                    }
                    e.preventDefault();
                    return false;
                }else{
                    // Check if the element is part of the last focussed form
                    if(SUPER.lastFocussedForm && e.target.closest('form') && e.target.closest('form')===SUPER.lastFocussedForm){
                        // element is part of last focussed form, focus the element
                        SUPER.focusForm(e.target);
                        if(!e.target.classList.contains('super-placeholder')){
                            SUPER.focusField(e.target);
                        }
                    }else{
                        // element is not part of last focussed form, removing super-form-focussed class
                        if(SUPER.lastFocussedForm){
                            SUPER.lastFocussedForm.classList.remove('super-form-focussed');
                            SUPER.lastFocussedForm.tabIndex = 0;
                            // Make sure to remove focus/open classes
                            nodes = SUPER.lastFocussedForm.querySelectorAll('.super-focus');
                            for(i = 0; i < nodes.length; i++){
                                nodes[i].classList.remove('super-focus');
                                nodes[i].classList.remove('super-open');
                            }
                            nodes = SUPER.lastFocussedForm.querySelectorAll('.super-open');
                            for(i = 0; i < nodes.length; i++){
                                nodes[i].classList.remove('super-open');
                            }
                        }
                    }
                }
            }else{
                if(e.target.tagName==='FORM' && e.target.closest('.super-form')){
                    SUPER.focusForm(e.target);
                    SUPER.lastFocussedForm = e.target;
                    SUPER.lastFocussedForm.tabIndex = -1;
                }else{
                    // Might be a field inside the form
                    if(e.target.closest('.super-form')){
                        SUPER.focusField(e.target);
                        SUPER.focusForm(e.target.closest('form'));
                    }
                }
            }
        });
        $doc.keydown(function(e){
            SUPER.lastTabKey = undefined;
            var i, nodes,
                field,
                form,
                item,
                current,
                submitButton,
                nextSibling,
                keyCode = e.keyCode || e.which;

            // 32 = space
            if (keyCode == 32) {
                field = document.querySelector('.super-focus');
                if(field){
                    form = field.closest('.super-form');
                    // If checkbox or radio, then we check/uncheck the current focussed item
                    if(field.classList.contains('super-checkbox') || field.classList.contains('super-radio')){
                        if(field.querySelector('.super-focus')){
                            if(field.classList.contains('super-checkbox')){
                                SUPER.simulateCheckboxItemClicked(e, field.querySelector('.super-focus'));
                            }else{
                                SUPER.simulateRadioItemClicked(e, field.querySelector('.super-focus'));
                            }
                        }
                        e.preventDefault();
                        return false;
                    }
                    // If toggle
                    if(field.classList.contains('super-toggle')){
                        field.querySelector('.super-toggle-switch').click();
                        e.preventDefault();
                        return false;
                    }
                }
            }
            
            // 13 = enter
            if (keyCode == 13) {
                field = document.querySelector('.super-focus');
                if(field){
                    if(field.classList.contains('super-text')){
                        var countryList = field.querySelector('.super-int-phone_country-list');
                        if(countryList){
                            if(!countryList.classList.contains('.super-int-phone_hide')){
                                e.preventDefault();
                                return false;
                            }
                        }
                    }
                    if(field.classList.contains('super-dropdown')){
                        item = field.querySelector('.super-focus');
                        if(item) item.click();
                        e.preventDefault();
                        return false;
                    }
                    if(field.classList.contains('super-file')){
                        field.querySelector('.super-fileupload-button').click();
                        e.preventDefault();
                        return false;
                    }
                    if( field.classList.contains('super-textarea') ) {
                        // Allow to add line breaks on textarea elements
                        return true;
                    }
                    form = field.closest('.super-form');
                    // @since 3.3.0 - Do not submit form if Enter is disabled
                    if(form && form.dataset.disableEnter=='true'){
                        e.preventDefault();
                        return false;
                    }
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
            // 37 = left arrow
            // 38 = up arrow
            // 39 = right arrow
            // 40 = down arrow
            if ( keyCode == 37 || keyCode == 38 || keyCode == 39 || keyCode == 40 ) {
                field = document.querySelector('.super-focus');
                if(field){
                    // If toggle
                    if(field.classList.contains('super-toggle')){
                        field.querySelector('.super-toggle-switch').click();
                        e.preventDefault();
                        return false;
                    }
                    // Slider field
                    if(field.classList.contains('super-slider')){
                        var inputField = field.querySelector('.super-shortcode-field');
                        var value = inputField.value;
                        var steps = inputField.dataset.steps;
                        var newValue;
                        if(keyCode == 37 || keyCode == 38){
                            // Reverse
                            newValue = parseFloat(value)-parseFloat(steps);
                        }else{
                            newValue = parseFloat(value)+parseFloat(steps);
                        }
                        SUPER.reposition_slider_amount_label(inputField, newValue);
                        e.preventDefault();
                        return false;
                    }
                    // Star rating
                    if(field.classList.contains('super-rating')){
                        // First find last active star
                        nodes = field.querySelectorAll('.super-active');
                        if(keyCode == 37 || keyCode == 38){
                            // Reverse
                            if(nodes.length) nodes[nodes.length-2].click();
                        }else{
                            if(nodes.length){
                                // If it isn't last star, set next star to active
                                if(nodes[nodes.length-1].nextSibling && nodes[nodes.length-1].nextSibling.tagName==='I'){
                                    nodes[nodes.length-1].nextSibling.click();
                                }
                            }else{
                                // No active star yet
                                field.querySelector('i.super-rating-star').click();
                            }
                        }
                        e.preventDefault();
                        return false;
                    }

                    // Dropdown
                    if(field.classList.contains('super-dropdown')){
                        var all = field.querySelectorAll('.super-item:not(.super-placeholder)');
                        current = field.querySelector('.super-focus');
                        if(!current){
                            // If no item is focussed we grab the active item
                            current = field.querySelector('.super-active');
                        }
                        if(keyCode == 37 || keyCode == 38){
                            // go up (reverse)
                            if(current){
                                var prev = current.previousSibling;
                                if(prev && !prev.classList.contains('super-placeholder')){
                                    current.classList.remove('super-focus');
                                    prev.classList.add('super-focus');
                                }else{
                                    // continue at bottom (last item)
                                    all[0].classList.remove('super-focus');
                                    all[all.length-1].classList.add('super-focus');
                                }
                            }else{
                                // none selected yet, focus last
                                all[all.length-1].classList.add('super-focus');
                            }
                        }else{
                            // go down
                            if(current){
                                var next = current.nextSibling;
                                if(next){
                                    current.classList.remove('super-focus');
                                    next.classList.add('super-focus');
                                }else{
                                    // continue at top (first item)
                                    all[all.length-1].classList.remove('super-focus');
                                    all[0].classList.add('super-focus');
                                }
                            }else{
                                // none selected yet, focus first
                                all[0].classList.add('super-focus');
                            }
                        }
                        current = field.querySelector('.super-focus');
                        current.scrollIntoView({behavior: "auto", block: "center", inline: "center"});
                        e.preventDefault();
                        return false;
                    }

                    // Checkbox / Radio
                    if(field.classList.contains('super-checkbox') || field.classList.contains('super-radio')){
                        if(field.querySelector('.super-focus')){
                            current = field.querySelector('.super-item.super-focus');
                            if(current){
                                // 37 = left arrow
                                // 38 = up arrow
                                if(keyCode == 37 || keyCode == 38){
                                    // prev
                                    nextSibling = current.previousSibling;
                                }else{
                                    // next
                                    nextSibling = current.nextSibling;
                                }
                                current.classList.remove('super-focus');
                                if(nextSibling && nextSibling.classList.contains('super-item')){
                                    nextSibling.classList.add('super-focus');
                                    if(field.classList.contains('super-radio')){
                                        nextSibling.click();
                                    }
                                }else{
                                    // left, up
                                    var innerNodes = field.querySelectorAll('.super-item');
                                    if(keyCode == 37 || keyCode == 38){
                                        innerNodes[innerNodes.length-1].classList.add('super-focus');
                                        if(field.classList.contains('super-radio')){
                                            innerNodes[innerNodes.length-1].click();
                                        }
                                    }else{
                                        innerNodes[0].classList.add('super-focus');
                                        if(field.classList.contains('super-radio')){
                                            innerNodes[0].click();
                                        }
                                    }
                                }
                                e.preventDefault();
                                return false;
                            }
                        }
                        e.preventDefault();
                        return false;
                    }
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
                SUPER.firstFocussedField = false;
                SUPER.lastTabKey = 'tab';
                if(e.shiftKey){
                    SUPER.lastTabKey = 'shift+tab';
                }
                // Check if we have a focussed form
                form  = document.querySelector('.super-form-focussed')
                if(form){
                    // Check if we can find a currently focussed field
                    field = form.querySelector('.super-field.super-focus');
                    if(field){
                        // Check if first field of form
                        nodes = form.querySelectorAll('.super-field:not('+super_common_i18n.tab_index_exclusion+')');
                        var visibleNodes = [];
                        for (i = 0; i < nodes.length; i++) {
                            if(SUPER.has_hidden_parent(nodes[i])) continue;
                            visibleNodes.push(nodes[i]);
                        }
                        // if we shift+tab from second visible field to first visible field, we must set tabindex on form to -1
                        if(field===visibleNodes[1]){
                            if(SUPER.lastTabKey==='shift+tab'){
                                form.tabIndex = -1;
                                SUPER.focusNextTabField(e, field, form);
                                e.preventDefault();
                                return false;
                            }
                        }
                        if(field===visibleNodes[0]){
                            // The current field is the first field in the form
                            SUPER.firstFocussedField = true;
                            if(SUPER.lastTabKey==='shift+tab'){
                                // Unfocus current field
                                SUPER.resetFocussedFields();
                                return true;
                            }
                        }
                        if(field===visibleNodes[visibleNodes.length-1]){
                            // The current field is the last field in the form
                            if(SUPER.lastTabKey==='tab'){
                                // If this is the last field then we do a normal tab to get out of form focus
                                // Unfocus current field
                                field.classList.remove('super-focus');
                                if( field.classList.contains('super-form-button') ) {
                                    // Check if it's a button
                                    SUPER.init_button_colors(field.querySelector('.super-button-wrap'));
                                }else{
                                    // If not a button
                                    innerNodes = field.querySelectorAll('.super-focus');
                                    for ( i = 0; i < innerNodes.length; i++){
                                        innerNodes[i].classList.remove('super-focus');
                                    }
                                }
                                return true;
                            }
                        }

                        SUPER.focusNextTabField(e, field, form);
                        e.preventDefault();
                        return false;
                    }else{
                        // No focussed field yet, let's focus the first field or last depending on tab, or shift+tab
                        if(SUPER.lastTabKey==='tab'){
                            SUPER.firstFocussedField = true;
                            nodes = form.querySelectorAll('.super-field:not('+super_common_i18n.tab_index_exclusion+')');
                            for (i = 0; i < nodes.length; i++) {
                                if(SUPER.has_hidden_parent(nodes[i])) continue;
                                SUPER.focusNextTabField(e, nodes[i], form, nodes[i]);
                                e.preventDefault();
                                return false;
                            }
                        }
                        if(SUPER.lastTabKey==='shift+tab'){
                            return true;
                        }
                    }
                    e.preventDefault();
                    return false;
                }else{
                    // no focussed form found, just let the TAB event execute
                }
            }
        });


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
                        SUPER.reposition_slider_amount_label(field, field.value, true);
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
                $words,
                $removeNoneChars,
                $chars,
                $allChars;
            if(e.type!='keyup') $time = 0;
            if (word_count_timeout !== null) {
                clearTimeout(word_count_timeout);
            }
            word_count_timeout = setTimeout(function () {
                $text = $this.val();
                $words = $text.match(/\S+/g);
                $words = $words ? $words.length : 0;
                $removeNoneChars = $text.replace(/\s/g, ""); // use the \s quantifier to remove all white space, is equivalent to [\r\n\t\f\v ]
                $chars = $removeNoneChars.length; // count only characters after removing any whitespace, tabs, linebreaks etc.
                $allChars = $text.length; // count all characters
                $this.attr('data-word-count', $words);
                $this.attr('data-chars-count', $chars);
                $this.attr('data-allchars-count', $allChars);
                SUPER.after_field_change_blur_hook({el: $this[0]});
            }, $time);
        });

        // Upon opening color picker
        $doc.on('click', '.super-color', function(){
            SUPER.focusField(this);
        });

        // @since 1.2.4
        $doc.on('click', '.super-quantity .super-minus-button, .super-quantity .super-plus-button', function(){
            SUPER.focusField(this);
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
        $doc.on('blur', '.super-quantity .super-shortcode-field', function() {
            // Remove empty decimal place
            if(this.value.indexOf('.')!==-1){
                var split = this.value.split("."), before = split[0], after = split[1];
                var result = before;
                if(after!=='') result += '.'+after;
                this.value = result;
            } 
        });
        $doc.on('input', '.super-quantity .super-shortcode-field', function() {
            // Exception when using steps with decimals, then allow to enter decimal number
            if(this.dataset.steps.indexOf('.')!==-1){
                // Count decimals
                var decimals = this.dataset.steps.toString().split(".")[1].length || 0; 
                var floor = Math.floor(this.dataset.steps);
                if(floor===this.dataset.steps) decimals = 0;
                this.value = (this.value.indexOf(".")!==-1) ? (this.value.substr(0, this.value.indexOf(".")) + this.value.substr(this.value.indexOf("."), (decimals+1))) : this.value;
                return;
            }
            // Only allow whole numbers
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
            SUPER.focusField(this);
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
        
        $doc.on('click', '.super-form .super-duplicate-column-fields .super-add-duplicate', function(){
            
            var i, x, y,
                nodes,
                v,vv,
                found_field,
                el,
                parent,
                column,
                form,
                firstColumn,
                lastColumn,
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
                //field_counter = 0,
                element,
                foundElements = [],
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
                regex = /{([^\\\/\s"'+]*?)}/g,
                oldv,
                duplicate_dynamically;

            el = $(this)[0];
            parent = el.closest('.super-duplicate-column-fields');
            // If custom padding is being used set $column to be the padding wrapper `div`
            column = ( parent.parentNode.classList.contains('super-column-custom-padding') ? el.closest('.super-column-custom-padding') : parent.closest('.super-column') );
            form = SUPER.get_frontend_or_backend_form({el: el, form: form});
            var duplicateColumns = column.querySelectorAll('.super-duplicate-column-fields');
            firstColumn = duplicateColumns[0];
            found = column.querySelectorAll(':scope > .super-duplicate-column-fields').length;
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

            counter = column.querySelectorAll(':scope > .super-duplicate-column-fields').length;
            clone = firstColumn.cloneNode(true);
            lastColumn = duplicateColumns[(found-1)];
            lastColumn.parentNode.insertBefore(clone, lastColumn.nextElementSibling);

            // @since 3.3.0 - hook after appending new column
            SUPER.after_appending_duplicated_column_hook(form, unique_field_names, clone);

            // Now reset field values to default
            SUPER.init_clear_form({form: form, clone: clone});

            // @since 3.2.0 - increment for tab index fields when dynamic column is cloned
            if($(clone).find('.super-shortcode[data-super-tab-index]').last().length){
                last_tab_index = $(clone).find('.super-shortcode[data-super-tab-index]').last().attr('data-super-tab-index');
            }else{
                last_tab_index = '';
            }
            last_tab_index = parseFloat(last_tab_index);
            
            // First rename then loop through conditional logic and update names, otherwise we might think that the field didn't exist!
            // Loop over all fields that are inside dynamic column and rename them accordingly
            var $info = SUPER.append_dynamic_column_depth(clone);
            added_fields_with_suffix = $info.added_fields_with_suffix;
 
            // @since 4.6.0 - update html field tags attribute
            // @since 4.6.0 - update accordion title and description field tags attribute
            // @since 4.9.6 - update google maps field tags attribute
            // Get all elements based on field tag attribute that contain one of these field names
            // Then convert it to an array and append the missing field names
            // @IMPORTANT: Only do this for elements that are NOT inside a dynamic column
            foundElements = [];
            $.each(added_fields_with_suffix, function( index ) {
                html_fields = form.querySelectorAll('[data-tags*="{'+index+'}"], .super-google-map[data-fields*="{'+index+'}"], .super-html-content[data-fields*="{'+index+'}"]');
                for (i = 0; i < html_fields.length; ++i) {
                    found = false;
                    for (x=0; x<foundElements.length; x++) {
                        if($(foundElements[x]).is(html_fields[i])) found = true;
                    }
                    if(!found) foundElements.push(html_fields[i]);
                }
            });

            var formId = 0;
            if(form.querySelector('input[name="hidden_form_id"]')){
                formId = form.querySelector('input[name="hidden_form_id"]').value;
            }
            for(i=0; i<foundElements.length; i++) {
                if(!foundElements[i].parentNode.querySelector('textarea')) continue;
                var html = foundElements[i].parentNode.querySelector('textarea').value;
                html = SUPER.filter_foreach_statements(foundElements[i], 0, 0, html, undefined, formId, form);
                html = SUPER.replaceAll(html, '<%', '{');
                html = SUPER.replaceAll(html, '%>', '}');
                foundElements[i].innerHTML = html;
            }
            
            // Now replace {tags} inside HTML element
            for(i=0; i<foundElements.length; i++) {
                var html;
                // @since 4.9.0 - accordion title description {tags} compatibility
                if( foundElements[i].dataset.tags ) {
                    html = foundElements[i].dataset.original;
                }else{
                    if(!foundElements[i].parentNode.querySelector('textarea')) continue;
                    html = foundElements[i].parentNode.querySelector('textarea').value;
                }
                // If empty skip
                if(html===''){
                    continue;
                }
                // Before we continue replace any foreach(file_upload_fieldname)
                var regex = /{([-_a-zA-Z0-9]{1,})(\[.*?\])?(_\d{1,})?(?:;([a-zA-Z0-9]{1,}))?}/g;
                var m;
                var replaceTagsWithValue = {};
                while ((m = regex.exec(html)) !== null) {
                    // This is necessary to avoid infinite loops with zero-width matches
                    if (m.index === regex.lastIndex) {
                        regex.lastIndex++;
                    }
                    var $o = (m[0] ? m[0] : ''); // original tag
                    var $n = (m[1] ? m[1] : ''); // name
                    var $d = (m[2] ? m[2] : ''); // depth
                    var $dr = $d.replace(/[0-9]/g, "0") // depth reset to 0
                    var $c = (m[3] ? m[3] : ''); // counter e.g: _2 or _3 etc.
                    var $s = (m[4] ? m[4] : ''); // suffix
                    if($s!=='') $s = ';'+$s;
                    var currentFieldParent = $(foundElements[i]).parents('.super-duplicate-column-fields:eq(0)');
                    var findChildField = $(currentFieldParent).find('.super-shortcode-field[data-oname="'+$n+'"][data-olevels="'+$dr+'"]').first();
                    if(findChildField.length===0) continue;
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
                        replaceTagsWithValue[$o] = '{'+$n+levels+'_'+(childParentIndex+1)+$s+'}';
                        continue;
                    }
                    replaceTagsWithValue[$o] = '{'+$n+levels+$c+$s+'}';
                }
                var key;
                for(key in replaceTagsWithValue) {
                    html = SUPER.replaceAll(html, key, replaceTagsWithValue[key]);
                }
                if( foundElements[i].dataset.tags ) {
                    if(foundElements[i].value || foundElements[i].dataset.value){
                        if(foundElements[i].value) foundElements[i].value = html;
                        if(foundElements[i].dataset.value) foundElements[i].dataset.value = html;
                    }else{
                        foundElements[i].dataset.original = html;
                    }
                }else{
                    if(!foundElements[i].parentNode.querySelector('textarea')) continue;
                    foundElements[i].parentNode.querySelector('textarea').value = html;
                }

            }
            // Required to update calculations after adding dynamic column
            SUPER.after_field_change_blur_hook({form: form, el: undefined});

            SUPER.init_replace_html_tags({el: undefined, form: form, foundElements: foundElements});
            
            // @since 2.4.0 - hook after adding new column
            SUPER.after_duplicating_column_hook(form, unique_field_names, clone);            
            
            SUPER.init_common_fields();
        });

        // Delete dynamic column
        $doc.on('click', '.super-duplicate-column-fields .super-delete-duplicate', function(){
            var i, x, nodes, found,
                form = this.closest('.super-form'),
                removedFields = {},
                dataFields,
                parent = this.closest('.super-duplicate-column-fields'),
                foundElements = [];
            nodes = parent.querySelectorAll('.super-shortcode-field');
            for (i = 0; i < nodes.length; ++i) {
                // Check if this is a file upload element, if so make sure we delete all files from the files object
                var fieldName = SUPER.get_field_name(nodes[i]); //SUPER.get_original_field_name = function(field){
                var fieldType = SUPER.get_field_type(form, fieldName);
                if(fieldType.type==='file'){
                    var w = nodes[i].closest('.super-field-wrapper');
                    var x, d = w.querySelectorAll('.super-fileupload-delete');
                    for(x=0; x<d.length; x++){
                        d[x].click();
                    }
                }
                var $this = $(nodes[i]);
                var $fieldWrapper = $this.parents('.super-field-wrapper:eq(0)');
                var $fieldName = $fieldWrapper.find('.super-active-files').attr('name');
                var $index = $this.parents('div:eq(0)').index();
                // Modify file list, and remove one element at $index
                // We use splice() instead of delete() because we don't want to leave the old index with an `undefined` value
                if(SUPER.files){
                    if(SUPER.files[formId]){
                        if(SUPER.files[formId][$fieldName]){
                            SUPER.files[formId][$fieldName].splice($index, 1); 
                        }
                    }
                }
                var $parent = $this.parents('.super-fileupload-files:eq(0)');
                var $wrapper = $parent.parents('.super-field-wrapper:eq(0)');
                var total = $wrapper.children('.super-fileupload').data('total-file-sizes') - $this.parents('div:eq(0)').data('file-size');
                $wrapper.children('.super-fileupload').data('total-file-sizes', total);
                $wrapper.children('input[type="hidden"]').val('');
                $this.parents('div:eq(0)').remove();
                var field = $fieldWrapper.find('.super-active-files')[0];
                SUPER.after_field_change_blur_hook({el: field, form: form});
                removedFields[nodes[i].dataset.oname] = nodes[i];
            }
            
            // Now we can remove the dynamic column
            parent.remove();

            // @since 4.6.0 - update html field tags attribute
            // @since 4.6.0 - update accordion title and description field tags attribute
            // @since 4.9.6 - update google maps field tags attribute
            // Get all elements based on field tag attribute that contain one of these field names
            // Then convert it to an array and append the missing field names
            // @IMPORTANT: Only do this for elements that are NOT inside a dynamic column
            foundElements = [];
            $.each(removedFields, function( index ) {
                var html_fields = form.querySelectorAll('[data-tags*="{'+index+'}"], .super-google-map[data-fields*="{'+index+'}"], .super-html-content[data-fields*="{'+index+'}"]');
                for (i = 0; i < html_fields.length; ++i) {
                    if(!html_fields[i].closest('.super-duplicate-column-fields')){
                        found = false;
                        for(x=0; x<foundElements.length; x++) {
                            if($(foundElements[x]).is(html_fields[i])) found = true;
                        }
                        if(!found) foundElements.push(html_fields[i]);
                    }
                }
            });

            var formId = 0;
            if(form.querySelector('input[name="hidden_form_id"]')){
                formId = form.querySelector('input[name="hidden_form_id"]').value;
            }
            for (i = 0; i < foundElements.length; ++i) {
                var html = foundElements[i].parentNode.querySelector('textarea').value;
                html = SUPER.filter_foreach_statements(foundElements[i], 0, 0, html, undefined, formId, form);
                html = SUPER.replaceAll(html, '<%', '{');
                html = SUPER.replaceAll(html, '%>', '}');
                foundElements[i].innerHTML = html;
            }

            // @IMPORTANT
            // The below hook should come before the field change hook
            // This is because it will update the fields attribute on for instance the calculator element
            // If this hook is placed below the field change hook it would cause incorrect results
            SUPER.after_duplicating_column_hook(form, removedFields);

            // Now we need to update the html elements
            SUPER.init_replace_html_tags({el: undefined, form: form, foundElements: foundElements});

            // Reload google maps
            SUPER.google_maps_api.initMaps({form: form});
            
        });

        // Close messages
        $doc.on('click', '.super-msg .super-close', function(){
            $(this).parents('.super-msg:eq(0)').fadeOut(500);
        });

        $doc.on('click', '.super-fileupload-button', function(){
            SUPER.focusForm(this);
            SUPER.focusField(this);
            $(this).parents('.super-field-wrapper:eq(0)').find('.super-fileupload').trigger('click');
        });
        $doc.on('click', '.super-fileupload-delete', function(){
            var form = SUPER.get_frontend_or_backend_form({el: this});
            var formId = 0;
            if(form.querySelector('input[name="hidden_form_id"]')){
                formId = form.querySelector('input[name="hidden_form_id"]').value;
            }
            var $this = $(this);
            var $fieldWrapper = $this.parents('.super-field-wrapper:eq(0)');
            var $fieldName = $fieldWrapper.find('.super-active-files').attr('name');
            var $index = $this.parents('div:eq(0)').index();
            // Modify file list, and remove one element at $index
            // We use splice() instead of delete() because we don't want to leave the old index with an `undefined` value
            if(SUPER.files){
                if(SUPER.files[formId]){
                    if(SUPER.files[formId][$fieldName]){
                        SUPER.files[formId][$fieldName].splice($index, 1); 
                    }
                }
            }
            var $parent = $this.parents('.super-fileupload-files:eq(0)');
            var $wrapper = $parent.parents('.super-field-wrapper:eq(0)');
            var total = $wrapper.children('.super-fileupload').data('total-file-sizes') - $this.parents('div:eq(0)').data('file-size');
            $wrapper.children('.super-fileupload').data('total-file-sizes', total);
            $wrapper.children('input[type="hidden"]').val('');
            $this.parents('div:eq(0)').remove();
            var field = $fieldWrapper.find('.super-active-files')[0];
            SUPER.after_field_change_blur_hook({el: field, form: form});
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
                nodes = wrapper.querySelectorAll('.super-dropdown-list .super-item');

            if (autosuggestTimeout !== null) clearTimeout(autosuggestTimeout);
            autosuggestTimeout = setTimeout(function () {
                value = el.value.toString();
                if (value === '') {
                    parent.classList.remove('super-string-found');
                    return false;
                }
                for (i = 0; i < nodes.length; i++) {
                    var stringValue = nodes[i].dataset.searchValue.toString();
                    var stringValue_l = stringValue.toLowerCase();
                    var isMatch = false;
                    if(el.dataset.logic=='start'){
                        // Starts with filter logic:
                        isMatch = stringValue_l.startsWith(value);
                    }else{
                        // Contains filter logic:
                        isMatch = stringValue_l.indexOf(value) !== -1;
                    }
                    if( isMatch===true ) {
                        itemsToShow.push(nodes[i]);
                        regex = RegExp([value].join('|'), 'gi');
                        text = stringValue.split(';')[0];
                        stringBold = text.replace(regex, '<span>$&</span>');
                        stringBold = stringBold.replace(/ /g, '\u00a0');
                        nodes[i].innerHTML = stringBold;
                    }else{
                        itemsToHide.push(nodes[i]);
                    }
                }
                [].forEach.call(itemsToShow, function (el) {
                    el.classList.add('super-match');
                });
                [].forEach.call(itemsToHide, function (el) {
                    el.classList.remove('super-match');
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
        $doc.on('click', '.super-dropdown-list:not(.super-autosuggest-tags-list)', function(e){
            var i, nodes, field = e.target.closest('.super-field');
            if(field.classList.contains('super-auto-suggest')) return false;
            SUPER.focusForm(field);
            if(!field.classList.contains('super-open')){
                nodes = document.querySelectorAll('.super-focus');
                for(i=0; i<nodes.length; i++){
                    nodes[i].classList.remove('super-focus');
                    nodes[i].classList.remove('super-open');
                }
                field.classList.add('super-open');
                SUPER.focusField(field);
                var searchField = e.target.closest('.super-field-wrapper').querySelector('input[name="super-dropdown-search"]');
                if(searchField) searchField.focus();
            }else{
                if(e.target.classList.contains('super-placeholder')){
                    field.classList.remove('super-open');
                }
            }
        });
        // Unfocus dropdown
        document.addEventListener('click', function(e){
            if(e.target.closest('.super-multipart-step')) return true;
            var i, nodes;
            if(!e.target.closest('.super-form')){
                // Timepicker list is outside form element because it is 
                // appended dynamically at the bottom of the page
                // hence we have to skip it explicitly
                if(e.target.closest('.super-timepicker-dialog')){
                    return true;
                }
                // If searching in dropdown, do not close it
                if(e.target.closest('.super-dropdown-search')){
                    return true;
                }

                nodes = document.querySelectorAll('.super-form-focussed');
                for(i = 0; i < nodes.length; i++){
                    nodes[i].classList.remove('super-form-focussed');
                    nodes[i].tabIndex = 0;
                    SUPER.lastTabKey = undefined;
                }
                nodes = document.querySelectorAll('.super-focus');
                for(i = 0; i < nodes.length; i++){
                    if(nodes[i].classList.contains('super-keyword-tags')){
                        var f = nodes[i].querySelector('.super-keyword-filter');
                        if(f) f.value = ''; // empty value
                    }
                    nodes[i].classList.remove('super-focus');
                    nodes[i].classList.remove('super-open');
                }
                nodes = document.querySelectorAll('.super-open');
                for(i = 0; i < nodes.length; i++){
                    nodes[i].classList.remove('super-open');
                }
                if(e.target.closest('.super-setting')){
                    if(e.target.classList.contains('super-dropdown-placeholder')){
                        e.target.parentNode.classList.add('super-focus');
                        e.target.parentNode.classList.add('super-open');
                    }
                }
            }else{
                if(!e.target.closest('.super-dropdown')){
                    nodes = document.querySelectorAll('.super-dropdown.super-focus');
                    for(i = 0; i < nodes.length; i++){
                        nodes[i].classList.remove('super-focus');
                        nodes[i].classList.remove('super-open');
                    }
                    nodes = document.querySelectorAll('.super-dropdown.super-open');
                    for(i = 0; i < nodes.length; i++){
                        nodes[i].classList.remove('super-open');
                    }
                    if(e.target.classList.contains('super-shortcode') && !e.target.classList.contains('super-field')){
                        SUPER.resetFocussedFields();
                    }
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
                nodes = wrapper.querySelectorAll('.super-dropdown-list .super-item:not(.super-placeholder)');
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
                        stringBold = nodes[i].dataset.searchValue.replace(regex, replacement);
                        stringBold = stringBold.replace(/ /g, '\u00a0');
                        nodes[i].innerHTML = stringBold;
                        nodes[i].classList.add('super-focus');
                    }else{
                        nodes[i].innerHTML = stringValue;
                        nodes[i].classList.remove('super-focus');
                    }
                }
                if( found===true ) {
                    nodes = field.querySelectorAll('.super-dropdown-list .super-item.super-focus');
                    for(i=0; i<nodes.length; i++){
                        nodes[i].classList.remove('super-focus');
                    }
                    // If only one found, make it selected option by default
                    if(nodes.length===1){
                        field.classList.remove('super-string-found');
                    }else{
                        field.classList.add('super-string-found');
                    }
                    if(firstFound) firstFound.classList.add('super-focus');
                    field.classList.add('super-focus');
                    dropdownUI = $(field).find('.super-dropdown-list');
                    dropdownUI.scrollTop(dropdownUI.scrollTop() - dropdownUI.offset().top + $(firstFound).offset().top - 50); 
                }else{
                    // Nothing found, clear timeout
                    el.value = '';
                    field.classList.remove('super-string-found');
                }
            }
        });

        // On choosing item, populate form with data
        $doc.on('click', '.super-wc-order-search .super-field-wrapper:not(.super-overlap) .super-dropdown-list .super-item, .super-auto-suggest .super-field-wrapper:not(.super-overlap) .super-dropdown-list .super-item', function(){
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
            field.classList.remove('super-open');
            field.classList.remove('super-string-found');
            wrapper.classList.add('super-overlap');
            field.classList.add('super-filled');
            SUPER.after_field_change_blur_hook({el: wrapper.querySelector('.super-shortcode-field')});
            if(populate=='true'){
                SUPER.populate_form_data_ajax({el: field, clear: false});
            }
        });
        // On removing item
        $doc.on('click', '.super-wc-order-search .super-field-wrapper.super-overlap li, .super-auto-suggest .super-field-wrapper.super-overlap li', function(){
            var i,items,
                el = this.closest('.super-field'),
                wrapper = this.closest('.super-field-wrapper'),
                field = wrapper.querySelector('.super-shortcode-field');
            
            field.value = '';
            items = wrapper.querySelectorAll('.super-active');
            for( i = 0; i < items.length; i++ ) {
                items[i].classList.remove('super-active');
            }
            wrapper.classList.remove('super-overlap');
            el.classList.remove('super-filled');
            field.focus();
            SUPER.after_field_change_blur_hook({el: field});
        });

        // Update dropdown
        $doc.on('click', '.super-dropdown .super-dropdown-list .super-item:not(.super-placeholder)', function(e){
            SUPER.focusForm(this);
            SUPER.focusField(this);
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
                total,
                names,
                values,
                counter;
            
            e.stopPropagation();
            form = SUPER.get_frontend_or_backend_form({el: this});
            wrapper = this.closest('.super-field-wrapper');
            input = wrapper.querySelector('.super-shortcode-field');
            parent = this.closest('.super-dropdown-list');
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
                if(input) input.value = value;
                field.classList.remove('super-open');
            }else{
                max = (input.dataset.maxlength ? parseInt(input.dataset.maxlength, 10) : 1);
                total = parent.querySelectorAll('.super-item.super-active:not(.super-placeholder').length;
                if(this.classList.contains('super-active')){
                    this.classList.remove('super-active');    
                }else{
                    if(total >= max) return false;
                    this.classList.add('super-active');    
                    if(total+1 >= max){
                        field.classList.remove('super-open');
                    }
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
                if(input) input.value = values;
            }

            // Switch to different language when clicked
            if(field.closest('.super-i18n-switcher')){
                // Generate new nonce
                var $this = $(this), $form = $this.closest('.super-form');
                // Remove initialized class
                $form.find('.super-button').remove();
                $form.removeClass('super-initialized');
                $.ajax({ 
                    url: super_elements_i18n.ajaxurl, 
                    type: 'post', 
                    data: { 
                        action: 'super_create_nonce'
                    },
                    success: function (nonce) {
                        $form.find('input[name="sf_nonce"]').val(nonce.trim());
                    },
                    complete: function(){
                        var $form_id = $form.find('input[name="hidden_form_id"]').val(),
                            $sf_nonce = $form.find('input[name="sf_nonce"]').val(),
                            $i18n = $this.attr('data-value');

                        $this.parent().children('.super-item').removeClass('super-active');
                        $this.addClass('super-active');
                        // Also move to placeholder
                        $this.parents('.super-dropdown').children('.super-dropdown-placeholder').html($this.html());
                        
                        // Get URL parameters
                        var $queryString = window.location.search;
                        var $parameters = SUPER.getAllUrlParams($queryString);
                        $.ajax({
                            url: super_elements_i18n.ajaxurl,
                            type: 'post',
                            data: {
                                action: 'super_language_switcher',
                                form_id: $form_id,
                                i18n: $i18n,
                                parameters: $parameters,
                                sf_nonce: $sf_nonce,
                            },
                            success: function (result) {
                                var data = JSON.parse(result);
                                if(data.error && data.error===true){
                                    var i, nodes = document.querySelectorAll('.super-msg');
                                    for (i = 0; i < nodes.length; i++) { 
                                        nodes[i].remove();
                                    }
                                    var $html = '<div class="super-msg super-error">';                            
                                    $html += data.msg;
                                    $html += '<span class="super-close"></span>';
                                    $html += '</div>';
                                    $($html).prependTo($form);
                                }else{
                                    if(data.rtl==true){
                                        $form.addClass('super-rtl');
                                    }else{
                                        $form.removeClass('super-rtl');
                                    }
                                    $form.find('form').html(data.html);
                                    $form.data('i18n', $i18n);
                                }
                            },
                            complete: function(){
                                $form.removeClass('super-initialized');
                                $form.removeClass('super-rendered');
                                $form.find('.super-multipart-progress').remove();
                                $form.find('.super-multipart-steps').remove();
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                // eslint-disable-next-line no-console
                                console.log(xhr, ajaxOptions, thrownError);
                                alert(super_elements_i18n.failed_to_process_data);
                            }
                        });
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        // eslint-disable-next-line no-console
                        console.log(xhr, ajaxOptions, thrownError);
                        alert('Failed to generate nonce!');
                    }
                });
                return true;
            }

            if(input && input.value===''){
                field.classList.remove('super-filled');
            }else{
                field.classList.add('super-filled');
            }
            validation = input.dataset.validation;
            if(typeof validation !== 'undefined' && validation !== false){
                SUPER.handle_validations({el: input, form: form, validation: validation});
            }

            SUPER.after_field_change_blur_hook({el: input});
        });

        $doc.on('click','.super-back-to-top',function(){
            var form = SUPER.get_frontend_or_backend_form({el: this});
            if(form.closest('.super-popup-content')){
                $(form.closest('.super-popup-content')).animate({
                    scrollTop: $(form).offset().top-200
                }, 1000);
            }else{
                $('html, body').animate({
                    scrollTop: $(form).offset().top-200
                }, 1000);
            }
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

        SUPER.simulateRadioItemClicked = function(e, el){
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
                $form = SUPER.get_frontend_or_backend_form({el: el});
                SUPER.focusNextTabField(e, el, $form, el);
                $this = el.querySelector('input[type="radio"]');
                if(el.classList.contains('super-active')) return true;
                $parent = el.closest('.super-field-wrapper');
                $field = $parent.querySelector('.super-shortcode-field');
                $active = $parent.querySelector('.super-item.super-active');
                if($active){
                    $active.classList.remove('super-active');
                    $active.classList.remove('super-focus');
                }
                el.classList.add('super-active');
                el.classList.add('super-focus');
                el.closest('.super-field').classList.add('super-focus');
                $validation = $field.dataset.validation;
                $field.value = $this.value;
                if(typeof $validation !== 'undefined' && $validation !== false){
                    SUPER.handle_validations({el: $field, form: $form, validation: $validation});
                }
                SUPER.after_field_change_blur_hook({el: $field});
            }
            return false;
        };
        $doc.on('click', '.super-form .super-radio > .super-field-wrapper .super-item', function (e) {
            return SUPER.simulateRadioItemClicked(e, this);
        });

        SUPER.simulateCheckboxItemClicked = function(e, el){
            var i, 
                $form,
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
                $form = SUPER.get_frontend_or_backend_form({el: el});
                SUPER.focusNextTabField(e, el, $form, el);

                $checkbox = el.querySelector('input[type="checkbox"]');
                $parent = $checkbox.closest('.super-field-wrapper');
                $field = $parent.querySelector('input[type="hidden"]');
                $counter = 0;
                $maxlength = $parent.querySelector('.super-shortcode-field').dataset.maxlength;
                $checked = $parent.querySelectorAll('label.super-active');
                if(el.classList.contains('super-active')){
                    el.classList.remove('super-active');
                }else{
                    if($checked.length >= parseInt($maxlength, 10)){
                        if(parseInt($maxlength,10)===1){
                            for (i = 0; i < $checked.length; ++i) {
                                $checked[i].classList.remove('super-active');
                                $checked[i].querySelector('input').checked = false;
                            }
                        }else{
                            return false;
                        }
                    }
                    el.classList.add('super-active');
                }
                $checked = $parent.querySelectorAll('label.super-active');
                $value = '';
                for (i = 0; i < $checked.length; ++i) {
                    if ($counter === 0) $value = $checked[i].querySelector('input').value;
                    if ($counter !== 0) $value = $value + ',' + $checked[i].querySelector('input').value;
                    $counter++;
                }
                $field.value = $value;
                $validation = $field.dataset.validation;
                if(typeof $validation !== 'undefined' && $validation !== false){
                    SUPER.handle_validations({el: $field, form: $form, validation: $validation});
                }
                SUPER.after_field_change_blur_hook({el: $field});
            }
            return false;
        };
        $doc.on('click', '.super-form .super-checkbox > .super-field-wrapper .super-item', function (e) {
            return SUPER.simulateCheckboxItemClicked(e, this);
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
            SUPER.after_field_change_blur_hook({el: this});
        });
        
        $doc.on('mouseleave','.super-button .super-button-wrap',function(){
            this.parentNode.classList.remove('super-focus');
            SUPER.init_button_colors( this );
        });
        $doc.on('mouseover','.super-button .super-button-wrap',function(){
            SUPER.init_button_hover_colors( this.parentNode );
        });
        
        // Multi Part Columns
        $doc.on('click','.super-multipart-step',function(e){
            var i, nodes,
                el = this,
                form = el.closest('.super-form'),
                stepParams = (form.dataset.stepParams ? form.dataset.stepParams : ''), // default
                form_id = form.querySelector('input[name="hidden_form_id"]').value,
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
            if(stepParams!=='false'){
                window.location.hash = 'step-'+form_id+'-'+(parseInt(index,10)+1);
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
            skip = SUPER.skipMultipart(el, form, index, activeIndex);
            if(skip===true) return false;

            // Focus first TAB index field in next multi-part
            SUPER.focusFirstTabIndexField(e, form, multipart);

            // Update HTML element to reflect changes e.g foreach() and if statements
            SUPER.init_replace_html_tags({form: form});

            // Required for toggle field and other possible elements to rerender proper size
            SUPER.init_super_responsive_form_fields({form: form});
        });
        
        // Multi Part Next Prev Buttons
        $doc.on('click','.super-prev-multipart, .super-next-multipart',function(e){
            if(this.classList.contains('super-prev-multipart')){
                SUPER.switchMultipart(e, this, 'prev');
            }else{
                SUPER.switchMultipart(e, this, 'next');
            }
        });

        // @since 4.9.3 - Adaptive Placeholders
        SUPER.init_adaptive_placeholder();
    });


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

        // Keyword element functions
        app.autosuggestTagsTimeout = null;
        app.keywords = {
            updateValue: function(field, tagsContainer, keywordField, filterField, wrapper){
                var i, values=[], nodes = wrapper.querySelectorAll('.super-autosuggest-tags > span');
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
                    tagsContainer = wrapper.querySelector('.super-autosuggest-tags'),
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
                    var node = document.createElement('span');
                    node.className = 'super-noselect super-keyword-tag';
                    node.setAttribute('sfevents', '{"click":"keywords.remove"}');
                    node.dataset.value = value;
                    node.title = 'remove this tag';
                    node.innerHTML = searchValue;
                    filterField.parentNode.insertBefore(node, filterField.nextElementSibling);
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
                    tagsContainer = wrapper.querySelector('.super-autosuggest-tags');

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
                    tagsContainer = wrapper.querySelector('.super-autosuggest-tags'),
                    keywordField = wrapper.querySelector('.super-shortcode-field'),
                    filterField = wrapper.querySelector('.super-keyword-filter'),
                    max = (keywordField.dataset.maxlength ? parseInt(keywordField.dataset.maxlength, 10) : 0),
                    nodes = wrapper.querySelectorAll('.super-dropdown-list .super-item');

                if(method=='free'){
                    if(splitMethod=='both') tags = target.value.split(/[ ,]+/);
                    if(splitMethod=='comma') tags = target.value.split(/[,]+/);
                    if(splitMethod=='space') tags = target.value.split(/[ ]+/);
                    if(tags.length>1){
                        tag = tags[0];
                        // First check if already exists
                        if(keywordField.value.split(',').indexOf(tag)===-1){
                            if(typeof duplicates[tag]==='undefined'){
                                counter = tagsContainer.querySelectorAll('.super-keyword-tag').length;
                                if(max===0 || counter<max){
                                    if(splitMethod!='comma') tag = tag.replace(/ /g,'');
                                    if( (tag!=='') && (tag.length>1) ) {
                                        var node = document.createElement('span');
                                        node.className = 'super-noselect super-keyword-tag';
                                        node.setAttribute('sfevents', '{"click":"keywords.remove"}');
                                        node.dataset.value = tag;
                                        node.innerHTML = tag;
                                        filterField.parentNode.insertBefore(node, filterField);
                                        target.classList.add('super-active');
                                    }
                                }
                            }
                            duplicates[tag] = tag;
                        }
                        app.keywords.updateValue(field, tagsContainer, keywordField, target, wrapper);
                    }
                }else{
                    if (app.autosuggestTagsTimeout !== null) clearTimeout(app.autosuggestTagsTimeout);
                    app.autosuggestTagsTimeout = setTimeout(function () {
                        value = target.value.toString().toLowerCase();
                        if (value === '') {
                            parent.classList.remove('super-string-found');
                            parent.classList.remove('super-no-match');
                            return false;
                        }
                        for (i = 0; i < nodes.length; i++) {
                            var stringValue = nodes[i].dataset.searchValue.toString();
                            var stringValue_l = stringValue.toLowerCase();
                            var isMatch = false;
                            if(keywordField.dataset.logic=='start'){
                                // Starts with filter logic:
                                isMatch = stringValue_l.startsWith(value);
                            }else{
                                // Contains filter logic:
                                isMatch = stringValue_l.indexOf(value) !== -1;
                            }
                            if( isMatch===true ) {
                                itemsToShow.push(nodes[i]);
                                regex = RegExp([value].join('|'), 'gi');
                                text = stringValue.split(';')[0];
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
                            parent.classList.remove('super-no-match');
                        } else {
                            parent.classList.remove('super-string-found');
                            parent.classList.add('super-no-match');
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
                            _currentFunc(e, target, eventType, actions);
                        } else {
                            // eslint-disable-next-line no-console
                            console.log('Function ' + actions + '() is undefined!');
                        }
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
                // Trigger event(s)
                if (typeof target.attributes.sfevents !== 'undefined') app.triggerEvent(e, target, eventType);
            });
        });
    })(jQuery);
})(jQuery);
