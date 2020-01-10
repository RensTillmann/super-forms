(function () {
    "use strict"; // Minimize mutable state :)
    // Define all the events for our elements
    var app = {};
    app.currencies = {
            'ALL': 'Lek',
            'AFN': '؋',
            'ARS': '$',
            'AWG': 'ƒ',
            'AUD': '$',
            'AZN': '₼',
            'BSD': '$',
            'BBD': '$',
            'BYN': 'Br',
            'BZD': 'BZ$',
            'BMD': '$',
            'BOB': '$b',
            'BAM': 'KM',
            'BWP': 'P',
            'BGN': 'лв',
            'BRL': 'R$',
            'BND': '$',
            'KHR': '៛',
            'CAD': '$',
            'KYD': '$',
            'CLP': '$',
            'CNY': '¥',
            'COP': '$',
            'CRC': '₡',
            'HRK': 'kn',
            'CUP': '₱',
            'CZK': 'Kč',
            'DKK': 'kr',
            'DOP': 'RD$',
            'XCD': '$',
            'EGP': '£',
            'SVC': '$',
            'EUR': '€',
            'FKP': '£',
            'FJD': '$',
            'GHS': '¢',
            'GIP': '£',
            'GTQ': 'Q',
            'GGP': '£',
            'GYD': '$',
            'HNL': 'L',
            'HKD': '$',
            'HUF': 'Ft',
            'ISK': 'kr',
            'INR': '',
            'IDR': 'Rp',
            'IRR': '﷼',
            'IMP': '£',
            'ILS': '₪',
            'JMD': 'J$',
            'JPY': '¥',
            'JEP': '£',
            'KZT': 'лв',
            'KPW': '₩',
            'KRW': '₩',
            'KGS': 'лв',
            'LAK': '₭',
            'LBP': '£',
            'LRD': '$',
            'MKD': 'ден',
            'MYR': 'RM',
            'MUR': '₨',
            'MXN': '$',
            'MNT': '₮',
            'MZN': 'MT',
            'NAD': '$',
            'NPR': '₨',
            'ANG': 'ƒ',
            'NZD': '$',
            'NIO': 'C$',
            'NGN': '₦',
            'NOK': 'kr',
            'OMR': '﷼',
            'PKR': '₨',
            'PAB': 'B/.',
            'PYG': 'Gs',
            'PEN': 'S/.',
            'PHP': '₱',
            'PLN': 'zł',
            'QAR': '﷼',
            'RON': 'lei',
            'RUB': '₽',
            'SHP': '£',
            'SAR': '﷼',
            'RSD': 'Дин.',
            'SCR': '₨',
            'SGD': '$',
            'SBD': '$',
            'SOS': 'S',
            'ZAR': 'R',
            'LKR': '₨',
            'SEK': 'kr',
            'CHF': 'CHF',
            'SRD': '$',
            'SYP': '£',
            'TWD': 'NT$',
            'THB': '฿',
            'TTD': 'TT$',
            'TRY': '',
            'TVD': '$',
            'UAH': '₴',
            'GBP': '£',
            'USD': '$',
            'UYU': '$U',
            'UZS': 'лв',
            'VEF': 'Bs',
            'VND': '₫',
            'YER': '﷼',
            'ZWD': 'Z$'
        },
        // querySelector shorthand
        app.q = function (s) {
            return document.querySelector(s);
        };
    // Top element
    app.wrapper = app.q('.super-stripe-dashboard');
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
    // Remove class from elements
    app.removeClass = function (elements, class_name) {
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
    app.events = {
        click: [
            '.super-stripe-tab',
            '.super-stripe-load-more',
            '.super-stripe-invoice-btn'
        ],
    };
    // Switch TABs
    app.tabs = {
        open: function (e, target) {
            var index = app.index(target);
            app.removeClass(app.qap('.super-stripe-tab.super-active, .super-stripe-tabs-content > div.super-active'), 'super-active');
            app.addClass(app.qap(':scope > div', '.super-stripe-tabs-content')[index], 'super-active');
            app.addClass(target, 'super-active');
        }
    };
    app.serialize = function (obj, prefix) {
        var str = [],
            p;
        for (p in obj) {
            if (Object.prototype.hasOwnProperty.call(obj, p)) {
                //if( obj.hasOwnProperty(p) ) {
                var k = prefix ? prefix + "[" + p + "]" : p,
                    v = obj[p];
                str.push((v !== null && typeof v === "object") ? app.serialize(v, k) : encodeURIComponent(k) + "=" + encodeURIComponent(v));
            }
        }
        return str.join("&");
    },

    app.itemWidth = function(node){
        var style = window.getComputedStyle ? getComputedStyle(node, null) : node.currentStyle,
            marginLeft = parseFloat(style.marginLeft) || 0,
            marginRight = parseFloat(style.marginRight) || 0;
        if(node.classList.contains('carouseljs-wrapper')){
            var paddingLeft = parseFloat(style.paddingLeft) || 0,
                paddingRight = parseFloat(style.paddingRight) || 0;
            return node.offsetWidth+(marginLeft+marginRight)-(paddingLeft+paddingRight);
        } 
        return node.offsetWidth+(marginLeft+marginRight);
    },

    app.resizeColumns = function(parent){
        //debugger;
        console.log(parent);
        //if(!parent.classList.contains('super-initialized')){
            var x, y, widths = [], itemWidth, columns, rows = app.qap('.super-stripe-row', parent);
            for( x=0; x < rows.length; x++ ) {
                columns = app.qap('.super-stripe-column', rows[x]);
                for( y=0; y < columns.length; y++ ) {
                    if(typeof widths[y] === 'undefined') widths[y] = 0;
                    itemWidth = app.itemWidth(columns[y]);
                    if(itemWidth > widths[y]) widths[y] = itemWidth;
                }
            }
            for( x=0; x < rows.length; x++ ) {
                columns = app.qap('.super-stripe-column', rows[x]);
                for( y=0; y < columns.length; y++ ) {
                    columns[y].style.maxWidth = widths[y]+'px';
                }
            }
            console.log(widths);
            //var columns = app.qap('.super-stripe-column', parent);
            // // This is a page load
            // var widths = [];
            // var headings = app.qap('.super-stripe-heading, .super-stripe-column', parent);
            // var i = 0;
            // for( i=0; i < headings.length; i++ ) {
            //     widths[i] = app.itemWidth(headings[i]);
            // }
            // for( i=0; i < widths.length; i++) {
            //     console.log(widths[i], headings[i]);
            //     headings[i].dataset.width = widths[i];
            //     headings[i].style.width = widths[i]+'px';
            //     // var currentWidth = headings[i].dataset.width;
            //     // if( !currentWidth ) {
            //     // }
            // }
            // console.log(headings[i]);
            // console.log(app.itemWidth(headings[i]));
            // var newWidth = app.itemWidth(headings[i]);
            // var currentWidth = headings[i].dataset.width;
            // if( !currentWidth ) {
            //     headings[i].dataset.width = newWidth;
            //     headings[i].style.width = newWidth+'px';
            // }

        //}else{
            // Load more was called

        //}
        //console.log(parent.classList.contains('super-initialized'));

        // var columns = app.qap('.super-stripe-column');
        // console.log(headings);
        // console.log(columns);
    };

    // Get items from API
    app.api = {
        invoice: {
            pdf: function (e, target, type, attr) {
                // "invoice_pdf": "https://pay.stripe.com/invoice/invst_LC4o7wAvPzS3pCSZqCQ7PqaA0X/pdf",
                app.api.handler({
                    type: 'invoice.pdf',
                    id: attr.invoiceId
                });
            },
            online: function (e, target, type, attr) {
                // "hosted_invoice_url": "https://pay.stripe.com/invoice/invst_LC4o7wAvPzS3pCSZqCQ7PqaA0X",
                app.api.handler({
                    type: 'invoice.online',
                    id: attr.invoiceId
                });
            }
        },

        addRows: {
            paymentIntents: function (payload) {
                // eslint-disable-next-line no-undef
                var $declineCodes = super_stripe_dashboard_i18n.declineCodes;
                var currency_code = payload.currency.toUpperCase();
                var symbol = (app.currencies[currency_code] ? app.currencies[currency_code] : '');
                var parentNode = app.q('.super-stripe-transactions .super-stripe-table-rows');
                // Check if we can find the last ID, if not then just leave starting_after blank
                var newRow = document.createElement('div');
                newRow.className = 'super-stripe-row';
                var column = document.createElement('div');
                var columnClass = 'super-stripe-column ';
                column.className = columnClass+'super-stripe-id';
                column.innerHTML = '<a target="_blank" href="https://dashboard.stripe.com/payments/' + payload.id + '">' + payload.id + '</a>';
                newRow.appendChild(column);
                column = document.createElement('div');
                column.className = columnClass+'super-stripe-actions';
                var html = '';
                html += '<div calss="super-stripe-action-btn">';
                html += '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;"><path d="M2 10a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4z" fill-rule="evenodd"></path></svg>';
                html += '</div>';
                var receiptUrl = (payload.charges && payload.charges.data && payload.charges.data[0] ? payload.charges.data[0].receipt_url : '');
                if (payload.receipt_url) receiptUrl = payload.receipt_url;
                if (receiptUrl) html += '<a target="_blank" href="' + (receiptUrl) + '">View Receipt</a><br />';
                if (payload.invoice) {
                    html += '<span class="super-stripe-invoice-btn" sfevents=\'{"click":{"app.api.invoice.online":{"invoiceId":"' + payload.invoice + '"}}}\'>Online Invoice</span><br />';
                    html += '<span class="super-stripe-invoice-btn" sfevents=\'{"click":{"app.api.invoice.pdf":{"invoiceId":"' + payload.invoice + '"}}}\'>PDF Invoice</span><br />';
                }
                column.innerHTML = html
                newRow.appendChild(column);
                column = document.createElement('div');
                column.className = columnClass+'super-stripe-status';
                html = '';
                var $label = '',
                    $labelColor = '#4f566b;',
                    $title = '',
                    $class = '',
                    $pathFill = '#697386',
                    $path = 'M8 6.585l4.593-4.592a1 1 0 0 1 1.415 1.416L9.417 8l4.591 4.591a1 1 0 0 1-1.415 1.416L8 9.415l-4.592 4.592a1 1 0 0 1-1.416-1.416L6.584 8l-4.59-4.591a1 1 0 1 1 1.415-1.416z',
                    $bgColor = '#e3e8ee';

                if (payload.status == 'warning_needs_response') {
                    $label = 'Needs response';
                    $labelColor = '#983705;';
                    $class = ' super-stripe-needs-response';
                    $path = 'M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16zm0-2.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM8 2a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0V3a1 1 0 0 0-1-1z';
                    $pathFill = '#bb5504';
                    $bgColor = '#f8e5b9';
                }
                if (payload.status == 'canceled') {
                    $label = 'Canceled';
                    $title = ' title="Cancellation reason: ' + (payload.cancellation_reason) + '"';
                    $class = ' super-stripe-canceled';
                }
                if (payload.status == 'failed') {
                    $label = 'Failed';
                    $class = ' super-stripe-failed';
                    if (((payload['last_payment_error'])) && (($declineCodes[payload['last_payment_error']['decline_code']]))) {
                        $title = ' title="' + $declineCodes[payload['last_payment_error']['decline_code']]['desc'] + '"';
                    } else {
                        if (payload.outcome['seller_message']) {
                            $title = ' title="' + (payload.outcome['seller_message']) + '"';
                        } else {
                            if ((payload.charges) && (payload.charges['data'])) {
                                $title = ' title="' + (payload.charges['data'][0]['outcome']['seller_message']) + '"';
                            }
                        }
                    }
                }
                if (payload.status == 'requires_payment_method' || payload.status == 'requires_capture') {
                    if (((payload['last_payment_error'])) && (($declineCodes[payload['last_payment_error']['decline_code']]))) {
                        $class = ' super-stripe-failed';
                        $label = 'Failed';
                        $title = ' title="' + $declineCodes[payload['last_payment_error']['decline_code']]['desc'] + '"';
                    } else {
                        if (payload.status == 'requires_payment_method') {
                            $class = ' super-stripe-incomplete';
                            $label = 'Incomplete';
                            $title = ' title="The customer has not entered their payment method"';
                        } else {
                            $class = ' super-stripe-uncaptured';
                            $label = 'Uncaptured';
                            $title = ' title="Payment authorized, but not yet captured."';
                        }
                        $path = 'M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16zm1-8.577V4a1 1 0 1 0-2 0v4a1 1 0 0 0 .517.876l2.581 1.49a1 1 0 0 0 1-1.732z';
                    }
                }
                if (payload.status == 'succeeded') {
                    if (payload.refunded) {
                        $label = 'Refunded';
                        $title = '';
                        $class = ' super-stripe-refunded';
                        $pathFill = '#697386';
                        $path = 'M10.5 5a5 5 0 0 1 0 10 1 1 0 0 1 0-2 3 3 0 0 0 0-6l-6.586-.007L6.45 9.528a1 1 0 0 1-1.414 1.414L.793 6.7a.997.997 0 0 1 0-1.414l4.243-4.243A1 1 0 0 1 6.45 2.457L3.914 4.993z';
                    } else {
                        if (payload.amount_refunded) {
                            $label = 'Partial refund';
                            $labelColor = '#3d4eac;';
                            $title = ' title="' + (symbol + (payload.amount_refunded / 100) + ' ' + currency_code) + ' ' + 'was refunded"';
                            $class = ' super-stripe-partial-refund';
                            $pathFill = '#5469d4';
                            $path = 'M9 8a1 1 0 0 0-1-1H5.5a1 1 0 1 0 0 2H7v4a1 1 0 0 0 2 0zM4 0h8a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4zm4 5.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z';
                        } else {
                            $label = 'Succeeded';
                            $labelColor = '#3d4eac;';
                            $title = '';
                            $class = ' super-stripe-succeeded';
                            $pathFill = '#5469d4';
                            $bgColor = '#d6ecff';
                            $path = 'M5.297 13.213L.293 8.255c-.39-.394-.39-1.033 0-1.426s1.024-.394 1.414 0l4.294 4.224 8.288-8.258c.39-.393 1.024-.393 1.414 0s.39 1.033 0 1.426L6.7 13.208a.994.994 0 0 1-1.402.005z';
                        }
                    }
                }
                html += '<span' + $title + ' class="super-stripe-status' + $class + '" style="color:' + $labelColor + ';font-size:12px;padding:2px 8px 2px 8px;background-color:' + $bgColor + ';border-radius:20px;font-weight:500;">';
                html += $label;
                html += '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height:12px;width:12px;padding-left:3px;margin-bottom:-1px;">';
                html += '<path style="fill:' + $pathFill + ';" d="' + $path + '" fill-rule="evenodd"></path>';
                html += '</svg>';
                html += '</span>';
                column.innerHTML = html;
                newRow.appendChild(column);
                column = document.createElement('div');
                column.className = columnClass+'super-stripe-livemode';
                column.innerHTML = payload.livemode;
                newRow.appendChild(column);
                column = document.createElement('div');
                column.className = columnClass+'super-stripe-amount';
                column.innerHTML = symbol + payload.amount / 100 + ' ' + currency_code;
                newRow.appendChild(column);
                column = document.createElement('div');
                column.className = columnClass+'super-stripe-description';
                column.innerHTML = (payload.description ? payload.description : '');
                newRow.appendChild(column);
                column = document.createElement('div');
                column.className = columnClass+'super-stripe-customer';
                column.innerHTML = payload.customer;
                newRow.appendChild(column);
                column = document.createElement('div');
                column.className = columnClass+'super-stripe-shipping';
                column.innerHTML = payload.shipping;
                newRow.appendChild(column);
                column = document.createElement('div');
                column.className = columnClass+'super-stripe-method';

                html = '';
                if (payload.charges && payload.charges.data && payload.charges.data.payment_method_details) {
                    var methodType = payload.charges.data.payment_method_details.type; // card
                    var methodDetails = payload.charges.data.payment_method_details[methodType];
                    switch (methodType) {
                        case 'ach_credit_transfer':
                            html += methodDetails.bank_name + ' - ' + methodDetails.routing_number + ' - ' + methodDetails.swift_code + ' - ' + methodDetails.account_number;
                            break;

                        case 'ach_debit':
                            html += methodDetails.bank_name + ' - ' + methodDetails.last4;
                            break;
                        case 'alipay':
                            html += 'Alipay: ' + methodDetails.alipay;
                            break;
                        case 'bancontact':
                            html += methodDetails.bank_name + ' - ' + methodDetails.iban_last4;
                            break;
                        case 'card':
                            html += methodDetails.brand + ' - ' + methodDetails.last4;
                            break;
                        case 'card_present':
                            html += methodDetails.brand + ' / ' + methodDetails.last4;
                            break;
                        case 'eps':
                            html += 'EPS: ' + methodDetails.verified_name;
                            break;
                        case 'giropay':
                            html += 'Giropay: ' + methodDetails.bank_name + ' / ' + methodDetails.bic + ' / ' + methodDetails.verified_name;
                            break;
                        case 'ideal':
                            html += 'iDeal: ' + methodDetails.bank + ' / ' + methodDetails.iban_last4;
                            break;
                        case 'klarna':
                            html += 'Klarna: ' + methodDetails;
                            break;
                        case 'multibanco':
                            html += 'multibanco: ' + methodDetails.entity + ' / ' + methodDetails.reference;
                            break;
                        case 'p24':
                            html += 'przelewy24: ' + methodDetails.reference;
                            break;
                        case 'sepa_debit':
                            html += 'SEPA Debit: ' + methodDetails.last4;
                            break;
                        case 'sofort':
                            html += 'Sofort: ' + methodDetails.iban_last4;
                            break;
                        case 'stripe_account':
                            html += 'Stripe Account: ' + methodDetails;
                            break;
                        default:
                            // code block
                    }

                    // if(methodType=='card') 
                    //     html += methodDetails.brand + ' - ' + methodDetails.last4;
                    // if(methodType=='sepa_debit') 
                    //     html += 'SEPA Debit: ' + methodDetails.last4;
                    // if(methodType=='sepa_debit') 
                    //     html += 'SEPA Debit: ' + methodDetails.last4;
                    // if(methodType=='sofort') 
                    //     html += 'Sofort: ' + methodDetails.iban_last4;
                    // if(methodType=='stripe_account') 
                    //     html += 'Stripe Account: ' + methodDetails;

                    // methodDetails.brand // visa
                    // methodDetails.last4 // 4242
                }
                column.innerHTML = html;
                newRow.appendChild(column);

                column = document.createElement('div');
                column.className = columnClass+'super-stripe-date';
                column.innerHTML = payload.created;
                newRow.appendChild(column);

                parentNode.appendChild(newRow);
                app.resizeColumns(parentNode.parentNode);
                app.addClass(app.q('.super-stripe-transactions'), 'super-initialized');
            },

            products: function (payload) {
                var parentNode = app.q('.super-stripe-products > table > tbody');
                var newRow = document.createElement('tr');

                var column = document.createElement('td');
                column.className = 'super-stripe-id';
                column.innerHTML = payload.id;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-status';
                column.innerHTML = payload.status;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-livemode';
                column.innerHTML = payload.livemode;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-amount';
                column.innerHTML = payload.amount;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-description';
                column.innerHTML = payload.description;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-customer';
                column.innerHTML = payload.customer;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-shipping';
                column.innerHTML = payload.shipping;
                newRow.appendChild(column);

                parentNode.appendChild(newRow);
                app.addClass(app.q('.super-stripe-products'), 'super-initialized');
                //lastChild.parentNode.insertBefore(newRow, lastChild.nextSibling);
            },

            customers: function (payload) {
                var parentNode = app.q('.super-stripe-customers > table > tbody');
                var newRow = document.createElement('tr');

                var column = document.createElement('td');
                column.className = 'super-stripe-id';
                column.innerHTML = payload.id;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-status';
                column.innerHTML = payload.status;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-livemode';
                column.innerHTML = payload.livemode;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-amount';
                column.innerHTML = payload.amount;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-description';
                column.innerHTML = payload.description;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-customer';
                column.innerHTML = payload.customer;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-shipping';
                column.innerHTML = payload.shipping;
                newRow.appendChild(column);

                parentNode.appendChild(newRow);
                app.addClass(app.q('.super-stripe-customers'), 'super-initialized');
                //lastChild.parentNode.insertBefore(newRow, lastChild.nextSibling);
            }

        },
        handler: function (obj) {
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (this.readyState == 4) {
                    if (this.status == 200) {
                        // Success:
                        if (obj.type == 'invoice.online' || obj.type == 'invoice.pdf') {
                            console.log('just testing...');
                        } else {
                            var payload = JSON.parse(this.response);
                            for (var i = 0; i < payload.length; i++) {
                                app.api.addRows[obj.type](payload[i]);
                            }
                        }
                    }
                    // Complete:
                }
            };
            xhttp.onerror = function () {
                console.log(this);
                console.log("** An error occurred during the transaction");
            };
            // eslint-disable-next-line no-undef
            xhttp.open("POST", ajaxurl, true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
            var params = {
                action: 'super_stripe_api_handler',
                type: obj.type, // e.g: 'paymentIntents', 'products', 'customers'
                id: obj.id, // e.g: 'paymentIntents', 'products', 'customers'
                starting_after: (typeof obj.starting_after === 'undefined' ? '' : obj.starting_after)
            };
            xhttp.send(app.serialize(params));
        },
        payment_intents: function (obj) {
            app.api.handler(obj);
        }
    };

    // Load first items
    app.api.payment_intents({
        type: 'paymentIntents',
        limit: 3
    });
    app.api.payment_intents({
        type: 'products',
        limit: 3
    });
    app.api.payment_intents({
        type: 'customers',
        limit: 3
    });

    // Remove loader after data has been loaded from the API
    var removeLoader = setInterval(function () {
        var total = app.qa('.super-stripe-tabs-content > div').length;
        var found = app.qa('.super-stripe-tabs-content > .super-initialized').length;
        if (total == found) {
            app.removeClass(app.q('.super-stripe-dashboard'), 'super-loading');
            clearInterval(removeLoader);
        }
    }, 100);

    // Load more Transactions, Products, Customers
    app.loadMore = function (e, target, type, attr) {
        var nodes, lastChild, starting_after;
        if (attr.type == 'paymentIntents') nodes = app.qa('.super-stripe-transactions .super-stripe-id');
        if (attr.type == 'products') nodes = app.qa('.super-stripe-products .super-stripe-id');
        if (attr.type == 'customers') nodes = app.qa('.super-stripe-customers .super-stripe-id');
        lastChild = nodes[nodes.length - 1];
        starting_after = lastChild.innerText;
        app.api.payment_intents({
            type: attr.type,
            limit: 3,
            starting_after: starting_after
        });
    };

    // Trigger Events
    app.triggerEvent = function (e, target, event) {
        // Get element actions, and check for multiple event methods
        console.log(target);
        var actions, _event, _function, _currentFunc, sfevents = JSON.parse(target.attributes.sfevents.value);
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
        actions = sfevents[event];
        if (actions) {
            Object.keys(actions).forEach(function (key) { // key = function name
                console.log('event fired: ', key, e, target, event);
                _function = key.split('.');
                _currentFunc = app;
                for (var i = 0; i < _function.length; i++) {
                    // Skip if function name is 'app'
                    if (_function[i] == 'app') continue;
                    if (_currentFunc[_function[i]]) {
                        _currentFunc = _currentFunc[_function[i]];
                    } else {
                        console.log('Function ' + key + '() is undefined!');
                        break;
                    }
                }
                _currentFunc(e, target, event, actions[key]);
            });
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
    Object.keys(app.events).forEach(function (event) {
        var elements = app.events[event].join(", ");
        app.delegate(app.wrapper, event, elements, function (e, target) {
            if (typeof target.attributes.sfevents !== 'undefined') app.triggerEvent(e, target, event);
        });
    });
})();