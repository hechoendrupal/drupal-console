<?php

/**
 * @file
 * Contains Drupal\Console\Generator\ControllerGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Generator\GeneratorInterface;

class ControllerGenerator extends Generator implements GeneratorInterface
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
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $class = $parameters['class_name'];
        $test = $parameters['test'];
        $module = $parameters['module'];
        $moduleInstance = $this->extensionManager->getModule($module);

        $this->renderFile(
            'module/src/Controller/controller.php.twig',
            $moduleInstance->getControllerPath() . '/' . $class . '.php',
            $parameters
        );

        $this->renderFile(
            'module/routing-controller.yml.twig',
            $moduleInstance->getPath() . '/' . $module . '.routing.yml',
            $parameters,
            FILE_APPEND
        );

        if ($test) {
            $this->renderFile(
                'module/Tests/Controller/controller.php.twig',
                $moduleInstance->getTestPath('Controller') . '/' . $class . 'Test.php',
                $parameters
            );
        }
    }
}
