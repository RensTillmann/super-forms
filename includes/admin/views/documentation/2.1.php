<p>
	There are two types of methods to start creating a new form.
</p>
<table>
	<tr>
		<th>1. Using the menu: <a target="_blank" href="<?php echo admin_url( 'admin.php?page=super_create_form' ); ?>">Super Forms > Create Form</a></th>
		<th>2. Using the Marketplace: <a target="_blank" href="<?php echo admin_url( 'admin.php?page=super_marketplace' ); ?>">Super Forms > Marketplace</a></th>
	</tr>
	<tr>
		<td><img src="<?php echo $folder; ?>2.1.1.PNG" /></td>
		<td><img src="<?php echo $folder; ?>2.1.2.PNG" /></td>
	</tr>
	<tr>
		<td>Allows you to start creating a new <strong>form from scratch</strong></td>
		<td>Allows you to <strong>install an example form</strong> of your choosing</td>
	</tr>
</table>

<p>
	If you choose method 1 (<a target="_blank" href="<?php echo admin_url( 'admin.php?page=super_create_form' ); ?>">Super Forms > Create Form</a>) you will see the <strong>Form setup wizard</strong>.<br />
	This wizard allows you to set some basic but yet important things for any form you create.<br />
	It comes with the following 4 TAB's:
</p>
<table>
	<tr>
		<th>1. Theme & styles</th>
	</tr>
	<tr>
		<td><img src="<?php echo $folder; ?>2.1.1.1.PNG" /></td>
	</tr>
	<tr>
		<td>
			This is where you can change how your fields will look like.<br />
			The most important theme related settings can be previewed instantly upon changing on of the options.<br />
			You can change the <strong>style</strong>, <strong>field size</strong> and choose to enable <strong>field icons</strong>.<br /><br />

			<strong>Please note:</strong><br />
			Other options such as <a href="#">RTL</a> (Right To Left Text) and colors can be changed under <strong>Form Settings > Theme & Colors</strong> on the <strong><a href="<?php echo admin_url( 'admin.php?page=super_create_form' ); ?>">form builder page</a></strong> after you have saved or skipped the wizard.<br />
			<img src="<?php echo $folder; ?>2.1.1.1.1.PNG" /><br />		
			A good example of one setting that isn't listed under this TAB is the custom <a href="#">Reply-To:</a> header option.

		</td>
	</tr>
</table>

<table>
	<tr>
		<th>2. Admin email</th>
	</tr>
	<tr>
		<td><img src="<?php echo $folder; ?>2.1.1.2.PNG" /></td>
	</tr>
	<tr>
		<td>
			By default Super Forms will send emails to the Blog admin email which is currently set to: <?php echo get_option('admin_email'); ?><br />
			When you do not use the wizard it will automatically use that email address to send admin emails to.<br />
			However with the setup wizard, you will be able to change this on the fly to whatever email address you wish.<br />
			You are allowed to use <strong>{tags}</strong> (<a href="#">click here for more info regarding tags</a>) inside this option.<br /><br />
			
			<strong>Please note:</strong><br />
			The options listed under this TAB are only the most commonly used ones, if your setting isn't listed here, you can find all<br />
			the other options under <strong>Form Settings > Email settings (admin emails)</strong> on the <strong><a href="<?php echo admin_url( 'admin.php?page=super_create_form' ); ?>">form builder page</a></strong> after you have saved or skipped the wizard.<br />
			<img src="<?php echo $folder; ?>2.1.1.2.1.PNG" /><br />		
			A good example of one setting that isn't listed under this TAB is the custom <a href="#">Reply-To:</a> header option.
		</td>
	</tr>
</table>

<table>
	<tr>
		<th>3. Confirmation email</th>
	</tr>
	<tr>
		<td><img src="<?php echo $folder; ?>2.1.1.3.PNG" /></td>
	</tr>
	<tr>
		<td>
			By default (just like the admin email) Super Forms will also send a confirmation email to te submitter of the form.<br />
			As you can see the tag <strong>{email}</strong> is being used to retrieve the submitters email address.<br />
			Of course your form will need an email field named <strong>email</strong>. If it does and the user entered a correct email address he/she will receive the confirmation email.<br /><br />

			<strong>Please note:</strong><br />
			The options listed under this TAB are only the most commonly used ones, if your setting isn't listed here, you can find all<br />
			the other options under <strong>Form Settings > Email settings (admin emails)</strong> on the <strong><a href="<?php echo admin_url( 'admin.php?page=super_create_form' ); ?>">form builder page</a></strong> after you have saved or skipped the wizard.<br />
			<img src="<?php echo $folder; ?>2.1.1.3.1.PNG" /><br />			
			An example of settings that aren't listed under this TAB are the <a href="#">CC:</a> and <a href="#">BCC:</a> options (also available for admin emails).
	</tr>
</table>

<table>
	<tr>
		<th>4. Thank you message</th>
	</tr>
	<tr>
		<td><img src="<?php echo $folder; ?>2.1.1.4.PNG" /></td>
	</tr>
	<tr>
		<td>
			By default a thank you message will be displayed to the user when he/she successfully submitted the form.<br />
			Under this TAB you can change the title and message itself.<br />
			If you do not wish to display a message you can either leave both options empty or disable it under <strong>Form Settings > Form Settings</strong> on the <strong><a href="<?php echo admin_url( 'admin.php?page=super_create_form' ); ?>">form builder page</a></strong>.
			<img src="<?php echo $folder; ?>2.1.1.4.1.PNG" />		
		</td>
	</tr>
</table>