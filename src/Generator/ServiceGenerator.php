<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Generator\ServiceGenerator.
 */

namespace Drupal\AppConsole\Generator;

class ServiceGenerator extends Generator
{
  /**
   * Generator Service
   * @param  string $module       Module name
   * @param  string $service_name Service name
   * @param  string $class_name   Class name
   * @param  array  $services     List of services
   */
  public function generate($module, $service_name, $class_name, $services)
  {

    $parameters = [
      'module'   => $module,
      'service_name'     => $service_name,
      'name'     => [
        'class'      => $class_name,
        'underscore' => $this->camelCaseToUnderscore($class_name)
      ],
      'services'    => $services,
      'file_exists' => file_exists($this->getModulePath($module).'/'.$module.'.services.yml'),
    ];

    $this->renderFile(
      'module/services.yml.twig',
      $this->getModulePath($module).'/'.$module.'.services.yml',
      $parameters,
      FILE_APPEND
    );

    $this->renderFile(
      'module/services.class.php.twig',
      $this->getModulePath($module).'/src/'. $class_name .'.php',
      $parameters
    );

  }

}
