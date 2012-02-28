<?php
/**
 * @package Janrain Capture
 */
/*
Plugin Name: Janrain Capture
Plugin URI: http://www.janrain.com/
Description: Collect, store and leverage user profile data from social networks in a flexible, lightweight hosted database.
Version: 0.0.1
Author: Janrain
Author URI: http://www.janrain.com/
License: Apache License, Version 2.0
*/


if (!class_exists('JanrainCapture')) {	
  class JanrainCapture {
    public $path;
    public $basename;
    public $name;
    public $url;
    
    function init() {
      $this->path = dirname(__FILE__);
      $this->basename = plugin_basename(__FILE__);
      $this->name = trim(dirname($this->basename),'/');
      $this->url = WP_PLUGIN_URL.'/'.$this->name;
      
      require_once $this->path . '/janrain_capture_api.php';
      require_once $this->path . '/janrain_capture_ui.php';

      if (is_admin()) {
        require_once $this->path . '/janrain_capture_admin.php';
        $admin = new JanrainCaptureAdmin();
        $admin->onPost();
        
	    add_filter('loginout', array(&$this, 'login_logout'));
        add_action('admin_menu', array($admin,'admin_menu'));
        add_action('wp_ajax_janrain_capture_redirect_uri', array($this, 'redirect_uri'));
        add_action('wp_ajax_nopriv_janrain_capture_redirect_uri', array($this, 'redirect_uri'));
      } else {
      	add_filter('loginout', array(&$this, 'login_logout'));
      	add_filter('register', array(&$this, 'register_link'));
      }
      
      $ui = new JanrainCaptureUi();
    }

    public function redirect_uri() {
      // check_ajax_referer( ' ' );  // wp_create_nonce( ' ' );
      $code = $_REQUEST['code'];
      $origin = $_REQUEST['origin'];
      $redirect_args = array(
        'action' => 'janrain_capture_redirect_uri',
      );
      if ($origin)
        $redirect_args['origin'] = $origin;
      $redirect_uri = admin_url('admin-ajax.php') . '?' . http_build_query($redirect_args, '', '&');
      $api = new JanrainCaptureApi;
      $json_data = $api->new_access_token($code, $redirect_uri);
      if ($json_data['stat'] == 'ok' && $json_data['access_token']) {
        $json_data['expires'] == time() + $json_data['expires_in'];
        $user_entity = $api->load_user_entity($json_data['access_token']);
        if ($user_entity['stat'] == "ok") {
        	$user_entity = $user_entity['result'];
        } else {
        	// TODO: When would this return stat != "ok" ??????????????????????
        	echo "load_user_entity returned not ok status.<br />";
        	var_dump($user_entity);
        	die();
        }
       
        // Check to see if user exists in database
        $user_exists = username_exists($user_entity['email']);
        
        // If user does not exist, create user in database
        if (get_option('users_can_register') == 1 && !$user_exists) {
        	$this->write_wp_user_data($user_entity);
        }
        
        // Log user into local site
        $wp_user_entity = array( 'user_login'    => $user_entity['email'],
        						 'user_password' => $user_entity['uuid'],
        						 'remember'      => true);
        						 
        $secure_cookie = ($_SERVER['HTTPS']) ? true : false;
        $login_result = wp_signon($wp_user_entity, $secure_cookie);    // Secure cookie???????????
        if (is_wp_error($login_result)) {
        	echo $login_result->get_error_message() . "<br />";
        	if (get_option('users_can_register') != 1) {
        		echo "<br />New user registration is currently disabled.";
        	}
        	var_dump($login_result);
        } else {
        	echo "<h2>Login Successful</h2>";
        	?><script type='text/javascript'>window.parent.location.reload();</script><?php
        }
        
        exit;
      }
    }
	
	function login_logout($link) {
	  if (!is_user_logged_in()) {
	    $app_addr = 'https://' . get_option('janrain_capture_address') . '/oauth/signin' . '?';
	  
 	    $args = array ( 'response_type'   => 'code',
				  		'redirect_uri'    => admin_url('admin-ajax.php') . '?action=janrain_capture_redirect_uri',
						'client_id'       => get_option('janrain_capture_client_id'),
						'xd_receiver'     => WP_PLUGIN_URL . "/janrain-capture/xdcomm.html",
						'recover_password_callback' => 'CAPTURE.recoverPasswordCallback'); //optionally pass in callback  
 	    $url = $app_addr . http_build_query($args);
	
//		$link = str_replace('href=', 'id="login_link" href=', $link);
		$link_text = (get_option('users_can_register') == 1) ? "Log In / Register" : "Log In";
		$link = '<a id="login_link" href="' . $url . '">' . $link_text . '</a>';     // TODO: should regex to replace href
	  } else {
	  	$link = str_replace('?', '?redirect_to=' . rawurlencode($this->current_page_url()) . '&', $link);
	  }
	  return $link;
	}
	
	function current_page_url() {
		// Returns the current page URL
		$pageURL = 'http';
		if ( isset($_SERVER["HTTPS"]) ) {
			if ($_SERVER["HTTPS"] == "on") $pageURL .= "s";
		}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}
	
	function register_link($link) {
		// Registration and Site Administration links are both handled under this filter
		if (get_option('users_can_register') == 1 && strpos($link, "admin") === false) {
			return ''; // Link is for registration. Remove it.
		} else {
			return $link; // Link is for Site Admin. Keep it.
		}
	}
	
	function write_wp_user_data($user_entity) {
		$user_attrib = array( // WP Fields      => Capture Fields
							  'user_login'      => $user_entity['email'],
							  'user_pass'       => $user_entity['uuid'],   // ???????????
							  'user_nicename'   => $user_entity['email'],  // ???????????  This is public for authors & used in URLs!
							  'user_email'      => $user_entity['email'],
						//	  'user_url' => 
							  'user_registered' => date("Y-m-d H:i:s"),
						//	  'user_activation_key' => NULL,
							  'user_status'     => 0,
							  'display_name'    => $user_entity['displayName']);
		// Adds a new user with all the fields from $user_attrib array:
//		$new_user_id = wp_insert_user($user_attrib);

		// Adds a new user with the minimum required WP fields:
		$new_user_id = wp_create_user($user_entity['email'], $user_entity['uuid'], $user_entity['email']);
		
		// Add metadata to user_meta table:
//		add_user_meta($new_user_id, 'profile_identifier', 'i am a url');

		// Manually add DB entries for user.
	/*	$sql = 'INSERT INTO ' . $table . ' (user_login) VALUES ("' . $user_entity['email'] . '")'; // $json_data['email']
        $GLOBALS['wpdb']->query( $GLOBALS['wpdb']->prepare( $sql )); */
        
        
        
        
        // TODO: Need to handle error when email already exists in table, but isn't user_login. This currently causes a silent failure.




	}
  }
}

$capture = new JanrainCapture;
$capture->init();