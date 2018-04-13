<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginFieldWidgetGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginFieldWidgetGenerator extends Generator
{

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginFieldWidgetGenerator constructor.
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
            'module/src/Plugin/Field/FieldWidget/fieldwidget.php.twig',
            $this->extensionManager->getPluginPath($module, 'Field/FieldWidget') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
