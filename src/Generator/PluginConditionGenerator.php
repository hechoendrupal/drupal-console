<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginConditionGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

/**
 * Class PluginConditionGenerator
 *
 * @package Drupal\Console\Generator
 */
class PluginConditionGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginConditionGenerator constructor.
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
        $context_definition_id = $parameters['context_definition_id'];
        $parameters['context_id'] = str_replace('entity:', '', $context_definition_id);

        $this->renderFile(
            'module/src/Plugin/Condition/condition.php.twig',
            $this->extensionManager->getPluginPath($module, 'Condition') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
