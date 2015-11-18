<?php

/**
 * @file
 * Contains \Drupal\Console\Command\GeneratorEntityContentCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Command\Generate\EntityCommand;

class EntityContentCommand extends EntityCommand
{
    protected function configure()
    {
        $this->setEntityType('EntityContent');
        $this->setCommandName('generate:entity:content');
        parent::configure();
    }
}
