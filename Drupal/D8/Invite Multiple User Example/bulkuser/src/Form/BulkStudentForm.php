<?php

namespace Drupal\bulkuser\Form;

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\Profile;
use Drupal\bulkuser\Controller\AjaxHandler;

/**
 * Implements a Bulk User Form for inviting users with student role (without email).
 */
class BulkStudentForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'bulkstudentform';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $ajaxhandler = new AjaxHandler();
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    $current_user_roles = $current_user -> getRoles(TRUE);

    //Genrating list of all the Schools registered
    $school_list = [];

    $user_storage = \Drupal::service('entity_type.manager') -> getStorage('user');
    $ids = $user_storage -> getQuery()
      -> condition('status', 1)
      -> condition('roles', 'school')
      -> execute();

    if (!empty($ids)) {
      $school_users = \Drupal::service('entity_type.manager') -> getStorage('user') -> loadMultiple($ids);

      foreach ($school_users as $uid => $user_obj) {
        $profile = \Drupal::entityManager() -> getStorage('profile') -> loadByUser($user_obj, 'school');

        if ($profile) {
          $school_name = $profile -> get('field_school_name') -> getValue();
          $school_list[$uid] = isset($school_name[0]) ? $school_name[0]['value'] : $user_obj -> get('name') -> value;
        }
        else {
          $school_list[$uid] = $user_obj -> get('name') -> value;
        }
      }
    }

    // School selection access to certain roles
    $content_access_roles = ['administrator', 'manager'];
    $school_name_disabled = TRUE;
    $school_name_default_value = '';

    if (array_intersect($current_user_roles, $content_access_roles)) {
      $school_name_disabled = FALSE;
    }

    if (in_array('school', $current_user_roles)) {
      $school_name_default_value = $current_user -> get('uid') -> value;
    }

    $classrooms_options = [];
    //Generating list of classes based on current school user
    if ($school_name_default_value > 0) {
      $classrooms_list_db = \Drupal::database();
      $classrooms_list_query = $classrooms_list_db -> select('profile__field_classrooms', 'pfc');
      $classrooms_list_query -> join('profile', 'p', 'p.profile_id = pfc.entity_id');
      $classrooms_list_query -> join('node_field_data', 'n', 'n.nid = pfc.field_classrooms_target_id');
      $classrooms_list_query -> condition('pfc.deleted', 0);
      $classrooms_list_query -> condition('n.status', 1);
      $classrooms_list_query -> condition('p.uid', $school_name_default_value);
      $classrooms_list_query -> fields('n', ['title', 'nid']);
      $classrooms_list = $classrooms_list_query -> execute() -> fetchAllAssoc('nid');

      foreach ($classrooms_list as $classroom_id => $classroom) {
        $classrooms_options[$classroom -> nid] = $classroom -> title;
      }
    }

    $form = [];
    $form['school_name'] = [
      '#type' => 'select',
      '#title' => t('School'),
      '#description' => t('Choose the school for the new students'),
      '#required' => TRUE,
      '#options' => $school_list,
      '#disabled' => $school_name_disabled,
      '#default_value' => $school_name_default_value,
      '#ajax' => [
        'callback' => [$ajaxhandler, 'rebuildClasroomsList'],
        'wrapper' => 'classrooms-wrapper',
      ],
    ];

    $form['student_classroom'] = [
      '#type' => 'select',
      '#title' => t('Classroom'),
      '#description' => t('Choose a classroom for the new students'),
      '#required' => TRUE,
      '#options' => $classrooms_options,
      '#prefix' => '<div id="classrooms-wrapper">',
      '#suffix' => '</div>',
    ];

    // Text area to provide the list of email ids separated by comma.
    $form['students_info'] = [
      '#type' => 'textarea',
      '#title' => t('Firstname, Lastname, optional@email.address'),
      '#size' => 20,
      '#description' => t('Enter the students information, one user per line. The first and last name are mandatory. Email address is optional and will be used to recover a lost password. For example: <br><b>John, Appleseed<br>Pawan, Kumar, pawan.kumar@email.com</b><br>would create 2 accounts, one for a student named <i>John Appleseed</i> with no email address, and one for <i>Pawan Kumar</i> with email address <i>pawan.kumar@email.com</i>'),
      '#required' => TRUE,
    ];

    $form['submit_button'] = [
      '#type' => 'submit',
      '#value' => $this -> t('Create accounts'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      $school_user_id = $form_state -> getValue('school_name');
      $school_user = \Drupal::entityTypeManager() -> getStorage('user') -> load($school_user_id);
      $school_user_roles = $school_user -> getRoles(TRUE);
      $students_info = $form_state -> getValue('students_info');
      $student_classroom = $form_state -> getValue('student_classroom');

      $invited_users = 0;
      $students_info_fragments = [];
      $students_info_fragments = array_filter(explode(PHP_EOL, $students_info), function($value) { return trim($value); }); // array_filter will remove empty lines

      $error_messages = [];

      foreach ($students_info_fragments as $key => $student_info_string) {
        $student_info = explode(',', $student_info_string);
        $first_name = isset($student_info[0]) ? strip_tags(trim($student_info[0])) : '';
        $last_name = isset($student_info[1]) ? strip_tags(trim($student_info[1])) : '';
        $email = isset($student_info[2]) ? strip_tags(trim($student_info[2])) : '';

        if ($first_name == '') {
          $error_messages[] = $this -> t('Row #@num: Firstname is required. ', ['@num' => $key+1]);
        }

        if ($last_name == '') {
          $error_messages[] = $this -> t('Row #@num: Lastname is required. ', ['@num' => $key+1]);
        }

        if ($email != '') {
          $user_exist = user_load_by_mail($email);

          if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_messages[] = $this -> t('Row #@num: "@email" is not a valid email address. ', ['@num' => $key+1, '@email' => $email]);
          }
          else if ($user_exist) {
            $error_messages[] = $this -> t('Row #@num: There is already an account with the email address @email. ', ['@num' => $key+1, '@email' => $email]);
          }
        }
        $invited_users++;
      }

      if (!empty($error_messages)) {
        $form_state -> setErrorByName('students_info', implode(" ", $error_messages));
      }

      $student_account_left_raw = $school_user -> get('field_child_account_left') -> getValue();
      $student_account_left = isset($student_account_left_raw[0]) ? (int) $student_account_left_raw[0]['value'] : 0;

      if (!is_numeric($student_account_left) || $student_account_left <= 0 || (($student_account_left - $invited_users) < 0)) {
        if ($student_account_left <= 0) {
          $message = t('You do not have any user left in your account. Please <a href="@email-link">contact us</a> if you need more.', [
            '@email-link' => 'mailto:jashish24@gmail.com',
          ]);
        } else {
          $message = t('You are trying to create @invited_users users but you have only @student_account_left left in yout account. Please <a href="@email-link">contact us</a> if you need more.', [
            '@invited_users' => $invited_users,
            '@student_account_left' => $student_account_left,
            '@email-link' => 'mailto:jashish24@gmail.com',
          ]);
        }

        $form_state -> setErrorByName('school_name', $message);
      }
    }
    catch (Exception $e) {
      drupal_set_message(t('There was an error creating users.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $students_info = $form_state -> getValue('students_info');
    $school_user_id = $form_state -> getValue('school_name');
    $school_user = \Drupal::entityTypeManager() -> getStorage('user') -> load($school_user_id);
    $student_classroom = $form_state -> getValue('student_classroom');
    $roles = ['child'];
    $array = [];

    $students_info_fragments = [];
    $students_info_fragments = array_filter(explode(PHP_EOL, $students_info), function($value) { return trim($value); }); // array_filter will remove empty lines
    $invited_users = 0;

    foreach ($students_info_fragments as $student_info_string) {
      $student_info = explode(',', $student_info_string);
      $first_name = isset($student_info[0]) ? strip_tags(trim($student_info[0])) : '';
      $last_name = isset($student_info[1]) ? strip_tags(trim($student_info[1])) : '';
      $email = isset($student_info[2]) ? strip_tags(trim($student_info[2])) : '';

      $user_exist = user_load_by_mail($email);
      if (!$user_exist) {
        // User doesn't exist.
        $username = strtolower($first_name);
        $iusername = $username;
        $i = 1;
        while (user_load_by_name($username)) {
          $username = $iusername . $i++;
        }

        // Generate four digit password if not entered by Child Account Owner User
        $password = rand(1000, 9999);

        // New User is created and saved in the database.
        $user = User::create();
        $user -> enforceIsNew();
        $user -> setEmail($email);
        $user -> setUsername($username);
        $user -> activate();
        $user -> set('roles', array_values($roles));
        $user -> set('field_first_name', $first_name); // Extra field added
        $user -> set('field_last_name', $last_name); // Extra field added
        $user -> save();
        $student_uid = $user -> id();

        if ($student_uid > 0) {
          //Creating profile object - Can be used to create profile with required fields (apart from creating user)
          $profile_values = [
            'type' => 'child',
            'uid' => $student_uid,
            'status' => 1,
            'field_first_password' => $password, // A password field that can be viewed by admin to generate password card for kids accounts
            'field_child_school' => $school_user_id, // Attaching to School
            'field_classroom' => $student_classroom,
          ];

          //Creating Student Profile
          $student_profile = Profile::create($profile_values);
          $student_profile -> save();

          $invited_users++;
        }

        if ($email and _user_mail_notify('register_no_approval_required', $user)) {
          drupal_set_message($this -> t('A welcome message with further instructions has been emailed to @email. ', ['@email' => $email]));
        }
      }
      else {
        drupal_set_message(t('There is already an account with the email address @email. ', ['@email' => $email]));
      }
    }

    if ($invited_users > 0) {
      $student_account_left_raw = $school_user -> get('field_child_account_left') -> getValue();
      $student_account_left = isset($student_account_left_raw[0]) ? (int) $student_account_left_raw[0]['value'] : 0;

      $number_of_child_accounts = $student_account_left - $invited_users;
      $school_user -> set('field_child_account_left', $number_of_child_accounts);

      // saving user for permanent change
      $school_user -> save();

      $messageSingular = "1 user has been succesfully created. You have @number_of_child_accounts remaining in your account.";
      $messagePlural = "@count users have been succesfully created. You have @number_of_child_accounts remaining in your account.";
      drupal_set_message(\Drupal::translation()->formatPlural($invited_users, $messageSingular, $messagePlural, ['@number_of_child_accounts' => $number_of_child_accounts]));
    }

  }
}
