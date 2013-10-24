<?php

namespace Drupal\AppConsole\Generator;

use Symfony\Component\DependencyInjection\Container;

class FormGenerator extends Generator {

  private $filesystem;

  public function __construct() {}

  public function generate($module, $name, $controller, $services ) {
    $path = DRUPAL_ROOT . '/' . drupal_get_path('module', $module);

    $path_controller = $path . '/lib/Drupal/' . $module . '/Form';

    $parameters = array(
      'name' => $name,
      'services' => $services,
      'module' => $module
    );

    $this->renderFile(
      'module/module.DefaultForm.php.twig',
      $path_controller . '/'. $name .'.php',
      $parameters
    );

    $this->renderFile('module/module.routing.yml.twig', DRUPAL_ROOT.'/modules/'.$module.'/'.$module.'.routing.yml', $parameters);

  }

}
