<?php

/**
 * @file
 * Contains Drupal\Console\Command\GeneratorFormBaseCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Generate\FormCommand;

class FormBaseCommand extends FormCommand
{
    protected function configure()
    {
        $this->setFormType('FormBase');
        $this->setCommandName('generate:form');
        parent::configure();
    }
}
