<?php
/**
 * @file
 * Containt Drupal\AppConsole\Generator\FormGenerator.
 */
namespace Drupal\AppConsole\Generator;

use Drupal\AppConsole\Utils\Utils;

class FormGenerator extends Generator
{
  /**
   * @param  $module
   * @param  $class_name
   * @param  $services
   * @param  $inputs
   * @param  $update_routing
   */
  public function generate($module, $class_name, $services, $inputs, $update_routing)
  {

    $parameters = array(
      'class_name' => $class_name,
      'services' => $services,
      'inputs' => $inputs,
      'module_name' => $module,
      'form_id' => Utils::camelCaseToMachineName($class_name),
    );

    $this->renderFile(
      'module/module.form.php.twig',
      $path_form . '/'. $class_name .'.php',
      $parameters
    );

    if ($update_routing) {
      $this->renderFile('module/form-routing.yml.twig', $path .'/'. $module.'.routing.yml', $parameters, FILE_APPEND);
    }
  }
}
