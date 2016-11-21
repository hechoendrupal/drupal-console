<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\ConfigFormBaseCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\FormGenerator;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\ChainQueue;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Render\ElementInfoManager;

class ConfigFormBaseCommand extends FormCommand
{
    protected function configure()
    {
        $this->setFormType('ConfigFormBase');
        $this->setCommandName('generate:form:config');
        parent::configure();
    }
}
