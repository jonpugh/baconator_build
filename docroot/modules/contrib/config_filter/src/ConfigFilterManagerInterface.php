<?php

namespace Drupal\config_filter;

use Drupal\Core\Config\StorageInterface;

/**
 * Interface ConfigFilterManagerInterface.
 *
 * @package Drupal\config_filter
 */
interface ConfigFilterManagerInterface {

  /**
   * Get a decorated storage with filters applied.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to decorate.
   * @param string $storage_name
   *   The name of the storage, so the correct filters can be applied.
   * @param string[] $excluded
   *   The ids of filters to exclude.
   *
   * @return \Drupal\config_filter\Config\FilteredStorageInterface
   *   The decorated storage with the filters applied.
   */
  public function getFilteredStorage(StorageInterface $storage, $storage_name, array $excluded = []);

  /**
   * Returns a ConfigStorage object working with the sync config directory.
   *
   * @return \Drupal\config_filter\Config\FilteredStorageInterface
   *   The filtered sync storage.
   */
  public function getFilteredSyncStorage();

}
