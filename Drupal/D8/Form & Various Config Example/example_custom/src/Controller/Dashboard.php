<?php

namespace Drupal\example_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Example Code Dashboard Controller
 * @todo - Can be deleted if already done using views
 */

class Dashboard extends ControllerBase {
  /**
   * Function to return dashboard content
   */

  public function content() {
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    $roles = $current_user -> getRoles(TRUE);

    $content = '';
    $personal_eq_star = calculate_eq_stars('personal', \Drupal::currentUser() -> id());
    $personal_eq_star_html = '';

    if ($personal_eq_star['obtained'] > 0) {
      $content .= '<div class="row"><div class="col-md-12 personal-eq-status">' . $this -> t('<span>Wow!</span> You\'ve got @eq_num EQ stars!', ['@eq_num' => $personal_eq_star['obtained']]) . '</div></div>';
    }
    else {
      $content .= '<div class="row"><div class="col-md-12 personal-eq-status">' . $this -> t('You have no EQ stars. Take quizzes to gain EQ stars.') . '</div></div>';
    }

    $user_progress_id = isset($current_user -> user_progress_id) ? $current_user -> user_progress_id : 0;

    $my_episodes_path = '/my-episodes';
    $my_episodes_url = Url::fromUri('internal:' . $my_episodes_path);
    $my_episodes_link = Link::fromTextAndUrl($this -> t('My Episodes'), $my_episodes_url);
    $my_episodes_link = $my_episodes_link -> toRenderable();
    $my_episodes_link['#attributes'] = ['title' => $this -> t('My Episodes'), 'class' => ['internal', 'myepisodes']];
    $my_episodes_output = render($my_episodes_link);

    if ($user_progress_id > 0) {
      $user_progress_node = Node::load($user_progress_id);

      // Get list of video started by user
      $watched_videos = $user_progress_node -> get('field_videos_watched') -> getValue();

      if (!empty($watched_videos)) {
        $watched_incomplete_videos = [];

        foreach ($watched_videos as $key => $watched_video) {
          // Load settings related to each watched video
          $watched_video_settings = \Drupal\field_collection\Entity\FieldCollectionItem::load($watched_video['value']);

          $watched_video = $watched_video_settings -> get('field_single_video') -> getValue();
          $watched_video_progress = $watched_video_settings -> get('field_single_video_progress') -> getValue();
          $watched_video_completed = $watched_video_settings -> get('field_single_video_completed') -> getValue();

          $watched_video_target_id = isset($watched_video[0]['target_id']) ? $watched_video[0]['target_id'] : 0;
          $watched_video_progress_time = isset($watched_video_progress[0]['value']) ? $watched_video_progress[0]['value'] : 0;
          $watched_video_is_completed = isset($watched_video_completed[0]['value']) ? $watched_video_completed[0]['value'] : 0;

          if ($watched_video_is_completed == 0 && $watched_video_target_id > 0) {
            $watched_incomplete_videos[] = $watched_video_target_id;
          }
        }

        if (!empty($watched_incomplete_videos)) {
          // Randomly generate video id from list of incomplete watched videos
          $watched_incomplete_videos_key = mt_rand(0, sizeof($watched_incomplete_videos) - 1);

          $display_incomplete_video_id = $watched_incomplete_videos[$watched_incomplete_videos_key];
          $display_incomplete_video_node = Node::load($display_incomplete_video_id);

          $display_incomplete_video_path = $display_incomplete_video_node -> toUrl() -> toString();
          $display_incomplete_video_title = $display_incomplete_video_node -> title -> value;
          $display_incomplete_video_url = Url::fromUri('internal:' . $display_incomplete_video_path);
          $display_incomplete_video_link = Link::fromTextAndUrl($display_incomplete_video_title, $display_incomplete_video_url);
          $display_incomplete_video_link = $display_incomplete_video_link -> toRenderable();
          $display_incomplete_video_link['#attributes'] = ['title' => $display_incomplete_video_title, 'class' => ['video-title']];
          $display_incomplete_video_output = render($display_incomplete_video_link);

          $video_thumbnail = $display_incomplete_video_node -> field_video_id -> view(['type' => 'video_embed_field_thumbnail']);
          $video_thumbnail['#label_display'] = 'hidden';
          $video_thumbnail_html = \Drupal::service('renderer') -> render($video_thumbnail);

          $display_incomplete_video_thumbnail = $display_incomplete_video_node -> link($video_thumbnail_html) -> getGeneratedLink();

          $content .= '<div class="row"><div class="col-md-6 col-sm-12 col-xs-12 personal-incomplete-video"><h4>' . $this -> t('Continue Watching') . '</h4>' . $display_incomplete_video_thumbnail . $display_incomplete_video_output . '</div>';
        }
        else {
          $no_watched_video_path = '/my-episodes';
          $no_watched_video_url = Url::fromUri('internal:' . $no_watched_video_path);
          $no_watched_video_link = Link::fromTextAndUrl($this -> t('My Episodes'), $no_watched_video_url);
          $no_watched_video_link = $no_watched_video_link -> toRenderable();
          $no_watched_video_link['#attributes'] = ['title' => $this -> t('My Episodes'), 'class' => ['video-title']];
          $no_watched_video_output = render($no_watched_video_link);

          $video_image = '<img alt="' . $this -> t('My Episodes') . '" title="' . $this -> t('My Episodes') . '" src="/sites/default/files/video-library.jpg">';
          $content .= '<div class="row"><div class="col-md-6 col-sm-12 col-xs-12 personal-incomplete-video"><h4>' . $this -> t('Continue Watching') . '</h4>' . $video_image . $no_watched_video_output . '</div>';
        }
      }
      else {
        $no_watched_video_path = '/my-episodes';
        $no_watched_video_url = Url::fromUri('internal:' . $no_watched_video_path);
        $no_watched_video_link = Link::fromTextAndUrl($this -> t('My Episodes'), $no_watched_video_url);
        $no_watched_video_link = $no_watched_video_link -> toRenderable();
        $no_watched_video_link['#attributes'] = ['title' => $this -> t('My Episodes'), 'class' => ['video-title']];
        $no_watched_video_output = render($no_watched_video_link);
//        ksm($no_watched_video_output);
        $video_image = '<img alt="' . $this -> t('My Episodes') . '" title="' . $this -> t('My Episodes') . '" src="/sites/default/files/video-library.jpg">';
        $content .= '<div class="row"><div class="col-md-6 col-sm-12 col-xs-12 personal-incomplete-video"><h4>' . $this -> t('Continue Watching') . '</h4>' . $video_image . $no_watched_video_output . '</div>';
      }
    }

    $content .= '<div class="col-md-6 col-sm-12 col-xs-12  personal-info-wrap"><div class="personal-my-videos">' . $my_episodes_output . '</div>';

    if ($current_user -> hasRole('school')) {
      $path = '/progress/school/' . $current_user -> id();
      $url = Url::fromUri('internal:' . $path);
      $link = Link::fromTextAndUrl($this -> t('School Progress'), $url);
      $link = $link -> toRenderable();
      $link['#attributes'] = ['title' => $this -> t('School Progress'), 'class' => ['internal', 'my-episodes']];
      $output = render($link);

      $content .= '<div class="school-progress">' . $output . '</div></div></div>';
    }
    else if ($current_user -> hasRole('teacher')) {
      $path = '/progress/teacher/' . $current_user -> id();
      $url = Url::fromUri('internal:' . $path);
      $link = Link::fromTextAndUrl($this -> t('My Classes'), $url);
      $link = $link -> toRenderable();
      $link['#attributes'] = ['title' => $this -> t('My Classes'), 'class' => ['internal', 'my-episodes']];
      $output = render($link);

      $content .= '<div class="school-progress">' . $output . '</div></div></div>';
    }

    $html = [
      '#type' => 'markup',
      '#markup' => $content,
    ];

    return $html;
  }

  /**
   * Function to check dashboard access
   */

  function access(AccountInterface $account) {
    return AccessResult::allowedIf($account -> isAuthenticated());
  }
}
