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

      require_once $this->path . '/janrain_capture_admin.php';
      //require_once $this->path . '/janrain_capture_api.php';

      $admin = new JanrainCaptureAdmin();
      $admin->onPost();
      add_action('admin_menu', array($admin,'admin_menu'));
    }
  }
}

$capture = new JanrainCapture;
$capture->init();
