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
     * Generate Migrate Source plugin code.
     *
     * @param $module
     * @param $class_name
     * @param $plugin_id
     * @param $table
     * @param $alias
     * @param $group_by
     * @param fields
     */
    public function generate($module, $class_name, $plugin_id, $table, $alias, $group_by, $fields)
    {
        $parameters = [
          'module' => $module,
          'class_name' => $class_name,
          'plugin_id' => $plugin_id,
          'table' => $table,
          'alias' => $alias,
          'group_by' => $group_by,
          'fields' => $fields,
        ];

        $this->renderFile(
            'module/src/Plugin/migrate/source/source.php.twig',
            $this->extensionManager->getPluginPath($module, 'migrate').'/source/'.$class_name.'.php',
            $parameters
        );
    }
}
