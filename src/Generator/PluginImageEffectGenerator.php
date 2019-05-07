<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginImageEffectGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginImageEffectGenerator extends Generator
{

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginImageEffectGenerator constructor.
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
            'module/src/Plugin/ImageEffect/imageeffect.php.twig',
            $this->extensionManager->getPluginPath($module, 'ImageEffect') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
