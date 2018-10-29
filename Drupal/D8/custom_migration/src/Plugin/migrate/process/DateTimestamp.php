<?php

namespace Drupal\custom_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Convert date into its respective timestamp.
 *
 * @MigrateProcessPlugin(
 *   id = "date_timestamp",
 * )
 */
class DateTimestamp extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    try {
      $value = explode(' ', $value);
      $date = $value[0];
      $dateTime = new DrupalDateTime($date, 'GMT');
      $timestamp = $dateTime->getTimestamp();
    }
    catch (\Exception $e) {
      throw new MigrateException('Invalid source date.');
    }
    return $timestamp;
  }

}
