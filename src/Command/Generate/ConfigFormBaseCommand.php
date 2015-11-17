<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\FormBaseCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Generate\FormCommand;

class ConfigFormBaseCommand extends FormCommand
{
    protected function configure()
    {
        $this->setFormType('ConfigFormBase');
        $this->setCommandName('generate:form:config');
        parent::configure();
    }
}
