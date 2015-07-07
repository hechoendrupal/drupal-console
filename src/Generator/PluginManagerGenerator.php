<?php

/**
 * @file
 * Contains Drupal\AppConsole\Generator\PluginManagerGenerator.
 */
namespace Drupal\AppConsole\Generator;

use Drupal\AppConsole\Generator\Generator;

class PluginManagerGenerator extends Generator
{

    public function generate($module, $plugin_manager, $annotation, $annotation_property)
    {
        $parametters = [
            'module' => $module,
            'service_name' => $plugin_manager,
            'plugin_manager' => $plugin_manager,
            'annotation' => $annotation,
            'annotation_property' => $annotation_property,
            'file_exists' => file_exists($this->getModulePath($module) . '/' . $module . '.services.yml'),
        ];

        $this->renderFile(
            'module/plugin-services.yml.twig',
            $this->getModulePath($module) . '/' . $module . '.services.yml',
            $parametters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/Plugin/plugin-interface.php.twig',
            $this->getPluginPath($module, null) . $plugin_manager . 'Interface.php',
            $parametters
        );

        $this->renderFile(
            'module/src/Plugin/plugin-manager.php.twig',
            $this->getPluginPath($module, null) . $plugin_manager . '.php',
            $parametters
        );

        $this->renderFile(
            'module/src/Annotation/plugin-annotation.php.twig',
            $this->getAnnotationPath($module) . '/' . $annotation . '.php',
            $parametters
        );
    }
}