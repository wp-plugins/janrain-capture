<?php

/**
 * @package Janrain Capture
 *
 * Admin interface for plugin options
 *
 */
class JanrainCaptureAdmin {

  private $postMessage;
  private $fields;

  /**
   * Initializes plugin name, builds array of fields to render.
   *
   * @param string $name
   *   The plugin name to use as a namespace
   */
  function  __construct() {
    $site_url = site_url();
    $this->postMessage = array('class'=>'', 'message'=>'');
    $this->fields = array(
      // Main Screen Fields
      array(
        'name' => JanrainCapture::$name . '_main',
        'title' => 'Global Capture Options',
        'type' => 'title',
        'screen' => 'main'
      ),
      array(
        'name' => JanrainCapture::$name . '_ui_share_enabled',
        'title' => 'Enable Social Sharing',
        'description' => 'Load the JS and CSS required for the Engage Share Widget',
        'default' => '1',
        'type' => 'checkbox',
        'screen' => 'main',
        'validate' => '/[^0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_ui_native_links',
        'title' => 'Override Native Links',
        'description' => 'Replace native Login & Profile links with Capture links',
        'default' => '1',
        'type' => 'checkbox',
        'screen' => 'main',
        'validate' => '/[^0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_ui_type',
        'title' => 'UI Type',
        'description' => 'Choose from Capture 1.0, Capture 2.0, or None if you plan to build your own.',
        'type' => 'select',
        'default' => 'Capture 1.0',
        'options' => array('None', 'Capture 1.0','Capture 2.0'),
        'screen' => 'main'
      ),
        
      // CaptureUI Settings
      array(
        'name' => JanrainCapture::$name . '_main_options',
        'title' => 'CaptureUI Options',
        'type' => 'title',
        'screen' => 'options'
      ),
      array(
        'name' => JanrainCapture::$name . '_address',
        'title' => 'Application Domain',
        'description' => 'Your Capture application domain <br/>(example: demo.janraincapture.com)',
        'required' => true,
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_client_id',
        'title' => 'API Client ID',
        'description' => 'Your Capture Client ID',
        'required' => true,
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_client_secret',
        'title' => 'API Client Secret',
        'description' => 'Your Capture Client Secret',
        'required' => true,
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_signin_ext',
        'title' => 'Signin Screen Extension',
        'description' => 'Modifies the signin path of the signin screen.',
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_federate',
        'title' => 'Federate Settings',
        'type' => 'title',
        'screen' => 'options'
      ),
      array(
        'name' => JanrainCapture::$name . '_sso_enabled',
        'title' => 'Enable SSO',
        'description' => 'Enable/Disable SSO for CaptureUI',
        'default' => '0',
        'type' => 'checkbox',
        'screen' => 'options',
        'validate' => '/[^0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_sso_address',
        'title' => 'SSO Application Domain',
        'description' => 'Your Janrain Federate SSO domain <br/>(example: demo.janrainsso.com)',
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_backplane_settings',
        'title' => 'Backplane Settings',
        'type' => 'title',
        'screen' => 'options'
      ),
      array(
        'name' => JanrainCapture::$name . '_backplane_enabled',
        'title' => 'Enable Backplane',
        'description' => 'Enable/Disable Backplane for CaptureUI',
        'default' => '0',
        'type' => 'checkbox',
        'screen' => 'options',
        'validate' => '/[^0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_bp_server_base_url',
        'title' => 'Server Base URL',
        'description' => 'Your Backplane server URL',
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%-_]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_bp_bus_name',
        'title' => 'Bus Name',
        'description' => 'Your Backplane Bus Name',
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_bp_js_path',
        'title' => 'JS Path',
        'description' => 'The path to backplane.js',
        'default' => '',
        'type' => 'long-text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%-_]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_ui_options',
        'title' => 'Other Options',
        'type' => 'title',
        'screen' => 'options'
      ),
      array(
        'name' => JanrainCapture::$name . '_recover_password_screen',
        'title' => 'Recover Password Screen',
        'description' => 'The name of the Capture screen to launch for users who click the authentication link in password recover emails',
        'default' => 'profile',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_ui_colorbox',
        'title' => 'Load Colorbox',
        'description' => 'You can use the colorbox JS & CSS bundled in our plugin or use your own',
        'default' => '1',
        'type' => 'checkbox',
        'screen' => 'options',
        'validate' => '/[^0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_ui_capture_js',
        'title' => 'Load Capture JS',
        'description' => 'The included Capture JS relies on Colorbox. You may want to disable it and use your own.',
        'default' => '1',
        'type' => 'checkbox',
        'screen' => 'options',
        'validate' => '/[^0-9]+/i'
      ),
        
      // Widget Screen Fields
      array(
        'name' => JanrainCapture::$name . '_widget_options',
        'title' => 'Capture 2.0 Settings',
        'type' => 'title',
        'screen' => 'widget'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_address',
        'title' => 'Application Url',
        'description' => 'Your Capture application url <br/>(example: https://demo.janraincapture.com)',
        'required' => true,
        'default' => '',
        'type' => 'text',
        'screen' => 'widget',
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_app_id',
        'title' => 'Application ID',
        'description' => 'Your Capture Application ID',
        'required' => true,
        'default' => '',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_client_id',
        'title' => 'API Client ID',
        'description' => 'Your Capture Client ID',
        'required' => true,
        'default' => '',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_client_secret',
        'title' => 'API Client Secret',
        'description' => 'Your Capture Client Secret',
        'required' => true,
        'default' => '',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_packages',
        'title' => 'Packages',
        'description' => "Change this only when instructed to do so (default: capture & login)",
        'required' => true,
        'type' => 'multiselect',
        'default' => 'capture,login',
        'options' => array(0 => 'capture', 1 => 'login', 3 => 'share'),
        'screen' => 'widget'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_engage_url',
        'title' => 'Engage Application Url',
        'description' => "Your Janrain Engage Applicaiton url <br/>(example: https://capturewidget.rpxnow.com)",
        'default' => 'https://capturewidget.rpxnow.com',
        'required' => true,
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%-_]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_recaptcha_pk',
        'title' => 'Recaptcha Public Key',
        'description' => 'Your Recaptcha Public Key',
        'default' => '',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_screens',
        'title' => 'Widget Settings',
        'type' => 'title',
        'screen' => 'widget'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_load_js',
        'title' => 'Url for load.js file',
        'description' => "The absolute url (minus protocol) of the Widget load.js file <br/>(example: d16s8pqtk4uodx.cloudfront.net/default/load.js)",
        'default' => 'd16s8pqtk4uodx.cloudfront.net/default/load.js',
        'required' => true,
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%-_]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_screen_folder',
        'title' => 'Screens Folder',
        'description' => "The absolute url of the Widget screens folder <br/>(example: $site_url/wp-content/plugins/janrain-capture-screens/)",
        'required' => true,
        'default' => $site_url.'/wp-content/plugins/janrain-capture-screens/',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%-_]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_auth_screen',
        'title' => 'Sign In Screens File',
        'description' => 'The filename of the Widget screen file to launch for login and registration',
        'required' => true,
        'default' => 'signin.php',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%-_]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_edit_screen',
        'title' => 'Edit Profile Screen File',
        'description' => 'The filename of the Widget screen file for editing a user\'s Capture Profile',
        'required' => true,
        'default' => 'edit-profile.php',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%-_]+/i'
       ),
