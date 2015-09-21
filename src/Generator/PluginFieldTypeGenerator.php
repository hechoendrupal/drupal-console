<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginFieldTypeGenerator.
 */

namespace Drupal\Console\Generator;

class PluginFieldTypeGenerator extends Generator
{
    /**
     * Generator Plugin Field Type.
     *
     * @param string $module            Module name
     * @param string $class_name        Plugin Class name
     * @param string $label             Plugin label
     * @param string $plugin_id         Plugin id
     * @param string $description       Plugin description
     * @param string $default_widget    Default widget this field type used supports
     * @param string $default_formatter Default formatter this field type used supports
     */
    public function generate($module, $class_name, $label, $plugin_id, $description, $default_widget, $default_formatter)
    {
        $parameters = [
            'module' => $module,
            'class_name' => $class_name,
            'label' => $label,
            'plugin_id' => $plugin_id,
            'description' => $description,
            'default_widget' => $default_widget,
            'default_formatter' => $default_formatter,
        ];

        $this->renderFile(
            'module/src/Plugin/Field/FieldType/fieldtype.php.twig',
            $this->getSite()->getPluginPath($module, 'Field/FieldType') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
