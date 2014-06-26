<?php
/**
 * @file
 * Containt Drupal\AppConsole\Generator\ControllerGenerator.
 */

namespace Drupal\AppConsole\Generator;

class ControllerGenerator extends Generator
{

  public function generate($module, $class_name, $test, $services, $update_routing)
  {
    $path = DRUPAL_ROOT.'/'.drupal_get_path('module', $module);

    $path_controller = $path.'/src/Controller';

    $parameters = array(
      'name' => $class_name,
      'services' => $services,
      'module' => $module
    );

    $this->renderFile(
      'module/module.controller.php.twig',
      $path_controller.'/'.$class_name.'.php',
      $parameters
    );

    if ($update_routing) {
      $this->renderFile('module/controller-routing.yml.twig',
        DRUPAL_ROOT.'/modules/'.$module.'/'.$module.'.routing.yml',
        $parameters,
        FILE_APPEND
      );
    }

    if ($test) {
      $this->renderFile(
        'module/module.test.twig',
        $path.'/src/Tests/'.$class_name.'Test.php',
        $parameters
      );
    }
  }

}
