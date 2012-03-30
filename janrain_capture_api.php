<?php

/**
 * @package Janrain Capture
 *
 * API Client for making calls to the Janrain Capture web service
 *
 */
class JanrainCaptureAPI {

  protected $args;
  protected $capture_addr;
  private $name;
  public $access_token;
  public $refresh_token;
  public $expires;
  public $password_recover;
  public $action;

  /**
   * Gets settings, initializes plugin name.
   *
   * @param string $name
   *   The plugin name to use as a namespace
   */
  function __construct($name) {
    $this->name = $name;
    $this->args = array();
    $this->args['client_id'] = get_option($this->name . '_client_id');
    $this->args['client_secret'] = get_option($this->name . '_client_secret');
    $this->capture_addr = get_option($this->name . '_address');
  }

  /**
   * Performs the HTTP request.
   *
   * @param string $command
   *   The Capture command to perform
   * @param array $arg_array
   *   The data set to pass via POST
   * @param string $access_token
   *   The client access token to use when performing user-specific calls
   * @return mixed
   *   The HTTP request result data
   */
  protected function call($command, $arg_array = null, $access_token = null) {

    $url = "https://" . $this->capture_addr . "/$command";

    $headers = array();
    if (isset($access_token))
      $headers['Authorization'] = "OAuth $access_token";

    if (isset($arg_array)) {
      $headers['Content-Type'] = 'application/x-www-form-urlencoded';
      $arg_array = array_merge($arg_array, $this->args);
      $result = wp_remote_post($url, array(
        'method' => 'POST',
        'body' => $arg_array,
        'headers' => $headers
      ));
    }
    else {
      $result = wp_remote_get($url, array(
        'method' => 'GET',
        'headers' => $headers
      ));
    }

    if (is_wp_error($result) || !isset($result['body']))
      return false;

    $json_data = json_decode($result['body'], true);

    if ($json_data['stat'] == 'error')
      return false;

    return $json_data;
  }

  /**
   * Updates session variables with Capture user tokens
   *
   * @param string $json_data
   *   The data received from the HTTP request containing the tokens
   */
  protected function update_capture_token($json_data) {
    $this->access_token = $json_data['access_token'];
    $this->refresh_token = $json_data['refresh_token'];
    $this->expires = time() + $json_data['expires_in'];

    $this->password_recover = (isset($json_data['transaction_state']['capture']['password_recover'])
        && $json_data['transaction_state']['capture']['password_recover'] == true) ? true : false;
    if (isset($json_data['transaction_state']['capture']['action']))
      $this->action = $json_data['transaction_state']['capture']['action'];
  }

  /**
   * Stores Capture tokens in the wp_usermeta table
   *
   * @param int $user_id
   *   (Optional) A valid WordPress User ID
   */
  public function update_user_meta($user_id=false) {
    if (!$user_id) {
      $current_user = wp_get_current_user();
      if (!$current_user->ID)
        return false;
      else
        $user_id = $current_user->ID;
    }
    if (!$this->access_token || !$this->refresh_token || !$this->expires)
      return false;
    $results = array();
    $results[] = update_user_meta($user_id, $this->name . '_access_token', $this->access_token);
    $results[] = update_user_meta($user_id, $this->name . '_refresh_token', $this->refresh_token);
    $results[] = update_user_meta($user_id, $this->name . '_expires', $this->expires);
    return !array_search(false, $results);
  }

  /**
   * Perform the exchange to generate a new Access Token
   *
   * @param string $auth_code
   *   The authorization token to use for the exchange
   * @param string $redirect_uri
   *   The redirect_uri used to generated the code
   * @return boolean
   *   The success/failure of the token request
   */
  public function new_access_token($auth_code, $redirect_uri) {
    $command = "oauth/token";
    $arg_array = array('code' => $auth_code,
      'redirect_uri' => $redirect_uri,
      'grant_type' => 'authorization_code'
    );

    $json_data = $this->call($command, $arg_array);
    if ($json_data) {
      $this->update_capture_token($json_data);
      do_action($this->name . '_new_access_token', $json_data);
      return true;
    }

    return false;
  }

  /**
   * Fetches a new token set. Used when access_token expires.
   *
   * @return boolean
   *   The success/failure of the token request
   */
  function refresh_access_token() {
    if (!$this->refresh_token) {
      $current_user = wp_get_current_user();
      if (!$current_user->ID)
        return false;
      $this->refresh_token = get_user_meta($current_user->ID, $this->name . '_refresh_token', true);
    }

    if (!$this->refresh_token)
      return false;

    $command = "oauth/token";
    $arg_array = array('refresh_token' => $this->refresh_token,
      'grant_type' => 'refresh_token'
    );

    $json_data = $this->call($command, $arg_array);

    if ($json_data) {
      $this->update_capture_token($json_data);
      do_action($this->name . '_refresh_access_token', $json_data);
      return true;
    }

    return false;
  }

  /**
   * Fetches the user entity.
   *
   * @param boolean $can_refresh
   *   Switch to disable refresh if response fails
   * @return mixed
   *   The HTTP request response
   */
  public function load_user_entity($can_refresh = true) {
    if (!$this->access_token) {
      $current_user = wp_get_current_user();
      if ($current_user->ID) {
        $this->access_token = get_user_meta($current_user->ID, $this->name . '_access_token', true);
        $this->refresh_token = get_user_meta($current_user->ID, $this->name . '_refresh_token', true);
        $this->expires = get_user_meta($current_user->ID, $this->name . '_expires', true);
      }
    }

    if (!$this->access_token)
      return null;

    $user_entity = null;

    $need_to_refresh = false;

    // Check if we need to refresh the access token
    if (time() >= $this->expires) {
      $need_to_refresh = true;
    } else {
      $user_entity = $this->call('entity', array(), $this->access_token);
      if (isset($user_entity['code']) && $user_entity['code'] == '414')
        $need_to_refresh = true;
    }

    // If necessary, refresh the access token and try to fetch the entity again.
    if ($need_to_refresh) {
      if ($can_refresh) {
        if ($this->refresh_access_token())
          return $this->load_user_entity(false);
        else
          return null;
      }
    }

    return $user_entity;
  }
}

