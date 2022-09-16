<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginMigrateSourceGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginMigrateSourceGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginMigrateSourceGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

  /**
   * {@inheritdoc}
   */
  public function generate(array $parameters)
  {
      $module = $parameters['module'];
      $class_name = $parameters['class_name'];

      $this->renderFile(
          'module/src/Plugin/migrate/source/source.php.twig',
          $this->extensionManager->getPluginPath($module, 'migrate') . '/source/' . $class_name . '.php',
          $parameters
      );
  }
}
