<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorEntityContentCommand.
 */

namespace Drupal\AppConsole\Command;

class GeneratorEntityContentCommand extends GeneratorEntityCommand
{
    protected function configure()
    {
        $this->setEntityType('EntityContent');
        $this->setCommandName('generate:entity:content');
        parent::configure();
    }
}
