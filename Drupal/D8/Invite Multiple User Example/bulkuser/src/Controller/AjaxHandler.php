<?php

namespace Drupal\bulkuser\Controller;

use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Ajax\AjaxResponse;
use \Drupal\Core\Ajax\HtmlCommand;

class AjaxHandler {
  public function rebuildClasroomsList(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $school_user_id = $form_state -> getValue('school_name');
    
    $classrooms_options = [];
    //Generating list of classes based on current school user
    if ($school_user_id > 0) {
      $classrooms_list_db = \Drupal::database();
      $classrooms_list_query = $classrooms_list_db -> select('profile__field_classrooms', 'pfc');
      $classrooms_list_query -> join('profile', 'p', 'p.profile_id = pfc.entity_id');
      $classrooms_list_query -> join('node_field_data', 'n', 'n.nid = pfc.field_classrooms_target_id');
      $classrooms_list_query -> condition('pfc.deleted', 0);
      $classrooms_list_query -> condition('n.status', 1);
      $classrooms_list_query -> condition('p.uid', $school_user_id);
      $classrooms_list_query -> fields('n', ['title', 'nid']);
      $classrooms_list = $classrooms_list_query -> execute() -> fetchAllAssoc('nid');
      
      foreach ($classrooms_list as $classroom_id => $classroom) {
        $classrooms_options[$classroom -> nid] = $classroom -> title;
      }
    }

    $form['student_classroom']['#options'] = $classrooms_options;
    
    $response -> addCommand(new HtmlCommand('#classrooms-wrapper', $form['student_classroom']));
    $form_state -> setRebuild();
    return $response;
  }
  
  public function rebuildTeacherClasroomsList(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $school_user_id = $form_state -> getValue('school_name');
    
    $classrooms_options = [];
    //Generating list of classes based on current school user
    if ($school_user_id > 0) {
      $classrooms_list_db = \Drupal::database();
      $classrooms_list_query = $classrooms_list_db -> select('profile__field_classrooms', 'pfc');
      $classrooms_list_query -> join('profile', 'p', 'p.profile_id = pfc.entity_id');
      $classrooms_list_query -> join('node_field_data', 'n', 'n.nid = pfc.field_classrooms_target_id');
      $classrooms_list_query -> condition('pfc.deleted', 0);
      $classrooms_list_query -> condition('n.status', 1);
      $classrooms_list_query -> condition('p.uid', $school_user_id);
      $classrooms_list_query -> fields('n', ['title', 'nid']);
      $classrooms_list = $classrooms_list_query -> execute() -> fetchAllAssoc('nid');
      
      foreach ($classrooms_list as $classroom_id => $classroom) {
        $classrooms_options[$classroom -> nid] = $classroom -> title;
      }
    }

    $form['teacher_classrooms']['#options'] = $classrooms_options;
    
    $response -> addCommand(new HtmlCommand('#classrooms-wrapper', $form['teacher_classrooms']));
    $form_state -> setRebuild();
    return $response;
  }
}