/* globals jQuery, super_demos_i18n, ajaxurl */
"use strict";
(function() { // Hide scope, no $ conflict

    jQuery(document).ready(function ($) {
    
        var $doc = $(document);

        $doc.on('click', '.super-demos .install-now', function(){
            var $this = $(this);
            if(!$this.hasClass('button-disabled')){
                $this.html('Installing...').addClass('button-disabled');
                var $parent = $this.parents('.plugin-card:eq(0)');
                var $title = $parent.find('input[name="title"]').val();
                var $elements = $parent.find('textarea[name="_super_elements"]').val();
                var $settings = $parent.find('textarea[name="_super_form_settings"]').val();
                var $import = $parent.find('textarea[name="_super_import"]').val();
                $.ajax({
                    type: 'post',
                    url: ajaxurl,
                    data: {
                        action: 'super_demos_install_item',
                        title: $title,
                        elements: $elements,
                        settings: $settings,
                        import: $import
                    },
                    success: function (result) {
                        window.location.href = "admin.php?page=super_create_form&id="+result;
                    },
                    error: function () {
                        alert(super_demos_i18n.connection_lost);
                    }
                });
            }
        });
    });

})(jQuery);