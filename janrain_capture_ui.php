<?php

/**
 * @package Janrain Capture
 *
 * Methods for inserting UI elements
 *
 */
class JanrainCaptureUi {
  private $name;

  /**
   * Sets up actions, initializes plugin name.
   *
   * @param string $name
   *   The plugin name to use as a namespace
   */
  function __construct($name) {
    $this->name = $name;
    add_action('wp_head', array($this, 'head'));
    add_action('wp_enqueue_scripts', array(&$this, 'registerScripts'));
  }

  /**
   * Adds javascript libraries to the page.
   *
   */
  function registerScripts() {
    wp_enqueue_script('colorbox', WP_PLUGIN_URL . '/' . $this->name . '/colorbox/jquery.colorbox.js', array('jquery'));
    wp_enqueue_script($this->name . '_main_script', WP_PLUGIN_URL . '/' . $this->name . '/janrain_capture_ui.js');	
  }

  /**
   * Method bound to the wp_head action.
   */
  function head() {
    wp_enqueue_style('colorbox', WP_PLUGIN_URL . '/' . $this->name . '/colorbox/colorbox.css');
    echo '<style type="text/css">.janrain_capture_anchor { display:none; }</style>';
    $bp_js_path = get_option($this->name . '_bp_js_path');
    $bp_server_base_url = get_option($this->name . '_bp_server_base_url');
    $bp_bus_name = get_option($this->name . '_bp_bus_name');
    $sso_addr = get_option($this->name . '_sso_address');
    $capture_addr = get_option($this->name . '_ui_address') ? get_option($this->name . '_ui_address') : get_option($this->name . '_address');
    echo '<script type="text/javascript" src="' . esc_url('https://' . $capture_addr . '/cdn/javascripts/capture_client.js') . '"></script>';
    if ($bp_js_path)
      echo '<script type="text/javascript" src="' . esc_url($bp_js_path) . '"></script>';
    if ($bp_server_base_url && $bp_bus_name)
      $bp_server_base_url = esc_url($bp_server_base_url);
      $bp_bus_name = JanrainCapture::sanitize($bp_bus_name);
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
    if ($sso_addr) {
      $client_id = get_option($this->name . '_client_id');
      $client_id = JanrainCapture::sanitize($client_id);
      $xdcomm = admin_url('admin-ajax.php') . '?action=' . $this->name . '_xdcomm';
      $redirect_uri = admin_url('admin-ajax.php') . '?action=' . $this->name . '_redirect_uri';
      $logout = admin_url('admin-ajax.php') . '?action=' . $this->name . '_logout';
      $sso_addr = esc_url('https://' . $sso_addr);
      echo <<<SSO
<script type="text/javascript" src="$sso_addr/sso.js"></script>
<script type="text/javascript">
JANRAIN.SSO.CAPTURE.check_login({
  sso_server: "$sso_addr",
  client_id: "$client_id",
  redirect_uri: "$redirect_uri",
  logout_uri: "$logout",
  xd_receiver: "$xdcomm"
});
function janrain_capture_logout() {
  JANRAIN.SSO.CAPTURE.logout({
    sso_server: "$sso_addr",
    logout_uri: "$logout"
  });
}
</script>
SSO;
    }
    echo '<script type="text/javascript">
      if (typeof(ajaxurl) == "undefined") var ajaxurl = "' . admin_url('admin-ajax.php') . '";
    var janrain_capture_refresh_duration = ' . (int) get_option($this->name . '_refresh_duration') . ';
</script>';
  }
}

