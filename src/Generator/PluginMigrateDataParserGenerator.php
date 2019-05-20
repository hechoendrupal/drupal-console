<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginMigrateDataParserGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginMigrateDataParserGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginMigrateDataParserGenerator constructor.
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
            'module/src/Plugin/migrate_plus/data_parser/data_parser.php.twig',
            $this->extensionManager->getPluginPath($module, 'migrate_plus') . '/data_parser/' . $class_name . '.php',
            $parameters
        );
    }
}
