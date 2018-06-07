<?php

namespace Drupal\consume_webservice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form to delete a kexample { Confirmation needed to protect from spoofing and accidental clicks }
 */

class CreateKexample extends FormBase {
  /**
   * {@inheritdoc}
   */
	public function getFormId() {
    return 'create_kexample';
  }

	/**
	 * {@inheritdoc}
	 */
  public function buildForm(array $form, FormStateInterface $form_state, $kid = 0) {
		$form['kexample_name'] = [
			'#type' => 'textfield',
			'#title' => $this -> t('kexample Name'),
			'#description' => $this -> t('Input kexample name here.'),
			'#required' => TRUE,
		];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this -> t('Create kexample'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form state values
    $form_values = $form_state -> getValues();
		$kexample = isset($form_values['kexample_name']) ? strip_tags($form_values['kexample_name']) : '';

		if ($kexample != '') {
			$consumeks_config = \Drupal::config('consume_webservice.settings');
			$base_service = $consumeks_config -> get('base_service');
			$create_kexample_service = $consumeks_config -> get('create_kexample');
			$request_path = $base_service . $create_kexample_service;

			$data = [
				'kexample' => $kexample,
			];
			$post_data = json_encode($data);

	    $response = perform_webservice_post($request_path, $post_data);

			if ($response) {
				drupal_set_message(t('Your kexample has been created with id: @id', ['@id' => $response -> id]));
			}
			else {
				drupal_set_message(t('There was an error creating a kexample.'));
			}
		}
  }
}
