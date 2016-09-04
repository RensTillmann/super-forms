<div class="wrap super-marketplace">

    <h1>Super Forms - Marketplace <a href="#TB_inline?width=600&height=450&inlineId=super-add-item" class="thickbox share page-title-action">Add your own Form</a></h1>

    <div id="super-add-item" style="display:none;">
        <div class="super-add-item">
            <div class="super-msg"></div>
            <h1>Add your own form to the marketplace:</h1>
            <?php
            $args = array(
                'post_type' => 'super_form',
                'posts_per_page' => -1
            );
            $forms = get_posts( $args );
            if( count( $forms )==0 ) {
                echo __('No forms found, create one!', 'super-forms' );
            }else{
                echo '<label>';
                echo '<span>Choose your form:</span>';
                echo '<select name="forms">';
                echo '<option value="">- select a form -</option>';
                $items_added = get_option( 'super_marketplace_items_added', array() );
                foreach( $forms as $v ) {
                    if( !in_array( $v->ID, $items_added ) ) {
                        echo '<option value="' . $v->ID . '">'. ($v->post_title=='' ? '(no title)' : $v->post_title) . '</option>';
                    }
                }
                echo '</select>';
                echo '</label>';
            }
            echo '<label>';
            echo '<span>Price (enter 0 to share for free): $</span>';
            echo '<input type="number" name="price" value="0" min="0" max="10" />';
            echo '</label>';

            echo '<label>';
            echo '<span>PayPal email (to receive payments):</span>';
            echo '<input type="text" name="paypal" />';
            echo '</label>';

            echo '<label>';
            echo '<span>Contact email address:</span>';
            echo '<input type="text" name="email" placeholder="* Used to notify you when approved" />';
            echo '</label>';

            echo '<label>';
            echo '<span>Tags related to your form (max 5):</span>';
            echo '<input type="text" name="tags" placeholder="separated by comma\'s" />';
            echo '<span></span><p class="generated-tags"></p>';
            echo '</label>';

            echo '<input type="hidden" name="author" value="' . $author . '" />';

            echo '<label>';
            echo '<span></span>';
            echo '<a href="#" class="button button-primary super-submit">Add my form to the marketplace!</a>';
            echo '</label>';

            echo '<p><i>* Every submission will be reviewed by our staff, you will be notified by mail when it has been approved.</i></p> '

            ?>
        </div>
    </div>

    <h2 class="screen-reader-text">Filter forms list</h2>
    <div class="wp-filter">
        <ul class="filter-links">
            <?php
            $tabs = array(
                'newest', 'popular', 'free', 'paid', 'your-Forms'
            );
            $admin_url = get_admin_url();
            foreach($tabs as $v){
                echo '<li><a href="' . $admin_url . 'admin.php?page=super_marketplace&tab=' . strtolower($v) . '" ' . ( strtolower($v)==$tab ? 'class="current"' : '' ) . '>' . ucfirst(str_replace('-',' ',$v)) . '</a></li>';
            }
            ?>
        </ul>
        <form class="search-form search-plugins" method="get">
            <input type="hidden" name="page" value="super_marketplace" />
            <input type="hidden" name="tab" value="<?php echo $tab; ?>" />
            <label><span class="screen-reader-text"><?php echo __( 'Search Forms', 'super-forms' ); ?></span>
                <input type="search" name="s" value="<?php echo $s; ?>" class="wp-filter-search" placeholder="<?php echo __( 'Search Forms', 'super-forms' ); ?>" />
            </label>
            <input type="submit" id="search-submit" class="button screen-reader-text" value="<?php echo __( 'Search Forms', 'super-forms' ); ?>" />
        </form>
    </div>

    <br class="clear">

    <?php
    if(count($items)!=0){
        echo '<p>';
        echo __( 'The below forms have been added by other Super Forms users. You can install any form of your choosing, or even add your own forms to this list!', 'super-forms' );
        echo '</p>';
    }
    if( (isset($_GET['payment'])) && ($_GET['payment']=='completed') ) {
        echo '<div class="super-msg super-success">';
        echo __( 'Your payment has been completed, you can now import the form!', 'super-forms' );
        echo '</div>';
    }else if( (isset($_GET['payment'])) && ($_GET['payment']=='canceled') ) {
        echo '<div class="super-msg super-error">';
        echo __( 'You canceled your payment, we have brought you back to the page you came from!', 'super-forms' );
        echo '</div>';
    }

    $paged_url = $admin_url . 'admin.php?page=super_marketplace&tab=' . strtolower($tab);
    if( (isset($_GET['s'])) && ($_GET['s']!='') ) {
        $paged_url .= '&s=' . $_GET['s'];
    }
    ob_start();
    ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo $total; ?> items</span>
            <span class="pagination-links">
                <?php
                if( $paged==1 ) {
                    echo '<span class="tablenav-pages-navspan" aria-hidden="true">«</span> ';
                }else{
                    echo '<a class="first-page" href="' . $paged_url . '">';
                        echo '<span class="screen-reader-text">First page</span>';
                        echo '<span aria-hidden="true">«</span>';
                    echo '</a> ';
                }
                if( $paged==1 ) {
                    echo '<span class="tablenav-pages-navspan" aria-hidden="true">‹</span> ';
                }else{
                    echo '<a class="prev-page" href="' . $paged_url . '&paged=' . ($paged-1) . '" >';
                    echo '<span class="screen-reader-text">Previous page</span>';
                    echo '<span aria-hidden="true">‹</span>';
                    echo '</a> ';
                }
                ?>
                <span class="screen-reader-text">Current Page</span>
                <span id="table-paging" class="paging-input"><?php echo $paged; ?> of <span class="total-pages"><?php echo $total_pages; ?></span></span>
                <?php
                if( $paged==$total_pages ) {
                    echo '<span class="tablenav-pages-navspan" aria-hidden="true">›</span> ';
                }else{
                    echo '<a class="next-page" href="' . $paged_url . '&paged=' . ($paged+1) . '">';
                        echo '<span class="screen-reader-text">Next page</span>';
                        echo '<span aria-hidden="true">›</span>';
                    echo '</a> ';
                }
                if( $paged>=($total_pages-1) ) {
                    echo '<span class="tablenav-pages-navspan" aria-hidden="true">»</span> ';
                }else{
                    echo '<a class="last-page" href="' . $paged_url . '&paged=' . $total_pages . '">';
                        echo '<span class="screen-reader-text">Last page</span>';
                        echo '<span aria-hidden="true">»</span>';
                    echo '</a> ';
                }
                ?>
            </span>
        </div>
    </div>
    <?php
    $pagination = ob_get_contents();
    ob_end_clean();
    echo $pagination;
    ?>

    <br class="clear">

    <div class="wp-list-table widefat plugin-install">
        <h2 class="screen-reader-text">Forms list</h2>
        <div id="the-list">
            
            <?php
            if(count($items)==0){
                echo '<div class="no-plugin-results">' . __( 'No forms match your request.', 'super-forms' ) . '</div>';
            }else{
                foreach($items as $k => $v){
                    ?>
                    <div class="plugin-card<?php echo ( ( ($v->price==0) || (in_array($v->id, $licenses_new)) ) ? ' owned' : '' ); ?>" data-id="<?php echo $v->id; ?>">
                        <div class="plugin-card-top">
                            <div class="name column-name">
                                <h3>
                                    <?php
                                    if($v->approved==1){
                                        echo '<a href="' . $v->live_preview . '?TB_iframe=true&width=700&height=550" class="thickbox">';
                                    }else{
                                        echo '<a href="#">';
                                    }
                                    echo $v->title;
                                    echo '</a>';
                                    ?>
                                </h3>
                            </div>
                            <div class="action-links">
                                <ul class="plugin-action-buttons">
                                    <li>
                                        <?php
                                        if($v->approved==0){
                                            echo '<span class="under-review">Under review</span>';
                                        }else{
                                            if( ($v->price==0) || ($author==$v->author) ) {
                                                echo '<span class="install-now button">Install Now</span>';
                                            }else{
                                                if( in_array( $v->id, $licenses_new ) ) {
                                                    echo '<span class="install-now button">Install Now</span>';
                                                }else{
                                                    echo '<span class="purchase-now button">Purchase $'.$v->price.'</span>';
                                                }
                                            }
                                        }
                                        ?>
                                    </li>
                                    <?php
                                    if($v->approved==1){
                                        ?>
                                        <li>
                                            <a href="<?php echo $v->live_preview; ?>?TB_iframe=true&width=700&height=550" class="thickbox">Live Preview</a>
                                            <!---#TB_inline?width=600&height=450&inlineId=super-add-item-->
                                        </li>
                                        <li>
                                            <a href="#" class="report">Report</a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                            </div>
                            <div class="desc column-description">
                                <?php
                                if($v->approved==0){
                                    echo '<p>This item is currently being reviewed by one of our staff members. You will be notified on approval.</p>';
                                }else{
                                    echo '<p>' . $v->description . '</p>';
                                }
                                ?>
                                <p class="authors"> <cite>By <a href="#"><?php echo $v->author; ?></a></cite></p>
                            </div>
                        </div>
                        <div class="plugin-card-bottom">
                            <div class="vers column-rating">
                                <?php 
                                $total_ratings = $v->rating_total;
                                $sum_ratings = $v->rating_sum;
                                $avarage = 0;
                                if($total_ratings>0){
                                    $avarage = $sum_ratings / $total_ratings;
                                }
                                ?>
                                <div class="star-rating">
                                    <span class="screen-reader-text">4.0 rating based on <?php echo $total_ratings; ?> ratings</span>
                                    <?php
                                    if($avarage>=1){
                                        echo '<div class="star star-full"></div>';
                                    }else{
                                        if($avarage>0){
                                            echo '<div class="star star-half"></div>';
                                        }else{
                                             echo '<div class="star star-empty"></div>';
                                        }
                                    }
                                    if($avarage>=2){
                                        echo '<div class="star star-full"></div>';
                                    }else{
                                        if($avarage>1){
                                            echo '<div class="star star-half"></div>';
                                        }else{
                                             echo '<div class="star star-empty"></div>';
                                        }
                                    }
                                    if($avarage>=3){
                                        echo '<div class="star star-full"></div>';
                                    }else{
                                        if($avarage>2){
                                            echo '<div class="star star-half"></div>';
                                        }else{
                                             echo '<div class="star star-empty"></div>';
                                        }
                                    }
                                    if($avarage>=4){
                                        echo '<div class="star star-full"></div>';
                                    }else{
                                        if($avarage>3){
                                            echo '<div class="star star-half"></div>';
                                        }else{
                                             echo '<div class="star star-empty"></div>';
                                        }
                                    }
                                    if($avarage>=5){
                                        echo '<div class="star star-full"></div>';
                                    }else{
                                        if($avarage>4){
                                            echo '<div class="star star-half"></div>';
                                        }else{
                                             echo '<div class="star star-empty"></div>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="num-ratings" aria-hidden="true">(<?php echo $total_ratings; ?>)</span>
                            </div>
                            <div class="column-updated">
                                <strong>Created on:</strong> <?php echo date('d M, Y', strtotime($v->date)); ?>
                            </div>
                            <div class="column-downloaded">
                                <?php echo $v->installed; ?> Times installed
                            </div>
                            <div class="column-compatibility">
                                <span class="compatibility-compatible"><strong>Requirements:</strong> <?php echo ($v->approved==1 ? ($v->requirements!='' ? $v->requirements : 'None') : 'Under review'); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
    
    <br class="clear">

    <?php
    echo $pagination;
    ?>

    <br class="clear">

    <?php
    if(count($items)!=0){
        ?>
        <h2><?php echo __( 'Popular tags', 'super-forms' ); ?></h2>
        <p><?php echo __( 'You may also browse based on the most popular tags in the Form Directory:', 'super-forms' ); ?></p>
        <p class="popular-tags">
            <?php
            $max = $tags[0]->total;
            $min = end($tags)->total;
            $divided = array();
            $divided[0] = $max / 5;
            $divided[1] = $divided[0] * 2;
            $divided[2] = $divided[0] * 3;
            $divided[3] = $divided[0] * 4;
            foreach($tags as $v){
                if($v->total < $divided[0]) {
                    $font_size = 8;
                }else if($v->total < $divided[1]) {
                    $font_size = 10;
                }else if($v->total < $divided[2]) {
                    $font_size = 12;
                }else if($v->total < $divided[3]) { 
                    $font_size = 14;
                }else if($v->total <= $max) { 
                    $font_size = 18;
                }else {
                    $font_size = 18;
                }
                echo '<a href="' . $admin_url . 'admin.php?page=super_marketplace&tag=' . ($v->tag) . '" class="tag-link-woocommerce tag-link-position-98" title="' . $v->total . ' forms" style="font-size:' . $font_size . 'pt;">' . $v->tag . '</a>';
            }
            ?>
        </p>
        <br class="clear">
        <?php
    }
    ?>

</div>



