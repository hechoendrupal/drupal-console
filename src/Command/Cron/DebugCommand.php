<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cron\DebugCommand.
 */

namespace Drupal\Console\Command\Cron;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;

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
        $table = $this->getTableHelper();
        $table->setlayout($table::LAYOUT_COMPACT);

        $module_handler = $this->getModuleHandler();

        $output->writeln(
            '<info>'.
            $this->trans('commands.cron.debug.messages.module-list')
            .'</info>'
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

        $table->render($output);
    }
}
