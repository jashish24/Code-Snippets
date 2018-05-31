<?php

/**
 * @file
 * Definition of Drupal\example_custom\Plugin\views\field\EQStarsSummary
 */

namespace Drupal\example_custom\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to show a summary of the total EQ stars a user has
 * @todo finish writing this... its goal is to display a message in relation
 *       with the number of EQ stard found
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eq_stars_summary")
 */
class EQStarsSummary extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    // @todo ...
    // ksm($values);
    $elements = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['star-rating-field'],
        'data-rateyo-rating' => '65',
      ],
    ];
    return $elements;
  }
}
