<?php
/**
 * @file
 * Contains \Drupal\example_custom\Form\AddChildForm
 * Add child form definition
 */

namespace Drupal\example_custom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\profile\Entity\Profile;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AddChildForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  
  public function getFormId() {
    return 'add_child_account';
  }
  
  /**
   * {@inheritdoc}
   */
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    $form['first_name'] = array(
      '#type' => 'textfield',
      '#title' => t('First Name:'),
      '#size' => 60,
      '#required' => TRUE,
    );
    
    $form['last_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Last Name:'),
      '#size' => 60,
      '#required' => TRUE,
    );
    
    $form['child_password'] = array(
      '#type' => 'textfield',
      '#title' => t('Password:'),
      '#size' => 60,
    );
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this -> t('Create Account'),
      '#button_type' => 'primary',
    );
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    $number_of_child_accounts = $current_user -> get('field_child_account_left') -> getValue();
    $current_child_num = isset($number_of_child_accounts[0]) ? $number_of_child_accounts[0]['value'] : 0;
    
    // Get form state values
    $form_values = $form_state -> getValues();
    
    //Sanitize for SQL injection or other attacks
    $first_name = trim(strip_tags($form_values['first_name']));
    $last_name = trim(strip_tags($form_values['last_name']));
    $password = trim(strip_tags($form_values['child_password']));
    
    // Generate four digit password if not entered by Child Account Owner User
    $password = ($password != '') ? $password : rand(1000, 9999);
    
    //Generate random email id
    $email = 'childmail' . time() . '@example.com';
    
    // Create username based on child first name & last name
    $username = str_replace(' ', '_', strtolower($first_name)) . strtolower($last_name[0]) . rand(1, 100000);
    
    // Create user object.
    $child_user = User::create();

    //Mandatory settings
    $child_user -> setPassword($password);
    $child_user -> enforceIsNew();
    $child_user -> setEmail($email);
    $child_user -> setUsername($username); //This username must be unique and accept only a-Z,0-9, - _ @ .
    $child_user -> addRole('child'); //E.g: authenticated
    $child_user -> set('field_first_name', $first_name);
    $child_user -> set('field_last_name', $last_name);
    $child_user -> activate();
    
    //Set Language
    $language_interface = \Drupal::languageManager() -> getCurrentLanguage();
    $child_user -> set('langcode', $language_interface -> getId());
    $child_user -> set('preferred_langcode', $language_interface -> getId());
    $child_user -> set('preferred_admin_langcode', $language_interface -> getId());
    
    $child_user -> save();
    $child_uid = $child_user -> id();
    
    if ($child_uid > 0) {
      //Creating profile object
      $profile_values = [
        'type' => 'child',
        'uid' => $child_uid,
        'status' => 1,
        'field_first_password' => $password,
        'field_parent' => [$current_user -> id()], // Attaching to Parent (Authenticated User)
      ];
      
      //Creating Child Profile
      $child_profile = Profile::create($profile_values);
      $child_profile -> save();
      
      // Decreasing bought child account number by 1
      $current_child_num--;
      
      //Checking for negative value (may break child account number when buying child account, setting to zero if less than zero)
      $current_child_num = ($current_child_num > 0) ? $current_child_num : 0;
      $number_of_child_accounts[0]['value'] = (int) $current_child_num;
      
      // updating child accounts counts
      $current_user -> set('field_child_account_left', $number_of_child_accounts);
      // saving user for permanent change
      $current_user -> save();
      
      drupal_set_message(t('Child account has been created. Please note username(@username) and password(@password)', array('@username' => $username, '@password' => $password)));
      
      $response = new RedirectResponse('/user', 301);
      $response -> send();
    }
    else {
      drupal_set_message(t('There was an error creating Child Account. Please try again later. Contact administrator if problem persists.'));
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    $number_of_child_accounts = $current_user -> get('field_child_account_left') -> getValue();
    $current_child_num = isset($number_of_child_accounts[0]) ? $number_of_child_accounts[0]['value'] : 0;
    // Get form state values
    $form_values = $form_state -> getValues();
    
    if (!is_numeric($current_child_num) || $current_child_num <= 0) {
      $form_state -> setErrorByName('first_name', t('Your available child accounts are over. Please buy some more from our online store.'));
    }
    
    $password = trim(strip_tags($form_values['child_password']));
    
    if ($password != '' && !is_numeric($password) && ($password < 1000 || $password > 9999)) {
      $form_state -> setErrorByName('child_password', t('Please input valid password between 1000 and 9999. Leave blank to get system generated password.'));
    }
  }
  
  /**
   * Function to generate user access based on number of child account bought by a authenticated user
   */
  
  public function access() {
    $user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    $roles = $user -> getRoles(); // TRUE, getting only unlocked roles (authenticated is a locked role)

    if ($user -> hasRole('administrator', $roles) || $user -> hasRole('school', $roles)) {
      return AccessResult::allowed();
    }
    else if ($user -> hasField('field_child_account_left') && $user -> hasRole('authenticated', $roles)) {
      $number_of_child_accounts = $user -> get('field_child_account_left') -> getValue();
      $current_num = isset($number_of_child_accounts[0]) ? $number_of_child_accounts[0]['value'] : 0;
      
      if ($current_num > 0) {
        return AccessResult::allowed();
      }
    }
    
    drupal_set_message(t('You don\'t have enough Child Accounts left. Please visit our online store to buy more.'));
    return AccessResult::forbidden();
  }
}