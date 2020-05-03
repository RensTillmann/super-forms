<div class="super-addons">
    <h1>Super Forms Add-ons:</h1>
    <?php
    $addons = array(
        'pdf' => array(
            'title' => 'PDF Generator',
            'price' => '$5 p/m',
            'desc' => '<ul>
    <li>Convert forms automatically to PDF</li>
    <li>Attach converted PDF to E-mails</li>
    <li>Attach converted PDF to Contact Entries</li>
    <li>Let user download the PDF directly</li> 
    <li>Define a PDF header</li>
    <li>Define a PDF Footer</li>
    <li>Exclude specific elements from PDF</li>
    <li>Include specific elements in PDF</li>
    <li>Compatible with all elements</li>
</ul>',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'mailchimp' => array(
            'title' => 'MailChimp',
            'price' => '$20',
            'link' => 'super-forms-mailchimp-addon/14126404',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'frontend_register_login' => array(
            'title' => 'Front-end Register & Login',
            'price' => '$20',
            'link' => 'frontend-register-login/14403267',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'email_templates' => array(
            'title' => 'Email Templates',
            'price' => '$20',
            'link' => 'super-forms-email-templates/14468280',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'signature' => array(
            'title' => 'Signature',
            'price' => '$20',
            'link' => 'super-forms-signature/14879944',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'calculator' => array(
            'title' => 'Calculator',
            'price' => '$20',
            'link' => 'super-forms-calculator/16045945',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'frontend_posting' => array(
            'title' => 'Front-end Posting',
            'price' => '$20',
            'link' => 'super-forms-frontend-posting/17092502',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'wc_checkout' => array(
            'title' => 'WooCommerce Checkout',
            'price' => '$20',
            'link' => 'super-forms-woocommerce-checkout/18013799',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'popups' => array(
            'title' => 'Popups',
            'price' => '$20',
            'link' => 'super-forms-popups/19207307',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'csv_attachment' => array(
            'title' => 'CSV Attachment',
            'price' => '$20',
            'link' => 'super-forms-csv-attachment/19437918',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'password_protect_user_lockout_hide' => array(
            'title' => 'Password Protect & User Lockout & Hide',
            'price' => '$20',
            'link' => 'super-forms-password-protect-user-lockout-hide-addon/19604086',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'mailster' => array(
            'title' => 'Mailster',
            'price' => '$20',
            'link' => 'super-forms-mailster-addon/19735910',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'paypal' => array(
            'title' => 'PayPal',
            'price' => '$20',
            'link' => 'super-forms-paypal-addon/21048964',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        ),
        'email_appointment_reminders' => array(
            'title' => 'E-mail & Appointment Reminders',
            'price' => '$20',
            'link' => 'super-forms-email-reminders-appointment-reminders-addon/23861554',
            'desc' => '',
            'docs' => 'https://renstillmann.github.io/super-forms'
        )
    );
    foreach($addons as $k => $v){
        echo '<div class="super-addon">';
            echo '<div class="super-addon-title">' . $v['title'] . '</div>';
            echo '<div class="super-addon-price">' . $v['price'] . '</div>';
            echo '<div class="super-addon-desc">' . $v['desc'] . '</div>';
            echo '<a class="super-addon-docs" href="'. $v['docs'] .'">Documentation</a>';
            if( !empty($v['link']) ) {
                echo '<a class="super-purchase-addon" href="' . $v['link'] . '">Purchase</a>';
            }else{
                echo '<div class="super-activate-addon" data-slug="' . $k . '">Start 15 days Free Trial</div>';
            }
        echo '</div>';
    }
    // <div class="super-addon">
    //     Form to PDF
    //     <div class="super-activate-addon" data-slug="pdf">Activate</div>
    // </div>
    // <div class="super-addon">
    //     Calculator
    // </div>
    // <div class="super-addon">
    //     WooCommerce Checkout
    // </div>
    // <div class="super-addon">
    //     PayPal
    // </div>
    ?>
</div>