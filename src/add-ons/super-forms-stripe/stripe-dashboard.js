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
        if (typeof e.length === 'undefined') {
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
        svg: {
            invoice: {
                html: '<svg aria-hidden="true" height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;"><path d="M15 2v14l-2.5-2-2.25 2L8 14l-2.25 2-2.25-2L1 16V2a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2zM4 6a1 1 0 1 0 0 2h8a1 1 0 0 0 0-2zm3 3a1 1 0 1 0 0 2h5a1 1 0 0 0 0-2zM4 3a1 1 0 1 0 0 2h8a1 1 0 0 0 0-2z" fill-rule="evenodd"></path></svg>'
            },
            customer: {
                html: '<svg class="super-stripe-customer" style="width: 16px;height: 16px;" height="16" width="16" xmlns="http://www.w3.org/2000/svg"><g fill="none"><path d="M13.445 13.861C12.413 12.885 10.362 12.22 8 12.22s-4.413.665-5.445 1.641a8 8 0 1 1 10.89 0zM8 9.231a3.077 3.077 0 1 0 0-6.154 3.077 3.077 0 0 0 0 6.154z" fill="#4f566b"></path><path d="M13.944 13.354A7.98 7.98 0 0 1 8 16a7.98 7.98 0 0 1-5.944-2.646C2.76 12.043 5.154 11.077 8 11.077s5.24.966 5.944 2.277z" fill="#4f566b"></path></g></svg>'
            },
            loader: {
                html: '<svg class="super-stripe-loader" viewBox="0 0 24 24"><g transform="translate(1 1)" fill-rule="nonzero" fill="none"><circle cx="11" cy="11" r="11"></circle><path d="M10.998 22a.846.846 0 0 1 0-1.692 9.308 9.308 0 0 0 0-18.616 9.286 9.286 0 0 0-7.205 3.416.846.846 0 1 1-1.31-1.072A10.978 10.978 0 0 1 10.998 0c6.075 0 11 4.925 11 11s-4.925 11-11 11z" fill="currentColor"></path></g></svg>',
                remove: function () {
                    app.remove(app.qa('svg.super-stripe-loader'));
                }
            },
            delete: {
                html: '<svg class="super-stripe-delete" height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;"><path d="M8 6.585l4.593-4.592a1 1 0 0 1 1.415 1.416L9.417 8l4.591 4.591a1 1 0 0 1-1.415 1.416L8 9.415l-4.592 4.592a1 1 0 0 1-1.416-1.416L6.584 8l-4.59-4.591a1 1 0 1 1 1.415-1.416z" fill-rule="evenodd"></path></svg>',
                remove: function () {
                    app.remove(app.qa('svg.super-stripe-delete'));
                }
            },
            risk: {
                not_assessed: {
                    html: '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 16px; width: 16px;"><path d="M8 0c1.857 1.667 4.024 2.667 6.5 3L14 8.5c-.287 3.89-2.556 6.289-6 7.5-3.385-1.215-5.677-3.674-6-7.5L1.5 3C3.979 2.667 6.145 1.667 8 0z" fill-rule="evenodd"></path></svg>'
                }
            },
            paymentMethods: {
                ideal: {
                    html: '<svg height="32" viewBox="0 0 32 32" width="32" xmlns="http://www.w3.org/2000/svg" style="height: 20px; width: 20px;"><g fill="none" fill-rule="evenodd"><path d="M0 0h32v32H0z" fill="#db308b"></path><path d="M17.876 8c2.394 0 4.39.639 5.771 1.847C25.209 11.213 26 13.283 26 16c0 5.383-2.657 8-8.124 8H7V8z" fill="#fff"></path><path d="M17.845 8.196c2.34 0 4.29.623 5.64 1.802 1.526 1.332 2.3 3.352 2.3 6.002 0 5.252-2.598 7.804-7.94 7.804H7.215V8.196zM18.074 7H6v18h12.074v-.003c2.636-.035 4.726-.68 6.209-1.92C26.086 21.57 27 19.188 27 16c0-1.524-.24-2.891-.715-4.062a7.404 7.404 0 0 0-1.993-2.834c-1.53-1.336-3.677-2.059-6.218-2.1z" fill="#000"></path><path d="M17.678 21.24h-3.53V10.524h3.53-.143c2.944 0 6.078 1.14 6.078 5.372 0 4.472-3.133 5.343-6.078 5.343h.143z" fill="#db4093"></path><path d="M9.085 21.099v-5.646h3.47v5.645h-3.47zm3.732-8.467c0 1.063-.88 1.925-1.965 1.925s-1.964-.862-1.964-1.925c0-1.063.88-1.925 1.964-1.925s1.965.862 1.965 1.925z" fill="#000"></path></g></svg>'
                },
                card: {
                    visa: {
                        html: '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="height: 20px; width: 20px;"><g fill="none" fill-rule="evenodd"><path d="M0 0h32v32H0z" fill="#00579f"></path><g fill="#fff" fill-rule="nonzero"><path d="M13.823 19.876H11.8l1.265-7.736h2.023zm7.334-7.546a5.036 5.036 0 0 0-1.814-.33c-1.998 0-3.405 1.053-3.414 2.56-.016 1.11 1.007 1.728 1.773 2.098.783.379 1.05.626 1.05.963-.009.518-.633.757-1.216.757-.808 0-1.24-.123-1.898-.411l-.267-.124-.283 1.737c.475.213 1.349.403 2.257.411 2.123 0 3.505-1.037 3.521-2.641.008-.881-.532-1.556-1.698-2.107-.708-.354-1.141-.593-1.141-.955.008-.33.366-.667 1.165-.667a3.471 3.471 0 0 1 1.507.297l.183.082zm2.69 4.806l.807-2.165c-.008.017.167-.452.266-.74l.142.666s.383 1.852.466 2.239h-1.682zm2.497-4.996h-1.565c-.483 0-.85.14-1.058.642l-3.005 7.094h2.123l.425-1.16h2.597c.059.271.242 1.16.242 1.16h1.873zm-16.234 0l-1.982 5.275-.216-1.07c-.366-1.234-1.515-2.575-2.797-3.242l1.815 6.765h2.14l3.18-7.728z"></path><path d="M6.289 12.14H3.033L3 12.297c2.54.641 4.221 2.189 4.912 4.049l-.708-3.556c-.116-.494-.474-.633-.915-.65z"></path></g></g></svg>'
                    },
                    amex: {
                        html: '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="height: 20px; width: 20px;"><g fill="none" fill-rule="evenodd"><path fill="#0193CE" d="M0 0h32v32H0z"></path><path d="M17.79 18.183h4.29l1.31-1.51 1.44 1.51h1.52l-2.2-2.1 2.21-2.27h-1.52l-1.44 1.51-1.26-1.5H17.8v-.85h4.68l.92 1.18 1.09-1.18h4.05l-3.04 3.11 3.04 2.94h-4.05l-1.1-1.17-.92 1.17h-4.68v-.84zm3.67-.84h-2.53v-.84h2.36v-.83h-2.36v-.84h2.7l1.01 1.26-1.18 1.25zm-14.5 1.68h-3.5l2.97-6.05h2.8l.35.67v-.67h3.5l.7 1.68.7-1.68h3.31v6.05h-2.63v-.84l-.34.84h-2.1l-.35-.84v.84H8.53l-.35-1h-.87l-.35 1zm9.96-.84v-4.37h-1.74l-1.4 3.03-1.41-3.03h-1.74v4.04l-2.1-4.04h-1.4l-2.1 4.37h1.23l.35-1h2.27l.35 1h2.43v-3.36l1.6 3.36h1.05l1.57-3.36v3.36h1.04zm-8.39-1.85l-.7-1.85-.87 1.85h1.57z" fill="#FFF"></path></g></svg>'
                    },
                    mastercard: {
                        html: '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="height: 20px; width: 20px;"><g fill="none" fill-rule="evenodd"><path d="M0 0h32v32H0z" fill="#000"></path><g fill-rule="nonzero"><path d="M13.02 10.505h5.923v10.857H13.02z" fill="#ff5f00"></path><path d="M13.396 15.935a6.944 6.944 0 0 1 2.585-5.43c-2.775-2.224-6.76-1.9-9.156.745s-2.395 6.723 0 9.368 6.38 2.969 9.156.744a6.944 6.944 0 0 1-2.585-5.427z" fill="#eb001b"></path><path d="M26.934 15.935c0 2.643-1.48 5.054-3.81 6.21s-5.105.851-7.143-.783a6.955 6.955 0 0 0 2.587-5.428c0-2.118-.954-4.12-2.587-5.429 2.038-1.633 4.81-1.937 7.142-.782s3.811 3.566 3.811 6.21z" fill="#f79e1b"></path></g></g></svg>'
                    }
                }
            }
        },
        toast: {
            show: function (payload) {
                app.remove(app.qa('.super-stripe-toast-wrapper')); // First remove any existing toast
                var toastWrapper = document.createElement('div'),
                    // eslint-disable-next-line no-undef
                    toastText = OSREC.CurrencyFormatter.format(payload.amount / 100, {
                        currency: payload.currency
                    }) + ' Refunded',
                    toastURL = 'https://dashboard.stripe.com/payments/' + payload.payment_intent,
                    toastLinkText = 'View';
                toastWrapper.className = 'super-stripe-toast-wrapper';
                toastWrapper.innerHTML = '<div class="super-stripe-toast"><span>' + toastText + '</span><a target="_blank" href="' + toastURL + '">' + toastLinkText + '</a><div class="super-stripe-toast-close" sfevents=\'{"click":{"ui.toast.close":{}}}\'>' + app.ui.svg.delete.html + '</div></div>';
                document.body.appendChild(toastWrapper);
                setTimeout(function () {
                    toastWrapper.style.bottom = '10px';
                }, 10);
            },
            close: function () {
                var toastWrapper = app.q('.super-stripe-toast-wrapper');
                toastWrapper.style.bottom = '';
                setTimeout(function () {
                    toastWrapper.remove();
                }, 500);
            }
        },
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
                    json = app.api.rawJSON,
                    currency = 'EUR', //json.currency.toUpperCase(),
                    // eslint-disable-next-line no-undef
                    symbol = (typeof OSREC.CurrencyFormatter.symbols[currency] !== 'undefined' ? OSREC.CurrencyFormatter.symbols[currency] : currency + ''),
                    amount = json.amount / 100,
                    // eslint-disable-next-line no-undef
                    amountPretty = OSREC.CurrencyFormatter.format(amount, {
                        pattern: '#,##0.00'
                    }),
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
                            events: {
                                click: {
                                    "ui.modal.close": {}
                                }
                            }
                        },
                        "refund": {
                            name: "Refund",
                            color: "primary",
                            events: {
                                click: {
                                    "api.refund.post": {
                                        "id": attr.id
                                    }
                                }
                            }
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
                            html += '<input type="text" name="' + key + '" value="' + amountPretty + '" sfevents=\'{"keyup":{"api.refund.validate.amount":{"max":' + amount + ',"currency":"' + currency + '","symbol":"' + symbol + '"}}}\' />';
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
                        html += '<div class="super-stripe-action-btn super-stripe-' + action.color + '" sfevents=\'' + JSON.stringify(action.events) + '\'>';
                        if (key === 'refund') action.name = action.name + ' ' + symbol + amountPretty;
                        html += '<span>' + action.name + '</span>';
                        html += '</div>';
                    });
                    html += '</div>';
                }

                modalContainer.innerHTML = html;
                modalWrapper.appendChild(modalContainer);
                document.body.appendChild(modalWrapper);
                if (app.q('.super-field-type-currency input')) {
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
            refundReasonChanged: function (e, target) {
                // Remove error
                if (target.value !== '' && target.value !== 'null') {
                    app.remove(app.qap('.super-stripe-error', target.closest('.super-stripe-modal-field')));
                    target.closest('.super-stripe-modal-field').classList.remove('super-error');
                }
                /*
                console.log(e, target, eventType, attr);
                // eslint-disable-next-line no-undef
                var node, i18n = super_stripe_dashboard_i18n.refundReasons;
                if (target.parentNode.querySelector('textarea')) {
                    target.parentNode.querySelector('textarea').remove();
                }
                if(target.parentNode.querySelector('i')) {
                    target.parentNode.querySelector('i').remove();
                }
                // Remove error
                if(target.value!=='' && target.value!=='null'){
                    app.remove(app.qap('.super-stripe-error', target.closest('.super-stripe-modal-field')));
                    target.closest('.super-stripe-modal-field').classList.remove('super-error');
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
                */
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
                if (attr.type === 'actions') {
                    // Before we do anything, determine if this button was already clicked, and has active state
                    // If it has active state, we can simple close the context menu and do nothing
                    console.log(e, target, eventType, attr);
                    if (target.closest('.super-stripe-row').classList.contains('super-contextmenu-active')) {
                        // Close (delete) any existing context menu
                        app.ui.contextMenu.close();
                    } else {
                        // First close (delete) existing one
                        app.ui.contextMenu.close();
                        // Set active state on button
                        app.addClass(target.closest('.super-stripe-row'), 'super-contextmenu-active');
                        // Open up new context menu
                        var contextMenu = document.createElement('div');
                        contextMenu.className = app.ui.contextMenu.className + ' super-stripe-contextmenu-actions';
                        var row = target.closest('.super-stripe-row');
                        app.api.rawJSON = JSON.parse(row.querySelector('.super-stripe-raw').value);
                        var payload = app.api.rawJSON;
                        var html = '';
                        html += '<span>ACTIONS</span>';
                        // Edit
                        html += '<a target="_blank" href="https://dashboard.stripe.com/payments/' + row.id + '">Edit</a>';
                        // View Receipt
                        var receiptUrl = (payload.charges && payload.charges.data && payload.charges.data[0] ? payload.charges.data[0].receipt_url : '');
                        if (payload.receipt_url) receiptUrl = payload.receipt_url;
                        if (receiptUrl) html += '<a target="_blank" href="' + (receiptUrl) + '">View Receipt</a>';
                        // Download Invoice
                        if (payload.invoice) {
                            html += '<div sfevents=\'{"click":{"app.api.invoice.online":{"invoiceId":"' + payload.invoice + '"}}}\'>Online Invoice</div>';
                            html += '<div sfevents=\'{"click":{"app.api.invoice.pdf":{"invoiceId":"' + payload.invoice + '"}}}\'>PDF Invoice</div>';
                        }
                        html += '<div sfevents=\'{"click":{"ui.modal.open":{"type":"refundPayment","id":"' + row.id + '"}}}\'>Refund payment...</div>';
                        html += '<div sfevents=\'{"click":{"api.copyPaymentID":""}}\'>Copy payment ID</div>';
                        html += '<divider></divider>';
                        html += '<span>CONNECTIONS</span>';

                        var formID = 0, contactEntryID = 0, userID = 0;
                        if(payload.metadata){
                            // Based on form
                            if(payload.metadata.form_id) formID = payload.metadata.form_id;
                            // Contact entry
                            if(payload.metadata.contact_entry_id) contactEntryID = payload.metadata.contact_entry_id;
                            // WordPress user
                            if(payload.metadata.user_id) userID = payload.metadata.user_id;
                            // Registered user
                            if(payload.metadata.frontend_user_id) userID = payload.metadata.frontend_user_id;
                        }


                        console.log(payload.metadata);
                        console.log('formID:', formID);
                        console.log('contactEntryID:', contactEntryID);
                        console.log('userID:', userID);
                        if(contactEntryID!==0) html += '<a target="_blank" href="admin.php?page=super_contact_entry&id=' + contactEntryID + '">Contact Entry</a>';
                        if(payload.post_permalink){
                            html += '<a target="_blank" href="' + payload.post_permalink + '">WordPress Post</a>';
                        }
                        if(userID!==0) html += '<a target="_blank" href="user-edit.php?user_id=' + userID + '">WordPress User</a>';
                        if(payload.customer) html += '<a target="_blank" href="https://dashboard.stripe.com/customers/' + payload.customer + '">Stripe Customer</a>';
                        html += '<a target="_blank" href="https://dashboard.stripe.com/payments/' + row.id + '">View payment details</a>';
                        if(formID!==0) html += '<a target="_blank" href="admin.php?page=super_create_form&id=' + formID + '">Form</a>';
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
            // console.log(parent);
            // //if(!parent.classList.contains('super-initialized')){
            // var x, y, widths = [], itemWidth, columns, rows = app.qap('.super-stripe-row', parent);
            // for (x = 0; x < rows.length; x++) {
            //     columns = app.qap('.super-stripe-column', rows[x]);
            //     for (y = 0; y < columns.length; y++) {
            //         if (typeof widths[y] === 'undefined') widths[y] = 0;
            //         itemWidth = app.itemWidth(columns[y]);
            //         if (itemWidth > widths[y]) widths[y] = itemWidth;
            //     }
            // }
            // for (x = 0; x < rows.length; x++) {
            //     columns = app.qap('.super-stripe-column', rows[x]);
            //     for (y = 0; y < columns.length; y++) {
            //         columns[y].style.maxWidth = widths[y] + 'px';
            //     }
            // }
            // amounts = app.qap('.super-stripe-column span.super-stripe-amount', parent);
            // for (x = 0; x < amounts.length; x++) {
            //     width = app.itemWidth(amounts[x]);
            //     if (width > amountWidth) amountWidth = width;
            // }
            // for (x = 0; x < amounts.length; x++) {
            //     amounts[x].style.flexBasis = amountWidth + 'px';
            // }
            // console.log(amountWidth);
            // console.log(widths);
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
            var tmpHTML,
                row = app.q('.super-contextmenu-active'),
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
            post: function (e, target, eventType, attr) {
                if ((target.closest('.super-stripe-modal').classList.contains('super-loading')) ||
                    (app.api.refund.validate.all())) return false;
                target.closest('.super-stripe-modal').classList.add('super-loading');
                console.log(e, target, eventType, attr);
                target.innerHTML += app.ui.svg.loader.html;
                var amount = app.q('.super-stripe-modal input[name="amount"]').value;
                var reason = app.q('.super-stripe-modal select[name="reason"]').value;
                // eslint-disable-next-line no-undef
                amount = parseFloat(OSREC.CurrencyFormatter.parse(amount));
                console.log(amount);
                console.log('Post refund request');
                var data = {
                    type: 'refund.create',
                    // ID of the PaymentIntent to refund.
                    payment_intent: attr.id,
                    // String indicating the reason for the refund. If set, possible values are duplicate, fraudulent, and requested_by_customer.
                    // If you believe the charge to be fraudulent, specifying fraudulent as the reason will add the associated card and email to your block lists, and will also help us improve our fraud detection algorithms.
                    reason: reason,
                    // A positive integer in cents representing how much of this charge to refund.
                    // Can refund only up to the remaining, unrefunded amount of the charge.
                    amount: parseFloat(amount * 100).toFixed(0)
                };
                app.api.handler(data);
            },
            validate: {
                all: function () {
                    var error = false;
                    error = (app.api.refund.validate.amount() === true ? true : error);
                    error = (app.api.refund.validate.reason() === true ? true : error);
                    return error;
                },
                amount: function () {
                    var node,
                        sfevents = {},
                        attr = {},
                        msg = '',
                        amount,
                        error = false,
                        target = app.q('.super-stripe-modal select[name="reason"]');
                    target = app.q('.super-stripe-modal input[name="amount"]');
                    sfevents = JSON.parse(target.attributes.sfevents.value);
                    attr = sfevents.keyup['api.refund.validate.amount'];
                    // eslint-disable-next-line no-undef
                    amount = parseFloat(OSREC.CurrencyFormatter.parse(target.value));
                    if (amount <= 0) msg = 'Refund cannot be ' + parseFloat(attr.max).toFixed(2) + ' ' + attr.currency + '.';
                    if (amount > parseFloat(attr.max)) msg = 'Refund cannot be more than ' + parseFloat(attr.max).toFixed(2) + ' ' + attr.currency + '.';
                    app.remove(app.qap('.super-stripe-error', target.closest('.super-stripe-modal-field')));
                    app.q('.super-stripe-modal-actions .super-stripe-primary').innerHTML = 'Refund ' + attr.symbol + target.value;
                    if (msg !== '') {
                        error = true;
                        target.closest('.super-stripe-modal-field').classList.add('super-error');
                        node = document.createElement('p');
                        node.className = 'super-stripe-error';
                        node.innerHTML = msg;
                        target.parentNode.appendChild(node);
                    } else {
                        target.closest('.super-stripe-modal-field').classList.remove('super-error');
                    }
                    return error;
                },
                reason: function () {
                    var node,
                        error = false,
                        target = app.q('.super-stripe-modal select[name="reason"]');
                    target = app.q('.super-stripe-modal select[name="reason"]');
                    app.remove(app.qap('.super-stripe-error', target.closest('.super-stripe-modal-field')));
                    if (target.value === '' || target.value === 'null') {
                        error = true;
                        target.closest('.super-stripe-modal-field').classList.add('super-error');
                        node = document.createElement('p');
                        node.className = 'super-stripe-error';
                        node.innerHTML = 'Required';
                        target.parentNode.appendChild(node);
                    } else {
                        target.closest('.super-stripe-modal-field').classList.remove('super-error');
                    }
                    return error;
                }
            },
        },

        // Invoice
        invoice: {
            // Download as PDF
            pdf: function (e, target, eventType, attr) {
                if(target.parentNode.classList.contains('super-stripe-description')){
                    target.innerHTML = app.ui.svg.loader.html + target.innerHTML;
                }else{
                    target.innerHTML = target.innerHTML + app.ui.svg.loader.html;
                }
                // "invoice_pdf": "https://pay.stripe.com/invoice/invst_LC4o7wAvPzS3pCSZqCQ7PqaA0X/pdf",
                app.api.handler({
                    type: 'invoice.pdf',
                    id: attr.invoiceId
                });
            },
            // View invoice online
            online: function (e, target, eventType, attr) {
                if(target.parentNode.classList.contains('super-stripe-description')){
                    target.innerHTML = app.ui.svg.loader.html + target.innerHTML;
                }else{
                    target.innerHTML = target.innerHTML + app.ui.svg.loader.html;
                }
                // "hosted_invoice_url": "https://pay.stripe.com/invoice/invst_LC4o7wAvPzS3pCSZqCQ7PqaA0X",
                app.api.handler({
                    type: 'invoice.online',
                    id: attr.invoiceId
                });
            }
        },

        // Load more Transactions, Products, Customers
        loadMore: function (e, target, eventType, attr) {
            if (target.parentNode.classList.contains('super-loading')) {
                return false;
            }
            app.addClass(target.parentNode, 'super-loading');
            target.innerHTML = target.innerHTML + app.ui.svg.loader.html;
            var nodes, lastChild, starting_after;
            if (attr.type == 'paymentIntents') nodes = app.qa('.super-stripe-transactions .super-stripe-row');
            if (attr.type == 'products') nodes = app.qa('.super-stripe-products .super-stripe-row');
            if (attr.type == 'customers') nodes = app.qa('.super-stripe-customers .super-stripe-row');
            lastChild = nodes[nodes.length - 1];
            starting_after = lastChild.id;
            app.api.handler({
                type: attr.type,
                limit: 20,
                starting_after: starting_after
            });
        },

        // Add new rows
        addRows: {
            refreshPaymentIntent: function (payload) {
                app.api.addRows.paymentIntents(payload);
            },
            paymentIntents: function (payload) {

                // check if row already exists, if so then we need to update the row instead of adding a new one
                var replace = false;
                if (app.q('#' + payload.id)) replace = app.q('#' + payload.id);

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
                column.innerHTML = html
                newRow.appendChild(column);

                // Amount
                html = '';
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-amount';
                // eslint-disable-next-line no-undef
                payload.amountFormatted = OSREC.CurrencyFormatter.format(payload.amount / 100, {
                    currency: payload.currency
                });
                html += '<span class="super-stripe-amount">' + payload.amountFormatted + '</span>';
                column.innerHTML = html;
                newRow.appendChild(column);

                // Currency
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-currency';
                column.innerHTML = payload.currency.toUpperCase();
                newRow.appendChild(column);

                // Status
                html = '';
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-status';
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
                if (payload.status == 'requires_action' || payload.status == 'requires_confirmation' || payload.status == 'requires_payment_method' || payload.status == 'requires_capture') {
                    if (((payload['last_payment_error'])) && (($declineCodes[payload['last_payment_error']['decline_code']]))) {
                        $class = ' super-stripe-failed';
                        $label = 'Failed';
                        $title = ' title="' + $declineCodes[payload['last_payment_error']['decline_code']]['desc'] + '"';
                    } else {
                        if (payload.status == 'requires_action' || payload.status == 'requires_confirmation' || payload.status == 'requires_payment_method') {
                            $class = ' super-stripe-incomplete';
                            $label = 'Incomplete';
                            if(payload.status == 'requires_action'){
                                $title = ' title="The customer must complete an additional authentication step."';
                            }else{
                                if(payload.status == 'requires_confirmation'){
                                    $title = ' title="The customer has not completed the payment."';
                                }else{
                                    $title = ' title="The customer has not entered their payment method."';
                                }
                            }
                        } else {
                            $class = ' super-stripe-uncaptured';
                            $label = 'Uncaptured';
                            $title = ' title="Payment authorized, but not yet captured."';
                        }
                        $path = 'M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16zm1-8.577V4a1 1 0 1 0-2 0v4a1 1 0 0 0 .517.876l2.581 1.49a1 1 0 0 0 1-1.732z';
                    }
                }
                if (payload.status == 'succeeded') {
                    if (payload.charges.data[0].refunded) {
                        $label = 'Refunded';
                        $title = '';
                        $class = ' super-stripe-refunded';
                        $pathFill = '#697386';
                        $path = 'M10.5 5a5 5 0 0 1 0 10 1 1 0 0 1 0-2 3 3 0 0 0 0-6l-6.586-.007L6.45 9.528a1 1 0 0 1-1.414 1.414L.793 6.7a.997.997 0 0 1 0-1.414l4.243-4.243A1 1 0 0 1 6.45 2.457L3.914 4.993z';
                    } else {
                        if (payload.charges.data[0].amount_refunded) {
                            $label = 'Partial refund';
                            $labelColor = '#3d4eac;';
                            // eslint-disable-next-line no-undef
                            payload.charges.data[0].amount_refundedFormatted = OSREC.CurrencyFormatter.format(payload.charges.data[0].amount_refunded / 100, {
                                currency: payload.currency
                            });
                            $title = ' title="' + payload.charges.data[0].amount_refundedFormatted + ' ' + 'was refunded"';
                            $class = ' super-stripe-partial-refund';
                            $pathFill = '#5469d4';
                            $bgColor = '#d6ecff';
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

                // Check for Blocked payment
                if (payload.charges && payload.charges.data && payload.charges.data[0] && payload.charges.data[0].payment_method_details) {
                    var chargeData = payload.charges.data[0];
                    if (chargeData.outcome) {
                        var outcome = chargeData.outcome;
                        if(outcome.type==='blocked'){
                            $class = ' super-stripe-blocked';
                            $label = 'Blocked';
                            $title = ' title="' + outcome.seller_message + '"';
                            $path = 'M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16zm-3.477-3.11a6 6 0 0 0 8.367-8.367zM3.11 11.478l8.368-8.368a6 6 0 0 0-8.367 8.367z';
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

                // Description
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-description';
                // If has invoice link to invoice
                if (payload.invoice) {
                    column.innerHTML = '<a href="#" sfevents=\'{"click":{"app.api.invoice.online":{"invoiceId":"' + payload.invoice + '"}}}\'>'+app.ui.svg.invoice.html+payload.description+'</a>';
                }else{
                    column.innerHTML = (payload.description ? payload.description : '');
                }
                newRow.appendChild(column);

                // Customer
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-customer';
                html = '';
                // WordPress user
                if(payload.wp_user_info){
                    html += '<a class="super-stripe-wp-user" target="_blank" href="' + payload.wp_user_info.data.edit_link + '">'+payload.wp_user_info.data.display_name+'</a>';
                }
                // Stripe customer
                if (payload.customer) {
                    html += '<a class="super-stripe-stripe-customer" target="_blank" href="https://dashboard.stripe.com/customers/' + payload.customer + '">';
                    html += app.ui.svg.customer.html;
                    html += payload.customer;
                    html += '</a>';
                }

                //if(userID!==0) html += '<a target="_blank" href="user-edit.php?user_id=' + userID + '">WordPress User</a>';
                //html += '<a target="_blank" href="https://dashboard.stripe.com/customers/' + payload.customer.id + '">WP User';
                //html += '<a target="_blank" href="https://dashboard.stripe.com/customers/' + payload.customer.id + '">Stripe Customer';

                // console.log(payload.customer);
                // if (payload.customer) {
                //     column.customer = payload.customer.id;
                //     html += '<a target="_blank" href="https://dashboard.stripe.com/customers/' + payload.customer.id + '">';
                //     if (payload.customer.email) {
                //         html += payload.customer.email
                //     } else {
                //         html += payload.customer.id;
                //     }
                //     html += '</a>';
                // }
                column.innerHTML = html;
                newRow.appendChild(column);

                // Shipping address
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-shipping';

                html = '';
                if (payload.shipping) {
                    if (payload.shipping.address) {
                        html += '<div class="super-stripe-address">';
                        if (payload.shipping.carrier) {
                            html += '<div class="super-stripe-carrier">';
                            html += '<strong>Carrier:</strong>';
                            html += '<span>' + payload.shipping.carrier + '</span>';
                            html += '</div>';
                        }
                        if (payload.shipping.tracking_number) {
                            html += '<div class="super-stripe-tracking-number">';
                            html += '<strong>Tracking:</strong>';
                            html += '<span>' + payload.shipping.tracking_number + '</span>';
                            html += '</div>';
                        }
                        if (payload.shipping.name) {
                            html += '<div class="super-stripe-recipient">';
                            html += '<strong>Recipient:</strong>';
                            html += '<span>' + payload.shipping.name + (payload.shipping.phone ? ' (' + payload.shipping.phone + ')' : '') + '</span>';
                            html += '</div>';
                        }
                        if (payload.shipping.address) {
                            html += '<div class="super-stripe-address">';
                            html += '<strong>Address:</strong>';
                            html += '<span>';
                            html += (payload.shipping.address.line1 ? payload.shipping.address.line1 + '<br />' : '');
                            html += (payload.shipping.address.line2 ? payload.shipping.address.line2 + '<br />' : '');
                            html += (payload.shipping.address.city ? payload.shipping.address.city + ', ' : '');
                            html += (payload.shipping.address.state ? payload.shipping.address.state + ' ' : '');
                            html += (payload.shipping.address.postal_code ? payload.shipping.address.postal_code + ' ' : '');
                            html += (payload.shipping.address.country ? payload.shipping.address.country : '');
                            html += '</span>';
                            html += '</div>';
                        }
                        html += '</div>';
                    }
                }
                column.innerHTML = html;
                newRow.appendChild(column);

                // Payment Method
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-method';
                html = '';
                console.log(payload);
                if (payload.charges && payload.charges.data && payload.charges.data[0] && payload.charges.data[0].payment_method_details) {
                    chargeData = payload.charges.data[0];
                    var details = chargeData.payment_method_details;
                    console.log(methodType);
                    var methodType = details.type; // card
                    var methodDetails = details[methodType];
                    switch (methodType) {
                        case 'ach_credit_transfer':
                            html += methodDetails.bank_name + ' - ' + methodDetails.routing_number + ' - ' + methodDetails.swift_code + ' - ' + methodDetails.account_number;
                            break;
                        case 'ach_debit':
                            html += methodDetails.bank_name + ' •••• ' + methodDetails.last4;
                            break;
                        case 'alipay':
                            html += 'Alipay: ' + methodDetails.alipay;
                            break;
                        case 'bancontact':
                            html += methodDetails.bank_name + ' •••• ' + methodDetails.iban_last4;
                            break;
                        case 'card':
                            html += app.ui.svg.paymentMethods.card[methodDetails.brand].html + ' •••• ' + methodDetails.last4;
                            break;
                        case 'card_present':
                            html += methodDetails.brand + ' •••• ' + methodDetails.last4;
                            break;
                        case 'eps':
                            html += 'EPS: ' + methodDetails.verified_name;
                            break;
                        case 'giropay':
                            html += 'Giropay: ' + methodDetails.bank_name + ' / ' + methodDetails.bic + ' / ' + methodDetails.verified_name;
                            break;
                        case 'ideal':
                            html += app.ui.svg.paymentMethods.ideal.html + ' •••• ' + methodDetails.iban_last4;
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
                            html += 'SEPA Debit: •••• ' + methodDetails.last4;
                            break;
                        case 'sofort':
                            html += 'Sofort: •••• ' + methodDetails.iban_last4;
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

                    // If LIVE data
                    // if (chargeData.outcome) {
                    //     outcome = chargeData.outcome;
                    //     html += '<span title="' + outcome.seller_message + '" class="super-stripe-risk-score lvl-' + outcome.risk_level + '">';
                    //     switch (outcome.risk_level) {
                    //         case 'highest':
                    //             html += outcome.risk_score;
                    //             break;
                    //         case 'elevated':
                    //             html += outcome.risk_score;
                    //             break;
                    //         case 'normal':
                    //             html += outcome.risk_score;
                    //             break;
                    //         case 'not_assessed' || 'unknown':
                    //             html += app.ui.svg.risk[outcome.risk_level].html;
                    //             break;
                    //         default:
                    //             html += outcome.risk_score;
                    //     }
                    //     html += '</span>';
                    //     switch (outcome.risk_level) {
                    //         case 'highest':
                    //             html += 'Blocked';
                    //             break;
                    //         case 'elevated':
                    //             html += 'Elevated';
                    //             break;
                    //         case 'normal':
                    //             html += 'Normal';
                    //             break;
                    //         case 'not_assessed':
                    //             html += 'Not evaluated';
                    //             break;
                    //         case 'unknown':
                    //             html += 'Unknown';
                    //             break;
                    //         default:
                    //             html += outcome.risk_level;
                    //     }
                    // }
                }
                // If sandbox/test data
                if (!payload.livemode) {
                    html += '<span class="super-stripe-testdata">TEST DATA</span>';
                }
                column.innerHTML = html;
                newRow.appendChild(column);

                // Date
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-date';
                column.innerHTML = payload.createdFormatted;
                newRow.appendChild(column);

                // If we are replacing/refreshing an existing row
                if (replace) {
                    // Place new row right after replace
                    replace.parentNode.insertBefore(newRow, replace.nextSibling);
                    replace.remove();
                } else {
                    // Add the row to the parent (the list/table)
                    parentNode.appendChild(newRow);
                }

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
                //column.innerHTML = payload.customer;
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
                //column.innerHTML = payload.customer;
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
        handler: function (data) {
            var i, xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (this.readyState === 4) {
                    if (this.status === 200) { // Success:
                        console.log(data.type);
                        try {
                            var payload = JSON.parse(this.response);
                        } catch (error) {
                            console.log(error);
                            alert(error);
                        }
                        console.log(payload);
                        if (data.type == 'refund.create') {
                            if (payload.status === 'succeeded') {
                                app.ui.modal.close(); // Close modal
                                app.ui.toast.show(payload); // Display "View" link
                                // Refresh this payment intent in the table/list
                                app.api.handler({
                                    type: 'refreshPaymentIntent',
                                    id: payload.payment_intent,
                                    limit: 1
                                });
                            } else {
                                app.removeClass(app.q('.super-stripe-modal.super-loading'), 'super-loading');
                                if (payload.error) {
                                    alert(payload.error.message);
                                }
                            }
                            return true;
                        }
                        if (data.type == 'invoice.online' || data.type == 'invoice.pdf') {
                            console.log(payload);
                            if (data.type == 'invoice.online') {
                                window.open(payload.hosted_invoice_url, '_blank');
                                app.ui.svg.loader.remove(); // Delete loader
                            }
                            if (data.type == 'invoice.pdf') {
                                window.location.href = payload.invoice_pdf;
                                setTimeout(function () {
                                    app.ui.svg.loader.remove(); // Delete loader
                                }, 3000);
                            }
                            console.log('just testing...');
                            return true;
                        }
                        if (data.type == 'refreshPaymentIntent') {
                            console.log('just testing refresh row...');
                            for (i = 0; i < payload.length; i++) {
                                app.api.addRows[data.type](payload[i]);
                            }
                            return true;
                        }
                        for (i = 0; i < payload.length; i++) {
                            app.api.addRows[data.type](payload[i]);
                        }
                    }
                    // Complete:
                    app.ui.svg.loader.remove();
                    app.removeClass(app.q('.super-stripe-load-more'), 'super-loading');
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
                data: data
            };
            xhttp.send(app.serialize(params));
        }
    };

    // Load first items
    if (app.qa('.super-stripe-tabs-content > div').length) {
        app.api.handler({
            type: 'paymentIntents',
            limit: 20
        });
        app.api.handler({
            type: 'products',
            limit: 20
        });
        app.api.handler({
            type: 'customers',
            limit: 20
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
            '.super-stripe-load-more > div',
            '.super-stripe-invoice-btn',
            '.super-stripe-action-btn',
            '.super-stripe-toast-close',
            '.' + app.ui.contextMenu.className + ' > div',
            '.super-stripe-description > a'
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

    window.app = app;
})();