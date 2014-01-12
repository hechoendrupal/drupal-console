<?php

namespace Drupal\AppConsole\Command;

class Validators {

  // TODO: validate module name
  public static function validateModuleName($module){
    return $module;
  }

  // TODO: validate module name
  public static function validateModulePath($module_path){
    if(!is_dir($module_path)) {
      throw new \InvalidArgumentException(sprintf('Module path "%s" is invalid. You need to provide a valid path.', $module_path));
    }
  }

  /**
   * Validate if module name exist
   * @param  [type] $module  Module name
   * @param  [type] $modules List of modules
   * @return [type]          Module name
   */
  static function validateModuleExist($module, $modules) {
    if (!in_array($module, array_values($modules))) {
      throw new \InvalidArgumentException(sprintf('Module "%s" is invalid. You can use first generate:module command.', $module));
    }
    return $module;
  }

  /**
   * Validate if service name exist
   * @param  [type] $service  [description]
   * @param  [type] $services [description]
   * @return [type]           [description]
   */
  static function validateServiceExist($service, $services) {

    if ($service == ''){
      return null;
    }

    if (!in_array($service, array_values($services))) {
      throw new \InvalidArgumentException(sprintf('Service "%s" is invalid.\n %s', $service, $services));
    }

    return $service;
  }



}
