<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\ExampleContainerAwareCommand.
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ExampleContainerAwareCommand
 * @package Drupal\Console\Command\Develop
 */
class ExampleContainerAwareCommand extends Command
{
    use ContainerAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('develop:example:container:aware');
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
         * config/services/namespace.yml file and add the `console.command` tag.
         *
         * develop_example_container_aware:
         *   class: Drupal\Console\Command\Develop\ExampleContainerAwareCommand
         *   tags:
         *     - { name: console.command }
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
         *  By using ContainerAwareCommandTrait on your class for the command
         *  (instead of the more basic CommandTrait), you have access to
         *  the service container.
         *
         *  In other words, you can access to any configured Drupal service
         *  using the provided getService method.
         *
         *  $this->getDrupalService('entity_type.manager');
         *
         *  Reading user input argument
         *  $input->getArgument('ARGUMENT_NAME');
         *
         *  Reading user input option
         *  $input->getOption('OPTION_NAME');
         */
    }
}
