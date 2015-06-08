<?php

/**
 * @file
 * Contains Drupal\AppConsole\Command\GeneratorFormBaseCommand.
 */
namespace Drupal\AppConsole\Command;

class GeneratorConfigFormBaseCommand extends GeneratorFormCommand
{
    protected function configure()
    {
        $this->setFormType('ConfigFormBase');
        $this->setCommandName('generate:form:config');
        parent::configure();
    }
}
