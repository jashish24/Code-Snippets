<?php

namespace Drupal\plusminuscode_custom\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *  id = "plusminuscode_custom_quiz_block",
 *  admin_label = @Translation("Quiz block"),
 *  category = @Translation("Forms")  
 * )
 */
 
class QuizBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  
  public function build() {
    $form = \Drupal::formBuilder() -> getForm('Drupal\plusminuscode_custom\Form\QuizForm');
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }
  
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this -> getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this -> configuration['plusminuscode_custom_quiz_block_settings'] = $form_state -> getValue('plusminuscode_custom_quiz_block_settings');
  }
}