<?php

namespace Drupal\AppConsole\Generator;

use Symfony\Component\DependencyInjection\Container;

class FormGenerator extends Generator {

  public function __construct() {}

    public function generate($module, $class_name, $services, $inputs, $update_routing) {

    $path = DRUPAL_ROOT . '/' . drupal_get_path('module', $module);

    $path_controller = $path . '/lib/Drupal/' . $module . '/Form';

    $parameters = array(
      'class_name' => $class_name,
      'services' => $services,
      'inputs' => $inputs,
      'module_name' => $module,
    );

    $this->renderFile(
      'module/module.form.php.twig',
      $path_controller . '/'. $class_name .'.php',
      $parameters
    );

    if ($update_routing)
      $this->renderFile('module/form-routing.yml.twig', $path .'/'. $module.'.routing.yml', $parameters, FILE_APPEND);
  }

}
