<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Generator\PluginBlockGenerator.
 */

namespace Drupal\AppConsole\Generator;

class PluginBlockGenerator extends Generator
{
    /**
     * Generator Plugin Block.
     *
     * @param $module
     * @param $class_name
     * @param $label
     * @param $plugin_id
     * @param $services
     */
    public function generate($module, $class_name, $label, $plugin_id, $services, $inputs)
    {
        $parameters = [
          'module' => $module,
          'class_name' => $class_name,
          'label' => $label,
          'plugin_id' => $plugin_id,
          'services' => $services,
          'inputs' => $inputs,
        ];

        $this->renderFile(
            'module/src/Plugin/Block/block.php.twig',
            $this->getPluginPath($module, 'Block').'/'.$class_name.'.php',
            $parameters
        );
    }
}
