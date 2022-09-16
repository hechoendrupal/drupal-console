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
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $module = $parameters['module'];
        $class_name = $parameters['class_name'];

        $this->renderFile(
            'module/src/Plugin/migrate/process/process.php.twig',
            $this->extensionManager->getPluginPath($module, 'migrate') . '/process/' . $class_name . '.php',
            $parameters
        );
    }
}
