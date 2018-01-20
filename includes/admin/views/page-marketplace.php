<div class="wrap super-marketplace">

    <h1>Super Forms - Marketplace</h1>

    <h2 class="screen-reader-text">Filter forms list</h2>

    <br class="clear">

    <?php
    $forms = array(
        array(
            'title' => '',
            'live_preview' => '#'
            'elements' => '',
            'settings' => '',
            'date' => '',
            'requirements' => 
        ),
    );
    ?>

    <div class="wp-list-table widefat plugin-install">
        <h2 class="screen-reader-text">Forms list</h2>
        <div id="the-list">
            <?php
            foreach($forms as $k => $v){
                ?>
                <div class="plugin-card">
                    <div class="plugin-card-top">
                        <div class="name column-name">
                            <h3>
                                <?php
                                echo '<a href="' . $v['live_preview'] . '?TB_iframe=true&width=700&height=550" class="thickbox">';
                                echo $v['title'];
                                echo '</a>';
                                ?>
                            </h3>
                        </div>
                        <div class="action-links">
                            <ul class="plugin-action-buttons">
                                <li>
                                    <?php
                                    echo '<span class="install-now button">Install Now</span>';
                                    ?>
                                </li>
                                <li>
                                    <a href="<?php echo $v['live_preview']; ?>?TB_iframe=true&width=700&height=550" class="thickbox">Live Preview</a>
                                    <!---#TB_inline?width=600&height=450&inlineId=super-add-item-->
                                </li>
                            </ul>
                        </div>
                        <div class="desc column-description">
                            <?php
                                echo '<p>' . $v['description'] . '</p>';
                            ?>
                        </div>
                    </div>
                    <div class="plugin-card-bottom">
                        <div class="column-updated">
                            <strong>Created on:</strong> <?php echo date_i18n('d M, Y', strtotime($v['date'])); ?>
                        </div>
                        <div class="column-compatibility">
                            <span class="compatibility-compatible">
                                <strong>Requirements:</strong>
                                <?php 
                                if( !empty($v['requirements']) ) {
                                    
                                }else{
                                    echo __( 'None', 'super-forms' );
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    
    <br class="clear">

</div>