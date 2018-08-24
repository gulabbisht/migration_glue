<?php

namespace Drupal\migration_glue;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;

class MigrationGlueManager {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * MigrationGlueManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Register a migration to the system.
   *
   * @param array $yml_data
   *   YML content.
   */
  public function registerMigration(array $yml_data = []) {
    if (!empty($yml_data)) {
      $migration_name = 'migrate_plus.migration.' . $yml_data['id'];
      $this->configFactory->getEditable($migration_name)->setData($yml_data)->save();
      // Invalidate the cache so that the 'discovery_migration' cache bin re-built.
      Cache::invalidateTags(['migration_plugins']);
    }
  }

  /**
   * Gets all files of given type from the given directory recursively.
   *
   * @param string $directory_path
   *   Directory path.
   * @param string $file_type
   *   File type (JSON/XML).
   *
   * @return array
   *   All files of given type in the given directory.
   */
  public  function getFilesInDirectory(string $directory_path, string $file_type = 'json') {
    return glob($directory_path . '/*.' . $file_type);
  }

}
