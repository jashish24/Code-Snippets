<?php

namespace Drupal\xml_content_import;

/**
 * Trait SimpleXMLParseTrait.
 *
 * @package Drupal\xml_content_import
 */
trait SimpleXMLParseTrait {

  /**
   * Parse XML data to array.
   *
   * @param string $data
   *   XML string to be converted to array.
   */
  public function parseXml($data = '') {

    if ($data == '' || !$data) {
      return [];
    }
    // Read string using SimpleXMLElement.
    $xml_data = new \SimpleXMLElement($data);

    return $xml_data;
  }

}
