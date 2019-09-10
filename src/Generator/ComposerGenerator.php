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
        $this->renderFile(
          'module/composer.json.twig',
          $this->extensionManager->getModule($machineName)
            ->getPath() . '/composer.json',
          $parameters
        );
    }

}
