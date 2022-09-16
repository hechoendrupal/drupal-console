<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginBlockGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginRulesActionGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginRulesActionGenerator constructor.
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

        $this->renderFile(
            'module/src/Plugin/RulesAction/rulesaction.php.twig',
            $this->extensionManager->getPluginPath($module, 'RulesAction') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
