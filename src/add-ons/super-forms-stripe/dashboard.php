<div class="super-stripe-dashboard super-loading">

    <div class="super-stripe-loader">
        <div>
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="12" cy="12" rx="10" ry="10"></ellipse>
            </svg>
        </div>
    </div>

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
                'actions' => esc_html__( 'Actions', 'super-forms' ),
                'status' => esc_html__( 'Status', 'super-forms' ),
                'livemode' => esc_html__( 'livemode', 'super-forms' ),
                'amount' => esc_html__( 'Amount', 'super-forms' ),
                'description' => esc_html__( 'Description', 'super-forms' ),
                'customer' => esc_html__( 'Customer', 'super-forms' ),
                'shipping' => esc_html__( 'Shipping', 'super-forms' ),
                'method' => esc_html__( 'Payment Method', 'super-forms' ),
                'date' => esc_html__( 'Date', 'super-forms' ),
            );
            echo '<div class="super-stripe-headings">';
                foreach( $columns as $k => $v ) {
                    echo '<div class="super-stripe-heading super-stripe-heading-' . esc_attr($k) . '">';
                        echo esc_html($v);
                    echo '</div>';
                }
            echo '</div>';
            echo '<div class="super-stripe-table-rows"></div>';
            ?>
            <div class="super-stripe-load-more" sfevents='{"click":{"loadMore":{"type":"paymentIntents"}}}'>
                <?php echo esc_html__( 'Load More', 'super-forms' ); ?>
            </div>
        </div>
        <div class="super-stripe-products">
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
                        echo '<th>' . esc_html($v) . '</th>';
                    }
                echo '</tr>';
            echo '</table>';
            ?>
        </div>
        <div class="super-stripe-customers">
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
                        echo '<th>' . esc_html($v) . '</th>';
                    }
                echo '</tr>';
            echo '</table>';
            ?>
        </div>
    </div>
</div>