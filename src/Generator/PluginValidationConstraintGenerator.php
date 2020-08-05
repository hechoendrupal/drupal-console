<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginValidationConstraintGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginValidationConstraintGenerator extends Generator
{

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginValidationConstraintGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(Manager $extensionManager) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $module = $parameters['module'];
        $className = $parameters['class_name'];
        $hook = $parameters['hook'];
        $pluginPath = $this->extensionManager->getPluginPath($module, 'Validation/Constraint') . '/' . $className;

        // Generates Contraint class.
        $this->renderFile(
            'module/src/Plugin/Validation/Constraint/constraint.php.twig',
            $pluginPath . '.php',
            $parameters
        );

        // Generates Validator class.
        $this->renderFile(
            'module/src/Plugin/Validation/Constraint/validator.php.twig',
            $pluginPath . 'Validator.php',
            $parameters
        );

        if (!empty($hook)) {
            $this->renderFile(
                'module/src/Entity/Bundle/entity-bundle-field-info-alter.php.twig',
                $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.module',
                $parameters,
                FILE_APPEND
            );
        }
    }
}
