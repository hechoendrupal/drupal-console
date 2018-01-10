<?php

/**
 * @file
 * Contains Drupal\Console\Generator\AjaxCommandGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class AjaxCommandGenerator extends Generator
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

    public function generate($module, $class, $method)
    {
        $parameters = [
                'class_name' => $class,
                'module' => $module,
              'method' => $method
        ];

        $this->renderFile(
            'module/src/Ajax/ajax-command.php.twig',
            $this->extensionManager->getModule($module)->getAjaxPath().'/'.$class.'.php',
            $parameters
        );

        $this->renderFile(
            'module/js/commands.php.twig',
            $this->extensionManager->getModule($module)->getPath().'/js'.'/'.'custom.js',
            $parameters
        );

        $this->renderFile(
            'module/module-libraries.yml.twig',
            $this->extensionManager->getModule($module)->getPath().'/'.$module.'.libraries.yml',
            $parameters
        );
    }
}
