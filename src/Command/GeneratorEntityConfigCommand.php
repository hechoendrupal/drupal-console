<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorEntityConfigCommand.
 */

namespace Drupal\AppConsole\Command;

class GeneratorEntityConfigCommand extends GeneratorEntityCommand
{
    protected function configure()
    {
        $this->setEntityType('EntityConfig');
        $this->setCommandName('generate:entity:config');
        parent::configure();
    }
}
