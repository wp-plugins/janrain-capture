<?php

/**
 * @file
 *
 * Admin interface for plugin options
 *
 */
class JanrainCaptureAdmin {

  private $postMessage;
  private $fields;

  function  __construct() {
    $this->postMessage = array('class'=>'', 'message'=>'');
    $this->fields = array(
      array(
        'name' => 'janrain_capture_address',
        'title' => 'Application Domain',
        'description' => 'Your Capture application domain (e.g. demo.janraincapture.com)',
        'default' => '',
        'type' => 'text',
        'screen' => 'options'
      ),
      array(
        'name' => 'janrain_capture_client_id',
        'title' => 'API Client ID',
        'description' => 'Your Capture Client ID',
        'default' => '',
        'type' => 'text',
        'screen' => 'options'
      ),
      array(
        'name' => 'janrain_capture_client_secret',
        'title' => 'API Client Secret',
        'description' => 'Your Capture Client Secret',
        'default' => '',
        'type' => 'text',
        'screen' => 'options'
      ),
      array(
        'name' => 'janrain_capture_sso_address',
        'title' => 'SSO Application Domain',
        'description' => 'Your Jarain Federate SSO domain (e.g. demo.janrainsso.com)',
        'default' => '',
        'type' => 'text',
        'screen' => 'options'
      )
    );
  }

  function admin_menu() {
    $optionsPage = add_menu_page(__('Janrain Capture'), __('Janrain Capture'),
      'manage_options', 'janrain_capture', array($this, 'options'));
    $uiPage = add_submenu_page('janrain_capture', __('UI Options'), __('UI Options'),
      'manage_options', 'janrain_capture_ui', array($this,'options_ui'));

    //add_action('admin_print_scripts-' . $regPage, array($this,'admin_reg_scripts'));
    //add_action('admin_print_styles-' . $regPage, array($this,'admin_reg_styles'));
  }

  function options() {
    $args = new stdClass;
    $args->title = 'Janrain Capture Options';
    $args->action = 'options';
    $this->printAdmin($args);
  }

  function options_ui() {
    $args = new stdClass;
    $args->title = 'Janrain Capture UI Options';
    $args->action = 'options_ui';
    $this->printAdmin($args);
  }

  function printAdmin($args) {
    echo <<<HEADER
<div id="message" class="{$this->postMessage['class']} fade">
  <p><strong>
    {$this->postMessage['message']}
  </strong></p>
</div>
<div class="wrap">
  <h2>{$args->title}</h2>
  <form method="post" id="janrain_capture_{$args->action}">
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
      <input type="hidden" name="janrain_capture_action" value="{$args->action}" />
      <input type="submit" class="button-primary" value="Save Changes" />
    </p>
  </form>
</div>
FOOTER;
  }

  function printField($field) {
    $value = get_option($field['name']);
    $value = $value ? $value : $field['default'];
    switch ($field['type']){
      case 'text':
        echo <<<TEXT
        <tr valign="top">
          <th scope="row">{$field['title']}</th>
          <td>
            <input type="text" name="{$field['name']}" value="$value" style="width:150px" />
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
    }
  }

  public function onPost() {
    if($_POST['janrain_capture_action']){
      foreach($this->fields as $field){
        if (isset($_POST[$field['name']])) {
          $value = $_POST[$field['name']];
          if ($field['name'] == 'janrain_capture_address')
            $value = preg_replace('/^https?\:\/\//i', '', $value);
          update_option($field['name'], $value);
        }
      }
      $this->postMessage = array('class'=>'updated','message'=>'Configuration Saved');
    }
  }

}
