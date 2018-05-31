<?php

namespace Drupal\example_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;

/*
 * Example Code Custom Controller - Common API to handle all the custom Ajax requests
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
      case 'eq_star_update' :
        // Update number of EQ stars obtained by the user
        $user_progress_id = isset($current_user -> user_progress_id) ? $current_user -> user_progress_id : 0;
        if ($user_progress_id > 0) {
          $quiz_id = isset($_POST['quiz_id']) ? strip_tags($_POST['quiz_id']) : 0;
          $question_id = isset($_POST['question_id']) ? strip_tags($_POST['question_id']) : 0;
          
          $user_progress_node = Node::load($user_progress_id);
          // Getting list of already found EQ stars (avoiding duplication)
          $already_found_eq_star = $user_progress_node -> get('field_eq_star_questions_selected') -> getValue();
          $already_found_eq_star_array = [];
          $eq_in_list = false;
          
          foreach ($already_found_eq_star as $key => $already_found_eq_star_id) {
            $already_found_eq_star_array[] = $already_found_eq_star_id['target_id'];
            if ($already_found_eq_star_id['target_id'] == $question_id) {
              // Checking if this EQ id already obtained or not
              $eq_in_list = true;
              break;
            }
          }
          
          if (!$eq_in_list) {
            // Updating EQ stars count if not found in the list
            $completed_quizzes_status = $user_progress_node -> get('field_completed_quiz_status') -> getValue();
            
            foreach ($completed_quizzes_status as $key => $completed_quiz_status) {
              $completed_quiz_settings = \Drupal\field_collection\Entity\FieldCollectionItem::load($completed_quiz_status['value']);
              $completed_quiz_id = $completed_quiz_settings -> get('field_quiz_id') -> getValue();
              $eq_stars_obtained = $completed_quiz_settings -> get('field_eq_stars_obtained') -> getValue();
              $eq_stars_obtained_count = isset($eq_stars_obtained[0]) ? $eq_stars_obtained[0]['value'] : 0;
              
              if ($quiz_id == $completed_quiz_id[0]['target_id']) {
                $eq_stars_obtained_count++;
                $completed_quiz_settings -> set('field_eq_stars_obtained', $eq_stars_obtained_count);
                //Save the field_collection item. This will save the host node too.
                $completed_quiz_settings -> save();
              }
            }
            $already_found_eq_star_array[] = $question_id;
            $user_progress_node -> set('field_eq_star_questions_selected', $already_found_eq_star_array);
            $user_progress_node -> save();
          }
        }
        
        $message['code'] = 200;
        $message['message'] = $this -> t('EQ Star Updated');
        break;
        
      case 'video_progress' :
        // Updating video progress in user progress node of the current user
        $video_time = isset($_POST['video_time']) ? round(strip_tags($_POST['video_time'])) : 0; // Current playing time of the video
        $video_id = isset($_POST['video_id']) ? strip_tags($_POST['video_id']) : 0; // Current video ID
        $video_completed = isset($_POST['video_completed']) ? strip_tags($_POST['video_completed']) : 0; // Current video ID

        $user_progress_id = isset($current_user -> user_progress_id) ? $current_user -> user_progress_id : 0;
        
        if ($user_progress_id > 0) {
          $user_progress_node = Node::load($user_progress_id);
          $watched_videos = $user_progress_node -> get('field_videos_watched') -> getValue();
          $existing_record = false; // To check if the posted id already exists or not
          
          foreach ($watched_videos as $key => $watched_video) {
            // Loading existing video progress
            $watched_video_settings = \Drupal\field_collection\Entity\FieldCollectionItem::load($watched_video['value']);
            $watched_video = $watched_video_settings -> get('field_single_video') -> getValue();
            $watched_video_progress = $watched_video_settings -> get('field_single_video_progress') -> getValue();

            $watched_video_target_id = isset($watched_video[0]['target_id']) ? $watched_video[0]['target_id'] : 0;
            $watched_video_progress_time = isset($watched_video_progress[0]['value']) ? $watched_video_progress[0]['value'] : 0;
            
            // Checking if video progress updation is required or not
            if ($watched_video_target_id == $video_id && $video_time >= $watched_video_progress_time) {
              //Set the field_single_video value.
              $watched_video_settings -> set('field_single_video', $video_id);
              //Set the field_single_video_progress value
              $watched_video_settings -> set('field_single_video_progress', $video_time);
              //Set the field_single_video_completed value
              $watched_video_settings -> set('field_single_video_completed', $video_completed);
              //Save the field_collection item. This will save the host node too.
              $watched_video_settings -> save();
              //Update status that video record already exists (avoiding addition of duplicate entries)
              $existing_record = true;
            }
            else if ($watched_video_target_id == $video_id) {
              $existing_record = true;
            }
          }
          
          // Creating new video progress record if it does not exists
          if (!$existing_record && $video_id != 0) {
            $new_watched_video = entity_create('field_collection_item', array('field_name' => 'field_videos_watched'));
            //Set the relationship to the host node.
            $new_watched_video -> setHostEntity($user_progress_node);
            //Set the field_single_video value.
            $new_watched_video -> set('field_single_video', $video_id);
            //Set the field_single_video_progress value
            $new_watched_video -> set('field_single_video_progress', $video_time);
            //Set the field_single_video_completed value
            $new_watched_video -> set('field_single_video_completed', $video_completed);
            //Save the field_collection item. This will save the host node too.
            $new_watched_video -> save();
          }

          $message['code'] = 200;
          $message['message'] = $this -> t('Video Progress Updated');
        }
        else {
          $message['code'] = 403;
          $message['message'] = $this -> t('No Progress Card attached to this user.');
        }
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