<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginFieldFormatterGenerator.
 */

namespace Drupal\Console\Generator;

class PluginConditionGenerator extends Generator
{
    /**
     * Generator Plugin Field Formatter.
     *
     * @param string $module                      Module name
     * @param string $class_name                  Plugin condition Class name
     * @param string $label                       Plugin condition label
     * @param string $plugin_id                   Plugin condition id
     * @param string $context_definition_id       Plugin condition context definition id
     * @param string $context_definition_label    Plugin condition context definition label
     * @param bool   $context_definition_required Plugin condition context definition required
     */
    public function generate($module, $class_name, $label, $plugin_id, $context_definition_id, $context_definition_label, $context_definition_required)
    {
        $parameters = [
            'module' => $module,
            'class_name' => $class_name,
            'label' => $label,
            'plugin_id' => $plugin_id,
            'context_definition_id' => $context_definition_id,
            'context_definition_label' => $context_definition_label,
            'context_definition_required' => $context_definition_required,
            'context_id' => str_replace('entity:', '', $context_definition_id)
        ];

        $this->renderFile(
            'module/src/Plugin/Condition/condition.php.twig',
            $this->getSite()->getPluginPath($module, 'Condition') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
