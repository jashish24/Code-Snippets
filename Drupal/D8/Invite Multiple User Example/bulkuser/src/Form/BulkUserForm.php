<?php

namespace Drupal\bulkuser\Form;

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a Bulk User Form.
 */
class BulkUserForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'bulkuserform';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];
    // Text area to provide the list of email ids separated by comma.
    $form['emailids'] = [
      '#type' => 'textarea',
      '#title' => 'Email addresses',
      '#size' => 20,
      '#description' => t('Enter the email addresses of users to invite, separated by commas.'),
      '#required' => TRUE,
    ];

    $options = user_role_names(TRUE);
    // Removing Authenticated user role from the options.
    unset($options[DRUPAL_AUTHENTICATED_RID]);
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Which roles should the users be assigned to?'),
      '#description' => $this->t('Users always receive the <em>authenticated user</em> role.'),
      '#options' => $options,
    ];

    $form['submit_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Invite users'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $emails = $form_state->getValue('emailids');
    $array = [];
    $array = explode(",", $emails);
    foreach ($array as $email) {
      $email = trim($email);
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $form_state->setErrorByName('emailids', $this->t('Please enter valid list of email address.  "' . $email . '" is not in right format'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $emails = $form_state->getValue('emailids');
    $roles = array_filter($form_state->getValue('roles'));
    $array = [];
    $array = explode(",", $emails);

    foreach ($array as $email) {
      $email = trim($email);
      $index = strpos($email, '@');
      $user_exist = user_load_by_mail($email);
      if (!$user_exist) {
        // User doesn't exist.
        $username = substr($email, 0, $index);
        $name_exist = user_load_by_name($username);
        if ($name_exist) {
          $username = $username . '_' . rand(10, 99);
        }
        // New User is created and saved in the database.
        $user = User::create();
        $user->enforceIsNew();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->activate();
        $user->set('roles', array_values($roles));
        $res = $user->save();
        if (_user_mail_notify('register_no_approval_required', $user)) {
          drupal_set_message($this->t('A welcome message with further instructions has been emailed to your email address: ' . $email));
        }
      }
      else {
        drupal_set_message("Email:" . $email . " already exists");
      }
    }
  }

}
