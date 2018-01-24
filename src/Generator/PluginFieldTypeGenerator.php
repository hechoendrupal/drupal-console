<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginFieldTypeGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginFieldTypeGenerator extends Generator
{

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginFieldTypeGenerator constructor.
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
            'module/src/Plugin/Field/FieldType/fieldtype.php.twig',
            $this->extensionManager->getPluginPath($module, 'Field/FieldType') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
