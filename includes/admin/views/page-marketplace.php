<div class="wrap super-marketplace">

    <h1>Super Forms - Marketplace <a href="#TB_inline?width=600&height=550&inlineId=super-add-item" class="thickbox share page-title-action">Add your own Form</a></h1>

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
            if(count($forms)==0){
                echo __('No forms found, create one!', 'super-forms' );
            }else{
                echo '<label>';
                echo '<span>Choose your form:</span>';
                echo '<select name="forms">';
                echo '<option value="">- select a form -</option>';
                foreach($forms as $v){
                    echo '<option value="' . $v->ID . '">'. $v->post_title . '</option>';
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
                'newest', 'popular', 'free', 'paid'
            );
            $admin_url = get_admin_url();
            foreach($tabs as $v){
                echo '<li><a href="' . $admin_url . 'admin.php?page=super_marketplace&tab=' . $v . '" ' . ( $v==$tab ? 'class="current"' : '' ) . '>' . ucfirst($v) . '</a></li>';
            }
            ?>
        </ul>
        <form class="search-form search-plugins" method="get">
            <input type="hidden" name="page" value="super_marketplace" />
            <input type="hidden" name="tab" value="<?php echo $tab; ?>" />
            <label><span class="screen-reader-text">Search Forms</span>
                <input type="search" name="s" value="<?php echo $s; ?>" class="wp-filter-search" placeholder="Search Forms" />
            </label>
            <input type="submit" id="search-submit" class="button screen-reader-text" value="Search Forms" />
        </form>
    </div>

    <br class="clear">

    <?php
    if(count($items)!=0){
        echo '<p>The below forms have been added by other Super Forms users. You can install any form of your choosing, or even add your own forms to this list!</p>';
    }
    if( (isset($_GET['payment'])) && ($_GET['payment']=='completed') ) {
        echo '<div class="super-msg super-success">Your payment has been completed, you can now import the form!</div>';
    }
    ?>

    <div class="wp-list-table widefat plugin-install">
        <h2 class="screen-reader-text">Forms list</h2>
        <div id="the-list">
            
            <?php
            if(count($items)==0){
                echo '<div class="no-plugin-results">' . __( 'No forms match your request.', 'super-forms' ) . '</div>';
            }else{
                foreach($items as $k => $v){
                    ?>
                    <div class="plugin-card" data-id="<?php echo $v->id; ?>">
                        <div class="plugin-card-top">
                            <div class="name column-name">
                                <h3>
                                    <a href="#" class="thickbox open-plugin-details-modal">
                                        <?php echo $v->title; ?>
                                    </a>
                                </h3>
                            </div>
                            <div class="action-links">
                                <ul class="plugin-action-buttons">
                                    <li>
                                        <?php
                                        if($v->price==0){
                                            echo '<span class="install-now button">Install Now</span>';
                                        }else{
                                            if( in_array( $v->id, $licenses_new ) ) {
                                                echo '<span class="install-now button">Install Now</span>';
                                            }else{
                                                echo '<span class="purchase-now button">Purchase $'.$v->price.'</span>';
                                            }
                                        }
                                        ?>
                                        <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                                            <input type="hidden" name="cmd" value="_xclick">
                                            <input type="hidden" name="item_name" value="<?php echo $v->title; ?>">
                                            <input type="hidden" name="item_number" value="<?php echo $v->id; ?>">
                                        </form>
                                    </li>
                                    <li>
                                        <a href="<?php echo $v->live_preview; ?>?TB_iframe=true" class="thickbox">Live Preview</a>
                                    </li>
                                    <li>
                                        <a href="#" class="report">Report</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="desc column-description">
                                <p><?php echo $v->description; ?></p>
                                <p class="authors"> <cite>By <a href="#"><?php echo $v->author; ?></a></cite></p>
                            </div>
                        </div>
                        <div class="plugin-card-bottom">
                            <div class="vers column-rating">
                                <?php 
                                $ratings = explode(',', $v->ratings);
                                $ratings = array_filter($ratings);
                                $total_ratings = count($ratings);
                                $sum_ratings = array_sum($ratings);
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
                                <span class="compatibility-compatible"><strong>Requirements:</strong> <?php echo $v->requirements; ?></span>
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
    if(count($items)!=0){
        ?>
        <h2>Popular tags</h2>
        <p>You may also browse based on the most popular tags in the Form Directory:</p>
        <p class="popular-tags">
            <a href="http://f4d.nl/dev/wp-admin/plugin-install.php?tab=search&amp;type=tag&amp;s=woocommerce" class="tag-link-woocommerce tag-link-position-98" title="1,814 plugins" style="font-size: 15.830508474576pt;">woocommerce</a>
            <a href="http://f4d.nl/dev/wp-admin/plugin-install.php?tab=search&amp;type=tag&amp;s=wordpress" class="tag-link-wordpress tag-link-position-99" title="1,559 plugins" style="font-size: 15pt;">wordpress</a>
            <a href="http://f4d.nl/dev/wp-admin/plugin-install.php?tab=search&amp;type=tag&amp;s=youtube" class="tag-link-youtube tag-link-position-100" title="777 plugins" style="font-size: 11.440677966102pt;">youtube</a>
        </p>
        <br class="clear">
        <?php
    }
    ?>

</div>



