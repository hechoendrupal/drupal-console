<?php

/**
 * @file
 * Contains Drupal\Console\Command\GeneratorFormBaseCommand.
 */

namespace Drupal\Console\Command;

class GeneratorFormBaseCommand extends GeneratorFormCommand
{
    protected function configure()
    {
        $this->setFormType('FormBase');
        $this->setCommandName('generate:form');
        parent::configure();
    }
}
