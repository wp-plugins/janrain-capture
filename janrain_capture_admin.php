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
  private $name;

  /**
   * Initializes plugin name, builds array of fields to render.
   *
   * @param string $name
   *   The plugin name to use as a namespace
   */
  function  __construct($name) {
    $this->name = $name;
    $this->postMessage = array('class'=>'', 'message'=>'');
    $this->fields = array(
      // Main Screen Fields
      array(
        'name' => $this->name . '_main_options',
        'title' => 'Main Options',
        'type' => 'title',
        'screen' => 'options'
      ),
      array(
        'name' => $this->name . '_address',
        'title' => 'Application Domain',
        'description' => 'Your Capture application domain (e.g. demo.janraincapture.com)',
        'required' => true,
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_client_id',
        'title' => 'API Client ID',
        'description' => 'Your Capture Client ID',
        'required' => true,
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9]+/i'
      ),
      array(
        'name' => $this->name . '_client_secret',
        'title' => 'API Client Secret',
        'description' => 'Your Capture Client Secret',
        'required' => true,
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9]+/i'
      ),
      array(
        'name' => $this->name . '_sso_address',
        'title' => 'SSO Application Domain',
        'description' => 'Your Janrain Federate SSO domain (e.g. demo.janrainsso.com)',
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_backplane_settings',
        'title' => 'Backplane Settings',
        'type' => 'title',
        'screen' => 'options'
      ),
      array(
        'name' => $this->name . '_bp_server_base_url',
        'title' => 'Server Base URL',
        'description' => 'Your Backplane server URL',
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%]+/i'
      ),
      array(
        'name' => $this->name . '_bp_bus_name',
        'title' => 'Bus Name',
        'description' => 'Your Backplane Bus Name',
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_bp_js_path',
        'title' => 'JS Path',
        'description' => 'The path to backplane.js',
        'default' => '',
        'type' => 'long-text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\.:\/\&\?\=\%]+/i'
      ),

      // Data Mapping Screen Fields
      array(
        'name' => $this->name . '_standard_fields',
        'title' => 'Standard WordPress User Fields',
        'type' => 'title',
        'screen' => 'data'
      ),
      array(
        'name' => $this->name . '_user_email',
        'title' => 'Email',
        'required' => true,
        'default' => 'email',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_user_login',
        'title' => 'Username',
        'description' => 'Usernames cannot be changed.',
        'required' => true,
        'default' => 'uuid',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_user_nicename',
        'title' => 'Nickname',
        'required' => true,
        'default' => 'displayName',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_user_display_name',
        'title' => 'Display Name',
        'required' => true,
        'default' => 'displayName',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_optional_fields',
        'title' => 'Optional User Fields',
        'type' => 'title',
        'screen' => 'data'
      ),
      array(
        'name' => $this->name . '_user_first_name',
        'title' => 'First Name',
        'default' => 'givenName',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_user_last_name',
        'title' => 'Last Name',
        'default' => 'familyName',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_user_url',
        'title' => 'Website',
        'default' => '',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_user_aim',
        'title' => 'AIM',
        'default' => '',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_user_yim',
        'title' => 'Yahoo IM',
        'default' => '',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_user_jabber',
        'title' => 'Jabber / Google Talk',
        'default' => '',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_user_description',
        'title' => 'Biographical Info',
        'default' => 'aboutMe',
        'type' => 'text',
        'screen' => 'data',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),

      // UI Screen Fields
      array(
        'name' => $this->name . '_ui_enabled',
        'title' => 'Enable UI Features',
        'description' => 'You can disable all UI features if you prefer to write your own',
        'default' => '1',
        'type' => 'checkbox',
        'screen' => 'ui',
        'validate' => '/[^0-9]+/i'
      ),
      array(
        'name' => $this->name . '_ui_options',
        'title' => 'Other Options',
        'type' => 'title',
        'screen' => 'ui'
      ),
      array(
        'name' => $this->name . '_ui_native_links',
        'title' => 'Override Native Links',
        'description' => 'Replace native Login & Profile links with Capture links',
        'default' => '1',
        'type' => 'checkbox',
        'screen' => 'ui',
        'validate' => '/[^0-9]+/i'
      ),
      array(
        'name' => $this->name . '_ui_colorbox',
        'title' => 'Load Colorbox',
        'description' => 'You can use the colorbox JS & CSS bundled in our plugin or use your own',
        'default' => '1',
        'type' => 'checkbox',
        'screen' => 'ui',
        'validate' => '/[^0-9]+/i'
      ),
      array(
        'name' => $this->name . '_ui_capture_js',
        'title' => 'Load Capture JS',
        'description' => 'The included Capture JS relies on Colorbox. You may want to disable it and use your own.',
        'default' => '1',
        'type' => 'checkbox',
        'screen' => 'ui',
        'validate' => '/[^0-9]+/i'
      )
    );

    $this->onPost();
    add_action('admin_menu', array(&$this,'admin_menu'));
  }

  /**
   * Method bound to register_activation_hook.
   */
  function activate() {
    foreach($this->fields as $field) {
      if (!empty($field['default'])) {
        if (get_option($field['name']) === false)
          update_option($field['name'], $field['default']);
      }
    }
  }

  /**
   * Method bound to the admin_menu action.
   */
  function admin_menu() {
    $optionsPage = add_menu_page(__('Janrain Capture'), __('Janrain Capture'),
      'manage_options', $this->name, array(&$this, 'options'));
    $dataPage = add_submenu_page($this->name, __('Janrain Capture'), __('Data Mapping'),
      'manage_options', $this->name . '_data', array(&$this, 'data'));
    $uiPage = add_submenu_page($this->name, __('Janrain Capture'), __('UI Options'),
      'manage_options', $this->name . '_ui', array(&$this, 'ui'));
  }

  /**
   * Method bound to the Janrain Capture options menu.
   */
  function options() {
    $args = new stdClass;
    $args->title = 'Janrain Capture Options';
    $args->action = 'options';
    $this->printAdmin($args);
  }

  /**
   * Method bound to the Janrain Capture data menu.
   */
  function data() {
    $args = new stdClass;
    $args->title = 'Data Mapping Options';
    $args->action = 'data';
    $this->printAdmin($args);
  }

  /**
   * Method bound to the Janrain Capture data menu.
   */
  function ui() {
    $args = new stdClass;
    $args->title = 'UI Options';
    $args->action = 'ui';
    $this->printAdmin($args);
  }

  /**
   * Method to print the admin page markup.
   *
   * @param stdClass $args
   *   Object with page title and action variables
   */
  function printAdmin($args) {
    echo <<<HEADER
<div id="message" class="{$this->postMessage['class']} fade">
  <p><strong>
    {$this->postMessage['message']}
  </strong></p>
</div>
<div class="wrap">
  <h2>{$args->title}</h2>
  <form method="post" id="{$this->name}_{$args->action}">
    <table class="form-table">
      <tbody>
HEADER;

    foreach($this->fields as $field) {
      if ($field['screen'] == $args->action)
        $this->printField($field);
    }

    echo <<<FOOTER
      </tbody>
    </table>
    <p class="submit">
      <input type="hidden" name="{$this->name}_action" value="{$args->action}" />
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
    $value = get_option($field['name']);
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
            <option></option>
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
    if (isset($_POST[$this->name . '_action'])) {
      foreach($this->fields as $field) {
        if ($field['screen'] != $_POST[$this->name . '_action'])
          continue;

        if (isset($_POST[$field['name']])) {
          $value = $_POST[$field['name']];
          if ($field['name'] == $this->name . '_address' || $field['name'] == $this->name . '_sso_address')
            $value = preg_replace('/^https?\:\/\//i', '', $value);
          if ($field['validate'])
            $value = preg_replace($field['validate'], '', $value);
          update_option($field['name'], $value);
        } else if ($field['type'] == 'checkbox') {
          $value = '0';
          update_option($field['name'], $value);
        }
      }
      $this->postMessage = array('class'=>'updated','message'=>'Configuration Saved');
    }
  }

}

