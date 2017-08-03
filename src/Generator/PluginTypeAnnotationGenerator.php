<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginTypeAnnotationGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginTypeAnnotationGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginTypeAnnotationGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Generator for Plugin type with annotation discovery.
     *
     * @param $module
     * @param $class_name
     * @param $machine_name
     * @param $label
     */
    public function generate($module, $class_name, $machine_name, $label)
    {
        $parameters = [
            'module' => $module,
            'class_name' => $class_name,
            'machine_name' => $machine_name,
            'label' => $label,
            'file_exists' => file_exists($this->extensionManager->getModule($module)->getPath() .'/'.$module.'.services.yml'),
        ];

        $directory = $this->extensionManager->getModule($module)->getSourcePath() . '/Plugin/' . $class_name;

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        
        $this->renderFile(
            'module/src/Annotation/plugin-type.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath() . '/Annotation/' . $class_name . '.php',
            $parameters
        );

        $this->renderFile(
            'module/src/plugin-type-annotation-base.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath() .'/Plugin/' . $class_name . 'Base.php',
            $parameters
        );

        $this->renderFile(
            'module/src/plugin-type-annotation-interface.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath() .'/Plugin/' . $class_name . 'Interface.php',
            $parameters
        );

        $this->renderFile(
            'module/src/plugin-type-annotation-manager.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath() .'/Plugin/' . $class_name . 'Manager.php',
            $parameters
        );
        $this->renderFile(
            'module/plugin-annotation-services.yml.twig',
            $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.services.yml',
            $parameters,
            FILE_APPEND
        );
    }
}
