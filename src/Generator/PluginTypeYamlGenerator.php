<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Generator\PluginTypeYamlGenerator.
 */

namespace Drupal\AppConsole\Generator;

class PluginTypeYamlGenerator extends Generator
{
    /**
     * Generator for Plugin type with Yaml discovery.
     *
     * @param  $module
     * @param  $plugin_class
     * @param  $plugin_name
     * @param  $plugin_file_name
     */
    public function generate($module, $plugin_class, $plugin_name, $plugin_file_name)
    {
        $parameters = [
            'module' => $module,
            'plugin_class' => $plugin_class,
            'plugin_name' => $plugin_name,
            'plugin_file_name' => $plugin_file_name,
        ];

        $this->renderFile(
            'module/src/yaml-plugin-manager.php.twig',
            $this->getSourcePath($module) . '/' . $plugin_class . 'Manager.php',
            $parameters
        );

        $this->renderFile(
            'module/src/yaml-plugin-manager-interface.php.twig',
            $this->getSourcePath($module) . '/' . $plugin_class . 'ManagerInterface.php',
            $parameters
        );
    }
}
