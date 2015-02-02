<?php
/**
 * @package Janrain Capture
 *
 * Methods for inserting UI elements
 *
 */
class JanrainCaptureUi {

	var $ifolder;
	var $local;
	var $ui_type;

	/**
	 * Sets up actions, initializes plugin name.
	 *
	 * @param string $name
	 *   The plugin name to use as a namespace
	 */
	function __construct() {
		$this->ui_type = JanrainCapture::get_option( JanrainCapture::$name . '_ui_type' );
			if ( ! is_admin() ) {
				add_action( 'wp_head', array( $this, 'head' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
				if ( JanrainCapture::get_option( JanrainCapture::$name . '_ui_native_links' ) != '0' ) {
					add_filter( 'loginout', array( $this, 'loginout' ) );
					add_filter( 'logout_url', array( $this, 'logout_url' ), 10, 2 );
					add_filter( 'admin_url', array( $this, 'admin_url' ), 10, 3 );
				}
				if ( $this->ui_type == 'Capture' ) {
					if ( JanrainCapture::get_option( JanrainCapture::$name . '_widget_bp_enabled' ) > 0 ) {
						add_action( 'wp_enqueue_scripts', array( $this, 'backplane_head' ) );
						add_action( 'wp_footer', array( $this, 'backplane_js' ) );
					}
					// if not on edit page page write signin screen and js
					if ( strstr($this->current_page_url(), JanrainCapture::get_option( JanrainCapture::$name . '_widget_edit_page' )) === false ){
						add_action( 'wp_head', array( $this, 'sign_in_screen_js' ) );
						add_action( 'wp_footer', array( $this, 'sign_in_screen' ) );
					}
				}
		}
		$this->ifolder = JanrainCapture::get_option( JanrainCapture::$name . '_widget_screen_folder' );

		#validate ifolder
		$validUrl = filter_var( $this->ifolder, FILTER_VALIDATE_URL );
		if ( is_admin() && ( ! $validUrl ) ) {
			#url is not valid, and we're looking at the admin screen.
			#print message about error
			add_action( 'admin_notices', array( $this, 'invalidScreensNotice' ) );
			#set vars
			$this->local = true;
			return;
		}

		// check to see if this is a local url and fix path
		if ( stristr( $this->ifolder, site_url() ) ) {
			# screens url contains site URL, look for files locally
			$this->ifolder = ABSPATH . str_replace( site_url() . '/', '', $this->ifolder );
			$this->local = true;
			// Check to make sure screens exist, and display an admin error if they do not
			if ( is_admin() ) {
				$signin_path = trailingslashit( $this->ifolder ) . 'signin.html';
				if ( ! file_exists( $signin_path ) ) {
					add_action( 'admin_notices', array( $this, 'screens_missing_notice' ) );
				}
			}
		} else {
			$this->local = false;
		}
	}

	/**
	 * Admin notice when the screens url is invalid.
	 */
	public function invalidScreensNotice() {
		echo
			"<div class='error'>"
				. "<p>" . __( 'Janrain Capture: Invalid Screens URL.', 'janrain-capture' ) . "</p>"
			."</div>";
	}

	/**
	 * Admin notice hook to be displayed when screens are not found
	 */
	public function screens_missing_notice() {
		echo
			'<div class="error">'
				. '<p>'
				. __( 'Janrain Capture: Could not locate screens. Please make sure your screens folder is in the correct location.', 'janrain-capture' )
				. '</p>'
			.'</div>';
	}

	/**
	 * Adds javascript libraries to the page.
	 */
	function register_scripts() {
		$captureName = JanrainCapture::$name;
		$pluginDirUrl = untrailingslashit( plugin_dir_url( __FILE__ ) );
		wp_enqueue_script( 'json2' );
		wp_enqueue_script( 'localstorage-polyfill',  "$pluginDirUrl/localstorage-polyfill.js" );

		if ( JanrainCapture::share_enabled() ) {
			wp_enqueue_style( 'janrain_share', "{$pluginDirUrl}/stylesheet.css" );
			add_action( 'wp_footer', array( $this, 'share_js' ) );
		}

		if ( $this->ui_type == 'Capture Legacy' ) {
			if ( JanrainCapture::get_option( "{$captureName}_ui_colorbox" ) != '0' ) {
				wp_enqueue_script( 'colorbox', "{$pluginDirUrl}/colorbox/jquery.colorbox.js", array( 'jquery' ) );
				wp_enqueue_style( 'colorbox', "{$pluginDirUrl}/colorbox/colorbox.css" );
			}
			if ( JanrainCapture::get_option( JanrainCapture::$name . '_ui_capture_js' ) != '0' ) {
				wp_enqueue_script( JanrainCapture::$name . '_main_script', "{$pluginDirUrl}/janrain-capture-ui.js" );
			}
		}
	}

	/**
	 * Method bound to the wp_head action.
	 */
	function head() {
		switch ( $this->ui_type ) {
			case 'Capture Legacy':
				$this->captureui_js();
				break;
			case 'Capture':
				$this->widget_js();
				break;
			default:
				// dont load any UI
				break;
		}
	}

	/**
	 * Method bound to the loginout filter.
	 *
	 * @param string $link
	 *   The Login/Logout link html string.
	 *
	 * @return string $link
	 *   The html to output to the page.
	 */
	function loginout( $link ) {
		if ( $this->ui_type == 'Capture Legacy' ) {
			if ( ! is_user_logged_in() ) {
				$href = do_shortcode( '[' . JanrainCapture::$name . ' href_only="true"]' );
				$classes = JanrainCapture::$name . '_anchor ' . JanrainCapture::$name . '_signin';
				if ( strpos( $link, ' class=' ) ) {
					$link = preg_replace( "/(\sclass=[\"'][^\"']+)([\"'])/i", "$1 $classes$2", $link );
				} else {
					$link = str_replace( 'href=', "class=\"$classes\" href=", $link );
				}
				$link = preg_replace( "/(href=[\"'])[^\"']+([\"'])/i", "$1$href$2", $link );
			} else {
				$sso_addr = JanrainCapture::get_option( JanrainCapture::$name . '_sso_address' );
				$sso_enabled = JanrainCapture::get_option( JanrainCapture::$name . '_sso_enabled' );
				if ( $sso_enabled && $sso_addr ) {
					//TODO: shorthand function
					$logout = wp_logout_url( self::current_page_url() );
					$href = "javascript:JANRAIN.SSO.CAPTURE.logout({ sso_server: 'https://$sso_addr', logout_uri: '$logout' });";
				} else {
					$href = wp_logout_url( self::current_page_url() );
				}
				$link = preg_replace( "/href=[\"'][^\"']+[\"']/i", "href=\"$href\"", $link );
			}
		} elseif ( $this->ui_type == 'Capture' ) {
			if ( ! is_user_logged_in() ) {
				// change this to add a class to the element passed as $link
				// open class is 'capture_modal_open'
				$href = 'javascript:janrain.capture.ui.modal.open();';
			} else {
				$href = wp_logout_url( self::current_page_url() );
				// urlencode(wp_make_link_relative(get_option('siteurl')));
			}
			$link = preg_replace( "/href=[\"'][^\"']+[\"']/i", "href=\"$href\"", $link );
			$link = str_ireplace( '>', ' onclick="janrain.capture.ui.endCaptureSession();" >', $link );
			// change above to add class 'capture_end_session'
		}
		return $link;
	}

	/**
	 * Method bound to the logout_url filter.
	 *
	 * @param string $logout_url
	 *   The logout url as generated by the wp_logout_url method.
	 *
	 * @param string $redirect
	 *   The redirect string passed in to the function.
	 *
	 * @return string $logout_url
	 *   The modified logout URL.
	 */
	function logout_url( $logout_url, $redirect ) {
		if ( empty( $redirect ) ) {
			$logout_url = wp_logout_url( self::current_page_url() );
		}
		$url = str_replace( '&amp;', '&', $logout_url );
		return $url;
	}

	/**
	 * Method bound to the admin_url filter.
	 *
	 * @param string $url
	 *   The URL generated by the admin_url method.
	 *
	 * @param string $path
	 *   The path passed in to the admin_url method.
	 *
	 * @param int $blog_id
	 *   The ID of the current blog.
	 *
	 * @return string $link
	 *   The html to output to the page.
	 */
	function admin_url( $url, $path, $blog_id ) {
		$current_user = wp_get_current_user();
		if ( $path == 'profile.php' && $current_user->ID ) {
			return admin_url( 'admin-ajax.php', '' ) . '?action=' . JanrainCapture::$name . '_profile';
		} else {
			return $url;
		}
	}

	/**
	 * Returns the current page URL
	 *
	 * @return string
	 *   Page URL
	 */
	static function current_page_url() {
		$pageURL = 'http';
		if ( isset( $_SERVER['HTTPS'] ) ) {
			if ( $_SERVER['HTTPS'] == 'on' ) {
				$pageURL .= 's';
			}
		}
		$pageURL .= '://';
		if ( $_SERVER['SERVER_PORT'] != '80' ) {
			$pageURL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		} else {
			$pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}
		return $pageURL;
	}

	/**
	 * Outputs Engage Share widget js to the footer.
	 */
	function share_js() {
		if ( $this->ui_type == 'Capture Legacy' ) {
			$realm        = JanrainCapture::get_option( JanrainCapture::$name . '_widget_engage_url' );
			$realm        = str_ireplace( 'https://', '', $realm );
			$realm 	      = str_ireplace( 'http://', '', $realm );
			$realm        = str_ireplace( '.rpxnow.com', '', $realm );
			$legacy_share = <<<LSHARE

	function isReady() { janrain.ready = true; };
	if (document.addEventListener) {    document.addEventListener("DOMContentLoaded", isReady, false);
	} else {
		window.attachEvent('onload', isReady);
	}

	var e = document.createElement('script');
	e.type = 'text/javascript';
	e.id = 'janrainWidgets';

	if (document.location.protocol === 'https:') {
		e.src = 'https://rpxnow.com/load/$realm';
	} else {
		e.src = 'http://widget-cdn.rpxnow.com/load/$realm';
	}

	var s = document.getElementsByTagName('script')[0];
	s.parentNode.insertBefore(e, s);
LSHARE;
		} else {
			$legacy_share = '';
		}

		$providers = JanrainCapture::get_option( JanrainCapture::$name . '_rpx_share_providers' );
		$providers = implode( "', '", $providers );
		echo <<<SHARE
<style>#janrain-share { z-index: 99999 !important; }</style>
<script type="text/javascript">
(function() {
	if (typeof window.janrain !== 'object') window.janrain = {};
	if (typeof window.janrain.settings !== 'object') window.janrain.settings = {};
	if (typeof window.janrain.settings.share !== 'object') window.janrain.settings.share = {};
	if (typeof window.janrain.settings.packages !== 'object') janrain.settings.packages = ['share'];
	else janrain.settings.packages.push('share');

	janrain.settings.share.message = '';
	janrain.settings.share.providers = ['$providers'];
$legacy_share
})();
function setShare(url, title, desc, img, provider) {
	janrain.engage.share.setUrl(url);
	janrain.engage.share.setTitle(title);
	janrain.engage.share.setMessage(title);
	janrain.engage.share.setDescription(desc);
	janrain.engage.share.setImage(img);
	janrain.engage.share.showProvider(provider);
	janrain.engage.share.show();
}
</script>
SHARE;
	}

	/**
	 * Outputs CaptureUI js.
	 */
	function captureui_js() {

		$bp_js_path         = JanrainCapture::get_option( JanrainCapture::$name . '_bp_js_path' );
		$bp_server_base_url = JanrainCapture::get_option( JanrainCapture::$name . '_bp_server_base_url' );
		$bp_bus_name        = JanrainCapture::get_option( JanrainCapture::$name . '_bp_bus_name' );
		$sso_addr           = JanrainCapture::get_option( JanrainCapture::$name . '_sso_address' );
		$sso_enabled        = JanrainCapture::get_option( JanrainCapture::$name . '_sso_enabled' );
		$bp_enabled         = JanrainCapture::get_option( JanrainCapture::$name . '_backplane_enabled' );
		$capture_addr = JanrainCapture::get_option( JanrainCapture::$name . '_ui_address' )
			? JanrainCapture::get_option( JanrainCapture::$name . '_ui_address' )
			: JanrainCapture::get_option( JanrainCapture::$name . '_address' );
		echo '<script type="text/javascript" src="' . plugins_url('capture_client.js', __FILE__) . '"></script>';
		if ( isset($_GET['janrain_capture_action']) &&  $_GET['janrain_capture_action'] == 'password_recover' ) {
			$query_args = array( 'action' => JanrainCapture::$name . '_profile' );
			if ( $screen = JanrainCapture::get_option( JanrainCapture::$name . '_recover_password_screen' ) ) {
				$method = preg_replace( '/^profile/', '', $screen );
				$query_args['method'] = $method;
			}
			$recover_password_url = add_query_arg( $query_args, admin_url( 'admin-ajax.php', '' ) );
			echo <<<RECOVER
				<script type="text/javascript">
					jQuery(function(){
						jQuery.colorbox({
							href: '$recover_password_url',
							iframe: true,
							width: 700,
							height: 700,
							scrolling: false,
							overlayClose: false,
							current: '',
							next: '',
							previous: ''
						});
					});
					function janrain_capture_on_profile_update() {
						document.location.href = document.location.href.replace(/[\?\&]janrain_capture_action\=password_recover/, '');
					}
				</script>
RECOVER;
		}
		if ($bp_enabled && $bp_js_path) {
			echo '<script type="text/javascript" src="' . esc_url( $bp_js_path ) . '"></script>';
		}
		if ( $bp_enabled && $bp_server_base_url && $bp_bus_name ) {
			$bp_server_base_url = esc_url( $bp_server_base_url );
			$bp_bus_name = JanrainCapture::sanitize( $bp_bus_name );
			echo <<<BACKPLANE
<script type="text/javascript">
jQuery(function(){
	Backplane(CAPTURE.bp_ready);
		Backplane.init({
			serverBaseURL: "$bp_server_base_url",
			busName: "$bp_bus_name"
		});
});
</script>
BACKPLANE;
		}
		if ( $sso_enabled && $sso_addr ) {
			$client_id    = JanrainCapture::get_option( JanrainCapture::$name . '_client_id' );
			$client_id    = JanrainCapture::sanitize( $client_id );
			$xdcomm       = admin_url( 'admin-ajax.php', '' ) . '?action=' . JanrainCapture::$name . '_xdcomm';
			$redirect_uri = admin_url( 'admin-ajax.php', '' ) . '?action=' . JanrainCapture::$name . '_redirect_uri';
			$logout       = wp_logout_url( '/' );
			$sso_addr     = esc_url( 'https://' . $sso_addr );
			echo <<<SSOA
<script type="text/javascript" src="$sso_addr/sso.js"></script>
<script type="text/javascript">
var sso_login_obj = {
	sso_server: "$sso_addr",
	client_id: "$client_id",
	redirect_uri: "$redirect_uri",
	logout_uri: "$logout",
	xd_receiver: "$xdcomm",
	bp_channel: ""
};
</script>
SSOA;
			if ( ! $bp_enabled ) {
			echo <<<SSO
console.log(sso_login_obj);
<script type="text/javascript">
JANRAIN.SSO.CAPTURE.check_login(sso_login_obj);
function janrain_capture_logout() {
	JANRAIN.SSO.CAPTURE.logout({
		sso_server: "$sso_addr",
		logout_uri: "$logout"
	});
}
</script>
SSO;
			}
		}
		echo '<script type="text/javascript">
			if (typeof(ajaxurl) == "undefined") var ajaxurl = "' . admin_url( 'admin-ajax.php', '' ) . '";
</script>';
	}

	/**
	 * Outputs Capture widget js.
	 */
	function widget_js() {

		$folder = JanrainCapture::get_option( JanrainCapture::$name . '_widget_screen_folder' );

		// capture
		$settings['capture.redirectUri']   = admin_url( 'admin-ajax.php', '' ) . '?action=' . JanrainCapture::$name . '_redirect_uri';
		$settings['capture.appId']         = JanrainCapture::get_option( JanrainCapture::$name . '_widget_app_id' );
		$settings['capture.clientId']      = JanrainCapture::get_option( JanrainCapture::$name . '_widget_client_id' );
		$settings['capture.captureServer'] = JanrainCapture::get_option( JanrainCapture::$name . '_widget_address' );
		$settings['capture.packages']      = JanrainCapture::get_option( JanrainCapture::$name . '_widget_packages' );
		$janrain_packages = implode( "','", $settings['capture.packages'] );
		$settings['capture.loadJsUrl']          = JanrainCapture::get_option( JanrainCapture::$name . '_widget_load_js' );

		// engage
		$settings['appUrl'] = JanrainCapture::get_option( JanrainCapture::$name . '_widget_engage_url' );

		// federate
		$settings['capture.federate']           = JanrainCapture::get_option( JanrainCapture::$name . '_widget_sso_enabled' );
		$settings['capture.federateServer']     = JanrainCapture::get_option( JanrainCapture::$name . '_widget_sso_address' );
		$settings['capture.federateXdReceiver'] = JanrainCapture::get_option( JanrainCapture::$name . '_widget_so_xd' );
		$settings['capture.federateLogoutUri']  = JanrainCapture::get_option( JanrainCapture::$name . '_widget_sso_logout' );

		// backplane
		$settings['capture.backplane']        = JanrainCapture::get_option( JanrainCapture::$name . '_widget_backplane_enabled' );
		$settings['capture.backplaneServerBaseUrl'] = JanrainCapture::get_option( JanrainCapture::$name . '_widget_bp_server_base_url' );
		$settings['capture.backplaneBusName'] = JanrainCapture::get_option( JanrainCapture::$name . '_widget_bp_bus_name' );
		$settings['capture.backplaneVersion'] = JanrainCapture::get_option( JanrainCapture::$name . '_bp_version' );

		// mobile
		$settings['mobileWebView'] = JanrainCapture::get_option( JanrainCapture::$name . '_ui_web_view' );

		if ( $this->local ) {
			// check the stylesheets folder for css files
			try {
				$dir = new DirectoryIterator( $this->ifolder . '/stylesheets' );
			} catch (Exception $e) {
				if ( true === WP_DEBUG ) {
					error_log( 'janrain-capture: Could not locate screens in ' . $this->ifolder );
				}
				return false;
			}

			$settings['capture.stylesheets'] = '';
			foreach ( $dir as $fileinfo ) {
				$fn = $fileinfo->getFilename();
				if ( ! $fileinfo->isDot() && stripos( $fn, '.css' ) ) {
					switch ( true ){
						// if file begins with mobile set is as a mobile style
						case stripos( $fn, 'mobile' ) === 0:
							$settings['capture.mobileStylesheets'] .= "'" . $folder . "stylesheets/$fn',";
							break;
						// if file begins with ie set is as an IE style
						case stripos( $fn, 'ie' ) === 0:
							$settings['capture.conditionalIEStylesheets'] .= "'" . $folder . "stylesheets/$fn',";
							break;
						// otherwise set it as a normal style
						default:
							$settings['capture.stylesheets'] .= "'" . $folder . "stylesheets/$fn',";
							break;
					}
				}
			}
		} elseif ( $url = JanrainCapture::get_option( 'janrain_capture_widget_css_file' ) ) {
			//old installs may have been able to set this option manually.
			$settings['capture.stylesheets'] = "'{$url}'";
		} else {
			//default
			$settings['capture.stylesheets'] = "'{$this->ifolder}/stylesheets/janrain.css'";
		}
		// Convert locale to RFC-5646
		$locale = esc_js(str_replace('_', '-', get_locale()));
		echo <<<WIDGETCAPTURE
<script type="text/javascript">
function janrainSignOut(){
			janrain.capture.ui.endCaptureSession();
}
(function() {
		if (typeof window.janrain !== 'object') window.janrain = {};
		window.janrain.settings = {};
		window.janrain.settings.capture = {};

		// capture settings
		janrain.settings.capture.language = '$locale';
		janrain.settings.capture.redirectUri = '{$settings["capture.redirectUri"]}';
		janrain.settings.capture.appId= '{$settings["capture.appId"]}';
		janrain.settings.capture.clientId = '{$settings["capture.clientId"]}';
		janrain.settings.capture.responseType = 'code';
		janrain.settings.capture.captureServer = '{$settings["capture.captureServer"]}';
		janrain.settings.capture.registerFlow = 'socialRegistration';
		janrain.settings.packages = ['$janrain_packages'];

		janrain.settings.capture.setProfileCookie = true;
		janrain.settings.capture.keepProfileCookieAfterLogout = true;
		janrain.settings.capture.setProfileData = true;

		// styles
		janrain.settings.capture.stylesheets = [{$settings["capture.stylesheets"]}];
WIDGETCAPTURE;

		//mobile styles
		if ( isset( $settings['capture.mobileStylesheets'] ) ) {
			echo "\njanrain.settings.capture.mobileStylesheets = '{$settings['capture.mobileStylesheets']}';";
		}

		//IE styles
		if ( isset( $settings['capture.conditionalIEStylesheets'] ) ) {
			echo "\njanrain.settings.capture.conditionalIEStylesheets = '{$settings['capture.conditionalIEStylesheets']}';";
		}

		echo "\njanrain.settings.capture.recaptchaPublicKey = '6LeVKb4SAAAAAGv-hg5i6gtiOV4XrLuCDsJOnYoP'; //captcha";

		if ( in_array( 'login', $settings['capture.packages'] ) ) {
			// convert locale to RFC-5646
			$locale = esc_js(str_replace('_', '-', get_locale()));
			?>
			// engage settings
			janrain.settings.language = '<?php echo $locale;?>';
			janrain.settings.appUrl = '<?php echo $settings['appUrl'] ?>';
			janrain.settings.tokenAction = 'event';
			<?php
		}

		if ( $settings['capture.backplane'] ) {
			?>
			// backplane settings
			janrain.settings.capture.backplane = '<?php echo $settings['capture.backplane'] ?>';
			janrain.settings.capture.backplaneBusName = '<?php echo $settings['capture.backplaneBusName'] ?>';
			janrain.settings.capture.backplaneVersion = '<?php echo $settings['capture.backplaneVersion']?>';
			<?php
		}

		if (isset($settings['capture.backplaneServerBaseUrl']) && $settings['capture.backplaneServerBaseUrl'] != '') {
			?>
			janrain.settings.capture.backplaneServerBaseUrl = 'https://<?php echo $settings['capture.backplaneServerBaseUrl']?>';
			<?php
		}

		if ( $settings['capture.federate'] ) {
			$logoutUrl = $settings['capture.federateLogoutUri'] ?: wp_logout_url() . '&_janrainsso=1';
			?>
			// federate settings
			janrain.settings.capture.federate = '<?php echo $settings['capture.federate'] ?>';
			janrain.settings.capture.federateServer = '<?php echo $settings['capture.federateServer'] ?>';
			janrain.settings.capture.federateXdReceiver = '<?php echo $settings['capture.federateXdReceiver'] ?>';
			janrain.settings.capture.federateLogoutUri = '<?php echo $logoutUrl;?>';
			<?php
		}

		if ( $settings['mobileWebView'] ) {

			$_SESSION['janrain_capture_redirect_uri'] = $this->current_page_url();

			echo "
			// mobile-specific settings
			janrain.settings.tokenAction = 'url';
			janrain.settings.popup = false;
			janrain.settings.tokenUrl = janrain.settings.capture.captureServer;
			janrain.settings.capture.redirectFlow = true;
			janrain.settings.capture.redirectUri = '{$_SESSION['janrain_capture_redirect_uri']}';\n";
		} else {
			if ( isset( $_SESSION['janrain_capture_redirect_uri'] ) ) {
				unset( $_SESSION['janrain_capture_redirect_uri'] );
			}
		}

		echo <<<WIDGETFINISH
		function isReady() { janrain.ready = true; };
		if (document.addEventListener) {
				document.addEventListener("DOMContentLoaded", isReady, false);
		} else {
				window.attachEvent('onload', isReady);
		}

		var e = document.createElement('script');
		e.type = 'text/javascript';
		e.id = 'janrainAuthWidget'
		var url = document.location.protocol === 'https:' ? 'https://' : 'http://';
		url += '{$settings["capture.loadJsUrl"]}';
		e.src = url;
		var s = document.getElementsByTagName('script')[0];
		s.parentNode.insertBefore(e, s);
})();
		</script>
WIDGETFINISH;
	}


	/**
	 * Outputs backplane.js include file
	 */
	function backplane_head() {
		if ( JanrainCapture::get_option( JanrainCapture::$name . '_bp_version', 1.2 ) != 2 ) {
			wp_register_script( 'backplane', 'http://d134l0cdryxgwa.cloudfront.net/backplane.js' );
		} else {
			wp_register_script( 'backplane', 'http://d134l0cdryxgwa.cloudfront.net/backplane2.js' );
		}
		wp_enqueue_script( 'backplane' );
	}

	/**
	 * Outputs backplane setttings js block
	 */
	function backplane_js() {
		$bus = JanrainCapture::get_option( JanrainCapture::$name . '_bp_bus_name' );
		$ver = JanrainCapture::get_option( JanrainCapture::$name . '_bp_version', 1.2 );
		if ( $ver == 1.2 ) {
			echo <<<BACKPLANE2
<script type="text/javascript">
function setup_bp() {
	/*
	 * Initialize Backplane:
	 * This creates a channel and adds a cookie for the channel.
	 * It also sets the function to call when this is complete.
	 */
	Backplane(bp_ready);
	Backplane.init({
		serverBaseURL: "http://backplane1.janrainbackplane.com/v$ver",
		busName: "$bus"
	});
}

function bp_ready() {
	/*
	 * This function is called when Backplane.init is complete.
	 */
	if (Backplane.getChannelID() != undefined) {
		// backplane loaded
		//console.log(Backplane.getChannelID());
		return false;
	}
}
setup_bp();
</script>
BACKPLANE2;
		}
	}

	function sign_in_screen_js() {
		$url = $this->ifolder . '/';
		$file = JanrainCapture::get_option( JanrainCapture::$name . '_widget_auth_screen' );
		$url .= preg_replace('"\.(php|html|htm)$"', '.js', $file);
		echo '<script type="text/javascript">';
		if ( $this->local ) {
			if ( file_exists( $url ) ) {
				include_once $url;
			} else {
				if ( true === WP_DEBUG ) {
					error_log( 'janrain-capture: Could not find screen file at path ' . $url );
				}
			}
		} else {
			$resp = wp_remote_get( $url );
			$out = wp_remote_retrieve_body( $resp );
			echo $out ?: sprintf( 'Janrain: Unable to load %s', $url );
		}
		echo '</script>';
	}

	function sign_in_screen() {
		$url  = $this->ifolder . '/';
		$url .= JanrainCapture::get_option( JanrainCapture::$name . '_widget_auth_screen' );
		if ( $this->local ) {
			if ( file_exists( $url ) ) {
				include_once $url;
			} else {
				if ( true === WP_DEBUG ) {
					error_log( 'janrain-capture: Could not find screen file at path ' . $url );
				}
			}
		} else {
			$resp = wp_remote_get( $url );
			$out = wp_remote_retrieve_body( $resp );
			echo $out ?: sprintf( 'Janrain: Unable to load %s', $url );
		}
	}

	function edit_screen() {
		$url  = $this->ifolder . '/';
		$url .= JanrainCapture::get_option( JanrainCapture::$name . '_widget_edit_screen' );
		if ( $this->local ) {
			if ( file_exists( $url ) ) {
				include_once $url;
			} else {
				if ( true === WP_DEBUG ) {
					error_log( 'janrain-capture: Could not find screen file at path ' . $url );
				}
			}
		} else {
			$resp = wp_remote_get( $url );
			$out = wp_remote_retrieve_body( $resp );
			echo $out ?: sprintf( 'Janrain: Unable to load %s', $url );
		}
	}

	function edit_screen_js() {
		$url = $this->ifolder . '/';
		$file = JanrainCapture::get_option( JanrainCapture::$name . '_widget_edit_screen' );
		$url .= preg_replace( '"\.(php|html|htm)$"', '.js', $file );
		echo '<script type="text/javascript">';
		if ( $this->local ) {
			if ( file_exists( $url ) ) {
				include_once $url;
			} else {
				if ( true === WP_DEBUG ) {
					error_log( 'janrain-capture: Could not find screen file at path ' . $url );
				}
			}
		} else {
			$resp = wp_remote_get( $url );
			$out = wp_remote_retrieve_body( $resp );
			echo $out ?: sprintf( 'Janrain: Unable to load %s', $url );
		}
		echo '</script>';
	}
}
