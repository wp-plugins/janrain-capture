<?php

/**
 * @file
 *
 * Methods for inserting UI elements
 *
 */
class JanrainCaptureUi {
	
	function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'registerScript'));
	}
	
	function registerScript() {
		wp_enqueue_script('janrain_capture_main_script', WP_PLUGIN_URL . '/janrain-capture/janrain_capture_ui.js');
	}
}
	
	
	
?>