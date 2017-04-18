<?php

namespace Drupal\config_filter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ConfigFilterServiceProvider.
 *
 * @package Drupal\config_filter
 */
class ConfigFilterServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('config.storage.staging');
    $definition->setClass('Drupal\config_filter\Config\FilteredStorage');
    $definition->setFactory([new Reference('plugin.manager.config_filter'), 'getFilteredSyncStorage']);
  }

}
