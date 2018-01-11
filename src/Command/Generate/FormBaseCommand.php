<?php

/**
 * @file
 * Contains Drupal\Console\Command\GeneratorFormBaseCommand.
 */

namespace Drupal\Console\Command\Generate;

class FormBaseCommand extends FormCommand
{
    protected function configure()
    {
        $this->setFormType('FormBase');
        $this->setCommandName('generate:form');
        $this->setAliases(['gf']);
        parent::configure();
    }
}
