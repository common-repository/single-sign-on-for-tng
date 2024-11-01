<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="wrap">
	<!-- <div id="icon-plugins" class="icon32"></div> -->
	<div id="ssofortngsettings">
	<h2>Single Sign On For TNG</h2>
	<?php echo "<h3>Version: ". esc_html($this->plugin_info['Version']) . "</h3>"; ?>

	<form method="post" id="settings" action="options.php">

	<?php
	 @settings_fields('ssofortng-data');
	 @do_settings_fields('ssofortng-data','');
	 $options = get_option('ssofortng_options');
//	 error_log(print_r($options,true));

	function getoption($var) {
		printf(
	      /* translators: %s: previously stored url, path or untranslatable encrypted string */
			'%s',
			esc_html($var)
		);
	}
?>


	All fields marked with * are required.<br>
	<table class="form-table">
		<tr valign="top">
		<th scope="row"><label for="ssofortng_options[tngpath]"><?php esc_html_e("TNG Installation Location*",'single-sign-on-for-tng');?></label></th>
		<td><input type="text" name="ssofortng_options[tngpath]" id="tngpath" class="settings-input" value="<?php getoption($options['tngpath']); ?>" /></td>
		<td class="setuphelp"><?php esc_html_e("(Enter the full file path of the TNG installation, or its relative path to the wp-admin folder)",'single-sign-on-for-tng')?></td>
        </tr>

		<tr valign="top">
		<th scope="row"><label for="ssofortng_options[tngurl]"><?php esc_html_e("TNG Installation URL*",'single-sign-on-for-tng')?></label></th>
		<td><input type="text" name="ssofortng_options[tngurl]" id="tngurl" class="settings-input" value="<?php getoption($options['tngurl']); ?>"/></td>
		<td class="setuphelp"><?php esc_html_e("(Enter the TNG URL eg, https://www.mydomain.com/tng)",'single-sign-on-for-tng')?></td>
        </tr>

		<tr valign="top">
		<th scope="row"><label for="ssofortng_options[secret]"><?php esc_html_e("Password Ecryption Key*",'single-sign-on-for-tng')?></label></th>
		<td><input type="password" readonly name="ssofortng_options[secret]" id="secret" class="settings-input" value="<?php getoption($options['secret']); ?>"/></td>
		<td class="setuphelp">
		<?php
		if (!isset($options['secret'])) { // create the secret key one time
			?>
				<input type='button' id='secretgeneratorbutton' value='<?php esc_attr_e("Generate Key","single-sign-on-for-tng");?>' onClick='generate_key();'>
			<?php
		}
		else {
				esc_html_e("The key has already been generated and cannot be changed",'single-sign-on-for-tng');
			}
		?></td>
        </tr>

		<tr valign="top">
		<th scope="row"><label for="ssofortng_options[autoadd]"><?php esc_html_e("Add Existing Users",'single-sign-on-for-tng')?></label></th>
		<td><input type="checkbox" name="ssofortng_options[autoadd]" id="autoadd"
		<?php
			$disabled = true;
			if (isset($options['autoadd'])) {
				$disabled = false;
				echo " checked";
			}
		?>
		>
		<?php esc_html_e("Add Existing WordPress Users On Log In",'single-sign-on-for-tng')?></td>
		<td class="setuphelp"><?php esc_html_e("(Automatically add existing WordPress users to TNG if missing and they have the role below)",'single-sign-on-for-tng')?></td>
		</tr>

		<tr valign="top">
		<th scope="row"><label for="ssofortng_options[autoadd]"><?php esc_html_e("Select Matching Roles",'single-sign-on-for-tng')?></label></th>
		<td>
		<select name="ssofortng_options[role][]" id="role" multiple <?php echo $disabled ? 'disabled' : ''; ?> >
		<?php
			$roles = wp_roles()->get_names();
			foreach ($roles as $key=>$name) {
				$selected = '';
			if (isset($options['role']))
				$selected = in_array($key,$options['role']) ? 'selected' : '';
			echo "<option value=\"$key\" $selected>$name</option>\n";
			}
		?>
		</select>
		<input type="button" value="<?php esc_html_e(" Clear All ",'single-sign-on-for-tng')?>" id="clearall" <?php echo $disabled ? 'disabled' : ''; ?>>
		<input type="button" value="<?php esc_html_e(" Select All ",'single-sign-on-for-tng')?>" id="setall"<?php echo $disabled ? 'disabled' : ''; ?>>
		</td>
		<td class="setuphelp"><?php esc_html_e("(Select as many roles as you wish to have added on user log in)",'single-sign-on-for-tng')?></td>
		</tr>

		<tr valign="top">
		<th scope="row">
		<label for="ssofortng_options[disclaimer]">
		<?php esc_html_e("Software Disclaimer*",'single-sign-on-for-tng')?>
		</label></th>
		<td>
		<?php
		$mode = '';
		if (isset($options['disclaimer'])) {
			$mode = 'checked disabled';
		}
		?>
		<input type="checkbox" <?php _e($mode);?> name="ssofortng_options[disclaimer]" id="disclaimer" class="settings-input">
		<?php
		esc_html_e("I have read and accept the Software Disclaimer",'single-sign-on-for-tng')
		?>
		</td>
		<td class="setuphelp">
		<?php
		if (isset($options['disclaimer']))
			_e("(You have previously indicated you have read and accepted the <a href='#softwaredisclaimer'>Software Disclaimer</a>)",'single-sign-on-for-tng');
		else
			_e("(You must read the <a href='#softwaredisclaimer'>Software Disclaimer</a> and check this box to use the plugin)",'single-sign-on-for-tng');
		?>
		</td>
		</tr>

	</table>

	<?php
		@submit_button();
		?>
	</form>
	<?php
	$help =	 "<h1>" . __("Help" ,'single-sign-on-for-tng') . "</h1>";
	$help .= "<p>" . __("To activate the Single Sign On for TNG plugin the above first 3 fields must be completed and the Software Disclaimer read and the confirmation box checked." ,'single-sign-on-for-tng') . "</p>";
	$help .= "<h3>" . __("TNG Installation Location" ,'single-sign-on-for-tng'). "</h3>";
	$help .= "<p>" . __("In this box enter the full path to the location where you installed TNG, for example: /www/html/tng and this path will be checked to ensure it contains a valid installation of TNG." ,'single-sign-on-for-tng') . "</p>";
	$help .= "<h3>" . __("TNG Installation URL" ,'single-sign-on-for-tng'). "</h3>";
	$help .= "<p>" . __("Enter in this box the URL of your TNG installation: for example: https://www.mydomain.com/tng and this will NOT be checked, so make sure you have it right." ,'single-sign-on-for-tng');

	$help .= "<h3>" . __("Password Encryption Key" ,'single-sign-on-for-tng'). "</h3>";
	$help .= "<p>" . __("To implement the single sign on, the password to a site user's Wordpress account is stored in a cookie in the user's browser.  To ensure its security it is encrypted using OpenSSL.  This makes it unreadable without the associated key.  This unique key is generated by clicking the associated button.  The key will be displayed as asterisks.  Once created and saved, the button will no longer been displayed and the key may not be changed." ,'single-sign-on-for-tng') . "</p>";

	$help .= "<h3>" . __("Add Existing WordPress Users" ,'single-sign-on-for-tng'). "</h3>";
	$help .= "<p>" . __("If this box is checked then when a user logs in to the WordPress site with the indicated role below the checkbox, and no matching account existings in TNG, he/she will automatically have his/her username and password added to the TNG users, with <em><b>Guest</b></em> privileges.  No Administrator authorization in TNG is required.  You will be notified by email when this happens so that you can adjust their TNG privileges if you wish.  They will be automatically logged in to TNG on this and subsequent log ins.  More than one role can be selected using the CTRL or SHIFT keys when clicking." ,'single-sign-on-for-tng') . "</p>";


	$help .= "<h3>" . __("Additional Required Setup" ,'single-sign-on-for-tng') . "</h3>";
	$help .= "<p>" . __("Two shortcodes must be installed to complete the single sign-on process." ,'single-sign-on-for-tng') . "</p>";
	$help .= "<h4>" . __("Login Shortcode" ,'single-sign-on-for-tng') . "</h4>";
	$help .= "<p>" . __("Create a new blank WordPress page called <em>TNG Login</em> with a slug of <em>tng-login</em>.  The end result should be that it is publicly accessible at https://&lt;your domain&gt;/tng-login.  If your theme has a template for a blank page then use that, but if not you can just put some words on it indicated that the user is being redirected.  Then anywhere on the page put the shortcode <b>&#91;sso_for_tng&#93;</b> (including the square brackets)." ,'single-sign-on-for-tng') . "</p>";
	$help .= "<h4>" . __("Logout Shortcode" ,'single-sign-on-for-tng') . "</h4>";
	$help .= "<p>" . __("When a user logs out of WordPress the site usually returns to the Home page.  Whichever page yours returns to put the shortcode <b>&#91;sso_for_tng_logout&#93;</b> (including the square brackets) anywhere convenient on the page. It logs the user out of TNG in the background, and does does not emit any text to your page.",'single-sign-on-for-tng') . "</p><hr>";
	 $arr = array( 'h1' => array('id'=>array()),'h2' => array(),'h3' => array(),'h4' => array(),'hr' => array(),'p' => array(),'em' => array(),'b' => array());
	 echo wp_kses($help,$arr);


 	$Disclaimer = "<h1 id='softwaredisclaimer'>" . __("Software Disclaimer" ,'single-sign-on-for-tng') . "</h1>";
	$Disclaimer .= "<p>" . __("While the author (Author) of this plugin make every effort to deliver high quality software, Author does not
	guarantee that its software is free from defects. The software is provided 'as is', and you use the
	software at your own risk." ,'single-sign-on-for-tng') . "</p>";
	$Disclaimer .= "<p>" . __("This plugin may make changes to your installation of The Next Generation (TNG) application.  Author excepts no
	responsibility, either expressed or implied, for any undesirable changes it may make to the TNG software." ,'single-sign-on-for-tng') . "</p>";
	$Disclaimer .= "<p>" . __("Author makes no warranties as to performance, merchantability, fitness for a particular purpose, or any
	other warranties whether expressed or implied." ,'single-sign-on-for-tng') . "</p>";
	$Disclaimer .= "<p>" . __("No oral or written communication from or information provided by Author shall create a warranty.
	Under no circumstances shall Author be liable for direct, indirect, special,
	incidental, or consequential damages resulting from the use, misuse, or inability to use this software,
	even if Author has been advised of the possibility of such damages." ,'single-sign-on-for-tng') . "</p>";
	echo wp_kses($Disclaimer,$arr);
	?>
	</div>
</div>
