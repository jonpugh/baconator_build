<?php

namespace Drupal\config_filter\Plugin;

use Drupal\config_filter\Config\FilteredStorage;
use Drupal\config_filter\ConfigFilterManagerInterface;
use Drupal\Core\Config\FileStorageFactory;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Config filter plugin plugin manager.
 */
class ConfigFilterPluginManager extends DefaultPluginManager implements ConfigFilterManagerInterface {

  /**
   * Constructor for ConfigFilterPluginManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ConfigFilter', $namespaces, $module_handler, 'Drupal\config_filter\Plugin\ConfigFilterInterface', 'Drupal\config_filter\Annotation\ConfigFilter');

    $this->alterInfo('config_filter_info');
    $this->setCacheBackend($cache_backend, 'config_filter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFilteredStorage(StorageInterface $storage, $storage_name, array $excluded = []) {
    $filters = $this->getFilters($storage_name);
    if (!empty($excluded)) {
      $filters = array_diff_key($filters, array_combine($excluded, $excluded));
    }
    return new FilteredStorage($storage, $filters);
  }

  /**
   * {@inheritdoc}
   */
  public function getFilteredSyncStorage() {
    return $this->getFilteredStorage(FileStorageFactory::getSync(), 'config.storage.sync');
  }

  /**
   * Get the applicable filters for a given storage name.
   *
   * @param string $storage_name
   *   The storage name.
   *
   * @return \Drupal\config_filter\Plugin\ConfigFilterInterface[]
   *   The configured plugin instances.
   */
  protected function getFilters($storage_name) {
    $definitions = $this->getDefinitions();

    // Sort the definitions by weight.
    uasort($definitions, function ($a, $b) {
      return strcmp($a['weight'], $b['weight']);
    });

    $filters = [];
    foreach ($definitions as $id => $definition) {
      if (empty($definition['storages'])) {
        // The sync storage is the default.
        $definition['storages'] = ['config.storage.sync'];
      }

      if ($definition['status'] && in_array($storage_name, $definition['storages'])) {
        $filters[$id] = $this->createInstance($id, $definition);
      }
    }

    return $filters;
  }

}
