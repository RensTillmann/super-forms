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
	),
	array(
		'q' => 'Why do I get an error message when uploading a file?',
		'a' => "
If you are unable to upload files via your form the first thing you should try is to check if the server returns a <strong>403 error (Forbidden)</strong> on the following URL:<br />
http://<strong>yourdomain.com</strong>/wp-content/plugins/super-forms/uploads/php/
<br /><br />
If it returns a <strong>403 error</strong>, please contact your hosting company to let them fix this issue.<br />
It should return a blank page in order for the file upload field to work correctly.
<br /><br />
If the URL returns a blank page, you can check if uploading a small file works. If this doesn’t work, it’s mostly due to incorrect file permissions on the plugin folders, contact your hosting company to let them look at this.
<br /><br />
If you are able to upload smaller files, it is most likely due to PHP settings regarding file uploads. In this case you can adjust the <strong>post_max_size</strong> and, <strong>memory_limit</strong> and <strong>upload_max_filesize</strong> values in your php.ini file or ask your hosting company to increase these values to suite your needs. Remember the following rules when changing these values:<br />
1. To upload large files, post_max_size value must be larger than <strong>upload_max_filesize</strong>.<br />
2. <strong>memory_limit</strong> should be larger than <strong>post_max_size</strong>
		"
	),
	array(
		'q' => 'Where can I change the form font styles?',
		'a' => "You can change the font styles when editing a form under: <em>Form Settings > Font Styles</em>"
	),
	array(
		'q' => 'How to make the file upload mandatory?',
		'a' => "When editing the file upload element you can set a Max and Min under \"Advanced\". If you set the Minimum to 1 or higher the field will become required."
	),
	array(
		'q' => 'Is the plugin Multi-site compatible?',
		'a' => "Yes, but the contact entries, forms and form settings will be saved individually per site and not on global level."
	),	
	array(
		'q' => 'How can I make all fields to be required?',
		'a' => "This can be done per field individually because each field can have different type of validations. To do this you can edit the field/element you wish and select the required validation under <em>General > Special Validation</em>, there you have several option to choose from. Inside the <em>Error Message</em> setting you can enter a custom error to be displayed whenever the field was not entered correctly by the user."
	),
	array(
		'q' => 'Can I do price calculations based on user selection?',
		'a' => "Yes, you can do this with the <a href=\"https://codecanyon.net/item/super-forms-calculator/16045945\" target=\"_blank\">Calculator add-on</a> for Super Forms."
	),
	array(
		'q' => 'Where can I add a tooltip when the user hovers over the field?',
		'a' => "You can add a tooltip when editing the element under <em>General &gt; Tooltip text</em>"
	),
	array(
		'q' => 'I changed the settings but it doesn\'t seem to affect the form?',
		'a' => "Please note that you must change settings on form level when editing a form. Existing forms will not be overridden by the default/global settings. Global settings will only be used upon saving a form for the first time. Please edit the form and go to <em>Form Settings</em> TAB and make changes there. If still no success please contact support."
	),
	array(
		'q' => 'Why does the page refresh and doesn\'t do anything if I click on the submit button?',
		'a' => "This is normally due to a theme or plugin having an action on the submit button element. You can try and find out what plugin is causing it to disable them and re-enabling them one by one. You can also do the same with with your theme to see if the issue is inside your theme. You can then contact the author of the plugin or theme to ask if they could look at this issue. If you think something else is wrong then you are free to contact support."
	),
	array(
		'q' => 'I want to see who the author of the post or page is where the form was sent from, how can I accomplish this?',
		'a' => "You can add a Text field and put it inside a column and make the column invisible. Make sure the default value of the Text field is set to {post_author}"
	),
	array(
		'q' => 'Why are my conditional variable values on my hidden field not working?',
		'a' => "You can check the output of the any hidden field value by adding a HTML element to your form with HTML set like this: My hidden field: {replace_with_field_name}<br />If it is empty then you must recheck your conditions, in most cases the conditional logic was set incorrectly. If you still do not succeed you can contact support."
	),
	array(
		'q' => 'How do I redirect a user after submission?',
		'a' => "When editing the form you can enable redirect under: <em>Form Settings &gt; Form Settings &gt; Form redirect option</em>"
	),
	array(
		'q' => 'Is it compatible with Visual Composer?',
		'a' => "<strong>Yes! Super Forms has it's own Visual Composer (JS Composer) element.</strong><br />
				With this element you can simply drag &amp; drop any form at the location in your page you wish.<br />
				After you dropped the element you can choose which form it should load simply with the use of a dropdown that will list all the forms you have created.
				<br /><br />
				The Super Forms shortcode can also be inserted into a Visual Composer <strong>HTML element</strong>. This makes it easy to insert it into any text area within your Visual Composer pages."
	),
	array(
		'q' => 'Is it compatible with MailChimp?',
		'a' => "The <a href=\"https://codecanyon.net/item/super-forms-mailchimp-addon/14126404\" target=\"_blank\">MailChimp add-on</a> for Super Forms makes it possible to integrate your form(s) with MailChimp service."
	),
	array(
		'q' => 'Is it compatible with MyMail (Mailster) plugin?',
		'a' => "The <a href=\"https://codecanyon.net/item/super-forms-mailster-addon/19735910\" target=\"_blank\">MyMail (Mailster) add-on</a> for Super Forms makes it possible to integrate your form(s) with MyMail (Mailster) plugin."
	),
	array(
		'q' => 'Can i customize the layout to include collapse groups?',
		'a' => "Yes, this can be accomplished by adding fields into a column and append conditional logic on that column."
	),
	array(
		'q' => 'Can i import options for a dropdown from a CSV file?',
		'a' => "Yes, dropdowns, radio and checkboxes can retrieve options based on a selected CSV file that you uploaded in the media library of your wordpress site. It can contain the option Label in the first column and the option value in the second column."
	),
	array(
		'q' => 'Is it translatable?',
		'a' => "Yes, Super Forms is fully translation ready."
	),
);
foreach($faqs as $k => $v){
	echo '<h3>4.' . ($k+1) . ' - ' . $v['q'] . '</h3>';
	echo '<p>' . $v['a'] . '</p>';
}