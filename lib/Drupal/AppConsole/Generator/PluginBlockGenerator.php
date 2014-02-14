<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Generator\PluginBlockGenerator.
 */

namespace Drupal\AppConsole\Generator;

class PluginBlockGenerator extends Generator {

  /**
   * Generator Plugin Block
   * @param  string $module   Module name
   * @param  string $name     class name for plugin block
   * @param  array  $services list of services
   */
  public function generate($module, $class_name, $description, $services) {

    $path = DRUPAL_ROOT . '/' . drupal_get_path('module', $module);
    $path_plugin = $path . '/lib/Drupal/' . $module . '/Plugin/Block';

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
      'name'     => [
        'class'      => $class_name,
        'underscore' => $this->camelCaseToUnderscore($class_name)
      ],
      'description' => $description,
      'services'    => $services,
      'args'   => $args
    ];

    $this->renderFile(
      'module/plugin-DefaultBlock.php.twig',
      $path_plugin . '/'. $class_name .'.php',
      $parameters
    );
  }

}
