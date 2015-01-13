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

    $command_key_root = 'command.' . str_replace(':','.', $command);

    $parameters = [
      'module_name' => $module,
      'command'     => $command,
      'name'        => [
        'class'     => $class_name,
      ],
      'container'   => $container,
      'command_key_root' => $command_key_root
    ];

    $messages[$command_key_root . '.description'] =  'Greet someone';
    $messages[$command_key_root . '.arguments.name'] =  'Who do you want to greet?';
    $messages[$command_key_root . '.options.yell'] =  'If set, the task will yell in uppercase letters';

    $translator = $this->getTranslator();
    $translator->writeTranslationsByModule($module, $messages);

    $this->renderFile(
      'module/src/Command/command.php.twig',
      $this->getCommandPath($module).'/'.$class_name.'.php',
      $parameters
    );
  }
}
