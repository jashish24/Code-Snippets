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
 * Plugin implementation of the 'star_rating' formatter.
 *
 * @FieldFormatter(
 *   id = "star_rating",
 *   label = @Translation("Star rating"),
 *   field_types = {
 *     "integer", "computed_integer", "numeric", "eq_stars_summary"
 *   }
 * )
 */
class StarRatingFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size', [], ['context' => 'px']),
      '#min' => 1,
      '#default_value' => $this->getSetting('size'),
      '#description' => t('The star width in pixels.'),
      '#weight' => 0,
    ];

    $elements['maxvalue'] = [
      '#type' => 'number',
      '#title' => t('Maximum value of rating'),
      '#min' => 1,
      '#default_value' => $this->getSetting('maxvalue'),
      '#weight' => 5,
    ];

    $elements['foreground_color'] = [
      '#type' => 'textfield',
      '#title' => t('Star rating fill color'),
      '#default_value' => $this->getSetting('foreground_color'),
      '#description' => $this->t('Enter a hash color for the rating filling color including the # sign.'),
      '#weight' => 10,
    ];

    $elements['background_color'] = [
      '#type' => 'textfield',
      '#title' => t('Star unrated fill color'),
      '#default_value' => $this->getSetting('background_color'),
      '#description' => $this->t('Enter a hash color for the background color of unrated stars including the # sign.'),
      '#weight' => 20,
    ];

    $elements['readonly'] = [
      '#type' => 'checkbox',
      '#title' => t('Read only'),
      '#default_value' => $this->getSetting('readonly'),
      '#description' => $this->t('If checked, no mouse hover effect will be shown.'),
      '#weight' => 30,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = ($this->getSetting('size') ? $this->getSetting('size') : 'Default size');
    $summary[] = t('Max value: ') . ($this->getSetting('maxvalue') ? $this->getSetting('maxvalue') : 'Default maxValue (5)');
    $summary[] = t('Rating fill color: ') . $this->getSetting('foreground_color');
    $summary[] = t('Unrated fill color: ') . $this->getSetting('background_color');
    $summary[] = ($this->getSetting('readonly') ? 'Read only' : '');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => '',
      'maxvalue' => '100',
      'foreground_color' => '',
      'background_color' => '',
      'readonly' => true,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $value = number_format($item->value, '0');

      // Output the raw value in a content attribute if the text of the HTML
      // element differs from the raw value (for example when a prefix is used).
      if (isset($item->_attributes) && $item->value != $value) {
        $item->_attributes += ['content' => $item->value];
      }

      // <div class="star-rating" data-rating="{{ (output|trim // 20)|number_format(2,".") }}"></div>
      $elements[$delta] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['star-rating-field'],
          'data-rateyo-rating' => $value,
        ],
      ];
      if ($this->getSetting('size')) {
        $elements[$delta]['#attributes']['data-rateyo-star-width'] = $this->getSetting('size')."px";
      }
      if ($this->getSetting('maxvalue')) {
        $elements[$delta]['#attributes']['data-rateyo-max-value'] = $this->getSetting('maxvalue');
      }
      if ($this->getSetting('foreground_color')) {
        $elements[$delta]['#attributes']['data-rateyo-rated-fill'] = $this->getSetting('foreground_color');
      }
      if ($this->getSetting('background_color')) {
        $elements[$delta]['#attributes']['data-rateyo-normal-fill'] = $this->getSetting('background_color');
      }
      if ($this->getSetting('readonly')) {
        $elements[$delta]['#attributes']['data-rateyo-read-only'] = '1';
      }
    }

    return $elements;
  }
}
