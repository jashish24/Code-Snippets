<?php

namespace Drupal\consume_webservice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Form to delete a kexample { Confirmation needed to protect from spoofing and accidental clicks }
 */

class EditCexample extends FormBase {
  /**
   * {@inheritdoc}
   */
	public function getFormId() {
    return 'edit_cexample';
  }

	/**
	 * {@inheritdoc}
	 */
  public function buildForm(array $form, FormStateInterface $form_state, $cid = 0) {
		$form = [];
		//Loading cexample details
		$consumeks_config = \Drupal::config('consume_webservice.settings');
		$base_service = $consumeks_config -> get('base_service');
    $get_cexample_service = $consumeks_config -> get('get_cexample');
    $cexample_request_path = $base_service . str_replace('{id}', $cid, $get_cexample_service);
    $cexample_data = perform_webservice_get($cexample_request_path);

		if ($cexample_data) {
			$link_unlink = Link::createFromRoute($this -> t('Link / Unlink'),
				'consume_webservice.updatecexamples',
				['cid' => $cexample_data -> data -> id],
				['attributes' => [
						'class' => ['edit-cexample button button-action button--primary button--small'],
					],
				]
			);

			$link_unlink = $link_unlink -> toRenderable();

			$form['link_unlink'] = [
				'#type' => 'markup',
				'#markup' => render($link_unlink),
			];

			$form['cexample_name'] = [
				'#type' => 'textfield',
				'#title' => $this -> t('cexample Name'),
				'#description' => $this -> t('Input cexample name here.'),
				'#default_value' => $cexample_data -> data -> cexample,
				'#required' => TRUE,
				'#size' => 60,
	  		'#maxlength' => 255,
			];

			$form['code'] = [
				'#type' => 'textfield',
				'#title' => $this -> t('Code'),
				'#description' => $this -> t('Input code here.'),
				'#default_value' => $cexample_data -> data -> code,
				'#required' => TRUE,
			];

			$form['short_name'] = [
				'#type' => 'textfield',
				'#title' => $this -> t('Short Name'),
				'#description' => $this -> t('Input short name here.'),
				'#default_value' => $cexample_data -> data -> short_name,
			];

			$form['type'] = [
				'#type' => 'textfield',
				'#title' => $this -> t('Type'),
				'#description' => $this -> t('Input type here.'),
				'#default_value' => $cexample_data -> data -> type,
				'#required' => TRUE,
			];

			$form['level'] = [
				'#type' => 'textfield',
				'#title' => $this -> t('Level'),
				'#description' => $this -> t('Input level here.'),
				'#default_value' => $cexample_data -> data -> level,
				'#required' => TRUE,
			];

			$form['blocked_for_segment_explorer'] = [
				'#type' => 'textfield',
				'#title' => $this -> t('Blocked for segment explorer'),
				'#default_value' => $cexample_data -> data -> blocked_for_segment_explorer,
				'#required' => TRUE,
			];

			$form['cexample_id'] = [
				'#type' => 'hidden',
				'#value' => $cexample_data -> data -> id,
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
		$cexample_name = $form_values['cexample_name'];
		$cexample_id = $form_values['cexample_id'];
		$code = $form_values['code'];
		$short_name = $form_values['short_name'];
		$type = $form_values['type'];
		$level = $form_values['level'];
		$blocked_for_segment_explorer = $form_values['blocked_for_segment_explorer'];

		$consumeks_config = \Drupal::config('consume_webservice.settings');
		$base_service = $consumeks_config -> get('base_service');
		$update_cexample_service = $consumeks_config -> get('update_cexample');
    $request_path = $base_service . str_replace('{id}', $cexample_id, $update_cexample_service);

		$data = [
			'cexample' => $cexample_name,
			'code' => $code,
			'type' => $type,
			'short_name' => $short_name,
			'blocked_for_segment_explorer' => (int) $blocked_for_segment_explorer,
			'level' => (int) $level,
		];
		$post_data = json_encode($data);

		$response = perform_webservice_post($request_path, $post_data, 'PUT');

		if ($response) {
			drupal_set_message(t('cexample has been updated.'));
		}
		else {
			drupal_set_message(t('There was an error updating cexample.'));
		}
  }
}
