<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cron\DebugCommand.
 */

namespace Drupal\Console\Command\Cron;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class DebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cron:debug')
            ->setDescription($this->trans('commands.cron.debug.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $module_handler = $this->getModuleHandler();

        $io->section(
            $this->trans('commands.cron.debug.messages.module-list')
        );

        $io->table(
            [$this->trans('commands.cron.debug.messages.module')],
            $module_handler->getImplementations('cron'),
            'compact'
        );
    }
}
