<?php

/**
 * Implements hook_views_data_alter().
 */
function example_custom_views_data_alter(array &$data) {
  $data['node_field_data']['video_progress'] = array(
    'title' => t('Video progress'),
    'filter' => array(
      'title' => t('Video progress'),
      'group' => t('Content'),
      'help' => t("Filters videos by the logged-in user's progress status: Watched, Watching, Unwatched."),
      'field' => 'nid',
      'id' => 'example_video_progress'
    ),
  );
  $data['node_field_data']['video_access'] = array(
    'title' => t('Video access'),
    'filter' => array(
      'title' => t('Video access'),
      'group' => t('Content'),
      'help' => t("Filters videos by the logged-in user's media access field."),
      'field' => 'nid',
      'id' => 'example_video_access'
    ),
  );
  $data['node']['eq_stars_summary'] = array(
    'title' => t('EQ stars summary'),
    'field' => array(
      'title' => t('EQ stars summary'),
      'help' => t('Shows a summary of the total EQ stars a user has.'),
      'id' => 'eq_stars_summary',
    ),
  );
}
