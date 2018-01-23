<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\BreakPointGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Generator\GeneratorInterface;

/**
 * Class BreakPointGenerator
 *
 * @package Drupal\Console\Generator
 */
class BreakPointGenerator extends Generator implements GeneratorInterface
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * BreakPointGenerator constructor.
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
        $theme_path = $this->extensionManager->getTheme($parameters['theme'])->getPath();

        $this->renderFile(
            'theme/breakpoints.yml.twig',
            $theme_path . '/' . $parameters['machine_name'] . '.breakpoints.yml',
             $parameters,
            FILE_APPEND
        );
    }
}
