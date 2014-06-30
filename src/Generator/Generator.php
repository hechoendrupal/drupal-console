<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drupal\AppConsole\Generator;

use Drupal\AppConsole\Utils\Utils;

class Generator
{
  private $skeletonDirs;

  private $module_path;

  const PLUGIN_BLOCK = 'Block';

  /**
   * Sets an array of directories to look for templates.
   *
   * The directories must be sorted from the most specific to the most
   * directory.
   *
   * @param array $skeletonDirs An array of skeleton dirs
   */
  public function setSkeletonDirs($skeletonDirs)
  {
    $this->skeletonDirs = is_array($skeletonDirs) ? $skeletonDirs : array($skeletonDirs);
  }

  protected function render($template, $parameters)
  {
    $twig = new \Twig_Environment(new \Twig_Loader_Filesystem($this->skeletonDirs), array(
      'debug'            => true,
      'cache'            => false,
      'strict_variables' => true,
      'autoescape'       => false,
    ));

    $twig->addFunction($this->getServicesAsParameters());
    $twig->addFunction($this->getServicesAsParametersKeys());
    $twig->addFunction($this->getArgumentsFromRoute());

    return $twig->render($template, $parameters);
  }

  protected function renderFile($template, $target, $parameters, $flag=null)
  {
    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0777, true);
    }

    return file_put_contents($target, $this->render($template, $parameters), $flag);
  }

  public function camelCaseToUnderscore($camel_case)
  {
    return Utils::camelCaseToUnderscore($camel_case);
  }

  public function camelCaseToMachineName($camel_case)
  {
    return Utils::camelCaseToMachineName($camel_case);
  }

  public function getModulePath($module_name)
  {
    if (!$this->module_path) {
        $this->module_path = DRUPAL_ROOT.'/'.drupal_get_path('module', $module_name);
    }

    return $this->module_path;
  }

  public function getControllerPath($module_name)
  {
    return $this->getModulePath($module_name).'/src/Controller';
  }

  public function getTestPath($module_name)
  {
    return $this->getModulePath($module_name).'/src/Tests';
  }

  public function getFormPath($module_name)
  {
    return $this->getModulePath($module_name).'/src/Form';
  }

  public function getPluginPath($module_name, $plugin_type)
  {
    return $this->getModulePath($module_name).'/src/Plugin/'.$plugin_type;
  }

  public function getServicesAsParameters()
  {
    $servicesAsParameters = new \Twig_SimpleFunction('servicesAsParameters', function ($services) {
      $parameters = [];
      foreach ($services as $service) {
          $parameters[] = sprintf('%s $%s', $service['short'], $service['machine_name']);
      }

      return $parameters;
    });

    return $servicesAsParameters;
  }

  public function getServicesAsParametersKeys()
  {
    $servicesAsParametersKeys = new \Twig_SimpleFunction('servicesAsParametersKeys', function ($services) {
      $parameters = [];
      foreach ($services as $service) {
        $parameters[] = sprintf('"@%s"', $service['name']);
      }

      return $parameters;
    });

    return $servicesAsParametersKeys;
  }

  public function getArgumentsFromRoute()
  {
    $argumentsFromRoute = new \Twig_SimpleFunction('argumentsFromRoute', function ($route) {
      $parameters = array_filter(explode("/", $route), function ($value) {
        return (strpos($value, "}") > 0) ? : false;
      });
      $parameters = array_map(function ($value) {
        return "$".substr($value, 1, -1);
      }, $parameters);

      return $parameters;
    });

    return $argumentsFromRoute;
  }
}
