<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Generator\PluginTypeAnnotationGenerator.
 */

namespace Drupal\AppConsole\Generator;

class PluginTypeAnnotationGenerator extends Generator
{
    /**
     * Generator for Plugin type with annotation discovery.
     *
     * @param  $module
     * @param  $class_name
     * @param  $machine_name
     * @param  $label
     */
    public function generate($module, $class_name, $machine_name, $label)
    {
        $parameters = [
            'module' => $module,
            'class_name' => $class_name,
            'machine_name' => $machine_name,
            'label' => $label,
        ];

        $this->renderFile(
            'module/src/Annotation/plugin-type.php.twig',
            $this->getSourcePath($module) . '/Annotation/' . $class_name . '.php',
            $parameters
        );

        $this->renderFile(
            'module/src/plugin-type-annotation-base.php.twig',
            $this->getSourcePath($module).'/Plugin/' . $class_name . 'Base.php',
            $parameters
        );

        $this->renderFile(
            'module/src/plugin-type-annotation-interface.php.twig',
            $this->getSourcePath($module).'/Plugin/' . $class_name . 'Interface.php',
            $parameters
        );

        $this->renderFile(
            'module/src/plugin-type-annotation-manager.php.twig',
            $this->getSourcePath($module).'/Plugin/' . $class_name . 'Manager.php',
            $parameters
        );

        $directory = $this->getSourcePath($module).'/Plugin/' . $class_name;
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }
}
