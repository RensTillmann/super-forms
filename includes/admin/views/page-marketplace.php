<div class="wrap super-marketplace">

    <h1>Demo Forms <a href="#" class="share page-title-action">Share your own Form</a></h1>

    <h2 class="screen-reader-text">Filter forms list</h2>
    <div class="wp-filter">
        <ul class="filter-links">
            <li><a href="?tab=newest" class="current">Newest</a></li>
            <li><a href="?tab=popular" class="">Popular</a></li>
            <li><a href="?tab=free" class="">Free</a></li>
            <li><a href="?tab=paid" class="">Paid</a></li>
        </ul>

        <form class="search-form search-plugins" method="get">
            <input type="hidden" name="tab" value="search">
            <label><span class="screen-reader-text">Search Forms</span>
                <input type="search" name="s" value="" class="wp-filter-search" placeholder="Search Forms">
            </label>
            <input type="submit" id="search-submit" class="button screen-reader-text" value="Search Forms">
        </form>
    </div>

    <br class="clear">

    <p>The below forms are created and shared by other Super Forms users. You can import any form of your choosing, or even share your own created form!</a>.</p>

    <form id="plugin-filter" method="post">
        <div class="wp-list-table widefat plugin-install">
            <h2 class="screen-reader-text">Forms list</h2>
            <div id="the-list">
                
                <?php
                $demo_forms = json_decode($demo_forms);
                foreach($demo_forms as $k => $v){
                    ?>
                    <div class="plugin-card plugin-card-bbpress">
                        <div class="plugin-card-top">
                            <div class="name column-name">
                                <h3>
                                    <a href="http://f4d.nl/dev/wp-admin/plugin-install.php?tab=plugin-information&amp;plugin=bbpress&amp;TB_iframe=true&amp;width=600&amp;height=550" class="thickbox open-plugin-details-modal">
                                        <?php echo $v->title; ?>
                                    </a>
                                </h3>
                            </div>
                            <div class="action-links">
                                <ul class="plugin-action-buttons">
                                    <li><span class="button button-disabled">Installed</span></li>
                                    <li><a href="http://f4d.nl/dev/wp-admin/plugin-install.php?tab=plugin-information&amp;plugin=bbpress&amp;TB_iframe=true&amp;width=600&amp;height=550" class="thickbox open-plugin-details-modal" aria-label="More information about bbPress 2.5.10" data-title="bbPress 2.5.10">Live Preview</a></li>
                                </ul>
                            </div>
                            <div class="desc column-description">
                                <p><?php echo $v->description; ?></p>
                                <p class="authors"> <cite>By <a href="https://bbpress.org"><?php echo $v->author; ?></a></cite></p>
                            </div>
                        </div>
                        <div class="plugin-card-bottom">
                            <div class="vers column-rating">
                                <?php 
                                $ratings = explode(',', $v->ratings);
                                $ratings = array_filter($ratings);
                                $total_ratings = count($ratings);
                                $sum_ratings = array_sum($ratings);
                                $avarage = $sum_ratings / $total_ratings;
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
                                <strong>Shared on:</strong> <?php echo date('d M, Y', strtotime($v->date)); ?>
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
                ?>


            </div>
        </div>
    </form>
    
    <br class="clear">

    <h2>Popular tags</h2>
    <p>You may also browse based on the most popular tags in the Form Directory:</p>
    <p class="popular-tags">
        <a href="http://f4d.nl/dev/wp-admin/plugin-install.php?tab=search&amp;type=tag&amp;s=woocommerce" class="tag-link-woocommerce tag-link-position-98" title="1,814 plugins" style="font-size: 15.830508474576pt;">woocommerce</a>
        <a href="http://f4d.nl/dev/wp-admin/plugin-install.php?tab=search&amp;type=tag&amp;s=wordpress" class="tag-link-wordpress tag-link-position-99" title="1,559 plugins" style="font-size: 15pt;">wordpress</a>
        <a href="http://f4d.nl/dev/wp-admin/plugin-install.php?tab=search&amp;type=tag&amp;s=youtube" class="tag-link-youtube tag-link-position-100" title="777 plugins" style="font-size: 11.440677966102pt;">youtube</a>
    </p>
    <br class="clear">
</div>



