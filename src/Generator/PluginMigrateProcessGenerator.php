<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginMigrateProcessGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginMigrateProcessGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginMigrateProcessGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Generate Migrate Source plugin code.
     *
     * @param $module
     * @param $class_name
     * @param $plugin_id
     */
    public function generate($module, $class_name, $plugin_id)
    {
        $parameters = [
          'module' => $module,
          'class_name' => $class_name,
          'plugin_id' => $plugin_id,
        ];

        $this->renderFile(
            'module/src/Plugin/migrate/process/process.php.twig',
            $this->extensionManager->getPluginPath($module, 'migrate').'/process/'.$class_name.'.php',
            $parameters
        );
    }
}
