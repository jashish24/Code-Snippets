<?php

namespace Drupal\consume_webservice\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form to delete a kexample { Confirmation needed to protect from spoofing and accidental clicks }
 */

class DeleteKexample extends ConfirmFormBase {
  /**
   * {@inheritdoc}
   */
	public function getFormId() {
    return 'delete_kexample';
  }

	/**
	* ID of the item to delete.
	*
	* @var int
	*/
	protected $id;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $kid = 0) {
		//@kid - kexample id to be deleted
		$this -> id = $kid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
		$kexample_id = $this -> id;
		$consumeks_config = \Drupal::config('consume_webservice.settings');
		$base_service = $consumeks_config -> get('base_service');
		$delete_kexample_service = $consumeks_config -> get('delete_kexample');
		$request_path = $base_service . str_replace('{id}', $kexample_id, $delete_kexample_service);

		$data = [];
		$post_data = json_encode($data);

		$response = perform_webservice_post($request_path, $post_data, 'DELETE');

		drupal_set_message($this -> t('kexample has been deleted.'));
    $url_obj = Url::fromRoute('consume_webservice.listkexamples');
		$alias = $url_obj -> toString();
    $redirect_response = new RedirectResponse($alias, 302);
    $redirect_response -> send();
  }

	/**
	* {@inheritdoc}
	*/
	public function getCancelUrl() {
		return new Url('consume_webservice.listkexamples');
	}

	/**
	* {@inheritdoc}
	*/
	public function getQuestion() {
		return t('Do you want to delete kexample with id: @id?', ['@id' => $this -> id]);
	}
}
