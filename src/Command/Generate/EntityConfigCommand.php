<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\EntityConfigCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Generate\EntityCommand;

class EntityConfigCommand extends EntityCommand
{
    protected function configure()
    {
        $this->setEntityType('EntityConfig');
        $this->setCommandName('generate:entity:config');
        parent::configure();
    }
}
