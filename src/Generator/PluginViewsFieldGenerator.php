<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginFieldFormatterGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginViewsFieldGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginViewsFieldGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Generator Plugin Field Formatter.
     *
     * @param string $module     Module name
     * @param string $class_name Plugin Class name
     * @param string $label      Plugin label
     * @param string $plugin_id  Plugin id
     * @param string $field_type Field type this formatter supports
     */
    public function generate($module, $class_machine_name, $class_name, $title, $description)
    {
        $parameters = [
            'module' => $module,
            'class_machine_name' => $class_machine_name,
            'class_name' => $class_name,
            'title' => $title,
            'description' => $description,
        ];

        $this->renderFile(
            'module/module.views.inc.twig',
            $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.views.inc',
            $parameters
        );

        $this->renderFile(
            'module/src/Plugin/Views/field/field.php.twig',
            $this->extensionManager->getPluginPath($module, 'views/field') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
