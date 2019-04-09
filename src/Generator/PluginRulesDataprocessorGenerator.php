<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginBlockGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginRulesDataprocessorGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PluginRulesDataprocessorGenerator constructor.
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
          'module/src/Plugin/RulesDataProcessor/rulesdataprocessor.php.twig',
          $this->extensionManager->getPluginPath($module, 'RulesDataProcessor') . '/' . $class_name . '.php',
          $parameters
        );
    }
}
