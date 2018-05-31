<?php
/**
 * @file
 * Contains \Drupal\example_custom\Form\QuizForm
 * Add quiz form definition
 */

namespace Drupal\example_custom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\profile\Entity\Profile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

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
    // Building quiz form from the Quiz type node
    $form = [];
    $quiz_node = \Drupal::routeMatch() -> getParameter('node');
    $quiz_id = $quiz_node -> id();
    // Getting current quiz node
    $questions_group_ids = $quiz_node -> get('field_quiz_questions') -> getValue();
    
    // Getting current user details
    $current_user_id = \Drupal::currentUser() -> id();
    $quiz_user = \Drupal::entityTypeManager() -> getStorage('user') -> load($current_user_id);
    $current_user = clone $quiz_user;
    $my_progress = 'my-progress';
    $access_other_progress = FALSE;
    
    if (isset($_GET['user']) && is_numeric($_GET['user'])) {
      $quiz_user_id = strip_tags($_GET['user']);
      $quiz_user = \Drupal::entityTypeManager() -> getStorage('user') -> load($quiz_user_id);
      $my_progress = ($current_user_id != $quiz_user_id) ? 'other-progress' : $my_progress;
      $access_other_progress = get_other_quiz_access_info($current_user, $quiz_user_id);
    }
    
    $answers_summary = [];

    $current_user_progress_id = isset($quiz_user -> user_progress_id) ? $quiz_user -> user_progress_id : 0;
    
    if ($current_user_progress_id > 0) {
      // Loading user progress node and then if they have submitted quiz or not
      $user_progress_node = Node::load($current_user_progress_id);
      
      // EQ stars already found
      $eq_selected_raw = $user_progress_node -> get('field_eq_star_questions_selected') -> getValue();
      $eq_selected = [];
      
      foreach ($eq_selected_raw as $key => $option_id) {
        $eq_selected[] = $option_id['target_id'];
      }
      
      $answers_summary_raw = $user_progress_node -> get('field_answers_summary') -> getValue();
      $answers_summary = isset($answers_summary_raw[0]['value']) ? unserialize($answers_summary_raw[0]['value']) : [];
      $answers_summary = isset($answers_summary[$quiz_id]) ? $answers_summary[$quiz_id] : [];
    }
    
    if (!$access_other_progress) {
      $form['#theme'] = 'quiz_form';
      
      $form['quiz_not_taken'] = [
        '#type' => 'markup',
        '#markup' => $this -> t('You don\'t have access to view this report'),
      ];
      
      return $form;
    }
    if ($my_progress == 'other-progress' && empty($answers_summary)) {
      $form['#theme'] = 'quiz_form';
      
      $form['quiz_not_taken'] = [
        '#type' => 'markup',
        '#markup' => $this -> t('The user has not taken this quiz yet'),
      ];
      
      return $form;
    }
    
    $question_ids = [];
    $question_group_count = 1;
    
    // Building question groups with images for all the groups attached to a quiz
    foreach ($questions_group_ids as $key => $questions_group_id_raw) {
      $questions_group_id = $questions_group_id_raw['target_id'];
      $questions_group_wrapper = 'question_group_' . $questions_group_id;
      $question_group_node = Node::load($questions_group_id);
      $question_group_image_raw = $question_group_node -> get('field_group_image') -> getValue();
      $question_group_image_html = '';
      $image_style = 'full_width_100';
      
      $storage = \Drupal::entityTypeManager() -> getStorage('entity_view_display');
      $view_display = $storage -> load('node.questions_group.default');
      $view_display_settings = $view_display -> toArray();
      
      if (isset($view_display_settings['content']['field_group_image']['settings']['image_style'])) {
        $image_style = $view_display_settings['content']['field_group_image']['settings']['image_style'];
      }
      
      if (!empty($question_group_image_raw)) {
        $question_group_image_file = File::load($question_group_image_raw[0]['target_id']);
        $image_uri = $question_group_image_file -> getFileUri();
        $question_group_image = [
          '#theme' => 'image_style',
          '#style_name' => $image_style,
          '#uri' => $image_uri,
          '#attributes' => [
            'class' => [
              'quiz-image-' . $question_group_count,
              'quiz-image',
            ],
          ],
        ];
        // Getting group image
        $image_path = file_create_url($image_uri);
        
        // Loading link with actual group image link and small image as content
        // data-fancybox is for applying fancybox jquery
        $question_group_image_html = '<div class="quiz-image-container quiz-image-container-' . $question_group_count . '"><a class="quiz-fancy-img" data-fancybox href="' . $image_path . '">' . render($question_group_image) . '</a></div>';
      }
      
      $question_ids = $question_group_node -> get('field_questions') -> getValue();
      
      $count = 1;
      // Building separate tree for each quiz group
      $form['question_group'][$questions_group_wrapper]['questions'] = [
        '#tree' => TRUE,
        '#weight' => 3,
      ];
      
      $form['question_group'][$questions_group_wrapper]['group_image'] = [
        '#type' => 'markup',
        '#markup' => $question_group_image_html,
        '#weight' => 1,
      ];
      
      // Displaying IMAGE<num> so that can check that image is changed
      $form['question_group'][$questions_group_wrapper]['group_image_number'] = [
        '#type' => 'markup',
        '#markup' => '<h4 class="quiz-image-heading quiz-image-heading-' . $question_group_count . '">' . $this -> t('IMAGE') . $question_group_count . '</h4>',
        '#weight' => 2,
      ];
      
      foreach ($question_ids as $key => $question_id_raw) {
        // Building questions with attached option as checkboxes
        $question_id = $question_id_raw['target_id'];
        $question = Node::load($question_id);
        $layout_class_raw = $question -> get('field_question_layout') -> getValue();
        $layout_class = isset($layout_class_raw[0]) ? $layout_class_raw[0]['value'] : 'question_layout_1_col';
        $question_title = $question -> title -> value;
        $question_options = $question -> get('field_question_options') -> getValue();
        $options = [];
        $options_attributes = [];
        $question_default_value = [];

        foreach ($question_options as $key => $option) {
          $is_eq_selected = (in_array($option['value'], $eq_selected)) ? 1 : 0;
          $option_settings = \Drupal\field_collection\Entity\FieldCollectionItem::load($option['value']);
          $option_text = $option_settings -> field_option -> value;
          
          $options[$option['value']] = $option_text;
          
          if (count($answers_summary) > 0) {
            // Adding quiz-review class to let javascript and css know that quiz is already submitted 
            $form['question_group'][$questions_group_wrapper]['#prefix'] = '<div class="quiz-container quiz-review ' . $my_progress . '">';
            $form['question_group'][$questions_group_wrapper]['#suffix'] = '</div>';
            // Building options attached to each checkbox or answer attached to a question
            $is_correct_answer = $option_settings -> field_correct_answer -> value;
            $is_hidden_eq_star = $option_settings -> field_hidden_eq_star -> value;
            $option_explanation_text = $option_settings -> field_option_explanation -> value;
            $is_selected_correct = 0;
            $is_selected_wrong = 0;
            
            if (isset($answers_summary[$question_id])) {
              if ($answers_summary[$question_id][$option['value']] == 'correct') {
                $is_selected_correct = 1;
              }
              else if ($answers_summary[$question_id][$option['value']] == 'wrong') {
                $is_selected_wrong = 1;
              }
            }
            
            // New module option_attributes is used to process below datasets for each option
            $options_attributes[$option['value']] = [
              'data-explanation' => $option_explanation_text, // Answer explanation
              'data-correct-answer' => $is_correct_answer, // If answer is correct
              'data-hidden-eq' => $is_hidden_eq_star, // If answer has EQ star
              'data-question-id' => $question_id, // Question id for javascript
              'data-option-id' => $option['value'], // Option id for EQ star found track
              'data-selected-correct' => $is_selected_correct, // If this was marked correct or not
              'data-selected-wrong' => $is_selected_wrong, // If this was marked wrong
              'data-eq-selected' => $is_eq_selected, // If the eq is already selected
            ];
          }
          else {
            // Adding quiz-unreview class if at all required
            $form['question_group'][$questions_group_wrapper]['#prefix'] = '<div class="quiz-container quiz-unreview">';
            $form['question_group'][$questions_group_wrapper]['#suffix'] = '</div>';
          }
        }
        
        $question_wrapper_class = 'normal';

        if ($count == 1 && $question_group_count == 1) {
          $question_wrapper_class = 'first';
        }
        else if ($count == count($question_ids) && $question_group_count == count($questions_group_ids)) {
          $question_wrapper_class = 'last';
        }

        // Building actual form elements
        $form['question_group'][$questions_group_wrapper]['questions'][$question_id] = [
          '#type' => 'checkboxes',
          '#id' => 'quiz-question-' . $question_id,
          '#title' => $this -> t($question_title),
          '#options' => $options,
          '#options_attributes' => $options_attributes,
          '#weight' => 1,
          '#default_value' => isset($answers_summary[$question_id]['marked']) ? $answers_summary[$question_id]['marked'] : [],
          '#attributes' => [
            'class' => ['question-wrapper', $question_wrapper_class, $layout_class],
          ],
        ];
        
        $form['question_group'][$questions_group_wrapper]['questions'][$question_id]['prev'] = [
          '#type' => 'button',
          '#value' => $this -> t('Prev'),
          '#attributes' => [
            'class' => ['quiz-prev'],
          ],
          '#weight' => 1,
          '#prefix' => '<div class="prev-next-wrap">'
        ];
        
        $form['question_group'][$questions_group_wrapper]['questions'][$question_id]['next'] = [
          '#type' => 'button',
          '#value' => $this -> t('Next'),
          '#attributes' => [
            'class' => ['quiz-next'],
          ],
          '#weight' => 1,
          '#suffix' => '</div>',
        ];
        
        $count++;
      }
      
      $question_group_count++;
    }
    
    $form['quiz_id'] = [
      '#type' => 'hidden',
      '#value' => $quiz_node -> id(),
    ];
    
    // EQ star pop content to tell user that they have found EQ star
    $form['eq_content'] = [
      '#type' => 'markup',
      '#markup' => '<div id="eq-found-wrapper"><div class="eq-overlay">&nbsp;</div><div class="eq-found"><h4>' . $this -> t('Congratulation!! You have found a star.') . '</h4><img src="/sites/default/files/eq-star.png"></div></div>',
    ];
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => (count($answers_summary) > 0) ? $this -> t('Finish Review') : $this -> t('Finish'),
      '#button_type' => 'primary',
      '#prefix' => '<div class="finish-quiz-wrap">',
      '#suffix' => '</div>',
    ];
    
    $form['#theme'] = 'quiz_form';
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Processing quiz submission and saving progress to current user progress node
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());
    $user_progress_id = isset($current_user -> user_progress_id) ? $current_user -> user_progress_id : 0;
    $user_progress_node = Node::load($user_progress_id);
    $answers_summary_raw = $user_progress_node -> get('field_answers_summary') -> getValue();
    $answers_summary = isset($answers_summary_raw[0]['value']) ? unserialize($answers_summary_raw[0]['value']) : [];

    $correct_options_selected = 0;
    $correct_options_available = 0;
    
    // Get form state values
    $form_values = $form_state -> getValues();
    $quiz_id = $form_values['quiz_id'];
    //get submit button string
    $submit_button = $form_values['op'] -> __toString();
    $quiz_node = Node::load($quiz_id);
    $video_id_raw = $quiz_node -> get('field_quiz_episode') -> getValue();
    $video_id = isset($video_id_raw[0]['target_id']) ? $video_id_raw[0]['target_id'] : 0;
    
    // Redirect on Finish Review Button
    if ($submit_button == 'Finish Review') {
      if ($video_id > 0) {
        drupal_set_message(t('Your review has been completed successfully.'));
        
        $alias = \Drupal::service('path.alias_manager') -> getAliasByPath('/node/' . $video_id);
        $response = new RedirectResponse($alias, 301);
        $response -> send();
      }
    }
    
    $question_data = $form_values['questions'];
    
    $correct_options_db = \Drupal::database();
    $correct_options_query = $correct_options_db -> select('node__field_quiz_questions', 'fqq');
    $correct_options_query -> join('node__field_questions', 'fq', 'fq.entity_id = fqq.field_quiz_questions_target_id');
    $correct_options_query -> join('node__field_question_options', 'fqo', 'fqo.entity_id = fq.field_questions_target_id');
    $correct_options_query -> join('field_collection_item__field_correct_answer', 'fca', 'fca.entity_id = fqo.field_question_options_value');
    $correct_options_query -> condition('fqq.entity_id', $quiz_id)
      -> fields('fca', ['entity_id', 'field_correct_answer_value']);
    $correct_options = $correct_options_query -> execute() -> fetchAllAssoc('entity_id');
    $correct_options_available = count($correct_options);

    $eq_available_db = \Drupal::database();
    $eq_available_query = $eq_available_db -> select('node__field_quiz_questions', 'fqq');
    $eq_available_query -> join('node__field_questions', 'fq', 'fq.entity_id = fqq.field_quiz_questions_target_id');
    $eq_available_query -> join('node__field_question_options', 'fqo', 'fqo.entity_id = fq.field_questions_target_id');
    $eq_available_query -> join('field_collection_item__field_hidden_eq_star', 'fhes', 'fhes.entity_id = fqo.field_question_options_value');
    $eq_available_query -> condition('fqq.entity_id', $quiz_id)
      -> condition('fhes.field_hidden_eq_star_value', 1)
      -> fields('fhes', ['entity_id']);
    $eq_available_result = $eq_available_query -> execute() -> fetchAllAssoc('entity_id');
    $eq_available_count = count($eq_available_result);

    $answers_summary[$quiz_id] = [];

    foreach ($question_data as $question_id => $options_data) {
      unset($options_data['next']);
      unset($options_data['prev']);
      
      $answers_summary[$quiz_id][$question_id] = [];
      foreach ($options_data as $key => $value) {
        if (($value == 0 && $correct_options[$key] -> field_correct_answer_value == 0) || ($value !== 0 && $correct_options[$key] -> field_correct_answer_value == 1)) {
          $answers_summary[$quiz_id][$question_id][$key] = 'correct';
          $correct_options_selected++;
        }
        else {
          $answers_summary[$quiz_id][$question_id][$key] = 'wrong';
        }
        
        if ($value !== 0) {
          $answers_summary[$quiz_id][$question_id]['marked'][] = $key;
        }
        
      }
    }

    $user_progress_node -> set('field_answers_summary', serialize($answers_summary));
    
    $already_saved_quizzes = $user_progress_node -> get('field_completed_quiz_status') -> getValue();
    $existing_record = false;
    
    foreach ($already_saved_quizzes as $key => $already_saved_quiz) {
      $already_saved_quiz_settings = \Drupal\field_collection\Entity\FieldCollectionItem::load($already_saved_quiz['value']);
      $already_saved_quiz_raw_id = $already_saved_quiz_settings -> get('field_quiz_id') -> getValue();
      $already_saved_quiz_id = isset($already_saved_quiz_raw_id[0]['target_id']) ? $already_saved_quiz_raw_id[0]['target_id'] : 0;
      
      if ($already_saved_quiz_id == $quiz_id) {
        //Set the field_available_correct_options value.
        $already_saved_quiz_settings -> set('field_available_correct_options', $correct_options_available);
        //Set the field_marked_correct_options value
        $already_saved_quiz_settings -> set('field_marked_correct_options', $correct_options_selected);
        //Set the field_eq_stars_available value
        $already_saved_quiz_settings -> set('field_eq_stars_available', $eq_available_count);
        //Save the field_collection item. This will save the host node too.
        $already_saved_quiz_settings -> save();
        //Update status that quiz record already exists (avoiding addition of duplicate entries)
        $existing_record = true;
      }
    }
    
    if (!$existing_record) {
      $completed_quiz_status = entity_create('field_collection_item', ['field_name' => 'field_completed_quiz_status']);
      //Set the relationship to the host node.
      $completed_quiz_status -> setHostEntity($user_progress_node);
      //Set the field_quiz_id value.
      $completed_quiz_status -> set('field_quiz_id', $quiz_id);
      //Set the field_available_correct_options value.
      $completed_quiz_status -> set('field_available_correct_options', $correct_options_available);
      //Set the field_marked_correct_options value
      $completed_quiz_status -> set('field_marked_correct_options', $correct_options_selected);
      //Set the field_eq_stars_available value
      $completed_quiz_status -> set('field_eq_stars_available', $eq_available_count);
      //Save the field_collection item. This will save the host node too.
      $completed_quiz_status -> save();
    }
    
    $user_progress_node -> save();
    
    if ($submit_button != 'Finish Review') {
      drupal_set_message(t('Your answers has been submitted. Please find the result as below.'));
    }
    
    if ($video_id > 0) {
      $alias = \Drupal::service('path.alias_manager') -> getAliasByPath('/node/' . $video_id);
      $response = new RedirectResponse($alias, 301);
      $response -> send();
    }
  }
}