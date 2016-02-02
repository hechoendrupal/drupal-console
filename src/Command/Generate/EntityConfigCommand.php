<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\EntityConfigCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Generate\EntityCommand;
use Drupal\Console\Generator\EntityConfigGenerator;
use Drupal\Console\Style\DrupalStyle;

class EntityConfigCommand extends EntityCommand
{
    protected function configure()
    {
        $this->setEntityType('EntityConfig');
        $this->setCommandName('generate:entity:config');
        parent::configure();

        $this->addOption(
            'bundle-of',
            null,
            InputOption::VALUE_NONE,
            $this->trans('commands.generate.entity.config.options.bundle-of')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $entity_class = $input->getOption('entity-class');
        $entity_name = $input->getOption('entity-name');
        $label = $input->getOption('label');
        $bundle_of = $input->getOption('bundle-of');
        $base_path = $input->getOption('base-path');

        $this
            ->getGenerator()
            ->generate($module, $entity_name, $entity_class, $label, $base_path, $bundle_of);
    }

    protected function createGenerator()
    {
        return new EntityConfigGenerator();
    }
}
