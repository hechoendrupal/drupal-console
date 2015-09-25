<?php

/**
 * @file
 * Contains \Drupal\Console\Command\GeneratorEntityContentCommand.
 */

namespace Drupal\Console\Command;

class GeneratorEntityContentCommand extends GeneratorEntityCommand
{
    protected function configure()
    {
        $this->setEntityType('EntityContent');
        $this->setCommandName('generate:entity:content');
        parent::configure();
    }
}
