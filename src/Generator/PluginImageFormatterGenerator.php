<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginFieldFormatterGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginImageFormatterGenerator extends Generator
{
    /**
     * PluginImageFormatterGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }


    /**
     * Generator Plugin Image Formatter.
     *
     * @param string $module     Module name
     * @param string $class_name Plugin Class name
     * @param string $label      Plugin label
     * @param string $plugin_id  Plugin id
     */
    public function generate($module, $class_name, $label, $plugin_id)
    {
        $parameters = [
            'module' => $module,
            'class_name' => $class_name,
            'label' => $label,
            'plugin_id' => $plugin_id,
        ];

        $this->renderFile(
            'module/src/Plugin/Field/FieldFormatter/imageformatter.php.twig',
            $this->extensionManager->getPluginPath($module, 'Field/FieldFormatter') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
