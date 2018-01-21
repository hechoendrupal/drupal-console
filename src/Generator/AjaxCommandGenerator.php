<?php

/**
 * @file
 * Contains Drupal\Console\Generator\AjaxCommandGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Generator\GeneratorInterface;
use Drupal\Console\Extension\Manager;

/**
 * Class AjaxCommandGenerator
 *
 * @package Drupal\Console\Generator
 */
class AjaxCommandGenerator extends Generator implements GeneratorInterface
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * AuthenticationProviderGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * @param $parameters array
     */
    public function generate($parameters = [])
    {
        $class = $parameters['class_name'];
        $module = $parameters['module'];

        $this->renderFile(
            'module/src/Ajax/ajax-command.php.twig',
            $this->extensionManager->getModule($module)->getAjaxPath() . '/' . $class . '.php',
            $parameters
        );

        $this->renderFile(
            'module/js/commands.php.twig',
            $this->extensionManager->getModule($module)->getPath() . '/js/custom.js',
            $parameters
        );

        $this->renderFile(
            'module/module-libraries.yml.twig',
            $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.libraries.yml',
            $parameters
        );
    }
}