//       array(
//         'name' => JanrainCapture::$name . '_widget_public_screen',
//         'title' => 'Public Profile Screen File',
//         'description' => 'The filename of the Widget screen file for a user\'s Public Capture Profile',
//         'required' => true,
//         'default' => 'public-profile.php',
//         'type' => 'text',
//         'screen' => 'widget',
//         'validate' => '/[^a-z0-9\.:\/\&\?\=\%-_]+/i'
//       ),
      array(
        'name' => JanrainCapture::$name . '_widget_reg_flow',
        'title' => 'Registration Flow',
        'description' => 'Change this only when instructed to do so (default: socialRegistration)',
        'required' => true,
        'default' => 'socialRegistration',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%-_]+/i'
      ),
     array(
        'name' => JanrainCapture::$name . '_widget_federate',
        'title' => 'Federate Settings',
        'type' => 'title',
        'screen' => 'widget'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_sso_enabled',
        'title' => 'Enable SSO',
        'description' => 'Enable/Disable SSO for Capture 2.0',
        'default' => '0',
        'type' => 'checkbox',
        'screen' => 'widget',
        'validate' => '/[^0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_sso_address',
        'title' => 'Application Domain',
        'description' => 'Your Janrain Federate SSO domain <br/>(example: demo.janrainsso.com)',
        'default' => '',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_so_xd',
        'title' => 'Cross-Domain Reciever Page',
        'description' => "Your Janrain Federate XD Reciever url <br/>(example: $site_url/xdcomm.html)",
        'default' => '',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%-_]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_sso_logout',
        'title' => 'Federate Logout Page',
        'description' => "Your Janrain Federate Logout url <br/>(example: $site_url/logout.html)",
        'default' => '',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%-_]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_backplane_settings',
        'title' => 'Backplane Settings',
        'type' => 'title',
        'screen' => 'widget'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_backplane_enabled',
        'title' => 'Enable Backplane',
        'description' => 'Enable/Disable Backplane for Capture 2.0',
        'default' => '0',
        'type' => 'checkbox',
        'screen' => 'widget',
        'validate' => '/[^0-9]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_widget_bp_bus_name',
        'title' => 'Bus Name',
        'description' => 'Your Backplane Bus Name',
        'default' => '',
        'type' => 'text',
        'screen' => 'widget',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),

      // Data Mapping Screen Fields
      array(
        'name' => JanrainCapture::$name . '_standard_fields',
        'title' => 'Standard WordPress User Fields',
        'type' => 'title',
        'screen' => 'data'
      ),
      array(
        'name' => JanrainCapture::$name . '_user_email',
        'title' => 'Email',
        'required' => true,
        'default' => 'email',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_user_login',
        'title' => 'Username',
        'description' => 'Usernames cannot be changed.',
        'required' => true,
        'default' => 'uuid',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_user_nicename',
        'title' => 'Nickname',
        'required' => true,
        'default' => 'displayName',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_user_display_name',
        'title' => 'Display Name',
        'required' => true,
        'default' => 'displayName',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_optional_fields',
        'title' => 'Optional User Fields',
        'type' => 'title',
        'screen' => 'data'
      ),
      array(
        'name' => JanrainCapture::$name . '_user_first_name',
        'title' => 'First Name',
        'default' => 'givenName',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_user_last_name',
        'title' => 'Last Name',
        'default' => 'familyName',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_user_url',
        'title' => 'Website',
        'default' => '',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_user_aim',
        'title' => 'AIM',
        'default' => '',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_user_yim',
        'title' => 'Yahoo IM',
        'default' => '',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_user_jabber',
        'title' => 'Jabber / Google Talk',
        'default' => '',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => JanrainCapture::$name . '_user_description',
        'title' => 'Biographical Info',
        'default' => 'aboutMe',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
    );

    $this->onPost();
    if (is_multisite()) {
      add_action('network_admin_menu', array(&$this,'admin_menu'));
      if (!is_main_site())
        add_action('admin_menu', array(&$this,'admin_menu'));
    } else {
      add_action('admin_menu', array(&$this,'admin_menu'));
    }
  }

  /**
   * Method bound to register_activation_hook.
   */
  function activate() {
    foreach($this->fields as $field) {
      if (!empty($field['default'])) {
        if (JanrainCapture::get_option($field['name']) === false)
          JanrainCapture::update_option($field['name'], $field['default']);
      }
    }
  }

  /**
   * Method bound to the admin_menu action.
   */
  function admin_menu() {
    
    $optPage = add_menu_page(__('Janrain Capture'), __('Janrain Capture'),
      'manage_options', JanrainCapture::$name, array(&$this, 'main'));
   
    $optionsPage = add_submenu_page(JanrainCapture::$name, __('Janrain Capture'), __('Capture 1.0'),
      'manage_options', JanrainCapture::$name . '_options', array(&$this, 'options'));
    
    $widgetPage = add_submenu_page(JanrainCapture::$name, __('Janrain Capture'), __('Capture 2.0'),
      'manage_options', JanrainCapture::$name . '_widget', array(&$this, 'widget'));
    
    if (!is_multisite() || is_main_site()) {
      $dataPage = add_submenu_page(JanrainCapture::$name, __('Janrain Capture'), __('Data Mapping'),
        'manage_options', JanrainCapture::$name . '_data', array(&$this, 'data'));
    }
  }

  /**
   * Method bound to the Janrain Capture main menu.
   */
  function main() {
    $args = new stdClass;
    $args->title = 'Janrain Capture Settings';
    $args->action = 'main';
    $this->printAdmin($args);
  }
  
  /**
   * Method bound to the Janrain CaptureUI menu.
   */
  function options() {
    $args = new stdClass;
    $args->title = 'CaptureUI Settings';
    $args->action = 'options';
    $this->printAdmin($args);
  }

  /**
   * Method bound to the Janrain Capture data menu.
   */
  function data() {
    $args = new stdClass;
    $args->title = 'Data Mapping Settings';
    $args->action = 'data';
    $this->printAdmin($args);
  }
  
  /**
   * Method bound to the Janrain Capture 2.0 or widget menu.
   */
  function widget() {
    $args = new stdClass;
    $args->title = 'Capture 2.0 Settings';
    $args->action = 'widget';
    $this->printAdmin($args);
  }

  /**
   * Method to print the admin page markup.
   *
   * @param stdClass $args
   *   Object with page title and action variables
   */
  function printAdmin($args) {
    $name = JanrainCapture::$name;
    echo <<<HEADER
<div id="message" class="{$this->postMessage['class']} fade">
  <p><strong>
    {$this->postMessage['message']}
  </strong></p>
</div>
<div class="wrap">
  <h2>{$args->title}</h2>
  <form method="post" id="{$name}_{$args->action}">
    <table class="form-table">
      <tbody>
HEADER;

    foreach($this->fields as $field) {
      if ($field['screen'] == $args->action) {
        if (is_multisite()
          && !is_main_site()
          && $args->action == 'options'
          && $field['name'] != JanrainCapture::$name . '_client_id'
          && $field['name'] != JanrainCapture::$name . '_client_secret'
          && $field['name'] != JanrainCapture::$name . '_main_options')
          continue;
        $this->printField($field);
      }
    }

    echo <<<FOOTER
      </tbody>
    </table>
    <p class="submit">
      <input type="hidden" name="{$name}_action" value="{$args->action}" />
      <input type="submit" class="button-primary" value="Save Changes" />
    </p>
  </form>
</div>
FOOTER;
  }

  /**
   * Method to print field-level markup.
   *
   * @param array $field
   *   A structured field definition with strings used in generating markup.
   */
  function printField($field) {
    if (is_multisite() && !is_main_site())
      $value = (get_option($field['name']) !== false) ? get_option($field['name']) : JanrainCapture::get_option($field['name'], false, true);
    else
      $value = JanrainCapture::get_option($field['name']);
    $value = ($value !== false) ? $value : $field['default'];
    $r = (isset($field['required']) && $field['required'] == true) ? ' <span class="description">(required)</span>' : '';
    switch ($field['type']) {
      case 'text':
        echo <<<TEXT
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
            <input type="text" name="{$field['name']}" value="$value" style="width:200px" />
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
TEXT;
        break;
      case 'long-text':
        echo <<<LONGTEXT
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
            <input type="text" name="{$field['name']}" value="$value" style="width:400px" />
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
LONGTEXT;
        break;
      case 'password':
        echo <<<PASSWORD
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
            <input type="password" name="{$field['name']}" value="$value" style="width:150px" />
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
PASSWORD;
        break;
      case 'select':
        sort($field['options']);
        echo <<<SELECT
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
              <select name="{$field['name']}" value="$value">
SELECT;
            foreach($field['options'] as $option) {
              $selected = ($value==$option) ? ' selected="selected"' : '';
              echo "<option value=\"{$option}\"{$selected}>$option</option>";
            }
            echo <<<ENDSELECT
              </select>
              <span class="description">{$field['description']}</span>
          </td>
        </tr>
ENDSELECT;
        break;
      case 'multiselect':
        sort($field['options']);
        echo <<<MSELECT
        <tr>
          <th><label for="{$field['name']}[]">{$field['title']}$r</label></th>
          <td valign="top">
              <select name="{$field['name']}[]" multiple="multiple">
MSELECT;
            foreach($field['options'] as $option) {
              $selected = in_array($option,$value) !== false ? ' selected="selected"' : '';
              echo "<option value=\"{$option}\"{$selected}>$option</option>";
            }
            echo <<<MENDSELECT
              </select>
              {$field['description']}
          </td>
        </tr>
MENDSELECT;
        break;
      case 'checkbox':
        $checked = ($value == '1') ? ' checked="checked"' : '';
        echo <<<CHECKBOX
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
            <input type="checkbox" name="{$field['name']}" value="1"$checked />
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
CHECKBOX;
        break;
      case 'title':
        echo <<<TITLE
        <tr>
          <td colspan="2">
            <h3 class="title">{$field['title']}</h3>
          </td>
        </tr>
TITLE;
        break;
    }
  }

  /**
   * Method to receive and store submitted options when posted.
   */
  public function onPost() {
    if (isset($_POST[JanrainCapture::$name . '_action'])) {
      foreach($this->fields as $field) {
        if (isset($_POST[$field['name']])) {
          $value = $_POST[$field['name']];
          if ($field['name'] == JanrainCapture::$name . '_address' || $field['name'] == JanrainCapture::$name . '_sso_address')
            $value = preg_replace('/^https?\:\/\//i', '', $value);
          if ($field['validate'])
            $value = preg_replace($field['validate'], '', $value);
          JanrainCapture::update_option($field['name'], $value);
        } else {
          if ($field['type'] == 'checkbox' && $field['screen'] == $_POST[JanrainCapture::$name . '_action']) {
            $value = '0';
            JanrainCapture::update_option($field['name'], $value);
          } else {
            if (JanrainCapture::get_option($field['name']) === false
              && isset($field['default'])
              && (!is_multisite() || is_main_site()))
              JanrainCapture::update_option($field['name'], $field['default']);
          }
        }
      }
      if ($_POST[JanrainCapture::$name . '_action'] == 'options') {
        $api = new JanrainCaptureApi();
        $key = $api->rpx_api_key();
        if ($key === false) {
          $this->postMessage = array('class'=>'error','message'=>'Please verify your Client ID and Client Secret.');
          JanrainCapture::update_option(JanrainCapture::$name . '_client_id', '');
          JanrainCapture::update_option(JanrainCapture::$name . '_client_secret', '');
        } else {
          $this->postMessage = array('class'=>'updated','message'=>'Configuration Saved');
          if ($key) {
            JanrainCapture::update_option(JanrainCapture::$name . '_rpx_api_key', $key);
            $result = $api->rpx_lookup_rp();
            if (isset($result['realm']) && isset($result['shareProviders'])) {
              $realm = str_replace('.rpxnow.com', '', $result['realm']);
              JanrainCapture::update_option(JanrainCapture::$name . '_rpx_realm', $realm);
              JanrainCapture::update_option(JanrainCapture::$name . '_rpx_share_providers', $result['shareProviders']);
            }
          }
        }
      } else {
        $this->postMessage = array('class'=>'updated','message'=>'Configuration Saved');
      }
    }
  }

}
