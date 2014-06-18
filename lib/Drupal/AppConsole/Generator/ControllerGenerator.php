<?php

namespace Drupal\AppConsole\Generator;

class ControllerGenerator extends Generator
{
  private $filesystem;

  public function __construct() {}

  public function generate($module, $name, $controller, $services, $test)
  {
    $path = DRUPAL_ROOT . '/' . drupal_get_path('module', $module);

    $path_controller = $path . '/src/Controller';

    $parameters = array(
      'name' => $name,
      'services' => $services,
      'module' => $module
    );

    $this->renderFile(
      'module/module.controller.php.twig',
      $path_controller . '/'. $name .'.php',
      $parameters
    );

    $this->renderFile('module/controller-routing.yml.twig',
        DRUPAL_ROOT.'/modules/'.$module.'/'.$module.'.routing.yml',
        $parameters,
        FILE_APPEND
    );

    if ($test) {
      $this->renderFile(
          'module/module.test.twig',
          $path . '/src/Tests/' . $name . 'Test.php',
          $parameters
      );
    }
  }

}
