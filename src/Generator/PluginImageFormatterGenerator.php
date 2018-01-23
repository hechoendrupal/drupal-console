<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginFieldFormatterGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginImageFormatterGenerator extends Generator
{

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginImageFormatterGenerator constructor.
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
            'module/src/Plugin/Field/FieldFormatter/imageformatter.php.twig',
            $this->extensionManager->getPluginPath($module, 'Field/FieldFormatter') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
