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
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $module = $parameters['module'];
        $class_name = $parameters['class_name'];

        $moduleInstance = $this->extensionManager->getModule($module);
        $moduleSourcePath = $moduleInstance->getSourcePath();
        $modulePath = $moduleInstance->getPath() . '/' . $module;
        $modulePluginClass = $moduleSourcePath . '/Plugin/' . $class_name;
        $moduleServiceYaml = $modulePath . '.services.yml';
        $parameters['file_exists'] = file_exists($moduleServiceYaml);
        $directory = $modulePluginClass;

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        
        $this->renderFile(
            'module/src/Annotation/plugin-type.php.twig',
            $moduleSourcePath . '/Annotation/' . $class_name . '.php',
            $parameters
        );

        $this->renderFile(
            'module/src/plugin-type-annotation-base.php.twig',
            $modulePluginClass . 'Base.php',
            $parameters
        );

        $this->renderFile(
            'module/src/plugin-type-annotation-interface.php.twig',
            $modulePluginClass . 'Interface.php',
            $parameters
        );

        $this->renderFile(
            'module/src/plugin-type-annotation-manager.php.twig',
            $modulePluginClass . 'Manager.php',
            $parameters
        );
        $this->renderFile(
            'module/plugin-annotation-services.yml.twig',
            $moduleServiceYaml,
            $parameters,
            FILE_APPEND
        );
    }
}
