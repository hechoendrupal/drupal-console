<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\CommandGenerator.
 */

namespace Drupal\Console\Generator;

class CommandGenerator extends Generator
{
    /**
     * Generator Plugin Block.
     *
     * @param string  $module         Module name
     * @param string  $name           Command name
     * @param string  $class          Class name
     * @param boolean $containerAware Container Aware command
     */
    public function generate($module, $name, $class, $containerAware)
    {
        $command_key = 'command.'.str_replace(':', '.', $name);

        $parameters = [
          'module' => $module,
          'name' => $name,
          'class' => $class,
          'container_aware' => $containerAware,
          'command_key' => $command_key,
        ];

        $messages[$command_key.'.description'] = 'Greet someone';
        $messages[$command_key.'.arguments.name'] = 'Who do you want to greet?';
        $messages[$command_key.'.options.yell'] = 'If set, the task will yell in uppercase letters';

        $translator = $this->getTranslator();
        $translator->writeTranslationsByModule($module, $messages);

        $this->renderFile(
            'module/src/Command/command.php.twig',
            $this->getSite()->getCommandPath($module).'/'.$class.'.php',
            $parameters
        );
    }
}
