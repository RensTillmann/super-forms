<div class="super-documentation">
    <div class="super-index">
        <div class="super-search">
            <input type="text" name="search" placeholder="Search the library..." />
            <span class="total" data-total="0">1 of 12</span>
            <span class="prev"></span>
            <span class="next"></span>
        </div>
        <h3>Index:</h3>
        <?php
        $index = array(
            array(
                'title' => __( 'Introduction', 'super-forms' ),
            ),
            array(
                'title' => __( 'Getting Started', 'super-forms' ),
                array(
                    'title' => __( 'Creating a Form', 'super-forms' ),
                ),
                array(
                    'title' => __( 'Adding form elements', 'super-forms' ),
                ),
                array(
                    'title' => __( 'Publishing a Form', 'super-forms' ),
                ),
                array(
                    'title' => __( 'Editing a Form', 'super-forms' ),
                ),
                array(
                    'title' => __( 'Cloning a Form', 'super-forms' ),
                ),
            ),
            array(
                'title' => __( 'Settings', 'super-forms' ),
                array(
                    'title' => __( 'Default settings', 'super-forms' ),
                ),
                array(
                    'title' => __( 'Form settings', 'super-forms' ),
                ),
                array(
                    'title' => __( 'Element settings', 'super-forms' ),
                ),
            ),
            array(
                'title' => __( 'FAQ', 'super-forms' ),
            ),
        );

        echo '<ul>';
        foreach( $index as $k => $v ) {
            echo '<li><a href="#doc-' . ($k+1) . '">' . ($k+1) . ' - ' . $v['title'] . '</a>';
            if( isset($v[0]) ) {
                echo '<ul>';
                foreach( $v as $vk => $vv ) {
                    if( !is_array($vv) ) continue;
                    echo '<li><a href="#doc-' . ($k+1) . '-' . ($vk+1) . '">' . ($k+1) . '.' . ($vk+1) . ' - ' . $vv['title'] . '</a></li>';
                }
                echo '</ul>';
            }
            echo '</li>';
        }
        echo '</ul>';
        ?>
    </div>
    <div class="super-content">
        <?php
        $folder = SUPER_PLUGIN_FILE .'/includes/admin/views/documentation/images/';
        $dir = SUPER_PLUGIN_DIR . '/includes/admin/views/documentation/';
        foreach( $index as $k => $v ) {
            $n = ($k+1);
            echo '<div id="doc-' . $n . '" class="doc-' . $n . '">';
                echo '<div class="doc-content">';
                    echo '<h2>' . $n . ' - ' . $v['title'] . '</h2>';
                    $file = $dir . $n . '.php';
                    if( file_exists ( $file ) ) require_once ( $file );
                echo '</div>';
            echo '</div>';
            if( isset($v[0]) ) {
                foreach( $v as $vk => $vv ) {
                    if( !is_array($vv) ) continue;
                    $tn = ($k+1) . '.' . ($vk+1);
                    $n = ($k+1) . '-' . ($vk+1);
                    echo '<div id="doc-' . $n . '" class="doc-' . $n . '">';
                        echo '<div class="doc-content">';
                            echo '<h3>' . $tn . ' - ' . $vv['title'] . '</h3>';
                            $file = $dir . $n . '.php';
                            if( file_exists ( $file ) ) require_once ( $file );
                        echo '</div>';
                    echo '</div>';
                }
                echo '</ul>';
            }
        }
        ?>
    </div>
</div>

<script>
jQuery(document).ready(function($){
    var $input = $('input[name="search"]'),
        $clearBtn = $('button[data-search="clear"]'),
        $prevBtn = $('.super-search .prev'),
        $nextBtn = $('.super-search .next'),
        $content = $('.super-content'),
        $results,
        currentClass = "active",
        offsetTop = 50,
        currentIndex = 0;
    function jumpTo() {
        if ($results.length) {
            var position;
            $current = $results.eq(currentIndex);
            $results.removeClass(currentClass);
            if ($current.length) {
                $current.addClass(currentClass);
                position = $current.position().top + $content.scrollTop() - offsetTop;
                $content.animate({scrollTop: position}, 0);
            }
        }
    }
    $input.on("input", function() {
        var searchVal = this.value;
        $content.unmark({
            done: function() {
                $content.mark(searchVal, {
                    separateWordSearch: false,
                    diacritics: true,
                    done: function() {
                        $results = $content.find("mark");
                        var found = $results.length;
                        if(found>1){
                            $('.super-search .total').attr('data-total', found).html('1 of '+found);
                            $input.parent().addClass('active');
                        }else{
                            $input.parent().removeClass('active');
                        }
                        currentIndex = 0;
                        jumpTo();
                    }
                });
            }
        });
    });
    $input.on('keypress', function(e){
        if (e.which == 13) {
            $nextBtn.trigger('click');
        }
    });
    $nextBtn.add($prevBtn).on("click", function() {
        var found = $results.length;
        if (found) {
            currentIndex += $(this).is($prevBtn) ? -1 : 1;
            if (currentIndex < 0) currentIndex = found - 1;
            if (currentIndex > found - 1) currentIndex = 0;
            $('.super-search .total').attr('data-total', found).html(+(currentIndex+1)+' of '+found);
            jumpTo();
        }
    });
});
</script>