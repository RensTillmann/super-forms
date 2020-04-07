<div class="super-stripe-dashboard super-loading">

    <div class="super-stripe-loader">
        <div>
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="12" cy="12" rx="10" ry="10"></ellipse>
            </svg>
        </div>
    </div>

    <div class="super-stripe-tabs">
        <div class="super-stripe-tab super-active" sfevents='{"click":{"ui.tabs.open":{}}}'>
            <?php echo esc_html__( 'Transactions', 'super-forms' ); ?>
        </div>
        <div class="super-stripe-tab" sfevents='{"click":{"ui.tabs.open":{}}}'>
            <?php echo esc_html__( 'Customers', 'super-forms' ); ?>
        </div>
        <div class="super-stripe-tab" sfevents='{"click":{"ui.tabs.open":{}}}'>
            <?php echo esc_html__( 'Subscriptions', 'super-forms' ); ?>
        </div>
        <?php
        /*
        <div class="super-stripe-tab" sfevents='{"click":{"ui.tabs.open":{}}}'>
            <?php echo esc_html__( 'Products', 'super-forms' ); ?>
        </div>
        <div class="super-stripe-tab" sfevents='{"click":{"ui.tabs.open":{}}}'>
            <?php echo esc_html__( 'Customers', 'super-forms' ); ?>
        </div>
        */
        ?>
    </div>
    <div class="super-stripe-tabs-content">

        <div class="super-stripe-transactions super-active">
            <div class="super-stripe-action-bar">
                <a target="_blank" href="<?php echo esc_url($dashboardUrl); ?>payments/filter" class="super-stripe-action-btn super-stripe-filters">
                    <svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;">
                        <path d="M13.994.004c.555 0 1.006.448 1.006 1a.997.997 0 0 1-.212.614l-5.782 7.39L9 13.726a1 1 0 0 1-.293.708L7.171 15.97A.1.1 0 0 1 7 15.9V9.008l-5.788-7.39A.996.996 0 0 1 1.389.214a1.01 1.01 0 0 1 .617-.21z" fill-rule="evenodd"></path>
                    </svg>
                    <span><?php echo esc_html__( 'Filter', 'super-forms' ); ?></span>
                </a>
                <a target="_blank" href="<?php echo esc_url($dashboardUrl); ?>payments" class="super-stripe-action-btn super-stripe-export">
                    <svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;">
                        <path d="M15 10.006a1 1 0 1 1-2 0v-5.6L2.393 15.009a.992.992 0 1 1-1.403-1.404L11.595 3.002h-5.6a1 1 0 0 1 0-2.001h8.02a1 1 0 0 1 .284.045.99.99 0 0 1 .701.951z" fill-rule="evenodd"></path>
                    </svg>
                    <span><?php echo esc_html__( 'Export', 'super-forms' ); ?></span>
                </a>
                <a target="_blank" href="<?php echo esc_url($dashboardUrl); ?>payments/new" class="super-stripe-action-btn super-stripe-create">  
                    <svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;">
                        <path d="M9 7h6a1 1 0 0 1 0 2H9v6a1 1 0 0 1-2 0V9H1a1 1 0 1 1 0-2h6V1a1 1 0 1 1 2 0z" fill-rule="evenodd"></path>
                    </svg>
                    <span><?php echo esc_html__( 'New', 'super-forms' ); ?></span>
                </a>
            </div>

            <?php 
            $columns = array(
                'actions' => esc_html__( 'Actions', 'super-forms' ),
                'amount' => esc_html__( 'Amount', 'super-forms' ),
                'currency' => '',
                'status' => '',
                'description' => esc_html__( 'Description', 'super-forms' ),
                'customer' => esc_html__( 'Customer/User', 'super-forms' ),
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
            <div class="super-stripe-load-more">
                <span class="super-stripe-action-btn super-stripe-default" sfevents='{"click":{"api.loadMore":{"type":"paymentIntents"}}}'>
                    <?php echo esc_html__( 'Load More', 'super-forms' ); ?>
                </span>
            </div>
        </div>

        <div class="super-stripe-customers">
            <div class="super-stripe-action-bar">
                <a target="_blank" href="<?php echo esc_url($dashboardUrl); ?>customers" class="super-stripe-action-btn super-stripe-filters">
                    <svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;">
                        <path d="M13.994.004c.555 0 1.006.448 1.006 1a.997.997 0 0 1-.212.614l-5.782 7.39L9 13.726a1 1 0 0 1-.293.708L7.171 15.97A.1.1 0 0 1 7 15.9V9.008l-5.788-7.39A.996.996 0 0 1 1.389.214a1.01 1.01 0 0 1 .617-.21z" fill-rule="evenodd"></path>
                    </svg>
                    <span><?php echo esc_html__( 'Filter', 'super-forms' ); ?></span>
                </a>
                <a target="_blank" href="<?php echo esc_url($dashboardUrl); ?>customers" class="super-stripe-action-btn super-stripe-export">
                    <svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;">
                        <path d="M15 10.006a1 1 0 1 1-2 0v-5.6L2.393 15.009a.992.992 0 1 1-1.403-1.404L11.595 3.002h-5.6a1 1 0 0 1 0-2.001h8.02a1 1 0 0 1 .284.045.99.99 0 0 1 .701.951z" fill-rule="evenodd"></path>
                    </svg>
                    <span><?php echo esc_html__( 'Export', 'super-forms' ); ?></span>
                </a>
                <a target="_blank" href="<?php echo esc_url($dashboardUrl); ?>customers" class="super-stripe-action-btn super-stripe-create">  
                    <svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;">
                        <path d="M9 7h6a1 1 0 0 1 0 2H9v6a1 1 0 0 1-2 0V9H1a1 1 0 1 1 0-2h6V1a1 1 0 1 1 2 0z" fill-rule="evenodd"></path>
                    </svg>
                    <span><?php echo esc_html__( 'New', 'super-forms' ); ?></span>
                </a>
            </div>

            <?php 
            $columns = array(
                'actions' => esc_html__( 'Actions', 'super-forms' ),
                'email' => esc_html__( 'Email', 'super-forms' ),
                'description' => esc_html__( 'Description', 'super-forms' ),
                'customer' => esc_html__( 'Customer', 'super-forms' ),
                'user' => esc_html__( 'User', 'super-forms' ),
                'date' => esc_html__( 'Created', 'super-forms' ),
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
            <div class="super-stripe-load-more">
                <span class="super-stripe-action-btn super-stripe-default" sfevents='{"click":{"api.loadMore":{"type":"customers"}}}'>
                    <?php echo esc_html__( 'Load More', 'super-forms' ); ?>
                </span>
            </div>
        </div>


        <div class="super-stripe-subscriptions">
            <div class="super-stripe-action-bar">
                <a target="_blank" href="<?php echo esc_url($dashboardUrl); ?>subscriptions" class="super-stripe-action-btn super-stripe-filters">
                    <svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;">
                        <path d="M13.994.004c.555 0 1.006.448 1.006 1a.997.997 0 0 1-.212.614l-5.782 7.39L9 13.726a1 1 0 0 1-.293.708L7.171 15.97A.1.1 0 0 1 7 15.9V9.008l-5.788-7.39A.996.996 0 0 1 1.389.214a1.01 1.01 0 0 1 .617-.21z" fill-rule="evenodd"></path>
                    </svg>
                    <span><?php echo esc_html__( 'Filter', 'super-forms' ); ?></span>
                </a>
                <a target="_blank" href="<?php echo esc_url($dashboardUrl); ?>subscriptions" class="super-stripe-action-btn super-stripe-export">
                    <svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;">
                        <path d="M15 10.006a1 1 0 1 1-2 0v-5.6L2.393 15.009a.992.992 0 1 1-1.403-1.404L11.595 3.002h-5.6a1 1 0 0 1 0-2.001h8.02a1 1 0 0 1 .284.045.99.99 0 0 1 .701.951z" fill-rule="evenodd"></path>
                    </svg>
                    <span><?php echo esc_html__( 'Export', 'super-forms' ); ?></span>
                </a>
                <a target="_blank" href="<?php echo esc_url($dashboardUrl); ?>subscriptions" class="super-stripe-action-btn super-stripe-create">  
                    <svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height: 12px; width: 12px;">
                        <path d="M9 7h6a1 1 0 0 1 0 2H9v6a1 1 0 0 1-2 0V9H1a1 1 0 1 1 0-2h6V1a1 1 0 1 1 2 0z" fill-rule="evenodd"></path>
                    </svg>
                    <span><?php echo esc_html__( 'New', 'super-forms' ); ?></span>
                </a>
            </div>

            <?php 
            $columns = array(
                'actions' => esc_html__( 'Actions', 'super-forms' ),
                'customer' => esc_html__( 'Customer', 'super-forms' ),
                'user' => esc_html__( 'User', 'super-forms' ),
                'status' => esc_html__( 'Status', 'super-forms' ),
                'billing' => esc_html__( 'Billing', 'super-forms' ),
                'pricing_plan' => esc_html__( 'Pricing Plan', 'super-forms' ),
                'pricing_qty' => esc_html__( 'QTY', 'super-forms' ),
                'pricing_total' => esc_html__( 'Total', 'super-forms' ),
                'date' => esc_html__( 'Created', 'super-forms' ),
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
            <div class="super-stripe-load-more">
                <span class="super-stripe-action-btn super-stripe-default" sfevents='{"click":{"api.loadMore":{"type":"subscriptions"}}}'>
                    <?php echo esc_html__( 'Load More', 'super-forms' ); ?>
                </span>
            </div>
        </div>       

    </div>
</div>