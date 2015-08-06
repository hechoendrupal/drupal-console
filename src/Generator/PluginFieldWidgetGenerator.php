<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Generator\PluginFieldWidgetGenerator.
 */

namespace Drupal\AppConsole\Generator;

class PluginFieldWidgetGenerator extends Generator
{
    /**
     * Generator Plugin Field Formatter.
     *
     * @param string $module     Module name
     * @param string $class_name Plugin Class name
     * @param string $label      Plugin label
     * @param string $plugin_id  Plugin id
     * @param string $field_type Field type this widget supports
     */
    public function generate($module, $class_name, $label, $plugin_id, $field_type)
    {
        $parameters = [
            'module' => $module,
            'class_name' => $class_name,
            'label' => $label,
            'plugin_id' => $plugin_id,
            'field_type' => $field_type,
        ];

        $this->renderFile(
            'module/src/Plugin/Field/FieldWidget/fieldwidget.php.twig',
            $this->getPluginPath($module, 'FieldWidget') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
