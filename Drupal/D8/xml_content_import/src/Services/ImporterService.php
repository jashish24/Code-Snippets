<?php

namespace Drupal\xml_content_import\Services;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Path\AliasStorage;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\xml_content_import\SimpleXMLParseTrait;
use Drupal\xml_content_import\ProcessEntityTrait;

/**
 * Main Importer Class.
 */
class ImporterService {
  use StringTranslationTrait;
  use SimpleXMLParseTrait;
  use ProcessEntityTrait;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Entity Manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Drupal\core\Language\LanguageManagerInterface definition.
   *
   * @var \Drupal\core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Configurations available.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Path alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorage
   */
  protected $pathAlias;

  /**
   * Basic configuration.
   *
   * @var array
   */
  protected $xmlImportConfig;

  /**
   * Path to data file.
   *
   * @var string
   */
  protected $dataFilePath;

  /**
   * Path to data directory.
   *
   * @var string
   */
  protected $dataDirectory;

  /**
   * Path to media directory.
   *
   * @var string
   */
  protected $mediaDirectory;

  /**
   * Path to media directory.
   *
   * @var array
   */
  protected $entityData = [];

  /**
   * Constructor.
   *
   * @param Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity Type Manager object.
   * @param Drupal\Core\Entity\EntityManager $entity_manager
   *   Entity Manager for fields.
   * @param Drupal\core\Language\LanguageManagerInterface $language_manager
   *   Language manager to handle multi-lingual.
   * @param Drupal\Core\Session\AccountProxy $current_user
   *   Account to get current user details.
   * @param Drupal\Core\Config\ConfigFactory $config_factory
   *   Configuration factory to get import details.
   * @param Drupal\Core\Path\AliasStorage $path_alias
   *   Service to store path alias.
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityManager $entity_manager, LanguageManagerInterface $language_manager, AccountProxy $current_user, ConfigFactory $config_factory, AliasStorage $path_alias) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->pathAlias = $path_alias;
    $this->xmlImportConfig = $this->configFactory->get('xml_content_import.settings');

    // Generate data file path.
    $this->dataFilePath = file_default_scheme() . '://' . $this->xmlImportConfig->get('working_directory') . '/' . $this->xmlImportConfig->get('data_directory') . '/' . $this->xmlImportConfig->get('data_file');

    // Generate data directory path.
    $this->dataDirectory = file_default_scheme() . '://' . $this->xmlImportConfig->get('working_directory') . '/' . $this->xmlImportConfig->get('data_directory');

    // Generate media directory path.
    $this->mediaDirectory = file_default_scheme() . '://' . $this->xmlImportConfig->get('working_directory') . '/' . $this->xmlImportConfig->get('media_directory');
  }

  /**
   * Unused XMLElement to array function.
   *
   * @param array $xmlObject
   *   Object to be converted to array.
   * @param array $out
   *   Output array.
   */
  public function xml2array(array $xmlObject = [], array $out = []) {
    foreach ((array) $xmlObject as $index => $node) {
      $out[$index] = (is_object($node)) ? $this->xml2array($node) : $node;
    }

    return $out;
  }

  /**
   * Set and process data from file.
   */
  public function setEntityData() {
    if (file_exists($this->dataFilePath)) {
      $this->nodes_data = file_get_contents($this->dataFilePath);

      $xml_data = $this->parseXml($this->nodes_data);
      $this->entityData = json_decode(json_encode($xml_data), TRUE);
    }
  }

  /**
   * Get entity data.
   *
   * @return array
   *   Return processed data.
   */
  public function getEntityData() {
    return $this->entityData;
  }

  /**
   * Start import process.
   */
  public function runImport($entities = []) {
    if (!empty($entities)) {
      $import_summary = [
        'count' => 0,
        'messages' => [],
      ];

      $entity_definitions = $this->entityManager->getDefinitions();
      $entity_names = array_keys($entity_definitions);

      foreach ($entities as $entity_type => $entity_type_entities) {
        if (!$entity_definitions[$entity_type]) {
          $import_summary['messages'][] = $this->t('Entity definition of :type is not available at index :index', [':type' => $entity_type, ':index' => $import_summary['count']]);
          $import_summary['count']++;
        }
        else {
          foreach ($entity_type_entities as $entity_type_entity) {
            $bundle = $entity_type_entity['@attributes']['type'];
            $this->letTheEntitySlipIn($entity_type_entity, $import_summary, $entity_names, $entity_type, $bundle);
          }
        }
      }
    }
    else {
      drupal_set_message($this->t('Data file not found'));
    }

    drupal_set_message($this->t('Nodes import successful. :count nodes imported with translations', [
      ':count' => $import_summary['count'],
    ]));

    $messages = $import_summary['messages'];
    foreach ($messages as $message) {
      drupal_set_message($message);
    }
  }

  /**
   * Break data into chunks.
   *
   * @param int $limit
   *   Size of each chunk.
   */
  public function processDataChunks($limit = 5) {
    $chunks = [];

    foreach ($this->entityData as $entity_type => $entity_type_entities) {
      $splits = array_chunk($entity_type_entities, $limit);
      foreach ($splits as $single_chunk) {
        $chunks[][$entity_type] = $single_chunk;
      }
    }

    return $chunks;
  }

}
