<div class="super-stripe-dashboard">
    <div class="super-stripe-tabs">
        <div class="super-stripe-tab super-active" sfevents='{"click":{"tabs.open":{}}}'>
            <?php echo esc_html__( 'Transactions', 'super-forms' ); ?>
        </div>
        <div class="super-stripe-tab" sfevents='{"click":{"tabs.open":{}}}'>
            <?php echo esc_html__( 'Products', 'super-forms' ); ?>
        </div>
        <div class="super-stripe-tab" sfevents='{"click":{"tabs.open":{}}}'>
            <?php echo esc_html__( 'Customers', 'super-forms' ); ?>
        </div>
    </div>
    <div class="super-stripe-tabs-content">
        <div class="super-stripe-transactions super-active">
            <?php 
            $columns = array(
                'id' => esc_html__( 'Transaction ID', 'super-forms' ),
                'status' => esc_html__( 'Status', 'super-forms' ),
                'livemode' => esc_html__( 'livemode', 'super-forms' ),
                'amount' => esc_html__( 'Amount', 'super-forms' ),
                'description' => esc_html__( 'Description', 'super-forms' ),
                'customer' => esc_html__( 'Customer', 'super-forms' ),
                'shipping' => esc_html__( 'Shipping', 'super-forms' ),
                'invoice' => esc_html__( 'Invoice', 'super-forms' ),
            );
            echo '<table cellspacing="0" cellpadding="0">';
                echo '<tr>';
                    foreach($columns as $k => $v){
                        echo '<th>'.$v.'</th>';
                    }
                echo '</tr>';
                $paymentIntents = self::getPaymentIntents(3);
                foreach( $paymentIntents as $k => $v ) {
                    $v = $v->toArray();
                    $startingAfter = $v['id'];
                    echo '<tr>';
                    foreach($columns as $ck => $cv){
                        echo '<td class="super-stripe-row super-stripe-'.$ck.'">';
                        if(!empty($v[$ck])) {
                            echo $v[$ck];
                        }else{
                            echo '-';
                        }
                        echo '</td>';
                    }
                    echo '</tr>';
                    // echo '<br />id: ' . $v['id'];
                    // echo '<br />object: ' . $v['object'];
                    // echo '<br />status: ' . $v['status'];
                    // echo '<br />amount: ' . $v['amount'];
                    // echo '<br />currency: ' . $v['currency'];
                    // echo '<br />customer: ' . $v['customer'];
                    // echo '<br />description: ' . $v['description'];
                    // echo '<br />invoice: ' . $v['invoice'];
                    // echo '<br />last_payment_error: ' . json_encode($v['last_payment_error']);
                    // echo '<br />livemode: ' . $v['livemode'];
                    // echo '<br />metadata: ' . json_encode($v['metadata']);
                    // echo '<br />shipping: ' . json_encode($v['shipping']);
                }
            echo '</table>';
            ?>
            <div class="super-stripe-load-more" sfevents='{"click":{"loadMore":{"type":"paymentIntents"}}}'>
                <?php echo esc_html__( 'Load More', 'super-forms' ); ?>
            </div>
        </div>
        <div class="super-stripe-products">
            <?php 
            $products = self::getProducts(3);
            var_dump($products);
            ?>
        </div>
        <div class="super-stripe-customers">
            <?php 
            $customers = self::getCustomers(3);
            var_dump($customers);
            ?>
        </div>
    </div>
</div>



