<?php

/**
 * @file
 * Definition of \Drupal\example_custom\Plugin\views\filter\VideoProgress
 */

namespace Drupal\example_custom\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Filters videos by progress status: Watched, Watching, Unwatched
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("example_video_progress")
 */
class VideoProgress extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Video progress');
    $this->definition['options callback'] = array($this, 'generateOptions');
    $this->definition['allow empty'] = false;
  }

  /**
   * Helper function that generates the options.
   * @return array
   */
  public function generateOptions() {
    // Array keys are used to compare with the table field values.
    return array(
      'watched' => 'Watched',
      'watching' => 'Watching',
      'unwatched' => 'Unwatched',
    );
  }

  /**
   * Skip validation if no options have been chosen so we can use it as a
   * non-filter.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * Override the query so that no filtering takes place if the user doesn't
   * select any options.
   */
   /*
   Example query for finding watched and watching videos:

   SELECT *
   FROM
   node_field_data node_field_data
   LEFT JOIN user__field_user_media_access fuma ON node_field_data.nid = fuma.field_user_media_access_target_id AND (fuma.bundle = 'user' AND fuma.deleted = '0')
   LEFT JOIN node__field_attached_user fau ON fau.field_attached_user_target_id = fuma.entity_id AND (fau.bundle = 'user_progress' AND fau.deleted = '0')
   LEFT JOIN node__field_videos_watched fvw ON fvw.entity_id = fau.entity_id AND fvw.revision_id = fau.revision_id AND (fvw.bundle = 'user_progress' AND fvw.deleted = '0')
   LEFT JOIN field_collection_item__field_single_video fsv ON fvw.field_videos_watched_value = fsv.entity_id AND fvw.field_videos_watched_revision_id = fsv.revision_id AND (fsv.bundle = 'field_videos_watched' AND fsv.deleted = '0') AND (fsv.field_single_video_target_id = node_field_data.nid)
   LEFT JOIN field_collection_item__09f8ad5ee9 fsvc ON fvw.field_videos_watched_value = fsvc.entity_id AND fvw.field_videos_watched_revision_id = fsvc.revision_id AND (fsvc.bundle = 'field_videos_watched' AND fsvc.deleted = '0')
   LEFT JOIN field_collection_item__efb7956486 fsvp ON fvw.field_videos_watched_value = fsvp.entity_id AND fvw.field_videos_watched_revision_id = fsvp.revision_id AND (fsvp.bundle = 'field_videos_watched' AND fsvp.deleted = '0')
   WHERE ( (fuma.entity_id = '313') AND (node_field_data.status = '1') AND (node_field_data.type IN ('video')) AND (fsv.field_single_video_target_id IS NOT NULL) )
   ORDER BY node_field_data.title ASC

   ---

   Example query for finding unwatched videos:

   SELECT *
   FROM
   node_field_data node_field_data
   LEFT JOIN user__field_user_media_access fuma ON node_field_data.nid = fuma.field_user_media_access_target_id AND (fuma.bundle = 'user' AND fuma.deleted = '0')
   WHERE ( (fuma.entity_id = '313') AND (node_field_data.status = '1') AND (node_field_data.type IN ('video')) AND (node_field_data.nid NOT IN (
       SELECT node_field_data.nid
       FROM
       node_field_data node_field_data
       LEFT JOIN user__field_user_media_access fuma ON node_field_data.nid = fuma.field_user_media_access_target_id AND (fuma.bundle = 'user' AND fuma.deleted = '0')
       LEFT JOIN node__field_attached_user fau ON fau.field_attached_user_target_id = fuma.entity_id AND (fau.bundle = 'user_progress' AND fau.deleted = '0')
       LEFT JOIN node__field_videos_watched fvw ON fvw.entity_id = fau.entity_id AND fvw.revision_id = fau.revision_id AND (fvw.bundle = 'user_progress' AND fvw.deleted = '0')
       LEFT JOIN field_collection_item__field_single_video fsv ON fvw.field_videos_watched_value = fsv.entity_id AND fvw.field_videos_watched_revision_id = fsv.revision_id AND (fsv.bundle = 'field_videos_watched' AND fsv.deleted = '0') AND (fsv.field_single_video_target_id = node_field_data.nid)
       WHERE ( (fuma.entity_id = '313') AND (node_field_data.status = '1') AND (node_field_data.type IN ('video')) AND (fsv.field_single_video_target_id IS NOT NULL) )
     )))
   ORDER BY node_field_data.title ASC
   */
  public function query() {
    // Add the necessary joins and conditions for video progress status
    // @todo only 'is one of' is implemented. Need to do 'is not one of'
    if (!empty($this->value)) {
      // $this->value is an array of the possible values: array('watched', 'watching', 'unwatched')
      // $this->operator is a string containing either 'in' or 'not in'
      $this->ensureMyTable();
      $table = $this->tableAlias;
      $field = $this->realField;
      $where = new Condition('AND');

      // Get mappings for long database table names of field_collection_item. We support only SQL.
      $field_storage = [
        'field_single_video',
        'field_single_video_completed',
        'field_single_video_progress',
      ];
      $entity_storage_schema_sql = \Drupal::keyValue('entity.storage_schema.sql');
      foreach($field_storage as $field_name) {
        $schema_key = "field_collection_item.field_schema_data.$field_name";
        $field_schema_data = $entity_storage_schema_sql->get($schema_key);
        $field_storage[$field_name] = current(array_keys($field_schema_data));
      }

      // Join with the user media access field to get only videos for which the user has access
      $fumaJoin = Views::pluginManager('join')->createInstance('standard', [
       'type'       => 'LEFT',
       'table'      => 'user__field_user_media_access',
       'field'      => 'field_user_media_access_target_id',
       'left_table' => $table,
       'left_field' => $field,
       'operator'   => '=',
       'extra'      => [
         0 => [
           'field' => 'bundle',
           'value' => 'user',
         ],
         1 => [
           'field' => 'deleted',
           'value' => 0,
           'numeric' => true,
         ],
       ],
     ]);

      // Join with the attached user field to get progress node for this user
      $fauJoin = Views::pluginManager('join')->createInstance('standard', [
        'type'       => 'LEFT',
        'table'      => 'node__field_attached_user',
        'field'      => 'field_attached_user_target_id',
        'left_table' => 'fuma',
        'left_field' => 'entity_id',
        'operator'   => '=',
        'extra'      => [
          0 => [
            'field' => 'bundle',
            'value' => 'user_progress',
          ],
          1 => [
            'field' => 'deleted',
            'value' => 0,
            'numeric' => true,
          ],
        ],
      ]);

      // Join with the videos watched field collection field to get values associated with it
      $fvwJoin = Views::pluginManager('join')->createInstance('standard', [
        'type'       => 'LEFT',
        'table'      => 'node__field_videos_watched',
        'field'      => 'entity_id',
        'left_table' => 'fau',
        'left_field' => 'entity_id',
        'operator'   => '=',
        'extra'      => [
          0 => [
            'field' => 'bundle',
            'value' => 'user_progress',
          ],
          1 => [
            'field' => 'deleted',
            'value' => 0,
            'numeric' => true,
          ],
          2 => [
            'field' => 'revision_id',
            'left_field' => 'revision_id',
          ],
        ],
      ]);

      // Join the video node with the progress field single video that points to it
      $fsvJoin = Views::pluginManager('join')->createInstance('standard', [
        'type'       => 'LEFT',
        'table'      => $field_storage['field_single_video'],
        'field'      => 'entity_id',
        'left_table' => 'fvw',
        'left_field' => 'field_videos_watched_value',
        'operator'   => '=',
        'extra'      => [
          0 => [
            'field' => 'bundle',
            'value' => 'field_videos_watched',
          ],
          1 => [
            'field' => 'deleted',
            'value' => 0,
            'numeric' => true,
          ],
          2 => [
            'field' => 'revision_id',
            'left_field' => 'field_videos_watched_revision_id',
          ],
          // Unable to specify left_table in extra, so we must move this condition to the WHERE statement
          // 3 => [
          //   'field' => 'field_single_video_target_id',
          //   'left_field' => $field,
          //   'left_table' => $table, // <-- not supported. see  https://api.drupal.org/api/drupal/core%21modules%21views%21src%21Plugin%21views%21join%21JoinPluginBase.php/property/JoinPluginBase%3A%3Aextra/8.2.x
          // ],
        ],
      ]);

      // Join with the video completed field
      $fsvcJoin = Views::pluginManager('join')->createInstance('standard', [
        'type'       => 'LEFT',
        'table'      => $field_storage['field_single_video_completed'],
        'field'      => 'entity_id',
        'left_table' => 'fvw',
        'left_field' => 'field_videos_watched_value',
        'operator'   => '=',
        'extra'      => [
          0 => [
            'field' => 'bundle',
            'value' => 'field_videos_watched',
          ],
          1 => [
            'field' => 'deleted',
            'value' => 0,
            'numeric' => true,
          ],
          2 => [
            'field' => 'revision_id',
            'left_field' => 'field_videos_watched_revision_id',
          ],
        ],
      ]);

      // Join with the video progress field
      $fsvpJoin = Views::pluginManager('join')->createInstance('standard', [
        'type'       => 'LEFT',
        'table'      => $field_storage['field_single_video_progress'],
        'field'      => 'entity_id',
        'left_table' => 'fvw',
        'left_field' => 'field_videos_watched_value',
        'operator'   => '=',
        'extra'      => [
          0 => [
            'field' => 'bundle',
            'value' => 'field_videos_watched',
          ],
          1 => [
            'field' => 'deleted',
            'value' => 0,
            'numeric' => true,
          ],
          2 => [
            'field' => 'revision_id',
            'left_field' => 'field_videos_watched_revision_id',
          ],
        ],
      ]);

      // All conditions must join with fuma
      $this->query->addRelationship('fuma', $fumaJoin, $table);
      $where->condition('fuma.entity_id', \Drupal::currentUser()->id());

      // Add specific conditions and joins
      if(in_array('watched', $this->value)) {
        // Add condition for watched videos
        // Add the joins
        $this->query->addRelationship('fau', $fauJoin, $table);
        $this->query->addRelationship('fvw', $fvwJoin, $table);
        $this->query->addRelationship('fsv', $fsvJoin, $table);
        $this->query->addRelationship('fsvc', $fsvcJoin, $table);
        $this->query->addRelationship('fsvp', $fsvpJoin, $table);
        // Add the conditions
        $where->isNotNull('fsv.field_single_video_target_id');
        $where->where("fsv.field_single_video_target_id = $table.$field"); // See comment above in $fsvJoin
        $where->condition('fsvc.field_single_video_completed_value', '1');
      }
      if(in_array('watching', $this->value)) {
        // Add condition for videos that are in progress but not completed
        // Add the joins
        $this->query->addRelationship('fau', $fauJoin, $table);
        $this->query->addRelationship('fvw', $fvwJoin, $table);
        $this->query->addRelationship('fsv', $fsvJoin, $table);
        $this->query->addRelationship('fsvc', $fsvcJoin, $table);
        $this->query->addRelationship('fsvp', $fsvpJoin, $table);
        // Add the conditions
        $where->isNotNull('fsv.field_single_video_target_id');
        $where->where("fsv.field_single_video_target_id = $table.$field"); // See comment above in $fsvJoin
        $where->condition('fsvc.field_single_video_completed_value', '0');
      }
      if(in_array('unwatched', $this->value)) {
        // Add condition unwatched videos
        // Create a subselect to get all videos having a videos watched reference in progress to exclude them form the available videos
        $subSelect = \Drupal::database()->select('node_field_data', $table);
        $subSelect->fields($table, ['nid']);
        $subSelect->join('user__field_user_media_access', 'fuma', "fuma.field_user_media_access_target_id = $table.$field AND fuma.bundle = 'user' AND fuma.deleted = 0");
        $subSelect->join('node__field_attached_user', 'fau', "fau.field_attached_user_target_id = fuma.entity_id AND fau.bundle = 'user_progress' AND fau.deleted = 0");
        $subSelect->join('node__field_videos_watched', 'fvw', "fvw.entity_id = fau.entity_id AND fvw.revision_id = fau.revision_id AND fvw.bundle = 'user_progress' AND fvw.deleted = 0");
        $subSelect->join($field_storage['field_single_video'], 'fsv', "fvw.field_videos_watched_value = fsv.entity_id AND fvw.field_videos_watched_revision_id = fsv.revision_id AND fsv.bundle = 'field_videos_watched' AND fsv.deleted = '0' AND fsv.field_single_video_target_id = node_field_data.nid");
        $subSelect->condition('fuma.entity_id', \Drupal::currentUser()->id());
        $subSelect->condition("$table.status", 1);
        $subSelect->condition("$table.type", ['video'], 'IN');
        $subSelect->isNotNull('fsv.field_single_video_target_id');

        // Add the condition in views query for unwatched videos
        $where->condition("$table.$field", $subSelect, 'NOT IN');
      }
      $this->query->addWhere('AND', $where);
    }
  }

}
