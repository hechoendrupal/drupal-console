<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginBlockGenerator.
 */

namespace Drupal\Console\Generator;

class PluginRulesActionGenerator extends Generator
{
    /**
     * Generator Plugin RulesAction.
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $category
     * @param $context
     */
    public function generate($module, $class_name, $label, $plugin_id, $category, $context, $type)
    {
        $parameters = [
          'module' => $module,
          'class_name' => $class_name,
          'label' => $label,
          'plugin_id' => $plugin_id,
          'category' => $category,
          'context' => $context,
          'type' => $type,
        ];

        $this->renderFile(
            'module/src/Plugin/Action/rulesaction.php.twig',
            $this->getSite()->getPluginPath($module, 'Action').'/'.$class_name.'.php',
            $parameters
        );

        $this->renderFile(
            'module/system.action.action.yml.twig',
            $this->getSite()->getModulePath($module).'/config/install/system.action.'.$plugin_id.'.yml',
            $parameters
        );
    }
}
