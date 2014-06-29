<?php
/**
 * @file
 * Containt Drupal\AppConsole\Generator\ControllerGenerator.
 */

namespace Drupal\AppConsole\Generator;

class ControllerGenerator extends Generator
{

  public function generate($module, $class_name, $method_name, $route, $test, $services)
  {
    $path = DRUPAL_ROOT.'/'.drupal_get_path('module', $module);

    $path_controller = $path.'/src/Controller';

    $parameters = array(
      'class_name' => $class_name,
      'services' => $services,
      'module' => $module,
      'method_name' => $method_name,
      'route' => $route,
    );

    $this->renderFile(
      'module/module.controller.php.twig',
      $path_controller.'/'.$class_name.'.php',
      $parameters
    );

    $this->renderFile(
      'module/controller-routing.yml.twig',
      $path.'/'.$module.'.routing.yml',
      $parameters,
      FILE_APPEND
    );

    if ($test) {
      $this->renderFile(
        'module/module.test.twig',
        $path.'/src/Tests/'.$class_name.'Test.php',
        $parameters
      );
    }
  }

}
