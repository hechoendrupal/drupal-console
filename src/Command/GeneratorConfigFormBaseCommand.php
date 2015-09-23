<?php

/**
 * @file
 * Contains Drupal\Console\Command\GeneratorFormBaseCommand.
 */

namespace Drupal\Console\Command;

class GeneratorConfigFormBaseCommand extends GeneratorFormCommand
{
    protected function configure()
    {
        $this->setFormType('ConfigFormBase');
        $this->setCommandName('generate:form:config');
        parent::configure();
    }
}
