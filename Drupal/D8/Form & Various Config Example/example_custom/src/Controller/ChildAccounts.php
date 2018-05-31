<?php

namespace Drupal\example_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\file\Entity\File;

/**
 * Example Code ChildAccounts Controller
 */

class ChildAccounts extends ControllerBase {
  /**
  * Function to return ChildAccounts list available to different roles
  */
  
  public function content() {
    $userid = \Drupal::currentUser() -> id();
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    $user_roles = $current_user -> getRoles(TRUE);
    $child_ids = [];
    $database = \Drupal::database();
    
    // Loading list of child accounts attached to a school
    if (in_array('school', $user_roles)) {
      $profile = \Drupal::entityManager() -> getStorage('profile') -> loadByUser($current_user, 'school');
      $profile_id = $profile -> profile_id -> value;
      
      $child_ids_query = $database -> select('profile__field_child_school', 'fcs');
      $child_ids_query -> join('profile', 'p', 'p.uid = fcs.field_child_school_target_id');
      $child_ids_query -> join('profile', 'p1', 'p1.profile_id = fcs.entity_id');
      $child_ids_query -> condition('p.profile_id', $profile_id);
      $child_ids_query -> condition('p1.status', 1);
      $child_ids_query -> fields('p1', ['uid']);
      
      $child_ids_raw = $child_ids_query -> execute() -> fetchAll();
      
      $child_ids_built = [];
      foreach ($child_ids_raw as $child_id) {
        $child_ids_built[] = $child_id -> uid;
      }
    }
    else if (in_array('teacher', $user_roles)) {
      // Loading list of child accounts attached to a teacher
      $profile = \Drupal::entityManager() -> getStorage('profile') -> loadByUser($current_user, 'teacher');
      $profile_id = $profile -> profile_id -> value;
      
      $child_ids_query = $database -> select('profile__field_classrooms', 'fc');
      $child_ids_query -> join('profile__field_classroom', 'fcstu', 'fcstu.field_classroom_target_id = fc.field_classrooms_target_id');
      $child_ids_query -> join('profile', 'p', 'p.profile_id = fcstu.entity_id');
      $child_ids_query -> condition('fc.entity_id', $profile_id);
      $child_ids_query -> condition('p.status', 1);
      $child_ids_query -> fields('p', ['uid']);
      
      $child_ids_raw = $child_ids_query -> execute() -> fetchAll();
      
      $child_ids_built = [];
      foreach ($child_ids_raw as $child_id) {
        $child_ids_built[] = $child_id -> uid;
      }
    }
    
    // Loading all profile at once
    $child_profiles = \Drupal\user\Entity\User::loadMultiple($child_ids_built);
    
    foreach ($child_profiles as $uid => $child_profile) {
      $first_name_raw = $child_profile -> get('field_first_name') -> getValue();
      $username = $child_profile -> name -> value;
      $first_name = isset($first_name_raw[0]) ? $first_name_raw[0]['value'] : $username;
      
      $child_ids[$uid]['name'] = $first_name;
      $child_ids[$uid]['uid'] = $uid;
    }
    
    $renderable = [
      '#theme' => 'child_accounts',
      '#child_ids' => $child_ids,
    ];
    
    return $renderable;
  }
  
  /**
   * Function to check ChildAccounts access
   */
  
  function access(AccountInterface $account) {
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    return AccessResult::allowedIf($account -> isAuthenticated() && !$current_user -> hasRole('child'));
  }
}