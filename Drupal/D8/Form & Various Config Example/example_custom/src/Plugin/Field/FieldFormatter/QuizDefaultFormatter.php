<?php
/**
 * @file
 * Contains \Drupal\example_custom\Plugin\field\formatter\QuizDefaultFormatter.
 */

namespace Drupal\example_custom\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\Entity\Node;

/**
 * Plugin implementation of the 'quiz_default' formatter.
 *
 * @FieldFormatter(
 *   id = "quiz_default",
 *   label = @Translation("example Quiz Formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */

class QuizDefaultFormatter extends FormatterBase {
  /**
  * {@inheritdoc}
  */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $quiz_node = \Drupal::routeMatch() -> getParameter('node');
    $quiz_node_id = $quiz_node -> id();

    $form = [
      '#prefix' => '<form id="quiz-wrapper">',
      '#suffix' => '</form>',
    ];

    $form['quiz_id'] = [
      '#type' => 'hidden',
      '#value' => $quiz_node_id,
    ];

    $count = 1;
    $items_count = count($items);
    foreach ($items as $delta => $item) {
      $question_id = $item -> target_id;
      $question = Node::load($question_id);
      $question_title = $question -> title -> value;
      $question_options = $question -> get('field_question_options') -> getValue();
      $options = [];

      $form[$delta]['question_' . $question_id] = [
        '#prefix' => '<div class="question-wrapper question-wrapper-' . $question_id . '">',
        '#suffix' => '</div>',
      ];

      foreach ($question_options as $key => $option) {
        $option_settings = \Drupal\field_collection\Entity\FieldCollectionItem::load($option['value']);
        $is_correct_answer = $option_settings -> field_correct_answer -> value;
        $is_hidden_eq_star = $option_settings -> field_hidden_eq_star -> value;
        $option_text = $option_settings -> field_option -> value;
        $option_explanation_text = $option_settings -> field_option_explanation -> value;

        $form[$delta]['question_' . $question_id]['info'] = [
          '#markup' => '<h3>' . $this -> t($question_title) . '</h3>',
        ];

        $form[$delta]['question_' . $question_id][$option['value']] = [
          '#type' => 'checkbox',
          '#id' => 'quiz-question-' . $question_id . '-' . $option['value'],
          '#title' => $this -> t($option_text),
          '#return_value' => $option['value'],
          '#default_value' => isset($option['value']) ? $option['value'] : NULL,
          '#attributes' => [
            'data-explanation' => $option_explanation_text,
            'data-correct-answer' => $is_correct_answer,
            'data-hidden-eq' => $is_hidden_eq_star,
            'data-question-id' => $question_id,
            'data-option-id' => $option['value'],
          ],
        ];
      }

      $form[$delta]['question_' . $question_id]['next'] = [
        '#type' => 'button',
        '#value' => $this -> t('Next'),
        '#attributes' => array(
          'class' => array('quiz-next'),
        ),
      ];

      if ($count == $items_count) {
        $form[$delta]['question_' . $question_id]['finish'] = [
          '#type' => 'button',
          '#value' => $this -> t('Finish'),
          '#id' => 'quiz-finish',
        ];
      }

      $count++;
    }

    return $form;
  }
}
