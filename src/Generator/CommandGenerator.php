<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Generator\CommandGenerator.
 */

namespace Drupal\AppConsole\Generator;

class CommandGenerator extends Generator
{
  /**
   * Generator Plugin Block
   * @param  string $module     Module name
   * @param  string $command    Command name
   * @param  string $class_name class name for plugin block
   * @param  array  $container  Access to container class
   */
  public function generate($module, $command, $class_name, $container)
  {

    $parameters = [
      'module_name' => $module,
      'command'     => $command,
      'name'        => [
        'class'     => $class_name,
      ],
      'container'   => $container,
    ];

    $this->renderFile(
      'module/command.php.twig',
      $path_plugin . '/'. $class_name .'.php',
      $parameters
    );
  }
}
