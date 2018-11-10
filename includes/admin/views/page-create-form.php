<div class="sf-builder">

    <div class="sf-actions">
        <div class="sf-save"></div>
        <div class="sf-settings"></div>
        <div class="sf-theme"></div>
        <div class="sf-preview"></div>
        <div class="sf-add"></div>
        <div class="sf-version">v<?php echo SUPER_VERSION; ?></div>
    </div>

    <div class="sf-canvas">

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
                $i = 0;
                while($i<10) {
                    ?>
                    <div class="sf-item sf-type-text">
                        <div class="sf-title">
                            <span>Text field</span>
                        </div>
                        <div class="sf-preview">
                            <span class="sf-label">Dummy label</span>
                            <input type="text" placeholder="Dummy placeholder..." />
                        </div>
                    </div>
                    <?php
                    $i++;
                }
                ?>
            </div>
        </div>

    </div>

</div>