<?php

namespace Drupal\example_custom\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_store\Command;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *  id = "example_custom_products_block",
 *  admin_label = @Translation("Products block"),
 *  category = @Translation("Content")
 * )
 */

class ProductsBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */

  public function build() {
    $current_user = \Drupal::entityTypeManager() -> getStorage('user') -> load(\Drupal::currentUser() -> id());

    $current_store_node = \Drupal::routeMatch() -> getParameter('node');
    $store_product_types_list = $current_store_node -> get('field_product_types') -> getValue();
    //To get the lanuage code:
    $language = \Drupal::languageManager() -> getCurrentLanguage() -> getId();
    $store_product_types = [];

    $ip = get_client_ip();
    $country_code = ip2country_get_country($ip);

    if (!$country_code) {

      //CreateStoreCommand $default_store;
      $store_storage = \Drupal::entityTypeManager() -> getStorage('commerce_store');
      $default_store = $store_storage -> loadDefault();

      $default_store_billing_countries = $default_store -> get('billing_countries') -> getValue();

      if (!empty($default_store_billing_countries)) {
        $country_code = $default_store_billing_countries[0]['value'];
      }
      else {
        $country_code = 'US';
      }
    }

    foreach ($store_product_types_list as $key => $store_product_type) {
      $store_product_types[] = $store_product_type['target_id'];
    }

    // To check if user role have access to product {commerce_product__field_restrict_by_roles}
    $current_user_roles = $current_user -> getRoles(TRUE);

    $database = \Drupal::database();

    $query = $database -> select('commerce_product','cp');
    $query -> join('commerce_product_variation_field_data', 'cpvd', 'cpvd.product_id = cp.product_id');
    $query -> join('commerce_product__variations', 'cpvs', 'cpvs.variations_target_id = cpvd.variation_id');
    $query -> join('commerce_store_field_data', 'csd', 'csd.default_currency = cpvd.price__currency_code');
    $query -> join('commerce_store__billing_countries', 'csbc', 'csbc.entity_id = csd.store_id');
    $query -> leftjoin('commerce_product__field_restrict_by_roles', 'rbr', 'rbr.entity_id = cp.product_id');
    $query -> condition('csbc.billing_countries_value', $country_code)
      -> condition('cpvs.bundle', $store_product_types, 'IN')
      -> condition('cpvs.deleted', 0)
      -> condition('rbr.field_restrict_by_roles_target_id', $current_user_roles, 'NOT IN')
      -> fields('cp', array('product_id '))
      -> fields('cpvd', array('variation_id'))
      -> distinct();

    $product_data = $query -> execute();
    $product_data_list = $product_data -> fetchAll();
    
    /* If 'field_restrict_by_roles_target_id' is null (or no restrict has been selected based on roles) then above query will return null set
     * Running query again if product list is null because it can be because of no role selected for restriction
     */
    if (empty($product_data_list)) {
      $database_again = \Drupal::database();

      $query_again = $database_again -> select('commerce_product','cp');
      $query_again -> join('commerce_product_variation_field_data', 'cpvd', 'cpvd.product_id = cp.product_id');
      $query_again -> join('commerce_product__variations', 'cpvs', 'cpvs.variations_target_id = cpvd.variation_id');
      $query_again -> join('commerce_store_field_data', 'csd', 'csd.default_currency = cpvd.price__currency_code');
      $query_again -> join('commerce_store__billing_countries', 'csbc', 'csbc.entity_id = csd.store_id');
      $query_again -> condition('csbc.billing_countries_value', $country_code)
        -> condition('cpvs.bundle', $store_product_types, 'IN')
        -> condition('cpvs.deleted', 0)
        -> fields('cp', array('product_id '))
        -> fields('cpvd', array('variation_id'))
        -> distinct();

      $product_data_again = $query_again -> execute();
      $product_data_list = $product_data_again -> fetchAll();
    }

    $products = [];

    foreach ($product_data_list as $key => $product_raw_id) {
      $product = \Drupal\commerce_product\Entity\Product::load($product_raw_id -> product_id);
      $entity_type = 'commerce_product';
      $view_mode = 'store_list';
      $viewable_product = \Drupal::entityTypeManager() -> getViewBuilder($entity_type) -> view($product, $view_mode);
      $products[] = $viewable_product;
    }
    
    if (count($products) == 0) {
      $products = [
        '#type' => 'markup',
        '#markup' => $this -> t('Currently there are no products available in your location.'),
      ];
    }

    return $products;
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
    $this -> configuration['example_custom_quiz_block_settings'] = $form_state -> getValue('example_custom_quiz_block_settings');
  }
}
