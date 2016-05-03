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
        $command_key = str_replace(':', '.', $name);

        $parameters = [
          'module' => $module,
          'name' => $name,
          'class' => $class,
          'container_aware' => $containerAware,
          'command_key' => $command_key,
        ];

        $messages['description'] = 'Say hello';
        $this->getTranslator()->writeTranslationsByCommand($module, $messages, $command_key);

        $this->renderFile(
            'module/src/Command/command.php.twig',
            $this->getSite()->getCommandPath($module).'/'.$class.'.php',
            $parameters
        );
    }
}
