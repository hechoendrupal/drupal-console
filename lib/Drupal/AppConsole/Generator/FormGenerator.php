<?php

namespace Drupal\AppConsole\Generator;

use Symfony\Component\DependencyInjection\Container;

class FormGenerator extends Generator {

  public function __construct() {}

  public function generate($module, $class_name, $services, $inputs, $generate_config, $update_routing) {

    $path = DRUPAL_ROOT . '/' . drupal_get_path('module', $module);

    $path_controller = $path . '/lib/Drupal/' . $module . '/Form';

    $parameters = array(
      'name' => $class_name,
      'services' => $services,
      'inputs' => $inputs,
      'module' => $module
    );

    $this->renderFile(
      'module/module.DefaultForm.php.twig',
      $path_controller . '/'. $class_name .'.php',
      $parameters
    );

    if ($generate_config)
      $this->renderFile('module/module.config.yml.twig', $path .'/config/'. strtolower($class_name).'_config.yml', $parameters, FILE_APPEND);

    if ($update_routing)
      $this->renderFile('module/module.routing.yml.twig', $path .'/'. $module.'.routing.yml', $parameters, FILE_APPEND);
  }

}
