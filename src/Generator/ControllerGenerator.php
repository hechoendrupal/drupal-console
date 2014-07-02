<?php
/**
 * @file
 * Containt Drupal\AppConsole\Generator\ControllerGenerator.
 */

namespace Drupal\AppConsole\Generator;

class ControllerGenerator extends Generator
{

  public function generate($module, $class_name, $method_name, $route, $test, $services, $class_machine_name)
  {

    $parameters = array(
      'class_name' => $class_name,
      'services' => $services,
      'module' => $module,
      'method_name' => $method_name,
      'class_machine_name' => $class_machine_name,
      'route' => $route,
    );

    $this->renderFile(
      'module/module.controller.php.twig',
      $this->getControllerPath($module).'/'.$class_name.'.php',
      $parameters
    );

    $this->renderFile(
      'module/controller-routing.yml.twig',
      $this->getModulePath($module).'/'.$module.'.routing.yml',
      $parameters,
      FILE_APPEND
    );

    if ($test) {
      $this->renderFile(
        'module/module.test.twig',
        $this->getTestPath($module).'/'.$class_name.'Test.php',
        $parameters
      );
    }
  }
}
