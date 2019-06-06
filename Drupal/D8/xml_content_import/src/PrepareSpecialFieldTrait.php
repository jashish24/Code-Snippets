<?php

namespace Drupal\xml_content_import;

/**
 * Trait PrepareSpecialFieldTrait.
 *
 * @package Drupal\xml_content_import
 */
trait PrepareSpecialFieldTrait {

  /**
   * Attach image/file to a field.
   *
   * @param string $type
   *   Type of file field.
   * @param string $file_path
   *   Url to file to be attached.
   */
  public function handleFile($type = 'image', $file_path = '') {
    $file_target_id = [
      'target_id' => 0,
    ];

    if (filter_var($file_path, FILTER_VALIDATE_URL)) {
      $file = system_retrieve_file($file_path, $this->mediaDirectory, TRUE, FILE_EXISTS_REPLACE);
      if ($file) {
        $filename = $file->getFilename();
        $file_title = ucwords(str_replace('-', ' ', explode('.', $filename)[0]));
        $file_title = str_replace('_', ' ', $file_title);

        if ($fid = $file->id()) {
          $file_target_id['target_id'] = $fid;
          $file_target_id['alt'] = $file_title;
          $file_target_id['title'] = $file_title;
        }
      }
    }

    return $file_target_id;
  }

}
