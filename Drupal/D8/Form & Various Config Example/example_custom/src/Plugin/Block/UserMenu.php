<?php

namespace Drupal\example_custom\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_store\Command;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\views\Views;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides a block with user megamenu.
 *
 * @Block(
 *  id = "example_custom_user_megamenu_block",
 *  admin_label = @Translation("User Megamenu block"),
 *  category = @Translation("Content")
 * )
 */

class UserMenu extends BlockBase {
  /**
   * {@inheritdoc}
   */

  public function build() {
    $megamenu_markup = '<div id="user-megamenu">';

    //Loading current user profile pic or default if empty
    $current_user_id = \Drupal::currentUser() -> id();
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load($current_user_id);
    $user_picture = $current_user -> get('field_picture') -> getValue();
    $user_picture_html = 'User';

    //Loading default profile picture in case user has not uploaded any image
    if (empty($user_picture[0])) {
      $bundle_fields = \Drupal::getContainer() -> get('entity_field.manager') -> getFieldDefinitions('user', 'user');
      $default_user_picture = $bundle_fields['field_picture'];
      $default_user_picture_settings = $default_user_picture -> getSetting('default_image');
      $user_picture_file =  \Drupal::service('entity.manager') -> loadEntityByUuid('file', $default_user_picture_settings['uuid']);
      if ($user_picture_file) {
        $user_picture_url = ImageStyle::load('megamenu_profile_picture')->buildUrl($user_picture_file->getFileUri());
        $user_picture_html = '<img class="megamenu-profile-picture" src="' . $user_picture_url . '">';
      }
    }
    //Loading user profile picture
    else {
      $user_picture_file = File::load($user_picture[0]['target_id']);
      $user_picture_url = ImageStyle::load('megamenu_profile_picture')->buildUrl($user_picture_file->getFileUri());
      $user_picture_html = '<img class="megamenu-profile-picture" src="' . $user_picture_url . '">';
    }

    //Starting with user profile picture (default or uploaded)
    $megamenu_markup .= '<a class="user-megamenu-link" href="#">' . $user_picture_html . '<i class="icon-chevron-down"></i></a>';

    //Loading user summary view
    $args = [$current_user_id];
    $user_summary_html = '';
    $user_summary_view = Views::getView('user_summary'); //View machine name
    if (is_object($user_summary_view)) {
      $user_summary_view -> setArguments($args);
      $user_summary_view -> setDisplay('block_user_summary'); //View display machine name
      $user_summary_view -> preExecute();
      $user_summary_view -> execute();

      // Render the view
      $user_summary_render = $user_summary_view -> render();
      $user_summary_html = render($user_summary_render);
    }

    //Adding User summary view
    $megamenu_markup .= '<div class="user-megamenu-dropdown"><div class="user-megamenu-user-summary">' . $user_summary_html . '</div>';

    //Loading user account menu for making it easier to change links in future
    $menu_tree_service = \Drupal::menuTree();
    $menu_tree_parameters = $menu_tree_service -> getCurrentRouteMenuTreeParameters('account');
    $account_menu_tree = $menu_tree_service -> load('account', $menu_tree_parameters);

    // Transform the tree using the manipulators.
    $manipulators = [
      // Only show links that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      // Use the default sorting of menu links.
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $account_menu_tree = $menu_tree_service -> transform($account_menu_tree, $manipulators);
    // Finally, build a renderable array from the transformed tree.
    $account_menu = $menu_tree_service -> build($account_menu_tree);

    $account_menu_html = \Drupal::service('renderer') -> render($account_menu);

    $megamenu_markup .= '<div class="user-megamenu-account-menu">' . $account_menu_html . '</div>';
    $megamenu_markup .= '</div></div>';

    $megamenu_html = [
      '#type' => 'markup',
      '#markup' => $megamenu_markup,
    ];

    return $megamenu_html;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account -> isAuthenticated());
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
    $this -> configuration['example_custom_user_megamenu_settings'] = $form_state -> getValue('example_custom_user_megamenu_settings');
  }
}
