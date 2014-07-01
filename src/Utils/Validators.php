<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Utils\Validators.
 */

namespace Drupal\AppConsole\Utils;

class Validators
{

  public function __construct()
  {
  }

  // TODO: validate module name
  public function validateModuleName($module)
  {
    if (!empty($module))
      return $module;
    else
      throw new \InvalidArgumentException(sprintf('Module name "%s" is invalid.', $module));
  }

  public function validateModulePath($module_path)
  {
    if (!is_dir($module_path)) {
      throw new \InvalidArgumentException(sprintf(
        'Module path "%s" is invalid. You need to provide a valid path.',
        $module_path)
      );
    }

    return $module_path;
  }

  /**
   * Validate if module name exist
   * @param  string $module  Module name
   * @param  array  $modules List of modules
   * @return string
   */
  public function validateModuleExist($module, $modules)
  {
    if (!in_array($module, array_values($modules))) {
      throw new \InvalidArgumentException(sprintf(
        'Module "%s" is invalid. You can use first generate:module command.',
        $module)
      );
    }

    return $module;
  }

  /**
   * Validate if service name exist
   * @param  string $service  Service name
   * @param  array  $services Array of services
   * @return string
   */
  public function validateServiceExist($service, $services)
  {
    if ($service == '') {
      return null;
    }

    if (!in_array($service, array_values($services))) {
      throw new \InvalidArgumentException(sprintf("Service \"%s\" is invalid.", $service));
    }

    return $service;
  }
}
