<?php

/**
 * @file
 * Contains Drupal\Console\Generator\ControllerGenerator.
 */

namespace Drupal\Console\Generator;

class ControllerGenerator extends Generator
{
    public function generate($module, $class, $routes, $test, $services)
    {
        $parameters = [
          'class_name' => $class,
          'services' => $services,
          'module' => $module,
          'routes' => $routes,
          'learning' => $this->isLearning(),
        ];

        $this->renderFile(
            'module/src/Controller/controller.php.twig',
            $this->getSite()->getControllerPath($module).'/'.$class.'.php',
            $parameters
        );

        $this->renderFile(
            'module/routing-controller.yml.twig',
            $this->getSite()->getModulePath($module).'/'.$module.'.routing.yml',
            $parameters,
            FILE_APPEND
        );

        if ($test) {
            $this->renderFile(
                'module/Tests/Controller/controller.php.twig',
                $this->getSite()->getTestPath($module, 'Controller').'/'.$class.'Test.php',
                $parameters
            );
        }
    }
}
