<?php
$faqs = array(
	array(
		'q' => 'Why is Super Forms not sending any E-mails?',
		'a' => "
Check if your wordpress is sending E-mails when you use the <a href=\" " . wp_lostpassword_url() . "\" title=\"Lost password\">Lost password</a> form by WordPress itself.<br />
You can test this on the <a href=\" " . wp_login_url() . "\" title=\"Login\">Login page</a> of the dashboard.
<br /><br />
If you do not receive any E-mails (also check <strong>Spam folder</strong>), it could be that your hosting either has <strong>PHP mail()</strong> disabled, or something else isn't configured correctly on your server. In that case contact your hosting company.
<br /><br />
If you do receive an E-mail with the new password reset link, then it is most likely that you the \"From:\" header isn't set correctly.<br />
Make sure that on the form you have build, the setting is set to have your domain name as From: header. e.g: no-reply@<strong>mydomain.com</strong><br />
Some mail servers do not allow to use a From header different from the domain name it's being send from.
<br /><br />
If you are still unable to receive E-mails after above steps, check if any other plugin is being used that overrides WordPress <strong>wp_mail()</strong> functionality.<br />
If you are using <strong>SMTP plugin</strong> or settings, recheck if they are setup correctly, if you think everything is correctly setup, you can contact support.
		"
	)
);
foreach($faqs as $k => $v){
	echo '<h3>4.' . ($k+1) . ' - ' . $v['q'] . '</h3>';
	echo '<p>' . $v['a'] . '</p>';
}