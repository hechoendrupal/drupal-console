<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cron\DebugCommand.
 */

namespace Drupal\Console\Command\Cron;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
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
        $output = new DrupalStyle($input, $output);
        $table = new Table($output);
        $table->setStyle('compact');

        $module_handler = $this->getModuleHandler();

        $output->section(
            $this->trans('commands.cron.debug.messages.module-list')
        );

        $table->setHeaders(
            [
                $this->trans('commands.cron.debug.messages.module'),
            ]
        );

        foreach ($module_handler->getImplementations('cron') as $module) {
            $table->addRow(
                [
                    $module,
                ]
            );
        }

        $table->render();
    }
}
