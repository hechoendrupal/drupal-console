<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Command\Validators.
 */

namespace Drupal\AppConsole\Command;

class Validators {

  // TODO: validate module name
  public static function validateModuleName($module){
    if (!empty($module))
      return $module;
    else
      throw new \InvalidArgumentException(sprintf('Module name "%s" is invalid.', $module));
  }

  public static function validateModulePath($module_path){
    if(!is_dir($module_path)) {
      throw new \InvalidArgumentException(sprintf('Module path "%s" is invalid. You need to provide a valid path.', $module_path));
    }
    return $module_path;
  }

  /**
   * Validate if module name exist
   * @param  string $module  Module name
   * @param  array  $modules List of modules
   * @return string          Module name
   */
  static function validateModuleExist($module, $modules) {
    if (!in_array($module, array_values($modules))) {
      throw new \InvalidArgumentException(sprintf('Module "%s" is invalid. You can use first generate:module command.', $module));
    }

    return $module;
  }

  /**
   * Validate if service name exist
   * @param  string $service  [description]
   * @param  array  $services [description]
   * @return string           [description]
   */
  static function validateServiceExist($service, $services) {

    if ($service == ''){
      return null;
    }

    if (!in_array($service, array_values($services))) {
      throw new \InvalidArgumentException(sprintf("Service \"%s\" is invalid.", $service));
    }

    return $service;
  }

}
