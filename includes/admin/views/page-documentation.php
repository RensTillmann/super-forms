<div class="super-documentation">
    <h1>Browse our documentation, tips and tutorials</h1>
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
                    echo '<li><a href="#doc-' . ($k+1) . '.' . ($vk+1) . '">' . ($k+1) . '.' . ($vk+1) . ' - ' . $vv['title'] . '</a></li>';
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
        foreach( $index as $k => $v ) {
            $n = ($k+1);
            echo '<div id="doc-' . $n . '" class="doc-' . $n . '">';
                echo '<div class="doc-content">';
                    echo '<h2>' . $n . ' - ' . $v['title'] . '</h2>';
                    $file = SUPER_PLUGIN_DIR . '/includes/admin/views/documentation/' . $n . '.html';
                    if( file_exists ( $file ) ) require_once ( $file );
                echo '</div>';
            echo '</div>';
            if( isset($v[0]) ) {
                foreach( $v as $vk => $vv ) {
                    if( !is_array($vv) ) continue;
                    $n = ($k+1) . '.' . ($vk+1);
                    echo '<div id="doc-' . $n . '" class="doc-' . $n . '">';
                        echo '<div class="doc-content">';
                            echo '<h3>' . $n . ' - ' . $vv['title'] . '</h3>';
                            $file = SUPER_PLUGIN_DIR . '/includes/admin/views/documentation/' . $n . '.html';
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
    var doc = $(document);
    var content_clone = $('.super-content').clone();
    content_clone.css('display','none').addClass('clone');
    content_clone.appendTo('.super-documentation');
    doc.on('keyup', 'input[name="search"]', function(){
        var $this = $(this);
        var value = $this.val();
        var html = $('.super-content.clone').html();
        if(value.length>1){
            $this.parent().addClass('active');
            var term = value;
            term = term.replace(/(\s+)/,"(<[^>]+>)*$1(<[^>]+>)*");
            var pattern = new RegExp("("+term+")", "gi");
            html = html.replace(pattern, "<mark class=\"super-mark\">$1</mark>");
            html = html.replace(/(<mark class="super-mark">[^<>]*)((<[^>]+>)+)([^<>]*<\/mark>)/, "$1</mark>$2<mark class=\"super-mark\">$4");
        }else{
            $this.parent().removeClass('active');
        }
        var content = $('.super-content:not(.clone)');
        content.html(html);
        var target = content.find('.super-mark:eq(0)');
        target.addClass('active');
        var found = content.find('.super-mark').length;
        $('.super-search .total').attr('data-total', found).html('1 of '+found);
        scrollIntoViewIfNeeded(target);
    });
    doc.on('click', '.super-search .next, .super-search .prev', function(){
        var content = $('.super-content:not(.clone)');
        var current_mark = content.find('.super-mark.active');
        var index = $('.super-mark').index(content.find('.super-mark.active'));
        var max = $('.super-search .total').attr('data-total');
        if($(this).hasClass('next')){
            var next = index+1;
            if(next==max) next = 0;
        }else{
            var next = index-1;
            if(next<0) next = (max-1);
        }
        current_mark.removeClass('active');
        var target = content.find('.super-mark:eq('+next+')');
        target.addClass('active');
        scrollIntoViewIfNeeded(target);
    });
    function scrollIntoViewIfNeeded($target) {
        if(!$target.position()) return true;
        if ($target.position().top < jQuery(window).scrollTop()){
            //scroll up
            $('html,body').animate({scrollTop: $target.position().top - 50});
        }else{
            if ($target.position().top + $target.height() + 150 > $(window).scrollTop() + ( window.innerHeight || document.documentElement.clientHeight)) {
                //scroll down
                $('html,body').animate({scrollTop: $target.position().top - (window.innerHeight || document.documentElement.clientHeight) + $target.height() + 75}, 200);
            }
        }
    }
});
</script>