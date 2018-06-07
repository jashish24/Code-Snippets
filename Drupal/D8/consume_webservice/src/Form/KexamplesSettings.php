<?php

namespace Drupal\consume_webservice\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration settings for kexamples & cexample linking / unlinking
 */

class KexamplesSettings extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
	public function getFormId() {
    return 'kexamples_admin_settings';
  }

  /**
  * {@inheritdoc}
  */
  protected function getEditableConfigNames() {
    return [
      'consume_webservice.settings',
    ];
  }

  /**
   * Function to return required textfield
   * @Param: $key => Key of the field
   * @Param: $title => Title or Label of the field
   * @Param: $description => Short description about the field
   */
  protected function get_textfield($key = '', $title = '', $description = '', $config) {
    return [
      '#type' => 'textfield',
      '#title' => $this -> t($title),
      '#description' => trim($description) != '' ? $this -> t($description) : '',
      '#default_value' => $config -> get($key),
    ];
  }

  /**
   * Function to return required textfield
   * @Param: $key => Key of the field
   * @Param: $title => Title or Label of the field
   * @Param: $description => Short description about the field
   * @Param: Select list options
   */
  protected function get_selectfield($key = '', $title = '', $description = '', $options = [], $config) {
    return [
      '#type' => 'select',
      '#title' => $this -> t($title),
      '#description' => trim($description) != '' ? $this -> t($description) : '',
      '#default_value' => $config -> get($key),
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
		$config = $this -> config('consume_webservice.settings');
    $form['base_service'] = $this -> get_textfield('base_service', 'Base Service URL', '', $config);
		$form['login_service'] = $this -> get_textfield('login_service', 'Login Service URL', '', $config);
		$form['token_email'] = $this -> get_textfield('token_email', 'Login Email', 'A token is needed to user any of the webservice', $config);

    $form['token_password'] = $this -> get_textfield('token_password', 'Login Password', 'A token is needed to user any of the webservice', $config);

		$form['cexample_settings'] = [
			'#type' => 'details',
			'#title' => $this -> t('cexample Webservices'),
			'#open' => FALSE,
		];

    $form['cexample_settings']['get_cexamples_service'] = $this -> get_textfield('get_cexamples_service', 'Get cexamples Service URL', 'Get all cexamples - pagination supported', $config);

    $form['cexample_settings']['get_cexamples_per_page'] = $this -> get_textfield('get_cexamples_per_page', 'Number Of cexamples Per Page', '', $config);

    $form['cexample_settings']['kexample'] = $this -> get_textfield('kexample', 'Create cexample Service URL', 'Create a new cexample', $config);

    $form['cexample_settings']['get_cexample'] = $this -> get_textfield('get_cexample', 'Get cexample Service URL', 'Get details of a cexample', $config);

    $form['cexample_settings']['update_cexample'] = $this -> get_textfield('update_cexample', 'Update cexample Service URL', 'Update a cexample', $config);

    $form['cexample_settings']['get_cexample_kexamples'] = $this -> get_textfield('get_cexample_kexamples', 'Get cexample kexamples Service URL', 'Get all kexamples linked to a cexample', $config);

    $form['cexample_settings']['link_cexample_kexamples'] = $this -> get_textfield('link_cexample_kexamples', 'Link cexample kexamples', 'Link a kexample to a cexample', $config);

    $form['cexample_settings']['unlink_cexample_kexamples'] = $this -> get_textfield('unlink_cexample_kexamples', 'Unlink cexample kexamples', 'Un-link a kexample to a cexample', $config);

		$form['kexamples_settings'] = [
			'#type' => 'details',
			'#title' => $this -> t('kexamples Webservices'),
			'#open' => FALSE,
		];

		$form['kexamples_settings']['get_kexamples_service'] = $this -> get_textfield('get_kexamples_service', 'Get kexamples Service URL', 'Get all kexamples - pagination available', $config);

    $form['kexamples_settings']['get_kexamples_per_page'] = $this -> get_textfield('get_kexamples_per_page', 'Number Of kexamples Per Page', '', $config);

    $form['kexamples_settings']['create_kexample'] = $this -> get_textfield('create_kexample', 'Create kexample Service URL', 'Create a new kexample', $config);

    $form['kexamples_settings']['get_kexample'] = $this -> get_textfield('get_kexample', 'Get kexample Service URL', 'Get a kexample by id', $config);

    $form['kexamples_settings']['update_kexample'] = $this -> get_textfield('update_kexample', 'Update kexample Service URL', 'Update a kexample', $config);

		$form['kexamples_settings']['delete_kexample'] = $this -> get_textfield('delete_kexample', 'Delete kexample Service URL', 'Delete a kexample', $config);

    $form['kexamples_settings']['get_kexample_cexamples'] = $this -> get_textfield('get_kexample_cexamples', 'Get kexample cexamples Service URL', 'Get all cexamples that are linked to this kexample', $config);

    $form['kexamples_settings']['link_kexample_cexamples'] = $this -> get_textfield('link_kexample_cexamples', 'Link kexample cexamples', 'Link a cexample to this kexample', $config);

    $form['kexamples_settings']['unlink_kexample_cexamples'] = $this -> get_textfield('unlink_kexample_cexamples', 'Unlink kexample cexamples', 'Un-link a cexample from this kexample', $config);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form state values
    $form_values = $form_state -> getValues();
    // Retrieve the configuration
    $settings_config = $this -> configFactory -> getEditable('consume_webservice.settings');
    unset($form_values['submit']);
    unset($form_values['form_build_id']);
    unset($form_values['form_token']);
    unset($form_values['form_id']);
    unset($form_values['op']);

    foreach ($form_values as $key => $value) {
      $settings_config -> set($key, $value);
    }

    $settings_config -> save();
		drupal_set_message($this -> t('Your configurations are saved.'));
  }
}
