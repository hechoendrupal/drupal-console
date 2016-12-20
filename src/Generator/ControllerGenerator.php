<?php

/**
 * @file
 * Contains Drupal\Console\Generator\ControllerGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Extension\Manager;

class ControllerGenerator extends Generator
{
    /**
     * @var Manager  
     */
    protected $extensionManager;

    /**
     * AuthenticationProviderGenerator constructor.
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    public function generate($module, $class, $routes, $test, $services)
    {
        $parameters = [
          'class_name' => $class,
          'services' => $services,
          'module' => $module,
          'routes' => $routes,
          //'learning' => $this->isLearning(),
        ];

        $this->renderFile(
            'module/src/Controller/controller.php.twig',
            $this->extensionManager->getModule($module)->getControllerPath().'/'.$class.'.php',
            $parameters
        );

        $this->renderFile(
            'module/routing-controller.yml.twig',
            $this->extensionManager->getModule($module)->getPath().'/'.$module.'.routing.yml',
            $parameters,
            FILE_APPEND
        );

        if ($test) {
            $this->renderFile(
                'module/Tests/Controller/controller.php.twig',
                $this->extensionManager->getModule($module)->getTestPath('Controller').'/'.$class.'Test.php',
                $parameters
            );
        }
    }
}
