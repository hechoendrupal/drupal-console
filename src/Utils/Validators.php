<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Utils\Validators.
 */
namespace Drupal\AppConsole\Utils;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\HelperInterface;

class Validators extends Helper implements HelperInterface
{

  const REGEX_CLASS_NAME = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+$/';
    const REGEX_MACHINE_NAME = '/^[a-z0-9_]+$/';
  // This REGEX remove spaces between words
  const REGEX_REMOVE_SPACES = '/[\\s+]/';

    private $translator;

    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    public function validateModuleName($module)
    {
        if (!empty($module)) {
            return $module;
        } else {
            throw new \InvalidArgumentException(
        sprintf(
          $this->translator->trans('application.console.errors.invalid-modulename'),
          $module
        )
      );
        }
    }

    public function validateClassName($class_name)
    {
        if (preg_match(self::REGEX_CLASS_NAME, $class_name)) {
            return $class_name;
        } else {
            throw new \InvalidArgumentException(
        sprintf(
          $this->translator->trans('application.console.errors.invalid-classname'),
          $class_name
        )
      );
        }
    }

    public function validateMachineName($machine_name)
    {
        if (preg_match(self::REGEX_MACHINE_NAME, $machine_name)) {
            return $machine_name;
        } else {
            throw new \InvalidArgumentException(
        sprintf(
          $this->translator->trans('application.console.errors.invalid-machinename'),
          $machine_name
        )
      );
        }
    }

    public function validateModulePath($module_path, $create = false)
    {
        if (!is_dir($module_path)) {
            if ($create && mkdir($module_path, 0755, true)) {
                return $module_path;
            }

            throw new \InvalidArgumentException(
        sprintf(
          $this->translator->trans('application.console.errors.invalid-modulepath'),
          $module_path
        )
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
      throw new \InvalidArgumentException(
        sprintf(
          $this->translator->trans('application.console.errors.invalid-modulenotexists'),
          $module
        )
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
          return;
      }

      if (!in_array($service, array_values($services))) {
        throw new \InvalidArgumentException(
          sprintf(
            $this->translator->trans('application.console.errors.invalid-servicenotexists'),
            $service
          )
        );
      }
      return $service;
  }

  /**
   * Validates if class name have spaces between words
   * @param string $name
   * @return string
   */
  public function validateSpaces($name)
  {
    $string = $this->removeSpaces($name);
    if ($string == $name) {
        return $name;
    } else {
      throw new \InvalidArgumentException(
        sprintf(
          $this->translator->trans('application.console.errors.invalid-namewithspaces'),
          $name
        )
      );
    }
  }

    public function removeSpaces($name)
    {
        return preg_replace(self::REGEX_REMOVE_SPACES, '', $name);
    }

    public function getName()
    {
        return "validators";
    }
}
