<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginBlockGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Extension\Manager;

class PluginBlockGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PermissionGenerator constructor.
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

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
            $this->extensionManager->getPluginPath($module, 'Block').'/'.$class_name.'.php',
            $parameters
        );
    }
}
