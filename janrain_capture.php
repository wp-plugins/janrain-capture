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

  /**
   * Initializes the plugin.
   */
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
        add_action('wp_ajax_' . $this->name . '_profile_update', array(&$this, 'profile_update'));
        add_action('wp_ajax_nopriv_' . $this->name . '_profile_update', array(&$this, 'profile_update'));
        add_action('wp_ajax_' . $this->name . '_xdcomm', array(&$this, 'xdcomm'));
        add_action('wp_ajax_nopriv_' . $this->name . '_xdcomm', array(&$this, 'xdcomm'));
        add_action('wp_ajax_' . $this->name . '_refresh_token', array(&$this, 'refresh_token'));
        add_action('wp_ajax_nopriv_' . $this->name . '_refresh_token', array(&$this, 'refresh_token'));
        add_action('wp_ajax_' . $this->name . '_logout', array(&$this, 'logout'));
        add_action('wp_ajax_nopriv_' . $this->name . '_logout', array(&$this, 'logout'));
      } else {
        add_shortcode('janrain_capture', array(&$this, 'shortcode'));
      }

      $ui = new JanrainCaptureUi($this->name);
    }

  /**
   * Method used for the janrain_capture_redirect_uri action on admin-ajax.php.
   */
    function redirect_uri() {
      $code = $_REQUEST['code'];
      if (!ctype_alnum($code))
        throw new Exception('Janrian Capture: received code was not valid');
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
        $r = $origin ? esc_url($origin) : '/';
        $d = (int) get_option($this->name . '_refresh_duration');
        $user_attributes = get_option($this->name . '_user_attributes');
        if ($user_attributes) {
          $user_entity = $api->load_user_entity($json_data['access_token']);
          if (is_array($user_entity) && $user_entity['stat'] == "ok") {
            $user_entity = $user_entity['result'];
            do_action($this->name . '_user_entity_loaded', $user_entity);
          } else {
            throw new Exception('Janrain Capture: Could not retrieve user entity');
          }
        }
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
      ydate.setDate(ydate.getDate()+$d);
      document.cookie='{$this->name}_refresh_token={$json_data['refresh_token']}; expires='+ydate.toUTCString() + '; path=/$s';
      document.cookie='{$this->name}_expires='+ydate.toUTCString()+'; expires='+ydate.toUTCString() + '; path=/$s';
REDIRECT;
        if ($user_attributes && $user_entity) {
          $attrs = explode(',', $user_attributes);
          $cookie_vals = array('uuid' => $user_entity['uuid']);
          foreach ($attrs as $a) {
            $cookie_vals[$a] = $user_entity[$a];
          }
          $cookie_val = json_encode($cookie_vals);
          echo "
      document.cookie='{$this->name}_user_attrs=" . urlencode($cookie_val) . "; expires='+ydate.toUTCString() + '; path=/$s';
";
        }
        echo <<<CLOSER
      if (window.self != window.parent)
        window.parent.CAPTURE.closeAuth();
      else
        window.location.href = '$r';
    </script>
  </body>
</html>
CLOSER;
        die();
      } else {
        throw new Exception('Janrain Capture: Could not retrieve access_token');
      }
    }

  /**
   * Method used for the janrain_capture_profile action on admin-ajax.php.
   * This method prints javascript to retreive the access_token from a cookie and
   * render the profile screen if a valid access_token is found.
   */
    function profile() {
      $method = $_REQUEST['method'] ? $_REQUEST['method'] : '';
      $args = array(
        'redirect_uri' => admin_url('admin-ajax.php') . '?action=' . $this->name . '_redirect_uri',
        'client_id' => self::sanitize(get_option($this->name . '_client_id')),
        'xd_receiver' => admin_url('admin-ajax.php') . '?action=' . $this->name . '_xdcomm',
        'callback' => 'CAPTURE.closeProfile'
      );
      $capture_addr = get_option($this->name . '_ui_address') ? get_option($this->name . '_ui_address') : get_option($this->name . '_address');
      $capture_addr = 'https://' . self::sanitize($capture_addr) . '/oauth/profile' . self::sanitize($method) . '?' . http_build_query($args, '', '&');
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

  /**
   * Method used for the janrain_capture_profile_update action on admin-ajax.php.
   * This method retrives a user record from Capture and updates the janrain_capture_user_attrs
   * cookie accordingly.
   */
    function profile_update() {
      $access_token = $_REQUEST['access_token'] ? $_REQUEST['access_token'] : '';
      $api = new JanrainCaptureApi($this->name);
      $user_attributes = get_option($this->name . '_user_attributes');
      if ($user_attributes) {
        $user_entity = $api->load_user_entity($access_token);
        if (is_array($user_entity) && $user_entity['stat'] == "ok") {
          $user_entity = $user_entity['result'];
          do_action($this->name . '_user_entity_loaded', $user_entity);
          $attrs = explode(',', $user_attributes);
          $cookie_vals = array('uuid' => $user_entity['uuid']);
          foreach ($attrs as $a) {
            $cookie_vals[$a] = $user_entity[$a];
          }
          echo json_encode($cookie_vals);
        } else {
          throw new Exception('Janrain Capture: Could not retrieve user entity');
        }
      } else {
        echo '-1';
      }
      die();
    }

  /**
   * Method used for the janrain_capture_refresh_token action on admin-ajax.php.
   * This method is an AJAX endpoint for issuing a request to refresh the Capture
   * token set. The response is the JSON object returned by Capture.
   */
    function refresh_token() {
      $refresh_token = $_REQUEST['refresh_token'];
      if (!preg_match("/^[a-z0-9]+$/iD", $refresh_token))
        throw new Exception('Janrain Capture: invalid refresh_token');
      $api = new JanrainCaptureApi($this->name);
      $json_data = $api->refresh_access_token($refresh_token);
      if (is_array($json_data) && $json_data['stat'] == 'ok' && $json_data['access_token']) {
        echo json_encode($json_data);
        die();
      } else {
        throw new Exception('Janrain Capture: Could not refresh access_token');
      }
    }

  /**
   * Method used for the janrain_capture_logout action on admin-ajax.php.
   * This method is used to destroy any Capture cookies associated with the current
   * session.
   */
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
      document.cookie = '{$this->name}_access_token=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/';
      document.cookie = '{$this->name}_refresh_token=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/';
      document.cookie = '{$this->name}_user_attrs=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/';
      if (window.parent != window.self) {
        if (typeof(window.parent.Backplane) != 'undefined')
          document.cookie = 'backplane-channel=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/';
        if (typeof(window.parent.{$this->name}_on_logout) == 'function')
          window.parent.{$this->name}_on_logout();
      } else {
        window.location.redirect = '/';
      }
    </script>
  </body>
</html>
LOGOUT;
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
      var rpxJsHost = (("https:" == document.location.protocol) ? "https://" : "http://static.");
      document.write(unescape("%3Cscript src='" + rpxJsHost + "janraincapture.com/js/lib/xdcomm.js' type='text/javascript'%3E%3C/script%3E"));
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
    function shortcode($args) {
      extract(shortcode_atts(array(
        'type' => 'modal',
        'text' => 'Sign in / Register',
        'action' => 'signin',
        'height' => '400',
        'width' => '700',
        'href_only' => 'false'
      ), $args));
      $class = 'capture-anon';
      $capture_addr = get_option($this->name . '_ui_address') ? get_option($this->name . '_ui_address') : get_option($this->name . '_address');
      if (strpos($action, 'profile') === 0) {
        $uargs = array('action' => $this->name . '_profile');
        if (strlen($action) > 7) {
          $method = substr($action, 7);
          $uargs['method'] = urlencode($method);
        }
        $link = admin_url('admin-ajax.php') . '?' . http_build_query($uargs, '', '&');
        $class = 'capture-auth';
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
        return esc_url($link);
      if ($type == 'inline') {
        $iframe = '<iframe src="' . esc_url($link) . '" style="width:' . (int) $width . 'px;height:' . (int) $height . 'px;" class="' . $this->name . '_iframe ' . $class . ' ' . $this->name . '_' . self::sanitize($action) . '"></iframe>';
        return $iframe;
      } else {
        $anchor = '<a href="' . esc_url($link) . '" rel="width:' . (int) $width . 'px;height:' . (int) $height . 'px;" class="' . $this->name . '_anchor modal-link ' . $class . ' ' . $this->name . '_' . self::sanitize($action) . '">' . $text . '</a>';
        return $anchor;
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
    static function sanitize($s) {
      return preg_replace("/[^a-z0-9\._-]+/i", '', $s);
    }
  }
}

$capture = new JanrainCapture;
$capture->init();

