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
 * Example Code DownloadCard Controller
 */

class DownloadCard extends ControllerBase {
  /**
  * Function to return Student Card content
  */
  
  public function content($userid = 0) {
    // Return content if downloadable pdf format for a chile user
    $student_user = \Drupal::entityTypeManager() -> getStorage('user') -> load($userid);
    $student_profile = \Drupal::entityManager() -> getStorage('profile') -> loadByUser($student_user, 'child');
    $tmp_dir = file_directory_temp();
    $user_picture = $student_user -> get('field_picture') -> getValue();
    $first_name_raw = $student_user -> get('field_first_name') -> getValue();
    $last_name_raw = $student_user -> get('field_last_name') -> getValue();
    $username = $student_user -> name -> value;
    $student_name = '';
    $student_name .= isset($first_name_raw[0]) ? $first_name_raw[0]['value'] : '';
    $student_name .= isset($last_name_raw[0]) ? ' ' . $last_name_raw[0]['value'] : '';
    $student_name = ($student_name == '') ? $username : $student_name;
    $first_password_raw = $student_profile -> get('field_first_password') -> getValue();
    $first_password = isset($first_password_raw[0]) ? $first_password_raw[0]['value'] : '';
    
    //Loading default profile picture in case user has not uploaded any image
    if (empty($user_picture)) {
      $bundle_fields = \Drupal::getContainer() -> get('entity_field.manager') -> getFieldDefinitions('user', 'user');
      $default_user_picture = $bundle_fields['field_picture'];
      $default_user_picture_settings = $default_user_picture -> getSetting('default_image');
      $default_user_picture_file = \Drupal::service('entity.manager') -> loadEntityByUuid('file', $default_user_picture_settings['uuid']);

      if ($default_user_picture_file) {
        $wrapper = \Drupal::service('stream_wrapper_manager') -> getViaUri($default_user_picture_file -> getFileUri());
        $image_path = $wrapper -> getExternalUrl();
        
        $user_picture_html = '<img class="profile-picture" src="' . $image_path . '">';
      }
    }
    //Loading user profile picture
    else {
      $user_picture_file = File::load($user_picture[0]['target_id']);
      $wrapper = \Drupal::service('stream_wrapper_manager') -> getViaUri($user_picture_file -> getFileUri());
      $image_path = $wrapper -> getExternalUrl();

      $user_picture_html = '<img class="profile-picture" src="' . $image_path . '">';
    }
    
    $content = '<table width="100%">
      <tr style="float: left; width: 25%" rowspan="5">
        <td>' . $user_picture_html . '</td>
      </tr>
      <tr align="center" style="float: left; width: 75%"><td><h3>' . t('The Example') . '</h3></td></tr>
      <tr align="center" style="float: left; width: 75%"><td>' . t('Student ID: @uid', ['@uid' => $userid]) . '</td></tr>
      <tr style="float: left; width: 75%"><td>' . t('Username: @username', ['@username' => $username]) . '</td></tr>
      <tr style="float: left; width: 75%"><td>' . t('Password: @pass', ['@pass' => $first_password]) . '</td></tr>
      <tr style="float: left; width: 75%"><td>http://www.example.com</td></tr>
      <tr><td><b>' . $student_name . '</b></td></tr>
    </table>';
    // PHP library mdf is used to convert html to pdf
    $mpdf = new \Mpdf\Mpdf(['tempDir' => $tmp_dir]);
    $mpdf -> WriteHTML($content);
    $mpdf -> Output(str_replace(' ', '_', $student_name) . '.pdf', 'D');
    
    //print $content;
    exit;
  }
  
  /**
   * Function to check ChildAccounts access
   */
  
  function access(AccountInterface $account) {
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    return AccessResult::allowedIf($account -> isAuthenticated() && !$current_user -> hasRole('child'));
  }
}