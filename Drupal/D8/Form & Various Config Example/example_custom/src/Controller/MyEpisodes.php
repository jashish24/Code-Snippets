<?php

namespace Drupal\example_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Example Code My Episodes Controller
 * @todo - Can be deleted if already done using views
 */

class MyEpisodes extends ControllerBase {
  /**
  * Function to return dashboard content
  */
  
  public function content() {
    // Get current user globally as all the request are related to current user
    $current_user_id = \Drupal::currentUser() -> id();
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load($current_user_id);
    $user_progress_id = isset($current_user -> user_progress_id) ? $current_user -> user_progress_id : 0;
    $user_quiz_status = [];
    
    if ($user_progress_id > 0) {
      $user_progress_node = Node::load($user_progress_id);
      $user_completed_quizzes = $user_progress_node -> get('field_completed_quiz_status') -> getValue();
      
      foreach ($user_completed_quizzes as $key => $quiz_status) {
        $quiz_complete_status = \Drupal\field_collection\Entity\FieldCollectionItem::load($quiz_status['value']);
        $quiz_id_raw = $quiz_complete_status -> field_quiz_id -> getValue();
        $quiz_id = !empty($quiz_id_raw) ? $quiz_id_raw[0]['target_id'] : 0;

        if ($quiz_id > 0) {
          $quiz_total_score = isset($quiz_complete_status -> field_available_correct_options) ? $quiz_complete_status -> field_available_correct_options -> value : 0;
          $quiz_obtained_score = isset($quiz_complete_status -> field_marked_correct_options) ? $quiz_complete_status -> field_marked_correct_options -> value : 0;
          
          $quiz_score_percent = ($quiz_obtained_score > 0 && $quiz_total_score > 0) ? round(($quiz_obtained_score / $quiz_total_score) * 100) : 0;
          $quiz_total_eq_stars = isset($quiz_complete_status -> field_eq_stars_available) ? $quiz_complete_status -> field_eq_stars_available -> value : 0;
          $quiz_obtained_eq_stars = isset($quiz_complete_status -> field_eq_stars_obtained) ? $quiz_complete_status -> field_eq_stars_obtained -> value : 0;
          
          $eq_star_percent = ($quiz_obtained_eq_stars > 0 && $quiz_total_eq_stars > 0) ? round(($quiz_obtained_eq_stars / $quiz_total_eq_stars) * 100) : 0;
          $user_quiz_status[$quiz_id]['quiz_score'] = $quiz_score_percent;
          $user_quiz_status[$quiz_id]['quiz_eq_status'] = $eq_star_percent;
        }
      }
    }
    
    $user_total_videos = [];
    $content = '<div class="my-episodes">';
    
    if ($current_user -> hasRole('school')) {
      $school_videos_db = \Drupal::database();
      $school_videos_query = $school_videos_db -> select('user__field_user_media_access', 'fuma');
      $school_videos_query -> leftjoin('node__field_quiz_episode', 'fqe', 'fqe.field_quiz_episode_target_id = fuma.field_user_media_access_target_id');
      $school_videos_query -> fields('fuma', ['field_user_media_access_target_id']);
      $school_videos_query -> fields('fqe', ['entity_id']);
      $school_videos_query -> condition('fuma.entity_id', $current_user_id);
      
      $user_total_videos = $school_videos_query -> execute() -> fetchAllAssoc('field_user_media_access_target_id');
    }
    else if ($current_user -> hasRole('teacher')) {
      $teacher_school_videos = \Drupal::database();
      $teacher_school_videos_query = $teacher_school_videos -> select('profile__field_teacher_school', 'fts');
      $teacher_school_videos_query -> join('profile', 'p', 'p.profile_id = fts.entity_id');
      $teacher_school_videos_query -> join('user__field_user_media_access', 'fuma', 'fuma.entity_id = fts.field_teacher_school_target_id');
      $teacher_school_videos_query -> leftjoin('node__field_quiz_episode', 'fqe', 'fqe.field_quiz_episode_target_id = fuma.field_user_media_access_target_id');
      $teacher_school_videos_query -> fields('fuma', ['field_user_media_access_target_id']);
      $teacher_school_videos_query -> fields('fqe', ['entity_id']);
      $teacher_school_videos_query -> condition('p.uid', $current_user_id);
      
      $user_total_videos = $teacher_school_videos_query -> execute() -> fetchAllAssoc('field_user_media_access_target_id');
    }
    
    //$user_total_videos format: {array{$user_total_videos}, each record{object or stdClass}, key{video_id}, properties{entity_id, field_user_media_access_target_id}}
    foreach ($user_total_videos as $video_id => $video) {
      $related_quiz_id = isset($video -> entity_id) ? $video -> entity_id : 0;
      $quiz_score = 0;
      $quiz_eq_status = 0;
      
      if ($related_quiz_id > 0 && isset($user_quiz_status[$related_quiz_id])) {
        $quiz_score = $user_quiz_status[$related_quiz_id]['quiz_score'];
        $quiz_eq_status = $user_quiz_status[$related_quiz_id]['quiz_eq_status'];
      }
      
      $video_node = Node::load($video_id);
      $video_title = $video_node -> title -> value;
      $video_raw = $video_node -> field_video_id -> view(['type' => 'video_embed_field_thumbnail']);
      $video_raw['#label_display'] = 'hidden';
      $video_thumbnail_html = \Drupal::service('renderer') -> render($video_raw);
      $video_link = $video_node -> link($video_title) -> getGeneratedLink();
      $video_thumbnail_link = $video_node -> link($video_thumbnail_html) -> getGeneratedLink();
      
      $content .= '<div class="single-video single-video-"' . $video_id . '>';
      $content .= '<div class="video-thumbnail">' . $video_thumbnail_link . '</div>';
      $content .= '<div class="video-summary-wrapper"><div class="video-summary">' . $video_link . '<div data-rateyo-read-only="true" data-rateyo-rating="' . $quiz_eq_status . '%" class="video-rateYo">&nbsp;</div></div>';
      $content .= '<div class="video-score c100 small p' . $quiz_score . '"><span>' . $quiz_score . '%</span></div></div>';
      $content .= '</div>';
    }
    
    if (empty($user_total_videos)) {
      $content .= '<h4 class="no-videos">' . $this -> t('There are no videos in your library. Please buy some from our online shop.') . '</h4>';
    }

    $content .= '</div>';
    
    $html = [
      '#type' => 'markup',
      '#markup' => $content,
    ];
    
    return $html;
  }
  
  /**
   * Function to check my-episodes access
   */
  
  function access(AccountInterface $account) {
    return AccessResult::allowedIf($account -> isAuthenticated());
  }
}