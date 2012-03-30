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
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9\._-]+/i'
      ),
      array(
        'name' => $this->name . '_client_id',
        'title' => 'API Client ID',
        'description' => 'Your Capture Client ID',
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9]+/i'
      ),
      array(
        'name' => $this->name . '_client_secret',
        'title' => 'API Client Secret',
        'description' => 'Your Capture Client Secret',
        'default' => '',
        'type' => 'text',
        'screen' => 'options',
        'validate' => '/[^a-z0-9]+/i'
      ),
      array(
        'name' => $this->name . '_sso_address',
        'title' => 'SSO Application Domain',
        'description' => 'Your Jarain Federate SSO domain (e.g. demo.janrainsso.com)',
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
      )
    );

    $this->onPost();
    add_action('admin_menu', array(&$this,'admin_menu'));
  }

  /**
   * Method bound to the admin_menu action.
   */
  function admin_menu() {
    $optionsPage = add_menu_page(__('Janrain Capture'), __('Janrain Capture'),
      'manage_options', $this->name, array($this, 'options'));
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
    $value = $value ? $value : $field['default'];
    switch ($field['type']){
      case 'text':
        echo <<<TEXT
        <tr valign="top">
          <th scope="row">{$field['title']}</th>
          <td>
            <input type="text" name="{$field['name']}" value="$value" style="width:200px" />
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
TEXT;
        break;
      case 'long-text':
        echo <<<LONGTEXT
        <tr valign="top">
          <th scope="row">{$field['title']}</th>
          <td>
            <input type="text" name="{$field['name']}" value="$value" style="width:400px" />
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
LONGTEXT;
        break;
      case 'password':
        echo <<<PASSWORD
        <tr valign="top">
          <th scope="row">{$field['title']}</th>
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
        <tr valign="top">
          <th scope="row">{$field['title']}</th>
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
      case 'title':
        echo <<<TITLE
        <tr valign="top">
          <td colspan="2">
            <h2 class="title">{$field['title']}</h2>
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
        if (isset($_POST[$field['name']])) {
          $value = $_POST[$field['name']];
          if ($field['name'] == $this->name . '_address' || $field['name'] == $this->name . '_sso_address')
            $value = preg_replace('/^https?\:\/\//i', '', $value);
          if ($field['validate'])
            $value = preg_replace($field['validate'], '', $value);
          update_option($field['name'], $value);
        }
      }
      $this->postMessage = array('class'=>'updated','message'=>'Configuration Saved');
    }
  }

}

