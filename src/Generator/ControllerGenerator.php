<?php

/**
 * @file
 * Contains Drupal\AppConsole\Generator\ControllerGenerator.
 */

namespace Drupal\AppConsole\Generator;

class ControllerGenerator extends Generator
{
    public function generate($module, $class_name, $routes, $test, $services, $class_machine_name)
    {
        $parameters = [
          'class_name' => $class_name,
          'services' => $services,
          'module' => $module,
          'class_machine_name' => $class_machine_name,
          'routes' => $routes,
          'learning' => $this->isLearning(),
        ];

        $this->renderFile(
            'module/src/Controller/controller.php.twig',
            $this->getControllerPath($module).'/'.$class_name.'.php',
            $parameters
        );

        $this->renderFile(
            'module/routing-controller.yml.twig',
            $this->getModulePath($module).'/'.$module.'.routing.yml',
            $parameters,
            FILE_APPEND
        );

        if ($test) {
            $this->renderFile(
                'module/Tests/Controller/controller.php.twig',
                $this->getTestPath($module, 'Controller').'/'.$class_name.'Test.php',
                $parameters
            );
        }
    }
}
