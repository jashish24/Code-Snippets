<?php
/**
 * @file
 * Contains \Drupal\plusminuscode_custom\Form\QuizForm
 * Add quiz form definition
 */

namespace Drupal\plusminuscode_custom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\profile\Entity\Profile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\Entity\Node;

class QuizForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  
  public function getFormId() {
    return 'quiz_form';
  }
  
  /**
   * {@inheritdoc}
   */
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $quiz_node = \Drupal::routeMatch() -> getParameter('node');
    $question_ids = $quiz_node -> get('field_questions') -> getValue();
    $count = 1;
    
    $form['questions'] = [
      '#tree' => TRUE,
    ];
    
    foreach ($question_ids as $key => $question_id_raw) {
      $question_id = $question_id_raw['target_id'];
      $question = Node::load($question_id);
      $question_title = $question -> title -> value;
      $question_options = $question -> get('field_question_options') -> getValue();
      $options = [];      

      foreach ($question_options as $key => $option) {
        $option_settings = \Drupal\field_collection\Entity\FieldCollectionItem::load($option['value']);
        $is_correct_answer = $option_settings -> field_correct_answer -> value;
        $is_hidden_eq_star = $option_settings -> field_hidden_eq_star -> value;
        $option_text = $option_settings -> field_option -> value;
        $option_explanation_text = $option_settings -> field_option_explanation -> value;
        $options[$option['value']] = $option_text;
        $options_attributes[$option['value']] = [
          'data-explanation' => $option_explanation_text,
          'data-correct-answer' => $is_correct_answer,
          'data-hidden-eq' => $is_hidden_eq_star,
          'data-question-id' => $question_id,
          'data-option-id' => $option['value'],
        ];
      }

      $form['questions'][$question_id] = [
        '#type' => 'checkboxes',
        '#id' => 'quiz-question-' . $question_id,
        '#title' => $this -> t($question_title),
        '#options' => $options,
        '#options_attributes' => $options_attributes,
        '#weight' => 1,
      ];
      
      if ($count < count($question_ids)) {
        $form['questions'][$question_id]['next'] = [
          '#type' => 'button',
          '#value' => $this -> t('Next'),
          '#attributes' => array(
            'class' => array('quiz-next'),
          ),
          '#weight' => 1,
        ];
      }
      
      $count++;
    }
    
    $form['quiz_id'] = [
      '#type' => 'hidden',
      '#value' => $quiz_node -> id(),
    ];
    
    /* $form['vimeo_video'] = [
      '#type' => 'inline_template',
      '#template' => '<div id="vimeo-video"><iframe src="{{ url }}" width="640" height="360" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>',
      '#context' => [
        'url' => 'https://player.vimeo.com/video/20524411',
      ],
    ]; */
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this -> t('Finish'),
      '#button_type' => 'primary',
    );
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    $user_progress_query = \Drupal::entityQuery('node')
      -> condition('status', 1)
      -> condition('type', 'child_progress')
      -> condition('field_attached_user', $current_user -> id())
      -> execute();

    $user_progress_raw = array_values($user_progress_query);
    $user_progress_id = $user_progress_raw[0];
    
    // Get form state values
    $form_values = $form_state -> getValues();
    $question_data = $form_values['questions'];
    
    foreach ($question_data as $question_id => $options_data) {
      unset($options_data['next']);
      $user_progress_node = Node::load($user_progress_id);
      $existing_record = false;
      $already_saved_questions = $user_progress_node -> get('field_questions_answered') -> getValue();
      $options_selected = [];
      
      foreach ($options_data as $key => $value) {
        if ($value !== 0) {
          $options_selected[$key] = $value;
        }
      }
      
      foreach ($already_saved_questions as $key => $already_saved_question) {
        $already_saved_question_settings = \Drupal\field_collection\Entity\FieldCollectionItem::load($already_saved_question['value']);
        $already_saved_question_raw_id = $already_saved_question_settings -> get('field_questions_list') -> getValue();
        $already_saved_question_id = isset($already_saved_question_raw_id[0]['target_id']) ? $already_saved_question_raw_id[0]['target_id'] : 0;
        
        if ($already_saved_question_id == $question_id) {
          //Set the field_options_selected value.
          $already_saved_question_settings -> set('field_options_selected', $options_selected);
          //Set the field_questions_list value
          $already_saved_question_settings -> set('field_questions_list', $question_id);
          //Save the field_collection item. This will save the host node too.
          $already_saved_question_settings -> save();
          //Update status that video record already exists (avoiding addition of duplicate entries)
          $existing_record = true;
        }
      }
      
      if (!$existing_record) {
        $field_collection_questions_answered = entity_create('field_collection_item', array('field_name' => 'field_questions_answered'));
        //Set the relationship to the host node.
        $field_collection_questions_answered -> setHostEntity($user_progress_node);
        //Set the field_options_selected value.
        $field_collection_questions_answered -> set('field_options_selected', $options_selected);
        //Set the field_questions_list value
        $field_collection_questions_answered -> set('field_questions_list', $question_id);
        //Save the field_collection item. This will save the host node too.
        $field_collection_questions_answered -> save();
      }
    }
  }
}