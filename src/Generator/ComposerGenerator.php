<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ComposerGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

/**
 * Class ComposerGenerator
 *
 * @package Drupal\Console\Generator
 */
class ComposerGenerator extends Generator
{

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * AjaxCommandGenerator constructor.
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
        $machineName = $parameters['machine_name'];
        $module = $this->extensionManager->getModule($machineName);
        if (!$module) {
          throw new \Exception(
          "Unable to load module: \"{$machineName}\" from extension manager. This may be an unresolved issue in the module generator. This will prevent the generator from creating the composer.json file for the module. Try calling the command without setting dependencies. See https://github.com/hechoendrupal/drupal-console/issues/4118");
        }
        $composerPath = !is_null($parameters['package_path']) ?
          $parameters['package_path'] . '/' . $machineName . '/composer.json' :
          $module->getPath() . '/composer.json';
        $this->renderFile(
          'module/composer.json.twig',
          $composerPath,
          $parameters
        );
    }

}
