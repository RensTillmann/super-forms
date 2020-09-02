/* globals jQuery, super_register_common_i18n */
(function() { // Hide scope, no $ conflict
    "use strict";
    jQuery(document).ready(function ($) {
    
        var $doc = $(document);
        
        $doc.on('click', '.super-msg .resend-code', function () {     
            var $this = $(this);
            var $msg_container = $this.parents('.super-msg:eq(0)');
            var $form_id = $this.data('form');
            var $form = $this.parents('.super-form-' + $form_id + ':eq(0)');
            var $fields = $form.find('input');
            var $data = {};
            $fields.each(function(){
                $data[$(this).attr('name')] = $(this).val();
            });
            $data.username = $this.data('user');
            $data.form = $form_id;
            $this.addClass('super-loading');
            $.ajax({
                url: super_register_common_i18n.ajaxurl,
                type: 'post',
                data: {
                    action: 'super_resend_activation',
                    data: $data
                },
                success: function (result) {
                    var $result = JSON.parse(result);
                    var $html;
                    if($result.error==true){
                        $html = '<div class="super-msg error">';
                        if(typeof $result.fields !== 'undefined'){
                            $.each($result.fields, function( index, value ) {
                                $(value+'[name="'+index+'"]').parent().addClass('error');
                            });
                        }                               
                    }else{
                        $html = '<div class="super-msg super-success">';
                    }
                    if($result.redirect){
                        window.location.replace($result.redirect);
                    }else{
                        $html += $result.msg;
                        $html += '<span class="super-close"></span>';
                        $html += '</div>';
                        var $new_message = $($html).insertBefore($msg_container);
                        $msg_container.remove();
                        $('html, body').animate({
                            scrollTop: $new_message.offset().top-200
                        }, 1000);
                        if($result.error==false){
                            var $duration = super_register_common_i18n.duration;
                            $form.find('.super-field, .super-multipart-steps').fadeOut($duration);
                            setTimeout(function () {
                                $form.find('.super-field').remove();
                            }, $duration);
                        }
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    console.log(xhr, ajaxOptions, thrownError);
                    alert('Failed to resend activation code, please try again');
                },
                complete: function() {
                    $this.removeClass('super-loading');
                }
            });                    
            return false;
        });

    });

})(jQuery);
