<?php

/**
 * @file
 * Contains \Drupal\Console\Command\GeneratorEntityContentCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Command\GeneratorEntityCommand;

class EntityContentCommand extends GeneratorEntityCommand
{
    protected function configure()
    {
        $this->setEntityType('EntityContent');
        $this->setCommandName('generate:entity:content');
        parent::configure();
    }
}
