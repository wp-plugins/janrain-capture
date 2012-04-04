<?php
/**
 * @package Janrain Capture
 */
/*
Plugin Name: Janrain Capture
Plugin URI: http://www.janrain.com/
Description: Collect, store and leverage user profile data from social networks in a flexible, lightweight hosted database.
Version: 0.0.2
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
      $this->path = plugin_dir_path(__FILE__);
      $this->name = 'janrain_capture';
      $this->url = WP_PLUGIN_URL.'/janrain-capture';

      register_activation_hook(__FILE__, array(&$this, 'activate'));
      require_once $this->path . '/janrain_capture_api.php';

      if (is_admin()) {
        require_once $this->path . '/janrain_capture_admin.php';
        $admin = new JanrainCaptureAdmin($this->name);

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
      } else {
        add_shortcode($this->name, array(&$this, 'shortcode'));
      }
      if (get_option($this->name . '_ui_enabled') != '0') {
        require_once $this->path . '/janrain_capture_ui.php';
        $ui = new JanrainCaptureUi($this->name);
      }
    }

  /**
   * Method bound to register_activation_hook.
   */
    function activate() {
      require_once plugin_dir_path(__FILE__) . '/janrain_capture_admin.php';
      $admin = new JanrainCaptureAdmin($this->name);
      $admin->activate();
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
      if ($api->new_access_token($code, $redirect_uri)) {
        $user_entity = $api->load_user_entity();
        if (is_array($user_entity) && $user_entity['stat'] == "ok") {
          $user_entity = $user_entity['result'];
          do_action($this->name . '_user_entity_loaded', $user_entity);
          // Lookup user based on returned uuid
          $exists = get_users(array('blog_id'=>$GLOBALS['blog_id'], 'meta_key' => $this->name . '_uuid', 'meta_value' => $user_entity['uuid']));
          if (count($exists)<1) {
            $user_attrs = array();
            $user_attrs['user_pass'] = wp_generate_password($length=12, $include_standard_special_chars=false);
            if (get_option($this->name . '_user_email'))
              $user_attrs['user_email'] = esc_sql($this->get_field(get_option($this->name . '_user_email'), $user_entity));
            if (get_option($this->name . '_user_login'))
              $user_attrs['user_login'] = esc_sql($this->get_field(get_option($this->name . '_user_login'), $user_entity));
            if (get_option($this->name . '_user_nicename'))
              $user_attrs['user_nicename'] = esc_sql($this->get_field(get_option($this->name . '_user_nicename'), $user_entity));
            if (get_option($this->name . '_user_display_name'))
              $user_attrs['display_name'] = esc_sql($this->get_field(get_option($this->name . '_user_display_name'), $user_entity));
            $user_id = wp_insert_user($user_attrs);
            if (!$user_id)
              throw new Exception('Janrain Capture: Failed to create new user');
            if (!add_user_meta($user_id, $this->name . '_uuid', $user_entity['uuid'], true))
              throw new Exception('Janrain Capture: Failed to set uuid on new user');
            if (!$this->update_user_data($user_id, $user_entity, true))
              throw new Exception('Janrain Capture: Failed to update user data');
          } else {
            $user = $exists[0];
            $user_id = $user->ID;
            if (!$this->update_user_data($user_id, $user_entity))
              throw new Exception('Janrain Capture: Failed to update user data');
          }
          if (!$api->update_user_meta($user_id))
            throw new Exception('Janrain Capture: Failed to update user meta');
          wp_set_auth_cookie($user_id);
        } else {
          throw new Exception('Janrain Capture: Could not retrieve user entity');
        }

        $r = ($origin) ? esc_url($origin) : '/';

        echo <<<REDIRECT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
  <head>
    <title>Janrain Capture</title>
  </head>
  <body>
    <script type="text/javascript">
      if (window.self != window.parent)
        window.parent.CAPTURE.closeAuth('$r');
      else
        window.location.href = '$r';
    </script>
  </body>
</html>
REDIRECT;
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
      $current_user = wp_get_current_user();
      if (!$current_user->ID)
        throw new Exception('Janrain Capture: user not logged in');

      $method = $_REQUEST['method'] ? $_REQUEST['method'] : '';
      $callback = $_REQUEST['callback'] ? $_REQUEST['callback'] : 'CAPTURE.closeProfile';
      $expires = get_user_meta($current_user->ID, $this->name . '_expires', true);
      if ($expires && time() >= $expires) {
        $api = new JanrainCaptureApi($this->name);
        if ($api->refresh_access_token() === false)
          throw new Exception('Janrain Capture: Could not refresh access_token');
        if (!$api->update_user_meta())
          throw new Exception('Janrain Capture: Failed to update user meta');
      }
      $access_token = get_user_meta($current_user->ID, $this->name . '_access_token', true);
      if (!$access_token)
        throw new Exception('Janrain Capture: No user access token found');
      $args = array(
        'redirect_uri' => admin_url('admin-ajax.php') . '?action=' . $this->name . '_redirect_uri',
        'client_id' => self::sanitize(get_option($this->name . '_client_id')),
        'xd_receiver' => admin_url('admin-ajax.php') . '?action=' . $this->name . '_xdcomm',
        'callback' => self::sanitize($callback),
        'access_token' => $access_token
      );
      $capture_addr = get_option($this->name . '_ui_address') ? get_option($this->name . '_ui_address') : get_option($this->name . '_address');
      $capture_addr = 'https://' . self::sanitize($capture_addr) . '/oauth/profile' . self::sanitize($method) . '?' . http_build_query($args, '', '&');
      header("Location: $capture_addr", true, 302);
      die();
    }

  /**
   * Method used for the janrain_capture_profile_update action on admin-ajax.php.
   * This method retrives a user record from Capture and updates the janrain_capture_user_attrs
   * cookie accordingly.
   */
    function profile_update() {
      $current_user = wp_get_current_user();
      if (!$current_user->ID)
        throw new Exception('Janrain Capture: Must be logged in to update profile');

      $user_id = $current_user->ID;
      $api = new JanrainCaptureApi($this->name);
      $user_entity = $api->load_user_entity();
      if (is_array($user_entity)) {
        if (!$api->update_user_meta($user_id))
          throw new Exception('Janrain Capture: Failed to update user meta');
        $user_entity = $user_entity['result'];
        do_action($this->name . '_user_entity_loaded', $user_entity);
        if (!$this->update_user_data($user_id, $user_entity))
          throw new Exception('Janrain Capture: Failed to update user data');
      } else {
        throw new Exception('Janrain Capture: Could not retrieve user entity');
      }
      echo '1';
      die();
    }

  /**
   * Method used for updating user data with returned Capture user data
   *
   * @param int $user_id
   *   The ID of the user to update
   * @param array $user_entity
   *   The user entity returned from Capture
   * @return boolean
   *   Success or failure
   */
    function update_user_data($user_id, $user_entity, $meta_only=false) {
      if (!$user_id || !is_array($user_entity))
        throw new Exception('Janrain Capture: Not a valid User ID or User Entity');

      $results = array();
      if ($meta_only !== true) {
        $user_attrs = array('ID' => $user_id);
        if (get_option($this->name . '_user_email'))
          $user_attrs['user_email'] = esc_sql($this->get_field(get_option($this->name . '_user_email'), $user_entity));
        if (get_option($this->name . '_user_nicename'))
          $user_attrs['user_nicename'] = esc_sql($this->get_field(get_option($this->name . '_user_nicename'), $user_entity));
        if (get_option($this->name . '_user_display_name'))
          $user_attrs['display_name'] = esc_sql($this->get_field(get_option($this->name . '_user_display_name'), $user_entity));
        $userdata = wp_update_user($user_attrs);
        $results[] = ($userdata->ID > 0);
      }

      $metas = array('first_name', 'last_name', 'url', 'aim', 'yim', 'jabber', 'description');
      foreach($metas as $meta) {
        $key = get_option($this->name . '_user_' . $meta);
        if (!empty($key)) {
          $val = $this->get_field($key, $user_entity);
          if (!empty($val))
            $results[] = update_user_meta($user_id, $meta, $val);
        }
      }
      return !array_search(false, $results);
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
    function get_field($name, $user_entity) {
      if (strpos($name, '.')) {
        $names = explode('.', $name);
        $value = $user_entity;
        foreach ($names as $n) {
          $value = $value[$n];
        }   
        return $value;
      }
      else {
        return $user_entity[$name];
      }
    }

  /**
   * Method used for the janrain_capture_refresh_token action on admin-ajax.php.
   * This method is an AJAX endpoint for issuing a request to refresh the Capture
   * token set. The response is 1 for success and -1 on error.
   */
    function refresh_token() {
      $api = new JanrainCaptureApi($this->name);
      echo ($api->refresh_access_token() && $api->update_user_meta()) ? '1' : '-1';
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
        'href_only' => 'false',
        'callback' => 'CAPTURE.closeProfile'
      ), $args));
      $class = 'capture-anon';
      $capture_addr = get_option($this->name . '_ui_address') ? get_option($this->name . '_ui_address') : get_option($this->name . '_address');
      if (strpos($action, 'profile') === 0) {
        $uargs = array('action' => $this->name . '_profile', 'callback' => self::sanitize($callback));
        if (strlen($action) > 7) {
          $method = substr($action, 7);
          $uargs['method'] = self::sanitize($method);
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

