<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginSkeletonGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginSkeletonGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginSkeletonGenerator constructor.
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
        $className = $parameters['class_name'];
        $module = $parameters['module'];
        $pluginMetaData = $parameters['plugin_metadata'];

        $parameters['plugin_annotation'] = array_pop(explode('\\', $pluginMetaData['pluginAnnotation']));
        $parameters['plugin_interface'] = array_pop(explode('\\', $pluginMetaData['pluginInterface']));
        $parameters['namespace'] =  str_replace('/', '\\', $pluginMetaData['subdir']);

        $this->renderFile(
            'module/src/Plugin/skeleton.php.twig',
            $this->extensionManager->getModule($module)->getPath() . '/src/' . $pluginMetaData['subdir'] . '/' . $className . '.php',
            array_merge($parameters, $pluginMetaData)
        );
    }
}
