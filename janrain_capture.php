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
      $this->name = 'janrain_capture';
      $this->url = WP_PLUGIN_URL.'/'.$this->name;

      require_once $this->path . '/janrain_capture_api.php';
      require_once $this->path . '/janrain_capture_ui.php';

      if (is_admin()) {
        require_once $this->path . '/janrain_capture_admin.php';
        $admin = new JanrainCaptureAdmin($this->name);
        $admin->onPost();

        add_action('admin_menu', array(&$admin,'admin_menu'));
        add_action('wp_ajax_' . $this->name . '_redirect_uri', array(&$this, 'redirect_uri'));
        add_action('wp_ajax_nopriv_' . $this->name . '_redirect_uri', array(&$this, 'redirect_uri'));
        add_action('wp_ajax_' . $this->name . '_profile', array(&$this, 'profile'));
        add_action('wp_ajax_nopriv_' . $this->name . '_profile', array(&$this, 'profile'));
        add_action('wp_ajax_' . $this->name . '_xdcomm', array(&$this, 'xdcomm'));
        add_action('wp_ajax_nopriv_' . $this->name . '_xdcomm', array(&$this, 'xdcomm'));
        add_action('wp_ajax_' . $this->name . '_refresh_token', array(&$this, 'refresh_token'));
        add_action('wp_ajax_nopriv_' . $this->name . '_refresh_token', array(&$this, 'refresh_token'));
        add_action('wp_ajax_' . $this->name . '_logout', array(&$this, 'logout'));
        add_action('wp_ajax_nopriv_' . $this->name . '_logout', array(&$this, 'logout'));
      } else {
        add_shortcode('janrain_capture', array(&$this, 'shortcode'));
        add_action('wp_head', array(&$this, 'head'));
      }

      $ui = new JanrainCaptureUi($this->name);
    }

    function head() {
      $bp_js_path = get_option($this->name . '_bp_js_path');
      $bp_server_base_url = get_option($this->name . '_bp_server_base_url');
      $bp_bus_name = get_option($this->name . '_bp_bus_name');
      $sso_addr = get_option($this->name . '_sso_address');
      if ($bp_js_path)
        echo '<script type="text/javascript" src="' . $bp_js_path . '"></script>';
      if ($bp_server_base_url && $bp_bus_name)
        echo <<<BACKPLANE
<script type="text/javascript">
(function(){
  Backplane(CAPTURE.bp_ready);
    Backplane.init({
      serverBaseURL: "$bp_server_base_url",
      busName: "$bp_bus_name"
    });
})();
</script>
BACKPLANE;
      if ($sso_addr) {
        $client_id = get_option($this->name . '_client_id');
        $xdcomm = admin_url('admin-ajax.php') . '?action=' . $this->name . '_xdcomm';
        $redirect_uri = admin_url('admin-ajax.php') . '?action=' . $this->name . '_redirect_uri';
        $logout = admin_url('admin-ajax.php') . '?action=' . $this->name . '_logout';
        echo <<<SSO
<script type="text/javascript" src="https://$sso_addr/sso.js"></script>
<script type="text/javascript">
JANRAIN.SSO.CAPTURE.check_login({
  sso_server: "https://$sso_addr",
  client_id: "$client_id",
  redirect_uri: "$redirect_uri",
  logout_uri: "$logout",
  xd_receiver: "$xdcomm"
});
function janrain_capture_logout() {
  JANRAIN.SSO.CAPTURE.logout({
    sso_server: "https://$sso_addr",
    logout_uri: "$logout"
  });
}
</script>
SSO;
      }
      echo '<script type="text/javascript">if (typeof(ajaxurl) == "undefined") var ajaxurl = "' . admin_url('admin-ajax.php') . '";</script>';
    }

    function redirect_uri() {
      $code = $_REQUEST['code'];
      $origin = $_REQUEST['origin'];
      do_action($this->name . '_redirect_uri_start', $code, $origin);
      $redirect_args = array(
        'action' => $this->name . '_redirect_uri',
      );
      if ($origin)
        $redirect_args['origin'] = $origin;
      $redirect_uri = admin_url('admin-ajax.php') . '?' . http_build_query($redirect_args, '', '&');
      $api = new JanrainCaptureApi($this->name);
      $json_data = $api->new_access_token($code, $redirect_uri);
      if (is_array($json_data) && $json_data['stat'] == 'ok' && $json_data['access_token']) {
        do_action($this->name . '_new_access_token', $json_data);
        $s = $_SERVER['HTTPS'] ? '; secure' : '';
        $r = $origin ? $origin : '/';
        echo <<<REDIRECT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
  <head>
    <title>Janrain Capture</title>
  </head>
  <body>
    <script type="text/javascript">
      var xdate=new Date();
      xdate.setSeconds(xdate.getSeconds()+{$json_data['expires_in']});
      document.cookie='{$this->name}_access_token={$json_data['access_token']}; expires='+xdate.toUTCString() + '; path=/$s';
      var ydate=new Date();
      ydate.setDate(ydate.getDate()+30);
      document.cookie='{$this->name}_refresh_token={$json_data['refresh_token']}; expires='+ydate.toUTCString() + '; path=/$s';
      if (window.self != window.parent)
        window.parent.CAPTURE.closeAuth();
      else
        window.location.href = '$r';
    </script>
  </body>
</html>
REDIRECT;
        die();
        $user_entity = $api->load_user_entity($json_data['access_token']);
        if (is_array($user_entity) && $user_entity['stat'] == "ok") {
          $user_entity = $user_entity['result'];
        } else {
          throw new Exception('Janrain Capture: Could not retrieve user entity');
        }

        var_dump($user_entity);
        exit;

        die();
      } else {
        throw new Exception('Janrain Capture: Could not retrieve access_token');
      }
    }

    function profile() {
      $method = $_REQUEST['method'] ? $_REQUEST['method'] : '';
      $args = array (
        'redirect_uri' => admin_url('admin-ajax.php') . '?action=' . $this->name . '_redirect_uri',
        'client_id' => get_option($this->name . '_client_id'),
        'xd_receiver' => admin_url('admin-ajax.php') . '?action=' . $this->name . '_xdcomm',
        'callback' => 'CAPTURE.closeProfile'
      );
      $capture_addr = get_option($this->name . '_ui_address') ? get_option($this->name . '_ui_address') : get_option($this->name . '_address');
      $capture_addr = 'https://' . $capture_addr . "/oauth/profile{$method}?" . http_build_query($args, '', '&');
      echo <<<REDIRECT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
  <head>
    <title>Janrain Capture</title>
  </head>
  <body>
    <script type="text/javascript">
      function read_cookie(k,r){return(r=RegExp('(^|; )'+encodeURIComponent(k)+'=([^;]*)').exec(document.cookie))?r[2]:null;}
      var access_token = read_cookie('{$this->name}_access_token');
      if (access_token)
        window.location.href = '$capture_addr&access_token=' + access_token;
      else
        window.parent.CAPTURE.token_expired();
    </script>
  </body>
</html>
REDIRECT;
      die();
    }

    function refresh_token() {
      $refresh_token = $_REQUEST['refresh_token'];
      $api = new JanrainCaptureApi($this->name);
      $json_data = $api->refresh_access_token($refresh_token);
      if (is_array($json_data) && $json_data['stat'] == 'ok' && $json_data['access_token']) {
        echo json_encode($json_data);
        die();
      } else {
        throw new Exception('Janrain Capture: Could not refresh access_token');
      }
    }

    function logout() {
      echo <<<LOGOUT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
  <head>
    <title>Janrain Capture</title>
  </head>
  <body>
    <script type="text/javascript">
      document.cookie = 'janrain_capture_access_token=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
      document.cookie = 'janrain_capture_refresh_token=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
      if (window.parent != window.self) {
        if (typeof(window.parent.janrain_capture_on_logout) == 'function')
          window.parent.janrain_capture_on_logout();
      } else {
        window.location.redirect = '/';
      }
    </script>
  </body>
</html>
LOGOUT;
    }

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
      var rpxJsHost = (("https:" == document.location.protocol) ? "https://" : "http://static.");
      document.write(unescape("%3Cscript src='" + rpxJsHost + "janraincapture.com/js/lib/xdcomm.js' type='text/javascript'%3E%3C/script%3E"));
    </script>
  </body>
