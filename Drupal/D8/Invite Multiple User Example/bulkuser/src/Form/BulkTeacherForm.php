<?php

namespace Drupal\bulkuser\Form;

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\Profile;
use Drupal\bulkuser\Controller\AjaxHandler;

/**
 * Implements a Bulk User Form for inviting users with teacher role.
 */
class BulkTeacherForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'bulkteacherform';
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
    
    // Text area to provide the list of email ids separated by comma.
    $form = [];
    $form['school_name'] = [
      '#type' => 'select',
      '#title' => t('School'),
      '#description' => t('School of inviting teachers.'),
      '#required' => TRUE,
      '#options' => $school_list,
      '#disabled' => $school_name_disabled,
      '#default_value' => $school_name_default_value,
      '#ajax' => [
        'callback' => [$ajaxhandler, 'rebuildTeacherClasroomsList'],
        'wrapper' => 'classrooms-wrapper',
      ],
    ];
    
    $form['teacher_classrooms'] = [
      '#type' => 'select',
      '#title' => t('Classroom'),
      '#description' => t('Classroom of inviting teachers.'),
      '#required' => TRUE,
      '#options' => $classrooms_options,
      '#multiple' => TRUE,
      '#prefix' => '<div id="classrooms-wrapper">',
      '#suffix' => '</div>',
    ];
    
    $form['emailids'] = [
      '#type' => 'textarea',
      '#title' => 'Email addresses',
      '#size' => 20,
      '#description' => t('Enter the email addresses of users to invite, one email per line.'),
      '#required' => TRUE,
    ];

    $form['submit_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Invite Teachers'),
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
      $emails = $form_state -> getValue('emailids');
      $teacher_classrooms = $form_state -> getValue('teacher_classrooms');
 
      $invited_users = 0;
      $array = [];
      $array = explode(PHP_EOL, $emails);
      $error_email_messages = '';

      foreach ($array as $email) {
        $email = trim($email);
        $user_exist = user_load_by_mail($email);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $error_email_messages .= $this -> t('Please enter valid list of email address. "@email" is not in right format.||', ['@email' => $email]);
        }
        else if ($user_exist) {
          $error_email_messages .= $this -> t('Email:"@email" already exists.||', ['@email' => $email]);
        }
        $invited_users++;
      }
      
      if (trim($error_email_messages) != '') {
        $form_state -> setErrorByName('emailids', $error_email_messages);
      }
      
      $student_account_added_db = \Drupal::database();
      $student_account_added_query = $student_account_added_db -> select('profile__field_child_school', 'fcs');
      $student_account_added_query -> addExpression('count(entity_id)', 'student_accounts');
      $student_account_added_query -> condition('field_child_school_target_id', $school_user_id);
      $student_account_added = (int) $student_account_added_query -> execute() -> fetchField();
      
      $student_account_left_raw = $school_user -> get('field_child_account_left') -> getValue();
      $student_account_left = isset($student_account_left_raw[0]) ? (int) $student_account_left_raw[0]['value'] : 0;
      $total_student_account = $student_account_added + $student_account_left;
      
      
      $teacher_account_added_db = \Drupal::database();
      $teacher_account_added_query = $teacher_account_added_db -> select('profile__field_teacher_school', 'fts');
      $teacher_account_added_query -> addExpression('count(entity_id)', 'student_accounts');
      $teacher_account_added_query -> condition('field_teacher_school_target_id', $school_user_id);
      $teacher_account_added = (int) $teacher_account_added_query -> execute() -> fetchField();
      
      $teacher_account_invited = $invited_users + $teacher_account_added;
      $teacher_account_allowed = (int) ceil($total_student_account / 10);
      $bulkuser_access_roles = ['administrator', 'manager'];
      
      if (!array_intersect($school_user_roles, $bulkuser_access_roles) && $teacher_account_invited > $teacher_account_allowed) {
        $form_state -> setErrorByName('school_name', $this -> t('You are inviting more teacher than allowed. You have @invite teacher accounts left', ['@invite' => ($teacher_account_allowed - $teacher_account_added)]));
      }
    }
    catch (Exception $e) {
      drupal_set_message(t('There was an error inviting users.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $emails = $form_state -> getValue('emailids');
    $school_user_id = $form_state -> getValue('school_name');
    $school_user = \Drupal::entityTypeManager() -> getStorage('user') -> load($school_user_id);
    $teacher_classrooms = array_filter($form_state -> getValue('teacher_classrooms'));
    $roles = ['teacher'];
    $array = [];
    $array = explode(PHP_EOL, $emails);
    $invited_users = 0;

    foreach ($array as $email) {
      $email = trim($email);
      $index = strpos($email, '@');
      $user_exist = user_load_by_mail($email);
      if (!$user_exist) {
        // User doesn't exist.
        $username = substr($email, 0, $index);
        $iusername = $username;
        $i = 1;
        while (user_load_by_name($username)) {
          $username = $iusername . '_' . $i++;
        }
        // New User is created and saved in the database.
        $user = User::create();
        $user -> enforceIsNew();
        $user -> setEmail($email);
        $user -> setUsername($username);
        $user -> activate();
        $user -> set('roles', array_values($roles));
        $user -> save();
        $teacher_uid = $user -> id();
        
        if ($teacher_uid > 0) {
          //Creating profile object - Can be used to create profile with required fields (apart from creating user)
          $profile_values = [
            'type' => 'teacher',
            'uid' => $teacher_uid,
            'status' => 1,
            'field_teacher_school' => $school_user_id, // Attaching to School
            'field_classrooms' => $teacher_classrooms,
          ];
      
          //Creating Teacher Profile
          $teacher_profile = Profile::create($profile_values);
          $teacher_profile -> save();
          
          $invited_users++;
        }
        
        if (_user_mail_notify('register_no_approval_required', $user)) {
          drupal_set_message($this -> t('A welcome message with further instructions has been emailed to your email address: @email', ['@email' => $email]));
        }
      }
      else {
        drupal_set_message(t('Email:"@email" already exists', ['@email' => $email]));
      }
    }    
  }
}
