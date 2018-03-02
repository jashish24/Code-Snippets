<?php

namespace Drupal\plusminuscode_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;

/*
 * Plusminus Code Custom Controller
 */

class QuizAjax extends ControllerBase {
  /**
   * Function to change user quiz status
   */
 
  public function changeQuizStatus() {
    $type = isset($_POST['type']) ? strip_tags($_POST['type']) : 'default';
    // Get current user globally as all the request are related to current user
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    
    $message = [];
    
    switch ($type) {
      case 'quiz_next' :
        $message['code'] = 200;
        $message['message'] = $this -> t('Question Submitted') . $nid;
        break;
      
      case 'quiz_submit' :
        $message['code'] = 200;
        $message['message'] = $this -> t('Quiz Submitted');
        break;
        
      case 'video_progress' :
        $video_time = isset($_POST['video_time']) ? round(strip_tags($_POST['video_time'])) : 0; // Current playing time of the video
        $video_id = isset($_POST['video_id']) ? strip_tags($_POST['video_id']) : 0; // Current video ID
        
        //get current user progress card node
        $user_progress_query = \Drupal::entityQuery('node')
          -> condition('status', 1)
          -> condition('type', 'child_progress')
          -> condition('field_attached_user', $current_user -> id())
          -> execute();

        $user_progress_raw = array_values($user_progress_query);
        $user_progress_id = $user_progress_raw[0];
        $user_progress_node = Node::load($user_progress_id);
        $watched_videos = $user_progress_node -> get('field_videos_watched') -> getValue();
        $existing_record = false; // To check if the posted id already exists or not
        
        foreach ($watched_videos as $key => $watched_video) {
          $watched_video_settings = \Drupal\field_collection\Entity\FieldCollectionItem::load($watched_video['value']);
          $watched_video = $watched_video_settings -> get('field_single_video') -> getValue();
          $watched_video_target_id = isset($watched_video[0]['target_id']) ? $watched_video[0]['target_id'] : 0;
          if ($watched_video_target_id == $video_id) {
            //Set the field_single_video value.
            $watched_video_settings -> set('field_single_video', $video_id);
            //Set the field_single_video_progress value
            $watched_video_settings -> set('field_single_video_progress', $video_time);
            //Save the field_collection item. This will save the host node too.
            $watched_video_settings -> save();
            //Update status that video record already exists (avoiding addition of duplicate entries)
            $existing_record = true;
          }
        }
        
        if (!$existing_record && $video_id != 0) {
          $new_watched_video = entity_create('field_collection_item', array('field_name' => 'field_videos_watched'));
          //Set the relationship to the host node.
          $new_watched_video -> setHostEntity($user_progress_node);
          //Set the field_single_video value.
          $new_watched_video -> set('field_single_video', $video_id);
          //Set the field_single_video_progress value
          $new_watched_video -> set('field_single_video_progress', $video_time);
          //Save the field_collection item. This will save the host node too.
          $new_watched_video -> save();
        }

        $message['code'] = 200;
        $message['message'] = $this -> t('Video Progress Updated');
        break;
      
      default :
        $message['code'] = 403;
        $message['message'] = $this -> t('Invalid request');
    }
    
    $response_params = Json::encode($message);
    
    $response = new Response();
    $response -> setContent($response_params);
    $response -> headers -> set('Content-Type', 'application/json');
    return $response;
  }
  
  /**
   * Function to generate user access based on number of child account bought by a authenticated user
   */
  
  public function access() {
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    $roles = $current_user -> getRoles(); // TRUE, getting only unlocked roles (authenticated is a locked role)

    if ($current_user -> hasRole('administrator', $roles) || $current_user -> hasRole('school', $roles)) {
      return AccessResult::allowed();
    }
    else if ($current_user -> hasRole('authenticated', $roles)) {
      return AccessResult::allowed();
    }

    drupal_set_message(t('Please watch full video to access this quiz.'));
    return AccessResult::forbidden();
  }
}