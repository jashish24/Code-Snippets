<?php

namespace Drupal\consume_webservice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Configuration settings for kexamples & cexample linking / unlinking
 */

class UpdateKexample extends FormBase {
  /**
   * {@inheritdoc}
   */
	public function getFormId() {
    return 'update_cexample';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $kid = 0) {
    $consumeks_config = \Drupal::config('consume_webservice.settings');
    $base_service = $consumeks_config -> get('base_service');
    $get_kexample_cexamples = $consumeks_config -> get('get_kexample_cexamples');

    $request_path = $base_service . str_replace('{id}', $kid, $get_kexample_cexamples);
    $cexamples_data = perform_webservice_get($request_path);

    //Loading cexample details
    $get_kexample_service = $consumeks_config -> get('get_kexample');
    $kexample_request_path = $base_service . str_replace('{id}', $kid, $get_kexample_service);
    $kexample_data = perform_webservice_get($kexample_request_path);
    $kexample_name = $kexample_data -> data -> kexample . ' (id: ' . $kexample_data -> data -> id . ')';

    $cexamples_options = [];

    foreach ($cexamples_data -> data as $key => $cexample) {
      $type = $cexample -> type;
      $cexamples_options[$cexample -> id] = $cexample -> cexample . ' (<b>type:</b> ' . $type . ')';
    }

    $form = [];

		$edit_link = Link::createFromRoute($this -> t('Edit'),
			'consume_webservice.editkexample',
			['kid' => $kexample_data -> data -> id],
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

    $form['kexample_name'] = [
      '#type' => 'fieldset',
      '#title' => $this -> t('kexample Name: ') . $kexample_name,
    ];

		$form['kexample_name']['add_cexamples'] = [
			'#type' => 'autocomplete_deluxe',
			'#title' => $this -> t('Add cexample'),
			'#autocomplete_deluxe_path' => '/search-cexamples',
			'#multiple' => true,
			'#target_type' => NULL,
			'#element_validate' => [],
			'#description' => $this -> t('Search and select cexamples that you want to add to this kexample.'),
		];

    $form['kexample_name']['cexamples'] = [
      '#type' => 'checkboxes',
      '#options' => $cexamples_options,
      '#title' => $this -> t('Cexamples'),
      '#default_value' => array_keys($cexamples_options),
			'#description' => $this -> t('Uncheck cexamples that you want to unlink from this kexample.'),
    ];

    $form['kexample_name']['kexample_id'] = [
      '#type' => 'hidden',
      '#value' => $kid,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this -> t('Update kexample'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
		// Get form state values
    $form_values = $form_state -> getValues();
		$kid = isset($form_values['kexample_id']) ? strip_tags($form_values['kexample_id']) : 0;

		// kexamples linking to cexample
    $add_cexamples = isset($form_values['add_cexamples']) ? explode(',', strip_tags($form_values['add_cexamples'])) : [];

		$consumeks_config = \Drupal::config('consume_webservice.settings');
		$base_service = $consumeks_config -> get('base_service');
		$link_kexample_cexamples = $consumeks_config -> get('link_kexample_cexamples');
		$unlink_kexample_cexamples = $consumeks_config -> get('unlink_kexample_cexamples');

		if (!empty($add_cexamples) && trim($add_cexamples[0]) != '') {
			$request_path = $base_service . str_replace('{id}', $kid, $link_kexample_cexamples);
			$linked_cexamples_count = 0;
			$linked_cexamples = [];

			foreach ($add_cexamples as $cexample_id) {
				$data = [
					'cexample_id' => (int) $cexample_id,
				];
				$post_data = json_encode($data);

		    $response = perform_webservice_post($request_path, $post_data);

				if ($response) {
					$linked_cexamples_count++;
					$linked_cexamples[] = $cexample_id;
				}
			}

			drupal_set_message($this -> t('@count cexamples (ids: @ids) are successfully linked.', ['@count' => $linked_cexamples_count, '@ids' => implode(',', $linked_cexamples)]));
		}

		//kexamples unlinking to cexample
		$request_path = $base_service . str_replace('{id}', $kid, $unlink_kexample_cexamples);
		$kexample_cexamples = $form_values['cexamples'];
		$unlinked_cexamples_count = 0;
		$unlinked_cexamples = [];

		foreach ($kexample_cexamples as $cexample_id => $value) {
			if ($value == 0) {
				$data = [
					'cexample_id' => (int) $cexample_id,
				];
				$post_data = json_encode($data);

				// Third argument DELETE request
		    $response = perform_webservice_post($request_path, $post_data, 'DELETE');

				if ($response) {
					$unlinked_cexamples_count++;
					$unlinked_cexamples[] = $cexample_id;
				}
			}
		}

		if ($unlinked_kexamples_count > 0) {
			drupal_set_message($this -> t('@count cexamples (ids: @ids) are successfully unlinked.', ['@count' => $unlinked_cexamples_count, '@ids' => implode(',', $unlinked_cexamples)]));
		}
  }
}
