<?php

namespace Drupal\product\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a block for Product purchase link QR Code
 *
 * @Block(
 *  id = "product_purchase_link_qr_code",
 *  admin_label = @Translation("Product Purchase QR"),
 *  category = @Translation("Content")
 * )
 */

class ProductQRCode extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    // Load current node to automatically fetch related purchase link
	  $current_node = \Drupal::routeMatch() -> getParameter('node');
	
    // Check if block is placed in proper entity type with field field_purchase_link
	  if (($current_node instanceof \Drupal\node\NodeInterface) && $current_node -> hasField('field_purchase_link')) {
      //Prepare Current node related purchase link with proper sanitization
      $purchase_link_raw = $current_node -> get('field_purchase_link') -> getValue();
      $purchase_link = isset($purchase_link_raw[0]) ? trim(strip_tags($purchase_link_raw[0]['value'])) : '';
      
      //Check if the Purchase link is in proper URL format or googleapi will not work
      if (filter_var($purchase_link, FILTER_VALIDATE_URL)) {
        $data = [];
        $data['height'] = 300;
        $data['width'] = 300;
        // Google service for generating QR code based on url passed
        $google_qr_service = 'https://chart.googleapis.com/chart?chs=' . $data['width'] . 'x' . $data['height'] . '&cht=qr&chl=' . urlencode($purchase_link) . '&choe=UTF-8';
        // Tell user that this QR code will take them to purchase link
        $data['title'] = $this -> t('Purchase') . ' ' . $current_node -> get('title') -> value;
        $data['purchase_link'] = $google_qr_service;
        $data['description_head'] = $this -> t('Scan here on your mobile');
        $data['description'] = $this -> t('To purchase this product on our app to avail exclusive app-only');
        
        //Renderable array with theme suggestion
        $qr_renderable = [
          '#theme' => 'product_qr_code',
          '#data' => $data,
        ];

        return $qr_renderable;
      }
      else {
        // Tell user that url format is improper
        $improper_url = [
          '#type' => 'markup',
          '#markup' => '<p>' . $this -> t('Please input proper URL to generate QR code. Visit @url_standard for more details.', ['@url' => 'http://www.landofcode.com/html-tutorials/url-format.php']) . '</p>',
        ];
        
        return $improper_url;
      }
    }
    else {
      // Tell user that this entity type is not supported for QR code.
      $not_supported = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this -> t('This entity type does not support QR code.') . '</p>',
      ];
      
      return $not_supported;
    }
  }

  /**
  * {@inheritdoc}
  */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowed();
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
    $this -> configuration['product_purchase_link_qr_code_settings'] = $form_state -> getValue('product_purchase_link_qr_code_settings');
  }
}