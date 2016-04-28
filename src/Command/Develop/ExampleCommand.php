<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Example.
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class ExampleCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('example');
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
        /**
         * DrupalConsole extends the SymfonyStyle class to provide
         * an standardized Output Formatting Style.
         *
         * Drupal Console provides the DrupalStyle helper class:
         */
        $io = new DrupalStyle($input, $output);
        $io->simple('This text could be translatable by');
        $io->simple('adding a YAML file at "config/translations/LANGUAGE"');

        /**
         *  By using ContainerAwareCommand as the base class for the command
         *  (instead of the more basic Command), you have access to
         *  the service container.
         *
         *  In other words, you have access to any configured service using
         *  the provided getService method.
         *
         *  $this->getService('entity_type.manager');
         *
         *  Reading user input argument
         *  $input->getArgument('ARGUMENT_NAME');
         *
         *  Reading user input option
         *  $input->getOption('OPTION_NAME');
         */
    }

}
