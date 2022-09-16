<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginTypeYamlGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginTypeYamlGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginTypeYamlGenerator constructor.
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
        $plugin_file_name = $parameters['plugin_file_name'];

        $moduleInstance = $this->extensionManager->getModule($module);
        $modulePath = $moduleInstance->getPath() . '/' . $module;
        $moduleSourcePlugin = $moduleInstance->getSourcePath() . '/' . $class_name;
        $moduleServiceYaml = $modulePath . '.services.yml';
        $parameters['file_exists'] = file_exists($moduleServiceYaml);

        $this->renderFile(
            'module/src/yaml-plugin-manager.php.twig',
            $moduleSourcePlugin . 'Manager.php',
            $parameters
        );

        $this->renderFile(
            'module/src/yaml-plugin-manager-interface.php.twig',
            $moduleSourcePlugin . 'ManagerInterface.php',
            $parameters
        );

        $this->renderFile(
            'module/plugin-yaml-services.yml.twig',
            $moduleServiceYaml,
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/plugin.yml.twig',
            $modulePath . '.' . $plugin_file_name . '.yml',
            $parameters
        );
    }
}
