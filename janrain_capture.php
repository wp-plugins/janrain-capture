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
        add_action('admin_menu', array($admin,'admin_menu'));
        add_action('wp_ajax_janrain_capture_redirect_uri', array($this, 'redirect_uri'));
        add_action('wp_ajax_nopriv_janrain_capture_redirect_uri', array($this, 'redirect_uri'));
      }
      
      $ui = new JanrainCaptureUi();
    }

    public function redirect_uri() {
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
        var_dump($user_entity);
        exit;
      }
    }
    
 	function insert_signin_link() {
 	  $app_addr = 'https://' . get_option('janrain_capture_address') . '/oauth/signin' . '?';
 	  
 	  $args = array ( 'response_type'   => 'code',
					  'redirect_uri'    => WP_PLUGIN_URL . "/janrain-capture/oauth_redirect.php",
					  'client_id'       => get_option('janrain_capture_client_id'),
					  'xd_receiver'     => WP_PLUGIN_URL . "/janrain-capture/xdcomm.html",
					  'recover_password_callback' => 'CAPTURE.recoverPasswordCallback'); //optionally pass in callback
 	  
 	  $u = $app_addr . http_build_query($args);
	  $link = '<a id="login_link" href="' . $u . '">Register / Sign In</a>';
	  echo '<div id=login_div>' . $link . '</div>';
	}
  }
}

$capture = new JanrainCapture;
$capture->init();
$capture->insert_signin_link();
