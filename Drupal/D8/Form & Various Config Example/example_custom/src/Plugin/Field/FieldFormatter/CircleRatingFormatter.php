<?php
/**
 * @file
 * Contains \Drupal\example_custom\Plugin\field\formatter\PosterImageFormatter.
 */

namespace Drupal\example_custom\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;


/**
 * Plugin implementation of the circle percentage formatter.
 *
 * @FieldFormatter(
 *   id = "circle_rating",
 *   label = @Translation("Circle rating"),
 *   field_types = {
 *     "integer", "computed_integer", "numeric"
 *   }
 * )
 */
class CircleRatingFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['size'] = [
      '#type' => 'select',
      '#title' => t('Size'),
      '#options' => [
        '' => t('Default'),
        'small' => t('Small'),
        'big' => t('Big'),
      ],
      '#default_value' => $this->getSetting('size'),
      '#weight' => 0,
    ];

    $elements['color'] = [
      '#type' => 'select',
      '#title' => t('Circle color'),
      '#options' => [
        '' => t('Default (Blue)'),
        'green' => t('Green'),
        'orange' => t('Orange'),
      ],
      '#default_value' => $this->getSetting('color'),
      '#weight' => 10,
    ];

    $elements['center'] = [
      '#type' => 'checkbox',
      '#title' => t('Center'),
      '#default_value' => $this->getSetting('center'),
      '#weight' => 20,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = ($this->getSetting('size') ? $this->getSetting('size') : 'Default size');
    $summary[] = ($this->getSetting('color') ? $this->getSetting('color') : 'Default color (blue)');
    $summary[] = ($this->getSetting('center') ? 'Align center' : '');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 'default',
      'color' => 'blue',
      'center' => false,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $value = number_format($item->value, 0, '', '');

      // Output the raw value in a content attribute if the text of the HTML
      // element differs from the raw value (for example when a prefix is used).
      if (isset($item->_attributes) && $item->value != $value) {
        $item->_attributes += ['content' => $item->value];
      }

      // <div class="c100 p33 center">
      //     <span>33%</span>
      //     <div class="slice"><div class="bar"></div><div class="fill"></div></div>
      // </div>
      $elements[$delta] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'c100',
            "p$value",
            ($this->getSetting('size') ? $this->getSetting('size') : ''),
            ($this->getSetting('color') ? $this->getSetting('color') : ''),
            ($this->getSetting('center') ? 'center' : ''),
          ],
        ],
        "value" => [
          '#markup' => "<span>$value%</span>",
        ],
        "slice" => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['slice'],
          ],
          "bar" => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['bar'],
            ],
          ],
          "fill" => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['fill'],
            ],
          ]
        ]
      ];
    }

    return $elements;
  }
}
