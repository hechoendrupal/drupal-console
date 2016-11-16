<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Example.
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ExampleCommand
 * @package Drupal\Console\Command\Develop
 */
class ExampleCommand extends Command
{
    use CommandTrait;
    /**
     * {@inheritdoc}
     */

    /**
     * ExampleCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('develop:example');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* Register your command as a service
         *
         * Make sure you register your command class at
         * config/services/namespace.yml file and add the `drupal.command` tag.
         *
         * develop_example:
         *   class: Drupal\Console\Command\Develop\ExampleCommand
         *   arguments: ['@service_id', '@console.service_id']
         *   tags:
         *     - { name: drupal.command }
         *
         * NOTE: Make the proper changes on the namespace and class
         *       according your new command.
         *
         * DrupalConsole extends the SymfonyStyle class to provide
         * an standardized Output Formatting Style.
         *
         * Drupal Console provides the DrupalStyle helper class:
         */
        $io = new DrupalStyle($input, $output);
        $io->simple('This text could be translatable by');
        $io->simple('adding a YAML file at "config/translations/LANGUAGE/command.name.yml"');

        /**
         *  Reading user input argument
         *  $input->getArgument('ARGUMENT_NAME');
         *
         *  Reading user input option
         *  $input->getOption('OPTION_NAME');
         */
    }
}