</html>
XDCOMM;
      die();
    }

    function shortcode($args) {
      extract(shortcode_atts(array(
        'type' => 'modal',
        'text' => 'Sign in / Register',
        'action' => 'signin',
        'height' => '400',
        'width' => '700',
        'href_only' => 'false'
      ), $args));
      $capture_addr = get_option($this->name . '_ui_address') ? get_option($this->name . '_ui_address') : get_option($this->name . '_address');
      if (strpos($action, 'profile') === 0) {
        $uargs = array('action' => $this->name . '_profile');
        if (strlen($action) > 7) {
          $method = substr($action, 7);
          $uargs['method'] = urlencode($method);
        }
        $link = admin_url('admin-ajax.php') . '?' . http_build_query($uargs, '', '&');
      }
      else {
        $link = 'https://' . $capture_addr . '/oauth/' . $action;
        $args = array (
          'response_type' => 'code',
          'redirect_uri' => admin_url('admin-ajax.php') . '?action=' . $this->name . '_redirect_uri',
          'client_id' => get_option($this->name . '_client_id'),
          'xd_receiver' => admin_url('admin-ajax.php') . '?action=' . $this->name . '_xdcomm',
          'recover_password_callback' => 'CAPTURE.closeRecoverPassword'
        );
        $link = $link . '?' . http_build_query($args, '', '&');
      }
      if ($href_only == 'true')
        return $link;
      if ($type == 'inline') {
        $iframe = '<iframe src="' . $link . '" style="width:' . $width . 'px;height:' . $height . 'px;" class="' . $this->name . '_iframe"></iframe>';
        return $iframe;
      } else {
        $anchor = '<a href="' . $link . '" rel="width:' . $width . 'px;height:' . $height . 'px;" class="' . $this->name . '_anchor modal-link">' . $text . '</a>';
        return $anchor;
      }
    }
  }
}

$capture = new JanrainCapture;
$capture->init();
