<?php

/**
 * @file
 *
 * Methods for inserting UI elements
 *
 */
class JanrainCaptureUi {
	
	function __construct() {
		add_action('wp_head', array($this, 'registerPageHead'));
		add_action('wp_enqueue_scripts', array($this, 'registerScripts'));
	}
	
	function registerScripts() {
		wp_enqueue_script('jquery_1.7', 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js');
		wp_enqueue_script('colorbox', WP_PLUGIN_URL . '/janrain-capture/colorbox/jquery.colorbox.js');
		wp_enqueue_script('janrain_capture_main_script', WP_PLUGIN_URL . '/janrain-capture/janrain_capture_ui.js');	
	}
	
	function registerPageHead() {
		// Adds the Colorbox CSS to the page head.
		?><link rel="stylesheet" href="<?php echo WP_PLUGIN_URL;?>/janrain-capture/colorbox/colorbox.css" />
<?php
	}
}
	
	
	
?>