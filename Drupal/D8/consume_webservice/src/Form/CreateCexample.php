<?php

namespace Drupal\consume_webservice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form to delete a kexample { Confirmation needed to protect from spoofing and accidental clicks }
 */

class CreateCexample extends FormBase {
  /**
   * {@inheritdoc}
   */
	public function getFormId() {
    return 'kexample';
  }

	/**
	 * {@inheritdoc}
	 */
  public function buildForm(array $form, FormStateInterface $form_state) {
		$form['cexample_name'] = [
			'#type' => 'textfield',
			'#title' => $this -> t('cexample Name'),
			'#description' => $this -> t('Input cexample name here.'),
			'#required' => TRUE,
		];

		$form['code'] = [
			'#type' => 'textfield',
			'#title' => $this -> t('Code'),
			'#description' => $this -> t('Input code here.'),
			'#required' => TRUE,
		];

		$form['short_name'] = [
			'#type' => 'textfield',
			'#title' => $this -> t('Short Name'),
			'#description' => $this -> t('Input short name here.'),
		];

		$form['type'] = [
			'#type' => 'textfield',
			'#title' => $this -> t('Type'),
			'#description' => $this -> t('Input type here.'),
			'#required' => TRUE,
		];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this -> t('Create cexample'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form state values
    $form_values = $form_state -> getValues();
		$cexample = isset($form_values['cexample_name']) ? strip_tags($form_values['cexample_name']) : '';
		$code = isset($form_values['code']) ? strip_tags($form_values['code']) : '';
		$short_name = isset($form_values['short_name']) ? strip_tags($form_values['short_name']) : '';
		$type = isset($form_values['type']) ? strip_tags($form_values['type']) : '';

		if ($cexample != '') {
			$consumeks_config = \Drupal::config('consume_webservice.settings');
			$base_service = $consumeks_config -> get('base_service');
			$kexample_service = $consumeks_config -> get('kexample');
			$request_path = $base_service . $kexample_service;

			$data = [
				'cexample' => $cexample,
				'code' => $code,
				'short_name' => $short_name,
				'type' => $type,
			];
			$post_data = json_encode($data);

	    $response = perform_webservice_post($request_path, $post_data);

			if ($response) {
				drupal_set_message(t('Your cexample has been created with id: @id', ['@id' => $response -> data -> id]));
			}
			else {
				drupal_set_message(t('There was an error creating a cexample.'));
			}
		}
  }
}
