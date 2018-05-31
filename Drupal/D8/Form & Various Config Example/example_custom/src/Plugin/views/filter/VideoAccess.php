<?php

/**
 * @file
 * Definition of \Drupal\example_custom\Plugin\views\filter\VideoAccess
 */

namespace Drupal\example_custom\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Filters videos by access status via field_media_Access
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("example_video_access")
 */
class VideoAccess extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Video access');
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
      'access' => 'Has access',
      'noaccess' => 'Does not have access',
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
  public function query() {
    // Add the necessary joins and conditions for video progress status
    // @todo only 'is one of' is implemented. Need to do 'is not one of'
    $current_user_id = \Drupal::currentUser()->id();
    $current_user = \Drupal::entityTypeManager()->getStorage('user')->load($current_user_id);
    if (!(empty($this->value) or $current_user->hasRole('administrator') or $current_user->hasRole('manager') or $current_user->hasRole('editor'))) {
      // $this->value is an array of the possible values: array('access', 'noaccess')
      // $this->operator is a string containing either 'in' or 'not in'
      $this->ensureMyTable();
      $table = $this->tableAlias;
      $field = $this->realField;

      // Join with the user media access field
      $join = Views::pluginManager('join')->createInstance('standard', [
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
      $this->query->addRelationship('fuma', $join, $table);

      // Restrict to the current user or NULL if the user has no video progress field
      $this->query->addWhere('AND', db_or()
        ->condition('fuma.entity_id', \Drupal::currentUser()->id())
      );
    }
  }

}
