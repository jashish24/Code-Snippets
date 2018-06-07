<?php

namespace Drupal\consume_webservice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Form to delete a kexample { Confirmation needed to protect from spoofing and accidental clicks }
 */

class EditKexample extends FormBase {
  /**
   * {@inheritdoc}
   */
	public function getFormId() {
    return 'edit_kexample';
  }

	/**
	 * {@inheritdoc}
	 */
  public function buildForm(array $form, FormStateInterface $form_state, $kid = 0) {
		$form = [];
		//Loading kexample details
		$consumeks_config = \Drupal::config('consume_webservice.settings');
		$base_service = $consumeks_config -> get('base_service');
		$get_kexample_service = $consumeks_config -> get('get_kexample');
    $kexample_request_path = $base_service . str_replace('{id}', $kid, $get_kexample_service);
    $kexample_data = perform_webservice_get($kexample_request_path);

		if ($kexample_data) {
			$link_unlink = Link::createFromRoute($this -> t('Link / Unlink'),
				'consume_webservice.updatekexamples',
				['kid' => $kexample_data -> data -> id],
				['attributes' => [
						'class' => ['edit-cexample button button-action button--primary button--small'],
					],
				]
			);

			$delete_link = Link::createFromRoute($this -> t('Delete'),
				'consume_webservice.deletekexample',
				['kid' => $kexample_data -> data -> id],
				['attributes' => [
						'class' => ['delete-kexample button button-action button--primary button--small'],
					],
				]
			);

			$link_unlink = $link_unlink -> toRenderable();
			$delete_link = $delete_link -> toRenderable();

			$form['link_unlink'] = [
				'#type' => 'markup',
				'#markup' => render($link_unlink),
			];

			$form['delete_link'] = [
				'#type' => 'markup',
				'#markup' => render($delete_link),
			];

			$form['kexample_name'] = [
				'#type' => 'textfield',
				'#title' => $this -> t('kexample Name'),
				'#description' => $this -> t('Input kexample name here.'),
				'#default_value' => $kexample_data -> data -> kexample,
				'#required' => TRUE,
			];

			$form['kexample_status'] = [
				'#type' => 'textfield',
				'#title' => $this -> t('Status'),
				'#description' => $this -> t('Status of kexample.'),
				'#default_value' => $kexample_data -> data -> status,
				'#required' => TRUE,
			];

			$form['kexample_id'] = [
				'#type' => 'hidden',
				'#value' => $kexample_data -> data -> id,
			];

	    $form['actions']['#type'] = 'actions';
	    $form['actions']['submit'] = [
	      '#type' => 'submit',
	      '#value' => $this -> t('Update'),
	    ];
	  }
		else {
			drupal_set_message(t('There was error retrieving data. Please try again later.'), 'error');
		}

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form state values
    $form_values = $form_state -> getValues();
		$kexample_name = $form_values['kexample_name'];
		$kexample_id = $form_values['kexample_id'];
		$status = $form_values['kexample_status'];

		$consumeks_config = \Drupal::config('consume_webservice.settings');
		$base_service = $consumeks_config -> get('base_service');
		$update_kexample_service = $consumeks_config -> get('update_kexample');
    $request_path = $base_service . str_replace('{id}', $kexample_id, $update_kexample_service);

		$data = [
			'kexample' => $kexample_name,
			'status' => (int) $status,
		];
		$post_data = json_encode($data);

		$response = perform_webservice_post($request_path, $post_data, 'PUT');

		if ($response) {
			drupal_set_message(t('kexample has been updated.'));
		}
		else {
			drupal_set_message(t('There was an error updating kexample.'));
		}
  }
}
