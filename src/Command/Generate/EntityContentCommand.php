<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\EntityContentCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Generate\EntityCommand;
use Drupal\Console\Generator\EntityContentGenerator;

class EntityContentCommand extends EntityCommand
{
    protected function configure()
    {
        $this->setEntityType('EntityContent');
        $this->setCommandName('generate:entity:content');
        parent::configure();
    }

    protected function createGenerator()
    {
        return new EntityContentGenerator();
    }
}
