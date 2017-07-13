<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginMailGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginMailGenerator extends Generator
{
    /**
     * PluginMailGenerator constructor.
     *
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
    public function generate($module, $class_name, $label, $plugin_id, $services)
    {
        $parameters = [
          'module' => $module,
          'class_name' => $class_name,
          'label' => $label,
          'plugin_id' => $plugin_id,
          'services' => $services,
        ];

        $this->renderFile(
            'module/src/Plugin/Mail/mail.php.twig',
            $this->extensionManager->getPluginPath($module, 'Mail') .'/'.$class_name.'.php',
            $parameters
        );
    }
}
