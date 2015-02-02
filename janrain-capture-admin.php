<?php
/**
 * @package Janrain Capture
 *
 * Admin interface for plugin options
 *
 */
class JanrainCaptureAdmin {

	private $postMessage;
	private $fields;

	/**
	 * Initializes plugin name, builds array of fields to render.
	 *
	 * @param string $name
	 *   The plugin name to use as a namespace
	 */
	function  __construct() {
		$path = dirname( __FILE__ ) . '/janrain-capture-screens/';
		$site_url = site_url();
		$this->postMessage = array( 'class' => '', 'message' => '' );
		$this->fields = array(
			// Main Screen Fields
			array(
				'name' => JanrainCapture::$name . '_ui_share_enabled',
				'title' => 'Enable Social Sharing',
				'description' => 'Load the JS and CSS required for the Engage Share Widget',
				'default' => '1',
				'type' => 'checkbox',
				'screen' => 'main',
			),
			array(
				'name' => JanrainCapture::$name . '_rpx_share_providers',
				'title' => 'Share Providers to Display',
				'description' => 'Choose share providers to display. Note: You must configure all providers on your Enagage Dashboard: https://rpxnow.com',
				'type' => 'multiselect',
				'default' => array( 0 => 'email', 1 => 'facebook', 2 => 'linkedin', 3 => 'twitter' ),
				'options' => array( 0 => 'email', 1 => 'facebook', 2 => 'linkedin', 3 => 'mixi', 4 => 'myspace', 5 => 'twitter', 6 => 'yahoo' ),
				'screen' => 'main',
			 ),
			array(
				'name' => JanrainCapture::$name . '_ui_native_links',
				'title' => 'Override Native Links',
				'description' => 'Replace native Login & Profile links with Capture links',
				'default' => '1',
				'type' => 'checkbox',
				'screen' => 'main',
			),
			array(
				'name' => JanrainCapture::$name . '_ui_web_view',
				'title' => 'Support for Mobile WebView',
				'description' => 'The default Janrain login UI uses popups, which are not supported in mobile WebView. Check this box to change the Janrain login to appear in embedded mode which will support mobile WebView.',
				'default' => '0',
				'type' => 'checkbox',
				'screen' => 'main',
			),
			array(
				'name' => JanrainCapture::$name . '_ui_type',
				'title' => 'UI Type',
				'description' => 'Choose from Capture, Capture Legacy, or None if you plan to build your own.',
				'type' => 'select',
				'default' => 'Capture',
				'options' => array( 'None', 'Capture Legacy', 'Capture' ),
				'screen' => 'main',
			),

			// CaptureUI Settings
			array(
				'name' => JanrainCapture::$name . '_address',
				'title' => 'Application Domain',
				'description' => 'Your Capture application domain <br/>(example: demo.janraincapture.com)',
				'required' => true,
				'default' => '',
				'type' => 'text',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_client_id',
				'title' => 'API Client ID',
				'description' => 'Your Capture Client ID',
				'required' => true,
				'default' => '',
				'type' => 'text',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_client_secret',
				'title' => 'API Client Secret',
				'description' => 'Your Capture Client Secret',
				'required' => true,
				'default' => '',
				'type' => 'text',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_signin_ext',
				'title' => 'Signin Screen Extension',
				'description' => 'Modifies the signin path of the signin screen.',
				'default' => '',
				'type' => 'text',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_federate',
				'title' => 'Federate Settings',
				'type' => 'title',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_sso_enabled',
				'title' => 'Enable SSO',
				'description' => 'Enable/Disable SSO for CaptureUI',
				'default' => '0',
				'type' => 'checkbox',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_sso_address',
				'title' => 'SSO Application Domain',
				'description' => 'Your Janrain Federate SSO domain <br/>(example: https://demo.janrainsso.com)',
				'default' => '',
				'type' => 'text',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_backplane_settings',
				'title' => 'Backplane Settings',
				'type' => 'title',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_backplane_enabled',
				'title' => 'Enable Backplane',
				'description' => 'Enable/Disable Backplane for CaptureUI',
				'default' => '0',
				'type' => 'checkbox',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_bp_server_base_url',
				'title' => 'Server Base URL',
				'description' => 'Your Backplane server URL',
				'default' => '',
				'type' => 'text',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_bp_bus_name',
				'title' => 'Bus Name',
				'description' => 'Your Backplane Bus Name',
				'default' => '',
				'type' => 'text',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_bp_js_path',
				'title' => 'JS Path',
				'description' => 'The path to backplane.js',
				'default' => '',
				'type' => 'long-text',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_ui_options',
				'title' => 'Other Options',
				'type' => 'title',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_recover_password_screen',
				'title' => 'Recover Password Screen',
				'description' => 'The name of the Capture screen to launch for users who click the authentication link in password recover emails',
				'default' => 'profile',
				'type' => 'text',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_ui_colorbox',
				'title' => 'Load Colorbox',
				'description' => 'You can use the colorbox JS & CSS bundled in our plugin or use your own',
				'default' => '1',
				'type' => 'checkbox',
				'screen' => 'options',
			),
			array(
				'name' => JanrainCapture::$name . '_ui_capture_js',
				'title' => 'Load Capture JS',
				'description' => 'The included Capture JS relies on Colorbox. You may want to disable it and use your own.',
				'default' => '1',
				'type' => 'checkbox',
				'screen' => 'options',
			),

			// Widget Settings
			array(
				'name' => JanrainCapture::$name . '_widget_address',
				'title' => 'Application Url',
				'description' => 'Your Capture application url <br/>(example: https://demo.janraincapture.com)',
				'required' => true,
				'default' => '',
				'type' => 'long-text',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_app_id',
				'title' => 'Application ID',
				'description' => 'Your Capture Application ID',
				'required' => true,
				'default' => '',
				'type' => 'text',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_client_id',
				'title' => 'API Client ID',
				'description' => 'Your Capture Client ID',
				'required' => true,
				'default' => '',
				'type' => 'long-text',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_client_secret',
				'title' => 'API Client Secret',
				'description' => 'Your Capture Client Secret',
				'required' => true,
				'default' => '',
				'type' => 'long-text',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_packages',
				'title' => 'Packages',
				'description' => 'Change this only when instructed to do so (default: capture & login)',
				'required' => true,
				'type' => 'multiselect',
				'default' => array( 0 => 'capture', 1 => 'login' ),
				'options' => array( 0 => 'capture', 1 => 'login', 3 => 'share' ),
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_engage_url',
				'title' => 'Engage Application Url',
				'description' => 'Your Janrain Engage Applicaiton url <br/>(example: https://capturewidget.rpxnow.com)',
				'default' => 'https://capturewidget.rpxnow.com',
				'required' => true,
				'type' => 'long-text',
				'screen' => 'main',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_federate',
				'title' => 'Federate Settings',
				'type' => 'title',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_sso_enabled',
				'title' => 'Enable SSO',
				'description' => 'Enable/Disable SSO for Capture',
				'default' => '0',
				'type' => 'checkbox',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_sso_address',
				'title' => 'Application Domain',
				'description' => 'Your Janrain Federate SSO domain <br/>(example: https://demo.janrainsso.com)',
				'default' => '',
				'type' => 'text',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_so_xd',
				'title' => 'Cross-Domain Reciever Page',
				'description' => "Your Janrain Federate XD Reciever url <br/>(example: $site_url/wp-admin/admin-ajax.php?action=janrain_capture_xdcomm)",
				'default' => "{$site_url}/wp-admin/admin-ajax.php?action=janrain_capture_xdcomm",
				'type' => 'text',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_sso_logout',
				'title' => 'Federate Logout Page',
				'description' => "Your Janrain Federate Logout url.<br/>Leave blank or enter the full url of your customized WordPress logout page.",
				'default' => "",
				'type' => 'text',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_backplane_settings',
				'title' => 'Backplane Settings',
				'type' => 'title',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_backplane_enabled',
				'title' => 'Enable Backplane',
				'description' => 'Enable/Disable Backplane for Capture',
				'default' => '0',
				'type' => 'checkbox',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_bp_server_base_url',
				'title' => 'Server Base URL',
				'description' => 'Your Backplane Server Base URL',
				'prefix' => 'https://',
				'default' => '',
				'type' => 'text',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_bp_bus_name',
				'title' => 'Bus Name',
				'description' => 'Your Backplane Bus Name',
				'default' => '',
				'type' => 'text',
				'screen' => 'widget',
			),
			array(
				'name' => JanrainCapture::$name . '_bp_version',
				'title' => 'Backplane Version',
				'description' => 'Choose from Backplane Version 1.2 or 2.0',
				'type' => 'select',
				'default' => 1.2,
				'options' => array( 1.2, 2 ),
				'screen' => 'widget',
			),

			# widget UI settings
			array(
				'name' => JanrainCapture::$name . '_widget_load_js',
				'title' => 'Url for load.js file',
				'description' => 'The absolute url (minus protocol) of the Widget load.js file <br/>(example: d16s8pqtk4uodx.cloudfront.net/load-default)',
				'default' => '',
				'required' => true,
				'type' => 'text',
				'screen' => 'widgetui',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_edit_page',
				'title' => 'Edit Profile Page',
				'description' => 'Create a page with the shortcode: [janrain_capture action="edit_profile"] and remove it from the menu.<br/>(example: '.site_url().'/?page_id=2)',
				'required' => true,
				'default' => site_url() . '/?page_id=2',
				'type' => 'long-text',
				'screen' => 'widgetui',
			 ),
			array(
				'name' => JanrainCapture::$name . '_widget_screen_folder',
				'title' => 'Screens Folder',
				'description' => 'The absolute url of the Widget screens folder <br/>(example: ' . plugins_url() . '/janrain-capture-screens/)',
				'default' => plugins_url() . '/janrain-capture-screens/',
				'type' => 'long-text',
				'screen' => 'widgetui',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_auth_screen',
				'title' => 'Sign In Screens File',
				'description' => 'The filename of the Widget screen file to launch for login and registration',
				'default' => 'signin.html',
				'type' => 'text',
				'screen' => 'widgetui',
			),
			array(
				'name' => JanrainCapture::$name . '_widget_edit_screen',
				'title' => 'Edit Profile Screen File',
				'description' => 'The filename of the Widget screen file for editing a user\'s Capture Profile',
				'default' => 'edit-profile.html',
				'type' => 'text',
				'screen' => 'widgetui',
			 ),
			array(
				'name' => JanrainCapture::$name . '_widget_forgot_screen',
				'title' => 'Forgot Password Screen File',
				'description' => 'The filename of the Widget screen file for resetting a user\'s password.',
				'default' => 'forgot.html',
				'type' => 'text',
				'screen' => 'widgetui',
			 ),
			array(
				'name' => JanrainCapture::$name . '_widget_verify_screen',
				'title' => 'Email Verify Screen File',
				'description' => 'The filename of the Widget screen file for email verfication confirmation.',
				'default' => 'verify.html',
				'type' => 'text',
				'screen' => 'widgetui',
			 ),

			// Data Mapping Screen Fields
			array(

				'name' => JanrainCapture::$name . '_standard_fields',
				'title' => 'Standard WordPress User Fields',
				'description' => '',
				'type' => 'title',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_user_email',
				'title' => 'Email',
				'description' => '',
				'required' => true,
				'default' => 'email',
				'type' => 'text',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_user_login',
				'title' => 'Username',
				'description' => 'Usernames cannot be changed.',
				'required' => true,
				'default' => 'uuid',
				'type' => 'text',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_user_nicename',
				'title' => 'Nickname',
				'description' => '',
				'required' => true,
				'default' => 'displayName',
				'type' => 'text',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_user_display_name',
				'title' => 'Display Name',
				'description' => '',
				'required' => true,
				'default' => 'displayName',
				'type' => 'text',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_optional_fields',
				'title' => 'Optional User Fields',
				'description' => '',
				'type' => 'title',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_user_first_name',
				'title' => 'First Name',
				'description' => '',
				'default' => 'givenName',
				'type' => 'text',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_user_last_name',
				'title' => 'Last Name',
				'description' => '',
				'default' => 'familyName',
				'type' => 'text',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_user_url',
				'title' => 'Website',
				'description' => '',
				'default' => '',
				'type' => 'text',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_user_aim',
				'title' => 'AIM',
				'description' => '',
				'default' => '',
				'type' => 'text',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_user_yim',
				'title' => 'Yahoo IM',
				'description' => '',
				'default' => '',
				'type' => 'text',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_user_jabber',
				'title' => 'Jabber / Google Talk',
				'description' => '',
				'default' => '',
				'type' => 'text',
				'screen' => 'data',
			),
			array(
				'name' => JanrainCapture::$name . '_user_description',
				'title' => 'Biographical Info',
				'description' => '',
				'default' => 'aboutMe',
				'type' => 'text',
				'screen' => 'data',
			),
		);

		$this->on_post();
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
			if ( ! is_main_site() ) {
				add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			}
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}
	}

	/**
	 * Method bound to register_activation_hook.
	 */
	function activate() {
		foreach ( $this->fields as $field ) {
			if ( ! empty( $field['default'] ) ) {
				if ( JanrainCapture::get_option( $field['name'] ) === false) {
					JanrainCapture::update_option( $field['name'], $field['default'] );
				}
			}
		}
	}

	/**
	 * Method bound to the admin_menu action.
	 */
	function admin_menu() {
		$ui = JanrainCapture::get_option( JanrainCapture::$name . '_ui_type' );
		$optPage = add_menu_page(
			__( 'Janrain Capture' ),
			__( 'Janrain Capture' ),
			'manage_options',
			JanrainCapture::$name,
			array( $this, 'main' )
		);
		if ( $ui == 'Capture Legacy' ) {
			$optionsPage = add_submenu_page(
				JanrainCapture::$name,
				__( 'Janrain Capture' ),
				__( 'Capture Legacy Settings' ),
				'manage_options',
				JanrainCapture::$name . '_options',
				array( $this, 'options' ));
		} elseif ( $ui == 'Capture' ) {
			$widgetPage = add_submenu_page(
				JanrainCapture::$name,
				__( 'Janrain Capture' ),
				__( 'Capture Settings' ),
				'manage_options',
				JanrainCapture::$name . '_widget',
				array( $this, 'widget' ));
			$widgetUiPage = add_submenu_page(
				JanrainCapture::$name,
				__( 'Janrain Capture' ),
				__( 'Interface Settings' ),
				'manage_options',
				JanrainCapture::$name . '_widgetui',
				array( $this, 'widgetui' ));
		} else {
			$optionsPage = add_submenu_page(
				JanrainCapture::$name,
				__( 'Janrain Capture' ),
				__( 'Capture Legacy' ),
				'manage_options',
				JanrainCapture::$name . '_options',
				array( $this, 'options' ));
			$widgetPage  = add_submenu_page(
				JanrainCapture::$name,
				__( 'Janrain Capture' ),
				__( 'Capture Settings' ),
				'manage_options',
				JanrainCapture::$name . '_widget',
				array( $this, 'widget' ));
		}
		if ( ! is_multisite() || is_main_site() ) {
			$dataPage = add_submenu_page(
				JanrainCapture::$name,
				__( 'Janrain Capture' ),
				__( 'Data Mapping' ),
				'manage_options',
				JanrainCapture::$name . '_data',
				array( $this, 'data' ));
		}
	}

	/**
	 * Method bound to the Janrain Capture main menu.
	 */
	function main() {
		$args = new stdClass();
		$args->title  = 'Janrain Capture Settings';
		$args->action = 'main';
		$this->print_admin( $args );
	}

	/**
	 * Method bound to the Janrain Capture Legacy menu.
	 */
	function options() {
		$args = new stdClass();
		$args->title  = 'Capture Legacy Settings';
		$args->action = 'options';
		$this->print_admin( $args );
	}

	/**
	 * Method bound to the Janrain Capture data menu.
	 */
	function data() {
		$args = new stdClass();
		$args->title  = 'Data Mapping Settings';
		$args->action = 'data';
		$this->print_admin( $args );
	}

	/**
	 * Method bound to the Janrain Capture or widget menu.
	 */
	function widget() {
		$args = new stdClass();
		$args->title  = 'Capture Settings';
		$args->action = 'widget';
		$this->print_admin( $args );
	}

	/**
	 * Method bound to the Janrain Capture or widget menu.
	 */
	function widgetui() {
		$args = new stdClass();
		$args->title  = 'Interface Settings';
		$args->action = 'widgetui';
		$this->print_admin( $args );
	}

	/**
	 * Method to print the admin page markup.
	 *
	 * @param stdClass $args
	 *   Object with page title and action variables
	 */
	function print_admin( $args ) {
		$name = JanrainCapture::$name;
		echo <<<HEADER
<div id="message" class="{$this->postMessage['class']} fade">
	<p><strong>
		{$this->postMessage['message']}
	</strong></p>
</div>
<div class="wrap">
	<h2>{$args->title}</h2>
	<form method="post" id="{$name}_{$args->action}">
		<table class="form-table">
			<tbody>
HEADER;

		foreach ( $this->fields as $field ) {
			if ( $field['screen'] == $args->action ) {
				$this->print_field( $field );
			}
		}

		echo <<<FOOTER
			</tbody>
		</table>
		<p class="submit">
			<input type="hidden" name="{$name}_action" value="{$args->action}" />
			<input type="submit" class="button-primary" value="Save Changes" />
		</p>
	</form>
</div>
FOOTER;
	}

	/**
	 * Method to print field-level markup.
	 *
	 * @param array $field
	 *   A structured field definition with strings used in generating markup.
	 */
	function print_field( $field ) {
		if ( is_multisite() && ! is_main_site() ) {
			$value = ( get_option( $field['name'] ) !== false )
				? get_option( $field['name'] )
				: JanrainCapture::get_option( $field['name'], false, true );
		} else {
			$default = isset( $field['default'] )
				? $field['default']
				: '';
			$value = JanrainCapture::get_option( $field['name'] );
			$value = ( $value !== false )
				? $value
				: $default;
		}
		$r = ( isset( $field['required'] ) && $field['required'] == true )
			? ' <span class="description">(required)</span>'
			: '';

		if ( is_wp_error($value) ) {
			$value = "WPError: {$value->get_error_message()}";
		}

		$prefix = array_key_exists('prefix', $field) ? $field['prefix'] : '';

		switch ( $field['type'] ) {
			case 'text':
				echo <<<TEXT
				<tr>
					<th><label for="{$field['name']}">{$field['title']}$r</label></th>
					<td>
						{$prefix}<input type="text" name="{$field['name']}" value="$value" style="width:200px" />
						<span class="description">{$field['description']}</span>
					</td>
				</tr>
TEXT;
				break;
			case 'long-text':
				echo <<<LONGTEXT
				<tr>
					<th><label for="{$field['name']}">{$field['title']}$r</label></th>
					<td>
						<input type="text" name="{$field['name']}" value="$value" style="width:400px" />
						<span class="description">{$field['description']}</span>
					</td>
				</tr>
LONGTEXT;
				break;
			case 'textarea':
				echo <<<TEXTAREA
				<tr>
					<th><label for="{$field['name']}">{$field['title']}$r</label></th>
					<td>
						<span class="description">{$field['description']}</span><br/>
						<textarea name="{$field['name']}" rows="10" cols="80">$value</textarea>
					</td>
				</tr>
TEXTAREA;
				break;
			case 'password':
				echo <<<PASSWORD
				<tr>
					<th><label for="{$field['name']}">{$field['title']}$r</label></th>
					<td>
						<input type="password" name="{$field['name']}" value="$value" style="width:150px" />
						<span class="description">{$field['description']}</span>
					</td>
				</tr>
PASSWORD;
				break;
			case 'select':
				sort( $field['options'] );
				echo <<<SELECT
				<tr>
					<th><label for="{$field['name']}">{$field['title']}$r</label></th>
					<td>
							<select name="{$field['name']}" value="$value">
SELECT;
						foreach ( $field['options'] as $option ) {
							$selected = ( $value == $option )
								? ' selected="selected"'
								: '';
							echo "<option value=\"{$option}\"{$selected}>$option</option>";
						}
						echo <<<ENDSELECT
							</select>
							<span class="description">{$field['description']}</span>
					</td>
				</tr>
ENDSELECT;
				break;
			case 'multiselect':
				sort( $field['options'] );
				echo <<<MSELECT
				<tr>
					<th><label for="{$field['name']}[]">{$field['title']}$r</label></th>
					<td valign="top">
							<select name="{$field['name']}[]" multiple="multiple">
MSELECT;
						foreach ( $field['options'] as $option ) {
							$selected = in_array( $option, $value ) !== false
								? ' selected="selected"'
								: '';
							echo "<option value=\"{$option}\"{$selected}>$option</option>";
						}
						echo <<<MENDSELECT
							</select>
							{$field['description']}
					</td>
				</tr>
MENDSELECT;
				break;
			case 'checkbox':
				$checked = ($value == '1')
					? ' checked="checked"'
					: '';
				echo <<<CHECKBOX
				<tr>
					<th><label for="{$field['name']}">{$field['title']}$r</label></th>
					<td>
						<input type="checkbox" name="{$field['name']}" value="1"$checked />
						<span class="description">{$field['description']}</span>
					</td>
				</tr>
CHECKBOX;
				break;
			case 'title':
				echo <<<TITLE
				<tr>
					<td colspan="2">
						<h3 class="title">{$field['title']}</h3>
					</td>
				</tr>
TITLE;
				break;
		}
	}

	/**
	 * Method to receive and store submitted options when posted.
	 */
	public function on_post() {
		if ( isset( $_POST[JanrainCapture::$name . '_action'] ) ) {
			foreach ( $this->fields as $field ) {
				if ( isset( $_POST[$field['name']] ) ) {
					$value = $_POST[$field['name']];
					if ($field['name'] == JanrainCapture::$name . '_address' || $field['name'] == JanrainCapture::$name . '_sso_address') {
						$value = preg_replace( '/^https?\:\/\//i', '', $value );
					}
					JanrainCapture::update_option( $field['name'], $value );
				} else {
					if ( $field['type'] == 'checkbox' && $field['screen'] == $_POST[JanrainCapture::$name . '_action'] ) {
						$value = '0';
						JanrainCapture::update_option( $field['name'], $value );
					} else {
						if (JanrainCapture::get_option( $field['name'] ) === false
								&& isset($field['default'])
								&& (!is_multisite() || is_main_site())) {
							JanrainCapture::update_option( $field['name'], $field['default'] );
						}
					}
				}
			}
			$this->postMessage = array( 'class' => 'updated', 'message' => 'Configuration Saved' );
		}
	}
}
