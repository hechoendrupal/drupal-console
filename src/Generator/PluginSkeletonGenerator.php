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
     * Generator Post Update Name function.
     *
     * @param $module
     * @param $pluginId
     * @param $plugin
     * @param $className
     * @param $pluginMetaData
     * @param $services
     */
    public function generate($module, $pluginId, $plugin, $className, $pluginMetaData, $services)
    {
        $module_path =  $this->extensionManager->getModule($module)->getPath();

        $parameters = [
            'module' => $module,
            'plugin_id' => $pluginId,
            'plugin' => $plugin,
            'class_name' => $className,
            'services' => $services,
            'plugin_annotation' => array_pop(explode('\\', $pluginMetaData['pluginAnnotation'])),
            'plugin_interface' => array_pop(explode('\\', $pluginMetaData['pluginInterface']))
            ];

        $this->renderFile(
            'module/src/Plugin/skeleton.php.twig',
            $module_path .'/src/'. $pluginMetaData['subdir'] . '/' . $className .'.php',
            array_merge($parameters, $pluginMetaData)
        );
    }
}
