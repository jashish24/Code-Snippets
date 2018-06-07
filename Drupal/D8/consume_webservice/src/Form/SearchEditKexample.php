<?php

namespace Drupal\consume_webservice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Configuration settings for kexamples & cexample linking / unlinking
 */

class SearchEditKexample extends FormBase {
  /**
   * {@inheritdoc}
   */
	public function getFormId() {
    return 'search_edit_kexample';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
		$form['search_kexample'] = [
			'#type' => 'autocomplete_deluxe',
			'#title' => $this -> t('Search kexample'),
			'#autocomplete_deluxe_path' => '/search-kexamples',
			'#multiple' => TRUE,
			'#target_type' => NULL,
			'#element_validate' => NULL,
		];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this -> t('Edit kexample'),
    ];

    return $form;
  }

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		$form_values = $form_state -> getValues();
		$kexample_id = trim($form_values['search_kexample']);
		$check_single_data = explode(',', $kexample_id);
		if ($kexample_id == '') {
			$form_state -> setErrorByName('search_kexample', $this->t('Please select a kexample.'));
		}
		else if (count($check_single_data) > 1) {
			$form_state -> setErrorByName('search_kexample', $this->t('Please select only one kexample.'));
		}
	}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
		// Get form state values
    $form_values = $form_state -> getValues();
		$kexample_id = trim($form_values['search_kexample']);
		$check_single_data = explode(',', $kexample_id);
		$url_obj = Url::fromRoute('consume_webservice.editkexample', ['kid' => $kexample_id]);
		$alias = $url_obj -> toString();
    $redirect_response = new RedirectResponse($alias, 302);
    $redirect_response -> send();
  }
}
