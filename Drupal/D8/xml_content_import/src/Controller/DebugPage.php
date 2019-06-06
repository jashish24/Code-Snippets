<?php

namespace Drupal\xml_content_import\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\xml_content_import\Services\ImporterService;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Controller Class to get debug page.
 */
class DebugPage extends ControllerBase {

  protected $xmlImportService;

  /**
   * Create function.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container for DependencyInjection.
   */
  public static function create(ContainerInterface $container) {
    $xmlimport_service = $container->get('xml_content_import.importer');

    return new static($xmlimport_service);
  }

  /**
   * Constructor.
   *
   * @param Drupal\xml_content_import\Services\ImporterService $xmlimport_service
   *   Importer service injection.
   */
  public function __construct(ImporterService $xmlimport_service) {
    $this->xmlImportService = $xmlimport_service;
  }

  /**
   * Get page content.
   */
  public function getContent() {
    $this->xmlImportService->setEntityData();
    $chunks = $this->xmlImportService->processDataChunks(2);

    $batch = [
      'title' => $this->t('Importing Content...'),
      'operations' => [],
      'init_message'     => $this->t('Starting Import'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message'    => $this->t('An error occurred during processing'),
      'finished' => '\Drupal\xml_content_import\Controller\DebugPage::finishedImport',
    ];

    foreach ($chunks as $chunk) {
      $batch['operations'][] = ['\Drupal\xml_content_import\Controller\DebugPage::runContentImport', [$chunk]];
    }

    batch_set($batch);

    return batch_process('admin/content');
  }

  /**
   * Execute each operation.
   *
   * @param array $chunk
   *   Array of entities to be processed.
   */
  public static function runContentImport(array $chunk) {
    $xmlImportService = \Drupal::service('xml_content_import.importer');
    $xmlImportService->runImport($chunk);
  }

  /**
   * Execute batch process finished.
   *
   * @param bool $success
   *   Array of successful operations.
   * @param array $results
   *   Array of results.
   * @param array $operations
   *   Array of operations.
   */
  public static function finishedImport($success, array $results, array $operations) {
    drupal_set_message(t('Import completed!'));
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowed();
  }

}
