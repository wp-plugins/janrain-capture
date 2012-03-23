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
   * Perform the exchange to generate a new Access Token
   *
   * @param string $auth_code
   *   The authorization token to use for the exchange
   * @param array $arg_array
   *   The data set to pass via POST
   * @param string $access_token
   *   The client access token to use when performing user-specific calls
   */
  public function new_access_token($auth_code, $redirect_uri) {
    $command = "oauth/token";
    $arg_array = array('code' => $auth_code,
      'redirect_uri' => $redirect_uri,
      'grant_type' => 'authorization_code'
    );

    return $this->call($command, $arg_array);
  }

  /**
   * Fetches a new token set. Used when access_token expires.
   *
   * @param string $refresh_token
   *   A valid Capture refresh token
   * @return mixed
   *   The HTTP request response
   */
  function refresh_access_token($refresh_token) {
    $command = "oauth/token";
    $arg_array = array('refresh_token' => $refresh_token,
      'grant_type' => 'refresh_token'
    );

    return $this->call($command, $arg_array);
  }

  /**
   * Fetches the user entity.
   *
   * @param string $access_token
   *   A valid Capture access token
   * @return mixed
   *   The HTTP request response
   */
  public function load_user_entity($access_token) {
    return $this->call('entity', null, $access_token);
  }

}

