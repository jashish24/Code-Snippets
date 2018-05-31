<?php
/**
 * @file
 * Contains \Drupal\example_custom\Plugin\field\formatter\PosterImageFormatter.
 */

namespace Drupal\example_custom\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\Entity\Node;

/**
 * Plugin implementation of the 'poster_image' formatter.
 *
 * @FieldFormatter(
 *   id = "poster_image",
 *   label = @Translation("example Poster Image Formatter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */

class PosterImageFormatter extends FormatterBase {
  /**
  * {@inheritdoc}
  */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    // ksm($items);
    return $element;
  }
}
