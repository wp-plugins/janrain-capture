<?php
/*
Plugin Name: Janrain Capture
Plugin URI: http://janrain.com/capture/
Description: Collect, store and leverage user profile data from social networks in a flexible, lightweight hosted database.
Version: 0.2.7
Author: Janrain
Author URI: http://developers.janrain.com/extensions/wordpress-for-capture/
License: Apache License, Version 2.0
*/

// Handle sessions for logged out users cause wtf wp.
add_action('init', function () {if ( ! session_id() ) session_start();}, 1);
add_action('wp_logout', function () {session_destroy();});
add_action('wp_login', function () {session_destroy();});

// If SSO is turned on, relax the security on the logoutURL so SSO can log you out.
if ( get_option( 'janrain_capture_widget_sso_enabled' ) ) {
	// SSO enabled, enable remote logouts
	add_action( 'login_form_logout', function () {
		// check for sso
		if ( empty( $_REQUEST['_janrainsso'] ) ) {
			// no sso, let normal wp happen.
			return;
		}
		// Allow sso to load in iframe.
		header_remove( 'X-Frame-Options' );
		// logout user
		wp_logout();
		// hard stop wp
		exit;
	});
}

if ( ! class_exists( 'JanrainCapture' ) ) {
	/**
	 * @package Janrain Capture
	 */
	class JanrainCapture {

		public $path;
		public $basename;
		public $url;
		public static $name = 'janrain_capture';
		public $ui;

		/**
		 * Initializes the plugin.
		 */
		function init() {
			$this->path = untrailingslashit( plugin_dir_path( __FILE__ ) );
			$this->url  = untrailingslashit( plugin_dir_url( __FILE__ ) );

			register_activation_hook( __FILE__, array( $this, 'activate' ) );
			require_once $this->path . '/janrain-capture-api.php';

			if ( is_admin() ) {
				require_once $this->path . '/janrain-capture-admin.php';
				$admin = new JanrainCaptureAdmin();
				add_action( 'wp_ajax_' . self::$name . '_redirect_uri', array( $this, 'redirect_uri' ) );
				add_action( 'wp_ajax_nopriv_' . self::$name . '_redirect_uri', array( $this, 'redirect_uri' ) );
				add_action( 'wp_ajax_' . self::$name . '_refresh_token', array( $this, 'refresh_token' ) );
				add_action( 'wp_ajax_nopriv_' . self::$name . '_refresh_token', array( $this, 'refresh_token' ) );
				add_action( 'wp_ajax_' . self::$name . '_xdcomm', array( $this, 'xdcomm' ) );
				add_action( 'wp_ajax_nopriv_' . self::$name . '_xdcomm', array( $this, 'xdcomm' ) );
				$ui_type = self::get_option( self::$name . '_ui_type' );
				if ( $ui_type == 'Capture' ) {
					add_action( 'wp_ajax_' . self::$name . '_profile', array( $this, 'widget_redir' ) );
					add_action( 'wp_ajax_nopriv_' . self::$name . '_profile', array( $this, 'widget_redir' ) );
				} else {
					add_action( 'wp_ajax_' . self::$name . '_profile', array( $this, 'profile' ) );
					add_action( 'wp_ajax_nopriv_' . self::$name . '_profile', array( $this, 'profile' ) );
					add_action( 'wp_ajax_' . self::$name . '_profile_update', array( $this, 'profile_update' ) );
					add_action( 'wp_ajax_nopriv_' . self::$name . '_profile_update', array( $this, 'profile_update' ) );
				}
			} else {
				add_shortcode( self::$name, array( $this, 'shortcode' ) );
				add_shortcode( 'janrain_share', array( $this, 'shortcode_share' ) );
			}
			require_once $this->path . '/janrain-capture-ui.php';
			$this->ui = new JanrainCaptureUi();
		}

		/**
		 * Method bound to register_activation_hook.
		 */
		function activate() {
			require_once dirname( __FILE__ ) . '/janrain-capture-admin.php';
			$admin = new JanrainCaptureAdmin();
			$admin->activate();
		}

		/**
		 * Method used for the janrain_capture_redirect_uri action on admin-ajax.php.
		 */
		function redirect_uri() {
			$code = isset( $_REQUEST['code'] )
				? $_REQUEST['code']
				: '';
			$url_type = isset( $_REQUEST['url_type'] )
				? $_REQUEST['url_type']
				: false;
			if ( $url_type && (! ctype_alnum( str_replace( '-', '', $url_type ) ) ) ) {
				header( 'HTTP/1.1 400 Bad Request' );
				exit;
			}
			if ( isset( $_REQUEST['verification_code'] ) ) {
				$url_type = 'verify';
				$code = $_REQUEST['verification_code'];
			}
			if ( ! ctype_alnum( $code ) && ! $code) {
				throw new Exception( 'Janrain Capture: received code was not valid' );
			}
			if ( $url_type ) {
				$this->widget_show_screen($url_type, $code);
				exit;
			}
			$origin = isset( $_REQUEST['origin'] )
				? $_REQUEST['origin']
				: '';
			$redirect_args = array(
				'action' => self::$name . '_redirect_uri',
			);
			if ( $origin != '' ) {
				$redirect_args['origin'] = $origin;
			}

			// WebView is in play, use the redirect_uri from the session.
			if ( JanrainCapture::get_option( 'janrain_capture_ui_web_view' ) ) {
				$redirect_uri = $_SESSION['janrain_capture_redirect_uri'];
			} else {
				$redirect_uri = admin_url( 'admin-ajax.php', '' ) . '?' . http_build_query( $redirect_args, '', '&' );
			}
			$api = new JanrainCaptureApi();
			if ( $api->new_access_token( $code, $redirect_uri ) ) {
				$user_entity = $api->load_user_entity();
				if ( is_array( $user_entity ) && $user_entity['stat'] == 'ok' ) {
					$user_entity = $user_entity['result'];
					do_action( self::$name . '_user_entity_loaded', $user_entity );
					// Lookup user based on returned uuid
					$blog_id = ( is_multisite() )
						? 1
						: $GLOBALS['blog_id'];
					#check for WP user existence
					$exists  = get_users( array( 'blog_id' => $blog_id, 'meta_key' => self::$name . '_uuid', 'meta_value' => $user_entity['uuid'] ) );
					if ( count( $exists ) < 1 ) {
						#no user found using capture ID, try checking by nickname
						$exists = get_users( array( 'blog_id' => $blog_id, 'meta_key' => 'nickname', 'meta_value' => $user_entity['uuid'] ) );
					}
					if ( count( $exists ) < 1 ) {
						#pretty sure no wordpress user for this capture user so lets make one!
						$user_attrs = array();
						$user_attrs['user_pass'] = wp_generate_password( $length = 12, $include_standard_special_chars = true );
						if ( self::get_option( self::$name . '_user_email' ) ) {
							$user_attrs['user_email'] = esc_sql( $this->get_field( self::get_option( self::$name . '_user_email' ), $user_entity ) );
						}
						if ( self::get_option( self::$name . '_user_login' ) ) {
							$user_attrs['user_login'] = esc_sql( $this->get_field( self::get_option( self::$name . '_user_login' ), $user_entity ) );
						}
						if ( self::get_option( self::$name . '_user_nicename' ) ) {
							$user_attrs['user_nicename'] = esc_sql( $this->get_field( self::get_option( self::$name . '_user_nicename' ), $user_entity ) );
						}
						if (self::get_option( self::$name . '_user_display_name' ) ) {
							$user_attrs['display_name'] = esc_sql( $this->get_field( self::get_option( self::$name . '_user_display_name' ), $user_entity ) );
						}
						$user_id = wp_insert_user( $user_attrs );
						if ( is_wp_error( $user_id ) ) {
							#failed to create the WP user from this Capture user
							throw new Exception( $user_id->get_error_message() );
						}
						#link new WP user to this Capture user
						if ( ! add_user_meta( $user_id, self::$name . '_uuid', $user_entity['uuid'], true ) ) {
							#failed to set user meta should never happen!
							throw new Exception( 'Janrain Capture: Failed to set uuid on new user' );
						}
						if ( ! $this->update_user_data( $user_id, $user_entity, true ) ) {
							throw new Exception( 'Janrain Capture: Failed to update user data' );
						}
						if ( is_multisite() ) {
							add_user_to_blog( 1, $user_id, 'subscriber' );
						}
						do_action( 'wp_login', $user->user_login, $user );
						do_action( 'janrain_capture_user_logged_in' , $user_id );
					} else {
						#a wordpress user exists for this capture user lets update the profile data!
						$user    = $exists[0];
						$user_id = $user->ID;
						do_action('wp_login', $user->user_login, $user);
						do_action( 'janrain_capture_user_logged_in' , $user_id );
						#do not update administrators
						if ( ! $this->update_user_data( $user_id, $user_entity ) ) {
							throw new Exception( 'Janrain Capture: Failed to update user data' );
						}
						if ( is_multisite() && ! is_user_member_of_blog( $user_id ) ) {
							add_user_to_blog( get_current_blog_id(), $user_id, 'subscriber' );
						}
					}
					if ( ! $api->update_user_meta( $user_id ) ) {
						throw new Exception( 'Janrain Capture: Failed to update user meta' );
					}
					if ( $api->password_recover === true ) {
						wp_redirect( add_query_arg( array( 'janrain_capture_action' => 'password_recover' ), home_url() ) );
					}
					#set a compact privacy policy header; otherwise IE will refuse to set cookies from within an iframe
					header('P3P: CP="NOI ADM DEV COM NAV OUR STP"');
					wp_set_auth_cookie( $user_id, false, '' );
				} else {
					throw new Exception( 'Janrain Capture: Could not retrieve user entity' );
				}
			} else {
				if ( WP_DEBUG ) {
					throw new Exception( 'Janrain Capture: Could not retrieve access_token' );
				}
			}
			if ( $origin != '' ) {
				$r = esc_url( $origin );
			} else {
				$r = 'window.top.location.href';
			}

			$ui_type = self::get_option( self::$name . '_ui_type' );
			echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Janrain Capture</title>
</head>
<body>
	<script type="text/javascript">
		if ('Capture' === '{$ui_type}') {
			if (localStorage) {
				localStorage.setItem("janrainCaptureTokenWP", '$api->access_token');
			}
			// User Registration Widget flow
			if ('window.top.location.href' == '$r') {
				// redirect to the originating page
				if (document.referrer) {
					// redirect to the document.referrer
					window.top.location = document.referrer;
				} else {
					// some IE browsers don't pass document.referrer so use history
					window.top.history.go(-1);
				}
			} else {
				 // redirect to page passed as origin querystring param
				window.top.location.href = '$r';
			}
		} else {
			// Legacy Capture UI flow
			if (window.top.CAPTURE && window.top.CAPTURE.closeAuth) {
				window.top.CAPTURE.closeAuth();
			} else {
				window.top.location.href = '$r';
			}
		}
	</script>
</body>
</html>
HTML;
			die();
		}

		/**
		 * Method used for the janrain_capture_profile action on admin-ajax.php.
		 * This method prints javascript to retreive the access_token from a cookie and
		 * render the profile screen if a valid access_token is found.
		 */
		function profile() {
			$current_user = wp_get_current_user();
			if ( ! $current_user->ID ) {
				echo 'Please login to view your profile.';
				return false;
			}
			$method   = isset($_REQUEST['method'])
				? $_REQUEST['method']
				: '';
			$callback = isset($_REQUEST['callback'])
				? $_REQUEST['callback']
				: 'CAPTURE.closeProfile';
			$expires  = get_user_meta( $current_user->ID, self::$name . '_expires', true );
			if ( $expires && time() >= $expires ) {
				$api = new JanrainCaptureApi();
				if ( $api->refresh_access_token() === false ) {
					throw new Exception( 'Janrain Capture: Could not refresh access_token' );
				}
				if ( ! $api->update_user_meta() ) {
					throw new Exception( 'Janrain Capture: Failed to update user meta' );
				}
			}
			$access_token = get_user_meta( $current_user->ID, self::$name . '_access_token', true );
			if ( ! $access_token ) {
				throw new Exception( 'Janrain Capture: No user access token found' );
			}
			$args = array(
				'redirect_uri' => admin_url( 'admin-ajax.php', '' ) . '?action=' . self::$name . '_redirect_uri',
				'client_id' => self::sanitize( self::get_option( self::$name . '_client_id' ) ),
				'xd_receiver' => admin_url( 'admin-ajax.php', '' ) . '?action=' . self::$name . '_xdcomm',
				'callback' => self::sanitize( $callback ),
				'access_token' => $access_token,
			);
			$capture_addr = self::get_option( self::$name . '_ui_address' )
				? self::get_option( self::$name . '_ui_address' )
				: self::get_option( self::$name . '_address' );
			$capture_addr = 'https://' . self::sanitize( $capture_addr ) . '/oauth/profile' . self::sanitize( $method ) . '?' . http_build_query( $args, '', '&' );
			header( "Location: $capture_addr", true, 302 );
			die();
		}

		/**
		 * Method used for the janrain_capture_profile action on admin-ajax.php.
		 * This method renders the edit profile screen.
		 */
		function widget_redir() {
			$r = self::get_option( self::$name . '_widget_edit_page' );
			echo <<<RDIR
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
	<head>
		<title>Janrain Capture</title>
	</head>
	<body>
		<script type="text/javascript">
				window.location.href = '$r';
		</script>
	</body>
</html>
RDIR;
		}

		/**
		 * Method used to write the output of the screen
		 * displays the forgot password and email verification screens
		 */
		function widget_show_screen( $url_type, $code ){
				ob_start();
				$this->ui->widget_js();
				$widget_js = ob_get_clean();

				$screen = $this->widget_get_screen( $url_type . '.html' );
				$js = $this->widget_get_screen( $url_type . '.js' );

				echo <<<SCREEN
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/1999/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
	<title>Janrain Capture</title>
	{$widget_js}
	<script type="text/javascript">
		{$js}
	</script>
</head>
<body>
	{$screen}
SCREEN;
		}

		/**
		 * Method used to render a file-based screen
		 * used for forgot (reset) password and email verify confirmation
		 */
		function widget_get_screen( $fname ) {
			$folder = self::get_option( self::$name . '_widget_screen_folder' );
			if ( ! isset($folder) || $folder  == '' ) {
				echo "Janrain Capture: No Widget screens folder specified in Janrain Capture Interface settings";
				return false;
			}
			$resp = wp_remote_get( $folder . $fname );
			return wp_remote_retrieve_body( $resp );
		}

		/**
		 * Method called by Janrain shortcode to render the edit profile screeen.
		 */
		function widget_profile() {
			$current_user = wp_get_current_user();
			if ( ! $current_user->ID ) {
				echo ( 'User not logged in' );
				echo $this->ui->sign_in_screen_js();
				echo $this->ui->sign_in_screen();
				return false;
			}
			$method = isset( $_REQUEST['method'] )
				? $_REQUEST['method']
				: '';
			$callback = isset( $_REQUEST['callback'] )
				? $_REQUEST['callback']
				: 'CAPTURE.closeProfile';
			$expires  = get_user_meta( $current_user->ID, self::$name . '_expires', true );

			if ( $expires && time() >= $expires ) {
				$api = new JanrainCaptureApi();
				if ( false === $api->refresh_access_token() ) {
					throw new Exception( 'Janrain Capture: Could not refresh access_token' );
				}
				if ( ! $api->update_user_meta() ) {
					throw new Exception( 'Janrain Capture: Failed to update user meta' );
				}
			}
			$access_token = get_user_meta( $current_user->ID, self::$name . '_access_token', true );
			echo "<script type='text/javascript'>if (localStorage) {localStorage.setItem('janrainCaptureTokenWP', '$access_token')};</script>\n";
			return $this->ui->edit_screen_js() . $this->ui->edit_screen();
		}

		/**
		 * Method used for the janrain_capture_profile_update action on admin-ajax.php.
		 * This method retrives a user record from Capture and updates the janrain_capture_user_attrs
		 * cookie accordingly.
		 */
		function profile_update() {
			$current_user = wp_get_current_user();
			if ( ! $current_user->ID ) {
				throw new Exception( 'Janrain Capture: Must be logged in to update profile' );
			}
			$user_id     = $current_user->ID;
			$api         = new JanrainCaptureApi();
			$user_entity = $api->load_user_entity();
			if ( is_array( $user_entity ) ) {
				if ( ! $api->update_user_meta( $user_id ) ) {
					throw new Exception( 'Janrain Capture: Failed to update user meta' );
				}
				$user_entity = $user_entity['result'];
				do_action( self::$name . '_user_entity_loaded', $user_entity );
				if ( ! $this->update_user_data( $user_id, $user_entity ) ) {
					throw new Exception( 'Janrain Capture: Failed to update user data' );
				}
			} else {
				throw new Exception( 'Janrain Capture: Could not retrieve user entity' );
			}
			echo '1';
			die();
		}

		/**
		 * Method used for updating user data with returned Capture user data
		 *
		 * ignores user 1 (superadmin) and also ignores users with admin privileges.
		 *
		 * @param int $user_id
		 *   The ID of the user to update
		 * @param array $user_entity
		 *   The user entity returned from Capture
		 * @return boolean
		 *   Success or failure
		 */
		function update_user_data( $user_id, $user_entity, $meta_only = false ) {
			#check for super user to skip updates.
			if ($user_id == 1 || user_can($user_id, 'manage_options')) {
				#disallow updates to the superuser.
				return true;
			}
			if ( ! $user_id || ! is_array( $user_entity ) ) {
				throw new Exception( 'Janrain Capture: Not a valid User ID or User Entity' );
			}
			$results = array();
			if ( $meta_only !== true ) {
				$user_attrs = array( 'ID' => $user_id );
				if ( self::get_option( self::$name . '_user_email' ) ) {
					$user_attrs['user_email'] = esc_sql( $this->get_field( self::get_option( self::$name . '_user_email' ), $user_entity  ) );
				}
				if ( self::get_option( self::$name . '_user_nicename' ) ) {
					$user_attrs['user_nicename'] = esc_sql( $this->get_field( self::get_option( self::$name . '_user_nicename' ), $user_entity ) );
				}
				if ( self::get_option( self::$name . '_user_display_name' ) ) {
					$user_attrs['display_name'] = esc_sql( $this->get_field( self::get_option( self::$name . '_user_display_name' ), $user_entity ) );
				}
				$userdata = wp_update_user( $user_attrs );
				$results[] = ! ( $userdata > 0 );
			}
			$metas = array( 'first_name', 'last_name', 'url', 'aim', 'yim', 'jabber', 'description' );
			foreach ( $metas as $meta ) {
				$key = self::get_option( self::$name . '_user_' . $meta );
				if ( ! empty( $key ) ) {
					$val = $this->get_field( $key, $user_entity );
					if ( ! empty( $val ) ) {
						$results[] = update_user_meta( $user_id, $meta, $val );
					}
				}
			}

			$result = ! array_search( false, $results );
			if ( $result ) {
				do_action( self::$name . '_user_updated', $user_id, $user_entity );
			}
			return $result;
		}

		/**
		 * Method used for retrieving a field value
		 *
		 * @param string $name
		 *   The name of the field to retrieve
		 * @param array $user_entity
		 *   The user entity returned from Capture
		 * @return string
		 *   Value retrieved from Capture
		 */
		function get_field( $name, $user_entity ) {
			if ( strpos( $name, '.' ) ) {
				$names = explode( '.', $name );
				$value = $user_entity;
				foreach ( $names as $n ) {
					$value = $value[$n];
				}
				return $value;
			} else {
				return $user_entity[$name];
			}
		}

		/**
		 * Method used for the janrain_capture_refresh_token action on admin-ajax.php.
		 * This method is an AJAX endpoint for issuing a request to refresh the Capture
		 * token set. The response is 1 for success and -1 on error.
		 */
		function refresh_token() {
			$api = new JanrainCaptureApi();
			echo ( $api->refresh_access_token() && $api->update_user_meta() )
				? '1'
				: '-1';
			die();
		}

		/**
		 * Method used for the janrain_capture_xdcomm action on admin-ajax.php.
		 * This method is rendered to allow for cross-domain communication between Capture
		 * iframes and the parent.
		 */
		function xdcomm() {
			echo <<<XDCOMM
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
	<head>
		<title>Cross-Domain Receiver Page</title>
	</head>
	<body>
		<script type="text/javascript">
			var rpxJsHost = (("https:" == document.location.protocol) ? "https://d1lqe9temigv1p.cloudfront.net/js/lib/xdcomm.js" : "http://static.janraincapture.com/js/lib/xdcomm.js");
			document.write(unescape("%3Cscript src='" + rpxJsHost + "' type='text/javascript'%3E%3C/script%3E"));
		</script>
	</body>
</html>
XDCOMM;
			die();
		}

		/**
		 * Implementation of the janrain_capture shortcode.
		 *
		 * @param string $args
		 *   Arguments appended to the shortcode
		 *
		 * @return string
		 *   Text or HTML to render in place of the shortcode
		 */
		function shortcode( $args ) {
			extract(
					shortcode_atts(
						array(
							'type' => 'modal',
							'text' => 'Sign in / Register',
							'action' => 'signin',
							'height' => '400',
							'width' => '700',
							'href_only' => 'false',
							'callback' => 'CAPTURE.closeProfile',
						),
						$args
					));
			$class = 'capture-anon';
			$capture_addr = self::get_option( self::$name . '_ui_address' )
				? self::get_option( self::$name . '_ui_address' )
				: self::get_option( self::$name . '_address' );
			if ( strpos( $action, 'profile' ) === 0 ) {
				$uargs = array( 'action' => self::$name . '_profile', 'callback' => self::sanitize( $callback ) );
				if ( strlen( $action ) > 7 ) {
					$method = substr( $action, 7 );
					$uargs['method'] = self::sanitize( $method );
				}
				$link  = admin_url( 'admin-ajax.php', '' ) . '?' . http_build_query( $uargs, '', '&' );
				$class = 'capture-auth';
			} else {
				if ( strpos( $action, 'signin' ) === 0 ) {
					$action .= JanrainCapture::get_option( JanrainCapture::$name . '_signin_ext' );
				} elseif ( strpos( $action, 'edit_profile' ) === 0 ) {
					$display = $this->widget_profile();
					return $display;
				}
				$link = 'https://' . $capture_addr . '/oauth/' . $action;
				$args = array(
					'response_type' => 'code',
					'redirect_uri'  => admin_url( 'admin-ajax.php', '' ) . '?action=' . self::$name . '_redirect_uri',
					'client_id'     => self::get_option( self::$name . '_client_id' ),
					'xd_receiver'   => admin_url( 'admin-ajax.php', '' ) . '?action=' . self::$name . '_xdcomm',
					'recover_password_callback' => 'CAPTURE.closeRecoverPassword',
				);
				$link = $link . '?' . http_build_query( $args, '', '&' );
				if ( JanrainCapture::get_option( JanrainCapture::$name . '_ui_type' ) == 'Capture' ) {
					if ( ! is_user_logged_in() ) {
						$link = '<a href="javascript:janrain.capture.ui.modal.open();" >' . $text . '</a>';
					} else {
						$link = '<a href="' . wp_logout_url( JanrainCaptureUi::current_page_url() ) . '" onclick="janrain.capture.ui.endCaptureSession();" >Log out</a>';
					}
					return $link;
				}
			}
			if ( $href_only == 'true' ) {
				return esc_url( $link );
			}
			if ( $type == 'inline' ) {
				$iframe = '<iframe src="' . esc_url( $link ) . '" style="width:' . (int) $width . 'px;height:'
					. (int) $height . 'px;" class="' . self::$name . '_iframe ' . $class . ' ' . self::$name . '_' . self::sanitize( $action ) . '"></iframe>';
				return $iframe;
			} else {
				$anchor = '<a href="' . esc_url( $link ) . '" rel="width:' . (int) $width . 'px;height:' . (int) $height . 'px;" class="'
					. self::$name . '_anchor modal-link ' . $class . ' ' . self::$name . '_' . self::sanitize( $action ) . '">' . $text . '</a>';
				return $anchor;
			}
		}

		/**
		 * Implementation of the janrain_share shortcode.
		 *
		 * @param string $args
		 *   Arguments appended to the shortcode
		 *
		 * @return string
		 *   Text or HTML to render in place of the shortcode
		 */
		function shortcode_share( $args ) {
			if ( self::share_enabled() ) {
				global $post;
				$image = '';
				if ( $post->ID ) {
					$images = get_children(
						array(
							'post_parent' => get_the_ID(),
							'post_status' => 'inherit',
							'post_type' => 'attachment',
							'post_mime_type' => 'image',
							'order' => 'DESC',
							'orderby' => 'menu_order ID'
						) );
					$first_image = array_shift( $images );
					$image = isset( $first_image->ID )
						? wp_get_attachment_url( $first_image->ID )
						: '';
				}
				$atts = array(
					'url'         => ($post->ID ? get_permalink() : ''),
					'title'       => ($post->ID ? get_the_title() : ''),
					'description' => ($post->ID ? get_the_excerpt() : ''),
					'img'         => $image,
					'text'        => 'Share',
				);
				extract( shortcode_atts( $atts, $args ) );
				$link        = '';
				$url         = esc_js( esc_url( $url ) );
				$description = esc_js( $description );
				$title       = esc_js( $title );
				$img         = esc_js( esc_url( $img ) );
				$text        = $text;
				$onclick     = "setShare('$url', '$title', '$description', '$img', this.getAttribute('rel'))";
				if ( $icons  = self::social_icons( $onclick ) ) {
					$link .= '<div class="janrain-share-container"><span class="janrain-share-text">' . $text . '</span>'.$icons.'</div>';
				}
				return $link;
			} else {
				return '';
			}
		}

		/**
		 * Sanitization method to remove special chars
		 *
		 * @param string $s
		 *   String to be sanitized
		 *
		 * @return string
		 *   Sanitized string
		 */
		static function sanitize( $s ) {
			return preg_replace( '/[^a-z0-9\._-]+/i', '', $s );
		}

		/**
		 * Returns the main site or network option if using multisite
		 *
		 * @param string $key
		 *   The option key to retrieve
		 * @param mixed $default
		 *   The default value to use
		 * @param boolean $always_main
		 *   Always use the main blog
		 *
		 * @return string
		 *   The saved option or default value
		 */
		static function get_option( $key, $default = false, $always_main = false ) {
			$value = get_option( $key );
			if ( is_multisite() ) {
				if ( $always_main ) {
					$value = get_blog_option( 1, $key, $default );
				} else {
					$value = ($value !== false)
						? $value
						: get_blog_option( 1, $key, $default );
				}
			}
			return $value;
		}

		/**
		 * Updates the main site or network option if using multisite
		 *
		 * @param string $key
		 *   The option key to update
		 * @param mixed $value
		 *   The value to store in options
		 * @param boolean $always_main
		 *   Always use the main blog
		 *
		 * @return boolean
		 *   True if option value changed, false if not or if failed
		 */
		static function update_option( $key, $value, $always_main = false ) {
			if ( is_string( $value ) ) {
				$value = stripslashes( $value );
			}
			if ($always_main) {
				return update_blog_option( 1, $key, $value );
			} else {
				return update_option( $key, $value );
			}
		}

		/**
		 * Retrieves the plugin version.
		 *
		 * @return string
		 *   String version
		 */
		static function get_plugin_version() {
			$default_headers = array( 'Version' => 'Version' );
			$plugin_data = get_file_data( __FILE__, $default_headers, 'plugin' );
			return $plugin_data['Version'];
		}

		/**
		 * Retrieves the plugin version.
		 *
		 * @return string
		 *   String version
		 */
		static function share_enabled() {
			$enabled = self::get_option( self::$name . '_ui_share_enabled' );
			if ($enabled == '0') {
				return false;
			}
			return self::get_option( self::$name . '_rpx_share_providers' );
		}

		static function social_icons( $onclick ) {
			$social_providers = self::get_option( self::$name . '_rpx_share_providers' );
			if ( is_array( $social_providers ) ) {
				$rpx_social_icons = '';

				foreach ( $social_providers as $val ) {
					$rpx_social_icons .= '<span class="janrain-provider-icon-16 janrain-provider-icon-'
						. $val . '" rel="' . $val . '" onclick="' . $onclick . '"></span>';
				}
				$buttons = '<span class="rpx_social_icons">' . $rpx_social_icons . '</span>';
				return $buttons;
			}
			return false;
		}
	}
}

add_action('init', 'janrain_capture_init_wrap');
function janrain_capture_init_wrap() {
	global $capture;
	$capture = new JanrainCapture();
	$capture->init();
}
