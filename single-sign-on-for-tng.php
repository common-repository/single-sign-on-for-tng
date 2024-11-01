<?php
/*
Plugin Name: Single Sign On For TNG
Description: Automatic TNG sign-on and off, and user account control
Version: 1.1.0
Author: Colin Stearman
Author URI: https://www.stearman.com/wordpress/single-sign-on-for-tng
License:  GPLv2 or later
*/
///////////////////////////////////////////////////////////////////////////////////
namespace SSOFORTNG;
///////////////////////////////////////////////////////////////////////////////////

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

///////////////////////////////////////////////////////////////////////////////////
use wpdb;  //For db access
///////////////////////////////////////////////////////////////////////////////////
define(__NAMESPACE__ . '\PLUGINNAME',plugin_basename(__FILE__));
define(__NAMESPACE__ . '\DEBUG',false);

require_once("classes/cryptor.php");
///////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////

if(!class_exists('SSOFORTNG\SSOFORTNG_Class'))
	{
	class SSOFORTNG_Class
		{
		static private $tngdblink = null;		// database access
		private $options;
		static private $password;

///////////////////////////////////////////////////////////////////////////////////
		/* Construct the plugin object */
		public function __construct()
			{
			add_action('init', array(&$this, 'init'),1);
			}

///////////////////////////////////////////////////////////////////////////////////
		public function init()
			{
			add_shortcode('sso_for_tng', array(&$this, 'ssofortng'));
			add_shortcode('sso_for_tng_logout', array(&$this, 'ssofortng_logout'));
			add_action('admin_init', array(&$this, 'admin_init'));
			add_action('admin_menu', array(&$this, 'add_menu'));
			add_action('wp_authenticate', array(&$this,'save_password'), 10, 2);
			add_action('wp_login', array(&$this,'intercept_login'), 10, 2);
			add_action('clear_auth_cookie', array(&$this,'intercept_logout'));
			add_action( 'user_register', array(&$this,'tng_registration_save'), 10, 2 );
			add_action( 'delete_user', array(&$this,'tng_delete_user'), 10 );
			add_action('password_reset', array(&$this,'tng_password_reset'), 10, 2);
			add_action('after_password_reset', array(&$this,'tng_password_reset'), 10, 2);

			$stylesheeturl = plugins_url( dirname( PLUGINNAME ) . '/css/ssofortng.css');
			$stylesheetpath = plugin_dir_path(__FILE__) . 'css/ssofortng.css';
			wp_register_style('ssofortng-css', $stylesheeturl, array(), filemtime($stylesheetpath));
			wp_enqueue_style('ssofortng-css');

			if (is_admin())	{
				if( ! function_exists('get_plugin_data') )
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$this->plugin_data = get_plugin_data( ABSPATH . 'wp-content/plugins/' .  PLUGINNAME  );
			}

			$this->options = get_option('ssofortng_options');
			$this->makeDBConnection();
			}


///////////////////////////////////////////////////////////////////////////////////
////////////////////////////Public Functions///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////
		/* Activate the plugin */
		public static function activate()
			{
			// Do nothing
			}

///////////////////////////////////////////////////////////////////////////////////
		/* Deactivate the plugin */
		public static function deactivate()
			{
			// Do nothing
			}

///////////////////////////////////////////////////////////////////////////////////
		public function ssofortng($attr) {
			SSOFORTNG_Class::errlog("Calling ssofortng");
			global $current_user;
			$loginpost = null;
			if (empty($this->options) || !isset($this->options['secret'])) {
				return esc_html__("Please configure the Single Sign On For TNG.<br>Log in as Admin and look in Settings.",'single-sign-on-for-tng');
			}

			$stylesheeturl = plugins_url( dirname( PLUGINNAME ) . '/css/blankpage.css');
			$stylesheetpath = plugin_dir_path(__FILE__) . 'css/blankpage.css';
			wp_register_style('blankpage-css', $stylesheeturl, array(), filemtime($stylesheetpath));
			wp_enqueue_style('blankpage-css');

			$scriptpath = plugin_dir_path(__FILE__) . '/js/login.js';

			$logged_in = is_user_logged_in();
			if ($logged_in && isset($_COOKIE["tnglogin"])) {
				wp_get_current_user();
//				SSOFORTNG_Class::errlog($current_user);
				$username = $current_user->user_login;
				if ($this->isTNGUser($username)) {
										// This cookie contains an encrypted password and cannot be sanitized
										// However it contains a nonce which is checked during decryption to
										// ensure it has not been tampered with.
					$decrypted_password =  SSOFORTNG_cryptor::decrypt($this->options['secret'],$_COOKIE["tnglogin"]);
					if ($decrypted_password == 'error')
						wp_die(esc_html__('Invalid encrypted password','single-sign-on-for-tng'));
					SSOFORTNG_Class::errlog("User: " . $username);
					SSOFORTNG_Class::errlog("Password: " .$decrypted_password);
					wp_register_script('login_script', plugins_url( dirname( plugin_basename( __FILE__ ) ) . '/js/login.js'),
						array('jquery'),filemtime($scriptpath),false);
					wp_enqueue_script('login_script');
					wp_add_inline_script( 'login_script', 'const SCRIPTDATA = ' . wp_json_encode( array(
											'tngusername' => $username,
											'tngpassword' => $decrypted_password,
											'url' => $this->options['tngurl']
											) ), 'before' );
				}
			}
			else  { // not a TNG user
				wp_register_script('login_script', plugins_url( dirname( plugin_basename( __FILE__ ) ) . '/js/login.js'),
					array('jquery'),filemtime($scriptpath),false);
				wp_enqueue_script('login_script');
				wp_add_inline_script( 'login_script', 'const SCRIPTDATA = ' . wp_json_encode( array(
										'url' => $this->options['tngurl']
										) ), 'before' );
			}
		}

///////////////////////////////////////////////////////////////////////////////////
		public function save_password($user,$pwd) {  // capture the password for intercept_login
			if (!empty($pwd)) {
				SSOFORTNG_Class::$password = $pwd;
				SSOFORTNG_Class::errlog("Saving password: " . SSOFORTNG_Class::$password);
			}
		}

///////////////////////////////////////////////////////////////////////////////////
		public function intercept_login($username,$user) {
			if (empty($username))
				return;
			if (!$this->isTNGUser($username,true)) {
				if (isset($this->options['autoadd']) &&
					$this->doesUserHaveRole($user->ID,$this->options['role'])) { // add a user already authorized
					$user_id = $user->ID;
					$firstname = get_user_meta( $user_id, 'first_name',true);
					$lastname = get_user_meta( $user_id, 'last_name',true);
					$email = get_user_meta( $user_id, 'email',true);
					SSOFORTNG_Class::errlog($username.' '.SSOFORTNG_Class::$password.' '.
								$firstname . ' ' . $lastname.' '.
								$email);
					$this->addtnguser($username,SSOFORTNG_Class::$password,
								$firstname . ' ' . $lastname,
								$email,true);
				}
			}

			if ($this->isTNGUser($username,true)) {
				$encrypted_password =  SSOFORTNG_cryptor::encrypt($this->options['secret'],SSOFORTNG_Class::$password);
				SSOFORTNG_Class::errlog("Setting encrypted password in cookie: " . SSOFORTNG_Class::$password);
				setcookie("tnglogin",$encrypted_password,strtotime( '+30 days' ));
			}
		}

///////////////////////////////////////////////////////////////////////////////////
		public function ssofortng_logout($attr) {
			if (isset($_COOKIE['tnglogout'])) {
				SSOFORTNG_Class::errlog("Deleting logout Cookie");
				setcookie("tnglogout","",strtotime( '-30 days' ));
				$scriptpath = plugin_dir_path(__FILE__) . '/js/login.js';
				wp_register_script('logout_script', plugins_url( dirname( plugin_basename( __FILE__ ) ) . '/js/logout.js'),
					array('jquery'),filemtime($scriptpath),false);
				wp_enqueue_script('logout_script');
				wp_add_inline_script( 'logout_script', 'const SCRIPTDATA = ' . wp_json_encode( array(
										'url' => $this->options['tngurl']
										) ), 'before' );
			}
		}

///////////////////////////////////////////////////////////////////////////////////
		public function intercept_logout() {
			SSOFORTNG_Class::errlog("Deleting login Cookie");
			setcookie("tnglogin","",strtotime( '-30 days' ));
			SSOFORTNG_Class::errlog("Setting logout Cookie");
			setcookie("tnglogout","logging out",strtotime( '+30 days' ));
		}

///////////////////////////////////////////////////////////////////////////////////
		public function tng_registration_save($user_id, $userdata ) {
//			SSOFORTNG_Class::errlog($userdata);
			$username = $userdata->data->user_login;
			if (!$this->isTNGUser($username)) { // only if not already there
				$firstname = get_user_meta( $user_id, 'first_name',true);
				$lastname = get_user_meta( $user_id, 'last_name',true);
				$this->addtnguser($userdata['user_login'],
								$userdata['user_pass'],
								$firstname . ' ' . $lastname,
								$userdata['user_email']);
			}
		}

///////////////////////////////////////////////////////////////////////////////////
		public function tng_delete_user($user_id) {
			$userdata = get_userdata($user_id);
			$username = $userdata->data->user_login;
 			SSOFORTNG_Class::errlog("Deleting user: $username");
			if ($this->isTNGUser($username)) {
				SSOFORTNG_Class::errlog("delete from tng_users where `username` = '{$username}'");
				SSOFORTNG_Class::$tngdblink->delete("tng_users",array('username' => $username));
			}
		}
///////////////////////////////////////////////////////////////////////////////////
		public function tng_password_reset($user,$newPassword) {
			$username = $user->data->user_login;
			$firstname = get_user_meta( $user->ID, 'first_name',true);
			$lastname = get_user_meta( $user->ID, 'last_name',true);
			$description = $firstname . ' ' . $lastname;
			SSOFORTNG_Class::errlog("In password_reset " . $username . '/' . $newPassword . '/' . $description);
			if ($this->isTNGUser($username)) {
				$allow_living = SSOFORTNG_Class::$tngdblink->get_var("select allow_living from tng_users where username = '{$username}'");
				if ($allow_living  == -1)	//this seems to be a flag for not approved when -1
					$encryptedPassword = $newPassword; // not approved yet, so pwd not yet encrypted, so approval will do it later
				else
					$encryptedPassword = hash('sha256',$newPassword); // pwd already encrypted so encrypt new one
				SSOFORTNG_Class::errlog("update tng_users set password = '{$encryptedPassword}',
						description = '{$description}'
						where `username` = '{$username}'");
				SSOFORTNG_Class::$tngdblink->update("tng_users",
											array('password' => $encryptedPassword,
												  'description' => $description),
											array('username' => $username),
											"%s","%s");
				SSOFORTNG_Class::$password = $newPassword;
				$this->intercept_login($username,null);
			}
		}

///////////////////////////////////////////////////////////////////////////////////
		/* add a menu */
		public function add_menu()
			{
			add_options_page('Single Sign On For TNG', 'Single Sign On For TNG', 'manage_options', 'ssofortng_settings', array(&$this, 'plugin_settings_page'));
			}


///////////////////////////////////////////////////////////////////////////////////
		/* Menu Callback */
		public function plugin_settings_page()
			{
			if(!current_user_can('manage_options'))
				{
				wp_die(esc_html__('You do not have sufficient permissions to access this page.','single-sign-on-for-tng'));
				}
			// Render the settings template
			$jspath = plugin_dir_path(__FILE__) . '/js/keygen.js';
			wp_register_script('keygen_script', plugins_url( dirname( plugin_basename( __FILE__ ) ) . '/js/keygen.js'),
				array('jquery'),filemtime($jspath),false);
			wp_enqueue_script('keygen_script');
			include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
			}

///////////////////////////////////////////////////////////////////////////////////
		/* hook into WP's admin_init action hook */
		public function admin_init()
			{
			$this->plugin_info = get_plugin_data(__FILE__);
			// Set up the settings for this plugin
			$this->init_settings();
			// Possibly do additional admin_init tasks
			}

///////////////////////////////////////////////////////////////////////////////////
		public function validate($input)
			{
			SSOFORTNG_Class::errlog("Validating");
			$input['tngpath'] = sanitize_text_field($input['tngpath']);
			$input['tngpath'] = realpath($input['tngpath']); // maybe convert relative path to absolute
			$input['tngpath'] = $this->isTngPath($input['tngpath']);
			if (!empty($input['tngpath']))
				$this->save_htaccess($input['tngpath']);
			$input['tngurl'] = sanitize_text_field($input['tngurl']);
			$input['tngurl'] = $this->notEmpty($input['tngurl']);
			if (!empty($input['tngurl'] ) && substr($input['tngurl'], -1) != '/')
				$input['tngurl'] .= '/';
			return $input;
			}

///////////////////////////////////////////////////////////////////////////////////
///////////////////////////Private Functions///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////

		private function doesUserHaveRole($user_id,$allowed_role) { //$allowed_role can be a string or array of strings
			// Get the user object.
			$user = get_userdata($user_id);
			if ($user === false)
				return false;

			// Get all the user roles as an array.
			$user_roles = $user->roles;

			// Check if the role you're interested in is present in the array.
			$res = array_intersect($user_roles, $allowed_role);
			return count($res);
		}

///////////////////////////////////////////////////////////////////////////////////
// If TNG password is not SHA256 or is, but not as the WP one, fix it
///////////////////////////////////////////////////////////////////////////////////
		private function maybeUpdateTNGPassword($userinfo) {
			$encryptedPassword = hash('sha256',SSOFORTNG_Class::$password);
			if ($userinfo->password_type != 'sha256' || $userinfo->password != $encryptedPassword) {
				SSOFORTNG_Class::errlog("Fixing pwd");
					// update or correct password in TNG
				SSOFORTNG_Class::$tngdblink->update("tng_users",
										array('password' => $encryptedPassword,
											  'password_type' => 'sha256' ),
										array('userID' => $userinfo->userID));
			}
			else
				SSOFORTNG_Class::errlog("Password OK");
		}

///////////////////////////////////////////////////////////////////////////////////

		private function save_htaccess($tngpath) {
			global $wp_filesystem;
			$token = '### TNG_SSO ###';
			$endtoken = '### END TNG_SSO ###';
			$wordpressfolder = $this->getWPInstallFolder();
			$tngdir = basename($this->options['tngpath']);
			$htaccessfile = $tngpath . '/.htaccess';
			$template = $wp_filesystem->get_contents(sprintf("%s/templates/htaccess.txt", dirname(__FILE__)));
			SSOFORTNG_Class::errlog("Template: $template");
			$template = str_replace('^tngdir^',$tngdir,$template);
			$template = str_replace('^wpdir^',$wordpressfolder,$template);
			SSOFORTNG_Class::errlog($template);
			if (file_exists($htaccessfile)) { // maybe append our stuff to an existing file that doesn't already have it
				SSOFORTNG_Class::errlog('htaccess exists');
				$filecontents = $wp_filesystem->get_contents($htaccessfile);
				if (strpos($filecontents,$token) !== false)	  { // the file already has our section so delete it
					$pattern = "/^${token}.+${endtoken}\\n/sm";
					$filecontents = preg_replace($pattern,'',$filecontents);
				}
				$wp_filesystem->put_contents($htaccessfile,$filecontents . $template); // apend our stuff
			}
			else {							// Create a new .htacess file
				SSOFORTNG_Class::errlog("Writing to $htaccessfile");
				$wp_filesystem->put_contents($htaccessfile,$template);
			}
		}

///////////////////////////////////////////////////////////////////////////////////
		private function getWPInstallFolder() {
			if (isset($_SERVER['DOCUMENT_ROOT'])) {
				$docroot = rtrim(esc_url(sanitize_url(wp_unslash($_SERVER['DOCUMENT_ROOT']))),'/');	// /var/www/html/domains/www.stearman.com
				$pluginpath = plugin_dir_path( __DIR__ );			// /var/www/html/domains/www.stearman.com/wordpress/wp-content/plugins/
				preg_match(':' . $docroot . "(.*/)wp-content/plugins/:",$pluginpath,$match);
				$wordpressfolder = $match[1];
				return $wordpressfolder;
			}
			wp_die();
		}

///////////////////////////////////////////////////////////////////////////////////
		private function makeDBConnection() {
			if (SSOFORTNG_Class::$tngdblink != null)
				return;
			if (isset($this->options['tngpath']) && $this->checkTngPath($this->options['tngpath']) == -1) {
				require_once $this->options['tngpath'] . '/' . 'config.php';
				SSOFORTNG_Class::$tngdblink = new wpdb($database_username, $database_password, $database_name, $database_host);
			}
		}

///////////////////////////////////////////////////////////////////////////////////
		private function email_admin($subject,$msg) {
			$admin_email = get_option('admin_email');
//			SSOFORTNG_Class::errlog($admin_email);
			wp_mail($admin_email, $subject, $msg);
		}

///////////////////////////////////////////////////////////////////////////////////
// If fixpwd is true then if the TNG password is not encrypted or the WP one encrypted, update it
///////////////////////////////////////////////////////////////////////////////////
		private function isTNGUser($user,$fixpwd=false) { // $user might be the username or email, depending on what they used to login with
			$res = SSOFORTNG_Class::$tngdblink->get_row("select userID,password,password_type from tng_users where username = '{$user}' or email = '{$user}'");
			if ($res == null)
				return false;
			if ($fixpwd)
				$this->maybeUpdateTNGPassword($res);
			return true;
		}

///////////////////////////////////////////////////////////////////////////////////
		static private function errlog($var) {
			$debugcode = plugin_dir_path(__FILE__) . '/debug/debug.php';
			if (file_exists($debugcode) && DEBUG) {
			include $debugcode;
			}
		}

///////////;////////////////////////////////////////////////////////////////////////
		private function addtnguser($username,$password,$realname,$email,$activate=false) {
			SSOFORTNG_Class::errlog("Adding $username,$password,$realname,$email to TNG");
			if ($activate)
				$password = hash('sha256',$password);
			SSOFORTNG_Class::$tngdblink->query(SSOFORTNG_Class::$tngdblink->prepare(
				"insert into tng_users (`description`,`username`,`password`,`realname`,`email`,
					`password_type`,`allow_private_notes`,`allow_private_media`,`dt_consented`,
					`reset_pwd_code`,`allow_living`,`role`)
					VALUES (%s,%s,%s,%s,%s,'sha256',0,0,0,'',%d,'guest')",
						$realname,$username,$password,$realname,$email,$activate?1:-1 ));

			if ($activate) {
				$msg = esc_html__("A user has logged into your WordPress site with a role defined in set up\n",'single-sign-on-for-tng');
				$msg = esc_html__("and who does not have a TNG account, so one has been added and activated for them.\n",'single-sign-on-for-tng');
				$msg = esc_html__("There is no need to take action unless you want to revise their TNG to something other than Guest in the TNG Adminstration, Users screen.\n",'single-sign-on-for-tng');
			}
			else {
				$msg = esc_html__("A user has registered for access to your WordPress site, and, as you have installed, activated and configured the Single Sign On For TNG plugin, then by extension to your TNG family tree site also.\n",'single-sign-on-for-tng');
				$msg .= esc_html__("If you have a user authorization system in WordPress then you will need to authorize this user.\n",'single-sign-on-for-tng');
				$msg .= esc_html__("This will not, by itself, give anything but public access to TNG.  But if you want to give them some level of access to TNG greater than public then you need to log into TNG as administrator and approve and configure the user as appropriate under Users in the Review tab.\n",'single-sign-on-for-tng');
				$msg .= esc_html__("If you do not want to give this user access to your WordPress site then use Delete for that user in the WordPress Users menu.  The user will automatically be deleted from TNG as well.\n\n",'single-sign-on-for-tng');
			}
			$msg .= esc_html__("WordPress Administration\n",'single-sign-on-for-tng');
	      /* translators: %s ($s): is the user's real name and username */
			$subject = sprintf($activate ?
				esc_html__('Existing WordPress User %s (%s) Has Had An Account Added To TNG','single-sign-on-for-tng')
			:
				esc_html__('New User %s (%s) Has Registered For Site Access','single-sign-on-for-tng'),
				esc_html($realname),esc_html($username));
			$this->email_admin($subject,$msg);
		}

///////////////////////////////////////////////////////////////////////////////////
		/* Initialize some custom settings */
		private function init_settings()
			{
			// register the settings for this plugin
			register_setting('ssofortng-data', 'ssofortng_options',array(&$this,'validate'));
			}

///////////////////////////////////////////////////////////////////////////////////
		private function notEmpty($input)
			{
			$input = trim($input);
//			SSOFORTNG_Class::errlog("In notEmpty: " . print_r($input,true));
			if (empty($input) && !$this->requiredFieldsErrorMessageFlag)
				{
				$this->requiredFieldsErrorMessageFlag = true;
				add_settings_error(
					'Required field',           // setting title
					'texterror',            // error ID
					'All fields marked with * are required',   // error message
					'error'                        // type of message
					);
				return '';
				}
			return $input;
			}

///////////////////////////////////////////////////////////////////////////////////
		private function checkTngPath($path) {
			if (empty($path)) {
				return 0;
			}
			else if (!is_dir($path)) {
				return 1;
			}
			else if (!is_file($path . '/version.php')) {
				return 2;
			}
			else {
				include $path . '/version.php';
				if (!isset($tng_abbrev))
					return 3;
			}
			return -1;
		}

///////////////////////////////////////////////////////////////////////////////////
		private function isTngPath($path) {
			$path = trim($path);
			$state = $this->checkTngPath($path);
			SSOFORTNG_Class::errlog($state);
			switch ($state) {
			case -1:
				return $path;
			case 0:
				$msg = esc_html__("No TNG installation path provided",'single-sign-on-for-tng');
				break;
			case 1:
				$msg = esc_html__("TNG installation path provided does not exist",'single-sign-on-for-tng');
				break;
			case 2:
				$msg = esc_html__("No version.php found in TNG installation path provided",'single-sign-on-for-tng');
				break;
			case 3:
				$msg = esc_html__("No \$tng_abbrev found in version.php in the TNG installation path provided",'single-sign-on-for-tng');
				break;
			}

			add_settings_error(
				'Required field',           // setting title
				'patherror',				// error ID
				$msg,						// error message
				'error'                     // type of message
				);
			return '';
		}

///////////////////////////////////////////////////////////////////////////////////

	} // end of class
} // End of if class exists

///////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////
if (class_exists('SSOFORTNG\SSOFORTNG_Class'))
	{
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('SSOFORTNG\SSOFORTNG_Class', 'activate'));
	register_deactivation_hook(__FILE__, array('SSOFORTNG\SSOFORTNG_Class', 'deactivate'));

	// instantiate the plugin class
	$SSOFORTNG_Class = new SSOFORTNG_Class();
	}
///////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////
