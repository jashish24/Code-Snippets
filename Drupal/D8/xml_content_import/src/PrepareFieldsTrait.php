<?php

namespace Drupal\xml_content_import;

/**
 * Trait PrepareFieldsTrait.
 *
 * @package Drupal\xml_content_import
 */
trait PrepareFieldsTrait {
  use PrepareSpecialFieldTrait;

  /**
   * Get fields for an entity.
   *
   * @param string $entity_type
   *   Type of entity to be imported.
   * @param string $bundle
   *   Bundle to which entity should be imported.
   * @param string $language_code
   *   Language code of the entity.
   * @param array $original_entity
   *   Entity data.
   * @param array $import_summary
   *   Import summary with messages.
   * @param array $entity_names
   *   List of entity names available.
   */
  public function getFields($entity_type, $bundle, $language_code, array $original_entity, array &$import_summary, array $entity_names = []) {
    $allowed_fields = $this->entityManager->getFieldDefinitions($entity_type, $bundle);
    $fields = isset($original_entity['field']) ? $original_entity['field'] : [];

    $entity_values = [
      'title' => isset($original_entity['title']) ? trim($original_entity['title']) : '',
      'type' => $bundle,
      'langcode' => $language_code,
      'uid' => $this->currentUser->id(),
      'status' => 1,
      'moderation_state' => 'published',
    ];

    if (isset($fields['@attributes'])) {
      $temp = $fields;
      $fields = [];
      $fields[] = $temp;
    }

    foreach ($fields as $key => $field) {
      $field_name = '';
      if (isset($field['@attributes'])) {
        $attributes = $field['@attributes'];
        unset($field['@attributes']);
        $field_name = $attributes['name'];
      }

      if (isset($allowed_fields[$field_name])) {
        $field_definition = $allowed_fields[$field_name];
        $field_type = $field_definition->getType();
        $field_values = [];

        foreach ($field as $key => $value) {
          if (trim($key) == 'value') {
            if (is_array($value)) {
              foreach ($value as $key => $single_value) {
                // @TODO - Add more such(image, file) fields
                if ($field_type == 'image' || $field_type == 'file') {
                  $files_path = explode(',', $single_value);

                  if (count($files_path) > 1) {
                    foreach ($files_path as $file_path) {
                      $single_value_processed = explode(' ', $file_path);
                      $field_values[] = $this->handleFile($field_type, $single_value_processed[0]);
                    }
                  }
                  else {
                    $field_values[] = $this->handleFile($field_type, $single_value);
                  }
                }
                else {
                  $field_values[] = $single_value;
                }
              }
            }
            else {
              if ($field_type == 'image' || $field_type == 'file') {
                $field_values[] = $this->handleFile($field_type, $value);
              }
              else {
                $field_values[] = $value;
              }
            }
          }
          elseif (in_array($key, $entity_names) && !isset($value['field']) && isset($value[0]['field'])) {
            foreach ($value as $single_value) {
              $attributes = $single_value['@attributes'];
              $field_values[] = $this->saveEntity($key, $attributes['type'], $single_value, [], $import_summary, $entity_names);
            }
          }
          elseif (in_array($key, $entity_names)) {
            $attributes = $value['@attributes'];

            $field_values[] = $this->saveEntity($key, $attributes['type'], $value, [], $import_summary, $entity_names);
          }
          elseif (!in_array($key, $entity_names)) {
            $import_summary['messages'][] = t(':field_name does not allow :type as value at index :index', [
              ':field_name' => $field_name,
              ':type' => $key,
              ':index' => $import_summary['count'],
            ]);
          }
          // @todo Add entity reference, entity reference revisions, file, media etc field capabilities
        }

        $entity_values[$field_name] = $field_values;
      }
      else {
        $import_summary['messages'][] = t(':type Entity does not contain field with name :field_name at index :index', [
          ':type' => $entity_type,
          ':field_name' => $field_name,
          ':index' => $import_summary['count'],
        ]);
      }
    }

    return $entity_values;
  }

}
