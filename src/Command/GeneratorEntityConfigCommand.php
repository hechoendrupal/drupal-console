<?php

/**
 * @file
 * Contains \Drupal\Console\Command\GeneratorEntityConfigCommand.
 */

namespace Drupal\Console\Command;

class GeneratorEntityConfigCommand extends GeneratorEntityCommand
{
    protected function configure()
    {
        $this->setEntityType('EntityConfig');
        $this->setCommandName('generate:entity:config');
        parent::configure();
    }
}
