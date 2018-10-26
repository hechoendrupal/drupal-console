<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginMailGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginMailGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;


    /**
     * PluginMailGenerator constructor.
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
            'module/src/Plugin/Mail/mail.php.twig',
            $this->extensionManager->getPluginPath($module, 'Mail') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
