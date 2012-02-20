<?php

/**
 * @file
 *
 * API Client for making calls to the Janrain Capture web service
 *
 */
class JanrainCaptureAPI {

  protected $args;
  protected $capture_addr;

  function __construct() {
    $this->args = array();
    $this->args['client_id'] = get_option('janrain_capture_client_id');
    $this->args['client_secret'] = get_option('janrain_capture_client_secret');
    $this->capture_addr = get_option('janrain_capture_address');
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
   * @return
   *   The HTTP request result data
   */
  protected function call($command, $arg_array = null, $access_token = null) {

    $url = "https://" . $this->capture_addr . "/$command";

    $headers = array(
      'Content-Type' => 'application/json'
    );
    if (isset($access_token))
      $headers['Authorization'] = "OAuth $access_token";

    if (isset($arg_array)) {
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

    if (!isset($result['body']))
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

    $json_data = $this->call($command, $arg_array);
    if ($json_data) {
      return true;
    }

    return false;
  }
  
  function refresh_access_token($refresh_token) {
    $command = "oauth/token";
    $arg_array = array('refresh_token' => $refresh_token,
      'grant_type' => 'refresh_token'
    );

    $json_data = $this->call($command, $arg_array);

    if ($json_data) {
      return true;
    }

    return false;
  }

  public function load_user_entity($can_refresh = true) {
    $current_user = wp_get_current_user();
    if (!$current_user->ID
      || !$current_user->janrain_capture_access_token
      || !$current_user->janrain_capture_refresh_token
      || !$current_user->janrain_capture_expires) {
      return null;
    }

    $user_entity = null;

    $need_to_refresh = false;

    // Check if we need to refresh the access token
    if (time() >= $current_user->janrain_capture_expires)
      $need_to_refresh = true;
    else {
      $user_entity = $this->call('entity', array(), $current_user->janrain_capture_access_token);
      if (isset($user_entity['code']) && $user_entity['code'] == '414')
        $need_to_refresh = true;
    }

    // If necessary, refresh the access token and try to fetch the entity again.
    if ($need_to_refresh) {
      if ($can_refresh) {
        if ($this->refresh_access_token($current_user->janrain_capture_refresh_token))
          return $this->load_user_entity(false);
        else
          return null;
      }
    }

    return $user_entity;
  }

}
