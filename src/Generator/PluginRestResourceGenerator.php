<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginRestResourceGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginRestResourceGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginRestResourceGenerator constructor.
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
        $module = $parameters['module_name'];
        $class_name = $parameters['class_name'];

        $this->renderFile(
            'module/src/Plugin/Rest/Resource/rest.php.twig',
            $this->extensionManager->getPluginPath($module, 'rest') . '/resource/' . $class_name . '.php',
            $parameters
        );
    }
}
