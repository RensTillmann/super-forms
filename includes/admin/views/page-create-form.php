<div class="sf-builder loading">

    <img class="loader" src="<?php echo SUPER_PLUGIN_FILE . 'assets/images/loader.svg'; ?>" alt="loader">

    <div class="sf-actions">
        <div class="sf-save"></div>
        <div class="sf-settings"></div>
        <div class="sf-theme"></div>
        <div class="sf-preview"></div>
        <div class="sf-add"></div>
        <div class="sf-version">v<?php echo SUPER_VERSION; ?></div>
    </div>

    <div class="sf-canvas">
        <div class="sf-canvas-wrapper">
            <div class="sf-canvas-width">
                <div class="sf-canvas-width-fields">
                    <input type="text" name="width" value="100%" data-action="update_form_width" />
                </div>
            </div>
            <div class="sf-form">
                <div class="sf-form-settings">

                </div>
                <div class="sf-multipart">
                    <div class="sf-multipart-steps"></div>
                    <div class="sf-multipart-progress"></div>
                </div>
                <div class="sf-drop-area">

                </div>
            </div>
        </div>
    </div>

    <div class="sf-elements">
        <div class="sf-filter">
            <div class="sf-wrapper">
                <input type="text" placeholder="Filter elements..." />
            </div>
        </div>

        <div class="sf-items-wrapper">
            <div class="sf-items">

                <?php 
                foreach(SUPER_Forms()->elements as $k => $v){
                    ?>
                    <div class="sf-item sf-type-<?php echo $k; ?>">
                        <div class="sf-title">
                            <span><?php echo $v['title']; ?></span>
                        </div>
                        <div class="sf-preview">
                            <?php echo $v['preview']; ?>
                        </div>
                    </div>
                    <?php
                }
                ?>

                <div class="sf-item sf-type-textarea">
                    <div class="sf-title">
                        <span>Textarea</span>
                    </div>
                    <div class="sf-preview">
                        <textarea placeholder="Dummy placeholder..."></textarea>
                    </div>
                </div>

                <div class="sf-item sf-type-checkbox">
                    <div class="sf-title">
                        <span>Checkboxes</span>
                    </div>
                    <div class="sf-preview">
                        <ul>
                            <li>Option 1</li>
                            <li>Option 2</li>
                            <li>Option 3</li>
                        </ul>
                    </div>
                </div>

                <div class="sf-item sf-type-radio">
                    <div class="sf-title">
                        <span>Radio buttons</span>
                    </div>
                    <div class="sf-preview">
                        <ul>
                            <li>Option 1</li>
                            <li>Option 2</li>
                            <li>Option 3</li>
                        </ul>
                    </div>
                </div>


                <div class="sf-item sf-type-dropdown">
                    <div class="sf-title">
                        <span>Dropdown</span>
                    </div>
                    <div class="sf-preview">
                        <div class="sf-dropdown">- select an option -</div>
                    </div>
                </div>

                <div class="sf-item sf-type-quantity">
                    <div class="sf-title">
                        <span>Quantity</span>
                    </div>
                    <div class="sf-preview">
                        <div class="sf-quantity">
                            <span class="sf-min"></span>
                            <input type="text" value="1" />
                            <span class="sf-plus"></span>
                        </div>
                    </div>
                </div>

                <div class="sf-item sf-type-toggle">
                    <div class="sf-title">
                        <span>Toggle</span>
                    </div>
                    <div class="sf-preview">
                        <div class="sf-toggle">Off</div>
                    </div>
                </div>

                <div class="sf-item sf-type-color">
                    <div class="sf-title">
                        <span>Color picker</span>
                    </div>
                    <div class="sf-preview">
                        <div class="sf-color"></div>
                    </div>
                </div>

                <div class="sf-item sf-type-slider">
                    <div class="sf-title">
                        <span>Range slider</span>
                    </div>
                    <div class="sf-preview">
                        <div class="sf-slider"></div>
                    </div>
                </div>

                <div class="sf-item sf-type-currency">
                    <div class="sf-title">
                        <span>Currency field</span>
                    </div>
                    <div class="sf-preview">
                        <input type="text" value="$12,345.95" />
                    </div>
                </div>

                <div class="sf-item sf-type-file">
                    <div class="sf-title">
                        <span>File upload</span>
                    </div>
                    <div class="sf-preview">
                        <div class="sf-file">Upload files...</div>
                    </div>
                </div>

                <div class="sf-item sf-type-date">
                    <div class="sf-title">
                        <span>Date picker</span>
                    </div>
                    <div class="sf-preview">
                        <div class="sf-date">01-01-2018</div>
                    </div>
                </div>

                <div class="sf-item sf-type-time">
                    <div class="sf-title">
                        <span>Time picker</span>
                    </div>
                    <div class="sf-preview">
                        <div class="sf-time">16:45</div>
                    </div>
                </div>

                <div class="sf-item sf-type-rating">
                    <div class="sf-title">
                        <span>Rating</span>
                    </div>
                    <div class="sf-preview">
                        <div class="sf-rating">
                            <i></i>
                            <i></i>
                            <i></i>
                            <i></i>
                            <i></i>
                        </div>
                    </div>
                </div>


               
            </div>
        </div>

    </div>

</div>