<?php

/**
 * @file
 * Contains Drupal\Console\Generator\AjaxCommandGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

/**
 * Class AjaxCommandGenerator
 *
 * @package Drupal\Console\Generator
 */
class AjaxCommandGenerator extends Generator
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
        $class = $parameters['class_name'];
        $module = $parameters['module'];
        $js_name = $parameters['js_name'];

        $moduleInstance = $this->extensionManager->getModule($module);
        $moduleDir = $moduleInstance->getPath();
        $this->renderFile(
            'module/src/Ajax/ajax-command.php.twig',
            $moduleInstance->getAjaxPath() . '/' . $class . '.php',
            $parameters
        );

        $this->renderFile(
            'module/js/commands.php.twig',
            $moduleDir . '/js/' .$js_name. '.js',
            $parameters
        );

        $this->renderFile(
            'module/module-libraries.yml.twig',
            $moduleDir . '/' . $module . '.libraries.yml',
            $parameters
        );
    }
}
