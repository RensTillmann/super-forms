(function () {
    "use strict"; // Minimize mutable state :)
    // Define all the events for our elements
    var app = {};
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
    // Remove all elements
    app.remove = function (e) {
        if (typeof e === 'undefined' || !e) return true;
        if(typeof e.length === 'undefined'){
            e.remove();
        }
        if (e.length) {
            for (var i = 0; i < e.length; i++) {
                e[i].remove();
            }
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

    // UI    
    app.ui = {
        backdrop: {
            className: 'super-stripe-backdrop',
            add: function () {
                var node = document.createElement('div');
                node.className = app.ui.backdrop.className;
                node.style.position = 'fixed';
                node.style.zIndex = 9000000;
                node.style.left = '0px';
                node.style.top = '0px';
                node.style.width = '100%';
                node.style.height = '100%';
                node.style.backgroundColor = '#000';
                node.style.opacity = '0.2';
                document.body.appendChild(node);
            },
            remove: function () {
                app.remove(app.q('.' + app.ui.backdrop.className));
            }
        },
        modal: {
            wrapperClassName: 'super-stripe-modal-wrapper',
            containerClassName: 'super-stripe-modal',
            close: function () {
                app.remove(app.q('.' + app.ui.modal.wrapperClassName));
                app.ui.backdrop.remove();
            },
            reposition: function (modal) {
                if (typeof modal === 'undefined') modal = app.q('.' + app.ui.modal.containerClassName);
                if (modal) {
                    var marginTop = (window.innerHeight - parseFloat(modal.offsetHeight).toFixed(0)) / 2;
                    if (marginTop < 50) marginTop = 0;
                    modal.style.marginTop = marginTop + 'px';
                }
            },
            open: function (e, target, eventType, attr) {
                document.body.style.overflow = 'hidden';
                document.body.style.overscrollBehaviorY = 'none';
                console.log(e, target, eventType, attr);
                // Get Raw JSON data
                var html = '',
                    json = JSON.parse(app.api.rawJSON),
                    currency = 'EUR', //json.currency.toUpperCase(),
                    symbol = ( typeof OSREC.CurrencyFormatter.symbols[currency] !== 'undefined' ? OSREC.CurrencyFormatter.symbols[currency] : currency + '' ),
                    amount = json.amount/100,
                    amountPretty = OSREC.CurrencyFormatter.format(amount, {pattern: '#,##0.00'}),
                    modalWrapper = document.createElement('div'),
                    modalContainer = document.createElement('div');

                modalWrapper.className = app.ui.modal.wrapperClassName;
                modalContainer.className = app.ui.modal.containerClassName;
                if (attr.type == 'refundPayment') {
                    var modalTitle = 'Refund payment';
                    var modalInfo = 'Refunds take 5-10 days to appear on a customer\'s statement.';
                    var modalActions = {
                        "cancel": {
                            name: "Cancel",
                            color: "default",
                            events: {click: {"ui.modal.close":{}}}
                        },
                        "refund": {
                            name: "Refund",
                            color: "primary",
                            events: {click: {"api.refund.post":{}}}
                        }
                    };
                    var modalFields = {
                        "amount": {
                            label: "Refund",
                            type: "currency"
                        },
                        "reason": {
                            label: "Reason",
                            type: "select",
                            options: {
                                null: "Select a reason",
                                duplicate: "Duplicate",
                                fraudulent: "Fraudulent",
                                requested_by_customer: "Requested by customer",
                                other: "Other"
                            }
                        },
                        "payment_intent": {
                            type: "hidden"
                        }
                    };
                }
                if (modalTitle) html += '<div class="super-stripe-modal-title">' + modalTitle + '</div>';
                if (modalInfo) {
                    html += '<div class="super-stripe-modal-info">';
                    html += '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;">';
                    html += '<path d="M9 8a1 1 0 0 0-1-1H5.5a1 1 0 1 0 0 2H7v4a1 1 0 0 0 2 0zM4 0h8a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4zm4 5.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" fill-rule="evenodd"></path>';
                    html += '</svg>';
                    html += modalInfo;
                    html += '</div>';
                }
                if (modalFields) {
                    html += '<div class="super-stripe-modal-fields">';
                    Object.keys(modalFields).forEach(function (key) {
                        var field = modalFields[key],
                            type = field.type;

                        html += '<div class="super-stripe-modal-field">';
                        if (!field.label) field.label = '';
                        html += '<label class="super-stripe-field-label">' + field.label + '</label>';
                        html += '<div class="super-stripe-field-wrapper super-field-type-' + type + '">';
                        if (type === 'hidden') {
                            html += '<input type="hidden" name="' + key + '" />';
                        }
                        if (type === 'select') {
                            html += '<select name="' + key + '" sfevents=\'{"change":{"ui.modal.refundReasonChanged":{}}}\'>';
                            Object.keys(field.options).forEach(function (optionKey) {
                                html += '<option value="' + optionKey + '">' + field.options[optionKey] + '</option>';
                            });
                            html += '</select>';
                            html += '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" style="height: 12px; width: 12px;"><path d="M11.891 9.992a1 1 0 1 1 1.416 1.415l-4.3 4.3a1 1 0 0 1-1.414 0l-4.3-4.3A1 1 0 0 1 4.71 9.992l3.59 3.591 3.591-3.591zm0-3.984L8.3 2.417 4.709 6.008a1 1 0 0 1-1.416-1.415l4.3-4.3a1 1 0 0 1 1.414 0l4.3 4.3a1 1 0 1 1-1.416 1.415z"></path></svg>';
                        }
                        if (type === 'currency') {
                            // // TJS (Tajikistani somoni) is not supported, we have to use our own formatting
                            // // [ЅM](! #,##0.00)
                            // if (currency == 'TJS') {
                            //     // eslint-disable-next-line no-undef
                            //     value = OSREC.CurrencyFormatter.format(amount, {
                            //         currency: currency,
                            //         symbol: 'ЅM',
                            //         pattern: '! #,##0.00'
                            //     });
                            // }
                            // //  RON (Romanian leu) is not supported, we have to use our own formatting
                            // // [lei](! #,##0.00)
                            // if (currency == 'RON') { // Romanian leu
                            //     // eslint-disable-next-line no-undef
                            //     value = OSREC.CurrencyFormatter.format(amount, {
                            //         currency: currency,
                            //         symbol: 'lei',
                            //         pattern: '! #,##0.00'
                            //     });
                            // }
                            html += '<input type="text" name="' + key + '" value="' + amountPretty + '" sfevents=\'{"keyup":{"api.refund.validateAmount":{"max":'+amount+',"currency":"'+currency+'","symbol":"'+symbol+'"}}}\' />';
                            html += '<span class="super-stripe-currency">' + currency + '</span>';
                        }
                        html += '</div>';
                        html += '</div>';
                    });
                    html += '</div>';
                }
                if (modalActions) {
                    html += '<div class="super-stripe-modal-actions">';
                    Object.keys(modalActions).forEach(function (key) {
                        var action = modalActions[key];
                        html += '<div class="super-stripe-action-btn super-stripe-'+action.color+'" sfevents=\''+JSON.stringify(action.events)+'\'>';
                        if(key==='refund') action.name = action.name+' '+symbol+amountPretty;    
                        html += '<span>'+action.name+'</span>';
                        html += '</div>';
                    });
                    html += '</div>';
                }

                modalContainer.innerHTML = html;
                modalWrapper.appendChild(modalContainer);
                document.body.appendChild(modalWrapper);
                if(app.q('.super-field-type-currency input')){
                    // eslint-disable-next-line no-undef
                    jQuery(app.q('.super-field-type-currency input')).maskMoney({
                        affixesStay: true,
                        allowNegative: true,
                        thousands: ',',
                        decimal: '.',
                        precision: 2
                    });
                }
                app.ui.modal.reposition(modalContainer);
                app.ui.contextMenu.close();
                app.ui.backdrop.add();
            },
            refundReasonChanged: function (e, target, eventType, attr) {
                console.log(e, target, eventType, attr);
                // eslint-disable-next-line no-undef
                var node, i18n = super_stripe_dashboard_i18n.refundReasons;
                if (target.parentNode.querySelector('textarea')) {
                    target.parentNode.querySelector('textarea').remove();
                }
                if(target.parentNode.querySelector('i')) {
                    target.parentNode.querySelector('i').remove();
                }
                switch (target.value) {
                    case 'duplicate':
                        node = document.createElement('textarea');
                        node.placeholder = i18n.duplicate;
                        target.parentNode.appendChild(node);
                        node.focus();
                        break;
                    case 'fraudulent':
                        node = document.createElement('textarea');
                        node.placeholder = i18n.fraudulent;
                        target.parentNode.appendChild(node);
                        node.focus();
                        break;
                    case 'requested_by_customer':
                        node = document.createElement('textarea');
                        node.placeholder = i18n.requested_by_customer;
                        target.parentNode.appendChild(node);
                        node.focus();
                        break;
                    case 'other':
                        node = document.createElement('textarea');
                        node.placeholder = i18n.other;
                        target.parentNode.appendChild(node);
                        node.focus();
                        node = document.createElement('i');
                        node.innerText = i18n.other_note;
                        target.parentNode.appendChild(node);
                        break;
                }
            }
        },
        contextMenu: {
            className: 'super-stripe-contextmenu',
            close: function () {
                var node = app.q('.' + app.ui.contextMenu.className);
                if (node) node.remove();
                app.removeClass(app.qa('.super-contextmenu-active'), 'super-contextmenu-active');
            },
            open: function (e, target, eventType, attr) {
                console.log(eventType, attr);
                if (attr.type == 'actions') {
                    // Before we do anything, determine if this button was already clicked, and has active state
                    // If it has active state, we can simple close the context menu and do nothing
                    console.log(e, target, eventType, attr);
                    if (target.classList.contains('super-contextmenu-active')) {
                        // Close (delete) any existing context menu
                        app.ui.contextMenu.close();
                    } else {
                        // First close (delete) existing one
                        app.ui.contextMenu.close();
                        // Set active state on button
                        app.addClass(target, 'super-contextmenu-active');
                        // Open up new context menu
                        var contextMenu = document.createElement('div');
                        contextMenu.className = app.ui.contextMenu.className + ' super-stripe-contextmenu-actions';
                        var html = '';
                        html += '<span>ACTIONS</span>';
                        html += '<div sfevents=\'{"click":{"ui.modal.open":{"type":"refundPayment"}}}\'>Refund payment...</div>';
                        html += '<div sfevents=\'{"click":{"api.copyPaymentID":""}}\'>Copy payment ID</div>';
                        html += '<divider></divider>';
                        html += '<span>CONNECTIONS</span>';
                        var row = target.closest('.super-stripe-row');
                        app.api.rawJSON = row.querySelector('.super-stripe-raw').value;

                        var testData = row.querySelector('.super-stripe-testdata').length;
                        var customerID = row.querySelector('.super-stripe-customer').customer;
                        if (customerID) {
                            html += '<a target="_blank" href="https://dashboard.stripe.com/' + (testData !== 0 ? 'test/' : '') + 'customers/' + customerID + '">View customer</a>';
                        }
                        html += '<a target="_blank" href="https://dashboard.stripe.com/' + (testData !== 0 ? 'test/' : '') + 'payments/' + row.id + '">View payment details</a>';
                        contextMenu.innerHTML = html;

                        // Get the position relative to the viewport (i.e. the window)
                        var offset = target.getBoundingClientRect();
                        var target_absolute_position_left = offset.left + (offset.width / 2);
                        //var margins = getComputedStyle(target);
                        //var target_height = target.offsetHeight + parseInt(margins.marginTop) + parseInt(margins.marginBottom);
                        var w = window,
                            d = document,
                            dE = d.documentElement,
                            g = d.getElementsByTagName('body')[0],
                            window_width = w.innerWidth || dE.clientWidth || g.clientWidth,
                            scrollTop = window.pageYOffset || (document.documentElement || document.body.parentNode || document.body).scrollTop;
                        contextMenu.style.position = 'absolute';
                        contextMenu.style.top = 0;
                        contextMenu.style.left = 0;
                        // Append to body
                        document.body.appendChild(contextMenu);
                        var initial_width = contextMenu.offsetWidth;
                        contextMenu.style.top = offset.top + scrollTop + (offset.height) + 'px';
                        contextMenu.style.transform = 'translateX(-17px) translateY(15px)';
                        //contextMenu.style.top = offset.top+scrollTop+'px';
                        //contextMenu.style.transform = 'translateX(-50%) translateY(-150%)';
                        contextMenu.style.left = target_absolute_position_left + 'px';
                        // Check if we can't position the element at top or bottom because of overlapping window
                        // The tooltip could possibly be cut off if we do not check this
                        if (window_width < target_absolute_position_left + initial_width) {
                            // We have to position the tooltip to the left side of the target
                            contextMenu.style.transform = null;
                            contextMenu.style.left = (offset.left - initial_width - 30) + 'px';
                            contextMenu.classList.remove('sb-bottom');
                            contextMenu.classList.add('sb-left');
                        }
                        console.log('Console log, open Actions context menu');
                        console.log(e, target, eventType, attr);
                    }
                }
            }
        },

        // Switch TABs
        tabs: {
            open: function (e, target) {
                var index = app.index(target);
                app.removeClass(app.qap('.super-stripe-tab.super-active, .super-stripe-tabs-content > div.super-active'), 'super-active');
                app.addClass(app.qap(':scope > div', '.super-stripe-tabs-content')[index], 'super-active');
                app.addClass(target, 'super-active');
            }
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

        app.itemWidth = function (node) {
            var style = window.getComputedStyle ? getComputedStyle(node, null) : node.currentStyle,
                marginLeft = parseFloat(style.marginLeft) || 0,
                marginRight = parseFloat(style.marginRight) || 0;
            if (node.classList.contains('carouseljs-wrapper')) {
                var paddingLeft = parseFloat(style.paddingLeft) || 0,
                    paddingRight = parseFloat(style.paddingRight) || 0;
                return node.offsetWidth + (marginLeft + marginRight) - (paddingLeft + paddingRight);
            }
            return node.offsetWidth + (marginLeft + marginRight);
        },

        app.resizeColumns = function (parent) {
            console.log(parent);
            //if(!parent.classList.contains('super-initialized')){
            var x, y, widths = [],
                itemWidth, columns, rows = app.qap('.super-stripe-row', parent);
            for (x = 0; x < rows.length; x++) {
                columns = app.qap('.super-stripe-column', rows[x]);
                for (y = 0; y < columns.length; y++) {
                    if (typeof widths[y] === 'undefined') widths[y] = 0;
                    itemWidth = app.itemWidth(columns[y]);
                    if (itemWidth > widths[y]) widths[y] = itemWidth;
                }
            }
            for (x = 0; x < rows.length; x++) {
                columns = app.qap('.super-stripe-column', rows[x]);
                for (y = 0; y < columns.length; y++) {
                    columns[y].style.maxWidth = widths[y] + 'px';
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
        rawJSON: {},
        // Copy Payment ID
        copyPaymentID: function (e, target) {
            var tmpHTML, row = app.q('.super-contextmenu-active').closest('.super-stripe-row'),
                node = document.createElement('input');
            node.type = 'text';
            node.value = row.id;
            target.parentNode.querySelector('.super-stripe-copy');
            node.className = 'super-stripe-copy';
            app.remove(app.qap('.super-stripe-copy', target.parentNode));
            target.parentNode.insertBefore(node, target.nextSibling);
            tmpHTML = target.innerHTML;
            target.innerHTML = 'Copied!';
            app.addClass(target, 'super-stripe-copied');
            setTimeout(function () {
                target.innerHTML = tmpHTML;
                app.removeClass(target, 'super-stripe-copied');
            }, 3000);
            node.focus();
            node.select();
            document.execCommand("copy");
        },

        // Refund
        refund: {
            post: function(){
                alert('Post refund request');
            },
            validateAmount: function (e, target, eventType, attr) {
                var amount = parseFloat(OSREC.CurrencyFormatter.parse(target.value));
                var msg = '';
                if(amount<=0) msg = 'Refund cannot be '+parseFloat(attr.max).toFixed(2)+' '+attr.currency+'.';
                if(amount > parseFloat(attr.max))  msg = 'Refund cannot be more than '+parseFloat(attr.max).toFixed(2)+' '+attr.currency+'.';
                app.remove(app.qa('.super-stripe-error'));
                app.q('.super-stripe-modal-actions .super-stripe-primary').innerHTML = 'Refund '+attr.symbol+target.value;
                if(msg!==''){
                    var node = document.createElement('p');
                    node.className = 'super-stripe-error';
                    node.innerHTML = msg;
                    target.parentNode.appendChild(node);
                }
            }
        },

        // Invoice
        invoice: {
            // Download as PDF
            pdf: function (e, target, eventType, attr) {
                // "invoice_pdf": "https://pay.stripe.com/invoice/invst_LC4o7wAvPzS3pCSZqCQ7PqaA0X/pdf",
                app.api.handler({
                    type: 'invoice.pdf',
                    id: attr.invoiceId
                });
            },
            // View invoice online
            online: function (e, target, eventType, attr) {
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
                var parentNode = app.q('.super-stripe-transactions .super-stripe-table-rows');
                // Check if we can find the last ID, if not then just leave starting_after blank
                var newRow = document.createElement('div');
                newRow.className = 'super-stripe-row';
                newRow.id = payload.id;
                // column.innerHTML = '<a target="_blank" href="https://dashboard.stripe.com/payments/' + payload.id + '">' + payload.id + '</a>';

                // Raw JSON data
                var node = document.createElement('textarea');
                node.className = 'super-stripe-raw';
                node.value = payload.raw;
                newRow.appendChild(node);

                // Actions
                var column = document.createElement('div');
                var columnClass = 'super-stripe-column ';
                column.className = columnClass + 'super-stripe-actions';
                var html = '';
                html += '<div class="super-stripe-action-btn" sfevents=\'{"click":{"ui.contextMenu.open":{"type":"actions"}}}\'>';
                html += '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;"><path d="M2 10a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4z" fill-rule="evenodd"></path></svg>';
                html += '</div>';
                html += '<div class="super-stripe-action-options">';
                var receiptUrl = (payload.charges && payload.charges.data && payload.charges.data[0] ? payload.charges.data[0].receipt_url : '');
                if (payload.receipt_url) receiptUrl = payload.receipt_url;
                if (receiptUrl) html += '<a target="_blank" href="' + (receiptUrl) + '">View Receipt</a><br />';
                if (payload.invoice) {
                    html += '<span class="super-stripe-invoice-btn" sfevents=\'{"click":{"app.api.invoice.online":{"invoiceId":"' + payload.invoice + '"}}}\'>Online Invoice</span><br />';
                    html += '<span class="super-stripe-invoice-btn" sfevents=\'{"click":{"app.api.invoice.pdf":{"invoiceId":"' + payload.invoice + '"}}}\'>PDF Invoice</span><br />';
                }
                html += '</div>';
                column.innerHTML = html
                newRow.appendChild(column);

                // Amount
                html = '';
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-amount super-stripe-status';
                html += payload.amountFormatted;

                // Status
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
                        if (payload.amount_refundedFormatted) {
                            $label = 'Partial refund';
                            $labelColor = '#3d4eac;';
                            $title = ' title="' + payload.amount_refundedFormatted + ' ' + 'was refunded"';
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
                if (!payload.livemode) {
                    html += '<span class="super-stripe-testdata">TEST DATA</span>';
                }
                column.innerHTML = html;
                newRow.appendChild(column);

                // Description
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-description';
                column.innerHTML = (payload.description ? payload.description : '');
                newRow.appendChild(column);

                // Customer
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-customer';
                html = '';
                console.log(payload.customer);
                if (payload.customer) {
                    column.customer = payload.customer.id;
                    html += '<a target="_blank" href="https://dashboard.stripe.com/' + (!payload.livemode ? 'test/' : '') + 'customers/' + payload.customer.id + '">';
                    if (payload.customer.email) {
                        html += payload.customer.email
                    } else {
                        html += payload.customer.id;
                    }
                    html += '</a>';
                }
                column.innerHTML = html;
                newRow.appendChild(column);

                // Shipping address
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-shipping';
                column.innerHTML = payload.shipping;
                newRow.appendChild(column);

                // Payment Method
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-method';
                html = '';
                console.log(payload);
                if (payload.charges && payload.charges.data && payload.charges.data.payment_method_details) {
                    console.log(methodType);
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

                // Date
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-date';
                column.innerHTML = payload.createdFormatted;
                newRow.appendChild(column);

                parentNode.appendChild(newRow);
                app.resizeColumns(parentNode.parentNode);
                app.addClass(app.q('.super-stripe-transactions'), 'super-initialized');
            },

            products: function (payload) {
                var parentNode = app.q('.super-stripe-products > table > tbody');
                var newRow = document.createElement('tr');

                var column = document.createElement('td');
                column.className = 'super-stripe-status';
                column.innerHTML = payload.status;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-amount';
                column.innerHTML = payload.amountFormatted;
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
                column.className = 'super-stripe-status';
                column.innerHTML = payload.status;
                newRow.appendChild(column);

                column = document.createElement('td');
                column.className = 'super-stripe-amount';
                column.innerHTML = payload.amountFormatted;
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
                if (this.readyState === 4) {
                    if (this.status === 200) {
                        // Success:
                        if (obj.type == 'invoice.online' || obj.type == 'invoice.pdf') {
                            console.log('just testing...');
                        } else {
                            try {
                                var payload = JSON.parse(this.response);
                            } catch (error) {
                                console.log(error);
                                alert(error);
                            }
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
    if (app.qa('.super-stripe-tabs-content > div').length) {
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
    }

    // Remove loader after data has been loaded from the API
    var removeLoader = setInterval(function () {
        var total = app.qa('.super-stripe-tabs-content > div').length;
        var found = app.qa('.super-stripe-tabs-content > .super-initialized').length;
        if (total > 0 && total == found) {
            app.removeClass(app.q('.super-stripe-dashboard'), 'super-loading');
            clearInterval(removeLoader);
        }
    }, 100);

    // Load more Transactions, Products, Customers
    app.loadMore = function (e, target, eventType, attr) {
        var nodes, lastChild, starting_after;
        if (attr.type == 'paymentIntents') nodes = app.qa('.super-stripe-transactions .super-stripe-row');
        if (attr.type == 'products') nodes = app.qa('.super-stripe-products .super-stripe-row');
        if (attr.type == 'customers') nodes = app.qa('.super-stripe-customers .super-stripe-row');
        lastChild = nodes[nodes.length - 1];
        starting_after = lastChild.id;
        app.api.payment_intents({
            type: attr.type,
            limit: 3,
            starting_after: starting_after
        });
    };

    // Trigger Events
    app.triggerEvent = function (e, target, eventType) {
        // Get element actions, and check for multiple event methods
        console.log(target);
        var actions, _event, _function, _currentFunc, sfevents;
        try {
            console.log(target.attributes.sfevents.value);
            sfevents = JSON.parse(target.attributes.sfevents.value);
        } catch (error) {
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
            Object.keys(actions).forEach(function (key) { // key = function name
                console.log('event fired: ', key, e, target, eventType);
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
                _currentFunc(e, target, eventType, actions[key]);
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
    app.events = {
        click: [
            'body',
            '.super-stripe-tab',
            '.super-stripe-load-more',
            '.super-stripe-invoice-btn',
            '.super-stripe-action-btn',
            '.' + app.ui.contextMenu.className + ' > div'
        ],
        change: [
            '.super-stripe-field-wrapper select'
        ],
        keyup: [
            '.super-field-type-currency input'
        ]
    };
    Object.keys(app.events).forEach(function (eventType) {
        var elements = app.events[eventType].join(", ");
        app.delegate(document, eventType, elements, function (e, target) {
            if (eventType == 'click') {
                // Close Context Menu if clicked outside
                if ((!app.inPath(e, app.ui.contextMenu.className)) && (!app.inPath(e, 'super-stripe-action-btn'))) {
                    // Close context menu
                    app.ui.contextMenu.close();
                }
                if (!app.inPath(e, app.ui.modal.containerClassName)) {
                    // Close modal
                    app.ui.modal.close();
                }
            }
            // Trigger event(s)
            if (typeof target.attributes.sfevents !== 'undefined') app.triggerEvent(e, target, eventType);
        });
    });

    // Upon resizing window reposition modal
    window.addEventListener("resize", function () {
        app.ui.modal.reposition();
    });

})();