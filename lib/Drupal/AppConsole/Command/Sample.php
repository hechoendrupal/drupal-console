<?php

namespace Drupal\AppConsole\Command;

use Drupal\AppConsole\Command\GenerateCommand;
use Drupal\AppConsole\Command\ContainerAwareCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Sample extends ContainerAwareCommand {

    protected function configure() {
        $this->setName("services:debug")
             ->setDescription("The wawa command")
             ->setDefinition(array(
                new InputOption('flag', 'f', InputOption::VALUE_NONE, 'Raise a flag'),
                new InputArgument('activities', InputArgument::IS_ARRAY, 'Space-separated activities to perform', null),
             ))
            ->setHelp(<<<EOT
The <info>test</info> command does things and stuff
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        print_r($this->getContainer());
    }

}
