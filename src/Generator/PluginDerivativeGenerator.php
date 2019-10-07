<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginDerivativeGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginDerivativeGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PermissionGenerator constructor.
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
        $class_name = $parameters['class'];
        $blockLabel = $parameters['block_label'];
        $blockDescription = $parameters['block_description'];
        $blockId = $parameters['block_id'];
        
        //block_derivative.php.twig
        $this->renderFile(
            'module/src/Plugin/Block/block_derivative.php.twig',
            $this->extensionManager->getPluginPath($module, 'Block') . '/' . $class_name . '.php',
            $parameters
        );

        //derivative_block_derivative.php.twig
        $this->renderFile(
            'module/src/Plugin/Derivative/derivative_block_derivative.php.twig',
            $this->extensionManager->getPluginPath($module, 'Derivative') . '/' . $class_name . '.php',
            $parameters
        );
        
    }
}
