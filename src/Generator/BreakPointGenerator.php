<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\BreakPointGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

/**
 * Class BreakPointGenerator
 *
 * @package Drupal\Console\Generator
 */
class BreakPointGenerator extends Generator
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
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }


    /**
     * Generator BreakPoint.
     *
     * @param $theme
     * @param $breakpoints
     * @param $machine_name
     */
    public function generate($theme, $breakpoints, $machine_name)
    {
        $parameters = [
          'theme' => $theme,
          'breakpoints' => $breakpoints,
          'machine_name' => $machine_name
        ];

        $theme_path =  $this->extensionManager->getTheme($theme)->getPath();

        $this->renderFile(
            'theme/breakpoints.yml.twig',
            $theme_path .'/'.$machine_name.'.breakpoints.yml',
            $parameters,
            FILE_APPEND
        );
    }
}
