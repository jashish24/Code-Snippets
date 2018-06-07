<?php

namespace Drupal\consume_webservice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Configuration settings for kexamples & cexample linking / unlinking
 */

class UpdateCexample extends FormBase {
  /**
   * {@inheritdoc}
   */
	public function getFormId() {
    return 'update_cexample';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cid = 0) {
    $consumeks_config = \Drupal::config('consume_webservice.settings');
    $base_service = $consumeks_config -> get('base_service');
    $get_cexample_kexamples = $consumeks_config -> get('get_cexample_kexamples');

    $request_path = $base_service . str_replace('{id}', $cid, $get_cexample_kexamples);
    $kexamples_data = perform_webservice_get($request_path);

    //Loading cexample details
    $get_cexample_service = $consumeks_config -> get('get_cexample');
    $cexample_request_path = $base_service . str_replace('{id}', $cid, $get_cexample_service);
    $cexample_data = perform_webservice_get($cexample_request_path);
    $cexample_name = $cexample_data -> data -> cexample . ' (type: ' . $cexample_data -> data -> type . ')';

    $kexamples_options = [];

    foreach ($kexamples_data -> data as $key => $kexample) {
      $updated = date('Y-m-d H:i:s', strtotime($kexample -> last_modified));
      $kexamples_options[$kexample -> id] = $kexample -> kexample . ' (<b>Updated:</b> ' . $updated . ')';
    }

    $form = [];

		$edit_link = Link::createFromRoute($this -> t('Edit'),
			'consume_webservice.editcexample',
			['cid' => $cexample_data -> data -> id],
			['attributes' => [
					'class' => ['edit-cexample button button-action button--primary button--small'],
				],
			]
		);

		$edit_link = $edit_link -> toRenderable();

		$form['edit_link'] = [
			'#type' => 'markup',
			'#markup' => render($edit_link),
		];

    $form['cexample_name'] = [
      '#type' => 'fieldset',
      '#title' => $this -> t('cexample Name: ') . $cexample_name,
    ];

		$form['cexample_name']['add_kexamples'] = [
			'#type' => 'autocomplete_deluxe',
			'#title' => $this -> t('Add kexamples'),
			'#autocomplete_deluxe_path' => '/search-kexamples',
			'#multiple' => true,
			'#target_type' => NULL,
			'#element_validate' => [],
			'#description' => $this -> t('Search and select kexamples that you want to add to this cexample.'),
		];

    $form['cexample_name']['kexamples'] = [
      '#type' => 'checkboxes',
      '#options' => $kexamples_options,
      '#title' => $this -> t('kexamples'),
      '#default_value' => array_keys($kexamples_options),
			'#description' => $this -> t('Uncheck kexamples that you want to unlink from this cexample.'),
    ];

    $form['cexample_name']['cexample_id'] = [
      '#type' => 'hidden',
      '#value' => $cid,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this -> t('Update cexample'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form state values
    $form_values = $form_state -> getValues();
		$cid = isset($form_values['cexample_id']) ? strip_tags($form_values['cexample_id']) : 0;

		// kexamples linking to cexample
    $add_kexamples = isset($form_values['add_kexamples']) ? explode(',', strip_tags($form_values['add_kexamples'])) : [];

		$consumeks_config = \Drupal::config('consume_webservice.settings');
		$base_service = $consumeks_config -> get('base_service');
		$link_cexample_kexamples = $consumeks_config -> get('link_cexample_kexamples');
		$unlink_cexample_kexamples = $consumeks_config -> get('unlink_cexample_kexamples');

		if (!empty($add_kexamples) && trim($add_kexamples[0]) != '') {
			$request_path = $base_service . str_replace('{id}', $cid, $link_cexample_kexamples);
			$linked_kexamples_count = 0;
			$linked_kexamples = [];

			foreach ($add_kexamples as $kexample_id) {
				$data = [
					'kexample_id' => (int) $kexample_id,
				];
				$post_data = json_encode($data);

		    $response = perform_webservice_post($request_path, $post_data);

				if ($response) {
					$linked_kexamples_count++;
					$linked_kexamples[] = $kexample_id;
				}
			}

			drupal_set_message($this -> t('@count kexamples (ids: @ids) are successfully linked.', ['@count' => $linked_kexamples_count, '@ids' => implode(',', $linked_kexamples)]));
		}

		//kexamples unlinking to cexample
		$request_path = $base_service . str_replace('{id}', $cid, $unlink_cexample_kexamples);
		$cexample_kexamples = $form_values['kexamples'];
		$unlinked_kexamples_count = 0;
		$unlinked_kexamples = [];

		foreach ($cexample_kexamples as $kexample_id => $value) {
			if ($value == 0) {
				$data = [
					'kexample_id' => (int) $kexample_id,
				];
				$post_data = json_encode($data);

				// Third argument TRUE for DELETE request
		    $response = perform_webservice_post($request_path, $post_data, 'DELETE');

				if ($response) {
					$unlinked_kexamples_count++;
					$unlinked_kexamples[] = $kexample_id;
				}
			}
		}

		if ($unlinked_kexamples_count > 0) {
			drupal_set_message($this -> t('@count kexamples (ids: @ids) are successfully unlinked.', ['@count' => $unlinked_kexamples_count, '@ids' => implode(',', $unlinked_kexamples)]));
		}
  }
}
