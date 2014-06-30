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

    // set syntax for arguments
    $args = ', ';
    $i = 0;
    foreach ($services as $service) {
      $args .= $service['short'] . ' $' . $service['machine_name'];
      if ( ++$i != count($services)) {
        $args .= ', ';
      }
    }

    $parameters = [
      'module'   => $module,
      'service_name'     => $service_name,
      'name'     => [
        'class'      => $class_name,
        'underscore' => $this->camelCaseToUnderscore($class_name)
      ],
      'services'    => $services,
      'args'   => $args,
      'file_exists' => file_exists($module_path.'/'.$module.'.services.yml'),
    ];

    $this->renderFile(
      'module/services.yml.twig',
      $module_path.'/'.$module.'.services.yml',
      $parameters,
      FILE_APPEND
    );

    $this->renderFile(
      'module/services.class.php.twig',
      $module_path.'/src/'. $class_name .'.php',
      $parameters
    );

  }

}
