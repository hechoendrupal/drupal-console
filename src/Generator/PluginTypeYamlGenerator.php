<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginTypeYamlGenerator.
 */

namespace Drupal\Console\Generator;

class PluginTypeYamlGenerator extends Generator
{
    /**
     * Generator for Plugin type with Yaml discovery.
     *
     * @param $module
     * @param $plugin_class
     * @param $plugin_name
     * @param $plugin_file_name
     */
    public function generate($module, $plugin_class, $plugin_name, $plugin_file_name)
    {
        $parameters = [
            'module' => $module,
            'plugin_class' => $plugin_class,
            'plugin_name' => $plugin_name,
            'plugin_file_name' => $plugin_file_name,
            'file_exists' => file_exists($this->getSite()->getModulePath($module) . '/' . $module . '.services.yml'),
        ];

        $this->renderFile(
            'module/src/yaml-plugin-manager.php.twig',
            $this->getSite()->getSourcePath($module) . '/' . $plugin_class . 'Manager.php',
            $parameters
        );

        $this->renderFile(
            'module/src/yaml-plugin-manager-interface.php.twig',
            $this->getSite()->getSourcePath($module) . '/' . $plugin_class . 'ManagerInterface.php',
            $parameters
        );

        $this->renderFile(
            'module/plugin-yaml-services.yml.twig',
            $this->getSite()->getModulePath($module) . '/' . $module . '.services.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/plugin.yml.twig',
            $this->getSite()->getModulePath($module) . '/' . $module . '.' . $plugin_file_name . '.yml',
            $parameters
        );
    }
}
