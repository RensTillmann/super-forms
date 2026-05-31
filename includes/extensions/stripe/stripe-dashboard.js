/*global super_stripe_dashboard_i18n, OSREC, jQuery*/
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

    // Search WP users
    app.search = {
        timeout: null,
        users: function (e, target, eventType, attr) {
            if (app.search.timeout !== null) clearTimeout(app.search.timeout);
            app.search.timeout = setTimeout(function () {
                if (target.value === '') {
                    // Close filter menu and reset
                    app.ui.filterMenu.remove();
                } else {
                    app.ui.svg.loader.create(target.parentNode);
                    // Get user ID and email address
                    var customer_id = target.closest('.super-stripe-user').dataset.id,
                        customer_email = (target.closest('.super-stripe-row').querySelector('.super-stripe-email') ? target.closest('.super-stripe-row').querySelector('.super-stripe-email').innerText : '');
                    app.api.handler({
                        type: 'searchUsers',
                        target: target,
                        customer_id: customer_id,
                        customer_email: customer_email,
                        value: target.value
                    });
                }
            }, 500);
        }
    };
    // Connect WP user
    app.connectUser = function (e, target, eventType, attr) {
        if (typeof attr.unconnect === 'undefined') {
            app.ui.svg.loader.create(target.parentNode);
        }
        app.api.handler({
            type: 'connectUser',
            customer_id: attr.customer_id,
            user_id: attr.user_id,
            unconnect: (typeof attr.unconnect !== 'undefined' ? attr.unconnect : false)
        });
    };
    // Unconnect WP user
    app.unconnectUser = function (e, target, eventType, attr) {
        console.log('attr:', attr);
        target.innerHTML = app.ui.svg.loader.html;
        attr.unconnect = true;
        app.connectUser(e, target, eventType, attr);
        e.preventDefault();
        return false;
    };

    // UI
    app.ui = {
        templates: {
            connectedUser: function (payload, html) {
                if (typeof html === 'undefined') html = '';
                html = '<a class="super-stripe-wp-user" target="_blank" href="' + payload.edit_link + '">';
                html += payload.display_name;
                html += '<span sfevents=\'{"click":{"unconnectUser":{"customer_id":"' + payload.customer_id + '","user_id":"' + payload.ID + '"}}}\' class="super-stripe-action-btn super-stripe-unconnect">' + app.ui.svg.delete.html + '</span>';
                html += '</a>';
                return html;
            },
            userfilter: function (payload, html) {
                if (typeof html === 'undefined') html = '';
                html += '<div class="super-stripe-search-users">';
                html += '<div>';
                html += '- not connected -';
                html += '</div>';
                html += '<input placeholder="Connect to WordPress user..." type="text" sfevents=\'{"click":{"ui.row.state":{"value":"filtering"}},"keyup":{"search.users":{}}}\' />';
                html += '</div>';
                return html;
            }
        },
        row: {
            state: function (e, target, eventType, attr) {
                var i, nodes = app.qa('.super-stripe-row');
                for (i = 0; i < nodes.length; i++) {
                    nodes[i].className = 'super-stripe-row';
                }
                app.addClass(target.closest('.super-stripe-row'), 'super-stripe-' + attr.value);
            }
        },
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
                    app.removeClass(app.qa('.super-stripe-load-more.super-loading'), 'super-loading');
                },
                create: function (parent) {
                    var loader = document.createElement('div');
                    loader.innerHTML = app.ui.svg.loader.html;
                    parent.appendChild(loader.children[0]);
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
                sepa_debit: {
                    html: '<svg height="32" viewBox="0 0 32 32" width="32" xmlns="http://www.w3.org/2000/svg" style="height: 20px; width: 20px;"><g fill="none" fill-rule="evenodd"><path d="M0 0h32v32H0z" fill="#e3e8ee"></path><path d="M7.274 13.5a1.25 1.25 0 0 1-.649-2.333C7.024 10.937 10.15 9.215 16 6c5.851 3.215 8.976 4.937 9.375 5.167a1.25 1.25 0 0 1-.65 2.333zm12.476 10v-8.125h3.75V23.5H25a1 1 0 0 1 1 1V26H6v-1.5a1 1 0 0 1 1-1h1.5v-8.125h3.75V23.5h1.875v-8.125h3.75V23.5z" fill="#697386"></path></g></svg>'
                },
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
                    toastText = OSREC.CurrencyFormatter.format(payload.amount / 100, {
                        currency: payload.currency
                    }) + ' Refunded',
                    toastURL = '' + super_stripe_dashboard_i18n.dashboardUrl + '/payments/' + payload.payment_intent,
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
                // Get Raw JSON data
                var html = '',
                    json = app.api.rawJSON,
                    currency = 'EUR', //json.currency.toUpperCase(),
                    symbol = (typeof OSREC.CurrencyFormatter.symbols[currency] !== 'undefined' ? OSREC.CurrencyFormatter.symbols[currency] : currency + ''),
                    amount = json.amount / 100,
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
                                requested_by_customer: "Requested by customer"
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
            }
        },
        filterMenu: {
            className: 'super-stripe-filtermenu',
            remove: function () {
                app.remove(app.qa('.' + app.ui.filterMenu.className));
            },
            open: function (data, payload) {
                console.log(data, payload);
                console.log(data.target);
                console.log(data.target.value);
                data.target.parentNode.querySelector('.super-stripe-loader').remove();
                // Remove existing menu
                app.ui.filterMenu.remove();

                // Create new menu
                var filterMenu = document.createElement('div');
                filterMenu.className = app.ui.filterMenu.className;
                var i, html = '';
                if (payload.suggestions.length !== 0) {
                    html += '<span>Suggested connection:</span>';
                }
                for (i = 0; i < payload.suggestions.length; i++) {
                    html += '<div sfevents=\'{"click":{"connectUser":{"customer_id":"' + data.customer_id + '","user_id":"' + payload.suggestions[i].ID + '"}}}\'><strong>' + payload.suggestions[i].display_name + '</strong> (' + payload.suggestions[i].user_email + ')</div>';
                }
                if (payload.users.length !== 0) {
                    html += '<span>Results:</span>';
                }
                for (i = 0; i < payload.users.length; i++) {
                    html += '<div sfevents=\'{"click":{"connectUser":{"customer_id":"' + data.customer_id + '","user_id":"' + payload.users[i].ID + '"}}}\'><strong>' + payload.users[i].display_name + '</strong> (' + payload.users[i].user_email + ')</div>';
                }
                filterMenu.innerHTML = html;

                // Get the position relative to the viewport (i.e. the window)
                var offset = data.target.getBoundingClientRect();
                var target_absolute_position_left = offset.left + (offset.width / 2);
                target_absolute_position_left = offset.left;
                var w = window,
                    d = document,
                    dE = d.documentElement,
                    g = d.getElementsByTagName('body')[0],
                    window_width = w.innerWidth || dE.clientWidth || g.clientWidth,
                    scrollTop = window.pageYOffset || (document.documentElement || document.body.parentNode || document.body).scrollTop;
                filterMenu.style.position = 'absolute';
                filterMenu.style.top = 0;
                filterMenu.style.left = 0;
                // Append to body
                document.body.appendChild(filterMenu);
                var initial_width = filterMenu.offsetWidth;
                filterMenu.style.top = offset.top + scrollTop + (offset.height) + 'px';
                filterMenu.style.transform = 'translateY(3px)';
                filterMenu.style.left = target_absolute_position_left + 'px';
                // Check if we can't position the element at top or bottom because of overlapping window
                // The tooltip could possibly be cut off if we do not check this
                if (window_width < target_absolute_position_left + initial_width) {
                    // We have to position the tooltip to the left side of the target
                    filterMenu.style.transform = null;
                    filterMenu.style.left = (offset.left - initial_width - 30) + 'px';
                    filterMenu.classList.remove('sb-bottom');
                    filterMenu.classList.add('sb-left');
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
                if (attr.type === 'actions') {
                    // Before we do anything, determine if this button was already clicked, and has active state
                    // If it has active state, we can simple close the context menu and do nothing
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

                        // PaymentIntents:
                        if (attr.method === 'paymentIntents') {
                            // Edit
                            html += '<a target="_blank" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/payments/' + row.id + '">Edit</a>';
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
                            var formID = 0,
                                contactEntryID = 0,
                                userID = 0;
                            if (payload.metadata) {
                                // Based on form
                                if (payload.metadata.form_id) formID = payload.metadata.form_id;
                                // Contact entry
                                if (payload.metadata.contact_entry_id) contactEntryID = payload.metadata.contact_entry_id;
                                // WordPress user
                                if (payload.metadata.user_id) userID = payload.metadata.user_id;
                                // Registered user
                                if (payload.metadata.frontend_user_id) userID = payload.metadata.frontend_user_id;
                            }
                            if (contactEntryID !== 0) html += '<a target="_blank" href="admin.php?page=super_contact_entry&id=' + contactEntryID + '">Contact Entry</a>';
                            if (payload.post_permalink) {
                                html += '<a target="_blank" href="' + payload.post_permalink + '">WordPress Post</a>';
                            }
                            if (userID !== 0) html += '<a target="_blank" href="user-edit.php?user_id=' + userID + '">WordPress User</a>';
                            if (payload.customer) html += '<a target="_blank" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/customers/' + payload.customer + '">Stripe Customer</a>';
                            html += '<a target="_blank" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/payments/' + row.id + '">View payment details</a>';
                            if (formID !== 0) html += '<a target="_blank" href="admin.php?page=super_create_form&id=' + formID + '">Form</a>';
                        }
                        // PaymentIntents:
                        if (attr.method === 'customers') {
                            // Edit
                            html += '<a target="_blank" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/customers/' + row.id + '">Edit</a>';
                        }
                        // PaymentIntents:
                        if (attr.method === 'subscriptions') {
                            // View subscription
                            html += '<a target="_blank" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/subscriptions/' + row.id + '">View subscription</a>';
                            // Update subscription
                            html += '<a target="_blank" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/subscriptions/' + row.id + '/edit/">Update subscription</a>';
                            // Cancel subscription
                            html += '<a target="_blank" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/subscriptions/' + row.id + '">Cancel subscription</a>';
                            html += '<divider></divider>';
                            html += '<span>CONNECTIONS</span>';
                            // View customer
                            html += '<a target="_blank" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/customers/' + payload.customer + '">View customer</a>';
                        }
                        contextMenu.innerHTML = html;

                        // {type: "actions", method: "subscriptions"}
                        // {type: "actions", method: "customers"}
                        // {type: "actions", method: "paymentIntents"}

                        // Get the position relative to the viewport (i.e. the window)
                        var offset = target.getBoundingClientRect();
                        var target_absolute_position_left = offset.left + (offset.width / 2);
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
    };

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
                target.innerHTML += app.ui.svg.loader.html;
                var amount = app.q('.super-stripe-modal input[name="amount"]').value;
                var reason = app.q('.super-stripe-modal select[name="reason"]').value;
                amount = parseFloat(OSREC.CurrencyFormatter.parse(amount));
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
                if (target.parentNode.classList.contains('super-stripe-description')) {
                    target.innerHTML = app.ui.svg.loader.html + target.innerHTML;
                } else {
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
                if (target.parentNode.classList.contains('super-stripe-description')) {
                    target.innerHTML = app.ui.svg.loader.html + target.innerHTML;
                } else {
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
            //if (attr.type == 'products') nodes = app.qa('.super-stripe-products .super-stripe-row');
            //if (attr.type == 'customers') nodes = app.qa('.super-stripe-customers .super-stripe-row');
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

                var $declineCodes = super_stripe_dashboard_i18n.declineCodes;
                var parentNode = app.q('.super-stripe-transactions .super-stripe-table-rows');
                // Check if we can find the last ID, if not then just leave starting_after blank
                var newRow = document.createElement('div');
                newRow.className = 'super-stripe-row';
                newRow.id = payload.id;
                // column.innerHTML = '<a target="_blank" href="'+super_stripe_dashboard_i18n.dashboardUrl+'/payments/' + payload.id + '">' + payload.id + '</a>';

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
                html += '<div class="super-stripe-action-btn" sfevents=\'{"click":{"ui.contextMenu.open":{"type":"actions","method":"paymentIntents"}}}\'>';
                html += '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;"><path d="M2 10a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4z" fill-rule="evenodd"></path></svg>';
                html += '</div>';
                column.innerHTML = html
                newRow.appendChild(column);

                // Amount
                html = '';
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-amount';
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
                    $labelColor = '#4f566b',
                    $title = '',
                    $class = '',
                    $pathFill = '#697386',
                    $path = 'M8 6.585l4.593-4.592a1 1 0 0 1 1.415 1.416L9.417 8l4.591 4.591a1 1 0 0 1-1.415 1.416L8 9.415l-4.592 4.592a1 1 0 0 1-1.416-1.416L6.584 8l-4.59-4.591a1 1 0 1 1 1.415-1.416z',
                    $bgColor = '#e3e8ee';

                if (payload.status == 'warning_needs_response') {
                    $label = 'Needs response';
                    $labelColor = '#983705';
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
                    $labelColor = '#a41c4e';
                    $bgColor = '#fde2dd';
                    $pathFill = '#a41c4e';
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
                        $labelColor = '#a41c4e';
                        $bgColor = '#fde2dd';
                        $pathFill = '#a41c4e';
                        $class = ' super-stripe-failed';
                        $label = 'Failed';
                        $title = ' title="' + $declineCodes[payload['last_payment_error']['decline_code']]['desc'] + '"';
                        $path = 'M8 6.585l4.593-4.592a1 1 0 0 1 1.415 1.416L9.417 8l4.591 4.591a1 1 0 0 1-1.415 1.416L8 9.415l-4.592 4.592a1 1 0 0 1-1.416-1.416L6.584 8l-4.59-4.591a1 1 0 1 1 1.415-1.416z';
                    } else {
                        $path = 'M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16zm1-8.577V4a1 1 0 1 0-2 0v4a1 1 0 0 0 .517.876l2.581 1.49a1 1 0 0 0 1-1.732z';
                        if (payload.status == 'requires_action' || payload.status == 'requires_confirmation' || payload.status == 'requires_payment_method') {
                            $class = ' super-stripe-incomplete';
                            $label = 'Incomplete';
                            if (payload.status == 'requires_action') {
                                $title = ' title="The customer must complete an additional authentication step."';
                            } else {
                                if (payload.status == 'requires_confirmation') {
                                    $title = ' title="The customer has not completed the payment."';
                                } else {
                                    if (payload.charges.total_count!==0) {
                                        if (payload.charges.data[0].status == 'failed') {
                                            $labelColor = '#a41c4e';
                                            $bgColor = '#fde2dd';
                                            $pathFill = '#a41c4e';
                                            $class = ' super-stripe-failed';
                                            $label = 'Failed';
                                            $title = '';
                                            $path = 'M8 6.585l4.593-4.592a1 1 0 0 1 1.415 1.416L9.417 8l4.591 4.591a1 1 0 0 1-1.415 1.416L8 9.415l-4.592 4.592a1 1 0 0 1-1.416-1.416L6.584 8l-4.59-4.591a1 1 0 1 1 1.415-1.416z';
                                        } else {
                                            $title = ' title="The customer has not entered their payment method."';
                                        }
                                    }else{
                                        if (payload.status == 'requires_payment_method') {
                                            $title = ' title="The customer has not entered their payment method."';
                                        }else{
                                            $title = ' title="'+payload.status+'"';
                                        }
                                    }
                                }
                            }
                        } else {
                            $class = ' super-stripe-uncaptured';
                            $label = 'Uncaptured';
                            $title = ' title="Payment authorized, but not yet captured."';
                        }
                    }
                }
                if (payload.status == 'succeeded') {
                    if (payload.charges.data[0].refunded) {
                        $label = 'Refunded';
                        $title = ' title="This payment has been fully refunded."';
                        $class = ' super-stripe-refunded';
                        $path = 'M10.5 5a5 5 0 0 1 0 10 1 1 0 0 1 0-2 3 3 0 0 0 0-6l-6.586-.007L6.45 9.528a1 1 0 0 1-1.414 1.414L.793 6.7a.997.997 0 0 1 0-1.414l4.243-4.243A1 1 0 0 1 6.45 2.457L3.914 4.993z';
                    } else {
                        if (payload.charges.data[0].disputed) {
                            $label = 'Disputed';
                            $title = '';
                            $class = ' super-stripe-disputed';
                            $path = 'M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16zm0-2.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM8 2a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0V3a1 1 0 0 0-1-1z';
                        } else {
                            if (payload.charges.data[0].amount_refunded) {
                                $label = 'Partial refund';
                                payload.charges.data[0].amount_refundedFormatted = OSREC.CurrencyFormatter.format(payload.charges.data[0].amount_refunded / 100, {
                                    currency: payload.currency
                                });
                                $title = ' title="' + payload.charges.data[0].amount_refundedFormatted + ' ' + 'was refunded"';
                                $class = ' super-stripe-partial-refund';
                                $path = 'M9 8a1 1 0 0 0-1-1H5.5a1 1 0 1 0 0 2H7v4a1 1 0 0 0 2 0zM4 0h8a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4zm4 5.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z';
                            } else {
                                $label = 'Succeeded';
                                $labelColor = '#0e6245';
                                $title = ' title="This payment is complete."';
                                $class = ' super-stripe-succeeded';
                                $pathFill = '#5469d4';
                                $bgColor = '#cbf4c9';
                                $path = 'M5.297 13.213L.293 8.255c-.39-.394-.39-1.033 0-1.426s1.024-.394 1.414 0l4.294 4.224 8.288-8.258c.39-.393 1.024-.393 1.414 0s.39 1.033 0 1.426L6.7 13.208a.994.994 0 0 1-1.402.005z';
                            }
                        }
                    }
                }

                // Check for Blocked payment
                if (payload.charges && payload.charges.data && payload.charges.data[0] && payload.charges.data[0].payment_method_details) {
                    var chargeData = payload.charges.data[0];
                    if (chargeData.outcome) {
                        var outcome = chargeData.outcome;
                        if (outcome.type === 'blocked') {
                            $class = ' super-stripe-blocked';
                            $label = 'Blocked';
                            $title = ' title="' + outcome.seller_message + '"';
                            $path = 'M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16zm-3.477-3.11a6 6 0 0 0 8.367-8.367zM3.11 11.478l8.368-8.368a6 6 0 0 0-8.367 8.367z';
                        }
                    }
                }

                html += '<span' + $title + ' class="super-stripe-status' + $class + '" style="color:' + $labelColor + ';font-size:12px;padding:1px 6px 1px 6px;background-color:' + $bgColor + ';border-radius:4px;font-weight:500;">';
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
                    column.innerHTML = '<a title="' + super_stripe_dashboard_i18n.viewOnlineInvoice + '" href="#" sfevents=\'{"click":{"app.api.invoice.online":{"invoiceId":"' + payload.invoice + '"}}}\'>' + app.ui.svg.invoice.html + payload.description + '</a>';
                } else {
                    column.innerHTML = (payload.description ? payload.description : '');
                }
                newRow.appendChild(column);

                // Customer
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-customer';
                html = '';
                // WordPress user
                if (payload.wp_user_info) {
                    html += '<a class="super-stripe-wp-user" target="_blank" href="' + payload.wp_user_info.edit_link + '">' + payload.wp_user_info.display_name + '</a>';
                }
                // Stripe customer
                if (payload.customer) {
                    html += '<a class="super-stripe-stripe-customer" target="_blank" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/customers/' + payload.customer + '">';
                    html += app.ui.svg.customer.html;
                    html += payload.customer;
                    html += '</a>';
                }
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
                if (payload.charges && payload.charges.data && payload.charges.data[0] && payload.charges.data[0].payment_method_details) {
                    chargeData = payload.charges.data[0];
                    var details = chargeData.payment_method_details;
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
                            html += app.ui.svg.paymentMethods.sepa_debit.html + ' •••• ' + methodDetails.last4;
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
            },

            customers: function (payload) {
                // check if row already exists, if so then we need to update the row instead of adding a new one
                console.log(payload);

                var parentNode = app.q('.super-stripe-customers .super-stripe-table-rows');
                // Check if we can find the last ID, if not then just leave starting_after blank
                var newRow = document.createElement('div');
                newRow.className = 'super-stripe-row';
                newRow.id = payload.id;
                // column.innerHTML = '<a target="_blank" href="'+super_stripe_dashboard_i18n.dashboardUrl+'/payments/' + payload.id + '">' + payload.id + '</a>';

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
                html += '<div class="super-stripe-action-btn" sfevents=\'{"click":{"ui.contextMenu.open":{"type":"actions","method":"customers"}}}\'>';
                html += '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;"><path d="M2 10a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4z" fill-rule="evenodd"></path></svg>';
                html += '</div>';
                column.innerHTML = html
                newRow.appendChild(column);

                // E-mail
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-email';
                column.innerHTML = (payload.email ? payload.email : payload.id);
                newRow.appendChild(column);

                // Description
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-description';
                column.innerHTML = (payload.name ? payload.name : '');
                newRow.appendChild(column);

                // Customer
                html = '';
                html += '<a class="super-stripe-stripe-customer" target="_blank" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/customers/' + payload.id + '">';
                html += app.ui.svg.customer.html;
                html += payload.id;
                html += '</a>';
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-customer';
                column.innerHTML = html;
                newRow.appendChild(column);

                // WordPress user
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-user';
                column.dataset.id = payload.id;
                if (payload.wp_user_info) {
                    html = app.ui.templates.connectedUser(payload.wp_user_info);
                } else {
                    console.log('test2');
                    html = app.ui.templates.userfilter(payload);
                }
                column.innerHTML = html;
                newRow.appendChild(column);

                // Date
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-date';
                column.innerHTML = payload.createdFormatted;
                newRow.appendChild(column);


                // Add the row to the parent (the list/table)
                parentNode.appendChild(newRow);
            },

            subscriptions: function (payload) {
                // check if row already exists, if so then we need to update the row instead of adding a new one
                console.log(payload);

                var parentNode = app.q('.super-stripe-subscriptions .super-stripe-table-rows');
                // Check if we can find the last ID, if not then just leave starting_after blank
                var newRow = document.createElement('div');
                newRow.className = 'super-stripe-row';
                newRow.id = payload.id;
                // column.innerHTML = '<a target="_blank" href="'+super_stripe_dashboard_i18n.dashboardUrl+'/payments/' + payload.id + '">' + payload.id + '</a>';

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
                html += '<div class="super-stripe-action-btn" sfevents=\'{"click":{"ui.contextMenu.open":{"type":"actions","method":"subscriptions"}}}\'>';
                html += '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;"><path d="M2 10a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4z" fill-rule="evenodd"></path></svg>';
                html += '</div>';
                column.innerHTML = html
                newRow.appendChild(column);

                // Customer
                html = '';
                html += '<a class="super-stripe-stripe-customer" target="_blank" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/customers/' + payload.customer + '">';
                html += app.ui.svg.customer.html;
                html += payload.customer;
                html += '</a>';
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-customer';
                column.innerHTML = html;
                newRow.appendChild(column);

                // WordPress user
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-user';
                column.dataset.id = payload.customer;
                if (payload.wp_user_info) {
                    html = app.ui.templates.connectedUser(payload.wp_user_info);
                } else {
                    console.log('test2');
                    html = app.ui.templates.userfilter(payload);
                }
                column.innerHTML = html;
                newRow.appendChild(column);

                // Status
                html = '';
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-status';
                var $label = '',
                    $labelColor = '#0e6245',
                    $title = '',
                    $class = '',
                    $bgColor = '#cbf4c9';

                if (payload.status == 'incomplete') {
                    $label = 'Incomplete';
                    $title = ' title="Incomplete"';
                    $class = ' super-stripe-incomplete';
                }
                if (payload.status == 'incomplete_expired') {
                    $label = 'Incomplete Expired';
                    $title = ' title="Incomplete Expired"';
                    $class = ' super-stripe-incomplete-expired';
                }
                if (payload.status == 'trialing') {
                    $label = 'Trialing';
                    $title = ' title="Trialing"';
                    $class = ' super-stripe-trialing';
                }
                if (payload.status == 'active') {
                    $label = 'Active';
                    $title = ' title="Active"';
                    $class = ' super-stripe-active';
                }
                if (payload.status == 'past_due') {
                    $label = 'Past due';
                    $title = ' title="Past due"';
                    $class = ' super-stripe-past-due';
                }
                if (payload.status == 'canceled') {
                    $label = 'Canceled';
                    $title = ' title="Canceled"';
                    $class = ' super-stripe-canceled';
                    $labelColor = '#4f566b',
                    $bgColor = '#e3e8ee';
                }
                if (payload.status == 'unpaid') {
                    $label = 'Unpaid';
                    $title = ' title="Unpaid"';
                    $class = ' super-stripe-unpaid';
                }
                html += '<span' + $title + ' class="super-stripe-status' + $class + '" style="color:' + $labelColor + ';font-size:12px;padding:1px 6px 1px 6px;background-color:' + $bgColor + ';border-radius:4px;font-weight:500;">';
                html += $label;
                html += '</span>';
                column.innerHTML = html;
                newRow.appendChild(column);

                // Billing
                html = '';
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-billing';
                $labelColor = '#4f566b',
                    $bgColor = '#e3e8ee';

                if (payload.collection_method == 'charge_automatically') {
                    $label = 'Auto';
                    $title = ' title="Auto"';
                    $class = ' super-stripe-auto';
                }
                if (payload.collection_method == 'send_invoice') {
                    $label = 'Send';
                    $title = ' title="Send"';
                    $class = ' super-stripe-send';
                }
                html += '<span' + $title + ' class="super-stripe-status' + $class + '" style="color:' + $labelColor + ';font-size:12px;padding:1px 6px 1px 6px;background-color:' + $bgColor + ';border-radius:4px;font-weight:500;">';
                html += $label;
                html += '</span>';
                column.innerHTML = html;
                newRow.appendChild(column);

                // Pricing Plan
                var i, item;
                html = '';
                for (i = 0; i < payload.items.data.length; i++) {
                    item = payload.items.data[i];
                    html += '<a title="View product" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/subscritpions/product/' + item.plan.product + '" target="_blank">' + item.productName + '</a>';
                    html += ' • ';
                    html += '<a title="View plan" href="' + super_stripe_dashboard_i18n.dashboardUrl + '/plans/' + item.plan.id + '" target="_blank">' + item.plan.nickname + '</a>';
                    html += '<div class="clear"></div>';
                }
                // items.data.quantity
                // items.data.plan.interval
                //                 amount
                //                 currency
                //                 interval_count


                // "id": "si_GyEShXkbtJMyGV",
                // "object": "subscription_item",
                // "billing_thresholds": null,
                // "created": 1585076473,
                // "metadata": [],
                // "plan": {
                //     "id": "plan_GyEOetWFv2Hkr1",
                //     "object": "plan",
                //     "active": true,
                //     "aggregate_usage": null,
                //     "amount": 100,
                //     "amount_decimal": "100",
                //     "billing_scheme": "per_unit",
                //     "created": 1585076219,
                //     "currency": "eur",
                //     "interval": "day",
                //     "interval_count": 1,
                //     "livemode": false,
                //     "metadata": [],
                //     "nickname": "License",
                //     "product": "prod_GyEOQCmv5Mjg06",
                //     "tiers": null,
                //     "tiers_mode": null,
                //     "transform_usage": null,
                //     "trial_period_days": null,
                //     "usage_type": "licensed"
                // },
                // "quantity": 1,
                // "subscription": "sub_GyESkc8x10mstA",
                // "tax_rates": []
                //html = 'Super Forms • License';
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-pricing-plan';
                column.innerHTML = html;
                newRow.appendChild(column);

                // QTY
                html = '';
                for (i = 0; i < payload.items.data.length; i++) {
                    item = payload.items.data[i];
                    html += item.quantity + '<br />';
                }
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-pricing-qty';
                column.innerHTML = html;
                newRow.appendChild(column);

                // Total
                html = '€2.00 EUR / day';
                html = '';
                for (i = 0; i < payload.items.data.length; i++) {
                    item = payload.items.data[i];
                    html += OSREC.CurrencyFormatter.format((item.plan.amount*item.quantity) / 100, {
                        currency: item.plan.currency
                    }) + ' ' + item.plan.currency.toUpperCase();
                    if (item.plan.interval_count > 1) {
                        var pluralName = '';
                        if (item.plan.interval === 'day') pluralName = 'days';
                        if (item.plan.interval === 'week') pluralName = 'weeks';
                        if (item.plan.interval === 'month') pluralName = 'months';
                        html += ' every ' + item.plan.interval_count + ' ' + pluralName;
                    } else {
                        html += ' / ' + item.plan.interval;
                    }
                    html += '<br />';
                }
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-pricing-total';
                column.innerHTML = html;
                newRow.appendChild(column);

                // html += '<a class="super-stripe-stripe-pricing" target="_blank" href="'+super_stripe_dashboard_i18n.dashboardUrl+'/plans/' + payload.plan.id + '">';
                // html += payload.plan.nickname;
                // html += '</a>';
                // column = document.createElement('div');
                // column.className = columnClass + 'super-stripe-pricing';
                // column.innerHTML = html;
                // newRow.appendChild(column);

                // "plan": {
                //     "id": "plan_GyEOetWFv2Hkr1",
                //     "object": "plan",
                //     "active": true,
                //     "aggregate_usage": null,
                //     "amount": 100,
                //     "amount_decimal": "100",
                //     "billing_scheme": "per_unit",
                //     "created": 1585076219,
                //     "currency": "eur",
                //     "interval": "day",
                //     "interval_count": 1,
                //     "livemode": false,
                //     "metadata": [],
                //     "nickname": "Daily",
                //     "product": "prod_GyEOQCmv5Mjg06",
                //     "tiers": null,
                //     "tiers_mode": null,
                //     "transform_usage": null,
                //     "trial_period_days": null,
                //     "usage_type": "licensed"
                // },

                // // Product
                // html = '';
                // // html += '<a class="super-stripe-stripe-product" target="_blank" href="'+super_stripe_dashboard_i18n.dashboardUrl+'/subscriptions/products/' + payload.plan.product + '">';
                // // html += payload.productName;
                // // html += '</a>';
                // column = document.createElement('div');
                // column.className = columnClass + 'super-stripe-product';
                // column.innerHTML = html;
                // newRow.appendChild(column);

                // Date
                column = document.createElement('div');
                column.className = columnClass + 'super-stripe-date';
                column.innerHTML = payload.createdFormatted;
                newRow.appendChild(column);

                // Add the row to the parent (the list/table)
                parentNode.appendChild(newRow);
            }


            // products: function (payload) {
            //     var parentNode = app.q('.super-stripe-products > table > tbody');
            //     var newRow = document.createElement('tr');

            //     var column = document.createElement('td');
            //     column.className = 'super-stripe-status';
            //     column.innerHTML = payload.status;
            //     newRow.appendChild(column);

            //     column = document.createElement('td');
            //     column.className = 'super-stripe-amount';
            //     column.innerHTML = payload.amountFormatted;
            //     newRow.appendChild(column);

            //     column = document.createElement('td');
            //     column.className = 'super-stripe-description';
            //     column.innerHTML = payload.description;
            //     newRow.appendChild(column);

            //     column = document.createElement('td');
            //     column.className = 'super-stripe-customer';
            //     //column.innerHTML = payload.customer;
            //     newRow.appendChild(column);

            //     column = document.createElement('td');
            //     column.className = 'super-stripe-shipping';
            //     column.innerHTML = payload.shipping;
            //     newRow.appendChild(column);

            //     parentNode.appendChild(newRow);
            //     app.addClass(app.q('.super-stripe-products'), 'super-initialized');
            //     //lastChild.parentNode.insertBefore(newRow, lastChild.nextSibling);
            // },


        },
        handler: function (data) {
            var i, xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (this.readyState === 4) {
                    if (this.status === 200) { // Success:
                        console.log('Type:', data.type);
                        console.log('Response:', this.response);
                        try {
                            var payload = JSON.parse(this.response);
                        } catch (error) {
                            alert(error);
                        }
                        if (data.type == 'searchUsers') {
                            console.log(data);
                            app.ui.filterMenu.open(data, payload);
                            return true;
                        }
                        if (data.type == 'connectUser') {
                            console.log(data);
                            console.log(payload);
                            //target.innerHTML = app.ui.svg.loader.html;
                            var html;
                            if (payload.length === 0) {
                                // Close filter menu and reset
                                app.ui.filterMenu.remove();
                                console.log('test1', data);
                                html = app.ui.templates.userfilter(payload);
                            } else {
                                // Close filter menu and add wp user as connected user
                                app.ui.filterMenu.remove();
                                html = app.ui.templates.connectedUser(payload.wp_user_info);
                            }
                            var nodes = app.qa('.super-stripe-user[data-id="' + data.customer_id + '"]');
                            for (i = 0; i < nodes.length; i++) {
                                nodes[i].innerHTML = html;
                            }
                            // app.q('#' + data.customer_id).querySelector('.super-stripe-column.super-stripe-user').innerHTML = html;
                            return true;
                        }
                        if (data.type == 'refund.create') {
                            if (payload.status === 'succeeded' || payload.status === 'pending') {
                                app.ui.modal.close(); // Close modal
                                app.ui.toast.show(payload); // Display "View" link
                                // Refresh this payment intent in the table/list
                                app.api.handler({
                                    type: 'refreshPaymentIntent',
                                    id: payload.payment_intent,
                                    limit: 1
                                });
                            } else {
                                app.removeClass(app.qa('.super-stripe-modal.super-loading'), 'super-loading');
                                app.ui.svg.loader.remove(); // Delete loader
                                if (payload.error) {
                                    alert(payload.error.message);
                                }
                            }
                            return true;
                        }
                        if (data.type == 'invoice.online' || data.type == 'invoice.pdf') {
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
                            return true;
                        }
                        if (data.type == 'refreshPaymentIntent') {
                            for (i = 0; i < payload.length; i++) {
                                app.api.addRows[data.type](payload[i]);
                            }
                            return true;
                        }
                        for (i = 0; i < payload.length; i++) {
                            app.api.addRows[data.type](payload[i]);
                        }
                        if (data.type === 'paymentIntents') {
                            app.addClass(app.q('.super-stripe-transactions'), 'super-initialized');
                        }
                        if (data.type === 'customers') {
                            app.addClass(app.q('.super-stripe-customers'), 'super-initialized');
                        }
                        if (data.type === 'subscriptions') {
                            app.addClass(app.q('.super-stripe-subscriptions'), 'super-initialized');
                        }

                    }
                    // Complete:
                    app.ui.svg.loader.remove();
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
            type: 'customers',
            limit: 20
        });
        app.api.handler({
            type: 'subscriptions',
            limit: 20
        });
        // app.api.handler({
        //     type: 'products',
        //     limit: 20
        // });
        // app.api.handler({
        //     type: 'customers',
        //     limit: 20
        // });
    }

    // Remove loader after data has been loaded from the API
    var removeLoader = setInterval(function () {
        var total = app.qa('.super-stripe-tabs-content > div').length;
        var found = app.qa('.super-stripe-tabs-content > .super-initialized').length;
        console.log('test1 interval', total, found);
        if (total > 0 && total == found) {
            app.removeClass(app.qa('.super-stripe-dashboard'), 'super-loading');
            clearInterval(removeLoader);
        }
    }, 100);

    // Trigger Events
    app.triggerEvent = function (e, target, eventType) {
        // Get element actions, and check for multiple event methods
        var actions, _event, _function, _currentFunc, sfevents;
        try {
            sfevents = JSON.parse(target.attributes.sfevents.value);
        } catch (error) {
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
                _function = key.split('.');
                _currentFunc = app;
                for (var i = 0; i < _function.length; i++) {
                    // Skip if function name is 'app'
                    if (_function[i] == 'app') continue;
                    if (_currentFunc[_function[i]]) {
                        _currentFunc = _currentFunc[_function[i]];
                    } else {
                        alert('Function ' + key + '() is undefined!');
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
            '.super-stripe-description > a',
            '.super-stripe-search-users input',
            '.super-stripe-filtermenu > div'
        ],
        change: [
            '.super-stripe-field-wrapper select'
        ],
        keyup: [
            '.super-field-type-currency input',
            '.super-stripe-search-users input'
        ]
    };
    Object.keys(app.events).forEach(function (eventType) {
        var elements = app.events[eventType].join(", ");
        app.delegate(document, eventType, elements, function (e, target) {
            if (eventType == 'click') {
                // Close Context Menu if clicked outside
                if ((!app.inPath(e, app.ui.contextMenu.className)) &&
                    (!app.inPath(e, app.ui.filterMenu.className)) &&
                    (!app.inPath(e, 'super-stripe-action-btn'))) {
                    // Close context menu
                    app.ui.contextMenu.close();
                    app.ui.filterMenu.remove();
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
