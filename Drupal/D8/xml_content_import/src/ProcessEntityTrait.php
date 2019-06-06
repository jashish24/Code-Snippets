<?php

namespace Drupal\xml_content_import;

/**
 * Trait ProcessEntityTrait.
 *
 * @package Drupal\xml_content_import
 */
trait ProcessEntityTrait {
  use PrepareFieldsTrait;

  /**
   * Prepare and save entity.
   *
   * @param array $entity_type_entity
   *   Entity data with translations.
   * @param array $import_summary
   *   Import summary with messages.
   * @param array $entity_names
   *   List of entity names available.
   * @param string $entity_type
   *   Type of entity to be imported.
   * @param string $bundle
   *   Bundle to which entity should be imported.
   */
  public function letTheEntitySlipIn(array $entity_type_entity, array &$import_summary, array $entity_names, $entity_type = 'default', $bundle = '') {
    $entity_processed = FALSE;

    switch ($entity_type) {
      case 'node':
        $entity_processed = TRUE;
        $this->bakeEntity($entity_type, $bundle, $entity_type_entity, $import_summary, $entity_names);
        $import_summary['count']++;
        break;

      default:
        $entity_data['entity_type'] = $entity_type;
        $entity_data['bundle'] = $bundle;
        $entity_data['single_entity'] = $entity_type_entity;
        $entity_data['entity_processed'] = $entity_processed;
        \Drupal::moduleHandler()->alter('add_entity_type_importer', $entity_data);
        $entity_processed = $entity_data['entity_processed'];
        break;
    }

    if (!$entity_processed) {
      $import_summary['messages'][] = t('Import Handler not found for entity type :type at index :count', [':type' => $entity_type, ':count' => $import_summary['count']]);
    }
  }

  /**
   * Bake entity by separating out original entity from it's translations.
   *
   * @param string $entity_type
   *   Type of entity to be imported.
   * @param string $bundle
   *   Bundle to which entity should be imported.
   * @param array $entity_type_entity
   *   Entity data with translations.
   * @param array $import_summary
   *   Import summary with messages.
   * @param array $entity_names
   *   List of entity names available.
   */
  public function bakeEntity($entity_type, $bundle, array $entity_type_entity, array &$import_summary, array $entity_names) {
    $default_language = $this->languageManager->getDefaultLanguage()->getId();
    $entity_attributes = $entity_type_entity['@attributes'];
    $url_alias = isset($entity_attributes['url']) ? $entity_attributes['url'] : '';
    $original_entity = [];
    $translations = [];

    if ($entity_type_entity['node-language']) {
      foreach ($entity_type_entity['node-language'] as $key => $lang_entity) {
        if (isset($lang_entity['@attributes']['lang'])) {
          $language_code = $this->getLanguageCode($lang_entity['@attributes']['lang']);
          if ($language_code == $default_language) {
            $original_entity = $lang_entity;
            unset($entity_type_entity['node-language'][$key]);
          }
        }
      }

      $translations = $entity_type_entity['node-language'];
    }
    else {
      $original_entity = $entity_type_entity;
      $translations = [];
    }

    $saved_entity = $this->saveEntity($entity_type, $bundle, $original_entity, $translations, $import_summary, $entity_names, $url_alias);

    $import_summary['messages'][] = t('Entity created with :id', [':id' => $saved_entity['target_id']]);
  }

  /**
   * Save entity with translations.
   *
   * @param string $entity_type
   *   Type of entity to be imported.
   * @param string $bundle
   *   Bundle to which entity should be imported.
   * @param array $original_entity
   *   Entity data.
   * @param array $translations
   *   Translations of entity.
   * @param array $import_summary
   *   Import summary with messages.
   * @param array $entity_names
   *   List of entity names available.
   * @param string $url_alias
   *   Path alias of the entity.
   */
  public function saveEntity($entity_type, $bundle, array $original_entity, array $translations, array &$import_summary, array $entity_names, $url_alias = '') {
    $default_language = $this->languageManager->getDefaultLanguage()->getId();
    $attributes = $original_entity['@attributes'];
    $entity_manager = $this->entityTypeManager->getStorage($entity_type);
    $language_code = isset($attributes['lang']) ? $this->getLanguageCode($attributes['lang'], $default_language) : $default_language;

    $entity_values = $this->getFields($entity_type, $bundle, $language_code, $original_entity, $import_summary, $entity_names);

    $created_entity = $entity_manager->create($entity_values);
    $created_entity->save();

    if ($url_alias != '') {
      $internal_path = '/' . $entity_type . '/' . $created_entity->id();
      $this->pathAlias->save($internal_path, '/' . $url_alias, $language_code);
    }

    if (count($translations) > 0) {
      foreach ($translations as $key => $entity_translation) {
        $attributes = isset($entity_translation['@attributes']) ? $entity_translation['@attributes'] : $key;
        $language_code = isset($attributes['lang']) ? $this->getLanguageCode($attributes['lang'], $default_language) : $default_language;

        $entity_translation_values = $this->getFields($entity_type, $bundle, $language_code, $entity_translation, $import_summary, $entity_names);

        if ($language_code != $default_language) {
          $created_entity->addTranslation($language_code, $entity_translation_values)->save();
        }
      }
    }

    $saved_entity = [
      'target_id' => $created_entity->id(),
      'target_revision_id' => $created_entity->getRevisionId(),
    ];

    return $saved_entity;
  }

  /**
   * Convert human language code to Drupal language code.
   *
   * @param string $language_code
   *   Language to be converted.
   * @param string $default_language
   *   Default language code of application.
   */
  public function getLanguageCode($language_code = 'en', $default_language = 'en') {
    $language_code_mapping = [
      'en' => 'en',
      'tc' => 'zh-hant',
      'sc' => 'zh-hans',
    ];

    return isset($language_code_mapping[$language_code]) ? $language_code_mapping[$language_code] : $default_language;
  }

}
