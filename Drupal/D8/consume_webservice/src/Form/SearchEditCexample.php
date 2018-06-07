<?php

namespace Drupal\consume_webservice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Configuration settings for kexamples & cexample linking / unlinking
 */

class SearchEditCexample extends FormBase {
  /**
   * {@inheritdoc}
   */
	public function getFormId() {
    return 'search_edit_cexample';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
		$form['search_cexample'] = [
			'#type' => 'autocomplete_deluxe',
			'#title' => $this -> t('Search cexample'),
			'#autocomplete_deluxe_path' => '/search-cexamples',
			'#multiple' => TRUE,
			'#target_type' => NULL,
			'#element_validate' => [],
		];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this -> t('Edit cexample'),
    ];

    return $form;
  }

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		$form_values = $form_state -> getValues();
		$cexample_id = trim($form_values['search_cexample']);
		$check_single_data = explode(',', $cexample_id);
		if ($cexample_id == '') {
			$form_state -> setErrorByName('search_cexample', $this->t('Please select a cexample.'));
		}
		else if (count($check_single_data) > 1) {
			$form_state -> setErrorByName('search_cexample', $this->t('Please select only one cexample.'));
		}
	}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form state values
    $form_values = $form_state -> getValues();
		$cexample_id = trim($form_values['search_cexample']);
		$url_obj = Url::fromRoute('consume_webservice.editcexample', ['cid' => $cexample_id]);
		$alias = $url_obj -> toString();
    $redirect_response = new RedirectResponse($alias, 302);
    $redirect_response -> send();
  }
}