<script>
(function () {
    "use strict"; // Minimize mutable state :)
    // Define all the events for our elements
    var app = {};
    // querySelector shorthand
    app.q = function(s){
        return document.querySelector(s);
    };
    // Top element
    app.wrapper = app.q('.super-stripe-dashboard');
    // querySelectorAll shorthand
    app.qa = function(s){
        return document.querySelectorAll(s);
    };
    // querySelectorAll based on parent shorthand
    app.qap = function(s, p){
        // If no parent provided, default to Top element
        if(typeof p === 'undefined') p = app.wrapper;
        if(typeof p === 'string') p = app.wrapper.querySelector(p);
        return p.querySelectorAll(s);
    };
    // Remove class from elements
    app.removeClass = function(elements, class_name){
        if(elements.length){
            for (var key = 0; key < elements.length; key++) {
                elements[key].classList.remove(class_name);
            }
        }else{
            elements.classList.remove(class_name);
        }
    };
    // Add class from elements
    app.addClass = function(elements, class_name){
        if(elements.length){
            for (var key = 0; key < elements.length; key++) {
                elements[key].classList.add(class_name);
            }
        }else{
            elements.classList.add(class_name);
        }
    };
    // Get index of element based on parent node
    app.index = function(node, class_name){
        var index = 0;
        while ( node.previousElementSibling ) {
            node = node.previousElementSibling;
            // Based on specified class name
            if(class_name) {
                if(node.classList.contains(class_name)){
                    index++;
                }
            }else{
                index++;
            }
            
        }
        return index;
    };
    app.events = {
        click : [
            '.super-stripe-tab',
            '.super-stripe-load-more'
        ],
    };
    // Switch TABs
    app.tabs = {
        open : function(e, target){
            var index = app.index(target);
            app.removeClass(app.qap('.super-stripe-tab.super-active, .super-stripe-tabs-content > div.super-active'), 'super-active' );
            app.addClass(app.qap(':scope > div', '.super-stripe-tabs-content')[index], 'super-active' );
            app.addClass(target, 'super-active');
        }
    };
    app.serialize = function(obj, prefix) {
        var str = [], p;
        for( p in obj ) {
            if( obj.hasOwnProperty(p) ) {
                var k = prefix ? prefix + "[" + p + "]" : p,
                    v = obj[p];
                str.push((v !== null && typeof v === "object") ? app.serialize(v, k) : encodeURIComponent(k) + "=" + encodeURIComponent(v));
            }
        }
        return str.join("&");
    };
    // Load more Transactions, Products, Customers
    app.loadMore =  function(e, target, type, attr){
        var nodes = app.qa('.super-stripe-transactions .super-stripe-id'),
            lastChild = nodes[nodes.length- 1],
            row = lastChild.parentNode,
            startingAfter = lastChild.innerText;

        console.log(target, type, attr);
        console.log(attr.type);
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            if (this.readyState == 4) {
                if (this.status == 200) { 
                    // Success:
                    var response = JSON.parse(this.response);
                    for( var i=0; i < response.length; i++ ) {
                        var d = response[i];
                        console.log(d);
                        var clone = row.cloneNode(true);
                        if(attr.type=='paymentIntents'){
                            // Generate for Transactions list
                            clone.querySelector('.super-stripe-id').innerText = d.id;
                            clone.querySelector('.super-stripe-status').innerText = d.status;
                            clone.querySelector('.super-stripe-amount').innerText = d.amount;
                            clone.querySelector('.super-stripe-description').innerText = d.description;
                            clone.querySelector('.super-stripe-customer').innerText = d.customer;
                            clone.querySelector('.super-stripe-shipping').innerText = d.shipping;
                            clone.querySelector('.super-stripe-invoice').innerText = d.invoice;
                        }
                        row.parentNode.insertBefore(clone, row.nextSibling);
                    }
                }
                // Complete:
            }
        };
        xhttp.onerror = function () {
            console.log(this);
            console.log("** An error occurred during the transaction");
        };
        xhttp.open("POST", ajaxurl, true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
        var params = {
            action: 'super_stripe_load_more',
            type: attr.type, // e.g: 'paymentIntents', 'products', 'customers'
            startingAfter: startingAfter
        };
        xhttp.send(app.serialize(params));
    };

    // Trigger Events
    app.triggerEvent = function(e, target, event){
        // Get element actions, and check for multiple event methods
        console.log(target);
        var actions, _event, _function, sfevents = JSON.parse(target.attributes.sfevents.value);
        Object.keys(sfevents).forEach(function(key) {
            // Check if contains comma, meaning it has multiple event methods for this function
            _event = key.split(',');
            if(_event.length>1){
                // Seems it contains multiple events, so let's split them up and create a new sfevents object
                Object.keys(_event).forEach(function(e_key) {
                    sfevents[_event[e_key]] = sfevents[key];
                });
                delete sfevents[key];
            }
        });
        actions = sfevents[event];
        if(actions){
            Object.keys(actions).forEach(function(key) { // key = function name
                console.log('event fired: ', key, e, target, event);
                _function = key.split('.');
                if(_function.length>1){
                    if(_function.length>2){ 
                        app[_function[0]][_function[1]][_function[2]](e, target, event, actions[key]);
                    }else{
                        app[_function[0]][_function[1]](e, target, event, actions[key]);
                    } 
                }else{
                    app[_function[0]](e, target, event, actions[key]);
                }
            });
        }
    };
    // Equivalent for jQuery's .on() function
    app.delegate = function(element, event, elements, callback) {
        element.addEventListener(event, function(event) {
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
    Object.keys(app.events).forEach(function(event) {
        var elements = app.events[event].join(", ");
        app.delegate(app.wrapper, event, elements, function(e, target){
            if(typeof target.attributes.sfevents !== 'undefined') app.triggerEvent(e, target, event);
        });
    });
})();
</script>


<style>

.super-stripe-dashboard {
  padding-right:20px;
}

.super-stripe-tabs {
    display: flex; /* or inline-flex */
    border-bottom: 1px solid #ccc;
    padding-top: 9px;
    padding-bottom: 0;
    line-height: inherit;
    margin: 1.5em 0 1em;
}
.super-stripe-tab {
    cursor:pointer;
    border: 1px solid #ccc;
    border-bottom: none;
    margin-left: 0.5em;
    padding: 5px 10px;
    font-size: 14px;
    line-height: 1.71428571;
    font-weight: 600;
    background: #e5e5e5;
    color: #555555;
    text-decoration: none;
    white-space: nowrap;  
}
.super-stripe-tab.super-active,
.super-stripe-tab.super-active:hover {
    cursor:default;
    border-bottom: 1px solid #f1f1f1;
    background: #f1f1f1;
    color: #000;
    margin-bottom: -1px;
}
.super-stripe-tab:hover {
    background-color: #fff;
    color: #444;
}

.super-stripe-tabs-content > div {
    display:none;
}
.super-stripe-tabs-content > div.super-active {
    display:block;
}
.super-stripe-tabs-content table {
  width:100%;
}
.super-stripe-tabs-content th {
  background-color:#23282D;
  color:white;
}
.super-stripe-tabs-content th,
.super-stripe-tabs-content td {
  text-align: left;
  padding:5px 10px 5px 10px;
  margin:0px 0px 0px 0px;
}
.super-stripe-tabs-content tr {
  background-color:#fff;
}
.super-stripe-tabs-content tr td {
  border-bottom:1px solid #D3D3D3;
}
.super-stripe-tabs-content tr:nth-child(even) {
  background-color:#F4F4F4;
}
.super-stripe-tabs-content tr:hover {
  background-color:#EBEBEB;
}





</style>