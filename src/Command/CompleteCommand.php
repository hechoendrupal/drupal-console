<?php

/**
 * @file
 * Contains \Drupal\Console\CompleteCommand.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompleteCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('complete')
            ->setDescription($this->trans('commands.complete.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array_keys($this->getApplication()->all()));
    }
}
